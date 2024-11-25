<?php

/**
 * @brief        Controller
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        1/31/2024
 */

namespace IPS\Developer;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Application\Module;
use IPS\Db;
use IPS\Dispatcher\Controller as DispatcherController;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use OutOfRangeException;
use RuntimeException;
use const IPS\IPS_FOLDER_PERMISSION;
use const IPS\NO_WRITES;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Controller extends DispatcherController
{
	/**
	 * @brief	Application object
	 */
	protected ?Application $application = null;

	/**
	 * @var string
	 */
	protected string $header = '';

	/**
	 * @var array
	 */
	protected array $breadcrumbs = [];

	/**
	 * Execute
	 *
	 * @param string $command	The part of the query string which will be used to get the method
	 * @return	void
	 */
	public function execute( string $command='do' ) : void
	{
		/* This controller can only be accessed in developer mode, so we can bypass the check global */

		Output::i()->bypassCsrfKeyCheck = true;

		/* Are we in developer mode? */
		if( !\IPS\IN_DEV )
		{
			Output::i()->error( 'not_in_dev', '2C103/1', 403, '' );
		}
		if ( NO_WRITES )
		{
			Output::i()->error( 'no_writes', '1C103/M', 403, '' );
		}

		/* Load application */
		try
		{
			$this->application = Application::load( Request::i()->appKey );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=applications&controller=applications' ) );
		}

		/* Default header */
		$this->url = Url::internal( "app=core&module=developer&controller=" . ( Request::i()->controller ?? 'details' ) . "&appKey=" . $this->application->directory );
		$this->header = 'dev_' . ( Request::i()->controller ?? 'details' );

		/* Hand off to dispatcher */
		parent::execute();

		Output::i()->jsFiles = array_merge(Output::i()->jsFiles, Output::i()->js('admin_system.js', 'core', 'admin'));
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_developer.js', 'core', 'admin' ) );
		Output::i()->jsVars['devAppKey'] = $this->application->directory;

		/* Generate a dropdown to easily switch between applications */
		$applicationMenu = array(
			'new' => array(
				'icon' => 'plus-circle',
				'title' => 'add',
				'link' => Url::internal( "app=core&module=applications&controller=applications&do=form" ),
				'data' => [ 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'add' ) ]
			),
			'sep' => array(
				'hr' => true
			)
		);
		foreach( Application::applications() as $app )
		{
			$applicationMenu[ $app->directory ] = array(
				'icon' => $app->_icon,
				'title' => $app->_title,
				'link' => Url::internal( "app=core&module=developer&controller=" . ( Request::i()->controller ?? 'details' ) . "&appKey=" . $app->directory )
			);
		}

		Output::i()->sidebar['actions']['apps'] = array(
			'icon' => 'cubes',
			'title' => 'developer_change_apps',
			'menu' => $applicationMenu
		);

		/* Add the build and download button */
		Output::i()->sidebar['actions']['tools'] = array(
			'icon' => 'cogs',
			'title' => 'developer_tools',
			'menu' => array(
				'compilejs' => array(
					'icon'	=> 'cog',
					'title'	=> 'app_compile_js',
					'link'	=> Url::internal( "app=core&module=applications&controller=applications&appKey={$this->application->directory}&do=compilejs" )->csrf()
				),
				'build' => array(
					'icon' => 'cog',
					'title' => 'app_build',
					'link' => Url::internal("app=core&module=applications&controller=applications&appKey={$this->application->directory}&do=build"),
					'data' => array('ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('app_build'))
				),
				'export' => array(
					'icon' => 'download',
					'title' => 'download',
					'link' => Url::internal("app=core&module=applications&controller=applications&appKey={$this->application->directory}&do=download"),
					'data' => array(
						'controller' => 'system.buildApp',
						'downloadURL' => Url::internal("app=core&module=applications&controller=applications&appKey={$this->application->directory}&do=download&type=download"),
						'buildURL' => Url::internal("app=core&module=applications&controller=applications&appKey={$this->application->directory}&do=download&type=build"),
					)
				)
			)
		);

		if( empty( Output::i()->title ) )
		{
			Output::i()->title	= $this->application->_title;
		}

		Output::i()->breadcrumb[] = [ Url::internal( "app=core&module=developer&appKey=" . $this->application->directory ), $this->application->_title ];
		Output::i()->breadcrumb[] = [ $this->url, $this->header ];
		foreach( $this->breadcrumbs as $crumb )
		{
			Output::i()->breadcrumb[] = $crumb;
		}
	}

	/**
	 * Get ACP Menu Tabs
	 *
	 * @return	array
	 */
	protected function _getAcpMenuTabs() : array
	{
		return array_map( function( $val )
		{
			return mb_substr( $val, 9 );
		}, array_filter( array_keys( Member::loggedIn()->language()->words ), function( $val )
		{
			return preg_match( '/^menutab__[a-z]*$/i', $val );
		} ) );
	}

	/**
	 * Get available ACP restrictions
	 *
	 * @param	Module|null	$module	The module to get restrictions for
	 * @return	array
	 */
	protected function _getRestrictions( ?Module $module ) : array
	{
		$restrictions = array();
		$_restrictions = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json" );

		if ( $module !== null and isset( $_restrictions[ $module->key ] ) )
		{
			foreach ( $_restrictions[ $module->key ] as $groupKey => $rows )
			{
				foreach ( $rows as $key )
				{
					$restrictions[ 'r__'.$groupKey ][ $key ] = 'r__'.$key;
				}
			}
		}
		else
		{
			foreach( $_restrictions as $moduleKey => $groups )
			{
				foreach( $groups as $groupKey => $rows )
				{
					foreach( $rows as $key )
					{
						$restrictions[ 'r__'.$groupKey ][ $key ] = 'r__'.$key;
					}
				}
			}
		}

		return $restrictions;
	}

	/**
	 * Get JSON file
	 *
	 * @param	string	$file	Filepath
	 * @return	array	Decoded JSON data
	 */
	protected function _getJson( string $file ) : array
	{
		if( !file_exists( $file ) )
		{
			$json = array();
		}
		else
		{
			$json = json_decode( file_get_contents( $file ), TRUE );
		}

		return $json;
	}

	/**
	 * Write JSON file
	 *
	 * @param	string	$file	Filepath
	 * @param	array	$data	Data to write
	 * @return	void
	 */
	protected function _writeJson( string $file, array $data ) : void
	{
		try
		{
			Application::writeJson( $file, $data );
		}
		catch ( RuntimeException $e )
		{
			Output::i()->error( 'dev_could_not_write_data', '1C103/4', 403, '' );
		}
	}

	/**
	 * Load Module
	 *
	 * @param bool $returnOnError
	 * @return	Module|null
	 */
	protected function _loadModule( bool $returnOnError=false ) : ?Module
	{
		try
		{
			$module = Module::get( $this->application->directory, Request::i()->module_key, Request::i()->location );
		}
		catch ( Exception $e )
		{
			if( $returnOnError )
			{
				return null;
			}

			Output::i()->error( 'node_error', '2C103/L', 404, '' );
		}

		return $module;
	}

	/**
	 * Get modules.json
	 *
	 * @return	array
	 */
	protected function _getModules() : array
	{
		$file    = ROOT_PATH . "/applications/{$this->application->directory}/data/modules.json";
		$json    = $this->_getJson( $file );
		$modules = array();
		$extra   = array();
		$db      = array();

		foreach ( Db::i()->select( '*', 'core_modules', array( 'sys_module_application=?', $this->application->directory ) ) as $row )
		{
			$db[] = $row;
			$extra[ $row['sys_module_area'] ][ $row['sys_module_key'] ] = array( 'default' => $row['sys_module_default'], 'id' => $row['sys_module_id'], 'default_controller' => $row['sys_module_default_controller'], 'protected' => $row['sys_module_protected'] );
		}

		if ( count( $json ) )
		{
			$modules = $json;

			foreach( $db as $row )
			{
				if( $row['sys_module_default'] )
				{
					$modules[ $row['sys_module_area'] ][ $row['sys_module_key'] ]['default'] = true;
				}
				elseif( isset( $modules[ $row['sys_module_area'] ][ $row['sys_module_key'] ]['default'] ) )
				{
					$modules[ $row['sys_module_area'] ][ $row['sys_module_key'] ]['default'] = false;
				}
			}
		}
		else
		{
			foreach( $db as $row )
			{
				$modules[ $row['sys_module_area'] ][ $row['sys_module_key'] ] = array(
					'default_controller'	=> $row['sys_module_default_controller'],
					'protected'				=> $row['sys_module_protected'],
					'default'				=> $row['sys_module_default']
				);
			}
		}

		if ( ! is_file( $file ) )
		{
			$this->_writeJson( $file, $modules );
		}

		/* We get the ID and default flag from the local DB to prevent devs syncing defaults */
		return array_replace_recursive( $modules, $extra );
	}

	/**
	 * Get queries for a version
	 *
	 * @param	int|string		$long	Version ID
	 * @return array
	 */
	protected function _getQueries( int|string $long ) : array
	{
		return $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/setup/" . ( $long === 'install' ? $long : "upg_{$long}" ) . '/queries.json' );
	}

	/**
	 * Write queries.json file
	 *
	 * @param	int|string		$long	Version ID
	 * @param	array	$json	Data
	 * @return	void
	 */
	protected function _writeQueries( int|string $long, array $json ) : void
	{
		/* Create a directory if we don't already have one */
		$path = ROOT_PATH . "/applications/{$this->application->directory}/setup/" . ( $long === 'install' ? $long : "upg_{$long}" );
		if ( ! is_dir( $path ) )
		{
			mkdir( $path );
			chmod( $path, IPS_FOLDER_PERMISSION );
		}

		/* We need to make sure the array is 1-indexed otherwise the upgrader gets confused - unless this is the "working" version
			since that causes conflicts if two branches try to add queries - for the "working" version, this same thing is done
			by \IPS\Application::assignNewVersion() */

		if ( $long === 'working' )
		{
			$write = array_values( $json );
		}
		else
		{
			$write = array();
			$i = 0;
			foreach ( $json as $query )
			{
				$write[ ++$i ] = $query;
			}
		}

		/* Write */
		$this->_writeJson( $path  . '/queries.json', $write );

		/* Update core_dev */
		Db::i()->update( 'core_dev', array(
			'last_sync'	=> time(),
			'ran'		=> json_encode( $write ),
		), array( 'app_key=? AND working_version=?', $this->application->directory, $long ) );
	}

	/**
	 * @param string $file
	 * @return array
	 */
	public function parseFurlsFile( string $file ) : array
	{
		if( !file_exists( $file ) )
		{
			return array();
		}

		$contents = file_get_contents( $file );
		preg_match( '/\/\*(.+?)\*\/(.+?)\{(.+?)$/is', $contents, $match );
		if( is_array( $match ) and count( $match ) )
		{
			$comments = '/*' . $match[1] . '*/';
			$json = '{' . trim( $match[3] );
			$json = preg_replace( '/\/\*(.+?)\*\//is', '', $json );
		}
		else
		{
			$json = $contents;
		}

		return json_decode( $json, true );
	}
}