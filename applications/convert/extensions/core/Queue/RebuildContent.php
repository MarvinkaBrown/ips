<?php
/**
 * @brief		Background Task: Rebuild Content
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		10 Sep 2015
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\convert\App;
use IPS\Data\Store;
use IPS\Extensions\QueueAbstract;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
use IPS\Text\LegacyParser;
use OutOfRangeException;
use function defined;
use function get_class;
use function is_array;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RebuildContent extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_SLOW;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data	Data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];

		Log::debug( "Getting preQueueData for " . $classname, 'rebuildPosts' );

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

		Log::debug( "PreQueue count for " . $classname . " is " . $data['count'], 'rebuildPosts' );

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
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int					New offset or NULL if complete
	 * @throws    QueueOutOfRangeException    Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new QueueOutOfRangeException;
		}

		/* Make sure there's even content to parse */
		if( !isset( $classname::$databaseColumnMap['content'] ) )
		{
			throw new QueueOutOfRangeException;
		}

		/* Intentionally no try/catch as it means app doesn't exist */
		try
		{
			$app = App::load( $data['app'] );
		}
		catch( OutOfRangeException $e )
		{
			throw new QueueOutOfRangeException;
		}
		
		$softwareClass = $app->getSource( FALSE, FALSE );

		Log::debug( "Running " . $classname . ", with an offset of " . $offset, 'rebuildPosts' );

		$where	  = ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? ( is_array( $classname::commentWhere() ) ? array( $classname::commentWhere() ) : array() ) : array();
		$select   = $classname::db()->select( '*', $classname::$databaseTable, array_merge( $where, array( array( $classname::$databasePrefix . $classname::$databaseColumnId . ' < ?',  $data['runPid'] ) ) ), $classname::$databasePrefix . $classname::$databaseColumnId . ' DESC', array( 0, $this->rebuild ) );
		$iterator = new ActiveRecordIterator( $select, $classname );
		$last     = NULL;

		foreach( $iterator as $item )
		{
			$idColumn = $classname::$databaseColumnId;

			/* Is this converted content? */
			try
			{
				/* Just checking, we don't actually need anything */
				$app->checkLink( $item->$idColumn, $data['link'] );
			}
			catch( OutOfRangeException $e )
			{
				$last = $item->$idColumn;
				continue;
			}

			/* Did the rebuild previously time out on this? If so we need to skip it and move along */
			if( isset( Store::i()->currentRebuild ) )
			{
				/* If the last rebuild cycle timed out, currentRebuild might be set and we might have already rebuilt this post (the post that caused the rebuild to fail might come after this (but before in chronological order).
					If that is the case, we should skip rebuilding this post again. */
				if( is_array( Store::i()->currentRebuild ) AND Store::i()->currentRebuild[0] == $classname AND Store::i()->currentRebuild[1] < $item->$idColumn )
				{
					$last = $item->$idColumn;
					continue;
				}

				/* If the last rebuild cycle failed and we have just retrieved the post we last attempted to rebuild, skip it and move along */
				if( is_array( Store::i()->currentRebuild ) AND Store::i()->currentRebuild[0] == $classname AND Store::i()->currentRebuild[1] == $item->$idColumn )
				{
					unset( Store::i()->currentRebuild );
					$last = $item->$idColumn;
					continue;
				}
			}

			$member     = Member::load( $item->mapped('author') );

			if( isset( $classname::$itemClass ) )
			{
				$itemClass	= $classname::$itemClass;
				$module		= IPS::mb_ucfirst( $itemClass::$module );
			}
			else
			{
				$module     = IPS::mb_ucfirst( $classname::$module );
			}

			$contentColumn	= $classname::$databaseColumnMap['content'];

			/* Figure out ids for attachments */
			if( isset( $classname::$itemClass ) )
			{
				$itemIdColumn = $itemClass::$databaseColumnId;
				$module		= $itemClass::$module;

				try
				{
					$id1 = $item->item()->$itemIdColumn;
				}
				catch ( OutOfRangeException $e )
				{
					/* Post is orphaned and can be skipped */
					$last = $item->$idColumn;
					continue;
				}

				$id2 = $item->$idColumn;
			}
			else
			{
				$id1 = $item->$idColumn;
				$id2 = 0;
				$module	= $classname::$module;
			}

			/* Before we start trying to rebuild, set a flag to note what we are trying to rebuild. If it times out, we can check
				this on the next load and skip the problematic content */
			Store::i()->currentRebuild = array( $classname, $item->$idColumn );

			try
			{
				/* Pass off to software class */
				if ( $softwareClass::$contentFixed === FALSE )
				{
					$item->$contentColumn	= $softwareClass::fixPostData( $item->$contentColumn, $data['class'], $item->$idColumn );
				}

				/* Then pass off to legacy parser to bring up to 4.x */
				$item->$contentColumn	= LegacyParser::parseStatic( $item->$contentColumn, $member, false, $classname::$application . '_' . IPS::mb_ucfirst( $module ), $id1, $id2, NULL, $classname::$itemClass ?? get_class( $item ) );
			}
			catch( InvalidArgumentException $e )
			{
				if( $e->getcode() == 103014 )
				{
					$item->$contentColumn	= preg_replace( "#\[/?([^\]]+?)\]#", '', $item->$contentColumn );
				}
				else
				{
					throw $e;
				}
			}

			$item->save();

			$last = $item->$idColumn;

			$data['indexed']++;

			/* Now we will reset the rebuild flag we previously set since it rebuilt and saved successfully */
			unset( Store::i()->currentRebuild );
		}

		/* Store the runPid for the next iteration of this Queue task. This allows the progress bar to show correctly. */
		$data['runPid'] = $last;

		if( $last === NULL )
		{
			throw new QueueOutOfRangeException;
		}

		/* Return the number rebuilt so far, so that the rebuild progress bar text makes sense */
		return $data['indexed'];
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array	Text explaining task and percentage complete
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

		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_stuff', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title . '_pl', FALSE, array( 'strtolower' => TRUE ) ) ) ) ), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}
}