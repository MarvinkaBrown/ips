<?php
/**
 * @brief		Support Pages in sitemaps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		1 April 2015
 */

namespace IPS\cms\extensions\core\Sitemap;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Pages\Page;
use IPS\Db;
use IPS\Extensions\SitemapAbstract;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Settings;
use IPS\Sitemap;
use function defined;
use function intval;
use const IPS\SITEMAP_MAX_PER_FILE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Pages in sitemaps
 */
class Pages extends SitemapAbstract
{
	/**
	 * @brief	Recommended Settings
	 */
	public array $recommendedSettings = array(
		'sitemap_pages_include'		=> true,
		'sitemap_pages_count'		=> -1,
		'sitemap_pages_priority'	=> 1
	);
	
	/**
	 * Settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings(): array
	{
		return array(
			'sitemap_pages_include'	=> new YesNo( "sitemap_pages_include", Settings::i()->sitemap_pages_count != 0, FALSE, array( 'togglesOn' => array( "sitemap_pages_count", "sitemap_pages_priority" ) ), NULL, NULL, NULL, "sitemap_pages_include" ),
			'sitemap_pages_count'	 => new Number( 'sitemap_pages_count', Settings::i()->sitemap_pages_count, FALSE, array( 'min' => '-1', 'unlimited' => '-1' ), NULL, NULL, NULL, 'sitemap_pages_count' ),
			'sitemap_pages_priority' => new Select( 'sitemap_pages_priority', Settings::i()->sitemap_pages_priority, FALSE, array( 'options' => Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, 'sitemap_pages_priority' )
		);
	}

	/**
	 * Save settings for ACP configuration
	 *
	 * @param	array	$values	Values
	 * @return	void
	 */
	public function saveSettings( array $values ) : void
	{
		if ( $values['sitemap_configuration_info'] )
		{
			Settings::i()->changeValues( array( 'sitemap_pages_count' => $this->recommendedSettings['sitemap_pages_count'], 'sitemap_pages_priority' => $this->recommendedSettings['sitemap_pages_priority'] ) );
		}
		else
		{
			Settings::i()->changeValues( array( 'sitemap_pages_count' => $values['sitemap_pages_include'] ? $values['sitemap_pages_count'] : 0, 'sitemap_pages_priority' => $values['sitemap_pages_priority'] ) );
		}
	}
	
	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames(): array
	{
		/* Are we even including? */
		if( Settings::i()->sitemap_pages_count == 0 )
		{
			return array();
		}

		$files  = array();
		$class  = '\IPS\cms\Pages\Page';
		$count  = 0;
		$member = new Member;
		$permissionCheck = 'view';

		/* @var Page $class
		 * @var array $permissionMap */
		$where = array( array( '(' . Db::i()->findInSet( 'perm_' . $class::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'perm_' . $class::$permissionMap[ $permissionCheck ] . '=? )', '*' ) );
		$where[] = [ 'page_meta_index=?', 1 ];

		$count = Db::i()->select( '*', $class::$databaseTable, $where )
				->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $class::$databaseTable . "." . $class::$databasePrefix . $class::$databaseColumnId, $class::$permApp, $class::$permType ) )
				->count();
				
		$count = ceil( max( $count, Settings::i()->sitemap_pages_count ) / SITEMAP_MAX_PER_FILE );
		
		for( $i=1; $i <= $count; $i++ )
		{
			$files[] = 'sitemap_pages_' . $i;
		}

		return $files;
	}

	/**
	 * Generate the sitemap
	 *
	 * @param	string			$filename	The sitemap file to build (should be one returned from getFilenames())
	 * @param	Sitemap	$sitemap	Sitemap object reference
	 * @return	int|null
	 */
	public function generateSitemap( string $filename, Sitemap $sitemap ) : ?int
	{
		/* We have elected to not add databases to the sitemap */
		if ( ! Settings::i()->sitemap_pages_count )
		{
			return null;
		}
		
		$class  = '\IPS\cms\Pages\Page';
		$count  = 0;
		$member = new Member;
		$permissionCheck = 'view';
		$entries = array();
		
		$exploded = explode( '_', $filename );
		$block = (int) array_pop( $exploded );
			
		$offset = ( $block - 1 ) * SITEMAP_MAX_PER_FILE;
		$limit = SITEMAP_MAX_PER_FILE;
		
		$totalLimit = Settings::i()->sitemap_pages_count;
		if ( $totalLimit > -1 and ( $offset + $limit ) > $totalLimit )
		{
			if ( $totalLimit < $limit )
			{
				$limit = $totalLimit;
			}
			else
			{
				$limit = $totalLimit - $offset;
			}
		}

		/* @var Page $class
		 * @var array $permissionMap */
		$where = array( array( '(' . Db::i()->findInSet( 'perm_' . $class::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'perm_' . $class::$permissionMap[ $permissionCheck ] . '=? )', '*' ) );
		$where[] = [ 'page_meta_index=?', 1 ];
		$direction = ( $totalLimit > SITEMAP_MAX_PER_FILE ) ? ' ASC' : ' DESC';
		$select = Db::i()->select( '*', $class::$databaseTable, $where, 'page_id' . $direction, array( $offset, $limit ) )
				->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $class::$databaseTable . "." . $class::$databasePrefix . $class::$databaseColumnId, $class::$permApp, $class::$permType ) );

		foreach( $select as $row )
		{
			$item = $class::constructFromData( $row );
			
			$data = array( 'url' => $item->url() );				
			$priority = intval( Settings::i()->sitemap_pages_priority );
			if ( $priority !== -1 )
			{
				$data['priority'] = $priority;
			}

			$entries[] = $data;
			$lastId = $item->id;
		}

		$sitemap->buildSitemapFile( $filename, $entries );
		return $lastId ?? 0;
	}

}