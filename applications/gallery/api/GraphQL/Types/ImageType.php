<?php
/**
 * @brief		GraphQL: Image Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Feb 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Api\GraphQL\ItemType;
use IPS\File;
use IPS\gallery\Album;
use IPS\GeoLocation;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ImageType for GraphQL API
 */
class ImageType extends ItemType
{
	/*
	 * @brief 	The item classname we use for this type
	 */
	protected static string $itemClass	= '\IPS\gallery\Image';

	/*
	 * @brief 	GraphQL type name
	 */
	protected static string $typeName = 'gallery_Image';

	/*
	 * @brief 	GraphQL type description
	 */
	protected static string $typeDescription = 'An image';

	/*
	 * @brief 	Follow data passed in to FollowType resolver
	 */
	protected static array $followData = array('app' => 'gallery', 'area' => 'image');

	/**
	 * Return the fields available in this type
	 *
	 * @return	array
	 */
	public function fields(): array
	{
		// Extend our fields with image-specific stuff
		$defaultFields = parent::fields();
		$imageFields = array(
			'caption' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($image) {
					return $image->caption;
				}
			],
			'hasAlbum' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($image) {
					return (bool) $image->album_id;
				}
			],
			'album' => [
				'type' => \IPS\gallery\api\GraphQL\TypeRegistry::album(),
				'resolve' => function ($image) {
					if( $image->album_id )
					{
						return Album::load( $image->album_id );
					}

					return NULL;
				}
			],
			'nextImage' => [
				'type' => \IPS\gallery\api\GraphQL\TypeRegistry::image(),
				'resolve' => function ($image) {
					return $image->nextItem();
				}
			],
			'prevImage' => [
				'type' => \IPS\gallery\api\GraphQL\TypeRegistry::image(),
				'resolve' => function ($image) {
					return $image->prevItem();
				}
			],
			'credit' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($image) {
					return $image->credit_info;
				}
			],
			'copyright' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($image) {
					return $image->copyright;
				}
			],
			'isMedia' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($image) {
					return (bool) $image->media;
				}
			],
			'maskedFileName' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($image) {
					return (string) File::get( 'gallery_Images', $image->masked_file_name )->url;
				}
			],
			'originalFileName' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($image) {
					return (string) File::get( 'gallery_Images', $image->original_file_name )->url;
				}
			],
			'smallFileName' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($image) {
					return (string) File::get( 'gallery_Images', $image->small_file_name )->url;
				}
			],
			'fileSize' => [
				'type' => TypeRegistry::int(),
				'resolve' => function ($image) {
					return $image->file_size;
				}
			],
			'location' => [
				'type' => GeoLocation\Api\GraphQL\TypeRegistry::geolocation(),
				'resolve' => function ($image) {
					if( $image->gps_raw )
					{
						return GeoLocation::buildFromJson( $image->gps_raw );
					}

					return NULL;
				}
			],
			'dims' => [
				'type' => new ObjectType([
					'name' => 'gallery_ImageSize',
					'fields' => [
						'width' => [
							'type' => TypeRegistry::int(),
							'resolve' => function ($size) {
								return $size[0];
							}
						],
						'height' => [
							'type' => TypeRegistry::int(),
							'resolve' => function ($size) {
								return $size[1];
							}
						]
					]
				]),
				'args' => [
					'size' => TypeRegistry::string()
				],
				'resolve' => function ($image, $args) {
					$imageSizes	= json_decode( $image->data, true );

					if( isset( $imageSizes[ $args['size'] ] ) ){
						return $imageSizes[ $args['size'] ];
					}

					return null;
				}
			]
		);

		// Remove duplicated fields
		unset( $defaultFields['poll'] );
		unset( $defaultFields['hasPoll'] );

		return array_merge( $defaultFields, $imageFields );
	}

	/*public static function args()
	{
		return array_merge( parent::args(), array(
			'password' => [
				'type' => TypeRegistry::string()
			]
		));
	}*/

	/**
	 * Get the comment type that goes with this item type
	 *
	 * @return	ObjectType
	 */
	protected static function getCommentType(): ObjectType
	{
		return \IPS\gallery\api\GraphQL\TypeRegistry::imageComment();
	}
}
