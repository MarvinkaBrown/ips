<?php
/**
 * @brief		Expire Invoices Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		01 Apr 2014
 */

namespace IPS\nexus\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Email;
use IPS\Member;
use IPS\nexus\Invoice;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Task;
use IPS\Task\Exception;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Expire Invoices Task
 */
class expireInvoices extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	string|null	Message to log or NULL
	 * @throws	Exception
	 */
	public function execute() : string|null
	{
        $expireDate = DateTime::create();
        if( Settings::i()->cm_invoice_expireafter )
        {
            $expireDate->sub( new DateInterval( 'P' . Settings::i()->cm_invoice_expireafter . 'D' )  );
        }
		else
		{
			// they don't expire so return only NULL
			return NULL;
		}

		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_invoices', array( 'i_status=? AND i_date<?', Invoice::STATUS_PENDING, $expireDate->getTimestamp() ), 'i_date ASC', 100 ), 'IPS\nexus\Invoice' ) as $invoice )
		{
			$invoice->status = $invoice::STATUS_EXPIRED;
			$invoice->save();

			/* Send tracked notifications */
			foreach ( Db::i()->select( 'member_id', 'nexus_invoice_tracker', array( 'invoice_id=?', $invoice->id ) ) as $trackingMember )
			{
				$trackingMember = Member::load( $trackingMember );
				Email::buildFromTemplate( 'nexus', 'invoiceTrackExpired', array( $invoice, $invoice->summary() ), Email::TYPE_LIST )
					->send( $trackingMember );
			}

            try
            {
                $invoice->member->log( 'invoice', array(
                    'type'	=> 'expire',
                    'id'	=> $invoice->id,
                    'title'	=> $invoice->title,
                ), FALSE );
            }
            catch ( OutOfRangeException ) {}
		}

		return null;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup() : void
	{
		
	}
}