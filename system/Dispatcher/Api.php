<?php
/**
 * @brief		API Dispatcher
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 Dec 2015
 */

namespace IPS\Dispatcher;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use IPS\Api\Exception as ApiException;
use IPS\Api\Key;
use IPS\Api\OAuthClient;
use IPS\Application;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function intval;
use const IPS\OAUTH_REQUIRES_HTTPS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	API Dispatcher
 */
class Api extends Dispatcher
{
	/**
	 * @brief Controller Location
	 */
	public string $controllerLocation = 'api';
	
	/**
	 * @brief Path
	 */
	public ?string $path = NULL;
	
	/**
	 * @brief Raw API Key
	 */
	public ?string $rawApiKey = NULL;
	
	/**
	 * @brief Raw Access Token
	 */
	public ?string $rawAccessToken = NULL;
	
	/**
	 * @brief API Key Object
	 */
	public ?Key $apiKey = NULL;
	
	/**
	 * @brief Access Token Details
	 */
	public ?array $accessToken = NULL;
	
	/**
	 * @brief Language
	 */
	public ?Lang $language = NULL;

	/**
	 * Can the Response be cached?
	 *
	 * @var bool
	 */
	public bool $cacheResponse = TRUE;
	
	/**
	 * Init
	 *
	 * @return	void
	 * @throws	DomainException
	 */
	public function init() : void
	{
		try
		{
			/* Get the path */
			$this->_setPath();
			
			/* Check our IP address isn't banned */
			$this->_checkIpAddressIsAllowed();
			
			/* Set our credentials */
			$this->_setRawCredentials();
			if ( $this->rawAccessToken )
			{
				$this->_setAccessToken();
				if ( !$this->accessToken['scope'] or !json_decode( $this->accessToken['scope'] ) )
				{
					throw new ApiException( 'NO_SCOPES', '3S290/B', 401, 'insufficient_scope' );
				}
			}
			elseif ( $this->rawApiKey )
			{
				$this->_setApiKey();
			}
			else
			{
				throw new ApiException( 'NO_API_KEY', '2S290/6', 401 );
			}
			
			/* Set other data */
			$this->_setLanguage();

			/* We don't want to cache any output for Zapier Requests */
			if( Request::i()->isZapier() )
			{
				$this->cacheResponse = FALSE;
			}

		}
		catch ( ApiException $e )
		{
			/* Build response */
			$response = json_encode( array( 'errorCode' => $e->exceptionCode, 'errorMessage' => $e->getMessage() ), JSON_PRETTY_PRINT );
			
			/* Do we need to log this? */
			if ( $this->rawApiKey !== 'test' and in_array( $e->exceptionCode, array( '2S290/8', '2S290/B', '3S290/7', '3S290/9' ) ) )
			{
				$this->_log( (array)$response, $e->getCode(), in_array($e->exceptionCode, array('3S290/7', '3S290/9', '3S290/B')));
			}
			
			/* Output */
			$this->_respond($response, $e->getCode(), $e->oauthError);
		}
	}
	
	/**
	 * Set the path and request data
	 *
	 * @return	void
	 */
	protected function _setPath() : void
	{
		/* Decode URL */
		if ( Settings::i()->use_friendly_urls and Settings::i()->htaccess_mod_rewrite and mb_substr( Request::i()->url()->data[ Url::COMPONENT_PATH ], -14 ) !== '/api/index.php' )
		{
			/* We are using Mod Rewrite URL's, so look in the path */
			$this->path = mb_substr( Request::i()->url()->data[ Url::COMPONENT_PATH ], mb_strpos( Request::i()->url()->data[ Url::COMPONENT_PATH ], '/api/' ) + 5 );
			
			/* nginx won't convert the 'fake' query string to $_GET params, so do this now */
			if ( ! empty( Request::i()->url()->data[ Url::COMPONENT_QUERY ] ) )
			{
				parse_str( Request::i()->url()->data[ Url::COMPONENT_QUERY ], $params );
				foreach ( $params as $k => $v )
				{
					if ( ! isset( Request::i()->$k ) )
					{
						Request::i()->$k = $v;
					}
				}
			}
		}
		else
		{
			/* Otherwise we are not, so we need the query string instead, which is actually easier */
			$this->path = Request::i()->url()->data[ Url::COMPONENT_QUERY ];

			/* However, if we passed any actual query string arguments, we need to strip those */
			if( mb_strpos( $this->path, '&' ) )
			{
				$this->path = mb_substr( $this->path, 0, mb_strpos( $this->path, '&' ) );
			}
		}
	}
	
	/**
	 * Work out if this is an API Key request, or an OAuth request
	 *
	 * @note	OAuth requires Access Tokens only be transmitted over TLS, so if the request isn't secure, we ignore OAuth credentials
	 * @return	void
	 */
	public function _setRawCredentials() : void
	{
		if ( $authorizationHeader = Request::i()->authorizationHeader() )
		{
			if ( mb_substr( $authorizationHeader, 0, 7 ) === 'Bearer ' and ( !OAUTH_REQUIRES_HTTPS or Request::i()->isSecure() ) )
			{
				$this->rawAccessToken = mb_substr( $authorizationHeader, 7 );
			}
			else
			{
				$exploded = explode( ':', base64_decode( mb_substr( $authorizationHeader, 6 ) ) );
				if ( isset( $exploded[0] ) )
				{
					$this->rawApiKey = $exploded[0];
				}
			}
		}
	}
	
	/**
	 * Check the IP Address isn't banned
	 *
	 * @return	void
	 * @throws    ApiException
	 */
	protected function _checkIpAddressIsAllowed() : void
	{
		/* Check the IP address is banned */
		if ( Request::i()->ipAddressIsBanned() )
		{
			throw new ApiException( 'IP_ADDRESS_BANNED', '1S290/A', 403 );
		}
		
		/* If we have tried to access the API with a bad key more than 10 times, ban the IP address */
		if ( Db::i()->select( 'COUNT(*)', 'core_api_logs', array( 'ip_address=? AND is_bad_key=1', Request::i()->ipAddress() ) )->first() > 10 )
		{
			/* Remove the flag from these logs so that if the admin unbans the IP we aren't immediately banned again */
			Db::i()->update( 'core_api_logs', array( 'is_bad_key' => 0 ), array( 'ip_address=?', Request::i()->ipAddress() ) );
			
			/* Then insert the ban... */
			Db::i()->insert( 'core_banfilters', array(
				'ban_type'		=> 'ip',
				'ban_content'	=> Request::i()->ipAddress(),
				'ban_date'		=> time(),
				'ban_reason'	=> 'API',
			) );
			unset( Store::i()->bannedIpAddresses );
			
			/* And throw an error */
			throw new ApiException( 'IP_ADDRESS_BANNED', '1S290/C', 403 );
		}
		
		/* If we have tried to access the API with a bad key more than once in the last 5 minutes, throw an error to prevent brute-forcing */
		if ( Db::i()->select( 'COUNT(*)', 'core_api_logs', array( 'ip_address=? AND is_bad_key=1 AND date>?', Request::i()->ipAddress(), DateTime::create()->sub( new DateInterval( 'PT5M' ) )->getTimestamp() ) )->first() > 1 )
		{
			throw new ApiException( 'TOO_MANY_REQUESTS_WITH_BAD_KEY', '1S290/D', 429 );
		}
	}
	
	/**
	 * Set API Key
	 *
	 * @return	void
	 */
	public function _setApiKey() : void
	{
		try
		{
			$this->apiKey = Key::load( $this->rawApiKey );
			
			if ( $this->apiKey->allowed_ips and !in_array( Request::i()->ipAddress(), explode( ',', $this->apiKey->allowed_ips ) ) )
			{
				throw new ApiException( 'IP_ADDRESS_NOT_ALLOWED', '2S290/8', 403 );
			}
		}
		catch ( OutOfRangeException $e )
		{
			throw new ApiException( 'INVALID_API_KEY', '3S290/7', 401 );
		}
	}
	
	/**
	 * Set Access Token
	 *
	 * @return	void
	 */
	public function _setAccessToken() : void
	{
		try
		{
			$this->accessToken = OAuthClient::accessTokenDetails( $this->rawAccessToken );
		}
		catch ( UnderflowException $e )
		{
			throw new ApiException( 'INVALID_ACCESS_TOKEN', '3S290/9', 401, 'invalid_token' );
		}
	}
	
	/**
	 * Set Language
	 *
	 * @return	Lang
	 */
	public function _setLanguage() : Lang
	{
		try
		{
			if ( isset( $_SERVER['HTTP_X_IPS_LANGUAGE'] ) )
			{
				$this->language = Lang::load( intval( $_SERVER['HTTP_X_IPS_LANGUAGE'] ) );
			}
			else
			{
				$this->language = Lang::load( Lang::defaultLanguage() );
			}

			return $this->language;
		}
		catch ( OutOfRangeException $e )
		{
			throw new ApiException( 'INVALID_LANGUAGE', '2S290/9', 400, 'invalid_request' );
		}
	}
	
	/**
	 * Run
	 *
	 * @return	void
	 */
	public function run() : void
	{
		$shouldLog = FALSE;
		try
		{
			/* Work out the app and controller. Both can only be alphanumeric - prevents include injections */
			$pathBits = array_filter( explode( '/', $this->path ) );
			$app = array_shift( $pathBits );
			if ( !preg_match( '/^[a-z0-9]+$/', $app ) )
			{
				throw new ApiException( 'INVALID_APP', '3S290/3', 400 );
			}
			$controller = array_shift( $pathBits );
			if ( !preg_match( '/^[a-z0-9]+$/', $controller ) )
			{
				throw new ApiException( 'INVALID_CONTROLLER', '3S290/4', 400 );
			}
			
			/* Load the app */
			try
			{
				$app = Application::load( $app );
			}
			catch ( OutOfRangeException $e )
			{
				throw new ApiException( 'INVALID_APP', '2S290/1', 404 );
			}
				
			/* Check it's enabled */
			if ( !$app->enabled )
			{
				throw new ApiException( 'APP_DISABLED', '1S290/2', 503 );
			}
			
			/* Get the controller */
			$class = 'IPS\\' . $app->directory . '\\api\\' . $controller;
			if ( !class_exists( $class ) )
			{
				throw new ApiException( 'INVALID_CONTROLLER', '2S290/5', 404 );
			}
			
			/* Run it */
			$controller = new $class( $this->apiKey, $this->accessToken );
			$response = $controller->execute( $pathBits, $shouldLog );

			/* Store if we need to mask anything in logs */
			$this->parametersToMask = ( $controller->methodCalled AND isset( $controller->parametersToMask[ $controller->methodCalled ] ) ) ? $controller->parametersToMask[ $controller->methodCalled ] : NULL;
			
			/* Send Output */
			$output = $response->getOutput();
			$this->language->parseOutputForDisplay( $output );

			$this->_respond(json_encode($output, JSON_PRETTY_PRINT), $response->httpCode, NULL, $shouldLog);
		}
		catch ( ApiException $e )
		{
			$this->_respond(json_encode(array('errorCode' => $e->exceptionCode, 'errorMessage' => $e->getMessage()), JSON_PRETTY_PRINT), $e->getCode(), $e->oauthError, $shouldLog);
		}
		catch ( \Exception $e )
		{
			Log::log( $e, 'api' );
			
			$this->_respond(json_encode(array('errorCode' => 'EX' . $e->getCode(), 'errorMessage' => \IPS\IN_DEV ? $e->getMessage() : 'UNKNOWN_ERROR'), JSON_PRETTY_PRINT), 500);
		}
	}

	/**
	 * @brief	Parameters to mask in logs per the controller
	 */
	protected ?array $parametersToMask = NULL;
	
	/**
	 * Log
	 *
	 * @param array|string $response			Response to output
	 * @param int $httpResponseCode	HTTP Response Code
	 * @param bool $isBadKey			Was the ley invalid?
	 * @return	void
	 */
	protected function _log( array|string $response, int $httpResponseCode, bool $isBadKey=FALSE ) : void
	{
		try
		{
			$_requestData = $_REQUEST;

			if( $this->parametersToMask AND count( $this->parametersToMask ) )
			{
				foreach( $_requestData as $k => $v )
				{
					if( in_array( $k, $this->parametersToMask ) )
					{
						$_requestData[ $k ] = '******';
					}
				}
			}

			Db::i()->insert( 'core_api_logs', array(
				'endpoint'			=> $this->path,
				'method'			=> $_SERVER['REQUEST_METHOD'],
				'api_key'			=> $this->rawApiKey,
				'ip_address'		=> Request::i()->ipAddress(),
				'request_data'		=> json_encode( $_requestData, JSON_PRETTY_PRINT ),
				'response_code'		=> $httpResponseCode,
				'response_output'	=> $response,
				'date'				=> time(),
				'is_bad_key'		=> $isBadKey,
				'client_id'			=> $this->accessToken ? $this->accessToken['client_id'] : NULL,
				'member_id'			=> $this->accessToken ? $this->accessToken['member_id'] : NULL,
				'access_token'		=> $this->rawAccessToken,
			) );
		}
		catch ( Exception $e ) {}
	}
	
	/**
	 * Output response
	 *
	 * @param string $response			Response to output
	 * @param int $httpResponseCode	HTTP Response Code
	 * @param string|null $oauthError			OAuth error
	 * @param bool $log				Whether or not to log the response
	 * @return	void
	 */
	protected function _respond( string $response, int $httpResponseCode, string $oauthError=NULL, bool $log=FALSE ) : void
	{
		$headers = $this->canBeCached() ? Output::getCacheHeaders( time(), 60 ) : Output::getNoCacheHeaders();

		if ( $this->rawAccessToken and $oauthError )
		{
			$headers['WWW-Authenticate'] = "Bearer error=\"{$oauthError}\"";
		}
		
		if ( $log )
		{
			$this->_log($response, $httpResponseCode);
		}
		
		Output::i()->sendOutput( $response, $httpResponseCode, 'application/json', $headers );
	}

	/**
	 * Can this response be cached?
	 *
	 * @return bool
	 */
	protected function canBeCached() : bool
	{
		return $this->cacheResponse AND mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' AND !$this->rawAccessToken;
	}
	
	/**
	 * Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		
	}
}