<?php
/**
 * @brief		GraphQL: ImageComment Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Feb 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Content\Api\GraphQL\CommentType;
use IPS\gallery\api\GraphQL\TypeRegistry;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ImageCommentType for GraphQL API
 */
class ImageCommentType extends CommentType
{
	/*
	 * @brief 	The item classname we use for this type
	 */
	protected static string $commentClass	= '\IPS\gallery\Image\Comment';

	/*
	 * @brief 	GraphQL type name
	 */
	protected static string $typeName = 'gallery_ImageComment';

	/*
	 * @brief 	GraphQL type description
	 */
	protected static string $typeDescription = 'An image comment';

	/**
	 * Get the item type that goes with this item type
	 *
	 * @return	ObjectType
	 */
	public static function getItemType(): ObjectType
	{
		return TypeRegistry::image();
	}
}
