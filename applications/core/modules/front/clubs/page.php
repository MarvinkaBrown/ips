<?php
/**
 * @brief		page
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		02 Aug 2019
 */

namespace IPS\core\modules\front\clubs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Club\Page as ClubPage;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * page
 */
class page extends Controller
{
	/**
	 * @brief	Page
	 */
	protected ?ClubPage $page = null;

	/**
	 * @brief These properties are used to specify datalayer context properties.
	 *
	 */
	public static array $dataLayerContext = array(
		'community_area' =>  [ 'value' => 'clubs', 'odkUpdate' => 'true']
	);
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		try
		{
			$this->page = ClubPage::loadAndCheckPerms( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S410/4', 404, '' );
		}
		
		Output::i()->sidebar['contextual'] = '';

		/* Club info in sidebar */
		if ( Settings::i()->clubs_header == 'sidebar' )
		{
			Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core' )->header( $this->page->club, NULL, 'sidebar', $this->page );
		}

		if( ( GeoLocation::enabled() and Settings::i()->clubs_locations AND $location = $this->page->club->location() ) )
		{
			Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core' )->clubLocationBox( $this->page->club, $location );
		}
		
		if( $this->page->club->type != Club::TYPE_PUBLIC AND $this->page->club->canViewMembers() )
		{
			Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core' )->clubMemberBox( $this->page->club );
		}
		
		Output::i()->breadcrumb[] = array( $this->page->club->url(), $this->page->club->name );
		Output::i()->breadcrumb[] = array( NULL, $this->page->title );

		if( !$this->page->meta_index )
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}

		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( !$this->page->club->rulesAcknowledged() )
		{
			Url::internal( $this->page->club->url()->setQueryString( 'do', 'rules' ) );
		}
		
		Output::i()->title = $this->page->title;
		Output::i()->output = Theme::i()->getTemplate( 'clubs' )->viewPage( $this->page );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		if( !$this->page->canEdit() )
		{
			Output::i()->error( 'node_noperm_edit', '2S410/1', 403, '' );
		}

		/* Init form */
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--edit-club-page';
		ClubPage::form( $form, $this->page->club, $this->page );
		
		/* Form Submission */
		if ( $values = $form->values() )
		{
			$this->page->formatFormValues( $values );
			$this->page->save();
			
			File::claimAttachments( "club-page-{$this->page->id}", $this->page->id );
			
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( 'OK' );
			}
			else
			{
				Output::i()->redirect( $this->page->url() );
			}
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack( "edit" );
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		Session::i()->csrfCheck();

		if( !$this->page->canDelete() )
		{
			Output::i()->error( 'node_noperm_delete', '2S410/2', 403, '' );
		}
		
		$this->page->delete();
		
		Output::i()->redirect( $this->page->club->url(), 'deleted' );
	}
}