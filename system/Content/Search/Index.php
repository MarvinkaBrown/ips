<?php
/**
 * @brief		Abstract Search Index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Aug 2014
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Search\Elastic\MassIndexer;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Patterns\Singleton;
use IPS\Settings;
use IPS\Task;
use IPS\Text\Parser;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function chr;
use function defined;
use function get_class;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Search Index
 */
abstract class Index extends Singleton
{
	/**
	 * @brief	Singleton Instances
	 */
	protected static ?Singleton $instance = NULL;
	
	/**
	 * Get instance
	 *
	 * @param bool $skipCache	Do not use the cached instance if one exists
	 * @return	static
	 */
	public static function i( bool $skipCache=FALSE ): static
	{
		if( static::$instance === NULL OR $skipCache === TRUE )
		{
			if ( Settings::i()->search_method == 'elastic' )
			{
				static::$instance = new Elastic\Index( Url::external( rtrim( Settings::i()->search_elastic_server, '/' ) . '/' . Settings::i()->search_elastic_index ) );
			}
			else
			{
				static::$instance = new Mysql\Index;
			}
		}
		
		return static::$instance;
	}
	
	/**
	 * Get mass indexer
	 *
	 * @return	static
	 */
	public static function massIndexer(): static
	{
		if ( Settings::i()->search_method == 'elastic' )
		{
			return new MassIndexer( Url::external( rtrim( Settings::i()->search_elastic_server, '/' ) . '/' . Settings::i()->search_elastic_index ) );
		}
		else
		{
			return static::i();
		}
	}
	
	/**
	 * Initalize when first setting up
	 *
	 * @return	void
	 */
	public function init(): void
	{
		// Does nothing by default
	}
	
	/**
	 * Clear and rebuild search index
	 *
	 * @return	void
	 */
	public function rebuild(): void
	{
		/* Delete everything currently in it */
		$this->prune();		
		
		/* If the queue is already running, clear it out */
		Db::i()->delete( 'core_queue', array( "`key`=?", 'RebuildSearchIndex' ) );
		
		/* And set the queue in motion to rebuild */
		foreach( SearchContent::searchableClasses() as $class )
		{
			try
			{
				Task::queue( 'core', 'RebuildSearchIndex', array( 'class' => $class ), 5, array( 'class' ) );
			}
			catch( OutOfRangeException ) {}
		}
	}
	
	/**
	 * Index a single items comments and reviews if applicable.
	 *
	 * @param	Item	$object		The item to index
	 * @return	void
	 */
	public function indexSingleItem( Item $object ): void
	{
		if( !SearchContent::isSearchable( $object ) )
		{
			return;
		}
		
		/* It is possible for some items to not have a valid URL */
		try
		{
			if( !$url = (string) $object->url() )
			{
				throw new LogicException;
			}
		}
		catch( LogicException )
		{
			$url = '';
		}

		try
		{
			$idColumn = $object::$databaseColumnId;
			if ( isset( $object::$commentClass ) AND SearchContent::isSearchable( $object::$commentClass ) )
			{
				Task::queue( 'core', 'IndexSingleItem', array( 'class' => $object::$commentClass, 'id' => $object->$idColumn, 'title' => $object->mapped('title'), 'url' => $url )  );
			}
			
			if ( isset( $object::$reviewClass ) AND SearchContent::isSearchable( $object::$reviewClass ) )
			{
				Task::queue( 'core', 'IndexSingleItem', array( 'class' => $object::$reviewClass, 'id' => $object->$idColumn, 'title' => $object->mapped('title'), 'url' => $url )  );
			}
		}
		catch( OutOfRangeException ) {}
	}
	
	/**
	 * Get index data
	 *
	 * @param	Content	$object	Item to add
	 * @return	array|NULL
	 */
	public function indexData( Content $object ): array|NULL
	{
		/* Init */
		$class = get_class( $object );
		if( !SearchContent::isSearchable( $class ) )
		{
			return NULL;
		}

		$extension = SearchContent::extension( $object );
		if( $extension === null )
		{
			return NULL;
		}
		$extension->setObject( $object );

		$idColumn = $class::$databaseColumnId;
		$tags = ( IPS::classUsesTrait( $object, 'IPS\Content\Taggable' ) and Settings::i()->tags_enabled ) ? implode( ',', array_filter( $object->tags() ) ) : NULL;
		$prefix = ( IPS::classUsesTrait( $object, 'IPS\Content\Taggable' ) and Settings::i()->tags_enabled ) ? $object->prefix() : NULL;

		/* If this is an item where the first comment is required, don't index because the comment will serve as both */
		if ( $object instanceof Item and $object::$firstCommentRequired )
		{
			return NULL;
		}

		/* Don't index if this is an item to be published in the future */
		if ( IPS::classUsesTrait( $object, 'IPS\Content\FuturePublishing' ) AND $object->isFutureDate() )
		{
			return NULL;
		}

		/* Or if this *is* the first comment, add the title and replace the tags */
		$title = $extension->searchIndexTitle();
		$isForItem = FALSE;
		if ( $object instanceof Comment )
		{
			$itemClass = $object::$itemClass;
			if ( $itemClass::$firstCommentRequired and $object->isFirst() )
			{
				try
				{
					$item = $object->item();
				}
				catch( OutOfRangeException )
				{
					/* Comment has no working item, return */
					return NULL;
				}

				$tags = ( IPS::classUsesTrait( $item, 'IPS\Content\Taggable' ) and Settings::i()->tags_enabled ) ? implode( ',', array_filter( $item->tags() ) ) : NULL;
				$prefix = ( IPS::classUsesTrait( $item, 'IPS\Content\Taggable' ) and Settings::i()->tags_enabled ) ? $item->prefix() : NULL;
				$isForItem = TRUE;
			}
		}
		
		/* Get the last updated date */
		if ( $isForItem )
		{
			$dateUpdated = $object->item()->mapped('last_comment');
			$dateCommented = $object->item()->mapped('last_comment');
		}
		else
		{
			$dateUpdated = $object->mapped('date');
			$dateCommented = $object->mapped('date');
			if ( $object instanceof Item )
			{
				foreach ( array( 'last_comment', 'last_review', 'updated' ) as $k )
				{
					if ( $val = $object->mapped( $k ) )
					{
						if ( $val > $dateUpdated )
						{
							$dateUpdated = $val;
						}
						if ( $k != 'updated' and $val > $dateCommented )
						{
							$dateCommented = $val;
						}
					}
				}
			}
		}
		
		/* Is this the latest content? */
		$isLastComment = 0;
		if ( $object instanceof Comment )
		{
			try
			{
				$item = $object->item();
			}
			catch( OutOfRangeException )
			{
				/* Comment has no parent item, return */
				return NULL;
			}
			
			$latestThing = 0;
			foreach ( array( 'updated', 'last_comment', 'last_review' ) as $k )
			{
				if ( isset( $item::$databaseColumnMap[ $k ] ) and ( $item->mapped( $k ) < time() AND $item->mapped( $k ) > $latestThing ) )
				{
					$latestThing = $item->mapped( $k );
				}
			}
			
			if ( $object->mapped('date') >= $latestThing )
			{
				$isLastComment = 1;
			}

			/* If this comment is hidden, don't actually mark as the last comment as that will cause this item to be hidden in search if we are getting only the last comment. */
			if ( $isLastComment and $object->hidden() and !$object->isFirst() )
			{
				$isLastComment = 0;
			}
			
			/* If we are re-indexing the first post of an item, which is triggered by a comment being added to a $itemClass::$firstCommentRequired,
			   then ensure that the isLastComment flag is not added as we index the comment first, which means the index_is_last_comment is incorrectly reset to 0 for the comment itself */
			if ( $isForItem and $isLastComment )
			{
				if ( isset( $item::$databaseColumnMap['num_comments'] ) and $item->mapped('num_comments') > 1 )
				{
					$isLastComment = 0;
				}
			}
		}
		else if ( $object instanceof Item and ! $object::$firstCommentRequired )
		{
			/* If this is item itself and not a comment, then we will store it as the last comment so the activity stream fetches the data correctly */
			$isLastComment = 1;
			
			if ( isset( $class::$databaseColumnMap['num_comments'] ) and $object->mapped('num_comments') )
			{
				$isLastComment = 0;
			}
			else if ( isset( $class::$databaseColumnMap['num_reviews'] ) and $object->mapped('num_reviews') )
			{
				$isLastComment = 0;
			}
			
			/* Is the item itself searchable but the comment not? */
			$commentClass = $object::$commentClass;
			if( !$commentClass OR !SearchContent::isSearchable( $commentClass ) )
			{
				/* Then make this the last comment so it remains searchable */
				$isLastComment = 1;
			}
		}
		
		/* Strip spoilers */
		$content = $extension->searchIndexContent();
		if ( preg_match( '#<div\s+?class=["\']ipsSpoiler["\']#', $content ) )
		{
			$content = Parser::removeElements( $content, array( 'div[class=ipsSpoiler]' ) );
		}
		
		/* Take the HTML out of the content */
		$content = trim( str_replace( chr(0xC2) . chr(0xA0), ' ', strip_tags( preg_replace( "/(<br(?: \/)?>|<\/p>)/i", ' ', preg_replace( "#<blockquote(?:[^>]+?)>.+?(?<!<blockquote)</blockquote>#s", " ", preg_replace( "#<script(.*?)>(.*)</script>#uis", "", ' ' . $content . ' ' ) ) ) ) ) );
	
		/* Work out the hidden status */
		if( IPS::classUsesTrait( $object, 'IPS\Content\Hideable' ) )
		{
			try
			{
				$hiddenStatus = $object->hidden();
				if ( $hiddenStatus === 0 and method_exists( $object, 'item' ) and $object->item()->hidden() )
				{
					$hiddenStatus = $isForItem ? $object->item()->hidden() : 2;
				}
				if ( $hiddenStatus !== 0 and method_exists( $object, 'item' ) and IPS::classUsesTrait( $object, 'IPS\Content\FuturePublishing' ) AND $object->isFutureDate() )
				{
					$hiddenStatus = 0;
				}
				if ( $hiddenStatus === -3 )
				{
					return NULL;
				}
			}
			catch( RuntimeException )
			{
				/* Some classes implement Hideable for other reasons, but don't actually use it. See \IPS\nexus\Package\Item */
				$hiddenStatus = 0;
			}
		}
		else
		{
			$hiddenStatus = 0;
		}
		
		/* Get the item index ID */
		$itemIndexId = NULL;
		$itemClass = NULL;
		if ( $object instanceof Comment )
		{
			$itemClass = $object::$itemClass;
			if ( $itemClass::$firstCommentRequired )
			{
				try
				{
					/* If the first comment is required and there is no first comment, this is a broken piece of content - do not try to index */
					if( !$object->item()->firstComment() )
					{
						return NULL;
					}

					$itemIndexId = $this->getIndexId( $object->item()->firstComment() );
				}
				catch ( Exception ) { }
			}
			else
			{
				try
				{
					$itemIndexId = $this->getIndexId( $object->item() );
				}
				catch ( UnderflowException )
				{
					try
					{
						/* Try and index parent */
						Index::i()->index( $object->item() );
						$itemIndexId = $this->getIndexId( $object->item() );
					}
					catch( Exception )
					{
						return NULL;
					}
				}
			}
		}
		else if ( $object instanceof Item )
		{
			if ( ! $object::$firstCommentRequired )
			{
				/* See if this has already been indexed */
				try
				{
					/* Good, we need the index_item_index_id so this is not wiped on re-index */
					$itemIndexId = $this->getIndexId( $object );
				}
				catch ( Exception ) { }
			}
		}

		/* Club */
		$clubId = NULL;
		if ( $object instanceof Item )
		{
			$container = $object->containerWrapper();
		}
		else
		{
			$container = $object->item()->containerWrapper();
		}

		if ( $container and IPS::classUsesTrait( $container, 'IPS\Content\ClubContainer' ) )
		{
			$clubId = $container->{$container::clubIdColumn()};
		}

		/* Work out the container class */
		$containerClass = ( $extension->searchIndexContainerClass() ) ? get_class( $extension->searchIndexContainerClass() ) : null;

		/* Do we have an extension to modify this? */
		foreach( Application::enabledApplications() as $app )
		{
			foreach ( $app->extensions( 'core', 'SearchIndex' ) as $searchIndexExtension )
			{
				$content = $searchIndexExtension->content( $object, $content );
			}
		}

		/* Return */
		return array(
			'index_class'				=> $class,
			'index_object_id'			=> $object->$idColumn,
			'index_item_id'				=> ( $object instanceof Item ) ? $object->$idColumn : $object->mapped('item'),
			'index_container_class'		=> $containerClass,
			'index_container_id'		=> $extension->searchIndexContainer(),
			'index_title'				=> $title,
			'index_content'				=> $content,
			'index_permissions'			=> $extension->searchIndexPermissions(),
			'index_date_created'		=> intval( $object->mapped('date') ),
			'index_date_updated'		=> intval( $dateUpdated ),
			'index_date_commented'		=> intval( $dateCommented ),
			'index_author'				=> (int) $object->mapped('author'),
			'index_tags'				=> $tags,
			'index_prefix'				=> $prefix,
			'index_hidden'				=> $hiddenStatus,
			'index_item_index_id'		=> $itemIndexId,
			'index_item_author'			=> intval( ( $object instanceof Item ) ? $object->mapped('author') : $object->item()->mapped('author') ),
			'index_is_last_comment'		=> $isLastComment,
			'index_club_id'				=> $clubId,
			'index_class_type_id_hash'	=> md5( $class . ':' . $object->$idColumn ),
			'index_is_anon'				=> (int) ( IPS::classUsesTrait( $object, 'IPS\Content\Anonymous' ) AND $object->isAnonymous() ),
			'index_item_solved'			=> ( $itemClass and IPS::classUsesTrait( $itemClass, 'IPS\Content\Solvable' ) ) ? ( $object->item()->mapped('solved_comment_id') == $object->$idColumn ) ? 1 : 0 : NULL
		);
	}

	/**
	 * Get the object ID of the first comment.
	 * Used for posts, where the index_object_id is NOT the topic ID
	 *
	 * @param Content $object
	 * @return int|null
	 */
	protected function getFirstCommentId( Content $object ) : ?int
	{
		if( $object instanceof Comment )
		{
			$itemClass = $object::$itemClass;
			if( $itemClass::$firstCommentRequired )
			{
				if ( $object->isFirst() )
				{
					$column = $object::$databaseColumnId;
					return $object->$column;
				}
				else if ( isset( $itemClass::$databaseColumnMap['first_comment_id'] ) )
				{
					$column = $itemClass::$databaseColumnMap['first_comment_id'];
					return $object->item()->$column;
				}
				else
				{
					$column = $itemClass::$databaseColumnId;
					return $object->item()->firstComment()->$column;
				}
			}
		}

		return null;
	}
	
	/**
	 * Index an item
	 *
	 * @param	Content	$object	Item to add
	 * @return	void
	 */
	abstract public function index( Content $object ): void;
	
	/**
	 * Clear out any tasks associated with the search index method
	 *
	 * @return void
	 */
	public function clearTasks(): void
	{
		// Do nothing by default
	}
	
	/**
	 * Retrieve the search ID for an item
	 *
	 * @param	Content	$object	Item to add
	 * @return	mixed
	 */
	abstract public function getIndexId( Content $object ): mixed;
	
	/**
	 * Remove item
	 *
	 * @param	Content	$object	Item to remove
	 * @return	void
	 */
	abstract public function removeFromSearchIndex( Content $object ): void;
	
	/**
	 * Removes all content for a classs
	 *
	 * @param	string		$class 	The class
	 * @param	int|NULL	$containerId		The container ID to delete, or NULL
	 * @param	int|NULL	$authorId			The author ID to delete, or NULL
	 * @return	void
	 */
	abstract public function removeClassFromSearchIndex( string $class, int|null $containerId=NULL, int|null $authorId=NULL ): void;

	/**
	 * Removes all content for a specific application from the index (for example, when uninstalling).
	 *
	 * @param	Application	$application The application
	 * @return	void
	 */
	public function removeApplicationContent( Application $application ): void
	{
		foreach ( $application->extensions( 'core', 'SearchContent' ) as $extension )
		{
			foreach( $extension::supportedClasses() as $class )
			{
				$this->removeClassFromSearchIndex( $class );
			}
		}
	}
		
	/**
	 * Mass Update (when permissions change, for example)
	 *
	 * @param	string				$class 						The class
	 * @param	int|NULL			$containerId				The container ID to update, or NULL
	 * @param	int|NULL			$itemId						The item ID to update, or NULL
	 * @param	string|NULL			$newPermissions				New permissions (if applicable)
	 * @param	int|NULL			$newHiddenStatus			New hidden status (if applicable) special value 2 can be used to indicate hidden only by parent
	 * @param	int|NULL			$newContainer				New container ID (if applicable)
	 * @param	int|NULL			$authorId					The author ID to update, or NULL
	 * @param	int|NULL			$newItemId					The new item ID (if applicable)
	 * @param	int|NULL			$newItemAuthorId			The new item author ID (if applicable)
	 * @param	bool				$addAuthorToPermissions		If true, the index_author_id will be added to $newPermissions - used when changing the permissions for a node which allows access only to author's items
	 * @return	void
	 */
	abstract public function massUpdate( string $class, int|null $containerId = NULL, int|null $itemId = NULL, string|null $newPermissions = NULL, int|null $newHiddenStatus = NULL, int|null $newContainer = NULL, int|null $authorId = NULL, int|null $newItemId = NULL, int|null $newItemAuthorId = NULL, bool $addAuthorToPermissions = FALSE ): void;
	
	/**
	 * Update data for the first and last comment after a merge
	 * Sets index_is_last_comment on the last comment, and, if this is an item where the first comment is indexed rather than the item, sets index_title and index_tags on the first comment
	 *
	 * @param	Item	$item	The item
	 * @return	void
	 */
	abstract public function rebuildAfterMerge( Item $item ): void;
	
	/**
	 * Prune search index
	 *
	 * @param	DateTime|NULL	$cutoff	The date to delete index records from, or NULL to delete all
	 * @return	void
	 */
	abstract public function prune( DateTime $cutoff = NULL ): void;
	
	/**
	 * Reset the last comment flag in any given class/index_item_id
	 *
	 * @param	array				$classes 						The class
	 * @param	int|NULL			$indexItemId				The index item ID
	 * @param	int|NULL			$ignoreId					ID to ignore because it is being removed
	 * @param 	int|null			$firstCommentId				The first comment in this item, used only if $firstCommentRequired
	 * @return 	void
	 */
	abstract public function resetLastComment( array $classes, int|null $indexItemId, int|null $ignoreId = NULL, int|null $firstCommentId=null ): void;
	
	/**
	 * Given a list of item index IDs, return the ones that a given member has participated in
	 *
	 * @param	array		$itemIndexIds	Item index IDs
	 * @param	Member	$member			The member
	 * @return 	array
	 */
	abstract public function iPostedIn( array $itemIndexIds, Member $member ): array;
	
	/**
	 * Given a list of "index_class_type_id_hash"s, return the ones that a given member has permission to view
	 *
	 * @param	array		$hashes		Hashes
	 * @param	Member	$member		The member
	 * @param	int|NULL	$limit		Number of results to return
	 * @return 	array
	 */
	abstract public function hashesWithPermission( array $hashes, Member $member, int|null $limit = NULL ): array;
	
	/**
	 * Get timestamp of oldest thing in index
	 *
	 * @return 	int|null
	 */
	abstract public function firstIndexDate(): int|NULL;
	
	/**
	 * Convert terms into stemmed terms for the highlighting JS
	 *
	 * @param	array	$terms	Terms
	 * @return	array
	 */
	public function stemmedTerms( array $terms ): array
	{
		return $terms;
	}
	
	/**
	 * Supports filtering by views?
	 *
	 * @return	bool
	 */
	public function supportViewFiltering(): bool
	{
		return TRUE;
	}
}