<?php
/**
 * @brief		Front-end Dispatcher
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Dispatcher;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content\Search\SearchContent;
use IPS\core\Alerts\Alert;
use IPS\core\IndexNow;
use IPS\core\Rss;
use IPS\Data\Cache;
use IPS\Data\Cache\None;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Developer;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Http\Url\Internal;
use IPS\Log;
use IPS\Login;
use IPS\Member;
use IPS\Member\Device;
use IPS\MFA\MFAHandler;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session\Front as Session;
use IPS\Settings;
use IPS\Sitemap;
use IPS\Theme;
use IPS\Widget;
use IPS\Widget\Area;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function mb_substr;
use function strstr;
use const IPS\CACHING_LOG;
use const IPS\CIC;
use const IPS\ENFORCE_ACCESS;
use const IPS\QUERY_LOG;
use const IPS\REDIS_LOG;
use const IPS\UPGRADING_PAGE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front-end Dispatcher
 */
class Front extends Standard
{
	/**
	 * Controller Location
	 */
	public string $controllerLocation = 'front';

	/**
	 * @brief Module
	 */
	protected ?string $_module = NULL;

	/**
	 * @brief Controller
	 */
	protected ?string $_controller = NULL;

	/**
	 * @var int|null $currentPage
	 */
	public int|null $currentPage = null;

	/**
	 * Init
	 *
	 * @return    void
	 * @throws Exception
	 */
	public function init() : void
	{
		/* Set up in progress? */
		if ( isset( Settings::i()->setup_in_progress ) AND Settings::i()->setup_in_progress )
		{
			$protocol = '1.0';
			if( isset( $_SERVER['SERVER_PROTOCOL'] ) and strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false )
			{
				$protocol = '1.1';
			}

			/* Don't allow the setup in progress page to be cached, it will only be displayed for a very short period of time */
			foreach( Output::getNoCacheHeaders() as $headerKey => $headerValue )
			{
				header( "{$headerKey}: {$headerValue}" );
			}
			
			if ( CIC and ! Session::loggedIn() and ! Session::i()->userAgent->bot )
			{
				/* The software is unavailable, but the site is up so we do not want to affect our cloud downtime statistics and trigger monitoring alarms
				   if we are not a search engine */
				header( "HTTP/{$protocol} 200 OK" );
			}
			else
			{
				header( "HTTP/{$protocol} 503 Service Unavailable" );
				header( "Retry-After: 300"); #5 minutes
			}
					
			require \IPS\ROOT_PATH . '/' . UPGRADING_PAGE;
			exit;
		}

		/* Sync stuff when in developer mode */
		if ( \IPS\IN_DEV )
		{
			 Developer::sync();
		}

		/* Perform some legacy URL conversions - Need to do this before checking furl in case app name has changed */
		static::convertLegacyParameters();
		
		/* Base CSS */
		static::baseCss();

		/* Base JS */
		static::baseJs();

		/* Get the current page, if any */
		if ( isset( Request::i()->page ) and is_numeric( Request::i()->page ) )
		{
			$this->currentPage = Request::i()->page;
		}
		else if ( isset( Request::i()->url()->hiddenQueryString['page'] ) and is_numeric( Request::i()->url()->hiddenQueryString['page'] ) and $page = Request::i()->url()->hiddenQueryString['page'] )
		{
			$this->currentPage = $page;
		}

		/* Check friendly URL and whether it is correct */
		try
		{
			$this->checkUrl();
		}
		catch( OutOfRangeException $e )
		{
			/* Display a 404 */
			$this->application = Application::load('core');
			$this->setDefaultModule();
			if ( Member::loggedIn()->isBanned() )
			{
				Output::i()->sidebar = [];
				Output::i()->bodyClasses[] = 'ipsLayout_minimal';
			}
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'app.js' ) );
			Output::i()->error( 'requested_route_404', '1S160/2', 404, '' );
		}

		/* Check if we're a low quality (no search engine bot) user */
		if ( ! Member::loggedIn()->member_id and \IPS\Session\Front::i()->userAgent->isLowValue() )
		{
			/* We want to restrict the number of 301s low quality bots get */
			foreach( [ 'findComment', 'findReview', 'getNewComment', 'getLastComment'] as $do )
			{
				if ( isset( Request::i()->do ) and Request::i()->do === $do ) // baby shark
				{
					unset( Request::i()->do );
				}
			}
		}

		/* Run global init */
		try
		{
			parent::init();
		}
		catch ( DomainException $e )
		{
			// If this is a "no permission", and they're validating - show the validating screen instead
			if( $e->getCode() === 6 and Member::loggedIn()->member_id and Member::loggedIn()->members_bitoptions['validating'] )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register&do=validating', 'front', 'register' ) );
			}
			// Otherwise show the error
			else
			{
				Output::i()->error( $e->getMessage(), '2S100/' . $e->getCode(), $e->getCode() === 4 ? 403 : 404, '' );
			}
		}

		/* Loader extension - check for redirects */
		foreach( Application::allExtensions( 'core', 'Loader' ) as $loader )
		{
			/* First come first serve here */
			if( $redirect = $loader->checkForRedirect() )
			{
				Output::i()->redirect( $redirect );
			}
		}

		$this->_setReferralCookie();
		
		/* Enable sidebar by default (controllers can turn it off if needed) */
		Output::i()->sidebar['enabled'] = !Request::i()->isAjax();
		
		/* Add in RSS Feeds */
		foreach( Rss::getStore() AS $feed_id => $feed )
		{
			$feed = Rss::constructFromData( $feed );

			if ( $feed->_enabled AND ( $feed->groups == '*' OR Member::loggedIn()->inGroup( $feed->groups ) ) )
			{
				Output::i()->rssFeeds[ $feed->_title ] = $feed->url();
			}
		}
		
		/* Are we online? */
		if ( !Settings::i()->site_online and !Member::loggedIn()->group['g_access_offline'] and $this->controllerLocation == 'front' and !$this->application->allowOfflineAccess( $this->module, $this->controller, Request::i()->do ) )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( Member::loggedIn()->language()->addToStack( 'offline_unavailable', FALSE, array( 'sprintf' => array( Settings::i()->board_name ) ) ), 503 );
			}
			
			Output::i()->showOffline();
		}
		
		/* Member Ban? */

		/* IP Ban check happens only the Login and Register Controller for guests */
		$ipBanned = FALSE;
		if( Member::loggedIn()->member_id OR in_array( $this->controller, array( 'register', 'login' ) ) )
		{
			$ipBanned = Request::i()->ipAddressIsBanned();
		}

		if ( $ipBanned or $banEnd = Member::loggedIn()->isBanned() )
		{
			if ( !$ipBanned and !Member::loggedIn()->member_id )
			{
				if ( $this->notAllowedBannedPage() )
				{
					$url = Url::internal( 'app=core&module=system&controller=login', 'front', 'login' );
					
					if ( Request::i()->url() != Settings::i()->base_url AND !isset( Request::i()->_mfaLogin ) )
					{
						$url = $url->setQueryString( 'ref', base64_encode( Request::i()->url() ) );
					}
					else if ( isset( Request::i()->_mfaLogin ) )
					{
						$url = $url->setQueryString( '_mfaLogin', 1 );
					}
					
					Output::i()->redirect( $url );
				}
			}
			else
			{
				Output::i()->sidebar = [];
				Output::i()->bodyClasses[] = 'ipsLayout_minimal';
				if( !$this->application->allowBannedAccess( $this->module, $this->controller, Request::i()->do ?? null ) )
				{
					Output::i()->showBanned();
				}
			}
		}
		
		/* Do we need more info from the member or do they need to validate? */
		if( Member::loggedIn()->member_id and !$this->application->skipDoMemberCheck( $this->module, $this->controller, Request::i()->do ?? null ) )
		{
			if ( $url = static::doMemberCheck() )
			{
				Output::i()->redirect( $url );
			}
		}
		
		/* Permission Check */
		try
		{
			if ( !Member::loggedIn()->canAccessModule( $this->module ) )
			{
				if ( !Member::loggedIn()->member_id and isset( Request::i()->_mfaLogin ) )
				{
					Output::i()->redirect( Url::internal( "app=core&module=system&controller=login", 'front', 'login' )->setQueryString( '_mfaLogin', 1 ) );
				}
				Output::i()->error( ( Member::loggedIn()->member_id ? 'no_module_permission' : 'no_module_permission_guest' ), '2S100/2', 403, 'no_module_permission_admin' );
			}
		}
		catch( InvalidArgumentException $e ) # invalid module
		{
			Output::i()->error( 'requested_route_404', '2S160/5', 404, '' );
		}

		/* Set up isAnonymous variable for realtime */
		Output::i()->jsVars['isAnonymous'] = Member::loggedIn()->isOnlineAnonymously();

		/* Stuff for output */
		if ( !Request::i()->isAjax() )
		{
			/* Base Navigation. We only add the module not the app as most apps don't have a global base (for example, in Nexus, you want "Store" or "Client Area" to be the base). Apps can override themselves in their controllers. */
			foreach( Application::applications() as $directory => $application )
			{
				if( $application->default )
				{
					$defaultApplication	= $directory;
					break;
				}
			}

			if( !isset( $defaultApplication ) )
			{
				$defaultApplication = 'core';
			}
			
			if ( $this->module->key != 'system' AND $this->application->directory != $defaultApplication )
			{
				Output::i()->breadcrumb['module'] = array( Url::internal( 'app=' . $this->application->directory . '&module=' . $this->module->key . '&controller=' . $this->module->default_controller, 'front', array_key_exists( $this->module->key, Url::furlDefinition() ) ?  $this->module->key : NULL ), $this->module->_title );
			}

			/* Figure out what the global search is */
			foreach ( $this->application->extensions( 'core', 'ContentRouter' ) as $object )
			{
				if ( count( $object->classes ) === 1 )
				{
					$classes = $object->classes;
					foreach ( $classes as $class )
					{
						if ( SearchContent::isSearchable( $class ) and $this->module->key == $class::$module )
						{
							$type = mb_strtolower( str_replace( '\\', '_', mb_substr( array_pop( $classes ), 4 ) ) );
							
							/* If not the default app, set default search option to current app */
							if ( ! mb_stristr( $type, $defaultApplication ) )
							{
								Output::i()->defaultSearchOption = array( $type, "{$type}_pl" );
							}
							break;
						}
					}
				}
			}
		}

		Widget\Request::reset();
	}

	/**
	 * Set the referral cookie if appropriate
	 *
	 * @return void
	 */
	protected function _setReferralCookie() : void
	{
		/* Set a referral cookie */
		if( Settings::i()->ref_on and isset( Request::i()->_rid ) )
		{
			Request::i()->setCookie( 'referred_by', intval( Request::i()->_rid ), DateTime::create()->add( new DateInterval( 'P1Y' ) ) );
		}
	}

	/**
	  * Check whether the URL we visited is correct and route appropriately
	  *
	  * @return void
	  */
	protected function checkUrl() : void
	{
		/* Handle friendly URLs */
		if ( Settings::i()->use_friendly_urls )
		{
			$url = Request::i()->url();

			/* Redirect to the "correct" friendly URL if there is one */
			if ( !Request::i()->isAjax() and mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and !ENFORCE_ACCESS )
			{
				$correctUrl = NULL;
				
				/* If it's already a friendly URL, we need to check the SEO title is valid. If it isn't, we redirect iof "Force Friendly URLs" is enabled */
				if ( $url instanceof Friendly or ( $url instanceof Internal and Settings::i()->seo_r_on ) )
				{
					$correctUrl = $url->correctFriendlyUrl();
				}
				

				if ( !( $correctUrl instanceof Url ) and $url instanceof Internal )
				{
					$pathFromBaseUrl = ltrim( mb_substr( $url->data[ Url::COMPONENT_PATH ], mb_strlen( Url::internal('')->data[ Url::COMPONENT_PATH ] ) ), '/' );

					/* If they are accessing "index.php/whatever", we want "index.php?/whatever */
					if ( mb_strpos( $url->data[ Url::COMPONENT_PATH ], '/index.php/' ) !== FALSE )
					{
						if ( mb_substr( $pathFromBaseUrl, 0, 10 ) === 'index.php/' )
						{
							$correctUrl = Friendly::friendlyUrlFromComponent( 0, trim( mb_substr( $pathFromBaseUrl, 10 ), '/' ), $url->queryString );
						}
					}
					else
					{
						/* If necessary, return any special cases like the robots.txt file */
						$this->customResponse( $pathFromBaseUrl );
					}
				}

				/* Redirect to the correct URL if we got one */
				if ( $correctUrl instanceof Url )
				{
					if( $correctUrl->seoPagination and in_array( 'page', array_keys( $url->hiddenQueryString ) ) )
					{
						$correctUrl = $correctUrl->setPage( 'page', $url->hiddenQueryString['page'] );
					}
					Output::i()->redirect( $correctUrl, NULL );
				}

				/* Check pagination */
				if ( $url instanceof Friendly and $url->seoPagination and in_array( 'page', array_keys( $url->queryString ) ) )
				{
					Output::i()->redirect( $url->setPage( 'page', (int) $url->queryString['page'] )->stripQueryString('page'), NULL );
				}
			}
			
			/* If the accessed URL is friendly, set the "real" query string properties */
			if ( $url instanceof Friendly )
			{
				foreach ( ( $url->queryString + $url->hiddenQueryString ) as $k => $v )
				{
					if( $k == 'module' )
					{
						$this->_module	= NULL;
					}
					else if( $k == 'controller' )
					{
						$this->_controller	= NULL;
					}
					
					/* If this is a POST request, and this key has already been populated, do not overwrite it as this allows form input to be ignored and the query string data used */
					if ( Request::i()->requestMethod() == 'POST' and isset( Request::i()->$k ) )
					{
						continue;
					}
					
					Request::i()->$k = $v;
				}
			}
			/* Otherwise if it's not a recognised URL, show a 404 */
			elseif ( !( $url instanceof Internal ) or $url->base !== 'front' )
			{
				/* Call the parent first in case we need to redirect to https, and so the correct locale, etc. is set */
				try
				{
					parent::init();
				}
				catch ( Exception $e ) { }
				
				throw new OutOfRangeException;
			}
		}
	}

	/**
	 * Define that the page should load even if the user is banned and not logged in
	 *
	 * @return	bool
	 */
	protected function notAllowedBannedPage(): bool
	{
		return !Member::loggedIn()->group['g_view_board'] and !$this->application->allowGuestAccess( $this->module, $this->controller, Request::i()->do );
	}

	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public static function convertLegacyParameters() : void
	{
		foreach( Application::applications() as $directory => $application )
		{
			if ( $application->_enabled )
			{
				if( method_exists( $application, 'convertLegacyParameters' ) )
				{
					$application->convertLegacyParameters();
				}
			}
		}
	}

	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish() : void
	{
        Bridge::i()->frontDispatcherFinish();

		/* Sidebar Widgets */
		if( !Request::i()->isAjax() )
		{
			/**
			 * @var Area[] $widgets
			 */
			$widgets = array();
			
			if ( ! isset( Output::i()->sidebar['widgets'] ) OR ! is_array( Output::i()->sidebar['widgets'] ) )
			{
				Output::i()->sidebar['widgets'] = array();
			}

			try
			{
				$widgetConfig = Db::i()->select( '*', 'core_widget_areas', array( '(app=? AND module=? AND controller=?) OR (app=? AND module=? AND controller=?)', 'global', 'global', 'global', $this->application->directory, $this->module->key, $this->controller ) );
				foreach( $widgetConfig as $row )
				{
					if( $row['tree'] )
					{
						$widgets[$row['area']] = new Area( json_decode( $row['tree'], true ), $row['area'] );
					}
					elseif( $row['widgets'] )
					{
						$widgets[$row['area']] = Area::create( $row['area'], json_decode( $row['widgets'], true ) );
					}
				}
			}
			catch ( UnderflowException $e ) {}
			
			if ( Output::i()->allowDefaultWidgets )
			{
				$defaultAreas = [];
				foreach ( Widget::appDefaults( $this->application ) as $widget )
				{
					$areaKey = $widget['default_area'];

					/* If another app has already defined this area or the area has been filled by the page builder, don't overwrite it */
					if ( isset( $widgets[ $areaKey ] ) and !isset( $defaultAreas[ $areaKey ] ) )
					{
						continue;
					}

					$widget['unique'] = $widget['key'];
					if ( !isset( $widgets[ $areaKey ] ) )
					{
						$defaultAreas[ $areaKey ] = 1;
						$widgets[ $areaKey ] = Area::create( $widget['default_area'], [ $widget ] );
					}
					else
					{
						$widgets[ $areaKey ]->addWidget( $widget );
					}
				}
			}
					
			if ( count( $widgets ) )
			{
				if ( ( Cache::i() instanceof None ) )
				{
					$templateLoad = array();
					foreach ( $widgets as $areaKey => $area )
					{
						foreach ( $area as $widget )
						{
							if ( isset( $widget['app'] ) and $widget['app'] )
							{
								$templateLoad[] = array( $widget['app'], 'front', 'widgets' );
								$templateLoad[] = 'template_' . Theme::i()->id . '_' . Theme::makeBuiltTemplateLookupHash( $widget['app'], 'front', 'widgets' ) . '_widgets';
							}
						}
					}
	
					if ( count( $templateLoad ) )
					{
						Store::i()->loadIntoMemory( $templateLoad );
					}
				}
				
				$widgetObjects = array();
				$storeLoad = array();
				$googleFonts = array();
				foreach ( $widgets as $areaKey => $area )
				{
					Output::i()->sidebar['widgetareas'][$areaKey] = $area;
					foreach ( $area->getAllWidgets() as $widget )
					{
						try
						{
							$appOrPlugin = Application::load( $widget['app'] );

							if( !$appOrPlugin->enabled )
							{
								continue;
							}
							
							$_widget = Widget::load( $appOrPlugin, $widget['key'], ( ! empty($widget['unique'] ) ? $widget['unique'] : mt_rand() ), ( isset( $widget['configuration'] ) ) ? $widget['configuration'] : array(), ( $widget['restrict'] ?? null ), ( $areaKey == 'sidebar' ) ? 'vertical' : 'horizontal', $widget['layout'] );
							if ( ( Cache::i() instanceof None ) and isset( $_widget->cacheKey ) )
							{
								$storeLoad[] = $_widget->cacheKey;
							}

							if( $_widget->isBuilderWidget() )
							{
								if ( ! empty( $_widget->configuration['widget_adv__font'] ) and $_widget->configuration['widget_adv__font'] !== 'inherit' )
								{
									$font = $_widget->configuration['widget_adv__font'];

									if ( mb_substr( $font, -6 ) === ' black' )
									{
										$fontWeight = 900;
										$font = mb_substr( $font, 0, -6 ) . ':400,900';
									}

									$googleFonts[ $font ] = $font;
								}
							}

							$widgetObjects[ $areaKey ][] = $_widget;
						}
						catch ( Exception $e )
						{
							Log::log( $e, 'dispatcher' );
						}
					}
				}

				if ( count( $googleFonts ) )
				{
					Output::i()->linkTags['googlefonts'] = array('rel' => 'stylesheet', 'href' => "https://fonts.googleapis.com/css?family=" . implode( "|", array_values( $googleFonts ) ) . "&display=swap");
				}

				if( ( Cache::i() instanceof None ) and count( $storeLoad ) )
				{
					Store::i()->loadIntoMemory( $storeLoad );
				}
				
				foreach ( $widgetObjects as $areaKey => $_widgets )
				{
					foreach ( $_widgets as $_widget )
					{
						Output::i()->sidebar['widgets'][ $areaKey ][] = $_widget;
					}
				}
			}
		}

		/* Meta tags */
		Output::i()->buildMetaTags();

		/* Check MFA */
		$this->checkMfa();

		/* Check Alerts */
		$this->checkAlerts();

		/* Loader Extension */
		foreach( Application::allExtensions( 'core', 'Loader' ) as $loader )
		{
			$loader->onFinish();
		}
		
		/* Finish */
		parent::finish();
	}

	/**
	 * Check MFA to see if we need to supply a code. If the member elected to cancel, cancel (and redirect) here
	 *
	 * @param boolean $return Return any HTML (true) or add to Output (false)
	 *
	 * @return string|null
	 * @throws Exception
	 */
	public function checkMfa( bool $return=FALSE ) : ?string
	{
		/* MFA Login? */
		if ( isset( Request::i()->_mfaLogin ) and isset( $_SESSION['processing2FA'] ) and $member = Member::load( $_SESSION['processing2FA']['memberId'] ) and $member->member_id )
		{
			$device = Device::loadOrCreate( $member, FALSE );
			if ( $output = MFAHandler::accessToArea( 'core', $device->known ? 'AuthenticateFrontKnown' : 'AuthenticateFront', Request::i()->url(), $member ) )
			{
				/* Did we just cancel? */
				if ( Request::i()->_mfaCancel and ( ! Member::loggedIn()->member_id or ( Member::loggedIn()->member_id === $member->member_id ) ) )
				{
					/* We don't need this until we re-enter the MFA flow again */
					unset( $_SESSION['processing2FA'] );

					/* Is MFA required for this member? */
					$mfaRequired = Settings::i()->mfa_required_groups === '*' or $member->inGroup( explode( ',', Settings::i()->mfa_required_groups ) );

					/* Does this member require MFA upon login? */
					$logout = ( Settings::i()->mfa_required_prompt === 'immediate' and $mfaRequired );

					/* Can they see this page without MFA? */
					if ( !$mfaRequired OR ( $logout and $this->application->allowGuestAccess( $this->module, $this->controller, Request::i()->do ) ) )
					{
						$redirectUrl = Request::i()->url()->stripQueryString([ '_mfaCancel', '_mfaLogin', '_fromLogin', 'csrfKey' ]);
					}
					else
					{
						$redirectUrl = Url::internal( '' );
					}

					if ( $logout )
					{
						Login::logout( $redirectUrl );
						$redirectUrl = $redirectUrl->setQueryString( '_fromLogout', 1 );
					}

					Output::i()->redirect( $redirectUrl );
				}

				if ( $return )
				{
					return $output;
				}
				
				Output::i()->output .= $output;
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login&do=mfa', 'front', 'login' ) );
			}
		}

		return null;
	}

	/**
	 *  Show a robots.txt file if configured to do so
	 *
	 * @param string $pathFromBaseUrl
	 * @return void
	 */
	public function customResponse( string $pathFromBaseUrl ) : void
	{
		if ( $pathFromBaseUrl === 'robots.txt' )
		{
			$this->robotsTxt();
		}
		else if ( $pathFromBaseUrl === 'ads.txt' )
		{
			$this->adsTxt();
		}
		else if ( IndexNow::i()->isEnabled() AND $pathFromBaseUrl === IndexNow::i()->getKeyFileName() )
		{
			$this->indexNow();
		}
	}

	/**
	 * Set the IndexNow key.
	 *
	 * @return void
	 */
	protected function indexNow() : void
	{
		Output::i()->sendOutput( IndexNow::i()->getKeyfileContent(), 200, 'text/plain' );
	}

	/**
	 * Set the robots.txt files
	 *
	 * @return void
	 */
	protected function robotsTxt() : void
	{
		if ( Settings::i()->robots_txt == 'default' )
		{
			Output::i()->sendOutput( static::robotsTxtRules(), 200, 'text/plain' );
		}
		else if ( Settings::i()->robots_txt != 'off' )
		{
			Output::i()->sendOutput( Settings::i()->robots_txt, 200, 'text/plain' );
		}
		throw new OutOfRangeException;
	}

	/**
	 * Return the robots.txt files
	 *
	 * @return void
	 */
	protected function adsTxt() : void
	{
		if ( Settings::i()->ads_txt_enabled )
		{
			Output::i()->sendOutput( Settings::i()->ads_txt, 200, 'text/plain' );
		}
		throw new OutOfRangeException;
	}
	/**
	 * Return the text for the robots.txt file
	 *
	 * @return string
	 */
	public static function robotsTxtRules(): string
	{
		$path = str_replace( '//', '/', '/' . trim( str_replace( 'robots.txt', '', Url::createFromString( Url::baseUrl() )->data[ Url::COMPONENT_PATH ] ), '/' ) . '/' );
		$sitemapUrl = ( new Sitemap )->sitemapUrl;
		$content = <<<FILE
# Rules for Invision Community (https://invisioncommunity.com)
User-Agent: *
# Block pages with no unique content
Disallow: {$path}startTopic/
Disallow: {$path}discover/unread/
Disallow: {$path}markallread/
Disallow: {$path}staff/
Disallow: {$path}cookies/
Disallow: {$path}online/
Disallow: {$path}discover/
Disallow: {$path}leaderboard/
Disallow: {$path}search/
Disallow: {$path}*?advancedSearchForm=
Disallow: {$path}register/
Disallow: {$path}lostpassword/
Disallow: {$path}login/
Disallow: {$path}*currency=

# Block faceted pages and 301 redirect pages
Disallow: {$path}*?sortby=
Disallow: {$path}*?filter=
Disallow: {$path}*?tab=
Disallow: {$path}*?do=
Disallow: {$path}*ref=
Disallow: {$path}*?forumId*
Disallow: {$path}*?&controller=embed

# Sitemap URL
Sitemap: {$sitemapUrl}
FILE;

		return $content;
	}

	/**
	 * Output the basic javascript files every page needs
	 *
	 * @return void
	 */
	protected static function baseJs() : void
	{
		parent::baseJs();

		/* Stuff for output */
		if ( !Request::i()->isAjax() )
		{
			Output::i()->globalControllers[] = 'core.front.core.app';
			if ( Settings::i()->core_datalayer_enabled )
			{
				Output::i()->globalControllers[] = 'core.front.core.dataLayer';
			}
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front.js' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_core.js', 'core', 'front' ) );

			/* Can we edit widget layouts? */
			if( Member::loggedIn()->modPermission('can_manage_sidebar') )
			{
				Output::i()->globalControllers[] = 'core.front.widgets.manager';
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_widgets.js', 'core', 'front' ) );
			}

			/* Are we editing meta tags? */
			if( isset( $_SESSION['live_meta_tags'] ) and $_SESSION['live_meta_tags'] and Member::loggedIn()->isAdmin() )
			{
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_system.js', 'core', 'front' ) );
			}

			/* Loader extension */
			foreach( Application::allExtensions( 'core', 'Loader' ) as $loader )
			{
				foreach( $loader->js() as $js )
				{
					Output::i()->jsFiles = array_merge( Output::i()->jsFiles, $js );
				}
			}
		}
	}

	/**
	 * Base CSS
	 *
	 * @return	void
	 */
	public static function baseCss() : void
	{
		parent::baseCss();

		/* Stuff for output */
		if ( !Request::i()->isAjax() )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'core.css', 'core', 'front' ) );

			/* Are we editing meta tags? */
			if( isset( $_SESSION['live_meta_tags'] ) and $_SESSION['live_meta_tags'] and Member::loggedIn()->isAdmin() )
			{
				Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/meta_tags.css', 'core', 'front' ) );
			}
			
			/* Query log? */
			if ( QUERY_LOG and ! defined('QUERY_LOG_TO_PATH') )
			{
				Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/query_log.css', 'core', 'front' ) );
			}
			if ( CACHING_LOG or REDIS_LOG )
			{
				Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/caching_log.css', 'core', 'front' ) );
			}

			/* Loader extension */
			foreach( Application::allExtensions( 'core', 'Loader' ) as $loader )
			{
				foreach( $loader->css() as $css )
				{
					Output::i()->cssFiles = array_merge( Output::i()->cssFiles, $css );
				}
			}
		}
	}
	
	/**
	 * Do Member Check
	 *
	 * @return	Url|NULL
	 */
	protected static function doMemberCheck(): ?Url
	{
		foreach( Application::applications() AS $app )
		{
			if ( $url = $app->doMemberCheck() )
			{
				return $url;
			}
		}
		
		return NULL;
	}

	/**
	 *
	 *
	 * @return void
	 */
	public function checkAlerts() : void
	{
		/* Don't get in the way of validating members */
		if ( Member::loggedIn()->members_bitoptions['validating'] )
		{
			return;
		}

		/* Don't get in the way of the ModCP, registering, logging in, etc */
		$ignoreControllers = [ 'modcp', 'register', 'login', 'redirect', 'cookie' ];
		if( !Request::i()->isAjax() and !in_array( $this->controller, $ignoreControllers ) AND $alert = Alert::getNextAlertForMember( Member::loggedIn() ) )
		{
			$alert->viewed( Member::loggedIn() );

			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/alerts.css', 'core', 'front' ) );
			Output::i()->alert = Theme::i()->getTemplate( 'alerts', 'core', 'front' )->alertModal( $alert );
		}
	}
}
