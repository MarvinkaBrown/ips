<?php
/**
 * @brief		Block Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		19 Feb 2014
 */

namespace IPS\cms\Blocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Application;
use IPS\cms\Pages\Page;
use IPS\cms\Templates;
use IPS\Data\Store;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Log;
use IPS\Member;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Output;
use IPS\Patterns\ActiveRecord;
use IPS\Request;
use IPS\Theme;
use IPS\Widget;
use LogicException;
use OutOfRangeException;
use ParseError;
use function defined;
use function func_get_args;
use function in_array;
use function intval;
use function is_array;
use function is_callable;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Block Model
 *
 */
class Block extends Model implements Permissions
{
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'cms_blocks';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'block_';

	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('block_key');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();

	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = null;

	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static string $parentNodeColumnId = 'category';

	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static string $parentNodeClass = 'IPS\cms\Blocks\Container';

	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = TRUE;

	/**
	 * @brief	[Node] Sortable?
	 */
	public static bool $nodeSortable = TRUE;

	/**
	 * @brief	[Node] Title
	 */
	public static string $nodeTitle = 'block';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'content_block_name_';

	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';

	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 array(
	 'app'		=> 'core',				// The application key which holds the restrictrions
	 'module'	=> 'foo',				// The module key which holds the restrictions
	 'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 'add'			=> 'foo_add',
	 'edit'			=> 'foo_edit',
	 'permissions'	=> 'foo_perms',
	 'delete'		=> 'foo_delete'
	 ),
	 'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
			'app'		=> 'cms',
			'module'	=> 'pages',
			'prefix' 	=> 'block_'
	);

	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'cms';

	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'blocks';

	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
			'view' => 'view'
	);

	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'perm_cms_block_';

	/**
	 * @brief  Templates already loaded and evald via getTemplate()
	 */
	public static array $calledTemplates = array();

	/**
	 * Parse a block for display
	 * Wrapped in a static method so we can catch the OutOfRangeException and take action.
	 *
	 * @param int|string|Block $block Block ID
	 * @param string|null $orientation Orientation
	 * @return string|null Ready to display HTML
	 */
	public static function display( int|string|Block $block, string $orientation=NULL ): ?string
	{
		try
		{
			try
			{
				if ( is_numeric( $block ) )
				{
					$block = static::load( $block );
				}
				else if ( ! $block instanceof Block)
				{
					$block = static::load( $block, 'block_key' );
				}

				if ( !$block->active )
				{
					return NULL;
				}
			}
			catch( OutOfRangeException $ex )
			{
				return NULL;
			}

			/* We gots the perms to see this? */
			if ( !$block->can( 'view' ) )
			{
				return NULL;
			}

			if ( $block->type === 'custom' )
			{
				if( $block->getConfig('editor') == 'editor' )
				{
					return Theme::i()->getTemplate( 'widgets', 'cms', 'front' )->Wysiwyg( Member::loggedIn()->language()->addToStack( "cms_block_content_{$block->id}" ), $orientation );
				}
				try
				{
					$functionName = 'content_blocks_' .  $block->id;
	
					if ( ! isset( Store::i()->$functionName ) )
					{
						$content = $block->content;
						Store::i()->$functionName = Theme::compileTemplate( $content, $functionName );
					}
	
					Theme::runProcessFunction( Store::i()->$functionName, $functionName );
	
					if( $block->getConfig('editor') == 'php' )
					{
						unset( Store::i()->$functionName );
					}

					$themeFunction = 'IPS\\Theme\\'. $functionName;
					$html = $themeFunction();

					return $html;
				}
				catch ( ParseError $e )
				{
					@ob_end_clean();
					Log::log( $e, 'block_error' );
					return "<span style='background:black;color:white;padding:6px;'>[[Block {$block->key} is throwing an error]]</span>";
				}
			}
			else
			{
				$block->orientation = $orientation;

				if ( $block->template OR $block->content )
				{
					$block->widget()->template( array( $block, 'getTemplate' ) );
				}

				return Widget::parseOutput( $block->widget()->render() );
			}
		}
		catch( OutOfRangeException $ex )
		{
			return NULL;
		}
	}

	/**
	 *  Method to overload standard widget templates
	 *
	 *  @return string
	 */
	public function getTemplate() : string
	{
		$args		  = func_get_args();
		$functionName = 'content_template_for_block_' .  $this->id;

		unset( Store::i()->$functionName );

		/* Still here */
		if ( ! in_array( $functionName, array_keys( static::$calledTemplates ) ) )
		{
			if ( ! isset( Store::i()->$functionName ) )
			{
				if ( $this->content )
				{
					Store::i()->$functionName = Theme::compileTemplate( $this->content, 'run', $this->template_params );
					
				}
				else if ( $this->template )
				{
					try
					{
						$template	= Templates::load( $this->template );
						$object		= \IPS\cms\Theme::i()->getTemplate( $template->group, 'cms', $template->location );
						$title		= $template->title;
						return $object->$title( ...$args );
					}
					catch( OutOfRangeException $ex )
					{
						/* @todo what to do here? */
					}
				}
			}

			/* Put them in a class */
			$template = <<<EOF
class class_{$functionName}
{

EOF;
			$template .= Store::i()->$functionName;

			$template .= <<<EOF
}
EOF;

			/* It lives! */
			Theme::runProcessFunction( $template, $functionName );

			$class = "\IPS\Theme\\class_{$functionName}";

			/* Init */
			static::$calledTemplates[ $functionName ] = new $class();
		}
		
		return static::$calledTemplates[ $functionName ]->run( ...$args );
	}

	/**
	 * Delete compiled versions
	 *
	 * @param int|array|null $ids	Integer ID or Array IDs to remove
	 * @return void
	 */
	public static function deleteCompiled( int|array|null $ids=NULL ) : void
	{
		if ( $ids === NULL )
		{
			$ids = iterator_to_array( Db::i()->select( 'block_id', 'cms_blocks' )->setValueField('block_id') );
		}
		else if ( is_numeric( $ids ) )
		{
			$ids = array( $ids );
		}

		foreach( $ids as $id )
		{
			$functionName = 'content_blocks_' .  $id;
			if ( isset( Store::i()->$functionName ) )
			{
				unset( Store::i()->$functionName );
			}

			$functionName = 'content_template_for_block_' .  $id;
			if ( isset( Store::i()->$functionName ) )
			{
				unset( Store::i()->$functionName );
			}
		}
		
		/* We can also use blocks in per-page CSS */
		Page::deleteCompiledIncludes();
	}

	/**
	 * @brief	Config json as array
	 */
	protected mixed $_config = null;

	/**
	 * @brief   Stores a \IPS\Widget object if this is a custom block with an embedded widget
	 */
	protected array $widgetLoaded = [];

	/**
	 * @brief   Orientation for an embedded widget
	 */
	public ?string $orientation = NULL;

	/**
	 * Get config as an array if no $key, or as whatever type corresponds to key
	 *
	 * @param string|null $key	Config key to fetch
	 * @return	mixed
	 */
	public function getConfig( string $key = NULL ): mixed
	{
		if ( $this->_config === NULL )
		{
			$this->_config = json_decode( $this->config, TRUE );

			if ( $this->_config === FALSE )
			{
				$this->_config = array();
			}
		}

		if ( $key )
		{
			if ( isset( $this->_config[ $key ] ) )
			{
				return $this->_config[ $key ];
			}

			return NULL;
		}

		return $this->_config;
	}

	/**
	 * Set config key and value
	 *
	 * @param string $key	Config key
	 * @param	mixed	$value	Config value
	 * @return	void
	 */
	public function setConfig( string $key, mixed $value ) : void
	{
		$this->_config[ $key ] = $value;
	}

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge(): ?array
	{
		return array(
			0	=> 'ipsBadge ipsBadge--intermediary',
			1	=> $this->type === 'custom' ? 'content_block_add_type_custom' : 'content_block_add_type_plugin',
		);
	}

	/**
	 * [Node] Get description
	 *
	 * @return	string|null
	 */
	protected function get__description(): ?string
	{
		return Member::loggedIn()->language()->addToStack( 'content_block_name_' . $this->_id . '_desc' );
	}

	/**
	 * Get configuration.
	 *
	 * @return array
	 */
	public function get__plugin_config(): array
	{
		return ( $this->plugin_config ? ( ! is_string( $this->plugin_config ) ? $this->plugin_config : json_decode( $this->plugin_config, TRUE ) ) : array() );
	}

	/**
	 * [Node] Get buttons to display in tree
	 *
	 * @param Url $url		Base URL
	 * @param bool $subnode	Is this a subnode?
	 * @return    array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ): array
	{
		$buttons = parent::getButtons( $url, $subnode );

		if ( isset( $buttons['add'] ) )
		{
			unset( $buttons['add']['data'] );
		}

		if ( isset( $buttons['edit'] ) )
		{
			unset( $buttons['edit']['data'] );
		}

        /* View Details */
        $buttons['details']	= array(
            'icon'	=> 'search',
            'title'	=> 'block_embed_options',
            'link'	=> Url::internal( "app=cms&module=pages&controller=blocks&do=embedOptions&id={$this->_id}" ),
            'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('block_embed_options') )
        );

		return $buttons;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$block_type   = ( isset( Request::i()->block_type ) )   ? Request::i()->block_type   : ( $this->id ? $this->type : null );
		$block_editor = ( isset( Request::i()->block_editor ) ) ? Request::i()->block_editor : ( $this->id ? $this->getConfig('editor') : null );
		$block_plugin = ( isset( Request::i()->block_plugin ) ) ? Request::i()->block_plugin : ( $this->id ? $this->plugin : null );

		/* Build form */
		$form->addTab( 'content_block_form_tab__details' );
		$form->add( new Translatable( 'block_name', NULL, TRUE, array(
				'app'  => 'cms',
				'key'  => ( $this->id ? "content_block_name_" .  $this->id : NULL )
		) ) );

		$form->add( new Translatable( 'block_description', NULL, FALSE, array(
            'app' => 'cms',
            'key' => ( $this->id ? "content_block_name_" .  $this->id . '_desc' : NULL )
        ) ) );
		
		try
		{
			$nodeContainer = $this->id ? $this->category :
				( Request::i()->parent ?: Container::load( ( $block_type == 'custom' ? 'block_custom' : 'block_plugins' ), 'container_key' )->id );
		}
		catch( OutOfRangeException $e )
		{
			$nodeContainer = NULL;
		}
		$form->add( new Node( 'block_category', $nodeContainer, TRUE, array(
				'class'    => '\IPS\cms\Blocks\Container',
				'subnodes' => false
		) ) );

		$form->addHeader( 'cms_block_form_display' );

		$form->add( new Text( 'block_key', $this->id ? $this->key : FALSE, FALSE, array(), function( $val )
		{
			try
			{
				if ( ! $val )
				{
					return true;
				}

				try
				{
					$block = Block::load( $val, 'block_key');
				}
				catch( OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
					return true;
				}

				/* It's taken... */
				if ( Request::i()->id == $block->id )
				{
					/* But it's this one so that's ok */
					return true;
				}

				/* and if we're here, it's not... */
				throw new InvalidArgumentException('cms_block_key_not_unique');
			}
			catch ( OutOfRangeException $e )
			{
				/* Slug is OK as load failed */
				return true;
			}
		} ) );

		/* Do we have config? */
		if ( $block_type === 'plugin' and $block_plugin )
		{
			if ( ! $this->id )
			{
				$this->type       = 'plugin';
				$this->plugin     = $block_plugin;
				
				if ( isset( Request::i()->block_plugin_app ) )
				{
					$this->plugin_app = Request::i()->block_plugin_app;
				}
			}

			if ( mb_substr( $block_plugin, 0, 8 ) === 'db_feed_' )
			{
				$databaseId = intval( mb_substr( $block_plugin, 8 ) );
				$this->plugin = 'RecordFeed';
				$this->plugin_config = array( 'cms_rf_database' => $databaseId );

				/* JS needs this to produce the preview */
				$form->hiddenValues['cms_rf_database'] = $databaseId;
			}
			else if ( $this->id and $block_plugin === 'RecordFeed' )
			{
				$form->hiddenValues['cms_rf_database'] = $this->_plugin_config['cms_rf_database'];
			}
						
			try
			{
				$block_editor = 'html';

				Output::i()->output .= Theme::i()->getTemplate( 'global', 'core', 'global' )->message( Member::loggedIn()->language()->addToStack( 'cms_block_feed_form_message', FALSE, array( 'sprintf' => $this->widget()->title() ) ), 'information', NULL, FALSE );
			}
			catch ( OutOfRangeException $ex )
			{
				throw new LogicException( 'cms_error_block_plugin_not_found' );
			}

			if ( is_callable( array( $this->widget(), 'configuration' ) ) )
			{
				$form->addTab( 'content_block_form_tab__feed' );
				$this->widget()->configuration( $form );
			}
		}

		$form->addTab( 'content_block_form_tab__content');

		if ( $block_type === 'plugin' )
		{
			$templates = array( '_default_' => Member::loggedIn()->language()->addToStack('content_block_template_use_default') );

			foreach( Templates::getTemplates( Templates::RETURN_BLOCK ) as $id => $obj )
			{
				if ( $obj->group == $this->widget()->key )
				{
					$templates[ $obj->key ] = $obj->title;
				}
			}

			/* List of templates */
			$form->add( new Select( 'block_template_id', ( $this->id and $this->template ) ? $this->template : NULL, FALSE, array(
					'options' => $templates
			), NULL, NULL, Theme::i()->getTemplate( 'blocks', 'cms', 'admin' )->previewTemplateLink( $block_plugin ), 'block_template_id' ) );

			/* Use or copy to edit */
			$useHow = NULL;
			if ( $this->id )
			{
				if ( intval( $this->template ) or ( ! intval( $this->template ) and ! $this->content ) )
				{
					$useHow = 'use';
				}
				else
				{
					$useHow = 'copy';
				}
			}

			$form->add( new Select( 'block_template_use_how', $useHow, FALSE, array(
					'options' => array(
						'use'	=> 	'block_template_use_how_use',
						'copy'	=>  'block_template_use_how_copy'
					),
					'toggles' => array(
						'copy' => array( 'block_content', 'block_save_as_template' )
					)
			), NULL, NULL, NULL, 'block_template_use_how' ) );
		}

		if ( $block_editor === 'editor' )
		{
			$form->add( new Translatable( 'block_content', NULL, FALSE, array(
				'key'			=> ( $this->id ) ? "cms_block_content_{$this->id}" : NULL,
				'editor'		=> array(
					'app'         => 'cms',
					'key'         => 'BlockContent',
					'autoSaveKey' => 'block-content-' . ( $this->id ? $this->id : 'new' ),
					'attachIds'	  => ( $this->id ) ? array( $this->id ) : NULL
				)
			) ) );
				
		}
		else
		{
			$form->add( new Codemirror( 'block_content', htmlentities( (string) $this->content, ENT_DISALLOWED, 'UTF-8' ), FALSE, array( 'tagSource' => Url::internal( "app=cms&module=pages&controller=blocks&do=loadTags" ), "codeModeAllowedLanguages" => ["ipsphtml"]), function($val ) {
				if ( mb_strpos( $val, '{block="' . Request::i()->block_key . '"}' ) !== FALSE )
				{
					throw new DomainException('block_content_recursive_error');
				}
			}, NULL, NULL, 'block_content' ) );
		}

		if ( $block_type === 'plugin' and ! $this->id )
		{
			$form->add( new YesNo( 'block_save_as_template', FALSE, FALSE, array(
				'togglesOn' => array( 'block_save_as_template_name' )
			), NULL, NULL, NULL, 'block_save_as_template' ) );

			$form->add( new Text( 'block_save_as_template_name', NULL, FALSE, array(), NULL, NULL, NULL, 'block_save_as_template_name' ) );
		}

		if ( $block_type === 'custom' )
		{
			$form->add( new YesNo( 'block_cache', ( $this->id ) ? $this->cache : FALSE, FALSE, array(), NULL, NULL, NULL, 'block_cache' ) );
		}

		$form->hiddenValues['block_type']      = $block_type;
		$form->hiddenValues['block_editor']    = $block_editor;
		$form->hiddenValues['block_plugin']    = $this->plugin;
		if ( $this->plugin_app )
		{
			$form->hiddenValues['block_plugin_app']= $this->plugin_app;
		}

		$form->hiddenValues['template_params'] = ( $this->id ) ? $this->template_params : '';

		/* If we are editing, we can save and reload */
		if( $this->id )
		{
			$form->canSaveAndReload = true;
		}

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'templates/view.css', 'cms', 'admin' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'blocks/form.css', 'cms', 'admin' ) );

		Output::i()->globalControllers[]  = 'cms.admin.blocks.form';
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_blocks.js', 'cms' ) );
		Output::i()->title = ( $this->id ) ? Member::loggedIn()->language()->addToStack( 'content_block_block_editing', FALSE, array( 'sprintf' => array( $this->_title ) ) ) : Member::loggedIn()->language()->addToStack('content_block_block_add');
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		/* Claim Attachments - we need to adjust the temp key based on presence if this block existed or not */
		if ( Request::i()->block_editor === 'editor' )
		{
			File::claimAttachments( ( $this->id ) ? 'block-content-' . $this->id : 'block-content-new', $this->id );
		}
		
		$this->type		= Request::i()->block_type;
		$values['type']	= $this->type;
		
		if ( ! $this->id )
		{
			$this->active = 1;
			$this->save();
		}

		$config = array();
		if( isset( $values['language_key'] ) )
		{
			$config['language_key'] = $values['language_key'];
		}

		if( isset( $values['block_name'] ) )
		{
			Lang::saveCustom( 'cms', "content_block_name_" . $this->id, $values['block_name'] );
		}

		if( isset( $values['block_description'] ) )
		{
			Lang::saveCustom( 'cms', "content_block_name_" . $this->id . '_desc', $values['block_description'] );

			unset ( $values['block_description'] );
		}

		if( isset( $values['block_key'] ) )
		{
			if ( ! $values['block_key'] )
			{
				if ( is_array( $values['block_name'] ) )
				{
					reset( $values['block_name'] );
					$values['block_key'] = Friendly::seoTitle( $values['block_name'][ key( $values['block_name'] ) ] );
				}
				else
				{
					$values['block_key'] = Friendly::seoTitle( $values['block_name'] );
				}

				/* Now test it */
				try
				{
					$block = Block::load( $values['block_key'], 'block_key');

					/* It's taken... */
					if ( $this->id != $block->id )
					{
						$values['block_key'] .= '_' . mt_rand();
					}
				}
				catch( OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
				}
			}
		}

		if ( Request::i()->block_type === 'plugin' )
		{
			$values['plugin_app'] = Request::i()->block_plugin_app;

			/* configure widget related values */
			if ( is_callable( array( $this->widget(), 'preConfig' ) ) )
			{
				$values = $this->widget()->preConfig( $values );
			}

			/* Special advanced builder stuff */
			if( $this->widget()->isBuilderWidget() )
			{
				if( isset( $values['widget_adv__background_custom_image'] ) and $values['widget_adv__background_custom_image'] )
				{
					$values['widget_adv__background_custom_image'] = (string) $values['widget_adv__background_custom_image'];
				}
			}
			
			if( isset( $values['show_on_all_devices'] ) and $values['show_on_all_devices'] )
			{
				$values['devices_to_show'] = array( 'Phone', 'Tablet', 'Desktop' );
			}

			unset( $values['show_on_all_devices'] );

			/* Store config */
			foreach( $values as $k => $v )
			{
				if ( ! in_array( $k, array( 'block_name', 'block_key', 'block_description', 'block_category', 'block_template_id', 'block_template_use_how', 'block_content', 'block_save_as_template', 'block_save_as_template_name' ) ) )
				{
					if ( is_array( $v ) )
					{
						$theValue = NULL;
						foreach( $v as $eachKey => $eachValue )
						{
							if ( !( $eachValue instanceof Model ) AND $eachValue instanceof ActiveRecord )
							{
								$column     = $eachValue::$databaseColumnId;
								$theValue[] = $eachValue->$column;
							}
							elseif( $eachValue instanceof Model )
							{
								$theValue[ $eachKey ] = $eachValue;
							}
							elseif ( $eachKey == 'start' or $eachKey == 'end' )
							{
								/* date ranges */
								$theValue[ $eachKey ] = $eachValue;
							}
							else
							{
								$theValue[ $eachKey ] = $eachValue;
							}
						}
						$v = $theValue;
					}
					else if ( !( $v instanceof Model ) AND $v instanceof ActiveRecord )
					{
						$column = $v::$databaseColumnId;
						$v      = $v->$column;
					}
					else if ( $v instanceof Url)
					{
						$v = (string) $v;
					}

					$config[ $k ] = $v;

					unset( $values[ $k ] );
				}
			}

			$values['plugin']			= Request::i()->block_plugin;
			$values['plugin_config']	= json_encode( $config );

			/* Are we using the template as-is? */
			if ( $values['block_template_use_how'] === 'use' )
			{
				$values['content'] = null;

				/* Not using default? */
				if ( $values['block_template_id'] != '_default_' )
				{
					$values['template'] = $values['block_template_id'];
				}
				else
				{
					$values['template'] = 0;
				}
			}
			else
			{
				/* We're using a copy */
				if ( isset( $values['block_save_as_template'] ) AND $values['block_save_as_template'] )
				{
					$templateArray = array(
						'desc' 		   => null,
						'content' 	   => $values['block_content'],
						'location' 	   => 'block',
						'group' 	   => Request::i()->block_plugin,
						'container'    => null,
						'rel_id' 	   => 0,
						'user_created' => 1,
						'user_edited'  => 0,
					);

					if ( $values['block_template_id'] == '_default_' )
					{
						/* Find it from the normal template system */
						$plugin = $this->widget();

						$location = $plugin->getTemplateLocation();

						$templateBits  = Theme::master()->getAllTemplates( $location['app'], $location['location'], $location['group'], Theme::RETURN_ALL );
						$templateBit   = $templateBits[ $location['app'] ][ $location['location'] ][ $location['group'] ][ $location['name'] ];

						$templateArray['key']		= 'template_' . $templateBit['template_name'] . '.' . mt_rand();
						$templateArray['title']		= str_replace( '-', '_', Friendly::seoTitle( $values['block_save_as_template_name'] ?: $templateBit['template_name'] . '_' . Member::loggedIn()->language()->get('copy_noun') ) );
						$templateArray['params']	= $templateBit['template_data'];
					}
					else
					{
						try
						{
							$template = Templates::load( $values['block_template_id'] );

							$templateArray['key']		= 'template_' . $template->name . '.' . mt_rand();
							$templateArray['title']		= str_replace( '-', '_', Friendly::seoTitle( $values['block_save_as_template_name'] ?: $template->name . '_' . Member::loggedIn()->language()->get('copy_noun') ) );
							$templateArray['params']	= $template->params;
						}
						catch( OutOfRangeException $ex )
						{
							throw new LogicException('cms_error_no_template_found');
						}
					}

					/* Make sure template name is unique within the group */
					if( Db::i()->select( 'COUNT(*)', 'cms_templates', array( 'template_title=? and template_location=? and template_group=?', $templateArray['title'], $templateArray['location'], $templateArray['group'] ) )->first() )
					{
						$templateArray['title'] = $templateArray['title'] . '_' . time();
					}

					/* Save */
					$newTemplate = Templates::add( $templateArray );

					$values['content']  = null;
					$values['template'] = $newTemplate->key;
				}
				else
				{
					/* Just use it this once */
					$values['content']  = $values['block_content'];
					$values['template'] = 0;
				}
			}
		}
		else if( isset( $values['block_content'] ) )
		{
			$values['template'] = 0;
			if ( Request::i()->block_editor === 'editor' )
			{
				Lang::saveCustom( 'cms', "cms_block_content_{$this->id}", $values['block_content'] );
				$values['block_content'] = NULL;
			}
		}

		if ( isset( $values['block_category'] ) AND ( ! empty( $values['block_category'] ) ) )
		{
			$values['block_category'] = ( $values['block_category'] instanceof Model ) ? $values['block_category']->_id : 0;
		}

		if( isset( Request::i()->template_params ) )
		{
			$values['template_params'] = Request::i()->template_params;
		}

		/* Config */
		if( isset( Request::i()->block_editor ) )
		{
			$this->setConfig( 'editor', Request::i()->block_editor );
		}

		foreach( array( 'block_name', 'block_description', 'block_save_as_template', 'block_template_id', 'block_template_use_how', 'block_save_as_template_name', 'block_editor', 'block_plugin_app', 'block_plugin' ) as $field )
		{
			if ( array_key_exists( $field, $values ) )
			{
				unset( $values[ $field ] );
			}
		}

		return $values;
	}

	/**
	 * @var array|null
	 */
	protected static ?array $blockPages = null;

	/**
	 * Load a mapping of all custom blocks to the pages that use them
	 *
	 * @return array
	 */
	protected static function getBlockMapping() : array
	{
		if( static::$blockPages === null )
		{
			/* First find any custom blocks that reference other blocks.
			Load up a mapping  so that we can detect it later  */
			$customBlocksWithBlocks = [];
			foreach( Db::i()->select( '*', 'cms_blocks', [ 'block_type=?', 'custom' ] ) as $row )
			{
				preg_match_all( '/\{block="(.+?)"}/is', $row['block_content'], $matches );
				if( count( $matches[1] ) )
				{
					$customBlocksWithBlocks[ $row['block_key'] ] = $matches[1];
				}
			}

			$mapping = [];
			foreach( Db::i()->select( '*', 'cms_page_widget_areas', array( "area_tree LIKE CONCAT( '%',?,'%' ) OR area_tree LIKE CONCAT( '%',?,'%')", 'app_cms_Blocks', 'app_cms_Codemirror' ) )
				->join( 'cms_pages', 'cms_page_widget_areas.area_page_id=cms_pages.page_id' )
					 as $row )
			{
				if( $tree = json_decode( $row['area_tree'], true ) )
				{
					$area  = new Widget\Area( $tree, $row['area_area'] );
					foreach( $area->getAllWidgets() as $widget )
					{
						if( $widget['app'] == 'cms' )
						{
							if( $widget['key'] == 'Blocks' )
							{
								if( isset( $widget['configuration'] ) and isset( $widget['configuration']['cms_widget_custom_block'] ) )
								{
									$blockKey = $widget['configuration']['cms_widget_custom_block'];
									if( !isset( $mapping[ $blockKey ] ) )
									{
										$mapping[ $blockKey ] = [];
									}
									$mapping[ $blockKey ][] = Page::constructFromData( $row );

									/* Does this block reference other blocks? */
									if( isset( $customBlocksWithBlocks[ $blockKey ] ) )
									{
										foreach( $customBlocksWithBlocks[ $blockKey ] as $key )
										{
											if( !isset( $mapping[ $key ] ) )
											{
												$mapping[ $key ] = [];
											}
											$mapping[ $key ][] = Page::constructFromData( $row );
										}
									}
								}
							}
							elseif( $widget['key'] == 'Codemirror' )
							{
								if( isset( $widget['configuration'] ) and $widget['configuration']['content'] )
								{
									preg_match_all( '/\{block="(.+?)"}/is', $widget['configuration']['content'], $matches );
									foreach( $matches[1] as $blockKey )
									{
										if( !isset( $mapping[ $blockKey ] ) )
										{
											$mapping[ $blockKey ] = [];
										}
										$mapping[ $blockKey ][] = Page::constructFromData( $row );
									}
								}
							}
						}
					}
				}
			}

			static::$blockPages = $mapping;
		}

		return static::$blockPages;
	}

	/**
	 * Find all pages that use this block
	 *
	 * @return array|null
	 */
	public function getPages() : ?array
	{
		$mapping = static::getBlockMapping();
		return $mapping[ $this->key ] ?? null;
	}

	/**
	 * Save data
	 *
	 * @return void
	 */
	public function save(): void
	{
		if ( $this->_config !== NULL )
		{
			$this->config = json_encode( $this->_config );
		}

		if ( $this->id )
		{
			static::deleteCompiled( $this->id );
			\IPS\cms\Widget::deleteCachesForBlocks( $this->key );
		}

		parent::save();
	}

	/**
	 * Returns the widget object associated with this custom block
	 *
	 * @return Widget
	 */
	public function widget(): Widget
	{
		if ( $this->type === 'plugin' AND $this->plugin )
		{
			if ( mb_substr( $this->plugin, 0, 8 ) === 'db_feed_' )
			{
				$this->plugin = 'RecordFeed' . mb_substr( $this->plugin, 8 );
			}
			elseif( $this->plugin == 'RecordFeed' and isset( $this->_plugin_config['cms_rf_database'] ) )
			{
				$this->plugin .= $this->_plugin_config['cms_rf_database'];
			}

			if ( empty( $this->widgetLoaded[ $this->orientation ] ) )
			{
				$this->widgetLoaded[ $this->orientation ] = Widget::load(  Application::load( $this->plugin_app ), $this->plugin, mt_rand(), $this->_plugin_config, NULL, $this->orientation );
			}

			return $this->widgetLoaded[ $this->orientation ];
		}

		throw new OutOfRangeException;
	}

	/**
	 * [Node] Does the currently logged in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy(): bool
	{
		return FALSE;
	}
}
