<?php
/**
 * @brief		updatecheck Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Aug 2013
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\core\AdminNotification;
use IPS\Data\Store;
use IPS\Db;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Log;
use IPS\Settings;
use IPS\Task;
use RuntimeException;
use Throwable;
use UnderflowException;
use UnexpectedValueException;
use function count;
use function defined;
use const IPS\IPS_ALPHA_BUILD;
use const IPS\USE_DEVELOPMENT_BUILDS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * updatecheck Task
 */
class updatecheck extends Task
{
	/**
	 * @brief	Type to send to update server
	 */
	public string $type = 'task';
	
	/**
	 * Execute
	 *
	 * @return	mixed	Message to log or NULL
	 */
	public function execute() : mixed
	{
		/* Refresh stored license data */
		IPS::licenseKey( TRUE );
		
		$fails = array();
		
		/* Do IPS apps */
		$versions = array();
		foreach ( Db::i()->select( '*', 'core_applications', Db::i()->in( 'app_directory', IPS::$ipsApps ) ) as $app )
		{
			if ( $app['app_enabled'] )
			{
				$versions[] = $app['app_long_version'];
			}
		}
		$version = min( $versions );
		$url = Url::ips('updateCheck5')->setQueryString( array( 'type' => $this->type, 'key' => Settings::i()->ipb_reg_number ) );
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

			/* Send a notification if new version is available */
			if ( $updates = $coreApp->availableUpgrade( TRUE ) and count( $updates ) )
			{
				AdminNotification::send( 'core', 'NewVersion', NULL, FALSE );
			}
			else
			{
				AdminNotification::remove( 'core', 'NewVersion' );
			}
		}
		catch ( Exception $e ) { }

		/* Check for bulletins while we're here */
		try
		{
			$bulletins = Url::ips('bulletin')->request()->get()->decodeJson();

			foreach ( $bulletins as $id => $bulletin )
			{
				Db::i()->insert( 'core_ips_bulletins', array(

					'id' 			=> $id,
					'title'			=> $bulletin['title'],
					'body'			=> $bulletin['body'],
					'severity'		=> $bulletin['severity'],
					'style'			=> $bulletin['style'],
					'dismissible'	=> $bulletin['dismissible'],
					'link'			=> $bulletin['link'],
					'conditions'	=> $bulletin['conditions'],
					'cached'		=> time(),
					'min_version'	=> $bulletin['minVersion'],
					'max_version'	=> $bulletin['maxVersion']
				), TRUE );

				/* Don't send the notification until after we insert the bulletin data */
				try
				{
					if (
						/*  If the value is 0 for minVersion, there is no minimum version (a.k.a., display it). Same deal with maxVersion. */
						( $bulletin['minVersion'] == 0 AND $bulletin['maxVersion'] == 0 )
						/* If there's no minimum version, and the maximum Version is within range */
						OR ( $bulletin['minVersion'] == 0 AND Application::load('core')->long_version < $bulletin['maxVersion'] )
						/* If there's no maximum version, and the minimum version is within range */
						OR ( $bulletin['maxVersion'] == 0 AND Application::load('core')->long_version > $bulletin['minVersion'] )
						/* If both min and max versions are within range */
						OR ( Application::load('core')->long_version >= $bulletin['minVersion'] AND Application::load('core')->long_version <= $bulletin['maxVersion'] )
					)
					{
						if ( @eval( $bulletin['conditions'] ) )
						{
							AdminNotification::send( 'core', 'Bulletin', (string) $id, FALSE );
						}
						
						else
						{
							AdminNotification::remove( 'core', 'Bulletin', (string) $id );
						}
					}
					
					else
					{
						AdminNotification::remove( 'core', 'Bulletin', (string) $id );
					}
				}
				catch ( Throwable | Exception $e )
				{
					Log::log( $e, 'bulletin' );
				}
			}
		}
		catch( RuntimeException $e ){ }

		$updateChecked = [];
		$fails = [];
		$fiveMinutesAgo = time() - 300;

		$this->runUntilTimeout( function() use ( &$updateChecked, &$fails, $version, $fiveMinutesAgo ) {
			try
			{
				$row = Db::i()->union(
					array(
						Db::i()->select( "'core_applications' AS `table`, app_directory AS `id`, app_update_check AS `url`, app_update_last_check AS `last`, app_long_version AS `current`", 'core_applications', "app_update_last_check<{$fiveMinutesAgo} AND ( app_update_check<>'' AND app_update_check IS NOT NULL )" ),
						Db::i()->select( "'core_themes' AS `table`, set_id AS `id`, set_update_check AS `url`, set_update_last_check AS `last`, set_long_version AS `current`", 'core_themes', "set_update_last_check<{$fiveMinutesAgo} AND (set_update_check<>'' AND set_update_check IS NOT NULL )" ),
						Db::i()->select( "'core_sys_lang' AS `table`, lang_id AS `id`, `lang_update_url` AS `url`, `lang_update_check` AS `last`, `lang_version_long` AS `current`", "core_sys_lang", "lang_update_check<{$fiveMinutesAgo} AND (lang_update_url<>'' AND lang_update_url IS NOT NULL )" )
					),
					'last ASC',
					1
				)->first();
			}
			catch( UnderflowException $e )
			{
				return FALSE;
			}

			switch ( $row['table'] )
			{
				case 'core_applications':
					$dataColumn = 'app_update_version';
					$timeColumn = 'app_update_last_check';
					$idColumn	= 'app_directory';
					$updateChecked[] = 'applications';

					/* Account for legacy applications */
					try
					{
						$key = "__app_{$row['id']}";
						$source = Lang::load( Lang::defaultLanguage() )->get( $key );
					}
					catch ( UnderflowException | UnexpectedValueException $e )
					{
						return null;
					}
					break;

				case 'core_themes':
					$dataColumn = 'set_update_data';
					$timeColumn = 'set_update_last_check';
					$idColumn	= 'set_id';
					$key = "core_theme_set_title_{$row['id']}";
					$source = Lang::load( Lang::defaultLanguage() )->get( $key );
					$updateChecked[] = 'themes';
					break;
				
				case 'core_sys_lang':
					$dataColumn	= 'lang_update_data';
					$timeColumn	= 'lang_update_check';
					$idColumn	= 'lang_id';
					$source		= Lang::load( $row['id'] )->_title;
					$updateChecked[] = 'languages';
					break;
			}

			try
			{
				$url = Url::external( $row['url'] )->setQueryString( array( 'version' => $row['current'], 'ips_version' => $version ) );
				$response = $url->request()->get()->decodeJson();

				/* Unset the object so it isn't present for next update check. */
				unset( $object );

				/* Did we get all the information we need? */
				if ( !isset( $response['version'], $response['longversion'], $response['released'], $response['updateurl'] ) )
				{
					throw new RuntimeException( Lang::load( Lang::defaultLanguage() )->get( 'update_check_missing' ) );
				}

				/* Save the latest version data and move on to the next app */
				Db::i()->update( $row['table'], array(
					$dataColumn => json_encode( array(
						'version'		=> $response['version'],
						'longversion'	=> $response['longversion'],
						'released'		=> $response['released'],
						'updateurl'		=> $response['updateurl'],
						'releasenotes'	=> $response['releasenotes'] ?? NULL
					) ),
					$timeColumn	=> time()
				), array( "{$idColumn}=?", $row['id'] ) );
			}
			/* \RuntimeException catches BAD_JSON and \IPS\Http\Request\Exception both */
			catch ( RuntimeException $e )
			{
				$fails[] = $source . ": " . $e->getMessage();

				/* Save the time so that the next time the task runs it can move on to other apps/plugins/themes */
				Db::i()->update( $row['table'], array(
					$timeColumn	=> time()
				), array( "{$idColumn}=?", $row['id'] ) );
			}

			return TRUE;
		});

		/* Reset Menu Cache */
		foreach( $updateChecked as $type )
		{
			$key = 'updatecount_' . $type;
			unset( Store::i()->$key, Store::i()->$type );
		}
		
		if ( !empty( $fails ) )
		{
			return $fails;
		}
		
		return NULL;
	}
}