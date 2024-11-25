<?php
/**
 * @brief		GraphQL: Types registry
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Feb 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\api\GraphQL;
use IPS\gallery\api\GraphQL\Types\AlbumCommentType;
use IPS\gallery\api\GraphQL\Types\AlbumItemType;
use IPS\gallery\api\GraphQL\Types\AlbumType;
use IPS\gallery\api\GraphQL\Types\ImageCommentType;
use IPS\gallery\api\GraphQL\Types\ImageType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Gallery type registry. GraphQL requires exactly one instance of each type,
 * so we'll generate singletons here.
 * @todo automate this somehow?
 */
class TypeRegistry
{
    /**
     * Returns the album instance
     *
     * @var AlbumType|null
     */
    protected static ?AlbumType $album = null;

    /**
     * Returns the album comment instance
     *
     * @var AlbumCommentType|null
     */
    protected static ?AlbumCommentType $albumComment = null;

    /**
     * Returns the album item instance
     *
     * @var AlbumItemType|null
     */
    protected static ?AlbumItemType $albumItem = null;

    /**
     * Returns the image instance
     *
     * @var ImageType|null
     */
    protected static ?ImageType $image = null;

    /**
     * Returns the Image comment instance
     *
     * @var ImageCommentType|null
     */
    protected static ?ImageCommentType $imageComment = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Defined to suppress static warnings
	}

	/**
	 * @return AlbumType
	 */
	public static function album() : AlbumType
	{
		return self::$album ?: (self::$album = new AlbumType());
	}

	/**
	 * @return AlbumCommentType
	 */
	public static function albumComment() : AlbumCommentType
	{
		return self::$albumComment ?: (self::$albumComment = new AlbumCommentType());
	}

	/**
	 * @return AlbumItemType
	 */
	public static function albumItem() : AlbumItemType
	{
		return self::$albumItem ?: (self::$albumItem = new AlbumItemType());
	}

	/**
	 * @return ImageType
	 */
	public static function image() : ImageType
	{
		return self::$image ?: (self::$image = new ImageType());
	}

	/**
	 * @return ImageCommentType
	 */
	public static function imageComment() : ImageCommentType
	{
		return self::$imageComment ?: (self::$imageComment = new ImageCommentType());
	}
}