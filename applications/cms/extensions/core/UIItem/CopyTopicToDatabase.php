<?php

namespace IPS\cms\extensions\core\UIItem;

use IPS\Content\Item as BaseItem;
use IPS\forums\Topic;
use IPS\Helpers\Menu;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output\UI\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content UI extension: CopyTopicToDatabase
 */
class CopyTopicToDatabase extends Item
{

	/**
	 * @brief	Class to extend
	 */
	 public static ?string $class = Topic::class;

	 public function menuItems( BaseItem $item ): array
	 {
		 $newLinks = [];
		 if( $item->hidden() != -2 and Member::loggedIn()->modPermission( 'can_copy_topic_database' ) )
		 {
		 	$link = new Menu\Link( Url::internal( 'app=cms&module=database&controller=topic&id=' . $item->tid . '&_new=1', 'front', 'topic_copy', $item->title_seo ), 'copy_topic_to_database' );
			$link->opensDialog( 'copy_select_database', 'narrow', remoteSubmit: true );
			$link->position = 'delete';
			$newLinks['cms'] = $link;
		 }
		 return $newLinks;
	 }
}