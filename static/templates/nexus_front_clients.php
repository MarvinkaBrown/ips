<?php
namespace IPS\Theme;
class class_nexus_front_clients extends \IPS\Theme\Template
{	function address( $address ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div>
	{$address->address->toString('<br>')}
	
IPSCONTENT;

if ( isset( $address->address->vat ) and $address->address->vat ):
$return .= <<<IPSCONTENT

		<br>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_checkout_vat_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $address->address->vat, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<ul class='ipsButtons ipsButtons--start i-margin-top_2'>
	<li><a class="ipsButton ipsButton--inherit ipsButton--small" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=addresses&do=form&id={$address->id}", null, "clientsaddresses", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_address_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_address_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
	<li><a class="ipsButton ipsButton--inherit ipsButton--small" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=addresses&do=delete&id={$address->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "clientsaddresses", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmMessage='
IPSCONTENT;

if ( $address->primary_billing ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_address_confirm_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_address_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_address_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
</ul>


IPSCONTENT;

if ( !$address->primary_billing ):
$return .= <<<IPSCONTENT

	<ul class='ipsList ipsList--inline'>
		<li>
			<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_as_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		</li>
		
IPSCONTENT;

if ( !$address->primary_billing ):
$return .= <<<IPSCONTENT

			<li>
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=addresses&do=primary&id={$address->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "clientsaddresses", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--soft ipsButton--small' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_billing_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function addresses( $billingAddress=NULL, $otherAddresses=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox ipsPull">
	<header class='ipsPageHeader'>
		<div class="ipsPageHeader__row">
			<div class="ipsPageHeader__primary">
				<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_book', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
				<p class="ipsPageHeader__desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_book_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=addresses&do=form", null, "clientsaddresses", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_add_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</div>
	</header>
	
	
IPSCONTENT;

if ( $billingAddress ):
$return .= <<<IPSCONTENT

		<div class='ipsGrid ipsGrid--lines ipsGrid--commerce-address ipsGrid--auto-fit i-basis_360'>
			
IPSCONTENT;

if ( $billingAddress ):
$return .= <<<IPSCONTENT

				<div class='i-padding_3'>
					<h2 class='ipsTitle ipsTitle--h3 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_default_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clients", "nexus" )->address( $billingAddress );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

if ( \count( $otherAddresses ) ):
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		<div class='i-padding_3'>
			<h2 class='ipsTitle ipsTitle--h3 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'address_other_addresses', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<ul class='ipsGrid ipsGrid--lines ipsGrid--commerce-other-addresses ipsGrid--auto-fit i-basis_360'>
				
IPSCONTENT;

foreach ( $otherAddresses as $address ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clients", "nexus" )->address( $address );
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

</div>
IPSCONTENT;

		return $return;
}

	function alternatives( $protected=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='ipsBox ipsPull'>
	<header class='ipsPageHeader'>
		<div class="ipsPageHeader__row">
			<div class="ipsPageHeader__primary">
				<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
				<p class="ipsPageHeader__desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=alternatives&do=form", null, "clientsalternatives", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_add_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</div>
	</header>
	
IPSCONTENT;

if ( !$protected ):
$return .= <<<IPSCONTENT
	
		
IPSCONTENT;

if ( !\count( \IPS\nexus\Customer::loggedIn()->alternativeContacts() ) ):
$return .= <<<IPSCONTENT

			<div class='ipsEmptyMessage'>
				
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack('altcontact_add')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_none', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i-data>
				<ul class="ipsData ipsData--table ipsData--alternatives">
					
IPSCONTENT;

foreach ( \IPS\nexus\Customer::loggedIn()->alternativeContacts() as $contact ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item cAlternateContactPerms'>
							<div class='ipsData__icon'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $contact->alt_id );
$return .= <<<IPSCONTENT

							</div>
							<div class='ipsData__content'>
								<div class='ipsData__main'>
									<h2 class='ipsData__title'>
										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $contact->alt_id );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_can', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:
									</h2>
									<ul class='ipsList i-gap_1 cAlternateContactPerms_perms'>
										
IPSCONTENT;

if ( \count( $contact->purchases ) ):
$return .= <<<IPSCONTENT

											<li class='i-flex i-align-items_center i-gap_2'>
												<span class='ipsBadge ipsBadge--icon ipsBadge--positive i-flex_00'>
													<i class='fa-solid fa-check'></i>
												</span>
												<div class='i-flex_11'>
													
IPSCONTENT;

$pluralize = array( \count( $contact->purchases ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_perm_manage', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

													<ul class="ipsSubList">
														
IPSCONTENT;

foreach ( $contact->purchases as $purchase ):
$return .= <<<IPSCONTENT

															<li>
																<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
															</li>
														
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

													</ul>
												</div>
											</li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $contact->billing ):
$return .= <<<IPSCONTENT

											<li class='i-flex i-align-items_center i-gap_2'>
												<span class='ipsBadge ipsBadge--icon ipsBadge--positive i-flex_00'>
													<i class='fa-solid fa-check'></i>
												</span>
												<div class='i-flex_11'>
													
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_perm_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

												</div>
											</li>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<li class='i-flex i-align-items_center i-gap_2 i-opacity_4'>
												<span class='ipsBadge ipsBadge--icon ipsBadge--neutral i-flex_00'>
													<i class='fa-solid fa-xmark'></i>
												</span>
												<div class='i-flex_11'>
													
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_perm_billing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

												</div>
											</li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</ul>
								</div>
								<div class='i-basis_100'>
									<ul class='ipsButtons'>
										<li>
											<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=alternatives&do=form&id={$contact->alt_id->member_id}", null, "clientsalternatives", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--small ipsButton--wide' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
										</li>
										<li>
											<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=alternatives&do=delete&id={$contact->alt_id->member_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "clientsalternatives", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--small ipsButton--wide' data-confirm data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_delete_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'altcontact_delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
										</li>
									</ul>
								</div>
							</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</section>
IPSCONTENT;

		return $return;
}

	function billingAgreement( $billingAgreement, $purchases, $invoices, $invoicePagination ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsData__item i-margin-bottom_3">
	<div class=''>
		
IPSCONTENT;

if ( $billingAgreement->status() === \IPS\nexus\Customer\BillingAgreement::STATUS_ACTIVE ):
$return .= <<<IPSCONTENT

			<span class='ipsBadge ipsBadge--icon ipsBadge--positive' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
				<i class='fa-solid fa-check i-font-size_2'></i>
			</span>
		
IPSCONTENT;

elseif ( $billingAgreement->status() === \IPS\nexus\Customer\BillingAgreement::STATUS_SUSPENDED ):
$return .= <<<IPSCONTENT

			<span class='ipsBadge ipsBadge--icon ipsBadge--neutral'  data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_suspended', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
				<i class='fa-regular fa-clock i-font-size_2'></i>
			</span>
		
IPSCONTENT;

elseif ( $billingAgreement->status() === \IPS\nexus\Customer\BillingAgreement::STATUS_CANCELED ):
$return .= <<<IPSCONTENT

			<span class='ipsBadge ipsBadge--icon ipsBadge--negative'  data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
				<i class='fa-solid fa-xmark i-font-size_2'></i>
			</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsData__content'>
		<div class="ipsData__main">
			<h1 class='ipsData__title'>
IPSCONTENT;

$sprintf = array($billingAgreement->gw_id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
			<span class="cNexusPrice i-font-size_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->term(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		</div>
		<div class="">
			
IPSCONTENT;

if ( $billingAgreement->status() === \IPS\nexus\Customer\BillingAgreement::STATUS_ACTIVE ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->url()->setQueryString( array( 'do' => 'act', 'act' => 'suspend' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--soft" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_suspend_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_suspend', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $billingAgreement->status() === \IPS\nexus\Customer\BillingAgreement::STATUS_SUSPENDED ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->url()->setQueryString( array( 'do' => 'act', 'act' => 'reactivate' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--soft" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_reactivate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $billingAgreement->status() !== \IPS\nexus\Customer\BillingAgreement::STATUS_CANCELED ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->url()->setQueryString( array( 'do' => 'act', 'act' => 'cancel' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--soft" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>

IPSCONTENT;

if ( isset( $purchases[0] ) and \count( $purchases[0] ) ):
$return .= <<<IPSCONTENT

	<hr class="ipsHr">
	<h2 class="ipsTitle ipsTitle--h3 i-margin-bottom_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<ul>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clients", \IPS\Request::i()->app )->purchaseList( 0, $purchases, FALSE, $billingAgreement );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<hr class="ipsHr">
<h2 class="ipsTitle ipsTitle--h3 i-margin-bottom_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_payments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>

IPSCONTENT;

if ( $invoicePagination ):
$return .= <<<IPSCONTENT

	<div class="ipsBox i-padding_2 i-margin-bottom_3">
		{$invoicePagination}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<i-data>
		<ul class="ipsData ipsData--table ipsData--category cNexusOrderList">
			
IPSCONTENT;

foreach ( $invoices as $invoice ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clients", \IPS\Request::i()->app )->invoiceList( $invoice, $billingAgreement );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>

IPSCONTENT;

if ( $invoicePagination ):
$return .= <<<IPSCONTENT

	<div class="ipsBox i-padding_2 i-margin-top_3">
		{$invoicePagination}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function billingAgreements( $billingAgreements ) {
		$return = '';
		$return .= <<<IPSCONTENT


<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreements_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>

<p class='i-margin-bottom_3'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreements_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>


IPSCONTENT;

if ( !\count( $billingAgreements ) ):
$return .= <<<IPSCONTENT

	<div class='ipsBox ipsEmptyMessage'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreements_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<i-data>
		<ol class="ipsData ipsData--table ipsData--billing-agreements">
			
IPSCONTENT;

foreach ( $billingAgreements as $billingAgreement ):
$return .= <<<IPSCONTENT

				<li class='ipsData__item'>
					<div class='i-flex_00'>
						
IPSCONTENT;

if ( $billingAgreement['status'] === \IPS\nexus\Customer\BillingAgreement::STATUS_ACTIVE ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--icon ipsBadge--positive' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								<i class='fa-solid fa-check i-font-size_2'></i>
							</span>
						
IPSCONTENT;

elseif ( $billingAgreement['status'] === \IPS\nexus\Customer\BillingAgreement::STATUS_SUSPENDED ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--icon ipsBadge--neutral'  data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_suspended', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								<i class='fa-regular fa-clock i-font-size_2'></i>
							</span>
						
IPSCONTENT;

elseif ( $billingAgreement['status'] === \IPS\nexus\Customer\BillingAgreement::STATUS_CANCELED ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--icon ipsBadge--negative'  data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								<i class='fa-solid fa-xmark i-font-size_2'></i>
							</span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--icon ipsBadge--neutral'  data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_unknown', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
								<i class='fa-solid fa-question i-font-size_2'></i>
							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsData__content'>
						<div class='ipsData__main'>
							<strong class='i-font-size_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement['id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
							<p class='ipsData__meta cNexusPrice'>
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement['term'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</p>
						</div>
						<div class=''>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement['url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	</i-data>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function cards( $cards ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-flex i-align-items_center i-gap_2'>
	<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cards_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=cards&do=add", null, "clientscards", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cards_add_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cards_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>

<p class='i-margin-bottom_3'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cards_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</p>


IPSCONTENT;

if ( \count( $cards ) ):
$return .= <<<IPSCONTENT

	<ul class='ipsList ipsList--inline cNexusCards i-margin-top_3 ipsBox i-padding_3'>
		
IPSCONTENT;

foreach ( $cards as $card ):
$return .= <<<IPSCONTENT

			<li>
				
IPSCONTENT;

if ( $card['card_type'] === 'paypal' or $card['card_type'] === 'venmo' ):
$return .= <<<IPSCONTENT

					<div class='cNexusCards_name'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $card['card_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<span class='cNexusCards_number'>
						
IPSCONTENT;

if ( $card['card_type'] == 'american_express' OR $card['card_type'] == 'diners_club' ):
$return .= <<<IPSCONTENT

							XXXX XXXXXX X
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $card['card_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							XXXX XXXX XXXX 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $card['card_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $card['card_type'] ):
$return .= <<<IPSCONTENT

					<span class='cNexusCards_type cPayment cPayment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $card['card_type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span class='ipsInvisible'>
IPSCONTENT;

$val = "card_type_{$card['card_type']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $card['card_expire'] ):
$return .= <<<IPSCONTENT

					<span class='cNexusCards_expTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cards_exp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span class='cNexusCards_exp'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $card['card_expire'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=cards&do=delete&id={$card['id']}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "clientscards", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='cNexusCards_delete' data-confirm data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_this_card', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>&times;</a>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_credit_cards', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function credit( $balance, $pastWithdrawals, $pastWithdrawalsPagination, $canWithdraw, $canTopup ) {
		$return = '';
		$return .= <<<IPSCONTENT


<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>

<div class='ipsBox i-padding_3 i-margin-top_3'>
	<div class='ipsColumns'>
		<div class='ipsColumns__secondary i-basis_280'>
			
IPSCONTENT;

if ( is_countable( $balance ) AND \count( $balance ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $balance as $amount ):
$return .= <<<IPSCONTENT

					<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_balance', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \count( $balance ) > 1 ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $amount->currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
					<div class='cNexusCredit_total i-margin-bottom_3'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $amount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( $balance !== NULL ):
$return .= <<<IPSCONTENT

				<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_balance', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<div class='cNexusCredit_total i-margin-bottom_3'>
					
IPSCONTENT;

$return .= new \IPS\nexus\Money( 0, \IPS\nexus\Customer::loggedIn()->defaultCurrency() );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $canWithdraw or $canTopup ):
$return .= <<<IPSCONTENT

				<ul class='i-grid i-gap_2'>
					
IPSCONTENT;

if ( $canTopup ):
$return .= <<<IPSCONTENT

						<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=credit&do=topup", null, "clientscredit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--wide' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_add_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $canWithdraw ):
$return .= <<<IPSCONTENT

						<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=credit&do=withdraw", null, "clientscredit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_withdraw_dialog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--soft ipsButton--wide' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_withdraw_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_withdraw', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( \IPS\nexus\Customer::loggedIn()->parentContacts( array('billing=1') ) ) ):
$return .= <<<IPSCONTENT

				<hr class='ipsHr'>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_altcontact', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

foreach ( \IPS\nexus\Customer::loggedIn()->parentContacts( array('billing=1') ) as $contact ):
$return .= <<<IPSCONTENT

					<hr class='ipsHr'>
					<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $contact->main_id, 'tiny' );
$return .= <<<IPSCONTENT

						<div>
							<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contact->main_id->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>

							
IPSCONTENT;

$val = FALSE;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $contact->main_id->cm_credits as $credit ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $credit->amount->isGreaterThanZero() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$val = TRUE;
$return .= <<<IPSCONTENT

									<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_balance', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \count( $contact->main_id->cm_credits ) > 1 ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $credit->currency, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
									<div class='cNexusCredit_total cNexusCredit_contact i-margin-bottom_3'>
										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $credit, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									</div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !$val ):
$return .= <<<IPSCONTENT

								<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_balance', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
								<div class='cNexusCredit_total cNexusCredit_contact i-margin-bottom_3'>
									
IPSCONTENT;

$return .= new \IPS\nexus\Money( 0, $contact->main_id->defaultCurrency() );
$return .= <<<IPSCONTENT

								</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsColumns__primary'>
			
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->withdraw ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Widget\Request::i()->withdraw === 'success' ):
$return .= <<<IPSCONTENT

					<p class="ipsMessage ipsMessage--success">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payout_status_success', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					<br>
				
IPSCONTENT;

elseif ( \IPS\Widget\Request::i()->withdraw === 'pending' ):
$return .= <<<IPSCONTENT

					<p class="ipsMessage ipsMessage--info">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payout_status_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					<br>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( \count( $pastWithdrawals ) or $canWithdraw ):
$return .= <<<IPSCONTENT

				<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_withdrawal_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				
IPSCONTENT;

if ( \count( $pastWithdrawals ) ):
$return .= <<<IPSCONTENT

					<i-data>
						<ol class="ipsData ipsData--table ipsData--withdrawl-history">
							
IPSCONTENT;

foreach ( $pastWithdrawals as $withdrawal ):
$return .= <<<IPSCONTENT

								<li class='ipsData__item'>
									<div class='i-flex_00'>
										
IPSCONTENT;

if ( $withdrawal->status === \IPS\nexus\Payout::STATUS_COMPLETE ):
$return .= <<<IPSCONTENT

											<span class='ipsBadge ipsBadge--icon ipsBadge--positive' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_withdraw_complete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
												<i class='fa-solid fa-check i-font-size_2'></i>
											</span>
										
IPSCONTENT;

elseif ( $withdrawal->status === \IPS\nexus\Payout::STATUS_PENDING ):
$return .= <<<IPSCONTENT

											<span class='ipsBadge ipsBadge--icon ipsBadge--neutral'  data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_withdraw_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
												<i class='fa-regular fa-clock i-font-size_2'></i>
											</span>
										
IPSCONTENT;

elseif ( $withdrawal->status === \IPS\nexus\Payout::STATUS_CANCELED ):
$return .= <<<IPSCONTENT

											<span class='ipsBadge ipsBadge--icon ipsBadge--negative'  data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_withdraw_cancelled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
												<i class='fa-solid fa-xmark i-font-size_2'></i>
										
IPSCONTENT;

elseif ( $withdrawal->status == \IPS\nexus\Payout::STATUS_PROCESSING ):
$return .= <<<IPSCONTENT

											<span class='ipsBadge ipsBadge--icon ipsBadge--intermediary'  data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_withdraw_processing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
												<i class='fa-solid fa-circle-notch fa-spin'></i>
											</span>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
									<div class='ipsData__content'>
										<div class='ipsData__main'>
											<strong class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $withdrawal->amount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
											<p class='ipsData__meta'>
												
IPSCONTENT;

$val = ( $withdrawal->date instanceof \IPS\DateTime ) ? $withdrawal->date : \IPS\DateTime::ts( $withdrawal->date );$return .= $val->html();
$return .= <<<IPSCONTENT

											</p>
										</div>
										
IPSCONTENT;

if ( $withdrawal->status === \IPS\nexus\Payout::STATUS_PENDING ):
$return .= <<<IPSCONTENT

											<div class=''>
												<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=credit&do=cancel&id={$withdrawal->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "clientscredit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--small' data-confirm data-confirmMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_withdraw_cancel_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
											</div>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ol>
					</i-data>
					{$pastWithdrawalsPagination}
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<p class='ipsEmptyMessage'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'credit_no_withdrawals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function donations(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class="ipsBox ipsPull">
	<header class="ipsPageHeader ipsPageHeader--padding">
		<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'current_donation_goals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	</header>
	
	
IPSCONTENT;

if ( \IPS\Widget\Request::i()->thanks ):
$return .= <<<IPSCONTENT

		<p class="ipsMessage ipsMessage--success">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'thanks_for_your_donation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	<div class="ipsFluid i-gap_lines i-basis_400">
		
IPSCONTENT;

foreach ( \IPS\nexus\Donation\Goal::roots() as $goal ):
$return .= <<<IPSCONTENT

			<div class="i-flex i-flex-direction_column" id="elDonationGoal
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				<div class="i-padding_3 i-flex_11">
					<h2 class="ipsTitle ipsTitle--h3 i-margin-bottom_3">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
					
IPSCONTENT;

if ( $desc = \IPS\Member::loggedIn()->language()->get("nexus_donategoal_{$goal->id}_desc") ):
$return .= <<<IPSCONTENT

						<div class="i-margin-bottom_3">
							<div class="ipsRichText">
								{$desc}
							</div>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="i-padding_3 i-padding-top_0">
					
IPSCONTENT;

if ( $goal->goal ):
$return .= <<<IPSCONTENT

						<p class="i-text-align_center i-margin-bottom_2">
							<strong>
IPSCONTENT;

$sprintf = array(new \IPS\nexus\Money( $goal->current, $goal->currency ), new \IPS\nexus\Money( $goal->goal, $goal->currency )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'donate_progress', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong>
						</p>
						<progress class='ipsProgress ipsProgress--donation i-margin-bottom_2' max='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->goal, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->current, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></progress>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<p class="i-text-align_center">
							<strong>
IPSCONTENT;

$sprintf = array(new \IPS\nexus\Money( $goal->current, $goal->currency )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'donate_sofar', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong>
						</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsSubmitRow">
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $goal->url()->setQueryString( 'noDesc', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary" 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT
 data-ipsDialog 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 >
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'donate_to_goal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
</section>
IPSCONTENT;

		return $return;
}

	function info( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='ipsBox ipsPull'>
	<header class="ipsPageHeader ipsPageHeader--padding">
		<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'customerinfo_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	</header>
	{$form}
</section>
IPSCONTENT;

		return $return;
}

	function invoice( $invoice ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox i-padding_3 i-margin-bottom_3'>
	<div class='i-flex i-justify-content_space-between i-margin-bottom_2'>
		<div>
			<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 #
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
			<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_placed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $invoice->date instanceof \IPS\DateTime ) ? $invoice->date : \IPS\DateTime::ts( $invoice->date );$return .= $val->html(FALSE);
$return .= <<<IPSCONTENT
</p>
		</div>
		<div class='i-flex i-flex-direction_column i-align-items_stretch i-justify-content_center i-gap_1'>
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
' target="_blank" rel='noopener' class='ipsButton ipsButton--small ipsButton--soft ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_invoice_print', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

if ( $invoice->po ):
$return .= <<<IPSCONTENT

				<p class='i-margin-block_2'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_po_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->po, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url()->setQueryString( array( 'do' => 'poNumber' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_po_number_dialog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='i-font-size_-2' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_po_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</a>
				</p>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url()->setQueryString( array( 'do' => 'poNumber' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_po_number_dialog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_po_number_add_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--text ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_po_number_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>

	<hr class='ipsHr'>

	<div class='ipsSpanGrid i-margin-top_3 cNexusInvoiceView'>
		<div class='ipsSpanGrid__6'>
			
IPSCONTENT;

if ( $invoice->billaddress ):
$return .= <<<IPSCONTENT

				<div class='ipsSpanGrid i-margin-bottom_4'>
					
IPSCONTENT;

if ( $invoice->billaddress  ):
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


			
IPSCONTENT;

if ( $invoice->notes ):
$return .= <<<IPSCONTENT

				<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<div class='i-margin-bottom_2'>
					
IPSCONTENT;

$return .= nl2br( htmlspecialchars( $invoice->notes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) );
$return .= <<<IPSCONTENT

				</div>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url()->setQueryString( array( 'do' => 'notes' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_edit_invoice_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</a>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<p class='i-margin-top_3'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url()->setQueryString( array( 'do' => 'notes' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--soft ipsButton--small' data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_edit_invoice_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_add_invoice_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_add_invoice_notes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsSpanGrid__6 i-text-align_center'>
			<div class='ipsInnerBox i-padding_3'>
				<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_order_total', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<span class='cNexusOrderBadge cNexusOrderBadge_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->status, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "istatus_{$invoice->status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span> &nbsp;&nbsp;&nbsp;<span class='cNexusPrice'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->total, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>

				
IPSCONTENT;

if ( $invoice->status === $invoice::STATUS_PENDING ):
$return .= <<<IPSCONTENT

					<ul class='i-grid i-gap_2'>
						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->checkoutUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary ipsButton--wide ipsButton--small' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_pay_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_pay_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url()->setQueryString( 'do', 'cancel' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--soft ipsButton--wide ipsButton--small' data-confirm title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_cancel_invoice', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					</ul>
				
IPSCONTENT;

elseif ( $invoice->status === $invoice::STATUS_EXPIRED ):
$return .= <<<IPSCONTENT

					<p class='i-text-align_center i-color_soft i-margin-top_3'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_invoice_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
				
IPSCONTENT;

elseif ( $invoice->status === $invoice::STATUS_CANCELED ):
$return .= <<<IPSCONTENT

					<p class='i-text-align_center i-color_soft i-margin-top_3'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_invoice_cancelled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
				
IPSCONTENT;

elseif ( $invoice->status === $invoice::STATUS_PAID ):
$return .= <<<IPSCONTENT

					<p class='i-text-align_center i-color_soft i-margin-top_3'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_invoice_paid', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
						
IPSCONTENT;

if ( $invoice->billaddress AND \count( $invoice->transactions( array( \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_WAITING, \IPS\nexus\Transaction::STATUS_HELD, \IPS\nexus\Transaction::STATUS_REVIEW, \IPS\nexus\Transaction::STATUS_REFUSED, \IPS\nexus\Transaction::STATUS_REFUNDED, \IPS\nexus\Transaction::STATUS_PART_REFUNDED, \IPS\nexus\Transaction::STATUS_GATEWAY_PENDING ) ) ) > 0  ):
$return .= <<<IPSCONTENT

							<a href='#elPaymentDetails' data-ipsDialog data-ipsDialog-content='#elPaymentDetails' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_payment_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-size='narrow'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_view_payment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>

					
IPSCONTENT;

if ( $invoice->billaddress ):
$return .= <<<IPSCONTENT

						<div id='elPaymentDetails' class='i-text-align_start ipsJS_hide'>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clients", "nexus" )->paymentLog( $invoice );
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>
</div>



IPSCONTENT;

$summary = $invoice->summary();
$return .= <<<IPSCONTENT

<div class='ipsBox'>
    <h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
    <div class='ipsBox__content'>
        <i-data>
			<ul class="ipsData ipsData--table ipsData--order-details">
				
IPSCONTENT;

foreach ( $summary['items'] as $k => $item ):
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
" alt='' loading='lazy'>
								
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
							<div class='ipsData__content'>
								<div class='ipsData__main'>
									<h2 class='ipsData__title'>
										
IPSCONTENT;

if ( $url = $item->url() ):
$return .= <<<IPSCONTENT

											<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 x</span> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 x</span> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</h2>
									<dl class='cNexusPurchase_info'>
										{$item->detailsForDisplay( 'invoice' )}
									</dl>
								</div>
								<div class='i-basis_140 i-text-align_end i-align-self_start'>
									<span class='cNexusPrice i-font-size_2'>
										
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
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['total'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
					</div>
				</li>
			</ul>
		</i-data>
    </div>
</div>
IPSCONTENT;

		return $return;
}

	function invoiceList( $invoice, $fromBillingAgreement=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsData__item'>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_view_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
	<div class='ipsData__content'>
		
IPSCONTENT;

if ( !$fromBillingAgreement ):
$return .= <<<IPSCONTENT

			<div class='ipsData__main'>
				
IPSCONTENT;

$summary = $invoice->summary();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $summary['items'] ) ):
$return .= <<<IPSCONTENT

					<ul class='i-grid i-gap_3 cNexusOrderList_items'>
						
IPSCONTENT;

foreach ( $summary['items'] as $k => $item ):
$return .= <<<IPSCONTENT

							<li class='i-flex i-gap_2'>
								<div class='i-basis_40 i-flex_00' 
IPSCONTENT;

if ( $item::$title ):
$return .= <<<IPSCONTENT
title='
IPSCONTENT;

$val = "{$item::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<div class="ipsThumb">
										
IPSCONTENT;

if ( $image = $item->image() ):
$return .= <<<IPSCONTENT

											<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
										
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

									</div>
								</div>
								<div class='i-flex_11'>
									
IPSCONTENT;

if ( $url = $item->url() ):
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='i-color_hard i-font-weight_600'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<span class='i-color_hard i-font-weight_600'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'quantity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
									<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_unit_price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->price, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
								</div>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<span class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'orders_no_items', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='i-basis_220'>
			<strong class='i-font-size_2'>
IPSCONTENT;

$val = ( $invoice->date instanceof \IPS\DateTime ) ? $invoice->date : \IPS\DateTime::ts( $invoice->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</strong><br>
			<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 #
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			<p class='i-flex i-gap_1 i-align-items_center i-flex-wrap_wrap'>
				<span class='cNexusOrderBadge cNexusOrderBadge_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->status, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "istatus_{$invoice->status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span> <strong class='i-font-size_2 cNexusPrice'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->total, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
			</p>
		</div>
		<ul class='ipsButtons ipsButtons--fill i-margin-block_2'>
			
IPSCONTENT;

if ( $invoice->status === $invoice::STATUS_PENDING ):
$return .= <<<IPSCONTENT

				<li>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->checkoutUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_pay_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_pay_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
				<li>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url()->setQueryString( 'do', 'cancel' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm class='ipsButton ipsButton--inherit ipsButton--small' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'orders_cancel_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'orders_cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<li>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'orders_view_details_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_view_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		</ul>
	</div>
</li>

IPSCONTENT;

		return $return;
}

	function invoices( $invoices, $pagination ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('orders_title') );
$return .= <<<IPSCONTENT

<div class="ipsBox ipsPull">
	
IPSCONTENT;

if ( \count( $invoices ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( trim($pagination) ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--top">
				<div class="ipsButtonBar__pagination">
					{$pagination}
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<i-data>
			<ul class="ipsData ipsData--table ipsData--invoices cNexusOrderList">
				
IPSCONTENT;

foreach ( $invoices as $invoice ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clients", \IPS\Request::i()->app )->invoiceList( $invoice );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
		
IPSCONTENT;

if ( trim($pagination) ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				<div class="ipsButtonBar__pagination">
					{$pagination}
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'orders_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function packageFields( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $item->details as $k => $v ):
$return .= <<<IPSCONTENT

    <dt class='ipsMinorTitle i-margin-top_2'><strong>
IPSCONTENT;

$val = "nexus_pfield_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></dt>
    <dd>
IPSCONTENT;

$return .= \IPS\nexus\Package\CustomField::load( $k )->displayValue( $v );
$return .= <<<IPSCONTENT
</dd>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function paymentLog( $invoice ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
	<ul class="ipsData ipsData--table ipsData--payment-log cNexusPaymentLog">
		
IPSCONTENT;

foreach ( $invoice->transactions( array( \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_WAITING, \IPS\nexus\Transaction::STATUS_HELD, \IPS\nexus\Transaction::STATUS_REVIEW, \IPS\nexus\Transaction::STATUS_REFUSED, \IPS\nexus\Transaction::STATUS_REFUNDED, \IPS\nexus\Transaction::STATUS_PART_REFUNDED, \IPS\nexus\Transaction::STATUS_GATEWAY_PENDING ) ) as $thisTransaction ):
$return .= <<<IPSCONTENT

			<li class='ipsData__item'>
				
IPSCONTENT;

if ( $thisTransaction->status === $thisTransaction::STATUS_PAID ):
$return .= <<<IPSCONTENT

					<div class='ipsData__icon i-color_positive'>
						<i class="fa-solid fa-check-circle"></i>
					</div>
					<div class='ipsData__main'>
						<div class='ipsData__title'>
IPSCONTENT;

$sprintf = array($thisTransaction->amount, ( $thisTransaction->method ) ? $thisTransaction->method->_title : \IPS\Member::loggedIn()->language()->get('account_credit')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_paid', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
						<p class='ipsData__meta'>
IPSCONTENT;

$val = ( $thisTransaction->date instanceof \IPS\DateTime ) ? $thisTransaction->date : \IPS\DateTime::ts( $thisTransaction->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
					</div>
				
IPSCONTENT;

elseif ( $thisTransaction->status === $thisTransaction::STATUS_WAITING ):
$return .= <<<IPSCONTENT

					<div class='ipsData__icon i-color_issue'>
						<i class="fa-solid fa-triangle-exclamation"></i>
					</div>
					<div class='ipsData__main'>
						<div class='ipsData__title'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisTransaction->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($thisTransaction->method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_wait', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></div>
					</div>
				
IPSCONTENT;

elseif ( $thisTransaction->status === $thisTransaction::STATUS_HELD or $thisTransaction->status === $thisTransaction::STATUS_REVIEW ):
$return .= <<<IPSCONTENT

					<div class='ipsData__icon i-color_issue'>
						<i class="fa-regular fa-clock"></i>
					</div>
					<div class='ipsData__main'>
						<div class='ipsData__title'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_awaiting_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$sprintf = array($thisTransaction->amount, $thisTransaction->method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_paid', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							<span class='i-color_soft'>
IPSCONTENT;

$val = ( $thisTransaction->date instanceof \IPS\DateTime ) ? $thisTransaction->date : \IPS\DateTime::ts( $thisTransaction->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
						</div>
						
IPSCONTENT;

if ( $thisTransaction->gw_id ):
$return .= <<<IPSCONTENT

							<p class='ipsData__meta'>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment_ref', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisTransaction->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

elseif ( $thisTransaction->status === $thisTransaction::STATUS_GATEWAY_PENDING ):
$return .= <<<IPSCONTENT

					<div class='ipsData__icon i-color_issue'>
						<i class="fa-regular fa-clock"></i>
					</div>
					<div class='ipsData__main'>
						<div class='ipsData__title'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_processing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$sprintf = array($thisTransaction->amount, $thisTransaction->method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_paid', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong>
							<span class='i-color_soft'>
IPSCONTENT;

$val = ( $thisTransaction->date instanceof \IPS\DateTime ) ? $thisTransaction->date : \IPS\DateTime::ts( $thisTransaction->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
						</p>
					</div>
				
IPSCONTENT;

elseif ( $thisTransaction->status === $thisTransaction::STATUS_REFUSED ):
$return .= <<<IPSCONTENT

					<div class='ipsData__icon i-color_warning'>
						<i class="fa-solid fa-circle-xmark"></i>
					</div>
					<div class='ipsData__main'>
						<div class='ipsData__title'>
IPSCONTENT;

$sprintf = array($thisTransaction->amount, $thisTransaction->method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_declined', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
						
IPSCONTENT;

if ( $thisTransaction->gw_id ):
$return .= <<<IPSCONTENT

							<p class='ipsData__meta'>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment_ref', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisTransaction->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( isset( $thisTransaction->extra['processor_response'] ) ):
$return .= <<<IPSCONTENT

						<p class='ipsData__meta'>
							
IPSCONTENT;

$responseCode = $thisTransaction->extra['processor_response']['response_code'];
$return .= <<<IPSCONTENT

							<br>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'processor_response_avs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisTransaction->extra['processor_response']['avs_code'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;

$val = "processor_response_avs__{$thisTransaction->extra['processor_response']['avs_code']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
							<br>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'processor_response_cvv', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisTransaction->extra['processor_response']['cvv_code'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$val = "processor_response_cvv__{$thisTransaction->extra['processor_response']['cvv_code']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
							<br>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'processor_response_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $responseCode, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \is_numeric( $responseCode ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$responseCode = (int)$responseCode;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<br>
							<span>
IPSCONTENT;

$val = "processor_response_code__{$responseCode}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

elseif ( $thisTransaction->status === $thisTransaction::STATUS_REFUNDED ):
$return .= <<<IPSCONTENT

					<div class='ipsData__icon i-color_soft'>
						<i class="fa-solid fa-circle-arrow-left"></i>
					</div>
					<div class='ipsData__main'>
					<div class='ipsData__title'>
IPSCONTENT;

$sprintf = array($thisTransaction->amount, ( $thisTransaction->method ) ? $thisTransaction->method->_title : \IPS\Member::loggedIn()->language()->get('account_credit')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_refunded', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
						
IPSCONTENT;

if ( $thisTransaction->gw_id ):
$return .= <<<IPSCONTENT

							<p class='ipsData__meta'>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment_ref', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisTransaction->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

elseif ( $thisTransaction->status === $thisTransaction::STATUS_PART_REFUNDED ):
$return .= <<<IPSCONTENT

					<div class='ipsData__icon i-color_positive'>
						<i class="fa-solid fa-check-circle"></i>
					</div>
					<div class='ipsData__main'>
						<div class='ipsData__title'>
IPSCONTENT;

$sprintf = array($thisTransaction->amount, ( $thisTransaction->method ) ? $thisTransaction->method->_title : \IPS\Member::loggedIn()->language()->get('account_credit')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_paid', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							<span class='i-color_soft'>
IPSCONTENT;

$val = ( $thisTransaction->date instanceof \IPS\DateTime ) ? $thisTransaction->date : \IPS\DateTime::ts( $thisTransaction->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
						</div>
						
IPSCONTENT;

if ( $thisTransaction->partial_refund->amount->isGreaterThanZero() ):
$return .= <<<IPSCONTENT

							<p>
								
IPSCONTENT;

$sprintf = array($thisTransaction->partial_refund); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_part_refunded', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $thisTransaction->credit->amount->isGreaterThanZero() ):
$return .= <<<IPSCONTENT

							<p>
								
IPSCONTENT;

$sprintf = array($thisTransaction->credit); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_part_credited', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $thisTransaction->gw_id ):
$return .= <<<IPSCONTENT

							<p class='ipsData__meta'>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment_ref', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisTransaction->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function purchase( $purchase ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$extra = $purchase->clientAreaPage();
$return .= <<<IPSCONTENT

<div class='ipsBox'>
	<div class='ipsColumns cNexusPurchase i-padding_2'>
		<div class='ipsColumns__secondary i-basis_200'>
			<span class='ipsThumb'>
				
IPSCONTENT;

if ( $image = $purchase->image() ):
$return .= <<<IPSCONTENT

					<img src="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( '(', ')' ), array( '\(', '\)' ), $image->url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
				
IPSCONTENT;

elseif ( $icon = $purchase->getIcon() ):
$return .= <<<IPSCONTENT

				    <i class='fa fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $purchase->type == 'giftvoucher' ):
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-gift"></i>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-box-open"></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</span>

			<hr class='ipsHr'>

			
IPSCONTENT;

if ( ( $purchase->expire AND $canRenewUntil = $purchase->canRenewUntil(NULL,TRUE) ) OR $purchase->canCancel() ):
$return .= <<<IPSCONTENT

				<ul class='i-grid i-gap_2 cNexusPurchase_renewActions'>
					
IPSCONTENT;

if ( $purchase->expire AND $canRenewUntil ):
$return .= <<<IPSCONTENT

						<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=purchases&do=renew&id={$purchase->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, "front", "clientspurchaserenew", array( \IPS\Http\Url\Friendly::seoTitle( $purchase->name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary ipsButton--wide ipsButton--small' title="
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
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $purchase->canCancel() ):
$return .= <<<IPSCONTENT

						<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=clients&controller=purchases&do=cancel&id={$purchase->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, "front", "clientspurchasecancel", array( \IPS\Http\Url\Friendly::seoTitle( $purchase->name ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--text ipsButton--wide ipsButton--small' data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_cancel_renewal_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_cancel_renewal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			<dl class='cNexusPurchase_info i-margin-top_3'>
				<dt class='ipsMinorTitle'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></dt>
				<dd>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->start->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</dd>
				
IPSCONTENT;

if ( $purchase->expire ):
$return .= <<<IPSCONTENT

					<dt class='ipsMinorTitle'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_expire', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></dt>
					<dd>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->expire->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</dd>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $purchase->renewals and !$purchase->grouped_renewals ):
$return .= <<<IPSCONTENT

					<dt class='ipsMinorTitle'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renewal_terms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></dt>
					<dd>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->renewals->toDisplay( $purchase->member ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</dd>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $purchase->billing_agreement AND !$purchase->billing_agreement->canceled ):
$return .= <<<IPSCONTENT

					<dt class='ipsMinorTitle'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_billing_agreement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></dt>
					<dd class='i-link-color_inherit'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->billing_agreement->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->billing_agreement->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></dd>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</dl>
		</div>
		<div class='ipsColumns__primary'>
			<h1 class="ipsTitle ipsTitle--h3">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
			<p>
				
IPSCONTENT;

if ( $purchase->cancelled ):
$return .= <<<IPSCONTENT

					<span class='ipsBadge ipsBadge--negative'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>&nbsp;
				
IPSCONTENT;

elseif ( !$purchase->active ):
$return .= <<<IPSCONTENT

					<span class='ipsBadge ipsBadge--neutral'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>&nbsp;
				
IPSCONTENT;

elseif ( $purchase->expire ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $purchase->grace_period and $purchase->expire->getTimestamp() < time() ):
$return .= <<<IPSCONTENT

						<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_in_grace_period_front', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>&nbsp;
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>&nbsp;
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$val = "{$purchase->getTypeTitle()}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
			<br><br>

			
IPSCONTENT;

if ( $pendingInvoice = $purchase->invoice_pending and $pendingInvoice->status === $pendingInvoice::STATUS_PENDING ):
$return .= <<<IPSCONTENT

				<div class='i-margin-bottom_3 ipsMessage ipsMessage--info'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_pending_invoice', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $pendingInvoice->canView() ):
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingInvoice->checkoutUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_pay_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_pay_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( $lkey = $purchase->licenseKey() ):
$return .= <<<IPSCONTENT

				<div class='i-background_2 i-padding_3 i-text-align_center i-margin-bottom_4'>
					<h2 class='ipsMinorTitle i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_license_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<div class='i-font-size_2 i-font-family_monospace cNexusLicenseKey'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lkey->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( isset( $extra['packageInfo'] ) ):
$return .= <<<IPSCONTENT

				<h2 class='ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_package_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $extra['packageInfo'], array('i-margin-bottom_3') );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( isset( $extra['purchaseInfo'] ) ):
$return .= <<<IPSCONTENT

				<div>{$extra['purchaseInfo']}</div>
				<br><br>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function purchaseList( $rootId, $purchases, $halfSize=FALSE, $fromBillingAgreement=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $purchases[$rootId] as $purchase ):
$return .= <<<IPSCONTENT

	<li class='cNexusPurchaseList i-flex i-flex-direction_column i-gap_2 i-padding_3'>
		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsThumb i-aspect-ratio_7'>
			
IPSCONTENT;

if ( $image = $purchase->image() ):
$return .= <<<IPSCONTENT

				<img src="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( '(', ')' ), array( '\(', '\)' ), $image->url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
			
IPSCONTENT;

elseif ( $icon = $purchase->getIcon() ):
$return .= <<<IPSCONTENT

			    <i class='fa fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 i-font-size_7'></i>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</a>
		<h2 class='ipsTitle ipsTitle--h3'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchases_manage_purchase', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
		<p class="i-color_soft i-font-weight_500">
IPSCONTENT;

$val = "{$purchase->getTypeTitle()}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

if ( isset( $purchases[ $purchase->id ]) ):
$return .= <<<IPSCONTENT

			<ul class='cNexusPurchaseSubList'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clients", \IPS\Request::i()->app )->purchaseList( $purchase->id, $purchases, TRUE );
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='i-margin-top_auto i-padding-top_3 cNexusPurchaseList_info'>
		
			
IPSCONTENT;

if ( !$fromBillingAgreement and $pendingInvoice = $purchase->invoice_pending and $pendingInvoice->status === $pendingInvoice::STATUS_PENDING ):
$return .= <<<IPSCONTENT

				<p>
					<i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_pending_invoice', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $pendingInvoice->canView() ):
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $pendingInvoice->checkoutUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_pay_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_pay_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
				<hr class='ipsHr'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
			<div class='i-flex i-flex-wrap_wrap i-gap_3'>
				<div>
					<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_status', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<span>
IPSCONTENT;

if ( $purchase->active ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $purchase->grace_period and $expire = $purchase->expire and $expire->getTimestamp() < time() ):
$return .= <<<IPSCONTENT
<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_in_grace_period_front', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $purchase->cancelled ):
$return .= <<<IPSCONTENT
<span class='ipsBadge ipsBadge--negative'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<span class='ipsBadge ipsBadge--neutral'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
				</div>
				<div>
					<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->start->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</div>
				
IPSCONTENT;

if ( $purchase->expire ):
$return .= <<<IPSCONTENT

					<div>
						<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_expire', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->expire->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !$fromBillingAgreement and $purchase->billing_agreement AND !$purchase->billing_agreement->canceled ):
$return .= <<<IPSCONTENT

					<div>
						<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_billing_agreement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<span><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->billing_agreement->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->billing_agreement->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></span>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
		<div class='ipsButtons ipsButtons--fill ipsButtons--start i-padding-top_2'>
			
IPSCONTENT;

if ( $fromBillingAgreement ):
$return .= <<<IPSCONTENT

				<!-- <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary ipsButton--small' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchases_manage_purchase', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-box-open"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a> -->
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $canRenewUntil = $purchase->canRenewUntil(NULL,TRUE) ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url()->setQueryString( 'do', 'renew' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--inherit ipsButton--small' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renew_now_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $canRenewUntil === TRUE OR $canRenewUntil > 1 ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-size='narrow'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class="fa-solid fa-arrows-rotate"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchases_renewal_price', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= new \IPS\nexus\Money( $purchase->renewal_price, ( ( isset( \IPS\Request::i()->cookie['currency'] ) and \in_array( \IPS\Request::i()->cookie['currency'], \IPS\nexus\Money::currencies() ) ) ? \IPS\Request::i()->cookie['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency() ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<!-- <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary ipsButton--small' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchases_manage_purchase', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-pen-to-square"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'manage', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a> -->
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function purchases( $purchases ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox ipsPull">
	<header class="ipsPageHeader ipsPageHeader--padding">
		<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchases_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		
IPSCONTENT;

if ( \count( $purchases ) ):
$return .= <<<IPSCONTENT

			<p class='ipsPageHeader__desc'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchases_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	
IPSCONTENT;

if ( \count( $purchases ) ):
$return .= <<<IPSCONTENT

		<ul class="ipsGrid ipsGrid--lines i-basis_300">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clients", \IPS\Request::i()->app )->purchaseList( 0, $purchases );
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='ipsEmptyMessage'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchases_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}}