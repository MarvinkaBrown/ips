<?php
/**
 * @brief		Member Filter Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		30 Jan 2024
 */

namespace IPS\nexus\extensions\core\MemberFilter;

use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Select as FormSelect;
use IPS\nexus\Donation\Goal;
use IPS\nexus\Money;
use IPS\Theme;
use LogicException;
use function defined;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Filter Extension
 */
class Donations extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param	string	$area	Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'bulkmail', 'group_promotions' ) );
	}

	/**
	 * Get Setting Field
	 *
	 * @param array $criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		return array(
			$this->_combine( 'nexus_bm_filters_total_donate', 'IPS\nexus\Form\Money', $criteria ),
			new Node( 'nexus_bm_filters_donate_goals', $criteria['donate_goals'] ?? 0, false, array( 'class' => Goal::class, 'multiple' => true, 'zeroVal' => 'any' ) )
		);
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

		foreach( $post['nexus_bm_filters_total_donate'][1] as $amount )
		{
			$amounts[$amount->currency] = $amount->amount;
		}

		return array(
			'total_donate_operator' => $post['nexus_bm_filters_total_donate'][0],
			'total_donate_amounts' => json_encode( $amounts ),
			'donate_goals' => ( is_array( $post['nexus_bm_filters_donate_goals'] ) and count( $post['nexus_bm_filters_donate_goals'] ) ) ? array_keys( $post['nexus_bm_filters_donate_goals'] ) : 0
		);
	}

	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return    array|null    Where clause
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		$where = array();

		if ( ( isset( $data['total_donate_operator'] ) and $data['total_donate_operator'] !== 'any' ) and isset( $data['total_donate_amounts'] ) )
		{
			foreach( json_decode( $data['total_donate_amounts'], true ) as $currency => $amount )
			{
				switch ( $data['total_donate_operator'] )
				{
					case 'gt':
						$operator = ">";
						break;
					case 'lt':
						$operator = "<";
						break;
				}

				$where[] = "(nexus_donate_logs.dl_amount{$operator}'{$amount}')";
			}

			$where[] = array( implode( ' OR ', $where ) );
		}

		if( isset( $data['donate_goals'] ) and $data['donate_goals'] !== 0 )
		{
			$where[] = array( Db::i()->in( 'dl_goal', $data['donate_goals'] ) );
		}

		return count( $where ) ? $where : null;
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

		if (
			( ( isset( $data['total_donate_operator'] ) and $data['total_donate_operator'] !== 'any' ) and isset( $data['total_donate_amounts'] ) ) OR
			( isset( $data['donate_goals'] ) and $data['donate_goals'] !== 0 )
		)
		{
			$query->join( 'nexus_donate_logs', "core_members.member_id=nexus_donate_logs.dl_member" );
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

		$field1 = new FormSelect( $name . '_type', $criteria['total_donate_operator'] ?? '', FALSE, $options, NULL, NULL, NULL );
		$field2 = new $field2Class( $name . '_unit', $criteria['total_donate_amounts'] ?? NULL, FALSE, array() );

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