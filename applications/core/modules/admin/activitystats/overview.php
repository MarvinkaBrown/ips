<?php
/**
 * @brief		Content overview statistics
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		09 Jan 2020
 */

namespace IPS\core\modules\admin\activitystats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\core\modules\admin\stats\overview as StatsOverview;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content overview statistics
 */
class overview extends StatsOverview
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief Template group to use to output
	 */
	protected string $templateGroup = 'activitystats';

	/**
	 * @brief	Allow MySQL RW separation for efficiency
	 */
	public static bool $allowRWSeparation = TRUE;

	/**
	 * Create the general page layout, but we will load the individual cells via AJAX to ensure there are no performance concerns loading the page
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$formHtml = $this->form->customTemplate( array( Theme::i()->getTemplate( 'stats' ), 'filtersOverviewForm' ) );
		$blocks = Application::allExtensions( 'core', 'OverviewStatistics', TRUE, 'core', 'Registrations' );

		$excludedApps = array();

		if( isset( Request::i()->cookie['overviewExcludedApps'] ) )
		{
			try
			{
				$excludedApps = json_decode( Request::i()->cookie['overviewExcludedApps'] );

				if( !is_array( $excludedApps ) )
				{
					$excludedApps = array();
				}
			}
			catch( Exception $e ){}
		}

		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_stats.js', 'core' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/statistics.css', 'core', 'admin' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_overview');
		Output::i()->output = Theme::i()->getTemplate( $this->templateGroup )->overview( $formHtml, $blocks, $excludedApps );
	}

	/**
	 * Load the filter configuration form
	 *
	 * @return	void
	 */
	protected function loadBlockForm() : void
	{
		$blocks = Application::allExtensions( 'core', 'OverviewStatistics', TRUE, 'core', 'Registrations' );

		if( !isset( $blocks[ Request::i()->blockKey ] ) )
		{
			Output::i()->error( 'stats_overview_block_not_found', '2C416/1', 404, '' );
		}

		Output::i()->output = $blocks[ Request::i()->blockKey ]->getBlockForm( Request::i()->subBlockKey );
	}
}