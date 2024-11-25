<?php
/**
 * @brief		Device Tools
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Mar 2017
 */

namespace IPS\core\modules\admin\members;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadFunctionCallException;
use DateInterval;
use Exception;
use IPS\Api\OAuthClient;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Http\Url;
use IPS\Http\UserAgent;
use IPS\Member;
use IPS\Member\Device;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Session\Store;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Device Tools
 */
class devices extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'membertools_ip' );
		parent::execute();
	}

	/**
	 * View Device Details
	 *
	 * @return	void
	 */
	protected function device() : void
	{
		/* Load */
		$member = Member::load( Request::i()->member );
		try
		{
			$device = Device::loadAndAuthenticate( Request::i()->key, $member );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C355/1', 404, '' );
		}
		$url = Url::internal( "app=core&module=members&controller=devices&do=device&key={$device->device_key}&member={$member->member_id}" );
		
		/* Set title and breadcrumb */
		$userAgent = $device->userAgent();
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'member_names_device', FALSE, array( 'sprintf' => array( $member->name, $userAgent->platform ) ) );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=members&controller=members" ), 'members' );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), $member->name );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=members&controller=devices&do=member&id={$member->member_id}" ), 'members_devices' );
		Output::i()->breadcrumb[] = array( NULL, $userAgent );

		/* Define table */	
		$activeTabContents = '';
		$tabs = array(
			'info'		=> 'device_info',
			'member' 	=> Member::loggedIn()->language()->addToStack( 'device_ips_from_member', FALSE, array( 'sprintf' => array( $member->name ) ) ),
			'others'	=> 'device_other_members'
		);
		$activeTab = ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'info';
		
		if ( $activeTab === 'info' )
		{
			$oauthClients = OAuthClient::roots();
			$apps = array();
			foreach ( Db::i()->select( '*', 'core_oauth_server_access_tokens', array( 'member_id=? AND device_key=?', $member->member_id, $device->device_key ), 'issued DESC' ) as $accessToken )
			{
				if ( isset( $oauthClients[ $accessToken['client_id'] ] ) )
				{
					$apps[] = $accessToken;
				}
			}
			
			$activeTabContents = Theme::i()->getTemplate( 'members' )->deviceInfo( $device, $apps, $oauthClients );
		}
		elseif ( $activeTab === 'member' )
		{
			$table = new \IPS\Helpers\Table\Db( 'core_members_known_ip_addresses', $url->setQueryString( 'tab', 'member' ), array( 'device_key=? AND member_id=?', $device->device_key, $member->member_id ) );
			$table->langPrefix = 'device_table_';
			$table->include = array( 'ip_address', 'location', 'last_seen' );
			$table->sortBy = $table->sortBy ?: 'last_seen';
			$table->parsers = array(
				'location'	=> function( $val, $row ) {
					try
					{
						return GeoLocation::getByIp( $row['ip_address'] );
					}
					catch ( BadFunctionCallException $e )
					{
						return Member::loggedIn()->language()->addToStack('geolocation_enable_service');
					}
					catch ( Exception $e )
					{
						return Member::loggedIn()->language()->addToStack('unknown');
					}
				},
				'last_seen'	=> function( $val ) {
					return DateTime::ts( $val );
				}
			);
			$table->rowButtons = function( $row ) {
				return array(
					'view'	=> array(
						'title'	=> 'view',
						'icon'	=> 'search',
						'link'	=> Url::internal( 'app=core&module=members&controller=ip' )->setQueryString( 'ip', $row['ip_address'] ),
					),
				);
			};
			
			$activeTabContents = Theme::i()->getTemplate( 'forms' )->blurb( Member::loggedIn()->language()->addToStack( 'device_ips_from_member_info', FALSE, array( 'sprintf' => array( $member->name, Url::internal( "app=core&module=members&controller=members&do=ip&id={$member->member_id}" ) ) ) ), TRUE, TRUE ) . $table . Theme::i()->getTemplate( 'members' )->geoipDisclaimer();
		}
		else
		{
			$table = new \IPS\Helpers\Table\Db( 'core_members_known_devices', $url->setQueryString( 'tab', 'others' ), array( 'device_key=? AND member_id<>?', $device->device_key, $member->member_id ) );
			$table->langPrefix = 'device_table_';
			$table->include = array( 'member_id', 'last_seen' );
			$table->sortBy = $table->sortBy ?: 'last_seen';
			$table->parsers = array(
				'member_id'	=> function( $val ) {
					$member = Member::load( $val );
					if ( $member->member_id )
					{
						return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . Theme::i()->getTemplate( 'global', 'core' )->userLink( $member, 'tiny' );
					}
					else
					{
						return Member::loggedIn()->language()->addToStack('deleted_member');
					}
				},
				'last_seen'	=> function( $val ) {
					return DateTime::ts( $val );
				}
			);
			$table->rowButtons = function( $row ) {
				return array(
					'view'	=> array(
						'title'	=> 'view',
						'icon'	=> 'search',
						'link'	=> Url::internal( 'app=core&module=members&controller=members&do=view' )->setQueryString( 'id', $row['member_id'] ),
					),
				);
			};
			
			$activeTabContents = Theme::i()->getTemplate( 'forms' )->blurb( 'device_other_members_info', TRUE, TRUE ) . $table;
		}
		
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
		}
		else
		{
			if ( $device->login_key )
			{
				Output::i()->sidebar['actions']['deauthorize'] = array(
					'icon'		=> 'times',
					'title'		=> 'sign_out',
					'link'		=> Url::internal( "app=core&module=members&controller=devices&do=deauthorize" )->setQueryString( array( 'device' => $device->device_key, 'member' => $member->member_id, 'r' => 'device' ) )->csrf()
				);
			}
			
			Output::i()->output =  Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $activeTabContents, $url );
		}		
	}
	
	/**
	 * View Devices used by a member
	 *
	 * @return	void
	 */
	protected function member() : void
	{
		/* Load */
		$member = Member::load( Request::i()->id );
		if ( !$member->member_id ) 
		{
			Output::i()->error( 'node_error', '2C355/1', 404, '' );
		}
		
		/* Build table */
		$table = new \IPS\Helpers\Table\Db( 'core_members_known_devices', Url::internal("app=core&module=members&controller=devices&do=member&id={$member->member_id}"), array( 'member_id=?', $member->member_id ) );
		$table->langPrefix = 'device_table_';
		$table->include = array( 'user_agent', 'login_key', 'login_handler', 'last_seen', 'other_members' );
		$table->noSort = array( 'login_key', 'other_members' );
		$table->sortBy = $table->sortBy ?: 'last_seen';
		$table->parsers = array(
			'user_agent'	=> function( $val ) {
				return (string) UserAgent::parse( $val );
			},
			'login_key'		=> function( $val, $row ) {
				return Theme::i()->getTemplate('members')->deviceAuthorization( (bool) $val, $row['last_seen'] >= ( new DateTime )->sub( new DateInterval( Device::LOGIN_KEY_VALIDITY ) )->getTimestamp(), $row['anonymous'] );
			},
			'login_handler'		=> function( $val, $row ) {
				return Theme::i()->getTemplate('members')->deviceHandler( $val );
			},
			'last_seen'	=> function( $val ) {
				return DateTime::ts( $val );
			},
			'other_members'	=> function( $val, $row ) {
				if ( Db::i()->select( 'COUNT(*)', 'core_members_known_devices', array( 'device_key=? AND member_id<>?', $row['device_key'], $row['member_id'] ) )->first() )
				{
					return Theme::i()->getTemplate('members')->deviceDuplicate();
				}
				return '';
			},
		);
		$table->rowButtons = function( $row )
		{
			$return = array(
				'view'	=> array(
					'title'	=> 'view',
					'icon'	=> 'search',
					'link'	=> Url::internal( 'app=core&module=members&controller=devices&do=device' )->setQueryString( 'key', $row['device_key'] )->setQueryString( 'member', $row['member_id'] ),
				),
			);
			
			if ( $row['login_key'] )
			{
				$return['deauthorize'] = array(
					'title'	=> 'sign_out',
					'icon'	=> 'times',
					'link'	=> Url::internal( 'app=core&module=members&controller=devices&do=deauthorize' )->setQueryString( array(
						'device'	=> $row['device_key'],
						'member'	=> $row['member_id']
					) )->csrf(),
				);
			}
			
			return $return;
		};
		
		/* Output */
		Output::i()->output = Theme::i()->getTemplate( 'members' )->deviceTable( $table );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'member_names_devices', FALSE, array( 'sprintf' => array( $member->name ) ) );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=members&controller=members" ), 'members' );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), $member->name );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=members&controller=devices&do=member&id={$member->member_id}" ), 'members_devices' );
	}
	
	/**
	 * De-Authorize a Device
	 *
	 * @return	void
	 */
	protected function deauthorize() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$member = Member::load( Request::i()->member );
			$device = Device::loadAndAuthenticate( Request::i()->device, $member );
			$device->login_key = NULL;
			$device->save();
			
			Db::i()->update( 'core_oauth_server_access_tokens', array( 'status' => 'revoked' ), array( 'member_id=? AND device_key=?', $device->member_id, $device->device_key ) );
			
			$member->logHistory( 'core', 'login', array( 'type' => 'logout', 'device' => $device->device_key ) );
			Session::i()->log( 'acplog__members_disabled_autologin', array( $member->name => FALSE ) );
			
			Store::i()->deleteByMember( $device->member_id, $device->user_agent );
		}
		catch ( Exception $e ) { }
		
		if ( isset( Request::i()->r ) and Request::i()->r === 'device' )
		{
			Output::i()->redirect( Url::internal("app=core&module=members&controller=devices&do=device")->setQueryString( array( 'key' => Request::i()->device, 'member' => Request::i()->member ) ) );
		}
		else
		{
			Output::i()->redirect( Url::internal("app=core&module=members&controller=devices&do=member")->setQueryString( 'id', Request::i()->member ) );
		}
	}
}