<?php
/**
 * @brief		Installer/Upgrader Dispatcher
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Apr 2013
 */

namespace IPS\Dispatcher;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;
use const IPS\COOKIE_BYPASS_SSLONLY;
use const IPS\COOKIE_DOMAIN;
use const IPS\COOKIE_PATH;
use const IPS\COOKIE_PREFIX;
use const IPS\SITE_FILES_PATH;
use const IPS\STORE_CONFIG;
use const IPS\STORE_METHOD;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Installer/Upgrader Dispatcher
 */
class Setup extends Dispatcher
{
	/**
	 * @brief Controller Location
	 */
	public string $controllerLocation = 'setup';

	/**
	 * @brief Install or upgrade
	 */
	public string $setupLocation = 'install';

	/**
	 * @brief Step
	 */
	public int $step = 1;
	
	/**
	 * Initiator
	 *
	 * @return	void
	 */
	public function init() : void
	{
	}

	/**
	 * Return valid steps
	 *
	 * @return	array
	 */
	protected function returnSteps(): array
	{
		if( $this->setupLocation == 'upgrade' )
		{
			return array(
				1	=> 'login',
				2	=> 'systemcheck',
				3	=> 'license',
				4	=> 'applications',
				5	=> 'customoptions',
				6	=> 'confirm',
				7	=> 'upgrade',
				8	=> 'done',
			);
		}
		else
		{
			return array(
				1	=> 'systemcheck',
				2	=> 'license',
				3	=> 'applications',
				4	=> 'serverdetails',
				5	=> 'admin',
				6	=> 'install',
				7	=> 'done',
			);
		}
	}
		
	/**
	 * Set location (install or upgrade)
	 *
	 * @param string $location	'install' or 'upgrade'
	 * @return    Setup
	 */
	public function setLocation(string $location ): Setup
	{
		$this->setupLocation	= $location;
		$steps					= $this->returnSteps();
		$currentStep			= ( isset( Request::i()->controller ) ? Request::i()->controller : $steps[1] );
		$this->classname		= 'IPS\core\modules\setup\\' . $location . '\\' . $currentStep;

		/* If we are upgrading and just starting, check the current version.
		If we are upgrading from a version prior to v5, disable all non-1st party apps. */
		if( $location == 'upgrade' and $currentStep == 'login' )
		{
			$currentBaseVersion = Db::i()->select( 'app_long_version', 'core_applications', [ 'app_directory=?', 'core' ] )->first();

			/* If we are upgrading from a version earlier than v5, disable and lock anything that isn't 1st party */
			if( $currentBaseVersion < 5000016 )
			{
				Db::i()->update( 'core_applications', [ 'app_enabled' => 0, 'app_requires_manual_intervention' => 1 ], Db::i()->in( 'app_directory', IPS::$ipsApps, true ) );
				Cache::i()->clearAll();
				Store::i()->clearAll();
			}
		}

		return $this;
	}
	
	/**
	 * Run
	 *
	 * @return	void
	 */
	public function run() : void
	{
		/* Installer checks */
		if( $this->setupLocation == 'install' )
		{
			if ( !file_exists( \IPS\ROOT_PATH . '/conf_global.php' ) )
			{
				try
				{
					rename( \IPS\ROOT_PATH . '/conf_global.dist.php', \IPS\ROOT_PATH . '/conf_global.php' );
				}
				catch ( Exception $e ) { }
				
				if ( !file_exists( \IPS\ROOT_PATH . '/conf_global.php' ) )
				{
					try
					{
						file_put_contents( \IPS\ROOT_PATH. '/conf_global.php', '' );
					}
					catch ( Exception $e ) { }
				}
								
				if ( !file_exists( \IPS\ROOT_PATH . '/conf_global.php' ) )
				{
					Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Member::loggedIn()->language()->addToStack( 'installation_error' ), '', true, \IPS\ROOT_PATH ), 500, 'text/html', array(), FALSE, FALSE, FALSE );
				}
			}
			
			require \IPS\ROOT_PATH . '/conf_global.php';
			if ( isset( $INFO ) and isset( $INFO['installed'] ) )
			{
				$upgradeUrl = new Url( Settings::i()->base_url . 'admin/upgrade/' );
				Output::i()->redirect( $upgradeUrl );
			}
		}
		/* Upgrader Checks */
		else
		{
			if ( !file_exists( SITE_FILES_PATH . '/conf_global.php' ) )
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Member::loggedIn()->language()->addToStack('upgrade_error'), '', Member::loggedIn()->language()->addToStack('no_conf_global'), \IPS\ROOT_PATH ), 200, 'text/html', array(), FALSE );
			}
			
			if ( STORE_METHOD === 'FileSystem' )
			{
				$config = json_decode( STORE_CONFIG, TRUE );
				$path = str_replace( '{root}', \IPS\ROOT_PATH, $config['path'] );
				if ( !is_dir( $path ) or !is_writable( $path ) )
				{
					Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Member::loggedIn()->language()->addToStack('upgrade_error'), '', Member::loggedIn()->language()->addToStack( 'create_conf_global', FALSE, array( 'sprintf' => array( $path ) ) ), \IPS\ROOT_PATH ), 200, 'text/html', array(), FALSE );
				}
			}

			require SITE_FILES_PATH . '/conf_global.php';
			if ( !isset( $INFO ) )
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Member::loggedIn()->language()->addToStack('upgrade_error'), '', Member::loggedIn()->language()->addToStack('bad_conf_global'), \IPS\ROOT_PATH ), 200, 'text/html', array(), FALSE );
			}
			
			/* Fix languages if necessary */
			if( Db::i()->checkForTable( 'core_sys_lang' ) AND !Db::i()->checkForColumn( 'core_sys_lang', 'lang_order' ) )
			{
				Lang::languages( Db::i()->select( '*', 'core_sys_lang' ) );
			}
	
			/* Fix members if necessary */
			if( !Db::i()->checkForTable( 'core_members' ) )
			{
				Member::$databaseTable	= 'members';
				
				if ( !Db::i()->checkForTable( 'core_groups' ) )
				{
					Group::$databaseTable = 'groups';
				}
				
				/* re-arrange 3.x mapping to 4.x code */
				$bits    = Member::$bitOptions;
				unset( $bits['members_bitoptions']['members_bitoptions2'] );
				Member::$bitOptions = $bits;
			}
		}
		
		Settings::i()->base_url = ( Request::i()->isSecure()  ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . mb_substr( $_SERVER['SCRIPT_NAME'], 0, -mb_strlen(  'admin/' . $this->setupLocation . '/index.php' ) );

		$this->step	= array_search( Request::i()->controller, $this->returnSteps() );

		session_name( ( COOKIE_PREFIX !== NULL ) ? COOKIE_PREFIX . 'IPSSessionSetup' : 'IPSSessionSetup' );
		$currentCookieParams = session_get_cookie_params();
		session_set_cookie_params( 
			86400 * 14, 
			( COOKIE_PATH !== NULL ) ? COOKIE_PATH : $currentCookieParams['path'],
			( COOKIE_DOMAIN !== NULL ) ? COOKIE_DOMAIN : $currentCookieParams['domain'],
			( !COOKIE_BYPASS_SSLONLY ) ? ( mb_substr( Settings::i()->base_url, 0, 5 ) == 'https' ) : $currentCookieParams['secure'],
			TRUE
		);

		if( !@session_start() )
		{
			Output::i()->error( Member::loggedIn()->language()->addToStack( 'session_no_good', FALSE, array( 'sprintf' => array( IPS::$lastError?->getMessage() ) ) ), '4S109/5', 500, '' );
		}
		
		if( $this->classname != 'IPS\core\modules\setup\upgrade\done' and $this->setupLocation != 'install' and $this->step > 1 AND ( !isset( $_SESSION['uniqueKey'] ) OR $_SESSION['uniqueKey'] != Request::i()->key ) )
		{
			Output::i()->error( 'upgrade_session_error', '3S109/4', 403, '' );
		}

		/* Init class */
		if( !class_exists( $this->classname ) )
		{
			Output::i()->error( 'page_doesnt_exist', '2S100/1', 404 );
		}
		$controller = new $this->classname; 
		if( !( $controller instanceof Controller) )
		{
			Output::i()->error( 'page_not_found', '5S100/3', 500, '' );
		}
		
		/* Execute */
		$controller->execute();
		
		/* If we're still here - output */
		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( Output::i()->output ), 200, 'text/html', array(), FALSE, FALSE, TRUE, FALSE );
		}
		else
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output ), 200, 'text/html', array(), FALSE, FALSE, TRUE, FALSE );
		}
	}

    /**
     * Destructor
     *
     * @return	void
     */
    public function __destruct()
    {
    }
}