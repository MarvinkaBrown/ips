<?php
/**
 * @brief		User activity statistics overview
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		09 Jan 2020
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Select;
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
 * User activity statistics overview
 */
class overview extends Controller
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
	 * @brief Date range to restrict to, or NULL for no restriction
	 */
	protected string $dateRange = '7';

	/**
	 * @brief Form object
	 */
	protected ?Form $form = NULL;

	/**
	 * @brief Template group to use to output
	 */
	protected string $templateGroup = 'stats';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'overview_manage' );

		$options = array(
			'7'		=> 'last_week',
			'30'	=> 'last_month',
			'90'	=> 'last_three_months',
			'180'	=> 'last_six_months',
			'365'	=> 'last_year',
			'0'		=> 'alltime',
			'-1'	=> 'custom'
		);

		$this->form = new Form( 'posts', 'update' );
		$this->form->add( new Select( 'predate', '7', FALSE, array( 'options' => $options, 'toggles' => array( '-1' => array( 'dateFilterInputs' ) ) ) ) );
		$this->form->add( new DateRange( 'date', NULL, FALSE, array(), NULL, NULL, NULL, 'dateFilterInputs' ) );

		parent::execute();
	}

	/**
	 * Create the general page layout, but we will load the individual cells via AJAX to ensure there are no performance concerns loading the page
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$formHtml = $this->form->customTemplate( array( Theme::i()->getTemplate( 'stats' ), 'filtersOverviewForm' ) );
		$blocks = Application::allExtensions( 'core', 'OverviewStatistics', TRUE, 'core', 'Registrations' );

		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_stats.js', 'core' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/statistics.css', 'core', 'admin' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_overview');
		Output::i()->output = Theme::i()->getTemplate( $this->templateGroup )->overview( $formHtml, $blocks );
	}

	/**
	 * Load an individual block and output its HTML
	 *
	 * @return	void
	 */
	protected function loadBlock() : void
	{
		$blocks = Application::allExtensions( 'core', 'OverviewStatistics', TRUE, 'core', 'Registrations' );

		if( !isset( $blocks[ Request::i()->blockKey ] ) )
		{
			Output::i()->error( 'stats_overview_block_not_found', '2C412/1', 404, '' );
		}

		$dateFilters = NULL;

		if( Request::i()->range )
		{
			$dateFilters = Request::i()->range;
		}
		elseif( Request::i()->start )
		{
			try
			{
				$timezone = Member::loggedIn()->timezone ? new DateTimeZone( Member::loggedIn()->timezone ) : NULL;
			}
			catch ( Exception $e )
			{
				$timezone = NULL;
			}

			$dateFilters = array(
				'start'	=> new DateTime( Date::_convertDateFormat( Request::i()->start ), $timezone ),
				'end'	=> ( new DateTime( Date::_convertDateFormat( Request::i()->end ), $timezone ) )->setTime( 23, 59, 59 )
			);
		}

		Output::i()->sendOutput( $blocks[ Request::i()->blockKey ]->getBlock( $dateFilters, Request::i()->subblock ) );
	}
}