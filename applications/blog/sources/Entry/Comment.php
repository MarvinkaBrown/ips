<?php
/**
 * @brief		Blog Entry Comment Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		3 Mar 2013
 */

namespace IPS\blog\Entry;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Content;
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
use IPS\Db;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Blog Entry Comment Model
 */
class Comment extends ContentComment implements Embeddable,
	Filter
{
	use Featurable, Reactable, Reportable, Anonymous, Shareable, EditHistory, Hideable;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static ?string $itemClass = 'IPS\blog\Entry';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'blog_comments';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'comment_';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
			'item'				=> 'entry_id',
			'author'			=> 'member_id',
			'author_name'		=> 'member_name',
			'content'			=> 'text',
			'date'				=> 'date',
			'ip_address'		=> 'ip_address',
			'edit_time'			=> 'edit_time',
			'edit_member_name'	=> 'edit_member_name',
			'edit_show'			=> 'edit_show',
			'approved'			=> 'approved',
			'is_anon'			=> 'is_anon'
	);
	
	/**
	 * @brief	Application
	*/
	public static string $application = 'blog';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'blog_entry_comment';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'pen-to-square';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'blog-entries';
	
	/**
	 * Get items with permission check
	 *
	 * @param	array		$where				Where clause
	 * @param string|null $order				MySQL ORDER BY clause (NULL to order by date)
	 * @param	int|array|null	$limit				Limit clause
	 * @param	string|null		$permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index
	 * @param	mixed		$includeHiddenComments	Include hidden comments? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param	int			$queryFlags			Select bitwise flags
	 * @param Member|null  $member				The member (NULL to use currently logged in member)
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$countOnly				If true will return the count
	 * @param array|null  $joins					Additional arbitrary joins for the query
	 * @return	array|NULL|Comment		If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public static function getItemsWithPermission( array $where=array(), string $order= null, int|array|null $limit=10, string|null $permissionKey='read', mixed $includeHiddenComments= Filter::FILTER_AUTOMATIC, int $queryFlags=0, Member|null $member= null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins= null ): mixed
	{
		if ( in_array( $permissionKey, array( 'view', 'read' ) ) )
		{
			$joinContainer = TRUE;
			$member = $member ?: Member::loggedIn();
			if ( $member->member_id )
			{
				$where[] = array( '( blog_blogs.blog_social_group IS NULL OR blog_blogs.blog_member_id=' . $member->member_id . ' OR ( ' . Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', $member ) . ' ) )' );
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
						$where[] = array('( blog_blogs.blog_club_id IS NULL OR ' . Db::i()->in( 'blog_blogs.blog_club_id', $member->clubs() ) . ' OR core_clubs.type=? OR core_clubs.type=? OR core_clubs.type=? )', Club::TYPE_PUBLIC, Club::TYPE_READONLY, Club::TYPE_OPEN );
					}
				}
				else
				{
					$where[] = array( '( blog_blogs.blog_club_id IS NULL OR core_clubs.type=? OR core_clubs.type=? OR core_clubs.type=? )', Club::TYPE_PUBLIC, Club::TYPE_READONLY, Club::TYPE_OPEN  );
				}
			}
			
		}
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenComments, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins );
	}
	
	/**
	 * Check Moderator Permission
	 *
	 * @param	string						$type		'edit', 'hide', 'unhide', 'delete', etc.
	 * @param	Member|NULL			$member		The member to check for or NULL for the currently logged in member
	 * @param	Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function modPermission( string $type, Member $member = NULL, Model $container = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();

		if( parent::modPermission( $type, $member, $container ) )
		{
			return true;
		}

		if ( in_array( $type, array( 'edit', 'delete', 'lock' ) ) and $container and $container->member_id === $member->member_id )
		{
			return $member->group['g_blog_allowownmod'];
		}
		
		return false;
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
	 * @throws BadMethodCallException
	 */
	public function embedContent( array $params ): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'blog', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'blog' )->embedEntryComment( $this, $this->item(), $this->item()->container(), $this->url()->setQueryString( $params ) );
	}
}