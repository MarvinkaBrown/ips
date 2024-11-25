<?php
/**
 * @brief		5.0.0 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		20 Sep 2022
 */

namespace IPS\forums\setup\upg_500001;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Upgrade Code
 */
class Upgrade
{
	/**
	 * ...
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		/* Convert Q&A to Solved */
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'forums_forums', array( 'forums_bitoptions & ?', 4 ) ), '\IPS\forums\Forum' ) as $forum )
		{
			$forum->forums_bitoptions['bw_enable_answers'] = FALSE;
			$forum->forums_bitoptions['bw_solved_set_by_moderator'] = TRUE;
			$forum->forums_bitoptions['bw_solved_set_by_member'] = TRUE;
			$forum->save();
		}

		return TRUE;
	}

	/**
	 * Update the last post info for forums
	 *
	 * @return bool|array
	 */
	public function step2() : bool|array
	{
		/* Update last topic info per forum */
		Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\forums\Forum', 'count' => 0 ), 4, array( 'class' ) );

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}