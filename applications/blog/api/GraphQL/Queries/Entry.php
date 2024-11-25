<?php
/**
 * @brief		GraphQL: Entry query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Oct 2022
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\api\GraphQL\Queries;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\blog\api\GraphQL\Types\EntryType;
use IPS\blog\Entry as EntryClass;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Entry query for GraphQL API
 */
class Entry
{
    /*
     * @brief 	Query description
     */
    public static string $description = "Returns a Blog Entry";

    /*
     * Query arguments
     */
    public function args(): array
    {
        return array(
            'id' => TypeRegistry::nonNull( TypeRegistry::id() )
        );
    }

    /**
     * Return the query return type
     */
    public function type(): EntryType
    {
        return \IPS\blog\api\GraphQL\TypeRegistry::entry();
    }

    /**
     * Resolves this query
     *
     * @param 	mixed $val 	Value passed into this resolver
     * @param 	array $args 	Arguments
     * @param 	array $context 	Context values
	 * @param 	array $info
     * @return    EntryClass
	 */
    public function resolve( mixed $val, array $args, array $context, array $info): EntryClass
	{
        try
        {
            $entry = EntryClass::loadAndCheckPerms( $args['id'] );
        }
        catch ( OutOfRangeException )
        {
            throw new SafeException( 'NO_TOPIC', '2B300/A_graphql', 400 );
        }

        if( !$entry->can('read') )
        {
            throw new SafeException( 'NO_PERMISSION', '2B300/9_graphql', 403 );
        }

        return $entry;
    }
}
