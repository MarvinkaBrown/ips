<?php
/**
 * @brief		Converter Login Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		15 October 2017
 */

namespace IPS\convert;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\convert\Login\HashCryptPrivate;
use IPS\convert\Software\Core\Joomla;
use IPS\convert\Software\Core\Phpbb;
use IPS\convert\Software\Core\Vbulletin;
use IPS\convert\Software\Core\Vbulletin5;
use IPS\convert\Software\Core\Wordpress;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Events\Event;
use IPS\Login as LoginClass;
use IPS\Login\Exception;
use IPS\Login\Handler;
use IPS\Login\Handler\UsernamePasswordHandler;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use SoapClient;
use function count;
use function defined;
use function strlen;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Standard Internal Database Login Handler
 */
class Login extends Handler
{
	use UsernamePasswordHandler;

	/**
	 * Get title
	 *
	 * @return	string
	 */
	public static function getTitle(): string
	{
		return 'login_handler_Convert';
	}

	/**
	 * Authenticate
	 *
	 * @param	LoginClass	$login				The login object
	 * @param string $usernameOrEmail		The username or email address provided by the user
	 * @param object $password			The plaintext password provided by the user, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	Member
	 * @throws	Exception
	 */
	public function authenticateUsernamePassword( LoginClass $login, string $usernameOrEmail, object $password ): Member
	{
		/* Make sure we have the username or email */
		if( !$usernameOrEmail )
		{
			$member = NULL;

			$member = new Member;
			$member->email = $usernameOrEmail;

			throw new Exception( Member::loggedIn()->language()->addToStack( 'login_err_no_account', FALSE ), Exception::NO_ACCOUNT, NULL, $member );
		}

		/* Get member(s) */
		$where = array();
		$params = array();

		$where[] = 'email=?';
		$params[] = $usernameOrEmail;

		if ( $usernameOrEmail !== Request::legacyEscape( $usernameOrEmail ) )
		{
			$where[] = 'email=?';
			$params[] = Request::legacyEscape( $usernameOrEmail );
		}

		$members = new ActiveRecordIterator( Db::i()->select( '*', 'core_members', array_merge( array( implode( ' OR ', $where ) ), $params ) ), 'IPS\Member' );

		/* If we didn't match any, throw an exception */
		if ( !count( $members ) )
		{
			$member = NULL;

			$member = new Member;
			$member->email = $usernameOrEmail;

			throw new Exception( Member::loggedIn()->language()->addToStack( 'login_err_no_account', FALSE ), Exception::NO_ACCOUNT, NULL, $member );
		}

		/* Table switcher for new converters */
		try
		{
			foreach( $members as $member )
			{
				if( $this->authenticatePasswordForMember( $member, $password ) )
				{
					return $member;
				}
			}
		}
		catch( DbException $e )
		{
			/* Converter tables no longer exist */
			if( $e->getCode() == 1146 )
			{
				throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
			}
		}

		/* Still here? Throw a password incorrect exception */
		throw new Exception( Member::loggedIn()->language()->addToStack( 'login_err_bad_password', FALSE ), Exception::BAD_PASSWORD, NULL, $member ?? NULL );
	}

	/**
	 *	@brief		Convert app cache
	 */
	protected static ?ActiveRecordIterator $_apps;

	/**
	 * Authenticate
	 *
	 * @param	Member	$member				The member
	 * @param object $password			The plaintext password provided by the user, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	bool
	 */
	public function authenticatePasswordForMember( Member $member, object $password ): bool
	{
		if( static::$_apps === NULL )
		{
			static::$_apps = new ActiveRecordIterator( Db::i()->select( '*', 'convert_apps', array( 'login=?', 1 ) ), 'IPS\convert\App' );
		}

		foreach( static::$_apps as $app )
		{
			/* Strip underscores from keys */
			$sw = str_replace( "_", "", $app->key );

			/* Get converter classname */
			try
			{
				$application = $app->getSource( FALSE, FALSE );
			}
			/* Converter application class no longer exists, but we want to continue since we may have a login method here */
			catch( InvalidArgumentException $e )
			{
				$application = NULL;
			}

			/* Check at least one of the login methods exist */
			if ( !method_exists( $this, $sw ) AND ( $application === NULL OR !method_exists( $application, 'login' ) ) )
			{
				continue;
			}

			/* We still want to use the parent methods (no sense in recreating them) so copy conv_password_extra to misc */
			$member->misc = $member->conv_password_extra;

			/* New login method */
			if( class_exists( $application ) AND method_exists( $application, 'login' ) )
			{
				$success = $application::login( $member, (string) $password );
			}
			/* Deprecated method */
			else
			{
				$success = $this->$sw( $member, (string) $password );
			}

			unset( $member->misc );
			unset( $member->changed['misc'] );

			if ( $success )
			{
				/*	Update password and return */
				$member->conv_password			= NULL;
				$member->conv_password_extra	= NULL;
				$member->setLocalPassword( $password );
				$member->save();
				Event::fire( 'onPassChange', $member, array( $password ) );

				return true;
			}
		}

		return FALSE;
	}

	/**
	 * Can this handler process a login for a member?
	 *
	 * @param	Member	$member	Member
	 * @return	bool
	 */
	public function canProcess( Member $member ): bool
	{
		return (bool) $member->conv_password;
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
	 * Change Password
	 *
	 * @param	Member	$member			The member
	 * @param	object		$newPassword		New Password wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	void
	 */
	public function changePassword( Member $member, object $newPassword ) : void
	{
		$member->setLocalPassword( $newPassword );
		$member->save();
	}

	/**
	 * Show in Account Settings?
	 *
	 * @param	Member|NULL	$member	The member, or NULL for if it should show generally
	 * @return	bool
	 */
	public function showInUcp( Member $member = NULL ): bool
	{
		return FALSE;
	}

	/**
	 * AEF
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function aef( Member $member, string $password ) : bool
	{
		if ( LoginClass::compareHashes( $member->conv_password, md5( $member->misc . $password ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * BBPress Standalone
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function bbpressstandalone( Member $member, string $password ) : bool
	{
		return $this->bbpress( $member, $password );
	}

	/**
	 * BBPress
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function bbpress( Member $member, string $password ) : bool
	{
		$success = false;
		$password = html_entity_decode( $password );
		$hash = $member->conv_password;

		if ( strlen( $hash ) == 32 )
		{
			$success = ( LoginClass::compareHashes( $member->conv_password, md5( $password ) ) );
		}

		// Nope, not md5.
		if ( ! $success )
		{
			$hashLibrary = new HashCryptPrivate;
			$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			$crypt = $hashLibrary->hashCryptPrivate( $password, $hash, $itoa64, 'P' );
			if ( $crypt[ 0 ] == '*' )
			{
				$crypt = crypt( $password, $hash );
			}

			if ( $crypt == $hash )
			{
				$success = true;
			}
		}

		// Nope
		if ( ! $success )
		{
			// No - check against WordPress.
			// Note to self - perhaps push this to main bbpress method.
			$success = Wordpress::login( $member, $password );
		}

		return $success;
	}

	/**
	 * BBPress 2.3
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function bbpress23( Member $member, string $password ) : bool
	{
		return $this->bbpress( $member, $password );
	}

	/**
	 * Community Server
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function cs( Member $member, string $password ) : bool
	{
		$encodedHashPass = base64_encode( pack( "H*", sha1( base64_decode( $member->misc ) . $password ) ) );

		if ( LoginClass::compareHashes( $member->conv_password, $encodedHashPass ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * CSAuth
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function csauth( Member $member, string $password ) : bool
	{
		$wsdl = 'https://internal.auth.com/Service.asmx?wsdl';
		$dest = 'https://interal.auth.com/Service.asmx';
		$single_md5_pass = md5( $password );

		try
		{
			$client = new SoapClient( $wsdl, array( 'trace' => 1 ) );
			$client->__setLocation( $dest );
			$loginparams = array( 'username' => $member->name, 'password' => $password );
			$result = $client->AuthCS( $loginparams );

			switch( $result->AuthCSResult )
			{
				case 'SUCCESS' :
					return TRUE;
				case 'WRONG_AUTH' :
				default:
					return FALSE;
			}
		}
		catch( Exception $ex )
		{
			return FALSE;
		}
	}

	/**
	 * Discuz
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function discuz( Member $member, string $password ) : bool
	{
		if ( LoginClass::compareHashes( $member->conv_password, md5( md5( $password ) . $member->misc ) ) )
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * FudForum
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function fudforum( Member $member, string $password ) : bool
	{
		$success = false;
		$single_md5_pass = md5( $password );
		$hash = $member->conv_password;

		if ( strlen( $hash ) == 40 )
		{
			$success = LoginClass::compareHashes( $member->conv_password, sha1( $member->misc . sha1( $password ) ) );
		}
		else
		{
			$success = LoginClass::compareHashes( $member->conv_password, $single_md5_pass );
		}

		return $success;
	}

	/**
	 * FusionBB
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function fusionbb( Member $member, string $password ) : bool
	{
		/* FusionBB Has multiple methods that can be used to check a hash, so we need to cycle through them */

		/* md5( md5( salt ) . md5( pass ) ) */
		if ( LoginClass::compareHashes( $member->conv_password, md5( md5( $member->misc ) . md5( $password ) ) ) )
		{
			return TRUE;
		}

		/* md5( md5( salt ) . pass ) */
		if ( LoginClass::compareHashes( $member->conv_password, md5( md5( $member->misc ) . $password ) ) )
		{
			return TRUE;
		}

		/* md5( pass ) */
		if ( LoginClass::compareHashes( $member->conv_password, md5( $password ) ) )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Ikonboard
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function ikonboard( Member $member, string $password ) : bool
	{
		if ( LoginClass::compareHashes( $member->conv_password, crypt( $password, $member->misc ) ) )
		{
			return TRUE;
		}
		else if ( LoginClass::compareHashes( $member->conv_password, md5( $password . mb_strtolower( $member->conv_password_extra ) ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Kunena
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function kunena( Member $member, string $password ) : bool
	{
		// Kunena authenticates using internal Joomla functions.
		// This is required, however, if the member only converts from
		// Kunena and not Joomla + Kunena.
		return Joomla::login( $member, $password );
	}

	/**
	 * PHP Legacy (2.x)
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function phpbblegacy( Member $member, string $password ) : bool
	{
		return Phpbb::login( $member, $password );
	}

	/**
	 * Vbulletin 5.1
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vb51connect( Member $member, string $password ) : bool
	{
		return Vbulletin5::login( $member, $password );
	}

	/**
	 * Vbulletin 5
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vb5connect( Member $member, string $password ) : bool
	{
		return Vbulletin5::login( $member, $password );
	}

	/**
	 * Vbulletin 3.8
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vbulletinlegacy( Member $member, string $password ) : bool
	{
		return Vbulletin::login( $member, $password );
	}

	/**
	 * Vbulletin 3.6
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vbulletinlegacy36( Member $member, string $password ) : bool
	{
		return Vbulletin::login( $member, $password );
	}

	/**
	 * SMF Legacy
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function smflegacy( Member $member, string $password ) : bool
	{
		if ( LoginClass::compareHashes( $member->conv_password, sha1( mb_strtolower( $member->name ) . $password ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Telligent
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function telligentcs( Member $member, string $password ) : bool
	{
		return $this->cs( $member, $password );
	}

	/**
	 * WoltLab 4.x
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function woltlab( Member $member, string $password ) : bool
	{
		$testHash = FALSE;

		/* If it's not blowfish, then we don't have a salt for it. */
		if ( !preg_match( '/^\$2[ay]\$(0[4-9]|[1-2][0-9]|3[0-1])\$[a-zA-Z0-9.\/]{53}/', $member->conv_password ) )
		{
			$salt = mb_substr( $member->conv_password, 0, 29 );
			$testHash = crypt( crypt( $password, $salt ), $salt );
		}

		if (	$testHash AND LoginClass::compareHashes( $member->conv_password, $testHash ) )
		{
			return TRUE;
		}
		elseif ( $this->woltlablegacy( $member, $password ) )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * WoltLab 3.x
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function woltlablegacy( Member $member, string $password ) : bool
	{
		if ( LoginClass::compareHashes( $member->conv_password, sha1( $member->misc . sha1( $member->misc . sha1( $password ) ) ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * PHP Fusion
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function phpfusion( Member $member, string $password ) : bool
	{
		return LoginClass::compareHashes( $member->conv_password, md5( md5( $password ) ) );
	}

	/**
	 * fluxBB
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function fluxbb( Member $member, string $password ) : bool
	{
		$success = false;
		$hash = $member->conv_password;

		if ( strlen( $hash ) == 40 )
		{
			if ( LoginClass::compareHashes( $hash, sha1( $member->misc . sha1( $password ) ) ) )
			{
				$success = TRUE;
			}
			elseif ( LoginClass::compareHashes( $hash, sha1( $password ) ) )
			{
				$success = TRUE;
			}
		}
		else
		{
			$success = LoginClass::compareHashes( $hash, md5( $password ) );
		}

		return $success;
	}

	/**
	 * Simplepress Forum
	 *
	 * @param	Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function simplepress( Member $member, string $password ) : bool
	{
		return Wordpress::login( $member, $password );
	}
}