<?php

/**
 * @brief        ApprovalReasonAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        07/23/2024
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */
use IPS\core\Approval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class ApprovalReasonAbstract
{
	/**
	 * Reason Key
	 *
	 * @return	string
	 */
	abstract public function reasonKey(): string;

	/**
	 * Parse Reason
	 * Return an array with 2 elements: lang (the language string to use)
	 * and sprintf (an array of replacements)
	 *
	 * @param	Approval	$approval	Approval row
	 * @return	array
	 */
	abstract public function parseReason( Approval $approval ): array;
}