<?php
/**
 * @brief		GraphQL: Core mutations
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL;
use IPS\core\api\GraphQL\Mutations\ChangeNotificationSetting;
use IPS\core\api\GraphQL\Mutations\DeleteAttachment;
use IPS\core\api\GraphQL\Mutations\Follow;
use IPS\core\api\GraphQL\Mutations\IgnoreUser;
use IPS\core\api\GraphQL\Mutations\MarkNotificationRead;
use IPS\core\api\GraphQL\Mutations\Messenger\AddConversationUser;
use IPS\core\api\GraphQL\Mutations\Messenger\LeaveConversation;
use IPS\core\api\GraphQL\Mutations\Messenger\RemoveConversationUser;
use IPS\core\api\GraphQL\Mutations\Unfollow;
use IPS\core\api\GraphQL\Mutations\UploadAttachment;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Core mutationss GraphQL API
 */
abstract class Mutation
{
	/**
	 * Get the supported query types in this app
	 *
	 * @return	array
	 */
	public static function mutations() : array
	{
		return [
			'follow' => new Follow(),
			'unfollow' => new Unfollow(),
			'markNotificationRead' => new MarkNotificationRead(),
			'uploadAttachment' => new UploadAttachment(),
			'deleteAttachment' => new DeleteAttachment(),
			'ignoreMember' => new IgnoreUser(),
			'changeNotificationSetting' => new ChangeNotificationSetting(),
			'leaveConversation' => new LeaveConversation(),
			'removeConversationUser' => new RemoveConversationUser(),
			'addConversationUser' => new AddConversationUser(),
		];
	}
}
