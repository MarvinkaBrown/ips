<?php
/**
 * @brief		Album Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\calendar\Calendar;
use IPS\calendar\Event;
use IPS\Content\Comment;
use IPS\Content\Item as ContentItem;
use IPS\Content\Review;
use IPS\Content\Search\Index;
use IPS\Db;
use IPS\File;
use IPS\gallery\Album\Item;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\SocialGroup;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\DelayedCount;
use IPS\Node\Model;
use IPS\Node\Ratings;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Text\Parser;
use IPS\Theme;
use OutOfBoundsException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function ucwords;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Album Node
 */
class Album extends Model
{
	use DelayedCount,
	Ratings
	{
		Ratings::canRate as public _canRate;
	}
	
	/**
	 * @brief	Define view access levels
	 */
	const AUTH_TYPE_PUBLIC		= 1;
	const AUTH_TYPE_PRIVATE		= 2;
	const AUTH_TYPE_RESTRICTED	= 3;
	const AUTH_TYPE_DELETED		= 4;

	/**
	 * @brief	Define submit access levels
	 */
	const AUTH_SUBMIT_OWNER			= 0;
	const AUTH_SUBMIT_PUBLIC		= 1;
	const AUTH_SUBMIT_GROUPS		= 2;
	const AUTH_SUBMIT_MEMBERS		= 3;
	const AUTH_SUBMIT_CLUB			= 4;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'gallery_albums';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'album_';

	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static string $parentNodeColumnId = 'category_id';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'albums';

	/**
	 * @brief	[Node] Node Database Order Column
	 */
	public static ?string $databaseColumnOrder = 'name';

	/**
	 * @brief	[Node] Sortable?
	 */
	public static bool $nodeSortable = FALSE;

	/**
	 * @brief	Content Item Class
	 */
	public static ?string $contentItemClass = 'IPS\gallery\Image';

	/* These are required to be declared here as well as the asItem class for embeds.

	/**
	 * @brief	Review Class
	 */
	public static string $reviewClass = 'IPS\gallery\Album\Review';

	/**
	 * @brief	Comment Class
	 */
	public static ?string $commentClass = 'IPS\gallery\Album\Comment';

	/**
	 * @brief	[Node] If the node can be "owned", the owner "type" (typically "member" or "group") and the associated database column
	 */
	public static ?array $ownerTypes = array( 'member' => 'owner_id' );

	/**
	 * @brief	[Node] By mapping appropriate columns (rating_average and/or rating_total + rating_hits) allows to cache rating values
	 */
	public static array $ratingColumnMap	= array(
		'rating_average'	=> 'rating_aggregate',
		'rating_total'		=> 'rating_total',
		'rating_hits'		=> 'rating_count',
	);

	/**
	 * Mapping of node columns to specific actions (e.g. comment, review)
	 * Note: Mappings can also reference bitoptions keys.
	 *
	 * @var array
	 */
	public static array $actionColumnMap = array(
		'comments' 			=> 'allow_comments',
		'reviews'			=> 'allow_reviews'
	);

	/**
	 * @brief	[Node] Maximum results to display at a time in any node helper form elements. Useful for user-submitted node types when there may be a lot. NULL for no limit.
	 */
	public static ?int $maxFormHelperResults = 2000;

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;

	/**
	 * Return this album as a content item instead of a node
	 *
	 * @return Item
	 */
	public function asItem(): Item
	{
		$data = $this->_data;

		foreach( $this->_data as $k => $v )
		{
			$data['album_' . $k ] = $v;
		}

		return Item::constructFromData( $data );
	}

	/**
	 * [Node] Return the owner if this node can be owned
	 *
	 * @throws	RuntimeException
	 * @return	Member|null
	 */
	public function owner(): ?Member
	{
		$owner = parent::owner();

		/* Gallery albums have to be owned by a user, so return a guest user if the owner is invalid */
		if( $owner === NULL OR $owner->member_id === null )
		{
			return new Member;
		}
		
		return $owner;
	}

	/**
	 * [Node] Load and check permissions
	 *
	 * @param	mixed				$id		ID
	 * @param string $perm	Permission Key
	 * @param	Member|NULL	$member	Member, or NULL for logged in member
	 * @return	static
	 * @throws	OutOfRangeException
	 * @note	Album 'add' permissions are properly checked via the can() method
	 */
	public static function loadAndCheckPerms( mixed $id, string $perm='view', Member $member = NULL ): static
	{
		$obj = parent::loadAndCheckPerms( $id );
		$member = $member ?: Member::loggedIn();
		
		if ( $obj->type == static::AUTH_TYPE_DELETED )
		{
			throw new OutOfRangeException;
		}
		
		if ( !$obj->can( $perm ) )
		{
			/* If we are adding and the member has mod permissions to edit in the category, they can create an album for another user. In that case
				we need to allow the add permissions or they won't be able to add images to the new album */
			if( $perm != 'add' OR !Item::modPermission( 'edit', $member, $obj->category() ) )
			{
				throw new OutOfRangeException;
			}
		}

		return $obj;
	}

	/**
	 * Fetch all albums we can submit to
	 *
	 * @param Category $category		Category we are submitting in
	 * @return	array
	 * @throws	RuntimeException
	 */
	public static function loadForSubmit( Category $category ): array
	{
		$ownedAlbums		= static::loadByOwner( NULL, array( array( 'album_category_id=?', $category->id ) ) );
		$permittedAlbums	= Item::getItemsWithPermission( array( array( 'album_category_id=?', $category->id ) ), NULL, NULL, 'add' );
		$finalAlbums		= $ownedAlbums;

		foreach( $permittedAlbums as $album )
		{
			$finalAlbums[ $album->id ] = $album->asNode();
		}

		return $finalAlbums;
	}
	
	/**
	 * Fetch all nodes owned by a given user
	 *
	 * @param Member|int|null $member		The member whose nodes to load
	 * @param array $where		Initial where clause
	 * @return	array
	 * @throws	RuntimeException
	 */
	public static function loadByOwner( Member|int $member=NULL, array $where=array() ): array
	{
		$where[] = array( 'album_type !=?', static::AUTH_TYPE_DELETED );
		
		return parent::loadByOwner( $member, $where );
	}

	/**
	 * @brief Cached approved members
	 */
	protected mixed $_approvedMembers	= FALSE;

	/**
	 * Get members with access to view restricted album
	 *
	 * @return	array|null
	 * @note	This list will NOT include the album owner, who also inherently has access
	 */
	protected function get_approvedMembers() : array|null
	{
		if( $this->_approvedMembers !== FALSE )
		{
			return $this->_approvedMembers;
		}

		if( $this->type === static::AUTH_TYPE_RESTRICTED )
		{
			$members	= array();

			foreach( Db::i()->select( '*', 'core_sys_social_group_members', array( 'group_id=?', $this->allowed_access ) ) as $member )
			{
				$members[]	= Member::load( $member['member_id'] );
			}

			$this->_approvedMembers	= $members;

			return $this->_approvedMembers;
		}

		$this->_approvedMembers = NULL;

		return $this->_approvedMembers;
	}

	/**
	 * [Node] Get title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		return $this->name;
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

			if( $this->_data['name_seo'] )
			{
				$this->save();
			}
		}

		return $this->_data['name_seo'] ?: Friendly::seoTitle( $this->name );
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
		return $this->sort_options;
	}

	/**
	 * [Node] Get number of content items
	 *
	 * @return	int|null
	 */
	protected function get__items(): ?int
	{
		return $this->count_imgs;
	}

	/**
	 * Get items count language string
	 *
	 * @return string
	 * @throws	BadMethodCallException
	 */
	public function get__countLanguageString(): string
	{
		return 'num_images';
	}

	/**
	 * [Node] Get number of content comments
	 *
	 * @return int|null
	 */
	protected function get__comments(): ?int
	{
		return $this->count_comments;
	}

	/**
	 * [Node] Get number of content comments (for display)
	 *
	 * @return	int
	 */
	protected function get__commentsForDisplay(): int
	{
		return $this->_comments;
	}

	/**
	 * [Node] Get number of content reviews
	 *
	 * @return int|null
	 */
	protected function get__reviews(): ?int
	{
		return $this->count_reviews;
	}

	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @return int|null
	 */
	protected function get__unnapprovedItems(): ?int
	{
		return $this->count_imgs_hidden;
	}

	/**
	 * [Node] Get number of unapproved content reviews
	 *
	 * @return int|null
	 */
	protected function get__unapprovedReviews(): ?int
	{
		return $this->count_reviews_hidden;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @return	int|null
	 */
	protected function get__unapprovedComments(): ?int
	{
		return $this->count_comments_hidden;
	}

	/**
	 * [Node] Get content table description
	 *
	 * @return string|null
	 */
	protected function get_description(): ?string
	{
		return $this->_data['description'];
	}

	/**
	 * Set number of items
	 *
	 * @param int $val	Items
	 * @return	void
	 */
	protected function set__items( int $val ) : void
	{
		$this->count_imgs = $val;
	}


	/**
	 * Set number of items
	 *
	 * @param	int	$val		Comments
	 * @return	void
	 */
	protected function set__comments( int $val ) : void
	{
		$this->count_comments = $val;
	}

	/**
	 * Set number of items
	 *
	 * @param	int	$val		Reviews
	 * @return	void
	 */
	protected function set__reviews( int $val ) : void
	{
		$this->count_reviews = $val;
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
	 * [Node] Get number of unapproved content reviews
	 *
	 * @param	int	$val		Unapproved Reviews
	 * @return	void
	 */
	protected function set__unapprovedReviews( int $val ) : void
	{
		$this->count_reviews_hidden = $val;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		foreach (static::formFields($this->_id ? $this : NULL) as $field )
		{
			$form->add( $field );
		}

        parent::form( $form );
	}
	
	/**
	 * Get fields
	 *
	 * @param Album|NULL	$album		The album
	 * @param bool $forOther	Is this specifically not for the current member (e.g. on a move form)?
	 * @param	bool					$required	If TRUE, required elements (like name are actually required) otherwise they just appear so (e.g. on move form)
	 * @return	array
	 */
	public static function formFields( Album $album = NULL, bool $forOther=FALSE, bool $required = TRUE ): array
	{
		/* Save my sanity here.... load the category if we have one */
		$category = NULL;
		if( $album AND $album->category_id )
		{
			$category = $album->category();
		}
		elseif( !$album AND ( isset( Request::i()->chosenCategory ) or isset( Request::i()->category ) ) )
		{
			try
			{
				$category = Category::load( (int)Request::i()->chosenCategory ?: Request::i()->category );
			}
			catch( OutOfRangeException ){}
		}

		$return = array();
		
		$return[] = new Text( 'album_name', $album ? $album->name : '', $required ?: NULL );

		$return[] = new Editor( 'album_description', $album ? $album->description : '', FALSE, array( 'app' => 'gallery', 'key' => 'Albums', 'autoSaveKey' => ( $album ? "gallery_album_{$album->id}_desc" : "gallery-new-album" ), 'attachIds' => $album ? array( $album->id, NULL, 'description' ) : NULL ) );
		
		$return[] = new Node( 'album_category', $category?->id, $required ?: NULL, array(
			'class'		      => 'IPS\gallery\Category',
			'disabled'	      => false,
			'permissionCheck' => function( $node )
			{
				if ( ! $node->allow_albums )
				{
					return false;
				}
				
				if ( ! $node->can( 'add' ) )
				{
					return false;
				}
				
				return true;
			}
		) );

		if( $forOther or Item::modPermission( 'edit', NULL, ($album?->category()) ) )
		{
			if ( !$forOther )
			{
				$return[] = new Radio( 'set_album_owner', ( $album AND $album->owner_id !== Member::loggedIn()->member_id ) ? 'other' : 'me', $required ?: NULL, array( 'options' => array( 'me' => 'set_album_owner_me', 'other' => 'set_album_owner_other' ), 'toggles' => array( 'other' => array( 'album_owner' ) ) ), NULL, NULL, NULL, 'set_album_owner' );
			}
			$return[] = new Form\Member( 'album_owner', $album ? Member::load( $album->owner_id )->name : NULL, NULL, array(), function($val ) use ( $required, $forOther )
			{
				if ( !$val and $required and ( $forOther or Request::i()->set_album_owner == 'other' ) )
				{
					throw new DomainException('form_required');
				}
			}, NULL, NULL, 'album_owner' );
		}

		$types		= array( static::AUTH_TYPE_PUBLIC => 'album_public' );
		$toggles	= array();

		if( Member::loggedIn()->group['g_create_albums_private'] )
		{
			$types[ static::AUTH_TYPE_PRIVATE ]	= ( Image::modPermission( 'edit', NULL, ($album?->category()) ) ) ? 'album_private_mod' : 'album_private';
		}

		if( Member::loggedIn()->group['g_create_albums_fo'] )
		{
			$types[ static::AUTH_TYPE_RESTRICTED ]	= 'album_friend_only';
			$toggles[ static::AUTH_TYPE_RESTRICTED ]	= array( 'album_allowed_access' );
		}
		$return[] = new Radio( 'album_type', ( $album and $album->type ) ? $album->type : static::AUTH_TYPE_PUBLIC, FALSE, array( 'options' => $types, 'toggles' => $toggles ), NULL, NULL, NULL, 'album_type' );

		if( Member::loggedIn()->group['g_create_albums_fo'] )
		{
			$return[] = new SocialGroup( 'album_allowed_access', $album ? (int) $album->allowed_access : NULL, FALSE, array( 'owner' => $album ? Member::load( $album->owner_id ) : ( Request::i()->album_owner ? Member::load( Request::i()->album_owner, 'name' ) : Member::loggedIn() ) ), NULL, NULL, NULL, 'album_allowed_access' );
		}

		$submitTypes = array(
			static::AUTH_SUBMIT_OWNER		=> 'album_submittype_owner',
			static::AUTH_SUBMIT_PUBLIC		=> 'album_submittype_public',
			static::AUTH_SUBMIT_GROUPS		=> 'album_submittype_groups',
			static::AUTH_SUBMIT_MEMBERS		=> 'album_submittype_members'
		);

		/* Is the category in a club */
		if( $category !== NULL AND $club = $category->club() )
		{
			$submitTypes[ static::AUTH_SUBMIT_CLUB ]	= 'album_submittype_club';
		}

		$return[] = new Radio( 'album_submit_type', ( $album and $album->submit_type ) ? $album->submit_type : static::AUTH_SUBMIT_OWNER, FALSE, array( 'options' => $submitTypes, 'toggles' => array( static::AUTH_SUBMIT_MEMBERS => array( 'album_submit_access_members' ), static::AUTH_SUBMIT_GROUPS => array( 'album_submit_access_groups' ) ) ), NULL, NULL, NULL, 'album_submit_type' );

		$return[] = new SocialGroup( 'album_submit_access_members', ( $album AND $album->submit_type == static::AUTH_SUBMIT_MEMBERS ) ? (int) $album->submit_access : NULL, FALSE, array( 'owner' => $album ? Member::load( $album->owner_id ) : ( Request::i()->album_owner ? Member::load( Request::i()->album_owner, 'name' ) : Member::loggedIn() ) ), NULL, NULL, NULL, 'album_submit_access_members' );
		$groups		= array_combine( array_keys( Group::groups( TRUE, FALSE ) ), array_map( function( $_group ) { return (string) $_group; }, Group::groups( TRUE, FALSE ) ) );
		$return[] = new CheckboxSet( 'album_submit_access_groups', ( $album and $album->submit_type == static::AUTH_SUBMIT_GROUPS ) ? explode( ',', $album->submit_access ) : NULL, FALSE, array( 'options' => $groups, 'multiple' => true ), NULL, NULL, NULL, 'album_submit_access_groups' );

		$return[] = new Select( 'album_sort_options', ( ( $album and $album->sort_options ) ? $album->sort_options : ( ( $category AND $category->sort_options_img ) ? $category->sort_options_img : 'updated' ) ), FALSE, array( 'options' => array( 'updated' => 'sort_updated', 'last_comment' => 'sort_last_comment', 'title' => 'album_sort_caption', 'rating' => 'sort_rating', 'date' => 'sort_date', 'num_comments' => 'sort_num_comments', 'num_reviews' => 'sort_num_reviews', 'views' => 'sort_views' ) ), NULL, NULL, NULL, 'album_sort_options' );

		$return[] = new YesNo( 'album_allow_rating', $album ? $album->allow_rating : TRUE, FALSE );

		if( $category AND $category->allow_comments )
		{
			$return[] = new YesNo('album_allow_comments', $album ? $album->allow_comments : TRUE, FALSE);
		}
		if( $category AND $category->allow_reviews )
		{
			$return[] = new YesNo('album_allow_reviews', $album ? $album->allow_reviews : TRUE, FALSE);
		}

		if( $category AND $category->allow_comments )
		{
			$return[] = new YesNo('album_use_comments', $album ? $album->use_comments : TRUE, FALSE );
		}
		if( $category AND $category->allow_reviews )
		{
			$return[] = new YesNo('album_use_reviews', $album ? $album->use_reviews : TRUE, FALSE );
		}
		
		return $return;
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$this->postSaveIsEdit	= FALSE;

		/* Claim attachments */
		if ( !$this->id )
		{
			$this->save();
			File::claimAttachments( 'gallery-new-album', $this->id, NULL, 'description' );

			/* Update public/non-public album count */
			if( isset( $values['album_type'] ) AND isset( $values['album_category'] ) )
			{
				if( $values['album_type'] == static::AUTH_TYPE_PUBLIC )
				{
					$values['album_category']->public_albums	= $values['album_category']->public_albums + 1;
				}
				else
				{
					$values['album_category']->nonpublic_albums	= $values['album_category']->nonpublic_albums + 1;
				}

				$values['album_category']->save();
			}
		}
		else
		{
			$this->postSaveIsEdit = TRUE;
			
			/* Are we changing from a private / friends only album to a public one? */
			if ( $values['album_type'] != $this->type )
			{
				/* Remember the current type */
				$this->postSaveType	= $this->type;

				/* Private to Public */
				if ( $values['album_type'] == static::AUTH_TYPE_PUBLIC )
				{
					$values['album_category']->public_albums	= $values['album_category']->public_albums + 1;
					$values['album_category']->nonpublic_albums	= $values['album_category']->nonpublic_albums - 1;
					$values['album_category']->save();
				}
				else
				{
					/* Public to Private... but only if was really public previously (we don't want to do this for private to friends-only) */
					if ( $this->type == static::AUTH_TYPE_PUBLIC )
					{
						$values['album_category']->public_albums	= $values['album_category']->public_albums - 1;
						$values['album_category']->nonpublic_albums	= $values['album_category']->nonpublic_albums + 1;
						$values['album_category']->save();
					}
				}
			}
		}

		/* Custom language fields */
		if( isset( $values['album_name'] ) )
		{
			Lang::saveCustom( 'gallery', "gallery_album_{$this->id}", $values['album_name'] );
			$values['name_seo']	= Friendly::seoTitle( is_array( $values['album_name'] ) ? $values['album_name'][ Lang::defaultLanguage() ] : $values['album_name'] );
		}

		if( isset( $values['album_description'] ) )
		{
			Lang::saveCustom( 'gallery', "gallery_album_{$this->id}_desc", $values['album_description'] );
		}

		/* Related ID */
		if( isset( $values['album_category'] ) )
		{
			$this->postSaveCategory	= $this->category_id;
			$values['category_id']	= $values['album_category']->id;
			unset( $values['album_category'] );
		}
		
		if( isset( $values['set_album_owner'] ) )
		{
			$values['owner_id']		= ( $values['set_album_owner'] == 'me' ) ? Member::loggedIn()->member_id : ( ( $values['album_owner'] instanceof Member ) ? $values['album_owner']->member_id : $values['album_owner'] );

			if( !$values['owner_id'] )
			{
				$values['owner_id']	= Member::loggedIn()->member_id;
			}
			unset( $values['set_album_owner'] );

			if( array_key_exists( 'album_owner', $values ) )
			{
				unset( $values['album_owner'] );
			}
		}
		else if( array_key_exists( 'album_owner', $values ) )
		{
			$values['owner_id']	= ( $values['album_owner'] instanceof Member ) ? $values['album_owner']->member_id : $values['album_owner'];
			unset( $values['album_owner'] );
		}
		else if ( !$this->postSaveIsEdit )
		{
			$values['owner_id']	= Member::loggedIn()->member_id;
		}

		switch( $values['album_submit_type'] )
		{
			case static::AUTH_SUBMIT_GROUPS:
				$values['submit_access']	= implode( ',', $values['album_submit_access_groups'] );
			break;

			case static::AUTH_SUBMIT_MEMBERS:
				$values['submit_access']	= $values['album_submit_access_members'];
			break;
		}

		unset( $values['album_submit_access_members'] );
		unset( $values['album_submit_access_groups'] );

		/* Send to parent */
		return $values;
	}

	/**
	 * @brief	Remember if we are editing or adding
	 */
	protected bool $postSaveIsEdit	= FALSE;

	/**
	 * @brief	Remember previous category when editing
	 */
	protected ?int $postSaveCategory	= 0;

	/**
	 * @brief	Remember previous album status when editing
	 */
	protected mixed $postSaveType		= NULL;
	
	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		File::claimAttachments( 'gallery-new-album', $this->id );
		
		$sendFilterNotifications = $this->asItem()->checkProfanityFilters( FALSE, $this->postSaveIsEdit );
		if ( $sendFilterNotifications )
		{
			/* Save it so it hides */
			$this->asItem()->save();
		}

		/* Update counts in categories if we move the album */
		if( $this->postSaveIsEdit and $this->postSaveCategory != $values['category_id'] )
		{
			$this->moveTo( Category::load( $values['category_id'] ), Category::load( $this->postSaveCategory ) );
		}
		
		/* Update search index */
		if( $this->postSaveIsEdit and ( $this->postSaveType != $values['album_type'] OR $this->postSaveCategory != $values['category_id'] ) )
		{
			Index::i()->massUpdate( 'IPS\gallery\Image', $this->id, NULL, $this->searchIndexPermissions() );
		}

        parent::postSaveForm( $values );
	}

	/**
	 * Get category album belongs to
	 *
	 * @return    Category
	 */
	public function category(): Category
	{
		return Category::load( $this->category_id );
	}
	
	/**
	 * Move to a different category
	 *
	 * @param Category $newCategory		New category
	 * @param Category|NULL	$existingCategory	Old category
	 * @return	void
	 */
	public function moveTo( Category $newCategory, Category $existingCategory = NULL ) : void
	{
		if ( $existingCategory === NULL )
		{
			$existingCategory = $this->category();
		}
		$this->category_id	= $newCategory->id;
		$this->save();
		
		/* Update images */
		Db::i()->update( 'gallery_images', array( 'image_category_id' => $newCategory->id ), array( 'image_album_id=?', $this->id ) );

		/* Update categories */
		foreach ( array( $newCategory, $existingCategory ) as $category )
		{
			$category->setLastComment();
			$category->public_albums			= (int) Db::i()->select( 'COUNT(*)', 'gallery_albums', array( 'album_category_id=? and album_type=1', $category->_id ) )->first();
			$category->nonpublic_albums			= (int) Db::i()->select( 'COUNT(*)', 'gallery_albums', array( 'album_category_id=? and album_type>1', $category->_id ) )->first();
			$category->save();
		}

		/* Tags */
		Db::i()->update( 'core_tags', array(
			'tag_aap_lookup'		=> md5( 'gallery;category;' . $newCategory->_id ),
			'tag_meta_parent_id'	=> $newCategory->_id
		), array( 'tag_meta_app=? and tag_meta_area=? and tag_meta_parent_id=?', 'gallery', 'images', $existingCategory->_id ) );
		
		Db::i()->update( 'core_tags_perms', array(
			'tag_perm_aap_lookup'	=> md5( 'gallery;category;' . $newCategory->_id ),
			'tag_perm_text'			=> Db::i()->select( 'perm_2', 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id=?', 'gallery', 'category', $newCategory->_id ) )->first()
		), array( 'tag_perm_aap_lookup=?', md5( 'gallery;category;' . $existingCategory->_id ) ) );

		/* Load the item */
		$asItem = $this->asItem();

		/* Add to search index */
		Index::i()->index( $asItem );

		foreach ( array( 'commentClass', 'reviewClass' ) as $class )
		{
			$className = Item::$$class;
			Index::i()->massUpdate( $className, NULL, $this->id, $this->searchIndexPermissions(), NULL, $newCategory->_id );
		}

		/* Update caches */
		$asItem->expireWidgetCaches();
		$asItem->adjustSessions();
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;
	
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
	 * Get latest image information
	 *
	 * @return    Image|NULL
	 */
	public function lastImage(): ?Image
	{
		if( !$this->last_img_id )
		{
			return NULL;
		}

		try
		{
			return Image::load( $this->last_img_id );
		}
		catch ( Exception ) /* Catch both Underflow and OutOfRange */
		{
			return NULL;
		}
	}

	/**
	 * Set last comment
	 *
	 * @param Comment|null $comment The latest comment or NULL to work it out
	 * @param Item|null $updatedItem We sometimes run setLastComment() when an item has been edited, if so, that item will be here
	 * @return    void
	 */
	protected function _setLastComment( Comment $comment=NULL, ContentItem $updatedItem=NULL ) : void
	{
		/* If this updated comment is older than the last shown on the node, do nothing */
		if( $updatedItem !== NULL AND $updatedItem->date < $this->last_img_date )
		{
			$updatedItem = null;
		}

		$this->setLastImage( $updatedItem );
	}

	/**
	 * Set last review
	 *
	 * @param	Review|NULL	$review	The latest review or NULL to work it out
	 * @return	void
	 * @note	We actually want to set the last image info, not the last review, so we ignore $review
	 */
	public function setLastReview( Review $review=NULL ) : void
	{
		$this->setLastImage();
	}

	/**
	 * Set last image data
	 *
	 * @param Image|null	$image
	 * @param string $sortBy		The column to sort by for last X images (defaults to image_date DESC; third parties can override)
	 * @return	void
	 * @note	This is called from the category, so we don't need to update our parent (the category)
	 */
	public function setLastImage( ?Image $image=null, string $sortBy='image_date DESC' ) : void
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

			$_latestImages = $this->last_x_images ? json_decode( $this->last_x_images, true ) : [];
			if( !is_array($_latestImages ) )
			{
				$_latestImages = [];
			}
			if( !in_array( $image->id, $_latestImages ) )
			{
				array_unshift( $_latestImages, $image->id );
				if( count( $_latestImages ) > 20 )
				{
					array_pop( $_latestImages );
				}
				$this->last_x_images = json_encode( $_latestImages );
			}
		}
		else
		{
			/* Figure out our latest images in this album */
			$_latestImages	= array();

			$this->last_img_date	= 0;
			$this->last_img_id		= 0;
			foreach( Db::i()->select( '*', 'gallery_images', array( 'image_album_id=? AND image_approved=1', $this->id ), $sortBy, array( 0, 20 ) ) as $image )
			{
				if( $image['image_date'] > $this->last_img_date )
				{
					$this->last_img_date	= $image['image_date'];
					$this->last_img_id		= $image['image_id'];
				}

				$_latestImages[]	= $image['image_id'];
			}

			$this->last_x_images	= json_encode( $_latestImages );
		}

		$this->save();

		/* Save and then make sure search index is updated */
		Index::i()->index( $this->asItem() );
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
		return $this->asItem()->contentImages( $limit, $ignorePermissions );
	}
	
	/**
	 * Retrieve the latest images
	 *
	 * @return	iterable
	 */
	public function get__latestImages(): iterable
	{
		if ( ! $this->last_x_images )
		{
			return [];
		}

		$_latestImages	= json_decode( $this->last_x_images, TRUE ) ?? array();

		if( ! count( $_latestImages ) )
		{
			return [];
		}

		return Image::getItemsWithPermission( array( array( 'image_id IN(' . implode( ',', $_latestImages ) . ')' ) ), NULL, 20 );
	}

	/**
	 * @brief	Cached calendar events
	 */
	protected ?array $_events	= NULL;

	/**
	 * Get any associated calendar events
	 *
	 * @return	array|null
	 */
	public function get__event(): ?array
	{
		if( $this->_events !== NULL )
		{
			return $this->_events;
		}

		if( Application::appIsEnabled( 'calendar' ) )
		{
			try
			{
				$events	= iterator_to_array( Event::getItemsWithPermission( array( array( 'event_album=?', $this->id ) ) ) );

				if( !count( $events ) )
				{
					$this->_events	= array();
					return $this->_events;
				}

				Calendar::addCss();
				Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'calendar.css', 'calendar', 'front' ) );

				$this->_events	= $events;
				return $this->_events;
			}
			catch( OutOfRangeException ){}
		}

		$this->_events	= array();
		return $this->_events;
	}
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		File::unclaimAttachments( 'gallery_Albums', $this->id );
		parent::delete();

		Lang::deleteCustom( 'gallery', "gallery_album_{$this->id}" );
		Lang::deleteCustom( 'gallery', "gallery_album_{$this->id}_desc" );
		
		/* If there was a social group saved, delete it */
		if( $this->allowed_access )
		{
			Db::i()->delete( 'core_sys_social_groups', array( 'group_id=?', $this->allowed_access ) );
			Db::i()->delete( 'core_sys_social_group_members', array( 'group_id=?', $this->allowed_access ) );
		}

		/* If any calendar events are associated, unassociate */
		if( Application::appIsEnabled( 'calendar' ) )
		{
			Db::i()->update( 'calendar_events', array( 'event_album' => 0 ), array( 'event_album=?', $this->id ) );
		}

		/* Delete as an item to remove comments, meta data, search index, reviews, etc. */
		$this->asItem()->delete();

		/* Update category information */
		if( $this->type == static::AUTH_TYPE_PUBLIC )
		{
			$this->category()->public_albums	= $this->category()->public_albums - 1;
		}
		else if( $this->type == static::AUTH_TYPE_PRIVATE or $this->type == static::AUTH_TYPE_RESTRICTED )
		{
			$this->category()->nonpublic_albums	= $this->category()->nonpublic_albums - 1;
		}

		$this->category()->save();
	}

	/**
	 * Retrieve the content item count
	 *
	 * @param array|null $data	Data array for mass move/delete
	 * @return	null|int
	 */
	public function getContentItemCount( array $data=NULL ): ?int
	{
		/* @var ContentItem $contentItemClass */
		$contentItemClass = static::$contentItemClass;

		$where = array( array( $contentItemClass::$databasePrefix . 'album_id=?', $this->id ) );

		if( $data )
		{
			$where = array_merge_recursive( $where, $this->massMoveorDeleteWhere( $data ) );
		}

		return (int) Db::i()->select( 'COUNT(*)', $contentItemClass::$databaseTable, $where )->first();
	}

	/**
	 * Retrieve content items (if applicable) for a node.
	 *
	 * @param int|null $limit The limit
	 * @param int|null $offset The offset
	 * @param array $additionalWhere
	 * @param bool|int $countOnly If TRUE, will get the number of results
	 * @return    ActiveRecordIterator|int
	 */
	public function getContentItems( ?int $limit, ?int $offset, array $additionalWhere = array(), bool|int $countOnly=FALSE ): ActiveRecordIterator|int
	{
		if ( !isset( static::$contentItemClass ) )
		{
			throw new BadMethodCallException;
		}

		/* @var ContentItem $contentItemClass */
		$contentItemClass = static::$contentItemClass;

		$where		= array();
		$where[]	= array( $contentItemClass::$databasePrefix . 'album_id=?', $this->_id );

		if ( count( $additionalWhere ) )
		{
			foreach( $additionalWhere AS $clause )
			{
				$where[] = $clause;
			}
		}

		if ( $countOnly )
		{
			return Db::i()->select( 'COUNT(*)', $contentItemClass::$databaseTable, $where )->first();
		}
		else
		{
			$limit	= ( $offset !== NULL ) ? array( $offset, $limit ) : NULL;
			return new ActiveRecordIterator( Db::i()->select( '*', $contentItemClass::$databaseTable, $where, $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId, $limit ), $contentItemClass );
		}
	}

	/**
	 * Text for use with data-ipsTruncate
	 * Returns the post with paragraphs turned into line breaks
	 *
	 * @return	string
	 */
	public function truncated(): string
	{
		$text = Parser::removeElements( $this->description, array( 'blockquote' ) );
		$text = str_replace( array( '</p>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>' ), '<br>', $text );
		$text = strip_tags( str_replace( ">", "> ", $text ), '<br>' );

		return $text;
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

			if( $this->coverPhoto !== NULL )
			{
				return (string) File::get( 'gallery_Images', $this->coverPhoto->$property )->url;
			}
		}

		if( $lastImage = $this->lastImage() AND !$lastImage->media )
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
				}
				catch( OutOfRangeException )
				{
					/* Cover photo isn't valid, reset album automatically */
					$this->cover_img_id	= 0;
					$this->save();
				}
			}
		}

		if( $this->coverPhoto !== NULL )
		{
			return $this->coverPhoto;
		}

		if( $lastImage = $this->lastImage() AND !$lastImage->media )
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
		
		if ( isset( $qs['album'] ) )
		{
			return static::load( $qs['album'] );
		}
		
		throw new InvalidArgumentException;
	}

	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		if( static::restrictionCheck( 'delete' ) )
		{
			return TRUE;
		}

		return $this->asItem()->canDelete();
	}

	/**
	 * Check permissions
	 *
	 * @param	mixed								$permission		A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param Group|Member|null $member							The member or group to check (NULL for currently logged in member)
	 * @param bool $considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 * @throws	OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 * @note	Albums don't have permissions, but instead check against the category they are in
	 */
	public function can( mixed $permission, Group|Member $member=NULL, bool $considerPostBeforeRegistering = TRUE ): bool
	{
		/* Load the item */
		$asItem = $this->asItem();

		/* Figure out member */
		$member = $member ?: Member::loggedIn();

		/* Are we looking at a member? */
		if( $member instanceof Member )
		{
			/* If we don't have edit permission in the category, check album restrictions */
			if( !Image::modPermission( 'edit', $member, $this->category() ) )
			{
				/* Deny access if the album is private and we aren't the owner */
				if( $this->type == static::AUTH_TYPE_PRIVATE AND $this->owner() !== $member )
				{
					return FALSE;
				}

				/* If this is a restricted album, check that we're "on the list" */
				if( $this->type == static::AUTH_TYPE_RESTRICTED AND $this->owner() !== $member )
				{
					try
					{
						if( !$member->member_id )
						{
							return FALSE;
						}

						Db::i()->select( '*', 'core_sys_social_group_members', array( 'group_id=? AND member_id=?', $this->allowed_access, $member->member_id ) )->first();
					}
					catch( UnderflowException )
					{
						return FALSE;
					}
				}
			}

			/* If we are checking 'add' permission, verify if we can add */
			if( $permission == 'add' )
			{
				/* First check we can submit to this album based on the "Submissions" setting */
				switch ( $this->submit_type )
				{
					/* Owner */
					case static::AUTH_SUBMIT_OWNER:
						if ( $this->owner_id != $member->member_id )
						{
							return FALSE;
						}
						break;
											
					/* Chosen groups */
					case static::AUTH_SUBMIT_GROUPS:
						if ( !$member->inGroup( explode( ',', $this->submit_access ) ) )
						{
							return FALSE;
						}
						break;
						
					/* Chosen members */
					case static::AUTH_SUBMIT_MEMBERS:
						if ( !in_array( $this->submit_access, $member->socialGroups() ) AND $this->owner_id != $member->member_id )
						{
							return FALSE;
						}
						break;
					
					/* Club */
					case static::AUTH_SUBMIT_CLUB:
						if ( $club = $this->category()->club() and !in_array( $club->id, $member->clubs() ) )
						{
							return FALSE;
						}
						break;
				}
				
				/* Verify if we can add any more files to this album */
				if( $member->group['g_img_album_limit'] AND $member->group['g_img_album_limit'] - ( $this->count_imgs + $this->count_imgs_hidden ) < 1 )
				{
					return FALSE;
				}
			}

			/* Albums can be hidden, so make sure to test permissions */
			if( $asItem->hidden() )
			{
				if( !$asItem->canView( $member ) )
				{
					return FALSE;
				}

				$methodToCheck = "can" . ucwords( $permission );

				if( method_exists( $asItem, $methodToCheck ) )
				{
					return $asItem->$methodToCheck( $member );
				}
				else
				{
					return $asItem->can( $permission, $member, $considerPostBeforeRegistering );
				}
			}
		}
		/* Or are we looking at a group? */
		else
		{
			if( $permission == 'add' )
			{
				/* Verify if the group is in the submit list */
				if( $this->submit_type == static::AUTH_SUBMIT_GROUPS AND !in_array( $member->g_id, explode( ',', $this->submit_access ) ) )
				{
					return FALSE;
				}

				/* Verify if we can add any more files to this album */
				if( $member->g_img_album_limit AND $member->g_img_album_limit - ( $this->count_imgs + $this->count_imgs_hidden ) < 1 )
				{
					return FALSE;
				}
			}

			/* Albums can be hidden, so make sure to test permissions */
			if( $asItem->hidden() )
			{
				if( !$asItem->can( 'view', $member, $considerPostBeforeRegistering ) )
				{
					return FALSE;
				}

				return $asItem->can( $permission, $member, $considerPostBeforeRegistering );
			}
		}

		/* We'll just rely on category permissions if the album isn't hidden */
		try
		{
			return $this->category()->can( $permission, $member, $considerPostBeforeRegistering );
		}
		catch( OutOfRangeException )
		{
			return FALSE;
		}
	}
	
	/**
	 * Search Index Permissions
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function searchIndexPermissions(): string
	{
		$return = $this->category()->searchIndexPermissions();
		
		if ( $this->type != static::AUTH_TYPE_PUBLIC )
		{
			$return = [];
			
			if ( $this->owner_id )
			{
				$return[] = "m{$this->owner_id}";
			}
			
			if ( $this->type == static::AUTH_TYPE_RESTRICTED )
			{
				$return[] = "s{$this->allowed_access}";
			}
			
			$return = implode( ',', array_unique( $return ) );
		}
		return $return;
	}

	/**
	 * Can Rate?
	 *
	 * @param	Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	bool
	 * @throws	BadMethodCallException
	 */
	public function canRate( Member $member = NULL ): bool
	{
		if( $this->_canRate( $member ) )
		{
			if( $this->category()->allow_rating )
			{
				return $this->category()->can( 'rate', $member );
			}
			else
			{
				return FALSE;
			}
		}

		return FALSE;
	}

	/**
	 * Get template for node tables
	 *
	 * @return callable|array
	 */
	public static function nodeTableTemplate(): callable|array
	{
		\IPS\gallery\Application::outputCss();
		
		return array( Theme::i()->getTemplate( 'browse', 'gallery' ), 'albums' );
	}
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse	int						id					ID number
	 * @apiresponse	string					name				Name
	 * @apiresponse	string					description			Description
	 * @apiresponse	\IPS\gallery\Category	category			The category
	 * @apiresponse	\IPS\Member				owner				The owner
	 * @apiresponse	string					privacy				'public', 'private' (can only be viewed by owner) or 'restricted' (can only be viewed by owner or approved members)
	 * @apiresponse	[\IPS\Member]			approvedMembers		If the album is restricted, the members who can view it, in addition to the owner and moderators with appropriate permission
	 * @apiresponse	int						images				Number of images
	 * @apiresponse	string					url					URL
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array(
			'id'				=> $this->id,
			'name'				=> $this->name,
			'description'		=> Member::loggedIn()->language()->addToStack("gallery_album_{$this->id}_desc"),
			'category'			=> $this->category()->apiOutput( $authorizedMember ),
			'owner'				=> $this->owner()->apiOutput( $authorizedMember ),
			'privacy'			=> ( $this->type == static::AUTH_TYPE_PUBLIC ) ? 'public' : ( $this->type == static::AUTH_TYPE_PRIVATE ? 'private' : ( $this->type == static::AUTH_TYPE_RESTRICTED ? 'restricted' : null ) ),
			'approvedMembers'	=> $this->approvedMembers ? array_map( function( $val ) use ( $authorizedMember ) {
				return $val->apiOutput( $authorizedMember );
			}, $this->approvedMembers ) : null,
			'images'			=> $this->count_imgs,
			'url'				=> (string) $this->url()
		);
	}
	
	/**
	 * Webhook filters
	 *
	 * @return	array
	 */
	public function webhookFilters(): array
	{
		$filters = array();
		$filters['privacy'] = ( $this->type == static::AUTH_TYPE_PUBLIC ) ? 'public' : ( $this->type == static::AUTH_TYPE_PRIVATE ? 'private' : ( $this->type == static::AUTH_TYPE_RESTRICTED ? 'restricted' : null ) );
		return $filters;
	}

	/**
	 * Get template for managing this nodes follows
	 *
	 * @return callable|array
	 */
	public static function manageFollowNodeRow(): callable|array
	{
		\IPS\gallery\Application::outputCss();
		
		return array( Theme::i()->getTemplate( 'global', 'gallery' ), 'manageFollowNodeRow' );
	}
	
	/**
	 * Get the title for a node using the specified language object
	 * This is commonly used where we cannot use the logged in member's language, such as sending emails
	 *
	 * @param Lang $language	Language object to fetch the title with
	 * @param	array 		$options	What options to use for language parsing
	 * @return	string
	 */
	public function getTitleForLanguage( Lang $language, array $options=array() ): string
	{
		return $this->name;
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
		$this->_items				= Db::i()->select( 'COUNT(*) as total', 'gallery_images', array( 'image_album_id=? AND image_approved=1', $this->id ) )->first();
		$this->_unapprovedItems		= Db::i()->select( 'COUNT(*) as total', 'gallery_images', array( 'image_album_id=? AND image_approved=0', $this->id ) )->first();

		if( $this->allow_comments )
		{
			$this->_comments			= Db::i()->select( 'COUNT(*) as total', 'gallery_comments', array( 'gallery_images.image_album_id=? AND comment_approved=1 AND gallery_images.image_approved=1', $this->id ) )->join( 'gallery_images', 'image_id=comment_img_id' )->first();
			$this->_unapprovedComments	= Db::i()->select( 'COUNT(*) as total', 'gallery_comments', array( 'gallery_images.image_album_id=? AND comment_approved=0', $this->id ) )->join( 'gallery_images', 'image_id=comment_img_id' )->first();
		}

		if( $this->use_comments )
		{
			/* And then get counts/latest data for the direct comments/reviews */
			$this->comments				= (int) Db::i()->select( 'COUNT(*) as total', 'gallery_album_comments', array( 'comment_album_id=? AND comment_approved=1', $this->id ) )->first();
			$this->comments_unapproved	= (int) Db::i()->select( 'COUNT(*) as total', 'gallery_album_comments', array( 'comment_album_id=? AND comment_approved=0', $this->id ) )->first();
			$this->comments_hidden		= (int) Db::i()->select( 'COUNT(*) as total', 'gallery_album_comments', array( 'comment_album_id=? AND comment_approved=-1', $this->id ) )->first();
		}

		if( $this->allow_reviews )
		{
			$this->_reviews	= Db::i()->select( 'COUNT(*) as total', 'gallery_reviews', array( 'gallery_images.image_album_id=? AND review_approved=1', $this->id ) )->join( 'gallery_images', 'image_id=review_image_id' )->first();
			$this->_unapprovedReviews	= Db::i()->select( 'COUNT(*) as total', 'gallery_reviews', array( 'gallery_images.image_album_id=? AND review_approved=0', $this->id ) )->join( 'gallery_images', 'image_id=review_image_id' )->first();
		}

		if( $this->use_reviews )
		{
			$this->reviews				= (int) Db::i()->select( 'COUNT(*) as total', 'gallery_album_reviews', array( 'review_album_id=? AND review_approved=1', $this->id ) )->first();
			$this->reviews_unapproved	= (int) Db::i()->select( 'COUNT(*) as total', 'gallery_album_reviews', array( 'review_album_id=? AND review_approved=0', $this->id ) )->first();
			$this->reviews_hidden		= (int) Db::i()->select( 'COUNT(*) as total', 'gallery_album_reviews', array( 'review_album_id=? AND review_approved=-1', $this->id ) )->first();
		}
	}
}