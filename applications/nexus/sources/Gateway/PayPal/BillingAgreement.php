<?php
/**
 * @brief		PayPal Billing Agreement
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		16 Dec 2015
 */

namespace IPS\nexus\Gateway\PayPal;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\nexus\Customer\BillingAgreement as NexusBillingAgreement;
use IPS\nexus\Money;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Transaction;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PayPal Billing Agreement
 */
class BillingAgreement extends NexusBillingAgreement
{
	/**
	 * Get status
	 *
	 * @return	string	See STATUS_* constants
	 * @throws    \IPS\nexus\Gateway\PayPal\Exception
	 */
	public function status() : string
	{
		$data = $this->_getData();

		switch ( mb_strtoupper( $data['status'] ?? $data['state'] ) )
		{
			case 'ACTIVE':
			case 'PENDING':
			case 'APPROVAL_PENDING':
			case 'APPROVED':
			case 'REACTIVATE':
				return static::STATUS_ACTIVE;
				break;
			case 'SUSPEND':
			case 'SUSPENDED':
				return static::STATUS_SUSPENDED;
				break;
			case 'EXPIRED':
			case 'CANCEL':
			case 'CANCELLED':
				return static::STATUS_CANCELED;
				break;
		}

		return '';
	}
	
	/**
	 * Get term
	 *
	 * @return	RenewalTerm
	 * @throws    \IPS\nexus\Gateway\PayPal\Exception
	 */
	public function term() : RenewalTerm
	{
		$data = $this->_getData();
		
		if ( isset( $data['plan_id'] ) )
		{
			$plan = $this->method->api( "billing/plans/{$data['plan_id']}", NULL, 'get' );
			$cycle = array_pop( $plan['billing_cycles'] );

			return new RenewalTerm(
				new Money( $cycle['pricing_scheme']['fixed_price']['value'], $cycle['pricing_scheme']['fixed_price']['currency_code'] ),
				new DateInterval( 'P' . $cycle['frequency']['interval_count'] . mb_substr( $cycle['frequency']['interval_unit'], 0, 1 ) )
			);
		}
		else
		{
			$amount = $data['plan']['payment_definitions'][0]['amount']['value'];
			if ( isset( $data['plan']['payment_definitions'][0]['charge_models'][0] ) )
			{
				$amount += $data['plan']['payment_definitions'][0]['charge_models'][0]['amount']['value'];
			}
			
			return new RenewalTerm(
				new Money( $amount, $data['plan']['payment_definitions'][0]['amount']['currency'] ),
				new DateInterval( 'P' . $data['plan']['payment_definitions'][0]['frequency_interval'] . mb_substr( $data['plan']['payment_definitions'][0]['frequency'], 0, 1 ) )
			);
		}
	}
	
	/**
	 * Get next payment date
	 *
	 * @return	DateTime
	 * @throws    \IPS\nexus\Gateway\PayPal\Exception
	 */
	public function nextPaymentDate() : DateTime
	{
		$data = $this->_getData();
		if ( isset( $data['billing_info'] ) )
		{
			return new DateTime( $data['billing_info']['next_billing_time'] );
		}
		else
		{		
			return new DateTime( $data['agreement_details']['next_billing_date'] );
		}
	}

	/**
	 * Get latest unclaimed transaction (only gets transactions within the last 31 days which do not yet have a matching transaction)
	 *
	 * @return	Transaction
	 * @throws    \IPS\nexus\Gateway\PayPal\Exception
	 * @throws	OutOfRangeException
	 * @throws  \IPS\Http\Url\Exception
	 */
	public function latestUnclaimedTransaction() : Transaction
	{
		$data = $this->_getData();
		/* PayPal Subscriptions */
		if ( isset( $data['plan_id'] ) )
		{
			$transactions = $this->method->api( "billing/subscriptions/{$this->gw_id}/transactions?start_time=" . DateTime::create()->sub( new DateInterval( 'P31D' ) )->rfc3339() . '&end_time=' . DateTime::create()->rfc3339(), NULL, 'get' );
			foreach ( array_reverse( $transactions['transactions'] ?? [] ) as $t )
			{
				if ( $t['status'] == 'COMPLETED' )
				{
					try
					{
						Db::i()->select( 't_id', 'nexus_transactions', [ 't_gw_id=? AND t_method=?', $t['id'], $this->method->id ], flags: Db::SELECT_FROM_WRITE_SERVER )->first();
					}
					catch ( UnderflowException )
					{
						$transaction = new Transaction;
						$transaction->member = $this->member;
						$transaction->method = $this->method;
						$transaction->amount = new Money( $t['amount_with_breakdown']['gross_amount']['value'], $t['amount_with_breakdown']['gross_amount']['currency_code'] );
						$transaction->date = new DateTime( $t['time'] );
						$transaction->extra = array( 'automatic' => TRUE );
						$transaction->gw_id = $t['id'];
						$transaction->billing_agreement = $this;
						return $transaction;
					}
				}
			}
		}
		/* Legacy Billing Agreement */
		else
		{
			$url = "payments/billing-agreements/{$this->gw_id}/transactions?start_date=" . date( 'Y-m-d', time() - ( 86400 * 31 ) ) . '&end_date=' . date( 'Y-m-d' );
			$transactions = $this->method->api( $url, NULL, 'get' );
			foreach ( array_reverse( $transactions['agreement_transaction_list'] ) as $t )
			{
				if ( $t['status'] == 'Completed' )
				{
					try
					{
						Db::i()->select( 't_id', 'nexus_transactions', [ 't_gw_id=? AND t_method=?', $t['transaction_id'], $this->method->id ], flags: Db::SELECT_FROM_WRITE_SERVER )->first();
					}
					catch ( UnderflowException )
					{
						$transaction = new Transaction;
						$transaction->member = $this->member;
						$transaction->method = $this->method;
						$transaction->amount = new Money( $t['amount']['value'], $t['amount']['currency'] );
						$transaction->date = new DateTime( $t['time_stamp'] );
						$transaction->extra = array( 'automatic' => TRUE );
						$transaction->gw_id = $t['transaction_id'];
						$transaction->billing_agreement = $this;
						return $transaction;
					}
				}
			}
		}

		throw new OutOfRangeException( "{$this->id} ({$this->gw_id})\n{$url}\n\n" . print_r( $transactions, TRUE ) );
	}
	
	/**
	 * Suspend
	 *
	 * @return	void
	 * @throws	DomainException
	 */
	public function doSuspend() : void
	{
		$data = $this->_getData();
		if ( isset( $data['plan_id'] ) )
		{
			$this->method->api( "billing/subscriptions/{$this->gw_id}/suspend", array( 'reason' => 'Suspend' ) );
		}
		else
		{
			$this->method->api( "payments/billing-agreements/{$this->gw_id}/suspend", array( 'note' => 'Suspend' ) );
		}
	}
	
	/**
	 * Reactivate
	 *
	 * @return	void
	 * @throws	DomainException
	 */
	public function doReactivate() : void
	{
		$data = $this->_getData();
		if ( isset( $data['plan_id'] ) )
		{
			$this->method->api( "billing/subscriptions/{$this->gw_id}/activate", array( 'reason' => 'Reactivate' ) );
		}
		else
		{
			$this->method->api( "payments/billing-agreements/{$this->gw_id}/re-activate", array( 'note' => 'Reactivate' ) );
		}
	}
	
	/**
	 * Cancel
	 *
	 * @return	void
	 * @throws	DomainException
	 */
	public function doCancel() : void
	{
		$data = $this->_getData();
		if ( isset( $data['plan_id'] ) )
		{
			$this->method->api( "billing/subscriptions/{$this->gw_id}/cancel", array( 'reason' => 'Cancel' ) );
		}
		else
		{
			$this->method->api( "payments/billing-agreements/{$this->gw_id}/cancel", array( 'note' => 'Cancel' ) );
		}
	}
	
	/**
	 * @brief	Cached data
	 */
	protected  ?array $_payPalData = NULL;
	
	/**
	 * Get data
	 *
	 * @return	array
	 * @throws    \IPS\nexus\Gateway\PayPal\Exception
	 */
	public function _getData() : array
	{
		if ( $this->_payPalData === NULL )
		{
			try
			{
				$this->_payPalData = $this->method->api( "billing/subscriptions/{$this->gw_id}", NULL, 'get' );

				if( !isset( $this->_payPalData['plan_id'] ) )
				{
					throw new RuntimeException;
				}
			}
			catch ( Exception )
			{
				$this->_payPalData = $this->method->api( "payments/billing-agreements/{$this->gw_id}", NULL, 'get' );
			}
		}
		return $this->_payPalData;
	}
}