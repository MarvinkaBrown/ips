<?php
/**
 * @brief		View Cart
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus\modules\front\store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\GeoLocation;
use IPS\Http\Url;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function in_array;
use const IPS\CACHE_PAGE_TIMEOUT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View Cart
 */
class cart extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'store.css', 'nexus' ) );

		parent::execute();
	}
	
	/**
	 * View Cart
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Init */
		if ( !isset( $_SESSION['cart'] ) )
		{
			$_SESSION['cart'] = array();
		}
		$currency = ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency();

		/* Display */
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->title = Member::loggedIn()->language()->addToStack('your_cart');
		Output::i()->output = Theme::i()->getTemplate('store')->cart( ( isset( Request::i()->cookie['location'] ) and Request::i()->cookie['location'] ) ? GeoLocation::buildFromJson( Request::i()->cookie['location'] ) : NULL, $currency );
	}
	
	/**
	 * Update Quantities
	 *
	 * @return	void
	 */
	protected function quantities() : void
	{
		Session::i()->csrfCheck();
		
		foreach ( Request::i()->item as $k => $v )
		{
			/* Get item */			
			$item = $_SESSION['cart'][ $k ];
			$package = Package::load( $item->id );
			
			/* Are any others just a duplicate for discount purposes? If so, condense them into this one */
			foreach ( $_SESSION['cart'] as $_k => $_item )
			{
				if ( $_k != $k and $_item->id == $package->id )
				{
					$cloned = clone $_item;
					$cloned->quantity = $item->quantity;
					$cloned->price = $item->price;
					if ( $cloned == $item )
					{
						$v += $_item->quantity;
						unset( $_SESSION['cart'][ $_k ] );
						Db::i()->update( 'nexus_cart_uploads', array( 'item_id' => $k ), array( 'session_id=? AND item_id=?', Session::i()->id, $_k ) );
					}
				}
			}

			/* Subscriptions can only have 1 */			
			if ( $package->subscription and $v > 1 )
			{
				Output::i()->error( 'err_subscription_qty', '1X214/4', 403, '' );
			}
			
			/* Set the quantity back to 0 and "re-add" the item */
			$item->quantity = 0;
			if ( $v )
			{				
				$data = $package->optionValuesStockAndPrice( $package->optionValues( $item->details ) );
				if ( $data['stock'] != -1 and ( $data['stock'] - $item->quantity ) < $v )
				{
					Output::i()->error( Member::loggedIn()->language()->addToStack( 'not_enough_in_stock', FALSE, array( 'sprintf' => array( $data['stock'] - $item->quantity ) ) ), '1X214/3', 403, '' );
				}
				
				$package->addItemsToCartData( $item->details, $v, $item->renewalTerm, $item->parent );
			}
			else
			{
				foreach ( $_SESSION['cart'] as $k2 => $_item )
				{
					if ( $_item->parent === $k )
					{
						unset( $_SESSION['cart'][ $k2 ] );
					}
				}
			}
			
			/* And if the quantity is 0, remove it */
			if ( $_SESSION['cart'][ $k ]->quantity == 0 )
			{
				unset( $_SESSION['cart'][ $k ] );
				
				$ids = array();
				foreach ( Db::i()->select( 'id', 'nexus_cart_uploads', array( 'session_id=? AND item_id=?', Session::i()->id, $k ) ) as $id )
				{
					File::unclaimAttachments( 'nexus_Purchases', $id, NULL, 'cart' );
					$ids[] = $id;
				}
				Db::i()->delete( 'nexus_cart_uploads', Db::i()->in( 'id', $ids ) );
			}
		}

		if ( empty( $_SESSION['cart'] ) and CACHE_PAGE_TIMEOUT and !Member::loggedIn()->member_id )
		{
			Request::i()->setCookie( 'noCache', 0, DateTime::ts( time() - 86400 ) );
		}
			
		if ( Request::i()->isAjax() )
		{
			$currency = ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency();
			Output::i()->sendOutput( Theme::i()->getTemplate('store')->cartContents( ( isset( Request::i()->cookie['location'] ) and Request::i()->cookie['location'] ) ? GeoLocation::buildFromJson( Request::i()->cookie['location'] ) : NULL, $currency ) );
		}
		else
		{
			Output::i()->redirect( Url::internal('app=nexus&module=store&controller=cart', 'front', 'store_cart' ) );
		}
	}
	
	/**
	 * Empty Cart
	 *
	 * @return	void
	 */
	protected function clear() : void
	{
		Session::i()->csrfCheck();
		
		$_SESSION['cart'] = array();
		$ids = array();
		foreach ( Db::i()->select( 'id', 'nexus_cart_uploads', array( 'session_id=?', Session::i()->id ) ) as $id )
		{
			File::unclaimAttachments( 'nexus_Purchases', $id, NULL, 'cart' );
			$ids[] = $id;
		}
		Db::i()->delete( 'nexus_cart_uploads', Db::i()->in( 'id', $ids ) );

		if ( CACHE_PAGE_TIMEOUT and !Member::loggedIn()->member_id )
		{
			Request::i()->setCookie( 'noCache', 0, DateTime::ts( time() - 86400 ) );
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( $_SESSION['cart'] );
		}
		else
		{
			Output::i()->redirect( Url::internal('app=nexus&module=store&controller=cart', 'front', 'store_cart' ) );
		}
	}
	
	/**
	 * Checkout
	 *
	 * @return	void
	 */
	protected function checkout() : void
	{
		Session::i()->csrfCheck();
		
		$currency = ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency();
		
		$canRegister = ( !Settings::i()->nexus_reg_force or Member::loggedIn()->member_id );
		
		$tempInvoiceKey = md5( mt_rand() );
		
		$invoice = new Invoice;
		$invoice->member = Customer::loggedIn();
		$invoice->currency = $currency;
		foreach ( $_SESSION['cart'] as $k => $item )
		{
			if ( !$canRegister and Package::load( $item->id )->reg )
			{
				$canRegister = TRUE;
			}
						
			$itemId = $invoice->addItem( $item, $k );
			
			$ids = array();
			foreach ( Db::i()->select( 'id', 'nexus_cart_uploads', array( 'session_id=? AND item_id=?', Session::i()->id, $k ) ) as $id )
			{
				Db::i()->update( 'core_attachments_map', array( 'id1' => $itemId, 'id3' => "invoice-{$tempInvoiceKey}" ), array( 'location_key=? AND id1=? AND id3=?', 'nexus_Purchases', $id, 'cart' ) );
				$ids[] = $id;
			}
			Db::i()->delete( 'nexus_cart_uploads', Db::i()->in( 'id', $ids ) );
		}
		
		if ( !count( $invoice->items ) )
		{
			Output::i()->error( 'your_cart_empty', '2X214/2', 403, '' );
		}
		
		if ( !$canRegister )
		{
			Output::i()->redirect( Url::internal( 'app=nexus&module=store&controller=store&do=register', 'front', 'store' ) );
		}
				
		if ( $minimumOrderAmounts = json_decode( Settings::i()->nexus_minimum_order, TRUE ) and ( new Number( number_format( $minimumOrderAmounts[ $currency ]['amount'], 2, '.', '' ) ) )->compare( $invoice->total->amount ) === 1 )
		{
			Output::i()->error( Member::loggedIn()->language()->addToStack( 'err_minimum_order', FALSE, array( 'sprintf' => array( new Money( $minimumOrderAmounts[ $currency ]['amount'], $currency ) ) ) ), '1X214/1', 403, '' );
		}
				
		$_SESSION['cart'] = array();

		if ( CACHE_PAGE_TIMEOUT and !Member::loggedIn()->member_id )
		{
			Request::i()->setCookie( 'noCache', 0, DateTime::ts( time() - 86400 ) );
		}

		$invoice->save();
		Db::i()->update( 'core_attachments_map', array( 'id3' => "invoice-{$invoice->id}" ), array( 'location_key=? AND id3=?', 'nexus_Purchases', "invoice-{$tempInvoiceKey}" ) );
				
		Output::i()->redirect( $invoice->checkoutUrl() );
	}
}