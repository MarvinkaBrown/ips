<?php
/**
 * @brief		Future publishing Trait for Content Models
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Jan 2013
 */

namespace IPS\Content;

use IPS\Content;
use IPS\Events\Event;
use IPS\IPS;
use IPS\Member;
use IPS\Db;
use	IPS\DateTime;
use IPS\Node\Model;
use IPS\Content\Search\Index;
use IPS\Helpers\Form\Date;
use BadMethodCallException;

use function defined;
use function header;
use function time;
use function is_array;
use function method_exists;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Future publishing Trait for Content Models
 */
trait FuturePublishing
{
	/**
	 * Can view future publishing items?
	 *
	 * @param Member|NULL	    $member	        The member to check for (NULL for currently logged in member)
	 * @param Model|null    $container      Container
	 * @return	bool
	 * @note	If called without passing $container, this method falls back to global "can view hidden content" moderator permission which isn't always what you want - pass $container if in doubt
	 */
	public static function canViewFutureItems( ?Member $member=NULL, ?Model $container = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		return $container ? static::modPermission( 'view_future', $member, $container ) : $member->modPermission( "can_view_future_content" );
	}

	/**
	 * Can set items to be published in the future?
	 *
	 * @param Member|NULL	    $member	        The member to check for (NULL for currently logged in member)
	 * @param Model|null    $container      Container
	 * @return	bool
	 */
	public static function canFuturePublish( ?Member $member=NULL, ?Model $container = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		return $container ? static::modPermission( 'future_publish', $member, $container ) : $member->modPermission( "can_future_publish_content" );
	}

	/**
	 * Can publish future items?
	 *
	 * @param Member|NULL	    $member	        The member to check for (NULL for currently logged in member)
	 * @param Model|null    $container      Container
	 * @return	bool
	 */
	public function canPublish( ?Member $member=NULL, ?Model $container = NULL ): bool
	{
		if( !$this->actionEnabled( 'publish', $member ) )
		{
			return false;
		}

		return static::canFuturePublish( $member, $container );
	}

	/**
	 * "Unpublishes" an item.
	 * @note    This will not change the item's date. This should be done via the form methods if required
	 *
	 * @param Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @return	void
	 */
	public function unpublish( ?Member $member=NULL ): void
	{
		/* Now do the actual stuff */
		if ( isset( static::$databaseColumnMap['is_future_entry'] ) AND isset( static::$databaseColumnMap['future_date'] ) )
		{
			$future = static::$databaseColumnMap['is_future_entry'];

			$this->$future = 1;
		}
		
		$this->save();
		$this->onUnpublish( $member );

		/* And update the tags perm cache */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Taggable' ) )
		{
			Db::i()->update( 'core_tags_perms', array( 'tag_perm_visible' => 0 ), array( 'tag_perm_aai_lookup=?', $this->tagAAIKey() ) );
		}

		/* Update search index */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->removeFromSearchIndex( $this );
		}

		$this->expireWidgetCaches();
		$this->adjustSessions();
	}

	/**
	 * Publishes a 'future' entry now
	 *
	 * @param Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @return	void
	 */
	public function publish( ?Member $member=NULL ): void
	{
		/* Now do the actual stuff */
		if ( isset( static::$databaseColumnMap['is_future_entry'] ) AND isset( static::$databaseColumnMap['future_date'] ) )
		{
			$date   = static::$databaseColumnMap['future_date'];
			$future = static::$databaseColumnMap['is_future_entry'];

			$this->$date = time();
			$this->$future = 0;
		}

		$dateColumn = static::$databaseColumnMap['date'];
		if ( \is_array( $dateColumn ) )
		{
			$dateColumn = array_pop( $dateColumn );
		}
		$this->$dateColumn = time();

		/* Update the item */
		if ( isset( static::$databaseColumnMap['last_comment'] ) or isset( static::$databaseColumnMap['last_review'] ) )
		{
			if ( isset( static::$databaseColumnMap['last_comment'] ) )
			{
				$lastCommentField = static::$databaseColumnMap['last_comment'];
				if ( is_array( $lastCommentField ) )
				{
					foreach ( $lastCommentField as $column )
					{
						$this->$column = time();
					}
				}
				else
				{
					$this->$lastCommentField = time();
				}
			}

			if ( isset( static::$databaseColumnMap['last_review'] ) )
			{
				$lastReviewField = static::$databaseColumnMap['last_review'];
				$this->$lastReviewField = time();
			}
		}

		$this->save();
		$this->onPublish( $member );

		/* And update the tags perm cache */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Taggable' ) )
		{
			Db::i()->update( 'core_tags_perms', array( 'tag_perm_visible' => 1 ), array( 'tag_perm_aai_lookup=?', $this->tagAAIKey() ) );
		}

		/* Update the first comment */
		if ( static::$firstCommentRequired )
		{
			$comment = $this->firstComment();

			/* @var array $databaseColumnMap */
			$date  = $comment::$databaseColumnMap['date'];

			$comment->$date = time();
			$comment->save();
		}

		/* Mark this item as read for the author */
		$this->markRead( $this->author() );

		/* Update search index */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( ( static::$firstCommentRequired ) ? $this->firstComment() : $this );
		}

		/* Send notifications if necessary */
		$this->sendNotifications();
		
		/* Give out points */
		$this->author()->achievementAction( 'core', 'NewContentItem', $this );
	}

	/**
	 * Syncing to run when publishing something previously pending publishing
	 *
	 * @param Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onPublish(Member|null|bool $member ): void
	{
		if ( method_exists( $this, 'container' ) )
		{
			try
			{
				$container = $this->container();

				if ( $container->_futureItems !== NULL )
				{
					$container->_futureItems = ( $container->_futureItems > 0 ) ? $container->_futureItems - 1 : 0;
				}

				if( !$this->skipContainerRebuild )
				{
					$container->_items = $container->_items + 1;

					if ( isset( static::$commentClass ) )
					{
						$container->_comments = $container->_comments + $this->mapped('num_comments');
						$container->setLastComment();
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
		
		Event::fire( 'onStatusChange', $this, array( 'publish' ) );

		/* Synch topics */
		if( IPS::classUsesTrait( $this,'IPS\Content\ItemTopic' ) )
		{
			$this->itemPublished( $member );
		}
	}

	/**
	 * Syncing to run when unpublishing an item (making it a future dated entry when it was already published)
	 *
	 * @param Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onUnpublish( Member|NULL|false $member ): void
	{
		if ( method_exists( $this, 'container' ) )
		{
			try
			{
				$container = $this->container();

				if ( $container->_futureItems !== NULL )
				{
					$container->_futureItems = $container->_futureItems + 1;
				}

				$container->_items = $container->_items - 1;

				if ( isset( static::$commentClass ) )
				{
					$container->_comments = $container->_comments - $this->mapped('num_comments');
					$container->setLastComment();
				}
				if ( isset( static::$reviewClass ) )
				{
					$container->_reviews = $container->_reviews - $this->mapped('num_reviews');
					$container->setLastReview();
				}

				$container->save();
			}
			catch ( BadMethodCallException ) { }
		}
		
		Event::fire( 'onStatusChange', $this, array( 'unpublish' ) );

		/* Synch topics */
		if( IPS::classUsesTrait( $this,'IPS\Content\ItemTopic' ) )
		{
			$this->itemUnpublished( $member );
		}
	}
	
	/**
	 * Is this a future entry?
	 *
	 * @return bool
	 */
	public function isFutureDate(): bool
	{
		if ( isset( static::$databaseColumnMap['is_future_entry'] ) and isset( static::$databaseColumnMap['future_date'] ) )
		{
			$column = static::$databaseColumnMap['future_date'];
			if ( $this->$column > time() )
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			throw new BadMethodCallException( "Class using must implement is_future_entry and future_date column maps." );
		}
	}

	/**
	 * Return the tooltip blurb for future entries
	 *
	 * @return string
	 */
	public function futureDateBlurb(): string 
	{
		$column = static::$databaseColumnMap['future_date'];
		$time   = DateTime::ts( $this->$column );
		return  Member::loggedIn()->language()->addToStack("content_future_date_blurb", FALSE, array( 'sprintf' => array( $time->localeDate(), $time->localeTime() ) ) );
	}
	
	/**
	 * Set future publishing dates
	 *
	 * @param	array	$values	Values from form
	 * @return	void
	 */
	public function setFuturePublishingDates( array $values ): void
	{
		$time = $values[ static::$formLangPrefix . 'date' ] instanceof DateTime ? $values[ static::$formLangPrefix . 'date' ] : DateTime::create();

		if( isset( static::$databaseColumnMap['date'] ) )
		{
			$isFutureEntry = ( $time->getTimestamp() > time() );
			$column = static::$databaseColumnMap['is_future_entry'];
			if( $this->$column != $isFutureEntry )
			{
				$this->$column = $isFutureEntry;
			}
		}

		if( isset( static::$databaseColumnMap['future_date'] ) )
		{
			$column = static::$databaseColumnMap['future_date'];
			$this->$column =  $time->getTimestamp();
		}
	}

	/**
	 * Returns the Date Field for the publish date
	 *
	 * @param Content|null $item
	 * @return Date
	 */
	public static function getPublishDateField( ?Content $item = NULL ): Date
	{
		/* If it's not published, we don't want to allow any past times */
		$column = static::$databaseColumnMap['future_date'];
		
		$minFutureTime = static::getMinimumPublishDate();
		$unlimited = 0;
		$unlimitedLang = 'immediately';
		if( $item )
		{
			$unlimited = NULL;
			$unlimitedLang = NULL;
		}

		return new Date( static::$formLangPrefix . 'date', ( $item and $item->$column ) ? DateTime::ts( $item->$column ) : 0, FALSE, array( 'time' => TRUE, 'unlimited' => $unlimited, 'unlimitedLang' => $unlimitedLang, 'min' => $minFutureTime ), NULL, NULL, NULL,  static::$formLangPrefix . 'date' );
	}

	/**
	 * Can the publish date be changed while editing the item?
	 * Formerly a properly, however classes cannot overload / redeclare properties from traits.
	 *
	 * @return bool
	 */
	public static function allowPublishDateWhileEditing(): bool
	{
		return FALSE;
	}

	/**
	 * Whether this content supports future publish dates
	 *
	 * @param Item|null $item
	 * @return bool
	 */
	public static function supportsPublishDate( ?Item $item ): bool
	{
		return isset( static::$databaseColumnMap['future_date'] ) AND ( ( $item AND ( $item->isFutureDate() OR static::allowPublishDateWhileEditing() ) OR !$item ) );
	}

	/**
	 * Returns the earliest publish date for the new content item , should be the current timestamp for most content types
	 *
	 * @return DateTime|null
	 */
	public static function getMinimumPublishDate(): DateTime|NULL
	{
		return DateTime::create();
	}
}