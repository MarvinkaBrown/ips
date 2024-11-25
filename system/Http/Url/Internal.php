<?php
/**
 * @brief		Internal URL
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 May 2015
 */

namespace IPS\Http\Url;
 
/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content;
use IPS\Http\Url;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\cms\Pages\Router;
use IPS\Session;
use IPS\Application;
use OutOfRangeException;
use function count;
use function defined;
use function intval;
use function is_numeric;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Internal URL
 */
class Internal extends Url
{
	/**
	 * Create internal URL from components - all arguments should be UNENCODED
	 *
	 * @param string $base		Key for the URL base
	 * @param int|string|null $protocol	Protocol (one of the PROTOCOL_* constants) or an actual scheme
	 * @param string $path		Path, relative to site's base URL
	 * @param array|string|null $query		Query
	 * @param string|null $fragment	Fragment
	 * @return	Url
	 */
	protected static function createInternalFromComponents( string $base, int|string|null $protocol, string $path, array|string $query = NULL, string $fragment = NULL ): Url
	{		
		$baseUrlData = parse_url( static::baseUrl( is_numeric( $protocol ) ? $protocol : NULL ) );
		
		$pathToUse = '';
		if ( isset( $baseUrlData['path'] ) and $baseUrlData['path'] )
		{
			$pathToUse .= trim( $baseUrlData['path'], '/' );
		}
		if ( $path or $query or $fragment or $pathToUse )
		{
			if ( $pathToUse )
			{
				$pathToUse .= '/';
			}
			$pathToUse .= ltrim( $path, '/' );
		}
								
		$return = static::createFromComponents(
			$baseUrlData['host'],
			( is_string( $protocol ) and !is_numeric( $protocol ) ) ? $protocol : ( ( isset( $baseUrlData['scheme'] ) AND $protocol !== NULL ) ? $baseUrlData['scheme'] : NULL ),
			$pathToUse ? "/{$pathToUse}" : '',
			$query,
			$baseUrlData['port'] ?? NULL,
			$baseUrlData['user'] ?? NULL,
			$baseUrlData['pass'] ?? NULL,
			$fragment
		);
		
		$return->base = $base;
		return $return;
	}
	
	/**
	 * @brief	Does this URL have the SEO pagination (page/n/)?
	 */
	public ?bool $seoPagination = NULL;
	
	/**
	 * @brief	Base
	 */
	public ?string $base = NULL;
	
	/**
	 * Get the friendly URL for this URL if there is one
	 *
	 * @return	mixed	The friendly URL if there is one, TRUE if there isn't, or NULL if not sure
	 */
	public function correctFriendlyUrl(): mixed
	{
		$return = FALSE;

		foreach (Friendly::furlDefinition() as $seoTemplate => $furlDefinition )
		{
			if( mb_stripos( $this->data[ static::COMPONENT_QUERY ], $furlDefinition['real'] ) !== FALSE )
			{
				/* Figure out if this FURL definition requires extra data.
					Example: messenger_convo and messenger have the same $furlDefinition['real'] definition, but messenger_convo requires an 'id' parameter too */
				$params	= array();
				preg_match_all( '/{(.+?)}/', $furlDefinition['friendly'], $matches );
				foreach ( $matches[1] as $tag )
				{
					switch ( mb_substr( $tag, 0, 1 ) )
					{
						case '#':
						case '@':
							$params[] = mb_substr( $tag, 1 );
							break;
					}
				}

				/* If this definition requires a parameter, see if we have it.  If not, skip to next definition to check. */
				if( count( array_diff( $params, array_keys( $this->queryString ) ) ) )
				{
					continue;
				}

				/* Now try to check URL */
				try
				{
					/* If there's a "verify" method, we can use that to get the correct URL */
					if( !empty( $furlDefinition['verify'] ) )
					{
						/* Load it */
						try
						{
							if ( ! $return = $this->correctUrlFromVerifyClass( $furlDefinition['verify'] ) )
							{
								$return = NULL;
							}
						}
						/* It doesn't exist */
						catch ( OutOfRangeException $e )
						{
							$return = NULL;
						}
					}
					/* If not, we have to build it ourself */
					else
					{
						$seoTitles = array();
						if ( isset( $furlDefinition['seoTitles'] ) )
						{
							foreach ( $furlDefinition['seoTitles'] as $seoTitleData )
							{
								try
								{
									$class = $seoTitleData['class'];
									$queryParam = $seoTitleData['queryParam'];
									$property = $seoTitleData['property'];
																						
									$seoTitles[] = $class::load( $this->queryString[ $queryParam ] )->$property;
								}
								catch ( OutOfRangeException $e ) {}
							}
						}
						
						$return = Url::internal( $this->data['query'], 'front', $seoTemplate, $seoTitles );
					}
				}
				catch ( Exception $e ) {}
			}
		}

		/* If it it thinks it belongs to "pages", we might be able to be more accurate */
		if ( Application::appIsEnabled( 'cms' ) and ( $return instanceof Friendly and $return->seoTemplate === 'content_page_path' and isset( $this->queryString['path'] ) ) )
		{
			/* Try to find a page */
			try
			{
				/* Create it */
				$correctUrl = Router::loadFromUrl( $this )->url();

				/* Set extra stuff in our query string */
				$paramsToSet = array();
				foreach ( $this->queryString as $k => $v )
				{
					if ( !array_key_exists( $k, $correctUrl->queryString ) and !array_key_exists( $k, $correctUrl->hiddenQueryString ) )
					{
						$paramsToSet[ $k ] = $v;
					}
				}
				if ( count( $paramsToSet ) )
				{
					$correctUrl = $correctUrl->setQueryString( $paramsToSet );
				}

				/* Return */
				$return = $correctUrl;
			}
				/* Couldn't find one? Don't accept responsibility */
			catch (OutOfRangeException $e ){}
		}
		else if( $return !== NULL )
		{
			$return = TRUE;
		}

		return $return;
	}
	
	/**
	 * Get the correct URL using a "verify" property from the FURL definition
	 *
	 * @param mixed $verify	Class containing `preCorrectUrlFromVerifyClass()` method to check URL against
	 * @return	NULL|Url
	 * @throws	OutOfRangeException
	 */
	protected function correctUrlFromVerifyClass( mixed $verify ): ?Url
	{
		/* Load it */
		if ( method_exists( $verify, 'preCorrectUrlFromVerifyClass' ) )
		{
			$verify::preCorrectUrlFromVerifyClass( $this );
		}
		$contentObject = $verify::loadFromUrl( $this );
		
		/* Check the currently logged in user can view it, as we don't want to reveal the correct
			URL to something the user has no permission to view */
		$canView = TRUE;
		if ( $contentObject instanceof Content or $contentObject instanceof Club )
		{
			$canView = $contentObject->canView();
		}
		elseif ( $contentObject instanceof Model )
		{
			$canView = $contentObject->can('view');
		}
		if ( !$canView )
		{
			return NULL;
		} 
		
		/* Create it */
		$correctUrl = $contentObject->url();
		
		/* Set extra stuff in our query string */
		$paramsToSet = array();
		foreach ( $this->queryString as $k => $v )
		{
			if ( !array_key_exists( $k, $correctUrl->queryString ) and !array_key_exists( $k, $correctUrl->hiddenQueryString ) )
			{
				$paramsToSet[ $k ] = $v;
			}
		}
		if ( count( $paramsToSet ) )
		{
			$correctUrl = $correctUrl->setQueryString( $paramsToSet );
		}
		
		/* Return */
		return $correctUrl;
	}
	
	/**
	 * Add CSRF check to query string
	 *
	 * @return	Url
	 */
	public function csrf(): Url
	{
		return $this->setQueryString( 'csrfKey', Session::i()->csrfKey );
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
		/* Remove any existing page param */
		$url = $this->stripQueryString( $param );
		
		if ( intval( $number ) > 1 )
		{
			$url = $this->setQueryString( $param, $number );
		}
		
		return $url;
	}
	
	/**
	 * Get ACP query string without adsess
	 *
	 * @return	string
	 */
	public function acpQueryString(): string
	{
		$queryString = $this->queryString;
		unset( $queryString['csrf'] );
		return static::convertQueryAsArrayToString( $queryString );
	}
	
	/**
	 * Check if the URL is an open redirect.
	 * Useful if you are checking if a given URL is internal to ensure it also doesn't redirect elsewhere
	 *
	 * @return	bool
	 */
	public function openRedirect(): bool
	{
		return isset( $this->queryString['app'] ) and $this->queryString['app'] === 'core' and isset( $this->queryString['module'] ) and $this->queryString['module'] === 'system' and isset( $this->queryString['controller'] ) and $this->queryString['controller'] === 'redirect';
	}
}