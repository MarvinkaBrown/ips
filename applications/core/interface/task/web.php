<?php
/**
 * @brief		Runs tasks (web URL)
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Dec 2015
 */

/* Init Invision Community */

use IPS\Application;
use IPS\Log;
use IPS\Login;
use IPS\Request;
use IPS\Settings;
use IPS\Task;

define('REPORT_EXCEPTIONS', TRUE);
define('READ_WRITE_SEPARATION', FALSE);
require_once str_replace( 'applications/core/interface/task/web.php', 'init.php', str_replace( '\\', '/', __FILE__ ) );

@header( "Cache-Control: no-cache, no-store, must-revalidate, max-age=0, s-maxage=0" );
@header( "Expires: 0" );

/* Set HTTP status */
$http = ( isset( $_SERVER['SERVER_PROTOCOL'] ) and strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false ) ? '1.0' : '1.1';

/* Execute */
try
{
	/* Ensure applications set up correctly before task is executed. Pages, for example, needs to set up spl autoloaders first */
	Application::applications();

	if( Settings::i()->task_use_cron != 'web' )
	{
		throw new OutOfRangeException( "Invalid Task Method" );
	}

	if( !Login::compareHashes( (string) Settings::i()->task_cron_key, (string) Request::i()->key ) )
	{
		throw new OutOfRangeException( "Invalid Key" );
	}

	$task = Task::queued();

	if ( $task )
	{
		$task->runAndLog();
	}

	@header( "HTTP/{$http} 200 OK" );
	print "Task Ran";
}
catch (OutOfRangeException $e )
{
	Log::debug( $e, $e->getMessage() );

	@header( "HTTP/{$http} 500 Internal Server Error" );
	echo "Exception:\n";
	print $e->getMessage();
}
catch (Exception $e )
{
	Log::log( $e, 'uncaught_exception' );
	
	@header( "HTTP/{$http} 500 Internal Server Error" );
	echo "Exception:\n";
	print $e->getMessage();
}

/* Exit */
exit;