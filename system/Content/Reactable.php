<?php
/**
 * @brief		Reaction Trait
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Nov 2016
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use DomainException;
use Exception;
use IPS\Application;
use IPS\Content;
use IPS\DateTime;
use IPS\Db;
use IPS\Events\Event;
use IPS\Helpers\Table\Db as TableDb;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Platform\Bridge;
use IPS\Redis;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use RedisException;
use UnderflowException;
use function count;
use function defined;
use function get_called_class;
use function get_class;
use function is_array;
use function strtolower;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Reaction Trait
 */
trait Reactable
{
	/**
	 * Reaction type
	 *
	 * @return	string
	 */
	abstract public static function reactionType(): string;
	
	/**
	 * Reaction class
	 *
	 * @return	string
	 */
	public static function reactionClass(): string
	{
		return get_called_class();
	}

	/**
	 * React
	 *
	 * @param Reaction $reaction The reaction
	 * @param Member|null $member The member reacting, or NULL
	 * @return    void
	 * @throws    DomainException
	 * @throws Exception
	 */
	public function react(Content\Reaction $reaction, ?Member $member = NULL ): void
	{
		/* Did we pass a member? */
		$member = $member ?: Member::loggedIn();
		
		/* Figure out the owner of this - if it is content, it will be the author. If it is a node, then it will be the person who created it */
		if( $this instanceof Model )
		{
			$owner = $this->owner();
		}
		else
		{
			$owner = $this->author();
		}

		/* Can we react? */
		if ( !$this->canView( $member ) or !$this->canReact( $member ) or !$reaction->enabled )
		{
			throw new DomainException( 'cannot_react' );
		}
		
		/* Have we hit our limit? Also, why 999 for unlimited? */
		if ( $member->group['g_rep_max_positive'] !== -1 )
		{
			$count = Db::i()->select( 'COUNT(*)', 'core_reputation_index', array( 'member_id=? AND rep_date>?', $member->member_id, DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimestamp() ) )->first();
			if ( $count >= $member->group['g_rep_max_positive'] )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'react_daily_exceeded', FALSE, array( 'sprintf' => array( $member->group['g_rep_max_positive'] ) ) ) );
			}
		}
		
		/* Figure out our app - we do it this way as content items and nodes will always have a lowercase namespace for the app, so if the match below fails, then 'core' can be assumed */
		$app = explode( '\\', get_class( $this ) );
		if ( strtolower( $app[1] ) === $app[1] )
		{
			$app = $app[1];
		}
		else
		{
			$app = 'core';
		}
		
		/* If this is a comment, we need the parent items ID */
		$itemId = 0;
		if ( $this instanceof Comment)
		{
			$item			= $this->item();
			$itemIdColumn	= $item::$databaseColumnId;
			$itemId			= $item->$itemIdColumn;
		}
		
		/* Have we already reacted? */
		$reacted = $this->reacted( $member );
		
		/* Remove the initial reaction, if we have reacted */
		if ( $reacted )
		{
			$this->removeReaction( $member, FALSE );
		}
		
		/* Give points */
		$owner->achievementAction( 'core', 'Reaction', [
			'giver'		=> $member,
			'content'	=> $this,
			'reaction'	=> $reaction
		] );
		
		/* Actually insert it */
		$idColumn = static::$databaseColumnId;
		Db::i()->insert( 'core_reputation_index', array(
			'member_id'				=> $member->member_id,
			'app'					=> $app,
			'type'					=> static::reactionType(),
			'type_id'				=> $this->$idColumn,
			'rep_date'				=> DateTime::create()->getTimestamp(),
			'rep_rating'			=> $reaction->value,
			'member_received'		=> $owner->member_id,
			'rep_class'				=> static::reactionClass(),
			'class_type_id_hash'	=> md5( static::reactionClass() . ':' . $this->$idColumn ),
			'item_id'				=> $itemId,
			'reaction'				=> $reaction->id
		) );

		/* Fire event */
		Event::fire( 'onReact', $member, [ $this, $reaction ] );

		/* Have we hit highlighted content? */
		if( $this->isHighlighted() )
		{
			$owner->achievementAction( 'core', 'Highlight', [ 'content' => $this ] );
		}

		/* Send the notification but only if we aren't reacting to our own content, we can view the content, the user isn't ignored and we aren't changing from one reaction to another */
		if ( $this->author()->member_id AND $this->author() !== $member AND $this->canView( $owner ) AND !$reacted AND !$member->isIgnoring( $this->author(), 'posts' ) )
		{
			$notification = new Notification( Application::load('core'), 'new_likes', $this, array( $this, $member ), array(), TRUE, Content\Reaction::isLikeMode() ? NULL : 'notification_new_react' );
			$notification->recipients->attach( $owner );
			$notification->send();
		}
		
		if ( $owner->member_id )
		{
			$owner->pp_reputation_points += $reaction->value;
			$owner->save();
		}

		/* Reset some cached values */
		$this->_reactionCount	= NULL;
		$this->_reactions		= NULL;
		$this->likeBlurb 		= [];
		unset( $this->reputation );

		$this->hasReacted[ $member->member_id ] = $reaction;
		$item = ( ! $this instanceof Item) ? $this->item() : $this;
		if ( Bridge::i()->featureIsEnabled( 'trending' ) )
		{
			/* We need to make sure we're using the item */

			$itemIdColumn	= $item::$databaseColumnId;
			$itemId			= $item->$itemIdColumn;
			try
			{
				Redis::i()->zIncrBy( 'trending', time() * 0.4, get_class( $item ) .'__' . $itemId );
			}
			catch( BadMethodCallException | RedisException ) {}
		}

		if ( IPS::classUsesTrait( $item, 'IPS\Content\Statistics' ) )
		{
			/* @var	Item $item */
			$item->clearCachedStatistics();
		}
	}
	
	/**
	 * Remove Reaction
	 *
	 * @param	Member|NULL		$member					The member, or NULL for currently logged in member
	 * @param	bool					$removeNotifications	Whether to remove notifications or not
	 * @return	void
	 */
	public function removeReaction( ?Member $member = NULL, bool $removeNotifications = TRUE ): void
	{
		$member = $member ?: Member::loggedIn();

		/* Reset some cached values */
		$this->_reactionCount	= NULL;
		$this->_reactions		= NULL;
		$this->likeBlurb 		= [];
		unset( $this->reputation );
		
		try
		{
			try
			{
				$idColumn	= static::$databaseColumnId;
				
				$where = $this->getReactionWhereClause( NULL, FALSE );
				$where[] = array( 'member_id=?', $member->member_id );
				$rep		= Db::i()->select( '*', 'core_reputation_index', $where )->first();
			}
			catch( UnderflowException )
			{
				throw new OutOfRangeException;
			}
			
			$memberReceived		= Member::load( $rep['member_received'] );
			$reaction			= Content\Reaction::load( $rep['reaction'] );
		}
		catch( OutOfRangeException )
		{
			throw new DomainException;
		}
		
		if( Db::i()->delete( 'core_reputation_index', array( "app=? AND type=? AND type_id=? AND member_id=?", static::$application, static::reactionType(), $this->$idColumn, $member->member_id ) ) )
		{
			if ( $memberReceived->member_id )
			{
				$memberReceived->pp_reputation_points = $memberReceived->pp_reputation_points - $reaction->value;
				$memberReceived->save();
			}

			/* Fire event */
			Event::fire( 'onUnreact', $member, [ $this ] );

			/* Remove Notifications */
			if( $removeNotifications === TRUE )
			{
				$memberIds	= array();

				foreach( Db::i()->select( '`member`', 'core_notifications', array( 'notification_key=? AND item_class=? AND item_id=?', 'new_likes', get_class( $this ), (int) $this->$idColumn ) ) as $memberToRecount )
				{
					$memberIds[ $memberToRecount ]	= $memberToRecount;
				}

				Db::i()->delete( 'core_notifications', array( 'notification_key=? AND item_class=? AND item_id=?', 'new_likes', get_class( $this ), (int) $this->$idColumn ) );

				foreach( $memberIds as $memberToRecount )
				{
					Member::load( $memberToRecount )->recountNotifications();
				}
			}
		}

		if( isset( $this->hasReacted[ $member->member_id ] ) )
		{
			unset( $this->hasReacted[ $member->member_id ] );
		}
	}
	
	/**
	 * Can React
	 *
	 * @param	Member|NULL		$member	The member, or NULL for currently logged in
	 * @return	bool
	 */
	public function canReact( ?Member $member = NULL ): bool
	{
		if( !$this->actionEnabled( 'react', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();
		
		if ( $this instanceof Model )
		{
			$owner = $this->owner();
		}
		else
		{
			$owner = $this->author();
		}
		
		/* Only members can react */
		if ( !$member->member_id )
		{
			return FALSE;
		}

		/* Cannot react to guest content */
		if ( !isset( $owner ) or !$owner->member_id )
		{
			return FALSE;
		}
		
		/* Protected Groups */
		if ( $owner->inGroup( explode( ',', Settings::i()->reputation_protected_groups ) ) )
		{
			return FALSE;
		}
		
		/* Reactions per day */
		if ( $member->group['g_rep_max_positive'] == 0 )
		{
			return FALSE;
		}

		/* Unacknowledged warnings */
		if ( $member->members_bitoptions['unacknowledged_warnings'] )
		{
			return FALSE;
		}
		
		/* React to own content */
		if ( !Settings::i()->reputation_can_self_vote AND $this->author()->member_id == $member->member_id )
		{
			return FALSE;
		}
		
		/* Still here? All good. */
		return TRUE;
	}
	
	/**
	 * @brief	Reactions Cache
	 */
	protected array|null $_reactions = NULL;
	
	/**
	 * Reactions
	 *
	 * @return	array
	 */
	public function reactions(): array
	{
		if ( $this->_reactionCount === NULL )
		{
			$this->_reactionCount = 0;
		}
		
		if ( $this->_reactions === NULL )
		{
			$this->_reactions = array();

			if ( isset( $this->reputation ) )
			{
				if ( $enabledReactions = Content\Reaction::enabledReactions() )
				{
					foreach( $this->reputation AS $memberId => $reactionId )
					{
						if( isset( $enabledReactions[ $reactionId ] ) )
						{
							$this->_reactionCount += $enabledReactions[ $reactionId ]->value;
							$this->_reactions[ $memberId ][] = $reactionId;
						}
					}
				}
			}
			else
			{
				/* Set the data in $this->reputation to save queries later */
				$_reputation = array();
				foreach( Db::i()->select( '*', 'core_reputation_index', $this->getReactionWhereClause() )->join( 'core_reactions', 'reaction=reaction_id' ) AS $reaction )
				{
					$_reputation[ $reaction['member_id'] ] = $reaction['reaction'];
					$this->_reactions[ $reaction['member_id'] ][] = $reaction['reaction'];
					$this->_reactionCount += $reaction['rep_rating'];
				}
				$this->reputation = $_reputation;
			}
		}
		
		return $this->_reactions;
	}
	
	/**
	 * @brief Reaction Count
	 */
	protected int|null $_reactionCount = NULL;
	
	/**
	 * Reaction Count
	 *
	 * @return int
	 */
	public function reactionCount(): int
	{
		if( $this->_reactionCount === NULL )
		{
			$this->reactions();
		}

		return $this->_reactionCount;
	}

	/**
	 * Does this content reach the "reputation highlighted" threshhold?
	 *
	 * @return bool
	 */
	public function isHighlighted() : bool
	{
		return ( Settings::i()->reputation_enabled and Settings::i()->reputation_highlight and ( $this->reactionCount() >= Settings::i()->reputation_highlight ) );
	}
	
	/**
	 * Reaction Where Clause
	 *
	 * @param Reaction|array|int|NULL	$reactions			This can be any one of the following: An \IPS\Content\Reaction object, an array of \IPS\Content\Reaction objects, an integer, or an array of integers, or NULL
	 * @param	bool									$enabledTypesOnly 	If TRUE, only reactions of the enabled reaction types will be included (must join core_reactions)
	 * @return	array
	 */
	public function getReactionWhereClause( Reaction|array|int|null $reactions = NULL, bool $enabledTypesOnly=TRUE ): array
	{
		$app = explode( '\\', static::reactionClass() );
		if ( strtolower( $app[1] ) === $app[1] )
		{
			$app = $app[1];
		}
		else
		{
			$app = 'core';
		}

		$idColumn = static::$databaseColumnId;
		$where = array( array( 'rep_class=? AND app=? AND type=? AND type_id=?', static::reactionClass(), $app, static::reactionType(), $this->$idColumn ) );

		if ( $enabledTypesOnly )
		{
			$where[] = array( 'reaction_enabled=1' );
		}

		if ( $reactions !== NULL )
		{
			if ( !is_array( $reactions ) )
			{
				$reactions = array( $reactions );
			}

			$in = array();
			foreach( $reactions AS $reaction )
			{
				if ( $reaction instanceof Reaction)
				{
					$in[] = $reaction->id;
				}
				else
				{
					$in[] = $reaction;
				}
			}

			if ( count( $in ) )
			{
				$where[] = array( Db::i()->in( 'reaction', $in ) );
			}
		}

		return $where;
	}
	
	/**
	 * Reaction Table
	 *
	 * @param Reaction|int|NULL	$reaction			This can be any one of the following: An \IPS\Content\Reaction object, an integer, or NULL
	 * @return    TableDb
	 */
	public function reactionTable( Reaction|int|null $reaction=NULL ): TableDb
	{
		if ( !Member::loggedIn()->group['gbw_view_reps'] or !$this->canView() )
		{
			throw new DomainException;
		}
		
		$table = new TableDb( 'core_reputation_index', $this->url('showReactions'), $this->getReactionWhereClause( $reaction ) );
		$table->sortBy			= 'rep_date';
		$table->sortDirection	= 'desc';
		$table->tableTemplate = array( Theme::i()->getTemplate( 'global', 'core', 'front' ), 'reactionLogTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'global', 'core', 'front' ), 'reactionLog' );
		$table->joins = array( array( 'from' => 'core_reactions', 'where' => 'reaction=reaction_id' ) );

		$table->parsers = array(
			'rep_date' => function( $date, $row )
			{
				if ( isset( Request::i()->item ) and Request::i()->item )
				{
					/* This is an item level thing, and not a comment level thing */
					try
					{
						$class = $row['rep_class'];
						$item = $class::load( $row['type_id'] );

						return Member::loggedIn()->language()->addToStack( '_defart_from_date', FALSE, array( 'htmlsprintf' => array( $item->url(), $item->mapped('title'), DateTime::ts( $date )->html() ) ) );
					}
					catch( Exception )
					{
						return DateTime::ts( $date )->html();
					}
				}
				return DateTime::ts( $date )->html();
			}
		);

		$table->rowButtons = function( $row )
		{
			return array(
				'delete'	=> array(
					'icon'	=> 'times-circle',
					'title'	=> 'delete',
					'link'	=> $this->url( 'unreact' )->csrf()->setQueryString( array( 'member' => $row['member_id'] ) ),
					'data' 	=> array( 'confirm' => TRUE )
				)
			);
		};
		
		return $table;
	}

	/**
	 * @brief	Cached Reacted
	 */
	protected array $hasReacted = array();

	/**
	 * Has reacted?
	 *
	 * @param	Member|NULL	$member	The member, or NULL for currently logged in
	 * @return    Reaction|FALSE
	 */
	public function reacted( ?Member $member = NULL ): Reaction|bool
	{
		$member = $member ?: Member::loggedIn();

		if( !isset( $this->hasReacted[ $member->member_id ] ) )
		{
			$this->hasReacted[ $member->member_id ] = FALSE;

			try
			{
				if ( $this->reputation and count( $this->reputation ) )
				{
					if ( isset( $this->reputation[ $member->member_id ] ) )
					{
						$this->hasReacted[ $member->member_id ] = Content\Reaction::load( $this->reputation[ $member->member_id ] );
					}
				}
				elseif ( ! is_array( $this->reputation ) )
				{
					/* $this->reputation is not set, so we need to query the database */
					$where = $this->getReactionWhereClause( NULL, FALSE );
					$where[] = array( 'member_id=?', $member->member_id );
					$this->hasReacted[ $member->member_id ] = Content\Reaction::load( Db::i()->select( 'reaction', 'core_reputation_index', $where )->first() );
				}
			}
			catch( UnderflowException ){}
		}

		return $this->hasReacted[ $member->member_id ];
	}
	
	/**
	 * @brief	Cached React Blurb
	 */
	public array|null $reactBlurb = NULL;

	/**
	 * React Blurb
	 *
	 * @param array|null $reactionCounts
	 * @return    array
	 */
	public function reactBlurb( array|null $reactionCounts=null ): array
	{
		if ( $this->reactBlurb === NULL )
		{
			$this->reactBlurb = array();

			if ( $reactionCounts !== null )
			{
				$this->reactBlurb = $reactionCounts;
			}
			/*
			 	If we have lots of rows, then use a more efficient way of getting the data (but does increase query count and uses a group by).
				I know this is an artibrary number, but it should mean that most communities never need to run this code, and just those with topics with more than 4,000 pages
			*/
			else if ( ( $this instanceof Item) and isset( $this::$databaseColumnMap['num_comments'] ) and $this->mapped( 'num_comments' ) >= 100000 )
			{
				$enabledReactions = [];
				foreach(Reaction::enabledReactions() as $reaction )
				{
					$enabledReactions[] = $reaction->id;
				}

				foreach( Db::i()->select( 'COUNT(*) as count, reaction', 'core_reputation_index', $this->getReactionWhereClause( NULL , FALSE ), 'count DESC', NULL, 'reaction' ) as $row )
				{
					if( in_array( needle: $row['reaction'], haystack: $enabledReactions ) )
					{
						$this->reactBlurb[ $row['reaction'] ] = $row['count'];
					}
				}
			}
			else
			{
				if ( count( $this->reactions() ) )
				{
					if ( is_array( $this->_reactions ) )
					{
						foreach ( $this->_reactions as $memberId => $reactions )
						{
							foreach ( $reactions as $reaction )
							{
								if ( !isset( $this->reactBlurb[$reaction] ) )
								{
									$this->reactBlurb[$reaction] = 0;
								}

								$this->reactBlurb[$reaction]++;
							}
						}
					}
					else
					{
						foreach ( Db::i()->select( 'reaction', 'core_reputation_index', $this->getReactionWhereClause() )->join( 'core_reactions', 'reaction=reaction_id' ) as $rep )
						{
							if ( !isset( $this->reactBlurb[$rep] ) )
							{
								$this->reactBlurb[$rep] = 0;
							}

							$this->reactBlurb[$rep]++;
						}
					}

					/* Error suppressor for https://bugs.php.net/bug.php?id=50688 */
					$enabledReactions = Content\Reaction::enabledReactions();

					@uksort( $this->reactBlurb, function ( $a, $b ) use ( $enabledReactions ) {
						$positionA = $enabledReactions[$a]->position;
						$positionB = $enabledReactions[$b]->position;

						if ( $positionA == $positionB )
						{
							return 0;
						}

						return ( $positionA < $positionB ) ? -1 : 1;
					} );
				}
				else
				{
					$this->reactBlurb = array();
				}
			}
		}

		return $this->reactBlurb;
	}
	
	/**
	 * @brief	Cached like blurb
	 */
	public array $likeBlurb	= [];
	
	/**
	 * Who Reacted
	 *
	 * @param	bool|NULL	$isLike	Use like text instead? NULL to automatically determine
	 * @param 	bool		$anonymized	Whether to anonymize the result; when true, the result will not contain "You"
	 *
	 * @return	string
	 */
	public function whoReacted( ?bool $isLike = NULL, bool $anonymized=false ): string
	{
		if ( $isLike === NULL )
		{
			$isLike =  Content\Reaction::isLikeMode();
		}

		$blurbKey = $anonymized ? 'anon' : 'reg';
		if( !isset( $this->likeBlurb[$blurbKey] ) )
		{
			$langPrefix = 'react_';
			if ( $isLike )
			{
				$langPrefix = 'like_';
			}

			/* Did anyone like it? */
			$numberOfLikes = count( $this->reactions() ); # int
			if ( $numberOfLikes )
			{
				/* Is it just us? */
				$userLiked = ( !$anonymized AND $this->reacted() );
				if ( $userLiked and $numberOfLikes < 2 )
				{
					$this->likeBlurb[$blurbKey] = Member::loggedIn()->language()->addToStack("{$langPrefix}blurb_just_you");
				}
				/* Nope, we need to display a number... */
				else
				{
					$peopleToDisplayInMainView = array();
					$peopleToDisplayInSecondaryView = array(); // This is only used for "like" mode (i.e. there's only 1 reputation type)
					$andXOthers = $numberOfLikes;

					/* If the user liked, we always show "You" first */
					if ( $userLiked )
					{
						$peopleToDisplayInMainView[] = Member::loggedIn()->language()->addToStack("{$langPrefix}blurb_you_and_others");
						$andXOthers--;
					}
										
					/* Some random names */
					$i = 0;
					$where = $this->getReactionWhereClause();
					if ( !$anonymized )
					{
						$where[] = array( 'member_id!=?', Member::loggedIn()->member_id ?: 0 );
					}

					foreach (Db::i()->select( '*', 'core_reputation_index', $where, 'RAND()', Content\Reaction::isLikeMode() ? 18 : ( $userLiked ? 2 : 3 ) )->join( 'core_reactions', 'reaction=reaction_id' ) as $rep )
					{
						if ( $i < ( $userLiked ? 2 : 3 ) )
						{
							$peopleToDisplayInMainView[] = Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( Member::load( $rep['member_id'] ) );
							$andXOthers--;
						}
						else
						{
							$peopleToDisplayInSecondaryView[] = htmlspecialchars( Member::load( $rep['member_id'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
						}
						$i++;
					}
					
					/* If there's people to display in the secondary view, add that */
					if ( $andXOthers )
					{
						if ( count( $peopleToDisplayInSecondaryView ) < $andXOthers )
						{
							$peopleToDisplayInSecondaryView[] = Member::loggedIn()->language()->addToStack( "{$langPrefix}blurb_others_secondary", FALSE, array( 'pluralize' => array( $andXOthers - count( $peopleToDisplayInSecondaryView ) ) ) );
						}
						$peopleToDisplayInMainView[] = Theme::i()->getTemplate( 'global', 'core', 'front' )->reputationOthers( $this->url( 'showReactions' ), Member::loggedIn()->language()->addToStack( "{$langPrefix}blurb_others", FALSE, array( 'pluralize' => array( $andXOthers ) ) ), json_encode( $peopleToDisplayInSecondaryView ) );
					}
					
					/* Put it all together */
					$this->likeBlurb[$blurbKey] = Member::loggedIn()->language()->addToStack( "{$langPrefix}blurb", FALSE, array( 'pluralize' => array( $numberOfLikes ), 'htmlsprintf' => array( Member::loggedIn()->language()->formatList( $peopleToDisplayInMainView ) ) ) );
				}				
			}
			/* Nobody liked it - show nothing */
			else
			{
				$this->likeBlurb[$blurbKey] = '';
			}
		}
				
		return $this->likeBlurb[$blurbKey];
	}

	/**
	 * Return boolean indicating if *any* reaction elements are available to the user
	 * Provides a convenient way of reducing logic in frontend templates rather than having to check every condition.
	 *
	 * @return	bool
	 */
	public function hasReactionBar(): bool
	{
		/* If we're in count mode and have > 0 */
		if ( Settings::i()->reaction_count_display == 'count' && $this->reactionCount() )
		{
			return TRUE;
		}

		/* Not in count mode and have some blurb to show */
		if( Settings::i()->reaction_count_display !== 'count' && $this->reactBlurb() && count( $this->reactBlurb() ) )
		{
			return TRUE;
		}

		if( $this->canReact() )
		{
			return TRUE;
		}

		return FALSE;
	}
}