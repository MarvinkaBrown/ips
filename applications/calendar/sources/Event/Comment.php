<?php
/**
 * @brief		Event Comment Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		7 Jan 2014
 */

namespace IPS\calendar\Event;

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
use IPS\Http\Url;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Event Comment Model
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
	 * @brief	[Content\Comment]	Item Class
	 */
	public static ?string $itemClass = 'IPS\calendar\Event';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'calendar_event_comments';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'comment_';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'				=> 'eid',
		'author'			=> 'mid',
		'author_name'		=> 'author',
		'content'			=> 'text',
		'date'				=> 'date',
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
	public static string $application = 'calendar';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'calendar_event_comment';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'calendar';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'calendar-events';
	
	/**
	 * Get URL for doing stuff
	 *
	 * @param string|null $action		Action
	 * @return	Url
	 */
	public function url( ?string $action='find' ): Url
	{
		return parent::url( $action )->setQueryString( 'tab', 'comments' );
	}

	/**
	 * Get template for content tables
	 *
	 * @return	array
	 */
	public static function contentTableTemplate(): array
	{
		return array( Theme::i()->getTemplate( 'tables', 'core', 'front' ), 'commentRows' );
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
	 * @param array $params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'calendar', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'calendar' )->embedEventComment( $this, $this->item(), $this->url()->setQueryString( $params ) );
	}
}