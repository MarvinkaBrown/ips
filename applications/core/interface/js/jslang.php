<?php
/**
 * @brief		Return JS language strings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

use IPS\Application;
use IPS\Db;
use IPS\Lang;
use IPS\Output;
use IPS\Request;

define('REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';

Output::setCacheTime( false );

$langId	= intval( Request::i()->langId );
$_lang	= array();

foreach ( Db::i()->select( '*', 'core_sys_lang_words', array( 'lang_id=? AND word_js=?', $langId, TRUE ) ) as $row )
{
	$_lang[ $row['word_key'] ] = $row['word_custom'] ?: $row['word_default'];
}

if ( \IPS\IN_DEV )
{
	foreach ( Application::applications() as $app )
	{
		if( Application::appIsEnabled( $app->directory ) )
		{
			$_lang = array_merge( $_lang, Lang::readLangFiles( $app->directory, true ) );
		}
	}
}

$cacheHeaders	= ( \IPS\IN_DEV !== true ) ? Output::getCacheHeaders( time(), 360 ) : array();

/* Display */
Output::i()->sendOutput( 'ips.setString( ' . json_encode( $_lang ) . ')', 200, 'text/javascript', $cacheHeaders );