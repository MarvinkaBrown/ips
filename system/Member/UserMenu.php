<?php

/**
 * @brief        UserMenu
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        7/26/2023
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Application\Module;
use IPS\Dispatcher;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\Menu;
use IPS\Helpers\Menu\Link;
use IPS\Http\Url;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;

if (!defined('\IPS\SUITE_UNIQUE_KEY'))
{
	header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
	exit;
}

class UserMenu
{
	/**
	 * Build the user account menu for this member
	 *
	 * @param Member $member
	 * @return Menu|null
	 */
	public static function accountMenu( Member $member ) : Menu|null
	{
		if( !$member->member_id )
		{
			return null;
		}

		$menu = new Menu( 'UserLink', null, 'ipsUserNav__link' );
		$menu->menuType = 'normal';
		$menu->customLinkHtml = Theme::i()->getTemplate( 'global', 'core', 'front' )->userMenuLink();
		$menu->extraHtmlBeforeLinks = Theme::i()->getTemplate( 'global', 'core', 'front' )->userMenuAchievements();

		if( $member->canAccessModule( Module::get( 'core', 'members', 'front' ) ) )
		{
			$menu->add( new Link( $member->url(), 'menu_profile', icon:'fa-solid fa-user', identifier: 'profile' ) );
			$menu->add( new Link( $member->url()->setQueryString( 'do', 'edit' ), 'profile_edit', icon: 'fa-solid fa-pen-to-square', identifier: 'editProfile' ) );
		}

		$settingsLink = new Link( Url::internal( "app=core&module=system&controller=settings", "front", "settings" ), 'menu_settings', icon: 'fa-solid fa-gear', identifier: 'settings' );
		if( $member->members_bitoptions['email_messages_bounce'] )
		{
			$settingsLink->notificationIcon = 'fa-solid fa-triangle-exclamation';
		}
		$menu->add( $settingsLink );

		foreach( Application::allExtensions( 'core', 'UserMenu', $member ) as $ext )
		{
			foreach( $ext->accountMenu( 'settings' ) as $element )
			{
				$menu->add( $element );
			}
		}

		$menu->addSeparator();

		if( $member->canAccessModule( Module::get( 'core', 'messaging' ) ) AND $member->members_disable_pm AND $member->members_disable_pm != 2 )
		{
			$menu->add( new Link( Url::internal( "app=core&module=messaging&controller=messenger&do=enableMessenger", "front", "messaging" )->csrf(), 'menu_messages', dataAttributes: [
				'data-confirm' => true,
				'data-confirmMessage' => $member->language()->addToStack( 'messenger_disabled_msg' )
			], icon: 'fa-solid fa-envelope', identifier: 'messages' ) );
		}

		$menu->add( new Link( Url::internal( "app=core&module=system&controller=followed", "front", "followed_content" ), "menu_followed_content", icon: 'fa-solid fa-list-check', identifier: 'manageFollowed' ) );

		if( Settings::i()->ignore_system_on )
		{
			$menu->add( new Link( Url::internal( "app=core&module=system&controller=ignore", "front", "ignore" ), "menu_manage_ignore", icon: "fa-solid fa-user-slash", identifier: "ignoredUsers" ) );
		}

		if( $member->group['g_attach_max'] != 0 )
		{
			$menu->add( new Link( Url::internal( "app=core&module=system&controller=attachments", "front", "attachments" ), 'my_attachments', icon: 'fa-solid fa-paperclip', identifier: 'attachments' ) );
		}

		$menu->add( new Link( Url::internal( "app=core&module=system&controller=markread", "front", "mark_site_as_read" )->csrf()->addRef( Request::i()->url() ), 'mark_site_read', dataAttributes: [
			'data-action' => 'markSiteRead',
			'data-controller' => 'core.front.core.markRead'
		], icon: 'fa-solid fa-eye' ) );

		foreach( Application::allExtensions( 'core', 'UserMenu', $member ) as $ext )
		{
			foreach( $ext->accountMenu( 'content' ) as $element )
			{
				$menu->add( $element );
			}
		}

		$menu->addSeparator();

		if( $member->isAdmin() )
		{
			$menu->add( new Link( Url::internal( "app=core&module=system&controller=themeeditor", "front", "theme_editor" ), 'menu_theme_editor', dataAttributes: [
				'target' => '_top'
			], icon: "fa-solid fa-paintbrush", identifier: "themeEditor" ) );
		}

		if( Dispatcher::i()->application instanceof Application AND Dispatcher::i()->application->canManageWidgets() )
		{
			$menu->addHtml( Theme::i()->getTemplate( 'global', 'core', 'front' )->blockEditorButton() );
		}

		if( ( $member->canAccessModule( Module::get( 'core', 'modcp' ) ) AND $member->modPermission() ) OR $member->isAdmin() )
		{
			if( $member->canAccessModule( Module::get( 'core', 'modcp' ) ) AND $member->modPermission() )
			{
				$menu->add( new Link( Url::internal( "app=core&module=modcp", "front", "modcp" ), "menu_modcp", icon: "fa-solid fa-user-shield", identifier: "modcp" ) );
			}
			if( $member->isAdmin() )
			{
				$menu->add( new Link( Url::internal( "", "admin" ), "menu_admincp", dataAttributes: [
					'target' => '_blank',
					'rel' => 'noopener'
				], icon: 'fa-solid fa-lock', identifier: 'admincp' ) );
			}
			$menu->addSeparator();
		}

		foreach( Application::allExtensions( 'core', 'UserMenu', $member ) as $ext )
		{
			foreach( $ext->accountMenu( 'logout' ) as $element )
			{
				$menu->add( $element );
			}
		}

		$menu->add( new Link( Url::internal( "app=core&module=system&controller=login&do=logout", "front", "logout" )->csrf(), ( isset( $_SESSION['logged_in_as_key'] ) ? $member->language()->addToStack( 'switch_to_account', true, array(
			'sprintf' => array( $_SESSION['logged_in_from']['name'] )
		) ) : 'sign_out' ), icon: 'fa-solid fa-arrow-right-from-bracket', identifier: 'signout' ) );

		return $menu;
	}

	/**
	 * Build the mobile navigation menu for this member
	 *
	 * @param Member $member
	 * @return Menu|null
	 */
	public static function mobileMenu( Member $member ) : Menu|null
	{
		$menu = new Menu( 'MobileNav' );

		$menu->add( new Link( $member->url(), 'menu_profile', '', icon:'fa-solid fa-user', identifier: 'profile' ) );
		$menu->add( new Link( $member->url()->setQueryString( 'do', 'edit' ), 'profile_edit', '', icon: 'fa-solid fa-pen-to-square', identifier: 'editProfile' ) );
		$settingsLink = new Link( Url::internal( "app=core&module=system&controller=settings", "front", "settings" ), "menu_settings", "", icon: 'fa-solid fa-gear', identifier: "settings" );
		if( $member->members_bitoptions['email_messages_bounce'] )
		{
			$settingsLink->notificationIcon = 'fa-solid fa-triangle-exclamation';
		}
		$menu->add( $settingsLink );

		foreach( Application::allExtensions( 'core', 'UserMenu', $member ) as $ext )
		{
			foreach( $ext->mobileMenu( 'settings' ) as $element )
			{
				$menu->add( $element );
			}
		}

		$menu->addSeparator();

		if( $member->canAccessModule( Module::get( 'core', 'messaging' ) ) AND $member->members_disable_pm AND $member->members_disable_pm != 2 )
		{
			$menu->add( new Link( Url::internal( "app=core&module=messaging&controller=messenger&do=enableMessenger", "front", "messaging" )->csrf(), "menu_messages", "", [
				'data-confirm' => '',
				'data-confirmMessage' => $member->language()->addToStack( 'messenger_disabled_msg' )
			], icon: 'fa-solid fa-envelope', identifier: "messages" ) );
		}

		$menu->add( new Link( Url::internal( "app=core&module=system&controller=followed", "front", "followed_content" ), "menu_followed_content", "", icon: 'fa-solid fa-list-check', identifier: 'manageFollowed' ) );

		if( Settings::i()->ignore_system_on )
		{
			$menu->add( new Link( Url::internal( "app=core&module=system&controller=ignore", "front", "ignore" ), "menu_manage_ignore", "", icon: 'fa-solid fa-user-slash', identifier: "ignoredUsers" ) );
		}

		if( $member->group['g_attach_max'] )
		{
			$menu->add( new Link( Url::internal( "app=core&module=system&controller=attachments", "front", "attachments" ), "my_attachments", "", icon: 'fa-solid fa-paperclip', identifier: "attachments" ) );
		}

		$menu->add( new Link( Url::internal( "app=core&module=system&controller=markread", "front", "mark_site_as_read" )->csrf()->addRef( Request::i()->url() ), "mark_site_read", "", [
			'data-action' => 'markSiteRead',
			'data-controller' => 'core.front.core.markRead'
		], icon: 'fa-solid fa-eye' ) );

		foreach( Application::allExtensions( 'core', 'UserMenu', $member ) as $ext )
		{
			foreach( $ext->mobileMenu( 'content' ) as $element )
			{
				$menu->add( $element );
			}
		}

		if( $member->isAdmin() or ($member->canAccessModule( Module::get( 'core', 'modcp' ) ) AND $member->modPermission()))
		{
			$menu->addSeparator();
		}

		if( $member->isAdmin() )
		{
			$menu->add( new Link( Url::internal( "app=core&module=system&controller=themeeditor", "front", "theme_editor" ), 'menu_theme_editor', '', dataAttributes: [
				'target' => '_top'
			], icon: 'fa-solid fa-paintbrush', identifier: "themeEditor" ) );
		}

		if( $member->canAccessModule( Module::get( 'core', 'modcp' ) ) AND $member->modPermission() )
		{
			$menu->add( new Link( Url::internal( "app=core&module=modcp", "front", "modcp" ), "menu_modcp", "", icon: 'fa-solid fa-user-shield', identifier: "modcp" ) );
		}
		if( $member->isAdmin() )
		{
			$menu->add( new Link( Url::internal( "", "admin" ), "menu_admincp", '', dataAttributes: [
				'target' => '_blank',
				'rel' => 'noopener'
			], icon: 'fa-solid fa-lock', identifier: 'admincp' ) );
		}

		$menu->addSeparator();

		foreach( Application::allExtensions( 'core', 'UserMenu', $member ) as $ext )
		{
			foreach( $ext->mobileMenu( 'logout' ) as $element )
			{
				$menu->add( $element );
			}
		}

		$menu->add( new Link( Url::internal( "app=core&module=system&controller=login&do=logout", "front", "logout" )->csrf(), ( isset( $_SESSION['logged_in_as_key'] ) ? $member->language()->addToStack( 'switch_to_account', true, array(
			'sprintf' => array( $_SESSION['logged_in_from']['name'] )
		) ) : 'sign_out' ), "", icon: 'fa-solid fa-arrow-right-from-bracket', identifier: 'signout' ) );

		return $menu;
	}

	/**
	 * @param Member $member
	 * @return Menu|null
	 */
	public static function profileMenu( Member $member ): Menu|null
	{
		/* If we have no permission to modify this profile, show nothing */
		if( !Member::loggedIn()->modPermission( 'can_modify_profiles' ) and ( Member::loggedIn()->member_id != $member->member_id or !Member::loggedIn()->group['g_edit_profile'] ) )
		{
			return null;
		}

		$menu = new Menu( 'profile_edit','fa-regular fa-pen-to-square');
		$menu->css = 'ipsButton ipsButton--overlay';

		/* Only the logged in member should be able to modify the photos */
		if( Member::loggedIn()->member_id == $member->member_id AND explode( ':', $member->group['g_photo_max_vars'] )[0] )
		{
			$link = new Link( Url::internal( "app=core&module=members&controller=profile&do=editPhoto&id={$member->member_id}", 'front','edit_profile_photo', $member->members_seo_name ), 'profile_edit_photo_tab',icon: 'fa-regular fa-circle-user' );
			$link->dataAttributes['data-action'] = 'editPhoto';
			$link->opensDialog('profile_edit_photo_tab');
			$menu->add( $link );
			$menu->addSeparator();

			if( $coverPhoto = $member->coverPhoto() )
			{
				if( $coverPhoto->editable )
				{
					if( $coverPhoto->file )
					{
						$link = new Link('#', 'cover_photo_reposition', icon: 'fa-solid fa-arrows-up-down-left-right' );
						$link->dataAttributes['data-action'] = 'positionCoverPhoto';
						$link->wrapperDataAttributes['data-role'] = 'photoEditOption';
						$menu->add( $link);
						$link = new Link( $member->url()->setQueryString( 'do', 'coverPhotoRemove' )->csrf(), 'cover_photo_remove', icon: 'fa-solid fa-trash-can', );
						$link->wrapperDataAttributes['data-role'] = 'photoEditOption';
						$link->dataAttributes['data-action'] = 'removeCoverPhoto';
						$menu->add( $link);
					}
					$link = new Link( $member->url()->setQueryString( 'do', 'coverPhotoUpload'), 'cover_photo_add', icon: 'fa-solid fa-upload');
					$link->opensDialog('cover_photo_add');
					$menu->add( $link);
					$menu->addSeparator();
				}
			}
		}

		$link = new Link(  $member->url()->setQueryString('do', 'edit'), 'profile_edit', icon: 'fa-regular fa-pen-to-square');
		$link->opensDialog('profile_edit');
		$menu->add( $link);
		if( $member->member_id === Member::loggedIn()->member_id )
		{
			$menu->add( new Link( Url::internal( 'app=core&module=system&controller=settings', seoTemplate: 'settings'), 'menu_settings', icon: 'fa-solid fa-list-check') );
		}
		
		foreach( Application::allExtensions( 'core', 'UserMenu', $member ) as $ext )
		{
			foreach( $ext->editProfileMenu( $member ) as $element )
			{
				$menu->add( $element );
			}
		}

		return $menu;
	}
}