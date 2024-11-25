<?php
/**
 * @brief		Payouts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		07 Apr 2014
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\nexus\Form\Money;
use IPS\nexus\Gateway;
use IPS\nexus\Payout;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
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
 * Payouts
 */
class payouts extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'payouts_manage' );
		parent::execute();
	}

	/**
	 * Payout Requests
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Redirect to settings if we haven't set it up */
		if ( !Settings::i()->nexus_payout )
		{
			Output::i()->redirect( Url::internal( 'app=nexus&module=payments&controller=payouts&do=settings' ) );
		}
		
		/* Build table */
		$table = Payout::table( array(), Url::internal('app=nexus&module=payments&controller=payouts') );
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__nexus_payments_payouts');
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_settings' ) )
		{
			Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
					'icon'		=> 'cog',
					'title'		=> 'account_credit_settings',
					'link'		=> Url::internal( 'app=nexus&module=payments&controller=payouts&do=settings' )
				),
			);
			
			foreach ( Db::i()->select( 'po_gateway', 'nexus_payouts', array( 'po_status=?', Payout::STATUS_PENDING ), NULL, NULL, 'po_gateway' ) as $gateway )
			{
				$classname = Gateway::payoutGateways()[ $gateway ];
				if ( class_exists( $classname ) and method_exists( $classname, 'massProcess' ) )
				{
					Output::i()->sidebar['actions'][] = [
						'icon'		=> 'check',
						'title'		=> Member::loggedIn()->language()->addToStack( 'account_credit_mass_payout', TRUE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'payout__admin_' . $gateway ) ) ) ),
						'link'		=> Url::internal( 'app=nexus&module=payments&controller=payouts&do=massprocess&gateway=' . $gateway )->csrf(),
						'data'		=> [
							'confirm'           => '',
							'confirmMessage'    => Member::loggedIn()->language()->addToStack( 'nexus_payout_confirm_title' ),
							'confirmSubMessage' => Member::loggedIn()->language()->addToStack( 'nexus_payout_confirm_text' )
						]
					];
				}
			}
		}
		Output::i()->output = (string) $table;
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	protected function view() : void
	{
		try
		{
			$payout = Payout::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X200/2', 404, '' );
		}
				
		Output::i()->sidebar['actions'] = $payout->buttons( 'v' );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'payout_number', FALSE, array( 'sprintf' => array( $payout->id ) ) );
		Output::i()->output = Theme::i()->getTemplate('payouts')->view( $payout );
	}
	
	/**
	 * Approve
	 *
	 * @return	void
	 */
	protected function process() : void
	{
		Dispatcher::i()->checkAcpPermission( 'payouts_process' );
		Session::i()->csrfCheck();
		
		try
		{
			$payout = Payout::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X200/3', 404, '' );
		}
		
		if ( $payout->status !== $payout::STATUS_PENDING )
		{
			Output::i()->error( 'err_payout_not_pending', '1X200/4', 403, '' );
		}
		
		try
		{
			$payout->status = $payout->process();
		}
		catch ( Exception $e )
		{
			Output::i()->error( $e->getMessage(), '1X200/1', 500, '' );
		}

		$payout->processed_by = Member::loggedIn();
		$payout->save();
		Session::i()->log( 'acplogs__payout_processed', array( $payout->id => FALSE ) );
		$payout->member->log( 'payout', array( 'type' => 'processed', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );

		/* If the payout was actually completed, notify the user */
		if( $payout->status == Payout::STATUS_COMPLETE )
		{
			$payout->markCompleted();
		}
		else
		{
			/* Use task if the payout is pending */
			$task = Task::load( 'payoutPending', 'key', [ 'app=?', 'nexus' ] );
			$task->enabled = TRUE;
			$task->save();
		}
				
		$this->_redirect( $payout );
	}
	
	/**
	 * Mass Approve
	 *
	 * @return	void
	 */
	protected function massprocess() : void
	{
		Dispatcher::i()->checkAcpPermission( 'payouts_process' );
		Request::i()->confirmedDelete( 'nexus_payout_confirm_title', 'nexus_payout_confirm_text', 'nexus_payout_proceed' );
		
		$classname = Gateway::payoutGateways()[ Request::i()->gateway ];
		if ( !class_exists( $classname ) or !method_exists( $classname, 'massProcess' ) )
		{
			Output::i()->error( 'generic_error', '2X200/8', 403, '' );
		}
		
		$payouts = new ActiveRecordIterator( Db::i()->select( '*', 'nexus_payouts', array( 'po_status=? AND po_gateway=?', Payout::STATUS_PENDING, Request::i()->gateway ) ), $classname );
		
		try
		{	
			$classname::massProcess( $payouts );
		}
		catch ( Exception $e )
		{
			Output::i()->error( $e->getMessage(), '1X200/9', 403, '' );
		}

		$pending = 0;
		foreach ( $payouts as $payout )
		{
			/* @var Payout $payout */
			$payout->processed_by = Member::loggedIn();
			$payout->save();
			Session::i()->log( 'acplogs__payout_processed', array( $payout->id => FALSE ) );
			$payout->member->log( 'payout', array( 'type' => 'processed', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );

			/* Only do this if the payout is actually complete! */
			if( $payout->status == Payout::STATUS_COMPLETE )
			{
				$payout->markCompleted();
				continue;
			}

			$pending++;
		}

		/* Use task if there are incomplete payouts */
		if( $pending )
		{
			$task = Task::load( 'payoutPending', 'key', [ 'app=?', 'nexus' ] );
			$task->enabled = TRUE;
			$task->save();
		}
		
		Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=payouts') );
	}
	
	/**
	 * Cancel
	 *
	 * @return	void
	 */
	protected function cancel() : void
	{
		Dispatcher::i()->checkAcpPermission( 'payouts_cancel' );
		Session::i()->csrfCheck();
		
		try
		{
			$payout = Payout::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X200/6', 404, '' );
		}
		
		if ( $payout->status !== $payout::STATUS_PENDING )
		{
			Output::i()->error( 'err_payout_not_pending', '1X200/5', 403, '' );
		}
		
		$payout->status = $payout::STATUS_CANCELED;
		$payout->completed = new DateTime;
		$payout->processed_by = Member::loggedIn();
		$payout->save();
		Session::i()->log( 'acplogs__payout_cancelled', array( $payout->id => FALSE ) );
		$payout->member->log( 'payout', array( 'type' => 'cancel', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );
		
		if ( Request::i()->prompt )
		{
			$credits = $payout->member->cm_credits;
			$credits[ $payout->amount->currency ]->amount = $credits[ $payout->amount->currency ]->amount->add( $payout->amount->amount );
			$payout->member->cm_credits = $credits;
			$payout->member->save();
		}
		
		$this->_redirect( $payout );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'payouts_delete' );
		
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		try
		{
			$payout = Payout::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X200/7', 404, '' );
		}
		
		$payout->delete();
		Session::i()->log( 'acplogs__payout_deleted', array( $payout->id => FALSE ) );
		$payout->member->log( 'payout', array( 'type' => 'dismissed', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );
		
		Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=payouts') );
	}
	
	/**
	 * Payout Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		Dispatcher::i()->checkAcpPermission( 'payouts_settings' );
		
		$options = array();
		$toggles = array();
		$fields = array();
		foreach ( Gateway::payoutGateways() as $key => $class )
		{
			$options[ $key ] = 'payout__admin_' . $key;
			$toggles[ $key ] = array( 'form_header_payout_settings' );

			/* @var Payout $class */
			foreach ( $class::settings() as $field )
			{
				if ( !$field->htmlId )
				{
					$field->htmlId = md5(mt_rand());
				}
				
				$fields[] = $field;
				$toggles[ $key ][] = $field->htmlId;
			}
		}
		
		$groups = array();
		foreach ( Group::groups( TRUE, FALSE ) as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		
		$settings = Settings::i()->nexus_payout ? json_decode( Settings::i()->nexus_payout, TRUE ) : array();
		$settings = is_array( $settings ) ? array_keys( $settings ) : array();
		
		$form = new Form;
		$form->addMessage('payout_description', 'ipsMessage--transparent');
		$form->addHeader('commission_settings');
		$form->add( new CheckboxSet( 'nexus_no_commission', explode( ',', Settings::i()->nexus_no_commission ), FALSE, array( 'multiple' => TRUE, 'options' => $groups ) ) );
		$form->addHeader('withdrawal_methods');
		$form->add( new CheckboxSet( 'nexus_payout', $settings, FALSE, array( 'options' => $options, 'toggles' => $toggles ) ) );
		foreach ( $fields as $field )
		{
			$form->add( $field );
		}
		$form->addHeader('payout_settings');
		$form->add( new Money( 'nexus_payout_min', ( Settings::i()->nexus_payout_min AND Settings::i()->nexus_payout_min != '*' ) ? json_decode( Settings::i()->nexus_payout_min, TRUE ) : '*', FALSE, array( 'unlimitedLang' => 'no_restriction' ), NULL, NULL, NULL, 'nexus_payout_min' ) );

		$form->add( new Custom( 'nexus_payout_maximum', NULL, FALSE, array( 'getHtml' => function( $element ) {
			$amount			= new Money( 'nexus_payout_maximum[2]', ( Settings::i()->nexus_payout_max AND Settings::i()->nexus_payout_max != '*' ) ? json_decode( Settings::i()->nexus_payout_max, TRUE ) : '*', FALSE, array( 'unlimitedLang' => 'no_restriction', 'unlimitedTogglesOff' => array( 'payout_max_limited' ) ), NULL, NULL, NULL, 'nexus_payout_max' );
			$period			= Settings::i()->nexus_payout_max_period ? json_decode( Settings::i()->nexus_payout_max_period, TRUE ) : array( 1, 'day' );

			return Theme::i()->getTemplate( 'payouts' )->maximumLimits( $amount->html(), (int) $period[0], $period[1] );
		} ), NULL, NULL, NULL, 'nexus_payout_maximum' ) );

		$form->add( new YesNo( 'nexus_payout_approve', Settings::i()->nexus_payout_approve, FALSE, array(), NULL, NULL, NULL, 'nexus_payout_approve' ) );
		$form->add( new YesNo( 'nexus_payout_unlimited_times', Settings::i()->nexus_payout_unlimited_times, TRUE ) );

		$form->addHeader('topup_settings');
		$form->add( new YesNo( 'allow_topups', Settings::i()->nexus_min_topup, FALSE, array( 'togglesOn' => array( 'nexus_min_topup', 'nexus_max_credit' ) ) ) );
		$form->add( new Money( 'nexus_min_topup', Settings::i()->nexus_min_topup ?: '*', FALSE, array( 'unlimitedLang' => 'no_restriction' ), NULL, NULL, NULL, 'nexus_min_topup' ) );
		$form->add( new Money( 'nexus_max_credit', Settings::i()->nexus_max_credit ?: '*', FALSE, array( 'unlimitedLang' => 'no_restriction' ), NULL, NULL, NULL, 'nexus_max_credit' ) );

		if ( $values = $form->values() )
		{
			$payoutSettings = array();
			foreach ( $values['nexus_payout'] as $k )
			{
				$payoutSettings[ $k ] = array();
				foreach ( $values as $l => $v )
				{
					if ( mb_substr( $l, 0, mb_strlen( $k ) ) === mb_strtolower( $k ) )
					{
						$payoutSettings[ $k ][ mb_substr( $l, mb_strlen( $k ) + 1 ) ] = $v;
						unset( $values[ $l ] ); 
					}
				}
			}

			/* Clear out gateway fields that aren't real settings */
			foreach( $fields as $field )
			{
				unset( $values[ $field->name ] );
			}
						
			$values['nexus_payout'] = json_encode( $payoutSettings );
			$values['nexus_payout_min']			= is_array( $values['nexus_payout_min'] ) ? json_encode( $values['nexus_payout_min'] ) : '*';
			$values['nexus_payout_max']			= ( is_array( $values['nexus_payout_maximum'][2] ) AND ( !isset( $values['nexus_payout_maximum'][2]['__unlimited'] ) OR !$values['nexus_payout_maximum'][2]['__unlimited'] ) ) ? json_encode( $values['nexus_payout_maximum'][2] ) : '*';
			$values['nexus_payout_max_period']	= ( $values['nexus_payout_max'] == '*' ) ? NULL : json_encode( array( $values['nexus_payout_maximum'][0], $values['nexus_payout_maximum'][1] ) );
						
			if ( $values['allow_topups'] )
			{
				$values['nexus_min_topup'] = $values['nexus_min_topup'] == '*' ? '*' : json_encode( $values['nexus_min_topup'] );
				$values['nexus_max_credit'] = $values['nexus_max_credit'] == '*' ? '*' : json_encode( $values['nexus_max_credit'] );
			}
			else
			{
				$values['nexus_min_topup'] = '';
				$values['nexus_max_credit'] = '';
			}
			unset( $values['allow_topups'], $values['nexus_payout_maximum'] );
			
			$values['nexus_no_commission'] = implode( ',', $values['nexus_no_commission'] );

			$form->saveAsSettings( $values );
			Session::i()->log( 'acplogs__payout_settings' );

			/* Remove any ACP notifications related to Payout Settings */
			foreach( Gateway::payoutGateways() as $key => $class )
			{
				AdminNotification::remove( 'nexus', 'ConfigurationError', "po{$key}" );
			}
			
			Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=payouts'), 'saved' );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('account_credit_settings');
		Output::i()->output = $form;
	}
	
	/**
	 * Redirect
	 *
	 * @param	Payout	$payout	The payout
	 * @return	void
	 */
	protected function _redirect( Payout $payout ) : void
	{
		if ( isset( Request::i()->r ) )
		{
			switch ( mb_substr( Request::i()->r, 0, 1 ) )
			{
				case 'v':
					Output::i()->redirect( $payout->acpUrl() );
					break;
				
				case 'c':
					Output::i()->redirect( $payout->member->acpUrl() );
					break;
				
				case 't':
					Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=payouts')->setQueryString( 'filter', Request::i()->filter ) );
					break;
				
				case 'n':
					Output::i()->redirect( Url::internal('app=core&module=overview&controller=notifications') );
					break;
			}
		}
		
		Output::i()->redirect( $payout->acpUrl() );
	}
}