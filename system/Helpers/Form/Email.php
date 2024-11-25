<?php
/**
 * @brief		Email input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Mar 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Db;
use IPS\Login;
use IPS\Member;
use IPS\Settings;
use function count;
use function defined;
use function mb_stripos;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Email input class for Form Builder
 */
class Email extends Text
{
	/**
	 * @brief	Default Options
	 * @code
	 	$childDefaultOptions = array(
	 		'accountEmail' => TRUE,	// If TRUE, additional checks will be performed to ensure provided email address is acceptable for use on a user's account. If an \IPS\Member object, that member will be excluded
	 	);
	 * @endcode
	 */
	public array $childDefaultOptions = array(
		'accountEmail'	=> FALSE,
		'htmlAutocomplete'	=> "email",
		'bypassProfanity' => Text::BYPASS_PROFANITY_ALL
	);
	
	/**
	 * Validate
	 *
	 * @param string $value			The provided value (an email address)
	 * @param bool|Member $accountEmail	If TRUE, additional checks will be performed to ensure provided email address is acceptable for use on a user's account. If an \IPS\Member object, that member will be excluded
	 * @return	TRUE
	 *@throws	DomainException
	 * @throws	InvalidArgumentException
	 */
	public static function validateEmail(string $value, bool|Member $accountEmail=NULL )
	{
		/* Check it's generally an acceptable email */
		if ( $value !== '' and filter_var( $value, FILTER_VALIDATE_EMAIL ) === FALSE )
		{
			throw new InvalidArgumentException('form_email_bad');
		}
		
		/* If it's for a user account, do additional checks */
		if ( $accountEmail )
		{
			/* Check if it exists */
			if ( $error = Login::emailIsInUse( $value, ( $accountEmail instanceof Member ) ? $accountEmail : NULL, Member::loggedIn()->isAdmin() ) )
			{
				throw new DomainException( $error );
			}


			/* Check Banned and Allowed Emails only if the data are not coming from an administrator */
			if ( !Member::loggedIn()->isAdmin() )
			{
				/* Check it's not known to be undeliverable */
				if ( \IPS\Email::emailIsBlocked( $value ) )
				{
					throw new DomainException( 'member_email_blocked_info' );
				}

				/* Check it's not a banned address */
				foreach ( Db::i()->select( 'ban_content', 'core_banfilters', array( "ban_type=?", 'email' ) ) as $bannedEmail )
				{
					if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedEmail, '/' ) ) . '$/i', $value ) )
					{
						throw new DomainException( 'form_email_banned' );
					}
				}

				/* Check it's an allowed domain */
				if ( Settings::i()->allowed_reg_email !== '' AND $allowedEmailDomains = explode( ',', Settings::i()->allowed_reg_email )  )
				{
					$matched = FALSE;
					foreach ( $allowedEmailDomains AS $domain )
					{
						if( mb_stripos( $value,  "@" . $domain ) !== FALSE )
						{
							$matched = TRUE;
						}
					}

					if ( count( $allowedEmailDomains ) AND !$matched )
					{
						throw new DomainException( 'form_email_banned' );
					}
				}
			}
		}

		return TRUE;
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @throws	DomainException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		parent::validate();
		
		return static::validateEmail( $this->value, $this->options['accountEmail'] );
	}
}