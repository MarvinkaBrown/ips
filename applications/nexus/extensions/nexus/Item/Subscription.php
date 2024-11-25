<?php
/**
 * @brief		Subscriptions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		09 Feb 2018
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\DateTime;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Coupon;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Purchase;
use IPS\nexus\Subscription as SubscriptionClass;
use IPS\nexus\Subscription\Package;
use IPS\nexus\Tax;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_object;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Subscriptions
 */
class Subscription extends Invoice\Item\Purchase
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'subscription';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'certificate';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'nexus_member_subscription';
	
	/**
	 * Purchase can be reactivated in the ACP?
	 *
	 * @param	Purchase $purchase	The purchase
	 * @param string|NULL $error		Error to show, passed by reference
	 * @return    bool
	 */
	public static function canAcpReactivate(Purchase $purchase, string|null &$error=NULL ): bool
	{
		/* If the user has a different subscription that is active -or- can be reactivated, then we cannot reactivate this one */
		if ( $subscription = SubscriptionClass::loadByMember( $purchase->member, FALSE ) AND ( $subscription->purchase->active OR ( $subscription->purchase->cancelled AND $subscription->purchase->can_reactivate ) ) )
		{
			if ( $subscription->purchase == $purchase )
			{
				return TRUE;
			}
			
			$error = 'not_with_existing_subscription';
			return FALSE;
		}
		
		/* Otherwise, we're good */
		return TRUE;
	}
	
	/** 
	 * ACP Edit Save
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param	array				$values		Values from form
	 * @return    void
	 */
	public static function acpEditSave( Purchase $purchase, array $values ): void
	{
		parent::acpEditSave( $purchase, $values );
		
		try 
		{
			$subscription = SubscriptionClass::loadByMemberAndPackage( $purchase->member, Package::load( $purchase->item_id ) );
			$subscription->expire = is_object( $purchase->expire ) ? $purchase->expire->getTimestamp() : ( $purchase->expire ?: 0 );
			$subscription->save();
		}
		catch( UnderflowException ) { }
	}

	/**
	 * Get Title
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    string
	 */
	public static function getTypeTitle( Purchase $purchase ): string
	{
		try
		{
			$class = Package::load( $purchase->item_id );
			return $class::$nodeTitle;
		}
		catch ( Exception  ) {}
		
		return '';
	}
	
	/**
	 * Image
	 *
	 * @return File|null
	 */
	public function image(): File|null
	{
		try
		{
			if ( $photo = Package::load( $this->id )->image )
			{
				return File::get( 'nexus_Products', $photo );
			}
		}
		catch ( Exception  ) {}
		
		return NULL;
	}

	/**
	 * Additional elements that will be used to create coupons
	 * Also used on the Commission Rules
	 *
	 * @param array|string|null $current	Current data
	 * @return array
	 */
	public static function customFormElements( array|string|null $current =null ) : array
	{
		$subscriptions = null;
		if( $current and $current !== '*' )
		{
			if( isset( $current[ static::$title ]['subs'] ) and $current[ static::$title ]['subs'] !== 0 )
			{
				foreach( $current[ static::$title ]['subs'] as $id )
				{
					try
					{
						$subscriptions[] = Package::load( $id );
					}
					catch( OutOfRangeException ){}
				}
			}
		}

		return [
			new Node( 'c_nexus_subscription', $subscriptions ?? 0, FALSE, array(
				'class' => Package::class,
				'multiple' => true,
				'zeroVal' => 'no_restriction'
			), null, null, null, 'c_nexus_subscription' )
		];
	}

	/**
	 * Build a list of language strings to show in an object description
	 *
	 * @param array|string|null $data
	 * @return array
	 */
	public static function customFormDescription( array|string|null $data ) : array
	{
		$return = [];
		if( isset( $data[ static::$title ]['subs'] ) and is_array( $data[ static::$title ]['subs'] ) )
		{
			foreach( $data[ static::$title ]['subs'] as $packageId )
			{
				$return[] = Member::loggedIn()->language()->addToStack( 'nexus_subs_' . $packageId );
			}
		}

		return $return;
	}

	/**
	 * Return an array of values that will be stored with the coupon
	 * Note: If you have any additional fields that have been added to the form
	 * but are NOT saved to the database, you MUST unset them from the values array
	 *
	 * @param array $values
	 * @param mixed|null $object
	 * @return array|null
	 */
	public static function saveCustomForm( array &$values=array(), mixed $object = null ) : ?array
	{
		if( !isset( $values['c_nexus_subscription'] ) )
		{
			return null;
		}

		if( array_key_exists( 'c_products', $values ) and !empty( $values['c_products'] ) and !in_array( static::$title, $values['c_products'] ) )
		{
			unset( $values['c_nexus_subscription'] );
			return null;
		}

		if( is_array( $values['c_nexus_subscription'] ) )
		{
			$subscriptions = [];
			foreach ( $values['c_nexus_subscription'] as $package )
			{
				$subscriptions[] = $package->id;
			}
		}
		else
		{
			$subscriptions = 0;
		}

		unset( $values['c_nexus_subscription'] );

		return ( $subscriptions === 0 or count( $subscriptions ) ) ? array( 'subs' => $subscriptions ) : null;
	}

	/**
	 * Determines if the coupon can be applied to this item
	 *
	 * @param array|string $data
	 * @param Invoice $invoice
	 * @param Customer $customer
	 * @return bool
	 */
	public function isValid( array|string $data, Invoice $invoice, Customer $customer ) : bool
	{
		if( $data === '*' )
		{
			return true;
		}

		if( isset( $data[ static::$title ]['subs'] ) )
		{
			if( $data[ static::$title ]['subs'] === 0 )
			{
				return true;
			}

			return in_array( $this->id, $data[ static::$title ]['subs'] );
		}

		return false;
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    array    array( 'packageInfo' => '...', 'purchaseInfo' => '...' )
	 */
	public static function clientAreaPage( Purchase $purchase ): array
	{
		try
		{
			$package = Package::load( $purchase->item_id );
			
			return array( 'packageInfo' => Theme::i()->getTemplate( 'subscription', 'nexus' )->clientArea( $package ) );
		}
		catch ( OutOfRangeException  ) { }
		
		return [];
	}
	
	/**
	 * Image
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return File|null
	 */
	public static function purchaseImage( Purchase $purchase ): File|null
	{
		try
		{
			if ( $photo = Package::load( $purchase->item_id )->image )
			{
				return File::get( 'nexus_Products', $photo );
			}
		}
		catch ( Exception  ) {}
		
		return NULL;
	}
		
	/**
	 * URL
	 *
	 * @return Url|string|null
	 */
	function url(): Url|string|null
	{
		try
		{
			return Package::load( $this->id )->url();
		}
		catch ( OutOfRangeException  )
		{
			return NULL;
		}
	}
		
	/** 
	 * Get renewal payment methods IDs
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    array|NULL
	 */
	public static function renewalPaymentMethodIds( Purchase $purchase ): array|null
	{
		try
		{
			$package = Package::load( $purchase->item_id );
			if ( $package->gateways and $package->gateways != '*' )
			{
				return explode( ',', $package->gateways );
			}
			else
			{
				return NULL;
			}
		}
		catch ( Exception  ) {}

		return NULL;
	}

	/**
	 * Purchase can be renewed?
	 *
	 * @param	Purchase $purchase	The purchase
	 * @return    boolean
	 */
	public static function canBeRenewed( Purchase $purchase ): bool
	{
		$package = Package::load( $purchase->item_id );
		$renewals = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();

		return (boolean) count( $renewals );
	}

	/**
	 * Can Renew Until
	 *
	 * @param	Purchase		$purchase	The purchase
	 * @param	bool					$admin		If TRUE, is for ACP. If FALSE, is for front-end.
	 * @return	DateTime|bool				TRUE means can renew as much as they like. FALSE means cannot renew at all. \IPS\DateTime means can renew until that date
	 */
	public static function canRenewUntil( Purchase $purchase, bool $admin=FALSE ): DateTime|bool
	{
		return static::canBeRenewed( $purchase );
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public static function onPurchaseGenerated( Purchase $purchase, Invoice $invoice ): void
	{
		try
		{
			$subscription = Package::load( $purchase->item_id )->addMember( $purchase->member );
			$subscription->purchase_id = $purchase->id;
			$subscription->invoice_id = $invoice->id;
			$subscription->save();

			/* Achievements */
			$purchase->member->achievementAction( 'nexus', 'Subscription', $subscription );
		}
		catch ( Exception  ) {}
	}
	
	/**
	 * On Renew (Renewal invoice paid. Is not called if expiry data is manually changed)
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param int $cycles		Cycles
	 * @return    void
	 */
	public static function onRenew(Purchase $purchase, int $cycles = 1): void
	{
		try
		{
			Package::load( $purchase->item_id )->renewMember( $purchase->member );
		}
		catch ( Exception  ) {}
	}
	
	/**
	 * On Purchase Expired
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onExpire( Purchase $purchase ): void
	{		
		try
		{
			Package::load( $purchase->item_id )->expireMember( $purchase->member );
		}
		catch ( Exception  ) {}
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onCancel( Purchase $purchase ): void
	{
		try
		{
			Package::load( $purchase->item_id )->cancelMember( $purchase->member );
		}
		catch ( Exception  ) {}
	}
	
	/**
	 * Warning to display to admin when cancelling a purchase
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    string|null
	 */
	public static function onCancelWarning( Purchase $purchase ): string|null
	{
		return NULL;
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onDelete( Purchase $purchase ): void
	{
		/* Do any cancellation cleanup needed */
		static::onCancel( $purchase );
		
		/* Delete the subscription row */
		try
		{
			$sub = SubscriptionClass::load( $purchase->id, 'sub_purchase_id' );
			$sub->delete();
		}
		catch( OutOfRangeException  ) {}
	}
	
	/**
	 * On Purchase Reactivated (renewed after being expired or reactivated after being canceled)
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onReactivate( Purchase $purchase ): void
	{
		try
		{
			$sub = SubscriptionClass::load( $purchase->id, 'sub_purchase_id' );
			$sub->active = 1;
			$sub->save();
		}
		catch( OutOfRangeException  ) { }
		
		try
		{
			Package::load( $purchase->item_id )->addMember( $purchase->member );
		}
		catch ( Exception  ) {}
	}
	
	/**
	 * On Transfer (is ran before transferring)
	 *
	 * @param	Purchase	$purchase		The purchase
	 * @param	Member			$newCustomer	New Customer
	 * @return    void
	 */
	public static function onTransfer( Purchase $purchase, Member $newCustomer ): void
	{
		try
		{
			$package = Package::load( $purchase->item_id );
		}
		catch ( OutOfRangeException  )
		{
			return;
		}
				
		/* Remove the old member's record */
		$package->removeMember( $purchase->member );
		
		/* Now if the purchase isn't cancelled... */
		if ( !$purchase->cancelled )
		{
			/* We need the Customer object */
			$newCustomer = Customer::load( $newCustomer->member_id );
			
			/* Remove any request/invitation for the new member and add the new record */
			$package->removeMember( $newCustomer );
			$package->addMember( $newCustomer );
		}
	}
	
	/**
	 * Generate Invoice Form
	 *
	 * @param	Form	$form		The form
	 * @param	Invoice	$invoice	The invoice
	 * @return	void
	 */
	public static function form( Form $form, Invoice $invoice ) : void
	{
		$form->add( new Node( 'nexus_sub_package', NULL, TRUE, array( 'class' => 'IPS\nexus\Subscription\Package') ) );
	}
	
	/**
	 * Create From Form
	 *
	 * @param	array				$values		Values from form
	 * @param	Invoice	$invoice	The invoice
	 * @return    static
	 */
	public static function createFromForm( array $values, Invoice $invoice ): static
	{
		$package = $values['nexus_sub_package'];
		
		$fee = $package->price( $invoice->currency );
		
		$item = new static( $invoice->member->language()->get( $package->_titleLanguageKey ), $fee );
		$item->id = $package->id;
		try
		{
			$item->tax = $package->tax ? Tax::load( $package->tax ) : NULL;
		}
		catch ( OutOfRangeException  ) { }
		
		if ( $package->gateways !== '*' )
		{
			$item->paymentMethodIds = explode( ',', $package->gateways );
		}
		
		$item->renewalTerm = $package->renewalTerm( $fee->currency );
		
		return $item;
	}
	
	/**
	 * Requires Billing Address
	 *
	 * @return	bool
	 * @throws	DomainException
	 */
	public function requiresBillingAddress(): bool
	{
		return in_array( 'subscriptions', explode( ',', Settings::i()->nexus_require_billing ) );
	}

	/**
	 * Check for member
	 * If a user initially checks out as a guest and then logs in during checkout, this method
	 * is ran to check the items they are purchasing can be bought.
	 * Is expected to throw a DomainException with an error message to display to the user if not valid
	 *
	 * @param	Member	$member	The new member
	 * @return    void
	 * @throws	DomainException
	 */
	public function memberCanPurchase( Member $member ): void
	{
		/* Already purchased a subscription */
		if ( $current = SubscriptionClass::loadByMember( $member, FALSE ) AND ( $current->purchase AND ( !$current->purchase->cancelled OR $current->purchase->can_reactivate ) ) )
		{
			throw new DomainException( $member->language()->addToStack( 'err_sub_subscription_bought' ) );
		}
	}
}