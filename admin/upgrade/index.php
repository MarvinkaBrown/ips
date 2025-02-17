<?php
/**
 * @brief		Upgrader bootstrap
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 May 2014
 */

use IPS\Dispatcher\Setup;
use const IPS\NO_WRITES;
use const IPS\QUERY_LOG;

define('READ_WRITE_SEPARATION', FALSE);
define('REPORT_EXCEPTIONS', TRUE);

/* Prevent hooks from running during the upgrade */
define('RECOVERY_MODE', TRUE);

require_once '../../init.php';

if( \IPS\IN_DEV )
{
	die( "You must disable developer mode (IN_DEV) in order to run the upgrader" );
}

if( NO_WRITES )
{
	die( "You must disable no-writes mode (NO_WRITES) in order to run the upgrader" );
}

if( QUERY_LOG )
{
	die( "You must disable the query log (QUERY_LOG) in order to run the upgrader" );
}

Setup::i()->setLocation('upgrade')->run();