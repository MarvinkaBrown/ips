<?php
/**
 * @brief		Base mutator class for Content Items
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Jun 2018
 */

namespace IPS\Content\Api\GraphQL;

use DomainException;
use IPS\Api\Exception;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Search\Index;
use IPS\Content\Search\SearchContent;
use IPS\DateTime;
use IPS\File;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use IPS\Settings;
use IPS\Text\Parser;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function intval;
use function strlen;
use function strpos;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base mutator class for Content Items
 */
abstract class ItemMutator extends ContentMutator
{
    public function args(): array
    {
        return [
            'category' =>  TypeRegistry::nonNull( TypeRegistry::id() ),
            'title' => TypeRegistry::nonNull( TypeRegistry::string() ),
            'content' => TypeRegistry::nonNull( TypeRegistry::string() ),
            'tags' => TypeRegistry::listOf( TypeRegistry::string() ),
            'state' => TypeRegistry::itemState(),
            'postKey' => TypeRegistry::string()
        ];
    }

	/**
	 * Mark as read
	 *
	 * @param	Item	$item			Item to mark as read
	 * @return	Item
	 */
	protected function _markRead( Item $item ): Item
	{
		$item->markRead();
		return $item;
	}

	/**
	 * Create
	 *
	 * @param array $itemData Item Data
	 * @param Model|null $container Container
	 * @param string|null $postKey Post key
	 * @return    Item
	 */
	protected function _create( array $itemData, Model|null $container = NULL, string|null $postKey = NULL ): Item
	{
		$class = $this->class;
		
		/* Work out the date */
		$date = DateTime::create();
				
		/* Create item */
		$item = $class::createItem( Member::loggedIn(), Request::i()->ipAddress(), $date, $container );
		$this->_createOrUpdate( $item, $itemData );
		$item->save();
		
		/* Create post */
		if ( $class::$firstCommentRequired )
		{	
			$attachmentIdsToClaim = array();	
			if ( $postKey )
			{
				try
				{
					$this->_addAttachmentsToContent( $postKey, $itemData['content'] );
				}
				catch ( DomainException $e )
				{
					throw new SafeException( 'ATTACHMENTS_TOO_LARGE', '2S401/1', 403 );
				}
			}
			
			$postContents = Parser::parseStatic( $itemData['content'], array( md5( $postKey . ':' ) ), Member::loggedIn(), $class::$application . '_' . IPS::mb_ucfirst( $class::$module ) );
			
			$commentClass = $item::$commentClass;
			$post = $commentClass::create( $item, $postContents, TRUE, Member::loggedIn()->member_id ? NULL : Member::loggedIn()->real_name, NULL, Member::loggedIn(), $date );
			$itemIdColumn = $item::$databaseColumnId;
			$postIdColumn = $commentClass::$databaseColumnId;
			File::claimAttachments( "{$postKey}:", $item->$itemIdColumn, $post->$postIdColumn );
			
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

		/* Mark it as read */
		if( IPS::classUsesTrait( $item, 'IPS\Content\ReadMarkers' ) )
		{
			$item->markRead();
		}
		
		/* Send notifications and dish out points */
		if ( !$item->hidden() )
		{
			$item->sendNotifications();
			Member::loggedIn()->achievementAction( 'core', 'NewContentItem', $item );
		}
		elseif( $item->hidden() !== -1 )
		{
			$item->sendUnapprovedNotification();
		}
		
		/* Output */
		return $item;
	}

	/**
	 * Create or update item
	 *
	 * @param Item $item The item
	 * @param array $itemData Item data
	 * @param string $type add or edit
	 * @param string|null $postKey Post key
	 * @return    Item
	 */
	protected function _createOrUpdate( Item $item, array $itemData=array(), string $type='add', string|null $postKey = NULL ): Item
	{
		$class = $this->class;

		/* Title */
		if ( isset( $itemData['title'] ) and isset( $item::$databaseColumnMap['title'] ) )
		{
			$titleColumn = $item::$databaseColumnMap['title'];
			$item->$titleColumn = $itemData['title'];
		}
		
		/* Tags */
		if ( ( isset( $itemData['prefix'] ) or isset( $itemData['tags'] ) ) and IPS::classUsesTrait( $item, 'IPS\Content\Taggable' ) )
		{
			if ( Member::loggedIn()->member_id && $item::canTag( NULL, $item->containerWrapper() ) )
			{
				$source = array_map( 'trim', array_unique( $class::definedTags() ) );

				/* Filter our provided tags and exclude any that are invalid */
				$validTags = array_filter( array_map( 'trim', array_unique( $itemData['tags'] ) ), function($tag) use ($source) {
					
					if( !in_array($tag, $source) )
					{
						return FALSE;
					}

					if( strpos( $tag, '#' ) !== FALSE )
					{
						return FALSE;
					}

					return TRUE;
				});

				if( Settings::i()->tags_min && count( $validTags ) < Settings::i()->tags_min )
				{
					throw new SafeException( 'TOO_FEW_TAGS', 'GQL/0011/1', 400 );
				}

				if( Settings::i()->tags_max && count( $validTags ) > Settings::i()->tags_max )
				{
					throw new SafeException( 'TOO_MANY_TAGS', 'GQL/0011/2', 400 );
				}
	
				/* we need to save the item before we set the tags because setTags requires that the item exists */
				$idColumn = $item::$databaseColumnId;
				if ( !$item->$idColumn )
				{
					$item->save();
				}
	
				$item->setTags( $validTags );
			}
		}
		
		/* Open/closed */
		/* @var array $databaseColumnMap */
		if ( isset( $itemData['state']['locked'] ) and IPS::classUsesTrait( $item, 'IPS\Content\Lockable' ) )
		{
			if ( Member::loggedIn()->member_id && ( $itemData['state']['locked'] and $item->canLock() ) or ( !$itemData['state']['locked'] and $item->canUnlock() ) )
			{
				if ( isset( $item::$databaseColumnMap['locked'] ) )
				{
					$lockedColumn = $item::$databaseColumnMap['locked'];
					$item->$lockedColumn = intval( $itemData['state']['locked'] );
				}
				else
				{
					$stateColumn = $item::$databaseColumnMap['status'];
					$item->$stateColumn = $itemData['state']['locked'] ? 'closed' : 'open';
				}
			}
		}
		
		/* Hidden */
		if ( isset( $itemData['state']['hidden'] ) and IPS::classUsesTrait( $item, 'IPS\Content\Hideable' ) )
		{
			if ( Member::loggedIn()->member_id && ( $itemData['state']['hidden'] and $item->canHide() ) or ( !$itemData['state']['hidden'] and $item->canUnhide() ) )
			{
				$idColumn = $item::$databaseColumnId;
				if ( $itemData['state']['hidden'] )
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
							$item->$hiddenColumn = $itemData['state']['hidden'];
						}
						else
						{
							$approvedColumn = $item::$databaseColumnMap['approved'];
							$item->$approvedColumn = ( $itemData['state']['hidden'] == -1 ) ? -1 : 0;
						}
					}
				}
				else
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
		if ( isset( $itemData['state']['pinned'] ) and IPS::classUsesTrait( $item, 'IPS\Content\Pinnable' ) )
		{
			if ( Member::loggedIn()->member_id && ( $itemData['state']['pinned'] and $item->canPin() ) or ( !$itemData['state']['pinned'] and $item->canUnpin() ) )
			{
				$pinnedColumn = $item::$databaseColumnMap['pinned'];
				$item->$pinnedColumn = intval( $itemData['state']['pinned'] );
			}
		}
		
		/* Featured */
		if ( isset( $itemData['state']['featured'] ) and IPS::classUsesTrait( $item, 'IPS\Content\Featurable' ) )
		{
			if ( Member::loggedIn()->member_id && $item->canFeature() )
			{
				$featuredColumn = $item::$databaseColumnMap['featured'];
				$item->$featuredColumn = intval( $itemData['state']['featured'] );
			}
		}

		/* Update first comment if required, and it's not a new item */
		$field = $item::$databaseColumnMap['first_comment_id'] ?? NULL;
		$commentClass = $item::$commentClass;
		$contentField = $commentClass::$databaseColumnMap['content'];
		if ( $item::$firstCommentRequired AND isset( $item->$field ) AND isset( $itemData[ $contentField ] ) AND $type == 'edit' )
		{
			$attachmentIdsToClaim = array();
			if ( $postKey )
			{
				try
				{
					$this->_addAttachmentsToContent( $postKey, $itemData[ $contentField ] );
				}
				catch ( DomainException $e )
				{
					throw new SafeException( 'ATTACHMENTS_TOO_LARGE', '2S401/2', 403 );
				}
			}
			
			$content = Parser::parseStatic( $itemData[$contentField], array( $item->_id, $item->$field ), Member::loggedIn(), $item::$application . '_' . IPS::mb_ucfirst( $item::$module ) );

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


}