<?php
/**
 * @brief		Gallery Album Content Item Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		13 Mar 2017
 */

namespace IPS\gallery\Album;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Content\ContentMenuLink;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Hideable;
use IPS\Content\Item as ContentItem;
use IPS\Content\Lockable;
use IPS\Content\MetaData;
use IPS\Content\Featurable;
use IPS\Content\Reactable;
use IPS\Content\ReadMarkers;
use IPS\Content\Reportable;
use IPS\Content\Shareable;
use IPS\Content\Statistics;
use IPS\Content\ViewUpdates;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\gallery\Album;
use IPS\gallery\Application;
use IPS\gallery\Category;
use IPS\gallery\Image;
use IPS\Helpers\Menu;
use IPS\Member;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Theme;
use function array_slice;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Model
 */
class Item extends ContentItem implements Embeddable,
Filter
{
	use	Reactable,
		Reportable,
		Lockable,
		MetaData,
		Shareable,
		ReadMarkers,
		Hideable,
		Statistics,
		ViewUpdates,
		Featurable;

	/**
	 * @brief	Application
	 */
	public static string $application = 'gallery';
	
	/**
	 * @brief	Module
	 */
	public static string $module = 'gallery';
	
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'gallery_albums';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'album_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	Node Class
	 */
	public static ?string $containerNodeClass = 'IPS\gallery\Category';
	
	/**
	 * @brief	Review Class
	 */
	public static string $reviewClass = 'IPS\gallery\Album\Review';

	/**
	 * @brief	Comment Class
	 */
	public static ?string $commentClass = 'IPS\gallery\Album\Comment';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'title'					=> 'name',
		'content'				=> 'description',
		'container'				=> 'category_id',
		'num_reviews'			=> 'reviews',
		'unapproved_reviews'	=> 'reviews_unapproved',
		'hidden_reviews'		=> 'reviews_hidden',
		'num_comments'			=> 'comments',
		'unapproved_comments'	=> 'comments_unapproved',
		'hidden_comments'		=> 'comments_hidden',
		'rating_total'			=> 'rating_total',
		'rating_hits'			=> 'rating_count',
		'rating_average'		=> 'rating_aggregate',
		'rating'				=> 'rating_aggregate',
		'meta_data'				=> 'meta_data',
		'updated'				=> 'last_img_date',
		'date'					=> 'last_img_date',
		'author'				=> 'owner_id',
		'last_comment'			=> 'last_comment',
		'last_review'			=> 'last_review',
		'featured'				=> 'featured',
		'locked'				=> 'locked',
		'hidden'				=> 'hidden',
		'views'					=> 'views'
	);
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'gallery_album';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'images';

	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'gallery-album';

	/**
	 * @brief   Used for the datalayer
	 */
	public static string $contentType = 'album_item';
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public function get_title() : string
	{
		return $this->name;
	}

	/**
	 * Get album as node
	 *
	 * @return Album
	 */
	public function asNode() : Album
	{
		$data = $this->_data;

		foreach( $this->_data as $k => $v )
		{
			$data['album_' . $k ] = $v;
		}

		return Album::constructFromData( $data, FALSE );
	}

	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=gallery&module=gallery&controller=browse&album=';
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlTemplate = 'gallery_album';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'name_seo';

	/**
	 * Check if a specific action is available for this Content.
	 * Default to TRUE, but used for overrides in individual Item/Comment classes.
	 *
	 * @param string $action
	 * @param Member|null	$member
	 * @return bool
	 */
	public function actionEnabled( string $action, ?Member $member=null ) : bool
	{
		switch( $action )
		{
			case 'comment':
				if( !$this->use_comments )
				{
					return false;
				}
				break;

			case 'review':
				if( !$this->use_reviews )
				{
					return false;
				}
				break;
		}

		return parent::actionEnabled( $action, $member );
	}

	/**
	 * Supported Meta Data Types
	 *
	 * @return	array
	 */
	public static function supportedMetaDataTypes(): array
	{
		return array( 'core_ContentMessages' );
	}

	/**
	 * Get image for embed
	 *
	 * @return	File|NULL
	 */
	public function embedImage(): ?File
	{
		if( ( $this->cover_img_id or $this->last_img_id ) )
		{
			$image = Image::load( $this->cover_img_id ?: $this->last_img_id );
			if ( $image->small_file_name )
			{
				return File::get( 'gallery_Images', $image->small_file_name );
			}
		else
		{
			return null;
		}
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get preview image for share services
	 *
	 * @return	string
	 */
	public function shareImage(): string
	{
		if( ( $this->cover_img_id or $this->last_img_id ) )
		{
			$image = Image::load( $this->cover_img_id ?: $this->last_img_id );
			if ( $image->masked_file_name )
			{
				return (string)File::get( 'gallery_Images', $image->masked_file_name )->url;
			}
			else
			{
				return '';
			}
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get items with permission check
	 *
	 * @param array $where Where clause
	 * @param string|null $order MySQL ORDER BY clause (NULL to order by date)
	 * @param int|array|null $limit Limit clause
	 * @param string $permissionKey A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param int|bool|null $includeHiddenItems Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param int $queryFlags Select bitwise flags
	 * @param Member|null $member The member (NULL to use currently logged in member)
	 * @param bool $joinContainer If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinComments If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinReviews If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param bool $countOnly If true will return the count
	 * @param array|null $joins Additional arbitrary joins for the query
	 * @param bool|Model $skipPermission If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param bool $joinTags If true, will join the tags table
	 * @param bool $joinAuthor If true, will join the members table for the author
	 * @param bool $joinLastCommenter If true, will join the members table for the last commenter
	 * @param bool $showMovedLinks If true, moved item links are included in the results
	 * @param array|null $location Array of item lat and long
	 * @return    ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( array $where=array(), string $order=NULL, int|array|null $limit=10, string $permissionKey='read', int|bool|null $includeHiddenItems= Filter::FILTER_AUTOMATIC, int $queryFlags=0, Member $member=NULL, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins=NULL, bool|Model $skipPermission=FALSE, bool $joinTags=TRUE, bool $joinAuthor=TRUE, bool $joinLastCommenter=TRUE, bool $showMovedLinks=FALSE, array|null $location=null ): ActiveRecordIterator|int
	{
		if( $permissionKey == 'add' )
		{
			$where[] = static::submitRestrictionWhere( $member, Category::load( $where[0][1] ) );
		}

		$where[] = static::getItemsWithPermissionWhere( $where, $member, $joins );

		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );
	}

	/**
	 * Additional WHERE clauses for finding albums the user can submit to
	 *
	 * @param	Member|NULL		$member		Member to check
	 * @param	Category	$category	Category we are submitted in
	 * @return	string
	 */
	public static function submitRestrictionWhere( ?Member $member, Category $category ) : string
	{
		$member	= $member ?: Member::loggedIn();

		/* Guests can't create albums so we can skip all the member specific stuff */
		if( !$member->member_id )
		{
			return '(album_submit_type=' . Album::AUTH_SUBMIT_PUBLIC . ')';
		}
		else
		{
			/* For starters, allow us to submit to public albums and our own albums */
			$wheres	= array(
				'(album_submit_type=' . Album::AUTH_SUBMIT_OWNER . ' and album_owner_id=' . $member->member_id . ')',
				'(album_submit_type=' . Album::AUTH_SUBMIT_PUBLIC . ')'
			);

			/* Now allow us to submit to albums that allow group submissions */
			$wheres[] = '(album_submit_type=' . Album::AUTH_SUBMIT_GROUPS . ' AND ' . Db::i()->findInSet( 'album_submit_access', $member->groups ) . ')';

			/* And where we as an individual member are allowed to submit */
			$wheres[] = '(album_submit_type=' . Album::AUTH_SUBMIT_MEMBERS . ' AND (' . Db::i()->findInSet( 'album_submit_access', $member->socialGroups() ) . ' OR album_owner_id=' . $member->member_id . ' ) )';

			/* And finally, if we're in a club and we allow anyone in the club to submit, handle that */
			if( $category->club() AND in_array( $category->club()->id, $member->clubs() ) )
			{
				$wheres[] = '(album_submit_type=' . Album::AUTH_SUBMIT_CLUB . ')';
			}

			return '(album_owner_id=' . $member->member_id . ' OR ' . implode( ' OR ', $wheres ) . ')';
		}
	}

	/**
	 * @brief	Cached groups the member can access
	 */
	protected static array $_availableGroups	= array();

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();

	/**
	 * WHERE clause for getItemsWithPermission
	 *
	 * @param array $where Current WHERE clause
	 * @param Member|null $member The member (NULL to use currently logged in member)
	 * @param array|null $joins Additional joins
	 * @return    array
	 */
	public static function getItemsWithPermissionWhere( array $where, ?Member $member, ?array &$joins ) : array
	{
		/* We need to make sure we can access the album */
		$restricted	= array( 0 );
		$member		= $member ?: Member::loggedIn();

		if( isset( static::$_availableGroups[ $member->member_id ] ) )
		{
			$restricted	= static::$_availableGroups[ $member->member_id ];
		}
		else
		{
			if( $member->member_id )
			{
				foreach( Db::i()->select( '*', 'core_sys_social_group_members', array( 'member_id=?', $member->member_id ) ) as $group )
				{
					$restricted[]	= $group['group_id'];
				}
			}

			static::$_availableGroups[ $member->member_id ]	= $restricted;
		}

		/* If you can edit images in a category you can see private albums in that category. We can only really check globally at this stage, however. */
		if( Image::modPermission( 'edit', $member ) )
		{
			return array( "( gallery_albums.album_type IN(1,2) OR ( gallery_albums.album_type=3 AND ( gallery_albums.album_owner_id=? OR gallery_albums.album_allowed_access IN (" . implode( ',', $restricted ) . ") ) ) )", $member->member_id );
		}
		else
		{
			return array( "( gallery_albums.album_type=1 OR ( gallery_albums.album_type=2 AND gallery_albums.album_owner_id=? ) OR ( gallery_albums.album_type=3 AND ( gallery_albums.album_owner_id=? OR gallery_albums.album_allowed_access IN (" . implode( ',', $restricted ) . ") ) ) )", $member->member_id, $member->member_id );
		}
	}

	/**
	 * Additional WHERE clauses for Follow view
	 *
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	array		$joins				Other joins
	 * @return	array
	 */
	public static function followWhere( bool &$joinContainer, array &$joins ): array
	{
		return array_merge( parent::followWhere( $joinContainer, $joins ), array( static::getItemsWithPermissionWhere( array(), Member::loggedIn(), $joins ) ) );
	}

	/**
	 * Move
	 *
	 * @param	Model	$container	Container to move to
	 * @param bool $keepLink	If TRUE, will keep a link in the source
	 * @return	void
	 * @note	We need to update the image category references too
	 */
	public function move( Model $container, bool $keepLink=FALSE ): void
	{
		$this->asNode()->moveTo( $container, $this->container() );
	}

	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns(): array
	{
		$return = parent::basicDataColumns();
		$return[] = 'album_last_x_images';
		$return[] = 'album_cover_img_id';

		return $return;
	}

	/**
	 * Query to get additional data for search result / stream view
	 *
	 * @param	array	$items	Item data (will be an array containing values from basicDataColumns())
	 * @return	array
	 */
	public static function searchResultExtraData( array $items ): array
	{
		$imageIds = array();
		foreach ( $items as $itemData )
		{
			if ( $itemData['album_cover_img_id'] )
			{
				$imageIds[] = $itemData['album_cover_img_id'];
			}

			if ( $itemData['album_last_x_images'] )
			{
				$latestImages = json_decode( $itemData['album_last_x_images'], true );

				foreach( $latestImages as $imageId )
				{
					$imageIds[] = $imageId;
				}
			}
		}
		
		if ( count( $imageIds ) )
		{
			if( Dispatcher::hasInstance() )
			{
				Application::outputCss();
			}

			$return = array();
			
			foreach ( Image::getItemsWithPermission( array( array( 'image_id IN(' . implode( ',', $imageIds ) . ')' ) ), NULL, NULL ) as $image )
			{
				if( isset( $return[ $image->album_id] ) AND count( $return[ $image->album_id ] ) > 19 )
				{
					continue;
				}

				$return[ $image->album_id ][] = $image;
			}
			
			return $return;
		}
		
		return array();
	}

	/**
	 * Are comments supported by this class?
	 *
	 * @param	Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsComments( Member $member = NULL, Model $container = NULL ): bool
	{
		if( $container !== NULL )
		{
			return parent::supportsComments() and $container->allow_comments AND ( !$member or $container->can( 'read', $member ) );
		}
		else
		{
			return parent::supportsComments() and ( !$member or Category::countWhere( 'read', $member, array( 'category_allow_comments=1' ) ) );
		}
	}

	/**
	 * Are reviews supported by this class?
	 *
	 * @param	Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsReviews( Member $member = NULL, Model $container = NULL ): bool
	{
		if( $container !== NULL )
		{
			return parent::supportsReviews() and $container->allow_reviews AND ( !$member or $container->can( 'read', $member ) );
		}
		else
		{
			return parent::supportsReviews() and ( !$member or Category::countWhere( 'read', $member, array( 'category_allow_reviews=1' ) ) );
		}
	}

	/**
	 * Get template for content tables
	 *
	 * @return	array
	 */
	public static function contentTableTemplate(): array
	{
		Application::outputCss();
		
		return array( Theme::i()->getTemplate( 'browse', 'gallery', 'front' ), 'albums' );
	}

	/**
	 * Get available comment/review tabs
	 *
	 * @return	array
	 */
	public function commentReviewTabs(): array
	{
		$tabs = array();

		if ( $this->container()->allow_reviews AND $this->use_reviews )
		{
			$tabs['reviews'] = Member::loggedIn()->language()->addToStack( 'image_review_count', TRUE, array( 'pluralize' => array( $this->mapped('num_reviews') ) ) );
		}
		if ( $this->container()->allow_comments AND $this->use_comments )
		{
			$tabs['comments'] = Member::loggedIn()->language()->addToStack( 'image_comment_count', TRUE, array( 'pluralize' => array( $this->mapped('num_comments') ) ) );
		}

		return $tabs;
	}

	/**
	 * Get comment/review output
	 *
	 * @param string|null $tab Active tab
	 * @return    string
	 */
	public function commentReviews( string $tab=NULL ): string
	{
		if ( $tab === 'reviews' AND $this->container()->allow_reviews AND $this->use_reviews )
		{
			return (string) Theme::i()->getTemplate('browse')->albumReviews( $this );
		}
		elseif( $tab === 'comments' AND $this->container()->allow_comments AND $this->use_comments )
		{
			return (string) Theme::i()->getTemplate('browse')->albumComments( $this );
		}

		return '';
	}

	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		return 'album_id';
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'gallery', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'gallery' )->embedAlbums( $this, $this->asNode(), $this->url()->setQueryString( $params ) );
	}

	/**
	 * Get the container item class to use for mod permission checks
	 *
	 * @return	string|NULL
	 * @note	By default we will return NULL and the container check will execute against Node::$contentItemClass, however
	 *	in some situations we may need to override this (i.e. for Gallery Albums)
	 */
	protected static function getContainerModPermissionClass(): ?string
	{
		return 'IPS\gallery\Album\Item';
	}

	/**
	 * Returns the content images
	 *
	 * @param	int|null	$limit				Number of attachments to fetch, or NULL for all
	 * @param	bool		$ignorePermissions	If set to TRUE, permission to view the images will not be checked
	 * @return	array|NULL
	 * @throws	BadMethodCallException
	 */
	public function contentImages( int $limit = NULL, bool $ignorePermissions = FALSE ): array|null
	{
		$images = array();
		
		foreach( Image::getItemsWithPermission( array( array( 'image_album_id=?', $this->id ) ), NULL, $limit ?: 10, $ignorePermissions ? NULL : 'read' ) as $image )
		{
			$images[] = array( 'gallery_Images' => $image->masked_file_name );
		}
		
		return count( $images ) ? array_slice( $images, 0, $limit ) : NULL;
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();

		/* Recount category info */
		$this->container()->resetCommentCounts();
		$this->container()->save();
	}

	/**
	 * Get the last modification date for the sitemap
	 *
	 * @return DateTime|null		Timestamp of the last modification time for the sitemap
	 */
	public function lastModificationDate(): DateTime|NULL
	{
		/* Returns the last last comment date */
		$lastMod = parent::lastModificationDate();

		if( !$lastMod AND $this->last_img_date OR ( $lastMod AND $this->last_img_date AND $this->last_img_date > $lastMod->getTimestamp() ) )
		{
			$lastMod = DateTime::ts( $this->last_img_date );
		}

		return $lastMod;
	}

	/**
	 * Store club node IDs so we can save on queries
	 * @var array|null
	 */
	protected static ?array $_clubNodeIds = null;

	/**
	 * Return query WHERE clause to use for getItemsWithPermission when excluding club content
	 *
	 * @return array
	 */
	public static function clubAlbumExclusion(): array
	{
		if( ! Settings::i()->club_nodes_in_apps )
		{
			if( static::$_clubNodeIds === null )
			{
				static::$_clubNodeIds = iterator_to_array(
					Db::i()->select( 'node_id', 'core_clubs_node_map', array( 'node_class=?', 'IPS\gallery\Category' ) )
				);
			}

			if( count( static::$_clubNodeIds ) )
			{
				return array(
					array( Db::i()->in( 'gallery_albums.album_category_id', static::$_clubNodeIds, true ) )
				);
			}
		}

		return array();
	}

	/**
	 * Build the moderation menu links
	 *
	 * @param Member|null $member
	 * @return Menu
	 */
	public function menu( Member $member = null ): Menu
	{
		$member = $member ?: Member::loggedIn();
		$menu = parent::menu( $member );

		if ( $this->canEdit( $member ) )
		{
			$editLink = new ContentMenuLink( url: $this->url()->setQueryString( array( 'do' => 'editAlbum' ) ), languageString: Member::loggedIn()->language()->addToStack( 'edit_album'), id: 'edit_album' );
			$editLink->opensDialog( 'edit_album' );
			$menu->add( $editLink )->moveToStart( $editLink );
		}

		if ( isset( $menu->elements['delete'] ) )
		{
			$menu->elements['delete']->url = $this->url()->csrf()->setQueryString( array( 'do' => 'deleteAlbum' ) );
			$menu->elements['delete']->title = Member::loggedIn()->language()->addToStack( 'delete_album' );
			$menu->elements['delete']->dataAttributes = [
				"data-ipsDialog"       => "true",
				"data-ipsDialog-title" => Member::loggedIn()->language()->addToStack( 'delete_album' )
			];
		}
		return $menu;
	}
}