<?php

/**
 * @brief        DelayedCount
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        7/23/2024
 */

namespace IPS\Node;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Redis;
use OutOfRangeException;
use BadMethodCallException;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

trait DelayedCount
{
	/**
	 * @return string
	 */
	protected function getStorageId() : string
	{
		return get_called_class() . '_' . $this->_id;
	}

	/**
	 * @var bool Prevent loops continually setting the update flag
	 */
	public bool $inDelayedCount = FALSE;

	/**
	 * Store the update time in Redis so that we know it needs to be
	 * synced later
	 *
	 * @return void
	 */
	public function storeUpdateTime() : void
	{
		if( $this->inDelayedCount )
		{
			return;
		}

		/* Check Redis to see if we already have this node stored.
			If not, set it with a timestamp. */
		try
		{
			if( ! Redis::isEnabled() )
			{
				throw new BadMethodCallException;
			}

			/* hGet will return a false value if it doesn't exist, or the key doesn't exist in the Redis database */
			$timestamp = Redis::i()->hGet( 'nodeSyncTimes', $this->getStorageId() );

			if( empty( $timestamp ) )
			{
				Redis::i()->hSet( 'nodeSyncTimes', $this->getStorageId(), time() );

				/* Only store for 2 hours maximum, if it's not updated by then, then it's not going to be */
				Redis::i()->expire( 'nodeSyncTimes', ( 2 * 3600 ) );
			}
		}
		catch( BadMethodCallException )
		{
			try
			{
				$key = 'nodeSyncTimes';
				$timestamps = Store::i()->$key;
			}
			catch( OutOfRangeException )
			{
				$timestamps = [];
			}

			if( !array_key_exists( $this->getStorageId(), $timestamps ) )
			{
				$timestamps[ $this->getStorageId() ] = time();
				Store::i()->$key = $timestamps;
			}
		}

		/* Bubble up to parent nodes */
		try
		{
			$this->parent()?->storeUpdateTime();
		}
		catch( \OutOfRangeException )
		{}
	}

	/**
	 * Remove the node from Redis
	 *
	 * @return void
	 */
	public function clearUpdateTime() : void
	{
		try
		{
			if( ! Redis::isEnabled() )
			{
				throw new BadMethodCallException;
			}

			Redis::i()->hDel( 'nodeSyncTimes', $this->getStorageId() );
		}
		catch( BadMethodCallException | RedisException )
		{
			try
			{
				$key = 'nodeSyncTimes';

				/* @var array $timestamps */
				$timestamps = Store::i()->$key;
				if( array_key_exists( $this->getStorageId(), $timestamps ) )
				{
					unset( $timestamps[ $this->getStorageId() ] );
					Store::i()->$key = $timestamps;
				}
			}
			catch( OutOfRangeException ){}
		}
	}

	/**
	 * Run the rebuild methods
	 *
	 * @return void
	 */
	public function runScheduledRebuild(): void
	{
		/* Recount items, comments, and reviews */
		$this->recount();

		/* Set flag so we don't cause a loop */
		$this->inDelayedCount = TRUE;

		/* Force a reset of the last comment */
		$this->setLastComment();
	}

	/**
	 * Count all comments, items, etc
	 *
	 * @return void
	 */
	abstract protected function recount() : void;
}
