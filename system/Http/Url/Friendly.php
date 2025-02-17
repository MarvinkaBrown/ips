<?php
/**
 * @brief		Friendly URL
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 May 2015
 */

namespace IPS\Http\Url;
 
/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\cms\Pages\Page;
use IPS\Data\Store;
use IPS\Http\Url;
use IPS\Settings;
use IPS\Db;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function file_get_contents;
use function intval;
use function is_array;
use function is_null;
use function is_string;
use const IPS\DEV_USE_FURL_CACHE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Friendly URL
 */
class Friendly extends Internal
{
	/**
	 * @brief	Base
	 */
	public ?string $base = 'front';
	
	/**
	 * @brief	SEO Template
	 */
	public ?string $seoTemplate = NULL;
	
	/**
	 * @brief	SEO Titles (unencoded)
	 */
	public array $seoTitles = array();
	
	/**
	 * @brief	The friendly URL component, which may be for the path or the query string (e.g. "topic/1-test")
	 */
	public ?string $friendlyUrlComponent;
	
	/* !Factory methods */
	
	/**
	 * Create a friendly URL from a query string
	 *
	 * @param int $protocol				Protocol (one of the PROTOCOL_* constants)
	 * @param string $friendlyUrlComponent	The friendly URL component, which may be for the path or the query string (e.g. "topic/1-test")
	 * @param array|string $queryString			Additional query string data
	 * @return	static
	 * @throws    Exception
	 */
	public static function friendlyUrlFromComponent( int $protocol, string $friendlyUrlComponent, array|string $queryString ): Url
	{		
		if ( Settings::i()->htaccess_mod_rewrite )
		{
			return static::createInternalFromComponents(
				'front',
				$protocol,
				$friendlyUrlComponent ? "{$friendlyUrlComponent}/" : '',
				$queryString
			);
		}
		else
		{
			if( is_string( $queryString ) )
			{
				parse_str( $queryString, $queryStringArray );
			}
			else
			{
				$queryStringArray = $queryString;
			}

			return static::createInternalFromComponents(
				'front',
				$protocol,
				'index.php',
				( $friendlyUrlComponent ? array( "/{$friendlyUrlComponent}/" => NULL ) : array() ) + $queryStringArray
			);
		}
	}
	
	/**
	 * Create a friendly URL from a full URL, working out friendly URL data
	 *
	 * @param array $components			An array of components as returned by componentsFromUrlString()
	 * @param string $potentialFurl		The potential FURL slug (e.g. "topic/1-test")
	 * @return	static|NULL
	 */
	public static function createFriendlyUrlFromComponents( array $components, string $potentialFurl ): ?static
	{
		/* Loop each of our friendly URL definitions */
		$matchedFurlDefinition = NULL;
		$seoTitles = array();
		foreach ( static::furlDefinition() as $seoTemplate => $furlDefinition )
		{		
			if ( $returnedMatches = static::getMatchedParamsFromFriendlyUrlComponent( $furlDefinition, $potentialFurl ) )
			{
				$matchedFurlDefinition = $furlDefinition;
				[ $matchedParams, $seoTitles ] = $returnedMatches;
				break;
			}
		}
				
		/* If we didn't get one return NULL */
		if ( !$matchedFurlDefinition )
		{
			$return = NULL;
		}
		else if( $return = static::createFromComponents( $components[ static::COMPONENT_HOST ], $components[ static::COMPONENT_SCHEME ], $components[ static::COMPONENT_PATH ], $components[ static::COMPONENT_QUERY ], $components[ static::COMPONENT_PORT ], $components[ static::COMPONENT_USERNAME ], $components[ static::COMPONENT_PASSWORD ], $components[ static::COMPONENT_FRAGMENT ] )
			->setFriendlyUrlData( $seoTemplate, $seoTitles, $matchedParams, $potentialFurl ) or !$potentialFurl )
		{
			if ( !Application::appIsEnabled('cms') or !Application::load('cms')->default OR $potentialFurl )
			{
				return $return;
			}
		}

		if( Application::appIsEnabled('cms') )
		{
			/* Try to find a page */
			try
			{
				[ $pagePath, $pageNumber ] = Page::getStrippedPagePath( $potentialFurl );

				/* Is this from the static folders or upload folders? */
				if ( mb_substr( $pagePath, 0, 7 ) === 'static/' or mb_substr( $pagePath, 0, 8 ) === 'uploads/' )
				{
					/* Yes, so just return to avoid looking it up in pages when it won't be there */
					return $return;
				}

				try
				{
					$page = Page::loadFromPath( $pagePath );
				}
				catch( \Exception $e )
				{
					/* Try from furl */
					try
					{
						$page = Page::load( Db::i()->select( 'store_current_id', 'cms_url_store', array( 'store_type=? and store_path=?', 'page', $pagePath ) )->first() );
					}
					catch( UnderflowException $e )
					{
						throw new OutOfRangeException;
					}
				}

				return static::createFromComponents( $components[ static::COMPONENT_HOST ], $components[ static::COMPONENT_SCHEME ], $components[ static::COMPONENT_PATH ], $components[ static::COMPONENT_QUERY ], $components[ static::COMPONENT_PORT ], $components[ static::COMPONENT_USERNAME ], $components[ static::COMPONENT_PASSWORD ], $components[ static::COMPONENT_FRAGMENT ] )
					->setFriendlyUrlData( 'content_page_path', array( $potentialFurl ), array( 'path' => $potentialFurl ), $potentialFurl );
			}
				/* Couldn't find one? Don't accept responsibility */
			catch ( OutOfRangeException $e )
			{
				return $return;
			}
				/* The table may not yet exist if we're using the parser in an upgrade */
			catch ( \Exception $e )
			{
				if( $e->getCode() == 1146 )
				{
					return $return;
				}

				throw $e;
			}
		}

		return $return;
	}
	
	/**
	 * Create a friendly URL from a query string with known friendly URL data
	 *
	 * @param string $queryString	The query string
	 * @param string $seoTemplate	The key for making this a friendly URL
	 * @param array|string $seoTitles		The title(s) needed for the friendly URL
	 * @param int $protocol		Protocol (one of the PROTOCOL_* constants)
	 * @return	static
	 * @throws    Exception
	 * @todo	Currently this will silently return a non-friendly URL if $seoTemplate is not valid, for backwards compatibility. Remove this in a major update.
	 */
	public static function friendlyUrlFromQueryString( string $queryString, string $seoTemplate, array|string $seoTitles, int $protocol ): Url
	{
		$_originalQueryString = $queryString;

		if ( $seoTemplate === 'content_page_path' )
		{
			/* Get the friendly URL component */
			$friendlyUrlComponent = static::buildFriendlyUrlComponentFromData( $queryString, $seoTemplate, $seoTitles );

			return static::friendlyUrlFromComponent( $protocol, $friendlyUrlComponent, $queryString )->setFriendlyUrlData( $seoTemplate, $seoTitles, array( 'path' => $friendlyUrlComponent ), $friendlyUrlComponent );
		}

		/* Get the friendly URL component */
		try
		{
			$friendlyUrlComponent = static::buildFriendlyUrlComponentFromData( $queryString, $seoTemplate, $seoTitles );
		}
		catch (Exception $e )
		{
			if ( $e->getMessage() === 'INVALID_SEO_TEMPLATE' and !\IPS\IN_DEV )
			{
				return static::createInternalFromComponents( 'front', $protocol, 'index.php', $queryString );
			}
			else
			{
				throw $e;
			}
		}

		/* Extract the hidden query string parameters inside it */
		$matchedParams = array();
		$furlDefinition = static::furlDefinition();
		if ( !isset( $furlDefinition[ $seoTemplate ] ) )
		{
			throw new Exception( 'INVALID_SEO_TEMPLATE' );
		}
		if ( $returnedMatches = static::getMatchedParamsFromFriendlyUrlComponent( $furlDefinition[ $seoTemplate ], $friendlyUrlComponent ) )
		{
			[ $matchedParams, $_seoTitles ] = $returnedMatches;
		}
		else
		{
			if ( \IPS\IN_DEV )
			{
				throw new Exception( 'SEO_TEMPLATE_IS_NOT_VALID_FOR_URL: ' . $_originalQueryString . ' - ' . $seoTemplate );
			}
		}
		
		/* Return */
		return static::friendlyUrlFromComponent( $protocol, $friendlyUrlComponent, $queryString )->setFriendlyUrlData( $seoTemplate, $seoTitles, $matchedParams, $friendlyUrlComponent );
	}

	/**
	 * Set friendly URL data
	 *
	 * @param string $seoTemplate			The key for making this a friendly URL
	 * @param array|string $seoTitles				The title(s) needed for the friendly URL
	 * @param array $matchedParams			The values for hidden query string properties
	 * @param	string			$friendlyUrlComponent	The friendly URL component, which may be for the path or the query string (e.g. "topic/1-test")
	 * @return	static
	 * @throws    Exception
	 */
	protected function setFriendlyUrlData( string $seoTemplate, array|string $seoTitles, array $matchedParams=array(), string $friendlyUrlComponent = '' ): static
	{
		if ( $seoTemplate === 'content_page_path' )
		{
			/* Set basic properties */
			$this->seoTemplate = 'content_page_path';
			$this->seoTitles = is_string( $seoTitles ) ? array( $seoTitles ) : $seoTitles;
			$this->friendlyUrlComponent = $friendlyUrlComponent;
			$this->seoPagination = true;

			/* Set hidden query string */
			$this->hiddenQueryString = array( 'app' => 'cms', 'module' => 'pages', 'controller' => 'page' ) + $matchedParams;

			/* Return */
			return $this;
		}

		/* Get the definition */
		$furlDefinition = static::furlDefinition();
		if ( !isset( $furlDefinition[ $seoTemplate ] ) )
		{
			throw new Exception( 'INVALID_SEO_TEMPLATE' );
		}

		/* Set basic properties */
		$this->seoTemplate = $seoTemplate;
		$this->seoTitles = is_string( $seoTitles ) ? array( $seoTitles ) : $seoTitles;
		$this->friendlyUrlComponent = $friendlyUrlComponent;
		$this->seoPagination = ! empty( $furlDefinition[ $seoTemplate ]['seoPagination'] );

		/* Set hidden query string */
		parse_str( $furlDefinition[ $seoTemplate ]['real'], $hiddenQueryString );
		$this->hiddenQueryString = $hiddenQueryString + $matchedParams;

		/* Return */
		return $this;
	}
	
	/**
	 * Validate that the SEO title is correct
	 *
	 * @return	mixed	The correct URL if incorrect, TRUE if correct, or NULL if unknown
	 */
	public function correctFriendlyUrl(): mixed
	{
		/* Get the definition */
		$furlDefinition = static::furlDefinition();
		
		/* If we don't have a validate callback, we can return NULL */
		if ( !isset( $furlDefinition[ $this->seoTemplate ]['verify'] ) or !$furlDefinition[ $this->seoTemplate ]['verify'] )
		{
			return NULL;
		}
		
		/* Load it */
		try
		{
			if ( $correctUrl = $this->correctUrlFromVerifyClass( $furlDefinition[ $this->seoTemplate ]['verify'] ) )
			{
				/* IP.Board 3.x used /page-x in the path rather than a query string argument - we support this so as not to break past links */
				if( mb_strpos( (string) $this, '/page-' ) )
				{
					preg_match( "/\/page\-(\d+)/", (string) $this, $matches );
					if( isset( $matches[1] ) )
					{
						$correctUrl = $correctUrl->setPage( 'page', $matches[1] );
					}
				}

				/* IP.Board 3.x also used to do /page__x__y__a__b for /?x=y&a=b ... let's extract those parameters */
				if( mb_strpos( (string) $this, '/page__' ) )
				{
					preg_match( "/\/page__([^\/$]+?)(?:$|\/)/", (string) $this, $matches );
					if( isset( $matches[1] ) )
					{
						$params	= explode( '__', $matches[1] );
						$key	= NULL;
						foreach( $params as $param )
						{
							if( $key === NULL )
							{
								$key = $param;
							}
							else
							{
								$correctUrl = $correctUrl->setQueryString( $key, $param );
								$key = NULL;
							}
						}
					}
				}

				if ( (string) $correctUrl->normaliseForEqualityCheck() !== (string) $this->normaliseForEqualityCheck() )
				{
					return $correctUrl;
				}
				else
				{
					return TRUE;
				}
			}
			return NULL;
		}
		/* It doesn't exist */
		catch ( OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Normalise the URL for comparing if the "correct" URL is different to the accessed URL
	 *
	 * @return	Url
	 */
	public function normaliseForEqualityCheck(): Url
	{
		/* Strip the query string except the FURL component */
		$return = $this->stripQueryString();
		
		/* Make it schema-relative */
		$return = $return->setScheme( NULL );

		/* Remove pagination attributes */
		$return = $return->setPage();

		/* now remove any trailing / from the path */
		$return = $return->setPath( rtrim( $return->data['path'], '/' ) );

		/* Return */
		return $return;
	}
	
	/**
	 * Strip Query String
	 * Overrides main method so that the FURL slug isn't lost
	 *
	 * @param array|string|null $keys	The key(s) to strip - if omitted, entire query string is wiped
	 * @return	Url
	 */
	public function stripQueryString( array|string $keys=NULL ): Url
	{		
		if ( $keys === NULL and !Settings::i()->htaccess_mod_rewrite )
		{
			$keys = $this->queryString;
			unset( $keys[ "/{$this->friendlyUrlComponent}/" ] );
			$keys = array_keys( $keys );
		}
		
		return parent::stripQueryString( $keys );
	}
	
	/**
	 * Adds the page parameter to the URL
	 *
	 * @param string $param	The page key, default is 'page'
	 * @param int|null $number	The page number to use
	 * @return	Url
	 */
	public function setPage( string $param='page', ?int $number=1 ): Url
	{
		$number = (int) $number;
		if ( $param === 'page' )
		{
			$potentialFurl = NULL;
			$components = static::componentsFromUrlString( $this );
			
			/* seoPagination is set usually in setFriendlyUrlData */
			if ( $this->seoPagination === NULL )
			{
				/* Fallback */
				$def = static::getFurlDefinitionFromPath( preg_replace( "/\/" . preg_quote( $param, '/' ) . "\/\d+?/", '', $this->getFriendlyComponent() ) );
				
				if ( $def !== NULL and isset( $def['seoPagination'] ) )
				{
					$this->seoPagination = $def['seoPagination'];
				}
			}
			
			if ( ! $this->seoPagination )
			{
				/* Ok, do the usual ?page=x param */
				return parent::setPage( $param, $number );
			}

			/* If rewrite furls is disabled, or specifically if the request has index.php in the URL (it may be a cached URL from before the setting was toggled) */
			if ( !Settings::i()->htaccess_mod_rewrite OR static::fixComponentPath( $components[ static::COMPONENT_PATH ] ) === 'index.php' )
			{
				if ( is_array( $components[ static::COMPONENT_QUERY ] ) and count( $components[ static::COMPONENT_QUERY ] ) )
				{
					$new = NULL;
					foreach( $components[ static::COMPONENT_QUERY ] as $k => $v )
					{
						if ( $new === NULL and mb_strstr( $k, '/' . $param . '/' ) )
						{
							$new = static::stripPageComponent( $k );
							$potentialFurl = $new;
							unset( $components[ static::COMPONENT_QUERY ][ $k ] );
						}
					}
					
					if ( $new )
					{
						$components[ static::COMPONENT_QUERY ] = array_merge( array( '/' . ltrim( $new, '/' ) => NULL ), $components[ static::COMPONENT_QUERY ] );
					}
				}
				
				if ( ! $potentialFurl )
				{
					$potentialFurl = $this->getFriendlyComponent();
				}
			}
			else
			{
				$potentialFurl = static::stripPageComponent( static::fixComponentPath( $components[ static::COMPONENT_PATH ] ) );
				$components[ static::COMPONENT_PATH ] = static::stripPageComponent( $components[ static::COMPONENT_PATH ] );
			}

			/* Add on the page itself */
			if ( $number > 1 )
			{
				$potentialFurl = NULL;
				if ( ! Settings::i()->htaccess_mod_rewrite OR static::fixComponentPath( $components[ static::COMPONENT_PATH ] ) === 'index.php' )
				{ 
					foreach( $components[ static::COMPONENT_QUERY ] as $k => $v )
					{
						/* The FURL is always the first query string component */
						if ( $potentialFurl === NULL )
						{
							$potentialFurl = $k . $param . '/' . $number . '/';
							unset($components[ static::COMPONENT_QUERY ][ $k ] );
							break;
						}
					}
					
					if ( $potentialFurl )
					{
						$components[ static::COMPONENT_QUERY ] = array_merge( array( $potentialFurl => NULL ), $components[ static::COMPONENT_QUERY ] );
					}
				}
				else
				{
					$components[ static::COMPONENT_PATH ] .= $param . '/' . $number . '/';
					$potentialFurl = static::stripPageComponent( static::fixComponentPath( $components[ static::COMPONENT_PATH ] ) ) . $param . '/' . $number . '/';
				}
			}

			return static::createFriendlyUrlFromComponents( $components, trim( $potentialFurl, '/' ) );
		}
		else
		{
			return parent::setPage( $param, $number );
		}
	}
	
			
	/* !Utilities */
	
	/**
	 * Returns the FURL string from the current URL
	 *
	 * @return string|NULL	The furl part of the URL, eg /topic/1-foo/
	 */
	protected function getFriendlyComponent(): ?string
	{
		/* Are we lucky? */
		if ( ! empty( $this->friendlyUrlComponent ) )
		{
			return trim( $this->friendlyUrlComponent, '/' );
		}
		
		/* Of course not... */
		if ( ! Settings::i()->htaccess_mod_rewrite )
		{
			if ( is_array( $this->queryString ) and count( $this->queryString ) )
			{
				foreach( $this->queryString as $k => $v )
				{
					if ( mb_strstr( $k, '/' ) )
					{
						$this->friendlyUrlComponent = trim( $k, '/' );
						return $this->friendlyUrlComponent;
					}
				}
			}
		}
		else
		{
			$this->friendlyUrlComponent = trim( static::fixComponentPath( $this->data[ static::COMPONENT_PATH ] ), '/' );
			return $this->friendlyUrlComponent;
		}
		
		return NULL;
	}
	
	/**
	 * Ensure the path doesn't contain any 'real' path eg `site.com</forums/>`
	 *
	 * @param  string		$path		The path from ->data['path'] eg forums/topic/1-foo
	 * @return string	The furl part of the path, eg topic/1-foo
	 */
	public static function fixComponentPath( string $path ): string
	{
		$baseUrl = parse_url( static::baseUrl() );

		if ( ! empty( $baseUrl['path'] ) )
		{
			$pos = mb_stripos( $path, $baseUrl['path'] );
			if( $pos !== FALSE )
			{
				$path = substr_replace( $path, '/', $pos, mb_strlen( $baseUrl['path'] ) );
			}
		}

		return trim( $path, '/' );
	}
	
	/**
	 * Returns the matching FURL definition data for the FURL component eg /topic/1-foo/
	 *
	 * @param string $friendlyUrlComponent		The furl part of the path, eg /topic/1-foo/
	 * @return array|NULL	The FURL definition
	 */
	protected static function getFurlDefinitionFromPath( string $friendlyUrlComponent ): ?array
	{
		foreach ( static::furlDefinition() as $seoTemplate => $furlDefinition )
		{
			foreach ( $furlDefinition['regex'] as $_regex )
			{
				if ( preg_match( '/^' . $_regex . '$/i', $friendlyUrlComponent ) )
				{
					return $furlDefinition;
				}
			}
		}

		return NULL;
	}
	
	/**
	 * Removes any inline page component from the FURL component  eg /topic/1-foo/page/2/
	 *
	 * @param string $friendlyUrlComponent		The furl part of the path, eg /topic/1-foo/
	 * @param string $param						The /page/N/ key
	 * @return	string		The cleaned FURL eg /topic/1-foo/
	 */
	public static function stripPageComponent( string $friendlyUrlComponent, string $param='page' ): string
	{
		return preg_replace( '#/' . $param . '/(\d+?)$#', '', rtrim( $friendlyUrlComponent, '/' ) ) . '/';
	}
	
	/**
	 * Get friendly URL component (e.g. "topic/1-test") from a query string and SEO template
	 *
	 * @param string $queryString	The query string - is passed by reference and any parts used are removed, which can be used to detect extraneous parts
	 * @param string $seoTemplate	The key for making this a friendly URL
	 * @param array|string|null $seoTitles		The title(s) needed for the friendly URL
	 * @return	string
	 * @throws    Exception
	 */
	public static function buildFriendlyUrlComponentFromData( string &$queryString, string $seoTemplate, array|string|null $seoTitles ): string
	{
		if ( $seoTemplate === 'content_page_path' )
		{
			parse_str( $queryString, $queryStringParts );
			unset( $queryStringParts['app'] );
			unset( $queryStringParts['module'] );
			unset( $queryStringParts['controller'] );
			unset( $queryStringParts['page'] );

			$return = $queryStringParts['path'];
			unset( $queryStringParts['path'] );

			$queryString = http_build_query( $queryStringParts );

			return $return;
		}

		/* Get the definition */
		$furlDefinition = static::furlDefinition();
		if ( !isset( $furlDefinition[ $seoTemplate ] ) )
		{
			throw new Exception( 'INVALID_SEO_TEMPLATE ' . $seoTemplate );
		}
		$component = $furlDefinition[ $seoTemplate ]['friendly'];
		
		/* Parse the query string into an array */
		parse_str( $queryString, $queryStringParts );

		/* For each query string component, replace it out */
		foreach ( $queryStringParts as $k => $v )
		{
			if ( mb_strpos( $component, "{#{$k}}" ) !== FALSE or mb_strpos( $component, "{@{$k}}" ) !== FALSE )
			{
				$component = str_replace( "{#{$k}}", intval( $v ), $component );
				$component = str_replace( "{@{$k}}", $v, $component );
				unset( $queryStringParts[ $k ] );
			}
		}
		
		/* Parse out the titles */
		$seoTitles = is_null( $seoTitles ) ? array() : ( is_string( $seoTitles ) ? array( $seoTitles ) : array_values( $seoTitles ) );
		$seoTitlesCount = count( $seoTitles );
		for ( $i = 0; $i < $seoTitlesCount; $i++ )
		{
			if ( $i === 0 )
			{
				$component = str_replace( '{?}', $seoTitles[ $i ], $component );
			}
			$component = str_replace( "{?{$i}}", $seoTitles[ $i ], $component );
		}
		
		/* Remove the "real" parts in the query string */
		parse_str( $furlDefinition[ $seoTemplate ]['real'], $realQueryString );
		foreach ( $realQueryString as $k => $v )
		{
			if ( isset( $queryStringParts[ $k ] ) and $queryStringParts[ $k ] == $v )
			{
				unset( $queryStringParts[ $k ] );
			}
		}

		$queryString = http_build_query( $queryStringParts );
				
		/* Return */
		return $component;
	}
	
	/**
	 * Convert a value into an "SEO Title" for friendly URLs
	 *
	 * @param	string	$value	Value
	 * @return	string
	 * @note	Many places require an SEO title, so we always need to return something, so when no valid title is available we return a dash
	 */
	public static function seoTitle( string $value ): string
	{
		/* Ensure there are no HTML tags */
		$value = strip_tags( $value );
		
		/* Always lowercase */
		$value = mb_strtolower( $value );

		/* Get rid of newlines/carriage returns as they're not cool in friendly URL titles */
		$value = str_replace( array( "\r\n", "\r", "\n" ), ' ', $value );

		/* Just for readability */
		$value = str_replace( ' ', '-', $value );
		
		/* Disallowed characters which browsers may try to automatically percent-encode */
		$value = str_replace( array( '!', '*', '\'', '(', ')', ';', ':', '@', '&', '=', '+', '$', ',', '/', '?', '#', '[', ']', '%', '\\', '"', '<', '>', '^', '{', '}', '|', '.', '`' ), '', $value );
		
		/* Trim */
		$value = preg_replace( '/\-+/', '-', $value );
		$value = trim( $value, '-' );
		$value = trim( $value );
		
		/* Return */
		return $value ?: '-';
	}
	
	/**
	 * Get the matched parameters and SEO titles from a friendly URL component for a particular 
	 *
	 * @param array $furlDefinition			The FURL definition from the furl.json file
	 * @param string $friendlyUrlComponent	The friendly URL component, which may be for the path or the query string (e.g. "topic/1-test")
	 * @return	array|NULL	array( $matchedParams, $seoTitles ) if matched, NULL if the $friendlyUrlComponent does not match this $furlDefinition
	 */
	protected static function getMatchedParamsFromFriendlyUrlComponent( array $furlDefinition, string $friendlyUrlComponent ): ?array
	{
		/* See if it matches */
		$seoTitles = array();
		$matchedParams = array();

		foreach ( $furlDefinition['regex'] as $_regex )
		{
			$regex = ( ! empty( $furlDefinition['seoPagination'] ) ) ? '#^' . $_regex . '($|/page/\d+?$)#i' : '#^' . $_regex . '$#i';
			
			if ( preg_match( $regex, $friendlyUrlComponent, $matches ) )
			{
				/* Check pagination first */
				if ( ! empty( $furlDefinition['seoPagination'] ) and mb_strstr( $matches[0], '/page/' ) )
				{
					foreach( $matches as $k => $v )
					{
						if ( $k and mb_strstr( $v, '/page/' ) )
						{
							[ $x, $page ] = explode( '/', trim( $v, '/' ) );
							
							if ( $page )
							{
								$matchedParams['page'] = intval( $page );
							}
						}
					}
				}

				foreach ( $furlDefinition['params'] as $k => $param )
				{
					if ( $param )
					{
						$matchedParams[ $param ] = $matches[ $k + 1 ];
					}
					else
					{
						$seoTitles[] = $matches[ $k + 1 ];
					}
				}
						
				return array( $matchedParams, $seoTitles );
			}
		}
		
		/* Still here? No match */
		return NULL;
	}
	
	/**
	 * @brief	FURL Definition
	 */
	protected static ?array $furlDefinition = NULL;
	
	/**
	 * Get FURL Definition
	 *
	 * @param	bool	$revert	If TRUE, ignores all customisations and reloads from json
	 * @return	array
	 */
	public static function furlDefinition( bool $revert=FALSE ): array
	{
		if ( static::$furlDefinition === NULL or $revert )
		{
			$furlCustomizations	= ( Settings::i()->furl_configuration AND !$revert and ! \IPS\IN_DEV ) ? json_decode( Settings::i()->furl_configuration, TRUE ) : array();
			$furlConfiguration = ( isset( Store::i()->furl_configuration ) AND Store::i()->furl_configuration ) ? json_decode( Store::i()->furl_configuration, TRUE ) : array();

			if ( ( \IPS\IN_DEV and !DEV_USE_FURL_CACHE ) or !count( $furlConfiguration ) or $revert )
			{
				$furlConfiguration = static::buildFurlConfiguation();
			}
			
			static::$furlDefinition = array_merge( $furlConfiguration, $furlCustomizations );

			if( Application::appIsEnabled('cms') )
			{
				static::$furlDefinition=  array_merge( static::$furlDefinition, array( 'content_page_path' => static::buildFurlDefinition( 'app=cms&module=pages&controller=page', 'app=cms&module=pages&controller=page', NULL, FALSE, NULL, FALSE, 'IPS\cms\Pages\Router' ) ) );
			}
		}

		return static::$furlDefinition;
	}
	
	/**
	 * Rebuild and return `\IPS\Data\Store::i()->furl_configuration` with default, uncustomised values
	 *
	 * @return	array
	 */
	protected static function buildFurlConfiguation(): array
	{
		/* Init */
		$furlConfiguration = array();
		
		/* Load apps, prioritising the default (otherwise if two apps both have "/category/..." the app which is higher in the list may steal from the default app) */
		$applications = Application::applications();
		foreach ( $applications as $k => $app )
		{
			if ( $app->default )
			{
				unset( $applications[ $k ] );
				array_unshift( $applications, $app );
				break;
			}
		}
		
		/* Loop each app... */
		foreach ( $applications as $app )
		{
			/* If it has a furl.json file... */
			if( $app->enabled and file_exists( $app->getApplicationPath() . "/data/furl.json" ) )
			{
				/* Open it up */
				$data = json_decode( preg_replace( '/\/\*.+?\*\//s', '', file_get_contents( $app->getApplicationPath() . "/data/furl.json" ) ), TRUE );
				$topLevel = $data['topLevel'];
				
				/* Process them */
				$definitions = array();
				foreach ( $data['pages'] as $k => $page )
				{
					$definitions[ $k ] = static::buildFurlDefinition($page['friendly'], $page['real'], $topLevel, $app->default, $page['alias'] ?? NULL, FALSE, $page['verify'] ?? NULL, $page['seoTitles'] ?? NULL, $page['seoPagination'] ?? NULL );
				}
												
				/* Add it in */
				$furlConfiguration = array_merge( $furlConfiguration, $definitions );
			}
		}
				
		/* Store */
		Store::i()->furl_configuration = json_encode( $furlConfiguration );
		
		/* Return */
		return $furlConfiguration;
	}

	/**
	 * Build the friendly URL definition
	 *
	 * @param string $friendly		Friendly URL pattern
	 * @param string $real			Non-friendly URL pattern
	 * @param string|null $appTopLevel	FURL slug if the app is not the default app
	 * @param bool $appIsDefault	Flag to indicate if the app is default or not
	 * @param string|null $alias			Friendly URL alias
	 * @param bool $custom			Flag to indicate if this is a custom FURL definition
	 * @param string|null $verify			The name of a class that contains a loadFromUrl() and an url() method for verifying the friendly URL is correct
	 * @param array|null $seoTitles		The class, query param and property to load from to rebuild seo titles
	 * @param bool|null $seoPagination	Whether to use SEO-friendly pagination (e.g. /page/2/) or not
	 * @return	array
	 */
	public static function buildFurlDefinition( string $friendly, string $real, string $appTopLevel = NULL, bool $appIsDefault=FALSE, string $alias = NULL, bool $custom=FALSE, string $verify = NULL, array $seoTitles = NULL, bool $seoPagination = NULL ): array
	{
		/* Init */
		$return = array(
			'friendly'	=> $friendly,
			'real'		=> $real
		);
		if ( $verify )
		{
			$return['verify'] = $verify;
		}
		if ( $custom )
		{
			$return['custom'] = TRUE;
		}
		if ( $seoTitles )
		{
			$return['seoTitles'] = $seoTitles;
		}
		
		if ( $seoPagination )
		{
			$return['seoPagination'] = $seoPagination;
		}
		
		/* If it has a top-level (e.g. "/forums") we need to store the definition either with that if it's the default app, or
			without it if it isn't, so that if the default app changes we can redirect accordingly */
		if ( $appTopLevel )
		{
			if ( $appIsDefault )
			{
				$return['with_top_level'] = $appTopLevel . ( $friendly ? '/' . $friendly : '' );
			}
			else
			{
				$return['without_top_level'] = $friendly;
				$return['friendly'] = $appTopLevel . ( $friendly ? '/' . $friendly : '' );
			}
		}
		
		/* Figure out the regexes */
		$return['regex'] = array( preg_quote( $return['friendly'], '/' ) );
		if ( $alias )
		{
			$return['regex'][] = str_replace( '\{\!\}', '(?!&)(?:.+?)', preg_quote( $alias, '/' ) );
		}
		if ( isset( $return['without_top_level'] ) and $return['without_top_level'] )
		{
			$return['regex'][] = preg_quote( $return['without_top_level'], '/' );
		}
		elseif ( isset( $return['with_top_level'] ) )
		{
			$return['regex'][] = preg_quote( $return['with_top_level'], '/' );
		}
		
		/* Parse out variables */
		$return['params'] = array();
		preg_match_all( '/{(.+?)}/', $return['friendly'], $matches );
		foreach ( $matches[1] as $tag )
		{
			switch ( mb_substr( $tag, 0, 1 ) )
			{
				case '#':
					$return['regex'] = array_map( function( $_regex ) use ( $tag ) {
						return str_replace( preg_quote( '{#' . mb_substr( $tag, 1 ) . '}', '/' ), '(\d+?)', $_regex );
					}, $return['regex'] );
					$return['params'][] = mb_substr( $tag, 1 );
					break;
						
				case '@':
					$return['regex'] = array_map( function( $_regex ) use ( $tag ) {
						return str_replace( preg_quote( '{@' . mb_substr( $tag, 1 ) . '}', '/' ), '(.+?)', $_regex );
					}, $return['regex'] );
					$return['params'][] = mb_substr( $tag, 1 );
					break;
		
				case '?':
					$return['regex'] = array_map( function( $_regex ) use ( $tag ) {
						return preg_replace( '/\\\{\\\\\?\d*\\\}/', '(?![&\\/])(.+?)', $_regex );
					}, $return['regex'] );
					$return['params'][]	= '';
					break;
			}
		}
		
		/* Return */
		return $return;
	}
}