<?php
/**
 * @brief		Abstract class that Controllers should extend
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Dispatcher;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\DataLayer;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function get_called_class;
use function intval;
use function mb_stristr;
use const IPS\ENFORCE_ACCESS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract class that Controllers should extend
 */
abstract class Controller
{
	/**
	 * @brief	Base URL
	 */
	public mixed $url;

	/**
	 * @brief	Is this for displaying "content"? Affects if advertisements may be shown
	 */
	public bool $isContentPage = TRUE;

	/**
	 * @brief These properties are used to specify datalayer context properties.
	 *
	 * @example array(
		'community_area' => array( 'value' => 'search', 'odkUpdate' => 'true' )
	 * )
	 */
	public static array $dataLayerContext = array();
	
	/**
	 * Constructor
	 *
	 * @param Url|null $url		The base URL for this controller or NULL to calculate automatically
	 * @return	void
	 */
	public function __construct( mixed $url=NULL )
	{
		if ( $url === NULL )
		{
			$class		= get_called_class();
			$exploded	= explode( '\\', $class );
			$this->url = Url::internal( "app={$exploded[1]}&module={$exploded[4]}&controller={$exploded[5]}", Dispatcher::i()->controllerLocation );
		}
		else
		{
			$this->url = $url;
		}
	}

	/**
	 * Force a specific method within a controller to execute.  Useful for unit testing.
	 *
	 * @param string|null $method		The specific method to call
	 * @return	mixed
	 */
	public function forceExecute( ?string $method=NULL ): mixed
	{
		if( ENFORCE_ACCESS and $method !== null )
		{
			if ( method_exists( $this, $method ) )
			{
				return $this->$method();
			}
			else
			{
				$this->execute();
				return null;
			}
		}

		$this->execute();
		return null;
	}

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !empty( static::$dataLayerContext ) AND Settings::i()->core_datalayer_enabled AND !Request::i()->isAjax() )
		{
			foreach ( static::$dataLayerContext as $property => $data )
			{
				DataLayer::i()->addContextProperty( $property, $data['value'], $data['odkUpdate'] ?? false );
			}
		}

		if( Request::i()->do and preg_match( '/^[a-zA-Z\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', Request::i()->do ) )
		{
			if ( method_exists( $this, Request::i()->do ) or method_exists( $this, '__call' ) )
			{
				$method = Request::i()->do;
				$this->$method();
			}
			else
			{
				Output::i()->error( 'page_not_found', '2S106/1', 404, '' );
			}
		}
		else
		{
			if ( method_exists( $this, 'manage' ) or method_exists( $this, '__call' ) )
			{
				$this->manage();
			}
			else
			{
				Output::i()->error( 'page_not_found', '2S106/2', 404, '' );
			}
		}
	}
	
	/**
	 * Embed
	 *
	 * @return	void
	 */
	protected function embed() : void
	{
		$title		= Member::loggedIn()->language()->addToStack('error_title');
		$content	= null;
		
		if( !isset( static::$contentModel ) OR !in_array( 'IPS\Content\Embeddable', class_implements( static::$contentModel ) ) )
		{
			$output = Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedUnavailable();
		}
		else
		{			
	        try
	        {
	            $class = static::$contentModel;
	            $params = array();
	            	            
	            if( Request::i()->embedComment )
	            {
					$commentClass = $class::$commentClass;
					if ( isset( $class::$archiveClass ) )
					{
						$item = $class::load( Request::i()->id );
						if ( $item->isArchived() )
						{
							$commentClass = $class::$archiveClass;
						}
					}
					try
					{
						$content = $commentClass::load( Request::i()->embedComment );
						$title = $content->item()->mapped('title');
					}
					/* Comment wasn't found, but we can still link to the item */
					catch( OutOfRangeException $e )
					{
						$type = $commentClass::$title;
						$output = Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedCommentUnavailable( $type, $item );
						goto embedOutput;
					}
				}
				elseif( Request::i()->embedReview )
	            {
					$reviewClass = $class::$reviewClass;
					$item = $class::load( Request::i()->id );
					try
					{
						$content = $reviewClass::load( Request::i()->embedReview );
						$title = $content->item()->mapped('title');
					}
					/* Review wasn't found, but we can still link to the item */
					catch( OutOfRangeException $e )
					{
						$type = $reviewClass::$title;
						$output = Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedCommentUnavailable( $type, $item );
						goto embedOutput;
					}
				}
				else
				{
	                if ( isset( Request::i()->page ) and Request::i()->page > 1 )
	                {
		                $params['page'] = intval( Request::i()->page );
	                }
	                if ( isset( Request::i()->embedDo ) )
	                {
		                $params['do'] = Request::i()->embedDo;
	                }

	                $content = $class::load( Request::i()->id );
	                $title = $content instanceof Model ? $content->_title : $content->mapped( 'title' );
				}
				$output = $this->getEmbedOutput( $content, $params );
			}
			catch ( OutOfRangeException $e )
			{
				$output = Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedUnavailable();
			}
			catch( Exception $e )
			{
				Log::log( $e, 'embed_error' );
				$output = Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedNoPermission();
			}
		}
		embedOutput:

		/* Make sure our iframe contents get the necessary elements and JS */
		$js = array(
			Output::i()->js( 'js/commonEmbedHandler.js', 'core', 'interface' ),
			Output::i()->js( 'js/internalEmbedHandler.js', 'core', 'interface' )
		);
		Output::i()->base = '_parent';

		/* We need to keep any embed.css files that have been specified so that we can re-add them after we re-fetch the css framework */
		$embedCss = array();
		foreach( Output::i()->cssFiles as $cssFile )
		{
			if( mb_stristr( $cssFile, 'embed.css' ) )
			{
				$embedCss[] = $cssFile;
			}
		}

		/* We need to reset the included CSS files because by this point the responsive files are already in the output CSS array */
		Output::i()->cssFiles = array();
		Output::i()->responsive = FALSE;
		Front::baseCss();
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, $embedCss );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/embeds.css', 'core', 'front' ) );

		/* Seo Stuffs */
		Output::i()->title	= $title;

		if( $content !== NULL )
		{
			Output::i()->linkTags['canonical'] = (string) $content->url();
		}
		else
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}

		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core', 'front' )->embedInternal( $output, $js ) );
    }

	/**
	 * Returns the content item or the proper error message
	 *
	 * @param object $content
	 * @param array $params
	 *
	 * @return string
	 */
    protected function getEmbedOutput( object $content, array $params = array() ): string
	{
		if ( !$content->canView() )
		{
			$output = Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedNoPermission();
		}
		else if ( !in_array( 'IPS\Content\Embeddable', class_implements( $content ) ) )
		{
			$output = Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedUnavailable();
		}
		else
		{
			$output = $content->embedContent( $params );
		}

		return $output;
	}
}
