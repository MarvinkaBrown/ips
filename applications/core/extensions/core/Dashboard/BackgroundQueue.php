<?php
/**
 * @brief		Dashboard extension: Background Queue Progress
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 May 2014
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Db;
use IPS\Extensions\DashboardAbstract;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dashboard extension: Background Queue Progress
 */
class BackgroundQueue extends DashboardAbstract
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
		$rows = array();

		$select = Db::i()->select( '*', 'core_queue', array( 'app_enabled=?', 1 ), 'priority ASC, date ASC', array( 0, 100 ) )->join( 'core_applications', "app=app_directory" );
		$totalCount = Db::i()->select( 'count(*)', 'core_queue', array( 'app_enabled=?', 1 ) )->join( 'core_applications', "app=app_directory" )->first();
		if ( count( $select ) )
		{
			foreach ( $select as $queueData )
			{
				$extensions = Application::load( $queueData['app'] )->extensions( 'core', 'Queue', FALSE );
				if ( isset( $extensions[ $queueData['key'] ] ) )
				{
					try
					{
						$class = new $extensions[ $queueData['key'] ];
						$rows[] = $class->getProgress( json_decode( $queueData['data'], TRUE ), $queueData['offset'] );
					}
					catch ( OutOfRangeException $e ) { }
				}
			}			
		}

		return Theme::i()->getTemplate( 'dashboard' )->backgroundQueue( $rows, $totalCount );
	}
}