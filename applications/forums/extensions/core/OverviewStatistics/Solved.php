<?php
/**
 * @brief		Overview statistics extension: Solved
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		01 Dec 2020
 */

namespace IPS\forums\extensions\core\OverviewStatistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\OverviewStatisticsAbstract;
use IPS\forums\Forum;
use IPS\forums\Topic;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Node;
use IPS\Member;
use IPS\Request;
use IPS\Theme;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Overview statistics extension: Solved
 */
class Solved extends OverviewStatisticsAbstract
{
	/**
	 * @brief	Which statistics page (activity or user)
	 */
	public string $page	= 'activity';

	/**
	 * Return the sub-block keys
	 *
	 * @note This is designed to allow one class to support multiple blocks, for instance using the ContentRouter to generate blocks.
	 * @return array
	 */
	public function getBlocks(): array
	{
		return array( 'percentagesolved', 'averagetimesolved' );
	}

	/**
	 * Return block details (title and description)
	 *
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	array
	 */
	public function getBlockDetails( string $subBlock = NULL ): array
	{
		switch( $subBlock )
		{
			case 'percentagesolved':
				return array( 'app' => 'forums', 'title' => 'stats_percentagesolved', 'description' => 'stats_percentagesolved_desc', 'refresh' => 60, 'form' => true );


			case 'averagetimesolved':
				return array( 'app' => 'forums', 'title' => 'stats_averagetimesolved', 'description' => 'stats_averagetimesolved_desc', 'refresh' => 60, 'form' => true );

		}

		return [];
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	string
	 */
	public function getBlock( array|string $dateRange = NULL, string $subBlock = NULL ): string
	{
		/* Make sure someone isn't trying to manipulate the request or do something weird */
		if( !in_array( $subBlock, $this->getBlocks() ) )
		{
			return '';
		}

		$value			= 0;
		$previousValue	= 0;
		$nodeNames		= array();

		/* Build where clause in the event we are filtering */
		$where			= array();
		$previousWhere	= NULL;

		if( $dateRange !== NULL )
		{
			if( is_array( $dateRange ) )
			{
				$where = array(
					array( 'start_date > ?', $dateRange['start']->getTimestamp() ),
					array( 'start_date < ?', $dateRange['end']->getTimestamp() ),
				);
			}
			else
			{
				$currentDate	= new DateTime;
				$interval = static::getInterval( $dateRange );
				$initialTimestamp = $currentDate->sub( $interval )->getTimestamp();
				$where			= array( array( 'start_date > ?', $initialTimestamp ) );
				$previousWhere	= array( array( 'start_date BETWEEN ? AND ?', $currentDate->sub( $interval )->getTimestamp(), $initialTimestamp ) );
			}
		}
		else if ( isset( Request::i()->nodes ) and Request::i()->nodes )
		{
			$where = array( array( Db::i()->in( 'forum_id', explode( ',', Request::i()->nodes ) ) ) );
		}

		//$where[] = array( 'type=?', 'solved' );
		$where[] = array( Db::i()->in( 'forum_id', iterator_to_array( Db::i()->select( 'id', 'forums_forums', '(' . Db::i()->bitwiseWhere( Forum::$bitOptions['forums_bitoptions'], 'bw_enable_answers' ) . ') OR ( ' . Db::i()->bitwiseWhere( Forum::$bitOptions['forums_bitoptions'], 'bw_solved_set_by_moderator' ) . ' )' ) ) ) );

		if( Request::i()->nodes )
		{
			foreach( explode( ',', Request::i()->nodes ) as $nodeId )
			{
				$nodeNames[] = Forum::load( $nodeId )->_title;
			}
		}

		/* Get the current and previous values */
		switch( $subBlock )
		{
			case 'percentagesolved':
				$total	= Db::i()->select( 'COUNT(*)', 'forums_topics', $this->_modifyWhereClause( $where ) )->first();


				$solvedWhere = $this->_modifyWhereClause( $where );
				$solvedWhere[] = [ 'core_solved_index.id is not null' ];
				$solvedWhere[] = [ 'type=?', 'solved' ];
				$solved	= Db::i()->select( 'COUNT(*)', 'forums_topics', $solvedWhere )->join( 'core_solved_index', "core_solved_index.app='forums' AND core_solved_index.item_id=forums_topics.tid")->first();

				$value = $total ? round( $solved / $total * 100, 2 ) : 0;

				$previousTotal = $previousSolved = NULL;

				if( $previousWhere !== NULL )
				{
					$previousTotal	= Db::i()->select( 'COUNT(*)', 'forums_topics', $this->_modifyWhereClause( $previousWhere ) )->first();
					$previousSolved	= Db::i()->select( 'COUNT(*)', 'forums_topics', array_merge( $this->_modifyWhereClause( $previousWhere ), array( array( 'core_solved_index.id IS NOT NULL' ) ) ) )->join( 'core_solved_index', "core_solved_index.app='forums' AND core_solved_index.item_id=forums_topics.tid")->first();

					$previousValue = $previousTotal ? round( $previousSolved / $previousTotal * 100, 2 ) : 0;
				}

				return Theme::i()->getTemplate( 'stats', 'forums' )->solvedPercentage( $value, $total, $solved, $previousValue, $previousTotal, $previousSolved, $nodeNames );


			case 'averagetimesolved':
				$value	= Db::i()->select( 'AVG(CAST(core_solved_index.solved_date AS SIGNED)-forums_topics.start_date)', 'forums_topics', array_merge( $this->_modifyWhereClause( $where ), array( array( 'core_solved_index.id IS NOT NULL' ) ) ) )->join( 'core_solved_index', "core_solved_index.app='forums' AND core_solved_index.item_id=forums_topics.tid")->first();

				if( $previousWhere !== NULL )
				{
					$previousValue = Db::i()->select( 'AVG(CAST(core_solved_index.solved_date AS SIGNED)-forums_topics.start_date)', 'forums_topics', array_merge( $this->_modifyWhereClause( $previousWhere ), array( array( 'core_solved_index.id IS NOT NULL' ) ) ) )->join( 'core_solved_index', "core_solved_index.app='forums' AND core_solved_index.item_id=forums_topics.tid")->first();
				}

				return Theme::i()->getTemplate( 'stats', 'forums' )->timeToSolved( $value, $previousValue, $nodeNames );

		}

		return Theme::i()->getTemplate( 'stats', 'forums' )->solvedPercentage( $value, $previousValue, $nodeNames );
	}

	/**
	 * Modify the where clause to apply other filters
	 * 
	 * @param array $where	Current where clause
	 * @return	array
	 */
	protected function _modifyWhereClause( array $where ): array
	{
		$where[] = array( Db::i()->in( 'approved', array( -2, -3 ), TRUE ) );
		
		$where = array_merge( $where, Topic::overviewStatisticsWhere() );
		
		if( Request::i()->nodes )
		{
			$where[] = array( Db::i()->in( 'forum_id', explode( ',', Request::i()->nodes ) ) );
		}

		return $where;
	}

	/**
	 * Return block filter form, or the updated block result upon submit
	 *
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	array
	 */
	public function getBlockForm( string $subBlock = NULL ): mixed
	{
		/* Make sure someone isn't trying to manipulate the request or do something weird */
		if( !in_array( $subBlock, $this->getBlocks() ) )
		{
			return '';
		}

		$form = new Form;
		$form->attributes['data-controller'] = 'core.admin.stats.nodeFilters';
		$form->attributes['data-block'] = Request::i()->blockKey;
		$form->attributes['data-subblock'] = $subBlock;
		$form->add( new Node( Forum::$nodeTitle, NULL, TRUE, array( 'class' => '\IPS\forums\Forum', 'multiple' => TRUE, 'clubs' => FALSE ) ) );

		if( $values = $form->values() )
		{
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
					'end'	=> new DateTime( Date::_convertDateFormat( Request::i()->end ), $timezone )
				);
			}

			return $this->getBlock( $dateFilters, $subBlock );
		}

		return $form;
	}
}