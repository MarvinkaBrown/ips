<?php
/**
 * @brief		GraphQL: Topics query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\api\GraphQL\Queries;
use Exception;
use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\blog\api\GraphQL\Types\EntryType;
use IPS\blog\Blog;
use IPS\blog\Entry;
use IPS\Db;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use function count;
use function defined;
use function is_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Topics query for GraphQL API
 */
class Entries
{
    /*
     * @brief 	Query description
     */
    public static string $description = "Returns a list of blog entries";

    /*
     * Query arguments
     */
    public function args(): array
    {
        return array(
            'blogs' => TypeRegistry::listOf( TypeRegistry::int() ),
            'offset' => [
                'type' => TypeRegistry::int(),
                'defaultValue' => 0
            ],
            'limit' => [
                'type' => TypeRegistry::int(),
                'defaultValue' => 25
            ],
            'orderBy' => [
                'type' => TypeRegistry::eNum([
                    'name' => 'blog_order_by',
                    'description' => 'Fields on which event can be sorted',
                    'values' => EntryType::getOrderByOptions()
                ]),
                'defaultValue' => NULL // will use default sort option
            ],
            'orderDir' => [
                'type' => TypeRegistry::eNum([
                    'name' => 'entries_order_dir',
                    'description' => 'Directions in which items can be sorted',
                    'values' => [ 'ASC', 'DESC' ]
                ]),
                'defaultValue' => 'DESC'
            ],
            'honorPinned' => [
                'type' => TypeRegistry::boolean(),
                'defaultValue' => true
            ]
        );
    }

    /**
     * Return the query return type
     */
    public function type(): ListOfType
    {
        return TypeRegistry::listOf( \IPS\blog\api\GraphQL\TypeRegistry::entry() );
    }

    /**
     * Resolves this query
     *
     * @param 	mixed $val 	Value passed into this resolver
     * @param 	array $args 	Arguments
     * @param 	array $context 	Context values
	 * @param 	array $info
     * @return	ActiveRecordIterator
     */
    public function resolve( mixed $val, array $args, array $context, array $info): ActiveRecordIterator
    {
        Blog::loadIntoMemory('view', Member::loggedIn() );

        $blogIds = [];

        /* Are we filtering by blogs? */
        if( isset( $args['blogs'] ) && count( $args['blogs'] ) )
        {
            foreach( $args['blogs'] as $id )
            {
                $blog = Blog::loadAndCheckPerms( $id );
                $blogIds[] = $blog->id;
            }

            if( count( $blogIds ) )
            {
                $where['container'][] = array( Db::i()->in( 'blog_blogs.blog_id', array_filter( $blogIds ) ) );
            }
        }

        /* Get sorting */
        try
        {
            if( $args['orderBy'] === NULL )
            {
                $orderBy = 'last_post';
            }
            else
            {
                $orderBy = Entry::$databaseColumnMap[ $args['orderBy'] ];
            }

            if( $args['orderBy'] === 'last_comment' )
            {
                $orderBy = is_array( $orderBy ) ? array_pop( $orderBy ) : $orderBy;
            }
        }
        catch (Exception)
        {
            $orderBy = 'last_post';
        }

        $sortBy = Entry::$databaseTable . '.' . Entry::$databasePrefix . "{$orderBy} {$args['orderDir']}";
        $offset = max( $args['offset'], 0 );
        $limit = min( $args['limit'], 50 );

        /* Figure out pinned status */
        if ( $args['honorPinned'] )
        {
            $column = Entry::$databaseTable . '.' . Entry::$databasePrefix . Entry::$databaseColumnMap['pinned'];
            $sortBy = "{$column} DESC, {$sortBy}";
        }

        return Entry::getItemsWithPermission( $where, $sortBy, array( $offset, $limit ) );
    }
}
