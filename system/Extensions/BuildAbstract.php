<?php

/**
 * @brief        BuildAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/16/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use RuntimeException;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class BuildAbstract
{
	/**
	 * Build
	 *
	 * @return	void
	 * @throws	RuntimeException
	 */
	abstract public function build() : void;

	/**
	 * Finish Build
	 *
	 * @return	void
	 */
	abstract protected function finish() : void;
}