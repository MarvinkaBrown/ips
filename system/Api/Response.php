<?php
/**
 * @brief		API Response
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 Dec 2015
 */

namespace IPS\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * API Response
 */
class Response
{
	/**
	 * @brief	HTTP Response Code
	 */
	public int $httpCode;
	
	/**
	 * @brief	Data
	 */
	protected mixed $data = null;

	/**
	 * Constructor
	 *
	 * @param	int		$httpCode	HTTP Response code
	 * @param	mixed	$data		Data to return
	 * @return	void
	 */
	public function __construct( int $httpCode, mixed $data )
	{
		$this->httpCode = $httpCode;
		$this->data = $data;
	}
	
	/**
	 * Data to output
	 *
	 * @return	mixed
	 */
	public function getOutput() : mixed
	{
		return $this->data;
	}
}