<?php
/**
 * @brief		Nexus Application Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */
 
namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application as SystemApplication;
use IPS\Application\Module;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Invoice\Item\Renewal;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function array_merge;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function unserialize;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Nexus Application Class
 */
class Application extends SystemApplication
{
	/**
	 * Init - catches legacy PayPal IPN messages
	 *
	 * @return	void
	 */
	public function init(): void
	{
		if ( !Request::i()->isAjax() )
		{
			if ( Settings::i()->gateways_counts and $decoded = json_decode( Settings::i()->gateways_counts, TRUE ) and isset( $decoded['Stripe'] ) and $decoded['Stripe'] > 0 )
			{
				Output::i()->jsFiles[] = 'https://js.stripe.com/v3/';
			}
		}

		if ( Request::i()->app == 'nexus' and Request::i()->module == 'payments' and Request::i()->section == 'receive' and Request::i()->validate == 'paypal' )
		{
			if ( ( Request::i()->txn_type == 'subscr_payment' or Request::i()->txn_type == 'recurring_payment' ) and Request::i()->payment_status == 'Completed' )
			{
				try
				{
					$saveSubscription = FALSE;

					/* Get the subscription */
					try
					{
						if( Request::i()->txn_type == 'subscr_payment' )
						{
							$subscription = Db::i()->select( '*', 'nexus_subscriptions', array( 's_gateway_key=? AND s_id=?', 'paypal', Request::i()->subscr_id ) )->first();
						}
						else                            
						{
							$subscription = Db::i()->select( '*', 'nexus_subscriptions', array( 's_gateway_key=? AND s_id=?', 'paypalpro', Request::i()->recurring_payment_id ) )->first();
						}
						$items = unserialize( $subscription['s_items'] );
						if ( !is_array( $items ) or empty( $items ) )
						{
							/* Don't exit here, throw an underflow exception so we can see if this is a legacy subscription */
							throw new UnderflowException( 'NO_ITEMS' );
						}
					}
					catch( UnderflowException $e )
					{
						/* Was this from the old IP.Subscriptions? */
						if ( isset( Request::i()->old ) or mb_strstr( Request::i()->custom, '-' ) !== FALSE )
						{
							if ( mb_strstr( Request::i()->custom, '-' ) !== FALSE )
							{
								$exploded	= explode( '-', Request::i()->custom );
								$memberId	= intval( $exploded[0] );
								$purchaseId	= intval( $exploded[1] );

								$item = Db::i()->select( '*', 'nexus_purchases', array( "ps_id=?", $purchaseId ), 'ps_id' )->first();
							}
							else
							{
								$memberId	= intval( Request::i()->custom );

								$item = Db::i()->select( '*', 'nexus_purchases', array( "ps_member=? AND ps_name=?", $memberId, Request::i()->item_name ), 'ps_start DESC' )->first();
							}
				
							/* If the purchase isn't valid or was cancelled just exit */
							if ( !$item['ps_id'] or $item['ps_cancelled'] )
							{
								exit;
							}

							/* We need $items below, not $item */
							$items = array( $item['ps_id'] );

							/* Grab the first paypal method we can find */
							$method		= Db::i()->select( 'm_id', 'nexus_paymethods', array( 'm_gateway=?', 'PayPal' ) )->first();

							/* We are here because the subscription record doesn't exist.  The 3.x code just silently and automatically added one, so do the same now.
								Note that we will need the transaction ID to save the subscription, but we can set the array parameters that will be referenced below. */
							$subscription = array(
								's_gateway_key'	=> 'paypal',
								's_id'			=> Request::i()->subscr_id,
								's_start_trans'	=> 0, 	// We will set this later after saving the transaction
								's_method'		=> $method,
								's_member'		=> $memberId,
							);

							$saveSubscription = TRUE;
						}
						else
						{
							/* Just let the exception bubble up and get caught */
							throw $e;
						}
					}
					
					/* If the gateway still exists, fetch it */
					$gateway = NULL;
					try
					{
						$gateway = Gateway::load( $subscription['s_method'] );
					}
					catch ( OutOfRangeException ) { }
										
					/* Check this was actually a PayPal IPN message */					
					try
					{
						$response = Url::external( 'https://www.' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'sandbox.' : '' ) . 'paypal.com/cgi-bin/webscr/' )->request()->setHeaders( array( 'Accept' => 'application/x-www-form-urlencoded' ) )->post( array_merge( array( 'cmd' => '_notify-validate' ), $_POST ) );
						if ( (string) $response !== 'VERIFIED' )
						{
							exit;
						}
					}
					catch ( Exception )
					{
						exit;
					}
					
					/* Has an invoice already been generated? */
					$_items = $items;
					try
					{
						$invoice = Invoice::constructFromData( Db::i()->select( '*', 'nexus_invoices', array( 'i_member=? AND i_status=?', $subscription['s_member'], 'pend' ), 'i_date DESC', 1 )->first() );
						foreach ( $invoice->items as $item )
						{
							if ( $item instanceof Renewal and in_array( $item->id, $_items ) )
							{
								unset( $_items[ array_search( $item->id, $_items ) ] );
							}
						}
					}
					catch ( UnderflowException ) { }
					
					/* No, create one */
					if ( count( $_items ) )
					{
						$invoice = new Invoice;
						$invoice->member = Customer::load( $subscription['s_member'] );
						foreach ( $items as $purchaseId )
						{
							try
							{
								$purchase = Purchase::load( $purchaseId );
								if ( $purchase->renewals and !$purchase->cancelled )
								{
									$invoice->addItem( Renewal::create( $purchase ) );
								}
							}
							catch ( OutOfRangeException ) { }
						}
						if ( !count( $invoice->items ) )
						{
							exit;
						}
						$invoice->save();
					}
					
					/* And then log a transaction */
					$transaction = new Transaction;
					$transaction->member = $invoice->member;
					$transaction->invoice = $invoice;
					if ( $gateway )
					{
						$transaction->method = $gateway;
					}
					$transaction->amount = new Money( $_POST['mc_gross'], $_POST['mc_currency'] );
					$transaction->gw_id = $_POST['txn_id'];
					$transaction->approve();

					/* If this was a legacy subscription payment, we'll need to save the subscription now that we have a trans ID */
					if( $saveSubscription === TRUE )
					{
						$subscription['s_start_trans']	= $transaction->id;

						Db::i()->insert( 'nexus_subscriptions', $subscription );
					}
				}
				catch ( UnderflowException ) { }
			}
			
			exit;
		}
	}

	/**
	 * @brief Cached menu counts
	 */
	protected array $menuCounts = array();

	/**
	 * ACP Menu Numbers
	 *
	 * @param	string	$queryString	Query String
	 * @return	int
	 */
	public function acpMenuNumber( string $queryString ): int
	{
		parse_str( $queryString, $query );
		switch ( $query['controller'] )
		{
			case 'transactions':
				if( !isset( $this->menuCounts['transactions'] ) )
				{
					$this->menuCounts['transactions'] = Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( Db::i()->in( 't_status', array( Transaction::STATUS_HELD, Transaction::STATUS_WAITING, Transaction::STATUS_REVIEW, Transaction::STATUS_DISPUTED ) ) ) )->first();
				}
				return $this->menuCounts['transactions'];
				break;
			
			case 'payouts':
				if( !isset( $this->menuCounts['payouts'] ) )
				{
					$this->menuCounts['payouts'] = Db::i()->select( 'COUNT(*)', 'nexus_payouts', array( 'po_status=?', Payout::STATUS_PENDING ) )->first();
				}

				return $this->menuCounts['payouts'];
				break;
		}

		return 0;
	}
	
	/**
	 * Cart count
	 *
	 * @return	int
	 */
	public static function cartCount() : int
	{
		$count = 0;
		foreach ( $_SESSION['cart'] as $item )
		{
			$count += $item->quantity;
		}
		return $count;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return    string
	 */
	protected function get__icon(): string
	{
		return 'shopping-cart';
	}

    /**
     * A list of whitelisted modules which should be accessible without the subscription check.
     *
     * @var string[]
     */
    static array $bypassSubscriptionCheckControllers = ['store', 'checkout', 'subscriptions', 'system', 'clients'];

    /**
	 * Do Member Check
	 *
	 * @return	Url|NULL
	 */
	public function doMemberCheck(): ?Url
	{
		/* These checks do not apply to staff accounts or support account */
		if( is_array( Member::loggedIn()->modPermissions() ) OR Member::loggedIn()->isAdmin() OR Member::loggedIn()->members_bitoptions['is_support_account'] )
		{
			return NULL;
		}

		if ( Settings::i()->nexus_subs_enabled AND Settings::i()->nexus_subs_register AND !in_array( Dispatcher::i()->module->key, static::$bypassSubscriptionCheckControllers ) )
		{
			if( !isset( Store::i()->nexus_sub_count ) )
			{
				Store::i()->nexus_sub_count = Db::i()->select( 'count(*)', 'nexus_member_subscription_packages', [ 'sp_enabled=?', 1 ] )->first();
			}

			/* This user didn't join recently, so we don't need to redirect them */
			if( Store::i()->nexus_sub_count AND (int) Settings::i()->nexus_subs_register === 1 AND !Member::loggedIn()->joinedRecently() )
			{
				return NULL;
			}

			if ( Store::i()->nexus_sub_count AND !Subscription::loadByMember( Member::loggedIn(), (int) Settings::i()->nexus_subs_register === 2 ? TRUE : FALSE ) )
			{
				return Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&register=" . (int) Settings::i()->nexus_subs_register, 'front', 'nexus_subscriptions' );
			}
		}

		if ( Settings::i()->nexus_reg_force AND !in_array( Dispatcher::i()->module->key, static::$bypassSubscriptionCheckControllers ) AND Member::loggedIn()->joinedRecently() )
		{
			if( !isset( Store::i()->nexus_reg_product_count ) )
			{
				Store::i()->nexus_reg_product_count = Package::haveRegistrationProducts();
			}

			if ( Store::i()->nexus_reg_product_count AND !Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( "ps_member=? AND ps_active=?", Member::loggedIn()->member_id, 1 ) )->first() )
			{
				return Url::internal( 'app=nexus&module=store&controller=store&do=register', 'front', 'store' );
			}
		}

		return NULL;
	}
	
	/**
	 * Can view page even when user is a guest when guests cannot access the site
	 *
	 * @param	Module	$module			The module
	 * @param string $controller		The controller
	 * @param string|null $do				To "do" parameter
	 * @return	bool
	 */
	public function allowGuestAccess(Module $module, string $controller, ?string $do ): bool
	{
		if( ( Settings::i()->nexus_reg_force OR Package::haveRegistrationProducts() ) AND ( $module->key == 'store' OR $module->key == 'checkout' ) )
		{
			return TRUE;
		}
		
		if ( Settings::i()->nexus_subs_register AND ( in_array( $module->key, [ 'store', 'checkout', 'subscriptions' ] ) ) )
		{
			return TRUE;
		}
		
		if ( Settings::i()->cm_ref_on AND $module->key == 'promotion' AND $controller == 'referral' )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Can view page even when the member is validating
	 *
	 * @param Module $module
	 * @param string $controller
	 * @param string|null $do
	 * @return bool
	 */
	public function allowValidatingAccess( Module $module, string $controller, ?string $do ) : bool
	{
		return (
			$module->key == 'store' and $controller == 'store'
		) or (
			$module->key == 'checkout' and $controller == 'checkout'
			);
	}

	/**
	 * Do we run doMemberCheck for this controller?
	 * @see Application::doMemberCheck()(
	 *
	 * @param Module $module
	 * @param string $controller
	 * @param string|null $do
	 * @return bool
	 */
	public function skipDoMemberCheck( Module $module, string $controller, ?string $do ) : bool
	{
		return (
			$module->key  == 'subscriptions' and $controller == 'subscriptions'
		);
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation(): array
	{
		return array(
			'rootTabs'		=> array(
				array(
					'app'		=> 'core',
					'key'		=> 'Menu',
					'title'		=> Member::loggedIn()->language()->get( 'module__nexus_store' ),
					'icon'			=> json_encode( [ [
						'key' => 'cart-shopping:fas',
						'type' => 'fa',
						'raw' => '<i class="fa-solid fa-cart-shopping"></i>',
						'title' => 'cart-shopping',
						'html' => '\r\n<!-- theme_core_global_global_icon --><span class="ipsIcon ipsIcon--fa" data-label="cart-shopping" aria-hidden="true"><i class="fa-solid fa-cart-shopping"></i></span>'
					] ] ),
					'children'	=> array(
						array( 'key' => 'Store' ),
						array( 'key' => 'Gifts' ),
						array( 'key' => 'Subscriptions' ),
						array( 'key' => 'Donations' ),
						array( 'key' => 'Orders' ),
						array( 'key' => 'Purchases' ),
						array(
							'app'			=> 'core',
							'key'			=> 'Menu',
							'title'		=> 'default_menu_item_my_details',
							'children'	=> array(
								array( 'key' => 'Info' ),
								array( 'key' => 'Addresses' ),
								array( 'key' => 'Cards' ),
								array( 'key' => 'BillingAgreements' ),
								array( 'key' => 'Credit' ),
								array( 'key' => 'Alternatives' ),
								array( 'key' => 'Referrals' ),
							)
						)
					),
				)
			),
			'browseTabs'	=> array(),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}

	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public function convertLegacyParameters() : void
	{
		/* Support legacy subscriptions */
		if( isset( Request::i()->app ) AND Request::i()->app == 'subscriptions' )
		{
			/* Redirecting isn't necessary, we just need to route the payment to the appropriate area.
				@see \IPS\nexus\Application */
			Request::i()->app			= 'nexus';
			Request::i()->module		= 'payments';
			Request::i()->section		= 'receive';	/* It actually looks for section=receive, so make sure we set that */
			Request::i()->controller	= 'receive';	/* We set this just to be complete in case anywhere else only looks at controller */
			Request::i()->validate		= 'paypal';
		}
	}

	/**
	 * Returns a list of essential cookies which are set by this app.
	 * Wildcards (*) can be used at the end of cookie names for PHP set cookies.
	 *
	 * @return string[]
	 */
	public function _getEssentialCookieNames(): array
	{
		return [ 'cm_reg', 'location', 'currency', 'guestTransactionKey' ];
	}

	/**
	 * Retrieve additional form fields for adding an extension
	 * This should return an array where the key is the tag in
	 * the extension stub that will be replaced, and the value is
	 * the form field
	 *
	 * @param string $extensionType
	 * @param string $appKey The application creating the extension
	 * @return array
	 */
	public function extensionHelper( string $extensionType, string $appKey ) : array
	{
		$return = [];

		if( $extensionType == 'Item' )
		{
			$return[ '{itemType}' ] = new Select( 'extension_invoice_item_type', null, true, [
				'options' => [
					'Purchase' => 'Purchase',
					'Charge' => 'Charge'
				]
			]);
			$return[ '{type}' ] = new Text( 'extension_item_key', null, true, [], function( $val ) use ( $appKey ){
				foreach( SystemApplication::load( $appKey )->extensions( 'nexus', 'Item', false ) as $ext )
				{
					if( $ext::$type == $val )
					{
						throw new DomainException( 'err_duplicate_item_key' );
					}
				}
			} );
		}

		return $return;
	}

	/**
	 * Output CSS files
	 *
	 * @return void
	 */
	public static function outputCss() : void
	{
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'nexus.css', 'nexus', 'front' ) );
		}
	}
}