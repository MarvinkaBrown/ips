<?php
/**
 * @brief		MemberHistory: Nexus
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		24 Jan 2017
 */

namespace IPS\nexus\extensions\core\MemberHistory;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadFunctionCallException;
use DateInterval;
use IPS\Application;
use IPS\downloads\File;
use IPS\Extensions\MemberHistoryAbstract;
use IPS\GeoLocation;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Payout;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Subscription\Package;
use IPS\nexus\Transaction;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member History: Nexus
 */
class Nexus extends MemberHistoryAbstract
{
	/**
	 * Return the valid member history log types
	 *
	 * @return array
	 */
	public function getTypes(): array
	{
		return array(
			'invoice',
			'transaction',
			'purchase',
			'comission',
			'giftvoucher',
			'info',
			'address',
			'card',
			'alternative',
			'payout',
			'lkey',
			'download',
			'custom',
			'billingagreement',
			'note',
			'subscription'
		);
	}

	/**
	 * Parse LogData column
	 *
	 * @param	string		$value		column value
	 * @param	array		$row		entire log row
	 * @return	string
	 */
	public function parseLogData( string $value, array $row ): string
	{
		$val = json_decode( $value, TRUE );

		$byCustomer = '';
		$byStaff = '';
		if ( $row['log_by'] )
		{
			if ( $row['log_by'] === $row['log_member'] )
			{
				$byCustomer = Member::loggedIn()->language()->addToStack('history_by_customer');
			}

			$byStaff = Member::loggedIn()->language()->addToStack('history_by_staff', FALSE, array( 'sprintf' => array( Member::load( $row['log_by'] )->name ) ) );
		}

		switch ( $row['log_type'] )
		{
			case 'invoice':
				try
				{
					$invoice = Theme::i()->getTemplate('invoices', 'nexus')->link( Invoice::load( $val['id'] ) );
				}
				catch ( OutOfRangeException )
				{
					$invoice = $val['title'];
				}

				if ( isset( $val['type'] ) )
				{
					switch ( $val['type'] )
					{
						case 'status':
							return Member::loggedIn()->language()->addToStack( 'history_invoice_status', FALSE, array( 'htmlsprintf' => array( $invoice, mb_strtolower( Member::loggedIn()->language()->addToStack( 'istatus_' . $val['new'] ) ), $byStaff ) ) );

						case 'resend':
							return Member::loggedIn()->language()->addToStack( 'history_invoice_resend', FALSE, array( 'htmlsprintf' => array( $invoice, $byStaff, isset( $val['email'] ) ? Member::loggedIn()->language()->addToStack( $val['email'] ? 'history_invoice_resend_email' : 'history_invoice_resend_no_email' ) : '' ) ) );

						case 'delete':
							return Member::loggedIn()->language()->addToStack( 'history_invoice_delete', FALSE, array( 'htmlsprintf' => array( $invoice, $byStaff ) ) );

						case 'expire':
							return Member::loggedIn()->language()->addToStack( 'history_invoice_expired', FALSE, array( 'htmlsprintf' => array( $invoice ) ) );
					}
				}
				else
				{
					if ( isset( $val['system'] ) and $val['system'] )
					{
						return Member::loggedIn()->language()->addToStack( 'history_invoice_generated', FALSE, array( 'htmlsprintf' => array( $invoice, '' ) ) );
					}
					else
					{
						return Member::loggedIn()->language()->addToStack( 'history_invoice_generated', FALSE, array( 'htmlsprintf' => array( $invoice, $byCustomer ?: $byStaff ) ) );
					}
				}
				break;

			case 'transaction':
				try
				{
					$transaction = Theme::i()->getTemplate('transactions', 'nexus')->link( Transaction::load( $val['id'] ) );
				}
				catch ( OutOfRangeException )
				{
					$transaction = Member::loggedIn()->language()->addToStack( 'transaction_number', FALSE, array( 'htmlsprintf' => array( $val['id'] ) ) );
				}

				switch ( $val['type'] )
				{
					case 'paid':

						try
						{
							$invoice = Theme::i()->getTemplate('invoices', 'nexus')->link( Invoice::load( $val['invoice_id'] ), TRUE );
						}
						catch ( OutOfRangeException )
						{
							$invoice = Member::loggedIn()->language()->addToStack( 'invoice_number', FALSE, array( 'sprintf' => array( $val['id'] ) ) );
						}

						if ( isset( $val['automatic'] ) and $val['automatic'] )
						{
							return Member::loggedIn()->language()->addToStack( 'history_transaction_auto', FALSE, array( 'htmlsprintf' => array( $transaction, $invoice, Member::loggedIn()->language()->addToStack( 'history_transaction_status_' . $val['status'] ) ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_transaction_paid', FALSE, array( 'htmlsprintf' => array( $transaction, $invoice, $byStaff, Member::loggedIn()->language()->addToStack( 'history_transaction_status_' . $val['status'] ) ) ) );
						}
						
					case 'status':
						if ( $val['status'] === Transaction::STATUS_REFUNDED or $val['status'] === Transaction::STATUS_PART_REFUNDED )
						{
							if ( $val['refund'] === 'gateway' )
							{
								if ( isset( $val['amount'] ) and $val['amount'] )
								{
									return Member::loggedIn()->language()->addToStack( 'history_transaction_part_refunded', FALSE, array( 'htmlsprintf' => array( new Money( $val['amount'], $val['currency'] ), $transaction, $byStaff ) ) );
								}
								else
								{
									return Member::loggedIn()->language()->addToStack( 'history_transaction_refunded', FALSE, array( 'htmlsprintf' => array( $transaction, $byStaff ) ) );
								}
							}
							else
							{
								if ( isset( $val['amount'] ) and $val['amount'] )
								{
									return Member::loggedIn()->language()->addToStack( 'history_transaction_part_credited', FALSE, array( 'htmlsprintf' => array( new Money( $val['amount'], $val['currency'] ), $transaction, $byStaff ) ) );
								}
								else
								{
									return Member::loggedIn()->language()->addToStack( 'history_transaction_credited', FALSE, array( 'htmlsprintf' => array( $transaction, $byStaff ) ) );
								}
							}
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_transaction_status', FALSE, array( 'htmlsprintf' => array( $transaction, Member::loggedIn()->language()->addToStack( 'history_transaction_status_' . $val['status'] ), $byStaff ) ) );
						}

					case 'undo_credit':
						return Member::loggedIn()->language()->addToStack( 'history_transaction_undo_credit', FALSE, array( 'htmlsprintf' => array( new Money( $val['amount'], $val['currency'] ), $transaction, $byStaff ) ) );

					case 'delete':
						return Member::loggedIn()->language()->addToStack( 'history_transaction_delete', FALSE, array( 'htmlsprintf' => array( $transaction, $byStaff ) ) );
				}
				break;

			case 'purchase':
				try
				{
					$purchase = Theme::i()->getTemplate('purchases', 'nexus')->link( Purchase::load( $val['id'] ), $val['type'] === 'change' );
				}
				catch ( OutOfRangeException )
				{
					$purchase = $val['name'];
				}

				switch ( $val['type'] )
				{
					case 'new':
					case 'renew':
						try
						{
							$invoice = Theme::i()->getTemplate('invoices', 'nexus')->link( Invoice::load( $val['invoice_id'] ), TRUE );
						}
						catch ( OutOfRangeException )
						{
							$invoice = Member::loggedIn()->language()->addToStack('invoice_number', FALSE, array( 'sprintf' => array( $val['invoice_id'] ) ) );
						}

						if ( $val['type'] === 'new' )
						{
							return Member::loggedIn()->language()->addToStack( 'history_purchase_created', FALSE, array( 'htmlsprintf' => array( $purchase, $invoice ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_purchase_renewed', FALSE, array( 'htmlsprintf' => array( $purchase, $invoice ) ) );
						}

					case 'info':
						if ( isset( $val['info'] ) )
						{
							switch ( $val['info'] )
							{
								case 'change_renewals':
									/* A bug in an older version logged this wrong... */
									if ( !isset( $val['to']['currency'] ) )
									{
										foreach ( $val['to']['cost'] as $currency => $amount )
										{
											$to = new RenewalTerm( new Money( $amount['amount'], $currency ), new DateInterval( 'P' . $val['to']['term'] . mb_strtoupper( $val['to']['unit'] ) ) );
											break;
										}
									}
									/* This is the correct way */
									else
									{
										$to = new RenewalTerm( new Money( $val['to']['cost'], $val['to']['currency'] ), new DateInterval( 'P' . $val['to']['term']['term'] . mb_strtoupper( $val['to']['term']['unit'] ) ) );
									}
									return Member::loggedIn()->language()->addToStack( 'history_purchase_renewals_changed', FALSE, array( 'htmlsprintf' => array( $purchase, ( isset( $val['system'] ) and $val['system'] ) ? '' : $byStaff, $to ?? '' ) ) );

								case 'remove_renewals':
									return Member::loggedIn()->language()->addToStack( 'history_purchase_renewals_removed', FALSE, array( 'htmlsprintf' => array( $purchase, ( isset( $val['system'] ) and $val['system'] ) ? '' : $byStaff ) ) );
								
								case 'never_expire':
									return Member::loggedIn()->language()->addToStack( 'history_purchase_never_expire', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
								
								case 'restored_expire':
									return Member::loggedIn()->language()->addToStack( 'history_purchase_restored_expire', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
							}
						}
						return Member::loggedIn()->language()->addToStack( 'history_purchase_edited', FALSE, array( 'htmlsprintf' => array( $purchase, ( isset( $val['system'] ) and $val['system'] ) ? '' : $byStaff ) ) );

					case 'transfer_from':
						try
						{
							$to = Theme::i()->getTemplate('global', 'nexus')->userLink( Customer::load( $val['to'] ) );
						}
						catch( OutOfRangeException )
						{
							$to = Member::loggedIn()->language()->addToStack( 'deleted_member' );
						}

						return Member::loggedIn()->language()->addToStack( 'history_purchase_transfer_from', FALSE, array( 'htmlsprintf' => array( $purchase, $to, $byStaff ) ) );

					case 'transfer_to':
						try
						{
							$from = Theme::i()->getTemplate('global', 'nexus')->userLink( Customer::load( $val['from'] ) );
						}
						catch( OutOfRangeException )
						{
							$from = Member::loggedIn()->language()->addToStack( 'deleted_member' );
						}

						return Member::loggedIn()->language()->addToStack( 'history_purchase_transfer_to', FALSE, array( 'htmlsprintf' => array( $purchase, $from, $byStaff ) ) );

					case 'cancel':
						if( isset( $val['by'] ) AND $val['by'] == 'api' )
						{
							return Member::loggedIn()->language()->addToStack( 'history_purchase_canceled_api', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_purchase_canceled', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
						}
					case 'uncancel':
						return Member::loggedIn()->language()->addToStack( 'history_purchase_reactivated', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );

					case 'delete':
						return Member::loggedIn()->language()->addToStack( 'history_purchase_deleted', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );

					case 'expire':
						return Member::loggedIn()->language()->addToStack( 'history_purchase_expired', FALSE, array( 'htmlsprintf' => array( $purchase ) ) );

					case 'change':
						$by = ( isset( $val['system'] ) and $val['system'] ) ? '' : $byCustomer;
						return Member::loggedIn()->language()->addToStack( 'history_purchase_changed', FALSE, array( 'htmlsprintf' => array( $purchase, $val['old'], $val['name'], $by ) ) );

					case 'group':
						return Member::loggedIn()->language()->addToStack( 'history_purchase_grouped', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
					case 'ungroup':
						return Member::loggedIn()->language()->addToStack( 'history_purchase_ungrouped', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
				}
				break;

			case 'comission':

				switch ( $val['type'] )
				{
					case 'purchase':
					case 'purchase_refund':
						try
						{
							$purchase = Theme::i()->getTemplate('purchases', 'nexus')->link( Purchase::load( $val['id'] ) );
						}
						catch ( OutOfRangeException )
						{
							$purchase = $val['name'];
						}

						return Member::loggedIn()->language()->addToStack( "history_commission_{$val['type']}", FALSE, array( 'htmlsprintf' => array( new Money( $val['amount'], $val['currency'] ), $purchase ) ) );

					case 'invoice':
					case 'invoice_refund':
						try
						{
							$invoice = Theme::i()->getTemplate('invoices', 'nexus')->link( Invoice::load( $val['invoice_id'] ), TRUE );
						}
						catch ( OutOfRangeException )
						{
							$invoice = Member::loggedIn()->language()->addToStack('invoice_number', FALSE, array( 'sprintf' => array( $val['invoice_id'] ) ) );
						}

						return Member::loggedIn()->language()->addToStack( "history_commission_{$val['type']}", FALSE, array( 'htmlsprintf' => array( new Money( $val['amount'], $val['currency'] ), $invoice ) ) );

					case 'bought':
						try
						{
							$invoice = Theme::i()->getTemplate('invoices', 'nexus')->link( Invoice::load( $val['invoice_id'] ), TRUE );
						}
						catch ( OutOfRangeException )
						{
							$invoice = Member::loggedIn()->language()->addToStack( 'invoice_number', FALSE, array( 'sprintf' => array( $val['invoice_id'] ) ) );
						}
						return Member::loggedIn()->language()->addToStack( 'history_commission_bought', FALSE, array( 'htmlsprintf' => array( new Money( $val['new_amount'], $val['currency'] ), $invoice, new Money( $val['amount'], $val['currency'] ) ) ) );

					case 'manual':
						return Member::loggedIn()->language()->addToStack( 'history_commission_manual', FALSE, array( 'sprintf' => array( $byStaff, new Money( $val['new'], $val['currency'] ), new Money( $val['old'], $val['currency'] ) ) ) );
				}
				break;

			case 'giftvoucher':
				switch ( $val['type'] )
				{
					case 'used':
						/* If the customer who used this gift card no longer exists, then we need to load up a guest customer object to avoid an OutOfRangeException */
						try
						{
							$customer = Customer::load( $val['by'] );
						}
						catch( OutOfRangeException )
						{
							$customer = new Customer;
						}

						$currency = $val['currency'] ?? $customer->defaultCurrency();

						return
							Member::loggedIn()->language()->addToStack( 'history_giftvoucher_used', FALSE, array( 'htmlsprintf' => array(
							new Money( $val['amount'], $currency ),
							$val['code'],
							Theme::i()->getTemplate('global', 'nexus')->userLink( $customer )
						) ) );
						break;

					case 'redeemed':
						/* If the customer who redeemed this gift card no longer exists, then we need to load up a guest customer object to avoid an OutOfRangeException */
						try
						{
							$customer = Customer::load( $val['ps_member'] );
						}
						catch( OutOfRangeException )
						{
							$customer = new Customer;
						}

						$currency = $val['currency'] ?? $customer->defaultCurrency();

						return
							Member::loggedIn()->language()->addToStack( 'history_giftvoucher_redeemed', FALSE, array( 'htmlsprintf' => array(
							new Money( $val['amount'], $currency ),
							$val['code'],
							Theme::i()->getTemplate('global', 'nexus')->userLink( $customer ),
							new Money( $val['newCreditAmount'], $currency )
						) ) );
						break;
				}
				break;

			case 'info':
				$changes = array( Member::loggedIn()->language()->addToStack('history_info_change', FALSE, array( 'sprintf' => array( $byCustomer ?: $byStaff ) ) ) );

				if ( isset( $val['name'] ) )
				{
					$name = is_array( $val['name'] ) ? implode( ' ', array_values( $val['name'] ) ) : $val['name'];
					$changes[] =  Member::loggedIn()->language()->addToStack('history_name_changed_from', FALSE, array( 'sprintf' => array( $name ) ) );
				}

				if ( isset( $val['other'] ) )
				{
					foreach ( $val['other'] as $change )
					{
						/* Older versions may not have stored the display value, so we need to account for that */
						if( mb_strpos( $change['name'], 'nexus_ccfield_' ) !== FALSE AND is_array( $change['value'] ) )
						{
							try
							{
								/* If it's an array, we will start first by assuming it is probably an address */
								$value = GeoLocation::buildFromJson( json_encode( $change['value'] ) )->toString();

								/* A bad address will return an empty string */
								if( !$value )
								{
									throw new BadFunctionCallException;
								}

								$change['value'] = $value;
							}
							/* Maybe it wasn't an address or geoip support is disabled */
							catch( BadFunctionCallException )
							{
								$_value = array();

								/* We will just loop and implode so we have a string */
								foreach( $change['value'] as $k => $v )
								{
									if( is_array( $v ) )
									{
										foreach( $v as $_k => $_v )
										{
											$_value[] = $_k . ': ' . $_v;
										}
									}
									else
									{
										$_value[] = $k . ': ' . $v;
									}
								}

								$value = implode( ', ', $_value );

								$change['value'] = htmlspecialchars( $value, ENT_DISALLOWED, 'UTF-8', FALSE );
							}
						}

						if ( isset( $change['old'] ) )
						{
							$changes[] = Member::loggedIn()->language()->addToStack('history_field_changed_from', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $change['name'] ), $change['old'], $change['value'] ) ) );
						}
						else
						{
							$changes[] = Member::loggedIn()->language()->addToStack('history_field_changed', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $change['name'] ), $change['value'] ) ) );
						}
					}
				}

				return implode( '<br>', $changes );

			case 'address':
				switch ( $val['type'] )
				{
					case 'add':
						return Member::loggedIn()->language()->addToStack('history_address_add', FALSE, array( 'sprintf' => array( (string) GeoLocation::buildFromJson( $val['details'] ), $byCustomer ) ) );
					case 'edit':
						return Member::loggedIn()->language()->addToStack('history_address_edit', FALSE, array( 'sprintf' => array( (string) GeoLocation::buildFromJson( $val['old'] ), (string) GeoLocation::buildFromJson( $val['new'] ), $byCustomer ) ) );
					case 'primary_billing':
						return Member::loggedIn()->language()->addToStack('history_address_primary_billing', FALSE, array( 'sprintf' => array( (string) GeoLocation::buildFromJson( $val['details'] ), $byCustomer ) ) );
					case 'delete':
						return Member::loggedIn()->language()->addToStack('history_address_delete', FALSE, array( 'sprintf' => array( (string) GeoLocation::buildFromJson( $val['details'] ), $byCustomer ) ) );
				}
				break;


			case 'card':
				switch ( $val['type'] )
				{
					case 'add':
						return Member::loggedIn()->language()->addToStack('history_card_add', FALSE, array( 'sprintf' => array( $val['number'], $byCustomer ) ) );
					case 'delete':
						return Member::loggedIn()->language()->addToStack('history_card_delete', FALSE, array( 'sprintf' => array( $val['number'], $byCustomer ) ) );
				}
				break;

			case 'alternative':
				try
				{
					$altContact = Customer::load( $val['alt_id'] );
				}
				catch( OutOfRangeException )
				{
					$altContact = new Customer;
				}

				$altContact = $altContact->member_id ? $altContact->link() : htmlspecialchars( $val['alt_name'], ENT_DISALLOWED, 'UTF-8', FALSE );

				switch ( $val['type'] )
				{
					case 'add':
						return Member::loggedIn()->language()->addToStack('history_altcontact_add', FALSE, array( 'htmlsprintf' => array( $altContact, $byCustomer ) ) );
					case 'edit':
						return Member::loggedIn()->language()->addToStack('history_altcontact_edit', FALSE, array( 'htmlsprintf' => array( $altContact, $byCustomer ) ) );
					case 'delete':
						return Member::loggedIn()->language()->addToStack('history_altcontact_delete', FALSE, array( 'htmlsprintf' => array( $altContact, $byCustomer ) ) );
				}
				break;

			case 'payout':

				try
				{
					if ( !isset( $val['payout_id'] ) )
					{
						throw new OutOfRangeException;
					}

					$payout = Theme::i()->getTemplate('payouts', 'nexus')->link( Payout::load( $val['payout_id'] ) );
				}
				catch ( OutOfRangeException )
				{
					if ( isset( $val['currency'] ) )
					{
						$payout = new Money( new Number( (string)$val['amount'] ), $val['currency'] );
					}
					else
					{
						$payout = $val['amount'];
					}
				}

				switch ( $val['type'] )
				{
					case 'autoprocess':
						return Member::loggedIn()->language()->addToStack('history_payout_autoprocess', FALSE, array( 'htmlsprintf' => array( $payout ) ) );
					case 'request':
						return Member::loggedIn()->language()->addToStack('history_payout_request', FALSE, array( 'htmlsprintf' => array( $payout ) ) );
					case 'cancel':
						return Member::loggedIn()->language()->addToStack('history_payout_cancel', FALSE, array( 'htmlsprintf' => array( $payout, $byCustomer ?: $byStaff ) ) );
					case 'processed':
						return Member::loggedIn()->language()->addToStack('history_payout_processed', FALSE, array( 'htmlsprintf' => array( $payout, $byStaff ) ) );
					case 'dismissed':
						return Member::loggedIn()->language()->addToStack('history_payout_dismissed', FALSE, array( 'htmlsprintf' => array( $payout, $byStaff ) ) );
				}
				break;

			case 'lkey':

				try
				{
					$purchase = Theme::i()->getTemplate('purchases', 'nexus')->link( Purchase::load( $val['ps_id'] ) );
				}
				catch ( OutOfRangeException )
				{
					if ( isset( $val['ps_name'] ) )
					{
						$purchase = $val['ps_name'];
					}
					else
					{
						$purchase = Member::loggedIn()->language()->addToStack( 'purchase_number', FALSE, array( 'sprintf' => array( $val['ps_id'] ) ) );
					}
				}

				switch ( $val['type'] )
				{
					case 'activated':
						return Member::loggedIn()->language()->addToStack('history_lkey_activated', FALSE, array( 'htmlsprintf' => array( $purchase ) ) );
					case 'reset':
						return Member::loggedIn()->language()->addToStack('history_lkey_reset', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff, $val['new'], $val['key'] ) ) );
				}

				break;

			case 'download':

				switch ( $val['type'] )
				{
					case 'idm':
						try
						{
							if ( !Application::appIsEnabled('downloads') )
							{
								throw new OutOfRangeException;
							}
							
							$options = array( 'htmlsprintf' => array( Theme::i()->getTemplate( 'customers', 'nexus' )->downloadsLink( File::load( $val['id'] ) ) ) );
						}
						catch ( OutOfRangeException )
						{
							$options = array( 'sprintf' => array( $val['name'] ) );
						}
						return Member::loggedIn()->language()->addToStack( 'history_download', FALSE, $options );
					case 'attach':

						$file = Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedFile( Settings::i()->base_url . "applications/core/interface/file/attachment.php?id=" . $val['id'], $val['name'], FALSE );
						if ( isset( $val['ps_id'] ) and $val['ps_id'] )
						{
							try
							{
								$options = array( 'htmlsprintf' => array( $file, Theme::i()->getTemplate('purchases', 'nexus')->link( Purchase::load( $val['ps_id'] ) ) ) );
							}
							catch ( OutOfRangeException )
							{
								$options = array( 'htmlsprintf' => array( $file, htmlspecialchars( $val['ps_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) ) );
							}
							return Member::loggedIn()->language()->addToStack( 'history_download_with_purchase', FALSE, $options );
						}

						return Member::loggedIn()->language()->addToStack( 'history_download', FALSE, array( 'htmlsprintf' => $file ) );
				}
				break;

			case 'billingagreement':
				return Member::loggedIn()->language()->addToStack( 'history_billingagreement_' . $val['type'], FALSE, array( 'sprintf' => array( $val['id'], $val['gw_id'], $byCustomer ?: $byStaff ) ) );
				break;

			case 'note':
				return Member::loggedIn()->language()->addToStack( 'history_' . $val . '_note', FALSE, array( 'sprintf' => $byStaff ) );
				break;

			case 'custom':
				return $val['message'];
				break;
			case 'subscription':
				if ( isset( $val['type'] ) )
				{
					switch( $val['type'] )
					{
						case 'cancelrenewals':
							try
							{
								return Member::loggedIn()->language()->addToStack( 'nexus_history_cancelrenewals_with_name', FALSE, array( 'sprintf' => array( Package::load( $val['id'] )->_title, $byCustomer ) ) );
							}
							catch ( OutOfRangeException )
							{
								return Member::loggedIn()->language()->addToStack( 'nexus_history_cancelrenewals', FALSE, array( 'sprintf' => array( $byCustomer ) ) );
							}
						break;
						
						case 'change':
							try
							{
								return Member::loggedIn()->language()->addToStack( 'nexus_history_subscription_changed_with_name', FALSE, array( 'sprintf' => array( $val['old'], ( isset( $val['system'] ) and $val['system'] ) ? '' : $byStaff, $val['name'] ) ) );
							}
							catch ( OutOfRangeException )
							{
								return Member::loggedIn()->language()->addToStack( 'nexus_history_subscription_changed', FALSE, array( 'sprintf' => array( ( isset( $val['system'] ) and $val['system'] ) ? '' : $byStaff ) ) );
							}
						break;
					}
				}
				break;
		}

		return '';
	}

	/**
	 * Parse LogMember column
	 *
	 * @param string $value		column value
	 * @param array $row		entire log row
	 * @return	string
	 */
	public function parseLogMember( string $value, array $row ): string
	{
		return Customer::load( $value )->link();
	}

	/**
	 * Parse LogType column
	 *
	 * @param	string		$value		column value
	 * @param	array		$row		entire log row
	 * @return	string
	 */
	public function parseLogType( string $value, array $row ): string
	{
		return Theme::i()->getTemplate( 'customers', 'nexus' )->logType( $value );
	}
}