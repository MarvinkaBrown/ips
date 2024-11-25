<?php
/**
 * @brief		Redis Info
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Oct 2018
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Redis as RedisClass;
use IPS\Theme;
use function count;
use function defined;
use function intval;
use const IPS\CACHE_METHOD;
use const IPS\CIC;
use const IPS\REDIS_ENABLED;
use const IPS\STORE_METHOD;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Redis info
 */
class redis extends Controller
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
		/* Not accessible for CIC */
		if( CIC )
		{
			Output::i()->error( 'no_module_permission', '2C394/3', 403, '' );
		}

		Dispatcher::i()->checkAcpPermission( 'redis_data' );
		Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=support&controller=support' ), Member::loggedIn()->language()->addToStack('support') );
		parent::execute();
	}

	/**
	 * Info screen
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$redis = NULL;
		$info  = NULL;
		$datasource  = array();
		
		if ( CACHE_METHOD == 'Redis' or STORE_METHOD == 'Redis' )
		{
			$info = RedisClass::i()->info();
			
			$data = array( 'type' => 'redis_datastore', 'count' => NULL, 'enabled' => false );
			
			if ( STORE_METHOD == 'Redis' )
			{
				$data['enabled'] = true;
				
				/* Lets ensure we have in the cache */
				$data['count'] = count( RedisClass::i()->debugGetKeys( RedisClass::i()->get( 'redisKey_store' ) . '_str_*', TRUE ) );
			}
			
			$datasource[] = $data;
			
			$data = array( 'type' => 'redis_cache', 'count' => NULL, 'enabled' => false );
			
			if ( CACHE_METHOD == 'Redis' )
			{
				$data['enabled'] = true;
				
				/* Lets ensure we have something */
				$data['count'] = count( RedisClass::i()->debugGetKeys( RedisClass::i()->get( 'redisKey' ) . '_*', TRUE ) );
			}
			
			$datasource[] = $data;
			
			/* And now sessions */
			if ( CACHE_METHOD == 'Redis' and REDIS_ENABLED )
			{
				$datasource[] = array( 'type' => 'redis_sessions', 'count' => count( RedisClass::i()->debugGetKeys( 'session_id_*', TRUE ) ), 'enabled' => true );
				$datasource[] = array( 'type' => 'redis_topic_views', 'count' => RedisClass::i()->zCard('topic_views'), 'enabled' => true );
				$datasource[] = array( 'type' => 'redis_advert_impressions', 'count' => RedisClass::i()->zCard('advert_impressions'), 'enabled' => true );
			}
			else
			{
				$datasource[] = array( 'type' => 'redis_sessions'   , 'count' => NULL, 'enabled' => false );
				$datasource[] = array( 'type' => 'redis_topic_views', 'count' => NULL, 'enabled' => false );
				$datasource[] = array( 'type' => 'redis_advert_impressions', 'count' => NULL, 'enabled' => false );
			}
		}
		
		/* Not using redis then are we? */
		if ( $info === NULL )
		{
			Output::i()->error( 'redis_not_enabled', '2C394/2', 403, '' );
		}
		
		$table = new Custom( $datasource, Url::internal( 'app=core&module=support&controller=redis' ) );
		$table->langPrefix = 'redis_table_';
		
		/* Custom parsers */
		$table->parsers = array(
            'type'    => function( $val )
            {
                return Member::loggedIn()->language()->addToStack( $val );
            },
            'count'    => function( $val )
            {
                return intval( $val );
            },
			'enabled' => function( $val )
			{
				return Theme::i()->getTemplate( 'support' )->redisEnabledBadge( $val );
			}
		);
		
		Output::i()->sidebar['actions']['settings'] = array(
			'icon'	=> 'cog',
			'link'	=> Url::internal( '&app=core&module=settings&controller=advanced&tab=datastore' ),
			'title'	=> 'redis_settings',
		);	
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('redis_info');
		Output::i()->output = Theme::i()->getTemplate( 'support' )->redis( $info, $table );
	}
}