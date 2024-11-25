<?php
/**
 * @brief		Helpful Trait
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Jul 2023
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DomainException;
use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\IPS;
use IPS\Member;
use IPS\Platform\Bridge;
use IPS\Redis;
use RedisException;
use UnderflowException;
use function array_key_exists;
use function defined;
use function get_class;
use function iterator_to_array;
use function strtolower;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Helpful Trait
 */
trait Helpful
{
	/**
	 * Mark Helpful
	 *
	 * @param Member|null $member The member giving mark, or NULL
	 * @return    void
	 * @throws    DomainException
	 * @throws Exception
	 */
	public function markHelpful( ?Member $member = NULL ): void
	{
		/* Did we pass a member? */
		$member = $member ?: Member::loggedIn();

		/* Can we mark helpful? */
		if ( !$this->canView( $member ) or !$this->canMarkHelpful() )
		{
			throw new DomainException( 'cannot_mark_helpful' );
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

		/* Actually insert it */
		$idColumn = static::$databaseColumnId;
		Db::i()->insert( 'core_solved_index', array(
			'member_id'				=> $this->author()->member_id,
			'app'					=> $app,
			'solved_date'				=> DateTime::create()->getTimestamp(),
			'member_given'			=> $member->member_id,
			'comment_class'				=> get_class( $this ),
			'comment_id'				=> $this->$idColumn,
			'item_id'					=> $this->item()->tid,
			'type'					=> 'helpful',
			'node_id'				=> $this->item()->container()->_id
		) );

		/* Trending */
		if( Application::appIsEnabled( 'cloud' ) and Bridge::i()->featureIsEnabled( 'trending' ) )
		{
			$item = $this->item();

			$itemIdColumn	= $item::$databaseColumnId;
			$itemId			= $item->$itemIdColumn;
			try
			{
				Redis::i()->zIncrBy( 'trending', time() * 0.5, get_class( $item ) .'__' . $itemId );
			}
			catch( BadMethodCallException | RedisException ) {}
		}

		/* Reset some cached values */
		$this->_helpfulCount	= NULL;
		$this->_helpfuls		= NULL;

		$this->hasMarkedHelpful[ $member->member_id ] = true;

		if ( IPS::classUsesTrait( $this->item(), 'IPS\Content\Statistics' ) )
		{
			$this->item()->clearCachedStatistics();
		}

		/* Achievements */
		$this->author()->achievementAction( 'forums', 'AnswerMarkedBest', [ 'post' => $this, 'type' => 'helpful' ] );
	}
	
	/**
	 * Unmark Helpful
	 *
	 * @param	Member|NULL		$member					The member, or NULL for currently logged in member
	 * @return	void
	 */
	public function unmarkHelpful( ?Member $member = NULL ): void
	{
		$member = $member ?: Member::loggedIn();

		if ( !$this->canView( $member ) or !$this->canMarkHelpful() )
		{
			throw new DomainException( 'cannot_mark_helpful' );
		}

		$idColumn = static::$databaseColumnId;
		Db::i()->delete( 'core_solved_index', array( "app=? AND comment_id=? AND member_given=? AND type=?", static::$application, $this->$idColumn, $member->member_id, 'helpful' ) );

		/* Reset some cached values */
		$this->_helpfulsCount	= NULL;
		$this->helpfuls		= NULL;

		if( isset( $this->hasMarkedHelpful[ $member->member_id ] ) )
		{
			unset( $this->hasMarkedHelpful[ $member->member_id ] );
		}

		if ( IPS::classUsesTrait( $this->item(), 'IPS\Content\Statistics' ) )
		{
			$this->item()->clearCachedStatistics();
		}
	}

	/**
	 * @brief	Helpfuls Cache
	 */
	protected array|null $_helpfuls = null;

	/**
	 * @brief Helpful Count
	 */
	protected int|null $_helpfulCount = null;

	/**
	 * Number of helpful comments per item
	 */
	protected int|null $_helpfulRepliesCount = null;

	/**
	 * Get the number of comments marked helpful in this item
	 * @note We could probably cache this in the topic row, but it's a quick and efficient query
	 *
	 * @return	int
	 */
	public function helpfulsRepliesCount(): int
	{
		if ( $this->_helpfulRepliesCount === NULL )
		{
			$idColumn = static::$databaseColumnId;
			try
			{
				$this->_helpfulRepliesCount = (int)Db::i()->select( 'COUNT( DISTINCT(comment_id) )', 'core_solved_index', [["app=? AND item_id=? AND type=? AND hidden=0", static::$application, $this->$idColumn, 'helpful']], null, null, 'item_id' )->first();
			}
			catch( Exception )
			{
				$this->_helpfulRepliesCount = 0;
			}
		}

		return $this->_helpfulRepliesCount;
	}

	/**
	 * Helpful Count
	 *
	 * @return int
	 */
	public function helpfulCount(): int
	{
		if( isset( $this->helpful ) )
		{
			/* This may be set in Item::_comments() */
			$this->_helpfulCount = $this->helpful['count'];
			if( $this->_helpfulCount === 0 )
			{
				$this->_helpfuls = [];
			}
		}
		else if( $this->_helpfulCount === NULL )
		{
			$this->helpfuls();
		}

		return $this->_helpfulCount;
	}

	/**
	 * @brief	Cached Reacted
	 */
	protected array $hasMarkedHelpful = array();

	/**
	 * Has marked helpful?
	 *
	 * @param	Member|NULL	$member	The member, or NULL for currently logged in
	 * @return    bool
	 */
	public function markedHelpful( ?Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		if ( array_key_exists( 'helpful', $this->_data ) and is_array( $this->helpful['marked_by'] ) )
		{
			/* This may be set in Item::_comments() */
			$this->hasMarkedHelpful[ $member->member_id ] = in_array( $member->member_id, $this->helpful['marked_by'] );
		}
		else if( !isset( $this->hasMarkedHelpful[ $member->member_id ] ) )
		{
			$this->hasMarkedHelpful[ $member->member_id ] = FALSE;

			try
			{
				$idColumn = static::$databaseColumnId;
				$this->hasMarkedHelpful[ $member->member_id ] = (bool) Db::i()->select( 'id', 'core_solved_index', array( "app=? AND comment_id=? AND member_given=? AND type=? AND hidden=0", static::$application, $this->$idColumn, $member->member_id, 'helpful' )  )->first();
			}
			catch( UnderflowException ){}
		}

		return $this->hasMarkedHelpful[ $member->member_id ];
	}

	/**
	 * Helpfuls
	 *
	 * @return	array
	 */
	public function helpfuls(): array
	{
		if ( $this->_helpfulCount === NULL )
		{
			$this->_helpfulCount = 0;
		}

		if ( $this->_helpfuls === NULL )
		{
			$this->_helpfuls = array();

			$idColumn = static::$databaseColumnId;
			foreach( Db::i()->select( '*', 'core_solved_index', array( array( "app=? AND comment_id=? AND type=? and hidden=0", static::$application, $this->$idColumn, 'helpful' ) ) ) as $helpful )
			{
				$this->_helpfuls[ $helpful['member_given'] ] = true;
				$this->_helpfulCount += 1;
			}
		}

		return $this->_helpfuls;
	}

	/**
	 * Can user mark this item helpful?
	 *
	 * @param Member|null $member The member (null for currently logged in member)
	 * @return    bool
	 */
	public function canMarkHelpful( ?Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* It's a no for guests */
		if ( ! $member->member_id )
		{
			return FALSE;
		}

		if( isset( static::$archiveClass ) AND method_exists( $this, 'isArchived' ) AND $this->isArchived() )
		{
			return FALSE;
		}

		if ( $member === $this->author() )
		{
			return FALSE;
		}

		/* Otherwise yes */
		return TRUE;
	}

	/*
	 * @brief Members who are an expert in this node
	 */
	protected static array $expertsInThisNode = [];

	/**
	 * Is the author an expert in this node?
	 *
	 * @return bool
	 */
	public function authorIsAnExpert() : bool
	{
		/* Do they have it disabled? */
		if( !Bridge::i()->featureIsEnabled( 'experts' )  or $this->author()->members_bitoptions['expert_user_disabled'] or $this->author()->members_bitoptions['expert_user_blocked'] )
		{
			return false;
		}
		
		if ( $this instanceof Comment )
		{
			$container = $this->item()->container();
		}
		else
		{
			$container = $this->container();
		}

		if( !array_key_exists( $container->_id, static::$expertsInThisNode ) )
		{
			static::$expertsInThisNode[ $container->_id ] = iterator_to_array( Db::i()->select( 'member_id', 'core_expert_users', [ 'node_id=?', $container->_id ] ) );
		}

		return in_array( $this->author()->member_id, static::$expertsInThisNode[ $container->_id ] );
	}

	/**
	 * The item contains 1 or more helpful comments
	 *
	 * @param Item $item
	 * @return bool
	 */
	public static function itemHasHelpful( Item $item ): bool
	{
		try
		{
			return (bool) $item->helpfulPosts(1);
		}
		catch( Exception ) {}

		return false;
	}
}