<?php
/**
 * @brief		badges
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		10 Mar 2021
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\Statistics\Chart;
use IPS\core\Achievements\Badge;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use Throwable;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * badges
 */
class badges extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'overview_manage' );
		parent::execute();
	}

	/**
	 * Show badges in a modal
	 *
	 * @return void
	 */
	protected function showBadges() : void
	{
		$where = [
			[ 'datetime BETWEEN ? AND ?', Request::i()->badgeDateStart, Request::i()->badgeDateEnd ],
			[ 'member_group_id=?', Request::i()->member_group_id ]
		];

		$query = Db::i()->select( 'badge, COUNT(*) as count', 'core_member_badges', $where, NULL, NULL, 'badge' )
							 ->join( 'core_members', [ 'core_members.member_id=core_member_badges.member' ] );

		$results = [];
		foreach( $query as $row )
		{
			try
			{
				$row['badge'] = Badge::load( $row['badge'] );
				$results[] = $row;
			}
			catch( Exception $e ) { }
		}

		Output::i()->output = Theme::i()->getTemplate( 'achievements' )->statsBadgeModal( $results );
	}
	
	/**
	 * Show member badges in a modal
	 *
	 * @return void
	 */
	protected function showMemberBadges() : void
	{
		$where = [
			[ 'datetime BETWEEN ? AND ?', Request::i()->badgeDateStart, Request::i()->badgeDateEnd ],
			[ 'member_id=?', Request::i()->member_id ]
		];

		$query = Db::i()->select( 'badge, datetime', 'core_member_badges', $where )
							 ->join( 'core_members', [ 'core_members.member_id=core_member_badges.member' ] );

		$results = [];
		foreach( $query as $row )
		{
			try
			{
				$row['badge'] = Badge::load( $row['badge'] );
				$results[] = $row;
			}
			catch( Exception $e ) { }
		}

		Output::i()->output = Theme::i()->getTemplate( 'achievements' )->statsMemberBadgeModal( $results );
	}

	/**
	 * Badges earned activity chart
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$tabs = array(
			'type' => 'stats_badges_by_badge',
			'list' => 'stats_badges_by_group',
			'member'	=> 'stats_badges_by_member',
		);

		Request::i()->tab ??= 'type';
		$activeTab = ( array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'type';

		if ( $activeTab === 'type' )
		{
			$chart = Chart::loadFromExtension( 'core', 'Badges' )->getChart( Url::internal( 'app=core&module=stats&controller=badges&tab=' . $activeTab ) );
		}
		elseif ( $activeTab === 'member' )
		{
			$start		= NULL;
			$end		= NULL;

			$defaults = array( 'start' => DateTime::create()->setDate( date('Y'), date('m'), 1 ), 'end' => new DateTime );

			if( isset( Request::i()->badgeDateStart ) AND isset( Request::i()->badgeDateEnd ) )
			{
				$defaults = array( 'start' => DateTime::ts( Request::i()->badgeDateStart ), 'end' => DateTime::ts( Request::i()->badgeDateEnd ) );
			}

			$form = new Form( $activeTab, 'continue' );
			$form->add( new DateRange( 'date', $defaults, TRUE ) );

			if( $values = $form->values() )
			{
				/* Determine start and end time */
				$startTime	= $values['date']['start']->getTimestamp();
				$endTime	= $values['date']['end']->getTimestamp();

				$start		= $values['date']['start']->html();
				$end		= $values['date']['end']->html();
			}
			else
			{
				/* Determine start and end time */
				$startTime	= $defaults['start']->getTimestamp();
				$endTime	= $defaults['end']->getTimestamp();

				$start		= $defaults['start']->html();
				$end		= $defaults['end']->html();
			}

			/* Create the table */
			$chart = new TableDb( 'core_member_badges', Url::internal( 'app=core&module=stats&controller=badges&type=member' ), [ 'datetime BETWEEN ? AND ?', $startTime, $endTime ] );
			$chart->quickSearch = NULL;
			$chart->selects = [ 'count(*) as count' ];
			$chart->joins = [
				[ 'select' => 'member_id', 'from' => 'core_members', 'where' => 'core_members.member_id=core_member_badges.member' ]
			];

			$chart->groupBy = 'member_id';
			$chart->langPrefix = 'stats_badges_';
			$chart->include = [ 'member_id', 'count' ];
			$chart->mainColumn = 'member_id';
			$chart->baseUrl = $chart->baseUrl->setQueryString( array( 'badgeDateStart' => $startTime, 'badgeDateEnd' => $endTime, 'tab' => $activeTab ) );
			$chart->sortBy = 'count';
			
			/* Custom parsers */
			$chart->parsers = array(
				'member_id' => function( $val )
				{
					$member = Member::load( $val );
					return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . ' ' . $member->link();
				},
				'count' => function( $val, $row ) use( $startTime, $endTime )
				{
					return Theme::i()->getTemplate( 'achievements' )->statsMemberBadgeCount( $val, $row['member_id'], $startTime, $endTime );
				}
			);

			$formHtml = $form->customTemplate( array( Theme::i()->getTemplate( 'stats' ), 'filtersFormTemplate' ) );
			$chart = Theme::i()->getTemplate( 'achievements', 'core' )->statsBadgeWrapper( $formHtml, (string) $chart );
		}
		else
		{
			$start		= NULL;
			$end		= NULL;

			$defaults = array( 'start' => DateTime::create()->setDate( date('Y'), date('m'), 1 ), 'end' => new DateTime );

			if( isset( Request::i()->badgeDateStart ) AND isset( Request::i()->badgeDateEnd ) )
			{
				$defaults = array( 'start' => DateTime::ts( Request::i()->badgeDateStart ), 'end' => DateTime::ts( Request::i()->badgeDateEnd ) );
			}

			$form = new Form( $activeTab, 'continue' );
			$form->add( new DateRange( 'date', $defaults, TRUE ) );

			if( $values = $form->values() )
			{
				/* Determine start and end time */
				$startTime	= $values['date']['start']->getTimestamp();
				$endTime	= $values['date']['end']->getTimestamp();

				$start		= $values['date']['start']->html();
				$end		= $values['date']['end']->html();
			}
			else
			{
				/* Determine start and end time */
				$startTime	= $defaults['start']->getTimestamp();
				$endTime	= $defaults['end']->getTimestamp();

				$start		= $defaults['start']->html();
				$end		= $defaults['end']->html();
			}

			/* Create the table */
			$chart = new TableDb( 'core_member_badges', Url::internal( 'app=core&module=stats&controller=badges&type=list' ), [ 'datetime BETWEEN ? AND ?', $startTime, $endTime ] );
			$chart->quickSearch = NULL;
			$chart->selects = [ 'count(*) as count' ];
			$chart->joins = [
				[ 'select' => 'member_group_id', 'from' => 'core_members', 'where' => 'core_members.member_id=core_member_badges.member' ]
			];

			$chart->groupBy = 'member_group_id';
			$chart->langPrefix = 'stats_badges_';
			$chart->include = [ 'member_group_id', 'count' ];
			$chart->mainColumn = 'member_group_id';
			$chart->baseUrl = $chart->baseUrl->setQueryString( array( 'badgeDateStart' => $startTime, 'badgeDateEnd' => $endTime, 'tab' => $activeTab ) );

			/* Custom parsers */
			$chart->parsers = array(
				'member_group_id' => function( $val )
				{
					try
					{
						return Group::load( $val )->formattedName;
					}
					catch ( Throwable $e )
					{
						return Member::loggedIn()->language()->addToStack( 'unavailable' );
					}
				},
				'count' => function( $val, $row ) use( $startTime, $endTime )
				{
					return Theme::i()->getTemplate( 'achievements' )->statsBadgeCount( $val, $row['member_group_id'], $startTime, $endTime );
				}
			);

			$formHtml = $form->customTemplate( array( Theme::i()->getTemplate( 'stats' ), 'filtersFormTemplate' ) );
			$chart = Theme::i()->getTemplate( 'achievements', 'core' )->statsBadgeWrapper( $formHtml, (string) $chart );
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string)$chart;
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'menu__core_stats_badges' );
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string)$chart, Url::internal( "app=core&module=stats&controller=badges" ), 'tab', '', 'i-padding_3' );
		}
	}
}