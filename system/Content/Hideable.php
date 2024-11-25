<?php
/**
 * @brief		Hidebale Trait for Content Models/Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		6 Nov 2013
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content\Search\SearchContent;
use RuntimeException;
use BadMethodCallException;
use OutOfRangeException;
use UnderflowException;
use IPS\core\Approval;
use IPS\core\DeletionLog;
use IPS\core\IndexNow;
use IPS\core\Profanity;
use IPS\Api\Webhook;
use IPS\DateTime as IPSDateTime;
use IPS\Login;
use IPS\Member;
use IPS\Content\Search\Index;
use IPS\Db;
use IPS\IPS;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Settings;
use IPS\Theme;
use IPS\Events\Event;
use function count;
use function defined;
use function get_called_class;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function substr;
use function ucfirst;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Hidebale Trait for Content Models/Comments
 *
 * @note	Content classes will gain special functionality by implementing this interface
 */
trait Hideable
{
	/**
	 * Content is hidden?
	 *
	 * @return	int
	 *	@li -3 is a post made by a guest using the "post before register" feature
	 *	@li -2 is pending deletion
	 * 	@li	-1 is hidden having been hidden by a moderator
	 * 	@li	0 is unhidden
	 *	@li	1 is hidden needing approval
	 * @note	The actual column may also contain 2 which means the item is hidden because the parent is hidden, but it is not hidden in itself. This method will return -1 in that case.
	 *
	 * @note    A piece of content (item and comment) can have an alias for hidden OR approved.
	 *          With hidden: 0=not hidden, 1=hidden (needs moderator approval), -1=hidden by moderator, 2=parent item is hidden, -2=pending deletion, -3=guest post before register
	 *          With approved: 1=not hidden, 0=hidden (needs moderator approval), -1=hidden by moderator, -2=pending deletion, -3=guest post before register
	 *
	 *          User posting has moderator approval set: When adding an unapproved ITEM (approved=0, hidden=1) you should *not* increment container()->_comments but you should update container()->_unapprovedItems
	 *          User posting has moderator approval set: When adding an unapproved COMMENT (approved=0, hidden=1) you should *not* increment item()->num_comments in item or container()->_comments but you should update item()->unapproved_comments and container()->_unapprovedComments
	 *
	 *          User post is hidden by moderator (approved=-1, hidden=0) you should decrement item()->num_comments and decrement container()->_comments but *not* increment item()->unapproved_comments or container()->_unapprovedComments
	 *          User item is hidden by a moderator (approved=-1, hidden=0) you should decrement container()->comments and subtract comment count from container()->_comments, but *not* increment container()->_unapprovedComments
	 *
	 *          Moderator hides item (approved=-1, hidden=-1) you should substract num_comments from container()->_comments. Comments inside item are flagged as approved=-1, hidden=2 but item()->num_comments should not be substracted from
	 *
	 *          Comments with a hidden value of 2 should increase item()->num_comments but not container()->_comments
	 * @throws	RuntimeException
	 */
	public function hiddenStatus(): int
	{
		if ( isset( static::$databaseColumnMap['hidden'] ) )
		{
			$column = static::$databaseColumnMap['hidden'];
			return ( $this->$column == 2 ) ? -1 : intval( $this->$column );
		}
		elseif ( isset( static::$databaseColumnMap['approved'] ) )
		{
			$column = static::$databaseColumnMap['approved'];
			if ( $this->$column == -2 or $this->$column == -3 )
			{
				return intval( $this->$column );
			}
			return $this->$column == -1 ? intval( $this->$column ) : intval( !$this->$column );
		}
		else
		{
			throw new RuntimeException;
		}
	}
	
		/**
	 * Log for deletion later
	 *
	 * @param	Member|false|null 	$member	The member, NULL for currently logged in, or FALSE for no member
	 * @return	void
	 */
	public function logDelete( Member|false|null $member = NULL ): void
	{
		if( $member === NULL )
		{
			$member = Member::loggedIn();
		}

		$originalState = $this->hidden();
		
		/* Log it! */
		$log = new DeletionLog;
		$log->setContentAndMember( $this, $member );
		$log->save();

		$column = static::$databaseColumnMap[ 'hidden' ] ?? static::$databaseColumnMap[ 'approved' ];
		$this->$column = -2;
		$this->save();
		
		if ( $this instanceof Comment )
		{
			$item = $this->item();
			
			/* Update last comment stuff */
			$item->resyncLastComment();

			/* Update last review stuff */
			$item->resyncLastReview();

			/* Update number of comments */
			$item->resyncCommentCounts();

			/* Update number of reviews */
			$item->resyncReviewCounts();

			/* Save*/
			$item->save();

			/* Now the container */
			try
			{
				$containerColumn = ( $originalState === 1 ) ? '_unapprovedComments' : '_comments';
				if( $this instanceof Review )
				{
					$containerColumn = ( $originalState === 1 ) ? '_unapprovedReviews' : '_reviews';
				}

				if( $item->container()->$containerColumn !== null )
				{
					$item->container()->$containerColumn = ( $item->container()->$containerColumn > 0 ) ? ( $item->container()->$containerColumn - 1 ) : 0;
					$item->container()->save();
				}
			}
			catch( \BadMethodCallException ){}
		}

		if ( IPS::classUsesTrait( $this, 'IPS\Content\Taggable' ) )
		{
			Db::i()->update( 'core_tags_perms', array( 'tag_perm_visible' => 0 ), array( 'tag_perm_aai_lookup=?', $this->tagAAIKey() ) );
		}
		
		try
		{
			$idColumn = static::$databaseColumnId;
			
			if ( $this->container() AND !$this->skipContainerRebuild )
			{
				$this->container()->resetCommentCounts();
				$this->container()->setLastComment();
				$this->container()->setLastReview();
				$this->container()->save();
			}

			/* Update mappings */
			if ( $container = $this->container() and IPS::classUsesTrait( $container, 'IPS\Node\Statistics' ) )
			{
				if ( $this instanceof Comment )
				{
					$container->rebuildPostedIn( array( $this->mapped('item') ) );
				}
				else
				{
					$container->rebuildPostedIn( array($this->$idColumn) );
				}
			}
			
			Approval::loadFromContent( get_called_class(), $this->$idColumn )->delete();
		}
		catch( BadMethodCallException|OutOfRangeException ) {}

		/* Fire an event so that we know this was soft-deleted */
		Event::fire( 'onStatusChange', $this, array( 'softDelete' ) );

		if ( $this instanceof Review )
		{
			$this->_recalculateRating();
		}
	}
	
	/**
	 * Restore Content
	 *
	 * @param	bool	$hidden	Restore as hidden?
	 * @return	void
	 */
	public function restore( bool $hidden = FALSE ): void
	{
		try
		{
			$idColumn = static::$databaseColumnId;
			$log = DeletionLog::constructFromData( Db::i()->select( '*', 'core_deletion_log', array( "dellog_content_class=? AND dellog_content_id=?", get_class( $this ), $this->$idColumn ) )->first() );
		}
		catch( UnderflowException )
		{
			/* There's no deletion log record, but this shouldn't stop us from restoring */
		}
		
		/* Restoring as hidden? */
		if ( $hidden )
		{
			$column = static::$databaseColumnMap['hidden'] ?? static::$databaseColumnId['approved'];
			$this->$column = -1;
		}
		else
		{
			if ( isset( static::$databaseColumnMap['hidden'] ) )
			{
				$column = static::$databaseColumnMap['hidden'];
				$this->$column = 0;
			}
			else if ( isset( static::$databaseColumnMap['approved'] ) )
			{
				$column = static::$databaseColumnMap['approved'];
				$this->$column = 1;
			}
		}
		
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Taggable' ) AND !$hidden )
		{
			Db::i()->update( 'core_tags_perms', array( 'tag_perm_visible' => 1 ), array( 'tag_perm_aai_lookup=?', $this->tagAAIKey() ) );
		}

		/* Save the changes */
		$this->save();

		/* Reindex the now hidden content - if this is a content item with comments or reviews, then make sure to do those too. */
		if ( $this instanceof Item AND ( isset( static::$commentClass ) OR isset( static::$reviewClass ) ) )
		{
			Index::i()->index( ( static::$firstCommentRequired ) ? $this->firstComment() : $this );
			Index::i()->indexSingleItem( $this );
		}
		else
		{
			/* Either this is a comment / review, or the item doesn't support comments or reviews, so we can just reindex it now. */
			Index::i()->index( $this );
		}
		
		/* Delete the log */
		if ( isset( $log ) )
		{
			$log->delete();
		}

		/* Recount the container counters */
		if( $this->container() AND !$this->skipContainerRebuild )
		{
			$this->container()->resetCommentCounts();
			$this->container()->setLastComment();
			$this->container()->setLastReview();
			$this->container()->save();
		}

		/* Fire an event so we know the content was restored */
		Event::fire( 'onStatusChange', $this, array( 'restore' ) );
		
		if ( $this instanceof Review )
		{
			$this->_recalculateRating();
		}
	}
	
	/**
	 * Can restore?*
	 *
	 * @param	Member|NULL	$member	The member, or currently logged in member
	 * @return	bool
	 */
	public function canRestore( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'restore', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();
		return $member->modPermission('can_manage_deleted_content');
	}

	/**
	 * Hide
	 *
	 * @param Member|NULL|FALSE $member The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @param string|NULL $reason Reason
	 * @return    void
	 * @throws Exception
	 */
	public function hide( Member|null|bool $member, string|null $reason = NULL ): void
	{
		/* Hide any moved links that point to this item - moved_on>? is for query optimisation purposes */
		if( $this instanceof Item and isset( static::$databaseColumnMap['moved_to'] ) and isset( static::$databaseColumnMap['moved_on'] ) )
		{
			$idColumn = static::$databaseColumnId;
			$where = array(
				array( static::$databasePrefix . static::$databaseColumnMap['moved_on'] . '>?', 0 ),
				array( static::$databasePrefix . static::$databaseColumnMap['moved_to'] . " LIKE CONCAT( ?, '%')", $this->$idColumn . '&' )
			);

			if( isset( static::$databaseColumnMap['state'] ) )
			{
				$where[] = array( static::$databasePrefix . static::$databaseColumnMap['state'] . '=?', 'link' );
			}

			foreach( Db::i()->select( '*', static::$databaseTable, $where ) as $link )
			{
				static::constructFromData( $link )->hide( $member, $reason );
			}
		}

		if ( isset( static::$databaseColumnMap['hidden'] ) )
		{
			$column = static::$databaseColumnMap['hidden'];
		}
		elseif ( isset( static::$databaseColumnMap['approved'] ) )
		{
			$column = static::$databaseColumnMap['approved'];
		}
		else
		{
			throw new RuntimeException;
		}

		/* Already hidden? */
		if( $this->$column == -1 )
		{
			return;
		}
		
		if ( $this instanceof Item )
		{
			$idColumn = static::$databaseColumnId;

			Db::i()->delete( 'core_notifications', array( 'item_class=? AND item_id=?', get_class( $this ), (int) $this->$idColumn ) );

			if( SearchContent::isSearchable( $this ) )
			{
				if ( isset( static::$commentClass ) OR isset( static::$reviewClass ) )
				{
					if ( isset( static::$commentClass ) )
					{
						$commentClass = static::$commentClass;
						if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Hideable' ) AND isset( $commentClass::$databaseColumnMap['hidden'] ) )
						{
							Db::i()->update( $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] => 2 ), array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=?', $this->$idColumn, 0 ) );
						}
					}
					
					if ( isset( static::$reviewClass ) )
					{
						$reviewClass = static::$reviewClass;
						if ( IPS::classUsesTrait( $reviewClass, 'IPS\Content\Hideable' ) AND isset( $reviewClass::$databaseColumnMap['hidden'] ) )
						{
							Db::i()->update( $reviewClass::$databaseTable, array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] => 2 ), array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=? AND ' . $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] . '=?', $this->$idColumn, 0 ) );
						}
					}
					
					$firstComment = NULL;
					if ( static::$firstCommentRequired AND isset( static::$commentClass ) AND $firstComment = $this->firstComment() )
					{
						$commentClass = static::$commentClass;
						if( isset( $commentClass::$databaseColumnMap['hidden'] ) )
						{
							$firstColumn = $commentClass::$databaseColumnMap['hidden'];
							$firstComment->$firstColumn = 2;
						}
					}
	
					Index::i()->index( ( static::$firstCommentRequired AND $firstComment ) ? $firstComment : $this );
					Index::i()->indexSingleItem( $this );
				}
				else
				{
					/* Either this is a comment / review, or the item doesn't support comments or reviews, so we can just reindex it now. */
					Index::i()->index( $this );
				}
			}
		}

		$this->$column = -1;
		$this->save();
		$this->onHide( $member );
		
		$idColumn = static::$databaseColumnId;
		if ( static::$hideLogKey )
		{
			Db::i()->delete( 'core_soft_delete_log', array( 'sdl_obj_id=? AND sdl_obj_key=?', $this->$idColumn, static::$hideLogKey ) );
			Db::i()->insert( 'core_soft_delete_log', array(
				'sdl_obj_id'		=> $this->$idColumn,
				'sdl_obj_key'		=> static::$hideLogKey,
				'sdl_obj_member_id'	=> $member === FALSE ? 0 : intval( $member ? $member->member_id : Member::loggedIn()->member_id ),
				'sdl_obj_date'		=> time(),
				'sdl_obj_reason'	=> $reason,
				
			) );
		}
		
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Taggable' ) )
		{
			Db::i()->update( 'core_tags_perms', array( 'tag_perm_visible' => 0 ), array( 'tag_perm_aai_lookup=?', $this->tagAAIKey() ) );
		}

		/* Update the solved index */
		$idColumn = static::$databaseColumnId;
		$solvedWhere = array(
			array( 'app=?', static::$application )
		);

		if ( $this instanceof Item )
		{
			$item = $this;
			$solvedWhere[] = array( 'item_id=?', $this->$idColumn );
		}
		else
		{
			$item = $this->item();
			$solvedWhere[] = array( 'comment_id=?', $this->$idColumn );
		}

		Db::i()->update( 'core_solved_index', [ 'hidden' => 1 ], $solvedWhere );

        /* Update search index */
		if( SearchContent::isSearchable( $this ) )
        {
            Index::i()->index( $this );
        }

		/* Clear the item stats */
		if ( IPS::classUsesTrait( $item, 'IPS\Content\Statistics' ) )
		{
			try
			{
				$item->clearCachedStatistics();
			}
			catch ( Exception )
			{
				/* Sssh */
			}
		}

		$this->expireWidgetCaches();
		$this->adjustSessions();
		
		try
		{
			Approval::loadFromContent( get_called_class(), $this->$idColumn )->delete();
		}
		catch( OutOfRangeException ) { }
	}
	
	/**
	 * Unhide
	 *
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function unhide( Member|null|bool $member ): void
	{
		/* Update our comments first - this is so that when onUnhide is called in the parent, then these posts will be accounted for when comment counts are reset */
		if ( $this instanceof Item )
		{
			$idColumn = static::$databaseColumnId;

			/* Unhide any moved links that point to this item - moved_on>? is for query optimisation purposes */
			if( isset( static::$databaseColumnMap['moved_to'] ) and isset( static::$databaseColumnMap['moved_on'] ) )
			{
				$where = array(
					array( static::$databasePrefix . static::$databaseColumnMap['moved_on'] . '>?', 0 ),
					array( static::$databasePrefix . static::$databaseColumnMap['moved_to'] . " LIKE CONCAT( ?, '%')", $this->$idColumn . '&' )
				);

				if( isset( static::$databaseColumnMap['state'] ) )
				{
					$where[] = array( static::$databasePrefix . static::$databaseColumnMap['state'] . '=?', 'link' );
				}

				foreach( Db::i()->select( '*', static::$databaseTable, $where ) as $link )
				{
					static::constructFromData( $link )->unhide( $member );
				}
			}

			foreach ( array( 'commentClass', 'reviewClass' ) as $class )
			{
				if ( isset( static::$$class ) )
				{
					$className = static::$$class;
					if ( IPS::classUsesTrait( $className, 'IPS\Content\Hideable' ) AND isset( $className::$databaseColumnMap['hidden'] ) )
					{
						Db::i()->update( $className::$databaseTable, array( $className::$databasePrefix . $className::$databaseColumnMap['hidden'] => 0 ), array( $className::$databasePrefix . $className::$databaseColumnMap['item'] . '=? AND ' . $className::$databasePrefix . $className::$databaseColumnMap['hidden'] . '=?', $this->$idColumn, 2 ) );
					}
				}
			}
		}

		/* If we're approving, we have to do extra stuff */
		$approving	= FALSE;
		$pbr		= FALSE;
		if ( $this->hidden() === 1 )
		{
			$approving = TRUE;
			if ( isset( static::$databaseColumnMap['approved_by'] ) and $member !== FALSE )
			{
				$column = static::$databaseColumnMap['approved_by'];
				$this->$column = $member ? $member->member_id : Member::loggedIn()->member_id;
			}
			if ( isset( static::$databaseColumnMap['approved_date'] ) )
			{
				$column = static::$databaseColumnMap['approved_date'];
				$this->$column = time();
			}
		}
		elseif( $this->hidden() === -3 )
		{
			$pbr = TRUE;
		}

		/* Now do the actual stuff */
		if ( isset( static::$databaseColumnMap['hidden'] ) )
		{
			$column = static::$databaseColumnMap['hidden'];

			/* Already approved? */
			if( $this->$column == 0 )
			{
				return;
			}

			$this->$column = 0;
		}
		elseif ( isset( static::$databaseColumnMap['approved'] ) )
		{
			$column = static::$databaseColumnMap['approved'];

			/* Already approved? */
			if( $this->$column == 1 )
			{
				return;
			}

			$this->$column = 1;
		}
		else
		{
			throw new RuntimeException;
		}
		$this->save();
		$this->onUnhide( ( $approving OR ( $pbr AND $this->hidden() === 0 ) ), $member );

		/* Update container if needed */
		if( $this instanceof Item )
		{
			try
			{
				if ( $this->container()->_comments !== NULL )
				{
					$this->container()->setLastComment( null, $this );
					$this->container()->save();
				}

				if ( $this->container()->_reviews !== NULL )
				{
					$this->container()->setLastReview();
					$this->container()->save();
				}
			} catch ( BadMethodCallException ) {}
		}
		
		$idColumn = static::$databaseColumnId;
		if ( static::$hideLogKey )
		{
			Db::i()->delete('core_soft_delete_log', array('sdl_obj_id=? AND sdl_obj_key=?', $this->$idColumn, static::$hideLogKey));
		}

		/* And update the tags perm cache */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Taggable' ) )
		{
			Db::i()->update( 'core_tags_perms', array( 'tag_perm_visible' => 1 ), array( 'tag_perm_aai_lookup=?', $this->tagAAIKey() ) );
		}
		
		/* Update search index */
		if( SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( $this );
		}
		
		/* Update report center stuff */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Reportable' ) )
		{
			$this->moderated( 'unhide' );
		}
		
		/* Send notifications if necessary */
		if ( ( $approving OR ( $pbr AND $this->hidden() === 0 ) ) )
		{
			$this->sendApprovedNotification();
		}

		/* Award points */
		$itemClass = in_array( 'IPS\Content\Comment', class_parents( get_called_class() ) ) ? static::$itemClass : get_called_class();
		if ( $this instanceof Item )
		{
			$this->author()->achievementAction( 'core', 'NewContentItem', $this );
		}
		elseif ( $this instanceof Review )
		{
			$this->author()->achievementAction( 'core', 'Review', $this );
		}
		elseif ( $this instanceof Comment and ( ( $itemClass::$firstCommentRequired AND ! $this->isFirst() ) OR ( ! $itemClass::$firstCommentRequired ) ) )
		{
			$this->author()->achievementAction( 'core', 'Comment', $this );
		}

		/* Update solved index */
		if ( $this instanceof Item )
		{
			$item = $this;
			$solvedWhere[] = array( 'item_id=?', $this->$idColumn );
		}
		else
		{
			$item = $this->item();
			$solvedWhere[] = array( 'comment_id=?', $this->$idColumn );
		}

		Db::i()->update( 'core_solved_index', [ 'hidden' => 0 ], $solvedWhere );

		/* Clear the item stats */
		if ( IPS::classUsesTrait( $item, 'IPS\Content\Statistics' ) )
		{
			try
			{
				$item->clearCachedStatistics();
			}
			catch ( Exception )
			{
				/* Sssh */
			}
		}
		
		/* Send webhook if necessary */
		if ( $approving )
		{
			Webhook::fire( str_replace( '\\', '', substr( get_called_class(), 3 ) ) . '_create', $this, $this->webhookFilters() );

			if ( $this instanceof Item and $itemClass::$firstCommentRequired === TRUE and $firstComment = $this->firstComment() )
			{
				Webhook::fire( str_replace( '\\', '', substr( get_class( $firstComment ), 3 ) ) . '_create', $firstComment, $firstComment->webhookFilters() );
			}
		}

		$this->expireWidgetCaches();
		$this->adjustSessions();
		
		try
		{
			Approval::loadFromContent( get_called_class(), $this->$idColumn )->delete();
		}
		catch( OutOfRangeException ) { }
		
		if ( $this instanceof Item )
		{
			/* And then update the search index */
			if( SearchContent::isSearchable( $this ) )
			{
				if ( isset( static::$commentClass ) OR isset( static::$reviewClass ) )
				{
					if( isset( static::$databaseColumnMap['state'] ) )
					{
						$stateColumn = static::$databaseColumnMap['state'];
						if ( $this->$stateColumn == 'link' )
						{
							return;
						}
					}
	
					$firstComment = NULL;
					if ( static::$firstCommentRequired AND isset( static::$commentClass ) AND $firstComment = $this->firstComment() )
					{
						$className = static::$commentClass;
						if( isset( $className::$databaseColumnMap['hidden'] ) )
						{
							$column = $className::$databaseColumnMap['hidden'];
							$firstComment->$column = 0;
						}
					}
	
					Index::i()->index( ( static::$firstCommentRequired AND $firstComment ) ? $firstComment : $this );
					Index::i()->indexSingleItem( $this );
				}
				else
				{
					/* Either this is a comment / review, or the item doesn't support comments or reviews, so we can just reindex it now. */
					Index::i()->index( $this );
				}
			}
		}
	}

	/**
	 * @brief	Hidden blurb cache
	 */
	protected ?string $hiddenBlurb	= NULL;

	/**
	 * Blurb for when/why/by whom this content was hidden
	 *
	 * @return	string
	 */
	public function hiddenBlurb(): string
	{
		if( $this->hiddenBlurb === NULL )
		{
			try
			{
				$idColumn = static::$databaseColumnId;
				$log = Db::i()->select( '*', 'core_soft_delete_log', array( 'sdl_obj_id=? AND sdl_obj_key=?', $this->$idColumn, static::$hideLogKey ) )->first();
				
				if ( $log['sdl_obj_member_id'] )
				{
					$this->hiddenBlurb = Member::loggedIn()->language()->addToStack('hidden_blurb', FALSE, array( 'sprintf' => array( Member::load( $log['sdl_obj_member_id'] )->name, IPSDateTime::ts( $log['sdl_obj_date'] )->relative(),  $log['sdl_obj_reason'] ?: Member::loggedIn()->language()->addToStack('hidden_no_reason') ) ) );
				}
				else
				{
					$this->hiddenBlurb = Member::loggedIn()->language()->addToStack('hidden_blurb_no_member', FALSE, array( 'sprintf' => array( IPSDateTime::ts( $log['sdl_obj_date'] )->relative(), $log['sdl_obj_reason'] ?: Member::loggedIn()->language()->addToStack('hidden_no_reason') ) ) );
				}
			
			}
			catch ( UnderflowException )
			{
				/* If this is requiring approval and has a logged reason */
				$hidden = $this->hidden();
				$item = NULL;
				if ( ( $this instanceof Comment ) )
				{
					$item = $this->item();
					/* If this is the first comment, and it's required, then we need to check the items hidden status instead */
					if ( $item::$firstCommentRequired AND $this->isFirst() AND $hidden !== 1 )
					{
						$hidden = $this->item()->hidden();
					}
				}

				if ( $item and $hidden === 1 AND IPS::classUsesTrait( $item, 'IPS\Content\Hideable' ) )
				{
					/* If moderator, show the reason */
					$reason = NULL;
					if( $this->modPermission( 'unhide' ) )
					{
						$reason = ( $item and $item::$firstCommentRequired AND $this->isFirst() ) ? $item->approvalQueueReason() : $this->approvalQueueReason();
					}
					
					if ( $reason )
					{
						$this->hiddenBlurb = Member::loggedIn()->language()->addToStack( 'hidden_with_reason', FALSE, array( 'sprintf' => array( $reason ) ) );
					}
					else
					{
						/* Otherwise show a message that the content requires approval before it can be edited. */
						$this->hiddenBlurb = Member::loggedIn()->language()->addToStack( 'hidden_awaiting_approval' );
					}
				}
				else
				{
					$this->hiddenBlurb = Member::loggedIn()->language()->addToStack('hidden');
				}
			}
		}

		return $this->hiddenBlurb;
	}
	
	/**
	 * Blurb for when/why/by whom this content was deleted
	 *
	 * @return	string
	 * @throws BadMethodCallException
	 */
	public function deletedBlurb(): string
	{
		try
		{
			$idColumn = static::$databaseColumnId;
			$log = DeletionLog::constructFromData( Db::i()->select( '*', 'core_deletion_log', array( "dellog_content_class=? AND dellog_content_id=?", get_class( $this ), $this->$idColumn ) )->first() );
			if( $log->_deleted_by )
			{
				return Member::loggedIn()->language()->addToStack( 'deletion_blurb', FALSE, array( 'sprintf' => array( $log->_deleted_by->name, $log->deleted_date->fullYearLocaleDate(), $log->deletion_date->fullYearLocaleDate() ) ) );
			}
			else
			{
				return Member::loggedIn()->language()->addToStack( 'deletion_blurb_no_member', FALSE, array( 'sprintf' => array( $log->deletion_date->fullYearLocaleDate() ) ) );
			}
		}
		catch( UnderflowException )
		{
			return Member::loggedIn()->language()->addToStack('deleted');
		}
	}
	
	/**
	 * @brief	Reason for requiring approval
	 */
	protected ?string $_approvalQueueReason = NULL;
	
	/**
	 * Approval Queue Reason
	 *
	 * @return	NULL|bool|string
	 */
	public function approvalQueueReason(): null|bool|string
	{
		if ( $this->_approvalQueueReason === NULL )
		{
			try
			{
				$idColumn = static::$databaseColumnId;
				$this->_approvalQueueReason = Approval::loadFromContent( get_class( $this ), $this->$idColumn )->reason();
			}
			catch( OutOfRangeException )
			{
				$this->_approvalQueueReason = NULL;
			}
		}
		return $this->_approvalQueueReason;
	}
	
	/**
	 * Get HTML for search result display
	 *
	 * @param	NULL|string		$ref		Referrer
	 * @param	Model	$container	Container
	 * @param	string			$title		Title
	 * @return	callable
	 */
	public function approvalQueueHtml( ?string $ref, Model $container, string $title ): string
	{
		return Theme::i()->getTemplate( 'modcp', 'core', 'front' )->approvalQueueItem( $this, $ref, $container, $title );
	}
	
	/**
	 * Log a row in core_post_before_registering
	 *
	 * @param	string	$guestEmail	Guest email address
	 * @param	string|null	$key		User's existing post_before_register cookie value
	 * @return	string	The new key, if one wasn't provided
	 */
	public function _logPostBeforeRegistering( string $guestEmail, ?string $key = NULL ): string
	{
		$key = $key ?: Login::generateRandomString();
		
		$idColumn = static::$databaseColumnId;
		Db::i()->insert( 'core_post_before_registering', array(
			'email'		=> $guestEmail,
			'class'		=> get_class( $this ),
			'id'		=> $this->$idColumn,
			'timestamp'	=> time(),
			'secret'	=> $key,
			'language'	=> Member::loggedIn()->language()->id
		) );
		
		/* enable followup task */
		Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), "`key`='postBeforeRegisterFollowup'" );
		
		return $key;
	}
	
	/**
	 * Send Approved Notification
	 *
	 * @return	void
	 */
	public function sendApprovedNotification(): void
	{
		$this->sendNotifications();
		$this->sendAuthorApprovalNotification();
	}

	/**
	 * Send Author Approval Notification
	 *
	 * @return  void
	 */
	public function sendAuthorApprovalNotification(): void
	{
		/* Tell the author their content has been approved */
		$member = Member::load( $this->mapped('author') );
		$notification = new Notification( Application::load('core'), 'approved_content', $this, array( $this ), array(), FALSE );
		$notification->recipients->attach( $member );
		$notification->send();
	}
	
	/**
	 * Send Unapproved Notification
	 *
	 * @return	void
	 */
	public function sendUnapprovedNotification(): void
	{
		$moderators = array( 'g' => array(), 'm' => array() );
		foreach(Db::i()->select( '*', 'core_moderators' ) AS $mod )
		{
			$canView = FALSE;
			$canApprove = FALSE;
			if ( $mod['perms'] == '*' )
			{
				$canView = TRUE;
				$canApprove = TRUE;
			}
			else
			{
				$perms = json_decode( $mod['perms'], TRUE );
								
				foreach ( array( 'canView' => 'can_view_hidden_', 'canApprove' => 'can_unhide_' ) as $varKey => $modPermKey )
				{
					if ( isset( $perms[ $modPermKey . 'content' ] ) AND $perms[ $modPermKey . 'content' ] )
					{
						$$varKey = TRUE;
					}
					else
					{						
						try
						{
							$container = ( $this instanceof Comment ) ? $this->item()->container() : $this->container();
							$containerClass = get_class( $container );
							$title = static::$title;
							if
							(
								isset( $containerClass::$modPerm )
								and
								(
									$perms[ $containerClass::$modPerm ] === -1
									or
									(
										is_array( $perms[ $containerClass::$modPerm ] )
										and
										in_array( $container->_id, $perms[ $containerClass::$modPerm ] )
									)
								)
								and
								isset( $perms[ $modPermKey . $title ] )
								and
								$perms[ $modPermKey . $title ]
							)
							{
								$$varKey = TRUE;
							}
						}
						catch ( BadMethodCallException ) { }
					}
				}
			}
			if ( $canView === TRUE and $canApprove === TRUE )
			{
				$moderators[ $mod['type'] ][] = $mod['id'];
			}
		}
						
		$notification = new Notification( Application::load('core'), 'unapproved_content', $this, array( $this, $this->author() ) );
		foreach (Db::i()->select( '*', 'core_members', ( count( $moderators['m'] ) ? Db::i()->in( 'member_id', $moderators['m'] ) . ' OR ' : '' ) . Db::i()->in( 'member_group_id', $moderators['g'] ) . ' OR ' . Db::i()->findInSet( 'mgroup_others', $moderators['g'] ) ) as $member )
		{
			$member = Member::constructFromData( $member );
			/* @var $member Member */
			/* We don't need to notify the author of the content or when member cannot view this content*/
			if( $this->author()->member_id != $member->member_id AND $this->canView( $member ) )
            {
                $notification->recipients->attach( $member );
            }
		}
		$notification->send();
	}

	/**
	 * Check comment against profanity filters but do not act on it
	 *
	 * @param bool $first Is this the first comment?
	 * @param bool $edit Are we editing or merging (true) or is this a new comment (false)?
	 * @param string|null $content The content to check - useful for if the content needs to be checked first, before it gets saved to the database.
	 * @param string|NULL|bool $title The title of the content to check, or NULL to check the current title, or FALSE to not check at all.
	 * @param string|null $autoSaveLocation The autosave location key of any editors to check, or NULL to use the default.
	 * @param array|null $autoSaveKeys The autosave keys (for new content) or attach lookup ids (for an edit) of any editors to check, or NULL to use the default.
	 * @param array $imageUploads Images that have been uploaded that may require moderation
	 * @param bool $hiddenByFilter
	 * @param bool $itemHiddenByFilter
	 * @param array $filtersMatched What matched, passed by reference
	 * @return    bool                                    Whether to send unapproved notifications (i.e. true if the content was hidden)
	 */
	public function shouldTriggerProfanityFilters( bool $first=FALSE, bool $edit=TRUE, ?string $content=NULL, string|null|bool $title=NULL, ?string $autoSaveLocation=NULL, ?array $autoSaveKeys=NULL, array $imageUploads=[], bool &$hiddenByFilter = FALSE, bool &$itemHiddenByFilter = FALSE, array &$filtersMatched = array() ): bool
	{		
		/* Set our content */
		$content = $content ?: $this->content();
				
		/* We need our item */
		$item = $this;
		if ( $this instanceof Comment )
		{
			$item = $this->item();
			
			if ( $item::$firstCommentRequired AND $first AND $title !== FALSE )
			{
				$title = $title ?: $item->mapped('title');
			}
		}
		else
		{
			if ( $title !== FALSE )
			{
				$title = $title ?: $this->mapped('title');
			}
		}
		
		/* If this content is "post before register", skip this check (we'll do it after registration is complete) */
		if ( $this->hidden() === -3 or ( $item::$firstCommentRequired and $first and $item->hidden() === -3 ) )
		{
			return FALSE;
		}

		/* Check the author cannot bypass */
		$member = $this->author();
		if ( $member->group['g_bypass_badwords'] )
		{
			return FALSE;
		}
				
		/* First, check the image scanner */
		$idColumn = static::$databaseColumnId;
		$itemIdColumn = $item::$databaseColumnId;
		if ( Settings::i()->ips_imagescanner_enable )
		{
			if ( $edit )
			{
				$autoSaveLocation = $autoSaveLocation ?: ( $item::$application . '_' . ucfirst( $item::$module ) );
				
				if ( !$autoSaveKeys )
				{			
					if ( $this instanceof Comment and ( !( $this instanceof Review ) or !$first or !$item::$firstCommentRequired ) )
					{
						$autoSaveKeys = [ $this->attachmentIds() ];
					}
					else
					{
						$autoSaveKeys = [ [ $item->$itemIdColumn ] ];
					}
				}
				
				$clauses = [];
				$binds = [];
				foreach ( $autoSaveKeys as $_autoSaveKeys )
				{				
					$idLookups = [];
					foreach ( range( 1, count( $_autoSaveKeys ) ) as $i )
					{
						$idLookups[]= "id$i=?";
					}
					$clauses[] = '( ' . implode( ' AND ', $idLookups ) . ' )';
					$binds = array_merge( $binds, $_autoSaveKeys );
				}
				
				$attachmentsMapWhere = array_merge( array( 'location_key=? AND ( ' . implode( ' OR ', $clauses ) . ' )', $autoSaveLocation ), $binds );
			}
			else
			{
				if ( $autoSaveKeys === NULL )
				{
					if ( $this instanceof Item or ( $first and $item::$firstCommentRequired ) )
					{
						$container = $item->containerWrapper();
						$autoSaveKeys[] = 'newContentItem-' . $item::$application . '/' . $item::$module  . '-' . ( $container ? $container->_id : 0 );
					}
					else
					{
						if ( $this instanceof Review )
						{
							$autoSaveKeys[] = 'review-' . $item::$application . '/' . $item::$module  . '-' . $item->$itemIdColumn;
						}
						else
						{
							$autoSaveKeys[] = 'reply-' . $item::$application . '/' . $item::$module  . '-' . $item->$itemIdColumn;
						}
					}
				}
				
				$attachmentsMapWhere = Db::i()->in( 'temp', array_map( 'md5', $autoSaveKeys ) );
			}

			if ( $this instanceof Item OR ( $first AND $item::$firstCommentRequired ) )
			{
				$itemHiddenByFilter = Profanity::hiddenByFilters( $content, $member, $filtersMatched );

				if ( $title AND !$itemHiddenByFilter )
				{
					$itemHiddenByFilter = Profanity::hiddenByFilters( $title, $member, $filtersMatched );
				}
			}
			else
			{
				$hiddenByFilter = Profanity::hiddenByFilters( $content, $member, $filtersMatched );

				if ( $title AND !$hiddenByFilter )
				{
					$hiddenByFilter = Profanity::hiddenByFilters( $title, $member, $filtersMatched );
				}
			}
			
			foreach (Db::i()->select( 'attach_moderation_status', 'core_attachments', [ 'attach_id IN (?)', Db::i()->select( 'attachment_id', 'core_attachments_map', $attachmentsMapWhere ) ] ) as $attachModerationStatus )
			{
				if ( $attachModerationStatus === 'pending' )
				{
					if ( $this instanceof Item or ( $first and $item::$firstCommentRequired ) )
					{
						$itemHiddenByFilter = TRUE;
					}
					else
					{
						$hiddenByFilter = TRUE;
					}
					break;
				}
			}
			
			if ( !$hiddenByFilter and !$itemHiddenByFilter )
			{
				foreach ( $imageUploads as $file )
				{
					if ( $file->requiresModeration )
					{
						if ( $this instanceof Item or ( $first and $item::$firstCommentRequired ) )
						{
							$itemHiddenByFilter = TRUE;
						}
						else
						{
							$hiddenByFilter = TRUE;
						}
						break;
					}
				}
				
				if ( $hiddenByFilter OR $itemHiddenByFilter )
				{
					$log = new Approval;
					$log->content_class	= get_called_class();
					$log->content_id	= $this->$idColumn;
					$log->held_reason	= 'image';
					$log->save(); 
				}
			}
		}
		else
		{
			/* Then pass this through our profanity and link filters */
			try
			{
				if ( $this instanceof Item or ( $first and $item::$firstCommentRequired ) )
				{
					if ( !$itemHiddenByFilter )
					{
						$itemHiddenByFilter = Profanity::hiddenByFilters( $content, $member, $filtersMatched );
					}
					if ( !$itemHiddenByFilter and $title )
					{
						$itemHiddenByFilter = Profanity::hiddenByFilters( $title, $member, $filtersMatched );
					}
				}
				else
				{
					if ( !$hiddenByFilter )
					{
						$hiddenByFilter = Profanity::hiddenByFilters( $content, $member, $filtersMatched );
					}
					if ( !$hiddenByFilter and $title )
					{
						$hiddenByFilter = Profanity::hiddenByFilters( $title, $member, $filtersMatched );
					}
				}
			}
			catch( BadMethodCallException ) { }
		}
		
		/* Return */
		return ( $hiddenByFilter or $itemHiddenByFilter );
	}

	/**
	 * Check comment against profanity filters AND act on it
	 *
	 * @param bool $first Is this the first comment?
	 * @param bool $edit Are we editing or merging (true) or is this a new comment (false)?
	 * @param string|null $content The content to check - useful for if the content needs to be checked first, before it gets saved to the database.
	 * @param string|NULL|bool $title The title of the content to check, or NULL to check the current title, or FALSE to not check at all.
	 * @param string|null $autoSaveLocation The autosave location key of any editors to check, or NULL to use the default.
	 * @param array|null $autoSaveKeys The autosave keys (for new content) or attach lookup ids (for an edit) of any editors to check, or NULL to use the default.
	 * @param array $imageUploads Images that have been uploaded that may require moderation
	 * @return    bool                                    Whether to send unapproved notifications (i.e. true if the content was hidden)
	 */
	public function checkProfanityFilters( bool $first=FALSE, bool $edit=TRUE, ?string $content=NULL, string|null|bool $title=NULL, ?string $autoSaveLocation=NULL, array|null $autoSaveKeys=NULL, array $imageUploads=[] ): bool
	{
		/* Check it */
		$hiddenByFilter = FALSE;
		$itemHiddenByFilter = FALSE;
		$sendNotifications = FALSE;
		$filtersMatched = array();
		$item = ( $this instanceof Item ) ? $this : $this->item();
		$this->shouldTriggerProfanityFilters( $first, $edit, $content, $title, $autoSaveLocation, $autoSaveKeys, $imageUploads, $hiddenByFilter, $itemHiddenByFilter, $filtersMatched );

		/* If we need to hide the item, then do that */
		if ( $itemHiddenByFilter )
		{
			$sendNotifications = $edit;
			
			/* 'approved' is easy, clear and concise */
			if ( isset( $item::$databaseColumnMap['approved'] ) )
			{
				$column = $item::$databaseColumnMap['approved'];
				$item->$column = 0;
				$item->save();
			}
			/* 'hidden' is backwards */
			elseif ( isset( $item::$databaseColumnMap['hidden'] ) )
			{
				$column = $item::$databaseColumnMap['hidden'];
				$item->$column = 1;
				$item->save();
			}
		}
		
		/* If we need to hide this, then do that */
		if ( $hiddenByFilter or $itemHiddenByFilter )
		{
			$sendNotifications = TRUE;
			
			/* 'approved' is easy, clear and concise */
			if ( isset( static::$databaseColumnMap['approved'] ) )
			{
				$column = static::$databaseColumnMap['approved'];
				$this->$column = 0;
				$this->save();
			}
			/* 'hidden' is backwards */
			else if ( isset( static::$databaseColumnMap['hidden'] ) )
			{
				$column = static::$databaseColumnMap['hidden'];
				$this->$column = ( $itemHiddenByFilter and $this instanceof Comment ) ? 2 : 1; # We use the special 2 flag to note it is only hidden because the parent is
				$this->save();
			}
			
			$idColumn = static::$databaseColumnId;
			$itemColumnId = $item::$databaseColumnId;
			
			try
			{
				if ( $this instanceof Comment AND $item::$firstCommentRequired AND $this->isFirst() )
				{
					Approval::loadFromContent( get_class( $item ), $item->$itemColumnId );
				}
				else
				{
					Approval::loadFromContent( get_called_class(), $this->$idColumn );
				}
			}
			catch( OutOfRangeException )
			{
				if ( isset( $filtersMatched['type'] ) AND isset( $filtersMatched['match'] ) )
				{
					$log = new Approval;
					if ( $this instanceof Comment AND $item::$firstCommentRequired AND $this->isFirst() )
					{
						$log->content_class	= get_class( $item );
						$log->content_id	= $item->$itemColumnId;
					}
					else
					{
						$log->content_class	= get_called_class();
						$log->content_id	= $this->$idColumn;
					}
					$log->held_reason	= $filtersMatched['type'];
					switch( $filtersMatched['type'] )
					{
						case 'profanity':
							$log->held_data = array( 'word' => $filtersMatched['match'] );
							break;
						
						case 'url':
							$log->held_data = array( 'url' => $filtersMatched['match'] );
							break;
						
						case 'email':
							$log->held_data = array( 'email' => $filtersMatched['match'] );
							break;
					}
					$log->save();
				}
			}
		}
		
		/* If we did either, then recount number of comments */
		if ( $itemHiddenByFilter or $hiddenByFilter )
		{
			$item->resyncCommentCounts();
			$item->resyncLastComment();
			$item->save();

			if ( $first AND $container = $item->containerWrapper() )
			{
				$container->resetCommentCounts();
				$container->save();
			}
		}

		/* Return */
		return $sendNotifications;
	}
	
	/**
	 * Can hide?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canHide( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'hide', $member ) )
		{
			return false;
		}

		if ( $this->hidden() === -1 )
		{
			return FALSE;
		}

		$member = $member ?: Member::loggedIn();
		if ( $this instanceof Comment )
		{
			return ( !$this->isFirst() and ( static::modPermission( 'hide', $member, $this->item()->containerWrapper() ) or ( $member->member_id and $member->member_id == $this->author()->member_id and ( $member->group['g_hide_own_posts'] == '1' or in_array( get_class( $this->item() ), explode( ',', $member->group['g_hide_own_posts'] ) ) ) ) ) );
		}

		return ( $member->member_id and ( static::modPermission( 'hide', $member, $this->containerWrapper() ) or ( $member->member_id == $this->author()->member_id and ( $member->group['g_hide_own_posts'] == '1' or in_array( get_class( $this ), explode( ',', $member->group['g_hide_own_posts'] ) ) ) ) ) );
	}
	
	/**
	 * Can unhide?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canUnhide( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'unhide', $member ) )
		{
			return false;
		}

		if ( !$this->hidden() )
		{
			return FALSE;
		}

		/* Check delayed deletes */
		if ( $this->hidden() == -2 )
		{
			return FALSE;
		}

		$member = $member ?: Member::loggedIn();
		if ( $this instanceof Comment )
		{
			$hiddenByItem = FALSE;
			if ( isset( static::$databaseColumnMap['hidden'] ) )
			{
				$column = static::$databaseColumnMap['hidden'];
				$hiddenByItem = ( $this->$column === 2 );
			}
	
			return ( !$this->isFirst() and ! $hiddenByItem and static::modPermission( 'unhide', $member, $this->item()->containerWrapper() ) );
		}

		return ( $member->member_id and ( static::modPermission( 'unhide', $member, $this->containerWrapper() ) ) );
	}
	
	/**
	 * Can view hidden items?
	 *
	 * @param	Member|NULL	    $member	        The member to check for (NULL for currently logged in member)
	 * @param   Model|null    $container      Container
	 * @return	bool
	 * @note	If called without passing $container, this method falls back to global "can view hidden content" moderator permission which isn't always what you want - pass $container if in doubt or use canViewHiddenItemsContainers()
	 */
	public static function canViewHiddenItems( ?Member $member=NULL, ?Model $container = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		return $container ? static::modPermission( 'view_hidden', $member, $container ) : $member->modPermission( "can_view_hidden_content" );
	}
	
	/**
	 * Container IDs that the member can view hidden items in
	 *
	 * @param	Member|NULL	    $member	        The member to check for (NULL for currently logged in member)
	 * @return	bool|array				TRUE means all, FALSE means none
	 */
	public static function canViewHiddenItemsContainers( ?Member $member=NULL ): bool|array
	{
		$member = $member ?: Member::loggedIn();
		if ( $member->modPermission( "can_view_hidden_content" ) )
		{
			return TRUE;
		}
		elseif ( $member->modPermission( "can_view_hidden_" . static::$title ) )
		{
			if ( !isset( static::$containerNodeClass ) )
			{
				return TRUE;
			}

			$containerClass = static::$containerNodeClass;
			if ( isset( $containerClass::$modPerm ) )
			{
				$containers = $member->modPermission( $containerClass::$modPerm );
				if ( $containers === -1 )
				{
					return TRUE;
				}
				return $containers;
			}
			else
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}

	/**
	 * Can view hidden comments on this item?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canViewHiddenComments( ?Member $member=NULL ): bool
	{		
		$commentClass = static::$commentClass;

		return $commentClass::modPermission( 'view_hidden', $member, $this->containerWrapper() );
	}
	
	/**
	 * Can view hidden reviews on this item?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canViewHiddenReviews( ?Member $member=NULL ): bool
	{
		$reviewClass = static::$reviewClass;

		return $reviewClass::modPermission( 'view_hidden', $member, $this->containerWrapper() );
	}
	
	/**
	 * Item is moderator hidden by a moderator
	 *
	 * @return	boolean
	 * @throws	RuntimeException
	 */
	public function approvedButHidden(): bool
	{
		if ( isset( static::$databaseColumnMap['hidden'] ) )
		{
			$column = static::$databaseColumnMap['hidden'];
			return $this->$column == 2;
		}
		elseif ( isset( static::$databaseColumnMap['approved'] ) )
		{
			$column = static::$databaseColumnMap['approved'];
			return $this->$column == -1;
		}
		else
		{
			throw new RuntimeException;
		}
	}
	
	/**
	 * Should new items be moderated?
	 *
	 * @param	Member		$member		The member posting
	 * @param	Model|NULL	$container	The container
	 * @param	bool			$considerPostBeforeRegistering	If TRUE, and $member is a guest, will check if a newly registered member would be moderated
	 * @return	bool
	 */
	public static function moderateNewItems( Member $member, ?Model $container = NULL, bool $considerPostBeforeRegistering = FALSE ): bool
	{
		if ( $container and $container->checkAction( 'moderate_items' ) and !$member->group['g_avoid_q'] )
		{
			return !static::modPermission( 'approve', $member, $container );
		}

		return $member->moderateNewContent( $considerPostBeforeRegistering );
	}
	
	/**
	 * Should new comments be moderated?
	 *
	 * @param	Member	$member							The member posting
	 * @param	bool		$considerPostBeforeRegistering	If TRUE, and $member is a guest, will check if a newly registered member would be moderated
	 * @return	bool
	 */
	public function moderateNewComments( Member $member, bool $considerPostBeforeRegistering = FALSE ): bool
	{
		if( ( $this instanceof Item and $this->containerWrapper() ) )
		{
			if ( $this->container()->checkAction( 'moderate_comments' ) and ! $member->group['g_avoid_q'] )
			{
				return true;
			}
		}

		$return = $member->moderateNewContent( $considerPostBeforeRegistering );
		
		if ( $return === false AND IPS::classUsesTrait( $this, 'IPS\Content\MetaData' ) AND static::supportedMetaDataTypes() !== NULL AND in_array( 'core_ItemModeration', static::supportedMetaDataTypes() ) )
		{
			$return = $this->itemModerationEnabled( $member );
		}

		return $return;
	}
	
	/**
	 * Should new reviews be moderated?
	 *
	 * @param	Member	$member							The member posting
	 * @param	bool		$considerPostBeforeRegistering	If TRUE, and $member is a guest, will check if a newly registered member would be moderated
	 * @return	bool
	 */
	public function moderateNewReviews( Member $member, bool $considerPostBeforeRegistering = FALSE ): bool
	{
		if( ( $this instanceof Item and $this->containerWrapper() ) )
		{
			if( $this->container()->checkAction( 'moderate_reviews' ) and ! $member->group['g_avoid_q'] )
			{
				return true;
			}
		}

		$return = $member->moderateNewContent( $considerPostBeforeRegistering );
		
		if ( $return === FALSE AND IPS::classUsesTrait( $this, 'IPS\Content\MetaData' ) AND static::supportedMetaDataTypes() !== NULL AND in_array( 'core_ItemModeration', static::supportedMetaDataTypes() ) )
		{
			$return = $this->itemModerationEnabled( $member );
		}
		
		return $return;
	}
	
	/**
	 * Syncing to run when hiding
	 *
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onHide( Member|null|bool $member ): void
	{
		if ( $this instanceof Item )
		{
			if ( method_exists( $this, 'container' ) )
			{
				try
				{
					$container = $this->container();

					if( $container->_items !== null )
					{
						$container->_items = ( $container->_items >=0 ) ? ( $container->_items - 1 ) : 0;
					}
	
					if ( isset( static::$commentClass ) )
					{
						$container->setLastComment();
					}
					if ( isset( static::$reviewClass ) )
					{
						if( !$this->hidden() )
						{
							$container->_reviews = $container->_reviews - $this->mapped('num_reviews');
						}
	
						$container->setLastReview();
					}
	
					$container->resetCommentCounts();
					$container->save();
				}
				catch ( BadMethodCallException ) { }
			}

			/* If this is a tagged class, remove it if it was pinned */
			if( IPS::classUsesTrait( $this, Taggable::class ) )
			{
				$idColumn = static::$databaseColumnId;
				Db::i()->delete( 'core_tags_pinned', [ 'pinned_item_class=? and pinned_item_id=?', get_called_class(), $this->$idColumn ] );
			}
		}
		else if ( $this instanceof Comment )
		{
			if ( $this instanceof Review )
			{
				$item = $this->item();
				if ( $item->container()->_reviews !== NULL )
				{
					$item->container()->_reviews = ( $item->container()->_reviews > 0 ) ? ( $item->container()->_reviews - 1 ) : 0;
					$item->container()->setLastReview();
					$item->container()->save();
				}
				if ( isset( $item::$databaseColumnMap['hidden_reviews'] ) )
				{
					$column = $item::$databaseColumnMap['hidden_reviews'];
					$item->$column = $item->mapped('hidden_reviews') + 1;
				}
				
				if ( isset( $item::$databaseColumnMap['num_reviews'] ) )
				{
					$column = $item::$databaseColumnMap['num_reviews'];
					$item->$column = $item->mapped('num_reviews') - 1;
				}

				if( isset( $item::$databaseColumnMap['rating'] ) )
				{
					$ratingField = $item::$databaseColumnMap['rating'];
					$item->$ratingField = $item->averageReviewRating() ?: 0;
				}
		
				$item->resyncLastReview();
				$item->save();
			}
			else
			{
				$item = $this->item();

				$this->rebuildItemStats( $item );
		
				/* Remove any notifications */
				$idColumn = static::$databaseColumnId;
				Db::i()->delete( 'core_notifications', array( 'item_sub_class=? AND item_sub_id=?', get_called_class(), (int) $this->$idColumn ) );

				if ( $item->container()->_comments !== NULL )
				{
					$item->container()->setLastComment();
					$item->container()->_comments = ( $item->container()->_comments > 0 ) ? ( $item->container()->_comments - 1 ) : 0;
					$item->container()->resetCommentCounts();
					$item->container()->save();
				}
			}
		}
		
		Event::fire( 'onStatusChange', $this, array( 'hide' ) );

		/* If we have an ItemTopic we need ot keep it in synch */
		if( IPS::classUsesTrait( $this, 'IPS\Content\ItemTopic' ) )
		{
			$this->itemHidden( $member );
		}
	}
	
	/**
	 * Syncing to run when unhiding
	 *
	 * @param	bool					$approving	If true, is being approved for the first time
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onUnhide( bool $approving, Member|null|bool $member ): void
	{
		if ( $this instanceof Item )
		{
			/* If approving, we may need to increase the post count */
			if ( $approving AND isset( static::$commentClass ) )
			{
				$commentClass = static::$commentClass;
				if ( ( IPS::classUsesTrait( $this, 'IPS\Content\Anonymous' ) AND !$this->isAnonymous() ) and ( static::$firstCommentRequired and $commentClass::incrementPostCount( $this->containerWrapper() ) ) or static::incrementPostCount( $this->containerWrapper() ) )
				{
					$this->author()->member_posts++;
					$this->author()->save();
				}
			}
			
			/* Update container */
			if ( method_exists( $this, 'container' ) )
			{
				try
				{
					$container = $this->container();

					if( $container->_items !== null )
					{
						$container->_items = ( $container->_items + 1 );
					}

					if( $approving and $container->_unapprovedItems !== null )
					{
						$container->_unapprovedItems = ( $container->_unapprovedItems >= 0 ) ? ( $container->_unapprovedItems - 1 ) : 0;
					}

					$resetCommentCounts = TRUE;
					if ( IPS::classUsesTrait( $this, 'IPS\Content\FuturePublishing' ) AND $this->isFutureDate() )
					{
						$resetCommentCounts = FALSE;
					}

					if ( $resetCommentCounts )
					{
						$container->resetCommentCounts();

						if ( isset( static::$commentClass ) )
						{
							$container->setLastComment( null, $this );
						}
						if ( isset( static::$reviewClass ) )
						{
							$container->_reviews = $container->_reviews + $this->mapped('num_reviews');
							$container->setLastReview();
						}

						$container->save();
					}
				}
				catch ( BadMethodCallException ) { }
			}
		}
		else if ( $this instanceof Comment )
		{
			if ( $this instanceof Review )
			{
				$item = $this->item();

				if ( $approving )
				{
					if ( isset( $item::$databaseColumnMap['unapproved_reviews'] ) )
					{
						$column = $item::$databaseColumnMap['unapproved_reviews'];
						$item->$column = $item->mapped('unapproved_reviews') - 1;
					}
					if ( $item->container()->_unapprovedReviews !== NULL )
					{
						$item->container()->_unapprovedReviews = ( $item->container()->_unapprovedReviews > 0 ) ? ( $item->container()->_unapprovedReviews - 1 ) : 0;
						$item->container()->setLastReview();
						$item->container()->save();
					}
				}
				else if ( isset( $item::$databaseColumnMap['hidden_reviews'] ) )
				{
					$column = $item::$databaseColumnMap['hidden_reviews'];
					if ( $item->mapped('hidden_reviews') > 0 )
					{
						$item->$column = $item->mapped('hidden_reviews') - 1;
					}
				}
		
				if ( isset( $item::$databaseColumnMap['num_reviews'] ) )
				{
					$column = $item::$databaseColumnMap['num_reviews'];
					$item->$column = $item->mapped('num_reviews') + 1;
				}
				if ( $item->container()->_reviews !== NULL )
				{
					$item->container()->_reviews = $item->container()->_reviews + 1;
					$item->container()->setLastReview();
					$item->container()->save();
				}

				if( isset( $item::$databaseColumnMap['rating'] ) )
				{
					$ratingField = $item::$databaseColumnMap['rating'];
					$item->$ratingField = $item->averageReviewRating() ?: 0;
				}
				
				$item->resyncLastReview();
				$item->save();
			}
			else
			{
				$item = $this->item();
		
				if ( $approving )
				{
					/* We should only do this if it is an actual account, and not a guest. */
					if ( $this->author()->member_id )
					{
						try
						{
							if ( static::incrementPostCount( $item->container() ) )
							{
								$this->author()->member_posts++;
								$this->author()->save();
							}
						}
						catch( BadMethodCallException ) { }
					}
				}

				$this->rebuildItemStats( $item );

				if ( $item->container()->_comments !== NULL )
				{
					$item->container()->setLastComment( $this, $item );
					$item->container()->_comments = ( $item->container()->_comments + 1 );
					$item->container()->resetCommentCounts();
					$item->container()->save();
				}

				if( $item->container()->_unapprovedComments !== NULL and $approving )
				{
					$item->container()->_unapprovedComments = ( $item->container()->_unapprovedComments > 0 ) ? ( $item->container()->_unapprovedComments - 1 ) : 0;
					$item->container()->save();
				}
			}
		}
		
		Event::fire( 'onStatusChange', $this, array( 'unhide' ) );

		/* If we use ItemTopic, keep it in synch */
		if( IPS::classUsesTrait( $this, 'IPS\Content\ItemTopic' ) )
		{
			$this->itemUnhidden( $approving, $member );
		}
	}

	/**
	 * Resynch and recount
	 *
	 * @param Item $item
	 * @return void
	 */
	protected function rebuildItemStats( Item $item ) : void
	{
		$itemIdColumn = $item::$databaseColumnId;
		$item->resyncCommentCounts();
		$item->resyncLastComment();
		$item->save();

		/* We have to do this *after* updating the last comment data for the item, because that uses the cached data from the item (i.e. topic) */
		try
		{
			/* Update mappings */
			if ( IPS::classUsesTrait( $item->container(), 'IPS\Node\Statistics' ) )
			{
				$item->container()->rebuildPostedIn( array( $item->$itemIdColumn ), array( $this->author() ) );
			}
		} catch ( BadMethodCallException ) {}

		if ( IPS::classUsesTrait( $item, 'IPS\Content\Statistics' ) )
		{
			$item->clearCachedStatistics();
		}

		/* Send the content items URL to IndexNow if the guest can view it */
		if( $item->canView( new Member ) )
		{
			IndexNow::addUrlToQueue( $item->url() );
		}
	}
}