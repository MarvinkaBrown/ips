<?php
/**
 * @brief		Abstract Login Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Login;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\core\ShareLinks\Service;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Login;
use IPS\Login\Exception as LoginException;
use IPS\Login\Handler as HandlerClass;
use IPS\Login\Handler\Standard;
use IPS\Member;
use IPS\Member\Device;
use IPS\Node\Model;
use IPS\Request;
use IPS\Settings;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function mb_stripos;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Login Handler
 */
abstract class Handler extends Model
{
	/**
	 * Get all handler classes
	 *
	 * @return	array
	 */
	public static function handlerClasses(): array
	{
		$return = array(
			'IPS\Login\Handler\Standard',
			'IPS\Login\Handler\OAuth2\Apple',
			'IPS\Login\Handler\OAuth2\Facebook',
			'IPS\Login\Handler\OAuth2\Google',
			'IPS\Login\Handler\OAuth2\LinkedIn',
			'IPS\Login\Handler\OAuth2\Microsoft',
			'IPS\Login\Handler\OAuth1\Twitter',
			'IPS\Login\Handler\OAuth2\Invision',
			'IPS\Login\Handler\OAuth2\Wordpress',
			'IPS\Login\Handler\OAuth2\Custom',
			'IPS\Login\Handler\ExternalDatabase',
			'IPS\Login\Handler\LDAP',
		);

		foreach ( Application::allExtensions( 'core', 'LoginHandler', FALSE, 'core' ) as $key => $extension )
		{
			$return[] = $extension::class;
		}

		return $return;
	}
	
	/**
	 * Find a particular handler
	 *
	 * @param string $classname	Classname
	 * @return    mixed
	 */
	public static function findMethod( string $classname ): mixed
	{
		foreach ( Login::methods() as $method )
		{
			if ( $method instanceof $classname )
			{
				return $method;
			}
		}
		return NULL;
	}
	
	/* !Login Handler */
	
	/**
	 * @brief	Can we have multiple instances of this handler?
	 */
	public static bool $allowMultiple = FALSE;
	
	/**
	 * @brief	Share Service
	 */
	public static ?string $shareService = NULL;
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public static function getTitle(): string
	{
		return '';
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
		return array();
	}
	
	/**
	 * Save Handler Settings
	 *
	 * @param array $values	Values from form
	 * @return	array
	 */
	public function acpFormSave( array &$values ): array
	{
		$settings = array();
		foreach ( $this->acpForm() as $key => $field )
		{
			if ( is_object( $field ) )
			{
				$settings[ $key ] = $values[ $field->name ];
				unset( $values[ $field->name ] );
			}
		}
		
		/* If the legacy_redirect flag is set, make sure it stays set otherwise logins will break once the login method has been edited */
		if ( isset( $this->settings['legacy_redirect'] ) AND $this->settings['legacy_redirect'] )
		{
			$settings['legacy_redirect'] = TRUE;
		}
		return $settings;
	}
	
	/**
	 * Get type
	 *
	 * @return	int
	 */
	abstract public function type(): int;
	
	/**
	 * Can this handler process a login for a member? 
	 *
	 * @param	Member	$member	Member
	 * @return	bool
	 */
	public function canProcess( Member $member ): bool
	{
		return (bool) $this->_link( $member );
	}
	
	/**
	 * Can this handler sync passwords?
	 *
	 * @return	bool
	 */
	public function canSyncPassword(): bool
	{
		return FALSE;
	}
	
	/**
	 * @brief	Cached links
	 */
	protected array $_cachedLinks = array();
	
	/**
	 * Get link
	 *
	 * @param	Member	$member	Member
	 * @return	array|null
	 */
	protected function _link( Member $member ): ?array
	{
		if ( !isset( $this->_cachedLinks[ $member->member_id ] ) )
		{
			try
			{				
				$this->_cachedLinks[ $member->member_id ] = Db::i()->select( '*', 'core_login_links', array( 'token_login_method=? AND token_member=? AND token_linked=1', $this->id, $member->member_id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
			}
			catch ( UnderflowException $e )
			{
				$this->_cachedLinks[ $member->member_id ] = NULL;
			}
		}
		return $this->_cachedLinks[ $member->member_id ];
	}
	
	/**
	 * Can this handler process a password change for a member? 
	 *
	 * @param	Member	$member	Member
	 * @return	bool
	 */
	public function canChangePassword( Member $member ): bool
	{
		return FALSE;
	}
	
	/**
	 * Email is in use?
	 * Used when registering or changing an email address to check the new one is available
	 *
	 * @param string $email		Email Address
	 * @param	Member|NULL	$exclude	Member to exclude
	 * @return	bool|null Boolean indicates if email is in use (TRUE means is in use and thus not registerable) or NULL if this handler does not support such an API
	 */
	public function emailIsInUse( string $email, Member $exclude=NULL ): ?bool
	{
		return NULL;
	}
	
	/**
	 * Username is in use?
	 * Used when registering or changing an username to check the new one is available
	 *
	 * @param string $username	Username
	 * @param	Member|NULL	$exclude	Member to exclude
	 * @return	bool|NULL			Boolean indicates if username is in use (TRUE means is in use and thus not registerable) or NULL if this handler does not support such an API
	 */
	public function usernameIsInUse( string $username, Member $exclude=NULL ): ?bool
	{
		return NULL;
	}
	
	/**
	 * Change Username
	 *
	 * @param	Member	$member			The member
	 * @param string $oldUsername	Old Username
	 * @param string $newUsername	New Username
	 * @return	void
	 * @throws	Exception
	 */
	public function changeUsername( Member $member, string $oldUsername, string $newUsername ) : void
	{
		// By default do nothing. Handlers can extend.
	}
	
	/**
	 * Change Email Address
	 *
	 * @param	Member	$member			The member
	 * @param string $oldEmail		Old Email
	 * @param string $newEmail		New Email
	 * @return	void
	 * @throws	Exception
	 */
	public function changeEmail( Member $member, string $oldEmail, string $newEmail ) : void
	{
		// By default do nothing. Handlers can extend.
	}
	
	/**
	 * Forgot Password URL
	 *
	 * @return	Url|NULL
	 */
	public function forgotPasswordUrl(): ?Url
	{
		return NULL;
	}
	
	/**
	 * Force Password Reset URL
	 *
	 * @param	Member			$member	The member
	 * @param	Url|NULL	$ref	Referrer
	 * @return	Url|NULL
	 */
	public function forcePasswordResetUrl( Member $member, ?Url $ref = NULL ): ?Url
	{
		return NULL;
	}
		
	/**
	 * Create an account from login - checks registration is enabled, the name/email doesn't already exists and calls the spam service
	 *
	 * @param string|null $name				The desired username. If not provided, not allowed, or another existing user has this name, it will be left blank and the user prompted to provide it.
	 * @param string|null $email				The user's email address. If it matches an existing account, an \IPS\Login\Exception object will be thrown so the user can be prompted to link those accounts. If not provided, it will be left blank and the user prompted to provide it.
	 * @param bool $allowCreateAccount	If an account can be created
	 * @return	Member
	 * @throws    LoginException    If email address matches (\IPS\Login\Exception::MERGE_SOCIAL_ACCOUNT), registration is disabled (IPS\Login\Exception::REGISTRATION_DISABLED) or the spam service denies registration (\IPS\Login\Exception::REGISTRATION_DENIED_BY_SPAM_SERVICE)
	 */
	protected function createAccount( string $name=NULL, string $email=NULL, bool $allowCreateAccount=TRUE ): Member
	{
		/* Is there an existing user with the same email address? */
		if ( $email )
		{
			$existingAccount = Member::load( $email, 'email' );
			if ( $existingAccount->member_id )
			{
				$exception = new LoginException( 'link_your_accounts_error', LoginException::MERGE_SOCIAL_ACCOUNT );
				$exception->handler = $this;
				$exception->member = $existingAccount;
				throw $exception;
			}
		}
		
		/* Nope - we need to register one - can we do that? */
		if( !$this->register or !$allowCreateAccount )
		{
			$exception = new LoginException( Login::registrationType() == 'disabled' ? 'reg_disabled' : 'reg_not_allowed_by_login', LoginException::REGISTRATION_DISABLED );
			$exception->handler = $this;
			throw $exception;
		}
		
		/* Create the account */
		$member = new Member;
		$member->member_group_id = Settings::i()->member_group;
		$member->members_bitoptions['view_sigs'] = TRUE;
		$member->members_bitoptions['must_reaccept_terms'] = (bool) Settings::i()->force_reg_terms;
		if ( $name and Login::usernameIsAllowed( $name ) )
		{
			$existingUsername = Member::load( $name, 'name' );
			if ( !$existingUsername->member_id )
			{
				$member->name = $name;
			}
		}
		$spamCode = NULL;
		$spamAction = NULL;
		if ( $email )
		{
			/* Check it's an allowed domain */
			$allowed = TRUE;
			if ( Settings::i()->allowed_reg_email and $allowedEmailDomains = explode( ',', Settings::i()->allowed_reg_email )  )
			{
				$allowed = FALSE;
				foreach ( $allowedEmailDomains AS $domain )
				{
					if( mb_stripos( $email,  "@" . $domain ) !== FALSE )
					{
						$allowed = TRUE;
					}
				}
			}
			if ( $allowed )
			{
				$member->email = $email;
			}	
			
			/* Check the spam service is okay with it */
			if( Settings::i()->spam_service_enabled )
			{
				$spamAction = $member->spamService( 'register', NULL, $spamCode );
				if( $spamAction == 4 )
				{
					$exception = new LoginException( 'spam_denied_account', LoginException::REGISTRATION_DENIED_BY_SPAM_SERVICE );
					$exception->handler = $this;
					throw $exception;
				}
			}
		}
		$member->save();
		$member->logHistory( 'core', 'account', array( 'type' => 'register_handler', 'service' => static::getTitle(), 'handler' => $this->id, 'spamCode' => $spamCode, 'spamAction' => $spamAction, 'complete' => ( $member->real_name and $member->email ) ), FALSE );
		
		/* Create a device setting $sendNewDeviceEmail to false so that when we hand back to the login
			handler is doesn't send the new device email */
		Device::loadOrCreate( $member, FALSE )->save();
								
		/* If registration is complete, do post-registration stuff */
		if ( $member->real_name and $member->email and !$member->members_bitoptions['bw_is_spammer'] )
		{
			$postBeforeRegister = NULL;
			if ( isset( Request::i()->cookie['post_before_register'] ) )
			{
				try
				{
					$postBeforeRegister = Db::i()->select( '*', 'core_post_before_registering', array( 'secret=?', Request::i()->cookie['post_before_register'] ) )->first();
				}
				catch ( UnderflowException $e ) { }
			}

			/* If account wasn't flagged as spammer and banned, handle validation stuff */
			if( $spamAction != 3 )
			{
				$member->postRegistration( TRUE, FALSE, $postBeforeRegister );
			}
		}
		
		/* Return our new member */
		return $member;
	}
	
	/**
	 * Link Account
	 *
	 * @param	Member	$member		The member
	 * @param	mixed		$details	Details as they were passed to the exception
	 * @return	void
	 */
	public function completeLink(Member $member, mixed $details ) : void
	{
		Db::i()->update( 'core_login_links', array( 'token_linked' => 1 ), array( 'token_login_method=? AND token_member=?', $this->id, $member->member_id ) );
		unset( $this->_cachedLinks[ $member->member_id ] );
		
		$member->logHistory( 'core', 'social_account', array(
			'service'		=> static::getTitle(),
			'handler'		=> $this->id,
			'account_id'	=> $this->userId( $member ),
			'account_name'	=> $this->userProfileName( $member ),
			'linked'		=> TRUE,
		) );
	}
	
	/**
	 * Unlink Account
	 *
	 * @param	Member|null	$member		The member or NULL for currently logged in member
	 * @return	void
	 */
	public function disassociate( ?Member $member = NULL ) : void
	{
		$member = $member ?: Member::loggedIn();

		try
		{
			$userId		= $this->userId( $member );
			$userName	= $this->userProfileName( $member );
		}
		catch(Exception $e )
		{
			$userId		= NULL;
			$userName	= NULL;
		}

		$member->logHistory( 'core', 'social_account', array(
			'service'		=> static::getTitle(),
			'handler'		=> $this->id,
			'account_id'	=> $userId,
			'account_name'	=> $userName,
			'linked'		=> FALSE,
		) );
		
		Db::i()->delete( 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $member->member_id ) );
	}
		
	/**
	 * Get logo to display in information about logins with this method
	 * Returns NULL for methods where it is not necessary to indicate the method, e..g Standard
	 *
	 * @return	Url|string|null
	 */
	public function logoForDeviceInformation(): Url|string|null
	{
		return NULL;
	}

	/**
	 * Get logo to display in user cp sidebar
	 *
	 * @return Url|string|null
	 */
	public function logoForUcp(): Url|string|null
	{
		return $this->logoForDeviceInformation() ?: 'database';
	}
	
	/**
	 * Show in Account Settings?
	 *
	 * @param	Member|NULL	$member	The member, or NULL for if it should show generally
	 * @return	bool
	 */
	public function showInUcp( Member $member = NULL ): bool
	{
		if( !$this->enabled )
		{
			return FALSE ;
		}
		if ( isset( $this->settings['show_in_ucp'] ) )
		{
			switch ( $this->settings['show_in_ucp'] )
			{
				case 'always':
					return TRUE;
					
				case 'loggedin':
					return ( $member and $this->canProcess( $member ) );
					
				case 'disabled':
					return FALSE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Things which must be synced if a member is using this handler
	 *
	 * @return	array
	 */
	public function forceSync(): array
	{
		$return = array();
		
		if ( isset( $this->settings['update_name_changes'] ) and $this->settings['update_name_changes'] === 'force' )
		{
			$return[] = 'name';
		}
		
		if ( isset( $this->settings['update_email_changes'] ) and $this->settings['update_email_changes'] === 'force' )
		{
			$return[] = 'email';
		}
		
		return $return;
	}

	/**
	 * Check if any handler has a particular value set in forceSync()
	 *
	 * @note    Deliberately checks disabled methods, otherwise you'd be able to re-enable two which have it enabled bypassing the check
	 * @param string $type The type to check for
	 * @param Handler|null $not Exclude a particular handler from the check
	 * @param Member|null $member If specified, only login handlers that member has set up will be checked
	 * @return    Handler|FALSE
	 */
	public static function handlerHasForceSync( string $type, Handler $not = NULL, Member $member = NULL ): Handler|FALSE
	{
		foreach ( Db::i()->select( '*', 'core_login_methods' ) as $row )
		{
			try
			{
				$method = static::constructFromData( $row );
				
				if ( ( !$not or $not->_id != $method->_id ) and ( !$member or $method->canProcess( $member ) ) )
				{
					if ( in_array( $type, $method->forceSync() ) )
					{
						return $method;
					}
				}
			}
			catch ( Exception $e ) { }
		}
		return FALSE;
	}
	
	/**
	 * Syncing Options
	 *
	 * @param	Member	$member			The member we're asking for (can be used to not show certain options if the user didn't grant those scopes)
	 * @param bool $defaultOnly	If TRUE, only returns which options should be enabled by default for a new account
	 * @return	array
	 */
	public function syncOptions( Member $member, bool $defaultOnly=FALSE ): array
	{
		return array();
	}

	/**
	 * Has any sync options
	 *
	 * @return	bool
	 */
	public function hasSyncOptions(): bool
	{
		return FALSE;
	}
	
	/**
	 * Get user's identifier (may not be a number)
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	string|NULL
	 * @throws    Exception    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userId( Member $member ): ?string
	{
		return NULL;
	}
	
	/**
	 * Get user's profile photo
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	Url|NULL
	 * @throws    Exception    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userProfilePhoto( Member $member ): ?Url
	{
		return NULL;
	}
	
	/**
	 * Get user's profile name
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	string|NULL
	 * @throws    Exception    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userProfileName( Member $member ): ?string
	{
		return NULL;
	}
	
	/**
	 * Get user's email address
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	string|NULL
	 * @throws    Exception    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userEmail( Member $member ): ?string
	{
		return NULL;
	}
	
	/**
	 * Get user's cover photo
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	Url|NULL
	 * @throws    Exception    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userCoverPhoto( Member $member ): ?Url
	{
		return NULL;
	}
	
	/**
	 * Get link to user's remote profile
	 * May return NULL if server doesn't support this
	 *
	 * @param string $identifier	The ID Nnumber/string from remote service
	 * @param string $username	The username from remote service
	 * @return	Url|NULL
	 * @throws    Exception    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userLink( string $identifier, string $username ): ?Url
	{
		return NULL;
	}
	
	/* !ActiveRecord & Node */
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_login_methods';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'login_';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'login_handlers';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'order';
	
	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static ?string $databaseColumnEnabledDisabled = 'enabled';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'login_method_';
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    HandlerClass
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): HandlerClass
	{
		$classname = $data['login_classname'];
		if ( !class_exists( $classname ) )
		{
			throw new OutOfRangeException;
		}
		
		/* Initiate an object */
		$obj = new $classname;
		$obj->_new  = FALSE;
		$obj->_data = array();
		
		/* Import data */
		$databasePrefixLength = strlen( static::$databasePrefix );
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
			{
				$k = substr( $k, $databasePrefixLength );
			}

			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();
		
		/* Init */
		if ( method_exists( $obj, 'init' ) )
		{
			$obj->init();
		}
		
		/* If it doesn't exist in the multiton store, set it */
		if( !isset( static::$multitons[ $data['login_id'] ] ) )
		{
			static::$multitons[ $data['login_id'] ] = $obj;
		}
				
		/* Return */
		return $obj;
	}
	
	/**
	 * Get settings
	 *
	 * @return	array
	 */
	protected function get_settings(): array
	{
		return ( isset( $this->_data['settings'] ) and $this->_data['settings'] ) ? json_decode( $this->_data['settings'], TRUE ) : array();
	}
	
	/**
	 * Set settings
	 *
	 * @param array $values	Values
	 * @return	void
	 */
	public function set_settings( array $values ) : void
	{
		$this->_data['settings'] = json_encode( $values );
	}
			
	/**
	 * [Node] Does the currently logged in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy(): bool
	{
		if ( !static::$allowMultiple )
		{
			return FALSE;
		}
		return parent::canCopy();
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		if ( parent::canDelete() )
		{
			return count( static::roots() ) > 1;
		}
		return FALSE;
	}
	
	/* !AdminCP Management */
	
	/**
	 * @brief	Should ACP logins be enabled by default
	 */
	protected static bool $enableAcpLoginByDefault = TRUE;
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->addHeader('login_method_basic_settings');
		$form->add( new Translatable( 'login_method_name', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? 'login_method_' . $this->id : NULL ) ) ) );
		if ( !( $this instanceof Standard ) ) {
			$self = $this;
			$form->add(new YesNo('login_acp', $this->id ? $this->acp : static::$enableAcpLoginByDefault, FALSE, array(), function ($val) use ($self) {
				if (!$val) {
					foreach (Login::methods() as $method) {
						if ($method != $self and $method->canProcess(Member::loggedIn()) and $method->acp) {
							return true;
						}
					}
					throw new DomainException('login_handler_cannot_disable_acp');
				}
			}));
		}

		$form->add( new YesNo( 'login_front', $this->id ? $this->front : static::$enableAcpLoginByDefault, FALSE, array() ) );

		if ( !( $this instanceof Standard ) ) {
			$form->add( new Radio( 'login_register', $this->id ? $this->register : TRUE, FALSE, array(
				'options' 	=> array(
					1	=> 'login_register_enabled',
					0	=> 'login_register_disabled'
				),
				'toggles'	=> array(
					1	=> array( 'login_real_name', 'login_real_email' )
				)
			) ) );
		}

		$form->add( new YesNo( 'login_front', $this->id ? $this->front : static::$enableAcpLoginByDefault, FALSE, array() ) );

		foreach ( $this->acpForm() as $key => $field )
		{
			if ( is_string( $field ) )
			{
				$form->addHeader( $field );
			}
			elseif ( is_array( $field ) )
			{
				$form->addHeader( $field[0] );
				$form->addMessage( $field[1] );
			}
			else
			{
				$form->add( $field );
			}
		}
		
		if ( isset( static::$shareService ) )
		{
			try
			{
				$shareService = Service::load( static::$shareService, 'share_key' );
				$form->addHeader( 'sharelinks' );
				$form->add( new YesNo( 'share_autoshare_' . static::$shareService, $shareService->autoshare ) );
			}
			catch ( OutOfRangeException $e ) { }
		}
	}
	
	/**
	 * [Node] Save Add/Edit Form
	 *
	 * @param array $values	Values from the form
	 * @return    mixed
	 */
	public function saveForm( array $values ): mixed
	{
		if ( isset( static::$shareService ) and isset( $values[ 'share_autoshare_' . static::$shareService ] ) )
		{
			try
			{
				$shareService = Service::load( static::$shareService, 'share_key' );
				$shareService->autoshare = $values[ 'share_autoshare_' . static::$shareService ];
				$shareService->save();
			}
			catch ( OutOfRangeException $e ) { }
			unset( $values[ 'share_autoshare_' . static::$shareService ] );
		}
		
		return parent::saveForm( $values );
	}		
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$settings = $this->acpFormSave( $values );
		$values['login_settings'] = $settings;
		$this->settings = $settings;
		$this->testSettings();

		if( isset( $values['login_method_name'] ) )
		{
			if ( !$this->id )
			{
				$this->save();
			}
			Lang::saveCustom( 'core', "login_method_{$this->id}", $values['login_method_name'] );
			unset( $values['login_method_name'] );
		}

		return parent::formatFormValues( $values );
	}
	
	/**
	 * Test Compatibility
	 *
	 * @return	bool
	 * @throws	LogicException
	 */
	public static function testCompatibility(): bool
	{		
		return TRUE;
	}
	
	
	/**
	 * Test Settings
	 *
	 * @return	bool
	 * @throws	LogicException
	 */
	public function testSettings(): bool
	{
		return static::testCompatibility();
	}
	
	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();
		unset( Store::i()->loginMethods, Store::i()->essentialCookieNames );
		Cache::i()->clearAll();
	}
	
}