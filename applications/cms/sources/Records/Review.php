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

use BadMethodCallException;
use IPS\Content\EditHistory;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Hideable;
use IPS\Content\Item;
use IPS\Content\Reactable;
use IPS\Content\Reportable;
use IPS\Content\Review as ContentReview;
use IPS\Content\Shareable;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\Http\Url\Exception;
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
class Review extends ContentReview implements Embeddable,
	Filter
{
	use Reactable, Reportable, Shareable, EditHistory, Hideable;
	
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
	public static ?string $databaseTable = 'cms_database_reviews';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'review_';
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'cms';

	/**
	 * @brief	Title
	 */
	public static string $title = 'content_review';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'				=> 'item',
		'author'			=> 'author',
		'author_name'		=> 'author_name',
		'content'			=> 'content',
		'date'				=> 'date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_member_name'	=> 'edit_member_name',
		'edit_show'			=> 'edit_show',
		'rating'			=> 'rating',
		'votes_total'		=> 'votes_total',
		'votes_helpful'		=> 'votes_helpful',
		'votes_data'		=> 'votes_data',
		'approved'			=> 'approved',
		'author_response'	=> 'author_response',
	);
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'star';
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static array $commentTemplate = array( array( 'display', 'cms', 'database' ), 'reviewContainer' );
	
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
	 * @return ActiveRecord|Review
	 * @see        \IPS\Db::build
	 */
	public static function load( int|string|null $id, string $idField=NULL, mixed $extraWhereClause=NULL ): ActiveRecord|static
	{
		if( $extraWhereClause === NULL )
		{
			$extraWhereClause = array( static::commentWhere() );
		}
		else
		{
			$extraWhereClause[] = static::commentWhere();
		}

		return parent::load( $id, $idField, $extraWhereClause );
	}

	/**
	 * Create first comment (created with content item)
	 *
	 * @param Item $item The content item just created
	 * @param string $comment The comment
	 * @param bool $first Is the first comment?
	 * @param string|null $guestName If author is a guest, the name to use
	 * @param bool|null $incrementPostCount
	 * @param Member|null $member The author of this comment. If NULL, uses currently logged in member.
	 * @param DateTime|null $time The time
	 * @param string|null $ipAddress The IP address or NULL to detect automatically
	 * @param int|null $hiddenStatus NULL to set automatically or override: 0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @param int|null $anonymous NULL for no value, 0 or 1 for a value (0=no, 1=yes)
	 * @return Review|null
	 */
	public static function create( Item $item, string $comment, bool $first=false, string|null $guestName=null, bool|null $incrementPostCount= null, Member|null $member= null, DateTime|null $time= null, string|null $ipAddress= null, int|null $hiddenStatus= null, int|null $anonymous= null ): static|null
	{
		$review = parent::create( $item, $comment, $first, $guestName, $member, $time, $ipAddress, $hiddenStatus, $anonymous );

		$review->database_id = static::$customDatabaseId;
		$review->save();

		/* Have to do these AFTER database id is set */
		/* @var array $databaseColumnMap */
		$ratingField = $item::$databaseColumnMap['rating'];

		$review->item()->$ratingField = (int) $review->item()->averageReviewRating() ?: 0;
		$review->item()->save();
		
		return $review;
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
	 * @throws	BadMethodCallException
	 * @throws	Exception
	 */
	public function url( ?string $action='find' ): Url
	{
		$url = parent::url( $action );

		if ( $action !== NULL )
		{
			$url = $url->setQueryString( 'd', static::$customDatabaseId );
		}
		
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
		return array( $this->item()->$idColumn, $this->$commentIdColumn, static::$customDatabaseId . '-review' ); 
	}

	/**
	 * Addition where needed for fetching comments
	 *
	 * @return	array|NULL
	 */
	public static function commentWhere(): ?array
	{
		return array( 'review_database_id=?', static::$customDatabaseId );
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 * @todo This was implemented improperly before - look into upgrade routine to fix
	 */
	public static function reactionType(): string
	{
		$databaseId = static::$customDatabaseId;
		return "review_id_{$databaseId}";
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
		return Theme::i()->getTemplate( 'global', 'cms' )->embedRecordReview( $this, $this->item(), $this->url()->setQueryString( $params ) );
	}
}