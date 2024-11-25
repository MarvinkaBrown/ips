<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		01 Dec 2017
 */

namespace IPS\downloads\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\File;
use IPS\Image;
use IPS\Member;
use IPS\Settings;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_INTENSE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RebuildScreenshotWatermarks extends QueueAbstract
{
	/**
	 * @brief Number of items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_INTENSE;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		try
		{
			$watermark = Settings::i()->idm_watermarkpath ? File::get( 'core_Theme', Settings::i()->idm_watermarkpath )->contents() : NULL;
			$where = array( array( 'record_type=?', 'ssupload' ) );
			if ( !$watermark )
			{
				$where[] = array( 'record_no_watermark<>?', '' );
			}

			$data['count']		= Db::i()->select( 'MAX(record_id)', 'downloads_files_records', $where )->first();
			$data['realCount']	= Db::i()->select( 'COUNT(*)', 'downloads_files_records', $where )->first();
		}
		catch( Exception $ex )
		{
			return NULL;
		}

		if( $data['count'] == 0 or $data['realCount'] == 0 )
		{
			return NULL;
		}

		$data['indexed']	= 0;

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		$last = NULL;
		$watermark = Settings::i()->idm_watermarkpath ? Image::create( File::get( 'core_Theme', Settings::i()->idm_watermarkpath )->contents() ) : NULL;

		$where = array( array( 'record_id>? AND record_type=?', $offset, 'ssupload' ) );
		if ( !$watermark )
		{
			$where[] = array( 'record_no_watermark<>?', '' );
		}

		$select = Db::i()->select( '*', 'downloads_files_records', $where, 'record_id', array( 0, $this->rebuild ) );

		foreach ( $select as $row )
		{
			try
			{
				if ( $row['record_no_watermark'] )
				{
					$original = File::get( 'downloads_Screenshots', $row['record_no_watermark'] );

					try
					{
						File::get( 'downloads_Screenshots', $row['record_location'] )->delete();
						File::get( 'downloads_Screenshots', $row['record_thumb'] )->delete();
					}
					catch ( Exception $e ) { }

					if ( !$watermark )
					{
						Db::i()->update( 'downloads_files_records', array(
							'record_location'		=> (string) $original,
							'record_thumb'			=> (string) $original->thumbnail( 'downloads_Screenshots' ),
							'record_no_watermark'	=> NULL
						), array( 'record_id=?', $row['record_id'] ) );

						$data['indexed']++;
						$last = $row['record_id'];

						continue;
					}
				}
				else
				{
					$original = File::get( 'downloads_Screenshots', $row['record_location'] );
				}

				$image = Image::create( $original->contents() );
				$image->watermark( $watermark );

				$newFile = File::create( 'downloads_Screenshots', $original->originalFilename, $image );

				Db::i()->update( 'downloads_files_records', array(
					'record_location'		=> (string) $newFile,
					'record_thumb'			=> (string) $newFile->thumbnail( 'downloads_Screenshots' ),
					'record_no_watermark'	=> (string) $original
				), array( 'record_id=?', $row['record_id'] ) );
			}
			catch ( Exception $e ) { }

			$data['indexed']++;
			$last = $row['record_id'];
		}

		if( $last === NULL )
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
		return array( 'text' => Member::loggedIn()->language()->addToStack('downloads_rebuilding_screenshots'), 'complete' => ( $data['realCount'] * $data['indexed'] ) > 0 ? round( ( $data['realCount'] * $data['indexed'] ) * 100, 2 ) : 0 );
	}
}