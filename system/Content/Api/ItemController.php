<?php
/**
 * @brief		Base API endpoint for Content Items
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Dec 2015
 */

namespace IPS\Content\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Api\Webhook;
use IPS\Content\Comment;
use IPS\Content\Filter;
use IPS\Content\Item;
use IPS\Content\Search\Index;
use IPS\Content\Search\SearchContent;
use IPS\DateTime;
use IPS\Db;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use IPS\Poll;
use IPS\Request;
use IPS\Settings;
use IPS\Text\Parser;
use OutOfRangeException;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base API endpoint for Content Items
 */
class ItemController extends Controller
{
	/**
	 * List
	 *
	 * @param	array	$where			Extra WHERE clause
	 * @param	string	$containerParam	The parameter which includes the container values
	 * @param	bool	$byPassPerms	If permissions should be ignored
	 * @param	string|null	$customSort		Custom sort by parameter to use
	 * @return	PaginatedResponse
	 */
	protected function _list( array $where = array(), string $containerParam = 'categories', bool $byPassPerms = FALSE, string|null $customSort = NULL ): PaginatedResponse
	{
		/* @var array $databaseColumnMap */
		$class = $this->class;
		
		/* Containers */
		if ( $containerParam and isset( Request::i()->$containerParam ) )
		{
			$where[] = array( Db::i()->in( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['container'], array_map( 'intval', array_filter( explode( ',', Request::i()->$containerParam ) ) ) ) );
		}

		/* IDs */
		if ( isset( Request::i()->ids ) )
		{
			$idField = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnId;
			$where[] = array(Db::i()->in( $idField, array_map( 'intval', explode(',', Request::i()->ids ) ) ) );
		}
		
		/* Authors */
		if ( isset( Request::i()->authors ) )
		{
			$where[] = array( Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['author'], array_map( 'intval', array_filter( explode( ',', Request::i()->authors ) ) ) ) );
		}
		
		/* Pinned? */
		if ( isset( Request::i()->pinned ) AND IPS::classUsesTrait( $class, 'IPS\Content\Pinnable' ) )
		{
			if ( Request::i()->pinned )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . "=1" );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . "=0" );
			}
		}
		
		/* Featured? */
		if ( isset( Request::i()->featured ) AND IPS::classUsesTrait( $class, 'IPS\Content\Featurable' ) )
		{
			if ( Request::i()->featured )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['featured'] . "=1" );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['featured'] . "=0" );
			}
		}
		
		/* Locked? */
		if ( isset( Request::i()->locked ) AND IPS::classUsesTrait( $class, 'IPS\Content\Lockable' ) )
		{
			if ( isset( $class::$databaseColumnMap['locked'] ) )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['locked'] . '=?', intval( Request::i()->locked ) );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['state'] . '=?', Request::i()->locked ? 'closed' : 'open' );
			}
		}
		
		/* Hidden */
		if ( isset( Request::i()->hidden ) AND IPS::classUSesTrait( $class, 'IPS\Content\Hideable' ) )
		{
			if ( Request::i()->hidden )
			{
				if ( isset( $class::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['hidden'] . '<>0' );
				}
				else
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['approved'] . '<>1' );
				}
			}
			else
			{
				if ( isset( $class::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['hidden'] . '=0' );
				}
				else
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['approved'] . '=1' );
				}
			}
		}
		
		/* Has poll? */
		if ( isset( Request::i()->hasPoll ) AND IPS::classUsesTrait( $class, 'IPS\Content\Polls' ) )
		{
			if ( Request::i()->hasPoll )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['poll'] . ">0" );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['poll'] . " IS NULL" );
			}
		}
		
		/* Sort */
		$supportedSortBy = array( 'date', 'title' );
		if( isset($class::$databaseColumnMap['updated'] ) )
		{
			$supportedSortBy[] = 'updated';
		}

		if( $customSort !== NULL )
		{
			$sortBy = $customSort;
		}
		elseif ( isset( Request::i()->sortBy ) and in_array( Request::i()->sortBy, $supportedSortBy ) )
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnMap[ Request::i()->sortBy ];
		}
		else
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnId;
		}
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';
		
		/* Get results */
		if ( $this->member and !$byPassPerms )
		{
			$query = $class::getItemsWithPermission( $where, "{$sortBy} {$sortDir}", NULL, 'view', Filter::FILTER_AUTOMATIC, 0, $this->member )->getInnerIterator();
			$count = $class::getItemsWithPermission( $where, "{$sortBy} {$sortDir}", NULL, 'view', Filter::FILTER_AUTOMATIC, 0, $this->member, FALSE, FALSE, FALSE, TRUE );
		}
		else
		{
			/* And no PBR or queued for deletion things either */
			if ( isset( $class::$databaseColumnMap['hidden'] ) )
			{
				$col = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['hidden'];
				$where[] = array( "{$col}!=-2 AND {$col} !=-3" );
			}
			else if ( isset( $class::$databaseColumnMap['approved'] ) )
			{
				$col = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['approved'];
				$where[] = array( "{$col}!=-2 AND {$col}!=-3" );
			}

			$query = Db::i()->select( '*', $class::$databaseTable, $where, "{$sortBy} {$sortDir}" );
			$count = Db::i()->select( 'COUNT(*)', $class::$databaseTable, $where )->first();
		}
		
		/* Return */
		return new PaginatedResponse(
			200,
			$query,
			isset( Request::i()->page ) ? Request::i()->page : 1,
			$class,
			$count,
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}
	
	/**
	 * View
	 *
	 * @param	int	$id	ID Number
	 * @return	Response
	 */
	protected function _view( int $id ): Response
	{
		$class = $this->class;
		
		$item = $class::load( $id );
		if ( $this->member and !$item->can( 'read', $this->member ) )
		{
			throw new OutOfRangeException;
		}
		
		return new Response( 200, $item->apiOutput( $this->member ) );
	}

	/**
	 * Create or update item
	 *
	 * @param	Item	$item	The item
	 * @param	string				$type	add or edit
	 * @return	Item
	 */
	protected function _createOrUpdate( Item $item, string $type='add' ): Item
	{
		/* @var array $databaseColumnMap */
		/* Title */
		if ( isset( Request::i()->title ) and isset( $item::$databaseColumnMap['title'] ) )
		{
			$titleColumn = $item::$databaseColumnMap['title'];
			$item->$titleColumn = Request::i()->title;
		}
		
		/* Tags */
		if ( ( isset( Request::i()->prefix ) or isset( Request::i()->tags ) ) and IPS::classUsesTrait( $item, 'IPS\Content\Taggable' ) )
		{
			if ( !$this->member or $item::canTag( $this->member, $item->containerWrapper() ) )
			{			
				$tags = isset( Request::i()->tags ) ? array_filter( explode( ',', Request::i()->tags ) ) : $item->tags();
				
				if ( !$this->member or $item::canPrefix( $this->member, $item->containerWrapper() ) )
				{
					if ( isset( Request::i()->prefix ) )
					{
						if ( Request::i()->prefix )
						{
							$tags['prefix'] = Request::i()->prefix;
						}
					}
					elseif ( $existingPrefix = $item->prefix() )
					{
						$tags['prefix'] = $existingPrefix;
					}
				}
	
				/* we need to save the item before we set the tags because setTags requires that the item exists */
				$idColumn = $item::$databaseColumnId;
				if ( !$item->$idColumn )
				{
					$item->save();
				}
	
				$item->setTags( $tags );
			}
		}
		
		/* Open/closed */
		if ( isset( Request::i()->locked ) and IPS::classUsesTrait( $item, 'IPS\Content\Lockable' ) )
		{
			if ( !$this->member or ( Request::i()->locked and $item->canLock( $this->member ) ) or ( !Request::i()->locked and $item->canUnlock( $this->member ) ) )
			{
				if ( isset( $item::$databaseColumnMap['locked'] ) )
				{
					$lockedColumn = $item::$databaseColumnMap['locked'];
					$item->$lockedColumn = intval( Request::i()->locked );
				}
				else
				{
					$stateColumn = $item::$databaseColumnMap['status'];
					$item->$stateColumn = Request::i()->locked ? 'closed' : 'open';
				}
			}
		}
		
		/* Hidden */
		if ( isset( Request::i()->hidden ) and IPS::classUsesTrait( $item, 'IPS\Content\Hideable' ) )
		{
			if ( !$this->member or ( Request::i()->hidden and $item->canHide( $this->member ) ) or ( !Request::i()->hidden and $item->canUnhide( $this->member ) ) )
			{
				$idColumn = $item::$databaseColumnId;
				if ( Request::i()->hidden AND $item->hidden() != -1 )
				{
					if ( $item->$idColumn )
					{
						$item->hide( FALSE );
					}
					else
					{
						if ( isset( $item::$databaseColumnMap['hidden'] ) )
						{
							$hiddenColumn = $item::$databaseColumnMap['hidden'];
							$item->$hiddenColumn = Request::i()->hidden;
						}
						else
						{
							$approvedColumn = $item::$databaseColumnMap['approved'];
							$item->$approvedColumn = ( Request::i()->hidden == -1 ) ? -1 : 0;
						}
					}
				}
				
				if ( !Request::i()->hidden AND $item->hidden() == -1 )
				{
					if ( $item->$idColumn )
					{
						$item->unhide( FALSE );
					}
					else
					{
						if ( isset( $item::$databaseColumnMap['hidden'] ) )
						{
							$hiddenColumn = $item::$databaseColumnMap['hidden'];
							$item->$hiddenColumn = 0;
						}
						else
						{
							$approvedColumn = $item::$databaseColumnMap['approved'];
							$item->$approvedColumn = 1;
						}
					}
				}
			}
		}
		
		/* Pinned */
		if ( isset( Request::i()->pinned ) and IPS::classUsesTrait( $item, 'IPS\Content\Pinnable' ) )
		{
			if ( !$this->member or ( Request::i()->pinned and $item->canPin( $this->member ) ) or ( !Request::i()->pinned and $item->canUnpin( $this->member ) ) )
			{
				$pinnedColumn = $item::$databaseColumnMap['pinned'];
				$item->$pinnedColumn = intval( Request::i()->pinned );
			}
		}
		
		/* Featured */
		if ( isset( Request::i()->featured ) and IPS::classUsesTrait( $item, 'IPS\Content\Featurable' ) )
		{
			if ( !$this->member or $item->canFeature( $this->member ) )
			{
				$featuredColumn = $item::$databaseColumnMap['featured'];
				$item->$featuredColumn = intval( Request::i()->featured );
			}
		}

		/** We intentionally allow anonymous content in nodes where it's possible (but where it's disabled via the settings */
		if( isset( Request::i()->anonymous ) AND IPS::classUsesTrait( $item, 'IPS\Content\Anonymous' ) )
		{
			/* we need to save the item before we set the anonymous data */
			$idColumn = $item::$databaseColumnId;
			if ( !$item->$idColumn )
			{
				$item->save();
			}
			try
			{
				$item->setAnonymous( (bool) Request::i()->anonymous, $item->author() );
			}
			catch ( BadMethodCallException $e ){}

		}

		/* Update first comment if required, and it's not a new item */
		$field = $item::$databaseColumnMap['first_comment_id'] ?? NULL;
		$commentClass = $item::$commentClass;
		$contentField = $commentClass::$databaseColumnMap['content'];
		if ( $item::$firstCommentRequired AND isset( $item->$field ) AND isset( Request::i()->$contentField ) AND $type == 'edit' )
		{
			$content = Request::i()->$contentField;
			if ( $this->member )
			{
				$content = Parser::parseStatic( $content, NULL, $this->member, $item::$application . '_' . IPS::mb_ucfirst( $item::$module ) );
			}

			try
			{
				/* @var Comment $commentClass */
				$comment = $commentClass::load( $item->$field );
			}
			catch ( OutOfRangeException $e )
			{
				throw new Exception( 'NO_FIRST_POST', '1S377/1', 400 );
			}

			$comment->$contentField = $content;
			$comment->save();

			/* Update Search Index of the first item */
			if( SearchContent::isSearchable( $item ) )
			{
				Index::i()->index( $comment );
			}
		}
		
		/* Return */
		return $item;
	}


	/**
	 * Create
	 *
	 * @param Model|null $container Container
	 * @param Member $author Author
	 * @param string $firstPostParam The parameter which contains the body for the first comment
	 * @return    Item
	 */
	protected function _create( Model|null $container, Member $author, string $firstPostParam = 'post' ): Item
	{
		$class = $this->class;
		
		/* Work out the date */
		$date = ( !$this->member and Request::i()->date ) ? new DateTime( Request::i()->date ) : DateTime::create();
		
		/* Create item */
		$item = $class::createItem( $author, ( !$this->member and Request::i()->ip_address ) ? Request::i()->ip_address : Request::i()->ipAddress(), $date, $container );

		$this->_createOrUpdate( $item );
		$item->save();

		/* Create post */
		if ( $class::$firstCommentRequired )
		{			
			$postContents = Request::i()->$firstPostParam;
			
			if ( $this->member )
			{
				$postContents = Parser::parseStatic( $postContents, NULL, $this->member, $class::$application . '_' . IPS::mb_ucfirst( $class::$module ) );
			}

			$commentClass = $item::$commentClass;
			$post = $commentClass::create( $item, $postContents, TRUE, $author->member_id ? NULL : $author->real_name, NULL, $author, $date, ( !$this->member and Request::i()->ip_address ) ? Request::i()->ip_address : Request::i()->ipAddress(), NULL, ( isset( Request::i()->anonymous ) ? (bool) Request::i()->anonymous : NULL ) );
			
			if ( isset( $class::$databaseColumnMap['first_comment_id'] ) )
			{
				$firstCommentColumn = $class::$databaseColumnMap['first_comment_id'];
				$commentIdColumn = $commentClass::$databaseColumnId;
				$item->$firstCommentColumn = $post->$commentIdColumn;
				$item->save();
			}
		}
		
		/* Index */
		if( SearchContent::isSearchable( $item ) )
		{
			Index::i()->index( $item );
		}
		
		/* Send webhooks */
		if ( in_array( $item->hidden(), array( -1, 0, 1 ) ) ) // i.e. not post before register or pending deletion
		{
			Webhook::fire( str_replace( '\\', '', substr( get_class( $item ), 3 ) ) . '_create', $item, $item->webhookFilters() );
		}
		
		/* Send notifications and dish out points */
		if ( !$item->hidden() )
		{
			$item->sendNotifications();
			$author->achievementAction( 'core', 'NewContentItem', $item );
		}
		elseif( !in_array( $item->hidden(), array( -1, -3 ) ) )
		{
			$item->sendUnapprovedNotification();
		}
		
		/* Output */
		return $item;
	}

	/**
	 * Create or update poll
	 * 
	 * @param	Item	$item	The content item to attach the poll to
	 * @param	string				$type	Whether we are adding or editing
	 * @return	void
	 */
	protected function _createOrUpdatePoll( Item $item, string $type ): void
	{
		/* Are we creating or updating a poll? */
		if( isset( Request::i()->poll_title ) AND isset( Request::i()->poll_options ) AND Request::i()->poll_title AND Request::i()->poll_options AND ( !$this->member OR $item::canCreatePoll( $this->member, $item->container() ) ) )
		{
			$canCreatePoll = TRUE;

			if( $type == 'edit' AND !$item->getPoll() )
			{
				$canCreatePoll = ( Settings::i()->startpoll_cutoff == -1 or DateTime::create()->sub( new DateInterval( 'PT' . Settings::i()->startpoll_cutoff . 'H' ) )->getTimestamp() < $item->mapped('date') );
			}

			if( $canCreatePoll === TRUE )
			{
				$poll = $item->getPoll() ?: new Poll;

				if( !$item->getPoll() )
				{
					$poll->starter_id		= $item->author()->member_id;
					$poll->poll_item_class	= get_class( $item );
				}

				$formatted = array(
					'title'		=> Request::i()->poll_title,
					'questions'	=> Request::i()->poll_options
				);

				if( isset( Request::i()->poll_only ) AND Request::i()->poll_only )
				{
					$formatted['poll_only'] = 1;
				}

				if( isset( Request::i()->poll_public ) AND Request::i()->poll_public )
				{
					$formatted['public'] = 1;
				}
				
				$poll->setDataFromForm( $formatted, TRUE );
				$poll->save();
				
				$item->author()->achievementAction( 'core', 'NewPoll', $poll );

				$item->poll_state = $poll->pid;
			}
		}
	}
	
	/**
	 * View Comments or Reviews
	 *
	 * @param	int		$id				ID Number
	 * @param	string	$commentClass	The class
	 * @param	array	$where			Base where clause
	 * @return	PaginatedResponse
	 */
	protected function _comments( int $id, string $commentClass, array $where = array() ): PaginatedResponse
	{
		/* Init */
		/* @var array $databaseColumnMap
		 * @var Item $itemClass
		 * @var Comment $commentClass */
		$itemClass = $this->class;
		$item = $itemClass::load( $id );
		if ( $this->member and !$item->can( 'read', $this->member ) )
		{
			throw new OutOfRangeException;
		}
		$itemIdColumn = $itemClass::$databaseColumnId;
		$where [] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $item->$itemIdColumn );

		/* Hideable? */
		if ( IPS::classUsesTrait( $commentClass, 'IPS\Content\Hideable' ) )
		{
			/* If request for hidden comments, only show if request is via API key or by a moderator */
			if ( isset( Request::i()->hidden ) AND Request::i()->hidden
				AND ( $this->member === NULL OR $commentClass::modPermission( 'view_hidden', $this->member ) ) )
			{
				if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . ' NOT IN( -3, -2 )' );
				}
				else
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . ' NOT IN( -3, -2 )' );
				}
			}
			/* Do not show hidden comments */
			else
			{
				if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=0' );
				}
				else
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '=1' );
				}
			}
		}
		
		if ( $commentClass::commentWhere() !== NULL )
		{
			$where[] = $commentClass::commentWhere();
		}
		
		/* Sort */
		$sortBy = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'];
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';
		
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', $commentClass::$databaseTable, $where, "{$sortBy} {$sortDir}" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			$commentClass,
			Db::i()->select( 'COUNT(*)', $commentClass::$databaseTable, $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}
}
