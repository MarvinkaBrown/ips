<?php
/**
 * @brief		Database Storage Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 May 2013
 */

namespace IPS\Data\Store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Cache;
use IPS\Data\Cache\None;
use IPS\Data\Store;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Session\Front;
use UnderflowException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Database Storage Class
 */
class Database extends Store
{
	/**
	 * Server supports this method?
	 *
	 * @return	bool
	 */
	public static function supported(): bool
	{
		return TRUE;
	}

	/**
	 * @brief	Always needed Store keys
	 */
	public array $initLoad = array( 'cacheKeys', 'settings', 'storageConfigurations', 'themes', 'languages', 'groups', 'applications', 'modules', 'widgets', 'furl', 'javascript_map', 'metaTags', 'bannedIpAddresses', 'license_data', 'furl_configuration', 'rssFeeds', 'frontNavigation', 'globalStreamIds', 'profileSteps' );
		
	/**
	 * @brief	Have we done the intitial load?
	 */
	protected bool $doneInitLoad = FALSE;
		
	/**
	 * @brief	Cache
	 */
	protected static array $cache = array();
	
	/**
	 * Constructor
	 * Gets stores which are always needed to save individual queries
	 *
	 * @return	void
	 */
	public function __construct()
	{
		/* When caching is enabled, we only need cacheKeys, the rest will come from the cache */
		if ( !Cache::i() instanceof None )
		{
			$this->initLoad = array( 'cacheKeys' );
		}
		/* Otherwise, we also need a few more things depending on whether the user is logged in or not */
		else
		{
			$this->initLoad[] = 'announcements';
			$this->initLoad[] = 'loginMethods'; # Previously, this was only loaded for Guests, however both Guests and Logged In members interface with login handlers (ex. profile syncing).
			$this->initLoad[] = 'widgets';
			$this->initLoad[] = 'defaultStreamData';
			$this->initLoad[] = 'acpNotifications';
			$this->initLoad[] = 'emoticons';

			$this->initLoad[] = 'administrators';
			$this->initLoad[] = 'moderators';
			$this->initLoad[] = 'group_promotions';
			$this->initLoad[] = 'promoters';

			/* things from other apps */
			$this->initLoad[] = 'nexusPackagesWithReviews';
			$this->initLoad[] = 'cms_menu';
			$this->initLoad[] = 'cms_databases';
			$this->initLoad[] = 'pages_page_urls';
		}
	}
	
	/**
	 * Load mutiple
	 * Used so if it is known that several are going to be needed, they can all be loaded into memory at the same time
	 *
	 * @param	array	$keys	Keys
	 * @return	void
	 */
	public function loadIntoMemory( array $keys ) : void
	{
		foreach ( Db::i()->select( '*', 'core_store', Db::i()->in( 'store_key', $keys ) ) as $row )
		{
			static::$cache[ $row['store_key'] ] = $row['store_value'];
		}
	}

	/**
	 * Abstract Method: Get
	 *
	 * @param string $key	Key
	 * @return	string	Value from the datastore
	 */
	public function get( string $key ) : string
	{
		if ( !$this->doneInitLoad and in_array( $key, $this->initLoad ) )
		{
			$this->loadIntoMemory( $this->initLoad );
			$this->doneInitLoad = TRUE;
		}
		
		if ( !isset( static::$cache[ $key ] ) )
		{
			try
			{
				static::$cache[ $key ] = Db::i()->select( 'store_value', 'core_store', array( 'store_key=?', $key ) )->first();
			}
			catch ( Exception $e )
			{
				throw new UnderflowException;
			}
		}
		
		return static::$cache[ $key ];
	}
	
	/**
	 * Abstract Method: Set
	 *
	 * @param string $key	Key
	 * @param string $value	Value
	 * @return	bool
	 */
	public function set( string $key, string $value ): bool
	{
		Db::i()->replace( 'core_store', array(
			'store_key'		=> $key,
			'store_value'	=> $value
		) );

		static::$cache[ $key ] = $value;

		return TRUE;
	}
	
	/**
	 * Abstract Method: Exists?
	 *
	 * @param string $key	Key
	 * @return	bool
	 */
	public function exists( string $key ): bool
	{
		if ( isset( static::$cache[ $key ] ) )
		{
			return TRUE;
		}
		else
		{
			try
			{
				$this->get( $key );
				return TRUE;
			}
			catch ( UnderflowException $e )
			{
				return FALSE;
			}
		}
	}
	
	/**
	 * Abstract Method: Delete
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	public function delete( string $key ): bool
	{
		if( isset( static::$cache[ $key ] ) )
		{
			unset( static::$cache[ $key ] );
		}

		Db::i()->delete( 'core_store', array( 'store_key=?', $key ) );
		return TRUE;
	}
	
	/**
	 * Abstract Method: Clear All Caches
	 *
	 * @param string|null $exclude	Key to exclude (keep)
	 * @return	void
	 */
	public function clearAll( string $exclude=NULL ) : void
	{
		$where = array();
		if( $exclude !== NULL )
		{
			$where[] = array( 'store_key != ?', $exclude );
		}
		Db::i()->delete( 'core_store', $where );

		foreach( static::$cache as $key => $value )
		{
			if( $exclude === NULL OR $key != $exclude )
			{
				unset( static::$cache[ $key ] );
			}
		}
	}
}