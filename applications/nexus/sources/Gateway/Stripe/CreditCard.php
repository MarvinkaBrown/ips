<?php
/**
 * @brief		PayPal Stored Card
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus\Gateway\Stripe;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Db;
use IPS\Member;
use IPS\nexus\CreditCard as CreditCardClass;
use IPS\nexus\Customer\CreditCard as CustomerCardClass;
use UnexpectedValueException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stripe Stored Card
 */
class CreditCard extends CustomerCardClass
{
	/**
	 * @brief	Card
	 */
	protected ?CreditCardClass $_card = null;
	
	/**
	 * Get card
	 *
	 * @return	CreditCardClass
	 */
	public function get_card() : CreditCardClass
	{		
		if ( !$this->_card )
		{
			if ( mb_substr( $this->data, 0, 4 ) === 'src_' )
			{
				$response = $this->method->api( "sources/{$this->data}", NULL, 'get' );
				
				$this->_card = new CreditCardClass;
				$this->_card->lastFour = $response['card']['last4'];
				switch ( $response['card']['brand'] )
				{
					case 'Visa':
					case 'visa':
						$this->_card->type = CreditCardClass::TYPE_VISA;
						break;
					case 'American Express':
					case 'amex':
						$this->_card->type =  CreditCardClass::TYPE_AMERICAN_EXPRESS;
						break;
					case 'mastercard':
					case 'MasterCard':
						$this->_card->type = CreditCardClass::TYPE_MASTERCARD;
						break;
					case 'Discover':
					case 'discover':
						$this->_card->type =  CreditCardClass::TYPE_DISCOVER;
						break;
					case 'JCB':
					case 'jcb':
						$this->_card->type =  CreditCardClass::TYPE_JCB;
						break;
					case 'Diners Club':
					case 'diners':
						$this->_card->type =  CreditCardClass::TYPE_DINERS_CLUB;
						break;
				}
				$this->_card->expMonth = $response['card']['exp_month'];
				$this->_card->expYear = $response['card']['exp_year'];
			}
			elseif ( mb_substr( $this->data, 0, 3 ) === 'pm_' )
			{
				$response = $this->method->api( "payment_methods/{$this->data}", NULL, 'get' );
				
				$this->_card = new CreditCardClass;
				$this->_card->lastFour = $response['card']['last4'];
				switch ( $response['card']['brand'] )
				{
					case 'Visa':
					case 'visa':
						$this->_card->type = CreditCardClass::TYPE_VISA;
						break;
					case 'American Express':
					case 'amex':
						$this->_card->type =  CreditCardClass::TYPE_AMERICAN_EXPRESS;
						break;
					case 'mastercard':
					case 'MasterCard':
						$this->_card->type = CreditCardClass::TYPE_MASTERCARD;
						break;
					case 'Discover':
					case 'discover':
						$this->_card->type =  CreditCardClass::TYPE_DISCOVER;
						break;
					case 'JCB':
					case 'jcb':
						$this->_card->type =  CreditCardClass::TYPE_JCB;
						break;
					case 'Diners Club':
					case 'diners':
						$this->_card->type =  CreditCardClass::TYPE_DINERS_CLUB;
						break;
				}
				$this->_card->expMonth = $response['card']['exp_month'];
				$this->_card->expYear = $response['card']['exp_year'];
			}
			else
			{		
				$profiles = $this->member->cm_profiles;
				if ( !isset( $profiles[ $this->method->id ] ) )
				{
					throw new UnexpectedValueException;
				}
				
				$response = $this->method->api( "customers/{$profiles[ $this->method->id ]}/cards/{$this->data}", NULL, 'get' );
						
				$this->_card = new CreditCardClass;
				$this->_card->lastFour = $response['last4'];
				switch ( $response['type'] )
				{
					case 'Visa':
					case 'visa':
						$this->_card->type = CreditCardClass::TYPE_VISA;
						break;
					case 'American Express':
					case 'amex':
						$this->_card->type =  CreditCardClass::TYPE_AMERICAN_EXPRESS;
						break;
					case 'mastercard':
					case 'MasterCard':
						$this->_card->type = CreditCardClass::TYPE_MASTERCARD;
						break;
					case 'Discover':
					case 'discover':
						$this->_card->type =  CreditCardClass::TYPE_DISCOVER;
						break;
					case 'JCB':
					case 'jcb':
						$this->_card->type =  CreditCardClass::TYPE_JCB;
						break;
					case 'Diners Club':
					case 'diners':
						$this->_card->type =  CreditCardClass::TYPE_DINERS_CLUB;
						break;
				}
				$this->_card->expMonth = $response['exp_month'];
				$this->_card->expYear = $response['exp_year'];
			}
		}		
		return $this->_card;
	}
	
	/**
	 * Set card
	 *
	 * @param	CustomerCardClass	$card	The card
	 * @return	void
	 */
	public function set_card( CustomerCardClass $card ) : void
	{
		/* Create a customer object if we don't have one */
		$profiles = $this->member->cm_profiles;
		if ( !isset( $profiles[ $this->method->id ] ) )
		{
			$response = $this->method->api( 'customers', array(
				'description'	=> $this->member->member_id ? $this->member->cm_name : '',
				'email'			=> $this->member->email,
				'metadata' 		=> array(
					"Customer ID" => $this->member->member_id
				)
			) );
			$profiles[ $this->method->id ] = $response['id'];
			$this->member->cm_profiles = $profiles;
			$this->member->save();
		}
		
		/* Get the payment method */
		$response = $this->method->api( "payment_methods/{$card->token}", NULL, 'get' );
		
		/* Check it doesn't already exist */
		$otherCards = Db::i()->select( 'card_data', 'nexus_customer_cards', array( 'card_member=? AND card_method=?', $this->member->member_id, $this->method->id ) );
		if ( count( $otherCards ) )
		{
			if ( isset( $response['card']['fingerprint'] ) )
			{
				foreach ( $otherCards as $otherCardId )
				{
					try
					{
						if ( mb_substr( $otherCardId, 0, 4 ) === 'src_' )
						{
							$otherCardData = $this->method->api( "sources/{$otherCardId}", NULL, 'get' );
							$otherCardData = $otherCardData['card'];
						}
						elseif ( mb_substr( $otherCardId, 0, 3 ) === 'pm_' )
						{
							$otherCardData = $this->method->api( "payment_methods/{$otherCardId}", NULL, 'get' );
							$otherCardData = $otherCardData['card'];
						}
						else
						{
							$otherCardData = $this->method->api( "customers/{$profiles[ $this->method->id ]}/cards/{$otherCardId}", NULL, 'get' );
						}
						
						if ( isset( $otherCardData['fingerprint'] ) and $otherCardData['fingerprint'] === $response['card']['fingerprint'] )
						{
							throw new DomainException( Member::loggedIn()->language()->addToStack('card_is_duplicate') );
						}
					}
					catch (Exception ) { }
				}
			}
		}
		
		/* Save the card */
		$response = $this->method->api( "payment_methods/{$card->token}/attach", array(
			'customer' => $profiles[ $this->method->id ]
		) );
		$this->data = $card->token;
		$this->save();
	}
	
	/**
	 * Delete
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		$profiles = $this->member->cm_profiles;
		try
		{
			if ( mb_substr( $this->data, 0, 4 ) === 'src_' )
			{
				$this->method->api( "sources/{$this->data}", NULL, 'delete' );
			}
			elseif ( mb_substr( $this->data, 0, 3 ) === 'pm_' )
			{
				$this->method->api( "payment_methods/{$this->data}/detach", NULL, 'post' );
			}
			else
			{
				$this->method->api( "customers/{$profiles[ $this->method->id ]}/cards/{$this->data}", NULL, 'delete' );
			}
		}

		catch ( Exception ) { }
		
		parent::delete();
	}
}