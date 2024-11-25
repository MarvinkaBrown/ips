<?php
/**
 * @brief		Databases Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		31 March 2014
 */

namespace IPS\cms\modules\admin\databases;
	
/* To prevent PHP errors (extending class does not exist) revealing path */

use Cassandra\Set;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\cms\Categories;
use IPS\cms\Databases as CmsDatabases;
use IPS\cms\Pages\Page;
use IPS\cms\Templates;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Patterns\Bitwise;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use IPS\Widget\Area;
use LogicException;
use OutOfRangeException;
use SimpleXMLElement;
use UnderflowException;
use XMLReader;
use XMLWriter;
use function count;
use function defined;
use function file_get_contents;
use function in_array;
use function intval;
use function is_array;
use function ucfirst;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}
	
/**
 * databases
 */
class databases extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\cms\Databases';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'databases_use' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Create the table */
		$table = new TableDb( 'cms_databases', Url::internal( 'app=cms&module=databases&controller=databases' ) );
		$table->langPrefix = 'content_';

		/* Columns */
		$table->joins = array(
			array( 'select' => 'w.word_custom', 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'content_db_', cms_databases.database_id ) AND w.lang_id=" . Member::loggedIn()->language()->id )
		);

		$table->include = array( 'word_custom', 'database_record_count', 'database_category_count' );
		$table->widths  = array(
			'word_custom' => '50'
		);
		
		$table->mainColumn = 'word_custom';
		$table->quickSearch = 'word_custom';
		
		$table->sortBy = $table->sortBy ?: 'word_custom';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		
		/* Parsers */
		$table->parsers = array(
				'word_custom'	=> function( $val, $row )
				{
					$page     = NULL;
					$database = NULL;

					try
					{
						$database = CmsDatabases::load( $row['database_id'] );

						if ( $database->page_id > 0 )
						{
							try
							{
								$page = Page::load( $database->page_id );
							}
							catch ( OutOfRangeException $ex )
							{
								$database->page_id = 0;
								$database->save();
							}
						}
					}
					catch ( OutOfRangeException $ex )
					{

					}

					return Theme::i()->getTemplate( 'databases' )->manageDatabaseName( $database, $row, $page );
				},
				'database_category_count' => function( $val, $row )
				{
					try
					{
						$database = CmsDatabases::load( $row['database_id'] );

						if ( ! $database->use_categories )
						{
							return Member::loggedIn()->language()->addToStack('cms_db_cats_disabled');
						}
					}
					catch ( OutOfRangeException )
					{

					}

					/* This sucks but adding a COUNT into a join breaks the query and sub selects not easy with DB driver */
					return Db::i()->select( 'COUNT(*)', 'cms_database_categories', array( 'category_database_id=?', $row['database_id'] ) )->first();
				},
		        'database_record_count' => function( $val, $row )
				{
					return Db::i()->select( 'SUM(category_records)', 'cms_database_categories', array( 'category_database_id=?', $row['database_id'] ) )->first();
				}
		);

		if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'databases', 'databases_add' ) )
		{
			/* Buttons */
			Output::i()->sidebar['actions']['add'] = array(
				'primary'	=> true,
				'title'	=> 'cms_database_add_new',
				'icon'	=> 'plus',
				'link'	=> Url::internal( 'app=cms&module=databases&controller=databases&do=form' )
			);
			Output::i()->sidebar['actions']['import'] = array(
				'primary' => true,
				'title' => 'cms_database_add_upload',
				'icon' => 'upload',
				'link' => Url::internal( "app=cms&module=databases&controller=databases&do=add" ),
				'data' => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'cms_database_add_upload' ) )
			);
		}


		$table->rowButtons = function( $row )
		{
			$return = array();
			
			if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'databases', 'databases_edit' ) )
			{
				$return['edit']	= array(
					'title'	=> 'edit',
					'icon'	=> 'pencil',
					'link'	=> Url::internal( 'app=cms&module=databases&controller=databases&do=form&id=' . $row['database_id'] ),
				);
			}
			
			$return['records']	= array(
				'title'	=> 'content_database_manage_records',
				'icon'	=> 'file-text',
				'link'	=> Url::internal( 'app=cms&module=databases&controller=records&database_id=' . $row['database_id'] ),
			);
			
			$return['permissions'] = array(
					'title'	=> 'content_database_manage_permissions',
					'icon'	=> 'lock',
					'link'	=> Url::internal( 'app=cms&module=databases&controller=databases&do=permissions&id=' . $row['database_id'] ),
			);

			if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'categories', 'categories_manage' ) and $row['database_use_categories'] )
			{
				$return['categories'] = array(
					'title'	=> 'content_database_manage_categories',
					'icon'	=> 'folder',
					'link'	=> Url::internal( 'app=cms&module=databases&controller=categories&database_id=' . $row['database_id'] ),
				);
			}
			
			if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'fields', 'cms_fields_manage' ) )
			{
				$return['fields'] = array(
					'title'	=> 'content_database_manage_fields',
					'icon'	=> 'tasks',
					'link'	=> Url::internal( 'app=cms&module=databases&controller=fields&database_id=' . $row['database_id'] ),
				);
			}

			if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'databases', 'databases_edit' ) )
			{
				$return['download']	= array(
					'title'	=> 'download',
					'icon'	=> 'download',
					'link'	=> Url::internal( 'app=cms&module=databases&controller=databases&do=download&id=' . $row['database_id'] ),
					'data'	=> array(
						'controller'	=> 'cms.admin.databases.download',
						'downloadURL'	=> Url::internal( "app=cms&module=databases&controller=databases&do=download&id=" . $row['database_id'] . '&go=true' )
					)
				);
			}

			if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'databases', 'databases_delete' ) )
			{
				$return['delete'] = array(
					'title'	=> 'delete',
					'icon'	=> 'times-circle',
					'link'	=> Url::internal( 'app=cms&module=databases&controller=databases&do=delete&id=' . $row['database_id'] ),
					'data'  => array( 'delete' => '' )
				);

				if ( CmsDatabases::isUsedAsReciprocalField( $row['database_id'] ) )
				{
					unset( $return['delete']['data'] );
				}
			}
			
			return $return;
		};

		/* Javascript */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_databases.js', 'cms', 'admin' ) );
		
		/* Display */
		Output::i()->output = (string) $table;
		Output::i()->title  = Member::loggedIn()->language()->addToStack('menu__cms_databases_databases');
	}

	/**
	 * Add a database dialog
	 *
	 * @return void
	 */
	public function add() : void
	{
		Dispatcher::i()->checkAcpPermission( 'databases_add' );

		$form = new Form( 'form', 'continue' );

		$form->addMessage( 'cms_database_import_info', 'ipsMessage ipsMessage--warning' );

		$form->add( new Upload( 'cms_database_import', NULL, FALSE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ), NULL, NULL, NULL, 'cms_database_import' ) );

		if ( $values = $form->values() )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['cms_database_import'], $tempFile );

			/* Initate a redirector */
			Output::i()->redirect( Url::internal( 'app=cms&module=databases&controller=databases&do=import' )->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ) ) )->csrf() );
		}

		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'cms_database_add_upload', $form, FALSE );
	}

	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	public function import() : void
	{
		Dispatcher::i()->checkAcpPermission( 'databases_add' );
		Session::i()->csrfCheck();

		if ( !file_exists( Request::i()->file ) or md5_file( Request::i()->file ) !== Request::i()->key )
		{
			Output::i()->error( 'generic_error', '3T259/3', 403, '' );
		}

		Output::i()->output = new MultipleRedirect(
			Url::internal( 'app=cms&module=databases&controller=databases&do=import' )->setQueryString( array( 'file' => Request::i()->file, 'key' =>  Request::i()->key ) )->csrf(),
			function( $data )
			{
				/* Open XML file */
				$xml = \IPS\Xml\XMLReader::safeOpen( Request::i()->file );

				if ( ! @$xml->read() )
				{
					@unlink( Request::i()->file );
					Output::i()->error( 'xml_upload_invalid', '2T259/5', 403, '' );
				}

				/* Is this the first batch? */
				$i        = 0;
				if ( !is_array( $data ) )
				{
					$database = new CmsDatabases;
					$database->key  = 'import_' . mt_rand();
					$database->save();

					/* Set default perms, these will be editable post DB import */
					Db::i()->replace( 'core_permission_index', array(
						'app'			=> 'cms',
						'perm_type'		=> $database::$permType,
						'perm_type_id'	=> $database->id,
						'perm_view'		=> '*',
						'perm_2'		=> '*',  #read
						'perm_3'		=> Settings::i()->admin_group,  #add
						'perm_4'		=> Settings::i()->admin_group,  #edit
						'perm_5'		=> Settings::i()->admin_group,  #reply
						'perm_6'		=> Settings::i()->admin_group,  #rate
						'perm_7'		=> Settings::i()->admin_group,  #review
					) );

					$json  = json_decode( @file_get_contents( \IPS\ROOT_PATH . "/applications/cms/data/databaseschema.json" ), true );
					$table = $json['cms_custom_database_1'];

					$table['name'] = 'cms_custom_database_' . $database->id;

					foreach( $table['columns'] as $name => $data )
					{
						if ( mb_substr( $name, 0, 6 ) === 'field_' )
						{
							unset( $table['columns'][ $name ] );
						}
					}

					foreach( $table['indexes'] as $name => $data )
					{
						if ( mb_substr( $name, 0, 6 ) === 'field_' )
						{
							unset( $table['indexes'][ $name ] );
						}
					}

					try
					{
						if ( ! Db::i()->checkForTable( $table['name'] ) )
						{
							Db::i()->createTable( $table );
						}
					}
					catch(Db\Exception $ex )
					{
						throw new LogicException( $ex );
					}

					/* What version are we importing from? */
					$data['sourceVersion'] = $xml->getAttribute( 'version' );
					
					$langs = array();
					foreach( array( 'content_db_lang_sl', 'content_db_lang_pl', 'content_db_lang_su', 'content_db_lang_pu', 'content_db_lang_ia' ) as $lang )
					{
						if ( $xml->getAttribute( $lang ) )
						{
							$langs[ $lang ] = $xml->getAttribute( $lang );
							
							Lang::saveCustom( 'cms', $lang . "_" . $database->id, $xml->getAttribute( $lang ) );

							if ( $lang === 'content_db_lang_pu' )
							{
								Lang::saveCustom( 'cms', $lang . "_" . $database->id . '_pl', $xml->getAttribute( $lang ) );
							}
						}
					}
					
					/* Other data */
					while ( $xml->read() )
					{
						$name = NULL;
						$desc = NULL;
						$displaySettings = [
							'index' => [ 'type' => 'categories', 'layout' => 'table' ]
						];
						
						if ( $xml->name == 'data' )
						{
							/* Life is too short otherwise */
							$node = new SimpleXMLElement( $xml->readOuterXML() );
							foreach( $node->attributes() as $k => $v )
							{
								switch( $k )
								{
									case 'template_listing':
										$displaySettings['listing'] = ( $v == 'listing' ) ? [ 'layout' => 'table', 'template' => null ] : [ 'layout' => 'custom', 'template' => (string) $v ];
										break;
									case 'template_display':
										$displaySettings['display'] = [ 'layout' => 'custom', 'template' => (string) $v ];
										break;
									case 'template_form':
										$displaySettings['form'] = [ 'layout' => 'custom', 'template' => (string) $v ];
										break;
									case 'template_categories':
										$displaySettings['categories'] = ( $v == 'category_index' ) ? [ 'layout' => 'table', 'template' => null ] : [ 'layout' => 'custom', 'template' => (string) $v ];
										break;
									case 'template_featured':
										break;
									case 'cat_index_type':
										if( $v )
										{
											$displaySettings['index'] = [ 'type' => 'all', 'layout' => 'grid' ];
										}
										break;
									default:
										$database->$k = (string) $v;
										break;
								}
							}

							/* Any kids of your own? */
							foreach( $node->children() as $k => $v )
							{
								switch( $k )
								{
									case 'name':
										$name = $v;
										break;
									case 'description':
										$desc = $v;
										break;
									case 'featured_settings':
										$json = json_decode( $v, true );
										if( isset( $json['featured'] ) and $json['featured'] )
										{
											$displaySettings['index']['type'] = 'featured';
										}
										break;
									default:
										$tryJson = json_decode( $v, TRUE );
										$database->$k =  ( $tryJson ) ?: (string) $v;
										break;
								}
							}

							$database->display_settings = $displaySettings;
							
							Lang::saveCustom( 'cms', "content_db_" . $database->id, $name );
							Lang::saveCustom( 'cms', "content_db_" . $database->id . '_desc', $desc );
							Lang::saveCustom( 'cms', "digest_area_cms_records" . $database->id, $langs['content_db_lang_pu'] );
							Lang::saveCustom( 'cms', "digest_area_cms_categories" . $database->id, $langs['content_db_lang_pu'] );
		
							$menu	= array();
		
							/* Notification, search/followed/new content langs */
							Lang::saveCustom( 'cms', "cms_records" . $database->id . '_pl', $langs['content_db_lang_su'] );
							Lang::saveCustom( 'cms', "module__cms_records" . $database->id, $name );
					
							break;
						}
					}

					$database->save();

					/* set up some stores (and make sure they are clean from previous possibly broken imports */
					Store::i()->db_import_cat_map = array();
					Store::i()->db_import_cat_parent_map = array();
					Store::i()->db_import_field_map = array();
					Store::i()->db_import_db_id = $database->id;

					/* Start impoprting */
					$data = array( 'next' => 'field', 'done' => 0 );
					return array( $data, Member::loggedIn()->language()->addToStack('processing') );
				}

				$database = CmsDatabases::load( Store::i()->db_import_db_id );

				$xml->read();
				$next = NULL;
				$areas = array( 'field', 'category' );

				/* Only allow templates that were created after 5.0 */
				if( isset( $data['sourceVersion'] ) and $data['sourceVersion'] >= 500000 )
				{
					$areas[] = 'template';
				}
				
				if ( $data['next'] )
				{
					while ( $xml->read() )
					{
						if( ! in_array( $xml->name, $areas ) OR $xml->nodeType != XMLReader::ELEMENT )
						{
							continue;
						}
						
						$i++;
						
						if ( $data['done'] )
						{
							if ( $i - 1 < $data['done'] )
							{
								$xml->next();
								continue;
							}
						}
						
						$doneSomething = false;		
						if ( $xml->name == $data['next'] )
						{
							$data['done']++;
							
							$areaName = $xml->name;
							$node = new SimpleXMLElement( $xml->readOuterXML() );
							
							/* Import */
							switch ( $areaName )
							{
								case 'field':
									$attrs = array();
									$perms = array();
									foreach( $node->attributes() as $k => $v )
									{
										if ( mb_substr( $k, 0, 5 ) === 'perm_' or in_array( $k, array( 'app', 'owner_only', 'friend_only', 'authorized_users' ) ) )
										{
											$perms[ $k ] = (string) $v;
										}
										else
										{
											$attrs[ $k ] = (string) $v;
										}
									}
									
									/* Any kids of your own? */
									$displayJson = NULL;
									foreach( $node->children() as $k => $v )
									{
										if ( $k == 'field_name' )
										{
											$k = 'field_title';
										}

										$tryJson = json_decode( $v, TRUE );
										
										if ( $k === 'field_display_json' AND is_array( $tryJson ) )
										{
											$displayJson = $tryJson;
											continue;
										}
										
										$attrs[ $k ] = ( $tryJson ) ?: (string) $v;
									}
									
									/* Check to ensure we have the same field type, if not default to text so data is retained (buildHelper also does this */
									if ( ! class_exists( '\IPS\cms\Fields\\' . IPS::mb_ucfirst( $attrs['field_type'] ) ) and ! class_exists( '\IPS\Helpers\Form\\' . IPS::mb_ucfirst( $attrs['field_type'] ) ) )
									{
										$attrs['field_type'] = 'Text';
									}

									/* Upload field has a special "is multiple" option we need to set */
									if( IPS::mb_ucfirst( $attrs['field_type'] ) == 'Upload' )
									{
										$attrs['field_upload_is_multiple'] = $attrs['field_is_multiple'];
									}
									
									$attrs['_skip_formatting'] = TRUE;

									$originalFieldId = $attrs['field_id'];
									unset( $attrs['field_id'] );

									$fieldsClass = 'IPS\cms\Fields' . $database->id;

									$obj = new $fieldsClass;
									$values = $obj->formatFormValues( $attrs );
									
									if ( isset( $attrs['field_extra'] ) )
									{
										/* The export format is always correct */
										$values['extra'] = $attrs['field_extra'];
									}
									
									$obj->saveForm( $values );
							
									if ( $displayJson )
									{
										$obj->display_json = $displayJson;
										$obj->save();
									}
									
									if ( $originalFieldId == $database->field_title )
									{
										$database->field_title = $obj->id;
										$database->save();
									}
									else if ( $originalFieldId == $database->field_content )
									{
										$database->field_content = $obj->id;
										$database->save();
									}
									
									$map = Store::i()->db_import_field_map;

									$map[ $originalFieldId ] = $obj->id;

									Store::i()->db_import_field_map = $map;
									
									$existingPerms	= $obj->permissions();
									$newPerms		= array_merge( $perms, array( 'perm_id' => $existingPerms['perm_id'], 'perm_type_id' => $obj->id ) );

									/* Set default permissions if not defined in export */
									if( !isset( $perms['perm_view'] ) )
									{
										$newPerms['perm_view'] = '*';
									}

									if( !isset( $perms['perm_2'] ) )
									{
										$newPerms['perm_2'] = Settings::i()->admin_group;
									}

									if( !isset( $perms['perm_3'] ) )
									{
										$newPerms['perm_3'] = Settings::i()->admin_group;
									}
	
									Db::i()->update( 'core_permission_index', $newPerms, array( 'perm_id=?', $existingPerms['perm_id'] ) );

									Lang::saveCustom( 'cms', "content_field_" . $obj->id, $attrs['field_title'] );

									if ( isset($attrs['field_description']) )
									{
										Lang::saveCustom( 'cms', "content_field_" . $obj->id . '_desc', $attrs['field_description'] );
									}

									if ( isset($attrs['field_validator_error']) )
									{
										Lang::saveCustom( 'cms', "content_field_" . $obj->id . '_validation_error', $attrs['field_validator_error'] );
									}
									$next = 'category';
									$doneSomething = true;
								break;

								case 'category':
									$attrs = array();
									$perms = array();
									foreach( $node->attributes() as $k => $v )
									{
										if ( mb_substr( $k, 0, 5 ) === 'perm_' or in_array( $k, array( 'app', 'owner_only', 'friend_only', 'authorized_users' ) ) )
										{
											$perms[ $k ] = (string) $v;
										}
										else
										{
											switch( $k )
											{
												case 'category_parent_id':
													$attrs[ $k ] = (int) $v;
													break;
												case 'category_club_id': // always null because the club itself is not imported here
													$attrs[ $k ] = null;
													break;
												default:
													$attrs[ $k ] = (string) $v;
													break;
											}
										}
									}

									/* Any kids of your own? */
									foreach( $node->children() as $k => $v )
									{
										$tryJson = json_decode( $v, TRUE );
										$attrs[ $k ] =  ( $tryJson ) ?: (string) $v;
									}

									$originalCategoryId = $attrs['category_id'];
									$originalParentId   = $attrs['category_parent_id'];

									unset( $attrs['category_id'] );
									$attrs['category_parent_id'] = 0;

									/* Create a category */
									$category = new Categories;
									$category->database_id = $database->id;
									$category::$permType = 'categories_' . $database->id;
									
									$category->saveForm( $category->formatFormValues( $attrs ) );
									
									if ( $category->fields AND $category->fields !== '*' )
									{
										if ( is_array( $category->fields ) )
										{
											$field_map = Store::i()->db_import_field_map;
											$newMap    = array();
											
											foreach( $category->fields as $fid )
											{
												if ( isset( $field_map[ $fid ] ) )
												{
													$newMap[] = $field_map[ $fid ];
												}
											}
											
											if ( count( $newMap ) )
											{
												$category->fields = json_encode( $newMap );
												$category->save();
											}
										}
									}

									/* If the database does not have a default category yet, assign one */
									if( !$database->use_categories and !$database->default_category )
									{
										$database->default_category = $category->id;
										$database->save();
									}
									
									$cat_map = Store::i()->db_import_cat_map;
									$parent_map = Store::i()->db_import_cat_parent_map;

									$cat_map[ $originalCategoryId ] = $category->id;
									$parent_map[ $category->id ] = $originalParentId;

									Store::i()->db_import_cat_map = $cat_map;
									Store::i()->db_import_cat_parent_map = $parent_map;

									/* Perms */
									$existingPerms = $category->permissions();

									/* Make sure these are at least visible */
									if( $category->has_perms )
									{
										if( !isset( $perms['perm_view'] ) or empty( $perms['perm_view'] ) )
										{
											$perms['perm_view'] = '*';
										}
										if( !isset( $perms['perm_2'] ) or empty( $perms['perm_2'] ) )
										{
											$perms['perm_2'] = Settings::i()->admin_group;
										}
										if( !isset( $perms['perm_3'] ) or empty( $perms['perm_3'] ) )
										{
											$perms['perm_3'] = Settings::i()->admin_group;
										}

										Db::i()->update( 'core_permission_index', array_merge( $perms, array( 'perm_id' => $existingPerms['perm_id'], 'perm_type_id' => $category->id ) ), array( 'perm_id=?', $existingPerms['perm_id'] ) );
									}

									$next = 'template';
									$doneSomething = true;
								break;

								case 'template':
									$templates = \IPS\cms\Theme::i()->getAllTemplates( 'cms', 'database', '', Theme::RETURN_ALL );

									$attrs = array();
									foreach( $node->attributes() as $k => $v )
									{
										$attrs[ $k ] = (string) $v;
									}

									/* Any kids of your own? */
									foreach( $node->children() as $k => $v )
									{
										$tryJson = json_decode( $v, TRUE );
										$attrs[ $k ] =  ( $tryJson ) ?: (string) $v;
									}

									
									$obj = new Templates;
									$obj->location       = $attrs['template_location'];
									$obj->group          = $attrs['template_group'];
									$obj->title          = $attrs['template_title'];
									$obj->params	     = $attrs['template_params'];
									$obj->content        = $attrs['template_content'];
									$obj->original_group = $attrs['template_original_group'];
									$obj->user_created   = 1;
									$obj->user_edited    = 1;
									$obj->desc           = '';
									
									if ( $attrs['template_group'] !== 'template_form' )
									{
										$obj->group .= '_' . $database->id;
										
										foreach( array( 'template_listing', 'template_display', 'template_categories', 'template_form', 'template_featured' ) as $name )
										{
											if ( $database->$name == $attrs['template_group'] )
											{
												$database->$name = $obj->group;
												$database->save();
											}
										}
									}
									else
									{
										$obj->title .= '_' . $database->_id;
										
										$database->template_form = $obj->title;
										$database->save();
									}
									
									$obj->save();

									$obj->key = 'database_' . Friendly::seoTitle( $obj->group ) . '_' . Friendly::seoTitle( $obj->title ) . '_' . $obj->id;
									$obj->save();
									$doneSomething = true;
									$next = NULL;
								break;
							}
							
							if ( $i % 10 === 0 )
							{
								return array( $data, Member::loggedIn()->language()->addToStack('cms_db_import_progress', FALSE, array( 'sprintf' => array( ucfirst( $data['next'] ) ) ) ) );
							}
						}
						else
						{
							/* Did we do anything? if not, skip to the next section */
							if ( ! $doneSomething )
							{
								switch ( $data['next'] )
								{
									case 'field':
										$data['next'] = 'category';
									break;
									case 'category':
										$data['next'] = 'template';
									break;
									case 'template':
										$data['next'] = null;
									break;
								}
								
								if ( $data['next'] )
								{
									return array( $data, Member::loggedIn()->language()->addToStack('cms_db_import_progress', FALSE, array( 'sprintf' => array( ucfirst( $data['next'] ) ) ) ) );
								}
							}
						}

						$xml->next();
					}

					/* Done */
					$data['next'] = $next;
					
					return array( $data, Member::loggedIn()->language()->addToStack('cms_db_import_progress', FALSE, array( 'sprintf' => array( ucfirst( $data['next'] ?? '' ) ) ) ) );
				}
				else
				{
					return NULL;
				}
			},
			function()
			{
				/* Remap categories */
				if ( isset( Store::i()->db_import_cat_map ) and isset( Store::i()->db_import_cat_parent_map ) )
				{
					foreach( Store::i()->db_import_cat_parent_map as $new => $oldParent )
					{
						if ( $oldParent > 0 )
						{
							if ( isset( Store::i()->db_import_cat_map[ $oldParent ] ) )
							{
								$cat = Categories::load( $new );
								$cat->parent_id = Store::i()->db_import_cat_map[ $oldParent ];
								$cat->save();
							}
						}
					}
				}

				$databaseId = Store::i()->db_import_db_id;
				$database = CmsDatabases::load( $databaseId );
				
				/* SORT IT OUT! */
				if ( isset( Store::i()->db_import_field_map ) )
				{
					$map = Store::i()->db_import_field_map;
					$sortId = intval( mb_substr( $database->field_sort, 6 ) );
				
					if ( isset( $map[ $sortId ] ) )
					{
						$database->field_sort = 'field_' . $map[ $sortId ];
						$database->save();
					}
				}

				foreach( array( 'template_listing', 'template_display', 'template_categories', 'template_form', 'template_featured' ) as $name )
				{
					Templates::fixTemplateTags( $database->$name );
				}
				
				unset( Store::i()->db_import_db_id );
				unset( Store::i()->db_import_cat_map );
				unset( Store::i()->db_import_cat_parent_map );
				unset( Store::i()->db_import_field_map );
					
				@unlink( Request::i()->file );
				
				Session::i()->log( 'acplogs__cms_imported_database', array( 'content_db_' . $database->id => TRUE ) );
				
				/* Done */
				Output::i()->redirect( Url::internal( 'app=cms&module=databases&controller=databases&do=permissions&id=' . $databaseId ) );
			}
		);
	}

	/**
	 * Download
	 *
	 * @return void
	 */
	protected function download() : void
	{
		Dispatcher::i()->checkAcpPermission( 'databases_edit' );

		try
		{
			$database = CmsDatabases::load( Request::i()->id );
		}
		catch( OutofRangeException $ex )
		{
			Output::i()->error( 'cms_database_not_exist', '3T259/4', 403, '' );
		}

		if( empty( Request::i()->go ) )
		{
			Output::i()->output = Theme::i()->getTemplate( 'databases' )->downloadDialog( $database );
		}
		else
		{
			/* We need to know the database schema to prevent non-standard columns from add-ons being added to the XML (which then causes the import to fail) */
			$schema = json_decode( file_get_contents( \IPS\ROOT_PATH . '/applications/cms/data/schema.json' ), TRUE );
			
			/* Init */
			$xml = new XMLWriter;
			$xml->openMemory();
			$xml->setIndent( TRUE );
			$xml->startDocument( '1.0', 'UTF-8' );

			/* Root tag */
			$xml->startElement('database');

			/* Add the current version */
			$xml->startAttribute( 'version' );
			$xml->text( Application::load( 'cms' )->long_version );
			$xml->endAttribute();
			
			foreach( array( 'content_db_lang_sl', 'content_db_lang_pl', 'content_db_lang_su', 'content_db_lang_pu', 'content_db_lang_ia' ) as $lang )
			{
				try
				{
					$xml->startAttribute( $lang );
					$xml->text( Member::loggedIn()->language()->get( $lang  . '_' . $database->id ) );
					$xml->endAttribute();
				}
				catch( UnderflowException $ex )
				{

				}
			}

			/* Initiate the <fields> tag */
			$xml->startElement('data');
			
			$arrays = array();
			foreach( array( 'use_categories', 'all_editable', 'revisions', 'field_title', 'field_content', 'field_sort', 'field_direction', 'field_perpage', 'comment_approve', 'record_approve', 'rss',
							'comment_bump', 'forum_record', 'forum_comments', 'forum_delete', 'forum_forum', 'forum_prefix', 'forum_suffix', 'search', 'fixed_field_perms', 'fixed_field_settings', 'options', 'display_settings' ) as $field )
			{
				if ( is_array( $database->$field ) )
				{
					$arrays[ $field ] = $database->$field;
				}
				else
				{
					$store = $database->$field;

					if ( $database->$field instanceof Bitwise )
					{
						$bwfield = $database->$field;
						$store = intval( $bwfield->values['options'] );
					}

					$xml->startAttribute( $field );
					$xml->text( $store );
					$xml->endAttribute();
				}
			}

			if ( count( $arrays ) )
			{
				foreach( $arrays as $field => $v )
				{
					$xml->startElement( $field );
					$xml->writeCData( json_encode( $v ) );
					$xml->endElement();
				}
			}
			
			$xml->startElement('name');
			$xml->writeCData( $database->_title );
			$xml->endElement();
			
			$xml->startElement('description');
			$xml->writeCData( $database->_description );
			$xml->endElement();


			$xml->endElement();

			/* Custom fields */
			$textFields  	= array( 'field_extra', 'field_default_value', 'field_name', 'field_description', 'field_display_json', 'field_validator_error' );
			$removeFields	= array( 'field_database_id', 'perm_id', 'perm_type', 'perm_type_id' );
			$fieldSchema	= $schema['cms_database_fields'];
			foreach ( Db::i()->select( 'f.*, w.word_custom as field_name, w2.word_custom as field_description, w3.word_custom as field_validator_error, p.*', array('cms_database_fields', 'f'), array( 'field_database_id=?', $database->id ) )
				            ->join( array( 'core_sys_lang_words', 'w' ), "w.word_key=CONCAT( 'content_field_', f.field_id ) and w.lang_id=" . Lang::defaultLanguage() )
			                ->join( array( 'core_sys_lang_words', 'w2' ), "w2.word_key=CONCAT( 'content_field_', f.field_id, '_desc' ) and w2.lang_id=" . Lang::defaultLanguage() )
				            ->join( array( 'core_sys_lang_words', 'w3' ), "w3.word_key=CONCAT( 'content_field_', f.field_id, '_validation_error' ) and w3.lang_id=" . Lang::defaultLanguage() )
				            ->join( array( 'core_permission_index', 'p' ), "p.app='cms' and p.perm_type='fields' and p.perm_type_id=f.field_id" )
				as $row )
			{
				/* Initiate the <fields> tag */
				$xml->startElement('field');

				foreach( $row as $k => $v )
				{
					if ( !in_array( $k, $removeFields ) AND !in_array( $k, $textFields ) and ( mb_substr( $k, 0, 5 ) == 'perm_' or array_key_exists( $k, $fieldSchema['columns'] ) ) )
					{
						$xml->startAttribute( $k );
						$xml->text( $v ?? '' );
						$xml->endAttribute();
					}
				}

				/* Write (potential) HTML fields */
				foreach( $textFields as $field )
				{
					if ( isset( $row[ $field ] ) )
					{
						$xml->startElement( $field );
						if ( preg_match( '/[<>&"]/', $row[ $field ] ) )
						{
							$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $row[ $field ] ) );
						}
						else
						{
							$xml->text( $row[ $field ] );
						}
						$xml->endElement();
					}
				}

				/* Close the <fields> tag */
				$xml->endElement();
			}

			/* Categories */
			$textFields   = array( 'category_name', 'category_description', 'category_meta_keywords', 'category_meta_description', 'category_page_title' );
			$removeFields = array( 'category_database_id', 'category_last_record_id', 'category_last_record_date', 'category_last_record_member', 'category_last_record_name', 'category_last_record_seo_name', 'category_records', 'category_record_comments',
								   'category_record_comments_queued', 'category_rss_cache', 'category_rss_cached', 'category_rss_exclude', 'category_forum_override', 'category_forum_record', 'category_forum_comments', 'category_forum_delete', 'category_forum_suffix', 'category_forum_prefix',
							       'category_forum_forum', 'category_full_path', 'category_last_title', 'category_last_seo_title', 'perm_id', 'perm_type_id', 'perm_type' );
			$categorySchema	= $schema['cms_database_categories'];
			foreach ( Db::i()->select( 'c.*, w.word_custom as category_name, w2.word_custom as category_description, p.*', array('cms_database_categories', 'c'), array( 'category_database_id=?', $database->id ) )
				          ->join( array( 'core_sys_lang_words', 'w' ), "w.word_key=CONCAT( 'content_cat_name_', c.category_id ) and w.lang_id=" . Lang::defaultLanguage() )
				          ->join( array( 'core_sys_lang_words', 'w2' ), "w2.word_key=CONCAT( 'content_cat_name_', c.category_id, '_desc' ) and w2.lang_id=" . Lang::defaultLanguage() )
				          ->join( array( 'core_permission_index', 'p' ), "p.app='cms' and p.perm_type='categories' and p.perm_type_id=c.category_id" )
			          as $row )
			{
				/* Initiate the <category> tag */
				$xml->startElement('category');

				foreach( $row as $k => $v )
				{

					if ( !in_array( $k, $removeFields ) AND !in_array( $k, $textFields ) and ( mb_substr( $k, 0, 5 ) == 'perm_' or array_key_exists( $k, $categorySchema['columns'] ) ) )
					{
						$xml->startAttribute( $k );
						$xml->text( $v ?? '' );
						$xml->endAttribute();
					}
				}

				/* Write (potential) HTML fields */
				foreach( $textFields as $field )
				{
					if ( isset( $row[ $field ] ) )
					{
						$xml->startElement( $field );
						if ( preg_match( '/[<>&]/', $row[ $field ] ) )
						{
							$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $row[ $field ] ) );
						}
						else
						{
							$xml->text( $row[ $field ] );
						}
						$xml->endElement();
					}
				}

				/* Close the <category> tag */
				$xml->endElement();
			}

			/* Templates @todo see if blocks are used in template and export too */
			$templates = \IPS\cms\Theme::i()->getAllTemplates( 'cms', 'database', '', Theme::RETURN_ALL );
			$toSave    = array();

			foreach( array('template_listing', 'template_display', 'template_categories', 'template_featured') as $area )
			{
				if ( isset( $templates['cms']['database'][ $database->$area ] ) and is_array( $templates['cms']['database'][ $database->$area ] ) )
				{
					/* Only fetch edited/added templates, no point in fetching default theme templates */
					foreach( $templates['cms']['database'][ $database->$area ] as $key => $item )
					{
						if ( $item['template_user_created'] or $item['template_user_edited'] )
						{
							$toSave[ $database->$area ][ $key ] = $item;
						}
					}
				}
			}

			/* Form template */
			if ( isset( $templates['cms']['database']['form'][ $database->template_form ] ) )
			{
				$item = $templates['cms']['database']['form'][ $database->template_form ];

				if ( $item['template_user_created'] or $item['template_user_edited'] )
				{
					$toSave['form'][ $database->template_form ] = $item;
				}
			}

			if ( count( $toSave ) )
			{
				foreach( $toSave as $group => $items )
				{
					foreach( $items as $key => $item )
					{
						/* Initiate the <template> tag */
						$xml->startElement('template');

						foreach( array( 'template_title', 'template_group', 'template_location', 'template_original_group' ) as $field )
						{
							$xml->startAttribute( $field );
							$xml->text( $item[ $field ] );
							$xml->endAttribute();
						}

						/* Write (potential) HTML fields */
						foreach( array( 'template_params', 'template_content' ) as $field )
						{
							if ( isset( $item[ $field ] ) )
							{
								$xml->startElement( $field );
								if ( preg_match( '/[<>&]/', $item[ $field ] ) )
								{
									$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $item[ $field ] ) );
								}
								else
								{
									$xml->text( $item[ $field ] );
								}
								$xml->endElement();
							}
						}

						/* Close the <template> tag */
						$xml->endElement();
					}
				}
			}

			/* Finish */
			$xml->endDocument();

			$name = addslashes( str_replace( array( ' ', '.', ',' ), '_', Member::loggedIn()->language()->get( 'content_db_' . $database->_id ) ) . '.xml' );
			
			Session::i()->log( 'acplogs__cms_downloaded_database', array( 'content_db_' . $database->id => TRUE ) );

			Output::i()->sendOutput( $xml->outputMemory(), 200, 'application/xml', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', $name ) ) );
		}
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'databases_delete' );

		/* Load the database */
		try
		{
			$database = CmsDatabases::load( Request::i()->id );
		}
		catch( OutofRangeException $ex )
		{
			Output::i()->error( 'cms_database_not_exist', '3T259/2', 403, '' );
		}

		/* Make sure the user confirmed the deletion */
		if( CmsDatabases::isUsedAsReciprocalField( Request::i()->id  ) )
		{
			/* SHow a custom deletion confirmation for the DB if it used as reciprocal field in any other DB */
			Request::i()->confirmedDelete( 'delete_confirm', 'delete_db_confirm_detail_msg' );
		}
		else
		{	/* Make sure the user confirmed the deletion */
			Request::i()->confirmedDelete();
		}

		$database->delete();

		/* Log the deletion */
		Session::i()->log( 'acplogs__cms_deleted_database', array( 'content_db_' . $database->id => TRUE ) );

		/* Send the user back to the list */
		Output::i()->redirect( Url::internal( 'app=cms&module=databases&controller=databases' ), 'deleted' );
	}

	/**
	 * Permissions
	 *
	 * @return	void
	 */
	protected function permissions() : void
	{
		parent::permissions();

		$database = CmsDatabases::load( Request::i()->id );

		try
		{
			if( $database->page_id AND Page::load( $database->page_id )->permissions()['perm_view'] != '*' )
			{
				/* We want the message to show at the top, so get our output as we will start over */
				$output = Output::i()->output;

				Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack('database_page_permissions_restrict', FALSE, array( 'sprintf' => array( $database->page_id ) ) ), 'warning' );
				Output::i()->output .= $output;
			}
		}
		catch( OutOfRangeException $e ){}
	}

	/**
	 * Resynchronise topic content dialog
	 *
	 * @return void
	 */
	public function rebuildTopicContent() : void
	{
		Session::i()->csrfCheck();
		
		if ( isset( Request::i()->process ) )
		{
			Task::queue( 'cms', 'ResyncTopicContent', array( 'databaseId' => Request::i()->id ), 3, array( 'databaseId' ) );
			Session::i()->log( 'acplogs__cms_database_topic_resync', array( CmsDatabases::load( Request::i()->id )->_title => TRUE ) );
			Output::i()->redirect( Url::internal( 'app=cms&module=databases&controller=databases&do=form&id=' . Request::i()->id ), 'database_rebuild_added' );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'databases', 'cms', 'admin' )->rebuildTopics( Request::i()->id );
		}
	}
	
	/**
	 * Resynchronise article comment counts
	 *
	 * @return void
	 */
	public function rebuildCommentCounts() : void
	{
		if ( isset( Request::i()->process ) )
		{
			Session::i()->csrfCheck();
			Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\cms\Records' . Request::i()->id ), 3, array( 'class' ) );
			Session::i()->log( 'acplogs__cms_database_comments_recount', array( CmsDatabases::load( Request::i()->id )->_title => TRUE ) );
			Output::i()->redirect( Url::internal( 'app=cms&module=databases&controller=databases&do=form&id=' . Request::i()->id ), 'database_rebuild_added' );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'databases', 'cms', 'admin' )->rebuildCommentCounts( Request::i()->id );
		}
	}
	
	/**
	 * Add/Edit
	 *
	 * @csrfChecked	Uses $this->_getDatabaseForm() which uses Form Helper 7 Oct 2019
	 * @return	void
	 */
	public function form() : void
	{
		$current  = NULL;
		$category = NULL;
		if ( Request::i()->id )
		{
			$current = CmsDatabases::load( Request::i()->id );

			if ( ! $current->use_categories )
			{
				$class    = '\IPS\cms\Categories' . $current->id;
				/* @var	$class    Categories */
				$category = $class::load( $current->_default_category );
			}
		}
	
		/* Get the database form - abstracted so plugins can adjust easier */
		$form = $this->_getDatabaseForm( $current, $category );

		if ( $values = $form->values() )
		{
			$new = FALSE;

			if ( empty( $current ) )
			{
				$new = TRUE;
				$current = new CmsDatabases;
				$current->key = mt_rand(); # This is modified below to use a proper key
				$current->fixed_field_perms	= NULL;
				$current->save();
				
				/* Create a new database table */
				try
				{
					CmsDatabases::createDatabase( $current );
					$current->preLoadWords();
				}
				catch ( Exception $ex )
				{
					$current->delete();
					
					Output::i()->error( Member::loggedIn()->language()->addToStack('content_acp_err_db_creation_fail', FALSE, array( 'sprintf' => $ex->getMessage() ) ), '4T259/1', 403, '' );
				}
			}

			if ( ! $values['database_key'] )
			{
				if ( is_array( $values['database_name'] ) )
				{
					$keyToUse = mt_rand();
					foreach( $values['database_name'] as $langId => $word )
					{
						if ( ! empty( $word ) )
						{
							$keyToUse = $word;
							break;
						}
					}
					
					$current->key = Friendly::seoTitle( $keyToUse );
				}
				else
				{
					$current->key = Friendly::seoTitle( $values['database_name'] );
				}

				/* Now test it */
				try
				{
					$database = CmsDatabases::load( $current->key, 'database_key');

					/* It's taken... */
					if ( $current->id != $database->id )
					{
						$current->key .= '_' . mt_rand();
					}
				}
				catch( OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
				}
			}
			else
			{
				$current->key = $values['database_key'];
			}

			/* Bit options */
			foreach ( array( 'comments', 'comments_mod', 'reviews', 'reviews_mod', 'indefinite_own_edit', 'assignments' ) as $k )
			{
				if ( isset( $values[ "cms_bitoptions_{$k}" ] ) )
				{
					$current->options[ $k ] = $values["cms_bitoptions_{$k}"];
					unset( $values["cms_bitoptions_{$k}"] );
				}
			}

			Lang::saveCustom( 'cms', "content_db_" . $current->id, $values['database_name'] );
			Lang::saveCustom( 'cms', "content_db_" . $current->id . '_desc', $values['database_description'] );
			Lang::saveCustom( 'cms', "content_db_lang_sl_" . $current->id, $values['database_lang_sl'] );
			Lang::saveCustom( 'cms', "content_db_lang_pl_" . $current->id, $values['database_lang_pl'] );
			Lang::saveCustom( 'cms', "content_db_lang_su_" . $current->id, $values['database_lang_su'] );
			Lang::saveCustom( 'cms', "content_db_lang_pu_" . $current->id, $values['database_lang_pu'] );
			Lang::saveCustom( 'cms', "content_db_lang_ia_" . $current->id, $values['database_lang_ia'] );
			Lang::saveCustom( 'cms', "content_db_lang_sl_" . $current->id . '_pl', $values['database_lang_pu'] );
			Lang::saveCustom( 'cms', "digest_area_cms_records" . $current->id, $values['database_lang_pu'] );
			Lang::saveCustom( 'cms', "digest_area_cms_categories" . $current->id, $values['database_lang_pu'] );

			/* Notification, search/followed/new content langs */
			Lang::saveCustom( 'cms', "cms_records" . $current->id . '_pl', $values['database_lang_pu'] );
			Lang::saveCustom( 'cms', "module__cms_records" . $current->id, $values['database_name'] );

			$current->use_categories      = $values['database_use_categories'];
			$current->allow_club_categories = $values['database_allow_club_categories'] ?? $current->allow_club_categories;

			$current->display_settings = [
				'index' => [ 'type' => $values['database_index_type'], 'layout' => $values['database_index_layout'] ?? 'table', 'template' => $values['database_template_index'] ?? null ],
				'categories' => [ 'layout' => $values['database_categories_layout'], 'template' => ( $values['database_categories_layout'] == 'custom' ? $values['database_template_categories'] : null ) ],
				'listing' => [ 'layout' => $values['database_listing_layout'], 'template' => ( $values['database_listing_layout'] == 'custom' ? $values['database_template_listing'] : null ) ],
				'display' => [ 'layout' => 'custom', 'template' => $values['database_template_display'] ],
				'form' => [ 'layout' => 'custom', 'template' => $values['database_template_form' ] ]
			];

			$current->use_as_page_title   = $values['database_use_as_page_title'];

			$current->all_editable   = (int) $values['database_all_editable'];
			$current->revisions      = (int) $values['database_revisions'];
			$current->search         = (int) $values['database_search'];
			$current->comment_bump   = $values['database_comment_bump'];
			if ( $values['database_rss_enable'] )
			{
				$current->rss		= $values['database_rss'];
			}
			else
			{
				$current->rss		= 0;
			}
			$current->record_approve = $values['database_record_approve'];

			$current->field_sort      	= $values['database_field_sort'];
			$current->field_direction 	= $values['database_field_direction'];
			$current->field_perpage   	= $values['database_field_perpage'];
			$current->comments_perpage	= $values['database_comments_perpage'];
			
			if ( Application::appIsEnabled( 'forums' ) )
			{
				/* Are we changing where comments go? */
				if ( !$new AND ( (int) $current->forum_record != (int) $values['database_forum_record'] OR (int) $current->forum_comments != (int) $values['database_forum_comments'] ) )
				{
					Task::queue( 'cms', 'MoveComments', array(
						'databaseId'	=> $current->id,
						'to'				=> ( $values['database_forum_comments'] AND $values['database_forum_record'] ) ? 'forums' : 'pages',
						'deleteTopics'	=> ( !$values['database_forum_record'] )
					), 1, array( 'databaseId', 'to' ) );
				}
				
				$current->forum_record   = (int) $values['database_forum_record']; 
				$current->forum_comments = (int) $values['database_forum_comments'];
				$current->forum_forum    = ( ! $values['database_forum_forum']  ) ? 0 : ( is_int( $values['database_forum_forum'] ) ? $values['database_forum_forum'] : $values['database_forum_forum']->id );
				$current->forum_prefix   = $values['database_forum_prefix'];
				$current->forum_suffix   = $values['database_forum_suffix'];
				$current->forum_delete   = (int) $values['database_forum_delete'];
			}
			else
			{
				$current->forum_record		= 0;
				$current->forum_comments	= 0;
				$current->forum_delete		= 0;
			}
			
			/* SEO */
			$current->canonical_flag = (int) $values['database_canonical_flag'];
			
			$fieldSettingJson = array();

			foreach( $values as $k => $v )
			{
				if ( mb_stristr( $k, 'fixed_field_setting__' ) )
				{
					$bits = explode( '__', $k );

					$fieldSettingJson[ $bits[1] ][ $bits[2] ] = $v;
				}
			}

			$current->fixed_field_settings = $fieldSettingJson;

			$fixedFields = $current->fixed_field_perms;
			$fixedFields['record_image']['visible'] = $values['database_record_image'];

			foreach( array( 'perm_view', 'perm_2', 'perm_3' ) as $p )
			{
				if ( ! isset( $fixedFields['record_image'][ $p ] ) )
				{
					$fixedFields['record_image'][ $p ] = '*';
				}
			}

			$current->fixed_field_perms = $fixedFields;
			$current->use_categories      = $values['database_use_categories'];
			
			$current->save();

			/* Make sure we have a default category */
			$current->default_category = $current->_default_category;

			if ( ! $current->use_categories )
			{
				$class                  = '\IPS\cms\Categories' . $current->id;
				$category               = $class::load( $current->get__default_category() );
				$category->allow_rating = $values['category_allow_rating'];
				$category->can_view_others = $values['category_can_view_others'];
				$category->save();
				$category->cloneDatabasePermissions();
			}

			$pageValues = array();
			foreach( $values as $k => $v )
			{
				if( mb_strpos( $k, 'page_' ) === 0 )
				{
					$pageValues[ $k ]	= $v;
				}
			}
			$pageValues['page_folder_id'] = $pageValues['page_folder_id'] ?: 0;
			$pageValues['page_name'] = $pageValues['page_name'] ?: $values['database_name'];
			$pageValues['page_ipb_wrapper'] = true;

			if( $currentPage = $current->page )
			{
				/* @var Page $currentPage */
				$currentPage->saveForm( $currentPage->formatFormValues( $pageValues ) );
			}
			else
			{
				$newPage = Page::createFromForm( $pageValues );
				$area = Area::create( 'col1', [
					[
						'app' => 'cms',
						'key' => 'Database',
						'unique' => mt_rand(),
						'configuration' => [ 'database' => $current->id ]
					]
				]);
				$newPage->saveArea( $area );
				$current->page_id = $newPage->id;
				$current->save();
			}
			
			if( !$new )
			{
				/* Use categories setting may have changed */
				$current->preLoadWords();
			}
			
			if ( $new )
			{
                Session::i()->log( 'acplogs__cms_added_database', array( 'content_db_' . $current->id => TRUE ) );

                Output::i()->redirect( Url::internal( 'app=cms&module=databases&controller=databases&do=permissions&id=' . $current->id ) );
			}

            Session::i()->log( 'acplogs__cms_edited_database', array( 'content_db_' . $current->id => TRUE ) );

            Output::i()->redirect( Url::internal( 'app=cms&module=databases&controller=databases' ), 'saved' );
		}

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_databases.js', 'cms', 'admin' ) );
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->block( $current ? "content_db_" . $current->id : 'add', $form, FALSE );
		Output::i()->title  = ( $current ) ? Member::loggedIn()->language()->addToStack( 'cms_editing_database', FALSE, array( 'sprintf' => array( $current->_title ) ) ) : Member::loggedIn()->language()->addToStack('cms_adding_database');
	}

	/**
	 * Generate the database form
	 *
	 * @param CmsDatabases|null $current The current database
	 * @param Categories|null $category The default catgory
	 * @return    Form
	 */
	protected function _getDatabaseForm( ?CmsDatabases $current, ?Categories $category ): Form
	{
		$form = new Form( 'form', 'save', NULL, array( 'data-controller' => 'cms.admin.databases.form' ) );
		
		$form->addTab( 'content_database_form_details' );
		
		$form->add( new Translatable( 'database_name', NULL, TRUE, array( 'app' => 'cms', 'key' => ( ! empty( $current ) ? "content_db_" . $current->id : NULL ) ) ) );
		
		$form->add( new Translatable( 'database_description', NULL, FALSE, array(
				'app'		  => 'cms',
				'key'		  => ( $current ? "content_db_" .  $current->id . "_desc" : NULL ),
				'textArea'	  => true
		) ) );

		$form->add( new Text( 'database_key', ! empty( $current ) ? $current->key : FALSE, FALSE, array(), function( $val )
		{
			try
			{
				if ( ! $val )
				{
					return true;
				}

				try
				{
					$database = CmsDatabases::load( $val, 'database_key');
				}
				catch( OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
					return true;
				}

				/* It's taken... */
				if ( Request::i()->id == $database->id )
				{
					/* But it's this one so that's ok */
					return true;
				}

				/* and if we're here, it's not... */
				throw new InvalidArgumentException('cms_database_key_not_unique');
			}
			catch ( OutOfRangeException $e )
			{
				/* Slug is OK as load failed */
				return true;
			}
		} ) );
		
		$disabled = FALSE;
		if ( $current and ( ( $current->use_categories and $current->numberOfCategories() > 1 ) or ( $current->allow_club_categories and $current->numberOfClubCategories() ) ) )
		{
			$disabled = TRUE;
		}
		
		$form->add( new Radio( 'database_use_categories' , $current ? $current->use_categories : 1, FALSE, array(
            'options' => array(
	            1	=> 'database_use_categories_yes',
	            0	=> 'database_use_categories_no'
            ),
            'toggles' => array(
	            1  => array( 'database_categories_layout' ),
	            0  => array( 'category_allow_rating', 'category_can_view_others' )
            ),
            'disabled' => $disabled
        ) ) );
        
        if ( $disabled === TRUE )
        {
	        $form->hiddenValues['database_use_categories_impossible'] = TRUE;
	        Member::loggedIn()->language()->words['database_use_categories_warning'] =  Member::loggedIn()->language()->addToStack( 'database_use_categories_impossible', FALSE, array( 'sprintf' => array( $current->numberOfCategories() ) ) );
        }

		if( Settings::i()->clubs )
		{
			$form->add( new Radio( 'database_allow_club_categories', $current?->allow_club_categories ? 1 : 0, false, array(
				'options' => array (
					0 => 'database_allow_club_categories_no',
					1 => 'database_allow_club_categories_yes',
				),
				'disabled' => ( $current?->allow_club_categories and $current->numberOfClubCategories() ),
			), null, null, null, 'database_allow_club_categories' ) );
		}
        
        Member::loggedIn()->language()->words['category_can_view_others_desc'] = Member::loggedIn()->language()->addToStack('category_can_view_others_database_desc');
		$form->add( new YesNo( 'category_allow_rating', $category !== NULL and $category->allow_rating, FALSE, array(), NULL, NULL, NULL, 'category_allow_rating' ) );
		$form->add( new YesNo( 'category_can_view_others', ( $category !== NULL ) ? $category->can_view_others : TRUE, FALSE, array(), NULL, NULL, NULL, 'category_can_view_others' ) );

		$fields = array(
			'primary_id_field'      => 'database_field__id',
			'member_id'		        => 'database_field__member',
			'record_publish_date'   => 'database_field__saved',
			'record_updated'        => ( $current and $current->_comment_bump === CmsDatabases::BUMP_ON_EDIT ) ? 'database_field__edited' : 'database_field__updated',
			'record_last_comment'   => "database_field__last_comment",
			'record_rating' 	    => 'database_field__rating'
		);

		if ( $current )
		{
			$FieldsClass = '\IPS\cms\Fields' . $current->id;
			/* @var \IPS\cms\Fields $FieldsClass */
			foreach( $FieldsClass::data() as $id => $field )
			{
				if ( in_array( $field->type, array( 'checkbox', 'multiselect', 'attachments' ) ) )
				{
					continue;
				}

				$fields[ 'field_' . $field->id ] = $field->_title;
			}
		}

		/* Page Properties */

		$form->addHeader( 'content_database_form_options_page' );
		$form->add( new Radio( 'database_use_as_page_title' , $current ? $current->use_as_page_title : 1, FALSE, array(
			'options' => array(
				1	=> 'database_use_as_page_title_yes',
				0	=> 'database_use_as_page_title_no'
			),
			'toggles' => array(
				0	=> array( 'page_name' )
			)
		) ) );

		foreach( Page::formElements( $current?->page ) as $name => $field )
		{
			if( !in_array( $name, [ 'tab_details', 'tab_content', 'page_content', 'page_ipb_wrapper', 'page_wrapper_template', 'page_theme', 'page_template' ] ) )
			{
				if ( is_array( $field ) )
				{
					$form->addHeader( $field[0] );
				}
				else
				{
					$form->add( $field );
				}
			}
		}

		$form->addTab( 'content_database_form_templates' );
		$form->addHeader( 'content_database_form_options_layouts' );

		$templatesCat      = array();
		$templatesList     = array();
		$templatesDisplay  = array();
		$templatesForm     = array();
		$templatesFeatured = array();

		foreach( Templates::getTemplates( Templates::RETURN_DATABASE + Templates::RETURN_DATABASE_AND_IN_DEV ) as $template )
		{
			$title = Templates::readableGroupName( $template->group );

			switch( $template->original_group )
			{
				case 'category_index':
					if( $template->group != 'category_index' )
					{
						$templatesCat[ $template->group ] = $title;
					}
				break;
				case 'listing':
					if( $template->group != 'listing' )
					{
						$templatesList[ $template->group ] = $title;
					}
				break;
				case 'display':
					$templatesDisplay[ $template->group ] = $title;
				break;
				case 'form':
					$templatesForm[ $template->group ] = $title;
				break;
				case 'category_2_column_first_featured':
				case 'category_2_column_image_feature':
				case 'category_3_column_image_feature':
				case 'category_3_column_first_featured':
				case 'category_articles':
					$templatesFeatured[ $template->group ] = $title;
				break;
			}
		}

		$layouts = [];
		foreach( Area::$widgetOnlyLayouts as $feedLayout )
		{
			if( !str_ends_with( $feedLayout, '-carousel' ) )
			{
				$layouts[ $feedLayout ] = 'core_pagebuilder_wrap__' . $feedLayout;
			}
		}

		$custom = ['custom' => 'database_layout_custom' ];

		$form->add( new Radio( 'database_index_type', !empty( $current ) ? $current->display_settings['index']['type'] : 'all', true, [
			'options' => [
				'featured' => 'database_index_type_featured',
				'categories' => 'database_index_type_categories',
				'all' => 'database_index_type_all'
			],
			'toggles' => [
				'featured' => [ 'database_index_layout' ],
				'all' => [ 'database_index_layout' ]
			]
		] ) );

		$form->add( new Select( 'database_index_layout', !empty( $current ) ? $current->display_settings['index']['layout'] : null, true, [
			'options' => ( count( $templatesFeatured ) ? array_merge( $layouts, $custom ) : $layouts ),
			'toggles' => [ 'custom' => [ 'database_template_index'] ]
		], null, null, null, 'database_index_layout' ) );
		if( count( $templatesFeatured ) )
		{
			$form->add( new Select( 'database_template_index', ( !empty( $current ) and $current->display_settings['index']['layout'] == 'custom' ) ? $current->display_settings['index']['template'] : 'grid', null, [ 'options' => $templatesFeatured ], NULL, NULL, null, 'database_template_index' ) );
		}

		$form->add( new Select( 'database_categories_layout', !empty( $current ) ? $current->display_settings['categories']['layout'] : 'table', true, [
			'options' => ( count( $templatesCat ) ? array_merge( $layouts, $custom ) : $layouts ),
			'toggles' => [ 'custom' => [ 'database_template_categories' ] ],
			'disabled' => ( count( $templatesCat ) ? null : [ 'custom' ] )
		], null, null, null, 'database_categories_layout' ) );
		if( count( $templatesCat ) )
		{
			$form->add( new Select( 'database_template_categories', ( ! empty( $current ) and $current->display_settings['categories']['layout'] == 'custom' ) ? $current->display_settings['categories']['template'] : NULL, null, array( 'options' => $templatesCat ), NULL, NULL, Theme::i()->getTemplate( 'databases', 'cms' )->templateGoButton('database_template_categories'), 'database_template_categories' ) );
		}

		$form->add( new Select( 'database_listing_layout', !empty( $current ) ? $current->display_settings['listing']['layout'] : 'table', true, [
			'options' => ( count( $templatesList ) ? array_merge( $layouts, $custom ) : $layouts ),
			'toggles' => [ 'custom' => [ 'database_template_listing' ] ]
		] ) );
		if( count( $templatesList ) )
		{
			$form->add( new Select( 'database_template_listing'   , ( !empty( $current ) and $current->display_settings['listing']['layout'] == 'custom' ) ? $current->display_settings['listing']['template'] : NULL, FALSE, array( 'options' => $templatesList ), NULL, NULL, Theme::i()->getTemplate( 'databases', 'cms' )->templateGoButton('database_template_listing'), 'database_template_listing' ) );
		}

		$form->add( new Select( 'database_template_display'   , ! empty( $current ) ? $current->template_display    : NULL, FALSE, array( 'options' => $templatesDisplay ), NULL, NULL, Theme::i()->getTemplate( 'databases', 'cms' )->templateGoButton('database_template_display'), 'database_template_display' ) );
		$form->add( new Select( 'database_template_form'      , ! empty( $current ) ? $current->template_form       : NULL, FALSE, array( 'options' => $templatesForm ), NULL, NULL, Theme::i()->getTemplate( 'databases', 'cms' )->templateGoButton('database_template_form'), 'database_template_form' ) );

		$form->addHeader( 'content_database_form_options_fields' );

		$form->add( new Select( 'database_field_sort'     , ! empty( $current ) ? $current->field_sort      : NULL, FALSE, array( 'options' => $fields ) ) );
		$form->add( new Select( 'database_field_direction', ! empty( $current ) ? $current->field_direction : NULL, FALSE, array(
			'options' => array(
				'asc'  => 'database_sort_asc',
				'desc' => 'database_sort_desc'
			)
		) ) );

		$form->add( new Number( 'database_field_perpage', $current ? $current->field_perpage : 25, FALSE, array( 'min' => 1 ), NULL, NULL, NULL, 'database_field_perpage' ) );
		$form->add( new Number( 'database_comments_perpage', $current ? $current->comments_perpage : 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'database_comments_perpage_inherit' ) ) );

		$form->addTab( 'content_database_form_lang' );
		
		$sl_Default = null;
		$pl_Default = null;
		$su_Default = null;
		$pu_Default = null;
		$ia_Default = null;

		if ( ! $current )
		{
			foreach ( Lang::languages() as $lang )
			{
				$sl_Default[ $lang->id ] = $lang->get('content_database_noun_sl');
				$pl_Default[ $lang->id ] = $lang->get('content_database_noun_pl');
				$su_Default[ $lang->id ] = $lang->get('content_database_noun_su');
				$pu_Default[ $lang->id ] = $lang->get('content_database_noun_pu');
				$ia_Default[ $lang->id ] = $lang->get('content_database_noun_ia');
			}
		}

		$form->add( new Translatable( 'database_lang_sl', $sl_Default, FALSE, array( 'app' => 'cms', 'key' => ( ! empty( $current ) ? "content_db_lang_sl_" . $current->id : NULL ) ) ) );
		$form->add( new Translatable( 'database_lang_pl', $pl_Default, FALSE, array( 'app' => 'cms', 'key' => ( ! empty( $current ) ? "content_db_lang_pl_" . $current->id : NULL ) ) ) );
		$form->add( new Translatable( 'database_lang_su', $su_Default, FALSE, array( 'app' => 'cms', 'key' => ( ! empty( $current ) ? "content_db_lang_su_" . $current->id : NULL ) ) ) );
		$form->add( new Translatable( 'database_lang_pu', $pu_Default, FALSE, array( 'app' => 'cms', 'key' => ( ! empty( $current ) ? "content_db_lang_pu_" . $current->id : NULL ) ) ) );
		$form->add( new Translatable( 'database_lang_ia', $ia_Default, FALSE, array( 'app' => 'cms', 'key' => ( ! empty( $current ) ? "content_db_lang_ia_" . $current->id : NULL ) ) ) );

		$form->addTab( 'content_database_form_options' );
		
		$form->add( new YesNo( 'database_all_editable' , $current ? $current->all_editable : FALSE, FALSE, array( 'togglesOff' => array( 'cms_bitoptions_indefinite_own_edit' ) ) ) );
		$form->add( new YesNo( 'cms_bitoptions_indefinite_own_edit'   , $current ? $current->options['indefinite_own_edit'] : FALSE, FALSE, array(), NULL, NULL, NULL, 'cms_bitoptions_indefinite_own_edit' ) );
		$form->add( new YesNo( 'database_revisions'    , $current ? $current->revisions : TRUE, FALSE ) );
		$form->add( new YesNo( 'database_search'       , $current ? $current->search : TRUE, FALSE ) );
		$form->add( new Radio( 'database_comment_bump' , $current ? $current->comment_bump : TRUE, FALSE, array(
			'options' => array(
				2	=> 'database_comment_bump_edit_comment',
				0	=> 'database_comment_bump_edit',
				1	=> 'database_comment_bump_comment'
			)
		) ) );

		$form->add( new YesNo( 'database_record_approve', $current ? $current->record_approve : FALSE, FALSE, array(), NULL, NULL, NULL, 'database_record_approve' ) );
		$form->add( new YesNo( 'database_rss_enable'   , $current ? $current->rss : FALSE, FALSE, array( 'togglesOn' => array('database_rss') ), NULL, NULL, NULL, 'database_rss_enable' ) );
		$form->add( new Number( 'database_rss'  		 , $current ? $current->rss : 0, FALSE, array(), NULL, NULL, NULL, 'database_rss' ) );

		if( Bridge::i()->featureIsEnabled( 'assignments' ) )
		{
			$form->add( new YesNo( 'cms_bitoptions_assignments', $current ? $current->options['assignments'] : false, false, array(), null, null, null, 'cms_bitoptions_assignments' ) );
		}

		$form->addHeader( 'cms_comments_and_reviews' );
		$form->add( new YesNo( 'cms_bitoptions_comments', $current ? $current->options['comments'] : TRUE, FALSE, array( 'togglesOn' => array( 'cms_bitoptions_comment_mod' ) ), NULL, NULL, NULL, 'cms_bitoptions_comments' ) );
		$form->add( new YesNo( 'cms_bitoptions_comments_mod', $current ? $current->options['comments_mod'] : FALSE, FALSE, array(), NULL, NULL, NULL, 'cms_bitoptions_comment_mod' ) );
		$form->add( new YesNo( 'cms_bitoptions_reviews', $current ? $current->options['reviews'] : TRUE, FALSE, array( 'togglesOn' => array( 'cms_bitoptions_reviews_mod' ) ) ) );
		$form->add( new YesNo( 'cms_bitoptions_reviews_mod', $current ? $current->options['reviews_mod'] : FALSE, FALSE, array(), NULL, NULL, NULL, 'cms_bitoptions_reviews_mod' ) );

		$form->addHeader( 'content_database_form_options_field_record_image_settings' );

		$widthHeight = $thumbWidthHeight = NULL;
		if ( $current )
		{
			$fixedFields    = $current->fixed_field_perms;
			$recordImagesOn = ( isset( $fixedFields['record_image']['visible'] ) and $fixedFields['record_image']['visible'] !== FALSE );

			$ffsettings = $current->fixed_field_settings;

			if ( isset( $ffsettings['record_image']['image_dims'] ) and is_array( $ffsettings['record_image']['image_dims'] ) )
			{
				$widthHeight = $ffsettings['record_image']['image_dims'];
			}

			if ( isset( $ffsettings['record_image']['thumb_dims'] ) and is_array( $ffsettings['record_image']['thumb_dims'] ) )
			{
				$thumbWidthHeight = $ffsettings['record_image']['thumb_dims'];
			}
		}
		else
		{
			$recordImagesOn = TRUE;
		}

		$form->add( new YesNo( 'database_record_image' , $recordImagesOn, FALSE, array( 'togglesOn' => array( 'fixed_field_setting__record_image__image_dims', 'fixed_field_setting__record_image__thumb_dims' ) ), NULL, NULL, NULL, 'database_record_image' ) );
		$form->add( new WidthHeight( 'fixed_field_setting__record_image__image_dims', ( $current AND $widthHeight !== NULL ) ? $widthHeight : array( 0, 0 ), FALSE, array( 'resizableDiv' => FALSE, 'unlimited' => array( 0, 0 ) ), NULL, NULL, NULL, 'fixed_field_setting__record_image__image_dims' ) );
		$form->add( new WidthHeight( 'fixed_field_setting__record_image__thumb_dims', ( $current AND $thumbWidthHeight !== NULL ) ? $thumbWidthHeight : array( 200, 200 ), FALSE, array( 'resizableDiv' => FALSE, 'unlimited' => array( 0, 0 ) ), NULL, NULL, NULL, 'fixed_field_setting__record_image__thumb_dims' ) );

		if ( Application::appIsEnabled( 'forums' ) )
		{
			$form->addTab( 'content_database_form_options_forums' );

			$databasePage = NULL;
			try
			{
				if ( $current )
				{
					$databasePage = Page::loadByDatabaseId( $current->id );
				}
			}
			catch( OutOfRangeException $e ) { }

			if ( ! $databasePage )
			{
				$form->addMessage( 'cms_no_db_page_no_forum_link', 'ipsMessage ipsMessage--info' );
			}

			$disabled = FALSE;
			if ( $current )
			{
				$rebuildUrl = Url::internal( 'app=cms&module=databases&controller=databases&id=' . $current->id . '&do=rebuildTopicContent' );
				$rebuildUrlCounts = Url::internal( 'app=cms&module=databases&controller=databases&id=' . $current->id . '&do=rebuildCommentCounts' );
				
				Member::loggedIn()->language()->words['database_forum_record_desc'] = Member::loggedIn()->language()->addToStack( 'database_forum_record__desc' ) . ' ' .
				Member::loggedIn()->language()->addToStack( 'database_forum_record__rebuild', FALSE, array( 'sprintf' => array( $rebuildUrl ) ) ) . ' ' .
				Member::loggedIn()->language()->addToStack( 'database_forum_comments__rebuild', FALSE, array( 'sprintf' => array( $rebuildUrlCounts ) ) );

				try
				{
					Db::i()->select( '*', 'core_queue', [ "`app`=? AND `key`=? AND `data` LIKE CONCAT( '%', ?, '%' )", 'cms', 'MoveComments', 'databaseID":' . $current->id ] )->first();
					Member::loggedIn()->language()->words['database_forum_record_desc'] .= Member::loggedIn()->language()->addToStack( 'database_forum_comments_in_progress' );
					$disabled = true;
				}
				catch( UnderflowException $e ) { }
			}
			else
			{
				Member::loggedIn()->language()->words['database_forum_record_desc'] = Member::loggedIn()->language()->addToStack( 'database_forum_record__desc' );
			}

			$form->add( new YesNo( 'database_forum_record', $current ? $current->forum_record : FALSE, FALSE, array( 'togglesOn' => array(
				'database_forum_comments',
				'database_forum_forum',
				'database_forum_prefix',
				'database_forum_suffix',
				'database_forum_delete'
			),
				'disabled' => $disabled ), NULL, NULL, NULL, 'database_forum_record' ) );

			$form->add( new YesNo( 'database_forum_comments', $current ? $current->forum_comments : FALSE, FALSE, array( 'disabled' => $disabled ), NULL, NULL, NULL, 'database_forum_comments' ) );
				
			$form->add( new Node( 'database_forum_forum', $current?->forum_forum, FALSE, array(
					'class'		      => '\IPS\forums\Forum',
					'disabled'	      => false,
					'permissionCheck' => function( $node )
					{
						return $node->sub_can_post;
					}
			), function( $val )
			{
				if ( ! $val and Request::i()->database_forum_record_checkbox )
				{
					throw new InvalidArgumentException('cms_database_no_forum_selected');
				}
				return true;
			}, null, null, 'database_forum_forum' ) );
			
			$form->add( new Text( 'database_forum_prefix', $current ? $current->forum_prefix: '', FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'database_forum_prefix' ) );
			$form->add( new Text( 'database_forum_suffix', $current ? $current->forum_suffix: '', FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'database_forum_suffix' ) );
			$form->add( new YesNo( 'database_forum_delete' , $current ? $current->forum_delete : FALSE, FALSE, array(), NULL, NULL, NULL, 'database_forum_delete' ) );
		}

		/* SEO */
		$form->addTab( 'content_database_form_seo' );
		$form->addHeader( 'database_canonical_header' );
		
		$form->add( new Radio( 'database_canonical_flag', ! empty( $current ) ? $current->canonical_flag : NULL, FALSE, array(
			'options' => array(
					'0'  => 'database_canonical_flag_0',
					'1'  => 'database_canonical_flag_1'
			)
		) ) );

		return $form;
	}
}