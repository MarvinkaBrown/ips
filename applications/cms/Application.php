<?php
/**
 * @brief		Content Application Class
 * @author		<a href=''>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	Content
 * @since		13 Jan 2014
 * @version		
 */
 
namespace IPS\cms;

use Exception;
use IPS\Application as SystemApplication;
use IPS\cms\Blocks\Container;
use IPS\cms\Pages\Page;
use IPS\Content\Search\Index;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Member;
use IPS\Member\Group;
use IPS\Request;
use IPS\Widget;
use IPS\Widget\Area;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use XMLWriter;
use function file_put_contents;
use function in_array;
use function intval;
use function is_numeric;
use function substr;

spl_autoload_register( function($class )
{
	if ( mb_substr( $class, 0, 15 ) === 'IPS\cms\Records' and is_numeric( mb_substr( $class, 15, 1 ) ) )
	{
		$databaseId   = intval( mb_substr( $class, 15 ) );
		$databases    = Databases::databases();
		
		if ( ! isset( $databases[ $databaseId ] ) )
		{
			return false;
		}
		
		$titleField   = $databases[ $databaseId ]->field_title;
		$contentField = $databases[ $databaseId ]->field_content;
		$contentType  = $databases[ $databaseId ]->key;
		$titleLang    = 'content_db_lang_su_' . $databaseId;
		$includeInSearch = $databases[ $databaseId ]->search ? "TRUE" : "FALSE";

		$data = <<<PHP
		namespace IPS\cms;
		class Records{$databaseId} extends Records
		{
			protected static array \$multitons = array();
			protected static array \$multitonMap	= array();
			public static ?int \$customDatabaseId = $databaseId;
			public static ?string \$commentClass = 'IPS\cms\Records\Comment{$databaseId}';
			public static ?string \$reviewClass = 'IPS\cms\Records\Review{$databaseId}';
			public static ?string \$containerNodeClass = 'IPS\cms\Categories{$databaseId}';
			public static ?string \$databaseTable = 'cms_custom_database_{$databaseId}';
			public static string \$title = '{$titleLang}';
			public static string \$module = 'records{$databaseId}';
			public static bool \$includeInSearch = {$includeInSearch};
			public static string \$contentType = '{$contentType}';
			public static ?string \$hideLogKey = 'ccs-records{$databaseId}';
			public static array \$databaseColumnMap = array(
				'author'				=> 'member_id',
				'container'				=> 'category_id',
				'date'					=> 'record_saved',
				'is_future_entry'       => 'record_future_date',
				'future_date'           => 'record_publish_date',
				'title'					=> 'field_{$titleField}',
				'content'				=> 'field_{$contentField}',
				'num_comments'			=> 'record_comments',
				'unapproved_comments'	=> 'record_comments_queued',
				'hidden_comments'		=> 'record_comments_hidden',
				'last_comment'			=> 'record_last_comment',
				'last_comment_by'		=> 'record_last_comment_by',
				'last_comment_name'		=> 'record_last_comment_name',
				'views'					=> 'record_views',
				'approved'				=> 'record_approved',
				'pinned'				=> 'record_pinned',
				'locked'				=> 'record_locked',
				'featured'				=> 'record_featured',
				'rating'				=> 'record_rating',
				'rating_hits'			=> 'rating_hits',
				'rating_average'	    => 'record_rating',
				'rating_total'			=> 'rating_value',
				'num_reviews'	        => 'record_reviews',
				'last_review'	        => 'record_last_review',
				'last_review_by'        => 'record_last_review_by',
				'last_review_name'      => 'record_last_review_name',
				'updated'				=> 'record_last_comment',
				'meta_data'				=> 'record_meta_data',
				'author_name'			=> 'record_author_name',
				'is_anon'				=> 'record_is_anon',
				'last_comment_anon'		=> 'record_last_comment_anon',
				'item_topicid'			=> 'record_topicid',
				'cover_photo'			=> 'record_image',
				'cover_photo_offset'	=> 'record_image_offset',
				'assigned'				=> 'record_assignment_id'
			);
			public static string \$pagePath = '';
		}
PHP;
		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 23 ) === 'IPS\cms\Records\Comment' and is_numeric( mb_substr( $class, 23, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 23 ) );
	
		$data = <<<PHP
		namespace IPS\cms\Records;
		class Comment{$databaseId} extends Comment
		{ 
			protected static array \$multitons = array();
			protected static array \$multitonMap	= array();
			public static ?int \$customDatabaseId = $databaseId;
			public static ?string \$itemClass = 'IPS\cms\Records{$databaseId}';
			public static string \$title     = 'content_record_comments_title_{$databaseId}';
			public static ?string \$hideLogKey = 'ccs-records{$databaseId}-comments';
		}
PHP;
		eval( $data );
	}

	if ( mb_substr( $class, 0, 22 ) === 'IPS\cms\Records\Review' and is_numeric( mb_substr( $class, 22, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 22 ) );

		$data = <<<PHP
		namespace IPS\cms\Records;
		class Review{$databaseId} extends Review
		{
			protected static array \$multitons = array();
			protected static array \$multitonMap	= array();
			public static ?int \$customDatabaseId = $databaseId;
			public static ?string \$itemClass = 'IPS\cms\Records{$databaseId}';
			public static string \$title     = 'content_record_reviews_title_{$databaseId}';
			public static ?string \$hideLogKey = 'ccs-records{$databaseId}-reviews';
		}
PHP;
		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 18 ) === 'IPS\cms\Categories' and is_numeric( mb_substr( $class, 18, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 18 ) );
		
		$databases = Databases::databases();
		
		if ( ! isset( $databases[ $databaseId ] ) )
		{
			return false;
		}
		
		$dbObject = $databases[ $databaseId ];
		
		$data = <<<PHP
		namespace IPS\cms;
		class Categories{$databaseId} extends Categories
		{
			use \IPS\Node\Statistics;
			
			protected static array \$multitons = array();
			protected static array \$multitonMap	= array();
			public static ?int \$customDatabaseId = $databaseId;
			public static ?string \$contentItemClass = 'IPS\cms\Records{$databaseId}';
			protected static ?array \$containerIds = NULL;
			public static string \$modPerm = 'cms{$databaseId}';
			public static ?string \$permType = 'categories_{$databaseId}';
			public static string \$contentArea = '{$dbObject->_title}';
			public static string \$containerType = '{$dbObject->key}_category';
			
			public static function fullyQualifiedType(): string
			{
				return '{$dbObject->_title} ' . \IPS\Member::loggedIn()->language()->addToStack( static::\$nodeTitle . '_sg' );
			}
		}
PHP;

		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 32 ) === 'IPS\cms\Records\RecordsTopicSync' )
	{
		$databaseId = intval( mb_substr( $class, 32 ) );
	
		$data = <<<PHP
		namespace IPS\cms\Records;
		class RecordsTopicSync{$databaseId} extends \IPS\cms\Records{$databaseId}
		{ 
			protected static array \$multitons = array();
	 		protected static array \$multitonMap	= array();
			public static ?int \$customDatabaseId = $databaseId;
			public static ?string \$databaseTable = 'cms_custom_database_{$databaseId}';
			public static string \$databaseColumnId = 'record_topicid';
			public static ?string \$commentClass = 'IPS\cms\Records\CommentTopicSync{$databaseId}';

			public function useForumComments(): bool
			{
				return false;
			}
		}
PHP;
		eval( $data );
	}

	if ( mb_substr( $class, 0, 32 ) === 'IPS\cms\Records\CommentTopicSync' )
	{
		$databaseId = intval( mb_substr( $class, 32 ) );

		$data = <<<PHP
		namespace IPS\cms\Records;
		class CommentTopicSync{$databaseId} extends CommentTopicSync
		{ 
			protected static array \$multitons = array();
			protected static array \$multitonMap	= array();
			public static ?int \$customDatabaseId = $databaseId;
			public static ?string \$itemClass = 'IPS\cms\Records\RecordsTopicSync{$databaseId}';
			public static string \$title     = 'content_record_comments_title_{$databaseId}';
		}
PHP;
		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 14 ) === 'IPS\cms\Fields' and is_numeric( mb_substr( $class, 14, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 14 ) );
		eval( "namespace IPS\\cms; class Fields{$databaseId} extends Fields { public static ?int \$customDatabaseId = $databaseId; protected array \$caches = array( 'database_reciprocal_links', 'cms_fieldids_{$databaseId}' ); }" );
	}

	if ( mb_substr( $class, 0, 47 ) === 'IPS\cms\extensions\core\EditorLocations\Records' and is_numeric( mb_substr( $class, 47, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 47 ) );
		eval( "namespace IPS\\cms\\extensions\\core\\EditorLocations; class Records{$databaseId} extends \\IPS\\cms\\extensions\\core\\EditorLocations\\Records { public static ?int \$customDatabaseId = $databaseId; public static bool \$buttonLocation	= TRUE; }" );
	}

	if( mb_substr( $class, 0, 26 ) == 'IPS\cms\widgets\RecordFeed' )
	{
		$databaseId = intval( mb_substr( $class, 26 ) );
		$data = <<<PHP
		namespace IPS\cms\widgets;
		class RecordFeed{$databaseId} extends RecordFeed {
			public static ?int \$customDatabaseId = $databaseId;
		}
PHP;
		eval( $data );

		//eval( "namespace IPS\\cms\\widgets; class RecordFeed{$databaseId} extends \\IPS\\cms\\widgets\\RecordFeed { public static ?int \$customDatabaseId = {$databaseId};  }" );
	}
} );

/**
 * Content Application Class
 */
class Application extends SystemApplication
{
	/**
	 * Returns the ACP Menu JSON for this application.
	 *
	 * @return array
	 */
	public function acpMenu(): array
	{
		$menu = parent::acpMenu();
		
		if ( ! Db::i()->checkForTable('cms_databases') or ! Member::loggedIn()->hasAcpRestriction( 'cms', 'databases', 'databases_use' ) )
		{
			return $menu;
		}

		/* Now add in the databases... */
		foreach(Databases::acpMenu() as $database )
		{
			$menu[ 'database_' . $database['id'] ][ 'records_' . $database['id'] ] = array(
				'tab' 		  => 'cms',
				'module_url'  => 'databases',
				'controller'  => 'records',
				'do' 		  => 'manage&database_id=' . $database['id'],
				'restriction' => 'records_manage',
				'restriction_module' => 'databases',
				'menu_checks' => array( 'do' => 'manage', 'database_id' => $database['id'] ),
				'menu_controller' => 'records_' . $database['id']
			);

			if ( $database['use_categories'] )
			{
				$menu[ 'database_' . $database['id'] ][ 'categories_' . $database['id'] ] = array(
					'tab' 		  => 'cms',
					'module_url'  => 'databases',
					'controller'  => 'categories',
					'do' 		  => 'manage&database_id=' . $database['id'],
					'restriction' => 'categories_manage',
					'restriction_module' => 'databases',
					'menu_checks' => array( 'do' => 'manage', 'database_id' => $database['id'] ),
					'menu_controller' => 'categories_' . $database['id']
				);
			}
			
			$menu[ 'database_' . $database['id'] ][ 'fields_' . $database['id'] ] = array(
				'tab' 		  => 'cms',
				'module_url'  => 'databases',
				'controller'  => 'fields',
				'do' 		  => 'manage&database_id=' . $database['id'],
				'restriction' => 'cms_fields_manage',
				'restriction_module' => 'databases',
				'menu_checks' => array( 'do' => 'manage', 'database_id' => $database['id'] ),
				'menu_controller' => 'fields_' . $database['id']
			);
			
			Member::loggedIn()->language()->words[ 'menu__cms_database_' . $database['id'] ]    = $database['title'];
			Member::loggedIn()->language()->words[ 'menu__cms_database_' . $database['id'] . '_records_' . $database['id'] ]    = $database['record_name'];
			Member::loggedIn()->language()->words[ 'menu__cms_database_' . $database['id'] . '_categories_' . $database['id'] ] = Member::loggedIn()->language()->addToStack('menu__cms_categories');
			Member::loggedIn()->language()->words[ 'menu__cms_database_' . $database['id'] . '_fields_' . $database['id'] ]     = Member::loggedIn()->language()->addToStack('menu__cms_fields');
		}

		return $menu;
	}

	/**
	 * Get Extensions
	 *
	 * @param string|SystemApplication $app		    The app key of the application which owns the extension
	 * @param string $extension	    Extension Type
	 * @param bool $construct	    Should an object be returned? (If false, just the classname will be returned)
	 * @param bool|Group|Member|null $checkAccess	Check access permission for extension against supplied member/group (or logged in member, if TRUE)
	 * @return	array
	 */
	public function extensions( SystemApplication|string $app, string $extension, bool $construct=TRUE, bool|Group|Member|null $checkAccess = FALSE ): array
	{
		$classes = parent::extensions( $app, $extension, $construct, $checkAccess );

		if ( $extension === 'EditorLocations' )
		{
			foreach( Databases::databases() as $obj )
			{
				$classname = '\\IPS\\cms\\extensions\\core\\EditorLocations\\Records' . $obj->_id;

				if ( method_exists( $classname, 'generate' ) )
				{
					$classes = array_merge( $classes, $classname::generate() );
				}
				elseif ( !$construct )
				{
					$classes[ 'Records' . $obj->_id ] = $classname;
				}
				else
				{
					try
					{
						$classes[ 'Records' . $obj->_id ] = new $classname( $checkAccess === TRUE ? Member::loggedIn() : ( $checkAccess === FALSE ? NULL : $checkAccess ) );
					}
					catch( RuntimeException $e ){}
				}
			}
		}

		return $classes;
	}

	/**
	 * Return all widgets available for the Page Editor
	 *
	 * @return array
	 */
	public function getAvailableWidgets() : array
	{
		$blocks = parent::getAvailableWidgets();

		if( isset( $blocks['RecordFeed'] ) )
		{
			unset( $blocks['RecordFeed'] );

			$widget = Db::i()->select( '*', 'core_widgets', [ 'app=? and `key`=?', 'cms', 'RecordFeed' ] )->first();

			foreach( Databases::databases() as $db )
			{
				if( !$db->page_id )
				{
					continue;
				}

				try
				{
					$block = Widget::load( $this, 'RecordFeed' . $db->id, mt_rand(), array(), $widget['restrict'] );
					$block->allowReuse = (boolean)$widget['allow_reuse'];
					$block->menuStyle = $widget['menu_style'];
					$block->allowCustomPadding = (bool)$widget['padding'];
					$block->layouts = $block->getSupportedLayouts();
					$blocks[] = $block;
				}
				catch ( Exception $e )
				{
					continue;
				}
			}
		}

		return $blocks;
	}

	/**
	 * Developer sync items
	 *
	 * @param   int     $lastSync       Last time syncd
	 * @return  boolean                 Updated (true), nothing updated(false)
	 */
	public function developerSync( int $lastSync ) : bool
	{
		$updated = false;

		if ( $lastSync < filemtime( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/databaseschema.json" ) )
		{
			foreach( Databases::databases() as $key => $db )
			{
				Databases::checkandFixDatabaseSchema( $db->_id );

				$updated = TRUE;
			}
		}

		return $updated;
	}

	/**
	 * Install 'other' items.
	 *
	 * @return void
	 */
	public function installOther() : void
	{
		/* Install default database and page */
		$database = new Databases;
		$database->key = 'articles';
		$database->save();

		/* Add in permissions */
		$groups = array();
		foreach( Db::i()->select( 'row_id', 'core_admin_permission_rows', array( 'row_id_type=?', 'group' ) ) as $row )
		{
			$groups[] = $row;
		}

		$default = implode( ',', $groups );

		Db::i()->insert( 'core_permission_index', array(
             'app'			=> 'cms',
             'perm_type'	=> 'databases',
             'perm_type_id'	=> $database->id,
             'perm_view'	=> '*', # view
             'perm_2'		=> '*', # read
             'perm_3'		=> $default, # add
             'perm_4'		=> $default, # edit
             'perm_5'		=> $default, # reply
             'perm_6'		=> $default  # rate
        ) );

		/* Needs to be added before createDatabase is called */
		Lang::saveCustom( 'cms', "content_db_" . $database->id, "Articles" );
		Lang::saveCustom( 'cms', "module__cms_records" . $database->id, "Articles" );
		Lang::saveCustom( 'cms', "content_db_" . $database->id . '_desc', "Our website articles" );
		Lang::saveCustom( 'cms', "content_db_lang_sl_" . $database->id, 'article' );
		Lang::saveCustom( 'cms', "content_db_lang_pl_" . $database->id, 'articles' );
		Lang::saveCustom( 'cms', "content_db_lang_su_" . $database->id, 'Article' );
		Lang::saveCustom( 'cms', "content_db_lang_pu_" . $database->id, 'Articles' );
		Lang::saveCustom( 'cms', "content_db_lang_ia_" . $database->id, 'an article' );
		Lang::saveCustom( 'cms', "content_db_lang_sl_" . $database->id . '_pl', 'Articles' );
		Lang::saveCustom( 'cms', "digest_area_cms_records" . $database->id, "Articles" );
		Lang::saveCustom( 'cms', "cms_records" . $database->id . '_pl', 'Article' );

		try
		{
			Databases::createDatabase( $database );
		}
		catch ( Exception $ex )
		{
			$database->delete();

			Log::log( $ex, 'pages_create_db_error' );

			throw new LogicException( $ex->getMessage() );
		}

		$database->all_editable = 0;
		$database->revisions    = 1;
		$database->search       = 1;
		$database->comment_bump = 1; # Just new comments bump record
		$database->rss	        = 10;
		$database->record_count = 1;
		$database->fixed_field_perms = array( 'record_image' => array( 'visible' => true, 'perm_view' => '*', 'perm_2' => '*', 'perm_3' => '*' ) );
		$database->options['comments'] = 1;
		$database->field_sort      = 'primary_id_field';
		$database->field_direction = 'asc';
		$database->field_perpage   = 25;
		$database->save();
		
		/* Create default record */
		$item    = 'IPS\cms\Records' . $database->id;
		/* @var $item Records */
		$container = 'IPS\cms\Categories' . $database->id;
		/* @var $container Categories */
		
		$link = (string) Url::ips('docs/pages_docs');

		$content = <<<HTML
<p>Welcome to Pages!</p>
<p>Pages extends your site with custom content management designed especially for communities.
Create brand new sections of your community using features like blocks, databases and articles,
pulling in data from other areas of your community.</p>
<p>Create custom pages in your community using our drag'n'drop, WYSIWYG editor.
Build blocks that pull in all kinds of data from throughout your community to create dynamic pages,
or use one of the ready-made widgets we include with the Invision Community.</p>
<p><br></p>
<p><a href="{$link}">View our Pages documentation</a></p>
HTML;
		
		$titleField = 'field_' . $database->field_title;
		$contentField = 'field_' . $database->field_content;
		$category = $container::load( $database->_default_category );
		
		$member = Member::loggedIn()->member_id ? Member::loggedIn() : Member::load(1);
		
		$record = $item::createItem( $member, Request::i()->ipAddress(), DateTime::ts( time() ), $category, FALSE );
		$record->$titleField = "Welcome to Pages";
		$record->$contentField = $content;
		$record->record_publish_date = time();
		$record->record_saved = time();
		$record->save();

		Index::i()->index( $record );
		
		$category->last_record_date = time();
		$category->save();
		
		/* Create the page */
		$pageValues = array(
			'page_name'         => "Articles",
			'page_title'        => "Articles",
			'page_seo_name'     => "articles.html",
			'page_folder_id'    => 0,
			'page_ipb_wrapper'  => 1,
			'page_type'         => 'builder',
			'page_template'     => 'page_builder__single_column__page_page_builder_single_column'
		);
		
		try
		{
			$page = Page::createFromForm( $pageValues );
		}
		catch( Exception $ex )
		{
			Log::log( $ex, 'pages_create_page_error' );
		}
		
		$page->setAsDefault();
		
		Db::i()->replace( 'core_permission_index', array(
             'app'			=> 'cms',
             'perm_type'	=> 'pages',
             'perm_type_id'	=> $page->id,
             'perm_view'	=> '*'
        ) );
        
		$database->page_id = $page->id;
		$database->save();
		
		unset( Store::i()->pages_page_urls );
		
		$defaultWidgets = array();
		$buttonUrl      = (string) Url::internal( 'applications/cms/interface/default/block_arrow.png', 'none' );
		$defaultContent = <<<HTML
		<p>
			<strong>Welcome to Pages!</strong>
		</p>
		<p>
			To get started, make sure you are logged in and click then choose the "Manage Blocks" option in your user menu to expand the block manager.
			<br>
			You can move, add and edit blocks without the need for complex coding!
		</p>
HTML;

		/* Default WYSIWYG widget */
		$defaultWidgets[] = array( 
			'app'           => 'cms',
			'key'           => 'Wysiwyg',
			'unique'        => mt_rand(),
			'configuration' => array( 'content' => $defaultContent )
		);
		
		/* Default database widget */
		$defaultWidgets[] = array( 
			'app'           => 'cms',
			'key'           => 'Database',
			'unique'        => mt_rand(),
			'configuration' => array( 'database' => $database->id )
		);

		$area = Area::create( 'col1', $defaultWidgets );
		$page->saveArea( $area );
						
		/* Add block container (custom)*/
		$container = new Container;
		$container->parent_id = 0;
		$container->name      = "Custom";
		$container->type      = 'block';
		$container->key       = 'block_custom';
		$container->save();

		/* Add block container (plugins) */
		$container = new Container;
		$container->parent_id = 0;
		$container->name      = "Plugins";
		$container->type      = 'block';
		$container->key       = 'block_plugins';
		$container->save();
		
		Templates::importXml( \IPS\ROOT_PATH . "/applications/cms/data/cms_theme.xml", NULL, NULL, FALSE );
	}

	/**
	 * Install the application's templates
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param bool $update	If set to true, do not overwrite current theme setting values
	 * @param int|null $offset Offset to begin import from
	 * @param int|null $limit	Number of rows to import
	 * @return	int			Rows inserted
	 */
	public function installTemplates( bool $update=FALSE, int $offset=null, int $limit=null ): int
	{
		$inserted = parent::installTemplates( $update, $offset, $limit );
		
		if ( ( ! $inserted or ( $inserted < $limit ) ) AND $update )
		{
			Templates::importXml( \IPS\ROOT_PATH . "/applications/cms/data/cms_theme.xml", NULL, NULL, $update );
		}

		return $inserted;
	}

	/**
	 * Build skin templates for an app
	 *
	 * @return	array
	 * @throws	RuntimeException
	 */
	public function buildThemeTemplates() : array
	{
		$return = parent::buildThemeTemplates();

		foreach( array( 'database', 'block', 'page' ) as $location )
		{
			Theme::importFromFiles( $location );
		}

		/* Build XML and write to app directory */
		$xml = new XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );

		/* Root tag */
		$xml->startElement('theme');
		$xml->startAttribute('name');
		$xml->text( "Default" );
		$xml->endAttribute();
		$xml->startAttribute('author_name');
		$xml->text( "Invision Power Services, Inc" );
		$xml->endAttribute();
		$xml->startAttribute('author_url');
		$xml->text( "https://www.invisioncommunity.com" );
		$xml->endAttribute();

		/* Templates */
		foreach ( Db::i()->select( '*', 'cms_templates', array( 'template_master=1 and template_user_created=0 and template_user_edited=0' ), 'template_group, template_title' ) as $template )
		{
			/* Initiate the <template> tag */
			$xml->startElement('template');

			foreach( $template as $k => $v )
			{
				if ( in_array( substr( $k, 9 ), array('key', 'title', 'desc', 'location', 'group', 'params', 'app', 'type' ) ) )
				{
					$xml->startAttribute( $k );
					$xml->text( (string) $v );
					$xml->endAttribute();
				}
			}

			/* Write value */
			if ( preg_match( '/<|>|&/', $template['template_content'] ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $template['template_content'] ) );
			}
			else
			{
				$xml->text( $template['template_content'] );
			}

			/* Close the <template> tag */
			$xml->endElement();
		}

		/* Finish */
		$xml->endDocument();

		/* Write it */
		if ( is_writable( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			file_put_contents( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data/cms_theme.xml', $xml->outputMemory() );
		}
		else
		{
			throw new RuntimeException( Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}

		return $return;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return    string
	 */
	protected function get__icon(): string
	{
		return 'folder-open';
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation(): array
	{
		$browseTabs = array();
		
		try
		{
			$defaultPage = Page::getDefaultPage();
			$browseTabs[] = array( 'key' => 'Pages', 'config' => array( 'menu_content_page' => $defaultPage->id, 'menu_title_page_type' => 0 ) );
		}
		catch( OutOfRangeException $ex ) { }
		
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> $browseTabs,
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}
}