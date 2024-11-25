<?php
/**
 * @brief		Skin Set
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Apr 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Diff;
use DomainException;
use DOMText;
use DOMXPath;
use ErrorException;
use Exception;
use InvalidArgumentException;
use IPS\cms\Pages\Page;
use IPS\Data\Store;
use IPS\Dispatcher\Front;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Theme\Editor\Setting;
use IPS\Theme\SandboxedTemplate;
use IPS\Theme\Dev\Theme as DevTheme;
use IPS\Theme\Setup\Theme as SetupTheme;
use IPS\Theme\TemplateException;
use IPS\Xml\DOMDocument;
use LogicException;
use OutOfRangeException;
use OverflowException;
use ParseError;
use UnderflowException;
use UnexpectedValueException;
use XMLReader;
use function array_combine;
use function array_merge;
use function count;
use function defined;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function get_called_class;
use function glob;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function mb_substr;
use function md5;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function rtrim;
use function str_replace;
use function stristr;
use function strlen;
use function strstr;
use function strtolower;
use function substr;
use function unlink;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Skin set
 */
class Theme extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_themes';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'set_';

	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('set_id', 'set_key', 'set_edit_in_progress');

	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();

	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'order';

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'menu__core_customization_themes';

	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = TRUE;

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'core_theme_set_title_';

	/**
	 * @brief	IN_DEV "theme"
	 */
	public static ?Theme $inDevTheme = NULL;

	/**
	 * @brief	Setup "theme"
	 */
	protected static Theme|SetupTheme|null $setupSkin = NULL;

	/**
	 * @brief	Admin "theme"
	 */
	public static Theme|DevTheme|null $adminSkin = NULL;

	/**
	 * @brief	Member's "theme"
	 */
	public static mixed $memberTheme = NULL;

	/**
	 * @brief	Have fetched all?
	 */
	protected static bool $gotAll = FALSE;

	/**
	 * @brief	[SkinSets] Stores the default theme set id
	 */
	public static int $defaultFrontendThemeSet = 0;

	/**
	 * @brief	[SkinSets] Templates already loaded and evald via getTemplate()
	 */
	public static array $calledTemplates = array();

	/**
	 * @brief	[SkinSets] Some CSS files are built from a directory to save on http requests. They are saved as {$location}_{$folder}.css (so front_responsive.css for example)
	 */
	protected static array $buildGrouping = array(
		'css'  => array(
			'core' => array(
				'global'=> array( 'framework' ),
				'admin' => array( 'core' )
				)
			)
		);

	/**
	 * @brief	Return type for getAllTemplates/getRawCss: Return all
	 */
	const RETURN_ALL = 1;

	/**
	 * @brief	Return type for getAllTemplates/getRawCss: Return groups and names as a tree
	 */
	const RETURN_BIT_NAMES = 2;

	/**
	 * @brief	Return type for getAllTemplates/getRawCss: Return groups and names as a tree with array of data without content
	 */
	const RETURN_ALL_NO_CONTENT = 4;

	/**
	 * @brief	Return type for getAllTemplates/getRawCss: Returns bit names as a flat array
	 */
	const RETURN_ARRAY_BIT_NAMES = 8;

	/**
	 * @brief	Return type for getAllTemplates/getRawCss: Uses DB if not IN_DEV, otherwise uses disk .phtml look up
	 */
	const RETURN_NATIVE = 16;

	/**
	 * @brief	Type for templates
	 */
	const TEMPLATES = 1;

	/**
	 * @brief	Type for CSS
	 */
	const CSS = 2;

	/**
	 * @brief	Type for Images
	 */
	const IMAGES = 4;

	/**
	 * @brief Bit option for theme settings
	 */
	const THEME_KEY_VALUE_PAIRS = 1;

	/**
	 * @brief Bit option for theme settings
	 */
	const THEME_ID_KEY = 2;

	/**
	 * @brief Bit option for theme settings
	 */
	const TEMPLATE_SETTINGS_DEFAULT = 4;

	/**
	 * @brief Bit option for getting css variables
	 */
	const CUSTOM_ONLY = 1;

	/**
	 * @brief Bit option to use the default values
	 */
	const FORCE_DEFAULT = 2;

	/**
	 * @brief Name of variable filename
	 */
	const CSS_VARIABLE_FILENAME = '1-2-settings.css';

	/**
	 * @brief	Disable the copy button - useful when the forms are very distinctly different
	 */
	public bool $noCopyButton = TRUE;

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'updatecount_themes' );

	/**
	 * @brief Save a few cycles not json_decoding constantly
	 */
	protected array $_layoutValues = [];

	/**
	 * Get currently logged in member's theme
	 *
	 * @return    static|SetupTheme|DevTheme
	 */
	public static function i(): static|SetupTheme|DevTheme
	{
		if ( Dispatcher::hasInstance() AND class_exists( '\IPS\Dispatcher', FALSE ) and Dispatcher::i()->controllerLocation === 'setup' )
		{
			if ( static::$setupSkin === NULL )
			{
				static::$setupSkin = new SetupTheme;
			}
			return static::$setupSkin;
		}
		else if (RECOVERY_MODE)
		{
			if ( static::$memberTheme === NULL )
			{
				foreach( static::themes() as $theme )
				{
					if ( ! $theme->isCustomized() )
					{
						static::$memberTheme = $theme;
						break;
					}
				}
			}

			/* Set Admin Theme */
			if ( static::$adminSkin === NULL )
			{
				static::$adminSkin = static::master();
			}

			/* Still here because all themes are customized? */
			if ( static::$memberTheme === NULL )
			{
				$newTheme = new Theme;
				$newTheme->permissions = Member::loggedIn()->member_group_id;
				$newTheme->save();

				Lang::saveCustom( 'core', "core_theme_set_title_" . $newTheme->id, "IPS Default" );

				static::$memberTheme = $newTheme;
			}

			return static::$memberTheme;
		}
		else if (IN_DEV)
		{
			if ( static::$inDevTheme === NULL )
			{
				static::$inDevTheme = new DevTheme();
				static::themes();

				/* Add in the default theme properties (_data array, etc) */
				$default = ( isset( static::$multitons[DEFAULT_THEME_ID] ) ) ? static::$multitons[DEFAULT_THEME_ID] : reset( static::$multitons );

				foreach( $default as $k => $v )
				{
					static::$inDevTheme->$k = $v;
				}
			}

			if ( Member::loggedIn()->isEditingTheme() )
			{
				try
				{
					static::$memberTheme = static::byEditingMember();
				}
				catch( Exception )
				{
					static::$memberTheme = static::load( static::defaultTheme() );
				}
			}

			return static::$inDevTheme;
		}
		else if ( Dispatcher::hasInstance() AND class_exists( '\IPS\Dispatcher', FALSE ) and Dispatcher::i()->controllerLocation === 'admin' )
		{
			if ( static::$adminSkin === NULL )
			{
				static::$adminSkin = static::master();
			}
			return static::$adminSkin;
		}
		else
		{
			if ( static::$memberTheme === NULL )
			{
				static::themes();

				$setId = NULL;
				if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation == 'front' )
				{
					$setId = static::currentThemeId();

					if ( $setId )
					{
						if( ! Request::i()->isAjax() and Session\Front::loggedIn() )
						{
							/* Not an ajax call, so reset theme_id */
							$setId = NULL;
							Session\Front::i()->setTheme(0);
						}
						else
						{
							try
							{
								if ( static::load( $setId )->canAccess() !== true )
								{
									$setId = NULL;
								}
							}
							catch ( OutOfRangeException $ex )
							{
								$setId = NULL;
							}
						}

					}
				}

				if ( ! $setId and Member::loggedIn()->skin and array_key_exists( Member::loggedIn()->skin, static::themes() ) )
				{
					$setId = Member::loggedIn()->skin;

					if ( static::load( $setId )->canAccess() !== true )
					{
						$setId = static::defaultTheme();

						/* Restore default theme for member */
						Member::loggedIn()->skin = $setId;

						if( Member::loggedIn()->member_id )
						{
							Member::loggedIn()->save();
						}
					}
				}
				else if ( ! $setId )
				{
					$setId = static::defaultTheme();
				}

				if ( Member::loggedIn()->isEditingTheme() )
				{
					try
					{
						static::$memberTheme = static::byEditingMember();
					}
					catch( Exception )
					{
						static::$memberTheme = static::load( $setId );
					}
				}
				else
				{
					static::$memberTheme = static::load( $setId );
				}
			}

			return static::$memberTheme;
		}
	}

	/**
	 * Get current theme id
	 *
	 * @return int|null
	 */
	public static function currentThemeId(): ?int
	{
		if ( Session\Front::loggedIn() )
		{
			return Session\Front::i()->getTheme();
		}

		return isset( Request::i()->cookie['theme'] ) ? Request::i()->cookie['theme'] : NULL;
	}

	/**
	 * Themes
	 *
	 * @return	array
	 */
	public static function themes(): array
	{
		if ( !static::$gotAll )
		{
			static::$gotAll = true;

			if ( isset( Store::i()->themes ) )
			{
				$rows = Store::i()->themes;
			}
			else
			{
				$rows = iterator_to_array( Db::i()->select( '*', 'core_themes', NULL, 'set_order' )->setKeyField('set_id') );
				Store::i()->themes = $rows;
			}

			foreach( $rows as $id => $theme )
			{
				if ( $theme['set_is_default'] )
				{
					static::$defaultFrontendThemeSet = $theme['set_id'];
				}

				static::$multitons[ $theme['set_id'] ] = static::constructFromData( $theme );
			}

			/* Load all theme settings upfront */
			Setting::loadAllKeys();
		}
		return static::$multitons;
	}

	/**
	 * Returns only the visible themes for the member
	 *
	 * @return array
	 */
	public static function getThemesWithAccessPermission(): array
	{
		$visibleThemes = array();
		foreach(Theme::roots() as $theme )
		{
			if ( $theme->canAccess() )
			{
				$visibleThemes[$theme->id] = $theme;
			}
		}

		return $visibleThemes;
	}

	/**
	 * Fetch the master theme object
	 *
	 * @return Theme|DevTheme
	 */
	public static function master(): Theme|DevTheme
	{
		static::themes();
		return static::$multitons[ static::$defaultFrontendThemeSet ];
	}

	/**
	 * Default Frontend Skin Set ID
	 *
	 * @return	int
	 */
	public static function defaultTheme(): int
	{
		if ( !static::$gotAll )
		{
			static::themes();
		}

		return static::$defaultFrontendThemeSet;
	}

	/**
	 * Switches the currently initialized theme during execution
	 *
	 * @param int $themeId        Id of the theme to switch to
	 * @param boolean $persistent		Allow to persist through ajax calls
	 * @return  boolean
	 * @note    This will not check to ensure the member has permission to view the theme
	 */
	public static function switchTheme( int $themeId, bool $persistent=TRUE ): bool
	{
		static::themes();

		try
		{
			/* Make sure the member theme is set */
			Theme::i();

			$class = static::i();
			static::$memberTheme = $class::load( $themeId );

			/* Store the ID in sessions so ajax loads correct theme */
			if ( $persistent )
			{
				Session\Front::i()->setTheme( $themeId );
			}

			/* Flush loaded CSS */
			Output::i()->cssFiles = array();

			Front::baseCss();

			/* App CSS */
			$app = Front::i()->application;
			$app::outputCss();
		}
		catch( OutOfRangeException $e )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @brief Prevent CSS from being loaded more than once
	 */
	protected static array $usedCss = array();

	/**
	 * Get CSS
	 * This method is used to return the built CSS stored in the file objects system
	 *
	 * @param string $file		Filename
	 * @param string|null $app		Application
	 * @param string|null $location	Location (e.g. 'admin', 'front')
	 * @return	array		URLs to CSS files
	 */
	public function css( string $file, string $app=NULL, string $location=NULL ): array
	{
		$app      = $app      ?: Dispatcher::i()->application->directory;
		$location = $location ?: Dispatcher::i()->controllerLocation;
		$paths    = explode( '/', $file );
		$name     = array_pop( $paths );
		$path     = ( count( $paths ) ) ? implode( '/', $paths ) : '.';

		$cacheKey = $file . ',' . $app . ',' . $location . ',' . $this->_id;

		if ( isset( static::$usedCss[ $cacheKey ] ) )
		{
			return static::$usedCss[ $cacheKey ];
		}

		if ( $location === 'interface' )
		{
			static::$usedCss[ $cacheKey ] = array( rtrim( Url::baseUrl( Url::PROTOCOL_RELATIVE ), '/' ) . "/applications/{$app}/interface/{$file}" );
			return static::$usedCss[ $cacheKey ];
		}

		$key = static::cssFilePrefix( $app, $location, $path );

		if( in_array( $app, IPS::$ipsApps ) AND $file != 'custom.css' )
		{
			static::$usedCss[ $cacheKey ] = array( rtrim( Url::baseUrl( Url::PROTOCOL_RELATIVE ), '/' ) . "/static/css/" . $key . "_{$name}" );
			return static::$usedCss[ $cacheKey ];
		}

		/* We use a different format for when we compile CSS outside first party static files */
		$cssMapKey = static::makeBuiltTemplateLookupHash( $app, $location, str_contains( $file, '/' ) ? $file : './' . $file );

		if( array_key_exists( $cssMapKey, $this->css_map ) )
		{
			if ( $this->css_map[ $cssMapKey ] !== null )
			{
				if( !in_array( $app, IPS::$ipsApps ) or $file == 'custom.css' )
				{
					static::$usedCss[ $cacheKey ] = array( File::get( 'core_Theme', $this->css_map[ $cssMapKey ] )->url );
				}
			}
			else
			{
				static::$usedCss[ $cacheKey ] = array();
			}

			return static::$usedCss[ $cacheKey ];
		}
		else
		{
			/* We're setting up, do nothing to avoid compilation requests when tables are incomplete */
			if ( ! isset( Settings::i()->setup_in_progress ) OR Settings::i()->setup_in_progress )
			{
				/* Do not store a cache here as this will break later when we want CSS */
				//return array();
				// If we don't let the CSS rebuild here as needed then the AdminCP upgrader can result in a completely unstyled page when interrupted
			}

			/* Map doesn't exist, try and create it */
			if ( $this->compileCss( $app, $location, $path, $name ) === NULL )
			{
				/* Do not store a cache here as this will break later when we want CSS */
				return array();
			}

			/* Still not here? Then add a key but as null to prevent it from attempting to rebuild on every single page
			 * load thus hitting the DB multiple times */
			$cssMap = $this->css_map;
			if ( ! in_array( $cssMapKey, array_keys( $this->css_map ) ) )
			{
				$cssMap[ $cssMapKey ] = null;

				$this->css_map = $cssMap;
				$this->save();
			}
			else
			{
				if( in_array( $app, IPS::$ipsApps ) AND $file != 'custom.css' )
				{
					static::$usedCss[ $cacheKey ] = array( "/static/css/". $key . "_{$name}" );
				}
				else
				{
					try
					{
						static::$usedCss[ $cacheKey ] = array( File::get( 'core_Theme', $this->css_map[ $cssMapKey ] )->url );
					}
					catch( Exception )
					{
						static::$usedCss[ $cacheKey ] = array();
					}
				}

				return static::$usedCss[ $cacheKey ];
			}
		}

		return array();
	}

	/**
	 * Returns a human brain digestable unique prefix for CSS files
	 *
	 * @param string $app
	 * @param string $location
	 * @param string $path
	 * @return	string	Md5 Key
	 */
	public static function cssFilePrefix( string $app, string $location, string $path ): string
	{
		return str_replace( '/', '-', mb_strtolower( $app ) . '_' . mb_strtolower( $location ) . ( ( $path and $path != '.' ) ? '_' . mb_strtolower( $path ) : '' ) );
	}

	/**
	 * Resets the hook point flags in the core_theme_templates database
	 *
	 * @param string|null $app
	 * @return void
	 */
	public static function rebuildHookPointFlags( string $app=null ): void
	{
		/* Reset flags */
		$where = $app ? [ 'template_app=?', $app ] : [];
		Db::i()->update( 'core_theme_templates', [ 'template_has_hookpoints' => 0 ], $where );

		/* We might need to get more fancy at some point but now that no one can edit templates, it's unlikely data-ips-hook= will be used in any other way.
		   If it becomes a problem, we can simply iterate over and use xpath to check for reals. */
		Db::i()->update( 'core_theme_templates', [ 'template_has_hookpoints' => 1 ], [ array_merge( $where, Db::i()->like( 'template_content', ' data-ips-hook=', FALSE, TRUE, TRUE ) ) ] );
	}

	/**
	 * Get Theme Resource (image, font, theme-specific JS, etc)
	 *
	 * @param string $path		Path to resource
	 * @param string|null $app		Application key
	 * @param string|null $location	Location
	 * @return    Url|string|NULL		URL to resource
	 */
	public function resource( string $path, string $app=NULL, string $location=NULL, $noProtocol=FALSE ): Url|string|null
	{
		$app      = $app      ?: Dispatcher::i()->application->directory;
		$location = $location ?: Dispatcher::i()->controllerLocation;
		$paths    = explode( '/', $path );
		$name     = array_pop( $paths );
		$path     = ( count( $paths ) ) ? ( '/' . implode( '/', $paths ) . '/' ) : '/';
		$key      = static::makeBuiltTemplateLookupHash($app, $location, $path) . '_' .$name;

		if ( $location === 'interface' )
		{
			return Url::internal( "applications/{$app}/interface{$path}{$name}", 'interface', NULL, array(), Url::PROTOCOL_RELATIVE );
		}

		/* Are these core IPS apps? If so, we store these in the static directory and won't be in the resource map */
		if( in_array( $app, IPS::$ipsApps ) and str_replace( '/', '', $path ) !== 'custom' )
		{

			$url = Url::internal( 'static/resources/' . $app . '_' . static::makeBuiltTemplateLookupHash($app, $location, $path) . '_' .$name, 'static' );

			if( $noProtocol )
			{
				$url = $url->setScheme(NULL);
			}

			return $url;
		}


		if ( in_array( $key, array_keys( $this->resource_map ) ) )
		{
			if ( $this->resource_map[ $key ] === NULL )
			{
				return NULL;
			}
			else
			{
				$url = File::get( 'core_Theme', $this->resource_map[ $key ] )->url;

				if( $noProtocol and $url instanceof Url)
				{
					$url = $url->setScheme(NULL);
				}

				return $url;
			}
		}

		/* Still here? Map doesn't exist, try and create it */
		$resourceMap = $this->resource_map;

		try
		{
			/* We're setting up, do nothing to avoid compilation requests when tables are incomplete */
			if ( ! isset( Settings::i()->setup_in_progress ) OR Settings::i()->setup_in_progress )
			{
				return NULL;
			}

			$flagKey  = 'resource_compiling_' . $this->_id . '_' . md5( $key );
			if ( static::checkLock( $flagKey ) )
			{
				return NULL;
			}

			static::lock( $flagKey );

			$resource = Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? AND resource_app=? AND resource_location=? AND resource_path=? AND resource_name=?', $this->id, $app, $location, $path, $name ) )->first();

			$resourceMap[ $key ] = (string) File::create( 'core_Theme', $key, $resource['resource_data'], 'set_resources_' . $this->id, TRUE, NULL, FALSE );
			Db::i()->update( 'core_theme_resources', array( 'resource_added' => time(), 'resource_filename' => $resourceMap[ $key ] ), array( 'resource_id=?', $resource['resource_id'] ) );

			/* Save map */
			$this->resource_map = $resourceMap;
			$this->save();

			static::unlock( $flagKey );

			if( in_array( $app, IPS::$ipsApps ) )
			{
				$url = rtrim( Settings::i()->base_url, '/' ) . '/' . $resourceMap[ $key ];
			}
			else
			{
				$url = File::get( 'core_Theme', $resourceMap[ $key ] )->url;
			}

			if( $noProtocol and $url instanceof Url)
			{
				$url = $url->setScheme(NULL);
			}

			return $url;
		}
		catch( UnderflowException $e )
		{
			/* Doesn't exist, add null entry to map to prevent it from being rebuilt on each page load */
			$resourceMap[ $key ] = null;

			/* Save map */
			$this->resource_map = $resourceMap;
			$this->save();

			return NULL;
		}
	}

	/**
	 * Get a template
	 *
	 * @param string $group Template Group
	 * @param string|null $app Application key (NULL for current application)
	 * @param string|null $location Template Location (NULL for current template location)
	 * @return    mixed
	 * @throws    UnexpectedValueException
	 * @throws ErrorException
	 */
	public function getTemplate( string $group, string $app=NULL, string $location=NULL ): mixed
	{
		/* Do we have an application? */
		if( $app === NULL )
		{
			$app = Dispatcher::i()->application->directory;
		}

		/* How about a template location? */
		if( $location === NULL )
		{
			$location = Dispatcher::i()->controllerLocation;
		}

		$hashedKey = strtolower( static::makeBuiltTemplateLookupHash( $app, $location, $group ) . '_' . static::cleanGroupName( $group ) );
		$key = 'template_' . $this->id . '_' . $hashedKey;

		$cachedObject = NULL;
		$class = "\\IPS\\Theme\\" . 'class_' . $app . '_' . $location . '_' . $group;

		/* First-party apps should be pre-compiled */
		if( in_array( $app, IPS::$ipsApps ) AND !IN_DEV )
		{
			$templateFile = ROOT_PATH . '/static/templates/' . $app . '_' . $location . '_' . $group . '.php';
			if( file_exists( $templateFile ) )
			{
				require_once( $templateFile );
				return new SandboxedTemplate( new $class( $app, $location, $group ) );
			}
		}

		/* We cannot use isset( static::$calledTemplates[ $key ] ) here because it fails with NULL while in_array does not */
		if ( !in_array( $hashedKey, array_keys( static::$calledTemplates ) ) )
		{
			/* If we don't have a compiled template, do that now */
			if ( ! $cachedObject and !isset( Store::i()->$key ) )
			{
				/* It can take a few seconds for templates to finish compiling if initiated elsewhere, so let's try a few times sleeping 1 second between attempts
				   to give the compilation time to finish */
				$attempts = 0;
				while( $attempts < 6 )
				{
					if ( $attempts === 5 )
					{
						/* Rebuild in progress */
						Log::debug( "Template store key: {$key} rebuilding and requested again ({$app}, {$location}, {$group})", "template_store_building" );

						/* Since we can't do anything else, this ends up just being an uncaught exception - show the error page right away to avoid the unnecessary logging */
						IPS::genericExceptionPage();
					}

					$built = $this->compileTemplates( $app, $location, $group );

					if ( $built === NULL )
					{
						$attempts++;
						sleep(1);
					}
					else
					{
						break;
					}
				}

				/* Still no key? */
				if ( ! isset( Store::i()->$key ) )
				{
					Log::log( "Template store key: {$key} missing ({$app}, {$location}, {$group})", "template_store_missing" );

					throw new ErrorException( 'template_store_missing' );
				}
			}

			/* Load compiled template */
			if ( $cachedObject )
			{
				/* Init */
				static::$calledTemplates[ $hashedKey ] = new SandboxedTemplate( $cachedObject );
			}
			else
			{
				$compiledGroup = Store::i()->$key;

				if (DEBUG_TEMPLATES)
				{
					static::runDebugTemplate( $key, $compiledGroup );
				}
				else
				{
					try
					{
						if ( @eval( $compiledGroup ) === FALSE )
						{
							throw new TemplateException( 'Invalid Template', 1000, NULL, array( 'group' => $group, 'app' => $app, 'location' => $location ), $this );
						}
					}
					catch ( ParseError $e )
					{
						throw new UnexpectedValueException;
					}
				}

				/* Init */
				static::$calledTemplates[ $hashedKey ] = new SandboxedTemplate( new $class( $app, $location, $group ) );
			}
		}

		return static::$calledTemplates[ $hashedKey ];
	}

	/**
	 * Determine the path to load the file from
	 *
	 * @param string $file	File path
	 * @return	string
	 */
	public static function getHookPath( string $file ): string
	{
		$path = ROOT_PATH;
		if ( CIC2 AND IPS::isThirdParty( $file ) )
		{
			$path = SITE_FILES_PATH;
		}

		return $path;
	}

	/*! Active Record */

	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    ActiveRecord
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): ActiveRecord
	{
		$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );

		/* Extra set up */
		$logo = json_decode( $obj->logo_data, true );

		$obj->_data['logo'] = array( 'front' => null, 'front-dark' => null, 'mobile' => null, 'mobile-dark' => null );

		if ( is_array( $logo ) )
		{
			foreach( [ 'front', 'front-dark', 'mobile', 'mobile-dark' ] as $type )
			{
				if ( isset( $logo[$type] ) )
				{
					$obj->_data['logo'][$type] = $logo[$type];
				}
			}
		}

		if ( $editorData = json_decode( $obj->_data['theme_editor_data'], true ) )
		{
			$obj->_data['theme_editor_data'] = $editorData;

			if ( ! isset( $obj->_data['theme_editor_data']['header'] ) )
			{
				$obj->_data['theme_editor_data']['header'] = [
					'logo' => 4,
					'navigation' => 5,
					'user' => 6,
					'breadcrumb' => 7,
					'search' => 9,
				];
			}
		}
		else
		{
			/* Set up defaults */
			$obj->_data['theme_editor_data'] = [ 'header' => [
				'logo' => 4,
				'navigation' => 5,
				'user' => 6,
				'breadcrumb' => 7,
				'search' => 9,
			] ];
		}

		if ( ! is_array( $obj->_data['resource_map'] ) )
		{
			if( $obj->id > 0 )
			{
				if ( $imgMap = json_decode( $obj->_data['resource_map'], true ) )
				{
					$obj->_data['resource_map'] = $imgMap;
				}
				else
				{
					$obj->_data['resource_map'] = array();
				}
			}
			else
			{
				if( !isset( Store::i()->acp_resource_map ) )
				{
					$obj->_data['resource_map'] = [];
				}
				else
				{
					$obj->_data['resource_map'] = Store::i()->acp_resource_map;
				}
			}
		}

		if ( ! is_array( $obj->_data['css_map'] ) )
		{
			if ( $obj->id > 0 )
			{
				if ( $cssMap = json_decode( $obj->_data['css_map'], true ) )
				{
					$obj->_data['css_map'] = $cssMap;
				}
				else
				{
					$obj->_data['css_map'] = array();
				}
			}
			else
			{
				$obj->_data['css_map'] = json_decode( Settings::i()->acp_css_map, true );
			}
		}

		return $obj;
	}

	/**
	 * Save resource map
	 *
	 * @param	$value        array    Value to save
	 * @return void
	 */
	public function set_resource_map( array $value ) : void
	{
		$this->_data['resource_map'] = json_encode( $value );

		if ( $this->id === 0 )
		{
			Store::i()->acp_resource_map = $value;
		}
	}

	/**
	 * Save CSS map
	 *
	 * @param	$value        array    Value to save
	 */
	public function set_css_map( array $value ) : void
	{
		$this->_data['css_map'] = json_encode( $value );

		if ( $this->id === 0 )
		{
			Settings::i()->changeValues( array( 'acp_css_map' => json_encode( $value ) ) );
		}
	}

	/**
	 * Make sure we clean out the custom CSS to prevent anyone adding closing style tags
	 *
	 * @param string $value
	 * @return void
	 */
	public function set_custom_css( string $value ) : void
	{
		$value = str_replace( '</style>', '', $value );
		$this->_data['custom_css'] = $value;

		$key = $this->_id . '_theme_editor_custom_css';
		if ( isset( Store::i()->$key ) )
		{
			unset( Store::i()->$key );
		}
	}

	/**
	 * Return the CSS, compiled, from the cache
	 *
	 * @return string
	 */
	public function getCustomCssForOutput(): string
	{
		$key = $this->_id . '_theme_editor_custom_css';
		if ( ! isset( Store::i()->$key ) )
		{
			$functionName = "css_" . uniqid();
			static::makeProcessFunction( static::fixResourceTags( (string) $this->custom_css, 'front' ), $functionName, '', FALSE, TRUE );

			$fqFunc		= 'IPS\\Theme\\'. $functionName;
			$content	= static::minifyCss( $fqFunc() );

			/* Replace any <fileStore.xxx> tags in the CSS */
			Output::i()->parseFileObjectUrls( $content );

			Store::i()->$key = $content;
		}

		return Store::i()->$key;
	}

	/**
	 * Add in the header and footer special tags if they are not already present in globalTemplate
	 *
	 * @return void
	 */
	public function fixHeaderAndFooterTags(): void
	{
		/* Check the globalTemplate for the special tags we need */
		try
		{
			$template = Db::i()->select( '*', 'core_theme_templates', [ 'template_set_id=? AND template_app=? AND template_group=? AND template_location=? and template_name=?', $this->_id, 'core', 'global', 'front', 'globalTemplate'] )->first();
			$update = FALSE;

			if ( ! stristr( $template['template_content'], '{theme="headerHtml"}') )
			{
				$template['template_content'] = preg_replace( '#(<body.*>\n)#i', "$1\n{theme=\"headerHtml\"}\n", $template['template_content'] );
				$update = TRUE;
			}

			if ( ! stristr( $template['template_content'], '{theme="footerHtml"}') )
			{
				$template['template_content'] = preg_replace( '#(</body>\n)#i', "{theme=\"footerHtml\"}\n$1", $template['template_content'] );
				$update = TRUE;
			}

			if ( $update )
			{
				Db::i()->update( 'core_theme_templates', [ 'template_content' => $template['template_content'] ], [ 'template_id=?', $template['template_id'] ] );
			}

		}
		catch( UnderflowException $e )
		{
			/* No custom template, so we're good */
		}
	}

	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	function save(): void
	{
		parent::save();
		unset( Store::i()->themes );

		/* If we are using a customized globalTemplate, we need to slap in the header/footer tags */
		if ( ! empty( $this->custom_footer ) or ! empty( $this->customer_header ) )
		{
			$this->fixHeaderAndFooterTags();
		}

		foreach( [ 'header', 'footer' ] as $type )
		{
			$key = 'custom_' . $type . '_' . $this->_id;
			if ( isset( Store::i()->$key ) )
			{
				unset( Store::i()->$key );
			}
		}

		/* Reset map arrays */
		if ( !isset( $this->_data['resource_map'] ) OR  ! is_array( $this->_data['resource_map'] ) )
		{
			if ( isset( $this->_data['resource_map'] ) AND $imgMap = json_decode( $this->_data['resource_map'], true ) )
			{
				$this->_data['resource_map'] = $imgMap;
			}
			else
			{
				$this->_data['resource_map'] = array();
			}
		}

		if ( !isset( $this->_data['css_map'] ) OR ! is_array( $this->_data['css_map'] ) )
		{
			if ( isset( $this->_data['css_map'] ) AND $cssMap = json_decode( $this->_data['css_map'], true ) )
			{
				$this->_data['css_map'] = $cssMap;
			}
			else
			{
				$this->_data['css_map'] = array();
			}
		}
	}

	/*! Node */

	/**
	 * Get header logo
	 *
	 * @return    Url|string
	 */
	public function get_logo_front(): Url|string
	{
		return $this->logoImage( 'front' );
	}

	/**
	 * Return logo image
	 *
	 * @param string $type	Type of logo image
	 * @return    Url|string
	 */
	protected function logoImage( string $type ): Url|string
	{
		if( isset( Theme::i()->logo[ $type ]['url'] ) )
		{
			try
			{
				return File::get( 'core_Theme', Theme::i()->logo[ $type ]['url'] )->url;
			}
			catch( Exception $e )
			{
				return '';
			}
		}

		return '';
	}

	/**
	 * [Node] Get Description
	 *
	 * @return	string|null
	 */
	protected function get__description(): ?string
	{
		return Theme::i()->getTemplate( 'customization', 'core' )->themeDescription( $this );
	}

	/**
	 * Get the authors website
	 *	 *
	 * @return Url|null
	 */
	public function website(): ?Url
	{
		if ( $this->_data['author_url'] )
		{
			return Url::createFromString( $this->_data['author_url'] );
		}
		return NULL;
	}

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge(): ?array
	{
		/* Is there an update to show? */
		$badge	= NULL;

		if ( $this->is_default )
		{
			$message = 'default_no_parenthesis';

			$badge	= array(
				0	=> 'positive',
				1	=> $message
			);
		}

		if ( $this->update_data )
		{
			$data	= json_decode( $this->update_data, TRUE );

			if( !empty($data['longversion']) AND $data['longversion'] > $this->long_version )
			{
				$released	= NULL;

				if( $data['released'] AND intval($data['released']) == $data['released'] AND strlen($data['released']) == 10 )
				{
					$released	= DateTime::ts( $data['released'] )->localeDate();
				}
				else if( $data['released'] )
				{
					$released	= $data['released'];
				}

				$badge	= array(
						0	=> 'new',
						1	=> '',
						2	=> Theme::i()->getTemplate( 'global', 'core' )->updatebadge( $data['version'], $data['updateurl'], $released )
				);
			}
		}

		return $badge;
	}

	/**
	 * [Node] Clone the theme set
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$originalId = $this->_data['id'];
		$title      = Member::loggedIn()->language()->get( static::$titleLangPrefix . $originalId );

		/* Unset custom properties */
		foreach( array( 'resource_map', 'css_map', 'logo', 'name_translated', 'title' ) as $f )
		{
			unset( $this->_data[ $f ] );
		}
		$this->is_default = FALSE;

		parent::__clone();

		/* Dynamically produce insert list, so we don't have to update each time the table changes */
		$templateTable  = Db::i()->getTableDefinition( 'core_theme_templates', TRUE );
		$cssTable       = Db::i()->getTableDefinition( 'core_theme_css', TRUE );
		$templateFields = array_keys( (array)$templateTable['columns'] );
		$cssFields      = array_keys( (array)$cssTable['columns'] );

		array_walk( $templateFields, function( &$name, $i, $setId )
		{
			switch( $name )
			{
				case 'template_id':
					$name = 'null';
					break;
				case 'template_set_id':
					$name = $setId;
					break;
			}
		}, $this->id );

		array_walk( $cssFields, function( &$name, $i, $setId )
		{
			switch( $name )
			{
				case 'css_id':
					$name = 'null';
					break;
				case 'css_set_id':
					$name = $setId;
					break;
			}
		}, $this->id );

		/* Insert new language bit */
		Lang::saveCustom( 'core', "core_theme_set_title_" . $this->id, sprintf( Member::loggedIn()->language()->get( 'theme_clone_copy_of' ), $title ) );

		/* Copy across any template bits */
		Db::i()->insert( 'core_theme_templates', Db::i()->select( implode(',', $templateFields ), 'core_theme_templates', array( 'template_set_id=?', $originalId ) ) );

		/* Copy across any CSS bits */
		Db::i()->insert( 'core_theme_css', Db::i()->select( implode(',', $cssFields ), 'core_theme_css', array( 'css_set_id=?', $originalId ) ) );

		/* Copy any language keys */
		foreach(Db::i()->select( '*', 'core_sys_lang_words', array( 'word_theme=?', $originalId ) ) AS $word )
		{
			$word['word_theme'] = $this->id;
			unset( $word['word_id'] );
			Db::i()->insert( 'core_sys_lang_words', $word );
		}

		/* Copy across resources */
		$this->copyResourcesFromSet( $originalId );

		/* Make sure data objects are loaded correctly */
		static::$gotAll = false;

		/* Save css/img maps */
		Theme::load( $this->id )->saveSet();

		/* Copy logos */
		$this->logo_data = NULL;
		$this->copyLogosFromSet( $originalId );

		Session::i()->log( 'acplogs__themeset_created', array( sprintf( Member::loggedIn()->language()->get( 'theme_clone_copy_of' ), Member::loggedIn()->language()->get( 'core_theme_set_title_' . $originalId ) ) => FALSE ) );
	}

	/**
	 * [Node] Does the currently logged-in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		if ( IN_DEV AND $this->_id == DEFAULT_THEME_ID)
		{
			return FALSE;
		}

		if( $this->is_default )
		{
			return FALSE;
		}

		foreach( $this->children( NULL ) as $childTheme )
		{
			if( $childTheme->is_default )
			{
				return FALSE;
			}
		}

		return parent::canDelete();
	}

	/**
	 * [Node] Delete the theme set
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		if ( IN_DEV AND $this->_id == DEFAULT_THEME_ID)
		{
			Output::i()->error( 'theme_error_not_available_in_dev', '2S140/1', 403, '' );
		}

		if ( $this->is_default )
		{
			Output::i()->error( 'core_theme_cannot_delete_default_theme', '2S162/1', 403, '' );
		}

		/* Clear out existing built bits */
		File::getClass('core_Theme')->deleteContainer( 'css_built_' . $this->_id );
		File::getClass('core_Theme')->deleteContainer( 'set_resources_' . $this->_id );

		$templates = $this->getAllTemplates();

		foreach( $templates as $app => $v )
		{
			foreach( $v as $location => $groups )
			{
				foreach( $v[ $location ] as $group => $bits )
				{
					foreach( $v[ $location ][ $group ] as $name => $data )
					{
						/* Store it */
						$key = strtolower( 'template_' . $this->_id . '_' .static::makeBuiltTemplateLookupHash( $app, $location, $group ) . '_' . static::cleanGroupName( $group ) );

						unset( Store::i()->$key );
					}
				}
			}
		}

		Db::i()->delete( 'core_theme_resources', array( 'resource_set_id=?', $this->_id ) );
		Db::i()->delete( 'core_theme_css', array( 'css_set_id=?', $this->_id ) );
		Db::i()->delete( 'core_theme_templates', array( 'template_set_id=?', $this->_id ) );
		Db::i()->delete( 'core_sys_lang_words', array( 'word_theme=?', $this->_id ) );

		/* Delete theme editor settings */
		Db::i()->delete( 'core_theme_editor_settings', [ 'setting_set_id=?', $this->_id ] );

		/* Check the categories. If there are settings from another app, then we leave the category,
		and change the application key */
		$categories = iterator_to_array(
			Db::i()->select( '*', 'core_theme_editor_categories', [ 'cat_set_id=?', $this->_id ] )
		);

		foreach( $categories as $category )
		{
			try
			{
				$otherSetting = Db::i()->select( '*', 'core_theme_editor_settings', [ 'setting_category_id=? and setting_set_id != ?', $category['cat_id'], $this->_id ], 'setting_position', array( 0, 1 ) )->first();
				Db::i()->update( 'core_theme_editor_categories', [ 'cat_set_id' => 0 ], [ 'cat_id=?', $category['cat_id'] ] );
			}
			catch( UnderflowException )
			{
				Db::i()->delete( 'core_theme_editor_categories', [ 'cat_id=?', $category['cat_id'] ] );
			}
		}

		/** reset member skin  */
		Db::i()->update( 'core_members', array( 'skin' => 0 ), array('skin=?', $this->_id ) );

		parent::delete();
		unset( Store::i()->themes );
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param Form $form The form
	 * @return    void
	 * @throws Exception
	 */
	public function form( Form &$form ): void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'theme_sets_manage' );

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customization/themes.css', 'core', 'admin' ) );
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_customization.js', 'core', 'admin' ) );
		$form->attributes[ 'data-controller' ] = 'core.admin.customization.themeForm';

		/* General */
		if ( $this->id )
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('core_theme_editing_set', FALSE, array( 'sprintf' => array( $this->_title ) ) );

			$form->addTab( 'theme_set_tab__general' );

			$groups = array();
			foreach( Group::groups() as $group )
			{
				if ( $group->g_bitoptions['gbw_change_layouts'] )
				{
					$groups[] = Theme::i()->getTemplate( 'customization' )->groupLink( $group->g_id, $group->name );
				}
			}

			$form->addHtml( Theme::i()->getTemplate( 'customization' )->layoutPermissionBlurb( $groups ) );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('theme_set_add_button');
			$form->addTab( 'theme_set_tab__new_custom_set' );
		}

		$form->add( new Translatable( 'core_theme_set_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? "core_theme_set_title_{$this->id}" : NULL ) ) ) );

		$id = $this->id;
		$form->add( new YesNo( 'core_theme_set_is_default' , $this->is_default, false, array( 'togglesOff'  => array('core_theme_set_permissions') ), function( $val ) use ( $id )
		{
			$where = array( array( 'set_is_default=1' ) );
			if ( $id )
			{
				$where[] = array('set_id<>?', $id );
			}

			if ( !$val and !Db::i()->select( 'COUNT(*)', 'core_themes', $where )->first() )
			{
				throw new DomainException('core_theme_set_is_default_error');
			}
		} ) );

		$form->add( new CheckboxSet(
				'core_theme_set_permissions',
				( $this->id ) ? ( $this->permissions === '*' ? '*' : explode( ",", $this->permissions ) ) : '*',
				FALSE,
				array( 'options' => Group::groups(), 'multiple' => TRUE, 'parse' => 'normal', 'unlimited' => '*', 'unlimitedLang' => 'all', 'impliedUnlimited' => TRUE ),
				NULL,
				NULL,
				NULL,
				'core_theme_set_permissions'
		) );

		if (IN_DEV)
		{
			$form->add( new Text( 'theme_template_export_author_name', $this->author_name, false ) );
			$form->add( new Text( 'theme_template_export_author_url' , $this->author_url, false ) );
			$form->add( new Text( 'theme_update_check' , $this->update_check, false ) );

			$form->add( new Text( 'theme_template_export_version'        , $this->version ? $this->version : '1.0'    , true, array( 'placeholder' => '1.0.0' ) ) );
			$form->add( new Number( 'theme_template_export_long_version' , $this->long_version ?: 10000, true ) );
		}

		/* Header, footer and custom CSS */
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'customization', 'theme_templates_manage' ) )
		{
			$form->addTab('core_theme_set_header_and_footer_header');
			$form->add( new Codemirror( 'theme_simple_header_settings', $this->custom_header, FALSE, [ 'codeModeAllowedLanguages' => [ 'ipsphtml' ], 'height' => 600 ], function( $val ) {
				try
				{
					static::makeProcessFunction( $val, 'template_' . md5( mt_rand() . time() ), '', TRUE, FALSE );
				}
				catch( ParseError $e )
				{
					throw new DomainException( 'core_theme_template_parse_error' );
				}
			}, NULL, NULL, 'theme_header' ) );
			$form->add( new Codemirror( 'theme_simple_footer_settings', $this->custom_footer, FALSE, [ 'codeModeAllowedLanguages' => [ 'ipsphtml' ], 'height' => 600 ], function( $val ) {
				try
				{
					static::makeProcessFunction( $val, 'template_' . md5( mt_rand() . time() ), '', TRUE, FALSE );
				}
				catch( ParseError $e )
				{
					throw new DomainException( 'core_theme_template_parse_error' );
				}
			}, NULL, NULL, 'theme_footer' ) );
		}

		$form->canSaveAndReload = true;
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @param Url $url		Base URL
	 * @param bool $subnode	Is this a subnode?
	 * @return    array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ): array
	{
		$parentButtons = array();
		$buttons       = array();

		foreach( parent::getButtons( $url, $subnode ) as $button )
		{
			$parentButtons[ $button['title'] ] = $button;
		}

		unset( $parentButtons['edit']['data']['ipsDialog'], $parentButtons['edit']['data']['ipsDialog-title'] );

		$buttons['edit'] = $parentButtons['edit'];

		$buttons['resources'] = array(
			'icon'	=> 'file-image',
			'title'	=> 'theme_set_manage_resources',
			'link'	=> Url::internal( "app=core&module=customization&controller=media&set_id={$this->_id}" ),
			'data'	=> array()
		);

		$buttons['copy'] = $parentButtons['copy'];

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'customization', 'theme_download_upload' ) AND !DEMO_MODE)
		{
			$buttons['upload'] = array(
					'icon' => 'upload',
					'title' => 'theme_set_import',
					'link' => Url::internal( "app=core&module=customization&controller=themes&do=importForm&id={$this->_id}" ),
					'data' => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'theme_set_import_title', FALSE, array( 'sprintf' => array( $this->_title ) ) ) )
			);

			$buttons['download'] = array(
					'icon'	=> 'download',
					'title'	=> 'theme_set_export',
					'link'	=> Url::internal( "app=core&module=customization&controller=themes&do=exportForm&id={$this->_id}" . ( ( IN_DEV or Settings::i()->theme_designer_mode ) ? '' : '&form_submitted=1' ) )
			);
		}

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
		{
			$buttons['member_theme_set'] = array(
					'icon'	=> 'user',
					'title'	=> 'theme_set_members',
					'link'	=> $url->setQueryString( array( 'do' => 'setMembers', 'id' => $this->is_default ? 0 : $this->_id ) ),
					'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $this->_title )
			);
		}

		if ( $this->canDelete() )
		{
			$buttons['delete'] = $parentButtons['delete'];
		}

		if ( $this->isCustomized() )
		{
			$buttons['theme_revert'] = [
				'icon' => 'reply',
				'title' => 'theme_set_revert',
				'link' => Url::internal( "app=core&module=customization&controller=themes&do=revertCustomizations&set_id={$this->_id}" ),
				'data' => [ 'confirm' => 'true', 'confirmMessage' => Member::loggedIn()->language()->addToStack( 'theme_set_revert_explain' )]
			];
		}

		if ( IN_DEV or Settings::i()->theme_designer_mode )
		{
			$buttons['theme_settings'] = [
				'icon' => 'sliders',
				'title' => 'theme_set_editor_settings',
				'link' => Url::internal( "app=core&module=customization&controller=themeeditor&set_id={$this->_id}" )
			];

			$buttons['theme_core_cssjs'] = [
				'icon' => 'file-code',
				'title' => 'theme_set_designer_core_cssjs',
				'link' => Url::internal( "app=core&module=customization&controller=themes&do=designerCore&set_id={$this->_id}" )
			];

			$buttons['custom_templates'] = array(
				'icon'	=> 'code',
				'title'	=> 'theme_set_manage_templates_custom',
				'link'	=> Url::internal( "app=core&module=customization&controller=customtemplates&type=themes&set_id={$this->_id}" ),
				'data'	=> array()
			);
		}

		return $buttons;
	}

	/**
	 * [Node] Save Add/Edit Form
	 *
	 * @param	array	$values	Values from the form
	 * @return    mixed
	 */
	public function saveForm( array $values ): mixed
	{
		$creating = FALSE;

		/* Create if necessary */
		if ( ! $this->id )
		{
			$creating = TRUE;
			$this->long_version = Application::load( 'core' )->long_version;
			$this->save();
		}

		if ( isset( $values['core_theme_set_new_import'] ) )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['core_theme_set_new_import'], $tempFile );

			/* Store values */
			$key = 'core_theme_import_' . md5_file( $tempFile );
			Store::i()->$key = array( 'apps'  		=> 'all',
										        'resources'		=> true,
										        'html'			=> true,
										        'css'			=> true );

			/* Initate a redirector */
			Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes&do=import' )->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ), 'id' => $this->id ) ) );
		}
		else
		{
			/* Name */
			Lang::saveCustom( 'core', "core_theme_set_title_{$this->id}", $values['core_theme_set_title'] );

			$dataChanged = false;
			$save        = array();

			if ( $values['core_theme_set_is_default'] )
			{
				Db::i()->update( 'core_themes', array( 'set_is_default' => 0 ), array( 'set_id<>?', $this->id ) );
				$dataChanged = true;
			}

			if ( $dataChanged OR IN_DEV)
			{
				if (IN_DEV)
				{
					$save['set_author_name']  = $values['theme_template_export_author_name'];
					$save['set_author_url']   = $values['theme_template_export_author_url'];
					$save['set_update_check'] = $values['theme_update_check'];
					$save['set_version']      = $values['theme_template_export_version'];
					$save['set_long_version'] = $values['theme_template_export_long_version'];
				}
				$this->save();
				$this->saveSet( $save );
			}
		}

		$this->is_default  = $values['core_theme_set_is_default'];
		$this->permissions = ( $values['core_theme_set_permissions'] === '*' ) ? '*' : implode( ',', $values['core_theme_set_permissions'] );
		$this->css_updated = time();

		if ( isset( $values['theme_simple_header_settings'] ) )
		{
			$this->custom_header = $values['theme_simple_header_settings'];
		}

		if ( isset( $values['theme_simple_footer_settings'] ) )
		{
			$this->custom_footer = $values['theme_simple_footer_settings'];
		}

		if ( isset( $values['theme_simple_css'] ) )
		{
			$this->custom_css = $values['theme_simple_css'];
		}

		$headerKey = 'custom_header_' . $this->id;
		$footerKey = 'custom_footer_' . $this->id;
		unset( Store::i()->$headerKey);
		unset( Store::i()->$footerKey);

		/* Remove compiled templates to ensure the globalTemplate gets the latest updates */
		static::deleteCompiledTemplate( 'core', 'front', 'global', $this->id );

		/* Remove compiled CSS */
		static::deleteCompiledCss( 'core', 'front', 'custom', 'custom.css', $this->id );

		/* Finally, save the theme */
		$this->save();

		return NULL;
	}

	/**
	 * Return the value of a layout setting
	 *
	 * @param string $what
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getLayoutValue( string $what ): string
	{
		if ( Member::loggedIn()->isEditingTheme() and isset( Request::i()->cookie['theme_editor_vars'] ) and $cookie = json_decode( Request::i()->cookie['theme_editor_vars'], true ) )
		{
			/* For consistency, the theme editor prefixes layout options with 'layout_' */
			if( isset( $cookie['layouts'] ) and isset( $cookie['layouts'][ 'layout_' . $what ] ) )
			{
				return $cookie['layouts'][ 'layout_' . $what ];
			}
			elseif( isset( $cookie['vars'] ) and isset( $cookie['vars'][ 'layout_' . $what ] ) )
			{
				return $cookie['vars'][ 'layout_' . $what ];
			}
		}

		if ( empty( $this->_layoutValues ) )
		{
			$this->_layoutValues = $this->view_options ? json_decode( $this->view_options, TRUE ) : [];
		}

		if ( isset( $this->_layoutValues[ $what ] ) )
		{
			return $this->_layoutValues[ $what ];
		}

		/* Got a default? */
		$vars = $this->getCssVariables();
		if( isset( $vars[ 'layout_' . $what ] ) )
		{
			return $vars[ 'layout_' . $what ];
		}

		/* Can't really help you */
		throw new InvalidArgumentException( 'Invalid layout value' );
	}

	/**
	 * Return the available layout options
	 *
	 * @return array
	 */
	public function getAvailableLayoutOptionsForThemeEditor(): array
	{
		$return = [];

		foreach( Application::enabledApplications() as $dir => $app )
		{
			$return = array_merge( $return, $app->getThemeLayoutOptionsForThisPage() );
		}

		/* loop through $return array and prepend 'layout_' to each value */
		foreach ( $return as &$value )
		{
			$value = 'layout_' . $value;
		}

		return $return;
	}

	/**
	 * Build resource map of "human URL" to File Object URL
	 *
	 * @param array|string|null $app	App (e.g. core, forum)
	 * @return	void
	 */
	public function buildResourceMap( array|string $app=NULL )
	{
		$flagKey = 'resource_compiling_' . $this->_id . '_' . $app;
		if ( static::checkLock( $flagKey ) )
		{
			return NULL;
		}

		static::lock( $flagKey );

		$resourceMap = $this->resource_map;

		$where = ( $app !== null ) ? array( 'resource_set_id=? and resource_app=?', $this->_id, $app ) : array('resource_set_id=?', $this->_id );

		foreach (Db::i()->select( '*', 'core_theme_resources', $where ) as $row )
		{
			$name = static::makeBuiltTemplateLookupHash( $row['resource_app'], $row['resource_location'], $row['resource_path'] ) . '_' . $row['resource_name'];

			if ( $row['resource_filename'] )
			{
				$resourceMap[$name] = $row['resource_filename'];
			}
			else
			{
				/* If there is no filename, then it has yet to be compiled so do not add it to the resource map as it prevents it being compiled later */
				unset( $resourceMap[$name] );
			}
		}

		$this->resource_map = $resourceMap;
		$this->save();

		static::unlock( $flagKey );
	}

	/**
	 * Copy all resources from set $id to this set
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param int $id		ID to copy from. 0 is the 'master' resources (same as the default when first installed)
	 * @return  void
	 */
	public function copyResourcesFromSet( int $id=0 ): void
	{
		Db::i()->delete( 'core_theme_resources', array( 'resource_set_id=?', $this->_id ) );

		$resourceMap = array();

		foreach (Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=?', $id ) ) as $data )
		{
			$key = static::makeBuiltTemplateLookupHash($data['resource_app'], $data['resource_location'], $data['resource_path']) . '_' . $data['resource_name'];

			if ( $data['resource_data'] )
			{
				try
				{
					$fileName = (string) File::create( 'core_Theme', $key, $data['resource_data'], 'set_resources_' . $this->_id, FALSE, NULL, FALSE );

					Db::i()->insert( 'core_theme_resources', array(
							'resource_set_id'      => $this->_id,
							'resource_app'         => $data['resource_app'],
							'resource_location'    => $data['resource_location'],
							'resource_path'        => $data['resource_path'],
							'resource_name'        => $data['resource_name'],
							'resource_added'	   => time(),
							'resource_filename'    => $fileName,
							'resource_data'        => $data['resource_data'],
							'resource_user_edited' => $data['resource_user_edited']
					) );

					$resourceMap[ $key ] = $fileName;
				}
				catch( Exception $e ) { }
			}
		}

		/* Update theme map */
		$this->resource_map = $resourceMap;
		$this->save();
	}

	/**
	 * Copy all logos from a set
	 *
	 * @param int $id			ID to copy from
	 * @return  void
	 */
	public function copyLogosFromSet( int $id ): void
	{
		try
		{
			$original = static::load( $id );
		}
		catch( OutOfRangeException $e )
		{
			throw new OutOfRangeException("CANNOT_LOAD_THEME");
		}

		if ( $original->logo_data === NULL )
		{
			return;
		}

		$currentLogos = json_decode( $this->logo_data, TRUE );
		$logos        = array();
		foreach ( json_decode( $original->logo_data, TRUE ) as $file => $data )
		{
			if ( isset( $currentLogos[ $file ] ) )
			{
				continue;
			}

			if( isset( $data['url'] ) and $data['url'] )
			{
				try
				{
					/* Create new file */
					$original = File::get( 'core_Theme', $data['url'] );
					$original->contents();
					$image = Image::create( $original->contents() );
					$newImage = File::create( 'core_Theme', $original->originalFilename, $original->contents() );

					$logos[$file] = array( 'url' => (string) $newImage, 'height' => $data['height'] );
				}
				catch ( Exception $e ) { }
			}
		}

		$this->logo_data = json_encode( $logos );
		$this->save();
	}

	/**
	 * Install the theme editor settings for this theme
	 *
	 * @return void
	 */
	public function installThemeEditorSettings() : void
	{

	}

	/**
	 * Compile all CSS for a first party application
	 *
	 * @param string $app
	 * @return void
	 */
	public static function compileStatic( string $app='' ) : void
	{
		if( !in_array( $app, IPS::$ipsApps ) )
		{
			return;
		}

		$themeXmlFile = Application::load( $app )->getApplicationPath() . "/data/theme.xml";
		if( ! file_exists( $themeXmlFile ) )
		{
			return;
		}

		/* Try to prevent timeouts to the extent possible */
		$cutOff			= null;

		if( $maxExecution = @ini_get( 'max_execution_time' ) )
		{
			/* If max_execution_time is set to "no limit" we should add a hard limit to prevent browser timeouts */
			if ( $maxExecution == -1 )
			{
				$maxExecution = 30;
			}

			$cutOff	= time() + ( $maxExecution * .5 );
		}

		/* Remove old templates... */
		$files = glob( ROOT_PATH . '/static/templates/' . $app . '_*' );
		foreach( $files as $file )
		{
			@unlink( $file );
		}

		/* Remove old CSS... */
		$files = glob( ROOT_PATH . '/static/css/' . $app . '_*' );
		foreach( $files as $file )
		{
			@unlink( $file );
		}

		/* Remove old resources... */
		$files = glob( ROOT_PATH . '/static/resources/' . $app . '_*' );
		foreach( $files as $file )
		{
			@unlink( $file );
		}

		/* Open XML file */
		$xml = Xml\XMLReader::safeOpen( $themeXmlFile );
		$xml->read();

		$functions = array();
		$styles = array();
		while( $xml->read() )
		{
			if( $xml->nodeType != XMLReader::ELEMENT )
			{
				continue;
			}

			switch( $xml->name )
			{
				case 'template':
					$template	= array(
						'group'		=> $xml->getAttribute('template_group'),
						'name'		=> $xml->getAttribute('template_name'),
						'variables'	=> $xml->getAttribute('template_data'),
						'content'	=> $xml->readString(),
						'location'	=> $xml->getAttribute('template_location')
					);

					if( !isset( $functions[ $template['location'] ][ $template['group'] ] ) )
					{
						$functions[ $template['location'] ][ $template['group'] ] = [];
					}

					$functions[ $template['location'] ][ $template['group'] ][ $template['name'] ] = static::compileTemplate( $template['content'], $template['name'], $template['variables'], true, false, $app, $template['location'], $template['group'] );
					break;

				case 'css':
					$location = $xml->getAttribute( 'css_location' );
					$path = $xml->getAttribute( 'css_path' );
					$name = $xml->getAttribute( 'css_name' );
					$content = $xml->readString();
					if( !isset( $styles[ $location ][ $path ] ) )
					{
						$styles[ $location ][ $path ] = [];
					}
					$styles[ $location ][ $path ][ $name ] = $content;
					break;

				case 'resource':
					static::addResource( array(
						'app'		=> $app,
						'location'	=> $xml->getAttribute('location'),
						'path'		=> $xml->getAttribute('path'),
						'name'		=> $xml->getAttribute('name'),
						'content'	=> base64_decode( $xml->readString() ),
					) );
					break;
			}
		}

		/* Loop through all functions and write them to the static directory */
		$path = '/static/templates/';
		if( !is_dir( ROOT_PATH. $path ) )
		{
			mkdir( ROOT_PATH . $path, IPS_FOLDER_PERMISSION, TRUE );
		}

		foreach( $functions as $location => $groups )
		{
			foreach( $groups as $group => $templates )
			{
				/* Put them in a class */
				$templateContent = <<<EOF
<?php
namespace IPS\Theme;
class class_{$app}_{$location}_{$group} extends \IPS\Theme\Template
{
EOF;
				$templateContent .= implode( "\n\n", $templates );
				$templateContent .= <<<EOF
}
EOF;

				$templateFileName = $path . $app . '_' . $location. '_' . $group . '.php';
				$result = (bool) @file_put_contents( ROOT_PATH . $templateFileName, $templateContent, LOCK_EX );

				/* Sometimes LOCK_EX is unavailable and throws file_put_contents(): Exclusive locks are not supported for this stream.
					While we would prefer an exclusive lock, it would be better to write the file if possible. */
				if( !$result )
				{
					@unlink( ROOT_PATH . $templateFileName );
					$result = (bool) @file_put_contents( ROOT_PATH . $templateFileName, $templateContent );
				}

				@chmod( ROOT_PATH . $templateFileName, IPS_FILE_PERMISSION );
			}
		}

		/**
		 * Everything that is a single CSS file in the /dev/css area should be a single file unless
		 * you're in the special building rules because oooh I'm so special look at me.
		 */
		foreach( $styles as $location => $paths )
		{
			foreach( $paths as $path => $data )
			{
				if( $path == '.' OR empty( $path ) )
				{
					foreach ( $data as $cssName => $cssData )
					{
						/* forums/front/widgets.css should be written as forums_front_widgets.css */
						static::writeCss( [
							'css_app' => $app,
							'css_location' => $location,
							'css_path' => '.',
							'css_name' => $cssName,
							'css_content' => $cssData,
							'css_set_id' => 0
						] );
					}
				}
				else
				{
					/* Special rules... */
					if ( isset( static::$buildGrouping['css'][ $app ][ $location ] ) and in_array( $path, static::$buildGrouping['css'][ $app ][ $location ] ) )
					{
						/* We want this packaged up as one file */
						static::writeCss( [
							'css_app' => $app,
							'css_location' => $location,
							'css_path' => $path,
							'css_name' => $path . '.css',
							'css_content' => implode( "\n\n", $data ),
							'css_set_id' => 0
						] );
					}
					else
					{
						/* Otherwise we don't (e.g. core/front/styles/foo.css should be written as core_front_styles_foo.css) */
						foreach( $data as $cssName => $cssData )
						{
							static::writeCss( array(
								'css_app' => $app,
								'css_location' => $location,
								'css_path' => $path,
								'css_name' => $cssName,
								'css_content' => $cssData,
								'css_set_id' => 0
							) );
						}
					}
				}
			}
		}
	}

	/**
	 * Compile CSS ready for non IN_DEV use. This replaces any HTML logic such as {resource="foo.png"} with full URLs
	 *
	 * @param array|string $app CSS app (e.g. core, forum)
	 * @param array|string $location CSS location (e.g. admin,global,front)
	 * @param array|string $group CSS group (e.g. custom, framework)
	 * @param string $name CSS name (e.g. foo.css)
	 * @return boolean|null
	 */
	public function compileCss( array|string $app='', array|string $location='', array|string $group='', string $name='' ): ?bool
	{
		$flagKey = 'css_compiling_' . $this->_id . '_' . md5( $app . ',' . $location . ',' . $name );
		if ( static::checkLock( $flagKey ) )
		{
			return NULL;
		}

		static::lock( $flagKey );

		/* Deconstruct build grouping */
		if ( $name !== null )
		{
			if ( isset( static::$buildGrouping['css'][ $app ][ $location ] ) )
			{
				foreach( static::$buildGrouping['css'][ $app ][ $location ] as $grouped )
				{
					if ( str_replace( '.css', '', $name ) == $grouped )
					{
						$group = $grouped;
					}
				}
			}
		}

		$css    = $this->getAllCss( $app, $location, $group );
		$cssMap = $this->css_map;

		if ( $name === null )
		{
			/* Clear out existing built bits */
			File::getClass('core_Theme')->deleteContainer( 'css_built_' . $this->_id );

			$cssMap = array();
		}

		foreach( $css as $app => $v )
		{
			foreach( $css[ $app ] as $location => $paths )
			{
				$built = array();

				foreach( $css[ $app ][ $location ] as $path => $data )
				{
					foreach( $css[ $app ][ $location ][ $path ] as $cssName => $cssData )
					{
						if ( isset( static::$buildGrouping['css'][ $app ][ $location ] ) AND in_array( $path,  static::$buildGrouping['css'][ $app ][ $location ] ) )
						{
							if ( $name === null OR $name == ( $path . '.css' ) )
							{
								$key = static::makeBuiltTemplateLookupHash( $app, $location, $path );

								if ( isset( $built[ $key ] ) )
								{
									$built[ $key ]['css_content'] .= "\n\n" . $cssData['css_content'];
								}
								else
								{
									$cssData['css_name'] = $path . '.css';
									$cssData['css_path'] = '.';

									$built[ $key ] = $cssData;
								}
							}
						}
						else
						{
							if ( $name === null OR $name == $cssData['css_name'] )
							{
								$store  = static::makeBuiltTemplateLookupHash( $app, $location, $cssData['css_path'] . '/' . $cssData['css_name'] );

								$cssMap[ $store ] = (string) static::writeCss( $cssData );
							}
						}
					}
				}

				/* Write combined css */
				if ( count( $built ) )
				{
					foreach( $built as $id => $cssData )
					{
						$store = static::makeBuiltTemplateLookupHash( $app, $location, $cssData['css_path'] . '/' . $cssData['css_name'] );

						$cssMap[ $store ] = (string) static::writeCss( $cssData );
					}
				}
			}
		}

		if ( ! empty( trim( $this->custom_css ) ) )
		{
			$store = static::makeBuiltTemplateLookupHash( 'core', 'front', 'custom/custom.css');
			$cssMap[ $store ] = (string) static::writeCss( [
				'css_content' => $this->custom_css,
				'css_name'    => 'custom.css',
				'css_path'    => 'custom',
				'css_location' => 'front',
				'css_app' => 'core',
				'css_set_id' => $this->id
			] );
		}

		$this->css_map = $cssMap;
		$this->save();

		static::unlock( $flagKey );

		return TRUE;
	}

	/**
	 * Build Templates ready for non IN_DEV use
	 * This fetches all templates in a group, converts HTML logic into ready to eval PHP and stores as a single PHP class per template group
	 *
	 * @param array|string|null $app		Templates app (e.g. core, forum)
	 * @param array|string|null $location	Templates location (e.g. admin,global,front)
	 * @param array|string|null $group		Templates group (e.g. forms, members)
	 * @return	boolean|null
	 */
	public function compileTemplates( array|string $app=null, array|string $location=null, array|string $group=null ): ?bool
	{
		$flagKey = 'template_compiling_' . $this->_id . '_' . md5( $app . ',' . $location . ',' . $group );
		if ( static::checkLock( $flagKey ) )
		{
			return NULL;
		}

		static::lock( $flagKey );

		$templates = $this->getAllTemplates( $app === null ? '' : $app, $location === null ? '' : $location, $group === null ? '' : $group );

		foreach( $templates as $app => $v )
		{
			foreach( $templates[ $app ] as $location => $groups )
			{
				foreach( $templates[ $app ][ $location ] as $group => $bits )
				{
					/* Build all the functions */
					$functions = array();
					foreach( $templates[ $app ][ $location ][ $group ] as $name => $data )
					{
						$functions[ $name ] = static::compileTemplate( $data['template_content'], $name, $data['template_data'], true, false, $app, $location, $group );
					}

					/* Put them in a class */
					$template = <<<EOF
namespace _NAMESPACE_;
class class_{$app}_{$location}_{$group} extends \IPS\Theme\Template
{
EOF;

					$template .= implode( "\n\n", $functions );

					$template .= <<<EOF
}
EOF;

					/* Store it */
					$key = strtolower( 'template_' . $this->_id . '_' .static::makeBuiltTemplateLookupHash( $app, $location, $group ) . '_' . static::cleanGroupName( $group ) );
					Store::i()->$key = str_replace( 'namespace _NAMESPACE_', 'namespace IPS\Theme', $template );
				}
			}
		}

		static::unlock( $flagKey );

		return TRUE;
	}

	/**
	 * Clean the group name
	 *
	 * @param string $name       The name to clean
	 * @return  string
	 */
	public static function cleanGroupName( string $name ): string
	{
		return str_replace( '-', '_', Friendly::seoTitle( $name ) );
	}

	/**
	 * Find templates in a template group
	 *
	 * @param string $group		Template group to search in
	 * @param string|null $app		Application key
	 * @param string|null $location	Template location
	 * @return	array
	 */
	public static function findTemplatesByGroup( string $group, string $app=NULL, string $location=NULL ): array
	{
		if (IN_DEV)
		{
			return Theme::findTemplatesByGroup( $group, $app, $location );
		}

		$where	= array( array( 'template_group=?', $group ) );

		if( $app !== NULL )
		{
			$where[]	= array( 'template_app=?', $app );
		}

		if( $location !== NULL )
		{
			$where[]	= array( 'template_location=?', $location );
		}

		$results	= array();

		foreach(Db::i()->select( 'template_name', 'core_theme_templates', $where ) as $result )
		{
			$results[ $result['template_name'] ]	= $result['template_name'];
		}

		return array_unique( $results );
	}

	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions(): bool
	{
		return false;
	}

	/**
	 * User can access this theme?
	 *
	 * @return	bool
	 */
	public function canAccess(): bool
	{
		if ( $this->is_default )
		{
			return true;
		}

		return ( $this->permissions === '*' OR Member::loggedIn()->inGroup( explode( ',', $this->permissions ) ) );
	}

	/**
	 * Get theme header and footer
	 *
	 * @param string	$type	header|footer
	 * @return string
	 */
	public function getHeaderAndFooter( string $type = 'header' ): string
	{
		$key = "custom_{$type}_{$this->_id}";
		$content = "";
		switch( $type )
		{
			case 'header':
				$content = $this->custom_header;
				break;

			case 'footer':
				$content = $this->custom_footer;
				break;
		}

		if( $content )
		{
			if ( ! isset( Store::i()->$key ) )
			{
				Store::i()->$key = static::compileTemplate( $content, $key );
			}

			static::runProcessFunction( Store::i()->$key, $key );
			$themeFunction = 'IPS\\Theme\\'. $key;
			return $themeFunction();
		}

		return "";
	}

	/**
	 * Get all CSS. Raw means {resource..} tags and uncompiled
	 *
	 * @param array|string|null $app CSS app (e.g. core, forum)
	 * @param array|string|null $location CSS location (e.g. admin,global,front)
	 * @param array|string|null $path CSS group (e.g. custom, framework)
	 * @param int|null $returnType Determines the content returned
	 * @param bool $returnThisSetOnly
	 * @return array
	 */
	public function getAllCss( array|string|null $app= null, array|string|null $location=array(), array|string|null $path=array(), int $returnType=null, bool $returnThisSetOnly=false ): array
	{
		$returnType = ( $returnType === null )   ? static::RETURN_ALL   : $returnType;
		$app        = ( is_string( $app )      AND $app != ''      ) ? array( $app )      : $app;
		$location   = ( is_string( $location ) AND $location != '' ) ? array( $location ) : $location;
		$path       = ( is_string( $path )     AND $path != ''    )  ? array( $path )     : $path;
		$where      = array();
		$css	    = array();

		$where[] = "css_set_id IN (" . $this->_id .  ", 0)";

		if ( is_array( $app ) AND count( $app ) )
		{
			$where[] = "css_app IN ('" . implode( "','", $app ) . "')";
		}

		if ( is_array( $location ) AND count( $location ) )
		{
			$where[] = "css_location IN ('" . implode( "','", $location ) . "')";
		}

		if ( is_array( $path ) AND count( $path ) )
		{
			$where[] = "css_path IN ('" . implode( "','", $path ) . "')";
		}

		$select = ( $returnType & static::RETURN_BIT_NAMES ) ? 'css_set_id, css_app, css_location, css_path, css_id, css_name, css_modules, css_attributes, css_hidden' : '*';

		foreach(
			Db::i()->select(
				$select . ', INSTR(\',' . $this->_id . ', 0,\', CONCAT(\',\',css_set_id,\',\') ) as theorder',
				'core_theme_css',
				implode( " AND ", $where ),
				'css_location, css_path, css_name, theorder desc'
			)
			as $row )
		{
			/* App installed? */
			if ( ! Application::appIsEnabled( $row['css_app'] ) )
			{
				continue;
			}

			/* CSS not to be included */
			if ( ! empty( $row['css_hidden'] ) )
			{
				continue;
			}

			/* This set only? */
			if ( $returnThisSetOnly === true )
			{
				if ( $row['css_set_id'] != $this->_id )
				{
					continue;
				}
			}

			/* ensure set ID is correct */
			$row['css_set_id']  = $this->_id;
			$row['CssKey']      = str_replace( '.css', '', $row['css_app'] . '_' . $row['css_location'] . '_' . $row['css_path'] . '_' . $row['css_name'] );
			$row['jsDataKey']   = str_replace( '.', '--', $row['CssKey'] );

			if ( $returnType & static::RETURN_ALL_NO_CONTENT )
			{
				unset( $row['css_content'] );
				$css[ $row['css_app'] ][ $row['css_location'] ][ $row['css_path'] ][ $row['css_name'] ] = $row;
			}
			else if ( $returnType & static::RETURN_ALL )
			{
				$css[ $row['css_app'] ][ $row['css_location'] ][ $row['css_path'] ][ $row['css_name'] ] = $row;
			}
			else if ( $returnType & static::RETURN_BIT_NAMES )
			{
				$css[ $row['css_app'] ][ $row['css_location'] ][ $row['css_path'] ][] = $row['css_name'];
			}
			else if ( $returnType & static::RETURN_ARRAY_BIT_NAMES )
			{
				$css[] = $row['css_name'];
			}
		}

		if ( $returnType & static::RETURN_ARRAY_BIT_NAMES )
		{
			sort( $css );
			return $css;
		}

		ksort( $css );

		/* Pretty sure Mark can turn this into a closure */
		foreach( $css as $k => $v )
		{
			ksort( $css[ $k ] );

			foreach( $css[ $k ] as $ak => $av )
			{
				ksort( $css[ $k ][ $ak ] );

				if ( $returnType & static::RETURN_ALL )
				{
					foreach( $css[ $k ][ $ak ] as $bk => $bv )
					{
						ksort( $css[ $k ][ $ak ][ $bk ] );
					}
				}
			}
		}

		return $css;
	}

	/**
	 * @var array The CSS variables for this theme, cached to prevent multiple DB calls
	 */
	protected array $cssVariablesCached = [];

	/**
	 * Return the CSS variables for this theme
	 *
	 * @param int $flag
	 * @return array|null
	 */
	public function getCssVariables( int $flag=0 ): array|null
	{
		if ( ! isset( $this->cssVariablesCached[ $flag ] ) )
		{
			$customValues = [];

			if( !( $flag & static::FORCE_DEFAULT ) )
			{
				if ( ! empty( $this->css_variables ) and $variables = json_decode( $this->css_variables, true ) )
				{
					$customValues = $variables;
				}

				if( ! empty( $this->view_options ) and $layouts = json_decode( $this->view_options, true ) )
				{
					foreach( $layouts as $k =>  $v )
					{
						$customValues[ 'layout_' . $k ] = $v;
					}
				}
			}

			/* Add any pending changes */
			if ( !( $flag & static::FORCE_DEFAULT ) and Member::loggedIn()->isEditingTheme() and isset( Request::i()->cookie['theme_editor_vars'] ) and $cookie = json_decode( Request::i()->cookie['theme_editor_vars'], true ) )
			{
				foreach( $cookie as $group => $vars )
				{
					$customValues = array_merge( $customValues, $vars );
				}
			}

			if ( $flag & static::CUSTOM_ONLY )
			{
				/* We just want the custom/changed variables only */
				$this->cssVariablesCached[ $flag ] = $customValues;
				return $this->cssVariablesCached[ $flag ];
			}

			/* If we are still here, we want to merge in any custom variables with the default variables (from the master set) */
			if ( IN_DEV )
			{
				/* Try the dev theme */
				$cssData = file_get_contents( ROOT_PATH . "/applications/core/dev/css/global/framework/" . static::CSS_VARIABLE_FILENAME );
			}
			else
			{
				try
				{
					/* Try the master theme in the core/global/framework path if not IN_DEV */
					$cssData = Db::i()->select( 'css_content', 'core_theme_css', ['css_set_id=? AND css_app=? AND css_location=? AND css_path=? AND css_name=?', 0, 'core', 'global', 'framework', static::CSS_VARIABLE_FILENAME] )->first();
				}
				catch( UnderflowException )
				{
					$cssData = '';
				}
			}

			// Regular expression to find CSS variables
			$pattern = '/--([\w-]+)\s*:\s*([^;]+)/';
			preg_match_all( $pattern, $cssData, $matches );

			// Extract variable names and values from the matches
			if ( isset( $matches[1] ) and isset( $matches[2] ) )
			{
				$variableNames = $matches[1];
				$variableValues = $matches[2];

				/* Include any editor settings that are not in the main CSS file */
				foreach( new ActiveRecordIterator(
					Db::i()->select( '*', 'core_theme_editor_settings' ),
					Setting::class
						 ) as $setting )
				{
					$variableNames[] = $setting->key;
					try
					{
						$variableValues[] = $setting->value( ( $flag & static::FORCE_DEFAULT ) );
					}
					catch( InvalidArgumentException $e )
					{
						Log::debug( $e, 'template_error' );
						$variableValues[] = "";
					}
				}

				$this->cssVariablesCached[ $flag ] = array_merge( array_combine( $variableNames, $variableValues ), $customValues );
			}
			else
			{
				$this->cssVariablesCached[ $flag ] = [];
			}
		}

		return $this->cssVariablesCached[ $flag ];
	}

	/**
	 * Return any saved CSS variables for <style id="themeVariables">
	 *
	 * @return string
	 */
	public function getInlineCssVariables(): string
	{
		$cssData = '';
		$customCssVars = $this->getCssVariables( Theme::CUSTOM_ONLY );

		if ( $customCssVars and count( $customCssVars ) )
		{
			$cssData = ":root{\n\n";

			foreach( $customCssVars as $key => $value )
			{
				/* Skip layout values, they don't belong here */
				if( str_starts_with( $key, 'layout' ) )
				{
					continue;
				}

				/* Parse the value based on the setting type */
				try
				{
					$value = Setting::load( $key, 'setting_key' )->parsedValue( $value );
				}
				catch( OutOfRangeException ){}

				if( $value !== '' )
				{
					$cssData .= "--{$key}: {$value};\n";
				}
			}

			$cssData .= "\n}";
		}

		return $cssData;
	}

	/**
	 * @param array $cssVariables
	 * @return void
	 */
	public function setCssVariables( array $cssVariables ): void
	{
		$this->css_variables = json_encode( $cssVariables );
		$this->save();

		$this->cssVariablesCached = [];
	}

	/**
	 * Get a CSS variable from its key
	 *
	 * @param $key
	 * @return string
	 */
	public function getCssVariableFromKey( $key ): string
	{
		$cssVariables = $this->getCssVariables();
		return $cssVariables[ $key ] ?? '';
	}

	/**
	 * Get the parsed css variable (what is directly added to themes). Returns an empty string when the provided key is unset
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getParsedCssVariableFromKey( string $key ) : string
	{
		$variables = $this->getCssVariables();
		$value = $variables[$key] ?? null;

		try
		{
			$setting = Setting::load( $key, "setting_key" );
			$value = $setting->parsedValue( $value ?? ( $setting->default ) );
		}
		catch ( OutOfRangeException ) {}

		return (string) $value;
	}

	/**
	 * Get the current scheme (light/dark)
	 *
	 * @return "light"|"dark"|string
	 */
	public function getCurrentCSSScheme() : string
	{
		if ( isset( Request::i()->cookie['scheme_preference']) and $this->getParsedCssVariableFromKey( "set__i-change-scheme" ) == "1" )
		{
			return Request::i()->cookie['scheme_preference'];
		}
		return $this->getParsedCssVariableFromKey( 'set__i-default-scheme' );
	}

	/**
	 * Get all templates. Raw means HTML logic and variables are still in {{format}}
	 *
	 * @param array|string $app				Template app (e.g. core, forum)
	 * @param array|string $location			Template location (e.g. admin,global,front)
	 * @param array|string $group				Template group (e.g. login, share)
	 * @param int|null $returnType			Determines the content returned
	 * @return array
	 */
	public function getAllTemplates( array|string $app=array(), array|string $location=array(), array|string $group=array(), int $returnType=null): array
	{
		$returnType = ( $returnType === null )  ? static::RETURN_ALL   : $returnType;
		$app        = ( is_string( $app )      AND $app != ''      ) ? array( $app )      : $app;
		$location   = ( is_string( $location ) AND $location != '' ) ? array( $location ) : $location;
		$group      = ( is_string( $group )    AND $group != ''    ) ? array( $group )    : $group;
		$where      = array();
		$templates  = array();

		if ( is_array( $app ) AND count( $app ) )
		{
			$where[] = "template_app IN ('" . implode( "','", $app ) . "')";
		}

		if ( is_array( $location ) AND count( $location ) )
		{
			$where[] = "template_location IN ('" . implode( "','", $location ) . "')";
		}

		if ( is_array( $group ) AND count( $group ) )
		{
			$where[] = "template_group IN ('" . implode( "','", $group ) . "')";
		}

		$select = ( $returnType & static::RETURN_BIT_NAMES ) ? 'template_app, template_location, template_group, template_id, template_name, template_data' : '*';

		foreach( Db::i()->select( $select, 'core_theme_templates', implode( " AND ", $where ), 'template_location, template_group, template_name' ) as $row )
		{
			/* App installed? */
			if ( ! Application::appIsEnabled( $row['template_app'] ) )
			{
				continue;
			}

			/* ensure set ID is correct */
			$row['TemplateKey']     = $row['template_app'] . '_' . $row['template_location'] . '_' . $row['template_group'] . '_' . $row['template_name'];
			$row['jsDataKey']       = str_replace( '.', '--', $row['TemplateKey'] );

			if ( $returnType & static::RETURN_ALL_NO_CONTENT )
			{
				unset( $row['template_content'] );
				$templates[ $row['template_app'] ][ $row['template_location'] ][ $row['template_group'] ][ $row['template_name'] ] = $row;
			}
			else if ( $returnType & static::RETURN_ALL )
			{
				$templates[ $row['template_app'] ][ $row['template_location'] ][ $row['template_group'] ][ $row['template_name'] ] = $row;
			}
			else if ( $returnType & static::RETURN_BIT_NAMES )
			{
				$templates[ $row['template_app'] ][ $row['template_location'] ][ $row['template_group'] ][] = $row['template_name'];
			}
			else if ( $returnType & static::RETURN_ARRAY_BIT_NAMES )
			{
				$templates[] = $row['template_name'];
			}
		}

		if ( $returnType & static::RETURN_ARRAY_BIT_NAMES )
		{
			sort( $templates );
			return $templates;
		}

		ksort( $templates );

		/* Pretty sure Mark can turn this into a closure */
		foreach( $templates as $k => $v )
		{
			ksort( $templates[ $k ] );

			foreach( $templates[ $k ] as $ak => $av )
			{
				ksort( $templates[ $k ][ $ak ] );

				if ( $returnType & static::RETURN_ALL )
				{
					foreach( $templates[ $k ][ $ak ] as $bk => $bv )
					{
						ksort( $templates[ $k ][ $ak ][ $bk ] );
					}
				}
			}
		}

		return $templates;
	}

	/**
	 * Get all available hook points in the templates
	 *
	 * @param array|string|null $app
	 * @return array
	 */
	public function getHookPoints( array|string $app=null ): array
	{
		$hookPoints = [];
		$where      = [ [ 'template_has_hookpoints=?', 1 ] ];

		if ( $app )
		{
			if ( is_array( $app ) )
			{
				$where[] = [ Db::i()->in( 'template_app', $app ) ];
			}
			else
			{
				$where[] = [ 'template_app=?', $app ];
			}
		}

		foreach( Db::i()->select( '*', 'core_theme_templates', $where ) as $row )
		{
			$path = $row['template_app'] . '/' . $row['template_location'] . '/' . $row['template_group'] . '/' . $row['template_name'];
			$hookPoints[ $path ] = static::extractHookNames( $row['template_content'] );
		}

		return $hookPoints;
	}

	/**
	 * Save a theme set
	 *
	 * @param array $data	Skin set data
	 * @return	void
	 */
	public function saveSet( array $data=array() ): void
	{
		$save    = array();
		$fields  = array( 'name', 'key', 'permissions', 'is_default', 'author_name', 'author_url', 'resource_dir', 'emo_dir', 'hide_from_list', 'order', 'version', 'long_version', 'update_check' );

		foreach( $fields as $k )
		{
			if ( isset( $data[ 'set_' . $k ] ) )
			{
				$save[ 'set_' . $k ] = $data[ 'set_' . $k ];
			}
		}

		if ( ! $this->_id )
		{
			$save['set_long_version'] = ( ! empty( $save['set_long_version'] ) ) ? (int) $save['set_long_version'] : Application::load( 'core' )->long_version;
		}
		else if ( isset( $save['set_long_version'] ) )
		{
			$save['set_long_version'] = intval( $save['set_long_version'] );
		}

		foreach( [ 'front', 'front-dark', 'mobile', 'mobile-dark' ] as $type )
		{
			if ( isset( $data['logo'][ $type ] ) )
			{
				$this->_data['logo'][ $type ] = $data['logo'][ $type ];
			}
		}

		if ( isset( $data['set_name'] ) )
		{
			Lang::saveCustom( 'core', "core_theme_set_title_{$this->_id}", $data['set_name'] );
		}

		$save['set_logo_data'] = ( isset( $this->_data['logo'] ) ? json_encode( $this->_data['logo'] ) : '{}' );

		if ( isset( $data['set_css_map'] ) )
		{
			$save['set_css_map'] = json_encode( $data['set_css_map'] );
		}

		if ( isset( $data['set_resource_map'] ) )
		{
			$save['set_resource_map'] = json_encode( $data['set_resource_map'] );
		}

		if ( isset( $data['set_theme_editor_data'] ) )
		{
			$save['set_theme_editor_data'] = json_encode( $data['set_theme_editor_data'] );
		}

		Db::i()->update( 'core_themes', $save, array( 'set_id=?', (int) $this->_id ) );

		unset( Store::i()->themes );
	}

	/**
	 * Copies all current theme templates and CSS to the history table for use with diff and/or conflict checking
	 * when importing new templates.
	 *
	 * @return void
	 */
	public function saveHistorySnapshot(): void
	{
		/* Remove all current template records for this theme set */
		Db::i()->delete( 'core_theme_content_history', array( 'content_set_id=?', $this->id ) );

		/* Templates */
		Db::i()->insert( 'core_theme_content_history', Db::i()->select( "null, template_set_id, 'template', template_app, template_location, template_group, template_name, template_data, template_content, IFNULL(template_version, 10000), template_updated", 'core_theme_templates', array( 'template_set_id=?', $this->id ) ) );

		/* CSS */
		Db::i()->insert( 'core_theme_content_history', Db::i()->select( "null, css_set_id, 'css', css_app, css_location, css_path, css_name, css_attributes, css_content, IFNULL(css_version, 10000), css_updated", 'core_theme_css', array( 'css_set_id=?', $this->id ) ) );
	}

	/**
	 * Get diffs. Returns an array of CSS and template diffs between latest version and previous version.
	 *
	 * @return	array	array( 'templates' => array(), 'css' => array() )
	 */
	public function getDiff(): array
	{
		$templates = array();
		$css       = array();
		$results   = [];
		$history   = array( 'templates' => [], 'css' => [] );

		require_once ROOT_PATH . "/system/3rd_party/Diff/class.Diff.php";

		foreach(Db::i()->select(
				"*, MD5( CONCAT( content_app, '.', content_location, '.', content_path, '.', content_name ) ) as bit_key",
				'core_theme_content_history',
				array( 'content_set_id=?', $this->id )
		)->setKeyField('bit_key') as $key => $data )
		{
			if ( $data['content_type'] == 'template' )
			{
				$history['template'][ $key ] = $data;
			}
			else
			{
				$history['css'][ $key ] = $data;
			}
		}

		$results['templates'] = iterator_to_array( Db::i()->select(
				'*, MD5( CONCAT( template_app, \'.\', template_location, \'.\', template_group, \'.\', template_name ) ) as bit_key',
				'core_theme_templates',
				array( 'template_set_id=?', $this->id )
		)->setKeyField('bit_key') );

		$results['css'] = iterator_to_array( Db::i()->select(
				'*, MD5( CONCAT( css_app, \'.\', css_location, \'.\', css_path, \'.\', css_name ) ) as bit_key',
				'core_theme_css',
				array( 'css_set_id=?', $this->id )
		)->setKeyField('bit_key') );

		$masterTemplateBits  = Theme::master()->getAllTemplates();

		/* Find changed and new template bits */
		foreach( $results['templates'] as $key => $data )
		{
			$data['added']   = false;
			$data['deleted'] = false;

			$masterSetTemplate = $masterTemplateBits[$data['template_app']][$data['template_location']][$data['template_group']][$data['template_name']] ?? NULL;

			if ( isset( $history['template'][ $key ] ) )
			{
				$data['oldHumanVersion'] = Application::load( $history['template'][ $key ]['content_app'] )->getHumanVersion( $history['template'][ $key ]['content_long_version'] );
				$data['newHumanVersion'] = Application::load( $results['templates'][ $key ]['template_app'] )->getHumanVersion( $results['templates'][ $key ]['template_version'] );

				if ( md5( $history['template'][ $key ]['content_content'] ) != md5( $data['template_content'] ) )
				{
					$data['diff'] = Diff::toTable( Diff::compare( $history['template'][ $key ]['content_content'], $data['template_content'] ) );
				}
				else
				{
					unset( $results['templates'][ $key ] );
					unset( $history['template'][ $key ] );
					continue;
				}
			}
			else if ( $masterSetTemplate and md5( $masterSetTemplate['template_content'] ) != md5( $data['template_content'] ) )
			{
				$data['diff'] = Diff::toTable( Diff::compare( $masterSetTemplate['template_content'], $data['template_content'] ) );
			}
			else
			{
				$data['added'] = true;
				$data['diff']  = Diff::toTable( Diff::compare( '', $data['template_content'] ) );
			}

			$templates[ $data['template_app'] ][ $data['template_location'] ][ $data['template_group'] ][ $data['template_name'] ] = $data;
		}

		/* Find changed and new CSS bits */
		foreach( $results['css'] as $key => $data )
		{
			$data['added']   = false;
			$data['deleted'] = false;

			if ( isset( $history['css'][ $key ] ) )
			{
				$data['oldHumanVersion'] = Application::load( $history['css'][ $key ]['content_app'] )->getHumanVersion( $history['css'][ $key ]['content_long_version'] );
				$data['newHumanVersion'] = Application::load( $results['css'][ $key ]['css_app'] )->getHumanVersion( $results['css'][ $key ]['css_version'] );

				if ( md5( $history['css'][ $key ]['content_content'] ) != md5( $data['css_content'] ) )
				{
					$data['diff'] = Diff::toTable( Diff::compare( $history['css'][ $key ]['content_content'], $data['css_content'] ) );
				}
				else
				{
					unset( $results['css'][ $key ] );
					unset( $history['css'][ $key ] );
					continue;
				}
			}
			else
			{
				$data['added'] = true;
				$data['diff']  = Diff::toTable( Diff::compare( '', $data['css_content'] ) );
			}

			$css[ $data['css_app'] ][ $data['css_location'] ][ $data['css_path'] ][ $data['css_name'] ] = $data;
		}

		/* Find deleted template bits */
		foreach( array_diff( array_keys( $history['template'] ), array_keys( $results['templates'] ) ) as $key )
		{
			$data = $history['template'][ $key ];

			$templates[ $data['content_app'] ][ $data['content_location'] ][ $data['content_path'] ][ $data['content_name'] ] = array(
				'template_app' 		=> $data['content_app'],
				'template_location' => $data['content_location'],
				'template_group'    => $data['content_path'],
				'template_name'     => $data['content_name'],
				'template_content'  => $data['content_content'],
				'diff'    			=> Diff::toTable( Diff::compare( $history['template'][ $key ]['content_content'], '' ) ),
				'added'				=> false,
				'deleted'			=> true
			);
		}

		/* Find deleted CSS bits */
		foreach( array_diff( array_keys( $history['css'] ), array_keys( $results['css'] ) ) as $key )
		{
			$data = $history['css'][ $key ];

			$css[ $data['content_app'] ][ $data['content_location'] ][ $data['content_path'] ][ $data['content_name'] ] = array(
				'css_app' 		=> $data['content_app'],
				'css_location' 	=> $data['content_location'],
				'css_path'    	=> $data['content_path'],
				'css_name'     	=> $data['content_name'],
				'css_content'  	=> $data['content_content'],
				'diff'    		=> Diff::toTable( Diff::compare( $history['css'][ $key ]['content_content'], '' ) ),
				'added'			=> false,
				'deleted'		=> true
			);
		}

		/* Now sort */
		foreach( $templates as $k => $v )
		{
			ksort( $templates[ $k ] );

			foreach( $templates[ $k ] as $ak => $av )
			{
				ksort( $templates[ $k ][ $ak ] );

				foreach( $templates[ $k ][ $ak ] as $bk => $bv )
				{
					ksort( $templates[ $k ][ $ak ][ $bk ] );
				}
			}
		}

		foreach( $css as $k => $v )
		{
			ksort( $css[ $k ] );

			foreach( $css[ $k ] as $ak => $av )
			{
				ksort( $css[ $k ][ $ak ] );

				foreach( $css[ $k ][ $ak ] as $bk => $bv )
				{
					ksort( $css[ $k ][ $ak ][ $bk ] );
				}
			}
		}

		return array( 'templates' => $templates, 'css' => $css );
	}

	/**
	 * Delete compiled templates
	 * Removes compiled templates bits for all themes that match the arguments
	 *
	 * @param string|null $app		Application Directory (core, forums, etc)
	 * @param array|string|null $location	Template location (front, admin, global, etc)
	 * @param array|string|null $group		Template group (forms, messaging, etc)
	 * @param int|null $themeId	Limit to a specific theme (and children)
	 * @return 	void
	 */
	public static function deleteCompiledTemplate( string $app=null, array|string|null $location=null, array|string|null $group=null, int $themeId=null ): void
	{
		$where     = array();
		$themeSets = array( 0 );

		if ( $app !== NULL )
		{
			$where[] = array( 'template_app=?', $app );
		}

		if ( $location !== null )
		{
			$where[] = array( Db::i()->in( 'template_location', ( is_array( $location ) ) ? $location : array( $location ) ) );
		}

		if ( $group !== null )
		{
			$where[] = array( Db::i()->in( 'template_group', ( is_array( $group ) ) ? $group : array( $group ) ) );
		}

		if ( ! empty( $themeId ) )
		{
			$themeSet  = static::load( $themeId );
			$where[] = array( Db::i()->in( 'template_set_id', array_keys( $themeSets ) ) );
		}

		foreach(
			Db::i()->select(
				"template_app, template_location, template_group, MD5( CONCAT(',', template_app, ',', template_location, ',', template_group) ) as group_key",
				'core_theme_templates',
				$where,
				NULL, NULL, array( 'group_key', 'template_group', 'template_app', 'template_location' )
			)
			as $groupKey => $data
		){
			/* ... remove from each theme */
			foreach( static::themes() as $id => $set )
			{
				if ( $themeId === null OR in_array( $id, array_keys( $themeSets ) ) )
				{
					$key = strtolower( 'template_' . $set->id . '_' .static::makeBuiltTemplateLookupHash( $data['template_app'], $data['template_location'], $data['template_group'] ) . '_' . static::cleanGroupName( $data['template_group'] ) );

					unset( Store::i()->$key );
				}
			}
		}
	}

	/**
	 * Delete compiled Css
	 * Removes compiled Css for all themes that match the arguments
	 *
	 * @param array|string|null $app		CSS Directory (core, forums, etc)
	 * @param array|string|null $location	CSS location (front, admin, global, etc)
	 * @param string|null $path		CSS path (forms, messaging, etc)
	 * @param string|null $name		CSS file to remove
	 * @param int|null $themeId	Limit to a specific theme (and children)
	 * @return 	void
	 */
	public static function deleteCompiledCss( array|string|null $app=null, array|string|null $location=null, string|null $path=null, string $name=null, int $themeId=null ): void
	{
		$where     = array();
		$themeSets = array( 0 );

		if ( $themeId !== null )
		{
			$themeSet  = static::load( $themeId );
			$themeSets = array( $themeId => $themeSet );
			$where[] = array( Db::i()->in( 'css_set_id', array_keys( $themeSets ) ) );
		}

		if ( $app === null )
		{
			/* Each theme... */
			foreach( static::themes() as $id => $set )
			{
				if ( $themeId === null OR in_array( $id, array_keys( $themeSets ) ) )
				{
					File::getClass( 'core_Theme')->deleteContainer('css_built_' . $set->_id );

					$set->css_map = array();
					$set->css_updated = time();
					$set->save();
				}
			}

			/* ACP CSS */
			Settings::i()->changeValues( array( 'acp_css_map' => '[]' ) );

			/* Done */
			return;
		}

		/* Custom CSS? */
		if ( $app == 'core' and $location == 'front' and $path == 'custom' and $name == 'custom.css' and $themeId )
		{
			/* @var $themeSet Theme */
			$map = $themeSet->css_map;
			$key = static::makeBuiltTemplateLookupHash( 'core', 'front', 'custom/custom.css' );
			if ( isset( $map[ $key ] ) )
			{
				File::get( 'core_Theme', $map[ $key ] )->delete();
				unset( $map[ $key ] );

				$themeSet->css_map = $map;
			}

			return;
		}

		/* Deconstruct build grouping */
		if ( $name !== null )
		{
			if ( isset( static::$buildGrouping['css'][ $app ][ $location ] ) )
			{
				foreach( static::$buildGrouping['css'][ $app ][ $location ] as $grouped )
				{
					if ( str_replace( '.css', '', $name ) == $grouped )
					{
						$path = $grouped;
						$name = null;
					}
				}
			}
		}

		$where[] = array( Db::i()->in( 'css_app', ( is_array( $app ) ) ? $app : array( $app ) ) );

		if ( $location !== null )
		{
			$where[] = array( Db::i()->in( 'css_location', ( is_array( $location ) ) ? $location : array( $location ) ) );
		}

		if ( $path !== null )
		{
			$where[] = array( 'css_path=?', $path );
		}

		$css = iterator_to_array( Db::i()->select( "*", 'core_theme_css', $where )->setKeyField('css_id') );
		if ( count( $css ) )
		{
			/* Each theme... */
			static::$gotAll = false;
			foreach( static::themes() as $id => $set )
			{
				if ( $themeId === null OR in_array( $id, array_keys( $themeSets ) ) )
				{
					$built = array();
					$map   = $set->css_map;

					foreach( $css as $cssId => $data )
					{
						if ( isset( static::$buildGrouping['css'][ $data['css_app'] ][ $data['css_location'] ] ) AND in_array( $data['css_path'], static::$buildGrouping['css'][ $data['css_app'] ][ $data['css_location'] ] ) )
						{
							$key = static::makeBuiltTemplateLookupHash( $data['css_app'], $data['css_location'], $data['css_path'] );

							if ( ! isset( $built[ $key ] ) )
							{
								$data['css_name']    = $data['css_path'] . '.css';
								$data['css_path']    = '.';
								$data['css_content'] = '';

								$built[ $key ] = $data;
							}
						}
						else
						{
							/* ... remove the CSS Files */
							$key = static::makeBuiltTemplateLookupHash( $data['css_app'], $data['css_location'], $data['css_path'] . '/' . $data['css_name'] );

							if ( isset( $map[ $key ] ) )
							{
								File::get( 'core_Theme', $map[ $key ] )->delete();
								unset( $map[ $key ] );
							}
						}
					}

					/* Write combined css */
					if ( count( $built ) )
					{
						foreach( $built as $cssData )
						{
							$key = static::makeBuiltTemplateLookupHash( $cssData['css_app'], $cssData['css_location'], $cssData['css_path'] . '/' . $cssData['css_name'] );

							if ( isset( $map[ $key ] ) )
							{
								File::get( 'core_Theme', $map[ $key ] )->delete();
								unset( $map[ $key ] );
							}
						}
					}

					/* Update mappings */
					$theme = static::load( $id );
					$theme->css_map = $map;
					$theme->css_updated = time();
					$theme->save();
				}
			}
		}
	}

	/**
	 * Delete compiled resources
	 * Removes stored resource file objects and associated mappings but doesn't actually remove the resource
	 * row from the database.
	 *
	 * @param array|string|null $app		App Directory (core, forums, etc)
	 * @param array|string|null $location	location (front, admin, global, etc)
	 * @param array|string|null $path		Path (forms, messaging, etc)
	 * @param string|null $name		Resource file to remove
	 * @param int|null $themeId	Limit to a specific theme (and children)
	 * @return 	void
	 */
	public static function deleteCompiledResources( array|string|null $app=null, array|string|null $location=null, array|string|null $path=null, string $name=null, int $themeId=null ): void
	{
		$query     = array();
		$themeSet  = null;
		$map       = array();

		if ( ! empty( $themeId ) )
		{
			$themeSet = static::load( $themeId );
		}

		if ( $app === null )
		{
			/* Each theme... */
			foreach( static::themes() as $id => $set )
			{
				if ( $themeId === null )
				{
					File::getClass( 'core_Theme' )->deleteContainer('set_resources_' . $set->_id );

					$set->resource_map = array();
					$set->save();
				}
			}
		}

		if ( $app !== NULL )
		{
			$query[] = Db::i()->in( 'resource_app', ( is_array( $app ) ) ? $app : array( $app ) );
		}

		if ( $location !== null )
		{
			$query[] = Db::i()->in( 'resource_location', ( is_array( $location ) ) ? $location : array( $location ) );
		}

		if ( $path !== null )
		{
			$query[] = Db::i()->in( 'resource_path', ( is_array( $path ) ) ? $path : array( $path ) );
		}

		if ( $themeSet !== null )
		{
			$query[] = Db::i()->in( 'resource_set_id', [ $themeSet ] );
		}

		if ( $app !== NULL )
		{
			foreach (Db::i()->select( "*", 'core_theme_resources', array( implode( ' AND ', $query ) ) ) as $row )
			{
				try
				{
					if ( !isset( $set ) OR !isset( $map[ $set->id ] ) )
					{
						$set = static::load( $row['resource_set_id'] );

						$map[ $set->id ] = $set->resource_map;
					}

					$name = static::makeBuiltTemplateLookupHash( $row['resource_app'], $row['resource_location'], $row['resource_path'] ) . '_' . $row['resource_name'];

					if ( isset( $map[ $set->id ][ $name ] ) )
					{
						unset( $map[ $set->id ][ $name ] );

						try
						{
							if ( $row['resource_filename'] )
							{
								File::get( 'core_Theme', $row['resource_filename'] )->delete();
							}
						}
						catch ( InvalidArgumentException $ex ) { }
					}
				}
				catch ( OutOfRangeException $ex )
				{
					$map[$row['resource_set_id']] = array();
				}
			}
		}

		Db::i()->update( 'core_theme_resources', array( 'resource_filename' => null ), ( count( $query ) ? array( implode( ' AND ', $query ) ) : NULL ) );

		/* Update mappings */
		foreach( $map as $setId => $data )
		{
			try
			{
				$set = static::load( $setId );
				$set->resource_map = $data;
				$set->save();
				$set->saveSet();
			}
			catch( OutOfRangeException $ex ) { }
		}

		if ( empty( $themeId ) )
		{
			/* ACP CSS and Resources */
			Settings::i()->changeValues( array( 'acp_css_map' => '[]' ) );
			unset( Store::i()->acp_resource_map );
		}
	}

	/**
	 * Run the template content via the compiler and eval methods to see if there's any broken syntax
	 *
	 * @param string $content        The template content
	 * @param string $params         The template params
	 * @return  false                   False if the template is good
	 * @throws  LogicException          If template has issues, $e->getMessage() has the details
	 */
	public static function checkTemplateSyntax( string $content, string $params='' ): bool
	{
		ob_start();

		try
		{
			static::makeProcessFunction( $content, 'unique_function_so_it_doesnt_look_in_function_exists_' . mt_rand(), $params );
		}
		catch( ParseError $e )
		{
			ob_end_clean();
			throw new LogicException( $e->getMessage() );
		}

		$return = ob_get_contents();
		ob_end_clean();

		if ( $return )
		{
			throw new LogicException( $return );
		}

		return false;
	}

	/**
	 * Make process function
	 * Parses template into executable function and evals it.
	 *
	 * @param string $content		Content with variables and parse tags
	 * @param string $functionName	Desired function name
	 * @param string $params			Parameter list
	 * @param bool $isHTML			If TRUE, HTML will automatically be escaped
	 * @param bool $isCSS			If TRUE, the plugins will be checked for $canBeUsedInCss
	 * @return void
	 */
	public static function makeProcessFunction( string $content, string $functionName, string $params='', bool $isHTML=TRUE, bool $isCSS=FALSE ) : void
	{
		static::runProcessFunction( static::compileTemplate( $content, $functionName, $params, $isHTML, $isCSS ), $functionName );
	}

	/**
	 * Make process function
	 * Parses template into executable function and evals it.
	 *
	 * @param string $content		Compiled content with variables and parse tags
	 * @param string $functionName	Desired function name
	 * @return	void
	 */
	public static function runProcessFunction( string $content, string $functionName ): void
	{
		/* If it's already been built, we don't need to do it again */
		if( function_exists( 'IPS\Theme\\' . $functionName ) )
		{
			return;
		}

		/* Build Function */
		$function = 'namespace IPS\Theme;' . "\n" . $content;

		/* Make it */
		if (DEBUG_TEMPLATES)
		{
			static::runDebugTemplate( $functionName, $function );
		}
		else
		{
			if( eval( $function ) === FALSE )
			{
				/* Throw exception for PHP 5 */
				throw new InvalidArgumentException;
			}
		}
	}

	/**
	 * Run the template as a PHP file, not an eval to debug errors
	 *
	 * @param string $functionName	Function name
	 * @param string $content		Compiled content with variables and parse tags
	 * @return	void
	 */
	public static function runDebugTemplate( string $functionName, string $content ): void
	{
		$temp = tempnam( TEMP_DIRECTORY, $functionName );
		file_put_contents( $temp, "<?php\n" . $content );
		include $temp;
		register_shutdown_function( function( $temp ) {
			unlink( $temp );
		}, $temp );
	}

	/**
	 * Expand shortcuts
	 *
	 * @param string $content		Content with shortcuts
	 * @return	string	Content with shortcuts expanded
	 */
	public static function expandShortcuts( string $content ): string
	{
		/* Parse shortcuts */
		foreach ( array( 'member' => 'loggedIn', 'settings' => 'i', 'output' => 'i' ) as $class => $function )
		{
			$content = preg_replace( '/(^|[^$\\\])' . $class . "\.(\S+?)/", '$1\IPS\\' . IPS::mb_ucfirst( $class ) . '::' . $function . '()->$2', $content );
		}

		$content = preg_replace( '/(^|[^$\\\])(?:request\\.|\\\\?IPS\\\\Request::i\\(\\)->)(\S+?)/', '$1\IPS\Widget\Request::i()->$2', $content );

		foreach( array( 'theme' => '\IPS\\Theme::i()->settings', 'cookie' => '\IPS\\Request::i()->cookie' ) as $shortcut => $array )
		{
			$content = preg_replace( '/(^|[^$\\\])' . $shortcut .'\.(.([a-zA-Z0-9_]+))/', '$1 ('. $array . '[\'$2\'])', $content );
		}

		/* Parse special CSS variable shortcut */
		$content = preg_replace( '/(^|[^$\\\])cssvar\.([a-zA-Z0-9_\-]+)/', '$1\IPS\Theme::i()->getCssVariableFromKey(\'$2\')', $content );

		return preg_replace( '/(^|[^$\\\])view\.([a-zA-Z0-9_\-]+)/', '$1\IPS\Theme::i()->getLayoutValue(\'$2\')', $content );
	}

	/**
	 * @param string $content
	 * @param string $path
	 * @param string $params
	 * @return string
	 */
	public static function expandHookPoints( string $content, string $path, string $params='' ): string
	{
		if ( ! mb_stristr( $content, 'data-ips-hook') )
		{
			return $content;
		}

		/* Swap out certain tags that confuse phpQuery */
		$content = preg_replace( '/<(\/)?(html|head|body)(>| (.+?))/', '<$1temp$2$3', $content );
		$content = str_replace( '<!DOCTYPE html>', '<tempdoctype></tempdoctype>', $content );

		$domQueryI = 0;
		$domQueryStore = array();
		$jsonAttrI = 0;
		$jsonAttrStore = array();

		/* Remove raw JS as this can cause a timeout if there is a lot of it */
		$content = preg_replace_callback( '#<script\b[^>]*>([\s\S]*?)<\/script>#', function( $matches ) use ( &$domQueryI, &$domQueryStore )
		{
			$domQueryStore[ ++$domQueryI ] = $matches[0];
			return 'he-' . $domQueryI . '--';
		}, $content );

		/* We sometimes need to use single quotes as the data attr contains json */
		$content = preg_replace_callback( "/([\d\w0-9-]+?)='\{([^']+?)\|raw\}'/", function( $matches ) use ( &$jsonAttrI, &$jsonAttrStore )
		{
			$jsonAttrStore[ ++$jsonAttrI ] = $matches;
			return $matches[1] . '="json--' . $jsonAttrI . '--"';
		}, $content );

		/* Remove any HTML logic as it confuses DOMDocument */
		$content = preg_replace_callback( array( '/{{?(?>[^{}]|(?R))*}?}/', '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/' ), function( $matches ) use ( &$domQueryI, &$domQueryStore )
		{
			$domQueryStore[ ++$domQueryI ] = $matches[0];
			return 'he-' . $domQueryI . '--';
		}, $content );

		/* Parse the HTML */
		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->loadHTML( DOMDocument::wrapHtml( $content ) );
		$xpath = new DOMXPath($doc);

		if( $params )
		{
			preg_match_all( '/(\$[a-zA-Z0-9_]+)/', $params, $matches );
			$paramList = implode( ',', array_unique( $matches[1] ) );
		}
		else
		{
			$paramList = '';
		}

		/* Fetch all tags with the data-ips-hook attribute */
		$tags = $xpath->query("//*[@data-ips-hook]");
		foreach( $tags as $element )
		{
			if ( $element->hasAttribute('data-ips-hook') )
			{
				/* Add in content BEFORE the tag opens */
				if ( $element->parentNode )
				{
					$element->parentNode->insertBefore( new DOMText( '{customtemplate="' . $path . '" params="' . $paramList . '" hook="' . $element->getAttribute( 'data-ips-hook' ) . ':before"}' ), $element );
				}

				/* Add in content AFTER the tag opens */
				if ( $firstChild = $element->firstChild )
				{
					$element->insertBefore( new DOMText( '{customtemplate="' . $path . '" params="' . $paramList . '" hook="' . $element->getAttribute( 'data-ips-hook' ) . ':inside-start"}' ), $firstChild );
				}

				/* Add in content BEFORE the tag closes */
				$element->appendChild( new DOMText( '{customtemplate="' . $path . '" params="' . $paramList . '" hook="' . $element->getAttribute( 'data-ips-hook' ) . ':inside-end"}' ) );

				/* Add in content AFTER the tag has closed */
				if ( $element->parentNode and $nextSibling = $element->nextSibling )
				{
					$element->parentNode->insertBefore( new DOMText( '{customtemplate="' . $path . '" params="' . $paramList . '" hook="' . $element->getAttribute( 'data-ips-hook' ) . ':after"}' ), $nextSibling );
				}
				elseif( $element->parentNode and $element->nextSibling === null )
				{
					$element->parentNode->appendChild( new DOMText( '{customtemplate="' . $path . '" params="' . $paramList . '" hook="' . $element->getAttribute( 'data-ips-hook' ) . ':after"}' ) );
				}
			}
		}

		$return = substr( $doc->saveHTML( $doc->getElementsByTagName('body')->item(0) ), 6, -7);

		/* Put our single quoted data back */
		foreach( $jsonAttrStore as $id => $matches )
		{
			$return = preg_replace( '#' . $matches[1] . '="json--' . $id . '--"#', $matches[0], $return );
		}

		/* Put our moved code back */
		$return = preg_replace_callback( '/he-(.+?)--/', function( $matches ) use ( $domQueryStore )
		{
			return $domQueryStore[$matches[1]] ?? '';
		}, $return );

		/* Swap back certain tags that confuse */
		$return = preg_replace( '/<(\/)?temp(html|head|body)(.*?)>/', '<$1$2$3>', $return );
		$return = str_replace( '<tempdoctype></tempdoctype>', '<!DOCTYPE html>', $return );

		return $return;
	}

	/**
	 * Process template into executable code.
	 *
	 * @param string $content		Content with variables and parse tags
	 * @param string|null $functionName	Desired function name if given, otherwise <<<CONTENT CONTENT; is returned without a function wrapper or $return .= statement
	 * @param string $params			Parameter list
	 * @param bool $isHTML			If TRUE, HTML will automatically be escaped
	 * @param bool $isCSS			If TRUE, the plugins will be checked for $canBeUsedInCss
	 * @param string|null $app			Application the template belongs to
	 * @param string|null $location		Location the template belongs to (admin,front)
	 * @param string|null $group			Group the template belongs to
	 * @return	string	Function name to eval
	 * @throws	InvalidArgumentException
	 */
	public static function compileTemplate( string $content, ?string $functionName, string $params='', bool $isHTML=TRUE, bool $isCSS=FALSE, string $app=null, string $location=null, string $group=null ): string
	{
		$calledClass = get_called_class();

		if( $functionName == 'theme_core_front_global_footer' or ( $functionName == 'footer' and $app == 'core' and $location == 'front' and $group == 'global' ) )
		{
			$content = $content . "\n<p class='ipsCopyright'>
	<span class='ipsCopyright__user'>{lang=\"copyright_line_value\"}</span>
			<a rel='nofollow' title='Invision Community' href='#'>Powered by Invision Community</a><br><a href='https://nullforums.net' style='display:none'>Invision Community Support Forums</a>
</p>";
		}

		/* Expand hook points */
		if ( ! $isCSS and mb_stristr( $content, 'data-ips-hook=' ) )
		{
			if ( $app == NULL and $location == NULL and $group == NULL and mb_substr( $functionName, 0, 6 ) == 'theme_' )
			{
				/* Yeah, preg_match because we can't just explode on '_' as some template names may have them */
				preg_match( '#theme_([^_]+?)_([^_]+?)_([^_]+?)_(.*)#', $functionName, $matches );
				$path = $matches[1] . '/' . $matches[2] . '/' . $matches[3] . '/' . $matches[4];
			}
			else
			{
				$path = $app . '/' . $location . '/' . $group . '/' . $functionName;
			}

			$content = static::expandHookPoints( $content, $path, $params );
		}

		return static::_compileTemplate( $content, $functionName, $isHTML, $isCSS, $params );
	}

	/**
	 * Compile the template into executable code
	 * @note This is a separate function so that we can call it outside of the main function
	 *
	 * @param string $content
	 * @param ?string $functionName
	 * @param bool $isHTML
	 * @param bool $isCSS
	 * @param string $params
	 * @return string
	 */
	public static function _compileTemplate( string $content, ?string $functionName, bool $isHTML=true, bool $isCSS=FALSE, string $params='' ): string
	{
		$contentName = $isCSS ? "'IPSCONTENT'" : 'IPSCONTENT';
		$calledClass = get_called_class();

		// Prevent anyone from attempting to hijack the theme system
		$content = preg_replace( "#((?:^|\\n)\\s*IPSCONTENT;)#si", "\$1\n\$return .= <<<IPSCONTENT_ESC\n\$1\nIPSCONTENT_ESC;\n\$return .= <<<{$contentName}\n", $content );

		/* Parse out {{code}} tags */
		$content = preg_replace_callback( '/{{(.+?)}}/', function( $matches ) use ( $contentName )
		{
			/* Parse shortcuts */
			$matches[1] = Theme::expandShortcuts( $matches[1] );
			/* Make conditionals and loops valid PHP */
			if( $matches[1] === 'else' )
			{
				$matches[1] .= ':';
			}
			elseif( substr( $matches[1], 0, 3 ) === 'end' )
			{
				$matches[1] .= ';';
			}
			elseif( in_array( substr( $matches[1], 0, 4 ), array( 'for ', 'for(' ) ) )
			{
				$matches[1] = 'for (' . substr( $matches[1], 3 ) . ' ):';
			}
			else
			{
				foreach ( array( 'if', 'elseif', 'foreach' ) as $tag )
				{
					if( substr( $matches[1], 0, strlen( $tag ) ) === $tag )
					{
						$matches[1] = $tag .' (' . substr( $matches[1], strlen( $tag ) ) . ' ):';
					}
				}
			}

			return "\nIPSCONTENT;\n\n{$matches[1]}\n\$return .= <<<{$contentName}\n";
		}, $content );

		/* Make sure any literal \{\{This should not be treated as PHP\}\} is converted back into {{this shoud not be treated as PHP}} */
		$content = preg_replace( '/\\\{\\\{(.+?)\\\}\\\}/', '{{\1}}', $content );

		if ( ! $functionName )
		{
			$function = <<<PHP
		<<<{$contentName}\n
{$content}
IPSCONTENT;\n
PHP;
		}
		else
		{
			$function = <<<PHP
	function {$functionName}( {$params} ) {
		\$return = '';
		\$return .= <<<{$contentName}\n
{$content}
IPSCONTENT;\n
		return \$return;
}
PHP;
		}

		/* Parse {plugin="foo"} tags */
		$function = preg_replace_callback
		(
			'/\{([a-z]+?=([\'"]).+?\\2 ?+)}/',
			function( $matches ) use ( $functionName, $isCSS, $calledClass, $contentName )
			{
				/* Work out the plugin and the values to pass */
				preg_match_all( '/(.+?)='.$matches[2].'([^' . $matches[2] . ']*)'.$matches[2].'\s?/', $matches[1], $submatches );

				$plugin = array_shift( $submatches[1] );
				$pluginClass = 'IPS\\Output\\Plugin\\' . IPS::mb_ucfirst( $plugin );

				$value = array_shift( $submatches[2] );
				$options = array();

				foreach ( $submatches[1] as $k => $v )
				{
					$options[ $v ] = $submatches[2][ $k ];
				}

				/* Work out if this plugin belongs to an application, and if so, include it */
				if( !class_exists( $pluginClass ) )
				{
					foreach (Application::applications() as $app )
					{
						$path = static::getHookPath( "applications/{$app->directory}/extensions/core/OutputPlugins/" . IPS::mb_ucfirst( $plugin ) . ".php" );

						if ( file_exists( $path . "/applications/{$app->directory}/extensions/core/OutputPlugins/" . IPS::mb_ucfirst( $plugin ) . ".php" ) )
						{
							$pluginClass = 'IPS\\' . $app->directory . '\\extensions\\core\\OutputPlugins\\' . IPS::mb_ucfirst( $plugin );
						}
					}
				}

				/* Still doesn't exist? */
				if( ! class_exists( $pluginClass ) )
				{
					return $matches[0];
				}

				/* can be used in CSS? */
				if ( $isCSS AND $pluginClass::$canBeUsedInCss !== TRUE )
				{
					throw new InvalidArgumentException( 'invalid_plugin:' . $functionName . ' - ' . $plugin );
				}

				$code = $pluginClass::runPlugin( $value, $options, $functionName, $calledClass );

				if( !is_array( $code ) )
				{
					$code = array( 'return' => $code );
				}
				if( !isset( $code['pre'] ) )
				{
					$code['pre'] = '';
				}

				if( !isset( $code['post'] ) )
				{
					$code['post'] = '';
				}

				$return = <<<PHP
\nIPSCONTENT;\n
{$code['pre']}
PHP;
				if ( $code['return'] )
				{
					$return .= <<<PHP
\$return .= {$code['return']};
PHP;
				}
				$return .= <<<PHP
{$code['post']}
\$return .= <<<{$contentName}

PHP;
				return $return;
			},
			$function
		);

		/* Escape output */
		if ( $isCSS )
		{
			/* For the raw css content, we want to slap it inside a NOWDOC, not a HEREDOC, so that variables and backslashes are never processed. */
			$function = preg_replace( '#(\\$return\\s*(?:\\.)?=\\s*<<<)IPSCONTENT(\\n.+?\\n\\s*IPSCONTENT;(\\n|$))#si', '$1\'IPSCONTENT\'$2', $function );
		}
		else
		{
			preg_match_all( '#\$return\s{0,}(?:\.)?=\s{0,}<<<IPSCONTENT[\r\n](.+?)IPSCONTENT;(\n|\r|$)#si', $function, $matches, PREG_SET_ORDER );
			foreach ( $matches as $id => $match )
			{
				$all = $match[0];
				$content = $match[1];
				$rawFinds = array();
				$rawReplaces = array();

				if ( $isHTML === TRUE )
				{
					preg_match_all( '#\{\$([^\}]+?)\}#', $content, $varMatches, PREG_SET_ORDER );

					foreach ( $varMatches as $index => $var )
					{
						if ( stristr( $var[1], '|raw' ) )
						{
							$rawFinds[] = $var[0];
							$rawReplaces[] = str_ireplace( '|raw', '', $var[0] );
						}
						else
						{
							if ( stristr( $var[1], '|doubleencode' ) )
							{
								$replace = "\nIPSCONTENT;\n\$return .= \IPS\Theme\Template::htmlspecialchars( \$" . str_ireplace( '|doubleencode', '', $var[1] ) . ", ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );\n\$return .= <<<IPSCONTENT\n";
							}
							else
							{
								$replace = "\nIPSCONTENT;\n\$return .= \IPS\Theme\Template::htmlspecialchars( \$" . $var[1] . ", ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n\$return .= <<<IPSCONTENT\n";
							}

							$all = str_replace( $var[0], $replace, $all );
						}
					}
					$all = str_replace( $rawFinds, $rawReplaces, $all );

					if ( $all != $match[0] )
					{
						$function = str_replace( $match[0], $all, $function );
					}
				}
				else
				{
					$all = preg_replace( '/\{\$([^\}]+?)\|raw\}/', '{\$$1}', $all );
					$function = str_replace( $match[0], $all, $function );
				}
			}
		}

		return $function;
	}

	/**
	 * Returns a location hash for selecting templates
	 * Used when building templates to core_theme_templates_built and also when selecting
	 * from that same table.
	 *
	 * @param string $app
	 * @param string $location
	 * @param string $group
	 * @return	string	Md5 Key
	 */
	public static function makeBuiltTemplateLookupHash( string $app, string $location, string $group ): string
	{
		return md5( mb_strtolower( $app ) . ';' . mb_strtolower( $location ) . ';' . mb_strtolower( $group ) );
	}

	/**
	 * Clears theme files from \IPS\File and the store.
	 * @note    This does not remove rows from the theme database tables.
	 *
	 * @param int $bit    Bitwise options for files to remove
	 * @param string|null	$app
	 * @return	void
	 */
	public static function clearFiles( int $bit, ?string $app=null ): void
	{
		if ( $bit & static::TEMPLATES )
		{
			static::deleteCompiledTemplate( $app );
		}

		if ( $bit & static::CSS )
		{
			static::deleteCompiledCss( $app );
		}

		if ( $bit & static::IMAGES )
		{
			static::deleteCompiledResources( $app );
		}

		foreach( static::themes() as $id => $theme )
		{
			/* Remove files, but don't fail if we can't */
			try
			{
				File::getClass('core_Theme')->deleteContainer( 'set_resources_' . $theme->id );
				File::getClass('core_Theme')->deleteContainer( 'css_built_' . $theme->id );
			}
			catch( Exception $e ){}

			/* Clear map */
			$theme->resource_map = array();
			$theme->css_map = array();
			$theme->save();
		}

		/* ACP CSS and Resources */
		Settings::i()->changeValues( array( 'acp_css_map' => '[]' ) );
		unset( Store::i()->acp_resource_map );
	}

	/**
	 * Add CSS.
	 * A check is first made to ensure we're not overwriting an existing master CSS file.
	 *
	 * @param	array $data	Data to insert (app, location, path, name, content)
	 * @return	int		Insert Id
	 *@throws	OverflowException
	 * @throws	InvalidArgumentException
	 */
	public static function addCss( array $data ): int
	{
		if ( empty( $data['app'] ) OR empty( $data['location'] ) OR empty( $data['path'] ) OR empty( $data['name'] ) )
		{
			throw new InvalidArgumentException;
		}

		/* Check for existing */
		try
		{
			$check = Db::i()->select( 'css_id', 'core_theme_css', array(
				'css_app=? AND css_location=? AND css_path=? AND css_name=LOWER(?) AND css_set_id=?',
				mb_strtolower( $data['app'] ),
				mb_strtolower( $data['location'] ),
				mb_strtolower( $data['path'] ),
				mb_strtolower( $data['name'] ),
				$data['set_id'] ?? 0
			) )->first();

			/* This exists */
			throw new OverflowException;
		}
		catch( UnderflowException $e )
		{
			/* That's ok, it doesn't exist */
		}

		/* Insert */
		$insertId = Db::i()->insert( 'core_theme_css', array(
			'css_set_id'	 => $data['set_id'] ?? 0,
			'css_app'		 => mb_strtolower( $data['app'] ),
			'css_location'   => mb_strtolower( $data['location'] ),
			'css_path'		 => mb_strtolower( $data['path'] ),
			'css_name'       => mb_strtolower( $data['name'] ),
			'css_content'    => $data['content'],
			'css_updated' 	 => time(),
			'css_version'    => Application::load('core')->long_version,
		), TRUE );

		if ( empty( $data['set_id'] ) )
		{
			Db::i()->update( 'core_themes', ['set_css_updated' => time()] );
		}

		return $insertId;
	}

	/**
	 * Add a template
	 * A check is first made to ensure we're not overwriting an existing master template bit.
	 *
	 * @param	array $data	Data to insert (app, location, group, name, variables, content)
	 * @return	int		Insert Id
	 *@throws	OverflowException
	 * @throws	InvalidArgumentException
	 */
	public static function addTemplate( array $data ): int
	{
		if ( empty( $data['app'] ) OR empty( $data['location'] ) OR empty( $data['group'] ) OR empty( $data['name'] ) )
		{
			throw new InvalidArgumentException;
		}

		/* Check for existing and there is not existing template, then it will throw an an UnderflowException */
		try
		{
			$check = Db::i()->select( 'template_id, template_set_id', 'core_theme_templates', array(
				'template_app=? AND template_location=? AND template_group=? AND LOWER(template_name)=?',
				mb_strtolower( $data['app'] ),
				mb_strtolower( $data['location'] ),
				mb_strtolower( $data['group'] ),
				mb_strtolower( $data['name'] )
			) )->first();

			/* Master bit exists, skip */
			throw new OverflowException;
		}
		catch( UnderflowException $e )
		{
			/* Template doesn't exist, so it's all good bro */
		}

		/* Insert */
		$insertId = Db::i()->insert( 'core_theme_templates', array(
			'template_set_id'	  => 0,
			'template_app'		  => $data['app'],
			'template_location'   => $data['location'],
			'template_group'	  => $data['group'],
			'template_name'       => $data['name'],
			'template_data'       => $data['variables'],
			'template_content'    => $data['content'],
			'template_updated'	  => time(),
			'template_version'    => Application::load('core')->long_version,
		), TRUE );

		static::rebuildHookPointFlags( $data['app'] );

		return $insertId;
	}

	/**
	 * Add resource
	 * Adds a resource to each theme set
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @note	$data['content'] should be the raw binary data, not base64_encoded data
	 * @param	array $data	        Array of data (app, location, path, name, content, [plugin])
	 * @param boolean $addToMaster    Add to master set 0
	 * @return	void
	 *@throws	InvalidArgumentException
	 */
	public static function addResource( array $data, bool $addToMaster=FALSE ): void
	{
		if ( empty( $data['app'] ) OR empty( $data['location'] ) OR empty( $data['path'] ) OR empty( $data['name'] ) )
		{
			throw new InvalidArgumentException;
		}

		$name = static::makeBuiltTemplateLookupHash( $data['app'], $data['location'], $data['path'] ) . '_' . $data['name'];

		if ( $addToMaster )
		{
			Db::i()->insert( 'core_theme_resources', array(
                 'resource_set_id'   => 0,
                 'resource_app'      => $data['app'],
                 'resource_location' => $data['location'],
                 'resource_path'     => $data['path'],
                 'resource_name'     => $data['name'],
                 'resource_added'	  => time(),
                 'resource_filename' => NULL,
                 'resource_data'     => $data['content'],
             ) );
		}

		foreach(Theme::themes() as $id => $theme )
		{
			$resource = NULL;
			try
			{
				$resource = Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? and resource_app=? and resource_location=? and resource_path=? and resource_name=?', $theme->id, $data['app'], $data['location'], $data['path'], $data['name'] ) )->first();
			}
			catch( UnderflowException $ex ) { }

			if ( $resource !== NULL and isset( $resource['resource_user_edited'] ) )
			{
				if ( $resource['resource_user_edited'] )
				{
					continue;
				}
			}

			/* Clear out old rows */
			Db::i()->delete( 'core_theme_resources', array( 'resource_set_id=? and resource_app=? and resource_location=? and resource_path=? and resource_name=?', $theme->id, $data['app'], $data['location'], $data['path'], $data['name'] ) );

			$resourceMap = $theme->resource_map;

			if ( $data['content'] )
			{
				$saveName = $name;
				if ( $resource !== NULL and $resource['resource_data'] != $data['content'] )
				{
					/* This resource exists, so let us create a unique name to prevent cache issues */
					$ext = mb_substr( $name, ( mb_strrpos( $name, '.' ) + 1 ) );
					$saveName = mb_substr( $name, 0, ( mb_strrpos( $name, '.' ) ) ) . '_' . mt_rand() . '.' . $ext;
				}

				if( in_array( $data['app'], IPS::$ipsApps ) )
				{
					$path = '/static/resources/';
					if( !is_dir( ROOT_PATH . $path ) )
					{
						mkdir( ROOT_PATH . $path, IPS_FOLDER_PERMISSION, TRUE );
					}

					$fileName = $path . $data['app'] . '_' . $saveName;
					@file_put_contents( ROOT_PATH . $fileName, $data['content'] );
				}
				else
				{
					$fileName = (string) File::create( 'core_Theme', $saveName, $data['content'], 'set_resources_' . $theme->id, FALSE, NULL, FALSE );
				}

				Db::i()->insert( 'core_theme_resources', array(
						'resource_set_id'   => $theme->id,
						'resource_app'      => $data['app'],
						'resource_location' => $data['location'],
						'resource_path'     => $data['path'],
						'resource_name'     => $data['name'],
						'resource_added'	 => time(),
						'resource_filename' => $fileName,
						'resource_data'     => $data['content'],
				) );
			}

			$key = static::makeBuiltTemplateLookupHash($data['app'], $data['location'], $data['path']) . '_' . $data['name'];

			$resourceMap[ $key ] = $fileName;

			/* Update theme map */
			$theme->resource_map = $resourceMap;
			$theme->save();
		}
	}


	/**
	 * Remove templates completely from the system.
	 * Used by application manager, etc.
	 *
	 * @param string $app		Application Key
	 * @param string|null $location	Location
	 * @param string|null $group		Group
	 * @param int|null $plugin		Plugin ID
	 * @param bool $doAll		Delete all - by default only the master set is cleared
	 * @param string|null $template	Template name if you just want to do one template. If specified, deleteCompiledTemplate is NOT called and must be done manually
	 * @return	void
	 */
	public static function removeTemplates( string $app, string $location=NULL, string $group=NULL, int $plugin=NULL, bool $doAll=FALSE, string $template=NULL ): void
	{
		if ( !$template )
		{
			static::deleteCompiledTemplate( $app, $location, $group );
		}

		$where = array( array( 'template_app=?', $app ) );

		if ( $location !== NULL )
		{
			$where[] = array( 'template_location=?', $location );
		}

		if ( $group !== NULL )
		{
			$where[] = array( 'template_group=?', $group );
		}

		if ( $template !== NULL )
		{
			$where[] = array( 'template_name=?', $template );
		}

		/* Coming from build script */
		if ( !$doAll )
		{
			$where[] = array( 'template_set_id=0' );
		}

		Db::i()->delete( 'core_theme_templates', $where );
	}

	/**
	 * Remove CSS completely from the system.
	 * Used by application manager, etc.
	 *
	 * @param string $app		Application Key
	 * @param string|null $location	Location
	 * @param string|null $path		Group
	 * @param int|null $plugin		Plugin ID
	 * @param bool $doAll		Delete all - by default only the master set is cleared
	 * @param string|null $name		CSS file name if you just want to do one file. If specified, deleteCompiledCss is NOT called and must be done manually
	 * @return	void
	 */
	public static function removeCss( string $app, string $location=NULL, string $path=NULL, int $plugin=NULL, bool $doAll=FALSE, string $name=NULL ): void
	{
		if ( !$name )
		{
			static::deleteCompiledCss( $app, $location, $path );
		}

		$where = array( array( 'css_app=?', $app ) );

		if ( $location !== NULL )
		{
			$where[] = array( 'css_location=?', $location );
		}

		if ( $path !== NULL )
		{
			$where[] = array( 'css_path=?', $path );
		}

		if ( $name !== NULL )
		{
			$where[] = array( 'css_name=?', $name );
		}

		/* Coming from build script */
		if ( !$doAll )
		{
			$where[] = array( 'css_set_id=0' );
		}

		Db::i()->delete( 'core_theme_css', $where );
	}

	/**
	 * Remove resources completely from the system.
	 * Used by application manager, etc.
	 *
	 * @param string $app		Application Key
	 * @param string|null $location	Location
	 * @param string|null $path		Path
	 * @param int|null $plugin		Plugin ID
	 * @param bool $doAll		Delete all - by default only the master set is cleared
	 * @param string|null $name		Resource file name if you just want to do one file. If specified, deleteCompiledResources AND buildResourceMap are both NOT called and must be done manually
	 * @return void
	 */
	public static function removeResources( string $app, string $location=NULL, string $path=NULL, int $plugin=NULL, bool $doAll=FALSE, string $name=NULL ): void
	{
		if ( !$name )
		{
			static::deleteCompiledResources( $app, $location, $path );
		}

		$where = array( array( 'resource_app=?', $app ) );

		if ( $location !== NULL )
		{
			$where[] = array( 'resource_location=?', $location );
		}

		if ( $path !== NULL )
		{
			$where[] = array( 'resource_path=?', $path );
		}

		if ( $name !== NULL )
		{
			$where[] = array( 'resource_name=?', $name );
		}

		/* Coming from build script */
		if ( !$doAll )
		{
			$where[] = array( '(resource_set_id=0 OR resource_user_edited=0)' );
		}

		Db::i()->delete( 'core_theme_resources', $where );

		if ( !$name )
		{
			foreach( static::themes() as $id => $set )
			{
				$set->buildResourceMap( $app );
			}
		}
	}

	/**
	 * Remove any editor settings from this application
	 *
	 * @param string $app
	 * @return void
	 */
	public static function removeEditorSettings( string $app ) : void
	{
		Db::i()->delete( 'core_theme_editor_settings', [ 'setting_app=?', $app ] );

		/* Check the categories. If there are settings from another app, then we leave the category,
		and change the application key */
		$categories = iterator_to_array(
			Db::i()->select( '*', 'core_theme_editor_categories', [ 'cat_app=?', $app ] )
		);

		foreach( $categories as $category )
		{
			try
			{
				$otherSetting = Db::i()->select( '*', 'core_theme_editor_settings', [ 'setting_category_id=? and setting_app != ?', $category['cat_id'], $app ], 'setting_position', array( 0, 1 ) )->first();
				Db::i()->update( 'core_theme_editor_categories', [ 'cat_app' => null ], [ 'cat_id=?', $category['cat_id'] ] );
			}
			catch( UnderflowException )
			{
				Db::i()->delete( 'core_theme_editor_categories', [ 'cat_id=?', $category['cat_id'] ] );
			}
		}
	}

	/**
	 * Removes all customizations made by the theme editor except uploaded logos
	 *
	 * @return void
	 */
	public function removeThemeEditorCustomizations(): void
	{
		/* Delete the CSS file */
		Db::i()->delete( 'core_theme_css', [ 'css_set_id=? and css_location=? and css_path=? and css_name=?', $this->id, 'front', 'custom', '1-variables.css' ] );
		static::deleteCompiledCss( 'core', 'front', 'custom', '1-variables.css', Theme::i()->_id );

		/* Remove any other non logo data from the theme editor json */
		$data = $this->theme_editor_data;
		if ( is_array( $data ) )
		{
			if ( isset( $data['header'] ) )
			{
				unset( $data['header'] );
			}
		}

		$this->custom_css = '';
		$this->css_variables = null;
		$this->view_options = null;
		$this->theme_editor_data = json_encode( $data );
		$this->save();
	}

	/**
	 * Because css still has {resource} tags when built, and the building is done via the ACP,
	 * Tags without a set "location" parameter are set to "admin" incorrectly.
	 *
	 * @param string $css		CSS Text
	 * @param string $location   CSS Location
	 * @return  string	Fixed CSS
	 */
	public static function fixResourceTags( string $css, string $location ): string
	{
		preg_match_all( '#\{resource=([\'"])(\S+?)\\1([^\}]+?)?\}#i', $css, $items, PREG_SET_ORDER );

		foreach( $items as $id => $attr )
		{
			/* Has manually added params */
			if ( isset( $attr[3] ) )
			{
				if ( ! strstr( $attr[3], 'location=' ) )
				{
					$new = str_replace( $attr[3], $attr[3] . ' location="' . $location . '"', $attr[0] );

					$css = str_replace( $attr[0], $new, $css );
				}
			}
			else
			{
				$new = str_replace( '}',  ' location="' . $location . '"}', $attr[0] );
				$css = str_replace( $attr[0], $new, $css );
			}
		}

		return $css;
	}

	/**
	 * Inserts a built record
	 *
	 * @param array $css	css_* table data
	 * @return File|string
	 */
	protected static function writeCss( array $css ): File|string
	{
		$css['css_path']    = ( empty( $css['css_path'] ) or $css['css_path'] == '.' ) ? '.' : $css['css_path'];
		$functionName = "css_" . $css['css_app'] . '_' . $css['css_location'] . '_' . str_replace( array( '-', '/', '.' ), '_', $css['css_path'] . '_' . $css['css_name'] );

		if ( !function_exists( $functionName ) )
		{
			static::makeProcessFunction( static::fixResourceTags( $css['css_content'], $css['css_location'] ), $functionName, '', FALSE, TRUE );
		}

		$fqFunc		= 'IPS\\Theme\\'. $functionName;
		$content	= static::minifyCss( $fqFunc() );
		$name		= static::cssFilePrefix( $css['css_app'], $css['css_location'], $css['css_path'] ) . '_' . $css['css_name'];

		/* Replace any <fileStore.xxx> tags in the CSS */
		Output::i()->parseFileObjectUrls( $content );

		if( in_array( $css['css_app'], IPS::$ipsApps ) AND $css['css_path'] != 'custom' )
		{
			$path = '/static/css/'; // Applications are in the root on Cloud2
			if( !is_dir( ROOT_PATH. $path ) )
			{
				mkdir( ROOT_PATH . $path, IPS_FOLDER_PERMISSION, TRUE );
			}

			$cssFileName = $path . $name;
			$result = (bool) @file_put_contents( ROOT_PATH . $cssFileName, $content, LOCK_EX );

			/* Sometimes LOCK_EX is unavailable and throws file_put_contents(): Exclusive locks are not supported for this stream.
				While we would prefer an exclusive lock, it would be better to write the file if possible. */
			if( !$result )
			{
				@unlink( ROOT_PATH . $cssFileName );
				$result = (bool) @file_put_contents( ROOT_PATH . $cssFileName, $content );
			}

			@chmod( ROOT_PATH . $cssFileName, IPS_FILE_PERMISSION );
			return $cssFileName;
		}

		return File::create( 'core_Theme', $name, $content, 'css_built_' . $css['css_set_id'], FALSE, NULL, FALSE );
	}

	/**
	 * Minifies CSS
	 *
	 * @param string $content	Content to minify
	 * @return	string  $content	Minified
	 */
	public static function minifyCss( string $content ): string
	{
		/* Comments */
		$content = preg_replace( '#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $content );

		/* Multiple spaces, tabs and newlines */
		$content = str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $content );
		$content = preg_replace( '!\s+!', ' ', $content );

		/* Some more space removal */
		$content = str_replace( ' {', '{', $content );
		$content = str_replace( '{ ', '{', $content );
		$content = str_replace( ' }', '}', $content );
		$content = str_replace( '} ', '}', $content );
		$content = str_replace( '; ', ';', $content );
		$content = str_replace( ': ', ':', $content );

		return $content;
	}

	/**
	 * This method is executed when theme settings have changed from saveForm()
	 *
	 * @param int $setId		Theme set id
	 * @erturn  void
	 */
	public static function themeSettingsHaveChanged( int $setId ): void
	{
		if( Application::appIsEnabled( 'cms' ) )
		{
			Page::deleteCachedIncludes();
		}

		$themeSetToBuild = static::load( $setId );

		File::getClass('core_Theme')->deleteContainer( 'css_built_' . $themeSetToBuild->id );
		$themeSetToBuild->css_map = array();
	}

	/**
	 * Any custom images/css/templates?
	 *
	 * @return boolean
	 */
	public function isCustomized(): bool
	{
		if ( is_array( $this->set_theme_editor_data ) and count( $this->set_theme_editor_data ) )
		{
			return true;
		}

		if ( $this->custom_css )
		{
			return true;
		}

		if ( $this->css_variables )
		{
			return true;
		}

		if( $this->view_options )
		{
			return true;
		}

		return false;
	}

	/**
	 * Cache bust key for CSS
	 *
	 * @return string
	 */
	public function cssCacheBustKey(): string
	{
		return CACHEBUST_KEY . $this->css_updated;
	}

	/**
	 * Add a lock to prevent race conditions
	 *
	 * @param	string	$key 	Unique key to sign the lock
	 * @return	void
	 */
	public static function lock( string $key ): void
	{
		Db::i()->replace( 'core_cache', array(
			'cache_key'    => 'locking_' . $key,
			'cache_value'  => $key,
			'cache_expire' => time() + 30
		) );
	}

	/**
	 * Check a race condition lock
	 *
	 * @param	string	$key 	Unique key to sign the lock
	 * @return	boolean
	 */
	public static function checkLock( string $key ): bool
	{
		try
		{
			Db::i()->select( 'SQL_NO_CACHE *', 'core_cache', array( 'cache_key=? and cache_expire > ?', 'locking_' . $key, time() - 30 ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
			return true;
		}
		catch( Exception $ex )
		{
			return false;
		}
	}

	/**
	 * Remove a race condition lock
	 *
	 * @param	string	$key 	Unique key to sign the lock
	 * @return	void
	 */
	public static function unlock( string $key ): void
	{
		Db::i()->delete( 'core_cache', array( 'cache_key=?', 'locking_' . $key ) );
	}

	/**
	 * Get the hook names from a template's contents
	 *
	 * @param string $contents
	 * @return array
	 */
	public static function extractHookNames( string $contents ): array
	{
		$names = [];
		preg_match_all( '/data-ips-hook="([^"]+)"/', $contents, $matches, PREG_SET_ORDER );

		if ( $matches and is_array( $matches ) and count( $matches ) )
		{
			foreach( $matches as $index => $match )
			{
				$names[] = $match[1];
			}
		}

		return $names;
	}

	/**
	 * Start a theme editing session
	 *
	 * @param Member|null $member
	 * @return void
	 */
	public function editingStart( ?Member $member=null ): void
	{
		$member = $member ?: Member::loggedIn();
		$member->members_bitoptions['bw_using_skin_gen'] = 1;
		$member->save();

		$this->edit_in_progress = $member->member_id;
		$this->save();
	}

	/**
	 * Finish a theme editing session
	 *
	 * @param Member|null $member
	 * @return void
	 */
	public function editingFinish( ?Member $member=null ): void
	{
		$member = $member ?: Member::loggedIn();
		$member->members_bitoptions['bw_using_skin_gen'] = 0;
		$member->save();

		$this->edit_in_progress = 0;
		$this->save();

		/* Remove any theme_editor cookie */
		Request::i()->setCookie( 'theme_editor_vars', null );
	}

	/**
	 * Return the theme that is currently being edited by this member
	 * If no theme is being edited, an OutOfRangeException is thrown
	 *
	 * @param Member|null $member
	 * @return Theme
	 */
	public static function byEditingMember( ?Member $member=null ): Theme
	{
		$member = $member ?: Member::loggedIn();

		try
		{
			return static::load( $member->member_id, 'set_edit_in_progress' );
		}
		catch( Exception )
		{
			/* No theme currently being edited by this member, so turn off editing */
			$member->members_bitoptions['bw_using_skin_gen'] = 0;
			$member->save();

			throw new OutOfRangeException();
		}
	}
}