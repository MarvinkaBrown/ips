<?php
/**
 * @brief		Active Record Pattern
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Patterns;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Data\Store;
use IPS\Db;
use IPS\Db\Select;
use IPS\File;
use IPS\Helpers\CoverPhoto;
use IPS\Http\Url;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_called_class;
use function in_array;
use function intval;
use function is_array;
use function ord;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Active Record Pattern
 */
abstract class ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = '';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database table
	 * @note	This MUST be over-ridden
	 */
	public static ?string $databaseTable	= '';
		
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 * @note	If using this, declare a static $multitonMap = array(); in the child class to prevent duplicate loading queries
	 */
	protected static array $databaseIdFields = array();
	
	/**
	 * @brief	Bitwise keys
	 */
	protected static array $bitOptions = array();

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static array $multitons	= array();

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array();

	/**
	 * @brief	[ActiveRecord] Attempt to load from cache
	 * @note	If this is set to TRUE you should define a getStore() method to return the objects from cache
	 */
	protected static bool $loadFromCache = FALSE;
		
	/**
	 * @brief	[ActiveRecord] Database Connection
	 * @return	Db
	 */
	public static function db(): Db
	{
		return Db::i();
	}
	
	/**
	 * Load Record
	 *
	 * @param	int|string|null	$id					ID
	 * @param string|null $idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param mixed $extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details) - if used will cause multiton store to be skipped and a query always ran
	 * @return	static
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 *@see        Db::build
	 */
	public static function load( int|string|null $id, string $idField=NULL, mixed $extraWhereClause=NULL ): ActiveRecord|static
	{
		if( !$id )
		{
			throw new OutOfRangeException;
		}

		/* If we didn't specify an ID field, assume the default */
		if( $idField === NULL )
		{
			$idField = static::$databasePrefix . static::$databaseColumnId;
		}
		
		/* If we did, check it's valid */
		elseif( !in_array( $idField, static::$databaseIdFields ) )
		{
			throw new InvalidArgumentException;
		}

		/* Some classes can load directly from a cache, so check that first */
		if( static::$loadFromCache !== FALSE AND $idField === static::$databasePrefix . static::$databaseColumnId AND $extraWhereClause === NULL )
		{
			$cachedObjects = static::getStore();

			if ( isset( $cachedObjects[ $id ] ) )
			{
				return static::constructFromData( $cachedObjects[ $id ] );
			}
			else
			{
				throw new OutOfRangeException;
			}
		}
				
		/* Does that exist in the multiton store? */
		if ( !$extraWhereClause )
		{
			if( $idField === static::$databasePrefix . static::$databaseColumnId )
			{
				if ( !empty( static::$multitons[ $id ] ) )
				{
					return static::$multitons[ $id ];
				}
			}
			elseif ( isset( static::$multitonMap ) and isset( static::$multitonMap[ $idField ][ $id ] ) )
			{
				return static::$multitons[ static::$multitonMap[ $idField ][ $id ] ];
			}
		}
		
		/* Load it */
		try
		{
			$row = static::constructLoadQuery( $id, $idField, $extraWhereClause )->first();
		}
		catch ( UnderflowException $e )
		{
			throw new OutOfRangeException;
		}
		
		/* If it doesn't exist in the multiton store, set it */
		if( !isset( static::$multitons[ $row[ static::$databasePrefix . static::$databaseColumnId ] ] ) )
		{
			static::$multitons[ $row[ static::$databasePrefix . static::$databaseColumnId ] ] = static::constructFromData( $row );
		}
		if ( isset( static::$multitonMap ) )
		{
			foreach ( static::$databaseIdFields as $field )
			{
				if ( $row[ $field ] )
				{
					static::$multitonMap[ $field ][ $row[ $field ] ] = $row[ static::$databasePrefix . static::$databaseColumnId ];
				}
			}
		}
		
		/* And return it */
		return static::$multitons[ $row[ static::$databasePrefix . static::$databaseColumnId ] ];
	}

	/**
	 * Load record based on a URL
	 *
	 * @param	Url	$url	URL to load from
	 * @return	mixed
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function loadFromUrl( Url $url ): mixed
	{		
		if ( isset( $url->queryString['id'] ) )
		{
			return static::load( $url->queryString['id'] );
		}
		if ( isset( $url->hiddenQueryString['id'] ) )
		{
			return static::load( $url->hiddenQueryString['id'] );
		}
		
		throw new InvalidArgumentException;
	}

	/**
	 * Construct Load Query
	 *
	 * @param int|string $id					ID
	 * @param string $idField			The database column that the $id parameter pertains to
	 * @param	mixed		$extraWhereClause	Additional where clause(s)
	 * @return	Select
	 */
	protected static function constructLoadQuery( int|string $id, string $idField, mixed $extraWhereClause ): Select
	{
		$where = array( array( '`' . $idField . '`=?', $id ) );
		if( $extraWhereClause !== NULL )
		{
			if ( !is_array( $extraWhereClause ) or !is_array( $extraWhereClause[0] ) )
			{
				$extraWhereClause = array( $extraWhereClause );
			}
			$where = array_merge( $where, $extraWhereClause );
		}
		
		return static::db()->select( '*', static::$databaseTable, $where );
	}
			
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    ActiveRecord
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): ActiveRecord|static
	{
		/* Does that exist in the multiton store? */
		$obj = NULL;
		if ( isset( static::$databaseColumnId ) )
		{
			$idField = static::$databasePrefix . static::$databaseColumnId;
			$id = $data[ $idField ];
			
			if( isset( static::$multitons[ $id ] ) )
			{
				if ( !$updateMultitonStoreIfExists )
				{
					return static::$multitons[ $id ];
				}
				$obj = static::$multitons[ $id ];
			}
		}
		
		/* Initiate an object */
		if ( !$obj )
		{
			$classname = get_called_class();
			$obj = new $classname;
			$obj->_new  = FALSE;
			$obj->_data = array();
		}
		
		/* Import data */
		$databasePrefixLength = strlen( static::$databasePrefix );
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
			{
				$k = substr( $k, $databasePrefixLength );
			}

			$obj->_data[ $k ] = $v;
		}
		
		$obj->changed = array();
		
		/* Init */
		if ( method_exists( $obj, 'init' ) )
		{
			$obj->init();
		}
		
		/* If it doesn't exist in the multiton store, set it */
		if( isset( static::$databaseColumnId ) and !isset( static::$multitons[ $id ] ) )
		{
			static::$multitons[ $id ] = $obj;
		}
				
		/* Return */
		return $obj;
	}
	
	/**
	 * Get which IDs are already loaded
	 *
	 * @return	array
	 */
	public static function multitonIds(): array
	{
		if ( is_array( static::$multitons ) )
		{
			return array_keys( static::$multitons );
		}
		return array();
	}
	
	/**
	 * @brief	Data Store
	 */
	protected array $_data = array();
	
	/**
	 * @brief	Is new record?
	 */
	protected bool $_new = TRUE;
		
	/**
	 * @brief	Changed Columns
	 */
	public array $changed = array();
	
	/**
	 * Constructor - Create a blank object with default values
	 *
	 * @return	void
	 */
	public function __construct()
	{						
		$this->setDefaultValues();
	}
	
	/**
	 * Set Default Values (overriding $defaultValues)
	 *
	 * @return	void
	 */
	protected function setDefaultValues()
	{
		
	} 
		
	/**
	 * Get value from data store
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value from the datastore
	 */
	public function __get( mixed $key )
	{
		if( method_exists( $this, 'get_'.$key ) )
		{
			$method = 'get_' . $key;
			return $this->$method();
		}
		elseif( isset( $this->_data[ $key ] ) or isset( static::$bitOptions[ $key ] ) )
		{
			if ( isset( static::$bitOptions[ $key ] ) )
			{
				if ( !isset( $this->_data[ $key ] ) or !( $this->_data[ $key ] instanceof Bitwise ) )
				{
					$values = array();
					foreach ( static::$bitOptions[ $key ] as $k => $map )
					{
						$values[ $k ] = $this->_data[$k] ?? 0;
					}
					$this->_data[ $key ] = new Bitwise( $values, static::$bitOptions[ $key ], method_exists( $this, "setBitwise_{$key}" ) ? array( $this, "setBitwise_{$key}" ) : NULL );
				}
			}
			return $this->_data[ $key ];
		}
				
		return NULL;
	}
	
	/**
	 * Set value in data store
	 *
	 * @see		ActiveRecord::save
	 * @param	mixed	$key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( mixed $key, mixed $value )
	{
		if( method_exists( $this, 'set_'.$key ) )
		{
			$oldValues = $this->_data;
			
			$method = 'set_' . $key;
			$this->$method( $value );
						
			foreach( $this->_data as $k => $v )
			{				
				if( !array_key_exists( $k, $oldValues ) or ( $v instanceof Bitwise and !( $oldValues[ $k ] instanceof Bitwise) ) or $oldValues[ $k ] !== $v )
				{
					$this->changed[ $k ]	= $v;
				}
			}
			
			unset( $oldValues );
		}
		else
		{
			if ( !array_key_exists( $key, $this->_data ) or $this->_data[ $key ] !== $value )
			{
				$this->changed[ $key ] = $value;
			}
			
			$this->_data[ $key ] = $value;
		}
	}
	
	/**
	 * Is value in data store?
	 *
	 * @param	mixed	$key	Key
	 * @return	bool
	 */
	public function __isset( mixed $key )
	{
		if ( method_exists( $this, 'get_' . $key ) )
		{
			$method = 'get_' . $key;
			return $this->$method() !== NULL;
		}
		
		if ( isset( $this->_data[$key] ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * @brief	By default cloning will create a new ActiveRecord record, but if you truly want an object copy you can set this to TRUE first and a direct copy will be returned
	 */
	public bool $skipCloneDuplication = FALSE;

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$primaryKey = static::$databaseColumnId;
		$this->$primaryKey = NULL;
		
		$this->_new = TRUE;
		$this->save();
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
					if ( isset( $data[ $field ] ) or $this->$k->originalValues[ $field ] != intval( $value ) )
					{
						$data[ $field ] = intval( $value );
					}
				}
			}
		}

		if ( $this->_new )
		{
			$insert = array();
			if( static::$databasePrefix === NULL )
			{
				$insert = $data;
			}
			else
			{
				foreach ( $data as $k => $v )
				{
					$insert[ static::$databasePrefix . $k ] = $v;
				}
			}
			
			$insertId = static::db()->insert( static::$databaseTable, $insert );
			
			$primaryKey = static::$databaseColumnId;
			if ( $this->$primaryKey === NULL and $insertId )
			{
				$this->$primaryKey = $insertId;
			}
			
			$this->_new = FALSE;

			/* Reset our log of what's changed */
			$this->changed = array();

			static::$multitons[ $this->$primaryKey ] = $this;
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
						
			/* Save */
			static::db()->update( static::$databaseTable, $update, $this->_whereClauseForSave() );
			
			/* Reset our log of what's changed */
			$this->changed = array();
		}

		$this->clearCaches();
	}
	
	/**
	 * Get the WHERE clause for save()
	 *
	 * @return	array
	 */
	protected function _whereClauseForSave() : array
	{
		$idColumn = static::$databaseColumnId;
		return array( static::$databasePrefix . $idColumn . '=?', $this->$idColumn );
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		$idColumn = static::$databaseColumnId;
		static::db()->delete( static::$databaseTable, array( static::$databasePrefix . $idColumn . '=?', $this->$idColumn ) );

		$this->clearCaches(TRUE);
	}
	
	/**
	 * Cover Photo
	 *
	 * @return	mixed
	 */
	public function coverPhoto(): mixed
	{
        $photo = new CoverPhoto;
        if( $file = $this->coverPhotoFile() )
        {
            $photoOffset = static::$databaseColumnMap[ 'cover_photo_offset' ];
            $photo->file = $file;
            $photo->offset = $this->$photoOffset ?: 0;
        }
        $photo->editable = $this->canEdit();
        $photo->object = $this;
        return $photo;
	}

    /**
     * Returns the CoverPhoto File Instance or NULL if there's none
     *
     * @return null|File
     */
    public function coverPhotoFile(): ?File
    {
        if ( isset( static::$databaseColumnMap['cover_photo'] ) )
        {
			$photoCol = static::$databaseColumnMap[ 'cover_photo' ];
			if( $this->$photoCol )
			{
				return File::get( static::$coverPhotoStorageExtension, $this->$photoCol );
			}
        }
        return NULL;
    }
	
	/**
	 * Produce a random hex color for a background
	 *
	 * @return string
	 */
	public function coverPhotoBackgroundColor(): string
	{
		return '#' . dechex( mt_rand( 0x000000, 0xFFFFFF ) );
	}

	/**
	 * Return cover photo background color based on a string
	 *
	 * @param string $string	Some string to base background color on
	 * @return	string
	 */
	protected function staticCoverPhotoBackgroundColor(string $string ): string
	{
		$integer	= 0;

		for($i=0, $j= strlen($string); $i<$j; $i++ )
		{
			$integer = ord( substr( $string, $i, 1 ) ) + ( ( $integer << 5 ) - $integer );
			$integer = $integer & $integer;
		}

		return "hsl(" . ( $integer % 360 ) . ", 100%, 80% )";
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		return $this->coverPhotoFile();
	}

	/**
	 * Clear any defined caches
	 *
	 * @param bool $removeMultiton		Should the multiton record also be removed?
	 * @return void
	 */
	public function clearCaches( bool $removeMultiton=FALSE ) : void
	{
		if( count( $this->caches ) )
		{
			foreach( $this->caches as $cacheKey )
			{
				unset( Store::i()->$cacheKey );
			}
		}

		if( $removeMultiton === TRUE )
		{
			$idColumn = static::$databaseColumnId;

			if ( isset( static::$multitons[ $this->$idColumn ] ) )
			{
				unset( static::$multitons[ $this->$idColumn ] );

				if ( isset( static::$multitonMap ) )
				{
					foreach ( static::$databaseIdFields as $field )
					{
						if( isset( static::$multitonMap[ $field ] ) )
						{
							foreach( static::$multitonMap[ $field ] as $otherId => $mappedId )
							{
								if( $mappedId == $this->$idColumn )
								{
									unset( static::$multitonMap[ $field ][ $otherId ] );
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Attempt to load cached data
	 *
	 * @note	This should be overridden in your class if you define $cacheToLoadFrom
	 * @return    array
	 */
	public static function getStore(): array
	{
		return iterator_to_array( Db::i()->select( '*', static::$databaseTable, NULL, static::$databasePrefix . static::$databaseColumnId )->setKeyField( static::$databasePrefix . static::$databaseColumnId ) );
	}
}