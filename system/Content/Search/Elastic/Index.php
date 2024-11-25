<?php
/**
 * @brief		Elasticsearch Search Index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		31 Oct 2017
*/

namespace IPS\Content\Search\Elastic;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Search\Index as SearchIndex;
use IPS\Content\Search\Elastic\Exception as ElasticException;
use IPS\Content;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Request\Exception as RequestException;
use IPS\Http\Request\Curl;
use IPS\Http\Response;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Settings;
use OutOfRangeException;
use RuntimeException;
use StdClass;
use function count;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_null;
use function strtolower;
use function substr;
use const IPS\ELASTICSEARCH_PASSWORD;
use const IPS\ELASTICSEARCH_USER;
use const IPS\LONG_REQUEST_TIMEOUT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Elasticsearch Search Index
 */
class Index extends SearchIndex
{
	/**
	 * @brief	Elasticsearch version requirements
	 */
	const MINIMUM_VERSION = '7.2.0';
	const UNSUPPORTED_VERSION = '8.0.0';

	/**
	 * @brief	The server URL
	 */
	protected Url $url;
	
	/**
	 * Constructor
	 *
	 * @param	Url	$url	The server URL
	 * @return	void
	 */
	public function __construct( Url $url )
	{
		$this->url = $url;
	}
	
	/**
	 * Initalize when first setting up
	 *
	 * @return	void
	 */
	public function init(): void
	{
		try
		{
			$analyzer = Settings::i()->search_elastic_analyzer;
			$settings = array(
				'max_result_window'	=> Settings::i()->search_index_maxresults
			);
			if ( $analyzer === 'custom' )
			{
				$settings['analysis'] = json_decode( '{' . Settings::i()->search_elastic_custom_analyzer . '}', TRUE );
				$analyzer = key( $settings['analysis']['analyzer'] );
			}

			Index::request( $this->url )->delete();

			$definition = array(
				'settings'	=> $settings,
				'mappings'	=> array(
					'_doc' 	=> array(
						'properties'	=> array(
							'index_class'				=> array( 'type' => 'keyword' ),
							'index_object_id'			=> array( 'type' => 'long' ),
							'index_item_id'				=> array( 'type' => 'long' ),
							'index_container_class'		=> array( 'type' => 'keyword' ),
							'index_container_id'		=> array( 'type' => 'long' ),
							'index_title'				=> array(
								'type' 		=> 'text',
								'analyzer'	=> $analyzer,
							),
							'index_content'				=> array(
								'type' 		=> 'text',
								'analyzer'	=> $analyzer,
							),
							'index_permissions'			=> array( 'type' => 'keyword' ),
							'index_date_created'		=> array(
								'type' 		=> 'date',
								'format'	=> 'epoch_second',
							),
							'index_date_updated'		=> array(
								'type' 		=> 'date',
								'format'	=> 'epoch_second',
							),
							'index_date_commented'		=> array(
								'type' 		=> 'date',
								'format'	=> 'epoch_second',
							),
							'index_author'				=> array( 'type' => 'long' ),
							'index_tags'				=> array( 'type' => 'keyword' ),
							'index_prefix'				=> array( 'type' => 'keyword' ),
							'index_hidden'				=> array( 'type' => 'byte' ),
							'index_item_index_id'		=> array( 'type' => 'keyword' ),
							'index_item_author'			=> array( 'type' => 'long' ),
							'index_is_last_comment'		=> array( 'type' => 'boolean' ),
							'index_club_id'				=> array( 'type' => 'long' ),
							'index_class_type_id_hash'	=> array( 'type' => 'keyword' ),
							'index_comments'			=> array( 'type' => 'long' ),
							'index_reviews'				=> array( 'type' => 'long' ),
							'index_participants'		=> array( 'type' => 'long' ),
							'index_is_anon'				=> array( 'type' => 'byte' ),
							'index_item_solved'			=> array( 'type' => 'byte' )
						)
					)
				)
			);

			try
			{
				$response = Index::request( $this->url->setQueryString( 'include_type_name', 'true' ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->put( json_encode( $definition ) );

				if( $response->httpResponseCode != 200 )
				{
					throw new RuntimeException;
				}
			}
			catch( Exception )
			{
				$response = Index::request( $this->url )->setHeaders( array( 'Content-Type' => 'application/json' ) )->put( json_encode( $definition ) );
			}
		}
		catch ( Exception $e )
		{
			Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Get index data
	 *
	 * @param	Content	$object	Item to add
	 * @return	array|NULL
	 */
	public function indexData( Content $object ): array|NULL
	{
		if ( $indexData = parent::indexData( $object ) )
		{
			$indexData['index_permissions'] = explode( ',', $indexData['index_permissions'] );
			$indexData['index_is_last_comment'] = (bool) $indexData['index_is_last_comment'];
			
			if ( $object instanceof Item )
			{
				$indexData = array_merge( $indexData, $this->metaData( $object ) );
			}
			else
			{
				$indexData = array_merge( $indexData, $this->metaData( $object->item() ) );
			}

			return $indexData;
		}
		
		return NULL;
	}
			
	/**
	 * Index an item
	 *
	 * @param	Content	$object	Item to add
	 * @return	void
	 */
	public function index( Content $object ): void
	{
		if ( $indexData = $this->indexData( $object ) )
		{
			/* If nobody has permission to access it, just remove it */
			if ( !$indexData['index_permissions'] )
			{
				$this->removeFromSearchIndex( $object );
			}
			/* Otherwise, go ahead... */
			else
			{
				try
				{
					$existingData		= NULL;
					$existingIndexId	= NULL;
					$resetLastComment	= FALSE;
					$firstCommentId		= $this->getFirstCommentId( $object );
					
					try
					{
						$r = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/' . $this->getIndexId( $object ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->get()->decodeJson();
						if ( $r['found'] )
						{
							$existingData = $r['_source'];
							$existingIndexId = $r['_id'];
						}
					}
					catch( Exception ) { }
					
					if ( $object instanceof Comment and $existingIndexId and $existingData['index_is_last_comment'] and $indexData['index_is_last_comment'] and $indexData['index_item_id'] and $indexData['index_hidden'] !== 0 )
					{
						/* We do not allow hidden or needing approval comments to become flagged as the last comment as this means users without hidden view permission never see the item in an item only stream */
						$indexData['index_is_last_comment'] = false;
						
						$resetLastComment = TRUE;
					}
					else if ( $indexData['index_is_last_comment'] and $indexData['index_item_id'] )
					{
						/* We have a new "last comment" */
						$resetLastComment = TRUE;
					}
															
					/* Insert into index */
					$r = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/' . $this->getIndexId( $object ) ), LONG_REQUEST_TIMEOUT )
						->setHeaders( array( 'Content-Type' => 'application/json' ) )
						->put( json_encode( $indexData ) );

					if( $error = $this->getResponseError( $r ) )
					{
						throw new \IPS\Content\Search\Elastic\Exception( $error['type'] . ': ' . $error['reason'] );
					}

					if ( $resetLastComment )
					{
						$this->resetLastComment( array( $indexData['index_class'] ), $indexData['index_item_id'], null, $firstCommentId );
					}

					/* Views / Comments / Reviews */
					if ( $object instanceof Item )
					{
						$item = $object;
					}
					elseif ( $object instanceof Comment )
					{
						$item = $object->item();
					}

					$this->rebuildMetaData( $item );
				}
				catch ( RequestException $e )
				{
					Log::log( $e, 'elasticsearch' );
				}
				catch ( ElasticException $e )
				{
					Log::log( $e, 'elasticsearch_response_error' );
				}
			}
		}
	}
	
	/**
	 * Clear out any tasks associated with the search index method
	 *
	 * @return void
	 */
	public function clearTasks(): void
	{
		try
		{
			/* This request *intentionally* goes to _tasks and not (ourpath)/_tasks */
			$response = Index::request( $this->url->setPath( '/_tasks' ), LONG_REQUEST_TIMEOUT )->setHeaders( array( 'Content-Type' => 'application/json' ) )->get();

			if( $error = $this->getResponseError( $response ) )
			{
				throw new ElasticException( $error['type'] . ' ' . $error['type'] );
			}

			$response = $response->decodeJson();

			foreach( $response['nodes'] as $nodeId => $nodeData )
			{
				foreach( $nodeData['tasks'] as $taskId => $taskData )
				{
					/* We only need to worry about deleting parent tasks */
					if( isset( $taskData['parent_task_id'] ) )
					{
						continue;
					}

					/* If the task is cancellable it isn't finished yet */
					if( $taskData['cancellable'] === TRUE )
					{
						continue;
					}

					Index::request( $this->url->setPath( '/.tasks/task' . $taskId ), LONG_REQUEST_TIMEOUT )->setHeaders( array( 'Content-Type' => 'application/json' ) )->delete();
				}
			}
		}
		catch ( ElasticException $e )
		{
			Log::log( $e, 'elasticsearch_response_error' );
		}
		catch( Exception $e )
		{
			Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Get the comment / review counts for an item
	 *
	 * @param	Item	$item					The content item
	 * @return	array|null
	 */
	protected function metaData( Item $item ): ?array
	{
		if( !Content\Search\SearchContent::isSearchable( $item ) )
		{
			return null;
		}
		
		$databaseColumnId = $item::$databaseColumnId;

		/* @var array $databaseColumnMap */
		$participants = array( $item->mapped('author') );
		if ( isset( $item::$commentClass ) )
		{
			/* @var Comment $commentClass */
			$commentClass = $item::$commentClass;
			$participants += iterator_to_array( Db::i()->select( 'DISTINCT ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'], $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $item->$databaseColumnId ) ) );
		}
		if ( isset( $item::$reviewClass ) )
		{
			/* @var Content\Review $reviewClass */
			$reviewClass = $item::$reviewClass;
			$participants += iterator_to_array( Db::i()->select( 'DISTINCT ' . $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['author'], $reviewClass::$databaseTable, array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $item->$databaseColumnId ) ) );
		}
		$participants = array_values( array_unique( $participants ) );

		$isSolved = 0;
		if ( IPS::classUsesTrait( $item, 'IPS\Content\Solvable' ) and $item->isSolved() )
		{
			$isSolved = 1;
		}

		return array(
			'index_comments'		=> $item->mapped('num_comments'),
			'index_reviews'			=> $item->mapped('num_reviews'),
			'index_participants'	=> $participants,
			'index_tags'			=> $item->tags(),
			'index_prefix'			=> $item->prefix(),
			'index_item_solved'     => $isSolved
		);
	}
	
	/**
	 * Rebuild the comment / review counts for an item
	 *
	 * @param	Item	$item					The content item
	 * @return	void
	 */
	protected function rebuildMetaData( Item $item ): void
	{
		$databaseColumnId = $item::$databaseColumnId;
		$class = get_class( $item );
		$classes = array( $class );
		if ( isset( $class::$commentClass ) )
		{
			$classes[] = $class::$commentClass;
		}
		if ( isset( $class::$reviewClass ) )
		{
			$classes[] = $class::$reviewClass;
		}
		
		try
		{			
			$updates	= array();
			$params		= array();
			foreach ( $this->metaData( $item ) as $k => $v )
			{
				$updates[]	= "ctx._source.{$k} = params.param_{$k};";
				if ( is_array( $v ) )
				{
					$params[ 'param_' . $k ]	= ( $k !== 'index_tags' ? array_map( 'intval', $v ) : $v );
				}
				elseif ( is_null( $v ) )
				{
					$params['param_' . $k ]		= null;
				}
				else
				{
					$params['param_' . $k ]		= intval( $v );
				}
			}

			$r = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_update_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false', 'scroll_size' => Settings::i()->search_index_maxresults ) ), LONG_REQUEST_TIMEOUT )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
				'script'	=> array(
					'source'	=> implode( ' ', $updates ),
					'lang'		=> 'painless',
					'params'	=> $params
				),
				'query'		=> array(
					'bool'		=> array(
						'must'		=> array(
							array(
								'terms'	=> array(
									'index_class' => $classes
								)
							),
							array(
								'term'	=> array(
									'index_item_id' => $item->$databaseColumnId
								)
							),
						)
					)
				)
			) ) );
		}
		catch ( RequestException $e )
		{
			Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Retrieve the search ID for an item
	 *
	 * @param	Content	$object	Item to add
	 * @return	mixed
	 */
	public function getIndexId( Content $object ): mixed
	{
		$databaseColumnId = $object::$databaseColumnId;
		return strtolower( str_replace( '\\', '_', substr( get_class( $object ), 4 ) ) ) . '-' . $object->$databaseColumnId;
	}
	
	/**
	 * Remove item
	 *
	 * @param	Content	$object	Item to remove
	 * @return	string
	 */
	public function removeFromSearchIndex( Content $object ): void
	{
		try
		{
			$class = get_class( $object );
			$idColumn = $class::$databaseColumnId;

			$this->directIndexRemoval( $class, $object->$idColumn );

			if ( !( $object instanceof Item ) )
			{
				$this->rebuildMetaData( $object->item() );

				/* @var Item $itemClass */
				$itemClass = get_class( $object->item() );
				$itemIdColumn = $itemClass::$databaseColumnId;

				$classes = array( $itemClass );
				if ( isset( $itemClass::$commentClass ) )
				{
					$classes[] = $itemClass::$commentClass;
				}
				if ( isset( $itemClass::$reviewClass ) )
				{
					$classes[] = $itemClass::$reviewClass;
				}

				$this->resetLastComment( $classes, $object->item()->$itemIdColumn, $object->$idColumn, null, $this->getFirstCommentId( $object ) );
			}		
		}
		catch ( RequestException $e )
		{
			Log::log( $e, 'elasticsearch' );
		}
	}

	/**
	 * Direct removal from the search index - only used when we don't need to perform ancillary cleanup (i.e. orphaned data)
	 *
	 * @param	string	$class	Class
	 * @param	int		$id		ID
	 * @return	void
	 */
	public function directIndexRemoval( string $class, int $id ): void
	{
		try
		{
			$indexId = strtolower( str_replace( '\\', '_', substr( $class, 4 ) ) ) . '-' . $id;
			Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/' . $indexId ) )->delete();
			
			if ( is_subclass_of( $class, 'IPS\Content\Item' ) )
			{				
				if ( isset( $class::$commentClass ) )
				{
					$commentClass = $class::$commentClass;
					$response = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_delete_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false' ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
						'query'	=> array(
							'bool' => array(
								'must' => array(
									array(
										'term'	=> array(
											'index_class' => $commentClass
										)
									),
									array(
										'term'	=> array(
											'index_item_id' => $id
										)
									),
								)
							)
									
						)
					) ) );
				}
				if ( isset( $class::$reviewClass ) )
				{
					$reviewClass = $class::$reviewClass;
					Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_delete_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false' ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
						'query'	=> array(
							'bool' => array(
								'must' => array(
									array(
										'term'	=> array(
											'index_class' => $reviewClass
										)
									),
									array(
										'term'	=> array(
											'index_item_id' => $id
										)
									),
								)
							)
									
						)
					) ) );
				}
			}	
		}
		catch ( RequestException $e )
		{
			Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Removes all content for a classs
	 *
	 * @param	string		$class 	The class
	 * @param	int|NULL	$containerId		The container ID to delete, or NULL
	 * @param	int|NULL	$authorId			The author ID to delete, or NULL
	 * @return	void
	 */
	public function removeClassFromSearchIndex( string $class, int|null $containerId=null, int|null $authorId=null ): void
	{
		try
		{
			if ( $containerId or $authorId )
			{
				$query = array(
					'bool'	=> array(
						'must'	=> array(
							array(
								'term'	=> array(
									'index_class' => $class
								)
							)
						)
					)
				);
				
				if ( $containerId )
				{
					$query['bool']['must'][] = array(
						'term'	=> array(
							'index_container_id' => $containerId
						)
					);
				}
				
				if ( $authorId )
				{
					$query['bool']['must'][] = array(
						'term'	=> array(
							'index_author' => $authorId
						)
					);
				}

				Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_delete_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false' ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'query'	=> $query
				) ) );
			}
			else
			{
				Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_delete_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false' ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'query'	=> array(
						'term'	=> array(
							'index_class' => $class
						)
					)
				) ) );
			}
		}
		catch ( RequestException $e )
		{
			Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Mass Update (when permissions change, for example)
	 *
	 * @param	string				$class 						The class
	 * @param	int|NULL			$containerId				The container ID to update, or NULL
	 * @param	int|NULL			$itemId						The item ID to update, or NULL
	 * @param	string|NULL			$newPermissions				New permissions (if applicable)
	 * @param	int|NULL			$newHiddenStatus			New hidden status (if applicable) special value 2 can be used to indicate hidden only by parent
	 * @param	int|NULL			$newContainer				New container ID (if applicable)
	 * @param	int|NULL			$authorId					The author ID to update, or NULL
	 * @param	int|NULL			$newItemId					The new item ID (if applicable)
	 * @param	int|NULL			$newItemAuthorId			The new item author ID (if applicable)
	 * @param	bool				$addAuthorToPermissions		If true, the index_author_id will be added to $newPermissions - used when changing the permissions for a node which allows access only to author's items
	 * @return	void
	 */
	public function massUpdate( string $class, int|null $containerId = null, int|null $itemId = null, string|null $newPermissions = null, int|null $newHiddenStatus = null, int|null $newContainer = null, int|null $authorId = null, int|null $newItemId = null, int|null $newItemAuthorId = null, bool $addAuthorToPermissions = FALSE ): void
	{
		try
		{
			$conditions = array();
			$conditions['must'][] = array(
				'term'	=> array(
					'index_class' => $class
				)
			);
			if ( $containerId !== NULL )
			{
				$conditions['must'][] = array(
					'term'	=> array(
						'index_container_id' => $containerId
					)
				);
			}
			if ( $itemId !== NULL )
			{
				$conditions['must'][] = array(
					'term'	=> array(
						'index_item_id' => $itemId
					)
				);
			}
			if ( $authorId !== NULL )
			{
				$conditions['must'][] = array(
					'term'	=> array(
						'index_item_author' => $authorId
					)
				);
			}
			
			$updates	= array();
			$params		= array();
			if ( $newPermissions !== NULL )
			{
				$updates[] = "ctx._source.index_permissions = params.params_indexpermissions;";
				$params['params_indexpermissions']	= explode( ',', $newPermissions );
			}
			if ( $newContainer )
			{
				$updates[] = "ctx._source.index_container_id = params.params_indexcontainer;";
				$params['params_indexcontainer']	= $newContainer;

				/* @var Item $itemClass */
				if ( $itemClass = ( in_array( 'IPS\Content\Item', class_parents( $class ) ) ? $class : $class::$itemClass ) and $containerClass = $itemClass::$containerNodeClass and IPS::classUsesTrait( $containerClass, 'IPS\Content\ClubContainer' ) and $clubIdColumn = $containerClass::clubIdColumn() )
				{
					try
					{
						$updates[] = "ctx._source.index_club_id = params.params_indexclub;";
						$params['params_indexclub']	= intval( $containerClass::load( $newContainer )->$clubIdColumn );
					}
					catch ( OutOfRangeException )
					{
						$updates[] = "ctx._source.index_club_id = params.params_indexclub;";
						$params['params_indexclub']	= null;
					}
				}
			}
			if ( $newItemId )
			{
				$updates[] = "ctx._source.index_item_id = params.params_indexitem;";
				$params['params_indexitem']	= $newItemId;
			}
			if ( $newItemAuthorId )
			{
				$updates[] = "ctx._source.index_item_author = params.params_indexauthor;";
				$params['params_indexauthor']	= $newItemAuthorId;
			}
			
			if ( count( $updates ) )
			{
				Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_update_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false', 'scroll_size' => Settings::i()->search_index_maxresults ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'script'	=> array(
						'source'	=> implode( ' ', $updates ),
						'lang'		=> 'painless',
						'params'	=> $params
					),
					'query'		=> array(
						'bool'		=> $conditions
					)
				) ) );
			}
			
			if ( $addAuthorToPermissions )
			{
				$addAuthorToPermissionsConditions = $conditions;
				$addAuthorToPermissionsConditions['must_not'][] = array(
					'term'	=> array(
						'index_author' => 0
					)
				);

				Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_update_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false', 'scroll_size' => Settings::i()->search_index_maxresults ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'script'	=> array(
						'source'	=> "ctx._source.index_permissions.add( 'm' + ctx._source.index_author );",
						'lang'		=> 'painless'
					),
					'query'		=> array(
						'bool'		=> $conditions
					)
				) ) );
			}
			
			if ( $newHiddenStatus !== NULL )
			{
				if ( $newHiddenStatus === 2 )
				{
					$conditions['must'][] = array(
						'term'	=> array(
							'index_hidden' => 0
						)
					);
				}
				else
				{
					$conditions['must'][] = array(
						'term'	=> array(
							'index_hidden' => 2
						)
					);
				}

				Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_update_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false', 'scroll_size' => Settings::i()->search_index_maxresults ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'script'	=> array(
						'source'	=> "ctx._source.index_hidden = params.newHiddenStatus;",
						'lang'		=> 'painless',
						'params'	=> array( 'newHiddenStatus' => $newHiddenStatus )
					),
					'query'		=> array(
						'bool'		=> $conditions
					)
				) ) );
			}
		}
		catch ( RequestException $e )
		{
			Log::log( $e, 'elasticsearch' );
		}		
	}
	
	/**
	 * Convert an arbitary number of elasticsearch conditions into a query
	 *
	 * @param	array	$conditions	Conditions
	 * @return	array
	 */
	public static function convertConditionsToQuery( array $conditions ): array
	{
		if ( count( $conditions ) == 1 )
		{
			return $conditions[0];
		}
		elseif ( count( $conditions ) == 0 )
		{
			return array( 'match_all' => new StdClass );
		}
		else
		{
			return array(
				'bool' => array(
					'must' => $conditions
				)
			);
		}
	}
	
	/**
	 * Update data for the first and last comment after a merge
	 * Sets index_is_last_comment on the last comment, and, if this is an item where the first comment is indexed rather than the item, sets index_title and index_tags on the first comment
	 *
	 * @param	Item	$item	The item
	 * @return	void
	 */
	public function rebuildAfterMerge( Item $item ): void
	{
		if ( $item::$commentClass )
		{
			$firstComment = $item->comments( 1, 0, 'date', 'asc', NULL, FALSE, NULL, NULL, TRUE, FALSE, FALSE );
			$lastComment = $item->comments( 1, 0, 'date', 'desc', NULL, FALSE, NULL, NULL, TRUE, FALSE, FALSE );
			
			$update = array( 'index_is_last_comment' => false );
			if ( $item::$firstCommentRequired )
			{
				$update['index_title'] = NULL;
			}
			
			try
			{
				Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/' . $this->getIndexId( $item ) . '/_update' ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'doc'	=> $update
				) ) );
				
				if ( $firstComment )
				{
					$this->index( $firstComment );
				}
				if ( $lastComment )
				{
					$this->index( $lastComment );
				}
			}
			catch ( RequestException $e )
			{
				Log::log( $e, 'elasticsearch' );
			}			
		}
	}
	
	/**
	 * Prune search index
	 *
	 * @param	DateTime|NULL	$cutoff	The date to delete index records from, or NULL to delete all
	 * @return	void
	 */
	public function prune( DateTime|null $cutoff = NULL ): void
	{
		if ( $cutoff )
		{			
			try
			{
				Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_delete_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false' ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'query'	=> array(
						'range'	=> array(
							'index_date_updated' => array(
								'lt' => $cutoff->getTimestamp()
							)
						)
					)
				) ) );
			}
			catch ( RequestException $e )
			{
				Log::log( $e, 'elasticsearch' );
			}
		}
		else
		{
			$this->init();
		}		
	}
	
	/**
	 * Reset the last comment flag in any given class/index_item_id
	 *
	 * @param array $classes The classes (when first post is required, this is typically just \IPS\forums\Topic\Post but for others, it will be both item and comment classes)
	 * @param int|NULL $indexItemId The index item ID
	 * @param int|NULL $ignoreId ID to ignore because it is being removed
	 * @param int|null $firstCommentId The first comment in this item, used only if $firstCommentRequired
	 * @return 	void
	 */
	public function resetLastComment( array $classes, int|null $indexItemId, int|null $ignoreId = null, int|null $firstCommentId = null ): void
	{
		try
		{			
			/* Remove the flag */
			$r = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_update_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false' ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
				'script'	=> array(
					'source'	=> "ctx._source.index_is_last_comment = false;",
					'lang'		=> 'painless'
				),
				'query'		=> array(
					'bool'		=> array(
						'must'		=> array(
							array(
								'terms'	=> array(
									'index_class' => $classes
								)
							),
							array(
								'term'	=> array(
									'index_item_id' => $indexItemId
								)
							),
							array(
								'term'	=> array(
									'index_is_last_comment' => true
								)
							)
						)
					)
				)
			) ) );
			
			/* Get the latest comment */
			$itemClass = NULL;
			foreach ( $classes as $class )
			{
				if ( in_array( 'IPS\Content\Item', class_parents( $class ) ) )
				{
					$itemClass = $class;
					break;
				}
				elseif ( isset( $class::$itemClass ) )
				{
					$itemClass = $class::$itemClass;
				}
			}
			if ( $itemClass )
			{
				try
				{
					$item = $itemClass::load( $indexItemId );
					
					$where = NULL;
					if( $ignoreId !== NULL AND isset( $itemClass::$commentClass ) )
					{
						$commentClass = $itemClass::$commentClass;
						$commentIdColumn = $commentClass::$databaseColumnId;

						$where = array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentIdColumn . '<>?', $ignoreId );
					}

					if ( $lastComment = $item->comments( 1, 0, 'date', 'desc', NULL, FALSE, NULL, $where ) AND Content\Search\SearchContent::isSearchable( $lastComment ) )
					{
						/* Set that it is the latest comment */
						$r = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_update/' . $this->getIndexId( $lastComment ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
							'doc'	=> array(
								'index_is_last_comment' => true
							)
						) ) );

						/* And set the updated time on the main item (done as _update_by_query because it might not exist if the first comment is required) */
						$indexDataForLastComment = $this->indexData( $lastComment );
						$r = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_update_by_query' )->setQueryString( array( 'conflicts' => 'proceed', 'wait_for_completion' => 'false' ) ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
							'script'	=> array(
								'source'	=> "ctx._source.index_date_updated = params.dateUpdated; ctx._source.index_date_commented = params.dateCommented;",
								'lang'		=> 'painless',
								'params'	=> array(
									'dateUpdated'	=> intval( $indexDataForLastComment['index_date_updated'] ),
									'dateCommented'	=> intval( $indexDataForLastComment['index_date_commented'] )
								)
							),
							'query'		=> array(
								'bool'		=> array(
									'must'		=> array(
										array(
											'terms'	=> array(
												'index_class' => $classes
											)
										),
										array(
											'term'	=> array(
												'index_item_id' => $indexItemId
											)
										),
										array(
											'term'	=> array(
												'index_object_id' => ( $firstCommentId ?? $indexItemId )
											)
										),
									)
								)
							)
						) ) );
					}
				}
				catch ( OutOfRangeException ) {}
			}
		}
		catch ( Exception $e )
		{
			Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Given a list of item index IDs, return the ones that a given member has participated in
	 *
	 * @param	array		$itemIndexIds	Item index IDs
	 * @param	Member	$member			The member
	 * @return 	array
	 */
	public function iPostedIn( array $itemIndexIds, Member $member ): array
	{
		try
		{
			/* Set the query */
			$query = array(
				'bool'	=> array(
					'filter' => array(
						array(
							'terms'	=> array(
								'index_item_index_id' => $itemIndexIds
							),
						),
						array(
							'term'	=> array(
								'index_author' => $member->member_id
							)
						)
					)
				)
			);
			
			/* Get the count */
			$count = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_search' ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'size'	=> 0,
				'query'	=> $query
			) ) )->decodeJson();
			$total = $count['hits']['total']['value'] ?? $count['hits']['total'];
			if ( !$total )
			{
				return array();
			} 

			/* Now get the unique item ids */
			$results = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_search' ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'aggs'	=> array(
					'itemIds' => array(
						'terms'	=> array(
							'field'	=> 'index_item_index_id',
							'size'	=> $total
						)
					)
				),
				'query'	=> $query
			) ) )->decodeJson();
		}
		catch ( Exception $e )
		{
			Log::log( $e, 'elasticsearch' );
			return array();
		}
		
		$iPostedIn = array();
		foreach ( $results['aggregations']['itemIds']['buckets'] as $result )
		{
			if ( $result['doc_count'] )
			{
				$iPostedIn[] = $result['key'];
			}
		}
		
		return $iPostedIn;
	}
	
	/**
	 * Given a list of "index_class_type_id_hash"s, return the ones that a given member has permission to view
	 *
	 * @param	array		$hashes			Item index hashes
	 * @param	Member	$member			The member
	 * @param	int|NULL		$limit			Number of results to return
	 * @return 	array
	 */
	public function hashesWithPermission( array $hashes, Member $member, int|null $limit = null ): array
	{
		try
		{
			$results = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_search' ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'query'	=> array(
					'bool'	=> array(
						'filter' => array(
							array(
								'terms' => array(
									'index_class_type_id_hash' => $hashes
								)
							),
							array(
								'terms' => array(
									'index_permissions' => array_merge( $member->permissionArray(), array( '*' ) )
								)
							),
							array(
								'term'	=> array(
									'index_hidden' => 0
								)
							)
						)
					)
				),
				'size'	=> $limit ?: 10 // If we define a limit, use that, otherwise default to 10 which is ElasticSearch's default
			) ) )->decodeJson();
		}
		catch ( Exception $e )
		{
			Log::log( $e, 'elasticsearch' );
			return array();
		}
		
		$hashesWithPermission = array();
		foreach ( $results['hits']['hits'] as $result )
		{
			$hashesWithPermission[ $result['_source']['index_class_type_id_hash'] ] = $result['_source']['index_class_type_id_hash'];
		}
		
		return $hashesWithPermission;
	}
	
	/**
	 * Get timestamp of oldest thing in index
	 *
	 * @return 	int|null
	 */
	public function firstIndexDate(): int|NULL
	{
		try
		{
			$results = Index::request( $this->url->setPath( $this->url->data[ Url::COMPONENT_PATH ] . '/_doc/_search' ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'size'	=> 1,
				'sort'	=> array( array( 'index_date_updated' => 'asc' ) )
			) ) )->decodeJson();
			
			if ( isset( $results['hits']['hits'][0] ) )
			{
				return $results['hits']['hits'][0]['_source']['index_date_updated'];
			}
			
			return NULL;
		}
		catch ( Exception $e )
		{
			Log::log( $e, 'elasticsearch' );
			return NULL;
		}
	}
	
	/**
	 * Convert terms into stemmed terms for the highlighting JS
	 *
	 * @param	array	$terms	Terms
	 * @return	array
	 */
	public function stemmedTerms( array $terms ): array
	{
		$analyzer = Settings::i()->search_elastic_analyzer;
		if ( $analyzer === 'custom' )
		{
			$analysisSettings = json_decode( '{' . Settings::i()->search_elastic_custom_analyzer . '}', TRUE );
			$analyzer = key( $analysisSettings['analyzer'] );
		}
		
		try
		{
			$results = Index::request( $this->url->setPath( '/_analyze' ) )->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'analyzer'	=> $analyzer,
				'text'		=> implode( ' ', $terms )
			) ) )->decodeJson();
			
			if ( isset( $results['tokens'] ) )
			{
				$stemmed = $terms;
				foreach ( $results['tokens'] as $token )
				{
					$stemmed[] = $token['token'];
				}
				return $stemmed;
			}
			
			return $terms;
		}
		catch ( Exception )
		{
			return $terms;
		}
	}
	
	/**
	 * Supports filtering by views?
	 *
	 * @return	bool
	 */
	public function supportViewFiltering(): bool
	{
		return FALSE;
	}

	/**
	 * Wrapper to account for log in where needed
	 *
	 * @param Url $url
	 * @param int|null $timeout
	 * @return Curl
	 */
	public static function request( Url $url, int|null $timeout=NULL ) : Curl
	{
		if ( ELASTICSEARCH_USER or ELASTICSEARCH_PASSWORD )
		{
			return $url->request( $timeout )->login( ELASTICSEARCH_USER, ELASTICSEARCH_PASSWORD );
		}

		return $url->request( $timeout );
	}

	/**
	 * Check response to see if an error was produced
	 *
	 * @param	Response	$response	Response object
	 * @return	array|null
	 */
	protected function getResponseError( Response $response ): array|NULL
	{
		/* Log any errors */
		if( $response->httpResponseCode != 200 AND $content = $response->decodeJson() AND isset( $content['error'] ) )
		{
			return $content['error'];
		}

		return NULL;
	}
}