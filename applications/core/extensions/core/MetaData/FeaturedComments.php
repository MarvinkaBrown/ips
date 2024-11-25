<?php
/**
 * @brief		Meta Data: Featured Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		04 Dec 2016
 */

namespace IPS\core\extensions\core\MetaData;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Db;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Meta Data: Featured Comments
 */
class FeaturedComments
{

	/**
	 * Feature A Comment
	 *
	 * @param	Item		$item		The content item
	 * @param	Comment	$comment	The Comment
	 * @param	string|NULL				$note		An optional note to include
	 * @param	Member|NULL		$member		The member featuring the comment
	 * @return	void
	 */
	public function featureComment( Item $item, Comment $comment, ?string $note = NULL, ?Member $member = NULL ) : void
	{
		$member				= $member ?: Member::loggedIn();
		$idColumn			= $item::$databaseColumnId;
		$commentIdColumn	= $comment::$databaseColumnId;
		
		$save = array(
			'comment' 		=> $comment->$commentIdColumn,
			'featured_by'	=> $member->member_id
		);
		
		if ( $note )
		{
			$save['note'] = $note;
		}
		
		$item->addMeta( 'core_FeaturedComments', $save );
	}
	
	/**
	 * Unfeature a comment
	 *
	 * @param	Item		$item		The content item
	 * @param	Comment	$comment	The Comment
	 * @param	Member|NULL		$member		The member unfeaturing the comment
	 * @return	void
	 */
	public function unfeatureComment( Item $item, Comment $comment, ?Member $member = NULL ) : void
	{
		$member = $member ?: Member::loggedIn();
		$commentIdField = $comment::$databaseColumnId;
		
		$idToRemove = FALSE;
		foreach( $item->getMeta()['core_FeaturedComments'] AS $key => $data )
		{
			if ( $data['comment'] == $comment->$commentIdField )
			{
				$idToRemove = $key;
				break;
			}
		}
		
		$item->deleteMeta( $idToRemove );
	}
	
	/**
	 * Get Featured Comments in the most efficient way possible
	 *
	 * @param	Item	$item	The content item
	 * @return	array
	 */
	public function featuredComments( Item $item ) : array
	{
		if ( $meta = $item->getMeta() AND isset( $meta['core_FeaturedComments'] ) AND is_array( $meta['core_FeaturedComments'] ) )
		{
			/* Start by constructing our array and gathering ID's - we'll need them later */
			$comments	= array();
			$commentIds	= array();
			$reviewIds	= array();
			$memberIds	= array();
			foreach( $meta['core_FeaturedComments'] AS $key => $comment )
			{
				$comments[ $comment['comment'] ] = array(
					'note'		=> $comment['note'] ?? '',
				);
				$commentIds[] = $comment['comment'];

				$memberIds[ $comment['featured_by'] ][]	= $comment['comment'];
			}

			/* @var Comment $commentClass */
			$commentClass = $item::$commentClass;
			$commentIdField = $commentClass::$databaseColumnId;

			if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
			{
				$col = $commentClass::$databaseColumnMap['hidden'];
			}
			else if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
			{
				$col = $commentClass::$databaseColumnMap['approved'];
			}

			/* @var array $databaseColumnMap */
			$softDeleted = [];
			foreach( new ActiveRecordIterator( Db::i()->select( '*', $commentClass::$databaseTable, array( Db::i()->in( $commentClass::$databasePrefix . $commentIdField, $commentIds ) ), $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'] . " DESC" ), $commentClass ) AS $row )
			{
				if( $row->$col < 0 )
				{
					unset( $comments[$row->$commentIdField] );
					$softDeleted[] = $row->$commentIdField;
				}
				else
				{
					$comments[ $row->$commentIdField ]['comment'] = $row;
				}
			}

			/* And finally, who featured them */
			if ( count( $memberIds ) )
			{
				foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_members', array( Db::i()->in( 'member_id', array_keys( $memberIds ) ) ) ), 'IPS\Member' ) AS $m )
				{
					foreach( $memberIds[ $m->member_id ] AS $attach )
					{
						if( in_array( $attach, $softDeleted ) )
						{
							continue;
						}

						$comments[ $attach ]['featured_by'] = $m;
					}
				}
			}

			/* And off we go! */
			return $comments;
		}
		
		return array();
	}

	/**
	 * Is this comment shown at the top of all replies?
	 *
	 * @param	Comment	$item	The comment
	 * @return	bool
	 */
	public function isCommentShownAtTheTop( Comment $item ): bool
	{
		try
		{
			$idColumn = $item::$databaseColumnId;
			if ( $meta = $item->item()->getMeta() AND isset( $meta['core_FeaturedComments'] ) )
			{
				foreach( $meta['core_FeaturedComments'] AS $key => $comment )
				{
					if ( $comment['comment'] === $item->$idColumn )
					{
						return TRUE;
					}
				}
			}

			return FALSE;
		}
		catch( BadMethodCallException $e )
		{
			return FALSE;
		}
	}
}
