<?php
/**
 * @brief		4.6.11 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		09 Feb 2022
 */

namespace IPS\core\setup\upg_106156;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Key;
use IPS\Api\OAuthClient;
use IPS\core\extensions\core\CommunityEnhancements\Zapier;
use IPS\Db;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.6.11 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * remove mobile app client
	 *
	 * @return    bool|array   If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		try
		{
			$client = OAuthClient::constructFromData( Db::i()->select( '*', 'core_oauth_clients', array( 'oauth_type=?', 'mobile' ) )->first() );
			$client->delete();
		}
		catch ( UnderflowException $e )
		{
		}



		/* Rebuild Zapier REST API Key Permissions */
		if( Settings::i()->zapier_api_key )
		{
			try
			{
				$apiKey = Key::load( Settings::i()->zapier_api_key );

				$correctPermissions = json_encode( Zapier::apiKeyPermissions() );
				if ( $apiKey->permissions != $correctPermissions )
				{
					$apiKey->permissions = $correctPermissions;
					$apiKey->save();
				}
			}
			catch ( OutOfRangeException $e )
			{

			}

		}


		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}