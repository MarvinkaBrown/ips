<?php
/**
 * @brief		Image Comment Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery\Image;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Anonymous;
use IPS\Content\Comment as ContentComment;
use IPS\Content\EditHistory;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Hideable;
use IPS\Content\Featurable;
use IPS\Content\Reactable;
use IPS\Content\Reportable;
use IPS\Content\Shareable;
use IPS\gallery\Album;
use IPS\gallery\Image;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Image Comment Model
 */
class Comment extends ContentComment implements Embeddable,
	Filter
{
	use	Reactable,
		Reportable,
		Anonymous,
		Shareable,
		EditHistory,
		Featurable,
		Hideable {
			Hideable::onHide as public _onHide;
			Hideable::onUnhide as public _onUnhide;
	}

	/**
	 * @brief	[Content\Comment]	Form Template
	 */
	public static array $formTemplate = array( array( 'forms', 'gallery', 'front' ), 'commentTemplate' );

	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static array $commentTemplate = array( array( 'global', 'gallery', 'front' ), 'commentContainer' );

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static ?string $itemClass = 'IPS\gallery\Image';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'gallery_comments';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'comment_';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'				=> 'img_id',
		'author'			=> 'author_id',
		'author_name'		=> 'author_name',
		'content'			=> 'text',
		'date'				=> 'post_date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_member_name'	=> 'edit_name',
		'edit_show'			=> 'append_edit',
		'approved'			=> 'approved',
		'is_anon'			=> 'is_anon'
	);
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'gallery';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'gallery_image_comment';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'camera';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'gallery-images';

	/**
	 * Get items with permission check
	 *
	 * @param array $where Where clause
	 * @param string|null $order MySQL ORDER BY clause (NULL to order by date)
	 * @param int|array|null $limit Limit clause
	 * @param string|null $permissionKey A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index
	 * @param mixed $includeHiddenComments
	 * @param int $queryFlags Select bitwise flags
	 * @param Member|null $member The member (NULL to use currently logged in member)
	 * @param bool $joinContainer If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinComments If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinReviews If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param bool $countOnly If true will return the count
	 * @param array|null $joins Additional arbitrary joins for the query
	 * @return    array|NULL|Comment        If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public static function getItemsWithPermission( array $where=array(), string $order= null, int|array|null $limit=10, string|null $permissionKey='read', mixed $includeHiddenComments= Filter::FILTER_AUTOMATIC, int $queryFlags=0, Member|null $member= null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins= null ): mixed
	{
		if( $itemsWhere = Image::getItemsWithPermissionWhere( $where, $member, $joins ) AND count( $itemsWhere ) )
		{
			$where[] = $itemsWhere;
		}
		
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenComments, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins );
	}
	
	/**
	 * Get URL for doing stuff
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 */
	public function url( ?string $action='find' ): Url
	{
		return parent::url( $action )->setQueryString( 'tab', 'comments' );
	}

	/**
	 * Do stuff after creating (abstracted as comments and reviews need to do different things)
	 *
	 * @return	void
	 */
	public function postCreate(): void
	{
		parent::postCreate();

		$item	= $this->item();

		if( $item->album_id )
		{
			$album = $item->directContainer();

			/* We need to increment the counters here because the parent method will increment
			the category but not the album */
			if( !$item->approvedButHidden() )
			{
				if( $this->hidden() == 0 and !$item->hidden() )
				{
					$album->_comments = ( $album->_comments + 1 );
				}
				elseif( $this->hidden() == 1 )
				{
					$album->_unapprovedComments = ( $album->_unapprovedComments >= 0 ) ? ( $album->_unapprovedComments + 1 ) : 1;
				}
			}

			$album->setLastComment( $this, $item );
			$album->save();
		}
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

		$item = $this->item();
		if( $item->album_id )
		{
			$album = $item->directContainer();
			$album->setLastComment();
			$album->_comments = ( $album->_comments - 1 );
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

		$item = $this->item();
		if( $item->album_id )
		{
			$album = $item->directContainer();
			$album->setLastComment( $this );
			$album->_comments = ( $album->_comments + 1 );
			if( $approving )
			{
				$album->_unapprovedComments = ( $album->_unapprovedComments > 0 ) ? ( $album->_unapprovedComments - 1 ) : 0;
			}
			$album->save();
		}
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		return 'comment_id';
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
		return Theme::i()->getTemplate( 'global', 'gallery' )->embedImageComment( $this, $this->item(), $this->url()->setQueryString( $params ) );
	}
}