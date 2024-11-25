<?php
/**
 * @brief		Editor Extension: Record Form
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		20 Feb 2014
 */

namespace IPS\cms\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\cms\Pages\Page;
use IPS\Content;
use IPS\Db;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Widget\Area;
use LogicException;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Record Content
 */
class Widgets extends EditorLocationsAbstract
{
	/**
	 * Can we use attachments in this editor?
	 *
	 * @param Member $member
	 * @param Editor $field
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( Member $member, Editor $field ): bool|null
	{
		return TRUE;
	}

	/**
	 * Permission check for attachments
	 *
	 * @param	Member	$member		The member
	 * @param	int|null	$id1		Primary ID
	 * @param	int|null	$id2		Secondary ID
	 * @param	string|null	$id3		Arbitrary data
	 * @param	array		$attachment	The attachment data
	 * @param	bool		$viewOnly	If true, just check if the user can see the attachment rather than download it
	 * @return	bool
	 */
	public function attachmentPermissionCheck( Member $member, ?int $id1, ?int $id2, ?string $id3, array $attachment, bool $viewOnly=FALSE ): bool
	{
		if ( ! $id3 )
		{
			throw new OutOfRangeException;
		}
		
		/* See if it's on a page in Pages */
		$pageId = $this->getPageIdFromWidgetUniqueId( $id3 );
		
		if ( $pageId !== NULL )
		{
			return Page::load( $pageId )->can( 'view', $member );
		}
		
		/* Still here? Look elsewhere */
		$area = $this->getAreaFromWidgetUniqueId( $id3 );
		
		if ( $area !== NULL )
		{
			return Module::get( $area[0], $area[1], 'front' )->can( 'view', $member );
		}
		
		/* Still here? */
		throw new OutOfRangeException;
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return    Content|Member|Model|Url|null
	 * @throws	LogicException
	 */
	public function attachmentLookup( int $id1=NULL, int $id2=NULL, string $id3=NULL ): Model|Content|Url|Member|null
	{
		$pageId = $this->getPageIdFromWidgetUniqueId( $id3 );
		
		if ( $pageId !== NULL )
		{
			return Page::load( $pageId );
		}
		
		$area = $this->getAreaFromWidgetUniqueId( $id3 );
		
		if ( $area !== NULL )
		{
			return Module::get( $area[0], $area[1] );
		}
		
		throw new LogicException;
	}
	
	/**
	 * Returns the page ID based on the widget's unique ID
	 *
	 * @param string $uniqueId	The widget's unique ID
	 * @return	null|int
	 */
	protected function getPageIdFromWidgetUniqueId( string $uniqueId ): ?int
	{
		$pageId = NULL;
		foreach( Db::i()->select( '*', 'cms_page_widget_areas' ) as $item )
		{
			if( $item['area_tree'] )
			{
				$area = new Area( json_decode( $item['area_tree'], true ), $item['area_area'] );
			}
			elseif( $item['area_widgets'] )
			{
				$area = Area::create( $item['area_area'], json_decode( $item['area_widgets'], true ) );
			}
			else
			{
				continue;
			}

			foreach( $area->getAllWidgets() as $widget )
			{
				if ( $widget['unique'] == $uniqueId )
				{
					$pageId = $item['area_page_id'];
				}
			}
		}
		
		return $pageId;
	}
	
	/**
	 * Returns area information if the widget is not on a cms page
	 *
	 * @param string $uniqueId	The widget's unique ID
	 * @return	array|null			Index 0 = Application, Index 1 = Module, Index 2 = Controller
	 */
	protected function getAreaFromWidgetUniqueId( string $uniqueId ): ?array
	{
		$return = NULL;
		foreach( Db::i()->select( '*', 'core_widget_areas' ) AS $row )
		{
			if( $row['tree'] )
			{
				$area = new Area( json_decode( $row['tree'], true ), $row['area'] );
			}
			elseif( $row['widgets'] )
			{
				$area = Area::create( $row['area'], json_decode( $row['widgets'], true ) );
			}
			else
			{
				continue;
			}
			
			foreach( $area->getAllWidgets() AS $widget )
			{
				if ( $widget['unique'] == $uniqueId )
				{
					$return = array( $row['app'], $row['module'], $row['controller'] );
				}
			}
		}
		
		return $return;
	}
}