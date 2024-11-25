<?php
/**
 * @brief		Upgrader: Finished Screen
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 May 2014
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Application;
use IPS\core\AdminNotification;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: Finished Screen
 */
class done extends Controller
{
	/**
	 * Finished
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		Output::clearJsFiles();
		
		/* Get rid of temporary upgrade data */
		Db::i()->dropTable( 'upgrade_temp', TRUE );
		
		/* Reset theme maps to make sure bad data hasn't been cached by visits mid-setup */
		foreach( Theme::themes() as $id => $set )
		{
			/* Update mappings */
			$set->css_map = array();
			$set->save();
		}

		/* Delete some variables we stored in our session */
		unset( $_SESSION['apps'] );

		if( isset( $_SESSION['upgrade_options'] ) )
		{
			unset( $_SESSION['upgrade_options'] );
		}
		
		if( isset( $_SESSION['sqlFinished'] ) )
		{
			unset( $_SESSION['sqlFinished'] );
		}

		if( isset( $_SESSION['uniqueKey'] ) )
		{
			unset( $_SESSION['uniqueKey'] );
		}

		unset( $_SESSION['key'] );

		/* Clear recent datastore logs to prevent an error message displaying immediately after upgrade */
		Db::i()->delete( 'core_log', array( '`category`=? AND `time`>?', 'datastore', DateTime::create()->sub( new DateInterval( 'PT1H' ) )->getTimestamp() ) );
		
		/* Unset settings datastore to prevent any upgrade settings that were overridden becoming persistent */
		Settings::i()->clearCache();
		
		/* IPS Cloud Sync */
		IPS::resyncIPSCloud('Upgraded community');
		
		/* Remove any new version ACP Notifications */
		AdminNotification::remove( 'core', 'NewVersion' );

		/* Clear any previous editor plugins */
		Application::resetEditorPlugins();

		/* And show the complete page - the template handles this step special already so we don't have to output anything */
		Output::i()->title = Member::loggedIn()->language()->addToStack('done');
		
		/* The upgrader will cause a few syncs on Cloud2, but we don't have to wait for those. Clear the flag here. */
		unset( Store::i()->syncCompleted );
	}
}