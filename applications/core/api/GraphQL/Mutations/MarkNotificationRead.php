<?php
/**
 * @brief		GraphQL: Mark a notification read mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		6 Nov 2018
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Application;
use IPS\core\api\GraphQL\Types\NotificationType;
use IPS\Db;
use IPS\Member;
use IPS\Notification\Api;
use UnderflowException;
use function defined;
use function intval;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Mark notification read mutation for GraphQL API
 */
class MarkNotificationRead
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Mark a notification as read";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'id' => TypeRegistry::int()
		];
	}

	/**
	 * Return the mutation return type
	 */
	public function type() : NotificationType
	{
		return \IPS\core\Api\GraphQL\TypeRegistry::notification();
	}

	/**
	 * Resolves this mutation
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @return	array|NULL
	 */
	public function resolve( mixed $val, array $args ) : ?array
	{
		if( !Member::loggedIn()->member_id )
		{
			throw new SafeException( 'NOT_LOGGED_IN', 'GQL/0003/1', 403 );
		}

		$where = array();
		$where[] = array( "notification_app IN('" . implode( "','", array_keys( Application::enabledApplications() ) ) . "')" );
		$where[] = array( "member = ?", Member::loggedIn()->member_id );

		if( isset( $args['id'] ) )
		{
			$where[] = array( "id = ?", intval( $args['id'] ) );
		}

		try 
		{
			$row = Db::i()->select( '*', 'core_notifications', $where )->first();
			$notification = Api::constructFromData( $row );
		}
		catch ( UnderflowException $e )
		{
			if( isset( $args['id'] ) )
			{
				// Only throw an error if we were trying to work on a specific notification
				throw new SafeException( 'INVALID_NOTIFICATION', 'GQL/0003/2', 403 );
			}
		}

		Db::i()->update( 'core_notifications', array( 'read_time' => time() ), $where );
		Member::loggedIn()->recountNotifications();

		if( isset( $notification ) )
		{
			return array( 'notification' => $notification, 'data' => $notification->getData() );
		}

		return NULL;
	}
}
