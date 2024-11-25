<?php
/**
 * @brief		Base class for Content Items
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Aug 2018
 */

namespace IPS\Content\Api\GraphQL;
use BadMethodCallException;
use DateTimeZone;
use Exception;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\IPS;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Settings;
use OutOfRangeException;
use function defined;
use function get_class;
use function in_array;
use function is_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base mutator class for Content Items
 */
class ItemType extends ObjectType
{
	/*
	 * @brief 	The item classname we use for this type
	 */
	protected static string $itemClass	= '\IPS\Content\Item';

	/*
	 * @brief 	GraphQL type name
	 */
	protected static string $typeName = 'core_Item';

	/*
	 * @brief 	GraphQL type description
	 */
	protected static string $typeDescription = 'A generic content item';


	public function __construct()
	{
		$config = [
			'name' => static::$typeName,
			'description' => static::$typeDescription,
			'fields' => function () {
				return $this->fields();
			}
		];

		parent::__construct($config);
	}

	/**
	 * Get the fields that this type supports
	 *
	 * @return	array
	 */
	public function fields(): array
	{
		return array(
			'id' => [
				'type' => TypeRegistry::id(),
				'resolve' => function ($item) {
					$idColumn = static::getIdColumn($item);
					return $item->$idColumn;
				}
			],
			'url' => [
				'type' => TypeRegistry::url(),
				'resolve' => function ($item) {
					return $item->url();
				}
			],
			'title' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($item) {
					return $item->mapped('title');
				}
			],
			'seoTitle' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($item) {
					return Friendly::seoTitle( $item->mapped('title') );
				}
			],
			'views' => [
				'type' => TypeRegistry::int(),
				'resolve' => function ($item) {
					if ( in_array( 'IPS\Content\ViewUpdates', class_uses( $item ) ) )
					{
						return $item->mapped('views');
					}

					return NULL;
				}
			],
			'commentCount' => [
				'type' => TypeRegistry::int(),
				'args' => [
					'includeHidden' => [
						'type' => TypeRegistry::boolean(),
						'description' => "Should the count include hidden/unapproved comments that the logged in member can see?",
						'defaultValue' => FALSE
					]
				],
				'resolve' => function ($item, $args) {
					if( $args['includeHidden'] && method_exists( $item, 'commentCount' )  ){
						return $item->commentCount();
					}

					return $item->mapped('num_comments');
				}
			],
			'isLocked' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($item) {
					if ( IPS::classUsesTrait( $item, 'IPS\Content\Lockable' ) )
					{
						return (bool) $item->locked();
					}

					return NULL;
				}
			],
			'isPinned' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($item) {
					if ( IPS::classUsesTrait( $item, 'IPS\Content\Pinnable' ) )
					{
						return (bool) $item->mapped('pinned');
					}

					return NULL;
				}
			],
			'isFeatured' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($item) {
					if ( IPS::classUsesTrait( $item, 'IPS\Content\Featurable' ) )
					{
						return (bool) $item->mapped('featured');
					}

					return NULL;
				}
			],
			'hiddenStatus' => [
				'type' => TypeRegistry::eNum([
					'name' => static::$typeName . '_hiddenStatus',
					'values' => ['HIDDEN', 'PENDING', 'DELETED']
				]),
				'resolve' => function ($item) {
					if ( !IPS::classUsesTrait( $item, 'IPS\Content\Hideable' ) )
					{
						switch( $item->hidden() ){
							case -2:
								return 'DELETED';
							case -1:
								return 'HIDDEN';
							case 1:
								return 'PENDING';
							default:
								return NULL;
						}
					}
					return null;
				}
			],
			'updated' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($item) {
					return $item->mapped('updated');
				}
			],
			'started' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($item) {
					return $item->mapped('date');
				}
			],
			'isUnread' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($item) {
					if ( IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) )
					{
						return $item->unread();
					}

					return NULL;
				}
			],
			'timeLastRead' => [
				'type' => TypeRegistry::int(),
				'resolve' => function ($item) {
					return static::timeLastRead($item);
				}
			],
			'unreadCommentPosition' => [
				'type' => TypeRegistry::int(),
				'description' => 'Returns the position of the comment that is the first unread in this topic',
				'resolve' => function ($item) {
					if ( IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) )
					{
						return static::getUnreadPosition($item);
					}

					return NULL;
				}
			],
			'findCommentPosition' => [
				'type' => TypeRegistry::int(),
				'args' => [
					'findComment' => TypeRegistry::int()
				],
				'description' => 'Returns the position of the comment provided in the required findComment arg',
				'resolve' => function ($item, $args) {
					return static::findCommentPosition($item, $args);
				}
			],
			'follow' => [
				'type' => TypeRegistry::follow(),
				'resolve' => function ($item) {
					if( IPS::classUsesTrait( $item, 'IPS\Content\Followable' ) && isset( static::$followData ) && is_array( static::$followData ) )
					{
						$idColumn = static::getIdColumn($item);
						return array_merge( static::$followData, array(
							'id' => $item->$idColumn,
							'item' => $item,
							'itemClass' => static::$itemClass
						));
					}

					return NULL;
				}
			],
			'tags' => [
				'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::tag() ),
				'resolve' => function ($item) {
					if ( IPS::classUsesTrait( $item, 'IPS\Content\Taggable' ) )
					{
						return static::tags($item);
					}

					return NULL;
				}
			],
			'author' => [
				'type' => \IPS\core\api\GraphQL\TypeRegistry::member(),
				'resolve' => function ($item, $args) {
					return static::author($item, $args);
				}
			],
			'container' => [
				// @todo return generic node
				'type' => \IPS\Node\Api\GraphQL\TypeRegistry::node(),
				'resolve' => function ($item) {
					return $item->container();
				}
			],
			'content' => [
				'type' => TypeRegistry::richText(),
				'resolve' => function ($item) {
					return $item->content();
				}
			],
			'contentImages' => [
				'type' => TypeRegistry::listOf( TypeRegistry::string() ),
				'resolve' => function ($item) {
					return static::contentImages($item);
				}
			],
			'hasPoll' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($item) {
					return IPS::classUsesTrait( $item, 'IPS\Content\Polls' ) AND $item->poll_state;
				}
			],
			'poll' => [
				'type' => \IPS\core\api\GraphQL\TypeRegistry::poll(),
				'resolve' => function ($item) {
					if( IPS::classUsesTrait( $item, 'IPS\Content\Polls' ) AND $item->poll_state )
					{
						return $item->getPoll();
					}

					return NULL;
				}
			],
			'firstCommentRequired' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($item) {
					/* @var Item $className */
					$className = get_class( $item );
					return $className::$firstCommentRequired;
				}
			],
			'articleLang' => [
				'type' => new ObjectType([
					'name' => static::$typeName . '_articleLang',
					'fields' => [
						'indefinite' => TypeRegistry::string(),
						'definite' => [
							'type' => TypeRegistry::string(),
							'args' => [
								'uppercase' => [
									'type' => TypeRegistry::boolean(),
									'defaultValue' => FALSE
								]
							]
						]
					],
					'resolveField' => function ($item, $args, $context, $info) {
						/* @var Item $className */
						$className = get_class( $item );

						switch( $info->fieldName )
						{
							case 'indefinite':
								return $className::_indefiniteArticle();

							case 'definite':
								return $className::_definiteArticle( NULL, NULL, $args['uppercase'] ? array( 'ucfirst' => TRUE ) : array() );	

						}
						return '';
					}
				]),
				'resolve' => function ($item) {
					return $item;
				}
			],
			'lastCommentAuthor' => [
				'type' => \IPS\core\api\GraphQL\TypeRegistry::member(),
				'resolve' => function ($item, $args) {
					return static::lastCommentAuthor($item, $args);
				}
			],
			'lastCommentDate' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($item, $args) {
					return static::lastCommentDate($item, $args);
				}
			],
			'comments' => [
				'type' => TypeRegistry::listOf( static::getCommentType() ),
				'args' => static::getCommentType()::args(),
				'resolve' => function ($item, $args, $context) {
					return static::comments($item, $args);
				}
			],
			'itemPermissions' => [
				'type' => new ObjectType([
					'name' => static::$typeName . '_itemPermissions',
					'fields' => static::getItemPermissionFields()
				]),
				'resolve' => function ($item) {
					return $item;
				}
			],
			'uploadPermissions' => [
				'type' => \IPS\core\api\GraphQL\TypeRegistry::attachmentPermissions(),
				'description' => 'Details about what the user can attach when commenting on this item.',
				'args' => [
					'postKey' => TypeRegistry::nonNull( TypeRegistry::string() ),
				],
				'resolve' => function( $node, $args, $context ) {
					return array( 'postKey' => $args['postKey'] );
				}
			],
			'reportStatus' => [
				'type' => \IPS\core\api\GraphQL\TypeRegistry::report(),
				'resolve' => function ($item) {
					return $item;
				}
			]
		);
	}

	/**
	 * Return the ID column for the provided item
	 *
	 * @param Item $item
	 * @return    string
	 */
	protected function getIdColumn(Item $item): string
	{
		$className = get_class( $item );
		return $className::$databaseColumnId;
	}

	/**
	 * Return the arguments that can be used to filter topics. Passed into NodeType.
	 *
	 * @return	array
	 */
	public static function args(): array
	{
		return array(
			'offset' => [
				'type' => TypeRegistry::int(),
				'defaultValue' => 0
			],
			'limit' => [
				'type' => TypeRegistry::int(),
				'defaultValue' => 25
			],
			'orderBy' => [
				'type' => TypeRegistry::eNum([
					'name' => static::$typeName . '_order_by',
					'description' => 'Fields on which items can be sorted',
					'values' => static::getOrderByOptions()
				]),
				'defaultValue' => NULL // will use default sort option
			],
			'orderDir' => [
				'type' => TypeRegistry::eNum([
					'name' => static::$typeName . '_order_dir',
					'description' => 'Directions in which items can be sorted',
					'values' => [ 'ASC', 'DESC' ]
				]),
				'defaultValue' => 'DESC'
			],
			'honorPinned' => [
				'type' => TypeRegistry::boolean(),
				'defaultValue' => true
			]
		);
	}

	/**
	 * Return the available sorting options
	 *
	 * @return	array
	 */
	public static function getOrderByOptions(): array
	{
		return array('title', 'author_name', 'last_comment_name');
	}

	/**
	 * Get the field config for the item permissions query
	 *
	 * @return	array
	 */
	public static function getItemPermissionFields(): array
	{
		return array(
			'canComment' => [
				'type' => TypeRegistry::boolean(),
				'description' => 'Can the logged in user comment on this item?',
				'resolve' => function ($item, $args, $context) {
					return $item->canComment( Member::loggedIn(), FALSE );
				}
			],
			'commentInformation' => [
				'type' => TypeRegistry::string(),
				'description' => 'A message providing some information about the comment form availability',
				'resolve' => function ($item, $args, $context) {
					if( $item->canComment( Member::loggedIn(), FALSE ) )
					{
						if( IPS::classUsesTrait( $item, 'IPS\Content\Lockable' ) AND $item->locked() )
						{
							return 'locked_can_comment';
						}
					}
					else
					{
						if( IPS::classUsesTrait( $item, 'IPS\Content\Lockable' ) AND $item->locked() )
						{
							return 'locked_cannot_comment';
						}
						elseif( Member::loggedIn()->restrict_post )
						{
							return 'restricted_cannot_comment';
						} 
						elseif( Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] )
						{
							return 'unacknowledged_warning_cannot_post';
						}
						elseif( !Member::loggedIn()->checkPostsPerDay() )
						{
							return 'member_exceeded_posts_per_day';
						}
					}

					return NULL;
				}
			],
			'canCommentIfSignedIn' => [
				'type' => TypeRegistry::boolean(),
				'description' => 'Returns boolean indicating whether a guest who signs in would be able to comment on this item. Returns NULL if already signed in.',
				'resolve' => function ($item) {
					if ( !Member::loggedIn()->member_id )
					{
						$testUser = new Member;
						$testUser->member_group_id = Settings::i()->member_group;
						
						return $item->canComment( $testUser, FALSE );
					}

					return NULL;
				}
			],
			'canMarkAsRead' => [
				'type' => TypeRegistry::boolean(),
				'description' => 'Boolean indicating whether this item supports read markers, and if so, if the user can mark as read',
				'resolve' => function ($item) {
					return IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) && Member::loggedIn()->member_id;
				}
			],
			'canReport' => [
				'type' => TypeRegistry::boolean(),
				'description' => 'Can the user report this item?',
				'resolve' => function ($item) {
					return $item->canReport( Member::loggedIn() ) === TRUE;
				}
			],
			'canReportOrRevoke' => [
				'type' => TypeRegistry::boolean(),
				'description' => 'Can the user report (or revoke a report) on this item?',
				'resolve' => function ($item) {
					return $item->canReportOrRevoke( Member::loggedIn() ) === TRUE;
				}
			],
			'canShare' => [
				'type' => TypeRegistry::boolean(),
				'description' => 'Can this item be shared?',
				'resolve' => function ($item) {
					return $item->canShare();
				}
			]
		);
	}

	/**
	 * Get the comment type that goes with this item type
	 *
	 * @return	ObjectType
	 */
	protected static function getCommentType(): ObjectType
	{
		return \IPS\Content\Api\GraphQL\TypeRegistry::comment();
	}

	/**
	 * Return content images for the provided item
	 *
	 * @param Item $item
	 * @return    array|null
	 */
	protected static function contentImages(Item $item): array|NULL
	{
		try
		{
			if ( $images = $item->contentImages( 20 ) )
			{
				$toReturn = [];
				foreach( $images as $image )
				{
					foreach( $image as $extension => $file )
					{
						$toReturn[] = (string) File::get( $extension, $file )->url;
					}
				}
				return $toReturn;
			}
		}
		catch( BadMethodCallException $e ) { }

		return NULL;
	}

	/**
	 * Resolve the findCommentPosition field
	 *
	 * @param Item $item
	 * @param array $args    Arguments passed to this resolver
	 * @return int|NULL
	 */
	protected static function findCommentPosition(Item $item, array $args): int|NULL
	{
		if( $args['findComment'] === NULL )
		{
			return NULL;
		}

		/* @var Item $itemClass */
		$itemClass = get_class( $item );

		try 
		{
			/* @var Comment $commentClass */
			$commentClass = $itemClass::$commentClass;
			$comment = $commentClass::load( $args['findComment'] );

			// Check this comment belongs to this topic
			if( $comment->item() !== $item )
			{
				return NULL;
			}
		}
		catch (Exception $e)
		{
			return NULL;
		}

		return static::findComment($comment, $item);
	}

	/**
	 * Resolve the comments field
	 *
	 * @param Item $item
	 * @param array $args    Arguments passed to this resolver
	 * @return    array
	 */
	protected static function comments(Item $item, array $args): array
	{
		/* @var Item $itemClass */
		$itemClass = get_class( $item );

		$offset = 0;
		$limit = 25;

		/* Figure out where we're starting our offset */
		switch( $args['offsetPosition'] )
		{
			case 'UNREAD':
				$offset = static::getUnreadPosition($item) + $args['offsetAdjust'];
			break;
			case 'LAST':
				// Since we're zero-indexed, when we're working from the end we need to go one more to get the last item
				$offset = static::getEndPosition($item) + $args['offsetAdjust'] + 1;
			break;
			case 'ID':
				if( !isset( $args['findComment'] ) )
				{
					throw new OutOfRangeException;
				}

				$offset = static::getCommentPosition($item, $args['findComment']) + $args['offsetAdjust'];
			break;
			case 'FIRST':
			default:
				$offset = 0 + $args['offsetAdjust'];
		}

		/* Ensure offset is never lower than 0 */
		$offset = max( $offset, 0 );
		$limit = min( $args['limit'], 50 );

		if( $args['orderBy'] == 'DATE' )
		{
			/* @var Comment $commentClass */
			/* @var array $databaseColumnMap */
			$commentClass = $itemClass::$commentClass;
			$args['orderBy'] = $commentClass::$databaseColumnMap['date'];
		}

		
		// We can't allow straight boolean TRUE here otherwise members without permission
		// will see them. Instead, if TRUE is passed as a value in the query, set the value
		// to NULL which honors permissions.
		$includeDeleted = $args['includeDeleted'] ? NULL : FALSE;
		$includeHidden = $args['includeHidden'] ? NULL : FALSE;

		return $item->comments( $limit, $offset, $args['orderBy'], $args['orderDir'], NULL, $includeHidden, NULL, NULL, NULL, $includeDeleted );
	}

	/**
	 * Get the position of a specific comment
	 *
	 * @param 	Item $item
	 * @param 	int $commentID
	 * @return	int|null
	 */
	protected static function getCommentPosition(Item $item, int $commentID): ?int
	{
		$itemClass = get_class( $item );

		/* @var Comment $commentClass */
		$commentClass = $itemClass::$commentClass;

		try 
		{
			$comment = $commentClass::load($commentID);
			return static::findComment($comment, $item);
		}
		catch(Exception $error)
		{}

		return null;
	}

	/**
	 * Get the position of the last comment
	 *
	 * @param 	Item $item
	 * @return	int
	 */
	protected static function getEndPosition(Item $item): int
	{
		$comment = $item->comments( 1, NULL, 'date', 'desc' );
		return static::findComment($comment, $item);
	}

	/**
	 * Get the position of the first unread comment
	 *
	 * @param 	Item $item
	 * @return	int
	 */
	protected static function getUnreadPosition(Item $item): int
	{
		try
		{
			/* @var Item $class */
			$class = static::$itemClass;
			$timeLastRead = $item->timeLastRead();

			if ( $timeLastRead instanceof DateTime )
			{
				$comment = NULL;
				if( DateTime::ts( $item->mapped('date') ) < $timeLastRead )
				{
					$comment = $item->comments( 1, NULL, 'date', 'asc', NULL, NULL, $timeLastRead );
				}

				/* If we don't have any unread comments... */
				if ( !$comment and $class::$firstCommentRequired )
				{
					/* If we haven't read the item at all, go there */
					if ( $item->unread() )
					{
						return 0;
					}
					/* Otherwise, go to the last comment */
					else
					{
						$comment = $item->comments( 1, NULL, 'date', 'desc' );
					}
				}

				if( !$comment ){
					return 0;
				}
			}
			else
			{
				if ( $item->unread() )
				{
					/* If we do not have a time last read set for this content, fallback to the reset time */
					$resetTimes = Member::loggedIn()->markersResetTimes( $class::$application );

					if ( array_key_exists( $item->container()->_id, $resetTimes ) and $item->mapped('date') < $resetTimes[ $item->container()->_id ] )
					{
						$comment = $item->comments( 1, NULL, 'date', 'asc', NULL, NULL, DateTime::ts( $resetTimes[ $item->container()->_id ] ) );
						
						if ( !$comment || $class::$firstCommentRequired and $comment->isFirst() )
						{
							return 0;
						}
					}
					else
					{
						return 0;
					}
				}
				else
				{
					return 0;
				}
			}

			return static::findComment($comment, $item);
		}
		catch( Exception $e )
		{
			return 0;
		}
	}

	/**
	 * Find the position of a comment
	 *
	 * @param 	Comment $comment
	 * @param 	Item $item
	 * @return	int
	 */
	protected static function findComment(Comment $comment, Item $item): int
	{
		try 
		{
			/* @var array $databaseColumnMap */
			$commentClass = get_class( $comment );
			$idColumn = $commentClass::$databaseColumnId;
			$itemColumn = $commentClass::$databaseColumnMap['item'];

			/* Work out where the comment is in the item */	
			$directional = ( in_array( 'IPS\Content\Review', class_parents( $commentClass ) ) ) ? '>=?' : '<=?';
			$where = array(
				array( $commentClass::$databasePrefix . $itemColumn . '=?', $comment->$itemColumn ),
				array( $commentClass::$databasePrefix . $idColumn . $directional, $comment->$idColumn )
			);

			/* Exclude content pending deletion, as it will not be shown inline  */
			if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
			{
				$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '<>?', -2 );
			}
			elseif( isset( $commentClass::$databaseColumnMap['hidden'] ) )
			{
				$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '<>?', -2 );
			}

			if ( $commentClass::commentWhere() !== NULL )
			{
				$where[] = $commentClass::commentWhere();
			}
			if ( $container = $item->containerWrapper() )
			{
				if ( $commentClass::modPermission( 'view_hidden', NULL, $container ) === FALSE )
				{
					if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
					{
						$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '=?', 1 );
					}
					elseif( isset( $commentClass::$databaseColumnMap['hidden'] ) )
					{
						$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=?', 0 );
					}
				}
			}
			$commentPosition = Db::i()->select( 'COUNT(*) AS position', $commentClass::$databaseTable, $where )->first();

			/* @var Item $itemClass */
			$itemClass = get_class( $item );
			if( $itemClass::$firstCommentRequired ){
				$commentPosition = $commentPosition - 1;
			}

			return $commentPosition;
		} 
		catch( Exception $e )
		{
			return 0;
		}
	}

	/**
	 * Resolve the tags field
	 *
	 * @param 	Item $item
	 * @return	int|null
	 */
	protected static function timeLastRead(Item $item): ?int
	{
		$time = $item->timeLastRead();
		if( $time instanceof DateTime )
		{
			return (int) $time->setTimezone( new DateTimeZone( "UTC" ) )->format('U');
		}
		return NULL;
	}

	/**
	 * Resolve the tags field
	 *
	 * @param 	Item $item
	 * @return	array
	 */
	protected static function tags(Item $item): array
	{
		return $item->tags();
	}

	/**
	 * Resolve the author field
	 *
	 * @param 	Item $item
	 * @param 	array $args 	Arguments passed to this resolver
	 * @return	Member
	 */
	protected static function author(Item $item, array $args): Member
	{
		return $item->author();
	}

	/**
	 * Resolve the last comment author field
	 *
	 * @param 	Item $item
	 * @param 	array $args 	Arguments passed to this resolver
	 * @return	Member
	 */
	protected static function lastCommentAuthor(Item $item, array $args): Member
	{
		if( $item->mapped('num_comments') )
		{
			return $item->lastCommenter();
		}
		
		return $item->author();
	}

	 /**
	 * Resolve the last comment date field
	 *
	 * @param 	Item $item
	 * @param 	array $args 	Arguments passed to this resolver
	 * @return	string
	 */
	protected static function lastCommentDate(Item $item, array $args): string
	{
		if( $item->mapped('last_comment') )
		{
			return $item->mapped('last_comment');
		}
		
		return $item->mapped('date');
	}
}