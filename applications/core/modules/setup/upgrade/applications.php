<?php
/**
 * @brief		Upgrader: Applications
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 May 2014
 */

namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\core\Setup\Upgrade;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\Xml\XMLReader;
use OutOfRangeException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: Applications
 */
class applications extends Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		$apps			= array();

		/* Stop here if we are trying to upgrade from a version older than 4.5 */
		if( Application::load( 'core' )->long_version < 105100 )
		{
			Upgrade::setUpgradingFlag( FALSE );
			Output::i()->title		= Member::loggedIn()->language()->addToStack('applications');
			Output::i()->output 	= Theme::i()->getTemplate( 'global' )->block( 'applications', Theme::i()->getTemplate( 'forms' )->minversioncheck() );
			return;
		}

		/* If we are upgrading from a version older than v5, disable all 3rd party apps */
		if( Application::load( 'core' )->long_version < 200000 )
		{
			Db::i()->update( 'core_applications', array( 'app_enabled' => 0, 'app_requires_manual_intervention' => 1 ), Db::i()->in( 'app_directory', IPS::$ipsApps, true ) );
		}

		/* Clear any caches or else we might not see new versions */
		if ( isset( Store::i()->applications ) )
		{
			unset( Store::i()->applications );
		}
		if ( isset( Store::i()->modules ) )
		{
			unset( Store::i()->modules );
		}

		foreach( Application::applications() as $app => $data )
		{
			$path = Application::getRootPath( $app ) . '/applications/' . $app;

			if ( $app == 'chat' )
			{
				continue;
			}

			/* Skip incomplete apps */
			if ( ! is_dir( $path . '/data' ) )
			{
				continue;
			}

			/* See if there are any errors */
			$errors = array();

			if ( file_exists( $path . '/setup/requirements.php' ) )
			{
				require $path . '/setup/requirements.php';
			}

			/* Figure out of an upgrade is even available */
			$currentVersion		= Application::load( $app )->long_version;
			$availableVersion	= Application::getAvailableVersion( $app );

			$name = $data->_title;

			/* Get app name */
			if ( file_exists( $path . '/data/lang.xml' ) )
			{
				$xml = XMLReader::safeOpen( $path . '/data/lang.xml' );
				$xml->read();

				$xml->read();
				while ( $xml->read() )
				{
					if ( $xml->getAttribute('key') === '__app_' . $app )
					{
						$name = $xml->readString();
						break;
					}
				}
			}

			if( $availableVersion > $currentVersion )
			{
				$apps[ $app ] = array(
					'name'		=> $name,
					'disabled'	=> ( !empty( $errors ) OR $availableVersion <= $currentVersion ),
					'errors'	=> $errors,
					'current'	=> Application::load( $app )->version,
					'available'	=> Application::getAvailableVersion( $app, TRUE )
				);
			}

		}

		if( count( $apps ) )
		{
			/* Make sure the core app is the first index */
			if( isset( $apps['core'] ) )
			{
				$apps = [ 'core' => $apps['core'] ] + $apps;
			}

			$_SESSION['apps'] = $apps;

			$warnings = array();
			$coreVersion = array_key_exists( 'core', $_SESSION['apps'] ) ? Application::getAvailableVersion( 'core' ) : Application::load( 'core' )->long_version;
			foreach ( IPS::$ipsApps as $key )
			{
				if ( $key == 'chat' )
				{
					continue;
				}

				try
				{
					$appVersion = array_key_exists( $key, $_SESSION['apps'] ) ? Application::getAvailableVersion( $key ) : Application::load( $key )->long_version;
					if ( $appVersion != $coreVersion )
					{
						$warnings[] = $key;
					}
				}
				catch( OutOfRangeException $e )
				{
					/* The application is not installed */
					continue;
				}
			}

			if ( count( $warnings ) )
			{
				Output::i()->redirect( Url::internal( "controller=applications&do=warning" )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'warnings' => implode( ',', $warnings ) ) ) );
			}

			Output::i()->redirect( Url::internal( "controller=customoptions" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
		}
		else
		{
			$form	= Theme::i()->getTemplate( 'forms' )->noapps();

			Upgrade::setUpgradingFlag( FALSE );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('applications');
		Output::i()->output 	= Theme::i()->getTemplate( 'global' )->block( 'applications', $form );
	}

	/**
	 * Show Warning
	 *
	 * @return	void
	 */
	public function warning() : void
	{
		$apps = array();
		foreach ( explode( ',', Request::i()->warnings ) as $key )
		{
			try
			{
				$name = Application::load( $key )->_title;
				$path = \IPS\ROOT_PATH . '/applications/' . $key;
				if ( file_exists( $path . '/data/lang.xml' ) )
				{
					$xml = XMLReader::safeOpen( $path . '/data/lang.xml' );
					$xml->read();

					$xml->read();
					while ( $xml->read() )
					{
						if ( $xml->getAttribute('key') === '__app_' . $key )
						{
							$name = $xml->readString();
							break;
						}
					}
				}
				$apps[] = $name;
			}
			catch ( Exception $e ) { }
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack('applications');
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'applications', Theme::i()->getTemplate( 'global' )->appWarnings( $apps ) );
	}
}