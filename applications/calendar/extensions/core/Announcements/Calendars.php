<?php
/**
 * @brief		Announcements Extension: Calendar
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar	
 * @since		29 Apr 2014
 */

namespace IPS\calendar\extensions\core\Announcements;

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
 * Announcements Extension
 */
class Calendars extends AnnouncementsAbstract
{
	/**
	 * @brief	ID Field
	 */
	public static string $idField = "id";
	
	/**
	 * @brief	Controller classes
	 */
	public static array $controllers = array( "IPS\\calendar\\modules\\front\\calendar\\view" );
	
	/**
	 * Get Setting Field
	 *
	 * @param Announcement|null $announcement
	 * @return    FormAbstract Form element
	 */
	public function getSettingField( ?Announcement $announcement ): FormAbstract
	{
		return new Node( 'announce_calendars', ( $announcement AND $announcement->ids ) ? explode( ",", $announcement->ids ) : 0, FALSE, array( 'class' => 'IPS\calendar\Calendar', 'zeroVal' => 'any', 'multiple' => TRUE ), NULL, NULL, NULL, 'announce_calendars' );
	}
}