<?php
/**
 * @brief		License Key API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Dec 2015
 */

namespace IPS\nexus\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\Response;
use IPS\nexus\Purchase\LicenseKey;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	License Key API
 */
class lkey extends Controller
{
	/**
	 * GET /nexus/lkey/{key}
	 * Get information about a specific purchase from its license key
	 *
	 * @param		string		$lkey			License key
	 * @throws		2X310/1		INVALID_KEY		The license key does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\nexus\Purchase
	 * @return Response
	 */
	public function GETitem( string $lkey ): Response
	{
		try
		{
			$licenseKey = LicenseKey::load( $lkey );
			$purchase = $licenseKey->purchase;
			if ( $this->member and !$purchase->canView( $this->member ) )
			{
				throw new OutOfRangeException;
			}
			
			return new Response( 200, $purchase->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException )
		{
			throw new Exception( 'INVALID_KEY', '2X332/1', 404 );
		}
	}
	
	/**
	 * POST /nexus/lkey/{key}
	 * Update custom fields for a purchase from its license key
	 *
	 * @apiclientonly
	 * @apiparam	object		customFields	Values for custom fields
	 * @param		string		$lkey			License key
	 * @throws		2X310/2		INVALID_KEY		The license key does not exist
	 * @apireturn		\IPS\nexus\Purchase
	 * @return Response
	 */
	public function POSTitem( string $lkey ): Response
	{
		try
		{	
			$licenseKey = LicenseKey::load( $lkey );
			$purchase = $licenseKey->purchase;
		}
		catch ( OutOfRangeException )
		{
			throw new Exception( 'INVALID_KEY', '2X332/2', 404 );
		}
		
		if ( isset( Request::i()->customFields ) )
		{
			$customFields = $purchase->custom_fields;
			foreach ( Request::i()->customFields as $k => $v )
			{
				$customFields[ $k ] = $v;
			}
			$purchase->custom_fields = $customFields;
		}
		
		$purchase->save();
		
		return new Response( 200, $purchase->apiOutput( $this->member ) );
	}
}