<?php
/**
 * @brief		Table Builder using an array datasource
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Helpers\Table;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Request;
use function array_slice;
use function count;
use function defined;
use function in_array;
use function is_callable;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * List Table Builder using an array datasource
 */
class Custom extends Table
{
	/**
	 * @brief	Data
	 */
	protected array $dataSource;
	
	/**
	 * @brief	Parse Source
	 */
	public bool $parseSource = TRUE;
	
	/**
	 * Number of results
	 */
	public int $count = 0;
	
	/**
	 * Number of pages
	 */
	public int $pages = 0;
	
	/**
	 * Constructor
	 *
	 * @param array $dataSource		Data source
	 * @param	Url	$baseUrl		Base URL
	 */
	public function __construct( array $dataSource, Url $baseUrl, int $count = NULL )
	{
		$this->dataSource = $dataSource;
		$this->count      = count( $this->dataSource );
		
		return parent::__construct( $baseUrl );
	}
	
	/**
	 * Get rows
	 *
	 * @param	array|null	$advancedSearchValues	Values from the advanced search form
	 * @note	$advancedSearchValues is currently ignored
	 * @return	array
	 */
	public function getRows( array $advancedSearchValues = NULL ): array
	{
		if ( ! $this->pages )
		{
			$this->pages = ceil( $this->count / $this->limit );
		}
		
		/* Get them */
		$rows = array();
		foreach ( $this->dataSource as $i => $data )
		{
			$row = $this->include ? array_combine( $this->include, array_fill( 0, count( $this->include ), NULL ) ) : array();

			/* Get the columns from the XML */
			foreach ( $data as $k => $v )
			{
				/* Are we including this one? */
				if( ( $this->include !== NULL and !in_array( $k, $this->include ) ) or ( $this->exclude !== NULL and in_array( $k, $this->exclude ) ) )
				{
					continue;
				}
				
				/* Add to array */
				$row[ $k ] = $v;
			}
			
			/* Add it to the array */
			$rows[ $i ] = $row;
		}
				
		/* Quicksearch */
		if ( $this->quickSearch !== NULL and Request::i()->quicksearch )
		{
			$quickSearchColumn = $this->quickSearch;
			$rows = array_filter( $rows, is_callable( $this->quickSearch ) ? $this->quickSearch : function( $row ) use ( $quickSearchColumn )
			{
				return mb_strpos( mb_strtolower( $row[ $quickSearchColumn ] ), mb_strtolower( trim( Request::i()->quicksearch ) ) ) !== FALSE;
			} );

			$this->count = count( $rows );
			$this->pages = ceil( $this->count / $this->limit );
		}

		/* Do we need to sort? */
		if( $this->parseSource and !empty( $rows ) and $this->sortBy and isset( $rows[ key( $rows ) ][ $this->sortBy ] ) )
		{
			$sortBy = $this->sortBy;
			$sortDirection = $this->sortDirection;
			uasort( $rows, function( $a, $b ) use ( $sortBy, $sortDirection )
			{
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
		
		/* Limit */
		if ( $this->limit and $this->count > $this->limit and $this->parseSource )
		{
			$rows = array_slice( $rows, ( $this->limit * ( $this->page - 1 ) ), $this->limit, TRUE );
		}

		/* Apply parsers */
		foreach( $rows as $i => $row )
		{
			foreach( $row as $k => $v )
			{
				/* Parse if necessary */
				if( isset( $this->parsers[ $k ] ) )
				{
					$parserFunction = $this->parsers[ $k ];
					$v = $parserFunction( $v, $this->dataSource[ $i ] );
				}
				else if ( is_string( $v ) )
				{
					$v = htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
				}
				
				$rows[ $i ][ $k ] = $v;
			}
			
			/* Add in some buttons if necessary */
			if( $this->rowButtons !== NULL )
			{
				$rowButtons = $this->rowButtons;
				$rows[ $i ]['_buttons'] = $rowButtons( $row, $i );
			}
		}
		
		return $rows;
	}
}

