<?php
/**
 * @brief		Pending Version Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		8 Apr 2020
 */

namespace IPS\downloads\modules\front\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Controller;
use IPS\Db;
use IPS\downloads\File\PendingVersion;
use IPS\downloads\File as FileClass;
use IPS\File;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Pending Version Controller
 */
class pending extends Controller
{
	/**
	 * [Content\Controller]    Class
	 */
	protected static string $contentModel = PendingVersion::class;

	/**
	 * @brief	Storage for loaded file
	 */
	protected ?FileClass $file = NULL;

	/**
	 * @brief	Storage for loaded version
	 */
	protected ?PendingVersion $version = NULL;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		try
		{
			$this->file = FileClass::load( Request::i()->file_id );
			$this->version = PendingVersion::load( Request::i()->id );

			if( $this->file->id !== $this->version->file()->id )
			{
				throw new OutOfRangeException;
			}

			if ( !$this->version->canUnhide() AND !$this->version->canDelete())
			{
				Output::i()->error( 'node_error', '2D417/1', 404, '' );
			}
		}
		catch ( OutOfRangeException $e )
		{
			/* The version does not exist, but the file does. Redirect there instead. */
			if( isset( $this->file ) )
			{
				Output::i()->redirect( $this->file->url() );
			}

			Output::i()->error( 'node_error', '2D417/2', 404, '' );
		}

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_view.js', 'downloads', 'front' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_pending.js', 'downloads', 'front' ) );

		parent::execute();
	}

	/**
	 * Execute
	 *
	 * @return	mixed
	 */
	public function manage() : mixed
	{
		/* Display */
		Output::i()->title = $this->file->name;

		$container = $this->file->container();
		foreach ( $container->parents() as $parent )
		{
			Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
		}
		Output::i()->breadcrumb[] = array( $container->url(), $container->_title );

		Output::i()->breadcrumb[] = array( $this->file->url(), $this->file->name );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('pending_version') );

		Output::i()->output = Theme::i()->getTemplate( 'view' )->pendingView( $this->file, $this->version );
		return null;
	}

	/**
	 * Download a file
	 *
	 * @return	void
	 */
	public function download() : void
	{
		try
		{
			$record = Db::i()->select( '*', 'downloads_files_records', array( 'record_id=? AND record_file_id=?', Request::i()->fileId, $this->file->id ) )->first();
		}
		catch( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2D417/3', 404, '' );
		}

		/* Download */
		if ( $record['record_type'] === 'link' )
		{
			Output::i()->redirect( $record['record_location'] );
		}
		else
		{
			$file = File::get( 'downloads_Files', $record['record_location'] );
			$file->originalFilename = $record['record_realname'] ?: $file->originalFilename;
		}

		/* If it's an AWS file just redirect to it */
		try
		{
			if ( $signedUrl = $file->generateTemporaryDownloadUrl() )
			{
				Output::i()->redirect( $signedUrl );
			}
		}
		catch( UnexpectedValueException $e )
		{
			Log::log( $e, 'downloads' );
			Output::i()->error( 'generic_error', '3D417/5', 500, '' );
		}

		/* Send headers and print file */
		Output::i()->sendStatusCodeHeader( 200 );
		Output::i()->sendHeader( "Content-type: " . File::getMimeType( $file->originalFilename ) . ";charset=UTF-8" );
		Output::i()->sendHeader( "Content-Security-Policy: default-src 'none'; sandbox" );
		Output::i()->sendHeader( "X-Content-Security-Policy:  default-src 'none'; sandbox" );
		Output::i()->sendHeader( 'Content-Disposition: ' . Output::getContentDisposition( 'attachment', $file->originalFilename ) );
		Output::i()->sendHeader( "Content-Length: " . $file->filesize() );

		$file->printFile();
		exit;
	}

	/**
	 * Moderate
	 *
	 * @return	void
	 */
	protected function moderate(): void
	{
		if( $this->file->hidden() === 1 AND Request::i()->action == 'unhide' )
		{
			Output::i()->error( 'file_version_pending_cannot_approve', '2D417/4', 403, '' );
		}

		parent::moderate();
	}

	/**
	 * Method used to allow pending version approval for authors
	 *
	 * @return void
	 */
	protected function delete() : void
	{
		if( !$this->version->canDelete())
		{
			Output::i()->error( 'file_version_pending_cannot_delete', '2D417/6', 403, '' );
		}
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		$this->version->delete();
		Session::i()->modLog( 'modlog__action_deletedpending', array( (string) $this->file->url() => FALSE, $this->file->name => FALSE ), $this->file );
		Output::i()->redirect( $this->file->url() , 'deleted');
	}
}