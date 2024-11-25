<?php
/**
 * @brief		Dashboard extension: Latest News
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Aug 2013
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Extensions\DashboardAbstract;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Theme;
use RuntimeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Latest News
 */
class LatestNews extends DashboardAbstract
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
	 * Return the block to show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock(): string
	{
		return '';
	}

	/**
	 * Updates news store
	 *
	 * @return	void
	 * @throws	Exception
	 */
	protected function refreshNews() : void
	{
		Store::i()->ips_news = json_encode( array(
			'content'	=> Url::ips( 'news' )->request()->get()->decodeJson(),
			'time'		=> time()
		) );
	}
}