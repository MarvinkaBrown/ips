<?php
/**
 * @brief		Profile
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Jul 2013
 */

namespace IPS\core\modules\front\members;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeInterface;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Application\Module;
use IPS\Content\Filter;
use IPS\Content\Reaction;
use IPS\Content\Search\Query;
use IPS\core\Achievements\Badge;
use IPS\core\Achievements\Recognize;
use IPS\core\DataLayer;
use IPS\core\ProfileFields\Field;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\File\Exception as FileException;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\CoverPhoto\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Image;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Member\ProfileStep;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use IPS\Widget;
use UnderflowException;
use UnexpectedValueException;
use function array_shift;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use const IPS\PHOTO_THUMBNAIL_SIZE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile
 */
class profile extends Controller
{
	/**
	 * @brief These properties are used to specify datalayer context properties.
	 *
	 */
	public static array $dataLayerContext = array(
		'community_area' =>  [ 'value' => 'profile', 'odkUpdate' => 'true']
	);

	/**
	 * @brief	Member object
	 */
	protected ?Member $member = null;

	/**
	 * Main execute entry point - used to override breadcrumb
	 *
	 * @return void
	 */
	public function execute() : void
	{
		/* Load Member */
		$this->member = Member::load( Request::i()->id );
		if ( !$this->member->member_id )
		{
			Output::i()->error( 'node_error', '2C138/1', 404, '' );
		}
		
		/* Set breadcrumb */
		unset( Output::i()->breadcrumb['module'] );
		Output::i()->breadcrumb[] = array( $this->member->url(), $this->member->name );
		Output::i()->sidebar['enabled'] = FALSE;

		if( !Request::i()->isAjax() )
		{
			/* Don't index new empty profiles */
	        if( ! $this->shouldBeIndexed() )
			{
				Output::i()->metaTags['robots'] = 'noindex, follow';
			}

			Output::i()->linkTags['canonical'] = (string) $this->member->url();

			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_core.js', 'core', 'global' ) );
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/profiles.css' ) );
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/streams.css' ) );
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/leaderboard.css' ) );
		}
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_profile.js', 'core' ) );
		
		/* Go */
		parent::execute();
	}
	
	/**
	 * Change the users follow preference
	 *
	 * @return void
	 */
	protected function changeFollow() : void
	{
		if ( !Member::loggedIn()->modPermission('can_modify_profiles') AND ( Member::loggedIn()->member_id !== $this->member->member_id OR !$this->member->group['g_edit_profile'] ) )
		{
			Output::i()->error( 'no_permission_edit_profile', '2C138/3', 403, '' );
		}

		Session::i()->csrfCheck();

		Member::loggedIn()->members_bitoptions['pp_setting_moderate_followers'] = ( Request::i()->enabled == 1 ? FALSE : TRUE );
		Member::loggedIn()->save();

		if( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( $this->member->url(), 'follow_saved' );
		}
	}

	/**
	 * Show Profile
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->linkTags['canonical'] = (string) $this->member->url();

		/* Get profile field values */
		try
		{
			$profileFieldValues	= Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $this->member->member_id ) )->first();
		}
		catch ( UnderflowException $e )
		{
			$profileFieldValues = array();
		}
		
		/* Split the fields into sidebar and main fields */
		$mainFields = array();
		$sidebarFields = array();
		if( !empty( $profileFieldValues ) )
		{
			if( Member::loggedIn()->isAdmin() OR Member::loggedIn()->modPermissions() )
			{
				$where = array( "pfd.pf_member_hide='owner' OR pfd.pf_member_hide='staff' OR pfd.pf_member_hide='all'" );
			}
			elseif( Member::loggedIn()->member_id == $this->member->member_id )
			{
				$where = array( "pfd.pf_member_hide='owner' OR pfd.pf_member_hide='all'" );
			}
			else
			{
				$where = array( "pfd.pf_member_hide='all'" );
			}

			foreach( new ActiveRecordIterator( Db::i()->select( 'pfd.*', array('core_pfields_data', 'pfd'), $where, 'pfg.pf_group_order, pfd.pf_position' )->join(
				array('core_pfields_groups', 'pfg'),
				"pfd.pf_group_id=pfg.pf_group_id"
			), 'IPS\core\ProfileFields\Field' ) as $field )
			{
				if( $profileFieldValues[ 'field_' . $field->id ] !== '' AND $profileFieldValues[ 'field_' . $field->id ] !== NULL )
				{
					if( $field->type == 'Editor' )
					{
						$mainFields['core_pfieldgroups_' . $field->group_id]['core_pfield_' . $field->id] = $field->displayValue( $profileFieldValues['field_' . $field->id], FALSE, NULL, Field::PROFILE, $this->member );
					}
					else
					{
						$sidebarFields['core_pfieldgroups_' . $field->group_id]['core_pfield_' . $field->id] = array( 'value' => $field->displayValue( $profileFieldValues['field_' . $field->id], FALSE, NULL, Field::PROFILE, $this->member ), 'custom' => (bool)$field->profile_format );
					}
				}
			}
		}
		
		/* Work out the main content to display */
		/* What tabs are available? */
		$tabs = array( 'activity' => 'users_activity_feed' );

		if ( Member::loggedIn()->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) and Settings::i()->clubs and Club::clubs( Member::loggedIn(), NULL, 'created', $this->member, array(), NULL, TRUE ) )
		{
			$tabs['clubs'] = 'users_clubs';
		}

		foreach( $mainFields as $group => $fields )
		{
			foreach( $fields as $field => $value )
			{
				if ( $value )
				{
					$tabs["field_{$field}"] = $field;
				}
			}
		}
		$nodes = array();
		foreach ( Application::allExtensions( 'core', 'Profile', TRUE, NULL, NULL, FALSE ) as $extension )
		{
			$profileExtension = new $extension( $this->member );
			if ( $profileExtension->showTab() )
			{
				preg_match( '/^IPS\\\(.+?)\\\extensions\\\core\\\Profile\\\(.+?)$/', $extension, $matches );
				$nodes[ "{$matches[1]}_{$matches[2]}" ] = $profileExtension;
				$tabs[ "node_{$matches[1]}_{$matches[2]}" ] = "profile_{$matches[1]}_{$matches[2]}";
			}
		}

		/* What tab are we on? */
		if ( !isset( Request::i()->tab ) or !array_key_exists( Request::i()->tab, $tabs ) )
		{
			$tab = 'activity';
		}
		else
		{
			$tab = Request::i()->tab;
		}

		/* Work out the content */
		$tabContents = '';
		if ( $tab == 'activity' )
		{
			$latestActivity = Query::init()->filterForProfile( $this->member )->setLimit( 15 )->setOrder( Query::ORDER_NEWEST_CREATED )->search();
			$latestActivity->init();

			$extra = array();
			foreach ( array( 'register', 'follow_member', 'follow_content', 'photo', 'votes', 'like', 'rep_neg' ) as $k )
			{
				$key = "all_activity_{$k}";
				if ( Settings::i()->$key )
				{
					$extra[] = $k;
				}
			}
			if ( !empty( $extra ) )
			{
				$latestActivity = $latestActivity->addExtraItems( $extra, $this->member );
			}

			$tabContents = Theme::i()->getTemplate( 'profile' )->profileActivity( $this->member, $latestActivity );
		}
		elseif ( $tab == 'clubs' )
		{
			/* Get All User Clubs */
			$baseUrl = Request::i()->url()->setQueryString('tab', 'clubs');
			$perPage = 24;
			$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;
			if( $page < 1 )
			{
				$page = 1;
			}

			/* Show all clubs this member belongs to if the viewer is a moderator with permissions to access all clubs or if the viewer is on his own profile */
			$extraWhere = ( Member::loggedIn()->member_id === $this->member->member_id OR Member::loggedIn()->modPermission( 'can_access_all_clubs' ) ) ? NULL : "( show_membertab = 'nonmember' OR show_membertab IS NULL )";

			$clubsCount	= Member\Club::clubs( Member::loggedIn(), array( ( $page - 1 ) * $perPage, $perPage ), 'last_activity', $this->member, array(), $extraWhere, TRUE );
			$allClubs	= Member\Club::clubs( Member::loggedIn(), array( ( $page - 1 ) * $perPage, $perPage ), 'last_activity', $this->member, array(), $extraWhere );

			$pagination	= Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $baseUrl, ( ceil( $clubsCount / $perPage ) ), $page, $perPage );

			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/clubs.css', 'core', 'front' ) );
			
			$tabContents = Theme::i()->getTemplate( 'profile' )->profileClubs( $this->member, $allClubs, $pagination );
		}
		elseif ( mb_substr( $tab, 0, 6 ) == 'field_' )
		{
			$fieldId = mb_substr( $tab, 6 );
			foreach( $mainFields as $group => $fields )
			{
				foreach( $fields as $field => $value )
				{
					if ( $field == $fieldId )
					{
						$tabContents = Theme::i()->getTemplate( 'profile' )->fieldTab( $fieldId, $value );
					}
				}
			}
		}
		elseif ( mb_substr( $tab, 0, 5 ) == 'node_' )
		{
			$type = mb_substr( $tab, 5 );
			$tabContents = (string) $nodes[ $type ]->render();
		}

		/* If this is AJAX request to change the tab, just display that */
		if ( Request::i()->isAjax() and isset( Request::i()->tab ) and !isset( Request::i()->entireSection ) )
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $tabContents ) );
		}

		/* Otherwise wrap it in the tabs */
		$mainContent = Theme::i()->getTemplate( 'profile' )->profileTabs( $this->member, $tabs, $tab, $tabContents );

		Output::i()->title = $this->member->name;
		
		/* Log a visit */
		if( Member::loggedIn()->member_id and $this->member->member_id != Member::loggedIn()->member_id and !Session::i()->getAnon() )
		{
			$this->member->addVisitor( Member::loggedIn() );
		}
		
		/* Update views */
		Db::i()->update(
				'core_members',
				"`members_profile_views`=`members_profile_views`+1",
				array( "member_id=?", $this->member->member_id ),
				array(),
				NULL,
				Db::LOW_PRIORITY
		);

		/* Data Layer Stuff */
		if ( Settings::i()->core_datalayer_enabled )
		{
			try
			{
				$groupName = Group::load( $this->member->member_group_id )->formattedName;
			}
			catch ( UnderflowException $e )
			{
				$groupName = null;
			}
			$properties = array(
				'profile_group' => $groupName,
				'profile_group_id'  => $this->member->member_group_id ?: null,
				'profile_id'    => intval( $this->member->member_id ) ?: null,
				'profile_name'  => $this->member->name ?: null,
				'view_location' => 'page',
			);
			DataLayer::i()->addEvent( 'social_view', $properties );
		}
		
		/* Get visitor data */
		$visitors = $this->member->profileVisitors;
		
		/* Get followers */
		$followers = $this->member->followers( ( Member::loggedIn()->isAdmin() OR Member::loggedIn()->member_id === $this->member->member_id ) ? \IPS\Content::FOLLOW_PUBLIC + \IPS\Content::FOLLOW_ANONYMOUS : \IPS\Content::FOLLOW_PUBLIC, array( 'immediate', 'daily', 'weekly', 'none' ), NULL, array( 0, 12 ) );

		/* Get solutions */
		$solutions = Db::i()->select( 'COUNT(*)', 'core_solved_index', array( 'member_id=? AND type=? AND hidden=0', $this->member->member_id, 'solved' ) )->first();
		
		/* Update online location */		
		$module = Module::get( 'core', 'members', 'front' )->permissions();
		Session::i()->setLocation( $this->member->url(), explode( ",", $module['perm_view'] ), 'loc_viewing_profile', array( $this->member->name => FALSE ) );
		
		/* Work out add warning URL */
		$addWarningUrl = Url::internal( "app=core&module=system&controller=warnings&do=warn&id={$this->member->member_id}", 'front', 'warn_add', array( $this->member->members_seo_name ) );
		if ( isset( Request::i()->wr ) )
		{
			$addWarningUrl = $addWarningUrl->setQueryString( 'ref', Request::i()->wr );
		}

		/* Set JSON-LD output */
		Output::i()->jsonLd['profile']	= array(
			'@context'		=> "https://schema.org",
			'@type'			=> "ProfilePage",
			'url'			=> (string) $this->member->url(),
			'name'			=> $this->member->name,
			'mainEntity'=> [
				'@type' => 'Person',
				'name' => $this->member->name,
				'identifier' => $this->member->member_id,
			],
			'primaryImageOfPage'	=> array(
				'@type'					=> "ImageObject",
				'contentUrl'			=> (string) $this->member->get_photo( TRUE, TRUE ),
				'representativeOfPage'	=> true,
				'thumbnail'	=> array(
					'@type'				=> "ImageObject",
					'contentUrl'		=> (string) $this->member->get_photo( TRUE, TRUE ),
				)
			),
			'thumbnailUrl'	=> (string) $this->member->get_photo( TRUE, TRUE ),
			'image'			=> (string) $this->member->get_photo( FALSE, TRUE ),
			'relatedLink'	=> (string) Url::internal( "app=core&module=members&controller=profile&do=content&id={$this->member->member_id}", "front", "profile_content", array( $this->member->members_seo_name ) ),
			'dateCreated'	=> $this->member->joined->format( DateTimeInterface::ATOM ),
			'interactionStatistic'	=> array(
				array(
					"@type"					=> "InteractionCounter",
					"interactionType"		=> "https://schema.org/CommentAction",
					'userInteractionCount'	=> $this->member->member_posts
				),
				array(
					"@type"					=> "InteractionCounter",
					"interactionType"		=> "https://schema.org/ViewAction",
					'userInteractionCount'	=> $this->member->members_profile_views
				),
			),
		);

		/* Output */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_profile.js', 'core' ) );
		Output::i()->output = Theme::i()->getTemplate( 'profile' )->profile( $this->member, $mainContent, $visitors, $sidebarFields, ( is_numeric( $followers ) ? $followers : iterator_to_array( $followers ) ), $addWarningUrl, $solutions );
	}

	/**
	 * Hovercard
	 *
	 * @return	void
	 */
	public function hovercard() : void
	{
		$addWarningUrl = Url::internal( "app=core&module=system&controller=warnings&do=warn&id={$this->member->member_id}", 'front', 'warn_add', array( $this->member->members_seo_name ) );
		if ( isset( Request::i()->wr ) )
		{
			$addWarningUrl = $addWarningUrl->setQueryString( 'ref', Request::i()->wr );
		}
		Output::i()->sendOutput( Theme::i()->getTemplate( 'profile' )->hovercard( $this->member, $addWarningUrl ) );
	}
	
	/**
	 * Show Content
	 *
	 * @return	void
	 */
	public function content(): void
	{
		/* Get the different types */
		$types			= array();
		$hasCallback	= array();

		static::loadProfileCss();

		foreach ( Application::allExtensions( 'core', 'ContentRouter' ) as $router )
		{
			foreach( $router->classes as $class )
			{
				if ( isset( $class::$databaseColumnMap['author'] ) )
				{
					$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ] = $class;
				}
								
				$supportsComments = ( in_array( 'IPS\Content\Item', class_parents( $class ) ) and $class::supportsComments( Member::loggedIn() ) );
				if ( $supportsComments )
				{
					$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$commentClass, 4 ) ) ) ] = $class::$commentClass;
				}
				
				$supportsReviews = ( in_array( 'IPS\Content\Item', class_parents( $class ) ) and $class::supportsReviews( Member::loggedIn() ) );
				if ( $supportsReviews )
				{
					$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$reviewClass, 4 ) ) ) ] = $class::$reviewClass;
				}

				if( method_exists( $router, 'customTableHelper' ) )
				{
					$hasCallback[ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ]					= $router;

					if ( $supportsComments )
					{
						$hasCallback[ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$commentClass, 4 ) ) ) ]	= $router;
					}

					if ( $supportsReviews )
					{
						$hasCallback[ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$reviewClass, 4 ) ) ) ]		= $router;
					}
				}
			}
		}

		/* What type are we looking at? */
		$currentAppModule = NULL;
		$currentType = NULL;
		if ( isset( Request::i()->type ) )
		{
			foreach ( $types as $appModule => $_types )
			{
				if ( array_key_exists( Request::i()->type, $_types ) )
				{
					$currentAppModule = $appModule;
					$currentType = Request::i()->type;
					break;
				}
			}
		}

		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;
		$baseUrl = NULL;
		
		if( $page < 1 )
		{
			$page = 1;
		}
		
		/* Build Output */
		if ( !$currentType )
		{
			$query = Query::init()->filterByAuthor( $this->member )->setOrder( Query::ORDER_NEWEST_CREATED )->setPage( $page );
			$results = $query->search();

			/* If we requested a higher page than is allowed, redirect back to last page */
			$totalResults = $results->count( TRUE );

			if( ceil( $totalResults / $query->resultsToGet ) < $page )
			{
				$highestPage = floor( $totalResults / $query->resultsToGet );

				if ( $highestPage > 0 OR ( $highestPage == 0 AND $page > 1 ) )
				{
					Output::i()->redirect( Request::i()->url()->setPage( 'page', $highestPage ?: 1 ), NULL, 303 );
				}
			}
			
			$baseUrl = Url::internal( "app=core&module=members&controller=profile&id={$this->member->member_id}&do=content", 'front', 'profile_content', $this->member->members_seo_name );
			$pagination = trim( Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $baseUrl->setQueryString( array( 'all_activity' => 1 ) ), ceil( $results->count( TRUE ) / $query->resultsToGet ), $page, $query->resultsToGet ) );
			if ( Request::i()->isAjax() AND Request::i()->all_activity )
			{
				Output::i()->json( array( 'rows' => Theme::i()->getTemplate('profile')->userContentStream( $this->member, $results, $pagination ) ) );
			}
			else
			{
				$output = Theme::i()->getTemplate('profile')->userContentStream( $this->member, $results, $pagination );
			}
		}
		else
		{
			$currentClass = $types[ $currentAppModule ][ $currentType ];
			$currentAppArray = explode( '_', $currentAppModule );
			$currentApp = $currentAppArray[0];
			if( isset( $hasCallback[ $currentType ] ) )
			{
				/* @var array $databaseColumnMap */
				$output	= $hasCallback[ $currentType ]->customTableHelper( $currentClass, Url::internal( "app=core&module=members&controller=profile&id={$this->member->member_id}&do=content", 'front', 'profile_content', $this->member->members_seo_name )->setQueryString( array( 'type' => $currentType ) ), array( array( $currentClass::$databaseTable . '.' . $currentClass::$databasePrefix . $currentClass::$databaseColumnMap['author'] . '=?', $this->member->member_id ) ) );
			}
			else
			{
				/* @var array $databaseColumnMap */
				$where = array();
				$where[] = array( $currentClass::$databaseTable . '.' . $currentClass::$databasePrefix . $currentClass::$databaseColumnMap['author'] . '=?', $this->member->member_id );
				if ( isset( $currentClass::$databaseColumnMap['state'] ) )
				{
					$where[] = array( $currentClass::$databaseTable . '.' . $currentClass::$databasePrefix . $currentClass::$databaseColumnMap['state'] . ' != ?', 'link' );
				}

				if ( isset( $currentClass::$databaseColumnMap['status'] ) )
				{
					$where[] = array( $currentClass::$databaseTable . '.' . $currentClass::$databasePrefix . $currentClass::$databaseColumnMap['status'] . ' != ?', 'draft' );
				}

				if( method_exists( $currentClass, 'commentWhere' ) AND $currentClass::commentWhere() !== NULL )
				{
					$where[] = $currentClass::commentWhere();
				}
				
				$baseUrl = Url::internal( "app=core&module=members&controller=profile&id={$this->member->member_id}&do=content", 'front', 'profile_content', $this->member->members_seo_name );
				$output = new Content( $currentClass, $baseUrl->setQueryString( array( 'type' => $currentType ) ), $where, NULL, Filter::FILTER_AUTOMATIC, 'read', FALSE );
			}

			$output->classes[] = 'cProfileContent ipsData--table';
		}
		
		/* If we've clicked from the tab section */
		if ( Request::i()->isAjax() && Request::i()->change_section && !isset( Request::i()->entireSection ) )
		{
			Output::i()->output = Theme::i()->getTemplate( 'profile' )->userContentSection( $this->member, $types, $currentAppModule, $currentType, (string) $output );
		}
		else
		{
			/* Display */
			$profileTitle	= Member::loggedIn()->language()->addToStack( 'members_content', FALSE, array( 'sprintf' => array( $this->member->name ) ) );
			$title			= ( isset( Request::i()->page ) and Request::i()->page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( $profileTitle, Request::i()->page ) ) ) : $profileTitle;
			
			if ( $baseUrl )
			{
				if ( isset( Request::i()->type ) )
				{
					$baseUrl = $baseUrl->setQueryString( 'type', Request::i()->type );
				}
				
				if ( isset( Request::i()->page ) and Request::i()->page > 1 )
				{
					$baseUrl = $baseUrl->setPage( 'page', Request::i()->page );
				}
				
				Output::i()->linkTags['canonical'] = (string) $baseUrl;
			}
			Output::i()->title = $title;
			Output::i()->output = Theme::i()->getTemplate( 'profile' )->userContent( $this->member, $types, $currentAppModule, $currentType, (string) $output );
		}
	}

	/**
	 * Show badges
	 *
	 * @return	void
	 */
	public function badges() : void
	{
		if( !$this->member->canHaveAchievements() || !Badge::show() )
		{
			Output::i()->error( 'no_module_permission', '2C138/S', 403, '' );
		}

		$percentage = NULL;
		if ( $this->member->achievements_points )
		{
			$percentage = Db::i()->select( "CEIL( 100 * COUNT( IF( achievements_points > " . ( $this->member->achievements_points - 1 ) . ", 1, NULL ) ) / COUNT(*) ) as percentage", 'core_members', ['achievements_points > 0'] )->first();

			if ( $percentage > 51 )
			{
				$percentage = NULL;
			}
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack( 'members_badges', FALSE, array( 'sprintf' => array( $this->member->name ) ) );
		Output::i()->output = Theme::i()->getTemplate( 'profile' )->userBadgeOverview( $this->member, $percentage );
	}
	
	/**
	 * Show Reputation
	 *
	 * @return	void
	 */
	public function reputation() : void
	{
		if ( !Member::loggedIn()->group['gbw_view_reps'] )
		{
			Output::i()->error( 'no_module_permission', '2C138/B', 403, '' );
		}
		
		/* Get the different types */
		$types = array();
		$hasCallback = array();
		static::loadProfileCss();
		foreach ( \IPS\Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			if ( IPS::classUsesTrait( $class, 'IPS\Content\Reactable' ) and !$class::$firstCommentRequired )
			{
				$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ] = $class;
			}
			
			if ( isset( $class::$commentClass ) and IPS::classUsesTrait( $class::$commentClass, 'IPS\Content\Reactable' ) )
			{
				$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$commentClass, 4 ) ) ) ] = $class::$commentClass;
			}
			
			if ( isset( $class::$reviewClass ) and IPS::classUsesTrait( $class::$reviewClass, 'IPS\Content\Reactable' ) )
			{
				$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$reviewClass, 4 ) ) ) ] = $class::$reviewClass;
			}
		}

		if( ! count( $types ) )
		{
			Output::i()->error( 'no_module_permission', '2C138/T', 403, '' );
		}

		/* What type are we looking at? */
		$currentAppModule = NULL;
		$currentType = NULL;
		if ( isset( Request::i()->type ) )
		{
			foreach ( $types as $appModule => $_types )
			{
				if ( array_key_exists( Request::i()->type, $_types ) )
				{
					$currentAppModule = $appModule;
					$currentType = Request::i()->type;
					break;
				}
			}
		}		
		if ( $currentType === NULL )
		{
			foreach ( $types as $appModule => $_types )
			{
				foreach ( $_types as $key => $class )
				{
					$currentAppModule = $appModule;
					$currentType = $key;
					break 2;
				}
			}
		}
		$currentClass = $types[ $currentAppModule ][ $currentType ];
		$currentAppArray = explode( '_', $currentAppModule );
		$currentApp = $currentAppArray[0];
		$member = $this->member;

		/* Build a callback to merge in reputation data */
		$callback = function( $rows ) use ( $member, $currentClass )
		{
			$ids = array();
			$idColumn = $currentClass::$databaseColumnId;
			
			foreach( $rows as $id => $row )
			{
				$ids[ $id ] = $row->$idColumn;
			}

			if ( count( $ids ) )
			{
				$rep = iterator_to_array(
					Db::i()->select( 'core_reputation_index.type_id, core_reputation_index.id AS rep_id, core_reputation_index.rep_date, core_reputation_index.rep_rating, core_reputation_index.member_received as rep_member_received, core_reputation_index.member_id as rep_member, core_reputation_index.reaction as rep_reaction',
					'core_reputation_index',
					array( Db::i()->in( 'core_reputation_index.type_id', array_values( $ids ) ) . " AND ( core_reputation_index.member_id=? OR core_reputation_index.member_received=? ) AND core_reputation_index.app=? AND core_reputation_index.type=?", $member->member_id, $member->member_id, $currentClass::$application, $currentClass::reactionType() ),
					'rep_date desc'
					)->setKeyField('rep_id')
				);
				
				$mapped = array();
				foreach( $rep as $repId => $data )
				{
					$mapped[ $data['type_id' ] ][] = $data;
				}
				
				/* Now overwrite the data */
				foreach( $rows as $id => $row )
				{
					if ( isset( $mapped[ $row->$idColumn ] ) )
					{
						/* Shift it to remove it from the stack as we always sort by desc */
						$useThisRep = array_shift( $mapped[ $row->$idColumn ] );
						
						/* We now want to clone the object so we can make a separate copy, thus not updating the one object used multiple times */
						$row->skipCloneDuplication = TRUE;
						$rows[ $id ] = clone $row;
						
						/* Now overwrite the data in $row */
						foreach( array( 'rep_id', 'rep_date', 'rep_rating', 'rep_member_received', 'rep_member', 'rep_reaction' ) as $field )
						{
							$rows[ $id ]->$field = $useThisRep[ $field ];
						}
					}
				}
			}

			return $rows;
		};
		
		
		/* Build Output */
		$url = Url::internal( "app=core&module=members&controller=profile&id={$this->member->member_id}&do=reputation&type={$currentType}", 'front', 'profile_reputation', array( $this->member->members_seo_name ) );

		$table = new Content( $currentClass, $url, NULL, NULL, Filter::FILTER_AUTOMATIC, 'read', FALSE, FALSE, $callback );
		$table->joinContainer = TRUE;
		$table->sortOptions = array( 'rep_date' );
		$table->sortBy = 'rep_date';
		$table->joins = array(
			array(
				'select' => "core_reputation_index.id AS rep_id, core_reputation_index.rep_date, core_reputation_index.rep_rating, core_reputation_index.member_received as rep_member_received, core_reputation_index.member_id as rep_member, core_reputation_index.reaction as rep_reaction",
				'from'   => 'core_reputation_index',
				'where'  => array( "core_reputation_index.type_id=" . $currentClass::$databaseTable . "." . $currentClass::$databasePrefix . $currentClass::$databaseColumnId  . " AND ( core_reputation_index.member_id=? OR core_reputation_index.member_received=? ) AND core_reputation_index.app=? AND core_reputation_index.type=?", $this->member->member_id, $this->member->member_id, $currentClass::$application, $currentClass::reactionType() ),
				'type'   => 'INNER'
			)
		);
		
		$table->tableTemplate = array( Theme::i()->getTemplate( 'profile', 'core' ), 'userReputationTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'profile', 'core' ), 'userReputationRows' );
		$table->showAdvancedSearch = FALSE;

		/* Display */
		if ( Request::i()->isAjax() && Request::i()->change_section )
		{
			Output::i()->sendOutput( (string) $table );
		}
		else
		{
			/* Get the reputation summary */
			$reactions = array( 'given' => array(), 'received' => array() );

			foreach( Db::i()->select( 'reaction, count(*) as count', 'core_reputation_index', array( 'member_id=?', $this->member->member_id ), NULL, NULL, 'reaction' ) as $reaction )
			{
				try
				{
					$object = Reaction::load( $reaction['reaction'] );

					/* Don't show disabled reactions */
					if( !$object->enabled )
					{
						throw new UnderflowException;
					}

					$reactions['given'][] = array( 'count' => $reaction['count'], 'reaction' => $object );
				}
				catch( UnderflowException $e ){}
			}

			foreach( Db::i()->select( 'reaction, count(*) as count', 'core_reputation_index', array( 'member_received=?', $this->member->member_id ), NULL, NULL, 'reaction' ) as $reaction )
			{
				try
				{
					$object = Reaction::load( $reaction['reaction'] );

					/* Don't show disabled reactions */
					if( !$object->enabled )
					{
						throw new UnderflowException;
					}

					$reactions['received'][] = array( 'count' => $reaction['count'], 'reaction' => $object );
				}
				catch( UnderflowException $e ){}
			}


			Output::i()->title = ( $table->page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'member_reputation_from', FALSE, array('sprintf' => $this->member->name ) ), $table->page ) ) ) : Member::loggedIn()->language()->addToStack( 'member_reputation_from', FALSE, array('sprintf' => $this->member->name ) );

			Output::i()->output = Theme::i()->getTemplate( 'profile' )->userReputation( $this->member, $types, $currentAppModule, $currentType, (string) $table, $reactions );
		}
	}
	
	/**
	 * Toggle Visitors
	 *
	 * @return	void
	 */
	protected function visitors() : void
	{
		if ( !Member::loggedIn()->modPermission('can_modify_profiles') AND ( Member::loggedIn()->member_id !== $this->member->member_id OR !$this->member->group['g_edit_profile'] ) )
		{
			Output::i()->error( 'no_permission_edit_profile', '2C138/3', 403, '' );
		}

		Session::i()->csrfCheck();
		
		if ( Request::i()->state == 0 )
		{
			$this->member->members_bitoptions['pp_setting_count_visitors']	= FALSE;
			$visitors = array();
		}
		else
		{
			$this->member->members_bitoptions['pp_setting_count_visitors']	= TRUE;

			$visitors = $this->member->profileVisitors;
		}

		$this->member->save();

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = Theme::i()->getTemplate( 'profile', 'core' )->recentVisitorsBlock( $this->member, $visitors );
		}
		else
		{
			Output::i()->redirect( $this->member->url(), 'saved' );
		}
	}
	
	/**
	 * Edit Profile
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		/* Do we have permission? */
		if ( !Member::loggedIn()->modPermission('can_modify_profiles') and ( Member::loggedIn()->member_id !== $this->member->member_id or !$this->member->group['g_edit_profile'] ) )
		{
			Output::i()->error( 'no_permission_edit_profile', '2S147/1', 403, '' );
		}
		
		$form = $this->buildEditForm();
		
		/* Handle the submission */
		if ( $values = $form->values() )
		{
			$this->_saveMember( $form, $values );

			/* Data Layer Stuff */
			if ( Settings::i()->core_datalayer_enabled )
			{
				try
				{
					$groupName = Group::load( $this->member->member_group_id )->formattedName;
				}
				catch ( UnderflowException $e )
				{
					$groupName = null;
				}
				$properties = array(
					'profile_group'    => $groupName,
					'profile_group_id' => $this->member->member_group_id ?: null,
					'profile_id'       => intval( $this->member->member_id ) ?: null,
					'profile_name'     => $this->member->name ?: null,
				);
				DataLayer::i()->addEvent( 'social_update', $properties );
			}

			Output::i()->redirect( $this->member->url() );
		}
		
		/* Set Session Location */
		Session::i()->setLocation( $this->member->url(), array(), 'loc_editing_profile', array( $this->member->name => FALSE ) );

		if( !count( $form->elements ) )
		{
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->genericBlock( Member::loggedIn()->language()->addToStack( 'profile_nothing_to_edit' ), NULL, 'i-padding_3' );
		}
		else if ( Request::i()->isAjax() )
		{
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->editContentForm( Member::loggedIn()->language()->addToStack( 'profile_edit' ), $form );
		}
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'editing_profile', FALSE, array( 'sprintf' => array( $this->member->name ) ) );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'editing_profile', FALSE, array( 'sprintf' => array( $this->member->name ) ) ) );
	}


	/**
	 * Build Edit Form
	 *
	 * @return Form
	 */
	protected function buildEditForm() : Form
	{
		/* Build the form */
		$form = new Form;

		/* The basics */
		$form->addTab( 'profile_edit_basic_tab', 'user');

		$canChangeBirthday = ( Settings::i()->profile_birthday_type !== 'none' );
		if( $canChangeBirthday )
		{
			$form->addHeader( 'profile_edit_basic_header' );
		}

		if ( Settings::i()->profile_birthday_type !== 'none' )
		{
			$form->add( new Custom( 'bday', array( 'year' => $this->member->bday_year, 'month' => $this->member->bday_month, 'day' => $this->member->bday_day ), FALSE, array( 'getHtml' => function( $element )
			{
				return strtr( Member::loggedIn()->language()->preferredDateFormat(), array(
					'DD'	=> Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_day( $element->name, $element->value, $element->error ),
					'MM'	=> Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_month( $element->name, $element->value, $element->error ),
					'YY'	=> Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_year( $element->name, $element->value, $element->error ),
					'YYYY'	=> Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_year( $element->name, $element->value, $element->error ),
				) );
			} ), function( $val )
			{
				$date = $val['day'];
				$month = $val['month'];
				$year = $val['year'];

				try
				{
					if( ( $month AND !$date ) OR ( !$month AND $date ) )
					{
						throw new UnexpectedValueException;
					}

					new DateTime( $year . "-" . $month . "-" . $date );
				}
				catch ( Exception $e )
				{
					throw new InvalidArgumentException( 'invalid_bdate') ;
				}
			}
			) );
			if ( Settings::i()->profile_birthday_type == 'private' )
			{
				$form->addMessage( 'profile_birthday_display_private', 'ipsMessage ipsMessage_info' );
			}
		}

		/* Profile fields */
		try
		{
			$values = Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $this->member->member_id ) )->first();
		}
		catch( UnderflowException $e )
		{
			$values	= array();
		}

		foreach ( Field::fields( $values, Field::EDIT, $this->member ) as $group => $fields )
		{
			$form->addHeader( "core_pfieldgroups_{$group}" );
			foreach ( $fields as $field )
			{
				$form->add( $field );
			}
		}

		/* Moderator stuff */
		if ( ( Member::loggedIn()->modPermission('can_modify_profiles') OR Member::loggedIn()->modPermission('can_unban') ) AND Member::loggedIn()->member_id != $this->member->member_id )
		{
			$sigLimits = explode( ":", $this->member->group['g_signature_limits'] );
			if( Settings::i()->signatures_enabled AND !$sigLimits[0] AND $sigLimits[5] != 0 )
			{
				$form->add( new Editor( 'signature',  $this->member->signature, FALSE, array( 'app' => 'core', 'key' => 'Signatures', 'autoSaveKey' => "frontsig-" . $this->member->member_id, 'attachIds' => array(  $this->member->member_id ) ) ) );
			}

			if ( Member::loggedIn()->modPermission('can_unban') )
			{
				$form->addTab( 'profile_edit_moderation', 'times' );

				if ( $this->member->mod_posts !== 0 )
				{
					$form->add( new YesNo( 'remove_mod_posts', NULL, FALSE ) );
				}

				if ( $this->member->restrict_post !== 0 )
				{
					$form->add( new YesNo( 'remove_restrict_post', NULL, FALSE ) );
				}

				if ( $this->member->temp_ban !== 0 )
				{
					$form->add( new YesNo( 'remove_ban', NULL, FALSE ) );
				}
			}
		}

		return $form;
	}

	/**
	 * Save Member
	 *
	 * @param Form $form
	 * @param array $values
	 * @return void
	 */
	protected function _saveMember( Form $form, array $values ) : void
	{
		if( isset( $values['bday'] ) )
		{
			if( $values['bday']  and ( ( $values['bday']['day'] and !$values['bday']['month'] ) or ( $values['bday']['month'] and !$values['bday']['day'] ) ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack( 'bday_month_and_day_required' );
				Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->editContentForm( Member::loggedIn()->language()->addToStack( 'profile_edit' ), $form );
				return;
			}

			if ( $values['bday'] and $values['bday']['day'] and $values['bday']['month'] )
			{
				$this->member->bday_day		= $values['bday']['day'];
				$this->member->bday_month	= $values['bday']['month'];
				$this->member->bday_year	= $values['bday']['year'];
			}
			else
			{
				$this->member->bday_day = NULL;
				$this->member->bday_month = NULL;
				$this->member->bday_year = NULL;
			}
		}

		/* Profile Fields */
		try
		{
			$profileFields = Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $this->member->member_id ) )->first();
		}
		catch( UnderflowException $e )
		{
			$profileFields = array();
		}

		/* If the row only contains one column (eg. member_id) then the result of the query is a string, we do not want this */
		if ( !is_array( $profileFields ) )
		{
			$profileFields = array();
		}
		
		$profileFields['member_id'] = $this->member->member_id;

		foreach ( Field::fields( $profileFields, Field::EDIT, $this->member ) as $group => $fields )
		{
			foreach ( $fields as $id => $field )
			{
				if ( $field instanceof Upload )
				{
					$profileFields[ "field_{$id}" ] = (string) $values[ $field->name ];
				}
				else
				{
					$profileFields[ "field_{$id}" ] = $field::stringValue( $values[ $field->name ] );
				}

				if ( $field instanceof Editor )
				{
					Field::load( $id )->claimAttachments( $this->member->member_id );
				}
			}

			$this->member->changedCustomFields = $profileFields;
		}

		/* Moderator stuff */
		if ( Member::loggedIn()->modPermission('can_modify_profiles') AND Member::loggedIn()->member_id != $this->member->member_id)
		{
			if ( isset( $values['remove_mod_posts'] ) AND $values['remove_mod_posts'] )
			{
				$this->member->mod_posts = 0;
			}

			if ( isset( $values['remove_restrict_post'] ) AND $values['remove_restrict_post'] )
			{
				$this->member->restrict_post = 0;
			}

			if ( isset( $values['remove_ban'] ) AND $values['remove_ban'] )
			{
				$this->member->temp_ban = 0;
			}

			if ( isset( $values['signature'] ) )
			{
				$this->member->signature = $values['signature'];
			}
		}
		
		/* Reset Profile Complete flag in case this was an optional step */
		$this->member->members_bitoptions['profile_completed'] = FALSE;

		/* Save */
		Db::i()->replace( 'core_pfields_content', $profileFields );
		$this->member->save();
	}

	/**
	 * Edit Photo
	 *
	 * @return	void
	 */
	protected function editPhoto() : void
	{
		if ( !Member::loggedIn()->modPermission('can_modify_profiles') and ( Member::loggedIn()->member_id !== $this->member->member_id or !$this->member->group['g_edit_profile'] ) )
		{
			Output::i()->error( 'no_permission_edit_profile', '2C138/9', 403, '' );
		}

		$photoVars = explode( ':', $this->member->group['g_photo_max_vars'] );

		/* Init */
		$form = new Form( 'profile_photo', 'continue' );
		$form->ajaxOutput = TRUE;
		$toggles = array( 'custom' => array( 'member_photo_upload' ) );

		$options = array();
		$defaultType =  ( $this->member->pp_photo_type == 'letter' ) ? 'none' : $this->member->pp_photo_type;
		
		/* Can we upload? */
		if ( $photoVars[0] )
		{
			$options['custom'] = 'member_photo_upload';
		}
		
		/* Can we use gallery images? */
		if ( Application::appIsEnabled('gallery') AND $this->member->pp_photo_type == 'gallery_Images' )
		{
			$options['gallery_Images'] = 'member_gallery_image';
		}
		
		/* And of course we can always not have a photo... except when we can't */
		$photoRequired = FALSE;
		foreach( ProfileStep::loadAll() AS $step )
		{
			if ( $step->completion_act === 'photo' AND $step->required )
			{
				$photoRequired = TRUE;
				break;
			}
		}
		if ( $photoRequired === FALSE )
		{
			if ( $this->member->pp_photo_type != 'none' )
			{
				$options['none'] = 'member_photo_remove';
			}
			else
			{
				$options['none'] = 'member_photo_none';
			}
		}
		
		/* iOS doesn't like upload forms being hidden by a toggle; and it makes sense if we do not have a profile photo to show the upload as the selected option */
		if ( $defaultType == 'none' and $photoVars[0] )
		{
			$defaultType = 'custom';
		}
		
		/* Create that selection */
		if( count( $options ) > 1 )
		{
			$form->add( new Radio( 'pp_photo_type', $defaultType, TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );
		}
		else
		{
			$form->hiddenValues['pp_photo_type']  = $defaultType;
		}
		
		/* Create the upload field */		
		if ( $photoVars[0] )
		{
			$form->add( new Upload( 'member_photo_upload', ( $this->member->pp_main_photo and $this->member->pp_photo_type == 'custom') ? File::get( 'core_Profile', $this->member->pp_main_photo ) : NULL, FALSE, array( 'supportsDelete' => FALSE, 'image' => array( 'maxWidth' => $photoVars[1], 'maxHeight' => $photoVars[2] ), 'allowStockPhotos' => TRUE, 'storageExtension' => 'core_Profile', 'maxFileSize' => $photoVars[0] / 1024 ), function( $val ) {
				if( Request::i()->pp_photo_type == 'custom' AND !$val )
				{
					throw new DomainException('form_required');
				}

				if ( $val instanceof File )
				{
					try
					{
						$image = Image::create( $val->contents() );
						if( $image->isAnimatedGif and !$this->member->group['g_upload_animated_photos'] )
						{
							throw new DomainException( 'member_photo_upload_no_animated' );
						}
					} catch ( FileException $e ){}

				}
			}, NULL, NULL, 'member_photo_upload' ) );
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Disable syncing */
			$profileSync = $this->member->profilesync;
			if ( isset( $profileSync['photo'] ) )
			{
				unset( $profileSync['photo'] );
				$this->member->profilesync = $profileSync;
			}

			switch ( $values['pp_photo_type'] )
			{
				case 'custom':
					if ( $photoVars[0] and $values['member_photo_upload'] )
					{

						if ( (string) $values['member_photo_upload'] !== '' and $this->member->pp_main_photo !== (string) $values['member_photo_upload'] )
						{
							$this->member->pp_photo_type  = 'custom';
							$this->member->pp_main_photo  = (string) $values['member_photo_upload'];
							
							$thumbnail = $values['member_photo_upload']->thumbnail( 'core_Profile', PHOTO_THUMBNAIL_SIZE, PHOTO_THUMBNAIL_SIZE, TRUE );
							$this->member->pp_thumb_photo = (string) $thumbnail;
							
							$this->member->photo_last_update = time();
						}
					}
					break;

				case 'none':
					$this->member->pp_photo_type								= NULL;
					$this->member->pp_main_photo								= NULL;
					$this->member->photo_last_update = NULL;
					break;
			}
			
			/* Reset Profile Complete flag in case this was an optional step */
			$this->member->members_bitoptions['profile_completed'] = FALSE;
							
			$this->member->save();
			
			if ( $this->member->pp_photo_type )
			{
				$this->member->logHistory( 'core', 'photo', array( 'action' => 'new', 'type' => $this->member->pp_photo_type ) );
			}
			else
			{
				$this->member->logHistory( 'core', 'photo', array( 'action' => 'remove' ) );
			}
			
			if ( $this->member->pp_photo_type == 'custom' )
			{
				if ( Request::i()->isAjax() )
				{					
					$this->cropPhoto();
					return;
				}
				else
				{
					Output::i()->redirect( $this->member->url()->setQueryString( 'do', 'cropPhoto' ) );
				}
			}
			else
			{
				Output::i()->redirect( $this->member->url() );
			}
		}
		
		/* Display */
		Session::i()->setLocation( $this->member->url(), array(), 'loc_editing_profile', array( $this->member->name => FALSE ) );
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'editing_profile', FALSE, array( 'sprintf' => array( $this->member->name ) ) );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'editing_profile', FALSE, array( 'sprintf' => array( $this->member->name ) ) ) );
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
		Output::i()->bypassCsrfKeyCheck = TRUE;
		$original = File::get( 'core_Profile', $this->member->pp_main_photo );
		$headers = array( "Content-Disposition" => Output::getContentDisposition( 'inline', $original->filename ) );
		Output::i()->sendOutput( $original->contents(), 200, File::getMimeType( $original->filename ), $headers );
	}
	
	/**
	 * Crop Photo
	 *
	 * @return	void
	 */
	protected function cropPhoto() : void
	{
		if ( !Member::loggedIn()->modPermission('can_modify_profiles') and ( Member::loggedIn()->member_id !== $this->member->member_id or !$this->member->group['g_edit_profile'] ) )
		{
			Output::i()->error( 'no_permission_edit_profile', '2C138/F', 403, '' );
		}
		
		if( !$this->member->pp_main_photo )
		{
			Output::i()->error( 'no_photo_to_crop', '2C138/C', 404, '' );
		}

		/* Get the photo */
		try
		{
			$original = File::get( 'core_Profile', $this->member->pp_main_photo );
			$image = Image::create( $original->contents() );
		}
		catch( Exception $e )
		{
			Log::log( $e, 'crop_error' );

			Output::i()->error( 'no_photo_to_crop', '3C138/N', 404, '' );
		}
		
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
		$form = new Form( 'photo_crop', 'save', $this->member->url()->setQueryString( 'do', 'cropPhoto' ) );
		$form->class = 'ipsForm--noLabels';
		$member = $this->member;
		$form->add( new Custom('photo_crop', array( 0, 0, $suggestedWidth, $suggestedHeight ), FALSE, array(
			'getHtml'	=> function( $field ) use ( $original, $member )
			{
				return Theme::i()->getTemplate('members', 'core', 'global')->photoCrop( $field->name, $field->value, $member->url()->setQueryString( 'do', 'cropPhotoGetPhoto' )->csrf() );
			}
		) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			try
			{
				/* Create new file */
				$image->cropToPoints( $values['photo_crop'][0], $values['photo_crop'][1], $values['photo_crop'][2], $values['photo_crop'][3] );
				
				/* Delete the current thumbnail */					
				if ( $this->member->pp_thumb_photo )
				{
					try
					{
						File::get( 'core_Profile', $this->member->pp_thumb_photo )->delete();
					}
					catch ( Exception $e ) { }
				}
								
				/* Save the new */
				$cropped = File::create( 'core_Profile', $original->originalFilename, (string) $image );
				$this->member->pp_thumb_photo = (string) $cropped->thumbnail( 'core_Profile', PHOTO_THUMBNAIL_SIZE, PHOTO_THUMBNAIL_SIZE );
				$this->member->save();

				/* Delete the temporary full size cropped image */
				$cropped->delete();

				/* Edited member, so clear widget caches (stats, widgets that contain photos, names and so on) */
				Widget::deleteCaches();
								
				/* Redirect */
				Output::i()->redirect( $this->member->url() );
			}
			catch ( Exception $e )
			{
				$form->error = Member::loggedIn()->language()->addToStack('photo_crop_bad');
			}
		}
		
		/* Display */
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Followers
	 *
	 * @return	void
	 */
	protected function followers() : void
	{
		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		$limit		= array( ( $page - 1 ) * 50, 50 );
		$followers	= $this->member->followers( ( Member::loggedIn()->isAdmin() OR Member::loggedIn()->member_id === $this->member->member_id ) ? \IPS\Content::FOLLOW_PUBLIC + \IPS\Content::FOLLOW_ANONYMOUS : \IPS\Content::FOLLOW_PUBLIC, array( 'immediate', 'daily', 'weekly', 'none' ), NULL, $limit, 'name' );
		$followersCount = $this->member->followersCount( ( Member::loggedIn()->isAdmin() OR Member::loggedIn()->member_id === $this->member->member_id ) ? \IPS\Content::FOLLOW_PUBLIC + \IPS\Content::FOLLOW_ANONYMOUS : \IPS\Content::FOLLOW_PUBLIC, array( 'immediate', 'daily', 'weekly', 'none' ) );
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'profile' )->followers( $this->member, $followers ) );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('members_followers', FALSE, array( 'sprintf' => array( $this->member->name ) ) );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('members_followers', FALSE, array( 'sprintf' => array( $this->member->name ) ) ) );
		Output::i()->output = Theme::i()->getTemplate( 'profile' )->allFollowers( $this->member, $followers, $followersCount );
	}
	
	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return	string
	 */
	protected function _coverPhotoStorageExtension(): string
	{
		return 'core_Profile';
	}
	
	/**
	 * Set Cover Photo
	 *
	 * @param	CoverPhoto	$photo	New Photo
	 * @param	string|null					$type	'new', 'remove', 'reposition'
	 * @return	void
	 */
	protected function _coverPhotoSet( CoverPhoto $photo, ?string $type=NULL ) : void
	{
		/* Disable syncing */
		$profileSync = $this->member->profilesync;
		if ( isset( $profileSync['cover'] ) )
		{
			unset( $profileSync['cover'] );
			$this->member->profilesync = $profileSync;
		}
		
		$this->member->pp_cover_photo = (string) $photo->file;
		$this->member->pp_cover_offset = $photo->offset;
		
		/* Reset Profile Complete flag in case this was an optional step */
		$this->member->members_bitoptions['profile_completed'] = FALSE;
	
		$this->member->save();
		if ( $type != 'reposition' )
		{
			$this->member->logHistory( 'core', 'coverphoto', array( 'action' => $type ) );
		}
	}

	/**
	 * Get Name History
	 */
	protected function namehistory() : void
	{
		if ( !Member::loggedIn()->group['g_view_displaynamehistory'] )
		{
			Output::i()->error( 'no_module_permission', '1C138/G', 403, '' );
		}

		$table = new \IPS\Helpers\Table\Db( 'core_member_history', $this->member->url()->setQueryString( 'do', 'namehistory' ), array( 'log_member=? AND log_app=? AND log_type=?', $this->member->member_id, 'core', 'display_name' ) );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'members', 'core', 'global' ), 'nameHistoryTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'members', 'core', 'global' ), 'nameHistoryRows' );
		$table->sortBy = 'log_date';
		$table->sortDirection = 'desc';

		$table->parsers = array(
			'log_data'	=> function( $val )
			{
				return json_decode( $val, TRUE );
			}
		);

		Output::i()->title = Member::loggedIn()->language()->addtoStack( 'members_dname_history', FALSE, array( 'sprintf' => ( $this->member->name ) ) );
		Output::i()->output = $table;

	}
	
	/**
	 * Get Cover Photo
	 *
	 * @return	CoverPhoto
	 */
	protected function _coverPhotoGet(): CoverPhoto
	{
		return $this->member->coverPhoto();
	}

	/**
	 * Show Solutions
	 *
	 * @return	void
	 */
	public function solutions() : void
	{
		/* Get the different types */
		$types = array();
		$hasCallback = array();
		static::loadProfileCss();
		foreach ( \IPS\Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			if ( IPS::classUsesTrait( $class, 'IPS\Content\Solvable' ) )
			{
				$types[ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ] = $class::$commentClass;
			}
		}

		/* What type are we looking at? */
		$currentType = NULL;
		if ( isset( Request::i()->type ) AND array_key_exists( Request::i()->type, $types ) )
		{
			$currentType = Request::i()->type;
		}

		if ( $currentType === NULL )
		{
			foreach ( $types as $key => $type )
			{
				$currentType = $key;
				break;
			}
		}

		$currentClass = $types[ $currentType ];

		/* Build Output */
		$url = Url::internal( "app=core&module=members&controller=profile&id={$this->member->member_id}&do=solutions&type={$currentType}", 'front', 'profile_solutions', array( $this->member->members_seo_name ) );

		$table = new Content( $currentClass, $url, NULL, NULL, Filter::FILTER_AUTOMATIC, 'read', FALSE );
		$table->joinContainer = TRUE;
		$table->sortOptions = array( 'solved_date' );
		$table->sortBy = 'solved_date';
		$table->joins = array(
			array(
				'select' => "core_solved_index.id AS solved_id, core_solved_index.solved_date",
				'from'   => 'core_solved_index',
				'where'  => array( "core_solved_index.comment_id=" . $currentClass::$databaseTable . "." . $currentClass::$databasePrefix . $currentClass::$databaseColumnId  . " AND core_solved_index.member_id=? AND core_solved_index.comment_class=? AND type=?", $this->member->member_id, $currentClass, 'solved' ),
				'type'   => 'INNER'
			)
		);
		
		$table->tableTemplate = array( Theme::i()->getTemplate( 'profile', 'core' ), 'userSolutionsTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'profile', 'core' ), 'userSolutionsRows' );
		$table->showAdvancedSearch = FALSE;

		/* Display */
		if ( Request::i()->isAjax() && Request::i()->change_section )
		{
			Output::i()->sendOutput( (string) $table );
		}
		else
		{
			Output::i()->title = ( $table->page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'member_solutions', FALSE, array('sprintf' => $this->member->name ) ), $table->page ) ) ) : Member::loggedIn()->language()->addToStack( 'member_solutions', FALSE, array('sprintf' => $this->member->name ) );

			Output::i()->output = Theme::i()->getTemplate( 'profile' )->userSolutions( $this->member, $types, $currentType, (string) $table, $this->member->solutionCount() );
		}
	}

	/**
	 * Recognize
	 *
	 * @return void
	 */
	protected function recognize() : void
	{		
		$class = Request::i()->content_class;
		$id = Request::i()->content_id;

		/* Does this content exist? */
		try
		{
			$content = $class::loadAndCheckPerms( $id );

			/* Can we view this item? */
			if ( ! $content->canView() )
			{
				Output::i()->error( 'node_error', '2C138/O', 403, '' );
			}

			/* Make sure there's no shenanigans */
			if ( $this->member->member_id != $content->author()->member_id )
			{
				Output::i()->error( 'node_error', '2C138/P', 403, '' );
			}

			/* Can we recognize this ? */
			if ( ! $content->canRecognize() )
			{
				Output::i()->error( 'node_error', '2C138/Q', 403, '' );
			}

			/* Now build the form */
			$form = new Form( 'badge_form', 'save' );
			$form->class = 'ipsForm--vertical ipsForm--recognize-member ipsForm_fullWidth';

			/* What has been awarded so far today? */
			$message = NULL;
			if ( $this->member->todaysRecognizePoints() and $this->member->todaysRecognizeBadges() )
			{
				$message = Member::loggedIn()->language()->addToStack( 'recognize_points_so_far_both', FALSE, [
					'sprintf' => [
						$this->member->name,
						Member::loggedIn()->language()->addToStack( 'recognize_points_pluralize', FALSE, [ 'sprintf' => [ $this->member->todaysRecognizePoints() ], 'pluralize' => [ $this->member->todaysRecognizePoints() ] ]),
						Member::loggedIn()->language()->addToStack( 'recognize_badges_pluralize', FALSE, [ 'sprintf' => [ $this->member->todaysRecognizeBadges() ], 'pluralize' => [ $this->member->todaysRecognizeBadges() ] ]),
					] ] );
			}
			else if ( $this->member->todaysRecognizePoints() )
			{
				$message = Member::loggedIn()->language()->addToStack( 'recognize_points_so_far_single', FALSE, [
					'sprintf' => [
						$this->member->name,
						Member::loggedIn()->language()->addToStack( 'recognize_points_pluralize', FALSE, [ 'sprintf' => [ $this->member->todaysRecognizePoints() ], 'pluralize' => [ $this->member->todaysRecognizePoints() ] ])
					] ] );
			}
			else if ( $this->member->todaysRecognizeBadges() )
			{
				$message = Member::loggedIn()->language()->addToStack( 'recognize_points_so_far_single', FALSE, [
					'sprintf' => [
						$this->member->name,
						Member::loggedIn()->language()->addToStack( 'recognize_points_pluralize', FALSE, [ 'sprintf' => [ $this->member->todaysRecognizeBadges() ], 'pluralize' => [ $this->member->todaysRecognizeBadges() ] ])
					] ] );
			}

			$maxPoints = NULL;
			$disabled = FALSE;
			if ( Settings::i()->achievements_recognize_max_per_user_day != -1 )
			{
				$maxPoints = Settings::i()->achievements_recognize_max_per_user_day - $this->member->todaysRecognizePoints();
				if ( $maxPoints > 0 )
				{
					$message .= Member::loggedIn()->language()->addToStack( 'recognize_points_so_far_limit', FALSE, ['sprintf' => [$this->member->name, $maxPoints]] );
				}
				else
				{
					$message .= Member::loggedIn()->language()->addToStack( 'recognize_points_so_far_limit_none', FALSE, ['sprintf' => [$this->member->name]] );
					$disabled = TRUE;
				}

				if ( Member::loggedIn()->modPermission('can_recognize_content_no_point_limit') )
				{
					$message .= Member::loggedIn()->language()->addToStack('recognize_points_so_far_limit_but_none');
					$maxPoints = NULL;
					$disabled = FALSE;
				}
			}

			if ( $message )
			{
				$form->addMessage( $message, 'ipsMessage ipsMessage_info' );
			}

			if ( Member::loggedIn()->modPermission('can_recognize_content') == '*' OR in_array( 'badges', Member::loggedIn()->modPermission('can_recognize_content_options') ) )
			{
				$form->add( new Node( 'recognize_badge', NULL, FALSE, [
					'class' => '\IPS\core\Achievements\Badge',
					'where' => [
						[ 'manually_awarded=?', 1 ]
					],
					'url' => $content->author()->url()->setQueryString( array('do' => 'recognize', 'content_class' => $class, 'content_id' => $id) )
				] ) );
			}

			if ( Member::loggedIn()->modPermission('can_recognize_content') == '*' OR in_array( 'points', Member::loggedIn()->modPermission('can_recognize_content_options') ) )
			{
				$form->add( new Number( 'recognize_points', 0, FALSE, [ 'disabled' => $disabled, 'min' => 0, 'max' => $maxPoints ] ) );
			}

			$form->add( new Text( 'recognize_message', NULL, FALSE ) );
			$form->add( new YesNo( 'recognize_public', TRUE, FALSE ) );

			Member::loggedIn()->language()->words['recognize_message_desc'] = Member::loggedIn()->language()->addToStack( 'recognize_message__desc', FALSE, [ 'sprintf' => [ $content->author()->name ] ] );
			if ( $values = $form->values() )
			{
				if ( ! $values['recognize_badge'] and ! $values['recognize_points'] )
				{
					$form->error = Member::loggedIn()->language()->addToStack('recognize_form_empty');
				}
				else
				{
					Recognize::add( $content, $this->member, $values['recognize_points'], ( $values['recognize_badge'] ?: null ), $values['recognize_message'], Member::loggedIn(), $values['recognize_public'] );

					if ( Request::i()->isAjax() )
					{
						Output::i()->sendOutput( Member::loggedIn()->language()->addToStack( 'recognize_submit_success' ) );
					}
					else
					{
						Output::i()->redirect( $content->url(), 'recognize_submit_success' );
					}
				}
			}

			Output::i()->title = Member::loggedIn()->language()->addToStack( 'recognize_author', FALSE, [ 'sprintf' => [ $content->author()->name ] ] );
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		catch( Exception $e )
		{
			Output::i()->error( 'node_error', '2C138/R', 403, '' );
		}
	}

	/**
	 * Recognize
	 *
	 * @return void
	 */
	protected function unrecognize() : void
	{
		$class = Request::i()->content_class;
		$id = Request::i()->content_id;

		/* Does this content exist? */
		try
		{
			$content = $class::loadAndCheckPerms( $id );

			/* Can we view this item? */
			if ( ! $content->canView() )
			{
				Output::i()->error( 'node_error', '2C138/S', 403, '' );
			}

			/* Can we recognize this ? */
			if ( ! $content->canRemoveRecognize() )
			{
				Output::i()->error( 'node_error', '2C138/T', 403, '' );
			}

			$content->removeRecognize();
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2C138/Ug', 403, '' );
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( Member::loggedIn()->language()->addToStack( 'recognize_removed_success' ) );
		}
		else
		{
			Output::i()->redirect( $content->url(), 'recognize_removed_success' );
		}
	}


	/**
	 * Should the member profile be indexed?
	 *
	 * @return bool
	 */
	protected function shouldBeIndexed(): bool
	{
		/* Don't index new empty profiles */
		if( ! $this->member->member_posts )
		{
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Load all profile.css files that are present in any app
	 *
	 * @return void
	 */
	protected static function loadProfileCss() : void
	{
		$apps = iterator_to_array(
			Db::i()->select( 'css_app', 'core_theme_css', [ 'css_name=? and css_location=?', 'profile.css', 'front' ] )
		);

		foreach ( \IPS\Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			if( in_array( $class::$application, $apps ) )
			{
				Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'profile.css', $class::$application ) );
			}
		}
	}
}