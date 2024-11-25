<?php
/**
 * @brief		GraphQL: Image query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Feb 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\api\GraphQL\Queries;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\gallery\api\GraphQL\Types\ImageType;
use IPS\gallery\Image as ImageClass;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Image query for GraphQL API
 */
class Image
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns a gallery image";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			'id' => TypeRegistry::nonNull( TypeRegistry::id() )
		);
	}

	/**
	 * Return the query return type
	 */
	public function type() : ImageType
	{
		return \IPS\gallery\api\GraphQL\TypeRegistry::image();
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val
	 * @param array $args
	 * @param array $context
	 * @param array $info
	 * @return    ImageClass
	 */
	public function resolve( mixed $val, array $args, array $context, array $info ) : ImageClass
	{
		try
		{
			$image = ImageClass::loadAndCheckPerms( $args['id'] );
		}
		catch ( OutOfRangeException )
		{
			throw new SafeException( 'NO_IMAGE', '1F294/2', 400 );
		}

		if( !$image->can('read') )
		{
			throw new SafeException( 'INVALID_ID', '2F294/9', 403 );
		}

		return $image;
	}
}
