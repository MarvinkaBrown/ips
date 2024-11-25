<?php
/**
 * @brief		Invoice Item Class for Purchases
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus\Invoice\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Coupon;
use IPS\nexus\Customer;
use IPS\nexus\Form\RenewalTerm;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item;
use IPS\nexus\Purchase as NexusPurchase;
use IPS\nexus\Purchase\RenewalTerm as PurchaseRenewalTerm;
use IPS\nexus\Tax;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoice Item Class for Purchases
 */
abstract class Purchase extends Item
{
	/**
	 * @brief	string	Act (new/charge)
	 */
	public static string $act = 'new';
	
	/**
	 * @brief	Requires login to purchase?
	 */
	public static bool $requiresAccount = TRUE;
		
	/**
	 * @brief	\DateInterval	Length granted by initial purchase before normal renewal term starts (or NULL to match renewal term)
	 */
	public ?DateInterval $initialInterval = null;

	/**
	 * @brief	\IPS\nexus\Purchase\RenewalTerm	Renewal Term
	 */
	public ?PurchaseRenewalTerm $renewalTerm = null;
	
	/**
	 * @brief	\IPS\DateTime	Expiry Date (only if the purchase needs to expire but not renew)
	 */
	public ?DateTime $expireDate = null;
	
	/**
	 * @brief	\IPS\nexus\Purchase|int	The parent purchase or item ID
	 */
	public NexusPurchase|int|null $parent = NULL;
	
	/**
	 * @brief	bool	Group with parent?
	 */
	public bool $groupWithParent = FALSE;
	
	/**
	 * Get Icon
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    string
	 */
	public static function getIcon( NexusPurchase $purchase ): string
	{
		return static::$icon;
	}
	
	/**
	 * Get Title
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    string
	 */
	public static function getTypeTitle( NexusPurchase $purchase ): string
	{
		return static::$title;
	}
	
	/**
	 * Image
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return File|null
	 */
	public static function purchaseImage( NexusPurchase $purchase ): File|null
	{
		return NULL;
	}
	
	/**
	 * Get purchases made by a customer of this item
	 *
	 * @param	Customer	$customer			The customer
	 * @param int|array|NULL $id					Item ID(s)
	 * @param bool $includeInactive	Include expired purchases?
	 * @param bool $includeCanceled	Include canceled purchases?
	 * @return    ActiveRecordIterator
	 */
	public static function getPurchases(Customer $customer, int|array|null $id = NULL, bool $includeInactive = TRUE, bool $includeCanceled = FALSE ): ActiveRecordIterator
	{
		$where = array( array( 'ps_app=? AND ps_type=? AND ps_member=?', static::$application, static::$type, $customer->member_id ) );
		if ( $id !== NULL )
		{
			if ( is_array( $id ) )
			{
				$where[] = array( Db::i()->in( 'ps_item_id', $id ) );
			}
			else
			{
				$where[] = array( 'ps_item_id=?', $id );
			}
		}
		if ( !$includeInactive )
		{
			$where[] = array( 'ps_active=1' );
		}
		if ( !$includeCanceled )
		{
			$where[] = array( 'ps_cancelled=0' );
		}

		return new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', $where ), 'IPS\nexus\Purchase' );
	}
	
	/**
	 * Get additional name info
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    array
	 */
	public static function getPurchaseNameInfo( NexusPurchase $purchase ): array
	{
		return array();
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    string
	 */
	public static function acpPage( NexusPurchase $purchase ): string
	{
		return '';
	}
	
	/**
	 * Get ACP Page Buttons
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @param	Url		$url		The page URL
	 * @return    array
	 */
	public static function acpButtons( NexusPurchase $purchase, Url $url ): array
	{
		return array();
	}
	
	/**
	 * ACP Action
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    string|null
	 */
	public static function acpAction( NexusPurchase $purchase ): string|null
	{
		return null;
	}
	
	/** 
	 * ACP Edit Form
	 *
	 * @param	NexusPurchase				$purchase	The purchase
	 * @param	Form				$form	The form
	 * @param PurchaseRenewalTerm|null $renewals	The renewal term
	 * @return	void
	 */
	public static function acpEdit(NexusPurchase $purchase, Form $form, ?PurchaseRenewalTerm $renewals ) : void
	{
		$form->add( new Text( 'ps_name', $purchase->_name, TRUE, array( 'maxLength' => 128 ) ) );
		
		if ( !$purchase->grouped_renewals and ( !$purchase->billing_agreement or $purchase->billing_agreement->canceled ) )
		{
			$form->add( new Date( 'ps_expire', $purchase->expire ?: 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'does_not_expire', 'disabled' => !$purchase->canChangeExpireDate() ) ) );
		}
		
		if ( !$purchase->billing_agreement or $purchase->billing_agreement->canceled )
		{
			$form->add( new RenewalTerm( 'ps_renewals', $renewals, FALSE, array( 'lockTerm' => !$purchase->canChangeExpireDate() ) ) );
			$form->add( new Interval( 'ps_grace_period', $purchase->grace_period / 86400, FALSE, array( 'valueAs' => Interval::DAYS, 'max' => Settings::i()->cm_invoice_expireafter ?: NULL, 'min' => NULL ), NULL, NULL, NULL ) );
		}
		
		if ( !$purchase->grouped_renewals )
		{
			$form->add( new Node( 'ps_parent', $purchase->parent(), FALSE, array( 'class' => 'IPS\nexus\Purchase', 'forceOwner' => $purchase->member, 'zeroVal' => 'no_parent', 'disabledIds' => array( $purchase->id ) ) ) );
		}
	}
	
	/** 
	 * ACP Edit Save
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @param	array				$values		Values from form
	 * @return    void
	 */
	public static function acpEditSave( NexusPurchase $purchase, array $values ): void
	{
		$purchase->name = $values['ps_name'];

		/* Figure out tax for renewals */
		$tax = NULL;

		try
		{
			if( $purchase->tax )
			{
				$tax = Tax::load( $purchase->tax );
			}
		}
		catch( OutOfRangeException ){}

		if( $tax AND $values['ps_renewals'] )
		{
			$values['ps_renewals']->tax = $tax;
		}

		/* Then save */
		if ( $purchase->grouped_renewals )
		{
			$purchase->ungroupFromParent();
			if ( !$purchase->billing_agreement or $purchase->billing_agreement->canceled )
			{
				$purchase->renewals = $values['ps_renewals'];
			}
			$purchase->save();
			$purchase->groupWithParent();
		}
		else
		{
			if ( !$purchase->billing_agreement or $purchase->billing_agreement->canceled )
			{
				$purchase->expire = ( $values['ps_expire'] ?: NULL );
				$purchase->renewals = $values['ps_renewals'];
				$purchase->grace_period = $values['ps_grace_period'] ? ( $values['ps_grace_period'] * 86400 ) : 0;
			}
			$purchase->parent = $values['ps_parent'] ?: NULL;
			$purchase->save();
		}
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
		return [];
	}

	/**
	 * Return an array of values that will be stored with the coupon
	 * Note: If you have any additional fields that have been added to the form
	 * but are NOT saved to the database, you MUST unset them from the values array
	 *
	 * @param array $values
	 * @param mixed $object		The coupon or commission rule (or other object)
	 * @return array|null
	 */
	public static function saveCustomForm( array &$values=array(), mixed $object = null ) : ?array
	{
		return null;
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    array
	 */
	public static function clientAreaPage( NexusPurchase $purchase ): array
	{
		return array();
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    void
	 */
	public static function clientAreaAction( NexusPurchase $purchase ): void
	{
		
	}
	
	/**
	 * Admin can change expire date / renewal term?
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    bool
	 */
	public static function canChangeExpireDate( NexusPurchase $purchase ): bool
	{
		return TRUE;
	}

	/**
	 * Purchase can be renewed?
	 *
	 * @param	NexusPurchase $purchase	The purchase
	 * @return    boolean
	 */
	public static function canBeRenewed( NexusPurchase $purchase ): bool
	{
		return TRUE;
	}
	
	/**
	 * Purchase can be reactivated in the ACP?
	 *
	 * @param	NexusPurchase $purchase	The purchase
	 * @param string|NULL $error		Error to show, passed by reference
	 * @return    bool
	 */
	public static function canAcpReactivate(NexusPurchase $purchase, string|null &$error=NULL ): bool
	{
		return TRUE;
	}

	/**
	 * Can Renew Until
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @param	bool				$admin		If TRUE, is for ACP. If FALSE, is for front-end.
	 * @return	DateTime|bool				TRUE means can renew as much as they like. FALSE means cannot renew at all. \IPS\DateTime means can renew until that date
	 */
	public static function canRenewUntil( NexusPurchase $purchase, bool $admin=FALSE ): DateTime|bool
	{
		return TRUE;
	}
	
	/**
	 * Get renewal payment methods IDs
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    array|NULL
	 */
	public static function renewalPaymentMethodIds( NexusPurchase $purchase ): array|null
	{
		return NULL;
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public static function onPurchaseGenerated( NexusPurchase $purchase, Invoice $invoice ): void
	{
		
	}
	
	/**
	 * On Renew (Renewal invoice paid. Is not called if expiry data is manually changed)
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @param int $cycles		Cycles
	 * @return    void
	 */
	public static function onRenew(NexusPurchase $purchase, int $cycles = 1): void
	{
		
	}
	
	/**
	 * On Expiration Date Change
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onExpirationDateChange( NexusPurchase $purchase ): void
	{
		
	}
	
	/**
	 * On expire soon
	 * If returns TRUE, the normal expire warning email will not be sent
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    bool
	 */
	public static function onExpireWarning( NexusPurchase $purchase ): bool
	{
		return FALSE;
	}
	
	/**
	 * On Purchase Expired
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onExpire( NexusPurchase $purchase ): void
	{
		
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onCancel( NexusPurchase $purchase ): void
	{
		
	}

	/**
	 * Warning to display to admin when cancelling a purchase
	 *
	 * @param NexusPurchase $purchase
	 * @return    string|null
	 */
	public static function onCancelWarning( NexusPurchase $purchase ): string|null
	{
		return NULL;
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onDelete( NexusPurchase $purchase ): void
	{
		
	}
	
	/**
	 * On Purchase Reactivated (renewed after being expired or reactivated after being canceled)
	 *
	 * @param	NexusPurchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onReactivate( NexusPurchase $purchase ): void
	{
		
	}
	
	/**
	 * On Transfer (is ran before transferring)
	 *
	 * @param	NexusPurchase	$purchase		The purchase
	 * @param	Member			$newCustomer	New Customer
	 * @return    void
	 */
	public static function onTransfer( NexusPurchase $purchase, Member $newCustomer ): void
	{
		
	}
	
	/**
	 * Requires Billing Address
	 *
	 * @return	bool
	 * @throws	DomainException
	 */
	public function requiresBillingAddress(): bool
	{
		return in_array( 'product', explode( ',', Settings::i()->nexus_require_billing ) );
	}
	
	/**
	 * Show Purchase Record?
	 *
	 * @return    bool
	 */
	public function showPurchaseRecord(): bool
	{
		return TRUE;
	}
	
	/**
	 * Is this item the same as another item in the cart?
	 * Used to decide when an item is added to the cart if we should just increase the quantity of this item instead  of creating a new item.
	 *
	 * @param Item $item	The other item
	 * @return    bool
	 */
	public function isSameAsOtherItem( Item $item ): bool
	{
		// You can't compare DateInterval objects, it just throws an exception, so we have to
		// manually figure out if the renewal terms have any differences
		
		if ( $item instanceof static )
		{
			if ( $item->renewalTerm xor $this->renewalTerm )
			{
				return FALSE;
			}
			elseif ( !$item->renewalTerm and !$this->renewalTerm )
			{
				return parent::isSameAsOtherItem( $item );
			}
			else
			{
				if ( $item->renewalTerm and $this->renewalTerm )
				{
					if ( $item->renewalTerm->getTerm() != $this->renewalTerm->getTerm() )
					{
						return FALSE;
					}
					if ( $item->renewalTerm->cost != $this->renewalTerm->cost )
					{
						return FALSE;
					}
				}
				
				$clonedThis = clone $this;
				$clonedThis->renewalTerm = NULL;
				$clonedOther = clone $item;
				$clonedOther->renewalTerm = NULL;
				
				return $clonedThis->isSameAsOtherItem( $clonedOther );
			}
		}
		else
		{
			return FALSE;
		}
	}
}