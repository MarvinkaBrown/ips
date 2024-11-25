<?php
/**
 * @brief		OAuth Clients
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 Apr 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\applications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Api\OAuthClient;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Http\UserAgent;
use IPS\Login;
use IPS\Member;
use IPS\Member\Device;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;
use const IPS\CIC;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\OAUTH_REQUIRES_HTTPS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * OAuth Clients
 */
class oauth extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\Api\OAuthClient';
	
	/**
	 * Show the "add" button in the page root rather than the table root
	 */
	protected bool $_addButtonInRoot = FALSE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'oauth_manage' );
		parent::execute();
	}
	
	/**
	 * View List (checks endpoints are available on https)
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( OAUTH_REQUIRES_HTTPS and mb_substr( Settings::i()->base_url, 0, 8 ) !== 'https://' )
		{
			try
			{
				$response = Url::external( 'https://' . mb_substr( Settings::i()->base_url, 7 ) )->request()->get();
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				Output::i()->output = Theme::i()->getTemplate( 'forms' )->blurb( CIC ? 'oauth_https_warning_cic' : 'oauth_https_warning', TRUE, TRUE );
				return;
			}
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'oauth_clients_blurb', TRUE, TRUE );
		parent::manage();
	}
	
	/**
	 * View Client Details
	 *
	 * @return	void
	 */
	protected function view() : void
	{
		try
		{
			$client = OAuthClient::load( Request::i()->client_id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C362/1', 404, '' );
		}
		
		if ( $client->type === 'mobile' )
		{
			Output::i()->redirect( Url::internal( "app=core&module=mobile&controller=mobile" ) );
		}
		
		$secret = NULL;
		if ( isset( Request::i()->newSecret ) and Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'oauth_secrets' ) )
		{
			Session::i()->csrfCheck();
			
			$secret = Login::generateRandomString( 48 );
			$client->client_secret = password_hash( $secret, PASSWORD_DEFAULT );
			$client->brute_force = NULL;
			$client->save();
			
			Session::i()->log( 'acplogs__oauth_new_secret', array( 'core_oauth_client_' . $client->client_id => TRUE ) );
			Output::i()->bypassCsrfKeyCheck = TRUE;
		}
		
		Output::i()->sidebar['actions'] = $client->getButtons( Url::internal( "app=core&module=applications&controller=oauth&do=view&client_id={$client->client_id}" ) );
		unset( Output::i()->sidebar['actions']['view'] );
		
		$bruteForce = NULL;
		if ( $client->brute_force and $bruteForce = json_decode( $client->brute_force, TRUE ) )
		{
			$data = array();
			foreach ( $bruteForce as $ipAddress => $fails )
			{
				$data[] = array(
					'ip_address'	=> $ipAddress,
					'fails'			=> $fails
				);
			}
			
			$bruteForce = new Custom( $data, Url::internal( "app=core&module=applications&controller=oauth&do=view&client_id={$client->client_id}" ) );
			$bruteForce->langPrefix = 'oauth_brute_force_';
			$bruteForce->rowButtons = function( $row ) use ( $client ) {
				$return = array();
				$return['ban'] = array(
					'icon'	=> 'ban',
					'title'	=> 'oauth_brute_force_ban',
					'link'	=>  Url::internal( "app=core&module=applications&controller=oauth&do=bfRemove&ban=1&client_id={$client->client_id}" )->setQueryString( 'ip', $row['ip_address'] )->csrf()
				);
				if ( $row['fails'] >= 3 )
				{
					$return['unlock'] = array(
						'icon'	=> 'unlock',
						'title'	=> 'oauth_brute_force_unlock',
						'link'	=>  Url::internal( "app=core&module=applications&controller=oauth&do=bfRemove&client_id={$client->client_id}" )->setQueryString( 'ip', $row['ip_address'] )->csrf()
					);
				}
				return $return;
			};
		}
		
		Output::i()->title = $client->_title;
		Output::i()->output = Theme::i()->getTemplate('api')->oauthSecret( $client, $secret, $bruteForce );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=applications&controller=api&tab=oauth" ), 'oauth_clients' );
		Output::i()->breadcrumb[] = array( NULL, $client->_title );
	}
	
	/**
	 * Remove IP Address from bruteforce
	 *
	 * @return	void
	 */
	protected function bfRemove() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$client = OAuthClient::load( Request::i()->client_id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C362/4', 404, '' );
		}
		
		if ( Request::i()->ip and Request::i()->ban )
		{
			Db::i()->insert( 'core_banfilters', array(
				'ban_type'		=> 'ip',
				'ban_content'	=> Request::i()->ip,
				'ban_date'		=> time(),
				'ban_reason'	=> 'OAuth',
			) );
			unset( Store::i()->bannedIpAddresses );
			Session::i()->log( 'acplog__ban_created', array( 'ban_filter_ip_select' => TRUE, Request::i()->ip => FALSE ) );
		}
		else
		{
			Session::i()->log( 'acplogs__oauth_unlock_ip', array( Request::i()->ip => FALSE, 'core_oauth_client_' . $client->client_id => TRUE ) );
		}
		
		$bruteForce = json_decode( $client->brute_force, TRUE );
		unset( $bruteForce[ Request::i()->ip ] );
		$client->brute_force = json_encode( $bruteForce );
		$client->save();
		
		Output::i()->redirect( Url::internal( "app=core&module=applications&controller=oauth&do=view&client_id={$client->client_id}" ) );
	}
	
	/**
	 * View Authorizations
	 *
	 * @return	void
	 */
	protected function tokens() : void
	{
		Dispatcher::i()->checkAcpPermission( 'oauth_tokens' );
		
		$client = NULL;
		$member = NULL;
		try
		{
			if ( isset( Request::i()->client_id ) )
			{
				$client = OAuthClient::load( Request::i()->client_id );
				$baseUrl = Url::internal( "app=core&module=applications&controller=oauth&do=tokens&client_id={$client->client_id}" );
			}
			else
			{
				$member = Member::load( Request::i()->member_id );
				if ( !$member->member_id )
				{
					throw new OutOfRangeException;
				}
				$baseUrl = Url::internal( "app=core&module=applications&controller=oauth&do=tokens&member_id={$member->member_id}" );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C362/3', 404, '' );
		}
		
		if ( $client )
		{
			$columns = array(
				'access_token_expires'	=> (bool) $client->access_token_length,
				'refresh_token_expires'	=> ( $client->use_refresh_tokens and $client->refresh_token_length ),
				'scope'					=> ( $client->scopes and json_decode( $client->scopes ) ),
				'auth_user_agent'		=> ( in_array( 'authorization_code', explode( ',', $client->grant_types ) ) or in_array( 'implicit', explode( ',', $client->grant_types ) ) ),
				'issue_user_agent'		=> ( in_array( 'authorization_code', explode( ',', $client->grant_types ) ) or in_array( 'password', explode( ',', $client->grant_types ) ) or in_array( 'client_credentials', explode( ',', $client->grant_types ) ) ),
			);
		}
		else
		{
			$columns = array(
				'access_token_expires'	=> FALSE,
				'refresh_token_expires'	=> FALSE,
				'scope'					=> FALSE,
				'auth_user_agent'		=> FALSE,
				'issue_user_agent'		=> FALSE,
			);
			
			$count = 0;
			foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_oauth_clients', array( Db::i()->findInSet( 'oauth_grant_types', array( 'authorization_code', 'implicit', 'password' ) ) ) ), 'IPS\Api\OAuthClient' ) as $_client )
			{
				$count++;
				if ( $_client->access_token_length )
				{
					$columns['access_token_expires'] = TRUE;
				}
				if ( $_client->use_refresh_tokens and $_client->refresh_token_length )
				{
					$columns['refresh_token_expires'] = TRUE;
				}
				if ( $_client->scopes and json_decode( $_client->scopes ) )
				{
					$columns['scope'] = TRUE;
				}
				if ( in_array( 'authorization_code', explode( ',', $_client->grant_types ) ) or in_array( 'implicit', explode( ',', $_client->grant_types ) ) )
				{
					$columns['auth_user_agent'] = TRUE;
				}
				if ( in_array( 'authorization_code', explode( ',', $_client->grant_types ) ) or in_array( 'password', explode( ',', $_client->grant_types ) ) or in_array( 'client_credentials', explode( ',', $_client->grant_types ) ) )
				{
					$columns['issue_user_agent'] = TRUE;
				}
			}
		}
		
		$table = new \IPS\Helpers\Table\Db( 'core_oauth_server_access_tokens', $baseUrl, array( $client ? array( 'client_id=?', $client->client_id ) : array( 'member_id=?', $member->member_id ) ) );
		$table->langPrefix = 'oauth_authorization_';
		$table->include = array();
		$table->advancedSearch = array();
		if ( $client )
		{
			$table->include[] = 'member_id';
			$table->advancedSearch['member_id'] = SEARCH_MEMBER;
		}
		elseif ( $count > 1 )
		{
			$table->include[] = 'client_id';
		}
		$table->include[] = 'issued';
		$table->include[] = 'status';
		$table->advancedSearch['issued'] = SEARCH_DATE_RANGE;
		if ( $columns['access_token_expires'] )
		{
			$table->include[] = 'access_token_expires';
			$table->advancedSearch['access_token_expires'] = SEARCH_DATE_RANGE;
		}
		if ( $columns['refresh_token_expires'] )
		{
			$table->include[] = 'refresh_token_expires';
			$table->advancedSearch['refresh_token_expires'] = SEARCH_DATE_RANGE;
		}
		if ( $columns['scope'] )
		{
			$table->include[] = 'scope';
		}
		if ( $columns['auth_user_agent'] )
		{
			$table->include[] = 'auth_user_agent';
		}
		if ( $columns['issue_user_agent'] )
		{
			$table->include[] = 'issue_user_agent';
		}
		$table->noSort = array( 'status', 'scope' );
		$table->sortBy = $table->sortBy ?: 'issued';
		$table->parsers = array(
			'client_id'		=> function( $val ) {
				try
				{
					$client = OAuthClient::load( $val );
					return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( Url::internal( "app=core&module=applications&controller=oauth&do=view&client_id={$client->client_id}" ), FALSE, $client->_title, FALSE );
				}
				catch ( Exception $e )
				{
					return '';
				}
			},
			'member_id'		=> function( $val ) {
				if ( $val )
				{
					$member = Member::load( $val );
					if ( $member->member_id )
					{
						return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . Theme::i()->getTemplate( 'global', 'core' )->userLink( $member, 'tiny' );
					}
					else
					{
						return Member::loggedIn()->language()->addToStack('deleted_member');
					}
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('oauth_client_credentials');
				}
			},
			'issued'		=> function( $val ) {
				return DateTime::ts( $val );
			},
			'access_token_expires' => function( $val ) {
				if ( $val )
				{
					return DateTime::ts( $val );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('never');
				}
			},
			'refresh_token_expires' => function( $val, $row ) {
				if ( OAuthClient::load( $row['client_id'] )->use_refresh_tokens )
				{
					if ( $val )
					{
						return DateTime::ts( $val );
					}
					else
					{
						return Member::loggedIn()->language()->addToStack('never');
					}
				}
				else
				{
					return '';
				}
			},
			'status'		=> function( $val, $row ) {
				return Theme::i()->getTemplate('api')->oauthStatus( $row, OAuthClient::load( $row['client_id'] )->use_refresh_tokens );
			},
			'scope'		=> function( $val ) {
				if ( $val )
				{
					return implode( '<br>', json_decode( $val ) );
				}
				else
				{
					return '';
				}
			},
			'auth_user_agent' => function( $val, $row )
			{
				if ( $row['device_key'] )
				{
					try
					{
						$device = Device::load( $row['device_key'] );
						return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( Url::internal( "app=core&module=members&controller=devices&do=device&key={$row['device_key']}&member={$row['member_id']}" ), FALSE, (string) UserAgent::parse( $val ), FALSE );
					}
					catch ( OutOfRangeException $e ) { }
				}
				return (string) UserAgent::parse( $val );
			},
			'issue_user_agent' => function( $val )
			{
				return  Theme::i()->getTemplate( 'api', 'core', 'admin' )->clientDetails( $val );

			}
		);
		$table->rowButtons = function( $row ) use ( $client ) {
			$return = [];
			if( $row['status'] == 'active')
			{
				$return['revoke'] = array(
					'icon' => 'times-circle',
					'title' => 'oauth_app_revoke',
					'link' => Url::internal( "app=core&module=applications&controller=oauth&do=revokeToken&client_id={$row['client_id']}&member_id={$row['member_id']}&token={$row['access_token']}" )->setQueryString( 'r', $client ? 'c' : 'm' )->csrf(),
					'data' => array( 'confirm' => '', 'confirmMessage' => Member::loggedIn()->language()->addToStack( 'oauth_app_revoke_title' ) )
				);
			}
			return $return;
		};
		$revokeAllLink = $client ? Url::internal( "app=core&module=applications&controller=oauth&do=revokeAllTokens&client_id={$client->client_id}" )->csrf() : Url::internal( "app=core&module=applications&controller=oauth&do=revokeAllTokens&member_id={$member->member_id}" )->csrf();
		$table->rootButtons = array(
			'revoke'	=> array(
				'icon'		=> 'times-circle',
				'title'		=> 'oauth_revoke_all_tokens',
				'link'		=> $revokeAllLink,
				'data'		=> array( 'confirm' => '' )
			)
		);
		
		Output::i()->output = $table;
		if ( $client )
		{
			Output::i()->title = $client->_title;
			if ( $client->type !== 'mobile' )
			{
				Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=applications&controller=api&tab=oauth" ), 'oauth_clients' );
			}
			Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=applications&controller=oauth&do=view&client_id={$client->client_id}" ), $client->_title );
			Output::i()->breadcrumb[] = array( NULL, 'oauth_view_authorizations' );
		}
		else
		{
			Output::i()->title = $member->name;
			Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), $member->name );
			
			if ( $count > 1 )
			{
				Output::i()->breadcrumb[] = array( NULL, 'oauth_member_authorizations' );
			}
			else
			{
				foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_oauth_clients', array( Db::i()->findInSet( 'oauth_grant_types', array( 'authorization_code', 'implicit', 'password' ) ) ) ), 'IPS\Api\OAuthClient' ) as $_client )
				{
					Output::i()->breadcrumb[] = array( NULL, $_client->_title );
					break;
				}
			}
		}
	}
	
	
	/**
	 * Revoke Authorizations
	 *
	 * @return	void
	 */
	protected function revokeToken() : void
	{
		Dispatcher::i()->checkAcpPermission( 'oauth_tokens' );
		Session::i()->csrfCheck();
		
		Db::i()->update( 'core_oauth_server_access_tokens', array( 'status' => 'revoked' ), array( 'client_id=? AND access_token=?', Request::i()->client_id, Request::i()->token ) );
		Session::i()->log( 'acplogs__oauth_revoke_token', array( 'core_oauth_client_' . Request::i()->client_id => TRUE ) );
		
		if ( Request::i()->r === 'c' )
		{
			Output::i()->redirect( Url::internal( "app=core&module=applications&controller=oauth&do=tokens" )->setQueryString( 'client_id', Request::i()->client_id ) );
		}
		elseif ( Request::i()->r === 'p' )
		{
			Output::i()->redirect( Url::internal( "app=core&module=members&controller=members&do=view" )->setQueryString( 'id', Request::i()->member_id ) );
		}
		else
		{
			Output::i()->redirect( Url::internal( "app=core&module=applications&controller=oauth&do=tokens" )->setQueryString( 'member_id', Request::i()->member_id ) );
		}
	}
	
	/**
	 * Revoke ALL Authorizations
	 *
	 * @return	void
	 */
	protected function revokeAllTokens() : void
	{
		Dispatcher::i()->checkAcpPermission( 'oauth_tokens' );
		Session::i()->csrfCheck();
		
		if ( Request::i()->member_id )
		{
			Db::i()->update( 'core_oauth_server_access_tokens', array( 'status' => 'revoked' ), array( 'member_id=?', Request::i()->member_id ) );
			Session::i()->log( 'acplogs__oauth_revoke_member', array( Member::load( Request::i()->member_id )->name => FALSE ) );
			Output::i()->redirect( Url::internal( "app=core&module=applications&controller=oauth&do=tokens" )->setQueryString( 'member_id', Request::i()->member_id ) );
		}
		else
		{					
			Db::i()->update( 'core_oauth_server_access_tokens', array( 'status' => 'revoked' ), array( 'client_id=?', Request::i()->client_id ) );
			Session::i()->log( 'acplogs__oauth_revoke_client', array( 'core_oauth_client_' . Request::i()->client_id => TRUE ) );
			Output::i()->redirect( Url::internal( "app=core&module=applications&controller=oauth&do=tokens" )->setQueryString( 'client_id', Request::i()->client_id ) );
		}
	}

	protected function form() : void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/api.css', 'core', 'admin' ) );
		parent::form();
	}
	/**
	 * Redirect after save
	 *
	 * @param	Model|null	$old			A clone of the node as it was before or NULL if this is a creation
	 * @param	Model	$new			The node now
	 * @param	string			$lastUsedTab	The tab last used in the form
	 * @return void
	 */
	protected function _afterSave( ?Model $old, Model $new, mixed $lastUsedTab = FALSE ): void
	{
		if ( $new->_clientSecret )
		{
			Output::i()->title = $new->_title;
			Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=applications&controller=api&tab=oauth" ), 'oauth_clients' );
			Output::i()->breadcrumb[] = array( NULL, $new->_title );
			Output::i()->output = Theme::i()->getTemplate('api')->oauthSecret( $new, $new->_clientSecret, NULL );
			Output::i()->sidebar['actions'] = $new->getButtons(Url::internal( "app=core&module=applications&controller=oauth&do=view&client_id={$new->client_id}" ));
			unset( Output::i()->sidebar['actions']['view'] );
		}
		else
		{
			parent::_afterSave( $old, $new, $lastUsedTab );
		}
	}
}