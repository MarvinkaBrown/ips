<?php
/**
 * @brief		Search Statistics
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Dec 2019
 */

namespace IPS\core\modules\admin\activitystats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Statistics
 */
class search extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'search_stats_manage' );
		parent::execute();
	}

	/**
	 * View search statistics
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Show button to adjust settings */
		Output::i()->sidebar['actions']['settings'] = array(
			'icon'		=> 'cog',
			'primary'	=> TRUE,
			'title'		=> 'manage_searchstats',
			'link'		=> Url::internal( 'app=core&module=activitystats&controller=search&do=settings' ),
			'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('settings') )
		);

		Output::i()->sidebar['actions']['log'] = array(
			'icon'		=> 'search',
			'title'		=> 'searchstats_log',
			'link'		=> Url::internal( 'app=core&module=activitystats&controller=search&do=log' ),
		);

		$chart = Chart::loadFromExtension( 'core', 'Search' )->getChart( Url::internal( "app=core&module=activitystats&controller=search" ) );

		Output::i()->output	= (string) $chart;

		if( Request::i()->noheader AND Request::i()->isAjax() )
		{
			return;
		}

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_activitystats_search');
	}

	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		$form = new Form;
		$form->add( new Interval( 'stats_search_prune', Settings::i()->stats_search_prune, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__statssearch_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=activitystats&controller=search' ), 'saved' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('settings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'settings', $form, FALSE );
	}

	/**
	 * Search log
	 *
	 * @return	void
	 */
	protected function log() : void
	{
		/* Create the table */
		$table = new Db( 'core_statistics', Url::internal( 'app=core&module=activitystats&controller=search&do=log' ), array( array( 'type=?', 'search' ) ) );
		$table->langPrefix = 'searchstats_';
		$table->quickSearch = 'value_4';

		/* Columns we need */
		$table->include = array( 'value_4', 'value_2', 'time' );
		$table->mainColumn = 'value_4';

		$table->sortBy = $table->sortBy ?: 'time';
		$table->sortDirection = $table->sortDirection ?: 'desc';

		/* Custom parsers */
		$table->parsers = array(
			'time'			=> function( $val, $row )
			{
				return DateTime::ts( $val );
			}
		);

		/* The table filters won't without this */
		Output::i()->bypassCsrfKeyCheck = true;

		Output::i()->title		= Member::loggedIn()->language()->addToStack('searchstats_log');
		Output::i()->output 	= (string) $table;
	}
}