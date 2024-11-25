<?php

/**
 * @brief        StreamItemsAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/21/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class StreamItemsAbstract
{
	/**
	 * Is there content to display?
	 *
	 * @param	Member|NULL	$author		The author to limit extra items to
	 * @param	int|NULL	$lastTime	If provided, only items since this date are included. If NULL, it works out which to include based on what results are being shown
	 * @param	int|NULL	$firstTime	If provided, only items before this date are included. If NULL, it works out which to include based on what results are being shown
	 * @return	array	Array of \IPS\Content\Search\Result\Custom objects
	 */
	abstract public function extraItems( ?Member $author=NULL, ?int $lastTime=NULL, ?int $firstTime=NULL ): array;
}