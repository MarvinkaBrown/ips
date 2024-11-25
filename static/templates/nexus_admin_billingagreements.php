<?php
namespace IPS\Theme;
class class_nexus_admin_billingagreements extends \IPS\Theme\Template
{	function view( $billingAgreement, $purchases, $transactions ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $billingAgreement->status() == $billingAgreement::STATUS_ACTIVE and !$billingAgreement->next_cycle ):
$return .= <<<IPSCONTENT

	<p class="ipsMessage ipsMessage--warning">
IPSCONTENT;

$sprintf = array($billingAgreement->acpUrl()->setQueryString( 'do', 'reconcile' )->csrf()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_run_failed', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='ipsSpanGrid' data-ips-template="view">
	<div class='ipsSpanGrid__4'>
		
IPSCONTENT;

if ( $billingAgreement->status() == $billingAgreement::STATUS_ACTIVE ):
$return .= <<<IPSCONTENT

				<p class='i-background_positive i-padding_3 i-text-align_center i-margin-bottom_3'>
					<span class='ipsTitle ipsTitle--h3'><i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</p>
		
IPSCONTENT;

elseif ( $billingAgreement->status() == $billingAgreement::STATUS_SUSPENDED ):
$return .= <<<IPSCONTENT

			<p class='i-background_3 i-padding_3 i-text-align_center i-margin-bottom_3'>
				<span class='ipsTitle ipsTitle--h3'><i class='fa-solid fa-circle-exclamation'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_suspended', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</p>
		
IPSCONTENT;

elseif ( $billingAgreement->status() == $billingAgreement::STATUS_CANCELED ):
$return .= <<<IPSCONTENT

			<p class='i-background_negative i-padding_3 i-text-align_center i-margin-bottom_3'>
				<span class='ipsTitle ipsTitle--h3'><i class='fa-solid fa-xmark-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsBox i-margin-bottom_1">
			<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<i-data>
				<ul class="ipsData ipsData--table ipsData--billing-agreements">
					<li class="ipsData__item">
						<span class="i-basis_120">
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ba_gw_id', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						</span>
						<span class="">
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</span>
					</li>
					<li class="ipsData__item">
						<span class="i-basis_120">
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_term', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						</span>
						<span class="">
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->term(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</span>
					</li>
					<li class="ipsData__item">
						<span class="i-basis_120">
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ba_started', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						</span>
						<span class="">
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->started->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</span>
					</li>
					
IPSCONTENT;

if ( $billingAgreement->next_cycle ):
$return .= <<<IPSCONTENT

						<li class="ipsData__item">
							<span class="i-basis_120">
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_next_payment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</span>
							<span class="">
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->nextPaymentDate()->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</span>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
		</div>
		<div class="ipsBox i-margin-bottom_1">
			<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_customer_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

if ( $billingAgreement->member ):
$return .= <<<IPSCONTENT

				<div class='i-padding_3 ipsPhotoPanel ipsPhotoPanel_small'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsUserPhoto ipsUserPhoto--small' alt="" loading="lazy"></a>
					<div>
						<h3 class='i-font-size_2 i-link-color_inherit'><strong><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></strong></h3>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $billingAgreement->member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
						
IPSCONTENT;

$sprintf = array($billingAgreement->member->joined->localeDate()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_customer_since', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
<br>
						
IPSCONTENT;

$sprintf = array($billingAgreement->member->totalSpent()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_spent', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</div>
				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class='i-padding_3'>
					<p class="ipsMessage ipsMessage--error">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_no_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	<div class='ipsSpanGrid__8'>
		
IPSCONTENT;

if ( $purchases ):
$return .= <<<IPSCONTENT

			<div class="i-margin-bottom_1">
				<h2 class="acpBlock_title acpBlock_titleDark acpBlock_titleSmall">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				{$purchases}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $transactions ):
$return .= <<<IPSCONTENT

			<div class="i-margin-bottom_1">
				<h2 class="acpBlock_title acpBlock_titleDark acpBlock_titleSmall">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_transactions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				{$transactions}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}}