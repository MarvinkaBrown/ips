<?php
/**
 * @brief		Consumer/business address input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		8 Oct 2019
 */

namespace IPS\nexus\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\GeoLocation;
use IPS\Helpers\Form\Address;
use IPS\Http\Request\Exception;
use IPS\Log;
use IPS\nexus\Tax;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Consumer/business address input class for Form Builder
 */
class BusinessAddress extends Address
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
			'minimize'			=> FALSE,			// Minimize the address field until the user focuses?
			'requireFullAddress'=> TRUE,				// Does this have to be a full address? Default is TRUE, may set to FALSE if a more generic location is acceptable
			'vat'				=> FALSE,			// In addition if asking if this is a business address, should we prompt for the VAT number?
	 	);
	 * @endcode
	 */
	protected array $defaultOptions = array(
		'minimize' 				=> FALSE,
		'requireFullAddress'	=> TRUE,
		'vat'					=> FALSE
	);
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_forms.js', 'nexus', 'global' ) );

		/* If we don't have a value, set their country based on the HTTP headers */
		if ( !$this->value OR ( $this->value instanceof GeoLocation AND !$this->value->country ) )
		{
			$this->value = ( $this->value instanceof GeoLocation ) ? $this->value : new GeoLocation;
			if ( $defaultCountry = static::calculateDefaultCountry() )
			{
				$this->value->country = $defaultCountry;
			}
		}
		
		/* We need a HTML id */
		if ( !$this->htmlId )
		{
			$this->htmlId = md5( 'ips_checkbox_' . $this->name );
		}
		
		/* Display */
		return Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->businessAddress( $this->name, $this->value, Settings::i()->googleplacesautocomplete ? Settings::i()->google_maps_api_key : NULL, $this->options['minimize'], $this->options['requireFullAddress'], $this->htmlId, $this->options['vat'] );
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue(): mixed
	{
		/* Get the normal address stuff */
		$value = parent::getValue();
		
		/* Add in business stuff */
		if ( $value )
		{
			$name = $this->name;
			if( Request::i()->$name['type'] === 'business' )
			{
				$value->business = Request::i()->$name['business'];
				
				if ( $this->options['vat'] and in_array( $value->country, array('AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','GB') ) and Request::i()->$name['vat'] )
				{
					$value->vat = Request::i()->$name['vat'];
				}
				else
				{
					$value->vat = NULL;
				}
			}
			else
			{
				$value->business = NULL;
				$value->vat = NULL;
			}
		}
		
		/* Return */
		return $value;
	}
	
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		parent::validate();
		
		if( $this->value )
		{
			if ( $this->value->business === '' )
			{
				throw new DomainException('cm_business_name_required');
			}
			
			if ( $this->value->vat )
			{
				try
				{
					$response = Tax::validateVAT( $this->value->vat );
					if ( !$response )
					{
						throw new DomainException('cm_checkout_vat_invalid');
					}
					elseif ( $response['countryCode'] !== $this->value->country )
					{
						throw new DomainException('cm_checkout_vat_wrong_country');
					}
				}
				catch ( Exception $e )
				{
					Log::log( $e, 'vat-validation' );
					throw new DomainException('cm_checkout_vat_error');
				}
			}
		}
		
		return TRUE;
	}
}