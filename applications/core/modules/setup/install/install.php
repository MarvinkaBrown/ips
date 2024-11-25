<?php
/**
 * @brief		Installer: Install
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Apr 2013
 */
 
namespace IPS\core\modules\setup\install;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\core\Setup\Install as InstallClass;
use IPS\Dispatcher\Controller;
use IPS\Helpers\MultipleRedirect;
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
 * Installer: Install
 */
class install extends Controller
{
	/**
	 * Install
	 */
	public function manage() : void
	{
		require \IPS\ROOT_PATH . '/conf_global.php';
		
		/* Zend Server has an issue where it caches 'require'd files which means the admin details written in the
		   previous step aren't in the $INFO array which causes the MultiRedirect to fail. Making the page reload after
		   a pause fixes the issue so we manually request a page refresh rather than doing it automatically */
		if ( ! isset( $INFO['admin_user'] ) )
		{
			Output::i()->title	 = Member::loggedIn()->language()->addToStack('install');
			Output::i()->output = Theme::i()->getTemplate( 'global' )->manualStart();
			
			return;
		}
		
		$multipleRedirect = new MultipleRedirect(
			Url::internal( 'controller=install' ),
			function( $data )
			{
				try
				{
					require \IPS\ROOT_PATH . '/conf_global.php';
					$install = new InstallClass(
						$INFO['apps'],
						$INFO['default_app'],
						$INFO['base_url'],
						mb_substr( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), 0, -mb_strlen( 'install/index.php' ) ),
						array( 'sql_host' => $INFO['sql_host'], 'sql_user' => $INFO['sql_user'], 'sql_pass' => $INFO['sql_pass'], 'sql_database' => $INFO['sql_database'], 'sql_port' => $INFO['sql_port'], 'sql_socket' => $INFO['sql_socket'], 'sql_tbl_prefix' => $INFO['sql_tbl_prefix'] ),
						$INFO['admin_user'],
						$INFO['admin_pass1'],
						$INFO['admin_email'],
						$INFO['diagnostics_reporting']
						);
				}
				catch ( InvalidArgumentException $e )
				{
					Output::i()->error( 'error', '4S112/1', 403, '' );
				}
		
				try
				{
					return $install->process( $data );
				}
				catch( Exception $e )
				{
					$backtrace = $e->getTraceAsString();

					$error = Theme::i()->getTemplate( 'global' )->error( "Error", $e->getMessage() ?: "Error", $e->getCode(), $backtrace );
					
					Request::i()->start = true;
					Output::i()->title	 = Member::loggedIn()->language()->addToStack('error');
					Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'install', $error, FALSE );
					 
					/* If we're still here - output */
					if ( Request::i()->isAjax() )
					{
						Output::i()->sendOutput( Output::i()->output, 200, 'text/html' );
					}
					else
					{
						Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output ), 403, 'text/html' );
					}
				}
			},
			function()
			{
				Output::i()->redirect( Url::internal( 'controller=done' ) );
			}
		);
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('install');
		Output::i()->output	= Theme::i()->getTemplate( 'global' )->block( 'install', $multipleRedirect, FALSE );
	}
}