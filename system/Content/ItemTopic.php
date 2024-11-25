<?php
/**
 * @brief		ItemTopic Trait
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Jun 2018
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content\Search\Index;
use IPS\DateTime;
use IPS\forums\Forum;
use IPS\forums\Topic;
use IPS\forums\Topic\Post;
use IPS\IPS;
use IPS\Member;
use IPS\Settings;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * ItemTopic Trait
 */
trait ItemTopic
{

	/**
	 * Get the topic title
	 *
	 * @return string
	 */
	abstract function getTopicTitle(): string;

	/**
	 * Get the topic content
	 *
	 * @return mixed
	 */
	abstract function getTopicContent(): mixed;

	/**
	 * Get the database column which stores the topic ID
	 *
	 * @return	string
	 */
	public static function topicIdColumn(): string
	{
		return static::$databaseColumnMap['item_topicid'];
	}

	/**
	 * Return the Forum ID
	 *
	 * @return	int
	 */
	abstract function getForumId() : int;

	/**
	 * Create/Update Topic
	 *
	 * @return	void
	 */
	public function syncTopic(): void
	{
		/* If this item is pending deletion, do nothing */
		if( $this->hidden() == -2 )
		{
			return;
		}

		$column = static::topicIdColumn();

		/* Existing topic */
		if ( $this->$column )
		{
			/* Get */
			try
			{
				$topic = Topic::load( $this->$column );
				$title = $this->getTopicTitle();
				Member::loggedIn()->language()->parseOutputForDisplay( $title );
				$topic->title = $title;
				if ( Settings::i()->tags_enabled )
				{
					$topic->setTags( $this->prefix() ? array_merge( $this->tags(), array( 'prefix' => $this->prefix() ) ) : $this->tags() );
				}
				$topic->save();
				$firstPost = $topic->comments( 1 );

				/* If the first post of the topic is missing, NULL will be returned */
				if( $firstPost === NULL )
				{
					throw new OutOfRangeException;
				}

				$content = $this->getTopicContent();
				Member::loggedIn()->language()->parseOutputForDisplay( $content );
				$firstPost->post = $content;
				$firstPost->save();
				Index::i()->index( $firstPost );
			}
			catch ( OutOfRangeException )
			{
				return;
			}
		}
		/* New topic */
		elseif( !isset( static::$skipTopicCreation ) or !static::$skipTopicCreation )
		{
			/* Create topic */
			try
			{
				$forum = Forum::load( $this->getForumId() );
			}
			catch( OutOfRangeException )
			{
				return;
			}

			$topic = Topic::createItem( $this->author(), $this->mapped('ip_address'), DateTime::ts( $this->mapped('date') ), $forum, $this->hidden() );
			$title = $this->getTopicTitle();
			Member::loggedIn()->language()->parseOutputForDisplay( $title );
			$topic->title = $title;
			$topic->topic_archive_status = Topic::ARCHIVE_EXCLUDE;
			$topic->save();
			
			if( IPS::classUsesTrait( $this, 'IPS\Content\Anonymous' ) AND $this->isAnonymous() )
			{
				try
				{
					$topic->setAnonymous( TRUE, $this->author );
				}
				catch( \BadMethodCallException ){}
			}
			
			$topic->markRead( $this->author() );
			if ( Settings::i()->tags_enabled )
			{
				$topic->setTags( $this->prefix() ? array_merge( $this->tags(), array( 'prefix' => $this->prefix() ) ) : $this->tags() );
			}

			/* Create post */
			$content = $this->getTopicContent();
			Member::loggedIn()->language()->parseOutputForDisplay( $content );
			$post = Post::create( $topic, $content, true, null, null, $this->author(), DateTime::ts( $topic->start_date ) );
			
			if( IPS::classUsesTrait( $this, 'IPS\Content\Anonymous' ) AND $this->isAnonymous() )
			{
				try
				{
					$post->setAnonymous( TRUE, $this->author );
				}
				catch( \BadMethodCallException ){}
			}
			
			$topic->topic_firstpost = $post->pid;
			$topic->save();
			Index::i()->index( $post );

			/* Send notifications */
			if ( !$topic->isFutureDate() AND !$topic->hidden() )
			{
				$topic->sendNotifications();
			}

			/* Update file */
			$this->$column = $topic->tid;
			$this->save();
		}
	}

	/**
	 * Get Topic (checks member's permissions)
	 *
	 * @param	bool	$checkPerms		Should check if the member can read the topic?
	 * @return	Topic|NULL
	 */
	public function topic( bool $checkPerms=TRUE ): Topic|NULL
	{
		$column = static::topicIdColumn();

		if ( Application::appIsEnabled('forums') and $this->$column )
		{
			try
			{
				return $checkPerms ? Topic::loadAndCheckPerms( $this->$column ) : Topic::load( $this->$column );
			}
			catch ( OutOfRangeException )
			{
				return NULL;
			}
		}

		return NULL;
	}

	/**
	 * Change Author
	 *
	 * @param	Member	$newAuthor	The new author
	 * @param bool $log		If TRUE, action will be logged to moderator log
	 * @return	void
	 */
	public function itemAuthorChanged( Member $newAuthor, bool $log=TRUE ): void
	{
		if ( Application::appIsEnabled( 'forums' ) )
		{
			if ( $topic = $this->topic() )
			{
				$topic->changeAuthor( $newAuthor, $log );
			}
		}
	}

	/**
	 * Process after the object has been edited on the front-end
	 *
	 * @return	void
	 */
	public function itemEdited(): void
	{
		if ( Application::appIsEnabled('forums') and $this->topic() )
		{
			$this->syncTopic();
		}
	}

	/**
	 * Callback to execute when tags are edited
	 *
	 * @return	void
	 */
	protected function itemTagsUpdated(): void
	{
		if ( Application::appIsEnabled('forums') and $this->topic() )
		{
			$this->syncTopic();
		}
	}

	/**
	 * Syncing to run when hiding
	 *
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function itemHidden( Member|null|bool $member ): void
	{
		if ( Application::appIsEnabled('forums') and $topic = $this->topic() )
		{
			$topic->hide( $member );
		}
	}

	/**
	 * Syncing to run when unhiding
	 *
	 * @param	bool					$approving	If true, is being approved for the first time
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function itemUnhidden( bool $approving, Member|null|bool $member ): void
	{
		if ( Application::appIsEnabled('forums') )
		{
			if ( $topic = $this->topic() )
			{
				$topic->unhide( $member );
			}
			elseif ( $approving and $this->getForumId() )
			{
				$this->syncTopic();
			}
		}
	}

	/**
	 * Run when an item is published
	 *
	 * @param Member|null $member
	 * @return void
	 */
	public function itemPublished( ?Member $member=null ) : void
	{
		if ( Application::appIsEnabled('forums') )
		{
			if ( $topic = $this->topic() )
			{
				if ( $topic->hidden() )
				{
					$topic->unhide( $member );
				}
			}
			elseif ( $this->getForumId() )
			{
				try
				{
					$this->syncTopic();
				}
				catch( Exception ){}
			}
		}
	}

	/**
	 * Run when an item is unpublished
	 *
	 * @param Member|null $member
	 * @return void
	 */
	public function itemUnpublished( ?Member $member=null ) : void
	{
		if ( Application::appIsEnabled('forums') AND $topic = $this->topic() )
		{
			$topic->hide( $member );
		}
	}

	/**
	 * Move
	 *
	 * @param bool $keepLink	If TRUE, will keep a link in the source
	 * @return	void
	 */
	public function itemMoved( bool $keepLink=FALSE ): void
	{
		if ( Application::appIsEnabled('forums') and $topic = $this->topic() )
		{
			if( $forumId = $this->getForumId() )
			{
				if( $topic->forum_id != $forumId )
				{
					try
					{
						$topic->move( Forum::load( $forumId ), $keepLink );
					}
					catch ( Exception ) { }
				}
				else
				{
					$this->syncTopic();
				}
			}
		}
	}

	/**
	 * Create from form
	 *
	 * @return void
	 */
	public function itemCreatedFromForm(): void
	{
		if ( Application::appIsEnabled('forums') and $this->getForumId() and !$this->hidden() )
		{
			$this->syncTopic();
		}
	}
}