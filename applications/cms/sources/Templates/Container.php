<?php
/**
 * @brief		Templates Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		25 Feb 2014
 */

namespace IPS\cms\Templates;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Http\Url\Friendly;
use IPS\Patterns\ActiveRecord;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Template Model
 */
class Container extends ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'container_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Table
	 */
	public static ?string $databaseTable = 'cms_containers';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'container_key' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	Have fetched all?
	 */
	protected static bool $gotAll = FALSE;
	
	/**
	 * Return all containers
	 *
	 * @return	array
	 */
	public static function containers() : array
	{
		if ( ! static::$gotAll )
		{
			foreach( Db::i()->select( '*', static::$databaseTable ) as $container )
			{
				static::$multitons[ $container['container_id'] ] = static::constructFromData( $container );
			}
			
			static::$gotAll = true;
		}
		
		return static::$multitons;
	}
	
	/**
	 * Get all containers by type
	 * 
	 * @param string $type		Type of container (template_block, page, etc)
	 * @return array	of Container objects
	 */
	public static function getByType( string $type ) : array
	{
		$return = array();
		static::containers();
		
		if ( $type === 'database' )
		{
			$type = 'dbtemplate';
		}
		
		foreach( static::$multitons as $id => $obj )
		{
			if ( $obj->type === $type )
			{
				$return[] = $obj;
			}
		}
		
		return $return;
	}
	
	/**
	 * Add a new container
	 *
	 * @param	array	$container	Template Data
	 * @return	static
	 */
	public static function add( array $container ) : static
	{
		$newContainer = new static;
		$newContainer->_new = TRUE;
		$newContainer->name = $container['name'];
		$newContainer->type = $container['type'];
		$newContainer->save();
	
		/* Create a unique key */
		if ( empty( $newContainer->key ) )
		{
			$newContainer->key = 'template__' . Friendly::seoTitle( $newContainer->name ) . '.' . $newContainer->id;
			$newContainer->save();
		}
		
		return $newContainer;
	}
}