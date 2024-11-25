<?php
/**
 * @brief		Announcements Extension : Forums
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		28 Apr 2014
 */

namespace IPS\forums\extensions\core\Announcements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Announcements\Announcement;
use IPS\Extensions\AnnouncementsAbstract;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Node;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Announcements Extension: Forums
 */
class Forums extends AnnouncementsAbstract
{
	/**
	 * @brief	ID Field
	 */
	public static string $idField = "id";
	
	/**
	 * @brief	Controller classes
	 */
	public static array $controllers = array(
		"IPS\\forums\\modules\\front\\forums\\forums",
		"IPS\\forums\\modules\\front\\forums\\topic",
		"IPS\\forums\\modules\\front\\forums\\index"
	);

	/**
	 * Get Setting Field
	 *
	 * @param Announcement|null $announcement
	 * @return    FormAbstract Form element
	 */
	public function getSettingField( ?Announcement $announcement ): FormAbstract
	{
		return new Node( 'announce_forums', ( $announcement AND $announcement->ids ) ? explode( ",", $announcement->ids ) : 0, FALSE, array( 'class' => 'IPS\forums\Forum', 'zeroVal' => 'any', 'multiple' => TRUE, 'permissionCheck' => function ( $forum )
		{
			return $forum->sub_can_post and !$forum->redirect_url;
		} ), NULL, NULL, NULL, 'announce_forums' );
	}
}