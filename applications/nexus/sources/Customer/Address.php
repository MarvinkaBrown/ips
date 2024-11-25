<?php
/**
 * @brief		Customer Address Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		11 Feb 2014
 */

namespace IPS\nexus\Customer;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\GeoLocation;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\Patterns\ActiveRecord;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Customer Address Model
 */

/* @property Customer $member */
class Address extends ActiveRecord
{	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'nexus_customer_addresses';

	/**
	 * @brief	State Codes
	 * @see		https://developer.paypal.com/docs/classic/api/state_codes/
	 */
	public static array $stateCodes = array(
		'CA' => array(
			'AB'	=> "Alberta",
			'BC'	=> "British Columbia",
			'MB'	=> "Manitoba",
			'NB'	=> "New Brunswick",
			'NL'	=> "Newfoundland and Labrador",
			'NT'	=> "Northwest Territories",
			'NS'	=> "Nova Scotia",
			'NU'	=> "Nunavut",
			'ON'	=> "Ontario",
			'PE'	=> "Prince Edward Island",
			'QC'	=> "Quebec",
			'SK'	=> "Saskatchewan",
			'YT'	=> "Yukon",
			),
		'US' => array(
			'AL'	=> "Alabama",
			'AK'	=> "Alaska",
			'AS'	=> "American Samoa",
			'AZ'	=> "Arizona",
			'AR'	=> "Arkansas",
			'CA'	=> "California",
			'CO'	=> "Colorado",
			'CT'	=> "Connecticut",
			'DE'	=> "Delaware",
			'DC'	=> "District Of Columbia",
			'FL'	=> "Florida",
			'GA'	=> "Georgia",
			'GU'	=> "Guam",
			'HI'	=> "Hawaii",
			'ID'	=> "Idaho",
			'IL'	=> "Illinois",
			'IN'	=> "Indiana",
			'IA'	=> "Iowa",
			'KS'	=> "Kansas",
			'KY'	=> "Kentucky",
			'LA'	=> "Louisiana",
			'ME'	=> "Maine",
			'MD'	=> "Maryland",
			'MA'	=> "Massachusetts",
			'MI'	=> "Michigan",
			'MN'	=> "Minnesota",
			'MS'	=> "Mississippi",
			'MO'	=> "Missouri",
			'MT'	=> "Montana",
			'NE'	=> "Nebraska",
			'NV'	=> "Nevada",
			'NH'	=> "New Hampshire",
			'NJ'	=> "New Jersey",
			'NM'	=> "New Mexico",
			'NY'	=> "New York",
			'NC'	=> "North Carolina",
			'ND'	=> "North Dakota",
			'MP'	=> "Northern Mariana Islands",
			'OH'	=> "Ohio",
			'OK'	=> "Oklahoma",
			'OR'	=> "Oregon",
			'PA'	=> "Pennsylvania",
			'PR'	=> "Puerto Rico",
			'RI'	=> "Rhode Island",
			'SC'	=> "South Carolina",
			'SD'	=> "South Dakota",
			'TN'	=> "Tennessee",
			'TX'	=> "Texas",
			'UT'	=> "Utah",
			'VT'	=> "Vermont",
			'VI'	=> "Virgin Islands",
			'VA'	=> "Virginia",
			'WA'	=> "Washington",
			'WV'	=> "West Virginia",
			'WI'	=> "Wisconsin",
			'WY'	=> "Wyoming",
			'AA'	=> "Armed Forces - Americas",
			'AE'	=> "Armed Forces - Europe",
			'AP'	=> "Armed Forces - Pacific",
			),
		);
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->added = new DateTime;
	}
	
	/**
	 * Get member
	 *
	 * @return	Customer
	 */
	public function get_member(): Customer
	{
		return Customer::load( $this->_data['member'] );
	}
	
	/**
	 * Set member
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function set_member( Member $member ) : void
	{
		$this->_data['member'] = $member->member_id;
	}
	
	/**
	 * Get address
	 *
	 * @return	GeoLocation
	 */
	public function get_address(): GeoLocation
	{
		return isset( $this->_data['address'] ) ? GeoLocation::buildFromJson( $this->_data['address'] ) : new GeoLocation;
	}
	
	/**
	 * Set member
	 *
	 * @param	GeoLocation	$address	Address
	 * @return	void
	 */
	public function set_address( GeoLocation $address ) : void
	{
		$this->_data['address'] = json_encode( $address );
	}
	
	/**
	 * Get added date
	 *
	 * @return	DateTime
	 */
	public function get_added(): DateTime
	{
		return DateTime::ts( $this->_data['added'] );
	}
	
	/**
	 * Set added date
	 *
	 * @param	DateTime	$date	The invoice date
	 * @return	void
	 */
	public function set_added( DateTime $date ) : void
	{
		$this->_data['added'] = $date->getTimestamp();
	}
}