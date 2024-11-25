<?php
/**
 * @brief		Entry Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		3 Mar 2014
 */

namespace IPS\blog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Application;
use IPS\blog\Entry\Category;
use IPS\Content;
use IPS\Content\Anonymous;
use IPS\Content\Comment;
use IPS\Content\EditHistory;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Followable;
use IPS\Content\FuturePublishing;
use IPS\Content\Hideable;
use IPS\Content\Item;
use IPS\Content\Lockable;
use IPS\Content\MetaData;
use IPS\Content\Featurable;
use IPS\Content\Pinnable;
use IPS\Content\Polls;
use IPS\Content\Ratings;
use IPS\Content\Reactable;
use IPS\Content\ReadMarkers;
use IPS\Content\Reportable;
use IPS\Content\Shareable;
use IPS\Content\Statistics;
use IPS\Content\Taggable;
use IPS\Content\ViewUpdates;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\gallery\Album;
use IPS\Helpers\Badge;
use IPS\Helpers\Badge\Icon;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Login;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Poll;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
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
 * Entry Model
 */
class Entry extends Item implements
	Embeddable,
	Filter
{
	use Reactable,
		Reportable,
		Pinnable,
		Anonymous,
		Followable,
		FuturePublishing,
		Lockable,
		MetaData,
		Polls,
		Ratings,
		Shareable,
		Taggable,
		EditHistory,
		ReadMarkers,
		Statistics,
		Hideable,
		ViewUpdates,
		Featurable
		{
			FuturePublishing::onPublish as public _onPublish;
			FuturePublishing::onUnpublish as public _onUnpublish;
		}
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	Application
	 */
	public static string $application = 'blog';
	
	/**
	 * @brief	Module
	 */
	public static string $module = 'blogs';
	
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'blog_entries';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'entry_';
		
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'author'				=> 'author_id',
		'author_name'			=> 'author_name',
		'content'				=> 'content',
		'container'				=> 'blog_id',
		'date'					=> 'date',
		'updated'				=> 'last_update',
		'title'					=> 'name',
		'num_comments'			=> 'num_comments',
		'unapproved_comments'	=> 'queued_comments',
		'hidden_comments'		=> 'hidden_comments',
		'last_comment_by'		=> 'last_comment_mid',
		'last_comment'			=> 'last_update',	// Same as updated above
		'views'					=> 'views',
		'approved'				=> 'hidden',
		'pinned'				=> 'pinned',
		'poll'					=> 'poll_state',
		'featured'				=> 'featured',
		'ip_address'			=> 'ip_address',
		'locked'				=> 'locked',
		'cover_photo'			=> 'cover_photo',
		'cover_photo_offset'	=> 'cover_offset',
		'is_future_entry'		=> 'is_future_entry',
        'future_date'           => 'publish_date',
		'status'				=> 'status',
        'meta_data'				=> 'meta_data',
		'edit_time'				=> 'edit_time',
		'edit_member_name'    	=> 'edit_name',
		'edit_show'				=> 'append_edit',
		'edit_reason'			=> 'edit_reason',
		'is_anon'				=> 'is_anon',
		'last_comment_anon'		=> 'last_poster_anon',
	);
	
	/**
	 * @brief	Title
	*/
	public static string $title = 'blog_entry';
	
	/**
	 * @brief	Node Class
	 */
	public static ?string $containerNodeClass = 'IPS\blog\Blog';
	
	/**
	 * @brief	[Content\Item]	Comment Class
	 */
	public static ?string $commentClass = 'IPS\blog\Entry\Comment';
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static bool $firstCommentRequired = FALSE;
	
	/**
	 * @brief	[Content\Comment]	Language prefix for forms
	 */
	public static string $formLangPrefix = 'blog_entry_';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'pen-to-square';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
			'view' 				=> 'view',
			'read'				=> 2,
			'add'				=> 3,
			'reply'				=> 4,
	);
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'blog-entry';
	
	/**
	 * @brief	[CoverPhoto]	Storage extension
	 */
	public static string $coverPhotoStorageExtension = 'blog_Entries';
	
	/**
	 * @brief	Use a default cover photo
	 */
	public static bool $coverPhotoDefault = true;
	
	/**
	 * Set the title
	 *
	 * @param	string	$name	Title
	 * @return	void
	 */
	public function set_name( string $name ) : void
	{
		$this->_data['name'] = $name;
		$this->_data['name_seo'] = Friendly::seoTitle( $name );
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_name_seo(): string
	{
		if( !$this->_data['name_seo'] )
		{
			$this->name_seo	= Friendly::seoTitle( $this->name );
			$this->save();
		}

		return $this->_data['name_seo'] ?: Friendly::seoTitle( $this->name );
	}

	/**
	 * Get the album HTML, if there is one associated
	 *
	 * @return	string
	 */
	public function get__album(): string
	{
		if( Application::appIsEnabled( 'gallery' ) AND $this->gallery_album )
		{
			try
			{
				$album = Album::loadAndCheckPerms( $this->gallery_album );
	
				$gallery = Application::load( 'gallery' );
				$gallery::outputCss();
	
				return (string) Theme::i()->getTemplate( 'browse', 'gallery', 'front' )->miniAlbum( $album );
			}
			catch( OutOfRangeException | UnderflowException ){}
		}
	
		return '';
	}
	
	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=blog&module=blogs&controller=entry&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlTemplate = 'blog_entry';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'name_seo';

	/**
	 * @brief	Category|null
	 */
	protected ?Category $category = null;
	
	/**
	 * Can view this entry
	 *
	 * @param	Member|NULL	$member		The member or NULL for currently logged in member.
	 * @return	bool
	 */
	public function canView( Member $member = null ): bool
	{
		$member = $member ?: Member::loggedIn();

		$return = parent::canView( $member );

		if ( $this->status == 'draft' AND !static::canViewHiddenItems( $member, $this->container() ) AND !in_array( $this->container()->id, array_keys( Blog::loadByOwner( $member ) ) ) )
		{
			$return = FALSE;
			if ( ( $club = $this->container()->club() AND in_array( $club->memberStatus( Member::loggedIn() ), array( Club::STATUS_LEADER, Club::STATUS_MODERATOR ) ) ) )
			{
				$return = TRUE;
			}
		}
		
		/* Is this a future publish entry and we are the owner of the blog? */
		if ( $this->status == 'draft' AND $this->is_future_entry == 1 AND in_array( $this->container()->id, array_keys( Blog::loadByOwner( $member ) ) ) )
		{
			$return = TRUE;
		}
		
		/* Club blog */
		if ( $club = $this->container()->club() )
		{
			if ( !$club->canRead( $member ) )
			{
				return FALSE;
			}
		}

		/* Private blog */
		if( $this->container()->social_group != 0 AND $this->container()->owner()->member_id != $member->member_id )
		{
			/* This will throw an exception of the row does not exist */
			try
			{
				if( !$member->member_id )
				{
					return FALSE;
				}

				$member	= Db::i()->select( '*', 'core_sys_social_group_members', array( 'group_id=? AND member_id=?', $this->container()->social_group, $member->member_id ) )->first();
			}
			catch( UnderflowException )
			{
				return FALSE;
			}
		}
		
		return $return;
	}

	/**
	 * Unclaim attachments
	 *
	 * @return	void
	 */
	protected function unclaimAttachments(): void
	{
		File::unclaimAttachments( 'blog_Entries', $this->id );
	}
	
	/**
	 * Get items with permisison check
	 *
	 * @param array $where				Where clause
	 * @param string|null $order				MySQL ORDER BY clause (NULL to order by date)
	 * @param int|array|null $limit				Limit clause
	 * @param string $permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param int|bool|null $includeHiddenItems	Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param int $queryFlags			Select bitwise flags
	 * @param	Member|null	$member				The member (NULL to use currently logged in member)
	 * @param bool $joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param bool $countOnly			If true will return the count
	 * @param array|null $joins				Additional arbitrary joins for the query
	 * @param bool|Model $skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param bool $joinTags			If true, will join the tags table
	 * @param bool $joinAuthor			If true, will join the members table for the author
	 * @param bool $joinLastCommenter	If true, will join the members table for the last commenter
	 * @param bool $showMovedLinks		If true, moved item links are included in the results
	 * @param array|null $location			Array of item lat and long
	 * @return	ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( array $where=array(), string $order=null, int|array|null $limit=10, string $permissionKey='read', int|bool|null $includeHiddenItems= Filter::FILTER_AUTOMATIC, int $queryFlags=0, Member $member=null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins=null, bool|Model $skipPermission=FALSE, bool $joinTags=TRUE, bool $joinAuthor=TRUE, bool $joinLastCommenter=TRUE, bool $showMovedLinks=FALSE, array|null $location=null ): ActiveRecordIterator|int
	{
		if ( in_array( $permissionKey, array( 'view', 'read' ) ) )
		{
			$joinContainer = TRUE;
						
			$member = $member ?: Member::loggedIn();
            if ( $member->member_id )
            {
                $where[] = array( '( blog_blogs.blog_member_id=' . $member->member_id . ' OR ( ' . Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', $member ) . ' ) OR blog_blogs.blog_social_group IS NULL )' );
            }
            else
            {
                $where[] = array( "(" . Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', $member ) . " OR blog_blogs.blog_social_group IS NULL )" );
            }
            
			if ( Settings::i()->clubs )
			{
				$joins[] = array( 'from' => 'core_clubs', 'where' => 'core_clubs.id=blog_blogs.blog_club_id' );
				if ( $member->member_id )
				{
					if ( !$member->modPermission( 'can_access_all_clubs' ) )
					{
						$where[] = array( '( blog_blogs.blog_club_id IS NULL OR ' . Db::i()->in( 'blog_blogs.blog_club_id', $member->clubs() ) . ' OR core_clubs.type=? OR core_clubs.type=?  OR core_clubs.type=?)', Club::TYPE_PUBLIC, Club::TYPE_READONLY, Club::TYPE_OPEN );
					}
				}
				else
				{
					$where[] = array( '( blog_blogs.blog_club_id IS NULL OR core_clubs.type=? OR core_clubs.type=? OR core_clubs.type=? )', Club::TYPE_PUBLIC, Club::TYPE_READONLY, Club::TYPE_OPEN );
				}
			}
		}
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );
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
		$joinContainer = TRUE;
		if ( Member::loggedIn()->member_id )
		{
			$where = array( array( '( blog_blogs.blog_social_group IS NULL OR blog_blogs.blog_member_id=' . Member::loggedIn()->member_id . ' OR ( ' . Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', Member::loggedIn() ) . ' ) )' ) );
		}
		else
		{
			$where = array( Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', Member::loggedIn() ) );
		}
		
		if ( Settings::i()->clubs )
		{
			$joins[] = array( 'from' => 'core_clubs', 'where' => 'core_clubs.id=blog_blogs.blog_club_id' );
			if ( Member::loggedIn()->member_id )
            {
				$where[] = array( '( blog_blogs.blog_club_id IS NULL OR ' . Db::i()->in( 'blog_blogs.blog_club_id', Member::loggedIn()->clubs() ) . ' OR core_clubs.type=? OR core_clubs.type=? )', Club::TYPE_PUBLIC, Club::TYPE_READONLY );
            }
            else
            {
				$where[] = array( '( blog_blogs.blog_club_id IS NULL OR core_clubs.type=? OR core_clubs.type=? )', Club::TYPE_PUBLIC, Club::TYPE_READONLY );
            }
		}

		return array_merge( parent::followWhere( $joinContainer, $joins ), $where );
	}
	
	/**
	 * Get elements for add/edit form
	 *
	 * @param	Item|null	$item		The current item if editing or NULL if creating
	 * @param	Model|null						$container	Container (e.g. forum) ID, if appropriate
	 * @return	array
	 */
	public static function formElements( Item $item=NULL, Model $container=NULL ): array
	{
		$return = parent::formElements( $item, $container );
		$return['entry'] = new Editor( 'blog_entry_content', $item?->content, TRUE, array( 'app' => 'blog', 'key' => 'Entries', 'autoSaveKey' => ( $item === NULL ) ? 'blog-entry-' . $container?->id : 'blog-edit-' . $item->id, 'attachIds' => ( $item === NULL ? NULL : array( $item->id ) ) ) );

		/* Edit Log Fields need to be under the editor */
		if( isset( $return['edit_reason']) )
		{
			$editReason = $return['edit_reason'];
			unset( $return['edit_reason'] );
			$return['edit_reason'] = $editReason;
		}

		if( isset( $return['log_edit']) )
		{
			$logEdit = $return['log_edit'];
			unset( $return['log_edit'] );
			$return['log_edit'] = $logEdit;
		}

		/* Gallery album association */
		if( Application::appIsEnabled( 'gallery' ) )
		{
			$return['album']	= new Node( 'entry_gallery_album', ( $item AND $item->gallery_album ) ? $item->gallery_album : NULL, FALSE, array(
					'url'					=> Url::internal( 'app=blog&module=blogs&controller=submit', 'front', 'blog_submit' ),
					'class'					=> 'IPS\gallery\Album',
					'permissionCheck'		=> 'add',
			) );
		}

		if( $container )
		{
			$categories = Category::roots( NULL, NULL, array( 'entry_category_blog_id=?', $container->id ) );
			$choiceOptions = array( 0 => 'entry_category_choice_new' );
			$choiceToggles = array( 0 => array( 'blog_entry_new_category' ) );

			if( count( $categories ) )
			{
				$choiceOptions[1] = 'entry_category_choice_existing';
				$choiceToggles[1] = array( 'entry_category_id' );
			}

			$return['entry_category_choice'] = new Radio( 'entry_category_choice', ( ( $item AND $item->category_id ) or Request::i()->cat ) ? 1 : 0, FALSE, array(
				'options' => $choiceOptions,
				'toggles' => $choiceToggles
			) );

			if( count( $categories ) )
			{
				$options = array();
				foreach ( $categories as $category )
				{
					$options[ $category->id ] = $category->name;
				}

				$return[ 'entry_category_id' ] = new Select( 'entry_category_id', ( $item AND $item->category_id ) ? $item->category_id : ( Request::i()->cat ? Request::i()->cat : NULL ), FALSE, array( 'options' => $options, 'parse' => 'normal' ), NULL, NULL, NULL, "entry_category_id" );
			}
		}

		$return['blog_entry_new_category']	= new Text( 'blog_entry_new_category', NULL, TRUE, array(), NULL, NULL, NULL, "blog_entry_new_category" );

		$return['image'] = new Upload( 'blog_entry_cover_photo', ( ( $item AND $item->cover_photo ) ? File::get( 'blog_Entries', $item->cover_photo ) : NULL ), FALSE, array( 'storageExtension' => 'blog_Entries', 'allowStockPhotos' => TRUE, 'image' => array( 'maxWidth' => 4800, 'maxHeight' => 4800 ), 'canBeModerated' => TRUE ) );
		
		$return['publish'] = new YesNo( 'blog_entry_publish', $item ? $item->status : TRUE, FALSE, array( 'togglesOn' => array( 'blog_entry_date' ) ) );
		
		/* Publish date needs to go near the bottom */
		if ( isset( $return['date'] ) )
		{
			$date = $return['date'];
			unset( $return['date'] );
			
			$return['date'] = $date;
		}
		
		/* Poll always needs to go on the end */
		if ( isset( $return['poll'] ) )
		{
			$poll = $return['poll'];
			unset( $return['poll'] );
			
			$return['poll'] = $poll;
		}

		
		return $return;
	}
	
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( array $values ): void
	{
		$new = $this->_new;

		parent::processForm( $values );
		
		if ( !$new )
		{
			$oldContent = $this->content;
		}
		$this->content	= $values['blog_entry_content'];
		
		$sendFilterNotifications = $this->checkProfanityFilters( FALSE, !$new, NULL, NULL, 'blog_Entries', $new ? ['blog-entry-' . $this->container()->id] : NULL, $values['blog_entry_cover_photo'] ? [ $values['blog_entry_cover_photo'] ] : [] );
		
		if ( !$new AND $sendFilterNotifications === FALSE )
		{
			$this->sendAfterEditNotifications( $oldContent );
		}
		
		$this->status = $values['blog_entry_publish'] ? 'published' : 'draft';
		
		if ( isset( $values['blog_entry_date'] ) )
		{
			$this->date = ( $values['blog_entry_date'] AND $values['blog_entry_publish'] ) ? $values['blog_entry_date']->getTimestamp() : time();
		}

		$this->cover_photo = (string) $values['blog_entry_cover_photo'];
		
		/* Gallery album association */
		if( Application::appIsEnabled( 'gallery' ) AND $values['entry_gallery_album'] instanceof Album )
		{
			$this->gallery_album = $values['entry_gallery_album']->_id;
		}
		else
		{
			$this->gallery_album = NULL;
		}
		
		if ( $this->date > time() )
		{
			$this->status = 'draft';
			$this->publish_date = $this->date;
		}

		if( $values['entry_category_choice'] == 1 and $values['entry_category_id'] )
		{
			$this->category_id = $values['entry_category_id'];
		}
		else
		{
			$newCategory = new Category;
			$newCategory->name = $values['blog_entry_new_category'];
			$newCategory->seo_name = Friendly::seoTitle( $values['blog_entry_new_category'] );

			$newCategory->blog_id = $this->blog_id;
			$newCategory->save();

			$this->category_id = $newCategory->id;
		}
		
		/* Ping */
		$this->container()->ping();
	}
	
	/**
	 * Can a given member create this type of content?
	 *
	 * @param	Member	$member		The member
	 * @param	Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param bool $showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( Member $member, Model $container=null, bool $showError=FALSE ): bool
	{
		parent::canCreate( $member, $container, $showError );
		
		if ( $member->member_id AND $member->checkPostsPerDay() === FALSE )
		{
			if ( $showError )
			{
				Output::i()->error( 'posts_per_day_error', '1B203/2', 403, '' );
			}
			else
			{
				return FALSE;
			}
		}
		
		$return = TRUE;

		$blogs = Blog::loadByOwner( $member );

		if ( $container )
		{
			if ( $club = $container->club() )
			{
				$return = $club->isModerator( $member );
				$error = 'no_module_permission';
			}
			elseif ( !in_array( $container->id, array_keys( $blogs ) ) )
			{
				$return = FALSE;
				$error = 'no_module_permission';
			}
			
			if ( $container->disabled )
			{
				$return = FALSE;
				$error = 'no_module_permission';
			}
		}
		else
		{
			if( !count( $blogs ) )
			{
				$return = FALSE;
				$error = 'no_module_permission';
			}
		}
				
		/* Return */
		if ( $showError and !$return )
		{
			Output::i()->error( $error, '1B203/1', 403, '' );
		}
		
		return $return;
	}
	
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param	Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( ?Comment $comment, array $values ): void
	{
		parent::processAfterCreate( $comment, $values );

		File::claimAttachments( 'blog-entry-' . $this->container()->id, $this->id );

		if ( $this->status == 'published' )
		{

			/* @var Blog $blog */
			$blog						= $this->container();

			/* @var array $databaseColumnMap */
			$lastUpdateColumn			= $blog::$databaseColumnMap['date'];
			$blog->$lastUpdateColumn	= time();
			$blog->save();
		}
	}

	/**
	 * Syncing to run when publishing something previously pending publishing
	 *
	 * @param	Member|null|bool	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onPublish( Member|bool|null $member ) : void
	{
		$this->status = 'published';
		$this->save();
		
		$this->_onPublish( $member );
		
		/* The blog system is slightly different from the \Content future entry stuff. Future entries are treated as drafts,
			so do count towards entries but parent::onPublish will try and increment item count again after publish */
		$this->container()->resetCommentCounts();
		$this->container()->save();
	}
	
	/**
	 * Syncing to run when unpublishing an item (making it a future dated entry when it was already published)
	 *
	 * @param	Member|bool|null	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onUnpublish( Member|bool|null $member ) : void
	{
		$this->status = 'draft';
		$this->save();
		
		$this->_onUnpublish( $member );
		
		/* The blog system is slightly different from the \Content future entry stuff. Future entries are treated as drafts,
			so do count towards entries but parent::onUnpublish will try and decrement item count after unpublish */
		$this->container()->resetCommentCounts();
		$this->container()->save();
	}

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
		$member = $member ?: Member::loggedIn();
		switch( $action )
		{
			case 'comment':
			case 'reply':
				if ( $member->checkPostsPerDay() === FALSE )
				{
					return FALSE;
				}
				break;

			case 'featureComment':
			case 'unfeatureComment':
				if ( $member->member_id AND $member->member_id === $this->author()->member_id AND $member->group['g_blog_allowownmod'] )
				{
					return TRUE;
				}
				break;
		}

		return parent::actionEnabled( $action, $member );
	}
	
	/**
	 * Can comment?
	 *
	 * @param	Member|NULL	$member							The member (NULL for currently logged in member)
	 * @param	bool				$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 */
	public function canComment( Member $member=NULL, bool $considerPostBeforeRegistering = TRUE ): bool
	{
		$member = $member ?: Member::loggedIn();

		$response = parent::canComment( $member, $considerPostBeforeRegistering );
		if( $response )
		{
			if ( !$member->member_id and $considerPostBeforeRegistering and Settings::i()->post_before_registering and Login::registrationType() != 'disabled' )
			{
				return (bool) Group::load( Settings::i()->member_group )->g_blog_allowcomment;
			}
		}
		
		return $response;
	}
	
	/**
	 * Check Moderator Permission
	 *
	 * @param	string						$type		'edit', 'hide', 'unhide', 'delete', etc.
	 * @param	Member|NULL			$member		The member to check for or NULL for the currently logged in member
	 * @param	Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function modPermission( string $type, ?Member $member = NULL, Model $container = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		if( $type == 'future_publish' AND $member->member_id > 0 )
		{
			return TRUE;
		}

		$result = parent::modPermission( $type, $member, $container );
		
		if ( $result !== TRUE )
		{
			if ( in_array( $type, array( 'edit', 'delete', 'lock', 'unlock' ) ) and $container and $container->member_id === $member->member_id )
			{
				$result = $member->group['g_blog_allowownmod'];
			}
		}
		
		return $result;
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();

		$this->coverPhoto()->delete();
	}

	/**
	 * Get template for content tables
	 *
	 * @return	array
	 */
	public static function contentTableTemplate(): array
	{
		return array( Theme::i()->getTemplate( 'global', 'blog', 'front' ), 'rows' );
	}

	/**
	 * WHERE clause for getting items for digest (permissions are already accounted for)
	 *
	 * @return	array
	 */
	public static function digestWhere(): array
	{
		return array( array( 'blog_entries.entry_is_future_entry=0 AND blog_entries.entry_status!=?', 'draft' ) );
	}

	/**
	 * WHERE clause for getting items for sitemap (permissions are already accounted for)
	 *
	 * @return    array
	 */
	public static function sitemapWhere(): array
	{
		return array( array( 'blog_entries.entry_is_future_entry=0 AND blog_entries.entry_status!=?', 'draft' ) );
	}
    
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL				$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return    array
	 * @apiresponse	int							id				ID number
	 * @apiresponse	string						title			Title
	 * @apiresponse	\IPS\blog\Blog				blog			Blog
	 * @apiresponse	\IPS\Member					author			The member that created the entry
	 * @apiresponse	bool						draft			If this entry is a draft
	 * @apiresponse	datetime					date			Date
	 * @apiresponse	string						entry			Entry content
	 * @apiresponse	int							comments		Number of comments
	 * @apiresponse	int							views			Number of posts
	 * @apiresponse	string						prefix			The prefix tag, if there is one
	 * @apiresponse	[string]					tags			The tags
	 * @apiresponse	bool						locked			Entry is locked
	 * @apiresponse	bool						hidden			Entry is hidden
	 * @apiresponse	bool						future			Will be published at a future date?
	 * @apiresponse	bool						pinned			Entry is pinned
	 * @apiresponse	bool						featured		Entry is featured
	 * @apiresponse	\IPS\Poll					poll			Poll data, if there is one
	 * @apiresponse	string						url				URL
	 * @apiresponse	float						rating			Average Rating
	 * @apiresponse	\IPS\blog\Entry\Category	category		Category
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array(
			'id'			=> $this->id,
			'title'			=> $this->name,
			'blog'			=> $this->container()->apiOutput( $authorizedMember ),
			'author'		=> $this->author()->apiOutput( $authorizedMember ),
			'draft'			=> $this->status == 'draft',
			'date'			=> DateTime::ts( $this->date )->rfc3339(),
			'entry'			=> $this->content(),
			'comments'		=> $this->num_comments,
			'views'			=> $this->views,
			'prefix'		=> $this->prefix(),
			'tags'			=> $this->tags(),
			'locked'		=> $this->locked(),
			'hidden'		=> (bool) $this->hidden(),
			'future'		=> $this->isFutureDate(),
			'pinned'		=> (bool) $this->mapped('pinned'),
			'featured'		=> (bool) $this->mapped('featured'),
			'poll'			=> $this->poll_state ? Poll::load( $this->poll_state )->apiOutput( $authorizedMember ) : null,
			'url'			=> (string) $this->url(),
			'rating'		=> $this->averageRating(),
			'category'		=> $this->category_id ? $this->category()->apiOutput() : NULL,
		);
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		return 'entry_id';
	}
	
	/**
	 * Supported Meta Data Types
	 *
	 * @return	array
	 */
	public static function supportedMetaDataTypes(): array
	{
		return array( 'core_FeaturedComments', 'core_ContentMessages' );
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
		$idColumn = static::$databaseColumnId;
		$internal = NULL;
		$attachments = array();
		
		if ( isset( static::$databaseColumnMap['content'] ) )
		{
			$internal = Db::i()->select( 'attachment_id', 'core_attachments_map', array( 'location_key=? and id1=?', 'blog_Entries', $this->$idColumn ) );
		}

		if ( $internal )
		{
			foreach( Db::i()->select( '*', 'core_attachments', array( array( 'attach_id IN(?)', $internal ), array( 'attach_is_image=1' ) ), 'attach_id ASC', $limit ) as $row )
			{
				$attachments[] = array( 'core_Attachment' => $row['attach_location'] );
			}
		}

		/* Does the blog entry have a cover photo? */
		if( $this->cover_photo )
		{
			$attachments[] = array( 'blog_Entries' => $this->cover_photo );
		}

		/* And what about the blog itself? */
		if( $this->container()->cover_photo )
		{
			$attachments[] = array( 'blog_Blogs' => $this->container()->cover_photo );
		}

		/* IS there a club with a cover photo? */
		if ( IPS::classUsesTrait( $this->container(), 'IPS\Content\ClubContainer' ) and $club = $this->container()->club() )
		{
			$attachments[] = array( 'core_Clubs' => $club->cover_photo );
		}
		
		return count( $attachments ) ? array_slice( $attachments, 0, $limit ) : NULL;
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'blog', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'blog' )->embedEntry( $this, $this->container(), $this->url()->setQueryString( $params ) );
	}

	/**
	 * Get category
	 *
	 * @return	Category
	 * @throws	OutOfRangeException
	 */
	public function category(): Category
	{
		if ( $this->category === NULL )
		{
			$this->category	= Category::load( $this->category_id );
		}

		return $this->category;
	}
	
	/**
	 * Move
	 *
	 * @param	Model	$container	Container to move to
	 * @param bool $keepLink	If TRUE, will keep a link in the source
	 * @return	void
	 */
	public function move( Model $container, bool $keepLink=FALSE ): void
	{
		parent::move( $container, $keepLink );
		
		$this->category_id = NULL;
		$this->save();
	}
	public static string $itemMenuKey = 'entry_actions';

	public static string $itemMenuCss = '';

	/**
	 * Return badges that should be displayed with the content header
	 *
	 * @return array
	 */
	public function badges() : array
	{
		$return = parent::badges();

		if( $this->status === 'draft' )
		{
			$return['unpublished'] = new Icon( Badge::BADGE_WARNING, 'fa-pen-to-square fa-regular', Member::loggedIn()->language()->addToStack( 'unpublished' ) );
		}

		return $return;
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		if( $image = parent::primaryImage() )
		{
			return $image;
		}

		/* If we don't have a cover photo for the entry, maybe we have one for the blog? */
		return $this->container()->primaryImage();
	}
}