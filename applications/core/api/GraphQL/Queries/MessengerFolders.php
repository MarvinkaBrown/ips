<?php
/**
 * @brief		GraphQL: Messenger conversation query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Sep 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\MessengerFolderType;
use IPS\Member;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Messenger folders query for GraphQL API
 */
class MessengerFolders
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns the member's messenger folders";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			
		);
	}

	/**
	 * Return the query return type
	 *
	 * @return ListOfType<MessengerFolderType>
	 */
	public function type() : ListOfType
	{
		return TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::messengerFolder() );
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @param array $info
	 * @return	array|null
	 */
	public function resolve( mixed $val, array $args, array $context, array $info ) : ?array
	{
        if( !Member::loggedIn()->member_id || Member::loggedIn()->members_disable_pm )
        {
            return NULL;
        }

		$folderObj = \IPS\core\api\GraphQL\TypeRegistry::messengerFolder();
        return $folderObj->getMemberFolders();
	}
}
