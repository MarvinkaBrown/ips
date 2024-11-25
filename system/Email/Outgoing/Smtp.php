<?php
/**
 * @brief		SMTP Email Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Apr 2013
 */

namespace IPS\Email\Outgoing;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Email;
use IPS\Request;
use IPS\Settings;
use function defined;
use function intval;
use function stream_socket_enable_crypto;
use const IPS\DEFAULT_REQUEST_TIMEOUT;
use const IPS\VERY_LONG_REQUEST_TIMEOUT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SMTP Email Class
 */
class Smtp extends Email
{
	/**
	 * @brief	SMTP Protocol ("tls", "ssl" or "plain")
	 */
	protected string $smtpProtocol;
	
	/**
	 * @brief	SMTP Host
	 */
	protected string $smtpHost;
	
	/**
	 * @brief	SMTP Port
	 */
	protected int $smtpPort;
	
	/**
	 * @brief	SMTP Username
	 */
	protected string $smtpUser;
	
	/**
	 * @brief	SMTP Password
	 */
	protected string $smtpPass;
	
	/**
	 * @brief	SMTP Connections
	 */
	protected static array $smtp = array();
	
	/**
	 * @brief	Connection Key
	 */
	protected string $connectionKey;
	
	/**
	 * @brief	Log
	 */
	protected string $log = '';
	
	/**
	 * Constructor
	 *
	 * @param string $smtpProtocol	Protocol to use
	 * @param string $smtpHost		Hostname to connect to
	 * @param int $smtpPort		Port to connect to
	 * @param string $smtpUser		Username
	 * @param string $smtpPass		Password
	 * @return	void
	 */
	public function __construct( string $smtpProtocol, string $smtpHost, int $smtpPort, string $smtpUser, string $smtpPass )
	{
		$this->smtpProtocol = $smtpProtocol;
		$this->smtpHost = $smtpHost;
		$this->smtpPort = $smtpPort;
		$this->smtpUser = $smtpUser;
		$this->smtpPass = $smtpPass;
		$this->connectionKey = md5( $smtpProtocol . $smtpHost . $smtpPort . $smtpUser . $smtpPass );
	}
	
	/**
	 * Connect to server
	 *
	 * @param bool $checkSsl	If set to FALSE, will skip peer certificate verification for TLS connections
	 * @return void
	 */
	public function connect( bool $checkSsl=TRUE ) : void
	{
		/* Do we already have a connection? */
		if( array_key_exists( $this->connectionKey, static::$smtp ) )
		{
			return;
		}

		/* Connect */
		$connection = @fsockopen( ( ( $this->smtpProtocol == 'ssl' ) ? 'ssl://' : '' ) . $this->smtpHost, $this->smtpPort, $errno, $errstr, Request::isCliEnvironment() ? VERY_LONG_REQUEST_TIMEOUT : DEFAULT_REQUEST_TIMEOUT );
		if ( !$connection )
		{
			throw new Exception( $errstr, $errno );
		}
		static::$smtp[ $this->connectionKey ] = $connection;
		register_shutdown_function(function( $object ){
			$object->_sendCommand( 'quit' );
			@fclose( static::$smtp[ $object->connectionKey ] );
			unset( static::$smtp[ $object->connectionKey ] );
		}, $this );

		/* Check the initial response is okay */
		$announce		= $this->_getResponse();
		$responseCode	= mb_substr( $announce, 0, 3 );
		if ( $responseCode != 220 )
		{
			throw new Exception( 'smtpmail_fsock_error_initial', 0, NULL, array( $responseCode ) );
		}

		/* HELO/EHLO */
		try
		{
			$helo = 'EHLO';
			$responseCode = $this->_sendCommand('EHLO ' . $this->smtpHost, 250);
		}
		catch (Exception $e )
		{
			$helo = 'HELO';
			$responseCode = $this->_sendCommand('HELO ' . $this->smtpHost, 250);
		}

		/* Is TLS being used? */
		if( $this->smtpProtocol == 'tls' )
		{
			if ( $checkSsl )
			{
				@stream_context_set_option( static::$smtp[ $this->connectionKey ], 'ssl', 'verify_peer', false );
			}
			
			$this->_sendCommand('STARTTLS', 220);
			if ( !@stream_socket_enable_crypto( static::$smtp[ $this->connectionKey ], TRUE, STREAM_CRYPTO_METHOD_SSLv23_CLIENT ) )
			{
				if ( $checkSsl )
				{
					/* Try again, but ignore SSL checks in case the certificate was self-signed, which will fail when initializing TLS. This will be slightly slower to connect, but will avoid an error in most instances. */
					$this->connect( FALSE );
				}
				else
				{
					/* If it still failed on the second connection attempt, throw the exception */
					throw new Exception( 'smtpmail_tls_failed' );
				}
			}

			/* Exchange server (at least) wants EHLO resending for STARTTLS */
			$this->_sendCommand($helo . ' ' . $this->smtpHost, 250);
		}

		/* Authenticate */
		if ( $this->smtpUser )
		{
			$responseCode = $this->_sendCommand('AUTH LOGIN', 334);
			$responseCode = $this->_sendCommand(base64_encode($this->smtpUser), 334);
			$responseCode = $this->_sendCommand(base64_encode($this->smtpPass), 235);
		}
	}
		
	/**
	 * Send the email
	 * 
	 * @param	mixed	$to					The member or email address, or array of members or email addresses, to send to
	 * @param mixed $cc					Addresses to CC (can also be email, member or array of either)
	 * @param mixed $bcc				Addresses to BCC (can also be email, member or array of either)
	 * @param mixed $fromEmail			The email address to send from. If NULL, default setting is used
	 * @param mixed $fromName			The name the email should appear from. If NULL, default setting is used
	 * @param array $additionalHeaders	Additional headers to send
	 * @return	void
	 * @throws    Exception
	 */
	public function _send( mixed $to, mixed $cc=array(), mixed $bcc=array(), mixed $fromEmail = NULL, mixed $fromName = NULL, array $additionalHeaders = array() ) : void
	{
		/* Get the from email */
		$fromEmail = $fromEmail ?: Settings::i()->email_out;
		
		/* SMTP requires you to do CC/BCC by sending a RCPT TO command for each recipient. We'll hide BCC by not actually setting that header */ 
		$recipieintsForSmtp = explode( ',', static::_parseRecipients( $to, TRUE ) );
		if ( $cc )
		{
			$recipieintsForSmtp = array_merge( $recipieintsForSmtp, explode( ',', static::_parseRecipients( $cc, TRUE ) ) );
		}
		if ( $bcc )
		{
			$recipieintsForSmtp = array_merge( $recipieintsForSmtp, explode( ',', static::_parseRecipients( $bcc, TRUE ) ) );
		}
		$recipieintsForSmtp = array_unique( array_map( 'trim', $recipieintsForSmtp ) );
		
		/* Send */
		$this->_sendCompiled( $fromEmail, $recipieintsForSmtp, $this->compileFullEmail( $to, $cc, array(), $fromEmail, $fromName, $additionalHeaders ) );
	}
	
	/**
	 * Send an email
	 * 
	 * @param string $fromEmail			The email address to send from
	 * @param array $toEmails			Array of email addresses to send to
	 * @param string $email				The full email (with headers, etc.) except the Bcc header
	 * @return	void
	 * @throws    Exception
	 */
	public function _sendCompiled( string $fromEmail, array $toEmails, string $email ) : void
	{
		$this->connect();
		
		$this->_sendCommand("MAIL FROM:<{$fromEmail}>", 250);
		
		foreach ( $toEmails as $toEmail )
		{
			$this->_sendCommand("RCPT TO:<{$toEmail}>", 250 );
		}
				
		$this->_sendCommand('DATA', 354);
		$this->_sendCommand($email . "\r\n.", 250);
	}
	
	/**
	 * Send SMTP Command
	 *
	 * @param string $command			The command
	 * @param int|null $expectedResponse	The expected response code. Will throw an exception if different.
	 * @param bool $resetOnFailure		If the command fails, issue a RSET (reset) command
	 * @return	string	Response
	 * @throws    Exception
	 */
	protected function _sendCommand( string $command, ?int $expectedResponse=NULL, bool $resetOnFailure = TRUE): string
	{
		/* Log */
		$this->log .= "> {$command}\r\n";
		
		/* Send */
		fputs( static::$smtp[ $this->connectionKey ], $command . "\r\n" );
		
		/* Read */
		$response = $this->_getResponse();
		
		/* Get response code */
		$code = intval( mb_substr( $response, 0, 3 ) );
		if ( $expectedResponse !== NULL and $code !== $expectedResponse )
		{
			if( $resetOnFailure === TRUE )
			{
				$this->_sendCommand('RSET');
			}

			throw new Exception( $response, $code );
		}
		
		/* Return */
		return $response;
	}

	/**
	 * Get response
	 *
	 * @return	string	Response
	 */
	protected function _getResponse(): string
	{
		/* Read */
		$response = '';
		while ( $line = @fgets( static::$smtp[ $this->connectionKey ], 515 ) )
		{			
			$response .= $line;
			if ( mb_substr($line, 3, 1) == " " )
			{
				break;
			}
		}
		
		/* Log */
		$this->log .= mb_convert_encoding( $response, 'UTF-8', 'ASCII' );
		
		/* Return */
		return $response;
	}
	
	/**
	 * Return the SMTP log
	 *
	 * @return string
	 */
	public function getLog(): string
	{
		return $this->log;
	}
}