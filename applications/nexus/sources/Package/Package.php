<?php
/**
 * @brief		Package Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content\Search\Index;
use IPS\DateTime;
use IPS\Db;
use IPS\downloads\File;
use IPS\Email;
use IPS\File as SystemFile;
use IPS\File\Iterator;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Lang;
use IPS\Math\Number;
use IPS\Member;
use IPS\Member\Group;
use IPS\nexus\extensions\nexus\Item\Package as ItemPackage;
use IPS\nexus\extensions\nexus\Item\UpgradeCharge;
use IPS\nexus\Package\CustomField;
use IPS\nexus\Package\Item;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use OutOfBoundsException;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_null;
use function strlen;
use function substr;
use const IPS\CACHE_PAGE_TIMEOUT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package
 */
class Package extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'nexus_reg_product_count' );
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_packages';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'p_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static string $parentNodeColumnId = 'group';
		
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static string $parentNodeClass = 'IPS\nexus\Package\Group';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'menu__nexus_store_packages';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'nexus_package_';

	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;

	/**
	 * Package Types
	 *
	 * @return	array
	 */
	public static function packageTypes() : array
	{
		return array(
			'Product'	=> 'IPS\nexus\Package\Product',
			'Ad'		=> 'IPS\nexus\Package\Ad'
		);
	}
	
	/* !ActiveRecord */
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		$types		= static::packageTypes();
		$classname	= $types[IPS::mb_ucfirst($data['p_type'])] ?? '';

		if ( $classname and isset( $classname::$packageDatabaseTable ) )
		{
			$data = array_merge( $data, Db::i()->select( '*', $classname::$packageDatabaseTable, array( 'p_id=?', $data['p_id'] ) )->first() );
		}

		/* Initiate an object */
		$obj = ( $classname ) ? new $classname : new static;
		$obj->_new = FALSE;
		
		/* Import data */
		$databasePrefixLength = strlen( static::$databasePrefix );
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
		'module'	=> 'store',
		'prefix'	=> 'packages_',
	);
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->member_groups = '*';
		$this->date_added = time();
		$this->store = TRUE;
	}
		
	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		$data = $this->changed;

		$secondaryTable = array();
		foreach ( $this->changed as $k => $v )
		{
			if ( isset( static::$packageDatabaseColumns ) and in_array( "p_{$k}", static::$packageDatabaseColumns ) )
			{
				$secondaryTable[ "p_{$k}" ] = $v;
				unset( $this->changed[ $k ] );
			}
			elseif ( !in_array( "p_{$k}", array( 'p_id', 'p_name', 'p_seo_name', 'p_desc', 'p_group', 'p_stock', 'p_reg', 'p_store', 'p_member_groups', 'p_allow_upgrading', 'p_upgrade_charge', 'p_allow_downgrading', 'p_downgrade_refund', 'p_base_price', 'p_tax', 'p_renewal_days', 'p_primary_group', 'p_secondary_group', 'p_return_primary', 'p_return_secondary', 'p_position', 'p_associable', 'p_force_assoc', 'p_assoc_error', 'p_discounts', 'p_page', 'p_featured', 'p_upsell', 'p_notify', 'p_type', 'p_custom', 'p_reviewable', 'p_review_moderate', 'p_image', 'p_methods', 'p_renew_options', 'p_group_renewals', 'p_rebuild_thumb', 'p_renewal_days_advance', 'p_date_added', 'p_reviews', 'p_rating', 'p_grace_period', 'p_email_purchase_type', 'p_email_purchase', 'p_email_expire_soon_type', 'p_email_expire_soon', 'p_email_expire_type', 'p_email_expire', 'p_initial_term' ) ) )
			{
				unset( $this->changed[ $k ] );
			}
		}
		
		if ( isset( $data['base_price'] ) )
		{
			$decoded = json_decode( $data['base_price'], TRUE );
			if ( $decoded and is_array( $decoded ) )
			{
				$prices = array( 'id' => $this->id );
				foreach ( $decoded as $currency => $value )
				{
					if ( !Db::i()->checkForColumn( 'nexus_package_base_prices', $currency ) )
					{
						Db::i()->addColumn( 'nexus_package_base_prices', array(
							'name'	=> $currency,
							'type'	=> 'FLOAT'
						) );
					}
					$prices[ $currency ] = $value['amount'];
				}
				Db::i()->replace( 'nexus_package_base_prices', $prices );
			}
		}
		
		$this->date_updated = time();
		parent::save();
		$this->changed = $data;
		
		if ( !empty( $secondaryTable ) AND isset( static::$packageDatabaseTable ) )
		{
			Db::i()->update( static::$packageDatabaseTable, $secondaryTable, array( 'p_id=?', $this->id ) );
		}
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		$this->item()->delete();
		parent::delete();

		if( isset( static::$packageDatabaseTable ) )
		{
			Db::i()->delete( static::$packageDatabaseTable, array( 'p_id=?', $this->id ) );
		}
		
		/* Translatables */
		foreach ( array( '_assoc', '_page', '_email_purchase_subject', '_email_expire_soon_subject', '_email_expire_subject' ) as $suffix )
		{
			Lang::deleteCustom( 'nexus', "nexus_package_{$this->id}{$suffix}" );
		}
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone() : void
	{
		if( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$primaryTable = array();
		$secondaryTable = array();
		foreach ( $this->_data as $k => $v )
		{
			if ( !in_array( $k, array( 'id', 'reviews', 'unapproved_reviews', 'hidden_reviews' ) ) )
			{
				if ( isset( static::$packageDatabaseColumns ) and in_array( "p_{$k}", static::$packageDatabaseColumns ) )
				{
					$secondaryTable[ "p_{$k}" ] = $v;
				}
				else
				{
					$primaryTable[ "p_{$k}" ] = $v;
				}
			}
		}

		/* We need to update the date_added field so cloned products appear in the new products block */
		$primaryTable['p_date_added'] = time();

		$oldId = $this->_id;
		$id = Db::i()->insert( 'nexus_packages', $primaryTable );

		$secondaryTable['p_id'] = $id;
		Db::i()->insert( static::$packageDatabaseTable, $secondaryTable );
		
		/* Translatables */
		Lang::saveCustom( 'nexus', static::$titleLangPrefix . $id, iterator_to_array( Db::i()->select( 'CONCAT(word_custom, \' ' . Member::loggedIn()->language()->get('copy_noun') . '\') as word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$titleLangPrefix . $this->_id ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
		Lang::saveCustom( 'nexus', static::$titleLangPrefix . $id . static::$descriptionLangSuffix, iterator_to_array( Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$titleLangPrefix . $oldId . static::$descriptionLangSuffix ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );

		foreach ( array( '_assoc', '_page', '_email_purchase_subject', '_email_expire_soon_subject', '_email_expire_subject' ) as $suffix )
		{
			Lang::saveCustom( 'nexus', static::$titleLangPrefix . $id . $suffix, iterator_to_array( Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$titleLangPrefix . $oldId . $suffix ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
		}

		Db::i()->insert( 'nexus_package_base_prices', Db::i()->select( "{$id} AS id, " . implode( ',', array_map( function( $val )
		{
			return "`{$val}`";
		}, Money::currencies() ) ), 'nexus_package_base_prices', array( 'id=?', $this->_id ) ) );
		
		foreach ( Db::i()->select( '*', 'nexus_package_images', array( 'image_product=?', $this->_id ) ) as $image )
		{
			$file = SystemFile::get( 'nexus_Products', $image['image_location'] );
					
			Db::i()->insert( 'nexus_package_images', array(
				'image_product'		=> $id,
				'image_location'	=> (string) SystemFile::create( 'nexus_Products', $file->originalFilename, $file->contents(), $file->container ),
				'image_primary'		=> $image['image_primary']
			) );
		}
		
		Db::i()->insert( 'nexus_product_options', Db::i()->select( "NULL as opt_id, {$id} AS opt_package, opt_values, opt_stock, opt_base_price, opt_renew_price", 'nexus_product_options', array( 'opt_package=?', $this->_id ) ) );
		
		Db::i()->update( 'nexus_package_fields', "cf_packages=CONCAT( cf_packages, ',{$id}' )", Db::i()->findInSet( 'cf_packages', array( $this->_id ) ) );
		Db::i()->update( 'nexus_referral_rules', "rrule_purchase_packages=CONCAT( rrule_purchase_packages, ',{$id}' )", Db::i()->findInSet( 'rrule_purchase_packages', array( $this->_id ) ) );

		$primaryKey = static::$databaseColumnId;
		$this->$primaryKey = $id;
	}
	
	/* !Properties */
	
	/**
	 * Get the lowest price for multiple packages
	 * May return price ($x) or "From $x" or a price struck and replaced with a discount price
	 *
	 * @param	ActiveRecordIterator	$iterator	Iterator of packages
	 * @param Customer|NULL			$customer	The customer (NULL for currently logged in member)
	 * @param bool $returnMoney
	 * @return    string|Money
	 */
	public static function lowestPriceToDisplay( ActiveRecordIterator $iterator, Customer $customer = NULL, bool $returnMoney = false ): string|Money
	{
		$customer = $customer ?: Customer::loggedIn();
		$currency = ( $customer->member_id === Customer::loggedIn()->member_id ) ? ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : $customer->defaultCurrency() ) : $customer->defaultCurrency();
		
		$lowest = NULL;
		$_priceMayChange = FALSE;
		$priceMayChange = FALSE;
		foreach ( $iterator as $package )
		{
			/* @var Package $package */
			$price = $package->_lowestPrice( $customer, $currency, $priceMayChange );
			if ( $lowest !== NULL and $price !== $lowest )
			{
				$_priceMayChange = TRUE;
			}
			if ( $lowest === NULL or $price < $lowest )
			{
				$lowest = $price;
			}
		}

		if( $returnMoney )
		{
			return new Money( $lowest, $currency );
		}
		
		return Theme::i()->getTemplate( 'store', 'nexus' )->price( new Money( $lowest, $currency ), $priceMayChange or $_priceMayChange );
	}

	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'box';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'product';
		
	/**
	 * Basic purchase price
	 * May be further adjusted by renewal terms which add
	 *
	 * @param Customer|NULL $customer			The customer (NULL for currently logged in member)
	 * @param bool $usergroupDiscounts	Account for usergroup discounts?
	 * @param bool $loyaltyDiscounts	Account for loyalty discounts?
	 * @param bool $bulkDiscounts		Account for bulk discounts?
	 * @param int $initialCount		Will assume that number of additional purchases for this package
	 * @param string|NULL $currency			Currency to use or NULL to use customer's default currency
	 * @return    Money
	 * @throws	OutOfBoundsException
	 */
	public function price(?Customer $customer = NULL, bool $usergroupDiscounts = TRUE, bool $loyaltyDiscounts = TRUE, bool $bulkDiscounts = TRUE, int $initialCount = 0, ?string $currency = NULL ): Money
	{
		return static::priceFromData(
			$this->id,
			$this->base_price ? json_decode( $this->base_price, TRUE ) : array(),
			$this->discounts ? json_decode( $this->discounts, TRUE ) : array(),
			$customer,
			$currency,
			$usergroupDiscounts,
			$loyaltyDiscounts,
			$bulkDiscounts,
			$initialCount
		);
	}
	
	/**
	 * Basic purchase price
	 * May be further adjusted by renewal terms which add
	 *
	 * @param int $packageId				Package ID
	 * @param array $prices					Base Price data
	 * @param array $discounts				Discount data
	 * @param Customer|null $customer				The customer (NULL for currently logged in member)
	 * @param string|null $currency				The currency (NULL to autodetect based on $customer)
	 * @param bool $usergroupDiscounts		Account for usergroup discounts?
	 * @param bool $loyaltyDiscounts		Account for loyalty discounts?
	 * @param bool $bulkDiscounts			Account for bulk discounts?
	 * @param int $initialCount			Will assume that number of additional purchases for this package
	 * @return    Money
	 * @throws	OutOfBoundsException
	 */
	public static function priceFromData( int $packageId, array $prices, array $discounts, ?Customer $customer = NULL, ?string $currency = NULL, bool $usergroupDiscounts = TRUE, bool $loyaltyDiscounts = TRUE, bool $bulkDiscounts = TRUE, int $initialCount = 0 ): Money
	{
		/* Base */
		$customer = $customer ?: Customer::loggedIn();
		if ( !$currency )
		{
			$currency = ( $customer->member_id === Customer::loggedIn()->member_id ) ? ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : $customer->defaultCurrency() ) : $customer->defaultCurrency();
		}
		if ( !isset( $prices[ $currency ] ) )
		{
			throw new OutOfBoundsException;
		}
		$price = $prices[ $currency ]['amount'];
		
		/* Usergroup discounts */
		if ( $usergroupDiscounts and isset( $discounts['usergroup'] ) )
		{
			foreach ( $discounts['usergroup'] as $discount )
			{
				if ( $discount['price'][ $currency ] < $price )
				{
					if ( $discount['secondary'] )
					{
						$inGroup = $customer->inGroup( $discount['group'] );
					}
					else
					{
						$inGroup = $customer->member_group_id == $discount['group'];
					}
										
					if ( $inGroup )
					{
						$price = $discount['price'][ $currency ];
					}
				}
			}
		}
		
		/* Loyalty discounts */
		if ( $loyaltyDiscounts and isset( $discounts['loyalty'] ) )
		{
			foreach ( $discounts['loyalty'] as $discount )
			{
				$comparingPackageId = ( $discount['package'] ?: $packageId );
				$count = ( $comparingPackageId == $packageId ) ? $initialCount : 0;
				if ( $discount['price'][ $currency ] < $price )
				{
					$count += $customer->member_id ? $customer->previousPurchasesCount( $discount['package'] ?: $packageId, $discount['active'] ) : 0;
				}
				if ( $customer->member_id === Member::loggedIn()->member_id and isset( $_SESSION['cart'] ) )
				{
					foreach ( $_SESSION['cart'] as $item )
					{
						if ( $item->id == $comparingPackageId )
						{
							$count += $item->quantity;
						}
					}
				}
				
				if ( $count >= $discount['owns'] )
				{
					$price = $discount['price'][ $currency ];
				}
			}
		}
		
		/* Bulk Discounts */
		if ( $bulkDiscounts and isset( $discounts['bulk'] ) )
		{
			/* Make sure we're only applying the highest quantity discount */
			rsort( $discounts['bulk'] );
			$discount = array_shift( $discounts['bulk'] );

			$discountPackageId = ( $discount['package'] ?: $packageId );
			$count = ( $discountPackageId == $packageId ) ? $initialCount : 0;
			if ( $customer->member_id === Member::loggedIn()->member_id and isset( $_SESSION['cart'] ) )
			{
				foreach ( $_SESSION['cart'] as $item )
				{
					if ( $item->id == $discountPackageId )
					{
						$count += $item->quantity;
					}
				}
			}

			if ( $count and ( ( $count + 1 ) % ( $discount['buying'] + 1 ) == 0 ) )
			{
				$price = $discount['price'][ $currency ];
			}
		}

		/* Return */
		return new Money( $price, $currency );
	}
		
	/**
	 * Get the lowest price
	 *
	 * @param Customer|NULL $customer					The customer
	 * @param string $currency					Currency
	 * @param bool $priceMayChange				[Reference] will be set to a value indicating if the price might change (i.e. should be displayed as "From $x")
	 * @param Number|null $priceNotIncludingDiscounts	[Reference] will be set to the lowest price not taking discounts into consideration
	 * @param RenewalTerm|null $relevantRenewalTerm		[Reference] will be set to the renewal term which would need to be selected for this price
	 * @return    Number
	 */
	protected function _lowestPrice(?Customer $customer, string $currency, bool &$priceMayChange = FALSE, ?Number &$priceNotIncludingDiscounts = NULL, ?RenewalTerm &$relevantRenewalTerm = NULL ): Number
	{
		$renewOptions = $this->renew_options ? json_decode( $this->renew_options, TRUE ) : array();
		
		$tax = NULL;
		if ( Settings::i()->nexus_show_tax and $this->tax )
		{
			try
			{
				$tax = Tax::load( $this->tax );
			}
			catch ( OutOfRangeException ) { }
		}
		
		return static::lowestPriceFromData( $customer, $currency, $this->id, json_decode( $this->base_price, TRUE ), json_decode( $this->discounts, TRUE ), $renewOptions, $this->stock, $tax, $priceMayChange, $priceNotIncludingDiscounts, $relevantRenewalTerm );
	}
	
	/**
	 * Get the lowest price
	 *
	 * @param Customer $customer					The customer
	 * @param string $currency					Currency
	 * @param int $packageId					Package ID
	 * @param array $prices						Base Price data
	 * @param array $discounts					Discount data
	 * @param array $renewOptions				Renewal options data
	 * @param int $stock						Stock type (-2 for if it adjusts on fields)
	 * @param Tax|NULL $tax						Tax rate
	 * @param bool $priceMayChange				[Reference] will be set to a value indicating if the price might change (i.e. should be displayed as "From $x")
	 * @param Number|null $priceNotIncludingDiscounts	[Reference] will be set to the lowest price not taking discounts into consideration
	 * @param RenewalTerm|null $relevantRenewalTerm		[Reference] will be set to the renewal term which would need to be selected for this price
	 * @return    Number
	 */
	static function lowestPriceFromData(Customer $customer, string $currency, int $packageId, array $prices, array $discounts, array $renewOptions, int $stock, ?Tax $tax, bool &$priceMayChange = FALSE, ?Number &$priceNotIncludingDiscounts = NULL, ?RenewalTerm &$relevantRenewalTerm = NULL ): Number
	{
		/* What's the base price with and without discounts? */
		$baseNoDiscounts = static::priceFromData( $packageId, $prices, $discounts, $customer, $currency, FALSE, FALSE, FALSE )->amount;
		$base = static::priceFromData( $packageId, $prices, $discounts, $customer, $currency )->amount;

		/* Adjustments based on custom fields */
		if ( $stock == -2 )
		{
			$mostReducedOption = NULL;
			foreach ( Db::i()->select( '*', 'nexus_product_options', array( 'opt_package=?', $packageId ) ) as $option )
			{
				$basePriceAdjustments = json_decode( $option['opt_base_price'], TRUE );
				if ( isset( $basePriceAdjustments[ $currency ] ) and ( $basePriceAdjustments[ $currency ] or $basePriceAdjustments[ $currency ] === '0' ) )
				{
					if ( $mostReducedOption === NULL or $basePriceAdjustments[ $currency ] < $mostReducedOption )
					{
						$mostReducedOption = new Number( number_format( $basePriceAdjustments[ $currency ], Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) );
					}
					
					$priceMayChange = TRUE;
				}
			}
			$mostReducedOption = $mostReducedOption ?: new Number('0');
			
			$baseNoDiscounts = $baseNoDiscounts->add( $mostReducedOption );
			$base = $base->add( $mostReducedOption );
		}
		
		/* Init */
		$lowestNoDiscounts = $baseNoDiscounts;
		$lowest = $base;

		/* It's possible the base price is $0, but all the renewal options add, so let's look at that */
		if ( !empty( $renewOptions ) )
		{
			$lowestNoDiscounts = NULL;
			$lowest = NULL;
			foreach ( $renewOptions as $term )
			{
				if ( $term['add'] )
				{
					if ( count( $renewOptions ) > 1 )
					{
						$priceMayChange = TRUE;
					}
					$_lowest = $base->add( new Number( number_format( $term['cost'][ $currency ]['amount'], Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) ) );
					$_lowestNoDiscounts = $baseNoDiscounts->add( new Number( number_format( $term['cost'][ $currency ]['amount'], Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) ) );
				}
				else
				{
					$_lowest = $base;
					$_lowestNoDiscounts = $baseNoDiscounts;
				}
				
				if ( $lowest === NULL or $_lowest->compare( $lowest ) === -1 )
				{
					$lowest = $_lowest;
					if ( $term['add'] )
					{
						$relevantRenewalTerm = new RenewalTerm( new Money( new Number( $term['cost'][ $currency ]['amount'] ), $currency ), new DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ), $tax, $term['add'] );
					}
					else
					{
						$relevantRenewalTerm = NULL;
					}
				}
				if ( $lowestNoDiscounts === NULL or $_lowestNoDiscounts->compare( $lowestNoDiscounts ) === -1 )
				{
					$lowestNoDiscounts = $_lowestNoDiscounts;
				}
			}
		}
					
		/* Add tax? */
		if ( Settings::i()->nexus_show_tax and $tax )
		{
			/* What is the rate? */
			$taxRate = new Number( $tax->rate( $customer->estimatedLocation() ) );
			
			/* Add it on */
			$lowest = $lowest->add( $lowest->multiply( $taxRate ) );
			$lowestNoDiscounts = $lowestNoDiscounts->add( $lowestNoDiscounts->multiply( $taxRate ) );
		}

		/* Return */
		$priceNotIncludingDiscounts = $lowestNoDiscounts;
		return $lowest;
	}
		
	/**
	 * Price to display in store
	 * May return price ($x) or "From $x" or a price struck and replaced with a discount price or NULL if not available in desired currency
	 *
	 * @param Customer|NULL $customer					The customer (NULL for currently logged in member)
	 * @param bool $includePriceDescription	If TRUE, will include the "Price Description" setting (normally used to indicate if price includes tax)
	 * @return    string|NULL
	 */
	public function priceToDisplay( ?Customer $customer = NULL, bool $includePriceDescription=TRUE ): string|null
	{
		/* Get customer */
		$customer = $customer ?: Customer::loggedIn();
		$currency = ( $customer->member_id === Customer::loggedIn()->member_id ) ? ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : $customer->defaultCurrency() ) : $customer->defaultCurrency();
		
		/* Get the price */
		$priceMayChange = FALSE;
		$priceNotIncludingDiscounts = NULL;
		try
		{
			$price = $this->_lowestPrice( $customer, $currency, $priceMayChange, $priceNotIncludingDiscounts );
		}
		catch ( OutOfBoundsException )
		{
			return NULL;
		}

		/* Display */
		if ( $price < $priceNotIncludingDiscounts )
		{
			return Theme::i()->getTemplate( 'store', 'nexus' )->priceDiscounted( new Money( $priceNotIncludingDiscounts, $currency ), new Money( $price, $currency ), $priceMayChange, $includePriceDescription );
		}
		else
		{
			return Theme::i()->getTemplate( 'store', 'nexus' )->price( new Money( $price, $currency ), $priceMayChange, $includePriceDescription );
		}
	}
	
	/**
	 * Get full price info
	 *
	 * @param Customer|NULL $customer					The customer
	 * @return    array|NULL
	 */
	public function fullPriceInfo(?Customer $customer = NULL ): array|null
	{
		$customer = $customer ?: Customer::loggedIn();
		$currency = ( $customer->member_id === Customer::loggedIn()->member_id ) ? ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : $customer->defaultCurrency() ) : $customer->defaultCurrency();
		
		$renewOptions = $this->renew_options ? json_decode( $this->renew_options, TRUE ) : array();
		
		$tax = NULL;
		if ( Settings::i()->nexus_show_tax and $this->tax )
		{
			try
			{
				$tax = Tax::load( $this->tax );
			}
			catch ( OutOfRangeException ) { }
		}
		
		try
		{
			return static::fullPriceInfoFromData( $customer, $currency, $this->id, $this->base_price ? json_decode( $this->base_price, TRUE ) : array(), $this->discounts ? json_decode( $this->discounts, TRUE ) : array(), $renewOptions, $this->stock, $tax, $this->initial_term ? new DateInterval( "P{$this->initial_term}" ) : NULL );
		}
		catch( OutOfBoundsException )
		{
			return NULL;
		}
	}
	
	/**
	 * Get full price info from specific data
	 *
	 * @param Customer|NULL			$customer					The customer
	 * @param string $currency					Currency
	 * @param int $packageId					Package ID
	 * @param array $prices						Base Price data
	 * @param array $discounts					Discount data
	 * @param array $renewOptions				Renewal options data
	 * @param int $stock						Stock type (-2 for if it adjusts on fields)
	 * @param Tax|NULL $tax						Tax rate
	 * @param DateInterval|NULL $initialTerm				The initial term
	 * @return    array|null
	 */
	public static function fullPriceInfoFromData(?Customer $customer, string $currency, int $packageId, array $prices, array $discounts, array $renewOptions, int $stock, ?Tax $tax, ?DateInterval $initialTerm ): array|null
	{
		/* Get customer / currency */
		$customer = $customer ?: Customer::loggedIn();

		/* Get the initial price */
		$priceMayChange = FALSE;
		$priceNotIncludingDiscounts = NULL;
		$relevantRenewalTerm = NULL;
		
		try
		{
			$price = static::lowestPriceFromData( $customer, $currency, $packageId, $prices, $discounts, $renewOptions, $stock, $tax, $priceMayChange, $priceNotIncludingDiscounts, $relevantRenewalTerm );		
		}
		catch ( OutOfBoundsException )
		{
			return NULL;
		}
		
		$primaryPrice = new Money( $price, $currency );
		
		/* Renewals */
		$renewalPrice = NULL;
		$renewalPriceMayChange = FALSE;
		if ( $renewOptions )
		{
			if ( count( $renewOptions ) === 1 and isset( $renewOptions[0]['cost'][ $currency ] ) and $renewOptions[0]['cost'][ $currency ]['amount'] == $price and !$renewOptions[0]['add'] )
			{
				$primaryPrice = new RenewalTerm( $primaryPrice, new DateInterval( "P{$renewOptions[0]['term']}" . mb_strtoupper( $renewOptions[0]['unit'] ) ), $tax );

				if ( $initialTerm !== NULL )
				{
					$initialTerm = ( new RenewalTerm( [], $initialTerm ) )->getTermUnit();
				}
			}
			else
			{
				$renewalPriceMayChange = ( count( $renewOptions ) > 1 );
				if ( !$relevantRenewalTerm )
				{
					if ( count( $renewOptions ) === 1 and isset( $renewOptions[0]['cost'][ $currency ] ) )
					{
						$relevantRenewalTerm = new RenewalTerm( new Money( $renewOptions[0]['cost'][ $currency ]['amount'], $currency ), new DateInterval( "P{$renewOptions[0]['term']}" . mb_strtoupper( $renewOptions[0]['unit'] ) ), $tax );
					}
					else
					{
						foreach ( $renewOptions as $option )
						{
							if ( isset( $option['cost'][ $currency ] ) and ( $relevantRenewalTerm === NULL or $relevantRenewalTerm->cost->amount->compare( new Number( "{$option['cost'][ $currency ]['amount']}" ) ) === 1 ) )
							{
								$relevantRenewalTerm = new RenewalTerm( new Money( $option['cost'][ $currency ]['amount'], $currency ), new DateInterval( "P{$option['term']}" . mb_strtoupper( $option['unit'] ) ), $tax );
							}
						}
					}
				}
				$renewalPrice = $relevantRenewalTerm;
				
				if ( $initialTerm !== NULL )
				{
					$initialTerm = ( new RenewalTerm( [], $initialTerm ) )->getTermUnit();
				}
				else
				{
					if ( $relevantRenewalTerm->cost->amount?->compare( $primaryPrice->amount ) === 0 )
					{
						$priceMayChange = TRUE;
						$primaryPrice = $relevantRenewalTerm;
					}
					else
					{
						$initialTerm = $renewalPrice->getTermUnit();
					}
				}
			}
		}
		

		/* Return */
		return array(
			'primaryPrice'					=> $priceMayChange ? $customer->language()->addToStack( 'price_from', FALSE, array( 'sprintf' => array( $primaryPrice ) ) ) : ( $price->isZero() ? $customer->language()->addToStack('nexus_sub_cost_free') : $primaryPrice ),
			'primaryPriceIsZero'			=> $price ? $price->isZero() : FALSE,
			'primaryPriceDiscountedFrom'	=> ( $price < $priceNotIncludingDiscounts ) ? new Money( $priceNotIncludingDiscounts, $currency ) : NULL,
			'initialTerm'					=> $initialTerm,
			'renewalPrice'					=> $renewalPriceMayChange ? $customer->language()->addToStack( 'price_from', FALSE, array( 'sprintf' => array( $renewalPrice->toDisplay( $customer ) ) ) ) : ($renewalPrice?->toDisplay($customer)),
		);
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();

	/**
	 * Get URL
	 *
	 * @param string|null $action		Action
	 * @return    Url
	 */
	public function url( string $action=NULL ): Url
	{
		$_key = $action ? md5( $action ) : NULL;

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = Url::internal( "app=nexus&module=store&controller=product&id={$this->id}", 'front', 'store_product', Friendly::seoTitle( Member::loggedIn()->language()->get( 'nexus_package_' . $this->id ) ) );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}

	/**
	 * Get full image URL
	 *
	 * @return string|null
	 */
	public function get_image() : string|null
	{
		return ( $this->_data['image'] ) ? (string) SystemFile::get( 'nexus_Products', $this->_data['image'] )->url : NULL;
	}
		
	/**
	 * Get additional name info
	 *
	 * @param Purchase $purchase	The purchase
	 * @return	array
	 */
	public function getPurchaseNameInfo( Purchase $purchase ) : array
	{
		$stickyFields = array();
		if ( count( $purchase->custom_fields ) )
		{
			$customFields = CustomField::roots();
			foreach ( $purchase->custom_fields as $k => $v )
			{
				if ( $v and isset( $customFields[ $k ] ) and $customFields[ $k ]->sticky )
				{
					$stickyFields[] = strip_tags( CustomField::load( $k )->displayValue( $v, TRUE ) ); // We need to call displayValue so things like dates and select boxes show the right value, but we *also* need to call strip_tags as Url fields will return full links and we need text only. Note that fields which would show something other than a simple text output are prevented from being used in this context
				}
			}
		}
		return $stickyFields;
	}
	
	/**
	 * @brief	Associable packages
	 */
	protected ?array $_associablePackages = NULL;
	
	/**
	 * Associable Packages
	 *
	 * @return	array|null
	 */
	public function associablePackages() : array|null
	{
		if ( $this->_associablePackages === NULL )
		{
			$this->_associablePackages = array();

			if( $this->associable )
			{
				foreach ( explode( ',', $this->associable ) as $id )
				{
					try
					{
						$this->_associablePackages[ $id ] = Package::load( $id );
					}
					catch ( OutOfRangeException ) { }
				}
			}
		}
		return $this->_associablePackages;
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
		
		$_buttons = parent::getButtons( $url, $subnode );
		if ( isset( $_buttons['edit'] ) )
		{
			$buttons['edit'] = $_buttons['edit'];
		}
						
		if ( !$this->deleteOrMoveQueued() and ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_edit' ) or Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_cancel' ) ) )
		{
			$buttons['cancel_purchases'] = array(
				'icon'	=> 'arrow-right',
				'title'	=> 'mass_change_all_purchases',
				'link'	=> $url->setQueryString( array( 'do' => 'massManagePurchases', 'id' => $this->_id ) ),
				'data'	=> array(
					'ipsDialog'			=> TRUE,
					'ipsDialog-title'	=> $this->_title
				)
			);
		}
		
		if ( isset( $_buttons['delete'] ) )
		{
			unset( $_buttons['delete']['data']['delete'] );
			$_buttons['delete']['data']['confirm'] = TRUE;
		}
		
		$buttons = array_merge( $buttons, $_buttons );
		
		if ( !$this->deleteOrMoveQueued() and Member::loggedIn()->hasAcpRestriction( 'nexus', 'subscriptions', 'subscriptions_manage' ) and Subscription\Package::canConvert( $this ) )
		{
			$buttons['convert_subs'] = array(
				'icon'	=> 'certificate',
				'title'	=> 'nexus_subs_convert',
				'link'	=> Url::internal( 'app=nexus&module=subscriptions&controller=subscriptions&do=convertToSubscription&id=' . $this->_id )
			);
		}

		/* If the product is locked, don't allow any edits or changes */
		if( $this->locked )
		{
			foreach( array( 'edit', 'copy', 'convert_subs' ) as $k )
			{
				if( isset( $buttons[ $k ] ) )
				{
					unset( $buttons[ $k ] );
				}
			}
		}
				
		return $buttons;
	}

	/**
	 * [Node] Get Node Description
	 *
	 * @return	string|null
	 */
	protected function get__description(): ?string
	{
		if( $this->locked )
		{
			return Member::loggedIn()->language()->addToStack( 'product_locked_deprecated' );
		}

		return NULL;
	}

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge(): ?array
	{
		if ( $this->deleteOrMoveQueued() === TRUE )
		{
			return array(
				0	=> 'ipsBadge ipsBadge--intermediary',
				1	=> 'mass_change_purchases_in_progress',
			);
		}

		if( $this->locked )
		{
			return array(
				0	=> 'ipsBadge ipsBadge--negative',
				1 	=> 'product_locked'
			);
		}

		return NULL;
	}
	
	/**
	 * Is this node currently queued for deleting or moving content?
	 *
	 * @return	bool
	 */
	public function deleteOrMoveQueued(): bool
	{
		/* If we already know, don't bother */
		if ( is_null( $this->queued ) )
		{
			$this->queued = FALSE;

			if ( !is_array( static::$deleteOrMoveQueue ) )
			{
				static::$deleteOrMoveQueue = iterator_to_array( Db::i()->select( 'data', 'core_queue', array( 'app=? AND `key`=?', 'nexus', 'MassChangePurchases' ) ) );
			}

			foreach( static::$deleteOrMoveQueue AS $row )
			{
				$data = json_decode( $row, TRUE );
				if ( $data['id'] == $this->_id or ( $data['cancel_type'] == 'change' and $data['mass_change_purchases_to'] == $this->_id ) )
				{
					$this->queued = TRUE;
				}
			}
		}

		return $this->queued;
	}
		
	/* !ACP Package Form */
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{		
		$groups = array();
		foreach ( Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		$groupsExcludingGuestsAndAdmins = array();
		foreach ( Group::groups( FALSE, FALSE ) as $group )
		{
			$groupsExcludingGuestsAndAdmins[ $group->g_id ] = $group->name;
		}

		$types = array();
		$typeFields = array();
		$typeFieldToggles = array();
		$formId = $this->id ? "form_{$this->id}" : 'form_new';
		foreach ( static::packageTypes() as $key => $class )
		{
			$forceShow = TRUE;
			$types[ mb_strtolower( $key ) ] = 'p_type_' . $key;

			/* @var Package $class */
			if ( $formFields = $class::acpFormFields( $this  ) )
			{
				foreach ( $formFields as $group => $fields )
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
			}
			
			if ( $forceShow )
			{
				$typeFieldToggles[ mb_strtolower( $key ) ] = array_merge( $typeFieldToggles[mb_strtolower($key)] ?? array(), array( "{$formId}_tab_package_client_area", "{$formId}_header_package_associations", "{$formId}_header_package_associations_desc", 'p_associate', "{$formId}_header_package_renewals", 'p_renews', 'p_lkey' ) );
			}
		}
						
		$renewOptions = array();
		if ( $this->renew_options and $_renewOptions = json_decode( $this->renew_options, TRUE ) and is_array( $_renewOptions ) )
		{
			foreach ( $_renewOptions as $option )
			{
				$costs = array();
				foreach ( $option['cost'] as $cost )
				{
					$costs[ $cost['currency'] ] = new Money( $cost['amount'], $cost['currency'] );
				}
				
				/* Catch any invalid renewal terms, these can occasionally appear from legacy IP.Subscriptions */
				try
				{
					$renewOptions[] = new RenewalTerm( $costs, new DateInterval( "P{$option['term']}" . mb_strtoupper( $option['unit'] ) ), NULL, $option['add'] );
				}
				catch( Exception ) {}
			}
		}
		
		$groupToggles = array();
		$availableFilters = array();
		foreach ( Db::i()->select( array( 'pg_id', 'pg_filters' ), 'nexus_package_groups', "pg_filters IS NOT NULL" ) as $group )
		{
			$groupToggles[ $group['pg_id'] ] = array_map( function( $filterId ) use ( &$availableFilters ) {
				$availableFilters[ $filterId ] = $filterId;
				return "nexus_filter_{$filterId}";
			} , explode( ',', $group['pg_filters'] ) );
			$groupToggles[ $group['pg_id'] ][] = "{$form->id}_header_pg_filters";
		}
								
		$form->addTab('package_settings');
		$form->addHeader('package_settings');
		$form->add( new Radio( 'p_type', ( $this->id ? $this->type : 'product' ), TRUE, array( 'options' => $types, 'toggles' => $typeFieldToggles, 'disabled' => (bool) $this->id ) ) );
		$form->add( new Translatable( 'p_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_package_{$this->id}" : NULL ) ) );
		$form->add( new Node( 'p_group', $this->group, TRUE, array( 'class' => 'IPS\nexus\Package\Group', 'subnodes' => FALSE, 'toggleIds' => $groupToggles ) ) );
		$form->add( new Node( 'p_custom_fields', $this->id ? CustomField::roots( NULL, NULL, array( array( Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ) ) ) : array(), FALSE, array( 'class' => 'IPS\nexus\Package\CustomField', 'multiple' => TRUE ) ) );
		foreach ( $typeFields['package_settings'] as $field )
		{
			$form->add( $field );
		}
		$form->addHeader('package_registration');
		$form->add( new YesNo( 'p_reg', $this->reg, FALSE, array(), function( $val )
		{
			if ( $val and ( !Request::i()->p_store_checkbox or ( Request::i()->p_member_groups and !in_array( Settings::i()->guest_group, array_keys( Request::i()->p_member_groups ) ) ) ) )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'p_reg_err', FALSE, array( 'sprintf' => array( Group::load( Settings::i()->guest_group )->name ) ) ) );
			}
		} ) );
		$form->addHeader('package_associations');
		$form->addMessage('package_associations_desc');
		$form->add( new Radio( 'p_associate', ( $this->force_assoc and count( $this->associablePackages() ) ) ? 2 : ( count( $this->associablePackages() ) ? 1 : 0 ), FALSE, array(
			'options' => array(
				0	=> 'p_associate_none',
				1	=> 'p_associate_optional',
				2	=> 'p_associate_required'
			),
			'toggles'	=> array(
				1	=> array( 'p_associable', 'p_group_renewals', 'p_upsell' ),
				2	=> array( 'p_associable', 'p_group_renewals', 'p_assoc_error_editor', 'p_upsell' )
			)
		), NULL, NULL, NULL, 'p_associate' ) );
		$form->add( new Node( 'p_associable', $this->associablePackages(), FALSE, array( 'class' => 'IPS\nexus\Package\Group', 'multiple' => TRUE, 'permissionCheck' => function( $node )
		{
			return !( $node instanceof Package\Group);
		} ), function( $val )
		{
			if ( $val)
			{
				foreach ( $val AS $item )
				{
					if ( $item->id == $this->id)
					{
						throw new DomainException( 'p_associable_error' );
					}
				}
			}
		}, NULL, NULL, 'p_associable' ) );

		$form->add( new YesNo( 'p_group_renewals', $this->group_renewals ?: FALSE, FALSE, array(), NULL, NULL, NULL, 'p_group_renewals' ) );
		$form->add( new Translatable( 'p_assoc_error', NULL, FALSE, array(
			'app' => 'nexus',
			'key' => $this->id ? "nexus_package_{$this->id}_assoc" : NULL,
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-pkg-{$this->id}-assoc" : "nexus-new-pkg-assoc" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'pkg-assoc' ) : NULL, 'minimize' => 'p_assoc_error_placeholder'
			)
		), NULL, NULL, NULL, 'p_assoc_error_editor' ) );
		$form->add( new YesNo( 'p_upsell', $this->upsell ?: FALSE, FALSE, array(), NULL, NULL, NULL, 'p_upsell' ) );

		$form->addTab( 'package_pricing' );
		$form->addHeader( 'package_purchase_price' );

		$form->add( new \IPS\nexus\Form\Money( 'p_base_price', $this->base_price, TRUE ) );
		$taxToggleIds = array();
		if ( !in_array( 'product', explode( ',', Settings::i()->nexus_require_billing ) ) )
		{
			foreach (Tax::roots() as $tax )
			{
				if ( $tax->type !== 'single' )
				{
					$taxToggleIds[ $tax->_id ] = array( 'elProductTaxWarning' );
				}
				elseif ( $rates = json_decode( $tax->rate, TRUE ) )
				{
					foreach ( $rates as $rate )
					{
						if ( $rate['locations'] !== '*' )
						{
							$taxToggleIds[ $tax->_id ] = array( 'elProductTaxWarning' );
							continue 2;
						}
					}
				}
			}
		}
		$taxField = new Node( 'p_tax', (int) $this->tax, FALSE, array( 'class' => 'IPS\nexus\Tax', 'zeroVal' => 'do_not_tax', 'toggleIds' => $taxToggleIds, 'zeroValTogglesOff' => array( 'elProductTaxWarning' ) ) );
		if ( !in_array( 'product', explode( ',', Settings::i()->nexus_require_billing ) ) )
		{
			$taxField->warningBox = Theme::i()->getTemplate('store')->productTaxWarning();
		}
		$form->add( $taxField );
		$form->addHeader('package_discounts');
		$discounts = $this->discounts ? json_decode( $this->discounts, TRUE ) : [];
		
		$form->add( new YesNo( 'p_set_discounts', ( is_array( $discounts ) AND count( $discounts ) ), FALSE, array( 'togglesOn' => array( 'p_usergroup_discounts', 'p_loyalty_discounts', 'p_bulk_discounts' ) ), NULL, NULL, NULL, 'p_set_discounts' ) );
		$form->add( new Stack( 'p_usergroup_discounts', $discounts['usergroup'] ?? array(), FALSE, array( 'stackFieldType' => 'IPS\nexus\Form\DiscountUsergroup' ), NULL, NULL, NULL, 'p_usergroup_discounts' ) );
		$form->add( new Stack( 'p_loyalty_discounts', $discounts['loyalty'] ?? array(), FALSE, array( 'stackFieldType' => 'IPS\nexus\Form\DiscountLoyalty' ), NULL, NULL, NULL, 'p_loyalty_discounts' ) );
		$form->add( new Stack( 'p_bulk_discounts', $discounts['bulk'] ?? array(), FALSE, array( 'stackFieldType' => 'IPS\nexus\Form\DiscountBulk' ), NULL, NULL, NULL, 'p_bulk_discounts' ) );
				
		$form->addHeader( 'package_renewals' );
		$form->add( new YesNo( 'p_renews', !empty( $renewOptions ), FALSE, array( 'togglesOn' => array( 'p_initial_term', 'p_renew_options', 'p_grace_period', 'p_renew', ( $this->id ? "form_{$this->id}" : 'form_new' ) . '_header_package_client_area_renewals', 'p_email_expire_soon_type', 'p_email_expire_type' ) ), NULL, NULL, NULL, 'p_renews' ) );
		$form->add( new \IPS\nexus\Form\RenewalTerm( 'p_initial_term', $this->initial_term ?: NULL, FALSE, array( 'initialTerm' => TRUE, 'initialTermLang' => 'term_same_as_renewal', 'lockPrice' => TRUE ), NULL, NULL, NULL, 'p_initial_term' ) );
		$form->add( new Stack( 'p_renew_options', $renewOptions, FALSE, array(
			'stackFieldType'	=> 'IPS\nexus\Form\RenewalTerm',
			'allCurrencies'		=> TRUE,
			'addToBase'			=> TRUE
		), NULL, NULL, NULL, 'p_renew_options' ) );
		$form->add( new Interval( 'p_grace_period', $this->grace_period ?: 0, FALSE, array( 'valueAs' => Interval::DAYS, 'max' => Settings::i()->cm_invoice_expireafter ?: NULL ), NULL, NULL, NULL, 'p_grace_period' ) );
		
		$form->addTab('package_stock_adjustments');
		$fields = new ActiveRecordIterator( Db::i()->select( '*', 'nexus_package_fields', array( array( '( ( cf_type=? OR cf_type=? ) AND cf_multiple=0 )', 'Select', 'Radio' ), Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ) ), 'IPS\nexus\Package\CustomField' );
		if ( count( $fields ) )
		{
			Member::loggedIn()->language()->words['p_stock_price_dynamic_desc'] = '';
		}
		$form->add( new Radio( 'p_stock_price_type', $this->stock == -2 ? 'dynamic' : 'static', TRUE, array(
			'options'	=> array(
				'static'	=> 'p_stock_price_static',
				'dynamic'	=> 'p_stock_price_dynamic',
			),
			'toggles'	=> array(
				'static'	=> array( 'p_stock' ),
				'dynamic'	=> array( 'p_dynamic_stock' )
			),
			'disabled'		=> count( $fields ) ? array() : array( 'dynamic' )
		) ) );
		$form->add( new Form\Number( 'p_stock', $this->id ? ( $this->stock == -2 ? -1 : $this->stock ) : -1, TRUE, array( 'unlimited' => -1 ), NULL, NULL, NULL, 'p_stock' ) );
		$package = $this;
		$form->add( new Custom( 'custom_fields', $this->optionIdKeys(), FALSE, array(
			'rowHtml'	=> function( $input ) use ( $fields, $package )
			{
				return Theme::i()->getTemplate('store')->productOptions( $input, $fields, $package );
			}
		), NULL, NULL, NULL, 'p_dynamic_stock' ) );
		
		
		$form->addTab('package_store');
		$form->addHeader('package_store_permissions');
		$form->add( new YesNo( 'p_store', $this->store, FALSE, array( 'togglesOn' => array( 'p_member_groups', 'p_featured', 'p_desc_editor', 'p_images', 'p_reviewable', "{$form->id}_header_package_store_display", "{$form->id}_header_package_reviews" ) ) ) );
		$form->add( new CheckboxSet( 'p_member_groups', $this->member_groups === '*' ? '*' : explode( ',', $this->member_groups ), FALSE, array( 'options' => $groups, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'all', 'impliedUnlimited' => TRUE ), NULL, NULL, NULL, 'p_member_groups' ) );
		$form->add( new Node( 'p_methods', ( !$this->methods or $this->methods === '*' ) ? 0 : explode( ',', $this->methods ), TRUE, array( 'class' => 'IPS\nexus\Gateway', 'multiple' => TRUE, 'zeroVal' => 'any' ) ) );
		foreach ( $typeFields['store_permissions'] as $field )
		{
			$form->add( $field );
		}
		$form->addHeader('package_store_display');
		$form->add( new YesNo( 'p_featured', $this->featured, FALSE, array(), NULL, NULL, NULL, 'p_featured' ) );
		$form->add( new Translatable( 'p_desc', NULL, FALSE, array(
			'app' => 'nexus',
			'key' => $this->id ? "nexus_package_{$this->id}_desc" : NULL,
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-pkg-{$this->id}" : "nexus-new-pkg" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'pkg' ) : NULL, 'minimize' => 'p_desc_placeholder'
			)
		), NULL, NULL, NULL, 'p_desc_editor' ) );
		$form->add( new Upload( 'p_images', iterator_to_array( new Iterator( Db::i()->select( 'image_location', 'nexus_package_images', array( 'image_product=?', $this->id ), 'image_primary desc' ), 'nexus_Products' ) ), FALSE, array( 'storageExtension' => 'nexus_Products', 'multiple' => TRUE, 'image' => TRUE, 'template' => "nexus.store.images" ), NULL, NULL, NULL, 'p_images' ) );
		
		if ( $availableFilters )
		{
			$form->addHeader( 'pg_filters' );
		}
		
		$filters = array();
		foreach ( Db::i()->select( '*', 'nexus_package_filters_values', Db::i()->in( 'pfv_filter', $availableFilters ), 'pfv_order' ) as $row )
		{
			if ( $row['pfv_lang'] == Member::loggedIn()->language()->id )
			{
				$filters[ $row['pfv_filter'] ][ $row['pfv_value'] ] = $row['pfv_text'];
			}
		}
		$filterValues = array();
		if ( $this->id )
		{
			foreach ( Db::i()->select( '*', 'nexus_package_filters_map', array( 'pfm_package=?', $this->id ) ) as $row )
			{
				$filterValues[ $row['pfm_filter'] ] = explode( ',', $row['pfm_values'] );
			}
		}
		foreach ( $filters as $filterId => $options )
		{
			$form->add( new CheckboxSet( "nexus_product_filter_{$filterId}", $filterValues[$filterId] ?? array(), FALSE, array( 'options' => $options, 'disableCopy'	=> TRUE ), NULL, NULL, NULL, "nexus_filter_{$filterId}" ) );
		}
		
		$form->addHeader('package_reviews');
		$form->add( new YesNo( 'p_reviewable', $this->reviewable, FALSE, array( 'togglesOn' => array( 'p_review_moderate' ) ), NULL, NULL, NULL, 'p_reviewable' ) );
		$form->add( new YesNo( 'p_review_moderate', $this->review_moderate ?: FALSE, FALSE, array(), NULL, NULL, NULL, 'p_review_moderate' ) );
		$form->addHeader( 'package_notify' );
		$form->add( new Stack( 'p_notify', $this->notify ? explode( ',', $this->notify ) : array(), FALSE, array( 'stackFieldType' => 'Email' ) ) );
		
		$form->addTab( 'package_benefits' );
		$form->add( new Select( 'p_primary_group', $this->primary_group ?: '*', FALSE, array( 'options' => $groupsExcludingGuestsAndAdmins, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_primary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new YesNo( 'p_return_primary', $this->return_primary, FALSE, array(), NULL, NULL, NULL, 'p_return_primary' ) );
		$form->add( new Select( 'p_secondary_group', $this->secondary_group ? explode( ',', $this->secondary_group ) : '*', FALSE, array( 'options' => $groupsExcludingGuestsAndAdmins, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_secondary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new YesNo( 'p_return_secondary', $this->return_secondary, FALSE, array(), NULL, NULL, NULL, 'p_return_secondary' ) );
		foreach ( $typeFields['package_benefits'] as $field )
		{
			$form->add( $field );
		}
		
		$form->addTab('package_client_area');
		$form->addHeader('package_client_area_display');
		$form->add( new Translatable( 'p_page', NULL, FALSE, array(
			'app' => 'nexus',
			'key' => $this->id ? "nexus_package_{$this->id}_page" : NULL,
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-pkg-{$this->id}-pg" : "nexus-new-pkg-pg" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'pkg-pg' ) : NULL, 'minimize' => 'p_page_placeholder'
			)
		), NULL, NULL, NULL, 'p_page' ) );
		$form->addHeader('package_client_area_renewals');
		$form->add( new YesNo( 'p_renew', $this->renewal_days != 0, FALSE, array( 'togglesOn' => array( 'p_renewal_days', 'p_renewal_days_advance' ) ), NULL, NULL, NULL, 'p_renew' ) );
		$form->add( new Form\Number( 'p_renewal_days', $this->id ? $this->renewal_days : -1, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'any_time' ), NULL, NULL, Member::loggedIn()->language()->addToStack('days_before_expiry'), 'p_renewal_days' ) );
		$form->add( new Interval( 'p_renewal_days_advance', $this->id ? $this->renewal_days_advance : -1, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => -1 ), NULL, NULL, NULL, 'p_renewal_days_advance' ) );
		$form->addHeader('package_upgrade_downgrade');
		$form->addMessage('package_upgrade_downgrade_desc');
		$form->add( new YesNo( 'p_allow_upgrading', $this->allow_upgrading, FALSE, array( 'togglesOn' => array( 'p_upgrade_charge' ) ) ) );
		$form->add( new Radio( 'p_upgrade_charge', $this->upgrade_charge, FALSE, array( 'options' => array(
			0	=> 'p_upgrade_charge_none',
			1	=> 'p_upgrade_charge_full',
			2	=> 'p_upgrade_charge_prorate'
		) ), NULL, NULL, NULL, 'p_upgrade_charge' ) );
		$form->add( new YesNo( 'p_allow_downgrading', $this->allow_downgrading, FALSE, array( 'togglesOn' => array( 'p_downgrade_refund' ) ) ) );
		$form->add( new Radio( 'p_downgrade_refund', $this->downgrade_refund, FALSE, array( 'options' => array(
			0	=> 'p_downgrade_refund_none',
			1	=> 'p_downgrade_refund_full',
			2	=> 'p_downgrade_refund_prorate'
		)), NULL, NULL, NULL, 'p_downgrade_refund' ) );
		
		$form->addTab('package_custom_emails');
		$form->addMessage('package_custom_emails_blurb');
		foreach ( Settings::i()->cm_invoice_warning ? array( 'purchase', 'expire_soon', 'expire' ) : array( 'purchase', 'expire' ) as $k )
		{
			$typeKey = "email_{$k}_type";
			$valueKey = "email_{$k}";
			
			$form->add( new Radio( "p_email_{$k}_type", $this->$typeKey, FALSE, array(
				'options' => array(
					''					=> 'p_email_none',
					'wysiwyg'			=> 'p_email_wysiwyg',
					'html_template'		=> 'p_email_html_template',
					'html_raw'			=> 'p_email_html_raw',
				),
				'toggles'	=> array(
					'wysiwyg'			=> array( "p_email_{$k}_subject", "p_email_{$k}_wysiwyg" ),
					'html_template'		=> array( "p_email_{$k}_subject", "p_email_{$k}_html" ),
					'html_raw'			=> array( "p_email_{$k}_subject", "p_email_{$k}_html" )
				)
			), NULL, NULL, NULL, "p_email_{$k}_type" ) );
			$form->add( new Translatable( "p_email_{$k}_subject", NULL, FALSE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_package_{$this->id}_email_{$k}_subject" : NULL ), NULL, NULL, NULL, "p_email_{$k}_subject" ) );
			$form->add( new Codemirror( "p_email_{$k}_html", $this->$valueKey, FALSE, array(
				'preview' => Url::internal('app=nexus&module=store&controller=packages&do=emailPreview')->csrf(),
				'tags'	=> array(
					'{$purchase->name}'					=> Member::loggedIn()->language()->addToStack('ps_name'),
					'{$purchase->expire->localeDate()}'	=> Member::loggedIn()->language()->addToStack('p_email_tag_expire'),
					'{$purchase->renewals}'				=> Member::loggedIn()->language()->addToStack('ps_renewals'),
					'{$purchase->custom_fields[1]}'		=> Member::loggedIn()->language()->addToStack('p_email_tag_field'),
					'{$purchase->url()}'				=> Member::loggedIn()->language()->addToStack('p_email_tag_url'),
					'{$purchase->licenseKey()->key}'	=> Member::loggedIn()->language()->addToStack('license_key'),
				)
			), NULL, NULL, NULL, "p_email_{$k}_html" ) );
			$form->add( new Editor( "p_email_{$k}_wysiwyg", $this->$valueKey, FALSE, array(
				'app' => 'nexus',
				'key' => 'Admin',
				'autoSaveKey' => $this->id ? "nexus-pkg-{$this->id}-{$k}-email" : "nexus-new-pkg-{$k}-email",
				'allowAttachments' => false
			), NULL, NULL, NULL, "p_email_{$k}_wysiwyg" ) );
		}

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
			$this->type = ( isset( $values['p_type'] ) ) ? $values['p_type'] : '';
			$this->save();
			unset( static::$multitons[ $this->id ] );
			
			SystemFile::claimAttachments( 'nexus-new-pkg', $this->id, NULL, 'pkg', TRUE );
			SystemFile::claimAttachments( 'nexus-new-pkg-assoc', $this->id, NULL, 'pkg-assoc', TRUE );
			SystemFile::claimAttachments( 'nexus-new-pkg-pg', $this->id, NULL, 'pkg-pg', TRUE );
			
			$types	= static::packageTypes();
			/* @var Package $class */
			$class	= $types[ IPS::mb_ucfirst( $values['p_type'] ) ];
			
			if ( isset( $class::$packageDatabaseTable ) )
			{
				Db::i()->insert( $class::$packageDatabaseTable, array( 'p_id' => $this->id ) );
			}
			
			$obj = $class::load( $this->id );
			return $obj->saveForm( $obj->formatFormValues( $values ) );			
		}
				
		Db::i()->delete( 'nexus_package_filters_map', array( 'pfm_package=?', $this->id ) );
		if ( $this->group and $filters = Package\Group::load( $this->group )->filters )
		{
			foreach (explode( ',', $filters ) as $filterId )
			{
				if ( isset( $values["nexus_product_filter_{$filterId}"] ) and $values["nexus_product_filter_{$filterId}"] )
				{
					Db::i()->insert( 'nexus_package_filters_map', array(
						'pfm_package'	=> $this->id,
						'pfm_filter'	=> $filterId,
						'pfm_values'	=> implode( ',', $values["nexus_product_filter_{$filterId}"] ),
					) );
				}
			}
		}

		
		$return = parent::saveForm( $values );
		
		if ( !$this->custom )
		{
			Index::i()->index( $this->item() );
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
		if ( !$this->id )
		{
			return $values;
		}
		
		/* Email content */
		foreach ( array( 'purchase', 'expire_soon', 'expire' ) as $k )
		{
			if ( isset( $values["p_email_{$k}_type"] ) )
			{ 
				if ( $values["p_email_{$k}_type"] == 'wysiwyg' )
				{
					$values["p_email_{$k}"] = $values["p_email_{$k}_wysiwyg"];
				}
				elseif ( in_array( $values["p_email_{$k}_type"], array( 'html_template', 'html_raw' ) ) )
				{
					$values["p_email_{$k}"] = $values["p_email_{$k}_html"];
				}
				else
				{
					$values["p_email_{$k}"] = NULL;
				}
			}
			unset( $values["p_email_{$k}_wysiwyg"] );
			unset( $values["p_email_{$k}_html"] );
		}
				
		/* Translatables */
		foreach ( array( 'name' => '', 'desc' => '_desc', 'assoc_error' => '_assoc', 'page' => '_page', 'email_purchase_subject' => '_email_purchase_subject', 'email_expire_soon_subject' => '_email_expire_soon_subject', 'email_expire_subject' => '_email_expire_subject' ) as $key => $suffix )
		{
			if ( isset( $values[ 'p_' . $key ] ) )
			{
				Lang::saveCustom( 'nexus', "nexus_package_{$this->id}{$suffix}", $values[ 'p_' . $key ] );
			}
			unset( $values[ 'p_' . $key ] );
		}
		
		/* Custom Fields */
		if( isset( $values['p_custom_fields'] ) )
		{
			$old = array_keys( CustomField::roots( NULL, NULL, array( array( Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ) ) ) );
			$new = is_array( $values['p_custom_fields'] ) ? array_keys( $values['p_custom_fields'] ) : array();
			$added = array_diff( $new, $old );
			$removed = array_diff( $old, $new );
			if ( count( $added ) )
			{
				foreach ( $added as $id )
				{
					$field = CustomField::load( $id );
					$packages = explode( ',', $field->packages );
					$packages[] = $this->id;
					$field->packages = implode( ',', $packages );
					$field->save();
				}
			}
			if ( count( $removed ) )
			{
				foreach ( $removed as $id )
				{
					$field = CustomField::load( $id );
					$packages = explode( ',', $field->packages );
					unset( $packages[ array_search( $this->id, $packages ) ] );
					$field->packages = implode( ',', $packages );
					$field->save();
				}
			}
			unset( $values['p_custom_fields'] );
		}
		
		/* Normalise */
		if( isset( $values['p_group'] ) )
		{
			$values['p_group'] = $values['p_group']->id;
		}

		if( isset( $values['p_base_price'] ) )
		{
			$values['p_base_price'] = json_encode( $values['p_base_price'] );
		}

		if( isset( $values['p_member_groups'] ) )
		{
			$values['p_member_groups'] = $values['p_member_groups'] === '*' ? '*' : implode( ',', $values['p_member_groups'] );
		}

		if( isset( $values['p_primary_group'] ) )
		{
			$values['p_primary_group'] = $values['p_primary_group'] == '*' ? 0 : $values['p_primary_group'];
		}

		if( isset( $values['p_secondary_group'] ) )
		{
			$values['p_secondary_group'] = $values['p_secondary_group'] == '*' ? '' : implode( ',', $values['p_secondary_group'] );
		}

		if( isset( $values['p_notify'] ) and is_array( $values['p_notify'] ) )
		{
			$values['p_notify'] = implode( ',', $values['p_notify'] );
		}

		if( isset( $values['p_methods'] ) )
		{
			$values['p_methods'] = is_array( $values['p_methods'] ) ? implode( ',', array_keys( $values['p_methods'] ) ) : '*';
		}

		if( isset( $values['p_tax'] ) )
		{
			$values['p_tax'] = $values['p_tax'] ? $values['p_tax']->id : 0;
		}

		if( isset( $values['p_renew'] ) AND isset( $values['p_renewal_days'] ) )
		{
			$values['p_renewal_days'] = $values['p_renew'] ? $values['p_renewal_days'] : 0;
		}
		
		/* Renewal options */
		if( isset( $values['p_renews'] ) )
		{
			if ( $values['p_renews'] )
			{
				$renewOptions = array();
				foreach ( $values['p_renew_options'] as $option )
				{
					$term = $option->getTerm();
					$renewOptions[] = array(
						'cost'	=> $option->cost,
						'term'	=> $term['term'],
						'unit'	=> $term['unit'],
						'add'	=> $option->addToBase
					);
				}
				$values['p_renew_options'] = json_encode( $renewOptions );
				
				if ( isset( $values['p_initial_term'] ) and $values['p_initial_term']->interval )
				{
					$term = $values['p_initial_term']->getTerm();
					$values['p_initial_term'] = $term['term'] . mb_strtoupper( $term['unit'] );
				}
				else
				{
					$values['p_initial_term'] = NULL;
				}
			}
			else
			{
				$values['p_renew_options'] = '';
				$values['p_initial_term'] = NULL;
			}
		}
		else
		{
			$values['p_initial_term'] = NULL;
		}
		
		/* Associate */
		if ( isset( $values['p_associate'] ) )
		{
			switch ( $values['p_associate'] )
			{
				case 0:
					$values['p_associable'] = '';
					$values['p_force_assoc'] = 0;
					break;
				
				case 1:
					$values['p_associable'] = implode( ',', array_map( function( $node ) { return $node->id; }, $values['p_associable'] ) );
					$values['p_force_assoc'] = 0;
					break;
				
				case 2:
					$values['p_associable'] = implode( ',', array_map( function( $node ) { return $node->id; }, $values['p_associable'] ) );
					$values['p_force_assoc'] = 1;
					break;
			}
		}

		/* Product Options */
		Db::i()->delete( 'nexus_product_options', array( 'opt_package=?', $this->id ) );
		if ( isset( $values['p_stock_price_type'], $values['custom_fields'] ) )
		{
			if ( $values['p_stock_price_type'] === 'dynamic' )
			{
				$values['p_stock'] = -2;
				foreach ( $values['custom_fields'] as $k => $data )
				{				
					Db::i()->insert( 'nexus_product_options', array(
						'opt_package'		=> $this->id,
						'opt_values'		=> rawurldecode( $k ),
						'opt_stock'			=> isset( $data['unlimitedStock'] ) ? -1 : intval( $data['stock'] ),
						'opt_base_price'	=> json_encode( $data['bpa'] ),
						'opt_renew_price'	=> isset( $data['rpa'] ) ? json_encode( $data['rpa'] ) : ''
					) );
				}
			}
			unset( $values['p_stock_price_type'] );
			unset( $values['custom_fields'] );
		}
		/* Reset the discounts ? */
		if ( isset( $values['p_set_discounts'] ) AND !$values['p_set_discounts'] )
		{
			unset( $values['p_usergroup_discounts'] );
			unset( $values['p_loyalty_discounts'] );
			unset( $values['p_bulk_discounts'] );
			$values['discounts'] = NULL;
		}
		else
		{
			/* Discounts */
			if (isset($values['p_usergroup_discounts']) OR isset($values['p_loyalty_discounts']) OR isset($values['p_bulk_discounts']))
			{
				$discounts = array();
			}

			if (isset($values['p_usergroup_discounts']))
			{
				foreach ($values['p_usergroup_discounts'] as $data)
				{
					if (isset($data['group']) and $data['group'] and isset($data['price']))
					{
						foreach ($data['price'] as $price)
						{
							if (!$price)
							{
								continue 2;
							}
						}

						$discounts['usergroup'][] = array(
							'group' => $data['group'],
							'price' => $data['price'],
							'secondary' => (int)isset($data['secondary']),
						);
					}
				}
				unset($values['p_usergroup_discounts']);
			}

			if (isset($values['p_loyalty_discounts']))
			{
				foreach ($values['p_loyalty_discounts'] as $data)
				{
					if ($data['owns'])
					{
						foreach ($data['price'] as $price)
						{
							if (!$price)
							{
								continue 2;
							}
						}

						$packageId = $data['package'] ? mb_substr($data['package'], 0, mb_strpos($data['package'], '.')) : 0;

						$discounts['loyalty'][] = array(
							'owns' => $data['owns'],
							'package' => ($data['this'] or ($this->id and $this->id == $packageId)) ? '0' : $packageId,
							'price' => $data['price'],
							'active' => (int)isset($data['active']),
						);
					}
				}
				unset($values['p_loyalty_discounts']);
			}

			if (isset($values['p_bulk_discounts']))
			{
				foreach ($values['p_bulk_discounts'] as $data)
				{
					if ($data['buying'])
					{
						foreach ($data['price'] as $price)
						{
							if (!$price)
							{
								continue 2;
							}
						}

						$packageId = $data['package'] ? mb_substr($data['package'], 0, mb_strpos($data['package'], '.')) : 0;

						$discounts['bulk'][] = array(
							'buying' => $data['buying'],
							'package' => ($data['this'] or ($this->id and $this->id == $packageId)) ? '0' : $packageId,
							'price' => $data['price'],
						);
					}
				}
				unset($values['p_bulk_discounts']);
			}
		}
		
		if( isset( $discounts ) )
		{
			$values['discounts'] = json_encode( $discounts );
		}
		
		/* Images */
		if( isset( $values['p_images'] ) )
		{
			Db::i()->delete( 'nexus_package_images', array( 'image_product=?', $this->id ) );

			if( !empty( $values['p_images'] ) )
			{
				$primary = TRUE;
				foreach ( $values['p_images'] as $_key => $image )
				{
					if (  Request::i()->p_images_primary_image )
					{
						$primary = ( Request::i()->p_images_primary_image == $_key ) ? 1 : 0;
					}

					Db::i()->insert( 'nexus_package_images', array(
						'image_product'		=> $this->id,
						'image_location'	=> (string) $image,
						'image_primary'		=> $primary,
					) );

					if ( $primary )
					{
						$this->image = $image;
					}

					$primary = FALSE;
				}
			}
			else
			{
				$this->image = NULL;
			}
		}

		/* Column does not accept null */
		$values['p_featured'] = isset( $values['p_featured'] ) ? (int) $values['p_featured'] : 0 ;
		$values['p_reviewable'] = isset( $values['p_reviewable'] ) ? (int) $values['p_reviewable'] : 0;
		
		/* Save */
		return $values;
	}
	
	/**
	 * ACP Fields
	 *
	 * @param Package $package	The package
	 * @param bool $custom		If TRUE, is for a custom package
	 * @param bool $customEdit	If TRUE, is editing a custom package
	 * @return    array
	 */
	public static function acpFormFields(Package $package, bool $custom=FALSE, bool $customEdit=FALSE ): array
	{
		return array();
	}
	
	/**
	 * Updateable fields
	 *
	 * @return	array
	 */
	public static function updateableFields() : array
	{
		return array(
			'tax',
			'renew_options',
			'grace_period',
			'primary_group',
			'secondary_group',
			'group_renewals'
		);
	}
	
	/**
	 * Update existing purchases
	 *
	 * @param Purchase $purchase							The purchase
	 * @param array $changes							The old values
	 * @param bool $cancelBillingAgreementIfNecessary	If making changes to renewal terms, TRUE will cancel associated billing agreements. FALSE will skip that change
	 * @return    void
	 */
	public function updatePurchase(Purchase $purchase, array $changes, bool $cancelBillingAgreementIfNecessary=FALSE ): void
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
						
			$key = 'x';
			$childPurchaseRenewalCost = NULL;
			if ( $purchase->renewals )
			{
				/* If the purchase has any child purchases, subtract them from the renewal cost to find the actual cost of this product */
				$thisPurchaseRenewalCost = $purchase->renewals->cost->amount;
				$childPurchaseRenewalCost = new Number( "0" );
				foreach( $purchase->children() as $child )
				{
					if( $child->renewals )
					{
						$childPurchaseRenewalCost = $childPurchaseRenewalCost->add( $child->renewals->cost->amount );
						$thisPurchaseRenewalCost = $thisPurchaseRenewalCost->subtract( $child->renewals->cost->amount );
					}
				}

				foreach ( $changes['renew_options'] as $k => $data )
				{
					if ( $data['old'] != 'x' )
					{
						/* Compare against the actual renewal cost */
						if ( $thisPurchaseRenewalCost->compare( new Number( number_format( $data['old']['cost'][ $purchase->renewals->cost->currency ]['amount'], Money::numberOfDecimalsForCurrency( $purchase->renewals->cost->currency ), '.', '' ) ) ) === 0 )
						{
							$term = $purchase->renewals->getTerm();
							if ( $data['old']['term'] == $term['term'] and $data['old']['unit'] == $term['unit'] )
							{
								$key = $k;
								break;
							}
						}
					}
				}
			}
			
			switch ( $changes['renew_options'][ $key ]['new'] )
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
					if ( mb_substr( $changes['renew_options'][ $key ]['new'], 0, 1 ) === 'o' )
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
							
							$key = mb_substr( $changes['renew_options'][ $key ]['new'], 1 );
							if ( isset( $newRenewTerms[ $key ] ) )
							{
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

								$originalRenewalCost = $purchase->renewals->cost->amount;

								/* If we had child purchases, add back the renewal amounts */
								$newRenewalCost = new Number( $newRenewTerms[ $key ]['cost'][ $currency ]['amount'] );
								if( $childPurchaseRenewalCost !== NULL )
								{
									$newRenewalCost = $newRenewalCost->add( $childPurchaseRenewalCost );
								}

								$purchase->renewals = new RenewalTerm(
									new Money( $newRenewalCost, $currency ),
									new DateInterval( 'P' . $newRenewTerms[ $key ]['term'] . mb_strtoupper( $newRenewTerms[ $key ]['unit'] ) ),
									$tax
								);
								$purchase->save();

								/* If we have a parent purchase, update the renewals for the parent */
								if( $parent = $purchase->parent() )
								{
									if( $parentRenewals = $parent->renewals )
									{
										$parentRenewalCost = $parentRenewals->cost->amount->subtract( $originalRenewalCost );
										$parentRenewalCost = $parentRenewalCost->add( $newRenewalCost );
										$parent->renewals = new RenewalTerm(
											new Money( $parentRenewalCost, $currency ),
											$parentRenewals->interval,
											$parentRenewals->tax
										);
										$parent->save();
									}
								}
							}
						}
					}
					break;
			}
		}
		
		if ( array_key_exists( 'primary_group', $changes ) or array_key_exists( 'secondary_group', $changes ) )
		{
			$this->_removeUsergroups( $purchase, $changes );
			$this->_addUsergroups( $purchase );
		}
		
		if ( array_key_exists( 'group_renewals', $changes ) )
		{
			if ( $this->group_renewals )
			{
				try
				{
					$purchase->groupWithParent();
				}
				catch ( LogicException ) { }
			}
			else
			{
				try
				{
					$purchase->ungroupFromParent();
				}
				catch ( LogicException ) { }
			}
		}
		
		if ( array_key_exists( 'grace_period', $changes ) )
		{
			$purchase->grace_period = $this->grace_period * 86400;
			$purchase->save();
		}
	}
	
	/* !Store */
	
	/**
	 * Store Form
	 *
	 * @param	Form	$form			The form
	 * @param string $memberCurrency	The currency being used
	 * @return    void
	 */
	public function storeForm( Form $form, string $memberCurrency ): void
	{
		if( $this->subscription )
		{
			$form->hiddenValues['quantity'] = 1;
		}
		else
		{
			$form->add( new Form\Number('quantity', 1, TRUE, array( 'min' => 1 ) ) );
		}
	}
	
	/**
	 * Additional Secondary Page to show on checkout
	 *
	 * @param	array	$values	Values from store form
	 * @return	string|NULL
	 */
	public function storeAdditionalPage( array $values ) : string|null
	{
		return NULL;
	}
	
	/**
	 * Add To Cart
	 *
	 * @param Invoice\Item $item			The item
	 * @param	array										$values			Values from form
	 * @param string $memberCurrency	The currency being used
	 * @return    array    Additional items to add
	 */
	public function addToCart( Invoice\Item $item, array $values, string $memberCurrency ): array
	{
		return array();
	}
	
	/**
	 * Get item
	 *
	 * @return	Item
	 */
	public function item() : Item
	{
		$data = array();
		foreach ( $this->_data as $k => $v )
		{
			$data[ 'p_' . $k ] = $v; 
		}
		
		return Item::constructFromData( $data );
	}
	
	/**
	 * Get option values for stock/price adjustments
	 *
	 * @param	array	$details	Custom field values
	 * @return	array
	 */
	public function optionValues( array $details ) : array
	{
		$optionValues = array();
		if ( $this->stock == -2 )
		{
			foreach( $this->optionIdKeys() as $k )
			{
				$optionValues[ $k ] = ( isset( $details[ $k ] ) ) ? $details[ $k ] : NULL;
			}
		}
		return $optionValues;
	}

	/**
	 * @brief   Temporarily store stock data based on query.
	 */
	protected array $_stockQueryCache = [];
	
	/**
	 * Stock level
	 *
	 * @return	int|null	Integer for a specific stock number or NULL if unlimited or variable number in stock
	 */
	public function stockLevel() : int|null
	{
		switch ( $this->stock )
		{
			case -1: // Unlimited
				return NULL;
			case -2: // Variable
				if( !array_key_exists( $this->id, $this->_stockQueryCache ) )
				{
					if ( Db::i()->select( 'COUNT(*)', 'nexus_product_options', array( 'opt_package=? AND opt_stock<>0', $this->id ) )->first() )
					{
						$this->_stockQueryCache[ $this->id ] = NULL;
					}
					else
					{
						$this->_stockQueryCache[ $this->id ] = 0;
					}
				}

				return $this->_stockQueryCache[ $this->id ];
			default:
				return (int) $this->stock;
		}
	}
	
	/**
	 * Get stock and price for option values
	 *
	 * @param array $optionValues		Option values
	 * @param bool $includeDiscounts	Get discounted price?
	 * @return    array( 'price' => \IPS\nexus\Money, 'stock' => 5, 'renewalAdjustment' => 0 )	'stock' will be -1 for unlimited, 0 for none in stock
	 */
	public function optionValuesStockAndPrice(array $optionValues, bool $includeDiscounts=TRUE ): array
	{
		/* Dynamic */
		if ( $this->stock == -2 )
		{
			$chosenOption = Db::i()->select( '*', 'nexus_product_options', array( 'opt_package=? AND opt_values=?', $this->id, json_encode( $optionValues ) ) )->first();
			
			/* Price */
			$basePriceAdjustments = json_decode( $chosenOption['opt_base_price'], TRUE );
			$defaultPrice = $this->price( NULL, $includeDiscounts, $includeDiscounts, $includeDiscounts );
			if ( isset( $basePriceAdjustments[ $defaultPrice->currency ] ) )
			{
				$price = new Money( $defaultPrice->amount->add( new Number( number_format( $basePriceAdjustments[ $defaultPrice->currency ], Money::numberOfDecimalsForCurrency( $defaultPrice->currency ), '.', '' ) ) ), $defaultPrice->currency );
			}
			else
			{
				$price = $defaultPrice;
			}
			
			$renewalAdjustment = 0;
			if ( $chosenOption['opt_renew_price'] )
			{
				$renewPriceAdjustments = json_decode( $chosenOption['opt_renew_price'], TRUE );
				if ( isset( $renewPriceAdjustments[ $price->currency ] ) )
				{
					$renewalAdjustment = $renewPriceAdjustments[ $price->currency ];
				}
			}
			
			/* Return */
			return array( 'price' => $price, 'stock' => $chosenOption['opt_stock'], 'renewalAdjustment' => $renewalAdjustment );
		}
		/* Static */
		else
		{
			return array( 'price' => $this->price( NULL, $includeDiscounts, $includeDiscounts, $includeDiscounts ), 'stock' => $this->stock, 'renewalAdjustment' => 0 );
		}
	}
	
	/**
	 * Create Item Object
	 *
	 * @param Money $price		Price
	 * @return	ItemPackage
	 */
	public function createItemForCart( Money $price ) : ItemPackage
	{
		$item = new ItemPackage( Member::loggedIn()->language()->get( 'nexus_package_' . $this->id ), $price );
		$item->id = $this->id;
		$item->tax = $this->tax ? Tax::load( $this->tax ) : NULL;
		if ( $this->methods and $this->methods != '*' )
		{
			$item->paymentMethodIds = explode( ',', $this->methods );
		}
		if ( $this->group_renewals )
		{
			$item->groupWithParent = TRUE;
		}

		return $item;
	}
	
	/**
	 * Add items into cart
	 *
	 * @param array $details			Custom field values
	 * @param int $quantity			Quantity to add
	 * @param RenewalTerm|NULL $renewalTerm		Chosen renewal term, if applicable
	 * @param int|Purchase|null $parent				Parent purchase, if applicable
	 * @param array|NULL $values				If adding from the main purchase field, any additional values which may be used for extra items
	 * @return    int
	 * @note	Does not verify stock. Stock check first
	 */
	public function addItemsToCartData(array $details=array(), int $quantity=1, ?RenewalTerm $renewalTerm = NULL, Purchase|int|null $parent=NULL, ?array $values=NULL ): int
	{		
		$optionValues = $this->optionValues( $details );
		
		/* If we do not have a currency in our session data, set it now so that if the language is changed, it is not confused about
			what currency the store should be in */
		if ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) )
		{
			$memberCurrency = Request::i()->cookie['currency'];
		}
		else
		{
			$memberCurrency = Customer::loggedIn()->defaultCurrency();
			Request::i()->setCookie( 'currency', $memberCurrency );
		}
					
		/* Work out how we're splitting these */
		$items = array();
		$previousPrice = NULL;
		$inThisBatch = 0;
		for ( $i=0; $i<$quantity;$i++ )
		{
			$price = $this->price( NULL, TRUE, TRUE, TRUE, $i );	
			if ( $price->currency !== $memberCurrency )
			{
				throw new RuntimeException('CURRENCY_MISMATCH');
			}		
			$price = $price->amount;
							
			if ( $previousPrice !== NULL and $previousPrice !== $price )
			{
				$items[] = $inThisBatch;
				$inThisBatch = 0;
			}
						
			$inThisBatch++;
			$previousPrice = $price;
		}
						
		if ( $inThisBatch )
		{
			$items[] = $inThisBatch;
		}
		
		/* And do it */
		foreach ( $items as $quantity )
		{
			/* Get base price */
			$price = $this->price()->amount;
			
			/* Renewal term */
			if ( $renewalTerm and $renewalTerm->addToBase )
			{
				$price = $price->add( $renewalTerm->cost->amount );
			}
						
			/* Adjustments based on custom fields */
			if ( $this->stock == -2 )
			{					
				try
				{
					$chosenOption = Db::i()->select( '*', 'nexus_product_options', array( 'opt_package=? AND opt_values=?', $this->id, json_encode( $optionValues ) ) )->first();
					$basePriceAdjustments = json_decode( $chosenOption['opt_base_price'], TRUE );
					if ( isset( $basePriceAdjustments[ $memberCurrency ] ) )
					{
						$price = $price->add( new Number( number_format( $basePriceAdjustments[ $memberCurrency ], Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) );
					}
				}
				catch ( UnderflowException ) {}
			}
			
			/* Create the item */			
			$item = $this->createItemForCart( new Money( $price, $memberCurrency ) );
			$item->renewalTerm = $renewalTerm;
			if ( $renewalTerm and $this->initial_term )
			{
				$item->initialInterval = new DateInterval( "P{$this->initial_term}" );
			}
			$item->quantity = $quantity;
			$item->details = $details;

			/* Associations */
			if ( $parent !== NULL )
			{
				$item->parent = $parent;
			}
			
			/* Do any package-specific modifications */
			$extraItems = ( $values === NULL ) ? array() : $this->addToCart( $item, $values, $memberCurrency );
			
			/* Hang on, is that the same as anything else in the cart? */
			$added = FALSE;
			foreach ( $_SESSION['cart'] as $_id => $_item )
			{
				if ( $_item->isSameAsOtherItem( $item ) )
				{
					$_SESSION['cart'][$_id]->quantity += $quantity;
					$added = TRUE;
					$cartId = $_id;
					break;
				}
			}
									
			/* Nope, go ahead and add it */
			if ( !$added )
			{
				$_SESSION['cart'][] = $item;
				$keys = array_keys( $_SESSION['cart'] );
				$cartId = array_pop( $keys );
			}
			
			/* Associate extras */
			if ( count( $extraItems ) )
			{
				foreach ( $extraItems as $item )
				{
					$item->parent = $cartId;
					$_SESSION['cart'][] = $item;
				}
			}
		}

		/* Turn off guest page caching */
		if ( CACHE_PAGE_TIMEOUT and !Member::loggedIn()->member_id )
		{
			/* Set the cookie for 20 minutes - if the guest hasn't checked out by then they may no longer see their cart on every page but as long as their session is still valid they will still be able to check out. */
			Request::i()->setCookie( 'noCache', 1, DateTime::ts( time() + 1200 ) );
		}

		/* Return */
		return $cartId;
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
	 * @param Purchase $purchase	The purchase
	 * @return	array( 'packageInfo' => '...', 'purchaseInfo' => '...' )
	 */
	public function clientAreaPage( Purchase $purchase ) : array
	{
		/* Custom Fields */
		$editable = FALSE;
		$customFieldsForm = new Form;
		$customFieldsForm->class = 'ipsForm--vertical ipsForm--client-custom-fields';
		$customFields = CustomField::roots( NULL, NULL, array( array( Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ) ) );
		foreach ( $customFields as $field )
		{
			$value = isset( $purchase->custom_fields[ $field->id ] ) ? $purchase->custom_fields[ $field->id ] : NULL;
			if ( $field->editable )
			{
				if ( $field->type === 'Editor' )
				{
					$field::$editorOptions = array_merge( $field::$editorOptions, array( 'attachIds' => array( $purchase->id, $field->id, 'purchase' ) ) );
				}
				$helper = $field->buildHelper( $value );
				$editable = TRUE;
				$customFieldsForm->add( $helper );
			}
			else
			{
				$customFieldsForm->addDummy( $field->_title, $field->displayValue( $value ) );
			}
		}
		if ( !$editable )
		{
			$customFieldsForm->actionButtons = array();
		}

		if( $values = $customFieldsForm->values() )
		{
			$customFieldsSave = $purchase->custom_fields;
			
			foreach ( $customFields as $field )
			{
				if ( isset( $values[ 'nexus_pfield_' . $field->id ] ) )
				{
					$class = $field->buildHelper();
					if ( $class instanceof Upload )
					{
						$customFieldsSave[ $field->id ] = (string) $values[ 'nexus_pfield_' . $field->id ];
					}
					else
					{
						$customFieldsSave[ $field->id ] = $class::stringValue( $values[ 'nexus_pfield_' . $field->id ] );
					}

					if ( $field->type === 'Editor' )
					{
						$field->claimAttachments( $purchase->id, 'purchase' );
					}
				}
			}
						
			$purchase->custom_fields = $customFieldsSave;
			$purchase->save();
		}

		/* Stuff only the owner or "billing" alternate contact can do */
		$reactivateUrl = NULL;
		$upgradeDowngradeUrl = NULL;
		$upgradeDowngradeLang = 'change_package';
		if ( $purchase->member->member_id === Member::loggedIn()->member_id or array_key_exists( Member::loggedIn()->member_id, iterator_to_array( $purchase->member->alternativeContacts( array( Db::i()->findInSet( 'purchases', array( $purchase->id ) ) . ' AND billing=1' ) ) ) ) )
		{
			/* Reactivate */
			if ( !$purchase->renewals and $renewOptions = json_decode( $this->renew_options, TRUE ) and count( $renewOptions ) and $purchase->can_reactivate and ( !$purchase->billing_agreement or $purchase->billing_agreement->canceled ) )
			{
				$reactivateUrl = Url::internal( "app=nexus&module=clients&controller=purchases&id={$purchase->id}&do=extra&act=reactivate", 'front', 'clientspurchaseextra', Url::seoTitle( $purchase->name ) )->csrf();
			}
			
			/* Upgrade/Downgrade */
			$includesUpgrades = FALSE;
			$includesDowngrades = FALSE;
			if( count( $this->upgradeDowngradeOptions( $purchase, FALSE, $includesUpgrades, $includesDowngrades ) ) )
			{
				$upgradeDowngradeUrl = Url::internal( "app=nexus&module=clients&controller=purchases&id={$purchase->id}&do=extra&act=change", 'front', 'clientspurchaseextra', Url::seoTitle( $purchase->name ) );
				if ( $includesUpgrades and !$includesDowngrades )
				{
					$upgradeDowngradeLang = 'change_package_upgrade';
				}
				elseif ( !$includesUpgrades and $includesDowngrades )
				{
					$upgradeDowngradeLang = 'change_package_downgrade';
				}
			}
		}
		
		/* Associated Downloads Files */
		$associatedFiles = array();
		if ( Application::appIsEnabled( 'downloads' ) and Settings::i()->idm_nexus_on )
		{
			$associatedFiles = File::getItemsWithPermission( array( array( Db::i()->findInSet( 'file_nexus', array( $this->id ) ) ) ) );
		}

		/* Display */
		return array(
			'packageInfo'	=> Member::loggedIn()->language()->checkKeyExists("nexus_package_{$this->id}_page") ? Member::loggedIn()->language()->addToStack("nexus_package_{$this->id}_page") : '',
			'purchaseInfo'	=> Theme::i()->getTemplate( 'purchases' )->package( $this, $customFieldsForm, $reactivateUrl, $upgradeDowngradeUrl, $upgradeDowngradeLang, $associatedFiles ),
		);
	}
	
	/**
	 * Client Area Action
	 *
	 * @param Purchase $purchase	The purchase
	 * @return	string
	 */
	public function clientAreaAction( Purchase $purchase ) : string
	{
		switch ( Request::i()->act )
		{
			case 'change':
				$form = $this->upgradeDowngradeForm( $purchase );
				if ( $values = $form->values() )
				{
					$newPackage				= Package::load( $values['change_package_to'] );
					$chosenRenewalOption	= $values["renew_option_{$newPackage->id}"] ?? NULL;
					
					/* This package may only have one renewal option, in which case it is not displayed in the form - we should auto select it here, if renewal options are available */
					if ( $chosenRenewalOption === NULL )
					{
						$renewOptions = json_decode( $newPackage->renew_options, TRUE ) ?? array();
						if ( count( $renewOptions ) === 1 )
						{
							$chosenRenewalOption = key( $renewOptions );
						}
					}
					
					$invoice = $this->upgradeDowngrade( $purchase, $newPackage, $chosenRenewalOption );
					if ( $invoice )
					{
						Output::i()->redirect( $invoice->checkoutUrl() );
					}
					$purchase->member->log( 'purchase', array( 'type' => 'change', 'id' => $purchase->id, 'old' => $purchase->name, 'name' => $newPackage->titleForLog(), 'system' => FALSE ) );
					Output::i()->redirect( $purchase->url() );
				}
				return (string) $form;
			
			case 'reactivate':
				$currency = $purchase->original_invoice->currency;
				$renewalOptions = json_decode( $this->renew_options, TRUE );
				$noOptions = count( $renewalOptions ) === 1;

				$tax = NULL;
				if ( $purchase->tax )
				{
					try
					{
						$tax = Tax::load( $purchase->tax );
					}
					catch ( Exception ) { }
				}

				if ( $noOptions )
				{
					Session::i()->csrfCheck();
					$term = array_pop( $renewalOptions );

					$purchase->renewals = new RenewalTerm( new Money( $term['cost'][$currency]['amount'], $currency ), new DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ), $tax );
					$purchase->cancelled = FALSE;
					$purchase->save();
					
					foreach ( $purchase->children() as $child )
					{
						if ( $child->grouped_renewals )
						{
							$child->groupWithParent();
						}
					}
					
					$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'info' => 'change_renewals', 'to' => array( 'cost' => $purchase->renewals->cost->amount, 'currency' => $purchase->renewals->cost->currency, 'term' => $purchase->renewals->getTerm() ) ) );

					if ( !$purchase->active and $cycles = $purchase->canRenewUntil( NULL, TRUE ) )
					{
						$url = $cycles === 1 ? $purchase->url()->setQueryString( 'do', 'renew' )->csrf() : $purchase->url()->setQueryString( 'do', 'renew' );
						Output::i()->redirect( $url );
					}
					else
					{
						Output::i()->redirect( $purchase->url() );
					}
				}
			
				$form = new Form( 'reactivate', 'reactivate' );
				$form->class = 'ipsForm--vertical ipsForm--reactivate-package';
				$options = array();
				foreach ( $renewalOptions as $k => $term )
				{
					$options[ $k ] = new RenewalTerm( new Money( $term['cost'][$currency]['amount'], $currency ), new DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ), $tax );
				}
				$form->add( new Radio( 'ps_renewals', NULL, TRUE, array( 'options' => $options, 'parse' => 'normal' ) ) );
				if ( $values = $form->values() )
				{
					$term = $renewalOptions[ $values['ps_renewals'] ];
					$purchase->renewals = new RenewalTerm( new Money( $term['cost'][$currency]['amount'], $currency ), new DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ), $tax );
					$purchase->cancelled = FALSE;
					$purchase->save();
					
					foreach ( $purchase->children() as $child )
					{
						if ( $child->grouped_renewals )
						{
							$child->groupWithParent();
						}
					}
					
					$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'info' => 'change_renewals', 'to' => array( 'cost' => $purchase->renewals->cost->amount, 'currency' => $purchase->renewals->cost->currency, 'term' => $purchase->renewals->getTerm() ) ) );

					if ( !$purchase->active and $cycles = $purchase->canRenewUntil( NULL, TRUE ) )
					{
						$url = $cycles === 1 ? $purchase->url()->setQueryString( 'do', 'renew' )->csrf() : $purchase->url()->setQueryString( 'do', 'renew' );
						Output::i()->redirect( $url );
					}
					else
					{
						Output::i()->redirect( $purchase->url() );
					}
				}
				return (string) $form;
		}

		return '';
	}
	
	/* !ACP */
	
	/**
	 * ACP Add to invoice
	 *
	 * @param Invoice\Item $item			The item
	 * @param	array										$values			Values from form
	 * @param string $k				The key to add to the field names
	 * @param invoice $invoice		The invoice
	 * @return    void
	 */
	public function acpAddToInvoice( Invoice\Item $item, array $values, string $k, invoice $invoice ): void
	{
	
	}	
	
	/**
	 * Get ACP Page Buttons
	 *
	 * @param Purchase $purchase	The purchase
	 * @param Url $url		The page URL
	 * @return    array
	 */
	public function acpButtons(Purchase $purchase, Url $url ): array
	{
		$return = array();
		
		if ( count( $this->upgradeDowngradeOptions( $purchase, TRUE ) ) )
		{
			$return['change'] = array(
				'icon'	=> 'archive',
				'title'	=> 'change_package',
				'link'	=> $url->setQueryString( array( 'do' => 'extra', 'act' => 'change' ) ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('change_package') )
			);
		}
		
		return $return;
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @param Purchase $purchase
	 * @return    string
	 */
	public function acpPage(Purchase $purchase ): string
	{
		return '';
	}
	
	/**
	 * ACP Action
	 *
	 * @param Purchase $purchase	The purchase
	 * @return    string|null
	 */
	public function acpAction(Purchase $purchase ): string|null
	{
		switch ( Request::i()->act )
		{
			case 'change':
				$form = $this->upgradeDowngradeForm( $purchase, TRUE );
				$form->class = 'ipsForm--horizontal ipsForm--acp-purchase-action';
				$form->add( new YesNo( 'change_package_skip_charges', FALSE, TRUE ) );
				if ( $values = $form->values() )
				{
					$newPackage = Package::load( $values['change_package_to'] );
					$invoice = $this->upgradeDowngrade( $purchase, $newPackage, $values["renew_option_{$newPackage->id}"] ?? NULL, $values['change_package_skip_charges'] );
					if ( $invoice )
					{
						Output::i()->redirect( $invoice->acpUrl() );
					}
					$purchase->member->log( 'purchase', array( 'type' => 'change', 'id' => $purchase->id, 'old' => $purchase->name, 'name' => $newPackage->titleForLog(), 'system' => FALSE ) );
					Output::i()->redirect( $purchase->acpUrl() );
				}
				return (string) $form;
		}

		return null;
	}
	
	/** 
	 * ACP Edit Form
	 *
	 * @param Purchase $purchase	The purchase
	 * @param	Form				$form	The form
	 * @param	RenewalTerm|null	$renewals	The renewal term
	 * @return	void
	 */
	public static function acpEdit( Purchase $purchase, Form $form, ?RenewalTerm $renewals ) : void
	{
		$form->addHeader('menu__nexus_store_fields');
		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_package_fields', Db::i()->findInSet( 'cf_packages', array( $purchase->item_id ) ) ), 'IPS\nexus\Package\CustomField' ) as $field )
		{
			/* @var CustomField $field */
			if ( $field->type === 'Editor' )
			{
				$field::$editorOptions = array_merge( $field::$editorOptions, array( 'attachIds' => array( $purchase->id, $field->id, 'purchase' ) ) );
			}
			$form->add( $field->buildHelper( isset( $purchase->custom_fields[ $field->id ] ) ? $purchase->custom_fields[ $field->id ] : NULL ) );
		}
	}
	
	/** 
	 * ACP Edit Save
	 *
	 * @param Purchase $purchase	The purchase
	 * @param	array				$values		Values from form
	 * @return    void
	 */
	public function acpEditSave(Purchase $purchase, array $values ): void
	{
		$customFields = $purchase->custom_fields;
				
		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_package_fields', Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ), 'IPS\nexus\Package\CustomField' ) as $field )
		{
			/* @var CustomField $field */
			$class = $field->buildHelper();
			if ( $class instanceof Upload )
			{
				$customFields[ $field->id ] = (string) $values[ 'nexus_pfield_' . $field->id ];
			}
			else
			{
				if ( $class instanceof Editor )
				{
					$field->claimAttachments( $purchase->id, 'purchase' );
				}
				$customFields[ $field->id ] = $class::stringValue( $values[ 'nexus_pfield_' . $field->id ] );
			}
		}
		
		$purchase->custom_fields = $customFields;
		$purchase->save();
	}

	/* !Actions */
	
	/**
	 * On Paid
	 *
	 * @param invoice $invoice	The invoice
	 * @return    void
	 */
	public function onPaid(invoice $invoice ): void
	{
		// Blank for hooking
	}
	
	/**
	 * On Unpaid description
	 *
	 * @param Invoice $invoice	The invoice
	 * @return    array
	 */
	public function onUnpaidDescription( Invoice $invoice ): array
	{
		// Blank for hooking
		return array();
	}
	
	/**
	 * On Unpaid
	 *
	 * @param invoice $invoice	The invoice
	 * @param string $status		Status
	 * @return    void
	 */
	public function onUnpaid(Invoice $invoice, string $status ): void
	{
		// Blank for hooking
	}
	
	/**
	 * On Invoice Cancel (when unpaid)
	 *
	 * @param invoice $invoice	The invoice
	 * @return    void
	 */
	public function onInvoiceCancel(Invoice $invoice ): void
	{
		// Blank for hooking
	}

	/**
	 * Warning to display to admin when cancelling a purchase
	 *
	 * @param Purchase $purchase	The purchase
	 * @return    string|null
	 */
	public function onCancelWarning(Purchase $purchase ): string|null
	{
		return NULL;
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
		// Handled by subclasses
	}
	
	/**
	 * Can Renew Until
	 *
	 * @param Purchase $purchase	The purchase
	 * @param bool $admin		If TRUE, is for ACP. If FALSE, is for front-end.
	 * @return    bool|DateTime    TRUE means can renew as much as they like. FALSE means cannot renew at all. \IPS\DateTime means can renew until that date
	 */
	public function canRenewUntil(Purchase $purchase, bool $admin ): DateTime|bool
	{
		if ( !$purchase->expire )
		{
			return FALSE;
		}
		
		if ( $admin )
		{
			return TRUE;
		}
		
		if ( !$this->renewal_days )
		{
			return FALSE;
		}
		elseif ( $this->renewal_days != -1 )
		{
			$diff = $purchase->expire->diff( DateTime::create() );
			if ( $diff->invert and $diff->days > $this->renewal_days )
			{
				return FALSE;
			}
		}
		
		if ( $this->renewal_days_advance == -1 )
		{
			return TRUE;
		}
		else
		{
			$baseDate = ( $purchase->expire->getTimestamp() > time() ) ? $purchase->expire : DateTime::create();
			return $baseDate->add( new DateInterval( 'P' . $this->renewal_days_advance . 'D' ) );
		}
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param Purchase $purchase	The purchase
	 * @param invoice $invoice	The invoice
	 * @return    void
	 */
	public function onPurchaseGenerated(Purchase $purchase, Invoice $invoice ): void
	{		
		$this->_changeStockLevel( -1, $this->_getOptionId( $purchase ) );
		$this->_addUsergroups( $purchase );
		
		if ( $this->notify )
		{
			$email = Email::buildFromTemplate( 'nexus', 'purchaseNotify', array( $purchase, $invoice ), Email::TYPE_LIST );
			foreach ( explode( ',', $this->notify ) as $to )
			{
				$email->send( $to );
			}
		}
		
		$this->sendCustomEmail( 'purchase', $purchase );
	}
	
	/**
	 * On Renew (Renewal invoice paid. Is not called if expiry data is manually changed)
	 *
	 * @param Purchase $purchase	The purchase
	 * @param int $cycles		Cycles
	 * @return    void
	 */
	public function onRenew(Purchase $purchase, int $cycles = 1): void
	{
		// Blank for hooking
	}
	
	/**
	 * On Expiration Date Change
	 *
	 * @param Purchase $purchase	The purchase
	 * @return    void
	 */
	public function onExpirationDateChange(Purchase $purchase ): void
	{
	
	}
	
	/**
	 * On expire soon
	 * If returns TRUE, the normal expire warning email will not be sent
	 *
	 * @param Purchase $purchase	The purchase
	 * @return    bool
	 */
	public function onExpireWarning(Purchase $purchase ): bool
	{
		return $this->sendCustomEmail( 'expire_soon', $purchase );
	}
	
	/**
	 * On Purchase Expired
	 *
	 * @param Purchase $purchase	The purchase
	 * @return    void
	 */
	public function onExpire(Purchase $purchase ): void
	{
		$this->_removeUsergroups( $purchase );
		
		$this->sendCustomEmail( 'expire', $purchase );
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param Purchase $purchase	The purchase
	 * @return    void
	 */
	public function onCancel(Purchase $purchase ): void
	{
		$this->_changeStockLevel( 1, $this->_getOptionId( $purchase ) );
		$this->_removeUsergroups( $purchase );
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param Purchase $purchase	The purchase
	 * @return    void
	 */
	public function onDelete(Purchase $purchase ): void
	{
		$this->onCancel( $purchase );
	}
	
	/**
	 * On Purchase Reactivated (renewed after being expired or reactivated after being canceled)
	 *
	 * @param Purchase $purchase	The purchase
	 * @return    void
	 */
	public function onReactivate(Purchase $purchase ): void
	{
		$this->_changeStockLevel( -1, $this->_getOptionId( $purchase ) );
		$this->_addUsergroups( $purchase );
	}
	
	/**
	 * On Transfer (is ran before transferring)
	 *
	 * @param Purchase $purchase		The purchase
	 * @param	Member			$newCustomer	New Customer
	 * @return    void
	 */
	public function onTransfer(Purchase $purchase, Member $newCustomer ): void
	{
		$this->_removeUsergroups( $purchase );
		$purchase->member = $newCustomer;
		$this->_addUsergroups( $purchase );
	}
	
	/**
	 * On Upgrade/Downgrade
	 *
	 * @param Purchase $purchase				The purchase
	 * @param Package $newPackage				The package to upgrade to
	 * @param int|RenewalTerm|NULL $chosenRenewalOption	The chosen renewal option
	 * @return    void
	 */
	public function onChange(Purchase $purchase, Package $newPackage, RenewalTerm|int|null $chosenRenewalOption = NULL ): void
	{
	
	}
	
	/**
	 * Send a custom email
	 *
	 * @param string $type		Which custom email
	 * @param Purchase $purchase	The purchase
	 * @return    bool
	 */
	public function sendCustomEmail(string $type, Purchase $purchase ): bool
	{
		$typeKey = "email_{$type}_type";
		$valueKey = "email_{$type}";
		
		if ( $this->$typeKey )
		{
			if ( $this->$typeKey == 'wysiwyg' )
			{
				$email = Email::buildFromContent( $purchase->member->language()->get( "nexus_package_{$this->id}_email_{$type}_subject" ), $this->$valueKey, NULL, Email::TYPE_TRANSACTIONAL, Email::WRAPPER_USE, 'package_customemail' );
			}
			else
			{
				$functionName = 'nexus_customPurchaseEmail_' . $this->id;
				Theme::makeProcessFunction( $this->$valueKey, $functionName, '$purchase' );
				
				$themeFunction = 'IPS\\Theme\\'.$functionName;
				$email = Email::buildFromContent( $purchase->member->language()->get( "nexus_package_{$this->id}_email_{$type}_subject" ), $themeFunction( $purchase ), NULL, Email::TYPE_TRANSACTIONAL, ( $this->$typeKey == 'html_template' ? Email::WRAPPER_USE : Email::WRAPPER_NONE ), 'package_customemail' );
			}
			$email->send( $purchase->member );
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/* !Upgrading/Downgrading */
	
	/**
	 * Upgrade/Downgrade Options
	 *
	 * @param Purchase $purchase				The purchase
	 * @param bool $overrideRestrictions	If true, will be able to choose any packages in the same group
	 * @param bool $includesUpgrades		Will be set if there are upgrade options, useful for knowing what text to show
	 * @param bool $includesDowngrades		Will be set if there are downgrade options, useful for knowing what text to show
	 * @return    array
	 */
	protected function upgradeDowngradeOptions(Purchase $purchase, bool $overrideRestrictions = FALSE, bool &$includesUpgrades=FALSE, bool &$includesDowngrades=FALSE ): array
	{
		/* Cannot upgrade/downgrade custom or cancelled packages */
		if ( $this->custom or $purchase->cancelled )
		{
			return array();
		}

		/* Cannot upgrade/downgrade without permission unless $overrideRestrictions is set */
		if ( ( !$this->allow_upgrading AND !$this->allow_downgrading ) AND !$overrideRestrictions )
		{
			return array();
		}
		
		/* Cannot upgrade/downgrade with billing agreement */
		if ( ( $purchase->billing_agreement and !$purchase->billing_agreement->canceled ) or ( $parent = $purchase->parent() and $parent->billing_agreement and !$parent->billing_agreement->canceled ) )
		{
			return array();
		}
		
		/* Work out how much this package costs, including considering if the renewal term adds to the base price */
		try
		{
			$currency = $purchase->original_invoice->currency;
		}
		catch ( Exception )
		{
			$currency = $purchase->member->defaultCurrency();
		}

		$prices = NULL;
		$thisPrice = NULL;
		
		if( $this->base_price )
		{
			$prices = $this->base_price ? json_decode( $this->base_price, TRUE ) : NULL;
			$thisPrice = $prices[ $currency ]['amount'];
		}

		if ( $this->renew_options )
		{
			$renewalOptions = json_decode( $this->renew_options, TRUE );
			if ( !empty( $renewalOptions ) )
			{
				$option = array_shift( $renewalOptions );						
				if ( $option['add'] )
				{
					$thisPrice += ( $option['cost'][ $currency ]['amount'] );
				}
			}
		}
		
		/* Loop through our siblings */
		$return = array();
		foreach ( $this->parent()->children() as $package )
		{
			if ( $package->id === $this->id )
			{
				continue;
			}
			
			$prices = $package->base_price ? json_decode( $package->base_price, TRUE ) : NULL;
			if ( !isset( $prices[ $currency ] ) )
			{
				continue;
			}
			$price = $prices[ $currency ]['amount'];
			
			/* Does Renewal add to base price */
			if ( $package->renew_options )
			{
				$renewalOptions = json_decode( $package->renew_options, TRUE );
				if ( !empty( $renewalOptions ) )
				{
					$option = array_shift( $renewalOptions );						
					if ( $option['add'] )
					{
						$price += ( $option['cost'][ $currency ]['amount'] );
					}
				}
			}
			
			/* Upgrade */
			if ( $price >= $thisPrice and ( $this->allow_upgrading or $overrideRestrictions ) )
			{
				/* We want to pro-rata renewals for upgrades, but we do not have any renewals here, so no */
				if ( $package->upgrade_charge == 2 and ! $purchase->renewals )
				{
					continue;
				}

				$return[] = $package;
				$includesUpgrades = TRUE;
			}
			
			/* Downgrade */
			elseif ( $price < $thisPrice and ( $this->allow_downgrading or $overrideRestrictions ) )
			{
				$return[] = $package;
				$includesDowngrades = TRUE;
			}
		}
		
		/* Return */
		return $return;
	}
	
	/**
	 * Upgrade/Downgrade Form
	 *
	 * @param Purchase $purchase				The purchase
	 * @param bool $overrideRestrictions	If true, will be able to choose any packages in the same group
	 * @return    Form
	 */
	protected function upgradeDowngradeForm(Purchase $purchase, bool $overrideRestrictions = FALSE ): Form
	{
		try
		{
			$currency = $purchase->original_invoice->currency;
		}
		catch ( Exception )
		{
			$currency = $purchase->member->defaultCurrency();
		}
		$options = array();
		$desciptions = array();
		$toggles = array();
		$renewFields = array();

		$tax = NULL;
		if ( $purchase->tax )
		{
			try
			{
				$tax = Tax::load( $purchase->tax );
			}
			catch ( Exception ) { }
		}

		foreach ( $this->upgradeDowngradeOptions( $purchase, $overrideRestrictions ) as $package )
		{
			try
			{
				$costToUpgrade = $package->costToUpgrade( $purchase );
			}
			catch ( InvalidArgumentException )
			{
				continue;
			}

			/* Do we need to show the tax? */
			if( $tax AND Settings::i()->nexus_show_tax )
			{
				$costToUpgrade = new Money( $costToUpgrade->amount->add( $costToUpgrade->amount->multiply( new Number( $tax->rate() ) ) ), $currency );
			}
			
			$options[ $package->id ] = $package->_title;
								
			$renewOptions = json_decode( $package->renew_options, TRUE ) ?: array();
			$renewalOptions = array();
			foreach ( $renewOptions as $k => $option )
			{
				$renewalOptions[ $k ] = ( new RenewalTerm( new Money( $option['cost'][$currency]['amount'], $currency ), new DateInterval( 'P' . $option['term'] . mb_strtoupper( $option['unit'] ) ), $tax ) )->toDisplay();
			}
			
			if ( count( $renewalOptions ) === 1 )
			{
				foreach ( $renewalOptions as $k => $v )
				{
					if ( $costToUpgrade->amount->isZero() )
					{
						$desciptions[ $package->id ] = $v;
					}
					elseif( $costToUpgrade->amount->isPositive() )
					{
						$desciptions[ $package->id ] = Member::loggedIn()->language()->addToStack( 'upgrade_cost_and_renew', FALSE, array( 'sprintf' => array( $costToUpgrade, $v ) ) );
					}
					else
					{
						$costToUpgrade->amount = $costToUpgrade->amount->multiply( new Number( '-1' ) );
						$desciptions[ $package->id ] = Member::loggedIn()->language()->addToStack( 'downgrade_refund_and_renew', FALSE, array( 'sprintf' => array( $costToUpgrade, $v ) ) );
					}
				}
			}
			elseif ( count( $renewalOptions ) === 0 )
			{
				if( $costToUpgrade->amount->isGreaterThanZero() )
				{
					$desciptions[ $package->id ] = Member::loggedIn()->language()->addToStack( 'upgrade_cost', FALSE, array( 'sprintf' => array( $costToUpgrade ) ) );
				}
				elseif( !$costToUpgrade->amount->isPositive() )
				{
					$costToUpgrade->amount = $costToUpgrade->amount->multiply( new Number( '-1' ) );
					$desciptions[ $package->id ] = Member::loggedIn()->language()->addToStack( 'downgrade_refund', FALSE, array( 'sprintf' => array( $costToUpgrade ) ) );
				}
			}
			else
			{
				$renewalOptionsString = Member::loggedIn()->language()->formatList( $renewalOptions, Member::loggedIn()->language()->get('or_list_format') );
				$renewFieldsDescriptions = array();

				if ( $costToUpgrade->amount->isZero() )
				{
					$desciptions[ $package->id ] = $renewalOptionsString;
				}
				elseif( $costToUpgrade->amount->isGreaterThanZero() )
				{
					$desciptions[ $package->id ] = Member::loggedIn()->language()->addToStack( 'upgrade_cost_and_renew', FALSE, array( 'sprintf' => array( $costToUpgrade, $renewalOptionsString ) ) );
				}
				else
				{
					$costToUpgrade->amount = $costToUpgrade->amount->multiply( new Number( '-1' ) );
					$desciptions[ $package->id ] = Member::loggedIn()->language()->addToStack( 'downgrade_refund_and_renew', FALSE, array( 'sprintf' => array( $costToUpgrade, $renewalOptionsString ) ) );
				}
				
				$renewFields[] = new Radio( 'renew_option_' . $package->id, NULL, NULL, array( 'options' => $renewalOptions, 'descriptions' => $renewFieldsDescriptions, 'parse' => 'normal' ), NULL, NULL, NULL, 'renew_option_' . $package->id );
				Member::loggedIn()->language()->words['renew_option_' . $package->id] = Member::loggedIn()->language()->addToStack( 'renewal_term', FALSE );
				$toggles[ $package->id ] = array( 'renew_option_' . $package->id );
			}
		}
		
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--upgrade-downgrade-package';
		$form->add( new Radio( 'change_package_to', NULL, TRUE, array( 'options' => $options, 'descriptions' => $desciptions, 'toggles' => $toggles, 'parse' => 'normal' ) ) );
		foreach ( $renewFields as $field )
		{
			$form->add( $field );
		}
		
		return $form;
	}
	
	/** 
	 * Cost to upgrade to this package (may return negative value for refund)
	 *
	 * @param Purchase $purchase	The purchase to be upgraded
	 * @return    Money|NULL
	 * @throws	InvalidArgumentException
	 */
	protected function costToUpgrade(Purchase $purchase ): Money|null
	{
		$package = Package::load( $purchase->item_id );
		try
		{
			$currency = $purchase->original_invoice->currency;
		}
		catch ( Exception )
		{
			$currency = $purchase->member->defaultCurrency();
		}
		
		$priceOfExistingPackage = json_decode( $package->base_price, TRUE );
		$priceOfExistingPackage = $priceOfExistingPackage[ $currency ]['amount'];
		$renewalOptionsOnOldPackage = json_decode( $package->renew_options, TRUE );
		if ( $purchase->renewals )
		{
			foreach ( $renewalOptionsOnOldPackage as $renewalOption )
			{
				$term = $purchase->renewals->getTerm();
				if ( $renewalOption['add'] and $term['term'] == $renewalOption['term'] and $term['unit'] == $renewalOption['unit'] )
				{
					$priceOfExistingPackage += $renewalOption['cost'][ $currency ]['amount'];
				}
			}
		}
		
		$priceOfThisPackage = json_decode( $this->base_price, TRUE );
		$priceOfThisPackage = $priceOfThisPackage[ $currency ]['amount'];
		$renewalOptionsOnNewPackage = json_decode( $this->renew_options, TRUE );
		if ( $purchase->renewals )
		{
			if ( $renewalOptionsOnNewPackage )
			{
				foreach ( $renewalOptionsOnNewPackage as $renewalOption )
				{
					$term = $purchase->renewals->getTerm();
					if ( $renewalOption['add'] and $term['term'] == $renewalOption['term'] and $term['unit'] == $renewalOption['unit'] )
					{
						$priceOfThisPackage += $renewalOption['cost'][ $currency ]['amount'];
					}
				}
			}
		}
				
		if ( $priceOfThisPackage >= $priceOfExistingPackage )
		{
			$type = $package->upgrade_charge;
		}
		else
		{
			$type = $package->downgrade_refund;
		}
						
		switch ( $type )
		{
			case 0:
				return new Money( 0, $currency );
			
			case 1:
				return new Money( $priceOfThisPackage - $priceOfExistingPackage, $currency );
			
			case 2:
			
				if ( !$purchase->renewals )
				{
					return new Money( 0, $currency );
				}
				if ( !$renewalOptionsOnNewPackage )
				{
					throw new InvalidArgumentException;
				}
				
				/* What is the closest renewal option on the new package? We'll use that one */
				$renewalOptionsInDays = array();
				foreach ( $renewalOptionsOnNewPackage as $renewalTerm )
				{
					$term = ( new RenewalTerm( new Money( $renewalTerm['cost'][ $currency ]['amount'], $currency ), new DateInterval( 'P' . $renewalTerm['term'] . mb_strtoupper( $renewalTerm['unit'] ) ), $purchase->renewals->tax, $renewalTerm['add'] ) );
					$renewalOptionsInDays[ $term->days() ] = $term;
				}
				$closestRenewalOption = null;
				$numberOfDaysInCurrentRenewalTerm = $purchase->renewals->days();
				foreach ( $renewalOptionsInDays as $days => $term )
				{
					if ( $closestRenewalOption === null or abs( $numberOfDaysInCurrentRenewalTerm - $closestRenewalOption ) > abs( $days - $numberOfDaysInCurrentRenewalTerm ) )
					{
						$closestRenewalOption = $days;
					}
				}
				$renewalTermToUse = $renewalOptionsInDays[ $closestRenewalOption ];
				
				/* What is the difference between our current renewal term and the renewal term we're moving to? */
				$diff = $purchase->renewals->diff( $renewalTermToUse );
				
				/* Multiply that by how many days are left */
				$numberOfDaysInCurrentRenewalTerm = new Number( $numberOfDaysInCurrentRenewalTerm );
				$daysLeftUntilExpiry = new Number( (string) ceil( ( $purchase->expire->getTimestamp() - time() ) / 86400 ) );
				if ( !$daysLeftUntilExpiry->isGreaterThanZero() )
				{
					return new Money( new Number('0'), $currency );
				}
				elseif ( $numberOfDaysInCurrentRenewalTerm->compare( $daysLeftUntilExpiry ) === 0 )
				{
					return $diff;
				}
				else
				{			
					return new Money( $diff->amount->divide( $numberOfDaysInCurrentRenewalTerm )->multiply( $daysLeftUntilExpiry ), $currency );
				}
		}

		return null;
	}
	
	/**
	 * Actually Upgrade/Downgrade
	 *
	 * @param Purchase $purchase				The purchase
	 * @param Package $newPackage				The package to upgrade to
	 * @param int|RenewalTerm|NULL $chosenRenewalOption	The chosen renewal option
	 * @param bool $skipCharge				If TRUE, an upgrade charges and downgrade refunds will not be issued
	 * @return    Invoice|null                                                An invoice if an upgrade charge has to be paid, or void if not
	 * @throws	InvalidArgumentException											If $chosenRenewalOption is invalid
	 */
	public function upgradeDowngrade(Purchase $purchase, Package $newPackage, RenewalTerm|int|null $chosenRenewalOption = NULL, bool $skipCharge = FALSE ): Invoice|null
	{		
		/* Charge / Refund */
		if ( !$skipCharge )
		{
			$costToUpgrade = $newPackage->costToUpgrade( $purchase );
			
			/* Upgrade Charge */
			if ( $costToUpgrade->amount->isGreaterThanZero() )
			{
				$item = new UpgradeCharge( sprintf( $purchase->member->language()->get( 'upgrade_charge_item' ), $purchase->member->language()->get( "nexus_package_{$this->id}" ), $purchase->member->language()->get( "nexus_package_{$newPackage->id}" ) ), $costToUpgrade );
				$item->tax = $newPackage->tax ? Tax::load( $newPackage->tax ) : NULL;
				$item->id = $purchase->id;
				$item->extra = array( 'newPackage' => $newPackage->id, 'oldPackage' => $this->id, 'renewalOption' => $chosenRenewalOption, 'previousRenewalTerms' => $purchase->renewals ? array( 'cost' => $purchase->renewals->cost->amount, 'currency' => $purchase->renewals->cost->currency, 'term' => $purchase->renewals->getTerm(), 'tax' => $purchase->tax ) : NULL );

				if ( $newPackage->methods and $newPackage->methods != '*' )
				{
					$item->paymentMethodIds = explode( ',', $newPackage->methods );
				}
				
				$invoice = new invoice;
				$invoice->member = $purchase->member;
				$invoice->currency = $costToUpgrade->currency;
				$invoice->addItem( $item );
				$invoice->return_uri = "app=nexus&module=clients&controller=purchases&do=view&id={$purchase->id}";
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
		
		/* Work out the new renewal term */
		if ( $chosenRenewalOption instanceof RenewalTerm )
		{
			$term = $chosenRenewalOption;
		}
		else
		{
			$term = NULL;
			$renewalOptions = json_decode( $newPackage->renew_options, TRUE );
			if ( $chosenRenewalOption === NULL )
			{
				if ( $renewalOptions AND count( $renewalOptions ) === 1 )
				{
					$term = array_pop( $renewalOptions );
				}
				elseif ( $renewalOptions AND count( $renewalOptions ) !== 0 )
				{
					throw new InvalidArgumentException;
				}
			}
			else
			{
				if ( isset( $renewalOptions[ $chosenRenewalOption ] ) )
				{
					$term = $renewalOptions[ $chosenRenewalOption ];
				}
				else
				{
					throw new InvalidArgumentException;
				}
			}
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
				$term = new RenewalTerm( new Money( $term['cost'][$currency]['amount'], $currency ), new DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ), $newPackage->tax ? Tax::load( $newPackage->tax ) : NULL );
			}
		}
		
		/* If grouped, ungroup */
		$grouped = $purchase->grouped_renewals;
		if ( $grouped )
		{
			$purchase->ungroupFromParent();
			$purchase->save();
		}
		
		/* Do package-specific stuff */
		$this->onChange( $purchase, $newPackage, $chosenRenewalOption );
		
		/* Remove usergroups */
		$this->_removeUsergroups( $purchase );
		
		/* Ungroup any children */
		$groupedChildren = array();
		foreach ( $purchase->children( NULL ) as $child )
		{
			if ( $child->grouped_renewals )
			{
				$child->ungroupFromParent();
				$groupedChildren[] = $child;
			}
		}
		
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

		/* Initial Term, apply the difference if the new package has a longer initial term */
		if( $term and $newPackage->initial_term )
		{
			$initial = new DateInterval( "P{$newPackage->initial_term}" );

			/* If we're still in the initial period of the new package */
			$diff = DateTime::create()->diff( $purchase->start->add( $initial ) );
			if( !$diff->invert and $diff->days > 0 )
			{
				$purchase->expire = $purchase->start->add( $diff );
			}
		}
				
		/* Update Purchase */
		$purchase->name = Member::loggedIn()->language()->get( "nexus_package_{$newPackage->id}" );
		$purchase->item_id = $newPackage->id;
		$purchase->renewals = $term;
		$purchase->save();
		
		/* Regroup children */
		foreach ( $groupedChildren as $child )
		{
			$child->groupWithParent();
		}
		
		/* Re-add usergroups */
		$newPackage->_addUsergroups( $purchase );
		
		/* If grouped, regroup */
		if ( $grouped )
		{
			$purchase->groupWithParent();
			$purchase->save();
		}
		
		/* Cancel any pending invoices */
		if ( $pendingInvoice = $purchase->invoice_pending )
		{
			$pendingInvoice->status = invoice::STATUS_CANCELED;
			$pendingInvoice->save();
			$purchase->invoice_pending = NULL;
			$purchase->save();
		}

		return null;
	}
	
	/* !Stock */
	
	/**
	 * Change Stock Level
	 *
	 * @param int $changeBy	Amount to change by
	 * @param int|NULL $optionId	Product option ID, if applicable
	 * @return    void
	 */
	protected function _changeStockLevel(int $changeBy, ?int $optionId=NULL ): void
	{		
		if ( $this->stock == -2 )
		{
			if ( $optionId !== NULL )
			{
				try
				{
					$stock = Db::i()->select( 'opt_stock', 'nexus_product_options', array( 'opt_id=?', $optionId ) )->first();
					if ( $stock != -1 )
					{
						$newValue = $stock + $changeBy;
						if ( $newValue < 0 )
						{
							$newValue = 0;
						}
						
						Db::i()->update( 'nexus_product_options', array( 'opt_stock' => $newValue ), array( 'opt_id=?', $optionId ) );
					}
				}
				catch ( UnderflowException ) { }
			}
		}
		elseif ( $this->stock > 0 )
		{
			$newValue = $this->stock + $changeBy;
			if ( $newValue < 0 )
			{
				$newValue = 0;
			}
			$this->stock = $newValue;
			$this->save();
		}
	}
	
	/**
	 * @brief	Fields which affect the option ID
	 */
	protected ?array $_optionIdKeys = NULL;
	
	/**
	 * Get field IDs which affect the option ID
	 *
	 * @return	array
	 */
	public function optionIdKeys() : array
	{
		if( $this->_optionIdKeys === NULL )
		{
			$this->_optionIdKeys = array();
			try
			{
				$options = Db::i()->select( 'opt_values', 'nexus_product_options', array( 'opt_package=?', $this->id ) )->first();
				if ( $options = json_decode( $options, TRUE ) )
				{
					$this->_optionIdKeys = array_keys(  $options );
				}

			}
			catch ( UnderflowException ) { }
		}
		return $this->_optionIdKeys;
	}
	
	/**
	 * Get option ID
	 *
	 * @param Purchase $purchase	The purchase
	 * @return	int|NULL
	 */
	protected function _getOptionId(Purchase $purchase ) : int|null
	{
		$optionValues = array();
		foreach( $this->optionIdKeys() as $id )
		{
			$optionValues[ $id ] = $purchase->custom_fields[$id];
		}
		
		try
		{
			return Db::i()->select( 'opt_id', 'nexus_product_options', array( 'opt_package=? AND opt_values=?', $this->id, json_encode( $optionValues ) ) )->first();
		}
		catch ( UnderflowException )
		{
			return NULL;
		}
	}
	
	/* !Usergroups */
	
	/**
	 * Add user groups
	 *
	 * @param Purchase $purchase	The purchase
	 * @return	void
	 */
	public function _addUsergroups(Purchase $purchase ) : void
	{
		$extra = $purchase->extra;
			
		/* Primary Group */
		if (
			$this->primary_group // This package wants to move us into a different group...
			and $this->primary_group != $purchase->member->member_group_id // And we aren't already in that group
			and !in_array( $purchase->member->member_group_id, explode( ',', Settings::i()->cm_protected ) ) // And we're not in a group that is protected from having purchases move them out
			and ( $purchase->active or !$this->return_primary ) // And the purchase is active or this promotion applies even if the purchase isn't active (that might seem weird, but remember expired purchases can be changed/transferred/have their settings edited/etc which calls this method)
			and !( $purchase->member->isAdmin() and !in_array( $this->primary_group, array_keys( Member::administrators()['g'] ) ) ) // And moving groups wouldn't boot us out of the AdminCP
		) {
			/* Save the current group */
			$extra['old_primary_group'] = $purchase->member->member_group_id;
			$purchase->extra = $extra;
			$purchase->save();
			if ( !$purchase->member->cm_return_group )
			{
				$purchase->member->cm_return_group = $purchase->member->member_group_id;
			}
			
			/* And update to the new group */
			$purchase->member->member_group_id = $this->primary_group;
			$purchase->member->members_bitoptions['ignore_promotions'] = true;
			$purchase->member->save();
			$purchase->member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'purchase', 'action' => 'add', 'id' => $purchase->id, 'name' => $purchase->name, 'old' => $extra['old_primary_group'], 'new' => $purchase->member->member_group_id ) );
		}
						
		/* Secondary Groups */
		$secondary = array_filter( explode( ',', $this->secondary_group ), function( $v ){ return (bool) $v; } );
		$current_secondary = $purchase->member->mgroup_others ? explode( ',', $purchase->member->mgroup_others ) : array();
		$new_secondary = $current_secondary;
		if ( !empty( $secondary ) )
		{
			foreach ( $secondary as $gid )
			{
				if ( !in_array( $gid, $new_secondary ) )
				{
					$new_secondary[] = $gid;
				}
			}
		}
		if ( $current_secondary != $new_secondary and ( $purchase->active or !$this->return_secondary ) )
		{
			$extra['old_secondary_groups'] = $purchase->member->mgroup_others;
			$purchase->extra = $extra;
			$purchase->save();
									
			$purchase->member->mgroup_others = ',' . implode( ',', $new_secondary ) . ',';
			$purchase->member->save();
			$purchase->member->logHistory( 'core', 'group', array( 'type' => 'secondary', 'by' => 'purchase', 'action' => 'add', 'id' => $purchase->id, 'name' => $purchase->name, 'old' => $current_secondary, 'new' => $new_secondary ) );
		}
	}
	
	/**
	 * Remove user groups
	 *
	 * @param Purchase $purchase	The purchase
	 * @param array $basis		The current package data, if needed to override
	 * @return    void
	 */
	public function _removeUsergroups(Purchase $purchase, array $basis = array() ): void
	{		
		$basis = array_merge( $this->_data, $basis );		
		$extra = $purchase->extra;
				
		/* Primary Group */
		if ( $basis['return_primary'] )
		{
			/* We only want to move them back if they haven't been moved again since */
			if ( $purchase->member->member_group_id == $basis['primary_group'] )
			{
				$oldGroup = $purchase->member->member_group_id;
				
				/* Have we made other purchases that have changed their primary group? */
				try
				{
					$next = Db::i()->select( array( 'ps_id', 'ps_name', 'p_primary_group' ), 'nexus_purchases', array( "ps_member=? AND ps_app=? AND ps_type IN('package','subscription') AND ps_active=1 AND p_primary_group<>0 AND ps_id<>?", $purchase->member->member_id, 'nexus', $purchase->id ) )
						->join( 'nexus_packages', 'p_id=ps_item_id' )
						->first();

					/* Make sure this group exists */
					try
					{
						Group::load( $next['p_primary_group'] );
					}
					catch( OutOfRangeException )
					{
						throw new UnderflowException;
					}
					
					$purchase->member->member_group_id = $next['p_primary_group'];
					$purchase->member->save();
					$purchase->member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'purchase', 'action' => 'change', 'remove_id' => $purchase->id, 'remove_name' => $purchase->name, 'id' => $next['ps_id'], 'name' => $next['ps_name'], 'old' => $oldGroup, 'new' => $purchase->member->member_group_id ) );
				}
				/* No, move them to their original group */
				catch ( UnderflowException )
				{
					/* Does this group exist? */
					try
					{
						Group::load( $purchase->member->cm_return_group );
						$purchase->member->member_group_id = $purchase->member->cm_return_group;
					}
					catch ( OutOfRangeException )
					{
						$purchase->member->member_group_id = Settings::i()->member_group;
					}
										
					/* Save */
					$purchase->member->cm_return_group = 0;
					$purchase->member->members_bitoptions['ignore_promotions'] = false;
					$purchase->member->save();
					$purchase->member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'purchase', 'action' => 'remove', 'id' => $purchase->id, 'name' => $purchase->name, 'old' => $oldGroup, 'new' => $purchase->member->member_group_id ) );
				}
			}
		}
				
		// Secondary groups
		if ( isset( $extra['old_secondary_groups'] ) and $extra['old_secondary_groups'] !== NULL and $basis['return_secondary'] )
		{			
			/* Work some stuff out */
			$membersSecondaryGroups = $purchase->member->mgroup_others ? array_unique( array_filter( explode( ',', $purchase->member->mgroup_others ) ) ) : array();
			$currentSecondaryGroups = $membersSecondaryGroups;
			$membersPreviousSecondaryGroupsBeforeThisPurchase = array_unique( array_filter( explode( ',', $extra['old_secondary_groups'] ) ) );
			$secondaryGroupsAwardedByThisPurchase = array_unique( array_filter( explode( ',', $basis['secondary_group'] ) ) );
			
			/* Have we made other purchases that have added secondary groups? */
			$secondaryGroupsAwardedByOtherPurchases = array();
			foreach ( Db::i()->select( 'p_secondary_group', 'nexus_purchases', array( "ps_member=? AND ps_app=? AND ps_type IN('package','subscription') AND ps_active=1 AND p_secondary_group IS NOT NULL AND p_secondary_group<>? AND ps_id<>?", $purchase->member->member_id, 'nexus', '', $purchase->id ) )->join( 'nexus_packages', 'p_id=ps_item_id' ) as $secondaryGroups )
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
			if( $currentSecondaryGroups != $membersSecondaryGroups )
			{
				$purchase->member->mgroup_others = implode( ',', $membersSecondaryGroups );
				$purchase->member->save();
				$purchase->member->logHistory( 'core', 'group', array( 'type' => 'secondary', 'by' => 'purchase', 'action' => 'remove', 'id' => $purchase->id, 'name' => $purchase->name, 'old' => $currentSecondaryGroups, 'new' => $membersSecondaryGroups ) );
			}
		}
	}

	/**
	 * Do we have any products for the registration form
	 *
	 * @return	int
	 */
	public static function haveRegistrationProducts() : int
	{
		return Db::i()->select( 'COUNT(*)', 'nexus_packages', 'p_reg=1 AND p_store=1' )->first();
	}

	/**
	 * [Node] Does the currently logged in user have permission to edit this node?
	 *
	 * @return	bool
	 */
	public function canEdit(): bool
	{
		/* Locked packages cannot be edited */
		if( $this->locked )
		{
			return FALSE;
		}

		return parent::canEdit();
	}


	/**
	 * [Node] Does the currently logged in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy(): bool
	{
		/* Locked packages cannot be copied */
		if( $this->locked )
		{
			return FALSE;
		}

		return parent::canCopy();
	}

	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		foreach ( Db::i()->select( 'data', 'core_queue', array( 'app=? AND `key`=?', 'nexus', 'MassChangePurchases' ) ) as $row )
		{
			$data = json_decode( $row, TRUE );
			if ( $data['id'] == $this->_id OR $data['mass_change_purchases_to'] == $this->_id )
			{
				return FALSE;
			}
		}

		return parent::canDelete();
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return SystemFile|null
	 */
	public function primaryImage() : ?SystemFile
	{
		if( $this->_data['image'] )
		{
			return SystemFile::get( 'nexus_Products', $this->_data['image'] );
		}

		return parent::primaryImage();
	}
}
