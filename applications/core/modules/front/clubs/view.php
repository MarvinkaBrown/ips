<?php
/**
 * @brief		Clubs View
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Feb 2017
 */

namespace IPS\core\modules\front\clubs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content\Search\Query;
use IPS\core\extensions\nexus\Item\ClubMembership;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Front;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\CoverPhoto\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Image;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Club\Page;
use IPS\nexus\Customer;
use IPS\Notification;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use IPS\Widget;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function mb_stristr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Clubs View
 */
class view extends Controller
{
	/**
	 * @brief	The club being viewed
	 */
	protected ?Club $club = null;
	
	/**
	 * @brief	The logged in user's status
	 */
	protected ?string $memberStatus = '';

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
		if ( Request::i()->do != 'embed' )
		{
			/* Permission check */
			if ( !Settings::i()->clubs )
			{
				Output::i()->error( 'no_module_permission', '2C350/P', 403, '' );
			}
			
			/* Load the club */
			try
			{
				$this->club = Club::load( Request::i()->id );
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2C350/1', 404, '' );
			}
			$this->memberStatus = $this->club->memberStatus( Member::loggedIn() );
			
			/* If we can't even know it exists, show an error */
			if ( !$this->club->canView() )
			{
				Output::i()->error( Member::loggedIn()->member_id ? 'no_module_permission' : 'no_module_permission_guest', '2C350/2', 403, '' );
			}
							
			/* Sort out the breadcrumb */
			Output::i()->breadcrumb = array(
				array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') )
			);
			
			/* Add a "Search in this club" contextual search option and set to default*/
			Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item_club' ) ] = array( 'type' => '', 'club' => "c{$this->club->id}" );
			Output::i()->defaultSearchOption = array( '', 'search_contextual_item_club' );

			/* CSS */
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/clubs.css', 'core', 'front' ) );
	
			/* JS */
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_clubs.js', 'core', 'front' ) );
			
			/* Location for online list */
			if ( $this->club->type !== Club::TYPE_PRIVATE )
			{
				Session::i()->setLocation( $this->club->url(), array(), 'loc_clubs_club', array( $this->club->name => FALSE ) );
			}
			else
			{
				Session::i()->setLocation( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), array(), 'loc_clubs_directory' );
			}

			Output::i()->sidebar['contextual'] = '';

			/* Club info in sidebar */
			if ( Settings::i()->clubs_header == 'sidebar' )
			{
				Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core' )->header( $this->club, NULL, 'sidebar' );
			}
			$club = $this->club;

			if( ( GeoLocation::enabled() and Settings::i()->clubs_locations AND $location = $this->club->location() ) )
			{
				Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core' )->clubLocationBox( $this->club, $location );
			}
			if( $this->club->type != $club::TYPE_PUBLIC AND $this->club->canViewMembers() )
			{
				Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core' )->clubMemberBox( $this->club );
			}
		}
		
		if ( !in_array( Request::i()->do, array( 'rules', 'leave', 'embed' ) ) AND $this->club->memberStatus( Member::loggedIn() ) !== NULL AND !$this->club->rulesAcknowledged()  )
		{
			Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'rules' )->addRef( Request::i()->url() ) );
		}
		
		/* Pass upwards */
		parent::execute();
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$firstTab = $this->club->firstTab();

		$key = key($firstTab);

		if ( !in_array( $key, array( 'club_home', 'club_members') ) )
		{
			Output::i()->redirect( (string) $firstTab[ $key ]['href'] );
		}

		if( $key == 'club_members' )
		{
			$this->members();
		}
		else
		{
			$this->overview();
		}
	}

	/**
	 * Overview page
	 *
	 * @return void
	 */
	protected function overview() : void
	{
		/* Get the activity stream */
		$activity = Query::init()->filterByClub( $this->club )->setOrder( Query::ORDER_NEWEST_CREATED )->search();

		/* Get who joined the club in between those results */
		if ( $this->club->type != Club::TYPE_PUBLIC )
		{
			$lastTime = NULL;
			foreach ( $activity as $key => $result )
			{
				if ( $result !== NULL )
				{
					$lastTime = $result->createdDate->getTimestamp();
				}
				else
				{
					unset( $activity[ $key ] );
				}
			}
			$joins = array();

			if( $this->club->canViewMembers() )
			{
				$joinWhere = array( array( 'club_id=?', $this->club->id ), array( Db::i()->in( 'status', array( Club::STATUS_MEMBER, Club::STATUS_MODERATOR, Club::STATUS_LEADER ) ) ) );
				if ( $lastTime )
				{
					$joinWhere[] = array( 'core_clubs_memberships.joined>?', $lastTime );
				}
				$select = 'core_clubs_memberships.joined' . ',' . implode( ',', array_map( function( $column ) {
						return 'core_members.' . $column;
					}, Member::columnsForPhoto() ) );
				foreach ( Db::i()->select( $select, 'core_clubs_memberships', $joinWhere, 'joined DESC', array( 0, 50 ), NULL, NULL, Db::SELECT_MULTIDIMENSIONAL_JOINS )->join( 'core_members', 'core_members.member_id=core_clubs_memberships.member_id' ) as $join )
				{
					$joins[] = new \IPS\Content\Search\Result\Custom(
						DateTime::ts( $join['core_clubs_memberships']['joined'] ),
						Member::loggedIn()->language()->addToStack( 'clubs_activity_joined', FALSE, array( 'htmlsprintf' => Theme::i()->getTemplate( 'global', 'core', 'front' )->userLinkFromData( $join['core_members']['member_id'], $join['core_members']['name'], $join['core_members']['members_seo_name'] ) ) ),
						Theme::i()->getTemplate( 'global', 'core', 'front' )->userPhotoFromData( $join['core_members']['member_id'], $join['core_members']['name'], $join['core_members']['members_seo_name'], Member::photoUrl( $join['core_members'] ), 'tiny' )
					);
				}
			}


			/* Merge them in */
			if ( !empty( $joins ) )
			{
				$activity = array_filter( array_merge( iterator_to_array( $activity ), $joins ) );
				uasort( $activity, function( $a, $b )
				{
					if ( $a->createdDate->getTimestamp() == $b->createdDate->getTimestamp() )
					{
						return 0;
					}
					elseif( $a->createdDate->getTimestamp() < $b->createdDate->getTimestamp() )
					{
						return 1;
					}
					else
					{
						return -1;
					}
				} );
			}
		}

		/* Display */
		Output::i()->linkTags['canonical'] = (string) $this->club->url();
		Output::i()->title = $this->club->name;
		Output::i()->output = Theme::i()->getTemplate('clubs')->view( $this->club, $activity, $this->club->fieldValues() );

		if( $firstTab = $this->club->firstTab() AND !isset( $firstTab['club_home'] ) )
		{
			Output::i()->breadcrumb[] = array( $this->club->url(), $this->club->name );
			Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'club_home' ) );
		}
		else
		{
			Output::i()->breadcrumb[] = array( NULL, $this->club->name );
		}

		/* Set some meta tags for the club */
		$this->_setDefaultMetaTags();
	}
	
	/**
	 * Map Callback
	 *
	 * @return	void
	 */
	protected function mapPopup() : void
	{
		Output::i()->output = Theme::i()->getTemplate('clubs')->mapPopup( $this->club );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		if ( !( $this->club->owner and $this->club->owner->member_id == Member::loggedIn()->member_id ) and !Member::loggedIn()->modPermission('can_access_all_clubs') )
		{
			Output::i()->error( 'no_module_permission', '2C350/A', 403, '' );
		}
		
		$form = $this->club->form();

		if( $values = $form->values() )
		{
			$this->club->skipCloneDuplication = TRUE;
			$old = clone $this->club;

			$this->club->processForm( $values, FALSE, FALSE, NULL );

			$changes = $this->club::renewalChanges( $old, $this->club );

			if ( !empty( $changes ) )
			{
				Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'global' )->decision( 'product_change_blurb', array(
					'product_change_blurb_existing'	=> Url::internal( "app=core&module=clubs&controller=view&do=updateExisting&id={$this->club->id}" )->setQueryString( 'changes', json_encode( $changes ) )->csrf(),
					'product_change_blurb_new'		=> $this->club->url(),
				) );

				return;
			}
			else
			{
				Output::i()->redirect( $this->club->url() );
			}
		}

		Output::i()->title = $this->club->name;
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		else
		{
			Output::i()->output = $form;
		}

	}
	
	/**
	 * Edit Photo
	 *
	 * @return	void
	 */
	protected function editPhoto() : void
	{
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/R', 403, '' );
		}
		Output::i()->title = $this->club->name;
		
		$form = new Form( 'club_profile_photo', 'continue' );
		$form->ajaxOutput = TRUE;
		$form->add( new Upload( 'club_profile_photo', $this->club->profile_photo_uncropped ? File::get( 'core_Clubs', $this->club->profile_photo_uncropped ) : NULL, FALSE, array( 'storageExtension' => 'core_Clubs', 'allowStockPhotos' => TRUE, 'image' => array( 'maxWidth' => 200, 'maxHeight' => 200 ) ) ) );
		if ( $values = $form->values() )
		{
			if ( !$values['club_profile_photo'] or $this->club->profile_photo_uncropped != (string) $values['club_profile_photo'] )
			{
				foreach ( array( 'profile_photo', 'profile_photo_uncropped' ) as $k )
				{
					if( $this->club->$k )
					{
						try
						{
							File::get( 'core_Clubs', $this->club->$k )->delete();
						}
						catch ( Exception $e ) { }
					}

					$this->club->$k = NULL;
				}
			}
			
			if ( $values['club_profile_photo'] )
			{
				$this->club->profile_photo_uncropped = (string) $values['club_profile_photo'];
				$this->club->save();
				
				if ( Request::i()->isAjax() )
				{					
					$this->cropPhoto();
					return;
				}
				else
				{
					Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'cropPhoto' ) );
				}
			}
			else
			{
				$this->club->save();
				Output::i()->redirect( $this->club->url() );
			}
		}
		
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Crop Photo
	 *
	 * @return	void
	 */
	protected function cropPhoto() : void
	{
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/V', 403, '' );
		}
		Output::i()->title = $this->club->name;
		
		/* Get the photo */
		if ( !$this->club->profile_photo_uncropped )
		{
			Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'editPhoto' ) );
		}
		$original = File::get( 'core_Clubs', $this->club->profile_photo_uncropped );
		$image = Image::create( $original->contents() );
		
		/* Work out which dimensions to suggest */
		if ( $image->width < $image->height )
		{
			$suggestedWidth = $suggestedHeight = $image->width;
		}
		else
		{
			$suggestedWidth = $suggestedHeight = $image->height;
		}
		
		/* Build form */
		$form = new Form( 'photo_crop', 'save', $this->club->url()->setQueryString( 'do', 'cropPhoto' ) );
		$form->class = 'ipsForm--noLabels';
		$form->add( new Custom('photo_crop', array( 0, 0, $suggestedWidth, $suggestedHeight ), FALSE, array(
			'getHtml'	=> function( $field ) use ( $original )
			{
				return Theme::i()->getTemplate('members', 'core', 'global')->photoCrop( $field->name, $field->value, $this->club->url()->setQueryString( 'do', 'cropPhotoGetPhoto' )->csrf() );
			}
		) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Crop it */
			$image->cropToPoints( $values['photo_crop'][0], $values['photo_crop'][1], $values['photo_crop'][2], $values['photo_crop'][3] );
			
			/* Delete the existing */
			if ( $this->club->profile_photo )
			{
				try
				{
					File::get( 'core_Clubs', $this->club->profile_photo )->delete();
				}
				catch ( Exception $e ) { }
			}
						
			/* Save it */
			$croppedFilename = mb_substr( $original->originalFilename, 0, mb_strrpos( $original->originalFilename, '.' ) ) . '.cropped' . mb_substr( $original->originalFilename, mb_strrpos( $original->originalFilename, '.' ) );
			$cropped = File::create( 'core_Clubs', $croppedFilename, (string) $image );
			$this->club->profile_photo = (string) $cropped;
			$this->club->save();

			/* Edited member, so clear widget caches (stats, widgets that contain photos, names and so on) */
			Widget::deleteCaches();
							
			/* Redirect */
			Output::i()->redirect( $this->club->url() );
		}
		
		/* Display */
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Get photo for cropping
	 * If the photo is on a different domain to the JS that handles cropping,
	 * it will be blocked because of CORS. See notes in Cropper documentation.
	 *
	 * @return	void
	 */
	protected function cropPhotoGetPhoto() : void
	{
		Session::i()->csrfCheck();

		/* Bypass the CSRF check, otherwise the image doesn't load when IN_DEV */
		Output::i()->bypassCsrfKeyCheck = true;

		$original = File::get( 'core_Clubs', $this->club->profile_photo_uncropped );
		$headers = array( "Content-Disposition" => Output::getContentDisposition( 'inline', $original->filename ) );
		Output::i()->sendOutput( $original->contents(), 200, File::getMimeType( $original->filename ), $headers );
	}
	
	/**
	 * See Members
	 *
	 * @return	void
	 */
	protected function members() : void
	{
		/* Public groups have no member list */
		if ( !$this->club->canViewMembers() )
		{
			Output::i()->error( 'node_error', '2C350/H', 404, '' );
		}
		
		/* What members are we getting? */
		$filter = NULL;
		$statuses = array( Club::STATUS_MEMBER, Club::STATUS_MODERATOR, Club::STATUS_LEADER );
		$baseUrl = $this->club->url()->setQueryString( 'do', 'members' );
		if ( isset( Request::i()->filter ) )
		{
			switch ( Request::i()->filter )
			{
				case Club::STATUS_LEADER:
					$filter = Club::STATUS_LEADER;
					$statuses = array( Club::STATUS_MODERATOR, Club::STATUS_LEADER );
					$baseUrl = $baseUrl->setQueryString( 'filter', Club::STATUS_LEADER );
					break;
				
				case Club::STATUS_REQUESTED:
					if ( $this->club->isLeader() )
					{
						$filter = Club::STATUS_REQUESTED;
						$statuses = array( Club::STATUS_REQUESTED );
						$baseUrl = $baseUrl->setQueryString( 'filter', Club::STATUS_REQUESTED );
					}
					break;
					
				case Club::STATUS_WAITING_PAYMENT:
					if ( $this->club->isLeader() )
					{
						$filter = Club::STATUS_WAITING_PAYMENT;
						$statuses = array( Club::STATUS_WAITING_PAYMENT );
						$baseUrl = $baseUrl->setQueryString( 'filter', Club::STATUS_WAITING_PAYMENT );
					}
					break;
					
				case Club::STATUS_BANNED:
					if ( $this->club->isLeader() )
					{
						$filter = Club::STATUS_BANNED;
						$statuses = array( Club::STATUS_DECLINED, Club::STATUS_BANNED );
						$baseUrl = $baseUrl->setQueryString( 'filter', Club::STATUS_BANNED );
					}
					break;
					
				case Club::STATUS_INVITED:
					if ( $this->club->isLeader() )
					{
						$filter = Club::STATUS_INVITED;
						$statuses = array( Club::STATUS_INVITED, Club::STATUS_INVITED_BYPASSING_PAYMENT );
						$baseUrl = $baseUrl->setQueryString( 'filter', Club::STATUS_INVITED );
					}
					break;
					
				case Club::STATUS_EXPIRED:
					if ( $this->club->isLeader() )
					{
						$filter = Club::STATUS_EXPIRED;
						$statuses = array( Club::STATUS_EXPIRED, Club::STATUS_EXPIRED_MODERATOR );
						$baseUrl = $baseUrl->setQueryString( 'filter', Club::STATUS_EXPIRED );
					}
					break;
			}
		}
		
		/* What are we sorting by? */
		$orderByClause = 'core_clubs_memberships.joined DESC';
		$sortBy = 'joined';
		if ( isset( Request::i()->sortby ) and Request::i()->sortby === 'name' )
		{
			$orderByClause = 'core_members.name ASC';
			$sortBy = 'name';
		}
		
		/* Sort out the offset */
		$perPage = $this->club->membersPerPage();

		$activePage = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

		if( $activePage < 1 )
		{
			$activePage = 1;
		}

		$offset = ( $activePage - 1 ) * $perPage;

		/* Fetch them */
		$members = $this->club->members( $statuses, array( $offset, $perPage ), $orderByClause, $this->club->isLeader() ? 5 : 1 );

		$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $baseUrl, ceil( $this->club->members( $statuses, NULL, $orderByClause, 4 ) / $perPage ), $activePage, $perPage );


		/* Display */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'rows' => Theme::i()->getTemplate( 'clubs' )->membersRows( $this->club, $members ), 'pagination' => $pagination, 'extraHtml' => '' ) );
		}
		else
		{
			$staffStatuses = array( Club::STATUS_LEADER, Club::STATUS_MODERATOR );
			if ( $this->club->isLeader( Member::loggedIn() ) )
			{
				$staffStatuses[] = Club::STATUS_EXPIRED_MODERATOR;
			}
			$clubStaff = $this->club->members( $staffStatuses, NULL, "IF(core_clubs_memberships.status='leader',0,1), core_clubs_memberships.joined ASC", $this->club->isLeader( Member::loggedIn() ) ? 5 : 3 );
			
			Output::i()->title = $this->club->name;

			if( $firstTab = $this->club->firstTab() AND !isset( $firstTab['club_members'] ) )
			{
				Output::i()->breadcrumb[] = array( $this->club->url(), $this->club->name );
				Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'club_members' ) );
			}
			else
			{
				Output::i()->breadcrumb[] = array( NULL, $this->club->name );
			}

			Output::i()->output = Theme::i()->getTemplate( 'clubs' )->members( $this->club, $members, $pagination, $sortBy, $filter, $clubStaff );
		}

		/* Set some meta tags for the club */
		$this->_setDefaultMetaTags();
	}
	
	/**
	 * Accept a join request
	 *
	 * @return	void
	 */
	protected function acceptRequest() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/3', 403, '' );
		}
		
		/* Check the member's request is pending */
		$member = Member::load( Request::i()->member );
		if ( $this->club->memberStatus( $member ) != Club::STATUS_REQUESTED )
		{
			Output::i()->error( 'node_error', '2C350/4', 403, '' );
		}
		
		/* Add them */
		$status = Club::STATUS_MEMBER;
		if ( $this->club->isPaid() and !isset( Request::i()->waiveFee ) )
		{
			$status = Club::STATUS_WAITING_PAYMENT;
		}
		$this->club->addMember( $member, $status, TRUE, Member::loggedIn(), NULL, TRUE );
		$this->club->recountMembers();
		
		/* Notify the member */
		$notification = new Notification( Application::load('core'), 'club_response', $this->club, array( $this->club, TRUE ) );
		$notification->recipients->attach( $member );
		$notification->send();
		
		/* Send a notification to any leaders besides ourselves */
		$notification = new Notification( Application::load('core'), 'club_join', $this->club, array( $this->club, $member ), array( 'response' => TRUE ) );
		foreach ( $this->club->members( array( Club::STATUS_LEADER ), NULL, NULL, 2 ) as $leader )
		{
			$leader = Member::constructFromData( $leader );
			if ( $leader->member_id != Member::loggedIn()->member_id )
			{
				$notification->recipients->attach( $leader );
			}
		}
		if ( count( $notification->recipients ) )
		{
			$notification->send();
		}
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'status' => 'approved' ) );
		}
		else
		{
			/* If other requests are pending, send us back, otherwise take us to the main member list */
			$url = $this->club->url()->setQueryString( 'do', 'members' );
			if ( count( $this->club->members( array( Club::STATUS_REQUESTED ) ) ) )
			{
				Output::i()->redirect( $url->setQueryString( 'filter', Club::STATUS_REQUESTED ) );
			}
			else
			{
				Output::i()->redirect( $url );
			}
		}
	}
	
	/**
	 * Decline a join request
	 *
	 * @return	void
	 */
	protected function declineRequest() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/F', 403, '' );
		}
		
		/* Check the member's request is pending */
		$member = Member::load( Request::i()->member );
		if ( $this->club->memberStatus( $member ) != Club::STATUS_REQUESTED )
		{
			Output::i()->error( 'node_error', '2C350/G', 403, '' );
		}
		
		/* Decline them */
		$this->club->addMember( $member, Club::STATUS_DECLINED, TRUE, Member::loggedIn() );
		
		/* Notify the member */
		$notification = new Notification( Application::load('core'), 'club_response', $this->club, array( $this->club, FALSE ) );
		$notification->recipients->attach( $member );
		$notification->send();
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'status' => 'declined' ) );
		}
		else
		{
			/* If other requests are pending, send us back, otherwise take us to the main member list */
			$url = $this->club->url()->setQueryString( 'do', 'members' );
			if ( count( $this->club->members( array( Club::STATUS_REQUESTED ) ) ) )
			{
				Output::i()->redirect( $url->SetQueryString( 'filter', Club::STATUS_REQUESTED ) );
			}
			else
			{
				Output::i()->redirect( $url );
			}
		}
	}
	
	/**
	 * Make a member a leader
	 *
	 * @return	void
	 */
	protected function makeLeader() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/6', 403, '' );
		}
		
		/* Get member */
		$member = Member::load( Request::i()->member );
		if ( !in_array( $this->club->memberStatus( $member ), array( Club::STATUS_MEMBER, Club::STATUS_EXPIRED, Club::STATUS_MODERATOR, Club::STATUS_EXPIRED_MODERATOR ) ) )
		{
			Output::i()->error( 'node_error', '2C350/7', 403, '' );
		}
		
		/* Promote */
		$this->club->addMember( $member, Club::STATUS_LEADER, TRUE );
		$this->club->recountMembers();
		
		/* Redirect */
		Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'members' ) );
	}
	
	/**
	 * Demote a member from being a leader
	 *
	 * @return	void
	 */
	protected function demoteLeader() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/8', 403, '' );
		}
		
		/* Get member */
		$member = ( Application::appIsEnabled('nexus') and Settings::i()->clubs_paid_on ) ? Customer::load( Request::i()->member ) : Member::load( Request::i()->member );
		if ( $this->club->memberStatus( $member ) != Club::STATUS_LEADER )
		{
			Output::i()->error( 'node_error', '2C350/9', 403, '' );
		}
		
		/* Are they expired? */
		$status = Club::STATUS_MEMBER;
		if ( Application::appIsEnabled('nexus') and Settings::i()->clubs_paid_on and $this->club->renewal_price )
		{
			foreach ( ClubMembership::getPurchases( $member, $this->club->id ) as $purchase )
			{
				if ( $purchase->expire and $purchase->expire->getTimestamp() < time() )
				{
					$status = Club::STATUS_EXPIRED;
				}
			}
		}
		
		/* Promote */
		$this->club->addMember( $member, $status, TRUE );
		$this->club->recountMembers();
		
		/* Redirect */
		Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'members' ) );
	}
	
	/**
	 * Make a member a moderator
	 *
	 * @return	void
	 */
	protected function makeModerator() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/K', 403, '' );
		}
		
		/* Get member */
		$member = ( Application::appIsEnabled('nexus') and Settings::i()->clubs_paid_on ) ? Customer::load( Request::i()->member ) : Member::load( Request::i()->member );
		if ( !in_array( $this->club->memberStatus( $member ), array( Club::STATUS_MEMBER, Club::STATUS_EXPIRED, Club::STATUS_LEADER ) ) )
		{
			Output::i()->error( 'node_error', '2C350/L', 403, '' );
		}
		
		/* Are they expired? */
		$status = Club::STATUS_MODERATOR;
		if ( Application::appIsEnabled('nexus') and Settings::i()->clubs_paid_on and $this->club->renewal_price )
		{
			foreach ( ClubMembership::getPurchases( $member, $this->club->id ) as $purchase )
			{
				if ( $purchase->expire and $purchase->expire->getTimestamp() < time() )
				{
					$status = Club::STATUS_EXPIRED_MODERATOR;
				}
			}
		}
		
		/* Promote */
		$this->club->addMember( $member, $status, TRUE );
		$this->club->recountMembers();
		
		/* Redirect */
		Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'members' ) );
	}
	
	/**
	 * Demote a member from being a moderator
	 *
	 * @return	void
	 */
	protected function demoteModerator() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/M', 403, '' );
		}
		
		/* Get member */
		$member = ( Application::appIsEnabled('nexus') and Settings::i()->clubs_paid_on ) ? Customer::load( Request::i()->member ) : Member::load( Request::i()->member );
		if ( !in_array( $this->club->memberStatus( $member ), array( Club::STATUS_MODERATOR, Club::STATUS_EXPIRED_MODERATOR ) ) )
		{
			Output::i()->error( 'node_error', '2C350/N', 403, '' );
		}
		
		/* Are they expired? */
		$status = Club::STATUS_MEMBER;
		if ( Application::appIsEnabled('nexus') and Settings::i()->clubs_paid_on and $this->club->renewal_price )
		{
			foreach ( ClubMembership::getPurchases( $member, $this->club->id ) as $purchase )
			{
				if ( $purchase->expire and $purchase->expire->getTimestamp() < time() )
				{
					$status = Club::STATUS_EXPIRED;
				}
			}
		}
		
		/* Promote */
		$this->club->addMember( $member, $status, TRUE );
		$this->club->recountMembers();
		
		/* Redirect */
		Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'members' ) );
	}
	
	/**
	 * Remove a member
	 *
	 * @return	void
	 */
	protected function removeMember() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/C', 403, '' );
		}
		
		/* Get member */
		$member = Member::load( Request::i()->member );
		$status = $this->club->memberStatus( $member );
		
		/* If this was just an invite, then just remove that */
		if ( in_array( $status, array( Club::STATUS_INVITED, Club::STATUS_INVITED_BYPASSING_PAYMENT ) ) )
		{
			$this->club->removeMember( $member );
			Db::i()->delete( 'core_notifications', array( '`member`=? AND notification_app=? AND notification_key=? and item_id=?', $member->member_id, 'core', 'club_invitation', $this->club->id ) );
			$member->recountNotifications();
		}
		
		/* If they were previously accepted and waiting for payment, treat it as a decline */
		elseif ( $status == Club::STATUS_WAITING_PAYMENT )
		{
			/* Decline them */
			$this->club->addMember( $member, Club::STATUS_DECLINED, TRUE, Member::loggedIn() );
			
			/* Notify the member */
			$notification = new Notification( Application::load('core'), 'club_response', $this->club, array( $this->club, FALSE ) );
			$notification->recipients->attach( $member );
			$notification->send();
		}
		
		/* Otherwise check they are actually a member that can be removed */
		else
		{
			if ( !in_array( $status, array( Club::STATUS_MEMBER, Club::STATUS_MODERATOR, Club::STATUS_LEADER, Club::STATUS_EXPIRED, Club::STATUS_EXPIRED_MODERATOR ) ) )
			{
				Output::i()->error( 'node_error', '2C350/9', 403, '' );
			}
			if ( $this->club->owner and $this->club->owner->member_id === $member->member_id )
			{
				Output::i()->error( 'club_cannot_remove_owner', '2C350/E', 403, '' );
			}
			
			/* Cancel purchase */
			if ( Application::appIsEnabled('nexus') and Settings::i()->clubs_paid_on )
			{
				foreach ( ClubMembership::getPurchases( Customer::load( Request::i()->member ), $this->club->id ) as $purchase )
				{
					$purchase->cancelled = TRUE;
					$purchase->member->log( 'purchase', array( 'type' => 'cancel', 'id' => $purchase->id, 'name' => $purchase->name ) );
					$purchase->can_reactivate = FALSE;
					$purchase->save();
				}
			}
			
			/* Remove */
			$this->club->addMember( $member, Club::STATUS_BANNED, TRUE, Member::loggedIn() );
		}
		
		/* Recount */
		$this->club->recountMembers();
		
		/* Redirect */
		Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'members' ) );
	}
	
	/**
	 * Waive membership fee
	 *
	 * @return	void
	 */
	protected function bypassPayment() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() or !Application::appIsEnabled('nexus') or !Settings::i()->clubs_paid_on )
		{
			Output::i()->error( 'no_module_permission', '2C350/W', 403, '' );
		}
		
		/* Get member */
		$member = Customer::load( Request::i()->member );
		
		/* Do it */
		switch ( $this->club->memberStatus( $member ) )
		{
			/* If they were waiting for payment, just go ahead and promote them to a member */
			case Club::STATUS_WAITING_PAYMENT:
				$this->club->addMember( $member, Club::STATUS_MEMBER, TRUE );
				break;
				
			/* If they were already in the club, find their purchase and remove the expiry date */
			case Club::STATUS_MEMBER:
			case Club::STATUS_EXPIRED:
			case Club::STATUS_MODERATOR:
			case Club::STATUS_EXPIRED_MODERATOR:
			case Club::STATUS_LEADER:
				foreach ( ClubMembership::getPurchases( $member, $this->club->id ) as $purchase )
				{
					$extra = $purchase->extra;
					$extra['originalExpire'] = $purchase->expire ? $purchase->expire->getTimestamp() : NULL;
					$purchase->extra = $extra;
					$purchase->expire = NULL;
					$purchase->save();
					
					$member->log( 'purchase', array( 'type' => 'info', 'info' => 'never_expire', 'id' => $purchase->id, 'name' => $purchase->name ) );
				}
				break;
				
			/* If they have been invited, just change their status to invited bypassing payment */
			case Club::STATUS_INVITED:
				$this->club->addMember( $member, Club::STATUS_INVITED_BYPASSING_PAYMENT, TRUE );
				break;			
				
			/* For anything else, we can't do this... */
			default:
				Output::i()->error( 'node_error', '2C350/X', 403, '' );
		}
		$this->club->recountMembers();
		
		/* Redirect */
		Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'members' ) );
	}
	
	/**
	 * Restore Payment
	 *
	 * @return	void
	 */
	protected function restorePayment() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() or !Application::appIsEnabled('nexus') or !Settings::i()->clubs_paid_on )
		{
			Output::i()->error( 'no_module_permission', '2C350/W', 403, '' );
		}
		
		/* Get member */
		$member = Customer::load( Request::i()->member );
		
		/* Do it */
		$status = $this->club->memberStatus( $member );
		switch ( $status )
		{
			/* If they were already in the club, find their purchase and restore the expiry date */
			case Club::STATUS_MEMBER:
			case Club::STATUS_EXPIRED:
			case Club::STATUS_MODERATOR:
			case Club::STATUS_EXPIRED_MODERATOR:
			case Club::STATUS_LEADER:
				
				$foundPurchase = FALSE;
				foreach ( ClubMembership::getPurchases( $member, $this->club->id ) as $purchase )
				{
					$foundPurchase = TRUE;
					
					if ( !$purchase->renewals or !$purchase->renewals->cost->amount->isZero() )
					{
						$purchase->renewals = $this->club->renewalTerm( $purchase->renewal_currency ?: $member->defaultCurrency() );
					}
					if ( isset( $purchase->extra['originalExpire'] ) )
					{
						$newExpiry = DateTime::ts( $purchase->extra['originalExpire'] );
					}
					else
					{
						$newExpiry = DateTime::create()->add( $purchase->renewals->interval );
					}
					$purchase->expire = $newExpiry;
					$purchase->save();
					$member->log( 'purchase', array( 'type' => 'info', 'info' => 'restored_expire', 'id' => $purchase->id, 'name' => $purchase->name ) );
					
					if ( $newExpiry->getTimestamp() < time() )
					{
						if ( $status === Club::STATUS_MEMBER )
						{
							$this->club->addMember( $member, Club::STATUS_EXPIRED, TRUE );
						}
						elseif ( $status === Club::STATUS_MODERATOR )
						{
							$this->club->addMember( $member, Club::STATUS_EXPIRED_MODERATOR, TRUE );
						}
					}
					else
					{
						if ( $status === Club::STATUS_EXPIRED )
						{
							$this->club->addMember( $member, Club::STATUS_MEMBER, TRUE );
						}
						elseif ( $status === Club::STATUS_EXPIRED_MODERATOR )
						{
							$this->club->addMember( $member, Club::STATUS_MODERATOR, TRUE );
						}
					}
				}
				
				/* If we couldn't find a purchase, we'll have to remove them and re-invite them */
				if ( !$foundPurchase )
				{
					$this->club->addMember( $member, Club::STATUS_INVITED, TRUE, NULL, Member::loggedIn(), TRUE );
					
					$notification = new Notification( Application::load('core'), 'club_invitation', $this->club, array( $this->club, Member::loggedIn() ), array( 'invitedBy' => Member::loggedIn()->member_id ) );
					$notification->recipients->attach( $member );
					$notification->send();
				}
				
				break;
				
			/* If they have been invited, just change their status to invited NOT bypassing payment */
			case Club::STATUS_INVITED_BYPASSING_PAYMENT:
				$this->club->addMember( $member, Club::STATUS_INVITED, TRUE );
				break;
				
			/* For anything else, we can't do this... */
			default:
				Output::i()->error( 'node_error', '2C350/X', 403, '' );
		}
		$this->club->recountMembers();
		
		/* Redirect */
		Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'members' ) );
	}
	
	/**
	 * Rules
	 *
	 * @return void
	 */
	protected function rules() : void
	{
		if ( $this->club->rules_required AND !$this->club->rulesAcknowledged() )
		{
			$form = new Form( 'form', 'accept' );
			$form->hiddenValues['accepted'] = 1;
			if ( $referrer = Request::i()->referrer() )
			{
				$form->hiddenValues['ref'] = base64_encode( (string) $referrer );
			}
			$form->class = 'ipsForm--vertical ipsForm--club-rules';

			if ( $this->club->memberStatus( Member::loggedIn() ) === NULL )
			{
				$form->addButton( 'cancel', 'link', $this->club->url() );
			}
			else
			{
				$form->addButton( 'club_leave', 'link', $this->club->url()->setQueryString( 'do', 'leave' )->csrf() );
			}
			
			if ( $values = $form->values() )
			{
				/* If we're not a member of this club yet, send us back to the join form */
				if( $this->club->memberStatus( Member::loggedIn() ) === NULL  )
				{
					if( $referrer = Request::i()->referrer() )
					{
						Output::i()->redirect( $referrer->setQueryString( 'rulesAcknowledged', 1 ), 'accepted' );
					}
					else
					{
						Output::i()->redirect( $this->club->url()->setQueryString( 'rulesAcknowledged', 1 ), 'accepted' );
					}
				}

				$this->club->acknowledgeRules( Member::loggedIn() );
				if ( $referrer = Request::i()->referrer() )
				{
					Output::i()->redirect( $referrer, 'accepted' );
				}
				
				Output::i()->redirect( $this->club->url(), 'accepted' );
			}
			
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'club_rules' );
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'clubs', 'core' ), 'rulesForm' ), $this->club );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'club_rules' );
			Output::i()->output = Theme::i()->getTemplate( 'clubs', 'core' )->clubRules( $this->club );
		}
	}
	
	/**
	 * Join
	 *
	 * @return	void
	 */
	protected function join() : void
	{
		/* Can we join? */
		if ( !$this->club->canJoin() )
		{
			Output::i()->error( 'no_module_permission', '2C350/I', 403, '' );
		}

		/* Are there rules that need to be acknowledged which we haven't acknowledged yet? */
		if( $this->club->rules_required AND !$this->club->rulesAcknowledged() AND !Request::i()->rulesAcknowledged )
		{
			$rulesUrl = $this->club->url()->setQueryString( 'do', 'rules' );

			if( $referrer = Request::i()->referrer() )
			{
				$rulesUrl	= $rulesUrl->addRef( Request::i()->url()->addRef( $referrer ) );
			}

			Output::i()->redirect( $rulesUrl );
		}
		
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* If this is an open club, or the member was invited, or they have mod access anyway go ahead and add them */
		if ( in_array( $this->memberStatus, array( Club::STATUS_INVITED, Club::STATUS_INVITED_BYPASSING_PAYMENT, Club::STATUS_WAITING_PAYMENT ) ) or $this->club->type === Club::TYPE_OPEN or Member::loggedIn()->modPermission('can_access_all_clubs') )
		{
			/* Unless they have to pay */
			if ( $this->club->isPaid() and $this->memberStatus !== Club::STATUS_INVITED_BYPASSING_PAYMENT )
			{
				if ( $this->club->joiningFee() )
				{
					$invoiceUrl = $this->club->generateInvoice();
					
					/* Take them to it */
					Output::i()->redirect( $invoiceUrl );
				}
				else
				{
					Output::i()->error( 'club_paid_unavailable', '1C350/N', 403, '' );
				}
			}
			else
			{
				$this->club->addMember( Member::loggedIn(), Club::STATUS_MEMBER, TRUE, NULL, NULL, TRUE );
				$this->club->recountMembers();
				$notificationKey = 'club_join';
			}
		}
		/* Otherwise, add the request */
		else
		{
			$this->club->addMember( Member::loggedIn(), Club::STATUS_REQUESTED );
			$notificationKey = 'club_request';
		}
		
		/* Send a notification to any leaders */
		$notification = new Notification( Application::load('core'), $notificationKey, $this->club, array( $this->club, Member::loggedIn() ), array( Member::loggedIn()->member_id ) );
		foreach ( $this->club->members( array( Club::STATUS_LEADER ), NULL, NULL, 2 ) as $member )
		{
			$notification->recipients->attach( Member::constructFromData( $member ) );
		}
		$notification->send();

		/* If we just accepted the rules, set the flag */
		if( Request::i()->rulesAcknowledged )
		{
			$this->club->acknowledgeRules( Member::loggedIn() );
		}
		
		/* Redirect */
		if ( ! $this->club->rulesAcknowledged() )
		{
			Output::i()->redirect( $this->club->url()->setQueryString( 'do', 'rules' ) );
		}
		else
		{
			if ( $url = Request::i()->referrer() )
			{
				Output::i()->redirect( $url );
			}
			else
			{
				Output::i()->redirect( $this->club->url() );
			}
		}
	}
	
	/**
	 * Leave
	 *
	 * @return	void
	 */
	protected function leave() : void
	{
		/* Can we leave? */
		if ( !in_array( $this->club->memberStatus( Member::loggedIn() ), array( Club::STATUS_MEMBER, Club::STATUS_MODERATOR, Club::STATUS_LEADER, Club::STATUS_EXPIRED, Club::STATUS_EXPIRED_MODERATOR, Club::STATUS_WAITING_PAYMENT, Club::STATUS_INVITED, Club::STATUS_REQUESTED ) ) or ( $this->club->owner and $this->club->owner->member_id == Member::loggedIn()->member_id ) )
		{
			Output::i()->error( 'no_module_permission', '2C350/S', 403, '' );
		}
		
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Cancel purchase */
		if ( Application::appIsEnabled('nexus') and Settings::i()->clubs_paid_on )
		{
			foreach ( ClubMembership::getPurchases( Customer::loggedIn(), $this->club->id ) as $purchase )
			{
				$purchase->cancelled = TRUE;
				$purchase->member->log( 'purchase', array( 'type' => 'cancel', 'id' => $purchase->id, 'name' => $purchase->name ) );
				$purchase->can_reactivate = FALSE;
				$purchase->save();
			}
		}
		
		/* Leave */
		$this->club->removeMember( Member::loggedIn() );
		$this->club->recountMembers();
		$this->club->save();
		
		/* Delete the invitation */
		Db::i()->delete( 'core_notifications', array( '`member`=? AND notification_app=? AND notification_key=? and item_id=?', Member::loggedIn()->member_id, 'core', 'club_invitation', $this->club->id ) );
		Member::loggedIn()->recountNotifications();

		/* Redirect */
		Output::i()->redirect( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ) );
	}

	/**
	 * Cancel Join Request
	 *
	 * @return	void
	 */
	protected function cancelJoin() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();

		/* Leave */
		$this->club->removeMember( Member::loggedIn() );
		$this->club->recountMembers();
		$this->club->save();

		/* Update notification counts */
		Db::i()->delete( 'core_notifications', array( 'notification_key=? and item_id=? and extra=?', 'club_request', $this->club->id, json_encode( array( Member::loggedIn()->member_id ) ) ) );
		foreach( $this->club->members( array( 'moderator', 'leader' ), 250, NULL, 2 ) as $member )
		{
			$member = Member::constructFromData( $member );
			$member->recountNotifications();
		}

		/* Redirect */
		Output::i()->redirect( $this->club->url(), 'clubs_request_cancelled' );
	}

	/**
	 * Renew
	 *
	 * @return	void
	 */
	protected function renew() : void
	{
		/* Can we renew? */
		if ( !in_array( $this->club->memberStatus( Member::loggedIn() ), array( Club::STATUS_EXPIRED, Club::STATUS_EXPIRED_MODERATOR ) ) )
		{
			Output::i()->error( 'no_module_permission', '2C350/Y', 403, '' );
		}
		
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Find the purchase */
		foreach ( ClubMembership::getPurchases( Customer::loggedIn(), $this->club->id ) as $purchase )
		{
			if ( $invoice = $purchase->invoice_pending )
			{
				Output::i()->redirect( $invoice->checkoutUrl() );
			}

			Output::i()->redirect( $purchase->url()->setQueryString( array( 'do' => 'renew', 'cycles' => 1 ) )->csrf() );
		}
		
		/* Couldn't find it? */
		Output::i()->error( 'no_module_permission', '2C350/Z', 500, '' );
	}
	
	/**
	 * Invite Members
	 *
	 * @return	void
	 */
	protected function invite() : void
	{
		if ( !$this->club->canInvite() )
		{
			Output::i()->error( 'no_module_permission', '2C350/5', 403, '' );
		}
		
		$form = new Form( 'form', 'club_send_invitations' );
		$form->class = 'ipsForm--vertical ipsForm--club-invite';
		$form->add( new FormMember( 'members', NULL, TRUE, array( 'multiple' => NULL ) ) );
		if ( $this->club->isPaid() and $this->club->isLeader() )
		{
			$form->add( new YesNo( 'club_invite_waive_fee', FALSE ) );
			if ( $this->club->renewal_price )
			{
				Member::loggedIn()->language()->words['club_invite_waive_fee_desc'] = Member::loggedIn()->language()->addToStack('club_invite_waive_fee_renewal');
			}
		}
		
		if ( $values = $form->values() )
		{
			$status = Club::STATUS_INVITED;
			if ( $this->club->isPaid() and $this->club->isLeader() and $values['club_invite_waive_fee'] )
			{
				$status = Club::STATUS_INVITED_BYPASSING_PAYMENT;
			}

			foreach ( $values['members'] as $member )
			{
				if ( $member instanceof Member )
				{
					$memberStatus = $this->club->memberStatus( $member );
					if ( !$memberStatus or in_array( $memberStatus, array( Club::STATUS_INVITED, Club::STATUS_REQUESTED, Club::STATUS_DECLINED, Club::STATUS_BANNED ) ) )
					{
						$this->club->addMember( $member, $status, TRUE, NULL, Member::loggedIn(), TRUE );
					}
				}
			}
			$this->club->sendInvitation( Member::loggedIn(), $values['members'] );
			
			Output::i()->redirect( $this->club->url(), 'club_notifications_sent' );
		}
		
		Output::i()->title = $this->club->name;
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Re-invite a banned memer
	 *
	 * @return	void
	 */
	protected function reInvite() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/J', 403, '' );
		}
		
		/* Check the member needs to be reinvited */
		$member = Member::load( Request::i()->member );
		if ( !in_array( $this->club->memberStatus( $member ), array( Club::STATUS_DECLINED, Club::STATUS_BANNED ) ) )
		{
			Output::i()->error( 'node_error', '2C350/K', 403, '' );
		}
		
		/* Add them */
		$this->club->removeMember( $member );
		$this->club->addMember( $member, Club::STATUS_INVITED, FALSE, NULL, Member::loggedIn() );
		$this->club->recountMembers();
		
		/* Notify the member */
		$notification = new Notification( Application::load('core'), 'club_invitation', $this->club, array( $this->club, Member::loggedIn() ), array( 'invitedBy' => Member::loggedIn()->member_id ) );
		$notification->recipients->attach( $member );
		$notification->send();
				
		/* If other requests are banned, send us back, otherwise take us to the main member list */
		$url = $this->club->url()->setQueryString( 'do', 'members' );
		if ( count( $this->club->members( array( Club::STATUS_DECLINED, Club::STATUS_BANNED ) ) ) )
		{
			Output::i()->redirect( $url->SetQueryString( 'filter', Club::STATUS_BANNED ) );
		}
		else
		{
			Output::i()->redirect( $url );
		}
	}
	
	/**
	 * Feature
	 *
	 * @return	void
	 */
	protected function feature() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !Member::loggedIn()->modPermission('can_manage_featured_clubs') )
		{
			Output::i()->error( 'no_module_permission', '2C350/Q', 403, '' );
		}
		
		/* Feature */
		$this->club->featured = TRUE;
		$this->club->save();
		
		/* Redirect */
		Output::i()->redirect( $this->club->url() );
	}
	
	/**
	 * Unfeature
	 *
	 * @return	void
	 */
	protected function unfeature() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !Member::loggedIn()->modPermission('can_manage_featured_clubs') )
		{
			Output::i()->error( 'no_module_permission', '2C350/Q', 403, '' );
		}
		
		/* Unfeature */
		$this->club->featured = FALSE;
		$this->club->save();
		
		/* Redirect */
		Output::i()->redirect( $this->club->url() );
	}
	
	/**
	 * Approve/Deny
	 *
	 * @return	void
	 */
	protected function approve() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !Member::loggedIn()->modPermission('can_manage_featured_clubs') or $this->club->approved or !Settings::i()->clubs_require_approval )
		{
			Output::i()->error( 'no_module_permission', '2C350/U', 403, '' );
		}
		
		/* Approve... */
		if ( Request::i()->approved )
		{
			$this->club->approved = TRUE;
			$this->club->save();
			$this->club->onApprove();
			
			Output::i()->redirect( $this->club->url() );
		}
		
		/* ... or delete */
		else
		{
			$this->club->delete();
			
			Output::i()->redirect( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ) );
		}
	}
	
	/**
	 * Create a node
	 *
	 * @return	void
	 */
	protected function nodeForm() : void
	{
		/* Permission check */
		$class = Request::i()->type;
		if ( !$this->club->isLeader() or !in_array( $class, Club::availableNodeTypes( Member::loggedIn() ) ) or ( Settings::i()->clubs_require_approval and !$this->club->approved ) )
		{
			Output::i()->error( 'no_module_permission', '2C350/T', 403, '' );
		}
		
		/* Load if editing */
		if ( isset( Request::i()->node ) )
		{
			try
			{
				$node = $class::load( (int) Request::i()->node );
				$club = $node->club();
				if ( !$club or $club->id !== $this->club->id )
				{
					throw new Exception;
				}
			}
			catch ( Exception $e )
			{
				Output::i()->error( 'node_error', '2C350/O', 404, '' );
			}
		}
		else
		{
			$node = new $class;
		}
		
		/* Build Form */
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--club-create-node';
		$node->clubForm( $form, $this->club );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$node->saveClubForm( $this->club, $values );
			Output::i()->redirect( $node->url() );
		}
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('club_create_node');
		Output::i()->output = Request::i()->isAjax() ? $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) ) : $form;
	}
	
	/**
	 * Delete a node
	 *
	 * @return	void
	 */
	protected function nodeDelete() : void
	{
		Session::i()->csrfCheck();
		
		/* Permission check */
		if ( !$this->club->isLeader() )
		{
			Output::i()->error( 'no_module_permission', '2C350/AA', 403, '' );
		}
		
		/* Load */
		$class = Request::i()->type;

		try
		{
			if ( !in_array( 'IPS\Node\Model', class_parents( $class ) ) )
			{
				throw new Exception;
			}

			$node = $class::load( (int) Request::i()->node );
			$club = $node->club();
			if ( !$club or $club->id !== $this->club->id )
			{
				throw new Exception;
			}
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2C350/AB', 404, '' );
		}
		
		/* Permission check */
		$itemClass = $node::$contentItemClass;
		if ( !$node->modPermission( 'delete', Member::loggedIn(), $itemClass ) and $itemClass::contentCount( $node, TRUE, TRUE, TRUE, 1 ) )
		{
			Output::i()->error( 'no_module_permission', '2C350/AC', 403, '' );
		}
		
		/* Delete */
		Db::i()->delete( 'core_clubs_node_map', array( 'club_id=? AND node_class=? AND node_id=?', $club->id, $class, $node->_id ) );
		$node->deleteOrMoveFormSubmit( array() );
		
		/* Redirect */
		Output::i()->redirect( $club->url() );
	}
	
	/**
	 * Add a page
	 *
	 * @return	void
	 */
	public function addPage() : void
	{
		/* Init form */
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--club-add-page';
		Page::form( $form, $this->club );
		
		/* Form Submission */
		if ( $values = $form->values() )
		{
			$page = new Page;
			$page->formatFormValues( $values );
			$page->save();
			
			File::claimAttachments( 'club-page-new', $page->id );
			
			Output::i()->redirect( $page->url() );
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack( "add_page_to_club", NULL, array( "sprintf" => array( $this->club->name ) ) );
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/* !Cover Photo */
	
	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return	string
	 */
	protected function _coverPhotoStorageExtension(): string
	{
		return 'core_Clubs';
	}
	
	/**
	 * Set Cover Photo
	 *
	 * @param	CoverPhoto	$photo	New Photo
	 * @return	void
	 */
	protected function _coverPhotoSet( CoverPhoto $photo ) : void
	{
		$this->club->cover_photo = (string) $photo->file;
		$this->club->cover_offset = $photo->offset;
		$this->club->save();
	}
	
	/**
	 * Get Cover Photo
	 *
	 * @return	CoverPhoto
	 */
	protected function _coverPhotoGet(): CoverPhoto
	{
		return $this->club->coverPhoto();
	}
	
	/**
	 * Embed
	 *
	 * @return	void
	 */
	protected function embed() : void
	{
		$title = Member::loggedIn()->language()->addToStack( 'error_title' );
		
		try
		{
			$club = Club::load( Request::i()->id );
			if ( !$club->canView() )
			{
				$output = Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedNoPermission();
			}
			else
			{
				$output = $club->embedContent();
			}
		}
		catch( Exception $e )
		{
			$output = Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedUnavailable();
		}
		
		/* Make sure our iframe contents get the necessary elements and JS */
		$js = array(
			Output::i()->js( 'js/commonEmbedHandler.js', 'core', 'interface' ),
			Output::i()->js( 'js/internalEmbedHandler.js', 'core', 'interface' )
		);
		Output::i()->base = '_parent';

		/* We need to keep any embed.css files that have been specified so that we can re-add them after we re-fetch the css framework */
		$embedCss = array();
		foreach( Output::i()->cssFiles as $cssFile )
		{
			if( mb_stristr( $cssFile, 'embed.css' ) )
			{
				$embedCss[] = $cssFile;
			}
		}

		/* We need to reset the included CSS files because by this point the responsive files are already in the output CSS array */
		Output::i()->cssFiles = array();
		Output::i()->responsive = FALSE;
		Front::baseCss();
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, $embedCss );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/embeds.css', 'core', 'front' ) );

		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core', 'front' )->embedInternal( $output, $js ) );
	}

	/**
	 * Save the club menu order
	 *
	 * @return	void
	 */
	protected function saveMenu() : void
	{
		Session::i()->csrfCheck();

		/* Permission check */
		if ( !$this->club->canManageNavigation() )
		{
			Output::i()->error( 'no_module_permission', '1C350/O', 403, '' );
		}

		if ( is_array( Request::i()->tabOrder ) )
		{
			$tabs =  Request::i()->tabOrder;
			$this->club->menu_tabs = json_encode( $tabs );
			$this->club->save();
		}

		Output::i()->json( 'ok' );
	}

	/**
	 * Set some default meta tags for the club
	 *
	 * @return void
	 */
	protected function _setDefaultMetaTags() : void
	{
		if( $this->club->cover_photo )
		{
			Output::i()->metaTags['og:image'] = File::get( 'core_Clubs', $this->club->cover_photo )->url;
		}
		
		Output::i()->metaTags['og:title'] = $this->club->name;

		if( $this->club->about )
		{
			Output::i()->metaTags['description'] = $this->club->about;
			Output::i()->metaTags['og:description'] = $this->club->about;
		}
	}

	/**
	 * Update Existing Purchases
	 *
	 * @return	void
	 */
	public function updateExisting() : void
	{
		Session::i()->csrfCheck();

		/* Make sure logged-in user has permission */
		if ( !( $this->club->owner and $this->club->owner->member_id == Member::loggedIn()->member_id ) and !Member::loggedIn()->modPermission('can_access_all_clubs') )
		{
			Output::i()->error( 'no_module_permission', '3C350/Q', 403, '' );
		}

		$changes = json_decode( Request::i()->changes, TRUE );

		Task::queue( 'core', 'UpdateClubRenewals', array( 'changes' => $changes, 'club' => $this->club->id ), 5 );

		/* Redirect */
		Output::i()->redirect( $this->club->url(), 'saved' );
	}
}
