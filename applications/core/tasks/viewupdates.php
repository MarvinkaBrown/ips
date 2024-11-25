<?php
/**
 * @brief		view_updates Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Nov 2015
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\IPS;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Log;
use IPS\Platform\Bridge;
use IPS\Redis;
use IPS\Task;
use OutOfRangeException;
use Throwable;
use UnderflowException;
use function defined;
use function in_array;
use function intval;
use function is_array;
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
 * view_updates Task
 */
class viewupdates extends Task
{
	/**
	 * @breif Stored data to send to Cloud in one go
	 */
	protected array $_sendToCloud = [];

	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws    Task\Exception
	 */
	public function execute() : mixed
	{
		$this->runUntilTimeout(function(){
			$hasCount = FALSE;

			try
			{
				$database = Db::i()->select( 'classname, id, count(*) AS count', 'core_view_updates', NULL, NULL, 20, array( 'classname', 'id' ), NULL, Db::SELECT_FROM_WRITE_SERVER );
				
				/* Database results first */
				foreach( $database as $row )
				{
					$hasCount = TRUE;

					try
					{
						$this->update( $row['classname'], $row['id'], $row['count'] );
					}
					catch( OutOfRangeException $e )
					{
						Db::i()->delete( 'core_view_updates', array( 'classname=?', $row['classname'] ) );
					}
					
					Db::i()->delete( 'core_view_updates', array( 'classname=? AND id=?', $row['classname'], $row['id'] ) );
				}
			}
			catch ( UnderflowException $e ) { }

			/* Now do cloud */
			Bridge::i()->viewTask( $this->_sendToCloud );

			/* Now try redis */
			if ( Redis::isEnabled() )
			{
				try
				{
					$redis = Redis::i()->zRevRangeByScore( 'topic_views', '+inf', '-inf', array('withscores' => TRUE, 'limit' => array( 0, 20 ) ) );

					if( is_array( $redis ) and count( $redis ) )
					{
						foreach ( $redis as $data => $count )
						{
							$hasCount = TRUE;

							[ $class, $id ] = explode( '__', $data );

							try
							{
								$this->update( $class, $id, intval( $count ) );
							}
							catch ( OutOfRangeException $e ) {}
						}

						Redis::i()->zRem( 'topic_views', ...array_keys( $redis ) );
					}
				}
				catch( \Exception $e ) { }

				/* Now try advert impressions */
				try
				{
					$redis = Redis::i()->zRevRangeByScore( 'advert_impressions', '+inf', '-inf', array('withscores' => TRUE, 'limit' => array( 0, 20 ) ) );

					if( is_array( $redis ) )
					{
						$updates = [];
						foreach ( $redis as $id => $count )
						{
							$hasCount = TRUE;
							$updates[ $count ][] = $id;

							Redis::i()->zRem( 'advert_impressions', $id );
						}

						foreach ( $updates as $incrementBy => $ids )
						{
							Db::i()->update( 'core_advertisements', "ad_impressions=ad_impressions+" . $incrementBy, [ Db::i()->in( 'ad_id', $ids ) ] );
						}
					}
				}
				catch( \Exception $e ) { }
			}
			
			/* Go for another go? */
			if ( $hasCount === TRUE )
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		});

		return null;
	}
	
	/**
	 * Update the row
	 *
	 * @param	string	$class  Class to update
	 * @param	int		$id		ID of item
	 * @param	int		$count	Count to update
	 * @throws OutOfRangeException	When table to update no longer exists
	 */
	protected function update( string $class, int $id, int $count ) : void
	{

		if ( class_exists( $class ) and IPS::classUsesTrait( $class, 'IPS\Content\ViewUpdates' ) AND isset( $class::$databaseColumnMap['views'] ) )
		{
			try
			{
				$item = $class::load( $id );
				$title = '';
				/* If we specify a title specifically for analytics, use that always. */
				if ( method_exists( $item, 'titleForAnalytics' ) )
				{
					$title = $item->titleForAnalytics();
				}
				/* Otherwise use titleForLog for Nodes */
				else if ( in_array( 'IPS\Node\Model', class_parents( $item ) ) )
				{
					$title = $item->titleForLog();
				}
				/* Or the defined title column for content items. */
				else if ( in_array( 'IPS\Content\Item', class_parents( $item ) ) )
				{
					$title = $item->mapped('title');
				}
	
				$url = '';
				if ( method_exists( $item, 'url' ) )
				{
					$url = $item->url();
				}
	
				$this->_sendToCloud[] = [
					'contentClass'	=> $class,
					'contentId'		=> $id,
					'views'			=> $count,
					'title'			=> $title,
					'url'			=> (string) $url
				];
			}
			catch( Throwable $e )
			{
				Log::log( $e, 'views' );
			}
			
			try
			{
				Db::i()->update(
					$class::$databaseTable,
					"`{$class::$databasePrefix}{$class::$databaseColumnMap['views']}`=`{$class::$databasePrefix}{$class::$databaseColumnMap['views']}`+{$count}",
					array( "{$class::$databasePrefix}{$class::$databaseColumnId}=?", $id )
				);
			}
			catch( Exception $e )
			{
				/* Table to update no longer exists */
				if( $e->getCode() == 1146 )
				{
					throw new OutOfRangeException;
				}

				throw $e;
			}
		}
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}