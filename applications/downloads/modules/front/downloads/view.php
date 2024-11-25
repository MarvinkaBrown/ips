<?php
/**
 * @brief		View File Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		10 Oct 2013
 */

namespace IPS\downloads\modules\front\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeInterface;
use DomainException;
use Exception;
use IPS\Application;
use IPS\Content\Controller;
use IPS\core\DataLayer;
use IPS\core\FrontNavigation;
use IPS\DateTime;
use IPS\Db;
use IPS\downloads\File as FileClass;
use IPS\downloads\File\PendingVersion;
use IPS\downloads\Form\LinkedScreenshots;
use IPS\File;
use IPS\File\Iterator;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Ranges;
use IPS\Http\Url;
use IPS\Image;
use IPS\Log;
use IPS\Login;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Package\Item;
use IPS\nexus\Tax;
use IPS\Output;
use IPS\Output\Plugin\Filesize;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use UnexpectedValueException;
use function count;
use function defined;
use function floatval;
use function function_exists;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View File Controller
 */
class view extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\downloads\File';

	/**
	 * File object
	 */
	protected ?FileClass $file = NULL;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		try
		{
			$this->file = FileClass::load( Request::i()->id );
			
			$this->file->container()->clubCheckRules();
			
			/* Downloading and viewing the embed does not need to check the permission, as there is a separate download permission already and embed method need to return it's own error  */
			if ( !$this->file->canView( Member::loggedIn() ) and Request::i()->do != 'download' and Request::i()->do != 'embed' )
			{
				Output::i()->error( $this->file->container()->message('npv') ?: 'node_error', '2D161/2', 403, '' );
			}
			
			if ( $this->file->primary_screenshot )
			{
				Output::i()->metaTags['og:image'] = File::get( 'downloads_Screenshots', $this->file->primary_screenshot_thumb )->url;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2D161/1', 404, '' );
		}
		
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_view.js', 'downloads', 'front' ) );
		
		parent::execute();
	}
	
	/**
	 * View File
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		/* Init */
		parent::manage();

		/* Sort out comments and reviews */
		$tabs = $this->file->commentReviewTabs();
		$_tabs = array_keys( $tabs );
		$tab = isset( Request::i()->tab ) ? Request::i()->tab : array_shift( $_tabs );
		$activeTabContents = $this->file->commentReviews( $tab );
		$commentsAndReviews = count( $tabs ) ? Theme::i()->getTemplate( 'global', 'core' )->commentsAndReviewsTabs( Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $tab, $activeTabContents, $this->file->url(), 'tab', FALSE, FALSE ), md5( $this->file->url() ) ) : NULL;
		if ( Request::i()->isAjax() and !isset( Request::i()->changelog ) )
		{
			Output::i()->output = $activeTabContents;
			return null;
		}
		
		/* Any previous versions? */
		$versionData = array( 'b_version' => $this->file->version, 'b_changelog' => $this->file->changelog, 'b_backup' => $this->file->published );
		$versionWhere = array( array( "b_fileid=?", $this->file->id ) );
		if ( !FileClass::canViewHiddenItems( NULL, $this->file->container() ) )
		{
			$versionWhere[] = array( 'b_hidden=0' );
		}
		$previousVersions = iterator_to_array( Db::i()->select( '*', 'downloads_filebackup', $versionWhere, 'b_backup DESC' )->setKeyField( 'b_id' ) );
		if ( isset( Request::i()->changelog ) and isset( $previousVersions[ Request::i()->changelog ] ) )
		{
			$versionData = $previousVersions[ Request::i()->changelog ];
		}
	
		if( Request::i()->isAjax() )
		{
			Output::i()->json( Theme::i()->getTemplate( 'view' )->changeLog( $this->file, $versionData ) );
		}
		
		/* Online User Location */
		Session::i()->setLocation( $this->file->url(), $this->file->onlineListPermissions(), 'loc_downloads_viewing_file', array( $this->file->name => FALSE ) );
		
		/* Custom Field Formatting */
		$cfields	= array();
		$fields		= $this->file->customFields();

		foreach ( new ActiveRecordIterator( Db::i()->select( 'pfd.*', array( 'downloads_cfields', 'pfd' ), NULL, 'pfd.cf_position' ), 'IPS\downloads\Field' ) as $field )
		{
			if( array_key_exists( 'field_' . $field->id, $this->file->customFields() ) )
			{
				if ( $fields[ 'field_' . $field->id ] !== null AND $fields[ 'field_' . $field->id ] !== '' )
				{
					/* Check for download permission, this is also used to determine if the file has been purchased by the viewer
					If this is flagged as a paid field, and download is not available, do not show it */
					if( $field->paid_field AND !$this->file->canDownload( Member::loggedIn() ) )
					{
						continue;
					}

					$cfields[ 'field_' . $field->id ] = array( 
						'type'	=> $field->type, 
						'key'	=> 'field_' . $field->id, 
						'value'	=> $field->displayValue( $fields[ 'field_' . $field->id ] ),
						'location'	=> $field->display_location
					);
				}
			}
		}

		/* Add JSON-ld */
		Output::i()->jsonLd['download']	= array(
			'@context'		=> "https://schema.org",
			'@type'			=> "WebApplication",
			"operatingSystem"	=> "N/A",
			'url'			=> (string) $this->file->url(),
			'name'			=> $this->file->mapped('title'),
			'description'	=> $this->file->truncated( TRUE, NULL ),
			'applicationCategory'	=> $this->file->container()->_title,
			'downloadUrl'	=> (string) $this->file->url( 'download' ),
			'dateCreated'	=> DateTime::ts( $this->file->submitted )->format( DateTimeInterface::ATOM ),
			'fileSize'		=> ( $this->file->filesize() ? Filesize::humanReadableFilesize( $this->file->filesize() ) : null ),
			'softwareVersion'	=> $this->file->version ?: '1.0',
			'author'		=> array(
				'@type'		=> 'Person',
				'name'		=> Member::load( $this->file->submitter )->name,
				'image'		=> Member::load( $this->file->submitter )->get_photo( TRUE, TRUE )
			),
			'interactionStatistic'	=> array(
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/ViewAction",
					'userInteractionCount'	=> $this->file->views
				),
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/DownloadAction",
					'userInteractionCount'	=> $this->file->downloads
				)
			)
		);

		/* Do we have a real author? */
		if( $this->file->submitter )
		{
			Output::i()->jsonLd['download']['author']['url']	= (string) Member::load( $this->file->submitter )->url();
		}

		if( $this->file->updated != $this->file->submitted )
		{
			Output::i()->jsonLd['download']['dateModified']	= DateTime::ts( $this->file->updated )->format( DateTimeInterface::ATOM );
		}

		if( $this->file->container()->bitoptions['reviews'] AND $this->file->reviews AND $this->file->averageReviewRating() )
		{
			Output::i()->jsonLd['download']['aggregateRating'] = array(
				'@type'			=> 'AggregateRating',
				'ratingValue'	=> $this->file->averageReviewRating(),
				'reviewCount'	=> $this->file->reviews,
				'bestRating'	=> Settings::i()->reviews_rating_out_of
			);
		}

		if( $this->file->screenshots()->getInnerIterator()->count() )
		{
			Output::i()->jsonLd['download']['screenshot'] = array();

			$thumbnails = iterator_to_array( $this->file->screenshots( 1 ) );

			foreach( $this->file->screenshots() as $id => $screenshot )
			{
				Output::i()->jsonLd['download']['screenshot'][]	= array(
					'@type'		=> 'ImageObject',
					'url'		=> (string) $screenshot->url,
					'thumbnail'	=> array(
						'@type'		=> 'ImageObject',
						'url'		=> (string) $thumbnails[ $id ]->url
					)
				);
			}
		}

		if( $versionData['b_changelog'] )
		{
			Output::i()->jsonLd['download']['releaseNotes'] = $versionData['b_changelog'];
		}

		if( $this->file->topic() )
		{
			Output::i()->jsonLd['download']['sameAs'] = (string) $this->file->topic()->url();
		}

		if( $this->file->isPaid() )
		{
			Output::i()->jsonLd['download']['potentialAction'] = array(
				'@type'		=> 'BuyAction',
				'target'	=> (string) $this->file->url( 'buy' ),
			);

			/* Get the price */
			$price = $this->file->price();

			if( $price instanceof Money )
			{
				$price = $price->amountAsString();
			}

			Output::i()->jsonLd['download']['offers'] = array(
				'@type'		=> 'Offer',
				'url'		=> (string) $this->file->url( 'buy' ),
				'price'		=> $price,
				'priceCurrency'	=> Customer::loggedIn()->defaultCurrency(),
				'availability'	=> "https://schema.org/InStock"
			);
		}

		if( $this->file->container()->bitoptions['comments'] )
		{
			Output::i()->jsonLd['download']['interactionStatistic'][] = array(
				'@type'					=> 'InteractionCounter',
				'interactionType'		=> "https://schema.org/CommentAction",
				'userInteractionCount'	=> $this->file->mapped('num_comments')
			);

			Output::i()->jsonLd['download']['commentCount'] = $this->file->mapped('num_comments');
		}
		
		if( $this->file->container()->bitoptions['reviews'] )
		{
			Output::i()->jsonLd['download']['interactionStatistic'][] = array(
				'@type'					=> 'InteractionCounter',
				'interactionType'		=> "https://schema.org/ReviewAction",
				'userInteractionCount'	=> $this->file->mapped('num_reviews')
			);
		}

		/* Set default search option */
		Output::i()->defaultSearchOption = array( 'downloads_file', "downloads_file_pl" );

		/* Display */
		Output::i()->sidebar['sticky'] = TRUE;
		Output::i()->output = Theme::i()->getTemplate( 'view' )->view( $this->file, $commentsAndReviews, $versionData, $previousVersions, $this->file->nextItem(), $this->file->prevItem(), $cfields );
		return null;
	}
	
	/**
	 * Purchase Status
	 *
	 * @return	void
	 */
	public function purchaseStatus() : void
	{
		Session::i()->csrfCheck();
		
		if ( Request::i()->value )
		{
			$method = 'canEnablePurchases';
		}
		else
		{
			$method = 'canDisablePurchases';
		}
		
		if ( !$this->file->$method() )
		{
			Output::i()->error( 'no_module_permission', '2D161/L', 403, '' );
		}
		
		$this->file->purchasable = (bool) Request::i()->value;
		$this->file->save();

		Session::i()->modLog( 'modlog__action_purchasestatus', array( (string) $this->file->url() => FALSE, $this->file->name => FALSE ), $this->file );
		Output::i()->redirect( $this->file->url(), 'saved' );
	}
	
	/**
	 * Buy file
	 *
	 * @return	void
	 */
	protected function buy() : void
	{
		Session::i()->csrfCheck();
		
		/* Can we buy? */
		if ( !$this->file->canBuy() )
		{
			Output::i()->error( 'no_module_permission', '2D161/E', 403, '' );
		}
		
		if ( !$this->file->isPurchasable() )
		{
			Output::i()->error( 'no_module_permission', '2D161/K', 403, '' );
		}

		/* Have we accepted the terms? */
		if ( $downloadTerms = $this->file->container()->message('disclaimer') and in_array( $this->file->container()->disclaimer_location, [ 'purchase', 'both' ] ) and !isset( Request::i()->confirm ) )
		{
			Output::i()->output = Theme::i()->getTemplate( 'view' )->purchaseTerms( $this->file, $downloadTerms, $this->file->url('buy')->csrf()->setQueryString( 'confirm', 1 ) );
			return;
		}
		
		/* Is it associated with a Nexus product? */
		if ( $this->file->nexus )
		{
			$productIds = explode( ',', $this->file->nexus );
			
			if ( count( $productIds ) === 1 )
			{
				try
				{
					Output::i()->redirect( Package::load( array_pop( $productIds ) )->url() );
				}
				catch ( OutOfRangeException $e )
				{
					Output::i()->error( 'node_error', '2D161/F', 404, '' );
				}
			}
			
			$category = $this->file->container();
			try
			{
				foreach ( $category->parents() as $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			}
			catch ( Exception $e ) { }

			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'store.css', 'nexus' ) );
			Output::i()->bodyClasses[] = 'ipsLayout_minimal';
			Output::i()->sidebar['enabled'] = FALSE;
			Output::i()->breadcrumb[] = array( $this->file->url(), $this->file->name );
			Output::i()->title = $this->file->name;
			Output::i()->output = Theme::i()->getTemplate('nexus')->chooseProduct( Item::getItemsWithPermission( array( array( Db::i()->in( 'p_id', $productIds ) ) ), 'p_position' ) );
			return;
		}
		
		/* Create the item */		
		$price = $this->file->price();
		if ( !$price )
		{
			Output::i()->error( 'file_no_price_for_currency', '1D161/H', 403, '' );
		}
		$item = new \IPS\downloads\extensions\nexus\Item\File( $this->file->name, $price );
		$item->id = $this->file->id;
		try
		{
			$item->tax = Settings::i()->idm_nexus_tax ? Tax::load( Settings::i()->idm_nexus_tax ) : NULL;
		}
		catch ( OutOfRangeException $e ) { }
		if ( Settings::i()->idm_nexus_gateways )
		{
			$item->paymentMethodIds = explode( ',', Settings::i()->idm_nexus_gateways );
		}
		$item->renewalTerm = $this->file->renewalTerm();
		$item->payTo = $this->file->author();
		$item->commission = Settings::i()->idm_nexus_percent;
		if ( $fees = json_decode( Settings::i()->idm_nexus_transfee, TRUE ) and isset( $fees[ $price->currency ] ) )
		{
			$item->fee = new Money( $fees[ $price->currency ]['amount'], $price->currency );
		}
				
		/* Generate the invoice */
		$invoice = new Invoice;
		$invoice->currency = ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency();
		$invoice->member = Customer::loggedIn();
		$invoice->addItem( $item );
		$invoice->return_uri = "app=downloads&module=downloads&controller=view&id={$this->file->id}";
		$invoice->save();
		
		/* Take them to it */
		Output::i()->redirect( $invoice->checkoutUrl() );
	}
		
	/**
	 * Download file - Show terms and file selection
	 *
	 * @return	void
	 */
	protected function download() : void
	{
		Output::i()->metaTags['robots'] = 'noindex';

		/* No direct linking check */
		if ( Settings::i()->idm_antileech AND !$this->file->requiresDownloadConfirmation() )
		{
			if ( !isset( Request::i()->csrfKey ) )
			{
				Output::i()->redirect( $this->file->url() );
			}

			Session::i()->csrfCheck();
		}
		
		/* Can we download? */
		try
		{
			$this->file->downloadCheck();
		}
		catch ( DomainException $e )
		{
			Output::i()->error( $e->getMessage(), '1D161/3', 403, '' );
		}
			
		/* What's the URL to confirm? */
		$confirmUrl = $this->file->url()->setQueryString( array( 'do' => 'download', 'confirm' => 1 ) );
		if ( isset( Request::i()->version ) )
		{
			$confirmUrl = $confirmUrl->setQueryString( 'version', Request::i()->version );
		}
		if ( Settings::i()->idm_antileech )
		{
			$confirmUrl = $confirmUrl->csrf();
		}
		
		/* Set navigation */
		$category = $this->file->container();
		try
		{
			foreach ( $category->parents() as $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
		}
		catch ( Exception $e ) { }

		Output::i()->breadcrumb[] = array( $this->file->url(), $this->file->name );
		Output::i()->title = $this->file->name;
		
		/* What files do we have? */
		$files = $this->file->files( Request::i()->version ?? null );
		
		/* Have we accepted the terms? */
		if ( $downloadTerms = $category->message('disclaimer') and in_array( $category->disclaimer_location, [ 'download', 'both' ] ) and !isset( Request::i()->confirm ) )
		{
			Output::i()->output = Theme::i()->getTemplate( 'view' )->download( $this->file, $downloadTerms, null, $confirmUrl, count( $files ) > 1 );
			return;
		}

		/* File Selected? */
		if ( count( $files ) === 1 or ( isset( Request::i()->r ) ) )
		{
			/* Which file? */
			foreach ( $files as $k => $file )
			{
				$data = $files->data();
				if ( isset( Request::i()->r ) and Request::i()->r == $k )
				{
					break;
				}
			}
			
			/* Check it */
			try
			{
				$this->file->downloadCheck( $data );
			}
			catch ( DomainException $e )
			{
				Output::i()->error( $e->getMessage(), '1D161/4', 403, '' );
			}
			
			/* Time Delay */
			if ( Member::loggedIn()->group['idm_wait_period'] AND ( !$this->file->isPaid() OR Member::loggedIn()->group['idm_paid_restrictions'] ) )
			{
				if ( isset( Request::i()->t ) )
				{
					$timerKey = "downloads_delay_" . md5( (string) $file );
					$cookieDelay = 0;
					
					if ( !isset( Request::i()->cookie[ $timerKey ] ) )
					{
						Request::i()->setCookie( $timerKey, time() );
					}
					else
					{
						$cookieDelay = Request::i()->cookie[ $timerKey ];
					}
					
					if ( Request::i()->isAjax() )
					{
						Output::i()->json( array( 'download' => time() + Member::loggedIn()->group['idm_wait_period'], 'currentTime' => time() ) );
					}
					
					if ( $cookieDelay > ( time() - Member::loggedIn()->group['idm_wait_period'] ) )
					{
						Output::i()->output = Theme::i()->getTemplate( 'view' )->download( $this->file, null, $files, $confirmUrl, count( $files ) > 1, $data['record_id'], ( $cookieDelay + Member::loggedIn()->group['idm_wait_period'] ) - time() );
						return;
					}
					else
					{
						Request::i()->setCookie( $timerKey, -1 );
					}
				}
				else
				{
					Output::i()->output = Theme::i()->getTemplate( 'view' )->download( $this->file, null, $files, $confirmUrl, count( $files ) > 1 );
					return;
				}
			}
			
			/* Log */
			$_log	= true;
			if( isset( $_SERVER['HTTP_RANGE'] ) )
			{
				if( !Ranges::isStartOfFile() )
				{
					$_log	= false;
				}
			}
			if( $_log )
			{
				if ( $category->log !== 0 )
				{
					Db::i()->insert( 'downloads_downloads', array(
						'dfid'		=> $this->file->id,
						'dtime'		=> time(),
						'dip'		=> Request::i()->ipAddress(),
						'dmid'		=> (int) Member::loggedIn()->member_id,
						'dsize'		=> $data['record_size'],
						'dua'		=> Session::i()->userAgent->useragent,
						'dbrowsers'	=> Session::i()->userAgent->browser ?: '',
						'dos'		=> ''
					) );
				}

				$this->file->downloads++;
				$this->file->save();
			}
			if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on and ( $this->file->cost or $this->file->nexus ) )
			{
				Customer::loggedIn()->log( 'download', array( 'type' => 'idm', 'id' => $this->file->id, 'name' => $this->file->name ) );
			}

			$this->file->author()->achievementAction( 'downloads', 'DownloadFile', [
				'file' => $this->file,
				'downloader' => Member::loggedIn()
			] );

			/* Data Layer Event */
			if ( Settings::i()->core_datalayer_enabled )
			{
				$properties = $this->file->getDataLayerProperties();
				$properties['file_name'] = $data['record_realname'] ?: $file->originalFilename;

				DataLayer::i()->addEvent( 'file_download', $properties );
				DataLayer::i()->cache();
			}

			/* Download */
			if ( $data['record_type'] === 'link' )
			{
				Output::i()->redirect( $data['record_location'] );
			}
			else
			{
				$file = File::get( 'downloads_Files', $data['record_location'], $data['record_size'] );
				$file->originalFilename = $data['record_realname'] ?: $file->originalFilename;
				$this->_download( $file );
			}
		}
		
		/* Nope - choose one */
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'view' )->download( $this->file, null, $files, $confirmUrl, count( $files ) > 1 );
		}
	}
	
	/**
	 * Actually send the file for download
	 *
	 * @param	File	$file	The file to download
	 * @return	void
	 */
	protected function _download( File $file ) : void
	{
        if ( !$file->filesize() )
        {
            Output::i()->error( 'downloads_no_file', '3D161/G', 404, '' );
        }
		
		/* Log session (but we don't need to create a new session on subsequent requests) */
		$downloadSessionId = Login::generateRandomString();
		if( !isset( $_SERVER['HTTP_RANGE'] ) )
		{
			Db::i()->insert( 'downloads_sessions', array(
				'dsess_id'		=> $downloadSessionId,
				'dsess_mid'		=> (int) Member::loggedIn()->member_id,
				'dsess_ip'		=> Request::i()->ipAddress(),
				'dsess_file'	=> $this->file->id,
				'dsess_start'	=> time()
			) );
		}

		/* If a user aborts the connection the shutdown function is not executed, and we need it to be */
		ignore_user_abort( true );

		register_shutdown_function( function() use( $downloadSessionId ) {
			Db::i()->delete( 'downloads_sessions', array( 'dsess_id=?', $downloadSessionId ) );
		} );
		
		/* If it's an AWS file just redirect to it */
		try
		{
			if ( $signedUrl = $file->generateTemporaryDownloadUrl() )
			{
				Output::i()->redirect( $signedUrl );
			}
		}
		catch( UnexpectedValueException $e )
		{
			Log::log( $e, 'downloads' );
			Output::i()->error( 'generic_error', '3D161/M', 500, '' );
		}

		/* Print the file, honoring ranges */
		try
		{
			if ( Member::loggedIn()->group['idm_throttling'] AND ( !$this->file->isPaid() OR Member::loggedIn()->group['idm_paid_restrictions'] ) )
			{
				$ranges	= new Ranges( $file, (int) Member::loggedIn()->group['idm_throttling'] );
			}
			else
			{
				$ranges = new Ranges( $file );
			}
		}
		catch( RuntimeException $e )
		{
			Log::log( $e, 'file_download' );

			Output::i()->error( 'downloads_no_file', '4D161/J', 403, '' );
		}

		/* If using PHP-FPM, close the request so that __destruct tasks are run after data is flushed to the browser
			@see http://www.php.net/manual/en/function.fastcgi-finish-request.php */
		if( function_exists( 'fastcgi_finish_request' ) )
		{
			fastcgi_finish_request();
		}

		exit;
	}
	
	/**
	 * Restore a previous version
	 *
	 * @return	void
	 */
	protected function restorePreviousVersion() : void
	{
		/* Permission check */
		if ( !$this->file->canEdit() or !Member::loggedIn()->group['idm_bypass_revision'] )
		{
			Output::i()->error( 'no_module_permission', '2D161/5', 403, '' );
		}

		Session::i()->csrfCheck();
		
		/* Load the desired version */
		try
		{
			$version = Db::i()->select( '*', 'downloads_filebackup', array( 'b_id=?', Request::i()->version ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2D161/6', 404, '' );
		}
		
		/* Delete the current versions and any versions in between */
		foreach ( new Iterator( Db::i()->select( 'record_location', 'downloads_files_records', array( 'record_file_id=? AND record_backup=0', $this->file->id ) ), 'downloads_Files' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( Exception $e ) {}
		}
		Db::i()->delete( 'downloads_files_records', array( 'record_file_id=? AND record_backup=0', $this->file->id ) );
		
		/* Delete any versions in between */
		foreach ( Db::i()->select( 'b_records', 'downloads_filebackup', array( 'b_fileid=? AND b_backup>?', $this->file->id, $version['b_backup'] ) ) as $backup )
		{
			foreach ( new Iterator( Db::i()->select( 'record_location', 'downloads_files_records', array( array( 'record_type=?', 'upload' ), Db::i()->in( 'record_id', explode( ',', $backup ) ) ) ), 'downloads_Files' ) as $file )
			{
				try
				{
					$file->delete();
				}
				catch ( Exception $e ) { }
			}
			foreach ( new Iterator( Db::i()->select( 'record_location', 'downloads_files_records', array( array( 'record_type=?', 'ssupload' ), Db::i()->in( 'record_id', explode( ',', $backup ) ) ) ), 'downloads_Files' ) as $file )
			{
				try
				{
					$file->delete();
				}
				catch ( Exception $e ) { }
			}
			
			Db::i()->delete( 'downloads_files_records', Db::i()->in( 'record_id', explode( ',', $backup ) ) );
		}
		Db::i()->delete( 'downloads_filebackup', array( 'b_fileid=? AND b_backup>=?', $this->file->id, $version['b_backup'] ) );
		
		/* Restore the records */
		Db::i()->update( 'downloads_files_records', array( 'record_backup' => 0 ), array( 'record_file_id=?', $this->file->id ) );
		
		/* Update the file information */
		$this->file->name = $version['b_filetitle'];
		$this->file->desc = $version['b_filedesc'];
		$this->file->version = $version['b_version'];
		$this->file->changelog = $version['b_changelog'];
		$this->file->save();

		/* Moderator log */
		Session::i()->modLog( 'modlog__action_restorebackup', array( (string) $this->file->url() => FALSE, $this->file->name => FALSE ), $this->file );

		/* Redirect */
		Output::i()->redirect( $this->file->url() );
	}
	
	/**
	 * Toggle Previous Version Visibility
	 *
	 * @return	void
	 */
	protected function previousVersionVisibility() : void
	{
		/* Permission check */
		if ( !$this->file->canEdit() or !Member::loggedIn()->group['idm_bypass_revision'] )
		{
			Output::i()->error( 'no_module_permission', '2D161/8', 403, '' );
		}

		Session::i()->csrfCheck();
		
		/* Load the desired version */
		try
		{
			$version = Db::i()->select( '*', 'downloads_filebackup', array( 'b_id=?', Request::i()->version ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2D161/7', 404, '' );
		}
		
		/* Change visibility */
		Db::i()->update( 'downloads_filebackup', array( 'b_hidden' => !$version['b_hidden'] ), array( 'b_id=?', $version['b_id'] ) );

		/* Moderator log */
		Session::i()->modLog( 'modlog__action_visibilitybackup', array( (string) $this->file->url() => FALSE, $this->file->name => FALSE ), $this->file );
		
		/* Redirect */
		Output::i()->redirect( $this->file->url()->setQueryString( 'changelog', $version['b_id'] ) );
	}
	
	/**
	 * Delete Previous Version
	 *
	 * @return	void
	 */
	protected function deletePreviousVersion() : void
	{
		/* Permission check */
		if ( !$this->file->canEdit() or !Member::loggedIn()->group['idm_bypass_revision'] )
		{
			Output::i()->error( 'no_module_permission', '2D161/9', 403, '' );
		}

		Session::i()->csrfCheck();
		
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		/* Load the desired version */
		try
		{
			$version = Db::i()->select( '*', 'downloads_filebackup', array( 'b_id=?', Request::i()->version ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2D161/A', 404, '' );
		}

		/* Base file iterator */
		$fileIterator = function( $recordType, $storageExtension ) use( $version )
		{
			return new Iterator(
				Db::i()->select(
					'record_location', 'downloads_files_records', array(
						array( 'record_type=?', $recordType ),
						Db::i()->in( 'record_id', explode( ',', $version['b_records'] ) ),
						array( 'record_location NOT IN (?)', Db::i()->select(
							'record_location', 'downloads_files_records', array( 'record_type=?', $recordType ), NULL,
							NULL, 'record_location', 'COUNT(*) > 1'
						) )
					)
				), $storageExtension
			);
		};

		/* Delete */
		foreach ( $fileIterator( 'upload', 'downloads_Files' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( Exception $e ) { }
		}

		foreach ( $fileIterator( 'ssupload', 'downloads_Screenshots' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( Exception $e ) { }
		}

		Db::i()->delete( 'downloads_files_records', Db::i()->in( 'record_id', explode( ',', $version['b_records'] ) ) );
		Db::i()->delete( 'downloads_filebackup', array( 'b_id=?', $version['b_id'] ) );

		/* Moderator log */
		Session::i()->modLog( 'modlog__action_deletebackup', array( (string) $this->file->url() => FALSE, $this->file->name => FALSE ), $this->file );

		/* Redirect */
		Output::i()->redirect( $this->file->url()->setQueryString( 'changelog', $version['b_id'] ) );
	}
	
	/**
	 * View download log
	 *
	 * @return	void
	 */
	protected function log() : void
	{
		/* Permission check */
		if ( !$this->file->canViewDownloaders() )
		{
			Output::i()->error( 'no_module_permission', '2D161/B', 403, '' );
		}
		
		$table = new TableDb( 'downloads_downloads', $this->file->url()->setQueryString( 'do', 'log' ), array( 'dfid=?', $this->file->id ) );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'view' ), 'logTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'view' ), 'logRows' );
		$table->sortBy = 'dtime';
		$table->limit = 10;

		Output::i()->output = Theme::i()->getTemplate( 'view' )->log( $this->file, (string) $table );
	}
	
	/**
	 * Upload a new version
	 *
	 * @return	void
	 */
	protected function newVersion() : void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_submit.js', 'downloads', 'front' ) );

		/* Permission check */
		if ( !$this->file->canEdit() OR !$this->file->container()->can('add') OR $this->file->hasPendingVersion() )
		{
			Output::i()->error( 'no_module_permission', '2D161/C', 403, '' );
		}
		
		$category = $this->file->container();
		Output::i()->sidebar['enabled'] = FALSE;

		/* Club */
		try
		{
			if ( $club = $category->club() )
			{
				FrontNavigation::$clubTabActive = TRUE;
				Output::i()->breadcrumb = array();
				Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
				Output::i()->breadcrumb[] = array( $club->url(), $club->name );
				Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			}
		}
		catch ( OutOfRangeException $e ) { }
		
		/* Require approval */
		$requireApproval = FALSE;
		$member = Member::loggedIn();
		if(  !$member->group['g_avoid_q'] and $category->bitoptions['moderation'] and $category->bitoptions['moderation_edits'] and !$this->file->canUnhide() )
		{
			$requireApproval = TRUE;
		}

		$postingInformation = ( $requireApproval ) ? Theme::i()->getTemplate( 'forms', 'core' )->postingInformation( NULL, TRUE, TRUE ) : NULL;
		
		/* Build form */
		$form = new Form;

		if ( $category->versioning !== 0 and Member::loggedIn()->group['idm_bypass_revision'] )
		{
			$form->add( new YesNo( 'file_save_revision', TRUE ) );
		}

		if( $category->version_numbers )
		{
			$form->add( new Text( 'file_version', $this->file->version, $category->version_numbers == 2, array( 'maxLength' => 32 ) ) );
		}
		$form->add( new Editor( 'file_changelog', NULL, $category->require_changelog, array( 'app' => 'downloads', 'key' => 'Downloads', 'autoSaveKey' => "downloads-{$this->file->id}-changelog", 'allowAttachments' => FALSE ) ) );

		$defaultFiles = iterator_to_array( $this->file->files( NULL, FALSE ) );
		if( !$category->multiple_files )
		{
			$defaultFiles = array_pop( $defaultFiles );
		}

		$fileField = new Upload( 'files', $defaultFiles, ( !Member::loggedIn()->group['idm_linked_files'] and !Member::loggedIn()->group['idm_import_files'] ), array( 'storageExtension' => 'downloads_Files', 'allowedFileTypes' => $category->types, 'maxFileSize' => $category->maxfile ? ( $category->maxfile / 1024 ) : NULL, 'multiple' => $category->multiple_files, 'retainDeleted' => TRUE, 'canBeModerated' => TRUE ) );
		$fileField->label = Member::loggedIn()->language()->addToStack('downloads_file');
		$form->add( $fileField );

		$linkedFiles = iterator_to_array( Db::i()->select( 'record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0 AND record_hidden=0', $this->file->id, 'link' ) ) );

		if ( Member::loggedIn()->group['idm_linked_files'] )
		{
			$form->add( new Stack( 'url_files', $linkedFiles, FALSE, array( 'stackFieldType' => 'Url' ), array( 'IPS\downloads\File', 'blacklistCheck' ) ) );
		}
		else if ( count( $linkedFiles ) > 0 )
		{
			$form->addMessage( 'url_files_no_perm' );
		}

		if ( Member::loggedIn()->group['idm_import_files'] )
		{
			$form->add( new Stack( 'import_files', array(), FALSE, array( 'placeholder' => \IPS\ROOT_PATH ), function( $val )
			{
				if( is_array( $val ) )
				{
					foreach ( $val as $file )
					{
						if ( !is_file( $file ) )
						{
							throw new DomainException( Member::loggedIn()->language()->addToStack('err_import_files', FALSE, array( 'sprintf' => array( $file ) ) ) );
						}
					}
				}
			} ) );
		}

		if ( $category->bitoptions['allowss'] )
		{
			$screenshots = iterator_to_array( $this->file->screenshots( 2, FALSE ) );

			if( $this->file->_primary_screenshot and isset( $screenshots[ $this->file->_primary_screenshot ] ) )
			{
				$screenshots[ $this->file->_primary_screenshot ] = array( 'fileurl' => $screenshots[ $this->file->_primary_screenshot ], 'default' => true );
			}

			$image = TRUE;
			if ( $category->maxdims and $category->maxdims != '0x0' )
			{
				$maxDims = explode( 'x', $category->maxdims );
				$image = array( 'maxWidth' => $maxDims[0], 'maxHeight' => $maxDims[1] );
			}

			$form->add( new Upload( 'screenshots', $screenshots, ( $category->bitoptions['reqss'] and !Member::loggedIn()->group['idm_linked_files'] ), array(
				'storageExtension'	=> 'downloads_Screenshots',
				'image'				=> $image,
				'maxFileSize'		=> $category->maxss ? ( $category->maxss / 1024 ) : NULL,
				'multiple'			=> TRUE,
				'retainDeleted'		=> TRUE,
				'template'			=> "downloads.submit.screenshot",
				'canBeModerated'	=> TRUE,
			) ) );

			if ( Member::loggedIn()->group['idm_linked_files'] )
			{
				$form->add( new LinkedScreenshots( 'url_screenshots', array(
					'values'	=> iterator_to_array( Db::i()->select( 'record_id, record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0 AND record_hidden=0', $this->file->id, 'sslink' ) )->setKeyField('record_id')->setValueField('record_location') ),
					'default'	=> $this->file->_primary_screenshot
				), FALSE, array( 'IPS\downloads\File', 'blacklistCheck' ) ) );
			}
		}

		/* Check for any extra form elements */
		$this->file->newVersionFormElements( $form );

		/* Output */
		Output::i()->title = $this->file->name;

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Check */
			if ( empty( $values['files'] ) and empty( $values['url_files'] ) and empty( $values['import_files'] ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack('err_no_files');
				Output::i()->output = Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), FALSE, 0, $postingInformation, $category->versioning !== 0 );
				Output::i()->output = (string) $form;
				return;
			}
			elseif ( !$category->multiple_files AND is_array( $values['files'] ) AND ( count( $values['files'] ?? [] ) + count( $values['url_files'] ?? [] ) + count( $values['import_files'] ?? [] ) > 1 ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack('err_too_many_files');
				Output::i()->output = Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), FALSE, 0, $postingInformation, $category->versioning !== 0 );
				return;
			}
			if ( $category->bitoptions['reqss'] and empty( $values['screenshots'] ) and empty( $values['url_screenshots'] ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack('err_no_screenshots');
				Output::i()->output = Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), FALSE, 0, $postingInformation, $category->versioning !== 0 );
				Output::i()->output = (string) $form;
				return;
			}
			
			/* Filters */
			$imageUploads = isset( $values['files'] ) ? ( is_array( $values['files'] ) ? $values['files'] : [ $values['files'] ] )  : [];
			if ( isset( $values['screenshots'] ) )
			{
				$imageUploads = array_merge( $imageUploads, $values['screenshots'] );
			}
			if ( $this->file->shouldTriggerProfanityFilters( FALSE, FALSE, $values['file_changelog'], $values['file_version'], 'downloads_Downloads', ["downloads-{$this->file->id}-changelog"], $imageUploads ) )
			{
				$requireApproval = TRUE;
			}
			
			/* Versioning */
			if( $requireApproval )
			{
				$fileObj = new PendingVersion;
				$fileObj->file_id = $this->file->id;
				$fileObj->member_id = Member::loggedIn()->member_id;
				$fileObj->form_values = $values;
			}
			else
			{
				$fileObj = $this->file;
				$fileObj->published = time();
			}

			$existingRecords = array();
			$existingScreenshots = array();
			$existingThumbnails = array();
			$existingLinks = array();
			$existingScreenshotLinks = array();
			if ( $category->versioning !== 0 and ( !Member::loggedIn()->group['idm_bypass_revision'] or $values['file_save_revision'] ) )
			{
				$fileObj->saveVersion();
			}
			else
			{
				$existingRecords = array_unique( iterator_to_array( Db::i()->select( 'record_id, record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=?', $this->file->id, 'upload', 0 ) )->setKeyField('record_id')->setValueField('record_location') ) );
				$existingScreenshots = array_unique( iterator_to_array( Db::i()->select( 'record_id, record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=?', $this->file->id, 'ssupload', 0 ) )->setKeyField('record_id')->setValueField('record_location') ) );
				$existingThumbnails = array_unique( iterator_to_array( Db::i()->select( 'record_id, record_thumb', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=?', $this->file->id, 'ssupload', 0 ) )->setKeyField('record_id')->setValueField('record_thumb') ) );
				$existingLinks = array_unique( iterator_to_array( Db::i()->select( 'record_id, record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=?', $this->file->id, 'link', 0 ) )->setKeyField('record_id')->setValueField('record_location') ) );
                $existingScreenshotLinks = array_unique( iterator_to_array( Db::i()->select( 'record_id, record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=?', $this->file->id, 'sslink', 0 ) )->setKeyField('record_id')->setValueField('record_location') ) );
			}

			/* Files may not be an array since we have an option to limit to a single upload */
			if( !is_array( $values['files'] ) )
			{
				$values['files'] = [ $values['files'] ];
			}

			/* Insert the new records */
			foreach ( $values['files'] as $file )
			{
				$key = array_search( (string) $file, $existingRecords );
				
				if ( $key !== FALSE )
				{
					unset( $existingRecords[ $key ] );
				}
				else
				{
					Db::i()->insert( 'downloads_files_records', array(
						'record_file_id'	=> $this->file->id,
						'record_type'		=> 'upload',
						'record_location'	=> (string) $file,
						'record_realname'	=> $file->originalFilename,
						'record_size'		=> $file->filesize(),
						'record_time'		=> time(),
						'record_hidden'		=> $requireApproval
					) );
				}
			}

			if ( isset( $values['import_files'] ) )
			{
				foreach ( $values['import_files'] as $path )
				{
					$file = File::create( 'downloads_Files', mb_substr( $path, mb_strrpos( $path, DIRECTORY_SEPARATOR ) + 1 ), file_get_contents( $path ) );
					
					$key = array_search( (string) $file, $existingRecords );
					if ( $key !== FALSE )
					{
						unset( $existingRecords[ $key ] );
					}
					else
					{
						Db::i()->insert( 'downloads_files_records', array(
							'record_file_id'	=> $this->file->id,
							'record_type'		=> 'upload',
							'record_location'	=> (string) $file,
							'record_realname'	=> $file->originalFilename,
							'record_size'		=> $file->filesize(),
							'record_time'		=> time(),
							'record_hidden'		=> $requireApproval
						) );
					}
				}
			}

			if ( isset( $values['url_files'] ) )
			{
				foreach ( $values['url_files'] as $url )
				{
					$key = array_search( $url, $existingLinks );
					if ( $key !== FALSE )
					{
						unset( $existingLinks[ $key ] );
					}
					else
					{
						Db::i()->insert( 'downloads_files_records', array(
							'record_file_id'	=> $this->file->id,
							'record_type'		=> 'link',
							'record_location'	=> (string) $url,
							'record_realname'	=> NULL,
							'record_size'		=> 0,
							'record_time'		=> time(),
							'record_hidden'		=> $requireApproval
						) );
					}
				}
			}

			if ( isset( $values['screenshots'] ) )
			{
				foreach ( $values['screenshots'] as $_key => $file )
				{
					/* If this was the primary screenshot, convert back */
					if( is_array( $file ) )
					{
						$file = $file['fileurl'];
					}

					$key = array_search( (string) $file, $existingScreenshots );
					if ( $key !== FALSE )
					{
						Db::i()->update( 'downloads_files_records', array(
							'record_default'		=> ( Request::i()->screenshots_primary_screenshot AND Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0
						), array( 'record_id=?', $_key ) );

						unset( $existingScreenshots[ $key ] );

						if( isset( $existingThumbnails[ $key ] ) )
						{
							unset( $existingThumbnails[ $key ] );
						}
					}
					else
					{
						$noWatermark = NULL;
						$watermarked = FALSE;
						if ( Settings::i()->idm_watermarkpath )
						{
							try
							{
								$noWatermark = (string) $file;
								$watermark = Image::create( File::get( 'core_Theme', Settings::i()->idm_watermarkpath )->contents() );
								$image = Image::create( $file->contents() );
								$image->watermark( $watermark );
								$file = File::create( 'downloads_Screenshots', $file->originalFilename, $image );
								$watermarked = TRUE;
							}
							catch ( Exception $e ) { }
						}
						
						/**
						 * We only need to generate a new thumbnail if we are using watermarking.
						 * If we are not, then we can simply use the previous thumbnail, if this existed previously, rather than generating a new one every time we upload a new version, which is unnecessary extra processing as well as disk usage.
						 * If we are, then it is impossible to know if the watermark has since changed, so we need to go ahead and do it anyway.
						 */
						$existing = NULL;
						if ( $watermarked !== TRUE )
						{
							try
							{
								$existing = Db::i()->select( '*', 'downloads_files_records', array( "record_location=? AND record_file_id=?", (string) $file, $this->file->id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
							}
							catch( UnderflowException $e ) {}
						}
						
						Db::i()->insert( 'downloads_files_records', array(
							'record_file_id'		=> $this->file->id,
							'record_type'			=> 'ssupload',
							'record_location'		=> (string) $file,
							'record_thumb'			=> ( $watermarked OR !$existing ) ? (string) $file->thumbnail( 'downloads_Screenshots' ) : $existing['record_thumb'],
							'record_realname'		=> $file->originalFilename,
							'record_size'			=> $file->filesize(),
							'record_time'			=> time(),
							'record_no_watermark'	=> $noWatermark,
							'record_default'		=> ( Request::i()->screenshots_primary_screenshot AND Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0,
							'record_hidden'			=> $requireApproval
						) );
					}
				}
			}

			if ( isset( $values['url_screenshots'] ) )
			{
				foreach ( $values['url_screenshots'] as $_key => $url )
				{
					$key = array_search( (string) $file, $existingScreenshotLinks );
					if ( $key !== FALSE )
					{
						Db::i()->update( 'downloads_files_records', array(
							'record_default'		=> ( Request::i()->screenshots_primary_screenshot AND Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0
						), array( 'record_id=?', $_key ) );
						unset( $existingScreenshotLinks[ $key ] );
					}
					else
					{
						Db::i()->insert( 'downloads_files_records', array(
							'record_file_id'	=> $this->file->id,
							'record_type'		=> 'sslink',
							'record_location'	=> (string) $url,
							'record_realname'	=> NULL,
							'record_size'		=> 0,
							'record_time'		=> time(),
							'record_default'	=> ( Request::i()->screenshots_primary_screenshot AND Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0,
							'record_hidden'		=> $requireApproval
						) );
					}
				}
			}

			$deletions = array( 'records' => array(), 'links' => array(), 'thumbs' => array() );
			
			/* Delete any we're not using anymore */
			foreach ( $existingRecords as $recordId => $url )
			{
				$deletions['records'][ $recordId ] = array( 'handler' => 'downloads_Files', 'url' => $url );
			}
			foreach ( $existingScreenshots as $recordId => $url )
			{
				$deletions['records'][ $recordId ] = array( 'handler' => 'downloads_Screenshots', 'url' => $url );
			}
			foreach ( ( $existingLinks + $existingScreenshotLinks ) as $id => $url )
			{
				$deletions['links'][ $id ] = $id;
			}

			$deletions['thumbs'] = $existingThumbnails;

            if( $requireApproval )
			{
				$fileObj->record_deletions = $deletions;
			}
            else
			{
				array_walk( $deletions['records'], function( $arr, $key  ) use( $fileObj ) {
					$fileObj->deleteRecords( $key, $arr['url'], $arr['handler'] );
				});
				$fileObj->deleteRecords( $deletions['links'] );

				foreach( $deletions['thumbs'] as $url )
				{
					try
					{
						File::get( 'downloads_Screenshots', $url )->delete();
					}
					catch( Exception $e ){}
				}

				/* Set the new details */
				$fileObj->version = ( isset( $values['file_version'] ) ) ? $values['file_version'] : NULL;
				$fileObj->changelog = $values['file_changelog'];
			}
			
			/* These are specific to unapproved updates */
			if ( !$requireApproval )
			{
				$fileObj->size = floatval( Db::i()->select( 'SUM(record_size)', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $this->file->id, 'upload' ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first() );

				/* Work out the new primary screenshot */
				try
				{
					$this->file->primary_screenshot = Db::i()->select( 'record_id', 'downloads_files_records', array( 'record_file_id=? AND ( record_type=? OR record_type=? ) AND record_backup=0 AND record_hidden=0', $this->file->id, 'ssupload', 'sslink' ), 'record_default DESC, record_id ASC', NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
				}
				catch ( UnderflowException $e ) { }
			}
			
			/* Save */
			$fileObj->updated = time();
			$fileObj->save();

			if( $requireApproval )
			{
				$this->file->save();
				$fileObj->sendUnapprovedNotification();
			}
			else
			{
				/* Send notifications */
				if ( $this->file->open )
				{
					$this->file->sendUpdateNotifications();
				}
				else
				{
					$this->file->sendUnapprovedNotification();
				}

				$this->file->processAfterNewVersion( $values );
			}

			/* Boink */
			Output::i()->redirect( $this->file->url() );
		}
		
		/* Set navigation */
		try
		{
			foreach ( $category->parents() as $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
		}
		catch ( Exception $e ) { }
		Output::i()->breadcrumb[] = array( $this->file->url(), $this->file->name );

		Output::i()->output = Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $this->file->container(), $this->file->container()->message('subterms'), FALSE, 0, $postingInformation, $category->versioning !== 0 );
	}
	
	/**
	 * Change Author
	 *
	 * @return	void
	 */
	public function changeAuthor() : void
	{
		/* Permission check */
		if ( !$this->file->canChangeAuthor() )
		{
			Output::i()->error( 'no_module_permission', '2D161/D', 403, '' );
		}
		
		/* Build form */
		$form = new Form;
		$form->add( new FormMember( 'author', NULL, TRUE ) );
		$form->class .= 'ipsForm--vertical ipsForm--change-file-author';

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$this->file->changeAuthor( $values['author'] );			
			Output::i()->redirect( $this->file->url() );
		}
		
		/* Display form */
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}

	/**
	 * Subscribe
	 *
	 * @return void
	 */
	function toggleSubscription() : void
	{
		Session::i()->csrfCheck();

		if( $this->file->subscribed() )
		{
			Db::i()->delete( 'downloads_files_notify', array( 'notify_member_id=? and notify_file_id=?', Member::loggedIn()->member_id, $this->file->id ) );
			$subscribed = FALSE;
		}
		else
		{
			Db::i()->replace( 'downloads_files_notify', array( 'notify_member_id' => Member::loggedIn()->member_id, 'notify_file_id' => $this->file->id ) );
			$subscribed = TRUE;
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( $subscribed ? 'subscribed' : 'unsubscribed' );
		}
		else
		{
			Output::i()->redirect( $this->file->url(), $subscribed ? 'file_subscribed' : 'file_unsubscribed' );
		}
	}

	/**
	 * Subscribe Hover
	 *
	 * @return void
	 */
	function subscribeBlurb() : void
	{
		$notificationConfiguration = Member::loggedIn()->notificationsConfiguration();
		$notificationConfiguration = $notificationConfiguration['new_file_version'] ?? array();

		$options = NULL;
		if( count( $notificationConfiguration ) )
		{
			$methods = [];
			foreach( $notificationConfiguration as $option )
			{
				$methods[] = Member::loggedIn()->language()->addToStack( 'member_notifications_' . $option );
			}

			$options = Member::loggedIn()->language()->formatList( $methods );
		}

		Output::i()->output = Theme::i()->getTemplate( 'view' )->notifyBlurb( $this->file, $options );
	}
}
