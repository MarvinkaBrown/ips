<?php
/**
 * @brief		alerts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		12 May 2022
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Alerts\Alert;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * alerts
 */
class alerts extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		parent::execute();
	}

	/**
	 * Dismiss alert
	 *
	 * @return	void
	 */
	protected function dismiss() : void
	{
		Session::i()->csrfCheck();

		/* Update user last seen */
		try
		{
			$alert = Alert::load( Request::i()->id );

			if( $alert->reply == 2 and Member::loggedIn()->member_id and Member::loggedIn()->canUseMessenger() )
			{
				Output::i()->error( 'alert_cant_dismiss', '3C428/1', 403, '' );
			}

			$alert->dismiss();
		}
		catch( OutOfRangeException $e ) {}

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( [ 'message' => 'ok' ] );
		}

		/* Redirect */
		Output::i()->redirect( base64_decode( Request::i()->ref ?: '' ) );
	}

	/**
	 * Set currently filtering alert and redirect
	 *
	 * @return void
	 */
	protected function viewReplies() : void
	{
		Alert::setAlertCurrentlyFilteringMessages( Alert::load( Request::i()->id ) );

		Output::i()->redirect( Url::internal('app=core&module=messaging&controller=messenger&overview=1') );
	}
}