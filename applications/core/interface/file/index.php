<?php
/**
 * @brief		Database File Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 May 2013
 */

use IPS\Db;
use IPS\File;
use IPS\Output;
use IPS\Request;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/core/interface/file/index.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';

try
{	
	if ( isset( Request::i()->id ) and isset( Request::i()->salt ) )
	{
		$where = array( 'id=? AND salt=?', Request::i()->id, Request::i()->salt );
	}
	else
	{
		$exploded = explode( '/', trim( urldecode( Request::i()->file ), '/' ) );
		$filename = array_pop( $exploded );
		$container = implode( '/', $exploded );
		
		$where = array( 'container=? AND filename=?', $container, $filename );
	}
	
	$file = Db::i()->select( '*', 'core_files', $where )->first();
		
	if ( $file['id'] )
	{
		$headers	= array_merge( Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => Output::getContentDisposition( 'inline', $file['filename'] ), "X-Content-Type-Options" => "nosniff" ) );
		Output::i()->sendOutput( $file['contents'], 200, File::getMimeType( $file['filename'] ), $headers );
	}
}
catch (UnderflowException $e )
{
	Output::i()->sendOutput( '', 404 );
}