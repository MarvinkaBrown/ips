<?php
/**
 * @brief		License Key API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		01 Apr 2015
 */

use IPS\Dispatcher\External;
use IPS\nexus\Customer;
use IPS\nexus\Purchase\LicenseKey;
use IPS\Output;
use IPS\Request;
use const IPS\NEXUS_LKEY_API_ALLOW_IP_OVERRIDE;
use const IPS\NEXUS_LKEY_API_CHECK_IP;
use const IPS\NEXUS_LKEY_API_DISABLE;

define('REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';
External::i();

if ( NEXUS_LKEY_API_ALLOW_IP_OVERRIDE )
{
	$_SERVER['REMOTE_ADDR'] = isset( Request::i()->ip ) ? Request::i()->ip : $_SERVER['REMOTE_ADDR'];
}

/**
 * API Exception
 */
class ApiException extends Exception
{ }

/**
 * API class to verify license keys
 */
class Api
{
	/**
	 * Output Error
	 *
	 * @param	int		$code			Status code
	 * @param	string	$message		Status message
	 * @return	void
	 */
	public function error( int $code, string $message ) : void
	{
		Output::setCacheTime( false );
		Output::i()->sendOutput( json_encode( array( 'errorCode' => $code, 'errorMessage' => $message ) ), 400, 'application/json' );
	}
	
	/**
	 * Get key
	 *
	 * @param	bool	$validateIdentifier	Should the identifier be validated?
	 * @return	LicenseKey
	 * @throws ApiException
	 */
	protected function getKey( bool $validateIdentifier=TRUE ) : LicenseKey
	{
		try
		{	
			$key = LicenseKey::load( isset( Request::i()->key ) ? Request::i()->key : NULL );
			
			if ( $key->key !== Request::i()->key )
			{
				throw new ApiException( 'BAD_KEY_OR_ID', 105 );
			}
			
			if ( $validateIdentifier and $identifier = $this->getIdentifier( $key ) and ( !isset( Request::i()->identifier ) or $identifier != Request::i()->identifier ) )
			{
				throw new ApiException( 'BAD_KEY_OR_ID', 101 );
			}
			
			if ( !$key->active )
			{
				throw new ApiException( 'INACTIVE', 102 );
			}
			if ( $key->purchase->cancelled )
			{
				throw new ApiException( 'INACTIVE', 103 );
			}
			if ( !$key->purchase->active )
			{
				throw new ApiException( 'INACTIVE', 104 );
			}
						
			return $key;
		}
		catch (OutOfRangeException )
		{
			throw new ApiException( 'BAD_KEY_OR_ID', 101 );
		}
	}
	
	/**
	 * Get identifier
	 *
	 * @param	LicenseKey	$key	The license key
	 * @return	string|NULL
	 */
	protected function getIdentifier( LicenseKey $key ) : string|null
	{
		$identifier = NULL;
		switch ( $key->identifier )
		{
			case 'name':
				$identifier = Customer::load( $key->member )->cm_name;
				break;
				
			case 'email':
				$identifier = Customer::load( $key->member )->email;
				break;
				
			case 'username':
				$identifier = Customer::load( $key->member )->name;
				break;
		
			default:
				$cfields = $key->purchase->custom_fields;
				if ( isset( $cfields[ $key->identifier ] ) )
				{
					$identifier = $cfields[ $key->identifier ];
				}
				break;
		}
		return $identifier;
	}
	
	/**
	 * Activate
	 *
	 * @return	void
	 * @throws  ApiException
	 */
	public function activate() : void
	{
		$key = $this->getKey( FALSE );
		
		if ( $key->max_uses != -1 and $key->uses >= $key->max_uses )
		{
			throw new ApiException( 'MAX_USES', 201 );
		}
		
		$identifier = $this->getIdentifier( $key );
		$providedIdentifier = isset( Request::i()->identifier ) ? Request::i()->identifier : NULL;
		if ( isset( Request::i()->setIdentifier ) and Request::i()->setIdentifier )
		{
			if ( $identifier != $providedIdentifier )
			{
				if ( $identifier or in_array( $key->identifier, array( 'name', 'email', 'username' ) ) )
				{
					throw new ApiException( 'BAD_KEY_OR_ID', 101 );
				}
				else
				{
					$cfields = $key->purchase->custom_fields;
					$cfields[ $key->identifier ] = Request::i()->setIdentifier;
					$key->purchase->custom_fields = $cfields;
					$key->purchase->save();
				}
			}
		}
		elseif( $identifier != $providedIdentifier )
		{
			throw new ApiException( 'BAD_KEY_OR_ID', 101 );
		}
		
		if ( defined( 'NEXUS_LKEY_API_ALLOW_IP_OVERRIDE' ) )
		{
			$ip = $this->params['ip'] ?: $_SERVER['REMOTE_ADDR'];
		}
		else
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		$activateData = $key->activate_data;
		$k = empty( $activateData ) ? 0 : max( array_keys( $activateData ) );
		$k++;
		$activateData[ $k ] = array(
			'activated'		=> time(),
			'ip'			=> $ip,
			'last_checked'	=> 0,
			'extra'			=> isset( Request::i()->extra ) ? json_decode( Request::i()->extra ) : NULL,
		);
		
		$key->activate_data = $activateData;
		$key->uses++;
		$key->save();
		
		Customer::load( $key->member )->log( 'lkey', array( 'type' => 'activated', 'key' => $key->key, 'ps_id' => $key->purchase->id ), FALSE );

		Output::setCacheTime( false );
		Output::i()->sendOutput( json_encode( array( 'response' => 'OKAY', 'usage_id' => $k ) ), 200, 'application/json' );
	}
	
	/**
	 * Check
	 *
	 * @return	void
	 * @throws ApiException
	 */
	public function check() : void
	{
		try
		{
			$key = $this->getKey();
		}
		catch ( ApiException $e )
		{
			switch ( $e->getCode() )
			{
				case 102:
				case 103:
					Output::i()->sendOutput( json_encode( array( 'status' => 'INACTIVE' ) ), 200, 'application/json', Output::getCacheHeaders( time(), 360 ) );
				case 104:
					Output::i()->sendOutput( json_encode( array( 'status' => 'EXPIRED' ) ), 200, 'application/json', Output::getCacheHeaders( time(), 360 ) );
					
				default:
					throw $e;
			}
		}
		
		$activateData = $key->activate_data;
		if ( !isset( Request::i()->usage_id ) or !isset( $activateData[ Request::i()->usage_id ] ) )
		{
			throw new ApiException( 'BAD_USAGE_ID', 303 );
		}
		if ( NEXUS_LKEY_API_CHECK_IP and $activateData[ Request::i()->usage_id ]['ip'] != $_SERVER['REMOTE_ADDR'] )
		{
			throw new ApiException( 'BAD_IP', 304 );
		}
		
		$activateData[ Request::i()->usage_id ]['last_checked'] = time();
		$key->activate_data = $activateData;
		$key->save();
		
		Output::i()->sendOutput( json_encode( array( 'status' => 'ACTIVE', 'uses' => $key->uses, 'max_uses' => $key->max_uses ) ), 200, 'application/json', Output::getCacheHeaders( time(), 360 ) );
	}
	
	/**
	 * Get Information
	 *
	 * @return	void
	 * @throws ApiException
	 */
	public function info() : void
	{
		$key = $this->getKey();
		
		$children = array();
		foreach ( $key->purchase->children( NULL ) as $child )
		{
			$children[ $child->id ] = array(
				'id'		=> $child->id,
				'name'		=> $child->name,
				'app'		=> $child->app,
				'type'		=> $child->type,
				'item_id'	=> $child->item_id,
				'active'	=> $child->cancelled ? 0 : $child->active,
				'start'		=> $child->start,
				'expire'	=> $child->expire,
				'lkey'		=> $child->licenseKey() ? $child->licenseKey()->key : NULL,
			);
		}
		
		Output::i()->sendOutput( json_encode( array(
			'key'				=> $key->key,
			'identifier'		=> $this->getIdentifier( $key ),
			'generated'			=> $key->generated->getTimestamp(),
			'expires'			=> $key->purchase->expire ? $key->purchase->expire->getTimestamp() : NULL,
			'usage_data'		=> $key->activate_data,
			'purchase_id'		=> $key->purchase->id,
			'purchase_name'		=> $key->purchase->name,
			'purchase_pkg'		=> $key->purchase->item_id,
			'purchase_active'	=> $key->purchase->cancelled ? FALSE : $key->purchase->active,
			'purchase_start'	=> $key->purchase->start->getTimestamp(),
			'purchase_expire'	=> $key->purchase->expire ? $key->purchase->expire->getTimestamp() : NULL,
			'purchase_children'	=> $children,
			'customer_name'		=> Customer::load( $key->member )->cm_name,
			'customer_email'	=> Customer::load( $key->member )->email,
			'uses'				=> $key->uses,
			'max_uses'			=> $key->max_uses
		) ), 200, 'application/json', Output::getCacheHeaders( time(), 360 ) );
	}
	
	/**
	 * Update extra information
	 *
	 * @return	void
	 * @throws ApiException
	 */
	public function updateExtra() : void
	{
		$key = $this->getKey();
		
		$activateData = $key->activate_data;
		if ( !isset( Request::i()->usage_id ) or !isset( $activateData[ Request::i()->usage_id ] ) )
		{
			throw new ApiException( 'BAD_USAGE_ID', 303 );
		}
		if ( NEXUS_LKEY_API_CHECK_IP and $activateData[ Request::i()->usage_id ]['ip'] != $_SERVER['REMOTE_ADDR'] )
		{
			throw new ApiException( 'BAD_IP', 304 );
		}
						
		$activateData[ Request::i()->usage_id ]['extra'] = isset( Request::i()->extra ) ? json_decode( Request::i()->extra ) : NULL;
		$key->activate_data = $activateData;
		$key->save();

		Output::setCacheTime( false );
		Output::i()->sendOutput( json_encode( array( 'status' => 'OKAY' ) ), 200, 'application/json' );
	}
}

$api = new api;
if ( !NEXUS_LKEY_API_DISABLE )
{
	foreach ( array( 'activate', 'check', 'info', 'updateExtra' ) as $k )
	{
		if ( isset( Request::i()->$k ) )
		{
			try
			{
				$api->$k();
			}
			catch ( ApiException $e )
			{
				$api->error( $e->getMessage(), $e->getCode() );
			}
			catch ( Exception )
			{
				$api->error( 0, 'INTERNAL_ERROR' );
			}
		} 
	}
	$api->error( 0, 'BAD_METHOD' );
}
else
{
	$api->error( 0, 'API_DISABLED' );
}