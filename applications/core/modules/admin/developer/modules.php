<?php
/**
 * @brief		modulesadmin
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 Jan 2024
 */

namespace IPS\core\modules\admin\developer;

use DirectoryIterator;
use DomainException;
use IPS\Application\Module;
use IPS\Developer;
use IPS\Developer\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use const IPS\FILE_PERMISSION_NO_WRITE;
use const IPS\IPS_FOLDER_PERMISSION;
use const IPS\ROOT_PATH;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * modulesadmin
 */
class modules extends Controller
{
	/**
	 * @var bool
	 */
	public static bool $csrfProtected = true;

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Get modules */
		$appKey = $this->application->directory;
		$modules = $this->_getModules();
		$location = Request::i()->location ?? 'front';

		$this->header = 'dev_modules' . $location;
		$this->url = $this->url->setQueryString( 'location', $location );

		/* Show tree */
		$url = $this->url;
		$tree = new Tree(
			$url,
			Member::loggedIn()->language()->addToStack('dev_modules', FALSE, array( 'sprintf' => array( ucwords( $location ) ) ) ),
			/* Get Roots */
			function() use ( $appKey, $location, $modules, $url )
			{
				$rows = array();

				if( !empty($modules[ $location ]) AND is_array($modules[ $location ]) )
				{
					foreach ( $modules[ $location ] as $k => $module )
					{
						$rows[ $k ] = Theme::i()->getTemplate( 'trees' )->row( $url, $k, $k, TRUE, array(
							'default'=> array(
								'icon'		=> ( array_key_exists( 'default', $module ) ) ? ( $module['default'] ? 'star' : 'regular fa-star' ) : 'regular fa-star',
								'title'		=> 'make_default_module',
								'link'		=> $url->setQueryString( array( 'do' => 'setDefaultModule', 'id' => $module['id'] ) )->csrf(),
							),
							'add'	=> array(
								'icon'		=> 'plus-circle',
								'title'		=> 'modules_add_controller',
								'link'		=> $url->setQueryString( array( 'do' => 'addController', 'module_key' => $k ) ),
								'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('modules_add_controller') )
							),
							'edit'	=> array(
								'icon'		=> 'pencil',
								'title'		=> 'edit',
								'link'		=> $url->setQueryString( array( 'do' => 'moduleForm', 'key' => $k ) ),
								'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') ),
								'hotkey'	=> 'e'
							),
							'delete'	=> array(
								'icon'		=> 'times-circle',
								'title'		=> 'delete',
								'link'		=> $url->setQueryString( array( 'do' => 'deleteModule', 'key' => $k ) ),
								'data'		=> array( 'delete' => '' )
							)
						), "", NULL, NULL );
					}
				}
				return $rows;
			},
			/* Get Row */
			function( $key, $root=FALSE ) use ( $url, $appKey, $location )
			{
				return Theme::i()->getTemplate( 'trees' )->row( $url, $key, $key, TRUE, array(
					'add'	=> array(
						'icon'		=> 'plus-circle',
						'title'		=> 'modules_add_controller',
						'link'		=> $url->setQueryString( array( 'do' => 'addController', 'module_key' => $key ) ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('modules_add_controller') )
					),
					'edit'	=> array(
						'icon'		=> 'pencil',
						'title'		=> 'edit',
						'link'		=> $url->setQueryString( array( 'do' => 'moduleForm', 'key' => $key ) ),
						'data' 		=> array( 'ipDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') ),
						'hotkey'	=> 'e'
					),
					'delete'	=> array(
						'icon'		=> 'times-circle',
						'title'		=> 'delete',
						'link'		=> $url->setQueryString( array( 'do' => 'deleteModule', 'key' => $key ) ),
						'data'		=> array( 'delete' => '' )
					)
				), '', NULL, NULL, $root );
			},
			/* Get Row's Parent ID */
			function( $id )
			{
				return NULL;
			},
			/* Get Children */
			function( $key ) use ( $appKey, $location, $modules, $url )
			{
				$rows = array();
				foreach ( new DirectoryIterator( ROOT_PATH . "/applications/{$appKey}/modules/{$location}/{$key}" ) as $controller )
				{
					if ( $controller->isFile() and substr( $controller, 0, 1 ) !== '.'  and $controller->getFilename() !== 'index.html' )
					{
						$buttons = [];
						if( $ideLink = Developer::getIdeHref( $controller->getPathname() ) )
						{
							$buttons['ide'] = [
							'icon'		=> 'fa-file-code',
							'title'		=> 'open_in_ide',
							'link'		=> $ideLink
							];
						};
						$buttons['default']	= array(
							'icon'		=> ( str_replace( '.php', '', $controller ) == $modules[ $location ][ $key ]['default_controller'] ) ? 'fa-star' : 'regular fa-star',
							'title'		=> 'modules_make_default',
							'link'		=> $url->setQueryString( array( 'do' => 'setDefaultController', 'root' => $key, 'default' => (string)$controller ) )->csrf(),
						);

						$buttons[	'delete']	= array(
							'icon'		=> 'times-circle',
							'title'		=> 'delete',
							'link'		=> $url->setQueryString( array( 'do' => 'deleteController', 'root' => $key, 'delete' => (string) $controller ) )->csrf(),
							'data'		=> array( 'delete' => '' )
						);
						
						$rows[] = Theme::i()->getTemplate( 'trees' )->row( $url, $controller, $controller, FALSE, $buttons, '', NULL, NULL, FALSE, NULL, NULL, ( $modules[ $location ][ $key ]['default_controller'] == $controller ? array( 'green', 'modules_default' ) : NULL ) );
					}
				}
				return $rows;
			},
			/* Get Root Buttons */
			function() use ( $appKey, $location, $url )
			{
				return array(
					'add'	=> array(
						'icon'		=> 'plus',
						'title'		=> 'modules_add',
						'link'		=> $url->setQueryString( 'do', 'moduleForm' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('modules_add') )
					),
				);
			},
			FALSE,
			TRUE,
			TRUE
		);
		
		Output::i()->output = (string) $tree;
	}

	/**
	 * Make this the default module
	 *
	 * @return void
	 */
	public function setDefaultModule() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$module	= Module::load( Request::i()->id );
			$module->setAsDefault();

			$this->_writeModules( $this->_getModules() );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_for_default', '2C133/A', 403, '' );
		}

		Output::i()->redirect( Url::internal( "app=core&module=developer&controller=modules&appKey={$module->application}&location={$module->area}" ), 'saved' );
	}


	/**
	 * Add/Edit Module
	 *
	 * @return	void
	 */
	protected function moduleForm() : void
	{
		/* Get JSON */
		$modules = $this->_getModules();
		$application = $this->application;
		$location = Request::i()->location;

		/* Load existing module if we're editing */
		if ( Request::i()->key )
		{
			if ( !isset( $modules[ $location ][ Request::i()->key ] ) )
			{
				Output::i()->error( 'node_error', '2C103/J', 404, '' );
			}
			$current = array( 'module_key' => Request::i()->key, 'protected' => $modules[ $location ][ Request::i()->key ]['protected'], 'default_controller' => $modules[ $location ][ Request::i()->key ]['default_controller'] );
		}
		else
		{
			$current = array( 'module_key' => NULL, 'protected' => FALSE, 'default_controller' => NULL );
		}

		/* Build the form */
		$form = new Form();
		$form->add( new Text( 'module_key', $current['module_key'], TRUE, array( 'maxLength' => 32 ), function( $val ) use ( $application, $modules, $location, $current )
		{
			if ( !preg_match( '/^[a-z]*$/', $val ) )
			{
				throw new DomainException( 'module_key_bad' );
			}

			try
			{
				$module = ( !$current['module_key'] OR $current['module_key'] != $val ) ? Module::load( $val, 'sys_module_key', array( 'sys_module_application=? and sys_module_area=?', $application->directory, $location ) ) : NULL;
				if( $module === NULL )
				{
					throw new OutOfRangeException;
				}

				throw new DomainException( 'module_key_exists' );
			}
			catch ( OutOfRangeException $e ) {}
		} ) );
		$form->add( new YesNo( 'module_protected', $current['protected'], TRUE ) );
		if ( Request::i()->key )
		{
			$form->add( new Text( 'module_default_controller', $current['default_controller'] ) );
		}
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'modules_add', $form, FALSE );
		if ( Request::i()->isAjax() )
		{
			return;
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if( $current['module_key'] )
			{
				$module = Module::get( $this->application->directory, $current['module_key'], $location );
			}
			else
			{
				$module = new Module;
				$module->application		= $this->application->directory;
				$module->area				= $location;
			}

			$module->key				= $values['module_key'];
			$module->protected			= $values['module_protected'];
			$module->default_controller	= $values['module_default_controller'] ?? '';
			$module->save();

			$modules[ $location ][ $module->key ] = array(
				'default_controller'	=> $module->default_controller,
				'protected'				=> $module->protected,
				'default'				=> $module->default
			);

			if( $current['module_key'] AND $current['module_key'] != $module->key )
			{
				unset( $modules[ $location ][ $current['module_key'] ] );
			}

			$this->_writeModules( $modules );

			if( $current['module_key'] )
			{
				$oldDir = ROOT_PATH . "/applications/{$this->application->directory}/modules/{$location}/{$current['module_key']}";
				$newDir = ROOT_PATH . "/applications/{$this->application->directory}/modules/{$location}/{$module->key}";
				@rename( $oldDir, $newDir );
				chmod( $newDir, IPS_FOLDER_PERMISSION );
			}
			else
			{
				$dir = ROOT_PATH . "/applications/{$this->application->directory}/modules/{$location}/{$module->key}";
				@mkdir( $dir );
				chmod( $dir, IPS_FOLDER_PERMISSION );
			}

			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=modules&appKey={$this->application->directory}&location={$location}" ), 'saved' );
		}
	}

	/**
	 * Delete Module
	 *
	 * @return	void
	 */
	protected function deleteModule() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		/* Load the module */
		try
		{
			$module = Module::get( $this->application->directory, Request::i()->key, Request::i()->location );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C103/K', 404, '' );
		}

		/* Remove it from the JSON */
		$modules = $this->_getModules();
		unset( $modules[ $module->area ][ $module->key ] );
		$this->_writeModules( $modules );

		/* Delete it */
		$location = $module->area;
		$module->delete();

		/* Redirect */
		Output::i()->redirect( Url::internal( "app=core&module=developer&controller=modules&appKey={$this->application->directory}&location={$location}" ) );
	}

	/**
	 * Create Controller
	 *
	 * @return	void
	 */
	protected function addController() : void
	{
		$module = $this->_loadModule();
		$targetDir = ROOT_PATH . "/applications/{$this->application->directory}/modules/" . Request::i()->location . '/' . $module->key . '/';

		$form = new Form();
		$form->add( new Text( 'filename', NULL, TRUE, array(), function( $val ) use ( $targetDir )
		{
			if ( file_exists( $targetDir . $val . '.php' ) )
			{
				throw new DomainException( 'modules_controller_exists' );
			}
		}, NULL, '.php' ) );

		if ( Request::i()->location === 'admin' )
		{
			$form->add( new Select( 'type', NULL, TRUE, array(
				'options' => array(
					'blank'	=> 'controllertype_blank',
					'node'	=> 'controllertype_node',
					'list'	=> 'controllertype_list',
				),
				'toggles'	=> array(
					'node'	=> array( 'model_name' ),
					'list'	=> array( 'database_table_name' ),
				)
			) ) );

			$form->add( new Text( 'model_name', NULL, FALSE, array(),NULL, NULL, NULL, 'model_name' ) );

			$form->add( new Text( 'database_table_name', NULL, FALSE, array(),NULL, NULL, NULL, 'database_table_name' ) );

			$form->add( new Text( 'acpmenu_tab', $this->application->directory, FALSE, array( 'autocomplete' => array(
				'source'	=> $this->_getAcpMenuTabs(),
				'maxItems'	=> 1
			) ) ) );
			$form->add( new Select( 'acpmenu_restriction', '__create', FALSE, array( 'options' => array_merge( array(
				''			=> 'acpmenu_norestriction',
				'__create'	=> 'controller_rcreate',
			), $this->_getRestrictions( $module ) ) ) ) );
		}

		if ( $values = $form->values() )
		{
			if( !isset($values['type']) )
			{
				$values['type']	= 'blank';
			}

			/* Create a restriction? */
			$restriction = NULL;
			if ( isset( $values['acpmenu_restriction'] ) and $values['acpmenu_restriction'] )
			{
				if ( $values['acpmenu_restriction'] === '__create' )
				{
					$restrictions = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json" );
					$restrictions[ $module->key ][ $values['filename'] ]["{$values['filename']}_manage"] = "{$values['filename']}_manage";
					$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json", $restrictions );
					$restriction = "{$values['filename']}_manage";
				}
				else
				{
					$restriction = $values['acpmenu_restriction'];
				}
			}

			/* Work out the contents */
			$contents = str_replace(
				array(
					'{controller}',
					"{subpackage}",
					'{date}',
					'{app}',
					'{module}',
					'{location}',
					'{restriction}',
					'{node_model}',
					'{table_name}'
				),
				array(
					$values['filename'],
					( $this->application->directory != 'core' ) ? ( " * @subpackage\t" . Member::loggedIn()->language()->get( "__app_{$this->application->directory}" ) ) : '',
					date( 'd M Y' ),
					$this->application->directory,
					Request::i()->module_key,
					Request::i()->location,
					$restriction ? '\IPS\Dispatcher::i()->checkAcpPermission( \''.$restriction.'\' );' : '',
					$values['model_name'] ?? NULL,
					$values['database_table_name'] ?? NULL,
				),
				file_get_contents( ROOT_PATH . "/applications/core/data/defaults/Controller" . IPS::mb_ucfirst( $values['type'] ) . '.txt' )
			);

			/* If this isn't an IPS app, strip out our header */
			if ( !in_array( $this->application->directory, IPS::$ipsApps ) )
			{
				$contents = preg_replace( '/(<\?php\s)\/*.+?\*\//s', '$1', $contents );
			}

			/* Write */
			if( @file_put_contents( $targetDir . $values['filename'] . '.php', $contents ) === FALSE )
			{
				Output::i()->error( 'dev_could_not_write_controller', '1C103/H', 403, '' );
			}

			@chmod( $targetDir . $values['filename'] . '.php', FILE_PERMISSION_NO_WRITE );

			/* Add to the menu? */
			if ( isset( $values['acpmenu_tab'] ) and $values['acpmenu_tab'] )
			{
				$menu = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json" );
				$menu[ $module->key ][ $values['filename'] ] = array(
					'tab'			=> $values['acpmenu_tab'],
					'controller'	=> $values['filename'],
					'do'			=> '',
					'restriction'	=> $restriction,
					'subcontrollers'	=> ''
				);

				$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json", $menu );
			}

			/* Boink */
			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=modules&appKey={$this->application->directory}&location=" . Request::i()->location . "&root=" . Request::i()->module_key ) );
		}

		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'modules_add_controller', $form, FALSE );
	}

	/**
	 * Set a default controller for a module
	 * 
	 * @return void
	 */
	protected function setDefaultController() : void
	{
		Session::i()->csrfCheck();
		$location = Request::i()->location;
		$modules = $this->_getModules();
		$modules[ $location ][ Request::i()->root ]['default_controller'] = mb_substr( Request::i()->default, 0, -4 );
		$this->_writeModules( $modules );

		$module = Module::get( $this->application->directory, Request::i()->root, $location );
		$module->default_controller = mb_substr( Request::i()->default, 0, -4 );
		$module->save();

		if( !Request::i()->isAjax() )
		{
			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=modules&location={$location}&appKey=" . $this->application->directory ) );
		}
	}

	/**
	 * Delete a controller
	 * 
	 * @return void
	 */
	protected function deleteController() : void
	{
		Session::i()->csrfCheck();
		
		$appKey = $this->application->directory;
		$location = Request::i()->location;

		if ( @unlink( ROOT_PATH . "/applications/{$appKey}/modules/{$location}/" . Request::i()->root . '/' . Request::i()->delete ) === FALSE )
		{
			Output::i()->error( 'dev_could_not_write_controller', '1C103/I', 403, '' );
		}

		/* delete all the other stuff */

		$restrictions = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json" );
		if (isset ( $restrictions[ Request::i()->root ][ mb_substr(Request::i()->delete,0 ,-4 )] ) )
		{
			unset($restrictions[ Request::i()->root ][ mb_substr(Request::i()->delete,0 ,-4 )] );
			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json", $restrictions );
		}

		$menu = $this->_getJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json" );
		if (isset ( $menu[ Request::i()->root ][ mb_substr(Request::i()->delete,0 ,-4 )] ) )
		{
			unset($menu[ Request::i()->root ][ mb_substr(Request::i()->delete,0 ,-4 )] );
			$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/acpmenu.json", $menu );
		}
		
		if( !Request::i()->isAjax() )
		{
			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=modules&appKey={$appKey}&location={$location}" ) );
		}
	}

	/**
	 * Write modules.json file
	 *
	 * @param	array	$json	Data
	 * @return	void
	 */
	protected function _writeModules( array $json ) : void
	{
		foreach( $json as $location => $module )
		{
			foreach( $module as $name => $data )
			{
				foreach( $data as $k => $v )
				{
					if ( ! in_array( $k, array( 'protected', 'default_controller', 'default' ) ) )
					{
						unset( $json[ $location ][ $name ][ $k ] );
					}
				}
			}
		}

		$this->_writeJson( ROOT_PATH . "/applications/{$this->application->directory}/data/modules.json", $json );
	}
}