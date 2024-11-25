<?php

/**
 * @brief        Setting
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        2/14/2024
 */

namespace IPS\Theme\Editor;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use const IPS\IN_DEV;
use function str_replace;
use function trim;
use function ucwords;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Setting extends Model
{
	const SETTING_TEXT = 'text';
	const SETTING_NUMBER = 'range';
	const SETTING_COLOR = 'color';
	const SETTING_SELECT = 'select';
	const SETTING_CHECKBOX = 'checkbox';
	const SETTING_IMAGE = 'image';

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static array $multitons	= array();

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'setting_';

	/**
	 * @brief	[ActiveRecord] Database table
	 * @note	This MUST be over-ridden
	 */
	public static ?string $databaseTable	= 'core_theme_editor_settings';

	/**
	 * @var array
	 */
	protected static array $multitonMap = array();

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 * @note	If using this, declare a static $multitonMap = array(); in the child class to prevent duplicate loading queries
	 */
	protected static array $databaseIdFields = array( 'setting_key' );

	/**
	 * @brief       [Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}" as the key
	 */
	public static ?string $titleLangPrefix = 'themeeditor_var_';

	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';

	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static string $parentNodeColumnId = 'category_id';

	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static string $parentNodeClass = Category::class;

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'theme_editor_settings';

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'themeEditorSettings' );

	/**
	 * @brief	[ActiveRecord] Attempt to load from cache
	 * @note	If this is set to TRUE you should define a getStore() method to return the objects from cache
	 */
	protected static bool $loadFromCache = true;

	/**
	 * [Node] Get Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		return Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $this->key, NULL, array( 'escape' => TRUE ) );
	}

	/**
	 * Get the title for a node using the specified language object
	 * This is commonly used where we cannot use the logged in member's language, such as sending emails
	 *
	 * @param Lang $language	Language object to fetch the title with
	 * @param array $options	What options to use for language parsing
	 * @return	string
	 */
	public function getTitleForLanguage( Lang $language, array $options=array() ): string
	{
		return $language->addToStack( static::$titleLangPrefix . $this->key, NULL, $options );
	}

	/**
	 * [Node] Get Title language key, not added to a language stack
	 *
	 * @return	string|null
	 */
	protected function get__titleLanguageKey(): ?string
	{
		return static::$titleLangPrefix . $this->key;
	}

	/**
	 * [Node] Get content table description
	 *
	 * @return	string|null
	 */
	protected function get_description(): ?string
	{
		return Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $this->key . static::$descriptionLangSuffix );
	}

	/**
	 * Attempt to load cached data
	 *
	 * @note	This should be overridden in your class if you define $cacheToLoadFrom
	 * @return    array
	 */
	public static function getStore(): array
	{
		try
		{
			$cache = Store::i()->themeEditorSettings;
		}
		catch( OutOfRangeException )
		{
			$cache = iterator_to_array( Db::i()->select( '*', static::$databaseTable, NULL, static::$databasePrefix . static::$databaseColumnId )->setKeyField( static::$databasePrefix . static::$databaseColumnId ) );
			Store::i()->themeEditorSettings = $cache;
		}

		return $cache;
	}

	/**
	 * Load all the setting into the cache in one shot
	 *
	 * @return void
	 */
	public static function loadAllKeys() : void
	{
		if( !isset( static::$multitonMap[ 'setting_key'] ) )
		{
			foreach( new ActiveRecordIterator(
				Db::i()->select( '*', static::$databaseTable ),
				Setting::class
					 ) as $setting )
			{
				if( !isset( static::$multitons[ $setting->id ] ) )
				{
					static::$multitons[ $setting->id ] = $setting;
				}

				/* Color settings will be called with the prefix light__ or dark__ */
				if( $setting->type == static::SETTING_COLOR )
				{
					static::$multitonMap['setting_key'][ 'dark__' . $setting->key ] = $setting->id;
					static::$multitonMap['setting_key'][ 'light__' . $setting->key ] = $setting->id;
				}
				static::$multitonMap['setting_key'][ $setting->key ] = $setting->id;
			}
		}
	}

	/**
	 * Fetch All Root Nodes
	 *
	 * @param string|null $permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param Member|null $member				The member to check permissions for or NULL for the currently logged in member
	 * @param mixed $where				Additional WHERE clause
	 * @param array|null $limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	Model[]
	 */
	public static function roots( ?string $permissionCheck='view', Member $member=NULL, mixed $where=array(), array $limit=NULL ): array
	{
		$return = parent::roots( $permissionCheck, $member, $where, $limit );
		static::loadAllKeys();
		return $return;
	}

	/**
	 * @return string
	 */
	public function get_name() : string
	{
		return Member::loggedIn()->language()->addToStack( 'themeeditor_var_' . $this->key );
	}

	/**
	 * @return string
	 */
	public function get_desc() : string
	{
		if( Member::loggedIn()->language()->checkKeyExists( 'themeeditor_var_' . $this->key . '_desc' ) )
		{
			return Member::loggedIn()->language()->addToStack( 'themeeditor_var_' . $this->key . '_desc' );
		}

		return '';
	}

	/**
	 * @return array
	 */
	public function get_data() : array
	{
		return isset( $this->_data['data'] ) ? json_decode( $this->_data['data'], true ) : array();
	}

	/**
	 * @return array|string
	 */
	public function get_default() : array|string
	{
		if( $this->type == static::SETTING_COLOR )
		{
			return isset( $this->_data['default'] ) ? json_decode( $this->_data['default'], true ) : array( 'light' => '', 'dark' => '' );
		}
		elseif( $this->type == static::SETTING_NUMBER )
		{
			return isset( $this->_data['default'] ) ? (int) $this->_data['default'] : 0;
		}

		return $this->_data['default'] ?? '';
	}

	/**
	 * @param array|string|null $val
	 * @return void
	 */
	public function set_data( array|string|null $val ) : void
	{
		if( is_array( $val ) )
		{
			$val = json_encode( $val );
		}

		$this->_data['data'] = $val ?: null;
	}

	/**
	 * @var array|null
	 */
	protected static ?array $appKeys = null;

	/**
	 * Load all settings for a given category
	 *
	 * @param Category $category
	 * @return array
	 */
	public static function loadByCategory( Category $category ) : array
	{
		if( static::$appKeys === null )
		{
			static::$appKeys = array_keys( Application::enabledApplications() );
		}

		$return = [];
		foreach( new ActiveRecordIterator(
			Db::i()->select( '*', static::$databaseTable, array( 'setting_category_id=?', $category->id ), 'setting_position' ),
			Setting::class
				 ) as $setting )
		{
			/* Make sure we only use enabled applications */
			if( $setting->app and !in_array( $setting->app, static::$appKeys ) )
			{
				continue;
			}

			if( $setting->set_id and !in_array( $setting->set_id, array( Theme::$defaultFrontendThemeSet, Theme::i()->id ) ) )
			{
				continue;
			}

			$return[] = $setting;
		}
		return $return;
	}

	/**
	 * Figure out the value for this setting
	 *
	 * @param bool $forceDefault
	 * @return string|array
	 */
	public function value( bool $forceDefault=false ): string|array
	{
		/* Do we have an override in the theme? */
		$overrides = $forceDefault ? [] : Theme::i()->getCssVariables( Theme::CUSTOM_ONLY );

		switch( $this->type )
		{
			case static::SETTING_COLOR:
				if( $forceDefault )
				{
					return [
						'light' => $this->default['light'],
						'dark' => $this->default['dark']
					];
				}

				return [
					'light' => ( $overrides['light__' . $this->key ] ?? $this->default['light'] ),
					'dark' => ( $overrides['dark__' . $this->key ] ?? $this->default['dark'] )
				];

			default:
				if( $forceDefault )
				{
					$value = $this->default;
				}
				else
				{
					$value = $overrides[ $this->key ] ?? $this->default;
				}

				if( empty( $value ) and $value != 0 )
				{
					return "";
				}

				/* If we have an override,then the admin manually entered text, so treat it
				as plain text */
				if( !$forceDefault and $this->type == static::SETTING_TEXT and isset( $overrides[ $this->key ] ) )
				{
					$value = htmlentities( $value, ENT_QUOTES );
				}

				$functionName = 'setting__' . ( $forceDefault ? 'default__' : '' ) . str_replace( '-', '_', $this->key );
				Theme::runProcessFunction( Theme::_compileTemplate( $value, $functionName ), $functionName );
				$settingFunction = 'IPS\\Theme\\' . $functionName;
				$return = $settingFunction();
				return trim( $return );
		}
	}

	/**
	 * Wrapper to make things easier in the templates
	 *
	 * @return string|array
	 */
	public function defaultValue() : string|array
	{
		return $this->value( true );
	}

	/**
	 * Parse the value of this setting based on the setting type
	 *
	 * @param string|null $value
	 * @return string|int
	 */
	public function parsedValue( ?string $value ) : string|int
	{
		if( $value === null )
		{
			$value = Theme::i()->getCssVariables()[ $this->key ] ?? '';
		}

		switch( $this->type )
		{
			case static::SETTING_IMAGE:
				$value = 'url("' . $value . '")';
				break;

			case static::SETTING_TEXT:
				$value = strip_tags( $value );
				$value = str_replace( array( '/*', ';' ), '', $value );
				$value = htmlentities( $value );
				break;

			case static::SETTING_CHECKBOX:
				$value = (int) $value;
				break;
		}

		return $value;
	}

	/**
	 * @return string
	 */
	public function editorHtml() : string
	{
		$template = 'setting' . ucwords( $this->type );
		return Theme::i()->getTemplate( 'themeeditor', 'core', 'front' )->$template( $this );
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 * array(
	 * array(
	 * 'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	 * 'title'	=> 'foo',		// Language key to use for button's title parameter
	 * 'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 * 'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 * ),
	 * ...							// Additional buttons
	 * );
	 * @endcode
	 * @param Url $url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ): array
	{
		$buttons = parent::getButtons( $url, $subnode );

		/* Don't allow modification of 1st party settings if we're in theme designer mode */
		if( !IN_DEV and Settings::i()->theme_designer_mode and !$this->set_id and in_array( $this->app, IPS::$ipsApps ) )
		{
			unset( $buttons['edit'] );
			unset( $buttons['delete'] );
		}

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
		$form->add( new Translatable( 'themeeditor_setting_name', null, true, array(
			'app' => 'core',
			'key' => ( $this->id ? 'themeeditor_var_' . $this->key : null )
		) ) );

		$form->add( new Translatable( 'themeeditor_setting_desc', null, false, array(
			'app' => 'core',
			'key' => ( $this->id ? 'themeeditor_var_' . $this->key . '_desc' : null )
		) ) );

		$form->add( new Text( 'themeeditor_setting_key', $this->key, true, array(
			'regex' => '/[A-Z]+/i',
			'maxLength' => 150
		), function( $val ){
			try
			{
				$test = static::load( $val, 'setting_key' );
				if( $test->id != $this->id )
				{
					throw new InvalidArgumentException( "err__duplicate_setting_key" );
				}
			}
			catch( OutOfRangeException ){}
		}) );

		$form->add( new Node( 'themeeditor_setting_category', $this->parent(), true, array(
			'class' => Category::class,
			'multiple' => false,
			'subnodes' => false,
			'permissionCheck' => function( $val ){
				if( $val instanceof Category and ( $val->hasSettings() or $val->hasColors() or !$val->hasContents() ) )
				{
					return true;
				}
				return false;
			}
		) ) );

		$form->add( new Select( 'themeeditor_setting_type', $this->type, true, array(
			'options' => array(
				static::SETTING_COLOR => 'themeeditor_setting_type__' . static::SETTING_COLOR,
				static::SETTING_TEXT => 'themeeditor_setting_type__' . static::SETTING_TEXT,
				static::SETTING_NUMBER => 'themeeditor_setting_type__' . static::SETTING_NUMBER,
				static::SETTING_SELECT => 'themeeditor_setting_type__' . static::SETTING_SELECT,
				static::SETTING_CHECKBOX => 'themeeditor_setting_type__' . static::SETTING_CHECKBOX,
				static::SETTING_IMAGE => 'themeeditor_setting_type__' . static::SETTING_IMAGE
			),
			'toggles' => array(
				static::SETTING_COLOR => array( 'themeeditor_setting_light_default', 'themeeditor_setting_dark_default' ),
				static::SETTING_TEXT => array( 'themeeditor_setting_default' ),
				static::SETTING_NUMBER => array( 'themeeditor_setting_min', 'themeeditor_setting_max', 'themeeditor_setting_step', 'themeeditor_setting_default' ),
				static::SETTING_SELECT => array( 'themeeditor_setting_options', 'themeeditor_setting_default' ),
				static::SETTING_CHECKBOX => array( 'themeeditor_setting_default' ),
				static::SETTING_IMAGE => array( 'themeeditor_setting_default' )
			)
		) ) );

		$form->add( new Number( 'themeeditor_setting_min', $this->data['min'] ?? null, false, array(), null, null, null, 'themeeditor_setting_min' ) );
		$form->add( new Number( 'themeeditor_setting_max', $this->data['max'] ?? null, null, array(), null, null, null, 'themeeditor_setting_max' ) );
		$form->add( new Number( 'themeeditor_setting_step', $this->data['step'] ?? 1, false, array(
			'decimals' => 2
		), null, null, null, 'themeeditor_setting_step' ) );

		$options = [];
		if( isset( $this->data['options'] ) )
		{
			foreach( $this->data['options'] as $opt )
			{
				$options[] = [ 'key' => $opt[0], 'value' => $opt[1] ];
			}
		}
		$form->add( new Form\Stack( 'themeeditor_setting_options', count( $options ) ? $options : null, null, array(
			'stackFieldType' => 'KeyValue'
		), null, null, null, 'themeeditor_setting_options' ) );

		$form->add( new Codemirror( 'themeeditor_setting_default', is_array( $this->default ) ? null : $this->default, false, array(
			'height' => 100
		), null, null, null, 'themeeditor_setting_default' ) );
		$form->add( new Codemirror( 'themeeditor_setting_light_default', is_array( $this->default ) ? $this->default['light'] : null, false, array(
			'height' => 100
		), null, null, null, 'themeeditor_setting_light_default' ) );
		$form->add( new Codemirror( 'themeeditor_setting_dark_default', is_array( $this->default ) ? $this->default['dark'] : null, false, array(
			'height' => 100
		), null, null, null, 'themeeditor_setting_dark_default' ) );

		$form->add( new YesNo( 'themeeditor_setting_refresh', $this->refresh, false ) );

		if( Dispatcher::i()->module->key == 'customization' )
		{
			$form->add( new Node( 'themeeditor_setting_app', $this->app ? Application::load( $this->app ) : Application::load( 'core' ), true, array(
				'class' => Application::class,
				'multiple' => false,
				'subnodes' => false
			) ) );
		}
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( !$this->id )
		{
			$this->save();
		}

		$return = [
			'setting_key' => $values['themeeditor_setting_key'],
			'setting_category_id' => ( $values['themeeditor_setting_category'] instanceof Category ) ? $values['themeeditor_setting_category']->id : 0,
			'setting_type' => $values['themeeditor_setting_type'],
			'setting_set_id' => Request::i()->set_id ?? 0,
			'setting_refresh' => (bool) $values['themeeditor_setting_refresh']
		];

		switch( $values['themeeditor_setting_type' ] )
		{
			case static::SETTING_COLOR:
				$return['setting_default'] = json_encode( array(
					'light' => $values['themeeditor_setting_light_default'],
					'dark' => $values['themeeditor_setting_dark_default']
				) );
				break;

			case static::SETTING_SELECT:
				$options = [];
				foreach( $values['themeeditor_setting_options'] as $opt )
				{
					$options[] = [ $opt['key'], $opt['value'] ];
				}
				$return['setting_data'] = array( 'options' => $options );
				$return['setting_default'] = $values['themeeditor_setting_default'];
				break;

			case static::SETTING_NUMBER:
				$return['setting_data'] = array(
					'min' => $values['themeeditor_setting_min'],
					'max' => $values['themeeditor_setting_max'],
					'step' => $values['themeeditor_setting_step']
				);
				$return['setting_default'] = $values['themeeditor_setting_default'];
				break;

			default:
				$return['setting_default'] = $values['themeeditor_setting_default'];
				break;
		}

		if( isset( $values['themeeditor_setting_app'] ) and $values['themeeditor_setting_app'] instanceof  Application )
		{
			$return['setting_app'] = $values['themeeditor_setting_app']->directory;
		}

		Lang::saveCustom( 'core', static::$titleLangPrefix . $this->key, $values['themeeditor_setting_name'] );
		Lang::saveCustom( 'core', static::$titleLangPrefix . $this->key . '_desc', $values['themeeditor_setting_desc'] );

		return $return;
	}
}