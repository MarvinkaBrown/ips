<?php
/**
 * @brief		GraphQL: Reply to topic mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\blog\api\GraphQL\Types\CommentType;
use IPS\blog\Entry;
use IPS\blog\Entry\Comment;
use IPS\Content\Api\GraphQL\CommentMutator;
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
 * Reply to entry mutation for GraphQL API
 */
class ReplyEntry extends CommentMutator
{
    /**
     * Class
     */
    protected string $class = Comment::class;

    /*
     * @brief 	Query description
     */
    public static string $description = "Create a new comment";

    /*
     * Mutation arguments
     */
    public function args(): array
    {
        return [
            'entryID'		=> TypeRegistry::nonNull( TypeRegistry::id() ),
            'content'		=> TypeRegistry::nonNull( TypeRegistry::string() ),
            'replyingTo'	=> TypeRegistry::id(),
            'postKey'		=> TypeRegistry::string()
        ];
    }

    /**
     * Return the mutation return type
     */
    public function type(): CommentType
    {
        return \IPS\blog\api\GraphQL\TypeRegistry::comment();
    }

    /**
     * Resolves this mutation
     *
     * @param 	mixed $val 	Value passed into this resolver
     * @param 	array $args 	Arguments
     * @param 	array $context 	Context values
	 * @param 	array $info
     * @return	Comment
     */
    public function resolve( mixed $val, array $args, array $context, array $info): Comment
    {
        /* Get topic */
        try
        {
            $entry = Entry::loadAndCheckPerms( $args['entryID'] );
        }
        catch ( OutOfRangeException )
        {
            throw new SafeException( 'NO_TOPIC', '1F295/1_graphql', 403 );
        }

        /* Get author */
        if ( !$entry->canComment( Member::loggedIn() ) )
        {
            throw new SafeException( 'NO_PERMISSION', '2F294/A_graphql', 403 );
        }

        /* Check we have a post */
        if ( !$args['content'] )
        {
            throw new SafeException( 'NO_POST', '1F295/3_graphql', 403 );
        }

        $originalPost = NULL;

        if( isset( $args['replyingTo'] ) )
        {
            try
            {
                $originalPost = Comment::loadAndCheckPerms( $args['replyingTo'] );
            }
            catch ( OutOfRangeException )
            {
                // Just ignore it
            }
        }

        /* Do it */
        return $this->_createComment( $args, $entry, $args['postKey'] ?? NULL, $originalPost );
    }
}
