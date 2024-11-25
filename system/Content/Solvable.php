<?php
/**
 * @brief		Solvable Trait
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2020
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\Content\Search\Index;
use IPS\Db;
use IPS\Member;
use IPS\Notification;
use OutOfRangeException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Solvable Trait
 */
trait Solvable
{
	/**
	 * Container has solvable enabled
	 *
	 * @return	bool
	 */
	abstract public function containerAllowsSolvable(): bool;
	
	/**
	 * Container has solvable enabled
	 *
	 * @return	bool
	 */
	abstract public function containerAllowsMemberSolvable(): bool;
	
	/**
	 * Any container has solvable enabled?
	 *
	 * @return	boolean
	 */
	abstract public static function anyContainerAllowsSolvable(): bool;
	
	/**
	 * Toggle the solve value of a comment
	 *
	 * @param 	int		$commentId	The comment ID
	 * @param 	boolean	$value		TRUE/FALSE value
	 * @param	Member|null	$member	The member (null for currently logged in member)
	 *
	 * @return	void
	 */
	public function toggleSolveComment( int $commentId, bool $value, ?Member $member = NULL ): void
	{
		$member = $member ?: Member::loggedIn();
		
		$commentClass = static::$commentClass;
		$commentIdField = $commentClass::$databaseColumnId;
		$idField = static::$databaseColumnId;
		$solvedField = static::$databaseColumnMap['solved_comment_id'];
		
		$comment = $commentClass::load( $commentId );
		$comment->setSolved( $value );
		$comment->save();
		
		if ( $value )
		{
			if ( $this->$solvedField )
			{
				try 
				{
					$oldComment = $commentClass::load( $this->$solvedField );
					$oldComment->setSolved( FALSE );
					$oldComment->save();
					
					Db::i()->delete( 'core_solved_index', array( 'comment_class=? AND comment_id=? AND type=?', $commentClass, $oldComment->$commentIdField, 'solved' ) );
				}
				catch( Exception ) { }
			}
			
			$this->$solvedField = $comment->$commentIdField;
			$this->save();
		
			Db::i()->insert( 'core_solved_index', array(
				'member_id' => (int) $comment->author()->member_id,
				'app'	=> $commentClass::$application,
				'comment_class' => $commentClass,
				'comment_id' => $comment->$commentIdField,
				'item_id'	 => $this->$idField,
				'solved_date' => time(),
				'type' => 'solved',
				'node_id' => $comment->item()->container()->_id
			) );

			/* Send the "solution to your topic" notification but only if we didn't post the solution, we're not marking the solution, we can view the content, and the user isn't ignored */
			if ( $this->author()->member_id AND $comment->author() != $this->author() AND $this->author() !== $member AND $this->canView( $this->author() ) AND !$this->author()->isIgnoring( $comment->author(), 'posts' ) )
			{
				$notification = new Notification( Application::load('core'), 'mine_solved', $this, array( $this, $comment, $member ), array(), TRUE, NULL );
				$notification->recipients->attach( $this->author() );
				$notification->send();
			}

			/* Send the "you solved the topic" notification but only if we didn't mark the solution */
			if ( $comment->author()->member_id AND $comment->author() != $member )
			{
				$notification = new Notification( Application::load('core'), 'my_solution', $this, array( $this, $comment, $member ), array(), TRUE, NULL );
				$notification->recipients->attach( $comment->author() );
				$notification->send();
			}

			$payload = [
				'item' => $this,
				'comment' => $comment,
				'markedBy' => $member
			];
			Webhook::fire( 'content_marked_solved', $payload );
		}
		else
		{
			$this->$solvedField = 0;
			$this->save();
		
			Db::i()->delete( 'core_solved_index', array( 'comment_class=? and comment_id=? AND type=?', $commentClass, $comment->$commentIdField, 'solved' ) );

			$memberIds	= array();

			foreach( Db::i()->select( '`member`', 'core_notifications', array( Db::i()->in( 'notification_key', array( 'mine_solved', 'my_solution' ) ) . ' AND item_class=? AND item_id=?', get_class( $this ), (int) $this->$idField ) ) as $memberToRecount )
			{
				$memberIds[ $memberToRecount ]	= $memberToRecount;
			}

			Db::i()->delete( 'core_notifications', array( Db::i()->in( 'notification_key', array( 'mine_solved', 'my_solution' ) ) . ' AND item_class=? AND item_id=?', get_class( $this ), (int) $this->$idField ) );

			foreach( $memberIds as $memberToRecount )
			{
				Member::load( $memberToRecount )->recountNotifications();
			}
		}

		/* Update search index */
		Index::i()->index( $comment );
	}
	
	/**
	 * Query to get additional data for search result / stream view
	 *
	 * @param	array	$items	Item data (will be an array containing values from basicDataColumns())
	 * @return	array
	 */
	public static function searchResultExtraData( array $items ): array
	{
		$itemIds = array();
		$idField = static::$databaseColumnId;
		
		foreach ( $items as $item )
		{
			if ( $item[ $idField ] )
			{
				$itemIds[ $item[ $idField ] ] = $item[ $idField ];
			}
		}

		if ( count( $itemIds ) )
		{
			foreach ( static::getItemsWithPermission( array(array( $idField . ' IN(' . implode( ',', $itemIds ) . ')') ), NULL, NULL ) as $row )
			{
				$items[ $row->$idField ]['solved'] = $row->isSolved();
			}

			return $items;
		}

		return array();
	}
	
	/**
	 * Is this topic a "best answer" and solved?
	 *
	 * @return	bool
	 */
	public function isSolved(): bool
	{
		return ( ( $this->containerAllowsMemberSolvable() OR $this->containerAllowsSolvable() ) and $this->mapped('solved_comment_id') );
	}

	/**
	 * Is this a non-admin/mod but can solve this item?
	 *
	 * @param Member|null $member The member (null for currently logged in member)
	 * @return boolean
	 */
	public function isNotModeratorButCanSolve( ?Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		
		if ( $this->canSolve( $member ) and $member->member_id === $this->author()->member_id and $this->containerAllowsMemberSolvable() and ! $member->modPermissions() )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Can user solve this item?
	 *
	 * @param Member|null $member The member (null for currently logged in member)
	 * @return    bool
	 */
	public function canSolve( ?Member $member = NULL ): bool
	{
		if( !$this->actionEnabled( 'solve', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();
		
		if( isset( static::$archiveClass ) AND method_exists( $this, 'isArchived' ) AND $this->isArchived() )
		{
			return FALSE;
		}

		/* If we have no replies, it's not solvable yet */
		if( $this->commentCount() <= 0 OR ( static::$firstCommentRequired AND $this->commentCount() == 1 ) )
		{
			return false;
		}
		
		if ( $this->containerAllowsSolvable() )
		{
			if ( $member->member_id === $this->author()->member_id and $this->containerAllowsMemberSolvable() )
			{
				return TRUE;
			}
		}
		else
		{
			return FALSE;
		}
		
		/* Or if we're a moderator */
		$container = $this->container();
		if ( isset( $container::$modPerm ) )
		{
			if
			(
				$member->modPermission( 'can_set_best_answer' )
				and
				(
					( $member->modPermission( $container::$modPerm ) === TRUE or $member->modPermission( $container::$modPerm ) === -1 )
					or
					(
						is_array( $member->modPermission( $container::$modPerm ) )
						and
						in_array( $this->container()->_id, $member->modPermission( $container::$modPerm ) )
					)
				)
			)
			{
				return TRUE;
			}
		}
		
		/* Otherwise no */
		return FALSE;
	}

	/**
	 * Get the solution
	 *
	 * @return Comment|NULL
	 */
	public function getSolution(): Comment|NULL
	{
		try
		{
			$commentClass = static::$commentClass;
			return $commentClass::load( $this->mapped('solved_comment_id') );
		}
		catch( OutOfRangeException )
		{
			return NULL;
		}
	}
}