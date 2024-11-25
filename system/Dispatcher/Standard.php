<?php
/**
 * @brief		Standard Dispatcher (For Front-End and ACP)
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Nov 2013
 */

namespace IPS\Dispatcher;
 
/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Application;
use IPS\Application\Module;
use IPS\Data\Cache;
use IPS\Data\Cache\None;
use IPS\Data\Store;
use IPS\Db\Exception;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function intval;
use function is_array;
use const IPS\CIC2;
use const IPS\SITE_FILES_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Standard Dispatcher
 */
abstract class Standard extends Dispatcher
{
	/**
	 * Application
	 */
	public ?Application $application = NULL;
	
	/**
	 * Module
	 */
	public ?Module $module = NULL;
	
	/**
	 * Controller
	 */
	public ?string $controller = NULL;

	/**
	 * @brief	Check access permissions
	 */
	public bool $checkGenericPermissions = TRUE;

	public array $platformFeatureExpires = [];
	
	/**
	 * Base CSS
	 *
	 * @return	void
	 */
	public static function baseCss() : void
	{
		if ( !Request::i()->isAjax() )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'framework/framework.css', 'core', 'global' ) );
		}

		if ( count( Lang::languages() ) > 1 )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'flags.css', 'core', 'global' ) );
		}
	}

	/**
	 * Output the basic javascript files every page needs
	 *
	 * @return void
	 */
	protected static function baseJs() : void
	{
		/* Stuff for output */
		if ( !Request::i()->isAjax() )
		{
			/* JS */
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'library.js' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, [ Output\Javascript::getLanguageUrl( Member::loggedIn()->language() ) ] );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'framework.js' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_core.js', 'core', 'global' ) );
			Output::i()->jsVars['date_format'] = mb_strtolower( Member::loggedIn()->language()->preferredDateFormat() );
			Output::i()->jsVars['date_first_day'] = 0;
			Output::i()->jsVars['ipb_url_filter_option'] = Settings::i()->ipb_url_filter_option;
			Output::i()->jsVars['url_filter_any_action'] = Settings::i()->url_filter_any_action;
			Output::i()->jsVars['bypass_profanity'] = intval( Member::loggedIn()->group['g_bypass_badwords'] );
			Output::i()->jsVars['emoji_cache'] = (int) Settings::i()->emoji_cache;
			Output::i()->jsVars['image_jpg_quality'] = (int) Settings::i()->image_jpg_quality ?: 85;
			Output::i()->jsVars['cloud2'] = (bool) CIC2;
		}
	}
	
	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish() : void
	{		
		/* If we're still here - output */
		if ( ! Request::i()->isAjax() )
		{
			/* Load all models for this app and location */
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'app.js', Dispatcher::i()->application->directory ) );
			/* Map.js must come last as it will always have the correct file names */
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'map.js' ) );
		}
		
		parent::finish();
	}
	
	/**
	 * @brief	Initialize tasks
	 */
	protected bool $runTasks = FALSE;

	/**
	 * Init
	 *
	 * @return	void
	 * @throws	DomainException
	 */
	public function init() : void
	{
		$this->runTasks = (bool) Member::loggedIn()->member_id;
		
		/* Force HTTPs and correct domain (e.g. not "www." if that's not in the base URL) */
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' )
		{
			$baseUrl	= new Url( Settings::i()->base_url );
			$newUrl		= Request::i()->url();

			if( $baseUrl->data['scheme'] === 'https' and Request::i()->url()->data['scheme'] !== 'https' )
			{
				$newUrl = $newUrl->setScheme('https');
			}

			if( $baseUrl->data['host'] !== Request::i()->url()->data['host'] )
			{
				$newUrl = $newUrl->setHost( $baseUrl->data['host'] );
			}

			if( $newUrl != Request::i()->url() )
			{
				Output::i()->redirect( $newUrl );
			}
		}

		/* Set locale */
		Member::loggedIn()->language()->setLocale();

		/* Set Application */
		if ( isset( Request::i()->app ) )
		{
			try
			{
				$this->application = Application::load( Request::i()->app );
			}
			catch ( OutOfRangeException $e )
			{
				$applications = Application::applications();
			
				foreach( $applications as $application )
				{
					if( $application->default )
					{
						$this->application = $application;
					}
				}
				
				if( !isset( $this->application ) )
				{
					$this->application = array_shift( $applications );
				}
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'app.js' ) );
				throw new DomainException( 'requested_route_404', 5 );
			}
		}
		else
		{
			$applications = Application::applications();
			
			foreach( $applications as $application )
			{
				if( $application->default )
				{
					$this->application = $application;
				}
			}
			
			if( !isset( $this->application ) )
			{
				$this->application = array_shift( $applications );
			}
		}
		
		/* Init Application */
		if( $this->checkGenericPermissions === TRUE AND !$this->application->canAccess( Member::loggedIn() ) AND $this->controllerLocation != 'admin' )
		{
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'app.js' ) );
			$message = $this->application->disabled_message ?: 'generic_offline_message';
			throw new DomainException( $message, 4 );
		}
		if ( method_exists( $this->application, 'init' ) )
		{
			$this->application->init();
		}
		
		/* Set Module */
		if ( isset( Request::i()->module ) )
		{
			try
			{
				$this->module = Module::get( $this->application->directory, Request::i()->module, static::i()->controllerLocation );
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'app.js' ) );
				throw new DomainException( 'requested_route_404', 6 );
			}
		}
		else
		{
			$this->setDefaultModule();
		}
				
		/* Set controller */
		$this->controller = isset( Request::i()->controller ) ? Request::i()->controller : $this->module->default_controller;

		if( is_array( $this->controller ) )
		{
			$this->controller	= NULL;
			throw new DomainException( 'requested_route_404', 7 );
		}

		/* Set classname */
		$this->classname = 'IPS\\' . $this->application->directory . '\\modules\\' . $this->controllerLocation . '\\' . $this->module->key . '\\' . $this->controller;
		
		/* Base Templates, CSS and JS */
		if ( !Request::i()->isAjax() )
		{
			/* Templates */
			if ( Cache::i() instanceof None )
			{
				Store::i()->templateLoad[] = array( 'core', $this->controllerLocation, 'global' );
				Store::i()->templateLoad[] = array( 'core', 'global', 'global' );
				Store::i()->templateLoad[] = array( 'core', 'global', 'forms' );
				Store::i()->templateLoad[] = array( 'core', $this->controllerLocation, 'forms' );
				$templateLoad = array();
				foreach ( Store::i()->templateLoad as $data )
				{
					$templateLoad[] = 'template_' . Theme::i()->id . '_' . Theme::makeBuiltTemplateLookupHash( $data[0], $data[1], $data[2] ) . '_' . $data[2];
				}

				Store::i()->loadIntoMemory( $templateLoad );
			}
			
			/* App JS */
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'app.js' ) );

			/* App CSS */
			$currentApp = $this->application;
			$currentApp::outputCss();

			/* VLE */
			if ( Lang::vleActive() )
			{
				Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customization/visuallanguage.css', 'core', 'admin' ) );
				Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_customization.js', 'core', 'global' ) );
				Output::i()->globalControllers[] = 'core.global.customization.visualLang';
			}			
		}
	}
	
	/**
	 * Set default module
	 *
	 * @return void
	 */
	protected function setDefaultModule() : void
	{
		$modules = $this->application->modules( static::i()->controllerLocation );
		foreach( $modules as $module )
		{
			if( $module->default )
			{
				$this->module = $module;
				break;
			}
		}

		if( $this->module === NULL )
		{
			$this->module = array_shift( $modules );
		}
	}

	/**
	 * @brief   Enable destruct method
	 * @note    Disable to avoid an automatic database connection
	 */
	public bool $destruct = true;

	/**
	 * @brief  Flag to check if we are in the destructor
	 */
	public bool $inDestructor = false;

	/**
	 * Destructor
	 * Runs tasks
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		/* If you visit and you are redirected to installer, this code should not run */
		if( !file_exists( SITE_FILES_PATH . '/conf_global.php' ) OR !$this->destruct )
		{
			return;
		}

		if ( $this->runTasks and Settings::i()->task_use_cron == 'normal' and !Request::i()->isAjax() )
		{
			$this->inDestructor = true;
			try
			{
				$task = Task::queued();
				if ( $task )
				{
					$task->runAndLog();
				}
			}
			catch( Exception $e ) { }
		}
	}


	/**
	 * Check ACP Permission
	 *
	 * @param string $key Permission Key
	 * @param string|Application|null $app Application (NULL will default to current)
	 * @param string|Module|null $module Module (NULL will default to current)
	 * @param boolean $return Return boolean (true/false) instead of throwing an error
	 * @return int|bool|null
	 */
	public function checkAcpPermission( string $key, string|Application $app=NULL, Module|string $module=NULL, bool $return=FALSE ): int|bool|null
	{
		$result = Bridge::i()->checkPlatformPermission( $key, $app, $module, $return );

		if ( is_int( $result ) )
		{
			return $result;
		}

		if ( !Member::loggedIn()->hasAcpRestriction( ( $app ?: $this->application ), ( $module ?: $this->module ), $key ) )
		{
			if ( $return )
			{
				return FALSE;
			}

			Output::i()->error( 'no_module_permission', '2S107/2', 403, '' );
		}

		if ( $return )
		{
			return TRUE;
		}

		return NULL;
	}

}