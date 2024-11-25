<?php
/**
 * @brief		Standard Internal Database Login Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 May 2017
 */

namespace IPS\Login\Handler;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Http\Url;
use IPS\Login;
use IPS\Login\Exception;
use IPS\Login\Handler;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Standard Internal Database Login Handler
 */
class Standard extends Handler
{
	use UsernamePasswordHandler;
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public static function getTitle(): string
	{
		return 'login_handler_Internal';
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
		return array();
	}
	
	/**
	 * Authenticate
	 *
	 * @param	Login	$login				The login object
	 * @param string $usernameOrEmail		The username or email address provided by the user
	 * @param object $password			The plaintext password provided by the user, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	Member
	 * @throws	Exception
	 */
	public function authenticateUsernamePassword( Login $login, string $usernameOrEmail, object $password ): Member
	{
		/* Make sure we have the username or email */
		if( !$usernameOrEmail )
		{
			throw new Exception( Member::loggedIn()->language()->addToStack( 'login_err_no_account', FALSE ), Exception::NO_ACCOUNT );
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
		
		/* Check the password for each possible account */
		foreach ( $members as $member )
		{
			if ( $this->authenticatePasswordForMember( $member, $password ) )
			{				
				/* If it's the old style, convert it to the new */
				if ( $member->members_pass_salt )
				{
					$member->setLocalPassword( $password );
					$member->save();
				}
				
				/* Return */
				return $member;
			}
		}

		/* Still here? Throw a password incorrect exception */
		throw new Exception( Member::loggedIn()->language()->addToStack( 'login_err_bad_password', FALSE ), Exception::BAD_PASSWORD, NULL, $member );
	}
	
	/**
	 * Authenticate
	 *
	 * @param	Member	$member		The member
	 * @param object $password	The plaintext password provided by the user, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	bool
	 */
	public function authenticatePasswordForMember( Member $member, object $password ): bool
	{
		if ( password_verify( $password, $member->members_pass_hash ) === TRUE )
		{
			return TRUE;
		}
		elseif ( $member->members_pass_salt and mb_strlen( $member->members_pass_hash ) === 32 )
		{
			return $member->verifyLegacyPassword( $password );
		}
		
		return FALSE;
	}

	/**
	 * Can this handler process a login for a member?
	 *
	 * @param Member $member
	 * @return    bool
	 */
	public function canProcess( Member $member ): bool
	{
		return (bool) $member->members_pass_hash;
	}

	/**
	 * Can this handler process a password change for a member?
	 *
	 * @param Member $member
	 * @return    bool
	 */
	public function canChangePassword( Member $member ): bool
	{
		/* If it's forced, then yes. */
		if ( $member->members_bitoptions['password_reset_forced'] AND !$member->members_pass_hash )
		{
			return TRUE;
		}
		
		return $this->canProcess( $member );
	}
	
	/**
	 * Can this handler sync passwords?
	 *
	 * @return	bool
	 */
	public function canSyncPassword(): bool
	{
		return TRUE;
	}
	
	/**
	 * Change Password
	 *
	 * @param	Member	$member			The member
	 * @param	object|string		$newPassword		New Password wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	void
	 */
	public function changePassword( Member $member, object|string $newPassword ) : void
	{
		$member->setLocalPassword( $newPassword );
		$member->save();
	}
	
	/**
	 * Force Password Reset URL
	 *
	 * @param	Member			$member		The member
	 * @param	Url|NULL	$ref		Referrer
	 * @return	Url|NULL
	 */
	public function forcePasswordResetUrl( Member $member, ?Url $ref = NULL ): ?Url
	{
		return $member->passwordResetForced( $ref );
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
}