<?php

/**
 * @brief        Category
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
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Icon;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use const IPS\IN_DEV;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Category extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static array $multitons	= array();

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'cat_';

	/**
	 * @brief	[ActiveRecord] Database table
	 * @note	This MUST be over-ridden
	 */
	public static ?string $databaseTable	= 'core_theme_editor_categories';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 * @note	If using this, declare a static $multitonMap = array(); in the child class to prevent duplicate loading queries
	 */
	protected static array $databaseIdFields = array( 'cat_key' );

	/**
	 * @brief       [Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief       [Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = 'parent';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}" as the key
	 */
	public static ?string $titleLangPrefix = 'themeeditor_cat_';

	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = true;

	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'theme_editor_categories';

	/**
	 * @brief	[Node] Subnode class
	 */
	public static ?string $subnodeClass = Setting::class;

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
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe', the 'fa fa-' is added automatically so you do not need this here)
	 * @return	mixed
	 */
	protected function get__icon(): mixed
	{
		if( $this->icon )
		{
			$icon = json_decode( $this->icon, true );

			$suffix = substr( $icon['key'], -3 );
			switch( $suffix )
			{
				case 'fab':
					$icon['title'] = 'fa-brands fa-' . $icon['title'];
					break;
				case 'far':
					$icon['title'] = 'fa-regular fa-' . $icon['title'];
					break;
			}

			if( isset( $icon['extra'] ) )
			{
				$icon['title'] .= ' fa-' . $icon['extra'];
			}

			return $icon['title'];
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function icon() : string
	{
		if( $this->icon )
		{
			$icon = json_decode( $this->icon, true );
			$bits = explode( ":", $icon['key'] );
			$return = 'fa-';
			switch( $bits[1] )
			{
				case 'fas':
					$return .= 'solid';
					break;
				case 'far':
					$return .= 'regular';
					break;
				case 'fab':
					$return .= 'brands';
					break;
			}

			$return .= ' fa-' . $bits[0];
			if( isset( $icon['extra'] ) )
			{
				$return .= ' ' . $icon['extra'];
			}

			return $return;
		}

		return '';
	}

	/**
	 * Generate icon data from the icon string
	 *
	 * @param string $key
	 * @return string[]
	 */
	public static function buildIconData( string $key ) : array
	{
		$suffix = null;
		$icon = [
			'type' => 'fa',
			'raw' => "<i class='" . $key . "'></i>"
		];

		foreach( explode( "fa-", $key ) as $string )
		{
			$string = trim( $string );
			switch( $string )
			{
				case 'solid':
					$suffix = 'fas';
					break;
				case 'brands':
					$suffix = 'fab';
					break;
				case 'regular':
					$suffix = 'far';
					break;
				default:
					if( !isset( $icon['title'] ) or empty( $icon['title'] ) )
					{
						$icon['title'] = $string;
					}
					else
					{
						$icon['extra'] = $string;
					}
					break;
			}
		}
		$icon['key'] = $icon['title'] . ':' . ( $suffix ?? 'fas' );
		$icon['html'] = Theme::i()->getTemplate( 'global', 'core', 'global' )->icon( $icon );
		return $icon;
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

		if( $this->hasChildren( null, null, false ) or !$this->hasContents() )
		{
			$buttons['child'] = [
				'icon' => 'plus',
				'title' => 'themeeditor_cat_add',
				'link' => $url->setQueryString( array( 'do' => 'categoryForm', 'parent' => $this->id ) )
			];
		}

		/* Don't allow modification of 1st party settings if we're in theme designer mode */
		if( !IN_DEV and Settings::i()->theme_designer_mode and !$this->set_id )
		{
			unset( $buttons['edit'] );
			unset( $buttons['delete'] );
		}

		return $buttons;
	}

	/**
	 * [Node] Does the currently logged in user have permission to add a child node to this node?
	 *
	 * @return	bool
	 */
	public function canAdd(): bool
	{
		if( !parent::canAdd() )
		{
			return false;
		}

		if( $this->hasChildren( null, null, false ) )
		{
			return false;
		}

		return true;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Translatable( 'themeeditor_cat_name', null, true, array(
			'app' => 'core',
			'key' => ( $this->id ? 'themeeditor_cat_' . $this->key : null )
		) ) );

		$form->add( new Text( 'themeeditor_cat_key', $this->key, true, array(
			'regex' => '/[A-Z]+/i',
			'maxLength' => 150
		), function( $val ){
			try
			{
				$test = static::load( $val, 'cat_key' );
				if( $test->id != $this->id )
				{
					throw new InvalidArgumentException( "err__duplicate_cat_key" );
				}
			}
			catch( OutOfRangeException ){}
		}) );

		$iconData = $this->icon ? json_decode( $this->icon, true ) : [];
		$form->add( new Icon( 'themeeditor_cat_icon', count( $iconData ) ? array( $iconData ) : null, true, array(
			'emoji' => false,
			'maxIcons' => 1
		) ) );
		$form->add( new Text( 'themeeditor_cat_icon_extra', $iconData['extra'] ?? null, false ) );

		$form->add( new Node( 'themeeditor_cat_parent', $this->parent, false, array(
			'class' => Category::class,
			'subnodes' => false,
			'autoPopulate' => false,
			'multiple' => false,
			'permissionCheck' => function( $val ){
				if( $val instanceof Category and ( $val->hasSettings() or $val->hasColors() ) )
				{
					return false;
				}
				return true;
			}
		) ) );

		if( Dispatcher::i()->module->key == 'customization' )
		{
			$form->add( new Node( 'themeeditor_cat_app', $this->app ? Application::load( $this->app ) : Application::load( 'core' ), true, array(
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
			$this->key = $values['themeeditor_cat_key'];
			$this->save();
		}

		$icon = $values['themeeditor_cat_icon'][0];
		if( $values['themeeditor_cat_icon_extra'] )
		{
			$icon['extra'] = $values['themeeditor_cat_icon_extra'];
		}

		$return = [
			'cat_key' => $values['themeeditor_cat_key'],
			'cat_icon' => json_encode( $icon ),
			'cat_parent' => ( $values['themeeditor_cat_parent'] instanceof Model ? $values['themeeditor_cat_parent']->id : 0 ),
			'cat_set_id' => Request::i()->set_id ?? 0
		];

		if( isset( $values['themeeditor_cat_app'] ) and $values['themeeditor_cat_app'] instanceof Application )
		{
			$return['cat_app'] = $values['themeeditor_cat_app']->directory;
		}

		Lang::saveCustom( 'core', static::$titleLangPrefix . $this->key, $values['themeeditor_cat_name'] );

		return $return;
	}

	/**
	 * Does the category have anything in it?
	 *
	 * @return bool
	 */
	public function hasContents() : bool
	{
		return ( $this->hasChildren() or $this->hasSettings() or $this->hasColors() );
	}

	/**
	 * @var array|null
	 */
	protected ?array $_settings = null;

	/**
	 * Load category settings
	 *
	 * @return void
	 */
	protected function loadSettings() : void
	{
		if( $this->_settings === null )
		{
			$this->_settings = [];
			$this->_colors = [];
			foreach( Setting::loadByCategory( $this ) as $setting )
			{
				if( $setting->type == Setting::SETTING_COLOR )
				{
					$this->_colors[] = $setting;
				}
				else
				{
					$this->_settings[] = $setting;
				}
			}
		}
	}

	/**
	 * Return all settings in this category
	 *
	 * @return array
	 */
	public function settings() : array
	{
		$this->loadSettings();
		return $this->_settings;
	}

	/**
	 * Determines if this category has any settings
	 *
	 * @return bool
	 */
	public function hasSettings() : bool
	{
		return (bool) ( count( $this->settings() ) );
	}

	/**
	 * @var array|null
	 */
	protected ?array $_colors = null;

	/**
	 * Return all color settings in this category
	 *
	 * @return array
	 */
	public function colors() : array
	{
		$this->loadSettings();
		return $this->_colors;
	}

	/**
	 * Determines if this category has any color settings
	 *
	 * @return bool
	 */
	public function hasColors() : bool
	{
		return (bool) ( count( $this->colors() ) );
	}

	/**
	 * @return string
	 */
	public function editorHtml() : string
	{
		return ( $this->hasContents() ) ? Theme::i()->getTemplate( 'themeeditor', 'core', 'front' )->editorPanel( $this ) : '';
	}

	/**
	 * Return only the editor categories that should be visible in the
	 * theme editor. Some may be related to disabled apps,
	 * some may be related to another theme.
	 *
	 * @return array
	 */
	public static function themeEditorCategories() : array
	{
		$appKeys = array_keys( Application::enabledApplications() );

		$return = [];
		foreach( static::roots( null ) as $cat )
		{
			if( $cat->app and !in_array( $cat->app, $appKeys ) )
			{
				continue;
			}

			if( $cat->set_id and !in_array( $cat->set_id, array( Theme::$defaultFrontendThemeSet, Theme::i()->id ) ) )
			{
				continue;
			}

			$return[] = $cat;
		}
		return $return;
	}
}