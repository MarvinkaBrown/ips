<?php
/**
 * @brief		PayPal Exception
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Mar 2014
 */

namespace IPS\nexus\Gateway\PayPal;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Http\Response;
use IPS\Log;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PayPal Exception
 */
class Exception extends DomainException
{
	/**
	 * @brief	Name
	 */
	protected string $name;
	
	/**
	 * @brief	Details
	 */
	protected array $details = array();
	
	/**
	 * @brief	Full Response
	 */
	protected string $fullResponse = '';
	
	/**
	 * Constructor
	 *
	 * @param	Response	$response	Response from PayPal
	 * @param	bool				$refund		request is for a refund?
	 */
	public function __construct( Response $response, bool $refund=FALSE )
	{
		$this->fullResponse = (string) $response;
		Log::debug( (string) $response, 'paypal' );
		
		$details = $response->decodeJson();
		$this->name = $details['name'];
		if ( isset( $details['details'] ) )
		{
			$this->details = $details['details'];
		}
		
		switch ( $this->name )
		{				
			case 'EXPIRED_CREDIT_CARD':
				$message = Member::loggedIn()->language()->get( 'card_expire_expired' );
				break;
							
			case 'CREDIT_CARD_REFUSED':
				$message = Member::loggedIn()->language()->get( 'card_refused' );
				break;
			
			case 'CREDIT_CARD_CVV_CHECK_FAILED':
				$message = Member::loggedIn()->language()->get( 'ccv_invalid' );
				break;
				
			case 'REFUND_EXCEEDED_TRANSACTION_AMOUNT':
			case 'FULL_REFUND_NOT_ALLOWED_AFTER_PARTIAL_REFUND':
				$message = Member::loggedIn()->language()->get( 'refund_amount_exceeds' );
				break;
				
			case 'REFUND_TIME_LIMIT_EXCEEDED':
				$message = Member::loggedIn()->language()->get( 'refund_time_limit' );
				break;
				
			case 'TRANSACTION_ALREADY_REFUNDED':
				$message = Member::loggedIn()->language()->get( 'refund_already_processed' );
				break;
			
			case 'ADDRESS_INVALID':
			case 'VALIDATION_ERROR':
				$message = Member::loggedIn()->language()->get( 'address_invalid' );
				break;
			
			case 'INSTRUMENT_DECLINED':
				$message = Member::loggedIn()->language()->get( 'payment_refused' );
				break;
			
			default:
				if( isset( $details[0] ) AND $details[0]['issue'] === 'INSTRUMENT_DECLINED' )
				{
					$message = Member::loggedIn()->language()->get( 'payment_refused' );
				}
				elseif ( $refund )
				{
					$message = Member::loggedIn()->language()->get( 'refund_failed' );
				}
				else
				{
					$message = Member::loggedIn()->language()->get( 'gateway_err' );
				}
				break;
		}
		
		return parent::__construct( $message, $response->httpResponseCode );
	}
	
	/**
	 * Get Name
	 *
	 * @return	string
	 */
	public function getName() : string
	{
		return $this->name;
	}
	
	/**
	 * Extra Log Data
	 *
	 * @return	string
	 */
	public function extraLogData() : string
	{
		return $this->fullResponse;
	}
}