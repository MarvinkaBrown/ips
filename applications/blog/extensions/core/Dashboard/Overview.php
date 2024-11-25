<?php
/**
 * @brief		Dashboard extension: Overview
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		20 Mar 2014
 */

namespace IPS\blog\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\DashboardAbstract;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Overview
 */
class Overview extends DashboardAbstract
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView(): bool
	{
		return TRUE;
	}

	/** 
	 * Return the block HTML show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock(): string
	{
		/* Basic stats */
		$data = array(
			'total_blogs'		=> (int) Db::i()->select( 'COUNT(*)', 'blog_blogs' )->first(),
			'total_entries'		=> (int) Db::i()->select( 'COUNT(*)', 'blog_entries' )->first(),
			'total_comments'	=> (int) Db::i()->select( 'COUNT(*)', 'blog_comments' )->first(),
		);
		
		/* Display */
		return Theme::i()->getTemplate( 'dashboard', 'blog' )->overview( $data );
	}
}