<?php

/**
 * @brief        ContentRouterAbstract
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
use IPS\Member\Group;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class ContentRouterAbstract
{
	/**
	 * @brief	Content Item Classes
	 */
	public array $classes = array();

	/**
	 * @brief	Can be shown in similar content
	 */
	public bool $similarContent = FALSE;

	/**
	 * Constructor
	 *
	 * @param Member|Group|null $member If checking access, the member/group to check for, or NULL to not check access
	 */
	abstract public function __construct( Member|Group $member = NULL );
}