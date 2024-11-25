<?php
/**
 * @brief		Image Model
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
use Exception;
use GraphQL\Type\Definition\NullableType;
use IPS\Application;
use IPS\Content\Anonymous;
use IPS\Content\Comment;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Followable;
use IPS\Content\Hideable;
use IPS\Content\Lockable;
use IPS\Content\MetaData;
use IPS\Content\Featurable;
use IPS\Content\Pinnable;
use IPS\Content\Ratings;
use IPS\Content\Reactable;
use IPS\Content\ReadMarkers;
use IPS\Content\Reportable;
use IPS\Content\Shareable;
use IPS\Content\Tag;
use IPS\Content\Taggable;
use IPS\Content\ViewUpdates;
use IPS\Content\Statistics;
use IPS\Content\Item as ContentItem;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Email;
use IPS\File;
use IPS\gallery\Album\Item;
use IPS\gallery\Application as GalleryApplication;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Image as ImageClass;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Output;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfBoundsException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function array_slice;
use function count;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_null;
use function strpos;
use const IPS\NOTIFICATION_BACKGROUND_THRESHOLD;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Image Model
 *
 * @property array $metadata
 */
class Image extends ContentItem implements Embeddable,
Filter
{
	use Reactable,
		Reportable,
		Pinnable,
		Anonymous,
		Followable,
		Lockable,
		MetaData,
		Ratings,
		Shareable,
		Taggable,
		ReadMarkers,
		Hideable,
		Statistics,
		ViewUpdates,
		Featurable
		{
			Hideable::onHide as public _onHide;
			Hideable::onUnhide as public _onUnhide;
			Hideable::logDelete as public _logDelete;
			Hideable::approvalQueueHtml as public _approvalQueueHtml;
			Followable::notificationRecipients as public _notificationRecipients;
		}
	
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
	public static ?string $databaseTable = 'gallery_images';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'image_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	Node Class
	 */
	public static ?string $containerNodeClass = 'IPS\gallery\Category';

	/**
	 * @brief	Additional classes for following
	 */
	public static array $containerFollowClasses = array( 'category_id' => 'IPS\gallery\Category', 'album_id' => 'IPS\gallery\Album' );
	
	/**
	 * @brief	Comment Class
	 */
	public static ?string $commentClass = 'IPS\gallery\Image\Comment';

	/**
	 * @brief	Review Class
	 */
	public static string $reviewClass = 'IPS\gallery\Image\Review';

	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'container'				=> 'category_id',
		'author'				=> 'member_id',
		'views'					=> 'views',
		'title'					=> 'caption',
		'content'				=> 'description',
		'num_comments'			=> 'comments',
		'unapproved_comments'	=> 'unapproved_comments',
		'hidden_comments'		=> 'hidden_comments',
		'last_comment'			=> 'last_comment',
		'date'					=> 'date',
		'updated'				=> 'updated',
		'rating'				=> 'rating',
		'approved'				=> 'approved',
		'approved_by'			=> 'approved_by',
		'approved_date'			=> 'approved_on',
		'pinned'				=> 'pinned',
		'featured'				=> 'feature_flag',
		'locked'				=> 'locked',
		'ip_address'			=> 'ipaddress',
		'rating_average'		=> 'rating',
		'rating_total'			=> 'ratings_total',
		'rating_hits'			=> 'ratings_count',
		'num_reviews'			=> 'reviews',
		'unapproved_reviews'	=> 'unapproved_reviews',
		'hidden_reviews'		=> 'hidden_reviews',
		'meta_data'				=> 'meta_data',
		'is_anon'				=> 'is_anon',
		'last_comment_anon'		=> 'last_poster_anon',
	);
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'gallery_image';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'camera';
	
	/**
	 * @brief	Form Lang Prefix
	 */
	public static string $formLangPrefix = 'image_';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'gallery-image';
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns(): array
	{
		$return = parent::basicDataColumns();
		$return[] = 'image_masked_file_name';
		$return[] = 'image_original_file_name';
		$return[] = 'image_small_file_name';
		$return[] = 'image_album_id';
		$return[] = 'image_copyright';
		$return[] = 'image_nsfw';
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
		$albumIds = array();
		foreach ( $items as $itemData )
		{
			if ( $itemData['image_album_id'] )
			{
				$albumIds[ $itemData['image_id'] ] = $itemData['image_album_id'];
			}
		}
		
		if ( count( $albumIds ) )
		{
			$return = array();
			$albumData = iterator_to_array( Db::i()->select( array( 'album_id', 'album_name', 'album_name_seo' ), 'gallery_albums', Db::i()->in( 'album_id', $albumIds ) )->setKeyField( 'album_id' ) );
			
			foreach ( $albumIds as $imageId => $albumId )
			{
				$return[ $imageId ] = $albumData[ $albumId ];
			}
			
			return $return;
		}
		
		return array();
	}

	/**
	 * Get the meta data
	 *
	 * @return	array
	 */
	public function get_metadata(): array
	{
		/* Videos don't have EXIF data */
		if( !isset( $this->_data['metadata'] ) )
		{
			return array();
		}

		return is_array( $this->_data['metadata'] ) ? $this->_data['metadata'] : ( $this->_data['metadata'] ? json_decode( $this->_data['metadata'], TRUE ) : array() );
	}

	/**
	 * Get any image dimensions stored
	 *
	 * @return	array
	 */
	public function get__dimensions(): array
	{
		return is_array( $this->_data['data'] ) ? $this->_data['data'] : ( $this->_data['data'] ? json_decode( $this->_data['data'], TRUE ) : array() );
	}

	/**
	 * Set any image dimensions
	 *
	 * @param array $dimensions	Image dimensions to store
	 * @return	void
	 */
	public function set__dimensions( array $dimensions ) : void
	{
		$this->data	= json_encode( $dimensions );
	}

	/**
	 * Get any image notes stored (sorted for the javascript helper)
	 *
	 * @return	array
	 */
	public function get__notes(): array
	{
		return is_array( $this->_data['notes'] ) ? $this->_data['notes'] : ( $this->_data['notes'] ? json_decode( $this->_data['notes'], TRUE ) : array() );
	}
	
	/**
	 * Returns a JSON string of the notes data made safe for decoding in javascript.
	 *
	 * @return string
	 */
	public function get__notes_json(): string
	{
		/* We want to essentially double encode the entities so that when javascript decodes the JSON it is safe */
		if( $this->_notes and is_array( $this->_notes ) )
		{ 
			$notes = $this->_notes;
			array_walk( $notes, function( &$v, $k )
			{
				if ( ! empty( $v['NOTE'] ) )
				{
					$v['NOTE'] = htmlspecialchars( $v['NOTE'], ENT_DISALLOWED, 'UTF-8' );
				}
			} );
		}
		else
		{
			$notes = array();
		}

		return json_encode( $notes );
	}
	
	/**
	 * Set any image notes stored
	 *
	 * @param	array	$notes	Image notes to store
	 * @return	void
	 */
	public function set__notes( array $notes ) : void
	{
		$this->notes	= json_encode( $notes );
	}

	/**
	 * Get focal length
	 *
	 * @return	string
	 */
	public function get_focallength(): string
	{
		if( !isset( $this->metadata['EXIF.FocalLength'] ) )
		{
			return '';
		}

		$length	= $this->metadata['EXIF.FocalLength'];

		if( strpos( $length, '/' ) !== FALSE )
		{
			$bits	= explode( '/', $length );

			return Member::loggedIn()->language()->addToStack( 'gallery_focal_length_mm', FALSE, array( 'sprintf' => array( ( $bits[1] > 0 ) ? round( $bits[0] / $bits[1], 1 ) : $bits[0] ) ) );
		}
		else
		{
			return Member::loggedIn()->language()->addToStack( 'gallery_focal_length_mm', FALSE, array( 'sprintf' => array( $length ) ) );
		}
	}

	/**
	 * Set name
	 *
	 * @param string $name	Name
	 * @return	void
	 */
	public function set_caption( string $name ) : void
	{
		$this->_data['caption']		= $name;
		$this->_data['caption_seo']	= Friendly::seoTitle( $name );
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_caption_seo(): string
	{
		if( !$this->_data['caption_seo'] )
		{
			$this->caption_seo	= Friendly::seoTitle( $this->caption );
			$this->save();
		}

		return $this->_data['caption_seo'] ?: Friendly::seoTitle( $this->caption );
	}

	/**
	 * Get Small File Name
	 *
	 * @return	string|null
	 */
	public function get_small_file_name(): ?string
	{
		if( isset( $this->_data['small_file_name'] ) AND $this->_data['small_file_name'] )
		{
			return $this->_data['small_file_name'] ?? '';
		}
		else
		{
			return $this->masked_file_name ?? '';
		}
	}

	/**
	 * @return string
	 */
	public function get_masked_file_name() : string
	{
		/* Make sure this never returns null */
		return $this->_data['masked_file_name'] ?? '';
	}

	/**
	 * @return string
	 */
	public function get_original_file_name() : string
	{
		return $this->_data['original_file_name'] ?? '';
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=gallery&module=gallery&controller=view&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlTemplate = 'gallery_image';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'caption_seo';

	/* v5 todo: Can the below be removed now that we don't use patchwork? */
	/**
	 * Return selection of image data as a JSON-encoded string (used for patchwork)
	 *
	 * @param array $parameters		Optional key => value array of additional query string parameters to use with the image URL
	 * @return	string
	 */
	public function json( array $parameters=array() ): string
	{
		$imageSizes	= json_decode( $this->_data['data'], true );
		$state		= array();
		$modActions	= array();
		$modStates	= array();

		/* Some generic moderator permissions */
		if ( $this->canSeeMultiModTools() OR ( $this->container()->club() AND $this->container()->club()->isModerator() ) )
		{
			if( $this->canMove() )
			{
				$modActions[]	= "move";
			}
	
			if( $this->canDelete() )
			{
				$modActions[]	= "delete";
			}
	
			if( $this->mapped('locked') )
			{
				if( $this->canUnlock() )
				{
					$modActions[] = 'unlock';
				}
	
				$modStates[] = 'locked';
			}
			else if( $this->canLock() )
			{
				$modActions[] = 'lock';
			}

			if ( $this->mapped('pinned') )
			{
				if( $this->canUnpin() )
				{
					$modActions[] = 'unpin';
				}
	
				$state['pinned'] = TRUE;
				$modStates[] = 'pinned';
			}
			else if( $this->canPin() )
			{
				$modActions[] = 'pin';
			}
	
			/* Approve, hide or unhide */
			if ( $this->hidden() === -1 )
			{
				if( $this->canUnhide() )
				{
					$modActions[] = 'unhide';
				}
	
				$state['hidden'] = TRUE;
				$modStates[] = 'hidden';
			}
			elseif ( $this->hidden() === 1 )
			{
				if( $this->canUnhide() )
				{
					$modActions[] = 'approve';
				}

				if( $this->canHide() )
				{
					$modActions[] = 'hide';
				}
	
				$state['pending'] = TRUE;
				$modStates[] = 'unapproved';
			}
			else if( $this->canHide() )
			{
				$modActions[] = 'hide';
			}
		}

		/* Set read or unread status */
		if ( $this->unread() === -1 )
		{
			$unread = Member::loggedIn()->language()->addToStack( 'new' );
			$modStates[] = 'unread';
		}
		elseif( $this->unread() === 1 )
		{
			$unread = Member::loggedIn()->language()->addToStack( 'updated' );
			$modStates[] = 'unread';
		}
		else
		{
			$modStates[] = 'read';
		}	

		$modActions = implode( ' ', $modActions );
		$modStates = implode( ' ', $modStates );

		return json_encode( array(
			'filenames'		=> array(
				'small' 		=> array( $this->_data['small_file_name'] ? (string) File::get( 'gallery_Images', $this->_data['small_file_name'] )->url : null, $this->_data['small_file_name'] ? $imageSizes['small'][0] : null, $this->_data['small_file_name'] ? $imageSizes['small'][1] : null ),
				'large' 		=> array( $this->_data['masked_file_name'] ? (string) File::get( 'gallery_Images', $this->_data['masked_file_name'] )->url : null, $this->_data['masked_file_name'] ? $imageSizes['large'][0] : null, $this->_data['masked_file_name'] ? $imageSizes['large'][1] : null )
			),
			/* We do not use ENT_QUOTES as this replaces " to &quot; which browsers turn back into " again which breaks the JSON string as it needs to be \", single quotes break the data-attribute='' boundaries */
			'caption'		=> $this->_data['caption'],
			'date'			=> DateTime::ts( $this->mapped('date') )->relative(),
			'hasState'		=> (bool) count($state),
			'state'			=> $state,
			'container' 	=> ( $this->directContainer() instanceof Category) ? Member::loggedIn()->language()->addToStack( "gallery_category_{$this->directContainer()->_id}", false, array( 'json' => true, 'jsonEscape' => true ) ) : $this->directContainer()->_title,
			'id' 			=> $this->_data['id'],
			'url'			=> (string) $this->url()->setQueryString( $parameters ),
			'author'		=> array(
				'photo' 		=> (string) $this->author()->photo,
				'name'			=> $this->author()->name
			),
			'modActions'	=> $modActions,
			'modStates'		=> $modStates,
			'allowComments' => (boolean) $this->directContainer()->allow_comments,
			'comments'		=> ( $this->directContainer()->allow_comments ) ? $this->_data['comments'] : 0,
			'views'			=> $this->_data['views']
		), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS );
	}

	/**
	 * Get EXIF data
	 *
	 * @return	array
	 */
	public function exif(): array
	{
		return $this->metadata;
	}

	/**
	 * Get URL for last comment page
	 *
	 * @return	Url
	 */
	public function lastCommentPageUrl(): Url
	{
		return parent::lastCommentPageUrl()->setQueryString( 'tab', 'comments' );
	}

	/**
	 * Get template for content tables
	 *
	 * @return array
	 */
	public static function contentTableTemplate(): array
	{
		GalleryApplication::outputCss();
		
		return array( Theme::i()->getTemplate( 'browse', 'gallery', 'front' ), 'tableRowsRows' );
	}

	/**
	 * HTML to manage an item's follows 
	 *
	 * @return	array
	 */
	public static function manageFollowRows(): array
	{
		return array( Theme::i()->getTemplate( 'global', 'gallery', 'front' ), 'manageFollowRow' );
	}

	/**
	 * Get available comment/review tabs
	 *
	 * @return	array
	 */
	public function commentReviewTabs(): array
	{
		$tabs = array();

		if ( $this->container()->allow_reviews AND $this->directContainer()->allow_reviews )
		{
			$tabs['reviews'] = Member::loggedIn()->language()->addToStack( 'image_review_count', TRUE, array( 'pluralize' => array( $this->mapped('num_reviews') ) ) );
		}
		if ( $this->container()->allow_comments AND $this->directContainer()->allow_comments )
		{
			$tabs['comments'] = Member::loggedIn()->language()->addToStack( 'image_comment_count', TRUE, array( 'pluralize' => array( $this->mapped('num_comments') ) ) );
		}

		return $tabs;
	}

	/**
	 * Get comment/review output
	 *
	 * @param string|null $tab Active tab
	 * @param bool $condensed Use condensed style
	 * @return    string
	 */
	public function commentReviews( string $tab=NULL, ?bool $condensed=FALSE ): string
	{
		if ( $tab === 'reviews' AND $this->container()->allow_reviews AND $this->directContainer()->allow_reviews )
		{
			return (string) Theme::i()->getTemplate('view')->reviews( $this, $condensed );
		}
		elseif( $tab === 'comments' AND $this->container()->allow_comments AND $this->directContainer()->allow_comments )
		{
			return (string) Theme::i()->getTemplate('view')->comments( $this, $condensed );
		}

		return '';
	}

	/**
	 * Return the album node if the image belongs to an album, otherwise return the category
	 *
	 * @return    Model
	 */
	public function directContainer(): Model
	{
		if( $this->album_id )
		{
			return Album::load( $this->album_id );
		}

		return parent::directContainer();
	}

	/**
	 * Users to receive immediate notifications
	 *
	 * @param int|array|null $limit LIMIT clause
	 * @param bool $countOnly Just return the count
	 * @return Select|int
	 */
	public function notificationRecipients( int|array|null $limit=array( 0, 25 ), bool $countOnly=FALSE ): Select|int
	{
		/* Do we only want the count? */
		if( $countOnly )
		{
			$count	= 0;
			$count	+= $this->author()->followersCount( 3, array( 'immediate' ), $this->mapped('date') );
			$count	+= static::containerFollowerCount( $this->container(), 3, array( 'immediate' ), $this->mapped('date') );
			$count  += $this->tagsFollowerCount( 3, array( 'immediate' ) );

			if( get_class( $this->container() ) != get_class( $this->directContainer() ) )
			{
				$count	+= static::containerFollowerCount( $this->directContainer(), 3, array( 'immediate' ), $this->mapped('date') );
			}

			return $count;
		}

		/* Create a union query to return the followers */
		$unions	= array( static::containerFollowers( $this->container(), 3, array( 'immediate' ), $this->mapped('date'), NULL ) );

		if( get_class( $this->container() ) != get_class( $this->directContainer() ) )
		{
			$unions[]	= static::containerFollowers( $this->directContainer(), 3, array( 'immediate' ), $this->mapped('date'), NULL );
		}
		
		if ( $followersQuery = $this->author()->followers( 3, array( 'immediate' ), $this->mapped('date'), NULL ) )
		{
			$unions[] = $followersQuery;
		}
		
		return Db::i()->union( $unions, 'follow_added', $limit );
	}
	
	/**
	 * Users to receive immediate notifications (bulk)
	 *
	 * @param Category $category	The category the images were posted in.
	 * @param Album|NULL	$album		The album the images were posted in, or NULL for no album.
	 * @param	Member|NULL		$member		The member posting the images or NULL for currently logged in member.
	 * @param array|null	$tags
	 * @param	int|array|null		$limit		LIMIT clause
	 * @param	bool					$countOnly	Only return the count
	 * @return	Select|int
	 */
	public static function _imageNotificationRecipients( Category $category, ?Album $album=NULL, ?Member $member=NULL, ?array $tags=null, int|array|null $limit=array( 0, 25 ), bool $countOnly=FALSE ) : Select|int
	{
		$member = $member ?: Member::loggedIn();

		/* Do we only want the count? */
		if( $countOnly )
		{
			$count	= 0;
			$count	+= $member->followersCount( 3, array( 'immediate' ) );
			$count	+= static::containerFollowerCount( $category, 3, array( 'immediate' ) );

			if( $album )
			{
				$count	+= static::containerFollowerCount( $album, 3, array( 'immediate' ) );
			}

			return $count;
		}

		$unions = array( static::containerFollowers( $category, 3, array( 'immediate' ), NULL, NULL, 'follow_added' ) );
		
		if ( !is_null( $album ) )
		{
			$unions[] = static::containerFollowers( $album, 3, array( 'immediate' ), NULL, NULL, 'follow_added' );
		}
		
		if ( $followersQuery = $member->followers( 3, array( 'immediate' ), NULL, NULL ) )
		{
			$unions[] = $followersQuery;
		}

		return Db::i()->union( $unions, 'follow_added', $limit );
	}
	
	/**
	 * Send Notifications (bulk)
	 *
	 * @param Category $category	The category the images were posted in.
	 * @param Album|NULL	$album		The album the images were posted in, or NULL for no album.
	 * @param	Member|NULL		$member		The member posting the images, or NULL for currently logged in member.
	 * @param	array|null	$tags
	 * @return	void
	 */
	public static function _sendNotifications( Category $category, ?Album $album=NULL, ?Member $member=NULL, ?array $tags=NULL ) : void
	{
		$member = $member ?: Member::loggedIn();
		try
		{
			$count = static::_imageNotificationRecipients( $category, $album, $member, $tags, NULL, TRUE );
		}
		catch( BadMethodCallException )
		{
			return;
		}
		
		$categoryIdColumn	= $category::$databaseColumnId;
		$albumIdColumn		= $album ? $album::$databaseColumnId : NULL;
		
		if ( $count > NOTIFICATION_BACKGROUND_THRESHOLD )
		{
			$queueData = array(
				'followerCount'		=> $count,
				'category_id'		=> $category->$categoryIdColumn,
				'member_id'			=> $member->member_id,
				'album_id'			=> $album?->$albumIdColumn,
				'tags'				=> $tags
			);

			Task::queue( 'gallery', 'Follow', $queueData, 2 );
		}
		else
		{
			static::_sendNotificationsBatch( $category, $album, $member, $tags );
		}
	}
	
	/**
	 * Send Unapproved Notification (bulk)(
	 *
	 * @param Category $category	The category the images were posted too.
	 * @param Album|NULL	$album		The album the images were posted too, or NULL for no album.
	 * @param	Member|NULL		$member		The member posting the images, or NULL for currently logged in member.
	 * @return	void
	 */
	public static function _sendUnapprovedNotifications( Category $category, ?Album $album=NULL, ?Member $member=NULL ) : void
	{
		$member = $member ?: Member::loggedIn();
		
		$directContainer = $album ?: $category;
		
		$moderators = array( 'g' => array(), 'm' => array() );
		foreach( Db::i()->select( '*', 'core_moderators' ) AS $mod )
		{
			$canView = FALSE;
			if ( $mod['perms'] == '*' )
			{
				$canView = TRUE;
			}
			if ( $canView === FALSE )
			{
				$perms = json_decode( $mod['perms'], TRUE );
				
				if ( isset( $perms['can_view_hidden_content'] ) AND $perms['can_view_hidden_content'] )
				{
					$canView = TRUE;
				}
				else if ( isset( $perms['can_view_hidden_' . static::$title ] ) AND $perms['can_view_hidden_' . static::$title ] )
				{
					$canView = TRUE;
				}
			}
			if ( $canView === TRUE )
			{
				$moderators[ $mod['type'] ][] = $mod['id'];
			}
		}
		
		$notification = new Notification( Application::load('core'), 'unapproved_content_bulk', $directContainer, array( $directContainer, $member, $directContainer::$contentItemClass ), array( $member->member_id ) );
		foreach ( Db::i()->select( '*', 'core_members', ( count( $moderators['m'] ) ? Db::i()->in( 'member_id', $moderators['m'] ) . ' OR ' : '' ) . Db::i()->in( 'member_group_id', $moderators['g'] ) . ' OR ' . Db::i()->findInSet( 'mgroup_others', $moderators['g'] ) ) as $moderator )
		{
			$notification->recipients->attach( Member::constructFromData( $moderator ) );
		}
		$notification->send();
	}
	
	/**
	 * Send Notification Batch (bulk)
	 *
	 * @param Category $category	The category the images were posted too.
	 * @param Album|NULL	$album		The album the images were posted too, or NULL for no album.
	 * @param	Member|NULL		$member		The member posting the images, or NULL for currently logged in member.
	 * @param array|null	$tags
	 * @param	int						$offset		Offset
	 * @return	int|NULL				New Offset or NULL if complete
	 */
	public static function _sendNotificationsBatch( Category $category, ?Album $album=NULL, ?Member $member=NULL, ?array $tags=null, int $offset=0 ) : ?int
	{
		/* Check notification initiator spam status */
		if( ( $member instanceof Member ) AND $member->members_bitoptions['bw_is_spammer'] )
		{
			/* Initiator is flagged as spammer, don't send notifications */
			return NULL;
		}

		$member				= $member ?: Member::loggedIn();
		$directContainer	= $album ?: $category;
		
		$followIds = array();
		$followers = iterator_to_array( static::_imageNotificationRecipients( $category, $album, $member, $tags, array( $offset, static::NOTIFICATIONS_PER_BATCH ) ) );

		if( !count( $followers ) )
		{
			return NULL;
		}
		
		$notification = new Notification( Application::load( 'core' ), 'new_content_bulk', $directContainer, array( $directContainer, $member, $directContainer::$contentItemClass ), array( $member->member_id ) );
		
		foreach( $followers AS $follower )
		{
			$followMember = Member::load( $follower['follow_member_id'] );
			if ( $followMember !== $member and $directContainer->can( 'view', $followMember ) )
			{
				$followIds[] = $follower['follow_id'];
				$notification->recipients->attach( $followMember, $follower );
			}
		}

		Db::i()->update( 'core_follow', array( 'follow_notify_sent' => time() ), Db::i()->in( 'follow_id', $followIds ) );
		$notification->send();
		
		return $offset + static::NOTIFICATIONS_PER_BATCH;
	}

	/**
	 * Does this image need next/previous icons?
	 *
	 * @return bool
	 */
	public function hasPreviousOrNext(): bool
	{
		$images = $this->directContainer()->_items;

		if ( static::canViewHiddenItems( NULL, $this->directContainer() ) )
		{
			$images += $this->directContainer()->_unapprovedItems;
		}

		return $images > 1;
	}

	/**
	 * Return the first or last image in this album or category
	 *
	 * @param string $which
	 * @return ActiveRecord|null
	 */
	public function fetchFirstOrLast( string $which='first') : ActiveRecord|null
	{
		$where	= array();
		$direction = $which == 'first' ? 'DESC' : 'ASC';

		if( $this->album_id )
		{
			$where[]	= array( 'image_album_id=?', $this->album_id );
			$sortBy		= static::$databaseColumnMap[ $this->directContainer()->_sortBy ?: 'updated' ];
		}
		else
		{
			$where[]	= array( 'image_category_id=?', $this->category_id );
			$where[]	= array( 'image_album_id=?', 0 );
			$sortBy		= static::$databaseColumnMap[ $this->directContainer()->sort_options_img ?: 'updated' ];
		}

		if( $sortBy == 'caption' )
		{
			$direction	= ( $direction == 'ASC' ) ? 'DESC' : 'ASC';
		}

		foreach( static::getItemsWithPermission( $where, static::$databasePrefix . $sortBy . ' ' . $direction . ', image_id ' . $direction, 1 ) as $image )
		{
			return $image;
		}

		return NULL;
	}

	/**
	 * Get the next or previous 5 images in the container
	 *
	 * @param	int		$count		(Maximum) number of images to return
	 * @param	string	$direction	DESC or ASC
	 * @return	array
	 */
	public function fetchNextOrPreviousImages( int $count, string $direction = 'DESC' ) : array
	{
		$where	= array();
		$dir	= $direction == 'DESC' ? '<' : '>';

		if( $this->album_id )
		{
			$where[]	= array( 'image_album_id=?', $this->album_id );
			$sortBy		= static::$databaseColumnMap[ $this->directContainer()->_sortBy ?: 'updated' ];
		}
		else
		{
			$where[]	= array( 'image_category_id=?', $this->category_id );
			$where[]	= array( 'image_album_id=?', 0 );
			$sortBy		= static::$databaseColumnMap[ $this->directContainer()->sort_options_img ?: 'updated' ];
		}

		$where['id']	= array( 'image_id<>?', $this->id );
		
		if( in_array( $sortBy, array( 'caption', 'rating' ) ) )
		{
			if( $sortBy == 'caption' )
			{
				$direction	= ( $direction == 'ASC' ) ? 'DESC' : 'ASC';
				$dir		= ( $dir == '>' ) ? '<' : '>';
			}

			/* We need to sort on a unique value */
			$sortValue = $this->$sortBy . $this->updated;
			$where['date']	= array( "CONCAT(image_{$sortBy},image_updated) {$dir}= ?", $sortValue );
			$sortBy = 'image_' . $sortBy . ' ' . $direction. ',image_updated';
		}
		elseif( in_array( $sortBy, array( 'date', 'updated' ) ) )
		{
			/* We need to sort on a unique value */
			$sortValue = $this->$sortBy . $this->id;
			$where['date']	= array( "CONCAT(image_{$sortBy},image_id) {$dir}= ?", $sortValue );
			$sortBy = 'image_' . $sortBy . ' ' . $direction. ',image_id';
		}
		else
		{
			$sortValue = $this->$sortBy;
			$sortBy = static::$databasePrefix . $sortBy;
			$where['date']	= array( $sortBy . " {$dir}= ?", $sortValue );
		}

		return iterator_to_array( static::getItemsWithPermission( $where, $sortBy . ' ' . $direction, $count, 'read', null, 0, null, false, false, false, false, null, false, false, false, false  ) );
	}

	/**
	 * Get Next Item
	 *
	 * @param string|null $context	Context to consider next/previous from
	 * @return    static|NULL
	 */
	public function nextItem( string $context=NULL ): static|NULL
	{
		if( $context !== NULL )
		{
			$results = NULL;
			switch( $context )
			{
				case 'featured':
					$results	= iterator_to_array( static::featured( 20, NULL ) );
				break;

				case 'new':
					$results	= iterator_to_array( static::getItemsWithPermission( static::clubImageExclusion(), NULL, 30, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, !Settings::i()->club_nodes_in_apps ) );
				break;
			}

			if( $results !== NULL )
			{
				$returnNext = FALSE;
				foreach( $results as $imageResult )
				{
					if( $returnNext === TRUE )
					{
						return $imageResult;
					}

					if( $imageResult->id == $this->id )
					{
						$returnNext = TRUE;
					}
				}
			}

			return NULL;
		}

		$result = $this->fetchNextOrPreviousImages( 1, 'DESC' );

		if( count( $result ) )
		{
			return array_pop( $result );
		}

		return null;
	}
	
	/**
	 * Get Previous Item
	 *
	 * @param string|null $context	Context to consider next/previous from
	 * @return    static|NULL
	 */
	public function prevItem( string $context=NULL ): static|NULL
	{
		if( $context !== NULL )
		{
			$results = NULL;
			switch( $context )
			{
				case 'featured':
					$results	= iterator_to_array( static::featured( 20, NULL ) );
				break;

				case 'new':
					$results	= iterator_to_array( static::getItemsWithPermission( static::clubImageExclusion(), NULL, 30, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, !Settings::i()->club_nodes_in_apps ) );
				break;
			}

			if( $results !== NULL )
			{
				$previousResult = NULL;
				foreach( $results as $imageResult )
				{
					if( $imageResult->id == $this->id )
					{
						return $previousResult;
					}

					$previousResult = $imageResult;
				}
			}

			return NULL;
		}

		$result = $this->fetchNextOrPreviousImages( 1, 'ASC' );

		if( count( $result ) )
		{
			return array_pop( $result );
		}

		return null;
	}
	
	/**
	 * Get HTML for search result display
	 *
	 * @return	callable
	 */
	public function approvalQueueHtml( $ref, $container, $title ): mixed
	{
		return Theme::i()->getTemplate( 'global', 'gallery', 'front' )->approvalQueueItem( $this, $ref, $container, $title );
	}

	/**
	 * Get elements for add/edit form
	 *
	 * @param Item|null $item The current item if editing or NULL if creating
	 * @param Model|null $container Container (e.g. forum) ID, if appropriate
	 * @param int|null $currentlyEditing If this is for a new submission, the index ID of the image in the array
	 * @param int|null $tempId If this is for a new submission, the temporary image ID
	 * @return    array
	 */
	public static function formElements( ContentItem $item=NULL, Model $container=NULL, int $currentlyEditing=NULL, int $tempId=NULL ): array
	{
		/* Init */
		$return = parent::formElements( $item, $container );

		/* The submission process requires container to be chosen first */
		unset( $return['container'] );

		/* Some other details */
		$return['description']	= new Editor( 'image_description', $item?->description, FALSE, array(
			'app' 			=> 'gallery',
			'key' 			=> 'Images',
			'autoSaveKey' 	=> ( $item === NULL ? NULL : ( 'contentEdit-' . static::$application . '/' . static::$module . '-' . $item->id ) ),
			'editorId'		=> ( $item === NULL ) ? "filedata_{$currentlyEditing}_image_description" : 'image_description'
		) );
		$return['credit_info']	= new TextArea( 'image_credit_info', $item?->credit_info, FALSE );
		$return['copyright']	= new Text( 'image_copyright', $item?->copyright, FALSE, array( 'maxLength' => 255 ) );

		if( Settings::i()->gallery_nsfw )
		{
			$return['nsfw']	= new YesNo( 'image_nsfw', $item ? $item->nsfw : FALSE );
		}

		/* If we are editing, return the appropriate fields */
		if( $item )
		{
			/* Is this a media file, or an image? */
			if( $item->media )
			{
				$return['imageLocation'] = new Upload( 'mediaLocation', File::get( 'gallery_Images', $item->original_file_name ), TRUE, array(
					'storageExtension'	=> 'gallery_Images', 
					'allowedFileTypes'	=> array( 'flv', 'f4v', 'wmv', 'mpg', 'mpeg', 'mp4', 'mkv', 'm4a', 'm4v', '3gp', 'mov', 'avi', 'webm', 'ogg' ), 
					'multiple'			=> FALSE, 
					'minimize'			=> TRUE,
					/* 'template' => "...",		// This is the javascript template for the submission form */ 
					/* This has to be converted from kB to mB */
					'maxFileSize'		=> Member::loggedIn()->group['g_movie_size'] ? ( Member::loggedIn()->group['g_movie_size'] / 1024 ) : NULL,
				) );

				$return['image_thumbnail'] = new Upload( 'image_thumbnail', $item->masked_file_name ? File::get( 'gallery_Images', $item->masked_file_name ) : NULL, FALSE, array(
					'storageExtension'	=> 'gallery_Images', 
					'image'				=> TRUE, 
					'multiple'			=> FALSE, 
					'minimize'			=> TRUE,
					/* 'template' => "...",		// This is the javascript template for the submission form */ 
					/* This has to be converted from kB to mB */
					'maxFileSize'		=> Member::loggedIn()->group['g_max_upload'] ? ( Member::loggedIn()->group['g_max_upload'] / 1024 ) : NULL,
					'canBeModerated'		=> TRUE
				) );
			}
			else
			{
				$return['imageLocation'] = new Upload( 'imageLocation', File::get( 'gallery_Images', $item->original_file_name ), TRUE, array(
					'storageExtension'	=> 'gallery_Images', 
					'image'				=> TRUE, 
					'multiple'			=> FALSE, 
					'minimize'			=> TRUE,
					/* 'template' => "...",		// This is the javascript template for the submission form */ 
					/* This has to be converted from kB to mB */
					'maxFileSize'		=> Member::loggedIn()->group['g_max_upload'] ? ( Member::loggedIn()->group['g_max_upload'] / 1024 ) : NULL,
					'canBeModerated'		=> TRUE
				) );
			}
		}
		
		return $return;
	}

	/**
	 * Process after the object has been edited on the front-end
	 *
	 * @param array $values		Values from form
	 * @return	void
	 */
	public function processAfterEdit( array $values ): void
	{
		parent::processAfterEdit( $values );

		Request::i()->setClearAutosaveCookie( 'contentEdit-' . static::$application . '/' . static::$module . '-' . $this->id );
	}

	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( array $values ): void
	{
		parent::processForm( $values );

		/* Set a few details */
		$oldContent = NULL;
		if ( isset( $values['image_description'] ) )
		{
			if ( !$this->_new )
			{
				$oldContent = $this->description;
			}
			$this->description	= $values['image_description'];
		}
		if ( isset( $values['image_copyright'] ) )
		{
			$this->copyright	= $values['image_copyright'];
		}
		if ( isset( $values['image_credit_info'] ) )
		{
			$this->credit_info	= $values['image_credit_info'];
		}

		if ( isset( $values['image_nsfw'] ) )
		{
			$this->nsfw	= $values['image_nsfw'];
		}
		
		/* If we are editing and have a movie, update it */
		if( isset( $values['mediaLocation'] ) )
		{
			$values['imageLocation']	= $values['mediaLocation'];
		}

		if ( isset( $values['labels'] ) )
		{
			$this->labels	= $values['labels'];
		}

		/* Get the file... */
		$file = NULL;

		if( isset( $values['imageLocation'] ) AND $values['imageLocation'] and $values['imageLocation'] != $this->original_file_name) 
		{
			$file = File::get( 'gallery_Images', $values['imageLocation'] );
			if ( isset( $values['imageRequiresModeration'] ) )
			{
				$file->requiresModeration = $values['imageRequiresModeration'];
			}
			$this->original_file_name	= (string) $file;

			/* Get some details about the file */
			$this->file_size	= $file->filesize();
			$this->file_name	= $file->originalFilename;
			$this->file_type	= File::getMimeType( $file->filename );

			/* If this is an image, grab EXIF data and create thumbnails */
			if ( $file->isImage() )
			{
				/* Extract EXIF data if possible */
				if( ImageClass::exifSupported() )
				{
					$this->metadata	= ( isset( $values['_exif'] ) ) ? $values['_exif'] : ImageClass::create( $file->contents() )->parseExif();

					/* And then parse geolocation data */
					if( count( $this->metadata ) )
					{
						$this->parseGeolocation();

						$this->gps_show		= ( isset( $values['image_gps_show'] ) ) ? $values['image_gps_show'] : 0;
					}

					/* We need to do this after parsing the geolocation data */
					$metadata	= $this->metadata;

					$this->metadata	= json_encode( $metadata );
				}

				/* Create the various thumbnails */
				$this->buildThumbnails( $file );
			}
			else
			{
				/* This is a media file */
				$this->media	= 1;
			}
		}

		/* Manage the thumbnail */
		if( isset( $values['image_thumbnail'] ) and $values['image_thumbnail'] and ( $this->new or $values['image_thumbnail'] != $this->masked_file_name ) )
		{
			$file = File::get( 'gallery_Images', $values['image_thumbnail'] );

			/* Create the various thumbnails */
			$this->buildThumbnails( $file );

			$file->delete();
		}
		/* Or was the thumbnail removed? */
		elseif( !$this->_new AND $this->masked_file_name and isset( $values['image_thumbnail'] ) and ! $values['image_thumbnail'] )
		{
			foreach( array( 'masked_file_name', 'small_file_name' ) as $key )
			{
				if( $this->$key )
				{
					File::get( 'gallery_Images', $this->$key )->delete();

					$this->$key = NULL;
				}
			}
		}

		
		/* Check profanity filters */
		$filesForImageScanner = [];
		if ( ( isset( $values['imageLocation'] ) AND $values['imageLocation'] AND ( $values['imageLocation'] instanceof File ) ) )
		{
			$filesForImageScanner[] = $values['imageLocation'];
		}
		elseif ( $file )
		{
			$filesForImageScanner[] = $file;
		}
		if ( $this->media and isset( $values['image_thumbnail'] ) and $values['image_thumbnail'] )
		{
			if ( $values['image_thumbnail'] instanceof File )
			{
				$filesForImageScanner[] = $values['image_thumbnail'];
			}
			else
			{
				$thumbnail = File::get( 'gallery_Images', $values['image_thumbnail'] );
				if ( isset( $values['image_thumbnail_requires_moderation'] ) and $values['image_thumbnail_requires_moderation'] )
				{
					$thumbnail->requiresModeration = TRUE;
				}
				$filesForImageScanner[] = $thumbnail;
			}
		}

		$sendFilterNotifications = $this->checkProfanityFilters( FALSE, !$this->_new, NULL, NULL, NULL, NULL, $filesForImageScanner );
		if ( $oldContent AND $sendFilterNotifications === FALSE )
		{
			$this->sendAfterEditNotifications( $oldContent );
		}
	}

	/**
	 * Process created object BEFORE the object has been created
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	protected function processBeforeCreate( array $values ): void
	{
		$this->category_id	= ( isset( $values['category'] ) ) ? $values['category'] : Album::load( $values['album'] )->category()->_id;

		if( isset( $values['album'] ) )
		{
			$this->album_id	= $values['album'];
		}

		parent::processBeforeCreate( $values );
	}

	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param Comment|NULL	$comment	The first comment
	 * @param	array		$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( Comment|null $comment, array $values ): void
	{
		parent::processAfterCreate( $comment, $values );

		if( $this->album_id )
		{
			if( $this->hidden() === 0 )
			{
				$this->directContainer()->_items = ( $this->directContainer()->_items + 1 );
			}
			elseif( $this->hidden() === 1 )
			{
				$this->directContainer()->_unapprovedItems = ( $this->directContainer()->_unapprovedItems + 1 );
			}
			$this->directContainer()->save();
		}
	}

	/**
	 * Attempt to parse geolocation data from EXIF data
	 *
	 * @return	void
	 */
	public function parseGeolocation() : void
	{
		if( isset( $this->metadata['GPS.GPSLatitudeRef'] ) && isset( $this->metadata['GPS.GPSLatitude'] ) && isset( $this->metadata['GPS.GPSLongitudeRef'] ) && isset( $this->metadata['GPS.GPSLongitude'] ) )
		{
			$this->gps_lat		= $this->_getCoordinates( $this->metadata['GPS.GPSLatitudeRef'], $this->metadata['GPS.GPSLatitude'] );
			$this->gps_lon		= $this->_getCoordinates( $this->metadata['GPS.GPSLongitudeRef'], $this->metadata['GPS.GPSLongitude'] );

			try
			{
				$this->gps_raw		= GeoLocation::getByLatLong( $this->gps_lat, $this->gps_lon );
				$this->loc_short	= (string) $this->gps_raw;
				$this->gps_raw		= json_encode( $this->gps_raw );
			}
			catch( Exception ) {}
		}
	}

	/**
	 * Convert the coordinates stored in EXIF to lat/long
	 *
	 * @param	string	$ref	Reference (N, S, W, E)
	 * @param	array	$degree	Degrees
	 * @return	string
	 */
	protected function _getCoordinates( string $ref, array $degree ) : string
	{
		return ( ( $ref == 'S' || $ref == 'W' ) ? '-' : '' ) . sprintf( '%.6F', $this->_degreeToInteger( $degree[0] ) + ( ( ( $this->_degreeToInteger( $degree[1] ) * 60 ) + ( $this->_degreeToInteger( $degree[2] ) ) ) / 3600 ) );
	}

	/**
	 * Convert the degree value stored in EXIF to an integer
	 *
	 * @param	string	$coordinate	Coordinate
	 * @return	string
	 */
	protected function _degreeToInteger( string $coordinate ) : string
	{
		if ( mb_strpos( $coordinate, '/' ) === false )
		{
			return sprintf( '%.6F', $coordinate );
		}
		else
		{
			[ $base, $divider ]	= explode( "/", $coordinate, 2 );
			
			if ( $divider == 0 )
			{
				return sprintf( '%.6F', 0 );
			}
			else
			{
				return sprintf( '%.6F', ( $base / $divider ) );
			}
		}
	}
	
	/**
	 * Delete existing thumbnails prior to rebuilding or deleting (does not delete the main image in original_file_name)
	 *
	 * @return	void
	 */
	public function deleteThumbnails() : void
	{
		/* We don't delete thumbnails for videos */
		if( $this->media )
		{
			return;
		}

		foreach( array( 'masked_file_name', 'small_file_name' ) as $size )
		{
			if( isset( $this->_data[ $size ] ) AND $this->$size AND $this->$size != $this->original_file_name )
			{
				try
				{
					File::get( 'gallery_Images', $this->$size )->delete();
				}
				catch( Exception ){}
			}
		}
	}

	/**
	 * Build the copies of the image with watermark as appropriate
	 *
	 * @param	File|NULL	$file	Base file to create from (if not supplied it will be found automatically)
	 * @return	void
	 */
	public function buildThumbnails( ?File $file=NULL ) : void
	{
		$this->deleteThumbnails();

		if( $file === NULL )
		{
			$file	= File::get( 'gallery_Images', $this->original_file_name );
		}

		$thumbnailDimensions	= array();
		$watermarks = explode( ',', Settings::i()->gallery_watermark_images );

		/* Create the various thumbnails - For animated gifs use the original image for the large version */
		$largeImage				= $file->isAnimatedImage() ? $file : File::create( 'gallery_Images', 'large.' . $file->originalFilename, $this->createImageFile( $file, explode( 'x', Settings::i()->gallery_large_dims ), FALSE, in_array( 'large', $watermarks ) ), $file->container );
		$this->masked_file_name	= (string) $largeImage;

		$thumbnailDimensions['large']	= $largeImage->getImageDimensions();

		$smallImage				= File::create( 'gallery_Images', 'small.' . $file->originalFilename, $this->createImageFile( $file, explode( 'x', Settings::i()->gallery_small_dims ), Settings::i()->gallery_use_square_thumbnails, in_array( 'small', $watermarks ) ), $file->container );
		$this->small_file_name	= (string) $smallImage;

		$thumbnailDimensions['small']	= $smallImage->getImageDimensions();

		$this->_dimensions			= $thumbnailDimensions;
	}

	/**
	 * Create image object and apply watermark, if appropriate
	 *
	 * @param	File	$file			Base file to create from
	 * @param	array|NULL	$dimensions		Dimensions to resize to, or NULL to not resize
	 * @param	bool		$crop			Whether to crop (true) or resize (false)
	 * @param	bool		$watermark		Watermark the created image
	 * @return    ImageClass
	 */
	public function createImageFile( File $file, ?array $dimensions, bool $crop=FALSE, bool $watermark=TRUE ) : ImageClass
	{
		$image	= ImageClass::create( $file->contents() );

		if( $dimensions !== NULL )
		{
			if( $crop )
			{
				//$image->crop( $dimensions[0], $dimensions[1] );
				$image->resizeToMax( $dimensions[0], $dimensions[0] );
			}
			else
			{
				$image->resizeToMax( $dimensions[0], $dimensions[1] );
			}
		}

        if( $watermark and Settings::i()->gallery_use_watermarks and Settings::i()->gallery_watermark_path AND $this->container()->watermark )
        {
            try
            {
                $image->watermark( ImageClass::create( File::get( 'core_Theme', Settings::i()->gallery_watermark_path )->contents() ) );
            }
            catch ( RuntimeException )
            {
                throw new RuntimeException( 'WATERMARK_DOES_NOT_EXIST' );
            }
        }

		return $image;
	}

	/**
	 * Return the map for the image if available
	 *
	 * @param	int		$width	Width
	 * @param	int		$height	Height
	 * @return	string
	 * @note	\BadMethodCallException can be thrown if the google maps integration is shut off - don't show any error if that happens.
	 */
	public function map( int $width, int $height ): string
	{
		if( $this->gps_raw )
		{
			try
			{
				return GeoLocation::buildFromJson( $this->gps_raw )->map()->render( $width, $height );
			}
			catch( BadMethodCallException ){}
		}

		return '';
	}

	/**
	 * Return the form to enable the map
	 *
	 * @param	bool	$lightbox	Is this for the lightbox?
	 * @return	string
	 */
	public function enableMapForm( bool $lightbox = FALSE ) : string
	{
		if( $this->canEdit() )
		{
			/* We do this to prevent a javascript error from having two elements on the same page with the same name/id */
			$setting = $lightbox ? "map_enabled_lightbox" : "map_enabled";

			$form	= new Form;
			$form->class = 'ipsForm--vertical ipsForm--enable-map';
			$form->add( new YesNo( $setting, $this->gps_show, FALSE ) );

			if( $values = $form->values() )
			{
				$this->gps_show	= $values[ $setting ];
				$this->save();
				Output::i()->redirect( $this->url() );
			}

			return $form;
		}

		return '';
	}
	
	/**
	 * Get available sizes
	 *
	 * @return	array
	 */
	public function sizes() : array
	{
		$return	= array();
		$data	= json_decode( $this->data, TRUE );

		if( !empty( $data ) )
		{
			foreach ( $data as $k => $v )
			{
				if ( !in_array( $v, $return ) )
				{
					$return[ $k ] = $v;
				}
			}
		}

		return $return;
	}

	/**
	 * Log for deletion later
	 *
	 * @param	Member|null 	$member	The member, NULL for currently logged in, or FALSE for no member
	 * @return	void
	 */
	public function logDelete( ?Member $member = NULL ) : void
	{
		$this->_logDelete( $member );

		/* Now we need to update "last image" info */
		if( $this->album_id )
		{
			$album	= Album::load( $this->album_id );
			$album->setLastImage();
			$album->save();
		}
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();
		
		/* Delete files */
		$this->deleteThumbnails();
		if( $this->original_file_name )
		{
			try
			{
				File::get( 'gallery_Images', $this->original_file_name )->delete();
			}
			catch( Exception ){}
		}

		/* Delete bandwidth logs */
		Db::i()->delete( 'gallery_bandwidth', array( 'image_id=?', $this->id ) );

		/* Remove cover id association */
		Db::i()->update( 'gallery_albums', array( 'album_cover_img_id' => 0 ), array( 'album_cover_img_id=?', $this->id ) );
		Db::i()->update( 'gallery_categories', array( 'category_cover_img_id' => 0 ), array( 'category_cover_img_id=?', $this->id ) );

		/* Now we need to update "last image" info */
		if( $this->album_id )
		{
			$album	= Album::load( $this->album_id );
			$album->setLastImage();
			$album->save();
		}
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
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return    array
	 * @apiresponse	int						id				ID number
	 * @apiresponse	string					caption			Caption
	 * @apiresponse	string					description		Description
	 * @apiresponse	string					filename		Original file name (e.g. 'image.png')
	 * @apiresponse	int						filesize		Original file size, in bytes
	 * @apiresponse	object					images			URLs to where the images are stored. Keys are 'original', 'large', and 'small', and values are URLs to the corresponding images
	 * @apiresponse	\IPS\gallery\Album		album			The album, if in one
	 * @apiresponse	\IPS\gallery\Category	category		The category (if in an album, this will be the category that the album is in)
	 * @apiresponse	\IPS\Member				author			The author
	 * @apiresponse	string					copyright		Copyright
	 * @apiresponse	string					credit			Credit
	 * @apiresponse	\IPS\GeoLocation		location		The location where the picture was taken, if it was able to be retreived from the EXIF data
	 * @apiresponse	object					exif			The raw EXIF data
	 * @apiresponse	datetime				date			Date image was uploaded
	 * @apiresponse	int						comments		Number of comments
	 * @apiresponse	int						reviews			Number of reviews
	 * @apiresponse	int						views			Number of views
	 * @apiresponse	string					prefix			The prefix tag, if there is one
	 * @apiresponse	[string]				tags			The tags
	 * @apiresponse	bool					locked			Image is locked
	 * @apiresponse	bool					hidden			Image is hidden
	 * @apiresponse	bool					featured		Image is featured
	 * @apiresponse	bool					pinned			Image is pinned
	 * @apiresponse	string					url				URL
	 * @apiresponse	float					rating			Average Rating
	 * @apiresponse	bool					nsfw			Not safe for work
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{				
		return array(
			'id'				=> $this->id,
			'caption'			=> $this->caption,
			'description'		=> $this->description,
			'filename'			=> $this->file_name,
			'filesize'			=> intval( $this->file_size ),
			'images'			=> array(
				'original'			=> (string) File::get( 'gallery_Images', $this->original_file_name )->url,
				'large'				=> (string) File::get( 'gallery_Images', $this->masked_file_name )->url,
				'small'				=> (string) File::get( 'gallery_Images', $this->small_file_name )->url,
			),
			'album'				=> $this->album_id ? $this->directContainer()->apiOutput() : null,
			'category'			=> $this->container()->apiOutput(),
			'author'			=> $this->author()->apiOutput(),
			'copyright'			=> $this->copyright ?: null,
			'credit'			=> $this->credit_info ?: null,
			'location'			=> $this->gps_raw ? GeoLocation::buildFromJson( $this->gps_raw ) : null,
			'exif'				=> $this->metadata ?: null,
			'date'				=> DateTime::ts( $this->date )->rfc3339(),
			'comments'			=> $this->comments,
			'reviews'			=> $this->reviews,
			'views'				=> $this->views,
			'prefix'			=> $this->prefix(),
			'tags'				=> $this->tags(),
			'locked'			=> (bool) $this->locked(),
			'hidden'			=> (bool) $this->hidden(),
			'featured'			=> (bool) $this->mapped('featured'),
			'pinned'			=> (bool) $this->mapped('pinned'),
			'url'				=> (string) $this->url(),
			'rating'			=> $this->averageRating(),
			'nsfw'				=> $this->nsfw
		);
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
		/* Remember the album id */
		$previousAlbum	= $this->album_id;

		if( $container instanceof Album)
		{
			$category	= $container->category();

			$this->album_id	= $container->_id;

			$container	= $category;
		}
		else
		{
			$this->album_id	= 0;
		}

		/* Move */
		parent::move( $container, $keepLink );

		/* Rebuild previous album */
		if( $previousAlbum )
		{
			$album	= Album::load( $previousAlbum );
			$album->setLastImage();
			$album->save();
		}

		/* Rebuild new album */
		if( $this->album_id )
		{
			$album	= Album::load( $this->album_id );
			$album->setLastImage();
			$album->save();
		}
	}

	/**
	 * Check permissions
	 *
	 * @param	mixed								$permission						A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index
	 * @param	Member|Group|NULL	$member							The member or group to check (NULL for currently logged in member)
	 * @param	bool								$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 * @throws	OutOfBoundsException	If $permission does not exist in map
	 */
	public function can( mixed $permission, Member|Group|null $member=null, bool $considerPostBeforeRegistering=TRUE ): bool
	{
		if( !parent::can( $permission, $member, $considerPostBeforeRegistering ) )
		{
			return FALSE;
		}
		
		try
		{
			if ( !$this->directContainer()->can( $permission, $member, $considerPostBeforeRegistering ) )
			{
				return FALSE;
			}
		}
		catch( OutOfRangeException )
		{
			/* If the direct container is lost, assume we can do nothing. @see \IPS\Content\Item::can() */
			return FALSE;
		}
		
		/* Still here? It must be okay */
		return TRUE;
	}

	/**
	 * Can view?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( ?Member $member=null ): bool
	{
		if( !parent::canView( $member ) )
		{
			return FALSE;
		}

		/* Check if the image is in a private or restricted access album */
		if( !static::modPermission( 'edit', NULL, $this->container() ) AND $this->directContainer() instanceof Album)
		{
			/* Make sure we have a member */
			$member = $member ?: Member::loggedIn();

			/* Is this a private album we can't access? */
			if( $this->directContainer()->type == Album::AUTH_TYPE_PRIVATE AND $this->directContainer()->owner()->member_id != $member->member_id )
			{
				return FALSE;
			}

			/* Is this a restricted album we can't access? */
			if( $this->directContainer()->type == Album::AUTH_TYPE_RESTRICTED AND $this->directContainer()->owner()->member_id != $member->member_id )
			{
				/* This will throw an exception of the row does not exist */
				try
				{
					if( !$member->member_id )
					{
						throw new OutOfRangeException;
					}

					$member	= Member::constructFromData( Db::i()->select( '*', 'core_sys_social_group_members', array( 'group_id=? AND member_id=?', $this->directContainer()->allowed_access, $member->member_id ) )->first() );
				}
				catch( OutOfRangeException )
				{
					return FALSE;
				}
				catch( UnderflowException )
				{
					/* Access checking for share strips in the parent::canView() method can throw UnderflowException */
					return FALSE;
				}
			}
		}

		/* And make sure we're not in a hidden album, unless we can view hidden albums */
		if( $this->directContainer() instanceof Album)
		{
			if( !$this->directContainer()->asItem()->canView( $member ) )
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Can set as album coverphoto?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canSetAsAlbumCover( ?Member $member=NULL ) : bool
	{
		$member = $member ?: Member::loggedIn();
		/* Allow album owners to always change the album cover */
		if( ( $this->album_id AND $album = Album::load( $this->album_id ) AND $member->member_id AND $album->owner_id == $member->member_id ) OR static::modPermission( 'edit', $member, $this->container() ) )
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Can set as category coverphoto?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canSetAsCategoryCover( ?Member $member=NULL ) : bool
	{
		if( static::modPermission( 'edit', $member, $this->container() ) )
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * WHERE clause for getItemsWithPermission
	 *
	 * @param	array		$where				Current WHERE clause
	 * @param	Member|null	$member				The member (NULL to use currently logged in member)
	 * @param	array|null		$joins				Additional joins
	 * @return	array
	 */
	public static function getItemsWithPermissionWhere( array $where, ?Member $member, ?array &$joins ) : array
	{
		/* If we already filtered by a specific album, we can stop right here */
		if( array_key_exists( 'album', $where ) )
		{
			return array();
		}

		/* Then we need to make sure we can access the album the image is in, if applicable */
		$member		= $member ?: Member::loggedIn();

		/* Skip permissions for guests */
		if( !$member->member_id )
		{
			$subQuery = array( "album_type=1 AND album_hidden=0" );
		}
		else
		{
			/* If you can edit images in a category you can see images in private albums in that category. We can only really check globally at this stage, however. */
			if( Image::modPermission( 'edit', $member ) )
			{
				return array();
			}

			$restricted	= $member->socialGroups();
			if( count( $restricted ) )
			{
				$subQuery = array( "( album_type=1 OR ( album_type=2 AND album_owner_id=? ) OR ( album_type=3 AND ( album_owner_id=? OR album_allowed_access IN (" . implode( ',', $restricted ) . ") ) ) )", $member->member_id, $member->member_id );
			}
			else
			{
				$subQuery = array( "( album_type=1 OR ( album_type=2 AND album_owner_id=? ) )", $member->member_id );
			}

			/* Make sure the images aren't in hidden albums, unless we can view hidden albums */
			$hiddenContainers = Item::canViewHiddenItemsContainers( $member );

			if( $hiddenContainers !== TRUE )
			{
				if( is_array( $hiddenContainers ) AND count( $hiddenContainers ) )
				{
					$subQuery[0] .= " AND ( album_hidden=0 OR album_category_id IN(" . implode( ',', $hiddenContainers ) . ") )";
				}
				else
				{
					$subQuery[0] .= " AND album_hidden=0";
				}
			}
		}

		return array( '( gallery_images.image_album_id=0 OR gallery_images.image_album_id IN( ' . Db::i()->select( 'album_id', 'gallery_albums', $subQuery )->returnFullQuery() . ' ) )' );
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
	 * @param	Member|NULL	$member				The member (NULL to use currently logged in member)
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
	public static function getItemsWithPermission( array $where=array(), string $order=NULL, int|array|null $limit=10, string $permissionKey='read', int|bool|null $includeHiddenItems=null, int $queryFlags=0, Member $member=null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins=null, bool|Model $skipPermission=FALSE, bool $joinTags=TRUE, bool $joinAuthor=TRUE, bool $joinLastCommenter=TRUE, bool $showMovedLinks=FALSE, array|null $location=null ): ActiveRecordIterator|int
	{
		if ( $order === NULL )
		{
			$order = 'image_date DESC';
		}
		
		/* We have to fix order by for images */
		$orders		= explode( ',', $order );
		$newOrders	= array();

		foreach( $orders as $_order )
		{
			$_check = explode( ' ', trim( $_order ) );

			if( count( $_check ) == 2 )
			{
				if( $_check[0] == 'image_updated' OR $_check[0] == 'image_date' )
				{
					$_order = $_check[0] . ' ' . $_check[1] . ', image_id ' . $_check[1];
				}
			}

			$newOrders[] = $_order;
		}

		$order = implode( ', ', $newOrders );

		if( $additionalWhere = static::getItemsWithPermissionWhere( $where, $member, $joins ) )
		{
			$where[] = $additionalWhere;
		}

		$parentResult = parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );

		/* Pre-prime album AR objects by loading them in one query now */
		if( !$countOnly )
		{
			$albumIds = array();

			foreach ( $parentResult as $result )
			{
				if ( $result->album_id )
				{
					$albumIds[ $result->album_id ] = $result->album_id;
				}
			}

			if ( count( $albumIds ) )
			{
				foreach ( Db::i()->select( '*', 'gallery_albums', array( Db::i()->in( 'album_id', $albumIds ) ) ) as $album )
				{
					Album::constructFromData( $album );
				}
			}
		}

		return $parentResult;
	}
	
	/**
	 * Additional WHERE clauses for Follow view
	 *
	 * @param bool $joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param array $joins				Other joins
	 * @return	array
	 */
	public static function followWhere( bool &$joinContainer, array &$joins ): array
	{
		return array_merge( parent::followWhere( $joinContainer, $joins ), static::getItemsWithPermissionWhere( array(), Member::loggedIn(), $joins ) );
	}

	/**
	 * Total item \count(including children)
	 *
	 * @param	Model	$container			The container
	 * @param	bool			$includeItems		If TRUE, items will be included (this should usually be true)
	 * @param	bool			$includeComments	If TRUE, comments will be included
	 * @param	bool			$includeReviews		If TRUE, reviews will be included
	 * @param	int				$depth				Used to keep track of current depth to avoid going too deep
	 * @return	int|NULL|string	When depth exceeds 10, will return "NULL" and initial call will return something like "100+"
	 * @note	This method may return something like "100+" if it has lots of children to avoid exahusting memory. It is intended only for display use
	 * @note	This method includes counts of hidden and unapproved content items as well
	 */
	public static function contentCount( Model $container, bool $includeItems=TRUE, bool $includeComments=FALSE, bool $includeReviews=FALSE, int $depth=0 ): int|NULL|string
	{
		if( !$container->nonpublic_albums )
		{
			return parent::contentCount( $container, $includeItems, $includeComments, $includeReviews, $depth );
		}

		$_key = md5( get_class( $container ) . $container->_id );

		if( !isset( static::$itemCounts[ $_key ][ $container->_id ] ) )
		{
			static::$itemCounts[ $_key ][ $container->_id ] = static::getItemsWithPermission( array( array( 'gallery_images.image_category_id=?', $container->_id ) ), NULL, 1, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );
		}

		return parent::contentCount( $container, $includeItems, $includeComments, $includeReviews, $depth );
	}
	
	/* !Embeddable */
	
	/**
	 * Get image for embed
	 *
	 * @return	File|NULL
	 */
	public function embedImage(): ?File
	{
		if( $this->media )
		{
			return null;
		}

		return File::get( 'gallery_Images', $this->small_file_name );
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
		return Theme::i()->getTemplate( 'global', 'gallery' )->embedImage( $this, $this->url()->setQueryString( $params ), $this->embedImage() );
	}

	/**
	 * Syncing to run when hiding
	 *
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onHide( Member|null|bool $member ): void
	{
		$this->_onHide( $member );

		if( $this->album_id )
		{
			$album = $this->directContainer();
			$album->_items = ( $album->_items >= 0 ) ? ( $album->_items - 1 ) : 0;
			$album->setLastImage();
			$album->save();
		}
	}

	/**
	 * Syncing to run when unhiding
	 *
	 * @param	bool					$approving	If true, is being approved for the first time
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onUnhide( bool $approving, Member|null|bool $member ): void
	{
		$this->_onUnhide( $approving, $member );

		if( $this->album_id )
		{
			$album = $this->directContainer();
			$album->_items = ( $album->_items + 1 );

			if( $approving )
			{
				$album->_unapprovedItems = ( $album->_unapprovedItems >= 0 ) ? ( $album->_unapprovedItems - 1 ) : 0;
			}

			$album->setLastImage( $this );
			$album->save();
		}
	}

	/**
	 * Get preview image for share services
	 *
	 * @return	string
	 */
	public function shareImage(): string
	{
		if ( $this->masked_file_name )
		{
			return (string)File::get( 'gallery_Images', $this->masked_file_name )->url;
		}

		return '';
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
		/* Categories are checked in the base class. Check direct containers here */
		switch( $action )
		{
			case 'comment':
			case 'reply':
				if( !$this->directContainer()->checkAction( 'comment' ) )
				{
					return FALSE;
				}
				break;

			case 'review':
				if( !$this->directContainer()->checkAction( 'review' ) )
				{
					return FALSE;
				}
				break;
		}

		return parent::actionEnabled( $action, $member );
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		return 'image_id';
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
	 * Get widget sort options
	 *
	 * @return array
	 */
	public static function getWidgetSortOptions(): array
	{
		$sortOptions = parent::getWidgetSortOptions();

		$sortOptions['_rand'] = 'sort_rand';

		return $sortOptions;
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
		$result = parent::contentImages( $limit, $ignorePermissions );

		if( $result === NULL )
		{
			$result = array();
		}

		$result[] = array( 'gallery_Images' => $this->masked_file_name );

		return array_slice( $result, 0, $limit );
	}

	/**
	 * Get content for an email
	 *
	 * @param Email $email The email
	 * @param string $type 'html' or 'plaintext'
	 * @param bool $includeLinks
	 * @param bool $includeAuthor
	 * @return    string
	 */
	public function emailContent(Email $email, string $type, bool $includeLinks=TRUE, bool $includeAuthor=TRUE ): string
	{
		if ( $type === 'html' )
		{
			return Email::template( 'gallery', '_imageContent', $type, array( $this, $includeLinks, $includeAuthor, $email ) );
		}
		else
		{
			return parent::emailContent( $email, $type, $includeLinks, $includeAuthor );
		}
	}

	/**
	 * @brief	Some constants to define the ability to download original images
	 */
	const DOWNLOAD_ORIGINAL_NONE		= 0;
	const DOWNLOAD_ORIGINAL_RAW			= 1;
	const DOWNLOAD_ORIGINAL_WATERMARKED	= 2;

	/**
	 * Can the member download the original image?
	 *
	 * @note	Returns one of the defined constants
	 *	@li DOWNLOAD_ORIGINAL_NONE
	 *	@li DOWNLOAD_ORIGINAL_RAW
	 *	@li DOWNLOAD_ORIGINAL_WATERMARKED
	 * @param	Member|NULL	$member		The member to test, or NULL for currently logged in member
	 * @return	int
	 */
	public function canDownloadOriginal( ?Member $member=NULL ) : int
	{
		$member = $member ?: Member::loggedIn();

		return $member->group['g_download_original'];
	}

	/**
	 * Return query WHERE clause to use for getItemsWithPermission when excluding club content
	 *
	 * @return array
	 */
	public static function clubImageExclusion(): array
	{
		if( Settings::i()->club_nodes_in_apps )
		{
			return array();
		}
		else
		{
			return array( array( 
				'gallery_images.image_category_id NOT IN(?)',
				Db::i()->select( 'node_id', 'core_clubs_node_map', array( 'node_class=?', 'IPS\gallery\Category' ) )
			) );
		}
	}

	/**
	 * Returns the labels from image scanner for search
	 *
	 * @return	array
	 * @throws	BadMethodCallException
	 */
	public function imageLabelsForSearch() : array
	{
		$return = [];

		$labels = $this->labels ? json_decode( $this->labels, TRUE ) : array();

		if( $labels and count( $labels ) )
		{
			foreach( $labels as $label )
			{
				$return[] = $label['Name'];
			}
		}

		return $return;
	}

	/**
	 * Is the image NSFW?
	 *
	 * @return	bool
	 */
	public function nsfw() : bool
	{
		return (bool) $this->nsfw;
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		try
		{
			return $this->small_file_name ? File::get( 'gallery_Images', $this->small_file_name ) : null;
		}
		catch( Exception ){}

		return parent::primaryImage();
	}
}
