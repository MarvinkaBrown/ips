<?php
/**
 * @brief		Front Navigation Extension: Menu Header
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		22 Jul 2015
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Menu Header
 */
class MenuHeader
{
	/**
	 * @brief	The language string for the title
	 */
	protected ?string $title = null;
	
	/**
	 * Constructor
	 *
	 * @param	string	$title		The language string for the title
	 */
	public function __construct( string $title )
	{
		$this->title = $title;
	}
		
	/**
	 * Can access?
	 *
	 * @return	bool
	 */
	public function canView(): bool
	{
		return TRUE;
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack( $this->title );
	}
		
	/**
	 * Children
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array|null
	 */
	public function children( bool $noStore=FALSE ): ?array
	{
		return NULL;
	}

	/**
	 * Is this item available for the specified type?
	 *
	 * @param string $type
	 * @return bool
	 */
	public function isAvailableFor( string $type ): bool
	{
		return true;
	}
}