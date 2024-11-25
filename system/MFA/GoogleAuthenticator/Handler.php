<?php
/**
 * @brief		Multi Factor Authentication Handler for Google Authenticator
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Sep 2016
 */

namespace IPS\MFA\GoogleAuthenticator;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Login;
use IPS\Member;
use IPS\Member\Group;
use IPS\MFA\MFAHandler;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use UnderflowException;
use function chr;
use function count;
use function defined;
use function function_exists;
use function in_array;
use function ord;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Multi Factor Authentication Handler for Google Authenticator
 */
class Handler extends MFAHandler
{
	/**
	 * @brief	Key
	 */
	protected string $key = 'google';
	
	/* !Setup */
	
	/**
	 * Handler is enabled
	 *
	 * @return	bool
	 */
	public function isEnabled(): bool
	{
		return Settings::i()->googleauth_enabled;
	}

	/**
	 * Member *can* use this handler (even if they have not yet configured it)
	 *
	 * @param Member $member
	 * @return    bool
	 */
	public function memberCanUseHandler( Member $member ): bool
	{
		return Settings::i()->googleauth_groups == '*' or $member->inGroup( explode( ',', Settings::i()->googleauth_groups ) );
	}
	
	/**
	 * Member has configured this handler
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public function memberHasConfiguredHandler( Member $member ): bool
	{
		return isset( $member->mfa_details['google'] );
	}
		
	/**
	 * Show a setup screen
	 *
	 * @param	Member		$member						The member
	 * @param	bool			$showingMultipleHandlers	Set to TRUE if multiple options are being displayed
	 * @param	Url	$url						URL for page
	 * @return	string
	 */
	public function configurationScreen( Member $member, bool $showingMultipleHandlers, Url $url ): string
	{
		/* Generate a secret */
		if ( isset( Request::i()->secret ) )
		{
			$secret = Request::i()->secret;
		}
		else
		{
			if ( function_exists( 'random_bytes' ) )
			{
				$randomString = random_bytes( 16 );
			}
			elseif ( function_exists( 'mcrypt_create_iv' ) )
			{
				$randomString = mcrypt_create_iv( 16, MCRYPT_DEV_URANDOM );
			}
			elseif ( function_exists( 'openssl_random_pseudo_bytes' ) )
			{
				$randomString = openssl_random_pseudo_bytes( 16 );
			}
			else
			{
				$randomString = substr( md5( uniqid( microtime(), true ) ) . md5( uniqid( microtime(), true ) ), 0, 16 );
			}
			$validChars = array( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',  'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '2', '3', '4', '5', '6', '7', '=' );
			$secret = '';
			for ( $i = 0; $i < 16; ++$i )
			{
	            $secret .= $validChars[ ord( $randomString[ $i ] ) & 31 ];
	        }
	    }
		
		/* Generate QR code */
		IPS::$PSR0Namespaces['BaconQrCode'] = \IPS\ROOT_PATH . '/system/3rd_party/BaconQrCode/src';
		IPS::$PSR0Namespaces['DASPRiD'] = \IPS\ROOT_PATH . '/system/3rd_party/DASPRiD';
		$renderer = new ImageRenderer(
			new RendererStyle(150),
			new SvgImageBackEnd()
		);
		$writer = new Writer($renderer);
		$data   = "otpauth://totp/{$member->email}?secret={$secret}&issuer=" . rawurlencode( Settings::i()->board_name );

		$str = $writer->writeString($data);

		$qrCode = 'data:image/svg+xml;utf8,' . rawurlencode($str);
		
		/* Display */
		return Theme::i()->getTemplate( 'login', 'core', 'global' )->googleAuthenticatorSetup( $qrCode, $secret, $showingMultipleHandlers );
	}
	
	/**
	 * Submit configuration screen. Return TRUE if was accepted
	 *
	 * @param	Member		$member	The member
	 * @return	bool
	 */
	public function configurationScreenSubmit( Member $member ): bool
	{
		if ( Request::i()->google_authenticator_setup_code )
		{
			if ( static::checkSubmittedCode( Request::i()->google_authenticator_setup_code, Request::i()->secret, $member ) )
			{
				$mfaDetails = $member->mfa_details;
				$mfaDetails['google'] = Request::i()->secret;
				$member->mfa_details = $mfaDetails;
				$member->save();

				/* Log MFA Enable */
				$member->logHistory( 'core', 'mfa', array( 'handler' => $this->key, 'enable' => TRUE ) );

				return TRUE;
			}
		}
		return FALSE;
	}
	
	/* !Authentication */
	
	/**
	 * Get the form for a member to authenticate
	 *
	 * @param	Member		$member		The member
	 * @param	Url	$url		URL for page
	 * @return	string
	 */
	public function authenticationScreen( Member $member, Url $url ): string
	{
		try
		{
			$waitUntil = ( Db::i()->select( 'time', 'core_googleauth_used_codes', array( '`member`=?', $member->member_id ), 'time DESC', 1 )->first() * 30 ) + 30;
		}
		catch ( UnderflowException $e )
		{
			$waitUntil = NULL;
		}
				
		return Theme::i()->getTemplate( 'login', 'core', 'global' )->googleAuthenticatorAuth( $waitUntil );
	}
	
	/**
	 * Submit authentication screen. Return TRUE if was accepted
	 *
	 * @param	Member		$member	The member
	 * @return	bool
	 */
	public function authenticationScreenSubmit( Member $member ): bool
	{
		if ( Request::i()->google_authenticator_auth_code )
		{
			if ( $codeTime = static::checkSubmittedCode( Request::i()->google_authenticator_auth_code, $member->mfa_details['google'], $member ) )
			{
				Db::i()->insert( 'core_googleauth_used_codes', array(
					'member'	=> $member->member_id,
					'time'		=> $codeTime
				) );
				return TRUE;
			}
			return FALSE;
		}
		return FALSE;
	}
	
	/* !ACP */
	
	/**
	 * Toggle
	 *
	 * @param	bool	$enabled	On/Off
	 * @return	void
	 */
	public function toggle( bool $enabled ): void
	{
		Settings::i()->changeValues( array( 'googleauth_enabled' => $enabled ) );
	}
	
	/**
	 * ACP Settings
	 *
	 * @return	string
	 */
	public function acpSettings(): string
	{
		$form = new Form;
		$form->add( new CheckboxSet( 'googleauth_groups', Settings::i()->googleauth_groups == '*' ? '*' : explode( ',', Settings::i()->googleauth_groups ), FALSE, array(
			'multiple'		=> TRUE,
			'options'		=> array_combine( array_keys( Group::groups() ), array_map( function( $_group ) { return (string) $_group; }, Group::groups() ) ),
			'unlimited'		=> '*',
			'unlimitedLang'	=> 'everyone',
			'impliedUnlimited' => TRUE
		) ) );
		
		if ( $values = $form->values() )
		{
			$values['googleauth_groups'] = ( $values['googleauth_groups'] == '*' ) ? '*' : implode( ',', $values['googleauth_groups'] );
			$form->saveAsSettings( $values );	
			Session::i()->log( 'acplogs__mfa_handler_enabled', array( "mfa_google_title" => TRUE ) );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=mfa' ), 'saved' );
		}
		
		return (string) $form;
	}
	
	/* !Misc */
	
	/**
	 * If member has configured this handler, disable it
	 *
	 * @param	Member	$member	The member
	 * @return	void
	 */
	public function disableHandlerForMember( Member $member ): void
	{
		$mfaDetails = $member->mfa_details;
		unset( $mfaDetails['google'] );
		$member->mfa_details = $mfaDetails;
		$member->save();

		/* Log MFA Disable */
		$member->logHistory( 'core', 'mfa', array( 'handler' => $this->key, 'enable' => FALSE ) );
	}
	
	/* !Helper Methods */
	
	/**
	 * Verify a submitted code with a ±30 seconds leeway
	 *
	 * @param string $submittedCode		The code that was submitted
	 * @param string $secret				The secret key
	 * @param Member $member				The member this is for
	 * @return	int|FALSE
	 */
	protected static function checkSubmittedCode(string $submittedCode, string $secret, Member $member ): int|FALSE
	{
		$submittedCode = str_replace( ' ', '', $submittedCode );
				
		$validTimes = array( new DateTime(), ( new DateTime() )->add( new DateInterval('PT30S') ), ( new DateTime() )->sub( new DateInterval('PT30S') ) );
		$blockedTimes = iterator_to_array( Db::i()->select( 'time', 'core_googleauth_used_codes', array( '`member`=?', $member->member_id ) ) );

		$allowedCodes = array();
		foreach ( $validTimes as $time )
		{
			$codeTime = floor( $time->getTimestamp() / 30 );
			if ( !in_array( $codeTime, $blockedTimes ) )
			{
				$allowedCodes[ static::getCodeForSecretAtTime( $secret, $time ) ] = $codeTime;
			}
		}
		
		foreach ( $allowedCodes as $code => $time )
		{
			if ( Login::compareHashes( (string) $code, $submittedCode ) )
			{
				return $time;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Get the code
	 *
	 * @param string $secret		The secret key for the user
	 * @param	DateTime	$time	Timestamp
	 * @return	string
	 */
	protected static function getCodeForSecretAtTime( string $secret, DateTime $time ): string
	{
		/* Decode secret key */
		$secret = str_split( str_replace( '=', '', $secret ) );
		$chars = array( 'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25, 2 => 26, 3 => 27, 4 => 28, 5 => 29, 6 => 30, 7 => 31, '=' => 32 );		
		$decodedSecretKey = '';
		for ( $i = 0; $i < 16; $i += 8 )
		{
			$block = '';
            for ( $j = 0; $j < 8; ++$j )
            {
                $block .= str_pad( base_convert( $chars[ $secret[ $i + $j ] ], 10, 2 ), 5, '0', STR_PAD_LEFT );
            }
            $eightBits = str_split( $block, 8 );
            for ( $z = 0; $z < count( $eightBits ); ++$z )
            {
                $decodedSecretKey .=  ( ( $y = chr( base_convert( $eightBits[ $z ], 2, 10) ) ) || ord( $y ) == 48) ? $y : '';
            }
		}
		        
        /* Hash the timestamp with the secret key */
        $hash = hash_hmac('SHA1', chr(0). chr(0). chr(0). chr(0).pack('N*', floor( $time->getTimestamp() / 30 ) ), $decodedSecretKey, true);
        
        /* Unpack it */
        $value = unpack( 'N', substr( $hash, ord( substr( $hash, -1 ) ) & 0x0F, 4 ) );
        $value = $value[1];
        
        /* Get 32 bits */
        $value = $value & 0x7FFFFFFF;
        return str_pad( $value % pow( 10, 6 ), 6, '0', STR_PAD_LEFT );
	}
}