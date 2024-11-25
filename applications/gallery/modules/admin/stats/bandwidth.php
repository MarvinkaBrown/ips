<?php
/**
 * @brief		Gallery bandwidth statistics
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Gallery bandwidth statistics
 */
class bandwidth extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	Allow MySQL RW separation for efficiency
	 */
	public static bool $allowRWSeparation = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'bandwidth_manage' );
		parent::execute();
	}

	/**
	 * Gallery bandwidth statistics
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->title = Member::loggedIn()->language()->addToStack('bandwidth_stats');
		Output::i()->output = (string) Chart::loadFromExtension( 'gallery', 'Bandwidth' )->getChart( Url::internal( "app=gallery&module=stats&controller=bandwidth" ) );
	}
}