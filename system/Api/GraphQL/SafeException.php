<?php
/**
 * @brief		Safe GraphQL Exception (i.e. client can be informed of the error message)
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 Dec 2015
 */

namespace IPS\Api\GraphQL;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

use Exception;
use GraphQL\Error\ClientAware;
use function defined;

/**
 * API Exception
 */
class SafeException extends Exception implements ClientAware
{
	/**
	 * @brief	Exception code
	 */
	public ?string $exceptionCode = null;
	
	/**
	 * @brief	OAUth Error
	 */
	public ?string $oauthError = null;
	
	/**
	 * Constructor
	 *
	 * @param	string	$message	Error Message
	 * @param	string	$code		Code
	 * @param	int		$httpCode	HTTP Error code
	 * @return	void
	 */
	public function __construct( string $message, string $code, int $httpCode )
	{
		$this->exceptionCode = $code;
		parent::__construct( $message, $httpCode );
	}

	public function getCategory() : string
	{
		return 'clienterror';
	}

	public function isClientSafe() : bool
	{
		return true;
	}
}