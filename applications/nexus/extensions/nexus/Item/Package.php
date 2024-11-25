<?php
/**
 * @brief		Package
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Coupon;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Purchase as ItemPurchase;
use IPS\nexus\Money;
use IPS\nexus\Package as PackageClass;
use IPS\nexus\Package\CustomField;
use IPS\nexus\Package\Group;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Tax;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package
 */
class Package extends ItemPurchase
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'package';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'box';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'product';
	
	/**
	 * Get (can be used to override static properties like icon and title in an instance)
	 *
	 * @param	string	$k	Property
	 * @return	mixed
	 */
	public function __get( string $k ) : mixed
	{
		if ( $k === '_icon' or $k === '_title' )
		{
			try
			{
				$package = PackageClass::load( $this->id );
				return $k === '_icon' ? $package::$icon : $package::$title;
			}
			catch ( Exception ) { }
		}
		return parent::__get( $k );
	}

	/**
	 * @return bool
	 */
	public function canChangeQuantity() : bool
	{
		try
		{
			$package = PackageClass::load( $this->id );
			if( !$package->subscription )
			{
				return true;
			}

			return false;
		}
		catch( OutOfRangeException )
		{
			return parent::canChangeQuantity();
		}
	}
	
	/**
	 * Get Icon
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    string
	 */
	public static function getIcon( Purchase $purchase ): string
	{
		try
		{
			$class = PackageClass::load( $purchase->item_id );
			return $class::$icon;
		}
		catch ( OutOfRangeException )
		{
			return '';
		}
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
			$class = PackageClass::load( $purchase->item_id );
			return $class::$title;
		}
		catch ( OutOfRangeException )
		{
			return '';
		}
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
			$imageUrl = PackageClass::load( $this->id )->image;
			if ( !$imageUrl )
			{
				return NULL;
			}
			
			return File::get( 'nexus_Products', $imageUrl );
		}
		catch ( Exception ) {}
		
		return NULL;
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
			$imageUrl = PackageClass::load( $purchase->item_id )->image;
			if ( !$imageUrl )
			{
				return NULL;
			}
			
			return File::get( 'nexus_Products', $imageUrl );
		}
		catch ( Exception )
		{
			return NULL;
		}
	}
		
	/**
	 * Generate Invoice Form: First Step
	 *
	 * @param	Form	$form		The form
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public static function form( Form $form, Invoice $invoice ): void
	{	
		$form->class = 'ipsForm--vertical ipsForm--package-selector';
		$form->add( new Custom( 'invoice_products', array(), TRUE, array(
			'rowHtml'	=> function( $field )
			{
				return Theme::i()->getTemplate('invoices')->packageSelector( $field->value );
			}
		) ) );
	}
	
	/**
	 * Generate Invoice Form: Second Step
	 *
	 * @param	array				$values		Form values from previous step
	 * @param	Form	$form		The form
	 * @param	Invoice	$invoice	The invoice
	 * @return    bool
	 */
	public static function formSecondStep( array $values, Form $form, Invoice $invoice ): bool
	{
		$displayForm = FALSE;
		
		/* Do an initial loop so we know what we can associate with */
		$justSelected = array_filter( $values['invoice_products'] );
		
		/* Now do the actual loop */
		foreach ( array_filter( $values['invoice_products'] ) as $id => $qty )
		{
			$package = PackageClass::load( $id );
			$customFields = new ActiveRecordIterator( Db::i()->select( '*', 'nexus_package_fields', Db::i()->findInSet( 'cf_packages', array( $package->id ) ) ), 'IPS\nexus\Package\CustomField' );
			$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();

			for ( $i = 0; $i < $qty; $i++ )
			{							
				if ( count( $customFields ) or count( $renewOptions ) > 1 or count( $package->associablePackages() ) or method_exists( $package, 'generateInvoiceForm' ) )
				{
					$displayForm = TRUE;
					$form->addHeader( $package->_title );
					
					if ( count( $customFields ) )
					{
						foreach ( $customFields as $customField )
						{
							$field = $customField->buildHelper();
							$field->label = Member::loggedIn()->language()->addToStack( $customField->name );
							$field->name = "{$customField->name}_{$id}_{$i}";
							$form->add( $field );
						}
					}
					
					if ( count( $renewOptions ) > 1 )
					{
						$options = array();
						foreach ( $renewOptions as $k => $option )
						{
							switch ( $option['unit'] )
							{
								case 'd':
									$term = Member::loggedIn()->language()->addToStack('renew_days', FALSE, array( 'pluralize' => array( $option['term'] ) ) );
									break;
								case 'm':
									$term = Member::loggedIn()->language()->addToStack('renew_months', FALSE, array( 'pluralize' => array( $option['term'] ) ) );
									break;
								case 'y':
									$term = Member::loggedIn()->language()->addToStack('renew_years', FALSE, array( 'pluralize' => array( $option['term'] ) ) );
									break;
							}
							
							$options[ $k ] = 
								Member::loggedIn()->language()->addToStack( 'renew_option', FALSE, array( 'sprintf' => array(
								(string) new Money( $option['cost'][ $invoice->currency ]['amount'], $invoice->currency ),
								$term
							) ) );
						}
						
						$field = new Radio( "renewal_term_{$id}_{$i}", NULL, TRUE, array( 'options' => $options ) );
						$field->label = Member::loggedIn()->language()->addToStack('renewal_term');
						$form->add( $field );
					}
					
					if ( count( $package->associablePackages() ) )
					{
						$associableIds = array_keys( $package->associablePackages() );
						$associableOptions = array();
						if ( !$package->force_assoc )
						{
							$associableOptions[0] = 'no_parent';
						}
						$selected = NULL;
						foreach ( $justSelected as $k => $_qty )
						{
							if ( in_array( $k, $associableIds ) )
							{
								for ( $j = 0; $j < $_qty; $j++ )
								{
									$associableOptions['just_selected'][ "2.{$k}.{$j}" ] = PackageClass::load( $k )->_title;
									if ( $j === $i )
									{
										$selected = "2.{$k}.{$j}";
									}
								}
							}
						}
						foreach ( $invoice->items as $index => $item )
						{
							if ( in_array( $item->id, $associableIds ) )
							{
								for ( $j = 0; $j < $item->quantity; $j++ )
								{
									$name = $item->name;
									if ( count( $item->details ) )
									{
										$customFields = CustomField::roots();
										$stickyFields = array();
										foreach ( $item->details as $k => $v )
										{
											if ( $v and isset( $customFields[ $k ] ) and $customFields[ $k ]->sticky )
											{
												$stickyFields[] = $v;
											}
										}
										if ( count( $stickyFields ) )
										{
											$name .= ' (' . implode( ' &middot; ', $stickyFields ) . ')';
										}
									}
									$associableOptions['on_invoice']["0.{$index}"] = $name;
								}
							}
						}
						foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', array( array( 'ps_member=? AND ps_app=? AND ps_type=?', $invoice->member->member_id, 'nexus', 'package' ), Db::i()->in( 'ps_item_id', $associableIds ) ), 'ps_start DESC' ), 'IPS\nexus\Purchase' ) as $purchase )
						{
							$name = $purchase->name;

							if( $name == $purchase->_name )
							{
								$name .= ' (' . Member::loggedIn()->language()->addToStack( 'purchase_number', FALSE, array( 'sprintf' => array( $purchase->id ) ) ) . ')';
							}

							$associableOptions['existing_purchases'][ "1.{$purchase->id}" ] = $name;
						}
						$field = new Select( "associate_with_{$id}_{$i}", $selected, $package->force_assoc, array( 'options' => $associableOptions ) );
						$field->label = Member::loggedIn()->language()->addToStack('associate_with');
						$form->add( $field );
					}
					
					if ( method_exists( $package, 'generateInvoiceForm' ) )
					{
						$package->generateInvoiceForm( $form, "_{$id}_{$i}" );
					}
				}
			}
		}
		
		return $displayForm;
	}
	
	/**
	 * Create From Form
	 *
	 * @param	array				$values		Values from form
	 * @param	Invoice	$invoice	The invoice
	 * @return    array|Package
	 */
	public static function createFromForm( array $values, Invoice $invoice ): array|static
	{
		/* Get the packages we want to add */
		if ( isset( Request::i()->firstStep ) )
		{
			$data = json_decode( Request::i()->firstStep, TRUE );
			$data = $data['invoice_products'];
		}
		else
		{
			$data = $values['invoice_products'];
		}
		
		/* Loop them */
		$items = array();
		$itemsToBeAssociated = array();
		foreach ( array_filter( $data ) as $id => $qty )
		{
			/* Load package */
			$package = PackageClass::load( $id );
			
			/* Get the number already on the invoice for the purposes of discounts */
			$initialCount = 0;
			foreach ( $invoice->items as $_item )
			{
				if ( $_item instanceof Package and $_item->id == $id )
				{
					$initialCount += $_item->quantity;
				}
			}
			
			/* Loop for each qty */
			for ( $i = 0; $i < $qty; $i++ )
			{
				/* Custom Fields */
				$details = array();
				foreach ( $values as $k => $v )
				{
					if ( preg_match( "/nexus_pfield_(\d+)_{$id}_{$i}/", $k, $matches ) )
					{
						try
						{
							$field = CustomField::load( $matches[1] );
							$class = $field->buildHelper();
							if ( $class instanceof Upload )
							{
								$details[ $field->id ] = (string) $v;
							}
							else
							{
								$details[ $field->id ] = $class::stringValue( $v );
							}
						}
						catch ( Exception ) { }
					}
				}

				/* Base price */
				$basePrice = $package->price( $invoice->member, TRUE, TRUE, TRUE, $initialCount + $i, $invoice->currency ?: NULL );
				$price = $basePrice->amount;

				/* Adjustments based on custom fields */
				if ( $package->stock == -2 )
				{
					try
					{
						$chosenOption = Db::i()->select( '*', 'nexus_product_options', array( 'opt_package=? AND opt_values=?', $package->id, json_encode( $package->optionValues( $details ) ) ) )->first();
						$basePriceAdjustments = json_decode( $chosenOption['opt_base_price'], TRUE );
						if ( isset( $basePrice->currency ) )
						{
							$price = $price->add( new Number( number_format( $basePriceAdjustments[ $basePrice->currency ], Money::numberOfDecimalsForCurrency( $basePrice->currency ), '.', '' ) ) );
						}
					}
					catch ( UnderflowException ) {}
				}
				
				/* Work out renewal term */
				$renewalTerm = NULL;
				$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();
				if ( count( $renewOptions ) )
				{
					if ( count( $renewOptions ) === 1 )
					{
						$chosenRenewOption = array_pop( $renewOptions );
					}
					else
					{
						$chosenRenewOption = $renewOptions[ $values["renewal_term_{$id}_{$i}"] ];
					}

					$renewalPrice = new Money( $chosenRenewOption['cost'][ $invoice->currency ]['amount'], $invoice->currency );
					$renewalAmount = $renewalPrice->amount;

					/* Adjustments based on custom fields */
					if ( isset( $chosenOption ) )
					{
						$renewalPriceAdjustments = json_decode( $chosenOption['opt_renew_price'], TRUE );
						if ( isset( $renewalPrice->currency ) )
						{
							$renewalAmount = $renewalAmount->add( new Number( number_format( $renewalPriceAdjustments[ $renewalPrice->currency ], Money::numberOfDecimalsForCurrency( $renewalPrice->currency ), '.', '' ) ) );
						}
					}

					$renewalTerm = new RenewalTerm( new Money( $renewalAmount, $invoice->currency ), new DateInterval( 'P' . $chosenRenewOption['term'] . mb_strtoupper( $chosenRenewOption['unit'] ) ), $package->tax ? Tax::load( $package->tax ) : NULL, FALSE,$package->grace_period ? new DateInterval( 'P' . $package->grace_period . 'D' ) : NULL );

					if ( $chosenRenewOption['add'] )
					{
						$price = $price->add( $renewalTerm->cost->amount );
					}
				}
				
				/* Create item */
				$item = new static( Member::loggedIn()->language()->get( 'nexus_package_' . $package->id ), new Money( $price, $invoice->currency ) );
				$item->renewalTerm = $renewalTerm;
				$item->id = $package->id;
				$item->tax = $package->tax ? Tax::load( $package->tax ) : NULL;
				if ( $package->methods and $package->methods != '*' )
				{
					$item->paymentMethodIds = explode( ',', $package->methods );
				}
				if ( $package->group_renewals )
				{
					$item->groupWithParent = TRUE;
				}
				$item->details = $details;
				
				/* Associations */
				if ( isset( $values["associate_with_{$id}_{$i}"] ) and $values["associate_with_{$id}_{$i}"] )
				{
					$exploded = explode( '.', $values["associate_with_{$id}_{$i}"] );
					switch ( $exploded[0] )
					{
						case '0':
							$item->parent = (int) $exploded[1];
							break;
						case '1':
							$item->parent = Purchase::load( $exploded[1] );
							break;
						case '2':
							$itemsToBeAssociated["{$id}.{$i}"] = "{$exploded[1]}.{$exploded[2]}";
							break;
					}
				}
				
				/* Do any package-sepcific modifications */
				$package->acpAddToInvoice( $item, $values, "_{$id}_{$i}", $invoice );
								
				/* Add it */
				$items["{$id}.{$i}"] = $item;
			}
		}
		
		/* Sort out any associations */
		$added = array();
		foreach( $itemsToBeAssociated as $itemKey => $associateKey )
		{
			if ( !array_key_exists( $associateKey, $added ) )
			{
				$added[ $associateKey ] = $invoice->addItem( $items[ $associateKey ] );
			}
			$items[ $itemKey ]->parent = $added[ $associateKey ];
		}
						
		/* Group wherever we can */
		$itemsToAdd = array();
		foreach ( $items as $key => $item )
		{
			if ( !array_key_exists( $key, $added ) )
			{
				/* Is this the same as any of the other items? */
				foreach ( $itemsToAdd as $_item )
				{
					if ( $_item->isSameAsOtherItem( $item ) )
					{
						$_item->quantity++;
						continue 2;
					}
				}
				
				/* Or anything on the invoice? */
				foreach ( $invoice->items as $k => $_item )
				{
					if ( $_item->isSameAsOtherItem( $item ) )
					{
						$invoice->changeItem( $k, array( 'quantity' => $_item->quantity + 1 ) );
						continue 2;
					}
				}
				
				/* Nope, give it it's own entry */
				$itemsToAdd[] = $item;
			}
		}
		
		/* Return */
		return $itemsToAdd;
	}
	
	/**
	 * Get additional name info
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    array
	 */
	public static function getPurchaseNameInfo( Purchase $purchase ): array
	{
		try
		{
			return PackageClass::load( $purchase->item_id )->getPurchaseNameInfo( $purchase );
		}
		catch ( OutOfRangeException )
		{
			return array();
		}
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @param	Purchase	$purchase
	 * @return    string
	 */
	public static function acpPage( Purchase $purchase ): string
	{
		try
		{
			return PackageClass::load( $purchase->item_id )->acpPage( $purchase );
		}
		catch ( OutOfRangeException )
		{
			return '';
		}
	}
	
	/**
	 * Get ACP Page Buttons
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param	Url		$url		The page URL
	 * @return    array
	 */
	public static function acpButtons( Purchase $purchase, Url $url ): array
	{
		try
		{
			return PackageClass::load( $purchase->item_id )->acpButtons( $purchase, $url );
		}
		catch ( OutOfRangeException )
		{
			return array();
		}
	}
	
	/**
	 * ACP Action
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    string|null
	 */
	public static function acpAction( Purchase $purchase ): string|null
	{
		try
		{
			return PackageClass::load( $purchase->item_id )->acpAction( $purchase );
		}
		catch ( OutOfRangeException )
		{
			return null;
		}
	}
	
	/** 
	 * ACP Edit Form
	 *
	 * @param	Purchase				$purchase	The purchase
	 * @param	Form				$form	The form
	 * @param	RenewalTerm|null	$renewals	The renewal term
	 * @return	void
	 */
	public static function acpEdit( Purchase $purchase, Form $form, ?RenewalTerm $renewals ) : void
	{
		$form->addHeader('nexus_purchase_settings');
		parent::acpEdit( $purchase, $form, $renewals );
		
		try
		{
			PackageClass::load( $purchase->item_id )->acpEdit( $purchase, $form, $renewals );
		}
		catch ( OutOfRangeException ) { }
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
		try
		{
			PackageClass::load( $purchase->item_id )->acpEditSave( $purchase, $values );
		}
		catch ( OutOfRangeException ) { }
		
		parent::acpEditSave( $purchase, $values );
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
		$products = null;
		if( $current and $current !== '*' )
		{
			if( isset( $current[ static::$title ]['products'] ) and $current[ static::$title ]['products'] !== 0 )
			{
				foreach( $current[ static::$title ]['products'] as $id )
				{
					try
					{
						$products[] = PackageClass::load( $id );
					}
					catch( OutOfRangeException ){}
				}
			}
		}

		return [
			new Node( 'c_nexus_package', $products ?? 0, FALSE, array(
				'class' => Group::class,
				'multiple' => true,
				'zeroVal' => 'no_restriction',
				'permissionCheck' => function( $node ){
					return !( $node instanceof Group );
				}
			), null, null, null, 'c_nexus_package' )
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
		if( isset( $data[ static::$title ]['products'] ) and is_array( $data[ static::$title ]['products'] ) )
		{
			foreach( $data[ static::$title ]['products'] as $packageId )
			{
				$return[] = Member::loggedIn()->language()->addToStack( 'nexus_package_' . $packageId );
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
		if( !isset( $values['c_nexus_package'] ) )
		{
			return null;
		}

		if( array_key_exists( 'c_products', $values ) and !empty( $values['c_products'] ) and !in_array( static::$title, $values['c_products'] ) )
		{
			unset( $values['c_nexus_package'] );
			return null;
		}

		if( is_array( $values['c_nexus_package'] ) )
		{
			$products = [];
			foreach ( $values['c_nexus_package'] as $package )
			{
				$products[] = $package->id;
			}
		}
		else
		{
			$products = 0;
		}

		unset( $values['c_nexus_package'] );
		return $products ? array( 'products' => $products ) : null;
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

		if( isset( $data[ static::$title ]['products'] ) )
		{
			if( $data[ static::$title ]['products'] === 0 )
			{
				return true;
			}

			return in_array( $this->id, $data[ static::$title ]['products'] );
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
			return PackageClass::load( $purchase->item_id )->clientAreaPage( $purchase );
		}
		catch ( OutOfRangeException )
		{
			return array( 'packageInfo' => '', 'purchaseInfo' => '' );
		}
	}
	
	/**
	 * Client Area Action
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function clientAreaAction( Purchase $purchase ): void
	{
		try
		{
			PackageClass::load( $purchase->item_id )->clientAreaAction( $purchase );
		}
		catch ( OutOfRangeException ){}
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
			$package = PackageClass::load( $purchase->item_id );
			if ( $package->methods and $package->methods != '*' )
			{
				return explode( ',', $package->methods );
			}
			else
			{
				return NULL;
			}
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * On Paid
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public function onPaid( Invoice $invoice ): void
	{
		try
		{
			$package = PackageClass::load( $this->id );
			$package->onPaid( $invoice );
			$invoice->member->achievementAction( 'nexus', 'Package', $package );
		}
		catch ( OutOfRangeException ){}
	}
	
	/**
	 * On Unpaid description
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return	array
	 */
	public function onUnpaidDescription( Invoice $invoice ) : array
	{
		try
		{
			return PackageClass::load( $this->id )->onUnpaidDescription( $invoice );
		}
		catch ( OutOfRangeException )
		{
			return array();
		}
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
		try
		{
			PackageClass::load( $this->id )->onUnpaid( $invoice, $status );
		}
		catch ( OutOfRangeException ){}
	}
	
	/**
	 * On Invoice Cancel (when unpaid)
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public function onInvoiceCancel( Invoice $invoice ): void
	{
		try
		{
			PackageClass::load( $this->id )->onInvoiceCancel( $invoice );
		}
		catch ( OutOfRangeException ){}
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
		try
		{
			PackageClass::load( $this->id )->memberCanPurchase( $member );
		}
		catch ( OutOfRangeException )
		{
			throw new DomainException;
		}
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
			PackageClass::load( $purchase->item_id )->onPurchaseGenerated( $purchase, $invoice );
		}
		catch ( OutOfRangeException ){}
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
			PackageClass::load( $purchase->item_id )->onRenew($purchase, $cycles);
		}
		catch ( OutOfRangeException ){}
	}
	
	/**
	 * On Expiration Date Change
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onExpirationDateChange( Purchase $purchase ): void
	{
		try
		{
			PackageClass::load( $purchase->item_id )->onExpirationDateChange( $purchase );
		}
		catch ( OutOfRangeException ){}
	}
	
	/**
	 * On expire soon
	 * If returns TRUE, the normal expire warning email will not be sent
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    bool
	 */
	public static function onExpireWarning( Purchase $purchase ): bool
	{
		try
		{
			return PackageClass::load( $purchase->item_id )->onExpireWarning( $purchase );
		}
		catch ( OutOfRangeException )
		{
			return FALSE;
		}
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
			PackageClass::load( $purchase->item_id )->onExpire( $purchase );
		}
		catch ( OutOfRangeException ){}
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
			PackageClass::load( $purchase->item_id )->onCancel( $purchase );
		}
		catch ( OutOfRangeException ){}
	}
	
	/**
	 * Warning to display to admin when cancelling a purchase
	 *
	 * @param	Purchase $purchase
	 * @return    string|null
	 */
	public static function onCancelWarning( Purchase $purchase ): string|null
	{
		try
		{
			return PackageClass::load( $purchase->item_id )->onCancelWarning( $purchase );
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function onDelete( Purchase $purchase ): void
	{
		try
		{
			PackageClass::load( $purchase->item_id )->onDelete( $purchase );
		}
		catch ( OutOfRangeException ){}
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
			PackageClass::load( $purchase->item_id )->onReactivate( $purchase );
		}
		catch ( OutOfRangeException ){}
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
			PackageClass::load( $purchase->item_id )->onTransfer( $purchase, $newCustomer );
		}
		catch ( OutOfRangeException ){}
	}
	
	/**
	 * Purchase can be renewed?
	 *
	 * @param	Purchase $purchase	The purchase
	 * @return    boolean
	 */
	public static function canBeRenewed( Purchase $purchase ): bool
	{
		return TRUE;
	}
	
	/**
	 * Can Renew Until
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param	bool				$admin		If TRUE, is for ACP. If FALSE, is for front-end.
	 * @return	DateTime|bool	TRUE means can renew as much as they like. FALSE means cannot renew at all. \IPS\DateTime means can renew until that date
	 */
	public static function canRenewUntil( Purchase $purchase, bool $admin = FALSE ) : DateTime|bool
	{
		try
		{
			return PackageClass::load( $purchase->item_id )->canRenewUntil( $purchase, $admin );
		}
		catch ( OutOfRangeException )
		{
			return FALSE;
		}
	}
	
	/**
	 * Show Purchase Record?
	 *
	 * @return    bool
	 */
	public function showPurchaseRecord(): bool
	{
		try
		{
			return PackageClass::load( $this->id )->showPurchaseRecord();
		}
		catch ( OutOfRangeException )
		{
			return FALSE;
		}
	}
}