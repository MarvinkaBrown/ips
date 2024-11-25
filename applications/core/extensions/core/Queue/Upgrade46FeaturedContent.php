<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		07 Apr 2021
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Log;
use IPS\Member;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class Upgrade46FeaturedContent extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_SLOW;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];

		try
		{
			/* @var array $databaseColumnMap */
			$data['count']		= $classname::db()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable, [ $classname::$databasePrefix . $classname::$databaseColumnMap['featured'] . '=?', 1 ] )->first();
			$data['realCount']	= $classname::db()->select( 'COUNT(*)', $classname::$databaseTable, [ $classname::$databasePrefix . $classname::$databaseColumnMap['featured'] . '=?', 1 ] )->first();

			/* We're going to use the < operator, so we need to ensure the most recent item is rebuilt */
			$data['runId'] = $data['count'] + 1;
		}
		catch( Exception $ex )
		{
			throw new OutOfRangeException;
		}

		Log::debug( "PreQueue count for " . $classname . " is " . $data['count'], 'Upgrade46FeaturedContent' );

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

		/* @var array $databaseColumnMap */
		$select   = $classname::db()->select( '*', $classname::$databaseTable, [ $classname::$databasePrefix . $classname::$databaseColumnMap['featured'] . '=1 AND ' . $classname::$databasePrefix . $classname::$databaseColumnId . ' < ?',  $data['runId'] ], $classname::$databasePrefix . $classname::$databaseColumnId . ' DESC', array( 0, $this->rebuild ) );
		$last     = NULL;

		foreach( $select as $row )
		{
			try
			{
				Db::i()->delete( 'core_content_featured', [ 'feature_content_id=? and feature_content_class=?', $row[ $classname::$databasePrefix . $classname::$databaseColumnId ], $classname ] );
				Db::i()->insert( 'core_content_featured', [
					'feature_content_id'     => $row[ $classname::$databasePrefix . $classname::$databaseColumnId ],
					'feature_content_class'  => $classname,
					'feature_content_author' => $row[ $classname::$databaseColumnMap['author'] ],
					'feature_date'           => $row[ $classname::$databaseColumnMap['date'] ]
				] );
			}
			catch( Exception $e ){}

			$last = $row[ $classname::$databasePrefix . $classname::$databaseColumnId ];

			$data['indexed']++;
		}

		/* Store the runId for the next iteration of this Queue task. This allows the progress bar to show correctly. */
		$data['runId'] = $last;

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

		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_featured_stuff', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title . '_pl_lc' ) ) ) ), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}

	/**
	 * Perform post-completion processing
	 *
	 * @param	array	$data		Data returned from preQueueData
	 * @param	bool	$processed	Was anything processed or not? If preQueueData returns NULL, this will be FALSE.
	 * @return	void
	 */
	public function postComplete( array $data, bool $processed = TRUE ) : void
	{

	}
}