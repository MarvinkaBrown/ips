<?php
/**
 * @brief		PHP Email Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Apr 2013
 */

namespace IPS\Email\Outgoing;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Email;
use IPS\IPS;
use IPS\Settings;
use function defined;
use function function_exists;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PHP Email Class
 */
class Php extends Email
{
	/**
	 * Send the email
	 * 
	 * @param	mixed	$to					The member or email address, or array of members or email addresses, to send to
	 * @param mixed $cc					Addresses to CC (can also be email, member or array of either)
	 * @param mixed $bcc				Addresses to BCC (can also be email, member or array of either)
	 * @param mixed $fromEmail			The email address to send from. If NULL, default setting is used
	 * @param mixed $fromName			The name the email should appear from. If NULL, default setting is used
	 * @param array $additionalHeaders	The name the email should appear from. If NULL, default setting is used
	 * @return	void
	 * @throws   Exception
	 */
	public function _send( mixed $to, mixed $cc=array(), mixed $bcc=array(), mixed $fromEmail = NULL, mixed $fromName = NULL, array $additionalHeaders = array() ) : void
	{
        if( !function_exists( 'mail' ) )
        {
            throw new Exception( 'email_test_mailfunction_disabled' );
        }

		$boundary = "--==_mimepart_" . md5( mt_rand() );
		
		$subject = $this->compileSubject( static::_getMemberFromRecipients( $to ) );
		$headers = array();
		foreach( $this->_compileHeaders( $subject, $to, $cc, $bcc, $fromEmail, $fromName, $additionalHeaders, $boundary ) as $k => $v )
		{
			if ( !in_array( $k, array( 'To', 'Subject' ) ) )
			{
				$headers[] = "{$k}: {$v}";
			}
		}
		
		try
		{			
			if ( !mail( $this->_parseRecipients( $to, TRUE ), static::encodeHeader( $subject ), $this->_compileMessage( static::_getMemberFromRecipients( $to ), $boundary, "\r\n", 68 ), implode( "\r\n", $headers ), Settings::i()->php_mail_extra ) )
			{
				if ( $error = IPS::$lastError )
				{
					throw new Exception( $error->getMessage(), $error->getCode() );
				}
				else
				{
					/* If $error is null, mail() is probably disabled */
					throw new Exception( 'email_test_mailfunction_disabled' );
				}
			}
		}
		catch ( Exception $e )
		{
			throw new Exception( $e->getMessage() );
		}
	}
}