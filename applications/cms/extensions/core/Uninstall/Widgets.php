<?php
/**
 * @brief		Uninstall callback
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		10 Feb 2016
 */

namespace IPS\cms\extensions\core\Uninstall;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\UninstallAbstract;
use IPS\Widget\Area;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Uninstall callback
 */
class Widgets extends UninstallAbstract
{
	/**
	 * Code to execute when other applications or plugins are uninstalled
	 *
	 * @param string $application Application directory
	 * @return    void
	 */
	public function onOtherUninstall( string $application ) : void
	{
		/* clean up widget areas table */
		foreach ( Db::i()->select( '*', 'cms_page_widget_areas' ) as $row )
		{
			$deleted = false;
			if( $row['area_widgets'] and $data = json_decode( $row['area_widgets'], true ) )
			{
				$area = Area::create( $row['area_area'], $data );
			}
			elseif( $row['area_tree'] )
			{
				$area = new Area( json_decode( $row['area_tree'], true ), $row['area_area'] );
			}
			else
			{
				continue;
			}

			foreach( $area->getAllWidgets() as $widget )
			{
				if ( isset( $widget['app'] ) and $widget['app'] == $application )
				{
					$deleted = true;
					$area->removeWidget( $widget['unique'] );
				}
			}

			if ( $deleted === true )
			{
				Db::i()->update( 'cms_page_widget_areas', array( 'area_tree' => json_encode( $area->toArray( true, false ) ) ), array( 'area_page_id=? AND area_area=?', $row['area_page_id'], $row['area_area'] ) );
			}
		}
	}
}