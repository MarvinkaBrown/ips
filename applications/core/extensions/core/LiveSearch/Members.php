<?php
/**
 * @brief		ACP Live Search Extension: Members
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Sept 2013
 */

namespace IPS\core\extensions\core\LiveSearch;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Extensions\LiveSearchAbstract;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Members
 */
class Members extends LiveSearchAbstract
{
	/**
	 * @brief	Cutoff to start doing LIKE 'string%' instead of LIKE '%string%'
	 */
	protected static int $inlineSearchCutoff	= 1000000;

	/**
	 * Check we have access
	 *
	 * @return	bool
	 */
	public function hasAccess(): bool
	{
		/* Check Permissions */
		return Member::loggedIn()->hasAcpRestriction( "core","members" );
	}
	
	/**
	 * Get the search results
	 *
	 * @param	string	$searchTerm	Search Term
	 * @return	array 	Array of results
	 */
	public function getResults( string $searchTerm ): array
	{
		/* Check we have access */
		if( !$this->hasAccess() )
		{
			return array();
		}

		/* Init */
		$results = array();
		$searchTerm = mb_strtolower( $searchTerm );
		
		/* Perform the search */
		$members = Db::i()->select( "*", 'core_members', Db::i()->like( array( 'name', 'email' ), $searchTerm, TRUE, TRUE, static::canPerformInlineSearch() ), NULL, 50 ); # Limit to 50 so it doesn't take too long to run

		/* Format results */
		foreach ( $members as $member )
		{
			$member = Member::constructFromData( $member );
			
			$results[] = Theme::i()->getTemplate('livesearch')->member( $member );
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
		return Dispatcher::i()->application->directory == 'core' and Dispatcher::i()->module->key == 'members' and Dispatcher::i()->controller != 'groups';
	}

	/**
	 * Determine if it's safe to perform a partial inline search
	 *
	 * @note	If we have more than 1,000,000 member records we will do a LIKE 'string%' search instead of LIKE '%string%'
	 * @return	bool
	 */
	public static function canPerformInlineSearch() : bool
	{
		/* If the data store entry is present, read it first */
		if( isset( Store::i()->safeInlineSearch ) )
		{
			/* We are over the threshold, return FALSE now */
			if( Store::i()->safeInlineSearch == false )
			{
				return FALSE;
			}
			else
			{
				/* If we haven't checked in 24 hours we should do so again */
				if( Store::i()->safeInlineSearch > ( time() - ( 60 * 60 * 24 ) ) )
				{
					return TRUE;
				}
			}
		}

		/* Get our member count */
		$totalMembers = Db::i()->select( 'COUNT(*)', 'core_members' )->first();

		/* If we have more members than our cutoff, just set a flag as we don't need to recheck this periodically. The total will never materially dip to where we can start performing inline searches again, and worst case scenario the upgrader/support tool would clear the cache anyways. */
		if( $totalMembers > static::$inlineSearchCutoff )
		{
			Store::i()->safeInlineSearch = false;
			return FALSE;
		}
		else
		{
			/* Otherwise we store a timestamp so we can recheck periodically */
			Store::i()->safeInlineSearch = time();
			return TRUE;
		}	
	}
}