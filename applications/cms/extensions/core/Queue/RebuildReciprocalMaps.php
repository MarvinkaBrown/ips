<?php
/**
 * @brief		Background Task: Rebuild database reciprocal maps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		31 May 2016
 */

namespace IPS\cms\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Databases;
use IPS\cms\Fields;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild database reciprocal maps
 */
class RebuildReciprocalMaps extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_QUICK;
	
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$databaseId = $data['database'];
		$fieldId    = $data['field'];

		try
		{
			$data['count'] = (int) Db::i()->select( 'COUNT(*)', 'cms_custom_database_' . $databaseId, array( 'field_' . $fieldId . ' != \'\' or field_' . $fieldId . ' IS NOT NULL' ) )->first();
		}
		catch( Exception $ex )
		{
			throw new OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return null;
		}
		
		$data['completed'] = 0;

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param mixed $data Data as it was passed to \IPS\Task::queue()
	 * @param int $offset Offset
	 * @return int New offset or NULL if complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		$databaseId	= $data['database'];
		$fieldId	= $data['field'];
		$last		= NULL;
		
		if ( Db::i()->checkForTable( 'cms_custom_database_' . $databaseId ) )
		{
			/* @var Fields $fieldsClass */
			$fieldsClass = 'IPS\cms\Fields' . $databaseId;
			$field = $fieldsClass::load( $fieldId );
		
			foreach ( Db::i()->select( '*', 'cms_custom_database_' . $databaseId, array( 'primary_id_field > ? AND (field_' . $fieldId . ' != \'\' or field_' . $fieldId . ' IS NOT NULL)', $offset ), 'primary_id_field asc', array( 0, $this->rebuild ) ) as $row )
			{
				$extra = $field->extra;
				if ( $row[ 'field_' . $fieldId ] and ! empty( $extra['database'] ) )
				{
					foreach( explode( ',', $row[ 'field_' . $fieldId ] ) as $foreignId )
					{
						if ( $foreignId )
						{
							Db::i()->insert( 'cms_database_fields_reciprocal_map', array(
								'map_origin_database_id'	=> $databaseId,
								'map_foreign_database_id'   => $extra['database'],
								'map_origin_item_id'		=> $row['primary_id_field'],
								'map_foreign_item_id'		=> $foreignId,
								'map_field_id'				=> $fieldId
							) );
						}
					}
				}
				
				$data['completed']++;
				$last = $row['primary_id_field'];
			}
		}
		
		if ( $last === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return $last;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		$databaseId = $data['database'];
		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_cms_database_reciprocal_map', FALSE, array( 'sprintf' => array( Databases::load( $databaseId )->_title, $data['field'] ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['completed'], 2 ) ) : 100 );
	}	
}