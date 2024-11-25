<?php
/**
 * @brief		Language Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DirectoryIterator;
use DomainException;
use ErrorException;
use InvalidArgumentException;
use IPS\cms\Databases;
use IPS\Data\Store;
use IPS\Db\Exception;
use IPS\Db\Select as DbSelect;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang\Setup\Lang as SetupLang;
use IPS\Lang\Upgrade\Lang as UpgradeLang;
use IPS\Node\Model;
use IPS\Output\Javascript;
use IPS\Output\System;
use IPS\Patterns\ActiveRecord;
use OutOfRangeException;
use Throwable;
use UnderflowException;
use UnexpectedValueException;
use function constant;
use function count;
use function defined;
use function floatval;
use function function_exists;
use function get_declared_classes;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function preg_replace_callback;
use function strlen;
use function strtolower;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Language Class
 */
class Lang extends Model
{
	/* !Lang - Static */

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'languages', 'listFormats', 'updatecount_languages', 'shortFormats' );

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('lang_id');

	/**
	 * @brief	Have fetched all?
	 */
	protected static bool $gotAll = FALSE;

	/**
	 * @brief	Default language ID
	 */
	protected static ?int $defaultLanguageId = NULL;

	/**
	 * @brief	Output lang stack
	 */
	public array $outputStack	= array();

	/**
	 * @brief	lang key salt
	 */
	protected static ?string $outputSalt	= NULL;

	/**
	 * @brief	Have all the words been loaded?
	 */
	protected bool $wordsLoaded	= FALSE;

	/**
	 * Load Record
	 *
	 * @see        Db::build
	 * @param	int|string|null	$id					ID
	 * @param	string|null		$idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param	mixed		$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details)
	 * @return	static|ActiveRecord
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function load( int|string|null $id, string $idField=NULL, mixed $extraWhereClause=NULL ): ActiveRecord|static
	{
		if( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation == 'front' AND $idField === NULL AND $extraWhereClause === NULL )
		{
			$languages = static::languages();

			if ( !isset( $languages[ $id ] ) )
			{
				throw new OutOfRangeException;
			}

			$languages[ $id ]->languageInit();
			return $languages[ $id ];
		}

		$result	= parent::load( $id, $idField, $extraWhereClause );
		$result->languageInit();

		return $result;
	}

	/**
	 * Get data store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->languages ) )
		{
			Store::i()->languages = iterator_to_array( Db::i()->select( '*', 'core_sys_lang', NULL, 'lang_order' )->setKeyField('lang_id') );
		}

		return Store::i()->languages;
	}

	/**
	 * Languages
	 *
	 * @param	null|DbSelect $iterator	Select iterator
	 * @return	array
	 */
	public static function languages( DbSelect $iterator=NULL ): array
	{
		if ( !static::$gotAll )
		{
			if( $iterator === NULL )
			{
				$rows	= static::getStore();
			}
			else
			{
				$rows	= iterator_to_array( $iterator );
			}

			foreach( $rows as $id => $lang )
			{
				if ( $lang['lang_default'] )
				{
					static::$defaultLanguageId = $lang['lang_id'];
				}
				static::$multitons[ $id ] = static::constructFromData( $lang );
			}

			static::$outputSalt = mt_rand();

			static::$gotAll	= TRUE;
		}
		return static::$multitons;
	}

	/**
	 * Get the enabled languages
	 *
	 * @param	null|DbSelect $iterator	Select iterator
	 * @return array
	 */
	public static function getEnabledLanguages( DbSelect $iterator=NULL ): array
	{
		$languages = static::languages($iterator);
		$enabledLanguages = array();
		foreach ( $languages AS $id => $lang )
		{
			if  ( $lang->enabled )
			{
				$enabledLanguages[$id] = $lang;
			}
		}

		return $enabledLanguages;
	}

	/**
	 * Get default language ID
	 *
	 * @return int|null
	 */
	public static function defaultLanguage(): ?int
	{
		if ( !static::$gotAll )
		{
			static::languages();
		}
		return static::$defaultLanguageId;
	}

	/**
	 * Get language object for installer
	 *
	 * @return SetupLang
	 */
	public static function setupLanguage(): SetupLang
	{
		$obj = new SetupLang;
		require ROOT_PATH . '/admin/install/lang.php';
		$obj->words = $lang;
		$obj->set_short( ( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) === 'WIN' ) ? 'english' : 'en_US' );
		$obj->wordsLoaded = TRUE;
		return $obj;
	}

	/**
	 * Add upgrader language bits
	 *
	 * @return    UpgradeLang
	 */
	public static function upgraderLanguage() : UpgradeLang
	{
		$obj = new UpgradeLang;
		require ROOT_PATH . '/admin/upgrade/lang.php';
		$obj->words = $lang;
		return $obj;
	}

	/**
	 * Auto detect language
	 *
	 * @param string $httpAcceptLanguage		HTTP Accept-Language header
	 * @return	int|NULL	ID Of preferred language or NULL if could not be autodetected
	 */
	public static function autoDetectLanguage( string $httpAcceptLanguage ): ?int
	{
		$preferredLanguage = NULL;

		if( mb_strpos( $httpAcceptLanguage, ',' ) )
		{
			$httpAcceptLanguage = explode( ',', $httpAcceptLanguage );
			$httpAcceptLanguage	= $httpAcceptLanguage[0];
		}
		$httpAcceptLanguage	= explode( '-', mb_strtolower( $httpAcceptLanguage ) );

		foreach ( static::languages() as $lang )
		{
			if( !$lang->enabled )
			{
				continue;
			}

			if ( preg_match( '/^\w{2}[-_]\w{2}($|\.)/i', $lang->short ) ) // This will only work for Unix-style locales
			{
				$langCode = strtolower( substr( $lang->short, 0, 2 ) );
				$countryCode = strtolower( substr( $lang->short, -2 ) );

				if ( $langCode === $httpAcceptLanguage[0] )
				{
					$preferredLanguage = $lang->id;

					/* Some browsers are silly and send HTTP_ACCEPT_LANGUAGE like this: en,en-US;q=0.9 */
					/* I'm looking at you, Opera */
					if ( isset( $httpAcceptLanguage[1] ) )
					{
						if ( $countryCode === $httpAcceptLanguage[1] )
						{
							break;
						}
					}
				}
			}
		}

		return $preferredLanguage;
	}

	/**
	 * Save translatable language strings
	 *
	 * @param string|Application $application	Application key
	 * @param string $key			Word key
	 * @param mixed $values			The values
	 * @param bool $js				Expose to JavaScript?
	 * @param string|null $default		If the administrator added this word, this is the default value.
	 * @return	void
	 */
	public static function saveCustom(Application|string $application, string $key, mixed $values, bool $js=FALSE, string $default=NULL ) : void
	{
		if( is_string( $application))
		{
			$application = Application::load( $application );
		}

		/* Values is a string, so use this value for all languages */
		$isCustom = FALSE;
		if ( $default !== NULL )
		{
			$isCustom = TRUE;
		}

		if ( !is_array( $values ) )
		{
			if ( $default === NULL )
			{
				$default = $values;
			}

			$values  = array();

			foreach ( static::languages() as $lang )
			{
				$values[ $lang->id ] = $default;
			}
		}
		else
		{
			if ( count( $values ) == 0  )
			{
				return;
			}
			foreach ( static::languages() as $lang )
			{
				if ( !isset( $values[ $lang->id ] ) )
				{
					$values[ $lang->id ] = '';
				}
				else if ( $lang->default AND $default === NULL )
				{
					$default = $values[ $lang->id ];
				}
			}
		}

		$currentValues = iterator_to_array( Db::i()->select( '*', 'core_sys_lang_words', array( 'word_key=?', $key ) )->setKeyField('lang_id') );
		foreach ( $values as $langId => $value )
		{
			if ( isset( $currentValues[ $langId ] ) )
			{
				Db::i()->update( 'core_sys_lang_words', array( 'word_default' => $default, 'word_custom' => $value ), array( 'lang_id=? AND word_key=?', $langId, $key ) );
			}
			else
			{
				$directory = $application->directory;

				$insert = array(
					'lang_id'		=> $langId,
					'word_app'		=>  is_string( $directory ) ? $directory : NULL,
					'word_key'		=> $key,
					'word_default'	=> $default,
					'word_custom'	=> $value,
					'word_js'		=> $js,
					'word_export'	=> FALSE,
					'word_is_custom'=> ( $isCustom === TRUE )
				);

				try
				{
					Db::i()->replace( 'core_sys_lang_words', $insert );
				}
				catch( Exception $e )
				{
					/* The upgrader can try to insert custom language strings before the word_is_custom column is added in 4.5 */
					if( $e->getCode() == 1054 )
					{
						unset( $insert['word_is_custom'] );

						Db::i()->replace( 'core_sys_lang_words', $insert );
					}
					else
					{
						throw $e;
					}
				}
			}

			if ( isset( static::$multitons[ $langId ] ) )
			{
				static::$multitons[ $langId ]->words[ $key ] = $value;
			}

			if ( $js )
			{
				Javascript::clearLanguage( static::load( $langId ) );
			}

			if ( $key === '_list_format_' )
			{
				unset( Store::i()->listFormats );
			}

			if ( substr( $key, 0, 10 ) === 'num_short_' )
			{
				unset( Store::i()->shortFormats );
			}
		}
	}

	/**
	 * Copy custom values to a different key
	 *
	 * @param string $app	Application Key
	 * @param string $key	Word key
	 * @param string $newKey	New Word Key
	 * @param string|null $newApp	New Application Key, if different
	 * @return	void
	 */
	public static function copyCustom( string $app, string $key, string $newKey, string $newApp=NULL ) : void
	{
		$values = array();
		foreach (Db::i()->select( 'lang_id, word_default, word_custom', 'core_sys_lang_words', array( 'word_app=? AND word_key=?', $app, $key ) )->setKeyField('lang_id') as $langId => $data )
		{
			$values[ $langId ] = $data['word_custom'] ?: $data['word_default'];
		}

		foreach( $values as $row )
		{
			static::saveCustom($newApp ?: $app, $newKey, $values);
		}
	}

	/**
	 * Delete translatable language strings
	 *
	 * @param string $app	Application key
	 * @param string $key	Word key
	 * @return	void
	 */
	public static function deleteCustom( string $app, string $key ) : void
	{
		Db::i()->delete( 'core_sys_lang_words', array( 'word_app=? AND word_key=?', $app, $key ) );
	}

	/**
	 * Validate a locale
	 *
	 * @param string $locale	The locale to test
	 * @return	void
	 * @throws	InvalidArgumentException
	 */
	public static function validateLocale( string $locale ) : void
	{
		if ( $locale != 'x' )
		{
			$success = FALSE;
			$currentLocale = setlocale( LC_ALL, '0' );
			foreach ( array( "{$locale}.UTF-8", "{$locale}.UTF8", $locale ) as $l )
			{
				$test = setlocale( LC_ALL, $l );

				if ( $test !== FALSE )
				{
					$success = TRUE;
					break;
				}
			}

			static::restoreLocale( $currentLocale );

			if ( $success === FALSE )
			{
				throw new InvalidArgumentException( 'lang_short_err' );
			}
		}
	}

	/**
	 * Import IN_DEV languages to the database
	 *
	 * @param string $app	Application directory
	 * @return void
	 */
	public static function importInDev( string $app ) : void
	{
		/* Import the language files */
		$lang = array();

		/* Get all installed languages */
		$languages = array_keys( Lang::languages() );
		$version   = Application::load( $app )->long_version;


		Db::i()->delete( 'core_sys_lang_words', array( 'word_app=? AND word_export=1', $app ) );

		$lang = static::readLangFiles( $app );
		foreach ( $lang as $k => $v )
		{
			$inserts = array();
			foreach( $languages as $languageId )
			{
				$inserts[]	= array(
					'word_app'				=> $app,
					'word_key'				=> $k,
					'lang_id'				=> $languageId,
					'word_default'			=> $v,
					'word_custom'			=> NULL,
					'word_default_version'	=> $version,
					'word_custom_version'	=> NULL,
					'word_js'				=> 0,
					'word_export'			=> 1,
				);
			}

			Db::i()->replace( 'core_sys_lang_words', $inserts );
		}

		$lang = static::readLangFiles( $app, true );
		foreach ( $lang as $k => $v )
		{
			$inserts = array();
			foreach( $languages as $languageId )
			{
				$inserts[]	= array(
					'word_app'				=> $app,
					'word_key'				=> $k,
					'lang_id'				=> $languageId,
					'word_default'			=> $v,
					'word_custom'			=> NULL,
					'word_default_version'	=> $version,
					'word_custom_version'	=> NULL,
					'word_js'				=> 1,
					'word_export'			=> 1,
				);
			}

			Db::i()->replace( 'core_sys_lang_words', $inserts );
		}
	}

	/**
	 * Read all language strings for the specific application
	 *
	 * @param string $app
	 * @param bool $js
	 * @return array
	 */
	public static function readLangFiles( string $app, bool $js=false ) : array
	{
		$_lang = array();

		/* First check the main file */
		$file = ROOT_PATH . "/applications/{$app}/dev/" . ( $js ? "js" : "" ) . "lang.php";
		if ( file_exists( $file ) )
		{
			require $file;
			if( isset( $lang ) )
			{
				$_lang = array_merge( $_lang, $lang );
				unset( $lang );
			}

		}

		/* Check for additional files */
		$langDirectory = ROOT_PATH . "/applications/{$app}/dev/" . ( $js ? "js" : "" ) . "lang";
		if( file_exists( $langDirectory ) )
		{
			foreach( new DirectoryIterator( $langDirectory ) as $file )
			{
				if( !$file->isDir() and !$file->isDot() )
				{
					/* Make sure it's PHP! */
					$extension = strtolower( substr( $file->getFilename(), strrpos( $file->getFilename(), '.' ) + 1 ) );
					if( $extension == 'php' )
					{
						require $langDirectory . "/" . $file->getFilename();
						if( isset( $lang ) )
						{
							$_lang = array_merge( $_lang, $lang );
							unset( $lang );
						}
					}
				}
			}
		}

		return $_lang;
	}

	/* !Lang - Instance */

	/**
	 * @brief	Locale data
	 */
	public array $locale = array();

	/**
	 * @brief	Codepage used, if Windows
	 */
	public ?string $codepage = NULL;

	/**
	 * Set the appropriate locale
	 *
	 * @return	void
	 * @note	<a href='https://bugs.php.net/bug.php?id=18556'>Turkish and some other locales do not work properly</a>
	 */
	public function setLocale() : void
	{
		$result	= setlocale( LC_ALL, $this->short );

		/* Some locales in some PHP versions break things drastically */
		if( in_array( 'ips\\db\\_select', get_declared_classes() ) AND !in_array( 'IPS\\Db\\_Select', get_declared_classes() ) )
		{
			setlocale( LC_CTYPE, 'en_US.UTF-8' );
		}

		/* If this is Windows, store the codepage as we will need it again later */
		if( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) === 'WIN' )
		{
			$codepage	= preg_replace( "/^(.+?)\.(.+?)$/i", "$2", $result );

			if( $codepage !== $result )
			{
				$this->codepage	= $codepage;
			}
		}
	}

	/**
	 * Restore a previous locale
	 *
	 * @param string $previousLocale	Value from setlocale( LC_ALL, '0' )
	 * @return	void
	 */
	public static function restoreLocale( string $previousLocale ) : void
	{
		foreach( explode( ";", $previousLocale ) as $locale )
		{
			if( mb_strpos( $locale, '=' ) !== FALSE )
			{
				$parts = explode( "=", $locale );
				if( in_array( $parts[0], array( 'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME' ) ) )
				{
					setlocale( constant( $parts[0] ), $parts[1] );
				}
			}
			else
			{
				setlocale( LC_ALL, $locale );
			}
		}
	}

	/**
	 * @brief Cached preferred date format
	 */
	protected ?string $preferredDateFormat	= NULL;

	/**
	 * Get the preferred date format for this locale
	 *
	 * @return	string|null
	 */
	public function preferredDateFormat(): ?string
	{
		if( $this->preferredDateFormat === NULL )
		{
			/* Make sure the locale has been set, important for things like the js date_format variable */
			$this->setLocale();

			$date = new DateTime('1992-03-04');
			$this->preferredDateFormat = str_replace( array( '1992', '92', '03', '3', $date->strFormat('%B'), $date->strFormat('%b'), '04', ' 4', '4' ), array( 'YY', 'YY', 'MM', 'MM', 'MM', 'MM', 'DD', 'DD', 'DD' ), $date->localeDate() );
		}

		return $this->preferredDateFormat;
	}

	/**
	 * Convert the character set for locale-aware strings on Windows systems
	 *
	 * @param string $text	Text to convert
	 * @return	string
	 */
	public function convertString( string $text ): string
	{
		/* We only do this on Windows */
		if( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) !== 'WIN' )
		{
			return $text;
		}

		/* And only if iconv() exists */
		if( !function_exists( 'iconv' ) )
		{
			return $text;
		}

		/* And only if we have a codepage stored */
		if( !$this->codepage )
		{
			return $text;
		}

		/* Convert the codepage to UTF-8 (if it is valid) and return */
		try
		{
			if( ( $converted = iconv( "CP" . $this->codepage, "UTF-8", $text ) ) !== FALSE )
			{
				return $converted;
			}
			else
			{
				throw new ErrorException;
			}
		}
		catch( ErrorException $e )
		{
			return $text;
		}
	}

	/**
	 * @brief	Words
	 */
	public array $words = array();

	/**
	 * @brief	Original Words
	 */
	public array $originalWords = array();

	/**
	 * Check Keys Exist
	 *
	 * @param string $key	Language key
	 * @return	bool
	 */
	public function checkKeyExists( string $key ): bool
	{
		if( isset( $this->words[ $key ] ) )
		{
			return TRUE;
		}
		else if ( array_key_exists( $key, $this->words ) and $this->words[ $key ] === NULL )
		{
			/* Language key has been preloaded but does not exist */
			return FALSE;
		}
		else if( $this->wordsLoaded or IN_DEV)
		{
			return FALSE;
		}

		try
		{
			$lang = Db::i()->select( 'word_key, word_default, word_custom', 'core_sys_lang_words', array( 'lang_id=? AND word_key=?', Member::loggedIn()->language()->id, $key ) )->first();

			$value = $lang['word_custom'] ?: $lang['word_default'];

			$this->words[ $key ] = $value;

			return TRUE;
		}
		catch ( UnderflowException $e )
		{
			return FALSE;
		}
	}

	/**
	 * Get Language String
	 *
	 * @param array|string $key	Language key or array of keys
	 * @return	string|array|null			Language string or array of key => string pairs
	 */
	public function get( array|string $key ): array|string|null
	{
		$return     = array();
		$keysToLoad = array();

		if ( is_array( $key ) )
		{
			foreach( $key as $k )
			{
				if ( in_array( $k, array_keys( $this->words ), true ) )
				{
					$return[ $k ] = $this->words[ $k ];
				}
				else
				{
					$keysToLoad[] = "'" . Db::i()->real_escape_string( $k ) . "'";
				}
			}

			if ( !count( $keysToLoad ) )
			{
				return $return;
			}
		}
		else
		{
			if ( isset( $this->words[ $key ] ) )
			{
				return $this->words[ $key ];
			}

			$keysToLoad = array( "'" . Db::i()->real_escape_string( $key ) . "'" );
		}

		foreach(Db::i()->select( 'word_key, word_default, word_custom', 'core_sys_lang_words', array( "lang_id=? AND word_key IN(" . implode( ",", $keysToLoad ) . ")", ( $this->id ?? static::defaultLanguage() ) ) ) as $lang )
		{
			$value = $lang['word_custom'] ?: $lang['word_default'];

			$this->words[ $lang['word_key'] ] = $value;
			$return[ $lang['word_key' ] ]     = $value;
		}

		/* If we're using an array, fill any missings strings with NULL to prevent duplicate queries */
		if ( is_array( $key ) )
		{
			foreach( $key as $k )
			{
				if ( !in_array( $k, $return ) and ! array_key_exists( $k, $this->words ) )
				{
					$return[ $k ] = NULL;
					$this->words[ $k ] = NULL;
				}
			}
		}

		if ( !count( $return ) )
		{
			throw new UnderflowException( ( is_string( $key ) ? 'lang_not_exists__' . $key : 'lang_not_exists__' . implode( ',', $key ) ) );
		}

		return is_string( $key ) ? $this->words[ $key ] : $return;
	}

	/**
	 * Add to output stack
	 *
	 * @param string $key	Language key
	 * @param bool|null $vle	Add VLE tags?
	 * @param array $options Options
	 * @return	string	Unique id
	 */
	public function addToStack( string $key, ?bool $vle=TRUE, array $options=array() ): string
	{
		/* Setup? */
		if( $this->wordsLoaded === TRUE )
		{
			return ( isset( $this->words[ $key ] ) ) ? $this->words[ $key ] : $key;
		}

		/* Get it */
		if( isset( $this->outputStack[ $key ] ) )
		{
			return htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
		}

		$id = md5( 'ipslang_' . static::$outputSalt . $key . json_encode( $options ) );
		$this->outputStack[ $id ]['key']		= $key;
		$this->outputStack[ $id ]['options']	= $options;
		$this->outputStack[ $id ]['vle']		= $vle;

		/* Return */
		return $id;
	}

	/**
	 * Pluralize
	 *
	 * @param string $string	Language string to pluralize
	 * @param array $params	Parameters to pluraizlie with
	 * @param string|null $formatter	Number formatter (default is formatNumber)
	 * @return	string
	 * @note	You can use the following wildcards to do special things
	 * @li	? is a fallback, so anything not matched will use it
	 * @li	* is a beginning wildcard, so anything that ENDS with the number supplied will match
	 * @li	% is an ending wildcard, so anything that BEGINS with the number supplied will match
	 * @li	# (optional) will be replaced with the actual value
	 * @code
	 * {# [1:test][*2:tests][%3:no tests][?:finals]} will result in 1 test, 2 tests, 12 tests, 3 no tests, 35 no tests, 8 finals
	 * {!1#[1:January][2:February][3:March][4:April][5:May][6:June][7:July][8:August][9:September][10:October][11:November][12:December]} {0#}
	 * {!#[?:%s liked]} %s
	 * @endcode
	 */
	public function pluralize( string $string, array $params, string $formatter=NULL ): string
	{
		$i = 0;
		$openCurly  = '--' . mt_rand() . '--';
		$closeCurly = '--' . mt_rand() . '--';

		/* Prevent nested { } breaking the syntax */
		$string = preg_replace_callback( '/(\{!(?:.+?)?(?:\d+?)?\#\[)(.*?)(\]\})/', function( $matches ) use ( $openCurly, $closeCurly )
		{
			$replaced = str_replace( '{', $openCurly, $matches[2] );
			$replaced = str_replace( '}', $closeCurly, $replaced );
			return $matches[1] . $replaced . $matches[3];
		}, $string );

		$numberFormatter = array( $this, 'formatNumber' );
		return preg_replace_callback( '/\{!?(\d+?)?#(.*?)\}/', function( $format ) use ( $params, $i, $numberFormatter, $openCurly, $closeCurly, $formatter )
		{
			$originalNumber = $format[1];
			if ( !$format[1] or $format[1] == '!' )
			{
				$format[1] = $i;
				$i++;
			}

			/* Format now so that 0 is really 0 and not '' or null */
			$preFormattedNumber = $params[ $format[1] ]; // We need 7300 later, not 7,3000
			$params[ $format[1] ] = $numberFormatter( $params[ $format[1] ] );
			$fallback = NULL;
			$value = NULL;
			$token = '--' . mt_rand() . '--';

			/* We want to ensure that manually escaped # are not switched */
			$format[2] = str_replace( '\#', $token, $format[2] );

			/* This regex is tricky: It matches [ followed by anything NOT : until :, then any character, then everything that is not a [ until ] */
			preg_match_all( '/\[([^:]+):(.[^\[]*)\]/', $format[2], $matches );

			foreach ( $matches[1] as $k => $v )
			{
				if ( $v == '?' )
				{
					$fallback = preg_replace( '/(?!&)#(?!\d{2,4};)/', $params[ $format[1] ], $matches[2][ $k ] );
				}
				elseif( ( mb_substr( $v, 0, 1 ) === '%' and ( mb_substr( $v, 1 ) == $params[ $format[1] ] ) ) )
				{
					$value = preg_replace( '/(?!&)#(?!\d{2,4};)/', $params[ $format[1] ], $matches[2][ $k ] );
					// We don't break in case there is a better match
				}
				elseif( ( mb_substr( $v, 0, 1 ) === '*' and ( mb_substr( $v, -( mb_strlen( mb_substr( $v, 1 ) ) ) ) == mb_substr( $params[ $format[1] ], -( mb_strlen( mb_substr( $v, 1 ) ) ) ) ) ) )
				{
					$value = preg_replace( '/(?!&)#(?!\d{2,4};)/', $params[ $format[1] ], $matches[2][ $k ] );
					// We don't break in case there is a better match
				}
				elseif ( ( $v === $params[ $format[1] ] ) )
				{
					$value = preg_replace( '/(?!&)#(?!\d{2,4};)/', $params[ $format[1] ], $matches[2][ $k ] );

					break;
				}
			}

			$finalFormattedNumber = ( $formatter == 'short' ) ? $this->formatNumberShort( $preFormattedNumber ) : $params[ $format[1] ];
			$return = rtrim( ltrim( $format[0], '{' ), '}' );
			$return = str_replace( "!{$originalNumber}#", '', $return );
			$return = str_replace( array( "{$format[1]}#", '#' ), $finalFormattedNumber, $return );
			$return = preg_replace( '/\[.+\]/', ( $value === NULL ? $fallback : $value ), $return );

			$return = str_replace( $token, '#', $return );
			$return = str_replace( $openCurly, '{', $return );
			return str_replace( $closeCurly, '}', $return );
		}, $string );
	}

	/**
	 * Format Number
	 *
	 * @param	number	$number		The number to format
	 * @param int $decimals	Number of decimal places
	 * @return	string
	 */
	public function formatNumber( $number, int $decimals=0 ): string
	{
		return number_format( floatval( $number ), floatval( $decimals ), $this->locale['decimal_point'], $this->locale['thousands_sep'] );
	}

	/**
	 * Format a number in a short format (e.g. 1.6k, instead of 1,558)
	 *
	 * @param	number	$number			The number to format
	 * @param int $postDecimal	Number of numbers to show after the decimal (1.1k, 1.12k, etc)
	 * @return	string
	 */
	public function formatNumberShort( $number, int $postDecimal=1 ): string
	{
		$lang = NULL;
		$origNumber = $number;

		if (IN_DEV)
		{
			foreach( array( 'num_short_billion', 'num_short_million', 'num_short_thousand' ) as $word )
			{
				$format[ $word ] = $this->words[ $word ];
			}
		}
		else
		{
			if ( !isset( Store::i()->shortFormats ) )
			{
				$formats = array();
				foreach (Db::i()->select( array( 'lang_id', 'word_key', 'word_custom', 'word_default' ), 'core_sys_lang_words', array( Db::i()->in('word_key', array( 'num_short_billion', 'num_short_million', 'num_short_thousand' ) ) ) ) as $row )
				{
					$formats[ $row['lang_id'] ][ $row['word_key'] ] = $row['word_custom'] ?: $row['word_default'];
				}
				Store::i()->shortFormats = $formats;
			}

			$format = Store::i()->shortFormats[ $this->id ];
		}

		/* Hundreds */
		if ( $number >= 1000 )
		{
			$number = round( $number, -3 + $postDecimal );
		}

		/* 1 billion or more */
		if ( $number >= 1000000000 )
		{
			$number = number_format( $number / 1000000000, $postDecimal, $this->locale['decimal_point'], $this->locale['thousands_sep'] );
			$lang = 'num_short_billion';
		}

		/* all the millions */
		else if ( $number >= 1000000 )
		{
			$number = number_format( $number / 1000000, $postDecimal, $this->locale['decimal_point'], $this->locale['thousands_sep'] );
			$lang = 'num_short_million';
		}

		/* 1,000 to 999,000 */
		else if ( $number >= 1000 )
		{
			$number = number_format( $number / 1000, $postDecimal, $this->locale['decimal_point'], $this->locale['thousands_sep'] );
			$lang = 'num_short_thousand';
		}

		// Ensure we don't return 1.0, but rather 1
		if ( substr( $number, -2 ) === $this->locale['decimal_point'] . "0" )
		{
			$number = substr($number, 0, -2);
		}

		if ( $lang )
		{
			return sprintf( $format[ $lang ], $number );
		}

		return $origNumber;
	}

	/**
	 * Format List
	 * Takes an array and returns a string, appropriate for the language (e.g. "a, b and c")
	 *
	 * Relies on the _list_format_ language string which should be an example list of three items using the keys a, b and c.
	 * Any can be capitalised to run ucfirst on that item
	 *
	 * Examples if $items = array( 'foo', 'bar', 'baz', 'moo' );
	 *	If _list_format_ is this:			Output will be this:
	 *	a, b and c							foo, bar, baz and moo
	 *	A, B und C							Foo, Bar, Baz und Moo
	 *	a; b; c.							foo; bar; baz; moo.
	 *
	 * @param array $items	The items for the list
	 * @param string|null $format	If provided, will override _list_format_
	 * @return	string
	 */
	public function formatList( array $items, string $format=NULL ): string
	{
		$items = array_values( $items );

		if ( $format === NULL )
		{
			if (IN_DEV)
			{
				$format = $this->words['_list_format_'];
			}
			else
			{
				if ( !isset( Store::i()->listFormats ) )
				{
					$formats = array();
					foreach (Db::i()->select( array( 'lang_id', 'word_custom', 'word_default' ), 'core_sys_lang_words', array( 'word_key=?', '_list_format_' ) ) as $row )
					{
						$formats[ $row['lang_id'] ] = $row['word_custom'] ?: $row['word_default'];
					}
					Store::i()->listFormats = $formats;
				}
				$format = Store::i()->listFormats[ $this->id ];
			}
		}

		preg_match( '/(^|^(.+?)\s)\b(a)\b(.+?\s?)\b(b)\b(.+?\s?)\b(c)\b(.+?)?$/i', $format, $matches );

		$return = $matches[1];
		for ($i = 0; $i< count( $items ); $i++ )
		{
			/* Pluralize can be used after, so avoid triggering the '#', '#[...]' syntax there */
			$items[ $i ] = preg_replace( '/#(\s|\[)/', '\#\1', $items[ $i ] );
			$upper = FALSE;
			if ( $i == 0 )
			{
				$upper = ( $matches[3] === 'A' );
			}
			elseif ( $i == count( $items ) - 1 )
			{
				$upper = ( $matches[7] === 'C' );
			}
			else
			{
				$upper = ( $matches[5] === 'B' );
			}

			$return .= ( $upper ? ucfirst( $items[ $i ] ) : $items[ $i ] );

			if ( $i == count( $items ) - 2 )
			{
				$return .= $matches[6];
			}
			elseif ( $i != count( $items ) - 1 )
			{
				$return .= $matches[4];
			}
		}
		if ( isset( $matches[8] ) )
		{
			$return .= $matches[8];
		}

		return $return;
	}

	/**
	 * Search translatable language strings
	 *
	 * @param string $prefix				Prefix used
	 * @param string $query				Search query
	 * @param bool $alsoSearchDefault	If TRUE, will also search the default value
	 * @return	array
	 */
	public function searchCustom( string $prefix, string $query, bool $alsoSearchDefault=FALSE ): array
	{
		$return = array();

		$where = array();
		$where[] = array( "lang_id=?", $this->id );
		$where[] = array( "word_key LIKE CONCAT( ?, '%' )", $prefix );
		if ( $alsoSearchDefault )
		{
			$where[] = array( "word_custom LIKE CONCAT( '%', ?, '%' ) OR ( word_custom IS NULL AND word_default LIKE CONCAT( '%', ?, '%' ) )", $query, $query );
		}
		else
		{
			$where[] = array( "word_custom LIKE CONCAT( '%', ?, '%' )", $query );
		}

		foreach (Db::i()->select( '*', 'core_sys_lang_words', $where ) as $row )
		{
			$return[ mb_substr( $row['word_key'], mb_strlen( $prefix ) ) ] = $this->get( $row['word_key'] );
		}

		return $return;
	}

	/**
	 * BCP 47
	 *
	 * @return	string
	 * @see		<a href="https://tools.ietf.org/html/bcp47">BCP 47 - Tags for Identifying Languages</a>
	 */
	public function bcp47(): string
	{
		if ( preg_match( '/^([a-z]{2})[-_]([a-z]{2})(.utf-?8)?$/i', $this->short, $matches ) )
		{
			return mb_strtolower( $matches[1] ) . '-' . mb_strtoupper( $matches[2] );
		}
		else
		{
			return mb_substr( $this->short, 0, 2 );
		}
	}

	/* !Node */

	/**
	 * @brief	Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'order';

	/**
	 * @brief	Node Title
	 */
	public static ?string $nodeTitle = 'menu__core_languages_languages';

	/**
	 * @brief	ACP Restrictions
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'languages',
		'all'		=> 'lang_packs'
	);

	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = TRUE;

	/**
	 * Search
	 *
	 * @param string $column	Column to search
	 * @param string $query	Search query
	 * @param string|null $order	Column to order by
	 * @param mixed $where	Where clause
	 * @return	array
	 */
	public static function search( string $column, string $query, string $order=NULL, mixed $where=array() ): array
	{
		if ( $column === '_title' )
		{
			$column = 'lang_title';
		}
		if ( $order === '_title' )
		{
			$order = 'lang_title';
		}
		return parent::search( $column, $query, $order, $where );
	}

	/**
	 * [Node] Does the currently logged in user have permission to add children for this node?
	 *
	 * @return	bool
	 */
	public function canAdd(): bool
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		return !$this->default;
	}


	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Text( 'lang_title', $this->title, TRUE, array( 'maxLength' => 255 ) ) );
		$this->localeField( $form, $this->id ? $this->short : 'en_US' );
		$form->add( new Select( 'lang_isrtl', $this->isrtl, FALSE, array( 'options' => array( FALSE => 'lang_isrtl_left', TRUE => 'lang_isrtl_right' ) ) ) );

		if ( !$this->default )
		{
			$form->add( new YesNo( 'lang_default', $this->default, FALSE ) );
		}
	}

	/**
	 * Add locale field to form
	 *
	 * @param Form $form		The form
	 * @param string $current	The current locale
	 * @return	void
	 */
	public static function localeField( Form &$form, string $current='en_US' ) : void
	{
		$commonLocales = json_decode( file_get_contents( ROOT_PATH . '/system/Lang/locales.json' ), TRUE );
		natcasesort( $commonLocales );
		foreach ( $commonLocales as $k => $v )
		{
			try
			{
				static::validateLocale( $k );
			}
			catch ( InvalidArgumentException $e )
			{
				unset( $commonLocales[ $k ] );
			}
		}

		if ( !empty( $commonLocales ) )
		{
			$form->add( new Select( 'lang_short', array_key_exists( preg_replace( '/^(.+?)\..+?$/', '$1', $current ), $commonLocales ) ? preg_replace( '/^(.+?)\..+?$/', '$1', $current ) : 'x', TRUE, array(
				'options'	=> array_merge( $commonLocales, array( 'x' =>  Member::loggedIn()->language()->addToStack('lang_short_other') ) ),
				'toggles'	=> array( 'x' => array( 'locale_custom' ) ),
				'parse'		=> 'raw'
			), '\IPS\Lang::validateLocale' ) );
		}
		else
		{
			$form->hiddenValues['lang_short'] = 'x';
		}

		$form->add( new Text( 'lang_short_custom', !in_array( $current, $commonLocales ) ? $current : NULL, FALSE, array( 'placeholder' => 'en_US' ), '\IPS\Lang::validateLocale', NULL, NULL, 'locale_custom' ) );
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( isset( $values['lang_short_custom'] ) )
		{
			if ( !isset($values['lang_short']) OR $values['lang_short'] === 'x' )
			{
				$values['lang_short'] = $values['lang_short_custom'];
			}
			unset( $values['lang_short_custom'] );
		}

		if( isset( $values['lang_short'] ) )
		{
			$currentLocale	= setlocale( LC_ALL, '0' );
			$localesToTest	= ( mb_strtolower( mb_substr( PHP_OS, 0, 3 ) ) !== 'win' ) ? array( "{$values['lang_short']}.UTF-8", "{$values['lang_short']}.UTF8" ) : array( $values['lang_short'] );

			foreach ( $localesToTest as $l )
			{
				$test = setlocale( LC_ALL, $l );
				if ( $test !== FALSE )
				{
					$values['lang_short'] = $l;
					break;
				}
			}

			static::restoreLocale( $currentLocale );
		}

		foreach ( $values as $k => $v )
		{
			$this->_data[ $k ] = $v;
			$this->changed[ mb_substr( $k, 5 ) ] = $v;
		}

		if( isset( $values['lang_default'] ) and $values['lang_default'] )
		{
			$this->enabled = TRUE;
			Db::i()->update( 'core_sys_lang', array( 'lang_default' => 0 ) );
		}

		return $values;
	}

	/**
	 * Get title
	 *
	 * @return	string
	 */
	public function get__title(): string
	{
		return $this->title;
	}

	/**
	 * Get Icon
	 *
	 * @return	string
	 * @note	Works on Unix systems. Partial support for Windows systems.
	 */
	public function get__icon(): mixed
	{
		return "ipsFlag ipsFlag-{$this->getCountry()}";
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

		if ( $this->update_data )
		{
			$data	= json_decode( $this->update_data, TRUE );

			if( !empty( $data['longversion'] ) AND $data['longversion'] > $this->version_long )
			{
				$released	= NULL;

				if( $data['released'] AND intval( $data['released'] ) == $data['released'] AND strlen( $data['released'] ) == 10 )
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
	 * [Node] Get Description
	 *
	 * @return	string|null
	 */
	protected function get__description(): ?string
	{
		return Theme::i()->getTemplate( 'customization', 'core' )->langDescription( $this );
	}

	/**
	 * Return country code
	 *
	 * @return	string
	 */
	protected function getCountry(): string
	{
		/* We may need to remap some entries for Windows */
		if( mb_strtolower( mb_substr( PHP_OS, 0, 3 ) ) === 'win' )
		{
			/* If it's just "english", consider that US */
			if( mb_strtolower( $this->short ) === 'english' )
			{
				return 'us';
			}
			elseif( mb_strtolower( $this->short ) === 'german' )
			{
				return 'de';
			}

			if( mb_strpos( $this->short, '_' ) !== FALSE )
			{
				$pieces = explode( '_', $this->short );

				if( mb_strpos( mb_strtolower( $pieces[0] ), 'chinese' ) === 0 )
				{
					return 'cn';
				}
				elseif( mb_strtolower( $pieces[0] ) === 'english' AND mb_strtolower( $pieces[1] ) === 'uk' )
				{
					return 'gb';
				}
			}
		}

		return ( mb_strpos( $this->short, '_' ) !== FALSE ) ? mb_strtolower( mb_substr( $this->short, mb_strpos( $this->short, '_' ) + 1, 2 ) ) : mb_strtolower( mb_substr( $this->short, 0, 2 ) );
	}

	/**
	 * Get enabled
	 *
	 * @return	bool|null
	 */
	public function get__enabled(): ?bool
	{
		return $this->enabled;
	}

	/**
	 * Get locked
	 *
	 * @return	bool
	 */
	public function get__locked(): bool
	{
		return (bool) $this->default;
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
	public function getButtons( Url $url, bool $subnode=FALSE ):array
	{
		$buttons = array();

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) and ( !isset( Request::i()->cookie['vle_editor'] ) or Request::i()->cookie['vle_editor'] == 0 ) )
		{
			$buttons['translate'] = array(
				'icon'	=> 'globe',
				'title'	=> 'lang_translate',
				'link'	=> Url::internal( "app=core&module=languages&controller=languages&do=translate&id={$this->_id}" ),
			);
		}

		$buttons = array_merge( $buttons, parent::getButtons( $url, $subnode ) );

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			$buttons['upload'] = array(
				'icon'	=> 'upload',
				'title'=> 'upload_new_version',
				'link'	=> Url::internal( "app=core&module=languages&controller=languages&do=uploadNewVersion&id={$this->_id}" ),
				'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->get('upload_new_version') )
			);
		}

		if ( $this->canEdit() )
		{
			$buttons['download'] = array(
				'icon'	=> 'download',
				'title'	=> 'download',
				'link'	=> Url::internal( "app=core&module=languages&controller=languages&do=download&id={$this->_id}" ),
				'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $this->_title )
			);
		}

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
		{
			$buttons[] = array(
				'icon'	=> 'user',
				'title'	=> 'language_set_members',
				'link'	=> $url->setQueryString( array( 'do' => 'setMembers', 'id' => $this->default ? 0 : $this->_id ) ),
				'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $this->_title )
			);
		}

		if (IN_DEV)
		{
			$buttons['devimport'] = array(
				'icon'	=> 'cogs',
				'title'	=> 'lang_dev_import',
				'link'	=> Url::internal( "app=core&module=languages&controller=languages&do=devimport&id={$this->_id}" )->csrf(),
			);
		}

		return $buttons;
	}

	/* !ActiveRecord */

	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Default Values
	 */
	protected static array $defaultValues = array(
		'lang_id'		=> 0,
		'lang_short'	=> 'en_US',
		'lang_title'	=> 'English (USA)',
		'lang_default'	=> TRUE,
		'lang_isrtl'	=> FALSE,
		'lang_protected'=> FALSE,
		'lang_order'	=> 0,
		'lang_enabled'	=> TRUE
	);

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_sys_lang';

	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'lang_';

	/**
	 * @brief	Has been initialized?
	 */
	protected bool $_initialized = FALSE;

	/**
	 * Set words
	 *
	 * @return	void
	 */
	public function languageInit() : void
	{
		/* Only initialize once */
		if( $this->_initialized === TRUE )
		{
			return;
		}

		$this->_initialized	= TRUE;

		/* Set locale data */
		$this->set_short( $this->short );

		/* Get values from developer files */
		if (IN_DEV)
		{
			/* Apps */
			try
			{
				foreach (Application::applications() as $app )
				{
					$this->words = array_merge( $this->words, static::readLangFiles( $app->directory ) );
				}
			}
			catch( UnexpectedValueException $ex )
			{
				System::i()->error( $ex->getMessage(), 500 );
			}

			/* Allow custom strings to override the default strings */
			foreach(Db::i()->select( 'word_key, word_default, word_custom', 'core_sys_lang_words', array( 'lang_id=? and word_export=?', $this->id, '0' ) ) as $bit )
			{
				$this->words[ $bit['word_key'] ]	= $bit['word_custom'] ?: $bit['word_default'];
			}
		}

		/* Pages */
		if( Application::appIsEnabled( 'cms' ) and !( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation == 'setup' ) )
		{
			/* Ensure applications set up correctly before task is executed. Pages, for example, needs to set up spl autoloaders first */
			Application::applications();

			/* Add in the database specific language bits and bobs */
			foreach( Databases::getStore() as $database )
			{
				$this->words['__indefart_content_record_comments_title_' . $database['database_id'] ] = $this->addToStack( '__indefart_content_record_comments_title' );
				$this->words['__indefart_content_record_reviews_title_' . $database['database_id'] ] = $this->addToStack( '__indefart_content_record_reviews_title' );
				$this->words['__indefart_content_db_lang_su_' . $database['database_id'] ] = $this->addToStack( 'content_db_lang_ia_' . $database['database_id'] );
				$this->words['__defart_content_record_comments_title_' . $database['database_id'] ] = $this->addToStack( '__defart_content_record_comments_title' );
				$this->words['__defart_content_record_reviews_title_' . $database['database_id'] ] = $this->addToStack( '__defart_content_record_reviews_title' );
				$this->words['__defart_content_db_lang_su_' . $database['database_id'] ] = $this->addToStack( 'content_db_lang_sl_' . $database['database_id'] );

				$this->words['content_record_comments_title_' . $database['database_id'] ] = $this->addToStack( 'content_record_comment_title', FALSE, array( 'sprintf' => array( $this->recordWord( 1, TRUE, $database['database_id'] ) ) ) );
				$this->words['content_record_reviews_title_' . $database['database_id'] ] = $this->addToStack( 'content_record_review_title', FALSE, array( 'sprintf' => array( $this->recordWord( 1, TRUE, $database['database_id'] ) ) ) );
				$this->words['content_record_comments_title_' . $database['database_id'] . '_pl' ] = $this->addToStack( 'content_record_comments_title', FALSE, array( 'sprintf' => array( $this->recordWord( 1, TRUE, $database['database_id'] ) ) ) );
				$this->words['content_record_comments_title_' . $database['database_id'] . '_pl_lc' ] = $this->addToStack( 'content_record_comments_title_lc', FALSE, array( 'sprintf' => array( $this->recordWord( 1, FALSE, $database['database_id'] ) ) ) );
				$this->words['content_record_comments_title_' . $database['database_id'] . '_lc' ] = $this->addToStack( 'content_record_comments_title_lc', FALSE, array( 'sprintf' => array( $this->recordWord( 1, FALSE, $database['database_id'] ) ) ) );
				$this->words['content_record_reviews_title_' . $database['database_id'] . '_pl' ] = $this->addToStack( 'content_record_reviews_title', FALSE, array( 'sprintf' => array( $this->recordWord( 1, TRUE, $database['database_id'] ) ) ) );
				$this->words['content_record_reviews_title_' . $database['database_id'] . '_pl_lc' ] = $this->addToStack( 'content_record_reviews_title_lc', FALSE, array( 'sprintf' => array( $this->recordWord( 1, FALSE, $database['database_id'] ) ) ) );
				$this->words['content_record_reviews_title_' . $database['database_id'] . '_lc' ] = $this->addToStack( 'content_record_reviews_title_lc', FALSE, array( 'sprintf' => array( $this->recordWord( 1, FALSE, $database['database_id'] ) ) ) );

				$this->words['content_db_lang_su_' . $database['database_id'] . '_pl' ] =  $this->addToStack( 'content_db_lang_pu_' . $database['database_id'] );
				$this->words['content_db_lang_su_' . $database['database_id'] . '_pl_lc' ] =  $this->addToStack( 'content_db_lang_pl_' . $database['database_id'] );
				$this->words['content_db_lang_sl_' . $database['database_id'] . '_pl_lc' ] =  $this->addToStack( 'content_db_lang_pl_' . $database['database_id'] );

				/* @var cms\Fields\ $fieldsClass */
				$fieldsClass = '\IPS\cms\Fields' . $database['database_id'];
				$customFields = $fieldsClass::databaseFieldIds();

				foreach ( $customFields AS $id )
				{
					$this->words['sort_field_' . $id] = $this->addToStack( 'content_field_' . $id );
				}
			}
		}
	}

	/**
	 * Set locale data
	 *
	 * @param string $val	Locale
	 * @return	void
	 */
	public function set_short( string $val ) : void
	{
		$oldLocale = setlocale( LC_ALL, '0' );
		$result = setlocale( LC_ALL, $val );

		/* Some locales in some PHP versions break things drastically */
		if( in_array( 'ips\\db\\_select', get_declared_classes() ) )
		{
			setlocale( LC_CTYPE, 'en_US.UTF-8' );
		}

		/* If this is Windows, store the codepage as we will need it again later */
		if( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) === 'WIN' )
		{
			$codepage	= preg_replace( "/^(.+?)\.(.+?)$/i", "$2", $result );

			if( $codepage !== $result )
			{
				$this->codepage	= $codepage;
			}
		}

		$this->locale = localeconv();

		foreach( $this->locale as $k => $v )
		{
			if( is_string( $v ) )
			{
				$this->locale[ $k ] = $this->convertString( $v );
			}
		}

		foreach( explode( ";", $oldLocale ) as $locale )
		{
			if( mb_strpos( $locale, '=' ) !== FALSE )
			{
				$parts = explode( "=", $locale );
				if( in_array( $parts[0], array( 'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME' ) ) )
				{
					setlocale( constant( $parts[0] ), $parts[1] );
				}
			}
			else
			{
				setlocale( LC_ALL, $locale );
			}
		}
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();
		Db::i()->delete( 'core_sys_lang_words', array( 'lang_id=?', $this->id ) );
	}

	/**
	 * Parse output and replace language keys
	 *
	 * @param mixed $output	Unparsed
	 * @return	void
	 */
	public function parseOutputForDisplay( mixed &$output ) : void
	{
		/* Do we actually have any? */
		if( !count( $this->outputStack ) )
		{
			return;
		}

		/* Parse out lang */
		$keys = array();

		foreach( $this->outputStack as $word => $values )
		{
			if( !isset( $this->words[ $values['key'] ] ) )
			{
				$keys[] = "'" . Db::i()->real_escape_string( $values['key'] ) . "'";
			}
		}

		if( !$this->wordsLoaded === TRUE AND count( $keys ) and !IN_DEV)
		{
			foreach(Db::i()->select( 'word_key, word_default, word_custom', 'core_sys_lang_words', array( "lang_id=? AND word_key IN(" . implode( ",", $keys ) . ") and word_js=0", $this->id ) ) as $row )
			{
				$this->words[ $row['word_key'] ] = $row['word_custom'] ?: $row['word_default'];
			}

			foreach( $this->outputStack as $word => $values )
			{
				if( !isset( $this->words[ $values['key'] ] ) )
				{
					if( isset( $values['options']['returnBlank'] ) AND $values['options']['returnBlank'] === TRUE )
					{
						$this->words[ $values['key'] ]	= '';
					}
				}
			}
		}

		/* Adjust for VLE */
		if ( isset( Request::i()->cookie['vle_editor'] ) and Request::i()->cookie['vle_editor'] and Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			$this->originalWords = $this->words;
		}
		if ( isset( Request::i()->cookie['vle_keys'] ) and Request::i()->cookie['vle_keys'] and Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			$this->words = array_combine( array_keys( $this->words ), array_keys( $this->words ) );
		}

		$this->outputStack = array_reverse( $this->outputStack );

		$this->replaceWords( $output );
	}

	/**
	 * Emails require some additional work on top of replacing the language stack
	 *
	 * @param	mixed	$output	Unparsed
	 * @return	void
	 */
	public function parseEmail( mixed &$output ) : void
	{
		$this->parseOutputForDisplay( $output );
		$output = $this->stripVLETags( $output );

		$dir = ( $this->isrtl ) ? 'rtl' : 'ltr';

		if ( mb_stristr( $output, '{dir}' ) )
		{
			$output = preg_replace( '#(<td.+?)dir=([\'"]){dir}([\'"])#i', '\1dir=\2' . $dir . '\3', $output );

			preg_match_all( '#<(body|html)([^>]+?)>#i', $output, $matches, PREG_SET_ORDER );

			foreach( $matches as $match )
			{
				if ( mb_stristr( $match[2], '{dir}' ) )
				{
					$parsed = str_replace( '{dir}', $dir, $match[0] );
					$output = str_replace( $match[0], $parsed, $output );
				}
			}
		}
	}

	/**
	 * Strip VLE tags, useful for AJAX responses where you can't edit the string anyways
	 *
	 * @param array|string|null $output	The output string
	 * @return	string|array|null
	 */
	public function stripVLETags( array|string|null $output ): array|string|null
	{
		if ( ! static::vleActive() )
		{
			if( ! is_array( $output ) )
			{
				return (string) $output;
			}

			$replacement = array();
			foreach ( $output as $key => $value )
			{
				$replacement[ $key ] = $this->stripVLETags( $value );
			}

			return $replacement;
		}

		if( ! is_array( $output ) )
		{
			preg_match_all( '/#VLE#(.+?)#!#/', (string) $output, $matches, PREG_SET_ORDER );

			foreach( $matches as $match )
			{
				$replace = $match[1]; /* Possibly better than nothing */

				if ( isset( $this->words[$match[1]] ) )
				{
					$replace = $this->words[$match[1]];
				}
				else
				{
					try
					{
						$replace = $this->get( $match[1] );
					}
					catch( OutOfRangeException $e ) {}
				}
				$output = str_replace( $match[0], $replace, $output );
			}

			return $output;
		}

		$replacement = array();

		foreach ( $output as $key => $value )
		{
			$replacement[ $key ] = $this->stripVLETags( $value );
		}

		return $replacement;
	}

	/**
	 * Replace values in array recursively
	 *
	 * @param array|string $find		The string to find
	 * @param array|string $replace	The string to replace with
	 * @param array|string|null $haystack	The subject
	 *
	 * @return	string|array|null|object
	 */
	public static function replace( array|string $find, array|string $replace, mixed $haystack ): array|string|null|object
	{
		/* Reduce the number of str_replace with arrays */
		static $replaceTable = array();

		if ( !is_array( $haystack ) )
		{
			if( !is_string( $haystack ) )
			{
				return $haystack;
			}

			$hash = md5( json_encode($find) . json_encode($replace) . $haystack );

			if ( isset( $replaceTable[ $hash ] ) )
			{
				return $replaceTable[ $hash ];
			}
			else
			{
				$output = str_replace( $find, $replace, $haystack );
				$replaceTable[ $hash ] = $output;
				return $output;
			}
		}

		$replacement = array();

		foreach ( $haystack as $key => $value )
		{
			$replacement[ $key ] = static::replace( $find, $replace, $value );
		}

		return $replacement;
	}


	/**
	 * Parse the output stack
	 *
	 * @param array|string|null $output Unparsed
	 * @return    void
	 */
	public function replaceWords( array|string|null &$output ) : void
	{
		/* It's possible to call this method and not pass in any content - it's a waste of resources to run replacements on an empty string */
		if( !$output )
		{
			return;
		}

		$replacements = array();

		foreach ( $this->outputStack as $key => $values )
		{
			if ( isset( $values[ 'options' ][ 'returnBlank' ] ) AND $values[ 'options' ][ 'returnBlank' ] === true AND ( !isset( $this->words[ $values[ 'key' ] ] ) OR !$this->words[ $values[ 'key' ] ] ) )
			{
				$replacements[ $key ] = "";
				continue;
			}
			else
			{
				if ( isset( $this->words[ $values[ 'key' ] ] ) )
				{
					$replacement = $this->words[ $values[ 'key' ] ];

					/* Parse URLs */
					if ( mb_strpos( $replacement, "{external" ) !== false )
					{
						$replacement = preg_replace_callback(
							"/{external\.(.+?)}/",
							function ( $matches )
							{
								return Url::ips( 'docs/' . $matches[ 1 ] );
							},
							$replacement
						);
					}

					if ( mb_strpos( $replacement, "{internal" ) !== false )
					{
						$replacement = preg_replace_callback(
							"/{internal\.(.+?)\.csrf}/",
							function ( $matches )
							{
								return Url::internal( $matches[ 1 ] )->csrf();
							},
							$replacement
						);
						$replacement = preg_replace_callback(
							"/{internal\.([a-zA-Z]+?)\.([^}]+?)\.([^}]+?)}/",

							function ( $matches )
							{
								return Url::internal( $matches[ 2 ], $matches[ 1 ], $matches[ 3 ] );
							},
							$replacement
						);
						$replacement = preg_replace_callback(
							"/{internal\.([a-zA-Z]+?)\.([^}]+?)}/",
							function ( $matches )
							{
								return Url::internal( $matches[ 2 ], $matches[ 1 ] );
							},
							$replacement
						);
						$replacement = preg_replace_callback(
							"/{internal\.([^}]+?)}/",
							function ( $matches )
							{
								return Url::internal( $matches[ 1 ] );
							},
							$replacement
						);
					}

					/* We do pluralize first in case you have something like "{!#[1:%s has][?:%s have]} %s: %s" */
					if ( !empty( $values[ 'options' ][ 'pluralize' ] ) )
					{
						$replacement = $this->pluralize(
							$replacement,
							$values[ 'options' ][ 'pluralize' ],
							$values['options']['format'] ?? NULL
						);
					}

					$sprintf     = array();

					if ( isset( $values[ 'options' ][ 'flipsprintf' ] ) AND $values[ 'options' ][ 'flipsprintf' ] === true )
					{
						if ( isset( $values[ 'options' ][ 'sprintf' ] ) )
						{
							$replacement                      = $values[ 'options' ][ 'sprintf' ];
							$values[ 'options' ][ 'sprintf' ] = array( $this->words[ $values[ 'key' ] ] );
						}

						if ( isset( $values[ 'options' ][ 'htmlsprintf' ] ) )
						{
							$replacement                          = $values[ 'options' ][ 'htmlsprintf' ];
							$values[ 'options' ][ 'htmlsprintf' ] = array( $this->words[ $values[ 'key' ] ] );
						}
					}

					if ( isset( $values[ 'options' ][ 'sprintf' ] ) )
					{
						$sprintf = array_map(
							function ( $val ) use ( $replacement )
							{
								return htmlspecialchars( trim( $val ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', false );
							},
							( is_array(
								$values[ 'options' ][ 'sprintf' ]
							) ? $values[ 'options' ][ 'sprintf' ] : explode( ',', $values[ 'options' ][ 'sprintf' ] ) )
						);
					}

					if ( isset( $values[ 'options' ][ 'htmlsprintf' ] ) and $values[ 'options' ][ 'htmlsprintf' ] !== null )
					{
						$sprintf = array_merge(
							$sprintf,
							( is_array(
								$values[ 'options' ][ 'htmlsprintf' ]
							) ? $values[ 'options' ][ 'htmlsprintf' ] : explode(
								',',
								$values[ 'options' ][ 'htmlsprintf' ]
							) )
						);
					}

					if ( count( $sprintf ) )
					{
						try
						{
							$replacement = vsprintf( $replacement, $sprintf );

							/* Without IN_DEV we suppress warnings, so we need to verify if the return was FALSE */
							if( !$replacement )
							{
								throw new ErrorException;
							}
						}
						catch ( Throwable $e )
						{
							// If there's the wrong number of parameters because the translator's done it wrong, we can just use empty strings for replacements
						}
					}
				}
				else
				{
					$values['options']['escape'] = TRUE; // Prevent accidentally introducing an XSS vulnerability by using raw strings where something expects a language string
					$replacement = $values[ 'key' ];
				}
			}

			if ( isset( $values[ 'options' ][ 'wordbreak' ] ) )
			{
				$replacement = Lang::wordbreak( $replacement );
			}

			if ( isset( $values[ 'options' ][ 'ucfirst' ] ) )
			{
				$replacement = mb_strtoupper( mb_substr( $replacement, 0, 1 ) ) . mb_substr( $replacement, 1 );
			}

			if ( isset( $values[ 'options' ][ 'strtoupper' ] ) )
			{
				$replacement = mb_strtoupper( $replacement );
			}

			if ( isset( $values[ 'options' ][ 'strtolower' ] ) )
			{
				$replacement = mb_strtolower( $replacement );
			}

			if ( isset( $values[ 'options' ][ 'seotitle' ] ) )
			{
				$replacement = Friendly::seoTitle( $replacement );
			}

			if ( isset( $values[ 'options' ][ 'json' ] ) )
			{
				if( isset( $values['options']['jsonEscape'] ) )
				{
					$replacement = mb_substr( json_encode( $replacement, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS ), 1, -1 );
				}
				else
				{
					$replacement = mb_substr( json_encode( $replacement ), 1, -1 );
				}
			}

			if ( isset( $values[ 'options' ][ 'striptags' ] ) )
			{
				$replacement = strip_tags( $replacement );
			}

			if ( isset( $values[ 'options' ][ 'urlencode' ] ) )
			{
				$replacement = urlencode( $replacement );
			}

			if ( isset( $values[ 'options' ][ 'rawurlencode' ] ) )
			{
				$replacement = rawurlencode( $replacement );
			}

			if ( isset( $values[ 'options' ][ 'escape' ] ) )
			{
				$replacement = htmlspecialchars( $replacement, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', false );
			}

			if ( isset( $values['options']['escape'] ) and isset( $values['options']['json'] ) )
			{
				/* Some browsers treat &#039; as a literal ' which can break things when used in JSON
					A similar issue occurrs in IPS\gallery\Image::json() */
				$replacement = str_replace( "&#039;", "&apos;", $replacement );
			}

			/* Add VLE tags */
			if ( $values[ 'vle' ] and $replacement and static::vleActive() )
			{
				$replacement = "#VLE#{$values['key']}#!#";
			}

			if ( isset( $values[ 'options' ][ 'returnInto' ] ) )
			{
				$replacement = sprintf( $values[ 'options' ][ 'returnInto' ], $replacement );
			}

			/* Remove newlines and any whitespace */
			if ( isset( $values[ 'options' ][ 'removeNewlines' ] ) )
			{
				$replacement = trim( preg_replace( "#\n\s+?#", " ", $replacement ) );
			}

			$replacements[ $key ] = $replacement;
		}

		/* We do this 4 times in case a replacement contains another replacement, etc. */
		$output = static::replace( array_keys( $replacements ), array_values( $replacements ), $output );
		$output = static::replace( array_keys( $replacements ), array_values( $replacements ), $output );
		$output = static::replace( array_keys( $replacements ), array_values( $replacements ), $output );
		$output = static::replace( array_keys( $replacements ), array_values( $replacements ), $output );
	}

	/**
	 * Word Form
	 *
	 * @param	Form|NULL $form		An existing form object or NULL to create new
	 * @return	void
	 */
	public static function wordForm( ?Form &$form = NULL ) : void
	{
		if ( $form === NULL )
		{
			$form = new Form;
		}
		if ( Application::appIsEnabled( 'cms' ) )
		{
			$form->addMessage( 'add_word_reason_pages', 'ipsMessage ipsMessage_info' );
		}
		else
		{
			$form->addMessage( 'add_word_reason', 'ipsMessage ipsMessage_info' );
		}
		$form->add( new Text( 'word_key', NULL, TRUE, array( 'regex' => '/^([A-Z_][A-Z0-9_]+?)$/i', 'placeholder' => 'my_custom_phrase' ), function( $val ) {
			try
			{
				/* This is a generic check - if key exists in *any* language, don't allow. */
				Db::i()->select( '*', 'core_sys_lang_words', array( "word_key=?", $val ) )->first();

				/* Here? No excepton thrown so error */
				throw new DomainException( 'word_key_exists' );
			}
			catch( UnderflowException $e ) {}
		} ) );
		$form->add( new TextArea( 'word_default', NULL, TRUE, array( 'placeholder' => Member::loggedIn()->language()->addToStack( 'my_custom_phrase' ) ) ) );
		if ( count( static::languages() ) > 1 )
		{
			$form->add( new Translatable( 'word_custom', NULL, FALSE, array( 'textArea' => TRUE ) ) );
		}
	}

	/**
	 * "Records" / "Record" word
	 *
	 * @param int $number	Number
	 * @param bool $upper  ucfirst string
	 * @param int $databaseId  Database Id
	 * @return	string
	 */
	public function recordWord( int $number, bool $upper=FALSE, int $databaseId=0 ): string
	{
		$case = $upper ? 'u' : 'l';
		return $number == 1 ? $this->addToStack("content_db_lang_s{$case}_{$databaseId}") : $this->addToStack("content_db_lang_p{$case}_{$databaseId}");
	}

	/**
	 * Is the visual language editor active?
	 *
	 * @return bool
	 */
	public static function vleActive(): bool
	{
		return isset( Request::i()->cookie[ 'vle_editor' ] ) and Request::i()->cookie[ 'vle_editor' ] and Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' );
	}

	/**
	 * Return an array of keys > values for the VLE
	 *
	 * @return array
	 */
	public function vleForJson(): array
	{
		/* if we're using IN_DEV, this is all of the strings, so... good luck I guess */
		return $this->words;
	}
}