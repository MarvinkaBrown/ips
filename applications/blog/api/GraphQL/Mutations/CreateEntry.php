<?php
/**
 * @brief		GraphQL: Create blog entry mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 oct 2022
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\blog\api\GraphQL\Types\EntryType;
use IPS\blog\Blog;
use IPS\blog\Entry;
use IPS\Content\Api\GraphQL\ItemMutator;
use IPS\Member;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Create blog entry mutation for GraphQL API
 */
class CreateEntry extends ItemMutator
{
    /**
     * Class
     */
    protected string $class = Entry::class;

    /*
     * @brief 	Query description
     */
    public static string $description = "Create a new blog entry";

    /*
     * Mutation arguments
     */
    public function args(): array
    {
		return [
			'blog' =>  TypeRegistry::nonNull( TypeRegistry::id() )
		];
    }

    /**
     * Return the mutation return type
     */
    public function type(): EntryType
    {
        return \IPS\blog\api\GraphQL\TypeRegistry::entry();
    }

    /**
     * Resolves this mutation
     *
     * @param 	mixed $val 	Value passed into this resolver
     * @param 	array $args 	Arguments
     * @param 	array $context 	Context values
	 * @param   mixed $info
     * @return	Entry
     */
    public function resolve( mixed $val, array $args, array $context, mixed $info): Entry
    {
        /* Get blog */
        try
        {
            $blog = Blog::loadAndCheckPerms( $args['blogID'] );
        }
        catch ( OutOfRangeException )
        {
            throw new SafeException( 'NO_BLOG', '1B300/1_graphql', 400 );
        }

        /* Check permission */
        if ( !$blog->can( 'add', Member::loggedIn() ) )
        {
            throw new SafeException( 'NO_PERMISSION', '1B300/A_graphql', 403 );
        }

        /* Check we have a title and a post */
        if ( !$args['title'] )
        {
            throw new SafeException( 'NO_TITLE', '1B300/4_graphql', 400 );
        }
        if ( !$args['content'] )
        {
            throw new SafeException( 'NO_POST', '1B300/5_grapqhl', 400 );
        }


        return $this->_create( $args, $blog, $args['postKey'] ?? NULL );
    }
}
