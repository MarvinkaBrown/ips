<?php
/**
 * @brief		Product Package
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\IPS;
use IPS\Member;
use IPS\nexus\Invoice;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\LicenseKey;
use IPS\nexus\Package as NexusPackage;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Product Package
 */
class Product extends NexusPackage
{
	/**
	 * @brief	Database Table
	 */
	protected static string $packageDatabaseTable = 'nexus_packages_products';
	
	/**
	 * @brief	Which columns belong to the local table
	 */
	protected static array $packageDatabaseColumns = array( 'p_subscription', 'p_lkey', 'p_lkey_identifier', 'p_lkey_uses', 'p_show' );
	
	/**
	 * ACP Fields
	 *
	 * @param NexusPackage $package	The package
	 * @param bool $custom		If TRUE, is for a custom package
	 * @param bool $customEdit	If TRUE, is editing a custom package
	 * @return    array
	 */
	public static function acpFormFields(NexusPackage $package, bool $custom=FALSE, bool $customEdit=FALSE ): array
	{
		$return = array();
		$formId = $package->id ? "form_{$package->id}" : 'form_new';

		$return['package_settings']['show'] = new YesNo( 'p_show', $package->type === 'product' ? $package->show : TRUE, FALSE, array( 'togglesOn' => array( "{$formId}_tab_package_client_area", "{$formId}_header_package_associations", "{$formId}_header_package_associations_desc", 'p_associate', "{$formId}_header_package_renewals", 'p_renews', 'p_lkey' ) ) );
		
		if ( !$custom )
		{		
			$return['store_permissions']['subscription'] = new YesNo( 'p_subscription', $package->type === 'product' ? !$package->subscription : TRUE );
		}
			
		$licenseKeyOptions = array();
		$licenseKeyToggles = array();
		foreach ( LicenseKey::licenseKeyTypes() as $key => $class )
		{
			$licenseKeyOptions[ mb_strtolower( $key ) ] = 'lkey_' . $key;
			$licenseKeyToggles[ mb_strtolower( $key ) ] = array( 'p_lkey_identifier', 'p_lkey_uses' );
		}
		if ( !empty( $licenseKeyOptions ) )
		{ 
			array_unshift( $licenseKeyOptions, 'lkey_none' );
			$return['package_benefits']['lkey'] = new Radio( 'p_lkey', $package->type === 'product' ? $package->lkey : 0, FALSE, array( 'options' => $licenseKeyOptions, 'toggles' => $licenseKeyToggles ), NULL, NULL, NULL, 'p_lkey' );
		}
		
		$return['package_benefits']['lkey_uses'] = new Number( 'p_lkey_uses', $package->type === 'product' ? $package->lkey_uses : -1, FALSE, array( 'unlimited' => -1 ) );
		
		$identifierOptions = array(
			'name'		=> 'lkey_identifier_name',
			'email'		=> 'lkey_identifier_email',
			'username'	=> 'lkey_identifier_username',
		);
		if ( $package->id )
		{
			foreach (CustomField::roots( NULL, NULL, array( array( Db::i()->findInSet( 'cf_packages', array( $package->id ) ) ) ) ) as $field )
			{
				$identifierOptions[ $field->id ] = $field->_title;
			}
		}
		
		$return['package_benefits']['lkey_identifier'] = new Select( 'p_lkey_identifier', $package->type === 'product' ? $package->lkey_identifier : '0', FALSE, array( 'options' => $identifierOptions, 'unlimited' => '0', 'unlimitedLang' => 'lkey_identifier_none' ), NULL, NULL, NULL, 'p_lkey_identifier' );
		
		return $return;
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$values['p_subscription'] = isset( $values['p_subscription'] ) ? !$values['p_subscription'] : FALSE;

		return parent::formatFormValues( $values );
	}
	
	/**
	 * Updateable fields
	 *
	 * @return	array
	 */
	public static function updateableFields() : array
	{
		return array_merge( parent::updateableFields(), array(
			'lkey',
			'lkey_identifier',
			'lkey_uses',
			'show'
		) );
	}
	
	/**
	 * Update existing purchases
	 *
	 * @param	Purchase	$purchase							The purchase
	 * @param array $changes							The old values
	 * @param bool $cancelBillingAgreementIfNecessary	If making changes to renewal terms, TRUE will cancel associated billing agreements. FALSE will skip that change
	 * @return    void
	 */
	public function updatePurchase(Purchase $purchase, array $changes, bool $cancelBillingAgreementIfNecessary=FALSE ): void
	{
		if ( array_key_exists( 'lkey', $changes ) )
		{
			$lKey = $purchase->licenseKey();

			if ( $lKey )
			{
				$lKey->delete();
			}

			$licenseTypes = LicenseKey::licenseKeyTypes();

			if( class_exists( $licenseTypes[ IPS::mb_ucfirst( $this->lkey ) ]) )
			{
				$class = $licenseTypes[ IPS::mb_ucfirst( $this->lkey ) ];
				$licenseKey = new $class;
				$licenseKey->identifier = $this->lkey_identifier;
				$licenseKey->purchase = $purchase;
				$licenseKey->max_uses = $this->lkey_uses;
				$licenseKey->save();
			}

		}
		elseif ( array_key_exists( 'lkey_identifier', $changes ) or array_key_exists( 'lkey_uses', $changes ) )
		{
			$licenseKey = $purchase->licenseKey();

			if( $licenseKey )
			{
				$licenseKey->identifier = $this->lkey_identifier;
				$licenseKey->max_uses = $this->lkey_uses;
				$licenseKey->save();
			}
		}
		
		if ( array_key_exists( 'show', $changes ) )
		{
			$purchase->show = $this->show;
			$purchase->save();
		}
		
		parent::updatePurchase( $purchase, $changes, $cancelBillingAgreementIfNecessary );
	}
	
	/* !Actions */
	
	/**
	 * Add To Cart
	 *
	 * @param \IPS\nexus\Invoice\Item $item			The item
	 * @param	array										$values			Values from form
	 * @param string $memberCurrency	The currency being used
	 * @return    array    Additional items to add
	 */
	public function addToCart(\IPS\nexus\Invoice\Item $item, array $values, string $memberCurrency ): array
	{
		if ( $this->subscription )
		{
			if ( $item->quantity > 1 )
			{
				Output::i()->error( 'err_subscription_qty', '1X247/2', 403, '' );
			}
			
			if ( $this->_memberHasPurchasedSubscription( Member::loggedIn() ) )
			{
				Output::i()->error( 'err_subscription_bought', '1X247/1', 403, '' );
			}
			
			if ( isset( $_SESSION['cart'] ) )
			{
				foreach ( $_SESSION['cart'] as $_item )
				{
					if ( $_item->id === $this->id )
					{
						Output::i()->error( 'err_subscription_in_cart', '1X247/3', 403, '' );
					}
				}
			}
		}
		
		return parent::addToCart( $item, $values, $memberCurrency );
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
		if ( $this->subscription and $this->_memberHasPurchasedSubscription( $member ) )
		{
			throw new DomainException( $member->language()->addToStack( 'err_subscription_bought_login', FALSE, array( 'sprintf' => array( $member->language()->addToStack( "nexus_package_{$this->id}" ) ) ) ) );
		}
		if ( ! ( $this->member_groups == "*" or !empty( ( array_intersect( explode( ",", $this->member_groups ), $member->groups ) ) ) ) )
		{
			throw new DomainException( $member->language()->addToStack( 'err_group_cant_purchase', FALSE, array( 'sprintf' => array( $member->language()->addToStack( "nexus_package_{$this->id}" ) ) ) ) );
		}
	}
	
	/**
	 * Check if a member has purchased this subscription product
	 *
	 * @param	Member	$member	The new member
	 * @return	bool
	 */
	protected function _memberHasPurchasedSubscription( Member $member ) : bool
	{
		return (bool) Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_cancelled=0 AND ps_member=?', 'nexus', 'package', $this->id, $member->member_id ) )->first();
	}
	
	/**
	 * Show Purchase Record?
	 *
	 * @return	bool
	 */
	public function showPurchaseRecord(): bool
	{
		return $this->show;
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @param Purchase $purchase
	 * @return    string
	 */
	public function acpPage( Purchase $purchase ): string
	{
		if ( $this->lkey and Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'lkeys_view' ) )
		{
			if ( $lkey = $purchase->licenseKey() )
			{
				return Theme::i()->getTemplate('purchases')->lkey( $lkey );
			}
			else
			{
				return Theme::i()->getTemplate('purchases')->noLkey( $purchase );
			}
		}

		return '';
	}
	
	/**
	 * ACP Action
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    string|null
	 */
	public function acpAction( Purchase $purchase ): string|null
	{
		switch ( Request::i()->act )
		{
			case 'lkeyReset':
				Dispatcher::i()->checkAcpPermission( 'lkeys_reset' );
				Session::i()->csrfCheck();
				
				$oldKey = NULL;
				try
				{
					if ( $old = $purchase->licenseKey() )
					{
						$oldKey = $old->key;
						$old->delete();
					}
				}
				catch ( OutOfRangeException ) { }
				
				/* Invalidate License Key Cache so old data is not loaded */
				$purchase->licenseKey = NULL;
				
				$licenseTypes = LicenseKey::licenseKeyTypes();
				$class = $licenseTypes[ IPS::mb_ucfirst( $this->lkey ) ];
				$licenseKey = new $class;
				$licenseKey->identifier = $this->lkey_identifier;
				$licenseKey->purchase = $purchase;
				$licenseKey->max_uses = $this->lkey_uses;
				$licenseKey->save();
				
				$purchase->member->log( 'lkey', array( 'type' => 'reset', 'key' => $oldKey, 'new' => $licenseKey->key, 'ps_id' => $purchase->id, 'ps_name' => $purchase->name ) );
				return null;
				
			default:
				return parent::acpAction( $purchase );
		}
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param Invoice $invoice	The invoice
	 * @return    void
	 */
	public function onPurchaseGenerated(Purchase $purchase, Invoice $invoice ): void
	{
		if ( $this->lkey )
		{
			$licenseTypes = LicenseKey::licenseKeyTypes();
			$class = $licenseTypes[ IPS::mb_ucfirst( $this->lkey ) ];
			$licenseKey = new $class;
			$licenseKey->identifier = $this->lkey_identifier;
			$licenseKey->purchase = $purchase;
			$licenseKey->max_uses = $this->lkey_uses;
			$licenseKey->save();
		}
		
		parent::onPurchaseGenerated( $purchase, $invoice );
	}
	
	/**
	 * Warning to display to admin when cancelling a purchase
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    string|null
	 */
	public function onCancelWarning( Purchase $purchase ): string|null
	{
		return NULL;
	}
}