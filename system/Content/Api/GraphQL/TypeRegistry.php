<?php
/**
 * @brief		Type registry for \IPS\Content types
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 Aug 2018
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Content\Api\GraphQL;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * \IPS\Content base types
 */
class TypeRegistry
{
	protected static CommentType $comment;
	protected static ContentType $content;
	protected static ItemType $item;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Defined to suppress static warnings
	}
	
	/**
	 * @return CommentType
	 */
	public static function comment(): CommentType
	{
		return self::$comment ?? (self::$comment = new CommentType());
	}

	/**
	 * @return ItemType
	 */
	public static function item(): ItemType
	{
		return self::$item ?? (self::$item = new ItemType());
	}

	/**
	 * @return ContentType
	 */
	public static function content(): ContentType
	{
		return self::$content ?? (self::$content = new ContentType());
	}
}