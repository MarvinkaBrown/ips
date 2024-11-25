<?php
/**
 * @brief		Background Task: Rebuild Search Index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Aug 2014
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content\Search\Index;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Extensions\QueueAbstract;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use OutOfRangeException;
use Throwable;
use function defined;
use function in_array;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild Search Index
 */
class RebuildSearchIndex extends QueueAbstract
{
	/**
	 * @brief Number of content items to index per cycle
	 */
	public int $index	= REBUILD_QUICK;
	
	/**
	 * Build query
	 *
	 * @param	array	$data
	 * @return	array	array( 'where' => xxx, 'joins' => array() )
	 */
	protected function _buildQuery( array $data ) : array
	{
		$classname = $data['class'];
		
		$where = array();
		$joins = array();
		
		if ( isset( $data['container'] ) )
		{
			/* @var array $databaseColumnMap */
			if ( in_array( 'IPS\Content\Comment', class_parents( $classname ) ) )
			{
				$itemClass = $classname::$itemClass;
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . '=' . $data['container'] );
				$joins[ $itemClass::$databaseTable ] = $classname::$databasePrefix . $classname::$databaseColumnMap['item'] . '=' . $itemClass::$databasePrefix . $itemClass::$databaseColumnId;
			}
			else
			{
				$where[] = array( $classname::$databasePrefix . $classname::$databaseColumnMap['container'] . '=' . $data['container'] );
			}
		}

		if ( is_subclass_of( $classname, 'IPS\Content\Comment' ) AND $classname::commentWhere() !== NULL )
		{
			$where[] = $classname::commentWhere();
		}
		
		if( Settings::i()->search_method == 'mysql' and Settings::i()->search_index_timeframe )
		{
			$cutoff = DateTime::ts( time() - ( 86400 * Settings::i()->search_index_timeframe ) )->getTimestamp();

			/* If we store the time of the last comment / review, use that. If not, then use normal start date. */
			if ( isset( $classname::$databaseColumnMap['last_comment'] ) OR isset( $classname::$databaseColumnMap['last_review'] ) )
			{
				$columns	= array();
				$binds		= array();
				if ( isset( $classname::$databaseColumnMap['last_comment'] ) )
				{
					if ( is_array( $classname::$databaseColumnMap['last_comment'] ) )
					{
						foreach( $classname::$databaseColumnMap['last_comment'] AS $column )
						{
							$columns[]	= $classname::$databasePrefix . $column . " > ?";
							$binds[]	= $cutoff;
						}
					}
					else
					{
						$columns[]	= $classname::$databasePrefix . $classname::$databaseColumnMap['last_comment'] . " > ?";
						$binds[]	= $cutoff;
					}
				}

				if ( isset( $classname::$databaseColumnMap['last_review'] ) )
				{
					if ( is_array( $classname::$databaseColumnMap['last_review'] ) )
					{
						foreach( $classname::$databaseColumnMap['last_review'] AS $column )
						{
							$columns[]	= $classname::$databasePrefix . $column . " > ?";
							$binds[]	= $cutoff;
						}
					}
					else
					{
						$columns[]	= $classname::$databasePrefix . $classname::$databaseColumnMap['last_review'] . " > ?";
						$binds[]	= $cutoff;
					}
				}

				$where[] = array( '(' . implode( ' OR ', $columns ) . ')', ...$binds );
			}
			else if( isset( $classname::$databaseColumnMap['date'] ) )
			{
				$where[] = array( $classname::$databasePrefix . $classname::$databaseColumnMap['date'] . '> ?', $cutoff );
			}
		}
		
		return array( 'where' => $where, 'joins' => $joins );
	}

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];
		
		Log::debug( "Getting preQueueData for " . $classname, 'rebuildSearchIndex' );
		
		$queryData = $this->_buildQuery( $data );
		try
		{
			$select = Db::i()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable, $queryData['where'] );
			foreach ( $queryData['joins'] as $table => $on )
			{
				$select->join( $table, $on );
			}
			$data['count'] = $select->first();
			
			/* We're going to use the < operator, so we need to ensure the most recent item is indexed */
		    $data['runPid'] = $data['count'] + 1;
		    
			$select = Db::i()->select( 'COUNT(*)', $classname::$databaseTable, $queryData['where'] );
			foreach ( $queryData['joins'] as $table => $on )
			{
				$select->join( $table, $on );
			}
			$data['realCount'] = $select->first();
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
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		/* We want to allow read/write separation in this task */
		Db::i()->readWriteSeparation = TRUE;

		$classname = $data['class'];		
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$indexed = NULL;
		
		Log::debug( "Running " . $classname . ", with an offset of " . $offset, 'rebuildSearchIndex' );
		
		$queryData = $this->_buildQuery( $data );		
		
		$indexer = Index::massIndexer();
				
		/* A pages database may have been deleted */
		try
		{
			$select = Db::i()->select( '*', $classname::$databaseTable, array_merge( $queryData['where'], array( array( $classname::$databasePrefix . $classname::$databaseColumnId . ' < ?',  $data['runPid'] ) ) ), $classname::$databasePrefix . $classname::$databaseColumnId . ' DESC', array( 0, $this->index ) );
			foreach ( $queryData['joins'] as $table => $on )
			{
				$select->join( $table, $on );
			}
			
			try
			{
				$iterator = new ActiveRecordIterator( $select, $classname );
			
				foreach( $iterator as $item )
				{
					$idColumn = $classname::$databaseColumnId;
		
					try
					{
						$index = TRUE;
						if ( IPS::classUsesTrait( $item, 'IPS\Content\FuturePublishing' ) AND $item->isFutureDate() )
						{
							$index = FALSE;
						}
						if ( $index )
						{
							$indexer->index($item);
						}
					}
					catch( OutOfRangeException $e )
					{
						/* This can happen if there are older, orphaned posts/comments. Just do nothing here,
						don't even log it, because we end up with pages and pages of logs. */
					}
					catch ( Exception| Throwable $e )
					{
						/* There was an issue indexing the item - skip and log it */
						Log::log( $e, 'rebuildSearchIndex' );
					}
		
					$indexed = $item->$idColumn;
					
					/* Store the runPid for the next iteration of this Queue task. This allows the progress bar to show correctly. */
					$data['runPid'] = $item->$idColumn;
					$data['indexed']++;
				}
			}
			catch( OutOfRangeException $e )
			{
				/* Turn off read/write separation before returning */
				Db::i()->readWriteSeparation = FALSE;

				/* Something has gone wrong with iterator attempting to use constructFromData */
				throw new \IPS\Task\Queue\OutOfRangeException;
			}
		}
		catch( DbException $e )
		{
			/* Turn off read/write separation before returning */
			Db::i()->readWriteSeparation = FALSE;

			/* Something has gone wrong with the query, like the table not existing */
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		if( $indexed === NULL )
		{
			/* Turn off read/write separation before returning */
			Db::i()->readWriteSeparation = FALSE;

			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		/* Turn off read/write separation before returning */
		Db::i()->readWriteSeparation = FALSE;
				
		/* Return the number indexed so far, so that the rebuild progress bar text makes sense */
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
		
		return array( 'text' => Member::loggedIn()->language()->addToStack('reindexing_stuff', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title . '_pl_lc' ) ) ) ), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}	
}