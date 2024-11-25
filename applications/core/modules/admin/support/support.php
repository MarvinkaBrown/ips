<?php
/**
 * @brief		Health Dashboard
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		4 December 2020
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\core\AdminNotification;
use IPS\core\Advertisement;
use IPS\core\Setup\Upgrade;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Db\Select;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Events\ListenerType;
use IPS\File;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Callback;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Text;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Log;
use IPS\Login\Handler;
use IPS\Member;
use IPS\Notification;
use IPS\Output;
use IPS\Output\Javascript;
use IPS\Output\Plugin\Filesize;
use IPS\Redis;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use RedisException;
use Throwable;
use UnderflowException;
use UnexpectedValueException;
use function chr;
use function count;
use function defined;
use function function_exists;
use function in_array;
use function intval;
use function is_array;
use function is_int;
use function method_exists;
use function strtoupper;
use function substr;
use function trim;
use const IPS\CACHE_METHOD;
use const IPS\CIC;
use const IPS\CIC2;
use const IPS\IPS_ALPHA_BUILD;
use const IPS\LONG_REQUEST_TIMEOUT;
use const IPS\NO_WRITES;
use const IPS\STORE_METHOD;
use const IPS\USE_DEVELOPMENT_BUILDS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Health dashboard
 */
class support extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	Define the "large log table" size in bytes
	 */
	public const LARGE_LOG_TABLE_SIZE = 2147483648;	// 2GB

	/**
	 * @brief	Define the number of log repeats considered high
	 */
	public const LARGE_NUMBER_LOG_REPEATS = 10;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'get_support' );
		parent::execute();
	}

	/**
	 * Support Wizard
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Build the guide search form */
		$form = new Form( 'form', 'continue' );
		$form->class = 'ipsForm--vertical ipsForm--support-wizard';
		$form->add( new Text( 'support_advice_search', NULL, NULL, array( 'placeholder' => Member::loggedIn()->language()->addToStack('health__guides_form') ), NULL, NULL, NULL, 'support_advice_search' ) );
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('get_support');
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'support/dashboard.css', 'core', 'admin' ) );
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('admin_support.js', 'core', 'admin') );
		Output::i()->output	= Theme::i()->getTemplate( 'support' )->dashboard( $this->_getBlocks(), $this->getLogChart(), $form, $this->_getFeaturedGuides(), $this->_getBulletins() );
	}

	/**
	 * Get featured guides
	 *
	 * @return	array
	 */
	protected function _getFeaturedGuides() : array
	{
		try
		{
			$response = Url::ips( 'guides' )->setQueryString( 'featured', 1 )->request()->get();

			if( $response->httpResponseCode !== 200 )
			{
				throw new \IPS\Http\Request\Exception;
			}

			return $response->decodeJson();
		}
		catch( \IPS\Http\Request\Exception $e )
		{
			return array();
		}
	}

	/**
	 * Get AdminCP bulletins
	 *
	 * @return	array
	 */
	protected function _getBulletins() : array
	{
		try
		{
			/* We will get the last 10 bulletins, process if they apply based on the condition to show and whether they are less than 12 months old, and return the most recent 3 */
			$bulletins = array();
			foreach( Db::i()->select( '*', 'core_ips_bulletins', NULL, 'id DESC', 10 ) as $bulletin )
			{
				/* If it's a year old or older, inherently ignore */
				if( $bulletin['cached'] < time() - ( 60 * 60 * 24 * 365 ) )
				{
					continue;
				}

				if( $bulletin['min_version'] AND $bulletin['min_version'] > Application::load('core')->long_version )
				{
					continue;
				}

				if( $bulletin['max_version'] AND $bulletin['max_version'] < Application::load('core')->long_version )
				{
					continue;
				}

				/* If not cached in the last hour, check it still exists */
				try
				{
					if( ( time() - $bulletin['cached'] ) > 3600 )
					{
						$request = Url::ips("bulletin/{$bulletin['id']}")->request( 2 )->get();

						switch( (int) $request->httpResponseCode )
						{
							case 410:
									Db::i()->delete( 'core_ips_bulletins', [ 'id=?', $bulletin['id'] ] );
									continue 2;
							default:
									Db::i()->update( 'core_ips_bulletins', [ 'cached' => ( time() + 3600 - 900 ) ], [ 'id=?', $bulletin['id'] ] );
								break;
						}
					}
				}
				catch( \IPS\Http\Request\Exception $e ) {}

				/* If we have conditions, process them */
				if( $bulletin['conditions'] )
				{
					try
					{
						$show = @eval( $bulletin['conditions'] );
					}
					catch ( \Exception | Throwable $e )
					{
						$show = FALSE;
					}
				}
				else
				{
					$show = TRUE;
				}

				if( $show )
				{
					$bulletins[] = $bulletin;
				}

				/* If we have 3, stop now */
				if( count( $bulletins ) === 3 )
				{
					break;
				}
			}

			return $bulletins;
		}
		catch( Exception $e )
		{
			return array();
		}
	}

	/**
	 * Search guides
	 *
	 * @return void
	 */
	protected function guideSearch() : void
	{
		Output::i()->json( array() );
	}

	/**
	 * Get the block
	 *
	 * @return	void
	 */
	protected function getBlock() : void
	{
		/* If we are fixing things, run CSRF check */
		if( Request::i()->fix )
		{
			Session::i()->csrfCheck();
		}

		$blockName = '_showBlock' . mb_convert_case( Request::i()->block, MB_CASE_TITLE );

		if( method_exists( $this, $blockName ) )
		{
			Output::i()->json( $this->$blockName() );
		}
		else
		{
			Output::i()->error( 'block_not_found', '3C338/3', 404 );
		}
	}

	/**
	 * Get block: PHP
	 *
	 * @return	array
	 */
	protected function _showBlockPhp() : array
	{
		$requirements = CIC ? array( 'list' => array(), 'failures' => 0, 'advice' => 0 ) : $this->_checkRequirements( 'PHP' );

		/* Reformat entries if they exist */
		if( isset( $requirements['list']['version'] ) )
		{
			$requirements['list']['version']['element']	= 'version';
			$requirements['list']['version']['body']	= $requirements['list']['version']['detail'];
			$requirements['list']['version']['detail']	= Member::loggedIn()->language()->addToStack( $requirements['list']['version']['critical'] ? 'health_check_update_required' : 'health_check_update_recommended' );
		}

		foreach( $requirements['list'] as $k => $v )
		{
			$k = trim( $k );
			if( $v['advice'] )
			{
				$requirements['list'][ $k ]['element']	= $k;
				$requirements['list'][ $k ]['body']		= $v['detail'];

				switch( $k )
				{
					case 'php':
						$requirements['list'][ $k ]['detail'] = Member::loggedIn()->language()->addToStack( 'health_check_update_recommended' );
					break;

					case 'curl':
						$requirements['list'][ $k ]['detail'] = Member::loggedIn()->language()->addToStack( 'health_check_curlupdate_recommended' );
					break;

					default:
						$requirements['list'][ $k ]['detail'] = Member::loggedIn()->language()->addToStack( 'health__php_extension', FALSE, array( 'sprintf' => array( $k ) ) );
				}
			}
		}

		return array(
			'html'				=> Theme::i()->getTemplate( 'support' )->supportBlockList( $requirements['list'] ),
			'criticalIssues'	=> $requirements['failures'],
			'recommendedIssues'	=> $requirements['advice']
		);
	}

	/**
	 * Get block: MySQL
	 *
	 * @return	array
	 */
	protected function _showBlockMysql() : array
	{
		/* Check other requirements */
		$requirements = CIC ? array( 'list' => array(), 'failures' => 0, 'advice' => 0 ) : $this->_checkRequirements( 'MySQL' );

		/* Check whether there are any db changes needed */
		$databaseChanges = $this->_databaseChecker( (bool) Request::i()->fix );

		if( $databaseChanges )
		{
			$requirements['failures']++;
			$requirements['list'][] = array(
				'critical'		=> TRUE,
				'advice'		=> FALSE,
				'success'		=> FALSE,
				'link'			=> Url::internal( "app=core&module=support&controller=support&do=getBlock&block=mysql&fix=1" ),
				'detail'		=> Member::loggedIn()->language()->addToStack('health_database_check_fail')
			);
		}

		/* Reformat entries if they exist */
		if( isset( $requirements['list']['compact'] ) )
		{
			$requirements['list']['compact']['element']	= 'compact';
			$requirements['list']['compact']['body']	= $requirements['list']['compact']['detail'];
			$requirements['list']['compact']['detail']	= Member::loggedIn()->language()->addToStack('health_database_compact_fail');
		}

		if( isset( $requirements['list']['version'] ) )
		{
			$requirements['list']['version']['element']	= 'version';
			$requirements['list']['version']['body']	= $requirements['list']['version']['detail'];
			$requirements['list']['version']['detail']	= Member::loggedIn()->language()->addToStack( $requirements['list']['version']['critical'] ? 'health_check_update_required' : 'health_check_update_recommended' );
		}

		if ( !CIC AND count( iterator_to_array( Db::i()->query( "SHOW TABLE STATUS WHERE Engine!='InnoDB'" ) ) ) )
		{
			$requirements['advice']++;
			$requirements['list'][] = array(
				'critical'		=> FALSE,
				'advice'		=> TRUE,
				'success'		=> FALSE,
				'element'		=> 'innodb',
				'body'			=> Member::loggedIn()->language()->addToStack('health_innodb_details'),
				'detail'		=> Member::loggedIn()->language()->addToStack('health__mysql_innodb'),
				'button'		=> [ 'lang' => 'storage_engine_run', 'href' => Url::internal( "app=core&module=support&controller=support&do=fixStorageEngine" ), 'css' => 'ipsButton--primary' ]
			);
		}

		if( Settings::i()->getFromConfGlobal('sql_utf8mb4') !== TRUE )
		{
			$requirements['advice']++;
			$requirements['list'][] = array(
				'critical'		=> FALSE,
				'advice'		=> TRUE,
				'success'		=> FALSE,
				'element'		=> 'utf8mb4',
				'body'			=> Member::loggedIn()->language()->addToStack( CIC ? 'utf8mb4_generic_explain_cic' : 'utf8mb4_generic_explain' ),
				'detail'		=> Member::loggedIn()->language()->addToStack('health__mysql_utf8mb4')
			);
		}

		return array(
			'html'				=> Theme::i()->getTemplate( 'support' )->supportBlockList( $requirements['list'] ),
			'criticalIssues'	=> $requirements['failures'],
			'recommendedIssues'	=> $requirements['advice']
		);
	}

	/**
	 * Get block: Vapid
	 *
	 * @return	array
	 */
	protected function _showBlockVapid() : array
	{
		/* Check other requirements */
		$requirements = array( 'list' => array(), 'failures' => 0, 'advice' => 0 );

		if( ! CIC2 and ! function_exists('gmp_init') )
		{
			$requirements['advice']++;
			$requirements['list'][] = array(
				'critical'		=> FALSE,
				'advice'		=> TRUE,
				'success'		=> FALSE,
				'element'		=> 'vapidNoGmp',
				'body'			=> Member::loggedIn()->language()->addToStack('acp_notifications_cannot_use_web_push'),
				'detail'		=> Member::loggedIn()->language()->addToStack('health_vapid_gmp_check_fail')
			);
		}
		elseif ( ! Settings::i()->vapid_public_key )
		{
			$requirements['failures']++;
			$requirements['list'][] = array(
				'critical' => TRUE,
				'advice' => FALSE,
				'success' => FALSE,
				'link'	  => Url::internal( "app=core&module=support&controller=support&do=vapidKeys" )->csrf(),
				'skipDialog'	=> TRUE,
				'detail' => Member::loggedIn()->language()->addToStack( 'health_vapid_key_check_fail' )
			);
		}

		return array(
			'html'				=> Theme::i()->getTemplate( 'support' )->supportBlockList( $requirements['list'] ),
			'criticalIssues'	=> $requirements['failures'],
			'recommendedIssues'	=> $requirements['advice']
		);
	}

	/**
	 * Create new vapid keys
	 *
	 * @return void
	 */
	protected function vapidKeys() : void
	{
		Session::i()->csrfCheck();

		if ( ! Settings::i()->vapid_public_key )
		{
			try
			{
				$vapid = Notification::generateVapidKeys();
				Settings::i()->changeValues( array('vapid_public_key' => $vapid['publicKey'], 'vapid_private_key' => $vapid['privateKey']) );
			}
			catch ( \Exception $ex )
			{
				Log::log( $ex, 'create_vapid_keys' );
				Output::i()->error( '', '2C338/4', 403, Member::loggedIn()->language()->addToStack( 'health_vapid_key_check_fail_exception', FALSE, [ 'sprintf' => $ex->getMessage() ] ) );
			}
		}

		if( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=core&module=support&controller=support' ), 'health_vapid_key_check_fail_fixed' );
		}
	}

	/**
	 * Get block: Invision Community
	 *
	 * @return	array
	 */
	protected function _showBlockVersion() : array
	{
		$requirements = array( 'advice' => 0, 'failures' => 0, 'list' => array() );

		/* Check for updates available */
		if( $updates = $this->_checkUpgrades() )
		{
			if( is_array( $updates ) )
			{
				$requirements['list'][] = array(
					'critical'		=> FALSE,
					'advice'		=> FALSE,
					'success'		=> FALSE,
					'element'		=> 'patch',
					'body'			=> Theme::i()->getTemplate( 'support' )->patchAvailable( $updates ),
					'detail'		=> Member::loggedIn()->language()->addToStack('upgrade_check_patchavail'),
					'button'		=> array( 'lang' => 'upgrade_apply_patch', 'href' => Url::internal( "app=core&module=system&controller=upgrade&_new=1&patch=1" ), 'css' => 'ipsButton--intermediate' )
				);
			}
			else
			{
				if( $updates === -1 )
				{
					$requirements['failures']++;
				}
				else
				{
					$requirements['advice']++;
				}

				$requirements['list'][] = array(
					'critical'		=> ( $updates === -1 ),
					'advice'		=> !( $updates === -1 ),
					'success'		=> FALSE,
					'link'			=> Url::internal( "app=core&module=system&controller=upgrade&_new=1" ),
					'skipDialog'	=> TRUE,
					'detail'		=> ( $updates === -1 ) ? Member::loggedIn()->language()->addToStack('upgrade_check_security') : Member::loggedIn()->language()->addToStack('upgrade_check_fail')
				);
			}
		}
		else
		{
			$requirements['list'][] = array(
				'critical'		=> FALSE,
				'advice'		=> FALSE,
				'success'		=> TRUE,
				'detail'		=> Member::loggedIn()->language()->addToStack('upgrade_check_ok')
			);
		}

		/* Run MD5 check */
		$modifiedFiles = $this->_md5Checker( (bool) Request::i()->fix );

		if( $modifiedFiles )
		{
			$requirements['failures']++;
			$requirements['list'][] = array(
				'critical'		=> TRUE,
				'advice'		=> FALSE,
				'success'		=> FALSE,
				'link'			=> Url::internal( "app=core&module=support&controller=support&do=getBlock&block=version&fix=1" ),
				'detail'		=> Member::loggedIn()->language()->addToStack('md5_check_fail')
			);
		}

		return array(
			'html'				=> Theme::i()->getTemplate( 'support' )->supportBlockList( $requirements['list'] ),
			'criticalIssues'	=> $requirements['failures'],
			'recommendedIssues'	=> $requirements['advice']
		);
	}

	/**
	 * Get block: Third Party
	 *
	 * @return	array
	 */
	protected function _showBlockThirdparty() : array
	{
		$requirements = array( 'advice' => 0, 'failures' => 0, 'list' => array() );

		$count = $this->_getThirdPartyCount();

		if( $count )
		{
			$requirements['advice']++;
		}

		$requirements['list'][] = array(
			'critical'		=> FALSE,
			'advice'		=> (bool) $count,
			'success'		=> FALSE,
			'link'			=> Url::internal( 'app=core&module=support&controller=support&do=thirdparty' ),
			'detail'		=> Member::loggedIn()->language()->addToStack( 'health__thirdparty_count', FALSE, array( 'pluralize' => array( $count ) ) ),
			'dialogTitle'	=> 'health_thirdparty_disabled'
		);

		$appUpdates		= 0;
		$pluginUpdates	= 0;

		foreach( Application::applications() as $app )
		{
			if( !in_array( $app->directory, IPS::$ipsApps ) AND $app->availableUpgrade( TRUE ) )
			{
				$appUpdates++;
			}
		}

		if( $appUpdates )
		{
			$requirements['advice']++;
			$requirements['list'][] = array(
				'critical'		=> FALSE,
				'advice'		=> TRUE,
				'success'		=> FALSE,
				'link'			=> Url::internal( 'app=core&module=applications&controller=applications' ),
				'detail'		=> Member::loggedIn()->language()->addToStack('health__thirdparty_appupdates', FALSE, array( 'sprintf' => array( $appUpdates ) ) )
			);
		}

		return array(
			'html'				=> Theme::i()->getTemplate( 'support' )->supportBlockList( $requirements['list'] ),
			'criticalIssues'	=> $requirements['failures'],
			'recommendedIssues'	=> $requirements['advice']
		);
	}

	/**
	 * Get block: Caching
	 *
	 * @return	array
	 */
	protected function _showBlockCaching() : array
	{
		$requirements = array( 'advice' => 0, 'failures' => 0, 'list' => array() );

		/* Check if Redis is being used */
		$redis = NULL;
		
		if ( !CIC and CACHE_METHOD == 'Redis' or STORE_METHOD == 'Redis' )
		{
			try
			{
				$redis = Redis::i()->info();
			}
			catch( RedisException $e )
			{
				$requirements['failures']++;
				$requirements['list'][] = array(
					'critical'		=> TRUE,
					'advice'		=> FALSE,
					'success'		=> FALSE,
					'element'		=> 'redisfail',
					'body'			=> Member::loggedIn()->language()->addToStack('health__cache_redisfail_detail'),
					'button'		=> array( 'lang' => 'health_view_redis_config', 'href' => Url::internal( 'app=core&module=settings&controller=advanced&tab=datastore' ), 'css' => 'ipsButton--intermediate' ),
					'detail'		=> Member::loggedIn()->language()->addToStack('health__cache_redisfail')
				);
			}
		}

		if( $redis and !CIC )
		{
			if( isset( $redis['total_system_memory'] ) OR isset( $redis['maxmemory'] ) )
			{
				$detail = Member::loggedIn()->language()->addToStack('health__cache_redis', FALSE, array( 'sprintf' => array( $redis['redis_version'], $redis['used_memory_human'], $redis['maxmemory'] ? $redis['maxmemory_human'] : $redis['total_system_memory_human'] ) ) );
			}
			else
			{
				$detail = Member::loggedIn()->language()->addToStack('health__cache_redis_nototal', FALSE, array( 'sprintf' => array( $redis['redis_version'], $redis['used_memory_human'] ) ) );
			}

			$requirements['list'][] = array(
				'critical'		=> FALSE,
				'advice'		=> FALSE,
				'success'		=> FALSE,
				'link'			=> Url::internal( 'app=core&module=support&controller=redis' ),
				'detail'		=> $detail,
				'button'		=> array( 'lang' => 'health_view_redis_config' ),
				'dialogTitle'	=> 'health__more_information',
			);
		}

		/* Make a request so we can inspect the response headers */
		try
		{
			$request = Url::internal( "app=core&module=system&controller=metatags&do=manifest", "front", "manifest" )
				->request()
				->get();

			$headerKeys = is_array( $request->httpHeaders ) ? array_map( 'mb_strtolower', array_keys( $request->httpHeaders ) ) : [];

			if( in_array( 'cf-cache-status', $headerKeys ) )
			{
				$requirements['list'][] = array(
					'critical'		=> FALSE,
					'advice'		=> FALSE,
					'success'		=> FALSE,
					'learnmore'     => TRUE,
					'dialogTitle'   => Member::loggedIn()->language()->addToStack( 'health_learn_more'),
					'element'		=> 'cloudflare',
					'body'			=> Member::loggedIn()->language()->addToStack('health__cache_cloudflare_details'),
					'detail'		=> Member::loggedIn()->language()->addToStack('health__cache_cloudflare')
				);
			}

			if( in_array( 'x-varnish', $headerKeys ) )
			{
				$requirements['list'][] = array(
					'critical'		=> FALSE,
					'advice'		=> FALSE,
					'success'		=> FALSE,
					'detail'		=> Member::loggedIn()->language()->addToStack('health__cache_varnish')
				);
			}

			if( in_array( 'x-akamai-transformed', $headerKeys ) )
			{
				$requirements['list'][] = array(
					'critical'		=> FALSE,
					'advice'		=> FALSE,
					'success'		=> FALSE,
					'detail'		=> Member::loggedIn()->language()->addToStack('health__cache_akamai')
				);
			}
		}
		catch( \IPS\Http\Request\Exception $e ) { }

		return array(
			'html'				=> Theme::i()->getTemplate( 'support' )->supportBlockList( $requirements['list'] ),
			'criticalIssues'	=> $requirements['failures'],
			'recommendedIssues'	=> $requirements['advice']
		);
	}

	/**
	 * Get block: Server
	 *
	 * @return	array
	 */
	protected function _showBlockServer() : array
	{
		if( CIC )
		{
			return array(
				'html'				=> Theme::i()->getTemplate( 'support' )->supportBlockList( array() ),
				'criticalIssues'	=> 0,
				'recommendedIssues'	=> 0
			);
		}

		$writeablesKey	= Member::loggedIn()->language()->addToStack('requirements_file_system');
		$requirements	= $this->_checkRequirements( $writeablesKey );

		/* Windows server? */
		if( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' )
		{
			$requirements['advice']++;
			$requirements['list'][] = array(
				'critical'		=> FALSE,
				'advice'		=> TRUE,
				'success'		=> FALSE,
				'element'		=> 'windows',
				'body'			=> Member::loggedIn()->language()->addToStack('health__server_windows'),
				'detail'		=> Member::loggedIn()->language()->addToStack('health__server_windows_title')
			);
		}

		/* Reformat some entries */
		if( isset( $requirements['list']['tmp'] ) )
		{
			$requirements['list']['tmp']['element']	= 'tmp';
			$requirements['list']['tmp']['body']	= $requirements['list']['tmp']['detail'];
			$requirements['list']['tmp']['detail']	= Member::loggedIn()->language()->addToStack( 'health__server_tmp' );
		}

		if( isset( $requirements['list']['suhosin'] ) )
		{
			$requirements['list']['suhosin']['element']	= 'suhosin';
			$requirements['list']['suhosin']['body']	= $requirements['list']['suhosin']['detail'];
			$requirements['list']['suhosin']['detail']	= Member::loggedIn()->language()->addToStack( 'health__server_suhosin' );
		}

		foreach ( array( 'applications', 'datastore', 'uploads' ) as $dir )
		{
			if( isset( $requirements['list'][ $dir ] ) )
			{
				$requirements['list'][ $dir ]['element']	= $dir;
				$requirements['list'][ $dir ]['body']		= $requirements['list'][ $dir ]['detail'];
				$requirements['list'][ $dir ]['detail']		= Member::loggedIn()->language()->addToStack( 'health__server_filesystem', FALSE, array( 'sprintf' => array( $dir ) ) );
			}
		}

		foreach( array_keys( $requirements['list'] ) as $key )
		{
			if( mb_strpos( $key, 'filesystem' ) === 0 )
			{
				$class = File::getClass( (int) mb_substr( $key, 10 ) );
				$requirements['list'][ $key ]['element']	= $key;
				$requirements['list'][ $key ]['body']		= $requirements['list'][ $key ]['detail'];
				$requirements['list'][ $key ]['detail']		= Member::loggedIn()->language()->addToStack( 'health__server_filestorage', FALSE, array( 'sprintf' => array( $class->displayName( $class->configuration ) ) ) );
			}
		}

		/* Check connections and server time */
		try
		{
			$result = time();
		}
		catch ( \Exception $e )
		{
			$result = $e->getMessage();
		}

		if( !is_int( $result ) )
		{
			$requirements['failures']++;
			$requirements['list'][] = array(
				'critical'		=> TRUE,
				'advice'		=> FALSE,
				'success'		=> FALSE,
				'element'		=> 'connection',
				'body'			=> Theme::i()->getTemplate( 'support' )->fixConnection( $result ),
				'detail'		=> Member::loggedIn()->language()->addToStack('connection_check_fail')
			);
		}
		else if( abs( $result - time() ) > 30 )
		{
			$requirements['failures']++;
			$requirements['list'][] = array(
				'critical'		=> TRUE,
				'advice'		=> FALSE,
				'success'		=> FALSE,
				'element'		=> 'servertime',
				'body'			=> Member::loggedIn()->language()->addToStack('sever_time_fail_desc', FALSE, array( 'sprintf' => array( (string)  new DateTime ) ) ),
				'detail'		=> Member::loggedIn()->language()->addToStack('server_time_fail')
			);
		}

		return array(
			'html'				=> Theme::i()->getTemplate( 'support' )->supportBlockList( $requirements['list'] ),
			'criticalIssues'	=> $requirements['failures'],
			'recommendedIssues'	=> $requirements['advice']
		);
	}
	
	/**
	 * Return all IPS log tables which can become quite large
	 *
	 * @return array<string, Url>
	 */
	public function getLogTables(): array
	{
		return [
			'core_log' => Url::internal( 'app=core&module=support&controller=systemLogs&do=logSettings' ),
			'core_error_logs' => Url::internal( 'app=core&module=support&controller=errorLogs&do=settings&searchResult=prune_log_error' ),
			'core_mail_error_logs' => Url::internal( 'app=core&module=settings&controller=email&do=errorLogSettings' ),
			'core_edit_history' => Url::internal( 'app=core&module=settings&controller=posting&tab=general&searchResult=edit_log_prune' ),
			'core_api_logs' => Url::internal( 'app=core&module=applications&controller=apiLogs&do=settings' ),
		];
	}
	
	/**
	 * Get block: Logs
	 *
	 * @return	array
	 */
	protected function _showBlockLogs() : array
	{
		$requirements = array( 'advice' => 0, 'failures' => 0, 'list' => array() );
		
		foreach( $this->getLogTables() as $table => $url )
		{
			$size = $this->_getLogTableSize( $table );

			if( $size === NULL OR $size > static::LARGE_LOG_TABLE_SIZE )
			{
				if( $size !== NULL )
				{
					$size = Filesize::humanReadableFilesize( $size );
				}
				else
				{
					$size = Member::loggedIn()->language()->addToStack('unavailable');
				}

				$requirements['failures']++;
				$requirements['list'][] = array(
					'critical'		=> TRUE,
					'advice'		=> FALSE,
					'success'		=> FALSE,
					'element'		=> $table . 'logtablesize',
					'body'			=> Member::loggedIn()->language()->addToStack('health__logs_large_desc', FALSE, array( 'sprintf' => array( $table, $size, (string) $url ) ) ),
					'detail'		=> Member::loggedIn()->language()->addToStack('health__logs_large', FALSE, array( 'sprintf' => array( $table ) ) )
				);
			}
		}

		/* Check the last 500 system logs for reoccurring entries */
		$lastIds		= iterator_to_array( Db::i()->select( 'id', 'core_log', NULL, 'id DESC', 500 ) );
		$repeatedLogs	= array();

		foreach( Db::i()->select( 'message, COUNT(*) as occurrences', 'core_log', array( Db::i()->in( 'id', $lastIds ) ), 'occurrences DESC', NULL, 'message' ) as $log )
		{
			if( $log['occurrences'] > static::LARGE_NUMBER_LOG_REPEATS )
			{
				$repeatedLogs[ $log['message'] ] = $log['occurrences'];
			}
		}

		if( count( $repeatedLogs ) )
		{
			$requirements['advice']++;
			$requirements['list'][] = array(
				'critical'		=> FALSE,
				'advice'		=> TRUE,
				'success'		=> FALSE,
				'element'		=> 'repeatedlogs',
				'body'			=> Theme::i()->getTemplate( 'support' )->fixRepeatLogs( $repeatedLogs ),
				'detail'		=> Member::loggedIn()->language()->addToStack('health__logs_repeats'),
				'button'		=> array( 'lang' => 'health_view_system_log', 'href' => Url::internal( "app=core&module=support&controller=systemLogs" )->csrf(), 'css' => 'ipsButton--intermediate' )
			);
		}

		if( Settings::i()->debug_log_enabled )
		{
			$days = DateTime::create()->diff( DateTime::ts( Settings::i()->debug_log_enabled ) )->days;
			if( $days > 30 )
			{
				$requirements['failures']++;
				$requirements['list'][] = array(
					'critical' => true,
					'advice' => false,
					'success' => false,
					'element' => 'debugLogging',
					'detail' => Member::loggedIn()->language()->addToStack( 'health__logs_debug', true, [ 'sprintf' => $days ] ),
					'body' => Member::loggedIn()->language()->addToStack( 'health__logs_debug_desc', true, [ 'sprintf' => $days ] ),
					'button' => array( 'lang' => 'debug_log_disable', 'href' => Url::internal( "app=core&module=support&controller=support&do=disableDebug" )->csrf(), 'css' => 'ipsButton--intermediate' )
				);
			}
			elseif( $days > 7 )
			{
				$requirements['advice']++;
				$requirements['list'][] = array(
					'critical' => false,
					'advice' => true,
					'success' => false,
					'element' => 'debugLogging',
					'detail' => Member::loggedIn()->language()->addToStack( 'health__logs_debug', true, [ 'sprintf' => $days ] ),
					'body' => Member::loggedIn()->language()->addToStack( 'health__logs_debug_desc', true, [ 'sprintf' => $days ] ),
					'button' => array( 'lang' => 'debug_log_disable', 'href' => Url::internal( "app=core&module=support&controller=support&do=disableDebug" )->csrf(), 'css' => 'ipsButton--intermediate' )
				);
			}
		}

		return array(
			'html'				=> Theme::i()->getTemplate( 'support' )->supportBlockList( $requirements['list'] ),
			'criticalIssues'	=> $requirements['failures'],
			'recommendedIssues'	=> $requirements['advice']
		);
	}

	/**
	 * Generic requirements check
	 *
	 * @param	string	$category	Requirements category
	 * @return	array
	 */
	protected function _checkRequirements( string $category ) : array
	{
		/* Check required and recommended PHP versions and extensions */
		$requirements = Upgrade::systemRequirements();

		$failedRequirements		= 0;
		$failedRecommendations	= 0;
		$listItems				= array();

		if( !empty( $requirements['requirements'] ) AND !empty( $requirements['requirements'][ $category] ) )
		{
			foreach( $requirements['requirements'][ $category ] as $key => $requirement )
			{
				if( !$requirement['success'] )
				{
					$failedRequirements++;
					$listItems[ $key ] = array(
						'critical'		=> TRUE,
						'advice'		=> FALSE,
						'success'		=> FALSE,
						'link'			=> NULL,
						'detail'		=> $requirement['message']
					);

					if( isset( $requirement['short'] ) )
					{
						$listItems[ $key ]['element']	= $key;
						$listItems[ $key ]['body']		= $listItems[ $key ]['detail'];
						$listItems[ $key ]['detail']	= $requirement['short'];
					}
				}
			}
		}

		if( !empty( $requirements['advice'] ) AND !empty( $requirements['advice'][ $category ] ) )
		{
			foreach( $requirements['advice'][ $category ] as $key => $requirement )
			{
				$failedRecommendations++;
				$listItems[ $key ] = array(
					'critical'		=> FALSE,
					'advice'		=> TRUE,
					'success'		=> FALSE,
					'link'			=> NULL,
					'detail'		=> $requirement
				);
			}
		}

		return array( 'failures' => $failedRequirements, 'advice' => $failedRecommendations, 'list' => $listItems );
	}

	/**
	 * Check for upgrades/patches
	 *
	 * @return	bool|int|array
	 */
	protected function _checkUpgrades() : bool|int|array
	{
		return FALSE;
	}

	/**
	 * Clear caches
	 *
	 * @return void
	 */
	protected function clearCaches() : void
	{
		/* Check CSRF Key*/
		Session::i()->csrfCheck();

		/* Clear JS Maps first */
		Output::clearJsFiles();
		
		/* Reset theme maps to make sure bad data hasn't been cached by visits mid-setup */
		Theme::deleteCompiledCss();
		Theme::deleteCompiledResources();
		
		foreach( Theme::themes() as $id => $set )
		{
			/* Invalidate template disk cache */
			$set->cache_key = md5( microtime() . mt_rand( 0, 1000 ) );
			$set->save();
		}

		/* Reset forum last post info, so it can be rebuilt on the fly */
		Db::i()->update( 'forums_forums', array( 'last_post_data' => null ) );

		/* Reset compiled JS files, so they can be rebuilt on the fly */
		foreach( Lang::getEnabledLanguages() as $lang )
		{
			Javascript::clearLanguage( $lang );
		}
		
		Store::i()->clearAll();
		Cache::i()->clearAll();

		Session::i()->log( 'acplog__support_tool_caches_cleared' );

		if( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=core&module=support&controller=support' ) );
		}
	}

	/**
	 * Step 2: Disable Third Party Customizations
	 *
	 * @return	void
	 */
	protected function thirdparty() : void
	{
		Session::i()->csrfCheck();

		if( isset( Request::i()->enable ) )
		{
			if( Request::i()->enable )
			{
				$this->_enableThirdParty();
			}
			else
			{
				$this->_disableThirdParty();
			}
		}
		else
		{
			/* Display */
			Output::i()->output = Theme::i()->getTemplate( 'support' )->thirdPartyItems(
				$this->_thirdPartyApps(),
				$this->_thirdPartyTheme(),
				$this->_thirdPartyAds()
			);
		}
	}

	/**
	 * Disable third party customizations
	 *
	 * @return void
	 */
	protected function _disableThirdParty() : void
	{		
		/* Init */
		$disabledApps = array();
		$disabledAppNames = array();
		$restoredDefaultTheme = FALSE;
		$disabledAds = array();

		/* Do we need to disable any third party apps? */
		if ( !NO_WRITES )
		{		
			/* Loop Apps */
			foreach ( $this->_thirdPartyApps() as $app )
			{
				Db::i()->update( 'core_applications', array( 'app_enabled' => 0 ), array( 'app_id=?', $app->id ) );
				
				$disabledApps[] = $app->directory;
				$disabledAppNames[ $app->directory ] = $app->_title;
			}
			
			if ( count( $disabledApps ) )
			{
				Session::i()->log( 'acplog__support_tool_apps_disabled' );
			}

			if( count( $this->_thirdPartyApps() ) )
			{
				Application::postToggleEnable( true );
			}
		}
		
		/* Do we need to restore the default theme? */
		if ( $this->_thirdPartyTheme() )
		{
			$newTheme = new Theme;
			$newTheme->permissions = Member::loggedIn()->member_group_id;
			$newTheme->save();
			$newTheme->installThemeEditorSettings();
			
			Lang::saveCustom( 'core', "core_theme_set_title_" . $newTheme->id, "IPS Default" );
			
			Member::loggedIn()->skin = $newTheme->id;
			Member::loggedIn()->save();
			
			$restoredDefaultTheme = TRUE;
		}
		
		if ( $restoredDefaultTheme )
		{
			Session::i()->log( 'acplog__support_tool_theme_restored' );
		}
		
		/* Do we need to disable any thid party ads? */
		foreach ( $this->_thirdPartyAds() as $ad )
		{
			$ad = Advertisement::constructFromData( $ad );
			$ad->active = 0;
			$ad->save();
			$disabledAds[] = $ad->id;
		}
		
		if ( count( $disabledAds ) )
		{
			Session::i()->log( 'acplog__support_tool_ads_disabled' );
		}
		
		/* Clear cache */
		Cache::i()->clearAll();

		/* Store what we've done so we can restore it after if we want */
		$_SESSION['thirdParty'] = array(
			'enableApps'	=> implode( ',', $disabledApps ),
			'deleteTheme'	=> $restoredDefaultTheme ? $newTheme->id : 0,
			'enableAds'		=> implode( ',', $disabledAds )
		);
		
		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'support' )->thirdPartyDisabled(
			$disabledAppNames,
			$restoredDefaultTheme ? $newTheme->id : 0,
			$disabledAds
		);
	}
	
	/**
	 * Step 2: Re-Enable Third Party Customizations
	 *
	 * @return	void
	 */
	protected function _enableThirdParty() : void
	{
		/* Theme */
		if ( isset( $_SESSION['thirdParty']['deleteTheme'] ) and $_SESSION['thirdParty']['deleteTheme'] and ( Request::i()->type == 'all' or Request::i()->type == 'theme' ) )
		{
			try
			{
				Theme::load(  $_SESSION['thirdParty']['deleteTheme'] )->delete();
				Session::i()->log( 'acplog__support_tool_theme_deleted' );
			}
			catch ( \Exception $e ) {}

			unset( $_SESSION['thirdParty']['deleteTheme'] );
		}
		
		/* Apps */
		if( Request::i()->type == 'all' or Request::i()->type == 'apps' )
		{
			foreach ( explode( ',', $_SESSION['thirdParty']['enableApps'] ) as $app )
			{			
				try
				{
					Db::i()->update( 'core_applications', array( 'app_enabled' => 1 ), array( 'app_directory=?', $app ) );
				}
				catch ( \Exception $e ) {}
			}

			if( $_SESSION['thirdParty']['enableApps'] )
			{
				Application::postToggleEnable( true );
				Session::i()->log( 'acplog__support_tool_apps_enabled' );
			}

			unset( $_SESSION['thirdParty']['enableApps'] );
		}

		/* Ads Ads */
		if( Request::i()->type == 'all' or Request::i()->type == 'ads' )
		{
			foreach ( explode( ',', $_SESSION['thirdParty']['enableAds'] ) as $ad )
			{
				try
				{
					$ad = Advertisement::load( $ad );
					$ad->active = 1;
					$ad->save();
				}
				catch ( \Exception $e ) {}
			}
			
			if ( $_SESSION['thirdParty']['enableAds'] )
			{
				Session::i()->log( 'acplog__support_tool_ads_enabled' );
			}

			unset( $_SESSION['thirdParty']['enableAds'] );
		}
		
		/* Clear cache */
		Cache::i()->clearAll();
		
		/* Output */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( Url::internal('app=core&module=support&controller=support') );
		}
	}

	/**
	 * Get our blocks for the dashboard. Skeleton templates are returned that will then be lazy loaded.
	 *
	 * @return array
	 */
	protected function _getBlocks() : array
	{
		$blocks = array(
			'version'		=> array(
				'title'		=> Member::loggedIn()->language()->addToStack('health__version_title'),
				'details'	=> Member::loggedIn()->language()->addToStack( 'acp_version_number_raw', FALSE, array( 'sprintf' => array( Application::load('core')->version ) ) )
			)
		);

		if( !CIC )
		{
			$blocks['php'] = array(
				'title'		=> Member::loggedIn()->language()->addToStack('health__php_title'),
				'details'	=> Member::loggedIn()->language()->addToStack( 'acp_version_number_raw', FALSE, array( 'sprintf' => array( PHP_VERSION ) ) )
			);
		}

		$blocks['mysql'] = array(
			'title'		=> Member::loggedIn()->language()->addToStack( CIC ? 'health__mysql_title' : 'health__mysql_title_cic' ),
			'details'	=> !CIC ? Member::loggedIn()->language()->addToStack( 'acp_version_number_raw', FALSE, array( 'sprintf' => array( Db::i()->server_info ) ) ) : NULL
		);

		if( !CIC )
		{
			$blocks['caching'] = array(
				'title'		=> Member::loggedIn()->language()->addToStack('health__caching_title'),
				'details'	=> Member::loggedIn()->language()->addToStack('health__caching_enabled', FALSE, array( 'sprintf' => array( IPS::mb_ucfirst( CACHE_METHOD ) ) ) )
			);

			$blocks['server'] = array(
				'title'		=> Member::loggedIn()->language()->addToStack('health__server_title'),
				'details'	=> Member::loggedIn()->language()->addToStack('health__server_subtitle', FALSE, array( 'sprintf' => array( \IPS\ROOT_PATH, $this->_getServerAddress() ) ) )
			);
		}

		if( $size = $this->_getLogTableSize() )
		{
			$size = Filesize::humanReadableFilesize( $size );
		}
		else
		{
			$size = Member::loggedIn()->language()->addToStack('unavailable');
		}

		$blocks['logs'] = array(
			'title'		=> Member::loggedIn()->language()->addToStack('health__logs_title'),
			'details'	=> Member::loggedIn()->language()->addToStack( 'health__logs_table', FALSE, array( 'sprintf' => array( $size ) ) )
		);

		$blocks['vapid'] = array(
			'title'		=> Member::loggedIn()->language()->addToStack('health__vapid_title'),
		);

		return $blocks;
	}

	/**
	 * Get the server address
	 *
	 * @return string
	 */
	protected function _getServerAddress() : string
	{
		if( array_key_exists( 'SERVER_ADDR', $_SERVER ) )
		{
			return $_SERVER['SERVER_ADDR'];
		}
		elseif( array_key_exists( 'LOCAL_ADDR', $_SERVER ) )
		{
			return $_SERVER['LOCAL_ADDR'];
		}

		return Member::loggedIn()->language()->addToStack('unavailable');
	}

	/**
	 * Get the error/system log chart
	 *
	 * @return string
	 */
	protected function getLogChart() : string
	{
		$chart = new Callback(
			Url::internal( 'app=core&module=support&controller=support&do=getLogChart' ),
			array( $this, '_getLogChartResults' ),
			'', 
			array( 
				'isStacked' => TRUE,
				'backgroundColor' 	=> '#ffffff',
				'colors'			=> array( '#10967e', '#ea7963', '#de6470' ),
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			), 
			'LineChart', 
			'daily', 
			array( 'start' => DateTime::create()->sub( new DateInterval( 'P30D' ) ), 'end' => DateTime::ts( time() ) )
		);
		$chart->addSeries( Member::loggedIn()->language()->get('health_system_log_title'), 'number', FALSE );
		$chart->addSeries( Member::loggedIn()->language()->get('health_error_log_title'), 'number', FALSE );
		$chart->addSeries( Member::loggedIn()->language()->get('health_email_error_log_title'), 'number', FALSE );
		$chart->title = NULL;
		$chart->showFilterTabs = FALSE;
		$chart->showSave = FALSE;
		$chart->availableTypes = array( 'LineChart' );
		
		if( Request::i()->isAjax() )
		{
			Output::i()->output	= (string) $chart;
		}
		else
		{
			return $chart;
		}
		return '';
	}

	/**
	 * Fetch the results
	 *
	 * @param	Callback	$chart	Chart object
	 * @return	array
	 */
	public function _getLogChartResults( Callback $chart ) : array
	{
		$finalResults = array();

		foreach( $this->_getLogChartResultsSql( 'core_log', 'time', $chart ) as $date => $count )
		{
			if( !isset( $finalResults[ $date ] ) )
			{
				$finalResults[ $date ] = array( 'time' => $date, Member::loggedIn()->language()->get('health_error_log_title') => 0, Member::loggedIn()->language()->get('health_email_error_log_title') => 0 );
			}

			$finalResults[ $date ][ Member::loggedIn()->language()->get('health_system_log_title') ] = $count;
		}

		foreach( $this->_getLogChartResultsSql( 'core_error_logs', 'log_date', $chart ) as $date => $count )
		{
			if( !isset( $finalResults[ $date ] ) )
			{
				$finalResults[ $date ] = array( 'time' => $date, Member::loggedIn()->language()->get('health_system_log_title') => 0, Member::loggedIn()->language()->get('health_email_error_log_title') => 0 );
			}

			$finalResults[ $date ][ Member::loggedIn()->language()->get('health_error_log_title') ] = $count;
		}

		foreach( $this->_getLogChartResultsSql( 'core_mail_error_logs', 'mlog_date', $chart ) as $date => $count )
		{
			if( !isset( $finalResults[ $date ] ) )
			{
				$finalResults[ $date ] = array( 'time' => $date, Member::loggedIn()->language()->get('health_error_log_title') => 0, Member::loggedIn()->language()->get('health_system_log_title') => 0 );
			}

			$finalResults[ $date ][ Member::loggedIn()->language()->get('health_email_error_log_title') ] = $count;
		}

		return $finalResults;
	}

	/**
	 * Get SQL query/results
	 *
	 * @note Consolidated to reduce duplicated code
	 * @param	string	$table	Database table
	 * @param	string	$date	Date column
	 * @param	Chart	$chart	Chart
	 * @return	array
	 */
	protected function _getLogChartResultsSql( string $table, string $date, Chart $chart ) : array
	{
		/* What's our SQL time? */
		switch ( $chart->timescale )
		{
			case 'daily':
				$timescale = '%Y-%c-%e';
				break;
			
			case 'weekly':
				$timescale = '%x-%v';
				break;
				
			case 'monthly':
				$timescale = '%Y-%c';
				break;
		}

		$results	= array();
		$where		= array();
		if ( $chart->start )
		{
			$where[] = array( "{$date}>?", $chart->start->getTimestamp() );
		}
		else
		{
			$where[] = array( "{$date}>?", 0 );
		}
		if ( $chart->end )
		{
			$where[] = array( "{$date}<?", $chart->end->getTimestamp() );
		}

		/* First we need to get search index activity */
		$fromUnixTime = "FROM_UNIXTIME( IFNULL( {$date}, 0 ) )";
		if ( !$chart->timezoneError and Member::loggedIn()->timezone and in_array( Member::loggedIn()->timezone, DateTime::getTimezoneIdentifiers() ) )
		{
			$fromUnixTime = "CONVERT_TZ( {$fromUnixTime}, @@session.time_zone, '" . Db::i()->escape_string( Member::loggedIn()->timezone ) . "' )";
		}

		$stmt = Db::i()->select( "COUNT(*) as total, DATE_FORMAT( {$fromUnixTime}, '{$timescale}' ) AS ctime", $table, $where, 'ctime ASC', NULL, array( 'ctime' ) );

		foreach( $stmt as $row )
		{
			$results[ $row['ctime'] ] = $row['total'];
		}

		return $results;
	}
	
	/**
	 * Run database checker
	 *
	 * @param	bool	$fix	Fix the issue instead of returning the count
	 * @return    array
	 */
	public function _databaseChecker( bool $fix = FALSE ) : array
	{
		$changesToMake = array();

		foreach ( Application::enabledApplications() as $app )
		{
			$changesToMake = array_merge( $changesToMake, $app->databaseCheck() );
		}

		if( !$fix )
		{
			return $changesToMake;
		}

		Output::i()->httpHeaders['X-IPS-FormNoSubmit'] = "true";
		
		if ( isset( Request::i()->run ) )
		{
			$erroredQueries = array();
			$errors = array();
			foreach ( $changesToMake as $query )
			{
				try
				{
					Db::i()->query( $query['query'] );
				}
				catch ( \Exception $e )
				{
					$erroredQueries[] = $query['query'];
					$errors[] = $e->getMessage();
				}
			}
			
			Session::i()->log( 'acplog__support_tool_db_check' );
			
			if ( count( $erroredQueries ) )
			{
				Output::i()->output = Theme::i()->getTemplate( 'support' )->fixDatabase( $erroredQueries, $errors, Request::i()->_upgradeVersion );
			}
			else
			{
				if ( isset( Request::i()->_upgradeVersion ) and Request::i()->_upgradeVersion )
				{
					Output::i()->redirect( Url::internal( 'app=core&module=system&controller=upgrade&_chosenVersion=' . Request::i()->_upgradeVersion ) );
				}
				else
				{
					Output::i()->redirect( Url::internal('app=core&module=support&controller=support') );
				}
			}
		}
		else
		{
			$queries = array();
			foreach ( $changesToMake as $query )
			{
				$queries[] = $query['query'];
			}
			
			if ( count( $queries ) )
			{
				Output::i()->output = Theme::i()->getTemplate( 'support' )->fixDatabase( $queries, NULL, Request::i()->_upgradeVersion );
			}
			else
			{
				if ( isset( Request::i()->_upgradeVersion ) and Request::i()->_upgradeVersion )
				{
					Output::i()->redirect( Url::internal( 'app=core&module=system&controller=upgrade&_chosenVersion=' . Request::i()->_upgradeVersion ) );
				}
				else
				{
					Output::i()->redirect( Url::internal('app=core&module=support&controller=support') );
				}
			}
		}

		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( Output::i()->output ) );
	}

	/**
	 * Run MD5 checker
	 *
	 * @param	bool	$fix	Fix the issue instead of returning the count
	 * @return	int|null
	 */
	protected function _md5Checker( bool $fix = FALSE ) : ?int
	{
		return 0;
	}

	/**
	 * Get a count of third party apps, themes, ckeditor plugins, and ads
	 *
	 * @return	int
	 */
	protected function _getThirdPartyCount() : int
	{
		return count( $this->_thirdPartyApps() ) +
			$this->_thirdPartyTheme() +
			count( $this->_thirdPartyAds() );
	}

	/**
	 * Get the size of the system log table
	 *
	 * @param	string	$tableName	Database table to get size of
	 * @return	int|null
	 */
	protected function _getLogTableSize( string $tableName = 'core_log' ) : ?int
	{
		try
		{
			if( $result = Db::i()->query( "SELECT DATA_LENGTH + INDEX_LENGTH as _size FROM `information_schema`.`TABLES` WHERE TABLE_SCHEMA = '" . Settings::i()->sql_database . "' AND TABLE_NAME='" . Db::i()->prefix . $tableName . "'" ) )
			{
				if( $resultSet = $result->fetch_assoc() )
				{
					return (int) $resultSet['_size'];
				}
				else
				{
					throw new Exception;
				}
			}
			else
			{
				throw new Exception;
			}
		}
		catch( Exception $e )
		{
			return NULL;
		}
	}

	/**
	 * Get third-party applications
	 *
	 * @return	array
	 */
	protected function _thirdPartyApps() : array
	{	
		if ( NO_WRITES )
		{
			return array();
		}
		
		$apps = [];
		
		foreach ( Application::applications() as $app )
		{
			if ( $app->enabled and !in_array( $app->directory, IPS::$ipsApps ) )
			{
				$apps[] = $app;
			}
		}
		
		return $apps;
	}
	
	/**
	 * Has the theme been customised?
	 *
	 * @return	bool
	 */
	protected function _thirdPartyTheme() : bool
	{	
		return Db::i()->select( 'COUNT(*)', 'core_theme_templates', 'template_set_id>0' )->first() or Db::i()->select( 'COUNT(*)', 'core_theme_css', 'css_set_id>0' )->first();
	}
	
	/**
	 * Get third-party advertisements
	 *
	 * @return	Select
	 */
	protected function _thirdPartyAds() : Select
	{	
		return Db::i()->select( '*','core_advertisements', array( 'ad_active=?', 1 ) );
	}

	/**
	 * Create Admin
	 * 
	 * @return 	void
	 */
	public function admin() : void
	{
		if ( Handler::findMethod( 'IPS\Login\Handler\Standard' ) )
		{
			$password = '';
			$length = rand( 8, 15 );
			for ( $i = 0; $i < $length; $i++ )
			{
				do {
					$key = rand( 33, 126 );
				} while ( in_array( $key, array( 34, 39, 60, 62, 92 ) ) );
				$password .= chr( $key );
			}
			
			$supportAccount = Member::load( 'ipstempadmin@invisionpower.com', 'email' );
			if ( !$supportAccount->member_id )
			{
				$supportAccount = Member::load( 'nobody@invisionpower.com', 'email' );
			}
			
			if ( !$supportAccount->member_id )
			{
				$name = 'IPS Temp Admin';
				$_supportAccount = Member::load( $name, 'name' );
				if ( $_supportAccount->member_id )
				{
					$number = 2;
					while ( $_supportAccount->member_id )
					{
						$name = "IPS Temp Admin {$number}";
						$_supportAccount = Member::load( $name, 'name' );
						$number++;
					}
				}
				
				$supportAccount = new Member;
				$supportAccount->name = $name;
				$supportAccount->member_group_id = Settings::i()->admin_group;
			}
			
			/* Always update the email in case we found the old "nobody" support account. */
			$supportAccount->email = 'ipstempadmin@invisionpower.com';

			/* Set english language to the admin account / create new english language if needed */
			$locales	= array( 'en_US', 'en_US.UTF-8', 'en_US.UTF8', 'en_US.utf8', 'english' );
			try
			{
				$existingEnglishLangPack = Db::i()->select( 'lang_id', 'core_sys_lang', array( Db::i()->in( 'lang_short', $locales ) ) )->first();
				$supportAccount->language = $existingEnglishLangPack;
				$supportAccount->acp_language = $existingEnglishLangPack;
			}
			catch ( UnderflowException $e )
			{
				/* Install the default language */
				$locale		= 'en_US';
				foreach ( $locales as $k => $localeCode )
				{
					try
					{
						Lang::validateLocale( $localeCode );
						$locale = $localeCode;
						break;
					}
					catch ( InvalidArgumentException $e ){}
				}

				$insertId = Db::i()->insert( 'core_sys_lang', array(
					'lang_short'	=> $locale,
					'lang_title'	=> "Default ACP English",
					'lang_enabled'	=> 0,
				) );
				
				$supportAccount->language		= $insertId;
				$supportAccount->acp_language	= $insertId;

				/* Initialize Background Task to insert the language strings */
				foreach ( Application::applications() as $key => $app )
				{
					Task::queue( 'core', 'InstallLanguage', array( 'application' => $key, 'language_id' => $insertId ), 1 );
				}
			}
			
			$supportAccount->members_bitoptions['is_support_account'] = TRUE;
			$supportAccount->setLocalPassword( $password );
			$supportAccount->save();
			AdminNotification::send( 'core', 'ConfigurationError', "supportAdmin-{$supportAccount->member_id}", TRUE, NULL, TRUE );
			
			Session::i()->log( 'acplog__support_tool_admin' );
			
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'administrator_account' );
			Output::i()->output = Theme::i()->getTemplate( 'support' )->admin( $supportAccount->name, $supportAccount->email, $password );
		}
	}

	/**
	 * @return void
	 */
	protected function debug() : void
	{
		$form = new Form;

		$form->addMessage( 'debug_logs_warning', 'ipsMessage ipsMessage--warning' );

		$levels = [ 0 => 'debug_log_level_disabled' ];
		for( $i=1; $i<= Log::MAX_LOG_LEVEL; $i++ )
		{
			$levels[$i] = $i;
		}
		$form->add( new Form\Select( 'debug_log_level', Settings::i()->debug_log_level, false, array(
			'options' => $levels
		) ) );

		$form->add( new Form\YesNo( 'debug_log_requests', (bool) Settings::i()->debug_log_requests, false, array(
			'togglesOn' => [ 'debug_log_requests_list' ]
		) ) );
		$form->add( new Form\Stack( 'debug_log_requests_list', Settings::i()->debug_log_requests ? json_decode( Settings::i()->debug_log_requests, true ) : null, null, array(), null, null, null, 'debug_log_requests_list' ) );

		$webhooks = [];
		foreach( Webhook::getAvailableWebhooks() as $app => $data )
		{
			$webhooks = array_merge( $webhooks, array_keys( $data ) );
		}
		sort( $webhooks );

		$form->add( new Form\YesNo( 'debug_log_webhooks', (bool) Settings::i()->debug_log_webhooks, false, array(
			'togglesOn' => [ 'debug_log_webhooks_list' ]
		) ) );
		$form->add( new Text( 'debug_log_webhooks_list', Settings::i()->debug_log_webhooks ? json_decode( Settings::i()->debug_log_webhooks, true ) : null, null, array(
			'autocomplete' => [
				'source' => $webhooks,
				'freeChoice' => false,
				'maxItems' => null,
				'forceLower' => false,
				'minimized' => false
			]
		), null, null, null, 'debug_log_webhooks_list' ) );

		$events = [];
		foreach( ListenerType::allListeners() as $type => $listeners )
		{
			foreach( $listeners as $class )
			{
				foreach( get_class_methods( $class ) as $method )
				{
					if( substr( $method, 0, 2 ) == 'on' and !in_array( $method, $events ) )
					{
						$events[] = $method;
					}
				}
			}
		}
		sort( $events );

		$form->add( new Form\YesNo( 'debug_log_events', (bool) Settings::i()->debug_log_events, false, array(
			'togglesOn' => [ 'debug_log_events_list' ]
		) ) );
		$form->add( new Text( 'debug_log_events_list', Settings::i()->debug_log_events ? json_decode( Settings::i()->debug_log_events, true ) : null, null, array(
			'autocomplete' => [
				'source' => $events,
				'freeChoice' => false,
				'maxItems' => null,
				'forceLower' => false,
				'minimized' => false
			]
		), null, null, null, 'debug_log_events_list' ) );

		if( $values = $form->values() )
		{
			$form->saveAsSettings([
				'debug_log_level' => $values['debug_log_level'],
				'debug_log_requests' => ( is_array( $values['debug_log_requests_list'] ) and count( $values['debug_log_requests_list'] ) ) ? json_encode( $values['debug_log_requests_list'] ) : null,
				'debug_log_webhooks' => ( is_array( $values['debug_log_webhooks_list']) and count( $values['debug_log_webhooks_list'] ) ) ? json_encode( $values['debug_log_webhooks_list'] ) : null,
				'debug_log_events' => ( is_array( $values['debug_log_events_list']) and count( $values['debug_log_events_list'] ) ) ? json_encode( $values['debug_log_events_list'] ) : null,
				'debug_log_enabled' => ( $values['debug_log_level'] or !empty( $values['debug_log_requests_list'] ) ) ? time() : 0
			] );

			Output::i()->redirect( Url::internal( "app=core&module=support&controller=support" ), 'saved' );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * @return void
	 */
	protected function disableDebug() : void
	{
		Session::i()->csrfCheck();

		Settings::i()->changeValues([
			'debug_log_level' => 0,
			'debug_log_requests' => null,
			'debug_log_webhooks' => null,
			'debug_log_events' => null,
			'debug_log_enabled' => 0
		]);

		Output::i()->redirect( Url::internal( "app=core&module=support&controller=support" ), 'saved' );
	}

	/**
	 * Force all tables to InnoDB
	 *
	 * @return void
	 */
	protected function fixStorageEngine() : void
	{
		if( CIC )
		{
			Output::i()->redirect( Url::internal( "app=core&module=support&controller=support" ) );
		}

		$tables = [];
		foreach( Db::i()->query( "SHOW TABLE STATUS WHERE Engine!='InnoDB'" ) as $row )
		{
			$tables[] = $row['Name'];
		}

		if( !count( $tables ) and !isset( Request::i()->run ) )
		{
			Output::i()->redirect( Url::internal( "app=core&module=support&controller=support" ) );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack( 'support_db_engine_title' );
		Output::i()->output = new MultipleRedirect(
			Url::internal( "app=core&module=support&controller=support&do=fixStorageEngine&run=1" ),
			function( $data ) use ( $tables )
			{
				if( !is_array( $data ) or !isset( $data['tables'] ) )
				{
					$data['tables'] = $tables;
					$data['offset'] = 0;
				}

				$tableName = $data['tables'][ $data['offset'] ];
				Db::i()->query( "ALTER TABLE " . Settings::i()->sql_tbl_prefix . "{$tableName} ENGINE=InnoDB" );

				$data['offset'] ++;
				if( !isset( $data['tables'][$data['offset']] ) or $data['offset'] >= count( $tables ) )
				{
					return null;
				}

				return [
					$data,
					Member::loggedIn()->language()->addToStack( 'processing' ),
					100 / count( $tables ) * $data['offset']
				];
			},
			function()
			{
				Output::i()->redirect( Url::internal( "app=core&module=support&controller=support" ) );
			}
		);
	}
}