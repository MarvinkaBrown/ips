<?php
/**
 * @brief		Profile settings gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		08 Jan 2018
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile settings gateway
 */
class profiles extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Call
	 *
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		/* Init */
		$activeTab			= Request::i()->tab ?: 'profilefields';
		$activeTabContents	= '';
		$tabs				= array();

		/* Add a tab for fields and completion */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'profilefields_manage' ) )
		{
			$tabs['profilefields']		= 'profile_fields';
			$tabs['profilecompletion']	= 'profile_completion';
		}

		/* Add a tab for settings */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'profiles_manage' ) )
		{
			$tabs['profilesettings']	= 'profile_settings';
		}

		/* Route */
		$classname = 'IPS\core\modules\admin\membersettings\\' . $activeTab;
		$class = new $classname;
		$class->url = Url::internal("app=core&module=membersettings&controller=profiles&tab={$activeTab}");
		$class->execute();
		
		$output = Output::i()->output;
				
		if ( $method !== 'manage' or Request::i()->isAjax() )
		{
			return;
		}
		Output::i()->output = '';
				
		/* Output */
		Output::i()->title = Member::loggedIn()->language()->addToStack('module__core_profile');
		Output::i()->output .= Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $output, Url::internal( "app=core&module=membersettings&controller=profiles" ) );
	}
}