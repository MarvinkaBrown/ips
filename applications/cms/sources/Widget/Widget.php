<?php
/**
 * @brief		CMS Widgets
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		13 Oct 2014
 */

namespace IPS\cms;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Db;
use IPS\Widget as SystemWidget;
use IPS\Widget\Area;
use function count;
use function defined;
use function in_array;
use function is_array;
use function json_decode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * CMS Widgets
 */
class Widget extends SystemWidget
{
	/**
	 * Delete caches. We need a different name from the parent class otherwise the Pages app hook will get stuck in infinite recursion
	 *
	 * @param String|null $key				Widget key
	 * @param String|null $app				Parent application
	 * @param String|null $plugin				Parent plugin
	 * @return	void
	 */
	static public function deleteCachesForBlocks( string $key=NULL, string $app=NULL, string $plugin=NULL ) : void
	{
		/* Delete any custom block caches relevant to this plug in */
		if ( $key OR $app )
		{
			$where = array( array( 'block_type=?', 'plugin' ) );

			if( $key )
			{
				$where[] = array( 'block_key=?', (string) $key );
			}

			if( $app )
			{
				$where[] = array( 'block_plugin_app=?', (string) $app );
			}

			$blocks = array();
			foreach( Db::i()->select( '*', 'cms_blocks', $where ) as $row )
			{
				$blocks[ $row['block_key'] ] = $row;
			}

			if ( count( $blocks ) )
			{
				$uniqueIds = array();
				foreach( Db::i()->select( '*', 'cms_page_widget_areas' ) as $item )
				{
					if( $item['area_widgets'] )
					{
						$widgets = json_decode( $item['area_widgets'], TRUE );
					}
					else
					{
						$area = new Area( json_decode( $item['area_tree'], true ), $item['area_area'] );
						$widgets = $area->getAllWidgets();
					}

					if( !is_array( $widgets ) or !count( $widgets ) )
					{
						continue;
					}

					foreach( $widgets as $widget )
					{
						if ( ( isset( $widget['app'] ) and $widget['app'] === 'cms' ) and $widget['key'] === 'Blocks' and isset( $widget['unique'] ) and isset( $widget['configuration'] ) and isset( $widget['configuration']['cms_widget_custom_block'] ) )
						{
							if ( in_array( $widget['configuration']['cms_widget_custom_block'], array_keys( $blocks ) ) )
							{
								$uniqueIds[] = $widget['unique'];
							}
						}
					}
				}

				foreach( Db::i()->select( '*', 'core_widget_areas' ) as $item )
				{
					if( $item['widgets'] )
					{
						$widgets = json_decode( $item['widgets'], TRUE );
					}
					else
					{
						$area = new Area( $item['area'], json_decode( $item['tree'], true ) );
						$widgets = $area->getAllWidgets();
					}

					if( !is_array( $widgets ) or !count( $widgets ) )
					{
						continue;
					}

					foreach( $widgets as $widget )
					{
						if ( ( isset( $widget['app'] ) and $widget['app'] === 'cms' ) and $widget['key'] === 'Blocks' and isset( $widget['unique'] ) and isset( $widget['configuration'] ) and isset( $widget['configuration']['cms_widget_custom_block'] ) )
						{
							if ( in_array( $widget['configuration']['cms_widget_custom_block'], array_keys( $blocks ) ) )
							{
								$uniqueIds[] = $widget['unique'];
							}
						}
					}
				}

				if ( count( $uniqueIds ) )
				{
					$widgetRow = Db::i()->select( '*', 'core_widgets', array( '`key`=? and app=?', 'Blocks', 'cms' ) )->first();

					if ( ! empty( $widgetRow['caches'] ) )
					{
						$caches = json_decode( $widgetRow['caches'], TRUE );

						if ( is_array( $caches ) )
						{
							$save  = $caches;
							foreach( $caches as $key => $time )
							{
								foreach( $uniqueIds as $id )
								{
									if ( mb_stristr( $key, 'widget_Blocks_' . $id ) )
									{
										if ( isset( Store::i()->$key ) )
										{
											unset( Store::i()->$key );
										}

										unset( $save[ $key ] );
									}
								}
							}

							if ( count( $save ) !== count( $caches ) )
							{
								Db::i()->update( 'core_widgets', array( 'caches' => ( count( $save ) ? json_encode( $save ) : NULL ) ), array( 'id=?', $widgetRow['id'] ) );
								unset( Store::i()->widgets );
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Return unique IDs in use
	 *
	 * @return array
	 */
	public static function getUniqueIds(): array
	{
		$uniqueIds = parent::getUniqueIds();
		foreach ( Db::i()->select( '*', 'cms_page_widget_areas' ) as $row )
		{
			if( $row['area_widgets'] )
			{
				$data = json_decode( $row['area_widgets'], TRUE );

				if ( is_countable( $data ) AND  count( $data ) )
				{
					foreach( $data as $widget )
					{
						if ( isset( $widget['unique'] ) )
						{
							$uniqueIds[] = $widget['unique'];
						}
					}
				}
			}

			if( $row['area_tree'] )
			{
				$area = new Area( json_decode( $row['area_tree'], true ), $row['area_area'] );
				foreach( $area->getAllWidgets() as $widget )
				{
					if( isset( $widget['unique'] ) )
					{
						$uniqueIds[] = $widget['unique'];
					}
				}
			}
		}
		
		return array_unique( $uniqueIds );
	}
}
