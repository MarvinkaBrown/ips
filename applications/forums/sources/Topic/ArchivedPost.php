<?php
/**
 * @brief		Archived Post Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		24 Jan 2014
 */

namespace IPS\forums\Topic;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content\Item;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Member;
use IPS\Settings;
use function defined;
use function IPS\Cicloud\getForumArchiveDb;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Post Model
 */
class ArchivedPost extends Post
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Title
	 */
	public static string $archiveTitle = 'archivedpost';
	
	/**
	 * @brief	[ActiveRecord] Database Connection
	 * @return	Db
	 */
	public static function db(): Db
	{
        if ( Application::appIsEnabled('cloud') )
        {
            return getForumArchiveDb();
        }
        
		/* Connect to the remote DB if needed */
		return ( !Settings::i()->archive_remote_sql_host ) ? Db::i() : Db::i( 'archive', array(
			'sql_host'		=> Settings::i()->archive_remote_sql_host,
			'sql_user'		=> Settings::i()->archive_remote_sql_user,
			'sql_pass'		=> Settings::i()->archive_remote_sql_pass,
			'sql_database'	=> Settings::i()->archive_remote_sql_database,
			'sql_port'		=> Settings::i()->archive_sql_port,
			'sql_socket'	=> Settings::i()->archive_sql_socket,
			'sql_tbl_prefix'=> Settings::i()->archive_sql_tbl_prefix,
			'sql_utf8mb4'	=> isset( Settings::i()->sql_utf8mb4 ) ? Settings::i()->sql_utf8mb4 : FALSE
		) );
	}
		
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'forums_archive_posts';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'archive_';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'				=> 'topic_id',
		'author'			=> 'author_id',
		'author_name'		=> 'author_name',
		'content'			=> 'content',
		'date'				=> 'content_date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_show'			=> 'append_edit',
		'edit_member_name'	=> 'edit_name',
		'edit_reason'		=> 'post_edit_reason',
		'hidden'			=> 'queued',
		'first'				=> 'is_first'
	);

	/**
	 * @brief	Bitwise values for post_bwoptions field
	 */
	public static array $bitOptions = array(
		'post_bwoptions' => array(
			'post_bwoptions' => array(
				'best_answer'	=> 1
			)
		)
	);

	/**
	 * @brief	Database Column ID
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * Post count for member
	 *
	 * @param	Member	$member								The member
	 * @param	bool		$includeNonPostCountIncreasing		If FALSE, will skip any posts which would not cause the user's post count to increase
	 * @param	bool		$includeHiddenAndPendingApproval	If FALSE, will skip any hidden posts, or posts pending approval
	 * @return	int
	 */
	public static function memberPostCount( Member $member, bool $includeNonPostCountIncreasing = FALSE, bool $includeHiddenAndPendingApproval = TRUE ): int
	{
		$where = [];
		$where[] = [ 'archive_author_id=?', $member->member_id ];

		try
		{
			if ( !$includeNonPostCountIncreasing )
			{
				$where[] = [ static::db()->in( 'archive_forum_id', iterator_to_array( Db::i()->select( 'id', 'forums_forums', 'inc_postcount=1' ) ) ) ];
			}
			if ( !$includeHiddenAndPendingApproval )
			{
				$where[] = [ 'archive_queued=0' ];
			}

			return static::db()->select( 'COUNT(*)', static::$databaseTable, $where )->first();
		}
		catch ( Exception $e )
		{
			return 0;
		}

	}

	/**
	 * Joins (when loading comments)
	 *
	 * @param	Item	$item			The item
	 * @return	array
	 */
	public static function joins( Item $item ): array
	{
		$return = parent::joins( $item );
		
		unset( $return['author'] );
		unset( $return['author_pfields'] );
		
		return $return;
	}
	
	/**
	 * Can edit?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEdit( Member|null $member=null ): bool
	{
		return FALSE;
	}
	
	/**
	 * Can hide?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canHide( Member|null $member=NULL ): bool
	{
		return FALSE;
	}
	
	/**
	 * Can unhide?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 */
	public function canUnhide( Member $member=NULL ): bool
	{
		return FALSE;
	}
	
	/**
	 * Can delete?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( Member $member=null ): bool
	{
		return FALSE;
	}
	
	/**
	 * Can split?
	 *
	 * @param Member|null $member The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canSplit( Member|null $member=null ): bool
	{
		return FALSE;
	}
	
	/**
	 * Can react?
	 *
	 * @note	This method is also ran to check if a member can "unrep"
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canReact( Member $member = NULL ): bool
	{
		return FALSE;
	}
}
