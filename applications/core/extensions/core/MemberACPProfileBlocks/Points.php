<?php
/**
 * @brief		ACP Member Profile Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Feb 2021
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\core\Achievements\Rank;
use IPS\core\MemberACPProfile\Block;
use IPS\DateTime;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function get_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile Block
 */
class Points extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		return (string) Theme::i()->getTemplate('memberprofile')->rank( $this->member );
	}
	
	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
		{
			Output::i()->sidebar['actions']['addpoints'] = array(
				'primary' => true,
				'icon' => 'pencil',
				'link' => $this->member->acpUrl()->setQueryString('do', 'points')->csrf(),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('acp_profile_points_manage_title' ) ),
				'title' => Member::loggedIn()->language()->addToStack( 'acp_profile_edit_points', FALSE, [ 'sprintf' => $this->member->name ] ),
			);
		}

		$table = new Db( 'core_points_log', $this->member->acpUrl()->setQueryString( array( 'do' => 'editBlock', 'block' => get_class( $this ) ) ), [ [ '`member`=?', $this->member->member_id ] ] );
		$table->joins[] = [
			'select'	=> 'action,identifier',
			'from'		=> 'core_achievements_log',
			'where'		=> 'core_achievements_log.id=core_points_log.action_log',
			'type'		=> 'LEFT'
		];

		$table->include = [ 'action_log', 'new_rank', 'points', 'balance', 'datetime' ];
		$table->sortBy = $table->sortBy ?: 'datetime';
		$table->langPrefix = 'acp_points_log_table_';

		/* Filters */
		$table->filters = [
			'acp_manage_points_manual' => '(LENGTH(core_points_log.rules)=0)',
			'acp_manage_points_rule' => '(LENGTH(core_points_log.rules)>0)',
			'acp_manage_points_promote' => '(new_rank IS NOT NULL)'
		];

		$table->parsers = [
			'action_log'	=> function( $val, $row ) {
				$exploded = explode( '_', $row['action'] );

				if ( isset( $exploded[1] ) )
				{
                    try{
                        $extension = Application::load( $exploded[0] )->extensions( 'core', 'AchievementAction' )[$exploded[1]];
                        return $extension->logRow( $row['identifier'], explode( ',', $row['actor'] ) );
                    }
                    catch( OutOfRangeException $e )
                    {
                        return Member::loggedIn()->language()->addToStack( 'acp_points_rule_deleted');
                    }
				}
				else if ( isset( $row['rules'] ) and empty( $row['rules'] ) )
				{
					return Member::loggedIn()->language()->addToStack( 'acp_points_log_manual');
				}
				else
				{
					return Member::loggedIn()->language()->addToStack( 'acp_points_rule_deleted');
				}
			},
			'datetime' => function( $val ) {
				return DateTime::ts( $val );
			},
			'new_rank' => function( $val, $row )
			{
				if ( ! $val )
				{
					return '';
				}
				
				try
				{
					$rank = Rank::load( $val );
					return Theme::i()->getTemplate( 'global' )->genericLink( Url::internal( "app=core&module=achievements&controller=ranks", 'admin' ), $rank->_title );
				}
				catch( Exception $e )
				{
					return Member::loggedIn()->language()->addToStack('deleted');
				}
			}
		];

		Output::i()->title = Member::loggedIn()->language()->addToStack('acp_profile_points_manage_title');
		return Output::i()->output = Theme::i()->getTemplate( 'members' )->pointsLog( $table, $this->member );
	}
}