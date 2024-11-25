<?php
/**
 * @brief		Gateway Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\nexus\Customer\CreditCard;
use IPS\nexus\Fraud\MaxMind\Request;
use IPS\Node\Model;
use IPS\Settings;
use LogicException;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_null;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Gateway Node
 */
abstract class Gateway extends Model
{
	/**
	 * Gateways
	 *
	 * @return	array
	 */
	public static function gateways() : array
	{
		$return = array(
			'Stripe'		=> 'IPS\nexus\Gateway\Stripe',
			'PayPal'		=> 'IPS\nexus\Gateway\PayPal',
			'Manual'		=> 'IPS\nexus\Gateway\Manual',
		);

		/* Extension Gateways */
		foreach ( Application::allExtensions( 'nexus', 'Gateway', FALSE, 'core' ) as $key => $extension )
		{
			$return[$extension::$gatewayKey] = $extension::class;
		}
		
		if ( \IPS\NEXUS_TEST_GATEWAYS )
		{
			$return['Test'] = 'IPS\nexus\Gateway\Test';
		}
		
		return $return;
	}
	
	/**
	 * Payout Gateways
	 *
	 * @return	array
	 */
	public static function payoutGateways() : array
	{
		$return = array(
			'PayPal'		=> 'IPS\nexus\Gateway\PayPal\Payout',
			'Manual'		=> 'IPS\nexus\Gateway\Manual\Payout',
		);

		/* Extension Gateways */
		foreach ( Application::allExtensions( 'nexus', 'Payout', FALSE, 'core' ) as $key => $extension )
		{
			$return[ $extension::$gatewayKey ] = $extension::class;
		}

		return $return;
	}
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_paymethods';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'm_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'payment_methods';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'nexus_paymethod_';
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): Gateway
	{
		/* Might be a deleted/deprecated gateway */
		if( !array_key_exists( $data['m_gateway'], static::gateways() ) )
		{
			throw new OutOfRangeException;
		}

		$classname = static::gateways()[ $data['m_gateway'] ];
		if ( !class_exists( $classname ) )
		{
			throw new OutOfRangeException;
		}
		
		/* Initiate an object */
		$obj = new $classname;
		$obj->_new = FALSE;
		
		/* Import data */
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
			{
				$k = substr( $k, strlen( static::$databasePrefix ) );
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
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled(): ?bool
	{
		return $this->enabled;
	}
	
	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( bool|int $enabled ) : void
	{
		$this->enabled	= $enabled;
	}
			
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'nexus',
		'module'	=> 'payments',
		'all'		=> 'gateways_manage',
	);
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Translatable( 'paymethod_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_paymethod_{$this->id}" : NULL ) ) );
		$this->settings( $form );
		$form->add( new Select( 'paymethod_countries', ( $this->id and $this->countries !== '*' ) ? explode( ',', $this->countries ) : '*', FALSE, array( 'options' => array_map( function( $val )
		{
			return "country-{$val}";
		}, array_combine( GeoLocation::$countries, GeoLocation::$countries ) ), 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'no_restriction' ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( isset( $values['paymethod_name'] ) )
		{
			Lang::saveCustom( 'nexus', "nexus_paymethod_{$this->id}", $values['paymethod_name'] );
			unset( $values['paymethod_name'] );
		}

		if( isset( $values['paymethod_countries'] ) )
		{
			$values['countries'] = is_array( $values['paymethod_countries'] ) ? implode( ',', $values['paymethod_countries'] ) : $values['paymethod_countries'];
		}

		if( isset( $values['m_validationfile'] ) )
		{
			$values['validationfile'] = (string) $values['m_validationfile'];
		}

		$settings = array();
		foreach ( $values as $k => $v )
		{
			if ( mb_substr( $k, 0, mb_strlen( $this->gateway ) + 1 ) === mb_strtolower( "{$this->gateway}_" ) )
			{
				$settings[ mb_substr( $k, mb_strlen( $this->gateway ) + 1 ) ] = $v;
			}
			if( $k != "countries" AND $k != 'validationfile' )
			{
				unset( $values[$k] );
			}
		}
		$values['settings'] = json_encode( $this->testSettings( $settings ) );

		return $values;
	}

	/**
	 * Get gateways that support storing cards
	 *
	 * @param	bool	$adminCreatableOnly	If TRUE, will only return gateways where the admin (opposed to the user) can create a new option
	 * @return	array
	 */
	public static function cardStorageGateways( bool $adminCreatableOnly = FALSE ) : array
	{
		$return = array();
		foreach ( static::roots() as $gateway )
		{
			if ( $gateway->canStoreCards( $adminCreatableOnly ) )
			{
				$return[ $gateway->id ] = $gateway;
			}
		}
		return $return;
	}
		
	/**
	 * Get gateways that support manual admin charges
	 *
	 * @param Customer $customer	The customer we're wanting to charge
	 * @return	array
	 */
	public static function manualChargeGateways(Customer $customer ) : array
	{
		$return = array();
		foreach ( static::roots() as $gateway )
		{
			if ( $gateway->canAdminCharge( $customer ) )
			{
				$return[ $gateway->id ] = $gateway;
			}
		}
		return $return;
	}
	
	/**
	 * Get gateways that support billing agreements
	 *
	 * @return	array
	 */
	public static function billingAgreementGateways() : array
	{
		$return = array();
		foreach ( static::roots() as $gateway )
		{
			if ( $gateway->billingAgreements() )
			{
				$return[ $gateway->id ] = $gateway;
			}
		}
		return $return;
	}
	
	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();
		static::recountCardStorageGateways();
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	* array(
	* array(
	* 'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	* 'title'	=> 'foo',		// Language key to use for button's title parameter
	* 'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	* 'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	* ),
	* ...							// Additional buttons
	* );
	 * @endcode
	 * @param Url $url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ):array
	{
		$buttons = parent::getButtons( $url, $subnode );

		/* If we have active billing agreements and the node isn't currently queued for deletion, add a special warning to the delete confirmation box */
		if( $this->hasActiveBillingAgreements() AND isset( $buttons['delete'] ) )
		{
			$buttons['delete']['data']['delete-warning'] = Member::loggedIn()->language()->addToStack('gateway_ba_delete_blurb');
		}

		return $buttons;
	}

	/**
	 * Is this node currently queued for deleting or moving content?
	 *
	 * @return	bool
	 */
	public function deleteOrMoveQueued(): bool
	{
		if( $this->hasActiveBillingAgreements() )
		{
			/* If we already know, don't bother */
			if ( is_null( $this->queued ) )
			{
				$this->queued = FALSE;

				foreach( Db::i()->select( 'data', 'core_queue', array( 'app=? AND `key`=?', 'nexus', 'DeletePaymentMethod' ) ) as $taskData )
				{
					$data = json_decode( $taskData, TRUE );

					if( $data['id'] == $this->id )
					{
						$this->queued = TRUE;
						break;
					}
				}
			}

			return $this->queued;
		}
		else
		{
			return parent::deleteOrMoveQueued();
		}
	}

	/**
	 * [Node]	Dissalow gateways with validation files to be copyable ( the file can't /shouldn't be copied and reusing the existing value will delete the source file when the new copy is deleted )
	 *
	 * @return	bool
	 */
	public function canCopy(): bool
	{
		if ( $this->deleteOrMoveQueued() === TRUE )
		{
			return FALSE;
		}

		return ( !$this->validationfile );
	}

	/**
	 * [ActiveRecord] Delete
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* Delete cards and billing agreements */
		Db::i()->delete( 'nexus_customer_cards', array( 'card_method=?', $this->id ) );
		Db::i()->delete( 'nexus_billing_agreements', array( 'ba_method=?', $this->id ) );

		try
		{
			File::get( 'nexus_Gateways', $this->validationfile )->delete();
		}
		catch( Exception ) { }


		/* Delete */
		parent::delete();
		
		/* Recount how many gateways support cards */
		static::recountCardStorageGateways();
	}
	
	/**
	 * Recount card storage gateays
	 *
	 * @return	void
	 */
	protected static function recountCardStorageGateways() : void
	{
		$counts = array();
		foreach ( static::roots() as $gateway )
		{
			if ( !isset( $counts[ $gateway->gateway ] ) )
			{
				$counts[ $gateway->gateway ] = 0;
			}
			
			$counts[ $gateway->gateway ]++;
		}
		
		Settings::i()->changeValues( array( 'card_storage_gateways' => count( static::cardStorageGateways() ), 'billing_agreement_gateways' => count( static::billingAgreementGateways() ), 'gateways_counts' => json_encode( $counts ) ) );
	}
	
	/* !Features (Each gateway will override) */

	const SUPPORTS_REFUNDS = FALSE;
	const SUPPORTS_PARTIAL_REFUNDS = FALSE;
	const SUPPORTS_AUTOPAY = FALSE;
	
	/**
	 * Check the gateway can process this...
	 *
	 * @param	$amount            Money        The amount
	 * @param	$billingAddress	GeoLocation|NULL	The billing address, which may be NULL if one if not provided
	 * @param	$customer        Customer|null        The customer (Default NULL value is for backwards compatibility - it should always be provided.)
	 * @param	array			$recurrings				Details about recurring costs
	 * @return	bool
	 */
	public function checkValidity(Money $amount, ?GeoLocation $billingAddress = NULL, ?Customer $customer = NULL, array $recurrings = array() ) : bool
	{
		if ( $this->countries !== '*' )
		{
			if ( $billingAddress )
			{
				return in_array( $billingAddress->country, explode( ',', $this->countries ) );
			}
			else
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Can store cards?
	 *
	 * @param bool $adminCreatableOnly	If TRUE, will only return gateways where the admin (opposed to the user) can create a new option
	 * @return    bool
	 */
	public function canStoreCards(bool $adminCreatableOnly = FALSE ): bool
	{
		return FALSE;
	}
	
	/**
	 * Admin can manually charge using this gateway?
	 *
	 * @param Customer $customer	The customer we're wanting to charge
	 * @return    bool
	 */
	public function canAdminCharge(Customer $customer ): bool
	{
		return FALSE;
	}
	
	/**
	 * Supports billing agreements?
	 *
	 * @return    bool
	 */
	public function billingAgreements(): bool
	{
		return FALSE;
	}

	/**
	 * Has active billing agreements?
	 *
	 * @return    bool
	 */
	public function hasActiveBillingAgreements(): bool
	{
		return (bool) Db::i()->select( 'COUNT(*)', 'nexus_billing_agreements', array( 'ba_method=? AND ba_canceled=?', $this->id, 0 ) )->first();
	}
	
	/* !Payment Gateway */
	
	/**
	 * Should the submit button show when this payment method is shown?
	 *
	 * @return    bool
	 */
	public function showSubmitButton(): bool
	{
		return true;
	}
	
	/**
	 * Payment Screen Fields
	 *
	 * @param Invoice $invoice	Invoice
	 * @param Money $amount		The amount to pay now
	 * @param Customer|null $member		The member the payment screen is for (if in the ACP charging to a member's card) or NULL for currently logged in member
	 * @param array $recurrings	Details about recurring costs
	 * @param string $type		'checkout' means the cusotmer is doing this on the normal checkout screen, 'admin' means the admin is doing this in the ACP, 'card' means the user is just adding a card
	 * @return    array
	 */
	abstract public function paymentScreen(Invoice $invoice, Money $amount, ?Customer $member = NULL, array $recurrings = array(), string $type = 'checkout' ): array;
	
	/**
	 * Manual Payment Instructions
	 *
	 * @param Transaction $transaction	Transaction
	 * @param string|NULL $email			If this is for the email, will be 'html' or 'plaintext'. If for UI, will be NULL.
	 * @return    string
	 */
	public function manualPaymentInstructions(Transaction $transaction, ?string $email = NULL ): string
	{
		return $transaction->member->language()->addToStack( "nexus_gateway_{$this->id}_ins" );
	}

	/**
	 * Authorize
	 *
	 * @param Transaction $transaction	Transaction
	 * @param array|CreditCard $values			Values from form OR a stored card object if this gateway supports them
	 * @param	Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made
	 * @param	array									$recurrings		Details about recurring costs
	 * @param string|NULL $source			'checkout' if the customer is doing this at a normal checkout, 'renewal' is an automatically generated renewal invoice, 'manual' is admin manually charging. NULL is unknown
	 * @return    array|DateTime|NULL                        Auth is valid until or NULL to indicate auth is good forever
	 * @throws	LogicException							Message will be displayed to user
	 */
	public function auth(Transaction $transaction, array|CreditCard $values, ?Request $maxMind = NULL, array $recurrings = array(), ?string $source = NULL ): DateTime|array|null
	{
		return NULL;
	}
	
	/**
	 * Void
	 *
	 * @param Transaction $transaction	Transaction
	 * @return    mixed
	 * @throws	Exception
	 */
	public function void(Transaction $transaction ): mixed
	{
		return $this->refund($transaction );
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse		int						id				ID number
	 * @apiresponse		string					name			Name
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array(
		'id'	=> $this->id,
		'name'	=> $this->_title
		);
	}
	
	/**
	 * Capture
	 *
	 * @param Transaction $transaction	Transaction
	 * @return    void
	 * @throws	LogicException
	 */
	abstract public function capture(Transaction $transaction ): void;
	
	/**
	 * Refund
	 *
	 * @param Transaction $transaction	Transaction to be refunded
	 * @param mixed|NULL $amount			Amount to refund (NULL for full amount - always in same currency as transaction)
	 * @param string|null $reason
	 * @return    mixed                                    Gateway reference ID for refund, if applicable
	 * @throws	Exception
 	 */
	abstract public function refund(Transaction $transaction, mixed $amount = NULL, ?string $reason = NULL): mixed;
	
	/**
	 * Refund Reasons that the gateway understands, if the gateway supports this
	 *
	 * @return    array
 	 */
	public static function refundReasons(): array
	{
		return [];
	}

	/**
	 * Settings
	 *
	 * @param Form $form	The form
	 * @return    void
	 */
	abstract public function settings( Form $form ): void;

	/**
	 * Test Settings
	 *
	 * @param array $settings	Settings
	 * @return    array
	 * @throws	InvalidArgumentException
	 */
	abstract public function testSettings(array $settings=array() ): array;

	/**
	 * Extra data to show on the ACP transaction page
	 *
	 * @param Transaction $transaction	Transaction
	 * @return    string
	 */
	public function extraData(Transaction $transaction ): string
	{
		return '';
	}

	/**
	 * Extra data to show on the ACP transaction page for a dispute
	 *
	 * @param Transaction $transaction	Transaction
	 * @param string|array $ref			Dispute reference ID
	 * @return    string
	 */
	public function disputeData(Transaction $transaction, string|array $ref ): string
	{
		return '';
	}

	/**
	 * Run any gateway-specific anti-fraud checks and return status for transaction
	 * This is only called if our local anti-fraud rules have not matched
	 *
	 * @param Transaction $transaction	Transaction
	 * @return    string
	 */
	public function fraudCheck(Transaction $transaction ): string
	{
		return $transaction::STATUS_PAID;
	}

	/**
	 * URL to view transaction in gateway
	 *
	 * @param Transaction $transaction	Transaction
	 * @return    Url|NULL
	 */
	public function gatewayUrl(Transaction $transaction ): Url|null
	{
		return NULL;
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
		return [];
	}

	/**
	 * Returns the Apple Pay domain verification file if there's one.
	 *
	 * @return File|null
	 */
	public static function getStripeAppleVerificationFile(): File|null
	{
		foreach ( static::roots() as $gateway )
		{
			if ( $gateway->gateway == 'Stripe' AND $gateway->validationfile )
			{
				try
				{
					return File::get( 'nexus_Gateways', $gateway->validationfile );
				}
				catch ( Exception ){}
			}
		}
		return null;
	}
}