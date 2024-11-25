<?php
/**
 * @brief		Member subscription Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		9 Feb 2018
 */

namespace IPS\nexus\Subscription;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use InvalidArgumentException;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Math\Number;
use IPS\Member;
use IPS\Member\Group;
use IPS\nexus\Customer;
use IPS\nexus\extensions\nexus\Item\Subscription;
use IPS\nexus\extensions\nexus\Item\SubscriptionUpgrade;
use IPS\nexus\invoice;
use IPS\nexus\Money;
use IPS\nexus\Package as PackageClass;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Tax;
use IPS\Node\Model;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Subscription Model
 */
class Package extends Model
{
	/* !ActiveRecord */
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'nexus_sub_count' );
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_member_subscription_packages';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'sp_';
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
		
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'nexus_subs_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';
	
	/* !Node */

	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'nexus';

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'menu__nexus_subscriptions_subscriptions';
			
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
		'module'	=> 'subscriptions',
		'prefix' 	=> 'subscriptions_'
	);

	/**
	 * @brief	URL Base
	 */
	protected static string $urlBase = 'app=nexus&module=subscriptions&controller=subscriptions&id=';

	/**
	 * @brief	URL Template
	 */
	protected static string $urlTemplate = 'nexus_subscription';
	
	/**
	 * @brief	SEO Title Column
	 */
	protected static string $seoTitleColumn = '';

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;

    /**
     * [ActiveRecord] Duplicate
     *
     * @return	void
     */
    public function __clone() : void
    {
        if ( $this->skipCloneDuplication === TRUE )
        {
            return;
        }

        $old = $this;

        parent::__clone();

        /* Copy across images */
		if ( $old->image )
		{
			try
			{
				$file = File::get( 'nexus_Products', $old->image );
				$this->image = (string) File::create( 'nexus_Products', $file->originalFilename, $file->contents() );
				$this->save();
			}
			catch( Exception ) {}
		}
    }

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
    public function delete(): void
	{
		try
		{
			if( $this->image )
			{
				File::get( 'nexus_Products', $this->image )->delete();
			}
		}
		catch( Exception ){}

		Task::queue( 'nexus', 'DeleteSubscriptions', [ 'id' => $this->id ] );

		parent::delete();
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
		$buttons = array();
		
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'subscriptions', 'subscriptions_manage' ) )
		{
			$buttons['add_member'] = array(
				'icon'	=> 'plus',
				'title'	=> 'nexus_subs_add_member',
				'link'	=> $url->setQueryString( array( 'do' => 'addMember', 'id' => $this->id ) ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('nexus_subs_add_member') )
			);
		}
		
		$buttons = array_merge( $buttons, parent::getButtons( $url, $subnode ) );
		
		if ( isset( $buttons['delete'] ) )
		{
			unset( $buttons['delete']['data']['delete'] );
			$buttons['delete']['data']['confirm'] = TRUE;
		}
				
		return $buttons;
	}
	
	/**
	 * Fetch the cover image uRL
	 *
	 * @return File | NULL
	 */
	public function get__image() : File|null
	{
		return ( $this->image ) ? File::get( 'nexus_Products', $this->image ) : NULL;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$groupsExcludingGuestsAndAdmins = array();
		foreach ( Group::groups( FALSE, FALSE ) as $group )
		{
			$groupsExcludingGuestsAndAdmins[ $group->g_id ] = $group->name;
		}
		
		$renewInterval = NULL;
		$renewalTerm = NULL;
		$renewalCosts = array();
		if ( $this->renew_options and $_renewOptions = json_decode( $this->renew_options, TRUE ) and is_array( $_renewOptions ) )
		{
			foreach ( $_renewOptions['cost'] as $cost )
			{
				$renewalCosts[ $cost['currency'] ] = new Money( $cost['amount'], $cost['currency'] );
			}
			
			try
			{
				$renewInterval = new DateInterval( "P{$_renewOptions['term']}" . mb_strtoupper( $_renewOptions['unit'] ) );
				$renewalTerm = new RenewalTerm( $renewalCosts, $renewInterval, NULL );
			}
			catch( Exception ) { } // Catch any invalid renewal terms, these can occasionally appear from legacy IP.Subscriptions
		}
		
		$initialPrice = NULL;
		if ( $this->price )
		{
			$initialInterval = $renewInterval;
			$initialPrices = [];
			$costs = json_decode( $this->price, TRUE );
			if ( isset( $costs['cost'] ) )
			{
				$initialInterval = new DateInterval( 'P' . $costs['term'] . mb_strtoupper( $costs['unit'] ) );
				$costs = $costs['cost'];
			}
			foreach ( $costs as $price )
			{
				$initialPrices[ $price['currency'] ] = new Money( $price['amount'], $price['currency'] );
			}
			
			if ( $initialPrices == $renewalCosts )
			{
				$renewalTerm = NULL;
			}
			
			$initialPrice = new RenewalTerm( $initialPrices, $initialInterval );
		}
		
		$form->addHeader('subscription_basic_settings');
		$form->add( new Translatable( 'sp_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_subs_{$this->id}" : NULL ) ) );
		$form->add( new YesNo( 'sp_enabled', $this->id ? $this->enabled : TRUE, FALSE ) );
		$form->addHeader( 'nexus_subs_cost' );
		$form->add( new \IPS\nexus\Form\RenewalTerm( 'sp_price', $initialPrice, NULL, array( 'allCurrencies' => TRUE, 'initialTerm' => TRUE, 'unlimitedTogglesOn' => [ 'sp_renew_upgrade' ], 'unlimitedTogglesOff' => [ 'sp_renew_options' ] ), NULL, NULL, NULL, 'sp_initial_term' ) );
		$form->add( new \IPS\nexus\Form\RenewalTerm( 'sp_renew_options', $renewalTerm, NULL, array( 'allCurrencies' => TRUE, 'nullLang' => 'term_same_as_initial' ), NULL, NULL, NULL, 'sp_renew_options' ) );
		$form->add( new Radio( 'sp_renew_upgrade', $this->renew_upgrade ?: 0, FALSE, array( 'options' => array(
				0 => 'sp_renew_upgrade_none',
				1 => 'sp_renew_upgrade_full',
				2 => 'sp_renew_upgrade_partial'
			) ), NULL, NULL, NULL, 'sp_renew_upgrade' ) );	
		$form->add( new Node( 'sp_tax', (int) $this->tax, FALSE, array( 'class' => 'IPS\nexus\Tax', 'zeroVal' => 'do_not_tax' ) ) );
		$form->add( new Node( 'sp_gateways', ( !$this->gateways or $this->gateways === '*' ) ? 0 : explode( ',', $this->gateways ), FALSE, array( 'class' => 'IPS\nexus\Gateway', 'multiple' => TRUE, 'zeroVal' => 'any' ) ) );
		

		$form->addHeader( 'nexus_subs_groups' );
		$form->add( new Select( 'sp_primary_group', $this->primary_group ?: '*', FALSE, array( 'options' => $groupsExcludingGuestsAndAdmins, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_primary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new Select( 'sp_secondary_group', $this->secondary_group ? explode( ',', $this->secondary_group ) : '*', FALSE, array( 'options' => $groupsExcludingGuestsAndAdmins, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_secondary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new YesNo( 'sp_return_primary', $this->return_primary, FALSE, array(), NULL, NULL, NULL, 'sp_return_primary' ) );


		$form->addHeader('nexus_subs_display');
		$form->add( new Upload( 'sp_image', ( ( $this->id AND $this->image ) ? File::get( 'nexus_Products', $this->image ) : NULL ), FALSE, array( 'storageExtension' => 'nexus_Products', 'image' => TRUE, 'allowStockPhotos' => TRUE ), NULL, NULL, NULL, 'sp_image' ) );
		/*$form->add( new \IPS\Helpers\Form\YesNo( 'sp_featured', $this->featured, FALSE, array(), NULL, NULL, NULL, 'sp_featured' ) );*/
		$form->add( new Translatable( 'sp_desc', NULL, FALSE, array(
			'app' => 'nexus',
			'key' => $this->id ? "nexus_subs_{$this->id}_desc" : NULL,
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-sub-{$this->id}" : "nexus-new-sub" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'sub' ) : NULL, 'minimize' => 'p_desc_placeholder'
			)
		), NULL, NULL, NULL, 'p_desc_editor' ) );

        parent::form( $form );
	}
	
	/**
	 * [Node] Save Add/Edit Form
	 *
	 * @param	array	$values	Values from the form
	 * @return    mixed
	 */
	public function saveForm( array $values ): mixed
	{		
		if ( !$this->id )
		{	
			$this->save();
			unset( static::$multitons[ $this->id ] );
			
			File::claimAttachments( 'nexus-new-sub', $this->id, NULL, 'sub', TRUE );
				
			$obj = static::load( $this->id );
			return $obj->saveForm( $obj->formatFormValues( $values ) );			
		}
		
		return parent::saveForm( $values );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( !$this->id )
		{
			return $values;
		}
		
		/* Translatables */
		foreach ( array( 'name' => '', 'desc' => '_desc' ) as $key => $suffix )
		{
			if ( isset( $values[ 'sp_' . $key ] ) )
			{
				Lang::saveCustom( 'nexus', "nexus_subs_{$this->id}{$suffix}", $values[ 'sp_' . $key ] );
			}
			unset( $values[ 'sp_' . $key ] );
		}

		/* Normalise */
		$originalPrice = NULL;
		if( isset( $values['sp_price'] ) )
		{
			$originalPrice = $values['sp_price'];
			if ( $values['sp_price']->interval )
			{
				$term = $values['sp_price']->getTerm();
				$values['sp_price'] = json_encode( array(
					'cost'	=> $values['sp_price']->cost,
					'term'	=> $term['term'],
					'unit'	=> $term['unit']
				) );
			}
			else
			{
				$values['sp_price'] = json_encode( $values['sp_price']->cost );
			}
		}

		if( isset( $values['sp_primary_group'] ) )
		{
			$values['sp_primary_group'] = $values['sp_primary_group'] == '*' ? 0 : $values['sp_primary_group'];
		}

		if( isset( $values['sp_secondary_group'] ) )
		{
			$values['sp_secondary_group'] = $values['sp_secondary_group'] == '*' ? '' : implode( ',', $values['sp_secondary_group'] );
		}
		
		if( isset( $values['sp_tax'] ) )
		{
			$values['sp_tax'] = $values['sp_tax'] ? $values['sp_tax']->id : 0;
		}
		
		if( isset( $values['sp_gateways'] ) )
		{
			$values['sp_gateways'] = is_array( $values['sp_gateways'] ) ? implode( ',', array_keys( $values['sp_gateways'] ) ) : '*';
		}

		/* Renewal options */
		if( isset( $values['sp_renew_options'] ) )
		{
			if ( $values['sp_renew_options'] )
			{
				$option = $values['sp_renew_options'];
				$term = $option->getTerm();
				
				$values['sp_renew_options'] = json_encode( array(
					'cost'	=> $option->cost,
					'term'	=> $term['term'],
					'unit'	=> $term['unit']
				) );
			}
			else
			{
				$values['sp_renew_options'] = '';
			}
		}
		elseif ( isset( $values['sp_price'] ) and $originalPrice->interval )
		{
			$values['sp_renew_options'] = $values['sp_price'];
			$values['sp_price'] = json_encode( $originalPrice->cost );
		}
	
		if ( isset( $values['sp_image'] ) )
		{
			$values['sp_image'] = (string) $values['sp_image'];
		}
		
		return $values;
	}
	
	/**
	 * Price
	 *
	 * @param	string|NULL	$currency	Desired currency, or NULL to choose based on member's chosen currency
	 * @return	Money|NULL
	 */
	public function price( ?string $currency = NULL ) : Money|null
	{
		if ( !$currency )
		{
			$currency = ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency();
		}
		
		$costs = json_decode( $this->price, TRUE );
		if ( isset( $costs['cost'] ) )
		{
			$costs = $costs['cost'];
		}

		if ( is_array( $costs ) and isset( $costs[ $currency ]['amount'] ) )
		{
			return new Money( $costs[ $currency ]['amount'], $currency );
		}
	
		return NULL;
	}
	
	/**
	 * Joining fee
	 *
	 * @param	string|NULL	$currency	Desired currency, or NULL to choose based on member's chosen currency
	 * @return	RenewalTerm|NULL
	 * @throws	OutOfRangeException
	 */
	public function renewalTerm( ?string $currency = NULL ) : RenewalTerm|null
	{
		if ( $this->renew_options and $renewal = json_decode( $this->renew_options, TRUE ) )
		{
			$renewalPrices = $renewal['cost'];
			if ( !$currency )
			{
				$currency = ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency();
			}
			
			if ( isset( $renewalPrices[ $currency ] ) )
			{
				$grace = NULL;
				if ( Settings::i()->nexus_subs_invoice_grace )
				{
					$grace = new DateInterval( 'P' . Settings::i()->nexus_subs_invoice_grace . 'D' );
				}
				
				$tax = NULL;
				if ( $this->tax )
				{
					try
					{
						$tax = Tax::load( $this->tax );
					} 
					catch( OutOfRangeException ) { }
				}
				
				return new RenewalTerm( new Money( $renewalPrices[ $currency ]['amount'], $currency ), new DateInterval( 'P' . $renewal['term'] . mb_strtoupper( $renewal['unit'] ) ), $tax, FALSE, $grace );
			}
			else
			{
				throw new OutOfRangeException;
			}
		}
		
		return NULL;
	}
	
	/**
	 * Price Info
	 *
	 * @return	array|NULL
	 */
	public function priceInfo() : array|null
	{
		/* Base Price */
		$price = $this->price();

		/* Price may not have been defined in our currency - abort if we don't have one */
		if( $price === NULL )
		{
			return null;
		}
		
		/* Renewal Term */
		$renewalTerm = NULL;
		$initialTerm = NULL;
		try
		{
			$renewalTerm = $this->renewalTerm( $price->currency );
			
			/* Is the initial term different? */
			$priceInfo = json_decode( $this->price, TRUE );
			if ( isset( $priceInfo['term'] ) )
			{
				$initialTerm = new RenewalTerm( $price, new DateInterval( 'P' . $priceInfo['term'] . mb_strtoupper( $priceInfo['unit'] ) ) );
			}
		}
		catch ( OutOfRangeException ) {}
		
		/* If we can encompass the primary price and renewal term together, do that */
		$priceIsZero = $price->amount->isZero();
		if ( $renewalTerm and $price->amount->compare( $renewalTerm->cost->amount ) === 0 )
		{
			$price = $renewalTerm->toDisplay();
			$renewalTerm = NULL;
		}
		elseif ( $price )
		{
			if ( Settings::i()->nexus_show_tax and $this->tax )
			{
				try
				{
					$taxRate = new Number( Tax::load( $this->tax )->rate( Customer::loggedIn()->estimatedLocation() ) );
					
					$price->amount = $price->amount->add( $price->amount->multiply( $taxRate ) );
				}
				catch ( OutOfRangeException ) { }
			}
		}

		/* Return */
		return array(
			'primaryPrice'					=> $priceIsZero ? Member::loggedIn()->language()->addToStack('nexus_sub_cost_free') : $price,
			'primaryPriceIsZero'			=> $priceIsZero,
			'primaryPriceDiscountedFrom'	=> NULL,
			'initialTerm'					=> $initialTerm?->getTermUnit(),
			'renewalPrice'					=> $renewalTerm?->toDisplay(),
		);
	}
	
	/**
	 * Price Blurb
	 *
	 * @return	string|NULL
	 */
	public function priceBlurb() : string|null
	{
		$priceInfo = $this->priceInfo();
		
		if ( $priceInfo['primaryPrice'] )
		{
			if ( $priceInfo['renewalPrice'] and $priceInfo['initialTerm'] )
			{
				return Member::loggedIn()->language()->addToStack( 'nexus_sub_cost_plus_renewal', FALSE, array( 'sprintf' => array( $priceInfo['primaryPrice'], $priceInfo['initialTerm'], $priceInfo['renewalPrice'] ) ) );
			}
			elseif ( $priceInfo['renewalPrice'] )
			{
				return $priceInfo['renewalPrice'];
			}
			else
			{
				return $priceInfo['primaryPrice'];
			}
		}
		else
		{
			return Member::loggedIn()->language()->addToStack('nexus_sub_cost_unavailable');
		}		
	}
	
	/** 
	 * Cost to upgrade to this package (may return negative value for refund)
	 *
	 * @param Package $package	The currently subscribed package
	 * @param	Customer						$member		The member.
	 * @return	Money|NULL
	 * @throws	InvalidArgumentException
	 */
	public function costToUpgrade(Package $package, Customer $member ) : Money|null
	{
		/* Fetch purchase */
		$purchase = NULL;
		foreach ( Subscription::getPurchases( $member, $package->id, TRUE, TRUE ) as $row )
		{
			if ( !$row->cancelled OR ( $row->cancelled AND $row->can_reactivate ) )
			{
				$purchase = $row;
				break;
			}
		}

		if ( $purchase === NULL )
		{
			return NULL;
		}

		/* @var Purchase $purchase */
		try
		{
			$currency = $purchase->original_invoice->currency;
		}
		catch ( Exception )
		{
			$currency = $purchase->member->defaultCurrency();
		}

		$priceOfExistingPackage = json_decode( $package->price, TRUE );
		if ( isset( $priceOfExistingPackage['cost'] ) )
		{
			$priceOfExistingPackage = $priceOfExistingPackage['cost'];
		}
		$priceOfExistingPackage = $priceOfExistingPackage[ $currency ]['amount'];
		
		$priceOfThisPackage = json_decode( $this->price, TRUE );
		if ( isset( $priceOfThisPackage['cost'] ) )
		{
			$priceOfThisPackage = $priceOfThisPackage['cost'];
		}
		$priceOfThisPackage = $priceOfThisPackage[ $currency ]['amount'];
		$renewalOptionsOnNewPackage = json_decode( $this->renew_options, TRUE );

		/* It's a non-recurring subscription */
		if ( empty( $renewalOptionsOnNewPackage ) )
		{
			switch( $this->renew_upgrade )
			{
				case 0:
					return NULL;
				
				case 1:
					return new Money( $priceOfThisPackage, $currency );
					
				case 2:
					return new Money( $priceOfThisPackage - $priceOfExistingPackage, $currency );
			}
		}
		
		/* It's a recurring subscription */
		if ( $priceOfThisPackage >= $priceOfExistingPackage )
		{
			$type = Settings::i()->nexus_subs_upgrade;
		}
		else
		{
			$type = Settings::i()->nexus_subs_downgrade;
		}

		switch ( $type )
		{
			case -1:
				return NULL; /* nope */
				
			case 0:
				return new Money( 0, $currency );
			
			case 1:
				/* If the purchase is expired, charge the full amount */
				if( !$purchase->active or ( $purchase->expire instanceof DateTime and $purchase->expire->getTimestamp() < time() ) )
				{
					$newPrice = new Money( $priceOfThisPackage, $currency );

					/* If the package has a free trial, charge the renewal fee instead to stop endless free trials */
					if( $newPrice->amount->isZero() )
					{
						return new Money( $renewalOptionsOnNewPackage['cost'][ $currency ]['amount'], $currency );
					}

					return new Money( $priceOfThisPackage, $currency );
				}

				return new Money( $priceOfThisPackage - $priceOfExistingPackage, $currency );
			
			case 2:
				/* If there are no renewals, we charge full price no matter what */
				if ( !$purchase->renewals and !$purchase->expire )
				{
					return new Money( $priceOfThisPackage, $currency );
				}
				if ( !$renewalOptionsOnNewPackage )
				{
					throw new InvalidArgumentException;
				}

				/* What is the closest renewal option on the new package? We'll use that one */
				$renewalOptionsInDays = array();
				$term = ( new RenewalTerm( new Money( $renewalOptionsOnNewPackage['cost'][ $currency ]['amount'], $currency ), new DateInterval( 'P' . $renewalOptionsOnNewPackage['term'] . mb_strtoupper( $renewalOptionsOnNewPackage['unit'] ) ), $purchase->renewals?->tax ) );
				$renewalOptionsInDays[ $term->days() ] = $term;

				$closestRenewalOption = null;
				$numberOfDaysInCurrentRenewalTerm = $purchase->renewals ? $purchase->renewals->days() : ( $purchase->expire ? $purchase->start->diff( $purchase->expire )->days : 0 );
				foreach ( $renewalOptionsInDays as $days => $term )
				{
					if ( $closestRenewalOption === null or abs( $numberOfDaysInCurrentRenewalTerm - $closestRenewalOption ) > abs( $days - $numberOfDaysInCurrentRenewalTerm ) )
					{
						$closestRenewalOption = $days;
					}
				}
				$renewalTermToUse = $renewalOptionsInDays[ $closestRenewalOption ];
				$numberOfDaysInCurrentRenewalTerm = new Number( (string) round( $numberOfDaysInCurrentRenewalTerm ) );


				/* If the purchase is not active, it is the full cost */
				if( !$purchase->active or ( $purchase->expire instanceof DateTime and $purchase->expire->getTimestamp() < time() ) )
				{
					return new Money( $renewalTermToUse->cost->amount, $currency );
				}

				/* Current Period Start Date */
				if( $purchase->expire instanceof DateTime and $purchase->renewals )
				{
					$startOfCurrentPeriod = $purchase->expire->sub( $purchase->renewals->interval );
				}
				else
				{
					/* If the purchase does not expire, we have to do some fun logic here */
					$timeSincePurchase = $purchase->start->diff( DateTime::create() );
					$daysUsedSincePurchase = new Number( (string)DateTime::intervalToDays( $timeSincePurchase ) );

					/* If we used less than a full renewal period, then set the start to the purchase date */
					if( $daysUsedSincePurchase->compare( $numberOfDaysInCurrentRenewalTerm ) <= 0 )
					{
						$startOfCurrentPeriod = $purchase->start;
					}
					else
					{
						/* Otherwise loop through all the used intervals */
						$start = clone $purchase->start;
						while( $daysUsedSincePurchase->compare( $numberOfDaysInCurrentRenewalTerm ) > 0 )
						{
							$start = $start->add( $purchase->renewals->interval );
							$timeSincePurchase = $start->diff( DateTime::create() );
							$daysUsedSincePurchase = new Number( (string)DateTime::intervalToDays( $timeSincePurchase ) );
						}
						$startOfCurrentPeriod = $start;
					}
				}

				/* DateInterval of time between subscription start and now */
				$timeSinceStartOfCurrentPeriod = $startOfCurrentPeriod->diff( DateTime::create() );

				/* Count Days in Period */
				$daysUsedInPeriod = new Number( (string)DateTime::intervalToDays( $timeSinceStartOfCurrentPeriod ) );
				$daysRemainingInPeriod = new Number( (string) $numberOfDaysInCurrentRenewalTerm->subtract( $daysUsedInPeriod ) );

				/* Currency Worth of value used in current sub */
				$valueUsedOfSubscription = $purchase->renewals ? $purchase->renewals->cost->amount->divide( $numberOfDaysInCurrentRenewalTerm )->multiply( $daysUsedInPeriod ) : new Money( new Number( '0' ), $currency );

				/* If there are no days left, charge the full amount */
				if ( !$daysRemainingInPeriod->isGreaterThanZero() )
				{
					return new Money( $priceOfThisPackage, $currency );
				}
				elseif( $priceOfExistingPackage > $priceOfThisPackage )
				{
					/* If this is a downgrade, calculate a refund */
					$refund = $purchase->renewals->cost->amount->subtract( $valueUsedOfSubscription );
					return new Money( $refund->multiply( new Number( "-1" ) ), $currency );
				}
				else
				{
					/* If we have renewals, then we have a valid value to use */
					if( $purchase->renewals )
					{
						return new Money( $renewalTermToUse->cost->amount->subtract( $valueUsedOfSubscription ), $currency );
					}
					else
					{
						/* Otherwise we have to calculate the cost based on how much time is left */
						$cost = new Money( $renewalTermToUse->costPerDay()->amount->multiply( $daysRemainingInPeriod ), $currency );
						if( $cost->amount->compare( new Number( $priceOfThisPackage ) ) == 1 )
						{
							$cost = new Money( $priceOfThisPackage, $currency );
						}
						return $cost;
					}
				}
		}

		return null;
	}

	/**
	 * Cost to upgrade to this package (may return negative value for refund)
	 *
	 * @param Package $package	The currently subscribed package
	 * @param	Customer						$member		The member.
	 * @return	Money|NULL
	 * @throws	InvalidArgumentException
	 */
	public function costToUpgradeIncludingTax(Package $package, Customer $member ) : Money|null
	{
		$upgradeCost = $this->costToUpgrade( $package, $member );

		if( $upgradeCost !== NULL AND $upgradeCost->amount->isGreaterThanZero() AND Settings::i()->nexus_show_tax and $this->tax )
		{
			try
			{
				$taxRate = new Number( Tax::load( $this->tax )->rate( Customer::loggedIn()->estimatedLocation() ) );

				$upgradeCost->amount = $upgradeCost->amount->add( $upgradeCost->amount->multiply( $taxRate ) );
			}
			catch ( OutOfRangeException ) { }
		}

		return $upgradeCost;
	}
	
	/**
	 * Create the upgrade/downgrade invoice or refund
	 *
	 * @param	Purchase 			$purchase
	 * @param Package $newPackage
	 * @param	bool										$skipCharge				If TRUE, an upgrade charges and downgrade refunds will not be issued
	 * @return	Invoice|null							An invoice if an upgrade charge has to be paid, or null if not
	 */
	public function upgradeDowngrade(Purchase $purchase, Package $newPackage, bool $skipCharge = FALSE ) : Invoice|null
	{
		/* Right, that's all the "I'll tamper with the URLs for a laugh" stuff out of the way... */
		$oldPackage = Package::load( $purchase->item_id );
		$costToUpgrade = $newPackage->costToUpgrade( $oldPackage, $purchase->member );
		
		/* Charge / Refund */
		if ( !$skipCharge )
		{
			/* Upgrade Charge */
			if ( $costToUpgrade->amount->isGreaterThanZero() )
			{
				$item = new SubscriptionUpgrade( sprintf( $purchase->member->language()->get( 'upgrade_charge_item' ), $purchase->member->language()->get( "nexus_subs_{$this->id}" ), $purchase->member->language()->get( "nexus_subs_{$newPackage->id}" ) ), $costToUpgrade );
				$item->tax = $newPackage->tax ? Tax::load( $newPackage->tax ) : NULL;
				$item->id = $purchase->id;
				$item->extra = array( 'newPackage' => $newPackage->id, 'oldPackage' => $this->id );
	
				if ( $newPackage->gateways and $newPackage->gateways != '*' )
				{
					$item->paymentMethodIds = explode( ',', $newPackage->gateways );
				}
	
				$invoice = new Invoice;
				$invoice->member = $purchase->member;
				$invoice->currency = $costToUpgrade->currency;
				$invoice->addItem( $item );
				$invoice->return_uri = "app=nexus&module=subscriptions&controller=subscriptions";
				$invoice->renewal_ids = array( $purchase->id );
				$invoice->save();
				return $invoice;
			}
			elseif ( !$costToUpgrade->amount->isPositive() )
			{
				$credits = $purchase->member->cm_credits;
				$credits[ $costToUpgrade->currency ]->amount = $credits[ $costToUpgrade->currency ]->amount->add( $costToUpgrade->amount->multiply( new Number( '-1' ) ) );
				$purchase->member->cm_credits = $credits;
				$purchase->member->save();
			}
		}
		
		/* Get old renewal term details here */
		$oldRenewalOptions = json_decode( $oldPackage->renew_options, TRUE );
		$oldTerm = $oldRenewalOptions;
		
		/* Work out the new renewal term */
		$renewalOptions = json_decode( $newPackage->renew_options, TRUE );
		$term = $rawTerm = $renewalOptions;
		
		if ( $term )
		{
			try
			{
				$currency = $purchase->original_invoice->currency;
			}
			catch ( OutOfRangeException )
			{
				$currency = $purchase->member->defaultCurrency();
			}

			/* Check Tax exists */
			$tax = NULL;
			try
			{
				$tax = $newPackage->tax ? Tax::load( $newPackage->tax ) : NULL;
			}
			catch( OutOfRangeException ) {}
			$term = new RenewalTerm( new Money( $term['cost'][$currency]['amount'], $currency ), new DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ), $tax );
		}

		/* Remove usergroups */
		$this->_removeUsergroups( $purchase->member );
				
		/* If we didn't have an expiry date before, but the new package has a renewal term, set an expiry date */
		if ( !$purchase->expire and $term )
		{
			$purchase->expire = DateTime::create()->add( $term->interval );
		}
		/* OR if we did have an expiry date, but the new package does not have a renewal term, remove it */
		elseif ( !$term )
		{
			$purchase->expire = NULL;
		}
		/* We have a term, but the unit is different from the existing package */
		elseif ( $purchase->expire and $oldTerm and ( $rawTerm['unit'] != $oldTerm['unit'] ) )
		{
			/* If there is an upgrade cost, the expire date should be from when the current billing period started. */
			if( $costToUpgrade->amount->isGreaterThanZero() AND Settings::i()->nexus_subs_upgrade > 0 )
			{
				$startOfCurrentPeriod = $purchase->expire->sub( new DateInterval( 'P' . $oldTerm['term'] . mb_strtoupper( $oldTerm['unit'] ) ) );
				$purchase->expire = $startOfCurrentPeriod < $purchase->start ? $purchase->start->add( $term->interval ) : $startOfCurrentPeriod->add( $term->interval );
			}
			else
			{
				$difference = $purchase->expire->getTimestamp() - time();

				if ( $difference > 0 )
				{
					$newExpire = DateTime::ts( ( time() - $difference ) );
					$newExpire = $newExpire->add( $term->interval );

					if ( $newExpire->getTimestamp() < time() )
					{
						$newExpire = DateTime::create()->add( $term->interval );
					}

					$purchase->expire = $newExpire;
				}
			}
		}
		/* Purchase expired in the past, so it needs one renewal term adding */
		elseif( $purchase->expire->getTimestamp() < time() AND $term )
		{
			$purchase->expire = DateTime::create()->add( $term->interval );
		}
				
		/* Update Purchase */
		$purchase->name = Member::loggedIn()->language()->get( "nexus_subs_{$newPackage->id}" );
		$purchase->cancelled = FALSE;
		$purchase->item_id = $newPackage->id;
		$purchase->renewals = $term;
		$purchase->save();
				
		/* Re-add usergroups */
		$newPackage->_addUsergroups( $purchase->member, TRUE );
		
		/* Cancel any pending invoices */
		if ( $pendingInvoice = $purchase->invoice_pending )
		{
			$pendingInvoice->status = invoice::STATUS_CANCELED;
			$pendingInvoice->save();
			$purchase->invoice_pending = NULL;
			$purchase->save();
		}
		
		/* Change the subscription itself */
		try
		{
			\IPS\nexus\Subscription::load( $purchase->id, 'sub_purchase_id' )->changePackage( $newPackage, $purchase->expire );
		}
		catch( Exception $e )
		{
			Log::log( "Change Package error (" . $e->getCode() . ") " . $e->getMessage(), 'subscriptions' );
		}

		return null;
	}
	
	/**
	 * Renew a member to the subscription package
	 *
	 * @param	Customer			$member		The cutomer innit
	 * @return  \IPS\nexus\Subscription		The new subscription object added
	 */
	public function renewMember( Customer $member ) : \IPS\nexus\Subscription
	{
		try
		{
			$expires = 0;
			$renews  = 0;
		
			/* Get the most recent active subscription */
			$sub = \IPS\nexus\Subscription::loadByMemberAndPackage( $member, $this, FALSE );
			
			if ( $this->renew_options and $renewal = json_decode( $this->renew_options, TRUE ) )
			{
				$nextExpiration = DateTime::ts( $sub->expire, TRUE );
				$nextExpiration->add( new DateInterval( 'P' . $renewal['term'] . mb_strtoupper( $renewal['unit'] ) ) );
				$expires = $nextExpiration->getTimeStamp();
				$renews = 1;
			}
			
			$sub->active = 1;
			$sub->cancelled = 0;
			$sub->expire = $expires;
			$sub->renews = $renews;
			$sub->save();
			
			/* Member groups may have changed in the package itself */
			$this->_removeUsergroups( $member );
			$this->_addUsergroups( $member );
			
			return $sub;
		}
		catch( Exception )
		{
			return $this->addMember( $member );
		}
	}

	/**
	 * Adds a member to the subscription package
	 *
	 * @param	Customer	        $member		        The customer innit
	 * @param   bool                        $generatePurchase   Generate purchase when adding member (for manual subscriptions)
	 * @param   bool                        $purchaseRenews     Does the purchase renew, or is it forever
	 * @return  \IPS\nexus\Subscription		                    The new subscription object added
	 */
	public function addMember( Customer $member, bool $generatePurchase=FALSE, bool $purchaseRenews=FALSE ): \IPS\nexus\Subscription
	{
		try
		{
			$sub = \IPS\nexus\Subscription::loadByMemberAndPackage( $member, $this, FALSE );
		}
		catch ( OutOfRangeException )
		{
			$sub = new \IPS\nexus\Subscription;
			$sub->package_id = $this->id;
			$sub->member_id = $member->member_id;
		}
		
		$expires = 0;
		$renews  = 0;
		if ( $this->renew_options and $renewal = json_decode( $this->renew_options, TRUE ) )
		{
			$start = DateTime::ts( time(), TRUE );
			$start->add( new DateInterval( 'P' . $renewal['term'] . mb_strtoupper( $renewal['unit'] ) ) );
			$expires = $start->getTimeStamp();
			$renews = 1;
		}
		
		/* Create a new one */
		$sub->active = 1;
		$sub->cancelled = 0;
		$sub->start = time();
		$sub->expire = $expires;
		$sub->renews = $renews;
		$sub->save();

		/* Do we need to generate a purchase to track this sub? */
		if( $generatePurchase )
		{
			$purchase = new Purchase;
			$purchase->member = $member;
			$purchase->name = $member->language()->get( static::$titleLangPrefix . $this->id );
			$purchase->app = 'nexus';
			$purchase->type = 'subscription';
			$purchase->item_id = $this->id;
			$purchase->tax = $this->tax;
			$purchase->show = 1;

			if( $purchaseRenews )
			{
				if ( $renewalTerm = $this->renewalTerm() )
				{
					$purchase->renewals = $renewalTerm;
				}
				$purchase->expire = DateTime::ts( $expires );
			}
			else
			{
				$sub->expire = 0;
				$sub->renews = 0;
			}

			$purchase->save();

			/* update sub with purchase id */
			$sub->purchase_id = $purchase->id;
			$sub->save();
		}
		
		$this->_addUsergroups( $member );
		
		return $sub;
	}
	
	/**
	 * Expires a member
	 *
	 * @param	Customer		$member		The cutomer innit
	 * @return void
	 */
	public function expireMember( Customer $member ) : void
	{
		/* Run before marking it inactive or it won't find the row in _removeUsergroups */
		$this->_removeUsergroups( $member );
		
		/* Make any previous subscriptions inactive */
		\IPS\nexus\Subscription::markInactiveByUser( $member );
	}
	
	/**
	 * Cancels a member
	 *
	 * @param	Customer		$member		The cutomer innit
	 * @return void
	 */
	public function cancelMember( Customer $member ) : void
	{
		/* Run before marking it inactive or it won't find the row in _removeUsergroups */
		$this->_removeUsergroups( $member );
		
		/* Make any previous subscriptions inactive */
		\IPS\nexus\Subscription::markInactiveByUser( $member );
		
		/* Cancel purchase */
		foreach ( Subscription::getPurchases( $member, $this->id ) as $purchase )
		{
			if ( $purchase->active )
			{
				$purchase->active = FALSE;
				$purchase->save();
			}
		}
	}
	
	/**
	 * Removes a member
	 *
	 * @param	Customer		$member		The cutomer innit
	 * @return void
	 */
	public function removeMember( Customer $member ) : void
	{
		/* Run before marking it inactive or it won't find the row in _removeUsergroups */
		$this->_removeUsergroups( $member );
		
		try
		{
			\IPS\nexus\Subscription::loadByMemberAndPackage( $member, $this, FALSE )->delete();
		}
		catch( OutOfRangeException ) {}
	}
		
	/* !Usergroups */
	
	/**
	 * Add user groups
	 *
	 * @param	Customer	$member	The customer
	 * @param bool $isPackageChange true if this is an upgrade/downgrade
	 * @return	void
	 */
	public function _addUsergroups( Customer $member, bool $isPackageChange=false ) : void
	{
		$previousGroup = 0;
		$previousSecondary = '';
		$current = null;
		try
		{
			$current = Db::i()->select( 'sub_previous_group,sub_previous_secondary_groups', 'nexus_member_subscriptions', array( 'sub_active=? and sub_member_id=? and sub_package_id=?', 1, $member->member_id, $this->id ) )->first();
		}

		catch( UnderflowException ){}
		/* Primary Group */
		if ( $this->primary_group and $this->primary_group != $member->member_group_id and !in_array( $member->member_group_id, explode( ',', Settings::i()->nexus_subs_exclude_groups ) ) )
		{
			/* Hang on, are we about to boot someone out the ACP? */
			if ( ! ( $member->isAdmin() and !in_array( $this->primary_group, array_keys( Member::administrators()['g'] ) ) ) )
			{
				/* Only do this if the target group exists */
				try
				{
					$group = Group::load( $this->primary_group );
					/* Save the current group */
					$previousGroup = $member->member_group_id;

					/* And update to the new group */
					$member->member_group_id = $this->primary_group;
					$member->members_bitoptions['ignore_promotions'] = true;
					$member->save();
					$member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'subscription', 'action' => 'add', 'id' => $this->id, 'old' => $previousGroup, 'new' => $member->member_group_id ) );
				}
				catch( OutOfRangeException )
				{

				}
			}
		}
		
		/* Secondary Groups */
		$secondary = array_filter( explode( ',', $this->secondary_group ), function( $v ){ return (bool) $v; } );

		$current_secondary = $member->mgroup_others ? explode( ',', $member->mgroup_others ) : array();
		$newSecondary = $current_secondary;
		if ( !empty( $secondary ) )
		{
			foreach ( $secondary as $gid )
			{
				if ( !in_array( $gid, $newSecondary ) )
				{
					/* Only do this if the target group exists */
					try
					{
						$group = Group::load( $gid );
						$newSecondary[] = $gid;
					}
					catch( OutOfRangeException )
					{

					}
				}
			}
		}
		
		if ( $current_secondary != $newSecondary )
		{
			$previousSecondary = $member->mgroup_others;
			$member->mgroup_others = ',' . implode( ',', $newSecondary ) . ',';
			$member->save();
			$member->logHistory( 'core', 'group', array( 'type' => 'secondary', 'by' => 'subscription', 'action' => 'add', 'id' => $this->id, 'old' => $previousSecondary, 'new' => $newSecondary ) );
		}

		/* We only want to  update the group demotions if this is a new subscription,
		OR if the previous subscription did not have a demotion */
		$update = [];
		if( !$isPackageChange OR $current === null OR !$current['sub_previous_group'] )
		{
			$update[ 'sub_previous_group' ] = $previousGroup;
		}
		if( !$isPackageChange OR $current === null OR !$current['sub_previous_secondary_groups'] )
		{
			$update['sub_previous_secondary_groups'] = $previousSecondary;
		}
		if( count( $update ) )
		{
			Db::i()->update( 'nexus_member_subscriptions', $update, array( 'sub_active=1 and sub_member_id=?', $member->member_id ) );
		}
	}
	
	/**
	 * Remove user groups
	 *
	 * @param	Customer	$member	The customer
	 * @return	void
	 */
	public function _removeUsergroups( Customer $member ) : void
	{
		if ( ! $this->return_primary )
		{
			return;
		}
		
		/* Fetch purchase */
		$purchase = NULL;
		foreach ( Subscription::getPurchases( $member, $this->id ) as $row )
		{
			/* Don't check for cancelled here, as the purchase will be cancelled before we get here */
			if ( $row->active )
			{
				$purchase = $row;
				break;
			}
		}
		
		try
		{
			$sub = Db::i()->select( '*', 'nexus_member_subscriptions', array( 'sub_active=1 and sub_package_id=? and sub_member_id=?', $this->id, $member->member_id ) )->first();
		}
		catch( UnderflowException )
		{
			return;
		}
		
		/* We only want to move them back if they haven't been moved again since */
		if ( $member->member_group_id == $this->primary_group )
		{
			$oldGroup = $member->member_group_id;
			
			/* Have we made other purchases that have changed their primary group? */
			try
			{
				if( $purchase !== NULL )
				{
					$next = Db::i()->select( array( 'ps_id', 'ps_name', 'p_primary_group' ), 'nexus_purchases', array( 'ps_member=? AND ps_app=? AND ps_type=? AND ps_active=1 AND p_primary_group<>0 AND ps_id<>?', $member->member_id, 'nexus', 'package', $purchase->id ) )
						->join( 'nexus_packages', 'p_id=ps_item_id' )
						->first();
				}
				else
				{
					$next = Db::i()->select( array( 'ps_id', 'ps_name', 'p_primary_group' ), 'nexus_purchases', array( 'ps_member=? AND ps_app=? AND ps_type=? AND ps_active=1 AND p_primary_group<>0', $member->member_id, 'nexus', 'package' ) )
						->join( 'nexus_packages', 'p_id=ps_item_id' )
						->first();
				}

				/* Make sure this group exists */
				try
				{
					Group::load( $next['p_primary_group'] );
				}
				catch( OutOfRangeException )
				{
					throw new UnderflowException;
				}

				$member->member_group_id = $next['p_primary_group'];
				$member->save();
				$member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'purchase', 'action' => 'change', 'remove_id' => $next['ps_id'], 'ps_name' => $next['ps_id'], 'id' => $next['ps_id'], 'name' => $next['ps_name'], 'old' => $oldGroup, 'new' => $member->member_group_id ) );
			}
			/* No, move them to their original group */
			catch ( UnderflowException )
			{
				/* Does this group exist? */
				try
				{
					Group::load( $sub['sub_previous_group'] );
					$member->member_group_id = $sub['sub_previous_group'];
				}
				catch ( OutOfRangeException )
				{
					$member->member_group_id = Settings::i()->member_group;
				}
									
				/* Save */
				$member->members_bitoptions['ignore_promotions'] = false;
				$member->save();
				$member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'subscription', 'action' => 'remove', 'id' => $this->id, 'old' => $oldGroup, 'new' => $member->member_group_id ) );
			}
		}

		// Secondary groups
		$secondaryGroupsAwardedByThisPurchase = array_unique( array_filter( explode( ',', $this->secondary_group ) ) );
		$membersSecondaryGroups = $member->mgroup_others ? array_unique( array_filter( explode( ',', $member->mgroup_others ) ) ) : array();
		if ( isset( $sub['sub_previous_secondary_groups'] ) and $sub['sub_previous_secondary_groups'] )
		{			
			/* Work some stuff out */
			$currentSecondaryGroups = $membersSecondaryGroups;
			$membersPreviousSecondaryGroupsBeforeThisPurchase = array_unique( array_filter( explode( ',', $sub['sub_previous_secondary_groups'] ) ) );
			
			/* Have we made other purchases that have added secondary groups? */
			$secondaryGroupsAwardedByOtherPurchases = array();

			if( $purchase !== NULL )
			{
				$query = Db::i()->select( 'p_secondary_group', 'nexus_purchases', array( 'ps_member=? AND ps_app=? AND ps_type=? AND ps_active=1 AND p_secondary_group IS NOT NULL AND p_secondary_group<>? AND ps_id<>?', $member->member_id, 'nexus', 'package', '', $purchase->id ) )->join( 'nexus_packages', 'p_id=ps_item_id' );
			}
			else
			{
				$query = Db::i()->select( 'p_secondary_group', 'nexus_purchases', array( 'ps_member=? AND ps_app=? AND ps_type=? AND ps_active=1 AND p_secondary_group IS NOT NULL AND p_secondary_group<>?', $member->member_id, 'nexus', 'package', '' ) )->join( 'nexus_packages', 'p_id=ps_item_id' );
			}

			foreach ( $query as $secondaryGroups )
			{
				$secondaryGroupsAwardedByOtherPurchases = array_merge( $secondaryGroupsAwardedByOtherPurchases, array_filter( explode( ',', $secondaryGroups ) ) );
			}

			$secondaryGroupsAwardedByOtherPurchases = array_unique( $secondaryGroupsAwardedByOtherPurchases );
			
			/* Loop through */
			foreach ( $secondaryGroupsAwardedByThisPurchase as $groupId )
			{
				/* If we had this group before we made this purchase, we're going to keep it */
				if ( in_array( $groupId, $membersPreviousSecondaryGroupsBeforeThisPurchase ) )
				{
					continue;
				}
				
				/* If we are being awarded this group by a different purchase, we're also going to keep it */
				if ( in_array( $groupId, $secondaryGroupsAwardedByOtherPurchases ) )
				{
					continue;
				}
				
				/* If we're still here, remove it */
				unset( $membersSecondaryGroups[ array_search( $groupId, $membersSecondaryGroups ) ] );
			}

			/* And make sure only valid groups are saved */
			$membersSecondaryGroups = array_filter( $membersSecondaryGroups, function( $group ){
				try
				{
					Group::load( $group );
					return TRUE;
				}
				catch( OutOfRangeException )
				{
					return FALSE;
				}
			});

			/* Save */
			$member->mgroup_others = implode( ',', $membersSecondaryGroups );
			$member->save();
			$member->logHistory( 'core', 'group', array( 'type' => 'secondary', 'by' => 'subscription', 'action' => 'remove', 'id' => $this->id, 'old' => $currentSecondaryGroups, 'new' => $membersSecondaryGroups ) );
		}
		else if ( $secondaryGroupsAwardedByThisPurchase )
		{
			$currentSecondaryGroups = $membersSecondaryGroups;
			foreach( $membersSecondaryGroups as $group )
			{
				if ( in_array( $group, $secondaryGroupsAwardedByThisPurchase ) )
				{
					unset( $membersSecondaryGroups[ array_search( $group, $membersSecondaryGroups ) ] );
				}
			}

			/* And make sure only valid groups are saved */
			$membersSecondaryGroups = array_filter( $membersSecondaryGroups, function( $group ){
				try
				{
					Group::load( $group );
					return TRUE;
				}
				catch( OutOfRangeException )
				{
					return FALSE;
				}
			});
			
			$member->mgroup_others = implode( ',', $membersSecondaryGroups );
			$member->save();
			$member->logHistory( 'core', 'group', array( 'type' => 'secondary', 'by' => 'subscription', 'action' => 'remove', 'id' => $this->id, 'old' => $currentSecondaryGroups, 'new' => $membersSecondaryGroups ) );
		}
	}
	
	/**
	 * Determines whether this package can be converted or not.
	 *
	 * @param	PackageClass	$package	The package we wish to convert
	 * @return boolean
	 */
	public static function canConvert( PackageClass $package ) : bool
	{
		if ( ! $package->lkey )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Update existing purchases
	 *
	 * @param	Purchase	$purchase							The purchase
	 * @param	array				$changes							The old values
	 * @param	bool				$cancelBillingAgreementIfNecessary	If making changes to renewal terms, TRUE will cancel associated billing agreements. FALSE will skip that change
	 * @return	void
	 */
	public function updatePurchase( Purchase $purchase, array $changes, bool $cancelBillingAgreementIfNecessary=FALSE ) : void
	{
		if ( array_key_exists( 'tax', $changes ) )
		{
			if ( !$purchase->billing_agreement or $cancelBillingAgreementIfNecessary )
			{
				if ( $billingAgreement = $purchase->billing_agreement )
				{
					try
					{
						$billingAgreement->cancel();
						$billingAgreement->save();
					}
					catch ( Exception ) { }
				}
				
				$purchase->tax = $this->tax;
				$purchase->save();
			}
		}
		
		if ( array_key_exists( 'renew_options', $changes ) and !empty( $changes['renew_options'] ) )
		{
			$newRenewTerms = json_decode( $this->renew_options, TRUE );

			if( !is_array( $newRenewTerms ) )
			{
				$newRenewTerms = array();
			}
						
			switch ( $changes['renew_options']['new'] )
			{
				case 'z':
					$purchase->renewals = NULL;
					$purchase->save();
					if ( $billingAgreement = $purchase->billing_agreement )
					{
						try
						{
							$billingAgreement->cancel();
							$billingAgreement->save();
						}
						catch ( Exception ) { }
					}
					break;
				case 'y':
					$purchase->renewals = NULL;
					$purchase->active = TRUE;
					$purchase->save();
					if ( $billingAgreement = $purchase->billing_agreement )
					{
						try
						{
							$billingAgreement->cancel();
							$billingAgreement->save();
						}
						catch ( Exception ) { }
					}
					break;
				case 'x':
					$purchase->renewals = NULL;
					$purchase->active = FALSE;
					$purchase->save();
					if ( $billingAgreement = $purchase->billing_agreement )
					{
						try
						{
							$billingAgreement->cancel();
							$billingAgreement->save();
						}
						catch ( Exception ) { }
					}
					break;
				case '-':
					// do nothing
					break;
				default:
					if ( $changes['renew_options']['new'] === 'o' )
					{
						if ( !$purchase->billing_agreement or $cancelBillingAgreementIfNecessary )
						{
							if ( $billingAgreement = $purchase->billing_agreement )
							{
								try
								{
									$billingAgreement->cancel();
									$billingAgreement->save();
								}
								catch ( Exception ) { }
							}
							
							
							$tax = NULL;
							if ( $purchase->tax )
							{
								try
								{
									$tax = Tax::load( $purchase->tax );
								}
								catch ( OutOfRangeException ) { }
							}
							
							$currency = $purchase->renewal_currency ?: $purchase->member->defaultCurrency( );

							$purchase->renewals = new RenewalTerm(
								new Money( $newRenewTerms['cost'][ $currency ]['amount'], $currency ),
								new DateInterval( 'P' . $newRenewTerms['term'] . mb_strtoupper( $newRenewTerms['unit'] ) ),
								$tax
							);
							$purchase->save();
						}
					}
					break;
			}
		}
		
		if ( array_key_exists( 'primary_group', $changes ) or array_key_exists( 'secondary_group', $changes ) AND ( !$purchase->expire OR $purchase->expire->getTimestamp() > time() ) )
		{
			$this->_removeUsergroups( $purchase->member );
			$this->_addUsergroups( $purchase->member );
		}
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		return $this->_image;
	}
}