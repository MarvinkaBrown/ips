<?php
/**
 * @brief		streams
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		01 Jul 2015
 */

namespace IPS\core\modules\admin\discovery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomXPath;
use IPS\Application;
use IPS\Content\Reaction;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Xml\DOMDocument;
use function defined;
use function in_array;
use function intval;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * streams
 */
class streams extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\core\Stream';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'streams_manage' );
		parent::execute();
	}
	
	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{		
		Output::i()->sidebar['actions'] = array(
			'rebuildIndex'	=> array(
				'title'		=> 'all_activity_stream_settings',
				'icon'		=> 'cog',
				'link'		=> Url::internal( 'app=core&module=discovery&controller=streams&do=allActivitySettings' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('all_activity_stream_settings') )
			),
			'rebuildDefault'	=> array(
				'title'		=> 'restore_default_streams',
				'icon'		=> 'cog',
				'link'		=> Url::internal( 'app=core&module=discovery&controller=streams&do=restoreDefaultStreams' )->csrf(),
				'data'		=> array( 'confirm' => '', 'confirmMessage' => Member::loggedIn()->language()->addToStack('restore_default_streams_confirm') )
			),
		);
		
		parent::manage();
	}

	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form() : void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_streams.js', 'core', 'admin' ) );
		parent::form();
	}
	
	/**
	 * Restores default streams
	 *
	 * @return	void
	 */
	protected function restoreDefaultStreams() : void
	{
		Session::i()->csrfCheck();
		
		$schema	= json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/core/data/schema.json" ), TRUE );
		
		/* Get the default language strings */
		$defaultLanguages = array();
		if( file_exists( \IPS\ROOT_PATH . "/applications/core/data/lang.xml" ) )
		{			
			/* Open XML file */
			$dom = new DOMDocument( '1.0', 'UTF-8' );
			$dom->load( \IPS\ROOT_PATH . "/applications/core/data/lang.xml" );

			$xp  = new DomXPath( $dom );
			
			$results = $xp->query('//language/app/word[contains(@key, "stream_title_")]');
			
			foreach( $results as $lang )
			{
				$defaultLanguages[ str_replace( 'stream_title_', '', $lang->getAttribute('key') ) ] = $lang->nodeValue;
			}
		}
		
		foreach ( $schema['core_streams']['inserts'] as $insertData )
		{
			try
			{
				$newId = Db::i()->replace( 'core_streams', $insertData, TRUE );
				$oldId = $insertData['id'];
				
				if ( $oldId and $newId )
				{
					Lang::saveCustom( 'core', "stream_title_{$newId}", $defaultLanguages[ $oldId ] );
				}
			}
			catch( Exception $e )
			{}
		}
		
		Session::i()->log( 'acplog__streams_restored' );
		
		Output::i()->redirect( Url::internal( 'app=core&module=discovery&controller=streams' ), 'restore_default_streams_restored' );
	}
	
	/**
	 * All Activity Stream Settings
	 *
	 * @return	void
	 */
	protected function allActivitySettings() : void
	{
		$types = array( 'register', 'follow_member', 'follow_content', 'photo' );
		if ( Settings::i()->reputation_enabled )
		{
			$types[] = 'like';
		}
		if ( Settings::i()->clubs )
		{
			$types[] = 'clubs';
		}

		/* Extensions */
		foreach ( Application::allExtensions( 'core', 'StreamItems', TRUE, 'core' ) as $key => $extension )
		{
			$extensionKey = mb_strtolower( $key );
			$settingKey = "all_activity_{$extensionKey}";

			/* Only add the option if a setting for it exists - the setting must be defined by the application the extension is for. */
			if( isset( Settings::i()->$settingKey ) )
			{
				$types[] = mb_strtolower( $key );
			}
		}
		
		$options = array();
		$currentValuesStream = array();
		foreach ( $types as $k )
		{
			$key = "all_activity_{$k}";
			if ( Settings::i()->$key )
			{
				$currentValuesStream[] = $k;
			}
			$options[ $k ] = ( $k == 'like' and !Reaction::isLikeMode() ) ? 'all_activity_react' : $key;
		}
		
		$form = new Form;
		$form->add( new CheckboxSet( 'all_activity_extra_stream', $currentValuesStream, FALSE, array( 'options' => $options ) ) );
		$form->add( new YesNo( 'activity_stream_rss', Settings::i()->activity_stream_rss ) );
		$form->add( new YesNo( 'activity_stream_subscriptions', Settings::i()->activity_stream_subscriptions, FALSE, ['togglesOn' => ['activity_stream_subscriptions_max', 'activity_stream_subscriptions_inactive_limit' ] ] ) );
		$form->add( new Number( 'activity_stream_subscriptions_max', Settings::i()->activity_stream_subscriptions_max, TRUE, ['min' => 1, 'max' => CIC ? 10:NULL ], NULL, NULL, NULL,'activity_stream_subscriptions_max' ) );
		$form->add( new Number( 'activity_stream_subscriptions_inactive_limit', Settings::i()->activity_stream_subscriptions_inactive_limit, TRUE, [ ], NULL, NULL, NULL,'activity_stream_subscriptions_inactive_limit' ) );


		if ( $values = $form->values() )
		{
			$toSave = array();
			foreach ( $types as $k )
			{
				$toSave[ "all_activity_{$k}" ] = intval( in_array( $k, $values['all_activity_extra_stream'] ) );
			}

			$toSave[ 'activity_stream_rss' ] = $values['activity_stream_rss'];
			$toSave[ 'activity_stream_subscriptions' ] = $values['activity_stream_subscriptions'];
			$toSave[ 'activity_stream_subscriptions_max' ] = $values['activity_stream_subscriptions_max'];
			
			Session::i()->Log( 'acplog__all_activity_settings' );

			$form->saveAsSettings( $toSave );
			Output::i()->redirect( Url::internal( 'app=core&module=discovery&controller=streams' ), 'saved' );
		}
		
		Output::i()->output = $form;
	}
}