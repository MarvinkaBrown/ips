<?php
/**
 * @brief		Stats Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Nov 2013
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Member;
use IPS\Session\Store;
use IPS\Settings;
use IPS\Widget;
use IPS\Widget\StaticCache;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stats Widget
 */
class stats extends StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'stats';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
	


	/**
	 * Specify widget configuration
	 *
	 * @param	Form|NULL	$form	Form helper
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
 	{
		$form = parent::configuration( $form );

		$mostOnline = json_decode( Settings::i()->most_online, TRUE );
		$form->add( new Number( 'stats_most_online', $mostOnline['count'], TRUE ) );
		
		return $form;
 	}
 	
 	/**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( array $values ): array
 	{
 		if ( Member::loggedIn()->isAdmin() and Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_recount_content' ) )
 		{
 			$mostOnline = array( 'count' => $values['stats_most_online'], 'time' => time() );
			Settings::i()->changeValues( array( 'most_online' => json_encode( $mostOnline ) ) );

			unset( $values['stats_most_online'] );

 			Widget::deleteCaches( 'stats', 'core' );
 		}

 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$stats = array();
		$mostOnline = json_decode( Settings::i()->most_online, TRUE );

		/* fetch only successful registered members ; if this needs to be changed, please review the other areas where we have the name<>? AND email<>? condition */
		$where = array( 'completed=?', true );

		/* Member count */
		$stats['member_count'] = Db::i()->select( 'COUNT(*)', 'core_members', $where )->first();
		
		/* Most online */
		$count = Store::i()->getOnlineUsers( Store::ONLINE_GUESTS | Store::ONLINE_MEMBERS | Store::ONLINE_COUNT_ONLY );
		if( $count > $mostOnline['count'] )
		{
			$mostOnline = array( 'count' => $count, 'time' => time() );
			Settings::i()->changeValues( array( 'most_online' => json_encode( $mostOnline ) ) );
		}
		$stats['most_online'] = $mostOnline;
				
		/* Last Registered Member */
		$where   = array( array( "completed=1 AND temp_ban != -1" ) );
		$where[] = array( '( ! ' . Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ) . ' )' );
		$where[] = array( 'member_id NOT IN(?)', Db::i()->select( 'member_id', 'core_validating', array( 'new_reg=1' ) ) );
		$where[] = array( 'NOT(members_bitoptions2 & ?)', Member::$bitOptions['members_bitoptions']['members_bitoptions2']['is_support_account'] );

		try
		{
			$stats['last_registered'] = Member::constructFromData( Db::i()->select( 'core_members.*', 'core_members', $where, 'core_members.member_id DESC', array( 0, 1 ) )->first() );
		}
		catch( UnderflowException $ex )
		{
			$stats['last_registered'] = NULL;
		}
		
		/* Display */		
		return $this->output( $stats );
	}
}