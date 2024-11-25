<?php
/**
 * @brief		GraphQL: Streams query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\StreamType;
use IPS\Db;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Streams query for GraphQL API
 */
class Streams
{

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns a list of user's activity streams";

	/*
	 * @brief 	Query arguments
	 */
	public function args(): array
	{
		return array();
	}

	/**
	 * Return the query return type
	 *
	 * @return ListOfType<StreamType>
	 */
	public function type() : ListOfType
	{
		return TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::stream() );
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @return	array
	 */
	public function resolve( mixed $val, array $args, array $context ) : array
	{
		$streams = array();

		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_streams', '`member` IS NULL', 'position ASC' ), 'IPS\core\Stream' ) as $stream )
		{
			if( Member::loggedIn()->member_id || ( !Member::loggedIn()->member_id && ( $stream->ownership == 'all' and $stream->read == 'all' and $stream->follow == 'all' and $stream->date_type != 'last_visit' ) ) )
			{
				$streams[ $stream->id ] = $stream;
			}
		}

		if ( Member::loggedIn()->member_id )
		{
			foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_streams', array( '`member`=?', Member::loggedIn()->member_id ), 'position ASC' ), 'IPS\core\Stream' ) as $stream )
			{
				$streams[ $stream->id ] = $stream;
			}
		}

		return $streams;
	}
}
