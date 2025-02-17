<?php
/**
 * @brief		Database SELECT Statement
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Aug 2013
 */

namespace IPS\Db;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Countable;
use InvalidArgumentException;
use IPS\Db;
use Iterator;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Database SELECT Statement
 */
class Select implements Iterator, Countable
{
	/**
	 * @brief	The query
	 */
	public string $query;

	/**
	 * @brief	Non-JOIN Binds
	 */
	public array $binds = array();

	/**
	 * @brief	JOIN Binds (need to separate them because the JOIN clauses come before the WHERE clause)
	 */
	protected array $joinBinds = array();

	/**
	 * @brief	The database object
	 */
	protected Db $db;

	/**
	 * @brief	The statement
	 */
	protected mixed $stmt = NULL;

	/**
	 * @brief	If TRUE, will return the result as a multidimensional array, with each joined table separately
	 */
	protected bool $multiDimensional = FALSE;

	/**
	 * @brief	If read/write separation is enabled, SELECTs normally go to the read server - this flag will set this query to use the write server
	 */
	protected bool $useWriteServer = FALSE;

	/**
	 * @brief   Is this a union query? - this should only be set by the UNION method in \IPS\Db
	 */
	public bool $isUnion = FALSE;

	/**
	 * Constuctor
	 *
	 * @param string $query				The query
	 * @param	array	$binds				Binds
	 * @param	Db	$db					The database object
	 * @param bool $multiDimensional	If TRUE, will return the result as a multidimensional array, with each joined table separately
	 * @param bool $useWriteServer		If read/write sepration is enabled, SELECTs normally go to the read server - this flag will set thia query to use the write server
	 * @return	void
	 */
	public function __construct( string $query, array $binds, Db $db, bool $multiDimensional=FALSE, bool $useWriteServer=FALSE )
	{
		$this->query				= $query;
		$this->binds				= $binds;
		$this->db					= $db;
		$this->multiDimensional		= $multiDimensional;
		$this->useWriteServer		= $useWriteServer;
	}

	/**
	 * Return the query
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->query;
	}

	/**
	 * Return the query
	 *
	 * @return	string
	 */
	public function returnFullQuery(): string
	{
		return Db::_replaceBinds( $this->query, array_merge( $this->joinBinds, $this->binds ) );
	}

	/**
	 * Force an index
	 *
	 * @param string $index		Index name to force
	 * @return    Select
	 */
	public function forceIndex( string $index ): Select
	{
		$this->query = preg_replace( '/(FROM `(.+?)`( AS `(.+?)`)?)/', "$1 FORCE INDEX(`" . $index . "`)", $this->query, 1 );

		return $this;
	}

	/**
	 * Add Join
	 *
	 * @param array|string|Select $table	The table to select from. Either (string) table_name or (array) ( name, alias ) or \IPS\Db\Select object
	 * @param	mixed							$on		The on clause for the join
	 * @param string $type	Type of join (left, right, inner, cross, straight_join)
	 * @param bool $using	Whether to append the using clause for the join
	 * @return    Select
	 */
	public function join( Select|array|string $table, mixed $on, string $type='LEFT', bool $using=FALSE ): Select
	{
		$query = '';
		$joinConditionIsOptional = TRUE;

		switch ( $type )
		{
			case 'INNER':
			case 'CROSS':
				$query .= ' INNER JOIN ';
				break;

			case 'STRAIGHT_JOIN':
				$query .= ' STRAIGHT_JOIN';
				if ( $using )
				{
					throw new InvalidArgumentException; // USING cannot be used with STRAIGHT_JOIN
				}
				break;

			case 'LEFT':
			case 'RIGHT':
				$query .= ' ' . $type . ' JOIN';
				$joinConditionIsOptional = FALSE;
				break;
			case 'JOIN':
				$query .= ' JOIN';
				break;
		}

		if ( $table instanceof Select)
		{
			$tableQuery = $table->query;
			preg_match( '/FROM `(.+?)`( AS `(.+?)`)?/', $tableQuery, $matches );

			if ( isset( $matches[2] ) )
			{
				$query .= "( " . str_replace( $matches[2], '', $tableQuery ) . " ) AS `{$matches[3]}`";
			}
			else
			{
				$query .= "( {$tableQuery} ) AS `{$matches[1]}`";
			}
		}
		elseif ( is_array( $table ) )
		{
			if ( substr( $table[0], 0, 1 ) === '(' )
			{
				$query .= " {$table[0]} AS `{$table[1]}`";
			}
			else
			{
				$query .= " `{$this->db->prefix}{$table[0]}` AS `{$table[1]}`";
			}
		}
		else
		{
			$query .= $this->db->prefix ? " `{$this->db->prefix}{$table}` AS `{$table}`" : " `{$table}`";
		}

		if ( $on )
		{
			if ( $using )
			{
				$query .= ' USING ( ' . implode( ', ', array_map( function( $col )
					{
						return '`' . $col . '`';
					}, $on ) ) . ' ) ';
			}
			else if ( is_array( $on ) AND isset($on[1]) and $on[1] instanceof Select)
			{
				$query .= ' ON ' . $on[0] . '=(' . $on[1]->query . ')';

				if( count( $on[1]->binds ) )
				{
					foreach ( $on[1]->binds as $bind )
					{
						$this->joinBinds[] = $bind;
					}
				}
			}
			else
			{
				$where = $this->db->compileWhereClause( $on );
				$query .= ' ON ' . $where['clause'];
				foreach ( $where['binds'] as $bind )
				{
					$this->joinBinds[] = $bind;
				}
			}
		}
		elseif ( !$joinConditionIsOptional )
		{
			throw new InvalidArgumentException;
		}

		/* If we are joining on a sub-query already, remove it temporarily to ensure our regex below works correctly */
		$subqueries = array();

		while( preg_match( "/JOIN\( SELECT(.+?)\) AS .+? ON .+?/", $this->query, $matches ) )
		{
			$key = md5( $matches[0] );
			$subqueries[ $key ] = $matches[0];
			$this->query = str_replace( $matches[0], $key, $this->query );
		}

		if ( $this->isUnion )
		{
			$this->query = str_replace( 'derivedTable', "derivedTable {$query}", $this->query );
		}
		else
		{
			$this->query = preg_replace( '/(WHERE|GROUP BY|HAVING|LIMIT|ORDER BY|$)/', $query . ' $1', $this->query, 1 );
		}

		/* Now put our subquery joins back in */
		foreach( $subqueries as $k => $v )
		{
			$this->query = str_replace( $k, $v, $this->query );
		}

		return $this;
	}

	/**
	 * @brief	Columns in the resultset
	 */
	protected array $columns = array();

	/**
	 * @brief	Key Field
	 */
	protected mixed $keyField = NULL;

	/**
	 * @brief	Key Table
	 */
	protected ?string $keyTable = NULL;

	/**
	 * @brief	Value Field
	 */
	protected mixed $valueField = NULL;

	/**
	 * @brief	Value Table
	 */
	protected ?string $valueTable = NULL;

	/**
	 * Set key field
	 *
	 * @param callable|string $column	Column to treat as the key
	 * @param string|null $table	The table, if this is a multidimensional select
	 * @return    Select
	 */
	public function setKeyField( callable|string $column, string $table=NULL ): Select
	{
		if ( !$this->stmt )
		{
			$this->runQuery();
		}

		if ( is_string( $column ) )
		{
			if ( $this->multiDimensional )
			{
				if ( !isset( $this->columns[ $table ] ) or !in_array( $column, $this->columns[ $table ] ) )
				{
					throw new InvalidArgumentException;
				}
			}
			else
			{
				if ( !in_array( $column, $this->columns ) )
				{
					throw new InvalidArgumentException;
				}
			}
		}

		$this->keyField = $column;
		$this->keyTable = $table;

		return $this;
	}

	/**
	 * Set value field
	 *
	 * @param callback|string $column		Column to treat as the value. Callback to determine on a per-row basis.
	 * @param string|null $table	The table, if this is a multidimensional select
	 * @return    Select
	 */
	public function setValueField( callable|string $column, string $table=NULL ): Select
	{
		if ( !$this->stmt )
		{
			$this->runQuery();
		}

		if ( is_string( $column ) )
		{
			if ( $this->multiDimensional )
			{
				if ( !isset( $this->columns[ $table ] ) or !in_array( $column, $this->columns[ $table ] ) )
				{
					throw new InvalidArgumentException;
				}
			}
			else
			{
				if ( !in_array( $column, $this->columns ) )
				{
					throw new InvalidArgumentException;
				}
			}
		}

		$this->valueField = $column;
		$this->valueTable = $table;

		return $this;
	}

	/**
	 * @brief	The current row
	 */
	protected mixed $row = NULL;

	/**
	 * @brief	The current key
	 */
	protected ?int $key = null;

	/**
	 * Get first record
	 *
	 * @return	mixed
	 * @throws	UnderflowException
	 */
	public function first(): mixed
	{
		/* Move to the first result */
		$this->rewind();

		/* Return it */
		if ( !$this->valid() )
		{
			throw new UnderflowException;
		}
		return $this->current();
	}

	/**
	 * Run the query
	 *
	 * @return	void
	 */
	protected function runQuery() : void
	{
		/* Run the query */
		$this->stmt = $this->db->preparedQuery( $this->query, array_merge( $this->joinBinds, $this->binds ), !$this->useWriteServer );

		/* Populate $this->row which we read into */
		$this->row = array();
		$params = array();
		$meta = $this->stmt->result_metadata();

		if ( !$meta )
		{
			throw new Exception( $this->db->errno ? $this->db->error : "No result metadata - possible table crash", $this->db->errno ?: -1, NULL, $this->query, array_merge( $this->joinBinds, $this->binds ) );
		}

		while ( $field = $meta->fetch_field() )
		{
			if ( $this->multiDimensional )
			{
				$params[] = &$this->row[ $field->table ][ $field->name ];
			}
			else
			{
				$params[] = &$this->row[ $field->name ];
			}
		}

		$meta->free_result();

		if ( $this->multiDimensional )
		{
			foreach ( $this->row as $table => $columns )
			{
				$this->columns[ $table ] = array_keys( $columns );
			}
		}
		else
		{
			$this->columns = array_keys( $this->row );
		}

		$stmtReference = $this->stmt;
		$stmtReference->bind_result( ...$params );

		/* Set counts */
		$this->count = $this->stmt->num_rows;

		/* Set the key to -1 (i.e. we haven't fetched any row yet) */
		$this->key = -1;
	}

	/**
	 * [Iterator] Rewind - will (re-)execute statement
	 *
	 * @return	void
	 */
	public function rewind(): void
	{
		/* If we haven't run the query yet, or we have already traversed the result set beyond the first result, (re-)run the query */
		if ( !$this->stmt or $this->key > 0 )
		{
			$this->runQuery();
		}

		/* Move to the first result if we haven't already */
		if ( $this->key === -1 )
		{
			$this->next();
		}
	}

	/**
	 * [Iterator] Get current row
	 *
	 * @return	mixed
	 */
	public function current(): mixed
	{
		if ( $this->valueField )
		{
			if ( !is_string( $this->valueField ) and is_callable( $this->valueField ) )
			{
				$valueField = $this->valueField;
				return $valueField( $this->row );
			}
			else
			{
				if ( $this->valueTable )
				{
					return $this->row[ $this->valueTable ][ $this->valueField ];
				}
				else
				{
					return $this->row[ $this->valueField ];
				}
			}
		}
		elseif ( count( $this->row ) === 1 )
		{
			foreach ( $this->row as $v )
			{
				return $v;
			}
		}
		else
		{
			$row = array();
			foreach ( $this->row as $k => $v )
			{
				if ( is_array( $v ) )
				{
					foreach ( $v as $k2 => $v2 )
					{
						$row[ $k ][ $k2 ] = $v2;
					}
				}
				else
				{
					$row[ $k ] = $v;
				}
			}
			return $row;
		}

		return NULL;
	}

	/**
	 * [Iterator] Get current key
	 *
	 * @return	mixed
	 */
	public function key(): mixed
	{
		if ( $this->keyField )
		{
			if ( is_string( $this->keyField ) )
			{
				if ( $this->keyTable )
				{
					return $this->row[ $this->keyTable ][ $this->keyField ];
				}
				else
				{
					return $this->row[ $this->keyField ];
				}
			}
			else
			{
				$keyField = $this->keyField;
				return $keyField( $this->row );
			}
		}
		else
		{
			return $this->key;
		}
	}

	/**
	 * [Iterator] Fetch next result
	 *
	 * @return	void
	 */
	public function next(): void
	{
		$fetch = $this->stmt->fetch();
		if ( $fetch === NULL )
		{
			$this->stmt->close();
			$this->row = NULL;
		}
		$this->key++;
	}

	/**
	 * [Iterator] Is the current row valid?
	 *
	 * @return	bool
	 */
	public function valid(): bool
	{
		return ( $this->row !== NULL );
	}

	/**
	 * @brief	Number of rows in this set
	 */
	protected ?int $count = NULL;

	/**
	 * [Countable] Get number of rows
	 *
	 * @return	int
	 */
	public function count(): int
	{
		if ( !$this->stmt )
		{
			$this->runQuery();
		}

		return $this->count;
	}
}