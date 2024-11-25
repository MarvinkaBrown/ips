<?php
/**
 * @brief		5.0.0 Alpha 6 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		17 Jul 2024
 */

namespace IPS\cms\setup\upg_5000009;

use IPS\Db;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Alpha 6 Upgrade Code
 */
class Upgrade
{
	/**
	 * ...
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		/* Rework the database layouts */
		$databases = iterator_to_array( Db::i()->select( '*', 'cms_databases' ) );
		foreach( $databases as $database )
		{
			if( $database['database_cat_index_type'] )
			{
				$featuredSettings = json_decode( $database['database_featured_settings'], true );
				$index = [ 'type' => ( $featuredSettings['featured'] ? 'featured' : 'all' ), 'layout' => 'grid' ];
			}
			else
			{
				$index = [ 'type' => 'categories', 'layout' => 'table' ];
			}

			$layout = [
				'index' => $index,
				'categories' => ( $database['database_template_categories'] == 'category_index' ? [ 'layout' => 'table', 'template' => null ] : [ 'layout' => 'custom', 'template' => $database['database_template_categories'] ] ),
				'listing' => ( $database['database_template_listing'] == 'listing' ? [ 'layout' => 'table', 'template' => null ] : [ 'layout' => 'custom', 'template' => $database['database_template_listing'] ] ),
				'display' => [ 'layout' => 'custom', 'template' => $database['database_template_display'] ],
				'form' => [ 'layout' => 'custom', 'template' => $database['database_template_form' ] ]
			];

			Db::i()->update( 'cms_databases', [ 'database_display_settings' => json_encode( $layout ) ], [ 'database_id=?', $database['database_id'] ] );
		}

		Db::i()->dropColumn( 'cms_databases', [ 'database_template_listing', 'database_template_display', 'database_template_categories', 'database_cat_index_type', 'database_template_form', 'database_template_featured', 'database_featured_settings' ] );

		return TRUE;
	}

	public function step2() : bool|array
	{
		/* Re-work the category-specific templates */
		$categories = iterator_to_array(
			Db::i()->select( '*', 'cms_database_categories', [ 'category_template_listing is not null and category_template_listing !=?', 0 ] )
		);

		foreach( $categories as $category )
		{
			$listing = ( $category['category_template_listing'] == 'listing' ) ? [ 'layout' => 'table', 'template' => null ] : [ 'layout' => 'custom', 'template' => $category['category_template_listing'] ];
			Db::i()->update( 'cms_database_categories', [ 'category_template_listing' => json_encode( $listing ) ], [ 'category_id=?', $category['category_id'] ] );
		}

		return true;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}