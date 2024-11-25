<?php
/**
 * @brief		Output Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Api\Exception as ApiException;
use IPS\Application\Module;
use IPS\Content\Controller;
use IPS\cms\Pages\Page;
use IPS\Content\Search\SearchContent;
use IPS\core\AdminNotification;
use IPS\core\Advertisement;
use IPS\core\DataLayer;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Dispatcher\Front as DispatcherFront;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Http\Url\Internal;
use IPS\Output\Javascript;
use IPS\Output\System as SystemOutput;
use IPS\Session\Front;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function array_merge;
use function array_slice;
use function count;
use function defined;
use function function_exists;
use function get_called_class;
use function in_array;
use function intval;
use function is_array;
use function ob_start;
use function stristr;
use function strlen;
use function strpos;
use function strstr;
use function substr;
use const LIVE_TOPICS_DEV;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Output Class
 */
class Output
{
	/**
	 * @brief	HTTP Statuses
	 * @see		<a href="http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html">RFC 2616</a>
	 */
	public static array $httpStatuses = array( 100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 429 => 'Too Many Requests', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported' );
	
	/**
	 * @brief	Singleton Instance
	 */
	protected static ?Output $instance = NULL;
	
	/**
	 * @brief	Global javascript bundles
	 */
	public static array $globalJavascript = array( 'admin.js', 'front.js', 'framework.js', 'library.js' );
	
	/**
	 * @brief	Javascript map of file object URLs
	 */
	protected static ?array $javascriptObjects = null;
	
	/**
	 * @brief	File object classes
	 */
	protected static array $fileObjectClasses = array();
	
	/**
	 * @brief	Meta tags for the current page
	 */
	public array $metaTags	= array();

	/**
	 * @brief	Custom meta tags for the current page
	 */
	public array $customMetaTags	= array();

	/**
	 * @brief	Automatic meta tags for the current page
	 */
	public array $autoMetaTags	= array();
	
	/**
	 * @brief	Other `<link rel="">` tags
	 */
	public array $linkTags = array();
	
	/**
	 * @brief	RSS feeds for the current page
	 */
	public array $rssFeeds = array();

	/**
	 * @brief	Custom meta tag page title
	 */
	public string $metaTagsTitle	= '';

	/**
	 * @brief	Requested URL fragment for meta tag editing
	 */
	public string $metaTagsUrl	= '';

	/**
	 * @brief	Custom Header
	 */
	public ?string $customHeader = NULL;

	/**
	 * @brief	Custom Header
	 */
	public ?array $outputTemplate = NULL;
	public string $headerMessage;

	/**
	 * Get instance
	 *
	 * @return    Output
	 */
	public static function i() : static
	{
		if( static::$instance === NULL )
		{
			$classname = get_called_class();
			static::$instance = new $classname;
		}
		
		/* Inline Message */
		if( $message = static::getInlineMessage() )
		{
			if( !Request::i()->isAjax() )
			{
				static::$instance->inlineMessage = $message;
				static::setInlineMessage();
			}
		}

		return static::$instance;
	}
	
	/**
	 * @brief	Additional HTTP Headers
	 */
	public array $httpHeaders = array(
		'X-XSS-Protection' => '0',	// This is so when we post contents with scripts (which is possible in the editor, like when embedding a Twitter tweet) the broswer doesn't block it
	);
	
	/**
	 * @brief	Stored Page Title
	 */
	public string $title = '';

	/**
	 * @brief	Default page title (may differ from $title if the meta tag editor was used)
	 */
	public string $defaultPageTitle = '';

	/**
	 * @brief	Should the title show in the header (ACP only)?
	 */
	public bool $showTitle = TRUE;
	
	/**
	 * @brief	Stored Content to output
	 */
	public string $output = '';
	
	/**
	 * @brief	URLs for CSS files to include
	 */
	public array $cssFiles = array();
	
	/**
	 * @brief	URLs for JS files to include
	 */
	public array $jsFiles = array();
	
	/**
	 * @brief	URLs for JS files to include with async="true"
	 */
	public array $jsFilesAsync = array();
	
	/**
	 * @brief	Other variables to hand to the JavaScript
	 */
	public array $jsVars = array();
	
	/**
	 * @brief	Other raw JS - this is included inside an existing `<script>` tag already, so you should omit wrapping tags
	 */
	public string $headJs = '';

	/**
	 * @brief	Raw CSS to output, used to send custom CSS that may need to be dynamically generated at runtime
	 */
	public string $headCss = '';

	/**
	 * @brief	Anything set in this property will be output right before `</body>` - useful for certain third party scripts that need to be output at end of page
	 */
	public string $endBodyCode = '';
	
	/**
	 * @brief	Breadcrumb
	 */
	public array $breadcrumb = array();
	
	/**
	 * @brief	Page is responsive?
	 */
	public bool $responsive = TRUE;
	
	/**
	 * @brief	Sidebar
	 */
	public array $sidebar = array();

	/**
	 * @var string|null Any user alerts that should show on page load
	 */
	public string|null $alert = null;
	
	/**
	 * @brief	Global controllers
	 */
	public array $globalControllers = array();
	
	/**
	 * @brief	Additional CSS classes to add to body tag
	 */
	public array $bodyClasses = array();
	
	/**
	 * @brief	Elements that can be hidden from view
	 */
	public array $hiddenElements = array();
	
	/**
	 * @brief	Inline message
	 */
	public string $inlineMessage = '';
	
	/**
	 * @brief	Page Edit URL
	 */
	public ?Url $editUrl	= NULL;
	
	/**
	 * @brief	`<base target="">`
	 */
	public ?string $base	= NULL;
	
	/**
	 * @brief	Allow default widgets with this output
	 */
	public bool $allowDefaultWidgets = TRUE;
	
	/**
	 * @brief	Allow page caching. This can be set at any point during controller execution to override defaults
	 */
	public bool $pageCaching = TRUE;
	
	/**
	 * @brief	pageName for data-pageName in the <body> tag
	 */
	public ?string $pageName = NULL;

	/**
	 * @brief	Data which were loaded via the GraphQL framework, but which have to be immediately available, rather then via later AJAX requests
	 */
	public array $graphData = [];

	/**
	 * @brief	A custom cache DateTime object set to UTC
	 */
	protected static bool|null|DateTime $cacheDate = NULL;
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation !== 'setup' )
		{
			if ( Settings::i()->clickjackprevention == 'csp' )
			{
				$this->httpHeaders['Content-Security-Policy'] = Settings::i()->csp_header;
				$this->httpHeaders['X-Content-Security-Policy'] = Settings::i()->csp_header; // This is just for IE11
			}
			elseif ( Settings::i()->clickjackprevention != 'none' )
			{
				$this->httpHeaders['X-Frame-Options'] = "sameorigin";
				$this->httpHeaders['Content-Security-Policy'] = "frame-ancestors 'self'";
				$this->httpHeaders['X-Content-Security-Policy'] = "frame-ancestors 'self'";
			}

			/* 2 = entire suite, 1 = ACP only */
			if( Settings::i()->referrer_policy_header == 2 OR ( Settings::i()->referrer_policy_header == 1 AND Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'admin' ) )
			{
				$this->httpHeaders['Referrer-Policy'] = 'strict-origin-when-cross-origin';
			}

            if ( Application::appIsEnabled('cloud') )
            {
                $this->httpHeaders['Strict-Transport-Security'] = 'max-age=31536000';
            }
		}
	}

	/**
	 * Add a css file to the output
	 * 
	 * @param string $file		Filename / Path
	 * @param string|NULL $app	Application
	 * @param string|NULL $location	Location
	 * @return void
	 */
	public function addCssFiles( string $file, string $app=NULL, string $location=NULL ): void
	{
		$this->cssFiles = array_merge( $this->cssFiles, Theme::i()->css( $file, $app, $location ) );
	}

	/**
	 * Get a JS bundle and add the files to the output. This greatly cleans up the usage from outside of the Output class
	 *
	 * @par JS Bundle Filename Cheatsheet
	 * @li library.js (this is jQuery, mustache, underscore, jstz, etc)
	 * @li framework.js (this is ui/, utils/*, ips.model.js, ips.controller.js and the editor controllers)
	 * @li admin.js or front.js (these are controllers, templates and models which are used everywhere for that location)
	 * @li app.js (this is all models for a single application)
	 * @li {location}_{section}.js (this is all controllers and templates for this section called ad-hoc when needed)
	 *
	 * @param string $file Filename
	 * @param string|null $app Application
	 * @param string|null $location Location (e.g. 'admin', 'front')
	 * @return void
	 */
	public function addJsFiles( string $file, string $app=NULL, string $location=NULL ): void
	{
		$this->jsFiles = array_merge( $this->jsFiles, $this->js( $file, $app, $location ) );
	}
	
	/**
	 * Get a JS bundle
	 *
	 * @par JS Bundle Cheatsheet
	 * @li library.js (this is jQuery, mustache, underscore etc)
	 * @li framework.js (this is ui/, utils/*, ips.model.js, ips.controller.js and the editor controllers)
     * @li admin.js or front.js (these are controllers, templates and models which are used everywhere for that location)
	 * @li app.js (this is all models for a single application)
	 * @li {location}_{section}.js (this is all controllers and templates for this section called ad-hoc when needed)
	 *
	 * @param string $file		Filename
	 * @param string|null $app		Application
	 * @param string|null $location	Location (e.g. 'admin', 'front')
	 * @return	array		URL to JS files
	 */
	public function js( string $file, string $app=NULL, string $location=NULL ): array
	{
		$file = trim( $file, '/' );
			 
		if ( $location === 'interface' AND mb_substr( $file, -3 ) === '.js' )
		{
			return array( rtrim( Url::baseUrl( Url::PROTOCOL_RELATIVE ), '/' ) . "/applications/{$app}/interface/{$file}?v=" . ( defined( '\IPS\CACHEBUST_KEY' ) ? CACHEBUST_KEY : time() ) );
		}
		elseif (IN_DEV)
		{
			return Javascript::inDevJs( $file, $app, $location );
		}
		else
		{
			if ( class_exists( 'IPS\Dispatcher', FALSE ) and ( !Dispatcher::hasInstance() OR Dispatcher::i()->controllerLocation === 'setup' ) )
			{
				return array();
			}

			$fileObj = null;
			if ( $app === null OR $app === 'global' )
			{
				if ( in_array( $file, static::$globalJavascript ) )
				{
					/* Global bundle (admin.js, front.js, library.js, framework.js, map.js) */
					$fileObj = static::_getJavascriptFileObject( 'global', 'root', $file );
				}
			}
			else
			{
				$app      = $app      ?: Request::i()->app;
				$location = $location ?: Dispatcher::i()->controllerLocation;
				
				/* app.js - all models and ui */
				if ( $file === 'app.js' )
				{
					$fileObj = static::_getJavascriptFileObject( $app, $location, 'app.js' );
				}
				/* {location}_{section}.js */
				else if ( mb_strstr( $file, '_') AND mb_substr( $file, -3 ) === '.js' )
				{
					[ $location, $key ] = explode( '_',  mb_substr( $file, 0, -3 ) );
						
					if ( ( $location == 'front' OR $location == 'admin' OR $location == 'global' ) AND ! empty( $key ) )
					{
						$fileObj = static::_getJavascriptFileObject( $app, $location, $location . '_' . $key . '.js' );
					}
				}
			}

			if ( $fileObj !== NULL )
			{
				$fileObjUrl = Url::createFromString( ( $fileObj instanceof File ? $fileObj->url : $fileObj ) );
				return array( $fileObjUrl->setQueryString( 'v', Javascript::javascriptCacheBustKey() ) );
			}
		}
		
		return array();
	}
	
	/**
	 * Removes JS files from \IPS\File
	 *
	 * @param string|null $app		Application
	 * @param string|null $location	Location (e.g. 'admin', 'front')
	 * @param string|null $file		Filename
	 * @return	void
	 */
	public static function clearJsFiles( string $app=null, string $location=null, string $file=null ) : void
	{
		$javascriptObjects = ( isset( Store::i()->javascript_map ) ) ? Store::i()->javascript_map : array();
			
		if ( $location === null and $file === null )
		{
			if ( $app === null or $app === 'global' )
			{
				try
				{
					File::getClass('core_Theme')->deleteContainer( 'javascript_global' );
				} catch( Exception $e ) { }
				
				unset( $javascriptObjects['global'] );
			}
			
			foreach(Application::applications() as $key => $data )
			{
				if ( $app === null or $app === $key )
				{
					try
					{
						File::getClass('core_Theme')->deleteContainer( 'javascript_' . $key );
					} catch( Exception $e ) { }
					
					unset( $javascriptObjects[ $key ] );
				}
			}
		}
		
		if ( $file )
		{
			$key = md5( $app .'-' . $location . '-' . $file );
			
			if ( isset( $javascriptObjects[ $app ] ) and is_array( $javascriptObjects[ $app ] ) and in_array( $key, array_keys( $javascriptObjects[ $app ] ) ) )
			{
				if ( $javascriptObjects[ $app ][ $key ] !== NULL )
				{
					File::get( 'core_Theme', $javascriptObjects[ $app ][ $key ] )->delete();
					
					unset( $javascriptObjects[ $app ][ $key ] );
				}
			}
		}

		Settings::i()->changeValues( array( 'javascript_updated' => time() ) );

		Store::i()->javascript_map = $javascriptObjects;

		/* Clear any JS languages */
		foreach( Lang::getEnabledLanguages() as $lang )
		{
			Javascript::clearLanguage( $lang );
		}
	}

	/**
	 * Check page title and modify as needed
	 *
	 * @param string|null $title Page title
	 * @return    string
	 */
	public function getTitle( ?string $title ): string
	{
		if( $this->metaTagsTitle )
		{
			$title	= $this->metaTagsTitle;
		}
		else
		{
			$title = htmlspecialchars( $title, ENT_DISALLOWED, 'UTF-8', FALSE );
		}
		
		if( !Settings::i()->site_online )
		{
			$title	= sprintf( Member::loggedIn()->language()->get( 'offline_title_wrap' ), $title );
		}

		return $title;
	}

	/**
	 * Store any custom DateTime for cache headers
	 *
	 * @param DateTime|bool $date
	 * @return void
	 */
	public static function setCacheTime( DateTime|bool $date=false ): void
	{
		static::$cacheDate = $date;
	}

	/**
	 * Retrieve cache headers
	 *
	 * @param	int		$lastModified	Last modified timestamp
	 * @param	int		$cacheSeconds	Number of seconds to cache for
	 * @return	array
	 */
	public static function getCacheHeaders( int $lastModified, int $cacheSeconds ): array
	{
		/* The default is null, so if it is to false from setCacheTime, we should not cache */
		if ( static::$cacheDate === false )
		{
			return static::getNoCacheHeaders();
		}

		$expires = DateTime::ts( ( time() + $cacheSeconds ), TRUE );
		if ( static::$cacheDate instanceof \DateTime )
		{
			$expires = static::$cacheDate;
			$cacheSeconds = $expires->getTimestamp() - time();
		}
		else
		{
			/* Set the cache date to the expiration date in case anything intercepts the output process and checks the cacheDate */
			static::$cacheDate = $expires;
		}

		if ( \IPS\CIC or \IPS\IN_DEV )
		{
			return array(
				'Date'			=> DateTime::ts( time(), TRUE )->rfc1123(),
				'Last-Modified'	=> DateTime::ts( $lastModified, TRUE )->rfc1123(),
				'Expires'		=> $expires->rfc1123(),
				'Cache-Control'	=> implode( ', ', [
					'max-age=0', 					// No cache for the browser [https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#response_directives]
					'public',						// Public cache
					's-maxage=' . $cacheSeconds, 	// Cache for the CDN [https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#response_directives]
					'stale-if-error'				// Allow the CDN to serve stale content if there is an error
				] )
			);
		}

		return array(
			'Date'			=> DateTime::ts( time(), TRUE )->rfc1123(),
			'Last-Modified'	=> DateTime::ts( $lastModified, TRUE )->rfc1123(),
			'Expires'		=> $expires->rfc1123(),
			'Cache-Control'	=> 'no-cache="Set-Cookie", max-age=' . $cacheSeconds . ", public, s-maxage=" . $cacheSeconds . ", stale-while-revalidate, stale-if-error",
		);
	}
	
	/**
	 * Get No Cache Headers
	 *
	 * @return	array
	 */
	public static function getNoCacheHeaders(): array
	{
		return array(
			'Expires'		=> 0,
			'Cache-Control'	=> "no-cache, no-store, must-revalidate, max-age=0, s-maxage=0"
		);
	}

	/**
	 * Retrieve Content-disposition header. Formats filename according to requesting client.
	 *
	 * @param string $disposition	Disposition: attachment or inline
	 * @param string|null $filename		Filename
	 * @return	string
	 * @see		<a href='http://code.google.com/p/browsersec/wiki/Part2#Downloads_and_Content-Disposition'>Browser content-disposition handling</a>
	 */
	public static function getContentDisposition( string $disposition='attachment', string $filename=NULL ): string
	{
		if( $filename === NULL )
		{
			return $disposition;
		}

		$return	= $disposition . '; filename';

		if ( !Dispatcher::hasInstance() )
		{
			Front::i();
		}
		
		switch( Session::i()->userAgent->browser )
		{
			case 'firefox':
			case 'opera':
				$return	.= "*=UTF-8''" . rawurlencode( $filename );
			break;

			case 'explorer':
			case 'Edge':
			case 'edge':
			case 'chrome':
			case 'Chrome':
				$return	.= '="' . rawurlencode( $filename ) . '"';
			break;

			default:
				$return	.= '="' . $filename . '"';
			break;
		}

		return $return;
	}

	/**
	 * Return a JS file object, recompiling it first if doesn't exist.
	 *
	 * @param string|null $app Application
	 * @param string|null $location Location (e.g. 'admin', 'front')
	 * @param string $file Filename
	 * @return File|string|null URL to JS file object
	 */
	protected static function _getJavascriptFileObject( ?string $app, ?string $location, string $file ): File|string|null
	{
		$key = md5( $app .'-' . $location . '-' . $file );

		if( in_array( $app, IPS::$ipsApps ) OR $app === 'global' )
		{
			if ( file_exists( ROOT_PATH . '/static/js/' . $app . '/' . $location . '_' . $file ) )
			{
				return rtrim( Settings::i()->base_url, '/' ) . '/static/js/' . $app . '/' . $location . '_' . $file;
			}

			return null;
		}
		else
		{
			$javascriptObjects = ( isset( Store::i()->javascript_map ) ) ? Store::i()->javascript_map : array();

			if ( isset( $javascriptObjects[ $app ] ) and in_array( $key, array_keys( $javascriptObjects[ $app ] ) ) )
			{
				if ( $javascriptObjects[ $app ][ $key ] === NULL )
				{
					return NULL;
				}

				return File::get( 'core_Theme', $javascriptObjects[ $app ][ $key ] );
			}
		}

		/* We're setting up, do nothing to avoid compilation requests when tables are incomplete */
		if ( ! isset( Settings::i()->setup_in_progress ) OR Settings::i()->setup_in_progress )
		{
			return NULL;
		}
			
		/* Still here? */
		try
		{
			if ( Javascript::compile( $app, $location, $file ) === NULL )
			{
				/* Rebuild already in progress */
				return NULL;
			}
		}
		catch( RuntimeException $e )
		{
			/* Possibly cannot write file - log but don't show an error as the user can't fix anyways */
			Log::log( $e, 'javascript' );

			return NULL;
		}

		/* The map may have changed */
		$javascriptObjects = ( isset( Store::i()->javascript_map ) ) ? Store::i()->javascript_map : array();
		
		/* Test again */
		if ( isset( $javascriptObjects[ $app ] ) and in_array( $key, array_keys( $javascriptObjects[ $app ] ) ) )
		{
			if( in_array( $app, IPS::$ipsApps ) OR $app === 'global' )
			{
				if( isset( $javascriptObjects[ $app ][ $key ] ) )
				{
					return rtrim( Settings::i()->base_url, '/' ) . $javascriptObjects[ $app ][ $key ];
				}
			}
			elseif( $javascriptObjects[ $app ][ $key ] )
			{
				return File::get( 'core_Theme', $javascriptObjects[ $app ][ $key ] );
			}
		}
		else
		{
			/* Still not there, set this map key to null to prevent repeat access attempts */
			$javascriptObjects[ $app ][ $key ] = null;
			
			Store::i()->javascript_map = $javascriptObjects;
		}
		
		return NULL;
	}
	
	/**
	 * Display Error Screen
	 *
	 * @param string $message 			language key for error message
	 * @param	mixed				$code 				Error code
	 * @param int $httpStatusCode 	HTTP Status Code
	 * @param string|null $adminMessage 		language key for error message to show to admins
	 * @param array $httpHeaders 		Additional HTTP Headers
	 * @param string|null $extra 				Additional information (such backtrace or API error) which will be shown to admins
	 * @param int|string|null $faultyAppOrHookId	The 3rd party application or the hook id, which caused this error, NULL if it was a core application
	 * @return void
	 */
	public function error( string $message, mixed $code, int $httpStatusCode=500, string $adminMessage=NULL, array $httpHeaders=array(), string $extra=NULL, int|string $faultyAppOrHookId=NULL ) : void
	{
		/* Loader extension - check for custom error message */
		foreach( Application::allExtensions( 'core', 'Loader' ) as $loader )
		{
			if( $customMessage = $loader->customError( $message, $code, $httpStatusCode ) )
			{
				$message = $customMessage;
			}
		}

		/* When we log out, the user is taken back to the page they were just on. If this is producing a "no permission" error, redirect them to the index instead */
		if ( isset( Request::i()->_fromLogout ) )
		{
			// _fromLogout=1 indicates that they came from log out. To make sure that we don't cause an infinite redirect (which
			// would happen if guests cannot view the index page) we need to change _fromLogout, but we can't unset it because _fromLogout={anything}
			// will clear the autosave content on next load (by Javascript), which we need to do on log out for security reasons... so, _fromLogout=2
			// is used here which will clear the autosave, but *not* redirect them again
			if ( Request::i()->_fromLogout != 2 )
			{
				$this->redirect(Url::internal('')->stripQueryString()->setQueryString( '_fromLogout', 2 ));
			}
		}
		
		/* If we just logged in and we need to do MFA, do that */
		if ( isset( Request::i()->_mfaLogin ) )
		{
			Output::i()->redirect( Url::internal( "app=core&module=system&controller=login", 'front', 'login' )->setQueryString( '_mfaLogin', 1 ) );
		}

		/* Do not show advertisements that shouldn't display on non-content pages on error pages */
		if( Dispatcher::i()->dispatcherController )
		{
			Dispatcher::i()->dispatcherController->isContentPage = FALSE;
		}
		
		/* If we're in an external script, just show a simple message */
		if ( !Dispatcher::hasInstance() )
		{
			Front::i();

			$this->sendOutput(Member::loggedIn()->language()->get($message), $httpStatusCode, 'text/html', $httpHeaders, FALSE);
		}

		/* Remove the page token */
		unset( Output::i()->jsVars['page_token'] );
		
		/* Work out the title */
		$title = "{$httpStatusCode}_error_title";
		$title = Member::loggedIn()->language()->checkKeyExists( $title ) ? Member::loggedIn()->language()->addToStack( $title ) : Member::loggedIn()->language()->addToStack( 'error_title' );

		/* If we're in setup, just display it */
		if ( Dispatcher::i()->controllerLocation === 'setup' )
		{
			$this->sendOutput(Theme::i()->getTemplate('global', 'core')->globalTemplate($title, Theme::i()->getTemplate('global', 'core')->error($title, $message, $code, $extra)), $httpStatusCode, 'text/html', $httpHeaders, FALSE);
		}

		/* Are we in the API? Throw an exception */
		if( Dispatcher::i()->controllerLocation === 'api' )
		{
			throw new ApiException( $message, $code, $httpStatusCode );
		}
		
		/* Are we an administrator logged in as a member? */
		$member = Member::loggedIn();
		if ( isset( $_SESSION['logged_in_as_key'] ) )
		{
			try
			{
				$_member = Member::load( $_SESSION['logged_in_from']['id'] );
				if ( $_member->member_id == $_SESSION['logged_in_from']['id'] )
				{
					$member = $_member;
				}
			}
			catch ( OutOfRangeException $e ) { }
		}
		
		/* Which message are we showing? */
		if( $member->isAdmin() and $adminMessage )
		{
			$message = $adminMessage;
		}
		else if ( Dispatcher::i()->dispatcherController instanceof Controller and mb_substr( $message, 0, 10 ) == 'node_error' )
		{
			if ( Member::loggedIn()->language()->checkKeyExists( $httpStatusCode . '_' . Dispatcher::i()->application->directory . '_' . Dispatcher::i()->controller ) )
			{
				$message = $httpStatusCode . '_' . Dispatcher::i()->application->directory . '_' . Dispatcher::i()->controller;
			}
		}

		if ( Member::loggedIn()->language()->checkKeyExists( $message ) )
		{
			$message = Member::loggedIn()->language()->addToStack( $message );
		}
		
		/* Replace language stack keys with actual content */
		Member::loggedIn()->language()->parseOutputForDisplay( $message );
								
		/* Log */
		$level = intval( substr( $code, 0, 1 ) );
		if( !Session::i()->userAgent->bot )
		{
			if( $code and Settings::i()->error_log_level and $level >= Settings::i()->error_log_level )
			{
				Db::i()->insert( 'core_error_logs', array(
					'log_member'		=> Member::loggedIn()->member_id ?: 0,
					'log_date'			=> time(),
					'log_error'			=> $message,
					'log_error_code'	=> $code,
					'log_ip_address'	=> Request::i()->ipAddress(),
					'log_request_uri'	=> $_SERVER['REQUEST_URI'],
					) );

				AdminNotification::send( 'core', 'Error', NULL, TRUE, array( $code, $message ) );
			}
		}
			
		/* If this is an AJAX request, send a JSON response */
		if( Request::i()->isAjax() )
		{
			$this->json( $message, $httpStatusCode );
		}


		$faulty = '';

		/* Try to find the breaking hook */
		if ( $faultyAppOrHookId )
		{
				$app = Application::load( $faultyAppOrHookId );
				$faulty = Member::loggedIn()->language()->addToStack( 'faulty_app', FALSE, array( 'sprintf' => array( $app->_title, Url::internal( 'app=core&module=applications&controller=applications', 'admin' ) ) ) );
		}

		/* Send default Cache Headers if this is a front-end page. */
		if ( !isset( $httpHeaders['Cache-Control'] ) AND ( $httpStatusCode == 404 OR $httpStatusCode == 403 ) )
		{
			if ( CACHE_PAGE_TIMEOUT AND $this->pageCaching AND Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation === 'front' AND !Member::loggedIn()->member_id )
			{
				$httpHeaders += static::getCacheHeaders( time(), CACHE_PAGE_TIMEOUT);
			}
		}

		/* Send output */
		if ( Application::appIsEnabled( 'cms' ) and isset( Settings::i()->cms_error_page ) and Settings::i()->cms_error_page )
		{
			Page::errorPage( $title, $message, $code, $httpStatusCode, $httpHeaders );
		}
		else
		{
			Output::i()->sidebar['enabled'] = FALSE;
			$this->sendOutput(Theme::i()->getTemplate('global', 'core')->globalTemplate($title, Theme::i()->getTemplate('global', 'core')->error($title, $message, $code, $extra, $member, $faulty, $httpStatusCode), array('app' => Dispatcher::i()->application ? Dispatcher::i()->application->directory : NULL, 'module' => Dispatcher::i()->module ? Dispatcher::i()->module->key : NULL, 'controller' => Dispatcher::i()->controller)), $httpStatusCode, 'text/html', $httpHeaders, FALSE );
		}

	}

	/**
	 * Send a header.  This is abstracted in an effort to better isolate code for testing purposes.
	 *
	 * @param string $header	Text to send as a fully formatted header string
	 * @return	void
	 */
	public function sendHeader( string $header ) : void
	{
		/* If we are running our test suite, we don't want to send browser headers */
		if( ENFORCE_ACCESS AND mb_strtolower( php_sapi_name() ) == 'cli' )
		{
			return;
		}

		header( $header );
	}

	/**
	 * Send a header.  This is abstracted in an effort to better isolate code for testing purposes.
	 *
	 * @param int $httpStatusCode	HTTP Status Code
	 * @return	void
	 */
	public function sendStatusCodeHeader( int $httpStatusCode ) : void
	{
		/* Set HTTP status */
		if( isset( $_SERVER['SERVER_PROTOCOL'] ) and strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false )
		{
			$this->sendHeader( "HTTP/1.0 {$httpStatusCode} " . static::$httpStatuses[ $httpStatusCode ] );
		}
		else
		{
			$this->sendHeader( "HTTP/1.1 {$httpStatusCode} " . static::$httpStatuses[ $httpStatusCode ] );
		}
	}

	/**
	 * Can this output be cached by CDN?
	 *
	 * @param string $contentType
	 * @param bool $checkCachePageTimeout
	 * @return bool
	 */
	public function isCacheable( string $contentType='text/html', bool $checkCachePageTimeout = true ): bool
	{
		/* Have we got a value for the default cache timeout? */
		if ( $checkCachePageTimeout and ! \IPS\CACHE_PAGE_TIMEOUT )
		{
			return false;
		}

		/* Have we set a per-page cache timeout of false, which means we don't want to cache? */
		if ( static::$cacheDate === false )
		{
			return false;
		}
		else if ( static::$cacheDate instanceof DateTime )
		{
			/* Or have we set a specific cache date and it's in the past? */
			$cacheSeconds = static::$cacheDate->getTimestamp() - time();
			if ( $cacheSeconds <= 0 )
			{
				return false;
			}
		}

		/* Are we logged in? */
		if ( Member::loggedIn()->member_id )
		{
			return false;
		}

		/* Are we using the legacy pageCaching flag? */
		if ( ! $this->pageCaching )
		{
			return false;
		}

		/* Do we have a noCache cookie set? */
		if ( isset( Request::i()->cookie['noCache'] ) )
		{
			return false;
		}

		/* Do we have a CSRF key? */
		if ( isset( Request::i()->csrfKey ) )
		{
			return false;
		}

		/* Check the request method */
		if ( ! in_array( mb_strtolower( $_SERVER['REQUEST_METHOD'] ), [ 'get', 'head' ] ) )
		{
			return false;
		}

		if ( ! in_array( $contentType, ['text/html', 'text/xml', 'application/manifest+json' ] ) )
		{
			return false;
		}

		if ( Dispatcher::hasInstance() and class_exists( 'IPS\Dispatcher', FALSE ) and Dispatcher::i()->controllerLocation !== 'front' )
		{
			return false;
		}

		return true;
	}

	/**
	 * @brief Flag to bypass CSRF IN_DEV check
	 */
	public bool $bypassCsrfKeyCheck	= FALSE;

	/**
	 * Send output
	 *
	 * @param string $output Content to output
	 * @param int $httpStatusCode HTTP Status Code
	 * @param string $contentType HTTP Content-type
	 * @param array $httpHeaders Additional HTTP Headers
	 * @param bool $cacheThisPage Can/should this page be cached?
	 * @param bool $pageIsCached Is the page from a cache? If TRUE, no language parsing will be done
	 * @param bool $parseFileObjects Should `<fileStore.xxx>` and `<___base_url___>` be replaced in the output?
	 * @param bool $parseEmoji Should Emoji be parsed?
	 * @return    void
	 * @throws Exception
	 */
	public function sendOutput(string $output='', int $httpStatusCode=200, string $contentType='text/html', array $httpHeaders=array(), bool $cacheThisPage=TRUE, bool $pageIsCached=FALSE, bool $parseFileObjects=TRUE, bool $parseEmoji=TRUE ) : void
	{
		if( IN_DEV AND !$this->bypassCsrfKeyCheck AND mb_substr( $httpStatusCode, 0, 1 ) === '2' AND isset( $_GET['csrfKey'] ) AND $_GET['csrfKey'] AND !Request::i()->isAjax() AND ( !isset( $httpHeaders['Content-Disposition'] ) OR mb_strpos( $httpHeaders['Content-Disposition'], 'attachment' ) === FALSE ) )
		{
			trigger_error( "An {$httpStatusCode} response is being sent however the CSRF key is present in the requested URL. CSRF keys should be sent via POST or the request should be redirected to a URL not containing a CSRF key once finished.", E_USER_ERROR );
		}

		if ( defined('LIVE_TOPICS_DEV') AND LIVE_TOPICS_DEV )
		{
			$httpHeaders['Access-Control-Allow-Origin'] = '*';
		}

		/* If we have debug templates enabled, we might have bad comments in there */
		if( DEBUG_TEMPLATES )
		{
			$output = $this->_stripBadDebugComments( $output );
		}

		/* Cache session Data Layer events */
		if ( !( $httpStatusCode === 200 AND !Request::i()->isAjax() AND $contentType == 'text/html' ) )
		{
			DataLayer::i()->cache();
		}

		/* Replace language stack keys with actual content */
		if ( Dispatcher::hasInstance() and !in_array( $contentType, array( 'text/javascript', 'text/css', 'application/json' ) ) and $output and !$pageIsCached )
		{
			Member::loggedIn()->language()->parseOutputForDisplay( $output );
		}
		
		/* Parse file storage URLs */
		if ( $output and $parseFileObjects )
		{
			$this->parseFileObjectUrls( $output );
		}

		/* Can we cache this page to a CDN? */
		if( $cacheThisPage and $this->isCacheable( $contentType ) )
		{
			/* Add caching headers */
			if( !isset( $httpHeaders['Cache-Control'] ) )
			{
				$httpHeaders += Output::getCacheHeaders( time(), CACHE_PAGE_TIMEOUT);
			}
		}
		elseif( !isset( $httpHeaders['Cache-Control'] ) )
		{
			/* Send no-cache headers if we got to this point without any cache-control headers being set, or page caching is forced off */
			$httpHeaders += Output::getNoCacheHeaders();
		}

		/* Include headers set in constructor, intentionally after guest caching. */
		$httpHeaders = $this->httpHeaders + $httpHeaders;

		/* We will only push resources (http/2) on the first visit, i.e. if the session cookie is not present yet */
		$location = ( Dispatcher::hasInstance() ) ? IPS::mb_ucfirst( Dispatcher::i()->controllerLocation ) : 'Front';

		if( isset( Request::i()->cookie['IPSSession' . $location ] ) AND Request::i()->cookie['IPSSession' . $location ] AND isset( $httpHeaders['Link'] ) )
		{
			unset( $httpHeaders['Link'] );
		}
		
		/* Query Log (has to be done after parseOutputForDisplay because runs queries and after page caching so the log isn't misleading) */
		if ( $output and ( QUERY_LOG or CACHING_LOG or REDIS_LOG) and in_array( $contentType, array( 'text/html', 'application/json' ) ) and ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation !== 'setup' ) )
		{
			/* Close the session and run tasks now so we can see those queries */
			session_write_close();
			if ( Dispatcher::hasInstance() )
			{
				Dispatcher::i()->__destruct();
			}
			
			/* And run */
			$cachingLog = Cache::i()->log;

			try
			{
				if (REDIS_LOG)
				{
					$cachingLog =  $cachingLog + Redis::$log;
					ksort( $cachingLog );
				}
			}
			catch( Exception $e ) { }

			$queryLog = Db::i()->log;
			if (QUERY_LOG)
			{
				if ( defined('QUERY_LOG_TO_PATH') )
				{
					$queryLogHtml = SystemOutput::generateDbLogHtml( $queryLog );

					if ( defined('QUERY_LOG_TO_SCREEN') )
					{
						@ob_end_clean();
						@ob_start();
						print $queryLogHtml;
						exit();
					}

					@file_put_contents( QUERY_LOG_TO_PATH  . '/sql_' . microtime( true ) . '.txt', $queryLogHtml );
				}
				else
				{
					$output = str_replace( '<!--ipsQueryLog-->', Theme::i()->getTemplate( 'global', 'core', 'front' )->queryLog( $queryLog ), $output );
				}
			}
			if ( CACHING_LOG or REDIS_LOG)
			{
				$output = str_replace( '<!--ipsCachingLog-->', Theme::i()->getTemplate( 'global', 'core', 'front' )->cachingLog( $cachingLog ), $output );
			}
		}

		/* VLE language bits now parseOutputForDisplay has run */
		if ( Lang::vleActive() )
		{
			$output = str_replace( '<!--ipsVleWords-->', 'var ipsVle = ' . json_encode( Member::loggedIn()->language()->vleForJson(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS ) . ';', $output );
		}

		/* Check for any autosave cookies */
		if ( count( Request::i()->clearAutoSaveCookie ) )
		{
			Request::i()->setCookie( 'clearAutosave', implode( ',', Request::i()->clearAutoSaveCookie ), NULL, FALSE );
		}

		/* Remove anything from the output buffer that should not be there as it can confuse content-length */
		if( ob_get_length() )
		{
			@ob_end_clean();
		}

		/* Trim any blank spaces before the beginning of output */
		$output = ltrim( $output );
				
		/* Set HTTP status */
		$this->sendStatusCodeHeader( $httpStatusCode );

		/* Start buffering */
		ob_start();
		
		/* Generated by a logged in user? */
		if( Dispatcher::hasInstance() )
		{
			$this->sendHeader( "X-IPS-LoggedIn: " . ( ( Member::loggedIn()->member_id ) ? 1 : 0 ) );
		}

		/* We want to vary on the cookie so that browser caches are not used when changing themes or languages */
		$vary = array( 'Cookie' );
		
		/* Can we compress the content? */
		if ( $output and isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) )
		{
			/* If php zlib.output_compression is on, don't do anything since PHP will */
			if( (bool) ini_get('zlib.output_compression') === false )
			{
				/* Try brotli first - support will be rare, but preferred if it is available */
				if ( strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'br' ) !== false and function_exists( 'brotli_compress' ) )
				{
					$output = brotli_compress( $output );
					$this->sendHeader( "Content-Encoding: br" ); // Tells the server we've alredy encoded so it doesn't need to
					$vary[] = "Accept-Encoding"; // Tells proxy caches that the response varies depending upon encoding
				}
				/* If the browser supports gzip, gzip the content - we do this ourselves so that we can send Content-Length even with mod_gzip */
				elseif ( strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) !== false and function_exists( 'gzencode' ) )
				{
					$output = gzencode( $output ); // mod_gzip will encode pages, but we want to encode ourselves so that Content-Length is correct
					$this->sendHeader( "Content-Encoding: gzip" ); // Tells the server we've alredy encoded so it doesn't need to
					$vary[] = "Accept-Encoding"; // Tells proxy caches that the response varies depending upon encoding
				}
			}
		}
		
		if ( count( $vary ) )
		{
			$this->sendHeader( "Vary: " . implode( ", ", $vary ) );
		}

		/* Output */
		print $output;
		
		/* Update advertisement impression counts, if appropriate */
		Advertisement::updateImpressions();

		/* Send headers */
		$this->sendHeader( "Content-type: {$contentType};charset=UTF-8" );

		/* Send content-length header, but only if not using zlib.output_compression, because in that case the length we send in the header
			will not match the length of the actual content sent to the browser, breaking things (particularly json) */
		if( (bool) ini_get('zlib.output_compression') === false )
		{
			$size = ob_get_length();
			$this->sendHeader( "Content-Length: {$size}" ); // Makes sure the connection closes after sending output so that tasks etc aren't holding it open
		}
		
		/* Rest of our HTTP headers */
		foreach ( $httpHeaders as $key => $header )
		{
			$this->sendHeader( $key . ': ' . $header );
		}
		$this->sendHeader( "Connection: close" );

		/* If we are running our test suite, we don't want to output or exit, which will allow the test suite to capture the response */
		if( ENFORCE_ACCESS AND mb_strtolower( php_sapi_name() ) == 'cli' )
		{
			return;
		}

		/* Flush and exit */
		@ob_end_flush();
		@flush();

		/* Log headers if we are set to do so */
		if( DEV_LOG_HEADERS )
		{
			$this->_logHeadersSent();
		}

		/* If using PHP-FPM, close the request so that __destruct tasks are run after data is flushed to the browser
			@see http://www.php.net/manual/en/function.fastcgi-finish-request.php */
		if( function_exists( 'fastcgi_finish_request' ) )
		{
			fastcgi_finish_request();
		}

		exit;
	}


	/**
	 * Should we reduce the number of links in the HTML for SEO purposes?
	 *
	 * @return bool
	 */
	public function reduceLinks(): bool
	{
		if ( ! Member::loggedIn()->member_id and Settings::i()->seo_reduce_links )
		{
			return true;
		}

		return false;
	}

	/**
	 * Remove any comment tags that were inserted in the middle of an HTML tag
	 *
	 * @param string $output
	 * @return string
	 */
	protected function _stripBadDebugComments( string $output ) : string
	{
		return preg_replace_callback(
			'/<([^>]+?)<!--(.+?)-->/is',
			function( $match ){
				return "<" . $match[1];
			},
			$output
		);
	}

	/**
	 * Logs the headers that have been sent, if we are able to do so
	 *
	 * @return void
	 */
	protected function _logHeadersSent() : void
	{
		$headers = NULL;

		if( function_exists('headers_list') )
		{
			$headers = headers_list();
		}
		elseif( function_exists('apache_response_headers') )
		{
			$headers = apache_response_headers();
		}
		elseif( function_exists('xdebug_get_headers') )
		{
			$headers = xdebug_get_headers();
		}

		if( $headers !== NULL )
		{
			Log::log( $headers, 'httpHeaders' );
		}
	}

	/**
	 * Fetch the URLs to preload (via Link: HTTP header)
	 *
	 * @return array
	 */
	public function getPreloadUrls(): array
	{
		/* http/2 push resources */
		$preload = array();

		foreach( $this->linkTags as $tag )
		{
			/* We are only doing this for rel=preload, and the 'as' parameter is not optional. Preloading fonts currently does not work as expected in Chrome, resulting in the font file downloading twice, so we will skip fonts. */
			if( is_array( $tag ) AND isset( $tag['rel'] ) AND $tag['rel'] == 'preload' AND isset( $tag['as'] ) AND $tag['as'] AND $tag['as'] != 'font' )
			{
				$preload[] = $tag['href'] . '; rel=preload; as=' .  $tag['as'];
			}
		}

		$cssFilesToCheck = ( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation == 'setup' ) ? Output::i()->cssFiles : array_merge( Output::i()->cssFiles, Theme::i()->css( 'custom.css', 'core', 'front' ) );

		foreach( array_unique( $cssFilesToCheck, SORT_STRING ) as $css )
		{
			$url = Url::external( $css )->setQueryString( 'v', CACHEBUST_KEY );
			$preload[] = $url . '; rel=preload; as=style';
		}
		
		foreach(array_unique( array_filter( array_merge( Output::i()->jsFiles, Output::i()->jsFilesAsync ) ), SORT_STRING ) as $js )
		{
			$url = Url::external( $js );

			if( $url->data['host'] == parse_url( Settings::i()->base_url, PHP_URL_HOST ) )
			{
				$url = $url->setQueryString( 'v', CACHEBUST_KEY);
			}

			$preload[] = $url . '; rel=preload; as=script';
		}

		/* Only include the first 30 entries if there are a lot */
		return array_slice( $preload, 0, 30 );
	}
	
	/**
	 * Send JSON output
	 *
	 * @param array|string $data	Data to be JSON-encoded
	 * @param int $httpStatusCode		HTTP Status Code
	 * @return	void
	 */
	public function json( array|string $data, int $httpStatusCode=200 ) : void
	{
		Member::loggedIn()->language()->parseOutputForDisplay( $data );
		$this->sendOutput(json_encode(Member::loggedIn()->language()->stripVLETags($data)), $httpStatusCode, 'application/json', $this->httpHeaders);
	}

	/**
	 * Redirect
	 *
	 * @param Url|string $url URL to redirect to
	 * @param string|null $message Optional message to display
	 * @param int $httpStatusCode HTTP Status Code
	 * @param bool $forceScreen If TRUE, an intermediate screen will be shown
	 * @return    void
	 * @throws Exception
	 */
	public function redirect( Url|string $url, ?string $message='', int $httpStatusCode=301, bool $forceScreen=FALSE ) : void
	{
		if( Request::i()->isAjax() )
		{
			if ( $message !== '' )
			{
				$message =  Member::loggedIn()->language()->checkKeyExists( $message ) ? Member::loggedIn()->language()->addToStack( $message ) : $message;
				Member::loggedIn()->language()->parseOutputForDisplay( $message );
			}

			$this->json( array(
					'redirect' => (string) $url,
					'message' => $message
			)	);
		}
		elseif ( $forceScreen === TRUE or ( $message and !( $url instanceof Internal ) ) )
		{
			/* We cannot send a 3xx status code without a Location header, or some browsers (cough IE) will not actually redirect. We are showing
				an intermediary page performing the redirect through a meta refresh tag, so a 200 status is appropriate in this case. */
			$httpStatusCode = ( mb_substr( $httpStatusCode, 0, 1 ) == 3 ) ? 200 : $httpStatusCode;

			$this->sendOutput(Theme::i()->getTemplate('global', 'core', 'global')->redirect($url, $message), $httpStatusCode);
		}
		else
		{
			if ( $message )
			{
				$message = Member::loggedIn()->language()->addToStack( $message );
				Member::loggedIn()->language()->parseOutputForDisplay( $message );
				static::setInlineMessage( $message );
				session_write_close();
			}
			elseif( $this->inlineMessage )
			{
				static::setInlineMessage( $this->inlineMessage );
				session_write_close();
			}

			/* Send location and no-cache headers to prevent redirects from being cached */
			$headers = array_merge( array( "Location" => (string) $url ), Output::getNoCacheHeaders() );

			$this->sendOutput('', $httpStatusCode, '', $headers);
		}
	}
	
	/**
	 * Replace the {{fileStore.xxxxxx}} urls to the actual URLs
	 *
	 * @param string|null $output		The compiled output
	 * @return void
	 */
	public function parseFileObjectUrls( ?string &$output ) : void
	{
		if ( stristr( $output, '<fileStore.' ) )
		{
			preg_match_all( '#<fileStore.([\d\w\_]+?)>#', $output, $matches, PREG_SET_ORDER );
			
			foreach( $matches as $index => $data )
			{
				if ( isset( $data[1] ) )
				{
					if ( ! isset( static::$fileObjectClasses[ $data[1] ] ) )
					{
						try
						{
							static::$fileObjectClasses[ $data[1] ] = File::getClass( $data[1], TRUE );
						}
						catch ( RuntimeException $e )
						{
							static::$fileObjectClasses[ $data[1] ] = NULL;
						}
					}
					
					if ( static::$fileObjectClasses[ $data[1] ] )
					{
						$output = str_replace( $data[0], static::$fileObjectClasses[ $data[1] ]->baseUrl(), $output );
					}
				}
			}
		}
		
		/* ___base_url___ is a bit dramatic but it prevents accidental replacements with tags called base_url if a third party app or hook uses it */
		$output = str_replace( '<___base_url___>', rtrim( Settings::i()->base_url, '/' ), $output );
	}

	/**
	 * Show Offline
	 *
	 * @return	void
	 */
	public function showOffline() : void
	{
		$this->bodyClasses[] = 'ipsLayout_minimal';
		$this->bodyClasses[] = 'ipsLayout_minimalNoHome';
		
		$this->output = Theme::i()->getTemplate( 'system', 'core' )->offline( Settings::i()->site_offline_message );
		$this->title  = Settings::i()->board_name;
		
		Output::i()->sidebar['enabled'] = FALSE;

		DispatcherFront::i()->checkMfa();

		/* Unset page token */
		unset( Output::i()->jsVars['page_token'] );

		$this->sendOutput(Theme::i()->getTemplate('global', 'core')->globalTemplate($this->title, $this->output, array('app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller)), 503);
	}

	/**
	 * Show Banned
	 *
	 * @return	void
	 */
	public function showBanned() : void
	{
		$ipBanned = Request::i()->ipAddressIsBanned();
		$banEnd = Member::loggedIn()->isBanned();

		$message = 'member_banned';
		if ( !$ipBanned and $banEnd instanceof DateTime)
		{
			$message = Member::loggedIn()->language()->addToStack( 'member_banned_temp', FALSE, array( 'htmlsprintf' => array( $banEnd->html() ) ) );
		}

		$member = Member::loggedIn();
		$warnings = NULL;

		if( $member->member_id )
		{
			try
			{
				$warningCount = Db::i()->select( 'COUNT(*)', 'core_members_warn_logs', array( 'wl_member = ?', $member->member_id ) )->first();

				if( $warningCount )
				{
					$warnings = new Content( 'IPS\core\Warnings\Warning', Url::internal( "app=core&module=system&controller=warnings&id={$member->member_id}", 'front', 'warn_list', $member->members_seo_name ), array( array( 'wl_member=?', $member->member_id ) ) );
					$warnings->rowsTemplate	  = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'warningRow' );
				}
			}
			catch ( UnderflowException $e ){}
		}

		$this->bodyClasses[] = 'ipsLayout_minimal';
		$this->bodyClasses[] = 'ipsLayout_minimalNoHome';

		$this->output = Theme::i()->getTemplate( 'system', 'core' )->banned( $message, $warnings, $banEnd );
		$this->title  = Settings::i()->board_name;

		Output::i()->sidebar['enabled'] = FALSE;

		/* Unset page token */
		unset( Output::i()->jsVars['page_token'] );

		$this->sendOutput(Theme::i()->getTemplate('global', 'core')->globalTemplate($this->title, $this->output, array('app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller)), 403, 'text/html', array(), FALSE);
	}

	/**
	 * Determines if the sidebar should be displayed on this page
	 * Replaces the very long and confusing if statement in core_front_global_sidebar
	 *
	 * @return bool
	 */
	public function showSidebar() : bool
	{
		/* If we are forcing the sidebar for ads, this is always true */
		if( Settings::i()->ads_force_sidebar AND Advertisement::loadByLocation( 'ad_sidebar' ) )
		{
			return true;
		}

		/* Is the sidebar disabled? */
		if( !isset( $this->sidebar['enabled'] ) or !$this->sidebar['enabled'] )
		{
			return false;
		}

		/* If we can manage widgets, this is always true */
		if( Dispatcher::hasInstance() and Dispatcher::i()->application->canManageWidgets() )
		{
			return true;
		}

		/* Finally, check if we have content */
		return $this->sidebarHasContent();
	}

	/**
	 * Check if there is any content in the sidebar
	 *
	 * @return bool
	 */
	public function sidebarHasContent() : bool
	{
		if( isset( $this->sidebar['contextual'] ) and !empty( $this->sidebar['contextual'] ) )
		{
			return true;
		}

		if( isset( $this->sidebar['widgets']['sidebar'] ) and count( $this->sidebar['widgets']['sidebar'] ) )
		{
			return true;
		}


		if( isset( $this->sidebar['widgetareas'] ) and count( $this->sidebar['widgetareas'] ) )
		{
			return true;
		}

		return false;
	}

	/**
	 * Checks and rebuilds JS map if it is broken
	 *
	 * @param string $app	Application
	 * @return	void
	 */
	protected function _checkJavascriptMap( string $app ) : void
	{
		$javascriptObjects = ( isset( Store::i()->javascript_map ) ) ? Store::i()->javascript_map : array();

		if ( ! is_array( $javascriptObjects ) OR ! count( $javascriptObjects ) OR ! isset( $javascriptObjects[ $app ] ) )
		{
			/* Map is broken or missing, recompile all JS */
			Javascript::compile( $app );
		}
	}

	/**
	 * @brief	JSON-LD structured data
	 */
	public array $jsonLd	= array();

	/**
	 * Fetch meta tags for the current page.  Must be called before sendOutput() in order to reset title.
	 *
	 * @return	void
	 */
	public function buildMetaTags() : void
	{
		/* Set basic ones */
		$this->metaTags['og:site_name'] = Settings::i()->board_name;
		$this->metaTags['og:locale'] = preg_replace( "/^([a-zA-Z0-9\-_]+?)(?:\..*?)$/", "$1", Member::loggedIn()->language()->short );
		
		/* Add the site name to the title */
		if( Settings::i()->board_name )
		{
			$this->title .= ' - ' . Settings::i()->board_name;
		}

		$this->defaultPageTitle	= $this->title;
		
		/* Add Admin-specified ones */
		if( !$this->metaTagsUrl )
		{
			$this->metaTagsUrl	= ( Request::i()->url() instanceof Friendly ) ? Request::i()->url()->friendlyUrlComponent : '';

			if ( isset( Store::i()->metaTags ) )
			{
				$rows = Store::i()->metaTags;
			}
			else
			{
				$rows = iterator_to_array( Db::i()->select( '*', 'core_seo_meta' ) );
				Store::i()->metaTags = $rows;
			}
						
			if( is_array( $rows ) )
			{
				/* We duplicate these so we can know what is generated automatically for the live meta tag editor */
				$this->autoMetaTags = $this->metaTags;

				$rootPath = Url::external( Settings::i()->base_url )->data['path'];

				foreach ( $rows as $row )
				{
					if( strpos( $row['meta_url'], '*' ) !== FALSE )
					{
						if( preg_match( "#^" . str_replace( '\*', '(.*)', trim( preg_quote( $row['meta_url'], '#' ), '/' ) ) . "$#i", trim( $this->metaTagsUrl, '/' ) ) )
						{
							$_tags	= json_decode( $row['meta_tags'], TRUE );
		
							if( is_array( $_tags ) )
							{
								foreach( $_tags as $_tagName => $_tagContent )
								{
									if( $_tagContent === NULL )
									{
										unset( $this->metaTags[ $_tagName ] );
									}
									else
									{
										$this->metaTags[ $_tagName ]		= $_tagContent;
									}

									$this->customMetaTags[ $_tagName ]	= $_tagContent;
								}
							}
		
							/* Are we setting page title? */
							if( $row['meta_title'] )
							{
								$this->title			= $row['meta_title'];
								$this->metaTagsTitle	= $row['meta_title'];
							}
						}
					}
					else
					{
						if( trim( $row['meta_url'], '/' ) == trim( $this->metaTagsUrl, '/' ) and ( ( $row['meta_url'] == '/' and trim( Request::i()->url()->data['path'], '/' ) == trim( $rootPath, '/' ) ) or $row['meta_url'] !== '/' ) )
						{
							$_tags	= json_decode( $row['meta_tags'], TRUE );
							
							if ( is_array( $_tags ) )
							{
								foreach( $_tags as $_tagName => $_tagContent )
								{
									if( $_tagContent === NULL )
									{
										unset( $this->metaTags[ $_tagName ] );
									}
									else
									{
										$this->metaTags[ $_tagName ]		= $_tagContent;
									}

									$this->customMetaTags[ $_tagName ]	= $_tagContent;
								}
							}
							
							/* Are we setting page title? */
							if( $row['meta_title'] )
							{
								$this->title			= $row['meta_title'];
								$this->metaTagsTitle	= $row['meta_title'];
							}
						}
					}
				}
			}
		}
		
		$baseUrl = parse_url( Settings::i()->base_url );

		foreach( $this->metaTags as $name => $value )
		{
			if ( ! is_array( $value ) )
			{
				$value = array( $value );
			}
			
			foreach( $value as $tag )
			{
				if ( mb_substr( $tag, 0, 2 ) === '//' )
				{
					/* Try to preserve http vs https */
					if( isset( $baseUrl['scheme'] ) )
					{
						$tag = str_replace( '//', $baseUrl['scheme'] . '://', $tag );
					}
					else
					{
						$tag = str_replace( '//', 'http://', $tag );
					}
					
					$this->metaTags[ $name ] = $tag;
				}
			}
		}

		/* Automatically generate JSON-LD markup */
		$mainSiteUrl = ( Settings::i()->site_site_elsewhere and Settings::i()->site_main_url ) ? Settings::i()->site_main_url : Settings::i()->base_url;
		$mainSiteTitle = ( Settings::i()->site_site_elsewhere and Settings::i()->site_main_title ) ? Settings::i()->site_main_title : Settings::i()->board_name;
		$jsonLd = array(
			'website'		=> array(
				'@context'	=> "https://www.schema.org",
				'publisher' => Settings::i()->base_url . '#organization',
				'@type'		=> "WebSite",
				'@id' 		=> Settings::i()->base_url . '#website',
	            'mainEntityOfPage' => Settings::i()->base_url,
				'name'		=> Settings::i()->board_name,
				'url'		=> Settings::i()->base_url,
				'potentialAction'	=> array(
					'type'			=> "SearchAction",
					'query-input'	=> "required name=query",
					'target'		=> urldecode( (string) Url::internal( "app=core&module=search&controller=search", "front", "search" )->setQueryString( "q", "{query}" ) ),
				),
				'inLanguage'		=> array()
			),
			'organization'	=> array(
				'@context'	=> "https://www.schema.org",
				'@type'		=> "Organization",
				'@id' 		=> $mainSiteUrl . '#organization',
	            'mainEntityOfPage' => $mainSiteUrl,
				'name'		=> $mainSiteTitle,
				'url'		=> $mainSiteUrl,
			)
		);

		if( $logo = Theme::i()->logo_front )
		{
			$jsonLd['organization']['logo'] = array(
				'@type' => 'ImageObject',
	            '@id'   => Settings::i()->base_url . '#logo',
	            'url'   => (string) $logo
			);
		}

		if( Settings::i()->site_social_profiles AND $links = json_decode( Settings::i()->site_social_profiles, TRUE ) AND count( $links ) )
		{
			if( !isset( $jsonLd['organization']['sameAs'] ) )
			{
				$jsonLd['organization']['sameAs'] = array();
			}

			foreach( $links as $link )
			{
				$jsonLd['organization']['sameAs'][]	= $link['key'];
			}
		}

		if( Settings::i()->site_address AND $address = GeoLocation::buildFromJson( Settings::i()->site_address ) )
		{
			if ( ! empty( $address->country ) )
			{
				$jsonLd['organization']['address'] = array(
					'@type'				=> 'PostalAddress',
					'streetAddress'		=> implode( ', ', $address->addressLines ),
					'addressLocality'	=> $address->city,
					'addressRegion'		=> $address->region,
					'postalCode'		=> $address->postalCode,
					'addressCountry'	=> $address->country,
				);
			}
		}

		foreach(Lang::getEnabledLanguages() as $language )
		{
			$jsonLd['website']['inLanguage'][] = array(
				'@type'		=> "Language",
				'name'		=> $language->title,
				'alternateName'	=> $language->bcp47()
			);
		}

		/* Add breadcrumbs */
		if( count( $this->breadcrumb ) )
		{

			$position	= 1;
			$elements	= [];

			foreach( $this->breadcrumb as $breadcrumb )
			{
				if( $breadcrumb[0] )
				{
					$elements[] = array(
						'@type'		=> "ListItem",
						'position'	=> $position,
						'item'		=> array(
							'@id'	=> (string) $breadcrumb[0],
							'name'	=> $breadcrumb[1],
						)
					);

					$position++;
				}
			}

			if( count( $elements ) )
			{
				$jsonLd['breadcrumbs'] = array(
					'@context'	=> "https://schema.org",
					'@type'		=> "BreadcrumbList",
					'itemListElement'	=> $elements,
				);
			}
		}

		if( Member::loggedIn()->canUseContactUs() )
		{
			$jsonLd['contact'] = array(
				'@context'	=> "https://schema.org",
				'@type'		=> "ContactPage",
				'url'		=> urldecode( (string) Url::internal( "app=core&module=contact&controller=contact", "front", "contact" ) ),
			);
		}
		
		$this->jsonLd	= array_merge( $this->jsonLd, $jsonLd );
	}

	/**
	 * @brief	Global search menu options
	 */
	protected ?array $globalSearchMenuOptions	= NULL;
	
	/**
	 * @brief	Contextual search menu options
	 */
	public array $contextualSearchOptions = array();
	
	/**
	 * @brief	Default search option
	 */
	public array $defaultSearchOption	= array( 'all', 'search_everything' );

	/**
	 * Retrieve options for search menu
	 *
	 * @return	array|null
	 */
	public function globalSearchMenuOptions(): ?array
	{
		if( $this->globalSearchMenuOptions === NULL )
		{
			foreach( SearchContent::searchableClasses() as $class )
			{
				if( in_array( 'IPS\Content\Item', class_parents( $class ) ) )
				{
					$type	= mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) );
					$this->globalSearchMenuOptions[ $type ] = $type . '_pl';
				}
			}
		}
		
		/* This is also supported, but is not a content item class implementing \Searchable */
		if ( Member::loggedIn()->canAccessModule( Module::get( 'core', 'members', 'front' ) ) )
		{
			$this->globalSearchMenuOptions['core_members'] = 'core_members_pl';
		}

		return $this->globalSearchMenuOptions;
	}

	/**
	 * Include a file and return the output
	 *
	 * @param string $path	Path or URL
	 * @return	string
	 */
	public static function safeInclude( string $path ): string
	{
		ob_start();
		include( ROOT_PATH . DIRECTORY_SEPARATOR . $path );
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
	
	/**
	 * Get any inline message
	 *
	 * @return	string|null
	 */
	protected static function getInlineMessage(): ?string
	{
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation == 'front' and Front::loggedIn() ) # Don't attempt to initiate a full member object here
		{
			return ( ( isset( $_SESSION['inlineMessage'] ) and ! empty( $_SESSION['inlineMessage'] ) ) ? $_SESSION['inlineMessage'] : NULL );
		}
		else if ( isset( Request::i()->cookie['inlineMessage'] ) )
		{
			return Request::i()->cookie['inlineMessage'];
		}
		
		return NULL; 
	}
	
	/**
	 * Set an inline message
	 *
	 * @param string|null $message	The message
	 * @return	void
	 */
	protected static function setInlineMessage( string $message=NULL ) : void
	{
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation == 'front' and Front::loggedIn() ) # Don't attempt to initiate a full member object here
		{
			$_SESSION['inlineMessage'] = $message;
		}
		else
		{
			Request::i()->setCookie( 'inlineMessage', $message );
		}
	}
}