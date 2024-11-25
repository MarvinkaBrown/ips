<?php
/**
 * @brief		archive Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		24 Jan 2014
 */

namespace IPS\forums\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content\Search\Index;
use IPS\Db;
use IPS\forums\Topic;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Task;
use IPS\Task\Exception;
use UnderflowException;
use function defined;
use function intval;
use function IPS\Cicloud\getForumArchiveDb;
use const IPS\CIC2;
use const IPS\DISABLE_ARCHIVING;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * archive Task
 */
class archive extends Task
{
	/**
	 * @brief	Number of items to process per cycle
	 */
	const PROCESS_PER_BATCH = 250;
	
	/**
	 * @brief	Storage database
	 */
	protected Db|null $storage = null;
	
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
		/* If archiving is disabled, disable the task */
		if ( !Settings::i()->archive_on )
		{
			Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'archive' ) );
		}
		
		/* Otherwise, let's do this! */
		else
		{
            if( defined( 'DISABLE_ARCHIVING') AND DISABLE_ARCHIVING )
            {
                $working = Db::i()->select('count(*)', 'forums_topics', [ 'topic_archive_status=?', Topic::ARCHIVE_WORKING ] )->first();

                if( !$working )
                {
                    Db::i()->update( 'core_tasks', [ 'enabled' => 0 ], [ '`key`=?', 'archive' ] );
                    Db::i()->update( 'forums_topics', [ 'topic_archive_status' => Topic::ARCHIVE_RESTORE ], [ 'topic_archive_status=?', Topic::ARCHIVE_DONE ] );
                    return NULL;
                }
            }

			/* Init */
			$postsProcessed = 0;
			
			/* Connect to the remote DB if needed */
			if ( CIC2 )
			{
				$this->storage = getForumArchiveDb();
			}
			else
			{
				$this->storage = !Settings::i()->archive_remote_sql_host ? Db::i() : Db::i( 'archive', array(
					'sql_host'		=> Settings::i()->archive_remote_sql_host,
					'sql_user'		=> Settings::i()->archive_remote_sql_user,
					'sql_pass'		=> Settings::i()->archive_remote_sql_pass,
					'sql_database'	=> Settings::i()->archive_remote_sql_database,
					'sql_port'		=> Settings::i()->archive_sql_port,
					'sql_socket'	=> Settings::i()->archive_sql_socket,
					'sql_tbl_prefix'=> Settings::i()->archive_sql_tbl_prefix,
					'sql_utf8mb4'	=> isset( Settings::i()->sql_utf8mb4 ) ? Settings::i()->sql_utf8mb4 : FALSE
				) );
			}

			/* Do we have a topic we are working on? */
			try
			{
				/* Get the topic */
				$topic = Topic::constructFromData( Db::i()->select( '*', 'forums_topics', array( 'topic_archive_status=?', Topic::ARCHIVE_WORKING ) )->first() );
								
				/* Do as many posts as we can */
				$offset	= $this->storage->select( 'MAX(archive_id)', 'forums_archive_posts', array( 'archive_topic_id=?', $topic->tid ) )->first();
				$total	= Db::i()->select( 'COUNT(*)', 'forums_posts', array( 'pid>? AND topic_id=?', intval( $offset ), $topic->tid ) )->join( 'forums_topics', 'tid=topic_id' )->first();
				$posts	= Db::i()->select( 'forums_posts.*,forums_topics.forum_id', 'forums_posts', array( 'pid>? AND topic_id=?', intval( $offset ), $topic->tid ), 'pid ASC', static::PROCESS_PER_BATCH )->join( 'forums_topics', 'tid=topic_id' );
				foreach ( $posts as $post )
				{					
					$this->archive( $post, $post['forum_id'] );
					$postsProcessed++;
				}
				
				/* If we did them all, mark that this topic is done and remove from search index */
				if ( $postsProcessed == $total )
				{
					$topic->topic_archive_status = Topic::ARCHIVE_DONE;
					$topic->save();
					
					Db::i()->delete( 'forums_posts', array( 'topic_id=?', $topic->tid ) );
					
					Index::i()->removeFromSearchIndex( $topic );
				}
			}
			catch ( UnderflowException $e ) { }

			/* Can we do even more? */
			$query = array_merge( array( array( 'topic_archive_status=? and approved !=?', Topic::ARCHIVE_NOT, -2 ) ), Application::load('forums')->archiveWhere( iterator_to_array( Db::i()->select( '*', 'forums_archive_rules' ) ) ) );
			if ( $postsProcessed < static::PROCESS_PER_BATCH )
			{
				/* Loop topics */
				foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'forums_topics', $query, 'tid ASC', 250 ), 'IPS\forums\Topic' ) as $topic )
				{					
					/* First set it as working */
					Db::i()->update( 'forums_topics', array( 'topic_archive_status' => Topic::ARCHIVE_WORKING ), array( 'tid=?', $topic->tid ) );
					
					/* Do as many posts as we can */
					foreach ( Db::i()->select( '*', 'forums_posts', array( 'topic_id=?', $topic->tid ), 'pid ASC', static::PROCESS_PER_BATCH - $postsProcessed ) as $post )
					{						
						$this->archive( $post, $topic->forum_id );
						$postsProcessed++;
						if ( $postsProcessed >= static::PROCESS_PER_BATCH )
						{
							break 2;
						}
					}
										
					/* Set that it's done */
					Db::i()->update( 'forums_topics', array( 'topic_archive_status' => Topic::ARCHIVE_DONE ), array( 'tid=?', $topic->tid ) );
					Db::i()->delete( 'forums_posts', array( 'topic_id=?', $topic->tid ) );

					/* Remove from search index */
					Index::i()->removeFromSearchIndex( $topic );
				}
			}
		}
		
		return NULL;
	}
	
	/**
	 * Archive
	 *
	 * @param array $post		The post to be archived
	 * @param int|null $forumId	The forum id, if known
	 * @return	void
	 */
	protected function archive( array $post, int $forumId=NULL ) : void
	{		
		/* Translate the fields */
		$archive = array(
			'archive_id'				=> intval( $post['pid'] ),
			'archive_author_id'			=> intval( $post['author_id'] ),
			'archive_author_name'		=> $post['author_name'],
			'archive_ip_address'		=> $post['ip_address'],
			'archive_content_date'		=> intval( $post['post_date'] ),
			'archive_content'			=> $post['post'],
			'archive_queued'			=> $post['queued'],
			'archive_topic_id'			=> intval( $post['topic_id'] ),
			'archive_is_first'			=> intval( $post['new_topic'] ),
			'archive_bwoptions'			=> $post['post_bwoptions'],
			'archive_added'				=> time(),
			'archive_attach_key'		=> $post['post_key'],
			'archive_html_mode'			=> $post['post_htmlstate'],
			'archive_show_edited_by'	=> intval( $post['append_edit'] ),
			'archive_edit_time'			=> intval( $post['edit_time'] ),
			'archive_edit_name'			=> (string) $post['edit_name'],
			'archive_edit_reason'		=> $post['post_edit_reason'],
			'archive_field_int'			=> intval( $post['post_field_int'] ),
			'archive_forum_id'			=> $forumId ?: Db::i()->select( 'forum_id', 'forums_topics', array( 'tid=?', intval( $post['topic_id'] ) ) )->first()
		);
		
		/* Insert */
		$this->storage->replace( 'forums_archive_posts', $archive );
		
		/* Update reports and promote tables */
		Db::i()->update( 'core_rc_index', array( 'class' => 'IPS\forums\Topic\ArchivedPost' ), array( 'class=? AND content_id=?', 'IPS\forums\Topic\Post', $post['pid'] ) );
		Db::i()->update( 'core_content_promote', array( 'promote_class' => 'IPS\forums\Topic\ArchivedPost' ), array( 'promote_class=? AND promote_class_id=?', 'IPS\forums\Topic\Post', $post['pid'] ) );
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
	public function cleanup() : void
	{
		
	}
}