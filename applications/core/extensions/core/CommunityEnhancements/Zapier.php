<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		06 Feb 2020
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Api\Key;
use IPS\Application;
use IPS\Db;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Login;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class Zapier extends CommunityEnhancementsAbstract
{
	/**
	 * Get the permissions needed for the Zapier API key
	 *
	 * Since 4.7.1 we don't need to call any other methods while the upgrade, just adjust the permission here, everything else will be handled automatically.
	 *
	 * @return	array
	 */
	public static function apiKeyPermissions() : array
	{
		$return = array(
			'core/clubs/GETindex'				=> array( 'access' => TRUE ),
			'core/clubs/GETitem'				=> array( 'access' => TRUE ),
			'core/groups/GETindex'				=> array( 'access' => TRUE ),
			'core/hello/GETindex'				=> array( 'access' => TRUE ),
			'core/members/GETindex'				=> array( 'access' => TRUE ),
			'core/members/GETitem'				=> array( 'access' => TRUE ),
			'core/members/POSTindex'			=> array( 'access' => TRUE ),
			'core/members/POSTitem_secgroup'	=> array( 'access' => TRUE ),
			'core/members/DELETEitem_secgroup'	=> array( 'access' => TRUE ),
			'core/members/POSTitem'				=> array( 'access' => TRUE ),
			'core/webhooks/POSTindex'			=> array( 'access' => TRUE ),
			'core/webhooks/DELETEitem'			=> array( 'access' => TRUE ),
			'core/promotions/GETindex'			=> array( 'access' => TRUE ),
			'core/promotions/GETitem'			=> array( 'access' => TRUE ),
		);
		
		if ( Application::appIsEnabled('forums') )
		{
			$return['forums/forums/GETindex'] 	= array( 'access' => TRUE );
			$return['forums/forums/GETitem'] 	= array( 'access' => TRUE );
			$return['forums/topics/GETindex'] 	= array( 'access' => TRUE );
			$return['forums/topics/GETitem'] 	= array( 'access' => TRUE );
			$return['forums/topics/POSTindex'] 	= array( 'access' => TRUE );
			$return['forums/posts/GETindex'] 	= array( 'access' => TRUE );
			$return['forums/posts/GETitem'] 	= array( 'access' => TRUE );
			$return['forums/posts/POSTindex'] 	= array( 'access' => TRUE );
		}
		
		if ( Application::appIsEnabled('calendar') )
		{
			$return['calendar/calendars/GETindex'] 	= array( 'access' => TRUE );
			$return['calendar/calendars/GETitem'] 	= array( 'access' => TRUE );
			$return['calendar/events/GETindex'] 	= array( 'access' => TRUE );
			$return['calendar/events/GETitem'] 		= array( 'access' => TRUE );
			$return['calendar/events/POSTindex'] 	= array( 'access' => TRUE );
			$return['calendar/comments/GETindex'] 	= array( 'access' => TRUE );
			$return['calendar/comments/GETitem'] 	= array( 'access' => TRUE );
			$return['calendar/comments/POSTindex'] 	= array( 'access' => TRUE );
			$return['calendar/reviews/GETindex'] 	= array( 'access' => TRUE );
			$return['calendar/reviews/GETitem'] 	= array( 'access' => TRUE );
			$return['calendar/reviews/POSTindex'] 	= array( 'access' => TRUE );
		}
		
		if ( Application::appIsEnabled('downloads') )
		{
			$return['downloads/categories/GETindex'] 	= array( 'access' => TRUE );
			$return['downloads/categories/GETitem'] 	= array( 'access' => TRUE );
			$return['downloads/files/GETindex'] 		= array( 'access' => TRUE );
			$return['downloads/files/GETitem'] 			= array( 'access' => TRUE );
			$return['downloads/files/POSTindex'] 		= array( 'access' => TRUE );
			$return['downloads/comments/GETindex']	 	= array( 'access' => TRUE );
			$return['downloads/comments/GETitem'] 		= array( 'access' => TRUE );
			$return['downloads/comments/POSTindex'] 	= array( 'access' => TRUE );
			$return['downloads/reviews/GETindex'] 		= array( 'access' => TRUE );
			$return['downloads/reviews/GETitem'] 		= array( 'access' => TRUE );
			$return['downloads/reviews/POSTindex'] 		= array( 'access' => TRUE );
		}

		if ( Application::appIsEnabled('blog') )
		{
			$return['blog/categories/GETindex'] 	= array( 'access' => TRUE );
			$return['blog/categories/GETitem'] 		= array( 'access' => TRUE );
			$return['blog/blogs/GETindex'] 			= array( 'access' => TRUE );
			$return['blog/blogs/GETitem'] 			= array( 'access' => TRUE );
			$return['blog/blogs/POSTindex'] 		= array( 'access' => TRUE );
			$return['blog/entrycategories/GETindex'] 			= array( 'access' => TRUE );
			$return['blog/entrycategories/GETitem'] 			= array( 'access' => TRUE );
			$return['blog/entries/GETindex'] 		= array( 'access' => TRUE );
			$return['blog/entries/GETitem'] 		= array( 'access' => TRUE );
			$return['blog/entries/POSTindex'] 		= array( 'access' => TRUE );
			$return['blog/comments/GETindex']	 	= array( 'access' => TRUE );
			$return['blog/comments/GETitem'] 		= array( 'access' => TRUE );
			$return['blog/comments/POSTindex'] 		= array( 'access' => TRUE );
		}
		
		if ( Application::appIsEnabled('gallery') )
		{
			$return['gallery/categories/GETindex'] 	= array( 'access' => TRUE );
			$return['gallery/categories/GETitem'] 	= array( 'access' => TRUE );
			$return['gallery/albums/GETindex'] 		= array( 'access' => TRUE );
			$return['gallery/albums/GETitem'] 		= array( 'access' => TRUE );
			$return['gallery/images/GETindex'] 		= array( 'access' => TRUE );
			$return['gallery/images/GETitem'] 		= array( 'access' => TRUE );
			$return['gallery/images/POSTindex'] 	= array( 'access' => TRUE );
			$return['gallery/comments/GETindex']	= array( 'access' => TRUE );
			$return['gallery/comments/GETitem'] 	= array( 'access' => TRUE );
			$return['gallery/comments/POSTindex'] 	= array( 'access' => TRUE );
			$return['gallery/reviews/GETindex'] 	= array( 'access' => TRUE );
			$return['gallery/reviews/GETitem'] 		= array( 'access' => TRUE );
			$return['gallery/reviews/POSTindex'] 	= array( 'access' => TRUE );
		}
		
		if ( Application::appIsEnabled('cms') )
		{
			$return['cms/databases/GETindex'] 	= array( 'access' => TRUE );
			$return['cms/databases/GETitem'] 	= array( 'access' => TRUE );
			$return['cms/categories/GETindex'] 	= array( 'access' => TRUE );
			$return['cms/categories/GETitem'] 	= array( 'access' => TRUE );
			$return['cms/records/GETindex'] 	= array( 'access' => TRUE );
			$return['cms/records/GETitem'] 		= array( 'access' => TRUE );
			$return['cms/records/POSTindex'] 	= array( 'access' => TRUE );
			$return['cms/comments/GETindex']	= array( 'access' => TRUE );
			$return['cms/comments/GETitem'] 	= array( 'access' => TRUE );
			$return['cms/comments/POSTindex'] 	= array( 'access' => TRUE );
			$return['cms/reviews/GETindex'] 	= array( 'access' => TRUE );
			$return['cms/reviews/GETitem'] 		= array( 'access' => TRUE );
			$return['cms/reviews/POSTindex'] 	= array( 'access' => TRUE );
		}
		
		return $return;
	}

	/**
	 * @brief	Enhancement is enabled?
	 */
	public bool $enabled	= FALSE;

	/**
	 * @brief	IPS-provided enhancement?
	 */
	public bool $ips	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public bool $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public string $icon	= "zapier.png";
	
	/**
	 * Can we use this?
	 *
	 * @return	bool
	 */
	public static function isAvailable() : bool
	{
		return TRUE;
	}
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		if ( Settings::i()->zapier_api_key )
		{
			try
			{
				$apiKey = Key::load( Settings::i()->zapier_api_key );
				$this->enabled = (bool) json_decode( $apiKey->permissions, TRUE );
			}
			catch ( OutOfRangeException $e ) {}
		}
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$apiKey = Key::load( Settings::i()->zapier_api_key );
		
		$correctPermissions = json_encode( static::apiKeyPermissions() );
		if ( $apiKey->permissions != $correctPermissions )
		{
			$apiKey->permissions = $correctPermissions;
			$apiKey->save();
		}
		
		try
		{
			$this->testSettings();
		}
		catch ( DomainException $e )
		{
			Output::i()->error( $e->getMessage(), '3C414/2' );
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'api' )->zapier( $apiKey );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 * @throws	LogicException
	 */
	public function toggle( bool $enabled ) : void
	{
		$isNew = FALSE;
		try
		{
			$apiKey = Key::load( Settings::i()->zapier_api_key );
		}
		catch ( OutOfRangeException $e )
		{
			$isNew = TRUE;
			
			$apiKey = new Key;
			$apiKey->id = Login::generateRandomString( 32 );
		}
				
		if ( $enabled )
		{
			try
			{
				$this->testSettings();
			}
			catch ( DomainException $e )
			{
				Output::i()->error( $e->getMessage(), '3C414/1' );
			}
			
			$apiKey->permissions = json_encode( static::apiKeyPermissions() );
			Db::i()->update( 'core_api_webhooks', array( 'enabled' => 1 ), array( 'api_key=?', $apiKey->id ) );
		}
		else
		{
			$apiKey->permissions = json_encode( array() );
			Db::i()->update( 'core_api_webhooks', array( 'enabled' => 0 ), array( 'api_key=?', $apiKey->id ) );
		}
		
		$apiKey->allowed_ips = NULL;
		$apiKey->save();
		
		Settings::i()->changeValues( array( 'zapier_api_key' => $apiKey->id ) );
		
		if ( $isNew )
		{
			Lang::saveCustom( 'core', "core_api_name_{$apiKey->id}", "Zapier" );
			
			throw new DomainException;
		}
	}
	
	/**
	 * Test Settings
	 *
	 * @return	void
	 * @throws	DomainException
	 */
	protected function testSettings() : void
	{
		if ( !Settings::i()->use_friendly_urls or !Settings::i()->htaccess_mod_rewrite )
		{
			throw new DomainException( 'zapier_error_friendly_urls' );
		}

		$url = Url::external( rtrim( Settings::i()->base_url, '/' ) . '/api/core/hello' );
		try
		{
			if ( Request::i()->isCgi() )
			{
				$response = $url->setQueryString( 'key', 'test' )->request()->get()->decodeJson();
			}
			else
			{
				$response = $url->request()->login( 'test', '' )->get()->decodeJson();
			}
			if ( isset( $response['errorMessage'] ) AND $response['errorMessage'] == 'IP_ADDRESS_BANNED' )
			{
				throw new Exception;
			}
			
			if ( $response['errorMessage'] != 'INVALID_API_KEY' and $response['errorMessage'] != 'TOO_MANY_REQUESTS_WITH_BAD_KEY' )
			{
				throw new Exception;
			}
		}
		catch ( Exception $e )
		{
			throw new DomainException( 'zapier_error_api' );
		}
	}

	/**
	 * Give the Zapier REST Key all required permissions.
	 *
	 * @return void
	 */
	public static function rebuildRESTApiPermissions() : void
	{
		/* Rebuild Zapier REST API Key Permissions */
		if( Settings::i()->zapier_api_key )
		{
			try
			{
				$apiKey = Key::load( Settings::i()->zapier_api_key );

				$correctPermissions = json_encode( static::apiKeyPermissions() );
				if ( $apiKey->permissions != $correctPermissions )
				{
					$apiKey->permissions = $correctPermissions;
					$apiKey->save();
				}
			}
			catch ( OutOfRangeException $e ) {}
		}
	}
}