<?php
/**
 * @brief		SearchContent extension: Entries
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blogs
 * @since		10 Jul 2023
 */

namespace IPS\blog\extensions\core\SearchContent;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Entry;
use IPS\Content\Search\SearchContentAbstract;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SearchContent extension: Entries
 */
class Entries extends SearchContentAbstract
{
	/**
	 * Return all searchable classes in your application,
	 * including comments and/or reviews
	 *
	 * @return array
	 */
	public static function supportedClasses() : array
	{
		return array(
			Entry::class,
			Entry\Comment::class
		);
	}

	/**
	 * Search Index Permissions
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function searchIndexPermissions(): string
	{
		if( $this->object instanceof Entry AND $this->object->status == 'draft' )
		{
			return '0';
		}

		return parent::searchIndexPermissions();
	}
}