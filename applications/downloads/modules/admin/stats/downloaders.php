<?php
/**
 * @brief		Top Downloaders
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		17 Dec 2013
 */

namespace IPS\downloads\modules\admin\stats;

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
use function count;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Top Downloaders
 */
class downloaders extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'downloaders_manage' );
		parent::execute();
	}

	/**
	 * Top Downloaders
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$where = array( array( 'dmid>0' ) );
		
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
				Output::i()->output = $form;
				return;
			}
		}

		/* Figure out start and end parameters for links */
		$params = array(
			'start' => !empty( $values['stats_date_range']['start'] ) ? $values['stats_date_range']['start']->getTimestamp() : Request::i()->start,
			'end' => !empty( $values['stats_date_range']['end'] ) ? $values['stats_date_range']['end']->getTimestamp() : Request::i()->end
		);

		if( $params['start'] )
		{
			$where[] = array( 'dtime>?', $params['start'] );
		}

		if( $params['end'] )
		{
			$where[] = array( 'dtime<?', $params['end'] );
		}
		
		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		try
		{
			$total = Db::i()->select( 'COUNT(DISTINCT(dmid))', 'downloads_downloads', $where )->first();
		}
		catch ( UnderflowException $e )
		{
			$total = 0;
		}

		/* Add the date range button */
		Output::i()->sidebar['actions'] = array(
			'settings'	=> array(
				'title'		=> 'stats_date_range',
				'icon'		=> 'calendar',
				'link'		=> Url::internal( 'app=downloads&module=stats&controller=downloaders&form=1' )->setQueryString( $params ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('stats_date_range') )
			)
		);
		
		if( $total > 0 )
		{
			$select	= Db::i()->select( 'dmid, count(*) as downloads', 'downloads_downloads', $where, 'downloads DESC', array( ( $page - 1 ) * static::PER_PAGE, static::PER_PAGE ), 'dmid' );
			$mids = array();

			foreach( $select as $row )
			{
				$mids[] = $row['dmid'];
			}

			$members = array();

			if ( count( $mids ) )
			{
				$members = iterator_to_array( Db::i()->select( '*', 'core_members', array( Db::i()->in( 'member_id', $mids ) ) )->setKeyField('member_id') );
			}

			$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
				Url::internal( 'app=downloads&module=stats&controller=downloaders' )->setQueryString( $params ),
				ceil( $total / static::PER_PAGE ),
				$page,
				static::PER_PAGE,
				FALSE
			);

			Output::i()->output = Theme::i()->getTemplate('stats')->downloadsTable( $select, $pagination, $members, $total );
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__downloads_stats_downloaders');
		}
		else
		{
			/* Return the no results message */
			Output::i()->output .= Theme::i()->getTemplate( 'global', 'core' )->block( Member::loggedIn()->language()->addToStack('menu__downloads_stats_downloaders'), Member::loggedIn()->language()->addToStack('no_results'), FALSE , 'i-padding_3', NULL, TRUE );
		}
	}
}