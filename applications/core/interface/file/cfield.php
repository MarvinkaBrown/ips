<?php
/**
 * @brief		Upload Custom Field Download Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Feb 2016
 */

use IPS\Dispatcher\Front;
use IPS\File;
use IPS\Output;
use IPS\Request;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/core/interface/file/cfield.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';

try
{
	/* Get the extension */
	list( $app, $extension ) = explode( '_', Request::i()->storage );
	$classname = 'IPS\\' . $app . '\extensions\core\FileStorage\\' . $extension;	
	if ( !class_exists( $classname ) )
	{
		throw new RuntimeException;
	}
	$extension = new $classname;

	if ( ! isset( Request::i()->fileKey ) )
	{
		throw new RuntimeException;
	}

	/* Get the actual filename from the extension */
	$realFileName = \IPS\Text\Encrypt::fromTag( Request::i()->fileKey )->decrypt();

	/* Check the file is valid */
	$file = File::get( Request::i()->storage, $realFileName );
	if ( !$extension->isValidFile( $realFileName ) )
	{
		throw new RuntimeException;
	}
	
	/* Send headers and print file */
	Output::i()->sendStatusCodeHeader( 200 );
	Output::i()->sendHeader( "Content-type: " . File::getMimeType( Request::i()->path ) . ";charset=UTF-8" );
	foreach( array_merge( Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => Output::getContentDisposition( 'attachment', Request::i()->path ), "X-Content-Type-Options" => "nosniff" ) ) as $key => $header )
	{
		Output::i()->sendHeader( $key . ': ' . $header );
	}
	Output::i()->sendHeader( "Content-Length: " . $file->filesize() );
	Output::i()->sendHeader( "Content-Security-Policy: default-src 'none'; sandbox" );
	Output::i()->sendHeader( "X-Content-Security-Policy:  default-src 'none'; sandbox" );
	$file->printFile();
	exit;
}
catch (Exception $e )
{
	Front::i();
	Output::i()->sendOutput( '', 404 );
}