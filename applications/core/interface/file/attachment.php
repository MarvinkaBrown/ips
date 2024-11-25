<?php
/**
 * @brief		Attachment Download Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 May 2013
 */

use IPS\Application;
use IPS\Db;
use IPS\Dispatcher\External;
use IPS\File;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session\Front;

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/core/interface/file/attachment.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
Front::i();

try
{
	/* Load member */
	$member = Member::loggedIn();
	
	/* Init */
	$permission = FALSE;
	$loadedExtensions = array();
	
	/* Get attachment */
	$attachment = Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', Request::i()->id ) )->first();
	
	/* If the user isn't logged in, and this attachment has a security key attached to it, check that. */
	if ( $attachment['attach_security_key'] )
	{
		/* Key doesn't exist or doesn't match */
		if ( !isset( Request::i()->key ) OR !Login::compareHashes( $attachment['attach_security_key'], Request::i()->key ) )
		{
			throw new UnexpectedValueException;
		}
		
		/* Key passes, so do normal permission checks. */
	}

	if( $member->member_id )
	{
		/* If there is a member logged in, and they posted the attachment, they have permission to view. */
		if ( $member->member_id == $attachment['attach_member_id'] )
		{
			$permission	= TRUE;
		}
	}

	if( $permission !== TRUE )
	{
		foreach ( Db::i()->select( '*', 'core_attachments_map', array( 'attachment_id=?', $attachment['attach_id'] ) ) as $map )
		{
			if ( !isset( $loadedExtensions[ $map['location_key'] ] ) )
			{
				$exploded = explode( '_', $map['location_key'] );
				try
				{
					$extensions = Application::load( $exploded[0] )->extensions( 'core', 'EditorLocations' );
					if ( isset( $extensions[ $exploded[1] ] ) )
					{
						$loadedExtensions[ $map['location_key'] ] = $extensions[ $exploded[1] ];
					}
				}
				catch (OutOfRangeException $e ) { }
			}
					
			if ( isset( $loadedExtensions[ $map['location_key'] ] ) )
			{
				try
				{
					if ( $loadedExtensions[ $map['location_key'] ]->attachmentPermissionCheck( $member, $map['id1'], $map['id2'], $map['id3'], $attachment ) )
					{
						$permission = TRUE;
						break;
					}
				}
				catch (OutOfRangeException $e ) { }
			}
		}
	}
		
	/* Permission check */
	if ( !$permission )
	{
		External::i();
		Output::i()->error( 'no_attachment_permission', '2C171/1', 403, '' );
	}

	/* Get file and data */
	$file		= File::get( 'core_Attachment', $attachment['attach_location'] );
	$headers	= array_merge( Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => Output::getContentDisposition( 'attachment', $attachment['attach_file'] ), "X-Content-Type-Options" => "nosniff" ) );

	/* Update download counter */
	Db::i()->update( 'core_attachments', "attach_hits=attach_hits+1", array( 'attach_id=?', $attachment['attach_id'] ) );
	
	/* If it's an AWS file just redirect to it */
	$file->originalFilename = $attachment['attach_file'];
	if ( $signedUrl = $file->generateTemporaryDownloadUrl() )
	{
		Output::i()->redirect( $signedUrl );
	}
	
	/* Send headers and print file */
	Output::i()->sendStatusCodeHeader( 200 );
	Output::i()->sendHeader( "Content-type: " . File::getMimeType( $file->originalFilename ) . ";charset=UTF-8" );

	Output::i()->sendHeader( "Content-Security-Policy: default-src 'none'; sandbox" );
	Output::i()->sendHeader( "X-Content-Security-Policy:  default-src 'none'; sandbox" );
	
	foreach( $headers as $key => $header )
	{
		Output::i()->sendHeader( $key . ': ' . $header );
	}
	Output::i()->sendHeader( "Content-Length: " . $file->filesize() );

	$file->printFile();
	exit;
}
catch (UnexpectedValueException | UnderflowException | ErrorException $e )
{
	switch( get_class( $e ) )
	{
		case 'UnexpectedValueException':
			$code = '2S328/2';
			break;
		
		case 'UnderflowException':
			$code = '2S328/1';
			break;
		
		case 'ErrorException':
			$code = '2C327/1';
			break;
	}
	/* Remove previously sent headers, so that the browser doesn't try to download this error as a file */
	header_remove();
	External::i();
	Output::i()->error( 'node_error', $code, 404, '' );
}