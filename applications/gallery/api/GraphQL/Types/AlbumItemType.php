<?php
/**
 * @brief		GraphQL: Album Item Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		24 Feb 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Content\Api\GraphQL\ItemType;
use IPS\gallery\api\GraphQL\TypeRegistry;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * AlbumItemType for GraphQL API
 */
class AlbumItemType extends ItemType
{
	/*
	 * @brief 	The item classname we use for this type
	 */
	protected static string $itemClass	= '\IPS\gallery\Album\Item';

	/*
	 * @brief 	GraphQL type name
	 */
	protected static string $typeName = 'gallery_AlbumItem';

	/*
	 * @brief 	GraphQL type description
	 */
	protected static string $typeDescription = 'An album';

	/*
	 * @brief 	Follow data passed in to FollowType resolver
	 */
	protected static array $followData = array('app' => 'gallery', 'area' => 'album');

	/**
	 * Return the fields available in this type
	 *
	 * @return	array
	 */
	public function fields(): array
	{
		// Extend our fields with image-specific stuff
		$defaultFields = parent::fields();

		// Remove duplicated fields
		unset( $defaultFields['views'] );
		unset( $defaultFields['poll'] );
		unset( $defaultFields['hasPoll'] );

		return $defaultFields;
	}

	/**
	 * Get the comment type that goes with this item type
	 *
	 * @return	ObjectType
	 */
	protected static function getCommentType(): ObjectType
	{
		return TypeRegistry::albumComment();
	}
}
