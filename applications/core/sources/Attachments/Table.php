<?php
/**
 * @brief		Table helper for attachments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		19 Jun 2018
 */

namespace IPS\core\Attachments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\extensions\core\EditorMedia\Attachment;
use IPS\Db;
use IPS\File;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use UnderflowException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Table Builder for attachments
 */
class Table extends \IPS\Helpers\Table\Db
{
	/**
	 * Constructor
	 *
	 * @param	string	$table						Database table
	 * @param	Url	$baseUrl			Base URL
	 * @param	array|null		$where				WHERE clause
	 * @param	array|null		$forceIndex			Index to force
	 * @return	void
	 */
	public function __construct( $table, Url $baseUrl, $where=NULL, $forceIndex=NULL )
	{
		/* Do any multi-mod */
		if ( isset( Request::i()->modaction ) )
		{
			$this->multiMod();
		}

		parent::__construct( $table, $baseUrl, $where, $forceIndex );
	}

	/**
	 * @brief	Return table filters
	 */
	public bool $showFilters	= TRUE;

	/**
	 * Saved Actions (for multi-moderation)
	 */
	public array $savedActions = array();

	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public function getFilters() : array
	{
		return array();
	}
	
	/**
	 * Does the user have permission to use the multi-mod checkboxes?
	 *
	 * @param	string|null		$action		Specific action to check (hide/unhide, etc.) or NULL for a generic check
	 * @return	bool
	 */
	public function canModerate( string $action=NULL ): bool
	{
		return (bool) Member::loggedIn()->group['gbw_delete_attachments'];
	}
	
	/**
	 * What multimod actions are available
	 *
	 * @return	array
	 */
	public function multimodActions() : array
	{
		return array( 'delete' );
	}
	
	/**
	 * Multimod
	 *
	 * @return	void
	 */
	protected function multimod() : void
	{
		if( !is_array( Request::i()->moderate ) )
		{
			return;
		}

		Session::i()->csrfCheck();

		foreach (Request::i()->moderate as $id => $status )
		{
			try
			{
				$attachment = Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $id ) )->first();

				/* Check it belongs to us */
				if ( $attachment['attach_member_id'] !== Member::loggedIn()->member_id )
				{
					Output::i()->error( 'no_module_permission', '2C388/1', 403, '' );
				}

				/* And we can delete it */
				if ( !Member::loggedIn()->group['gbw_delete_attachments'] )
				{
					Attachment::getLocations( $attachment['attach_id'] );
					if ( count( Attachment::$locations[ $attachment['attach_id'] ] ) )
					{
						Output::i()->error( 'no_module_permission', '2C388/2', 403, '' );
					}
				}

				/* Delete */
				try
				{
					File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
					if ( $attachment['attach_thumb_location'] )
					{
						File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
					}
				}
				catch ( Exception $e ) { }
				Db::i()->delete( 'core_attachments', array( 'attach_id=?', $attachment['attach_id'] ) );
				Db::i()->delete( 'core_attachments_map', array( 'attachment_id=?', $attachment['attach_id'] ) );
			}
			catch ( UnderflowException $e )
			{
				Output::i()->error( 'node_error', '2C388/3', 404, '' );
			}
		}

		Output::i()->redirect( Url::internal( 'app=core&module=system&controller=attachments', 'front', 'attachments' ), 'deleted' );
	}
}