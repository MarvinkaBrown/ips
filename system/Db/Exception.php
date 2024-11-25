<?php
/**
 * @brief		Exception class for database errors
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Db;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use RuntimeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Exception class for database errors
 */
class Exception extends RuntimeException
{
	/**
	 * @brief	Query
	 */
	public ?string $query = NULL;
	
	/**
	 * @brief	Binds
	 */
	public array $binds = array();

	/**
	 * Constructor
	 *
	 * @param string|null $message MySQL Error message
	 * @param int $code MySQL Error Code
	 * @param mixed $previous Previous Exception
	 * @param mixed|null $query MySQL Query that caused exception
	 * @param array $binds Binds for query
	 * @see        <a href='https://bugs.php.net/bug.php?id=30471'>Recursion "bug" with var_export()</a>
	 */
	public function __construct( string $message=NULL, int $code = 0, mixed $previous=NULL, mixed $query=NULL, array $binds=array() )
	{
		/* Store these for the extraLogData() method */
		$this->query = $query;
		$this->binds = $binds;
				
		return parent::__construct( $message, $code, $previous );
	}
	
	/**
	 * Is this a server issue?
	 *
	 * @return	bool
	 */
	public function isServerError(): bool
	{
		/* Low-end server errors */
		if ( $this->getCode() < 1046 or in_array( $this->getCode(), array( 1129, 1130, 1194, 1195, 1203 ) ) )
		{
			return TRUE;
		}
		
		/* Low-end client errors */
		if ( $this->getCode() >= 2000 and $this->getCode() < 2029 )
		{
			return TRUE;
		}
		
		/* Our custom error code */
		if ( $this->getCode() === -1 )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Additional log data?
	 *
	 * @return	string|null
	 */
	public function extraLogData(): ?string
	{
		return Db::_replaceBinds( $this->query, $this->binds );
	}
}