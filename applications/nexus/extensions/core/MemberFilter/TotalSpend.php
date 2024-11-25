<?php
/**
 * @brief		Member filter extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		8 Aug 2018
 */

namespace IPS\nexus\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db\Select;
use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Select as FormSelect;
use IPS\nexus\Money;
use IPS\Theme;
use LogicException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member filter extension
 */
class TotalSpend extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param string $area Area to check (bulkmail, group_promotions, automatic_moderation)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'bulkmail' ) );
	}

	/** 
	 * Get Setting Field
	 *
	 * @param array $criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		return array( $this->_combine( 'nexus_bm_filters_total_spend', 'IPS\nexus\Form\Money', $criteria ) );
	}
	
	/**
	 * Save the filter data
	 *
	 * @param	array	$post	Form values
	 * @return	array			False, or an array of data to use later when filtering the members
	 * @throws LogicException
	 */
	public function save( array $post ): array
	{
		$amounts = array();

		foreach( $post['nexus_bm_filters_total_spend'][1] as $amount )
		{
			$amounts[$amount->currency] = $amount->amount;
		}

		return array( 'total_spend_operator' => $post['nexus_bm_filters_total_spend'][0], 'total_spend_amounts' => json_encode( $amounts ) );
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return    array|null    Where clause
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if ( ( isset( $data['total_spend_operator'] ) and $data['total_spend_operator'] !== 'any' ) and isset( $data['total_spend_amounts'] ) )
		{
			$where = array();

			foreach( json_decode( $data['total_spend_amounts'], true ) as $currency => $amount )
			{
				switch ( $data['total_spend_operator'] )
				{
					case 'gt':
						$operator = ">";
						break;
					case 'lt':
						$operator = "<";
						break;
				}

				$where[] = "(nexus_customer_spend.spend_amount{$operator}'{$amount}' and nexus_customer_spend.spend_currency='{$currency}')";
			}

			return array( implode( ' OR ', $where ) );
		}
		return NULL;
	}
	
	/**
	 * Callback for member retrieval database query
	 * Can be used to set joins
	 *
	 * @param array $data	The array returned from the save() method
	 * @param	Select	$query	The query
	 * @return	void
	 */
	public function queryCallback( array $data, Select $query ) : void
	{
		$currencies = Money::currencies();
		$defaultCurrency = array_shift( $currencies );

		if ( ( isset( $data['total_spend_operator'] ) and $data['total_spend_operator'] !== 'any' ) and isset( $data['total_spend_amounts'] ) )
		{
			$query->join( 'nexus_customer_spend', "core_members.member_id=nexus_customer_spend.spend_member_id AND nexus_customer_spend.spend_currency='{$defaultCurrency}'" );
		}
	}

	/**
	 * Combine two fields
	 *
	 * @param	string		$name			Field name
	 * @param	string		$field2Class	Classname for second field
	 * @param	array		$criteria		Value returned from the save() method
	 * @return	Custom
	 */
	public function _combine( string $name, string $field2Class, array $criteria ) : Custom
	{
		$validate = NULL;

			$options = array(
				'options' => array(
					'any'	=> 'any_value',
					'gt'	=> 'gt',
					'lt'	=> 'lt'
				),
				'toggles' => array(
					'gt'	=> array( $name . '_unit' ),
					'lt'	=> array( $name . '_unit' ),
				)
			);

		$field1 = new FormSelect( $name . '_type', $criteria['total_spend_operator'] ?? '', FALSE, $options, NULL, NULL, NULL );
		$field2 = new $field2Class( $name . '_unit', $criteria['total_spend_amounts'] ?? NULL, FALSE, array() );

		return new Custom( $name, array( "gt", NULL ), FALSE, array(
			'getHtml'	=> function() use ( $name, $field1, $field2 )
			{
				return Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->combined( $name, $field1, $field2 );
			},
			'formatValue'	=> function() use ( $field1, $field2 )
			{
				return array( $field1->value, $field2->value );
			}
		) );
	}
}