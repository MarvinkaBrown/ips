<?php

/**
 * @brief        EditorMediaAbstract
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

abstract class EditorMediaAbstract
{
	/**
	 * Get Counts
	 *
	 * @param	Member	$member		The member
	 * @param	string		$postKey	The post key
	 * @param	string|null	$search		The search term (or NULL for all)
	 * @return	array|int
	 */
	abstract public function count( Member $member, string $postKey, ?string $search=NULL ): array|int;

	/**
	 * Get Files
	 *
	 * @param	Member	$member	The member
	 * @param	string|null	$search	The search term (or NULL for all)
	 * @param	string		$postKey	The post key
	 * @param	int			$page	Page
	 * @param	int			$limit	Number to get
	 * @return	array		array( 'Title' => array( 'http://www.example.com/file1.txt' => \IPS\File, 'http://www.example.com/file2.txt' => \IPS\File, ... ), ... )
	 */
	abstract public function get( Member $member, ?string $search, string $postKey, int $page, int $limit ): array;
}