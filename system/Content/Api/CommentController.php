<?php
/**
 * @brief		Base API endpoint for Content Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Dec 2015
 */

namespace IPS\Content\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Content\Comment;
use IPS\Content\Filter;
use IPS\Content\Item;
use IPS\Content\Reaction;
use IPS\Content\Search\Index;
use IPS\Content\Search\SearchContent;
use IPS\core\Reports\Report;
use IPS\DateTime;
use IPS\Db;
use IPS\IPS;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use IPS\Text\Parser;
use OutOfRangeException;
use UnexpectedValueException;
use function defined;
use function get_class;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base API endpoint for Content Comments
 */
class CommentController extends Controller
{
	/**
	 * List
	 *
	 * @param	array	$where			Extra WHERE clause
	 * @param	string	$containerParam	The parameter which includes the container values
	 * @param	bool	$byPassPerms	If permissions should be ignored
	 * @return	PaginatedResponse
	 */
	protected function _list( array $where = array(), string $containerParam = 'categories', bool $byPassPerms=FALSE ): PaginatedResponse
	{
		/* @var array $databaseColumnMap */
		$class = $this->class;
		$itemClass = $class::$itemClass;
		
		/* Containers */
		if ( isset( Request::i()->$containerParam ) )
		{
			$where[] = array( Db::i()->in( $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'], array_map( 'intval', array_filter( explode( ',', Request::i()->$containerParam ) ) ) ) );
		}
		
		/* Authors */
		if ( isset( Request::i()->authors ) )
		{
			$where[] = array( Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['author'], array_map( 'intval', array_filter( explode( ',', Request::i()->authors ) ) ) ) );
		}
		
		/* Pinned? */
		if ( isset( Request::i()->pinned ) AND IPS::classUsesTrait( $itemClass, 'IPS\Content\Pinnable' ) )
		{
			if ( Request::i()->pinned )
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['pinned'] . "=1" );
			}
			else
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['pinned'] . "=0" );
			}
		}
		
		/* Featured? */
		if ( isset( Request::i()->featured ) AND IPS::classUsesTrait( $itemClass, 'IPS\Content\Featurable' ) )
		{
			if ( Request::i()->featured )
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['featured'] . "=1" );
			}
			else
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['featured'] . "=0" );
			}
		}
		
		/* Locked? */
		if ( isset( Request::i()->locked ) AND IPS::classUsesTrait( $itemClass, 'IPS\Content\Lockable' ) )
		{
			if ( isset( $itemClass::$databaseColumnMap['locked'] ) )
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['locked'] . '=?', intval( Request::i()->locked ) );
			}
			else
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['state'] . '=?', Request::i()->locked ? 'closed' : 'open' );
			}
		}
		
		/* Hidden */
		if ( isset( Request::i()->hidden ) AND IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
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
		if ( isset( Request::i()->hasPoll ) AND IPS::classUsesTrait( $itemClass, 'IPS\Content\Polls' ) )
		{
			if ( Request::i()->hasPoll )
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['poll'] . ">0" );
			}
			else
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['poll'] . "=0" );
			}
		}
		
		/* Sort */
		if ( isset( Request::i()->sortBy ) and Request::i()->sortBy == 'date' )
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnMap[ Request::i()->sortBy ];
		}
		if ( isset( Request::i()->sortBy ) and Request::i()->sortBy == 'title' )
		{
			$sortBy = $itemClass::$databasePrefix . $itemClass::$databaseColumnMap[ Request::i()->sortBy ];
		}
		else
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnId;
		}
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';
		
		/* Get results */
		if ( $this->member and !$byPassPerms )
		{
			$query = $class::getItemsWithPermission( $where, "{$sortBy} {$sortDir}", NULL, 'read', Filter::FILTER_AUTOMATIC, 0, $this->member )->getInnerIterator();
			$count = $class::getItemsWithPermission( $where, "{$sortBy} {$sortDir}", NULL, 'read', Filter::FILTER_AUTOMATIC, 0, $this->member, FALSE, FALSE, FALSE, TRUE );
		}
		else
		{
			$itemWhere = array();
			
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

			$query = Db::i()->select( '*', $class::$databaseTable, $where, "{$sortBy} {$sortDir}" )->join( $itemClass::$databaseTable, array_merge( array( array( $class::$databaseTable . "." . $class::$databasePrefix . $class::$databaseColumnMap['item'] . "=" . $itemClass::$databaseTable . "." . $itemClass::$databasePrefix . $itemClass::$databaseColumnId ) ), $itemWhere ), 'STRAIGHT_JOIN' );
			$count = Db::i()->select( 'COUNT(*)', $class::$databaseTable, $where )->join( $itemClass::$databaseTable, array_merge( array( array( $class::$databaseTable . "." . $class::$databasePrefix . $class::$databaseColumnMap['item'] . "=" . $itemClass::$databaseTable . "." . $itemClass::$databasePrefix . $itemClass::$databaseColumnId ) ), $itemWhere ), 'STRAIGHT_JOIN' )->first();
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
	 * Create
	 *
	 * @param	Item	$item			Content Item
	 * @param	Member			$author			Author
	 * @param	string				$contentParam	The parameter that contains the content body
	 * @return	Response
	 */
	protected function _create( Item $item, Member $author, string $contentParam='content' ): Response
	{
		return new Response( 201, $this->_createComment( $item, $author, $contentParam )->apiOutput( $this->member ) );
	}
	
	/**
	 * Create
	 *
	 * @param	Item	$item			Content Item
	 * @param	Member			$author			Author
	 * @param	string				$contentParam	The parameter that contains the content body
	 * @return	Comment
	 */
	protected function _createComment( Item $item, Member $author, string $contentParam='content' ): Comment
	{
		/* Work out the date */
		$date = ( !$this->member and Request::i()->date ) ? new DateTime( Request::i()->date ) : DateTime::create();
		
		/* Is it hidden? */
		$hidden = NULL;
		if ( isset( Request::i()->hidden ) and !$this->member )
		{
			$hidden = Request::i()->hidden;
		}
		
		/* Parse */
		$content = Request::i()->$contentParam;
		if ( $this->member )
		{
			$content = Parser::parseStatic( $content, NULL, $this->member, $item::$application . '_' . IPS::mb_ucfirst( $item::$module ) );
		}
		
		/* Create post */
		/* @var Comment $class */
		$class = $this->class;
		if ( in_array( 'IPS\Content\Review', class_parents( $class ) ) )
		{
			$comment = $class::create( $item, $content, FALSE, intval( Request::i()->rating ), $author->member_id ? NULL : $author->real_name, $author, $date, ( !$this->member and Request::i()->ip_address ) ? Request::i()->ip_address : Request::i()->ipAddress(), $hidden, ( isset( Request::i()->anonymous ) ? (bool) Request::i()->anonymous : NULL ) );
		}
		else
		{
			$comment = $class::create( $item, $content, FALSE, $author->member_id ? NULL : $author->real_name, NULL, $author, $date, ( !$this->member and Request::i()->ip_address ) ? Request::i()->ip_address : Request::i()->ipAddress(), $hidden, ( isset( Request::i()->anonymous ) ? (bool) Request::i()->anonymous : NULL ) );
		}

		/* Index */
		if( SearchContent::isSearchable( $item ) )
		{
			if ( $item::$firstCommentRequired and !$comment->isFirst() )
			{
				if( SearchContent::isSearchable( $class ) )
				{					
					Index::i()->index( $item->firstComment() );
				}
			}
			else
			{
				Index::i()->index( $item );
			}
		}
		if( SearchContent::isSearchable( $comment ) )
		{
			Index::i()->index( $comment );
		}
		
		/* Hide */
		if ( isset( Request::i()->hidden ) and $this->member and Request::i()->hidden and $comment->canHide( $this->member ) )
		{
			$comment->hide( $this->member );
		}
		
		/* Return */
		return $comment;
	}
	
	/**
	 * Edit
	 *
	 * @param	Comment		$comment		The comment
	 * @param	string						$contentParam	The parameter that contains the content body
	 * @throws	InvalidArgumentException	Invalid author
	 * @return	Response
	 */
	protected function _edit( Comment $comment, string $contentParam='content' ): Response
	{
		/* @var array $databaseColumnMap */
		/* Hidden */
		if ( !$this->member and isset( Request::i()->hidden ) )
		{			
			if ( Request::i()->hidden )
			{
				$comment->hide( FALSE );
			}
			else
			{
				$comment->unhide( FALSE );
			}
		}
		
		/* Change author */
		if ( !$this->member and isset( Request::i()->author ) )
		{
			$authorIdColumn = $comment::$databaseColumnMap['author'];
			$authorNameColumn = $comment::$databaseColumnMap['author_name'];
			
			/* Just renaming the guest */
			if ( !$comment->$authorIdColumn and ( !isset( Request::i()->author ) or !Request::i()->author ) and isset( Request::i()->author_name ) )
			{
				$comment->$authorNameColumn = Request::i()->author_name;
			}
			
			/* Actually changing the author */
			else
			{
				try
				{
					$member = Member::load( Request::i()->author );
					if ( !$member->member_id )
					{
						throw new InvalidArgumentException;
					}
					
					$comment->changeAuthor( $member );
				}
				catch ( OutOfRangeException $e )
				{
					throw new InvalidArgumentException;
				}
			}
		}
		
		/* Post value */
		if ( isset( Request::i()->$contentParam ) )
		{
			$contentColumn = $comment::$databaseColumnMap['content'];
			
			$content = Request::i()->$contentParam;
			if ( $this->member )
			{
				$item = $comment->item();
				$content = Parser::parseStatic( $content, NULL, $this->member, $item::$application . '_' . IPS::mb_ucfirst( $item::$module ) );
			}
			$comment->$contentColumn =$content;
		}
		
		/* Rating */
		$ratingChanged = FALSE;
		if ( isset( Request::i()->rating ) )
		{
			$ratingChanged = TRUE;
			$ratingColumn = $comment::$databaseColumnMap['rating'];
			$comment->$ratingColumn = intval( Request::i()->rating );
		}
		
		/* Save and return */
		$comment->save();
		
		/* Recalculate ratings */
		if ( $ratingChanged )
		{
			$itemClass = $comment::$itemClass;
			$ratingField = $itemClass::$databaseColumnMap['rating'];
			
			$comment->item()->$ratingField = $comment->item()->averageReviewRating() ?: 0;
			$comment->item()->save();
		}
		
		/* Return */
		return new Response( 200, $comment->apiOutput( $this->member ) );
	}

	/**
	 * Delete a reaction to a comment
	 *
	 * @param int $id
	 * @return Response
	 * @throws Exception
	 */
	public function _reactRemove( int $id ): Response
	{
		try
		{
			$member = Member::load( Request::i()->author );
		}
		catch( OutOfRangeException $e )
		{
			throw new Exception( 'NO_AUTHOR', '1S425/6', 404 );
		}

		try
		{
			$class = $this->class;
			if ( $this->member )
			{
				$object = $class::loadAndCheckPerms( $id, $this->member );
			}
			else
			{
				$object = $class::load( $id );
			}

			$object->removeReaction( $member );

			return new Response( 200, $object->apiOutput( $this->member ) );
		}
		catch ( DomainException $e )
		{
			throw new Exception( $e->getMessage(), '1S425/7', 403 );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1S425/8', 404 );
		}
	}

	/**
	 * React to a comment
	 *
	 * @param int $id
	 * @return Response
	 * @throws Exception
	 */
	public function _reactAdd( int $id ): Response
	{
		try
		{
			$reaction = Reaction::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			throw new Exception( 'NO_REACTION', '1S425/2', 404 );
		}

		try
		{
			$member = Member::load( Request::i()->author );
		}
		catch( OutOfRangeException $e )
		{
			throw new Exception( 'NO_AUTHOR', '1S425/3', 404 );
		}

		try
		{
			$class = $this->class;
			if ( $this->member )
			{
				$object = $class::loadAndCheckPerms( $id, $this->member );
			}
			else
			{
				$object = $class::load( $id );
			}

			$object->react( $reaction, $member );

			return new Response( 200, $object->apiOutput( $this->member ) );
		}
		catch ( DomainException $e )
		{
			throw new Exception( 'REACT_ERROR_' . $e->getMessage(), '1S425/4', 403 );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1S425/5', 404 );
		}
	}


	/**
	 * Report a comment
	 *
	 * @param int $id
	 * @return Response
	 * @throws Exception
	 */
	public function _report( int $id ): Response
	{
		try
		{
			$member = Member::load( Request::i()->author );
		}
		catch( OutOfRangeException $e )
		{
			throw new Exception( 'NO_AUTHOR', '1S425/B', 404 );
		}

		$class = $this->class;
		$idColumn = $class::$databaseColumnId;
		if ( $this->member )
		{
			$object = $class::loadAndCheckPerms( $id, $this->member );
		}
		else
		{
			$object = $class::load( $id );
		}

		/* Has this member already reported this in the past 24 hours */
		try
		{
			$index = Report::loadByClassAndId( get_class( $object ), $object->$idColumn );
			$report = Db::i()->select( '*', 'core_rc_reports', array( 'rid=? and report_by=? and date_reported > ?', $index->id, $member, time() - ( Settings::i()->automoderation_report_again_mins * 60 ) ) )->first();

			/* They have aleady reported, so do nothing */
			throw new Exception( 'REPORTED_ALREADY', '1S425/C', 404 );
		}
		catch( \Exception $e )
		{
			/* No issues here */
		}

		try
		{
			$object->report( ( isset( Request::i()->message ) ? Request::i()->message : '' ), ( isset(Request::i()->report_type) ? Request::i()->report_type : 0 ), $member );
		}
		catch( UnexpectedValueException $e )
		{
			throw new Exception( 'REPORT_ERROR_' . $e->getMessage(), '1S425/B', 403 );
		}

		return new Response( 200, $object->apiOutput( $this->member ) );
	}
}