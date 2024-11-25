<?php
/**
 * @brief		Build/Tools Dispatcher
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Apr 2013
 */

namespace IPS\Dispatcher;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Application\Module;
use IPS\Data\Store;
use IPS\Dispatcher;
use IPS\Request;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Build/Tools Dispatcher
 */
class Build extends Dispatcher
{
	/**
	 * @brief Controller Location
	 */
	public string $controllerLocation = 'front';

	/**
	 * @brief Application
	 */
	public string $application        = 'core';

	/**
	 * @brief Module
	 */
	public string $module		       = 'system';
	
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
		$modules = Module::modules();
		$this->application = Application::load('core');
		$this->module      = $modules['core']['front']['system'];
		$this->controller  = 'build';
	}

	/**
	 * Run
	 *
	 * @return	void
	 */
	public function run() : void
	{
		if ( isset( Request::i()->force ) )
		{
			if ( isset( Store::i()->builder_building ) )
			{
				unset( Store::i()->builder_building );
			}
		}
		else
		{
			if ( isset( Store::i()->builder_building ) and ! empty( Store::i()->builder_building ) )
			{
				/* We're currently rebuilding */
				if ( time() - Store::i()->builder_building < 180  )
				{
					print "Builder is already running. To force a rebuild anyway, add &force=1 on the end of your URL";
					exit();
				}
			}
			
			Store::i()->builder_building = time();
		}
				
		Settings::i()->changeValues( array( 'site_online' => 0 ) );
	}
	
	/**
	 * Done
	 *
	 * @return	void
	 */
	public function buildDone() : void
	{
		if ( isset( Store::i()->builder_building ) )
		{
			unset( Store::i()->builder_building );
		}
		
		Settings::i()->changeValues( array( 'site_online' => 1 ) );
	}
}