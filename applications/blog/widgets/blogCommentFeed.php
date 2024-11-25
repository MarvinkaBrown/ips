<?php
/**
 * @brief		blogCommentFeed Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		13 Jul 2015
 */

namespace IPS\blog\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\WidgetComment;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * blogCommentFeed Widget
 */
class blogCommentFeed extends WidgetComment
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'blogCommentFeed';
	
	/**
	 * @brief	App
	 */
	public string $app = 'blog';

	/**
	 * Class
	 */
	protected static string $class = 'IPS\blog\Entry\Comment';

	/**
	 * @brief	Moderator permission to generate caches on [optional]
	 */
	protected array $moderatorPermissions	= array( 'can_view_hidden_content', 'can_view_hidden_blog_entry_comment' );
	
	/**
	 * Get where clause
	 *
	 * @return	array
	 */
	protected function buildWhere(): array
	{
		$where = parent::buildWhere();
		$where['item'][] = array( 'entry_status!=?', 'draft' );
		return $where;
	}
}