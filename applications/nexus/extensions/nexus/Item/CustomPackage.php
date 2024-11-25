<?php
/**
 * @brief		Custom Package
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		07 Aug 2014
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\IPS;
use IPS\Member;
use IPS\Member\Group;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Package as PackageClass;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\LicenseKey;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Tax;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use StdClass;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom Package
 */
class CustomPackage extends \IPS\nexus\extensions\nexus\Item\Package
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'custom';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'asterisk';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'custom';

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
			$package = PackageClass::load( $purchase->item_id );
			return array(
				'packageInfo' => $package->page,
				'purchaseInfo' => ''
			);
		}
		catch ( OutOfRangeException )
		{
			return array( 'packageInfo' => '', 'purchaseInfo' => '' );
		}
	}
	
	/**
	 * Generate Invoice Form
	 *
	 * @param	Form	$form		The form
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public static function form( Form $form, Invoice $invoice ): void
	{
		$groups = array();
		foreach ( Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		
		$types = array();
		$typeFields = array();
		$typeFieldToggles = array();
		$formId = 'form_new';
		foreach ( Package::packageTypes() as $key => $class )
		{
			$forceShow = TRUE;
			$types[ mb_strtolower( $key ) ] = 'p_type_' . $key;

			/* @var Package $class */
			foreach ( $class::acpFormFields( new $class, TRUE ) as $group => $fields )
			{
				foreach ( $fields as $field )
				{
					if ( $field->name === 'p_show' )
					{
						$forceShow = FALSE;
					}							
					if ( !$field->htmlId )
					{
						$field->htmlId = $field->name;
					}

					$typeFieldToggles[ mb_strtolower( $key ) ][] = $field->htmlId;
					$typeFields[ $group ][] = $field;
				}
			}
			
			if ( $forceShow )
			{
				$typeFieldToggles[ mb_strtolower( $key ) ] = array_merge( $typeFieldToggles[mb_strtolower($key)] ?? array(), array( "{$formId}_tab_package_client_area", "{$formId}_header_package_associations", "{$formId}_header_package_associations_desc", 'p_associate', "{$formId}_header_package_renewals", 'p_renews', 'p_lkey' ) );
			}
		}
		
		$form->addTab('package_settings');
		$form->add( new Radio( 'p_type', 'product', TRUE, array( 'options' => $types, 'toggles' => $typeFieldToggles ) ) );
		$form->add( new Text( 'p_name', NULL, TRUE ) );
		
		foreach ( $typeFields['package_settings'] as $field )
		{
			$form->add( $field );
		}
		
		$form->addTab( 'package_pricing' );
		$form->add( new Number( 'p_base_price', 0, TRUE, array( 'decimals' => TRUE ), NULL, NULL, $invoice->currency ) );
		$form->add( new Node( 'p_tax', 0, FALSE, array( 'class' => 'IPS\nexus\Tax', 'zeroVal' => 'do_not_tax' ) ) );
		$form->add( new YesNo( 'p_renews', FALSE, FALSE, array( 'togglesOn' => array( 'p_renew_options', 'p_renew' ) ), NULL, NULL, NULL, 'p_renews' ) );
		$form->add( new \IPS\nexus\Form\RenewalTerm( 'p_renew_options', NULL, FALSE, array( 'currency' => $invoice->currency ), NULL, NULL, NULL, 'p_renew_options' ) );
		$form->add( new YesNo( 'p_renew', FALSE, FALSE, array( 'togglesOn' => array( 'p_renewal_days', 'p_renewal_days_advance' ) ), NULL, NULL, NULL, 'p_renew' ) );
		$form->add( new Number( 'p_renewal_days', -1, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'any_time' ), NULL, NULL, Member::loggedIn()->language()->addToStack('days_before_expiry'), 'p_renewal_days' ) );
		$form->add( new Interval( 'p_renewal_days_advance', -1, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => -1 ), NULL, NULL, NULL, 'p_renewal_days_advance' ) );
		
		$form->addTab( 'package_benefits' );
		unset( $groups[ Settings::i()->guest_group ] );
		$form->add( new Select( 'p_primary_group', '*', FALSE, array( 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_primary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new YesNo( 'p_return_primary', TRUE, FALSE, array(), NULL, NULL, NULL, 'p_return_primary' ) );
		$form->add( new Select( 'p_secondary_group', '*', FALSE, array( 'options' => $groups, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_secondary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new YesNo( 'p_return_secondary', TRUE, FALSE, array(), NULL, NULL, NULL, 'p_return_secondary' ) );

		foreach ( $typeFields['package_benefits'] as $field )
		{
			$form->add( $field );
		}
		
		$form->addTab('package_client_area_display');
		$form->add( new Editor( 'p_page', NULL, FALSE, array(
			'app'			=> 'nexus',
			'key'			=> 'Admin',
			'autoSaveKey'	=> "nexus-new-pkg-pg",
			'attachIds'		=> NULL, 'minimize' => 'p_page_placeholder'
		), NULL, NULL, NULL, 'p_desc_editor' ) );
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
		$types		= Package::packageTypes();
		$classname	= $types[ IPS::mb_ucfirst( $values['p_type'] ) ];
		
		if ( method_exists( $classname, 'generateInvoiceForm' ) )
		{
			if ( isset( Request::i()->customPackageId ) )
			{
				$package = Package::load( Request::i()->customPackageId );
			}
			else
			{
				$package = static::_createPackage( $values, $invoice );
			}
			
			$form->hiddenValues['customPackageId'] = $package->id;
			$package->generateInvoiceForm( $form, '' );
			
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Create the package
	 *
	 * @param	array				$values		Values from form
	 * @param	Invoice	$invoice	The invoice
	 * @return	Package
	 */
	protected static function _createPackage( array $values, Invoice $invoice ) : Package
	{
		/* Init */
		$package = new Package;
		$package->custom = $invoice->member->member_id;

		/* Figure out tax */
		$tax = NULL;

		try
		{
			if( $values['p_tax'] )
			{
				$tax = $values['p_tax'];
			}
		}
		catch( OutOfRangeException ){}

		/* Set default values for stuff we didn't have */
		$values['p_member_groups'] = array();
		$values['p_notify'] = array();
		$values['p_usergroup_discounts'] = array();
		$values['p_loyalty_discounts'] = array();
		$values['p_bulk_discounts'] = array();
		$values['p_images'] = array();
		$values['p_group'] = new StdClass;
		$values['p_group']->id = 0;
		$renewTerm = $values['p_renews'] ? 
			new RenewalTerm(
				new Money( Request::i()->p_renew_options['amount'], ( isset( Request::i()->p_renew_options['currency'] ) ) ? Request::i()->p_renew_options['currency'] : $invoice->currency ),
				new DateInterval( 'P' . Request::i()->p_renew_options['term'] . mb_strtoupper( Request::i()->p_renew_options['unit'] ) ),
				$tax,
				FALSE 
			) : NULL;
		$values['p_renew_options'] = array();
		
		/* Save */
		$package->saveForm( $package->formatFormValues( $values ) );
		
		/* Custom-specific */
		$package = Package::load( $package->id );
		$package->name = $values['p_name'];
		$package->base_price = json_encode( array( $invoice->currency => array( 'amount' => $values['p_base_price'], 'currency' => $invoice->currency ) ) );
		if ( $values['p_renews'] )
		{
			$term = $renewTerm->getTerm();
			$package->renew_options = json_encode( array( array(
					'cost'	=> $renewTerm->cost,
					'term'	=> $term['term'],
					'unit'	=> $term['unit'],
					'add'	=> FALSE
				) ) );
		}
		else
		{
			$package->renew_options = NULL;
		}
		$package->store = 0;
		$package->page = $values['p_page'];
		$package->save();

		File::claimAttachments( 'nexus-new-pkg-pg', $package->id, null, 'custom-pg' );
		
		/* Return */
		return $package;
	}
	
	/**
	 * Create From Form
	 *
	 * @param	array				$values		Values from form
	 * @param	Invoice	$invoice	The invoice
	 * @return    array|static
	 */
	public static function createFromForm( array $values, Invoice $invoice ): array|static
	{		
		/* Create the package */
		$package = null;
		if ( isset( Request::i()->customPackageId ) )
		{
			try
			{
				$package = Package::load( Request::i()->customPackageId );
			}
			catch( OutOfRangeException ){}
		}

		if( $package === null )
		{
			$package = static::_createPackage( $values, $invoice );
		}

		/* Figure out tax */
		$tax = NULL;

		try
		{
			if( $package->tax )
			{
				$tax = Tax::load( $package->tax );
			}
		}
		catch( OutOfRangeException ){}
				
		/* Work stuff out */
		$basePrice = json_decode( $package->base_price, TRUE );
		if ( $package->renew_options )
		{
			$renewTerm = json_decode( $package->renew_options, TRUE );
			$renewTerm = array_pop( $renewTerm );
			$renewTerm = new RenewalTerm( new Money( $renewTerm['cost']['amount'], $renewTerm['cost']['currency'] ), new DateInterval( 'P' . $renewTerm['term'] . mb_strtoupper( $renewTerm['unit'] ) ), $tax, FALSE );
		}
		else
		{
			$renewTerm = NULL;
		}
				
		/* Now create an item */
		$item = new static( $package->name, new Money( $basePrice[ $invoice->currency ]['amount'], $invoice->currency ) );
		$item->renewalTerm = $renewTerm;
		$item->quantity = 1;
		$item->id = $package->id;
		$item->tax = $tax;
		$package->acpAddToInvoice( $item, $values, '', $invoice );
		
		/* And return */
		return $item;
	}
	
	/** 
	 * ACP Edit Form
	 *
	 * @param	Purchase				$purchase	The purchase
	 * @param	Form				$form		The form
	 * @param	RenewalTerm|null	$renewals	The renewal term
	 * @return	void
	 */
	public static function acpEdit( Purchase $purchase, Form $form, ?RenewalTerm $renewals ) : void
	{
		$form->addTab( 'basic_settings' );
		parent::acpEdit( $purchase, $form, $renewals );
		
		$package = Package::load( $purchase->item_id );
		$typeFields = $package->acpFormFields( $package, TRUE, TRUE );
		
		$groups = array();
		foreach ( Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		
		if ( isset( $typeFields['package_settings'] ) )
		{
			foreach ( $typeFields['package_settings'] as $field )
			{
				$form->add( $field );
			}
		}
		
		$form->add( new YesNo( 'p_renew', $package->renewal_days != 0, FALSE, array( 'togglesOn' => array( 'p_renewal_days', 'p_renewal_days_advance' ) ), NULL, NULL, NULL, 'p_renew' ) );
		$form->add( new Number( 'p_renewal_days', $package->renewal_days, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'any_time' ), NULL, NULL, Member::loggedIn()->language()->addToStack('days_before_expiry'), 'p_renewal_days' ) );
		$form->add( new Interval( 'p_renewal_days_advance', $package->renewal_days_advance, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => -1 ), NULL, NULL, NULL, 'p_renewal_days_advance' ) );

		$form->addTab( 'package_benefits' );
		unset( $groups[ Settings::i()->guest_group ] );
		$form->add( new Select( 'p_primary_group', $package->primary_group ?: '*', FALSE, array( 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_primary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new YesNo( 'p_return_primary', $package->return_primary, FALSE, array(), NULL, NULL, NULL, 'p_return_primary' ) );
		$form->add( new Select( 'p_secondary_group', $package->secondary_group ? explode( ',', $package->member_groups ) : '*', FALSE, array( 'options' => $groups, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_secondary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new YesNo( 'p_return_secondary', $package->return_secondary, FALSE, array(), NULL, NULL, NULL, 'p_return_secondary' ) );

		if ( isset( $typeFields['package_benefits'] ) )
		{
			foreach ( $typeFields['package_benefits'] as $field )
			{
				$form->add( $field );
			}
		}
		
		$form->addTab('package_client_area_display');
		$form->add( new Editor( 'p_page', $package->page, FALSE, array(
			'app'			=> 'nexus',
			'key'			=> 'Admin',
			'autoSaveKey'	=> "nexus-new-pkg-pg",
			'attachIds'		=> [ $package->id, null, 'custom-pg' ],
			'minimize' => 'p_page_placeholder'
		), NULL, NULL, NULL, 'p_desc_editor' ) );
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
		
		$package = Package::load( $purchase->item_id );
		
		$resetLkey = FALSE;
		$updateLkey = FALSE;
		$deleteLkey = FALSE;
		
		$package->show = $values['p_show'];
		$package->renewal_days = $values['p_renew'] ? $values['p_renewal_days'] : 0;
		$package->renewal_days_advance = $values['p_renew'] ? $values['p_renewal_days_advance'] : 0;
		$package->primary_group = $values['p_primary_group'] == '*' ? 0 : $values['p_primary_group'];
		$package->return_primary = $values['p_return_primary'];
		$package->secondary_group = $values['p_secondary_group'] == '*' ? '*' : implode( ',', $values['p_secondary_group'] );
		$package->return_secondary = $values['p_return_secondary'];
		
		if ( $values['p_lkey'] != $package->lkey )
		{
			$package->lkey = $values['p_lkey'];
			
			if ( $values['p_lkey'] )
			{
				$resetLkey = TRUE;
			}
			else
			{
				$deleteLkey = TRUE;
			}
		}
		if ( $values['p_lkey_identifier'] != $package->lkey_identifier )
		{
			$package->lkey_identifier = $values['p_lkey_identifier'];
			$updateLkey = TRUE;
		}
		if ( $values['p_lkey_uses'] != $package->lkey_uses )
		{
			$package->lkey_uses = $values['p_lkey_uses'];
			$updateLkey = TRUE;
		}
		
		$package->page = $values['p_page'];
		$package->save();

		File::claimAttachments( 'nexus-new-pkg-pg', $package->id, null, 'custom-pg' );

		if ( $resetLkey or $updateLkey or $deleteLkey )
		{
			$licenseTypes = LicenseKey::licenseKeyTypes();

			if ( $resetLkey or $deleteLkey )
			{
				try
				{
					$purchase->licenseKey()->delete();
				}
				catch ( OutOfRangeException ) { }
				
				if ( $resetLkey )
				{
					$class = $licenseTypes[ IPS::mb_ucfirst( $package->lkey ) ];
					$licenseKey = new $class;
				}
			}
			elseif ( $updateLkey )
			{
				try
				{
					$licenseKey = $purchase->licenseKey();
				}
				catch ( OutOfRangeException )
				{
					$class = $licenseTypes[ IPS::mb_ucfirst( $package->lkey ) ];
					$licenseKey = new $class;
				}
			}
			
			if ( isset( $licenseKey ) and ( $resetLkey or $updateLkey ) )
			{
				$licenseKey->identifier = $package->lkey_identifier;
				$licenseKey->purchase = $purchase;
				$licenseKey->max_uses = $package->lkey_uses;
				$licenseKey->save();
			}
		} 
	}

	/**
	 * Additional elements that will be used to create coupons
	 * Also used on the Commission Rules
	 *
	 * @param array|string|null $current
	 * @return array
	 */
	public static function customFormElements( array|string|null $current =null ) : array
	{
		/* Intentional override so that this doesn't show up in the coupon form */
		return [];
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
		/* Intentional override so that this doesn't show up in the coupon form */
		return null;
	}
	
	/**
	 * Show Purchase Record?
	 *
	 * @return    bool
	 */
	public function showPurchaseRecord(): bool
	{
		return Package::load( $this->id )->showPurchaseRecord();
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
		// Custom packages do not have any purchase restrictions
	}
}