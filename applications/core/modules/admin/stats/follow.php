<?php
/**
 * @brief		follow
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		10 Sep 2021
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
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
 * follow
 */
class follow extends Controller
{
	
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
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
		Dispatcher::i()->checkAcpPermission( 'follow_manage' );
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
			'followers'		=> 'stats_followers_title',
			'following'		=> 'stats_following_title',
		);
		$activeTab	= ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'followers';

		$where = array( 'core_follow.follow_app=? and core_follow.follow_area=?', 'core', 'member' );
		
		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		$column = ( $activeTab == "followers" ) ? 'follow_rel_id' : 'follow_member_id';
		
		try
		{
			$total = Db::i()->select( 'COUNT(DISTINCT(core_follow.' . $column . '))', 'core_follow', $where )->join( 'core_members', 'core_members.member_id = core_follow.' . $column )->first();
		}
		catch ( UnderflowException $e )
		{
			$total = 0;
		}

		if( $total )
		{
			$select	= Db::i()->select( 'core_follow.' . $column . ', count(*) as count', 'core_follow', $where, 'count DESC', array( ( $page - 1 ) * static::PER_PAGE, static::PER_PAGE ), $column )->join( 'core_members', 'core_members.member_id = core_follow.' . $column );
			$mids = array();
			
			foreach( $select as $row )
			{
				$mids[] = $row[ $column ];
			}

			$members = array();

			if ( count( $mids ) )
			{
				$members = iterator_to_array( Db::i()->select( '*', 'core_members', array( Db::i()->in( 'member_id', $mids ) ) )->setKeyField('member_id') );
			}

			$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
				Url::internal( 'app=core&module=stats&controller=follow' ),
				ceil( $total / static::PER_PAGE ),
				$page,
				static::PER_PAGE,
				FALSE
			);

			$output = Theme::i()->getTemplate('stats' )->topFollow( $select, $pagination, $members, $total, $column, $activeTab );
		}
		else
		{
			$output= Theme::i()->getTemplate( 'global', 'core' )->block( NULL, Member::loggedIn()->language()->addToStack('no_results'), FALSE , 'i-padding_3', NULL, TRUE );
		}
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = Theme::i()->getTemplate( 'global' )->paddedBlock( $output, NULL, "i-padding_3" );
		}
		else
		{	
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'menu__core_stats_follow' );
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $output, Url::internal( "app=core&module=stats&controller=follow" ), 'tab', '', 'i-padding_3' );
		}
			
	}
	
	// Create new methods with the same name as the 'do' parameter which should execute it
}