<?php
/**
 * @brief		GraphQL: Notification types query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		30 Jan 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Application;
use IPS\core\api\GraphQL\Types\NotificationTypeType;
use IPS\Member;
use IPS\Notification;
use function defined;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * NotificationTypes query for GraphQL API
 */
class NotificationTypes
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns available notification types";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array();
	}

	/**
	 * Return the query return type
	 *
	 * @return ListOfType<NotificationTypeType>
	 */
	public function type() : ListOfType
	{
		return TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::notificationType() );
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
		//$defaultConfiguration = \IPS\Notification::defaultConfiguration();
		//$memberConfiguration = $member->notificationsConfiguration();
		$notificationTypes = array();

		$extensions = Application::allExtensions( 'core', 'Notifications' );
		$availableOptions = array();

		foreach( $extensions as $extensionKey => $extension )
		{
			$extensionOptions = Notification::availableOptions( Member::loggedIn(), $extension );

			foreach( $extensionOptions as $key => $option )
			{
				// For now, only return 'standard' types, which are the email/inline/push options
				if( $option['type'] !== 'standard' )
				{
					continue;
				}

				$methods = array('inline' => array(), 'email' => array(), 'push' => array());

				foreach( $methods as $method => $methodData )
				{
					if( !isset( $option['options'][ $method ] ) )
					{
						continue;
					}

					$methods[ $method ]['default'] = isset( $option['default'] ) && in_array( $method, $option['default'] );
					$methods[ $method ]['disabled'] = isset( $option['disabled'] ) && in_array( $method, $option['disabled'] );
					$methods[ $method ]['member'] = ( $option['options'][ $method ]['value'] == TRUE );
				}

				$notificationTypes[] = array(
					'id' => $extensionKey === $key ? $extensionKey : $extensionKey . '_' . $key,
					'extension' => $extensionKey,
					'type' => $key,
					'name' => $option['title'],
					'description' => $option['description'],
					'inline' => $methods['inline'],
					'email' => $methods['email'],
					'push' => $methods['push']
				);
			}
		}

		return $notificationTypes;
	}
}
