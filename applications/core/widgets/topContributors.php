<?php
/**
 * @brief		topContributors Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		02 Jul 2014
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
use IPS\Widget\PermissionCache;
use function array_slice;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * topContributors Widget
 */
class topContributors extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'topContributors';
	
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
		$topContributorsThisWeek = array();
		foreach ( Db::i()->select( array( 'member_received', 'rep_rating' ), 'core_reputation_index', array( 'member_received>0 AND rep_date>?', DateTime::create()->sub( new DateInterval( 'P1W' ) )->getTimestamp() ) ) as $rep )
		{
			if ( !isset( $topContributorsThisWeek[ $rep['member_received'] ] ) )
			{
				$topContributorsThisWeek[ $rep['member_received'] ] = $rep['rep_rating'];
			}
			else
			{
				$topContributorsThisWeek[ $rep['member_received'] ] += $rep['rep_rating'];
			}
		}
		arsort( $topContributorsThisWeek );
		$topContributorsThisWeek = array_slice( $topContributorsThisWeek, 0, $limit, TRUE );
		
		/* Load their data */	
		if( count( $topContributorsThisWeek ) )
		{
			foreach ( Db::i()->select( '*', 'core_members', Db::i()->in( 'member_id', array_keys( $topContributorsThisWeek ) ) ) as $member )
			{
				Member::constructFromData( $member );
			}
		}
		
		/* Display */
		return $this->output( $topContributorsThisWeek, $limit );
	}
}