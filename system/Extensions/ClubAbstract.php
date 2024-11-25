<?php

/**
 * @brief        ClubAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        08/04/2024
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */
use IPS\Member\Club;
use IPS\Node\Model;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class ClubAbstract
{
	/**
	 * Tabs
	 *
	 * @param 	Club $club		The club
	 * @param	Model|null		$container	Container
	 * @return	array
	 */
	abstract public function tabs( Club $club, ?Model $container = NULL ): array;
}