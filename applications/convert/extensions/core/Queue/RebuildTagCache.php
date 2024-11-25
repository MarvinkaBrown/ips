<?php
/**
 * @brief		Background Task: Rebuild Tag Cache
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		12 October 2016
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\convert\App;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
use OutOfRangeException;
use function defined;
use function in_array;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RebuildTagCache extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data	Data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];

		Log::debug( "Getting preQueueData for " . $classname, 'rebuildTagCache' );

		try
		{
			$data['count']		= Db::i()->select( 'COUNT(DISTINCT tag_aai_lookup)', 'core_tags', array( 'tag_meta_app=? AND tag_meta_area=?', $classname::$application, $classname::$module ) )->first();
		}
		catch( Exception $ex )
		{
			throw new OutOfRangeException;
		}

		Log::debug( "PreQueue count for " . $classname . " is " . $data['count'], 'rebuildTagCache' );

		if( $data['count'] == 0 )
		{
			return null;
		}

		$data['indexed']	= 0;
		$data['currentId']	= 0;

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int					New offset or NULL if complete
	 * @throws    QueueOutOfRangeException    Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) OR !in_array( 'IPS\Content\Taggable', class_implements( $classname ) ) )
		{
			throw new QueueOutOfRangeException;
		}

		/* Intentionally no try/catch as it means app doesn't exist */
		try
		{
			$app = App::load( $data['app'] );
		}
		catch( OutOfRangeException $e )
		{
			throw new QueueOutOfRangeException;
		}

		Log::debug( "Running " . $classname . ", with an offset of " . $offset, 'rebuildTagCache' );

		$select   = Db::i()->select( '*', 'core_tags', array( 'tag_id>? AND tag_meta_app=? AND tag_meta_area=?', $data['currentId'], $classname::$application, $classname::$module ), 'tag_id ASC', array( 0, REBUILD_QUICK ), NULL, NULL, Db::SELECT_DISTINCT );
		$last     = NULL;

		foreach( $select as $outerTag )
		{
			/* Get all tags with this AAI Key */
			$tags	= array();
			$last	= $outerTag['tag_id'];
			$prefix	= NULL;

			foreach( Db::i()->select( '*', 'core_tags', array( 'tag_aai_lookup=?', $outerTag['tag_aai_lookup'] ) ) as $tag )
			{
				if( $tag['tag_prefix'] )
				{
					$prefix = $tag['tag_text'];
				}
				else
				{
					$tags[] = $tag['tag_text'];
				}

				if( $tag['tag_id'] > $last )
				{
					$last = $tag['tag_id'];
				}
			}

			/* Is this converted content? */
			try
			{
				/* Just checking, we don't actually need anything */
				$app->checkLink( $outerTag['tag_meta_id'], $data['link'] );
			}
			catch( OutOfRangeException $e )
			{
				continue;
			}

			/* Make sure the content item exists */
			try
			{
				$item = $classname::load( $outerTag['tag_meta_id'] );
			}
			catch( OutOfRangeException $e )
			{
				continue;
			}

			Db::i()->insert( 'core_tags_cache', array(
				'tag_cache_key'		=> $outerTag['tag_aai_lookup'],
				'tag_cache_text'	=> json_encode( array( 'tags' => $tags, 'prefix' => $prefix ) ),
				'tag_cache_date'	=> time()
			), TRUE );

			$containerClass = $classname::$containerNodeClass;
			if ( isset( $containerClass::$permissionMap['read'] ) )
			{

				$permissions = $containerClass::load( $item->container()->_id )->permissions();
				
				if ( isset( $containerClass::$permissionMap['read'] ) )
				{
					Db::i()->insert( 'core_tags_perms', array(
						'tag_perm_aai_lookup'		=> $outerTag['tag_aai_lookup'],
						'tag_perm_aap_lookup'		=> $outerTag['tag_aap_lookup'],
						'tag_perm_text'				=> $permissions[ 'perm_' . $containerClass::$permissionMap['read'] ] ?? '',
						'tag_perm_visible'			=> ( $item->hidden() OR ( IPS::classUsesTrait( $item, 'IPS\Content\FuturePublishing' ) AND $item->isFutureDate() ) ) ? 0 : 1,
					), TRUE );
				}
			}

			$data['indexed']++;
		}

		/* Store the runPid for the next iteration of this Queue task. This allows the progress bar to show correctly. */
		$data['currentId'] = $last;

		if( $last === NULL )
		{
			throw new QueueOutOfRangeException;
		}

		/* Return the number rebuilt so far, so that the rebuild progress bar text makes sense */
		return $data['indexed'];
	}

	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		$class = $data['class'];
        $exploded = explode( '\\', $class );
        if ( !class_exists( $class ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new OutOfRangeException;
		}

		return array( 'text' => Member::loggedIn()->language()->addToStack( 'queue_rebuilding_tag_cache', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title . '_pl', FALSE, array( 'strtolower' => TRUE ) ) ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['indexed'], 2 ) ) : 100 );
	}
}