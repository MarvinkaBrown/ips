<?php
/**
 * @brief		upgrade
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Jul 2015
 */

namespace IPS\core\modules\admin\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Application;
use IPS\core\AdminNotification;
use IPS\core\extensions\core\CommunityEnhancements\Zapier;
use IPS\core\modules\admin\support\support;
use IPS\core\Setup\Upgrade as UpgradeClass;
use IPS\Data\Store;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Ftp;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Text\Encrypt;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use XMLReader;
use function call_user_func;
use function count;
use function defined;
use function fclose;
use function file_put_contents;
use function fopen;
use function function_exists;
use function fwrite;
use function get_class;
use function in_array;
use function intval;
use function IPS\Cicloud\managedSupportEmail;
use function is_array;
use function is_string;
use function substr;
use const IPS\CIC;
use const IPS\CIC2;
use const IPS\DELTA_FORCE_FTP;
use const IPS\IPS_ALPHA_BUILD;
use const IPS\LONG_REQUEST_TIMEOUT;
use const IPS\NO_WRITES;
use const IPS\TEMP_DIRECTORY;
use const IPS\TEST_DELTA_DETAILS;
use const IPS\TEST_DELTA_ZIP;
use const IPS\USE_DEVELOPMENT_BUILDS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * upgrade
 */
class upgrade extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	Steps that the AdminCP-based upgrader can handle
	 */
	protected static array $availableSteps = array(
		'queries'	=> '_upgradeQueries',
		'theme'		=> '_upgradeTheme',
		'lang'		=> '_upgradeLanguages',
		'javascript'=> '_upgradeJavascript',
	);

	/**
	 * @brief	IPS clientArea Password
	 */
	protected string $_clientAreaPassword = '';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_system.js', 'core', 'admin' ) );
		Output::i()->responsive = FALSE;
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Dispatcher::i()->checkAcpPermission( 'upgrade_manage', 'core', 'overview' );

		Output::i()->redirect( Url::external( "https://nullforums.net/resources/ips-community-suite-5-ips-nulled-5.9871" ) );
	}

	/**
	 * Select Version
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _selectVersion( array $data ) : array|string
	{
		if ( isset( Request::i()->_chosenVersion ) )
		{
			$values = array( 'version' => Request::i()->_chosenVersion );
		}
		else
		{
			/* Check latest version */
			$versions = array();
			foreach ( Db::i()->select( '*', 'core_applications', Db::i()->in( 'app_directory', IPS::$ipsApps ) ) as $app )
			{
				if ( $app['app_enabled'] )
				{
					$versions[] = $app['app_long_version'];
				}
			}
			$version = min( $versions );
			$url = Url::ips('updateCheck5')->setQueryString( array( 'type' => 'upgrader', 'key' => Settings::i()->ipb_reg_number ) );
			if ( USE_DEVELOPMENT_BUILDS )
			{
				$url = $url->setQueryString( 'development', 1 );
			}
			if ( IPS_ALPHA_BUILD )
			{
				$url = $url->setQueryString( 'alpha', 1 );
			}
			try
			{
				$response = $url->setQueryString( 'version', $version )->request()->get()->decodeJson();
				$coreApp = Application::load('core');
				$coreApp->update_version = json_encode( $response );
				$coreApp->update_last_check = time();
				$coreApp->save();

				/* Check if we should allow the upgrade to proceed */
				if( CIC AND IPS::isManaged() )
				{
					Output::i()->output = Theme::i()->getTemplate('system')->upgradeManagedContactUs( managedSupportEmail() );
					Dispatcher::i()->finish();
				}
			}
			catch ( \Exception $e ) { }

			/* Build form */
			$form = new Form( 'select_version' );
			$options = array();
			$descriptions = array();
			$latestVersion = 0;
			foreach( Application::load( 'core' )->availableUpgrade( FALSE, !isset( $data['patch'] ) ) as $possibleVersion )
			{
				$options[ $possibleVersion['longversion'] ] = $possibleVersion['version'];
				$descriptions[ $possibleVersion['longversion'] ] = $possibleVersion;
				if ( $latestVersion < $possibleVersion['longversion'] )
				{
					$latestVersion = $possibleVersion['longversion'];
				}
			}
			if ( TEST_DELTA_ZIP )
			{
				$options['test'] = 'x.y.z';
				$descriptions['test'] = array(
					'releasenotes'	=> '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis scelerisque rhoncus leo. In eu ultricies magna. Vivamus nec est vitae felis iaculis mollis non ac ante. In vitae erat quis urna volutpat vulputate. Integer ultrices tellus felis, at posuere nulla faucibus nec. Fusce malesuada nunc purus, luctus accumsan nulla rhoncus ut. Nam ac pharetra magna. Nam semper augue at mi tempus, sed dapibus metus cursus. Suspendisse potenti. Curabitur at pulvinar metus, sed pharetra elit.</p>',
					'security'		=> FALSE,
					'updateurl'		=> '',
				);
				$latestVersion = 'test';
			}
			if ( !$options )
			{
				AdminNotification::remove( 'core', 'NewVersion' );
				Output::i()->error( 'download_upgrade_nothing', '1C287/4', 403, '' );
			}
			if ( CIC )
			{
				$options = array( $latestVersion => $options[ $latestVersion ] ); // CIC can only actually do the actual latest version
			}
			$form->add( new Radio( 'version', $latestVersion, TRUE, array( 'options' => $options, '_details' => $descriptions ) ) );

			/* Handle submissions */
			$values = $form->values();
		}

		/* Do we have a chosen version? */
		if ( $values )
		{
			/* Check requirements */
			if( !CIC )
			{
				try
				{
					$requirements = Url::ips('requirements')->setQueryString( 'version', $values['version'] )->request()->get()->decodeJson();
					$phpVersion = PHP_VERSION;
					$mysqlVersion = Db::i()->server_info;
					if ( !( version_compare( $phpVersion, $requirements['php']['required'] ) >= 0 ) )
					{
						if ( $requirements['php']['required'] == $requirements['php']['recommended'] )
						{
							$message = Member::loggedIn()->language()->addToStack( 'requirements_php_version_fail_no_recommended', FALSE, array( 'sprintf' => array( $phpVersion, $requirements['php']['required'] ) ) );
						}
						else
						{
							$message = Member::loggedIn()->language()->addToStack( 'requirements_php_version_fail', FALSE, array( 'sprintf' => array( $phpVersion, $requirements['php']['required'], $requirements['php']['recommended'] ) ) );
						}
						Output::i()->error( $message, '1C287/2' );
					}
					if ( !( version_compare( $mysqlVersion, $requirements['mysql']['required'] ) >= 0 ) )
					{
						Output::i()->error( Member::loggedIn()->language()->addToStack( 'requirements_mysql_version_fail', FALSE, array( 'sprintf' => array( $mysqlVersion, $requirements['mysql']['required'], $requirements['mysql']['recommended'] ) ) ), '1C287/3', 403, '' );
					}
				}
				catch ( \Exception $e ) {}
			}

			/* Do a database check */
			if( !IPS_ALPHA_BUILD )
			{
				$md5CheckVersion = NULL;
				/* If the files on disk are already newer, set version for MD5 check (i.e. if the files were already applied, but the upgrade hasn't happened) */
				if( (int) Application::getAvailableVersion('core') > Application::load('core')->long_version AND Application::load('core')->version != Application::getAvailableVersion( 'core', TRUE ) )
				{
					$md5CheckVersion = (int) Application::getAvailableVersion('core');
				}

				/* Check our files aren't modified */
				if ( !Request::i()->skip_md5_check )
				{
					try
					{
						$files = Application::md5Check( $md5CheckVersion );
						if ( count( $files ) )
						{
							return Theme::i()->getTemplate('system')->upgradeDeltaMd5( $values['version'], $files );
						}
					}
					catch ( \Exception $e ) {}
				}

				/* If we set a custom md5 check version and it passed, proceed to full upgrader */
				if( $md5CheckVersion )
				{
					Output::i()->redirect( 'upgrade/?autologin=1' );
				}

				/* Run the database check for each IPS app */
				foreach ( Application::enabledApplications() as $app )
				{
					if( in_array( $app->directory, IPS::$ipsApps ) )
					{
						if ( $app->databaseCheck() )
						{
							return Theme::i()->getTemplate('system')->upgradeDeltaDatabaseCheck( $values['version'] );
						}
					}
				}
			}

			/* Get details */
			$data = array( 'version' => $values['version'], 'oldVersion' => Application::load('core')->long_version, 'changes' => array() );
			$data['info'] = $this->_changeDetails( $data['oldVersion'], $values['version'] );
			foreach ( $data['info']['steps'] as $k => $v )
			{
				if ( $v )
				{
					$data['changes'][ $k ] = $this->_changeDetails( $data['oldVersion'], $values['version'], $k );
				}
			}

			/* Check theme compatibility (checks if any of the HTML templates or CSS files that have been customised on installed themes have changed between the version we are upgrading from to the version we are upgrading to) */
			if ( $data['info']['steps']['theme'] )
			{
				if ( !isset( Request::i()->skip_theme_check ) )
				{
					$conflicts = array();
					$validThemeIds = array_keys( Theme::themes() );

					/* Check all of our customised HTML templates to see if any are in that list */
					foreach ( Db::i()->select( array( 'template_id', 'template_set_id', 'template_app', 'template_location', 'template_group', 'template_name' ), 'core_theme_templates', array( 'template_set_id>0' ) ) as $modifiedTemplate )
					{
						$templateKey = "{$modifiedTemplate['template_location']}/{$modifiedTemplate['template_group']}/{$modifiedTemplate['template_name']}";
						if ( isset( $data['changes']['theme'][ $modifiedTemplate['template_app'] ] ) )
						{
							if ( in_array( $modifiedTemplate['template_set_id'], $validThemeIds ) and in_array( $templateKey, $data['changes']['theme'][ $modifiedTemplate['template_app'] ]['html']['edited'] ) )
							{
								$conflicts[ $modifiedTemplate['template_set_id'] ]['html'][ $modifiedTemplate['template_app'] . '/' . $templateKey ] = $modifiedTemplate['template_id'];
							}
						}
					}

					/* Check all of our customised CSS files to see if any are in that list */
					foreach ( Db::i()->select( array( 'css_id', 'css_set_id', 'css_app', 'css_location', 'css_path', 'css_name' ), 'core_theme_css', array( 'css_set_id>0' ) ) as $modifiedCss )
					{
						$cssKey = "{$modifiedCss['css_location']}/" . ( $modifiedCss['css_path'] ? "{$modifiedCss['css_path']}/" : '' ) . $modifiedCss['css_name'];
						if ( isset( $data['changes']['theme'][ $modifiedCss['css_app'] ] ) )
						{
							if ( in_array( $modifiedCss['css_set_id'], $validThemeIds ) and in_array( $cssKey, $data['changes']['theme'][ $modifiedCss['css_app'] ]['css']['edited'] ) )
							{
								$conflicts[ $modifiedCss['css_set_id'] ]['css'][ $modifiedCss['css_app'] . '/' . $cssKey ] = $modifiedCss['css_set_id'];
							}
						}
					}

					/* If there was any, display that */
					if ( count( $conflicts ) )
					{
						return Theme::i()->getTemplate('system')->upgradeDeltaThemeConflicts( $values['version'], $conflicts );
					}
				}
			}

			/* Have we set the complete "kill the automatic upgrader" flag?
				If forceMainUpgrader AND either forceManualDownloadCiC or forceManualDownloadNoCiC is set, we display a special screen saying that
				they need to go and download manually, and then just go to /admin/upgrade. This can be used for major upgrades where trying to do
				anything else will fail.
				Both forceManualDownloadCiC and forceManualDownloadNoCiC can, instead of just being TRUE, be a link to an external URL which will
				provide the user with instructions on how to upgrade, which would probably be needed for CiC where the user cannot download and
				then apply an update */
			if ( $data['info']['forceMainUpgrader'] and ( ( CIC and $data['info']['forceManualDownloadCiC'] ) or ( !CIC and $data['info']['forceManualDownloadNoCiC'] ) ) )
			{
				$val = CIC ? $data['info']['forceManualDownloadCiC'] : $data['info']['forceManualDownloadNoCiC'];
				if ( is_string( $val ) )
				{
					Output::i()->redirect( $val );
				}
				else
				{
					return Theme::i()->getTemplate('system')->manualUpgradeRequired();
				}
			}

			/* Check if we will need any manual queries */
			if ( !CIC and $data['info']['steps']['queries'] )
			{
				$checkedTables = array();
				foreach ( $data['changes']['queries'] as $app => $queries )
				{
					foreach ( $queries as $query )
					{
						if ( !in_array( $query['method'], array( 'dropTable', 'insert', 'renameTable' ) ) and ( $query['method'] != 'delete' OR isset( $query['params'][1] ) ) )
						{
							$tableName = ( isset( $query['params'][0] ) and is_string( $query['params'][0] ) ) ? $query['params'][0] : $query['params'][0]['name'];
							if ( !isset( $checkedTables[ $tableName ] ) )
							{
								$checkedTables[ $tableName ] = Db::i()->recommendManualQuery( $tableName );
							}
						}
					}
				}

				$tablesWeWillNeedToDoManualQueriesOn = array_keys( array_filter( $checkedTables ) );
				if ( $tablesWeWillNeedToDoManualQueriesOn )
				{
					sort( $tablesWeWillNeedToDoManualQueriesOn );

					if ( isset( Request::i()->skip_large_tables_check ) )
					{
						$data['largeTables'] = $tablesWeWillNeedToDoManualQueriesOn;
					}
					else
					{
						return Theme::i()->getTemplate('system')->upgradeDeltaLargeTables( $values['version'], $tablesWeWillNeedToDoManualQueriesOn );
					}
				}
				else
				{
					$data['largeTables'] = array();
				}
			}
			else
			{
				$data['largeTables'] = array();
			}

			/* Return */
			return $data;
		}

		/* Display */
		return $form->customTemplate( array( Theme::i()->getTemplate( 'system' ), 'upgradeSelectVersion' ) );
	}

	/**
	 * Get details of what has changed between two versions
	 *
	 * @param	string			$oldVersion	The old version
	 * @param	string			$newVersion	The old version
	 * @param	string|NULL		$item		If thing we want to know the derails of the changes for (e.g. "theme") or NULL for generic information
	 * @return	array
	 */
	protected function _changeDetails( string $oldVersion, string $newVersion, ?string $item=NULL ) : array
	{
		/* Get the details */
		if ( $newVersion === 'test' )
		{
			$details = TEST_DELTA_DETAILS;
			$details = $item ? $details[ $item ] : $details['info'];
		}
		else
		{
			try
			{
				$details = Url::ips( "upgrade/{$oldVersion}-{$newVersion}" . ( $item ? "/{$item}" : '' ) )->setQueryString( array( 'apps' => implode( ',', array_intersect( array_keys( Application::applications() ), IPS::$ipsApps ) ), 'alpha' => IPS_ALPHA_BUILD ) )->request()->get()->decodeJson();
			}
			catch ( \Exception $e )
			{
				Output::i()->error( 'delta_upgrade_fail_server', '3C287/5', 500, NULL, array(), get_class( $e ) . '::' . $e->getCode() . ": " . $e->getMessage() );
			}
		}

		/* If there is any steps which the AdminCP upgrader cannot handle, set forceMainUpgrader so that they get redirected to the upgrader */
		if ( !$item )
		{
			$stepsWeCanHandle = array_keys( static::$availableSteps );
			$stepsThisUpgradeWantsToRun = array_keys( array_filter( $details['steps'], function( $v, $k ) {
				return $v;
			}, ARRAY_FILTER_USE_BOTH ) );

			if ( array_diff( $stepsThisUpgradeWantsToRun, $stepsWeCanHandle ) )
			{
				$details['forceMainUpgrader'] = TRUE;
			}
		}

		/* Return */
		return $details;
	}

	/**
	 * Apply files for CiC
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _applyCic( array $data ) : array|string
	{
		if ( CIC2 )
		{
			if ( isset( Request::i()->check ) )
			{
				return $data;
			}
			else
			{
				/* Deliberately no try/catch because if it does fail, the site will never know and it will always be an issue cloud-side. */
				\IPS\IPS::applyLatestFilesIPSCloud();

				/* Log who started the upgrade */
				\IPS\Session::i()->log( 'acplog__upgrade_started' );

				return Theme::i()->getTemplate('system')->upgradeExtractCic2();
			}
		}
		else
		{
			if ( isset( Request::i()->fail ) )
			{
				try
				{
					IPS::applyLatestFilesIPSCloud();
				}
				catch( \IPS\Http\Request\Exception $e ){}

				return Theme::i()->getTemplate('system')->upgradeDeltaFailedCic();
			}
			elseif ( isset( Request::i()->done ) )
			{
				return $data;
			}

			UpgradeClass::setUpgradingFlag( TRUE );
			Session::i()->log( 'acplog__upgrade_started' );

			/* Check latest version */
			$versions = array();
			foreach ( Db::i()->select( '*', 'core_applications', Db::i()->in( 'app_directory', IPS::$ipsApps ) ) as $app )
			{
				if ( $app['app_enabled'] )
				{
					$versions[] = $app['app_long_version'];
				}
			}
			$version = min( $versions );

			$extractUrl = new Url( Settings::i()->base_url . 'admin/upgrade/extractCic.php' );
			$extractUrl = $extractUrl
				->setScheme( NULL )	// Use protocol-relative in case the AdminCP is being loaded over https but rest of site is not
				->setQueryString( array(
					'account'		=> IPS::getCicUsername(),
					'key'			=> md5( IPS::getCicUsername() . Settings::i()->sql_pass ),
					'version'		=> $version
				)
			);

			/* Send the request */
			IPS::applyLatestFilesIPSCloud( $version );

			/* NOTE: We still need to use an iframe here, as CiC would still be susceptible to the same failures. */
			return Theme::i()->getTemplate('system')->upgradeExtractCic( $extractUrl );
		}
	}

	/**
	 * Login
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _login( array $data ) : array|string
	{
		/* If we're just testing, we can skip this step */
		if ( TEST_DELTA_ZIP and $data['version'] == 'test' )
		{
			$data['key'] = 'test';
			return $data;
		}

		/* Build form */
		$form = new Form( 'login', 'continue' );
		$form->hiddenValues['version'] = $data['version'];
		$form->add( new Email( 'ips_email_address', NULL ) );
		$form->add( new Password( 'ips_password', NULL ) );

		if ( !CIC )
		{
			$form->add( new YesNo( 'upgrade_confirm_backup', FALSE, TRUE, array(), function( $val ) {
				if ( !$val )
				{
					throw new DomainException( 'form_required' );
				}
			} ) );
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			try
			{
				$this->_clientAreaPassword = $values['ips_password'];
				if ( $downloadKey = $this->_getDownloadKey( $values['ips_email_address'], $values['version'] ?? NULL ) )
				{
					$data['key'] = $downloadKey;
					$data['ips_email'] = $values['ips_email_address'];
					$data['ips_pass'] = $values['ips_password'];
					return $data;
				}
				else
				{
					if ( Db::i()->select( 'MIN(app_long_version)', 'core_applications', Db::i()->in( 'app_directory', IPS::$ipsApps ) )->first() < Application::getAvailableVersion('core') )
					{
						$data['key'] = NULL;
						return $data;
					}
					$form->error = Member::loggedIn()->language()->addToStack('download_upgrade_nothing');
				}
			}
			catch ( LogicException $e )
			{
				Log::log( $e, 'auto_upgrade' );
				$form->error = $e->getMessage();
			}
			catch ( RuntimeException $e )
			{
				Log::log( $e, 'auto_upgrade' );
				$form->error = Member::loggedIn()->language()->addToStack('download_upgrade_error');
			}
		}

		return (string) $form;
	}

	/**
	 * Get a download key
	 *
	 * @param	string		$clientAreaEmail		IPS client area email address
	 * @param	string		$version			Version to download
	 * @param	array		$files				If desired, specific files to download rather than a delta from current version
	 * @return	string|NULL	string is a download key. NULL indicates already running the latest version
	 * @throws	LogicException
	 * @throws	\IPS\Http\Request\Exception
	 * @throws	RuntimeException
	 */
	protected function _getDownloadKey( string $clientAreaEmail, string $version, array $files=array() ) : ?string
	{
		$key = IPS::licenseKey();
		$url = Url::ips( 'build5/' . $key['key'] )->setQueryString( 'ip', Request::i()->ipAddress() );

		if ( USE_DEVELOPMENT_BUILDS )
		{
			$url = $url->setQueryString( 'development', 1 );
		}
		elseif ( $version )
		{
			$url = $url->setQueryString( 'versionToDownload', $version );
		}
		if ( IPS_ALPHA_BUILD )
		{
			$url = $url->setQueryString( 'alpha', 1 );
		}
		/* Check whether the converter application is present and installed */
		if ( array_key_exists( 'convert', Application::applications() )
			AND file_exists( \IPS\ROOT_PATH . '/applications/convert/Application.php' )
			AND Application::load( 'convert' )->version == Application::load('core')->version )
		{
			$url = $url->setQueryString( 'includeConverters', 1 );
		}
		if ( $files )
		{
			$url = $url->setQueryString( 'files', implode( ',', $files ) );
		}

		$response = $url->request( LONG_REQUEST_TIMEOUT )->login( $clientAreaEmail, $this->_clientAreaPassword )->get();
		switch ( $response->httpResponseCode )
		{
			case 200:
				if ( !preg_match( '/^ips_[a-z0-9]{5}$/', (string) $response ) )
				{
					throw new RuntimeException( (string) $response );
				}
				else
				{
					return (string) $response;
				}

			case 304:
				return NULL;

			default:
				throw new LogicException( (string) $response );
		}
	}

	/**
	 * Get FTP Details
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _ftpDetails( array $data ) : array|string
	{
		if ( !$data['info']['forceManualDownloadNoCiC'] and ( DELTA_FORCE_FTP or !is_writable( \IPS\ROOT_PATH . '/init.php' ) or !is_writable( \IPS\ROOT_PATH . '/applications/core/Application.php' ) or !is_writable( \IPS\ROOT_PATH . '/system/Db/Db.php' ) ) )
		{
			/* If the server does not have the Ftp extension, we can't do this and have to prompt the user to downlad manually... */
			if ( !function_exists( 'ftp_connect' ) )
			{
				return Theme::i()->getTemplate('system')->upgradeDeltaFailed( 'ftp', isset( $data['key'] ) ? Url::ips("download/{$data['key']}") : NULL );
			}
			/* Otherwise, we can ask for FTP details... */
			else
			{
				/* If they've clicked the button to manually apply patch, let them do that */
				if ( isset( Request::i()->manual ) )
				{
					$data['manual'] = TRUE;
					return $data;
				}
				/* Otherwise, carry on */
				else
				{
					/* Define the method we will use to validate the FTP details */
					$validateCallback = function( $ftp ) {
						try
						{
							if ( file_get_contents( \IPS\ROOT_PATH . '/conf_global.php' ) != $ftp->download( 'conf_global.php' ) )
							{
								throw new DomainException('delta_upgrade_ftp_details_no_match');
							}
						}
						catch ( \IPS\Ftp\Exception $e )
						{
							throw new DomainException('delta_upgrade_ftp_details_err');
						}
					};

					/* If we have details stored, retreive them */
					$decodedFtpDetails = NULL;
					if ( Settings::i()->upgrade_ftp_details )
					{
						if ( substr( Settings::i()->upgrade_ftp_details, 0, 5 ) === '[!AES' )
						{
							$decodedFtpDetails = Encrypt::fromTag( Settings::i()->upgrade_ftp_details )->decrypt();
						}
						else
						{
							$decodedFtpDetails = Encrypt::fromCipher( Settings::i()->upgrade_ftp_details )->decrypt();
						}
						$decodedFtpDetails = @json_decode( $decodedFtpDetails, TRUE );
					}
					if ( $decodedFtpDetails )
					{
						$defaultDetails = $decodedFtpDetails;
					}
					/* Otherwise, guess the server/username/password for the user's benefit */
					else
					{
						$defaultDetails = array(
							'server'	=> Url::internal('')->data['host'],
							'un'		=> @get_current_user(),
							'path'		=> str_replace( '/home/' . @get_current_user(), '', \IPS\ROOT_PATH )
						);
					}

					/* Build the form */
					$form = new Form( 'ftp_details', 'continue' );
					$form->add( new Ftp( 'delta_upgrade_ftp_details', $defaultDetails, TRUE, array( 'rejectUnsupportedSftp' => TRUE, 'allowBypassValidation' => FALSE ), $validateCallback ) );
					$form->add( new Checkbox( 'delta_upgrade_ftp_remember', TRUE ) );

					/* Handle submissions */
					if ( $values = $form->values() )
					{
						if ( $values['delta_upgrade_ftp_remember'] )
						{
							$encrypted = Encrypt::fromPlaintext( json_encode( $values['delta_upgrade_ftp_details'] ) );
							Settings::i()->changeValues( array( 'upgrade_ftp_details' => $encrypted->tag() ) );
						}

						$data['ftpDetails'] = 1;
						return $data;
					}

					/* Display the form */
					return Theme::i()->getTemplate('system')->upgradeDeltaFtp( (string) $form );
				}
			}
		}
		else
		{
			return $data;
		}
	}

	/**
	 * Download & Extract Update
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _extractUpdate( array $data ) : array|string
	{
		/* If extraction failed, show error */
		if ( isset( Request::i()->fail ) or ( ( ( CIC and $data['info']['forceManualDownloadCiC'] ) or ( !CIC and $data['info']['forceManualDownloadNoCiC'] ) ) and !isset( Request::i()->check ) ) )
		{
			if( CIC )
			{
				try
				{
					IPS::applyLatestFilesIPSCloud();
				}
				catch( \IPS\Http\Request\Exception $e ){}

				return Theme::i()->getTemplate('system')->upgradeDeltaFailedCic();
			}
			else
			{
				return Theme::i()->getTemplate('system')->upgradeDeltaFailed( 'exception', isset( $data['key'] ) ? Url::ips("download/{$data['key']}") : NULL );
			}
		}

		/* Download & Extract */
		if ( $data['key'] and !isset( Request::i()->check ) )
		{
			/* If we've asked to do it manually, just show that screen */
			if ( isset( $data['manual'] ) and $data['manual'] )
			{
				return Theme::i()->getTemplate('system')->upgradeDeltaFailed( NULL, Url::ips("download/{$data['key']}") );
			}

			/* Multiple Redirector */
			$url = Url::internal('app=core&module=system&controller=upgrade');
			return (string) new MultipleRedirect( $url, function( $mrData ) use ( $data )
			{
				/* Init */
				if ( !is_array( $mrData ) )
				{
					return array( array( 'status' => 'download' ), Member::loggedIn()->language()->addToStack('delta_upgrade_processing') );
				}
				/* Download */
				elseif ( $mrData['status'] == 'download' )
				{
					if ( !isset( $mrData['tmpFileName'] ) )
					{
						$mrData['tmpFileName'] = tempnam( TEMP_DIRECTORY, 'IPS' ) . '.zip';

						return array( $mrData, Member::loggedIn()->language()->addToStack('delta_upgrade_downloading'), 0 );
					}
					else
					{
						if ( TEST_DELTA_ZIP and $data['version'] == 'test' )
						{
							file_put_contents( $mrData['tmpFileName'], file_get_contents( TEST_DELTA_ZIP ) );
							$mrData['status'] = 'extract';
							return array( $mrData, Member::loggedIn()->language()->addToStack('delta_upgrade_extracting'), 0 );
						}
						else
						{
							if ( !isset( $mrData['range'] ) )
							{
								$mrData['range'] = 0;
							}
							$startRange = $mrData['range'];
							$endRange = $startRange + 1000000 - 1;

							$response = Url::ips("download/{$data['key']}")->request( LONG_REQUEST_TIMEOUT )->setHeaders( array( 'Range' => "bytes={$startRange}-{$endRange}" ) )->get();

							Log::debug( "Fetching download [range={$startRange}-{$endRange}] with a response code: " . $response->httpResponseCode, 'auto_upgrade' );

							if ( $response->httpResponseCode == 404 )
							{
								if ( isset( $mrData['tmpFileName'] ) )
								{
									@unlink( $mrData['tmpFileName'] );
								}

								Log::log( "Cannot fetch delta download: " . var_export( $response, TRUE ), 'auto_upgrade' );

								if( CIC )
								{
									try
									{
										IPS::applyLatestFilesIPSCloud();
									}
									catch( \IPS\Http\Request\Exception $e ){}

									return Theme::i()->getTemplate('system')->upgradeDeltaFailedCic();
								}
								else
								{
									return array( Theme::i()->getTemplate('system')->upgradeDeltaFailed( 'unexpected_response', isset( $data['key'] ) ? Url::ips("download/{$data['key']}") : NULL ) );
								}
							}
							elseif ( $response->httpResponseCode == 206 )
							{
								$totalFileSize = intval( mb_substr( $response->httpHeaders['Content-Range'], mb_strpos( $response->httpHeaders['Content-Range'], '/' ) + 1 ) );

								$fh = fopen( $mrData['tmpFileName'], 'a' );
								fwrite( $fh, (string) $response );
								fclose( $fh );

								$mrData['range']	= $endRange + 1;
								$complete			= 100 / $totalFileSize * $mrData['range'];

								return array( $mrData, Member::loggedIn()->language()->addToStack('delta_upgrade_downloading'), ( $complete > 100 ) ? 100 : $complete );
							}
							else
							{
								$mrData['status'] = 'extract';
								return array( $mrData, Member::loggedIn()->language()->addToStack('delta_upgrade_extracting'), 0 );
							}
						}
					}
				}
				/* Extract */
				elseif ( $mrData['status'] == 'extract' )
				{
					UpgradeClass::setUpgradingFlag( TRUE );
					Session::i()->log( 'acplog__upgrade_started' );

					$extractUrl = new Url( Settings::i()->base_url . 'admin/upgrade/extract.php' );
					$extractUrl = $extractUrl
						->setScheme( NULL )	// Use protocol-relative in case the AdminCP is being loaded over https but rest of site is not
						->setQueryString( array(
							'file'			=> $mrData['tmpFileName'],
							'container'		=> $data['key'],
							'key'			=> md5( Settings::i()->board_start . $mrData['tmpFileName'] . Settings::i()->sql_pass ),
							'ftp'			=> ( isset( $data['ftpDetails'] ) ) ? $data['ftpDetails'] : ''
						)
					);

					return array( Theme::i()->getTemplate('system')->upgradeExtract( $extractUrl ) );
				}
			},
			function()
			{
				Output::i()->redirect( Url::internal('app=core&module=system&controller=upgrade&check=1') );
			} );
		}

		/* Run md5 check */
		try
		{
			$files = Application::md5Check();
			if ( count( $files ) )
			{
				/* Log */
				Log::debug( "MD5 check of delta download failed with " . count( $files ) . " reported as modified", 'auto_upgrade' );

				/* If we'rve already tried to fix them and failed, show an error */
				if ( isset( $data['md5Fix'] ) and $data['md5Fix'] )
				{
					return Theme::i()->getTemplate('system')->upgradeDeltaFailed( 'exception', NULL );
				}

				/* Otherwise try to just fix them - first get a new download key */
				$files = array_map( function( $file ) {
					return str_replace( \IPS\ROOT_PATH, '', $file );
				}, $files );
				$this->_clientAreaPassword = $data['ips_pass'];
				$newDownloadKey = $this->_getDownloadKey( $data['ips_email'], $data['version'], $files );

				/* Manipulate the wizard data */
				$data = $_SESSION[ 'wizard-' . md5( Url::internal( 'app=core&module=system&controller=upgrade' ) ) . '-data' ];
				$data['key'] = $newDownloadKey;
				$data['md5Fix'] = TRUE;
				$_SESSION[ 'wizard-' . md5( Url::internal( 'app=core&module=system&controller=upgrade' ) ) . '-data' ] = $data;

				/* Redirect back in */
				Output::i()->redirect( Url::internal('app=core&module=system&controller=upgrade') );
			}
		}
		catch ( \Exception $e ) {}

		/* Nope, we're good! */
		return $data;
	}

	/**
	 * Upgrade
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _upgrade( array $data ) : array|string
	{
		/* Resync */
		IPS::resyncIPSCloud('Uploaded new version');

		/* If we cannot handle this in the AdminCP, redirect them to the upgrader */
		if ( $data['info']['forceMainUpgrader'] )
		{
			Output::i()->redirect( 'upgrade/?autologin=1' );
		}

		/* If we have an adsess we are coming from 4.4 to 4.5, so just send to the full upgrader now */
		if( isset( Request::i()->adsess ) )
		{
			Output::i()->redirect( 'upgrade/?autologin=1' );
		}

		/* Otherwise let's do the upgrade! */
		$url = Url::internal('app=core&module=system&controller=upgrade');
		return (string) new MultipleRedirect( $url, function( $mrData ) use ( $data )
		{
			if ( !is_array( $mrData ) )
			{
				return array( array( 'step' => 0 ), Member::loggedIn()->language()->addToStack('delta_upgrade_processing'), 0 );
			}
			else
			{
				$steps = array();
				foreach ( $data['info']['steps'] as $step => $v )
				{
					if ( $v )
					{
						if ( isset( static::$availableSteps[ $step ] ) )
						{
							$steps[] = static::$availableSteps[ $step ];
						}
						else
						{
							Output::i()->redirect( 'upgrade/?autologin=1' ); // This is just a sanity check, should never be hit
						}
					}
				}

				if ( count( $steps ) AND array_key_exists( $mrData['step'], $steps ) )
				{
					$perStepPercentage = ( 100 / count( $steps ) );

					$step = $steps[ $mrData['step'] ];
					$percentage = $perStepPercentage * intval( $mrData['step'] );
					$stepData = $mrData[$step] ?? array();

					$return = $this->$step( $data, $stepData );
					if ( $return === NULL )
					{
						unset( $mrData[ $step ] );
						$mrData['step']++;
						$percentage += $perStepPercentage;
					}
					elseif ( is_string( $return ) )
					{
						return array( $return );
					}
					else
					{
						$mrData[ $step ] = $return[1];
						$percentage += ( $return[0] / ( 100 / $perStepPercentage ) );
					}

					return array( $mrData, Member::loggedIn()->language()->addToStack( 'delta_upgrade' . $step ), round( $percentage, 2 ) );
				}
				else
				{
					UpgradeClass::setUpgradingFlag( FALSE );

					$databaseErrors = FALSE;
					foreach ( Application::applications() as $app )
					{
						if( in_array( $app->directory, IPS::$ipsApps ) )
						{
							$versions = $app->getAllVersions();
							$longVersions	= array_keys( $versions );
							$humanVersions	= array_values( $versions );
							$latestLVersion	= array_pop( $longVersions );
							$latestHVersion	= array_pop( $humanVersions );
							Db::i()->update( 'core_applications', array( 'app_version' => $latestHVersion, 'app_long_version' => $latestLVersion ), array( 'app_directory=?', $app->directory ) );
							Db::i()->insert( 'core_upgrade_history', array( 'upgrade_version_human' => $latestHVersion, 'upgrade_version_id' => $latestLVersion, 'upgrade_date' => time(), 'upgrade_mid' => (int) Member::loggedIn()->member_id, 'upgrade_app' => $app->directory ) );

							if( !$app->enabled )
							{
								// Do not run the database check if the app is disabled.
								continue;
							}

							if ( $app->databaseCheck() )
							{
								$databaseErrors = TRUE;
							}
						}
					}
					unset( Store::i()->applications, Store::i()->updatecount_applications );

					Zapier::rebuildRESTApiPermissions();

					AdminNotification::remove( 'core', 'NewVersion' );

					return array( Theme::i()->getTemplate('system')->upgradeFinished( $databaseErrors ) );
				}
			}
		},
		function()
		{
			Output::i()->redirect( 'upgrade/?autologin=1' );
		} );
	}

	/**
	 * Upgrade: Queries
	 *
	 * @param	array	$data		Wizard data
	 * @param	array	$stepData	Data for this step
	 * @return	array|null	array( percentage of this step complete, $stepData ) OR NULL if this step is complete
	 */
	public function _upgradeQueries( array $data, array $stepData ) : ?array
	{
		return $this->_appLoop( 'queries', $data, $stepData, function( $app, $data, $stepData )
		{
			/* If this is the first run, work out how many things we have to do, and remove any that need removing */
			if ( !isset( $stepData['offset'] ) )
			{
				$numberOfChangesInThisApp = count( $data['changes']['queries'][ $app ] );
				if ( !$numberOfChangesInThisApp )
				{
					return NULL;
				}
				else
				{
					$stepData['offset'] = 0;
					$stepData['count'] = $numberOfChangesInThisApp;
					return array( 0, $stepData );
				}
			}

			/* Get the next query */
			$queriesToRun = array_values( $data['changes']['queries'][ $app ] );
			if ( isset( $queriesToRun[ $stepData['offset'] ] ) )
			{
				$method = $queriesToRun[ $stepData['offset'] ]['method'];
				$params = $queriesToRun[ $stepData['offset'] ]['params'];

				/* Is it for a table that we need to run manual queries on? */
				$tableName = ( isset( $params[0] ) and is_string( $params[0] ) ) ? $params[0] : $params[0]['name'];
				if ( !isset( Request::i()->runQuery ) and in_array( $tableName, $data['largeTables'] ) )
				{
					if ( !isset( Request::i()->query_has_been_ran ) )
					{
						$query = Db::i()->returnQuery( $method, $params );

						return Theme::i()->getTemplate('system')->upgradeDeltaManualQuery( Request::i()->mr, $query );
					}
					else
					{
						$stepData['offset']++;
						return array( 100 / $stepData['count'] * $stepData['offset'], $stepData );
					}
				}

				/* Nope, we can run it manually */
				else
				{
					try
					{
						Db::i()->$method( ...$params );
					}
					catch ( Exception $e )
					{
						if ( !isset( Request::i()->query_has_been_ran ) )
						{
							if ( isset( Request::i()->runQuery ) )
							{
								Output::i()->json( array( 'runManualQuery' => FALSE ) );
							}

							return Theme::i()->getTemplate('system')->upgradeDeltaQueryFailed( Request::i()->mr, $e );
						}
					}

					if ( isset( Request::i()->runQuery ) )
					{
						Output::i()->json( array( 'runManualQuery' => TRUE ) );
					}

					$stepData['offset']++;
					return array( 100 / $stepData['count'] * $stepData['offset'], $stepData );
				}
			}

			/* If there isn't one, we're done - run a final database check */
			return NULL;
		} );
	}

	/**
	 * Upgrade: HTML and CSS
	 *
	 * @param	array	$data		Wizard data
	 * @param	array	$stepData	Data for this step
	 * @return	array|null	array( percentage of this step complete, $stepData ) OR NULL if this step is complete
	 */
	public function _upgradeTheme( array $data, array $stepData ) : ?array
	{
		return $this->_appLoop( 'theme', $data, $stepData, function( $app, $data, $stepData )
		{
			/* If this is the first run, work out how many things we have to do, and remove any that need removing */
			if ( !isset( $stepData['offset'] ) )
			{
				$numberOfChangesInThisApp = 0;

				/* HTML */
				$templateGroupsToClear = array();
				foreach ( $data['changes']['theme'][ $app ]['html'] as $type => $templates )
				{
					$numberOfChangesInThisApp += count( $templates );

					if ( $type == 'removed' )
					{
						foreach ( $templates as $template )
						{
							$exploded = explode( '/', $template );
							$templateGroupsToClear[ $exploded[0] ][ $exploded[1] ] = TRUE;

							Theme::removeTemplates( $app, $exploded[0], $exploded[1], NULL, FALSE, $exploded[2] );
						}
					}
				}

				/* CSS */
				foreach ( $data['changes']['theme'][ $app ]['css'] as $type => $cssFiles )
				{
					$numberOfChangesInThisApp += count( $cssFiles );

					if ( $type == 'removed' )
					{
						foreach ( $cssFiles as $cssFile )
						{
							preg_match( '/^([^\/]+)\/(.+?)\/([^\/]+\.css)$/', $cssFile, $matches );
							Theme::deleteCompiledCss( $app, $matches[1], $matches[2] ?: '.', $matches[3] );
							Theme::removeCss( $app, $matches[1], $matches[2] ?: '.', NULL, FALSE, $matches[3] );
						}
					}
				}

				/* Resources */
				foreach ( $data['changes']['theme'][ $app ]['resources'] as $type => $resources )
				{
					$numberOfChangesInThisApp += count( $resources );

					if ( $type == 'removed' )
					{
						foreach ( $resources as $resource )
						{
							preg_match( '/^([^\/]+)(.+?)([^\/]+)$/', $resource, $matches );
							Theme::deleteCompiledResources( $app, $matches[1], $matches[2], $matches[3] );
							Theme::removeResources( $app, $matches[1], $matches[2], NULL, FALSE, $matches[3] );
						}
					}
				}

				/* Return those details so the next step can actually begin the import */
				if ( !$numberOfChangesInThisApp )
				{
					return NULL;
				}
				else
				{
					$stepData['offset'] = 0;
					$stepData['count'] = $numberOfChangesInThisApp;
					$stepData['templateGroupsToClear'] = $templateGroupsToClear;
					return array( 0, $stepData );
				}
			}

			/* Import new stuff */
			$perLoop = 150;
			$i = 0;
			$done = 0;
			$xml = \IPS\Xml\XMLReader::safeOpen( \IPS\ROOT_PATH . "/applications/{$app}/data/theme.xml" );
			$xml->read();
			while ( $xml->read() )
			{
				/* Skip to where we need to be */
				if( $xml->nodeType != XMLReader::ELEMENT )
				{
					continue;
				}
				$i++;
				if ( $stepData['offset'] )
				{
					if ( $i - 1 < $stepData['offset'] )
					{
						$xml->next();
						continue;
					}
				}

				/* Templates */
				if( $xml->name == 'template' )
				{
					if ( $location = $xml->getAttribute('template_location') and $group = $xml->getAttribute('template_group') and $template = $xml->getAttribute('template_name') )
					{
						$templateKey = "{$location}/{$group}/{$template}";
						if ( in_array( $templateKey, $data['changes']['theme'][ $app ]['html']['edited'] ) )
						{
							Theme::removeTemplates( $app, $location, $group, NULL, FALSE, $template );
							$stepData['templateGroupsToClear'][ $location ][ $group ] = TRUE;
						}
						if ( in_array( $templateKey, $data['changes']['theme'][ $app ]['html']['added'] ) or in_array( $templateKey, $data['changes']['theme'][ $app ]['html']['edited'] ) )
						{
							Theme::addTemplate( array(
								'app'				=> $app,
								'group'				=> $group,
								'name'				=> $template,
								'variables'			=> $xml->getAttribute('template_data'),
								'content'			=> $xml->readString(),
								'location'			=> $location,
								'_default_template' => true
							) );
							$done++;
						}
					}
				}
				/* CSS Files */
				elseif( $xml->name == 'css' )
				{
					if ( $location = $xml->getAttribute('css_location') and $path = $xml->getAttribute('css_path') and $name = $xml->getAttribute('css_name') )
					{
						$cssKey = "{$location}/" . ( ( $path and $path != '.' ) ? "{$path}/" : '' ) . $name;
						if ( in_array( $cssKey, $data['changes']['theme'][ $app ]['css']['edited'] ) )
						{
							Theme::deleteCompiledCss( $app, $location, $path ?: '.', $name );
							Theme::removeCss( $app, $location, $path ?: '.', NULL, FALSE, $name );
						}
						if ( in_array( $cssKey, $data['changes']['theme'][ $app ]['css']['added'] ) or in_array( $cssKey, $data['changes']['theme'][ $app ]['css']['edited'] ) )
						{
							Theme::addCss( array(
								'app'		=> $app,
								'location'	=> $location,
								'path'		=> $path,
								'name'		=> $name,
								'content'	=> $xml->readString(),
								'_default_template' => true
							) );
							$done++;
						}
					}
				}
				/* CSS Files */
				elseif( $xml->name == 'resource' )
				{
					if ( $location = $xml->getAttribute('location') and $path = $xml->getAttribute('path') and $name = $xml->getAttribute('name') )
					{
						$resourceKey = "{$location}{$path}{$name}";
						if ( isset( $templateKey ) AND in_array( $templateKey, $data['changes']['theme'][ $app ]['resources']['edited'] ) )
						{
							Theme::deleteCompiledResources( $app, $location, $path, $name );
							Theme::removeResources( $app, $location, $path, NULL, FALSE, $name );
						}
						if ( in_array( $resourceKey, $data['changes']['theme'][ $app ]['resources']['added'] ) or in_array( $resourceKey, $data['changes']['theme'][ $app ]['resources']['edited'] ) )
						{
							Theme::addResource( array(
								'app'		=> $app,
								'location'	=> $location,
								'path'		=> $path,
								'name'		=> $name,
								'content'	=> base64_decode( $xml->readString() ),
							) );
							$done++;
						}
					}
				}

				/* Have we done the most we're allowed per loop? */
				if( $done >= $perLoop )
				{
					$stepData['offset'] = $i;
					$stepData['done'] = isset( $stepData['done'] ) ? ( $stepData['done'] + $done ) : $done;
					return array( 100 / $stepData['count'] * $stepData['done'], $stepData );
				}
			}

			/* If we're still here, this app is complete - do cleanup */
			foreach ( $stepData['templateGroupsToClear'] as $location => $groups )
			{
				foreach ( $groups as $group => $_ )
				{
					Theme::deleteCompiledTemplate( $app, $location, $group );
				}
			}
			if ( count( $data['changes']['theme'][ $app ]['resources']['removed'] ) )
			{
				foreach( Theme::themes() as $id => $set )
				{
					$set->buildResourceMap( $app );
				}
			}
			return NULL;
		} );
	}

	/**
	 * Upgrade: Languages
	 *
	 * @param	array	$data		Wizard data
	 * @param	array	$stepData	Data for this step
	 * @return	array|null	array( percentage of this step complete, $stepData ) OR NULL if this step is complete
	 */
	public function _upgradeLanguages( array $data, array $stepData ) : ?array
	{
		return $this->_appLoop( 'lang', $data, $stepData, function( $app, $data, $stepData )
		{
			$languages	= array_keys( Lang::languages() );

			/* Remove old */
			if ( $data['changes']['lang'][ $app ]['normal']['removed'] )
			{
				Db::i()->delete( 'core_sys_lang_words', array( array( 'word_app=? AND word_js=0', $app ), array( Db::i()->in( 'word_key', $data['changes']['lang'][ $app ]['normal']['removed'] ) ) ) );
			}
			if ( $data['changes']['lang'][ $app ]['js']['removed'] )
			{
				Db::i()->delete( 'core_sys_lang_words', array( array( 'word_app=? AND word_js=1', $app ), array( Db::i()->in( 'word_key', $data['changes']['lang'][ $app ]['js']['removed'] ) ) ) );
			}
			if ( !isset( $stepData['offset'] ) )
			{
				$numberOfChangesInThisApp = 0;

				foreach ( $data['changes']['lang'][ $app ] as $jsOrNormal => $types )
				{
					foreach ( $types as $type => $langKeys )
					{
						if ( count( $langKeys ) )
						{
							if ( $type == 'removed' )
							{
								Db::i()->delete( 'core_sys_lang_words', array( array( 'word_app=? AND word_js=?', $app, ( $jsOrNormal == 'js' ? 1 : 0 ) ), array( Db::i()->in( 'word_key', $langKeys ) ) ) );
							}
							else
							{
								$numberOfChangesInThisApp += count( $langKeys );
							}
						}
					}
				}

				if ( !$numberOfChangesInThisApp )
				{
					return NULL;
				}
				else
				{
					$stepData['offset'] = 0;
					$stepData['count'] = $numberOfChangesInThisApp;
					return array( 0, $stepData );
				}
			}

			/* Import new stuff */
			$perLoop = 150;
			$batchSize = 25;
			$i = 0;
			$done = 0;
			$inserts = array();
			$xml = \IPS\Xml\XMLReader::safeOpen( \IPS\ROOT_PATH . "/applications/{$app}/data/lang.xml" );
			$xml->read();
			$xml->read();
			$xml->read();
			$version = $xml->getAttribute('version');
			while ( $xml->read() )
			{
				/* Skip to where we need to be */
				if( $xml->nodeType != XMLReader::ELEMENT )
				{
					continue;
				}
				$i++;
				if ( $stepData['offset'] )
				{
					if ( $i - 1 < $stepData['offset'] )
					{
						$xml->next();
						continue;
					}
				}

				/* Templates */
				if( $xml->name == 'word' )
				{
					if ( $langKey = $xml->getAttribute('key') )
					{
						$js = $xml->getAttribute('js');
						$value = $xml->readString();
						if ( in_array( $langKey, $data['changes']['lang'][ $app ][ $js ? 'js' : 'normal' ]['added'] ) or in_array( $langKey, $data['changes']['lang'][ $app ][ $js ? 'js' : 'normal' ]['edited'] ) )
						{
							foreach ( $languages as $languageId )
							{
								$inserts[] = array(
									'lang_id'				=> $languageId,
									'word_app'				=> $app,
									'word_key'				=> $langKey,
									'word_default'			=> $value,
									'word_default_version'	=> $version,
									'word_js'				=> $js,
									'word_export'			=> 1
								);
								$done++;
							}
						}
					}
				}

				/* Have we got enough for a batch? */
				if ( $done >= $batchSize )
				{
					if ( count( $inserts ) )
					{
						Db::i()->insert( 'core_sys_lang_words', $inserts, TRUE );
						$inserts = array();
					}
					$batchesDone++;
				}

				/* Have we done the most we're allowed per loop? */
				if( $done >= $perLoop )
				{
					if ( count( $inserts ) )
					{
						Db::i()->insert( 'core_sys_lang_words', $inserts, TRUE );
					}

					$stepData['offset'] = $i;
					$stepData['done'] = isset( $stepData['done'] ) ? ( $stepData['done'] + $done ) : $done;
					return array( 100 / $stepData['count'] * $stepData['done'], $stepData );
				}
			}

			/* If we're still here, this app is complete */
			if ( count( $inserts ) )
			{
				Db::i()->insert( 'core_sys_lang_words', $inserts, TRUE );
			}
			return NULL;
		} );
	}

	/**
	 * Upgrade: Javascript
	 *
	 * @param	array	$data		Wizard data
	 * @param	array	$stepData	Data for this step
	 * @return	array|null	array( percentage of this step complete, $stepData ) OR NULL if this step is complete
	 */
	public function _upgradeJavascript( array $data, array $stepData ) : ?array
	{
		return $this->_appLoop( 'javascript', $data, $stepData, function( $app, $data, $stepData )
		{
			if ( !isset( $stepData['offset'] ) )
			{
				$numberOfChangesInThisApp = 0;

				foreach ( $data['changes']['javascript'][ $app ]['files'] as $type => $files )
				{
					if ( $type === 'removed' )
					{
						foreach ( $files as $file )
						{
							preg_match( '/^' . preg_quote( $app, '/' ) . '\/(.+?)\/(.*)\/(.+?)$/', $file, $matches );
							Db::i()->delete( 'core_javascript', array( 'javascript_app=? AND javascript_location=? AND javascript_path=? AND javascript_name=?', $app, $matches[1], $matches[2], $matches[3] ) );
						}
					}
					else
					{
						$numberOfChangesInThisApp += count( $files );
					}
				}

				if ( !$numberOfChangesInThisApp )
				{
					if ( count( $data['changes']['javascript'][ $app ]['files']['removed'] ) )
					{
						Output::clearJsFiles( $app );
					}
					return NULL;
				}
				else
				{
					$stepData['offset'] = 0;
					$stepData['count'] = $numberOfChangesInThisApp;
					return array( 0, $stepData );
				}
			}

			/* Import new stuff */
			$perLoop = 150;
			$i = 0;
			$done = 0;
			$xml = \IPS\Xml\XMLReader::safeOpen( \IPS\ROOT_PATH . "/applications/{$app}/data/javascript.xml" );
			$xml->read();
			while ( $xml->read() )
			{
				/* Skip to where we need to be */
				if( $xml->nodeType != XMLReader::ELEMENT )
				{
					continue;
				}
				$i++;
				if ( $stepData['offset'] )
				{
					if ( $i - 1 < $stepData['offset'] )
					{
						$xml->next();
						continue;
					}
				}

				if( $xml->name == 'file' )
				{
					if ( $location = $xml->getAttribute('javascript_location') and $path = $xml->getAttribute('javascript_path') and $name = $xml->getAttribute('javascript_name') )
					{
						$filePath = "{$app}/{$location}/{$path}/{$name}";

						if ( in_array( $filePath, $data['changes']['javascript'][ $app ]['files']['added'] ) or in_array( $filePath, $data['changes']['javascript'][ $app ]['files']['edited'] ) )
						{
							Db::i()->replace( 'core_javascript', array(
								'javascript_app'		=> $xml->getAttribute('javascript_app'),
								'javascript_key'        => '',
								'javascript_location'	=> $xml->getAttribute('javascript_location'),
								'javascript_path'		=> $xml->getAttribute('javascript_path'),
								'javascript_name'		=> $xml->getAttribute('javascript_name'),
								'javascript_type'		=> $xml->getAttribute('javascript_type'),
								'javascript_content'	=> $xml->readString(),
								'javascript_version'	=> $xml->getAttribute('javascript_version'),
								'javascript_position'	=> $xml->getAttribute('javascript_position')
							) );
							$done++;
						}
					}
				}

				/* Have we done the most we're allowed per loop? */
				if( $done >= $perLoop )
				{
					$stepData['offset'] = $i;
					$stepData['done'] = isset( $stepData['done'] ) ? ( $stepData['done'] + $done ) : $done;
					return array( 100 / $stepData['count'] * $stepData['done'], $stepData );
				}
			}

			/* If we're still here, this app is complete */
			Output::clearJsFiles( $app );
			return NULL;
		} );
	}

	/**
	 * App Looper
	 *
	 * @param	string			$step		The step to do
	 * @param	array			$data		Data for upgrade
	 * @param	array			$stepData	Data for this step
	 * @param	callable			$code		Code to execute for each app
	 * @return	array|string|null		array( percentage of this step complete, $stepData ) OR NULL if this step is complete
	 */
	protected function _appLoop( string $step, array $data, array $stepData, callable $code ) : array|string|null
	{
		$returnNext = FALSE;
		$apps = array_keys( Application::applications() );
		$percentage = 0;
		$perAppPercentage = ( 100 / count( $apps ) );

		foreach ( $apps as $app )
		{
			if( !in_array( $app, IPS::$ipsApps ) or !isset( $data['changes'][ $step ][ $app ] ) )
			{
				continue;
			}

			if ( !isset( $stepData['app'] ) )
			{
				$stepData['app'] = $app;
			}

			if ( $stepData['app'] == $app )
			{
				$val = call_user_func( $code, $app, $data, $stepData );

				if ( is_string( $val ) )
				{
					return $val;
				}
				elseif ( $val !== NULL )
				{
					$percentage += ( $val[0] / ( 100 / $perAppPercentage ) );
					return array( $percentage, $val[1] );
				}
				else
				{
					$returnNext = TRUE;
				}
			}
			else
			{
				$percentage += $perAppPercentage;

				if ( $returnNext )
				{
					$stepData = array( 'app' => $app );
					return array( $percentage, $stepData );
				}
			}
		}

		return NULL;
	}

	/**
	 * Run the database checker
	 *
	 * @return  void
	 */
	public function databaseChecker() : void
	{
		/* We can re-use the method used for the support section, however we need to pass a different bool */
		( new support )->_databaseChecker( TRUE );
	}
}
