<?php
/**
 * @brief		Content Comment Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Jul 2013
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\Application\Module;
use IPS\core\Messenger\Conversation;
use IPS\core\Warnings\Warning;
use IPS\Content;
use IPS\Content\Search\Index;
use IPS\core\Approval;
use IPS\core\DataLayer;
use IPS\core\IndexNow;
use IPS\core\ProfileFields\Field;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Db\Select;
use IPS\Dispatcher\Front;
use IPS\Events\Event;
use IPS\File;
use IPS\Helpers\Form\Captcha;
use IPS\Helpers\Menu;
use IPS\Helpers\Menu\Separator;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Redis;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use RedisException;
use function array_key_exists;
use function array_slice;
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
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Comment Model
 */
abstract class Comment extends Content
{
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static array $commentTemplate = array( array( 'global', 'core', 'front' ), 'commentContainer' );
	
	/**
	 * @brief	[Content\Comment]	Form Template
	 */
	public static array $formTemplate = array( array( 'forms', 'core', 'front' ), 'commentTemplate' );
	
	/**
	 * @brief	[Content\Comment]	The ignore type
	 */
	public static string $ignoreType = 'topics';

	/**
	 * @brief	[Content\Comment]	EditLine Template
	 */
	public static array $editLineTemplate = array( array( 'global', 'core', 'front' ), 'commentEditLine' );

	/**
	 * Create comment
	 *
	 * @param Item $item The content item just created
	 * @param string $comment The comment
	 * @param bool $first Is the first comment?
	 * @param string|null $guestName If author is a guest, the name to use
	 * @param bool|null  $incrementPostCount Increment post count? If NULL, will use static::incrementPostCount()
	 * @param Member|null  $member The author of this comment. If NULL, uses currently logged in member.
	 * @param DateTime|null  $time The time
	 * @param string|null  $ipAddress The IP address or NULL to detect automatically
	 * @param int|null  $hiddenStatus NULL to set automatically or override: 0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @param int|null  $anonymous NULL for no value, 0 or 1 for a value (0=no, 1=yes)
	 * @return    static|null
	 */
	public static function create( Item $item, string $comment, bool $first=false, string|null $guestName=null, bool|null $incrementPostCount= null, Member|null $member= null, DateTime|null $time= null, string|null $ipAddress= null, int|null $hiddenStatus= null, int|null $anonymous= null ): static|null
	{
		if ( $member === NULL )
		{
			$member = Member::loggedIn();
		}

		$idColumn = $item::$databaseColumnId;

		/* Create the object */
		$obj = new static;
		foreach ( array( 'item', 'date', 'author', 'author_name', 'content', 'ip_address', 'first', 'approved', 'hidden' ) as $k )
		{
			if ( isset( static::$databaseColumnMap[ $k ] ) )
			{
				$val = NULL;
				switch ( $k )
				{
					case 'item':
						$val = $item->$idColumn;
						break;
					
					case 'date':
						$val = ( $time ) ? $time->getTimestamp() : time();
						break;
					
					case 'author':
						$val = (int) $member->member_id;
						break;
						
					case 'author_name':
						$val = ( $member->member_id ) ? $member->name : ( $guestName ?: '' );
						break;
						
					case 'content':
						$val = $comment;
						break;
						
					case 'ip_address':
						$val = $ipAddress ?: Request::i()->ipAddress();
						break;
					
					case 'first':
						$val = $first;
						break;
						
					case 'approved':
						if( IPS::classUsesTrait( $obj, Hideable::class ) )
						{
							if ( $first ) // If this is the first post within an item, don't mark it hidden, otherwise the count of unapproved comments/items will include both the comment and the item when really only the item is hidden
							{
								$val = TRUE;
							}
							elseif ( $hiddenStatus === NULL )
							{
								$permissionCheckFunction = in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) ? 'canReview' : 'canComment';
								if ( !$member->member_id and !$item->$permissionCheckFunction( $member, FALSE ) )
								{
									$val = -3;
								}
								elseif ( in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) )
								{
									$val = $item->moderateNewReviews( $member ) ? 0 : 1;
								}
								else
								{
									$val = $item->moderateNewComments( $member ) ? 0 : 1;
								}
							}
						}

						if( $val === null or !IPS::classUsesTrait( $obj, Hideable::class ) )
						{
							switch ( $hiddenStatus )
							{
								case 0:
									$val = 1;
									break;
								case 1:
									$val = 0;
									break;
								case -1:
									$val = -1;
									break;
							}
						}
						break;
					
					case 'hidden':
						if( !IPS::classUsesTrait( $obj, Hideable::class ) )
						{
							$val = $hiddenStatus;
						}
						if ( $first )
						{
							$val = FALSE; // If this is the first post within an item, don't mark it hidden, otherwise the count of unapproved comments/items will include both the comment and the item when really only the item is hidden
						}
						elseif ( IPS::classUsesTrait( $item, Hideable::class ) and $item->approvedButHidden() )
						{
							$val = 2;
						}
						elseif ( $hiddenStatus === NULL )
						{
							$permissionCheckFunction = in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) ? 'canReview' : 'canComment';
							if ( !$member->member_id and !$item->$permissionCheckFunction( $member, FALSE ) )
							{
								$val = -3;
							}
							elseif ( in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) )
							{
								$val = $item->moderateNewReviews( $member ) ? 1 : 0;
							}
							else
							{
								$val = $item->moderateNewComments( $member ) ? 1 : 0;
							}
						}
						else
						{
							$val = $hiddenStatus;
						}
						break;
				}
				
				foreach ( is_array( static::$databaseColumnMap[ $k ] ) ? static::$databaseColumnMap[ $k ] : array( static::$databaseColumnMap[ $k ] ) as $column )
				{
					$obj->$column = $val;
				}
			}
		}

		/* Check if profanity filters should mod-queue this comment */
		if ( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) )
		{
			$obj->checkProfanityFilters( $first, false );
		}

		/* Save the comment */
		$obj->save();

		/* Set anonymous status */
		if( $anonymous !== NULL )
		{
			try
			{
				$obj->setAnonymous( (bool) $anonymous, $member );
			}
			catch ( \Exception $e ) { }
		}
		
		/* Increment post count */
		try
		{
			if ( ( IPS::classUsesTrait( $obj, 'IPS\Content\Anonymous' ) AND !$obj->isAnonymous() ) and !$obj->hidden() and ( $incrementPostCount === TRUE or ( $incrementPostCount === NULL and static::incrementPostCount( $item->container() ) ) ) )
			{
				$member->member_posts++;
			}
		}
		catch( BadMethodCallException $e ) { }
		

		/* Update member's last post and daily post limits */
		if( $member->member_id AND $obj::incrementPostCount() )
		{
			$member->member_last_post = time();

			/* Update posts per day limits */
			if ( $member->group['g_ppd_limit'] )
			{
				$current = $member->members_day_posts;
				
				$current[0] += 1;
				if ( $current[1] == 0 )
				{
					$current[1] = DateTime::create()->getTimestamp();
				}

				$member->members_day_posts = $current;
			}

			$member->save();
		}
		
		/* Send notifications and dish out points */
		if ( !in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) )
		{
			if ( !( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) and $obj->hidden() ) and ( !$first or !$item::$firstCommentRequired ) )
			{
				$obj->sendNotifications();
				if( !$item instanceof Conversation )
				{
					$member->achievementAction( 'core', 'Comment', $obj );
				}
			}
			else if( ( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) and $obj->hidden() === 1 ) )
			{
				$obj->sendUnapprovedNotification();
			}
		}
		
		/* Send webhook */
		if ( IPS::classUsesTrait( $obj, 'IPS\Content\Hideable' ) and in_array( $obj->hidden(), array( -1, 0, 1 ) ) ) // i.e. not post before register or pending deletion
		{
			Webhook::fire( str_replace( '\\', '', substr( get_called_class(), 3 ) ) . '_create', $obj, $obj->webhookFilters() );
		}

		/* DataLayer Event */
		if ( Settings::i()->core_datalayer_enabled and !( $item::$firstCommentRequired and $obj->isFirst() ) )
		{
			$event = 'comment_create';
			$properties = $obj->getDataLayerProperties();

			if ( $properties['content_area'] === 'personal_messages' )
			{
				return NULL;
			}

			$properties = array_merge( $properties, array(
				'author_id'   => $obj->author()->member_id,
				'author_name' => $obj->author()->name ?? 'Guest',
			) );

			DataLayer::i()->addEvent( $event, $properties );
		}

		/* Update item */
		$obj->postCreate();

		/* This needs to happen before the index is added */
		if( $item::$firstCommentRequired and $obj->isFirst() )
		{
			$container = $obj->container();
			$parameters = array_merge( array( 'newContentItem-' . $item::$application . '/' . $item::$module  . '-' . ( $container ? $container->_id : 0 ) ), $obj->attachmentIds() );
		}
		else
		{
			$parameters = array_merge( array( 'reply-' . $item::$application . '/' . $item::$module  . '-' . $item->$idColumn ), $obj->attachmentIds() );
		}
		File::claimAttachments( ...$parameters );

		/* Add to search index */
		if( Content\Search\SearchContent::isSearchable( $obj ) )
		{
			Index::i()->index( $obj );
		}

		/* Return */
		return $obj;
	}

	/**
	 * @brief   Field cache for getDataLayerProperties
	 */
	protected array $_dataLayerProperties = array();

	/**
	 * Get the properties that can be added to the datalayer for this key
	 *
	 * @return  array
	 */
	public function getDataLayerProperties(): array
	{
		if ( empty( $this->_dataLayerProperties ) )
		{
			$item = $this->item();
			$this->_dataLayerProperties = $item->getDataLayerProperties( $this );
			if ( $this->_dataLayerProperties['content_type'] === 'question' )
			{
				$this->_dataLayerProperties['comment_type'] = 'answer';
			}
			$this->_dataLayerProperties = DataLayer::i()->filterProperties( $this->_dataLayerProperties );
		}

		return $this->_dataLayerProperties;
	}
	
	/**
	 * Join profile fields when loading comments?
	 */
	public static bool $joinProfileFields = FALSE;
	
	/**
	 * Joins (when loading comments)
	 *
	 * @param Item $item			The item
	 * @return	array
	 */
	public static function joins( Item $item ): array
	{
		$return = array();
		
		/* Author */
		$authorColumn = static::$databasePrefix . static::$databaseColumnMap['author'];
		$return['author'] = array(
			'select'	=> 'author.*',
			'from'		=> array( 'core_members', 'author' ),
			'where'		=> array( 'author.member_id = ' . static::$databaseTable . '.' . $authorColumn )
		);
		
		/* Author profile fields */
		if ( static::$joinProfileFields and Field::fieldsForContentView() )
		{
			$return['author_pfields'] = array(
				'select'	=> 'author_pfields.*',
				'from'		=> array( 'core_pfields_content', 'author_pfields' ),
				'where'		=> array( 'author_pfields.member_id=author.member_id' )
			);
		}
				
		return $return;
	}
	
	/**
	 * Do stuff after creating (abstracted as comments and reviews need to do different things)
	 *
	 * @return	void
	 */
	public function postCreate(): void
	{
		if ( Bridge::i()->checkCommentForSpam( $this ) )
		{
			/* This is spam, so do not continue */
			return;
		}

		$item = $this->item();
		$itemIdColumn = $item::$databaseColumnId;

		$item->resyncCommentCounts();
			
		if( isset( static::$databaseColumnMap['date'] ) )
		{
			if( is_array( static::$databaseColumnMap['date'] ) )
			{
				$postDateColumn = static::$databaseColumnMap['date'][0];
			}
			else
			{
				$postDateColumn = static::$databaseColumnMap['date'];
			}
		}

		if ( !IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) or ( !$this->hidden() or $item->approvedButHidden() ) )
		{
			if ( isset( $item::$databaseColumnMap['last_comment'] ) )
			{
				$lastCommentField = $item::$databaseColumnMap['last_comment'];
				if ( is_array( $lastCommentField ) )
				{
					foreach ( $lastCommentField as $column )
					{
						$item->$column = ( isset( $postDateColumn ) ) ? $this->$postDateColumn : time();
					}
				}
				else
				{
					$item->$lastCommentField = ( isset( $postDateColumn ) ) ? $this->$postDateColumn : time();
				}
			}
			if ( isset( $item::$databaseColumnMap['last_comment_by'] ) )
			{
				$lastCommentByField = $item::$databaseColumnMap['last_comment_by'];
				$item->$lastCommentByField = (int) $this->author()->member_id;
			}
			if ( isset( $item::$databaseColumnMap['last_comment_name'] ) )
			{
				$lastCommentNameField = $item::$databaseColumnMap['last_comment_name'];
				$item->$lastCommentNameField = $this->mapped('author_name');
			}
			
			$item->save();
			
			if ( ( !IPS::classUsesTrait( $item, Hideable::class ) or ( !$item->hidden() and ! $item->approvedButHidden() ) ) and $item->containerWrapper() and $item->container()->_comments !== NULL and ( !IPS::classUsesTrait( $item, 'IPS\Content\FuturePublishing' ) OR !$item->isFutureDate() ) )
			{
				$item->container()->_comments = ( $item->container()->_comments + 1 );
				$item->container()->setLastComment( $this, $item );
				$item->container()->save();
			}
		}
		elseif( IPS::classUsesTrait( $this, Hideable::class ) )
		{
			$item->save();

			if ( $item->containerWrapper() AND !$item->approvedButHidden() AND $this->hidden() == 1 AND $item->container()->_unapprovedComments !== NULL )
			{
				$item->container()->_unapprovedComments = ( $item->container()->_unapprovedComments >= 0 ) ? ( $item->container()->_unapprovedComments + 1 ) : 0;
				$item->container()->save();
			}
		}

		/* Are we tracking keywords? */
		if ( $this->content() )
		{
			$this->checkKeywords( $this->content() );
		}

		if ( IPS::classUsesTrait( $item, 'IPS\Content\Statistics' ) )
		{
			$item->clearCachedStatistics();
		}

		/* Update mappings */
		if ( $item->containerWrapper() and IPS::classUsesTrait( $item->container(), 'IPS\Node\Statistics' ) )
		{
			$item->container()->rebuildPostedIn( array( $item->$itemIdColumn ), array( $this->author() ) );
		}

		/* Update trending */
		$class = get_class( $item );
		if( Application::appIsEnabled( 'cloud' ) and Bridge::i()->featureIsEnabled('trending') and $class::$includeInTrending )
		{
			/* Score by timestamp for trending */
			try
			{
				Redis::i()->zIncrBy( 'trending', time(), $class . '__' . $item->$itemIdColumn );
			}
			catch( BadMethodCallException | RedisException $e ) { }
		}
		
		/* Was it moderated? Let's see why. */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) and $this->hidden() === 1 )
		{
			$idColumn = static::$databaseColumnId;
			
			/* Check we don't already have a reason from profanity / url / email filters */
			try
			{
				Approval::loadFromContent( get_called_class(), $this->$idColumn );
			}
			catch( OutOfRangeException $e )
			{
				/* If the user is mod-queued - that's why. These will cascade, so check in that order. */
				$foundReason = FALSE;
				$log = new Approval;
				$log->content_class	= get_called_class();
				$log->content_id	= $this->$idColumn;
				if ( $this->author()->mod_posts )
				{
					
					$log->held_reason	= 'user';
					$foundReason = TRUE;
				}
				
				/* If the user isn't mod queued, but is in a group that is, that's why. */
				if ( $foundReason === FALSE AND $this->author()->group['g_mod_preview'] )
				{
					$log->held_reason	= 'group';
					$foundReason = TRUE;
				}
				
				/* If the user isn't on mod queue, but the container requires approval, that's why. */
				if ( $foundReason === FALSE )
				{
					try
					{
						if ( $item->container() AND $item->container()->contentHeldForApprovalByNode( 'comment', $this->author() ) === TRUE )
						{
							$log->held_reason = 'node';
							$foundReason = TRUE;
						}
					}
					catch( BadMethodCallException $e ) { }
				}
				
				/* Finally if the item itself requires moderation, that's why */
				if (
					$foundReason === FALSE AND
					IPS::classusesTrait( $class, 'IPS\Content\MetaData' ) AND
					is_array( $item::supportedMetaDataTypes() ) AND
					in_array('core_ItemModeration', $item::supportedMetaDataTypes() ) AND
					Application::load('core')->extensions( 'core', 'MetaData' )['ItemModeration']->enabled( $item, $this->author() )
				)
				{
					$log->held_reason = 'item';
					$foundReason = TRUE;
				}
				
				if ( $foundReason )
				{
					$log->save();
				}
			}
		}
	}

	/**
	 * @brief	Value to set for the 'tab' parameter when redirecting to the comment (via _find())
	 */
	public static ?array $tabParameter	= array( 'tab' => 'comments' );

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 */
	public function url( ?string $action='find' ): Url
	{
		$idColumn = static::$databaseColumnId;
		
		return $this->item()->url()->setQueryString( array(
			'do'		=> $action . 'Comment',
			'comment'	=> $this->$idColumn
		) );
	}

	/**
	 * @var array Shareable URLs
	 */
	protected static $shareableUrls = array();
	/**
	 * Get a shareable URL
	 *
	 * @return    Url
	 */
	public function shareableUrl( string $type='comment' ): Url
	{
		$currentPage = Front::i()->currentPage ?? 1;
		$idColumn = static::$databaseColumnId;
		$item = $this->item();
		$itemIdColumn = $item::$databaseColumnId;

		/* Avoid building a single item's URL multiple times, such as when viewing a topic */
		if ( isset( static::$shareableUrls[ $type ][ $item->$itemIdColumn ] ) )
		{
			$url = static::$shareableUrls[ $type ][ $item->$itemIdColumn ];
		}
		else
		{
			$url = $item->url()->setPage( 'page', $currentPage );

			/* if we can do both comments and reviews, just return the findCommment url as we have tabs to navigate anyway */
			if ( isset( $item::$reviewClass ) and isset( $item::$commentClass ) )
			{
				if ( method_exists( $item, 'commentReviewTabs' ) )
				{
					if ( count( $item->commentReviewTabs() ) > 1 )
					{
						$url = $url->setQueryString( ['tab' => $type . 's'] );
					}
				}
			}

			static::$shareableUrls[ $type ][ $item->$itemIdColumn ] = $url;
		}

		return $url->setFragment( 'find' . ucfirst( $type ) . '-' . $this->$idColumn );
	}

	/**
	 * Get containing item
	 *
	 * @return    Item
	 */
	public function item(): Item
	{
		$itemClass = static::$itemClass;
		return $itemClass::load( $this->mapped( 'item' ) );
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		return $this->item()->primaryImage();
	}
	
	/**
	 * Is first message?
	 *
	 * @return	bool
	 */
	public function isFirst(): bool
	{
		if ( isset( static::$databaseColumnMap['first'] ) )
		{
			if ( $this->mapped('first') )
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Get permission index ID
	 *
	 * @return	int|NULL
	 */
	public function permId(): int|NULL
	{
		return $this->item()->permId();
	}
	
	/**
	 * Can view?
	 *
	 * @param Member|null $member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( Member|null $member= null ): bool
	{
		if( $member === NULL )
		{
			$member	= Member::loggedIn();
		}
				
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) and $this->hidden() and !$this->item()->canViewHiddenComments( $member ) and ( $this->hidden() !== 1 or ( $this->author()->member_id AND $this->author() !== $member ) ) )
		{
			return FALSE;
		}

		return $this->item()->canView( $member );
	}
	
	/**
	 * Can edit?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEdit( Member|null $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'edit', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();

		/* Are we restricted from posting or have an unacknowledged warning? */
		if ( $member->restrict_post or ( $member->members_bitoptions['unacknowledged_warnings'] and Settings::i()->warn_on and Settings::i()->warnings_acknowledge ) )
		{
			return FALSE;
		}

		if ( $member->member_id AND $this->canView( $member ) )
		{
			$item = $this->item();
			
			/* Do we have moderator permission to edit stuff in the container? */
			if ( static::modPermission( 'edit', $member, $item->containerWrapper() ) )
			{
				return TRUE;
			}
			
			/* Can the member edit their own content? */
			if ( $member->member_id == $this->author()->member_id and ( $member->group['g_edit_posts'] == '1' or in_array( get_class( $item ), explode( ',', $member->group['g_edit_posts'] ) ) ) )
			{
				if ( IPS::classUsesTrait( $item, 'IPS\Content\Lockable' ) AND $item->locked() )
				{
					return FALSE;
				}
				
				if ( !$member->group['g_edit_cutoff'] )
				{
					return TRUE;
				}
				else
				{
					if( DateTime::ts( $this->mapped('date') )->add( new DateInterval( "PT{$member->group['g_edit_cutoff']}M" ) ) > DateTime::create() )
					{
						return TRUE;
					}
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Can delete?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( Member|null $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'delete', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();

		return ( !$this->isFirst() and ( static::modPermission( 'delete', $member, $this->item()->containerWrapper() ) or ( $member->member_id and $member->member_id == $this->author()->member_id and ( $member->group['g_delete_own_posts'] == '1' or in_array( get_class( $this->item() ), explode( ',', $member->group['g_delete_own_posts'] ) ) ) ) ) );
	}
	
	/**
	 * Can split this comment off?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canSplit( Member|null $member=null ): bool
	{
		if( !$this->actionEnabled( 'split', $member ) )
		{
			return false;
		}

		$itemClass = static::$itemClass;

		if ( $itemClass::$firstCommentRequired )
		{
			if ( !$this->isFirst() )
			{
				$member = $member ?: Member::loggedIn();
				return $itemClass::modPermission( 'split_merge', $member, $this->item()->containerWrapper() );
			}
		}
		return FALSE;
	}
	
	/**
	 * Should this comment be ignored?
	 *
	 * @param	Member|null	$member	The member to check for - NULL for currently logged in member
	 * @return	bool
	 */
	public function isIgnored( Member|null $member=NULL ): bool
	{
		if ( !Settings::i()->ignore_system_on )
		{
			return FALSE;
		}
		
		if ( $member === NULL )
		{
			$member = Member::loggedIn();
		}
				
		return $member->isIgnoring( $this->author(), static::$ignoreType );
	}
	
	/**
	 * Get date line
	 *
	 * @return	string
	 */
	public function dateLine(): string
	{
		if( $this->mapped('first') )
		{
			return Member::loggedIn()->language()->addToStack( static::$formLangPrefix . 'date_started', FALSE, array( 'htmlsprintf' => array( DateTime::ts( $this->mapped('date') )->html() ) ) );
		}
		else
		{
			return Member::loggedIn()->language()->addToStack( static::$formLangPrefix . 'date_replied', FALSE, array( 'htmlsprintf' => array( DateTime::ts( $this->mapped('date') )->html() ) ) );
		}
	}
	
	/**
	 * Edit Comment Contents - Note: does not add edit log
	 *
	 * @param	string	$newContent	New content
	 * @return	void
	 */
	public function editContents( string $newContent ): void
	{
		$sendNotifications = true;
		/* Check if profanity filters should mod-queue this comment */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) )
		{
			$sendNotifications = $this->checkProfanityFilters( $this->isFirst(), true, $newContent );
		}

		/* Do it */
		$valueField = static::$databaseColumnMap['content'];
		$oldValue = $this->$valueField;
		$this->$valueField = $newContent;
		$this->save();

		/* Update any last post data if required */
		if ( $this->item()->containerWrapper() )
		{
			$this->item()->container()->setLastComment();
			$this->item()->container()->save();
		}
		
		/* Send any new mention/quote notifications */
		$this->sendAfterEditNotifications( $oldValue );
		
		/* Reindex */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( $this );
		}

		/* Send notifications */
		if ( $sendNotifications AND !in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) )
		{
			if( $this->hidden() === 1 )
			{
				$this->sendUnapprovedNotification();
			}
		}

		if ( IPS::classUsesTrait( $this->item(), 'IPS\Content\Statistics' ) )
		{
			$this->item()->clearCachedStatistics();
		}
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		$template = static::$commentTemplate[1];

		return Theme::i()->getTemplate( static::$commentTemplate[0][0], static::$commentTemplate[0][1], ( isset( static::$commentTemplate[0][2] ) ) ? static::$commentTemplate[0][2] : NULL )->$template( $this->item(), $this );
	}
		
	/**
	 * Users to receive immediate notifications
	 *
	 * @param	int|array|null		$limit		LIMIT clause
	 * @param	boolean			$countOnly	Just return the count
	 * @return Select|int
	 */
	public function notificationRecipients( int|array|null $limit=array( 0, 25 ), bool $countOnly=FALSE ): Select|int
	{
		/* Do we only want the count? */
		if( $countOnly )
		{
			$count	= 0;
			$count	+= $this->author()->followersCount( 3, array( 'immediate' ), $this->mapped('date') );
			if( IPS::classUsesTrait( $this->item(), 'IPS\Content\Followable' ) )
			{
				$count	+= $this->item()->followersCount( 3, array( 'immediate' ), $this->mapped('date') );
			}
			
			return $count;
		}

		$memberFollowers = $this->author()->followers( 3, array( 'immediate' ), $this->mapped('date'), NULL );
		
		if( $memberFollowers !== NULL AND $memberFollowers !== 0 and IPS::classUsesTrait( $this->item(), 'IPS\Content\Followable' ) )
		{
			$unions	= array( 
				$this->item()->followers( 3, array( 'immediate' ), $this->mapped('date'), NULL ),
				$memberFollowers
			);

			return Db::i()->union( $unions, 'follow_added', $limit );
		}
		else if( IPS::classUsesTrait( $this->item(), 'IPS\Content\Followable' ) )
		{
			return $this->item()->followers( static::FOLLOW_PUBLIC + static::FOLLOW_ANONYMOUS, array( 'immediate' ), $this->mapped('date'), $limit, 'follow_added' );
		}
		else
		{
			return $memberFollowers;
		}
	}
	
	/**
	 * Create Notification
	 *
	 * @param	string|NULL		$extra		Additional data
	 * @return	Notification
	 */
	protected function createNotification( string|null $extra=NULL ): Notification
	{
		return new Notification( Application::load( 'core' ), 'new_comment', $this->item(), array( $this ) );
	}
	
	/**
	 * Move Comment to another item
	 *
	 * @param Item $item	The item to move this comment to
	 * @param	bool				$skip	Skip rebuilding new/old content item data (used for multimod where we can do it in one go after)
	 * @return	void
	 */
	public function move( Item $item, bool $skip=FALSE ): void
	{
		$oldItem = $this->item();
		
		$idColumn = $item::$databaseColumnId;
		$itemColumn = static::$databaseColumnMap['item'];
		$commentIdColumn = static::$databaseColumnId;
		$this->$itemColumn = $item->$idColumn;

		/* Does this comment's hidden status align with its item's hidden status? */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) AND $this->hidden() !== 1 )
		{
			if ( $item->hidden() === -1 )
			{
				if ( isset( static::$databaseColumnMap['hidden'] ) )
				{
					$column = static::$databaseColumnMap['hidden'];

					/* if this comment was directly hidden, we don't want to inherit its status from the item */
					if ( intval( $this->$column ) !== -1 )
					{
						$this->$column = 2;
					}
				}
				elseif ( isset( static::$databaseColumnMap['approved'] ) )
				{
					$column = static::$databaseColumnMap['approved'];
					$this->$column = -1;
				}
			}
			elseif ( $this->hidden() === -1 )
			{
				if ( isset( static::$databaseColumnMap['hidden'] ) )
				{
					$column = static::$databaseColumnMap['hidden'];

					/* if this comment was directly hidden, we don't want to inherit its status from the item */
					if ( intval( $this->$column ) !== -1 )
					{
						$this->$column = 0;
					}
				}
				elseif ( isset( static::$databaseColumnMap['approved'] ) )
				{
					$column = static::$databaseColumnMap['approved'];
					$this->$column = 1;
				}
			}
		}

		$this->save();
		
		/* The new item needs to re-claim any attachments associated with this comment */
		Db::i()->update( 'core_attachments_map', array( 'id1' => $item->$idColumn ), array( "location_key=? AND id1=? AND id2=?", $oldItem::$application . '_' . IPS::mb_ucfirst( $oldItem::$module ), $oldItem->$idColumn, $this->$commentIdColumn ) );

		/* Update notifications */
		Db::i()->update( 'core_notifications', array( 'item_id' => $item->$idColumn ), array( 'item_class=? and item_id=? and item_sub_class=? and item_sub_id=?', get_class( $item ), $oldItem->$idColumn, get_called_class(), $this->$commentIdColumn ) );
		
		/* Update reputation */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Reactable' ) )
		{
			Db::i()->update( 'core_reputation_index', array( 'item_id' => $item->$idColumn ), array( 'class_type_id_hash=?', md5( get_class( $this ) . ':' . $oldItem->$idColumn ) ) );
		}

		/* Update solved */
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Solvable' ) )
		{
			Db::i()->update( 'core_solved_index', array( 'item_id' => $item->$idColumn ), array( 'comment_class=? and comment_id=?', get_class( $this ), $oldItem->$idColumn ) );
		}

		/* Update member map */
		try
		{
			Db::i()->update( 'core_item_member_map', array( 'map_item_id' => $item->$idColumn ), array( 'map_class=? and map_item_id=?', get_class( $oldItem ), $oldItem->$idColumn ) );
		}
		catch( Exception $e )
		{
			/* If we are splitting to a topic that this member has already posted in, this will cause a duplicate key issue. Best to remove rows and let the next page load rebuild */
			Db::i()->delete( 'core_item_member_map', array( 'map_class=? and map_item_id=?', get_class( $oldItem ), $oldItem->$idColumn ) );
			Db::i()->delete( 'core_item_member_map', array( 'map_class=? and map_item_id=?', get_class( $item ), $item->$idColumn ) );
		}

		/* Update reports */
		Db::i()->update( 'core_rc_index', array( 'item_id' => $item->$idColumn, 'node_id' => ( $item->containerWrapper() ? $item->containerWrapper()->_id : 0 ) ), array( 'class=? and content_id=?', get_class( $this ), $oldItem->$idColumn ) );

		if( $skip === FALSE )
		{
			$oldItem->rebuildFirstAndLastCommentData();
			$item->rebuildFirstAndLastCommentData();

			/* Add to search index */
			if( Content\Search\SearchContent::isSearchable( $this ) )
			{
				Index::i()->index( $this );
			}

			if( Content\Search\SearchContent::isSearchable( $item ) )
			{
				$item->clearCachedStatistics();
			}
		}

		/* Send the content items URL to IndexNow */
		$noIndexUrls = [];
		$guest =  new Member;

		if( $oldItem->canView( $guest ) )
		{
			$noIndexUrls[] = $oldItem->url();
		}
		if( $item->canView( $guest ) )
		{
			$noIndexUrls[] = $item->url();
		}
		if( $oldItem->canView( new Member ) )
		{
			IndexNow::addUrlsToQueue( $noIndexUrls );
		}

        Event::fire( 'onCommentMove', $this, array( $oldItem, $skip ) );
	}
	
	/**
	 * Get container
	 *
	 * @return	Model|null
	 * @note	Certain functionality requires a valid container but some areas do not use this functionality (e.g. messenger)
	 * @note	Some functionality refers to calls to the container when managing comments (e.g. deleting a comment and decrementing content counts). In this instance, load the parent items container.
	 * @throws	OutOfRangeException|BadMethodCallException
	 */
	public function container(): ?Model
	{
		$container = NULL;
		
		try
		{
			$container = $this->item()->container();
		}
		catch( BadMethodCallException $e ) {}
		
		return $container;
	}
			
	/**
	 * Delete Comment
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* Remove from search index first */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->removeFromSearchIndex( $this );
		}

		/* Init */
		$idColumn = static::$databaseColumnId;
		$itemClass = static::$itemClass;
		$itemIdColumn = $itemClass::$databaseColumnId;

		/* It is possible to delete a comment that is orphaned, so let's try to protect against that */
		try
		{
			$item	= $this->item();
			$itemId	= $this->item()->$itemIdColumn;
		}
		catch( OutOfRangeException $e )
		{
			$item	= NULL;
			$itemId	= $this->mapped('item');
		}

		/* Remove featured comment associations */
		if( $item and IPS::classUsesTrait( $item, 'IPS\Content\MetaData' ) and $this->isFeatured() )
		{
			Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->unfeatureComment( $item, $this );
		}
		
		/* Unclaim attachments */
		File::unclaimAttachments( $itemClass::$application . '_' . IPS::mb_ucfirst( $itemClass::$module ), $itemId, $this->$idColumn );
		
		/* Reduce the number of comment/reviews count on the item but only if the item is unapproved or visible 
		 * - hidden as opposed to unapproved items do not get included in either of the unapproved_comments/num_comments columns */
		if( IPS::classUsesTrait( $item, Hideable::class ) )
		{
			if( $this->hidden() !== -1 AND $this->hidden() !== -2 AND $this->hidden() !== -3 )
			{
				$columnName = ( $this->hidden() === 1 ) ? 'unapproved_comments' : 'num_comments';
				if ( in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) )
				{
					$columnName = ( $this->hidden() === 1 ) ? 'unapproved_reviews' : 'num_reviews';
				}
				if ( isset( $itemClass::$databaseColumnMap[$columnName] ) AND $item !== NULL )
				{
					$column = $itemClass::$databaseColumnMap[$columnName];

					if ( $item->$column > 0 )
					{
						$item->$column--;
						$item->save();
					}
				}
			}
			else if ( $this->hidden() === -1 )
			{
				if ( in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) )
				{
					if( isset( $itemClass::$databaseColumnMap['hidden_reviews'] ) AND $item !== NULL )
					{
						$column = $itemClass::$databaseColumnMap['hidden_reviews'];

						if ( $item->$column > 0 )
						{
							$item->$column--;
							$item->save();
						}
					}
				}
				else
				{
					if( isset( $itemClass::$databaseColumnMap['hidden_comments'] ) AND $item !== NULL )
					{
						$column = $itemClass::$databaseColumnMap['hidden_comments'];

						if ( $item->$column > 0 )
						{
							$item->$column--;
							$item->save();
						}
					}
				}
			}
		}

		if ( $item AND IPS::classUsesTrait( $this->item(), 'IPS\Content\Solvable' ) and isset( $itemClass::$databaseColumnMap['solved_comment_id'] ) )
		{
			/* Reset item */
			if ( $item->mapped('solved_comment_id') and $item->mapped('solved_comment_id') == $this->$idColumn )
			{
				Db::i()->update( $itemClass::$databaseTable, array( $itemClass::$databaseColumnMap['solved_comment_id'] => 0 ), array( $itemIdColumn . '=?', $itemId ) );

				Db::i()->delete( 'core_solved_index', array( 'app=? and comment_class=? and item_id=? and comment_id=?', static::$application, get_called_class(), $itemId, $this->$idColumn ) );
			}
		}

		if ( $item AND IPS::classUsesTrait( $this->item(), 'IPS\Content\Statistics' ) )
		{
			$item->clearCachedStatistics();
		}

		/* Delete any notifications telling people about this */
		$memberIds	= array();

		foreach( Db::i()->select( '`member`', 'core_notifications', array( 'item_sub_class=? AND item_sub_id=?', get_called_class(), (int) $this->$idColumn ) ) as $member )
		{
			$memberIds[ $member ]	= $member;
		}

		Db::i()->delete( 'core_notifications', array( 'item_sub_class=? AND item_sub_id=?', get_called_class(), (int) $this->$idColumn ) );

		foreach( $memberIds as $member )
		{
			Member::load( $member )->recountNotifications();
		}

		/* Actually delete */
		parent::delete();
		
		/* Deletions can occur via cron - shut off joining of profile fields as templates and current logged in member object may be referenced. */
		static::$joinProfileFields = FALSE;

		/* Update last comment/review data for container and item */
		try
		{
			if ( $item !== NULL AND $item->container() !== NULL AND in_array( 'IPS\Content\Review', class_parents( get_called_class() ) ) )
			{
				if ( $item->container()->_reviews !== NULL )
				{
					$item->container()->_reviews = ( $item->container()->_reviews - 1 );
					$item->resyncLastReview();
					$item->save();
					$item->container()->setLastReview();
				}
				if ( $item->container()->_unapprovedReviews !== NULL )
				{
					$item->container()->_unapprovedReviews = ( $item->container()->_unapprovedReviews > 0 ) ? ( $item->container()->_unapprovedReviews - 1 ) : 0;
				}
				$item->container()->save();
			}
			else if ( $item !== NULL AND $item->container() !== NULL )
			{
				if ( $item->container()->_comments !== NULL )
				{
					if ( !$this->hidden() AND $this->hidden() !== -2 )
					{
						$item->container()->_comments = ( $item->container()->_comments > 0 ) ? ( $item->container()->_comments - 1 ) : 0;
					}	
					
					$item->resyncLastComment();
					$item->save();
					$item->container()->setLastComment();
				}
				if ( $item->container()->_unapprovedComments !== NULL and $this->hidden() === 1 )
				{
					$item->container()->_unapprovedComments = ( $item->container()->_unapprovedComments > 0 ) ? ( $item->container()->_unapprovedComments - 1 ) : 0;
				}
				$item->container()->save();
			}

			/* Update mappings */
			if ( $item !== NULL AND $item->container() !== NULL AND IPS::classUsesTrait( $item->container(), 'IPS\Node\Statistics' ) )
			{
				$item->container()->rebuildPostedIn( array( $item->$itemIdColumn ), array( $this->author() ) );
			}
		}
		catch ( BadMethodCallException $e ) {}

        Event::fire( 'onDelete', $this );
	}
	
	/**
	 * Change Author
	 *
	 * @param	Member	$newAuthor	The new author
	 * @param bool $log		If TRUE, action will be logged to moderator log
	 * @return	void
	 */
	public function changeAuthor( Member $newAuthor, bool $log=TRUE ): void
	{
		$oldAuthor = $this->author();

		/* If we delete a member, then change author, the old author returns 0 as does the new author as the
		   member row is deleted before the task is run */
		if( $newAuthor->member_id and ( $oldAuthor->member_id == $newAuthor->member_id ) )
		{
			return;
		}
		
		/* Update the row */
		parent::changeAuthor( $newAuthor, $log );
		
		/* Adjust post counts */
		if ( static::incrementPostCount( $this->item()->containerWrapper() ) AND ( $oldAuthor->member_id OR ( $this->hidden() === 0 AND $this->item()->hidden === 0 ) ) )
		{
			if( $oldAuthor->member_id )
			{
				$oldAuthor->member_posts--;
				$oldAuthor->save();
			}
			
			if( $newAuthor->member_id )
			{
				$newAuthor->member_posts++;
				$newAuthor->save();
			}
		}
		
		/* Last comment */
		$this->item()->resyncLastComment( $this );
		$this->item()->resyncLastReview( $this );
		$this->item()->save();
		if ( $container = $this->item()->containerWrapper() )
		{
			$container->setLastComment( updatedItem: $this->item() );
			$container->setLastReview();
			$container->save();
		}

		/* Update search index */
		if( Content\Search\SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( $this );
		}
	}
	
	/**
	 * Get template for content tables
	 *
	 * @return	array
	 */
	public static function contentTableTemplate(): array
	{
		return array( Theme::i()->getTemplate( 'tables', 'core', 'front' ), 'commentRows' );
	}
	
	/**
	 * Get content for header in content tables
	 *
	 * @return	string
	 */
	public function contentTableHeader(): string
	{
		return Theme::i()->getTemplate( 'global', static::$application )->commentTableHeader( $this, $this->item() );
	}

	/**
	 * @brief Cached containers we can access
	 */
	protected static array $permissionSelect	= array();

	/**
	 * Get comments based on some arbitrary parameters
	 *
	 * @param	array		$where					Where clause
	 * @param string|null $order					MySQL ORDER BY clause (NULL to order by date)
	 * @param	int|array|null	$limit					Limit clause
	 * @param	string|NULL	$permissionKey			A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index, or NULL to ignore permissions
	 * @param	mixed		$includeHiddenComments	Include hidden comments? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param	int			$queryFlags				Select bitwise flags
	 * @param Member|null  $member					The member (NULL to use currently logged in member)
	 * @param	bool		$joinContainer			If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinComments			If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinReviews			If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$countOnly				If true will return the count
	 * @param array|null  $joins					Additional arbitrary joins for the query
	 * @return	array|NULL|Comment        If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public static function getItemsWithPermission( array $where=array(), string $order= null, int|array|null $limit=10, string|null $permissionKey='read', mixed $includeHiddenComments= Filter::FILTER_AUTOMATIC, int $queryFlags=0, Member|null $member= null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins= null ): mixed
	{
		/* Get the item class - we need it later */
		$itemClass	= static::$itemClass;
		
		$itemWhere = array();
		$containerWhere = array();
		
		/* Queries are always more efficient when the WHERE clause is added to the ON */
		if ( is_array( $where ) )
		{
			foreach( $where as $key => $value )
			{
				if ( $key ==='item' )
				{
					$itemWhere = array_merge( $itemWhere, $value );
					
					unset( $where[ $key ] );
				}
				
				if ( $key === 'container' )
				{
					$containerWhere = array_merge( $containerWhere, $value );
					unset( $where[ $key ] );
				}
			}
		}
		
		/* Work out the order */
		$order = $order ?: ( static::$databasePrefix . static::$databaseColumnMap['date'] . ' DESC' );
		
		/* Exclude hidden comments */
		if( $includeHiddenComments === Filter::FILTER_AUTOMATIC )
		{
			if( static::modPermission( 'view_hidden', $member ) )
			{
				$includeHiddenComments = Filter::FILTER_SHOW_HIDDEN;
			}
			else
			{
				$includeHiddenComments = Filter::FILTER_OWN_HIDDEN;
			}
		}
		
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Hideable' ) and $includeHiddenComments === Filter::FILTER_ONLY_HIDDEN )
		{
			/* If we can't view hidden stuff, just return an empty array now */
			if( !static::modPermission( 'view_hidden', $member ) )
			{
				return array();
			}

			if ( isset( static::$databaseColumnMap['approved'] ) )
			{
				$where[] = array( static::$databasePrefix . static::$databaseColumnMap['approved'] . '=?', 0 );
			}
			elseif ( isset( static::$databaseColumnMap['hidden'] ) )
			{
				$where[] = array( static::$databasePrefix . static::$databaseColumnMap['hidden'] . '=?', 1 );
			}
		}
		elseif ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Hideable' ) and ( $includeHiddenComments === Filter::FILTER_OWN_HIDDEN OR $includeHiddenComments === Filter::FILTER_PUBLIC_ONLY ) )
		{
			if ( isset( static::$databaseColumnMap['approved'] ) )
			{
				$where[] = array( static::$databasePrefix . static::$databaseColumnMap['approved'] . '=?', 1 );
			}
			elseif ( isset( static::$databaseColumnMap['hidden'] ) )
			{
				$where[] = array( static::$databasePrefix . static::$databaseColumnMap['hidden'] . '=?', 0 );
			}
		}
		
		/* Exclude hidden items. We don't check FILTER_ONLY_HIDDEN because we should return hidden comments in both approved and unapproved topics. */
		if ( IPS::classUsesTrait( $itemClass, 'IPS\Content\Hideable' ) and ( $includeHiddenComments === Filter::FILTER_OWN_HIDDEN OR $includeHiddenComments === Filter::FILTER_PUBLIC_ONLY ) and isset( $itemClass::$databaseColumnMap['author'] ) )
		{
			$member = $member ?: Member::loggedIn();
			$authorCol = $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'];
			if ( isset( $itemClass::$databaseColumnMap['approved'] ) )
			{
				$col = $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['approved'];
				if ( $member->member_id AND $includeHiddenComments !== Filter::FILTER_PUBLIC_ONLY )
				{
					$itemWhere[] = array( "( {$col}=1 OR ( {$col}=0 AND {$authorCol}=" . $member->member_id . " ) )" );
				}
				else
				{
					$itemWhere[] = array( "{$col}=1" );
				}
			}
			elseif ( isset( $itemClass::$databaseColumnMap['hidden'] ) )
			{
				$col = $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['hidden'];
				if ( $member->member_id AND $includeHiddenComments !== Filter::FILTER_PUBLIC_ONLY )
				{
					$itemWhere[] = array( "( {$col}=0 OR ( {$col}=1 AND {$authorCol}=" . $member->member_id . " ) )" );
				}
				else
				{
					$itemWhere[] = array( "{$col}=0" );
				}
			}
		}
        else
        {
            /* Legacy items pending deletion in 3.x at time of upgrade may still exist */
            $col	= null;

            if ( isset( $itemClass::$databaseColumnMap['approved'] ) )
            {
                $col = $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['approved'];
            }
            else if( isset( $itemClass::$databaseColumnMap['hidden'] ) )
            {
                $col = $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['hidden'];
            }

            if( $col )
            {
            	$itemWhere[] = array( "{$col} < 2" );
            }
        }
        
        /* No matter if we can or cannot view hidden items, we do not want these to show: -2 is queued for deletion and -3 is posted before register */
        if ( isset( static::$databaseColumnMap['hidden'] ) )
        {
	        $col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['hidden'];
	        $where[] = array( "{$col}!=-2 AND {$col} !=-3" );
        }
        else if ( isset( static::$databaseColumnMap['approved'] ) )
        {
	        $col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['approved'];
	        $where[] = array( "{$col}!=-2 AND {$col} !=-3" );
        }

        /* We also need to check the item for soft delete and post before register */
        if( IPS::classUsesTrait( $itemClass, 'IPS\Content\Hideable' ) )
		{
			/* No matter if we can or cannot view hidden items, we do not want these to show: -2 is queued for deletion and -3 is posted before register */
			if ( isset( $itemClass::$databaseColumnMap['hidden'] ) )
			{
				$col = $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['hidden'];
				$itemWhere[] = array( "{$col}!=-2 AND {$col} !=-3" );
			}
			else if ( isset( $itemClass::$databaseColumnMap['approved'] ) )
			{
				$col = $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['approved'];
				$itemWhere[] = array( "{$col}!=-2 AND {$col} !=-3" );
			}
		}

		/* @var array $databaseColumnMap */
		/* Finally, we do not want posts from future items */
		if ( in_array( 'IPS\Content\FuturePublishing', class_implements( $itemClass ) ) )
		{
			$itemWhere[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['is_future_entry'] . '=0' );
		}

        if ( $joinContainer AND isset( $itemClass::$containerNodeClass ) )
		{
			$containerClass = $itemClass::$containerNodeClass;
			if( $joins !== NULL )
			{
				$map = $itemClass::$databaseColumnMap;
				array_unshift( $joins, array(
					'from'	=> 	$containerClass::$databaseTable,
					'where'	=> array_merge( array( array( $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . '=' . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) ), $containerWhere )
				) );
			}
			else
			{
				$joins = array(
					array(
						'from'	=> 	$containerClass::$databaseTable,
						'where'	=> array_merge( array( array( $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . '=' . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) ), $containerWhere )
					)
				);
			}
		}
        
		/* Build the select clause */
		if( $countOnly )
		{
			if ( $permissionKey !== NULL and in_array( 'IPS\Node\Permissions', class_implements( $itemClass::$containerNodeClass ) ) )
			{
				$member = $member ?: Member::loggedIn();
				
				$containerClass = $itemClass::$containerNodeClass;
				/* @var $permissionMap array */
				$select = Db::i()->select( 'COUNT(*) as cnt', static::$databaseTable, $where, NULL, NULL, NULL, NULL, $queryFlags )
					->join( $itemClass::$databaseTable, array_merge( array( array( static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnMap['item'] . "=" . $itemClass::$databaseTable . "." . $itemClass::$databasePrefix . $itemClass::$databaseColumnId ) ), $itemWhere ), 'STRAIGHT_JOIN' )
					->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $itemClass::$databaseTable . "." . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . ' AND (' . Db::i()->findInSet( 'perm_' . $containerClass::$permissionMap[ $permissionKey ], $member->groups ) . ' OR ' . 'perm_' . $containerClass::$permissionMap[ $permissionKey ] . '=? )', $containerClass::$permApp, $containerClass::$permType, '*' ), 'STRAIGHT_JOIN' );
			}
			else
			{
				$select = Db::i()->select( 'COUNT(*) as cnt', static::$databaseTable, $where, NULL, NULL, NULL, NULL, $queryFlags )
					->join( $itemClass::$databaseTable, array_merge( array( array( static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnMap['item'] . "=" . $itemClass::$databaseTable . "." . $itemClass::$databasePrefix . $itemClass::$databaseColumnId ) ), $itemWhere ), 'STRAIGHT_JOIN' );
			}

			if ( $joins !== NULL AND count( $joins ) )
			{
				foreach( $joins as $join )
				{
					$select->join( $join['from'], ( $join['where'] ?? null ), ( isset( $join['type'] ) ) ? $join['type'] : 'LEFT' );
				}
			}
			return $select->first();
		}

		$selectClause = static::$databaseTable . '.*';
		if ( $joins !== NULL AND count( $joins ) )
		{
			foreach( $joins as $join )
			{
				if ( isset( $join['select'] ) )
				{
					$selectClause .= ', ' . $join['select'];
				}
			}
		}
		
		if ( $permissionKey !== NULL and in_array( 'IPS\Node\Permissions', class_implements( $itemClass::$containerNodeClass ) ) )
		{
			$containerClass = $itemClass::$containerNodeClass;

			$member = $member ?: Member::loggedIn();
			$categories	= array();
			$lookupKey	= md5( $containerClass::$permApp . $containerClass::$permType . $permissionKey . json_encode( $member->groups ) );

			if( !isset( static::$permissionSelect[ $lookupKey ] ) )
			{
				static::$permissionSelect[ $lookupKey ] = array();
                /* @var $permissionMap array */
				$permQuery = Db::i()->select( 'perm_type_id', 'core_permission_index', array( "core_permission_index.app='" . $containerClass::$permApp . "' AND core_permission_index.perm_type='" . $containerClass::$permType . "' AND (" . Db::i()->findInSet( 'perm_' . $containerClass::$permissionMap[ $permissionKey ], $member->permissionArray() ) . ' OR ' . 'perm_' . $containerClass::$permissionMap[ $permissionKey ] . "='*' )" ) );
				
				if ( count( $containerWhere ) )
				{
					$permQuery->join( $containerClass::$databaseTable, array_merge( $containerWhere, array( 'core_permission_index.perm_type_id=' . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) ), 'STRAIGHT_JOIN' );
				}

				foreach( $permQuery as $result )
				{
					static::$permissionSelect[ $lookupKey ][] = $result;
				}
			}

			$categories = static::$permissionSelect[ $lookupKey ];
			if( count( $categories ) )
			{
				$where[]	= array( $itemClass::$databaseTable . "." . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . ' IN(' . implode( ',', $categories ) . ')' );
			}
			else
			{
				$where[]	= array( $itemClass::$databaseTable . "." . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . '=0' );
			}

			$selectClause .= ', ' . $itemClass::$databaseTable . '.*';

			$select = Db::i()->select( $selectClause, static::$databaseTable, $where, $order, $limit, NULL, NULL, $queryFlags )
				->join( $itemClass::$databaseTable, array_merge( array( array( static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnMap['item'] . "=" . $itemClass::$databaseTable . "." . $itemClass::$databasePrefix . $itemClass::$databaseColumnId ) ), $itemWhere ), 'STRAIGHT_JOIN' );
		}
		else
		{
			$select = Db::i()->select( $selectClause, static::$databaseTable, $where, $order, $limit, NULL, NULL, $queryFlags )
				->join( $itemClass::$databaseTable, array_merge( array( array( static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnMap['item'] . "=" . $itemClass::$databaseTable . "." . $itemClass::$databasePrefix . $itemClass::$databaseColumnId ) ), $itemWhere ), 'STRAIGHT_JOIN' );
		}
						
		if ( $joins !== NULL AND count( $joins ) )
		{
			foreach( $joins as $join )
			{
				$select->join( $join['from'], ( $join['where'] ?? null ), ( isset( $join['type'] ) ) ? $join['type'] : 'LEFT' );
			}
		}
				
		/* Return */
		return new ActiveRecordIterator( $select, get_called_class(), $itemClass );
	}
	
	/**
	 * Warning Reference Key
	 *
	 * @return	string|null
	 */
	public function warningRef(): string|null
	{
		/* If the member cannot warn, return NULL so we're not adding ugly parameters to the profile URL unnecessarily */
		if ( !Member::loggedIn()->modPermission('mod_can_warn') )
		{
			return NULL;
		}
		
		$itemClass = static::$itemClass;
		$idColumn = static::$databaseColumnId;
		return base64_encode( json_encode( array( 'app' => $itemClass::$application, 'module' => $itemClass::$module . '-comment' , 'id_1' => $this->mapped('item'), 'id_2' => $this->$idColumn ) ) );
	}
	
	/**
	 * Get attachment IDs
	 *
	 * @return	array
	 */
	public function attachmentIds(): array
	{
		$item = $this->item();
		$idColumn = $item::$databaseColumnId;
		$commentIdColumn = static::$databaseColumnId;
		return array( $this->item()->$idColumn, $this->$commentIdColumn ); 
	}
	
	/**
	 * Returns the content images
	 *
	 * @param	int|null	$limit				Number of attachments to fetch, or NULL for all
	 * @param	bool		$ignorePermissions	If set to TRUE, permission to view the images will not be checked
	 * @return	array|NULL
	 * @throws	BadMethodCallException
	 */
	public function contentImages( int|null $limit = NULL, bool $ignorePermissions = FALSE ): array|null
	{
		$idColumn = static::$databaseColumnId;
		$item = $this->item();
		$attachments = array();
		$itemIdColumn = $item::$databaseColumnId;
		$internal = Db::i()->select( 'attachment_id', 'core_attachments_map', array( 'location_key=? and id1=? and id2=?', $item::$application . '_' . IPS::mb_ucfirst( $item::$module ), $item->$itemIdColumn, $this->$idColumn ) );
		
		foreach( Db::i()->select( '*', 'core_attachments', array( array( 'attach_id IN(?)', $internal ), array( 'attach_is_image=1' ) ), 'attach_id ASC', $limit ) as $row )
		{
			$attachments[] = array( 'core_Attachment' => $row['attach_location'] );
		}

		/* IS there a club with a cover photo? */
		if( $container = $item->containerWrapper() )
		{
			if( IPS::classUsesTrait( $container, ClubContainer::class ) and $club = $container->club() )
			{
				$attachments[] = array( 'core_Clubs' => $club->cover_photo );
			}
		}
			
		return count( $attachments ) ? array_slice( $attachments, 0, $limit ) : NULL;
	}
	
	/**
	 * @brief	Existing warning
	 */
	public ?Warning $warning = NULL;

	/**
	 * Addition where needed for fetching comments
	 *
	 * @return	array|NULL
	 */
	public static function commentWhere(): array|NULL
	{
		return NULL;
	}
	
	/**
	 * Is a featured comment?
	 *
	 * @return	bool
	 * @note This is a wrapper for the extension so content items can extend and apply their own logic
	 */
	public function isFeaturedComment(): bool
	{
		if ( IPS::classUsesTrait( $this->item(), 'IPS\Content\MetaData' ) )
		{
			return Application::load('core')->extensions( 'core', 'MetaData' )['FeaturedComments']->isFeaturedComment( $this );
		}
		
		return FALSE;
	}
	
	/* !API */
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse	int			id			ID number
	 * @apiresponse	int			item_id		The ID number of the item this belongs to
	 * @apiresponse	\IPS\Member	author		Author
	 * @apiresponse	datetime	date		Date
	 * @apiresponse	string		content		The content
	 * @apiresponse	bool		hidden		Is hidden?
	 * @apiresponse	string		url			URL to content
	 * @apiresponse	array		reactions	Array of reactions given, array key is member_id of reaction giver ([member_id => [ title, id, value, icon ]])
	 */
	public function apiOutput( Member|null $authorizedMember = NULL ): array
	{
		$idColumn = static::$databaseColumnId;
		$itemColumn = static::$databaseColumnMap['item'];
		$return = array(
			'id'		=> $this->$idColumn,
			'item_id'	=> $this->$itemColumn,
			'author'	=> $this->author()->apiOutput( $authorizedMember ),
			'date'		=> DateTime::ts( $this->mapped('date') )->rfc3339(),
			'content'	=> $this->content(),
			'hidden'	=> (bool) $this->hidden(),
			'url'		=> (string) $this->url()
		);

		if ( IPS::classUsesTrait( $this, 'IPS\Content\Reactable' ) )
		{
			if ( $reactions = $this->reactions() )
			{
				$enabledReactions = Content\Reaction::enabledReactions();
				$finalReactions = [];
				foreach( $reactions as $memberId => $array )
				{
					foreach( $array as $reaction )
					{
						$finalReactions[ $memberId ][] = [
							'title' => $enabledReactions[ $reaction ]->_title,
							'id'    => $reaction,
							'value' => $enabledReactions[ $reaction ]->value,
							'icon'  => (string) $enabledReactions[ $reaction ]->_icon->url
							];
					}
				}

				$return['reactions'] = $finalReactions;
			}
			else
			{
				$return['reactions'] = [];
			}
		}

		return $return;
	}
	
	/**
	 * Webhook filters
	 *
	 * @return	array
	 */
	public function webhookFilters(): array
	{
		$filters = parent::webhookFilters();
		try
		{
			$item = $this->item();
		}
		catch ( OutOfRangeException $e )
		{
			$item = NULL;
		}

		/* This is kept for legacy compatibility, but starting with 4.6.13 the Content and Item class handle this inside their own webhookFilter methods */
		if( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) )
		{
			$filters['hidden'] = ( ( $this->hidden() ) or ( $item and $item->hidden() ) );
		}
		else
		{
			$filters['hidden'] = false;
		}

		if( $item )
		{
			if ( IPS::classUsesTrait( $item, 'IPS\Content\Lockable' ) )
			{
				$filters['locked'] = $item->locked();
			}
			if ( IPS::classUsesTrait( $item, 'IPS\Content\Pinnable' ) )
			{
				$filters['pinned'] = (bool) $item->mapped('pinned');
			}
			if ( IPS::classUsesTrait( $item, 'IPS\Content\Featurable' ) )
			{
				$filters['featured'] = (bool) $item->mapped('featured');
			}
			if ( IPS::classUsesTrait( $item, 'IPS\Content\Polls' ) )
			{
				$filters['hasPoll'] = (bool) $item->mapped('poll');
			}
		}

		return $filters;
	}

	public static string $moderationMenuIdPrefix = 'comment_';

	/**
	 * Returns the Comment Menu Instance
	 * 
	 * @param Member|NULL $member
	 * @return Menu
	 */
	public function menu( Member $member = NULL ): Menu
	{
		$member = $member ?: Member::loggedIn();
		$idField = static::$databaseColumnId;
		$menu = new Menu( static::$moderationMenuIdPrefix . $this->$idField, "fa-solid fa-ellipsis", 'ipsEntry__topButton ipsEntry__topButton--ellipsis');
		$menu->showCaret = FALSE;
		$menu->shrinkToButton = false;

		$contentType = ( $this instanceof Review ) ? 'Review' : 'Comment';

		if( $this->canReportOrRevoke() === TRUE )
		{
			$report = new ContentMenuLink( url: $this->url('report' ), languageString: Member::loggedIn()->language()->addToStack( 'report' ) );
			if( $member->member_id OR Captcha::supportsModal() )
			{
				$report->addAttribute( 'data-ipsDialog')
					->addAttribute( 'data-ipsDialog-size', 'medium' )
					->addAttribute( 'data-ipsDialog-remoteSubmit' )
					->addAttribute( 'data-ipsDialog-title', Member::loggedIn()->language()->addToStack( 'report' ) );
				$menu->add( $report );
			}
		}

		if ( ! Output::i()->reduceLinks() )
		{
			if ( $this->mapped( 'first' ) )
			{
				$share = new ContentMenuLink( url: $this->item()->url( 'share' ), languageString: Member::loggedIn()->language()->addToStack( $contentType === 'Comment' ? 'share_this_post' : 'share_this_review' ) );
			}
			else
			{
				if ( $contentType === 'Comment' )
				{
					$share = new ContentMenuLink( url: $this->shareableUrl(), languageString: Member::loggedIn()->language()->addToStack( 'share_this_post' ) );
				}
				else
				{
					$share = new ContentMenuLink( url: $this->shareableUrl('review'), languageString: Member::loggedIn()->language()->addToStack( 'share_this_review' ) );

				}
			}

			$share->opensDialog( title: Member::loggedIn()->language()->addToStack('share'), size: 'narrow', contentSelector: '#elShare' . ucfirst( static::$moderationMenuIdPrefix ) . $this->$idField . '_menu' )
				->addAttribute( 'data-role', 'share' . $contentType )
				->addAttribute( 'id', 'elShare' . ucfirst( static::$moderationMenuIdPrefix ) . $this->$idField )
				->addAttribute( 'rel', 'nofollow' );
			$menu->add( $share );
		}

		if( method_exists( $this, 'canRecognize') )
		{
			if( $this->canRecognize() === TRUE )
			{
				$menu->add( new ContentMenuLink( url: $this->author()->url()->setQueryString( array( 'do' => 'recognize', 'content_class' => get_class( $this ), 'content_id' => $this->$idField ) ), languageString: Member::loggedIn()->language()->addToStack( 'recognize_author', FALSE,[ 'sprintf' => $this->author()->name] ), opensDialog: true ) );
			}
			else if ( $this->canRemoveRecognize() )
			{
				$menu->add( new ContentMenuLink( url: $this->author()->url()->setQueryString( array( 'do' => 'unrecognize', 'content_class' => get_class( $this ), 'content_id' => $this->$idField ) ), languageString: Member::loggedIn()->language()->addToStack( 'recognize_author_remove', FALSE,[ 'sprintf' => $this->author()->name] ) ) );
			}
		}

		if( $this->canEdit($member ) or (IPS::classUsesTrait( $this, "\\IPS\\Content\\Hideable" ) and $this->canHide( $member )) or $this->canSplit( $member ) )
		{
			$menu->addSeparator();
		}
	
		if( $this->canEdit( $member ) )
		{
			if( $this->mapped('first') AND $this->item()->canEdit( $member ) )
			{
				$menu->add( new ContentMenuLink( url: $this->item()->url('edit' ), languageString: Member::loggedIn()->language()->addToStack( 'edit' ) ) );
			}
			else
			{
				$edit = new ContentMenuLink( url: $this->url('edit' ), languageString: Member::loggedIn()->language()->addToStack( 'edit' ) );
				$edit->addAttribute( 'data-action', 'edit' . $contentType );
				$menu->add( $edit );
			}
		}
		if( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) )
		{
			if( $this->hidden() == -2 AND $member->modPermission('can_manage_deleted_content'))
			{
				$restore = new ContentMenuLink( url: $this->url('restore' )->csrf(), languageString: Member::loggedIn()->language()->addToStack( 'restore_as_visible' ) );
				$restore->requiresConfirm( 'restore_as_visible_desc');
				$menu->add( $restore );

				$restoreHidden = new ContentMenuLink( url: $this->url('restore' )->csrf()->setQueryString( 'restoreAsHidden', 1 ), languageString: Member::loggedIn()->language()->addToStack( 'restore_as_hidden' ) );
				$restoreHidden->requiresConfirm( 'restore_as_hidden_desc');
				$menu->add( $restoreHidden );

				$delete = new ContentMenuLink( url: $this->url('delete')->csrf()->setQueryString('immediately', 1), languageString: 'delete_immediately');
				$delete->requiresConfirm( 'delete_immediately_desc');
				$menu->add( $delete );
			}

			if( $this->canHide( $member ) )
			{
				$hide = new ContentMenuLink( url: $this->url('hide' )->csrf(), languageString: Member::loggedIn()->language()->addToStack( 'hide' ) );
				$hide->opensDialog(title: Member::loggedIn()->language()->addToStack('hide'), destruct: TRUE);
				$menu->add( $hide );
			}

			if( $this->canUnhide($member) )
			{
				$menu->add( new ContentMenuLink( url: $this->url( 'unhide' )->csrf(), languageString: 'unhide', dataAttributes: [
					'data-ipsDialog-destructOnClose' => 'true'
				] ) );
			}
		}


		if( $this->canSplit( $member ) )
		{
			$split = new ContentMenuLink( url: $this->url('split' )->csrf(), languageString: Member::loggedIn()->language()->addToStack( 'split' ) );
			$title = Member::loggedIn()->language()->addToStack( 'split_to_new', FALSE,[ "sprintf" => [ Member::loggedIn()->language()->addToStack( static::$title ) ] ] );
			$split->opensDialog( title: $title, destruct: TRUE )
						   ->addAttribute( 'data-action', 'split' . $contentType );
			$menu->add( $split );
		}

		if( $this->canDelete( $member ) )
		{
			$delete = new ContentMenuLink( url: $this->url('delete' )->csrf(), languageString: Member::loggedIn()->language()->addToStack( 'delete' ),  dataAttributes: ['data-action'=>'deleteComment' ] );
			$delete->addAttribute( 'data-updateOnDelete', '#' . strtolower( $contentType ) . 'Count');
			$menu->add( $delete );
		}

		if( IPS::classUsesTrait( $this, 'IPS\Content\Featurable' ) )
		{
			if( $this->canFeature( $member ) )
			{
				$class = get_class( $this );
				$column = $class::$databaseColumnId;
				$id = $this->$column;

				if ( !$this->isFeatured() )
				{
					$feature = new ContentMenuLink( url: $this->url()->setQueryString( array( 'do' => 'feature', 'fromItem' => 1 ) ), languageString: 'promote_social_button' );
					$feature->addAttribute( 'data-ipsDialog-flashMessage', Member::loggedIn()->language()->addToStack( 'promote_flash_msg' ) )
						->addAttribute( 'data-ipsDialog-flashMessageTimeout', 5 )
						->addAttribute( 'data-ipsDialog-flashMessageEscape', 'false' )
						->addAttribute( 'data-ipsDialog' )
						->addAttribute( 'data-ipsDialog-size', 'large' )
						->addAttribute( 'data-ipsDialog-title', Member::loggedIn()->language()->addToStack( 'promote_social_button' ) );
				}
				else
				{
					$feature = new ContentMenuLink( url: $this->url()->csrf()->setQueryString( array( 'do' => 'unfeature', 'fromItem' => 1 ) ), languageString: 'demote_social_button' );
					$feature->addAttribute( 'data-ipsDialog-flashMessage', Member::loggedIn()->language()->addToStack( 'demote_flash_msg' ) )
						->addAttribute( 'data-ipsDialog-flashMessageTimeout', 5 )
						->addAttribute( 'data-ipsDialog-flashMessageEscape', 'false' )
						->addAttribute( 'data-ipsDialog-remoteSubmit', 'true' );
					$feature->requiresConfirm( 'demote_confirm' );
				}

				$menu->add( $feature );
			}
		}

		if( $this->ip_address AND $member->modPermission('can_use_ip_tools') AND  $member->canAccessModule( Module::get( 'core', 'modcp' ) ) )
		{
			$menu->addSeparator();
			$menu->add( new ContentMenuLink( url: Url::internal( 'app=core&module=modcp&controller=modcp&tab=ip_tools&ip=' . $this->ip_address), languageString: Member::loggedIn()->language()->addToStack( 'ip_address' ) . ' ' . $this->ip_address ) );
		}

		foreach( $this->ui( 'menuItems', array(), TRUE ) as $key => $link )
		{
			$menu->add( $link );
		}

		return $menu;
	}

	/**
	 * Is Spam?
	 *
	 * @return	bool
	 */
	public function isSpam(): bool
	{
		$idColumn = static::$databaseColumnId;
		return array_key_exists( $this->$idColumn, $this->item()->commentsMarkedSpam() );
	}

	/**
	 * Get the item feed id
	 *
	 * @return string
	 */
	public function get_feedId() : string
	{
		return $this->item()->feedId;
	}
}