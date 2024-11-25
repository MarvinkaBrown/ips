<?php
/**
 * @brief		A HTMLPurifier Attribute Definition used for attributes which must be internal URLs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Mar 2016
 */

namespace IPS\Text;

/* To prevent PHP errors (extending class does not exist) revealing path */

use HTMLPurifier_AttrDef_URI;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use IPS\File;
use IPS\Http\Url;
use IPS\Http\Url\Exception as UrlException;
use IPS\Http\Url\Friendly;
use IPS\Http\Url\Internal;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * A HTMLPurifier Attribute Definition used for attributes which must be internal URLs
 */
class HtmlPurifierInternalLinkDef extends HTMLPurifier_AttrDef_URI
{
	/**
	 * @brief	Allowed bases
	 */
	protected ?array $allowedBases = NULL;
	
	/**
	 * Constructor
	 *
     * @param	bool		$embeds_resource		Does the URI here result in an extra HTTP request?
     * @param array|null $allowedBases			If an array is provided, only URLs with the query strings set allowed - for example array( array( 'app' => 'core', 'module' => 'members', 'controller' => 'profile' ) ) will only allow URLs to profiles. If NULL, any internal URL beside the open proxy and attachment downloads is allowed
     */
    public function __construct( bool $embeds_resource=FALSE, array $allowedBases = NULL )
    {
	    $this->allowedBases = $allowedBases;
        return parent::__construct( $embeds_resource );
    }
	
	/**
	 * Validate
	 * 
     * @param	string					$uri
     * @param	HTMLPurifier_Config	$config
     * @param	HTMLPurifier_Context	$context
     * @return	bool|string
     */
    public function validate($uri, $config, $context): bool|string
	{
	    /* Create the URL */
		try
		{
			$url = Url::createFromString( str_replace( array( '%7B___base_url___%7D/', '{___base_url___}/' ), Settings::i()->base_url, $uri ) );
		}
		catch( UrlException $e )
		{
			return FALSE;
		}

	    /* If it's not internal, we can stop now */
	    if ( !( $url instanceof Internal ) )
	    {
		    return FALSE;
	    }
	    	    
	    /* If we have allowed templates, check those... */
	    if ( $this->allowedBases )
	    {
		    $isOkay = FALSE;
		    $queryString = ( $url instanceof Friendly ) ? ( $url->hiddenQueryString + $url->queryString ) : $url->queryString;
		    
		    foreach ( $this->allowedBases as $requiredQueryString )
		    {
			    $queryStringDiff = array_diff( $requiredQueryString, $queryString );
			    if ( empty( $queryStringDiff ) )
			    {
				    $isOkay = TRUE;
				    break;
			    }
		    }
		    
		    if ( !$isOkay )
		    {
			    return FALSE;
		    }
	    }

	    /* Otherwise exclude the open proxy */
	    if ( ( isset( $url->queryString['section'] ) and $url->queryString['section'] == 'redirect' ) or ( isset( $url->queryString['controller'] ) and $url->queryString['controller'] == 'redirect' ) )
	    {
		    return FALSE;
	    }

	    /* And exclude the attachments downloading gateway */
	    if( mb_strpos( preg_replace( '#/{1,}#', '/', $url->data['path'] ), 'applications/core/interface/file/' ) !== FALSE )
	    {
	    	return FALSE;
	    }

	    /* And exclude uploaded files (we do this by checking the beginning of the (protocol-relative) URL against all upload configurations) */
	    $url = '//' . mb_substr( (string) $url, mb_strpos( (string) $url, '://' ) + 3 );

	    foreach( static::getUploadUrls() as $uploadBaseUrl )
	    {
	    	if( mb_strpos( $url, '//' . mb_substr( $uploadBaseUrl, mb_strpos( $uploadBaseUrl, '://' ) + 3 ) ) === 0 )
	    	{
	    		return FALSE;
	    	}
	    }
	    	    
	    /* Still here? We can pass up */
	    return parent::validate( $uri, $config, $context );
    }

    /**
     * @brief	Uploaded file base URLs (i.e. URL to uploads directory)
     */
    protected static ?array $uploadUrls	= NULL;

	/**
	 * Fetch and cache upload URLs
	 *
	 * @return array|null
	 */
    protected static function getUploadUrls(): ?array
	{
    	if( static::$uploadUrls !== NULL )
    	{
    		return static::$uploadUrls;
    	}

    	static::$uploadUrls	= array();

		foreach( File::getStore() as $configuration )
		{
			$class = File::getClass( $configuration['id'] );

			if( $class->baseUrl() !== NULL )
			{
				$url = new Url( $class->baseUrl() );

				if( $url instanceof Internal )
				{
					static::$uploadUrls[ $class->baseUrl() ]	= $class->baseUrl();
				}
			}
		}

    	return static::$uploadUrls;
    }
}