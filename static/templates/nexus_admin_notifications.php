<?php
namespace IPS\Theme;
class class_nexus_admin_notifications extends \IPS\Theme\Template
{	function baCancellationError( $agreements ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notification_nexus_bacancelerror_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<ul>
		
IPSCONTENT;

foreach ( $agreements as $agreement ):
$return .= <<<IPSCONTENT

			<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $agreement, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function maxmind(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notification_nexus_maxmind_body', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>
<ul class='ipsList ipsList--inline i-margin-top_1'>
	<li>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=enhancements&do=edit&id=nexus_MaxMind", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'help_me_fix_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</li>
</ul>
IPSCONTENT;

		return $return;
}

	function paymentMethodError( $method ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$sprintf = array($method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notification_nexus_config_error_paymethod_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class="i-margin-top_3">
	<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=paymentsettings&tab=gateways&do=form&id={$method->_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'help_me_fix_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function payoutSettingsError( $gatewayName ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$sprintf = array($gatewayName); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notification_nexus_config_error_payoutmethod_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class="i-margin-top_3">
	<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=payouts&do=settings", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'help_me_fix_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function transactions( $transactions ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsSpanGrid" data-controller="nexus.admin.notifications.pendingTransactions">
	
IPSCONTENT;

foreach ( $transactions as $transaction ):
$return .= <<<IPSCONTENT

		<div class="ipsSpanGrid__3 ipsBox i-margin-bottom_1">
			<h3 class="ipsBox__header">
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->invoice->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <span class="i-font-size_-1">#
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
			</h3>
			<div class="i-padding_2">
				<div class='ipsSpanGrid i-padding_2'>
					<div class="ipsSpanGrid__3 i-text-align_center">
						<span class='i-font-size_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->amount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</div>
					<div class="ipsSpanGrid__7">
						
IPSCONTENT;

if ( $transaction->method ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \in_array( $transaction->status, array( \IPS\nexus\Transaction::STATUS_PENDING, \IPS\nexus\Transaction::STATUS_WAITING ) ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$sprintf = array($transaction->method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_method_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$sprintf = array($transaction->method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_method', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
							
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment_received', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<br>
						<span class='i-font-size_-1'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->date, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $transaction->gw_id ):
$return .= <<<IPSCONTENT

								<br>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment_reference', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

if ( $transaction->method and $url = $transaction->method->gatewayUrl( $transaction ) ):
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noreferrer">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<br>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</span>
					</div>
				</div>
				<hr class="ipsHr ipsHr_small">
				<div class='ipsSpanGrid i-padding_2'>
					<div class="ipsSpanGrid__3 i-text-align_center">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsUserPhoto ipsUserPhoto--tiny'></a>
					</div>
					<div class="ipsSpanGrid__7">
						<a class="i-link-color_inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a><br>
						<span class='i-font-size_-1'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
							
IPSCONTENT;

$sprintf = array($transaction->member->joined->localeDate()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_customer_since', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
<br>
							
IPSCONTENT;

$sprintf = array($transaction->member->totalSpent()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_spent', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

						</span>
					</div>
				</div>
				
IPSCONTENT;

if ( $transaction->fraud or $transaction->fraud_blocked ):
$return .= <<<IPSCONTENT

					<hr class="ipsHr ipsHr_small">
					<div class="i-text-align_center">
						
IPSCONTENT;

if ( $transaction->fraud ):
$return .= <<<IPSCONTENT

							<div class="
IPSCONTENT;

if ( ( $transaction->fraud->riskScore !== NULL and $transaction->fraud->riskScore > 80 ) or ( $transaction->fraud->riskScore === NULL and $transaction->fraud->score > 8 ) ):
$return .= <<<IPSCONTENT
i-color_warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-margin-block_1">
								
IPSCONTENT;

if ( $transaction->fraud->riskScore !== NULL ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->riskScore, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( $transaction->fraud->score * 10 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'possibility_of_fraud', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $transaction->fraud_blocked ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud_blocked->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $buttons = array_filter( $transaction->buttons('n'), function( $k ) { return \in_array( $k, array( 'approve', 'review', 'void', 'refund' ) ); }, ARRAY_FILTER_USE_KEY ) ):
$return .= <<<IPSCONTENT

					<hr class="ipsHr ipsHr_small">
					<div class="i-text-align_center i-margin-top_1">
						<ul class='ipsButton_split' data-role="buttons">
							
IPSCONTENT;

foreach ( $buttons as $k => $button ):
$return .= <<<IPSCONTENT

								<li><a 
IPSCONTENT;

if ( !isset( $button['data']['ipsDialog'] ) ):
$return .= <<<IPSCONTENT
data-action="quickAction"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $button['data'] as $_k => $v ):
$return .= <<<IPSCONTENT
data-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 title="
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip class="ipsButton ipsButton--small 
IPSCONTENT;

if ( $k === 'approve' ):
$return .= <<<IPSCONTENT
ipsButton--positive
IPSCONTENT;

elseif ( $k === 'review' ):
$return .= <<<IPSCONTENT
ipsButton--intermediate
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsButton--negative
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['link']->setQueryString( 'queueStatus', $transaction->status ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i></a></li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}}