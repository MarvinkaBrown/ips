<?php
/**
 * @brief		Application Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DirectoryIterator;
use DomainException;
use ErrorException;
use FilesystemIterator;
use InvalidArgumentException;
use IPS\Application\Module;
use IPS\cms\Templates;
use IPS\Content\Search\Index;
use IPS\core\FrontNavigation;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Db\Exception;
use Exception as PHPException;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Member\ProfileStep;
use IPS\Node\Model;
use IPS\Output\Javascript;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Theme\CustomTemplate;
use IPS\Theme\Editor\Category;
use IPS\Theme\Editor\Setting;
use IPS\Widget\Area;
use IPS\Xml\SimpleXML;
use OutOfRangeException;
use OverflowException;
use RecursiveArrayIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use UnderFlowException;
use UnexpectedValueException;
use XMLReader;
use XMLWriter;
use function class_exists;
use function count;
use function defined;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function str_replace;
use function strlen;
use function strtoupper;
use function substr;
use function unlink;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Abstract class that applications extend and use to handle application data
 */
class Application extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Have fetched all?
	 */
	protected static bool $gotAll	= FALSE;

	/**
	 * @brief	Defined versions
	 */
	protected ?array $definedVersions	= NULL;

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = '__app_';

	/**
	 * @brief	Defined theme locations for the theme system
	 */
	public array $themeLocations = array('admin', 'front', 'global');

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'updatecount_applications', 'applications' );

	/**
	 * Set default
	 *
	 * @return void
	 */
	public function setAsDefault() : void
	{
		/* Update any FURL customizations */
		if ( Settings::i()->furl_configuration )
		{
			$furlCustomizations = json_decode( Settings::i()->furl_configuration, TRUE );

			try
			{
				/* Add the top-level directory to all the FURLs for the old default app */
				$previousDefaultApp = Application::constructFromData( Db::i()->select( '*', 'core_applications', 'app_default=1' )->first() );
				if( file_exists( $previousDefaultApp->getApplicationPath()  . "/data/furl.json" ) )
				{
					$oldDefaultAppDefinition = json_decode( preg_replace( '/\/\*.+?\*\//s', '', file_get_contents( $previousDefaultApp->getApplicationPath() . "/data/furl.json" ) ), TRUE );
					if ( $oldDefaultAppDefinition['topLevel'] )
					{
						foreach ( $oldDefaultAppDefinition['pages'] as $k => $data )
						{
							if ( isset( $furlCustomizations[ $k ] ) )
							{
								$furlCustomizations[ $k ] = Friendly::buildFurlDefinition( $furlCustomizations[ $k ]['friendly'], $furlCustomizations[ $k ]['real'], $oldDefaultAppDefinition['topLevel'], FALSE, $furlCustomizations[$k]['alias'] ?? NULL, $furlCustomizations[$k]['custom'] ?? FALSE, $furlCustomizations[$k]['verify'] ?? NULL );
							}
						}
					}
				}
			}
			catch ( UnderflowException $e ){}


			/* And remove it from the new */
			if( file_exists( $this->getApplicationPath() . "/data/furl.json" ) )
			{
				$newDefaultAppDefinition = json_decode( preg_replace( '/\/\*.+?\*\//s', '', file_get_contents( $this->getApplicationPath() . "/data/furl.json" ) ), TRUE );
				if ( $newDefaultAppDefinition['topLevel'] )
				{
					foreach ( $newDefaultAppDefinition['pages'] as $k => $data )
					{
						if ( isset( $furlCustomizations[ $k ] ) )
						{
							$furlCustomizations[ $k ] = Friendly::buildFurlDefinition( rtrim( preg_replace( '/^' . preg_quote( $newDefaultAppDefinition['topLevel'], '/' ) . '\/?/', '', $furlCustomizations[ $k ]['friendly'] ), '/' ), $furlCustomizations[ $k ]['real'], $newDefaultAppDefinition['topLevel'], TRUE, $furlCustomizations[$k]['alias'] ?? NULL, $furlCustomizations[$k]['custom'] ?? FALSE, $furlCustomizations[$k]['verify'] ?? NULL );
						}
					}
				}
			}

			/* Save the new FURL customisation */
			Settings::i()->changeValues( array( 'furl_configuration' => json_encode( $furlCustomizations ) ) );
		}

		foreach(Application::applications() as $directory => $application )
		{
			if( $application->default )
			{
				static::removeMetaPrefix( $application );
				break;
			}
		}

		static::addMetaPrefix( $this );

		/* Actually update the database */
		Db::i()->update( 'core_applications', array( 'app_default' => 0 ) );
		Db::i()->update( 'core_applications', array( 'app_default' => 1 ), array( 'app_id=?', $this->id ) );

		/* Clear cached data */
		unset( Store::i()->applications );
		unset( Store::i()->furl_configuration );
	}

	/**
	 * Get Applications
	 *
	 * @return	array<Application>
	 */
	public static function applications(): array
	{
		if( static::$gotAll === FALSE )
		{
			static::$multitons = array();

			foreach ( static::getStore() as $row )
			{
				if( $row['app_requires_manual_intervention'] and !in_array( $row['app_directory'], IPS::$ipsApps ) )
				{
					continue;
				}

				try
				{
					static::$multitons[ $row['app_directory'] ] = static::constructFromData( $row );
				}
				catch( UnexpectedValueException $e )
				{
					if ( mb_stristr( $e->getMessage(), 'Missing:' ) )
					{
						/* Ignore this, the app is in the table, but not 4.0 compatible */
						continue;
					}
				}
				catch( \Exception $e )
				{
					if( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation == 'setup' )
					{
						continue;
					}

					throw $e;
				}
			}

			static::$gotAll = TRUE;
		}

		return static::$multitons;
	}

	/**
	 * Get data store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->applications ) )
		{
			Store::i()->applications = iterator_to_array( Db::i()->select( '*', 'core_applications', NULL, 'app_position' ) );
		}

		return Store::i()->applications;
	}

	/**
	 * Get enabled applications
	 *
	 * @return	array<Application>
	 */
	public static function enabledApplications(): array
	{
		$applications	= static::applications();
		$enabled		= array();

		foreach( $applications as $key => $application )
		{
			if( $application->enabled )
			{
				$enabled[ $key ] = $application;
			}
		}

		return $enabled;
	}

	/**
	 * Does an application exist and is it enabled? Note: does not check if offline for a particular member
	 *
	 * @param	string	$key	Application key
	 * @return	bool
		  *@see        Application::canAccess
	 */
	public static function appIsEnabled( string $key ): bool
	{
		if ( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation === 'setup' and Dispatcher::i()->setupLocation == 'install' )
		{
			return FALSE;
		}

		$applications = static::applications();

		if ( !array_key_exists( $key, $applications ) )
		{
			return FALSE;
		}

		return $applications[ $key ]->enabled;
	}

	/**
	 * Load Record
	 *
	 * @see     Db::build
	 * @param	int|string|null	$id					ID
	 * @param	string|null		$idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param	mixed		$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details)
	 * @return	ActiveRecord|static
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function load( int|string|null $id, string $idField=NULL, mixed $extraWhereClause=NULL ): ActiveRecord|static
	{
		$applications = static::applications(); // Load all applications so we can grab the data from the cache

		/* Make sure that the app key is in the list. If the app needs manual intervention, we do NOT want to try to load it. */
		if( !array_key_exists( $id, $applications ) )
		{
			throw new OutOfRangeException;
		}

		return parent::load( $id, $idField, $extraWhereClause );
	}

	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @param	array|NULL			$limit				Limit/offset to use, or NULL for no limit (default)
	 * @note	This is overridden to prevent UnexpectedValue exceptions when there is an old application record in core_applications without an Application.php file
	 * @return	array
	 */
	public static function roots( ?string $permissionCheck='view', Member $member=NULL, mixed $where=array(), array $limit=NULL ): array
	{
		return static::applications();
	}

	/**
	 * Get all extensions
	 *
	 * @param string|Application $app				The app key of the application which owns the extension
	 * @param string $extension			Extension Type
	 * @param bool|Group|Member|null $checkAccess		Check access permission for application against supplied member/group (or logged in member, if TRUE) before including extension
	 * @param string|null $firstApp			If specified, the application with this key will be returned first
	 * @param string|null $firstExtensionKey	If specified, the extension with this key will be returned first
	 * @param bool $construct			Should an object be returned? (If false, just the classname will be returned)
	 * @return	array
	 */
	public static function allExtensions( Application|string $app, string $extension, bool|Group|Member|null $checkAccess=TRUE, string $firstApp=NULL, string $firstExtensionKey=NULL, bool $construct=TRUE ): array
	{
		$extensions = array();

		/* Get applications */
		$apps = static::applications();

		if ( $firstApp !== NULL )
		{
			$apps = static::$multitons;

			usort( $apps, function( $a, $b ) use ( $firstApp )
			{
				if ( $a->directory === $firstApp )
				{
					return -1;
				}
				if ( $b->directory === $firstApp )
				{
					return 1;
				}
				return 0;
			} );
		}

		/* Get extensions */
		foreach ( $apps as $application )
		{
			if ( !static::appIsEnabled( $application->directory ) )
			{
				continue;
			}

			if( $checkAccess !== FALSE )
			{
				if( !$application->canAccess( $checkAccess === TRUE ? NULL : $checkAccess ) )
				{
					continue;
				}
			}

			$_extensions = array();

			foreach ( $application->extensions( $app, $extension, $construct, $checkAccess ) as $key => $class )
			{
				$_extensions[ $application->directory . '_' . $key ] = $class;
			}

			if ( $firstExtensionKey !== NULL AND array_key_exists( $application->directory . '_' . $firstExtensionKey, $_extensions ) )
			{
				uksort( $_extensions, function( $a, $b ) use ( $application, $firstExtensionKey )
				{
					if ( $a === $application->directory . '_' . $firstExtensionKey )
					{
						return -1;
					}
					if ( $b === $application->directory . '_' . $firstExtensionKey )
					{
						return 1;
					}
					return 0;
				} );
			}

			$extensions = array_merge( $extensions, $_extensions );
		}

		/* Return */
		return $extensions;
	}

	/**
	 * Retrieve a list of applications that contain a specific type of extension
	 *
	 * @param string|Application $app				The app key of the application which owns the extension
	 * @param string $extension			Extension Type
	 * @param bool|Member $checkAccess		Check access permission for application against supplied member (or logged in member, if TRUE) before including extension
	 * @return	array
	 */
	public static function appsWithExtension( Application|string $app, string $extension, bool|Member $checkAccess=TRUE ): array
	{
		$_apps	= array();

		foreach( static::applications() as $application )
		{
			if ( static::appIsEnabled( $application->directory ) )
			{
				/* If $checkAccess is false we don't verify access to the app */
				if( $checkAccess !== FALSE )
				{
					/* If we passed true, we want to check current member, otherwise pass the member in directly */
					if( $application->canAccess( ( $checkAccess === TRUE ) ? NULL : $checkAccess ) !== TRUE )
					{
						continue;
					}
				}

				if( count( $application->extensions( $app, $extension ) ) )
				{
					$_apps[ $application->directory ] = $application;
				}
			}
		}

		return $_apps;
	}

	/**
	 * Get available version for an application
	 * Used by the installer/upgrader
	 *
	 * @param string $appKey	The application key
	 * @param bool $human	Return the human-readable version instead
	 * @return	int|string|null
	 */
	public static function getAvailableVersion( string $appKey, bool $human = FALSE ): int|string|null
	{
		$versionsJson = static::getRootPath( $appKey ) . "/applications/{$appKey}/data/versions.json";

		if( file_exists( $versionsJson ) and $content = json_decode( file_get_contents( $versionsJson ), TRUE ) )
		{
			$_versions	= $human ? array_values( $content ) : array_keys( $content );

			/* Set the variable so that we return the newest version */
			if ( $versionsJson = $_versions )
			{
				return array_pop( $versionsJson );
			}
		}

		return NULL;
	}

	/**
	 * Get all defined versions for an application
	 *
	 * @return array|null
	 */
	public function getAllVersions(): ?array
	{
		if( $this->definedVersions !== NULL )
		{
			return $this->definedVersions;
		}

		$this->definedVersions	= array();

		$versionsJson = $this->getApplicationPath() . "/data/versions.json";

		if ( file_exists( $versionsJson ) )
		{
			$this->definedVersions	= json_decode( file_get_contents( $versionsJson ), TRUE );
		}

		return $this->definedVersions;
	}

	/**
	 * Return the human version of an INT long version
	 *
	 * @param int $longVersion	Long version (10001)
	 * @return	string|false			Long Version (1.1.1 Beta 1)
	 */
	public function getHumanVersion( int $longVersion ): bool|string
	{
		$this->getAllVersions();

		if ( isset( $this->definedVersions[ $longVersion ] ) )
		{
			return $this->definedVersions[ $longVersion ];
		}

		return false;
	}

	/**
	 * The available version we can upgrade to
	 *
	 * @param bool $latestOnly				If TRUE, will return the latest version only
	 * @param bool $skipSameHumanVersion	If TRUE, will not include any versions with the same "human" version number as the current version
	 * @return	array
	 */
	public function availableUpgrade( bool $latestOnly, bool $skipSameHumanVersion=TRUE ): array
	{
		$update = array();

		if( ( $versions = json_decode( $this->update_version, TRUE ) ) AND is_iterable( $versions ) )
		{
			if ( is_array( $versions ) and !isset( $versions[0] ) and isset( $versions['longversion'] ) )
			{
				$versions = array( $versions );
			}

			foreach ( $versions as $data )
			{
				if( !empty( $data['longversion'] ) and $data['longversion'] > $this->long_version and ( !$skipSameHumanVersion or $data['version'] != $this->version ) )
				{
					if( $data['released'] AND ( (int) $data['released'] != $data['released'] OR strlen($data['released']) != 10 ) )
					{
						$data['released']	= strtotime( $data['released'] );
					}

					$update[]	= $data;
				}
			}
		}

		if ( !empty( $update ) and $latestOnly )
		{
			$update = array_pop( $update );
		}

		return $update;
	}

	/**
	 * The latest new feature ID
	 *
	 * @return	int|null
	 */
	public function newFeature(): ?int
	{
		if( $this->update_version )
		{
			$versions = json_decode( $this->update_version, TRUE );
			if ( is_array( $versions ) and !isset( $versions[0] ) and isset( $versions['longversion'] ) )
			{
				$versions = array( $versions );
			}

			$latestVersion	= NULL;

			foreach ( $versions as $data )
			{
				if( isset( $data['latestNewFeature'] ) AND $data['latestNewFeature'] AND $data['latestNewFeature'] > $latestVersion )
				{
					$latestVersion	= $data['latestNewFeature'];
				}
			}

			return $latestVersion;
		}

		return NULL;
	}

	/**
	 * Is the application up to date with security patches?
	 *
	 * @return	bool
	 */
	public function missingSecurityPatches(): bool
	{
		$updates = $this->availableUpgrade( FALSE );
		if( !empty( $updates ) )
		{
			foreach( $updates as $update )
			{
				if( $update['security'] )
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * MD5 check (returns path to files which do not match)
	 *
	 * @param int|null $version	Version to check against
	 * @return	array
	 * @throws    Http\Request\Exception
	 */
	public static function md5Check( int $version = NULL ): array
	{
		/* For Community in the Cloud customers we cannot do this because they have encoded files
			and the encoder produces different output each time it runs, so every Cloud customer
			has different files, even for the same version */
		$key = IPS::licenseKey();
		if ( CIC OR $key['cloud'] )
		{
			return array();
		}

		/* For everyone else, get the correct md5 sums for each file... */
		$url = Url::ips( 'md5' );
		if ( $version !== NULL )
		{
			$url = $url->setQueryString( 'version', $version );
		}
		$correctMd5s = $url->request()->get()->decodeJson();

		/* And return whichever ones don't match */
		$return = array();
		foreach ( $correctMd5s as $file => $md5Hash )
		{
			/* If this is an application directory but the application doesn't exist or has been disabled then we shouldn't check it */
			preg_match( '/^\/applications\/(.+?)\//', $file, $matches );
			if ( $matches )
			{
				if( in_array( $matches[1], IPS::$ipsApps ) AND !static::appIsEnabled( $matches[1] ) )
				{
					continue;
				}
			}

			/* Ignore init.php (which always changes on build) and conf_global.dist.php (which doesn't exist after install) */
			if ( in_array( $file, array( '/init.php', '/conf_global.dist.php' ) ) )
			{
				continue;
			}

			/* If the file doesn't exist at all, flag it */
			if ( !file_exists( ROOT_PATH . $file ) )
			{
				$return[] = ROOT_PATH . $file;
			}
			/* Otherwise, compare the md5 hashes... */
			else
			{
				/* Get the contents. If you can't get the contents, it may be that the file permissions are set wrong. Try to fix. */
				$fileContents = @file_get_contents( ROOT_PATH . $file );
				if ( !$fileContents )
				{
					@chmod( ROOT_PATH . $file, FILE_PERMISSION_NO_WRITE);
				}

				/* If we got the file contents... */
				if ( $fileContents )
				{
					/* Strip whitespace since FTP in ASCII mode will change the whitespace characters */
					$fileContents = preg_replace( '#\s#', '', file_get_contents( ROOT_PATH . $file ) );

					/* Compare */
					if ( md5( $fileContents ) != $md5Hash )
					{
						$return[] = ROOT_PATH . $file;
					}
				}

				/* Otherwise, flag it */
				else
				{
					$return[] = ROOT_PATH . $file;
				}
			}
		}

		return $return;
	}

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_applications';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'app_';

	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'directory';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'app_id' );

	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();

	/**
	 * @brief	[Node] Subnode class
	 */
	public static ?string $subnodeClass = 'IPS\Application\Module';

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'applications_and_modules';

	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static ?array $restrictions = array( 'app' => 'core', 'module' => 'applications', 'prefix' => 'app_' );

	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		/* Load class */
		if( !file_exists( static::getRootPath( $data['app_directory'] ) . '/applications/' . $data['app_directory'] . '/Application.php' ) )
		{
			/* If you are upgrading and you have an application "123flashchat" this causes a PHP error, so just die out now */
			if( !in_array( mb_strtolower( mb_substr( $data['app_directory'], 0, 1 ) ), range( 'a', 'z' ) ) )
			{
				throw new UnexpectedValueException( "Missing: " . '/applications/' . $data['app_directory'] . '/Application.php' );
			}

			if( !Dispatcher::hasInstance() OR Dispatcher::i()->controllerLocation !== 'setup' )
			{
				throw new UnexpectedValueException( "Missing: " . '/applications/' . $data['app_directory'] . '/Application.php' );
			}
			else
			{
				$className = "\\IPS\\{$data['app_directory']}\\Application";

				if( !class_exists( $className ) )
				{
					$code = <<<EOF
namespace IPS\\{$data['app_directory']};
class Application extends \\IPS\\Application{}
EOF;
					eval( $code );
				}
			}
		}
		else
		{
			require_once static::getRootPath( $data['app_directory'] ) . '/applications/' . $data['app_directory'] . '/Application.php';
		}

		/* Initiate an object */
		$classname = 'IPS\\' . $data['app_directory'] . '\\Application';
		$obj = new $classname;
		$obj->_new = FALSE;

		/* Import data */
		if ( static::$databasePrefix )
		{
			$databasePrefixLength = strlen( static::$databasePrefix );
		}

		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix )
			{
				$k = substr( $k, $databasePrefixLength );
			}

			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();

		/* Return */
		return $obj;
	}

	/**
	 * @brief	Modules Store
	 */
	protected ?array $modules = NULL;

	/**
	 * Get Modules
	 *
	 * @param	string|null	$location	Location (e.g. "admin" or "front")
	 * @return	array
	 *@see		static::$modules
	 */
	public function modules( string $location=NULL ): array
	{
		/* Don't have an instance? */
		if( $this->modules === NULL )
		{
			$modules = Module::modules();
			$this->modules = array_key_exists( $this->directory, $modules ) ? $modules[ $this->directory ] : array();
		}

		/* Return */
		return $this->modules[$location] ?? array();
	}

	/**
	 * Returns the ACP Menu JSON for this application.
	 *
	 * @return array
	 */
	public function acpMenu(): array
	{
		return json_decode( file_get_contents( $this->getApplicationPath() . "/data/acpmenu.json" ), TRUE );
	}

	/**
	 * ACP Menu Numbers
	 *
	 * @param string $queryString	Query String
	 * @return	int
	 */
	public function acpMenuNumber( string $queryString ): int
	{
		return 0;
	}

	/**
	 * Get Extensions
	 *
	 * @param string|Application $app		    The app key of the application which owns the extension
	 * @param string $extension	    Extension Type
	 * @param bool $construct	    Should an object be returned? (If false, just the classname will be returned)
	 * @param bool|Group|Member|null $checkAccess	Check access permission for extension against supplied member/group (or logged in member, if TRUE)
	 * @return	array
	 */
	public function extensions( Application|string $app, string $extension, bool $construct=TRUE, bool|Group|Member|null $checkAccess = FALSE ): array
	{
		$app = ( is_string( $app ) ? $app : $app->directory );

		$classes = array();
		$jsonFile = $this->getApplicationPath() . "/data/extensions.json";

		/* New extensions.json based approach */
		if ( file_exists( $jsonFile ) and $json = @json_decode( file_get_contents( $jsonFile ), TRUE ) )
		{
			if ( isset( $json[ $app ] ) and isset( $json[ $app ][ $extension ] ) )
			{
				foreach ( $json[ $app ][ $extension ] as $name => $classname )
				{
					if( !class_exists( $classname ) )
					{
						/* Switching between branches confuses extensions */
						continue;
					}

					if ( method_exists( $classname, 'generate' ) )
					{
						$classes = array_merge( $classes, $classname::generate() );
					}
					elseif ( !$construct )
					{
						$classes[ $name ] = $classname;
					}
					else
					{
						try
						{
							$classes[ $name ] = new $classname( $checkAccess === TRUE ? Member::loggedIn() : ( $checkAccess === FALSE ? NULL : $checkAccess ) );
						}
						catch( RuntimeException | OutOfRangeException $e ){}
					}
				}
			}
		}
		return $classes;
	}

	/**
	 * Get All listeners for this application
	 *
	 * @return	array
	 */
	public function listeners(): array
	{
		$listeners = array();
		$jsonFile = $this->getApplicationPath() . "/data/listeners.json";
		$directory = $this->getApplicationPath() . "/listeners";

		if ( file_exists( $jsonFile ) and $json = @json_decode( file_get_contents( $jsonFile ), TRUE ) )
		{
			foreach( $json as $filename => $data )
			{
				if( file_exists( $directory . '/' . $filename . '.php' ) )
				{
					if( class_exists( $data['classname'] ) )
					{
						$listeners[] = $data['classname'];
					}
				}
			}
		}

		return $listeners;
	}

	/**
	 * Get all listeners for all applications
	 *
	 * @return array
	 */
	public static function allListeners() : array
	{
		$listeners = array();
		foreach( static::applications() as $app )
		{
			if( static::appIsEnabled( $app->directory ) )
			{
				$listeners = array_merge( $listeners, $app->listeners() );
			}
		}
		return $listeners;
	}

	/**
	 * [Node] Get Node Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		$key = "__app_{$this->directory}";
		return Member::loggedIn()->language()->addToStack( $key );
	}

	/**
	 * [Node] Get Node Icon
	 *
	 * @return    string
	 */
	protected function get__icon(): string
	{
		return 'cubes';
	}

	/**
	 * [Node] Does this node have children?
	 *
	 * @param string|null $permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param Member|null $member				The member to check permissions for or NULL for the currently logged in member
	 * @param bool $subnodes			Include subnodes?
	 * @param array $_where				Additional WHERE clause
	 * @return	bool
	 */
	public function hasChildren( ?string $permissionCheck='view', Member $member=NULL, bool $subnodes=TRUE, array $_where=array() ): bool
	{
		return $subnodes;
	}

	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		/* First-party apps cannot be deleted */
		if( in_array( $this->directory, IPS::$ipsApps ) )
		{
			return FALSE;
		}

		if( NO_WRITES or !static::restrictionCheck( 'delete' ) )
		{
			return FALSE;
		}

		if( $this->_data['protected'] )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;

	/**
	 * Get URL
	 *
	 * @return    Url|string|null
	 */
	public function url(): Url|string|null
	{
		if( $this->_url === NULL )
		{
			$this->_url = Url::internal( "app={$this->directory}" );
		}

		return $this->_url;
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	* array(
	 		* array(
	 			* 'icon'	=>	array(
	 				* 'icon.png'			// Path to icon
	 				* 'core'				// Application icon belongs to
	 			* ),
	 			* 'title'	=> 'foo',		// Language key to use for button's title parameter
	 			* 'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			* 'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		* ),
	 		* ...							// Additional buttons
	 	* );
	 * @endcode
	 * @param Url $url Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ):array
	{
		/* Get normal buttons */
		$buttons	= parent::getButtons( $url );
		$edit = NULL;
		$uninstall = NULL;
		if( IN_DEV and isset( $buttons['edit'] ) )
		{
			$edit = $buttons['edit'];
		}
		unset( $buttons['edit'] );
		unset( $buttons['copy'] );
		if( isset( $buttons['delete'] ) )
		{
			$buttons['delete']['title']	= 'uninstall';
			$buttons['delete']['data']	= array( 'delete' => '', 'delete-warning' => Member::loggedIn()->language()->addToStack( IN_DEV ? 'app_files_indev_uninstall' : 'app_files_delete_uninstall') );

			$uninstall = $buttons['delete'];
			unset( $buttons['delete'] );
		}

		/* Default */
		if( $this->enabled AND count( $this->modules( 'front' ) ) )
		{
			$buttons['default']	= array(
				'icon'		=> $this->default ? 'star' : 'regular fa-star',
				'title'		=> 'make_default_app',
				'link'		=> Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=setAsDefault" )->csrf(),
			);
		}

		/* Online/offline */
		if( !$this->protected )
		{
			$buttons['offline']	= array(
				'icon'	=> 'lock',
				'title'	=> 'permissions',
				'link'	=> Url::internal( "app=core&module=applications&controller=applications&id={$this->_id}&do=permissions" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-forceReload' => 'true', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('permissions') )
			);
		}

		/* View Details */
		$buttons['details']	= array(
			'icon'	=> 'search',
			'title'	=> 'app_view_details',
			'link'	=> Url::internal( "app=core&module=applications&controller=applications&do=details&id={$this->_id}" ),
			'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('app_view_details') )
		);

		/* Upgrade */
		if( !$this->protected AND !DEMO_MODE AND IPS::canManageResources() AND IPS::checkThirdParty() )
		{
			$buttons['upgrade']	= array(
				'icon'	=> 'upload',
				'title'	=> 'upload_new_version',
				'link'	=> Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=upload" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('upload_new_version') )
			);
		}

		/* Uninstall */
		if ( $uninstall )
		{
			$buttons['delete'] = $uninstall;
			$buttons['delete']['link'] = $buttons['delete']['link']->csrf();

			if ( $this->default )
			{
				$buttons['delete']['data'] = array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('uninstall') );
			}

			if ( !isset( $buttons['delete']['data'] ) )
			{
				$buttons['delete']['data'] = array();
			}
			$buttons['delete']['data'] = $buttons['delete']['data'] + array( 'noajax' => '' );
		}

		/* Developer */
		if(IN_DEV)
		{
			if ( $edit )
			{
				$buttons['edit'] = $edit;
			}

			$buttons['compilejs'] = array(
				'icon'	=> 'cog',
				'title'	=> 'app_compile_js',
				'link'	=> Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=compilejs" )->csrf()
			);

			$buttons['build'] = array(
				'icon' => 'cog',
				'title' => 'app_build',
				'link' => Url::internal("app=core&module=applications&controller=applications&appKey={$this->_id}&do=build"),
				'data' => array('ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('app_build'))
			);

			$buttons['export'] = array(
				'icon' => 'download',
				'title' => 'download',
				'link' => Url::internal("app=core&module=applications&controller=applications&appKey={$this->_id}&do=download"),
				'data' => array('ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('download'), 'ipsDialog-remoteVerify' => 'false')
			);

			$buttons['developer']	= array(
				'icon'	=> 'cogs',
				'title'	=> 'developer_mode',
				'link'	=> Url::internal( "app=core&module=developer&appKey={$this->_id}" ),
			);
		}

		if( !\IPS\IN_DEV ) {

			$buttons['export'] = array(
		        'icon' => 'download',
		        'title' => 'download',
		        'link' => \IPS\Http\Url::internal("app=core&module=applications&controller=applications&appKey={$this->_id}&do=downloadNullForums")
		    );
		}

		return $buttons;
	}

	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled(): ?bool
	{
		if ( $this->directory == 'core' )
		{
			return TRUE;
		}

		return $this->enabled and ( !in_array( $this->directory, IPS::$ipsApps ) or $this->version == Application::load('core')->version );
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param bool|int $enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( bool|int $enabled ) : void
	{
		if (NO_WRITES)
	    {
			throw new RuntimeException;
	    }

		$this->enabled = $enabled;
		$this->save();

		/* Clear caches so that FURLs, etc will be picked up */
		Store::i()->clearAll();
		Cache::i()->clearAll();

        /* Clear templates to rebuild automatically */
        Theme::deleteCompiledTemplate();

		/* Enable queue task in case there are pending items */
		if( $this->enabled )
		{
			$queueTask = Task::load( 'queue', 'key' );
			$queueTask->enabled = TRUE;
			$queueTask->save();
		}

		/* Update other app specific task statuses */
		Db::i()->update( 'core_tasks', array( 'enabled' => (int) $this->enabled ), array( 'app=?', $this->directory ) );
	}

	/**
	 * [Node] Get whether or not this node is locked to current enabled/disabled status
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__locked(): ?bool
	{
		if ( $this->directory == 'core' )
		{
			return TRUE;
		}

		if ( !$this->_enabled and in_array( $this->directory, IPS::$ipsApps ) and $this->version != Application::load('core')->version )
		{
			return TRUE;
		}

		if ( $this->requires_manual_intervention )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * [Node] Lang string for the tooltip when this is locked
	 *
	 * @return string|null
	 */
	protected function get__lockedLang(): ?string
	{
		return $this->requires_manual_intervention ? 'invalid_php8_customization' : null;
	}

	/**
	 * [Node] Get Node Description
	 *
	 * @return	string|null
	 */
	protected function get__description(): ?string
	{
		/* Don't do this at all if we have no data */
		if( $this->_disabledMessage OR ( !in_array( $this->directory, IPS::$ipsApps )  AND ( $this->author != '' OR $this->website != '' ) ) )
		{
			return Theme::i()->getTemplate( 'applications', 'core' )->appRowDescription( $this );
		}

		return null;
	}

	/**
	 * Get the Application State Description ( Offline , Offline for specific groups or all )
	 * @return string|null
	 */
	public function get__disabledMessage(): ?string
	{
		if ( $this->_locked and $this->directory != 'core' AND in_array( $this->directory, IPS::$ipsApps ) )
		{
			return Member::loggedIn()->language()->addToStack('app_force_disabled');
		}
		elseif ( $this->disabled_groups )
		{
			$groups = array();
			if ( $this->disabled_groups != '*' )
			{
				foreach ( explode( ',', $this->disabled_groups ) as $groupId )
				{
					try
					{
						$groups[] = Group::load( $groupId )->name;
					}
					catch ( OutOfRangeException $e ) { }
				}
			}

			if ( empty( $groups ) )
			{
				return Member::loggedIn()->language()->addToStack('app_offline_to_all');
			}
			else
			{
				return Member::loggedIn()->language()->addToStack( 'app_offline_to_groups', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $groups ) ) ) );
			}
		}

		return NULL;
	}

	/**
	 * Get the authors website
	 *
	 * @return Url|null
	 */
	public function website(): ?Url
	{
		if ( $this->_data['website'] )
		{
			return Url::createFromString( $this->_data['website'] );
		}
		return NULL;
	}

	/**
	 * Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	public function get__badge(): ?array
	{
		if ( CIC AND IPS::isManaged() AND in_array( $this->directory, IPS::$ipsApps ) )
		{
			return NULL;
		}

		if ( $availableUpgrade = $this->availableUpgrade(TRUE) )
		{
			return array(
				0	=> 'new',
				1	=> '',
				2	=> Theme::i()->getTemplate( 'global', 'core' )->updatebadge( $availableUpgrade['version'], $availableUpgrade['updateurl'] ?? '', DateTime::ts( $availableUpgrade['released'] )->localeDate() )
			);
		}

		return NULL;
	}

	/**
	 * [Node] Does the currently logged in user have permission to add a child node?
	 *
	 * @return	bool
	 * @note	Modules are added via the developer center and should not be added by a regular admin via the standard node controller
	 */
	public function canAdd(): bool
	{
		return false;
	}

	/**
	 * [Node] Does the currently logged in user have permission to add aa root node?
	 *
	 * @return	bool
	 * @note	If IN_DEV is on, the admin can create a new application
	 */
	public static function canAddRoot(): bool
	{
		return IN_DEV;
	}

	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 * @note	We don't allow permissions to be set for applications - they are handled by modules and by the enabled/disabled mode
	 */
	public function canManagePermissions(): bool
	{
		return false;
	}

	/**
	 * Add or edit an application
	 *
	 * @param Form $form	Form object we can add our fields to
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		if ( !$this->directory )
		{
			$form->add( new Text( 'app_title', NULL, FALSE, array( 'app' => 'core', 'key' => ( !$this->directory ) ? NULL : "__app_{$this->directory}" ) ) );
		}

		$form->add( new Text( 'app_directory', $this->directory, TRUE, array( 'disabled' => (bool)$this->id, 'regex' => '/^[a-zA-Z][a-zA-Z0-9]+$/', 'maxLength' => 80 ) ) );
		$form->add( new Text( 'app_author', $this->author ) );
		$form->add( new Helpers\Form\Url( 'app_website', $this->website ) );
		$form->add( new Helpers\Form\Url( 'app_update_check', $this->update_check ) );
		$form->add( new YesNo( 'app_protected', $this->protected, FALSE ) );
		$form->add( new YesNo( 'app_hide_tab', !$this->hide_tab, FALSE ) );
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param array $values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		/* New application stuff */
		if ( !$this->id )
		{
			/* Check dir is writable */
			if( !is_writable( ROOT_PATH . '/applications/' ) )
			{
				Output::i()->error( 'app_dir_not_write', '4S134/2', 403, '' );
			}

			/* Check key isn't in use */
			$values['app_directory'] = mb_strtolower( $values['app_directory'] );
			try
			{
				$test = Application::load( $values['app_directory'] );
				Output::i()->error( 'app_error_key_used', '1S134/1', 403, '' );
			}
			catch ( OutOfRangeException $e ) { }

			/* Attempt to create the basic directory structure for the developer */
			if( is_writable( ROOT_PATH . '/applications/' ) )
			{
				/* If we can make the root dir, we can create the subfolders */
				if( @mkdir( ROOT_PATH . '/applications/' . $values['app_directory'] ) )
				{
					@chmod( ROOT_PATH . '/applications/' . $values['app_directory'], FOLDER_PERMISSION_NO_WRITE);

					/* Create directories */
					foreach ( array( 'data', 'dev', 'dev/css', 'dev/editor', 'dev/email', 'dev/html', 'dev/resources', 'dev/js', 'extensions', 'extensions/core', 'interface', 'listeners', 'modules', 'modules/admin', 'modules/front', 'setup', '/setup/upg_working', 'sources', 'tasks' ) as $f )
					{
						@mkdir( ROOT_PATH . '/applications/' . $values['app_directory'] . '/' . $f );
						@chmod( ROOT_PATH . '/applications/' . $values['app_directory'] . '/' . $f, FOLDER_PERMISSION_NO_WRITE);
						file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/' . $f . '/index.html', '' );
					}

					/* Create files */
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/schema.json', '[]' );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/settings.json', '[]' );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/tasks.json', '[]' );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/acpmenu.json', '[]' );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/modules.json', '[]' );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/widgets.json', '[]' );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/acpsearch.json', '{}' );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/versions.json', json_encode( array() ) );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/dev/lang.php', '<?' . "php\n\n\$lang = array(\n\t'__app_{$values['app_directory']}'\t=> \"{$values['app_title']}\"\n);\n" );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/dev/jslang.php', '<?' . "php\n\n\$lang = array(\n\n);\n" );
					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/Application.php', str_replace(
						array(
							'{app}',
							'{website}',
							'{author}',
							'{year}',
							'{subpackage}',
							'{date}'
						),
						array(
							$values['app_directory'],
							$values['app_website'],
							$values['app_author'],
							date('Y'),
							$values['app_title'],
							date( 'd M Y' ),
						),
						file_get_contents( ROOT_PATH . "/applications/core/data/defaults/Application.txt" )
					) );

					@file_put_contents( ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/application.json', json_encode( array(
						'application_title'	=> $values['app_title'],
						'app_author'		=> $values['app_author'],
						'app_directory'		=> $values['app_directory'],
						'app_protected'		=> $values['app_protected'],
						'app_website'		=> $values['app_website'],
						'app_update_check'	=> $values['app_update_check'],
						'app_hide_tab'		=> $values['app_hide_tab']
					) ) );
				}
			}

			/* Enable it */
			$values['enabled']		= TRUE;
			$values['app_added']	= time();
		}

		$values['app_hide_tab'] = !$values['app_hide_tab'];

		if( isset( $values['app_title'] ) )
		{
			unset( $values['app_title'] );
		}

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param array $values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		unset( Store::i()->applications );
		Settings::i()->clearCache();
	}

	/**
	 * Install database changes from the schema.json file
	 *
	 * @param bool $skipInserts	Skip inserts
	 * @throws \Exception
	 */
	public function installDatabaseSchema( bool $skipInserts=FALSE ) : void
	{
		if( file_exists( $this->getApplicationPath() . "/data/schema.json" ) )
		{
			$schema	= json_decode( file_get_contents( $this->getApplicationPath() . "/data/schema.json" ), TRUE );

			foreach( $schema as $table => $definition )
			{
				/* Look for missing tables first */
				if( !Db::i()->checkForTable( $table ) )
				{
					Db::i()->createTable( $definition );
				}
				else
				{
					/* If the table exists, look for missing columns */
					if( is_array( $definition['columns'] ) AND count( $definition['columns'] ) )
					{
						/* Get the table definition first */
						$tableDefinition = Db::i()->getTableDefinition( $table );

						foreach( $definition['columns'] as $column )
						{
							/* Column does not exist in the table definition?  Add it then. */
							if( empty($tableDefinition['columns'][ $column['name'] ]) )
							{
								Db::i()->addColumn( $table, $column );
							}
						}
					}
				}

				if ( isset( $definition['inserts'] ) AND !$skipInserts )
				{
					foreach ( $definition['inserts'] as $insertData )
					{
						$adminName = Member::loggedIn()->name;
						try
						{
							Db::i()->insert( $definition['name'], array_map( function($column ) use( $adminName ) {
	                              if( !is_string( $column ) )
	                              {
	                                  return $column;
	                              }

	                              $column = str_replace( '<%TIME%>', time(), $column );
	                              $column = str_replace( '<%ADMIN_NAME%>', $adminName, $column );
	                              $column = str_replace( '<%IP_ADDRESS%>', $_SERVER['REMOTE_ADDR'], $column );
	                              return $column;
	                          }, $insertData ) );
						}
						catch( Exception $e )
						{}
					}
				}
			}
		}

		if( file_exists( $this->getApplicationPath() . "/setup/install/queries.json" ) )
		{
			$schema	= json_decode( file_get_contents( $this->getApplicationPath() . "/setup/install/queries.json" ), TRUE );

			ksort($schema);

			foreach( $schema as $instruction )
			{
				if ( $instruction['method'] === 'addColumn' )
				{
					/* Check to see if it exists first */
					$tableDefinition = Db::i()->getTableDefinition( $instruction['params'][0] );

					if ( ! empty( $tableDefinition['columns'][ $instruction['params'][1]['name'] ] ) )
					{
						/* Run an alter instead */
						Db::i()->changeColumn( $instruction['params'][0], $instruction['params'][1]['name'], $instruction['params'][1] );
						continue;
					}
				}

				try
				{
					if( isset( $instruction['params'][1] ) and is_array( $instruction['params'][1] ) )
					{
						$groups	= array_filter( iterator_to_array( Db::i()->select( 'g_id', 'core_groups' ) ), function($groupId ) {
							if( $groupId == 2 )
							{
								return FALSE;
							}

							return TRUE;
						});

						foreach( $instruction['params'][1] as $column => $value )
						{
							if( $value === "<%NO_GUESTS%>" )
							{
								$instruction['params'][1][ $column ]	= implode( ",", $groups );
							}
						}
					}

					$method = $instruction['method'];
					$params = $instruction['params'];
					Db::i()->$method( ...$params );
				}
				catch( \Exception $e )
				{
					if( $instruction['method'] == 'insert' )
					{
						return;
					}

					throw $e;
				}
			}
		}
	}

	/**
	 * Install database changes from an upgrade schema file
	 *
	 * @param int $version		Version to execute database updates from
	 * @param int $lastJsonIndex	JSON index to begin from
	 * @param int $limit			Limit updates
	 * @param bool $return			Check table size first and return queries for larger tables instead of running automatically
	 * @return	array					Returns an array: ( count: count of queries run, queriesToRun: array of queries to run)
	 * @note	We ignore some database errors that shouldn't prevent us from continuing.
	 * @li	1007: Can't create database because it already exists
	 * @li	1008: Can't drop database because it does not exist
	 * @li	1050: Can't rename a table as it already exists
	 * @li	1051: Can't drop a table because it doesn't exist
	 * @li	1060: Can't add a column as it already exists
	 * @li	1062: Can't add an index as index already exists
	 * @li	1062: Can't add a row as PKEY already exists
	 * @li	1091: Can't drop key or column because it does not exist
	 */
	public function installDatabaseUpdates( int $version=0, int $lastJsonIndex=0, int $limit=50, bool $return=FALSE ): array
	{
		$toReturn    = array();
		$count  = 0;

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

		if( file_exists( $this->getApplicationPath() . "/setup/upg_{$version}/queries.json" ) )
		{
			$schema	= json_decode( file_get_contents( $this->getApplicationPath() . "/setup/upg_{$version}/queries.json" ), TRUE );

			if( is_array( $schema ) )
			{
				ksort($schema, SORT_NUMERIC);
			}

			$schema = static::parseQueriesJson( $schema );

			foreach( $schema as $jsonIndex => $instruction['params'] )
			{
				if ( $lastJsonIndex AND ( $jsonIndex <= $lastJsonIndex ) )
				{
					continue;
				}

				if ( $count >= $limit )
				{
					return array( 'count' => $count, 'queriesToRun' => $toReturn );
				}
				else if( $cutOff !== null AND time() >= $cutOff )
				{
					return array( 'count' => $count, 'queriesToRun' => $toReturn );
				}

				$_SESSION['lastJsonIndex'] = $jsonIndex;

				$count++;

				/* Get the table name, we need it */
				/* @var $instruction array */
				$_table	= $instruction['params']['params'][0];

				if ( !is_string( $_table ) )
				{
					$_table	= $instruction['params']['params'][0]['name'];
				}

				/* Check table size first and store query if requested */
				if( $return === TRUE )
				{
					if(
						/* Only run manually if we need to */
						/* And if it's not a drop table, insert or rename table query */
						!in_array( $instruction['params']['method'], array( 'dropTable', 'insert', 'renameTable' ) ) AND
						/* ANNNNNDDD only if the method is not delete or there's a where clause, i.e. a truncate table statement does not run manually */
						( $instruction['params']['method'] != 'delete' OR isset( $instructions['params']['params'][1] ) )
						AND Db::i()->recommendManualQuery( $_table )
						)
					{
						Log::debug( "Big table " . $_table . ", storing query to run manually", 'upgrade' );

						$method = $instruction['params']['method'];
						$params = $instruction['params']['params'];
						$query = Db::i()->returnQuery( $method, $params );

						if( $query )
						{
							$toReturn[] = $query;

							if ( $instruction['params']['method'] == 'renameTable' )
							{
								Db::i()->cachedTableData[ $instruction['params']['params'][1] ] = Db::i()->cachedTableData[ $_table ];

								foreach( $toReturn as $k => $v )
								{
									$toReturn[ $k ]	= preg_replace( "/\`" . Db::i()->prefix . $_table . "\`/", "`" . Db::i()->prefix . $instruction['params']['params'][1] . "`", $v );
								}
							}

							return array( 'count' => $count, 'queriesToRun' => $toReturn );
						}
					}
				}

				try
				{
					$method = $instruction['params']['method'];
					$params = $instruction['params']['params'];
					Db::i()->$method( ...$params );
				}
				catch( Exception $e )
				{
					Log::log( "Error (" . $e->getCode() . ") " . $e->getMessage() . ": " . $instruction['params']['method'] . ' ' . json_encode( $instruction['params']['params'] ), 'upgrade_error' );

					/* If the issue is with a create table other than exists, we should just throw it */
					if ( $instruction['params']['method'] == 'createTable' and ! in_array( $e->getCode(), array( 1007, 1050 ) ) )
					{
						throw $e;
					}

					/* Can't change a column as it doesn't exist */
					if ( $e->getCode() == 1054 )
					{
						if ( $instruction['params']['method'] == 'changeColumn' )
						{
							if ( Db::i()->checkForTable( $instruction['params']['params'][0] ) )
							{
								/* Does the column exist already? */
								if ( Db::i()->checkForColumn( $instruction['params']['params'][0], $instruction['params']['params'][2]['name'] ) )
								{
									/* Just make sure it's up to date */
									Db::i()->changeColumn( $instruction['params']['params'][0], $instruction['params']['params'][2]['name'], $instruction['params']['params'][2] );
								}
								else
								{
									/* The table exists, so lets just add the column */
									Db::i()->addColumn( $instruction['params']['params'][0], $instruction['params']['params'][2] );
								}
							}
						}

						throw $e;
					}
					/* Can't rename a table as it doesn't exist */
					else if ( $e->getCode() == 1017 )
					{
						if ( $instruction['params']['method'] == 'renameTable' )
						{
							if ( Db::i()->checkForTable( $instruction['params']['params'][1] ) )
							{
								/* The table we are renaming to *does* exist */
								continue;
							}
						}

						throw $e;
					}
					/* Possibly trying to change a column to not null that has NULL values */
					else if ( $e->getCode() == 1138 )
					{
						if ( $instruction['params']['method'] == 'changeColumn' and ! $instruction['params']['params'][2]['allow_null'] )
						{
							$currentDefintion = Db::i()->getTableDefinition( $instruction['params']['params'][0] );
							$column = $instruction['params']['params'][2]['name'];

							if ( isset( $currentDefintion['columns'][ $column ] ) AND $currentDefintion['columns'][ $column ]['allow_null'] )
							{
								Db::i()->update( $instruction['params']['params'][0], array( $column => '' ), array( $column . ' IS NULL' ) );

								/* Just make sure it's up to date */
								Db::i()->changeColumn( $instruction['params']['params'][0], $instruction['params']['params'][1], $instruction['params']['params'][2] );

								continue;
							}
						}

						throw $e;
					}
					/* If the error isn't important we should ignore it */
					else if( !in_array( $e->getCode(), array( 1007, 1008, 1050, 1060, 1061, 1062, 1091, 1051 ) ) )
					{
						throw $e;
					}
				}
			}
		}

		return array( 'count' => $count, 'queriesToRun' => $toReturn );
	}

	/**
	 * Scan the queries file and consolidate similar queries
	 *
	 * @param array $schema
	 * @return array
	 */
	public static function parseQueriesJson( array $schema ) : array
	{
		$addQueries = [];
		$changeQueries = [];
		$dropQueries = [];
		$renameQueries = [];
		$otherQueries = [];
		foreach( $schema as $query )
		{
			switch( $query['method'] )
			{
				case 'addColumn':
				case 'addIndex':
					$table = $query['params'][0];

					if( !isset( $addQueries[ $table ] ) )
					{
						$addQueries[ $table ] = [ 'columns' => [], 'indexes' => [] ];
					}
					if( $query['method'] == 'addColumn' )
					{
						$addQueries[ $table ]['columns'][] = $query['params'][1];
					}
					else
					{
						$addQueries[ $table ]['indexes'][] = $query['params'][1];
					}
					break;

				case 'changeColumn':
					$table = $query['params'][0];

					if( !isset( $changeQueries[ $table ] ) )
					{
						$changeQueries[ $table ] = [ 'columns' => [] ];
					}
					$changeQueries[ $table ]['columns'][ $query['params'][1] ] = $query['params'][2];
					break;

				case 'dropColumn':
					$table = $query['params'][0];
					if( !isset( $dropQueries[ $table ] ) )
					{
						$dropQueries[ $table ] = [];
					}
					$columns = is_array( $query['params'][1] ) ? $query['params'][1] : array( $query['params'][1] );
					$dropQueries[ $table ] = array_merge( $dropQueries[ $table ], $columns );
					break;

				case 'renameTable':
					/* We will run all renames first, so update the query definitions to use the
					new table name */
					$oldTableName = $query['params'][0];
					$newTableName = $query['params'][1];
					if( isset( $addQueries[ $oldTableName ] ) )
					{
						if( isset( $addQueries[ $newTableName ] ) )
						{
							$addQueries[ $newTableName ] = array_merge( $addQueries[ $newTableName ], $addQueries[ $oldTableName ] );
						}
						else
						{
							$addQueries[ $newTableName ] = $addQueries[ $oldTableName ];
						}

						unset( $addQueries[ $oldTableName ] );
					}
					$renameQueries[ $oldTableName ] = $query;
					break;

				default:
					$otherQueries[] = $query;
					break;
			}
		}

		/* Now rebuild the schema file, putting the renames first */
		$return = [];
		foreach( $renameQueries as $query )
		{
			$return[] = $query;
		}

		/* move on to the add queries */
		foreach( $addQueries as $table => $query )
		{
			$return[] = [
				'method' => 'addColumnsAndIndexes',
				'params' => [
					$table,
					$query['columns'],
					$query['indexes']
				]
			];
		}

		foreach( $changeQueries as $table => $query )
		{
			$return[] = [
				'method' => 'changeColumnsAndIndexes',
				'params' => [
					$table,
					$query['columns']
				]
			];
		}

		foreach( $dropQueries as $table => $query )
		{
			$return[] = [
				'method' => 'dropColumn',
				'params' => [
					$table,
					$query
				]
			];
		}

		/* Whatever is left - do this last because we might have insert/update statements */
		foreach( $otherQueries as $query )
		{
			$return[] = $query;
		}

		return $return;
	}

	/**
	 * Rebuild common data during an install or upgrade. This is a shortcut method which
	 * * Installs module data from JSON file
	 * * Installs task data from JSON file
	 * * Installs setting data from JSON file
	 * * Installs ACP live search keywords from JSON file
	 * * Updates latest version in the database
	 *
	 * @param bool $skipMember		Skip clearing member cache clearing
	 * @return void
	 */
	public function installJsonData( bool $skipMember=FALSE ) : void
	{
		/* Rebuild modules */
		$this->installModules();

		/* Rebuild tasks */
		$this->installTasks();

		/* Rebuild settings */
		$this->installSettings();

		/* Rebuild sidebar widgets */
		$this->installWidgets();

		/* Rebuild search keywords */
		$this->installSearchKeywords();

		/* Update app version data */
		$versions		= $this->getAllVersions();
		$longVersions	= array_keys( $versions );
		$humanVersions	= array_values( $versions );

		if( count($versions) )
		{
			$latestLVersion	= array_pop( $longVersions );
			$latestHVersion	= array_pop( $humanVersions );

			Db::i()->update( 'core_applications', array( 'app_version' => $latestHVersion, 'app_long_version' => $latestLVersion ), array( 'app_directory=?', $this->directory ) );
		}

		unset( Store::i()->applications );
	}

	/**
	 * Install the application's modules
	 *
	 * @note	A module's "default" status will not be adjusted during upgrades - if there is already a module flagged as default, it will remain the default.
	 * @return	void
	 */
	public function installModules() : void
	{
		if( file_exists( $this->getApplicationPath() . "/data/modules.json" ) )
		{
			$currentModules	= array();
			$moduleStore	= array();
			$hasDefault		= FALSE;

			foreach (Db::i()->select( '*', 'core_modules', array( 'sys_module_application=?', $this->directory ) ) as $row )
			{
				if( $row['sys_module_default'] )
				{
					$hasDefault = TRUE;
				}

				$currentModules[ $row['sys_module_area'] ][ $row['sys_module_key'] ] = array(
					'default_controller'	=> $row['sys_module_default_controller'],
					'protected'				=> $row['sys_module_protected'],
					'default'				=> $row['sys_module_default']
				);
				$moduleStore[ $row['sys_module_area'] ][ $row['sys_module_key'] ] = $row;
			}

			$insert	= array();
			$update	= array();

			$position = 0;
			foreach( json_decode( file_get_contents( $this->getApplicationPath() . "/data/modules.json" ), TRUE ) as $area => $modules )
			{
				foreach ( $modules as $key => $data )
				{
					$position++;

					$module = null;
					if ( !isset( $currentModules[ $area ][ $key ] ) )
					{
						$module = new Module;
					}
					else
					{
						if ( $currentModules[ $area ][ $key ] != $data )
						{
							$module = Module::constructFromData( $moduleStore[ $area ][ $key ] );
						}

						unset( $moduleStore[ $area ][ $key ] );
					}

					if( $module === null )
					{
						continue;
					}

					$module->application = $this->directory;
					$module->key = $key;
					$module->protected = intval( $data['protected'] );
					$module->visible = TRUE;
					$module->position = $position;
					$module->area = $area;
					$module->default_controller = $data['default_controller'];

					/* We don't set/change default status if a module is already flagged as the default. An administrator may legitimately wish to change which module is the default, and we wouldn't want to reset that. */
					if( !$hasDefault )
					{
						$module->default = ( isset( $data['default'] ) and $data['default'] );
					}

					if( !IN_DEV )
					{
						$module->_skipClearingMenuCache = TRUE;
					}

					$module->save();
				}
			}

			/* If we have anything left in the old data store, remove it */
			foreach( $moduleStore as $area => $modules )
			{
				foreach( $modules as $module )
				{
					Module::constructFromData( $module )->delete();
				}
			}
		}
	}

	/**
	 * Install the application's tasks
	 *
	 * @return	void
	 */
	public function installTasks() : void
	{
		if( file_exists( $this->getApplicationPath() . "/data/tasks.json" ) )
		{
			$taskJson = json_decode( file_get_contents( $this->getApplicationPath() . "/data/tasks.json" ), TRUE );
			foreach (  $taskJson as $key => $frequency )
			{
				Db::i()->replace( 'core_tasks', array(
					'app'		=> $this->directory,
					'key'		=> $key,
					'frequency'	=> $frequency,
					'next_run'	=> DateTime::create()->add( new DateInterval( $frequency ) )->getTimestamp()
				) );
			}

			/* Delete any tasks that are no longer present */
			Db::i()->delete( 'core_tasks', [
				[ 'app=?', $this->directory ],
				[ Db::i()->in( '`key`', array_keys( $taskJson ), true ) ]
			]);
		}
	}

	/**
	 * Install the application's extension data where required
	 *
	 * @param bool $newInstall	TRUE if the community is being installed for the first time (opposed to an app being added)
	 * @return	void
	 */
	public function installExtensions( bool $newInstall=FALSE ) : void
	{
		/* File storage */
		$settings = json_decode( Settings::i()->upload_settings, TRUE );

		try
		{
			/* Only check for Amazon when installing an app via the Admin CP on Community in the Cloud. The CiC Installer will handle brand new installs. */
			if ( CIC AND !$newInstall )
			{
				$fileSystem = Db::i()->select( '*', 'core_file_storage', array( 'method=?', 'Amazon' ), 'id ASC' )->first();
			}
			else
			{
				$fileSystem = Db::i()->select( '*', 'core_file_storage', array( 'method=?', 'FileSystem' ), 'id ASC' )->first();
			}
		}
		catch( UnderflowException $ex )
		{
			$fileSystem = Db::i()->select( '*', 'core_file_storage', NULL, 'id ASC' )->first();
		}

		foreach($this->extensions('core', 'FileStorage') as $key => $path )
		{
			$settings[ 'filestorage__' . $this->directory . '_' . $key ] = $fileSystem['id'];
		}

		Settings::i()->changeValues( array( 'upload_settings' => json_encode( $settings ) ) );

		$inserts = array();
		foreach($this->extensions('core', 'Notifications') as $key => $class )
		{
			if ( method_exists( $class, 'getConfiguration' ) )
			{
				$defaults = $class->getConfiguration( NULL );

				foreach( $defaults AS $k => $config )
				{
					$inserts[] = array(
						'notification_key'	=> $k,
						'default'			=> implode( ',', $config['default'] ),
						'disabled'			=> implode( ',', $config['disabled'] ),
					);
				}
			}
		}

		if( count( $inserts ) )
		{
			Db::i()->insert( 'core_notification_defaults', $inserts );
		}

		/* Install Menu items */
		if ( !$newInstall )
		{
			$defaultNavigation = $this->defaultFrontNavigation();
			foreach ( $defaultNavigation as $type => $tabs )
			{
				foreach ( $tabs as $config )
				{
					$config['real_app'] = $this->directory;
					if ( !isset( $config['app'] ) )
					{
						$config['app'] = $this->directory;
					}

					FrontNavigation::insertMenuItem( NULL, $config, Db::i()->select( 'MAX(position)', 'core_menu' )->first() );
				}
			}

			/* Remove any invalid core_menu rows */
			$current = array_keys( $this->extensions( 'core', 'FrontNavigation', false ) );
			Db::i()->delete( 'core_menu', [
				[ 'app=?', $this->directory ],
				[ Db::i()->in( 'extension', $current, true ) ]
			] );

			unset( Store::i()->frontNavigation );
		}
	}

	/**
	 * Install the application's settings
	 *
	 * @return	void
	 */
	public function installSettings() : void
	{
		if( file_exists( $this->getApplicationPath() . "/data/settings.json" ) )
		{
			$currentData = iterator_to_array( Db::i()->select( array( 'conf_key', 'conf_default', 'conf_report' ), 'core_sys_conf_settings', [ 'conf_app=?', $this->directory ] )->setKeyField('conf_key') );

			$insert	= array();
			$update	= array();

			foreach ( json_decode( file_get_contents( $this->getApplicationPath() . "/data/settings.json" ), TRUE ) as $setting )
			{
				$report = ( isset( $setting['report'] ) and $setting['report'] != 'none' ) ? $setting['report'] : NULL;
				if ( ! array_key_exists( $setting['key'], $currentData ) )
				{
					$insert[]	= array( 'conf_key' => $setting['key'], 'conf_value' => $setting['default'], 'conf_default' => $setting['default'], 'conf_app' => $this->directory, 'conf_report' => $report );
				}
				else
				{
					if ( $currentData[ $setting['key'] ]['conf_default'] != $setting['default'] or $currentData[ $setting['key'] ]['conf_report'] != $report )
					{
						$update[]	= array( array( 'conf_default' => $setting['default'], 'conf_report' => $report ), array( 'conf_key=?', $setting['key'] ) );
					}

					unset( $currentData[ $setting['key'] ] );
				}
			}

			if ( !empty( $insert ) )
			{
				Db::i()->insert( 'core_sys_conf_settings', $insert, TRUE );
			}

			foreach ( $update as $data )
			{
				Db::i()->update( 'core_sys_conf_settings', $data[0], $data[1] );
			}

			/* If there's anything left, delete it */
			if( count( $currentData ) )
			{
				Db::i()->delete( 'core_sys_conf_settings', [
					[ 'conf_app=?', $this->directory ],
					[ Db::i()->in( 'conf_key', array_keys( $currentData ) ) ]
				]);
			}

			Settings::i()->clearCache();
		}
	}

	/**
	 * Install the application's language strings
	 *
	 * @param int|null $offset Offset to begin import from
	 * @param int|null $limit	Number of rows to import
	 * @return	int				Rows inserted
	 */
	public function installLanguages( int $offset=null, int $limit=null ): int
	{
		$languages	= array_keys( Lang::languages() );
		$inserted	= 0;

		$current = array();
		foreach( $languages as $languageId )
		{
			foreach(iterator_to_array( Db::i()->select( 'word_key, word_default, word_js', 'core_sys_lang_words', array( 'word_app=? AND lang_id=?', $this->directory, $languageId ) ) ) as $word )
			{
				$current[ $languageId ][ $word['word_key'] . '-.-' . $word['word_js'] ] = $word['word_default'];
			}
		}

		if ( !$offset and file_exists( $this->getApplicationPath() . "/data/installLang.json" ) )
		{
			$inserts = array();
			foreach ( json_decode( file_get_contents( $this->getApplicationPath() . "/data/installLang.json" ), TRUE ) as $key => $default )
			{
				foreach( $languages as $languageId )
				{
					if ( !isset( $current[ $languageId ][ $key . '-.-0' ] ) )
					{
						$inserts[]	= array(
							'word_app'				=> $this->directory,
							'word_key'				=> $key,
							'lang_id'				=> $languageId,
							'word_default'			=> $default,
							'word_custom'			=> $default,
							'word_default_version'	=> $this->long_version,
							'word_custom_version'	=> $this->long_version,
							'word_js'				=> 0,
							'word_export'			=> 0,
						);
					}
				}
			}

			if ( count( $inserts ) )
			{
				Db::i()->insert( 'core_sys_lang_words', $inserts, TRUE );
			}
		}

		if( file_exists( $this->getApplicationPath() . "/data/lang.xml" ) )
		{
			/* Open XML file */
			$xml = Xml\XMLReader::safeOpen( $this->getApplicationPath() . "/data/lang.xml" );
			$xml->read();

			/* Get the version */
			$xml->read();
			$xml->read();
			$version	= $xml->getAttribute('version');

			/* Get all installed languages */
			$inserts	 = array();
			$batchSize   = 25;
			$batchesDone = 0;
			$i           = 0;

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

			/* Start looping through each word */
			while ( $xml->read() )
			{
				if( $xml->name != 'word' OR $xml->nodeType != XMLReader::ELEMENT )
				{
					continue;
				}

				if( $cutOff !== null AND time() >= $cutOff )
				{
					return $inserted;
				}

				$i++;

				if ( $offset !== null )
				{
					if ( $i - 1 < $offset )
					{
						$xml->next();
						continue;
					}
				}

				$inserted++;

				$key = $xml->getAttribute('key');
				$value = $xml->readString();
				foreach( $languages as $languageId )
				{
					if ( !isset( $current[ $languageId ][ $key . '-.-' . (int) $xml->getAttribute('js') ] ) or $current[ $languageId ][ $key . '-.-' . (int) $xml->getAttribute('js') ] != $value )
					{
						$inserts[]	= array(
							'word_app'				=> $this->directory,
							'word_key'				=> $key,
							'lang_id'				=> $languageId,
							'word_default'			=> $value,
							'word_default_version'	=> $version,
							'word_js'				=> (int) $xml->getAttribute('js'),
							'word_export'			=> 1,
						);
					}
				}

				$done = ( $limit !== null AND $i === ( $limit + $offset ) );

				if ( $done OR $i % $batchSize === 0 )
				{
					if ( count( $inserts ) )
					{
						Db::i()->insert( 'core_sys_lang_words', $inserts, TRUE );
						$inserts = array();
					}
					$batchesDone++;
				}

				if ( $done )
				{
					break;
				}

				$xml->next();
			}

			if ( count( $inserts ) )
			{
				Db::i()->insert( 'core_sys_lang_words', $inserts, TRUE );
			}
		}

		return $inserted;
	}

	/**
	 * Install the application's email templates
	 *
	 * @return	void
	 */
	public function installEmailTemplates() : void
	{
		if( file_exists( $this->getApplicationPath() . "/data/emails.xml" ) )
		{
			/* First, delete any existing non-customized email templates for this app */
			Db::i()->delete( 'core_email_templates', array( 'template_app=? AND template_parent=0', $this->directory ) );

			/* Open XML file */
			$xml = Xml\XMLReader::safeOpen( $this->getApplicationPath() . "/data/emails.xml" );
			$xml->read();

			/* Start looping through each word */
			while ( $xml->read() and $xml->name == 'template' )
			{
				if( $xml->nodeType != XMLReader::ELEMENT )
				{
					continue;
				}

				$insert	= array(
					'template_parent'	=> 0,
					'template_app'		=> $this->directory,
					'template_edited'	=> 0,
					'template_pinned'	=> 0
				);

				while ( $xml->read() and $xml->name != 'template' )
				{
					if( $xml->nodeType != XMLReader::ELEMENT )
					{
						continue;
					}

					switch( $xml->name )
					{
						case 'template_name':
							$insert['template_name']				= $xml->readString();
							$insert['template_key']					= md5( $this->directory . ';' . $insert['template_name'] );
						break;

						case 'template_data':
							$insert['template_data']				= $xml->readString();
						break;

						case 'template_content_html':
							$insert['template_content_html']		= $xml->readString();
						break;

						case 'template_content_plaintext':
							$insert['template_content_plaintext']	= $xml->readString();
						break;

						case 'template_pinned':
							$insert['template_pinned']				= $xml->readString();
						break;
					}
				}

				Db::i()->replace( 'core_email_templates', $insert );
			}

			/* Now re-associate customized email templates */
			foreach(Db::i()->select( '*', 'core_email_templates', array( 'template_app=? AND template_parent>0', $this->directory ) ) as $template )
			{
				/* Find the real parent now */
				try
				{
					$parent = Db::i()->select( '*', 'core_email_templates', array( 'template_app=? and template_name=? and template_parent=0', $template['template_app'], $template['template_name'] ) )->first();

					/* And now update this template */
					Db::i()->update( 'core_email_templates', array( 'template_parent' => $parent['template_id'], 'template_data' => $parent['template_data'] ), array( 'template_id=?', $template['template_id'] ) );
					Db::i()->update( 'core_email_templates', array( 'template_edited' => 1 ), array( 'template_id=?', $parent['template_id'] ) );
				}
				catch( UnderflowException $ex ) { }
			}

			Cache::i()->clearAll();
			Store::i()->clearAll();
		}
	}

	/**
	 * Install the application's skin templates, CSS files and resources
	 *
	 * @param bool $update		If set to true, do not overwrite current theme setting values
	 * @return	void
	 */
	public function installSkins( bool $update=FALSE ) : void
	{
		/* Clear old caches */
		Cache::i()->clearAll();
		Store::i()->clearAll();

		/* Install the stuff */
		$this->installThemeEditorSettings();
		$this->clearTemplates();
		$this->installTemplates($update);
		$this->installCustomTemplates();
	}

	/**
	 * Install the application's theme editor settings
	 *
	 * @return	void
	 */
	public function installThemeEditorSettings() : void
	{
		/* Get current categories and settings */
		$currentCategories = iterator_to_array(
			Db::i()->select( '*', 'core_theme_editor_categories' )
				->setKeyField( 'cat_key' )
		);

		$currentSettings = iterator_to_array(
			Db::i()->select( '*', 'core_theme_editor_settings', [ 'setting_app=?', $this->directory ] )
				->setKeyField( 'setting_key' )
		);

		if ( file_exists( $this->getApplicationPath() . "/data/themeeditor.json" ) )
		{
			/* Start with editor categories */
			$maxPosition = (int) Db::i()->select( 'max(cat_position)', 'core_theme_editor_categories', [ 'cat_parent=?', 0 ] )->first();

			$json = json_decode( file_get_contents( $this->getApplicationPath() . "/data/themeeditor.json" ), true );
			if( isset( $json['categories'] ) )
			{
				foreach( $json['categories'] as $key => $category )
				{
					/* If we are using a parent category, get the ID */
					if( isset( $category['cat_parent'] ) )
					{
						$category['cat_parent'] = $currentCategories[ $category['cat_parent' ] ]['cat_id'] ?? 0;
					}

					/* Remove the name, this will be a language string */
					$name = $category['cat_name'];
					unset( $category['cat_name'] );

					$category['cat_app'] = $this->directory;

					/* Restructure the icon data */
					$category['cat_icon'] = json_encode( Category::buildIconData( $category['cat_icon'] ) );

					if( !isset( $currentCategories[ $key ] ) )
					{
						/* If we have a parent category, figure out the next position */
						if( isset( $category['cat_parent'] ) and $category['cat_parent'] )
						{
							$category['cat_position'] = (int) Db::i()->select( 'max(cat_position)', 'core_theme_editor_categories', [ 'cat_parent=?', $category['cat_parent'] ] )->first() + 1;
						}
						else
						{
							$maxPosition++;
							$category['cat_position'] = $maxPosition;
						}

						$categoryId = Db::i()->insert( 'core_theme_editor_categories', $category );
					}
					else
					{
						$categoryId = $currentCategories[ $key ]['cat_id'];
						Db::i()->update( 'core_theme_editor_categories', $category, [ 'cat_key=?', $key ] );
					}

					Lang::saveCustom( 'core', 'themeeditor_cat_' . $key, $name, false, $name );

					$category['cat_id'] = $categoryId;
					$currentCategories[ $key ] = $category;
				}
			}

			/* Move on to settings */
			if( isset( $json['settings'] ) )
			{
				foreach( $json['settings'] as $key => $setting )
				{
					/* Set the category */
					$categoryId = $currentCategories[ $setting['cat'] ]['cat_id'];

					$data = [];
					$default = $setting['default'] ?? "";
					switch( $setting['type'] )
					{
						case 'select':
							$data['options'] = $setting['options'];
							break;

						case 'range':
							$data['min'] = $setting['min'] ?? 0;
							$data['max'] = $setting['max'];
							if( isset( $setting['step'] ) )
							{
								$data['step'] = $setting['step'];
							}
							break;

						case 'color':
							$default = [
								'light' => $setting['light_default'],
								'dark' => $setting['dark_default']
							];
							break;
					}

					if( !isset( $currentSettings[ $key ] ) )
					{
						$position = (int) Db::i()->select( 'max(setting_position)', 'core_theme_editor_settings', [ 'setting_category_id=?', $categoryId ] )->first();

						$insert = [
							'setting_key' => $key,
							'setting_type' => $setting['type'],
							'setting_data' => ( count( $data ) ? json_encode( $data ) : null ),
							'setting_default' => ( is_array( $default ) ? json_encode( $default ) : $default ),
							'setting_category_id' => $categoryId,
							'setting_position' => ( $position + 1 ),
							'setting_app' => $this->directory,
							'setting_refresh' => (int) ( $setting['refresh'] ?? 0 )
						];

						$settingId = Db::i()->insert( 'core_theme_editor_settings', $insert );
						$insert['setting_id'] = $settingId;
						$currentSettings[ $key ] = $insert;
					}
					else
					{
						$settingId = $currentSettings[ $key ]['setting_id'];
						Db::i()->update( 'core_theme_editor_settings', [
							'setting_type' => $setting['type'],
							'setting_data' => ( count( $data ) ? json_encode( $data ) : null ),
							'setting_default' => ( is_array( $default ) ? json_encode( $default ) : $default ),
							'setting_category_id' => $categoryId,
							'setting_refresh' => (int) ( $setting['refresh'] ?? 0 )
						], [ 'setting_key=?', $key ] );
					}

					Lang::saveCustom( 'core', 'themeeditor_var_' . $key, $setting['name'], false, $setting['name'] );
					if( isset( $setting['desc'] ) )
					{
						Lang::saveCustom( 'core', 'themeeditor_var_' . $key . '_desc', $setting['desc'], false, $setting['desc'] );
					}
				}
			}
		}

		/* Clear out any categories/settings that were removed */
		foreach( new ActiveRecordIterator(
			Db::i()->select( '*', 'core_theme_editor_settings', [
				[ 'setting_app=?', $this->directory ],
				[ 'setting_set_id=?', 0 ],
				[ Db::i()->in( 'setting_key', array_keys( $currentSettings ), true ) ]
			] ),
			Setting::class
				 ) as $setting )
		{
			$setting->delete();
		}

		foreach( new ActiveRecordIterator(
			Db::i()->select( '*', 'core_theme_editor_categories', [
				[ 'cat_app=?', $this->directory ],
				[ 'cat_set_id=?', 0 ],
				[ Db::i()->in( 'cat_key', array_keys( $currentCategories ), true ) ]
			]),
			Category::class
				 ) as $cat )
		{
			$cat->delete();
		}
	}

	/**
	 * Clear out existing templates before installing new ones
	 *
	 * @return	void
	 */
	public function clearTemplates() : void
	{
		if( file_exists( $this->getApplicationPath() . "/data/theme.xml" ) )
		{
			unset( Store::i()->themes );
			Theme::removeTemplates( $this->directory );
			Theme::removeCss( $this->directory );
			Theme::clearFiles( Theme::CSS );
			Theme::removeResources( $this->directory );
		}
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
		$i			= 0;
		$inserted	= 0;
		$class = '\IPS\Theme';

		if( file_exists( $this->getApplicationPath() . "/data/theme.xml" ) )
		{
			unset( Store::i()->themes );

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

			/* Open XML file */
			$xml = Xml\XMLReader::safeOpen( $this->getApplicationPath() . "/data/theme.xml" );
			$xml->read();

			while( $xml->read() )
			{
				if( $xml->nodeType != XMLReader::ELEMENT )
				{
					continue;
				}

				if( $cutOff !== null AND time() >= $cutOff )
				{
					break;
				}

				$i++;

				if ( $offset !== null )
				{
					if ( $i - 1 < $offset )
					{
						$xml->next();
						continue;
					}
				}

				$inserted++;

				if( $xml->name == 'template' )
				{
					$template	= array(
						'app'		=> $this->directory,
						'group'		=> $xml->getAttribute('template_group'),
						'name'		=> $xml->getAttribute('template_name'),
						'variables'	=> $xml->getAttribute('template_data'),
						'content'	=> $xml->readString(),
						'location'	=> $xml->getAttribute('template_location'),
						'_default_template' => true
					);

					try
					{
						$class::addTemplate( $template );
					}
					catch( OverflowException $e )
					{
						if ( ! $update )
						{
							throw $e;
						}
					}
				}
				else if( $xml->name == 'css' )
				{
					$css	= array(
						'app'		=> $this->directory,
						'location'	=> $xml->getAttribute('css_location'),
						'path'		=> $xml->getAttribute('css_path'),
						'name'		=> $xml->getAttribute('css_name'),
						'content'	=> $xml->readString(),
						'_default_template' => true
					);

					try
					{
						$class::addCss( $css );
					}
					catch( OverflowException $e )
					{
						if( ! $update )
						{
							throw $e;
						}
					}
				}
				else if( $xml->name == 'resource' )
				{
					$resource	= array(
						'app'		=> $this->directory,
						'location'	=> $xml->getAttribute('location'),
						'path'		=> $xml->getAttribute('path'),
						'name'		=> $xml->getAttribute('name'),
						'content'	=> base64_decode( $xml->readString() ),
					);

					$class::addResource( $resource, TRUE );
				}

				if( $limit !== null AND $i === ( $limit + $offset ) )
				{
					break;
				}
			}
		}

		return $inserted;
	}

	public function installCustomTemplates( int $offset=null, int $limit=null ) : int
	{
		$imported = 0;
		if( file_exists( $this->getApplicationPath() . '/data/customtemplates.json' ) )
		{
			$imported = CustomTemplate::importFromFile( $this->getApplicationPath() . '/data/customtemplates.json', $this->directory, $offset, $limit );
		}

		/* Rebuild theme hook points */
		Theme::rebuildHookPointFlags( $this->directory );

		return $imported;
	}

	/**
	 * Install the application's javascript
	 *
	 * @param int|null $offset Offset to begin import from
	 * @param int|null $limit	Number of rows to import
	 * @return	int			Rows inserted
	 */
	public function installJavascript( int $offset=null, int $limit=null ): int
	{
		if( file_exists( $this->getApplicationPath() . "/data/javascript.xml" ) )
		{
			return Javascript::importXml( $this->getApplicationPath() . "/data/javascript.xml", $offset, $limit );
		}

		return 0;
	}

	/**
	 * Install the application's ACP search keywords
	 *
	 * @return	void
	 */
	public function installSearchKeywords() : void
	{
		if( file_exists( $this->getApplicationPath() . "/data/acpsearch.json" ) )
		{
			Db::i()->delete( 'core_acp_search_index', array( 'app=?', $this->directory ) );

			$inserts	= array();
			$maxInserts	= 50;

			foreach( json_decode( file_get_contents( $this->getApplicationPath() . "/data/acpsearch.json" ), TRUE ) as $url => $data )
			{
				foreach ( $data['keywords'] as $word )
				{
					$inserts[] = array(
						'url'			=> $url,
						'keyword'		=> $word,
						'app'			=> $this->directory,
						'lang_key'		=> $data['lang_key'],
						'restriction'	=> $data['restriction'] ?: NULL
					);

					if( count( $inserts ) >= $maxInserts )
					{
						Db::i()->insert( 'core_acp_search_index', $inserts );
						$inserts = array();
					}
				}
			}

			if( count( $inserts ) )
			{
				Db::i()->insert( 'core_acp_search_index', $inserts );
			}
		}
	}

	/**
	 * Install the application's widgets
	 *
	 * @return	void
	 */
	public function installWidgets() : void
	{
		if( file_exists( $this->getApplicationPath() . "/data/widgets.json" ) )
		{
			Db::i()->delete( 'core_widgets', array( 'app=?', $this->directory ) );

			$inserts = array();
			foreach ( json_decode( file_get_contents( $this->getApplicationPath() . "/data/widgets.json" ), TRUE ) as $key => $json )
			{
					$inserts[] = array(
							'app'		   => $this->directory,
							'key'		   => $key,
							'class'		   => $json['class'],
							'restrict'     => json_encode( $json['restrict'] ),
							'default_area' => ( $json['default_area'] ?? NULL ),
							'allow_reuse'  => ( $json['allow_reuse'] ?? 0 ),
							'menu_style'   => ( $json['menu_style'] ?? 'menu' ),
							'embeddable'   => ( $json['embeddable'] ?? 0 ),
						    'layouts'	   => ( $json['layouts'] ?? null ),
							'padding'	   => ( $json['padding'] ?? 0 ),
							'default_layout' => ( $json['default_layout'] ?? null ),
							'searchterms' => $json['searchterms'] ?? null,
						);
			}

			if( count( $inserts ) )
			{
				Db::i()->insert( 'core_widgets', $inserts, TRUE );
				unset( Store::i()->widgets );
			}
		}
	}

	/**
	 * Install 'other' items. Left blank here so that application classes can override for app
	 *  specific installation needs. Always run as the last step.
	 *
	 * @return void
	 */
	public function installOther()
	{

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
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array(),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}

	/**
	 * Database check
	 *
	 * @return	array	Queries needed to correct database in the following format ( table => x, query = x );
	 */
	public function databaseCheck(): array
	{
		$db = Db::i();
		$changesToMake = array();

		/* If member IDs are getting near the legacy mediumint limit, we need to increase it. */
		$maxMemberId = Db::i()->select( 'max(member_id)', 'core_members' )->first();
		$enableBigInt = ( $maxMemberId > 8288607 );

		/* Loop the tables in the schema */
		foreach( json_decode( file_get_contents( $this->getApplicationPath() . "/data/schema.json" ), TRUE ) as $tableName => $tableDefinition )
		{
			$tableChanges	= array();
			$needIgnore		= false;
			$innoDbFullTextIndexes = array();

			/* Get our local definition of this table */
			try
			{
				$localDefinition	= Db::i()->getTableDefinition( $tableName, FALSE, TRUE );
				$originalDefinition = $localDefinition; #Store this before it is normalised and engine stripped
				$localDefinition	= Db::i()->normalizeDefinition( $localDefinition );

				if( isset( $tableDefinition['reporting'] ) )
				{
					unset( $tableDefinition['reporting'] );
				}

				if( isset( $tableDefinition['inserts'] ) )
				{
					unset( $tableDefinition['inserts'] );
				}

				/* Now we have to add the correct colation for text columns to our compare definition to flag any columns that don't have the correct charset/collation */
				$tableDefinition['columns'] = array_map( function( $column ){
					if( in_array( mb_strtoupper( $column['type'] ), array( 'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'ENUM', 'SET' ) ) )
					{
						$column['collation'] = Db::i()->collation;
					}

					return $column;
				}, $tableDefinition['columns'] );

				/* And store our definition */
				$compareDefinition	= Db::i()->normalizeDefinition( $tableDefinition );
				$tableDefinition	= Db::i()->updateDefinitionIndexLengths( $tableDefinition );

				if( isset( $compareDefinition['comment'] ) AND !$compareDefinition['comment'] )
				{
					unset( $compareDefinition['comment'] );
				}

				/* Ensure that we use the proper engine, not whatever is in the schema.json as this will confuse index sub_part lengths */
				if ( isset( $originalDefinition['engine'] ) )
				{
					$tableDefinition['engine'] = $originalDefinition['engine'];
				}

				if ( $compareDefinition != $localDefinition )
				{
					$dropped = array();

					/* Loop the columns */
					/* @var $tableDefinition array */
					foreach ( $tableDefinition['columns'] as $columnName => $columnData )
					{
						/* If it doesn't exist in the local database, create it */
						if ( !isset( $localDefinition['columns'][ $columnName ] ) )
						{
							$tableChanges[] = "ADD COLUMN {$db->compileColumnDefinition( $columnData )}";
						}
						/* Or if it's wrong, change it */
						elseif ( $compareDefinition['columns'][ $columnName ] != $localDefinition['columns'][ $columnName ] )
						{
							/*  If the only difference is MEDIUMIT or INT should be BIGINT UNSIGNED - that's where we changed the member ID column. We don't need to flag it */
							$differences = array();
							foreach ( $columnData as $k => $v )
							{
								if ( isset( $localDefinition['columns'][ $columnName ][ $k ] ) AND $v != $localDefinition['columns'][ $columnName ][ $k ] )
								{
									$differences[ $k ] = array( 'is' => $localDefinition['columns'][ $columnName ][ $k ], 'shouldBe' => $v );
								}
							}
							if ( isset( $differences['type'] ) and ( $differences['type']['is'] == 'MEDIUMINT' or $differences['type']['is'] == 'INT' ) and $differences['type']['shouldBe'] == 'BIGINT' AND !$enableBigInt )
							{
								unset( $differences['type'] );
								if ( isset( $differences['length'] ) )
								{
									unset( $differences['length'] );
								}
								if ( isset( $differences['unsigned'] ) and !$differences['unsigned']['is'] and $differences['unsigned']['shouldBe'] )
								{
									unset( $differences['unsigned'] );
								}
							}

							/* Remove attempted changes back to empty string when INT */
							if( !empty( $differences['default'] ) AND $differences['default']['is'] == 0 AND $differences['default']['shouldBe'] == '' )
							{
								unset( $differences['default'] );
							}

							/* If this is a decimal column, ignore unsigned attribute */
							if( $compareDefinition['columns'][ $columnName ]['type'] == 'DECIMAL' AND isset( $differences['unsigned'] ) )
							{
								unset( $differences['unsigned'] );
							}

							/* If there were other differences, carry on... */
							if ( $differences )
							{
								/* We re-add indexes after changing columns */
								$indexesToAdd = array();

								/* First check indexes to see if any need to be adjusted */
								foreach( $localDefinition['indexes'] as $indexName => $indexData )
								{
									/* We skip the primary key as it can cause errors related to auto-increment */
									if( $indexName == 'PRIMARY' )
									{
										if ( isset( $tableDefinition['columns'][ $indexData['columns'][0] ] ) and isset( $tableDefinition['columns'][ $indexData['columns'][0] ]['auto_increment'] ) and $tableDefinition['columns'][ $indexData['columns'][0] ]['auto_increment'] === TRUE )
										{
											continue;
										}
									}

									foreach( $indexData['columns'] as $indexColumn )
									{
										/* If the column we are about to adjust is included in this index, see if it needs adjusting */
										if( $indexColumn == $columnName AND !in_array( $indexName, $dropped ) )
										{
											$thisIndex = $db->updateDefinitionIndexLengths( $tableDefinition );
											/* @var $thisIndex array */

											if( !isset( $thisIndex['indexes'][ $indexName ] ) )
											{
												$tableChanges[] = "DROP INDEX `{$db->escape_string( $indexName )}`";
												$dropped[]		= $indexName;
											}
											elseif( $thisIndex['indexes'][ $indexName ] !== $localDefinition['indexes'][ $indexName ] )
											{
												$tableChanges[] = "DROP INDEX `{$db->escape_string( $indexName )}`";
												$indexesToAdd[] = $db->buildIndex( $tableName, $thisIndex['indexes'][ $indexName ], $tableDefinition );
												$dropped[]		= $indexName;

												if( $tableDefinition['indexes'][ $indexName ]['type'] == 'unique' OR $tableDefinition['indexes'][ $indexName ]['type'] == 'primary' )
												{
													$needIgnore = TRUE;
												}
											}
										}
									}
								}

								/* If we are about to adjust the column to not allow NULL values then adjust those values first... */
								if( isset( $columnData['allow_null'] ) and $columnData['allow_null'] === FALSE )
								{
									$defaultValue = "''";

									/* Default value */
									if( isset( $columnData['default'] ) and !in_array( strtoupper( $columnData['type'] ), array( 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'BLOB', 'MEDIUMBLOB', 'BIGBLOB', 'LONGBLOB' ) ) )
									{
										if( $columnData['type'] == 'BIT' )
										{
											$defaultValue = "{$columnData['default']}";
										}
										else
										{
											$defaultValue = in_array( $columnData['type'], array( 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC' ) ) ? floatval( $columnData['default'] ) : ( ! in_array( $columnData['default'], array( 'CURRENT_TIMESTAMP', 'BIT' ) ) ? '\'' . $db->escape_string( $columnData['default'] ) . '\'' : $columnData['default'] );
										}
									}

									$changesToMake[] = array( 'table' => $tableName, 'query' => "UPDATE `{$db->prefix}{$db->escape_string( $tableName )}` SET `{$db->escape_string( $columnName )}`={$defaultValue} WHERE `{$db->escape_string( $columnName )}` IS NULL;" );
								}

								$tableChanges[] = "CHANGE COLUMN `{$db->escape_string( $columnName )}` {$db->compileColumnDefinition( $columnData )}";

								if( count( $indexesToAdd ) )
								{
									$tableChanges = array_merge( $tableChanges, $indexesToAdd );
								}
							}
						}
					}

					/* Loop the index */
					foreach ( $compareDefinition['indexes'] as $indexName => $indexData )
					{
						if( in_array( $indexName, $dropped ) )
						{
							continue;
						}

						if ( !isset( $localDefinition['indexes'][ $indexName ] ) )
						{
							/* InnoDB FullText indexes must be added one at a time */
							if( $tableDefinition['engine'] === 'InnoDB' AND $tableDefinition['indexes'][ $indexName ]['type'] === 'fulltext' )
							{
								$innoDbFullTextIndexes[] = $db->buildIndex( $tableName, $tableDefinition['indexes'][ $indexName ], $tableDefinition );
							}
							else
							{
								$tableChanges[] = $db->buildIndex( $tableName, $tableDefinition['indexes'][ $indexName ], $tableDefinition );
							}

							if( $tableDefinition['indexes'][ $indexName ]['type'] == 'unique' OR $tableDefinition['indexes'][ $indexName ]['type'] == 'primary' )
							{
								$needIgnore = TRUE;
							}
						}
						elseif ( $indexData != $localDefinition['indexes'][ $indexName ] )
						{
							$tableChanges[] = ( ( $indexName == 'PRIMARY KEY' ) ? "DROP " . $indexName . ", " : "DROP INDEX `" . $db->escape_string( $indexName ) . "`, " ) . $db->buildIndex( $tableName, $tableDefinition['indexes'][ $indexName ], $tableDefinition );

							if( $tableDefinition['indexes'][ $indexName ]['type'] == 'unique' OR $tableDefinition['indexes'][ $indexName ]['type'] == 'primary' )
							{
								$needIgnore = TRUE;
							}
						}
					}

					/* Remove unnecessary indexes, which can be an issue if, for example, there is a UNIQUE index that the schema doesn't think should be there */
					foreach ( $localDefinition['indexes'] as $indexName => $indexData )
					{
						if ( $indexName != 'PRIMARY' and !isset( $compareDefinition['indexes'][ $indexName ] ) )
						{
							/* If the index is on a column which we don't recognise (which may happen on tables which we add columns to like the ones that
								store custom fields, or very naughty third parties adding columns on tables they don't own), don't drop it */
							foreach ( $indexData['columns'] as $indexedColumn )
							{
								if ( !isset( $compareDefinition['columns'][ $indexedColumn ] ) )
								{
									continue 2;
								}
							}

							/* Still here? Go ahead */
							$dropIndexQuery = "DROP INDEX `{$db->escape_string( $indexName )}`";
							if ( !in_array( $dropIndexQuery, $tableChanges ) ) // We skip the primary key as it can cause errors related to auto-increment
							{
								$tableChanges[] = $dropIndexQuery;
							}
						}
					}
				}

				if( count( $tableChanges ) )
				{
					if( $needIgnore )
					{
						$changesToMake[] = array( 'table' => $tableName, 'query' => "CREATE TABLE `{$db->prefix}{$db->escape_string( $tableName )}_new` LIKE `{$db->prefix}{$db->escape_string( $tableName )}`;" );
						$changesToMake[] = array( 'table' => $tableName, 'query' => "ALTER TABLE `{$db->prefix}{$db->escape_string( $tableName )}_new` " . implode( ", ", $tableChanges ) . ";" );
						$changesToMake[] = array( 'table' => $tableName, 'query' => "INSERT IGNORE INTO `{$db->prefix}{$db->escape_string( $tableName )}_new` SELECT * FROM `{$db->prefix}{$db->escape_string( $tableName )}`;" );
						$changesToMake[] = array( 'table' => $tableName, 'query' => "DROP TABLE `{$db->prefix}{$db->escape_string( $tableName )}`;" );
						$changesToMake[] = array( 'table' => $tableName, 'query' => "RENAME TABLE `{$db->prefix}{$db->escape_string( $tableName )}_new` TO `{$db->prefix}{$db->escape_string( $tableName )}`;" );
					}
					else
					{
						$changesToMake[] = array( 'table' => $tableName, 'query' => "ALTER TABLE `{$db->prefix}{$db->escape_string( $tableName )}` " . implode( ", ", $tableChanges ) . ";" );
					}
				}

				/* InnoDB FullText indexes must be added one at a time */
				if( count( $innoDbFullTextIndexes ) )
				{
					foreach( $innoDbFullTextIndexes as $newIndex )
					{
						$changesToMake[] = array( 'table' => $tableName, 'query' => "ALTER TABLE `{$db->prefix}{$db->escape_string( $tableName )}` " . $newIndex . ";" );
					}
				}
			}
			/* If the table doesn't exist, create it */
			catch ( OutOfRangeException $e )
			{
				$changesToMake[] = array( 'table' => $tableName, 'query' => $db->_createTableQuery( $tableDefinition ) );
			}
		}

		/* And loop any install routine for columns added to other tables */
		if ( file_exists( $this->getApplicationPath() . "/setup/install/queries.json" ) )
		{
			foreach( json_decode( file_get_contents( $this->getApplicationPath() . "/setup/install/queries.json" ), TRUE ) as $query )
			{
				switch ( $query['method'] )
				{
					/* Add column */
					case 'addColumn':
						$localDefinition = Db::i()->getTableDefinition( $query['params'][0] );
						if ( !isset( $localDefinition['columns'][ $query['params'][1]['name'] ] ) )
						{
							$changesToMake[] = array( 'table' => $query['params'][0], 'query' => "ALTER TABLE `{$db->prefix}{$query['params'][0]}` ADD COLUMN {$db->compileColumnDefinition( $query['params'][1] )}" );
						}
						else
						{
							$correctDefinition = $db->compileColumnDefinition( $query['params'][1] );
							if ( $correctDefinition != $db->compileColumnDefinition( $localDefinition['columns'][ $query['params'][1]['name'] ] ) )
							{
								$changesToMake[] = array( 'table' => $query['params'][0], 'query' => "ALTER TABLE `{$db->prefix}{$query['params'][0]}` CHANGE COLUMN `{$query['params'][1]['name']}` {$correctDefinition}" );
							}
						}
						break;
				}
			}
		}

		/* Return */
		return $changesToMake;
	}

	/**
	 * Create a new version number and move current working version
	 * code into it
	 *
	 * @param int $long	The "long" version number (e.g. 100000)
	 * @param string $human	The "human" version number (e.g. "1.0.0")
	 * @return	void
	 */
	public function assignNewVersion( int $long, string $human ) : void
	{
		/* Add to versions.json */
		$json = json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->directory}/data/versions.json" ), TRUE );
		$json[ $long ] = $human;
		static::writeJson( ROOT_PATH . "/applications/{$this->directory}/data/versions.json", $json );

		/* Do stuff */
		$setupDir = ROOT_PATH . "/applications/{$this->directory}/setup";
		$workingDir = $setupDir . "/upg_working";
		if ( file_exists( $workingDir ) )
		{
			/* We need to make sure the array is 1-indexed otherwise the upgrader gets confused */
			$queriesJsonFile = $workingDir . "/queries.json";
			if ( file_exists( $queriesJsonFile ) )
			{
				$write = array();
				$i = 0;
				foreach ( json_decode( file_get_contents( $queriesJsonFile ), TRUE ) as $query )
				{
					$write[ ++$i ] = $query;
				}
				static::writeJson( $queriesJsonFile, $write );
			}

			/* Add the actual version number in upgrade.php & options.php */
			$versionReplacement = function( $file ) use ( $human, $long )
			{
				if ( file_exists( $file ) )
				{
					$contents = file_get_contents( $file );
					$contents = str_replace(
						array(
							'{version_human}',
							'upg_working',
							'{version_long}'
						),
						array(
							$human,
							"upg_{$long}",
							$long
						),
						$contents
					);
					file_put_contents( $file, $contents );
				}
			};

			/* Make the replacement */
			$versionReplacement( $workingDir . "/upgrade.php" );
			$versionReplacement( $workingDir . "/options.php" );

			/* Rename the directory */
			rename( $workingDir, $setupDir . "/upg_{$long}" );
		}

		/* Update core_dev */
		Db::i()->update( 'core_dev', array(
			'working_version'	=> $long,
		), array( 'app_key=? AND working_version=?', $this->directory, 'working' ) );
	}

	/**
	 * Build application for release
	 *
	 * @return	void
	 * @throws	RuntimeException
	 */
	public function build() : void
	{
		/* Set the building flag */
		Data\Store::i()->buildingApp = time();

		/* Use full upgrader? */
		$forceFullUpgrade = FALSE;

		/* Write the application data to the application.json file */
		$applicationData	= array(
			'application_title'	=> Member::loggedIn()->language()->get('__app_' . $this->directory ),
			'app_author'		=> $this->author,
			'app_directory'		=> $this->directory,
			'app_protected'		=> $this->protected,
			'app_website'		=> $this->website,
			'app_update_check'	=> $this->update_check,
			'app_hide_tab'		=> $this->hide_tab
		);

		Application::writeJson( ROOT_PATH . '/applications/' . $this->directory . '/data/application.json', $applicationData );

		/* Update app version data */
		$versions		= $this->getAllVersions();
		$longVersions	= array_keys( $versions );
		$humanVersions	= array_values( $versions );
		if( count($versions) )
		{
			$latestLVersion	= array_pop( $longVersions );
			$latestHVersion	= array_pop( $humanVersions );

			Db::i()->update( 'core_applications', array( 'app_version' => $latestHVersion, 'app_long_version' => $latestLVersion ), array( 'app_directory=?', $this->directory ) );

			$this->long_version = $latestLVersion;
			$this->version		= $latestHVersion;
		}
		$setupDir = ROOT_PATH . '/applications/' . $this->directory . '/setup/upg_' . $this->long_version;
		if ( !is_dir( $setupDir ) )
		{
			mkdir( $setupDir );
		}

		/* Take care of languages for this app */
		$languageChanges = $this->buildLanguages();
		$langChangesFile = $setupDir . '/lang.json';
		if ( count( array_filter( $languageChanges['normal'] ) ) or count( array_filter( $languageChanges['js'] ) ) )
		{
			if ( file_exists( $langChangesFile ) )
			{
				$previousLangChanges = json_decode( file_get_contents( $langChangesFile ), TRUE );
				$languageChanges['normal'] = $this->_combineChanges( $languageChanges['normal'], $previousLangChanges['normal'] );
				$languageChanges['js'] = $this->_combineChanges( $languageChanges['js'], $previousLangChanges['js'] );
			}

			if ( count( array_filter( $languageChanges['normal'] ) ) or count( array_filter( $languageChanges['js'] ) ) )
			{
				file_put_contents( $langChangesFile, json_encode( $languageChanges, JSON_PRETTY_PRINT ) );
			}
			elseif ( file_exists( $langChangesFile ) )
			{
				unlink( $langChangesFile );
			}
		}
		$this->installLanguages();

		/* Take care of skins for this app */
		$themeChanges = $this->buildThemeTemplates();
		$themeChangesFile = $setupDir . '/theme.json';
		if ( count( array_filter( $themeChanges['html'] ) ) or count( array_filter( $themeChanges['css'] ) ) or count( array_filter( $themeChanges['resources'] ) ) )
		{
			if ( file_exists( $themeChangesFile ) )
			{
				$previousThemeChanges = json_decode( file_get_contents( $themeChangesFile ), TRUE );
				$themeChanges['html'] = $this->_combineChanges( $themeChanges['html'], $previousThemeChanges['html'] );
				$themeChanges['css'] = $this->_combineChanges( $themeChanges['css'], $previousThemeChanges['css'] );
				$themeChanges['resources'] = $this->_combineChanges( $themeChanges['resources'], $previousThemeChanges['resources'] );
			}

			if ( count( array_filter( $themeChanges['html'] ) ) or count( array_filter( $themeChanges['css'] ) ) or count( array_filter( $themeChanges['resources'] ) ) )
			{
				file_put_contents( $themeChangesFile, json_encode( $themeChanges, JSON_PRETTY_PRINT ) );
			}
			elseif ( file_exists( $themeChangesFile ) )
			{
				unlink( $themeChangesFile );
			}
		}

		/* Custom Templates */
		$customTemplateChanges = $this->buildCustomTemplates();
		$customTemplatesChangesFile = $setupDir . '/customtemplates.json';
		if( count( $customTemplateChanges ) )
		{
			if( file_exists( $customTemplatesChangesFile ) )
			{
				$previousChanges = json_decode( file_get_contents( $customTemplatesChangesFile ), true );
				$customTemplateChanges  = $this->_combineChanges( $customTemplateChanges, $previousChanges );
			}

			if( count( array_filter( $customTemplateChanges ) ) )
			{
				file_put_contents( $customTemplatesChangesFile, json_encode( $customTemplateChanges, JSON_PRETTY_PRINT ) );
			}
			elseif( file_exists( $customTemplatesChangesFile ) )
			{
				unlink( $customTemplatesChangesFile );
			}
		}

		/* Theme Editor Settings */
		$editorChanges = $this->buildThemeEditorSettings();
		$editorChangesFile = $setupDir . '/themeeditor.json';
		if( count( array_filter( $editorChanges['categories'] ) ) or count( array_filter( $editorChanges['settings'] ) ) )
		{
			if ( file_exists( $editorChangesFile ) )
			{
				$previousEditorChanges = json_decode( file_get_contents( $editorChangesFile ), TRUE );
				$editorChanges['categories'] = $this->_combineChanges( $editorChanges['categories'] ?? [], $previousEditorChanges['categories'] ?? [] );
				$editorChanges['settings'] = $this->_combineChanges( $editorChanges['settings'] ?? [], $previousEditorChanges['settings'] ?? [] );
			}

			if( count( array_filter( $editorChanges['categories'] ) ) or count( array_filter( $editorChanges['settings'] ) ) )
			{
				file_put_contents( $editorChangesFile, json_encode( $editorChanges, JSON_PRETTY_PRINT ) );
			}
			elseif ( file_exists( $editorChangesFile ) )
			{
				unlink( $editorChangesFile );
			}
		}

		/* Take care of emails for this app */
		$emailTemplateChanges = $this->buildEmailTemplates();
		$emailTemplateChangesFile = $setupDir . '/emailTemplates.json';
		if ( count( array_filter( $emailTemplateChanges ) ) )
		{
			if ( file_exists( $emailTemplateChangesFile ) )
			{
				$emailTemplateChanges = $this->_combineChanges( $emailTemplateChanges, json_decode( file_get_contents( $emailTemplateChangesFile ), TRUE ) );
			}

			if ( count( array_filter( $emailTemplateChanges ) ) )
			{
				file_put_contents( $emailTemplateChangesFile, json_encode( $emailTemplateChanges, JSON_PRETTY_PRINT ) );
			}
			elseif ( file_exists( $emailTemplateChangesFile ) )
			{
				unlink( $emailTemplateChangesFile );
			}
		}
		$this->installEmailTemplates();

		/* Editor Plugins */
		$editorPluginChanges = $this->buildEditorPlugins();
		$editorPluginChangesFile = $setupDir . '/editor.json';
		if ( count( array_filter( $editorPluginChanges ) ) )
		{
			if ( file_exists( $editorPluginChangesFile ) )
			{
				$editorPluginChanges = $this->_combineChanges( $editorPluginChanges, json_decode( file_get_contents( $editorPluginChangesFile ), TRUE ) );
			}

			if ( count( array_filter( $editorPluginChanges ) ) )
			{
				file_put_contents( $editorPluginChangesFile, json_encode( $editorPluginChanges, JSON_PRETTY_PRINT ) );
			}
			elseif ( file_exists( $editorPluginChangesFile ) )
			{
				unlink( $editorPluginChangesFile );
			}
		}

		/* Take care of javascript for this app */
		$jsChanges = $this->buildJavascript();
		$jsChangesFile = $setupDir . '/javascript.json';
		if ( count( array_filter( $jsChanges['files'] ) ) or count( array_filter( $jsChanges['orders'] ) ) )
		{
			if ( file_exists( $jsChangesFile ) )
			{
				$previousJsChanges = json_decode( file_get_contents( $jsChangesFile ), TRUE );
				$jsChanges['files'] = $this->_combineChanges( $jsChanges['files'], $previousJsChanges['files'] );
				$jsChanges['orders'] = $this->_combineChanges( $jsChanges['orders'], $previousJsChanges['orders'] );
			}

			if ( count( array_filter( $jsChanges['files'] ) ) or count( array_filter( $jsChanges['orders'] ) ) )
			{
				file_put_contents( $jsChangesFile, json_encode( $jsChanges, JSON_PRETTY_PRINT ) );
			}
			elseif ( file_exists( $jsChangesFile ) )
			{
				unlink( $jsChangesFile );
			}

			/* Force full upgrade if global JS has changed */
			foreach( new RecursiveIteratorIterator( new RecursiveArrayIterator( $jsChanges ) ) as $k => $v )
			{
				if( mb_substr( $v, 0, 6 ) === 'global' )
				{
					$forceFullUpgrade = true;
					break;
				}
			}
		}
		$this->installJavascript();

		/* If this is a first party app, compile immediately */
		if( in_array( $this->directory, IPS::$ipsApps ) )
		{
			Javascript::compile( $this->directory );

			if ( $this->directory == 'core' )
			{
				/* We also need to compile global */
				Javascript::compile( 'global' );
			}

			Theme::compileStatic( $this->directory );
		}

		/* And custom build routines */
		foreach($this->extensions('core', 'Build') as $builder )
		{
			$builder->build();
		}

		/* Write a build.xml file with the current json data so we know what has changed next time we build */
		$jsonChanges = $this->buildJsonData();
		foreach ( array( 'modules', 'tasks', 'settings', 'widgets', 'acpSearchKeywords', 'themeeditor' ) as $k )
		{
			if ( isset( $jsonChanges[ $k ] ) )
			{
				$changesFile = "{$setupDir}/{$k}.json";

				/* Do we have changes? Rework this to handle the nested editor categories/settings */
				if( $k == 'themeeditor' )
				{
					if ( file_exists( $changesFile ) )
					{
						$jsonChanges[ $k ]['categories'] = $this->_combineChanges( $jsonChanges[ $k ]['categories'], json_decode( file_get_contents( $changesFile ), TRUE )['categories'] );
						$jsonChanges[ $k ]['settings'] = $this->_combineChanges( $jsonChanges[ $k ]['settings'], json_decode( file_get_contents( $changesFile ), TRUE )['settings'] );
					}

					$newChanges = ( $jsonChanges[ $k ]['categories']['added'] or $jsonChanges[ $k ]['categories']['edited'] or $jsonChanges[ $k ]['categories']['removed'] or $jsonChanges[ $k ]['settings']['added'] or $jsonChanges[ $k ]['settings']['edited'] or $jsonChanges[ $k ]['settings']['removed'] );
				}
				else
				{
					if ( file_exists( $changesFile ) )
					{
						$jsonChanges[ $k ] = $this->_combineChanges( $jsonChanges[ $k ], json_decode( file_get_contents( $changesFile ), TRUE ) );
					}

					$newChanges = ( $jsonChanges[ $k ]['added'] or $jsonChanges[ $k ]['edited'] or $jsonChanges[ $k ]['removed'] );
				}

				if( $newChanges )
				{
					file_put_contents( $changesFile, json_encode( $jsonChanges[ $k ], JSON_PRETTY_PRINT ) );
				}
				elseif ( file_exists( $changesFile ) )
				{
					unlink( $changesFile );
				}
			}
		}

		/* Included CMS Templates */
		if( file_exists( ROOT_PATH . '/applications/' . $this->directory . '/dev/cmsTemplates.json' ) )
		{
			$pagesTemplates = json_decode( file_get_contents( ROOT_PATH . '/applications/' . $this->directory . '/dev/cmsTemplates.json' ), TRUE );
			$xml = Templates::exportAsXml( $pagesTemplates );

			if( $xml )
			{
				if ( is_writable( ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
				{
					file_put_contents( ROOT_PATH . '/applications/' . $this->directory . '/data/cmsTemplates.xml', $xml->outputMemory() );
				}
				else
				{
					throw new RuntimeException( Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
				}
			}
		}

		/* Write the version data file */
		file_put_contents( $setupDir . '/data.json', json_encode( array(
			'id'					=> $this->long_version,
			'name'					=> $this->version,
			'steps'					=> array(
				'queries'				=> file_exists( $setupDir . "/queries.json" ),
				'lang'					=> file_exists( $langChangesFile ),
				'theme'					=> file_exists( $themeChangesFile ),
				'themeeditor'			=> file_exists( $setupDir . "/themeeditor.json" ),
				'javascript'			=> file_exists( $jsChangesFile ),
				'emailTemplates'		=> file_exists( $emailTemplateChangesFile ),
				'acpSearchKeywords'		=> file_exists( $setupDir . "/acpSearchKeywords.json" ),
				'settings'				=> file_exists( $setupDir . "/settings.json" ),
				'tasks'					=> file_exists( $setupDir . "/tasks.json" ),
				'modules'				=> file_exists( $setupDir . "/modules.json" ),
				'widgets'				=> file_exists( $setupDir . "/widgets.json" ),
				'customOptions'			=> file_exists( $setupDir . "/options.php" ),
				'customRoutines'		=> file_exists( $setupDir . "/upgrade.php" ),
			),
			'forceMainUpgrader'			=> $forceFullUpgrade,
			'forceManualDownloadNoCiC'	=> FALSE,
			'forceManualDownloadCiC'		=> FALSE,
		), JSON_PRETTY_PRINT ) );

		unset( Data\Store::i()->buildingApp );
	}

	/**
	 * Are we currently building?
	 *
	 * @return bool
	 */
	public static function areWeBuilding(): bool
	{
		if ( isset( Data\Store::i()->buildingApp ) )
		{
			return Data\Store::i()->buildingApp > ( time() - ( 5 * 60 ) );
		}

		return false;
	}

	/**
	 * Combine information about changes when rebuilding a version after it was already built once before
	 *
	 * @param array $newChanges			The changes detected in this build
	 * @param array $previousChanges		The changes detected in the previous build
	 * @param bool $keysOnly			Set to TRUE if the changes is just a list of keys, or FALSE if they're key/values
	 * @return	array
	 */
	protected function _combineChanges( array $newChanges, array $previousChanges, bool $keysOnly=TRUE ): array
	{
		if ( $keysOnly )
		{
			foreach ( $newChanges['added'] as $v )
			{
				if ( in_array( $v, $previousChanges['removed'] ) )
				{
					unset( $previousChanges['removed'][ array_search( $v, $previousChanges['removed'] ) ] );
				}
				else
				{
					$previousChanges['added'][] = $v;
				}
			}
			foreach ( $newChanges['edited'] as $v )
			{
				if ( !in_array( $v, $previousChanges['added'] ) )
				{
					$previousChanges['edited'][] = $v;
				}
			}
			foreach ( $newChanges['removed'] as $v )
			{
				if ( in_array( $v, $previousChanges['added'] ) )
				{
					unset( $previousChanges['added'][ array_search( $v, $previousChanges['added'] ) ] );
				}
				elseif ( in_array( $v, $previousChanges['edited'] ) )
				{
					unset( $previousChanges['edited'][ array_search( $v, $previousChanges['edited'] ) ] );
					$previousChanges['removed'][] = $v;
				}
				elseif ( !in_array( $v, $previousChanges['removed'] ) )
				{
					$previousChanges['removed'][] = $v;
				}
			}
		}
		else
		{
			foreach ( $newChanges['added'] as $k => $v )
			{
				if ( isset( $previousChanges['removed'][ $k ] ) )
				{
					unset( $previousChanges['removed'][ $k ] );
				}
				else
				{
					$previousChanges['added'][ $k ] = $v;
				}
			}
			foreach ( $newChanges['edited'] as $k => $v )
			{
				if ( !isset( $previousChanges['added'][ $k ] ) )
				{
					$previousChanges['edited'][ $k ] = $v;
				}
			}
			foreach ( $newChanges['removed'] as $k => $v )
			{
				if ( isset( $previousChanges['added'][ $k ] ) )
				{
					unset( $previousChanges['added'][ $k ] );
				}
				elseif ( isset( $previousChanges['edited'][ $k ] ) )
				{
					unset( $previousChanges['edited'][ $k ] );
					$previousChanges['removed'][ $k ] = $v;
				}
				elseif ( !isset( $previousChanges['removed'][ $k ] ) )
				{
					$previousChanges['removed'][ $k ] = $v;
				}
			}
		}

		/* Make sure we have all the sections defined */
		foreach( array( 'added', 'edited', 'removed' ) as $type )
		{
			if( !isset( $previousChanges[ $type ] ) )
			{
				$previousChanges[ $type ] = [];
			}
		}

		return $previousChanges;
	}

	/**
	 * Build skin templates for an app
	 *
	 * @return	void|array
	 * @throws	RuntimeException
	 */
	public function buildThemeTemplates()
	{
		/* Delete compiled items */
		Theme::deleteCompiledTemplate( $this->directory );
		Theme::deleteCompiledCss( $this->directory );
		Theme::removeResources( $this->directory );

		Theme::i()->importDevHtml( $this->directory, 0 );
		Theme::i()->importDevCss( $this->directory, 0 );

		/* Get current XML file for calculating differences */
		$return = array( 'html' => array( 'added' => array(), 'edited' => array(), 'removed' => array() ), 'css' => array( 'added' => array(), 'edited' => array(), 'removed' => array() ), 'resources' => array( 'added' => array(), 'edited' => array(), 'removed' => array() ) );
		$current = array( 'html' => array(), 'css' => array(), 'resources' => array() );
		$currentFile = ROOT_PATH . "/applications/{$this->directory}/data/theme.xml";
		if ( file_exists( $currentFile ) )
		{
			$xml = SimpleXML::loadFile( $currentFile );
			foreach ( $xml->template as $html )
			{
				$attributes = iterator_to_array( $html->attributes() );

				$current['html'][ "{$attributes['template_location']}/{$attributes['template_group']}/{$attributes['template_name']}" ] = array(
					'params'	=> $attributes['template_data'],
					'content'	=> (string) $html
				);
			}
			foreach ( $xml->css as $css )
			{
				$attributes = iterator_to_array( $css->attributes() );

				$current['css'][ "{$attributes['css_location']}/" . ( $attributes['css_path'] == '.' ? '' : "{$attributes['css_path']}/" ) . $attributes['css_name'] ] = array(
					'params'	=> $attributes['css_attributes'],
					'content'	=> (string) $css
				);
			}
			foreach ( $xml->resource as $resource )
			{
				$attributes = iterator_to_array( $resource->attributes() );
				$current['resources'][ "{$attributes['location']}{$attributes['path']}{$attributes['name']}" ] = (string) $resource;
			}
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
		foreach (Db::i()->select( '*', 'core_theme_templates', array( 'template_set_id=? AND template_app=?', 0, $this->directory ), 'template_group, template_name, template_location' ) as $template )
		{
			/* Initiate the <template> tag */
			$xml->startElement('template');
			$attributes = array();
			foreach( $template as $k => $v )
			{
				if ( in_array( substr( $k, 9 ), array('app', 'location', 'group', 'name', 'data' ) ) )
				{
					$attributes[ $k ] = $v;
					$xml->startAttribute( $k );
					$xml->text( $v );
					$xml->endAttribute();
				}
			}

			/* Write value */
			if ( preg_match( '/[<>&]/', $template['template_content'] ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $template['template_content'] ) );
			}
			else
			{
				$xml->text( $template['template_content'] );
			}

			/* Close the <template> tag */
			$xml->endElement();

			/* Note it */
			$k = "{$attributes['template_location']}/{$attributes['template_group']}/{$attributes['template_name']}";
			if ( !isset( $current['html'][ $k ] ) )
			{
				$return['html']['added'][] = $k;
			}
			elseif ( $current['html'][ $k ]['params'] != $attributes['template_data'] or $current['html'][ $k ]['content'] != $template['template_content'] )
			{
				$return['html']['edited'][] = $k;
			}
			unset( $current['html'][ $k ] );
		}

		/* Css */
		foreach (Db::i()->select( '*', 'core_theme_css', array( 'css_set_id=? AND css_app=?', 0 , $this->directory ), 'css_path, css_name, css_location' ) as $css )
		{
			$xml->startElement('css');
			$attributes = array();
			foreach( $css as $k => $v )
			{
				if ( in_array( substr( $k, 4 ), array('app', 'location', 'path', 'name', 'attributes' ) ) )
				{
					$attributes[ $k ] = $v;
					$xml->startAttribute( $k );
					$xml->text( $v );
					$xml->endAttribute();
				}
			}

			/* Write value */
			if ( preg_match( '/[<>&]/', $css['css_content'] ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $css['css_content'] ) );
			}
			else
			{
				$xml->text( $css['css_content'] );
			}
			$xml->endElement();

			/* Note it */
			$k = "{$attributes['css_location']}/" . ( $attributes['css_path'] === '.' ? '' : "{$attributes['css_path']}/" ) . $attributes['css_name'];
			if ( !isset( $current['css'][ $k ] ) )
			{
				$return['css']['added'][] = $k;
			}
			elseif ( $current['css'][ $k ]['params'] != $attributes['css_attributes'] or $current['css'][ $k ]['content'] != $css['css_content'] )
			{
				$return['css']['edited'][] = $k;
			}
			unset( $current['css'][ $k ] );
		}

		/* Resources */
		$_resources	= $this->_buildThemeResources();

		foreach ( $_resources as $data )
		{
			$xml->startElement('resource');

			$xml->startAttribute('name');
			$xml->text( $data['resource_name'] );
			$xml->endAttribute();

			$xml->startAttribute('app');
			$xml->text( $data['resource_app'] );
			$xml->endAttribute();

			$xml->startAttribute('location');
			$xml->text( $data['resource_location'] );
			$xml->endAttribute();

			$xml->startAttribute('path');
			$xml->text( $data['resource_path'] );
			$xml->endAttribute();

			/* Write value */
			$encoded = base64_encode( $data['resource_data'] );
			$xml->text( $encoded );

			$xml->endElement();

			/* Note it */
			$k = "{$data['resource_location']}{$data['resource_path']}{$data['resource_name']}";
			if ( !isset( $current['resources'][ $k ] ) )
			{
				$return['resources']['added'][] = $k;
			}
			elseif ( $current['resources'][ $k ] != $encoded )
			{
				$return['resources']['edited'][] = $k;
			}
			unset( $current['resources'][ $k ] );
		}

		/* Finish */
		$xml->endDocument();

		/* Write it */
		if ( is_writable( ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			file_put_contents( ROOT_PATH . '/applications/' . $this->directory . '/data/theme.xml', $xml->outputMemory() );
		}
		else
		{
			throw new RuntimeException( Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}

		/* Return */
		$return['html']['removed'] = array_keys( $current['html'] );
		$return['css']['removed'] = array_keys( $current['css'] );
		$return['resources']['removed'] = array_keys( $current['resources'] );
		return $return;
	}

	/**
	 * Build custom templates into a JSON file
	 *
	 * @return array|array[]
	 */
	public function buildCustomTemplates() : array
	{
		$return = array(
			'added' => array(), 'edited' => array(), 'removed' => array()
		);

		/* Read in the current file */
		$current = [];
		$file = ROOT_PATH . "/applications/" . $this->directory . "/data/customtemplates.json";
		if( file_exists( $file ) )
		{
			$current = json_decode( file_get_contents( $file ), true );
		}

		/* Custom templates */
		$json = [];
		foreach( Db::i()->select( '*', 'core_theme_templates_custom', array( 'template_app=?', $this->directory ) ) as $template )
		{
			$templateData = [
				'hookpoint' => $template['template_hookpoint'],
				'type' => $template['template_hookpoint_type'],
				'key' => $template['template_key'],
				'version' => $template['template_version'],
				'content' => $template['template_content']
			];
			$json[ $template['template_name'] ] = $templateData;

			if( !array_key_exists( $template['template_name'], $current ) )
			{
				$return['added'][] = $template['template_name'];
			}
			else
			{
				$currentTemplate = $current[ $template['template_name'] ];
				if( json_encode( $currentTemplate ) == json_encode( $templateData ) )
				{
					$return['edited'][] = $template['template_name'];
				}
				unset( $current[ $template['template_name']] );
			}
		}

		/* Whatever is left was deleted */
		if( count( $current ) )
		{
			$return['removed'] = array_keys( $current );
		}

		static::writeJson( $file, $json );

		return $return;
	}

	/**
	 * Build theme editor settings for an application
	 *
	 * @return array|array[]
	 */
	public function buildThemeEditorSettings() : array
	{
		$return = array(
			'categories' => array( 'added' => array(), 'edited' => array(), 'removed' => array() ),
			'settings' => array( 'added' => array(), 'edited' => array(), 'removed' => array() )
		);

		$current = array( 'categories' => array(), 'settings' => array() );

		/* Read in the current file */
		$file = ROOT_PATH . "/applications/" . $this->directory . "/data/themeeditor.json";
		if( file_exists( $file ) )
		{
			$current = json_decode( file_get_contents( $file ), true );
		}

		$lang = Lang::load( Lang::defaultLanguage() );
		$json = [];
		$mapping = [];

		foreach( Db::i()->select( '*', 'core_theme_editor_categories', [ 'cat_app=? and cat_set_id=?', $this->directory, 0 ], 'cat_parent,cat_position' ) as $row )
		{
			$mapping[ $row['cat_id'] ] = $row['cat_key'];
			$categoryData = [
				'cat_name' => $lang->get( 'themeeditor_cat_' . $row['cat_key'] ),
				'cat_key' => $row['cat_key'],
				'cat_icon' => Category::constructFromData( $row )->icon()
			];

			if( $row['cat_parent'] and isset( $mapping[ $row['cat_parent'] ] ) )
			{
				$categoryData['cat_parent'] = $mapping[ $row['cat_parent'] ];
			}

			if( !isset( $current['categories'] ) or !array_key_exists( $row['cat_key'], $current['categories'] ) )
			{
				$return['categories']['added'][] = $row['cat_key'];
			}
			else
			{
				$currentData = $current['categories'][ $row['cat_key'] ];
				if( json_encode( $currentData ) == json_encode( $categoryData ) )
				{
					$return['categories']['edited'][] = $row['cat_key'];
				}

				unset( $current['categories'][ $row['cat_key'] ] );
			}

			$json['categories'][ $row['cat_key'] ] = $categoryData;
		}

		/* Whatever is left was deleted */
		if( isset( $current['categories'] ) and count( $current['categories'] ) )
		{
			$return['categories']['removed'] = array_keys( $current['categories'] );
		}

		foreach(
			Db::i()->select( '*', 'core_theme_editor_settings', [ 'setting_app=? and setting_set_id=?', $this->directory, 0 ], 'setting_position' )
				->join( 'core_theme_editor_categories', 'setting_category_id=core_theme_editor_categories.cat_id' ) as $row )
		{
			$settingData = [
				'key' => $row['setting_key'],
				'type' => $row['setting_type'],
				'name' => $lang->get( 'themeeditor_var_' . $row['setting_key'] ),
				'desc' => ( $lang->checkKeyExists( 'themeeditor_var_' . $row['setting_key'] . '_desc' ) ? $lang->get( 'themeeditor_var_' . $row['setting_key'] . '_desc' ) : '' ),
				'refresh' => (bool) $row['setting_refresh']
			];

			$_data = $row['setting_data'] ? json_decode( $row['setting_data'], true ) : [];
			switch( $row['setting_type'] )
			{
				case Setting::SETTING_SELECT:
					$settingData['options'] = $_data['options'];
					break;

				case Setting::SETTING_NUMBER:
					foreach( $_data as $k => $v )
					{
						$settingData[$k] = $v;
					}
					break;

				case Setting::SETTING_COLOR:
					$default = json_decode( $row['setting_default'], true );
					$settingData['light_default'] = $default['light'];
					$settingData['dark_default'] = $default['dark'];
					break;
			}

			if( $row['setting_type'] != Setting::SETTING_COLOR )
			{
				$settingData['default'] = $row['setting_default'];
			}

			$settingData['cat'] = $row['cat_key'];

			if( !isset( $current['settings'] ) or !array_key_exists( $row['setting_key'], $current['settings'] ) )
			{
				$return['settings']['added'][] = $row['setting_key'];
			}
			else
			{
				$currentData = $current['settings'][ $row['setting_key'] ];
				if( json_encode( $currentData ) == json_encode( $settingData ) )
				{
					$return['settings']['edited'][] = $row['setting_key'];
				}

				unset( $current['settings'][ $row['setting_key'] ] );
			}

			$json['settings'][ $row['setting_key'] ] = $settingData;
		}

		if( isset( $current['settings'] ) and count( $current['settings'] ) )
		{
			$return['settings']['removed'] = array_keys( $current['settings'] );
		}

		static::writeJson( $file, $json );

		return $return;
	}

	/**
	 * Build Resources ready for non IN_DEV use
	 *
	 * @return	array
	 */
	protected function _buildThemeResources(): array
	{
		$resources = array();
		$path	= ROOT_PATH . "/applications/" . $this->directory . "/dev/resources/";

		Theme::i()->importDevResources( $this->directory, 0 );

		if ( is_dir( $path ) )
		{
			foreach( new DirectoryIterator( $path ) as $location )
			{
				if ( $location->isDot() || substr( $location->getFilename(), 0, 1 ) === '.' )
				{
					continue;
				}

				if ( $location->isDir() )
				{
					$resources	= $this->_buildResourcesRecursive( $location->getFilename(), '/', $resources );
				}
			}
		}

		return $resources;
	}

	/**
	 * Build Resources ready for non IN_DEV use (Iterable)
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param string $location	Location Folder Name
	 * @param string $path		Path
	 * @param array $resources	Array of resources to append to
	 * @return	array
	 */
	protected function _buildResourcesRecursive( string $location, string $path='/', array $resources=array() ): array
	{
		$root = ROOT_PATH . "/applications/{$this->directory}/dev/resources/{$location}";

		foreach( new DirectoryIterator( $root . $path ) as $file )
		{
			if ( $file->isDot() || substr( $file->getFilename(), 0, 1 ) === '.' || $file == 'index.html' )
			{
				continue;
			}

			if ( $file->isDir() )
			{
				$resources	= $this->_buildResourcesRecursive( $location, $path . $file->getFilename() . '/', $resources );
			}
			else
			{
				$resources[] = array(
					'resource_app'		=> $this->directory,
					'resource_location'	=> $location,
					'resource_path'		=> $path,
					'resource_name'		=> $file->getFilename(),
					'resource_data'		=> file_get_contents( $root . $path . $file->getFilename() ),
					'resource_added'	=> time()
				);
			}
		}

		return $resources;
	}

	/**
	 * Build languages for an app
	 *
	 * @return	array
	 * @throws	RuntimeException
	 */
	public function buildLanguages(): array
	{
		$return = array( 'normal' => array( 'added' => array(), 'edited' => array(), 'removed' => array() ), 'js' => array( 'added' => array(), 'edited' => array(), 'removed' => array() ) );

		/* Start with current XML file */
		$currentFile = ROOT_PATH . "/applications/{$this->directory}/data/lang.xml";

		$current = array( '0' => array(), '1' => array() );

		if ( file_exists( $currentFile ) )
		{
			foreach ( SimpleXML::loadFile( $currentFile )->app->word as $word )
			{
				$attributes = iterator_to_array( $word->attributes() );
				$current[ (string) $attributes['js'] ][ (string) $attributes['key'] ] = (string) $word;
			}
		}

		/* Create the lang.xml file */
		$xml = new XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );

		/* Root tag */
		$xml->startElement('language');

		/* Initiate the <app> tag */
		$xml->startElement('app');

		/* Set key */
		$xml->startAttribute('key');
		$xml->text( $this->directory );
		$xml->endAttribute();

		/* Set version */
		$xml->startAttribute('version');
		$xml->text( $this->long_version );
		$xml->endAttribute();

		/* Import the language files */
		$lang = Lang::readLangFiles( $this->directory );
		foreach ( $lang as $k => $v )
		{
			/* Start */
			$xml->startElement( 'word' );

			/* Add key */
			$xml->startAttribute('key');
			$xml->text( $k );
			$xml->endAttribute();

			/* Add javascript flag */
			$xml->startAttribute('js');
			$xml->text( 0 );
			$xml->endAttribute();

			/* Write value */
			if ( preg_match( '/<|>|&/', $v ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $v ) );
			}
			else
			{
				$xml->text( $v );
			}

			/* End */
			$xml->endElement();

			/* Enforce \n line endings */
			if( mb_strtolower( mb_substr( PHP_OS, 0, 3 ) ) === 'win' )
			{
				$v = str_replace( "\r\n", "\n", $v );
			}

			/* Note it */
			if ( !isset( $current['0'][ $k ] ) )
			{
				$return['normal']['added'][] = $k;
			}
			elseif ( $current['0'][ $k ] != $v )
			{
				$return['normal']['edited'][] = $k;
			}

			if ( isset( $current['0'][ $k ] ) )
			{
				unset( $current['0'][ $k ] );
			}
		}

		$lang = Lang::readLangFiles( $this->directory, true );
		foreach ( $lang as $k => $v )
		{
			/* Start */
			$xml->startElement( 'word' );

			/* Add key */
			$xml->startAttribute('key');
			$xml->text( $k );
			$xml->endAttribute();

			/* Add javascript flag */
			$xml->startAttribute('js');
			$xml->text( 1 );
			$xml->endAttribute();

			/* Write value */
			if ( preg_match( '/[<>&]/', $v ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $v ) );
			}
			else
			{
				$xml->text( $v );
			}

			/* End */
			$xml->endElement();

			/* Enforce \n line endings */
			if( mb_strtolower( mb_substr( PHP_OS, 0, 3 ) ) === 'win' )
			{
				$v = str_replace( "\r\n", "\n", $v );
			}

			/* Note it */
			if ( !isset( $current['1'][ $k ] ) )
			{
				$return['js']['added'][] = $k;
			}
			elseif ( $current['1'][ $k ] != $v )
			{
				$return['js']['edited'][] = $k;
			}
			if ( isset( $current['1'][ $k ] ) )
			{
				unset( $current['1'][ $k ] );
			}
		}

		/* Finish */
		$xml->endDocument();

		/* Write it */
		if ( is_writable( ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			file_put_contents( ROOT_PATH . '/applications/' . $this->directory . '/data/lang.xml', $xml->outputMemory() );
		}
		else
		{
			throw new RuntimeException( Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}

		/* Return */
		$return['normal']['removed'] = array_keys( $current['0'] );
		$return['js']['removed'] = array_keys( $current['1'] );
		return $return;
	}

	/**
	 * Build email templates for an app
	 *
	 * @return	array
	 * @throws	RuntimeException
	 */
	public function buildEmailTemplates(): array
	{
		/* Get current XML file for calculating differences */
		$return = array( 'added' => array(), 'edited' => array(), 'removed' => array() );
		$current = array();
		$currentFile = ROOT_PATH . "/applications/{$this->directory}/data/emails.xml";
		if ( file_exists( $currentFile ) )
		{
			$xml = SimpleXML::loadFile( $currentFile );
			foreach ( $xml->template as $template )
			{
				$attributes = iterator_to_array( $template->attributes() );

				$current[ (string) $template->template_name ] = array(
					'params'		=> (string) $template->template_data,
					'html'		=> (string) $template->template_content_html,
					'plaintext'	=> (string) $template->template_content_plaintext,
					'pinned'		=> (string) $template->template_pinned,
				);
			}
		}

		/* Where are we looking? */
		$path = ROOT_PATH . "/applications/{$this->directory}/dev/email";

		/* We create an array and store the templates temporarily so we can merge plaintext and HTML together */
		$templates		= array();
		$templateKeys	= array();

		/* Loop over files in the directory */
		if ( is_dir( $path ) )
		{
			foreach( new DirectoryIterator( $path ) as $location )
			{
				if ( $location->isDir() and mb_substr( $location, 0, 1 ) !== '.' and ( $location->getFilename() === 'plain' or $location->getFilename() === 'html' ) )
				{
					foreach( new DirectoryIterator( $path . '/' . $location->getFilename() ) as $sublocation )
					{
						if ( $sublocation->isDir() and mb_substr( $sublocation, 0, 1 ) !== '.' )
						{
							foreach( new DirectoryIterator( $path . '/' . $location->getFilename() . '/' . $sublocation->getFilename() ) as $file )
							{
								if ( $file->isDot() or !$file->isFile() or mb_substr( $file, 0, 1 ) === '.' or $file->getFilename() === 'index.html' )
								{
									continue;
								}

								$data = $this->_buildEmailTemplateFromInDev( $path . '/' . $location->getFilename() . '/' . $sublocation->getFilename(), $file, $sublocation->getFilename() . '__' );
								$extension = mb_substr( $file->getFilename(), mb_strrpos( $file->getFilename(), '.' ) + 1 );
								$type = ( $extension === 'txt' ) ? "plaintext" : "html";

								if ( ! isset( $templates[ $data['template_name'] ] ) )
								{
									$templates[ $data['template_name'] ] = array();
								}

								$templates[ $data['template_name'] ] = array_merge( $templates[ $data['template_name'] ], $data );

								/* Delete the template in the store */
								$key = $templates[ $data['template_name'] ]['template_key'] . '_email_' . $type;
								unset( Store::i()->$key );

								/* Remember our templates */
								$templateKeys[]	= $data['template_key'];
							}
						}
					}

				}
				else
				{
					if ( $location->isDot() or !$location->isFile() or mb_substr( $location, 0, 1 ) === '.' or $location->getFilename() === 'index.html' )
					{
						continue;
					}

					$data = $this->_buildEmailTemplateFromInDev( $path, $location );
					$extension = mb_substr( $location->getFilename(), mb_strrpos( $location->getFilename(), '.' ) + 1 );
					$type = ( $extension === 'txt' ) ? "plaintext" : "html";

					if ( ! isset( $templates[ $data['template_name'] ] ) )
					{
						$templates[ $data['template_name'] ]	= array();
					}

					$templates[ $data['template_name'] ] = array_merge( $templates[ $data['template_name'] ], $data );

					/* Delete the template in the store */
					$key = $templates[ $data['template_name'] ]['template_key'] . '_email_' . $type;
					unset( Store::i()->$key );

					/* Remember our templates */
					$templateKeys[]	= $data['template_key'];
				}
			}
		}

		/* Clear out invalid templates */
		Db::i()->delete( 'core_email_templates', array( "template_app=? AND template_key NOT IN('" . implode( "','", $templateKeys ) . "')", $this->directory ) );

		/* If we have any templates, put them in the database */
		if( count($templates) )
		{
			foreach( $templates as $template )
			{
				Db::i()->insert( 'core_email_templates', $template, TRUE );
			}

			/* Build the executable copies */
			$this->parseEmailTemplates();
		}

		$xml = SimpleXML::create('emails');

		/* Templates */
		foreach (Db::i()->select( '*', 'core_email_templates', array( 'template_parent=? AND template_app=?', 0, $this->directory ), 'template_key ASC' ) as $template )
		{
			$forXml = array();
			foreach( $template as $k => $v )
			{
				if ( in_array( substr( $k, 9 ), array('app', 'name', 'content_html', 'data', 'content_plaintext', 'pinned' ) ) )
				{
					$forXml[ $k ] = $v;
				}
			}

			$xml->addChild( 'template', $forXml );

			/* Note it */
			$compare = array(
				'params'		=> $template['template_data'],
				'html'			=> $template['template_content_html'],
				'plaintext'		=> $template['template_content_plaintext'],
				'pinned'		=> $template['template_pinned']
			);
			if ( !isset( $current[ $template['template_name'] ] ) )
			{
				$return['added'][] = $template['template_name'];
			}
			elseif ( $current[ $template['template_name'] ] != $compare )
			{
				$return['edited'][] = $template['template_name'];
			}
			unset( $current[ $template['template_name'] ] );
		}

		/* Write it */
		if ( is_writable( ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			file_put_contents( ROOT_PATH . '/applications/' . $this->directory . '/data/emails.xml', $xml->asXML() );
		}
		else
		{
			throw new RuntimeException( Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}

		/* Return */
		$return['removed'] = array_keys( $current );
		return $return;
	}

	/**
	 * Imports an IN_DEV email template into the database
	 *
	 * @param string $path			Path to file
	 * @param object $file			DirectoryIterator File Object
	 * @param string|null $namePrefix		Name prefix
	 * @return  array
	 */
	protected function _buildEmailTemplateFromInDev( string $path, object $file, ?string $namePrefix='' ): array
	{
		/* Get the content */
		$html	= file_get_contents( $path . '/' . $file->getFilename() );
		$params	= array();

		/* Parse the header tag */
		preg_match( '/^<ips:template parameters="(.+?)?" \/>(\r\n?|\n)/', $html, $params );

		/* Strip the params tag */
		$html	= str_replace( $params[0], '', $html );

		/* Enforce \n line endings */
		if( mb_strtolower( mb_substr( PHP_OS, 0, 3 ) ) === 'win' )
		{
			$html = str_replace( "\r\n", "\n", $html );
		}

		/* Figure out some details */
		$extension = mb_substr( $file->getFilename(), mb_strrpos( $file->getFilename(), '.' ) + 1 );
		$name	= $namePrefix . str_replace( '.' . $extension, '', $file->getFilename() );
		$type	= ( $extension === 'txt' ) ? "plaintext" : "html";

		return array(
			'template_app'				=> $this->directory,
			'template_name'				=> $name,
			'template_data'				=> ( isset( $params[1] ) ) ? $params[1] : '',
			'template_content_' . $type	=> $html,
			'template_key'				=> md5( $this->directory . ';' . $name ),
		);
	}

	/**
	 * Build editor plugins for an app
	 *
	 * @return	array
	 * @throws	RuntimeException
	 */
	public function buildEditorPlugins(): array
	{
		/* Get current XML file for calculating differences */
		$return = array( 'added' => array(), 'edited' => array(), 'removed' => array() );
		$current = array();
		$currentFile = ROOT_PATH . "/applications/{$this->directory}/data/editor.xml";
		if ( file_exists( $currentFile ) )
		{
			$xml = SimpleXML::loadFile( $currentFile );
			foreach( $xml->plugin as $plugin )
			{
				$attributes = iterator_to_array( $plugin->attributes() );
				$current[ (string) $attributes['name'] ] = (string) $plugin;
			}
		}

		/* Where are we looking? */
		$path = ROOT_PATH . "/applications/{$this->directory}/dev/editor";

		/* Loop over files in the directory */
		$plugins = [];
		if ( is_dir( $path ) )
		{
			foreach( new DirectoryIterator( $path ) as $file )
			{
				if( $file->isDir() or $file->isDot() or $file->getFilename() == 'js' )
				{
					continue;
				}

				$components = explode( '.', $file->getFilename() );
				$extension = array_pop( $components );
				if( $extension != 'js' )
				{
					continue;
				}

				$name = $file->getFilename();
				$contents = file_get_contents( $path . '/' . $name );
				if( mb_strtolower( mb_substr( PHP_OS, 0, 3 ) ) === 'win' )
				{
					$contents = str_replace( "\r\n", "\n", $contents );
				}

				if( !isset( $current[ $name ] ) )
				{
					$return['added'][] = $name;
				}
				else
				{
					if( $current[ $name ] != $contents )
					{
						$return['edited'][] = $name;
					}

					unset( $current[ $name ] );
				}

				$plugins[ $name ] = $contents;
			}
		}

		if( count( $current ) )
		{
			$return['removed'] = array_keys( $current );
		}

		/* Now write the XML */
		if( count( $plugins ) )
		{
			$xml = new XMLWriter;
			$xml->openMemory();
			$xml->setIndent( TRUE );
			$xml->startDocument( '1.0', 'UTF-8' );
			$xml->startElement( 'plugins' );

			foreach( $plugins as $name => $contents )
			{
				$xml->startElement( 'plugin' );
				$xml->startAttribute( 'name' );
				$xml->text( $name );
				$xml->endAttribute();
				$xml->writeCdata( $contents );
				$xml->endElement();
			}

			$xml->endElement();
			$xml->endDocument();

			if ( is_writable( ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
			{
				file_put_contents( ROOT_PATH . '/applications/' . $this->directory . '/data/editor.xml', $xml->outputMemory() );
			}
			else
			{
				throw new RuntimeException( Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
			}
		}

		return $return;
	}

	/**
	 * Build javascript for this app
	 *
	 * @return	array
	 * @throws	RuntimeException
	 */
	public function buildJavascript(): array
	{
		/* Get current XML file for calculating differences */
		$return = array( 'files' => array( 'added' => array(), 'edited' => array(), 'removed' => array() ), 'orders' =>  array( 'added' => array(), 'edited' => array(), 'removed' => array() ) );
		$currentFile = ROOT_PATH . "/applications/{$this->directory}/data/javascript.xml";
		$current = array( 'files' => array(), 'orders' => array() );
		if ( file_exists( $currentFile ) )
		{
			$xml = SimpleXML::loadFile( $currentFile );
			foreach ( $xml->file as $javascript )
			{
				$attributes = iterator_to_array( $javascript->attributes() );

				$current['files'][ "{$attributes['javascript_app']}/{$attributes['javascript_location']}/" . ( trim( $attributes['javascript_path'] ) ? "{$attributes['javascript_path']}/" : '' ) . "{$attributes['javascript_name']}" ] = (string) $javascript;
			}
			foreach ( $xml->order as $order )
			{
				$attributes = iterator_to_array( $order->attributes() );

				$current['orders'][ "{$attributes['app']}/{$attributes['path']}" ] = (string) $order;
			}
		}

		/* Remove existing file object maps */
		$map = isset( Store::i()->javascript_map ) ? Store::i()->javascript_map : array();
		$map[ $this->directory ] = array();

		Store::i()->javascript_map = $map;

		$xml = Javascript::createXml( $this->directory, $current, $return );

		/* Write it */
		if ( is_writable( ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			file_put_contents( ROOT_PATH . '/applications/' . $this->directory . '/data/javascript.xml', $xml->outputMemory() );
		}
		else
		{
			throw new RuntimeException( Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}

		/* Return */
		return $return;
	}

	/**
	 * Build extensions.json file for an app
	 *
	 * @return	array
	 * @throws	DomainException
	 */
	public function buildExtensionsJson(): array
	{
		$json = array();
		$appsMainExtensionDir = new DirectoryIterator( ROOT_PATH . "/applications/{$this->directory}/extensions/" );
		foreach ( $appsMainExtensionDir as $appDir )
		{
			if ( $appDir->isDir() and !$appDir->isDot() )
			{
				foreach ( new DirectoryIterator( $appDir->getPathname() ) as $extensionDir )
				{
					if ( $extensionDir->isDir() and !$extensionDir->isDot() )
					{
						foreach ( new DirectoryIterator( $extensionDir->getPathname() ) as $extensionFile )
						{
							if ( !$extensionFile->isDir() and !$extensionFile->isDot() and mb_substr( $extensionFile, -4 ) === '.php' AND mb_substr( $extensionFile, 0, 2 ) != '._' )
							{
								$classname = 'IPS\\' . $this->directory . '\extensions\\' . $appDir . '\\' . $extensionDir . '\\' . mb_substr( $extensionFile, 0, -4 );

								/* Check if class exists - sometimes we have to use blank files to wipe out old extensions */
								try
								{
									if( !class_exists( $classname ) )
									{
										continue;
									}

									if ( method_exists( $classname, 'deprecated' ) )
									{
										continue;
									}
								}
								catch( ErrorException $e )
								{
									continue;
								}

								$json[ (string) $appDir ][ (string) $extensionDir ][ mb_substr( $extensionFile, 0, -4 ) ] = $classname;
							}
						}
					}
				}
			}
		}
		$this->sortForJson( $json );
		return $json;
	}

	/**
	 * Write a build.xml file with the current json data so we know what has changed between builds
	 *
	 * @return	array
	 */
	public function buildJsonData(): array
	{
		$file = ROOT_PATH . "/applications/{$this->directory}/data/build.xml";

		/* Get current XML file for calculating differences */
		$return = array(
			'modules'			=> array( 'added' => array(), 'edited' => array(), 'removed' => array() ),
			'tasks'				=> array( 'added' => array(), 'edited' => array(), 'removed' => array() ),
			'settings'			=> array( 'added' => array(), 'edited' => array(), 'removed' => array() ),
			'widgets'			=> array( 'added' => array(), 'edited' => array(), 'removed' => array() ),
			'acpSearchKeywords'	=> array( 'added' => array(), 'edited' => array(), 'removed' => array() ),
			'themeeditor'		=> array(
				'categories' 		=> array( 'added' => array(), 'edited' => array(), 'removed' => array() ),
				'settings'			=> array( 'added' => array(), 'edited' => array(), 'removed' => array() )
			)
		);
		$current = array(
			'modules'			=> array(),
			'tasks'				=> array(),
			'settings'			=> array(),
			'widgets'			=> array(),
			'acpSearchKeywords'	=> array(),
			'themeeditor'		=> array( 'categories' => array(), 'settings' => array() )
		);

		if ( file_exists( $file ) )
		{
			$xml = SimpleXML::loadFile( $file );
			foreach ( $xml->module as $module )
			{
				$attributes = iterator_to_array( $module->attributes() );
				$current['modules'][ (string) $module['key'] ] = (string) $module;
			}
			foreach ( $xml->task as $task )
			{
				$attributes = iterator_to_array( $task->attributes() );
				$current['tasks'][ (string) $task ] = (string) $task['frequency'];
			}
			foreach ( $xml->setting as $setting )
			{
				$attributes = iterator_to_array( $setting->attributes() );
				$current['settings'][ (string) $setting['key'] ] = (string) $setting;
			}
			foreach ( $xml->widget as $widget )
			{
				$attributes = iterator_to_array( $widget->attributes() );
				$current['widgets'][ (string) $attributes['key'] ] = (string) $widget;
			}
			foreach ( $xml->acpsearch as $searchKeyword )
			{
				$attributes = iterator_to_array( $searchKeyword->attributes() );
				$current['acpSearchKeywords'][ (string) $attributes['key'] ] = (string) $searchKeyword;
			}
			foreach( $xml->themeeditorcategory as $themeEditorCategory )
			{
				$attributes = iterator_to_array( $themeEditorCategory->attributes() );
				$current['themeeditor']['categories'][ (string) $attributes['key'] ] = (string) $themeEditorCategory;
			}
			foreach( $xml->themeeditorsetting as $themeEditorSetting )
			{
				$attributes = iterator_to_array( $themeEditorSetting->attributes() );
				$current['themeeditor']['settings'][ (string) $attributes['key'] ] = (string) $themeEditorSetting;
			}
		}

		/* Build XML and write to app directory */
		$xml = new XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );

		/* Root tag */
		$xml->startElement('build');

		/* Modules */
		if( file_exists( ROOT_PATH . "/applications/{$this->directory}/data/modules.json" ) )
		{
			foreach(json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->directory}/data/modules.json" ), TRUE ) as $area => $modules )
			{
				foreach ( $modules as $moduleKey => $moduleData )
				{
					$val = json_encode( $moduleData );

					$xml->startElement('module');
					$xml->startAttribute('key');
					$xml->text( "{$area}/{$moduleKey}" );
					$xml->endAttribute();
					$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $val ) );
					$xml->endElement();

					if ( !isset( $current['modules'][ "{$area}/{$moduleKey}" ] ) )
					{
						$return['modules']['added'][] = "{$area}/{$moduleKey}";
					}
					elseif ( $current['modules'][ "{$area}/{$moduleKey}" ] != $val )
					{
						$return['modules']['edited'][] = "{$area}/{$moduleKey}";
					}
					unset( $current['modules'][ "{$area}/{$moduleKey}" ] );
				}
			}
		}

		/* Tasks */
		if( file_exists( ROOT_PATH . "/applications/{$this->directory}/data/tasks.json" ) )
		{
			foreach(json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->directory}/data/tasks.json" ), TRUE ) as $taskKey => $taskFrequency )
			{
				$xml->startElement('task');
				$xml->startAttribute('frequency');
				$xml->text( $taskFrequency );
				$xml->endAttribute();
				$xml->text( $taskKey );
				$xml->endElement();

				if ( !isset( $current['tasks'][ $taskKey ] ) )
				{
					$return['tasks']['added'][] = $taskKey;
				}
				elseif ( $current['tasks'][ $taskKey ] != $taskFrequency )
				{
					$return['tasks']['edited'][] = $taskKey;
				}
				unset( $current['tasks'][ $taskKey ] );
			}
		}

		/* Settings */
		if( file_exists( ROOT_PATH . "/applications/{$this->directory}/data/settings.json" ) )
		{
			foreach(json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->directory}/data/settings.json" ), TRUE ) as $setting )
			{
				$val = json_encode( $setting );

				$xml->startElement('setting');
				$xml->startAttribute('key');
				$xml->text( $setting['key'] );
				$xml->endAttribute();
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $val ) );
				$xml->endElement();

				if ( !isset( $current['settings'][ $setting['key'] ] ) )
				{
					$return['settings']['added'][] = $setting['key'];
				}
				elseif ( $current['settings'][ $setting['key'] ] != $val )
				{
					$return['settings']['edited'][] = $setting['key'];
				}
				unset( $current['settings'][ $setting['key'] ] );
			}
		}

		/* Widgets */
		if( file_exists( ROOT_PATH . "/applications/{$this->directory}/data/widgets.json" ) )
		{
			foreach(json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->directory}/data/widgets.json" ), TRUE ) as $widgetKey => $widgetData )
			{
				$val = json_encode( $widgetData );

				$xml->startElement('widget');
				$xml->startAttribute('key');
				$xml->text( $widgetKey );
				$xml->endAttribute();
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $val ) );
				$xml->endElement();

				if ( !isset( $current['widgets'][ $widgetKey ] ) )
				{
					$return['widgets']['added'][] = $widgetKey;
				}
				elseif ( $current['widgets'][ $widgetKey ] != $val )
				{
					$return['widgets']['edited'][] = $widgetKey;
				}
				unset( $current['widgets'][ $widgetKey ] );
			}
		}

		/* ACP Search Keywords */
		if( file_exists( ROOT_PATH . "/applications/{$this->directory}/data/acpsearch.json" ) )
		{
			foreach(json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->directory}/data/acpsearch.json" ), TRUE ) as $searchUrl => $searchData )
			{
				$val = json_encode( $searchData );

				$xml->startElement('acpsearch');
				$xml->startAttribute('key');
				$xml->text( $searchUrl );
				$xml->endAttribute();
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $val ) );
				$xml->endElement();

				if ( !isset( $current['acpSearchKeywords'][ $searchUrl ] ) )
				{
					$return['acpSearchKeywords']['added'][] = $searchUrl;
				}
				elseif ( $current['acpSearchKeywords'][ $searchUrl ] != $val )
				{
					$return['acpSearchKeywords']['edited'][] = $searchUrl;
				}
				unset( $current['acpSearchKeywords'][ $searchUrl ] );
			}
		}

		/* Theme Editor Settings */
		if( file_exists( ROOT_PATH . "/applications/{$this->directory}/data/themeeditor.json" ) )
		{
			$json = json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->directory}/data/themeeditor.json" ), true );
			foreach( array( 'categories', 'settings' ) as $group )
			{
				if( isset( $json[ $group ] ) )
				{
					foreach( $json[ $group ] as $key => $data )
					{
						$val = json_encode( $data );

						$xml->startElement('themeeditor' . ( $group == 'settings' ? 'setting' : 'category' ) );
						$xml->startAttribute('key');
						$xml->text( $key );
						$xml->endAttribute();
						$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $val ) );
						$xml->endElement();

						if ( !isset( $current['themeeditor'][ $group ][ $key ] ) )
						{
							$return['themeeditor'][ $group ]['added'][] = $key;
						}
						elseif ( $current['themeeditor'][ $group ][ $key ] != $val )
						{
							$return['themeeditor'][ $group ]['edited'][] = $key;
						}
						unset( $current['themeeditor'][ $group ][ $key ] );
					}
				}
			}
		}

		/* Finish */
		$xml->endDocument();

		/* Write it */
		file_put_contents( $file, $xml->outputMemory() );

		/* Return */
		$return['modules']['removed'] = array_keys( $current['modules'] );
		$return['tasks']['removed'] = array_keys( $current['tasks'] );
		$return['settings']['removed'] = array_keys( $current['settings'] );
		$return['widgets']['removed'] = array_keys( $current['widgets'] );
		$return['acpSearchKeywords']['removed'] = array_keys( $current['acpSearchKeywords'] );
		$return['themeeditor']['categories']['removed'] = array_keys( $current['themeeditor']['categories'] );
		$return['themeeditor']['settings']['removed'] = array_keys( $current['themeeditor']['settings'] );
		return $return;
	}

	/**
	 * Compile email template into executable template
	 *
	 * @return	void
	 */
	public function parseEmailTemplates() : void
	{
		foreach(Db::i()->select( '*','core_email_templates', NULL, 'template_parent DESC' ) as $template )
		{
			/* Rebuild built copies */
			$htmlFunction	= 'namespace IPS\Theme;' . "\n" . Theme::compileTemplate( $template['template_content_html'], "email_html_{$template['template_app']}_{$template['template_name']}", $template['template_data'] );
			$ptFunction		= 'namespace IPS\Theme;' . "\n" . Theme::compileTemplate( $template['template_content_plaintext'], "email_plaintext_{$template['template_app']}_{$template['template_name']}", $template['template_data'] );

			$key	= $template['template_key'] . '_email_html';
			Store::i()->$key = $htmlFunction;

			$key	= $template['template_key'] . '_email_plaintext';
			Store::i()->$key = $ptFunction;
		}
	}

	/**
	 * Write JSON file
	 *
	 * @param string $file	Filepath
	 * @param array $data	Data to write
	 * @return	void
	 * @throws	RuntimeException	Could not write
	 */
	public static function writeJson( string $file, array $data ) : void
	{
		$json = json_encode( $data, JSON_PRETTY_PRINT );

		/* No idea why, but for some people blank structures have line breaks in them and for some people they don't
			which unecessarily makes version control think things have changed - so let's make it the same for everyone */
		$json = preg_replace( '/\[\s*]/', '[]', $json );
		$json = preg_replace( '/\{\s*}/', '{}', $json );

		/* Write it */
		if( file_put_contents( $file, $json ) === FALSE )
		{
			throw new RuntimeException;
		}
		@chmod( $file, 0777 );
	}

	/**
	 * Can the user access this application?
	 *
	 * @param Group|Member|null $memberOrGroup		Member/group we are checking against or NULL for currently logged on user
	 * @return	bool
	 */
	public function canAccess( Group|Member $memberOrGroup=NULL ): bool
	{
		/* If it's not enabled, we can't */
		if( !$this->enabled )
		{
			return FALSE;
		}

		/* If we are in the AdminCP, and we have permission to manage applications, then we have access */
		if( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation === 'admin' AND ( !$memberOrGroup AND Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'app_manage' ) ) )
		{
			return TRUE;
		}

		/* If all groups have access, we can */
		if( $this->disabled_groups === NULL )
		{
			return TRUE;
		}

		/* If all groups do not have access, we cannot */
		if( $this->disabled_groups == '*' )
		{
			return FALSE;
		}

		/* Check member */
		if ( $memberOrGroup instanceof Group )
		{
			$memberGroups = array( $memberOrGroup->g_id );
		}
		else
		{
			$member	= ( $memberOrGroup === NULL ) ? Member::loggedIn() : $memberOrGroup;
			$memberGroups = array_merge( array( $member->member_group_id ), array_filter( explode( ',', $member->mgroup_others ) ) );
		}
		$accessGroups	= explode( ',', $this->disabled_groups );

		/* Are we in an allowed group? */
		if( count( array_intersect( $accessGroups, $memberGroups ) ) )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Can manage the widgets
	 *
	 * @param Member|null $member		Member we are checking against or NULL for currently logged on user
	 * @return 	boolean
	 */
	public function canManageWidgets( Member $member=NULL ): bool
	{
		/* Check member */
		$member	= ( $member === NULL ) ? Member::loggedIn() : $member;

		return $member->modPermission('can_manage_sidebar');
	}

	/**
	 * Return all widgets available for the Page Editor
	 *
	 * @return array
	 */
	public function getAvailableWidgets() : array
	{
		$blocks = [];
		foreach( Db::i()->select( '*', 'core_widgets', [ 'app=?', $this->directory ] ) as $widget )
		{
			try
			{
				$block = Widget::load( $this, $widget['key'], mt_rand(), array(), $widget['restrict'] );
				$block->allowReuse = (boolean) $widget['allow_reuse'];
				$block->menuStyle  = $widget['menu_style'];
				$block->allowCustomPadding = (bool) $widget['padding'];
				$block->layouts = $block->getSupportedLayouts();
				$blocks[ $widget['key'] ] = $block;
			}
			catch( PHPException $e )
			{
				continue;
			}
		}

		return $blocks;
	}

	/**
	 * Cleanup after saving
	 *
	 * @param bool $skipMember		Skip clearing member cache clearing
	 * @return	void
	 * @note	This is abstracted so it can be called externally, i.e. by the support tool
	 */
	public static function postToggleEnable( bool $skipMember ) : void
	{
		unset( Store::i()->applications );
		unset( Store::i()->frontNavigation );
		unset( Store::i()->acpNotifications );
		unset( Store::i()->acpNotificationIds );
		unset( Store::i()->furl_configuration );
	}

	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* Get our uninstall callback script(s) if present. They are stored in an array so that we only create one object per extension, instead of one each time we loop. */
		$uninstallExtensions	= array();
		foreach($this->extensions('core', 'Uninstall' ) as $extension )
		{
			$uninstallExtensions[]	= $extension;
		}

		/* Call preUninstall() so that application may perform any necessary cleanup before other data is removed (i.e. database tables) */
		foreach( $uninstallExtensions as $extension )
		{
			$extension->preUninstall( $this->directory );
		}

		/* Call onOtherUninstall so that other applications may perform any necessary cleanup */
		foreach( static::allExtensions( 'core', 'Uninstall', FALSE ) as $extension )
		{
			$extension->onOtherUninstall( $this->directory );
		}

		/* Delete profile steps */
		ProfileStep::deleteByApplication( $this );

		/* Delete menu items */
		FrontNavigation::deleteByApplication( $this );

		/* Delete club node maps */
		Club::deleteByApplication( $this );

		/* Delete data from shared tables */
		Index::i()->removeApplicationContent( $this );
		Db::i()->delete( 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id IN(?)', 'core', 'module', Db::i()->select( 'sys_module_id', 'core_modules', array( 'sys_module_application=?', $this->directory ) ) ) );
		Db::i()->delete( 'core_modules', array( 'sys_module_application=?', $this->directory ) );
		Db::i()->delete( 'core_dev', array( 'app_key=?', $this->directory ) );
		Db::i()->delete( 'core_item_markers', array( 'item_app=?', $this->directory ) );
		Db::i()->delete( 'core_reputation_index', array( 'app=?', $this->directory ) );
		Db::i()->delete( 'core_permission_index', array( 'app=?', $this->directory ) );
		Db::i()->delete( 'core_upgrade_history', array( 'upgrade_app=?', $this->directory ) );
		Db::i()->delete( 'core_admin_logs', array( 'appcomponent=?', $this->directory ) );
		Db::i()->delete( 'core_sys_conf_settings', array( 'conf_app=?', $this->directory ) );
		Db::i()->delete( 'core_queue', array( 'app=?', $this->directory ) );
		Db::i()->delete( 'core_follow', array( 'follow_app=?', $this->directory ) );
		Db::i()->delete( 'core_follow_count_cache', array( "class LIKE CONCAT( ?, '%' )", "IPS\\\\{$this->directory}" ) );
		Db::i()->delete( 'core_item_statistics_cache', array( "cache_class LIKE CONCAT( ?, '%' )", "IPS\\\\{$this->directory}" ) );
		Db::i()->delete( 'core_view_updates', array( "classname LIKE CONCAT( ?, '%' )", "IPS\\\\{$this->directory}" ) );
		Db::i()->delete( 'core_moderator_logs', array( 'appcomponent=?', $this->directory ) );
		Db::i()->delete( 'core_member_history', array( 'log_app=?', $this->directory ) );
		Db::i()->delete( 'core_acp_notifications', array( 'app=?', $this->directory ) );
		Db::i()->delete( 'core_solved_index', array( 'app=?', $this->directory ) );
		Db::i()->delete( 'core_notifications', array( 'notification_app=?', $this->directory ) );
		Db::i()->delete( 'core_javascript', array( 'javascript_app=?', $this->directory ) );
		Db::i()->delete( 'core_theme_templates_custom', array( 'template_app=?', $this->directory ) );

		$rulesToDelete = iterator_to_array( Db::i()->select( 'id', 'core_achievements_rules', [ "action LIKE CONCAT( ?, '_%' )", $this->directory ] ) );
		Db::i()->delete( 'core_achievements_rules', Db::i()->in( 'id', $rulesToDelete ) );
		Db::i()->delete( 'core_achievements_log_milestones', Db::i()->in( 'milestone_rule', $rulesToDelete ) );

		foreach($this->extensions('core', 'AdminNotifications', FALSE) AS $adminNotificationExtension )
		{
			$exploded = explode( '\\', $adminNotificationExtension );
			Db::i()->delete( 'core_acp_notifications_preferences', array( 'type=?', "{$this->directory}_{$exploded[5]}" ) );
		}

		$classes = array();
		foreach($this->extensions('core', 'ContentRouter') AS $contentRouter )
		{
			foreach ( $contentRouter->classes as $class )
			{
				$classes[]	= $class;

				if ( isset( $class::$commentClass ) )
				{
					$classes[]	= $class::$commentClass;
				}

				if ( isset( $class::$reviewClass ) )
				{
					$classes[]	= $class::$reviewClass;
				}
			}
		}

		if( count( $classes ) )
		{
			$queueWhere = array();
			$queueWhere[] = array( 'app=?', 'core' );
			$queueWhere[] = array( Db::i()->in( '`key`', array( 'rebuildPosts', 'RebuildReputationIndex' ) ) );

			foreach (Db::i()->select( '*', 'core_queue', $queueWhere ) as $queue )
			{
				$queue['data'] = json_decode( $queue['data'], TRUE );
				if( in_array( $queue['data']['class'], $classes ) )
				{
					Db::i()->delete( 'core_queue', array( 'id=?', $queue['id'] ) );
				}
			}

			Db::i()->delete( 'core_notifications', Db::i()->in( 'item_class', $classes ) );

			/* Delete Deletion Log Records */
			Db::i()->delete( 'core_deletion_log', Db::i()->in( 'dellog_content_class', $classes ) );

			/* Delete Promoted Content from this app */
			Db::i()->delete( 'core_content_promote', Db::i()->in( 'promote_class', $classes ) );

			/* Delete ratings from this app */
			Db::i()->delete( 'core_ratings', Db::i()->in( 'class', $classes ) );

			/* Delete merge redirects */
			Db::i()->delete( 'core_item_redirect', Db::i()->in( 'redirect_class', $classes ) );

			/* Delete member map */
			Db::i()->delete( 'core_item_member_map', Db::i()->in( 'map_class', $classes ) );

			/* Delete RSS Imports */
			foreach(new ActiveRecordIterator( Db::i()->select( '*', 'core_rss_import', Db::i()->in( 'rss_import_class', $classes ) ), 'IPS\core\Rss\Import' ) as $import )
			{
				$import->delete();
			}

			/* Delete Soft Deletion Log data */
			$softDeleteKeys = array();
			foreach ( $classes as $class )
			{
				if ( isset( $class::$hideLogKey ) AND $class::$hideLogKey )
				{
					$softDeleteKeys[]  = $class::$hideLogKey;
				}
			}

			if ( count( $softDeleteKeys ) )
			{
				Db::i()->delete( 'core_soft_delete_log', Db::i()->in( 'sdl_obj_key', $softDeleteKeys ) );
			}

			/* Delete PBR Data */
			Db::i()->delete( 'core_post_before_registering', Db::i()->in( 'class', $classes ) );

			/* Delete Anonymous Data */
			Db::i()->delete( 'core_anonymous_posts', Db::i()->in( 'anonymous_object_class', $classes ) );

			/* Delete Polls */
			Db::i()->delete( 'core_voters', array( 'poll in (?)', Db::i()->select( 'pid', 'core_polls', Db::i()->in( 'poll_item_class', $classes ) ) ) );
			Db::i()->delete( 'core_polls', Db::i()->in( 'poll_item_class', $classes ) );

			/* Assignments */
			Db::i()->delete( 'core_assignments', Db::i()->in( 'assign_item_class', $classes ) );
		}

		/* Delete attachment maps - if the attachment is unused, the regular cleanup task will remove the file later */
		$extensions = array();

		foreach($this->extensions('core', 'EditorLocations', FALSE) AS $key => $extension )
		{
			$extensions[] = $this->directory . '_' . $key;
		}

		Db::i()->delete( 'core_attachments_map', array( Db::i()->in( 'location_key', $extensions ) ) );

		/* Cleanup some caches */
		Settings::i()->clearCache();
		unset( Store::i()->acpNotifications );
		unset( Store::i()->acpNotificationIds );

		/* Delete tasks and task logs */
		Db::i()->delete( 'core_tasks_log', array( 'task IN(?)', Db::i()->select( 'id', 'core_tasks', array( 'app=?', $this->directory ) ) ) );
		Db::i()->delete( 'core_tasks', array( 'app=?', $this->directory ) );

		/* Delete reports */
		Db::i()->delete( 'core_rc_reports', array( 'rid IN(?)', Db::i()->select('id', 'core_rc_index', Db::i()->in( 'class', $classes ) ) ) );
		Db::i()->delete( 'core_rc_comments', array( 'rid IN(?)', Db::i()->select('id', 'core_rc_index', Db::i()->in( 'class', $classes ) ) ) );
		Db::i()->delete( 'core_rc_index', Db::i()->in('class', $classes) );

		/* Delete language strings */
		Db::i()->delete( 'core_sys_lang_words', array( 'word_app=?', $this->directory ) );

		/* Delete email templates */
		$emailTemplates	= Db::i()->select( '*', 'core_email_templates', array( 'template_app=?', $this->directory ) );

		if( $emailTemplates->count() )
		{
			foreach( $emailTemplates as $template )
			{
				if( $template['template_content_html'] )
				{
					$k = $template['template_key'] . '_email_html';
					unset( Store::i()->$k );
				}

				if( $template['template_content_plaintext'] )
				{
					$k = $template['template_key'] . '_email_plaintext';
					unset( Store::i()->$k );
				}
			}

			Db::i()->delete( 'core_email_templates', array( 'template_app=?', $this->directory ) );
		}

		/* Delete skin template/CSS/etc. */
		Theme::removeTemplates( $this->directory, NULL, NULL, NULL, TRUE );
		Theme::removeCss( $this->directory, NULL, NULL, NULL, TRUE );
		Theme::removeResources( $this->directory, NULL, NULL, NULL, TRUE );
		Theme::removeEditorSettings( $this->directory );

		unset( Store::i()->themes );

		/* Delete any stored files */
		foreach($this->extensions('core', 'FileStorage' ) as $extension )
		{
			try
			{
				$extension->delete();
			}
			catch( \Exception $e ){}
		}

		/* Delete any upload settings */
		foreach( $this->uploadSettings() as $setting )
		{
			if( Settings::i()->$setting )
			{
				try
				{
					File::get( 'core_Theme', Settings::i()->$setting )->delete();
				}
				catch( \Exception $e ){}
			}
		}

		$notificationTypes = array();
		foreach($this->extensions('core', 'Notifications') as $key => $class )
		{
			if ( method_exists( $class, 'getConfiguration' ) )
			{
				$defaults = $class->getConfiguration( NULL );

				foreach( $defaults AS $k => $config )
				{
					$notificationTypes[] =  $k;
				}
			}
		}

		if( count( $notificationTypes ) )
		{
			Db::i()->delete( 'core_notification_defaults', "notification_key IN('" . implode( "','", $notificationTypes ) . "')");
			Db::i()->delete( 'core_notification_preferences', "notification_key IN('" . implode( "','", $notificationTypes ) . "')");
		}

		/* Delete database tables */
		if( file_exists( $this->getApplicationPath() . "/data/schema.json" ) )
		{
			$schema	= @json_decode( file_get_contents( $this->getApplicationPath() . "/data/schema.json" ), TRUE );

			if( is_array( $schema ) AND count( $schema ) )
			{
				foreach( $schema as $tableName => $definition )
				{
					try
					{
						Db::i()->dropTable( $tableName, TRUE );
					}
					catch( Exception $e )
					{
						/* Ignore "Cannot drop table because it does not exist" */
						if( $e->getCode() <> 1051 )
						{
							throw $e;
						}
					}
				}
			}
		}

		/* Revert other database changes performed by installation */
		if( file_exists( $this->getApplicationPath() . "/setup/install/queries.json" ) )
		{
			$schema	= json_decode( file_get_contents( $this->getApplicationPath() . "/setup/install/queries.json" ), TRUE );

			ksort($schema);

			foreach( $schema as $instruction )
			{
				switch ( $instruction['method'] )
				{
					case 'addColumn':
						try
						{
							Db::i()->dropColumn( $instruction['params'][0], $instruction['params'][1]['name'] );
						}
						catch( Exception $e )
						{
							/* Ignore "Cannot drop key because it does not exist" */
							if( $e->getCode() <> 1091 )
							{
								throw $e;
							}
						}
					break;

					case 'addIndex':
						try
						{
							Db::i()->dropIndex( $instruction['params'][0], $instruction['params'][1]['name'] );
						}
						catch( Exception $e )
						{
							/* Ignore "Cannot drop key because it does not exist" */
							if( $e->getCode() <> 1091 )
							{
								throw $e;
							}
						}
					break;
				}
			}
		}

		/* delete widgets */
		Db::i()->delete( 'core_widgets', array( 'app = ?', $this->directory ) );
		Db::i()->delete( 'core_widget_areas', array( 'app = ?', $this->directory ) );

		/* clean up widget areas table */
		foreach (Db::i()->select( '*', 'core_widget_areas' ) as $row )
		{
			$data = json_decode( $row['widgets'], true );

			foreach ( $data as $key => $widget)
			{
				if ( isset( $widget['app'] ) and $widget['app'] == $this->directory )
				{
					unset( $data[$key]) ;
				}
			}

			Db::i()->update( 'core_widget_areas', array( 'widgets' => json_encode( $data ) ), array( 'id=?', $row['id'] ) );
		}

		/* Clean up widget trash table */
		$trash = array();
		foreach(Db::i()->select( '*', 'core_widget_trash' ) AS $garbage )
		{
			$data = json_decode( $garbage['data'], TRUE );

			if ( isset( $data['app'] ) AND $data['app'] == $this->directory )
			{
				$trash[] = $garbage['id'];
			}
		}

		Db::i()->delete( 'core_widget_trash', Db::i()->in( 'id', $trash ) );

		/* Call postUninstall() so that application may perform any necessary cleanup after other data is removed */
		foreach( $uninstallExtensions as $extension )
		{
			$extension->postUninstall( $this->directory );
		}

		/* Clean up FURL Definitions */
		if ( file_exists( $this->getApplicationPath() . "/data/furl.json" ) )
		{
			$current = json_decode( Db::i()->select( 'conf_value', 'core_sys_conf_settings', array( "conf_key=?", 'furl_configuration' ) )->first(), true );
			$default = json_decode( preg_replace( '/\/\*.+?\*\//s', '', @file_get_contents( $this->getApplicationPath() . "/data/furl.json" ) ), true );

			if ( isset( $default['pages'] ) and $current !== NULL )
			{
				foreach( $default['pages'] AS $key => $def )
				{
					if ( isset( $current[$key] ) )
					{
						unset( $current[$key] );
					}
				}

				Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => json_encode( $current ) ), array( "conf_key=?", 'furl_configuration' ) );
			}
		}

		/* Delete from DB */
		File::unclaimAttachments( 'core_Admin', $this->id, NULL, 'appdisabled' );
		parent::delete();

		/* Clear out data store for updated values */
		unset( Store::i()->modules );
		unset( Store::i()->applications );
		unset( Store::i()->widgets );
		unset( Store::i()->furl_configuration );

		Settings::i()->clearCache();

		/* Remove the files and folders, if possible (if not IN_DEV and not in DEMO_MODE and not on platform) */
		if ( !CIC2 AND !IN_DEV AND !DEMO_MODE AND file_exists( ROOT_PATH . '/applications/' . $this->directory ) )
		{
			try
			{
				$iterator = new RecursiveDirectoryIterator( ROOT_PATH . '/applications/' . $this->directory, FilesystemIterator::SKIP_DOTS );
				foreach ( new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::CHILD_FIRST ) as $file )
				{
					if ( $file->isDir() )
					{
						@rmdir( $file->getPathname() );
					}
					else
					{
						@unlink( $file->getPathname() );
					}
				}
				$dir = ROOT_PATH . '/applications/' . $this->directory;
				$handle = opendir( $dir );
				closedir ( $handle );
				@rmdir( $dir );
			}
			catch( UnexpectedValueException $e ){}
		}

		Bridge::i()->applicationDeleted( $this );
	}

	/**
	 * Return an array of version upgrade folders this application contains
	 *
	 * @param int $start	If provided, only upgrade steps above this version will be returned
	 * @return	array
	 */
	public function getUpgradeSteps( int $start=0 ): array
	{
		$path	= $this->getApplicationPath() . "/setup";

		if( !is_dir( $path ) )
		{
			return array();
		}

		$versions	= array();

		foreach( new DirectoryIterator( $path ) as $file )
		{
			if( $file->isDir() AND !$file->isDot() )
			{
				if( mb_substr( $file->getFilename(), 0, 4 ) == 'upg_' )
				{
					$_version	= intval( mb_substr( $file->getFilename(), 4 ) );

					if( $_version > $start )
					{
						$versions[]	= $_version;
					}
				}
			}
		}

		/* Sort the versions lowest to highest */
		sort( $versions, SORT_NUMERIC );

		return $versions;
	}

	/**
	 * Can view page even when user is a guest when guests cannot access the site
	 *
	 * @param	Module	$module			The module
	 * @param string $controller		The controller
	 * @param string|null $do				To "do" parameter
	 * @return	bool
	 */
	public function allowGuestAccess( Module $module, string $controller, ?string $do ): bool
	{
		return FALSE;
	}

	/**
	 * Can view page even when site is offline
	 *
	 * @param	Module	$module			The module
	 * @param string $controller		The controller
	 * @param string|null $do				To "do" parameter
	 * @return	bool
	 */
	public function allowOfflineAccess( Module $module, string $controller, ?string $do ): bool
	{
		return FALSE;
	}

	/**
	 * Can view page even when the member is IP banned
	 *
	 * @param Module $module
	 * @param string $controller
	 * @param string|null $do
	 * @return bool
	 */
	public function allowBannedAccess( Module $module, string $controller, ?string $do ) : bool
	{
		return FALSE;
	}

	/**
	 * Can view page even when the member is validating
	 *
	 * @param Module $module
	 * @param string $controller
	 * @param string|null $do
	 * @return bool
	 */
	public function allowValidatingAccess( Module $module, string $controller, ?string $do ) : bool
	{
		return FALSE;
	}

	/**
	 * Do we run doMemberCheck for this controller?
	 * @see Application::doMemberCheck()
	 *
	 * @param Module $module
	 * @param string $controller
	 * @param string|null $do
	 * @return bool
	 */
	public function skipDoMemberCheck( Module $module, string $controller, ?string $do ) : bool
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to edit this node?
	 *
	 * @return	bool
	 */
	public function canEdit(): bool
	{
		return ( Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'app_manage' ) );
	}

	/**
	 * Get any third parties this app uses for the privacy policy
	 *
	 * @return array( title => language bit, description => language bit, privacyUrl => privacy policy URL )
	 */
	public function privacyPolicyThirdParties(): array
	{
		/* Apps can overload this */
		return array();
	}

	/**
	 * Get any settings that are uploads
	 *
	 * @return	array
	 */
	public function uploadSettings(): array
	{
		/* Apps can overload this */
		return array();
	}

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
			$return = array();
			foreach(Member::loggedIn()->language()->words as $k => $v )
			{
				if ( preg_match( '/^__app_([a-z]*)$/', $k, $matches ) and mb_strpos( mb_strtolower( $v ), mb_strtolower( $query ) ) !== FALSE )
				{
					try
					{
						$application = static::load( $matches[1] );
						$return[ $application->_id ] = $application;
					}
					catch ( OutOfRangeException $e ) { }
				}
			}
			return $return;
		}
		return parent::search( $column, $query, $order, $where );
	}

	/**
	 * remove the furl prefix from all metadata rows
	 *
	 * @param Application $application
	 */
	public static function removeMetaPrefix( Application $application ) : void
	{
		$metaWhere = array();
		$prefix = '';
		$oldDefaultAppDefinition = ( file_exists( static::getRootPath( $application->directory ) . "/applications/{$application->directory}/data/furl.json" ) ) ? json_decode( preg_replace( '/\/\*.+?\*\//s', '', file_get_contents( static::getRootPath( $application->directory ) . "/applications/{$application->directory}/data/furl.json" ) ), TRUE ) : array();
		if ( isset( $oldDefaultAppDefinition['topLevel'] ) and $oldDefaultAppDefinition['topLevel'] )
		{
			$prefix = $oldDefaultAppDefinition['topLevel']  .'/';
			$metaWhere[] = Db::i()->like( 'meta_url', $oldDefaultAppDefinition['topLevel'] . '/' );

			/* Replace the root */
			Db::i()->update( 'core_seo_meta', array( 'meta_url' =>  '' ), array( 'meta_url=?', $oldDefaultAppDefinition['topLevel'] ) );
		}

		$rows = iterator_to_array( Db::i()->select( '*', 'core_seo_meta', $metaWhere )->setKeyField('meta_id') );

		foreach( $rows as $id => $row )
		{
			/* The old urls need now the new prefix */
			$newUrl = str_replace( $prefix, '', $row['meta_url'] );
			Db::i()->update( 'core_seo_meta', array( 'meta_url' =>  $newUrl ), array( 'meta_id=?', $id ) );
		}
	}

	/**
	 * Add the new prefix to the metadata rows
	 *
	 * @param Application $application
	 */
	public static function addMetaPrefix( Application $application ) : void
	{
		$metaWhere = array();
		$oldDefaultAppDefinition = ( file_exists( static::getRootPath( $application->directory ) . "/applications/{$application->directory}/data/furl.json" ) ) ? json_decode( preg_replace( '/\/\*.+?\*\//s', '', file_get_contents( static::getRootPath( $application->directory ) . "/applications/{$application->directory}/data/furl.json" ) ), TRUE ) : array();
		if ( isset( $oldDefaultAppDefinition['topLevel'] ) and $oldDefaultAppDefinition['topLevel'] )
		{
			$prefix = $oldDefaultAppDefinition['topLevel']  .'/';
		}
		else
		{
			$prefix = "";
		}

		foreach (Application::applications() as $app )
		{
			/* If it has a furl.json file... */
			if (  $application->directory != $app->directory AND file_exists( static::getRootPath( $application->directory ) . "/applications/{$application->directory}/data/furl.json" ) )
			{
				/* Open it up */
				$data = json_decode( preg_replace( '/\/\*.+?\*\//s', '', file_get_contents( static::getRootPath( $application->directory ) . "/applications/{$application->directory}/data/furl.json" ) ), TRUE );
			}
		}

		$rows = iterator_to_array( Db::i()->select( '*', 'core_seo_meta', $metaWhere )->setKeyField('meta_id') );

		foreach( $rows as $id => $row )
		{
			/* The old urls need now the new prefix */
			$newUrl = $prefix . $row['meta_url'];
			Db::i()->update( 'core_seo_meta', array( 'meta_url' =>  $newUrl ), array( 'meta_id=?', $id ) );
		}
	}

	/**
	 * Get Application Path
	 *
	 * @return	string
	 */
	public function getApplicationPath(): string
	{
		return static::getRootPath( $this->directory ) . '/applications/' . $this->directory;
	}

	/**
	 * Get Root Path
	 *
	 * @param	string|null	$appKey		Application to check if it's an IPS app or third party, or NULL to not check.
	 * @return	string
	 */
	public static function getRootPath( ?string $appKey = NULL ): string
	{
		if ( $appKey AND in_array( $appKey, IPS::$ipsApps ) )
		{
			return ROOT_PATH;
		}
		else
		{
			return SITE_FILES_PATH;
		}
	}

	/**
	 * Returns a list of all existing webhooks and their payload in this app.
	 *
	 * @return array
	 */
	public function getWebhooks() : array
	{
		// Fetch all the content classes
		$classes = [];
		$hooks = [];

		foreach ($this->extensions('core', 'ContentRouter') as $router )
		{
			foreach ( $router->classes as $class )
			{
				$classes[] = $class;

				if ( isset( $class::$commentClass ) )
				{
					$commentClass = $class::$commentClass;
					$classes[] = $commentClass;

				}

				if ( isset( $class::$reviewClass ) )
				{
					$reviewClass = $class::$reviewClass;
					$classes[] = $reviewClass;
				}
			}

		}

		foreach( $classes as $class )
		{
			$key = str_replace( '\\', '', substr( $class, 3 ) );
			$hooks[$key .'_create'] = $class;
			$hooks[$key .'_delete'] = $class;
			Member::loggedIn()->language()->words[ 'webhook_' . $key .'_create' ]     = Member::loggedIn()->language()->addToStack('webhook_contentitem_created', FALSE, ['sprintf' => [ $class::_indefiniteArticle() ]]);
			Member::loggedIn()->language()->words[ 'webhook_' . $key .'_delete' ]     = Member::loggedIn()->language()->addToStack('webhook_contentitem_deleted', FALSE, ['sprintf' => [ $class::_indefiniteArticle() ]]);
		}
		return $hooks;
	}

	/**
	 * Get all possible layout values for this page and app
	 *
	 * @return array
	 */
	public function getThemeLayoutOptionsForThisPage(): array
	{
		return [];
	}

	/**
	 * Do Member Check
	 *
	 * @return    Url|NULL
	 */
	public function doMemberCheck(): ?Url
	{
		return NULL;
	}

	/**
	 * Returns a list of all essential cookies which are set by all the installed apps
	 * To return a list of own cookies, use the @return string[]
	 * @see Application::_getEssentialCookieNames method.
	 *
	 */
	public final static function getEssentialCookieNames(): array
	{
		if ( !isset( Store::i()->essentialCookieNames ) )
		{
			$names = [];
			foreach( static::applications() as $app )
			{
				$names = array_merge( $names, $app->_getEssentialCookieNames() );
			}
			Store::i()->essentialCookieNames = $names;
		}
		return Store::i()->essentialCookieNames;
	}

	/**
	 * Returns a list of essential cookies which are set by this app.
	 * Wildcards (*) can be used at the end of cookie names for PHP set cookies.
	 *
	 * @return string[]
	 */
	public function _getEssentialCookieNames(): array
	{
		return [];
	}

	/**
	 * Recursively sort array for JSON output
	 *
	 * @param array $array
	 * @return    void
	 */
	function sortForJson( array &$array ) : void
	{
		foreach ( $array as &$value )
		{
			if ( is_array( $value ) )
			{
				$this->sortForJson( $value );
			}
		}
		ksort($array);
	}

	/**
	 * Clear out the current editor JS bundles.
	 * This will force them to be regenerated the next time
	 * the editor is loaded.
	 *
	 * @return void
	 */
	public static function resetEditorPlugins() : void
	{
		if( Settings::i()->editor_plugin_js )
		{
			try
			{
				File::get( 'core_Theme', Settings::i()->editor_plugin_js )->delete();
			}
			catch( \Exception ){}
			Settings::i()->changeValues( [ 'editor_plugin_js' => null ] );
		}
	}

	/**
	 * Retrieve additional form fields for adding an extension
	 * This should return an array where the key is the tag in
	 * the extension stub that will be replaced, and the value is
	 * the form field
	 *
	 * @param string $extensionType
	 * @param string $appKey	The application creating the extension
	 * @return array
	 */
	public function extensionHelper( string $extensionType, string $appKey ) : array
	{
		return [];
	}

	/**
	 * Process additional form fields that are added in Application::extensionHelper()
	 *
	 * @param string $extensionType
	 * @param string $appKey
	 * @param array $values
	 * @return array
	 */
	public function extensionGenerate( string $extensionType, string $appKey, array $values ) : array
	{
		return $values;
	}

	/**
	 * Output CSS files
	 *
	 * @return void
	 */
	public static function outputCss() : void
	{
		/* Each application can define any CSS that must be loaded when the app is called */
	}
}
