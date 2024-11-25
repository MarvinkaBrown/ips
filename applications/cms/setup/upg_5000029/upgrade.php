<?php
/**
 * @brief		5.0.0 Beta 3 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		24 Oct 2024
 */

namespace IPS\cms\setup\upg_5000029;

use function array_keys;
use function array_merge;
use function defined;
use function in_array;
use function json_decode;
use function json_encode;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Beta 3 Upgrade Code
 */
class Upgrade
{
	/**
	 * Fix an issue where Database widgets dropped into a header/sidebar/footer area in v4 cause issues in v5 as they must be in col1
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		$areas = [];
		foreach( \IPS\Db::i()->select( '*', 'cms_page_widget_areas', [], 'area_page_id ASC' ) as $row )
		{
			$areas[ $row['area_page_id'] ][ $row['area_area'] ] = $row;
		}

		/* Now check to make sure that we have a col1 area and also that we don't have a database widget in header/footer/sidebar from v4 */
		foreach( $areas as $pageId => $areaRows )
		{
			if ( ! in_array( 'col1', array_keys( $areaRows ) ) )
			{
				/* Insert one */
				\IPS\Db::i()->insert( 'cms_page_widget_areas', [
					'area_page_id' => $pageId,
					'area_widgets' => '[]',
					'area_area'	   => 'col1',
					'area_orientation' => 'vertical',
					'area_tree' => ''
				] );
			}

			/* Now check for a Database widget in the wrong area */
			foreach( [ 'header', 'footer', 'sidebar' ] as $protectedArea )
			{
				if ( in_array( $protectedArea, array_keys( $areaRows ) ) and $areaRows[ $protectedArea ]['area_widgets'] )
				{
					if( $widgets = json_decode( $areaRows[ $protectedArea ]['area_widgets'], true ) )
					{
						$needsUpdating = false;
						$database = [];
						$newWidgets = [];
						foreach( $widgets as $widget )
						{
							if ( $widget['app'] === 'cms' and $widget['key'] == 'Database' )
							{
								/* Well, this shouldn't be here */
								$database = $widget;
								$needsUpdating = true;
							}
							else
							{
								$newWidgets[] = $widget;
							}
						}

						if ( $needsUpdating )
						{
							/* Get any existing from the database for col1 if it exists */
							if( $col1 = json_decode( $areaRows['col1']['area_widgets'], true ) )
							{
								$database = array_merge( [ $database ], $col1 );
							}

							/* Update the existing area */
							\IPS\Db::i()->update( 'cms_page_widget_areas', [ 'area_widgets' => json_encode( $newWidgets ) ], [ 'area_page_id=? and area_area=?', $pageId, $protectedArea ] );

							/* Move the database across */
							\IPS\Db::i()->update( 'cms_page_widget_areas', [ 'area_widgets' => json_encode( [ $database ] ) ], [ 'area_page_id=? and area_area=?', $pageId, 'col1' ] );
						}
					}
				}
			}
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}