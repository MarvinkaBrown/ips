<?php
/**
 * @brief		GraphQL: Post Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\blog\api\GraphQL\TypeRegistry;
use IPS\blog\Entry\Comment;
use IPS\Content\Api\GraphQL\CommentType as ApiCommentType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * PostType for GraphQL API
 */
class CommentType extends ApiCommentType
{
    /*
     * @brief 	The item classname we use for this type
     */
    protected static string $commentClass	= Comment::class;

    /*
     * @brief 	GraphQL type name
     */
    protected static string $typeName = 'blog_Comment';

    /*
     * @brief 	GraphQL type description
     */
    protected static string $typeDescription = 'A blog comment';

    /**
     * Get the item type that goes with this item type
     *
     * @return	ObjectType
     */
    public static function getItemType(): ObjectType
	{
        return TypeRegistry::comment();
    }

    /**
     * Return the fields available in this type
     *
     * @return	array
     */
    public function fields(): array
	{
        $defaultFields = parent::fields();
        $postFields = array(
            'entry' => [
                'type' => TypeRegistry::entry(),
                'resolve' => function ($comment) {
                    return $comment->item();
                }
            ],
        );

        // Remove duplicated fields
        unset( $defaultFields['item'] );

        return array_merge( $defaultFields, $postFields );
    }
}
