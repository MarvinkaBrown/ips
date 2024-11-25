<?php
/**
 * @brief		Support Content in sitemaps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Dec 2013
 */

namespace IPS\core\extensions\core\Sitemap;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Application\Module;
use IPS\Content\ExtensionGenerator;
use IPS\Content\Filter;
use IPS\Content\Item;
use IPS\Db;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Sitemap;
use OutOfRangeException;
use UnderflowException;
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
 * Support Content in sitemaps
 */
class Content extends ExtensionGenerator
{
	/**
	 * @brief	If TRUE, will prevent comment classes being included
	 */
	protected static bool $contentItemsOnly = TRUE;
	
	const RECOMMENDED_NODE_PRIORIY = 0.6;
	const RECOMMENDED_ITEM_PRIORITY = 1;
	const RECOMMENDED_ITEM_LIMIT = -1;
	
	/**
	 * @brief	Recommended Settings
	 */
	public array $recommendedSettings = array();
	
	/**
	 * Add settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings(): array
	{
		/* @var Item $class */
		$class = $this->class;
		if ( $class::$includeInSitemap === FALSE )
		{
			return array();
		}
		
		if ( isset( $class::$containerNodeClass ) )
		{
			$nodeClass = $class::$containerNodeClass;
			$this->recommendedSettings["sitemap_{$nodeClass::$nodeTitle}_priority"] = self::RECOMMENDED_NODE_PRIORIY;
		}
		
		$this->recommendedSettings["sitemap_{$class::$title}_count"] = (string) self::RECOMMENDED_ITEM_LIMIT;
		$this->recommendedSettings["sitemap_{$class::$title}_priority"] = self::RECOMMENDED_ITEM_PRIORITY;
		$this->recommendedSettings["sitemap_{$class::$title}_include"] = true;
	
		$settings = Settings::i()->sitemap_content_settings ? json_decode( Settings::i()->sitemap_content_settings, TRUE ) : array();
	
		Member::loggedIn()->language()->words[ 'sitemap_core_Content_' . mb_substr( str_replace( '\\', '_', $class ), 4 ) ] = Member::loggedIn()->language()->addToStack( $class::$title . '_pl', FALSE );
		Member::loggedIn()->language()->words[ "sitemap_{$class::$title}_priority_desc" ] = Member::loggedIn()->language()->addToStack( 'sitemap_priority_generic_desc', FALSE );
	
		$return = array();

		$countToInclude = $settings["sitemap_{$class::$title}_count"] ?? $this->recommendedSettings["sitemap_{$class::$title}_count"];

		Member::loggedIn()->language()->words[ "sitemap_{$class::$title}_include" ] = Member::loggedIn()->language()->addToStack( 'sitemap_include_generic_desc', FALSE );
		$toggles = array( "sitemap_{$class::$title}_count", "sitemap_{$class::$title}_priority" );

		if ( isset( $class::$containerNodeClass ) )
		{
			$toggles[] = "sitemap_{$nodeClass::$nodeTitle}_priority";
		}

		$return["sitemap_{$class::$title}_include"] = new YesNo( "sitemap_{$class::$title}_include", $countToInclude != 0, FALSE, array( 'togglesOn' => $toggles ), NULL, NULL, NULL, "sitemap_{$class::$title}_include" );

		if ( isset( $class::$containerNodeClass ) )
		{
			/* @var Model $nodeClass */
			Member::loggedIn()->language()->words[ "sitemap_{$nodeClass::$nodeTitle}_priority_desc" ] = Member::loggedIn()->language()->addToStack( 'sitemap_priority_generic_desc', FALSE );
			$return["sitemap_{$nodeClass::$nodeTitle}_priority"] = new Select( "sitemap_{$nodeClass::$nodeTitle}_priority", $settings["sitemap_{$nodeClass::$nodeTitle}_priority"] ?? $this->recommendedSettings["sitemap_{$nodeClass::$nodeTitle}_priority"], FALSE, array( 'options' => Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, "sitemap_{$nodeClass::$nodeTitle}_priority" );
			$return["sitemap_{$nodeClass::$nodeTitle}_priority"]->label	= Member::loggedIn()->language()->addToStack( 'sitemap_priority_container' );
		}
		
		$return["sitemap_{$class::$title}_count"]	 = new Number( "sitemap_{$class::$title}_count", $countToInclude, FALSE, array( 'min' => '-1', 'unlimited' => '-1' ), NULL, NULL, NULL, "sitemap_{$class::$title}_count" );
		$return["sitemap_{$class::$title}_count"]->label	= Member::loggedIn()->language()->addToStack( 'sitemap_number_generic' );
		$return["sitemap_{$class::$title}_priority"] = new Select( "sitemap_{$class::$title}_priority", $settings["sitemap_{$class::$title}_priority"] ?? $this->recommendedSettings["sitemap_{$class::$title}_priority"], FALSE, array( 'options' => Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, "sitemap_{$class::$title}_priority" );
		$return["sitemap_{$class::$title}_priority"]->label	= Member::loggedIn()->language()->addToStack( 'sitemap_priority_generic' );
		
		return $return;
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
			Settings::i()->changeValues( array( 'sitemap_content_settings' => json_encode( array() ) ) );
		}
		else
		{
			/* @var Item $class */
			$class = $this->class;

			if ( $class::$includeInSitemap === FALSE )
			{
				return;
			}

			$toSave = Settings::i()->sitemap_content_settings ? json_decode( Settings::i()->sitemap_content_settings, TRUE ) : array();
			
			if ( isset( $class::$containerNodeClass ) )
			{
				$nodeClass = $class::$containerNodeClass;
				$toSave["sitemap_{$nodeClass::$nodeTitle}_priority"] = $values["sitemap_{$nodeClass::$nodeTitle}_priority"];	
			}
			
			foreach ( array( "sitemap_{$class::$title}_count", "sitemap_{$class::$title}_priority" ) as $k )
			{
				$toSave[ $k ] = $values[ $k ];
			}

			if( !$values["sitemap_{$class::$title}_include"] )
			{
				$toSave["sitemap_{$class::$title}_count"] = 0;
			}
			
			Settings::i()->changeValues( array( 'sitemap_content_settings' => json_encode( $toSave ) ) );
		}
	}

	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames(): array
	{
		/* @var Item $class */
		$class = $this->class;
	
		if ( $class::$includeInSitemap === FALSE )
		{
			return array();
		}

		$files = array();
		$settings = Settings::i()->sitemap_content_settings ? json_decode( Settings::i()->sitemap_content_settings, TRUE ) : array();

		$requestedCount = $settings["sitemap_{$class::$title}_count"] ?? -1;

		if( $requestedCount == 0 )
		{
			return array();
		}
		
		/* Check that guests can access the content at all */
		try
		{
			$app = Application::load( $class::$application );
			if ( !$app->canAccess( new Member ) )
			{
				throw new OutOfRangeException;
			}

			$module = Module::get( $class::$application, $class::$module, 'front' );
			if ( !$module->can( 'view', new Member ) )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			return array();
		}

		if ( isset( $class::$containerNodeClass ) )
		{
			$containerClass = $class::$containerNodeClass;

			/* We need one file for the nodes */
			$files[] = 'sitemap_content_' . str_replace( '\\', '_', mb_substr( $containerClass, 4 ) );
			$where = [];

			/* Get the count in the most efficient way possible */
			if ( in_array( 'IPS\Content\Permissions', class_implements( $class ) ) )
			{
				$member = new Member;
				$containerWhere = [];
				$categories = [];
				/* @var $permissionMap array */
				$permQuery = Db::i()->select( 'perm_type_id', 'core_permission_index', array( "core_permission_index.app='" . $containerClass::$permApp . "' AND core_permission_index.perm_type='" . $containerClass::$permType . "' AND (" . Db::i()->findInSet( 'perm_' . $containerClass::$permissionMap['read'], $member->permissionArray() ) . ' OR ' . 'perm_' . $containerClass::$permissionMap['read'] . "='*' )" ) );

				/* If we cannot access clubs, skip them */
				if ( IPS::classUsesTrait( $containerClass, 'IPS\Content\ClubContainer' ) AND !$member->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) )
				{
					$containerWhere[] = array( $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::clubIdColumn() . ' IS NULL' );
				}

				if ( count( $containerWhere ) )
				{
					$permQuery->join( $containerClass::$databaseTable, array_merge( $containerWhere, array( 'core_permission_index.perm_type_id=' . $containerClass::$databaseTable . '.' . $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) ), 'STRAIGHT_JOIN' );
				}

				foreach( $permQuery as $result )
				{
					$categories[] = $result;
				}

				if( count( $categories ) )
				{
					$where[] = array( $containerClass::$databasePrefix . $containerClass::$databaseColumnId . ' IN(' . implode( ',', $categories ) . ')' );
				}
			}

			$contentItems = 0;
			foreach( new ActiveRecordIterator( Db::i()->select( '*', $containerClass::$databaseTable, $where ), $containerClass ) as $node )
			{
				$contentItems += $node->_items;
			}
		}
		else
		{
			/* And however many for the content items */
			$contentItems = $class::getItemsWithPermission( $class::sitemapWhere(), NULL, NULL, 'read', Filter::FILTER_AUTOMATIC, 0, new Member, false, false, false, TRUE );
		}

		/* Choose which count to use to calculate the number of filess */
		$usedCount = ( $requestedCount > $contentItems OR $requestedCount <= 0 ) ? $contentItems : $requestedCount;

		$count = ceil( $usedCount / SITEMAP_MAX_PER_FILE );
		for( $i=1; $i <= $count; $i++ )
		{
			$files[] = 'sitemap_content_' . str_replace( '\\', '_', mb_substr( $class, 4 ) ) . '_' . $i;
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
		/* @var Item $class */
		$class = $this->class;
		if ( isset( $class::$containerNodeClass ) )
		{
			$nodeClass = $class::$containerNodeClass;
		}
		$entries	= array();
		$lastId		= 0;
		$settings	= Settings::i()->sitemap_content_settings ? json_decode( Settings::i()->sitemap_content_settings, TRUE ) : array();
		
		if ( isset( $nodeClass ) and $filename == 'sitemap_content_' . str_replace( '\\', '_', mb_substr( $nodeClass, 4 ) ) )
		{
			/* @var Model $nodeClass */
			/* @var array $permissionMap */
			$select = array();
			if ( in_array( 'IPS\Node\Permissions', class_implements( $nodeClass ) ) )
			{
				$select = new ActiveRecordIterator( Db::i()->select( '*', $nodeClass::$databaseTable, array( '(' . Db::i()->findInSet( "perm_{$nodeClass::$permissionMap['read']}", array( Settings::i()->guest_group ) ) . ' OR ' . "perm_{$nodeClass::$permissionMap['read']}=? )", '*' ) )->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id={$nodeClass::$databaseTable}.{$nodeClass::$databasePrefix}{$nodeClass::$databaseColumnId}", $nodeClass::$permApp, $nodeClass::$permType ) ), $nodeClass );
			}
			else if ( $nodeClass::$ownerTypes !== NULL and is_subclass_of( $nodeClass, 'IPS\Node\Model' ) )
			{
                $select = $nodeClass::roots( 'read', new Member );
			}

			foreach ( $select as $node )
			{
				/* We only want nodes we can see, and that have actual content inside */
				if( $node->can( 'view', new Member ) and $node->getContentItemCount() )
				{
					$data = array( 'url' => $node->url(), 'lastmod' => $node->getLastCommentTime( new Member ) );
				
					$priority = intval( $settings["sitemap_{$nodeClass::$nodeTitle}_priority"] ?? self::RECOMMENDED_NODE_PRIORIY );
					if ( $priority !== -1 )
					{
						$data['priority'] = $priority;
					}

					$entries[] = $data;
				}
			}
		}
		else
		{
			$exploded	= explode( '_', $filename );
			$block		= (int) array_pop( $exploded );
			$totalLimit	= ( isset( $settings["sitemap_{$class::$title}_count"] ) AND $settings["sitemap_{$class::$title}_count"] ) ? $settings["sitemap_{$class::$title}_count"] : self::RECOMMENDED_ITEM_LIMIT;
			$offset		= ( $block - 1 ) * SITEMAP_MAX_PER_FILE;
			$limit		= SITEMAP_MAX_PER_FILE;
			
			if ( ! $totalLimit )
			{
				return NULL;
			}
		
			if ( $totalLimit > -1 and ( $offset + $limit ) > $totalLimit )
			{
				$limit = $totalLimit - $offset;
			}

			/* Create limit clause */
			$limitClause	= array( $offset, $limit );

			$where	= $class::sitemapWhere();
			
			$direction = ( $totalLimit > SITEMAP_MAX_PER_FILE ) ? 'ASC' : 'DESC';

			/* Try to fetch the highest ID built in the last sitemap, if it exists */
			try
			{
				$lastId = Db::i()->select( 'last_id', 'core_sitemap', array( array( 'sitemap=?', implode( '_', $exploded ) . '_' . ( $block - 1 ) ) ) )->first();

				if( $lastId > 0 )
				{
					$where[]		= array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnId . ' ' . ( $direction === 'ASC' ? '>' : '<' ) . ' ?', $lastId );
					$limitClause	= $limit;
				}
			}
			catch( UnderflowException $e ){}

			$idColumn = $class::$databaseColumnId;

			$guest = new Member;
			foreach ( $class::getItemsWithPermission( $where, $class::$databasePrefix . $class::$databaseColumnId . ' ' . $direction, $limitClause, 'read', Filter::FILTER_PUBLIC_ONLY, Item::SELECT_IDS_FIRST, new Member, TRUE ) as $item )
			{
				try
				{
					if( !$item->canView( $guest ) )
					{
						/* Update the last ID, so if we get 500 results that cannot be viewed, the last ID updates for the next batch */
						$lastId = $item->$idColumn;
						continue;
					}
	
					$data = array( 'url' => $item->url() );
	
					$lastMod = $item->lastModificationDate();
					
					if ( $lastMod AND $lastMod->getTimestamp() )
					{
						$data['lastmod'] = $lastMod;
					}
				
					$priority = ( $item->sitemapPriority() ?: ( intval( $settings["sitemap_{$class::$title}_priority"] ?? self::RECOMMENDED_ITEM_PRIORITY ) ) );
					if ( $priority !== -1 )
					{
						$data['priority'] = $priority;
					}

					$entries[] = $data;
				}
				catch( Exception $e ) { }

				$lastId = $item->$idColumn;
			}
		}

		$sitemap->buildSitemapFile( $filename, $entries, $lastId );

		return $lastId;
	}

}