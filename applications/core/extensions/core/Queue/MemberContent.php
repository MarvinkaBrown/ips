<?php
/**
 * @brief		Background Task: Perform actions on all a member's content
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 May 2014
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ErrorException;
use IPS\Application;
use IPS\Content\Comment;
use IPS\Db\Exception;
use IPS\Extensions\QueueAbstract;
use IPS\IPS;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_NORMAL;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Delete or move content
 */
class MemberContent extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|NULL
	 */
	public function preQueueData( array $data ) : ?array
	{
		$classname = $data['class'];
		
		/* Check the app is enabled */
		if ( ! Application::appIsEnabled( $classname::$application ) )
		{
			return NULL;
		}
		
		/* Check the app supports what we're doing */
		if ( !$data['member_id'] and !isset( $classname::$databaseColumnMap['author_name'] ) )
		{
			return NULL;
		}
		if ( $data['action'] == 'hide' and !IPS::classUsesTrait( $classname, 'IPS\Content\Hideable' ) )
		{
			return NULL;
		}
		
		/* Get count - only do this if we're deleting member content, not guest content */
		if ( $data['member_id'] )
		{
			try
			{
				$data['count'] = $classname::db()->select( 'COUNT(*)', $classname::$databaseTable, static::_getWhere( $data ) )->first();
			}
			catch( Exception $e )
			{
				return NULL;
			}

			if ( !$data['count'] )
			{
				return NULL;
			}
		}
		
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
	public function run( array &$data, int $offset ): int
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$select = $classname::db()->select( '*', $classname::$databaseTable, static::_getWhere( $data ), $classname::$databasePrefix.$classname::$databaseColumnId, array( 0, REBUILD_NORMAL ) );
		
		/* Keep track of how many we did since we cannot rely on the count for guests */
		$did = 0;
		foreach ( new ActiveRecordIterator( $select, $classname ) as $item )
		{
			/* If this is the first comment on an item where a first comment is required (e.g. posts) do nothing, as when we get to the item, that will handle it */
			if ( $item instanceof Comment )
			{
				$itemClass = $item::$itemClass;
				if ( $itemClass::$firstCommentRequired and $item->isFirst() )
				{
					/* ... but we want to update the IP address of this post */
					if ( $data['action'] === 'merge' and empty( $data['merge_with_id'] ) )
					{
						try
						{
							$item->changeIpAddress( '' );
							$item->changeAuthor( new Member );
						}
						catch( OutOfRangeException $e ) {}
					}
						
					continue;
				}
			}
			
			/* Do the action... */
			try
			{
				switch ( $data['action'] )
				{
					case 'hide':
						$item->hide( isset( $data['initiated_by_member_id'] ) ? Member::load( $data['initiated_by_member_id'] ) : NULL );
						break;
						
					case 'delete':
						$item->delete( isset( $data['initiated_by_member_id'] ) ? Member::load( $data['initiated_by_member_id'] ) : NULL );
						break;
					
					case 'merge':
						$member = Member::load( $data['merge_with_id'] );
						
						if ( ! $data['merge_with_id'] and $data['merge_with_name'] )
						{
							$member->name = $data['merge_with_name'];
						}
						
						$item->changeAuthor( $member );
						
						if ( ! $data['merge_with_id'] )
						{
							$item->changeIpAddress( '' );
						}
						break;
				}
				
				$did++;
			}
			catch( OutOfRangeException |ErrorException $e )
			{
				$did++;
			}
		}
		
		if ( !$did )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		else if( isset( $data['count'] ) AND ( $offset + REBUILD_NORMAL >= $data['count'] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return ( $offset + REBUILD_NORMAL );
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
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new OutOfRangeException;
		}
		
		$member = Member::load( $data['member_id'] );
		if ( $member->member_id )
		{
			/* htmlsprintf is safe here because $member->link() uses a template */
			$sprintf = array( 'htmlsprintf' => array( $member->link(), Member::loggedIn()->language()->addToStack( $classname::$title . '_pl_lc' ) ) );
		}
		else
		{
			$sprintf = array( 'sprintf' => array( $data['name'], Member::loggedIn()->language()->addToStack( $classname::$title . '_pl_lc' ) ) );
		}
				
		$text = Member::loggedIn()->language()->addToStack( 'backgroundQueue_membercontent_' . $data['action'], FALSE, $sprintf );
		
		if ( !$data['member_id'] )
		{
			return array( 'text' => $text, 'complete' => NULL );
		}
		else
		{
			return array( 'text' => $text, 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
		}
	}
	
	/**
	 * Get where clause
	 *
	 * @param	array	$data
	 * @return	array
	 */
	protected static function _getWhere( array $data ) : array
	{
		/* @var array $databaseColumnMap */
		$classname = $data['class'];
		$where = array( array( $classname::$databasePrefix . $classname::$databaseColumnMap['author'] . '=?', $data['member_id'] ) );
		
		if ( !$data['member_id'] )
		{
			$where = array( $classname::$databasePrefix . $classname::$databaseColumnMap['author_name'] . '=?', $data['name'] );
		}
		
		if ( $data['action'] == 'hide' )
		{
			if ( isset( $classname::$databaseColumnMap['approved'] ) )
			{
				$where[] = array( $classname::db()->in( $classname::$databasePrefix . $classname::$databaseColumnMap['approved'], array( 0, 1 ) ) );
			}
			else
			{
				$where[] = array( $classname::db()->in( $classname::$databasePrefix . $classname::$databaseColumnMap['hidden'], array( 0, 1 ) ) );
			}
		}
		
		return $where;
	}
}