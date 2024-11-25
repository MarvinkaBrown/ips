<?php
/**
 * @brief		ACP Live Search Extension: Groups
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Sept 2013
 */

namespace IPS\core\extensions\core\LiveSearch;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Extensions\LiveSearchAbstract;
use IPS\Member;
use IPS\Member\Group;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Groups
 */
class Groups extends LiveSearchAbstract
{
	/**
	 * Check we have access
	 *
	 * @return	bool
	 */
	public function hasAccess(): bool
	{
		/* Check Permissions */
		return Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'groups_manage' );
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
		$groups = Db::i()->select(
						"*",
						'core_groups',
						array( "word_custom LIKE CONCAT( '%', ?, '%' ) AND lang_id=?", $searchTerm, Member::loggedIn()->language()->id ),
						NULL,
						NULL
					)->join(
						'core_sys_lang_words',
						"word_key=CONCAT( 'core_group_', g_id )"
					);
		
		
		/* Format results */
		foreach ( $groups as $group )
		{
			$group = Group::constructFromData( $group );
			
			$results[] = Theme::i()->getTemplate('livesearch')->group( $group );
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
		return Dispatcher::i()->application->directory == 'core' and Dispatcher::i()->module->key == 'members' and Dispatcher::i()->controller == 'groups';
	}
}