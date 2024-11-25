<?php
/**
 * @brief		Content Review Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		4 Nov 2013
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Application;
use IPS\Content\Search\Index;
use IPS\Content\Search\SearchContent;
use IPS\core\Approval;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Notification;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function get_called_class;
use function get_class;
use function in_array;
use function is_array;
use function is_null;
use function json_encode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Review Model
 */
abstract class Review extends Comment
{
	
	/**
	 * @brief	[Content\Comment]	Form Template
	 */
	public static array $formTemplate = array( array( 'forms', 'core', 'front' ), 'reviewTemplate' );

	/**
	 * @brief	[Content\Comment]	Reviews Template
	 */
	public static array $commentTemplate = array( array( 'global', 'core', 'front' ), 'reviewContainer' );

	public static string $moderationMenuIdPrefix = 'review_';

	/**
	 * Create first comment (created with content item)
	 *
	 * @param Item $item The content item just created
	 * @param string $comment The comment
	 * @param bool $first Is the first comment?
	 * @param string|null $guestName If author is a guest, the name to use
	 * @param bool|null $incrementPostCount
	 * @param Member|null $member The author of this comment. If NULL, uses currently logged in member.
	 * @param DateTime|null $time The time
	 * @param string|null $ipAddress The IP address or NULL to detect automatically
	 * @param int|null $hiddenStatus NULL to set automatically or override: 0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @param int|null $anonymous NULL for no value, 0 or 1 for a value (0=no, 1=yes)
	 * @return Review|null
	 */
	public static function create( Item $item, string $comment, bool $first=false, string|null $guestName=null, bool|null $incrementPostCount= null, Member|null $member= null, DateTime|null $time= null, string|null $ipAddress= null, int|null $hiddenStatus= null, int|null $anonymous= null ): static|null
	{
		$obj = parent::create( $item, $comment, $first, $guestName, null, $member, $time, $ipAddress, $hiddenStatus );
		
		if ( isset( static::$databaseColumnMap['votes_data'] ) )
		{
			/* Make sure we start with a valid json array */
			$val = json_encode( [] );

			foreach ( is_array( static::$databaseColumnMap['votes_data'] ) ? static::$databaseColumnMap['votes_data'] : array( static::$databaseColumnMap['votes_data'] ) as $column )
			{
				$obj->$column = $val;
			}
		}

		if( $obj->author()->member_id AND $obj::incrementPostCount() )
		{
			$obj->author()->member_last_post = time();
			$obj->author()->save();
		}

		$obj->save();

		/* Have to do these AFTER rating is set */
		$itemClass = static::$itemClass;
		/* @var array $databaseColumnMap */
		$ratingField = $itemClass::$databaseColumnMap['rating'];

		$obj->item()->$ratingField = $obj->item()->averageReviewRating() ?: 0;
		$obj->item()->save();

		/* Send notifications and dish out points */
		if ( !$obj->hidden() and ( !$first or !$item::$firstCommentRequired ) )
		{
			$obj->sendNotifications();
			$obj->author()->achievementAction( 'core', 'Review', $obj );
		}
		else if( $obj->hidden() === 1 )
		{
			$obj->sendUnapprovedNotification();
		}
		
		return $obj;
	}
	
	/**
	 * Do stuff after creating (abstracted as comments and reviews need to do different things)
	 *
	 * @return	void
	 */
	public function postCreate(): void
	{		
		$item = $this->item();
		if ( !$this->hidden() )
		{
			if ( isset( $item::$databaseColumnMap['last_review'] ) )
			{
				$lastReviewField = $item::$databaseColumnMap['last_review'];
				if ( is_array( $lastReviewField ) )
				{
					foreach ( $lastReviewField as $column )
					{
						$item->$column = time();
					}
				}
				else
				{
					$item->$lastReviewField = time();
				}
			}
			if ( isset( $item::$databaseColumnMap['last_review_by'] ) )
			{
				$lastReviewByField = $item::$databaseColumnMap['last_review_by'];
				$item->$lastReviewByField = $this->author()->member_id;
			}
			if ( isset( $item::$databaseColumnMap['last_review_name'] ) )
			{
				$lastReviewNameField = $item::$databaseColumnMap['last_review_name'];
				$item->$lastReviewNameField = $this->author()->name;
			}
			if ( isset( $item::$databaseColumnMap['num_reviews'] ) )
			{
				$numReviewsField = $item::$databaseColumnMap['num_reviews'];
				$item->$numReviewsField++;
			}
			
			if ( !$item->hidden() and $item->containerWrapper() and $item->container()->_reviews !== NULL )
			{
				$item->container()->_reviews = ( $item->container()->_reviews + 1 );
				$item->container()->setLastReview( $this );
				$item->container()->save();
			}
		}
		else
		{
			if ( isset( $item::$databaseColumnMap['unapproved_reviews'] ) )
			{
				$numReviewsField = $item::$databaseColumnMap['unapproved_reviews'];
				$item->$numReviewsField++;
			}
			if ( $item->containerWrapper() AND $item->container()->_unapprovedReviews !== NULL )
			{
				$item->container()->_unapprovedReviews = $item->container()->_unapprovedReviews + 1;
				$item->container()->save();
			}
		}
		
		$item->save();
		
		/* Was it moderated? Let's see why. */
		if ( $this->hidden() === 1 )
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
						if ( $item->container() AND $item->container()->contentHeldForApprovalByNode( 'review', $this->author() ) === TRUE )
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
					IPS::classUsesTrait( $item, 'IPS\Content\MetaData' ) AND
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
	public static ?array $tabParameter	= array( 'tab' => 'reviews' );

	/**
	 * Get URL
	 *
	 * @param	string|null		$action		Action
	 * @return	Url
	 */
	public function url( string|null $action='find' ): Url
	{
		$idColumn = static::$databaseColumnId;

		return $this->item()->url()->setQueryString( array(
			'do'		=> $action . 'Review',
			'review'	=> $this->$idColumn
		) );
	}
	
	/**
	 * Get line which says how many users found review helpful
	 *
	 * @return	string
	 */
	public function helpfulLine(): string
	{
		return Theme::i()->getTemplate( 'global', 'core', 'front' )->reviewHelpful( $this->mapped('votes_helpful'), $this->mapped('votes_total') );
	}
	
	/**
	 * Edit and existing rating
	 *
	 * @param	int		$rating		The new rating
	 * @return void
	 */
	public function editRating( int $rating ): void
	{
		/* @var array $databaseColumnMap */

		/* Review */
		$ratingField = static::$databaseColumnMap['rating'];
		$this->$ratingField = $rating;
		$this->save();
		
		/* Item */
		$item = $this->item();
		$ratingField = $item::$databaseColumnMap['rating'];
		$item->$ratingField = $item->averageReviewRating() ?: 0; 

		$item->resyncLastReview();
		$item->save();
	}
	
	/**
	 * Can split this comment off?
	 *
	 * @param	Member|null	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canSplit( Member|null $member=null ): bool
	{
		return FALSE;
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

		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) and $this->hidden() and !$this->item()->canViewHiddenReviews( $member ) and ( $this->hidden() !== 1 or $this->author() !== $member ) )
		{
			return FALSE;
		}

		return $this->item()->canView( $member );
	}
	
	/**
	 * Warning Reference Key
	 *
	 * @return	string
	 */
	public function warningRef(): string
	{
		/* If the member cannot warn, return NULL, so we're not adding ugly parameters to the profile URL unnecessarily */
		if ( !Member::loggedIn()->modPermission('mod_can_warn') )
		{
			return '';
		}
		
		$itemClass = static::$itemClass;
		$idColumn = static::$databaseColumnId;
		return base64_encode( json_encode( array( 'app' => $itemClass::$application, 'module' => $itemClass::$module . '-review' , 'id_1' => $this->mapped('item'), 'id_2' => $this->$idColumn ) ) );
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
		return array( $this->item()->$idColumn, $this->$commentIdColumn, 'review' ); 
	}
	
	/**
	 * Create Notification
	 *
	 * @param	string|NULL		$extra		Additional data
	 * @return	Notification
	 */
	protected function createNotification( string|null $extra=NULL ): Notification
	{
		return new Notification( Application::load( 'core' ), 'new_review', $this->item(), array( $this ) );
	}
	
	/**
	 * Delete Review
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();
		$this->_recalculateRating();
	}

	/**
	 * Recalculate rating after review action
	 *
	 * @return void
	 */
	protected function _recalculateRating(): void
	{
		$itemClass = static::$itemClass;
		/* @var array $databaseColumnMap */
		$ratingField = $itemClass::$databaseColumnMap['rating'];
		$this->item()->$ratingField = $this->item()->averageReviewRating() ?: 0;
		$this->item()->save();
	}

	/**
	 * Get output for API
	 *
	 * @param Member|null  $authorizedMember The member making the API request or NULL for API Key / client_credentials
	 * @return    array
	 * @apiresponse		int			id				ID number
	 * @apiresponse		int			item			The ID number of the item this belongs to
	 * @apiresponse		\IPS\Member	author			Author
	 * @apiresponse		datetime	date			Date
	 * @apiresponse		int			rating			The number of stars this review gave
	 * @apiresponse		int			votesTotal		The number of users that have voted if this review was helpful or unhelpful
	 * @apiresponse		int			votesHelpful	The number of users that voted helpful
	 * @apiresponse		string		content			The content
	 * @apiresponse		bool		hidden			Is hidden?
	 * @apiresponse		string		url				URL to content
	 * @apiresponse		string|null	authorResponse	The content item's author's response to the review, if any
	 */
	public function apiOutput( Member|null $authorizedMember = NULL ): array
	{
		$idColumn = static::$databaseColumnId;
		$itemColumn = static::$databaseColumnMap['item'];
		return array(
			'id'				=> $this->$idColumn,
			'item_id'			=> $this->$itemColumn,
			'author'			=> $this->author()->apiOutput( $authorizedMember ),
			'date'				=> DateTime::ts( $this->mapped('date') )->rfc3339(),
			'rating'			=> $this->mapped('rating'),
			'votesTotal'		=> $this->mapped('votes_total'),
			'votesHelpful'		=> $this->mapped('votes_helpful'),
			'content'			=> $this->content(),
			'hidden'			=> (bool) $this->hidden(),
			'url'				=> (string) $this->url(),
			'authorResponse'	=> $this->mapped('author_response')
		);
	}

	/* !Author responses */

	/**
	 * Has the author responded to this review?
	 *
	 * @return bool
	 */
	public function hasAuthorResponse(): bool
	{
		return !is_null( $this->mapped('author_response') );
	}

	/**
	 * Can the specified user respond to the review?
	 *
	 * @note	Only the author of the content item can respond by default, but this is abstracted so third parties can override
	 * @param	Member|NULL	$member	Member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function canRespond( Member|null $member=NULL ): bool
	{
		/* Make sure it's supported */
		if( !isset( static::$databaseColumnMap['author_response'] ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();

		/* If we have not responded... */
		if( !$this->hasAuthorResponse() )
		{
			/* ...and we are the author of the content item... */
			if( $member->member_id and $member->member_id == $this->item()->author()->member_id )
			{
				/* ...then we can respond to this review. */
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Can the specified user edit the response to this review?
	 *
	 * @param	Member|null	$member	Member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function canEditResponse( Member|null $member=null ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* If there is an author response... */
		if( $this->hasAuthorResponse() )
		{
			$item = $this->item();

			/* Moderators who can edit content can edit responses */
			if ( static::modPermission( 'edit', $member, $item->containerWrapper() ) )
			{
				return TRUE;
			}

			/* Or maybe the member can edit their own content? */
			if ( $member->member_id and $member->member_id == $item->author()->member_id and ( $member->group['g_edit_posts'] == '1' or in_array( get_class( $item ), explode( ',', $member->group['g_edit_posts'] ) ) ) )
			{
				if ( IPS::classUsesTrait( $item, 'IPS\Content\Lockable' ) AND $item->locked() )
				{
					return FALSE;
				}
				
				return TRUE;
			}
		}

		/* Nope, we cannot edit */
		return FALSE;
	}

	/**
	 * Can the specified user delete the response to this review?
	 *
	 * @param	Member|NULL	$member	Member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function canDeleteResponse( Member|null $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* If there is an author response... */
		if( $this->hasAuthorResponse() )
		{
			$container = NULL;

			try
			{
				$container = $this->item()->container();
			}
			catch ( BadMethodCallException $e ) { }

			/* Moderators who can delete content can delete responses */
			if( static::modPermission( 'delete', $member, $container ) )
			{
				return TRUE;
			}

			/* Or maybe the author can delete their own content? */
			if( $member->member_id and $member->member_id == $this->item()->author()->member_id and ( $member->group['g_delete_own_posts'] == '1' or in_array( get_class( $this->item() ), explode( ',', $member->group['g_delete_own_posts'] ) ) ) )
			{
				return TRUE;
			}
		}

		/* Nope, we cannot delete */
		return FALSE;
	}

	/**
	 * Respond to a review
	 *
	 * @param string $response
	 * @return void
	 */
	public function setResponse( string $response ) : void
	{
		$this->checkProfanityFilters( false, $this->hasAuthorResponse(), $response );
		$column = static::$databaseColumnMap['author_response'];
		$this->$column = $response;
		$this->save();

		/* Reindex in case it was hidden by the profanity filters */
		if( SearchContent::isSearchable( $this ) )
		{
			Index::i()->index( $this );
		}
	}

	/**
	 * Get the feed id that should contain reviews
	 *
	 * @return string
	 */
	public function get_feedId() : string
	{
		return $this->item()->reviewFeedId;
	}
}