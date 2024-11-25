<?php
/**
 * @brief		Pending Version Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		6 Apr 2020
 */

namespace IPS\downloads\File;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\Content\Filter;
use IPS\Content\Hideable;
use IPS\Content\Item;
use IPS\Db;
use IPS\Dispatcher;
use IPS\downloads\File;
use IPS\File as SystemFile;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function floatval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PendingVersion Model
 */
class PendingVersion extends Item implements Filter
{
	use Hideable
	{
		Hideable::unhide as public _unhide;
	}
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'downloads';

	/**
	 * @brief	Module
	 */
	public static string $module = 'downloads';

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'downloads_files_pending';

	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'pending_';

	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('pending_file_id');

	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'author'				=> 'member_id',
		'title'					=> 'name',
		'date'					=> 'date',
		'approved'				=> 'approved',
	);

	/**
	 * @brief	Title
	 */
	public static string $title = 'downloads_file_pending';

	/**
	 * @brief	Icon
	 */
	public static string $icon = 'download';

	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'downloads-file-pending';

	/**
	 * @brief   Used by the dataLayer
	 */
	public static string $contentType = 'file';

	/**
	 * Can unhide?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return  boolean
	 */
	public function canUnhide( ?Member $member=NULL ): bool
	{
		return File::modPermission( 'unhide', $member, $this->file()->containerWrapper() );
	}

	/**
	 * Can hide?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canHide( ?Member $member=NULL ): bool
	{
		return FALSE;
	}

	/**
	 * Can delete pending version?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( ?Member $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		if( !$member->member_id )
		{
			return FALSE;
		}

		if( !$this->author()->member_id == $member->member_id AND !$this->file()->canDeletePendingVersion( $member ) )
		{
			return FALSE;
		}

		return TRUE;
	}
	
	/**
	 * Returns the content
	 *
	 * @return	string|null
	 */
	public function content(): ?string
	{
		return $this->form_values['file_changelog'];
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();

	/**
	 * Pending URL
	 *
	 * @param string|null $action 	'do' action
	 * @return 	Url
	 */
	public function url( string $action=NULL ): Url
	{
		$url = Url::internal( "app=downloads&module=downloads&controller=pending&file_id={$this->file()->id}&id={$this->id}", 'front', 'downloads_file_pending', $this->file()->name_furl );

		if( $action )
		{
			$url = $url->setQueryString( 'do', $action );
		}

		return $url;
	}

	/**
	 * @brief	store decoded form values
	 */
	protected ?array $_formValues = NULL;

	/**
	 * Get form values decoded
	 *
	 * @return	array
	 */
	public function get_form_values(): array
	{
		if( $this->_formValues !== NULL )
		{
			return $this->_formValues;
		}

		return json_decode( $this->_data['form_values'], TRUE );
	}

	/**
	 * Getter for file name
	 *
	 * @return 	string
	 */
	public function get_name(): string
	{
		return $this->file()->mapped('title');
	}

	/**
	 * Get decoded record deletions
	 *
	 * @return 	array
	 */
	public function get_record_deletions(): array
	{
		return json_decode( $this->_data['record_deletions'], TRUE );
	}

	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->approved = 0;
	}

	/**
	 * Setter for download save version
	 *
	 * @return	void
	 */
	public function saveVersion() : void
	{
		$this->save_version = 1;
	}

	/**
	 * Set form values
	 *
	 * @param	array 	$values
	 */
	public function set_form_values( array $values ) : void
	{
		foreach( $values as $k => $v )
		{
			if( $v instanceof SystemFile )
			{
				$values[ $k ] = (string) $v;
			}
			elseif( is_array( $v ) )
			{
				foreach( $v as $_key => $_value )
				{
					if( $_value instanceof SystemFile )
					{
						$values[ $k ][ $_key ] = (string) $_value;
					}
				}
			}
		}

		$this->_formValues = $values;
		$this->_data['form_values'] = json_encode( $values );
	}

	/**
	 * Set record deletion IDs
	 *
	 * @param 	array 	$ids
	 */
	public function set_record_deletions( array $ids ) : void
	{
		$this->_data['record_deletions'] = json_encode( $ids );
	}

	/**
	 * Setter for download updated date
	 *
	 * @param int $value		New date
	 */
	public function set_updated( int $value ) : void
	{
		$this->date = $value;
	}

	/**
	 * Do Moderator Action
	 *
	 * @param	string				$action	The action
	 * @param	Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param	string|NULL			$reason	Reason (for hides)
	 * @param	bool				$immediately	Delete immediately
	 * @return	void
	 * @throws	OutOfRangeException|InvalidArgumentException|RuntimeException
	 */
	public function modAction( string $action, ?Member $member = NULL, mixed $reason = NULL, bool $immediately=FALSE ): void
	{
		/* We always want to immediately delete these records instead of leaving them soft deleted */
		if( $action === 'delete' )
		{
			$file = $this->file();
			$this->delete();

			/* Moderator log */
			Session::i()->modLog( 'modlog__action_newversion_reject', array( (string) $file->url() => FALSE, $file->name => FALSE ), $file );

			if( Dispatcher::hasInstance() AND !Request::i()->isAjax() )
			{
				Output::i()->redirect( $file->url() );
			}
			elseif( Request::i()->isAjax() )
			{
				Output::i()->json( 'OK' );
			}

			return;
		}
		elseif( $action == 'approve' OR $action == 'unhide' )
		{
			/* Moderator log */
			Session::i()->modLog( 'modlog__action_newversion_approved', array( (string) $this->file()->url() => FALSE, $this->file()->name => FALSE ), $this->file() );
			Webhook::fire( 'downloads_new_version_approved', $this );
		}

		parent::modAction( $action, $member, $reason, $immediately );
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		$ids = [];
		foreach( Db::i()->select( '*', 'downloads_files_records', [ 'record_file_id=? AND record_hidden=1', $this->file_id ], NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER ) as $record )
		{
			switch( $record['record_type'] )
			{
				case 'upload':
				case 'ssupload':
						$this->file()->deleteRecords( $record['record_id'], $record['record_location'], ( $record['record_type'] == 'ssupload' ) ? 'downloads_Screenshots' : 'downloads_Files' );

						try
						{
							if( $record['record_type'] == 'ssupload' AND !empty( $record['record_thumb'] )
								AND !Db::i()->select( 'COUNT(record_id)', 'downloads_files_records', [ 'record_id<>? AND record_thumb=?', $record['record_id'], $record['record_thumb'] ] )->first() )
							{
									SystemFile::get( 'downloads_Screenshots', $record['record_thumb'] )->delete();
							}
						}
						catch( Exception $e ){}
					break;
				case 'link':
				case 'sslink':
						$ids[] = $record['record_id'];
					break;
			}
		}

		if( count( $ids ) )
		{
			$this->file()->deleteRecords( $ids );
		}

		parent::delete();
	}

	/**
	 * Get related file object
	 *
	 * @return File
	 */
	public function file(): File
	{
		return File::load( $this->file_id );
	}

	/**
	 * Unhide
	 *
	 * @param	Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @return	void
	 */
	public function unhide( ?Member $member=NULL ) : void
	{
		$member = $member ?: Member::loggedIn();

		if( $this->save_version )
		{
			$this->file()->saveVersion();
		}

		/* Remove hidden flag */
		Db::i()->update( 'downloads_files_records', array( 'record_hidden' => 0 ), array( 'record_file_id=?', $this->file_id ) );

		$deletions = $this->record_deletions;
		$file = $this->file();
		array_walk( $deletions['records'], function( $arr, $key ) use ( $file ) {
			$file->deleteRecords( $key, $arr['url'], $arr['handler'] );
		});
		$file->deleteRecords( $deletions['links'] );

		foreach( $deletions['thumbs'] as $url )
		{
			try
			{
				SystemFile::get( 'downloads_Screenshots', $url )->delete();
			}
			catch( Exception $e ){}
		}

		$file->size = floatval( Db::i()->select( 'SUM(record_size)', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $file->id, 'upload' ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first() );

		/* Work out the new primary screenshot */
		try
		{
			$file->primary_screenshot = Db::i()->select( 'record_id', 'downloads_files_records', array( 'record_file_id=? AND ( record_type=? OR record_type=? ) AND record_backup=0 AND record_hidden=0', $file->id, 'ssupload', 'sslink' ), 'record_default DESC, record_id ASC', NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		}
		catch ( UnderflowException $e ) { }

		/* The container may not have versions enabled */
		if( !empty( $this->form_values['file_version'] ) )
		{
			$file->version = $this->form_values['file_version'];
		}

		$file->changelog = $this->content();
		$file->updated = time();
		$file->approver = $member->member_id;
		$file->approvedon = time();
		$file->published = time();
		$file->save();

		/* Saved form values for after new version processing */
		$formValues = $this->form_values;

		/* Delete pending record */
		$this->delete();

		/* Send notifications */
		if ( $file->open )
		{
			$file->sendApprovedNotification();
		}

		$file->processAfterNewVersion( $formValues );
	}

	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();

		SystemFile::claimAttachments( "downloads-{$this->file_id}-changelog", $this->file_id, $this->id, 'changelogpending' );
	}
}