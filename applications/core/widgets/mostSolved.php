<?php
/**
 * @brief		mostSolved Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		09 Mar 2020
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Member;
use IPS\Widget\Customizable;
use IPS\Widget\StaticCache;
use function array_slice;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * mostSolved Widget
 */
class mostSolved extends StaticCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'mostSolved';
	
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

 		$form->add( new Number( 'number_to_show', $this->configuration['number_to_show'] ?? 5, TRUE, array( 'max' => 25 ) ) );
 		return $form;
 	} 

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		/* How many? */
		$limit = $this->configuration['number_to_show'] ?? 5;
		
		/* Work out who has got the most reputation this week... */
		$topSolvedThisWeek = array();
		foreach ( Db::i()->select( 'member_id', 'core_solved_index', array( 'member_id > 0 AND solved_date>? AND type=? AND hidden=0', DateTime::create()->sub( new DateInterval( 'P1W' ) )->getTimestamp(), 'solved' ) ) as $memberId )
		{
			if ( !isset( $topSolvedThisWeek[ $memberId ] ) )
			{
				$topSolvedThisWeek[ $memberId ] = 1;
			}
			else
			{
				$topSolvedThisWeek[ $memberId ]++;
			}
		}
		arsort( $topSolvedThisWeek );
		$topSolvedThisWeek = array_slice( $topSolvedThisWeek, 0, $limit, TRUE );
		
		/* Load their data */	
		if( count( $topSolvedThisWeek ) )
		{
			foreach ( Db::i()->select( '*', 'core_members', Db::i()->in( 'member_id', array_keys( $topSolvedThisWeek ) ) ) as $member )
			{
				Member::constructFromData( $member );
			}
		}
		
		/* Display */
		return $this->output( $topSolvedThisWeek, $limit );
	}
}