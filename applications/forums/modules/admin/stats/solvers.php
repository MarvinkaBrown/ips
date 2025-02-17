<?php
/**
 * @brief		solvers
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		14 Dec 2023
 */

namespace IPS\forums\modules\admin\stats;

use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Member\Group;
use IPS\Theme;
use IPS\Output;

use function defined;
use function header;

use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_SELECT;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * solvers
 */
class solvers extends Controller
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
	public function execute(): void
	{
		Dispatcher::i()->checkAcpPermission( 'topics_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$table = new TableDb( 'core_solved_index', Url::internal( "app=forums&module=stats&controller=solvers" ) );
		$table->langPrefix = 'top_solvers_';
		$table->groupBy = array( 'core_solved_index.member_id', /*'core_members.name', 'core_members.member_group_id'*/ );
		$table->selects = array( 'COUNT(core_solved_index.member_id) AS count', 'core_solved_index.member_id', 'core_members.name', 'MAX(core_solved_index.solved_date) AS date', 'core_members.member_group_id' );
		$table->joins	= array( array( 'from' => 'core_members', 'where' => 'core_solved_index.member_id=core_members.member_id', 'type' => 'LEFT' ) );
		$table->include	= array( 'name', 'count', 'date', 'member_group_id' );
		$table->parsers = array(
			'name'			=> function( $val, $row ) {
				return Theme::i()->getTemplate( 'global', 'core' )->userLinkWithPhoto( Member::load( $row['member_id'] ) );
			},
			'count'			=> function( $val, $row ) {
				$url	= Url::internal( "app=core&module=members&controller=profile&id={$row['member_id']}&do=solutions", 'front', 'profile_solutions', array( Friendly::seoTitle( $row['name'] ) ) );
				$count	= Member::loggedIn()->language()->formatNumber( $val );
				return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $url, TRUE, $count );
			},
			'date'	=> function( $val ) {
				if( $val )
				{
					return (string) DateTime::ts( $val );
				}
			},
			'member_group_id' => function( $val ) {
				return Group::load( $val )->name;
			}
		);
		
		$groups = array();
		foreach( Group::groups( showGuestGroups: FALSE ) AS $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		$table->advancedSearch['solved_date']		= SEARCH_DATE_RANGE;
		$table->advancedSearch['member_group_id']	= array( SEARCH_SELECT, array( 'options' => $groups, 'multiple' => TRUE, 'noDefault' => TRUE ) );
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'top_solvers' );
		Output::i()->output = (string) $table;
	}
}