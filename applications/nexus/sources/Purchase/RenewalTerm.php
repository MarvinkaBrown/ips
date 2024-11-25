<?php
/**
 * @brief		Renewal Term Object
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		13 Feb 2014
 */

namespace IPS\nexus\Purchase;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use IPS\DateTime;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Money;
use IPS\nexus\Tax;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Renewal Term Object
 */
class RenewalTerm
{
	/**
	 * @brief	Cost
	 */
	public Money|array|null $cost = null;
	
	/**
	 * @brief	Interval
	 */
	public ?DateInterval $interval = null;
	
	/**
	 * @brief	Tax
	 */
	public ?Tax $tax = null;
	
	/**
	 * @brief	Add to base price?
	 */
	public bool $addToBase = FALSE;
	
	/**
	 * @brief	Grace period
	 */
	public ?DateInterval $gracePeriod = null;
	
	/**
	 * Constructor
	 *
	 * @param	Money|array	$cost			Cost
	 * @param	DateInterval|null			$interval		Interval
	 * @param	Tax|NULL		$tax			Tax
	 * @param	bool					$addToBase		Add to base?
	 * @param	DateInterval|NULL		$gracePeriod	Grace period
	 * @return	void
	 */ 
	public function __construct( Money|array $cost, ?DateInterval $interval = NULL, Tax $tax = NULL, bool $addToBase = FALSE, ?DateInterval $gracePeriod = NULL )
	{
		$this->cost = $cost;
		$this->interval = $interval;
		$this->tax = $tax;
		$this->addToBase = $addToBase;
		$this->gracePeriod = $gracePeriod;
	}
	
	/**
	 * Get term
	 *
	 * @return	array
	 */
	public function getTerm() : array
	{
		if( $this->interval->y )
		{
			return array( 'term' => $this->interval->y, 'unit' => 'y' );
		}
		elseif( $this->interval->m )
		{
			return array( 'term' => $this->interval->m, 'unit' => 'm' );
		}
		else
		{
			return array( 'term' => $this->interval->d, 'unit' => 'd' );
		}
	}
	
	/**
	 * Get term unit
	 *
	 * @return	string
	 */
	public function getTermUnit() : string
	{
		$term = $this->getTerm();
		$lang = Member::loggedIn()->language();
		switch( $term['unit'] )
		{
			case 'd':
				if ( $term['term'] % 7 == 0 )
				{
					return $lang->pluralize( $lang->get('renew_weeks'), array( $term['term'] / 7 ) );
				}
				else
				{
					return $lang->pluralize( $lang->get('renew_days'), array( $term['term'] ) );
				}
			case 'm':
				return $lang->pluralize( $lang->get('renew_months'), array( $term['term'] ) );
			case 'y':
				return $lang->pluralize( $lang->get('renew_years'), array( $term['term'] ) );
		}

		return "";
	}
	
	/**
	 * Number of days
	 *
	 * @return	string
	 */
	public function days(): string
	{
		return number_format( DateTime::intervalToDays( $this->interval ), 2, '.', '' );
	}
	
	/**
	 * Calculate cost per day
	 *
	 * @return	Money|int	Cost per day
	 */
	public function costPerDay() : Money|int
	{
		$days = $this->days();
		if ( !$days )
		{
			return 0;
		}
		else
		{
			return new Money( $this->cost->amount->divide( new Number("{$days}") ), $this->cost->currency );
		}
	}
	
	/**
	 * Get the combined cost of this term and another term (used for grouping)
	 *
	 * @param RenewalTerm $term	Term to add
	 * @return	Money
	 * @throws	DomainException
	 */
	public function add( RenewalTerm $term ) : Money
	{
		/* They need to have the same currency */
		if ( $this->cost->currency !== $term->cost->currency )
		{
			throw new DomainException('currencies_dont_match');
		}
		
		/* Get some details */
		$thisTerm = $this->getTerm();
		$otherTerm = $term->getTerm();
		$adjustedCost = $term->cost->amount;
		
		/* If they're not based on the same term, try to normalise as best we can */
		if ( $thisTerm['unit'] != $otherTerm['unit'] )
		{
			switch ( $thisTerm['unit'] )
			{
				case 'd':
					switch ( $otherTerm['unit'] )
					{
						case 'm':
							$otherTerm['term'] *= ( 365 / 12 );
							break;
							
						case 'y':
							$otherTerm['term'] *= 365;
							break;
					}
					break;
				case 'm':
					switch ( $otherTerm['unit'] )
					{
						case 'd':
							$thisTerm['term'] *= ( 365 / 12 );
							break;
							
						case 'y':
							$otherTerm['term'] *= 12;
							break;
					}
					break;
				case 'y':
					switch ( $otherTerm['unit'] )
					{
						case 'd':
							$thisTerm['term'] *= 365;
							break;
							
						case 'm':
							$thisTerm['term'] *= 12;
							break;
					}
					break;
			}
		}
			
		/* If they're not the same term, adjust */
		if ( $thisTerm['term'] != $otherTerm['term'] )
		{
			$adjustedCost = $adjustedCost->multiply( ( ( new Number("{$thisTerm['term']}") )->divide( new Number("{$otherTerm['term']}") ) ) );
		}
		
		/* And return */
		return new Money( $this->cost->amount->add( $adjustedCost ), $this->cost->currency );
	}
	
	/**
	 * Get the cost of this term subtract another term (used for grouping)
	 *
	 * @param RenewalTerm $term	Term to subtract
	 * @return	Money
	 * @throws	DomainException
	 */
	public function subtract( RenewalTerm $term ) : Money
	{
		$term->cost->amount = $term->cost->amount->multiply( new Number( '-1' ) );
		return $this->add( $term );
	}
	
	/**
	 * Times quantity (used to describe a combined renewal cost)
	 *
	 * @param	int	$n	The number to times by
	 * @return	string
	 */
	public function times( int $n ) : string
	{
		$cost = new Money( $this->cost->amount->multiply( new Number("{$n}") ), $this->cost->currency );
		return sprintf( Member::loggedIn()->language()->get( 'renew_option'), $cost, $this->getTermUnit() );
	}
	
	/**
	 * Get difference between this term and another
	 *
	 * @param RenewalTerm $otherTerm				The other term
	 * @param	bool							$returnAsPercentage		If TRUE, will return percentage difference rather than money amount
	 * @return	Money|Number
	 * @throws DomainException
	 */
	public function diff( RenewalTerm $otherTerm, bool $returnAsPercentage = FALSE ) : Money|Number
	{
		/* Sanity check */
		if ( $this->cost->currency !== $otherTerm->cost->currency )
		{
			throw new DomainException('currencies_dont_match');
		}
		
		/* Try to normalise the terms so we can work off multiplying - we want to either be dealing in days or months */
		$thisTermDetails = $this->getTerm();
		$otherTermDetails = $otherTerm->getTerm();
		foreach ( array( 'thisTermDetails', 'otherTermDetails' ) as $t )
		{
			if ( ${$t}['unit'] === 'y' )
			{
				${$t}['unit'] = 'm';
				${$t}['term'] *= 12;
			}
			if ( ${$t}['unit'] === 'w' )
			{
				${$t}['unit'] = 'd';
				${$t}['term'] *= 7;
			}
		}
		
		/* If the two terms are now based on the same unit of time, we can just multiply */		
		if ( $thisTermDetails['unit'] === $otherTermDetails['unit'] )
		{
			$factor = ( new Number( (string) $thisTermDetails['term'] ) )->divide( ( new Number( (string) $otherTermDetails['term'] ) ) );
			$thisCostPerDivision = $this->cost->amount;
			$otherCostPerDivision = $otherTerm->cost->amount->multiply( $factor );
		}
		/* Otherwise we have to work it out based on the cost per day */
		else
		{
			$days = new Number( $this->days() );
			$thisCostPerDivision = $this->costPerDay()->amount->multiply( $days );
			$otherCostPerDivision = $otherTerm->costPerDay()->amount->multiply( $days );
		}
		
		/* Work out the saving */
		$saving = $otherCostPerDivision->subtract( $thisCostPerDivision );
		
		/* Return in desired format */
		if ( $returnAsPercentage )
		{			
			return ( new Number('100') )->divide( $otherCostPerDivision, 4 )->multiply( $saving );
		}
		else
		{
			return new Money( $saving, $this->cost->currency );
		}
	}
	
	/**
	 * To String
	 *
	 * @return	string
	 */
	public function __toString() : string
	{		
		return sprintf( Member::loggedIn()->language()->get( 'renew_option'), $this->cost, $this->getTermUnit() )	;
	}
	
	/**
	 * To String incl. tax
	 *
	 * @param	Customer|NULL	$customer			The customer (NULL for currently logged in member)
	 * @param	int							$quantity			The quantity to times amount by
	 * @return	string
	 */
	public function toDisplay( ?Customer $customer = NULL, int $quantity = 1 ) : string
	{
		$cost = $this->cost;
		if ( Settings::i()->nexus_show_tax and $this->tax )
		{
			$customer = $customer ?: Customer::loggedIn();
			$taxRate = new Number( $this->tax->rate( $customer->estimatedLocation() ) );
			$cost = new Money( $cost->amount->add( $cost->amount->multiply( $taxRate ) ), $cost->currency );
		}
		
		if ( $quantity != 1 )
		{
			$cost = new Money( $cost->amount->multiply( new Number( "$quantity" ) ), $cost->currency );
		}
		
		return sprintf( Member::loggedIn()->language()->get( 'renew_option'), $cost, $this->getTermUnit() );
	}
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse		string				term		'd' for days; 'w' for weeks; 'm' for months; 'y' for years
	 * @apiresponse		int					unit		The number for term. For example, if the renewal term is every 6 months, term will be 'm' and unit will be 6
	 * @apiresponse		\IPS\nexus\Money	price		The renewal price
	 * @apiresponse		\IPS\nexus\Tax		taxClass	If the renewal price is taxed, the tax class that applies
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		$term = $this->getTerm();
		return array(
			'term'			=> $term['term'],
			'unit'			=> $term['unit'],
			'price'			=> $this->cost->apiOutput( $authorizedMember ),
			'taxClass'		=> $this->tax?->apiOutput($authorizedMember),
		);
	}
}