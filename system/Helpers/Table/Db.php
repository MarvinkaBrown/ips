<?php
/**
 * @brief		Table Builder using a database table datasource
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Helpers\Table;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use Exception;
use IPS\DateTime;
use IPS\Db as IPSDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Request;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * List Table Builder using a database table datasource
 */
class Db extends Table
{
	/**
	 * @brief	Database Table
	 */
	protected ?string $table = NULL;
	
	/**
	 * @brief	Selects
	 */
	public array $selects = array();
	
	/**
	 * @brief	Initial WHERE clause
	 */
	public mixed $where = NULL;

	/**
	 * @brief	Force index clause
	 */
	protected mixed $index = NULL;

	/**
	 * @brief	Primary sort column
	 */
	public ?string $primarySortBy = NULL;

	/**
	 * @brief	Direction of primary sort
	 */
	public ?string $primarySortDirection = NULL;
	
	/**
	 * @brief	Joins
	 */
	public array $joins = array();
	
	/**
	 * @brief	Key field
	 */
	public mixed $keyField = NULL;

	/**
	 * @brief	Group by key
	 */
	public string|array|null $groupBy = NULL;

	/**
	 * @brief	The database we will query against
	 */
	public ?IPSDb $db = NULL;

	/**
	 * Constructor
	 *
	 * @param string $table Database table
	 * @param Url $baseUrl Base URL
	 * @param array|string|null $where WHERE clause
	 * @param array|null $forceIndex Index to force
	 * @param IPSDb|null $database An instance of \IPS\Db to run the queries against (defaults to current connection)
	 */
	public function __construct( string $table, Url $baseUrl, array|string $where=null, mixed $forceIndex=null, IPSDb $database=null )
	{
		$this->table = $table;
		$this->where = $where;
		$this->index = $forceIndex;
		$this->db	 = $database ?? IPSDb::i();
		
		return parent::__construct( $baseUrl );
	}

	/**
	 * Get rows
	 * @note This method is called twice, so if there'some expensive operation happening, or if you're calculating something, make sure to cache it.
	 *
	 * @param array|null $advancedSearchValues Values from the advanced search form
	 * @return    array
	 * @throws Exception
	 */
	public function getRows( array $advancedSearchValues = null ): array
	{
		/* Specify filter in where clause */
		$where = $this->where ? is_array( $this->where ) ? $this->where : array( $this->where ) : array();

		if ( $this->filter and isset( $this->filters[ $this->filter ] ) )
		{
			$where[] = is_array( $this->filters[ $this->filter ] ) ? $this->filters[ $this->filter ] : array( $this->filters[ $this->filter ] );
		}
		
		/* Add quick search term to where clause if necessary */
		if ( $this->quickSearch !== NULL and Request::i()->quicksearch )
		{
			if ( is_callable( $this->quickSearch ) )
			{
				$quickSearchFunc = $this->quickSearch;
				$where[] = $quickSearchFunc( trim( Request::i()->quicksearch ) );
			}
			else
			{
				$columns = is_array( $this->quickSearch ) ? $this->quickSearch[0] : $this->quickSearch;
				$columns = is_array( $columns ) ? $columns : array( $columns );
				
				$_where = array();
				foreach ( $columns as $c )
				{
					$_where[] = "LOWER(`{$c}`) LIKE CONCAT( '%', ?, '%' )";
				}
				
				$where[] = array_merge( array( '(' . implode( ' OR ', $_where ) . ')' ), array_fill( 0, count( $_where ), mb_strtolower( trim( Request::i()->quicksearch ) ) ) );
			}
		}

		/* Add advanced search */
		if ( !empty( $advancedSearchValues ) )
		{
			foreach ( $advancedSearchValues as $k => $v )
			{
				if ( isset( $this->advancedSearch[ $k ] ) AND $v !== '' AND ( !is_array( $v ) OR !empty( $v ) ) )
				{
					$type = $this->advancedSearch[ $k ];

					if ( is_array( $type ) )
					{
						if ( isset( $type[2] ) )
						{
							$lambda = $type[2];
							$type = SEARCH_CUSTOM;
						}
						else
						{
							$options = $type[1];
							$type = $type[0];
						}
					}
					
					switch ( $type )
					{
						case SEARCH_CUSTOM:
							if ( $clause = $lambda( $v ) )
							{
								$where[] = $clause;
							}
							else
							{
								unset( $advancedSearchValues[ $k ] );
							}
							break;
					
						case SEARCH_CONTAINS_TEXT:
							$where[] = array( "{$k} LIKE ?", '%' . $v . '%' );
							break;
						case SEARCH_QUERY_TEXT:
							if ( !empty( $v[1] ) )
							{
								switch ( $v[0] )
								{
									case 'c':
										$where[] = array( "{$k} LIKE ?", '%' . $v[1] . '%' );
										break;
									case 'bw':
										$where[] = array( "{$k} LIKE ?", $v[1] . '%' );
										break;
									case 'eq':
										$where[] = array( "{$k}=?", $v[1] );
										break;
								}
							}
							else
							{
								unset( $advancedSearchValues[ $k ] );
							}
							break;	
						case SEARCH_DATE_RANGE:
							$timezone = ( Member::loggedIn()->timezone ? new DateTimeZone( Member::loggedIn()->timezone ) : NULL );

							if( !$v['start'] AND !$v['end'] )
							{
								unset( $advancedSearchValues[ $k ] );
							}

							if ( $v['start'] )
							{
								if( !( $v['start'] instanceof DateTime ) )
								{
									$v['start'] = new DateTime( $v['start'], $timezone );
								}

								$where[] = array( "{$k}>?", $v['start']->getTimestamp() );
							}
							if ( $v['end'] )
							{
								if( !( $v['end'] instanceof DateTime ) )
								{
									$v['end'] = new DateTime( $v['end'], $timezone );
								}

								$where[] = array( "{$k}<?", $v['end']->getTimestamp() );
							}
							break;
						
						case SEARCH_SELECT:
							if ( isset( $options['multiple'] ) AND $options['multiple'] === TRUE )
							{
								$where[] = array( $this->db->findInSet( $k, $v ) );
								break;
							}
							// No break so we fall through to radio
							
						case SEARCH_RADIO:
							$where[] = array( "{$k}=?", $v );
							break;
							
						case SEARCH_MEMBER:
							if ( $v )
							{
								$where[] = array( "{$k}=?", ( $v instanceof Member ) ? $v->member_id : $v );
							}
							else
							{
								unset( $advancedSearchValues[ $k ] );
							}
							break;
							
						case SEARCH_NODE:
							$nodeClass = $options['class'];
							$prop = $options['searchProp'] ?? '_id';
							if ( !is_array( $v ) )
							{
								$v = array( $v );
							}
							
							$values = array();
							foreach ( $v as $_v )
							{
								if ( !is_object( $_v ) )
								{
									if ( mb_substr( $_v, 0, 2 ) === 's.' )
									{
										$nodeClass = $nodeClass::$subnodeClass;
										$_v = mb_substr( $_v, 2 );
									}
									try
									{
										$_v = $nodeClass::load( $_v );
									}
									catch ( OutOfRangeException $e )
									{
										continue;
									}
								}
								$values[] = $_v->$prop;
							}
							$where[] = array( $this->db->in( $k, $values ) );
							break;
						
						case SEARCH_NUMERIC:
						case SEARCH_NUMERIC_TEXT:
							switch ( $v[0] )
							{
								case 'gt':
									$where[] = array( "{$k}>?", (float) $v[1] );
									break;
								case 'lt':
									$where[] = array( "{$k}<?", (float) $v[1] );
									break;
								case 'eq':
									$where[] = array( "{$k}=?", (float) $v[1] );
									break;
							}
							break;
							
						case SEARCH_BOOL:
							$where[] = array( "{$k}=?", (bool) $v );
							break;
					}
				}
				else
				{
					unset( $advancedSearchValues[ $k ] );
				}
			}
		}

		$selects = $this->selects;
		$isOtherJoin = false;

		if ( count( $this->joins ) )
		{
			foreach( $this->joins as $join )
			{
				if ( isset( $join['select'] ) )
				{
					$selects[] = $join['select'];
				}

				if ( isset( $join['type'] ) and mb_strtolower( $join['type'] ) != 'left' )
				{
					/* Inner join or straight join which will affect the query results */
					$isOtherJoin = true;
				}
			}
		}
		
		/* Count results (for pagination) */
		$count = $this->db->select( 'count(*)', $this->table, $where, NULL, NULL, $this->groupBy  );
		if ( count( $this->joins ) )
		{
			/* Add the joins if we have a where statement and all joins are left for the count */
			if ( ( $isOtherJoin or count( $where ) ) and count( $this->joins ) )
			{
				foreach ( $this->joins as $join )
				{
					$count->join( $join['from'], ( $join['where'] ?? null ), ( isset( $join['type'] ) ) ? $join['type'] : 'LEFT' );
				}
			}
		}

		$count		= $this->groupBy ? $count->count() : $count->first();
	
		$selectPrefix = ( $this->groupBy ) ? '' : $this->table . '.*, ';

		/* Now get column headers */
		$query = $this->db->select( ( count( $selects ) ) ? $selectPrefix . implode( ', ', $selects ) : '*', $this->table, NULL, NULL, array( 0, 1 ), $this->groupBy );

		if ( count( $this->joins ) )
		{
			foreach( $this->joins as $join )
			{
				$query->join( $join['from'], ( $join['where'] ?? null ), ( isset( $join['type'] ) ) ? $join['type'] : 'LEFT' );
			}
		}

		try
		{
			$results	= $query->first();
		}
		catch( UnderflowException $e )
		{
			$results	= array();
		}

		$this->pages = ceil( $count / $this->limit );

		/* What are we sorting by? */
		$orderBy = NULL;
		if ( $this->_isSqlSort( $results ) )
		{
			$orderBy = implode( ',', array_map( function( $v )
			{
				/* This gives you something like "g`.`g_id" which is then turned into "`g`.`g_id`" below */
				$v = str_replace( '.', "`.`", $v );

				if ( ! mb_strstr( trim( $v ), ' ' ) )
				{
					$return = ( isset( $this->advancedSearch[$v] ) and $this->advancedSearch[$v] == SEARCH_NUMERIC_TEXT ) ? 'LENGTH(' . '`' . trim( $v ) . '`' . ') ' . $this->sortDirection . ', ' . '`' . trim( $v ) . '` ' : '`' . trim( $v ) . '` ';
				}
				else
				{
					[ $field, $direction ] = explode( ' ', $v );
					$return = ( isset( $this->advancedSearch[$v] ) and $this->advancedSearch[$v] == SEARCH_NUMERIC_TEXT ) ? 'LENGTH(' . '`' . trim( $field ) . '`' . ') ' . mb_strtolower( $direction ) == 'asc' ? 'asc' : 'desc' . ', ' . '`' . trim( $field ) . '` ' : '`' . trim( $field ) . '` ';
					$return .= ( mb_strtolower( $direction ) == 'asc' ? 'asc' : 'desc' );
				}
				return $return;
			}, explode( ',', $this->sortBy ) ) );
			
			$orderBy .= $this->sortDirection == 'asc' ? ' asc' : ' desc';

			/* Primary sorting effectively creates a way to 'pin' records regardless of the user selected sort */
			if( $this->primarySortBy !== NULL AND $this->primarySortDirection !== NULL )
			{
				$orderBy = "{$this->primarySortBy} {$this->primarySortDirection}, {$orderBy}";
			}
		}

		/* Are we downloading? Bypass Table Limit */
		$limit = Request::i()->download ? $count : $this->limit;

		/* Run query */
		$rows = array();
		$select = $this->db->select(
			( count( $selects ) ) ? $selectPrefix . implode( ', ', $selects ) : '*',
			$this->table,
			$where,
			$orderBy,
			array( ( $this->limit * ( $this->page - 1 ) ), $limit ),
			$this->groupBy
		);

		if ( $this->index )
		{
			$select->forceIndex( $this->index );
		}

		if ( count( $this->joins ) )
		{
			foreach( $this->joins as $join )
			{
				$select->join( $join['from'], $join['where'], ( isset( $join['type'] ) ) ? $join['type'] : 'LEFT' );
			}
		}
		if ( $this->keyField !== NULL )
		{
			$select->setKeyField( $this->keyField );
		}

		foreach ( $select as $rowId => $row )
		{
			/* Add in any 'custom' fields */
			$_row = $row;
			if ( $this->include !== NULL )
			{
				$row = array();
				foreach ( $this->include as $k )
				{
					$row[ $k ] = $_row[$k] ?? NULL;
				}
				
				if( !empty( $advancedSearchValues ) AND !isset( Request::i()->noColumn ) )
				{
					foreach ( $advancedSearchValues as $k => $v )
					{
						$row[ $k ] = $_row[$k] ?? NULL;
					}
				}
			}
			
			/* Loop the data */
			foreach ( $row as $k => $v )
			{
				/* Parse if necessary (NB: deliberately do this before removing the row in case we need to do some processing, but don't want the column to actually show) */
				if( isset( $this->parsers[ $k ] ) )
				{
					$thisParser = $this->parsers[ $k ];
					$v = $thisParser( $v, $_row );
				}
				else
				{
					$v = htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
				}

				/* Are we including this one? */
				if( ( ( $this->include !== NULL and !in_array( $k, $this->include ) ) or ( $this->exclude !== NULL and in_array( $k, $this->exclude ) ) ) and !array_key_exists( $k, $advancedSearchValues ) )
				{
					unset( $row[ $k ] );
					continue;
				}
											
				/* Add to array */
				$row[ $k ] = $v;
			}

			/* Add in some buttons if necessary */
			if( $this->rowButtons !== NULL )
			{
				$rowButtons = $this->rowButtons;
				$row['_buttons'] = $rowButtons( $_row );
			}
			
			/* Highlighting? */
			if ( isset( $this->parsers['_highlight'] ) )
			{
				$class = $this->parsers['_highlight'];
				if( $class = $class( $_row ) )
				{
					$this->highlightRows[ $rowId ] = $class;
				}
			}
			
			$rows[ $rowId ] = $row;
		}
		
		/* If we're sorting on a column not in the DB, do it manually */
		if ( $this->sortBy and $this->_isSqlSort( $results ) !== true )
		{
			$sortBy = $this->sortBy;
			$sortDirection = $this->sortDirection;
			uasort( $rows, function( $a, $b ) use ( $sortBy, $sortDirection )
			{
				if( !isset( $a[ $sortBy ] ) )
				{
					return 0;
				}

				if( $sortDirection === 'asc' )
				{
					return strnatcasecmp( mb_strtolower( $a[ $sortBy ] ), mb_strtolower(  $b[ $sortBy ] ) );
				}
				else
				{
					return strnatcasecmp( mb_strtolower(  $b[ $sortBy ] ), mb_strtolower( $a[ $sortBy ] ) );
				}
			});
		}

		/* Return */
		return $rows;
	}
	
	/**
	 * User set sortBy is suitable for an SQL sort operation
	 * @param array $count	Result of count(*) query with field names included
	 * @return	boolean
	 */
	protected function _isSqlSort( array $count ): bool
	{
		if ( !$this->sortBy )
		{
			return false;
		}

		if( !is_array( $count ) )
		{
			$count = array( $count );
		}
		
		if ( mb_strstr( $this->sortBy, ',' ) )
		{
			foreach( explode( ',', $this->sortBy ) as $field )
			{
				/* Get rid of table alias if there is one */
				if( mb_strpos( $field, '.' ) !== FALSE )
				{
					$field = explode( '.', $field );
					$field = $field[1];
				}

				$field = trim($field);
				
				if ( mb_strstr( $field, ' ' ) )
				{
					[ $field, $direction ] = explode( ' ', $field );
				}
				
				if ( !array_key_exists( trim($field), $count ) )
				{
					return false;
				}
			}
			
			return true;
		}
		elseif ( array_key_exists( preg_replace( "/^.+?\.(.+?)$/", "$1", $this->sortBy ), $count ) )
		{
			return true;
		}
				
		return false;
	}

	/**
	 * What custom multimod actions are available
	 *
	 * @return	array
	 */
	public function customActions(): array
	{
		return array();
	}
}