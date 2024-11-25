<?php
/**
 * @brief		Commission Rule Filter Iterator
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		18 Aug 2014
 */

namespace IPS\nexus\CommissionRule;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Countable;
use FilterIterator;
use IPS\Db;
use IPS\Member;
use IPS\nexus\Invoice;
use IPS\Patterns\ActiveRecordIterator;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Commission Rule Filter Iterator
 * @note	When we require PHP 5.4+ this can just be replaced with a CallbackFilterIterator
 */
class Iterator extends FilterIterator implements Countable
{
	/**
	 * @brief	Member
	 */
	protected ?Member $member = NULL;
	
	/**
	 * @brief	Number of purchases
	 */
	protected ?int $numberOfPurchases = NULL;
	
	/**
	 * @brief	Value of purchases
	 */
	protected mixed $valueOfPurchases = NULL;
	
	/**
	 * @brief	Number of referral rules
	 */
	protected ?int $numberOfRules= NULL;
	
	/**
	 * Constructor
	 *
	 * @param	ActiveRecordIterator	$iterator	Iterator
	 * @param	Member							$member		Member
	 * @return	void
	 */
	public function __construct( ActiveRecordIterator $iterator, Member $member )
	{
		$this->member = $member;
		parent::__construct( $iterator );
	}
	
	/**
	 * Does this rule apply?
	 *
	 * @return	bool
	 */
	public function accept(): bool
	{	
		$rule = $this->getInnerIterator()->current();
		
		if ( $rule->by_group != '*' )
		{
			if ( !$this->member->inGroup( explode( ',', $rule->by_group ) ) )
			{
				return FALSE;
			}
		}
		
		if ( $rule->by_purchases_op )
		{
			if ( $rule->by_purchases_type === 'n' )
			{
				$value = $this->numberOfPurchases();
				
				$unit = $rule->by_purchases_unit;
			}
			else
			{
				$value = $this->valueOfPurchases();
				$value = array_map( 'floatval', $value );				
				asort( $value );
				
				$keys = array_keys( $value );
				$currency = array_pop( $keys );
				$value = $value[ $currency ];
				
				$unit = json_decode( $rule->by_purchases_unit, TRUE );
				$unit = $unit[ $currency ];
			}
			
			switch ( $rule->by_purchases_op )
			{
				case 'g':
					if ( $value < $unit )
					{
						return FALSE;
					}
					break;
					
				case 'l':
					if ( $value > $unit )
					{
						return FALSE;
					}
					break;
				
				case 'e':
					if ( $value != $unit )
					{
						return FALSE;
					}
					break;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Get the number of purchases
	 *
	 * @return	int
	 */
	protected function numberOfPurchases(): int
	{
		if ( $this->numberOfPurchases === NULL )
		{
			$this->numberOfPurchases = Db::i()->select( 'COUNT( i_id )', 'nexus_invoices', array( "i_member=? AND i_status=?", $this->member->member_id, Invoice::STATUS_PAID ) )->first();
		}
		return $this->numberOfPurchases;
	}
	
	/**
	 * Get the amounts spent
	 *
	 * @return	mixed
	 */
	protected function valueOfPurchases(): mixed
	{
		if ( $this->valueOfPurchases === NULL )
		{
			$this->valueOfPurchases = iterator_to_array( Db::i()->select( 'i_currency, SUM( i_total ) as value', 'nexus_invoices', array( "i_member=? AND i_status=?", $this->member->member_id, Invoice::STATUS_PAID ), NULL, NULL, 'i_currency' )->setKeyField( 'i_currency' )->setValueField( 'value' ) );
		}
		return $this->valueOfPurchases;
	}
	
	/**
	 * Countable
	 *
	 * @return	int
	 */
	public function count(): int
	{
		if ( $this->numberOfRules === NULL )
		{
			$this->numberOfRules = (int) Db::i()->select( 'COUNT(*)', 'nexus_referral_rules' )->first();
		}
		return $this->numberOfRules;
	}
}