<?php
/**
 * @brief		Forum Post Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		8 April 2014
 */

namespace IPS\cms\Records;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Records;
use IPS\cms\Theme;
use IPS\Content\Item;
use IPS\core\Reports\Report;
use IPS\forums\Topic\Post;
use IPS\Member;
use IPS\Request;
use OutOfRangeException;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Records Model
 */
class CommentTopicSync extends Comment
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'pid';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'forums_posts';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = '';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'				=> 'topic_id',
		'author'			=> 'author_id',
		'author_name'		=> 'author_name',
		'content'			=> 'post',
		'date'				=> 'post_date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_show'			=> 'append_edit',
		'edit_member_name'	=> 'edit_name',
		'edit_reason'		=> 'post_edit_reason',
		'hidden'			=> 'queued',
		'first'				=> 'new_topic'
	);
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'forums';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'comment';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'post';
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static array $commentTemplate = array( array( 'display', 'cms', 'database' ), 'commentContainer' );
	
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
	 * Get containing item
	 *
	 * @return Item
	 */
	public function item(): Item
	{
		$itemClass = static::$itemClass;
		/* @var $itemClass Records */
		$lookFor = 'IPS\cms\Records\RecordsTopicSync';

		if ( mb_substr( $itemClass, 0, mb_strlen( $lookFor ) ) === $lookFor )
		{
			$id = $this->topic_id;
		}
		else
		{
			$id = Request::i()->id;
		}
		
		return $itemClass::load( $id );
	}
	
	/**
	 * Delete Comment
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* We need the classname to be \IPS\forums\Topic\Post so topic/forum sync occur and to ensure post is removed from search index */
		$comment = Post::load( $this->pid );
		$comment->delete();
	}
	
	/**
	 * Edit Comment Contents - Note: does not add edit log
	 *
	 * @param string $newContent	New content
	 * @return    void
	 */
	public function editContents( string $newContent ): void
	{
		$comment = Post::load( $this->pid );
		$comment->editContents( $newContent );
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		if ( ! empty( $this->pid ) )
		{
			try
			{
				$comment = Post::load( $this->pid );
				$template = static::$commentTemplate[1];
				static::$commentTemplate[0][0] = $this->item()->database()->template_display;

				return Theme::i()->getTemplate( static::$commentTemplate[0][0], static::$commentTemplate[0][1], static::$commentTemplate[0][2] )->$template( $comment->item(), $comment );
			}
			catch( OutOfRangeException $e )
			{
				return parent::html();
			}
		}
		else
		{
			return parent::html();
		}
	}
	
	/**
	 * Report
	 *
	 * @param string $reportContent Report content message from member
	 * @param int $reportType Report type (see constants in \IPS\core\Reports\Report)
	 * @param \IPS\Member|NULL $member Member making the report (or NULL for loggedIn())
	 * @param array $guestDetails Details for a guest report
	 * @return    \IPS\core\Reports\Report
	 * @throws    UnexpectedValueException	If there is a permission error - you should only call this method after checking canReport
	 */
	public function report( string $reportContent, int $reportType=1, Member|null $member=null, array $guestDetails=array()  ): Report
	{
		$comment = Post::load( $this->pid );
		return $comment->report( $reportContent, $reportType, $member );
	}
	
	/**
	 * Addition where needed for fetching comments
	 *
	 * @return	array|NULL
	 */
	public static function commentWhere(): ?array
	{
		return NULL;
	}

	/**
	 * Return custom where for SQL delete
	 *
	 * @param   int     $id     Content item to delete from
	 * @return array
	 */
	public static function deleteWhereSql( int $id ): array
	{
		return array( array( static::$databasePrefix . static::$databaseColumnMap['item'] . '=?', $id ) );
	}

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
		return 0; // We explicitely return 0 because any posts will be counted via the forums application inherently already
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		return 'pid';
	}
	
	/**
	 * Reaction class
	 *
	 * @return	string
	 */
	public static function reactionClass() : string
	{
		return 'IPS\forums\Topic\Post';
	}

}