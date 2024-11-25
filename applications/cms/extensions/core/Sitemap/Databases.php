<?php


/**
 * @brief		Support Pages Databases in sitemaps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		1 April 2015
 */

namespace IPS\cms\extensions\core\Sitemap;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases as DatabasesClass;
use IPS\cms\Pages\Page;
use IPS\cms\Records;
use IPS\Content\Filter;
use IPS\Db;
use IPS\Extensions\SitemapAbstract;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Sitemap;
use OutOfRangeException;
use function defined;
use function in_array;
use function intval;
use const IPS\SITEMAP_MAX_PER_FILE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Pages Databases in sitemaps
 */
class Databases extends SitemapAbstract
{
	/**
	 * @brief	Recommended Settings
	 */
	public array $recommendedSettings = array(
		'sitemap_databases_include'		=> true,
		'sitemap_databases_count'		=> -1,
		'sitemap_databases_priority'	=> 1
	);
	
	/**
	 * Settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings(): array
	{
		return array(
			'sitemap_databases_include'	=> new YesNo( "sitemap_databases_include", Settings::i()->sitemap_databases_count != 0, FALSE, array( 'togglesOn' => array( "sitemap_databases_count", "sitemap_databases_priority" ) ), NULL, NULL, NULL, "sitemap_databases_include" ),
			'sitemap_databases_count'	 => new Number( 'sitemap_databases_count', Settings::i()->sitemap_databases_count, FALSE, array( 'min' => '-1', 'unlimited' => '-1' ), NULL, NULL, NULL, 'sitemap_databases_count' ),
			'sitemap_databases_priority' => new Select( 'sitemap_databases_priority', Settings::i()->sitemap_databases_priority, FALSE, array( 'options' => Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, 'sitemap_databases_priority' )
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
			Settings::i()->changeValues( array( 'sitemap_databases_count' => $this->recommendedSettings['sitemap_databases_count'], 'sitemap_databases_priority' => $this->recommendedSettings['sitemap_databases_priority'] ) );
		}
		else
		{
			Settings::i()->changeValues( array( 'sitemap_databases_count' => $values['sitemap_databases_include'] ? $values['sitemap_databases_count'] : 0, 'sitemap_databases_priority' => $values['sitemap_databases_priority'] ) );
		}
	}
	
	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames(): array
	{
		/* Are we including? */
		if ( ! Settings::i()->sitemap_databases_count )
		{
			return array();
		}

		$files = array();
		
		/* Check that guests can access the content at all */
		foreach( DatabasesClass::databases() as $database )
		{
			if ( $database->page_id > 0 )
			{
				try
				{
					if ( !$database->can( 'view', new Member ) )
					{
						throw new OutOfRangeException;
					}
				}
				catch ( OutOfRangeException $e )
				{
					continue;
				}

				try
				{
					$page = Page::load( $database->page_id );

					if( !$page->can( 'view', new Member ) )
					{
						throw new OutOfRangeException;
					}
				}
				catch ( OutOfRangeException $e )
				{
					continue;
				}

				/* @var Records $class */
				$class = '\IPS\cms\Records' . $database->id;
				
				if ( isset( $class::$containerNodeClass ) )
				{
					if ( $database->use_categories )
					{
						/* We need one file for the nodes */
						$files[] = $database->id . '_sitemap_database_categories';
					}
				}
				
				/* And however many for the content items */
				$count = ceil( max( (int) $class::getItemsWithPermission( $class::sitemapWhere(), NULL, 10, 'read', Filter::FILTER_PUBLIC_ONLY, 0, new Member, FALSE, FALSE, FALSE, TRUE ), Settings::i()->sitemap_databases_count ) / SITEMAP_MAX_PER_FILE );
				for( $i=1; $i <= $count; $i++ )
				{
					$files[] = $database->id . '_sitemap_database_records_' . $i;
				}
			}
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
		if ( ! Settings::i()->sitemap_databases_count )
		{
			return null;
		}
		
		$tmp = explode( '_', $filename );
		$databaseId = intval( array_shift( $tmp ) );
		try
		{
			$class = '\IPS\cms\Records' . $databaseId;
			if ( isset( $class::$containerNodeClass ) )
			{
				$nodeClass = $class::$containerNodeClass;
			}
			$entries = array();

			if ( isset( $nodeClass ) and $filename == $databaseId . '_sitemap_database_categories' )
			{
				$select = array();
				if ( in_array( 'IPS\Node\Permissions', class_implements( $nodeClass ) ) )
				{
					$select = new ActiveRecordIterator( Db::i()->select( '*', $nodeClass::$databaseTable, array( 'category_database_id=? AND (' . Db::i()->findInSet( 'perm_view', array( Settings::i()->guest_group ) ) . ' OR ' . 'perm_view=? )', $databaseId, '*' ) )->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id={$nodeClass::$databaseTable}.{$nodeClass::$databasePrefix}{$nodeClass::$databaseColumnId}", $nodeClass::$permApp, $nodeClass::$permType ) ), $nodeClass );
				}
				else if ( $nodeClass::$ownerTypes !== NULL and is_subclass_of( $nodeClass, 'IPS\Node\Model' ) )
				{
					$select = $nodeClass::loadByOwner( new Member );
				}

				foreach ( $select as $node )
				{
					/* We only want nodes we can see, and that have actual content inside */
					if( $node->url() !== NULL and $node->can( 'view', new Member ) and ( $node->hasChildren() OR ( $node->show_records and $node->getContentItemCount() ) ) )
					{
						$data = array( 'url' => $node->url(), 'lastmod' => $node->getLastCommentTime( new Member ) );

						$priority = intval( Settings::i()->sitemap_databases_priority );
						if ( $priority !== -1 )
						{
							$data['priority'] = $priority;
						}

						$entries[] = $data;
						$lastId = $node->_id;
					}
				}
			}
			else
			{
				$exploded = explode( '_', $filename );
				$block = (int) array_pop( $exploded );

				$offset = ( $block - 1 ) * SITEMAP_MAX_PER_FILE;
				$limit = SITEMAP_MAX_PER_FILE;

				$totalLimit = Settings::i()->sitemap_databases_count;
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

				/* @var Records $class */
				foreach ( $class::getItemsWithPermission( $class::sitemapWhere(), NULL, array( $offset, $limit ), 'read', Filter::FILTER_PUBLIC_ONLY, 0, new Member, TRUE ) as $item )
				{
					$data = array( 'url' => $item->url() );

					$lastMod = $item->lastModificationDate();

					if ( $lastMod )
					{
						$data['lastmod'] = $lastMod;
					}

					$priority = ( $item->sitemapPriority() ?: ( intval( Settings::i()->sitemap_databases_priority ) ) );
					if ( $priority !== -1 )
					{
						$data['priority'] = $priority;
					}

					$entries[] = $data;
					$lastId = $item->primary_id_field;
				}
			}

			$sitemap->buildSitemapFile( $filename, $entries );
			return $lastId ?? 0;
		}
		catch( OutOfRangeException $e )
		{
			return null;
		}
	}
}