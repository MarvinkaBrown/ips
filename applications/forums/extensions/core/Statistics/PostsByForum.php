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
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Helpers\Form\Node;
use IPS\Http\Url;
use IPS\Member;
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
class PostsByForum extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'forums_stats_posts_byforum';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		$chart = new Database( $url, 'forums_posts', 'post_date', '', array(
				'isStacked' => FALSE,
				'backgroundColor' 	=> '#ffffff',
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4,
				'chartArea'			=> array( 'width' => '70%', 'left' => '5%' ),
				'height'			=> 400,
			),
			'ColumnChart',
			'monthly',
			array( 'start' => ( new DateTime )->sub( new DateInterval('P90D') ), 'end' => new DateTime ),
			array(),
			'byforum' 
		);
		$chart->setExtension( $this );

		$chart->title = Member::loggedIn()->language()->addToStack( 'stats_posts_title_byforum' );
		$chart->availableTypes = array( 'ColumnChart' );
		$chart->where = array( array( Db::i()->in( 'queued', array( -2, -3 ), TRUE ) ) );
		$chart->groupBy = 'forum_id';
		$chart->joins	= array( array( 'forums_topics', 'forums_topics.tid=forums_posts.topic_id' ) );
		
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
				return Db::i()->in( 'forums_topics.forum_id', $forumIds );
			},
			'groupBy' => 'forum_id',
			'series'  => function( $values )
			{
				$series = array();
				$forumIds = is_array( $values['chart_forums'] ) ? array_keys( $values['chart_forums'] ) : explode( ',', $values['chart_forums'] );
				foreach( $forumIds as $id )
				{
					$series[] = array( Member::loggedIn()->language()->addToStack( 'forums_forum_' . $id ), 'number', 'COUNT(*)', FALSE, $id );
				}
				return $series;
			},
			'defaultSeries' => function()
			{
                $series = array();
				foreach( Db::i()->select( '*', 'forums_forums', array( 'posts>? AND club_id IS NULL', 0 ), 'topics desc', array( 0, 50 ) ) as $forum )
				{
					$series[] = array( Member::loggedIn()->language()->addToStack( 'forums_forum_' . $forum['id'] ), 'number', 'COUNT(*)', FALSE, $forum['id'] );
				}
				
				return $series;
			}
		);
		
		return $chart;
	}
}