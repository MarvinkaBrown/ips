<?php

/**
 * @brief        InvoiceListenerType
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        5/23/2023
 */

namespace IPS\Events\ListenerType;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Events\ListenerType;
use IPS\Member as MemberClass;
use IPS\nexus\Invoice as InvoiceClass;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @method onStatusChange( InvoiceClass $invoice, string $status ) : void
 * @method onCreateAccountForGuest( InvoiceClass $invoice, MemberClass $member, array $guestData ) : void
 * @method onCheckout( InvoiceClass $invoice, string $step ) : void
 */
class InvoiceListenerType extends ListenerType
{
	/**
	 * Defines the classes that are supported by each Listener Type
	 * When a new Listener Type is created, we must specify which
	 * classes are valid (e.g. \IPS\Content, \IPS\Member).
	 *
	 * @var array
	 */
	protected static array $supportedBaseClasses = array(
		InvoiceClass::class
	);
}