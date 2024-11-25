<?php
/**
 * @brief		Moderator Permissions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		07 Sep 2023
 */

namespace IPS\forums\extensions\core\ModeratorPermissions;



/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\ModeratorPermissionsAbstract;
use IPS\Content\Search\Index;
use IPS\Db;
use IPS\forums\Forum;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Permissions
 */
class Topic extends ModeratorPermissionsAbstract
{
	/**
	 * Get Permissions
	 *
	 * @param array $toggles
	 * @code
	 	return array(
	 		'key'	=> 'YesNo',	// Can just return a string with type
	 		'key'	=> array(	// Or an array for more options
	 			'YesNo',			// Type
	 			array( ... ),		// Options (as defined by type's class)
	 			'prefix',			// Prefix
	 			'suffix',			// Suffix
	 		),
	 		...
	 	);
	 * @endcode
	 * @return	array
	 */
	public function getPermissions( array $toggles ): array
	{
		return array();
	}

	/**
	 * Get Permissions
	 *
	 * @param	array	$toggles	Toggle data
	 * @code
	return array(
	'key'	=> 'YesNo',	// Can just return a string with type
	'key'	=> array(	// Or an array for more options
	'YesNo',			// Type
	array( ... ),		// Options (as defined by type's class)
	'prefix',			// Prefix
	'suffix',			// Suffix
	),
	...
	);
	 * @endcode
	 * @return	array
	 */
	public function getContentPermissions( array $toggles ): array
	{
		$return = array();

		if ( Db::i()->select( 'COUNT(*)', 'forums_forums', 'can_view_others=0 AND club_id IS NULL' )->first() )
		{
			$return['can_read_all_topics'] = 'YesNo';
		}

		if ( Db::i()->select( 'COUNT(*)', 'forums_forums', '(' . Db::i()->bitwiseWhere( Forum::$bitOptions['forums_bitoptions'], 'bw_enable_answers' ) . ') OR ( ' . Db::i()->bitwiseWhere( Forum::$bitOptions['forums_bitoptions'], 'bw_solved_set_by_moderator' ) . ' )' )->first() )
		{
			$return['can_set_best_answer'] = 'YesNo';
		}
		if ( Db::i()->select( 'COUNT(*)', 'forums_topic_mmod' )->first() )
		{
			$return['can_use_saved_actions'] = 'YesNo';
		}

		return $return;
	}

	/**
	 * After change
	 *
	 * @param	array	$moderator	The moderator
	 * @param	array	$changed	Values that were changed
	 * @return	void
	 */
	public function onContentChange( array $moderator, array $changed ) : void
	{
		if ( $changed == '*' or array_key_exists( 'can_read_all_topics', $changed ) OR array_key_exists( 'forums', $changed ) )
		{
			$deleteFirst = TRUE;
			if ( $changed == '*' or !empty( $changed['can_read_all_topics'] ) )
			{
				$deleteFirst = FALSE;
			}

			$this->reindexAuthorOnlyForums( $deleteFirst );
		}
	}

	/**
	 * After change
	 *
	 * @param	array	$moderator	The moderator
	 * @return	void
	 */
	public function onContentDelete( array $moderator ) : void
	{
		$this->reindexAuthorOnlyForums( TRUE );
	}

	/**
	 * Reindex forums where members can only see their own topics
	 *
	 * @param bool $deleteFirst	If TRUE, will delete first
	 * @return	void
	 */
	public function reindexAuthorOnlyForums( bool $deleteFirst=FALSE ) : void
	{
		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'forums_forums', 'can_view_others=0 AND club_id IS NULL' ), 'IPS\forums\Forum' ) as $forum )
		{
			if ( $deleteFirst )
			{
				Index::i()->removeClassFromSearchIndex( 'IPS\forums\Topic', $forum->id );
				Index::i()->removeClassFromSearchIndex( 'IPS\forums\Topic\Post', $forum->id );
			}
			Task::queue( 'core', 'RebuildSearchIndex', array( 'class' => 'IPS\forums\Topic', 'container' => $forum->id ), 5, 'container' );
			Task::queue( 'core', 'RebuildSearchIndex', array( 'class' => 'IPS\forums\Topic\Post', 'container' => $forum->id ), 5, 'container' );
		}
	}
}