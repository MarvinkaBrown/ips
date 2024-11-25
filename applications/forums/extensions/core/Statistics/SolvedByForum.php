<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Forums
 * @since		26 Jan 2023
 */

namespace IPS\forums\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\forums\Forum;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Helpers\Form\Node;
use IPS\Http\Url;
use IPS\Member;
use UnderflowException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class SolvedByForum extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'forums_stats_solved_solved';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		/* Determine minimum date */
		$minimumDate = NULL;
		
		/* We can't retrieve any stats prior to the new tracking being implemented */
		try
		{
			$oldestLog = Db::i()->select( 'MIN(time)', 'core_statistics', array( 'type=?', 'solved' ) )->first();
		
			if( !$minimumDate OR $oldestLog < $minimumDate->getTimestamp() )
			{
				$minimumDate = DateTime::ts( $oldestLog );
			}
		}
		catch( UnderflowException $e )
		{
			/* We have nothing tracked, set minimum date to today */
			$minimumDate = DateTime::create();
		}
		
		$chart = new Database( $url, 'core_solved_index', 'solved_date', '', array(
				'isStacked' => FALSE,
				'backgroundColor' 	=> '#ffffff',
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4,
				'chartArea'			=> array( 'width' => '70%', 'left' => '5%' ),
				'height'			=> 400,
			),
			'LineChart',
			'monthly',
			array( 'start' => ( new DateTime )->sub( new DateInterval('P90D') ), 'end' => new DateTime ),
			array(),
			'solved' 
		);
		$chart->setExtension( $this );
		
		$chart->joins = array( array( 'forums_topics', array( 'comment_class=? and core_solved_index.item_id=forums_topics.tid', 'IPS\forums\Topic\Post' ) ) );
		$chart->where = array( array( Db::i()->in( 'state', array( 'link', 'merged' ), TRUE ) ), array( Db::i()->in( 'approved', array( -2, -3 ), TRUE ) ) );
		$chart->where[] = array( 'type=?', 'solved' );
		$chart->title = Member::loggedIn()->language()->addToStack( 'stats_topics_title_solved' );
		$chart->availableTypes = array( 'LineChart', 'ColumnChart' );
	
		$chart->groupBy = 'forum_id';
		$customValues = ( isset( $chart->savedCustomFilters['chart_forums'] ) ? array_values( explode( ',', $chart->savedCustomFilters['chart_forums'] ) ) : 0 );
		
		$chart->customFiltersForm = array(
			'form' => array(
				new Node( 'chart_forums', $customValues, FALSE, array( 'class' => 'IPS\forums\Forum', 'zeroVal' => 'any', 'multiple' => TRUE, 'permissionCheck' => function ( $forum )
				{
					return $forum->sub_can_post and !$forum->redirect_url;
				} ), NULL, NULL, NULL, 'chart_forums' )
			),
			'where' => function( $values )
			{
				$forumIds = is_array( $values['chart_forums'] ) ? array_keys( $values['chart_forums'] ) : explode( ',', $values['chart_forums'] );
				if ( count( $forumIds ) )
				{
					return Db::i()->in( 'forum_id', $forumIds );
				}
				else
				{
					return '';
				}
			},
			'groupBy' => 'forum_id',
			'series'  => function( $values )
			{
				$series = array();
				$forumIds = array_filter( is_array( $values['chart_forums'] ) ? array_keys( $values['chart_forums'] ) : explode( ',', $values['chart_forums'] ) );
				if ( count( $forumIds ) )
				{
					foreach( $forumIds as $id )
					{
						$series[] = array( Member::loggedIn()->language()->addToStack( 'forums_forum_' . $id ), 'number', 'COUNT(*)', FALSE, $id );
					}
				}
				else
				{
					foreach( Db::i()->select( '*', 'forums_forums', array( 'topics>? and ( forums_bitoptions & ? or forums_bitoptions & ? or forums_bitoptions & ? )', 0, 4, 8, 16 ), 'topics desc', array( 0, 50 ) ) as $forum )
					{
						$series[] = array( Member::loggedIn()->language()->addToStack( 'forums_forum_' . $forum['id'] ), 'number', 'COUNT(*)', FALSE, $forum['id'] );
					}
				}
				return $series;
			},
			'defaultSeries' => function()
			{
				$series = array();
				foreach( Db::i()->select( '*', 'forums_forums', array( 'topics>? and ( forums_bitoptions & ? or forums_bitoptions & ? or forums_bitoptions & ? )', 0, 4, 8, 16 ), 'topics desc', array( 0, 50 ) ) as $forum )
				{
					$series[] = array( Member::loggedIn()->language()->addToStack( 'forums_forum_' . $forum['id'] ), 'number', 'COUNT(*)', FALSE, $forum['id'] );
				}
				
				return $series;
			}
		);
		
		return $chart;
	}
	
	/**
	 * Get valid forum IDs to protect against bad data when a forum is removed
	 *
	 * @return array
	 */
	protected function getValidForumIds() : array
	{
		$validForumIds = [];
		
		foreach( Db::i()->select( 'value_1', 'core_statistics', [ 'type=?', 'solved' ], NULL, NULL, 'value_1' ) as $forumId )
		{
			try
			{
				$validForumIds[ $forumId ] = Forum::load( $forumId );
			}
			catch( Exception $e ) { }
		}
		
		return $validForumIds;
	}
}