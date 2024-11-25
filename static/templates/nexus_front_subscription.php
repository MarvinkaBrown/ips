<?php
namespace IPS\Theme;
class class_nexus_front_subscription extends \IPS\Theme\Template
{	function clientArea( $package ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $package->description ):
$return .= <<<IPSCONTENT

<div class='i-padding_3'>
    <div class="ipsRichText i-margin-block_2">{$package->description}</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function current( $subscription ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $package = $subscription->package ):
$return .= <<<IPSCONTENT

	<div class="ipsBox cSubscription_current i-padding_3">
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subscription = $subscription->currentBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function profileSubscription( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $subscription = \IPS\nexus\Subscription::loadActiveByMember( $member ) ):
$return .= <<<IPSCONTENT

	<div class='ipsProfileWidget'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions", null, "nexus_subscriptions", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='cProfileSubscription 
IPSCONTENT;

if ( $subscription->package->_image ):
$return .= <<<IPSCONTENT
cProfileSubscription--with-image
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
cProfileSubscription--no-image
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

if ( $subscription->package->_image ):
$return .= <<<IPSCONTENT

				<img src="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( '(', ')' ), array( '\(', '\)' ), $subscription->package->_image->url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading='lazy' alt=''>
				<div class='cProfileSubscription__gradient'></div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<span class='cProfileSubscription__text'><i class="fa-solid fa-award"></i> 
IPSCONTENT;

$sprintf = array($subscription->package->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_subscriber', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
		</a>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function row( $package, $subscription, $showImagePlaceholder=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-->
<div class='ipsData__item cSubscriptions 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->id ) and \IPS\Widget\Request::i()->id == $package->id ):
$return .= <<<IPSCONTENT
cSubscription--highlighted
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $subscription and $subscription->package and $subscription->package->id === $package->id ):
$return .= <<<IPSCONTENT
cSubscription--active 
IPSCONTENT;

if ( !$subscription->active ):
$return .= <<<IPSCONTENT
cSubscription--expired
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( $package->_image || $showImagePlaceholder ):
$return .= <<<IPSCONTENT

		<div class='ipsData__image' aria-hidden='true'>
			
IPSCONTENT;

if ( $package->_image ):
$return .= <<<IPSCONTENT

				<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy' alt=''>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class="fa-solid fa-box"></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsData__content'>
		<div class="ipsData__main">
			<h2 class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
			<div>
				
IPSCONTENT;

if ( $priceInfo = $package->priceInfo() and $priceInfo['primaryPrice'] ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "store", "nexus" )->packageBlockPrice( $priceInfo );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<span class="cNexusPrice">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_cost_unavailable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsRichText ipsData__desc ipsData__desc--all">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		</div>

		
IPSCONTENT;

if ( $subscription and $subscription->package and $subscription->package->id === $package->id ):
$return .= <<<IPSCONTENT

			<div class='cSubscriptionRenew 
IPSCONTENT;

if ( $subscription->active ):
$return .= <<<IPSCONTENT
i-color_positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-text-align_center i-font-weight_600 i-margin-bottom_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subscription->currentBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $subscription ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $subscription->package and $subscription->purchase  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$upgradeCost = (!( $subscription->purchase->billing_agreement and !$subscription->purchase->billing_agreement->canceled )  )? $package->costToUpgradeIncludingTax( $subscription->package, \IPS\nexus\Customer::loggedIn() ) : NULL;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $subscription->package->id === $package->id ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $subscription->purchase->expire and ( $canRenewUntil = $subscription->purchase->canRenewUntil( NULL,TRUE ) or ( $subscription->purchase->can_reactivate ) or ( $pendingInvoice = $subscription->purchase->invoice_pending and $pendingInvoice->status === $pendingInvoice::STATUS_PENDING ) or $subscription->purchase->canCancel()) ):
$return .= <<<IPSCONTENT

						<div class='cSubscriptionInfo'>
							<ul class='ipsButtons ipsButtons--fill'>
								
IPSCONTENT;

if ( $pendingInvoice = $subscription->purchase->invoice_pending and $pendingInvoice->status === $pendingInvoice::STATUS_PENDING ):
$return .= <<<IPSCONTENT

									<li>
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingInvoice->checkoutUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--positive' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_pay_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
											
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										</a>
									</li>
								
IPSCONTENT;

elseif ( $canRenewUntil ):
$return .= <<<IPSCONTENT

									<li>
										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=purchases&do=renew&id={$subscription->purchase->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, "front", "clientspurchaserenew", array( \IPS\Http\Url\Friendly::seoTitle( $subscription->purchase->name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--positive' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' 
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

elseif ( $renewOptions = json_decode( $package->renew_options, TRUE ) and \count( $renewOptions ) and $subscription->purchase->can_reactivate and ( !$subscription->purchase->billing_agreement or $subscription->purchase->billing_agreement->canceled ) ):
$return .= <<<IPSCONTENT

									<li>
										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&do=reactivate&id={$package->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, "front", "nexus_subscription", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--positive' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
											
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reactivate_package', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										</a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $subscription->purchase->canCancel() ):
$return .= <<<IPSCONTENT

									<li>
										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=purchases&do=cancel&id={$subscription->purchase->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, "front", "clientspurchasecancel", array( \IPS\Http\Url\Friendly::seoTitle( $subscription->purchase->name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
&ref=
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( base64_encode( \IPS\Http\Url::internal( 'app=nexus&module=subscriptions&controller=subscriptions', 'front', 'nexus_subscriptions' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--negative" data-confirm data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_cancel_renewal_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
											
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_cancel_renewal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										</a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $upgradeCost !== NULL ):
$return .= <<<IPSCONTENT

					<div class='cSubscriptionInfo'>
						<ul class='ipsButtons ipsButtons--fill'>
							
IPSCONTENT;

if ( $upgradeCost->amount->isGreaterThanZero() or ( $upgradeCost->amount->isZero() and $package->price() >= $subscription->package->price()) ):
$return .= <<<IPSCONTENT

								<li>
									
IPSCONTENT;

if ( $upgradeCost->amount->isZero() ):
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&do=change&id={$package->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "nexus_subscription", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--inherit" data-change-subscription data-change-message='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_change_no_charge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

elseif ( !$subscription->active ):
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&do=change&id={$package->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "nexus_subscription", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--inherit" data-change-subscription data-change-message='
IPSCONTENT;

$sprintf = array($subscription->package->_title, $package->_title, $upgradeCost); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_change_confirm_switch', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&do=change&id={$package->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "nexus_subscription", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--inherit" data-change-subscription data-change-message='
IPSCONTENT;

$sprintf = array($subscription->package->_title, $package->_title, $upgradeCost); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_change_confirm_upgrade', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $subscription->active ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_upgrade_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_switch_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</a>
								</li>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<li>
									
IPSCONTENT;

if ( $upgradeCost->amount->isZero() ):
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&do=change&id={$package->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "nexus_subscription", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--negative" data-change-subscription data-change-message='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_change_no_charge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

elseif ( !$subscription->active ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$upgradeCost = new \IPS\nexus\Money( $upgradeCost->amount->absolute(), $upgradeCost->currency );
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&do=change&id={$package->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "nexus_subscription", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--negative" data-change-subscription data-change-message='
IPSCONTENT;

$sprintf = array($subscription->package->_title, $package->_title, $upgradeCost); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_change_confirm_switch', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$upgradeCost = new \IPS\nexus\Money( $upgradeCost->amount->absolute(), $upgradeCost->currency );
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&do=change&id={$package->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "nexus_subscription", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--negative" data-change-subscription data-change-message='"
IPSCONTENT;

$sprintf = array($subscription->package->_title, $package->_title, $upgradeCost); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_change_confirm_downgrade', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $subscription->active ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_downgrade_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_switch_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</a>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='cSubscriptionInfo'>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&do=purchase&id={$package->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "nexus_subscription", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_buy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function rows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$forcePlaceholders = FALSE;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT
 
	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $row->_image ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$forcePlaceholders = TRUE;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "subscription", "nexus" )->row( $row, $table->activeSubscription, $forcePlaceholders );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function table( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='ipsBox ipsPull'>
	<header class='ipsPageHeader'>
		<div class="ipsPageHeader__row">
			<div class='ipsPageHeader__primary'>
				
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->register ) and \IPS\Settings::i()->nexus_subs_register and (int) \IPS\Widget\Request::i()->register === 1 ):
$return .= <<<IPSCONTENT

					<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_sign_up_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
					<p class="ipsPageHeader__desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_sign_up_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

elseif ( isset( \IPS\Widget\Request::i()->register ) and \IPS\Settings::i()->nexus_subs_register and (int) \IPS\Widget\Request::i()->register === 2 ):
$return .= <<<IPSCONTENT

					<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_needed_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
					<p class="ipsPageHeader__desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_needed_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_front_subscriptions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
					
IPSCONTENT;

if ( $table->activeSubscription and $table->activeSubscription->purchase->billing_agreement and !$table->activeSubscription->purchase->billing_agreement->canceled ):
$return .= <<<IPSCONTENT

					<p class="ipsMessage ipsMessage--info">
						
IPSCONTENT;

$sprintf = array($table->activeSubscription->purchase->billing_agreement->url()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_active_billingagreement_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( \count( \IPS\nexus\Money::currencies() ) > 1 ):
$return .= <<<IPSCONTENT

				<div class='ipsButtons'>
					
IPSCONTENT;

$baseUrl = \IPS\Http\Url::internal('app=nexus&module=subscriptions&controller=subscriptions', 'front', 'nexus_subscriptions');
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->register ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$baseUrl = $baseUrl->setQueryString( 'register', (int) \IPS\Widget\Request::i()->register );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$memberCurrency = ( ( isset( \IPS\Widget\Request::i()->cookie['currency'] ) and \in_array( \IPS\Widget\Request::i()->cookie['currency'], \IPS\nexus\Money::currencies() ) ) ? \IPS\Widget\Request::i()->cookie['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency() );
$return .= <<<IPSCONTENT

					<a href='#elCurrencyChooser_menu' data-ipsMenu id='elCurrencyChooser' class='ipsButton ipsButton--soft'><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $memberCurrency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></a>
					<ul class='ipsMenu ipsMenu_veryNarrow ipsHide' id='elCurrencyChooser_menu' 
IPSCONTENT;

if ( isset( $_SESSION['cart'] ) and \count( $_SESSION['cart'] ) ):
$return .= <<<IPSCONTENT
data-controller="nexus.front.store.currencySelect"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

foreach ( \IPS\nexus\Money::currencies() as $currency ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item' 
IPSCONTENT;

if ( $currency == $memberCurrency ):
$return .= <<<IPSCONTENT
data-selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( 'currency', $currency )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($currency); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_currency_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;

if ( $currency == $memberCurrency ):
$return .= <<<IPSCONTENT
<i class='fa-solid fa-check'></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

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

		</div>
	</header>
	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top" data-role="tablePagination">
			<div class="ipsButtonBar__pagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \is_array( $rows ) AND \count( $rows ) ):
$return .= <<<IPSCONTENT

		<i-data>
			<div class='ipsData ipsData--grid ipsData--subscriptions i-basis_300 
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='nexus.front.subscriptions.main' data-role='tableRows'>
				
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

			</div>
		</i-data>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_non_available', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--bottom" data-role="tablePagination">
			<div class="ipsButtonBar__pagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</section>
IPSCONTENT;

		return $return;
}}