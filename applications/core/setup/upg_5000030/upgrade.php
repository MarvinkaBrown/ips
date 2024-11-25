<?php
/**
 * @brief		5.0.0 Beta 4 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		04 Nov 2024
 */

namespace IPS\core\setup\upg_5000030;

use IPS\Application;
use IPS\Content;
use IPS\Task;
use OutOfRangeException;
use function defined;
use function method_exists;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Beta 4 Upgrade Code
 */
class Upgrade
{
	/**
	 * Fire background tasks to migrate lazy-load to native
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		foreach ( Content::routedClasses( FALSE, TRUE ) as $class )
		{
			if( isset( $class::$databaseColumnMap['content'] ) )
			{
				try
				{
					Task::queue( 'core', 'RebuildLazyLoad', array( 'class' => $class ), 3 );
				}
				catch( OutOfRangeException $ex ) { }
			}
		}

		foreach( Application::allExtensions( 'core', 'EditorLocations', FALSE ) as $_key => $extension )
		{
			if( method_exists( $extension, 'rebuildLazyLoad' ) )
			{
				Task::queue( 'core', 'RebuildLazyLoadNonContent', array( 'extension' => $_key ), 3 );
			}
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}