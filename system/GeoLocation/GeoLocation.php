<?php
/**
 * @brief		GeoLocation
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Apr 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadFunctionCallException;
use BadMethodCallException;
use IPS\GeoLocation\GeoCoder;
use IPS\GeoLocation\Maps\Google;
use IPS\GeoLocation\Maps\Mapbox;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Platform\Bridge;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * GeoLocation
 */
class GeoLocation
{
	/**
	 * @brief	EU Member States
	 */
	const EU = array(
		'AT',
		'BE',
		'BG',
		'HR',
		'CY',
		'CZ',
		'DK',
		'EE',
		'FI',
		'FR',
		'DE',
		'GR',
		'HU',
		'IE',
		'IT',
		'LV',
		'LT',
		'LU',
		'MT',
		'NL',
		'PL',
		'PT',
		'RO',
		'SK',
		'SI',
		'ES',
		'SE'
	);

	/**
	 * @brief	Country Code List
	 */
	public static array $countries = array(
		'AF', // Afghanistan
		'AX', // Åland Islands
		'AL', // Albania
		'DZ', // Algeria
		'AS', // American Samoa
		'AD', // Andorra
		'AO', // Angola
		'AI', // Anguilla
		'AQ', // Antarctica
		'AG', // Antigua and Barbuda
		'AR', // Argentina
		'AM', // Armenia
		'AW', // Aruba
		'AU', // Australia
		'AT', // Austria
		'AZ', // Azerbaijan
		'BS', // Bahamas
		'BH', // Bahrain
		'BD', // Bangladesh
		'BB', // Barbados
		'BY', // Belarus
		'BE', // Belgium
		'BZ', // Belize
		'BJ', // Benin
		'BM', // Bermuda
		'BT', // Bhutan
		'BO', // Bolivia, Plurinational State Of
		'BA', // Bosnia and Herzegovina
		'BW', // Botswana
		'BV', // Bouvet Island
		'BR', // Brazil
		'IO', // British Indian Ocean Territory
		'BN', // Brunei Darussalam
		'BG', // Bulgaria
		'BF', // Burkina Faso
		'BI', // Burundi
		'KH', // Cambodia
		'CM', // Cameroon
		'CA', // Canada
		'CV', // Cape Verde
		'BQ', // Caribbean Netherlands
		'KY', // Cayman Islands
		'CF', // Central African Republic
		'TD', // Chad
		'CL', // Chile
		'CN', // China
		'CX', // Christmas Island
		'CC', // Cocos (Keeling) Islands
		'CO', // Colombia
		'KM', // Comoros
		'CG', // Congo
		'CD', // Congo, The Democratic Republic Of The
		'CK', // Cook Islands
		'CR', // Costa Rica
		'CI', // Côte d’Ivoire
		'HR', // Croatia
		'CU', // Cuba
		'CW', // Curaçao
		'CY', // Cyprus
		'CZ', // Czech Republic
		'DK', // Denmark
		'DJ', // Djibouti
		'DM', // Dominica
		'DO', // Dominican Republic
		'EC', // Ecuador
		'EG', // Egypt
		'SV', // El Salvador
		'GQ', // Equatorial Guinea
		'ER', // Eritrea
		'EE', // Estonia
		'ET', // Ethiopia
		'FK', // Falkland Islands (Malvinas)
		'FO', // Faroe Islands
		'FJ', // Fiji
		'FI', // Finland
		'FR', // France
		'GF', // French Guiana
		'PF', // French Polynesia
		'TF', // French Southern Territories
		'GA', // Gabon
		'GM', // Gambia
		'GE', // Georgia
		'DE', // Germany
		'GH', // Ghana
		'GI', // Gibraltar
		'GR', // Greece
		'GL', // Greenland
		'GD', // Grenada
		'GP', // Guadeloupe
		'GU', // Guam
		'GT', // Guatemala
		'GG', // Guernsey
		'GN', // Guinea
		'GW', // Guinea-Bissau
		'GY', // Guyana
		'HT', // Haiti
		'HM', // Heard Island and McDonald Islands
		'VA', // Holy See (Vatican City State)
		'HN', // Honduras
		'HK', // Hong Kong
		'HU', // Hungary
		'IS', // Iceland
		'IN', // India
		'ID', // Indonesia
		'IR', // Iran, Islamic Republic Of
		'IQ', // Iraq
		'IE', // Ireland
		'IM', // Isle Of Man
		'IL', // Israel
		'IT', // Italy
		'JM', // Jamaica
		'JP', // Japan
		'JE', // Jersey
		'JO', // Jordan
		'KZ', // Kazakhstan
		'KE', // Kenya
		'KI', // Kiribati
		'KP', // Korea, Democratic People's Republic Of
		'KR', // Korea, Republic Of
		'KW', // Kuwait
		'KG', // Kyrgyzstan
		'LA', // Laos People's Democratic Republic
		'LV', // Latvia
		'LB', // Lebanon
		'LS', // Lesotho
		'LR', // Liberia
		'LY', // Libya
		'LI', // Liechtenstein
		'LT', // Lithuania
		'LU', // Luxembourg
		'MO', // Macao
		'MK', // Macedonia, The Former Yugoslav Republic Of
		'MG', // Madagascar
		'MW', // Malawi
		'MY', // Malaysia
		'MV', // Maldives
		'ML', // Mali
		'MT', // Malta
		'MH', // Marshall Islands
		'MQ', // Martinique
		'MR', // Mauritania
		'MU', // Mauritius
		'YT', // Mayotte
		'MX', // Mexico
		'FM', // Micronesia, Federated States Of
		'MD', // Moldova, Republic Of
		'MC', // Monaco
		'MN', // Mongolia
		'ME', // Montenegro
		'MS', // Montserrat
		'MA', // Morocco
		'MZ', // Mozambique
		'MM', // Myanmar
		'NA', // Namibia
		'NR', // Nauru
		'NP', // Nepal
		'NL', // Netherlands
		'NC', // New Caledonia
		'NZ', // New Zealand
		'NI', // Nicaragua
		'NE', // Niger
		'NG', // Nigeria
		'NU', // Niue
		'NF', // Norfolk Island
		'MP', // Northern Mariana Islands
		'NO', // Norway
		'OM', // Oman
		'PK', // Pakistan
		'PW', // Palau
		'PS', // Palestine, State of
		'PA', // Panama
		'PG', // Papua New Guinea
		'PY', // Paraguay
		'PE', // Peru
		'PH', // Philippines
		'PN', // Pitcairn
		'PL', // Poland
		'PT', // Portugal
		'PR', // Puerto Rico
		'QA', // Qatar
		'RE', // Réunion
		'RO', // Romania
		'RU', // Russian Federation
		'RW', // Rwanda
		'BL', // Saint Barthélemy
		'SH', // Saint Helena, Ascension and Tristan da Cunha
		'KN', // Saint Kitts and Nevis
		'LC', // Saint Lucia
		'MF', // Saint Martin
		'PM', // Saint Pierre and Miquelon
		'VC', // Saint Vincent and The Grenadines
		'WS', // Samoa
		'SM', // San Marino
		'ST', // Sao Tome and Principe
		'SA', // Saudi Arabia
		'SN', // Senegal
		'RS', // Serbia
		'SC', // Seychelles
		'SL', // Sierra Leone
		'SG', // Singapore
		'SX', // Sint Maarten
		'SK', // Slovakia
		'SI', // Slovenia
		'SB', // Solomon Islands
		'SO', // Somalia
		'ZA', // South Africa
		'GS', // South Georgia and The South Sandwich Islands
		'SS', // South Sudan
		'ES', // Spain
		'LK', // Sri Lanka
		'SD', // Sudan
		'SR', // Suriname
		'SJ', // Svalbard and Jan Mayen
		'SZ', // Swaziland
		'SE', // Sweden
		'CH', // Switzerland
		'SY', // Syrian Arab Republic
		'TW', // Taiwan, Province Of China
		'TJ', // Tajikistan
		'TZ', // Tanzania, United Republic Of
		'TH', // Thailand
		'TL', // Timor-Leste
		'TG', // Togo
		'TK', // Tokelau
		'TO', // Tonga
		'TT', // Trinidad and Tobago
		'TN', // Tunisia
		'TR', // Turkey
		'TM', // Turkmenistan
		'TC', // Turks and Caicos Islands
		'TV', // Tuvalu
		'UG', // Uganda
		'UA', // Ukraine
		'AE', // United Arab Emirates
		'GB', // United Kingdom
		'US', // United States
		'UM', // United States Minor Outlying Islands
		'UY', // Uruguay
		'UZ', // Uzbekistan
		'VU', // Vanuatu
		'VE', // Venezuela, Bolivarian Republic Of
		'VN', // Vietnam
		'VG', // Virgin Islands, British
		'VI', // Virgin Islands, U.S.
		'WF', // Wallis and Futuna
		'EH', // Western Sahara
		'YE', // Yemen
		'ZM', // Zambia
		'ZW', // Zimbabwe
	);

	/**
	 * @brief	State List
	 */
	public static array $states = array(
		'AU' => array(
			'Australian Capital Territory',
			'New South Wales',
			'Northern Territory',
			'Queensland',
			'South Australia',
			'Tasmania',
			'Victoria',
			'Western Australia',
		),
		'BR' => array(
			'Acre',
			'Alagoas',
			'Amapá',
			'Amazonas',
			'Bahia',
			'Ceará',
			'Distrito Federal',
			'Espírito Santo',
			'Goiás',
			'Maranhão',
			'Mato Grosso',
			'Mato Grosso do Sul',
			'Minas Gerais',
			'Paraná',
			'Paraíba',
			'Pará',
			'Pernambuco',
			'Piauí',
			'Rio de Janeiro',
			'Rio Grande do Norte',
			'Rio Grande do Sul',
			'Rondônia',
			'Roraima',
			'Santa Catarina',
			'Sergipe',
			'São Paulo',
			'Tocantins'
		),
		'CA' => array(
			"Alberta",
			"British Columbia",
			"Manitoba",
			"New Brunswick",
			"Newfoundland and Labrador",
			"Northwest Territories",
			"Nova Scotia",
			"Nunavut",
			"Ontario",
			"Prince Edward Island",
			"Quebec",
			"Saskatchewan",
			"Yukon",
		),
		'PT' => array(
			"Aveiro",
			"Azores",
			"Beja",
			"Braga",
			"Braganca",
			"Castelo Branco",
			"Coimbra",
			"Evora",
			"Faro",
			"Guarda",
			"Leiria",
			"Lisboa",
			"Madeira Islands",
			"Portalegre",
			"Porto",
			"Santarem",
			"Setubal",
			"Viana do Castelo",
			"Vila Real",
			"Viseu",
		),
		'US' => array(
			"Alabama",
			"Alaska",
			"American Samoa",
			"Arizona",
			"Arkansas",
			"California",
			"Colorado",
			"Connecticut",
			"Delaware",
			"District of Columbia",
			"Federated States Of Micronesia",
			"Florida",
			"Georgia",
			"Guam",
			"Hawaii",
			"Idaho",
			"Illinois",
			"Indiana",
			"Iowa",
			"Kansas",
			"Kentucky",
			"Louisiana",
			"Maine",
			"Marshall Islands",
			"Maryland",
			"Massachusetts",
			"Michigan",
			"Minnesota",
			"Mississippi",
			"Missouri",
			"Montana",
			"Nebraska",
			"Nevada",
			"New Hampshire",
			"New Jersey",
			"New Mexico",
			"New York",
			"North Carolina",
			"North Dakota",
			"Northern Mariana Islands",
			"Ohio",
			"Oklahoma",
			"Oregon",
			"Palau",
			"Pennsylvania",
			"Puerto Rico",
			"Rhode Island",
			"South Carolina",
			"South Dakota",
			"Tennessee",
			"Texas",
			"Utah",
			"Vermont",
			"Virgin Islands",
			"Virginia",
			"Washington",
			"West Virginia",
			"Wisconsin",
			"Wyoming",
			"Armed Forces - Americas",
			"Armed Forces - Europe",
			"Armed Forces - Pacific",
		),
	);

	/**
	 * @brief	Latitude
	 */
	public ?float $lat = NULL;
	
	/**
	 * @brief	Longitude
	 */
	public ?float $long = NULL;
	
	/**
	 * @brief	Address Lines
	 */
	public array $addressLines = array( NULL );
	
	/**
	 * @brief	City
	 */
	public ?string $city = NULL;
	
	/**
	 * @brief	Region
	 */
	public ?string $region = NULL;
	
	/**
	 * @brief	Country (2 character code)
	 */
	public ?string $country = NULL;
	
	/**
	 * @brief	Postal Code
	 */
	public ?string $postalCode = NULL;

	/**
	 * @brief	Place Name
	 */
	public ?string $placeName = NULL;

	/**
	 * @brief	County
	 */
	public ?string $county = NULL;
		
	/**
	 * @brief	Map
	 */
	protected mixed $map = NULL;

	/**
	 * @brief	VAT (used in Commerce)
	 */
	public ?string $vat = NULL;
	
	/**
	 * Get Requester Location
	 *
	 * @return	GeoLocation
	 */
	public static function getRequesterLocation(): GeoLocation
	{
		return Bridge::i()->getRequesterLocation();
	}
	
	/**
	 * Get by IP address
	 *
	 * @param array|string $ip	IP Address or array of IP addresses
	 * @return    GeoLocation|array
	 * @throws	BadFunctionCallException		Service is not available
	 * @throws	Exception		Error communicating with external service
	 * @throws	RuntimeException				Error within the external service
	 * @throws	OutOfRangeException			IP address has no data
	 */
	public static function getByIp( array|string $ip ): GeoLocation|array
	{
		/* If the service is not turned on - throw an exception */
		if ( !Settings::i()->ipsgeoip )
		{
			throw new BadFunctionCallException;
		}
		
		/* If the license key is invalid or expired the service won't work, so throw an exception */
		$licenseData = IPS::licenseKey();
		if( !$licenseData or !$licenseData['active'] )
		{
			throw new BadFunctionCallException;
		}

		/* If the parameter is an array, the response will be an array */
		if( is_array( $ip ) )
		{
			$result			= array();
			$needToLookup	= array();
		}
		
		/* Check the cache */
		try
		{
			if( is_array( $ip ) )
			{
				$atLeastOneFailed = FALSE;

				foreach( $ip as $ipAddress )
				{
					try
					{
						$data = Db::i()->select( 'data', 'core_geoip_cache', array( 'ip_address=?', $ipAddress ) )->first();
						
						$result[ $ipAddress ] = $data;
					}
					catch( UnderflowException $e )
					{
						$atLeastOneFailed	= TRUE;
						$needToLookup[]		= $ipAddress;
					}
				}

				/* If any of the requested IP addresses failed, we need to look them up */
				if( $atLeastOneFailed )
				{
					throw new UnderflowException;
				}
			}
			else
			{
				$data = Db::i()->select( 'data', 'core_geoip_cache', array( 'ip_address=?', $ip ) )->first();
				if ( !$data )
				{
					/* @note This was changed from an \UnderflowException to be consistent with the \OutOfRangeException thrown later on, otherwise the system will keep trying even when it should not. cleanup task will remove out of date entries and it can try again then. */
					throw new OutOfRangeException;
				}
			}
		}
		
		/* Not in the cache - get from the external service */
		catch ( UnderflowException $e )
		{
			/* Fetch the details */
			if( is_array( $ip ) AND count( $needToLookup ) )
			{
				$response = Url::ips( 'geoip/' . urlencode( json_encode( $needToLookup ) ) )->request()->login( Settings::i()->ipb_reg_number, '' )->get();
			}
			else
			{
				$response = Url::ips( 'geoip/' . urlencode( $ip ) )->request()->login( Settings::i()->ipb_reg_number, '' )->get();
			}

			/* If it's a 404, the IP doesn't exist, we still store NULL to prevent multiple calls */
			if ( $response->httpResponseCode == 404 )
			{
				$data = NULL;
			}
			
			/* If it's anything other than a 200, log it and throw exception */
			elseif ( $response->httpResponseCode != 200 )
			{
				Log::log( "GeoIP Error\n\nRequested IP: {$ip}\n\nResponse:\n" . print_r( $response, TRUE ), 'geoip' );
				throw new RuntimeException;
			} 
			
			/* Otherwise it's fine */
			else
			{
				$data = (string) $response;

				if( is_array( $ip ) )
				{
					$data = json_decode( $data, true );
				}
			}

			/* Cache */
			if( is_array( $ip ) )
			{
				if( is_array( $data ) )
				{
					foreach( $data as $k => $v )
					{
						/* Database and buildFromJson need this as json */
						$v = $v ? json_encode( $v ) : NULL;

						$result[ $k ] = $v;
						Db::i()->replace( 'core_geoip_cache', array(
							'ip_address'	=> $k,
							'data'			=> $v,
							'date'			=> time()
						) );
					}
				}
			}
			else
			{
				Db::i()->replace( 'core_geoip_cache', array(
					'ip_address'	=> $ip,
					'data'			=> $data,
					'date'			=> time()
				) );
			}
		}
		
		/* Return */
		if( is_array( $ip ) and isset( $result ))
		{
			foreach( $result as $ipAddress => $ipData )
			{
				$result[ $ipAddress ] = $ipData ? static::buildFromJson( $ipData ) : NULL;
			}

			return $result;
		}
		else
		{
			/* If there's nothing, throw an exception */
			if ( !isset( $data ) or !$data )
			{
				throw new OutOfRangeException;
			}

			return static::buildFromJson( $data );
		}
	}

	/**
	 * Geocode Location
	 *
	 * @param string|null $input 		Location search term
	 * @return	static
	 */
	public static function geocodeLocation( string $input = NULL ): static
	{
		return GeoCoder::i()->decodeLocation( $input );
	}

	/**
	 * @brief Cached lookups to prevent duplicate lookups
	 */
	static protected array $lookupCache = array( 'latlong' => array(), 'address' => array() );

	/**
	 * Get by latitude and longitude
	 *
	 * @param float $lat	Latitude
	 * @param float $long	Longitude
	 * @return    GeoLocation
	 * @throws	BadFunctionCallException
	 * @throws	Exception
	 */
	public static function getByLatLong( float $lat, float $long ): GeoLocation
	{
		if( isset( static::$lookupCache['latlong'][ $lat . 'x' . $long ] ) )
		{
			return static::$lookupCache['latlong'][ $lat . 'x' . $long ];
		}

		static::$lookupCache['latlong'][ $lat . 'x' . $long ] = GeoCoder::i()->decodeLatLong( $lat, $long );

		return static::$lookupCache['latlong'][ $lat . 'x' . $long ];
	}

	/**
	 * Get the latitude and longitude for the current object. Address must be set.
	 *
	 * @param bool $setAddress	Whether or not to update the address information from the GeoCoder service
	 * @return	void
	 * @throws	BadMethodCallException
	 */
	public function getLatLong(bool $setAddress=FALSE ) : void
	{
		$lookupKey = md5( $this->toString() );

		if( isset( static::$lookupCache['address'][ $lookupKey ] ) )
		{
			foreach( json_decode( static::$lookupCache['address'][ $lookupKey ], true ) as $property => $value )
			{
				if( $property == 'lat' OR $property == 'long' OR $setAddress === TRUE )
				{
					$this->$property = $value;
				}
			}
			return;
		}

		GeoCoder::i()->setLatLong( $this, $setAddress );

		static::$lookupCache['address'][ $lookupKey ] = json_encode( $this );
	}

	/**
	 * Build from JSON
	 *
	 * @param string|null $json	JSON data
	 * @return	GeoLocation
	 */
	public static function buildFromJson( ?string $json ): static
	{
		if( empty( $json ) )
		{
			return new static;
		}

		if( $json = json_decode( $json, TRUE ) )
		{
			$obj = new static;
			if ( !empty( $json ) )
			{
				foreach ( $json as $k => $v )
				{
					$obj->$k = $v;
				}
			}
			return $obj;
		}

		return new static;
	}
	
	/**
	 * Get location
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->toString();
	}

	/**
	 * Convert to string
	 *
	 * @param string $separator	Separator
	 * @param string|null $name	Optional name to add to the address
	 * @return	string
	 * @note	While some places like France capitalize the surname, this cannot be done automatically because the surname could be supplied as the first or last
	* name value, and the name could contain more than one string that constitutes the surname.
	 */
	public function toString( string $separator=', ', string $name=NULL ): string
	{
		$output	= array();

		if( $name !== NULL )
		{
			$output[]	= $name;
		}

		foreach ( array( 'business', 'addressLines', 'city', 'region', 'postalCode' ) as $k )
		{
			if ( isset( $this->$k ) and $this->$k )
			{
				if ( is_array( $this->$k ) )
				{
					foreach ( $this->$k as $v )
					{
						if( $v )
						{
							$output[] = htmlspecialchars( $v, ENT_DISALLOWED, 'UTF-8', FALSE );
						}
					}
				}
				else
				{
					$output[] = htmlspecialchars( $this->$k, ENT_DISALLOWED, 'UTF-8', FALSE );
				}
			}
		}
		if ( $this->country and $this->country !== static::buildFromJson( Settings::i()->site_address )->country )
		{
			try
			{
				$output[] = strtoupper( Member::loggedIn()->language()->get( htmlspecialchars( 'country-' . $this->country, ENT_DISALLOWED, 'UTF-8', FALSE ) ) );
			}
			catch ( UnderflowException $e )
			{
				$output[] = htmlspecialchars( $this->country, ENT_DISALLOWED, 'UTF-8', FALSE );
			}
		}

		if ( !empty( $output ) )
		{
			return implode( $separator, $output );
		}
		elseif ( $this->lat and $this->long )
		{
			return "{$this->lat},{$this->long}";
		}

		return '';
	}
	
	/**
	 * Build Map
	 *
	 * @return	mixed
	 * @throws	BadMethodCallException
	 */
	public function map(): mixed
	{
		if ( $this->map === NULL )
		{
			if ( Settings::i()->googlemaps and Settings::i()->google_maps_api_key )
			{
				$this->map = new Google( $this );
			}
			else if ( Settings::i()->mapbox and Settings::i()->mapbox_api_key)
			{
				$this->map = new Mapbox( $this );
			}
			else
			{
				throw new BadMethodCallException;
			}
		}

		return $this->map;
	}

	/**
	 * Return value to use in template
	 *
	 * @param string $data	Data to parse
	 * @return	string
	 */
	public static function parseForOutput( string $data ): string
	{
		$address	= json_decode( $data, TRUE );
		$mapper		= new static;

		if ( is_array( $address ) )
		{
			foreach( $address as $k => $v )
			{
				$mapper->$k = $v;
			}
		}

		return (string) $mapper;
	}

	/**
	 * Get output for API
	 *
	 * @param Member|NULL $authorizedMember The member making the API request or NULL for API Key / client_credentials
	 * @return GeoLocation|array
	 * @apiresponse		float			lat				Latitude
	 * @apiresponse		float			long			Longitude
	 * @apiresponse 	[string]		addressLines	Lines of the street address
	 * @apiresponse		string			city			City
	 * @apiresponse		string			region			State/Region
	 * @apiresponse		string			country			2-letter country code
	 * @apiresponse		string			postalCode		ZIP/Postal Code
	 */
	public function apiOutput( ?Member $authorizedMember = NULL ): GeoLocation|array
	{
		$returnObject = clone $this;
		$returnObject->addressLines = array_values( $returnObject->addressLines );
		
		return $returnObject;
	}

	/**
	 * GeoLocation is enabled
	 *
	 * @return	bool
	 */
	public static function enabled(): bool
	{
		return ( ( Settings::i()->googlemaps and Settings::i()->google_maps_api_key ) || ( Settings::i()->mapbox and Settings::i()->mapbox_api_key ) );
	}
}
