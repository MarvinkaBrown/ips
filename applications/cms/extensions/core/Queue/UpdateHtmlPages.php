<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		18 Dec 2023
 */

namespace IPS\cms\extensions\core\Queue;

use IPS\cms\Pages\Page;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Lang;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
use IPS\Widget\Area;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_SLOW;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class UpdateHtmlPages extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = (int) Db::i()->select( 'count(page_id)', 'cms_pages' )->first();
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	array						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	QueueOutOfRangeException	        Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ): int
	{
		$limit = REBUILD_SLOW;
		$rows = iterator_to_array(
			new ActiveRecordIterator(
				Db::i()->select( '*', 'cms_pages', null, 'page_id', array( $offset, $limit ) ),
				Page::class
			)
		);

		$lang = Lang::load( Lang::defaultLanguage() );
		foreach( $rows as $row )
		{
			/* Builder pages need minor adjustment */
			if( $row->type == 'builder' )
			{
				$row->ipb_wrapper = true;
			}
			else
			{
				/* Convert the page to a builder page with a single column */
				$area = Area::create( 'col1', [
					[
						'title' => $lang->addToStack( 'block_Codemirror' ),
						'config' => true,
						'blank' => false,
						'app' => 'cms',
						'key' => 'Codemirror',
						'unique' => mt_rand(),
						'configuration' => [
							'content' => $row->content
						]
					]
				], [ 'wrapBehavior' => 'stack' ] );
				$row->saveArea( $area );

				$row->type = 'builder';
				$row->content = null;

				if( $row->wrapper_template === null or $row->wrapper_template == '__none__')
				{
					$row->ipb_wrapper = true;
				}
			}

			if( $row->template == 'page_builder__single_column__page_page_builder_single_column' or $row->template == 'page_builder__single_column__page_builder_single_column' )
			{
				$row->template = null;
			}

			$row->save();
		}

		$offset += $limit;
		if( $offset >= $data['count'] )
		{
			throw new QueueOutOfRangeException;
		}

		return $offset;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	array					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( array $data, int $offset ): array
	{
		return array(
			'text' => 'Converting HTML Pages to Builder',
			'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100
		);
	}
}