<?php
/**
 * @brief		preferences
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		02 Sep 2021
 */

namespace IPS\core\modules\admin\stats;

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
 * preferences
 */
class preferences extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'preferences_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$tabs		= array(
			'theme'		=> 'stats_member_pref_theme',
			'lang'		=> 'stats_member_pref_lang',
		);
		Request::i()->tab ??= 'lang';
		$activeTab	= ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'lang';

		switch( $activeTab )
		{
			case 'theme':

				$chart = Chart::loadFromExtension( 'core', 'Theme' )->getChart( Url::internal( "app=core&module=stats&controller=preferences&tab=theme" ) );
				break;
				
			case 'lang':

				$chart = Chart::loadFromExtension( 'core', 'Language' )->getChart( Url::internal( "app=core&module=stats&controller=preferences&tab=lang" ) );
				break;
		}
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = Theme::i()->getTemplate( 'global' )->paddedBlock( (string) $chart, NULL, "i-padding_3" );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_preferences');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, Url::internal( "app=core&module=stats&controller=preferences" ), 'tab', '', 'i-padding_3' );
		}
			
	}
}