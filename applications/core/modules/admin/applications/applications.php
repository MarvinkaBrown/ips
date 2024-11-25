<?php
/**
 * @brief		Application & Module Management Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\core\modules\admin\applications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DirectoryIterator;
use DomainException;
use Exception;
use FilesystemIterator;
use IPS\Application;
use IPS\Application\BuilderIterator;
use IPS\Application\Module;
use IPS\cms\Templates;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Http\Url\Internal;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Output\Javascript;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use Phar;
use PharData;
use PharException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use UnderflowException;
use UnexpectedValueException;
use function count;
use function defined;
use function extension_loaded;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function intval;
use function is_array;
use function is_dir;
use function time;
use const IPS\CIC2;
use const IPS\DEMO_MODE;
use const IPS\FILE_PERMISSION_NO_WRITE;
use const IPS\IN_DEV;
use const IPS\IPS_ALPHA_BUILD;
use const IPS\IPS_FILE_PERMISSION;
use const IPS\IPS_FOLDER_PERMISSION;
use const IPS\IPS_PASSWORD;
use const IPS\NO_WRITES;
use const IPS\SITE_FILES_PATH;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Application & Module Management Controller
 */
class applications extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\Application';

	/**
	 * Description can contain HTML?
	 */
	public bool $_descriptionHtml = TRUE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'app_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Create the basic tree */
		if ( !Request::i()->isAjax() )
		{
			if( IPS::canManageResources() )
			{
				if ( IPS::checkThirdParty() )
				{
					Output::i()->output = Theme::i()->getTemplate('forms')->blurb( 'applications_blurb' );
				}
				else
				{
					Output::i()->output = Theme::i()->getTemplate('forms')->blurb('applications_blurb_no_custom');
				}
			}
			else
			{
				Output::i()->output = Theme::i()->getTemplate('forms')->blurb( 'applications_blurb_no_upload' );
			}
		}
		parent::manage();

		/* Find uninstalled applications */
		$uninstalled	= array();
		$legacyApps		= array();
		$installed		= array_keys( Application::applications() );

		/* Build a list of uninstalled and legacy apps */
		$applicationDirectories = [ \IPS\ROOT_PATH . "/applications/" ];
		if( CIC2 and is_dir( SITE_FILES_PATH . "/applications/" ) )
		{
			$applicationDirectories[] = SITE_FILES_PATH. "/applications/";
		}
		foreach( $applicationDirectories as $dir )
		{
			foreach ( new DirectoryIterator( $dir ) as $file )
			{
				if ( $file->isDir() AND !in_array( $file->getFilename(), $installed ) AND !$file->isDot() )
				{
					if( file_exists( $file->getPathname() . '/data/application.json' ) )
					{
						$application	= json_decode( file_get_contents( $file->getPathname() . '/data/application.json' ), TRUE );

						$uninstalled[ $file->getFilename() ]	= array(
							'title'		=> $application['application_title'],
							'author'	=> $application['app_author'],
							'website'	=> $application['app_website'],
						);
					}
				}
			}
		}

		/* And 4.x applications not yet upgraded */
		foreach ( Db::i()->select( '*', 'core_applications', Db::i()->in( 'app_directory', array_merge( IPS::$ipsApps, $installed ), true ), 'app_position' ) as $application )
		{
			try
			{
				if( $application['app_requires_manual_intervention'] and !in_array( $application['app_directory'], IPS::$ipsApps ) )
				{
					$legacyApps[ $application['app_directory'] ]	= array(
						'title'		=> $application['app_title'] ?? $application['app_directory'],
						'author'	=> $application['app_author'],
						'website'	=> $application['app_website'],
						'update_version' => ( $application['app_update_version'] ? json_decode( $application['app_update_version'], true ) : null ),
						'long_version' => $application['app_long_version'],
						'version' => $application['app_version']
					);

					if( isset( $uninstalled[ $application['app_directory'] ] ) )
					{
						unset( $uninstalled[ $application['app_directory'] ] );
					}
				}
				else
				{
					Application::constructFromData( $application );
				}
			}
			catch( UnexpectedValueException $e )
			{
				if ( mb_stristr( $e->getMessage(), 'Missing:' ) )
				{
					$legacyApps[ $application['app_directory'] ]	= array(
						'title'		=> $application['app_title'] ?? $application['app_directory'],
						'author'	=> $application['app_author'],
						'website'	=> $application['app_website'],
						'update_version' => ( $application['app_update_version'] ? json_decode( $application['app_update_version'], true ) : null ),
						'long_version' => $application['app_long_version'],
						'version' => $application['app_version']
					);
				}
			}
		}

		if( count( $uninstalled ) AND empty( Request::i()->root ) )
		{
			$baseUrl	= $this->url;
			$tree = new Tree(
				$this->url,
				Member::loggedIn()->language()->addToStack('uninstalled_applications'),
				function() use ( $uninstalled, $baseUrl )
				{
					$rows = array();

					if( !empty($uninstalled) AND is_array($uninstalled) )
					{
						foreach ( $uninstalled as $k => $app )
						{
							$buttons = array();
							if( IPS::canManageResources() )
							{
								$buttons = array(
									'add'	=> array(
										'icon'		=> 'plus-circle',
										'title'		=> 'install',
										'link'		=> Url::internal( "app=core&module=applications&controller=applications&appKey={$k}&do=install" )->csrf(),
									)
								);
							}

							$rows[ $k ] = Theme::i()->getTemplate( 'trees' )->row( $baseUrl, $k, $app['title'], FALSE, $buttons );
						}
					}
					return $rows;
				},
				function( $key, $root=FALSE ) use ( $uninstalled, $baseUrl )
				{
					$buttons = array();
					if( IPS::canManageResources() AND IPS::checkThirdParty() )
					{
						$buttons = array(
							'add'	=> array(
								'icon'		=> 'plus-circle',
								'title'		=> 'install',
								'link'		=> Url::internal( "app=core&module=applications&controller=applications&appKey={$key}&do=install" )->csrf(),
							)
						);
					}

					return Theme::i()->getTemplate( 'trees' )->row( $baseUrl, $key, $uninstalled[ $key ]['title'], FALSE, $buttons, '', NULL, NULL, $root );
				},
				function() { return 0; },
				function() { return array(); },
				function() { return array(); },
				FALSE,
				TRUE,
				TRUE
			);

			Output::i()->output .= Theme::i()->getTemplate( 'applications' )->applicationWrapper( $tree, 'uninstalled_applications' );
		}

		if( count( $legacyApps ) AND empty( Request::i()->root ) )
		{
			$baseUrl	= $this->url;
			$legacyTree = new Tree(
				$this->url,
				Member::loggedIn()->language()->addToStack('legacy_applications'),
				function() use ( $legacyApps, $baseUrl )
				{
					$rows = array();
 					if( !empty( $legacyApps ) AND is_array( $legacyApps ) )
					{
						foreach ( $legacyApps as $k => $app )
						{
							$badge = null;
							$buttons = array();
							if( IPS::canManageResources() AND IPS::checkThirdParty() )
							{
								$buttons['upgrade'] = array(
									'icon' => 'upload',
									'title' => 'upload_new_version',
									'link' => Url::internal( "app=core&module=applications&controller=applications&appKey={$k}&do=upload" ),
									'data' => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'upload_new_version' ) )
								);

								if( isset( $app['update_version'] ) and is_iterable( $app['update_version'] ) )
								{
									$update = null;
									if ( !isset( $app['update_version'][0] ) and isset( $app['update_version']['longversion'] ) )
									{
										$app['update_version'] = array( $app['update_version'] );
									}

									foreach ( $app['update_version'] as $data )
									{
										if( !empty( $data['longversion'] ) and $data['longversion'] > $app['long_version'] and $data['version'] != $app['version'] )
										{
											if( $data['released'] AND ( (int) $data['released'] != $data['released'] OR strlen($data['released']) != 10 ) )
											{
												$data['released']	= strtotime( $data['released'] );
											}
											$update = $data;
										}
									}

									if( $update !== null )
									{
										$badge = [ 'new', '', Theme::i()->getTemplate( 'global', 'core' )->updatebadge( $update['version'], $update['updateurl'] ?? '', DateTime::ts( $update['released'] )->localeDate() ) ];
									}
								}
							}

							if( Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'app_delete' ) )
							{
								$buttons['delete'] = array(
									'icon'	=> 'times-circle',
									'title'	=> 'uninstall',
									'link'	=> $baseUrl->setQueryString( array( 'do' => 'delete', 'id' => $k ) )->csrf(),
									'data' 	=> array( 'delete' => '' ),
									'hotkey'=> 'd'
								);
							}

							$rows[ $k ] = Theme::i()->getTemplate( 'trees' )->row( $baseUrl, $k, $app['title'], FALSE, $buttons, $app['author'], null, null, false, null, null, $badge );
						}
					}
					return $rows;
				},
				function( $key, $root=FALSE ) use ( $legacyApps, $baseUrl )
				{
					$buttons = array();
					if( IPS::canManageResources() AND IPS::checkThirdParty() )
					{
						$buttons['upgrade']	= array(
							'icon' => 'upload',
							'title' => 'upload_new_version',
							'link' => Url::internal( "app=core&module=applications&controller=applications&appKey={$key}&do=upload" ),
							'data' => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'upload_new_version' ) )
						);
					}

					if( Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'app_delete' ) )
					{
						$buttons['delete'] = array(
							'icon'	=> 'times-circle',
							'title'	=> 'uninstall',
							'link'	=> $baseUrl->setQueryString( array( 'do' => 'delete', 'id' => $key ) )->csrf(),
							'data' 	=> array( 'delete' => '' ),
							'hotkey'=> 'd'
						);
					}

					return Theme::i()->getTemplate( 'trees' )->row( $baseUrl, $key, $legacyApps[ $key ]['title'], FALSE, $buttons, '', NULL, NULL, $root );
				},
				function() { return 0; },
				function() { return array(); },
				function() { return array(); },
				FALSE,
				TRUE,
				TRUE
			);

			Output::i()->output .= Theme::i()->getTemplate( 'applications' )->applicationWrapper( $legacyTree, 'legacy_applications' );
		}

		/* Javascript */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_system.js', 'core', 'admin' ) );

		/* Check for updates button */
		Output::i()->sidebar['actions']['settings'] = array(
			'icon'	=> 'refresh',
			'link'	=> Url::internal( 'app=core&module=applications&controller=applications&do=updateCheck' )->csrf(),
			'title'	=> 'check_for_updates',
		);

		if( IPS::canManageResources() and IPS::checkThirdParty() )
		{
			Output::i()->sidebar['actions']['upload'] = array(
				'icon' => 'upload',
				'title' => 'upload',
				'link' => Url::internal( "app=core&module=applications&controller=applications&do=upload" ),
				'data' => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'install_application' ) )
			);
		}

		if ( IN_DEV )
		{
			Output::i()->sidebar['actions']['build_all'] = array(
				'icon'	=> 'cogs',
				'link'	=> Url::internal( 'app=core&module=applications&controller=applications&do=buildAll' ),
				'title'	=> 'build_all_apps',
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('build_all_apps') )
			);
		}
	}

	/**
	 * Redirect after save
	 *
	 * @param Model|null $old			A clone of the node as it was before or NULL if this is a creation
	 * @param Model $new			The node now
	 * @param bool|string $lastUsedTab	The tab last used in the form
	 * @return	void
	 */
	protected function _afterSave( ?Model $old, Model $new, bool|string $lastUsedTab = FALSE ): void
	{
		/* Redirect to the dev center */
		if( IN_DEV )
		{
			Output::i()->redirect( Url::internal( "app=core&module=developer&controller=details&appKey=" . $new->directory ) );
		}

		parent::_afterSave( $old, $new, $lastUsedTab );
	}

	/**
	 * Check for updates
	 *
	 * @return	void
	 */
	public function updateCheck() : void
	{
		Session::i()->csrfCheck();

		$task = Task::constructFromData( Db::i()->select( '*', 'core_tasks', array( 'app=? AND `key`=?', 'core', 'updatecheck' ) )->first() );
		$task->type = 'manual';
		$task->run();
		Output::i()->redirect( Url::internal( "app=core&module=applications&controller=applications" ), 'update_check_complete' );
	}

	/**
	 * Set as default app
	 *
	 * @return void
	 */
	public function setAsDefault() : void
	{
		Session::i()->csrfCheck();

		$application = Application::load( Request::i()->appKey );

		if ( !count( $application->modules( 'front' ) ) )
		{
			Output::i()->error( 'app_cannot_be_default', '2C133/W', 403, '' );
		}

		$application->setAsDefault();
		Session::i()->log( 'acplog__application_set_default', array( $application->titleForLog() => FALSE ) );

		Output::i()->redirect( Url::internal( "app=core&module=applications&controller=applications" ), 'saved' );
	}

	/**
	 * Specify the default module
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
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_for_default', '2C133/A', 403, '' );
		}

		Output::i()->redirect( Url::internal( "app=core&module=applications&controller=applications&root={$module->application}" ), 'saved' );
	}

	/**
	 * Get Child Rows
	 *
	 * @param	int|string	$id		Row ID
	 * @return	array
	 */
	public function _getChildren( int|string $id ): array
	{
		$rows = array();

		/* @var Model $nodeClass */
		$nodeClass = $this->nodeClass;

		try
		{
			$node	= $nodeClass::load( $id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S101/R', 404, '' );
		}

		foreach ( $node->children( NULL ) as $child )
		{
			if( $child->area == 'admin' )
			{
				continue;
			}

			$id = ( $child instanceof $this->nodeClass ? '' : 's.' ) . $child->_id;
			$rows[ $id ] = $this->_getRow( $child );
		}
		return $rows;
	}

	/**
	 * Get Single Row
	 *
	 * @param mixed $id May be ID number (or key) or an \IPS\Node\Model object
	 * @param bool $root Format this as the root node?
	 * @param bool $noSort If TRUE, sort options will be disabled (used for search results)
	 * @return    string
	 * @note    Overridden so we can set the status toggle information to provide the offline message/permissions functionality
	 * @throws Exception
	 */
	public function _getRow( mixed $id, bool $root=FALSE, bool $noSort=FALSE ): string
	{
		/* Load the node first */
		if ( $id instanceof Model )
		{
			$node = $id;
		}
		else
		{
			try
			{
				$nodeClass = $this->nodeClass;
				/* @var $nodeClass Model */
				$node = $nodeClass::load( $id );
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2S101/P', 404, '' );
			}
		}

		/* Don't do this for modules, just applications */
		if( $node instanceof Module )
		{
			return parent::_getRow( $node, $root, $noSort );
		}

		if ( Application::appIsEnabled('cloud') )
		{
			$config = \IPS\cloud\Application::getCloudAppPermissions();

			if ( isset( $config['_other']['disabled_apps'] ) )
			{
				if ( is_array( $config['_other']['disabled_apps'] ) and isset( $config['_other']['disabled_apps']['what'] ) )
				{
					$time = ( isset( $config['_other']['disabled_apps']['when'] ) and mb_substr( $config['_other']['disabled_apps']['when'], 0, 1 ) == 'P' )
						? DateTime::ts( Settings::i()->board_start )->add( new DateInterval( $config['_other']['disabled_apps']['when'] ) )->getTimestamp()
						: 0;

					if ( is_array( $config['_other']['disabled_apps']['what'] ) and count( $config['_other']['disabled_apps']['what'] ) )
					{
						foreach ( $config['_other']['disabled_apps']['what'] as $key )
						{
							if ( $key === $node->directory and $time < time() )
							{
								return Theme::i()->getTemplate( 'applications', 'cloud', 'admin' )->appDisabled( $node );
							}
						}
					}
				}
			}
		}

		/* Work out buttons */
		$buttons = $node->getButtons($this->url, !($node instanceof $this->nodeClass));
		if ( isset( Request::i()->searchResult ) and isset( $buttons['edit'] ) )
		{
			$buttons['edit']['link'] = $buttons['edit']['link']->setQueryString( 'searchResult', Request::i()->searchResult );
		}

		/* Return */
		return Theme::i()->getTemplate( 'trees', 'core' )->row(
			$this->url,
			$node->_id,
			Theme::i()->getTemplate('applications')->appRowTitle( $node ),
			$node->childrenCount( NULL ),
			$buttons,
			$node->_description,
			$node->_icon ? $node->_icon : NULL,
			( $node->canEdit() ) ? $node->_position : NULL,
			$root,
			$node->_enabled,
			( $node->_locked or !$node->canEdit() or NO_WRITES ),
			( ( $node instanceof Model ) ? $node->_badge : $this->_getRowBadge( $node ) ),
			TRUE,
			$this->_descriptionHtml,
			$node->canAdd(),
			TRUE,
			Theme::i()->getTemplate('applications')->appRowAdditional( $node ),
			$node->_lockedLang
		);
	}

	/**
	 * Permissions Form
	 *
	 * @return	void
	 */
	protected function permissions() : void
	{
		/* Work out which class we're using */
		/* @var Model $nodeClass */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			parent::permissions();
			return;
		}

		/* Load Node */
		try
		{
			$node = $nodeClass::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '3S101/A', 404, '' );
		}

		/* Check we're not locked */
		if( $node->_locked or !$node->canEdit() )
		{
			Output::i()->error( 'node_noperm_enable', '2S101/3', 403, '' );
		}

		/* Create the form */
		$form = new Form;
		$form->add( new YesNo( 'app_enabled', $node->disabled_groups === NULL, TRUE, array( 'togglesOff' => array( 'app_disabled_groups', 'app_disabled_message_editor' ), 'disabled' => NO_WRITES ) ) );
		if ( NO_WRITES )
		{
			Member::loggedIn()->language()->words['app_enabled_desc'] = Member::loggedIn()->language()->addToStack( 'app_enabled_desc_no_writes' );
		}

		$form->add( new CheckboxSet( 'app_disabled_groups', ( $node->disabled_groups == '*' or $node->disabled_groups === NULL ) ? '*' : explode( ',', $node->disabled_groups ), FALSE, array(
			'options' 	=> array_combine( array_keys( Group::groups() ), array_map( function( $_group ) { return (string) $_group; }, Group::groups() ) ),
			'multiple' 	=> true,
			'unlimited'		=> '*',
			'unlimitedLang'	=> 'all',
			'impliedUnlimited' => TRUE
		), NULL, NULL, NULL, 'app_disabled_groups' ) );
		$form->add( new Editor( 'app_disabled_message', $node->disabled_message, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => $node->_key . 'app_disabled_message', 'attachIds' => array( $node->id, NULL, 'appdisabled' ) ), NULL, NULL, NULL, 'app_disabled_message_editor' ) );

		/* And then save the values, if appropriate */
		if ( $values = $form->values() )
		{
			$node->disabled_message	= $values['app_disabled_message'];
			$node->disabled_groups	= $values['app_enabled'] ? NULL : ( $values['app_disabled_groups'] == '*' ? '*' : implode( ',', $values['app_disabled_groups'] ) );
			$node->save();

			/* Clear templates to rebuild automatically */
			Theme::deleteCompiledTemplate();

			$this->_logToggleAndRedirect( $node );
		}

		/* Display */
		Output::i()->output = $form;
	}

	/**
	 * Build form
	 *
	 * @param	string	$appKey					The application key
	 * @param	bool	$includeDownloadOption	If a "just download" option should be included
	 * @return	Form
	 */
	protected function _buildForm( string $appKey, bool $includeDownloadOption=FALSE ) : Form
	{
		$json = json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$appKey}/data/versions.json" ), TRUE );
		ksort( $json );

		$defaults = array( 'human' => '1.0.0', 'long' => '10000' );
		$long = NULL;
		$human = NULL;
		foreach ( array_reverse( $json, TRUE ) as $long => $human )
		{
			$exploded = explode( '.', $human );
			$defaults['human'] = "{$exploded[0]}.{$exploded[1]}." . ( intval( $exploded[2] ) + 1 );
			$defaults['long'] = $long + 1;
			break;
		}

		$options = array(
			'options'	=> array(),
			'toggles'	=> array( 'new' => array( 'versions_human', 'versions_long' ) )
		);
		if ( $human !== NULL )
		{
			$options['options']['rebuild'] = 'developer_build_type_rebuild';
			Member::loggedIn()->language()->words['developer_build_type_rebuild'] = sprintf( Member::loggedIn()->language()->get('developer_build_type_rebuild'), $human );
		}
		$options['options']['new'] = 'developer_build_new';
		if ( $includeDownloadOption )
		{
			$options['options']['download'] = 'developer_build_download';
		}

		$form = new Form;
		$form->add( new Radio( 'developer_build_type', 'rebuild', TRUE, $options ) );
		$form->add( new Text( 'versions_human', $defaults['human'], NULL, array(), function( $val )
		{
			if ( !preg_match( '/^([0-9]+\.[0-9]+\.[0-9]+)/', $val ) )
			{
				throw new DomainException( 'versions_human_bad' );
			}
		}, NULL, NULL, 'versions_human' ) );
		$form->add( new Text( 'versions_long', $defaults['long'], NULL, array(), function( $val ) use ( $json )
		{
			if ( !preg_match( '/^\d*$/', $val ) )
			{
				throw new DomainException( 'form_number_bad' );
			}
			if( $val < 10000 )
			{
				throw new DomainException( 'versions_long_too_low' );
			}
			if( isset( $json[ $val ] ) )
			{
				throw new DomainException( 'versions_long_exists' );
			}
		}, NULL, NULL, 'versions_long' ) );

		return $form;
	}

	/**
	 * Build all applications
	 *
	 * @return void
	 */
	public function buildAll() : void
	{
		if ( !IN_DEV )
		{
			Output::i()->error( 'not_in_dev', '2C133/M', 403, '' );
		}

		$form = $this->_buildForm( 'core' );
		$form->add( new YesNo( 'developer_build_submit', (bool) IPS_PASSWORD ) );

		if ( $values = $form->values() )
		{
			foreach ( Application::applications() as $application )
			{
				if ( $values['developer_build_type'] === 'new' )
				{
					$application->assignNewVersion( $values['versions_long'], $values['versions_human'] );
				}

				try
				{
					$application->build();
				}
				catch ( Exception $e )
				{
					Output::i()->error( $e->getMessage(), '' );
				}
			}

			if ( $values['developer_build_submit'] )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=applications&controller=applications&do=submit' )->csrf() );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=applications&controller=applications' ), 'application_now_built' );
			}
		}

		Output::i()->output = $form;
	}

	/**
	 * Build an application
	 *
	 * @return void
	 */
	public function build() : void
	{
		if ( !IN_DEV )
		{
			Output::i()->error( 'not_in_dev', '2C133/N', 403, '' );
		}

		$application = Application::load( Request::i()->appKey );

		$form = $this->_buildForm( $application->directory );

		if ( $values = $form->values() )
		{
			if ( $values['developer_build_type'] === 'new' )
			{
				$application->assignNewVersion( $values['versions_long'], $values['versions_human'] );
			}

			try
			{
				$application->build();
			}
			catch ( Exception $e )
			{
				Output::i()->error( $e->getMessage() . "\n" . $e->getTraceAsString(), '' );
			}

			Output::i()->redirect( Url::internal( 'app=core&module=developer&appKey=' . $application->directory ), 'application_now_built' );
		}

		Output::i()->output = $form;
	}

	/**
	 * Export an application from nullforums.net
	 *
	 * @return void
	 */
	public function downloadNullForums()
	{
		$application = Application::load( Request::i()->appKey );

		try
		{
			$pharPath	= str_replace( '\\', '/', rtrim( TEMP_DIRECTORY, '/' ) ) . '/' . $application->directory . ".tar";
			$download	= new \PharData( $pharPath, 0, $application->directory . ".tar", \Phar::TAR );

			$download->buildFromIterator( new BuilderIterator( $application ), \IPS\ROOT_PATH . "/applications/" . $application->directory . "/" );
		}
		catch( \PharException $e )
		{
			Log::log( $e, 'phar' );
			Output::i()->error( 'app_no_phar', '4C133/7', 403, '' );
		}

		$output	= \file_get_contents( rtrim( TEMP_DIRECTORY, '/' ) . '/' . $application->directory . ".tar" );

		/* Cleanup */
		unset($download);
		\Phar::unlinkArchive($pharPath);

		Output::i()->sendOutput( $output, 200, 'application/tar', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', Member::loggedIn()->language()->get('__app_' . $application->directory ) . " {$application->version}.tar" ) ), FALSE, FALSE, FALSE );
	}

	/**
	 * Export an application
	 *
	 * @return void
	 * @note	We have to use a custom RecursiveDirectoryIterator in order to skip the /dev folder
	 */
	public function download() : void
	{
		$application = Application::load( Request::i()->appKey );

		/* Downloads need developer mode */
		if( !IN_DEV )
		{
			Output::i()->error( 'not_in_dev', '2C133/10', 403, '' );
		}

		$form = $this->_buildForm( $application->directory, TRUE );
		if ( $values = $form->values() )
		{
			if ( $values['developer_build_type'] !== 'download' )
			{
				if ( $values['developer_build_type'] === 'new' )
				{
					$application->assignNewVersion( $values['versions_long'], $values['versions_human'] );
				}
				try
				{
					$application->build();
				}
				catch ( Exception $e )
				{
					Output::i()->error( $e->getMessage(), '' );
				}
			}

			try
			{
				$pharPath	= str_replace( '\\', '/', rtrim( TEMP_DIRECTORY, '/' ) ) . '/' . $application->directory . ".tar";
				$download	= new PharData( $pharPath, 0, $application->directory . ".tar", Phar::TAR );

				$download->buildFromIterator( new BuilderIterator( $application ), \IPS\ROOT_PATH . "/applications/" . $application->directory . "/" );
			}
			catch( PharException $e )
			{
				Log::log( $e, 'phar' );
				Output::i()->error( 'app_no_phar', '4C133/7', 403, '' );
			}

			$output	= file_get_contents( rtrim( TEMP_DIRECTORY, '/' ) . '/' . $application->directory . ".tar" );

			/* Cleanup */
			unset($download);
			Phar::unlinkArchive($pharPath);

			Output::i()->sendOutput( $output, 200, 'application/tar', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', Member::loggedIn()->language()->get('__app_' . $application->directory ) . " {$application->version}.tar" ) ), FALSE, FALSE, FALSE );
		}

		Output::i()->output = $form;
	}

	/**
	 * Toggle Enabled/Disable
	 *
	 * @return	void
	 */
	protected function enableToggle() : void
	{
		/* Clear editor plugins so that it will automatically regenerate */
		Application::resetEditorPlugins();

		/* update the essential cookie name list */
		unset( Store::i()->essentialCookieNames );

		/* Call the parent last because it will send a JSON response.
		Anything after this will not be executed. */
		parent::enableToggle();
	}

	/**
	 * Upgrade an application that is currently installed. After importing a PHAR the user is redirected to this method.
	 *
	 * @return    void
	 * @see        applications::import
	 */
	public function upgrade() : void
	{
		Session::i()->csrfCheck();

		Output::i()->title		= Member::loggedIn()->language()->addToStack('upgrading_application');

		unset( Store::i()->syncCompleted );

		$url = Url::internal( "app=core&module=applications&controller=applications&do=upgrade&appKey=" . Request::i()->appKey )->csrf();

		Output::i()->output	= new MultipleRedirect(
			$url,
			function( $data )
			{
				/* On first cycle return data */
				if ( !is_array( $data ) )
				{
					/* Does this application exist in the database? */
					try
					{
						$app = Db::i()->select( "*", 'core_applications', [ 'app_directory=?', Request::i()->appKey ] )->first();
						$app = Application::constructFromData( $app );
					}
					catch( UnderflowException $e )
					{
						Output::i()->error( 'no_app_to_update', '3C133/G', 403, '' );
					}

					/* Get the application data to update the application record */
					if( file_exists( Application::getRootPath( Request::i()->appKey ) . '/applications/' . Request::i()->appKey . '/data/application.json' ) )
					{
						$application	= json_decode( file_get_contents( Application::getRootPath( $app->directory ) . '/applications/' . $app->directory . '/data/application.json' ), TRUE );

						//\IPS\Lang::saveCustom( $app->directory, "__app_{$app->directory}", $application['application_title'] );

						unset( $application['app_directory'], $application['app_protected'], $application['application_title'] );

						foreach( $application as $column => $value )
						{
							$column			= preg_replace( "/^app_/", "", $column );
							$app->$column	= $value;
						}

						/* If we are upgrading, we can unlock */
						$app->requires_manual_intervention = false;

						$app->save();
					}
					else
					{
						Output::i()->error( 'app_invalid_data', '3C133/H', 403, '' );
					}

					return array(
						array( 'laststep' => 'start', 'key' => Request::i()->appKey ),
						Member::loggedIn()->language()->addToStack('installing_application'),
						1
					);
				}

				/* Install the application in stages */
				$laststep	= NULL;
				$language	= NULL;
				$progress	= 1;
				$extra		= NULL;

				switch( $data['laststep'] )
				{
					case 'start':
						/* Determine our current version and the last version we ran */
						$currentVersion	= Application::load( $data['key'] )->long_version;
						$allVersions	= Application::load( $data['key'] )->getAllVersions();
						$longVersions	= array_keys( $allVersions );
						$humanVersions	= array_values( $allVersions );
						$lastRan		= ( isset( $data['extra']['_last'] ) ) ? intval( $data['extra']['_last'] ) : $currentVersion;

						if( count($allVersions) )
						{
							$latestLVersion	= array_pop( $longVersions );
							$latestHVersion	= array_pop( $humanVersions );

							Db::i()->insert( 'core_upgrade_history', array( 'upgrade_version_human' => $latestHVersion, 'upgrade_version_id' => $latestLVersion, 'upgrade_date' => time(), 'upgrade_mid' => (int) Member::loggedIn()->member_id, 'upgrade_app' => $data['key'] ) );
						}

						/* Now find any upgrade paths since the last one we ran that need to be executed */
						$upgradeSteps	= Application::load( $data['key'] )->getUpgradeSteps( $lastRan );

						/* Did we find any? */
						if( count( $upgradeSteps ) )
						{
							/* Re-initialize $extra variable */
							$extra	= array();

							/* Store a count of all the upgrade steps for later use */
							if( !$lastRan )
							{
								$extra['_totalSteps']			= count($upgradeSteps);
								$data['extra']['_totalSteps']	= $extra['_totalSteps'];
							}
							else
							{
								$extra['_totalSteps']			= $data['extra']['_totalSteps'];
							}

							/* We need to populate \IPS\Request with the extra data returned from the last upgrader step call */
							if( isset( $data['extra']['_upgradeData'] ) )
							{
								Request::i()->extra	= $data['extra']['_upgradeData'];
							}

							/* Grab next upgrade step to run */
							$_next	= array_shift( $upgradeSteps );

							/* Set this now - we can reset later if we need to re-run this step */
							$extra['_last']	= $_next;

							/* What step in the upgrader file are we on? */
							$upgradeStep	= ( isset($data['extra']['_upgradeStep']) ) ? intval($data['extra']['_upgradeStep']) : 1;

							/* Delete removed language strings */
							if( file_exists( \IPS\ROOT_PATH . "/applications/{$data['key']}/setup/upg_{$_next}/lang.json" ) )
 							{
 								$langChanges = json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$data['key']}/setup/upg_{$_next}/lang.json" ), TRUE );
 								if ( isset( $langChanges['normal']['removed'] ) and $langChanges['normal']['removed'] )
 								{
 									Db::i()->delete( 'core_sys_lang_words', array( array( 'word_app=?', $data['key'] ), array( 'word_js=0' ), array( Db::i()->in( 'word_key', $langChanges['normal']['removed'] ) ) ) );
 								}
 								if ( isset( $langChanges['js']['removed'] ) and $langChanges['js']['removed'] )
 								{
 									Db::i()->delete( 'core_sys_lang_words', array( array( 'word_app=?', $data['key'] ), array( 'word_js=1' ), array( Db::i()->in( 'word_key', $langChanges['js']['removed'] ) ) ) );
 								}
 							}

							/* If we haven't run the raw queries yet, do so */
							if( $upgradeStep == 1 AND !isset( $data['extra']['_upgradeData'] ) )
							{
								Application::load( $data['key'] )->installDatabaseUpdates( $_next );
							}

							/* Get the object */
							$_className		= "\\IPS\\{$data['key']}\\setup\\upg_{$_next}\\Upgrade";
							$_methodName	= "step{$upgradeStep}";

							if( class_exists( $_className ) )
							{
								$upgrader		= new $_className;

								/* If the next step exists, run it */
								if( method_exists( $upgrader, $_methodName ) )
								{
									/* Get custom title first as the step may unset session variables that are being referenced */
									$customTitleMethod = 'step' . $upgradeStep . 'CustomTitle';

									if ( method_exists( $upgrader, $customTitleMethod ) )
									{
										$language = $upgrader->$customTitleMethod();
									}

									$result		= $upgrader->$_methodName();

									/* If the result is 'true' we move on to the next step, otherwise we need to run the same step again and store the data returned */
									if( $result === TRUE )
									{
										$_nextMethodStep	= "step" . ( $upgradeStep + 1 );

										if( method_exists( $upgrader, $_nextMethodStep ) )
										{
											/* We have another step to run - set the data and move along */
											$extra['_last']			= $lastRan;
											$extra['_upgradeStep']	= $upgradeStep + 1;
										}
									}
									else
									{
										/* Store the data returned, set the step to the same/current one, and re-run */
										$extra['_upgradeData']	= $result;
										$extra['_upgradeStep']	= $upgradeStep;
										$extra['_last']			= $lastRan;
									}
								}
							}

							$laststep		= 'start';
							$language		= $language ?: Member::loggedIn()->language()->addToStack('appupdate_databasechanges', FALSE, array( 'sprintf' => $allVersions[ $_next ] ) );
							$progress		= round( ( 30 * ( $data['extra']['_totalSteps'] - count($upgradeSteps) ) ) / ( $data['extra']['_totalSteps'] ?: 1 ) );
						}
						else
						{
							$laststep		= 'db';
							$language		= Member::loggedIn()->language()->addToStack('appinstall_databasechanges');
							$progress		= 30;
						}
					break;

					case 'db':
						/* Rebuild data */
						Application::load( $data['key'] )->installJsonData();

						$laststep	= 'basics';
						$language	= Member::loggedIn()->language()->addToStack('appinstall_basics');
						$progress	= 40;
					break;

					case 'basics':
						/* Insert lang data */
						Application::load( $data['key'] )->installLanguages();

						$laststep	= 'lang';
						$language	= Member::loggedIn()->language()->addToStack('appinstall_languages');
						$progress	= 60;
					break;

					case 'lang':
						/* Insert email templates */
						Application::load( $data['key'] )->installEmailTemplates();

						$hasCmsTemplates = file_exists( \IPS\ROOT_PATH . '/applications/' . Application::load( $data['key'] )->directory . '/data/cmsTemplates.xml' );
						$laststep	= $hasCmsTemplates ? 'emails' : 'cmstemplates';
						$language	= Member::loggedIn()->language()->addToStack('appinstall_emails');
						$progress	= 75;
					break;

					case 'emails':
						/* Get Templates and Insert */
						$_SESSION['cmsConflictKey'] = '';
						try
						{
							$result = Templates::importUserTemplateXml( Application::getRootPath( $data['key'] ) . '/applications/' . Application::load( $data['key'] )->directory . '/data/cmsTemplates.xml' );
							if( $result instanceof Internal )
							{
								$_SESSION['cmsConflictKey'] = $result->setQueryString( [ 'application' => Application::load( $data['key'] )->directory, 'lang' => 'updated' ] );
							}
						}
						catch( Throwable $e )
						{
							Log::log( $e, 'cms_template_app_install' );
						}

						$laststep	= 'cmstemplates';
						$language	= Member::loggedIn()->language()->addToStack('appinstall_cmstemplates');
						$progress	= 95;
					break;

					case 'cmstemplates':
						/* Insert skin templates */
						Application::load( $data['key'] )->installSkins( TRUE );
						Application::load( $data['key'] )->installJavascript();

						$language	= Member::loggedIn()->language()->addToStack('appinstall_skins');
						$progress	= 100;
					break;
				}

				/* Return null to indicate we are done */
				if( $laststep === NULL )
				{
					Session::i()->log( 'acplog__application_updated', array( Application::load( $data['key'] )->titleForLog() => FALSE, Application::load( $data['key'] )->version => TRUE ) );

					return NULL;
				}
				else
				{
					return array( array( 'laststep' => $laststep, 'key' => $data['key'], 'extra' => $extra ), $language, $progress );
				}
			},
			function()
			{
				/* IPS Cloud Sync */
				IPS::resyncIPSCloud('Upgraded application in ACP');
				$application = Application::load( Request::i()->appKey );

				/* Unlock the app for PHP8 Usage */
				if ( $application->requires_manual_intervention )
				{
					$application->requires_manual_intervention = 0;
					$application->save();
				}

				/* Clear editor plugins so that it will automatically regenerate */
				Application::resetEditorPlugins();

				/* If CMS Template install had conflicts, solve those now */
				if( !empty( $_SESSION['cmsConflictKey'] ) )
				{
					Output::i()->redirect( $_SESSION['cmsConflictKey'] );
				}

				/* And redirect back to the overview screen */
				Output::i()->redirect( Url::internal( 'app=core&module=applications&controller=applications' ), 'application_now_updated' );
			}
		);
	}

	/**
	 * Install an application that is currently stored on disk. After importing a PHAR the user is redirected to this method.
	 *
	 * @return    void
	 * @see        applications::import
	 */
	public function install() : void
	{
		Session::i()->csrfCheck();

		unset( Store::i()->syncCompleted );

		Output::i()->title		= Member::loggedIn()->language()->addToStack('installing_application');

		$url = Url::internal( "app=core&module=applications&controller=applications&do=install&appKey=" . Request::i()->appKey )->csrf();

		Output::i()->output	= new MultipleRedirect(
			$url,
			function( $data )
			{
				/* On first cycle return data */
				if ( !is_array( $data ) )
				{
					/* Does this application exist in the database? */
					try
					{
						$application = Application::load( Request::i()->appKey );

						if( $application->id )
						{
							Output::i()->error( 'app_already_installed', '2C133/4', 403, '' );
						}
					}
					catch( OutOfRangeException $e ){} // We don't need to do anything if it hasn't loaded - that's good

					/* Get the application data to insert the application record */
					if( file_exists( Application::getRootPath( Request::i()->appKey ) . '/applications/' . Request::i()->appKey . '/data/application.json' ) )
					{
						$application	= json_decode( file_get_contents( Application::getRootPath( Request::i()->appKey ) . '/applications/' . Request::i()->appKey . '/data/application.json' ), TRUE );

						if( !$application['app_directory'] )
						{
							Output::i()->error( 'app_invalid_data', '4C133/5', 403, '' );
						}

						$application['app_position']	= Db::i()->select( 'MAX(app_position)', 'core_applications' )->first() + 1;
						$application['app_added']		= time();
						$application['app_protected']	= 0;
						$application['app_enabled']		= 0;	/* We will reset this post-installation */

						//\IPS\Lang::saveCustom( $application['app_directory'], "__app_{$application['app_directory']}", $application['application_title'] );
						unset($application['application_title']);

						Db::i()->insert( 'core_applications', $application );

						try
						{
							unset( Store::i()->applications );
						}
						catch( OutOfRangeException ){}
					}
					else
					{
						Output::i()->error( 'app_invalid_data', '4C133/6', 403, '' );
					}

					return array(
						array( 'laststep' => 'start', 'key' => Request::i()->appKey ),
						Member::loggedIn()->language()->addToStack('installing_application'),
						1
					);
				}

				/* Install the application in stages */
				$laststep	= NULL;
				$language	= NULL;
				$progress	= 1;

				switch( $data['laststep'] )
				{
					case 'start':
						/* Perform database changes */
						Application::load( $data['key'] )->installDatabaseSchema();

						$laststep	= 'db';
						$language	= Member::loggedIn()->language()->addToStack('appinstall_databasechanges');
						$progress	= 12.5;
					break;

					case 'db':
						/* Rebuild data */
						Application::load( $data['key'] )->installJsonData();

						$laststep	= 'basics';
						$language	= Member::loggedIn()->language()->addToStack('appinstall_basics');
						$progress	= 25;
					break;

					case 'basics':
						/* Insert lang data */
						$offset = ( isset( $data['offset'] ) ) ? intval( $data['offset'] ) : 0;

						$inserted	= Application::load( $data['key'] )->installLanguages( $offset, 250 );

						if( $inserted )
						{
							$laststep		= 'basics';
							$data['offset']	= $offset + $inserted;
						}
						else
						{
							$laststep	= 'lang';
							unset( $data['offset'] );
						}

						$language	= Member::loggedIn()->language()->addToStack('appinstall_languages');
						$progress	= 37.5;
					break;

					case 'lang':
						/* Insert email templates */
						Application::load( $data['key'] )->installEmailTemplates();

						$laststep	= 'emails';
						$language	= Member::loggedIn()->language()->addToStack('appinstall_emails');
						$progress	= 50;
					break;

					case 'emails':
						/* Install Extensions */
						Application::load( $data['key'] )->installExtensions();

						$laststep	= 'extensions';
						$language	= Member::loggedIn()->language()->addToStack('appinstall_extensions');
						$progress	= 62.5;
					break;

					case 'extensions':
						/* Insert skin templates */
						$offset = ( isset( $data['offset'] ) ) ? intval( $data['offset'] ) : 0;

						if( !$offset )
						{
							Application::load( $data['key'] )->installThemeEditorSettings();
							Application::load( $data['key'] )->installCustomTemplates();
							Application::load( $data['key'] )->clearTemplates();
						}

						$inserted = Application::load( $data['key'] )->installTemplates( FALSE, $offset, 150 );

						if( $inserted )
						{
							$laststep		= 'extensions';
							$data['offset']	= $offset + $inserted;
						}
						else
						{
							$laststep	= 'skins';
							unset( $data['offset'] );
						}

						$language	= Member::loggedIn()->language()->addToStack('appinstall_skins');
						$progress	= 75;
					break;

					case 'skins':
						/* Insert skin templates */
						Application::load( $data['key'] )->installJavascript();

						$hasCmsTemplates = file_exists( Application::getRootPath( $data['key'] ) . '/applications/' . $data['key'] . '/data/cmsTemplates.xml' );
						$laststep	= $hasCmsTemplates ? 'cmstemplates' : 'javascript';
						$language	= $hasCmsTemplates ? Member::loggedIn()->language()->addToStack('appinstall_cmstemplates') : Member::loggedIn()->language()->addToStack('appinstall_javascript');
						$progress	= 87.5;
					break;

					case 'cmstemplates':
						/* Get Templates and Insert */
						$_SESSION['cmsConflictKey'] = '';
						try
						{
							$result = Templates::importUserTemplateXml( Application::getRootPath( $data['key'] ) . '/applications/' . $data['key'] . '/data/cmsTemplates.xml' );
							if( $result instanceof Internal )
							{
								$_SESSION['cmsConflictKey'] = $result->setQueryString( [ 'application' => $data['key'], 'lang' => 'installed' ] );
							}
						}
						catch( Throwable $e )
						{
							Log::log( $e, 'cms_template_app_install' );
						}

						$laststep	= 'javascript';
						$language	= Member::loggedIn()->language()->addToStack('appinstall_javascript');
						$progress	= 95;
					break;

					case 'javascript':
						/* Insert other data */
						Application::load( $data['key'] )->installOther();

						$language	= Member::loggedIn()->language()->addToStack('appinstall_finish');
						$progress	= 100;
					break;
				}

				/* Return null to indicate we are done */
				if( $laststep === NULL )
				{
					Session::i()->log( 'acplog__application_installed', array( "__app_" . $data['key'] => TRUE ) );

					return NULL;
				}
				else
				{
					$data['laststep']	= $laststep;
					return array( $data, $language, $progress );
				}
			},
			function()
			{
				/* Enable the application now */
				$application = Application::load( Request::i()->appKey );
				$application->enabled	= 1;
				$application->save();

				/* Clear caches so templates can rebuild and so on */
				Store::i()->clearAll();
				Cache::i()->clearAll();
				IPS::resyncIPSCloud('Installed application in ACP');

				/* Clear editor plugins so that it will automatically regenerate */
				Application::resetEditorPlugins();

				/* If CMS Template install had conflicts, solve those now */
				if( !empty( $_SESSION['cmsConflictKey'] ) )
				{
					Output::i()->redirect( $_SESSION['cmsConflictKey'] );
				}

				/* And redirect back to the overview screen */
					Output::i()->redirect( Url::internal( 'app=core&module=applications&controller=applications' ), 'application_now_installed' );

			}
		);
	}

	/**
	 * Delete
	 *
	 * @return	void
	 * @note	For application uninstall we don't need the whole move children thing
	 */
	protected function delete() : void
	{
		Session::i()->csrfCheck();

		/* Get node */
		/* @var Model $nodeClass */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}

		try
		{
			/* Load the application - we don't use \IPS\Application::load() because this could be a legacy out of date application
				and if no Application.php an exception will be thrown. We don't need to stop just based on that, we want to proceed with delete. */
			$node = NULL;

			foreach( Store::i()->applications as $application )
			{
				if( $application['app_directory'] == Request::i()->id )
				{
					$node	= Application::constructFromData( $application );
					break;
				}
			}

			if( $node === NULL )
			{
				throw new UnexpectedValueException;
			}

			/* Permission check */
			if( !$node->canDelete() )
			{
				Output::i()->error( 'node_noperm_delete', '2C133/J', 403, '' );
			}

			if ( $node->default )
			{
				$this->setNewDefaultApplication($node);
				return;
			}
			else
			{
				/* Make sure the user confirmed the deletion */
				Request::i()->confirmedDelete();
			}

			/* Delete it */
			Session::i()->log( 'acplog__application_uninstalled', array( $node->directory => TRUE ) );
			$node->delete();

			/* Clear caches */
			Cache::i()->clearAll();

			/* Clear \Data\Store */
			Store::i()->clearAll();
		}
		/* Legacy */
		catch ( UnexpectedValueException $e )
		{
			if( !Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'app_delete' ) )
			{
				Output::i()->error( 'node_noperm_delete', '2C133/J', 403, '' );
			}

			Db::i()->delete( 'core_applications', array( 'app_directory=?', Request::i()->id ) );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C133/I', 404, '' );
		}

		/* Boink */
		Output::i()->redirect( Url::internal( 'app=core&module=applications&controller=applications' ), 'deleted' );
	}


	/**
	 * Set a new default application if the current default app is being uninstalled
	 *
	 * @param	$node	Application	Application to delete
	 * @return	void
	 */
	protected function setNewDefaultApplication( Application $node ) : void
	{
		$form = new Form();
		$form->hiddenValues['wasConfirmed']	= 1;
		$form->add( new Node( 'new_default_app', NULL, TRUE, array(
				'class'					=> 'IPS\Application',
				'subnodes' => false,
				'permissionCheck' => function( $app ) use ( $node )
					{
 						if ( $app->directory == 'core')
						{
							return false;
						}
						else
						{
							return !($node->directory == $app->directory);
						}

					}
		) ) );

		if  ( $values = $form->values() )
		{
			$values['new_default_app']->setAsDefault();
			Session::i()->log( 'acplog__application_set_default', array( $values['new_default_app']->titleForLog() => FALSE ) );

			Output::i()->redirect( Url::internal( "app=core&module=applications&controller=applications&do=delete&id={$node->_id}&wasConfirmed=1" )->csrf() );
		}
		else
		{
			Output::i()->output = (string) $form;
		}
	}

	/**
	 * View application details
	 *
	 * @return	void
	 */
	public function details() : void
	{
		/* Get node */
		/* @var Model $nodeClass */
		$nodeClass = $this->nodeClass;
		if ( Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}

		/* Get the application */
		try
		{
			$application	= $nodeClass::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'error_no_app', '2C133/1', 404, '' );
		}

		/* Work out tab */
		$tab				= Request::i()->tab ?: 'details';
		$tabFunction		= array( $this, '_show' . IPS::mb_ucfirst( $tab ) );
		$activeTabContents	= $tabFunction( $application );

		/* If this is an AJAX request, just return tab contents */
		if( Request::i()->isAjax() && Request::i()->tab and !isset( Request::i()->ajaxValidate ) )
		{
			Output::i()->output = $activeTabContents;
			return;
		}

		/* Build tab list */
		$tabs				= array();
		$tabs['details']	= 'app_details_details';
		$tabs['upgrades']	= 'app_details_upgrades';

		/* Output */
		Output::i()->title		= Member::loggedIn()->language()->addToStack( $tabs[ $tab ] );
		Output::i()->output 	= Theme::i()->getTemplate( 'global' )->tabs( $tabs, $tab, $activeTabContents, Url::internal( "app=core&module=applications&controller=applications&do=details&id={$application->directory}" ) );
	}

	/**
	 * Upload a new application for installation
	 *
	 * @return void
	 */
	public function upload() : void
	{
		if ( DEMO_MODE )
		{
			Output::i()->error( 'demo_mode_function_blocked', '1C133/V', 403, '' );
		}

		if ( NO_WRITES )
		{
			Output::i()->error( 'no_writes', '1C133/B', 403, '' );
		}

		if( !CIC2 AND !is_writable( \IPS\ROOT_PATH . "/applications/" ) )
		{
			Output::i()->error( 'app_dir_not_write', '4C133/8', 500, '' );
		}

		if ( !extension_loaded('phar') )
		{
			Output::i()->error( 'no_phar_extension', '1C133/P', 403, '' );
		}

		if ( !IPS::checkThirdParty() )
		{
			Output::i()->error( 'cic_3rdparty_unavailable', '2C133/Z', 403, '' );
		}

		$_type	= 'install';

		/* Are we upgrading an application? */
		if( Request::i()->appKey )
		{
			try
			{
				$app = NULL;

				foreach( Store::i()->applications as $application )
				{
					if( $application['app_directory'] == Request::i()->appKey )
					{
						$app	= $application;
						break;
					}
				}

				if( $app === NULL )
				{
					throw new OutOfRangeException;
				}
			}
			catch ( UnexpectedValueException $e )
			{
				// Legacy 3.x app
				if ( !CIC2 )
				{
					if ( !is_dir( \IPS\ROOT_PATH . "/applications/" . Request::i()->appKey ) )
					{
						mkdir( \IPS\ROOT_PATH . "/applications/" . Request::i()->appKey );
						chmod( \IPS\ROOT_PATH . "/applications/" . Request::i()->appKey, IPS_FOLDER_PERMISSION );
					}
				}
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'no_app_to_update', '2C133/C', 403, '' );
			}

			if( !CIC2 AND !is_writable( \IPS\ROOT_PATH . "/applications/" . $app['app_directory'] ) )
			{
				Output::i()->error( Member::loggedIn()->language()->addToStack( "app_specific_dir_nowrite", FALSE, array( 'sprintf' => $app['app_directory'] ) ), '4C133/D', 500, '' );
			}

			$_type	= 'upgrade';
		}

		$form = new Form( 'form', 'install' );
		$form->addMessage('applications_manual_install_warning');
		$form->add( new Upload( 'application_file', NULL, TRUE, array( 'allowedFileTypes' => array( 'tar' ), 'temporary' => TRUE ) ) );

		if ( $values = $form->values() )
		{
			try
			{
				if ( mb_substr( $values['application_file'], -4 ) !== '.tar' )
				{
					/* If rename fails on a significant number of customer's servers, we might have to consider using
						move_uploaded_file into uploads and rename in there */
					rename( $values['application_file'], $values['application_file'] . ".tar" );

					$values['application_file'] .= ".tar";
				}

				/* Test the phar */
				$application = new PharData( $values['application_file'], 0, NULL, Phar::TAR );

 				/* Get app directory */
				$appdata = json_decode( file_get_contents( "phar://" . $values['application_file'] . '/data/application.json' ), TRUE );

				/* Make sure that the app data is valid */
				if( !isset( $appdata['app_directory'] ) )
				{
					throw new UnexpectedValueException;
				}

				$appDirectory	= $appdata['app_directory'];

				if ( CIC2 )
				{
					unset( Store::i()->syncCompleted );

					\IPS\Cicloud\file( "IPScustomapp_{$appdata['app_directory']}.tar", file_get_contents( $values['application_file'] ) );

					/* Check files are ready */
					$i = 0;
					do
					{
						if ( ( isset( Store::i()->syncCompleted ) AND Store::i()->syncCompleted ) OR $i >= 30 ) # 30 x 0.25 seconds
						{
							/* We need to wait for the backend to process the tar */
							sleep(3);
							break;
						}

						/* Pause slightly before checking the datastore again */
						usleep( 250000 );
						$i++;
					}
					while( TRUE );
				}
				else
				{
					/* Extract */
					$application->extractTo( \IPS\ROOT_PATH . "/applications/" . $appDirectory, NULL, TRUE );
					$this->_checkChmod( \IPS\ROOT_PATH . '/applications/' . $appDirectory );
					IPS::resyncIPSCloud('Uploaded new application in ACP');
				}
			}
			catch( PharException $e )
			{
				Log::log( $e, 'phar' );
				Output::i()->error( 'application_notvalid', '1C133/9', 403, '' );
			}
			catch( UnexpectedValueException $e )
			{
				Output::i()->error( 'application_notvalid', '1C133/K', 403, '' );
			}

			Output::i()->redirect( Url::internal( "app=core&module=applications&controller=applications&do={$_type}&appKey={$appDirectory}" )->csrf(), Member::loggedIn()->language()->addToStack('installing_application') );
		}

		/* Display */
		Output::i()->output = $form;
	}

	/**
	 * Recursively check and adjust CHMOD permissions after uploading an application
	 *
	 * @param	string	$directory	Directory to check
	 * @return	void
	 * @see		Ticket 956849
	 */
	public static function _checkChmod( string $directory ) : void
	{
		if ( !is_dir( $directory ) )
		{
			throw new UnexpectedValueException;
		}

		$it = new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS );
		foreach( new RecursiveIteratorIterator( $it ) AS $f )
		{
			if ( $f->isDir() )
			{
				@chmod( $f->getPathname(), IPS_FOLDER_PERMISSION );
			}
			else
			{
				/* If this is a .php file in the /interface/ folder it will be called via web directly. We cannot set permissions too high though or it won't execute in many environments */
				@chmod( $f->getPathname(), ( mb_strpos( $f->getPathname(), '/interface/' ) !== FALSE AND mb_strtolower( $f->getExtension() ) == 'php' ) ? FILE_PERMISSION_NO_WRITE : IPS_FILE_PERMISSION );
			}
		}
	}

	/**
	 * Import JS from /dev folders and compile into file objects
	 *
	 * @return	void
	 */
	public function compilejs() : void
	{
		Session::i()->csrfCheck();

		Output::i()->output = new MultipleRedirect(
			Url::internal( 'app=core&module=applications&controller=applications&do=compilejs&appKey=' . Request::i()->appKey )->csrf(),
			function( $data )
			{
				/* Is this the first cycle? */
				if ( !is_array( $data ) )
				{
					/* Start importing */
					$data = array( 'toDo' => array( 'import', 'compile' ) );

					return array( $data, Member::loggedIn()->language()->addToStack('processing') );
				}

				/* Grab something to build */
				if ( count( $data['toDo'] ) )
				{
					reset( $data['toDo'] );
					$command = array_shift( $data['toDo'] );

					switch( $command )
					{
						case 'import':
							$xml = Javascript::createXml( Request::i()->appKey );

							/* Write it */
							if ( is_writable( \IPS\ROOT_PATH . '/applications/' . Request::i()->appKey . '/data' ) )
							{
								file_put_contents( \IPS\ROOT_PATH . '/applications/' . Request::i()->appKey . '/data/javascript.xml', $xml->outputMemory() );
							}
						break;
						case 'compile':
							Javascript::compile( Request::i()->appKey );

							/* Compile global JS after so map is written and correct */
							if ( Request::i()->appKey == 'core' )
							{
								Javascript::compile('global');
							}

						break;
					}

					return array( $data, Member::loggedIn()->language()->addToStack('processing') );
				}
				else
				{
					/* All Done */
					return null;
				}
			},
			function()
			{
				/* Finished */
				Output::i()->redirect( Url::internal( 'app=core&module=developer&appKey=' . Request::i()->appKey ), 'completed' );
			}
		);
	}

	/**
	 * Submit application details to IPS backend
	 *
	 * @return void
	 */
	public function submit() : void
	{
		Session::i()->csrfCheck();

		$url = Url::internal('app=core&module=applications&controller=applications&do=submit')->csrf();
		Output::i()->output = (string) new MultipleRedirect( $url, function( $data )
		{
			if ( !isset( $data['done'] ) )
			{
				$data['done'] = array();
			}

			foreach ( IPS::$ipsApps as $app )
			{
				if ( in_array( $app, $data['done'] ) )
				{
					continue;
				}

				/* Prevent race conditions */
				$data['done'][] = $app;

				try
				{
					$version = Application::getAvailableVersion( $app );
					$info = json_decode( file_get_contents( \IPS\ROOT_PATH . '/applications/' . $app . '/setup/upg_' . $version . '/data.json' ), TRUE );
					$info['built'] = time();

					$response = Url::ips('upgrade')->request()->login( 'dev', IPS_PASSWORD )->post( array(
						'version'	=> $version,
						'app'		=> $app,
						'type'		=> 'info',
						'data'		=> json_encode( $info ),
						'alpha'		=> (int) IPS_ALPHA_BUILD,
					) );
					if ( $response->httpResponseCode != 200 )
					{
						Output::i()->error( $app . $response, '2C133/X', 403, '' );
					}

					foreach ( $info['steps'] as $k => $v )
					{
						if ( $v and !in_array( $k, array( 'customOptions', 'customRoutines' ) ) and file_exists( \IPS\ROOT_PATH . '/applications/' . $app . '/setup/upg_' . $version . '/' . $k . '.json' ) )
						{
							$response = Url::ips('upgrade')->request()->login( 'dev', IPS_PASSWORD )->post( array(
								'version'	=> $version,
								'app'		=> $app,
								'type'		=> $k,
								'alpha'		=> (int) IPS_ALPHA_BUILD,
								'data'		=> json_encode( json_decode( file_get_contents( \IPS\ROOT_PATH . '/applications/' . $app . '/setup/upg_' . $version . '/' . $k . '.json' ) ) )
							) );
							if ( $response->httpResponseCode != 200 )
							{
								Output::i()->error( $app .  $response, '2C133/Z', 403, '' );
							}
						}
					}

					return array( $data, Member::loggedIn()->language()->addToStack('processing') );
				}
				catch ( Exception $e )
				{
					Output::i()->error( $e->getMessage(), '2C133/Y', 500, '' );
				}
			}

			return NULL;
		},
		function()
		{
			Output::i()->redirect( Url::internal( 'app=core&module=applications&controller=applications' ), 'application_now_built' );
		} );
	}

	/**
	 * Get application details
	 *
	 * @param	Model	$application	Application node
	 * @return	string
	 */
	protected function _showDetails( Model $application ) : string
	{
		try
		{
			$history = Db::i()->select( 'upgrade_date', 'core_upgrade_history', array( 'upgrade_app=?', $application->directory ), 'upgrade_version_id DESC', array( 0, 1 ) )->first();
		}
		catch( UnderflowException $ex )
		{
			$history = null;
		}

		return Theme::i()->getTemplate( 'applications' )->details( $application, $history );
	}

	/**
	 * Show the application upgrade history
	 *
	 * @param	Model	$application	Application node
	 * @return	string
	 */
	protected function _showUpgrades( Model $application ) : string
	{
		$list		= array();
		$upgrades = Db::i()->select( '*', 'core_upgrade_history', array( 'upgrade_app=?', $application->directory ), 'upgrade_version_id DESC' );
		foreach(  $upgrades as $version )
		{
			$list[ (string) DateTime::ts( $version['upgrade_date'] ) ]	= Member::loggedIn()->language()->addToStack('app_version_string', FALSE, array( 'sprintf' => array( $version['upgrade_version_human'], $version['upgrade_version_id'] ) ) );
		}

		return ( count( $upgrades ) ) ? Theme::i()->getTemplate( 'global' )->definitionTable( $list ) : Theme::i()->getTemplate( 'global' )->paddedBlock( Member::loggedIn()->language()->addToStack('app_no_upgrade_history') );
	}
}