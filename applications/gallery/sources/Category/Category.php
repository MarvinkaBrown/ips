<?php
/**
 * @brief		Category Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Content\ClubContainer;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\ViewUpdates;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Node\DelayedCount;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfBoundsException;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function get_called_class;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Category Node
 */
class Category extends Model implements Permissions
{
	use ClubContainer, DelayedCount, ViewUpdates;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'gallery_categories';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'category_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = 'parent_id';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'categories';

	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'gallery',
		'module'	=> 'gallery',
		'prefix'	=> 'categories_'
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'gallery';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'category';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
		'view' 				=> 'view',
		'read'				=> 2,
		'add'				=> 3,
		'reply'				=> 4,
		'rate'				=> 5,
		'review'			=> 6,
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'gallery_category_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';
	
	/**
	 * @brief	[Node] Moderator Permission
	 */
	public static string $modPerm = 'gallery_categories';

	/**
	 * @brief	Content Item Class
	 */
	public static ?string $contentItemClass = 'IPS\gallery\Image';

	/**
	 * Mapping of node columns to specific actions (e.g. comment, review)
	 * Note: Mappings can also reference bitoptions keys.
	 *
	 * @var array
	 */
	public static array $actionColumnMap = array(
		'comments' 			=> 'allow_comments',
		'reviews'			=> 'allow_reviews',
		'moderate_comments'	=> 'approve_com',
		'moderate_items'	=> 'approve_img',
		'tags'				=> 'can_tag',
		'prefix'			=> 'tag_prefixes'
	);

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;
	
	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_name_seo(): string
	{
		if( !$this->_data['name_seo'] )
		{
			$this->name_seo	= Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'gallery_category_' . $this->id ) );
			$this->save();
		}

		return $this->_data['name_seo'] ?: Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'gallery_category_' . $this->id ) );
	}

	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled(): ?bool
	{
		return NULL;
	}

	/**
	 * Get sort order
	 *
	 * @return	string|null
	 */
	public function get__sortBy(): ?string
	{
		return $this->sort_options ? str_replace( 'album_', '', $this->sort_options ) : NULL;
	}

	/**
	 * [Node] Get number of content items
	 *
	 * @return	int|null
	 * @note	We return null if there are non-public albums so that we can count what you can see properly
	 */
	protected function get__items(): ?int
	{
		return $this->nonpublic_albums ? NULL : $this->count_imgs;
	}

	/**
	 * [Node] Get number of content comments
	 *
	 * @return	int|null
	 */
	protected function get__comments(): ?int
	{
		return $this->count_comments;
	}

	/**
	 * [Node] Get number of content comments (including children)
	 *
	 * @return	int
	 */
	protected function get__commentsForDisplay(): int
	{
		$comments = $this->_comments;

		foreach( $this->children() as $child )
		{
			$comments += $child->_comments;
		}

		return $comments;
	}

	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @return	int
	 */
	protected function get__unnapprovedItems(): int
	{
		return $this->count_imgs_hidden;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @return	int
	 */
	protected function get__unapprovedComments(): int
	{
		return $this->count_comments_hidden;
	}

	/**
	 * @param int $val
	 * @return void
	 */
	protected function set__items( int $val ) : void
	{
		$this->count_imgs = $val;
	}

	/**
	 * Set number of items
	 *
	 * @param int $val	Comments
	 * @return	void
	 */
	protected function set__comments( int $val ) : void
	{
		$this->count_comments = $val;
	}

	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @param	int	$val	Unapproved Items
	 * @return	void
	 */
	protected function set__unapprovedItems( int $val ) : void
	{
		$this->count_imgs_hidden = $val;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @param	int	$val	Unapproved Comments
	 * @return	void
	 */
	protected function set__unapprovedComments( int $val ) : void
	{
		$this->count_comments_hidden = $val;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->addTab( 'category_settings' );
		$form->addHeader( 'category_settings' );
		$form->add( new Translatable( 'category_name', NULL, TRUE, array( 'app' => 'gallery', 'key' => ( $this->id ? "gallery_category_{$this->id}" : NULL ) ) ) );
		$form->add( new Translatable( 'category_description', NULL, FALSE, array(
			'app'		=> 'gallery',
			'key'		=> ( $this->id ? "gallery_category_{$this->id}_desc" : NULL ),
			'editor'	=> array(
				'app'			=> 'gallery',
				'key'			=> 'Categories',
				'autoSaveKey'	=> ( $this->id ? "gallery-cat-{$this->id}" : "gallery-new-cat" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'description' ) : NULL, 
				'minimize'		=> 'cdesc_placeholder'
			)
		) ) );

		$class = get_called_class();

		$form->add( new Node( 'gcategory_parent_id', $this->id ? $this->parent_id : ( Request::i()->parent ?: 0 ), FALSE, array(
			'class'		      => '\IPS\gallery\Category',
			'disabled'	      => false,
			'zeroVal'         => 'node_no_parentg',
			'permissionCheck' => function( $node ) use ( $class )
			{
				if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
				{
					return FALSE;
				}

				return !isset( Request::i()->id ) or ( $node->id != Request::i()->id and !$node->isChildOf( $node::load( Request::i()->id ) ) );
			}
		) ) );

		$sortoptions = array( 
			'album_last_img_date'		=> 'sort_updated', 
			'album_last_comment'		=> 'sort_last_comment',
			'album_rating_aggregate'	=> 'sort_rating', 
			'album_comments'			=> 'sort_num_comments',
			'album_reviews'				=> 'sort_num_reviews',
			'album_name'				=> 'sort_album_name', 
			'album_count_comments'		=> 'sort_album_count_comments', 
			'album_count_imgs'			=> 'sort_album_count_imgs'
		);
		$form->add( new Select( 'category_sort_options', $this->sort_options ?: 'updated', FALSE, array( 'options' => $sortoptions ), NULL, NULL, NULL, 'category_sort_options' ) );

		$form->add( new Select( 'category_sort_options_img', $this->sort_options_img ?: 'updated', FALSE, array( 'options' => array( 'updated' => 'sort_updated', 'last_comment' => 'sort_last_comment', 'title' => 'album_sort_caption', 'rating' => 'sort_rating', 'date' => 'sort_date', 'num_comments' => 'sort_num_comments', 'num_reviews' => 'sort_num_reviews', 'views' => 'sort_views' ) ), NULL, NULL, NULL, 'category_sort_options_img' ) );

		$form->add( new Radio( 'category_allow_albums', $this->id ? $this->allow_albums : 1, TRUE, array( 'options' => array( 0 => 'cat_no_allow_albums', 1 => 'cat_allow_albums', 2 => 'cat_require_albums' ) ) ) );
		$form->add( new YesNo( 'category_approve_img', $this->approve_img, FALSE ) );

		if( Settings::i()->gallery_watermark_path )
		{
			$form->add( new YesNo( 'category_watermark', $this->id ? $this->watermark : TRUE, FALSE ) );
		}
		
		$form->add( new YesNo( 'allow_anonymous_comments', $this->id ? $this->allow_anonymous : FALSE, FALSE, array() ) );

		$form->addHeader( 'category_comments_and_ratings' );
		$form->add( new YesNo( 'category_allow_comments', $this->id ? $this->allow_comments : TRUE, FALSE, array( 'togglesOn' => array( 'category_approve_com' ) ), NULL, NULL, NULL, 'category_allow_comments' ) );
		$form->add( new YesNo( 'category_approve_com', $this->approve_com, FALSE, array(), NULL, NULL, NULL, 'category_approve_com' ) );
		$form->add( new YesNo( 'gcategory_allow_rating', $this->id ? $this->allow_rating : TRUE, FALSE ) );
		$form->add( new YesNo( 'category_allow_reviews', $this->id ? $this->allow_reviews : FALSE, FALSE, array( 'togglesOn' => array( 'category_review_moderate' ) ) ) );
		$form->add( new YesNo( 'category_review_moderate', $this->id ? $this->review_moderate : FALSE, FALSE, array(), NULL, NULL, NULL, 'category_review_moderate' ) );

		$form->addTab( 'category_rules' );
		$form->add( new Radio( 'category_show_rules', $this->id ? $this->show_rules : 0, FALSE, array(
			'options' => array(
				0	=> 'category_show_rules_none',
				1	=> 'category_show_rules_link',
				2	=> 'category_show_rules_full'
			),
			'toggles'	=> array(
				1	=> array(
					'category_rules_title',
					'category_rules_text'
				),
				2	=> array(
					'category_rules_title',
					'category_rules_text'
				),
			)
		) ) );
		$form->add( new Translatable( 'category_rules_title', NULL, FALSE, array( 'app' => 'gallery', 'key' => ( $this->id ? "gallery_category_{$this->id}_rulestitle" : NULL ) ), NULL, NULL, NULL, 'category_rules_title' ) );
		$form->add( new Translatable( 'category_rules_text', NULL, FALSE, array( 'app' => 'gallery', 'key' => ( $this->id ? "gallery_category_{$this->id}_rules" : NULL ), 'editor' => array( 'app' => 'gallery', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "gallery-rules-{$this->id}" : "gallery-new-rules" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'rules' ) : NULL ) ), NULL, NULL, NULL, 'category_rules_text' ) );
		//$form->add( new \IPS\Helpers\Form\Url( 'category_rules_link', $this->rules_link, FALSE, array(), NULL, NULL, NULL, 'category_rules_link' ) );
		
		$form->addTab( 'error_messages' );
		$form->add( new Translatable( 'category_permission_custom_error', NULL, FALSE, array( 'app' => 'gallery', 'key' => ( $this->id ? "gallery_category_{$this->id}_permerror" : NULL ), 'editor' => array( 'app' => 'gallery', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "gallery-permerror-{$this->id}" : "gallery-new-permerror" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'permerror' ) : NULL, 'minimize' => 'gallery_permerror_placeholder' ) ), NULL, NULL, NULL, 'gallery_permission_custom_error' ) );

        parent::form( $form );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		/* Fix field (lang conflict) */
		if( isset( $values['gcategory_allow_rating'] ) )
		{
			$values['category_allow_rating'] = $values['gcategory_allow_rating'];
			unset( $values['gcategory_allow_rating'] );
		}
		if( isset( $values['gcategory_parent_id'] ) )
		{
			$values['category_parent_id'] = $values['gcategory_parent_id'];
			unset( $values['gcategory_parent_id'] );
		}
		
		if( isset( $values['allow_anonymous_comments'] ) )
		{
			$values['allow_anonymous'] = ( $values['allow_anonymous_comments'] ? 2 : 0 );
			unset( $values['allow_anonymous_comments'] );
		}
		
		/* If watermarks are disabled, enable them at the category level so that if you later enable watermarks they work in your existing categories */
		if( !Settings::i()->gallery_watermark_path )
		{
			$values['category_watermark']	= 1;
		}

		/* Claim attachments */
		if ( !$this->id )
		{
			$this->save();
			File::claimAttachments( 'gallery-new-cat', $this->id, NULL, 'description', TRUE );
			File::claimAttachments( 'gallery-new-rules', $this->id, NULL, 'rules', TRUE );
			File::claimAttachments( 'gallery-new-permerror', $this->id, NULL, 'permerror', TRUE );
		}

		/* Custom language fields */
		if( isset( $values['category_name'] ) )
		{
			Lang::saveCustom( 'gallery', "gallery_category_{$this->id}", $values['category_name'] );
			$values['name_seo']	= Friendly::seoTitle( $values['category_name'][ Lang::defaultLanguage() ] );
			unset( $values['category_name'] );
		}

		if( array_key_exists( 'category_description', $values ) )
		{
			Lang::saveCustom( 'gallery', "gallery_category_{$this->id}_desc", $values['category_description'] );
			unset( $values['category_description'] );
		}

		if( array_key_exists( 'category_rules_title', $values ) )
		{
			Lang::saveCustom( 'gallery', "gallery_category_{$this->id}_rulestitle", $values['category_rules_title'] );
			unset( $values['category_rules_title'] );
		}

		if( array_key_exists( 'category_rules_text', $values ) )
		{
			Lang::saveCustom( 'gallery', "gallery_category_{$this->id}_rules", $values['category_rules_text'] );
			unset( $values['category_rules_text'] );
		}

		if( array_key_exists( 'category_permission_custom_error', $values ) )
		{
			Lang::saveCustom( 'gallery', "gallery_category_{$this->id}_permerror", $values['category_permission_custom_error'] );
			unset( $values['category_permission_custom_error'] );
		}

		/* Parent ID */
		if ( isset( $values['category_parent_id'] ) )
		{
			$values['category_parent_id'] = $values['category_parent_id'] ? intval( $values['category_parent_id']->id ) : 0;
		}

		/* Cannot be null */
		if( !isset( $values['category_approve_com'] ) )
		{
			$values['category_approve_com']	= 0;
		}

		/* Send to parent */
		return $values;
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=gallery&module=gallery&controller=browse&category=';
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlTemplate = 'gallery_category';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'name_seo';

	/**
	 * Get "No Permission" error message
	 *
	 * @return	string
	 */
	public function errorMessage(): string
	{
		if ( Member::loggedIn()->language()->checkKeyExists( "gallery_category_{$this->id}_permerror" ) )
		{
			try
			{
				$message = trim( Member::loggedIn()->language()->get( "gallery_category_{$this->id}_permerror" ) );
				if ( !empty( $message ) AND ( $message != '<p></p>' ) )
				{
					return Theme::i()->getTemplate('global', 'core', 'global')->richText( $message );
				}
			}
			catch ( Exception ) {}
		}

		return 'node_error_no_perm';
	}

	/**
	 * Get latest image information
	 *
	 * @return    Image|NULL
	 */
	public function lastImage(): ?Image
	{
		$latestImageData	= $this->getLatestImageId();
		$latestImage		= NULL;
		
		if( $latestImageData !== NULL )
		{
			try
			{
				$latestImage	= Image::load( $latestImageData['id'] );
			}
			catch( OutOfRangeException ){}
		}

		return $latestImage;
	}

	/**
	 * Retrieve the latest image ID in categories and children categories
	 *
	 * @return	array|NULL
	 */
	protected function getLatestImageId(): ?array
	{
		$latestImage	= NULL;

		if ( $this->last_img_id )
		{
			$latestImage = array( 'id' => $this->last_img_id, 'date' => $this->last_img_date );
		}

		foreach( $this->children() as $child )
		{
			$childLatest = $child->getLatestImageId();

			if( $childLatest !== NULL AND ( $latestImage === NULL OR $childLatest['date'] > $latestImage['date'] ) )
			{
				$latestImage	= $childLatest;
			}
		}

		return $latestImage;
	}
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		File::unclaimAttachments( 'gallery_Categories', $this->id );
		parent::delete();
		
		Lang::deleteCustom( 'gallery', "gallery_category_{$this->id}_rulestitle" );
		Lang::deleteCustom( 'gallery', "gallery_category_{$this->id}_rules" );
		Lang::deleteCustom( 'gallery', "gallery_category_{$this->id}_permerror" );
	}
	
	/**
	 * Set last comment
	 *
	 * @param Comment|null $comment The latest comment or NULL to work it out
	 * @param Item|null $updatedItem We sometimes run setLastComment() when an item has been edited, if so, that item will be here
	 * @return    void
	 */
	protected function _setLastComment( Comment $comment=NULL, Item $updatedItem=NULL ) : void
	{
		$this->setLastImage( $updatedItem );
	}

	/**
	 * Set last image data
	 *
	 * @param Image|NULL	$image	The latest image or NULL to work it out
	 * @return	void
	 */
	public function setLastImage( Image $image=NULL ) : void
	{
		/* Make sure the image is actually the latest */
		if( $image !== null and $image->date < $this->last_img_date )
		{
			$image = null;
		}

		/* If we have one, just use it */
		if( $image !== null )
		{
			$this->last_img_date = $image->date;
			$this->last_img_id = $image->id;
		}
		else
		{
			try
			{
				$result	= Db::i()->select( '*', 'gallery_images', array( 'image_category_id=? AND image_approved=1 AND ( image_album_id = 0 OR album_type NOT IN ( 2, 3 ) )', $this->id ), 'image_date DESC', 1 )->join(
					'gallery_albums',
					"image_album_id=album_id"
				)->first();

				$image	= Image::constructFromData( $result );
				$this->last_img_id = $image->id;
				$this->last_img_date = $image->date;
			}
			catch ( UnderflowException $e )
			{
				$this->last_img_id		= 0;
				$this->last_img_date	= 0;
			}
		}

		$this->save();

		if( $image->album_id )
		{
			$album = isset( $result ) ? Album::constructFromData( $result ) : $image->directContainer();
			$album->setLastImage( $image );
			$album->save();
		}
	}

	/**
	 * @brief	Track if we've already reset comment counts so we don't do it more than once between saves
	 */
	protected bool $commentCountsReset = FALSE;

	/**
	 * Set the comment/approved/hidden counts
	 *
	 * @return void
	 */
	public function resetCommentCounts(): void
	{
		if( $this->commentCountsReset )
		{
			return;
		}

		parent::resetCommentCounts();

		/* If we allow albums, add album comment/review counts too */
		if( $this->allow_albums )
		{
			try
			{
				$consolidated = Db::i()->select( 'SUM(album_comments) as comments, SUM(album_comments_unapproved) as unapproved_comments', 'gallery_albums', array( 'album_category_id=?', $this->_id ) )->first();

				$this->_comments			= $this->count_comments + $consolidated['comments'];
				$this->_unapprovedComments	= $this->count_comments_hidden + $consolidated['unapproved_comments'];
			}
			catch( UnderflowException ){}
		}
	}

	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();

		$this->commentCountsReset = FALSE;
	}

	/**
	 * Get last comment time
	 *
	 * @note	This should return the last comment time for this node only, not for children nodes
	 * @param   Member|null    $member         MemberObject
	 * @return	DateTime|NULL
	 */
	public function getLastCommentTime( Member $member = NULL ): ?DateTime
	{
		return $this->last_img_date ? DateTime::ts( $this->last_img_date ) : NULL;
	}

	/**
	 * @brief	Cached cover photo
	 */
	protected mixed $coverPhoto	= NULL;

	/**
	 * Retrieve a cover photo
	 *
	 * @param string $size	Masked or small
	 * @return	mixed
	 * @throws	InvalidArgumentException
	 */
	public function coverPhoto( string $size='small' ): mixed
	{
		if ( !$this->can( 'read' ) )
		{
			return NULL;
		}

		/* Make sure it's a valid size */
		if( !in_array( $size, array( 'masked', 'small' ) ) )
		{
			throw new InvalidArgumentException;
		}
		
		$property = $size . "_file_name";

		/* If we have an explicit cover photo set, make sure it's valid and load/cache it */
		if( $this->cover_img_id )
		{
			if( $this->coverPhoto === NULL )
			{
				$this->coverPhoto = $this->coverPhotoObject();
			}
		}

		if( $this->coverPhoto !== NULL )
		{
			return (string) File::get( 'gallery_Images', $this->coverPhoto->$property )->url;
		}

		if( $lastImage = $this->lastImage() )
		{
			if( $lastImage->$property )
			{
				return (string) File::get( 'gallery_Images', $lastImage->$property )->url;
			}
		}

		return NULL;
	}

	/**
	 * Retrieve a cover photo object
	 *
	 * @return    Image|null
	 */
	public function coverPhotoObject(): ?Image
	{
		/* If we have an explicit cover photo set, make sure it's valid and load/cache it */
		if( $this->cover_img_id )
		{
			if( $this->coverPhoto === NULL )
			{
				try
				{
					$this->coverPhoto	= Image::load( $this->cover_img_id );
					return $this->coverPhoto;
				}
				catch( OutOfRangeException )
				{
					/* Cover photo isn't valid, reset category automatically */
					$this->cover_img_id	= 0;
					$this->save();
				}
			}
		}

		if( $lastImage = $this->lastImage() )
		{
			return $lastImage;
		}

		return NULL;
	}

	/**
	 * Load record based on a URL
	 *
	 * @param	Url	$url	URL to load from
	 * @return	mixed
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function loadFromUrl( Url $url ): mixed
	{
		$qs = array_merge( $url->queryString, $url->hiddenQueryString );
		
		if ( isset( $qs['category'] ) )
		{
			return static::load( $qs['category'] );
		}
		
		throw new InvalidArgumentException;
	}

	/**
	 * Check if any albums are located in this category
	 *
	 * @return	bool
	 */
	public function hasAlbums(): bool
	{
		return (bool) Db::i()->select( 'COUNT(*) as total', 'gallery_albums', array( 'album_category_id=?', $this->id ) )->first();
	}

	/**
	 * Should we show the form to delete or move content?
	 *
	 * @return bool
	 */
	public function showDeleteOrMoveForm(): bool
	{
		/* Do we have any albums? */
		if( $this->hasAlbums() )
		{
			return TRUE;
		}

		return parent::showDeleteOrMoveForm();
	}
	
	/**
	 * Form to delete or move content
	 *
	 * @param	bool	$showMoveToChildren	If TRUE, will show "move to children" even if there are no children
	 * @return	Form
	 */
	public function deleteOrMoveForm( bool $showMoveToChildren=FALSE ): Form
	{
		if ( $this->hasChildren( NULL ) OR $this->hasAlbums() )
		{
			$showMoveToChildren = TRUE;
			if( $this->hasChildren( NULL ) AND $this->hasAlbums() )
			{
				Member::loggedIn()->language()->words['node_move_children']	= Member::loggedIn()->language()->addToStack( 'node_move_catsalbums', FALSE );
				Member::loggedIn()->language()->words['node_delete_children']	= Member::loggedIn()->language()->addToStack( 'node_delete_children_catsalbums', FALSE );
			}
			else if( $this->hasChildren( NULL ) )
			{
				Member::loggedIn()->language()->words['node_move_children']	= Member::loggedIn()->language()->addToStack( 'node_move_children', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( static::$nodeTitle ) ) ) );
				Member::loggedIn()->language()->words['node_delete_children']	= Member::loggedIn()->language()->addToStack( 'node_delete_children_cats', FALSE );
			}
			else
			{
				Member::loggedIn()->language()->words['node_move_children']	= Member::loggedIn()->language()->addToStack( 'node_move_subalbums', FALSE );
				Member::loggedIn()->language()->words['node_delete_children']	= Member::loggedIn()->language()->addToStack( 'node_delete_children_albums', FALSE );
			}
		}
		return parent::deleteOrMoveForm( $showMoveToChildren );
	}
	
	/**
	 * Handle submissions of form to delete or move content
	 *
	 * @param	array	$values			Values from form
	 * @return	void
	 */
	public function deleteOrMoveFormSubmit( array $values ) : void
	{
		if ( $this->hasAlbums() )
		{
			foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'gallery_albums', array( 'album_category_id=?', $this->id ) ), 'IPS\gallery\Album' ) as $album )
			{
				/* @var Album $album */
				if ( isset( $values['node_move_children'] ) AND $values['node_move_children'] )
				{
					$album->moveTo( Category::load( ( isset( $values['node_destination'] ) ) ? $values['node_destination'] : Request::i()->node_move_children ) );
				}
				else
				{
					Task::queue( 'core', 'DeleteOrMoveContent', array( 'class' => 'IPS\gallery\Album', 'id' => $album->_id, 'deleteWhenDone' => TRUE ) );
				}
			}
		}
		
		parent::deleteOrMoveFormSubmit( $values );
	}

	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->sort_options = 'album_last_img_date';
		$this->sort_options_img = 'updated';
	}

	/**
	 * Get template for node tables
	 *
	 * @return	array
	 */
	public static function nodeTableTemplate(): array
	{
		return array( Theme::i()->getTemplate( 'browse', 'gallery' ), 'categoryRow' );
	}

	/**
	 * Check permissions on any node
	 *
	 * For example - can be used to check if the user has
	 * permission to create content in any node to determine
	 * if there should be a "Submit" button
	 *
	 * @param	mixed								$permission						A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param	Member|Group|NULL	$member							The member or group to check (NULL for currently logged in member)
	 * @param	array								$where							Additional WHERE clause
	 * @param	bool								$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 * @throws	OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public static function canOnAny( mixed $permission, Group|Member $member=NULL, array $where = array(), bool $considerPostBeforeRegistering = TRUE ): bool
	{
		/* Load member */
		$member = $member ?: Member::loggedIn();

		if ( $member->members_bitoptions['remove_gallery_access'] )
		{
			return FALSE;
		}

		return parent::canOnAny( $permission, $member, $where, $considerPostBeforeRegistering );
	}

    /**
     * [ActiveRecord] Duplicate
     *
     * @return	void
     */
    public function __clone()
    {
        if ( $this->skipCloneDuplication === TRUE )
        {
            return;
        }

        $this->public_albums = 0;
        $this->nonpublic_albums = 0;
        $this->cover_img_id = 0;

        $oldId = $this->id;

        parent::__clone();

        foreach ( array( 'rules_title' => "gallery_category_{$this->id}_rulestitle", 'rules_text' => "gallery_category_{$this->id}_rules" ) as $fieldKey => $langKey )
        {
            $oldLangKey = str_replace( $this->id, $oldId, $langKey );
            Lang::saveCustom( 'gallery', $langKey, iterator_to_array( Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', $oldLangKey ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
        }
    }
    	
	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string|array
	 */
	public function embedContent( array $params ): array|string
	{
		return 'x';
	}
	
	/**
	 * [Node] Get the fully qualified node type (mostly for Pages because Pages is Pages)
	 *
	 * @return	string
	 */
	public static function fullyQualifiedType(): string
	{
		return Member::loggedIn()->language()->addToStack('__app_gallery') . ' ' . Member::loggedIn()->language()->addToStack( static::$nodeTitle . '_sg');
	}
	
	/* !Clubs */
	
	/**
	 * Get acp language string
	 *
	 * @return	string
	 */
	public static function clubAcpTitle(): string
	{
		return 'editor__gallery_Categories';
	}

	/**
	 * Set form for creating a node of this type in a club
	 *
	 * @param Form $form Form object
	 * @param Club $club
	 * @return    void
	 */
	public function clubForm( Form $form, Club $club ) : void
	{
		/* @var Item $itemClass */
		$itemClass = static::$contentItemClass;
		$form->add( new Text( 'club_node_name', $this->_id ? $this->_title : Member::loggedIn()->language()->addToStack( $itemClass::$title . '_pl' ), TRUE, array( 'maxLength' => 255 ) ) );
		$form->add( new Editor( 'club_node_description', $this->_id ? Member::loggedIn()->language()->get( static::$titleLangPrefix . $this->_id . '_desc' ) : NULL, FALSE, array( 'app' => 'gallery', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "gallery-cat-{$this->id}" : "gallery-new-cat" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'cdesc_placeholder' ) ) );
		
		$form->add( new YesNo( 'category_allow_albums_club', $this->id ? $this->allow_albums : TRUE ) );
		$form->add( new YesNo( 'category_allow_comments', $this->id ? $this->allow_comments : TRUE ) );
		$form->add( new YesNo( 'category_allow_reviews', $this->id ? $this->allow_reviews : FALSE ) );

		$sortoptions = array(
			'album_last_img_date'		=> 'sort_updated',
			'album_last_comment'		=> 'sort_last_comment',
			'album_rating_aggregate'	=> 'sort_rating',
			'album_comments'			=> 'sort_num_comments',
			'album_reviews'				=> 'sort_num_reviews',
			'album_name'				=> 'sort_album_name',
			'album_count_comments'		=> 'sort_album_count_comments',
			'album_count_imgs'			=> 'sort_album_count_imgs'
		);
		$form->add( new Select( 'category_sort_options', $this->sort_options ?: 'updated', FALSE, array( 'options' => $sortoptions ), NULL, NULL, NULL, 'category_sort_options' ) );
		$form->add( new Select( 'category_sort_options_img', $this->sort_options_img ?: 'updated', FALSE, array( 'options' => array( 'updated' => 'sort_updated', 'last_comment' => 'sort_last_comment', 'title' => 'album_sort_caption', 'rating' => 'sort_rating', 'date' => 'sort_date', 'num_comments' => 'sort_num_comments', 'num_reviews' => 'sort_num_reviews', 'views' => 'sort_views' ) ), NULL, NULL, NULL, 'category_sort_options_img' ) );
		
		if( $club->type == 'closed' )
		{
			$form->add( new Radio( 'club_node_public', $this->id ? $this->isPublic() : 0, TRUE, array( 'options' => array( '0' => 'club_node_public_no', '1' => 'club_node_public_view', '2' => 'club_node_public_participate' ) ) ) );
		}
	}
	
	/**
	 * Class-specific routine when saving club form
	 *
	 * @param	Club	$club	The club
	 * @param	array				$values	Values
	 * @return	void
	 */
	public function _saveClubForm( Club $club, array $values ) : void
	{
		$this->allow_albums = $values['category_allow_albums_club'];
		$this->allow_comments = $values['category_allow_comments'];
		$this->allow_reviews = $values['category_allow_reviews'];
		$this->sort_options = $values['category_sort_options'];
		$this->sort_options_img = $values['category_sort_options_img'];
		$this->can_tag = TRUE;
		$this->tag_prefixes = TRUE;
		
		if ( $values['club_node_name'] )
		{
			$this->name_seo	= Friendly::seoTitle( $values['club_node_name'] );
		}
		
		if ( !$this->_id )
		{
			$this->save();
			File::claimAttachments( 'gallery-new-cat', $this->id, NULL, 'description' );
		}
	}

	/**
	 * @brief   The class of the ACP \IPS\Node\Controller that manages this node type
	 */
	protected static ?string $acpController = "IPS\\gallery\\modules\\admin\\gallery\\categories";

	/**
	 * Content was held for approval by container
	 * Allow node classes that can determine if content should be held for approval in individual nodes
	 *
	 * @param	string				$content	The type of content we are checking (item, comment, review).
	 * @param	Member|NULL	$member		Member to check or NULL for currently logged in member.
	 * @return	bool
	 */
	public function contentHeldForApprovalByNode( string $content, ?Member $member = NULL ): bool
	{
		/* If members group bypasses, then no. */
		$member = $member ?: Member::loggedIn();
		if ( $member->group['g_avoid_q'] )
		{
			return FALSE;
		}
		
		switch( $content )
		{
			case 'item':
				return (bool) $this->approve_img;
			
			case 'comment':
				return (bool) $this->approve_com;
			
			case 'review':
				return (bool) $this->review_moderate;
		}

		return FALSE;
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		if( $image = $this->coverPhotoObject() )
		{
			return $image->primaryImage();
		}

		return parent::primaryImage();
	}

	/**
	 * Count all comments, items, etc
	 *
	 * @return void
	 */
	protected function recount() : void
	{
		/* Now get the counts and set them for our images */
		$this->_items				= Db::i()->select( 'COUNT(*) as total', 'gallery_images', array( 'image_category_id=? AND image_approved=1', $this->id ) )->first();
		$this->_unapprovedItems		= Db::i()->select( 'COUNT(*) as total', 'gallery_images', array( 'image_category_id=? AND image_approved=0', $this->id ) )->first();

		if( $this->allow_comments )
		{
			$this->_comments			= Db::i()->select( 'COUNT(*) as total', 'gallery_comments', array( 'gallery_images.image_category_id=? AND comment_approved=1 AND gallery_images.image_approved=1', $this->id ) )->join( 'gallery_images', 'image_id=comment_img_id' )->first();
			$this->_unapprovedComments	= Db::i()->select( 'COUNT(*) as total', 'gallery_comments', array( 'gallery_images.image_category_id=? AND comment_approved=0', $this->id ) )->join( 'gallery_images', 'image_id=comment_img_id' )->first();

			/* And then get counts/latest data for the direct comments/reviews */
			$this->_comments += (int) Db::i()->select( 'sum(album_comments)', 'gallery_albums', array( 'album_category_id=?', $this->id ) )->first();
			$this->_unapprovedComments += (int) Db::i()->select( 'sum(album_comments_unapproved)', 'gallery_albums', array( 'album_category_id=?', $this->id ) )->first();
		}

		if( $this->allow_reviews )
		{
			$this->_reviews	= Db::i()->select( 'COUNT(*) as total', 'gallery_reviews', array( 'gallery_images.image_category_id=? AND review_approved=1', $this->id ) )->join( 'gallery_images', 'image_id=review_image_id' )->first();
			$this->_unapprovedReviews	= Db::i()->select( 'COUNT(*) as total', 'gallery_reviews', array( 'gallery_images.image_category_id=? AND review_approved=0', $this->id ) )->join( 'gallery_images', 'image_id=review_image_id' )->first();

			$this->_reviews += (int) Db::i()->select( 'sum(album_reviews)', 'gallery_albums', array( 'album_category_id=?', $this->id ) )->first();
			$this->_unapprovedReviews += (int) Db::i()->select( 'sum(album_reviews_unapproved)', 'gallery_albums', array( 'album_category_id=?', $this->id ) )->first();
		}
	}
}