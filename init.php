<?php
/**
 * @brief		Initiates Invision Community constants, autoloader and exception handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS;

use DomainException;
use ErrorException;
use IPS\core\AdminNotification;
use IPS\core\DataLayer;
use IPS\Data\Store;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use ParseError;
use ReflectionClass;
use RuntimeException;
use Throwable;
use Whoops\Run;
use function constant;
use function define;
use function defined;
use function function_exists;
use function get_class;
use function in_array;
use function intval;
use function IPS\Cicloud\applyLatestFilesIPSCloud;
use function IPS\Cicloud\canManageResources;
use function IPS\Cicloud\checkThirdParty;
use function IPS\Cicloud\compileRedisConfig;
use function IPS\Cicloud\isManaged;
use function IPS\Cicloud\resyncIPSCloud;
use function IPS\Cicloud\unpackCicConfig;
use function is_array;
use function is_numeric;
use function strrpos;
use function strstr;
use function strtolower;
use function substr;
use function trait_exists;

/**
 * Class to contain Invision Community autoloader and exception handler
 */
class IPS
{
	/**
	 * @brief	Unique key for this suite (used in http requests to defy browser caching)
	 */
	public static ?string $suiteUniqueKey = NULL;

	/**
	 * @brief	Developer Code to be added to all namespaces
	 */
	private static string $inDevCode = '';

	/**
	 * @brief	Namespaces developer code has been imported to
	 */
	private static array $inDevCodeImportedTo = array();

	/**
	 * @brief	Vendors to use PSR-0 autoloader for
	 */
	public static array $PSR0Namespaces = array();

	/**
	 * @brief	Community in the Cloud configuration
	 */
	public static array $cicConfig = array();

	/**
	 * @brief	IPS Applications
	 */
	public static array $ipsApps = array(
		'blog',
		'calendar',
		'cloud',
		'cms',
		'core',
		'downloads',
		'forums',
		'gallery',
		'nexus',
		'convert',
		'courses'
		);

	/**
	 * Get default constants
	 *
	 * @return	array
	 */
	public static function defaultConstants()
	{
		$storeMethod = 'FileSystem';
		$storeConfig = '{"path":"{root}/datastore"}';
		$cacheMethod = 'None';
		$cacheConfig = '{}';
		$redisConfig = NULL;
		$redisEnabled = FALSE;
		$guestTimeout = 900;
		$redisMaxConnectionAttempts = 5;

		if ( isset( self::$cicConfig['redis'] ) and is_array( self::$cicConfig['redis'] ) AND self::$cicConfig['redis']['enabled'] == TRUE )
		{
			$storeMethod = 'Redis';
			$cacheMethod = 'Redis';
			$redisEnabled = TRUE;

			if ( isset( self::$cicConfig['redis']['max_connection_attempts'] ) and self::$cicConfig['redis']['max_connection_attempts'] > 0 )
			{
				$redisMaxConnectionAttempts = (int) self::$cicConfig['redis']['max_connection_attempts'];
			}

			$redisConfig = self::compileCicRedisConfig();
		}

		if ( isset( self::$cicConfig['settings']['guest_cache'] ) and self::$cicConfig['redis']['guest_cache'] == TRUE )
		{
			$guestTimeout = self::$cicConfig['settings']['guest_cache_timeout'];
		}

		$sitePath = __DIR__;
		if ( isset( $_SERVER['IPS_CIC'] ) )
		{
			if ( isset( $_SERVER['IPS_CLOUD2'] ) AND isset( $_SERVER['IPS_CLOUD2_ID'] ) )
			{
				$uniqueKey = $_SERVER['IPS_CLOUD2_ID'];
				$sitePath = "/var/www/sitefiles/{$_SERVER['IPS_CLOUD2_ID']}";
			}
			else
			{
				$uniqueKey = preg_match( '/^\/var\/www\/html\/(.+?)$/i', __DIR__, $matches ) ? str_replace( '/', '', $matches[1] ) : mb_substr( md5( '13_nullforums' . '$Rev: 3023$'), 10, 10 );
			}
		}
		else
		{
			$uniqueKey = mb_substr( md5( '13_nullforums' . '$Rev: 3023$'), 10, 10 );
		}

		$staticKey = null;
		if ( isset( self::$cicConfig['static_key'] ) )
		{
			$staticKey = self::$cicConfig['static_key'];
		}

		// ==========================================================================================
		// Default system constants. Any of these can be modified by defining them in a constants.php
		// file placed in the root directory (doing this allows their values to remain unchanged by
		// future upgrades)
		return array(

			//--------------------------------------------------------------------------------------
			// GENERAL OPTIONS: CAN BE CHANGED
			// We would like to make these "actual" settings but we need their values before doing
			// any other initialisation.

				// Use beta releases?
				// If enabled, the upgrader in the AdminCP will prompt you to upgrade to beta releases
				// in addition to normal releases.
				'USE_DEVELOPMENT_BUILDS' => FALSE,

				// Recovery mode
				// Will turn off third party customisations if one is causing an issue that prevents
				// accessing the AdminCP. See https://remoteservices.invisionpower.com/docs/recovery_mode
				'RECOVERY_MODE' => FALSE,

				// Disable two-factor authentication?
				// Can be used if you ever lock yourself out of your account
				'DISABLE_MFA' => FALSE,

				// Error page
				// If a really bad error occurs, this is our final fallback for what to show. You can
				// change it if you want to translate or otherwise customise it.
				'ERROR_PAGE' => 'error.php',

				// Upgrading page
				// Is shown when upgrade is in process. You can change it if you want to translate
				// or otherwise customise it.
				'UPGRADING_PAGE' => 'admin/upgrade/upgrading.html',

				// Disable login for the upgrader?
				// The upgrader can only use the standard login handler so if you have turned that off
				// and are using entirely a different login handler, you'll need to enable this so you
				// can access the upgrader. See https://remoteservices.invisionpower.com/docs/upgrader_login_help
				'BYPASS_UPGRADER_LOGIN' => FALSE,

				// Commerce license key API settings
				// See https://remoteservices.invisionpower.com/docs/licenseapi
				'NEXUS_LKEY_API_DISABLE'				=> TRUE,
				'NEXUS_LKEY_API_CHECK_IP'				=> TRUE,
				'NEXUS_LKEY_API_ALLOW_IP_OVERRIDE'	=> FALSE,

				// Encryption key
				// In some places we encrypt data. By default the key we use for that is based off
				// the database name and password, but that means if you ever change those, the data
				// won't be able to be read. So if you *do* change those, you can set this constant
				// to md5( old_database_pass + old_database_name ) to fix that.
				'TEXT_ENCRYPTION_KEY' => $staticKey,

				// Data storage / caching settings
				// To change any of the below, go to [AdminCP > System > Advanced Configuration >
				// Data storage] and follow the instructions - it will give you a new constants.php
				// file to use without messing with anything else you've changed.
				'STORE_METHOD' 				=> $storeMethod,			// Data storage method (Database, Redis or FileSystem)
				'STORE_CONFIG'				=> $storeConfig,			// JSON-encoded settings specific to the Data storage method
				'CACHE_METHOD' 				=> $cacheMethod,			// Caching Method (Redis or "None" to disable)
				'CACHE_CONFIG' 				=> $cacheConfig,			// JSON-encoded settings specific to the caching storage method
				'CACHE_PAGE_TIMEOUT' 		=> $guestTimeout,		    // Guest caching timeout in seconds
				'REDIS_ENABLED'				=> $redisEnabled,		// Use Redis for sessions and to topic view counters in addition to normal caching?
				'REDIS_CONFIG' 				=> $redisConfig,			// JSON-encoded settings specific to Redis
				'REDIS_MAX_CONNECTION_ATTEMPTS' => $redisMaxConnectionAttempts, // Maximum number of connection attempts before giving up

				// Elastic search HTTP auth
				'ELASTICSEARCH_USER' 	  => NULL,
				'ELASTICSEARCH_PASSWORD'  => NULL,

				// Sitemap
				'SITEMAP_MAX_PER_FILE'	  => 500,	// Maximum number of entries per sitemap file

			//--------------------------------------------------------------------------------------
			// SERVER ENVIRONMENT OPTIONS: CAN BE CHANGED IF YOU'RE 100% SURE YOU KNOW WHAT YOU'RE DOING
			// These constants normally never need to be changed (hence not settings) but sometimes
			// we come accross an unusual sever environment where that isn't the case.

				// Disable IP address checking when validating the AdminCP sessions?
				// See https://remoteservices.invisionpower.com/docs/disable_ip_check
				'BYPASS_ACP_IP_CHECK' => FALSE,

				// Number of seconds to consider AdminCP sessions valid for
				'ACP_SESSION_TIMEOUT' => 3600,

				// File permissions
				// For example: when making folders for files to be uploaded to, what permissions
				// that should be created with
				'IPS_FOLDER_PERMISSION'				=> 0777,	// Writeable folders
				'FOLDER_PERMISSION_NO_WRITE'			=> 0755,	// Non-writeable folders
				'IPS_FILE_PERMISSION'					=> 0666,	// Writeable files
				'FILE_PERMISSION_NO_WRITE'			=> 0644,	// Non-writeable files

				// Log directory
				// Normally logging information gets written to the database, but if the database itself
				// is down, we fallback to writing to disk. This sets the directory to use.
				// {root} can be used for the site's root directory
				'LOG_FALLBACK_DIR' => '{root}/uploads/logs',

				// Temp directory
				// A directory where we can throw temporary files
				'TEMP_DIRECTORY'	=> sys_get_temp_dir(),

				// Cookie settings
				// The correct value for most of these are automatically detected so customising the
				// values should not be necessary except where doing very special customisations like
				// some kind of SSO integration
				'COOKIE_DOMAIN'			=> NULL,		// The domain to set cookies to. Defaults to "specific-subdomain.domain.com" with no preceeding "."
				'COOKIE_PREFIX'			=> 'ips4_',	// A prefix added to all cookie names to prevent conflicts
				'COOKIE_PATH'			=> NULL,		// Path from domain to set cookies to. Defaults to wherever the community is
				'COOKIE_BYPASS_SSLONLY'	=> FALSE,	// If site is running on https, we set cookies as "secure" (i.e. only sent in https requests) unless this is TRUE

				// Threshold for "basic search mode"
				// Very big database tables can be very slow to search, so if a value is set here, and
				// the core_search_index table has more rows than whatever its value is, we default to
				// limiting searches to the last year on the form and via quick search (but users can change) and only sorting by newest, relevance is not an option.
				'USE_MYSQL_SEARCH_OPTIMIZED_MODE' => FALSE,

				// Maximum number of emails per batch
				// Note: changing this will have very little practical effect because the batches will just
				// get processed quicker - turning it down won't allow you to workaround server limitation
				// about how many emails can get sent. It's just to prevent sending more data to the email
				// server than it can handle in one go.
				// Has no effect on some email handlers (Sendgrid and IPS Cloud Email) which override this
				// with their own specific limits.
				'BULK_MAILS_PER_CYCLE' => 50,

				// Threshold of notifications to reach before using background processes
				'NOTIFICATION_BACKGROUND_THRESHOLD' => 5,

				// Maximum number of notifications to send per batch
				'NOTIFICATIONS_PER_BATCH' => 30,

				// Maximum number of things to rebuild per cycle for background tasks
				'REBUILD_INTENSE' => 1, // For extremely intensive routines, such as rebuilding images
				'REBUILD_SLOW'	=> 50,	// For routines that take a while
				'REBUILD_NORMAL'	=> 250,	// For most routines
				'REBUILD_QUICK'	=> 500,	// For routines that are fast

				// Rate limit (in seconds) for bots on searching
				'BOT_SEARCH_FLOOD_SECONDS' => 30,

				// Upgrader settings
				// When running the upgrader, if it needs to run a query against a table that matches either
				// of these conditions, it will tell you to run the query manually (i.e. at the command line)
				// rather than try to run it itself to prevent timing out
				'UPGRADE_MANUAL_THRESHOLD'	=> 250000,		// More than this number of rows
				'UPGRADE_LARGE_TABLE_SIZE'	=> 100000000,	// Bigger than this for what MySQL reports as "Data_length" in the table status (what the value means differs between MySQL and ISAM)

				// Default timeouts for cURL requests
				// All values in seconds
				'DEFAULT_REQUEST_TIMEOUT'		=> 10,	// Default where no other timeout is set
				'LONG_REQUEST_TIMEOUT'			=> 30,	// Used for specific API-based calls where we expect a slightly longer response time
				'VERY_LONG_REQUEST_TIMEOUT'		=> 300,	// Used for specific API-based calls where we expect a significantly longer response time

				// "Don't Write Anything To Disk" Mode
				// Can be used for cluster environments where nothing should be written to disk.
				// Disables things like installing applications/plugins/themes and writing log files
				// The default value is to enable this for front-end of Invision Community in the Cloud
				// to disable log writing, but not in the AdminCP, so installing things is still
				// available (facilitated by `IPS::resyncIPSCloud()`)
				'NO_WRITES' => ( isset( $_SERVER['IPS_CIC'] ) and !isset( $_SERVER['IPS_CIC_ACP'] ) AND !isset( $_SERVER['IPS_CLOUD2'] ) ),

				// How long a task is late running before the admin gets a notification
				// CiC is longer as CiC is more closely monitored and delayed tasks are not a symptom of a hosting issue
				'TASK_OVERDUE_HOURS'	=> 36,

				// Number of replies when a topic is considered large
				'LARGE_TOPIC_REPLIES'   => 10000,

			//--------------------------------------------------------------------------------------
			// DEPRECATED OPTIONS: CHANGE AT YOUR OWN RISK
			// These constants were once customisable but their fucntionality should now be
			// considered deprecated.

				// These ones don't do anything at all, but we keep them just in case any third
				// party stuff if referencing them
				'CONNECT_MASTER_KEY' => NULL,
				'CONNECT_NOSYNC_NAMES' => FALSE,

			//--------------------------------------------------------------------------------------
			// DEVELOPERS/DEBUGGING: ONLY CHANGE IN LOCAL TEST SITES
			// These constants are for developers working on applications and plugins, or debugging.
			// They should never be used outside of a local test site as they will cause significant
			// speed slow downs, reveal potentially sensitive debugging information, and make
			// some features behave unexepectedly

				// Enable Developer mode?
				// Requires Developer Tools files to be present. See
				// https://remoteservices.invisionpower.com/docs/in_dev for more information
				'IN_DEV' => FALSE,

				// Disable ACP session timeout?
				// Disables the ACP session timeout check. Useful for developing where you may
				// get logged out of the ACP in between requests while working.
				// Has no effect if IN_DEV is FALSE
				'DEV_DISABLE_ACP_SESSION_TIMEOUT' => FALSE,

				// Hide developer mode tools?
				// Makes Developer Mode not look like Developer Mode so that IPS developers can take
				// screenshots from Developer Mode installs without it looking unappealing.
				// Has no effect if IN_DEV is FALSE
				'DEV_HIDE_DEV_TOOLS' => FALSE,

				// Whoops error handler settings
				// When in developer mode, Whoops overrides the normal error handler to provide more
				// debugging information. These allow you to change aspects of it.
				// Neither have any effect if IN_DEV is FALSE
				'DEV_USE_WHOOPS' 	=> TRUE,	// Use Whoops? Sometimes needs to be turned off to check how the software will behave in production.
				'DEV_WHOOPS_EDITOR'	=> NULL,	// Allows you to define your editor so the error page can provide a link to open the file.
				'DEV_WHOOPS_HANDLER' => '\Whoops\Handler\PrettyPageHandler',			//	Allows you to define a custom Handler.

				// Folder on disk to write outgoing emails to rather than sending
				// Used in developer/test installs to prevent actually sending emails or for debugging
				// purposes
				'EMAIL_DEBUG_PATH' => NULL,

				// Path to where Java is installed
				// Needed if you want to build the core application (java will execute a .jar file)
				'JAVA_PATH' => "",

				// Use friendly URL cache in developer mode?
				// Usually should be left off so developers can add new friendly URL definitions
				// without rebuilding, but will need to be turned off to test the friendly URL
				// configuation features.
				// Has no effect if IN_DEV is FALSE
				'DEV_USE_FURL_CACHE' => FALSE,

				// Use the cache for the "Create" menu in developer mode? Usually should be left
				// off so developers can add things to the menu without rebuilding
				// Has no effect if IN_DEV is FALSE
				'DEV_USE_MENU_CACHE' => FALSE,

				// Enable CSS debugging?
				// Normally CSS files get loaded all in a single request (as they would in production)
				// but this can be used to request them separately for debugging purposes.
				// Has no effect if IN_DEV is FALSE
				'DEV_DEBUG_CSS' => FALSE,

				// Enable JavaScript debugging?
				// Dumps lots of information into the console for debugging purposes.
				'DEBUG_JS'		=> FALSE, // This one is used if Developer Mode is OFF (which is why it's FALSE by default)
				'DEV_DEBUG_JS'	=> TRUE, // This one is used if Developer Mode is ON (which is why it's TRUE by default)

				// Enable template debugging?
				// When in developer mode, templates normally get executed with eval() but that can be
				// difficult to debug if it causes an error. With this turned on, each template will get
				// written to a PHP file and then that file will be included.
				'DEBUG_TEMPLATES' => FALSE,
				'DEBUG_CUSTOM_TEMPLATES' => FALSE,

				// Enable debug logging?
				// Throughout the code we call \IPS\Log::debug() with debug infotmation, but by default
				// this doesn't do anything. Turn this on to make that information get logged.
				'DEBUG_LOG' => FALSE,

				// Enable logging of output headers?
				// Logs every header the server is sending (if supported by the server) for
				// debugging purposes
				'DEV_LOG_HEADERS' => FALSE,

				// Show debug messages for GraphQL API errors?
				// By default, ALL GraphQL errors will show "Internal Server Error". This option will provide more information.
				'DEBUG_GRAPHQL' => FALSE,

				// Show database queries?
				// If enabled, a sidebar is added to every page showing all database queries that page
				// is running for debugging purposes
				'QUERY_LOG' => FALSE,

				// Show cache read/writes?
				// If enabled, a sidebar is added to every page showing all read/writes to the cache
				// store that page is running for debugging purposes
				'CACHING_LOG'	=> FALSE,	// Use this one for normal cache store methods
				'REDIS_LOG'		=> FALSE,	// Use this one for Redis (which is used for more than just the normal cache store)

				// Enable test caching method?
				// If enabled, a "Test" caching method will be enabled that just writes cache data to
				// somewhere on disk for debugging purposes
				'TEST_CACHING' => FALSE,

				// Enable test/sandbox mode for Commerce payment gateways?
				// Sets all payment gateways into test/sandbox mode and makes a generic "Test Gateway"
				// which just acts as if a payment was successful available. Is used in development
				// so they can be tested without actually taking any money.
				'NEXUS_TEST_GATEWAYS' => FALSE,

				// Request two-factor authentication every time
				// Normally after performing two-factor authentication you won't be asked again in the
				// same session. This overrides that for testing purposes.
				'DEV_FORCE_MFA' => FALSE,

				// OAuth functionality requires https?
				// Used for testing only. Never disable this in a real install - it's a huge security issue.
				'OAUTH_REQUIRES_HTTPS' => TRUE,

				// Upgrader testing
				// All of these are just used by IPS developers to test aspects of the upgrader
				'TEST_DELTA_ZIP'					=> '',		// The path to a zip file which will be used by the upgrader rather than downloading a zip from IPS servers
				'TEST_DELTA_DETAILS'				=> NULL,	// Array of responses to use for the various version difference tools the upgrader does rather than actually calling them
				'DELTA_FORCE_FTP'					=> FALSE,	// Will force the upgrader to ask for FTP details, even if it would be able to just write the files
				'UPGRADE_MD5_CHECK'					=> TRUE,	// Can be used to prevent the upgrader checking that the files that are present are correct
				'IPS_PASSWORD'						=> NULL,	// Used to authenticate with delta system's backend
				'IPS_ALPHA_BUILD'					=> FALSE,	// Are we submitting an alpha build to the delta system's backend?

				// Use old UI in converter app?
				// Used for development/testing purposes
				'CONVERTERS_DEV_UI' => FALSE,
			//--------------------------------------------------------------------------------------
			// CODING SHORTCUTS: NEVER CHANGE
			// These constants only exist so that if their values need to change we only need to
			// update one single place rather than everywhere in the code. You should never need
			// to change them.

				// Is this community running on an Invision Community in the Cloud?
				// Used to hardcode some settings, tweak certain explainations of how to do things, etc.
				'CIC' => isset( $_SERVER['IPS_CIC'] ),
				'CIC2'	=> ( isset( $_SERVER['IPS_CLOUD2'] ) AND isset( $_SERVER['IPS_CLOUD2_ID'] ) ),
				'CIC_EXPERIMENTAL' => FALSE,
				'SITE_FILES_PATH' => $sitePath,

				// Root path to community
				'ROOT_PATH' => __DIR__,		// Please use \IPS\Application::getRootPath() instead of the constant in 3rd party code

				// Random strings that change every time Invision Community is built
				// Used as a prefix in some data storage and cache engines to prevent conflicts, cache
				// busting in URLs to resources, etc.
				// Originally we just had SUITE_UNIQUE_KEY but then we found some people were, against
				// our advice, setting it static values, which broke cache busting. So we now treat
				// SUITE_UNIQUE_KEY as "might change with every release but might not" and added
				// CACHEBUST_KEY to be a "for reals, will change every release" one. DO NOT set a value
				// for it in constants.php
				'SUITE_UNIQUE_KEY'	=> $uniqueKey,
				'CACHEBUST_KEY'		=> mb_substr( md5( '13_nullforums' . '$Rev: 3023$'), 10, 10 ),

				// Thumbnail size
				// This needs to be hardcoded rather than configurable because it's the smallest size that
				// will still work based on how we actually use these images.
				'PHOTO_THUMBNAIL_SIZE'	=> 240, 			// For profile photos. The max we display is 120x120, so this allows for double size for high dpi screens
				'THUMBNAIL_SIZE' 		=> '500x500',	// For other random things like Downloads screenshots and Pages record images. Is just a generic reasonable size.

				// Default theme ID
				// It isn't really possible to change this but we have it defined as a constant rather
				// than putting "1" everywhere in the code just in case. If you deleted theme ID 1 and attempt to
				// build a custom application as a developer, you may need to override this value to specify a new
				// default/unmodified theme.
				'DEFAULT_THEME_ID' => 1,

			     	// ADDITINAL OAUTH REDIRECT WHICH CAN BE USED FOR DEBUGGING PURPOSES
				'DEBUG_OAUTH_REDIRECTS'			=> [],

			//--------------------------------------------------------------------------------------
			// SPECIAL MODES: NEVER CHANGE
			// These constants are set in certain areas so the framework knows it needs to behave
			// differently. They should NEVER be changed globally - very bad things will happen if you do.

				// Toggle for if uncaught exceptions should (if enabled) be reported back to IPS
				// We set it false here and then enable it at the top of our own scripts so that
				// any third-party code which is using the IPS framework *doesn't* have it enabled
				// which causes stuff we don't want to get reported to us.
				// Note this has nothing to do with displaying errors. DO NOT CHANGE IT.
				'REPORT_EXCEPTIONS' => FALSE,

				// Toggle for MySQL read/write separation
				// The whole of MySQL read/write separation functionality is experimental - we set
				// this in the AdminCP/upgrader/etc. because we haven't made those areas read/write
				// separation ready.
				'READ_WRITE_SEPARATION' => TRUE,

				// Demo Mode
				// We set this on demo installs. Disables some functionality.
				'DEMO_MODE' => FALSE,

				// Unit Testing Mode
				// We set this for unit testing scripts to bypass certain global checks.
				'ENFORCE_ACCESS' => FALSE,
		);
		// ==========================================================================================
	}

	/**
	 * Initiate Invision Community constants, autoloader and exception handler
	 *
	 * @return	void
	 */
	public static function init()
	{
		/* Set timezone */
		date_default_timezone_set( 'UTC' );

		/* Set default MB internal encoding */
		mb_internal_encoding('UTF-8');

		/* Define the IN_IPB constant - this needs to be in the global namespace for backwards compatibility */
		define( 'IN_IPB', TRUE );

		/* Load constants.php */
		if ( isset( $_SERVER['IPS_CLOUD2'] ) AND isset( $_SERVER['IPS_CLOUD2_ID'] ) )
		{
			if ( file_exists( "/var/www/sitefiles/{$_SERVER['IPS_CLOUD2_ID']}/constants.php" ) )
			{
				@include_once( "/var/www/sitefiles/{$_SERVER['IPS_CLOUD2_ID']}/constants.php" );
			}
		}
		else
		{
			if( file_exists( __DIR__ . '/constants.php' ) )
			{
				@include_once( __DIR__ . '/constants.php' );
			}
		}

		/* Load in our CiC functions */
		if ( isset( $_SERVER['IPS_CLOUD2'] ) AND isset( $_SERVER['IPS_CLOUD2_ID'] ) AND file_exists( "/var/www/sitefiles/{$_SERVER['IPS_CLOUD2_ID']}/applications/cloud/sources/functions.php" ) )
		{
			/* If we are uploading the cloud app manually, we need this */
			@include_once( "/var/www/sitefiles/{$_SERVER['IPS_CLOUD2_ID']}/applications/cloud/sources/functions.php" );
		}
		else if( file_exists( __DIR__ . '/applications/cloud/sources/functions.php' ) )
		{
			@require_once( __DIR__ . '/applications/cloud/sources/functions.php' );
		}

		/* Do we have a CiC config */
		self::unpackCicConfig();

		/* Import and set defaults */
		$defaultConstants = static::defaultConstants();

		foreach ( $defaultConstants as $k => $v )
		{
			if( defined( $k ) )
			{
				define( 'IPS\\' . $k, constant( $k ) );
			}
			else
			{
				define( 'IPS\\' . $k, $v );
			}
		}

		/* Load developer code */
		if( IN_DEV and file_exists( ROOT_PATH . '/dev/function_overrides.php' ) )
		{
			self::$inDevCode = file_get_contents( ROOT_PATH . '/dev/function_overrides.php' );
		}

		/* Set autoloader */
		spl_autoload_register( '\IPS\IPS::autoloader', true, true );

		/* Set error handlers */
		if ( IN_DEV AND DEV_USE_WHOOPS and file_exists( ROOT_PATH . '/dev/Whoops/Run.php' ) )
		{
			self::$PSR0Namespaces['Whoops'] = ROOT_PATH . '/dev/Whoops';
			$whoops = new Run;
			$handlerClass = DEV_WHOOPS_HANDLER;
			$handler =  new $handlerClass;

			if (DEV_WHOOPS_EDITOR)
			{
				$handler->setEditor(DEV_WHOOPS_EDITOR);
			}
			$whoops->pushHandler( $handler );
			$whoops->register();

			/* Remove some 8.1 deprecated errors for now */
			$dirSep = preg_quote( DIRECTORY_SEPARATOR );
			$whoops->silenceErrorsInPaths( [
				"#system#i",
				"#calendar#i",
			], E_DEPRECATED );
		}
		else
		{
			set_error_handler( '\IPS\IPS::errorHandler' );
			set_exception_handler( '\IPS\IPS::exceptionHandler' );
		}
	}

	/**
	 * Autoloader
	 *
	 * @param	string	$classname	Class to load
	 * @return	void
	 */
	public static function autoloader( $classname )
	{
		/* Separate by namespace */
		$bits = explode( '\\', ltrim( $classname, '\\' ) );

		/* If this doesn't belong to us, try a PSR-0 loader or ignore it */
		$vendorName = array_shift( $bits );
		if( $vendorName !== 'IPS' )
		{
			if ( isset( self::$PSR0Namespaces[ $vendorName ] ) )
			{
				@include_once( self::$PSR0Namespaces[ $vendorName ] . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $bits ) . '.php' );
			}

			return;
		}

		/* Work out what namespace we're in */
		$class = array_pop( $bits );
		$namespace = empty( $bits ) ? 'IPS' : ( 'IPS\\' . implode( '\\', $bits ) );
		$inDevCode = '';

		if( !class_exists( "{$namespace}\\{$class}", FALSE ) )
		{
			/* Locate file */
			$path = '';
			$sourcesDirSet = FALSE;
			foreach ( array_merge( $bits, array( $class ) ) as $i => $bit )
			{
				if( $bit and preg_match( "/^[a-z0-9]/", $bit ) )
				{
					if( $i === 0 )
					{
						if ( CIC2 AND !in_array( $bit, static::$ipsApps ) )
						{
							$path .= SITE_FILES_PATH . '/applications/'; // Applications are in the root on Cloud2
						}
						else
						{
							$path .= ROOT_PATH . '/applications/';
						}
					}
					else
					{
						$sourcesDirSet = TRUE;
					}
				}
				elseif ( $i === 3 and $bit === 'Upgrade' )
				{
					$bit = mb_strtolower( $bit );
				}
				elseif( $sourcesDirSet === FALSE )
				{
					if( $i === 0 )
					{
						$path .= ROOT_PATH . '/system/';
					}
					elseif ( $i === 1 and $bit === 'Application' )
					{
						// do nothing
					}
					else
					{
						$path .= 'sources/';
					}
					$sourcesDirSet = TRUE;
				}

				$path .= "{$bit}/";
			}

			/* Load it */
			$path = substr( $path, 0, -1 ) . '.php';
			if( !file_exists( $path ) )
			{
				$path = substr( $path, 0, -4 ) . substr( $path, strrpos( $path, '/' ) );
				if ( !file_exists( $path ) )
				{
					return FALSE;
				}
			}
			require_once( $path );

			/* Is it an interface? */
			if ( interface_exists( "{$namespace}\\{$class}", FALSE ) )
			{
				return;
			}

			/* Is it a trait? */
			if ( trait_exists( "{$namespace}\\{$class}", FALSE ) )
			{
				return;
			}

			/* Is it an enumeration? */
			if ( function_exists( 'enum_exists' ) AND enum_exists( "{$namespace}\\{$class}", FALSE ) )
			{
				return;
			}

			/* Does it exist? */
			if ( class_exists( "{$namespace}\\{$class}", FALSE ) )
			{
				return;
			}

			/* Doesn't exist? */
			if( !class_exists( "{$namespace}\\{$class}", FALSE ) )
			{
				trigger_error( "Class {$classname} could not be loaded. Ensure it is in the correct namespace.", E_USER_ERROR );
			}
		}
	}

	/**
	 * Determine if a file is a part of a third party application or plugin
	 *
	 * @param	string	$path	The file path.
	 * @return	bool
	 */
	public static function isThirdParty( string $path ): bool
	{
		$bits = explode( '/', $path );

		if ( $bits[0] === 'applications' AND isset( $bits[1] ) AND in_array( $bits[1], static::$ipsApps ) )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @brief	$lastError Last error
	 */
	public static ?\Exception $lastError = NULL;

	/**
	 * Error Handler
	 *
	 * @param int $errno Error number
	 * @param string $errstr Error message
	 * @param string $errfile File
	 * @param int $errline Line
	 * @param null $trace Backtrace
	 * @return    void
	 * @throws ErrorException
	 */
	public static function errorHandler( $errno, $errstr, $errfile, $errline, $trace=NULL )
	{
		self::$lastError = new ErrorException( $errstr, $errno, 0, $errfile, $errline );

		/* We don't care about these in production */
		if ( in_array( $errno, array( E_WARNING, E_NOTICE, E_STRICT, E_DEPRECATED ) ) )
		{
			return;
		}

		/* This means the error suppressor was used, so we should ignore any non-fatal errors */
		if ( error_reporting() === 0 )
		{
			return false;
		}

		throw self::$lastError;
	}

	/**
	 * Exception Handler
	 *
	 * @param	Throwable	$exception	Exception class
	 * @return	void
	 */
	public static function exceptionHandler( $exception )
	{
		/* Should we show the exception message? */
		$showMessage = ( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation == 'admin' );
		if ( method_exists( $exception, 'isServerError' ) and $exception->isServerError() )
		{
			$showMessage = TRUE;
		}

		$log = static::getExceptionDetails( $exception );

		/* Log it (unless it's a MySQL server error) */
		if( ! ( $exception instanceof \IPS\Db\Exception and $exception->isServerError() ) )
		{
			Log::log( $log, 'uncaught_exception' );
		}

		/* Report it */
		try
		{
			if (
				!IN_DEV
				and REPORT_EXCEPTIONS === TRUE
				and Settings::i()->diagnostics_reporting
				and !Settings::i()->theme_designer_mode
				and !self::exceptionWasThrownByThirdParty( $exception )
				and ( !method_exists( $exception, 'isThirdPartyError' ) or !$exception->isThirdPartyError() )
				and ( !method_exists( $exception, 'isServerError' ) or !$exception->isServerError() )
				and !( $exception instanceof ParseError )
				)
			{
				self::reportExceptionToIPS( $exception );
			}
		}
		catch ( \Exception $e ) { }

		/* Try to display a friendly error page */
		try
		{
			/* If we couldn't connect to the database, don't bother trying to show the friendly page because nope */
			if( $exception instanceof \IPS\Db\Exception AND $exception->getCode() === 0 )
			{
				throw new RuntimeException;
			}

			/* If we're in the installer/upgrader, show the raw message */
			$message = 'generic_error';
			if( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation == 'setup' )
			{
				$message = $exception->getMessage();
			}

			$faultyAppId = static::exceptionWasThrownByThirdParty( $exception );

			/* Output */
			Output::i()->error( $message, "EX{$exception->getCode()}", 500, NULL, array(), $log, $faultyAppId );
		}
		/* And if *that* fails, show our generic page */
		catch ( \Exception $e )
		{
			static::genericExceptionPage( $showMessage ? $exception->getMessage() : NULL );
		}
		catch ( Throwable $e )
		{
			static::genericExceptionPage( $showMessage ? $exception->getMessage() : NULL );
		}

		exit;
	}

	/**
	 * Get exception details as a string, suitable to display in an error (to admins only)
	 *
	 * @param \Exception|\Error $exception	Exception or Error
	 * @return	string
	 */
	public static function getExceptionDetails( \Exception|\Error $exception ): string
	{
		/* Work out what we'll log - exception classes can provide extra data */
		$log = '';
		if ( method_exists( $exception, 'extraLogData' ) )
		{
			$log .= $exception->extraLogData() . "\n";
		}
		$log .= get_class( $exception ) . ": " . $exception->getMessage() . " (" . $exception->getCode() . ")\n" . $exception->getTraceAsString();

		return $log;
	}

	/**
	 * Should a given exception be reported to IPS? Filter out 3rd party etc.
	 *
	 * @param	Throwable	$exception	The exception
	 * @return	void
	 */
	final public static function reportExceptionToIPS( $exception )
	{
		$response = Url::external('https://invisionpowerdiagnostics.com')->request()->post( array(
			'version'	=> Application::getAvailableVersion('core'),
			'class'		=> get_class( $exception ),
			'message'	=> $exception->getMessage(),
			'code'		=> $exception->getCode(),
			'file'		=> str_replace( ROOT_PATH, '', $exception->getFile() ),
			'line'		=> $exception->getLine(),
			'backtrace'	=> str_replace( ROOT_PATH, '', $exception->getTraceAsString() )
		) );

		if ( $response->httpResponseCode == 410 )
		{
			Settings::i()->changeValues( array( 'diagnostics_reporting' => 0 ) );
		}
	}

	/**
	 * Generic exception page
	 *
	 * @param	string			$message	The error message
	 * @return	void
	 * @note	Abstracted so Theme can call this if templates are in the process of building
	 */
	public static function genericExceptionPage( $message = NULL )
	{
		if( isset( $_SERVER['SERVER_PROTOCOL'] ) and strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false )
		{
			header( "HTTP/1.0 500 Internal Server Error" );
		}
		else
		{
			header( "HTTP/1.1 500 Internal Server Error" );
		}

		/* Don't allow error pages to be cached */
		foreach(Output::getNoCacheHeaders() as $headerKey => $headerValue )
		{
			header( "{$headerKey}: {$headerValue}" );
		}

		require ROOT_PATH . '/' . ERROR_PAGE;
		exit;
	}

	/**
	 * Small utility function to check if a class has a trait as PHP doesn't have an operator
	 * for this and the monkey patching means we can't use class_uses() directly
	 *
	 * @param	string|object	$class 	The class
	 * @param	string			$trait	Trait name to look for
	 * @return	bool
	 */
	public static function classUsesTrait( $class, $trait )
	{
	    do
	    {
		    if ( in_array( $trait, class_uses( $class ) ) )
		    {
			    return TRUE;
		    }
	    }
	    while( $class = get_parent_class( $class ) );

	    return FALSE;
	}

	/**
	 * Get license key data
	 *
	 * @param	bool	$forceRefresh	If TRUE, will get data from server
	 * @return	array|NULL
	 */
	public static function licenseKey( $forceRefresh = FALSE )
	{
		return array(
			'key' => Settings::i()->ipb_reg_number,
			'url' => Settings::i()->base_url,
			'test_url' => Settings::i()->base_url,
			'active' => 1,
			'cloud' => FALSE,
			'expires' => "2065-01-01 23:23:23",
			'products' => array(
				'blog' => 1,
				'calendar' => 1,
				'cms' => 1,
				'core' => 1,
				'downloads' => 1,
				'forums' => 1,
				'gallery' => 1,
				'nexus' => 1,
				'spam' => 0
			),
			'support' => 'Standard',
			'is_5' => 1
		);

		/* Do we even have a license key? */
		if ( !Settings::i()->ipb_reg_number )
		{
			try
			{
				AdminNotification::send( 'core', 'License', 'missing', FALSE );
			}
			/* AdminCP Notifications table may not exist yet if upgrading */
			catch( \IPS\Db\Exception $e ) {}
			return NULL;
		}

		/* Get the cached value */
		$response = NULL;
		$cached = NULL;
		$setFetched = FALSE;
		if ( isset( Store::i()->license_data ) )
		{
			$cached = Store::i()->license_data;

			/* If it's younger than 21 days, just use that */
			if ( $cached['fetched'] > ( time() - 1814400 ) and !$forceRefresh )
			{
				/* If the license is not expired, return the data */
				if( empty( $cached['data']['expires'] ) OR !$cached['data']['expires'] OR strtotime( $cached['data']['expires'] ) > time() )
				{
					$response = $cached['data'] ?? [];
				}
				/* Otherwise if the license is expired but we've automatically refetched, return the data */
				else if( $cached['data']['expires'] AND strtotime( $cached['data']['expires'] ) < time() AND isset( $cached['refetched'] ) )
				{
					$response = $cached['data'] ?? [];
				}
				/* Otherwise remember to set the 'refetched' flag */
				else
				{
					$setFetched = TRUE;
				}
			}
		}

		/* If we can't use the cache, call the actual server */
		if ( $response === NULL )
		{
			try
			{
				/* Prevent a race condition and set the next check cycle to be 10 mins from the 21 day cut off in case this request fails */
				Store::i()->license_data	= array( 'fetched' => time() - 1813800, 'data' => $cached['data'] ?? NULL );

				/* Actually call the server */
				$response = Url::ips( 'license/' . trim( Settings::i()->ipb_reg_number ) )->request()->get();
				if ( $response->httpResponseCode == 404 )
				{
					Store::i()->license_data	= array( 'fetched' => time() - 1728000, 'data' => $cached['data'] ?? NULL );
					return $cached;
				}
				$response = $response->decodeJson();

				/* Update the license info in the store */
				$licenseData = array( 'fetched' => time(), 'data' => $response );
				if( $setFetched )
				{
					$licenseData['refetched']	= 1;
				}
				Store::i()->license_data	= $licenseData;

				/* Clear the data layer cache, the available events and properties could have changed so there could be issues due to partial events in the cache */
				if ( method_exists( DataLayer::i(), "clearCachedConfiguration" ) )
				{
					DataLayer::i()->clearCachedConfiguration();
				}
			}
			catch ( \Exception $e )
			{
				/* If we can't access the license server right now, store something in cache to prevent a request on every page load. We
					set fetched to 20 days ago so that this cache is only good for 1 day instead of 21 days however. */
				if( $cached === NULL )
				{
					Store::i()->license_data	= array( 'fetched' => time() - 1728000, 'data' => NULL );
				}
				else
				{
					/* We wipe the data to prevent a race condition, but the license server failed so restore the data and set to try again in 1 day */
					Store::i()->license_data	= array( 'fetched' => time() - 1728000, 'data' => ( isset( $cached['data'] ) ? $cached['data'] : NULL ) );
				}

				/* If the server is offline right now, use the cached value from above */
				$response = $cached;
			}
		}

		/* Check it's good */
		if ( !empty( $response['legacy'] ) )
		{
			try
			{
				AdminNotification::send( 'core', 'License', 'missing', FALSE );
			}
			/* AdminCP Notifications table may not exist yet if upgrading */
			catch( \IPS\Db\Exception $e ) {}
			$response = NULL;
		}
		elseif( isset( $response['expires'] ) AND isset( $response['active'] ) )
		{
			try
			{
				AdminNotification::remove( 'core', 'License', 'missing' );

				if ( !CIC)
				{
					/* Check it hasn't expired */
					if ( strtotime( $response['expires'] ) < time() or !$response['active'] )
					{
						AdminNotification::send( 'core', 'License', 'expired', FALSE );
						AdminNotification::remove( 'core', 'License', 'expireSoon' );
					}
					else
					{
						AdminNotification::remove( 'core', 'License', 'expired' );

						/* Or there's 7 days or less to go */
						$daysLeft = (int) (new DateTime)->diff( DateTime::ts( strtotime( $response['expires'] ), TRUE ) )->format('%r%a');
						if( $daysLeft < 0 )
						{
							$daysLeft = 0;
						}
						if ( $daysLeft <= 7 )
						{
							AdminNotification::send( 'core', 'License', 'expireSoon', FALSE );
						}
						else
						{
							AdminNotification::remove( 'core', 'License', 'expireSoon' );
						}
					}
				}

				/* Check the URL is correct */
				$doUrlCheck = TRUE;
				$parsed = parse_url( Settings::i()->base_url );
				if ( CIC or $parsed['host'] === 'localhost' or mb_substr( $parsed['host'], -4 ) === '.dev' or mb_substr( $parsed['host'], -5 ) === '.test' )
				{
					$doUrlCheck = FALSE;
				}
				if ( $doUrlCheck )
				{
					/* Normalize our URL's. Specifically ignore the www. subdomain. */
					$validUrls		= array();
					if ( ! empty( $response['url'] ) )
					{
						$validUrls[] = rtrim( str_replace( ['http://', 'https://', 'www.'], '', $response['url'] ), '/' );
					}
					if( !empty( $response['test_url'] ) )
					{
						$validUrls[] = rtrim( str_replace (array( 'http://', 'https://', 'www.' ), '', $response['test_url'] ), '/' );
					}
					$ourUrl			= rtrim( str_replace( array( 'http://', 'https://', 'www.' ), '', Settings::i()->base_url ), '/' );

					if ( !in_array( $ourUrl, $validUrls ) )
					{
						AdminNotification::send( 'core', 'License', 'url', FALSE );
					}
					else
					{
						AdminNotification::remove( 'core', 'License', 'url' );
					}
				}
				else
				{
					AdminNotification::remove( 'core', 'License', 'url' );
				}
			}
			/* AdminCP Notifications table may not exist yet if upgrading */
			catch( \IPS\Db\Exception $e ) {}
		}

		/* Return */
		return $response;
	}

	/**
	 * Check license key
	 *
	 * @param	string	$val	The license key
	 * @param	string	$url	The site URL
	 * @return	void
	 * @throws	DomainException
	 */
	public static function checkLicenseKey( $val, $url )
	{
		// For what? :)
		return;

		$test = FALSE;
		if ( mb_substr( $val, -12 ) === '-TESTINSTALL' )
		{
			$test = TRUE;
			$val = mb_substr( $val, 0, -12 );
		}
		$urlKey = $test ? 'test_url' : 'url';

		try
		{
			$response = Url::ips( 'license/' . $val )->setQueryString( $urlKey, $url )->request()->get();
			switch ( $response->httpResponseCode )
			{
				case 200:
					$response = json_decode( $response, TRUE );
					if ( $response['legacy'] )
					{
						throw new DomainException( 'license_key_legacy' );
					}

					if ( !$response[ $urlKey ] )
					{
						Url::ips( 'license/' . $val )->request()->post( array(
							$urlKey	=> $url
						) );
					}
					elseif ( $response[ $urlKey ] != $url )
					{
						if ( rtrim( preg_replace( '/^https?:\/\//', '', $response[ $urlKey ] ), '/' ) == rtrim( preg_replace( '/^https?:\/\//', '', $url ), '/' ) ) // Allow changing if the difference is http/https or just a trailing slash
						{
							Url::ips( 'license/' . $val )->request()->post( array(
								$urlKey	=> $url
							) );
						}
						else
						{
							throw new DomainException( $test ? 'license_key_test_active' : 'license_key_active' );
						}
					}
					break;

				case 404:
					throw new DomainException( 'license_key_not_found' );

				default:
					throw new DomainException( 'license_generic_error' );
			}
		}
		catch ( Exception $e )
		{
			throw new DomainException( sprintf( Member::loggedIn()->language()->get( 'license_server_error' ), $e->getMessage() ) );
		}
	}

	/**
	 * Was the exception thrown by a third party app/plugin?
	 *
	 * @param	Throwable			$exception	The exception
	 * @return	string|NULL		string = application directory
	 */
	public static function exceptionWasThrownByThirdParty( $exception )
	{
		$trace = $exception->getTraceAsString();

		/* Did this happen in a custom app? */
		foreach ( explode( "\n", $trace ) as $line )
		{
			if ( preg_match( '/' . preg_quote( DIRECTORY_SEPARATOR, '/' ) . 'applications' . preg_quote( DIRECTORY_SEPARATOR, '/' ) . '([a-zA-Z]+)/', str_replace( ROOT_PATH, '', $line ), $matches ) )
			{
				if ( !in_array( $matches[1], IPS::$ipsApps ) )
				{
					return $matches[1];
				}
				else
				{
					return NULL;
				}
			}
		}

		/* Still here? Probably system */
		return NULL;
	}

	/* !CiC Wrapper Methods */
	/* The \IPS\Cicloud class only exists on Community in the Cloud, so these methods allow us to call those methods without erroring if the class doesn't exist */

	/**
	 * Resync IPS Cloud File System
	 * Must be called when writing any files to disk on IPS Community in the Cloud
	 *
	 * @param	string	$reason	Reason
	 * @return	void
	 */
	final public static function resyncIPSCloud( $reason = NULL )
	{
		if ( CIC AND function_exists( 'IPS\Cicloud\resyncIPSCloud' ) )
		{
			resyncIPSCloud( $reason );
		}
	}

	/**
	 * Send IPS Cloud applylatestfiles command
	 * Fallback for autoupgrade failures
	 *
	 * @param	int		$version		If we're upgrading, the current version we are running.
	 * @return	void
	 */
	final public static function applyLatestFilesIPSCloud( ?int $version = NULL )
	{
		if ( CIC AND function_exists( 'IPS\Cicloud\applyLatestFilesIPSCloud' ) )
		{
			applyLatestFilesIPSCloud( $version );
		}
	}

	/**
	 * Get CiCloud User
	 *
	 * @return	string|NULL
	 */
	final public static function getCicUsername(): ?string
	{
		if ( CIC AND function_exists( 'IPS\Cicloud\getCicUsername' ) )
		{
			return \IPS\Cicloud\getCicUsername();
		}
		return NULL;
	}

	/**
	 * Unpack the special IPS_CLOUD_CONFIG environment variable
	 *
	 * @return void
	 */
	final public static function unpackCicConfig()
	{
		/* This method is slightly different because it needs to run before anything else to establish our Cicloud configuration */
		if ( function_exists( 'IPS\Cicloud\unpackCicConfig' ) )
		{
			unpackCicConfig();
		}
	}

	/**
	 * Compile Redis Config
	 *
	 * @return	array
	 */
	final public static function compileCicRedisConfig()
	{
		if ( function_exists( 'IPS\Cicloud\compileRedisConfig' ) )
		{
			return compileRedisConfig();
		}

		return array();
	}

	/**
	 * Can Manage Resources?
	 *
	 * @return	bool
	 */
	final public static function canManageResources(): bool
	{
		if ( function_exists( 'IPS\Cicloud\canManageResources' ) )
		{
			return canManageResources();
		}

		return TRUE;
	}

	/**
	 * Check third party
	 *
	 * @return	bool
	 */
	final public static function checkThirdParty( bool $upgrade=FALSE ): bool
	{
		if ( function_exists( 'IPS\Cicloud\checkThirdParty' ) )
		{
			return checkThirdParty( $upgrade );
		}

		return TRUE;
	}

	/**
	 * Is Managed
	 *
	 * @return	bool
	 */
	final public static function isManaged(): bool
	{
		if ( function_exists( 'IPS\Cicloud\isManaged' ) )
		{
			return isManaged();
		}

		return FALSE;
	}

	/**
	 * Multi-bte UTF-8 aware ucfirst
	 *
	 * @return string
	 */
	final public static function mb_ucfirst(): string
	{
		$text = \func_get_arg( 0 );
		return ( $text ) ? mb_strtoupper( mb_substr( $text, 0, 1 ) ) . mb_substr( $text, 1 ) : "";
	}
}

/* Init */
IPS::init();