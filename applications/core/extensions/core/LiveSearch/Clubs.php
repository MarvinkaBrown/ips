<?php
/**
 * @brief		ACP Live Search Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Apr 2017
 */

namespace IPS\core\extensions\core\LiveSearch;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Extensions\LiveSearchAbstract;
use IPS\Member;
use IPS\Member\Club;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Live Search Extension
 */
class Clubs extends LiveSearchAbstract
{
	/**
	 * Check we have access
	 *
	 * @return	bool
	 */
	public function hasAccess(): bool
	{
		/* Check Permissions */
		return Member::loggedIn()->hasAcpRestriction( 'core', 'clubs', 'clubs_manage' );
	}

	/**
	 * Get the search results
	 *
	 * @param	string	$searchTerm	Search Term
	 * @return	array 	Array of results
	 */
	public function getResults( string $searchTerm ): array
	{
		/* Init */
		$results = array();
		$searchTerm = mb_strtolower( $searchTerm );

		/* Start with categories */
		if( $this->hasAccess() )
		{
			/* Perform the search */
			$clubs = Db::i()->select(
							"*",
							'core_clubs',
							array( "name LIKE CONCAT( '%', ?, '%' )", $searchTerm ),
							NULL,
							NULL
						);
			
			/* Format results */
			foreach ( $clubs as $club )
			{
				$club = Club::constructFromData( $club );
				
				$results[] = Theme::i()->getTemplate( 'livesearch', 'core', 'admin' )->club( $club );
			}
		}

		return $results;
	}
	
	/**
	 * Is default for current page?
	 *
	 * @return	bool
	 */
	public function isDefault(): bool
	{
		return Dispatcher::i()->application->directory == 'core' and Dispatcher::i()->module->key == 'clubs';
	}
}