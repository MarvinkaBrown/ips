<?php
/**
 * @brief		Tax Rate Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Db;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\nexus\Form\StateSelect;
use IPS\Node\Model;
use IPS\Xml\DOMDocument;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Tax Rate Node
 */
class Tax extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'nexus_tax';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 't_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'order';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'tax_rates';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'nexus_tax_';
								
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
		'module'	=> 'payments',
		'all'		=> 'tax_manage',
	);
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$defaultVal = array( 'consumer' => 0, 'business' => 0, 'eu' => 0 );
		if ( $this->rate )
		{
			foreach ( json_decode( $this->rate, TRUE ) as $rate )
			{
				if ( $rate['locations'] === '*' )
				{
					if ( isset( $rate['consumer'] ) )
					{
						$defaultVal['consumer'] = $rate['consumer'];
						$defaultVal['business'] = $rate['business'];
						$defaultVal['eu'] = $rate['eu'];
					}
					else
					{
						$defaultVal['consumer'] = $rate['rate'];
						$defaultVal['business'] = $rate['rate'];
						$defaultVal['eu'] = $rate['rate'];
					}
				}
			}
		}
		
		$form->addTab( 'tax_settings' );
		$form->addHeader('tax_basic_settings');
		$form->add( new Translatable( 'tax_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_tax_{$this->id}" : NULL, 'placeholder' => Member::loggedIn()->language()->addToStack('tax_name_placeholder') ) ) );
		$form->add( new Radio( 'tax_rate_type', $this->type, TRUE, array(
			'options' => array(
				'single'	=> 'tax_rate_type_single',
				'business'	=> 'tax_rate_type_business',
				'eu'		=> 'tax_rate_type_eu',
			),
			'toggles' => array(
				'single'	=> array( 'tax_default_single', 'rates_single' ),
				'business'	=> array( 'tax_default_consumer', 'tax_default_business', 'rates_business'),
				'eu'		=> array( 'tax_default_consumer', 'tax_default_business', 'tax_default_eu', 'rates_eu' ),
			) 
		) ) );
		$form->addHeader('tax_default');
		$form->addMessage('tax_default_desc');
		$form->add( new Number( 'tax_default_single', $defaultVal['consumer'], TRUE, array( 'decimals' => 2 ), NULL, NULL, '%', 'tax_default_single' ) );
		$form->add( new Number( 'tax_default_consumer', $defaultVal['consumer'], TRUE, array( 'decimals' => 2 ), NULL, NULL, '%', 'tax_default_consumer' ) );
		$form->add( new Number( 'tax_default_business', $defaultVal['business'], TRUE, array( 'decimals' => 2 ), NULL, NULL, '%', 'tax_default_business' ) );
		$form->add( new Number( 'tax_default_eu', $defaultVal['eu'], TRUE, array( 'decimals' => 2 ), NULL, NULL, '%', 'tax_default_eu' ) );
		
		$form->addTab( 'tax_rates_tab' );
		$singleMatrix = new Matrix;
		$singleMatrix->squashFields = FALSE;
		$singleMatrix->columns = array(
			'tax_locations'	=> function( $key, $value, $data )
			{
				return new StateSelect( $key, $value, FALSE, array( 'options' => array( 'foo' ), 'multiple' => TRUE ) );
			},
			'tax_rate' => function( $key, $value, $data )
			{
				return new Number( $key, $value, FALSE, array( 'decimals' => 2 ), NULL, NULL, '%' );
			}
		);
		$doubleMatrix = new Matrix;
		$doubleMatrix->squashFields = FALSE;
		$doubleMatrix->columns = array(
			'tax_locations'	=> function( $key, $value, $data )
			{
				return new StateSelect( $key, $value, FALSE, array( 'options' => array( 'foo' ), 'multiple' => TRUE ) );
			},
			'tax_rate_consumer' => function( $key, $value, $data )
			{
				return new Number( $key, $value, FALSE, array( 'decimals' => 2 ), NULL, NULL, '%' );
			},
			'tax_rate_business' => function( $key, $value, $data )
			{
				return new Number( $key, $value, FALSE, array( 'decimals' => 2 ), NULL, NULL, '%' );
			},
		);
		$tripleMatrix = new Matrix;
		$tripleMatrix->squashFields = FALSE;
		$tripleMatrix->columns = array(
			'tax_locations'	=> function( $key, $value, $data )
			{
				return new StateSelect( $key, $value, FALSE, array( 'options' => array( 'foo' ), 'multiple' => TRUE ) );
			},
			'tax_rate_consumer' => function( $key, $value, $data )
			{
				return new Number( $key, $value, FALSE, array( 'decimals' => 2 ), NULL, NULL, '%' );
			},
			'tax_rate_business' => function( $key, $value, $data )
			{
				return new Number( $key, $value, FALSE, array( 'decimals' => 2 ), NULL, NULL, '%' );
			},
			'tax_rate_eu' => function( $key, $value, $data )
			{
				return new Number( $key, $value, FALSE, array( 'decimals' => 2 ), NULL, NULL, '%' );
			},
		);
		$rates = $this->rate ? json_decode( $this->rate, TRUE ) : [];
		if ( is_array( $rates ) )
		{
			foreach ( $rates as $rate )
			{
				if ( $rate['locations'] !== '*' )
				{
					$singleMatrix->rows[] = array(
						'tax_locations'			=> $rate['locations'],
						'tax_rate'				=> $rate['consumer'] ?? $rate['rate'],
					);
					$doubleMatrix->rows[] = array(
						'tax_locations'			=> $rate['locations'],
						'tax_rate_consumer'		=> $rate['consumer'] ?? $rate['rate'],
						'tax_rate_business'		=> $rate['business'] ?? $rate['rate'],
					);
					$tripleMatrix->rows[] = array(
						'tax_locations'			=> $rate['locations'],
						'tax_rate_consumer'		=> $rate['consumer'] ?? $rate['rate'],
						'tax_rate_business'		=> $rate['business'] ?? $rate['rate'],
						'tax_rate_eu'			=> $rate['eu'] ?? $rate['rate'],
					);
				}
			}
		}
		
		$form->addMatrix( 'rates_single', $singleMatrix );
		$form->addMatrix( 'rates_business', $doubleMatrix );
		$form->addMatrix( 'rates_eu', $tripleMatrix );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{		
		$rates = array();
		
		$values['type'] = $values['tax_rate_type'];
		unset( $values['tax_rate_type'] );
		
		$ratesToUse = $values[ 'rates_' . $values['type'] ];
		foreach ( $ratesToUse as $rate )
		{
			if ( $rate['tax_locations'] )
			{
				if ( isset( $rate['tax_rate'] ) )
				{
					$rates[] = array(
						'locations' => $rate['tax_locations'],
						'rate'		=> $rate['tax_rate'],
					);
				}
				else
				{
					$rates[] = array(
						'locations' => $rate['tax_locations'],
						'consumer'	=> $rate['tax_rate_consumer'],
						'business'	=> $rate['tax_rate_business'] ?? $rate['tax_rate_consumer'],
						'eu'			=> $rate['tax_rate_eu'] ?? ($rate['tax_rate_business'] ?? $rate['tax_rate_consumer']),
					);
				}
			}
		}
		if ( $values['type'] === 'single' )
		{
			$rates[] = array(
				'locations' => '*',
				'rate'		=> $values['tax_default_single'],
			);
		}
		else
		{
			$rates[] = array(
				'locations' => '*',
				'consumer'	=> $values['tax_default_consumer'],
				'business'	=> ( in_array( $values['type'], array( 'business', 'eu' ) ) ) ? $values['tax_default_business'] : $values['tax_default_consumer'],
				'eu'		=> ( $values['type'] === 'eu' ) ? $values['tax_default_eu'] : ( ( $values['type'] === 'business' ) ? $values['tax_default_business'] : $values['tax_default_consumer'] )
			);
		}
		unset( $values['rates_single'] );
		unset( $values['rates_business'] );
		unset( $values['rates_eu'] );
		unset( $values['tax_default_single'] );
		unset( $values['tax_default_consumer'] );
		unset( $values['tax_default_business'] );
		unset( $values['tax_default_eu'] );
		
		$values = array_merge( array( 'rate' => json_encode( $rates ) ), $values );

		if( isset( $values['tax_name'] ) )
		{
			$name = $values['tax_name'];
			unset( $values['tax_name'] );
			$this->save();
			Lang::saveCustom( 'nexus', "nexus_tax_{$this->id}", $name );
		}

		return $values;
	}
	
	/**
	 * [Node] Delete
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		Db::i()->update( 'nexus_packages', array( 'p_tax' => 0 ), array( "p_tax=?", $this->_id ) );
		
		parent::delete();
	}
	
	/**
	 * Get rate
	 *
	 * For example, if the rate for the location is 10%, will return 0.1
	 *
	 * @param	GeoLocation|NULL	$billingAddress	The billing address
	 * @return	mixed	Result after running through number_format, which is a float represented as a string
	 */
	public function rate( ?GeoLocation $billingAddress = NULL ) : mixed
	{
		$type = 'consumer';
		if ( isset( $billingAddress->business ) and $billingAddress->business )
		{
			$type = $billingAddress->vat ? 'eu' : 'business';
		}
				
		$defaultVal = 0;
		foreach ( json_decode( $this->rate, TRUE ) as $rate )
		{
			if ( $rate['locations'] === '*' )
			{
				$defaultVal = $rate[$type] ?? $rate['rate'];
			}
			elseif ( $billingAddress and isset( $rate['locations'][ $billingAddress->country ] ) )
			{
				if ( $rate['locations'][ $billingAddress->country ] === '*' or in_array( $billingAddress->region, $rate['locations'][ $billingAddress->country ] ) )
				{
					return number_format( ( ($rate[$type] ?? $rate['rate']) / 100 ), 5, '.', '' );
				}
			}
		}

		return number_format( ( $defaultVal / 100 ), 5, '.', '' );
	}
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse		int		id		ID Number
	 * @apiresponse		string	name	Name
	 */
	public function apiOutput( ?Member $authorizedMember = NULL ): array
	{
		return array(
			'id'	=> $this->id,
			'name'	=> $this->_title
		);
	}
	
	/**
	 * Validate a VAT number
	 * If valid, response will be an array as returned by EU Commission's VIES with elements "countryCode", "vatNumber", "name" (except for Spain) and "address" (except for Spain)
	 *
	 * @param	string	$vatNumber	The VAT number
	 * @return	array|null
	 * @throws	Exception
	 */
	public static function validateVAT( string $vatNumber ) : array|null
	{
		/* Have we cached the response? */
		$vatNumber = mb_strtoupper( preg_replace( '/[^A-Z0-9]/', '', $vatNumber ) );
		$cacheKey = 'vatValidate-' . $vatNumber;
		try
		{
			return Cache::i()->getWithExpire( $cacheKey, TRUE );
		}
		catch( OutOfRangeException ){}
		
		/* Split up the number */
		$countryCode = mb_substr( $vatNumber, 0, 2 );
		$vatNumberWithoutCountryCode = mb_substr( $vatNumber, 2 );
		
		/* Construct the XML (We've alreasy stripped non-alphanumeric characters so it's fine to just drop the user-supplied input into the XML) */
		$xml = <<<XML
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns1="urn:ec.europa.eu:taxud:vies:services:checkVat:types" xmlns:impl="urn:ec.europa.eu:taxud:vies:services:checkVat"><soap:Header></soap:Header><soap:Body><tns1:checkVat xmlns:tns1="urn:ec.europa.eu:taxud:vies:services:checkVat:types" xmlns="urn:ec.europa.eu:taxud:vies:services:checkVat:types"><tns1:countryCode>{$countryCode}</tns1:countryCode><tns1:vatNumber>{$vatNumberWithoutCountryCode}</tns1:vatNumber></tns1:checkVat></soap:Body></soap:Envelope>
XML;
		/* Send the request */
		$request = Url::external('https://ec.europa.eu/taxation_customs/vies/services/checkVatService')->request()->setHeaders( [ 'Content-Type' => 'text/xml' ] );
		$response = $request->post( $xml );

		if( $response->httpResponseCode != 200 )
		{
			throw new Exception;
		}
		
		/* Interpret */
		$responseArray = array();
		$doc = new DOMDocument;
		$doc->loadXML( DOMDocument::wrapHtml( (string) $response ) );
		
		$data = iterator_to_array( $doc->getElementsByTagName( 'checkVatResponse' ) );
		
		if ( isset( $data[0] ) AND count( $data[0]->childNodes ) )
		{
			foreach( $data[0]->childNodes AS $child )
			{
				$responseArray[ $child->localName ] = $child->nodeValue;
			}
		}
		
		/* Cache and return */
		if ( isset( $responseArray['valid'] ) and $responseArray['valid'] === 'true' )
		{
			Cache::i()->storeWithExpire( $cacheKey, $responseArray, DateTime::create()->add( new DateInterval( 'P7D' ) ), TRUE );
			return $responseArray;
		}
		else
		{
			Cache::i()->storeWithExpire( $cacheKey, NULL, DateTime::create()->add( new DateInterval( 'PT10S' ) ), TRUE );
			return NULL;
		}
	}
}