<?php
/**
 * @brief		Upgrader: System Check
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
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use UnderflowException;
use function count;
use function defined;
use function is_array;
use const IPS\UPGRADE_MD5_CHECK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: System Check
 */
class systemcheck extends Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Do we have an older upgrade session hanging around? */
		if ( ! isset( Request::i()->skippreviousupgrade ) AND Db::i()->checkForTable( 'upgrade_temp' ) )
		{
			try
			{
				$row  = Db::i()->select( '*', 'upgrade_temp' )->first();
				$json = json_decode( $row['upgrade_data'], TRUE );
			}
			catch( UnderflowException $e )
			{
				$json = NULL;
			}

			if ( is_array( $json ) and isset( $json['session'] ) and isset( $json['data'] ) )
			{
				Output::i()->title		= Member::loggedIn()->language()->addToStack('unfinished_upgrade');
				Output::i()->output	= Theme::i()->getTemplate( 'global' )->unfinishedUpgrade( $json, $row['lastaccess'] );
				return;
			}
		}
		elseif( isset( Request::i()->skippreviousupgrade ) AND Request::i()->skippreviousupgrade )
		{
			/* If we skip the previous upgrade remove the temp upgrade data now. That way if we hit utf8 upgrader and come back to upgrader we are
				not prompted again to continue our upgrade we already elected to start over */
			Db::i()->dropTable( 'upgrade_temp', TRUE );
		}
		
		/* Do we need to disable designer mode? */
		if ( isset( Request::i()->disableDesignersMode ) )
		{
			Settings::i()->changeValues( array( 'theme_designer_mode' => 0 ) );
		}
		
		/* Get requirements */
		$requirements = Upgrade::systemRequirements();

		/* Can we just skip this screen? */
		$canProceed = FALSE;
		$canProceed = !isset( $requirements['advice'] ) or !count( $requirements['advice'] );
		if ( $canProceed )
		{
			foreach ( $requirements['requirements'] as $k => $_requirements )
			{
				foreach ( $_requirements as $item )
				{
					if ( !$item['success'] )
					{
						$canProceed = FALSE;
					}
				}
			}
		}

		/* Check we have the latest version and run an md5 check */
		$incorrectFiles = array();
		/* Or do not check :) */
		
		/* Display */
		if ( $canProceed )
		{
			Output::i()->redirect( Url::internal("controller=license&key={$_SESSION['uniqueKey']}") );
		}		
		Output::i()->title	 = Member::loggedIn()->language()->addToStack('healthcheck');
		Output::i()->output = Theme::i()->getTemplate( 'global' )->healthcheck( $requirements, $incorrectFiles );
	}
}