<?php
/**
 * @brief		Statistics Charts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Jul 2015
 */

namespace IPS\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use OutOfRangeException;
use UnderflowException;
use function array_keys;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Charts
 */
abstract class Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = NULL;

	/**
	 * Get Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * 
	 * @return \IPS\Helpers\Chart
	 */
	abstract public function getChart( Url $url ): \IPS\Helpers\Chart;

	/**
	 * Load from Extension
	 *
	 * @param string $app
	 * @param string $extension
	 * @return    static
	 */
	public static function loadFromExtension( string $app, string $extension ): static
	{
		$extensions = Application::load( $app )->extensions( 'core', 'Statistics' );
		if ( in_array( $extension, array_keys( $extensions ) ) )
		{
			return $extensions[ $extension ];
		}

		throw new OutOfRangeException;
	}

	/**
	 * Load from Controller
	 *
	 * @param string $controller
	 * @return	static
	 * @throws	OutOfRangeException
	 */
	public static function loadFromController( string $controller ): static
	{
		foreach( Application::allExtensions( 'core', 'Statistics', FALSE ) AS $extension )
		{
			if ( $extension->controller AND $extension->controller === $controller )
			{
				return $extension;
			}
		}
		
		throw new OutOfRangeException;
	}
	
	/**
	 * Construct a saved chart from data
	 *
	 * @param	array|int			$data			Chart ID or pre-loaded chart data.
	 * @param	Url		$url			URL chart is shown on
	 * @param	Member|bool	$check			Check chart is owned by a specific member, or the currently logged in member if TRUE. If FALSE, no permission checking.
	 * 
	 * @return	\IPS\Helpers\Chart
	 * @throws	OutOfRangeException
	 * @throws	InvalidArgumentException
	 */
	public static function constructMemberChartFromData( array|int $data, Url $url, Member|bool $check = TRUE ): \IPS\Helpers\Chart
	{
		try
		{
			if ( $check === FALSE )
			{
				if ( !is_array( $data ) )
				{
					$data = Db::i()->select( '*', 'core_saved_charts', array( "id=?", $data ) )->first();
				}
			}
			else if ( ( $check instanceof Member ) AND $check->member_id )
			{
				if ( !is_array( $data ) )
				{
					$data = Db::i()->select( '*', 'core_saved_charts', array( "chart_id=? AND chart_member=?", $data, $check->member_id ) )->first();
				}
				else if ( $data['chart_member'] !== $check->member_id )
				{
					throw new UnderflowException;
				}
			}
			else if ( $check === TRUE AND Member::loggedIn()->member_id )
			{
				if ( !is_array( $data ) )
				{
					$data = Db::i()->select( '*','core_saved_charts', array( "chart_id=? AND chart_member=?", $data, Member::loggedIn()->member_id ) )->first();
				}
				else if ( $data['chart_member'] !== Member::loggedIn()->member_id )
				{
					throw new UnderflowException;
				}
			}
			else
			{
				/* If we're here, we were passed a guest object which isn't going to work. Throw a different exception as this should lead directly to a bugfix since this should never happen. */
				throw new InvalidArgumentException;
			}
			
			$extension = static::loadFromController( $data['chart_controller'] );
			$chart = $extension->getChart( $url );
			$currentFilters = array();
			foreach( json_decode( $data['chart_configuration'], true ) AS $k => $v )
			{
				if ( mb_substr( $k, 0, 11 ) == 'customform_' )
				{
					$currentFilters[ mb_substr( $k, 11 ) ] = $v;
				}
				else
				{
					$currentFilters[ $k ] = $v;
				}
			}
			
			$chart->savedCustomFilters = $currentFilters;
			$chart->timescale = $data['chart_timescale'] ?? $chart->timescale;
			$chart->title = $data['chart_title'];
			$chart->showFilterTabs = FALSE;
			$chart->showIntervals = FALSE;
			$chart->showDateRange = FALSE;
			
			return $chart;
		}
		catch( UnderflowException $e )
		{
			/* Chart doesn't exist, or isn't owned by $member */
			throw new OutOfRangeException;
		}
	}
	
	/**
	 * Get charts for Member
	 *
	 * @param	Url		$url		URL
	 * @param	bool				$idsOnly	Return only ID's belonging to the user for lazyloading.
	 * @param	Member|null	$member		Member, or NULL for currently logged in member.
	 *
	 * @return	array
	 * @throws	InvalidArgumentException
	 */
	public static function getChartsForMember( Url $url, bool $idsOnly = FALSE, ?Member $member = NULL ): array
	{
		$member ??= Member::loggedIn();
		
		if ( !$member->member_id )
		{
			throw new InvalidArgumentException;
		}
		
		$return = [];
		
		foreach( Db::i()->select( '*', 'core_saved_charts', array( "chart_member=?", $member->member_id ) ) AS $chart )
		{
			if ( $idsOnly )
			{
				$return[] = $chart['chart_id'];
			}
			else
			{
				try
				{
					$controller = explode( '_', $chart['chart_controller'] );
					$return[$chart['chart_id']] = array(
						'chart'		=> static::constructMemberChartFromData( $chart, $url, $member ),
						'data'		=> $chart
					);
				}
				catch( OutOfRangeException )
				{
					continue;
				}
			}
		}
		
		return $return;
	}
}