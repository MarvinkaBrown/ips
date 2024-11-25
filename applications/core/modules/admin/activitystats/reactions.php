<?php
/**
 * @brief		reactions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Jan 2018
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
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * reactions
 */
class reactions extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'reactionsstats_manage' );
		parent::execute();
	}

	/**
	 * Reaction statistics
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$tabs		= array(
			'type'		=> 'stats_reactions_by_type',
			'app'		=> 'stats_reactions_by_app',
			'list'		=> 'stats_reactions_top_content',
			'givers'	=> 'stats_reactions_top_givers',
			'getters'	=> 'stats_reactions_top_receivers',
		);

		Request::i()->tab ??= 'type';
		$activeTab	= ( array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'type';
		$chart = Chart::loadFromExtension( 'core', ( $activeTab === 'type' ) ? 'Reactions' : 'ReactionsApp' )->getChart( Url::internal( "app=core&module=activitystats&controller=reactions&tab=" . $activeTab ) );

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $chart;
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_activitystats_reactions');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, Url::internal( "app=core&module=activitystats&controller=reactions" ), 'tab', '', 'i-padding_3' );
		}
	}
}