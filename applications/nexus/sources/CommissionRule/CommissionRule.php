<?php
/**
 * @brief		Commission Rule Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		15 Aug 2014
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Member\Group;
use IPS\nexus\Form\Money;
use IPS\Node\Model;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Commission Rule Node
 */
class CommissionRule extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_referral_rules';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'rrule_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'name';

	/**
	 * @brief	[Node] Automatically set position for new nodes
	 */
	public static bool $automaticPositionDetermination = FALSE;
	
	/**
	 * @brief	[Node] Sortable?
	 */
	public static bool $nodeSortable = FALSE;
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'commission_rules';
	
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
		'module'	=> 'customers',
		'all'		=> 'referrals_commission_rules'
	);
	
	/**
	 * [Node] Get title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		return $this->name;
	}

	/**
	 * @return array|null
	 */
	public function get_purchase_packages() : ?array
	{
		if( isset( $this->_data['purchase_packages'] ) and $this->_data['purchase_packages'] )
		{
			if( $test = json_decode( $this->_data['purchase_packages'], true ) )
			{
				return $test;
			}

			return [
				'product' => [
					'products' => explode( ",", $this->_data['purchase_packages'] )
				]
			];
		}

		return null;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$numberOfPurchasesField = array(
			'getHtml'	=> function( $field )
			{
				return Theme::i()->getTemplate('customers', 'nexus' )->numberOfPurchasesField( $field );
			},
			'formatValue'=> function( $field )
			{
				if ( isset( $field->value[4] ) )
				{
					return array( 'n', '', 0 );
				}
				else
				{
					if ( !isset( $field->value[3] ) )
					{
						return $field->value;
					}
					return array( $field->value[0], $field->value[1], ( $field->value[0] == 'n' ) ? $field->value[2] : json_encode( $field->value[3] ) );
				}
			}
		);
		
		$groups = array();
		foreach ( Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		$groupsExcludingGuests = $groups;
		unset( $groupsExcludingGuests[ Settings::i()->guest_group ] );

		$form->add( new Text( 'rrule_name', $this->name, TRUE ) );
		
		$form->addHeader('rrule_referrer');
		$form->add( new Custom( 'rrule_by_purchases', array( $this->by_purchases_type, $this->by_purchases_op, $this->by_purchases_unit ), FALSE, $numberOfPurchasesField ) );
		$form->add( new CheckboxSet( 'rrule_by_group', ( $this->by_group and $this->by_group != '*' ) ? explode( ',', $this->by_group ) : '*', FALSE, array( 'options' => $groupsExcludingGuests, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'any', 'impliedUnlimited' => TRUE ) ) );

		$form->addHeader('rrule_referree');
		$form->add( new Custom( 'rrule_for_purchases', array( $this->for_purchases_type, $this->for_purchases_op, $this->for_purchases_unit ), FALSE, $numberOfPurchasesField ) );
		$form->add( new CheckboxSet( 'rrule_for_group', ( $this->for_group and $this->for_group != '*' ) ? explode( ',', $this->for_group ) : '*', FALSE, array( 'options' => $groups, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'any', 'impliedUnlimited' => TRUE ) ) );
		
		$form->addHeader('rrule_purchase');
		$form->add( new Custom( 'rrule_purchase_amount', array( $this->purchase_amount_op, $this->purchase_amount_unit ), FALSE, array(
			'getHtml'	=> function( $field )
			{
				return Theme::i()->getTemplate( 'customers', 'nexus' )->purchaseValueField( $field );
			},
			'formatValue'	=> function( $field )
			{
				$value = $field->value;
				if ( isset( $value[2] ) )
				{
					return array( '', '' );
				}
				$value[1] = is_array( $value[1] ) ? json_encode( $value[1] ) : $value[1];
				return $value;
			}
		) ) );

		$productFields = [];
		$toggles = [ 'rrule_purchase_renewal', 'rrule_purchase_package_limit' ];
		foreach( Application::allExtensions( 'nexus', 'Item', null, null, null, false ) as $extension )
		{
			if( method_exists( $extension, 'customFormElements' ) )
			{
				$fields = $extension::customFormElements( $this->purchase_packages );
				if( count( $fields ) )
				{
					foreach( $fields as $field )
					{
						/* @var FormAbstract $field */
						$productFields[] = $field;
						$toggles[] = $field->htmlId ?? $field->name;
					}
				}
			}
		}

		$form->add( new Radio( 'rrule_purchase_any', $this->purchase_packages ? $this->purchase_any : 2, FALSE, array(
			'options'	=> array(
				2	=> 'rrule_purchase_any_nr',
				0	=> 'rrule_purchase_any_all',
				1	=> 'rrule_purchase_any_any',
			),
			'toggles'	=> array(
				0	=> $toggles,
				1	=> $toggles,
			)
		) ) );

		foreach( $productFields as $field )
		{
			$form->add( $field );
		}

		$form->add( new YesNo( 'rrule_purchase_renewal', $this->purchase_renewal, FALSE, array(), NULL, NULL, NULL, 'rrule_purchase_renewal' ) );
		
		$form->addHeader('rrule_commission_header');
		$form->add( new Number( 'rrule_commission', (int) $this->commission, TRUE, array( 'min' => 0, 'max' => 100 ), NULL, NULL, '%' ) );
		$form->add( new Radio( 'rrule_purchase_package_limit', $this->purchase_package_limit, FALSE, array( 'options' => array(
			0	=> 'rrule_purchase_package_limit_no',
			1	=> 'rrule_purchase_package_limit_yes'
		) ), NULL, NULL, NULL, 'rrule_purchase_package_limit' ) );
		$form->add( new Money( 'rrule_commission_limit', $this->commission_limit ?: '*', FALSE, array( 'unlimitedLang' => 'no_restriction' ) ) );
		
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		foreach ( array( 'by_purchases', 'for_purchases' ) as $k )
		{
			foreach ( array( 'type', 'op', 'unit' ) as $i => $v )
			{
				$key = "{$k}_{$v}";
				$values[ $key ] = $values[ 'rrule_' . $k ][ $i ];
			}
			unset( $values[ 'rrule_' . $k ] );
		}
				
		foreach ( array( 'by_group' ) as $k )
		{
			$values[ $k ] = $values[ 'rrule_' . $k ] == '*' ? '*' : implode( ',', $values[ 'rrule_' . $k ] );
			unset( $values[ 'rrule_' . $k ] );
		}
		
		if( isset( $values['rrule_purchase_amount'] ) )
		{
			$values['purchase_amount_op'] = $values['rrule_purchase_amount'][0];
			$values['purchase_amount_unit'] = $values['rrule_purchase_amount'][1];
			unset( $values['rrule_purchase_amount'] );
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
		
		if( isset( $values['rrule_purchase_any'] ) )
		{
			switch ( $values['rrule_purchase_any'] )
			{
				case 0:
				case 1:
					$values['rrule_purchase_packages'] = json_encode( $productData );
					break;
				case 2:
					$values['rrule_purchase_packages'] = '';
					$values['rrule_purchase_any'] = 1;
					break;
			}
		}
		
		if( isset( $values['rrule_commission_limit'] ) )
		{
			$values['rrule_commission_limit'] = ( !$values['rrule_commission_limit'] or $values['rrule_commission_limit'] == '*' ) ? '*' : json_encode( $values['rrule_commission_limit'] );
		}
						
		return $values;
	}
		
	/**
	 * Get description for client area
	 *
	 * @return	string
	 */
	public function description(): string
	{
		$conditions = array();
		
		if ( $this->for_purchases_op )
		{
			$prices = NULL;
			if ( $this->for_purchases_type == 'v' )
			{
				$prices = array();
				foreach ( json_decode( $this->for_purchases_unit, TRUE ) as $currency => $amount )
				{
					$prices[] = new Money( $amount, $currency );
				}
				$prices = Member::loggedIn()->language()->formatList( $prices, Member::loggedIn()->language()->get('or_list_format') );
			}
			
			$conditions[] = Member::loggedIn()->language()->addToStack( 'ref_cond_for_purch', FALSE, array( 'sprintf' => array(
				Member::loggedIn()->language()->addToStack( 'ref_cond_' . $this->for_purchases_op ),
				$this->for_purchases_type == 'v' ? $prices : $this->for_purchases_unit,
				Member::loggedIn()->language()->addToStack( 'ref_cond_' . $this->for_purchases_type ),
			) ) );
		}
		
		if ( $this->for_group != '*' )
		{
			$groups = array();
			foreach ( explode( ',', $this->for_group ) as $groupId )
			{
				$groups[] = Group::load( $groupId )->name;
			}
			
			$conditions[] = Member::loggedIn()->language()->addToStack( 'ref_cond_for_group', FALSE, array( 'sprintf' => array(
				Member::loggedIn()->language()->formatList( $groups, Member::loggedIn()->language()->get('or_list_format') )
			) ) );
		}
		
		if ( $this->purchase_packages )
		{
			$packages = array();
			foreach( Application::allExtensions( 'nexus', 'Item', null, null, null, false ) as $extension )
			{
				if( method_exists( $extension, 'customFormDescription' ) )
				{
					$packages = array_merge( $packages, $extension::customFormDescription( $this->purchase_packages ) );
				}
			}
			
			$conditions[] = Member::loggedIn()->language()->addToStack( $this->purchase_renewal ? 'ref_cond_packages_r' : 'ref_cond_packages', FALSE, array( 'sprintf' => array(
				Member::loggedIn()->language()->formatList( $packages, $this->purchase_any ? Member::loggedIn()->language()->get('or_list_format') : NULL )
			) ) );
		}
		
		if ( $this->purchase_amount_op )
		{
			$prices = array();
			foreach ( json_decode( $this->purchase_amount_unit, TRUE ) as $currency => $amount )
			{
				$prices[] = new Money( $amount, $currency );
			}
			$prices = Member::loggedIn()->language()->formatList( $prices, Member::loggedIn()->language()->get('or_list_format') );
			
			$conditions[] = Member::loggedIn()->language()->addToStack( 'ref_cond_purchase_value', FALSE, array( 'sprintf' => array(
				Member::loggedIn()->language()->addToStack( 'ref_cond_' . $this->purchase_amount_op ),
				$prices
			) ) );
		}

		if( count( $conditions ) )
		{
			return Member::loggedIn()->language()->addToStack( 'ref_cond', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $conditions ) ) ) );
		}
		else
		{
			return Member::loggedIn()->language()->addToStack( 'ref_no_cond', FALSE );
		}
	}
	
	/**
	 * Get commission limit for client area
	 *
	 * @return	string
	 */
	public function commissionLimit(): string
	{
		$prices = array();
		if ( $this->commission_limit and $limits = json_decode( $this->commission_limit, TRUE ) and is_array( $limits ) )
		{
			foreach ( $limits as $currency => $amount )
			{
				$prices[] = new Money( $amount['amount'], $currency );
			}
		}
		return count( $prices ) ? Member::loggedIn()->language()->addToStack( 'ref_comm_limit', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $prices, Member::loggedIn()->language()->get('or_list_format') ) ) ) ) : '';
	}
}