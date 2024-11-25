<?php
/**
 * @brief		Advertisement Package
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\AdminNotification;
use IPS\core\Advertisement;
use IPS\Db;
use IPS\Extensions\AdvertisementLocationsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\nexus\Invoice;
use IPS\nexus\Package;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Advertisement Package
 */
class Ad extends Package
{
	/**
	 * @brief	Database Table
	 */
	protected static string $packageDatabaseTable = 'nexus_packages_ads';
	
	/**
	 * @brief	Which columns belong to the local table
	 */
	protected static array $packageDatabaseColumns = array( 'p_locations', 'p_exempt', 'p_expire', 'p_expire_unit', 'p_max_height', 'p_max_width', 'p_settings' );
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'newspaper';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'advertisement';
		
	/* !ACP Package Form */
	
	/**
	 * ACP Fields
	 *
	 * @param	Package	$package	The package
	 * @param bool $custom		If TRUE, is for a custom package
	 * @param bool $customEdit	If TRUE, is editing a custom package
	 * @return    array
	 */
	public static function acpFormFields( Package $package, bool $custom=FALSE, bool $customEdit=FALSE ): array
	{
		$return = array();
		
		if ( !$customEdit ) // If we're editing a custom package, they can get to this by editing the advertisement
		{
			$currentValues = ( $package->id AND $package->settings ) ? json_decode( $package->settings, true ) : array();

			/* Add the location fields, remember to call extensions for additional locations.
				Array format: location => array of toggle fields to show */
			$defaultLocations	= array(
				'ad_global_header'	=> array( 'ad_apps' ),
				'ad_global_footer'	=> array( 'ad_apps' ),
				'ad_sidebar'		=> array( 'ad_apps' ),
			);

			$settingFields = [];

			/* Add a field to restrict by app */
			$settingFields[] = new Node( 'ad_apps', $currentValues['ad_apps'] ?? 0, false, array(
				'class' => Application::class,
				'multiple' => true,
				'zeroVal' => 'all',
				'subnodes' => false
			), null, null, null, 'ad_apps' );

			/* Now grab ad location extensions */
			foreach ( Application::allExtensions( 'core', 'AdvertisementLocations', FALSE, 'core' ) as $key => $extension )
			{
				/* @var AdvertisementLocationsAbstract $extension */
				$result	= $extension->getSettings( $currentValues );

				$defaultLocations = array_merge( $defaultLocations, $result['locations'] );

				/* If this is a list view, add setting fields for interaval and repeat */
				if( $extension::$listView )
				{
					$settingFields[] = new Number( 'ad_view_number', ( isset( $currentValues['ad_view_number'] ) AND $currentValues['ad_view_number'] ) ? $currentValues['ad_view_number'] : 1, FALSE, array( 'min' => 1 ), NULL, NULL, Member::loggedIn()->language()->addToStack('ad_view_number_suffix'), 'ad_view_number' );

					$settingFields[] = new Number( 'ad_view_repeat', $currentValues['ad_view_repeat'] ?? 1, FALSE, array( 'unlimited' => -1 ), NULL, NULL, Member::loggedIn()->language()->addToStack('ad_view_repeat_suffix'), 'ad_view_repeat' );

					foreach( $result['locations'] as $location => $toggles )
					{
						$defaultLocations[$location][] = 'ad_view_number';
						$defaultLocations[$location][] = 'ad_view_repeat';
					}
				}

				if( isset( $result['settings'] ) )
				{
					$settingFields = $settingFields + $result['settings'];
				}
			}

			/* Add the locations to the form, and make sure the toggles get set properly */
			$locations = array();
			if ( $package->id )
			{
				$locations = $package->locations ? explode( ',', $package->locations ) : [];
				$locations = array_intersect( $locations, array_keys( $defaultLocations ) );
			}

			/* Make locations optional so that we can have ads for widgets only */
			$return['package_settings']['p_locations'] = new CheckboxSet( 'p_locations',
				$locations,
				false,
				array(
					'options'	=> array_combine( array_keys( $defaultLocations ), array_keys( $defaultLocations ) ),
					'toggles'	=> $defaultLocations,
					'noDefault' => true
				),
				NULL,
				NULL,
				NULL,
				'p_locations'
			);

			foreach( $settingFields as $settingField )
			{
				$return['package_settings'][ $settingField->name ] = $settingField;
			}

			$return['package_settings']['p_exempt'] = new CheckboxSet( 'p_exempt', ( $package->id and $package->exempt ) ? ( $package->exempt == '*' ? '*' : explode( ',', $package->exempt ) ) : '*', FALSE, array( 'options' => Group::groups(), 'parse' => 'normal', 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'everyone', 'impliedUnlimited' => TRUE ) );
	
			$return['package_settings']['p_expire'] = new Custom( 'p_expire', array( 'value' => ( $package->id and $package->expire ) ? $package->expire : -1, 'type' => ( $package->id ) ? $package->expire_unit : 'i' ), FALSE, array(
				'getHtml'	=> function( $element )
				{
					return Theme::i()->getTemplate( 'promotion', 'core' )->imageMaximums( $element->name, $element->value['value'], $element->value['type'] );
				},
				'formatValue' => function( $element )
				{
					if( !is_array( $element->value ) AND $element->value == -1 )
					{
						return array( 'value' => -1, 'type' => 'i' );
					}
	
					return array( 'value' => $element->value['value'], 'type' => $element->value['type'] );
				}
			) );
			
			if ( !$custom )
			{
				$return['package_settings']['p_max_dims'] = new WidthHeight( 'p_max_dims', ( $package->id and $package->width ) ? array( $package->max_width, $package->max_height ) : null, FALSE, array( 'unlimited' => array( 0, 0 ), 'resizableDiv' => FALSE ) );
			}
		}
				
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
		if( isset( $values['p_exempt'] ) )
		{
			$values['p_exempt'] = is_array( $values['p_exempt'] ) ? implode( ',', $values['p_exempt'] ) : $values['p_exempt'];
		}
				
		if ( isset( $values['p_expire'] ) and is_array( $values['p_expire'] ) )
		{
			$values['expire'] = intval( $values['p_expire']['value'] );
			$values['expire_unit'] = $values['p_expire']['type'];
			unset( $values['p_expire'] );
		}
		
		if ( isset( $values['p_max_dims'] ) )
		{
			$values['max_width'] = (int) $values['p_max_dims'][0];
			$values['max_height'] = (int) $values['p_max_dims'][1];
			unset( $values['p_max_dims'] );
		}
		
		if ( isset( $values['p_locations'] ) )
		{
			$values['p_locations'] = is_array( $values['p_locations'] ) ? implode( ',', $values['p_locations'] ) : '';
		}

		$additionalSettings	= array(
			'ad_apps' => ( isset( $values['ad_apps'] ) AND $values['ad_apps'] != 0 ) ? array_keys( $values['ad_apps'] ) : 0
		);

		foreach ( Application::allExtensions( 'core', 'AdvertisementLocations', FALSE, 'core' ) as $key => $extension )
		{
			/* @var AdvertisementLocationsAbstract $extension */
			if( $extension::$listView )
			{
				$additionalSettings['ad_view_number'] = $values['ad_view_number'] ?? 1;
				$additionalSettings['ad_view_repeat'] = $values['ad_view_repeat'] ?? 0;
			}

			$settings	= $extension->parseSettings( $values );

			$additionalSettings	= array_merge( $additionalSettings, $settings );

			/* Unset any elements that were added */
			$result = $extension->getSettings( $values );
			if( isset( $result['settings'] ) )
			{
				foreach( $result['settings'] as $settingField )
				{
					if( isset( $values[ $settingField->name ] ) )
					{
						unset( $values[ $settingField->name ] );
					}
				}
			}
		}

		$values['p_settings'] = json_encode( $additionalSettings );

		foreach( array( 'ad_apps', 'ad_view_number', 'ad_view_repeat' ) as $field )
		{
			if( isset( $values[ $field ] ) )
			{
				unset( $values[ $field ] );
			}
		}
		
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
			'locations',
			'exempt',
			'expire',
			'expire_unit',
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
		if ( $purchase->extra['ad'] )
		{
			try
			{
				$ad = Db::i()->select( '*','core_advertisements', array( 'ad_id=?', $purchase->extra['ad'] ) )->first();
			}
			catch ( UnderflowException )
			{
				parent::updatePurchase( $purchase, $changes, $cancelBillingAgreementIfNecessary );
				return;
			}
			
			$update = array();
			foreach ( array( 'locations', 'exempt', 'expire', 'expire_unit' ) as $k )
			{
				if ( array_key_exists( $k, $changes ) )
				{
					switch ( $k )
					{
						case 'locations':
							$i = 'ad_location';
							break;
						case 'exempt':
							$i = 'ad_exempt';
							break;
						case 'expire':
							$i = 'ad_maximum_value';
							break;
						case 'expire_unit':
							$i = 'ad_maximum_unit';
							break;
					}
					
					if ( isset( $i ) AND $ad[ $i ] == $changes[ $k ] )
					{
						$update[ $i ] = $this->$k;
					}
				}
			}
			if ( !empty( $update ) )
			{
				Db::i()->update( 'core_advertisements', $update, array( 'ad_id=?', $purchase->extra['ad'] ) );
			}
		}
		
		parent::updatePurchase( $purchase, $changes, $cancelBillingAgreementIfNecessary );
	}
	
	/* !Store */
	
	/**
	 * Store Form
	 *
	 * @param	Form	$form			The form
	 * @param string $memberCurrency	The currency being used
	 * @return    void
	 */
	public function storeForm(Form $form, string $memberCurrency ): void
	{
		$form->add( new Form\Url( 'advertisement_url', NULL, TRUE ) );
		
		$maxDims = TRUE;
		if ( $this->max_height or $this->max_width )
		{
			$maxDims = array();
			if ( $this->max_width )
			{
				$maxDims['maxWidth'] = $this->max_width;
			}
			if ( $this->max_height )
			{
				$maxDims['maxHeight'] = $this->max_height;
			}
		}
		
		if ( !isset( Request::i()->stockCheck ) ) // The stock check will attempt to save the upload which we don't want to do until the form is actually submitted
		{
			$form->add( new Upload( 'advertisement_image', NULL, TRUE, array( 'storageExtension' => 'nexus_Ads', 'image' => $maxDims ) ) );
		}
		
		if ( $this->max_height and $this->max_width )
		{
			Member::loggedIn()->language()->words['advertisement_image_desc'] = Member::loggedIn()->language()->addToStack( 'advertisement_image_max_wh', FALSE, array( 'sprintf' => array( $this->max_width, $this->max_height ) ) );
		}
		elseif ( $this->max_height )
		{
			Member::loggedIn()->language()->words['advertisement_image_desc'] = Member::loggedIn()->language()->addToStack( 'advertisement_image_max_h', FALSE, array( 'sprintf' => array( $this->max_height ) ) );
		}
		elseif ( $this->max_width )
		{
			Member::loggedIn()->language()->words['advertisement_image_desc'] = Member::loggedIn()->language()->addToStack( 'advertisement_image_max_w', FALSE, array( 'sprintf' => array( $this->max_width ) ) );
		}
		
		$form->add( new YesNo( 'ad_image_more', FALSE, FALSE, array( 'togglesOn' => array( 'ad_image_small', 'ad_image_medium' ) ), NULL, NULL, NULL, 'ad_image_more' ) );
		$form->add( new Upload( 'ad_image_small', NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'nexus_Ads' ), NULL, NULL, NULL, 'ad_image_small' ) );
		$form->add( new Upload( 'ad_image_medium', NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'nexus_Ads' ), NULL, NULL, NULL, 'ad_image_medium' ) );
	}
	
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
		$item->extra['image'] = json_encode( array( 'large' => (string) $values['advertisement_image'], 'medium' => (string) $values['ad_image_medium'], 'small' => (string) $values['ad_image_small'] ) );
		$item->extra['link'] = (string) $values['advertisement_url'];
		return parent::addToCart( $item, $values, $memberCurrency );
	}
	
	/* !Client Area */
	
	/**
	 * Show Purchase Record?
	 *
	 * @return	bool
	 */
	public function showPurchaseRecord(): bool
	{
		return TRUE;
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return	array( 'packageInfo' => '...', 'purchaseInfo' => '...' )
	 */
	public function clientAreaPage( Purchase $purchase ) : array
	{	
		$parent = parent::clientAreaPage( $purchase );
		
		try
		{
			$advertisement = Advertisement::load( $purchase->extra['ad'] );
			
			return array(
				'packageInfo'	=> $parent['packageInfo'],
				'purchaseInfo'	=> $parent['purchaseInfo'] . Theme::i()->getTemplate('purchases')->advertisement( $purchase, $advertisement ),
			);
		}
		catch ( OutOfRangeException )
		{
			return $parent;
		}
	}
	
	/* !ACP */
	
	/**
	 * ACP Generate Invoice Form
	 *
	 * @param	Form	$form	The form
	 * @param string $k		The key to add to the field names
	 * @return    void
	 */
	public function generateInvoiceForm(Form $form, string $k ): void
	{
		$form->attributes['data-bypassValidation'] = true;
		$field = new Form\Url( 'advertisement_url' . $k, NULL, TRUE );
		$field->label = Member::loggedIn()->language()->addToStack('advertisement_url');
		$form->add( $field );
				
		$field = new Upload( 'ad_image' . $k, NULL, TRUE, array( 'image' => TRUE, 'storageExtension' => 'nexus_Ads' ) );
		$field->label = Member::loggedIn()->language()->addToStack('ad_image');
		$form->add( $field );
		
		$field = new YesNo( 'ad_image_more' . $k, FALSE, FALSE, array( 'togglesOn' => array( 'ad_image_small' . $k, 'ad_image_medium' . $k ) ) );
		$field->label = Member::loggedIn()->language()->addToStack('ad_image_more');
		$form->add( $field );
		
		$field = new Upload( 'ad_image_small' . $k, NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'nexus_Ads' ), NULL, NULL, NULL, 'ad_image_small' . $k );
		$field->label = Member::loggedIn()->language()->addToStack('ad_image_small');
		$form->add( $field );
		
		$field = new Upload( 'ad_image_medium' . $k, NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'nexus_Ads' ), NULL, NULL, NULL, 'ad_image_medium' . $k );
		$field->label = Member::loggedIn()->language()->addToStack('ad_image_medium');
		$form->add( $field );		
	}
	
	/**
	 * ACP Add to invoice
	 *
	 * @param \IPS\nexus\Invoice\Item $item			The item
	 * @param	array										$values			Values from form
	 * @param string $k				The key to add to the field names
	 * @param	Invoice							$invoice		The invoice
	 * @return    void
	 */
	public function acpAddToInvoice(\IPS\nexus\Invoice\Item $item, array $values, string $k, Invoice $invoice ): void
	{
		$item->extra['image'] = json_encode( array( 'large' => (string) $values[ 'ad_image' . $k ], 'medium' => (string) $values[ 'ad_image_medium' . $k ], 'small' => (string) $values[ 'ad_image_small' . $k ] ) );
		$item->extra['link'] = (string) $values[ 'advertisement_url' . $k ];
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @param	Purchase	$purchase	Purchase record
	 * @return    string
	 */
	public function acpPage( Purchase $purchase ): string
	{
		try
		{
			return Theme::i()->getTemplate( 'purchases' )->advertisement( $purchase, Advertisement::load( $purchase->extra['ad'] ) );
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
	 * @param Url $url		The page URL
	 * @return    array
	 */
	public function acpButtons(Purchase $purchase, Url $url ): array
	{
		$return = parent::acpButtons( $purchase, $url );
		
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'advertisements_edit' ) and isset( $purchase->extra['ad'] ) )
		{
			$return['edit_advertisement'] = array(
				'icon'	=> 'list-alt',
				'title'	=> 'edit_advertisement',
				'link'	=> Url::internal( 'app=core&module=promotion&controller=advertisements&do=form&id=' . $purchase->extra['ad'] ),
			);
		}
		
		return $return;
	}
	
	/* !Actions */
	
	/**
	 * On Purchase Generated
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param Invoice $invoice	The invoice
	 * @return    void
	 */
	public function onPurchaseGenerated(Purchase $purchase, Invoice $invoice ): void
	{		
		$insertId = Db::i()->insert( 'core_advertisements', array(
			'ad_location'			=> $this->locations,
			'ad_html'				=> NULL,
			'ad_images'				=> $purchase->extra['image'],
			'ad_link'				=> $purchase->extra['link'],
			'ad_impressions'		=> 0,
			'ad_clicks'				=> 0,
			'ad_exempt'				=> $this->exempt === '*' ? '*' : json_encode( explode( ',', $this->exempt ) ),
			'ad_active'				=> -1,
			'ad_html_https'			=> NULL,
			'ad_start'				=> $purchase->start->getTimestamp(),
			'ad_end'				=> $purchase->expire ? $purchase->expire->getTimestamp() : 0,
			'ad_maximum_value'		=> $this->expire,
			'ad_maximum_unit'		=> $this->expire_unit,
			'ad_additional_settings'=> $this->settings,
			'ad_html_https_set'		=> 0,
			'ad_member'				=> $purchase->member->member_id,
			'ad_type'				=> Advertisement::AD_IMAGES,
		) );
		
		$extra = $purchase->extra;
		$extra['ad'] = $insertId;
		$purchase->extra = $extra;
		$purchase->save();
		
		AdminNotification::send( 'nexus', 'Advertisement', NULL, TRUE, $purchase );

		parent::onPurchaseGenerated( $purchase, $invoice );
	}
		
	/**
	 * On Expiration Date Change
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public function onExpirationDateChange( Purchase $purchase ): void
	{
		try
		{
			$ad = Advertisement::load( $purchase->extra['ad'] );
			$ad->end = $purchase->expire ? $purchase->expire->getTimestamp() : 0;
						
			if ( ( !$ad->end or $ad->end > time() ) )
			{
				if ( $ad->maximum_value > -1 AND $ad->maximum_value )
				{					
					if ( $ad->maximum_unit == 'i' )
					{
						if ( $ad->impressions < $ad->maximum_value )
						{
							$ad->active = ( $ad->active == -1 ) ? -1 : 1;
						}
						else
						{
							$ad->active = 0;
						}
					}
					else
					{
						if ( $ad->clicks < $ad->maximum_value )
						{
							$ad->active = ( $ad->active == -1 ) ? -1 : 1;
						}
						else
						{
							$ad->active = 0;
						}
					}
				}
				else
				{
					$ad->active = ( $ad->active == -1 ) ? -1 : 1;
				}
			}
			else
			{
				$ad->active = 0;
			}
			
			$ad->save();
		}
		catch ( OutOfRangeException ) { }
		
		parent::onExpirationDateChange( $purchase );
	}
	
	/**
	 * On Purchase Expired
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public function onExpire( Purchase $purchase ): void
	{
		try
		{
			$ad = Advertisement::load( $purchase->extra['ad'] );
			$ad->active = 0;			
			$ad->save();
		}
		catch ( OutOfRangeException ) { }
		
		parent::onExpire( $purchase );
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public function onCancel( Purchase $purchase ): void
	{
		try
		{
			$ad = Advertisement::load( $purchase->extra['ad'] );
			$ad->active = 0;			
			$ad->save();
		}
		catch ( OutOfRangeException ) { }
		
		parent::onCancel( $purchase );
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public function onDelete( Purchase $purchase ): void
	{
		try
		{
			Advertisement::load( $purchase->extra['ad'] )->delete();
		}
		catch ( OutOfRangeException ) { }
		
		parent::onDelete( $purchase );
	}
	
	/**
	 * On Purchase Reactivated (renewed after being expired or reactivated after being canceled)
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public function onReactivate( Purchase $purchase ): void
	{
		$this->onExpirationDateChange( $purchase );
		
		parent::onReactivate( $purchase );
	}
	
	/**
	 * On Transfer (is ran before transferring)
	 *
	 * @param	Purchase	$purchase		The purchase
	 * @param	Member			$newCustomer	New Customer
	 * @return    void
	 */
	public function onTransfer( Purchase $purchase, Member $newCustomer ): void
	{
		try
		{
			$ad = Advertisement::load( $purchase->extra['ad'] );
			$ad->member = $newCustomer->member_id;
			$ad->save();
		}
		catch ( OutOfRangeException ) { }
		
		parent::onTransfer( $purchase, $newCustomer );
	}
	
	/**
	 * On Upgrade/Downgrade
	 *
	 * @param	Purchase							$purchase				The purchase
	 * @param	Package							$newPackage				The package to upgrade to
	 * @param int|RenewalTerm|NULL $chosenRenewalOption	The chosen renewal option
	 * @return    void
	 */
	public function onChange(Purchase $purchase, Package $newPackage, RenewalTerm|int|null $chosenRenewalOption = NULL ): void
	{
		try
		{
			$ad = Advertisement::load( $purchase->extra['ad'] );
			$ad->location = $newPackage->locations;
			$ad->exempt = $newPackage->exempt === '*' ? '*' : json_encode( explode( ',', $newPackage->exempt ) );
			$ad->maximum_value = $newPackage->expire;
			$ad->maximum_unit = $newPackage->expire_unit;
			$ad->save();
		}
		catch ( OutOfRangeException ) { }

		parent::onChange( $purchase, $newPackage );
	}
}