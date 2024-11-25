<?php
/**
 * @brief		helpful
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		16 Jul 2023
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\DateRange;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * helpful
 */
class helpful extends Controller
{
	/**
	 * @brief	Number of results per page
	 */
	const PER_PAGE = 25;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'helpful_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$tabs		= array(
			'solved'		=> 'stats_helpful_solved',
			'helpful'		=> 'stats_helpful_helpful',
		);
		Request::i()->tab ??= 'solved';
		$activeTab	= ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'solved';

		switch( $activeTab )
		{
			case 'helpful':
				 $output = $this->_output( 'helpful' );
				break;

			default:
				$output = $this->_output( 'solved' );
				break;
		}

		$params = array(
			'start' => Request::i()->start,
			'end' => Request::i()->end,
		);

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = Theme::i()->getTemplate( 'global' )->paddedBlock( $output, NULL, "i-padding_3" );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_helpful');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $output, Url::internal( "app=core&module=stats&controller=helpful" )->setQueryString( $params ), 'tab', '', 'i-padding_3' );
		}
	}

	/**
	 * Output for tab
	 *
	 * @param string $type
	 * @return	string
	 */
	public function _output( string $type='solved' ): string
	{
		$where = array();
		if ( isset( Request::i()->form ) )
		{
			$form = new Form( 'form', 'go' );

			$default = array(
				'start' => Request::i()->start ? DateTime::ts( Request::i()->start ) : NULL,
				'end' => Request::i()->end ? DateTime::ts( Request::i()->end ) : NULL
			);

			$form->add( new DateRange( 'stats_date_range', $default, FALSE, array( 'start' => array( 'max' => DateTime::ts( time() )->setTime( 0, 0, 0 ), 'time' => FALSE ), 'end' => array( 'max' => DateTime::ts( time() )->setTime( 23, 59, 59 ), 'time' => FALSE ) ) ) );

			if ( !$values = $form->values() )
			{
				return (string) $form;
			}
		}

		/* Figure out start and end parameters for links */
		$params = array(
			'start' => !empty( $values['stats_date_range']['start'] ) ? $values['stats_date_range']['start']->getTimestamp() : Request::i()->start,
			'end' => !empty( $values['stats_date_range']['end'] ) ? $values['stats_date_range']['end']->getTimestamp() : Request::i()->end,
			'tab' => $type
		);

		Request::i()->start = $params['start'];
		Request::i()->end = $params['end'];

		if( $params['start'] )
		{
			$where[] = array( 'solved_date>?', $params['start'] );
		}

		if( $params['end'] )
		{
			$where[] = array( 'solved_date<?', $params['end'] );
		}

		$where[] = [ 'hidden=0' ];

		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		$where[] = [ 'type=?', $type ];
		try
		{
			$total = Db::i()->select( 'COUNT(DISTINCT(core_solved_index.member_id))', 'core_solved_index', $where )->join( 'core_members', 'core_members.member_id = core_solved_index.member_id')->first();
		}
		catch ( UnderflowException $e )
		{
			$total = 0;
		}

		if( $total )
		{
			$select	= Db::i()->select( 'core_solved_index.member_id as member_id, count(*) as count', 'core_solved_index', $where, 'count DESC', array( ( $page - 1 ) * static::PER_PAGE, static::PER_PAGE ), 'member_id' )->join( 'core_members', 'core_members.member_id = core_solved_index.member_id');
			$mids = array();

			foreach( $select as $row )
			{
				$mids[] = $row['member_id'];
			}

			$members = array();

			if ( count( $mids ) )
			{
				$members = iterator_to_array( Db::i()->select( '*', 'core_members', array( Db::i()->in( 'member_id', $mids ) ) )->setKeyField('member_id') );
			}

			$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
				Url::internal( 'app=core&module=stats&controller=helpful' )->setQueryString( $params ),
				ceil( $total / static::PER_PAGE ),
				$page,
				static::PER_PAGE,
				FALSE
			);

			Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
					'title'		=> 'stats_date_range',
					'icon'		=> 'calendar',
					'link'		=> Url::internal( 'app=core&module=stats&controller=helpful&form=1' )->setQueryString( $params ),
					'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('stats_date_range') )
				)
			);

			$output = Theme::i()->getTemplate('stats' )->helpful( $select, $pagination, $members, $total );
		}
		else
		{
			/* Return the no results message */
			$output = Theme::i()->getTemplate( 'global', 'core' )->block( Member::loggedIn()->language()->addToStack('menu__core_stats_helpful'), Member::loggedIn()->language()->addToStack('no_results'), FALSE , 'i-padding_3', NULL, FALSE );
		}

		return $output;
	}
}