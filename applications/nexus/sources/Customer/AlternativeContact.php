<?php
/**
 * @brief		Alternative Contact Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		09 May 2014
 */

namespace IPS\nexus\Customer;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Patterns\Bitwise;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Alternative Contact Model
 */
class AlternativeContact extends ActiveRecord
{	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'nexus_alternate_contacts';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = "";

	/**
	 * Get main account
	 *
	 * @return	Customer
	 */
	public function get_main_id() : Customer
	{
		return Customer::load( $this->_data['main_id'] );
	}
	
	/**
	 * Set main account
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function set_main_id( Member $member ) : void
	{
		$this->_data['main_id'] = $member->member_id;
	}
	
	/**
	 * Get alternate account
	 *
	 * @return	Customer
	 */
	public function get_alt_id() : Customer
	{
		return Customer::load( $this->_data['alt_id'] );
	}
	
	/**
	 * Set alternate account
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function set_alt_id( Member $member ) : void
	{
		$this->_data['alt_id'] = $member->member_id;
	}
	
	/**
	 * Get purchases
	 *
	 * @return	ActiveRecordIterator
	 */
	public function get_purchases() : ActiveRecordIterator
	{
		return new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', array( array( 'ps_member=?', $this->main_id->member_id ), Db::i()->in( 'ps_id', $this->purchaseIds() ) ) ), 'IPS\nexus\Purchase' );
	}
	
	/**
	 * Get purchase IDs
	 *
	 * @return	array
	 */
	public function purchaseIds() : array
	{
		return explode( ',', $this->_data['purchases'] );
	}
	
	/**
	 * Set purchases
	 *
	 * @param	array	$purchases	The purchases
	 * @return	void
	 */
	public function set_purchases( array $purchases ) : void
	{
		$this->_data['purchases'] = implode( ',', array_keys( $purchases ) );
	}

	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    ActiveRecord
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): ActiveRecord
	{
		/* Initiate an object */
		$classname = get_called_class();
		$obj = new $classname;
		$obj->_new  = FALSE;
		$obj->_data = array();

		/* Import data */
		foreach ( $data as $k => $v )
		{
			$obj->_data[ $k ] = $v;
		}

		$obj->changed = array();

		return $obj;
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		if ( $this->_new )
		{
			$data = $this->_data;
		}
		else
		{
			$data = $this->changed;
		}
		
		foreach ( array_keys( static::$bitOptions ) as $k )
		{
			if ( $this->$k instanceof Bitwise )
			{
				foreach( $this->$k->values as $field => $value )
				{
					$data[ $field ] = intval( $value );
				}
			}
		}
	
		if ( $this->_new )
		{
			if( static::$databasePrefix === NULL )
			{
				$insert = $data;
			}
			else
			{
				$insert = array();
				foreach ( $data as $k => $v )
				{
					$insert[ static::$databasePrefix . $k ] = $v;
				}
			}

			$insertId = static::db()->insert( static::$databaseTable, $insert );
			
			$this->_new = FALSE;
		}
		elseif( !empty( $data ) )
		{
			/* Set the column names with a prefix */
			if( static::$databasePrefix === NULL )
			{
				$update = $data;
			}
			else
			{
				$update = array();
				foreach ( $data as $k => $v )
				{
					$update[ static::$databasePrefix . $k ] = $v;
				}
			}
						
			/* Work out the ID */
			$idColumn = static::$databaseColumnId;

			/* Save */
			static::db()->update( static::$databaseTable, $update, array( 'main_id=? AND alt_id=?', $this->main_id->member_id, $this->alt_id->member_id ) );
			
			/* Reset our log of what's changed */
			$this->changed = array();
		}
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		static::db()->delete( 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', $this->_data['main_id'], $this->_data['alt_id'] ) );
	}
}