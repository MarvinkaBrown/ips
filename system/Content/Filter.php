<?php
/**
 * @brief		Filter Interface for Content Models/Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		6 Nov 2013
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Filter Interface
 */
interface Filter
{
	/**
	 * @brief	Filter: Determine if the member is a moderator with permission to view hidden content (default)
	 */
	const FILTER_AUTOMATIC		= NULL;

	/**
	 * @brief	Filter: Show hidden content regardless of permissions (use very cautiously)
	 */
	const FILTER_SHOW_HIDDEN	= TRUE;

	/**
	 * @brief	Filter: Only return unapproved content that the current viewing user submitted
	 */
	const FILTER_OWN_HIDDEN		= FALSE;

	/**
	 * @brief	Filter: Only return hidden content (used primarily for widgets when you select 'hidden', respects permission to view hidden content)
	 */
	const FILTER_ONLY_HIDDEN	= 0;

	/**
	 * @brief	Filter: Only return public content (approved and not hidden, useful when things may get cached)
	 */
	const FILTER_PUBLIC_ONLY	= -1;
}