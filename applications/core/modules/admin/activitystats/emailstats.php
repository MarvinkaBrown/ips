<?php
/**
 * @brief		Email statistics
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		31 Oct 2018
 */

namespace IPS\core\modules\admin\activitystats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Email statistics
 */
class emailstats extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'emailstats_manage' );

		/* We can only view the stats if we have logging enabled */
		if( Settings::i()->prune_log_emailstats == 0 )
		{
			Output::i()->error( 'emaillogs_not_enabled', '1C395/1', 403, '' );
		}

		parent::execute();
	}

	/**
	 * Show the charts
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$activeTab = $this->_getActiveTab();
		$chart = Chart::loadFromExtension( 'core', ( $activeTab === 'emails' ) ? 'Emails' : 'EmailClicks' )->getChart( Url::internal( "app=core&module=activitystats&controller=emailstats&tab={$activeTab}" ) );
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $chart;
		}
		else
		{	
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_activitystats_emailstats');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $this->_getAvailableTabs(), $activeTab, (string) $chart, Url::internal( "app=core&module=activitystats&controller=emailstats" ), 'tab', '', 'i-padding_3' );
		}
	}

	/**
	 * Get the active tab
	 *
	 * @return string
	 */
	protected function _getActiveTab() : string
	{
		Request::i()->tab ??= 'emails';
		return ( array_key_exists( Request::i()->tab, $this->_getAvailableTabs() ) ) ? Request::i()->tab : 'emails';
	}

	/**
	 * Get the possible tabs
	 *
	 * @return array
	 */
	protected function _getAvailableTabs() : array
	{
		return array(
			'emails'	=> 'stats_emailstats_emails',
			'clicks'	=> 'stats_emailstats_clicks',
		);
	}
}