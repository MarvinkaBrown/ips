<?php

/**
 * @brief        AnnouncementsAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/16/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Announcements\Announcement;
use IPS\Helpers\Form\FormAbstract;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class AnnouncementsAbstract
{
	public static string $idField = "id";

	public static array $controllers = array();

	/**
	 * Get Setting Field
	 *
	 * @param	Announcement|null	$announcement
	 * @return	FormAbstract Form element
	 */
	abstract public function getSettingField( ?Announcement $announcement ): FormAbstract;
}