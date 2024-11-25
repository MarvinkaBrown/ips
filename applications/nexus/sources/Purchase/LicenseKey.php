<?php
/**
 * @brief		License Key Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		30 Apr 2014
 */

namespace IPS\nexus\Purchase;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\IPS;
use IPS\nexus\Purchase;
use IPS\Patterns\ActiveRecord;
use function count;
use function defined;
use function get_class;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * License Key Model
 */
abstract class LicenseKey extends ActiveRecord
{
	/**
	 * License Key Types
	 *
	 * @return	array
	 */
	public static function licenseKeyTypes() : array
	{
		$return = array(
			'Standard'	=> 'IPS\nexus\Purchase\LicenseKey\Standard',
			'Mdfive'	=> 'IPS\nexus\Purchase\LicenseKey\Mdfive',
		);

		/* Extensions */
		foreach ( Application::allExtensions( 'nexus', 'LicenseKey', FALSE, 'core' ) as $key => $extension )
		{
			$return[$extension::$keyType] = $extension::class;
		}

		return $return;
	}
		
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'nexus_licensekeys';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'lkey_';
	
	/**
	 * @brief	[ActiveRecord] Database ID Column
	 */
	public static string $databaseColumnId = 'key';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'lkey_purchase' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		$types		= static::licenseKeyTypes();
		$classname	= $types[ IPS::mb_ucfirst( $data['lkey_type'] ) ];

		/* Initiate an object */
		$obj = new $classname;
		$obj->_new = FALSE;
		
		/* Import data */
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
			{
				$k = substr( $k, strlen( static::$databasePrefix ) );
			}

			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();
		
		/* Init */
		if ( method_exists( $obj, 'init' ) )
		{
			$obj->init();
		}
				
		/* Return */
		return $obj;
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$exploded = explode( '\\', get_class( $this ) );
		$this->type = mb_strtolower( array_pop( $exploded ) );
		$this->active = TRUE;
		$this->uses = 0;
		$this->activate_data = array();
		$this->generated = new DateTime;
	}
	
	/**
	 * Set purchase
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function set_purchase( Purchase $purchase ) : void
	{
		$this->_data['purchase'] = $purchase->id;
		$this->_data['member'] = $purchase->member->member_id;
	}
	
	/**
	 * Set purchase
	 *
	 * @return	Purchase
	 */
	public function get_purchase() : Purchase
	{
		return Purchase::load( $this->_data['purchase'] );
	}
	
	/**
	 * Set activate data
	 *
	 * @param	array	$data	The data
	 * @return	void
	 */
	public function set_activate_data( array $data ) : void
	{
		$this->_data['activate_data'] = json_encode( $data );
	}
	
	/**
	 * Set activate data
	 *
	 * @return	array
	 */
	public function get_activate_data() : array
	{
		return json_decode( $this->_data['activate_data'], TRUE ) ?: array();
	}
	
	/**
	 * Set generated time
	 *
	 * @param	DateTime	$generated	Generated time
	 * @return	void
	 */
	public function set_generated( DateTime $generated ) : void
	{
		$this->_data['generated'] = $generated->getTimestamp();
	}
	
	/**
	 * Set generated time
	 *
	 * @return	DateTime
	 */
	public function get_generated() : DateTime
	{
		return DateTime::ts( $this->_data['generated'] );
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		if ( !$this->key )
		{
			do
			{
				$this->key = $this->generateKey();
			}
			while ( count( Db::i()->select( '*', 'nexus_licensekeys', array( 'lkey_key=?', $this->key ) ) ) );
		}
		parent::save();
	}
}