<?php
/**
 * @brief		Moderator Control Panel Extension: Announcements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Oct 2013
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Announcements\Announcement;
use IPS\Extensions\ModCpAbstract;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Widget;
use OutOfRangeException;
use function defined;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Announcements
 */
class Announcements extends ModCpAbstract
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return string|null
	 */
	public function getTab() : ?string
	{
		/* Check Permissions */
		if ( ! Member::loggedIn()->modPermission('can_manage_announcements') )
		{
			return null;
		}
		
		return 'announcements';
	}
	
	/**
	 * Manage Announcements
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Check Permissions */
		if ( ! Member::loggedIn()->modPermission('can_manage_announcements') )
		{
			Output::i()->error( 'no_module_permission', '3S148/2', 403, '' );
		}
		
		$table = new Content( '\IPS\core\Announcements\Announcement', Url::internal( 'app=core&module=modcp&controller=modcp&tab=announcements' ) );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'modcp', 'core', 'front' ), 'announcementRow' );
		$table->include = array( 'announce_title' );
		$table->mainColumn = 'announce_title';
		$table->sortBy = 'announce_id';
		$table->sortDirection = 'desc';
		$table->sortOptions = array( 'announce_id' );

		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'modcp_announcements' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'modcp_announcements' );
		Output::i()->output = Theme::i()->getTemplate( 'modcp' )->announcements( (string) $table );
	}
	
	/**
	 * Add/Edit Announcement
	 *
	 * @return	void
	 */
	public function create() : void
	{
		$current = NULL;
		if ( Request::i()->id )
		{
			$current = Announcement::load( Request::i()->id );
		}
		
		$form = Announcement::form( $current );
		$form->class = 'ipsForm--vertical ipsForm--edit-announcement';
		$form->attributes = array( 'data-controller' => 'core.front.modcp.announcementForm' );
		
		if ( $values = $form->values() )
		{
			$announcement = Announcement::_createFromForm( $values, $current );
				
			Output::i()->redirect( Url::internal( "app=core&module=modcp&tab=announcements", 'front', 'modcp_announcements' ) );
		}
		
		if ( !is_null( $current ) )
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'edit_announcement' );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'add_announcement' );
		}
		
		Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=modcp&controller=modcp&tab=announcements' ), Member::loggedIn()->language()->addToStack( 'modcp_announcements' ) );
		Output::i()->breadcrumb[] = array( NULL, ( !is_null( $current ) ) ? Member::loggedIn()->language()->addToStack( 'edit_announcement' ) : Member::loggedIn()->language()->addToStack( 'add_announcement' ) );
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Change Announcement Status
	 *
	 * @return	void
	 */
	public function status() : void
	{
		if ( !Member::loggedIn()->modPermission( 'can_manage_announcements' ) )
		{
			Output::i()->error( 'no_module_permission', '2C185/1', 403, '' );
		}
		
		Session::i()->csrfCheck();
		
		try
		{
			$announcement = Announcement::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C185/4', 404, '' );
		}
		
		$announcement->active = ( $announcement->active === 1 ? 0 : 1 );
		$announcement->save();

		Widget::deleteCaches( 'announcements', 'core' );
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'OK' ) );
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=modcp&tab=announcements", 'front', 'modcp_announcements' ) );
		}
	}
	
	/**
	 * Delete Announcement
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		if ( !Member::loggedIn()->modPermission( 'can_manage_announcements' ) )
		{
			Output::i()->error( 'no_module_permission', '2C185/2', 403, '' );
		}
		
		Session::i()->csrfCheck();
		
		try
		{
			$announcement = Announcement::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C185/5', 404, '' );
		}
		
		$announcement->delete();
		
		Output::i()->redirect( Url::internal( "app=core&module=modcp&tab=announcements", 'front', 'modcp_announcements' ) );
	}

	/**
	 * What do I manage?
	 * Acceptable responses are: content, members, or other
	 *
	 * @return    string
	 */
	public function manageType(): string
	{
		return 'other';
	}
}