<?php
/**
 * @brief		Advanced Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 June 2013
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use IPS\Application;
use IPS\core\AdminNotification;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Custom;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Task\Exception as TaskException;
use IPS\Theme;
use IPS\Widget;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use UnexpectedValueException;
use function constant;
use function count;
use function defined;
use function file_exists;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use const IPS\BYPASS_ACP_IP_CHECK;
use const IPS\CACHE_CONFIG;
use const IPS\CACHE_METHOD;
use const IPS\CIC;
use const IPS\DEV_USE_FURL_CACHE;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\IN_DEV;
use const IPS\REDIS_CONFIG;
use const IPS\REDIS_ENABLED;
use const IPS\ROOT_PATH;
use const IPS\STORE_CONFIG;
use const IPS\STORE_METHOD;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Advanced Settings
 */
class advanced extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage' );
		parent::execute();
	}
	
	/**
	 * Manage: Works out tab and fetches content
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Work out output */
		Request::i()->tab = isset( Request::i()->tab ) ? Request::i()->tab : 'settings';
		if ( $pos = mb_strpos( Request::i()->tab, '-' ) )
		{
			$tabMethod			= '_manage' . IPS::mb_ucfirst( mb_substr( Request::i()->tab, 0, $pos ) );
			$activeTabContents	= $this->$tabMethod( mb_substr( Request::i()->tab, $pos + 1 ) );
		}
		else
		{
			$tabMethod			= '_manage' . IPS::mb_ucfirst( Request::i()->tab );
			$activeTabContents	= $this->$tabMethod();
		}
		
		/* If this is an AJAX request, just return it */
		if( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
			return;
		}
		
		/* Build tab list */
		$tabs = array();
		$tabs['settings'] = 'server_environment';
		if ( Settings::i()->use_friendly_urls and Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'advanced_manage_furls' ) )
		{
			$tabs['furl']  = 'furls';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'datastore' ) and !CIC )
		{
			$tabs['datastore'] = 'data_store';
		}
		$tabs['pageoutput'] = 'page_output';
			
		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_settings_advanced');
		Output::i()->output 	= Theme::i()->getTemplate( 'global' )->tabs( $tabs, Request::i()->tab, $activeTabContents, Url::internal( "app=core&module=settings&controller=advanced" ) );
	}

	/**
	 * Data store management
	 *
	 * @return	string
	 */
	protected function _manageDatastore() : string
	{
		/* Are we just checking the constants? */
		if ( isset( Request::i()->checkConstants ) )
		{
			$cacheConfig = CACHE_CONFIG;
			
			if ( Request::i()->store_method === 'Redis' or Request::i()->cache_method === 'Redis' )
			{
				$cacheConfig = REDIS_CONFIG;
			}
			
			/* If we've changed anything, explain to the admin they have to update */
			if ( Request::i()->store_method !== STORE_METHOD or Request::i()->store_config !== STORE_CONFIG or Request::i()->cache_method !== CACHE_METHOD or Request::i()->cache_config !== $cacheConfig )
			{
				$downloadUrl = Url::internal( 'app=core&module=settings&controller=advanced&do=downloadDatastoreConstants' )->setQueryString( array( 'store_method' => Request::i()->store_method, 'store_config' => Request::i()->store_config, 'cache_method' => Request::i()->cache_method, 'cache_config' => Request::i()->cache_config ) );
				$checkUrl = Url::internal( 'app=core&module=settings&controller=advanced&tab=datastore&checkConstants=1' )->setQueryString( array( 'store_method' => Request::i()->store_method, 'store_config' => Request::i()->store_config, 'cache_method' => Request::i()->cache_method, 'cache_config' => Request::i()->cache_config ) )->csrf();
				return Theme::i()->getTemplate( 'settings' )->dataStoreChange( $downloadUrl, $checkUrl, TRUE );
			}
			/* Otherwise just log and redirect */
			else
			{
				/* Clear it */
				Cache::i()->clearAll();
				Store::i()->clearAll();

				/* Log and redirect */
				Session::i()->log( 'acplogs__datastore_settings_updated' );
				Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&tab=datastore' ), 'saved' );
			}
		}
		
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'datastore' );
		
		/* Init */
		$form = new Form;
		$form->attributes['data-controller'] = 'core.admin.system.settings';

		/* If the datastore isn't working properly, show a message */
		if( !Store::testStore() OR Db::i()->select( 'COUNT(*)', 'core_log', array( '`category`=? AND `time`>?', 'datastore', DateTime::create()->sub( new DateInterval( 'PT1H' ) )->getTimestamp() ) )->first() >= 10 )
		{
			/* Have we just recently updated the configuration? If so, ignore this warning for 24 hours */
			if( Settings::i()->last_data_store_update < DateTime::create()->sub( new DateInterval( 'PT24H' ) )->getTimestamp() )
			{
				$form->addMessage( 'dashboard_datastore_broken_settings', 'ipsMessage ipsMessage--warning' );
			}
		}

		/* Cold storage */
		$extra = array();
		$toggles = array();
		$disabled = array();
		$storeConfigurationFields = array();
		$options = [];
		foreach( Store::availableMethods() AS $key => $class )
		{
			$options[ $key ] = 'datastore_method_' . $key;
		}

		$existingConfiguration = json_decode( STORE_CONFIG, TRUE );
		foreach ( $options as $k => $v )
		{
			/* @var Store $class */
			$class = 'IPS\Data\Store\\' . $k;
			if ( !$class::supported() )
			{
				$disabled[] = $k;
				Member::loggedIn()->language()->words["datastore_method_{$k}_desc"] = Member::loggedIn()->language()->addToStack('datastore_method_disableddesc', FALSE, array( 'sprintf' => array( $k ) ) );
			}
			else
			{
				foreach ( $class::configuration( $k === STORE_METHOD ? $existingConfiguration : array() ) as $inputKey => $input )
				{
					if ( !$input->htmlId )
					{
						$input->htmlId = 'id_' . $input->name;
					}
					
					$extra[] = $input;
					$toggles[ $k ][] = $input->htmlId;
					$storeConfigurationFields[ $k ][ $inputKey ] = $input->name;
				}
			}
		}
		$form->add( new Radio( 'datastore_method', STORE_METHOD, TRUE, array(
			'options'	=> $options,
			'toggles'	=> $toggles,
			'disabled'	=> $disabled,
		), function( $val ){
			if( $val === 'Redis' AND Request::i()->cache_method !== 'Redis' )
			{
				throw new DomainException( 'datastore_redis_cache' );
			}
		} ) );
		foreach ( $extra as $input )
		{
			$form->add( $input );
		}

		/* Cache */
		$extra = array();
		$toggles = array( 'Redis' => array( 'redis_enabled' ) );
		$disabled = array();
		$cacheConfigurationFields = array();
		$options = [];
		foreach( Cache::availableMethods() AS $key => $class )
		{
			$options[ $key ] = 'datastore_method_' . $key;
		}
		
		$cacheConfig = CACHE_CONFIG;
		
		if ( defined( '\IPS\REDIS_CONFIG' ) and ( STORE_METHOD == 'Redis' OR CACHE_METHOD == 'Redis' ) )
		{
			$cacheConfig = REDIS_CONFIG;
		}
		
		$existingConfiguration = json_decode( $cacheConfig, TRUE );
		
		foreach ( $options as $k => $v )
		{
			/* @var Cache $class */
			$class = Cache::availableMethods()[ $k ];
			if ( !$class::supported() )
			{
				$disabled[] = $k;
				Member::loggedIn()->language()->words["datastore_method_{$k}_desc"] = Member::loggedIn()->language()->addToStack('datastore_method_disableddesc', FALSE, array( 'sprintf' => array( $k ) ) );
			}
			else
			{				
				foreach ( $class::configuration( $k === CACHE_METHOD ? $existingConfiguration : array() ) as $inputKey => $input )
				{
					if ( !$input->htmlId )
					{
						$input->htmlId = 'id_' . $input->name;
					}
					
					$extra[] = $input;
					$toggles[ $k ][] = $input->htmlId;
					$cacheConfigurationFields[ $k ][ $inputKey ] = $input->name;
				}
			}
		}
		$form->add( new Radio( 'cache_method', CACHE_METHOD, TRUE, array(
			'options'	=> $options,
			'toggles'	=> $toggles,
			'disabled'	=> $disabled,
		) ) );
		foreach ( $extra as $input )
		{
			$form->add( $input );
		}
		
		$form->add( new YesNo( 'redis_enabled', REDIS_ENABLED, FALSE, array(), NULL, NULL, NULL, 'redis_enabled' ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Work out configuration */
			$storeConfiguration = array();
			if ( isset( $storeConfigurationFields[ $values['datastore_method'] ] ) )
			{
				foreach ( $storeConfigurationFields[ $values['datastore_method'] ] as $k => $fieldName )
				{
					$storeConfiguration[ $k ] = $values[ $fieldName ];
				}
			}
			$cacheConfiguration = array();
			if ( isset( $cacheConfigurationFields[ $values['cache_method'] ] ) )
			{
				foreach ( $cacheConfigurationFields[ $values['cache_method'] ] as $k => $fieldName )
				{
					$cacheConfiguration[ $k ] = $values[ $fieldName ];
				}
			}
			
			/* If we've changed anything, explain to the admin they have to update */
			if ( $values['datastore_method'] !== STORE_METHOD or str_replace( '\\/', '/', json_encode( $storeConfiguration ) ) !== STORE_CONFIG or $values['cache_method'] !== CACHE_METHOD or json_encode( $cacheConfiguration ) !== CACHE_CONFIG or REDIS_ENABLED != (boolean) $values['redis_enabled'] )
			{
				/* Connect to cache engine if we can and invalidate any existing caches */
				try
				{
					/* @var Cache $classname */
					$classname = 'IPS\Data\Cache\\' . $values['cache_method'];
					
					if ( $classname::supported() )
					{
						$instance = new $classname( $cacheConfiguration );
						$instance->clearAll();
					}
				}
				catch( Exception $e ){}

				/* Invalidate any existing datastore records */
				try
				{
					$classname =  'IPS\Data\Store\\' . $values['datastore_method'];
					$instance = new $classname( $storeConfiguration );
					$instance->clearAll();
				}
				catch( Exception $e ){}

				/* Reset the last update flag for data store */
				Settings::i()->changeValues( array( 'last_data_store_update' => time() ) );
				AdminNotification::remove( 'core', 'ConfigurationError', 'dataStorageBroken' );
				
				/* Display */
				$downloadUrl = Url::internal( 'app=core&module=settings&controller=advanced&do=downloadDatastoreConstants' )->setQueryString( array( 'store_method' => $values['datastore_method'], 'store_config' => str_replace( '\\/', '/', json_encode( $storeConfiguration ) ), 'cache_method' => $values['cache_method'], 'cache_config' => json_encode( $cacheConfiguration ), 'redis_enabled' => $values['redis_enabled'] ) );
				$checkUrl = Url::internal( 'app=core&module=settings&controller=advanced&tab=datastore&checkConstants=1' )->setQueryString( array( 'store_method' => $values['datastore_method'], 'store_config' => str_replace( '\\/', '/', json_encode( $storeConfiguration ) ), 'cache_method' => $values['cache_method'], 'cache_config' => json_encode( $cacheConfiguration ), 'redis_enabled' => $values['redis_enabled'] ) )->csrf();
				return Theme::i()->getTemplate( 'settings' )->dataStoreChange( $downloadUrl, $checkUrl );
			}
			/* Otherwise just log and redirect */
			else
			{
				Session::i()->log( 'acplogs__datastore_settings_updated' );
				Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&tab=datastore' ), 'saved' );
			}
		}
		
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js('admin_system.js', 'core', 'admin') );
		
		return $form;
	}
	
	/**
	 * Download constants.php
	 *
	 * @return	void
	 */
	protected function downloadDatastoreConstants() : void
	{
		Dispatcher::i()->checkAcpPermission( 'datastore' );

		$output = "<?php\n\n";
		foreach ( IPS::defaultConstants() as $k => $v )
		{
			$val = constant( 'IPS\\' . $k );

			if ( $val !== $v and !in_array( $k, array( 'STORE_METHOD', 'STORE_CONFIG', 'CACHE_METHOD', 'CACHE_CONFIG', 'CACHE_PAGE_TIMEOUT', 'SUITE_UNIQUE_KEY', 'READ_WRITE_SEPARATION', 'REDIS_ENABLED', 'REDIS_CONFIG', 'REPORT_EXCEPTIONS' ) ) )
			{
				$output .= "\\define( '{$k}', " . var_export( $val, TRUE ) . " );\n";
			}
		}

		/* We have to treat READ_WRITE_SEPARATION special because admin/index.php always disables it */
		if( file_exists( ROOT_PATH . '/constants.php' ) )
		{
			$constants = file_get_contents( ROOT_PATH . '/constants.php' );

			/* Did we sniff the constant out with a quick check? */
			if( mb_strpos( $constants, 'READ_WRITE_SEPARATION' ) )
			{
				preg_match( "/define\(\s*?['\"]READ_WRITE_SEPARATION[\"']\s*?,\s*?(.+?)\);/i", $constants, $matches );

				if( isset( $matches[1] ) )
				{
					$output .= "\\define( 'READ_WRITE_SEPARATION', " . $matches[1] . " );\n";
				}
			}
		}
		
		$output .= "\n";
		$output .= "\\define( 'REDIS_ENABLED', " . var_export( (boolean) Request::i()->redis_enabled, TRUE ) . " );\n";
		$output .= "\\define( 'STORE_METHOD', " . var_export( Request::i()->store_method, TRUE ) . " );\n";
		$output .= "\\define( 'STORE_CONFIG', " . var_export( Request::i()->store_config, TRUE ) . " );\n";
		$output .= "\\define( 'CACHE_METHOD', " . var_export( Request::i()->cache_method, TRUE ) . " );\n";
		
		if ( Request::i()->store_method === 'Redis' or Request::i()->cache_method === 'Redis' )
		{
			$output .= "\\define( 'REDIS_CONFIG', " . var_export( Request::i()->cache_config, TRUE ) . " );\n";
		}
		else
		{
			$output .= "\\define( 'CACHE_CONFIG', " . var_export( Request::i()->cache_config, TRUE ) . " );\n";
		}
		
		$output .= "\\define( 'SUITE_UNIQUE_KEY', " . var_export( mb_substr( md5( mt_rand() ), 10, 10 ), TRUE ) . " );\n"; // Regenerate the unique key so there's no conflicts
		$output .= "\n\n\n";
				
		Output::i()->sendOutput( $output, 200, 'text/x-php', array( 'Content-Disposition' => 'attachment; filename=constants.php' ) );
	}

	/**
	 * Get setting to configure tasks
	 *
	 * @param Form $form	Form to add the setting to
	 * @return	void
	 */
	public static function taskSetting( Form $form ) : void
	{
		/* Generate a cron key if we don't have one */
		if ( !Settings::i()->task_cron_key )
		{
			Settings::i()->changeValues( array( 'task_cron_key' => md5( mt_rand() ) ) );
		}
		
		/* Sort stuff out for the cron setting */
		if ( CIC )
		{
			$options = array( 'options' => array(
				'ips' => 'task_method_ips'
			) );
		}
		else
		{
			$cronCommand = PHP_BINDIR . '/php -d memory_limit=-1 -d max_execution_time=0 ' . ROOT_PATH . '/applications/core/interface/task/task.php ' . Settings::i()->task_cron_key;
			try
			{
				Member::loggedIn()->language()->words['task_method_cron_warning'] = sprintf( Member::loggedIn()->language()->get( 'task_method_cron_warning' ), $cronCommand );
			}
			catch ( UnderflowException $e )
			{
				Member::loggedIn()->language()->words['task_method_cron_warning'] = $cronCommand;
			}
			
			$webCronUrl = (string) Url::internal( 'applications/core/interface/task/web.php?key=' . Settings::i()->task_cron_key, 'none' );
			try
			{
				Member::loggedIn()->language()->words['task_method_web_warning'] = sprintf( Member::loggedIn()->language()->get( 'task_method_web_warning' ), $webCronUrl );
			}
			catch ( UnderflowException $e )
			{
				Member::loggedIn()->language()->words['task_method_web_warning'] = $webCronUrl;
			}
			
			$options = array( 
				'options'	=> array(
					'normal'	=> 'task_method_normal',
					'cron'		=> 'task_method_cron',
					'web'		=> 'task_method_web',
				),
				'toggles' => array( 
					'cron' => array( 'task_use_cron_cron_warning' ), 
					'web' => array( 'task_use_cron_web_warning' )
				) 
			);
		}

		$form->add( new Radio( 'task_use_cron', CIC ? 'ips' : Settings::i()->task_use_cron, FALSE, $options, function ( $val )
		{
			$cronFile = ROOT_PATH . '/applications/core/interface/task/task.php';
			if ( $val == 'cron' and ( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) !== 'WIN' AND !is_executable( $cronFile ) ) )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack('task_use_cron_executable', FALSE, array( 'sprintf' => array( $cronFile ) ) ) );
			}
		}, NULL, NULL, 'task_use_cron' ) );
	}
	
	/**
	 * Settings
	 *
	 * @return	string
	 */
	protected function _manageSettings() : string
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_server' );
		
		/* Build and show form */
		$form = new Form;
		$form->addHeader('task_manager');
		
		static::taskSetting( $form );

		$form->addHeader( 'security_header_ips' );
		if ( !CIC )
		{
			$form->add( new YesNo( 'xforward_matching', Settings::i()->xforward_matching, FALSE ) );
		}
		$form->add( new YesNo( 'match_ipaddress', Settings::i()->match_ipaddress, FALSE ) );
		if( BYPASS_ACP_IP_CHECK )
		{
			Member::loggedIn()->language()->words['match_ipaddress_warning'] = Member::loggedIn()->language()->addToStack('ip_override_warn');
		}
		$form->add( new Radio( 'clickjackprevention', Settings::i()->clickjackprevention, FALSE, array(
			'options'	=> array(
				'xframe'	=> 'clickjackprevention_xframe',
				'csp'		=> 'clickjackprevention_csp',
				'none'		=> 'clickjackprevention_none',
			),
			'toggles'	=> array(
				'csp'		=> array( 'csp_header' )
			)
		) ) );
		$form->add( new Text( 'csp_header', Settings::i()->csp_header, FALSE, array( 'placeholder' => "default-src *; frame-ancestors 'self' *.example.com" ), NULL, NULL, NULL, 'csp_header' ) );
		$form->add( new Radio( 'referrer_policy_header', Settings::i()->referrer_policy_header, FALSE, array(
			'options'	=> array(
				'0'		=> 'referrerpolicy_disabled',
				'1'		=> 'referrerpolicy_acp_only',
				'2'		=> 'referrerpolicy_enabled',
			)
		) ) );

		$form->addHeader('performance_settings');
		$form->add( new Interval( 'widget_cache_ttl', ( isset( Settings::i()->widget_cache_ttl ) ) ? Settings::i()->widget_cache_ttl : 60, FALSE, array( 'valueAs' => Interval::SECONDS, 'min' => 60 ), NULL, Member::loggedIn()->language()->addToStack('for'), NULL ) );
		$form->add( new YesNo( 'auto_polling_enabled', Settings::i()->auto_polling_enabled, FALSE ) );
		
		if ( $values = $form->values() )
		{
			$form->saveAsSettings( $values );			
			Session::i()->log( 'acplogs__advanced_server_edited' );
			
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&tab=settings' ), 'saved' );
		}
		return $form;
	}
	
	/**
	 * Settings
	 *
	 * @return	string
	 */
	protected function _managePageoutput() : string
	{
		/* Build and show form */
		$form = new Form;
			
		$form->add( new Codemirror( 'custom_body_code', Settings::i()->custom_body_code, FALSE, array('height' => 150, 'codeModeAllowedLanguages' => [ 'xml' ] ), NULL, NULL, NULL, 'custom_body_code' ) );
		$form->add( new Codemirror( 'custom_page_view_js', Settings::i()->custom_page_view_js, FALSE, array('height' => 150, 'codeModeAllowedLanguages' => [ 'javascript' ] ), NULL, NULL, NULL, 'custom_page_view_js' ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( $values );			
			
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&tab=pageoutput' ), 'saved' );
		}
		return $form;
	}
	
	/**
	 * Tasks
	 *
	 * @return	void
	 */
	protected function tasks() : void
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_tasks' );
		
		$table = new TableDb( 'core_tasks', Url::internal( 'app=core&module=settings&controller=advanced&do=tasks' ), array( array( 'a.app_enabled=1' ) ) );
		$table->joins = array(
			array( 'select' => 'a.app_enabled', 'from' => array( 'core_applications', 'a' ), 'where' => "a.app_directory=app" ),
		);
		$table->langPrefix = 'task_manager_';
		$table->include = array( 'app', 'key', 'frequency', 'next_run', 'last_run' );
		$table->mainColumn = 'key';

		$table->primarySortBy = 'enabled';
		$table->primarySortDirection = 'DESC';
		
		$table->sortBy = $table->sortBy ?: 'next_run';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->noSort	= array( 'frequency' );
		
		$table->quickSearch = function( $val )
		{
			$matches = Member::loggedIn()->language()->searchCustom( 'task__', $val, TRUE );
			if ( count( $matches ) )
			{
				return array( '(' . Db::i()->in( '`key`', array_keys( $matches ) ) . " OR `key` LIKE '%{$val}%')" );
			}
			else
			{
				return array( "`key` LIKE '%" . Db::i()->escape_string( $val ) . "%'" );
			}
		};
		
		$table->parsers = array(
			'app'	=> function( $val )
			{
				try
				{
					return Application::load( $val )->_title ;
				}
				catch ( UnexpectedValueException | OutOfRangeException $e )
				{
					return NULL;
				}
			},
			'key'	=> function( $val )
			{
				$langKey = 'task__' . $val;
				if ( Member::loggedIn()->language()->checkKeyExists( $langKey ) )
				{
					return $val . '<br><span class="i-color_soft">' . Member::loggedIn()->language()->addToStack( $langKey ) . '</span>';
				}
				return $val;
			},
			'frequency' => function ( $v )
			{
				$interval = new DateInterval( $v );
				$return = array();
				foreach ( array( 'y' => 'years', 'm' => 'months', 'd' => 'days', 'h' => 'hours', 'i' => 'minutes', 's' => 'seconds' ) as $k => $v )
				{
					if ( $interval->$k )
					{
						$return[] = Member::loggedIn()->language()->addToStack( 'every_x_' . $v, FALSE, array( 'pluralize' => array( $interval->format( '%' . $k ) ) ) );
					}
				}
				
				return Member::loggedIn()->language()->formatList( $return );
			},
			'next_run' => function ( $v, $row )
			{
				if ( !$row['enabled'] )
				{
					return Member::loggedIn()->language()->addToStack('task_manager_disabled');
				}
				elseif ( $row['running'] )
				{
					return Member::loggedIn()->language()->addToStack('task_manager_running');
				}
				else
				{
					return (string) DateTime::ts( $row['next_run'] ?: time() );
				}
			},
			'last_run' => function ( $v, $row )
			{
				return (string) $row['last_run'] ?  DateTime::ts( $row['last_run'] ) : Member::loggedIn()->language()->addToStack( 'never' );
			}
		);
		
		$table->rowButtons = function( $row )
		{
			if ( $row['running'] )
			{
				$return = array( 'unlock' => array(
					'icon'	=> 'unlock',
					'title'	=> 'task_manager_unlock',
					'link'	=> Url::internal( "app=core&module=settings&controller=advanced&do=unlockTask&id={$row['id']}" )->csrf()
				) );
			}
			else
			{
				$return = array( 'run' => array(
					'icon'	=> 'play-circle',
					'title'	=> 'task_manager_run',
					'link'	=> Url::internal( "app=core&module=settings&controller=advanced&do=runTask&id={$row['id']}" )->csrf()
				) );
			}
			$return['logs'] = array(
				'icon'	=> 'search',
				'title'	=> 'task_manager_logs',
				'link'	=> Url::internal( "app=core&module=settings&controller=advanced&do=taskLogs&id={$row['id']}" )
			);
			return $return;
		};
		
		/* Add a button for settings */
		Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
						'title'		=> 'settings',
						'icon'		=> 'cog',
						'link'		=> Url::internal( 'app=core&module=settings&controller=advanced&do=taskSettings' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('settings') )
				),
		);
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('task_manager');
		Output::i()->output = (string) $table;
	}
	
	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function taskSettings() : void
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_tasks' );

		$form = new Form;
		$form->add( new Interval( 'prune_log_tasks', Settings::i()->prune_log_tasks, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_log_tasks' ) );
	
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__tasklog_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&do=tasks' ), 'saved' );
		}
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('task_settings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'task_settings', $form, FALSE );
	}
	
	/**
	 * Run Task
	 *
	 * @return	void
	 */
	protected function runTask() : void
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_tasks' );
		Session::i()->csrfCheck();
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('task_manager');
		
		try
		{
			$task = Task::load( Request::i()->id );
			if ( $task->running and !IN_DEV )
			{
				Output::i()->error( 'task_manager_locked', '2C124/2', 403, '' );
			}

			$output = $task->run();

			if ( $output === NULL )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&do=tasks' ), 'task_manager_ran' );
			}
			else
			{
				if ( is_array( $output ) )
				{
					$output = implode( "\n", array_map( array( Member::loggedIn()->language(), 'addToStack' ), $output ) );
				}
				elseif ( !is_string( $output ) and !is_numeric( $output ) )
				{
					$output = var_export( $output, TRUE );
				}
				else
				{
					$output = Member::loggedIn()->language()->addToStack( $output, FALSE );
				}
				
				Output::i()->bypassCsrfKeyCheck = true;
				Output::i()->output = Theme::i()->getTemplate( 'advancedsettings' )->taskResult( TRUE, $output, $task->id );
			}
		}
		catch ( TaskException $e )
		{
			Output::i()->bypassCsrfKeyCheck = true;
			Output::i()->output = Theme::i()->getTemplate( 'advancedsettings' )->taskResult( FALSE, Member::loggedIn()->language()->addToStack( $e->getMessage(), FALSE ), $task->id );
		}
		catch ( RuntimeException $e )
		{
			Output::i()->error( 'task_running_error', '2C124/7', 404, '', array(), IPS::getExceptionDetails( $e ) );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'task_class_not_found', '2C124/1', 404, '' );
		}
		catch( Exception $e )
		{
			Log::log( $e, 'uncaught_exception' );
			Output::i()->error( $e->getMessage() ?: 'task_running_error', '4C124/6', 404, '' );
		}
	}
	
	/**
	 * Unlock Task
	 *
	 * @return	void
	 */
	protected function unlockTask() : void
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_tasks' );
		Session::i()->csrfCheck();
		
		try
		{
			Task::load( Request::i()->id )->unlock();
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&do=tasks' ), 'task_manager_unlocked' );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C124/3', 404, '' );
		}
	}
	
	/**
	 * View task logs
	 *
	 * @return	void
	 */
	protected function taskLogs() : void
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_tasks' );
		
		try
		{
			$task = Task::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C124/4', 404, '' );
		}
		
		$table = new TableDb( 'core_tasks_log', Url::internal( "app=core&module=settings&controller=advanced&do=taskLogs&id={$task->id}" ), array( 'task=?', $task->id ) );
		$table->langPrefix = 'task_manager_';
		
		$table->include = array( 'time', 'log' );
		$table->parsers = array(
			'time'	=> function( $val )
			{
				return (string) DateTime::ts( $val );
			},
			'log'	=> function ( $val, $row )
			{
				$val = json_decode( $val );
				if ( is_array( $val ) )
				{
					$val = implode( "\n", array_map( array( Member::loggedIn()->language(), 'addToStack' ), $val ) );
				}
				elseif ( !is_string( $val ) and !is_numeric( $val ) )
				{
					$val = var_export( $val, TRUE );
				}
				else
				{
					if( $decoded = json_decode( $val ) )
					{
						$val = Member::loggedIn()->language()->addToStack( array_shift( $decoded ), FALSE, array( 'sprintf' => $decoded ) );
					}
					else
					{
						$val = Member::loggedIn()->language()->addToStack( $val, FALSE );
					}
				}
				return $row['error'] ? Theme::i()->getTemplate( 'global' )->message( $val, 'error' ) : $val;
			}
		);
		
		$table->sortBy = $table->sortBy ?: 'time';
		
		$table->quickSearch = 'log';
		$table->advancedSearch = array(
			'time'	=> SEARCH_DATE_RANGE,
			'log'	=> SEARCH_CONTAINS_TEXT
		);

		Output::i()->title = $task->key;
		Output::i()->output = Theme::i()->getTemplate( 'global' )->message( 'tasklogs_blurb', 'info' ) . $table;
	}
	
	/**
	 * FURLs
	 *
	 * @return string
	 */
	protected function _manageFurl() : string
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_furls' );
		
		if ( IN_DEV and !DEV_USE_FURL_CACHE )
		{
			Output::i()->error( 'furl_in_dev', '1C124/5', 403, '' );
		}

		$definition = Friendly::furlDefinition();
		$customConfiguration = Settings::i()->furl_configuration ? json_decode( Settings::i()->furl_configuration, TRUE ) : array();

		$table = new Custom( $definition, Url::internal( 'app=core&module=settings&controller=advanced&tab=furl' ) );
		$table->include = array( 'friendly', 'real' );
		$table->limit   = 100;
		$table->langPrefix = 'furl_';
		$table->mainColumn = 'real';
		$table->parsers = array(
			'friendly'	=> function( $val )
			{
				$val = preg_replace( '/{[@#](.+?)}/', '<strong><em>$1</em></strong>', $val );
				$val = preg_replace( '/{\?(\d+?)?}/', '<em>??</em>', $val );
				return "<span class='i-color_soft ipsResponsive_hideTablet'>" . Settings::i()->base_url . ( Settings::i()->htaccess_mod_rewrite ? '' : 'index.php?/' ) . "</span>{$val}";
			},
			'real' => function( $val, $row )
			{
				preg_match_all( '/{([@#])(.+?)}/', $row['friendly'], $matches );
				if ( !empty( $matches[0] ) )
				{
					foreach ( $matches[0] as $i => $m )
					{
						$val .= '&' . $matches[ 2 ][ $i ] . '=<strong><em>' . ( $matches[ 1 ][ $i ] == '#' ? '123' : 'abc' ) . '</em></strong>';
					}
					$val .= '</strong>';
				}
				
				return "<span class='i-color_soft ipsResponsive_hideTablet'>" . Settings::i()->base_url . "index.php?</span>{$val}";
			}
		);
		$table->quickSearch = 'friendly';
		$table->advancedSearch = array(
			'friendly'	=> SEARCH_CONTAINS_TEXT,
			'real'		=> SEARCH_CONTAINS_TEXT
		);
		
		$table->rootButtons = array(
			'add'		=> array(
				'icon'	=> 'plus',
				'title'	=> 'add',
				'link'	=> Url::internal( 'app=core&module=settings&controller=advanced&do=furlForm' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
			)
		);

		if( $customConfiguration AND count( $customConfiguration ) )
		{
			$table->rootButtons['revert'] = array(
				'icon'	=> 'undo',
				'title'	=> 'furl_revert',
				'link'	=> Url::internal( 'app=core&module=settings&controller=advanced&do=furlRevert' )->csrf(),
				'data'	=> array( 'confirm' => '' )
			);
		}

		$table->rowButtons = function( $row, $k ) use ( $definition, $customConfiguration )
		{
			$return = array(
				'edit'	=> array(
					'icon'	=> 'pencil',
					'title'	=> 'edit',
					'link'	=> Url::internal( "app=core&module=settings&controller=advanced&do=furlForm&key={$k}" ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') )
				)
			);

			if( isset( $definition[ $k ]['custom'] ) OR isset( $customConfiguration[ $k ] ) )
			{
				$return['revert'] = array(
					'icon'	=> 'undo',
					'title'	=> 'revert',
					'link'	=> Url::internal( "app=core&module=settings&controller=advanced&do=furlDelete&key={$k}" )->csrf(),
					'data'	=> array( 'confirm' => '', 'confirmMessage' => Member::loggedIn()->language()->addToStack('revert_confirm') )
				);
			}

			return $return;
		};

		return ( Request::i()->advancedSearchForm ? '' : Theme::i()->getTemplate('global')->message( 'furl_warning', 'warning i-margin_1 i-margin-bottom_2' ) ) . $table;
	}
	
	/**
	 * Add/Edit FURL
	 *
	 * @return	void
	 */
	protected function furlForm() : void
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_furls' );
		
		$current	= NULL;
		$config		= Friendly::furlDefinition();
		if ( Request::i()->key )
		{
			$current = ( isset( $config[ Request::i()->key ] ) ) ? $config[ Request::i()->key ] : NULL;
		}

		$form = new Form;
		$form->add( new Text( 'furl_friendly', $current ? $current['friendly'] : '', FALSE, array( 'placeholder' => Member::loggedIn()->language()->addToStack('furl_friendly_placeholder') ), function( $val )
		{
			if( mb_substr( $val, 0, 3 ) == '{?}' )
			{
				throw new DomainException( 'furl_too_greedy' );
			}
		},
		Settings::i()->base_url . ( Settings::i()->htaccess_mod_rewrite ? '' : 'index.php?/' ) ) );
		$form->add( new Text( 'furl_real', $current ? $current['real'] : '', FALSE, array(), NULL, Settings::i()->base_url . 'index.php?' ) );
		
		if ( $values = $form->values() )
		{
			$furl = Settings::i()->furl_configuration ? json_decode( Settings::i()->furl_configuration, TRUE ) : array();
			
			$currentDefinition = Friendly::furlDefinition();
			$appTopLevel = NULL;
			$appIsDefault = FALSE;
			$alias = NULL;
			$verify = NULL;
			$seoPagination = NULL;
			$friendly = $values['furl_friendly'];
			if ( Request::i()->key )
			{
				if ( isset( $currentDefinition[ Request::i()->key ]['alias'] ) )
				{
					$alias = $currentDefinition[ Request::i()->key ]['alias'];
				}
				
				if ( isset( $currentDefinition[ Request::i()->key ]['verify'] ) )
				{
					$verify = $currentDefinition[ Request::i()->key ]['verify'];
				}

				if ( isset( $currentDefinition[ Request::i()->key ]['seoPagination'] ) )
				{
					$seoPagination = $currentDefinition[ Request::i()->key ]['seoPagination'];
				}

				if ( isset( $currentDefinition[ Request::i()->key ]['with_top_level'] ) )
				{
					$appIsDefault = TRUE;
					$appTopLevel = mb_substr( $currentDefinition[ Request::i()->key ]['with_top_level'], 0, -mb_strlen( $currentDefinition[ Request::i()->key ]['friendly'] . '/' ) );
				}
				
				if ( isset( $currentDefinition[ Request::i()->key ]['without_top_level'] ) )
				{
					$appIsDefault = FALSE;
					if ( $currentDefinition[ Request::i()->key ]['without_top_level'] )
					{
						$appTopLevel = mb_substr( $currentDefinition[ Request::i()->key ]['friendly'], 0, -mb_strlen( $currentDefinition[ Request::i()->key ]['without_top_level'] . '/' ) );
						$friendly = rtrim( preg_replace( '/^' . preg_quote( $appTopLevel, '/' ) . '(\/|$)/', '', $friendly ), '/' );
					}
				}
			}

			$save = Friendly::buildFurlDefinition( $friendly, $values['furl_real'], $appTopLevel, $appIsDefault, $alias, TRUE, $verify, NULL, $seoPagination );
															
			if ( Request::i()->key )
			{
				$furl[ Request::i()->key ] = $save;
			}
			else
			{
				ksort( $furl, SORT_NATURAL );
				$keys = array_keys( $furl );
				$lastKey = str_replace( 'key', '', end( $keys ) );
				$key = 'key' . ( (int)$lastKey + 1 );
				$furl[ $key ] = $save;
			}
			
			Session::i()->log( 'acplogs__advanced_furl_edited' );
			
			$newValue = json_encode( $furl );
			Settings::i()->changeValues( array( 'furl_configuration' => $newValue ) );
			
			/* Clear Sidebar Caches */
			Widget::deleteCaches();

			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&tab=furl' ), 'saved' );
		}
		
		Output::i()->output = $form;
	}
	
	/**
	 * Delete FURL
	 *
	 * @return	void
	 */
	protected function furlDelete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_furls' );
		Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		$furlDefinition = Settings::i()->furl_configuration ? json_decode( Settings::i()->furl_configuration, TRUE ) : array();
		if( isset( $furlDefinition[ Request::i()->key ] ) )
		{
			unset( $furlDefinition[ Request::i()->key ] );
			$newValue = json_encode( $furlDefinition );
			Settings::i()->changeValues( array( 'furl_configuration' => $newValue ) );
		}
		
		Session::i()->log( 'acplogs__advanced_furl_deleted' );
		
		Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&tab=furl' ), 'saved' );
	}
	
	/**
	 * Revert FURL customisation
	 *
	 * @return	void
	 */
	protected function furlRevert() : void
	{
		Dispatcher::i()->checkAcpPermission( 'advanced_manage_furls' );
		Session::i()->csrfCheck();

		Settings::i()->changeValues( array( 'furl_configuration' => NULL ) );
		unset( Store::i()->furl_configuration );
		
		Session::i()->log( 'acplogs__advanced_furl_reverted' );
		
		Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=advanced&tab=furl' ), 'saved' );
	}
}