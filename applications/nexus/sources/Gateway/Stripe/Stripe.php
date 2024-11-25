<?php

/**
 * @brief		Stripe Gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		13 Mar 2014
 */

namespace IPS\nexus\Gateway;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use InvalidArgumentException;
use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Log;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Customer\CreditCard as CustomerCard;
use IPS\nexus\Fraud\MaxMind\Request;
use IPS\nexus\Gateway;
use IPS\nexus\Gateway\Stripe\CreditCard;
use IPS\nexus\Gateway\Stripe\Exception;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use RuntimeException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_numeric;
use function is_string;
use function strlen;
use const IPS\CIC;
use const IPS\LONG_REQUEST_TIMEOUT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stripe Gateway
 */
class Stripe extends Gateway
{
	/* !Features */
	
	const SUPPORTS_REFUNDS = TRUE;
	const SUPPORTS_PARTIAL_REFUNDS = TRUE;
	const SUPPORTS_AUTOPAY =  TRUE;

	/**
	 * Check the gateway can process this...
	 *
	 * @param    $amount            Money        The amount
	 * @param    $billingAddress    GeoLocation|NULL    The billing address, which may be NULL if one if not provided
	 * @param    $customer        Customer|null        The customer (Default NULL value is for backwards compatibility - it should always be provided.)
	 * @param array $recurrings Details about recurring costs
	 * @return    bool
	 */
	public function checkValidity(Money $amount, ?GeoLocation $billingAddress = NULL, ?Customer $customer = NULL, array $recurrings = array() ) : bool
	{
		$settings = json_decode( $this->settings, TRUE );

		/* Stripe has a minimum transaction fee. This is based on the businesses currency, but as we don't know what the transaction rate is
			we'll do this check only in the transactions we know - anything else will be rejected when the user tries to pay */
		switch ( $amount->currency )
		{
			case 'AUD':
			case 'BRL':
			case 'CAD':
			case 'CHF':
			case 'EUR':
			case 'INR':
			case 'JPY':
			case 'NZD':
			case 'SGD':
			case 'USD':
				if ( static::_amountAsCents( $amount ) < 50 )
				{
					return FALSE;
				}
				break;
			case 'AED':
			case 'PLN':
			case 'RON':
				if ( static::_amountAsCents( $amount ) < 200 )
				{
					return FALSE;
				}
				break;
			case 'BGN':
				if ( static::_amountAsCents( $amount ) < 100 )
				{
					return FALSE;
				}
				break;
			case 'CZK':
				if ( static::_amountAsCents( $amount ) < 1500 )
				{
					return FALSE;
				}
				break;
			case 'DKK':
				if ( static::_amountAsCents( $amount ) < 250 )
				{
					return FALSE;
				}
				break;
			case 'GBP':
				if ( static::_amountAsCents( $amount ) < 30 )
				{
					return FALSE;
				}
				break;
			case 'HKD':
				if ( static::_amountAsCents( $amount ) < 400 )
				{
					return FALSE;
				}
				break;
			case 'MXN':
				if ( static::_amountAsCents( $amount ) < 1000 )
				{
					return FALSE;
				}
				break;
			case 'MYR':
				if ( static::_amountAsCents( $amount ) < 2 )
				{
					return FALSE;
				}
				break;
			case 'NOK':
			case 'SEK':
				if ( static::_amountAsCents( $amount ) < 300 )
				{
					return FALSE;
				}
				break;
		}
		
		/* And the maximum is based on the size of the amount */
		if ( strlen( static::_amountAsCents( $amount ) ) > 8 )
		{
			return FALSE;
		}
				
		/* European methods are EUR only */
		if ( isset( $settings['type'] ) and in_array( $settings['type'], array( 'bancontact', 'giropay', 'ideal', 'sofort' ) ) )
		{
			if ( $amount->currency !== 'EUR' )
			{
				return FALSE;
			}
			
			/* Sofort is only for Austria, Belgium, Germany, Netherlands, Spain */
			if ( $settings['type'] == 'sofort' and ( !$billingAddress or !in_array( $billingAddress->country, array( 'AT', 'BE', 'DE', 'NL', 'ES' ) ) ) )
			{
				return FALSE;
			}
		}

		/* Otherwise, see https://stripe.com/docs/currencies (it makes it look like what currencies are supported depends on the business country, but at time of writing, all countries have the same list */
		else
		{
			if( !in_array( $amount->currency, array(
				'USD', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BIF', 'BMD', 'BND', 'BOB', 'BRL',
				'BSD', 'BWP', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD',
				'FKP', 'GBP', 'GEL', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JMD', 'JPY', 'KES',
				'KGS', 'KHR', 'KMF', 'KRW', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MUR',
				'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON',
				'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'STD', 'SZL', 'THB', 'TJS', 'TOP', 'TRY', 'TTD', 'TWD',
				'TZS', 'UAH', 'UGX', 'UYU', 'UZS', 'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW'
			) ) ) {
				return false;
			}
		}
		
		/* Check if Payment Request API is supported  */
		if ( isset( $settings['type'] ) and $settings['type'] === 'native' )
		{
			if ( isset( \IPS\Request::i()->cookie['PaymentRequestAPI'] ) and !\IPS\Request::i()->cookie['PaymentRequestAPI'] )
			{
				return FALSE;
			}
			if ( $billingAddress and !in_array( $billingAddress->country, array( 'AT', 'AU', 'BE', 'BR', 'CA', 'CH', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'HK', 'IE', 'IN', 'IT', 'JP', 'LT', 'LU', 'LV', 'MX', 'NL', 'NZ', 'NO', 'PH', 'PL', 'PT', 'RO', 'SE', 'SG', 'SK', 'US' ) ) )
			{
				return FALSE;
			}
		}
		
		/* Still here? Do normal checks */
		return parent::checkValidity( $amount, $billingAddress, $customer, $recurrings );
	}
	
	/**
	 * Can store cards?
	 *
	 * @param bool $adminCreatableOnly	If TRUE, will only return gateways where the admin (opposed to the user) can create a new option
	 * @return    bool
	 */
	public function canStoreCards(bool $adminCreatableOnly = FALSE ): bool
	{
		$settings = json_decode( $this->settings, TRUE );
		return ( ( !isset( $settings['type'] ) or $settings['type'] == 'card' ) and $settings['cards'] );
	}
	
	/**
	 * Admin can manually charge using this gateway?
	 *
	 * @param	Customer	$customer	The customer we're wanting to charge
	 * @return    bool
	 */
	public function canAdminCharge( Customer $customer ): bool
	{
		return TRUE;
	}
	
	/* !Payment Gateway */
	
	/**
	 * Should the submit button show when this payment method is shown?
	 *
	 * @return    bool
	 */
	public function showSubmitButton(): bool
	{
		$settings = json_decode( $this->settings, TRUE );
		return !in_array( $settings['type'], array( 'amex', 'native' ) );
	}
	
	/**
	 * Payment Screen Fields
	 *
	 * @param	Invoice		$invoice	Invoice
	 * @param	Money		$amount		The amount to pay now
	 * @param Customer|null $member		The member the payment screen is for (if in the ACP charging to a member's card) or NULL for currently logged in member
	 * @param array $recurrings	Details about recurring costs
	 * @param string $type		'checkout' means the cusotmer is doing this on the normal checkout screen, 'admin' means the admin is doing this in the ACP, 'card' means the user is just adding a card
	 * @return    array
	 */
	public function paymentScreen(Invoice $invoice, Money $amount, ?Customer $member = NULL, array $recurrings = array(), string $type = 'checkout' ): array
	{
		$settings = json_decode( $this->settings, TRUE );
		if ( !isset( $settings['type'] ) or $settings['type'] === 'card' )
		{			
			if ( isset( \IPS\Request::i()->createPaymentIntent ) )
			{
				$member = $member ?: Customer::loggedIn();
				
				try
				{			
					$paymentMethod = \IPS\Request::i()->createPaymentIntent;
					$savePaymentMethod = ( isset( \IPS\Request::i()->savePaymentMethod ) and \IPS\Request::i()->savePaymentMethod and \IPS\Request::i()->savePaymentMethod != 'false' );
					
					if ( is_numeric( $paymentMethod ) ) // That means it's a saved card
					{
						try
						{
							$card = Customer\CreditCard::load( $paymentMethod );
							if ( $card->member->member_id !== $member->member_id )
							{
								throw new DomainException;
							}
							
							$paymentMethod = $card->data;
							$savePaymentMethod = FALSE;
						}
						catch ( \Exception ) { }
					}
					
					$response = $this->_createPaymentIntent( $invoice, $amount, $member, $paymentMethod, $savePaymentMethod, $type );
					Output::i()->json( array( 'success' => true, 'type' => 'payment', 'response' => $response ) );
				}
				catch( Exception $e )
				{
					Output::i()->json( array( 'success' => false, 'message' => $e->getMessage() ), 500 );
				}
			}
			
			
			$supportedCards = array( \IPS\nexus\CreditCard::TYPE_VISA, \IPS\nexus\CreditCard::TYPE_MASTERCARD );
			if ( ( $settings['country'] == 'IN' and $invoice->currency == 'USD' ) or ( $settings['country'] == 'MX' and $invoice->currency == 'MXN' ) or ( !in_array( $settings['country'], [ 'IN', 'MX' ] ) and !in_array( $invoice->currency, array( 'AFN', 'AOA', 'ARS', 'BOB', 'BRL', 'CLP', 'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'FKP', 'GNF', 'GTQ', 'HNL', 'HUF', 'INR', 'LAK', 'MUR', 'NIO', 'PAB', 'PEN', 'PYG', 'SHP', 'SRD', 'STD', 'UYU', 'XOF', 'XPF' ) ) ) )
			{
				$supportedCards[] = \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS;
			}
			if ( in_array( $settings['country'], [ 'AU', 'CA', 'JA', 'NZ', 'US' ] ) and $invoice->currency == 'USD' )
			{
				$supportedCards[] = \IPS\nexus\CreditCard::TYPE_DISCOVER;
				$supportedCards[] = \IPS\nexus\CreditCard::TYPE_DINERS_CLUB;
				$supportedCards[] = \IPS\nexus\CreditCard::TYPE_JCB;
			}
			
			$setupIntent = NULL;
			if ( $type === 'card' )
			{
				try
				{
					$setupIntent = $this->api( 'setup_intents', array( 'usage' => 'off_session' ) );
				}
				catch ( Exception $e )
				{
					Log::log( $e, 'checkout' );
					return array();
				}
			}

			return array( 'card' => new \IPS\nexus\Form\CreditCard( $this->id . '_card', NULL, FALSE, array(
				'types' 		=> $supportedCards,
				'attr'			=> array(
					'data-controller'	=> 'nexus.global.gateways.stripe',
					'data-id'			=> $this->id,
					'class'				=> 'ipsHide',
					'data-key'			=> $settings['publishable_key'],
					'data-name'			=> $member ? $member->cm_name : $invoice->member->cm_name,
					'data-address1'		=> ( $invoice->billaddress and isset( $invoice->billaddress->addressLines[0] ) ) ? $invoice->billaddress->addressLines[0] : NULL,
					'data-address2'		=> ( $invoice->billaddress and isset( $invoice->billaddress->addressLines[1] ) ) ? $invoice->billaddress->addressLines[1] : NULL,
					'data-city'			=> $invoice->billaddress?->city,
					'data-state'		=> $invoice->billaddress?->region,
					'data-zip'			=> $invoice->billaddress?->postalCode,
					'data-country'		=> $invoice->billaddress?->country,
					'data-email'		=> $member ? $member->email : $invoice->member->email,
					'data-phone'		=> isset( $member->cm_phone ) ? $member->cm_phone : NULL,
					'data-amount'		=> static::_amountAsCents( $amount ),
					'data-currency'		=> $amount->currency,
					'data-setupIntent'	=> $setupIntent ? $setupIntent['id'] : NULL,
					'data-setupSecret'	=> $setupIntent ? $setupIntent['client_secret'] : NULL,
				),
				'jsRequired'	=> TRUE,
				'names'			=> FALSE,
				'dummy'			=> TRUE,
				'save'			=> ( $settings['cards'] ) ? $this : NULL,
				'member'		=> $member,
			) ) );
		}
		elseif ( $settings['type'] === 'sofort' and \IPS\NEXUS_TEST_GATEWAYS )
		{
			return array(
				new Radio( 'stripe_debug_action', 'succeeding_charge', FALSE, array( 'options' => array(
					'succeeding_charge'	=> 'stripe_debug_succeeding_charge',
					'pending_charge'	=> 'stripe_debug_pending_charge',
					'failing_charge'	=> 'stripe_debug_failing_charge',
				) ) )
			);
		}
		elseif ( in_array( $settings['type'], array( 'alipay', 'bancontact', 'giropay', 'ideal', 'sofort' ) ) )
		{
			return array();
		}
		else
		{
			return array( 'card' => new Custom( $this->id . '_card', NULL, FALSE, array(
				'rowHtml'	=> function( $field ) use ( $settings, $invoice, $amount ) {
					return Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->paymentRequestApi( $field, $this, $settings['publishable_key'], $invoice->billaddress ? $invoice->billaddress->country : 'US', $invoice, mb_strtolower( $amount->currency ), static::_amountAsCents( $amount ), $amount->amount );
				}
			), NULL, NULL, NULL, $this->id . '_card' ) );
		}
	}
	
	/**
	 * Create a payment intent
	 *
	 * @param	Invoice						$invoice				Invoice
	 * @param	Money							$amount					The amount to pay now
	 * @param	Customer						$member					The customer making the payment, or, if in the ACP charging to a member's card, the customer that the payment is for
	 * @param	string									$paymentMethod			Stripe payment method ID ("pm_....") - can be for a new card or a card already on file
	 * @param	bool									$savePaymentMethod		Save this payment method for future (off-session) payments?
	 * @param	string									$type					'checkout' means the cusotmer is doing this on the normal checkout screen, 'admin' means the admin is doing this in the ACP, 'card' means the user is just adding a card
	 * @return	array
	 * @throws	
	 */
	protected function _createPaymentIntent( Invoice $invoice, Money $amount, Customer $member, string $paymentMethod, bool $savePaymentMethod = FALSE, string $type = 'checkout' ) : array
	{	
		$data = array(
			'amount'				=> static::_amountAsCents( $amount ),
			'currency'				=> $amount->currency,
			'capture_method'		=> 'manual',
			'confirm'				=> 'true',
			'confirmation_method'	=> 'manual',
			'description'			=> $invoice->title,
			'metadata'				=> array(
				"Invoice ID"			=> $invoice->id,
				"Customer ID"			=> $member->member_id,
				"Customer Email"		=> $member->email,
				"IP Address"			=> \IPS\Request::i()->ipAddress(),
				"Payment Method ID"		=> $this->id
			),
			'payment_method' => $paymentMethod
		);

		if ( $type === 'admin' )
		{
			$data['metadata']["Admin"] = Member::loggedIn()->member_id;
		}
		$profiles = $member->cm_profiles;
		if ( isset( $profiles[ $this->id ] ) )
		{
			$data['customer'] = $profiles[ $this->id ];
		}
		
		/* We need the customer profile, so if we don't have it - try to fetch it now */
		if ( !isset( $data['customer'] ) )
		{
			$response = $this->api( 'payment_methods/' . $paymentMethod, array(), 'GET' );

			if( isset( $response['customer'] ) )
			{
				$data['customer'] = $response['customer'];
				$profiles[ $this->id ] = $response['customer'];

				if( $member->member_id )
				{
					$member->cm_profiles = $profiles;
					$member->save();
				}
				else
				{
					$guestData = $invoice->guest_data;
					$guestData['member']['cm_profiles'] = $profiles;
					$guestData['cards'][] = array(
						'card_method'	=> $this->id,
						'card_data'		=> $paymentMethod
					);
					$invoice->guest_data = $guestData;
					$invoice->save();
				}
			}
		}

		if ( $savePaymentMethod )
		{
			$data['save_payment_method'] = 'true';
			$data['setup_future_usage'] = 'off_session';
		
			if ( !isset( $data['customer'] ) )
			{
				if ( $member->member_id )
				{
					$response = $this->api( 'customers', array(
						'name'			=> $member->cm_name,
						'email'			=> $member->email,
						'metadata' 		=> array(
							"Customer ID" => $member->member_id
						)
					) );
					$profiles[ $this->id ] = $response['id'];
					
					$member->cm_profiles = $profiles;
					$member->save();
				}
				else
				{
					$response = $this->api( 'customers', array(
						'name'			=> $invoice->member->cm_name,
						'email'			=> $invoice->member->email,
					) );
					$profiles[ $this->id ] = $response['id'];
					
					$guestData = $invoice->guest_data;
					$guestData['member']['cm_profiles'] = $profiles;
					$guestData['cards'][] = array(
						'card_method'	=> $this->id,
						'card_data'		=> $paymentMethod
					);
					$invoice->guest_data = $guestData;
					$invoice->save();
				}
				
				$data['customer'] = $profiles[ $this->id ];
			}
		}
		
		$response = $this->api( 'payment_intents', $data );
		
		if ( $savePaymentMethod and $member->member_id )
		{
			Db::i()->insert( 'nexus_customer_cards', array(
				'card_member'	=> $member->member_id,
				'card_method'	=> $this->id,
				'card_data'		=> $paymentMethod
			) );
		}
		
		return $response;
	}

	/**
	 * Create a payment intent for non-card payments
	 *
	 * @param Transaction $transaction
	 * @param string|null $paymentMethod Stripe payment method ID ("pm_....") - can be for a new card or a card already on file
	 * @param string $type 'checkout' means the cusotmer is doing this on the normal checkout screen, 'admin' means the admin is doing this in the ACP, 'card' means the user is just adding a card
	 * @return    array
	 */
	protected function _createNonCardPaymentIntent( Transaction $transaction, ?string $paymentMethod, string $type = 'checkout' ) : array
	{
		$invoice = $transaction->invoice;
		$data = array(
			'amount'				=> static::_amountAsCents( $transaction->amount ),
			'currency'				=> $transaction->amount->currency,
			'capture_method'		=> 'automatic',
			'confirm'				=> 'true',
			'confirmation_method'	=> 'automatic',
			'description'			=> $invoice->title,
			'metadata'				=> array(
				"Invoice ID"			=> $invoice->id,
				"Customer ID"			=> $transaction->member->member_id,
				"Customer Email"		=> $transaction->member->email,
				"IP Address"			=> \IPS\Request::i()->ipAddress(),
				"Payment Method ID"		=> $this->id,
				"Transaction ID"		=> $transaction->id
			),
			'payment_method' => $paymentMethod,
			'payment_method_types' => [ $paymentMethod ],
			'return_url'	=> Settings::i()->base_url . 'applications/nexus/interface/gateways/stripe-redirector.php?nexusTransactionId=' . $transaction->id
		);

		if ( $type === 'admin' )
		{
			$data['metadata']["Admin"] = Member::loggedIn()->member_id;
		}
		if ( $invoice->shipaddress )
		{
			$data['shipping'] = array(
				'address'	=> array(
					'city'			=> $invoice->shipaddress->city,
					'country'		=> $invoice->shipaddress->country,
					'line1'			=> $invoice->shipaddress->addressLines[0] ?? NULL,
					'line2'			=> $invoice->shipaddress->addressLines[1] ?? NULL,
					'postal_code'	=> $invoice->shipaddress->postalCode,
					'state'			=> $invoice->shipaddress->region,
				),
				'name'		=> $invoice->member->cm_name,
				'phone'		=> $invoice->member->cm_phone ?? NULL
			);
		}

		/* Create a payment method */
		$paymentMethodData = [
			'type' => $paymentMethod,
			'billing_details' => [
				'address' => [
					'city' => $invoice->billaddress->city,
					'country' => $invoice->billaddress->country,
					'line1' => $invoice->billaddress->addressLines[0] ?? NULL,
					'line2' => $invoice->billaddress->addressLines[1] ?? NULL,
					'postal_code' => $invoice->billaddress->postalCode,
					'state' => $invoice->billaddress->region
				],
				'email' => ( $transaction->member->member_id ? $transaction->member->email : $invoice->guest_data['member']['email'] ),
				'name' => ( $transaction->member->member_id ? $transaction->member->cm_name : $invoice->guest_data['member']['cm_first_name'] . ' '. $invoice->guest_data['member']['cm_last_name'] ),
				'phone' => ( $transaction->member->member_id ? $transaction->member->cm_phone : $invoice->guest_data['member']['cm_phone'] )
			]
		];

		if( $paymentMethod == 'sofort' )
		{
			$paymentMethodData['sofort'] = array( 'country' => $transaction->invoice->billaddress->country );
		}

		$response = $this->api( 'payment_methods', $paymentMethodData );

		if( isset( $response['id'] ) )
		{
			$data['payment_method'] = $response['id'];
			$data['customer'] = $response['customer'];
		}

		return $this->api( 'payment_intents', $data );
	}

	/**
	 * Authorize
	 *
	 * @param Transaction $transaction Transaction
	 * @param array|Customer\CreditCard $values Values from form OR a stored card object if this gateway supports them
	 * @param Request|NULL $maxMind *If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made
	 * @param array $recurrings Details about recurring costs
	 * @param string|NULL $source 'checkout' if the customer is doing this at a normal checkout, 'renewal' is an automatically generated renewal invoice, 'manual' is admin manually charging. NULL is unknown
	 * @return    array|DateTime|NULL                        Auth is valid until or NULL to indicate auth is good forever
	 * @throws    LogicException                            Message will be displayed to user
	 */
	public function auth(Transaction $transaction, array|Customer\CreditCard $values, ?Request $maxMind = NULL, array $recurrings = array(), ?string $source = NULL ): DateTime|array|null
	{
		$settings = json_decode( $this->settings, TRUE );
		
		/* Do we need to redirect? */
		if( in_array( $settings['type'], [ 'alipay', 'bancontact', 'giropay', 'ideal', 'sofort' ] ) and !isset( $values[ $this->id . '_card' ] ) )
		{	
			/* We need a transaction ID */
			$transaction->save();
			/* Create a payment intent */
			$response = $this->_createNonCardPaymentIntent( $transaction, $settings['type'], 'checkout' );
			if( $response['status'] == 'requires_action' )
			{
				$transaction->gw_id = $response['id'];
				$transaction->status = Transaction::STATUS_GATEWAY_PENDING;
				$transaction->save();

				$actionType = $response['next_action']['type'];
				if ( isset( $response['next_action'][$actionType]['url'] ) )
				{
					Output::i()->redirect( Url::createFromString( $response['next_action'][$actionType]['url'] ) );
				}
			}

			/* still here? There was an error */
			throw new RuntimeException;
		}
				
		/* Set MaxMind type */
		if ( $maxMind )
		{
			if ( !isset( $settings['type'] ) or $settings['type'] === 'card' )
			{
				$maxMind->setTransactionType('creditcard');
			}
		}		
		/* Set card */
		$card = $values[ $this->id . '_card' ];
		
		/* If we got a payment intent ID, we just need to confirm it and then wait for the webhooks */
		if ( $card and !is_string( $card ) and $card->token and mb_substr( $card->token, 0, 3 ) === 'pi_' )
		{
			/* Confirm it */
			$response = $this->api( "payment_intents/{$card->token}" );
			if ( $response['status'] === 'requires_confirmation' )
			{
				$response = $this->api( "payment_intents/{$card->token}/confirm" );
			}
						
			/* Stripe webhooks will take it from there, so show the "Processing your payment" multiredirector */
			return isset( $values['previousTransactions'] ) ? explode( ',', $values['previousTransactions'] ) : array();
		}
		
		$transaction->save();
		
		/* Build data */
		$data = array(
			'amount'		=> static::_amountAsCents( $transaction->amount ),
			'currency'		=> $transaction->amount->currency,
			'capture'		=> 'false',
			'description'	=> $transaction->invoice->title,
			'metadata'		=> array(
				"Transaction ID"	=> $transaction->id,
				"Invoice ID"		=> $transaction->invoice->id,
				"Customer ID"		=> $transaction->member->member_id,
				"Customer Email"	=> $transaction->member->email,
			),			
		);

		/* Source-based */
		if ( is_string( $card ) )
		{			
			$data['source'] = $card;
			unset( $data['capture'] );
		}
		
		/* Stored Card (for recurring payments) */
		elseif ( $card instanceof CreditCard )
		{		
			$profiles = $card->member->cm_profiles;
			$data['customer'] = $profiles[ $this->id ];
			
			if ( mb_substr( $values[ $this->id . '_card' ]->data, 0, 3 ) === 'pm_' )
			{
				// If we are dealing with a payment method, we have to create a payment intent rather than directly creating a charge
				$paymentIntentData = $data;
				$paymentIntentData['capture_method'] = 'manual';
				$paymentIntentData['confirm'] = 'true';
				$paymentIntentData['confirmation_method'] = 'manual';
				$paymentIntentData['payment_method'] = $values[ $this->id . '_card' ]->data;
				$paymentIntentData['off_session'] = ( $source === 'renewal' ? 'recurring' : 'one_off' );
				unset( $paymentIntentData['capture'] );
				
				$paymentIntent = $this->api( 'payment_intents', $paymentIntentData );
				if ( $paymentIntent['status'] === 'requires_capture' )
				{
					$transaction->gw_id = $paymentIntent['id'];
					return DateTime::ts( $paymentIntent['created'] )->add( new DateInterval( 'P7D' ) );
				}
				else
				{
					// It could just be that the card requires authentication, but throwing this exception will automatically try any other payment methods
					// on file and then ultimately send the customer an invoice if none succeeded, so we can just allow that to happen.
					throw new DomainException("Unexpected Payment Intent status: {$paymentIntent['status']}");
				}
			}
			elseif ( mb_substr( $values[ $this->id . '_card' ]->data, 0, 4 ) === 'src_' )
			{
				$data['capture'] = 'false'; // Don't auto-capture Apple/Google Pay.
				$data['source'] = $values[ $this->id . '_card' ]->data;
			}
			else
			{
				$data['card'] = $values[ $this->id . '_card' ]->data;
			}
		}
												
		/* Authorize */
		try
		{
			$response = $this->api( 'charges', $data );
		}
		catch ( Exception $e )
		{
			if ( isset( $e->details['charge'] ) and $e->details['charge'] )
			{
				$note = $e->getMessage();
				try
				{
					$response = $this->api( "charges/{$e->details['charge']}", NULL, 'get' );
					if ( isset( $response['outcome']['seller_message'] ) )
					{
						$note = $response['outcome']['seller_message'];
					}
				}
				catch ( \Exception ) { }
				
				$transaction->gw_id = $e->details['charge'];
				$transaction->status = $transaction::STATUS_REFUSED;
				$extra = $transaction->extra;
				$extra['history'][] = array( 's' => Transaction::STATUS_REFUSED, 'noteRaw' => $note );
				$transaction->extra = $extra;
				$transaction->save();
			}
			throw $e;
		}
		$transaction->gw_id = $response['id'];
		
		/* Return */
		if ( isset( $response['captured'] ) and $response['captured'] )
		{
			return NULL;
		}
		else
		{
			return DateTime::ts( $response['created'] )->add( new DateInterval( 'P7D' ) );
		}
	}
		
	/**
	 * Void
	 *
	 * @param	Transaction	$transaction	Transaction
	 * @return    mixed
	 * @throws	\Exception
	 */
	public function void( Transaction $transaction ): mixed
	{
		try
		{
			if ( mb_substr( $transaction->gw_id, 0, 3 ) === 'pi_' )
			{
				$this->api( "payment_intents/{$transaction->gw_id}/cancel" );
			}
			else
			{
				$response = $this->refund($transaction);
			}
		}
		catch ( \Exception ) { }

		return null;
	}
	
	/**
	 * Capture
	 *
	 * @param	Transaction	$transaction	Transaction
	 * @return    void
	 * @throws	LogicException
	 */
	public function capture( Transaction $transaction ): void
	{
		$settings = json_decode( $this->settings, TRUE );
		if ( isset( $settings['type'] ) and in_array( $settings['type'], array( 'alipay', 'bancontact', 'giropay', 'ideal', 'sofort' ) ) )
		{
			return;
		}
				
		try
		{
			if ( mb_substr( $transaction->gw_id, 0, 3 ) === 'pi_' )
			{
				$this->api( "payment_intents/{$transaction->gw_id}/capture" );
			}
			else
			{
				$this->api( "charges/{$transaction->gw_id}/capture" );
			}
		}
		catch( Exception $e )
		{
			/* If we have already captured/refunded the charge we don't need to let an exception bubble up */
			if( $e->details['code'] == 'charge_already_captured' or $e->details['code'] == 'charge_already_refunded' )
			{
				return;
			}
			/* PaymentIntent returns a different error for an already captured payment */
			elseif( $e->details['code'] == 'payment_intent_unexpected_state'
				AND mb_strpos( $e->details['message'], 'already been captured' ) !== FALSE
				AND $e->details['payment_intent']['amount_capturable'] === 0 )
			{
				return;
			}

			throw $e;
		}
	}

	/**
	 * Refund
	 *
	 * @param	Transaction	$transaction	Transaction to be refunded
	 * @param mixed|NULL $amount			Amount to refund (NULL for full amount - always in same currency as transaction)
	 * @param string|null $reason
	 * @return    mixed                                    Gateway reference ID for refund, if applicable
	 * @throws	\Exception
 	 */
	public function refund(Transaction $transaction, mixed $amount = NULL, ?string $reason = NULL): mixed
	{
		$data = NULL;
		if ( $amount )
		{
			$data['amount'] = static::_amountAscents( new Money( $amount, $transaction->currency ) );
		}
		if ( $reason )
		{
			$data['reason'] = $reason;
		}
		
		if ( mb_substr( $transaction->gw_id, 0, 3 ) === 'pi_' )
		{
			$response = $this->api( "charges?payment_intent={$transaction->gw_id}", NULL, 'get' );

			foreach ( $response['data'] as $charge )
			{
				if ( $charge['paid'] )
				{
					$this->api( "charges/{$charge['id']}/refund", $data );
					return null;
				}
			}
		}
		else
		{
			$this->api( "charges/{$transaction->gw_id}/refund", $data );
		}

		return null;
	}
	
	/**
	 * Refund Reasons that the gateway understands, if the gateway supports this
	 *
	 * @return    array
 	 */
	public static function refundReasons(): array
	{
		return array(
			'requested_by_customer'	=> 'refund_reason_requested_by_customer',
			'duplicate'				=> 'refund_reason_duplicate',
			'fraudulent'			=> 'refund_reason_stripe_fraudulent',
		);
	}
	
	/**
	 * Extra data to show on the ACP transaction page
	 *
	 * @param	Transaction	$transaction	Transaction
	 * @return    string
 	 */
	public function extraData( Transaction $transaction ): string
	{
		if ( !$transaction->gw_id )
		{
			return '';
		}
				
		try
		{
			if ( mb_substr( $transaction->gw_id, 0, 3 ) === 'pi_' )
			{
				$response = $this->api( "charges?payment_intent={$transaction->gw_id}", NULL, 'get' );

				foreach ( $response['data'] as $charge )
				{
					if ( $charge['paid'] )
					{
						$response = $charge;
						$response['source'] = $response['payment_method_details'];
						if( isset( $response['payment_method_details']['card'] ) )
						{
							foreach ( array( 'cvc_check', 'address_line1_check', 'address_postal_code_check' ) as $k )
							{
								$response['source']['card'][ $k ] = isset( $response['payment_method_details']['card']['checks'][ $k ] ) ? $response['payment_method_details']['card']['checks'][ $k ] : 'unavailable';
							}
						}
						break;
					}
				}
			}
			else
			{
				$response = $this->api( "charges/{$transaction->gw_id}", NULL, 'get' );
				
				if ( isset( $response['source']['three_d_secure'] ) )
				{
					try
					{
						$response2 = $this->api( "sources/{$response['source']['three_d_secure']['card']}", NULL, 'get' );
						$response['source']['card'] = $response2['card'];
					}
					catch ( \Exception )
					{
						return Theme::i()->getTemplate( 'transactions', 'nexus', 'admin' )->stripeData( $response, 'error' );
					}
				}
				elseif ( $response['source']['object'] === 'card' and isset( $response['card'] ) ) // For cards stored in older versions
				{
					$response['source']['card'] = $response['card'];
				}
			}
		}
		catch ( \Exception )
		{
			return Theme::i()->getTemplate( 'transactions', 'nexus', 'admin' )->stripeData( NULL, 'error' );
		}		
										
		return Theme::i()->getTemplate( 'transactions', 'nexus', 'admin' )->stripeData( $response );
	}
	
	/**
	 * Extra data to show on the ACP transaction page for a dispute
	 *
	 * @param	Transaction	$transaction	Transaction
	 * @param string|array $ref			Dispute log data
	 * @return    string
 	 */
	public function disputeData(Transaction $transaction, string|array $ref ): string
	{
		if ( is_array( $ref ) AND isset( $ref['ref'] ) )
		{
			try
			{
				$response = $this->api( "disputes/{$ref['ref']}", NULL, 'get' );
				return Theme::i()->getTemplate( 'transactions', 'nexus', 'admin' )->stripeDispute( $transaction, $ref, $response );
			}
			catch ( \Exception ){}
		}

		return Theme::i()->getTemplate( 'transactions', 'nexus', 'admin' )->stripeDispute( $transaction, $ref, NULL, TRUE );
	}
	
	/**
	 * Run any gateway-specific anti-fraud checks and return status for transaction
	 * This is only called if our local anti-fraud rules have not matched
	 *
	 * @param	Transaction	$transaction	Transaction
	 * @return    string
	 */
	public function fraudCheck( Transaction $transaction ): string
	{
		try
		{
			if ( mb_substr( $transaction->gw_id, 0, 3 ) === 'pi_' )
			{
				$response = $this->api( "charges?payment_intent={$transaction->gw_id}", NULL, 'get' );

				foreach ( $response['data'] as $charge )
				{
					if ( $charge['status'] === 'succeeded' )
					{
						if ( isset( $charge['outcome']['risk_level'] ) and $charge['outcome']['risk_level'] === 'elevated' )
						{
							return $transaction::STATUS_HELD;
						}
					}
				}
			}
			else
			{
				$response = $this->api( "charges/{$transaction->gw_id}", NULL, 'get' );
				if ( isset( $response['outcome']['risk_level'] ) and $response['outcome']['risk_level'] === 'elevated' )
				{
					return $transaction::STATUS_HELD;
				}
			}
			return $transaction::STATUS_PAID;
		}
		catch ( \Exception )
		{
			return $transaction::STATUS_PAID;
		}
	}
	
	/**
	 * URL to view transaction in gateway
	 *
	 * @param	Transaction	$transaction	Transaction
	 * @return    Url|NULL
 	 */
	public function gatewayUrl( Transaction $transaction ): Url|null
	{
		return Url::external( "https://dashboard.stripe.com/payments/{$transaction->gw_id}" );
	}
	
	/* !ACP Configuration */
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->addHeader('stripe_basic_settings');
		$form->add( new Translatable( 'paymethod_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_paymethod_{$this->id}" : NULL ) ) );
		$form->add( new Select( 'paymethod_countries', ( $this->id and $this->countries !== '*' ) ? explode( ',', $this->countries ) : '*', FALSE, array( 'options' => array_map( function( $val )
		{
			return "country-{$val}";
		}, array_combine( GeoLocation::$countries, GeoLocation::$countries ) ), 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'no_restriction' ) ) );
		$this->settings( $form );
	}
	
	/**
	 * Settings
	 *
	 * @param Form $form	The form
	 * @return    void
	 */
	public function settings( Form $form ): void
	{
		$settings = json_decode( $this->settings, TRUE );
		$form->addHeader('stripe_keys');
		$form->addMessage('stripe_keys_blurb');
		$form->add( new Text( 'stripe_secret_key', $settings ? $settings['secret_key'] : NULL, TRUE ) );
		$form->add( new Text( 'stripe_publishable_key', $settings ? $settings['publishable_key'] : NULL, TRUE ) );
		$form->addHeader('stripe_type_header');
		$form->add( new Radio( 'stripe_type', $settings['type'] ?? 'card', TRUE, array(
			'options'	=> array(
				'card'		=> 'stripe_type_card',
				'native' 	=> 'stripe_type_native',
				'alipay' 	=> 'stripe_type_alipay',
				'bancontact'=> 'stripe_type_bancontact',
				'giropay'	=> 'stripe_type_giropay',
				'ideal'		=> 'stripe_type_ideal',
				'sofort'	=> 'stripe_type_sofort',
			),
			'toggles'	=> array(
				'card'		=> array( 'stripe_cards' ),
				'native'	=> array( 'stripe_apple_pay_file')
			)
		) ) );
		$form->add( new YesNo( 'stripe_cards', $settings ? $settings['cards'] : TRUE, FALSE, array(), NULL, NULL, NULL, 'stripe_cards' ) );

		if ( CIC )
		{
			$form->add( new Upload( 'm_validationfile', $this->validationfile ? File::get( 'nexus_Gateways', $this->validationfile ) : '', FALSE, array(  'storageExtension' => 'nexus_Gateways' ), NULL, NULL, NULL, 'stripe_apple_pay_file' ) );
			Member::loggedIn()->language()->words[ 'm_validationfile']     = Member::loggedIn()->language()->addToStack('stripe_apple_verificationfile');
		}

		$form->addHeader('stripe_webhook');
		$form->addMessage('stripe_webhook_blurb');
		Member::loggedIn()->language()->words["stripe_webhook_blurb"] = sprintf( Member::loggedIn()->language()->get('stripe_webhook_blurb'), (string) Url::internal( 'applications/nexus/interface/gateways/stripe.php', 'interface' ) );
		$form->add( new Text( 'stripe_webhook_secret', $settings['webhook_secret'] ?? NULL, TRUE ) );
	}

	/**
	 * @brief Webhook events we need
	 */
	protected array $webhookEvents = array( 'source.chargeable', 'charge.succeeded', 'charge.failed', 'charge.dispute.created', 'charge.dispute.closed' );
	
	/**
	 * Test Settings
	 *
	 * @param array $settings	Settings
	 * @return    array
	 * @throws	InvalidArgumentException
	 */
	public function testSettings(array $settings=array() ): array
	{
		try
		{
			/* Get the country */
			$response = $this->api( 'account', NULL, 'get', $settings );
			$settings['country'] = $response['country'];
			
			/* Check we have a webhook. We can't actually verify if the secret we have is correct, but if we see a webhook with our URL we'll assume it is */
			$correctWebhookUrl = Settings::i()->base_url . 'applications/nexus/interface/gateways/stripe.php';
			$webhookId = NULL;
			$webhooks = $this->api( 'webhook_endpoints', NULL, 'get', $settings );
			foreach ( $webhooks['data'] as $webhook )
			{
				if ( $webhook['url'] === $correctWebhookUrl and $webhook['status'] === 'enabled' )
				{
					if( in_array( '*', $webhook['enabled_events'] ) OR count( array_intersect( $webhook['enabled_events'], $this->webhookEvents ) ) === count( $this->webhookEvents ) )
					{
						$webhookId = $webhook['id'];
						break;
					}
				}
			}
			if ( !$webhookId )
			{
				throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack( 'stripe_webhook_invalid', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $this->webhookEvents ) ) ) ) );
			}
			$settings['webhook_id'] = $webhookId;
															
			/* Return */
			AdminNotification::remove( 'nexus', 'ConfigurationError', "pm{$this->id}" );
			return $settings;
		}
		catch ( Exception $e )
		{
			throw new InvalidArgumentException( $e->details['message'] );
		}
	}
	
	/* !Utility Methods */
	
	/**
	 * Send API Request
	 *
	 * @param	string		$uri		The API to request (e.g. "charges")
	 * @param	array|null		$data		The data to send
	 * @param	string		$method		Method (get/post)
	 * @param	array|NULL	$settings	Settings (NULL for saved setting)
	 * @return	array
	 * @throws    Exception
	 */
	public function api( string $uri, ?array $data=NULL, string $method='post', ?array $settings = NULL ) : array
	{		
		$settings = $settings ?: json_decode( $this->settings, TRUE );
		
		$response = Url::external( 'https://api.stripe.com/v1/' . $uri )
			->request( LONG_REQUEST_TIMEOUT )
			->setHeaders( array( 'Stripe-Version' => '2022-11-15' ) )
			->forceTls()
			->login( $settings['secret_key'], '' )
			->$method( $data )
			->decodeJson();
			
		if ( isset( $response['error'] ) )
		{
			throw new Exception( $response['error'] );
		}
		
		return $response;
	}
	
	/**
	 * Convert amount into cents
	 *
	 * @param	Money	$amount		The amount
	 * @return	int
	 */
	protected static function _amountAsCents( Money $amount ) : int
	{
		if ( in_array( $amount->currency, array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VUV', 'XAF', 'XOF', 'XPF' ) ) )
		{
			return intval( (string) $amount->amount );
		}
		else
		{
			return intval( (string) $amount->amount->multiply( new Number( '100' ) ) );
		}
	}

	/**
	 * Automatically take payment
	 * Return an array of all transactions generated by this method
	 *
	 * @param Invoice $invoice
	 * @return Transaction[]
	 */
	public function autopay( Invoice $invoice ) : array
	{
		$where = [
			array( 'card_member=?', $invoice->member->member_id ),
			array( 'card_method=?', $this->_id )
		];

		$return = [];
		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_customer_cards', $where ), 'IPS\nexus\Customer\CreditCard' ) as $card )
		{
			/* @var CustomerCard $card */
			try
			{
				$return[] = $card->takePayment( $invoice );
			}
			catch( \Exception $e ){}
		}

		return $return;
	}
}