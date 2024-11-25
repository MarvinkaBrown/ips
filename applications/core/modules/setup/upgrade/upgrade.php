<?php
/**
 * @brief		Upgrader: Perform Upgrade
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 May 2014
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use Exception;
use IPS\core\Setup\Upgrade as UpgradeClass;
use IPS\Dispatcher\Controller;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: Perform Upgrade
 */
class upgrade extends Controller
{
	/**
	 * Upgrade
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		$multipleRedirect = new MultipleRedirect(
			Url::internal( 'controller=upgrade' )->setQueryString( 'key', $_SESSION['uniqueKey'] ),
			function( $data )
			{
				try
				{
					$upgrader = new UpgradeClass( array_keys( $_SESSION['apps'] ) );
				}
				catch ( Exception $e )
				{
					Output::i()->error( 'error', '2C222/1', 403, '' );
				}
				
				try
				{
					return $upgrader->process( $data );
				}
				catch( BadMethodCallException $e )
				{
					/* Allow multi-redirect handle this */
					throw $e;
				}
				catch( Exception $e )
				{
					Log::log( $e, 'upgrade_error' );
					
					/* Error thrown that we want to handle differently */
					$key		= 'mr-' . md5( Url::internal( 'controller=upgrade' )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );

					if ( isset( $_SESSION['updatedData'] ) and isset( $_SESSION['updatedData'][1] ) )
					{
						$_SESSION[ $key ] = json_encode( $_SESSION['updatedData'] );
					}

					$continueUrl = Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr' => 1, 'mr_continue' => 1 ) );
					$retryUrl    = Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr' => 1 ) );
					
					$error = Theme::i()->getTemplate( 'global' )->upgradeError( $e, $continueUrl, $retryUrl );
					
					return array( Theme::i()->getTemplate( 'global' )->block( 'install', $error, FALSE ) );
				}
			},
			function()
			{
				Output::i()->redirect( Url::internal( 'controller=done' ) );
			}
		);
	
		Output::i()->title	 = Member::loggedIn()->language()->addToStack('upgrade');
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'upgrade', $multipleRedirect, FALSE );
	}
}