<?php
/**
 * @brief		Templates Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		25 Feb 2013
 */

namespace IPS\cms\modules\admin\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Diff;
use DomainException;
use Exception;
use IPS\Application;
use IPS\cms\Blocks\Block;
use IPS\cms\Databases;
use IPS\cms\Templates as TemplatesClass;
use IPS\cms\Templates\Container;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Http\Url\Internal;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Widget;
use OutOfRangeException;
use Throwable;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function mb_substr;
use function strip_tags;
use function substr;
use const IPS\TEMP_DIRECTORY;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * templates
 */
class templates extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'template_manage' );
		parent::execute();
	}
	
	/**
	 * Import the IN_DEV templates
	 * 
	 * @return void
	 */
	public function importInDev() : void
	{
		Session::i()->csrfCheck();
		
		\IPS\cms\Theme::importFromFiles('database');
		\IPS\cms\Theme::importFromFiles('page');

		Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates' ), 'completed' );
	}

	/**
	 * Import dialog
	 *
	 * @return void
	 */
	public function import() : void
	{
		$form = new Form( 'form', 'next' );

		$form->add( new Upload( 'cms_templates_import', NULL, FALSE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ), NULL, NULL, NULL, 'cms_templates_import' ) );

		if ( $values = $form->values() )
		{
			if ( $values['cms_templates_import'] )
			{
				/* Move it to a temporary location */
				$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
				move_uploaded_file( $values['cms_templates_import'], $tempFile );

				Session::i()->log( 'acplogs__cms_imported_templates' );

				/* Initate a redirector */
				Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates&do=importProcess' )->csrf()->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ) ) ) );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates&do=manage' ) );
			}
		}

		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'cms_templates_import_title', $form, FALSE );
	}

	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	public function importProcess() : void
	{
		Session::i()->csrfCheck();
		
		if ( !file_exists( Request::i()->file ) or md5_file( Request::i()->file ) !== Request::i()->key )
		{
			Output::i()->error( 'generic_error', '3T285/3', 403, '' );
		}

		$result = NULL;
		try
		{
			$result = TemplatesClass::importUserTemplateXml( Request::i()->file );
		}
		catch( Throwable $e )
		{
			@unlink( Request::i()->file );
		}

		/* Done */
		if ( $result instanceof Internal )
		{
			Output::i()->redirect( $result );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates&do=manage' ), 'cms_templates_imported' );
		}
	}

	/**
	 * Show and manage conflicts
	 *
	 * @return void
	 */
	public function conflicts() : void
	{
		$key         = Request::i()->key;
		$form        = new Form( 'form', 'theme_conflict_save' );
		$conflicts   = array();

		/* If this is part of plugin/app installation, set relevant form data */
		if( Request::i()->application OR Request::i()->plugin )
		{
			if( Request::i()->application )
			{
				$form->hiddenValues['application'] = Request::i()->application;
			}

			if( Request::i()->lang )
			{
				$form->hiddenValues['lang'] = Request::i()->lang;
			}
		}

		/* Get conflict data */
		foreach( Db::i()->select( '*', 'cms_template_conflicts', array( 'conflict_key=?', $key ) )->setKeyField( 'conflict_id' ) as $cid => $data )
		{
			$conflicts[ $cid ] = $data;
		}

		require_once ROOT_PATH . "/system/3rd_party/Diff/class.Diff.php";

		foreach( $conflicts as $cid => $data )
		{
			try
			{
				$template = TemplatesClass::load( $data['conflict_item_id'], 'template_id' );

				if ( !Login::compareHashes( md5( $data['conflict_content'] ), md5( $template->content ) ) )
				{
					if ( mb_strlen( $data['conflict_content'] ) <= 10000 )
					{
						$conflicts[ $cid ]['diff'] = Diff::toTable( Diff::compare( $template->content, $data['conflict_content'] ) );
						$conflicts[ $cid ]['large'] = false;
					}
					else
					{
						$conflicts[ $cid ]['diff'] = Theme::i()->getTemplate( 'customization', 'core' )->templateConflictLarge( $template->content, $data['conflict_content'], 'html' );
						$conflicts[ $cid ]['large'] = true;
					}

					$form->add( new Radio( 'conflict_' . $data['conflict_id'], 'old', false, array('options' => array('old' => '', 'new' => '')) ) );
				}
				else
				{
					unset( $conflicts[ $cid ] );
				}
			}
			catch( Exception $e )
			{
				unset( $conflicts[ $cid ] );
			}
		}

		if ( $values = $form->values() )
		{
			$conflicts   = array();
			$conflictIds = array();
			$templates = array();

			foreach( $values as $k => $v )
			{
				if ( substr( $k, 0, 9 ) == 'conflict_' )
				{
					if ( $v == 'new' )
					{
						$conflictIds[ (int) substr( $k, 9 ) ] = $v;
					}
				}
			}

			if ( count( $conflictIds ) )
			{
				/* Get conflict data */
				foreach( Db::i()->select( '*', 'cms_template_conflicts', Db::i()->in( 'conflict_id', array_keys( $conflictIds ) ) )->setKeyField( 'conflict_id' ) as $cid => $data )
				{
					$conflicts[ $data['conflict_item_id'] ] = $data;
				}
			}

			if ( count( $conflicts ) )
			{
				$templates = iterator_to_array( Db::i()->select(
					'*',
					'cms_templates',
					array( Db::i()->in( 'template_id', array_keys( $conflicts ) ) )
				)->setKeyField( 'template_id' ) );
			}

			foreach( $templates as $templateid => $template )
			{
				if ( isset( $conflicts[ $template['template_id'] ] ) )
				{
					try
					{
						$templateObj = TemplatesClass::load( $template['template_id'], 'template_id' );
						$templateObj->params = $conflicts[ $template['template_id'] ]['conflict_data'];
						$templateObj->content = $conflicts[ $template['template_id'] ]['conflict_content'];
						$templateObj->user_edited = (int) $templateObj->isDifferentFromMaster();
						$templateObj->save();
					}
					catch( Exception $e ) { }
				}
			}

			/* Clear out conflicts for this theme set */
			Db::i()->delete( 'cms_template_conflicts', array('conflict_key=?', Request::i()->key ) );

			$lang = NULL;
			if( !empty( $values['lang'] ) )
			{
				$lang = $values['lang'] == 'updated' ? 'application_now_updated' : 'application_now_installed';
			}
			
			elseif( !empty( $values['application'] ) )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=applications&controller=applications' ), $lang );
			}

			Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates&do=manage' ), 'completed' );
		}

		if ( count( $conflicts ) )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/diff.css', 'core', 'admin' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/codemirror.js', 'core', 'interface' ) );
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'codemirror/codemirror.css', 'core', 'interface' ) );
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customization/themes.css', 'core', 'admin' ) );
			Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_templates.js', 'core', 'admin' ) );

			Output::i()->output   = $form->customTemplate( array( Theme::i()->getTemplate( 'templates', 'cms' ), 'templateConflict' ), $conflicts );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates&do=manage' ), 'completed' );
		}
	}

	/**
	 * Export templates
	 *
	 * @return void
	 */
	public function export() : void
	{
		$form = TemplatesClass::exportForm();

		if ( $values = $form->values() )
		{
			$xml = TemplatesClass::exportAsXml( $values );

			if( $xml === NULL )
			{
				Output::i()->error( 'cms_no_templates_selected', '1T285/1', 403, '' );
			}

			Session::i()->log( 'acplogs__cms_exported_templates' );

			Output::i()->sendOutput( $xml->outputMemory(), 200, 'application/xml', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', "pages_templates.xml" ) ) );
		}
		
		Output::i()->breadcrumb[] = array( Url::internal( "app=cms&module=pages&controller=templates" ), Member::loggedIn()->language()->addToStack( 'menu__cms_pages_templates' ) );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('cms_templates_export_title') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('cms_templates_export_title');
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'cms_templates_export_title', $form, FALSE );
	}

	/**
	 * List templates
	 * 
	 * @return void
	 */
	public function manage() : void
	{
		Dispatcher::i()->checkAcpPermission( 'template_add_edit' );
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__cms_pages_templates');

		$request = array(
			't_location' => ( isset( Request::i()->t_location ) ) ? Request::i()->t_location : NULL,
			't_group'    => ( isset( Request::i()->t_group ) ) ? Request::i()->t_group : NULL,
			't_key'      => ( isset( Request::i()->t_key ) ) ? Request::i()->t_key : NULL,
			't_type'     => ( isset( Request::i()->t_type ) ) ? Request::i()->t_type : 'templates',
		);

		switch ( $request['t_type'] )
		{
			default:
			case 'template':
				$flag = TemplatesClass::RETURN_ONLY_TEMPLATE;
				break;
			case 'js':
				$flag = TemplatesClass::RETURN_ONLY_JS;
				break;
			case 'css':
				$flag = TemplatesClass::RETURN_ONLY_CSS;
				break;
		}

		$templates = TemplatesClass::buildTree( TemplatesClass::getTemplates( $flag + TemplatesClass::RETURN_DATABASE_ONLY ) );

		$current = NULL;

		if ( !empty( $request['t_key'] ) )
		{
			try
			{
				$current = TemplatesClass::load( $request['t_key'] );
			}
			catch ( OutOfRangeException $ex )
			{

			}
		}

		/* Load first block */
		if ( $current === NULL )
		{
			foreach ( $templates as $type => $_templates )
			{
				if ( $_templates )
				{
					$test = key( $_templates );

					try
					{
						$current = TemplatesClass::load( $test );
					}
					catch ( OutofRangeException $e )
					{
						foreach ( $_templates as $location => $group )
						{
							foreach ( $group as $name => $template )
							{
								$current = $template;
								break 3;
							}
						}
					}
				}
			}
		}

		/* Display */
		Output::i()->responsive = FALSE;

		/* A button */
		if ( \IPS\IN_DEV )
		{
			Output::i()->sidebar['actions']['add'] = array(
				'icon'  => 'cog',
				'title' => 'content_import_dev_templates',
				'link'  => Url::internal( "app=cms&module=pages&controller=templates&do=importInDev" )->csrf(),
				'data'  => array()
			);
		}

		Output::i()->sidebar['actions']['download'] = array(
			'icon'  => 'download',
			'title' => 'cms_templates_export_title',
			'link'  => Url::internal( "app=cms&module=pages&controller=templates&do=export" ),
		);

		Output::i()->sidebar['actions']['upload'] = array(
			'icon'  => 'upload',
			'title' => 'cms_templates_import_title',
			'link'  => Url::internal( "app=cms&module=pages&controller=templates&do=import" ),
			'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('cms_templates_import_title') )
		);

		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/diff_match_patch.js', 'core', 'interface' ) );
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'codemirror/codemirror.js', 'core', 'interface' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'codemirror/codemirror.css', 'core', 'interface' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'templates/templates.css', 'cms', 'admin' ) );
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_templates.js', 'cms' ) );

		Output::i()->output = Theme::i()->getTemplate( 'templates' )->templates( $templates, $current, $request );
	}
	
	/**
	 * Add Container
	 *
	 * @return	void
	 */
	public function addContainer() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'template_add' );
	
		$type = Request::i()->type;
	
		/* Build form */
		$form = new Form();
	
		$form->add( new Text( 'container_name', NULL, TRUE ) );
		$form->hiddenValues['type'] = $type;
	
		if ( $values = $form->values() )
		{
			$type = Request::i()->type;
				
			$newContainer = Container::add( array(
					'name' => $values['container_name'],
					'type' => 'template_' . $type
			) );

			Session::i()->log( 'acplogs__cms_template_container', array( $newContainer->title => false ) );
	
			if( Request::i()->isAjax() )
			{
				Output::i()->json( array(
					'id'   => $newContainer->id,
					'name' => $newContainer->title,
				) );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates' ), 'saved' );
			}
		}
	
		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->block( 'content_template_add_container_' . $type, $form, FALSE );
	}
	
	/**
	 * Add Template
	 * This is never used for editing as this is done via the template manager
	 *
	 * @return	void
	 */
	public function addTemplate() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'template_add_edit' );
		
		$type = Request::i()->type;
		
		/* Build form */
		$form = new Form();
		$form->hiddenValues['type'] = $type;
		
		$form->add( new Text( 'template_title', NULL, TRUE, array( 'regex' => '/^([A-Z_][A-Z0-9_]+?)$/i' ), function ( $val ) {
			/* PHP Keywords cannot be used as template names - so make sure the full template name is not in the list */
			$keywords = array( 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor' );
			
			if ( in_array( $val, $keywords ) )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'template_reserved_word', FALSE, array( 'htmlsprintf' => array( Member::loggedIn()->language()->formatList( $keywords ) ) ) ) );
			}

			try
			{
				$count = Db::i()->select( 'COUNT(*)', 'cms_templates', array( "LOWER(template_title)=?", mb_strtolower( str_replace( ' ', '_', $val ) )  ) )->first();
				
				if ( $count )
				{
					throw new DomainException( 'cms_template_title_exists' );
				}
			}
			catch( UnderflowException $e ) {}
		} ) );

		/* Very specific */
		if ( $type === 'database' )
		{
			$groups = array(); /* I was sorely tempted to put 'Radiohead', 'Beatles' in there */
			foreach( Databases::$templateGroups as $key => $group )
			{
				$groups[ $key ] = Member::loggedIn()->language()->addToStack( 'cms_new_db_template_group_' . $key );
			}

			$form->add( new Select( 'database_template_type', NULL, FALSE, array(
				'options' => $groups
			) ) );

			$databases = array( 0 => Member::loggedIn()->language()->addToStack('cms_new_db_assign_to_db_none' ) );
			foreach( Databases::databases() as $obj )
			{
				$databases[ $obj->id ] = $obj->_title;
			}

			$form->add( new Select( 'database_assign_to', NULL, FALSE, array(
				'options' => $databases
			) ) );
		}
		else if ( $type === 'block' )
		{
			$plugins = array();
			foreach ( Db::i()->select( "*", 'core_widgets', array( 'embeddable=1') ) as $widget )
			{
				/* Skip disabled applications */
				if ( !in_array( $widget['app'], array_keys( Application::enabledApplications() ) ) )
				{
					continue;
				}

				try
				{
					$plugins[ Application::load( $widget['app'] )->_title ][ $widget['app'] . '__' . $widget['key'] ] = Member::loggedIn()->language()->addToStack( 'block_' . $widget['key'] );
				}
				catch ( OutOfRangeException $e ) { }
			}
			
			$form->add( new Select( 'block_template_plugin_import', NULL, FALSE, array(
					'options' => $plugins
			) ) );
		
			$form->add( new Node( 'block_template_theme_import', NULL, TRUE, array(
				'class' => '\IPS\Theme'
			) ) );
		}
		else
		{
			/* Page, css, js */
			switch( $type )
			{
				default:
					$flag = \IPS\cms\Theme::RETURN_ONLY_TEMPLATE;
				break;
				case 'page':
					$flag = \IPS\cms\Theme::RETURN_PAGE;
				break;
				case 'js':
					$flag = \IPS\cms\Theme::RETURN_ONLY_JS;
				break;
				case 'css':
					$flag = \IPS\cms\Theme::RETURN_ONLY_CSS;
				break;
			}

			$templates = \IPS\cms\Theme::i()->getAllTemplates( array(), array(), array(), $flag | Theme::RETURN_ALL_NO_CONTENT );

			$groups = array();

			if ( isset( $templates['cms'][ $type ] ) )
			{
				foreach( $templates['cms'][ $type ] as $group => $data )
				{
					$groups[ $group ] = TemplatesClass::readableGroupName( $group );
				}
			}

			if ( ! count( $groups ) )
			{
				$groups[ $type ] = TemplatesClass::readableGroupName( $type );
			}

			$form->add( new Radio( 'theme_template_group_type', 'existing', FALSE, array(
				            'options'  => array( 'existing' => 'theme_template_group_o_existing',
				                                 'new'	    => 'theme_template_group_o_new' ),
				            'toggles'  => array( 'existing' => array( 'group_existing' ),
				                                 'new'      => array( 'group_new' ) )
			            ) ) );

			$form->add( new Text( 'template_group_new', NULL, FALSE, array( 'regex' => '/^([a-z_][a-z0-9_]+?)?$/' ), function( $val ) {
				/* PHP Keywords cannot be used as template names - so make sure the full template name is not in the list */
				$keywords = array( 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor' );

				if ( in_array( $val, $keywords ) )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'template_reserved_word', FALSE, array( 'htmlsprintf' => array( Member::loggedIn()->language()->formatList( $keywords ) ) ) ) );
				}

				try
				{
					$count = Db::i()->select( 'COUNT(*)', 'cms_templates', array( "LOWER(template_group)=?", mb_strtolower( str_replace( ' ', '_', $val ) )  ) )->first();
	
					if ( $count )
					{
						throw new DomainException( 'cms_template_group_exists' );
					}
				}
				catch( UnderflowException $e ) {}
			}, NULL, NULL, 'group_new' ) );
			$form->add( new Select( 'template_group_existing', NULL, FALSE, array( 'options' => $groups ), NULL, NULL, NULL, 'group_existing' ) );
		}

		if ( ! Request::i()->isAjax() AND $type !== 'database' )
		{
			$form->add( new TextArea( 'template_content', NULL ) );
		}
	
		if ( $values = $form->values() )
		{
			$type = Request::i()->type;

			if ( $type == 'database' )
			{
				/* We need to copy templates */
				$group     = Databases::$templateGroups[ $values['database_template_type' ] ];
				$templates = iterator_to_array( Db::i()->select( '*', 'cms_templates', array( 'template_location=? AND template_group=? AND template_user_edited=0 AND template_user_created=0', 'database', $group ) ) );

				foreach( $templates as $template )
				{
					unset( $template['template_id'] );
					$template['template_original_group'] = $template['template_group'];
					$template['template_group'] = str_replace( '-', '_', Friendly::seoTitle( $values['template_title'] ) );

					$save = array();
					foreach( $template as $k => $v )
					{
						$k = mb_substr( $k, 9 );
						$save[ $k ] = $v;
					}

					/* Make sure template tags call the correct group */
					if ( mb_stristr( $save['content'], '{template' ) )
					{
						preg_match_all( '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/', $save['content'], $matches, PREG_SET_ORDER );

						/* Work out the plugin and the values to pass */
						foreach( $matches as $index => $array )
						{
							preg_match_all( '/(.+?)=' . $array[ 2 ] . '(.+?)' . $array[ 2 ] . '\s?/', $array[ 1 ], $submatches );

							$plugin = array_shift( $submatches[ 1 ] );
							if ( $plugin == 'template' )
							{
								$value   = array_shift( $submatches[ 2 ] );
								$options = array();

								foreach ( $submatches[ 1 ] as $k => $v )
								{
									$options[ $v ] = $submatches[ 2 ][ $k ];
								}

								if ( isset( $options['app'] ) and $options['app'] == 'cms' and isset( $options['location'] ) and $options['location'] == 'database' and isset( $options['group'] ) and $options['group'] == $template['template_original_group'] )
								{
									$options['group'] = $template['template_group'];

									$replace = '{template="' . $value . '" app="' . $options['app'] . '" location="' . $options['location'] . '" group="' . $options['group'] . '" params="' . ($options['params'] ?? NULL) . '"}';

									$save['content'] = str_replace( $matches[$index][0], $replace, $save['content'] );
								}
							}
						}
					}

					$newTemplate = TemplatesClass::add( $save );
				}

				if ( $values['database_assign_to'] )
				{
					try
					{
						$db   = Databases::load( $values['database_assign_to'] );
						$displaySettings = $db->display_settings;
						if( !isset( $displaySettings[ $values['database_template_type'] ] ) )
						{
							$displaySettings[ $values['database_template_type'] ] = [];
						}
						$displaySettings[ $values['database_template_type'] ]['layout'] = 'custom';
						$displaySettings[ $values['database_template_type'] ]['template'] = $template['template_group'];
						$db->display_settings = $displaySettings;
						$db->save();
					}
					catch( OutOfRangeException $ex ) { }
				}
			}
			else if ( $type === 'block' )
			{
				$save = array(
					'title'	   => str_replace( '-', '_', Friendly::seoTitle( $values['template_title'] ) ),
					//'params'   => isset( $values['template_params'] ) ? $values['template_params'] : null,
					'location' => $type
				);

				/* Get template */
				list( $widgetApp, $widgetKey ) = explode( '__', $values['block_template_plugin_import'] );

				/* Find it from the normal template system */
				$plugin = Widget::load( Application::load( $widgetApp ), $widgetKey, mt_rand(), array() );

				$location = $plugin->getTemplateLocation();

				$theme = ( \IPS\IN_DEV ) ? Theme::master() : $values['block_template_theme_import'];
				$templateBits  = $theme->getAllTemplates( $location['app'], $location['location'], $location['group'], Theme::RETURN_ALL );
				$templateBit   = $templateBits[ $location['app'] ][ $location['location'] ][ $location['group'] ][ $location['name'] ];

				$save['content'] = $templateBit['template_content'];
				$save['params']  = $templateBit['template_data'];
				$save['group']   = $widgetKey;
				$newTemplate = TemplatesClass::add( $save );
			}
			else
			{
				$save = array( 'title' => $values['template_title'] );

				/* Page, css, js */
				if ( $type == 'js' or $type == 'css' )
				{
					$fileExt = ( $type == 'js' ) ? '.js' : ( $type == 'css' ? '.css' : NULL );
					if ( $fileExt AND ! preg_match( '#' . preg_quote( $fileExt, '#' ) . '$#', $values['template_title'] ) )
					{
						$values['template_title'] .= $fileExt;
					}

					$save['title'] = $values['template_title'];
					$save['type']  = $type;
				}
				
				if ( $type === 'page' AND $values['theme_template_group_type'] == 'existing' AND $values['template_group_existing'] == 'custom_wrappers' )
				{
					$save['params'] = '$html=NULL, $title=NULL';
				}
				
				if ( $type === 'page' AND $values['theme_template_group_type'] == 'existing' AND $values['template_group_existing'] == 'page_builder' )
				{
					$save['params'] = '$page, $widgets';
				}

				$save['group'] = ( $values['theme_template_group_type'] == 'existing' ) ? $values['template_group_existing'] : $values['template_group_new'];

				if ( isset( $values['template_content'] ) )
				{
					$save['content'] = $values['template_content'];
				}

				$save['location'] = $type;

				$newTemplate = TemplatesClass::add( $save );
			}

			Session::i()->log( 'acplogs__cms_template_add', array( $newTemplate->title => false ) );

			/* Done */
			if( Request::i()->isAjax() )
			{
				Output::i()->json( array(
					'id'		=> $newTemplate->id,
					'title'		=> $newTemplate->title,
					'params'	=> $newTemplate->params,
					'desc'		=> $newTemplate->description,
					'container'	=> $newTemplate->container,
					'location'	=> $newTemplate->location
				)	);
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates' )->setQueryString( ['id' => $newTemplate->id, 't_location' => $newTemplate->location, 't_type' => $type ] ), 'saved' );
			}
		}
	
		/* Display */
		$title = strip_tags( Member::loggedIn()->language()->get( 'content_template_add_template_' . $type ) );
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->block( $title, $form, FALSE );
		Output::i()->title  = $title;
	}
	
	/**
	 * Delete a template
	 * This can be either a CSS template or a HTML template
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'template_delete' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		$key    = Request::i()->t_key;
		$return = array(
			'template_content' => NULL,
			'template_id' 	   => NULL
		);

		$originalTemplate = TemplatesClass::load( $key );
		
		try
		{
			TemplatesClass::load( $key )->delete();
			
			/* Now reload */
			try
			{
				$template = TemplatesClass::load( $key );
				
				$return['template_location'] = $template->location;
				$return['template_content']  = $template->content;
				$return['template_id']		 = $template->id;
				$return['InheritedValue']    = ( $template->user_added ) ? 'custom' : ( $template->user_edited ? 'changed' : 0 );
			}
			catch( OutOfRangeException $ex )
			{
				
			}
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'node_error', '3T285/4', 500, '' );
		}

		Session::i()->log( 'acplogs__cms_template_delete', array( $originalTemplate->title => false ) );

		if( Request::i()->isAjax() )
		{
			Output::i()->json( $return );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates' ), 'completed' );
		}
	}
	
	/**
	 * Show a difference report for an individual template file
	 *
	 * @return	void
	 */
	protected function diffTemplate() : void
	{
		$customVersion = Db::i()->select( '*', 'cms_templates', array( 'template_id=?', (int) Request::i()->t_item_id ) )->first();
		
		try 
		{
			$original = Db::i()->select( 'template_content', 'cms_templates', array( 'template_location=? and template_group=? and template_title=? and template_master=1', $customVersion['template_location'], $customVersion['template_original_group'], $customVersion['template_title'] ) )->first();
		}
		catch( UnderflowException $e )
		{
			$original = FALSE;
		}
		
		Output::i()->json( $original );
	}
	
	/**
	 * Saves a template
	 * 
	 * @return void
	 */
	public function save() : void
	{
		Session::i()->csrfCheck();

		$key = Request::i()->t_key;
		
		$contentKey = 'editor_' . $key;

		$content     = Request::i()->$contentKey;
		$description = Request::i()->t_description;
		$variables   = isset( Request::i()->t_variables ) ? Request::i()->t_variables : '';
		
		try
		{
			$obj = TemplatesClass::load( $key );

			if ( $obj->master )
			{
				/* Do not edit a master bit directly, but overload it */
				$clone = new TemplatesClass;
				$clone->key = $obj->key;
				$clone->title = Request::i()->t_name;
				$clone->content = $content;
				$clone->location = Request::i()->t_location;
				$clone->group = empty( Request::i()->t_group ) ? null : Request::i()->t_group;
				$clone->params = $variables;
				$clone->container = $obj->container;
				$clone->position = $obj->position;
				$clone->user_edited = 1;
				$clone->master = 0;
				$clone->save();
			}
			else
			{
				$obj->location = Request::i()->t_location;
				$obj->group = empty( Request::i()->t_group ) ? null : Request::i()->t_group;
				$obj->title = Request::i()->t_name;
				$obj->params = $variables;
				$obj->content = $content;
				$obj->user_edited = 1;
			}

			if( $description )
			{
				$obj->description = $description;
			}
			$obj->save();
			
			$url = array(
				't_location'  => $obj->location,
				't_group'     => $obj->group,
				't_key'       => $key
			);
		}
		catch( Exception $ex )
		{
			Output::i()->json( array( 'msg' => $ex->getMessage() ) );
		}
		
		if ( isset( Request::i()->t_type ) and Request::i()->t_type !== 'js' )
		{
			/* Test */
			try
			{
				Theme::checkTemplateSyntax( $content, $variables );
			}
			catch( Exception $e )
			{
				Output::i()->json( array( 'msg' => Member::loggedIn()->language()->get('cms_page_error_bad_syntax') ) );
			}
		}

		/* reload to return new item Id */
		$obj = TemplatesClass::load( $key );
		
		/* Clear block caches */
		Block::deleteCompiled();

		Session::i()->log( 'acplogs__cms_template_save', array( Request::i()->t_name => false ) );
		
		if(  Request::i()->isAjax() )
		{
			Output::i()->json( array( 'template_id' => $obj->id, 'template_title' => $obj->title, 'template_container' => $obj->container, 'template_user_added' => $obj->user_created ) );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates&' . implode( '&', $url ) ), 'completed' );
		}
	}
		
	/**
	 * Display template options for a database template group
	 *
	 * @return void
	 */
	public function databaseTemplateGroupOptions(): void
	{
		$form = new Form();
		
		$databases = array();
		foreach( Db::i()->select( '*', 'cms_databases', array( "database_display_settings like concat( '%',?,'%')", "\"template\":\"" . Request::i()->group ) ) as $database )
		{
			$databases[ $database['database_id'] ] = Databases::constructFromData( $database );
		}
		
		if ( count( $databases ) )
		{
			$names = array();
			foreach( $databases as $db )
			{
				$names[] = $db->_title;
			}
			
			$form->addMessage( Member::loggedIn()->language()->addToStack( 'cms_database_template_used_in', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $names ) ) ) ), 'ipsMessage ipsMessage--info' );
		}
		
		$form->add( new Text( 'cms_database_group_name', Request::i()->group, NULL, array( 'regex' => '/^([a-z_][a-z0-9_]+?)?$/' ), function( $val ) {
			/* PHP Keywords cannot be used as template names - so make sure the full template name is not in the list */
			$keywords = array( 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor' );
			
			if ( in_array( $val, $keywords ) )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'template_reserved_word', FALSE, array( 'htmlsprintf' => array( Member::loggedIn()->language()->formatList( $keywords ) ) ) ) );
			}
			
			if ( mb_strtolower( str_replace( ' ', '_', $val ) ) != mb_strtolower( str_replace( ' ', '_', Request::i()->group ) ) )
			{
				$count = Db::i()->select( 'COUNT(*)', 'cms_templates', array( "LOWER(template_group)=?", mb_strtolower( str_replace( ' ', '_', $val ) ) ) )->first();
				if ( $count )
				{
					throw new DomainException( 'cms_template_group_exists' );
				}
			}
		} ) );
		$form->addButton( "delete", "link", Url::internal( 'app=cms&module=pages&controller=templates&do=deleteTemplateGroup&group=' . Request::i()->group . '&t_location=' . Request::i()->t_location )->csrf(), 'ipsButton ipsButton--negative', array( 'data-confirm' => 'true' ) );
		
		if ( $values = $form->values() )
		{
			$new = str_replace( ' ', '_', mb_strtolower( $values['cms_database_group_name'] ) );

			if ( $new != Request::i()->group )
			{
				Db::i()->update( 'cms_templates', array( 'template_group' => $new ), array( 'template_location=? and template_group=?', 'database', Request::i()->group ) );

				foreach( Databases::databases() as $db )
				{
					$displaySettings = $db->display_settings;
					foreach( TemplatesClass::$databaseDefaults as $field => $template )
					{
						if( isset( $displaySettings[ $field ] ) and $displaySettings[ $field ]['template'] == Request::i()->group )
						{
							$displaySettings[ $field ]['template'] = mb_strtolower( $new );
						}
					}
					$db->display_settings = $displaySettings;
					$db->save();
				}

				unset( Store::i()->cms_databases );

				$this->findAndUpdateTemplates( $new, Request::i()->group );
			}
			
			Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates' . ( isset( Request::i()->t_location ) ? '&t_location=' . Request::i()->t_location : '' ) ), 'saved' );
		}
		
		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->block( Member::loggedIn()->language()->addToStack('cms_template_database_options'), $form, FALSE, '', NULL, TRUE );
	}

	/**
	 * Find templates referencing a template and update them
	 *
	 * @param string $new	New template group name
	 * @param string $old	Old template group name
	 * @return	void
	 */
	protected function findAndUpdateTemplates( string $new, string $old ) : void
	{
		Session::i()->csrfCheck();
		
		foreach( Db::i()->select( '*', 'cms_templates', array( 'template_content LIKE ?', '%' . $old . '%' ) ) as $template )
		{
			/* Make sure template tags call the correct group */
			if ( mb_stristr( $template['template_content'], '{template' ) )
			{
				preg_match_all( '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/', $template['template_content'], $matches, PREG_SET_ORDER );

				/* Work out the plugin and the values to pass */
				foreach( $matches as $index => $array )
				{
					preg_match_all( '/(.+?)=' . $array[ 2 ] . '(.+?)' . $array[ 2 ] . '\s?/', $array[ 1 ], $submatches );

					$plugin = array_shift( $submatches[ 1 ] );
					if ( $plugin == 'template' )
					{
						$value   = array_shift( $submatches[ 2 ] );
						$options = array();

						foreach ( $submatches[ 1 ] as $k => $v )
						{
							$options[ $v ] = $submatches[ 2 ][ $k ];
						}

						if ( isset( $options['app'] ) and $options['app'] == 'cms' and isset( $options['location'] ) and $options['location'] == 'database' and isset( $options['group'] ) and $options['group'] == mb_strtolower( $old ) )
						{
							$replace = '{template="' . $value . '" app="' . $options['app'] . '" location="' . $options['location'] . '" group="' . mb_strtolower( $new ) . '" params="' . ($options['params'] ?? NULL) . '"}';

							Db::i()->update( 'cms_templates', array( 'template_content' => str_replace( $matches[$index][0], $replace, $template['template_content'] ) ), array( 'template_id=?', $template['template_id'] ) );
						}
					}
				}
			}
		}
	}
	
	/**
	 * Delete the template group! OH NOES
	 *
	 * @return void
	 */
	public function deleteTemplateGroup() : void
	{
		Session::i()->csrfCheck();

		foreach( Databases::databases() as $db )
		{
			$displaySettings = $db->display_settings;
			foreach( TemplatesClass::$databaseDefaults as $field => $template )
			{
				if( isset( $displaySettings[ $field ] ) and $displaySettings[ $field ]['template'] == Request::i()->group )
				{
					switch( $field )
					{
						case 'index':
							$displaySettings[ $field ]['layout'] = 'featured';
							$displaySettings[ $field ]['template'] = null;
							break;
						case 'listing':
						case 'categories':
							$displaySettings[ $field ]['layout'] = 'table';
							$displaySettings[ $field ]['template'] = null;
							break;
						case 'display':
						case 'form':
							$displaySettings[ $field ]['layout'] = 'custom';
							$displaySettings[ $field ]['template'] = $template;
							break;
					}
				}
			}
			$db->display_settings = $displaySettings;
			$db->save();
		}
	
		Db::i()->delete( 'cms_templates', array( 'template_location=? and template_group=?', 'database', Request::i()->group ) );
		
		unset( Store::i()->cms_databases );

		Session::i()->log( 'acplogs__cms_template_group_delete', array( Request::i()->group => false ) );
		
		Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=templates' . ( isset( Request::i()->t_location ) ? '&t_location=' . Request::i()->t_location : '' ) ), 'deleted' );
	}
}