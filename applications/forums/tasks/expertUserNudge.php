<?php
/**
 * @brief		expertUserNudge Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	forums
 * @since		25 Jul 2023
 */

namespace IPS\forums\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Db;
use IPS\forums\Forum;
use IPS\forums\Topic;
use IPS\Member;
use IPS\Notification;
use IPS\Platform\Bridge;
use IPS\Settings;
use IPS\Task;
use IPS\Task\Exception;
use OutOfRangeException;
use function array_keys;
use function defined;
use function iterator_to_array;
use function time;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * expertUserNudge Task
 */
class expertUserNudge extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	Exception
	 */
	public function execute() : mixed
	{
		if ( ! Settings::i()->forums_solved_nudge_experts or !Bridge::i()->featureIsEnabled( 'experts' ) )
		{
			return NULL;
		}

		$experts = Db::i()->select( 'member_id, node_id', 'core_expert_users', [], "", [ 0, 50 ]);
		$nodeExperts = [];

		foreach( $experts as $expert )
		{
			$nodeExperts[$expert['node_id']][] = $expert['member_id'];
		}

		$where = [
			[ Db::i()->in( 'approved', array( -2, -3 ), TRUE ) ],
			[ 'start_date > ? and start_date < ? and topic_answered_pid=0', ( time() - ( 365 * 86400) ), ( time() - ( (int) Settings::i()->forums_solved_nudge_experts * 86400 ) ) ], //@todo limit by nudges sent/date?
			[ Db::i()->in( 'forum_id', iterator_to_array( Db::i()->select( 'id', 'forums_forums', '(' . Db::i()->bitwiseWhere( Forum::$bitOptions['forums_bitoptions'], 'bw_solved_set_by_member' ) . ') AND (' . Db::i()->bitwiseWhere( Forum::$bitOptions['forums_bitoptions'], 'bw_solved_set_by_moderator' ) . ')' ) ) ) ],
			[ Db::i()->in( 'forum_id', array_keys( $nodeExperts ) ) ]
		];

		$expertsToNotify = [];
		foreach( Db::i()->select( '*', 'forums_topics', $where, 'start_date ASC', [ 0, 50 ] ) as $row )
		{
			// Do we have an expert for this forum?
			if( isset( $nodeExperts[ $row['forum_id'] ] ) )
			{
				foreach( $nodeExperts[ $row['forum_id'] ] as $expert )
				{
					// make sure we didn't start the fire
					if( $expert !== $row['starter_id'] )
					{
						$expertsToNotify[ $expert ][] = Topic::constructFromData( $row );
					}
				}
			}
		}

		foreach ( $expertsToNotify as $expertId => $topics )
		{
			try
			{
				$expert = Member::load( $expertId );
				if( !$expert->members_bitoptions['expert_user_disabled'] and !$expert->members_bitoptions['expert_user_blocked'] and ( $expert->expert_nudge < time() - ( 86400 * 7 ) ) )
				{
					$topicIds = [];
					foreach( $topics as $topic )
					{
						$topicIds[] = $topic->tid;
					}

					/* Skip any topics that we already replied to */
					$replied = iterator_to_array(
						Db::i()->select( 'topic_id', 'forums_posts', array(
							array( Db::i()->in( 'topic_id', $topicIds ) ),
							array( 'author_id=?', $expert->member_id )
						) )
					);

					$topicIds = array_diff( $topicIds, $replied );

					/* Rebuild the topic list with only the remaining topics */
					$topicsToInclude = [];
					foreach( $topics as $topic )
					{
						if( in_array( $topic->tid, $topicIds ) )
						{
							$topicsToInclude[] = $topic;
						}
					}

					if( count( $topicIds ) )
					{
						$notification = new Notification( Application::load( 'cloud' ), 'new_topics_to_review', $expert, [ $topicsToInclude, $expert ], [ 'topics' => $topicIds ] );
						$notification->recipients->attach( $expert );
						$notification->send();
					}

					$expert->expert_nudge = time();
					$expert->save();
				}
			}
			catch ( OutOfRangeException $e ) {}
		}


		return NULL;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}