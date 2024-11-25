<?php
/**
 * @brief		GraphQL: Core controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		7 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL;
use IPS\core\api\GraphQL\Queries\ActiveUsers;
use IPS\core\api\GraphQL\Queries\Club;
use IPS\core\api\GraphQL\Queries\Clubs;
use IPS\core\api\GraphQL\Queries\Content;
use IPS\core\api\GraphQL\Queries\Group;
use IPS\core\api\GraphQL\Queries\Language;
use IPS\core\api\GraphQL\Queries\LoginHandlers;
use IPS\core\api\GraphQL\Queries\Me;
use IPS\core\api\GraphQL\Queries\Member;
use IPS\core\api\GraphQL\Queries\Members;
use IPS\core\api\GraphQL\Queries\MessengerConversation;
use IPS\core\api\GraphQL\Queries\MessengerConversations;
use IPS\core\api\GraphQL\Queries\MessengerFolders;
use IPS\core\api\GraphQL\Queries\NotificationTypes;
use IPS\core\api\GraphQL\Queries\OurPicks;
use IPS\core\api\GraphQL\Queries\PopularContributors;
use IPS\core\api\GraphQL\Queries\Search;
use IPS\core\api\GraphQL\Queries\Settings;
use IPS\core\api\GraphQL\Queries\Stats;
use IPS\core\api\GraphQL\Queries\Stream;
use IPS\core\api\GraphQL\Queries\Streams;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Core controller for GraphQL API
 * @todo maybe this shouldn't be a class since it only has a static method?
 */
abstract class Query
{

	/**
	 * Get the supported query types in this app
	 *
	 * @return	array
	 */
	public static function queries(): array
	{
		return [
			'activeUsers' => new ActiveUsers(),
			'club' => new Club(),
			'clubs' => new Clubs(),
			'content' => new Content(),
			'group' => new Group(),
			'language' => new Language(),
			'loginHandlers' => new LoginHandlers(),
			'me' => new Me(),
			'member' => new Member(),
			'members' => new Members(),
			'messengerConversation' => new MessengerConversation(),
			'messengerConversations' => new MessengerConversations(),
			'messengerFolders' => new MessengerFolders(),
			'notificationTypes' => new NotificationTypes(),
			'ourPicks' => new OurPicks(),
			'popularContributors' => new PopularContributors(),
			'search' => new Search(),
			'settings' => new Settings(),
			'stats' => new Stats(),
			'stream' => new Stream(),
			'streams' => new Streams(),
		];
	}
}
