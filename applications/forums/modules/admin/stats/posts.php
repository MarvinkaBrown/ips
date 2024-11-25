<?php
/**
 * @brief		posts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		18 Aug 2014
 */

namespace IPS\forums\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\forums\Topic\Post;
use IPS\Helpers\Form;
use IPS\Helpers\Form\DateRange;
use IPS\Http\Url;
use IPS\Member;
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
 * posts
 */
class posts extends Controller
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
	 * Show solved posts
	 *
	 * @return void
	 */
	protected function showSolvedPosts() : void
	{
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_solved_index', Url::internal( 'app=forums&module=stats&controller=posts&do=showSolvedPosts' ), [ 'solved_date BETWEEN ? AND ? AND type=?', Request::i()->startTime, Request::i()->endTime, 'solved' ] );
		$table->quickSearch = NULL;
		$table->sortBy = $table->sortBy ?: 'solved_date';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->langPrefix = 'stats_posts_type_';
		$table->include = [ 'comment_id', 'solved_date' ];
		$table->widths = array( 'solved_date' => 25, 'comment_id' => 75 );
		$table->baseUrl = $table->baseUrl->setQueryString( array( 'startTime' => Request::i()->startTime, 'endTime' => Request::i()->endTime, 'tab' => 'bytype' ) );

		/* Custom parsers */
		$table->parsers = array(
			'solved_date' => function( $val, $row )
			{
				return DateTime::ts( $val )->html();
			},
			'comment_id'		=> function( $val, $row )
			{
				$class = $row['comment_class'];

				try
				{
					$item = $class::load( $row['comment_id'] );

					return Theme::i()->getTemplate( 'activitystats', 'core' )->contentCell( $item );
				}
				catch ( Throwable $e )
				{
					return Member::loggedIn()->language()->addToStack( 'unavailable' );
				}
			}
		);

		Output::i()->output = Theme::i()->getTemplate('global', 'core')->block( NULL, (string) $table, TRUE, 'i-padding_3' );
	}

	/**
	 * Show solved posts
	 *
	 * @return void
	 */
	protected function showRecommendedPosts() : void
	{
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_content_meta', Url::internal( 'app=forums&module=stats&controller=posts&do=showRecommendedPosts' ), [ 'meta_type=? AND meta_added BETWEEN ? AND ?', 'core_FeaturedComments', Request::i()->startTime, Request::i()->endTime ] );
		$table->quickSearch = NULL;
		$table->sortBy = $table->sortBy ?: 'meta_added';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->langPrefix = 'stats_posts_type_';
		$table->include = [ 'meta_data', 'meta_added' ];
		$table->widths = array( 'meta_added' => 25, 'meta_data' => 75 );
		$table->baseUrl = $table->baseUrl->setQueryString( array( 'startTime' => Request::i()->startTime, 'endTime' => Request::i()->endTime, 'tab' => 'bytype' ) );

		/* Custom parsers */
		$table->parsers = array(
			'meta_added' => function( $val, $row )
			{
				return DateTime::ts( $val )->html();
			},
			'meta_data'		=> function( $val, $row )
			{
				$data = json_decode( $val, TRUE );

				try
				{
					$item = Post::load( $data['comment'] );

					return Theme::i()->getTemplate( 'activitystats', 'core' )->contentCell( $item );
				}
				catch ( Throwable $e )
				{
					return Member::loggedIn()->language()->addToStack( 'unavailable' );
				}
			}
		);

		Output::i()->output = Theme::i()->getTemplate('global', 'core')->block( NULL, (string) $table, TRUE, 'i-padding_3' );
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Dispatcher::i()->checkAcpPermission( 'posts_manage' );

		$tabs		= array( 'total' => 'stats_posts_tab_total', 'bytype' => 'stats_posts_by_type', 'byforum' => 'stats_posts_tab_byforum' );
		Request::i()->tab ??= 'total';
		$activeTab	= ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'total';

		if ( $activeTab === 'bytype' )
		{
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

			/* Get a count of solved posts in this time */
			$solvedCount = Db::i()->select( 'count(*)', 'core_solved_index', [
				[ 'solved_date BETWEEN ? AND ?', $startTime, $endTime ],
				[ 'comment_class=?', 'IPS\forums\Topic\Post' ],
				[ 'type=?', 'solved' ]
			] )->first();

			/* Get a count of recommended posts in this time */
			$recommendedCount = Db::i()->select( 'count(*)', 'core_content_meta', [
				[ 'meta_added BETWEEN ? AND ?', $startTime, $endTime ],
				[ 'meta_type=?', 'core_FeaturedComments' ]
			] )->first();

			$formHtml = $form->customTemplate( array( Theme::i()->getTemplate( 'stats', 'core' ), 'filtersFormTemplate' ) );
			$chart = Theme::i()->getTemplate( 'stats', 'forums' )->statsTypeWrapper( $formHtml, $solvedCount, $recommendedCount, $startTime, $endTime );
		}
		else if ( $activeTab === 'total' )
		{
			$chart = Chart::loadFromExtension( 'forums', 'Posts' )->getChart( Url::internal( "app=forums&module=stats&controller=posts&tab={$activeTab}" ) );
		}
		else
		{
			$chart = Chart::loadFromExtension( 'forums', 'PostsByForum' )->getChart( Url::internal( "app=forums&module=stats&controller=posts&tab={$activeTab}" ) );
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $chart;
		}
		else
		{	
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__forums_stats_posts');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, Url::internal( "app=forums&module=stats&controller=posts" ) );
		}
	}
}