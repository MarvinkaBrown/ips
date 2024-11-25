<?php
/**
 * @brief		Account Credit
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		08 May 2014
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Http\Url;
use IPS\Member;
use IPS\MFA\MFAHandler;
use IPS\nexus\Customer;
use IPS\nexus\Customer\AlternativeContact;
use IPS\nexus\extensions\nexus\Item\AccountCreditIncrease;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Payout;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_countable;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Account Credit
 */
class credit extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2X239/1', 403, '' );
		}

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'clients.css', 'nexus' ) );
		Output::i()->breadcrumb[] = array( Url::internal( 'app=nexus&module=clients&controller=credit', 'front', 'clientscredit' ), Member::loggedIn()->language()->addToStack('client_credit') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('client_credit');
		Output::i()->sidebar['enabled'] = FALSE;
		
		if ( $output = MFAHandler::accessToArea( 'nexus', 'AccountCredit', Url::internal( 'app=nexus&module=clients&controller=credit', 'front', 'clientscredit' ) ) )
		{
			Output::i()->output = Theme::i()->getTemplate('clients')->credit( NULL, array(), NULL, FALSE, FALSE ) . $output;
			return;
		}
		
		parent::execute();
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Get Balance(s) */
		$balance = array_filter( Customer::loggedIn()->cm_credits, function( $val )
		{
			return $val->amount;
		} );
				
		/* Pending Withdrawls */
		$perPage = 10;
		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		$pastWithdrawalsSelect = Db::i()->select( '*', 'nexus_payouts', array( 'po_member=?', Customer::loggedIn()->member_id ), 'po_date DESC', array( ( $page - 1 ) * $perPage, $perPage ) );
		$pastWithdrawalsPagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
			Url::internal( 'app=nexus&module=clients&controller=credit', 'front', 'clientscredit' ),
			ceil( Db::i()->select( 'COUNT(*)', 'nexus_payouts', array( 'po_member=?', Customer::loggedIn()->member_id ) )->first() / $perPage ),
			$page,
			$perPage
		);
		$pastWithdrawals = new ActiveRecordIterator( $pastWithdrawalsSelect, 'IPS\nexus\Payout' );

		$activePayouts = [];
		if( !Settings::i()->nexus_payout_unlimited_times )
		{
			foreach ( $pastWithdrawals as $payout )
			{
				if ( $payout->status == Payout::STATUS_PENDING )
				{
					$activePayouts[ $payout->currency ] = TRUE;
				}
			}
		}
		
		/* Can we withdraw? */
		$canWithdraw = FALSE;
		$withdrawOptions = json_decode( Settings::i()->nexus_payout, TRUE );
		if( is_countable( $withdrawOptions ) AND count( $withdrawOptions ) )
		{
			foreach ( Money::currencies() as $currency )
			{
				if ( isset( $balance[ $currency ] ) and $balance[ $currency ]->amount->isGreaterThanZero() and !isset( $activePayouts[ $currency ] ) )
				{
					$canWithdraw = TRUE;
				}
			}
		}
		
		/* Can we topup? */
		$canTopup = FALSE;
		if ( Settings::i()->nexus_min_topup )
		{
			$maximumBalanceAmounts = ( Settings::i()->nexus_max_credit and Settings::i()->nexus_max_credit !== '*' ) ? json_decode( Settings::i()->nexus_max_credit, TRUE ) : NULL;
			
			foreach ( array_merge( array( Customer::loggedIn() ), iterator_to_array( Customer::loggedIn()->parentContacts( array('billing=1') ) ) ) as $account )
			{
				if ( $account instanceof AlternativeContact )
				{
					$account = $account->main_id;
				}
				
				foreach ( $account->cm_credits as $value )
				{
					if ( $maximumBalanceAmounts === NULL or $value->amount->compare( new \IPS\Math\Number( number_format( $maximumBalanceAmounts[ $value->currency ]['amount'], Money::numberOfDecimalsForCurrency( $value->currency ), '.', '' ) ) ) === -1 )
					{
						$canTopup = TRUE;
					}
				}
			}
		}
				
		/* Display */
		Output::i()->output = Theme::i()->getTemplate('clients')->credit( $balance, $pastWithdrawals, $pastWithdrawalsPagination, $canWithdraw, $canTopup );
	}
	
	/**
	 * Withdraw
	 *
	 * @return	void
	 */
	protected function withdraw() : void
	{
		$activeWithdrawals = Settings::i()->nexus_payout_unlimited_times ? [] : iterator_to_array( Db::i()->select( 'po_currency', 'nexus_payouts', array( 'po_status=? AND po_member=?', Payout::STATUS_PENDING, Customer::loggedIn()->member_id ) ) );
		$withdrawForm = NULL;
		$withdrawOptions = json_decode( Settings::i()->nexus_payout, TRUE );
		if ( isset( $withdrawOptions['Stripe'] ) )
		{
			unset( $withdrawOptions['Stripe'] );
		}
		if ( is_countable( $withdrawOptions ) AND count( $withdrawOptions ) )
		{
			$withdrawForm = new Form( 'withdraw', 'withdraw_credit' );
			
			$minimumWithdrawalsAmounts = ( Settings::i()->nexus_payout_min and Settings::i()->nexus_payout_min !== '*' ) ? json_decode( Settings::i()->nexus_payout_min, TRUE ) : NULL;
			$maximumWithdrawalsAmounts = ( Settings::i()->nexus_payout_max and Settings::i()->nexus_payout_max !== '*' ) ? json_decode( Settings::i()->nexus_payout_max, TRUE ) : NULL;
			
			$balance = array_filter( Customer::loggedIn()->cm_credits, function( $val )
			{
				return $val->amount;
			} );
				
			$currencyOptions = array();
			if ( count( $balance ) > 1 )
			{
				$currencyToggles = array();
				foreach ( Money::currencies() as $currency )
				{
					if ( isset( $balance[ $currency ] ) AND !in_array( $currency, $activeWithdrawals ) )
					{
						$currencyOptions[ $currency ] = $currency;
						$currencyToggles[ $currency ] = array( 'withdraw_amount_' . $currency );
					}
				}
				
				$withdrawForm->add( new Radio( 'withdraw_currency', NULL, TRUE, array( 'options' => $currencyOptions, 'toggles' => $currencyToggles ) ) );
			}

			$canWithdraw = FALSE;
			foreach ( Money::currencies() as $currency )
			{
				if ( !isset( $balance[ $currency ] ) OR ( !Settings::i()->nexus_payout_unlimited_times AND in_array( $currency, $activeWithdrawals ) ) )
				{
					continue;
				}
				
				$min = $minimumWithdrawalsAmounts ? ( $minimumWithdrawalsAmounts[ $currency ]['amount'] ) : 0;

				/* Default 'max' is our balance */
				$max = (string) $balance[ $currency ]->amount;

				/* Do we have a maximum withdrawal amount set up? */
				if( $maximumWithdrawalsAmounts AND $maximumWithdrawalsAmounts[ $currency ] )
				{
					/* If our maximum withdrawal amount is less than our balance, then the max we can withdraw is that */
					$upperLimit	= $maximumWithdrawalsAmounts[ $currency ];

					/* Determine our date cutoff */
					$periodRestrictions = json_decode( Settings::i()->nexus_payout_max_period, TRUE );

					$interval	= 'P' . (int) $periodRestrictions[0] . ( mb_strtoupper( mb_substr( $periodRestrictions[1], 0, 1 ) ) );
					$date		= DateTime::create()->sub( new DateInterval( $interval ) )->getTimestamp();

					/* Get our (non-cancelled) payout requests since the date cutoff */
					foreach( Db::i()->select( '*', 'nexus_payouts', array( 'po_member=? AND po_date>? AND po_status NOT IN(?) AND po_currency=?', Customer::loggedIn()->member_id, $date, 'canc', $currency ) ) as $request )
					{
						$upperLimit -= $request['po_amount'];
					}

					/* Do we need to reset the max? */
					if( $upperLimit < $max )
					{
						$max = $upperLimit;
					}
				}

				if( $max <= 0 )
				{
					Member::loggedIn()->language()->words[ 'withdraw_amount_' . $currency . '_desc' ] = Member::loggedIn()->language()->addToStack( 'max_withdrawl_exceeded' );
				}

				$field = new Number( 'withdraw_amount_' . $currency, (string) $max, count( $currencyOptions ) ? NULL : TRUE, array( 'min' => $min, 'max' => (string) $max, 'decimals' => Money::numberOfDecimalsForCurrency( $currency ), 'disabled' => ( $max <= 0 ) ), count( $currencyOptions ) ? NULL : function( $val ) use ( $currency )
				{
					if ( !$val and Request::i()->withdraw_currency === $currency )
					{
						throw new DomainException('form_required');
					}
				}, NULL, $currency, 'withdraw_amount_' . $currency );
				$field->label = Member::loggedIn()->language()->addToStack('withdraw_amount');
				$withdrawForm->add( $field );
				
				$canWithdraw = TRUE;
			}
						
			if ( $canWithdraw )
			{
				$options = array();
				$toggles = array();
				$fields = array();
				foreach ( $withdrawOptions as $k => $settings )
				{
					$options[ $k ] = $k === 'Manual' ? $settings['name'] : 'withdraw__' . $k;
					$toggles[ $k ] = array();

					/* @var Payout $class */
					$class = Gateway::payoutGateways()[ $k ];
					foreach ( $class::form() as $field )
					{
						if ( !$field->htmlId )
						{
							$field->htmlId = $field->name;
							$toggles[ $k ][] = $field->htmlId;
						}
						$fields[] = $field;
					}
				}
				
				if ( is_countable( $withdrawOptions ) AND count( $withdrawOptions ) > 1 )
				{
					$withdrawForm->add( new Radio( 'withdraw_method', NULL, TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );
				}
				
				foreach ( $fields as $field )
				{
					$withdrawForm->add( $field );
				}
				if ( $values = $withdrawForm->values() )
				{					
					$currencies = array_keys( $balance );
					$currency = count( $currencyOptions ) ? $values['withdraw_currency'] : array_pop( $currencies );
					
					$withdrawMethods = array_keys( $options );
					if ( count( $withdrawMethods ) === 1 )
					{
						$key = array_pop( $withdrawMethods );
						$class = Gateway::payoutGateways()[ $key ];
					}
					else
					{
						$class = Gateway::payoutGateways()[ $values['withdraw_method'] ];
					}
					
					$payout = new $class;
					$payout->amount = new Money( $values[ 'withdraw_amount_' . $currency ], $currency );
					$payout->member = Customer::loggedIn();
					try
					{
						$payout->data = $payout->getData( $values );
					}
					catch ( DomainException $e )
					{
						Output::i()->error( $e->getMessage(), '1X239/2', 403, '' );
					}
					$payout->ip = Request::i()->ipAddress();
					$payout->save();
					
					$credits = Customer::loggedIn()->cm_credits;
					$credits[ $currency ]->amount = $credits[ $currency ]->amount->subtract( new \IPS\Math\Number( number_format( $values[ 'withdraw_amount_' . $currency ], Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) ) );
					Customer::loggedIn()->cm_credits = $credits;
					Customer::loggedIn()->save();
					
					if ( !Settings::i()->nexus_payout_approve and !$payout::$requiresApproval )
					{
						try
						{
							$payout->process();
							Customer::loggedIn()->log( 'payout', array( 'type' => 'autoprocess', 'amount' => $values[ 'withdraw_amount_' . $currency ], 'currency' => $currency, 'payout_id' => $payout->id ) );
							Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=credit&withdraw=success', 'front', 'clientscredit' ) );
						}
						catch ( Exception ) {}
					}
					else
					{
						AdminNotification::send( 'nexus', 'Withdrawal', NULL, TRUE, $payout );
					}
					
					Customer::loggedIn()->log( 'payout', array( 'type' => 'request', 'amount' => $values[ 'withdraw_amount_' . $currency ], 'currency' => $currency, 'payout_id' => $payout->id ) );
					Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=credit&withdraw=pending', 'front', 'clientscredit' ) );
				}
			}
			else
			{
				$withdrawForm = NULL;
			}
		}
		
		if ( !$withdrawForm )
		{
			Output::i()->error( 'no_module_permission', '2X239/3', 403, '' );
		}
		
		Output::i()->output = $withdrawForm->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Topup
	 *
	 * @return	void
	 */
	protected function topup() : void
	{
		$topupForm = NULL;
		if ( Settings::i()->nexus_min_topup )
		{
			$minimumTopupAmounts = ( Settings::i()->nexus_min_topup and Settings::i()->nexus_min_topup !== '*' ) ? json_decode( Settings::i()->nexus_min_topup, TRUE ) : NULL;
			$maximumBalanceAmounts = ( Settings::i()->nexus_max_credit and Settings::i()->nexus_max_credit !== '*' ) ? json_decode( Settings::i()->nexus_max_credit, TRUE ) : NULL;
			
			$maximums = array();
			foreach ( array_merge( array( Customer::loggedIn() ), iterator_to_array( Customer::loggedIn()->parentContacts( array('billing=1') ) ) ) as $account )
			{
				if ( $account instanceof AlternativeContact )
				{
					$account = $account->main_id;
				}
				
				foreach ( $account->cm_credits as $value )
				{
					$max = $maximumBalanceAmounts ? ( ( new \IPS\Math\Number( number_format( $maximumBalanceAmounts[ $value->currency ]['amount'], Money::numberOfDecimalsForCurrency( $value->currency ), '.', '' ) ) )->subtract( $value->amount ) ) : NULL;
					if ( is_null( $max ) or $max->isGreaterThanZero() )
					{
						$maximums[ $account->member_id ][ $value->currency ] = is_null( $max ) ? NULL : (string) $max;
					}
				}
			}
			
			if ( count( $maximums ) )
			{
				$topupForm = new Form( 'topup', 'checkout' );
				
				$accountOptions = array();
				$accountOptionToggles = array();
				foreach ( $maximums as $accountId => $data )
				{
					$currency = NULL;
					if ( count( $data ) === 1 )
					{
						$currencies = array_keys( $data );
						$currency = array_pop( $currencies );
					}
					
					$accountOptions[ $accountId ] = $accountId === Customer::loggedIn()->id ? Customer::loggedIn()->language()->addToStack( 'my_account', FALSE, array( 'sprintf' => array( Customer::loggedIn()->cm_name ) ) ) : Customer::load( $accountId )->cm_name;
					$accountOptionToggles[ $accountId ] = ( count( $data ) > 1 ) ? array( 'topup_currency_' . $accountId ) : array( 'topup_amount_' . $accountId . '_' . $currency );
				}
				$topupForm->add( new Radio( 'topup_account', NULL, TRUE, array( 'options' => $accountOptions, 'parse' => 'normal', 'toggles' => $accountOptionToggles ) ) );
				
				foreach ( $maximums as $accountId => $data )
				{
					if ( count( $data ) > 1 )
					{
						$currencyToggles = array();
						foreach ( $data as $currency => $maximum )
						{
							$currencyToggles[ $currency ] = array( 'topup_amount_' . $accountId . '_' . $currency );
						}
						
						$field = new Radio( 'topup_currency_' . $accountId, NULL, NULL, array( 'options' => array_combine( array_keys( $data ), array_keys( $data ) ), 'toggles' => $currencyToggles ), NULL, NULL, NULL, 'topup_currency_' . $accountId );
						$field->label = Member::loggedIn()->language()->addToStack( 'topup_currency' );
						$topupForm->add( $field );
					}
					
					foreach ( $data as $currency => $maximum )
					{
						$min = $minimumTopupAmounts ? ( $minimumTopupAmounts[ $currency ]['amount'] ) : 0.01;
						
						$field = new Number( 'topup_amount_' . $accountId . '_' . $currency, NULL, NULL, array( 'decimals' => Money::numberOfDecimalsForCurrency( $currency ) ), function( $val ) use ( $currency, $accountOptions, $accountId, $data, $min, $maximum )
						{
							if ( count( $accountOptions ) === 1 or Request::i()->topup_account == $accountId )
							{
								$key = "topup_currency_{$accountId}";
								if ( count( $data ) === 1 or Request::i()->$key == $currency )
								{
									if ( !$val )
									{
										throw new DomainException('form_required');
									}
									elseif ( !is_null( $min ) and $val < $min )
									{
										throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_number_min', FALSE, array( 'sprintf' => array( $min ) ) ) );
									}
									elseif ( $maximum and $val > $maximum )
									{
										throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_number_max', FALSE, array( 'sprintf' => array( $maximum ) ) ) );
									}
								}
							}
						}, NULL, $currency, 'topup_amount_' . $accountId . '_' . $currency );
						$field->label = Member::loggedIn()->language()->addToStack('topup_amount');
						$topupForm->add( $field );		
					}
				}
				
				if ( $values = $topupForm->values() )
				{

					$account = isset( $values['topup_account'] ) ? Customer::load( $values['topup_account'] ) : Customer::loggedIn();
					if ( isset( $values[ 'topup_currency_' . $account->member_id ] ) )
					{
						$currency = $values[ 'topup_currency_' . $account->member_id ];
					}
					else
					{
						$currencies = array_keys( $maximums[ $account->member_id ] );
						$currency = array_pop( $currencies );
					}
					
					$invoice = new Invoice;
					$invoice->member = $account;
					$invoice->currency = $currency;
					$invoice->return_uri = 'app=nexus&module=clients&controller=credit';
					$invoice->addItem( new AccountCreditIncrease( Customer::loggedIn()->language()->get('account_credit'), new Money( $values["topup_amount_{$account->member_id}_{$currency}"], $currency ) ) );
					Output::i()->redirect( $invoice->checkoutUrl() );
				}
			}
		}
		
		if ( !$topupForm )
		{
			Output::i()->error( 'no_module_permission', '2X239/4', 403, '' );
		}
		
		Output::i()->output = $topupForm->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Cancel Pending Payout
	 *
	 * @return	void
	 */
	protected function cancel() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$payout = Payout::load( Request::i()->id );
			if ( $payout->member->member_id === Customer::loggedIn()->member_id and $payout->status === $payout::STATUS_PENDING )
			{
				$payout->status = $payout::STATUS_CANCELED;
				$payout->save();
				
				$credits = $payout->member->cm_credits;
				$credits[ $payout->amount->currency ]->amount = $credits[ $payout->amount->currency ]->amount->add( $payout->amount->amount );
				$payout->member->cm_credits = $credits;
				$payout->member->save();
				
				Customer::loggedIn()->log( 'payout', array( 'type' => 'cancel', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );
			}
		}
		catch ( OutOfRangeException ) { }
		
		Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=credit', 'front', 'clientscredit' ) );
	}	
}