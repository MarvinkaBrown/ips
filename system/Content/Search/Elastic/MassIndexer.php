<?php
/**
 * @brief		Elasticsearch Mass Indexer
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Nov 2017
*/

namespace IPS\Content\Search\Elastic;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Http\Url;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Elasticsearch Mass Indexer
 */
class MassIndexer extends Index
{
	/**
	 * @brief	Data to store in bulk
	 */
	protected ?array $data = array();
	
	/**
	 * Index an item
	 *
	 * @param	Content	$object	Item to add
	 * @return	void
	 */
	public function index( Content $object ): void
	{
		if ( $indexData = $this->indexData( $object ) and $indexData['index_permissions'] )
		{
			if ( $object instanceof Item )
			{
				$parent = $object;
			}
			elseif ( $object instanceof Comment )
			{
				$parent = $object->item();
			}
			
			$this->data[] = array(
				'index'		=> array(
					'_index'	=> trim( $this->url->data[ Url::COMPONENT_PATH ], '/' ),
					'_type'		=> '_doc',
					'_id'		=> $this->getIndexId( $object ),
				)
			);
			$this->data[] = $indexData;
		}
	}
	
	/**
	 * Commit at the end of the request
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if ( $this->data )
		{
			$json = array();
			foreach ( $this->data as $data )
			{
				$json[] = json_encode( $data );
			}
			$json = implode( "\n", $json );
			
			Index::request( $this->url->setPath( '/_bulk' ) )->setHeaders( array( 'Content-Type' => 'application/x-ndjson' ) )->post( $json . "\n" );
		}
	}
}