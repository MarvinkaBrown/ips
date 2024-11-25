<?php
/**
 * @brief		recovery
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		03 Nov 2016
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Theme;
use function defined;
use function in_array;
use function intval;
use const IPS\NO_WRITES;
use const IPS\RECOVERY_MODE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * recovery
 */
class recovery extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Recover
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Are we even in recovery mode? */
		if ( RECOVERY_MODE === FALSE )
		{
			Output::i()->error( 'recovery_mode_disabled', '1C342/1', 403, '' );
		}
		
		if ( NO_WRITES )
		{
			Output::i()->error( 'no_writes', '1C342/2', 403, '' );
		}
		
		Session::i()->csrfCheck();
		
		/* We are, let's set up a multi-redirect to disable things. At the end of the process, we'll list everything we did. */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'recovery_mode' );
		Output::i()->output = new MultipleRedirect( Url::internal( 'app=core&module=support&controller=recovery' )->csrf(), function( $step )
		{
			$step = intval( $step );
			
			switch( $step )
			{
				case 0: # Applications
					$appsDisabled = array();
					
					/* Disable All non-IPS Applications */
					foreach( Application::applications() AS $app )
					{
						if ( !in_array( $app->directory, IPS::$ipsApps ) )
						{
							$app->_enabled = FALSE;
							$appDisabled[] = $app->_id;
						}
					}
					
					$_SESSION['recoveryApps'] = $appsDisabled;
					
					return array( 2, Member::loggedIn()->language()->addToStack( 'disabled_applications' ), 25 );
				
				case 2: # Reset Theme
					$themeReset = FALSE;
					
					if ( Db::i()->select( 'COUNT(*)', 'core_theme_templates', array( "template_set_id>?", 0 ) )->first() OR Db::i()->select( 'COUNT(*)', 'core_theme_css', array( "css_set_id>?", 0 ) )->first() )
					{
						/* Create a new theme */
						$theme = new Theme;
						$theme->permissions = Member::loggedIn()->member_group_id;
						$theme->save();
						$theme->installThemeEditorSettings();
						
						Lang::saveCustom( 'core', "core_theme_set_title_" . $theme->id, "IPS Support" );
						
						/* Set this account to use that theme */
						Member::loggedIn()->skin		= $theme->id;
						Member::loggedIn()->save();
						
						$themeReset = TRUE;
					}
					
					$_SESSION['recoveryTheme'] = $themeReset;
					
					return array( 3, Member::loggedIn()->language()->addToStack( 'reset_theme_to_default' ), 75 );
								
				case 4: # Done
				default:
					return NULL;
			}
		}, function()
		{
			IPS::resyncIPSCloud('Enabled recovery mode');
			Session::i()->log( 'acplog__enabled_recovery' );
			Output::i()->redirect( Url::internal( 'app=core&module=support&controller=recovery&do=done' ) );
		} );
	}
	
	/**
	 * "Done" Screen
	 *
	 * @return	void
	 */
	public function done() : void
	{
		/* Did we disable any apps? */
		$apps = array();
		foreach( $_SESSION['recoveryApps'] AS $app )
		{
			$apps[] = Application::load( $app );
		}
		
		/* Did we reset the theme? */
		$theme = $_SESSION['recoveryTheme'];
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'recovery_mode' );
		Output::i()->output = Theme::i()->getTemplate( 'support' )->recovery( $apps, $theme );
	}
}