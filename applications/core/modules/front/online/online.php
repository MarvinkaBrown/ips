<?php
/**
 * @brief		Online Users
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Aug 2013
 */

namespace IPS\core\modules\front\online;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Online\Table;
use IPS\DateTime;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Session;
use IPS\Session\Front;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Online Users
 */
class online extends Controller
{
	/**
	 * @brief These properties are used to specify datalayer context properties.
	 *
	 */
	public static array $dataLayerContext = array(
		'community_area' =>  [ 'value' => 'online_user_list', 'odkUpdate' => 'true']
	);

	/**
	 * Show Online Users
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Set Session Location */
		Session::i()->setLocation( Url::internal( 'app=core&module=online&controller=online', 'front', 'online' ), array(), 'loc_viewing_online_users' );

		/* Sessions are written on shutdown so let's do it now instead */
		Front::i()->setTheme( Member::loggedIn()->skin ?: 0 );
		session_write_close();
		
		/* Create the table */
		$table = new Table( Url::internal( 'app=core&module=online&controller=online', 'front', 'online' ) );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'online', 'core', 'front' ), 'onlineUsersTable' );
		$table->rowsTemplate	  = array( Theme::i()->getTemplate( 'online', 'core', 'front' ), 'onlineUsersRow' );
		$table->langPrefix = 'online_users_';
		$table->include = array( 'photo', 'member_name', 'location_lang', 'running_time', 'ip_address', 'login_type' );
		$table->limit = 30;

		/* Custom parsers */
		$table->parsers = array(
			'location_lang'	=> function( $val, $row )
			{
				return Front::getLocation( $row );
			},
			'photo' => function( $val, $row )
			{
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( Member::load( $row['member_id'] ), 'fluid' );
			},
			'running_time' => function( $val )
			{
				return DateTime::ts( $val )->relative();
			},
			'member_name' => function( $val, $row )
			{
				if( $row['member_id'] )
				{
					return Theme::i()->getTemplate( 'global', 'core' )->userLink( Member::load( $row['member_id'] ) );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack( 'guest' );
				}
			},
		);
		
		$table->filters = array(
			'filter_loggedin'	=> 'filter_loggedin',
		);
		
		foreach ( Group::groups( TRUE, TRUE, TRUE ) as $group )
		{
			/* Alias the lang keys */
			$realLangKey = "core_group_{$group->g_id}";
			$fakeLangKey = "online_users_group_{$group->g_id}";
			Member::loggedIn()->language()->words[ $fakeLangKey ] = Member::loggedIn()->language()->addToStack( $realLangKey, FALSE );

			$table->filters[ 'group_' . $group->g_id ] = $group->g_id;
		}

		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Display */
		Output::i()->linkTags['canonical'] = (string) Url::internal( 'app=core&module=online&controller=online', 'front', 'online' );
		Output::i()->title	 = Member::loggedIn()->language()->addToStack('online_users');
		Output::i()->output = Theme::i()->getTemplate( 'online', 'core', 'front' )->onlineUsersList( (string) $table, $table->count );
	}
}