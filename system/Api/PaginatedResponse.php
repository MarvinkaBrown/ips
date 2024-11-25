<?php
/**
 * @brief		PaginatedAPI Response
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 Dec 2015
 */

namespace IPS\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Db\Select;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use Iterator;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * API Response
 */
class PaginatedResponse extends Response
{
	/**
	 * @brief	HTTP Response Code
	 */
	public int $httpCode = 200;
	
	/**
	 * @brief	Iterator (usually a select query)
	 */
	protected ?Iterator $iterator = null;
	
	/**
	 * @brief	Current page
	 */
	protected int $page = 1;
	
	/**
	 * @brief	Results per page
	 */
	protected int $resultsPerPage = 25;
	
	/**
	 * @brief	ActiveRecord class
	 */
	protected string $activeRecordClass = '';
	
	/**
	 * @brief	Total Count
	 */
	protected int $count = 0;
	
	/**
	 * @brief	The member making the API request or NULL for API Key / client_credentials
	 */
	protected ?Member $authorizedMember = null;
	
	/**
	 * Constructor
	 *
	 * @param	int				$httpCode			HTTP Response code
	 * @param	Iterator|array		$iterator			Select query or \IPS\Patterns\ActiveRecordIterator instance
	 * @param	int				$page				Current page
	 * @param	string			$activeRecordClass	ActiveRecord class
	 * @param	int|NULL				$count				Total Count
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @param	int|null				$perPage			Number of results per page
	 * @return	void
	 */
	public function __construct( int $httpCode, Iterator $iterator, int $page, string $activeRecordClass, ?int $count, ?Member $authorizedMember = NULL, ?int $perPage=NULL )
	{
		$this->httpCode				= $httpCode;
		$this->page = $page > 0 ? $page : 1;
		$this->iterator				= $iterator;
		$this->activeRecordClass	= $activeRecordClass;
		$this->count				= (int) $count;
		$this->resultsPerPage		= $perPage ?: 25;
		$this->authorizedMember		= $authorizedMember;

		if( $this->iterator instanceof Select )
		{
			/* Limit the query before calling count(), as this runs the query so it cannot be modified after */
			$this->iterator->query .= Db::i()->compileLimitClause( array( ( $this->page - 1 ) * $this->resultsPerPage, $this->resultsPerPage ) );

			if ( $count === NULL )
			{
				$this->count = $iterator->count();
			}
		}
	}
	
	/**
	 * Data to output
	 *
	 * @return	array
	 */
	public function getOutput() : array
	{
		$results = array();

		if ( $this->activeRecordClass )
		{
			if( $this->iterator instanceof ActiveRecordIterator )
			{
				foreach ( $this->iterator as $result )
				{
					$results[] = $result->apiOutput( $this->authorizedMember );
				}
			}
			else
			{
				foreach ( new ActiveRecordIterator( $this->iterator, $this->activeRecordClass ) as $result )
				{
					$results[] = $result->apiOutput( $this->authorizedMember );
				}
			}
		}
		else
		{
			foreach ( $this->iterator as $result )
			{
				$results[] = $result;
			}
		}
				
		return array(
			'page'			=> $this->page,
			'perPage'		=> $this->resultsPerPage,
			'totalResults'	=> $this->count,
			'totalPages'	=> ceil( $this->count / $this->resultsPerPage ),
			'results'		=> $results
		);
	}
}