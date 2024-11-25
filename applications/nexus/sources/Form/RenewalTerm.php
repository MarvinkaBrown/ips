<?php
/**
 * @brief		Renewal Term input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		25 Mar 2014
 */

namespace IPS\nexus\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Helpers\Form\FormAbstract;
use IPS\Member;
use IPS\nexus\Money;
use IPS\nexus\Purchase\RenewalTerm as RenewalTermClass;
use IPS\Theme;
use LengthException;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Renewal Term input class for Form Builder
 */
class RenewalTerm extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'customer'				=> \IPS\nexus\Customer,	// Customer this is for (sets appropriate default currency)
	 		'currency'				=> 'USD',				// Alternatively to specifying customer, can manually specify currency (defaults to NULL)
	 		'allCurrencies'			=> FALSE,				// If TRUE, will ask for a price in all currencies (defaults to FALSE)
	 		'addToBase'				=> FALSE,				// If TRUE, a checkbox will be added asking if the price should be added to the base price
	 		'lockTerm'				=> FALSE,				// If TRUE, only the price (not the term) will be editable
	 		'lockPrice'				=> FALSE,				// If TRUE, only the term (not the price) will be editable
	 		'nullLang'				=> FALSE,				// If a value is provided, an "unlimited" checkbox will show with this label which, if checked, will cause the return value to be null
	 		'initialTerm'			=> FALSE,				// Set to TRUE if this is to fetermine the initial term for a package. Will show a "[] or lifetime" checkbox and change some wording
	 		'initialTermLang'		=> 'term_no_renewals',	// The label to use for the "[] or lifetime" checkbox
	 		'unlimitedTogglesOn'		=> [...],				// Toggles for if the "[] or lifetime" checkbox is checked
	 		'unlimitedTogglesOff'	=> [...],				// Toggles for if the "[] or lifetime" checkbox is unchecked
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'customer'				=> NULL,
		'currency'				=> NULL,
		'allCurrencies'			=> FALSE,
		'addToBase'				=> NULL,
		'lockTerm'				=> FALSE,
		'lockPrice'				=> FALSE,
		'nullLang'				=> NULL,
		'initialTerm'			=> FALSE,
		'initialTermLang'		=> 'term_no_renewals',
		'unlimitedTogglesOn'	=> [],
		'unlimitedTogglesOff'	=> []
	);
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		return Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->renewalTerm( $this->name, $this->value, $this->options );
	}
	
	/**
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue(): mixed
	{
		if ( is_array( $this->value ) )
		{
			if ( isset( $this->value['null'] ) /* or !isset( $this->value['term'] ) or !$this->value['term'] or !isset( $this->value['unit'] ) or !$this->value['unit'] */ )
			{
				return NULL;
			}
			else
			{
				/* Work out prices */
				if ( $this->options['allCurrencies'] )
				{
					$costs = array();
					foreach ( Money::currencies() as $currency )
					{
						if ( isset( $this->value[ 'amount_' . $currency ] ) )
						{
							$costs[ $currency ] = new Money( $this->value[ 'amount_' . $currency ], $currency );
						}
						else
						{
							$costs[ $currency ] = 0;
						}
					}
				}
				else
				{
					if ( isset( $this->value['currency'] ) )
					{
						$currency = $this->value['currency'];
					}
					else
					{
						$currencies = Money::currencies();
						$currency = array_shift( $currencies );
					}
					$costs = isset( $this->value['amount'] ) ? new Money( $this->value['amount'], $currency ) : NULL;
				}

				/* If we have no price specified, stop here */
				if( $costs === NULL OR ( is_array( $costs ) AND !count( $costs ) ) )
				{
					return null;
				}
				
				/* Work out term */
				if ( isset( $this->value['unlimited'] ) )
				{
					$term = NULL;
				}
				else
				{
					if ( !isset( $this->value['term'] ) or !$this->value['term'] or !isset( $this->value['unit'] ) or !$this->value['unit'] )
					{
						return NULL;
					}
					
					if ( $this->value['term'] < 1 )
					{
						$this->value = new RenewalTermClass( $costs, new DateInterval( 'P' . 1 . mb_strtoupper( $this->value['unit'] ) ), NULL, $this->options['addToBase'] ? isset( $this->value['add'] ) : FALSE );
						throw new LengthException( Member::loggedIn()->language()->addToStack('form_number_min', FALSE, array( 'sprintf' => array( 0 ) ) ) );
					}
					if ( !in_array( $this->value['unit'], array( 'd', 'm', 'y' ) ) )
					{
						$this->value = new RenewalTermClass( $costs, new DateInterval( 'P' . $this->value['term'] . 'D' ), NULL, $this->options['addToBase'] ? isset( $this->value['add'] ) : FALSE );
						throw new OutOfRangeException( 'form_bad_value' );
					}
					
					$term = new DateInterval( 'P' . $this->value['term'] . mb_strtoupper( $this->value['unit'] ) );
				}
				
				/* Return */
				return new RenewalTermClass( $costs, $term, NULL, $this->options['addToBase'] ? isset( $this->value['add'] ) : FALSE );
			}
		}

		return $this->value;
	}
}