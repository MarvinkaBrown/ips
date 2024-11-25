<?php
/**
 * @brief		GraphQL: Ignore user mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 May 2020
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\IgnoreOptionType;
use IPS\core\Ignore;
use IPS\Member;
use OutOfRangeException;
use function defined;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Ignore user mutation for GraphQL API
 */
class IgnoreUser
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Ignore a member";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'member' => TypeRegistry::nonNull( TypeRegistry::int() ),
			'type' => TypeRegistry::nonNull( TypeRegistry::string() ),
			'isIgnoring' => TypeRegistry::nonNull( TypeRegistry::boolean() )
		];
	}

	/**
	 * Return the mutation return type
	 */
	public function type() : IgnoreOptionType
	{
		return \IPS\core\api\GraphQL\TypeRegistry::ignoreOption();
	}

	/**
	 * Resolves this mutation
	 *
	 * @param 	mixed $val	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @return	array
	 */
	public function resolve( mixed $val, array $args ) : array
	{
		if ( !in_array( $args['type'], Ignore::types() ) )
        {
            throw new SafeException( 'INVALID_TYPE', 'GQL/0006/1', 404 );
        }
        
        $type = $args['type'];
        $member = Member::load( $args['member'] );

        if( !$member->member_id )
        {
            throw new SafeException( 'INVALID_MEMBER', 'GQL/0006/2', 403 );
        }

        if ( $member->member_id == Member::loggedIn()->member_id )
        {
            throw new SafeException( 'NO_IGNORE_SELF', 'GQL/0006/3', 403 );
        }
        
        if ( !$member->canBeIgnored() )
        {
            throw new SafeException( 'NO_IGNORE_MEMBER', 'GQL/0006/4', 403 );
        }

        try
        {
            $ignore = Ignore::load( $member->member_id, 'ignore_ignore_id', array( 'ignore_owner_id=?', Member::loggedIn()->member_id ) );
            $ignore->$type = $args['isIgnoring'];
            $ignore->save();
        }
        catch( OutOfRangeException $e )
        {
            $ignore = new Ignore;
            $ignore->$type = $args['isIgnoring'];
            $ignore->owner_id	= Member::loggedIn()->member_id;
            $ignore->ignore_id	= $member->member_id;
            $ignore->save();
        }

        $return = array(
            'type' => $type,
            'is_being_ignored' => $args['isIgnoring']
        );

        Member::loggedIn()->members_bitoptions['has_no_ignored_users'] = FALSE;
		Member::loggedIn()->save();

		return $return;
	}
}
