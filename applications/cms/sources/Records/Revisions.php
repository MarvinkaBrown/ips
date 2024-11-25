<?php
/**
 * @brief		Revisions Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		29 April 2014
 */

namespace IPS\cms\Records;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Fields;
use IPS\cms\Records;
use IPS\Login;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Records Model
 */
class Revisions extends ActiveRecord
{
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'cms_database_revisions';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'revision_';

	/**
	 * @brief	Unpacked data
	 */
	protected ?array $_dataJson = NULL;
	
	/**
	 * Constructor - Create a blank object with default values
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		
		if ( $this->_new )
		{
			$this->member_id = Member::loggedIn()->member_id;
			$this->date      = time();
		}
	}
	
	/**
	 * Get a value by key
	 * 
	 * @param   string $key	Key of value to return
	 * @return	mixed
	 */
	public function get( string $key ) : mixed
	{
		if ( $this->_dataJson === NULL )
		{
			$this->_dataJson = $this->data;
		}
		
		if ( isset( $this->_dataJson[ $key ] ) )
		{
			return $this->_dataJson[ $key ];
		}
		
		return NULL;
	}

	/**
	 *  Compute differences
	 *
	 * @param int $databaseId     Database ID
	 * @param Records $record         Record
	 * @param boolean $justChanged    Get changed only
	 * @return array
	 */
	public function getDiffHtmlTables( int $databaseId, Records $record, bool $justChanged=FALSE ): array
	{
		$fieldsClass  = 'IPS\cms\Fields' .  $databaseId;
		/* @var $fieldsClass Fields */
		$customFields = $fieldsClass::data( 'view' );
		$conflicts    = array();

		/* Build up our data set */
		foreach( $customFields as $id => $field )
		{
			$key = 'field_' . $field->id;

			if( $justChanged === FALSE OR !Login::compareHashes( md5( $record->$key ), md5( $this->get( $key ) ) ) )
			{
				$conflicts[] = array( 'original' => $this->get( $key ), 'current' => $record->$key, 'field' => $field );
			}
		}

		return $conflicts;
	}

	/**
	 * Set the "data" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_data( string|array $value ) : void
	{
		$this->_data['data'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "data" field
	 *
	 * @return array
	 */
	public function get_data() : array
	{
		return json_decode( $this->_data['data'], TRUE );
	}
	
	
}