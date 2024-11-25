<?php
/**
 * @brief		Abstract Content Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 Oct 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\Content\Assignable;
use IPS\Content\Comment;
use IPS\Content\Followable;
use IPS\Content\Hideable;
use IPS\Content\Item;
use IPS\Content\Review;
use IPS\core\Approval;
use IPS\core\Reports\Report;
use IPS\Db\Select;
use IPS\Events\Event;
use IPS\Http\Url;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Output\UI\UiExtension;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Session\Store;
use IPS\Text\Parser;
use IPS\Xml\DOMDocument;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use UnexpectedValueException;
use function class_implements;
use function count;
use function defined;
use function func_get_args;
use function get_called_class;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_int;
use function substr;
use function ucfirst;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Content Model
 * We need PHPStorm to understand that trait methods are available so code completion and analyzing works correctly. PHPStorm can not
 * figure it out because we have these application classes which extend Content and use the traits, so it'll understand from the perspective
 * of \IPS\forums\Topic as it has the trait 'use'd but cannot understand from the perspective of \IPS\Content\Item because those traits are not
 * 'use'd directly. If this really bothers you, toggle the V next to the start of the doc block. :)
 *
 * If you need to update it, just run dev/generateContentTraitMethodTags.php
 *
 * @mixin \IPS\Content\Anonymous
 * @mixin \IPS\Content\Assignable
 * @mixin \IPS\Content\EditHistory
 * @mixin \IPS\Content\Followable
 * @mixin \IPS\Content\FuturePublishing
 * @mixin \IPS\Content\Hideable
 * @mixin \IPS\Content\ItemTopic
 * @mixin \IPS\Content\Lockable
 * @mixin \IPS\Content\MetaData
 * @mixin \IPS\Content\Featurable
 * @mixin \IPS\Content\Pinnable
 * @mixin \IPS\Content\Polls
 * @mixin \IPS\Content\Ratings
 * @mixin \IPS\Content\Reactable
 * @mixin \IPS\Content\ReadMarkers
 * @mixin \IPS\Content\Recognizable
 * @mixin \IPS\Content\Reportable
 * @mixin \IPS\Content\Shareable
 * @mixin \IPS\Content\Solvable
 * @mixin \IPS\Content\Statistics
 * @mixin \IPS\Content\Taggable
 */
abstract class Content extends ActiveRecord
{
	/**
	 * @brief	[Content\Comment]	Database Column Map
	 */
	public static array $databaseColumnMap = array();
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static string|null $hideLogKey = NULL;
	
	/**
	 * @brief	[Content\Comment]	Language prefix for forms
	 */
	public static string $formLangPrefix = '';

	/**
	 * @brief	Include In Sitemap
	 */
	public static bool $includeInSitemap = TRUE;
	
	/**
	 * @brief	Reputation Store
	 */
	protected array|null $reputation = null;
	
	/**
	 * @brief	Can this content be moderated normally from the front-end (will be FALSE for things like Pages and Commerce Products)
	 */
	public static bool $canBeModeratedFromFrontend = TRUE;

	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	Model|NULL	$container	Container
	 * @return	bool
	 */
	public static function incrementPostCount( Model $container = NULL ): bool
	{
		return TRUE;
	}

	/**
	 * Post count for member
	 *
	 * @param Member $member								The member
	 * @param	bool		$includeNonPostCountIncreasing		If FALSE, will skip any posts which would not cause the user's post count to increase
	 * @param	bool		$includeHiddenAndPendingApproval	If FALSE, will skip any hidden posts, or posts pending approval
	 * @return	int
	 */
	public static function memberPostCount( Member $member, bool $includeNonPostCountIncreasing = FALSE, bool $includeHiddenAndPendingApproval = TRUE ): int
	{
		if ( !isset( static::$databaseColumnMap['author'] ) )
		{
			return 0;
		}
		
		if ( !$includeNonPostCountIncreasing and !static::incrementPostCount() )
		{
			return 0;
		}
		
		$where = [];
		$where[] = [ static::$databasePrefix . static::$databaseColumnMap['author'] . '=?', $member->member_id ];
		
		if ( !$includeHiddenAndPendingApproval )
		{
			if ( isset( static::$databaseColumnMap['hidden'] ) )
			{
				$where[] = [ static::$databasePrefix . static::$databaseColumnMap['hidden'] . '=0' ];
			}
			if ( isset( static::$databaseColumnMap['approved'] ) )
			{
				$where[] = [ static::$databasePrefix . static::$databaseColumnMap['approved'] . '=1' ];
			}
		}
		
		return Db::i()->select( 'COUNT(*)', static::$databaseTable, $where )->first();
	}

	/**
	 * Post count for member
	 *
	 * @param	Member	$member	The member
	 * @return	int
	 *@deprecated	Use options provided to memberPostCount()
	 *
	 */
	public static function rawMemberPostCount( Member $member ): int
	{
		return static::memberPostCount( $member, TRUE );
	}
	
	/**
	 * Members with most contributions
	 *
	 * @param	int	$count	The number of results to return
	 * @return	array
	 */
	public static function mostContributions( int $count = 5 ): array
	{
		if( !isset( static::$databaseColumnMap['author'] ) )
		{
			return array( 'counts' => NULL, 'members' => NULL );
		}

		$where = array();
		if( isset( static::$databaseColumnMap['approved'] ) )
		{
			$approvedColumn = static::$databasePrefix . static::$databaseColumnMap['approved'];
			$where[] = array( "{$approvedColumn} = 1" );
		}
		if( isset( static::$databaseColumnMap['hidden'] ) )
		{
			$hiddenColumn = static::$databasePrefix . static::$databaseColumnMap['hidden'];
			$where[] = array( "{$hiddenColumn} = 0" );
		}

		$authorColumn = static::$databasePrefix . static::$databaseColumnMap['author'];
		$members = Db::i()->select( "count(*) as sum, {$authorColumn}", static::$databaseTable, $where, 'sum DESC', array( 0, $count ), array( static::$databasePrefix . static::$databaseColumnMap['author'] ) );

		$contributors = array();
		$counts = array();
		foreach ( $members as $member )
		{
			$contributors[] = $member[ $authorColumn ];
			$counts[ $member[ $authorColumn ] ] = $member[ 'sum' ];
		}

		if ( empty( $contributors ) )
		{
			return array( 'counts' => NULL, 'members' => NULL );
		}

		return array( 'counts' => $counts, 'members' => new ActiveRecordIterator( Db::i()->select( '*', 'core_members', array( Db::i()->in( 'member_id', $contributors ) ), "FIND_IN_SET( member_id, '" . implode( ",", $contributors) . "' )" ), 'IPS\Member' ) );
	}
		
	/**
	 * Load and check permissions
	 *
	 * @param	mixed				$id		ID
	 * @param	Member|NULL	$member	Member, or NULL for logged in member
	 * @return	static
	 * @throws	OutOfRangeException
	 */
	public static function loadAndCheckPerms( mixed $id, Member|null $member = NULL ): static
	{
		$obj = static::load( $id );
		
		$member = $member ?: Member::loggedIn();
		if ( !$obj->canView( $member ) )
		{
			throw new OutOfRangeException;
		}

		return $obj;
	}
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
	    if ( isset( $data[ static::$databaseTable ] ) and is_array( $data[ static::$databaseTable ] ) )
	    {
	        /* Add author data to multiton store to prevent ->author() running another query later */
	        if ( isset( $data['author'] ) and is_array( $data['author'] ) )
	        {
	           	$author = Member::constructFromData( $data['author'], FALSE );

	            if ( isset( $data['author_pfields'] ) )
	            {
		            unset( $data['author_pfields']['member_id'] );
					/* @var Member $author */
					$author->contentProfileFields( $data['author_pfields'] );
	            }
	        }

	        /* Load content */
	        $obj = parent::constructFromData( $data[ static::$databaseTable ], $updateMultitonStoreIfExists );

			/* Add reputation if it was passed*/
			if ( isset( $data['core_reputation_index'] ) and is_array( $data['core_reputation_index'] ) )
			{
				$obj->_data = array_merge( $obj->_data, $data['core_reputation_index'] );
			}
		}
		else
		{
			$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );
		}

        Event::fire( 'onLoad', $obj );

        /* Return */
        return $obj;
    }

    /**
     * @brief	Cached social groups
     */
    protected static array $_cachedSocialGroups = array();

	/**
	 * Get WHERE clause for Social Group considerations for getItemsWithPermission
	 *
	 * @param string $socialGroupColumn The column which contains the social group ID
	 * @param Member|null $member The member (NULL to use currently logged in member)
	 * @return    string
	 */
	public static function socialGroupGetItemsWithPermissionWhere( string $socialGroupColumn, Member|null $member = NULL ): string
	{			
		$socialGroups = array();
		
		$member = $member ?: Member::loggedIn();
		if ( $member->member_id )
		{
			if( !array_key_exists( $member->member_id, static::$_cachedSocialGroups ) )
			{
				static::$_cachedSocialGroups[ $member->member_id ] = iterator_to_array( Db::i()->select( 'group_id', 'core_sys_social_group_members', array( 'member_id=?', $member->member_id ) ) );
			}

			$socialGroups = static::$_cachedSocialGroups[ $member->member_id ];
		}

		if ( count( $socialGroups ) )
		{
			return $socialGroupColumn . '=0 OR ( ' . Db::i()->in( $socialGroupColumn, $socialGroups ) . ' )';
		}
		else
		{
			return $socialGroupColumn . '=0';
		}
	}

	/**
	 * Check the request for legacy parameters we may need to redirect to
	 *
	 * @return	NULL|Url
	 */
	public function checkForLegacyParameters(): Url|NULL
	{
		$paramsToSet	= array();
		$paramsToUnset	= array();

		/* st=20 needs to go to page=2 (or whatever the comments per page setting is set to) */
		if( isset( Request::i()->st ) )
		{
			$commentsPerPage = static::getCommentsPerPage();

			$paramsToSet['page']	= floor( intval( Request::i()->st ) / $commentsPerPage ) + 1;
			$paramsToUnset[]		= 'st';
		}

		/* Did we have any? */
		if( count( $paramsToSet ) )
		{
			$url = $this->url();

			if( count( $paramsToUnset ) )
			{
				$url = $url->stripQueryString( $paramsToUnset );
			}

			return $url->setQueryString( $paramsToSet );
		}

		return NULL;
	}

	/**
	 * Get mapped value
	 *
	 * @param string $key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( string $key ): mixed
	{
		if ( isset( static::$databaseColumnMap[ $key ] ) )
		{
			$field = static::$databaseColumnMap[ $key ];
			
			if ( is_array( $field ) )
			{
				$field = array_pop( $field );
			}
			
			return $this->$field;
		}
		return NULL;
	}
	
	/**
	 * Get author
	 *
	 * @return	Member
	 */
	public function author(): Member
	{
		if( IPS::classUsesTrait( $this, 'IPS\Content\Anonymous' ) AND $this->isAnonymous() )
		{
			$guest = new Member;
			$guest->name = Member::loggedIn()->language()->get( "post_anonymously_placename" );
			return $guest;
		}
		elseif ( $this->mapped('author') or !isset( static::$databaseColumnMap['author_name'] ) or !$this->mapped('author_name') )
		{
			return Member::load( $this->mapped('author') );
		} 
		else
		{
			$guest = new Member;
			$guest->name = $this->mapped('author_name');
			return $guest;
		}
	}

	/**
	 * Returns the content
	 *
	 * @return string|null
	 */
	public function content(): ?string
	{
		return $this->mapped('content');
	}

	/**
	 * Text for use with data-ipsTruncate
	 * Returns the post with paragraphs turned into line breaks
	 *
	 * @param	bool		$oneLine	If TRUE, will use spaces instead of line breaks. Useful if using a single line display.
	 * @param	int|null	$length		If supplied, and $oneLine is set to TRUE, the returned content will be truncated to this length
	 * @return	string
	 * @note	For now we are removing all HTML. If we decide to change this to remove specific tags in future, we can use \IPS\Text\Parser::removeElements( $this->content() )
	 */
	public function truncated( bool $oneLine=FALSE, int|null $length=500 ): string
	{
		$content = $this->content();
		if( $content === null )
		{
			return '';
		}

		return Parser::truncate( $content, $oneLine, $length );
	}
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		$idColumn = static::$databaseColumnId;
		
		if ( IPS::classUsesTrait( $this, 'IPS\Content\Reactable' ) )
		{
			Db::i()->delete( 'core_reputation_index', array( 'app=? AND type=? AND type_id=?', static::$application, $this->reactionType(), $this->$idColumn ) );
		}

		if ( IPS::classUsesTrait( $this, 'IPS\Content\Recognizable' ) )
		{
			Db::i()->delete( 'core_member_recognize', array( 'r_content_class=? AND r_content_id=?', get_class( $this ), $this->$idColumn ) );
		}

		if( IPS::classUsesTrait( $this, 'IPS\Content\Anonymous' ) )
		{
			Db::i()->delete( 'core_anonymous_posts', array( 'anonymous_object_class=? AND anonymous_object_id=?', get_class( $this ), $this->$idColumn ) );
		}

		/* Remove any entries in the promotions table */
		if( IPS::classUsesTrait( $this, 'IPS\Content\Featurable' ) )
		{
			Db::i()->delete( 'core_content_promote', array( 'promote_class=? AND promote_class_id=?', get_class( $this ), $this->$idColumn ) );
		}
		
		Db::i()->delete( 'core_deletion_log', array( "dellog_content_class=? AND dellog_content_id=?", get_class( $this ), $this->$idColumn ) );
		Db::i()->delete( 'core_solved_index', array( 'comment_class=? and comment_id=?', get_class( $this ), $this->$idColumn ) );

		if ( static::$hideLogKey )
		{
			$idColumn = static::$databaseColumnId;
			Db::i()->delete('core_soft_delete_log', array('sdl_obj_id=? AND sdl_obj_key=?', $this->$idColumn, static::$hideLogKey));
		}

		Webhook::fire( str_replace( '\\', '', substr( get_called_class(), 3 ) ) . '_delete', $this, $this->webhookFilters() );
		
		try
		{
			Approval::loadFromContent( get_called_class(), $this->$idColumn )->delete();
		}
		catch( OutOfRangeException $e ) { }

		parent::delete();

		$this->expireWidgetCaches();
		$this->adjustSessions();
	}

	/**
	 * Wrapper for the "hidden" method because we have
	 * way too many places that reference it
	 *
	 * @return int
	 */
	public function hidden() : int
	{
		if( IPS::classUsesTrait( $this, Hideable::class ) )
		{
			return $this->hiddenStatus();
		}

		return 0;
	}
	
	/**
	 * Can see moderation tools
	 *
	 * @note	This is used generally to control if the user has permission to see multi-mod tools. Individual content items may have specific permissions
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @param	Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function canSeeMultiModTools( Member|null $member = NULL, Model|null $container = NULL ): bool
	{
		return static::modPermission( 'pin', $member, $container ) or static::modPermission( 'unpin', $member, $container ) or static::modPermission( 'feature', $member, $container ) or static::modPermission( 'unfeature', $member, $container ) or static::modPermission( 'edit', $member, $container ) or static::modPermission( 'hide', $member, $container ) or static::modPermission( 'unhide', $member, $container ) or static::modPermission( 'delete', $member, $container ) or static::modPermission( 'move', $member, $container );
	}

	/**
	 * Return a list of groups that cannot see this item
	 *
	 * @return 	NULL|array
	 */
	public function cannotViewGroups(): array|NULL
	{
		$groups = array();
		foreach( Group::groups() as $group )
		{
			if ( $this instanceof Comment )
			{
				if ( ! $this->item()->can( 'view', $group ) )
				{
					$groups[] = $group->name;
				}
			}
			else
			{
				if ( ! $this->can( 'view', $group, FALSE ) )
				{
					$groups[] = $group->name;
				}
			}
		}

		return count( $groups ) ? $groups : NULL;
	}
	
	/**
	 * Check Moderator Permission
	 *
	 * @param	string						$type		'edit', 'hide', 'unhide', 'delete', etc.
	 * @param	Member|NULL			$member		The member to check for or NULL for the currently logged in member
	 * @param	Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function modPermission( string $type, ?Member $member = NULL, ?Model $container = NULL ): bool
	{
		/* Compatibility checks */
		if ( ( $type == 'hide' or $type == 'unhide' ) and !IPS::classUsesTrait( get_called_class(), 'IPS\Content\Hideable' ) )
		{
			return FALSE;
		}
		if ( ( $type == 'pin' or $type == 'unpin' ) and !IPS::classUsesTrait( get_called_class(), 'IPS\Content\Pinnable' ) )
		{
			return FALSE;
		}
		if ( ( $type == 'future_publish' ) and !IPS::classUsesTrait( get_called_class(), 'IPS\Content\FuturePublishing' ) )
		{
			return FALSE;
		}
		if( ( $type == 'assign' ) and ( !IPS::classUsesTrait( get_called_class(), Assignable::class ) or !Bridge::i()->featureIsEnabled( 'assignments' ) ) )
		{
			return false;
		}

		/* If this is called from a gateway script, i.e. email piping, just return false as we are a "guest" */
		if( $member === NULL AND !Dispatcher::hasInstance() )
		{
			return FALSE;
		}
		
		/* Load Member */
		$member = $member ?: Member::loggedIn();

		/* Global permission */
		if ( $member->modPermission( "can_{$type}_content" ) )
		{
			return TRUE;
		}
		/* Per-container permission */
		elseif ( $container )
		{
			return $container->modPermission( $type, $member, static::getContainerModPermissionClass() ?: get_called_class() );
		}
		
		/* Still here? return false */
		return FALSE;
	}

	/**
	 * Get the content to use for mod permission checks
	 *
	 * @return	string|NULL
	 * @note	By default we will return NULL and the container check will execute against Node::$contentItemClass, however
	 *	in some situations we may need to override this (i.e. for Gallery Albums)
	 */
	protected static function getContainerModPermissionClass(): ?string
	{
		return NULL;
	}

	/**
	 * @brief	Flag to skip rebuilding container data (because it will be rebuilt in one batch later)
	 */
	public bool $skipContainerRebuild = FALSE;

	/**
	 * Check if a specific action is available for this Content.
	 * Default to TRUE, but used for overrides in individual Item/Comment classes.
	 *
	 * @param string $action
	 * @param Member|null $member
	 * @return bool
	 */
	public function actionEnabled( string $action, ?Member $member=null ) : bool
	{
		return TRUE;
	}
		
	/**
	 * Do Moderator Action
	 *
	 * @param string $action	The action
	 * @param Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param string|null $reason	Reason (for hides)
	 * @param bool $immediately Delete Immediately
	 * @return	void
	 * @throws	OutOfRangeException|InvalidArgumentException|RuntimeException
	 */
	public function modAction( string $action, ?Member $member = NULL, mixed $reason = NULL, bool $immediately=FALSE ): void
	{
		if( $action === 'approve' )
		{
			$action	= 'unhide';
		}

		/* Check it's a valid action */
		if ( !in_array( $action, array( 'pin', 'unpin', 'feature', 'unfeature', 'hide', 'unhide', 'move', 'lock', 'unlock', 'delete', 'publish', 'restore', 'restoreAsHidden' ) ) )
		{
			throw new InvalidArgumentException;
		}
		
		/* And that we can do it */
		$toCheck = $action;
		if ( $action == 'restoreAsHidden' )
		{
			$toCheck = 'restore';
		}
		
		$methodName = 'can' . ucfirst( $toCheck );
		if ( !$this->$methodName( $member ) )
		{
			throw new OutOfRangeException;
		}
		
		/* Log */
		Session::i()->modLog( 'modlog__action_' . $action, array( static::$title => TRUE, $this->url()->__toString() => FALSE, $this->mapped('title') ?: ( method_exists( $this, 'item' ) ? $this->item()->mapped('title') : NULL ) => FALSE ), ( $this instanceof Item ) ? $this : $this->item() );

		$idColumn = static::$databaseColumnId;

		/* These ones just need a property setting */
		if ( in_array( $action, array( 'pin', 'unpin', 'feature', 'unfeature', 'lock', 'unlock' ) ) )
		{
			$val = TRUE;
			switch ( $action )
			{
				case 'unpin':
					$val = FALSE;
				case 'pin':
					$column = static::$databaseColumnMap['pinned'];
					break;
				
				case 'unfeature':
					$val = FALSE;
				case 'feature':
					$column = static::$databaseColumnMap['featured'];
					break;
				
				case 'unlock':
					$val = FALSE;
				case 'lock':
					if ( isset( static::$databaseColumnMap['locked'] ) )
					{
						$column = static::$databaseColumnMap['locked'];
					}
					else
					{
						$val = $val ? 'closed' : 'open';
						$column = static::$databaseColumnMap['status'];
					}
					break;
			}
			$this->$column = $val;
			$this->save();

            /* Fire events for these here, the others will be fired at a later point */
            Event::fire( 'onStatusChange', $this, array( $action ) );
		}
		
		/* Hide is a tiny bit more complicated */
		elseif ( $action === 'hide' )
		{
			$this->hide( $member, $reason );
		}
		elseif ( $action === 'unhide' )
		{
			$this->unhide( $member );
		}
		
		/* Delete is just a method */
		elseif ( $action === 'delete' )
		{
			/* If we are retaining content for a period of time, we need to just hide it instead for deleting later - this only works, though, with items that implement \IPS\Content\Hideable */
			if ( Settings::i()->dellog_retention_period AND IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) AND $immediately === FALSE )
			{
				$this->logDelete( $member );
				return;
			}
			
			$idColumn = static::$databaseColumnId;
			$this->delete();
		}
		
		/* Restore is just a method */
		elseif ( $action === 'restore' )
		{
			$this->restore();
		}
		
		/* Restore As Hidden is just a method */
		elseif ( $action === 'restoreAsHidden' )
		{
			$this->restore( TRUE );
		}

		/* Publish is just a method */
		elseif ( $action === 'publish' )
		{
			$this->publish();
		}

		/* Move is just a method */
		elseif ( $action === 'move' )
		{
			$args	= func_get_args();
			if ( $args[2] )
			{
				$this->move( $args[2][0], $args[2][1] );
			}
		}
	}

	/**
	 * @brief	Have we already reported?
	 */
	protected bool|null $alreadyReported = NULL;
	
	/**
	 * Can report?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	TRUE|string			TRUE or a language string for why not
	 * @note	This requires a few queries, so don't run a check in every template
	 */
	public function canReport( Member|null $member=NULL ): bool|string
	{
		if( !$this->actionEnabled( 'report', $member ) )
		{
			return false;
		}

		$member = $member ?: Member::loggedIn();
		
		/* Is this type of comment reportabe? */
		if ( !( IPS::classUsesTrait( $this, 'IPS\Content\Reportable' ) ) )
		{
			return 'generic_error';
		}
		
		/* Can the member report content? */
		$classToCheck = ( $this instanceof Comment ) ? get_class( $this->item() ) : get_class( $this );

		if ( $member->group['g_can_report'] != '1' AND !in_array( $classToCheck, explode( ',', $member->group['g_can_report'] ) ) )
		{
			return 'no_module_permission';
		}
		
		/* Can they view this? */
		if ( !$this->canView() )
		{
			return 'no_module_permission';
		}

		/* Have they already subitted a report? */
		if( $this->alreadyReported === TRUE )
		{
			return 'report_err_already_reported';
		}
		elseif( $this->alreadyReported === NULL )
		{
			/* Have we already prefetched it? */
			if ( ! isset( $this->reportData ) )
			{
				try
				{
					$idColumn = static::$databaseColumnId;
					$report = Db::i()->select( 'id', 'core_rc_index', array( 'class=? AND content_id=?', get_called_class(), $this->$idColumn ) )->first();
					$this->reportData = Db::i()->select( '*', 'core_rc_reports', array( 'rid=? AND report_by=?', $report, $member->member_id ) )->first();
				}
				catch( UnderflowException $e ){}
			}
			
			/* Check again */
			if ( isset( $this->reportData['date_reported'] ) )
			{
				if ( Settings::i()->automoderation_report_again_mins )
				{
					if ( ( ( time() - $this->reportData['date_reported'] ) / 60 ) > Settings::i()->automoderation_report_again_mins )
					{
						return TRUE;
					}
				}
				
				$this->alreadyReported = TRUE;
				return 'report_err_already_reported';
			}
			
			$this->alreadyReported = FALSE;
		}
		
		return TRUE;
	}

	/**
	 * Can report or revoke report?
	 * This method will return TRUE if the link to report content should be shown (which can occur even if you have already reported if you have permission to revoke your report)
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 * @note	This requires a few queries, so don't run a check in every template
	 */
	public function canReportOrRevoke( Member|null $member=NULL ): bool
	{
		/* If we are allowed to report, then we can return TRUE. */
		if( $this->canReport( $member ) === TRUE )
		{
			return TRUE;
		}
		/* If we have already reported but automatic moderation is enabled, show the link so the user can revoke their report. */
		elseif( $this->alreadyReported === TRUE AND Settings::i()->automoderation_enabled )
		{
			return TRUE;
		}

		return FALSE;
	}
	
	/**
	 * Report
	 *
	 * @param	string					$reportContent	Report content message from member
	 * @param	int						$reportType		Report type (see constants in \IPS\core\Reports\Report)
	 * @param	Member|null				$member			Member making the report (or NULL for loggedIn())
	 * @param array $guestDetails Details for a guest report
	 * @return	Report
	 * @throws	UnexpectedValueException	If there is a permission error - you should only call this method after checking canReport
	 */
	public function report( string $reportContent, int $reportType=1, Member|null $member=null, array $guestDetails=array() ): Report
	{
		$member = ( $member ) ?: Member::loggedIn();

		/* Permission check */
		$permCheck = $this->canReport( $member );
		if ( $permCheck !== TRUE )
		{
			throw new UnexpectedValueException( $permCheck );
		}

		/* Find or create an index */
		$idColumn = static::$databaseColumnId;
		$item = ( $this instanceof Comment ) ? $this->item() : $this;
		$itemIdColumn = $item::$databaseColumnId;

		try
		{
			$index = Report::load( $this->$idColumn, 'content_id', array( 'class=?', get_called_class() ) );
			$index->num_reports = $index->num_reports + 1;
		}
		catch ( OutOfRangeException $e )
		{
			$index = new Report;
			$index->class = get_called_class();
			$index->content_id = $this->$idColumn;
			$index->perm_id = $this->permId();
			$index->first_report_by = (int) $member->member_id;
			$index->first_report_date = time();
			$index->last_updated = time();
			$index->author = (int) $this->author()->member_id;
			$index->num_reports = 1;
			$index->num_comments = 0;
			$index->auto_moderation_exempt = 0;
			$index->item_id = $item->$itemIdColumn;
			$index->node_id = $item->containerWrapper() ? $item->containerWrapper()->_id : 0;
		}

		/* Only set this to a new report if it is not already under review */
		if( $index->status != 2 )
		{
			$index->status = 1;
		}

		$index->save();

		/* Create a report */
		$reportInsert = array(
			'rid'			=> $index->id,
			'report'		=> $reportContent,
			'report_by'		=> (int) $member->member_id,
			'date_reported'	=> time(),
			'ip_address'	=> Request::i()->ipAddress(),
			'report_type'	=> ( ! $member->member_id and Settings::i()->automoderation_enabled ) ? 0 : $reportType
		);

		if ( count( $guestDetails ) and isset( $guestDetails['email'] ) )
		{
			/* Guest name can be optional */
			$reportInsert['guest_name'] = isset( $guestDetails['name'] ) ? $guestDetails['name'] : '';
			$reportInsert['guest_email'] = $guestDetails['email'];
		}

		$insertID = Db::i()->insert( 'core_rc_reports', $reportInsert );
		$reportInsert['id'] = $insertID;
		
		/* Run automatic moderation */
		$index->runAutomaticModeration();

		/* Send notification to mods */
		$moderators = array( 'm' => array(), 'g' => array() );
		foreach (Db::i()->select( '*', 'core_moderators' ) as $mod )
		{
			$canView = FALSE;
			if ( $mod['perms'] == '*' )
			{
				$canView = TRUE;
			}
			if ( $canView === FALSE )
			{
				$perms = json_decode( $mod['perms'], TRUE );

				if ( isset( $perms['can_view_reports'] ) AND $perms['can_view_reports'] === TRUE )
				{
					$canView = TRUE;
				}

				/* Got nodes? */
				if ( $canView === TRUE and $container = $item->containerWrapper() and isset( $container::$modPerm ) )
				{
					if ( isset( $perms[ $container::$modPerm ] ) and $perms[ $container::$modPerm ] != '*' and $perms[ $container::$modPerm ] != -1 )
					{
						if ( empty( $perms[ $container::$modPerm ] ) or ! in_array( $item->mapped('container'), $perms[ $container::$modPerm ] ) )
						{
							$canView = FALSE;
						}
					}
				}
			}
			if ( $canView === TRUE )
			{
				$moderators[ $mod['type'] ][] = $mod['id'];
			}
		}

		$notification = new Notification( Application::load('core'), 'report_center', $index, array( $index, $reportInsert, $this ) );
		foreach (Db::i()->select( '*', 'core_members', ( count( $moderators['m'] ) ? Db::i()->in( 'member_id', $moderators['m'] ) . ' OR ' : '' ) . Db::i()->in( 'member_group_id', $moderators['g'] ) . ' OR ' . Db::i()->findInSet( 'mgroup_others', $moderators['g'] ) ) as $mem )
		{
			$memberObj = Member::constructFromData( $mem );
			
			/* Members may have individual member level mod permissions, but are also in a group that has moderator permissions. In this case, the member level restrictions always win so we need to recheck those now that we have a member object. See \IPS\Member::modPermissions(). */
			/* @var Member $memberObj */
			$perms = $memberObj->modPermissions();
			$canView = $this->canView( $memberObj );
			if ( $canView === TRUE and $container = $item->containerWrapper() and isset( $container::$modPerm ) )
			{
				if ( isset( $perms[ $container::$modPerm ] ) and $perms[ $container::$modPerm ] != '*' and $perms[ $container::$modPerm ] != -1 )
				{
					if ( empty( $perms[ $container::$modPerm ] ) or ! in_array( $item->mapped('container'), $perms[ $container::$modPerm ] ) )
					{
						$canView = FALSE;
					}
				}
			}
			
			if( $canView === TRUE )
			{
				$notification->recipients->attach( $memberObj );
			}
		}
		$notification->send();
		
		/* Set flag so future calls to report methods return correct value */
		$this->alreadyReported = TRUE;
		$this->reportData = $reportInsert;

		/* Return */
		return $index;
	}

	/**
	 * Change IP Address
	 * @param string $ip		The new IP address
	 *
	 * @return void
	 */
	public function changeIpAddress( string $ip ): void
	{
		if ( isset( static::$databaseColumnMap['ip_address'] ) )
		{
			$col = static::$databaseColumnMap['ip_address'];
			$this->$col = $ip;
			$this->save();
		}
	}
	
	/**
	 * Change Author
	 *
	 * @param	Member $newAuthor	The new author
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

		foreach ( array( 'author', 'author_name', 'edit_member_name', 'is_anon' ) as $k )
		{
			if ( isset( static::$databaseColumnMap[ $k ] ) )
			{
				$col = static::$databaseColumnMap[ $k ];
				switch ( $k )
				{
					case 'author':
						$this->$col = $newAuthor->member_id ? $newAuthor->member_id : 0;
						break;
					case 'author_name':
						$this->$col = $newAuthor->member_id ? $newAuthor->name : $newAuthor->real_name;
						break;
					case 'edit_member_name':
						/* Real name will contain the custom guest name if available or '' if not.
						   But we only want to update the user if oldName is the same as the current edit_member_name
						   So if "Bob" edited his own post, you want that to change if Bob becomes Bob2.
						   But if a moderator edited it, we don't want to change that name. */
						if ( $oldAuthor->name == $this->$col )
						{
							$this->$col = $newAuthor->member_id ? $newAuthor->name : $newAuthor->real_name;
						}
						break;
					case 'is_anon':
						/* We are specifying an author so turn off is_anon column */
						if ( $newAuthor->member_id )
						{
							$this->$col = 0;
						}
						break;
				}
			}
		}

		$this->save();

		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation == 'front' and $log )
		{
			Session::i()->modLog( 'modlog__action_changeauthor', array( static::$title => TRUE, $this->url()->__toString() => FALSE, $this->mapped('title') ?: ( method_exists( $this, 'item' ) ? $this->item()->mapped('title') : NULL ) => FALSE ), ( $this instanceof Item ) ? $this : $this->item() );
		}
	}

	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public static function getTableFilters(): array
	{
		$return = array();
		
		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Hideable' ) )
		{
			$return[] = 'hidden';
			$return[] = 'unhidden';
			$return[] = 'unapproved';
		}
				
		return $return;
	}
	
	/**
	 * Get content table states
	 *
	 * @return string
	 */
	public function tableStates(): string
	{
		$return	= array();

		if ( IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) )
		{
			switch ( $this->hidden() )
			{
				case -1:
					$return[] = 'hidden';
					break;
				case 0:
					$return[] = 'unhidden';
					break;
				case 1:
					$return[] = 'unapproved';
					break;
			}
		}
		
		return implode( ' ', $return );
		
	}
	
	/**
	 * Prune IP addresses from content
	 *
	 * @param	int		$days 		Remove from content posted older than DAYS ago
	 * @return	void
	 */
	public static function pruneIpAddresses( int $days=0 ): void
	{
		if ( $days and isset( static::$databaseColumnMap['ip_address'] ) and isset( static::$databaseColumnMap['date'] ) )
		{
			$time = time() - ( 86400 * $days );
			Db::i()->update( static::$databaseTable, array( static::$databasePrefix . static::$databaseColumnMap['ip_address'] => '' ), array( static::$databasePrefix . static::$databaseColumnMap['ip_address'] . "!='' AND " . static::$databasePrefix . static::$databaseColumnMap['date'] . ' <= ' . $time ) );
		}
	}
	
	/**
	 * Get content for an email
	 *
	 * @param Email $email			The email
	 * @param	string		$type			'html' or 'plaintext'
	 * @param	bool		$includeLinks	Whether or not to include links
	 * @param	bool		$includeAuthor	Whether or not to include the author
	 * @return	string
	 */
	public function emailContent( Email $email, string $type, bool $includeLinks=TRUE, bool $includeAuthor=TRUE ): string
	{
		return Email::template( 'core', '_genericContent', $type, array( $this, $includeLinks, $includeAuthor, $email ) );
	}
	
	/**
	 * Get a count of the database table
	 *
	 * @param   bool    $approximate     Accept an approximate result if the table is large (approximate results are faster on large tables)
	 * @return  int
	 */
	public static function databaseTableCount( bool $approximate=FALSE ): int
	{
		$key = 'tbl_cnt_' . ( $approximate ? 'approx_' : 'accurate_' ) . static::$databaseTable;
		$fetchAgain = FALSE;
		
		if ( ! isset( Data\Store::i()->$key ) )
		{
			$fetchAgain = TRUE;
		}
		else
		{
			/* Just check daily */
			$data = Data\Store::i()->$key;
			
			if ( $data['time'] < time() - 86400 )
			{
				$fetchAgain = TRUE;
			}
		}

		if ( $fetchAgain )
		{
			$count = 0;
			/* Accept approximate result? */
			if ( $approximate )
			{
				$approxRows = Db::i()->query( "SHOW TABLE STATUS LIKE '" . Db::i()->prefix . static::$databaseTable . "';" )->fetch_assoc();
				$count = (int) $approxRows['Rows'];
			}

			/* If the table is a reasonable size, we'll get the real value instead */
			if ( $count < 1000000 )
			{
				$count = Db::i()->select( 'COUNT(*)', static::$databaseTable )->first();
			}
			Data\Store::i()->$key = array( 'time' => time(), 'count' => $count );
		}

		$data = Data\Store::i()->$key;
		return $data['count'];
	}
	
	/* !Follow */

	/**
	 * @brief	Follow publicly
	 */
	const FOLLOW_PUBLIC = 1;

	/**
	 * @brief	Follow anonymously
	 */
	const FOLLOW_ANONYMOUS = 2;

	/**
	 * @brief	Number of notifications to process per batch
	 */
	const NOTIFICATIONS_PER_BATCH = NOTIFICATIONS_PER_BATCH;
	
	/**
	 * Send notifications
	 *
	 * @return	void
	 */
	public function sendNotifications(): void
	{		
		/* Send quote and mention notifications */
		$sentTo = $this->sendQuoteAndMentionNotifications();

		/* Stop here if the content does not use the "followable" trait */
		if( ( $this instanceof Item and !IPS::classUsesTrait( $this, Followable::class ) ) or ( $this instanceof Comment and !IPS::classUsesTrait( $this->item(), Followable::class ) ) )
		{
			return;
		}
		
		/* How many followers? */
		try
		{
			$count = $this->notificationRecipients( NULL, TRUE );
		}
		catch ( BadMethodCallException $e )
		{
			return;
		}
		
		/* Queue if there's lots, or just send them */
		if ( $count > NOTIFICATION_BACKGROUND_THRESHOLD)
		{
			$idColumn = $this::$databaseColumnId;
			Task::queue( 'core', 'Follow', array( 'class' => get_class( $this ), 'item' => $this->$idColumn, 'sentTo' => $sentTo, 'followerCount' => $count ), 2 );
		}
		else
		{
			$this->sendNotificationsBatch( 0, $sentTo );
		}
	}
	
	/**
	 * Send notifications batch
	 *
	 * @param	int				$offset		Current offset
	 * @param	array			$sentTo		Members who have already received a notification and how - e.g. array( 1 => array( 'inline', 'email' )
	 * @param	string|NULL		$extra		Additional data
	 * @return	int|null		New offset or NULL if complete
	 */
	public function sendNotificationsBatch( int $offset=0, array &$sentTo=array(), string|null $extra=NULL ): int|NULL
	{
		if( !IPS::classUsesTrait( $this, Followable::class ) )
		{
			return null;
		}

		/* Check authors spam status */
		if( $this->author()->members_bitoptions['bw_is_spammer'] )
		{
			/* Author is flagged as spammer, don't send notifications */
			return NULL;
		}

		$followIds = array();
		$followers = $this->notificationRecipients( array( $offset, static::NOTIFICATIONS_PER_BATCH ) );
		
		/* If $followers is NULL (which can be the case if the follows are just people following the author), just return as there is nothing to do */
		if ( $followers === NULL )
		{
			return NULL;
		}
		
		/* If we're still here, our iterator may not necessarily be one that implements Countable so we need to convert it to an array. */
		$followers = iterator_to_array( $followers );
		
		if( !count( $followers ) )
		{
			return NULL;
		}

		/* Send notification */
		$notification = $this->createNotification( $extra );
		$notification->unsubscribeType = 'follow';
		foreach ( $followers as $follower )
		{
			$member = Member::load( $follower['follow_member_id'] );
			if ( $member !== $this->author() and $this->canView( $member ) )
			{
				$followIds[] = $follower['follow_id'];
				$notification->recipients->attach( $member, $follower );
			}
		}

		/* Log that we sent it */
		if( count( $followIds ) )
		{
			Db::i()->update( 'core_follow', array( 'follow_notify_sent' => time() ), Db::i()->in( 'follow_id', $followIds ) );
		}

		$sentTo = $notification->send( $sentTo );
		
		/* Update the queue */
		return $offset + static::NOTIFICATIONS_PER_BATCH;
	}
	
	/**
	 * Send the notifications after the content has been edited (for any new quotes or mentiones)
	 *
	 * @param	string	$oldContent	The content before the edit
	 * @return	void
	 */
	public function sendAfterEditNotifications( string $oldContent ): void
	{				
		$existingData = static::_getQuoteAndMentionIdsFromContent( $oldContent );
		$this->sendQuoteAndMentionNotifications( array_unique( array_merge( $existingData['quotes'], $existingData['mentions'], $existingData['embeds'] ) ) );
	}
		
	/**
	 * Send quote and mention notifications
	 *
	 * @param	array	$exclude		An array of member IDs *not* to send notifications to
	 * @return	array	The members that were notified and how they were notified
	 */
	protected function sendQuoteAndMentionNotifications( array $exclude=array() ): array
	{
		return $this->_sendQuoteAndMentionNotifications( static::_getQuoteAndMentionIdsFromContent( $this->content() ), $exclude );
	}
	
	/**
	 * Send quote and mention notifications from data
	 *
	 * @param	array	$data		array( 'quotes' => array( ... member IDs ... ), 'mentions' => array( ... member IDs ... ), 'embeds' => array( ... member IDs ... ) )
	 * @param	array	$exclude	An array of member IDs *not* to send notifications to
	 * @return	array	The members that were notified and how they were notified
	 */
	protected function _sendQuoteAndMentionNotifications( array $data, array $exclude=array() ): array
	{
		/* Init */
		$sentTo = array();
		
		/* Quotes */
		$data['quotes'] = array_filter( $data['quotes'], function( $v ) use ( $exclude )
		{
			return !in_array( $v, $exclude );
		} );
		if ( !empty( $data['quotes'] ) )
		{
			$notification = new Notification( Application::load( 'core' ), 'quote', $this, array( $this ), array( $this->author()->member_id ) );
			foreach ( $data['quotes'] as $quote )
			{
				$member = Member::load( $quote );
				if ( $member->member_id and $member !== $this->author() and $this->canView( $member ) and !$member->isIgnoring( $this->author(), 'posts' ) )
				{
					$notification->recipients->attach( $member );
				}
			}
			$sentTo = $notification->send( $sentTo );
		}
		
		/* Mentions */
		$data['mentions'] = array_filter( $data['mentions'], function( $v ) use ( $exclude )
		{
			return !in_array( $v, $exclude );
		} );
		if ( !empty( $data['mentions'] ) )
		{
			$notification = new Notification( Application::load( 'core' ), 'mention', $this, array( $this ), array( $this->author()->member_id ) );
			foreach ( $data['mentions'] as $mention )
			{
				$member = Member::load( $mention );
				if ( $member->member_id AND $member !== $this->author() and $this->canView( $member ) and !$member->isIgnoring( $this->author(), 'mentions' ) )
				{
					$notification->recipients->attach( $member );
				}
			}
			$sentTo = $notification->send( $sentTo );
		}

		/* Embeds */
		$data['embeds'] = array_filter( $data['embeds'], function( $v ) use ( $exclude )
		{
			return !in_array( $v, $exclude );
		} );
		if ( !empty( $data['embeds'] ) )
		{
			$notification = new Notification( Application::load( 'core' ), 'embed', $this, array( $this ), array( $this->author()->member_id ) );
			foreach ( $data['embeds'] as $embed )
			{
				$member = Member::load( $embed );
				if ( $member->member_id AND $member !== $this->author() and $this->canView( $member ) and !$member->isIgnoring( $this->author(), 'posts' ) )
				{
					$notification->recipients->attach( $member );
				}
			}
			$sentTo = $notification->send( $sentTo );
		}
	
		/* Return */
		return $sentTo;
	}
	
	/**
	 * Get quote and mention notifications
	 *
	 * @param	string|null	$content	The content
	 * @return	array	array( 'quotes' => array( ... member IDs ... ), 'mentions' => array( ... member IDs ... ), 'embeds' => array( ... member IDs ... )  )
	 */
	protected static function _getQuoteAndMentionIdsFromContent( ?string $content ): array
	{
		$return = array( 'quotes' => array(), 'mentions' => array(), 'embeds' => array() );

		/* If we have no content, don't bother with this */
		if( empty( $content ) )
		{
			return $return;
		}
		
		$document = new DOMDocument( '1.0', 'UTF-8' );
		if ( @$document->loadHTML( DOMDocument::wrapHtml( '<div>' . $content . '</div>' ) ) !== FALSE )
		{
			/* Quotes */
			foreach( $document->getElementsByTagName('blockquote') as $quote )
			{
				if ( $quote->getAttribute('data-ipsquote-userid') and (int) $quote->getAttribute('data-ipsquote-userid') > 0 )
				{
					$return['quotes'][] = $quote->getAttribute('data-ipsquote-userid');
				}
			}
			
			/* Mentions */
			foreach( $document->getElementsByTagName('a') as $link )
			{
				if ( $link->getAttribute('data-mentionid') )
				{					
					if ( !preg_match( '/\/blockquote(\[\d*])?\//', $link->getNodePath() ) )
					{
						$return['mentions'][] = $link->getAttribute('data-mentionid');
					}
				}
			}

			/* Embeds */
			foreach( $document->getElementsByTagName('iframe') as $embed )
			{
				if ( $embed->getAttribute('data-embedauthorid') )
				{
					if ( $embed->getAttribute('data-embedauthorid') and (int) $embed->getAttribute('data-embedauthorid') > 0 and !preg_match( '/\/blockquote(\[\d*])?\//', $embed->getNodePath() ) )
					{
						$return['embeds'][] = $embed->getAttribute('data-embedauthorid');
					}
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * Expire appropriate widget caches automatically
	 *
	 * @return void
	 */
	public function expireWidgetCaches(): void
	{
		Widget::deleteCaches( NULL, static::$application );
	}

	/**
	 * Update "currently viewing" session data after moderator actions that invalidate that data for other users
	 *
	 * @return void
	 */
	public function adjustSessions(): void
	{
		if( $this instanceof Comment )
		{
			try
			{
				$item = $this->item();
			}
			catch( OutOfRangeException $e )
			{
				return;
			}
		}
		else
		{
			$item = $this;
		}

		/** Item::url() can throw a LogicException exception in specific cases like when a Pages Record has no valid page */
		try
		{
			/* We have to send a limit even though we want all records because otherwise the Database store does not return all columns */
			foreach( Store::i()->getOnlineUsers( 0, 'desc', array( 0, 5000 ), NULL, TRUE ) as $session )
			{
				if( mb_strpos( $session['location_url'], (string) $item->url() ) === 0 )
				{
					$sessionData = $session;
					$sessionData['location_url']			= NULL;
					$sessionData['location_lang']			= NULL;
					$sessionData['location_data']			= json_encode( array() );
					$sessionData['current_id']				= 0;
					$sessionData['location_permissions']	= 0;

					Store::i()->updateSession( $sessionData );
				}
			}
		}
		catch( LogicException $e ){}


	}

	/**
	 * Fetch classes from content router
	 *
	 * @param	bool|Member $member		Check member access
	 * @param	bool				$archived	Include any supported archive classes
	 * @param	bool				$onlyItems	Only include item classes
	 * @return	array
	 */
	public static function routedClasses( bool|Member $member=FALSE, bool $archived=FALSE, bool $onlyItems=FALSE ): array
	{
		$classes	= array();

		foreach (Application::allExtensions( 'core', 'ContentRouter', $member, NULL, NULL, TRUE ) as $router )
		{
			foreach ( $router->classes as $class )
			{
				$classes[]	= $class;

				if( $onlyItems )
				{
					continue;
				}
				
				if ( !( $member instanceof Member) )
				{
					$member = $member ? Member::loggedIn() : NULL;
				}
				
				if ( isset( $class::$commentClass ) and $class::supportsComments( $member ) )
				{
					$classes[]	= $class::$commentClass;
				}

				if ( isset( $class::$reviewClass ) and $class::supportsReviews( $member ) )
				{
					$classes[]	= $class::$reviewClass;
				}

				if( $archived === TRUE AND isset( $class::$archiveClass ) )
				{
					$classes[]	= $class::$archiveClass;
				}
			}
		}

		return $classes;
	}

	/**
	 * Override the HTML parsing enabled flag for rebuilds?
	 *
	 * @note	By default this will return FALSE, but classes can override
	 * @see		\IPS\forums\Topic\Post
	 * @return	bool
	 */
	public function htmlParsingEnforced(): bool
	{
		return FALSE;
	}

	/**
	 * Return any custom multimod actions this content item supports
	 *
	 * @return	array
	 */
	public function customMultimodActions(): array
	{
		return array();
	}

	/**
	 * Return any available custom multimod actions this content item class supports
	 *
	 * @note	Return in format of EITHER
	 *	@li	array( array( 'action' => ..., 'icon' => ..., 'label' => ... ), ... )
	 *	@li	array( array( 'grouplabel' => ..., 'icon' => ..., 'groupaction' => ..., 'action' => array( array( 'action' => ..., 'label' => ... ), ... ) ) )
	 * @note	For an example, look at \IPS\core\Announcements\Announcement
	 * @return	array
	 */
	public static function availableCustomMultimodActions(): array
	{
		return array();
	}

	/**
	 * Indefinite Article
	 *
	 * @param Lang|null $lang The language to use, or NULL for the language of the currently logged in member
	 * @return    string
	 */
	public function indefiniteArticle( ?Lang $lang = NULL ): string
	{
		$container = ( $this instanceof Comment ) ? $this->item()->containerWrapper() : $this->containerWrapper();
		return static::_indefiniteArticle( $container ? $container->_data : array(), $lang );
	}

	/**
	 * Indefinite Article
	 *
	 * @param array|null $containerData Container data
	 * @param Lang|null $lang The language to use, or NULL for the language of the currently logged in member
	 * @return    string
	 */
	public static function _indefiniteArticle( ?array $containerData = NULL, ?Lang $lang = NULL ): string
	{
		$lang = $lang ?: Member::loggedIn()->language();
		return $lang->addToStack( '__indefart_' . static::$title, FALSE );
	}

	/**
	 * Definite Article
	 *
	 * @param Lang|null $lang The language to use, or NULL for the language of the currently logged in member
	 * @param integer|boolean $count Number of items. If not FALSE, pluralized version of phrase will be used
	 * @return    string
	 */
	public function definiteArticle( ?Lang $lang = NULL, int|bool $count = FALSE ): string
	{
		$container = ( $this instanceof Comment ) ? $this->item()->containerWrapper() : $this->containerWrapper();
		return static::_definiteArticle( $container ? $container->_data : array(), $lang, array(), $count );
	}

	/**
	 * Definite Article
	 *
	 * @param array|null $containerData Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param Lang|null $lang The language to use, or NULL for the language of the currently logged in member
	 * @param array $options Options to pass to \IPS\Lang::addToStack
	 * @param integer|boolean $count Number of items. If not false, pluralized version of phrase will be used.
	 * @return    string
	 */
	public static function _definiteArticle( ?array $containerData = NULL, ?Lang $lang = NULL, array $options = array(), int|bool $count = FALSE ): string
	{
		$lang = $lang ?: Member::loggedIn()->language();
		
		if( ( is_int( $count ) || $count === TRUE ) && $lang->checkKeyExists('__defart_' . static::$title . '_plural') )
		{
			/* If $count is TRUE, use the pluralized form but don't pluralize here - useful if we're passing to JS for example */
			if( is_int( $count ) )
			{
				$options['pluralize'] = array( $count );
			}

			return $lang->addToStack( '__defart_' . static::$title . '_plural', FALSE, $options );
		}

		return $lang->addToStack( '__defart_' . static::$title, FALSE, $options );
	}

	/**
	 * Get preview image for share services
	 *
	 * @return	string
	 */
	public function shareImage(): string
	{
		/* While we now allow multiple share logos now, this deprecated method can only return one */
		$shareLogos = Settings::i()->icons_sharer_logo ? json_decode( Settings::i()->icons_sharer_logo, true ) : array();

		if( count( $shareLogos ) )
		{
			try
			{
				$url = Url::createFromString( File::get( 'core_Icons', array_shift( $shareLogos ) ) );
				return (string) ( $url->setScheme( ( Request::i()->isSecure() ) ? 'https' : 'http' ) );
			}
			catch( Exception $e )
			{
				return '';
			}
		}

		return '';
	}

	/**
	 * Log keyword usage, if any
	 *
	 * @param	string		$content	Content/text of submission
	 * @param	string|NULL	$title		Title of submission
	 * @param	int|NULL			$date		Date of submission
	 * @return	void
	 */
	public function checkKeywords( string $content, string|null $title=null, ?int $date = null ): void
	{
		/* Do we have any keywords to track? */
		if( !Settings::i()->stats_keywords )
		{
			return;
		}

		/* We need to know the ID */
		$idColumn	= static::$databaseColumnId;

		/* If this is a content item and first comment is required, skip checking the comment */
		if ( $this instanceof Comment )
		{
			$itemClass = static::$itemClass;

			if( $itemClass::$firstCommentRequired === TRUE )
			{
				/* During initial post, at this point the firstCommentIdColumn value won't be set, so we check for that or explicitly if this is the first post */
				if( !$this->item()->mapped('first_comment_id') OR $this->$idColumn == $this->item()->mapped('first_comment_id') )
				{
					return;
				}
			}
		}

		$words = preg_split("/[\s]+/", trim( strip_tags( preg_replace( "/<br( \/)?>/", "\n", $content ) ) ), NULL, PREG_SPLIT_NO_EMPTY );

		if( $title !== NULL )
		{
			$titleWords = explode( ' ', $title );
			$words		= array_merge( $words, $titleWords );
		}

		$words = array_unique( $words );

		$keywords = json_decode( Settings::i()->stats_keywords, true );

		$extraData	= json_encode( array( 'class' => get_class( $this ), 'id' => $this->$idColumn ) );

		foreach( $keywords as $keyword )
		{
			if( in_array( $keyword, $words ) )
			{
				$date = $date ?: DateTime::create()->getTimestamp();
				Db::i()->insert( 'core_statistics', array( 'time' => $date, 'type' => 'keyword', 'value_4' => $keyword, 'extra_data' => $extraData ) );
			}
		}
	}

	/**
	 * Return size and downloads count when this content type is inserted as an attachment via the "Insert other media" button on an editor.
	 *
	 * @note Most content types do not support this, and those that do will need to override this method to return the appropriate info
	 * @return array
	 */
	public function getAttachmentInfo(): array
	{
		return array();
	}

	/**
	 * Create a query to fetch the "top members"
	 *
	 * @note	The intention is to formulate a query that will fetch the members with the most contributions
	 * @param	int		$limit	The number of members to return
	 * @return	Select
	 */
	public static function topMembersQuery( int $limit ): Select
	{
		$contentWhere = array( array( static::$databasePrefix . static::$databaseColumnMap['author'] . '<>?', 0 ) );
		if ( isset( static::$databaseColumnMap['hidden'] ) )
		{
			$contentWhere[] = array( static::$databasePrefix . static::$databaseColumnMap['hidden'] . '=0' );
		}
		else if ( isset( static::$databaseColumnMap['approved'] ) )
		{
			$contentWhere[] = array( static::$databasePrefix . static::$databaseColumnMap['approved'] . '=1' );
		}
		
		$authorField = static::$databasePrefix . static::$databaseColumnMap['author'];

		return Db::i()->select( 'COUNT(*) as count, ' . static::$databaseTable . '.' . $authorField, static::$databaseTable, $contentWhere, 'count DESC', $limit, $authorField );
	}
	
	/**
	 * Webhook filters
	 *
	 * @return	array
	 */
	public function webhookFilters(): array
	{
		$filters = array();
		$filters['author'] = $this->author()->member_id;

		/* All our Zapier Triggers for content have a hidden setting, so we're using this one global for items and comments */
		$filters['hidden'] = IPS::classUsesTrait( $this, 'IPS\Content\Hideable' ) && $this->hidden();

		return $filters;
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 * @throws BadMethodCallException
	 */
	public function embedContent( array $params ): string
	{
		if( !in_array( 'IPS\Content\Embeddable', class_implements( $this ) ) )
		{
			return "";
		}

		if ( $this instanceof Comment )
		{
			$item = $this->item();
			$itemCommentClass = $item::$commentClass;
			$idField = static::$databaseColumnId;

			if ( $this instanceof $itemCommentClass and $item::$firstCommentRequired and ( in_array( 'IPS\Content\Embeddable', class_implements( $item ) ) ) and $item->firstComment()->$idField == $this->$idField )
			{
				if ( @$params['do'] === 'findComment' or @$params['embedDo'] === 'findComment' )
				{
					unset( $params['do'] );
					unset( $params['embedDo'] );
				}
				unset( $params['comment'] );
				unset( $params['embedComment'] );

				return $item->embedContent( $params ); // recurse up to the parent
			}
		}

		$template = Theme::i()->getTemplate( 'global', 'core' );
		return match( true ) {
			$this instanceof Review		=> $template->embedReview( $this->item(), $this, $this->url()->setQueryString( $params ), $this->embedImage() ),
			$this instanceof Comment	=> $template->embedComment( $this->item(), $this, $this->url()->setQueryString( $params ), $this->embedImage() ),
			$this instanceof Item		=> $template->embedItem( $this, $this->url()->setQueryString( $params ), $this->embedImage() ),
			default						=> throw new BadMethodCallException( 'Using class must define embedContent method for non-content classes.' )
		};
	}

	/**
	 * Get image for embed
	 *
	 * @return	File|NULL
	 */
	public function embedImage(): ?File
	{
		if( !in_array( 'IPS\Content\Embeddable', class_implements( $this ) ) )
		{
			return NULL;
		}

		/* Comments/Reviews should use whatever we have in the item */
		if ( $this instanceof Comment )
		{
			return $this->item()->embedImage();
		}

		/* If we have a cover photo, use it */
		return $this->coverPhotoFile();
	}

	/**
	 * Run a UI extension method
	 * This is identical to UiExtension::run, but used to simplify templates
	 *
	 * @param string $method
	 * @param array|null $payload
     * @param bool  $returnAsArray
     * @param string $separator
	 * @return array|string
	 */
	public function ui( string $method, ?array $payload=array(), bool $returnAsArray=false, string $separator = " " ) : array|string
	{
		$response = UiExtension::i()->run( $this, $method, $payload );
        if( !$returnAsArray )
        {
            return implode( $separator, $response );
        }
        return $response;
	}

	/**
	 * Spam methods (not currently used)
	 */
	public function spamProtectionPayload( bool $isEdit, string $contentType, Content $content ): array
	{
		return Bridge::i()->spamProtectionPayload( $isEdit, $contentType, $content );
	}

	public function spamCheck( bool $isEdit, Content $content ): int
	{
		return Bridge::i()->spamCheck( $isEdit, $content );
	}

	public function canMarkAsSpam( $member, Content $content ): bool
	{
		return Bridge::i()->canMarkAsSpam( $member, $content );
	}

	public function canUnmarkAsSpam( $member, Content $content ): bool
	{
		return Bridge::i()->canUnmarkAsSpam( $member, $content );
	}
}