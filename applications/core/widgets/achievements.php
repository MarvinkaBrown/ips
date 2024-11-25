<?php
/**
 * @brief		achievements Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		05 Mar 2021
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Badge;
use IPS\core\Achievements\Rank;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Translatable;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Settings;
use IPS\Widget\Customizable;
use IPS\Widget\StaticCache;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * achievements Widget
 */
class achievements extends StaticCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'achievements';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
		

	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
 		$form = parent::configuration( $form );

		$form->add( new Translatable( 'widget_feed_title', isset( $this->configuration['language_key'] ) ? NULL : Member::loggedIn()->language()->addToStack( 'achievements_widget_title' ), FALSE, array( 'app' => 'core', 'key' => ( $this->configuration['language_key'] ?? NULL ) ) ) );
		$form->add( new CheckboxSet( 'achievements_to_show', isset( $this->configuration['achievements_to_show'] ) ? explode( ',', $this->configuration['achievements_to_show'] ) : [ 'badges', 'ranks' ], TRUE, [ 'options' => [
			'badges'	=> 'block_achievements_badges',
			'ranks'		=> 'block_achievements_rank',
		] ] ) );
		$form->add( new Number( 'number_to_show', $this->configuration['number_to_show'] ?? 5, TRUE, array( 'max' => 25 ) ) );
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
		if ( !isset( $this->configuration['language_key'] ) )
		{
			$this->configuration['language_key'] = 'widget_title_' . md5( mt_rand() );
		}
		$values['language_key'] = $this->configuration['language_key'];
		Lang::saveCustom( 'core', $this->configuration['language_key'], $values['widget_feed_title'] );
		unset( $values['widget_feed_title'] );

		$values['achievements_to_show'] = implode( ',', $values['achievements_to_show'] );
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$result = [];
		$toShow = explode( ',', ( $this->configuration['achievements_to_show'] ?? 'badges,ranks' ) );
		$countToShow = $this->configuration['number_to_show'] ?? 5;
		$exclude = json_decode( Settings::i()->rules_exclude_groups, TRUE );

		foreach( Group::groups() as $group )
		{
			if( ! $group->g_view_board )
			{
				$exclude[] = $group->g_id;
			}
		}

		if ( is_array( $exclude ) and count( $exclude ) )
		{
			$subWhere[] = [ Db::i()->in( 'member_group_id', $exclude ) . ' OR ' . Db::i()->findInSet( 'mgroup_others', $exclude ) ];
		}

		$subWhere[] = [ 'temp_ban != 0 OR ' . Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ) ];
		$subQuery = Db::i()->select( 'member_id', 'core_members', $subWhere );

		if ( Badge::show() and in_array( 'badges', $toShow ) )
		{
			foreach ( Db::i()->select( '*', 'core_member_badges', ( $subQuery ? [ Db::i()->in( 'core_member_badges.member', $subQuery, TRUE ) ] : NULL ), 'datetime DESC', $countToShow )->join( 'core_badges', 'core_member_badges.badge=core_badges.id' ) as $earnedBadge )
			{
				try
				{
					$member =  Member::load( $earnedBadge['member'] );
					if ( !$member->member_id )
					{
						throw new OutOfRangeException;
					}
					
					$result[] = [
						'type'		=> 'badge',
						'badge'		=> Badge::constructFromData( $earnedBadge ),
						'member'	=> $member,
						'date'		=> $earnedBadge['datetime']
					];
				}
				catch ( OutOfRangeException $e ) { }
			}
		}
		
		if ( Rank::show() and in_array( 'ranks', $toShow ) )
		{
			$query = [ [ 'new_rank IS NOT NULL' ] ];

			if ( $subQuery )
			{
				$query[] = [ Db::i()->in( 'core_points_log.member', $subQuery, TRUE ) ];
			}
			
			foreach ( Db::i()->select( '*', 'core_points_log', $query, 'datetime DESC', $countToShow ) as $earnedRank )
			{
				try
				{
					$member =  Member::load( $earnedRank['member'] );
					if ( !$member->member_id )
					{
						throw new OutOfRangeException;
					}
					
					$result[] = [
						'type'		=> 'rank',
						'rank'		=> Rank::load( $earnedRank['new_rank'] ),
						'member'	=> $member,
						'date'		=> $earnedRank['datetime']
					];
				}
				catch ( OutOfRangeException $e ) { }
			}
		}
		
		if ( ( Badge::show() and in_array( 'badges', $toShow ) ) and ( Rank::show() and in_array( 'ranks', $toShow ) ) )
		{
			usort( $result, function( $a, $b ) { return $b['date'] <=> $a['date']; } );
			$result = array_splice( $result, 0, $countToShow );
		}
		
		return $result ? $this->output( $result, isset( $this->configuration['language_key'] ) ? Member::loggedIn()->language()->addToStack( $this->configuration['language_key'], FALSE, array( 'escape' => TRUE ) ) : Member::loggedIn()->language()->addToStack( 'achievements_widget_title' ) ) : '';
	}

	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete() : void
	{
		Lang::deleteCustom( 'core', $this->configuration['language_key'] );
	}
}