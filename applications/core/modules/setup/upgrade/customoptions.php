<?php
/**
 * @brief		Upgrader: Custom Upgrade Options
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 May 2014
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: Custom Upgrade Options
 */
class customoptions extends Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		$elements	= array();
		
		/* We need to store option_name => app reference for use later, otherwise all options get stored for the last app in the list. */
		$appOptions = array();
		
		/* Loop through all applications we are upgrading and all versions for those applications and see if any custom options are available.
			At the same time we look for any pre-upgrade checks, which completely halt everything. */
		foreach( $_SESSION['apps'] as $app => $upgrade )
		{
			$application	= Application::load( $app );
			$steps			= $application->getUpgradeSteps( $application->long_version );

			foreach( $steps as $step )
			{
				if( file_exists( \IPS\ROOT_PATH . "/applications/{$app}/setup/upg_{$step}/checks.php" ) )
				{
					$output = NULL;
					require_once( \IPS\ROOT_PATH . "/applications/{$app}/setup/upg_{$step}/checks.php" );
					
					if( $output !== NULL )
					{
						Output::i()->title		= Member::loggedIn()->language()->addToStack('admin');
						Output::i()->output 	= $output;
						return;
					}
				}

				if( file_exists( \IPS\ROOT_PATH . "/applications/{$app}/setup/upg_{$step}/options.php" ) )
				{
					require_once( \IPS\ROOT_PATH . "/applications/{$app}/setup/upg_{$step}/options.php" );
					$elements	= array_merge( $elements, $options );
					foreach( $options AS $option )
					{
						$appOptions[$option->name] = $app;
					}
				}
				
				if( file_exists( \IPS\ROOT_PATH . "/applications/{$app}/setup/upg_{$step}/postUpgrade.php" ) )
				{
					$message = NULL;
					require_once( \IPS\ROOT_PATH . "/applications/{$app}/setup/upg_{$step}/postUpgrade.php" );
					if ( $message !== NULL )
					{
						$_SESSION['upgrade_postUpgrade'][ $app ][ $step ] = $message;
					}
				}
			}
		}

		/* Do we need to disable any apps? */
		if( isset( Request::i()->disable ) )
		{
			foreach( explode( ',', Request::i()->disable ) as $app )
			{
				Db::i()->update( 'core_applications', array( 'app_enabled' => 0 ), array( 'app_directory=?', $app ) );
			}

			if ( isset( Store::i()->applications ) )
			{
				unset( Store::i()->applications );
			}
		}

		/* If there are no options, no need to show an empty/blank form */
		if( !count( $elements ) )
		{
			Output::i()->redirect( Url::internal( "controller=confirm" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
		}

		/* Otherwise, show the form */
		$form = new Form( 'options', 'continue' );

		foreach( $elements as $element )
		{
			$form->add( $element );
		}

		if( $values = $form->values() )
		{
			foreach( $values as $key => $value )
			{
				$app = $appOptions[$key];
				if ( preg_match( '#^(\d{5,6})_#', $key ) )
				{
					$version	= mb_substr( $key, 0, mb_strpos( $key, '_' ) );
					$key		= mb_substr( $key, mb_strpos( $key, '_' ) + 1 );
				}
				
				$_SESSION['upgrade_options'][ $app ][ $version ][ $key ] = $value;
			}
			
			Output::i()->redirect( Url::internal( "controller=confirm" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('admin');
		Output::i()->output 	= Theme::i()->getTemplate( 'global' )->block( 'admin', $form );
	}
}