<?php
/**
 * @brief		forumStatistics Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		27 Mar 2014
 */

namespace IPS\forums\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\forums\Topic\ArchivedPost;
use IPS\Settings;
use IPS\Widget\StaticCache;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * forumStatistics Widget
 */
class forumStatistics extends StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'forumStatistics';
	
	/**
	 * @brief	App
	 */
	public string $app = 'forums';

	/**
	 * @brief	Cache Expiration - 24h
	 */
	public int $cacheExpiration = 86400;

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$stats = array();

		$approxRows = Db::i()->query( "SHOW TABLE STATUS LIKE '" . Db::i()->prefix . "forums_posts';" )->fetch_assoc();

		if( (int) $approxRows['Rows'] >= 1000000 )
		{
			$stats['total_posts'] = Db::i()->select( 'SUM(posts)', 'forums_forums' )->first();
		}
		else
		{
			$stats['total_posts'] = Db::i()->select( "COUNT(*)", 'forums_posts', array( 'queued = ?', 0 ) )->first();
		}

		/* Only query if we're not using the cached forums count, the cached count includes these */
		if ( (int) $approxRows['Rows'] <= 1000000 AND Settings::i()->archive_on )
		{
			$stats['total_posts'] += ArchivedPost::db()->select( 'COUNT(*)', 'forums_archive_posts', array( 'archive_queued = ?', 0 ) )->first();
		}
		
		$stats['total_topics']	= Db::i()->select( "COUNT(*)", 'forums_topics', array( 'approved = ?', 1 ) )->first();
		
		return $this->output( $stats );
	}
}