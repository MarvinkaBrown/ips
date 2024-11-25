<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		25 Apr 2023
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\Extensions\QueueAbstract;
use IPS\Log;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use OutOfRangeException;
use Throwable;
use function defined;
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
class RebuildKeywords extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_SLOW;
	
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|NULL
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];

		try
		{
			$where = ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? array( $classname::commentWhere() ) : array();
			if ( Settings::i()->stats_keywords_prune )
			{
				/* @var array $databaseColumnMap */
				$where[] = array( $classname::$databasePrefix . $classname::$databaseColumnMap['date'] . ">?", DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->stats_keywords_prune . 'D' ) )->getTimestamp() );
			}
			$data['count']		= $classname::db()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable, $where )->first();
			$data['realCount']	= $classname::db()->select( 'COUNT(*)', $classname::$databaseTable, $where )->first();
		    $data['runPid']		= $data['count'] + 1;
		}
		catch( Exception $ex )
		{
			throw new OutOfRangeException;
		}

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
	 * @param	array						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ) : int
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$where	 	= ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? ( is_array( $classname::commentWhere() ) ? $classname::commentWhere() : array() ) : array();
		$where[]	= array( $classname::$databasePrefix . $classname::$databaseColumnId . ' < ?',  $data['runPid'] );
		if ( Settings::i()->stats_keywords_prune )
		{
			/* @var array $databaseColumnMap */
			$where[] = array( $classname::$databasePrefix . $classname::$databaseColumnMap['date'] . ">?", DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->stats_keywords_prune . 'D' ) )->getTimestamp() );
		}
		$select   = $classname::db()->select( '*', $classname::$databaseTable, $where, $classname::$databasePrefix . $classname::$databaseColumnId . ' DESC', array( 0, $this->rebuild ) );
		$iterator = new ActiveRecordIterator( $select, $classname );
		$last     = NULL;

		foreach( $iterator as $item )
		{
			$idColumn = $classname::$databaseColumnId;
			$title = NULL;
			if ( is_subclass_of( $classname, 'IPS\Content\Item' ) )
			{
				$title = $item->mapped('title');
			}
			else if ( isset( $item::$itemClass ) )
			{
				$itemClass = $item::$itemClass;
				if ( isset( $itemClass::$firstCommentRequired ) AND $item->isFirst() )
				{
					$title = $item->item()->mapped('title');
				}
			}
			
			try
			{
				$item->checkKeywords( $item->content(), $title, ( isset( $classname::$databaseColumnMap['date'] ) ) ? $item->mapped('date') : NULL );
			}
			catch( Throwable $e )
			{
				Log::log( $e, 'keyword_rebuild' );
			}
			
			$last = $item->$idColumn;
			
			$data['indexed']++;
		}
		
		$data['runPid'] = $last;
			
		if( $last === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

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
	public function getProgress( array $data, int $offset ) : array
	{
		$class = $data['class'];
        $exploded = explode( '\\', $class );
        if ( !class_exists( $class ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new OutOfRangeException;
		}
		
		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_keywords', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title . '_pl_lc' ) ) ) ), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}
}