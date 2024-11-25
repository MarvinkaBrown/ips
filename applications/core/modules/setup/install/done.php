<?php
/**
 * @brief		Installer: Finished Screen
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Apr 2013
 */
 
namespace IPS\core\modules\setup\install;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\FrontNavigation;
use IPS\Dispatcher\Controller;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function file_put_contents;
use function function_exists;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Installer: Finished Screen
 */
class done extends Controller
{
	/**
	 * Finished
	 */
	public function manage() : void
	{
		/* Reset theme maps to make sure bad data hasn't been cached by visits mid-setup */
		foreach( Application::applications() as $app => $data )
		{
			Theme::deleteCompiledTemplate( $app);
			Theme::deleteCompiledCss( $app );
			Theme::deleteCompiledResources( $app );
		}
		
		Output::clearJsFiles();

		/* Build default front navigation - if you visit ACP directly after it won't build, and you can run into issues */
		FrontNavigation::i()->buildDefaultFrontNavigation( true );
		
		require \IPS\ROOT_PATH . '/conf_global.php';

		Settings::i()->changeValues( array( 'ipb_reg_number' => $INFO['lkey'], 'site_secret_key' => md5( $INFO['sql_pass'] . $INFO['base_url'] . $INFO['sql_database'] ) ) );

		file_put_contents( \IPS\ROOT_PATH . '/conf_global.php', "<?php\n\n" . '$INFO = ' . var_export( array(
			'sql_host'	 		=> $INFO['sql_host'],
			'sql_database'		=> $INFO['sql_database'],
			'sql_user'			=> $INFO['sql_user'],
			'sql_pass'			=> $INFO['sql_pass'],
			'sql_port'			=> $INFO['sql_port'],
			'sql_socket'		=> $INFO['sql_socket'],
			'sql_tbl_prefix'	=> $INFO['sql_tbl_prefix'],
			'sql_utf8mb4'		=> $INFO['sql_utf8mb4'],
			'board_start'		=> time(),
			'installed'			=> TRUE,
			'base_url'			=> $INFO['base_url'],
			'guest_group'		=> 2,
			'member_group'		=> 3,
			'admin_group'		=> 4
			), TRUE ) . ';' );
		
		/* PHP 5.5 - clear opcode cache or details won't be seen on next page load */
		if ( function_exists( 'opcache_invalidate' ) )
		{
			@opcache_invalidate( \IPS\ROOT_PATH . '/conf_global.php' );
		}
					
		Settings::i()->base_url	= $INFO['base_url'];

		Output::i()->title = Member::loggedIn()->language()->addToStack('done');
	}
}