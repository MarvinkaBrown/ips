<?php
/**
* @brief		View tracking for email advertisements
* @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
* @copyright	(c) Invision Power Services, Inc.
* @license		https://www.invisioncommunity.com/legal/standards/
* @package		Invision Community
* @since		6 Dec 2018
*/

/* Init */

use IPS\Db;
use IPS\Output;
use IPS\Request;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/core/interface/email/views.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';

/* Track advertisement views */
if( isset( Request::i()->ads ) AND Request::i()->ads )
{
	$ads = explode( ',', Request::i()->ads );
	$ads = array_map( "intval", $ads );

	Db::i()->update( 'core_advertisements', "ad_email_views=COALESCE(ad_email_views,0)+1", array( 'ad_id IN(' . implode( ',', $ads ) . ')' ) );
}

/* Outputs a 1x1 transparent gif, disabling additional parsing we don't need */
Output::i()->sendOutput( base64_decode( "R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" ), 200, 'image/gif', Output::getNoCacheHeaders(), FALSE, FALSE, FALSE, FALSE );