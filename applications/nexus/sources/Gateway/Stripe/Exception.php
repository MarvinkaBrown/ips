<?php
/**
 * @brief		Stripe Exception
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Mar 2014
 */

namespace IPS\nexus\Gateway\Stripe;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Dispatcher;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stripe Exception
 */
class Exception extends DomainException
{
	/**
	 * @brief	Details
	 */
	public array $details = [];
	
	/**
	 * Constructor
	 *
	 * @param	array	$response	Error details
	 * @return	void
	 */
	public function __construct( array $response )
	{
		$this->details = $response;
		if ( $response['type'] == 'card_error' or !Dispatcher::hasInstance() )
		{
			parent::__construct( $response['message'] );
		}
		else
		{
			parent::__construct( Member::loggedIn()->language()->get( 'gateway_err' ) );
		}
	}
}