<?php
/**
 * @brief		MaxMind Response
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		07 Mar 2014
 */

namespace IPS\nexus\Fraud\MaxMind;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * MaxMind Response
 */
class Response
{
	/**
	 * @brief	Data
	 */
	protected array $data = array();
	
	/**
	 * Constructor
	 *
	 * @param	string|NULL	$data	Data from MaxMind
	 * @return	void
	 */
	public function __construct( ?string $data = NULL )
	{
		if ( $data )
		{
			$this->data = json_decode( $data, true );
		}
	}
	
	/**
	 * Get data
	 *
	 * @param	string	$key	Key
	 * @return	mixed
	 */
	public function __get( string $key )
	{
		if ( isset( $this->data[ $key ] ) )
		{
			return $this->data[ $key ];
		}
		/* They changed their API, this is a less fragile and backwards compatible way to do this */
		else if( $key == 'riskScore' and isset( $this->data[ 'risk_score' ] ) )
		{
			return $this->data[ 'risk_score' ];
		}
		return NULL;
	}
	
	/**
	 * Build from JSON
	 *
	 * @param	string	$json	JSON data
	 * @return    static
	 */
	public static function buildFromJson( string $json ) : static
	{
		$obj = new static;
		$obj->data = json_decode( $json, TRUE );
		return $obj;
	}
	
	/**
	 * JSON encoded
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return json_encode( $this->data );
	}
	
	/**
	 * proxyScore as percentage
	 *
	 * @return	int
	 */
	public function proxyScorePercentage() : int
	{
		return ( 100 - 10 ) / 3 * $this->proxyScore + ( $this->proxyScore > 3 ? ( 10 * ( $this->proxyScore - 3 ) ) : 0 );
	}

	/**
	 * @brief minFraud error codes
	 * @see https://dev.maxmind.com/minfraud/#Error_Reporting
	 */
	protected static array $responseErrors =  array( 'JSON_INVALID', 'REQUEST_INVALID', 'AUTHORIZATION_INVALID', 'ACCOUNT_ID_REQUIRED', 'LICENSE_KEY_REQUIRED', 'INSUFFICIENT_FUNDS', 'PERMISSION_REQUIRED' );

	/**
	 * Does the response include an error?
	 *
	 * @return bool
	 */
	public function error() : bool
	{
		if ( $this->error AND in_array( $this->code, static::$responseErrors ) )
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Does the response include a warning?
	 *
	 * @return bool
	 */
	public function warning() : bool
	{
		if ( $this->error AND !in_array($this->code, static::$responseErrors ) )
		{
			return TRUE;
		}
		return FALSE;
	}
}