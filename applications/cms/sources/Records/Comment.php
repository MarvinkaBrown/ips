<?php
/**
 * @brief		Post Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		8 Jan 2014
 */

namespace IPS\cms\Records;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases;
use IPS\cms\Records;
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
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecord;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Post Model
 */
class Comment extends ContentComment implements Embeddable,
	Filter
{
	use	Reactable,
		Reportable,
		Anonymous,
		Shareable,
		EditHistory,
		Hideable,
		Featurable;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static ?string $itemClass = NULL;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'cms_database_comments';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'comment_';
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'cms';

	/**
	 * @brief	Title
	 */
	public static string $title = 'content_comment';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'				=> 'record_id',
		'author'			=> 'user',
		'author_name'		=> 'author',
		'content'			=> 'post',
		'date'				=> 'date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_date',
		'edit_show'			=> 'edit_show',
		'edit_member_name'	=> 'edit_member_name',
		'edit_member_id'    => 'edit_member_id',
		'edit_reason'		=> 'edit_reason',
		'approved'			=> 'approved',
		'is_anon'			=> 'is_anon'
	#	'first'				=> 'new_topic'
	);
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'comments';
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static array $commentTemplate = array( array( 'display', 'cms', 'database' ), 'commentContainer' );

	/**
	 * @brief	Database ID
	 */
	public static ?int $customDatabaseId = NULL;

	/**
	 * Load Record
	 *
	 * @param int|string|null $id ID
	 * @param string|null $idField The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param mixed $extraWhereClause Additional where clause(s) (see \IPS\Db::build for details) - if used will cause multiton store to be skipped and a query always ran
	 * @return ActiveRecord|Comment
	 * @see        Db::build
	 */
	public static function load( int|string|null $id, string $idField=NULL, mixed $extraWhereClause=NULL ): ActiveRecord|static
	{
		if ( static::commentWhere() !== NULL )
		{
			if( $extraWhereClause === NULL )
			{
				$extraWhereClause = array( static::commentWhere() );
			}
			else
			{
				$extraWhereClause[] = static::commentWhere();
			}
		}

		return parent::load( $id, $idField, $extraWhereClause );
	}

	/**
	 * Post count for member
	 *
	 * @param Member $member The memner
	 * @param bool $includeNonPostCountIncreasing
	 * @param bool $includeHiddenAndPendingApproval
	 * @return    int
	 */
	public static function memberPostCount( Member $member, bool $includeNonPostCountIncreasing = FALSE, bool $includeHiddenAndPendingApproval = TRUE ): int
	{
		$where = [];
		$where[] = [ 'comment_database_id=?', static::$customDatabaseId ];
		$where[] = [ 'comment_user=?', $member->member_id ];
		
		if ( !$includeHiddenAndPendingApproval )
		{
			$where[] = [ 'comment_approved=1' ];
		}
		
		return Db::i()->select( 'COUNT(*)', static::$databaseTable, $where )->first();
	}
	
	/**
	 * Return custom where for SQL delete
	 *
	 * @param   int     $id     Content item to delete from
	 * @return array
	 */
	public static function deleteWhereSql( int $id ) : array
	{
		return array( array( static::$databasePrefix . static::$databaseColumnMap['item'] . '=?', $id ), array( static::$databasePrefix . 'database_id=?', static::$customDatabaseId ) );
	}

	/**
	 * Return custom where for IP tools
	 *
	 * @return string
	 */
	public static function findByIPWhere() : string
	{
		return " AND comment_database_id=" . static::$customDatabaseId;
	}

	/**
	 * Get items with permisison check
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
		$class = '\IPS\cms\Records' . static::$customDatabaseId;

		/* @var	$class	Records */
		$where = $class::getItemsWithPermissionWhere( $where, $permissionKey, $member, $joinContainer );
		
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenComments, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins );
	}
	
	/**
	 * Do stuff after creating (abstracted as comments and reviews need to do different things)
	 *
	 * @return	void
	 */
	public function postCreate(): void
	{
		$this->database_id = static::$customDatabaseId;
		$this->save();
		
		$item = $this->item();
		/* @var	$item	Records */
		if ( $item->hidden() OR ( Databases::load( static::$customDatabaseId )->_comment_bump & Databases::BUMP_ON_COMMENT ) )
		{
			parent::postCreate();
		}
		else
		{
			/* No bump, so don't update the record's last_action stuff */
			if ( isset( $item::$databaseColumnMap['num_comments'] ) )
			{
				$item->resyncCommentCounts();
				$item->save();
			}
				
			if ( $item->containerWrapper() AND $item->container()->_comments !== NULL )
			{
				$item->container()->_comments = ( $item->container()->_comments + 1 );
				$item->container()->save();
			}
		}
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		$template = static::$commentTemplate[1];
		static::$commentTemplate[0][0] = $this->item()->database()->template_display;

		return \IPS\cms\Theme::i()->getTemplate( static::$commentTemplate[0][0], static::$commentTemplate[0][1], static::$commentTemplate[0][2] )->$template( $this->item(), $this );
	}
	
	/**
	 * Get URL for doing stuff
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 */
	public function url( ?string $action='find' ): Url
	{
		$url = parent::url( $action );
		
		if ( $action !== NULL )
		{
			$url = $url->setQueryString( 'd', static::$customDatabaseId );
		}

		$url = $url->setQueryString( 'tab', 'comments' );
		
		return $url;
	}
	
	/**
	 * Get attachment IDs
	 *
	 * @return	array
	 */
	public function attachmentIds(): array
	{
		$item = $this->item();
		$idColumn = $item::$databaseColumnId;
		$commentIdColumn = static::$databaseColumnId;
		return array( $this->item()->$idColumn, $this->$commentIdColumn, static::$customDatabaseId . '-comment' ); 
	}

	/**
	 * Addition where needed for fetching comments
	 *
	 * @return	array|NULL
	 */
	public static function commentWhere(): ?array
	{
		return array( 'comment_database_id=?', static::$customDatabaseId );
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		$databaseId = static::$customDatabaseId;
		return "comment_id_{$databaseId}";
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'cms', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'cms' )->embedRecordComment( $this, $this->item(), $this->url()->setQueryString( $params ) );
	}
}