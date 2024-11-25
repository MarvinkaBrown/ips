<?php
/**
 * @brief		ACP Member Profile: Basic Information Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\MemberACPProfile\Block;
use IPS\Http\Url;
use IPS\Login;
use IPS\Login\Handler;
use IPS\Login\Handler\Standard;
use IPS\Member;
use IPS\nexus\Subscription;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Basic Information Block
 */
class BasicInformation extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$hasPassword = FALSE;
		$canChangePassword = Handler::findMethod( 'IPS\Login\Handler\Standard' );
		$activeIntegrations = array();
		if ( Member::loggedIn()->hasAcpRestriction('core', 'members', 'member_edit') )
		{
			/* Is this an admin? */
			if ( $this->member->isAdmin() AND !Member::loggedIn()->hasAcpRestriction('core', 'members', 'member_edit_admin' ) )
			{
				$canChangePassword = FALSE;
			}
			
			if ( $canChangePassword !== FALSE )
			{
				foreach ( Login::methods() as $method )
				{
					if ( $method->canProcess( $this->member ) )
					{
						if ( !( $method instanceof Standard ) )
						{
							$activeIntegrations[] = $method->_title;
						}
						if ( $method->canChangePassword( $this->member ) )
						{
							$hasPassword = TRUE;
							$canChangePassword = TRUE;
						}
					}
				}
			}
		}
		else
		{
			$canChangePassword = FALSE;
		}
		
		$accountActions = array();
		if ( Member::loggedIn()->member_id != $this->member->member_id AND Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_login' ) AND !$this->member->isBanned() )
		{
			$accountActions[] = array(
				'title'		=> Member::loggedIn()->language()->addToStack( 'login_as_x', FALSE, array( 'sprintf' => array( $this->member->name ) ) ),
				'icon'		=> 'key',
				'link'		=> Url::internal( "app=core&module=members&controller=members&do=login&id={$this->member->member_id}" )->csrf(),
				'class'		=> '',
				'target'    => '_blank'
			);
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'members_merge' ) )
		{
			$accountActions[] = array(
				'title'		=> 'merge_with_another_account',
				'icon'		=> 'level-up',
				'link'		=> Url::internal( "app=core&module=members&controller=members&do=merge&id={$this->member->member_id}" ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('merge') )
			);
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete' ) and ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete_admin' ) or !$this->member->isAdmin() ) and $this->member->member_id != Member::loggedIn()->member_id )
		{
			$accountActions[] = array(
				'title'		=> 'delete',
				'icon'		=> 'times-circle',
				'link'		=> Url::internal( "app=core&module=members&controller=members&do=delete&id={$this->member->member_id}" ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('delete') ),
			);
		}
		
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_export_pi' ) )
		{
			$accountActions[] = array(
				'title'		=> 'member_export_pi_title',
				'icon'		=> 'download',
				'link'		=> Url::internal( "app=core&module=members&controller=members&do=exportPersonalInfo&id={$this->member->member_id}" ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('member_export_pi_title') )
			);
		}

		/* Data Layer PII Option */
		if (
			Settings::i()->core_datalayer_enabled AND
			Settings::i()->core_datalayer_include_pii AND
			Settings::i()->core_datalayer_member_pii_choice AND
			( ( $this->member->isAdmin() AND Member::loggedIn()->hasAcpRestriction('core', 'members', 'member_edit_admin' ) ) OR Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
		)
		{
			$enabled = !$this->member->members_bitoptions['datalayer_pii_optout'];
			$accountActions[] = array(
				'title'		=> $enabled ? 'datalayer_omit_member_pii' : 'datalayer_collect_member_pii',
				'icon'		=> 'id-card',
				'link'		=> Url::internal( "app=core&module=members&controller=members&do=toggleDataLayerPii&id={$this->member->member_id}" ),
			);
		}
		
		$activeSubscription = FALSE;
		if ( Application::appIsEnabled('nexus') and Settings::i()->nexus_subs_enabled ) // I know... this should really be a hook... I won't tell if you won't
		{
			$activeSubscription = Subscription::loadByMember( $this->member, true );
		}
		
		return (string) Theme::i()->getTemplate('memberprofile')->basicInformation( $this->member, $canChangePassword, $hasPassword, $activeIntegrations, $accountActions, $activeSubscription );
	}
}