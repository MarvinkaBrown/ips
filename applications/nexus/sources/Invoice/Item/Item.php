<?php
/**
 * @brief		Invoice Abstract Item Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus\Invoice;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DomainException;
use IPS\File;
use IPS\GeoLocation;
use IPS\Http\Url;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Coupon;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Purchase;
use IPS\nexus\Tax;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function gettype;
use function in_array;
use function is_int;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoice Abstract Item Interface
 *
 */
abstract class Item
{
	/**
	 * @brief	Can use coupons?
	 */
	public static bool $canUseCoupons = TRUE;
	
	/**
	 * @brief	Can use account credit?
	 */
	public static bool $canUseAccountCredit = TRUE;

	/**
	 * @brief	Can you buy more than one of this item?
	 *
	 * @var bool
	 */
	public static bool $canChangeQuantity = false;

	/**
	 * @brief	Icon
	 */
	public static string $icon = '';
	
	/**
	 * @brief	string	Name
	 */
	public string $name;
	
	/**
	 * @brief	int	Quantity
	 */
	public int $quantity = 1;
	
	/**
	 * @brief	\IPS\nexus\Money	Price
	 */
	public Money $price;
	
	/**
	 * @brief	int|NULL	ID
	 */
	public ?int $id;
	
	/**
	 * @brief	\IPS\nexus\Tax		Tax Class
	 */
	public ?Tax $tax = null;
	
	/**
	 * @brief	Payment Methods IDs
	 */
	public ?array $paymentMethodIds = null;
	
	/**
	 * @brief	Key/Value array of extra details to display on the invoice
	 */
	public array $details = array();
	
	/**
	 * @brief	Key/Value array of extra details to store on the purchase
	 */
	public array $purchaseDetails = array();
	
	/**
	 * @brief	Pay To member
	 */
	public ?Member $payTo = null;
	
	/**
	 * @brief	Application
	 */
	public static string $application = '';

	/**
	 * @brief	Type
	 */
	public static string $type = '';
	
	/**
	 * @brief	Commission percentage
	 */
	public int $commission = 0;
	
	/**
	 * @brief	Commission fee
	 */
	public int|Money $fee = 0;
	
	/**
	 * @brief	Extra
	 */
	public mixed $extra = null;
	
	/**
	 * @brief	Group With Parent
	 */
	public bool $groupWithParent = FALSE;

	/**
	 * @brief	Application key
	 */
	public ?string $appKey = NULL;

	/**
	 * @brief	Type key
	 */
	public ?string $typeKey = NULL;

	/**
	 * Constructor
	 *
	 * @param string $name	Name
	 * @param	Money	$price	Price
	 * @return	void
	 */
	public function __construct(string $name, Money $price )
	{
		$this->name = $name;
		$this->price = $price;
	}
	
	/**
	 * Get (can be used to override static properties like icon and title in an instance)
	 *
	 * @param	string	$k	Property
	 * @return	mixed
	 */
	public function __get( string $k ) : mixed
	{
		$k = mb_substr( $k, 1 );
		return static::$$k;
	}

	/**
	 * Used to override the static property, if necessary
	 *
	 * @return bool
	 */
	public function canUseAccountCredit() : bool
	{
		return static::$canUseAccountCredit;
	}

	/**
	 * Used to override the static property, if necessary
	 *
	 * @return bool
	 */
	public function canUseCoupons() : bool
	{
		return static::$canUseCoupons;
	}

	/**
	 * @return bool
	 */
	public function canChangeQuantity() : bool
	{
		return static::$canChangeQuantity;
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
		return false;
	}
	
	/**
	 * Get line price without tax
	 *
	 * @return	Money
	 */
	public function linePrice() : Money
	{
		return new Money( $this->price->amount->multiply( new Number("{$this->quantity}") ), $this->price->currency );
	}
	
	/**
	 * Get tax rate
	 *
	 * @param	GeoLocation|NULL	$location	Location to use for tax rate or NULL to use specified billing address
	 * @return	Number
	 */
	public function taxRate( ?GeoLocation $location = NULL ) : Number
	{
		return new Number( $this->tax ? $this->tax->rate( $location ) : '0' );
	}
	
	/**
	 * Get item price with tax
	 *
	 * @param	GeoLocation|NULL	$location	Location to use for tax rate or NULL to use specified billing address
	 * @return	Money
	 */
	public function grossPrice( ?GeoLocation $location = NULL ) : Money
	{
		return new Money( $this->price->amount->add( $this->price->amount->multiply( $this->taxRate( $location ) ) ), $this->price->currency );
	}
	
	/**
	 * Get line price with tax
	 *
	 * @param	GeoLocation|NULL	$location	Location to use for tax rate or NULL to use specified billing address
	 * @return	Money
	 */
	public function grossLinePrice( ?GeoLocation $location = NULL ) : Money
	{
		return new Money( $this->linePrice()->amount->add( $this->linePrice()->amount->multiply( $this->taxRate( $location ) ) ), $this->price->currency );
	}

	/**
	 * Format the details to be displayed on the invoice
	 * Moved to a method so that custom items can add line items
	 * that may not be custom fields
	 *
	 * @param string $location	checkout|invoice|print
	 * @return string
	 */
	public function detailsForDisplay( string $location ) : string
	{
		switch( $location )
		{
			case 'checkout':
				return Theme::i()->getTemplate( 'checkout', 'nexus', 'front' )->packageFields( $this );

			case 'print':
				return Theme::i()->getTemplate( 'invoices', 'nexus', 'global' )->packageFields( $this );

			default:
				return Theme::i()->getTemplate( 'clients', 'nexus', 'front' )->packageFields( $this );
		}
	}
	
	/**
	 * Get recipient amounts
	 *
	 * @return	array
	 */
	public function recipientAmounts() : array
	{
		$return = array();
		
		if ( $this->payTo )
		{
			$linePrice = $this->linePrice();
			$currency = $linePrice->currency;
						
			$commission = $this->price->amount->percentage( $this->commission );
			$lineComission = $commission->multiply( new Number("{$this->quantity}") );
			
			$return['site_commission_unit'] = new Money( $commission, $currency );
			$return['site_commission_line'] = new Money( $lineComission, $currency );
			
			$return['recipient_unit'] = new Money( $this->price->amount->subtract( $return['site_commission_unit']->amount ), $currency );
			$return['recipient_line'] = new Money( $linePrice->amount->subtract( $return['site_commission_line']->amount ), $currency );
			
			$fee = $this->fee ? $this->fee->amount : new Number('0');
			$siteTotal = $return['site_commission_line']->amount->add( $fee );
			$recipientTotal = $linePrice->amount->subtract( $siteTotal );
			$return['site_total'] = new Money( $siteTotal, $currency );
			$return['recipient_final'] = new Money( $recipientTotal->isGreaterThanZero() ? $recipientTotal : 0, $currency );
		}
		else
		{
			$return['site_total'] = $this->linePrice()->amount;
		}
		
		return $return;
	}
	
	/**
	 * Get amount for recipient (on line price)
	 *
	 * @return	Money
	 * @throws	BadMethodCallException
	 */
	public function amountForRecipient() : Money
	{
		if ( !$this->payTo )
		{
			throw new BadMethodCallException;
		}
		
		$recipientAmount = $this->recipientAmounts();
		return $recipientAmount['recipient_final'];
	}
	
	/**
	 * Image
	 *
	 * @return File|null
	 */
	public function image(): File|null
	{
		return NULL;
	}
	
	/**
	 * On Paid
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public function onPaid( Invoice $invoice ): void
	{
		
	}
	
	/**
	 * On Unpaid description
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return	array
	 */
	public function onUnpaidDescription( Invoice $invoice ): array
	{
		return array();
	}
	
	/**
	 * On Unpaid
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @param	string				$status		Status
	 * @return    void
	 */
	public function onUnpaid( Invoice $invoice, string $status ): void
	{
		
	}
	
	/**
	 * On Invoice Cancel (when unpaid)
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public function onInvoiceCancel( Invoice $invoice ): void
	{
		
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
		
	}
	
	/**
	 * Requires Billing Address
	 *
	 * @return	bool
	 * @throws	DomainException
	 */
	public function requiresBillingAddress(): bool
	{
		return in_array( 'other', explode( ',', Settings::i()->nexus_require_billing ) );
	}
	
	/**
	 * Client Area URL
	 *
	 * @return Url|string|NULL
	 */
	function url(): Url|string|null
	{
		return NULL;
	}
	
	/**
	 * ACP URL
	 *
	 * @return Url|null
	 */
	public function acpUrl(): Url|null
	{
		return NULL;
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
		/* If one is set to be associated with something in the cart and the other is set to be associated
		with an existing purchase, trying to compare those will throw an error */
		if ( isset( $item->parent ) and isset( $this->parent ) )
		{			
			if ( gettype( $this->parent ) != gettype( $item->parent ) )
			{
				return FALSE;
			}
		}
		
		/* Assume the quantities are the same */
		$cloned = clone $item;
		$cloned->quantity = $this->quantity;
		
		/* Compare */
		return ( $cloned == $this );
	}
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse		string								name			Item name
	 * @apiresponse		string								itemApp			Key for application. For example, 'nexus' for products and renewals; 'downloads' for Downloads files
	 * @apiresponse		string								itemType		Key for item type. For example, 'package' for products; 'file' for Downloads files.
	 * @apiresponse		int									itemId			The ID for the item. For example, the product ID or the file ID.
	 * @apiresponse		string								itemUrl			Any relevant URL (for example, for Downloads files, this will be the URL to view the file)
	 * @apiresponse		string								itemImage		If the item has a relevant image (for exmaple, product image, Downloads file screenshot), the URL to it
	 * @apiresponse		int									quantity		The quantity being purchased
	 * @apiresponse		\IPS\nexus\Money					itemPrice		Item price, before tax
	 * @apiresponse		\IPS\nexus\Money					linePrice		Line price, before tax
	 * @apiresponse		\IPS\nexus\Tax						taxClassId		If the item should be taxed, the Tax Class that applies
	 * @apiresponse		\IPS\nexus\Purchase\RenewalTerm		renewalTerm		If the item renews, the renewal term
	 * @apiresponse		datetime							expireDate		If the item has been set to expire at a certain date but not automatically renew, the dare it will expire
	 * @apiresponse		object								details			The values for any custom package fields
	 * @apiresponse		\IPS\nexus\Purchase					parentPurchase	If when the item has been purchased it will be a child of an existing purchase, the parent purchase
	 * @apiresponse		int									parentItem		If when the item has been purchased it will be a child of another item on the same invoice, the ID number of the item that will be the parent
	 * @apiresponse		bool								groupWithParent	If when the item has been purchased it will have its renewals grouped with its parent
	 * @apiresponse		\IPS\nexus\Customer					payTo			If the payment for this item goes to another user (for example for Downloads files), the user who will receive the payment
	 * @apiresponse		float								commission		If the payment for this item goes to another user (for example for Downloads files), the percentage of the price that will be retained by the site (in addition to fee)
	 * @apiresponse		\IPS\nexus\Money					fee				If the payment for this item goes to another user (for example for Downloads files), the fee that will be deducted by the site (in addition to commission)
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array(
			'name'				=> $this->name,
			'itemApp'			=> $this->appKey,
			'itemType'			=> $this->typeKey,
			'itemId'			=> $this->id,
			'itemUrl'			=> $this->url() ? ( (string) $this->url() ) : null,
			'itemImage'			=> $this->image() ? ( (string) $this->image()->url ) : null,
			'quantity'			=> $this->quantity,
			'itemPrice'			=> $this->price->apiOutput( $authorizedMember ),
			'linePrice'			=> $this->linePrice()->apiOutput( $authorizedMember ),
			'taxClass'			=> $this->tax?->apiOutput($authorizedMember),
			'renewalTerm'		=> isset( $this->renewalTerm ) ? $this->renewalTerm->apiOutput( $authorizedMember ) : null,
			'expireDate'		=> isset( $this->expireDate ) ? $this->expireDate->rfc3339() : null,
			'details'			=> $this->details,
			'parentPurchase'	=> ( isset( $this->parent ) and $this->parent instanceof Purchase ) ? $this->parent->apiOutput( $authorizedMember ) : null,
			'parentItem'		=> ( isset( $this->parent ) and is_int( $this->parent ) ) ? $this->parent : null,
			'groupWithParent'	=> $this->groupWithParent ?? false,
			'payTo'				=> $this->payTo?->apiOutput($authorizedMember),
			'commission'		=> $this->commission,
			'fee'				=> $this->fee ? $this->fee->apiOutput( $authorizedMember ) : null,
		);
	}
}