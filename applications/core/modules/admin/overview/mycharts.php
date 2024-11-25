<?php
/**
 * @brief		mycharts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		09 Dec 2022
 */

namespace IPS\core\modules\admin\overview;

use IPS\core\Statistics\Chart;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use Throwable;
use function count;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * mycharts
 */
class mycharts extends Controller
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
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$charts = [];
		foreach( Chart::getChartsForMember( Url::internal( "app=core&module=overview&controller=mycharts" ), TRUE ) AS $id )
		{
			$charts[] = $id;
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_overview_mycharts');
		if ( !count( $charts ) )
		{
			Output::i()->output = Theme::i()->getTemplate('stats')->mychartsEmpty();
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate('stats')->mycharts( $charts );
		}
	}
	
	/**
	 * Get chart
	 *
	 * @return	void
	 */
	public function getChart() : void
	{
		
		if ( !isset( Request::i()->chartId ) )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->output = '';
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=overview&controller=mycharts" ) );
			}
		}
		
		try
		{
			$chart = Chart::constructMemberChartFromData( Request::i()->chartId, Url::internal( "app=core&module=overview&controller=mycharts&do=getChart&chartId=" . Request::i()->chartId ) );
			Output::i()->output = (string) $chart;
		}
		catch( Throwable )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->output = '';
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=overview&controller=mycharts" ) );
			}
		}
	}
}