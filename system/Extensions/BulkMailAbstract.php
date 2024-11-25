<?php

/**
 * @brief        BulkMailAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/16/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class BulkMailAbstract
{
	/**
	 * Get tags that can be used in bulk mail
	 *
	 * @return	array
	 */
	abstract public function getTags(): array;

	/**
	 * Get value for tags
	 *
	 * @param	string				$content	Bulk Mail Content (passed in case a particular tag is computationally expensive so that the extension may "sniff" for it and elect not to perform the computation if it is not used)
	 * @param	int					$type		0=All, 1=Global, 2=Member-specific
	 * @param	NULL|Member	$member		Member object if $type is 0 or 2
	 * @return	array
	 */
	abstract public function returnTagValues( string $content, int $type, ?Member $member ): array;
}