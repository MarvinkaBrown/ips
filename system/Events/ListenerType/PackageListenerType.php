<?php

/**
 * @brief        PackageListenerType
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        5/22/2023
 */

namespace IPS\Events\ListenerType;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Events\ListenerType;
use IPS\Member as MemberClass;
use IPS\nexus\Invoice as InvoiceClass;
use IPS\nexus\Invoice\Item as InvoiceItem;
use IPS\nexus\Package as PackageClass;
use IPS\nexus\Purchase as PurchaseClass;
use IPS\nexus\Purchase\RenewalTerm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @method onCancel( InvoiceItem $item, PurchaseClass $purchase ) : void
 * @method onChange( InvoiceItem $item, PurchaseClass $purchase, PackageClass $newPackage, int|RenewalTerm $chosenRenewalOption = NULL ) : void
 * @method onDelete( InvoiceItem $item, PurchaseClass $purchase ) : void
 * @method onExpireWarning( InvoiceItem $item, PurchaseClass $purchase ) : void
 * @method onExpire( InvoiceItem $item, PurchaseClass $purchase ) : void
 * @method onInvoiceCancel( InvoiceItem $item, InvoiceClass $invoice ) : void
 * @method onPaid( InvoiceItem $item, InvoiceClass $invoice ) : void
 * @method onPurchaseGenerated( InvoiceItem $item, PurchaseClass $purchase, InvoiceClass $invoice ) : void
 * @method onReactivate( InvoiceItem $item, PurchaseClass $purchase ) : void
 * @method onRenew( InvoiceItem $item, PurchaseClass $purchase, int $cycles ) : void
 * @method onTransfer( InvoiceItem $item, PurchaseClass $purchase, MemberClass $newCustomer ) : void
 * @method onUnpaid( InvoiceItem $item, InvoiceClass $invoice, string $status ) : void
 */
class PackageListenerType extends ListenerType
{
	/**
	 * @brief	Determine whether this listener requires an explicitly set class
	 * 			Example: MemberListeners are always for \IPS\Member, but ContentListeners
	 * 			will require a specific class.
	 * @var bool
	 */
	public static bool $requiresClassDeclaration = TRUE;

	/**
	 * Defines the classes that are supported by each Listener Type
	 * When a new Listener Type is created, we must specify which
	 * classes are valid (e.g. \IPS\Content, \IPS\Member).
	 *
	 * @var array
	 */
	protected static array $supportedBaseClasses = array(
		InvoiceItem::class
	);
}