<?php
/**
 * @brief		Announcement
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		09 Oct 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Controller;
use IPS\core\Announcements\Announcement as AnnouncementClass;
use IPS\Http\Url;
use IPS\Http\Url\Exception;
use IPS\Http\Url\Friendly;
use IPS\Http\Url\Internal;
use IPS\Member;
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
 * Announcement
 */
class announcement extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\core\Announcements\Announcement';
	
	/**
	 * View Announcement
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		parent::manage();
		
		/* Load announcement */
		try
		{
			$announcement = AnnouncementClass::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'announcement_missing', '2C199/1', 404, '' );
		}
		if ( !$announcement->canView() )
		{
			Output::i()->error( 'node_error_no_perm', '2C199/2', 403, '' );
		}
		
		/* Display */
		$announcementHtml = Theme::i()->getTemplate( 'system' )->announcement( $announcement );
		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $announcementHtml );
		}
		else
		{
			/* if the site is offline, use the minimal layout */
			if ( ( !Settings::i()->site_online and !Member::loggedIn()->group['g_access_offline'] ) OR ( !Member::loggedIn()->member_id AND !Member::loggedIn()->group['g_view_board'] ) )
			{
				Output::i()->bodyClasses[] = 'ipsLayout_minimal';
				Output::i()->allowDefaultWidgets = FALSE;
				Output::i()->sidebar['enabled'] = FALSE;
			}
			
			/* Set Session Location */
			Session::i()->setLocation( Url::internal( 'app=core&module=system&controller=announcement&id=' . $announcement->id, NULL, 'announcement', $announcement->seo_title  ), array(), 'loc_viewing_announcement', array( $announcement->title => FALSE ) );
			
			/* Display */
			Output::i()->title = Member::loggedIn()->language()->addToStack( $announcement->title );
			Output::i()->output = $announcementHtml;
		}
		return null;
	}

	/**
	 * Check permissions for linked content item
	 *
	 * @return	void
	 */
	protected function permissionCheck() : void
	{
		if ( !Member::loggedIn()->modPermission( 'can_manage_announcements' ) )
		{
			Output::i()->error( 'no_module_permission', '2C185/1', 403, '' );
		}

		try
		{
			$url = Url::createFromString( Request::i()->url );
		}
		catch( Exception $e )
		{
			Output::i()->json( array( 'status' => 'unexpected_format' ) );
		}

		/* Make sure this is a local URL */
		if( !( $url instanceof Internal ) )
		{
			Output::i()->json( array( 'status' => 'not_local' ) );
		}

		/* Get the definition */
		$furlDefinition = Friendly::furlDefinition();

		/* If we don't have a validate callback, we can return NULL */
		if ( !isset( $furlDefinition[ $url->seoTemplate ]['verify'] ) or !$furlDefinition[ $url->seoTemplate ]['verify'] )
		{
			Output::i()->json( array( 'status' => 'not_verified' ) );
		}

		$class = $furlDefinition[ $url->seoTemplate ]['verify'];
		$item = $class::loadFromUrl( $url );

		/* If the class does not have our method, return a not_verified status */
		if( !method_exists( $item, 'cannotViewGroups' ) )
		{
			Output::i()->json( array( 'status' => 'not_verified' ) );
		}

		/* Get groups that cannot view the item */
		$groups = $item->cannotViewGroups();

		if( !$groups )
		{
			Output::i()->json( array( 'status' => 'all_permissions' ) );
		}

		Output::i()->json( array( 'html' => Theme::i()->getTemplate( 'modcp', 'core', 'front' )->announcementGroupCheck( $groups ) ) );
	}
}