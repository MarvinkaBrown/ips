<?php
namespace IPS\Theme;
class class_nexus_front_checkout extends \IPS\Theme\Template
{	function checkoutOrderSummary( $summary ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$totalItems = 0;
$return .= <<<IPSCONTENT


IPSCONTENT;

$itemTotal = new \IPS\Math\Number('0');
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $summary['items'] as $item ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !( $item instanceof \IPS\nexus\extensions\nexus\Item\CouponDiscount ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$itemTotal = $itemTotal->add( $item->linePrice()->amount );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$totalItems += $item->quantity;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3'>
	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<i-data>
		<ul class="ipsData ipsData--table i-margin-top_3">
			<li class='ipsData__item'>
				<div class='ipsData__main'>
					<span>
IPSCONTENT;

$pluralize = array( $totalItems ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'summary_items', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
				</div>
				<div class='i-basis_100 i-text-align_end'>
					<span class='cNexusPrice'>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $itemTotal, ( ( isset( \IPS\Request::i()->cookie['currency'] ) and \in_array( \IPS\Request::i()->cookie['currency'], \IPS\nexus\Money::currencies() ) ) ? \IPS\Request::i()->cookie['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency() ) );
$return .= <<<IPSCONTENT
</span>
				</div>
			</li>
			
IPSCONTENT;

foreach ( $summary['items'] as $item ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $item instanceof \IPS\nexus\extensions\nexus\Item\CouponDiscount ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item cNexusCheckout_coupon'>
						<div class='ipsData__main i-text-align_end'>
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coupon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						</div>
						<div class='i-basis_100 i-text-align_end cNexusPrice'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<li class='ipsData__item cNexusCheckout_subtotal'>
				<div class='ipsData__main i-text-align_end'>
					<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subtotal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				</div>
				<div class='i-basis_100 i-text-align_end cNexusPrice'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['subtotal'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</div>
			</li>
			
IPSCONTENT;

foreach ( $summary['tax'] as $taxId => $tax ):
$return .= <<<IPSCONTENT

				<li class='ipsData__item'>
					<div class='ipsData__main i-text-align_end'>
						<strong>
IPSCONTENT;

$val = "nexus_tax_{$taxId}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)</strong>
					</div>
					<div class='i-basis_100 i-text-align_end cNexusPrice'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tax['amount'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
</div>
IPSCONTENT;

		return $return;
}

	function checkoutWrapper( $content, $checkoutStatus = NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class=''>
	<div class='i-text-align_center i-padding_3'>
		
IPSCONTENT;

if ( $checkoutStatus && $checkoutStatus != 'continue' ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $checkoutStatus === 'complete' ):
$return .= <<<IPSCONTENT

				<i class='i-font-size_6 fa-solid fa-check-circle'></i>
				<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_title_success', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>	
			
IPSCONTENT;

elseif ( $checkoutStatus === 'waiting' ):
$return .= <<<IPSCONTENT

				<i class='i-font-size_6 fa-regular fa-clock'></i>	
				<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_title_waiting', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
			
IPSCONTENT;

elseif ( $checkoutStatus === 'hold' ):
$return .= <<<IPSCONTENT

				<i class='i-font-size_6 fa-regular fa-clock'></i>	
				<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_title_hold', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
			
IPSCONTENT;

elseif ( $checkoutStatus === 'pending' ):
$return .= <<<IPSCONTENT

				<i class='i-font-size_6 fa-regular fa-clock'></i>	
				<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_title_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
			
IPSCONTENT;

elseif ( $checkoutStatus === 'refused' ):
$return .= <<<IPSCONTENT

				<i class='i-font-size_6 fa-solid fa-triangle-exclamation'></i>	
				<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_title_refused', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i class='i-font-size_6 fa-solid fa-cart-shopping'></i>
			<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'checkout', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isSecure() === TRUE ):
$return .= <<<IPSCONTENT

			<i class='fa-solid fa-lock'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secure_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

	<section>
		<div class='ipsBox i-padding_3' id='elCheckoutContent'>
			{$content}
		</div>
	</section>
</div>
IPSCONTENT;

		return $return;
}

	function confirmAndPay( $invoice, $summary, $form, $amountToPay, $couponForm, $recurrings, $overriddenRenewalTerms ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsColumns i-margin-top_3 cNexusCheckout_review' data-controller='nexus.front.checkout.review'>
	<div class='ipsColumns__primary'>
		
IPSCONTENT;

if ( $invoice->billaddress ):
$return .= <<<IPSCONTENT

			<div class='ipsSpanGrid i-margin-bottom_3'>
				
IPSCONTENT;

if ( $invoice->billaddress ):
$return .= <<<IPSCONTENT

					<div class='ipsSpanGrid__6'>
						<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->checkoutUrl()->setQueryString( '_step', 'checkout_customer' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='wizardLink' class='i-font-size_-2'>(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</a></h2>
						<div class='i-margin-top_3'>
							{$invoice->billaddress->toString('<br>')}
							
IPSCONTENT;

if ( isset( $invoice->billaddress->vat ) and $invoice->billaddress->vat ):
$return .= <<<IPSCONTENT

								<br>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_checkout_vat_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->billaddress->vat, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $invoice->member->email ):
$return .= <<<IPSCONTENT

			<div class='i-color_soft i-margin-bottom_3'>
				<i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$sprintf = array($invoice->member->email); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'confirmation_sent_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		{$couponForm}

		<section class='i-background_3 i-margin-top_3'>
			<h2 class='ipsBox__header ipsHide'>
				
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isSecure() === TRUE ):
$return .= <<<IPSCONTENT

					<i class='fa-solid fa-lock'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secure_payment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</h2>
			<div class='i-padding_3'>
				{$form}
			</div>
		</section>
	</div>
	<div class='ipsColumns__secondary i-basis_360 '>
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--order-review i-margin-top_3">
				
IPSCONTENT;

foreach ( $summary['items'] as $item ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !( $item instanceof \IPS\nexus\extensions\nexus\Item\CouponDiscount ) ):
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
" alt="" loading="lazy">
								
IPSCONTENT;

elseif ( $item::$icon ):
$return .= <<<IPSCONTENT

									<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
								<div><span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 x </span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
								{$item->detailsForDisplay( 'checkout' )}
							</div>
							<div class='i-text-align_end'>
								<span class='cNexusPrice'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								
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

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $summary['items'] as $item ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $item instanceof \IPS\nexus\extensions\nexus\Item\CouponDiscount ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item cNexusCheckout_coupon'>
							<div class='ipsData__main i-text-align_end'>
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coupon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</div>
							<div class='i-basis_100 i-text-align_end cNexusPrice'>
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</div>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<li class='ipsData__item cNexusCheckout_subtotal'>
					<div class='ipsData__main i-text-align_end'>
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subtotal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</div>
					<div class='i-basis_100 i-text-align_end cNexusPrice'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['subtotal'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				</li>
				
IPSCONTENT;

foreach ( $summary['tax'] as $taxId => $tax ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<div class='ipsData__main i-text-align_end'>
							<strong>
IPSCONTENT;

$val = "nexus_tax_{$taxId}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span class='i-color_soft i-font-weight_normal'>(
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)</span></strong>
						</div>
						<div class='i-basis_100 i-text-align_end cNexusPrice'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tax['amount'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $transactionsReceived = $invoice->transactions( array( \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_PART_REFUNDED, \IPS\nexus\Transaction::STATUS_HELD, \IPS\nexus\Transaction::STATUS_REVIEW, \IPS\nexus\Transaction::STATUS_GATEWAY_PENDING ) ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \count( $transactionsReceived ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $transactionsReceived as $id => $transaction ):
$return .= <<<IPSCONTENT

							<li class='ipsData__item i-font-size_2 
IPSCONTENT;

if ( $id === 0 ):
$return .= <<<IPSCONTENT
cNexusCheckout_subtotal
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
								<div class='ipsData__main i-text-align_end'>
									<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment_received', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								</div>
								<div class='i-basis_100 i-text-align_end cNexusPrice'>
									<strong>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $transaction->amount->amount->subtract( $transaction->partial_refund->amount )->multiply( new \IPS\Math\Number('-1') ), $transaction->currency );
$return .= <<<IPSCONTENT
</strong>
								</div>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						<li class='ipsData__item i-font-size_2 cNexusCheckout_subtotal'>
							<div class='ipsData__main i-text-align_end'>
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_to_pay', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</div>
							<div class='i-basis_100 i-text-align_end cNexusPrice'>
								<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->amountToPay( TRUE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
							</div>
						</li>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<li class='ipsData__item i-font-size_2 cNexusCheckout_subtotal'>
							<div class='ipsData__main i-text-align_end'>
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</div>
							<div class='i-basis_100 i-text-align_end cNexusPrice'>
								<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->amountToPay(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
							</div>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
		
IPSCONTENT;

if ( \count( $recurrings ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$taxAmount = new \IPS\Math\Number('0');
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $recurrings as $recurring ):
$return .= <<<IPSCONTENT

				<h3 class="ipsTitle ipsTitle--h3 i-margin-top_3">
IPSCONTENT;

$sprintf = array($recurring['term']->getTermUnit()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'renewals_header', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h3>
				<i-data>
					<ul class="ipsData ipsData--table ipsData--renewals i-margin-top_3">
						
IPSCONTENT;

foreach ( $recurring['items'] as $rId => $item ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !( $item instanceof \IPS\nexus\extensions\nexus\Item\CouponDiscount ) ):
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
" alt="" loading="lazy">
										
IPSCONTENT;

elseif ( $item::$icon ):
$return .= <<<IPSCONTENT

											<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
										<div><span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 x </span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
										{$item->detailsForDisplay( 'checkout' )}
														
IPSCONTENT;

if ( isset( $recurring['showDueDate'] ) AND !$recurring['showDueDate'] ):
$return .= <<<IPSCONTENT

														<strong>
IPSCONTENT;

$sprintf = array($item->expireDate->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'product_renews_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong>
														
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
									<div class='i-basis_120 i-text-align_end'>
										<span class='cNexusPrice'>
											
IPSCONTENT;

if ( $item instanceof \IPS\nexus\Invoice\Item\Renewal ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( isset( $overriddenRenewalTerms[ $rId ] ) ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$return .= new \IPS\nexus\Money( $overriddenRenewalTerms[ $rId ]->cost->amount->multiply( new \IPS\Math\Number( (string) $item->quantity ) ), $overriddenRenewalTerms[ $rId ]->cost->currency );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

elseif ( $item->renewalTerm ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

$return .= new \IPS\nexus\Money( $item->renewalTerm->cost->amount->multiply( new \IPS\Math\Number( (string) $item->quantity ) ), $item->renewalTerm->cost->currency );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</span>
										
IPSCONTENT;

if ( $item->quantity > 1 AND (!$item instanceof \IPS\nexus\Invoice\Item\Renewal) ):
$return .= <<<IPSCONTENT

											<p class='i-font-size_-2 i-color_soft'>
												
IPSCONTENT;

$sprintf = array($item->renewalTerm->cost); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'each_short', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

											</p>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $recurring['term']->tax and $taxRate = $recurring['term']->tax->rate( $invoice->billaddress ) and $taxAmount = $recurring['term']->cost->amount->multiply( new \IPS\Math\Number( $taxRate ) ) ):
$return .= <<<IPSCONTENT

							<li class='ipsData__item'>
								<div class='ipsData__main i-text-align_end'>
									<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $recurring['term']->tax->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <span class='i-color_soft i-font-weight_normal'>(
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $taxRate*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)</span></strong>
								</div>
								<div class='i-basis_100 i-text-align_end cNexusPrice'>
									
IPSCONTENT;

$return .= new \IPS\nexus\Money( $taxAmount, $recurring['term']->cost->currency );
$return .= <<<IPSCONTENT

								</div>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !isset( $recurring['showDueDate'] ) OR $recurring['showDueDate']  ):
$return .= <<<IPSCONTENT

							<li class='ipsData__item cNexusCheckout_subtotal'>
								<div class='ipsData__main i-text-align_end'>
									<strong>
IPSCONTENT;

$sprintf = array($recurring['dueDate']->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_due_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong>
								</div>
								<div class='i-basis_100 i-text-align_end cNexusPrice'>
									<strong>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $recurring['term']->cost->amount->add( $taxAmount ), $recurring['term']->cost->currency );
$return .= <<<IPSCONTENT
</strong>
								</div>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</i-data>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->nexus_tac === 'button' ):
$return .= <<<IPSCONTENT

			<p class="i-font-size_-2">
IPSCONTENT;

$sprintf = array(\IPS\Settings::i()->nexus_tac_link); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_agree_to_tac', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function couponForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--coupon" action="
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
 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
 data-ipsForm data-role='couponForm'>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \is_array($v) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
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

endif;
$return .= <<<IPSCONTENT

	
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


	<div class='i-padding_3 i-background_2'>
		
IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

			<div class="ipsMessage ipsMessage--error">
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
		<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'do_you_have_coupon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class='ipsSpanGrid i-margin-top_2'>
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

					<div class='ipsSpanGrid__8'>
						{$input->html()}
						
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

							<p class="i-margin-top_2 i-font-size_-2 i-color_warning">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<div class='ipsSpanGrid__4'>
				<button type='submit' class='ipsButton ipsButton--soft ipsButton--small ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'apply_coupon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function customerInformation( $informationForm, $login, $loginError, $invoice ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $login !== NULL ):
$return .= <<<IPSCONTENT

	<section data-controller='nexus.front.checkout.register' 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->customer_submitted ) ):
$return .= <<<IPSCONTENT
data-regform="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		
IPSCONTENT;

$usernamePasswordMethods = $login->usernamePasswordMethods();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Login::registrationType() != 'disabled' or ( $usernamePasswordMethods and $buttonMethods ) ):
$return .= <<<IPSCONTENT

			<div class='ipsSpanGrid i-margin-top_3' data-role="memberChoice">
				<div class='ipsSpanGrid__6 i-padding_3 cNexusCheckout_returning'>
					<h2 class="ipsTitle ipsTitle--h3 i-margin-bottom_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'returning_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<div class='ipsForm_fullWidth'>
						
IPSCONTENT;

if ( $loginError !== NULL ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->message( $loginError, 'error' );
$return .= <<<IPSCONTENT

							<br>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<form accept-charset='utf-8' method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsForm" data-controller="core.global.core.login">
							<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

if ( $usernamePasswordMethods ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->loginForm( $login );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

elseif ( $buttonMethods ):
$return .= <<<IPSCONTENT

								<div class='i-gap_2 i-margin-top_2'>
									
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

										<div class='i-text-align_center'>
											{$method->button()}
										</div>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</form>
					</div>
				</div>
				<div class='ipsSpanGrid__6 i-padding_3'>
					
IPSCONTENT;

if ( \IPS\Login::registrationType() != 'disabled' ):
$return .= <<<IPSCONTENT

						<div class="i-margin-bottom_4">
							<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_customers', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
							<p>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_customer_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</p>
							
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'redirect' ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_reg_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<button data-action='newMember' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_as_new_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $usernamePasswordMethods and $buttonMethods ):
$return .= <<<IPSCONTENT

						<div class='i-background_2 i-padding_2'>
							<form accept-charset='utf-8' method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsForm" data-controller="core.global.core.login">
								<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								<p class='i-font-size_-2 i-text-align_center'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_with_these', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></p>
								<div class='i-gap_2 i-margin-top_2'>
									
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

										<div class='i-text-align_center'>
											{$method->button()}
										</div>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</div>
							</form>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
			<div class="i-padding_3 ipsJS_hide" data-role='newCustomerForm'>
				<h2 class='ipsTitle ipsTitle--h3 i-margin-top_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_new_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				{$informationForm}
			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class="i-padding_3">
				<div>
					
IPSCONTENT;

if ( $loginError !== NULL ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->message( $loginError, 'error' );
$return .= <<<IPSCONTENT

						<br>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<form accept-charset='utf-8' method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsForm" data-controller="core.global.core.login">
						<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

if ( $usernamePasswordMethods ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->loginForm( $login );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( $buttonMethods ):
$return .= <<<IPSCONTENT

							<div class='i-gap_2 i-margin-top_2'>
								
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

									<div class='i-text-align_center'>
										{$method->button()}
									</div>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</form>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</section>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='ipsColumns i-margin-top_3'>
		<div class='ipsColumns__primary'>
			<h2 class='ipsTitle ipsTitle--h3 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			{$informationForm}
		</div>
		<div class='ipsColumns__secondary i-basis_280'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "checkout", "nexus" )->checkoutOrderSummary( $invoice->summary() );
$return .= <<<IPSCONTENT

		</div>
	</div>	

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function customerInformationForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--customer-info" action="
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
 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
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

		
IPSCONTENT;

if ( \is_array($v) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
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

endif;
$return .= <<<IPSCONTENT

	
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

	
IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--error">
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<ul>
		
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

				{$input}
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
	<hr class='ipsHr'>
	<div class='i-text-align_end i-margin-top_3'>
		<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_to_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp;<i class='fa-solid fa-chevron-right'></i></button>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function orderReview( $invoice ) {
		$return = '';
		$return .= <<<IPSCONTENT


<section class='ipsBox i-margin-top_3'>
	<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='i-padding_3'>
		<div class='i-flex i-align-items_center i-gap_2'>
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 #
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'nexus', 'clients' ) )  ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;

if ( $invoice->guest_data and isset( $invoice->guest_data['guestTransactionKey'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url()->setQueryString( array( 'do' => 'printout', 'key' => $invoice->guest_data['guestTransactionKey'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url()->setQueryString( 'do', 'printout' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' target="_blank" rel='noopener' class='ipsJS_show ipsButton ipsButton--soft ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_invoice_print', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<hr class='ipsHr'>
		<div class='ipsSpanGrid i-margin-top_3'>
			<div class='ipsSpanGrid__6'>
				
IPSCONTENT;

if ( $invoice->billaddress ):
$return .= <<<IPSCONTENT

					<div class='ipsSpanGrid i-margin-bottom_4'>
						
IPSCONTENT;

if ( $invoice->billaddress ):
$return .= <<<IPSCONTENT

							<div class='ipsSpanGrid__6'>
								<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<div class='i-margin-top_3'>
									{$invoice->billaddress->toString('<br>')}
								</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clients", "nexus" )->paymentLog( $invoice );
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsSpanGrid__6'>
				<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				
IPSCONTENT;

$summary = $invoice->summary();
$return .= <<<IPSCONTENT

				<i-data>
					<ul class="ipsData ipsData--table ipsData--order-review i-margin-top_3">
						
IPSCONTENT;

foreach ( $summary['items'] as $item ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !( $item instanceof \IPS\nexus\extensions\nexus\Item\CouponDiscount ) ):
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
" alt=""  loading="lazy">
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<i></i>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</figure>
									<div class='ipsData__main'>
										<div><span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 x </span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
										
IPSCONTENT;

if ( \count( $item->details ) ):
$return .= <<<IPSCONTENT

											<span class="i-color_soft">
												
IPSCONTENT;

foreach ( $item->details as $k => $v ):
$return .= <<<IPSCONTENT

													<strong>
IPSCONTENT;

$val = "nexus_pfield_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: </strong> 
IPSCONTENT;

$return .= \IPS\nexus\Package\CustomField::load( $k )->displayValue( $v );
$return .= <<<IPSCONTENT
<br>
												
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

											</span>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</i-data>
			</div>
		</div>
	</div>	
</section>
IPSCONTENT;

		return $return;
}

	function packageFields( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $item->details ) ):
$return .= <<<IPSCONTENT

    <div class="i-color_soft">
        
IPSCONTENT;

foreach ( $item->details as $k => $v ):
$return .= <<<IPSCONTENT

            <strong>
IPSCONTENT;

$val = "nexus_pfield_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: </strong> 
IPSCONTENT;

$return .= \IPS\nexus\Package\CustomField::load( $k )->displayValue( $v );
$return .= <<<IPSCONTENT
<br>
        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

    </div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function paymentForm( $invoice, $amountToPay, $showSubmitButton, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--payment" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
 data-ipsForm data-role="paymentForm">
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \is_array($v) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
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

endif;
$return .= <<<IPSCONTENT

	
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

	
	
IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--error">
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $collection as $inputName => $input ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $inputName == 'payment_method' ):
$return .= <<<IPSCONTENT

				<div class='ipsFieldRow'>
					<label class='ipsFieldRow__label' for='elInput_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						<span class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$val = "{$inputName}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</label>
					<div class='ipsFieldRow__content'>
						
IPSCONTENT;

if ( $invoice->canSplitPayment() ):
$return .= <<<IPSCONTENT

							<div class='i-flex i-align-items_center i-flex-wrap_wrap i-gap_2'>
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->checkoutUrl()->setQueryString( 'do', 'split' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split_payment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split_payment_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split_payment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<ul class='ipsFieldRow__content--checkboxes i-flex_11'>
							
IPSCONTENT;

foreach ( $input->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

								<li>
									<input type="radio" 
IPSCONTENT;

if ( (string) $input->value == (string) $k or ( isset( $input->options['userSuppliedInput'] ) and !\in_array( $input->value, array_keys( $input->options['options'] ) ) and $k == $input->options['userSuppliedInput'] ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $input->required === TRUE ):
$return .= <<<IPSCONTENT
required
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $input->disabled === TRUE or ( \is_array( $input->disabled ) and \in_array( $k, $input->disabled ) ) ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $input->options['toggles'][ $k ] ) and !empty( $input->options['toggles'][ $k ] ) ):
$return .= <<<IPSCONTENT
data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $input->options['toggles'][ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
									<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>{$v}</label>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
						
IPSCONTENT;

if ( $invoice->canSplitPayment() ):
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<ul>
					{$input->rowHtml()}
				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

if ( $showSubmitButton ):
$return .= <<<IPSCONTENT

		<div class='i-margin-top_3' id="paymentMethodSubmit">
			<button type='submit' class='ipsButton ipsButton--primary ipsButton--wide'>
				<i class='fa-solid fa-check-circle'></i>
				
IPSCONTENT;

if ( $amountToPay->amount->isZero() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'confirm_and_no_pay', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $amountToPay->amount->compare( $invoice->total->amount ) !== 0 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$sprintf = array($amountToPay); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'confirm_and_pay_split', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'confirm_and_pay', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</button>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</form>
IPSCONTENT;

		return $return;
}

	function transactionFail( $transaction ) {
		$return = '';
		$return .= <<<IPSCONTENT


<p class='i-font-size_2 i-text-align_center'>
	<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_fail_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
</p>


IPSCONTENT;

if ( isset( $transaction->extra['publicNote'] ) ):
$return .= <<<IPSCONTENT

	<div class='ipsRichText  i-background_3 i-padding_3 i-margin-block_2'>
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->extra['publicNote'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<p class='i-text-align_center'>
	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canUseContactUs() and !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'contact' ) ):
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=contact&controller=contact", null, "contact", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->invoice->checkoutUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--secondary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'try_another_payment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</p>
IPSCONTENT;

		return $return;
}

	function transactionGatewayPending( $transaction, $invoice=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<p class='i-font-size_2 i-text-align_center'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_thanks_blurb_no_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>
<p class='i-font-size_2 i-text-align_center'>
	<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_processing_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_processing_blurb2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

    <p class='i-text-align_center'>
        <i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'track_orders_in_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "checkout", "nexus" )->orderReview( $invoice ?: $transaction->invoice );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function transactionHold( $transaction ) {
		$return = '';
		$return .= <<<IPSCONTENT


<p class='i-font-size_2 i-text-align_center'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_thanks_blurb_no_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>
<p class='i-font-size_2 i-text-align_center'>
	<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_held_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_held_blurb2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

    <p class='i-text-align_center'>
        <i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'track_orders_in_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $transaction->extra['publicNote'] ) ):
$return .= <<<IPSCONTENT

	<div class='ipsRichText'>
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->extra['publicNote'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "checkout", "nexus" )->orderReview( $transaction->invoice );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function transactionOkay( $transaction, $complete, $purchases ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $complete ):
$return .= <<<IPSCONTENT

	<p class='i-font-size_2 i-text-align_center'>
		<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_thanks_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
	</p>
	<p class='i-text-align_center'>
		<i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'manage_orders_in_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
.
	</p>

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "checkout", "nexus" )->orderReview( $transaction->invoice );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='i-font-size_2 i-text-align_center'>
		<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_split_payment_success', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
	</p>
	<p class='i-font-size_2 i-text-align_center'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_split_payment_success2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>
	
IPSCONTENT;

if ( $invoice = $transaction->invoice ):
$return .= <<<IPSCONTENT

		<p class='i-text-align_center'>
			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->checkoutUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'return_to_checkout', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function transactionWait( $transaction ) {
		$return = '';
		$return .= <<<IPSCONTENT


<p class='i-font-size_2 i-text-align_center'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_thanks_blurb_no_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>
<p class='i-font-size_2 i-text-align_center'>
	<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_wait_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_wait_blurb2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>

<div class='ipsRichText  i-background_3 i-padding_3 i-margin-block_2'>
	{$transaction->method->manualPaymentInstructions( $transaction )}
</div>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

    <p class='i-text-align_center'>
        <i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'track_orders_in_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "checkout", "nexus" )->orderReview( $transaction->invoice );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}