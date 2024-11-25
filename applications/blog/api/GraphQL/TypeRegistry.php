<?php
/**
 * @brief		GraphQL: Types registry
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Oct 2022
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\api\GraphQL;
use IPS\blog\api\GraphQL\Types\BlogType;
use IPS\blog\api\GraphQL\Types\CommentType;
use IPS\blog\api\GraphQL\Types\EntryType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}


class TypeRegistry
{
    /**
     * The blog type instance
     * @var BlogType
     */
    protected static BlogType $blog;

    /**
     * The blog entry type instance
     * @var EntryType
     */
    protected static EntryType $entry;

    /**
     * The entry comment instance
     * @var CommentType
     */
    protected static CommentType $comment;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Defined to suppress static warnings
    }

    /**
     * @return BlogType
     */
    public static function blog(): BlogType
    {
        return self::$blog ?? (self::$blog = new BlogType());
    }

    /**
     * @return EntryType
     */
    public static function entry(): EntryType
    {
        return self::$entry ?? (self::$entry = new EntryType());
    }

    /**
     * @return CommentType
     */
    public static function comment(): CommentType
    {
        return self::$comment??( self::$comment = new CommentType() );
    }
}