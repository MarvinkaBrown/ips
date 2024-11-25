<?php
/**
 * @brief		Generate Renewal Invoices Task
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
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Math\Number;
use IPS\nexus\Customer;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Renewal;
use IPS\nexus\Money;
use IPS\nexus\Tax;
use IPS\nexus\Transaction;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Task;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Generate Renewal Invoices Task
 */
class generateRenewalInvoices extends Task
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
	 * @throws    Task\Exception
	 */
	public function execute() : string|null
	{
        /* Get purchases grouped by member and currency */
        $select = $this->_getSelectQuery();
        $log = Db::_replaceBinds( $select->query, $select->binds ) . "\n" . count( $select ) . " matches\n\n";
		$availableTaxes = Tax::roots();

		$groupedPurchases = array();
		foreach ( new ActiveRecordIterator( $select, 'IPS\nexus\Purchase' ) as $purchase )
		{
			/* If the member does not exist, we should not lock the task */
			try
			{
				$groupedPurchases[ $purchase->member->member_id ][ $purchase->renewal_currency ][ $purchase->id ] = $purchase;
			}
			catch( OutOfRangeException )
			{
				/* Set the purchase inactive so we don't try again. */
				$purchase->active = 0;
				$purchase->save();
			}
		}
		
		/* Loop */
		foreach ( $groupedPurchases as $memberId => $currencies )
		{
			$member = Customer::load( $memberId );
			foreach ( $currencies as $currency => $purchases )
			{		
				$log .= "Member {$memberId}, {$currency}: " . count( $purchases ) . " purchase(s) to be renewed: " . implode( ', ', array_keys( $purchases ) ) . ". ";
						
				/* Create Invoice */
				$invoice = new Invoice;
				$invoice->system = TRUE;
				$invoice->currency = $currency;
				$invoice->member = $member;
				$invoice->billaddress = $member->primaryBillingAddress();
				$items = array();
				
				foreach ( $purchases as $purchase )
				{
					/* Check the renewal is valid */
					if( $purchase->canBeRenewed() )
					{
						$items[] = $purchase;
						continue;
					}

					/* Remove renewals for this purchase */
					$log .= "Purchase {$purchase->id} cannot be renewed. ";
					$purchase->renewals = NULL;
					$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'info' => 'remove_renewals' ) );
					$purchase->can_reactivate = TRUE;
					$purchase->save();
				}

				/* Continue to next invoice if no items left */
				if( !count( $items ) )
				{
					continue;
				}

				/* Add items to invoice */
				foreach( $items as $item )
				{
					$invoice->addItem( Renewal::create( $item ) );
				}
				$invoice->save();
				$log .= "Invoice {$invoice->id} generated... ";
				
				/* Try to take payment automatically, but *only* if we have a billing address (i.e. the customer has a primary billing address set)
					otherwise we don't know how we're taxing this and the customer will need to manually come and pay it - we can skip this if tax has not been configured */
				if ( $invoice->billaddress OR count( $availableTaxes ) === 0 )
				{
					/* Nothing to pay? */
					if ( $invoice->amountToPay()->amount->isZero() )
					{
						$log .= "Nothing to pay!";
						
						$extra = $invoice->status_extra;
						$extra['type']		= 'zero';
						$invoice->status_extra = $extra;
						$invoice->markPaid();
					}
	
					/* Charge what we can to account credit */
					if ( $invoice->status !== $invoice::STATUS_PAID )
					{
						$credits = $member->cm_credits;
						if ( isset( $credits[ $currency ] ) )
						{
							$credit = $credits[$currency]->amount;
							if( $credit->isGreaterThanZero() )
							{
								$take = NULL;
								/* If credit is equal or larger than invoice value */
								if ( in_array( $credit->compare( $invoice->total->amount ), [ 0, 1 ] ) )
								{
									$take = $invoice->total->amount;
								}
								else
								{
									/* Only use credit if amount remaining is greater than card gateway min amount */
									if( $invoice->total->amount->subtract( $credit ) > new Number( '0.50' ) )
									{
										$take = $credit;
									}
								}

								if( $take )
								{
									$log .= "{$credit} account credit available... ";

									$transaction = new Transaction;
									$transaction->member = $member;
									$transaction->invoice = $invoice;
									$transaction->amount = new Money( $take, $currency );
									$transaction->extra = array('automatic' => TRUE);
									$transaction->save();
									$transaction->approve();

									$log .= "Transaction {$transaction->id} generated... ";

									$member->log( 'transaction', array(
										'type' => 'paid',
										'status' => Transaction::STATUS_PAID,
										'id' => $transaction->id,
										'invoice_id' => $invoice->id,
										'invoice_title' => $invoice->title,
										'automatic' => TRUE,
									), FALSE );

									$credits[$currency]->amount = $credits[$currency]->amount->subtract( $take );
									$member->cm_credits = $credits;
									$member->save();

									$invoice->status = $transaction->invoice->status;
								}

							}
						}
					}
					/* Charge to card */
					if ( $invoice->status !== $invoice::STATUS_PAID )
					{
                        /* Figure out which payment methods are allowed in this invoice */
                        $allowedPaymentMethods = array();
                        foreach( $invoice->items as $item )
                        {
                            if( is_array( $item->paymentMethodIds ) and !in_array( '*', $item->paymentMethodIds ) )
                            {
                                $allowedPaymentMethods = array_merge( $allowedPaymentMethods, $item->paymentMethodIds );
                            }
                        }

						/* Check all available gateways */
						if( empty( $allowedPaymentMethods ) )
						{
							foreach( Gateway::roots() as $gateway )
							{
								$allowedPaymentMethods[] = $gateway->_id;
							}
						}

						/* Loop through each payment method and try to take payment */
						foreach( $allowedPaymentMethods as $paymentMethodId )
						{
							try
							{
								$gateway = Gateway::load( $paymentMethodId );
								if( $gateway::SUPPORTS_AUTOPAY )
								{
									foreach( $gateway->autopay( $invoice ) as $transaction )
									{
										if( $transaction->status == Transaction::STATUS_REFUSED )
										{
											$log .= "Transaction {$transaction->id} failed. ";
										}
										else
										{
											$log .= "Transaction {$transaction->id} approved! ";
										}

										$invoice->status = $transaction->invoice->status;
									}
								}
							}
							catch( OutOfRangeException ){}
						}
					}
				}
				
				/* Update the purchase */
				if ( $invoice->status !== $invoice::STATUS_PAID )
				{					
					foreach ( $purchases as $purchase )
					{
						$purchase->invoice_pending = $invoice;
						$purchase->save();
					}
				}
			
				/* Send notification */
				$invoice->sendNotification();
				$log .= "Final status: {$invoice->status}\n";
			}
		}
						
		return $log;
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

	/**
	 * Get Purchases Query
	 *
	 * @return Select
	 * @throws Exception
	 */
	protected function _getSelectQuery(): Select
	{
		/* What's out cutoff? */
		$renewalDate = DateTime::create();
		if( Settings::i()->cm_invoice_generate )
		{
			$renewalDate->add( new DateInterval( 'PT' . Settings::i()->cm_invoice_generate . 'H' )  );
		}

		return Db::i()->select( 'ps.*', [ 'nexus_purchases', 'ps' ],
			[
				"ps_cancelled=0 AND ps_renewals>0 AND ps_invoice_pending=0 AND ps_active=1 AND ps_expire>0 AND ps_expire<? AND (ps_billing_agreement IS NULL OR ba.ba_canceled=1) AND ( ps_grouped_renewals='' OR ps_grouped_renewals IS NULL )",
				$renewalDate->getTimestamp()
			], 'ps_member', 50 )
			->join( [ 'nexus_billing_agreements', 'ba' ], 'ps.ps_billing_agreement=ba.ba_id' );
	}
}