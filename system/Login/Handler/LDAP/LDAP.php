<?php
/**
 * @brief		LDAP Login Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 June 2017
 */

namespace IPS\Login\Handler;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Db;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Log;
use IPS\Login;
use IPS\Login\Exception;
use IPS\Login\Handler;
use IPS\Login\Handler\LDAP\Exception as LdapException;
use IPS\Member;
use IPS\Settings;
use LDAP\Connection;
use LDAP\ResultEntry;
use LogicException;
use RuntimeException;
use UnderflowException;
use function defined;
use function extension_loaded;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * LDAP Database Login Handler
 */
class LDAP extends Handler
{
	/**
	 * @brief	Can we have multiple instances of this handler?
	 */
	public static bool $allowMultiple = TRUE;
	
	use UsernamePasswordHandler;
	
	/* !ACP Form */
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public static function getTitle(): string
	{
		return 'login_handler_Ldap';
	}
	
	/**
	 * ACP Settings Form
	 *
	 * @return	array	List of settings to save - settings will be stored to core_login_methods.login_settings DB field
	 * @code
	 	return array( 'savekey'	=> new \IPS\Helpers\Form\[Type]( ... ), ... );
	 * @endcode
	 */
	public function acpForm(): array
	{
		$id = $this->id ?: 'new';

		$return = array(
			'ldap_header',
			'server_protocol'	=> new Radio( 'ldap_server_protocol', $this->settings['server_protocol'] ?? 3, TRUE, array( 'options' => array( 3 => 'V3', 2 => 'V2' ) ) ),
			'server_host'		=> new Text( 'ldap_server_host', $this->settings['server_host'] ?? NULL, TRUE, array( 'placeholder' => 'ldap.example.com' ) ),
			'server_port'		=> new Number( 'ldap_server_port', $this->settings['server_port'] ?? 389, TRUE ),
			'server_user'		=> new Text( 'ldap_server_user', $this->settings['server_user'] ?? NULL ),
			'server_pass'		=> new Text( 'ldap_server_pass', $this->settings['server_pass'] ?? NULL ),
			'opt_referrals'		=> new YesNo( 'ldap_opt_referrals', $this->settings['opt_referrals'] ?? TRUE, TRUE ),
			'ldap_directory',
			'base_dn'			=> new Text( 'ldap_base_dn', $this->settings['base_dn'] ?? NULL, TRUE, array( 'placeholder' => 'dc=example,dc=com' ) ),
			'uid_field'			=> new Text( 'ldap_uid_field', $this->settings['uid_field'] ?? 'uid', TRUE ),
			'un_suffix'			=> new Text( 'ldap_un_suffix', $this->settings['un_suffix'] ?? NULL, FALSE, array( 'placeholder' => '@example.com' ) ),
			'name_field'		=> new Text( 'ldap_name_field', $this->_nameField() ?: 'cn' ),
			'email_field'		=> new Text( 'ldap_email_field', $this->settings['email_field'] ?? 'mail' ),
			'filter'			=> new Text( 'ldap_filter', $this->settings['filter'] ?? NULL, FALSE, array( 'placeholder' => 'ou=your_department' ) ),
			'login_settings',
			'auth_types'	=> new Select( 'login_auth_types', $this->settings['auth_types'] ?? ( Login::AUTH_TYPE_EMAIL ), TRUE, array( 'options' => array(
				Login::AUTH_TYPE_USERNAME + Login::AUTH_TYPE_EMAIL => 'username_or_email',
				Login::AUTH_TYPE_EMAIL	=> 'email_address',
				Login::AUTH_TYPE_USERNAME => 'username',
			), 'toggles' => array( Login::AUTH_TYPE_USERNAME + Login::AUTH_TYPE_EMAIL => array( 'form_' . $id . '_login_auth_types_warning' ), Login::AUTH_TYPE_USERNAME => array( 'form_' . $id . '_login_auth_types_warning' ) ) ) ),
			'pw_required'		=> new YesNo( 'ldap_pw_required', $this->settings['pw_required'] ?? TRUE ),
		);
		if ( Settings::i()->allow_forgot_password == 'normal' or Settings::i()->allow_forgot_password == 'handler' )
		{
			$return['forgot_password_url'] = new \IPS\Helpers\Form\Url( 'handler_forgot_password_url', $this->settings['forgot_password_url'] ?? NULL );
			Member::loggedIn()->language()->words['handler_forgot_password_url_desc'] = Member::loggedIn()->language()->addToStack( Settings::i()->allow_forgot_password == 'normal' ? 'handler_forgot_password_url_desc_normal' : 'handler_forgot_password_url_deschandler' );
		}
		
		$return[] = 'account_management_settings';
		$return['sync_name_changes'] = new Radio( 'login_sync_name_changes', $this->settings['sync_name_changes'] ?? 1, FALSE, array( 'options' => array(
			1	=> 'login_sync_changes_yes',
			0	=> 'login_sync_changes_no',
		) ) );
		if ( Settings::i()->allow_email_changes == 'normal' )
		{
			$return['sync_email_changes'] = new Radio( 'login_sync_email_changes', $this->settings['sync_email_changes'] ?? 1, FALSE, array( 'options' => array(
				1	=> 'login_sync_changes_yes',
				0	=> 'login_sync_changes_no',
			) ) );
		}
		if ( Settings::i()->allow_password_changes == 'normal' )
		{
			$return['sync_password_changes'] = new Radio( 'login_sync_password_changes', $this->settings['sync_password_changes'] ?? 1, FALSE, array( 'options' => array(
				1	=> 'login_sync_changes_yes',
				0	=> 'login_sync_password_changes_no',
			) ) );
		}
		
		$return['show_in_ucp'] = new Radio( 'login_handler_show_in_ucp', $this->settings['show_in_ucp'] ?? 'disabled', FALSE, array(
			'options' => array(
				'always'		=> 'login_handler_show_in_ucp_always',
				'loggedin'		=> 'login_handler_show_in_ucp_loggedin',
				'disabled'		=> 'login_handler_show_in_ucp_disabled'
			),
			'toggles' => array(
				'always'		=> array( 'login_update_name_changes_inc_optional', 'login_update_email_changes_inc_optional' ),
				'loggedin'		=> array( 'login_update_name_changes_inc_optional', 'login_update_email_changes_inc_optional' ),
				'disabled'		=> array( 'login_update_name_changes_no_optional', 'login_update_email_changes_no_optional' ),
			)
		) );
		
		$nameChangesDisabled = array();
		if ( $forceNameHandler = static::handlerHasForceSync( 'name', $this ) )
		{
			$nameChangesDisabled[] = 'force';
			Member::loggedIn()->language()->words['login_update_changes_yes_name_desc'] = Member::loggedIn()->language()->addToStack( 'login_update_changes_yes_disabled', FALSE, array( 'sprintf' => $forceNameHandler->_title ) );
		}
		$emailChangesDisabled = array();
		if ( $forceEmailHandler = static::handlerHasForceSync( 'email', $this ) )
		{
			$emailChangesDisabled[] = 'force';
			Member::loggedIn()->language()->words['login_update_changes_yes_email_desc'] = Member::loggedIn()->language()->addToStack( 'login_update_changes_yes_disabled', FALSE, array( 'sprintf' => $forceEmailHandler->_title ) );
		}
		
		$return['update_name_changes_inc_optional'] = new Radio( 'login_update_name_changes_inc_optional', $this->settings['update_name_changes'] ?? 'disabled', FALSE, array( 'options' => array(
			'force'		=> 'login_update_changes_yes_name',
			'optional'	=> 'login_update_changes_optional',
			'disabled'	=> 'login_update_changes_no',
		), 'disabled' => $nameChangesDisabled ), NULL, NULL, NULL, 'login_update_name_changes_inc_optional' );
		$return['update_name_changes_no_optional'] = new Radio( 'login_update_name_changes_no_optional', ( isset( $this->settings['update_name_changes'] ) and $this->settings['update_name_changes'] != 'optional' ) ? $this->settings['update_name_changes'] : 'disabled', FALSE, array( 'options' => array(
			'force'		=> 'login_update_changes_yes_name',
			'disabled'	=> 'login_update_changes_no',
		), 'disabled' => $nameChangesDisabled ), NULL, NULL, NULL, 'login_update_name_changes_no_optional' );
		$return['update_email_changes_inc_optional'] = new Radio( 'login_update_email_changes_inc_optional', $this->settings['update_email_changes'] ?? 'force', FALSE, array( 'options' => array(
			'force'		=> 'login_update_changes_yes_email',
			'optional'	=> 'login_update_changes_optional',
			'disabled'	=> 'login_update_changes_no',
		), 'disabled' => $emailChangesDisabled ), NULL, NULL, NULL, 'login_update_email_changes_inc_optional' );
		$return['update_email_changes_no_optional'] = new Radio( 'login_update_email_changes_no_optional', ( isset( $this->settings['update_email_changes'] ) and $this->settings['update_email_changes'] != 'optional' ) ? $this->settings['update_email_changes'] : 'force', FALSE, array( 'options' => array(
			'force'		=> 'login_update_changes_yes_email',
			'disabled'	=> 'login_update_changes_no',
		), 'disabled' => $emailChangesDisabled ), NULL, NULL, NULL, 'login_update_email_changes_no_optional' );
		Member::loggedIn()->language()->words['login_update_name_changes_inc_optional'] = Member::loggedIn()->language()->addToStack('login_update_name_changes');
		Member::loggedIn()->language()->words['login_update_name_changes_no_optional'] = Member::loggedIn()->language()->addToStack('login_update_name_changes');
		Member::loggedIn()->language()->words['login_update_email_changes_inc_optional'] = Member::loggedIn()->language()->addToStack('login_update_email_changes');
		Member::loggedIn()->language()->words['login_update_email_changes_no_optional'] = Member::loggedIn()->language()->addToStack('login_update_email_changes');
		
		return $return;
	}
	
	/**
	 * Save Handler Settings
	 *
	 * @param	array	$values	Values from form
	 * @return	array
	 */
	public function acpFormSave( array &$values ): array
	{
		$_values = $values;
		
		$settings = parent::acpFormSave( $values );
				
		if ( $_values['login_handler_show_in_ucp'] == 'never' )
		{
			$settings['update_name_changes'] = $_values['login_update_name_changes_no_optional'];
			$settings['update_email_changes'] = $_values['login_update_email_changes_no_optional'];
		}
		else
		{
			$settings['update_name_changes'] = $_values['login_update_name_changes_inc_optional'];
			$settings['update_email_changes'] = $_values['login_update_email_changes_inc_optional'];
		}
		
		unset( $settings['update_name_changes_inc_optional'] );
		unset( $settings['update_name_changes_no_optional'] );
		unset( $settings['update_email_changes_inc_optional'] );
		unset( $settings['update_email_changes_no_optional'] );		
				
		return $settings;
	}
	
	/**
	 * Test Settings
	 *
	 * @return	bool
	 * @throws	LogicException
	 */
	public function testSettings(): bool
	{
		if ( !extension_loaded('ldap') )
		{
			throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack( 'login_ldap_err' ) );
		}
		
		try
		{
			$this->_ldap();
		}
		catch ( LDAP\Exception $e )
		{
			throw new InvalidArgumentException( $e->getMessage() ?: Member::loggedIn()->language()->addToStack('login_ldap_err_connect' ) );
		}
		
		return TRUE;
	}
	
	/* !Authentication */

	/**
	 * Authenticate
	 *
	 * @param	Login	$login				The login object
	 * @param string $usernameOrEmail	The username or email address provided by the user
	 * @param object $password			The plaintext password provided by the user, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	Member
	 * @throws	Exception
	 */
	public function authenticateUsernamePassword( Login $login, string $usernameOrEmail, object $password ): Member
	{
		try
		{
			$result = NULL;
			
			if( $usernameOrEmail )
			{
				/* Try email address */
				$result = $this->_getUserWithFilter( $this->settings['email_field'] . '=' . ldap_escape( $usernameOrEmail, NULL, LDAP_ESCAPE_FILTER ) );
			}
						
			/* Don't have anything? */
			if ( !$result )
			{
				$member = NULL;

				$member = new Member;
				$member->email = $usernameOrEmail;

				throw new Exception( Member::loggedIn()->language()->addToStack( 'login_err_no_account', FALSE ), Exception::NO_ACCOUNT, NULL, $member );
			}
			
			/* Get a local account if one exists */
			$attrs = @ldap_get_attributes( $this->_ldap(), $result );
			if ( !$attrs )
			{
				throw new LDAP\Exception( ldap_error( $this->_ldap() ), ldap_errno( $this->_ldap() ) );
			}
			$nameField = $this->_nameField();
			$name = ( $nameField and isset( $attrs[ $nameField ] ) ) ? $attrs[ $nameField ][0] : NULL;
			$email = ( $this->settings['email_field'] and isset( $attrs[ $this->settings['email_field'] ] ) ) ? $attrs[ $this->settings['email_field'] ][0] : NULL;
			$member = NULL;
			try
			{
				$link = Db::i()->select( '*', 'core_login_links', array( 'token_login_method=? AND token_identifier=?', $this->id, $attrs[ $this->settings['uid_field'] ][0] ) )->first();
				$member = Member::load( $link['token_member'] );
				
				/* If the user never finished the linking process, or the account has been deleted, discard this access token */
				if ( !$link['token_linked'] or !$member->member_id )
				{
					Db::i()->delete( 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $link['token_member'] ) );
					$member = NULL;
				}
			}
			catch ( UnderflowException $e ) { }
						
			/* Verify password */
			if ( !$this->_passwordIsValid( $result, $password ) )
			{
				throw new Exception( Member::loggedIn()->language()->addToStack( 'login_err_bad_password', FALSE ), Exception::BAD_PASSWORD, NULL, $member );
			}
			
			/* Create account if we don't have one */
			if ( $member )
			{
				return $member;
			}
			else
			{				
				try
				{
					if ( $login->type === Login::LOGIN_UCP )
					{
						$exception = new Exception( 'generic_error', Exception::MERGE_SOCIAL_ACCOUNT );
						$exception->handler = $this;
						$exception->member = $login->reauthenticateAs;
						throw $exception;
					}
					
					$member = $this->createAccount( $name, $email );
					
					Db::i()->insert( 'core_login_links', array(
						'token_login_method'	=> $this->id,
						'token_member'			=> $member->member_id,
						'token_identifier'		=> $attrs[ $this->settings['uid_field'] ][0],
						'token_linked'			=> 1,
					) );
					
					$member->logHistory( 'core', 'social_account', array(
						'service'		=> static::getTitle(),
						'handler'		=> $this->id,
						'account_id'	=> $this->userId( $member ),
						'account_name'	=> $this->userProfileName( $member ),
						'linked'		=> TRUE,
						'registered'	=> TRUE
					) );
					
					if ( $syncOptions = $this->syncOptions( $member, TRUE ) )
					{
						$profileSync = array();
						foreach ( $syncOptions as $option )
						{
							$profileSync[ $option ] = array( 'handler' => $this->id, 'ref' => NULL, 'error' => NULL );
						}
						$member->profilesync = $profileSync;
						$member->save();
					}
					
					return $member;
				}
				catch ( Exception $exception )
				{
					if ( $exception->getCode() === Exception::MERGE_SOCIAL_ACCOUNT )
					{
						try
						{
							$identifier = Db::i()->select( 'token_identifier', 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $exception->member->member_id ) )->first();

							if( $identifier != $attrs[ $this->settings['uid_field'] ][0] )
							{
								$exception->setCode( Exception::LOCAL_ACCOUNT_ALREADY_MERGED );
								throw $exception;
							}
						}
						catch( UnderflowException $e )
						{
							Db::i()->insert( 'core_login_links', array(
								'token_login_method'	=> $this->id,
								'token_member'			=> $exception->member->member_id,
								'token_identifier'		=> $attrs[ $this->settings['uid_field'] ][0],
								'token_linked'			=> 0,
							) );
						}
					}
					
					throw $exception;
				}
			}
		}
		catch ( LDAP\Exception $e )
		{
			Log::log( $e, 'ldap' );
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}
	}
		
	/**
	 * Authenticate
	 *
	 * @param	Member	$member				The member
	 * @param object $password			The plaintext password provided by the user, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	bool
	 */
	public function authenticatePasswordForMember( Member $member, object $password ): bool
	{
		try
		{
			$linkedId = Db::i()->select( 'token_identifier', 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $member->member_id ) )->first();
			if ( $result = $this->_getUserWithFilter( $this->settings['uid_field'] . '=' . ldap_escape( $linkedId, NULL, LDAP_ESCAPE_FILTER ) ) )
			{
				return $this->_passwordIsValid( $result, $password );
			}
		}
		catch ( UnderflowException $e ) { }
		
		return FALSE;
	}
	
	/* !Utility Methods */
	
	protected ?Connection $_ldap = null;
	
	/**
	 * Get LDAP Connection
	 *
	 * @return	Connection
	 * @throws    LdapException
	 */
	protected function _ldap() : Connection
	{
		if ( !$this->_ldap )
		{
			$this->_ldap = ldap_connect( $this->settings['server_host'], ( isset( $this->settings['server_port'] ) and $this->settings['server_port'] ) ? intval( $this->settings['server_port'] ) : 389 );
			if ( !$this->_ldap )
			{
				throw new LDAP\Exception;
			}
			
			@ldap_set_option( $this->_ldap, LDAP_OPT_PROTOCOL_VERSION, $this->settings['server_protocol'] );
			@ldap_set_option( $this->_ldap, LDAP_OPT_REFERRALS, (bool) $this->settings['opt_referrals'] );
			
			if ( !@ldap_bind( $this->_ldap, ( isset( $this->settings['server_user'] ) and $this->settings['server_user'] ) ? $this->settings['server_user'] : NULL, ( isset( $this->settings['server_pass'] ) and $this->settings['server_pass'] ) ? $this->settings['server_pass'] : NULL ) )
			{
				throw new LDAP\Exception( ldap_error( $this->_ldap ), ldap_errno( $this->_ldap ) );
			}
		}
		
		return $this->_ldap;
	}
	
	/**
	 * Get name field
	 *
	 * @return	string|null
	 */
	public function _nameField(): ?string
	{
		return $this->settings['name_field'] ?? ( $this->settings['uid_field'] ?? NULL );
	}
	
	/**
	 * Get a user
	 *
	 * @param string $filter		Filter
	 * @return	ResultEntry|null
	 */
	protected function _getUserWithFilter( string $filter ) : ?ResultEntry
	{		
		/* Add any additional filter */
		if ( $this->settings['filter'] )
		{
			$filter = ( mb_substr( $this->settings['filter'], 0, 1 ) === '(' ) ? "(&({$filter}){$this->settings['filter']})" : "(&({$filter})({$this->settings['filter']}))";
		}
		
		/* Search */
		$search = @ldap_search( $this->_ldap(), $this->settings['base_dn'], $filter );
		if ( !$search )
		{
			throw new LDAP\Exception( ldap_error( $this->_ldap() ), ldap_errno( $this->_ldap() ) );
		}
		
		/* Get result */
		$result = @ldap_first_entry( $this->_ldap(), $search );
		if ( !$result )
		{
			if ( $errno = ldap_errno( $this->_ldap ) )
			{
				throw new LDAP\Exception( ldap_error( $this->_ldap() ), $errno );
			}
			else
			{
				return NULL;
			}
		}
		
		return $result;
	}
	
	/**
	 * Password is valid
	 *
	 * @param	ResultEntry		$result				The resource from LDAP
	 * @param object|string $providedPassword	The plaintext password provided by the user, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	bool
	 */
	protected function _passwordIsValid( ResultEntry $result, object|string $providedPassword ): bool
	{
		return @ldap_bind( $this->_ldap(), ldap_get_dn( $this->_ldap(), $result ), ( $this->settings['pw_required'] ? ( (string) $providedPassword ) : '' ) );
	}
	
	/* !Other Login Handler Methods */

	/**
	 * Can this handler process a password change for a member?
	 *
	 * @param Member $member
	 * @return    bool
	 */
	public function canChangePassword( Member $member ): bool
	{
		if ( !isset( $this->settings['sync_password_changes'] ) or $this->settings['sync_password_changes'] )
		{
			return $this->canProcess( $member );
		}
		return FALSE;
	}
	
	/**
	 * Can this handler sync passwords?
	 *
	 * @return	bool
	 */
	public function canSyncPassword(): bool
	{
		return ( isset( $this->settings['sync_password_changes'] ) AND $this->settings['sync_password_changes'] );
	}
	
	/**
	 * Email is in use?
	 * Used when registering or changing an email address to check the new one is available
	 *
	 * @param	string				$email		Email Address
	 * @param	Member|NULL	$exclude	Member to exclude
	 * @return	bool|null Boolean indicates if email is in use (TRUE means is in use and thus not registerable) or NULL if this handler does not support such an API
	 */
	public function emailIsInUse( string $email, Member $exclude=NULL ): ?bool
	{
		if ( $this->settings['email_field'] )
		{
			if ( $result = $this->_getUserWithFilter( $this->settings['email_field'] . '=' . ldap_escape( $email, NULL, LDAP_ESCAPE_FILTER )) )
			{
				if ( $exclude )
				{
					try
					{
						$linkedId = Db::i()->select( 'token_identifier', 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $exclude->member_id ) )->first();
						
						if ( $attrs = @ldap_get_attributes( $this->_ldap(), $result ) and $attrs[ $this->settings['uid_field'] ][0] == $linkedId )
						{
							return FALSE;
						}
					}
					catch ( UnderflowException $e ) { }
				}
				
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Username is in use?
	 * Used when registering or changing an username to check the new one is available
	 *
	 * @param	string				$username	Username
	 * @param	Member|NULL	$exclude	Member to exclude
	 * @return	bool|NULL			Boolean indicates if username is in use (TRUE means is in use and thus not registerable) or NULL if this handler does not support such an API
	 */
	public function usernameIsInUse( string $username, Member $exclude=NULL ): ?bool
	{
		if ( $this->_nameField() )
		{
			if ( $result = $this->_getUserWithFilter( $this->_nameField() . '=' . ldap_escape( $username, NULL, LDAP_ESCAPE_FILTER ) ) )
			{
				if ( $exclude )
				{
					try
					{
						$linkedId = Db::i()->select( 'token_identifier', 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $exclude->member_id ) )->first();
						
						if ( $attrs = @ldap_get_attributes( $this->_ldap(), $result ) and $attrs[ $this->settings['uid_field'] ][0] == $linkedId )
						{
							return FALSE;
						}
					}
					catch ( UnderflowException $e ) { }
				}
				
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Change Email Address
	 *
	 * @param	Member	$member		The member
	 * @param	string		$oldEmail	Old Email Address
	 * @param	string		$newEmail	New Email Address
	 * @return	void
	 */
	public function changeEmail( Member $member, string $oldEmail, string $newEmail ) : void
	{
		if ( !isset( $this->settings['sync_email_changes'] ) or $this->settings['sync_email_changes'] )
		{
			try
			{
				$linkedId = Db::i()->select( 'token_identifier', 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $member->member_id ) )->first();
				if ( $result = $this->_getUserWithFilter( $this->settings['uid_field'] . '=' . ldap_escape( $linkedId, NULL, LDAP_ESCAPE_FILTER ) ) )
				{
					if ( !@ldap_modify( $this->_ldap(), ldap_get_dn( $this->_ldap(), $result ), array( $this->settings['email_field'] => $newEmail ) ) )
					{
						$e = new LDAP\Exception( ldap_error( $this->_ldap() ), ldap_errno( $this->_ldap() ) );
						Log::log( $e, 'ldap' );
					}
				}
			}
			catch ( UnderflowException $e ) { }
		}
	}
	
	/**
	 * Change Password
	 *
	 * @param	Member	$member			The member
	 * @param string $newPassword		New Password, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	void
	 */
	public function changePassword( Member $member, string $newPassword ) : void
	{
		if ( !isset( $this->settings['sync_password_changes'] ) or $this->settings['sync_password_changes'] )
		{
			try
			{
				$linkedId = Db::i()->select( 'token_identifier', 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $member->member_id ) )->first();
				if ( $result = $this->_getUserWithFilter( $this->settings['uid_field'] . '=' . ldap_escape( $linkedId, NULL, LDAP_ESCAPE_FILTER ) ) )
				{
					if ( !@ldap_modify( $this->_ldap(), ldap_get_dn( $this->_ldap(), $result ), array( 'userPassword' => "{SHA}" . base64_encode( pack( "H*", sha1( $newPassword ) ) ) ) ) )
					{
						$e = new LDAP\Exception( ldap_error( $this->_ldap() ), ldap_errno( $this->_ldap() ) );
						Log::log( $e, 'ldap' );
					}
				}
			}
			catch ( UnderflowException $e ) { }
		}
	}
	
	/**
	 * Change Username
	 *
	 * @param	Member	$member			The member
	 * @param	string		$oldUsername	Old Username
	 * @param	string		$newUsername	New Username
	 * @return	void
	 */
	public function changeUsername( Member $member, string $oldUsername, string $newUsername ) : void
	{
		if ( !isset( $this->settings['sync_name_changes'] ) or $this->settings['sync_name_changes'] )
		{
			try
			{
				$linkedId = Db::i()->select( 'token_identifier', 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $member->member_id ) )->first();
				if ( $result = $this->_getUserWithFilter( $this->settings['uid_field'] . '=' . ldap_escape( $linkedId, NULL, LDAP_ESCAPE_FILTER ) ) )
				{
					if ( !@ldap_modify( $this->_ldap(), ldap_get_dn( $this->_ldap(), $result ), array( $this->_nameField() => $newUsername ) ) )
					{
						$e = new LDAP\Exception( ldap_error( $this->_ldap() ), ldap_errno( $this->_ldap() ) );
						Log::log( $e, 'ldap' );
					}
				}
			}
			catch ( UnderflowException $e ) { }
		}
	}
	
	/**
	 * Forgot Password URL
	 *
	 * @return	Url|NULL
	 */
	public function forgotPasswordUrl(): ?Url
	{
		return ( isset( $this->settings['forgot_password_url'] ) and $this->settings['forgot_password_url'] ) ? Url::external( $this->settings['forgot_password_url'] ) : NULL;
	}
	
	
	/**
	 * Get user's profile name
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	string|NULL
	 * @throws	Exception	The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userProfileName( Member $member ): ?string
	{
		if ( $nameField = $this->_nameField() )
		{
			if ( !( $link = $this->_link( $member ) ) )
			{
				throw new Exception( "", Exception::INTERNAL_ERROR );
			}
			
			if ( $result = $this->_getUserWithFilter( $this->settings['uid_field'] . '=' . ldap_escape( $link['token_identifier'], NULL, LDAP_ESCAPE_FILTER ) ) )
			{
				if ( $attrs = @ldap_get_attributes( $this->_ldap(), $result ) and isset( $attrs[ $nameField ] ) )
				{
					return $attrs[ $nameField ][0];
				}
				else
				{
					throw new RuntimeException;
				}
			}
			else
			{
				throw new Exception( "", Exception::INTERNAL_ERROR );
			}
		}
		
		return NULL;
	}
	
	/**
	 * Get user's email address
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	string|NULL
	 * @throws	Exception	The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userEmail( Member $member ): ?string
	{
		if ( $this->settings['email_field'] )
		{
			if ( !( $link = $this->_link( $member ) ) )
			{
				throw new Exception( "", Exception::INTERNAL_ERROR );
			}
			
			if ( $result = $this->_getUserWithFilter( $this->settings['uid_field'] . '=' . ldap_escape( $link['token_identifier'], NULL, LDAP_ESCAPE_FILTER ) ) )
			{
				if ( $attrs = @ldap_get_attributes( $this->_ldap(), $result ) and isset( $attrs[ $this->settings['email_field'] ] ) )
				{
					return $attrs[ $this->settings['email_field'] ][0];
				}
				else
				{
					throw new RuntimeException;
				}
			}
			else
			{
				throw new Exception( "", Exception::INTERNAL_ERROR );
			}
		}
		
		return NULL;
	}
	
	/**
	 * Syncing Options
	 *
	 * @param	Member	$member			The member we're asking for (can be used to not show certain options iof the user didn't grant those scopes)
	 * @param	bool		$defaultOnly	If TRUE, only returns which options should be enabled by default for a new account
	 * @return	array
	 */
	public function syncOptions( Member $member, bool $defaultOnly=FALSE ): array
	{
		$return = array();
		
		if ( isset( $this->settings['email_field'] ) and $this->settings['email_field'] and isset( $this->settings['update_email_changes'] ) and $this->settings['update_email_changes'] === 'optional' )
		{
			$return[] = 'email';
		}
		
		if ( $this->_nameField() and isset( $this->settings['update_name_changes'] ) and $this->settings['update_name_changes'] === 'optional' )
		{
			$return[] = 'name';
		}
				
		return $return;
	}

	/**
	 * Has any sync options
	 *
	 * @return	bool
	 */
	public function hasSyncOptions(): bool
	{
		return TRUE;
	}
}