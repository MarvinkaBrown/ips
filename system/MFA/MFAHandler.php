<?php
/**
 * @brief		Abstract Multi Factor Authentication Handler and Factory
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Aug 2016
 */

namespace IPS\MFA;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\MFA\Verify\Handler;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function in_array;
use const IPS\DEV_FORCE_MFA;
use const IPS\DISABLE_MFA;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Multi Factor Authentication Handler and Factory
 */
abstract class MFAHandler
{
	/* !Access Methods */
	
	/**
	 * Get areas
	 *
	 * @return	array
	 */
	public static function areas(): array
	{
		$return = array();
		foreach ( Application::allExtensions( 'core', 'MFAArea', FALSE, 'core', NULL, FALSE ) as $k => $v )
		{
			$return[ $k ] = "MFA_{$k}";
		}
		return $return;
	}
	
	/**
	 * Get handlers
	 *
	 * @return	array
	 */
	public static function handlers(): array
	{
		return array(
			'authy'		=> new Authy\Handler(),
			'google'	=> new GoogleAuthenticator\Handler(),
			'questions'	=> new SecurityQuestions\Handler(),
			'verify'	=> new Handler()
		);
	}
	
	/**
	 * Removes any previous authentication settings for this user
	 *
	 * @return void
	 */
	public static function resetAuthentication() : void
	{
		if ( isset( $_SESSION['MFAAuthenticated'] ) )
		{
			unset( $_SESSION['MFAAuthenticated'] );
		}
	}
	
	/**
	 * Display output when trying to access an area
	 *
	 * @param string $app		The application which owns the MFAArea extension
	 * @param string $area		The MFAArea key
	 * @param	Url	$url		URL for page
	 * @param	Member|null		$member		The member, or NULL for currently logged in member
	 * @return	string|null
	 */
	public static function accessToArea( string $app, string $area, Url $url, ?Member $member = NULL ): ?string
	{
		$member = $member ?: Member::loggedIn();
		
		/* Constant to disable MFA for emergency recovery */
		if ( DISABLE_MFA )
		{
			return NULL;
		}
		
		/* If MFA is not enabled for this area, do nothing */
		if ( !Settings::i()->security_questions_areas or !in_array( "{$app}_{$area}", explode( ',', Settings::i()->security_questions_areas ) ) )
		{
			return NULL;
		}
				
		/* Are we already authenticated? */
		if ( !DEV_FORCE_MFA and ( isset( $_SESSION['MFAAuthenticated'] ) and ( !Settings::i()->security_questions_timer or ( ( $_SESSION['MFAAuthenticated'] + ( Settings::i()->security_questions_timer * 60 ) ) > time() ) ) ) )
		{
			return NULL;
		}
		
		/* "Opt Out" */
		if ( Settings::i()->mfa_required_groups != '*' and !$member->inGroup( explode( ',', Settings::i()->mfa_required_groups ) ) )
		{
			if ( $member->members_bitoptions['security_questions_opt_out'] )
			{
				return NULL;
			}
			if ( isset( Request::i()->_mfa ) and Request::i()->_mfa == 'optout' )
			{
				Session::i()->csrfCheck();
				
				$member->members_bitoptions['security_questions_opt_out'] = TRUE;
				$member->save();

				/* Log MFA Optout */
				$member->logHistory( 'core', 'mfa', array( 'handler' => 'questions', 'enable' => FALSE, 'optout' => TRUE ) );

				return NULL;
			}
		}
		
		/* Gather all the one we *can* use */
		$acceptableHandlers = array();
		foreach ( static::handlers() as $key => $handler )
		{
			/* If it's enabled and we can use it... */
			if ( $handler->isEnabled() and $handler->memberCanUseHandler( $member ) )
			{
				$acceptableHandlers[ $key ] = $handler;
			}
		}
		if ( !$acceptableHandlers )
		{
			return NULL;
		}
		
		/* Locked out? */
		if ( $lockedOutScreen = static::_lockedOutScreen( $member ) )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( '2fa.css', 'core', 'global' ) );
			return $lockedOutScreen;
		}
				
		/* "Try another way to sign in" */
		if ( isset( Request::i()->_mfa ) )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( '2fa.css', 'core', 'global' ) );
			if ( Request::i()->_mfa == 'alt' )
			{			
				/* What handlers have we configured? */
				$configuredHandlers = array();
				foreach ( $acceptableHandlers as $key => $handler )
				{
					if ( $handler->memberHasConfiguredHandler( $member ) )
					{
						$configuredHandlers[ $key ] = $handler;
					}
				}
				
				if ( isset( Request::i()->_mfaMethod ) and array_key_exists( Request::i()->_mfaMethod, $configuredHandlers ) )
				{
					return static::_showHandlerAuthScreen( $configuredHandlers[ Request::i()->_mfaMethod ], $url->setQueryString( array( '_mfa' => 'alt', '_mfaMethod' => Request::i()->_mfaMethod ) ), $member );
				}
				
				/* Display */
				$knownDevicesAvailable = FALSE;
				if ( $app === 'core' and $area === 'AuthenticateFront' and !in_array( "app_AuthenticateFrontKnown", explode( ',', Settings::i()->security_questions_areas ) ) )
				{
					if ( Db::i()->select( 'COUNT(*)', 'core_members_known_devices', array( 'member_id=?', $member->member_id ) )->first() )
					{
						$knownDevicesAvailable = TRUE;
					}
				} 
				
				return Theme::i()->getTemplate( 'login', 'core', 'global' )->mfaRecovery( $configuredHandlers, $url, $knownDevicesAvailable );
			}
			elseif ( Request::i()->_mfa == 'knownDevice' )
			{
				return Theme::i()->getTemplate( 'system', 'core' )->mfaKnownDeviceInfo( $url );
			}
		}
						
		/* Normal authentication form */
		foreach ( $acceptableHandlers as $handler )
		{
			if ( $handler->memberHasConfiguredHandler( $member ) )
			{
				Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( '2fa.css', 'core', 'global' ) );
				return static::_showHandlerAuthScreen( $handler, $url, $member );
			}
		}
		
		/* Setup form */
		$showSetupForm = ( Settings::i()->mfa_required_groups == '*' or $member->inGroup( explode( ',', Settings::i()->mfa_required_groups ) ) ) ? 'mfa_required_prompt' : 'mfa_optional_prompt';
		if ( Settings::i()->$showSetupForm === 'access' or ( $app === 'core' and $area === 'AuthenticateAdmin' and Settings::i()->$showSetupForm === 'immediate' ) )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( '2fa.css', 'core', 'global' ) );
			
			/* Did we just submit it? */
			if ( isset( Request::i()->mfa_setup ) and isset( Request::i()->csrfKey ) )
			{
				Session::i()->csrfCheck();
				foreach ( $acceptableHandlers as $key => $handler )
				{
					if ( ( count( $acceptableHandlers ) == 1 ) or $key == Request::i()->mfa_method )
					{
						if ( $handler->configurationScreenSubmit( $member ) )
						{							
							$_SESSION['MFAAuthenticated'] = time();
							return NULL;
						}
						elseif ( $lockedOutScreen = static::_lockedOutScreen( $member ) )
						{
							return $lockedOutScreen;
						}
					}
				}
			}
			
			/* No, show it */			
			return Theme::i()->getTemplate( 'login', 'core', 'global' )->mfaSetup( $acceptableHandlers, $member, $url );
		}

		return NULL;
	}
	
	/**
	 * Show a handler's authentication screen
	 *
	 * @param MFAHandler $handler	The handler to use
	 * @param	Url		$url		URL for page
	 * @param	Member			$member		The member
	 * @return	string|null
	 */
	protected static function _showHandlerAuthScreen( MFAHandler $handler, Url $url, Member $member ): ?string
	{
		/* Did we just submit it? */
		if ( isset( Request::i()->mfa_auth ) )
		{
			Session::i()->csrfCheck();
			if ( $handler->authenticationScreenSubmit( $member ) )
			{
				$member->failed_mfa_attempts = 0;
				$member->save();
				$_SESSION['MFAAuthenticated'] = time();
				return NULL;
			}
			else
			{
				$member->failed_mfa_attempts++;
				$member->save();
				
				Request::i()->mfa_auth = NULL;
			}
		}
		
		/* No, show it */
		return Theme::i()->getTemplate( 'login', 'core', 'global' )->mfaAuthenticate( $handler->authenticationScreen( $member, $url ), $url );
	}
	
	/**
	 * Show the locked out screen, if necessary
	 *
	 * @param	Member			$member		The member
	 * @return	string|null
	 */
	protected static function _lockedOutScreen( Member $member ): ?string
	{
		if ( $member->failed_mfa_attempts >= Settings::i()->security_questions_tries )
		{
			if ( Settings::i()->mfa_lockout_behaviour == 'lock' )
			{
				$mfaDetails = $member->mfa_details;
				if ( !isset( $mfaDetails['_lockouttime'] ) )
				{
					$mfaDetails['_lockouttime'] = time();
					$member->mfa_details = $mfaDetails;
					$member->save();
					
					$member->logHistory( 'core', 'login', array( 'type' => 'mfalock', 'count' => $member->failed_mfa_attempts, 'unlockTime' => DateTime::create()->add( new DateInterval( 'PT' . Settings::i()->mfa_lockout_time . 'M' ) )->getTimestamp() ) );
				}
								
				$lockEndTime = DateTime::ts( $mfaDetails['_lockouttime'] )->add( new DateInterval( 'PT' . Settings::i()->mfa_lockout_time . 'M' ) );
				if ( $lockEndTime->getTimestamp() < time() )
				{
					unset( $mfaDetails['_lockouttime'] );
					$member->mfa_details = $mfaDetails;
					$member->failed_mfa_attempts = 0;
					$member->save();
				} 
				else
				{					
					return Theme::i()->getTemplate( 'login', 'core', 'global' )->mfaLockout( ( Settings::i()->mfa_lockout_time > 1440 ) ? $lockEndTime : $lockEndTime->localeTime( FALSE ) );
				}
			}
			else
			{
				if ( $member->failed_mfa_attempts == Settings::i()->security_questions_tries )
				{
					$member->logHistory( 'core', 'login', array( 'type' => 'mfalock', 'count' => $member->failed_mfa_attempts ) );
				}
				return Theme::i()->getTemplate( 'login', 'core', 'global' )->mfaLockout();
			}
		}

		return NULL;
	}
	
	/* !Setup */
	
	/**
	 * Handler is enabled
	 *
	 * @return	bool
	 */
	abstract public function isEnabled(): bool;
	
	/**
	 * Member *can* use this handler (even if they have not yet configured it)
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	abstract public function memberCanUseHandler( Member $member ): bool;
	
	/**
	 * Member has configured this handler
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	abstract public function memberHasConfiguredHandler( Member $member ): bool;
		
	/**
	 * Show a setup screen
	 *
	 * @param	Member		$member						The member
	 * @param bool $showingMultipleHandlers	Set to TRUE if multiple options are being displayed
	 * @param	Url	$url						URL for page
	 * @return	string
	 */
	abstract public function configurationScreen( Member $member, bool $showingMultipleHandlers, Url $url ): string;
	
	/**
	 * Submit configuration screen. Return TRUE if was accepted
	 *
	 * @param	Member		$member	The member
	 * @return	bool
	 */
	abstract public function configurationScreenSubmit( Member $member ): bool;
	
	/* !Authentication */
	
	/**
	 * Get the form for a member to authenticate
	 *
	 * @param	Member		$member		The member
	 * @param	Url	$url		URL for page
	 * @return	string
	 */
	abstract public function authenticationScreen( Member $member, Url $url ): string;
	
	/**
	 * Submit authentication screen. Return TRUE if was accepted
	 *
	 * @param	Member		$member	The member
	 * @return	bool
	 */
	abstract public function authenticationScreenSubmit( Member $member ): bool;
	
	/* !ACP */
	
	/**
	 * Toggle
	 *
	 * @param bool $enabled	On/Off
	 * @return	void
	 */
	abstract public function toggle( bool $enabled ) : void;
	
	/**
	 * ACP Settings
	 *
	 * @return	string
	 */
	abstract public function acpSettings(): string;
	
	/**
	 * Configuration options when editing member account in ACP
	 *
	 * @param	Member			$member		The member
	 * @return	array
	 */
	public function acpConfiguration( Member $member ): array
	{
		if ( $this->memberHasConfiguredHandler( $member ) )
		{
			return array( new YesNo( "mfa_{$this->key}_title", $this->memberHasConfiguredHandler( $member ), FALSE, array(), NULL, NULL, NULL, "mfa_{$this->key}_title" ) );
		}
		return array();
	}
	
	/**
	 * Save configuration when editing member account in ACP
	 *
	 * @param	Member		$member		The member
	 * @param array $values		Values from form
	 * @return	void
	 */
	public function acpConfigurationSave(Member $member, array $values ) : void
	{
		if ( isset( $values["mfa_{$this->key}_title"] ) and !$values["mfa_{$this->key}_title"] )
		{
			if ( isset( $member->mfa_details[ $this->key ] ) and $this->memberHasConfiguredHandler( $member ) )
			{
				$this->disableHandlerForMember( $member );
			}
		}
	}
	
	/* !Misc */
	
	/**
	 * If member has configured this handler, disable it
	 *
	 * @param	Member	$member	The member
	 * @return	void
	 */
	abstract public function disableHandlerForMember( Member $member ) : void;
	
	/**
	 * Get title for UCP
	 *
	 * @return	string
	 */
	public function ucpTitle(): string
	{
		return Member::loggedIn()->language()->addToStack("mfa_{$this->key}_title");
	}
	
	/**
	 * Get description for UCP
	 *
	 * @return	string
	 */
	public function ucpDesc(): string
	{
		return Member::loggedIn()->language()->addToStack("mfa_{$this->key}_desc_user");
	}
	
	/**
	 * Get label for recovery button
	 *
	 * @return	string
	 */
	public function recoveryButton(): string
	{
		return Member::loggedIn()->language()->addToStack("mfa_recovery_{$this->key}");
	}
	
}