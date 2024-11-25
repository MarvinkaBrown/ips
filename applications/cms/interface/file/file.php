<?php
/**
 * @brief		Pages Download Handler for custom record upload fields
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		27 May 2015
 */

use IPS\cms\Databases;
use IPS\Dispatcher\External;
use IPS\Dispatcher\Front;
use IPS\File;
use IPS\Member;
use IPS\Output;
use IPS\Request;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/cms/interface/file/file.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
External::i();

try
{
	/* Load member */
	$member = Member::loggedIn();
	
	/* Set up autoloader for CMS */

	/* Init */
	$databaseId  = intval( Request::i()->database );
	$database    = Databases::load( $databaseId );
	$recordId    = intval( Request::i()->record );
	$fileName    = urldecode( Request::i()->file );
	$recordClass = '\IPS\cms\Records' . $databaseId;
	$realFileName = NULL;

	try
	{
		$record = $recordClass::load( $recordId );
	}
	catch(OutOfRangeException $ex )
	{
		Output::i()->error( 'no_module_permission', '2T279/1', 403, '' );
	}
	
	if ( ! $record->canView() )
	{
		Output::i()->error( 'no_module_permission', '2T279/2', 403, '' );
	}

	$realFileName = \IPS\Text\Encrypt::fromTag( Request::i()->fileKey )->decrypt();

	if ( ! $realFileName )
	{
		Output::i()->error( 'no_module_permission', '2T279/4', 403, '' );
	}

	/* Get file and data */
	try
	{
		$file = File::get( 'cms_Records', $realFileName );
	}
	catch(Exception $ex )
	{
		Output::i()->error( 'no_module_permission', '2T279/3', 404, '' );
	}
		
	$headers = array_merge( Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => Output::getContentDisposition( 'attachment', Request::i()->file ), "X-Content-Type-Options" => "nosniff" ) );
	
	/* Send headers and print file */
	Output::i()->sendStatusCodeHeader( 200 );
	Output::i()->sendHeader( "Content-type: " . File::getMimeType( Request::i()->file ) . ";charset=UTF-8" );

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
	Front::i();
	Output::i()->sendOutput( '', 404 );
}