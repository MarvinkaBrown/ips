<?php
/**
 * @brief		blogStatistics Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		14 May 2014
 */

namespace IPS\blog\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Widget\StaticCache;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * blogStatistics Widget
 */
class blogStatistics extends StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'blogStatistics';
	
	/**
	 * @brief	App
	 */
	public string $app = 'blog';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$stats = array();
		
		$stats['total_blogs']	= Db::i()->select( "COUNT(*)", 'blog_blogs' )->first();
		$stats['total_entries']	= Db::i()->select( "COUNT(*)", 'blog_entries', array( 'entry_status=? AND entry_hidden=?', 'published', 1 ) )->first();
		
		return $this->output( $stats );
	}
}