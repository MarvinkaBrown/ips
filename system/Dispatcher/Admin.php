<?php
/**
 * @brief		Admin CP Dispatcher
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
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Developer;
use IPS\Helpers\Menu\Link;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Application\Module;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use UnderflowException;
use function array_merge;
use function count;
use function defined;
use function in_array;
use function is_array;
use function substr;
use const IPS\CIC;
use const IPS\DEMO_MODE;
use const IPS\ENFORCE_ACCESS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Dispatcher
 */
class Admin extends Standard
{
	/**
	 * Controller Location
	 */
	public string $controllerLocation = 'admin';
	
	/**
	 * @brief	Cached Menu
	 */
	protected ?array $menu = NULL;
	
	/**
	 * @brief	Search Keywords
	 */
	public array $searchKeywords = array();
	
	/**
	 * @brief	ACP Restrictions (for search keyword editing)
	 */
	public array $moduleRestrictions = array();
	
	/**
	 * @brief	ACP Restriction for the current menu item (for search keyword editing)
	 */
	public ?string $menuRestriction = NULL;

	const PLATFORM_PERMISSION_UPGRADE_NEEDED = -1;
	const PLATFORM_PERMISSION_FUTURE_EXPIRES = -2;

	/**
	 * Cache built URL map
	 * @var null
	 */
	public static mixed $permissionUrls = NULL;
	
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init() : void
	{
		Output::i()->sidebar['appmenu'] = '';

		/* Sync stuff when in developer mode */
		if ( \IPS\IN_DEV )
		{
			 Developer::sync();
		}

		if ( Member::loggedIn()->member_id )
		{
			/* Build the menu */
			$menu = $this->buildMenu();

			/* Do we need to figure out the default? */
			if ( !isset( Request::i()->app ) )
			{
				foreach ( $menu['tabs'] as $app => $appData )
				{
					if ( isset( $menu['defaults'][ $app ] ) )
					{
						parse_str( $menu['defaults'][ $app ], $defaultQueryString );
						foreach ( $defaultQueryString as $k => $v )
						{
							Request::i()->$k = $v;
						}
						break;
					}
				}
			}
		}
		
		/* Call parent */
		static::baseCss();
		static::baseJs();

		/* Loader extension */
		foreach( Application::allExtensions( 'core', 'Loader' ) as $loader )
		{
			foreach( $loader->js() as $js )
			{
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, $js );
			}
			foreach( $loader->css() as $css )
			{
				Output::i()->cssFiles = array_merge( Output::i()->cssFiles, $css );
			}
		}

		/* Stuff needed for output */
		if ( !Request::i()->isAjax() )
		{
			/* Special grouped CSS files */
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'core/core.css', 'core', 'admin' ) );

			/* JS */
			Output::i()->globalControllers[] = 'core.admin.core.app';
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin.js' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'jquery/jquery-ui.js', 'core', 'interface' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'jquery/jquery-touchpunch.js', 'core', 'interface' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'jquery/jquery.menuaim.js', 'core', 'interface' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'jquery/jquery.nestedSortable.js', 'core', 'interface' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_core.js', 'core', 'front' ) );

			if ( Member::loggedIn()->member_id )
			{
				/* These are just defaults in case we hit an immediate error, e.g. app or controller doesn't exist */
				Output::i()->sidebar['sidebar'] = Theme::i()->getTemplate( 'global', 'core' )->sidebar( array(), 'core_overview' );
				Output::i()->sidebar['appmenu'] = Theme::i()->getTemplate( 'global', 'core' )->appmenu( $menu, 'core', 'core_overview_dashboard' );
				Output::i()->sidebar['mobilenav'] = Theme::i()->getTemplate( 'global', 'core' )->mobileNavigation( $menu, 'core' );
				Output::i()->sidebar['quickLinks'] = $this->_getAcpQuickLinks();
			}

		}
		
		/* Check we're logged in and we have ACP access */
		if( ( !Member::loggedIn()->member_id or !Member::loggedIn()->isAdmin() )
				and ( Request::i()->module !== 'system' or Request::i()->controller !== 'login' )
				and ( !ENFORCE_ACCESS )
		)
		{
			/* Make sure the right protocol is used. IIS, for example, does not like protocol relative URL's in redirects. (Ref: 970629) */
			$protocol = Url::PROTOCOL_HTTP;
			if ( substr( Settings::i()->base_url, 0, 5 ) == 'https' )
			{
				$protocol = Url::PROTOCOL_HTTPS;
			}
			
			$url = Url::internal( "app=core&module=system&controller=login", 'admin', NULL, array(), $protocol );

			if ( Session::i()->error and is_object( Session::i()->error ) )
			{
				$url = $url->setQueryString( 'error', Session::i()->error );
			}
			
			if( !Request::i()->isAjax() )
			{
				/* If someone calls this from command line, while it wouldn't work, the key won't be set */
				if( isset( $_SERVER['QUERY_STRING'] ) )
				{
					$url = $url->setQueryString( 'ref', base64_encode( $_SERVER['QUERY_STRING'] ) );
				}
			}
			else if( isset( $_SERVER['HTTP_REFERER'] ) )
			{
				$previous = preg_replace( "/^(.+?)\/\?/", "", $_SERVER['HTTP_REFERER'] );
				$url = $url->setQueryString( 'ref', base64_encode( $previous ) );
			}

			Output::i()->redirect( $url );
		}
				
		/* Init */
		try
		{
			parent::init();
		}
		catch ( DomainException $e )
		{
			Output::i()->error( $e->getMessage(), '2S100/' . $e->getCode(), $e->getCode() === 4 ? 403 : 404, '' );
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
		
		/* Unless there is a flag telling us we have specifically added CSRF checks, assume any AdminCP action which contains more than app/module/controller/id (i.e. anything with "do") requires CSRF-protection */
		if ( !isset( $this->classname::$csrfProtected ) and array_diff( array_keys( Request::i()->url()->queryString ), array( 'app', 'module', 'controller', 'id' ) ) )
		{
			Session::i()->csrfCheck();
		}

		Db::i()->readWriteSeparation = FALSE;
		if ( isset( $this->classname::$allowRWSeparation ) and $this->classname::$allowRWSeparation )
		{
			Db::i()->readWriteSeparation = TRUE;
		}

		/* If we are in recovery mode, but not actually doing the recovery process, or logging in, then we need them to remove the constant */
		if ( \IPS\RECOVERY_MODE AND !in_array( $this->controller, array( 'recovery', 'login' ) ) )
		{
			Output::i()->error( 'recovery_mode_remove_constant', '1S107/3', 403, '' );
		}
		
		/* Permission Check */
		if (
			(
				$this->module->key !== 'system' or
				!in_array( $this->controller, array( 'login', 'language', 'theme', 'livesearch', 'editor', 'ajax' ) )
			) and
			/* Every admin can view and manage his own acp notification */
			( $this->module->key !== 'overview' or $this->controller !== 'notifications' ) and
			(
				$this->module->key !== 'members' or
				$this->controller !== 'members' or
				!in_array( Request::i()->do, array( 'adminDetails', 'adminEmail', 'adminPassword' ) )
			) and
			/* This is slightly hacky, but the upgrader was moved to the system module, however the ACP restriction is still set to overview.
				To avoid unintentionally removing restrictions via an upgrade by moving the restriction, we reference the overview module for the restriction check instead */
			!Member::loggedIn()->hasAcpRestriction( $this->application, ( $this->application->directory === 'core' and $this->module->key === 'system' and $this->controller === 'upgrade' ) ? Application\Module::get( 'core', 'overview', 'admin' ) : $this->module )
		)
		{
			Output::i()->error( 'no_module_permission', '2S107/1', 403, '' );
		}

		if( ! Application::appIsEnabled( $this->application->directory ) )
		{
			Output::i()->error( 'requested_route_404', '2S107/5', 404, '' );
		}

		/* Support is not available for demos */
		if ( DEMO_MODE AND $this->module->application === 'core' AND $this->module->key === 'support' )
		{
			Output::i()->error( 'demo_mode_function_blocked', '1S107/4', 403, '' );
		}
		
		/* ACP search keywords */
		if ( \IPS\IN_DEV )
		{
			foreach ( Db::i()->select( '*', 'core_acp_search_index' ) as $word )
			{
				$this->searchKeywords[ $word['url'] ]['lang_key'] = $word['lang_key'];
				$this->searchKeywords[ $word['url'] ]['restriction'] = $word['restriction'];
				$this->searchKeywords[ $word['url'] ]['keywords'][] = $word['keyword'];
			}

			$restrictions = array();

			$file = $this->application->getApplicationPath() . "/data/acprestrictions.json";
			if ( file_exists( $file ) )
			{
				$restrictions = json_decode( file_get_contents( $file ), TRUE );
			}

			$this->moduleRestrictions[''] = 'acpmenu_norestriction';
			if ( isset( $restrictions[ $this->module->key ] ) )
			{
				foreach ( $restrictions[ $this->module->key ] as $key => $values )
				{
					$this->moduleRestrictions[ $key ] = array_combine( $values, $values );
				}
			}
									
			$appMenu = $this->application->acpMenu();
			if ( isset( $appMenu[ $this->module->key ] ) )
			{
				foreach ( $appMenu[ $this->module->key ] as $menuItem )
				{
					if ( $menuItem['restriction'] and $menuItem['controller'] == $this->controller and ( !$menuItem['do'] or ( isset( Request::i()->do ) and $menuItem['do'] == Request::i()->do ) ) )
					{
						$this->menuRestriction = $menuItem['restriction'];
					}
				}
			}
		}
		
		/* More stuff needed for output */
		if ( !Request::i()->isAjax() )
		{
			/* Menu and base navigation */
			if ( Member::loggedIn()->member_id )
			{
				/* Work out what tab we're on */
				$currentTab = NULL;
				$currentItem = NULL;

				foreach ( $this->application->acpMenu() as $moduleKey => $items )
				{
					/* If the module key does not match, we still need to inspect each item to see if a module_url that matches was specified */
					$moduleUrlMatches = FALSE;

					foreach( $items as $item )
					{
						if( isset( $item['module_url'] ) AND $item['module_url'] == $this->module->key )
						{
							$moduleUrlMatches = TRUE;
							break;
						}
					}

					if ( $moduleUrlMatches OR $moduleKey === $this->module->key )
				  	{
				  		foreach ( $items as $itemKey => $item )
				  		{
					  		if ( !$currentTab )
					  		{
					  			$currentTab = $item['tab'];
					  		}

					  		$additionalChecksPass = TRUE;

							/* If we set a do=value in the menu, add that to the list of menu checks */
							if( isset( $item['do'] ) and $item['do'] )
							{
								if( isset( $item['menu_checks']['do'] ) and !is_array( $item['menu_checks']['do'] ) )
								{
									$item['menu_checks']['do'] = array( $item['menu_checks']['do'] );
								}
								$item['menu_checks']['do'][] = $item['do'];
							}

					  		if( isset( $item['menu_checks'] ) AND is_array( $item['menu_checks'] ) )
					  		{
					  			foreach( $item['menu_checks'] as $key => $value )
					  			{
									  if( isset( Request::i()->$key ) )
									  {
										  if( is_array( $value ) and !in_array( Request::i()->$key, $value ) )
										  {
											  $additionalChecksPass = false;
											  break;
										  }
										  elseif( !is_array( $value ) and Request::i()->$key != $value )
										  {
											  $additionalChecksPass = false;
											  break;
										  }
									  }
									  else
									  {
										  $additionalChecksPass = false;
										  break;
									  }
					  			}
					  		}

				  			if ( $additionalChecksPass === TRUE and ( $item['controller'] === $this->controller or ( isset( $item['subcontrollers'] ) and in_array( $this->controller, explode( ",", $item['subcontrollers'] ) ) ) ) )
				  			{
				  				$controllerForKey = ( isset( $item['menu_controller'] ) ) ? $item['menu_controller'] : $item['controller'];
								$currentItem = $item['activemenuitem'] ?? ( $this->application->directory . "_" . $moduleKey . "_" . $controllerForKey );

								if( $currentTab != $item['tab'] )
								{
									$currentTab = $item['tab'];
								}
					  		}
				  		}
				  	}
				}

				if ( !$currentTab )
				{
					$currentTab = $this->application->directory;
				}

				/* Display */
				if ( isset( $menu['tabs'][ $currentTab ] ) )
				{
					Output::i()->sidebar['sidebar'] = Theme::i()->getTemplate( 'global', 'core' )->sidebar( $menu['tabs'][ $currentTab ], $this->application->directory . '_' . $this->module->key );
				}
				Output::i()->sidebar['appmenu'] = Theme::i()->getTemplate( 'global', 'core' )->appmenu( $menu, $currentTab, $currentItem );
				Output::i()->sidebar['mobilenav'] = Theme::i()->getTemplate( 'global', 'core' )->mobileNavigation( $menu, $currentTab, $currentItem );
			}
		}
	}
	
	/**
	 * Build Menu
	 *
	 * @param bool $rebuild	If TRUE, will rebuild
	 * @return	array|null
	 */
	public function buildMenu(bool $rebuild=FALSE ): ?array
	{
		$acpTabOrder = $this->_getAcpTabOrder();

		if ( $this->menu === NULL or $rebuild === TRUE )
		{
			$this->menu = array( 'tabs' => array(), 'defaults' => array(), 'badges' );

			foreach ( Application::applications() as $app )
			{
				if ( Application::appIsEnabled( $app->directory ) and Application::load( $app->directory )->canAccess() )
				{
					$appMenu = $app->acpMenu();

					if ( $acpTabOrder !== NULL and isset( $acpTabOrder[ $app->directory ] ) and $app->directory == 'nexus' )
					{
						uksort( $appMenu, function( $a, $b ) use ( $acpTabOrder, $app )
						{
							return array_search( "{$app->directory}_{$a}", $acpTabOrder[ $app->directory ] ) - array_search( "{$app->directory}_{$b}", $acpTabOrder[ $app->directory ] );
						} );
					}

					foreach ( $appMenu as $moduleKey => $items )
					{
						foreach ( $items as $itemKey => $item )
						{
							if ( isset( $item['callback'] ) and !eval( $item['callback'] ) )
							{
								continue;
							}

							$moduleUrl = ( isset( $item['module_url'] ) ) ? $item['module_url'] : $moduleKey;
							$moduleToCheck = ( isset( $item['restriction_module'] ) ) ? $item['restriction_module'] : $moduleKey;

							if ( Member::loggedIn()->hasAcpRestriction( $app, $moduleToCheck ) )
							{
								if( !$item['restriction'] )
								{
									$canAccess = TRUE;
								}
								else
								{
									if( mb_strpos( $item['restriction'], ',' ) )
									{
										$restrictions = explode( ',', $item['restriction'] );
									}
									else
									{
										$restrictions = array( $item['restriction'] );
									}

									$canAccess = FALSE;

									foreach( $restrictions as $restrictionKey )
									{
										if( Member::loggedIn()->hasAcpRestriction( $app, $moduleToCheck, $restrictionKey ) )
										{
											$canAccess = TRUE;
											break;
										}
									}
								}

								if( isset( $item['activemenuitem'] ) and $item['activemenuitem'] )
								{
									$groupKey = substr( $item['activemenuitem'], 0, strrpos( $item['activemenuitem'], '_' ) );
									$itemKey = substr( $item['activemenuitem'], strrpos( $item['activemenuitem'], '_' ) + 1 );
								}
								else
								{
									$groupKey = $app->directory . "_" . $moduleKey;
								}

								if ( $canAccess )
								{
									$this->menu['tabs'][ $item['tab'] ][ "{$groupKey}" ][ $itemKey ] = "app={$app->directory}&module={$moduleUrl}&controller={$item['controller']}" . ( $item['do'] ? "&do={$item['do']}" : '' );
								}

								/* Custom badge */
								if( isset( $item['badge'] ) and $item['badge'] )
								{
									$this->menu['badges'][ "{$groupKey}_{$itemKey}" ] = $item['badge'];
								}
							}
						}
					}
				}
			}
		}

		/* Developer Center */
		if( \IPS\IN_DEV )
		{
			$currentApp = Request::i()->appKey ?? 'core';
			foreach( $this->menu['tabs']['developer'] as $module => $controllers )
			{
				foreach( $controllers as $key => $url )
				{
					parse_str( $url, $params );
					$params['module'] = 'developer';
					$params['appKey'] = $currentApp;
					$this->menu['tabs']['developer'][ $module ][ $key ] = http_build_query( $params );
				}
			}
		}
		
		if ( $acpTabOrder !== NULL )
		{
			$_apps	= array_keys( $acpTabOrder );
			uksort( $this->menu['tabs'], function($a, $b) use ( $_apps )
			{
				if( !in_array( $a, $_apps ) )
				{
					return 1;
				}

				if( !in_array( $b, $_apps ) )
				{
					return -1;
				}

				return array_search( $a, $_apps ) - array_search( $b, $_apps );
			} );
			
			foreach( $acpTabOrder as $app => $submenu )
			{
				if ( !empty( $submenu ) )
				{
					if( isset( $this->menu['tabs'][ $app ] ) )
					{
						uksort( $this->menu['tabs'][ $app ], function($a, $b) use ( $submenu )
						{
							if( !in_array( $a, $submenu ) )
							{
								return 1;
							}

							if( !in_array( $b, $submenu ) )
							{
								return -1;
							}

							return array_search( $a, $submenu ) - array_search( $b, $submenu );
						} );
					}
					
					if ( isset( $this->menu['defaults'] ) )
					{
						uksort( $this->menu['defaults'], function($a, $b) use ( $acpTabOrder )
						{
							return array_search( $a, $acpTabOrder );
						} );
					}
				}
			}
		}

		/* Now set the tab defaults */
		foreach( $this->menu['tabs'] as $tab => $menu )
		{
			if ( !isset( $this->menu['defaults'][ $tab ] ) )
			{
				foreach( $menu as $group => $submenu )
				{
					$this->menu['defaults'][ $tab ] = array_values($submenu)[0];
					break;
				}				
			}
		}

		return Bridge::i()->alterAcpMenu( $this->menu );
	}

	/**
	 * Build an array of quick links for the top menu
	 *
	 * @return array|null
	 */
	protected function _getAcpQuickLinks() : ?array
	{
		$links = [];

		if( Member::loggedIn()->hasAcpRestriction( 'core', 'support', 'get_support' ) )
		{
			$links[] = new Link( Url::internal( "app=core&module=support&controller=support&_new=1" ), 'support' );
			$links[] = new Link( Url::internal( "app=core&module=support&controller=systemLogs" ), 'system_logs' );
			$links[] = new Link( Url::internal( "app=core&module=support&controller=support&do=clearCaches" )->csrf(), 'health_clear_caches_button', dataAttributes: [ 'data-role' => 'clearCaches' ] );
		}

		if( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'advanced_manage_tasks' ) )
		{
			$links[] = new Link( Url::internal( "app=core&module=settings&controller=advanced&do=tasks" ), 'task_manager' );
		}

		return count( $links ) ? $links : null;
	}

	/**
	 * @brief	Cached ACP tab order
	 */
	protected ?array $acpTabOrder = NULL;

	/**
	 * Figure out the ACP tab order
	 *
	 * @return array|null
	 */
	public function _getAcpTabOrder(): ?array
	{
		if( $this->acpTabOrder !== NULL )
		{
			return $this->acpTabOrder;
		}
			
		if ( isset( Request::i()->cookie['acpTabs'] ) AND ! Settings::i()->acp_menu_cookie_rebuild )
		{ 
			$this->acpTabOrder = json_decode( Request::i()->cookie['acpTabs'], TRUE );
		}
		else
		{
			if ( Settings::i()->acp_menu_cookie_rebuild )
			{
				Settings::i()->changeValues( array( 'acp_menu_cookie_rebuild' => 0 ) );
			}

			try
			{
				$this->acpTabOrder = json_decode( Db::i()->select( 'data', 'core_acp_tab_order', array( 'id=?', Member::loggedIn()->member_id ) )->first(), TRUE );
			}
			catch( UnderflowException $ex )
			{
				$this->acpTabOrder = array( 'core' => array(), 'community' => array(), 'members' => array(), 'nexus' => array(), 'cms' => array(), 'stats' => array(), 'customization' => array() );
			}
			
			Request::i()->setCookie( 'acpTabs', json_encode( $this->acpTabOrder ) );
		}

		return $this->acpTabOrder;
	}

	/**
	 * Display a link in a custom format
	 *
	 * @param string $url    The URL from the menu system
	 * @return string|null
	 */
	public function acpMenuCustom( string $url ): ?string
	{
		return Bridge::i()->acpMenuCustom( $url );
	}

	/**
	 * Do we have permission to use this module?
	 *
	 * @param Application $app		Application
	 * @param string|Module $module		Module
	 * @return	bool
	 */
	public function hasPermission( Application $app, Module|string $module ): bool
	{
		return Member::loggedIn()->hasAcpRestriction( $app, $module );
	}
	
	/**
	 * Show switch link
	 *
	 * @return	boolean
	 */
	final public static function showSwitchLink(): bool
	{
		if ( ! CIC )
		{
			return false;
		}

		/* Don't show if installation was less than a month ago */
		if ( time() < ( (int) Settings::i()->board_start + ( 86400 * 30 ) ) )
		{
			return false;
		}

		if ( ! isset( Request::i()->cookie['acpLinkSnooze'] ) )
		{
			return true;
		}

		$value = json_decode( Request::i()->cookie['acpLinkSnooze'], true );

		if ( $value['hits'] < 3 )
		{
			/* Show every 30 days */
			if ( time() < ( $value['lastClick'] + ( 86400 * 30 ) ) )
			{
				return false;
			}
		}
		else if ( $value['hits'] < 9 )
		{
			/* Show every 60 days */
			if ( time() < ( $value['lastClick'] + ( 86400 * 60 ) ) )
			{
				return false;
			}
		}
		else
		{
			/* Show every 90 days */
			if ( time() < ( $value['lastClick'] + ( 86400 * 90 ) ) )
			{
				return false;
			}
		}

		return true;
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
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'core/core.css', 'core', 'admin' ) );
		}

		if ( count( Lang::languages() ) > 1 )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'flags.css', 'core', 'global' ) );
		}
	}

	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish() : void
	{
		if( !Request::i()->isAjax() )
		{
			$dismiss = [];
			$updated = false;
			if ( isset( Request::i()->cookie['acpDeprecations'] ) )
			{
				$dismiss = json_decode( Request::i()->cookie['acpDeprecations'], TRUE );

				if ( ! is_array( $dismiss ) )
				{
					$dismiss = [];
				}
			}

			if ( isset( Request::i()->deprecationDismiss ) )
			{
				$updated = true;
				$dismiss[ Request::i()->deprecationDismiss ] = DateTime::create()->add( new DateInterval( 'P1M' ) )->getTimestamp();
			}


			foreach ( $dismiss as $key => $value )
			{
				if ( $value < time() )
				{
					$updated = true;
					unset( $dismiss[ $key ] );
				}
			}

			if ( $updated )
			{
				Request::i()->setCookie( 'acpDeprecations', json_encode( $dismiss ), DateTime::create()->add( new DateInterval( 'P1Y' ) ) );
			}

			if ( isset( Request::i()->deprecationDismiss ) )
			{
				Output::i()->redirect( Request::i()->url()->stripQueryString( 'deprecationDismiss' ) );
			}
		}

		/* Loader Extension */
		foreach( Application::allExtensions( 'core', 'Loader' ) as $loader )
		{
			$loader->onFinish();
		}

		parent::finish();
	}
}
