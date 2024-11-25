<?php

/**
 * @brief        DashboardAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/16/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use LogicException;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class DashboardAbstract
{
	/**
	 * Can the current user view this dashboard item?
	 *
	 * @return	bool
	 */
	abstract public function canView(): bool;

	/**
	 * Return the block HTML show on the dashboard
	 *
	 * @return	string
	 */
	abstract public function getBlock(): string;

	/**
	 * Return the block information
	 *
	 * @return	array	array( 'name' => 'Block title', 'key' => 'unique_key', 'size' => [1,2,3], 'by' => 'Author name' )
	 */
	public function getInfo(): array
	{
		return array();
	}

	/**
	 * Save the block data submitted.  This method is only necessary if your block accepts some sort of submitted data to save (such as the 'admin notes' block).
	 *
	 * @return	void
	 * @throws	LogicException
	 */
	public function saveBlock()
	{
	}
}