<?php

/**
 * @brief        LiveSearchAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/17/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class LiveSearchAbstract
{
	/**
	 * Get the search results
	 *
	 * @param	string	$searchTerm	Search Term
	 * @return	array 	Array of results
	 */
	abstract public function getResults( string $searchTerm ): array;

	/**
	 * Check we have access
	 *
	 * @return	bool
	 */
	abstract public function hasAccess(): bool;

	/**
	 * Is default for current page?
	 *
	 * @return	bool
	 */
	abstract public function isDefault(): bool;
}