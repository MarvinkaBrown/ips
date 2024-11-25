<?php
/**
 * @brief		Views Trait
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Events\Event;
use IPS\Redis;
use function defined;
use function get_called_class;
use const IPS\CACHE_CONFIG;
use const IPS\CACHE_METHOD;
use const IPS\REDIS_CONFIG;
use const IPS\REDIS_ENABLED;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Views Trait
 */
trait ViewUpdates
{
	/**
	 * Update View Count
	 *
	 * @return	void
	 */
	public function updateViews(): void
	{
		$idColumn = static::$databaseColumnId;
		$class = get_called_class();
		
		$countUpdated = false;
		if ( Redis::isEnabled() )
		{
			try
			{
				Redis::i()->zIncrBy( 'topic_views', 1, $class .'__' . $this->$idColumn );
				$countUpdated = true;
			}
			catch( Exception $e ) {}
		}

		if ( ! $countUpdated )
		{
			Db::i()->insert( 'core_view_updates', array(
					'classname'	=> $class,
					'id'		=> $this->$idColumn
			) );
		}

		Event::fire( 'onItemView', $this );
	}
}