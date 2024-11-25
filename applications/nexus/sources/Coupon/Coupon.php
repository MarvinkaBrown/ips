<?php
/**
 * @brief		Coupon Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		11 Mar 2014
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Application;
use IPS\DateTime;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Math\Number;
use IPS\Member\Group;
use IPS\nexus\extensions\nexus\Item\CouponDiscount;
use IPS\nexus\Invoice\Item\Renewal;
use IPS\Node\Model;
use IPS\Request;
use OutOfRangeException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Coupon Node
 */
class Coupon extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_coupons';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'c_';
			
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'menu__nexus_store_coupons';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'c_code' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
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
		'prefix'	=> 'coupons_',
	);

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;
	
	/**
	 * [Node] Get title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		return $this->code;
	}

	/**
	 * @return array|string
	 */
	public function get_products() : array|string
	{
		if( isset( $this->_data['products'] ) and $this->_data['products'] !== '*' )
		{
			if( $test = json_decode( $this->_data['products'], true ) )
			{
				return $test;
			}

			return [
				'product' => [
					'products' => explode( ",", $this->_data['products'] )
				]
			];
		}

		return '*';
	}

	/**
	 * @param array|string|null $val
	 * @return void
	 */
	public function set_products( array|string|null $val ) : void
	{
		$this->_data['products'] = ( is_array( $val ) and count( $val ) ) ? json_encode( $val ) : '*';
	}
	
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
		
		$form->addHeader( 'coupon_basic_settings' );
		$id = $this->id ?: NULL;
		$form->add( new Text( 'c_code', $this->id ? $this->code : mb_strtoupper( mb_substr( md5( mt_rand() ), 0, 5 ) ), TRUE, array( 'maxLength' => 25 ), function( $val ) use ( $id )
		{
			try
			{
				$coupon = Coupon::load( $val, 'c_code' );
				if ( $coupon->id AND $coupon->id != $id )
				{
					throw new DomainException('c_code_err');
				}
			}
			catch ( OutOfRangeException ) {}
		} ) );
		$form->add( new Radio( 'c_unit', $this->id ? $this->unit : 'v', TRUE, array( 'options' => array( 'v' => 'c_unit_v', 'p' => 'c_unit_p' ), 'toggles' => array( 'v' => array( 'c_discount_v' ), 'p' => array( 'c_discount_p' ) ) ) ) );
		$form->add( new \IPS\nexus\Form\Money( 'c_discount_v', $this->unit === 'v' ? $this->discount : NULL, NULL, array(), function( $val )
		{
			if ( !$val and Request::i()->c_unit === 'v' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'c_discount_v' ) );
		$form->add( new Form\Number( 'c_discount_p', $this->unit === 'p' ? $this->discount : NULL, NULL, array( 'max' => 100 ), function($val )
		{
			if ( !$val and Request::i()->c_unit === 'p' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, '%', 'c_discount_p' ) );
		
		$form->addHeader( 'coupon_products' );
		$form->add( new Radio( 'c_limit_discount', $this->limit_discount, FALSE, array(
			'options' => array(
				0 => 'c_limit_discount_no',
				1 => 'c_limit_discount_yes'
			),
			'toggles' => array(
				1 => array( 'c_products', 'c_renewals' )
			)
		), NULL, NULL, NULL, 'c_limit_discount' ) );

		$productTypes = [];
		$productFields = [];
		$toggles = [];
		foreach( Application::allExtensions( 'nexus', 'Item', null, null, null, false ) as $extension )
		{
			if( method_exists( $extension, 'customFormElements' ) )
			{
				$fields = $extension::customFormElements( $this->products );
				if( count( $fields ) )
				{
					$productTypes[$extension::$title] = $extension::$title;
					$toggles[$extension::$title] = [];
					foreach( $fields as $field )
					{
						/* @var FormAbstract $field */
						$productFields[] = $field;
						$toggles[$extension::$title][] = $field->htmlId ?? $field->name;
					}
				}
			}
		}

		$form->add( new CheckboxSet( 'c_products', ( $this->products === '*' ? null : array_keys( $this->products ) ), null, array(
			'options' => $productTypes,
			'toggles' => $toggles,
			'noDefault' => true
		), null, null, null, 'c_products' ) );

		foreach( $productFields as $field )
		{
			$form->add( $field );
		}

		$form->add( new YesNo( 'c_renewals', (int) $this->renewals, false, array(), null, null, null, 'c_renewals' ) );

		$form->addHeader( 'coupon_dates' );
		$form->add( new Date( 'c_start', $this->id ? DateTime::ts( $this->start ) : DateTime::ts( time() ), TRUE, array( 'time' => TRUE ) ) );
		$form->add( new Date( 'c_end', ( $this->id and $this->end ) ? DateTime::ts( $this->end ) : 0, TRUE, array( 'time' => TRUE, 'unlimited' => 0, 'unlimitedLang' => 'no_end_date' ) ) );
		
		$form->addHeader( 'coupon_restrictions' );
		$form->add( new YesNo( 'c_combine', $this->combine ) );
		$form->add( new Form\Number( 'c_uses', $this->id ? $this->uses : -1, FALSE, array( 'unlimited' => -1 ) ) );
		$form->add( new Form\Number( 'c_member_uses', $this->id ? $this->member_uses : 1, FALSE, array( 'unlimited' => -1 ) ) );
		$form->add( new CheckboxSet( 'c_groups', ( $this->id and $this->groups !== '*' ) ? explode( ',', $this->groups ) : '*', FALSE, array( 'options' => $groups, 'multiple' => TRUE, 'unlimited' => '*', 'impliedUnlimited' => TRUE ) ) );

        parent::form( $form );
	}
		
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( isset( $values['c_unit'] ) )
		{
			$values['c_discount'] = $values['c_unit'] === 'v' ? json_encode( $values['c_discount_v'] ) : $values['c_discount_p'];

			unset( $values['c_discount_v'] );
			unset( $values['c_discount_p'] );
		}

		/* We need to loop through the extensions even if we
		are not going to use the data, because the fields need to be unset */
		$productData = [];
		foreach( Application::allExtensions( 'nexus', 'Item', null, null, null, false ) as $extension )
		{
			if( method_exists( $extension, 'saveCustomForm' ) )
			{
				if( $extensionData = $extension::saveCustomForm( $values, $this ) )
				{
					$productData[ $extension::$title ] = $extensionData;
				}
			}
		}

		if( $values['c_limit_discount'] != 1 )
		{
			$productData = '*';
		}

		$values['c_products'] = $productData;
		
		if( isset( $values['c_start'] ) )
		{
			$values['c_start'] = $values['c_start']->getTimestamp();
		}

		if( isset( $values['c_end'] ) )
		{
			$values['c_end'] = $values['c_end'] ? $values['c_end']->getTimestamp() : 0;
		}
		
		if( isset( $values['c_groups'] ) )
		{
			$values['c_groups'] = is_array( $values['c_groups'] ) ? implode( ',', $values['c_groups'] ) : '*';
		}
		
		return $values;
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
		return array_merge( array(
			'view'	=> array(
				'icon'	=> 'search',
				'title'	=> 'view_coupon_uses',
				'link'	=> $url->setQueryString( array( 'do' => 'viewUses', 'id' => $this->id ) )
			),
		), parent::getButtons( $url, $subnode ) );
	}
	
	/**
	 * Use coupon
	 *
	 * @param Invoice $invoice	Invoice to use against
	 * @param Customer $customer	The customer using
	 * @return	CouponDiscount
	 * @throws	DomainException
	 */
	public function useCoupon( Invoice $invoice, Customer $customer ) : CouponDiscount
	{
		/* Restricted to groups? */
		if ( $this->groups !== '*' )
		{
			$inGroup = FALSE;
			foreach ( explode( ',', $this->groups ) as $groupId )
			{
				if ( $customer->inGroup( $groupId ) )
				{
					$inGroup = TRUE;
					break;
				}
			}
			if ( !$inGroup )
			{
				throw new DomainException( 'coupon_not_in_group' );
			}
		}
		
		/* Valid dates */
		if ( $this->start and $this->start > time() )
		{
			throw new DomainException( 'coupon_not_started' );
		}
		if ( $this->end and $this->end < time() )
		{
			throw new DomainException( 'coupon_expired' );
		}
		
		/* Maximum uses? */
		if ( $this->uses == 0 )
		{
			throw new DomainException( 'coupon_expired' );
		}
		
		/* Maximum uses per member? */
		$uses = $this->used_by ? json_decode( $this->used_by, TRUE ) : array();
		$customerIdentifier = $customer->member_id ?: $invoice->guest_data['member']['email'];
		if ( $this->member_uses != -1 )
		{
			if ( isset( $uses[ $customerIdentifier ] ) and $uses[ $customerIdentifier ] >= $this->member_uses )
			{
				throw new DomainException( 'coupon_exceeded_member_uses' );
			}
		}
		
		/* Use in conjunction with other coupons? */
		foreach ( $invoice->items as $item )
		{
			if ( $item instanceof CouponDiscount )
			{
				if ( $item->id === $this->id )
				{
					throw new DomainException( 'coupon_already_used' );
				}
				else
				{
					if ( !$this->combine )
					{
						throw new DomainException( $customer->language()->addToStack( 'coupon_not_in_conjunction', FALSE, array( 'sprintf' => array( $this->code ) ) ) );
					}
					else
					{
						try
						{
							$otherCoupon = static::load( $item->id );
							if ( !$otherCoupon->combine )
							{
								throw new DomainException( $customer->language()->addToStack( 'coupon_not_in_conjunction', FALSE, array( 'sprintf' => array( $otherCoupon->code ) ) ) );
							}
						}
						catch ( OutOfRangeException ) { }
					}
				}
			}
		}
		
		/* Restricted to products? */
		$items = NULL;
		if ( $this->products !== '*' )
		{
			$hasAllowedProduct = FALSE;
			$productTotal = new Number('0');
			
			$items = array();
			
			foreach ( $invoice->items as $k => $item )
			{
				if( $item instanceof \IPS\nexus\Invoice\Item\Purchase )
				{
					if( $item->isValid( $this->products, $invoice, $customer ) )
					{
						$hasAllowedProduct = TRUE;
						$productTotal = $productTotal->add( $item->price->amount->multiply( new Number("{$item->quantity}") ) );
						$items[] = $k;
					}
				}
				elseif ( $this->renewals and $item instanceof Renewal )
				{
					if( $item->isValid( $this->products, $invoice, $customer ) )
					{
						$hasAllowedProduct = TRUE;
						$productTotal = $productTotal->add( $item->price->amount );
						$items[] = $k;
					}
				}
			}
			
			if ( !$hasAllowedProduct )
			{
				throw new DomainException( 'coupon_invalid_products' );
			}
		}
						
		/* How much are we taking off? */
		$discount = new Money( 0, $invoice->currency );
		if ( $this->unit === 'v' )
		{
			$prices = json_decode( $this->discount, TRUE );
			if ( isset( $prices[ $invoice->currency ] ) )
			{
				$discount = new Money( -$prices[ $invoice->currency ]['amount'], $invoice->currency );
			}
		}
		else
		{
			if ( $this->limit_discount and isset( $productTotal ) )
			{
				$base = $productTotal;
			}
			else
			{
				$summary = $invoice->summary();
				$base = $summary['subtotal']->amount;
			}

			$discount = new Money( $base->percentage( $this->discount )->multiply( new Number( '-1' ) ), $invoice->currency );
		}
		
		/* Never allow a discount greater than the invoice total */
		if ( $discount->amount->multiply( new Number( '-1' ) )->compare( $invoice->total->amount ) === 1 )
		{
			$discount = new Money( $invoice->total->amount->multiply( new Number( '-1' ) ), $invoice->currency );
		}
				
		/* Save that we've used it */
		if ( isset( $uses[ $customerIdentifier ] ) )
		{
			$uses[ $customerIdentifier ]++;
		}
		else
		{
			$uses[ $customerIdentifier ] = 1;
		}
		$this->used_by = json_encode( $uses );
		$this->save();
				
		/* Generate item */
		$item = new CouponDiscount( $this->code, $discount );
		$item->id = $this->id;
		$item->extra['usedBy'] = $customer->member_id;
		$item->extra['type'] = $this->unit;
		$item->extra['value'] = $this->unit === 'v' ? $discount->amount : $this->discount;
		$item->extra['items'] = $items;
		return $item;
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		parent::__clone();

		$this->code = mb_strtoupper( mb_substr( md5( mt_rand() ), 0, 5 ) );
		$this->used_by = array();
		$this->save();
	}

	/**
	 * Search
	 *
	 * @param	string		$column	Column to search
	 * @param	string		$query	Search query
	 * @param	string|null	$order	Column to order by
	 * @param	mixed		$where	Where clause
	 * @return	array
	 */
	public static function search( string $column, string $query, ?string $order=NULL, mixed $where=array() ): array
	{
		if( $column === '_title' )
		{
			$column = 'c_code';
		}

		if( $order === '_title' )
		{
			$order = 'c_code';
		}

		return parent::search( $column, $query, $order, $where );
	}
}