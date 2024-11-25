<?php
/**
 * @brief		Embed iframe display
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Sep 2014
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Content\Controller;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\Text\Parser;
use IPS\Theme;
use OutOfRangeException;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Embed iframe display
 */
class embed extends Controller
{
	/**
	 * Embed iframe display
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		/* Check cache for the current scheme */
		$scheme = Theme::i()->getCurrentCSSScheme();
		$cacheKey = 'embed_' . md5( Request::i()->url ) . "_" . $scheme;
		try
		{
			$return = Cache::i()->getWithExpire( $cacheKey, TRUE );
		}
		/* Not in cache - fetch */
		catch ( OutOfRangeException $e )
		{
			try
			{
				$return = Parser::embeddableMedia( Url::createFromString( Request::i()->url, FALSE, TRUE ), TRUE );
			}
			catch( UnexpectedValueException $e )
			{
				$return	= '';
			}

			/* And cache */
			Cache::i()->storeWithExpire( $cacheKey, $return, DateTime::create()->add( new DateInterval('P1D') ), TRUE );
		}

		/* Output */
		$js = array(
			Output::i()->js( 'js/commonEmbedHandler.js', 'core', 'interface' ),
			Output::i()->js( 'js/externalEmbedHandler.js', 'core', 'interface' )
		);

		/* We don't want search engines indexing this */
		Output::i()->metaTags['robots'] = 'noindex';

		/* Intentionally replace the cssFiles array with a single file here, since we don't need the complete CSS framework in external embeds */
		Output::i()->cssFiles = array_merge(
			Theme::i()->css( 'styles/embeds.css', 'core', 'front' ),
			Theme::i()->css( "framework/1-2-settings.css", "core", "global" ),
			Theme::i()->css( "framework/1-3-variables.css", "core", "global" ),
			Theme::i()->css( "framework/1-4-colors.css", "core", "global" ),
			Theme::i()->css( "core.css", "core", "front" ),
		);

		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core', 'front' )->embedExternal( $return, $js ) );
	}
}
