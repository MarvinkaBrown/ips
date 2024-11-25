<?php
namespace IPS\Theme;
class class_nexus_front_store extends \IPS\Theme\Template
{	function cart( $location, $currency ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class=''>
	<div class='i-text-align_center i-padding_3'>
		<i class='i-font-size_6 fa-solid fa-cart-shopping'></i>
		<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'your_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	</div>

	<div data-controller='nexus.front.store.cartReview'>
		<div data-role="cart">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->cartContents( $location, $currency );
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function cartContents( $location, $currency ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$subtotal = new \IPS\Math\Number('0');
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $_SESSION['cart'] ) ):
$return .= <<<IPSCONTENT

	<div id='elNexusCart' class='ipsBox'>
		<i-data>
			<div class="ipsData ipsData--table ipsData--cart-contents">
				
IPSCONTENT;

foreach ( $_SESSION['cart'] as $id => $item ):
$return .= <<<IPSCONTENT

					<div id='elCartItem_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsData__item">
						<figure class='ipsData__image'>
							
IPSCONTENT;

if ( $image = $item->image() ):
$return .= <<<IPSCONTENT

								<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
							
IPSCONTENT;

elseif ( $icon = $item::$icon ):
$return .= <<<IPSCONTENT

								<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</figure>
						<div class='ipsData__content'>
							<div class='ipsData__main'>
								<h2 class='ipsData__title'>
IPSCONTENT;

if ( $item->canChangeQuantity() ):
$return .= <<<IPSCONTENT
<span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 x</span> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
								{$item->detailsForDisplay( 'checkout' )}
								<div class='ipsData__meta'>
									<ul class='ipsList ipsList--inline i-font-size_-2'>
										<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart&do=quantities&item[$id]=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_from_cart_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action='removeFromCart'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_from_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

if ( $item->canChangeQuantity() ):
$return .= <<<IPSCONTENT
<li><a href='#elItemRow
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_quantity_menu' data-ipsMenu data-ipsMenu-appendTo='#elCartItem_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsMenu-closeOnClick='false' id='elItemRow
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_quantity' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_quantity_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_quantity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a></li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</ul>
									<div class='i-padding_2 ipsHide ipsMenu' id='elItemRow
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_quantity_menu'>
										<form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart&do=quantities" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post" data-role='quantityForm' class="i-flex i-gap_2">
											<input class="ipsInput ipsInput--number" type='number' name="item[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' size="6" min="0">
											<button type='submit' class='ipsButton ipsButton--primary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'update', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
										</form>
									</div>
								</div>
							</div>
							<div class='i-basis_180'>
								<span class='cNexusPrice i-font-size_2'>
									
IPSCONTENT;

if ( \IPS\Settings::i()->nexus_show_tax ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$location = $location ?: \IPS\nexus\Customer::loggedIn()->estimatedLocation();
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$itemPrice = $item->grossPrice( $location );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$linePrice = $item->grossLinePrice( $location );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$itemPrice = $item->price;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$linePrice = $item->linePrice();
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $linePrice, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$subtotal = $subtotal->add( $linePrice->amount );
$return .= <<<IPSCONTENT

								</span>
								
IPSCONTENT;

if ( $item->renewalTerm ):
$return .= <<<IPSCONTENT

									<p class='i-font-size_-2 i-color_soft'>
										
IPSCONTENT;

$sprintf = array($item->renewalTerm->toDisplay( NULL, $item->quantity )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_renewal', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

									</p>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $item->quantity > 1 ):
$return .= <<<IPSCONTENT

									<p class='i-font-size_-2 i-color_soft'>
										
IPSCONTENT;

if ( $item->renewalTerm ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$sprintf = array($itemPrice, $item->renewalTerm); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'each_short_with_renewal', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$sprintf = array($itemPrice); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'each_short', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</p>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</div>
					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<hr class='ipsHr'>
				<div class='cNexusCart_totals i-text-align_end'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cart_subtotal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: <span class='cNexusPrice'>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $subtotal, $currency );
$return .= <<<IPSCONTENT
</span>
				</div>
			</div>
		</i-data>
	</div>
	<ul class="ipsButtons ipsButtons--main i-margin-block_block">
		<li>
			<a class="ipsButton ipsButton--text" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart&do=clear" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="" data-confirm><i class='fa-solid fa-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'empty_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</li>
		<li>
			<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart&do=checkout" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="" data-action="checkout">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'checkout', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<i class='fa-solid fa-chevron-right'></i></a>
		</li>
		<li>
			<a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store", null, "store", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title=""><i class='fa-solid fa-chevron-left'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_shopping', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</li>
	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='ipsBox i-padding_3 i-text-align_center i-font-size_2 i-color_soft'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'your_cart_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		<br><br>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store", null, "store", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start_shopping', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start_shopping', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function cartHeader(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$location = NULL;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $_SESSION['cart'] ) and \count( $_SESSION['cart'] ) and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'nexus', 'store' ) ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$subtotal = new \IPS\Math\Number('0');
$return .= <<<IPSCONTENT

	<li data-el='cart' id='elCart_container'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsUserNav__link" id='elCart' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'your_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsMenu data-ipsMenu-closeOnClick='false'>
			<i class="fa-solid fa-basket-shopping ipsUserNav__icon"></i>
			<span class="ipsUserNav__text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			<span class='ipsNotification'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\nexus\Application::cartCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		</a>
		<div id='elCart_menu' class='ipsMenu ipsMenu_wide ipsHide'>
			<div class="ipsMenu_headerBar"><h4 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'your_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4></div>
			<div class='ipsMenu_innerContent'>
				<i-data>
					<ul class="ipsData ipsData--table ipsData--compact ipsData--cart-dropdown" data-role='cartList' id='elCartContent'>
						
IPSCONTENT;

foreach ( $_SESSION['cart'] as $id => $item ):
$return .= <<<IPSCONTENT

							<li class='ipsData__item'>
								<figure class='ipsData__image'>
									
IPSCONTENT;

if ( $image = $item->image() ):
$return .= <<<IPSCONTENT

										<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
									
IPSCONTENT;

elseif ( $icon = $item::$icon ):
$return .= <<<IPSCONTENT

										<i class='fa fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<i></i>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</figure>
								<div class='ipsData__main'>
									<div class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
									<p class="ipsData__meta">&times;
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
								</div>
								<div class='cNexusPrice'>
									<strong>
										
IPSCONTENT;

if ( \IPS\Settings::i()->nexus_show_tax ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$location = $location ?: \IPS\nexus\Customer::loggedIn()->estimatedLocation();
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$linePrice = $item->grossLinePrice( $location );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$linePrice = $item->linePrice();
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $linePrice, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$subtotal = $subtotal->add( $linePrice->amount );
$return .= <<<IPSCONTENT

									</strong>
									
IPSCONTENT;

if ( $item->quantity > 1 ):
$return .= <<<IPSCONTENT

										<p class='i-font-size_-2 i-color_soft'>
											
IPSCONTENT;

$sprintf = array($item->price); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'each_short', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

										</p>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						<li class='ipsData__item cNexusMenuCart_totalRow'>
							<div class='ipsData__main i-font-size_2 i-text-align_end'>
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subtotal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</div>
							<div class='i-basis_100 cNexusPrice i-font-size_2 i-text-align_end'>
								<strong>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $subtotal, ( ( isset( \IPS\Request::i()->cookie['currency'] ) and \in_array( \IPS\Request::i()->cookie['currency'], \IPS\nexus\Money::currencies() ) ) ? \IPS\Request::i()->cookie['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency() ) );
$return .= <<<IPSCONTENT
</strong>
							</div>
						</li>
					</ul>
				</i-data>
			</div>
			<div class='ipsMenu_footerBar i-text-align_center'>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_and_checkout', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</li>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<li class='ipsHide' id='elCart_container'></li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function cartHeaderMobile(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li data-el='cart' id="elCart_mobileHeaderContainer">
    <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsMobileNavIcons__button">
        <i class="fa-solid fa-basket-shopping"></i>
        <span class='ipsNotification'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\nexus\Application::cartCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
        <span class='ipsInvisible'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'your_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
    </a>
</li>
IPSCONTENT;

		return $return;
}

	function cartReview( $package, $quantity, $upsell ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pixel( array( 'AddToCart' => array( 'content_type' => 'product', 'content_ids' => array( $package->id ) ) ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Widget\Request::i()->registerCheckout ):
$return .= <<<IPSCONTENT

	<div class='i-text-align_center i-padding_3 i-color_soft i-font-size_2 ipsBox'>
		
IPSCONTENT;

$sprintf = array($package->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'package_added_to_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		<br><br>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_to_registration', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='i-padding_3'>
	 	<div class='i-background_2 i-padding_3 i-text-align_center i-margin-bottom_3'>
			<h3 class='ipsTitle ipsTitle--h3 i-text-align_center i-margin-bottom_2 i-color_positive'><i class='fa-regular fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_added_to_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			<h4 class='i-font-size_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'quantity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
		</div>
		<ul class='ipsSpanGrid'>
			<li class='ipsSpanGrid__6'>
				<a href='#' data-action='dialogClose' class='ipsButton ipsButton--wide ipsButton--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_shopping', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
			<li class='ipsSpanGrid__6'>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_cart_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--wide ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_and_checkout', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		</ul>
		
IPSCONTENT;

if ( \count( $upsell ) ):
$return .= <<<IPSCONTENT

			<hr class='ipsHr'>
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'related_products_you_might_like', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<ul class='ipsCarousel ipsCarousel--commerce-related-products i-basis_300' id='commerce-related-products' tabindex="0">
				
IPSCONTENT;

foreach ( $upsell as $upsellPackage ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->packageBlock( $upsellPackage, 'carousel' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'commerce-related-products' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function category( $category, $subcategories, $packages, $pagination, $packagesWithCustomFields, $totalCount ) {
		$return = '';
		$return .= <<<IPSCONTENT


<header class="ipsPageHeader ipsBox">
	<h1 class="ipsPageHeader__title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
	
IPSCONTENT;

if ( $category->description ):
$return .= <<<IPSCONTENT

		<div class="ipsPageHeader__desc">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $category->description, array('') );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
		
</header>


IPSCONTENT;

if ( \count( $subcategories ) ):
$return .= <<<IPSCONTENT

	<section class='ipsBox ipsPull ipsBox--padding'>
		<ul class='ipsGrid ipsGrid--commerce-subcategories i-basis_280 i-text-align_center'>
			
IPSCONTENT;

foreach ( $subcategories as $group ):
$return .= <<<IPSCONTENT

				<li>
					<figure class='ipsFigure'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main i-aspect-ratio_6'>
							
IPSCONTENT;

if ( $group->image ):
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( '(', ')' ), array( '\(', '\)' ), $group->image ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="ipsFigure__icon"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<span class="ipsInvisible">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						</a>
						<figcaption class="ipsFigure__footer">
							<h3 class='i-font-size_2 i-font-weight_600'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
						</figcaption>
					</figure>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</section>
	<br>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div data-role="packageListContainer">
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->categoryContents( $category, $subcategories, $packages, $pagination, $packagesWithCustomFields, $totalCount );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function categoryContents( $category, $subcategories, $packages, $pagination, $packagesWithCustomFields, $totalCount ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $packages ) ):
$return .= <<<IPSCONTENT

	<section class='ipsBox'>
		<h2 class='ipsBox__header'>
IPSCONTENT;

$pluralize = array( $totalCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'products_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="ipsButtonBar ipsButtonBar--top">
			
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

				<div class='ipsButtonBar__pagination'>{$pagination}</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar__end">
				<ul class="ipsDataFilters">
					<li>
						<a href="#elSortByMenu_menu" id="elSortByMenu_products" class="ipsDataFilters__button" data-role="sortButton" data-ipsMenu data-ipsMenu-activeclass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
						<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elSortByMenu_products_menu">
							
IPSCONTENT;

foreach ( array( 'default', 'name', 'price_low', 'price_high', 'rating' ) as $k ):
$return .= <<<IPSCONTENT

								<li class="ipsMenu_item 
IPSCONTENT;

if ( ( \IPS\Widget\Request::i()->sortby and \IPS\Widget\Request::i()->sortby == $k ) or ( !\IPS\Widget\Request::i()->sortby and $k === 'default' ) ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsmenuvalue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;

if ( $k === 'default' ):
$return .= <<<IPSCONTENT

										<a data-action="filter" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<a data-action="filter" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url()->setQueryString( 'sortby', $k ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$val = "products_sort_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									</a>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</li>
				</ul>
			</div>			
		</div>
		
IPSCONTENT;

if ( \count( $packages ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue( 'store_view' ) == 'list' ):
$return .= <<<IPSCONTENT

				<i-data>
					<ol class="ipsData ipsData--table ipsData--commerce-products" data-role="packageList">
						
IPSCONTENT;

foreach ( $packages as $package ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->packageRow( $package, \in_array( $package->id, $packagesWithCustomFields ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ol>
				</i-data>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i-data>
					<ol class="ipsData ipsData--grid ipsData--commerce-products" data-role="packageList">
						
IPSCONTENT;

foreach ( $packages as $package ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->packageBlock( $package, 'grid' );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ol>
				</i-data>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( trim( $pagination ) != '' ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				<div class="ipsButtonBar__pagination">
					{$pagination}
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</section>

IPSCONTENT;

elseif ( ( !\count( $subcategories ) ) ):
$return .= <<<IPSCONTENT

	<div class="ipsEmptyMessage" data-role="packageList">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'store_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function categorySidebar( $category=NULL, $subcategories=NULL, $url=NULL, $havePackages=FALSE, $currency=NULL, $havePackagesWhichAcceptReviews = FALSE, $havePackagesWhichUseStockLevels = FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-role="categorySidebar">
	
IPSCONTENT;

if ( !\IPS\nexus\Package\Group::theOnlyNode( array(), TRUE, FALSE ) ):
$return .= <<<IPSCONTENT

	<div class='ipsWidget' id='elNexusCategoriesBox'>
		<h2 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class='ipsWidget__content i-padding_2'>
			
IPSCONTENT;

if ( $subcategories !== NULL AND \count( $subcategories ) ):
$return .= <<<IPSCONTENT

				<div class='ipsSideMenu ipsSideMenu--truncate ipsSideMenu--pseudoRadios' id='elCategories_menu'>
					<h3 class='ipsSideMenu__view'>
						<a href='#elCategories_menu' data-action='openSideMenu'><i class='fa-solid fa-bars'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					</h3>
					<div class="ipsSideMenu__menu">
						<div class='cNexusCategoriesBox_back'>
							
IPSCONTENT;

if ( $category && $category->parent() ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->parent()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--inherit'><i class="fa-solid fa-arrow-left-long"></i><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->parent()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=store", null, "store", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--inherit'><i class="fa-solid fa-arrow-left-long"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all_categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<hr class='ipsHr'>
						<h4 class='ipsSideMenu__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
						<ul class='ipsSideMenu__list'>
							
IPSCONTENT;

foreach ( $subcategories as $idx => $subcategory ):
$return .= <<<IPSCONTENT

								<li>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item'><span class='ipsSideMenu__toggle'></span><span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
									
IPSCONTENT;

if ( $subcategory->hasSubgroups() ):
$return .= <<<IPSCONTENT

										<ul class='ipsSideMenu__list'>
											
IPSCONTENT;

foreach ( $subcategory->children( 'view', NULL, FALSE ) as $cidx => $child ):
$return .= <<<IPSCONTENT

												<li>
													
IPSCONTENT;

if ( $cidx >= 5 ):
$return .= <<<IPSCONTENT

														<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item'>
															<span class='i-color_soft i-font-size_-2'>
IPSCONTENT;

$pluralize = array( \count( $subcategory->children( 'view', NULL, FALSE ) ) - 5 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
														</a>
														
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

													
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

														<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'><span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												</li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</div>
			
IPSCONTENT;

elseif ( $category && $category->parent() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$siblings = $category->parent()->children( 'view', NULL, FALSE );
$return .= <<<IPSCONTENT

				<div class='ipsSideMenu ipsSideMenu--truncate ipsSideMenu--pseudoRadios' id='elCategories_menu'>
					<h3 class='ipsSideMenu__view'>
						<a href='#elCategories_menu' data-action='openSideMenu'><i class='fa-solid fa-bars'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					</h3>
					<div class="ipsSideMenu__menu">
						<div class='cNexusCategoriesBox_back'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->parent()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--inherit'><i class="fa-solid fa-arrow-left-long"></i><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->parent()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						</div>
						<hr class='ipsHr'>
						<h4 class='ipsSideMenu__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->parent()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
						<ul class='ipsSideMenu__list'>
							
IPSCONTENT;

foreach ( $siblings as $idx => $sibling ):
$return .= <<<IPSCONTENT

								<li>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $sibling->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item 
IPSCONTENT;

if ( $category && $category == $sibling ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><span class='ipsSideMenu__toggle'></span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $sibling->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

if ( $sibling instanceof \IPS\nexus\Package\Group && $sibling->hasSubgroups() ):
$return .= <<<IPSCONTENT

										<ul class='ipsSideMenu__list'>
											
IPSCONTENT;

$cidx = 0;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

foreach ( $sibling->children( 'view', NULL, FALSE ) as $child ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( $child->hasPackages( NULL, array(), TRUE ) OR $child->hasSubgroups() ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$cidx++;
$return .= <<<IPSCONTENT

													<li>
														
IPSCONTENT;

if ( $cidx >= 5 ):
$return .= <<<IPSCONTENT

															<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $sibling->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item'><span class='i-color_soft i-font-size_-2'>
IPSCONTENT;

$pluralize = array( \count( $sibling->children( 'view', NULL, FALSE ) ) - 5 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></a>
															
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

														
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

															<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'><span class='ipsSideMenu__toggle'></span><span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
														
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													</li>
												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class='ipsSideMenu ipsSideMenu--truncate ipsSideMenu--pseudoRadios' id='elCategories_menu'>
					<h3 class='ipsSideMenu__view'>
						<a href='#elCategories_menu' data-action='openSideMenu'><i class='fa-solid fa-bars'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					</h3>
					<div class="ipsSideMenu__menu">
						<ul class='ipsSideMenu__list'>
							
IPSCONTENT;

foreach ( \IPS\nexus\Package\Group::rootsWithViewablePackages() as $group ):
$return .= <<<IPSCONTENT

								<li>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item 
IPSCONTENT;

if ( $category && $category == $group ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><span class='ipsSideMenu__toggle'></span><span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
									
IPSCONTENT;

if ( $group->hasSubgroups() ):
$return .= <<<IPSCONTENT

										<ul class='ipsSideMenu__list'>
											
IPSCONTENT;

$idx = 0;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

foreach ( $group->children( 'view', NULL, FALSE ) as $child ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( $child->hasPackages( NULL, array(), TRUE ) OR $child->hasSubgroups() ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$idx++;
$return .= <<<IPSCONTENT

													<li>
														
IPSCONTENT;

if ( $idx >= 5 ):
$return .= <<<IPSCONTENT

															<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item'><span class='i-color_soft i-font-size_-2'>
IPSCONTENT;

$pluralize = array( \count( $group->children( 'view', NULL, FALSE ) ) - 5 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></a>
															
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

														
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

															<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'><span class='ipsSideMenu__toggle'></span><span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $child->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
														
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													</li>
												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

if ( $category and ( $havePackages or \IPS\Widget\Request::i()->filter or \IPS\Widget\Request::i()->minPrice or \IPS\Widget\Request::i()->maxPrice or \IPS\Widget\Request::i()->minRating or \IPS\Widget\Request::i()->inStock ) ):
$return .= <<<IPSCONTENT

	<div class='ipsWidget' id='elNexusFiltersBox'>
		
		<h2 class='ipsWidget__header'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'store_filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Widget\Request::i()->filter or \IPS\Widget\Request::i()->minPrice or \IPS\Widget\Request::i()->maxPrice or \IPS\Widget\Request::i()->minRating or \IPS\Widget\Request::i()->inStock ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="filter" class="ipsWidget__header-secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'store_clear_filters', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h2>
		<div class='ipsWidget__content i-padding_2'>
			
IPSCONTENT;

foreach ( $category->filters( \IPS\Member::loggedIn()->language() ) as $filterId => $values ):
$return .= <<<IPSCONTENT

				<div class="ipsSideMenu ipsSideMenu--truncate ipsSideMenu--pseudoRadios">
					<h4 class='ipsSideMenu__title'>
IPSCONTENT;

$val = "nexus_product_filter_{$filterId}_public"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<ul class='ipsSideMenu__list'>
						
IPSCONTENT;

foreach ( $values as $valueId => $value ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

$active = ( isset( \IPS\Widget\Request::i()->filter[ $filterId ] ) and \in_array( $valueId, explode( ',', \IPS\Widget\Request::i()->filter[ $filterId ] ) ) );
$return .= <<<IPSCONTENT

								<a data-action="filter" href='
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'filter', \IPS\nexus\Package\Filter::queryString( \IPS\Request::i()->filter, $filterId, NULL, $valueId ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'filter', \IPS\nexus\Package\Filter::queryString( \IPS\Request::i()->filter, $filterId, $valueId ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item 
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									<span class="ipsSideMenu__toggle"></span>
									<span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<div class="ipsSideMenu ipsSideMenu--truncate ipsSideMenu--pseudoRadios">
				<h4 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				<ul class='ipsSideMenu__list'>
					
IPSCONTENT;

$haveCategoryPriceFilters = ( $category->price_filters and $priceFilters = json_decode( $category->price_filters, TRUE ) and isset( $priceFilters[ $currency ] ) and \count( $priceFilters[ $currency ] ) ); $lastAmount = 0; $activePriceFilter = FALSE;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $haveCategoryPriceFilters ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $priceFilters[ $currency ] as $amount ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

if ( $lastAmount ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( \IPS\Widget\Request::i()->minPrice and \IPS\Widget\Request::i()->maxPrice and \IPS\Widget\Request::i()->minPrice == $lastAmount and \IPS\Widget\Request::i()->maxPrice == $amount ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$activePriceFilter = TRUE;
$return .= <<<IPSCONTENT

										<a data-action="filter" href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'minPrice', NULL )->setQueryString( 'maxPrice', NULL ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item ipsSideMenu_itemActive'>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<a data-action="filter" href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'minPrice', $lastAmount )->setQueryString( 'maxPrice', $amount ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item'>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										<span class="ipsSideMenu__toggle"></span>
										<span class='ipsSideMenu__text'>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $lastAmount, $currency );
$return .= <<<IPSCONTENT
&ndash;
IPSCONTENT;

$return .= new \IPS\nexus\Money( $amount, $currency );
$return .= <<<IPSCONTENT
</span>
									</a>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( \IPS\Widget\Request::i()->maxPrice and \IPS\Widget\Request::i()->maxPrice == $amount and !\IPS\Widget\Request::i()->minPrice ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$activePriceFilter = TRUE;
$return .= <<<IPSCONTENT

										<a data-action="filter" href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'maxPrice', NULL )->setQueryString( 'minPrice', NULL ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item ipsSideMenu_itemActive'>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<a data-action="filter" href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'maxPrice', $amount )->setQueryString( 'minPrice', NULL ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item'>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										<span class="ipsSideMenu__toggle"></span>
										<span class='ipsSideMenu__text'>
IPSCONTENT;

$return .= new \IPS\nexus\Money( 0, $currency );
$return .= <<<IPSCONTENT
&ndash;
IPSCONTENT;

$return .= new \IPS\nexus\Money( $amount, $currency );
$return .= <<<IPSCONTENT
</span>
									</a>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</li>
							
IPSCONTENT;

$lastAmount = $amount;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

if ( \IPS\Widget\Request::i()->minPrice and \IPS\Widget\Request::i()->minPrice == $lastAmount and !\IPS\Widget\Request::i()->maxPrice ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$activePriceFilter = TRUE;
$return .= <<<IPSCONTENT

								<a data-action="filter" href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'minPrice', NULL )->setQueryString( 'maxPrice', NULL ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item ipsSideMenu_itemActive'>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<a data-action="filter" href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'minPrice', $lastAmount )->setQueryString( 'maxPrice', NULL ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item'>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<span class="ipsSideMenu__toggle"></span>
								<span class='ipsSideMenu__text'>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $lastAmount, $currency );
$return .= <<<IPSCONTENT
+</span>
							</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$activePriceFilter and ( \IPS\Widget\Request::i()->minPrice or \IPS\Widget\Request::i()->maxPrice ) ):
$return .= <<<IPSCONTENT

						<li>
							<a data-action="filter" href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'minPrice', NULL )->setQueryString( 'maxPrice', NULL ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item ipsSideMenu_itemActive'>
								<span class="ipsSideMenu__toggle"></span>
								
IPSCONTENT;

if ( \IPS\Widget\Request::i()->minPrice and \IPS\Widget\Request::i()->maxPrice ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= new \IPS\nexus\Money( \IPS\Request::i()->minPrice, $currency );
$return .= <<<IPSCONTENT
&ndash;
IPSCONTENT;

$return .= new \IPS\nexus\Money( \IPS\Request::i()->maxPrice, $currency );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

elseif ( \IPS\Widget\Request::i()->minPrice ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= new \IPS\nexus\Money( \IPS\Request::i()->minPrice, $currency );
$return .= <<<IPSCONTENT
+
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= new \IPS\nexus\Money( 0, $currency );
$return .= <<<IPSCONTENT
&ndash;
IPSCONTENT;

$return .= new \IPS\nexus\Money( \IPS\Request::i()->maxPrice, $currency );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</a>
						</li>				
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'do', 'priceFilter' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size="narrow" data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'price_filter_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'>
							<span class="ipsSideMenu__toggle"></span>
							<span class='ipsSideMenu__text'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_price_filter', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</a>
					</li>				
				</ul>
			</div>
			
IPSCONTENT;

if ( $havePackagesWhichAcceptReviews ):
$return .= <<<IPSCONTENT

				<div class="ipsSideMenu ipsSideMenu--truncate ipsSideMenu--pseudoRadios">
					<h4 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'minRating', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<ul class='ipsSideMenu__list'>
						
IPSCONTENT;

foreach ( range( 5, 1 ) as $minRating ):
$return .= <<<IPSCONTENT

							<li>
								<a data-action="filter" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'minRating', \IPS\Request::i()->minRating == $minRating ? NULL : $minRating ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->minRating and \IPS\Widget\Request::i()->minRating == $minRating ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									<span class="ipsSideMenu__toggle"></span>
									<span class='ipsSideMenu__text'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'large', $minRating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT
</span>
								</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $havePackagesWhichUseStockLevels ):
$return .= <<<IPSCONTENT

				<div class="ipsSideMenu ipsSideMenu--truncate ipsSideMenu--pseudoRadios">
					<h4 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<ul class='ipsSideMenu__list'>
						<li>
							<a data-action="filter" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'inStock', \IPS\Request::i()->inStock ? NULL : 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->inStock ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
								<span class="ipsSideMenu__toggle"></span>
								<span class='ipsSideMenu__text'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in_stock_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</a>
						</li>
					</ul>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->chooseCurrency( $category ? $category->url() : \IPS\Http\Url::internal('app=nexus&module=store&controller=store', 'front', 'store') );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function chooseCurrency( $baseUrl ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( \IPS\nexus\Money::currencies() ) > 1 ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$memberCurrency = ( ( isset( \IPS\Widget\Request::i()->cookie['currency'] ) and \in_array( \IPS\Widget\Request::i()->cookie['currency'], \IPS\nexus\Money::currencies() ) ) ? \IPS\Widget\Request::i()->cookie['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency() );
$return .= <<<IPSCONTENT

	<div class='i-text-align_center ipsBox i-padding_3 i-margin-top_2' 
IPSCONTENT;

if ( isset( $_SESSION['cart'] ) and \count( $_SESSION['cart'] ) ):
$return .= <<<IPSCONTENT
data-controller="nexus.front.store.currencySelect"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_prices_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><br>
		<ul class='ipsList ipsList--inline i-link-color_inherit'>
			
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

if ( $currency == $memberCurrency ):
$return .= <<<IPSCONTENT

						<a data-action="filter" href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( 'currency', $currency )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($currency); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_currency_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
							<strong><i class='fa-solid fa-check'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<a data-action="filter" href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( 'currency', $currency )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($currency); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_currency_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function giftCard( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class="ipsPageHeader">
	<div class="ipsPageHeader__row">
		<div class="ipsPageHeader__primary">
			<h1 class="ipsPageHeader__title">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'buy_gift_voucher', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</h1>
		</div>
		<ul class="ipsButtons">
			<li>
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=gifts&do=redeem", null, "store_giftvouchers_redeem", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'redeem_gift_voucher', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'redeem_gift_voucher', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		</ul>
	</div>
</header>

<div class='ipsBox ipsBox--padding' data-controller='nexus.front.store.giftCard' data-formatCurrencyUrl="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=gifts&do=formatCurrency", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function giftCardForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--error i-margin-bottom_3">
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--gift-card" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsForm>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<input type="hidden" name="plupload" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsSpanGrid'>
		<div class='ipsSpanGrid__6'>
			<div class="ipsColumns cNexusStep_block">
				<div class="ipsColumns__secondary i-basis_50">
					<span class="cNexusStep_step">1</span>
				</div>
				<div class="ipsColumns__primary">
					<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_personalize', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<p>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_personalize_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
			<hr class='ipsHr'>
			<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--gift-card'>
				<li class="ipsFieldRow">
					<div class="ipsFieldRow__content">	
						<input type='hidden' name='gift_voucher_color' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( '#', '', $elements['']['gift_voucher_color']->value ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						<ul class='ipsList ipsList--inline i-gap_0 cNexusGiftcard_swatches'>
							<li class='i-background_2'><a href='#' data-color='3b3b3b' style='background-color: #3b3b3b'>&nbsp;</a></li>
							<li class='i-background_2'><a href='#' data-color='16a085' style='background-color: #16a085'>&nbsp;</a></li>
							<li class='i-background_2'><a href='#' data-color='27ae60' style='background-color: #27ae60'>&nbsp;</a></li>
							<li class='i-background_2'><a href='#' data-color='2980b9' style='background-color: #2980b9'>&nbsp;</a></li>
							<li class='i-background_2'><a href='#' data-color='2c3e50' style='background-color: #2c3e50'>&nbsp;</a></li>
							<li class='i-background_2'><a href='#' data-color='8e44ad' style='background-color: #8e44ad'>&nbsp;</a></li>
							<li class='i-background_2'><a href='#' data-color='f39c12' style='background-color: #f39c12'>&nbsp;</a></li>
							<li class='i-background_2'><a href='#' data-color='d35400' style='background-color: #d35400'>&nbsp;</a></li>
							<li class='i-background_2'><a href='#' data-color='c0392b' style='background-color: #c0392b'>&nbsp;</a></li>
							<li class='i-background_2'><a href='#' data-color='7f8c8d' style='background-color: #7f8c8d'>&nbsp;</a></li>
						</ul>
					</div>
				</li>
			</ul>
			<div id='elNexusGiftcard' class='ipsInnerBox i-margin-top_3' data-role='giftCardArea'>
				<div id='elNexusGiftcard_card' data-role='giftCard'>
					<span data-role='icon'><i class='fa-solid fa-gift'></i></span>
					<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<strong data-role='siteName'>
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
</strong>
					<strong data-role='value' class='cNexusGiftcard_content'></strong>
					<span data-role='redeem' class='cNexusGiftcard_redeem ipsResponsive_hidePhone'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_redeem', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</div>
				<div class='i-padding_3' id='elNexusGiftcard_personalize'>
					<div class='cNexusGiftcard_content' data-role='to'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type='text' class='ipsInput ipsInput--text' name='gift_voucher_recipient' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['gift_voucher_recipient']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_recipient', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">,
					</div>
					<div class='cNexusGiftcard_content i-margin-block_2' data-role='message'>
						<textarea class='ipsInput ipsInput--text' name='gift_voucher_message' rows='3' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['gift_voucher_message']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
					</div>
					<div class='cNexusGiftcard_content'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type='text' class='ipsInput ipsInput--text' name='gift_voucher_sender' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['gift_voucher_sender']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_sender', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></div>
				</div>
			</div>
		</div>
		<div class='ipsSpanGrid__6'>
			<div class="ipsColumns cNexusStep_block ">
				<div class="ipsColumns__secondary i-basis_50">
					<span class="cNexusStep_step">2</span>
				</div>
				<div class="ipsColumns__primary">
					<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_choose_amount', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<p>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher_choose_amount_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
			<hr class='ipsHr'>
			<ul class='ipsForm ipsForm--vertical ipsForm--gift-card-summary'>
				
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !\in_array( $inputName, array( 'gift_voucher_color', 'gift_voucher_recipient', 'gift_voucher_message', 'gift_voucher_sender') ) ):
$return .= <<<IPSCONTENT

							{$input}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>

			<div class='i-text-align_start i-margin-top_3'>
				<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_gift_voucher_to_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function index( $credits, $newProducts, $popularProducts ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('store') );
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $credits->amount->isGreaterThanZero() ):
$return .= <<<IPSCONTENT

	<div class='ipsMessage ipsMessage--info'>
		
IPSCONTENT;

$sprintf = array($credits); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'store_credit_message', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<section class='ipsBox ipsPull'>
	<header class='ipsBox__header'>
		<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'carousel--commerce-categories' );
$return .= <<<IPSCONTENT

	</header>
	<nav class='ipsBox__content'>
		<i-data>
			<ul class='ipsData ipsData--wallpaper ipsData--carousel ipsData--commerce-categories i-basis_220 i-text-align_center' id='carousel--commerce-categories' tabindex="0">
				
IPSCONTENT;

foreach ( \IPS\nexus\Package\Group::rootsWithViewablePackages() as $group ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsLinkPanel' aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						<figure class="ipsData__image">
							
IPSCONTENT;

if ( $group->image ):
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( '(', ')' ), array( '\(', '\)' ), $group->image ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</figure>
						<div class="ipsData__content">
							<div class="ipsData__main">
								<h3 class="ipsData__title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
							</div>
						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</nav>
</section>


IPSCONTENT;

if ( \count( \IPS\nexus\Package\Item::featured() ) ):
$return .= <<<IPSCONTENT

	<section class='ipsBox ipsPull'>
		<header class='ipsBox__header'>
			<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured_products', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'carousel--commerce-featured' );
$return .= <<<IPSCONTENT

		</header>
		<i-data>
			<ul class='ipsData ipsData--grid ipsData--carousel ipsData--commerce-featured ipsBox__content' id='carousel--commerce-featured' tabindex="0">
				
IPSCONTENT;

foreach ( \IPS\nexus\Package\Item::featured() as $package ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->packageBlock( $package, 'carousel' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	

IPSCONTENT;

if ( \count( $newProducts ) ):
$return .= <<<IPSCONTENT

	<section class='ipsBox ipsPull'>
		<header class='ipsBox__header'>
			<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_products', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'carousel--commerce-new' );
$return .= <<<IPSCONTENT

		</header>
		<i-data>
			<ul class='ipsData ipsData--grid ipsData--carousel ipsData--commerce-new ipsBox__content' id='carousel--commerce-new' tabindex="0">
				
IPSCONTENT;

foreach ( $newProducts as $package ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->packageBlock( $package, 'carousel' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $popularProducts ) ):
$return .= <<<IPSCONTENT

	<section class='ipsBox ipsPull'>
		<header class='ipsBox__header'>
			<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'popular_products', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'carousel--commerce-popular' );
$return .= <<<IPSCONTENT

		</header>
		<i-data>
			<ul class='ipsData ipsData--grid ipsData--carousel ipsData--commerce-popular i-basis_300 ipsBox__content' id='carousel--commerce-popular' tabindex="0">
				
IPSCONTENT;

foreach ( $popularProducts as $package ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->packageBlock( $package, 'carousel' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function package( $package, $item, $purchaseForm, $inCart, $renewalTerm, $initialTerm ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox ipsPull'>
	<section class='cNexusProduct_header ipsFluid i-basis_400 i-gap_lines' data-controller='nexus.front.store.packagePage' data-itemTitle="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

if ( $images = $item->images() and \count( $images ) ):
$return .= <<<IPSCONTENT

			<div class='i-background_2'>
				
IPSCONTENT;

$donePrimary = false;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $images as $image ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$donePrimary ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='cNexusProduct_primaryImage' data-ipsLightbox data-ipsLightbox-group='product' style='background-image:url("
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
")'><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt=''></a>
						
IPSCONTENT;

$donePrimary = true;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<div class='cNexusProduct_images'>
					<ul class='ipsCarousel ipsCarousel--images ipsCarousel--commerce-product-images' id="carousel--commerce-product-images" tabindex="0">
						
IPSCONTENT;

foreach ( $images as $image ):
$return .= <<<IPSCONTENT

							<li>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='toggleImage'><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'></a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'carousel--commerce-product-images' );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='i-padding_3'>
			<div class='i-flex i-align-items_center i-justify-content_space-between i-flex-wrap_wrap i-gap_2'>
				<h1 class='ipsTitle ipsTitle--h1'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
				
IPSCONTENT;

if ( $package->reviewable ):
$return .= <<<IPSCONTENT

					<div>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'large', $item->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $item->memberReviewRating() );
$return .= <<<IPSCONTENT
 <span class='i-color_soft'>(
IPSCONTENT;

$pluralize = array( $item->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
			
IPSCONTENT;

$priceDetails = $package->fullPriceInfo();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $priceDetails === NULL ):
$return .= <<<IPSCONTENT

				<div>
					<em class='cNexusPrice_tax i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_no_price_info_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
				</div>
			
IPSCONTENT;

elseif ( $priceDetails['initialTerm'] ):
$return .= <<<IPSCONTENT

				<ul class='i-flex i-align-items_center i-flex-wrap_wrap i-gap_3'>
					<li>
	                    <div><span data-role="initialTerm">
IPSCONTENT;

$sprintf = array($priceDetails['initialTerm']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'package_initial_term_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span></div>
						<span class="cNexusPrice">
							<span data-role="price">
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $priceDetails['primaryPrice'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $priceDetails['primaryPriceDiscountedFrom'] ):
$return .= <<<IPSCONTENT

									<s>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $priceDetails['primaryPriceDiscountedFrom'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</s>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</span>
						</span>
						
IPSCONTENT;

if ( !$priceDetails['primaryPriceIsZero'] and \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT
<span class='cNexusPrice_tax i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
	                
IPSCONTENT;

if ( $priceDetails['renewalPrice'] ):
$return .= <<<IPSCONTENT

	                    <li>
	                        <div>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'package_subsequent_term_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	                        <span class="cNexusPrice">
	                            <span data-role="renewalTerm">
	                                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $priceDetails['renewalPrice'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	                            </span>
	                        </span>
	                        
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT
<span class='cNexusPrice_tax i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	                    </li>
	                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="cNexusPrice">
					
IPSCONTENT;

if ( $priceDetails['renewalPrice'] ):
$return .= <<<IPSCONTENT

						<span data-role="renewalTerm">
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $priceDetails['renewalPrice'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</span>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span data-role="price">
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $priceDetails['primaryPrice'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $priceDetails['primaryPriceDiscountedFrom'] ):
$return .= <<<IPSCONTENT

								<s>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $priceDetails['primaryPriceDiscountedFrom'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</s>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</span>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT
<span class='cNexusPrice_tax i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
			
IPSCONTENT;

if ( \IPS\Settings::i()->nexus_show_stock and $package->stock != -1 ):
$return .= <<<IPSCONTENT

				<br><span data-role="stock">
IPSCONTENT;

if ( $package->stock == -2 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$pluralize = array( $package->stock - $inCart ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_in_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $package->description, array('i-margin-top_3') );
$return .= <<<IPSCONTENT

			
			<div class='i-background_2 i-border-radius_box i-margin-block_3'>
				{$purchaseForm}
			</div>

			
IPSCONTENT;

if ( \count( $item->shareLinks() ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $item );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</section>
</div>


IPSCONTENT;

if ( $package->reviewable ):
$return .= <<<IPSCONTENT

	<section class="ipsBox ipsPull">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_reviews_pl', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="ipsBox__content">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->reviews( $item );
$return .= <<<IPSCONTENT

		</div>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function packageBlock( $package, $layout='grid', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->ui( "css" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsData__image'>
		
IPSCONTENT;

if ( $package->image ):
$return .= <<<IPSCONTENT

			<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i class="fa-solid fa-box-open"></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</a>
	<div class='ipsData__content'>
		<div class='ipsData__main'>
			<div class="ipsData__title">
				<h3>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role='productLink'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				</h3>
			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockPrice( $package->fullPriceInfo() );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$stockLevel = $package->stockLevel();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $stockLevel === 0 ):
$return .= <<<IPSCONTENT

				<span class='ipsCommerceStock ipsCommerceStock--out'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'out_of_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class='ipsCommerceStock ipsCommerceStock--in'>
					
IPSCONTENT;

if ( $stockLevel and \IPS\Settings::i()->nexus_show_stock ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( $stockLevel ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_in_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $package->reviewable ):
$return .= <<<IPSCONTENT

				<div class='i-margin-top_2'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'medium', $package->item()->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $package->item()->memberReviewRating() );
$return .= <<<IPSCONTENT

					<span class='i-color_soft'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->item()->reviews, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function packageBlockPrice( $details ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsCommercePrices'>
	
IPSCONTENT;

if ( $details === NULL ):
$return .= <<<IPSCONTENT

		<div hidden>
			<small class='ipsCommercePrice__tax'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_no_price_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</small>
		</div>

	
IPSCONTENT;

elseif ( $details['primaryPriceIsZero'] AND $details['initialTerm'] ):
$return .= <<<IPSCONTENT


		<div>
			<span class="ipsCommercePrice">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['renewalPrice'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT

				<small class='ipsCommercePrice__tax'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</small>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['primaryPrice'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $details['initialTerm'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$sprintf = array($details['initialTerm']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'package_initial_term', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT


		<div>
			<span class="ipsCommercePrice">
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['primaryPrice'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $details['primaryPriceDiscountedFrom'] ):
$return .= <<<IPSCONTENT

					<s>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['primaryPriceDiscountedFrom'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</s>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</span>
			
IPSCONTENT;

if ( !$details['primaryPriceIsZero'] and \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT

				<small class='ipsCommercePrice__tax'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</small>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $details['initialTerm'] ):
$return .= <<<IPSCONTENT

				<span>
IPSCONTENT;

$sprintf = array($details['initialTerm']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'package_initial_term', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span><br>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
        
IPSCONTENT;

if ( $details['initialTerm'] ):
$return .= <<<IPSCONTENT

		<div>
			<span class="ipsCommercePrice">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['renewalPrice'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT

				<small class='ipsCommercePrice__tax'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</small>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'package_subsequent_term', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function packageBlockWidget( $package, $mini=FALSE, $showAddToCart=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsData__item cNexusWidgetProduct 
IPSCONTENT;

if ( $mini ):
$return .= <<<IPSCONTENT
cNexusProduct_mini
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsData__image'>
		
IPSCONTENT;

if ( $package->image ):
$return .= <<<IPSCONTENT

			<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i class="fa-solid fa-box-open"></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</a>
	<div class="ipsData__content">
		<div class='ipsData__main'>
			<h2 class='ipsData__title'>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role='productLink'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockPrice( $package->fullPriceInfo() );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$stockLevel = $package->stockLevel();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $stockLevel === 0 ):
$return .= <<<IPSCONTENT

				<span class='ipsCommerceStock ipsCommerceStock--out'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'out_of_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class='ipsCommerceStock ipsCommerceStock--in'>
					
IPSCONTENT;

if ( $stockLevel and \IPS\Settings::i()->nexus_show_stock ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( $stockLevel ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_in_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $package->reviewable ):
$return .= <<<IPSCONTENT

				<div class="i-flex i-gap_1 i-align-items_baseline">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'small', $package->item()->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT

					<span class='i-color_soft i-font-weight_500 i-font-size_-1'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->item()->reviews, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function packageRow( $package, $hasCustomFields=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsData__image cNexusProduct_image'>
		
IPSCONTENT;

if ( $package->image ):
$return .= <<<IPSCONTENT

			<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i class="fa-solid fa-box-open"></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</a>
	<div class='ipsData__content'>
		<div class='ipsData__main'>
			<div class='ipsTitle ipsTitle--h5 ipsTitle--margin'>
				<h2><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
				
IPSCONTENT;

if ( $package->featured ):
$return .= <<<IPSCONTENT
<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-star'></i></span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockPrice( $package->fullPriceInfo() );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $package->description, array('ipsTruncate_4', 'i-margin-top_2') );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $package->reviewable ):
$return .= <<<IPSCONTENT

				<div class='i-margin-top_2'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'large', $package->item()->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $package->item()->memberReviewRating() );
$return .= <<<IPSCONTENT
 <span class='i-color_soft'>(
IPSCONTENT;

$pluralize = array( $package->item()->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div>
			
IPSCONTENT;

if ( $package->stock === 0 ):
$return .= <<<IPSCONTENT

				<span class='ipsButton ipsButton--primary' aria-disabled="true">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'out_of_stock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url()->setQueryString( 'purchase', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'store_quick_view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-forceReload data-ipsDialog-destructOnClose='true'>
					<i class='fa-solid fa-cart-shopping'></i>
					<span>
						
IPSCONTENT;

if ( $hasCustomFields ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_and_choose_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_to_cart', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function price( $price, $priceMayChange, $includePriceDescription=TRUE, $class='cNexusPrice' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $class !== NULL ):
$return .= <<<IPSCONTENT
<span class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $priceMayChange ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$sprintf = array($price); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'price_from', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $price, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $class !== NULL ):
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $includePriceDescription and \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT

	<span class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_tax i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function priceDiscounted( $original, $discounted, $priceMayChange, $includePriceDescription=TRUE, $class='cNexusPrice' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $class !== NULL ):
$return .= <<<IPSCONTENT
<span class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $priceMayChange ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$sprintf = array($discounted); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'price_from', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $discounted, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<s>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $original, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</s>

IPSCONTENT;

if ( $class !== NULL ):
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $includePriceDescription and \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT

	<span class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_tax i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function purchaseForm( $package, $item, $purchaseForm ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3' data-controller='nexus.front.store.packagePage' data-itemTitle="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class='ipsColumns'>
		<div class='ipsColumns__secondary i-basis_200'>
			
IPSCONTENT;

if ( $images = $item->images() and \count( $images ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$donePrimary = false;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $images as $image ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$donePrimary ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='cNexusProduct_primaryImage'><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsImage ipsImage_thumb"></a>
						
IPSCONTENT;

$donePrimary = true;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsColumns__primary'>
			<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
			<hr class='ipsHr'>
			{$purchaseForm}
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function register( $packages ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox ipsPull'>
	<div class="i-padding_3 i-border-bottom_3">
		<h1 class='i-font-size_4 i-font-weight_600 i-color_hard'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'choose_product', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<p class='i-font-size_2 i-font-weight_500 i-color_soft'>
			
IPSCONTENT;

if ( \IPS\Settings::i()->nexus_reg_force ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'store_forced_purchase', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'store_optional_purchase', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
		
IPSCONTENT;

if ( isset( $_SESSION['cart'] ) and \count( $_SESSION['cart'] ) ):
$return .= <<<IPSCONTENT

			<p class='i-font-size_2 i-text-align_center i-color_soft'>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=cart", null, "store_cart", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_to_registration', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-right'></i></a>
			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>		
	<section data-controller='nexus.front.store.register'>
		<i-data>
			<ul class='ipsData ipsData--grid ipsData--carousel ipsData--commerce-registration-products' id='commerce-registration-products' tabindex="0">
				
IPSCONTENT;

foreach ( $packages as $package ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", \IPS\Request::i()->app )->packageBlock( $package, 'carousel' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'commerce-registration-products' );
$return .= <<<IPSCONTENT

		<div data-role='productInformationWrapper' class='ipsHide cNexusRegister_info'>
			<hr class='ipsHr'>
			<a href='#' class='cNexusRegister_close ipsHide' data-action='closeInfo'>&times;</a>
			<div class='i-margin-top_3' data-role='productInformation'></div>
		</div>
	</section>
	
	
IPSCONTENT;

if ( !\IPS\Settings::i()->nexus_reg_force ):
$return .= <<<IPSCONTENT

		<p class="i-text-align_end i-padding_2 i-border-top_2 i-font-weight_600"><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register&noPurchase=1", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_without_purchasing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-right'></i></a></p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function reviews( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='core.front.core.commentFeed' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $item->isLastPage('reviews') ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->reviewFeedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='reviews'>
	
IPSCONTENT;

if ( $item->reviewForm() ):
$return .= <<<IPSCONTENT

		<div id='elProductReviewForm'>
			{$item->reviewForm()}
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $item->hasReviewed() ):
$return .= <<<IPSCONTENT

			<!-- Already reviewed -->
		
IPSCONTENT;

elseif ( \IPS\Member::loggedin()->restrict_post ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->restrict_post == -1 ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'restricted_cannot_comment' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'restricted_cannot_comment', \IPS\Member::loggedIn()->warnings(5,NULL,'rpa'), \IPS\Member::loggedIn()->restrict_post );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'unacknowledged_warning_cannot_post', \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $reviews = $item->reviews( NULL, NULL, NULL, 'desc', NULL, NULL, NULL, NULL, isset( \IPS\Widget\Request::i()->showDeleted ) ) AND \count( $reviews ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !$item->hasReviewed() ):
$return .= <<<IPSCONTENT
<hr class='ipsHr'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			
IPSCONTENT;

if ( $item->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsButtonBar__pagination">
					{$item->reviewPagination( array( 'tab', 'sort' ) )}
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimodHeader( $item, '#reviews', 'review' );
$return .= <<<IPSCONTENT

				<ul class='ipsDataFilters'>
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->setQueryString( array( 'tab' => 'reviews', 'sort' => 'helpful' ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->sort ) or \IPS\Widget\Request::i()->sort != 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'most_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->setQueryString( array( 'tab' => 'reviews', 'sort' => 'newest' ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->sort ) and \IPS\Widget\Request::i()->sort == 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'newest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				</ul>
			</div>
		</div>
		
		<div data-role='commentFeed' data-controller='core.front.core.moderation'>
			<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->csrf()->setQueryString( 'do', 'multimodReview' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
				
IPSCONTENT;

foreach ( $reviews as $review ):
$return .= <<<IPSCONTENT

					{$review->html()}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $item, 'review' );
$return .= <<<IPSCONTENT

			</form>
		</div>
		
IPSCONTENT;

if ( $item->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

			<div>
				{$item->reviewPagination( array( 'tab', 'sort' ) )}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( !$item->canReview() ):
$return .= <<<IPSCONTENT

		<p class="ipsEmptyMessage" data-role="noReviews">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function subscriptionPurchase( $purchase ) {
		$return = '';
		$return .= <<<IPSCONTENT


<ul class='ipsList ipsList--inline'>
	<li>
		<strong class="ipsMinorTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		<br>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->start->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</li>
	
IPSCONTENT;

if ( $purchase->expire ):
$return .= <<<IPSCONTENT

		<li>
			<strong class="ipsMinorTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_expire', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			<br>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->expire->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $purchase->renewals and !$purchase->grouped_renewals ):
$return .= <<<IPSCONTENT

		<li>
			<strong class="ipsMinorTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renewal_terms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			<br>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->renewals->toDisplay( $purchase->member ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $purchase->billing_agreement AND !$purchase->billing_agreement->canceled ):
$return .= <<<IPSCONTENT

		<li>
			<strong class="ipsMinorTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_billing_agreement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			<br><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->billing_agreement->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="i-color_inherit">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->billing_agreement->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


</ul>

<ul class='ipsList ipsList--inline i-margin-top_3'>
	<li>
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_view_purchase', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</li>
	
IPSCONTENT;

if ( $pendingInvoice = $purchase->invoice_pending and $pendingInvoice->status === $pendingInvoice::STATUS_PENDING ):
$return .= <<<IPSCONTENT

		<li>
			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingInvoice->checkoutUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</li>
	
IPSCONTENT;

elseif ( $purchase->expire AND $canRenewUntil = $purchase->canRenewUntil(NULL,TRUE) ):
$return .= <<<IPSCONTENT

		<li>
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=purchases&do=renew&id={$purchase->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, "front", "clientspurchaserenew", array( \IPS\Http\Url\Friendly::seoTitle( $purchase->name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $canRenewUntil === TRUE or $canRenewUntil > 1 ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-size='narrow'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $purchase->canCancel() ):
$return .= <<<IPSCONTENT

		<li>
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=purchases&do=cancel&id={$purchase->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, "front", "clientspurchasecancel", array( \IPS\Http\Url\Friendly::seoTitle( $purchase->name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--negative' data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_cancel_renewal_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_cancel_renewal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</ul>

IPSCONTENT;

		return $return;
}}