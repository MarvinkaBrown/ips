<?php
/**
 * @brief		Exception class for email errors
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Aug 2013
 */

namespace IPS\Email\Outgoing;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use RuntimeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Exception class for email errors
 */
class Exception extends RuntimeException
{
	/**
	 * @brief Extra details for log
	 */
	public ?array $extraDetails	= array();
	public ?string $messageKey = NULL;

	/**
	 * Constructor
	 *
	 * @param string|null $message Error message
	 * @param int $code Error Code
	 * @param Exception|NULL $previous Previous Exception
	 * @param array|null $extra Extra details for log
	 */
	public function __construct( string $message = null, int $code = 0, \Exception|null $previous = null, array $extra=NULL )
	{
		/* Store these for the extraLogData() method */
		$this->extraDetails = $extra;
		$this->messageKey = $message;

		$message = Member::loggedIn()->language()->addToStack( $message, FALSE, array( 'sprintf' => $extra ) );

		return parent::__construct( $message, $code, $previous );
	}
}