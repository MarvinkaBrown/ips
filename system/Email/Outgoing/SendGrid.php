<?php
/**
 * @brief		SendGrid Email Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Oct 2013
 */

namespace IPS\Email\Outgoing;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Advertisement;
use IPS\Db;
use IPS\Email;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Settings;
use RuntimeException;
use function count;
use function defined;
use function is_array;
use const IPS\LONG_REQUEST_TIMEOUT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * SendGrid Email Class
 */
class SendGrid extends Email
{
	/* !Configuration */
	
	/**
	 * @brief	The number of emails that can be sent in one "go"
	 */
	const MAX_EMAILS_PER_GO = 1000; // SendGrid has a hard 1000 recipients per request limit
	
	/**
	 * @brief	API Key
	 */
	protected string $apiKey;
	
	/**
	 * Constructor
	 *
	 * @param string $apiKey	API Key
	 * @return	void
	 */
	public function __construct( string $apiKey )
	{
		$this->apiKey = $apiKey;
	}
	
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
	 * @throws    Exception
	 */
	public function _send( mixed $to, mixed $cc=array(), mixed $bcc=array(), mixed $fromEmail = NULL, mixed $fromName = NULL, array $additionalHeaders = array() ) : void
	{
		/* Initiate the request */
		$request = $this->_initRequest( $fromEmail, $fromName );
		
		/* Add the subject and content */
		$request['subject'] = $this->compileSubject( static::_getMemberFromRecipients( $to ) );
		$request['content'] = array(
			array(
				'type'				=> 'text/plain',
				'value'				=> $this->compileContent( 'plaintext', static::_getMemberFromRecipients( $to ) )
			),
			array(
				'type'				=> 'text/html',
				'value'				=> $this->compileContent( 'html', static::_getMemberFromRecipients( $to ) )
			),
		);
		
		/* Add the recipients */
		foreach ( array( 'to', 'cc', 'bcc' ) as $type )
		{
			if ( is_array( $$type ) )
			{
				foreach ( $$type as $recipient )
				{
					if ( $recipient instanceof Member )
					{
						$request['personalizations'][0][ $type ][] = array( 'email' => $recipient->email, 'name' => $recipient->name );
					}
					else
					{
						$request['personalizations'][0][ $type ][] = array( 'email' => $recipient );
					}
				}
			}
			elseif ( $$type )
			{
				$recipient = $$type;
				if ( $recipient instanceof Member )
				{
					$request['personalizations'][0][ $type ][] = array( 'email' => $recipient->email, 'name' => $recipient->name );
				}
				else
				{
					$request['personalizations'][0][ $type ][] = array( 'email' => $recipient );
				}
			}
		}
		
		/* Add additional headers */
		$request = $this->_modifyRequestDataWithHeaders( $request, $additionalHeaders );
				
		/* Send */
		$response = $this->_api( 'mail/send', $request );
		if ( isset( $response['errors'] ) )
		{
			throw new Exception( $response['errors'][0]['message'] );
		}
	}
	
	/**
	 * Merge and Send
	 *
	 * @param array $recipients			Array where the keys are the email addresses to send to and the values are an array of variables to replace
	 * @param mixed $fromEmail			The email address to send from. If NULL, default setting is used. NOTE: This should always be a site-controlled domin. Some services like Sparkpost require the domain to be validated.
	 * @param mixed $fromName			The name the email should appear from. If NULL, default setting is used
	 * @param array $additionalHeaders	Additional headers to send. Merge tags can be used like in content.
	 * @param	Lang|NULL	$language			The language the email content should be in
	 * @return	int				Number of successful sends
	 */
	public function mergeAndSend( array $recipients, mixed $fromEmail = NULL, mixed $fromName = NULL, array $additionalHeaders = array(), Lang $language = NULL ): int
	{
		/* Initiate the request */
		$request = $this->_initRequest( $fromEmail, $fromName );
		
		/* Add the subject and content */
		$subject = $this->compileSubject( NULL, $language );
		$htmlContent = $this->compileContent( 'html', null, $language );
		$plaintextContent = preg_replace( '/\*\|(.+?)\|\*/', '*|$1_plain|*', $this->compileContent( 'plaintext', null, $language ) );
		$request['subject'] = preg_replace( '/\*\|(.+?)\|\*/', '*|$1_plain|*', $subject );
		$request['content'] = array(
			array(
				'type'				=> 'text/plain',
				'value'				=> $plaintextContent
			),
			array(
				'type'				=> 'text/html',
				'value'				=> $htmlContent
			),
		);
		
		/* Add the recipients */
		$addresses = array();
		foreach ( $recipients as $email => $substitutions )
		{
			$addresses[] = $email;
			
			$finalSubstitutions = array();
			foreach ( $substitutions as $k => $v )
			{
				$language->parseEmail( $v );
				$finalSubstitutions["*|{$k}_plain|*"] = $v;
				$finalSubstitutions["*|{$k}|*"] = htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8' );
			} 
			
			$request['personalizations'][] = array(
				'to'			=> array( array( 'email' => $email ) ),
				'substitutions'	=> $finalSubstitutions
			);
		}
				
		/* Add additional headers */
		$request = $this->_modifyRequestDataWithHeaders( $request, $additionalHeaders );

		/* Log emails sent */
		$this->_trackStatistics( count( $addresses ) );
		
		/* Send */
		try
		{
			$response = $this->_api( 'mail/send', $request );
		}
		catch(Exception $e )
		{
			Db::i()->insert( 'core_mail_error_logs', array(
				'mlog_date'			=> time(),
				'mlog_to'			=> json_encode( $addresses ),
				'mlog_from'			=> $fromEmail ?: Settings::i()->email_out,
				'mlog_subject'		=> $subject,
				'mlog_content'		=> $htmlContent,
				'mlog_resend_data'	=> NULL,
				'mlog_msg'			=> json_encode( array( 'message' => $e->getMessage() ) ),
				'mlog_smtp_log'		=> NULL
			) );

			return 0;
		}

		$errorcount = 0;
		if ( isset( $response['errors'] ) )
		{
			$errorcount = count( $response['errors'] );
			Db::i()->insert( 'core_mail_error_logs', array(
				'mlog_date'			=> time(),
				'mlog_to'			=> json_encode( $addresses ),
				'mlog_from'			=> $fromEmail ?: Settings::i()->email_out,
				'mlog_subject'		=> $subject,
				'mlog_content'		=> $htmlContent,
				'mlog_resend_data'	=> NULL,
				'mlog_msg'			=> json_encode( array( 'message' => $response['errors'] ) ),
				'mlog_smtp_log'		=> NULL
			) );
		}

		$successCount = ( count( $recipients ) - $errorcount );

		/* Update ad impression count */
		Advertisement::updateEmailImpressions( $successCount );

		return $successCount;
	}
	
	/**
	 * Create a request
	 *
	 * @param mixed $fromEmail			The email address to send from. If NULL, default setting is used. NOTE: This should always be a site-controlled domin. Some services like Sparkpost require the domain to be validated.
	 * @param mixed $fromName			The name the email should appear from. If NULL, default setting is used
	 * @return	array
	 */
	protected function _initRequest( mixed $fromEmail = NULL, mixed $fromName = NULL ): array
	{
		$request = array(
			'personalizations'	=> array(),
			'from'				=> array(
				'email'				=> $fromEmail ?: Settings::i()->email_out,
				'name'				=> $fromName ?: Settings::i()->board_name
			),
			'tracking_settings'	=> array(
				'click_tracking'	=> array(
					'enable'			=> (bool) Settings::i()->sendgrid_click_tracking,
					'enable_text'		=> (bool) Settings::i()->sendgrid_click_tracking,
				),
				'open_tracking'	=> array(
					'enable'			=> (bool) Settings::i()->sendgrid_click_tracking,
				)
			)
		);
				
		if ( Settings::i()->sendgrid_ip_pool )
		{
			$request['ip_pool_name'] = Settings::i()->sendgrid_ip_pool;
		}
		
		return $request;
	}
	
	/**
	 * Modify the request data that will be sent to the SparkPost API with header data
	 * 
	 * @param array $request			SparkPost API request data
	 * @param array $additionalHeaders	Additional headers to send
	 * @param array $allowedTags		The tags that we want to parse
	 * @return	array
	 */
	protected function _modifyRequestDataWithHeaders( array $request, array $additionalHeaders = array(), array $allowedTags = array() ): array
	{
		/* Do we have a Reply-To? */
		if ( isset( $additionalHeaders['Reply-To'] ) )
		{
			if ( preg_match( '/(.*)\s?<(.*)>$/', $additionalHeaders['Reply-To'], $matches ) )
			{
				$email = $matches[2];

				$request['reply_to'] = array( 'email' => $matches[2] );
			
				if ( $matches[1] )
				{		
					if ( preg_match( '/^=\?UTF-8\?B\?(.+?)\?=$/i', trim( $matches[1] ), $_matches ) )
					{
						$request['reply_to']['name'] = base64_decode( $_matches[1] );
					}
				}
			}

			unset( $additionalHeaders['Reply-To'] );
		}
		
		/* Any other headers? */
		unset( $additionalHeaders['x-sg-id'] );
		unset( $additionalHeaders['x-sg-eid'] );
		unset( $additionalHeaders['received'] );
		unset( $additionalHeaders['dkim-signature'] );
		unset( $additionalHeaders['Content-Type'] );
		unset( $additionalHeaders['Content-Transfer-Encoding'] );
		unset( $additionalHeaders['Subject'] );
		unset( $additionalHeaders['From'] );
		unset( $additionalHeaders['To'] );
		unset( $additionalHeaders['CC'] );
		unset( $additionalHeaders['BCC'] );
		if ( count( $additionalHeaders ) )
		{
			$request['headers'] = $additionalHeaders;
		}
				
		/* Return */
		return $request;
	}
	
	/**
	 * Make API call
	 *
	 * @param string $method	Method
	 * @param array|null $args	Arguments
	 * @return    array|null
	 *@throws  Exception   Indicates an invalid JSON response or HTTP error
	 */
	protected function _api(string $method, array $args=NULL ): ?array
	{
		$request = Url::external( 'https://api.sendgrid.com/v3/' . $method )
			->request( LONG_REQUEST_TIMEOUT )
			->setHeaders( array( 'Content-Type' => 'application/json', 'Authorization' => "Bearer {$this->apiKey}" ) );

		try
		{
			if ( $args )
			{
				$response = $request->post( json_encode( $args ) );
			}
			else
			{
				$response = $request->get();
			}

			
			if ( $response->content )
			{
				$response = $response->decodeJson();
			}
			else
			{
				$response = null;
			}
			
			return $response;
		}
		catch ( Exception | RuntimeException $e )
		{
			throw new Exception($e->getMessage(), $e->getCode() );
		}
	}

	/**
	 * Get API key scopes
	 *
	 * @return array|null
	 */
	public function scopes(): ?array
	{
		return $this->_api( 'scopes' );
	}
	
}