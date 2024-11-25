<?php
/**
 * @brief		Gallery image download handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		30 May 2013
 */

use IPS\Dispatcher\External;
use IPS\File;
use IPS\Output;
use IPS\Request;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/gallery/interface/legacy/image.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
External::i();

try
{
	/* Get file and data */
	$file		= File::get( 'gallery_Images', Request::i()->path );

	$headers	= array_merge( Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => Output::getContentDisposition( 'inline', $file->originalFilename ), "X-Content-Type-Options" => "nosniff" ) );

	/* Send headers and print file */
	Output::i()->sendStatusCodeHeader( 200 );
	Output::i()->sendHeader( "Content-type: " . File::getMimeType( $file->originalFilename ) . ";charset=UTF-8" );

	foreach( $headers as $key => $header )
	{
		Output::i()->sendHeader( $key . ': ' . $header );
	}
	Output::i()->sendHeader( "Content-Length: " . $file->filesize() );
	Output::i()->sendHeader( "Content-Security-Policy: default-src 'none'; sandbox" );
	Output::i()->sendHeader( "X-Content-Security-Policy:  default-src 'none'; sandbox" );

	$file->printFile();
	exit;
}
catch (UnderflowException $e )
{
	Output::i()->sendOutput( '', 404 );
}