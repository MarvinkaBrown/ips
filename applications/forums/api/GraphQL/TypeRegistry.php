<?php
/**
 * @brief		GraphQL: Types registry
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		7 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL;
use IPS\forums\api\GraphQL\Types\ForumType;
use IPS\forums\api\GraphQL\Types\PostType;
use IPS\forums\api\GraphQL\Types\TopicType;
use IPS\forums\api\GraphQL\Types\VoteType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Forum type registry. GraphQL requires exactly one instance of each type,
 * so we'll generate singletons here.
 * @todo automate this somehow?
 */
class TypeRegistry
{
    /**
     * Returns the forum instance
     *
     * @var ForumType|null
     */
	protected static ?ForumType $forum = null;

    /**
     * Returns the post instance
     *
     * @var PostType|null
     */
    protected static ?PostType $post = null;

    /**
     * Returns the topic instance
     *
     * @var TopicType|null
     */
    protected static ?TopicType $topic = null;

    /**
     * Returns the vote instance
     *
     * @var VoteType|null
     */
    protected static ?VoteType $vote = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Defined to suppress static warnings
	}

	/**
	 * @return ForumType
	 */
	public static function forum() : ForumType
	{
		return self::$forum ?: (self::$forum = new ForumType());
	}
	
	/**
	 * @return PostType
	 */
	public static function post() : PostType
    {
        return self::$post ?: (self::$post = new PostType());
    }

	/**
	 * @return TopicType
	 */
	public static function topic() : TopicType
	{
		return self::$topic ?: (self::$topic = new TopicType());
	}

	/**
	 * @return VoteType
	 */
	public static function vote() : VoteType
	{
		return self::$vote ?: (self::$vote = new VoteType());
	}
}