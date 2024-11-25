<?php
/**
 * @brief		Pages External Block Gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		30 Jun 2015
 *
 */

use IPS\Dispatcher\External;
use IPS\Output;
use IPS\Request;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/cms/interface/developer/developer.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
External::i();

if ( !\IPS\IN_DEV )
{
	exit();
}

if ( isset( Request::i()->file ) )
{
	$realPath = realpath( \IPS\ROOT_PATH . '/themes/' . Request::i()->file );
	$pathContainer = realpath(\IPS\ROOT_PATH . '/themes/' );

	if( $realPath === FALSE OR mb_substr( $realPath, 0, mb_strlen( $pathContainer ) ) !== $pathContainer )
	{
		Output::i()->error( 'node_error', '3C171/8', 403, '' );
		exit;
	}

	$file = file_get_contents( \IPS\ROOT_PATH . '/themes/' . Request::i()->file );

	Output::setCacheTime( false );
	Output::i()->sendOutput( preg_replace( '#<ips:template.+?\n#', '', $file ), 200, ( mb_substr( Request::i()->file, -4 ) === '.css' ) ? 'text/css' : 'text/javascript' );
}