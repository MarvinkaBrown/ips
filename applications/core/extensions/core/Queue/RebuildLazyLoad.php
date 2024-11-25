<?php
/**
 * @brief		Background Task: Rebuild posts to apply or remove lazy loading
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 July 2018
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Data\Store;
use IPS\Extensions\QueueAbstract;
use IPS\Log;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Text\Parser;
use OutOfRangeException;
use Throwable;
use function defined;
use function is_array;
use const IPS\REBUILD_NORMAL;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild posts to apply or remove lazy loading
 */
class RebuildLazyLoad extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_NORMAL;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];

		Log::debug( "Getting preQueueData for " . $classname, 'rebuildLazyLoad' );

		try
		{
			$data['count']		= $classname::db()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable, ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? $classname::commentWhere() : array() )->first();
			$data['realCount']	= $classname::db()->select( 'COUNT(*)', $classname::$databaseTable, ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? $classname::commentWhere() : array() )->first();

			/* We're going to use the < operator, so we need to ensure the most recent item is rebuilt */
		    $data['runPid'] = $data['count'] + 1;
		}
		catch( Exception $ex )
		{
			throw new OutOfRangeException;
		}

		Log::debug( "PreQueue count for " . $classname . " is " . $data['count'], 'rebuildLazyLoad' );

		if( $data['count'] == 0 )
		{
			return null;
		}

		$data['indexed']	= 0;

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		/* Make sure there's even content to parse */
		if( !isset( $classname::$databaseColumnMap['content'] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		Log::debug( "Running " . $classname . ", with an offset of " . $offset, 'rebuildLazyLoad' );

		$where	  = ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? ( is_array( $classname::commentWhere() ) ? array( $classname::commentWhere() ) : array() ) : array();
		$select   = $classname::db()->select( '*', $classname::$databaseTable, array_merge( $where, array( array( $classname::$databasePrefix . $classname::$databaseColumnId . ' < ?',  $data['runPid'] ) ) ), $classname::$databasePrefix . $classname::$databaseColumnId . ' DESC', array( 0, $this->rebuild ) );
		$iterator = new ActiveRecordIterator( $select, $classname );
		$last     = NULL;
		
		/* This could be a pages database that has since been deleted */
		try
		{
			foreach( $iterator as $item )
			{
				$idColumn = $classname::$databaseColumnId;
	
				/* Did the rebuild previously time out on this? If so we need to skip it and move along */
				if( isset( Store::i()->currentLazyLoadRebuild ) )
				{
					/* If the last rebuild cycle timed out, currentRebuild might be set and we might have already rebuilt this post (the post that caused the rebuild to fail might come after this (but before in chronological order)).
						If that is the case, we should skip rebuilding this post again. */
					if( is_array( Store::i()->currentLazyLoadRebuild ) AND Store::i()->currentLazyLoadRebuild[0] == $classname AND Store::i()->currentLazyLoadRebuild[1] < $item->$idColumn )
					{
						$last = $item->$idColumn;
						continue;
					}
	
					/* If the last rebuild cycle failed and we have just retrieved the post we last attempted to rebuild, skip it and move along */
					if( is_array( Store::i()->currentLazyLoadRebuild ) AND Store::i()->currentLazyLoadRebuild[0] == $classname AND Store::i()->currentLazyLoadRebuild[1] == $item->$idColumn )
					{
						unset( Store::i()->currentLazyLoadRebuild );
						$last = $item->$idColumn;
						continue;
					}
				}
	
				$contentColumn	= $classname::$databaseColumnMap['content'];
	
				/* Before we start trying to rebuild, set a flag to note what we are trying to rebuild. If it times out, we can check
					this on the next load and skip the problematic content */
				Store::i()->currentLazyLoadRebuild = array( $classname, $item->$idColumn );
	
				$item->$contentColumn = Parser::parseLazyLoad( $item->$contentColumn );
	
				try
				{
					$item->save();
				}
				/* Content item may be orphaned, continue if we cannot save it. */
				catch( OutOfRangeException $e ) {}
	
				$last = $item->$idColumn;
	
				$data['indexed']++;
	
				/* Now we will reset the rebuild flag we previously set since it rebuilt and saved successfully */
				unset( Store::i()->currentLazyLoadRebuild );
			}
		}
		catch( Throwable $e )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		/* Store the runPid for the next iteration of this Queue task. This allows the progress bar to show correctly. */
		$data['runPid'] = $last;

		if( $last === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		/* Return the number rebuilt so far, so that the rebuild progress bar text makes sense */
		return $data['indexed'];
	}

	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		$class = $data['class'];
        $exploded = explode( '\\', $class );
        if ( !class_exists( $class ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new OutOfRangeException;
		}

		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_lazyload_stuff', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title . '_pl_lc' ) ) ) ), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}
}