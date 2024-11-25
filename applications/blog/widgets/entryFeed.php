<?php
/**
 * @brief		Blog Entry Feed Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		22 Jun 2015
 */

namespace IPS\blog\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Widget;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Blog Entry Feed Widget
 */
class entryFeed extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'entryFeed';
	
	/**
	 * @brief	App
	 */
	public string $app = 'blog';
	
	/**
	 * Class
	 */
	protected static string $class = 'IPS\blog\Entry';
	
	/**
	 * Get where clause
	 *
	 * @return	array
	 */
	protected function buildWhere(): array
	{
		$where = parent::buildWhere();
		$where[] = array( 'entry_status!=?', 'draft' );
		return $where;
	}
}