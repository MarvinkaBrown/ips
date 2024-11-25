<?php
/**
 * @brief		GraphQL: Stream query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\StreamType;
use IPS\core\Stream as StreamClass;
use IPS\Member;
use OutOfRangeException;
use function defined;
use function intval;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stream query for GraphQL API
 */
class Stream
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns an activity stream";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			'id' => TypeRegistry::id()
		);
	}

	/**
	 * Return the query return type
	 */
	public function type() : StreamType
	{
		return \IPS\core\api\GraphQL\TypeRegistry::stream();
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @return	StreamClass|null
	 */
	public function resolve( mixed $val, array $args, array $context ) : ?StreamClass
	{
		if( isset( $args['id'] ) && intval( $args['id'] ) )
		{
			try
			{
				$stream = StreamClass::load( $args['id'] );
			}
			catch ( OutOfRangeException $e )
			{
				return NULL;
			}

			/* Suitable for guests? */
			if ( !Member::loggedIn()->member_id and !( ( $stream->ownership == 'all' or $stream->ownership == 'custom' ) and $stream->read == 'all' and $stream->follow == 'all' and $stream->date_type != 'last_visit' ) )
			{
				throw new SafeException( 'INVALID_STREAM', 'GQL/0003/1', 403 );
			}
		}
		else
		{
			/* Start with a blank stream */
			$stream = StreamClass::allActivityStream();
		}

		return $stream;
	}
}
