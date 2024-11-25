<?php
/**
 * @brief		Microsoft Account Login Handler Redirect URI Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Mar 2013
 */

use IPS\Http\Url;
use IPS\Output;
use IPS\Request;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/core/interface/microsoft/auth.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';

$target = Url::internal( 'oauth/callback/', 'none' );

foreach ( array( 'code', 'state', 'scope', 'error', 'error_description', 'error_uri' ) as $k )
{
	if ( isset( Request::i()->$k ) )
	{
		$target = $target->setQueryString( $k, Request::i()->$k );
	}
}

Output::i()->redirect( $target );
