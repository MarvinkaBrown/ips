<?php
/**
 * @brief		Customer Stored Card Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		12 Mar 2014
 */

namespace IPS\nexus\Customer;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Transaction;
use IPS\Patterns\ActiveRecord;
use IPS\Theme;
use function count;
use function defined;
use function is_array;
use function strlen;
use function substr;

/* @property Customer $member
 * @property \IPS\nexus\CreditCard $card
 */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Customer Stored Card Model
 */

/* @property Customer $member */
class CreditCard extends ActiveRecord
{	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'nexus_customer_cards';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'card_';
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		$gateway = Gateway::load( $data['card_method'] );
		$classname = Gateway::gateways()[ $gateway->gateway ] . '\\CreditCard';
		
		/* Initiate an object */
		$obj = new $classname;
		$obj->_new = FALSE;
		
		/* Import data */
		if ( static::$databasePrefix )
		{
			$databasePrefixLength = strlen( static::$databasePrefix );
		}
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
			{
				$k = substr( $k, $databasePrefixLength );
			}

			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();
		
		/* Init */
		if ( method_exists( $obj, 'init' ) )
		{
			$obj->init();
		}
				
		/* Return */
		return $obj;
	}
	
	/**
	 * Add Form
	 *
	 * @param	Customer	$customer	The customer
	 * @param	bool				$admin		Set to TRUE if the *admin* (opposed to the customer themselves) wants to create a new payment method
	 * @return	string|CreditCard
	 */
	public static function create( Customer $customer, bool $admin ) : string|CreditCard
	{
		$form = new Form;
		$showSubmitButton = FALSE;
		$hiddenValues = array();
		foreach ( static::createFormElements( $customer, $admin, $showSubmitButton, $hiddenValues ) as $element )
		{
			$form->add( $element );
		}
		foreach ( $hiddenValues as $k => $v )
		{
			$form->hiddenValues[ $k ] = $v;
		}
		
		if ( $values = $form->values() )
		{			
			try
			{
				return static::createFormSubmit( $values, $customer, $admin );
			}
			catch ( DomainException $e )
			{
				$form->error = $e->getMessage();
			}
		}
		
		return $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'nexus', 'global' ), 'addPaymentMethodForm' ), $showSubmitButton );
	}
	
	/**
	 * Get form elements for creating a new card
	 *
	 * @param	Customer	$customer			The customer
	 * @param	bool				$admin				Set to TRUE if the *admin* (opposed to the customer themselves) wants to create a new payment method
	 * @param	bool				$showSubmitButton	Will be set to a bool indicating if the submit button should be shown
	 * @param	array				$hiddenValues		Hidden values to add to the form
	 * @return	array
	 */
	public static function createFormElements( Customer $customer, bool $admin, bool &$showSubmitButton, array &$hiddenValues ) : array
	{
		$gateways = Gateway::cardStorageGateways( $admin );
		
		$elements = array();
		$paymentMethodsToggles = array();
		foreach ( $gateways as $gateway )
		{
			$invoice = new Invoice;
			$invoice->currency = $customer->defaultCurrency();
			foreach ( $gateway->paymentScreen( $invoice, $invoice->total, $customer, array(), 'card' ) as $element )
			{
				if ( !$element->htmlId )
				{
					$element->htmlId = $gateway->id . '-' . $element->name;
				}
				if ( isset( $element->options['save'] ) )
				{
					$element->options['save'] = NULL;
				}
				$elements[] = $element;
				$paymentMethodsToggles[ $gateway->id ][] = $element->htmlId;
			}
		}
		
		if ( count( $gateways ) > 1 )
		{
			$showSubmitButton = FALSE;
			$options = array();
			foreach ( $gateways as $gateway )
			{
				$options[ $gateway->id ] = $gateway->_title;
				if ( $gateway->showSubmitButton() )
				{
					$showSubmitButton = TRUE;
					$paymentMethodsToggles[ $gateway->id ][] = 'paymentMethodSubmit';
				}
			}
			
			$element = new Radio( 'payment_method', NULL, TRUE, array( 'options' => $options, 'toggles' => $paymentMethodsToggles ) );
			$element->label = Member::loggedIn()->language()->addToStack('card_gateway');
			
			array_unshift( $elements, $element );
		}
		else
		{
			foreach ( $gateways as $gateway )
			{
				$hiddenValues['payment_method'] = $gateway->id;
				$showSubmitButton = $gateway->showSubmitButton();
			}
		}
		
		return $elements;
	}
	
	/**
	 * Handle submission of the form for creating a new card
	 *
	 * @param	array				$values			Values from the form
	 * @param	Customer	$customer		The customer
	 * @param	bool				$admin			Set to TRUE if the *admin* (opposed to the customer themselves) wants to create a new payment method
	 * @param	?Invoice	$invoice		If customer is a guest, will sa`ve the guest data onto the provided invoice
	 * @return	CreditCard
	 * @throws	DomainException
	 */
	public static function createFormSubmit( array $values, Customer $customer, bool $admin, ?Invoice $invoice = NULL ) : CreditCard
	{
		if ( isset( $values['payment_method'] ) )
		{
			if ( $values['payment_method'] != 0 )
			{
				$gateway = Gateway::load( $values['payment_method'] );
			}
		}
		else
		{
			$gateways = Gateway::cardStorageGateways( $admin );
			$gateway = array_pop( $gateways );
		}
		
		if ( !$values[ $gateway->id . '_card' ] )
		{
			throw new DomainException( Member::loggedIn()->language()->addToStack('card_number_invalid') );
		}
		else
		{
			$classname = Gateway::gateways()[ $gateway->gateway ] . '\\CreditCard';
			$card = new $classname;
			$card->member = $customer;
			$card->method = $gateway;
			
			if ( is_array( $values[ $gateway->id . '_card' ] ) )
			{
				$_card = new CreditCard;
				$_card->token = $values[ $gateway->id . '_card' ]['token'];
				$card->set_card( $_card, $invoice );
			}
			else
			{
				$card->set_card( $values[ $gateway->id . '_card' ], $invoice );
			}
			$card->save();
			
			$customer->log( 'card', array( 'type' => 'add', 'number' => $card->card->lastFour ) );
			
			return $card;
		}
	}
	
	/**
	 * Get member
	 *
	 * @return	Customer
	 */
	public function get_member() : Customer
	{
		return Customer::load( $this->_data['member'] );
	}
	
	/**
	 * Set member
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function set_member( Member $member ) : void
	{
		$this->_data['member'] = $member->member_id ?: 0;
	}
	
	/**
	 * Get payment gateway
	 *
	 * @return	Gateway
	 */
	public function get_method() : Gateway
	{
		return Gateway::load( $this->_data['method'] );
	}
	
	/**
	 * Set payment gateway
	 *
	 * @param	Gateway	$gateway	Payment gateway
	 * @return	void
	 */
	public function set_method( Gateway $gateway ) : void
	{
		$this->_data['method'] = $gateway->id;
	}

	/**
	 * Automatically take payment
	 *
	 * @param Invoice $invoice
	 * @return Transaction
	 * @throws Exception
	 */
	public function takePayment( Invoice $invoice ) : Transaction
	{
		$cardDetails = $this->card; // We're just checking this doesn't throw an exception

		$amountToPay = $invoice->amountToPay();
		$gateway = $this->method;

		$transaction = new Transaction;
		$transaction->member = $invoice->member;
		$transaction->invoice = $invoice;
		$transaction->method = $gateway;
		$transaction->amount = $amountToPay;
		$transaction->currency = $amountToPay->currency;
		$transaction->extra = array( 'automatic' => TRUE );

		try
		{
			$transaction->auth = $gateway->auth( $transaction, array(
				( $gateway->id . '_card' ) => $this
			), NULL, array(), 'renewal' );
			$transaction->capture();

			$transaction->member->log( 'transaction', array(
				'type'			=> 'paid',
				'status'		=> Transaction::STATUS_PAID,
				'id'			=> $transaction->id,
				'invoice_id'	=> $invoice->id,
				'invoice_title'	=> $invoice->title,
				'automatic'		=> TRUE,
			), FALSE );

			$transaction->approve();
			return $transaction;
		}
		catch ( Exception $e )
		{
			$transaction->status = Transaction::STATUS_REFUSED;
			$extra = $transaction->extra;
			$extra['history'][] = array( 's' => Transaction::STATUS_REFUSED, 'noteRaw' => $e->getMessage() );
			$transaction->extra = $extra;
			$transaction->save();

			$transaction->member->log( 'transaction', array(
				'type'			=> 'paid',
				'status'		=> Transaction::STATUS_REFUSED,
				'id'			=> $transaction->id,
				'invoice_id'	=> $invoice->id,
				'invoice_title'	=> $invoice->title,
				'automatic'		=> TRUE,
			), FALSE );

			return $transaction;
		}
	}
}