<?php
/**
 * @brief		HTTP Request Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Data\Store;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Http\Url\Exception;
use IPS\Http\Url\Internal;
use IPS\Patterns\Singleton;
use function defined;
use function function_exists;
use function in_array;
use function intval;
use function is_array;
use function mb_strtolower;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * HTTP Request Class
 */
class Request extends Singleton
{
	/**
	 * @brief	Singleton Instance
	 */
	protected static ?Singleton $instance = NULL;
	
	/**
	 * @brief	Cookie data
	 */
	public array $cookie = array();
	
	/**
	 * Constructor
	 *
	 * @return	void
	 * @note	We do not unset $_COOKIE as it is needed by session handling
	 */
	public function __construct()
	{
		if ( isset( $_SERVER['REQUEST_METHOD'] ) AND $_SERVER['REQUEST_METHOD'] == 'PUT' )
		{
			parse_str( file_get_contents('php://input'), $params );
			$this->parseIncomingRecursively( $params );
		}
		else
		{
			$this->parseIncomingRecursively( $_GET );
			$this->parseIncomingRecursively( $_POST );
		}

		array_walk_recursive( $_COOKIE, array( $this, 'clean' ) );

		/* If we have a cookie prefix, we have to strip it first */
		if( COOKIE_PREFIX !== NULL )
		{
			foreach( $_COOKIE as $key => $value )
			{
				if( mb_strpos( $key, COOKIE_PREFIX) === 0 )
				{
					$this->cookie[ preg_replace( "/^" . COOKIE_PREFIX . "(.+?)/", "$1", $key ) ]	= $value;
				}
			}
		}
		else
		{
			$this->cookie = $_COOKIE;
		}
	}

	/**
	 * Parse Incoming Data
	 *
	 * @param array $data	Data
	 * @return	void
	 */
	protected function parseIncomingRecursively( array $data ) : void
	{
		foreach( $data as $k => $v )
		{
			if ( is_array( $v ) )
			{
				array_walk_recursive( $v, array( $this, 'clean' ) );
			}
			else
			{
				$this->clean( $v, $k );
			}

			/* We used to call $this->$k = $v but that resulted in breaking our cookie array if a &cookie=1 parameter was passed in the URL */
			$this->data[ $k ] = $v;
		}
	}
	
	/**
	 * Clean Value
	 *
	 * @param	mixed	$v	Value
	 * @param	mixed	$k	Key
	 * @return	void
	 */
	protected function clean( mixed &$v, mixed $k ) : void
	{
		/* Remove NULL bytes and the RTL control byte */
		$v = str_replace( array( "\0", "\u202E" ), '', $v );
	}
	
	/**
	 * Get value from array
	 *
	 * @param string $key	Key with square brackets (e.g. "foo[bar]")
	 * @return	mixed	Value
	 */
	public function valueFromArray( string $key ): mixed
	{
		$array = $this->data;
		
		while ( $pos = mb_strpos( $key, '[' ) )
		{
			preg_match( '/^(.+?)\[([^\]]+?)?\](.*)?$/', $key, $matches );
			
			if ( !array_key_exists( $matches[1], $array ) )
			{
				return NULL;
			}
				
			$array = $array[ $matches[1] ];
			$key = $matches[2] . $matches[3];
		}
		
		if ( !isset( $array[ $key ] ) )
		{
			return NULL;
		}
				
		return $array[ $key ];
	}
	
	/**
	 * Get an object that can be cast to a string to get the value for a given input
	 *
	 * This can be used in place of passing strings as arguments to functions where it is
	 * desirable to avoid the value being included in a backtrace if an error occurs.
	 *
	 * @param string $k
	 * @return	object
	 */
	public function protect( string $k ): object
	{
		return eval('return new class
		{
			public function __toString()
			{
				return \IPS\Request::i()->' . $k . ' ?? \'\';
			}
		};' );
	}
	
	/**
	 * Is this an AJAX request?
	 *
	 * @return	bool
	 */
	public function isAjax(): bool
	{
		return ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) and $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' );
	}

	/**
	 * Is this an SSL/Secure request?
	 *
	 * @return	bool
	 * @note	A common technique to check for SSL is to look for $_SERVER['SERVER_PORT'] == 443, however this is not a correct check. Nothing requires SSL to be on port 443, or http to be on port 80.
	 */
	public function isSecure(): bool
	{
		if( !empty( $_SERVER['HTTPS'] ) AND ( mb_strtolower( $_SERVER['HTTPS'] ) == 'on' or $_SERVER['HTTPS'] === '1' ) )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) AND mb_strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) == 'https' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) AND mb_strtolower( $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) == 'https' )
		{
			return TRUE;
		}
		else if ( !empty( $_SERVER['HTTP_X_FORWARDED_HTTPS'] ) AND mb_strtolower( $_SERVER['HTTP_X_FORWARDED_HTTPS'] ) == 'https' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_FRONT_END_HTTPS'] ) AND mb_strtolower( $_SERVER['HTTP_FRONT_END_HTTPS'] ) == 'on' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_SSLSESSIONID'] ) )
		{
			return TRUE;
		}

		return FALSE;
	}
	
	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url	= NULL;

	/**
	 * Get current URL
	 *
	 * @return Url|string|null
	 */
	function url(): Url|string|null
	{
		if( $this->_url === NULL )
		{
			$url = $this->isSecure() ? 'https' : 'http';
			$url .= '://';
			$ruleMatched = FALSE;
			
			/* Nginx uses HTTP_X_FORWARDED_SERVER. @see <a href='https://plone.lucidsolutions.co.nz/web/reverseproxyandcache/setting-nginx-http-x-forward-headers-for-reverse-proxy'>Nginx Reverse Proxy</a> */
			if ( !CIC )
			{
				if ( !empty( $_SERVER['HTTP_X_FORWARDED_SERVER'] ) )
				{
					if ( !empty( $_SERVER['HTTP_X_FORWARDED_HOST'] ) and $host = filter_var( $_SERVER['HTTP_X_FORWARDED_HOST'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME | FILTER_NULL_ON_FAILURE ) )
					{
						$url .= $host;
						$ruleMatched = TRUE;
					}
					elseif ( $server = filter_var( $_SERVER['HTTP_X_FORWARDED_SERVER'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME | FILTER_NULL_ON_FAILURE ) )
					{
						$url .= $server;
						$ruleMatched = TRUE;
					}
				}
				elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_HOST'] ) and $host = filter_var( $_SERVER['HTTP_X_FORWARDED_HOST'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME | FILTER_NULL_ON_FAILURE ) )
				{
					$url .= $host;
					$ruleMatched = TRUE;
				}
			}

			/* Probably not Nginx and can use normal headers */
			if( !$ruleMatched )
			{
				if ( !empty( $_SERVER['HTTP_HOST'] ) )
				{
					$url .= $_SERVER['HTTP_HOST'];
				}
				else
				{
					$url .= $_SERVER['SERVER_NAME'];
				}
			}
			
			if ( $_SERVER['QUERY_STRING'] AND mb_strpos( $_SERVER['REQUEST_URI'], $_SERVER['QUERY_STRING'] ) !== FALSE )
			{
				$url .= mb_substr( $_SERVER['REQUEST_URI'], 0, -mb_strlen( $_SERVER['QUERY_STRING'] ) );
			}
			else
			{
				$url .= $_SERVER['REQUEST_URI'];
			}
			$url .= $_SERVER['QUERY_STRING'];

			return $this->_url = Url::createFromString( $url, TRUE, TRUE );
		}

		return $this->_url;
	}

	
	/**
	 * Get IP Address
	 *
	 * @return	string
	 */
	public function ipAddress(): string
	{
		$addrs = array();
		
		if ( Settings::i()->xforward_matching )
		{
			if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
			{
				foreach( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) as $x_f )
				{
					$addrs[] = trim( $x_f );
				}
			}

			if( isset( $_SERVER['HTTP_CLIENT_IP'] ) )
			{
				$addrs[] = $_SERVER['HTTP_CLIENT_IP'];
			}
			
			if ( isset( $_SERVER['HTTP_X_CLIENT_IP'] ) )
			{
				$addrs[] = $_SERVER['HTTP_X_CLIENT_IP'];
			}

			if( isset( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) )
			{
				$addrs[] = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
			}

			if( isset( $_SERVER['HTTP_PROXY_USER'] ) )
			{
				$addrs[] = $_SERVER['HTTP_PROXY_USER'];
			}
		}
		
		if ( isset( $_SERVER['REMOTE_ADDR'] ) )
		{
			$addrs[] = $_SERVER['REMOTE_ADDR'];
		}
		
		foreach ( $addrs as $ip )
		{
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) )
			{
				return $ip;
			}
		}

		return '';
	}
	
	/**
	 * IP address is banned?
	 *
	 * @return	bool
	 */
	public function ipAddressIsBanned(): bool
	{
		if ( isset( Store::i()->bannedIpAddresses ) )
		{
			$bannedIpAddresses = Store::i()->bannedIpAddresses;
		}
		else
		{
			$bannedIpAddresses = iterator_to_array( Db::i()->select( 'ban_content', 'core_banfilters', array( "ban_type=?", 'ip' ) ) );
			Store::i()->bannedIpAddresses = $bannedIpAddresses;
		}
		foreach ( $bannedIpAddresses as $ip )
		{
			if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( trim( $ip ), '/' ) ) . '$/', $this->ipAddress() ) )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}

	/**
	 * Returns the cookie path
	 *
	 * @return string
	 */
	public static function getCookiePath(): string
	{
		if( COOKIE_PATH !== NULL )
		{
			return COOKIE_PATH;
		}

		$path = mb_substr( Settings::i()->base_url, mb_strpos( Settings::i()->base_url, ( !empty( $_SERVER['SERVER_NAME'] ) ) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'] ) + mb_strlen( ( !empty( $_SERVER['SERVER_NAME'] ) ) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'] ) );
		$path = mb_substr( $path, mb_strpos( $path, '/' ) );
		
		return $path;
	}

	/**
	 * Get essential cookies
	 *
	 * @return array|string[] List of essential cookies that can't be skipped
	 */
	public static function getEssentialCookies(): array
	{
		return Application::getEssentialCookieNames();
	}
	
	/**
	 * Set a cookie
	 *
	 * @param string $name		Name
	 * @param	mixed				$value		Value
	 * @param DateTime|null $expire		Expiration date, or NULL for on session end
	 * @param bool $httpOnly	When TRUE the cookie will be made accessible only through the HTTP protocol
	 * @param string|null $domain		Domain to set to. If NULL, will be detected automatically.
	 * @param string|null $path		Path to set to. If NULL, will be detected automatically.
	 * @return	bool
	 */
	public function setCookie( string $name, mixed $value, DateTime $expire=NULL, bool $httpOnly=TRUE, string $domain=NULL, string $path=NULL ): bool
	{
		/* Let's see if this is an optional cookie and if it is one, if the user wants them */
		if( $this->skipCookie( $name ) )
		{
			Log::debug('skipping ' . $name, 'cookie' );
			return FALSE;
		}
		
		/* Work out the path and if cookies should be SSL only */
		$sslOnly	= FALSE;
		if( mb_substr( Settings::i()->base_url, 0, 5 ) == 'https' AND !COOKIE_BYPASS_SSLONLY )
		{
			$sslOnly	= TRUE;
		}
		$path = $path ?: static::getCookiePath();

		/* Are we forcing a cookie domain? */
		if( COOKIE_DOMAIN !== NULL AND $domain === NULL )
		{
			$domain	= COOKIE_DOMAIN;
		}
		
		$realName = $name;
		
		/* What about a prefix? */
		if( COOKIE_PREFIX !== NULL )
		{
			$name	= COOKIE_PREFIX . $name;
		}
				
		/* Set the cookie */
		if ( setcookie( $name, $value, $expire ? $expire->getTimestamp() : 0, $path, $domain ?: '', $sslOnly, $httpOnly ) === TRUE )
		{
			$this->cookie[ $realName ] = $value;

			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Should the cookie be set or skipped? Takes the controller location and members cookie preferences into account.
	 * 
	 * @param string $name
	 * @return bool
	 */
	protected function skipCookie( string $name ): bool
	{
		if( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation === 'admin' )
		{
			return FALSE;
		}
		elseif( $this->cookieConsentEnabled() !== TRUE )
		{
			return FALSE;
		}

		if( in_array( $name, static::getEssentialCookies() ) )
		{
			return FALSE;
		}

		/* Check wildcard cookies */
		foreach( static::getEssentialCookies() as $c )
		{
			if( mb_substr( $c, -1, 1 ) === '*' AND rtrim( $name, '*' ) === $name )
			{
				return FALSE;
			}
		}

		return ( !Member::loggedIn()->optionalCookiesAllowed );
	}

	/**
	 * @brief   Storage of cookie consent status
	 */
	protected ?bool $_cookieConsentEnabled = NULL;

	/**
	 * Check whether cookie consent is required
	 *
	 * @return bool|null
	 */
	public function cookieConsentEnabled(): ?bool
	{
		// See if cookie consent is enabled
		if( Dispatcher::hasInstance() AND $this->_cookieConsentEnabled === NULL )
		{
			$this->_cookieConsentEnabled = ( Settings::i()->guest_terms_bar AND mb_strstr( Member::loggedIn()->language()->get('guest_terms_bar_text_value'),  '%4$s' ) );
		}

		return $this->_cookieConsentEnabled;
	}
	
	/**
	 * Clear login cookies
	 *
	 * @return	void
	 */
	public function clearLoginCookies() : void
	{
		$this->setCookie( 'member_id', NULL );
		$this->setCookie( 'login_key', NULL );
		$this->setCookie( 'loggedIn', NULL, NULL, FALSE );
		$this->setCookie( 'noCache', NULL );

		foreach( $this->cookie as $name => $value )
		{
			if( mb_strpos( $name, "ipbforumpass_" ) !== FALSE )
			{
				$this->setCookie( $name, NULL );
			}
		}
	}
	
	/**
	 * @brief	Editor autosave keys to be cleared
	 */
	public array $clearAutoSaveCookie = array();
	
	/**
	 * Set cookie to clear autosave content from editor
	 *
	 * @param	$autoSaveKey    string    The editor's autosave key
	 * @return	void
	 */
	public function setClearAutosaveCookie( string $autoSaveKey ) : void
	{
		$this->clearAutoSaveCookie[ $autoSaveKey ] = $autoSaveKey;
	}
	
	/**
	 * Returns the request method
	 *
	 * @return string
	 */
	public function requestMethod() :string
	{
		return mb_strtoupper( $_SERVER['REQUEST_METHOD'] );
	}
	
	/**
	 * Flood Check
	 *
	 * @return	void
	 */
	public static function floodCheck() : void
	{
		$groupFloodSeconds = Member::loggedIn()->group['g_search_flood'];
		
		if ( Session::i()->userAgent->bot )
		{
			/* Force a 30 second flood control so if guests have it switched off, or set very low, you do not get flooded by known bots */
			$groupFloodSeconds = BOT_SEARCH_FLOOD_SECONDS;
		}
		
		/* Flood control */
		if( $groupFloodSeconds )
		{
			$time = ( isset( Request::i()->cookie['lastSearch'] ) ) ? Request::i()->cookie['lastSearch'] : 0;
			
			if( $time and ( time() - $time ) < $groupFloodSeconds )
			{
				$secondsToWait = $groupFloodSeconds - ( time() - $time );
				Output::i()->error( Member::loggedIn()->language()->addToStack( 'search_flood_error', FALSE, array( 'pluralize' => array( $secondsToWait ) ) ), '1C205/3', 429, Member::loggedIn()->language()->addToStack( 'search_flood_error_admin', FALSE, array( 'pluralize' => array( $secondsToWait ) ) ), array( 'Retry-After' => DateTime::create()->add( new DateInterval( 'PT' . $secondsToWait . 'S' ) )->format('r') ) );
			}
	
			$expire = new DateTime;
			Request::i()->setCookie( 'lastSearch', time(), $expire->add( new DateInterval( 'PT' . intval( $groupFloodSeconds ) . 'S' ) ) );
		}
	}

	/**
	 * Is PHP running as CGI?
	 *
	 * @note	Possible values: cgi, cgi-fcgi, fpm-fcgi
	 * @return	boolean
	 */
	public function isCgi(): bool
	{
		if ( substr( PHP_SAPI, 0, 3 ) == 'cgi' OR substr( PHP_SAPI, -3 ) == 'cgi' )
		{
			return true;
		}
		
		return false;	
	}

	/**
	 * Check, if this was called by command line
	 *
	 * @return bool
	 */
	public static function isCliEnvironment(): bool
	{
		return php_sapi_name() == 'cli';
	}

	/**
	 * Is this a request coming from Zapier?
	 *
	 * @return bool
	 */
	public function isZapier() : bool
	{
		return str_starts_with( Request::i()->userAgent(), 'IPS Zapier Integration' );
	}
	
	/**
	 * Confirmation check
	 *
	 * @param string $title		Lang string key for title
	 * @param string $message	Lang string key for confirm message
	 * @param string $submit		Lang string key for submit button
	 * @param	string		$css		CSS classes for the message
	 * @return	bool
	 */
	public function confirmedDelete( string $title = 'delete_confirm', string $message = 'delete_confirm_detail', string $submit = 'delete', string $css = 'ipsMessage ipsMessage_warning' ): bool
	{
		/* The confirmation dialogs will send form_submitted=1, as will displaying a form, so we check for this.
			If the admin (or user) simply visited a delete URL directly, this would not be included in the request. */
		if ( !isset( Request::i()->wasConfirmed ) )
		{
			$form = new Form( 'form', $submit );
			$form->hiddenValues['wasConfirmed']	= 1;
			$form->class = 'ipsForm--vertical ipsForm--confirmation-check';

			Output::i()->title	 = Member::loggedIn()->language()->addToStack( $title );

			/* We call sendOutput() to show the form now */
			if( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' )
			{
				Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->confirmDelete( $message, $form, $title );
			}
			else
			{
				$form->addMessage( $message, $css);
				Output::i()->output = $form;
			}

			if ( Request::i()->isAjax() )
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core', 'front' )->genericBlock( $form, Output::i()->title ) );
			}
			else
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
			}
		}

		/* If we are here, just check the csrf key */
		Session::i()->csrfCheck();
		return TRUE;
	}
	
	/**
	 * Old IPB escape-on-input routine
	 *
	 * @param object|string $val		The unescaped text (can be a string or an object that can be cast to a string)
	 * @return	string			The IPB3-style escaped text
	 */
	public static function legacyEscape( object|string $val ): string
	{
		$val = (string) $val;
		
		$val = str_replace( "&"			, "&amp;"         , $val );
		$val = str_replace( "<!--"		, "&#60;&#33;--"  , $val );
		$val = str_replace( "-->"			, "--&#62;"       , $val );
		$val = str_ireplace( "<script"	, "&#60;script"   , $val );
		$val = str_replace( ">"			, "&gt;"          , $val );
		$val = str_replace( "<"			, "&lt;"          , $val );
		$val = str_replace( '"'			, "&quot;"        , $val );
		$val = str_replace( "\n"			, "<br />"        , $val );
		$val = str_replace( "$"			, "&#036;"        , $val );
		$val = str_replace( "!"			, "&#33;"         , $val );
		$val = str_replace( "'"			, "&#39;"         , $val );
		$val = str_replace( "\\"			, "&#092;"        , $val );
		
		return $val;
	}
	
	/**
	 * Get our referrer, looking for a specific request variable, then falling back to the header
	 *
	 * @param	bool		$allowExternal	If set to TRUE, external URL's will be allowed and returned.
	 * @param	bool		$onlyRequest	If set to TRUE, will only look for the "ref" request parameter. Useful if you need to look for HTTP_REFERER at a specific point in time.
	 * @param	string|NULL	$base			If set, will only return URL's with this base.
	 * @return	Url|NULL
	 */
	public function referrer( bool $allowExternal=FALSE, bool $onlyRequest=FALSE, ?string $base = NULL ): ?Url
	{
		/* Do we have a _ref request parameter? */
		$ref = NULL;
		if ( isset( $this->ref ) )
		{
			$ref = @base64_decode( $this->ref );
		}
		
		/* Maybe not - check HTTP_REFERER */
		if ( !$ref AND !$onlyRequest AND !empty( $_SERVER['HTTP_REFERER'] ) )
		{
			$ref = $_SERVER['HTTP_REFERER'];
		}
		
		/* Did that work? */
		if ( $ref )
		{
			try
			{
				$ref = Url::createFromString( $ref );
			}
			catch( Exception $e )
			{
				/* Failed to create? Nope. */
				return NULL;
			}

			/* Exclude Service Worker */
			if( isset( $ref->queryString['app'] ) AND $ref->queryString['app'] == 'core' AND $ref->queryString['controller'] == 'serviceworker' )
			{
				return NULL;
			}
			
			/* Return if URL is internal and not an open redirect, or if we're allowing external referrer references */
			if ( ( ( $ref instanceof Internal ) AND !$ref->openRedirect() ) OR $allowExternal )
			{
				if ( $base !== NULL AND ( $ref instanceof Internal ) )
				{
					if ( $ref->base === $base )
					{
						return $ref;
					}
					else
					{
						return NULL;
					}
				}
				else
				{
					return $ref;
				}
			}
			else
			{
				return NULL;
			}
		}
		
		/* Still here? Nothing worked */
		return NULL;
	}
	
	/**
	 * Get any Authorization header (or otherwise passed API key or OAuth access token) in the request
	 * Note: doesn't do any kind of decoding, value will be something like "Basic xxxxx" or "Bearer xxxxx"
	 *
	 * @return	string|null
	 */
	public function authorizationHeader(): ?string
	{
		/* Check if an API Key or Access Token has been passed as a parameter in the query string. Because of the
			obvious security issues with this, we do not recommend it, but sometimes it is the only choice */
		if ( isset( $this->key ) )
		{
			return 'Basic ' . base64_encode( $this->key . ':' );
		}
		if ( isset( $this->access_token ) and ( !OAUTH_REQUIRES_HTTPS or $this->isSecure() ) )
		{
			return 'Bearer ' . $this->access_token;
		}
		
		/* Look for an API key in an automatically decoded HTTP Basic header */
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) )
		{
			return 'Basic ' . base64_encode( $_SERVER['PHP_AUTH_USER'] . ':' );
		}
		
		/* If we're still here, try to find an Authorization header - start with $_SERVER... */
		$authorizationHeader = NULL;
		foreach ( $_SERVER as $k => $v )
		{
			// There could be more than  one header with such suffix ( ( REDIRECT_HTTP_AUTHORIZATION + REDIRECT_REDIRECT_HTTP_AUTHORIZATION ) ) , so let's search for one with a value
			if ( ( mb_substr( $k, -18 ) == 'HTTP_AUTHORIZATION' or mb_substr( $k, -20 ) == 'HTTP_X_AUTHORIZATION' ) AND $v !== '' )
			{
				return $v;
			}
		}
		
		/* ...if we didn't find anything there, try apache_request_headers() */
		if ( function_exists('apache_request_headers') )
		{
			$headers = @apache_request_headers();
			$headerKeys = ['authorization', 'x-authorization'];
			foreach ( $headers as $k => $v )
			{
				if ( in_array( mb_strtolower( $k ), $headerKeys ) )
				{
					return $v;
				}
			}
		}
		
		/* Still here? We got nothing */
		return NULL;
	}

	/**
	 * Return the user agent
	 *
	 * @return string|null
	 */
	public function userAgent() : ?string
	{
		return $_SERVER['HTTP_USER_AGENT'] ?? NULL;
	}
}