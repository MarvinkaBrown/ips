<?php
namespace IPS\Theme;
class class_nexus_admin_transactions extends \IPS\Theme\Template
{	function dspd( $transaction, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $extraData = $transaction->method->disputeData( $transaction, $log ) ):
$return .= <<<IPSCONTENT

	{$extraData}

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<li class='cTransaction_intermediate'>
		<div class='cTransaction_icon'>
			<i class='fa-solid fa-rotate-left'></i>
		</div>
		<div class='cTransaction_info'>
			<h3 class="ipsTitle ipsTitle--h3">
				<strong>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_dspd_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</strong>
			</h3>
			<br>
			
			
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

					<br>
					<span class='i-font-size_1 i-color_soft'>
						
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function fail( $transaction, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='cTransaction_negative'>
	<div class='cTransaction_icon'>
		<i class='fa-solid fa-xmark'></i>
	</div>
	<div class='cTransaction_info'>
		<h3 class="ipsTitle ipsTitle--h3">
			<strong>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_fail_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</strong>
		</h3>
		<br>
		
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

				<br>
				<span class='i-font-size_1 i-color_soft'>
					
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $log['note'] ) and $log['note'] ):
$return .= <<<IPSCONTENT

			<p class='cTransaction_note'>
				
IPSCONTENT;

$val = "trans_extra_{$log['note']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $log['noteRaw'] ) and $log['noteRaw'] ):
$return .= <<<IPSCONTENT

			<p class='cTransaction_note'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['noteRaw'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function hold( $transaction, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li>
	<div class='cTransaction_icon'>
		<i class='fa-solid fa-triangle-exclamation'></i>
	</div>
	<div class='cTransaction_info'>
		<span class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_hold_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		<br>
		
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

				<br>
				<span class='i-font-size_1 i-color_soft'>
					
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $log['note'] ) and $log['note'] ):
$return .= <<<IPSCONTENT

			<p class='cTransaction_note'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['note'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function link( $transaction ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($transaction->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function okay( $transaction, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='cTransaction_positive'>
	<div class='cTransaction_icon'>
		<i class='fa-solid fa-check'></i>
	</div>
	<div class='cTransaction_info'>
		<h3 class="ipsTitle ipsTitle--h3">
			<strong>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_okay_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</strong>
		</h3>
		
		
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

				<br>
				<span class='i-font-size_1 i-color_soft'>
					
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<br>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_okay_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function paypalStatus( $transaction ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $transaction->extra['verified'] ) ):
$return .= <<<IPSCONTENT

	<p>
		
IPSCONTENT;

$sprintf = array($transaction->extra['verified']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'paypal_payer_status', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( isset( $transaction->extra['processor_response'] ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$responseCode = $transaction->extra['processor_response']['response_code'];
$return .= <<<IPSCONTENT

<p>
    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'processor_response_avs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->extra['processor_response']['avs_code'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <span class="i-color_soft">(
IPSCONTENT;

$val = "processor_response_avs__{$transaction->extra['processor_response']['avs_code']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</span>
    <br>
    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'processor_response_cvv', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->extra['processor_response']['cvv_code'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <span class="i-color_soft">(
IPSCONTENT;

$val = "processor_response_cvv__{$transaction->extra['processor_response']['cvv_code']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)</span>
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
    <span class="i-color_soft">
IPSCONTENT;

$val = "processor_response_code__{$responseCode}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function prfd( $transaction, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='cTransaction_intermediate'>
	<div class='cTransaction_icon'>
		
IPSCONTENT;

if ( $log['to'] === 'credit' ):
$return .= <<<IPSCONTENT

			<i class='fa-solid fa-reply'></i>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i class='fa-solid fa-reply-all'></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='cTransaction_info'>
		<h3 class="ipsTitle ipsTitle--h3">
			<strong>
				
IPSCONTENT;

if ( $log['to'] === 'credit' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_credited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_prfd_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</strong>
		</h3>
		<br>
		
		
IPSCONTENT;

if ( isset( $log['amount'] ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $transaction->amount->amount->compare( new \IPS\Math\Number( $log['amount'] ) ) !== 0 ):
$return .= <<<IPSCONTENT

				<p class='i-font-size_2'>
					<span class='cNexusPrice'>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $log['amount'], $transaction->currency );
$return .= <<<IPSCONTENT
</span>
				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( isset( $log['ref'] ) and $log['ref'] ):
$return .= <<<IPSCONTENT

			<p>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['ref'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

				<br>
				<span class='i-font-size_1 i-color_soft'>
					
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function revw( $transaction, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='cTransaction_intermediate'>
	<div class='cTransaction_icon'>
		<i class='fa-solid fa-flag'></i>
	</div>
	<div class='cTransaction_info'>
		<span class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_revw_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		<br>
		
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

				<br>
				<span class='i-font-size_1 i-color_soft'>
					
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function rfnd( $transaction, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='cTransaction_negative'>
	<div class='cTransaction_icon'>
		<i class='fa-solid fa-reply-all'></i>
	</div>
	<div class='cTransaction_info'>
		<h3 class="ipsTitle ipsTitle--h3">
			<strong>
				
IPSCONTENT;

if ( $log['to'] === 'credit' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_credited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_rfnd_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</strong>
		</h3>
		<br>
		
		
IPSCONTENT;

if ( isset( $log['ref'] ) and $log['ref'] ):
$return .= <<<IPSCONTENT

			<p class='i-font-size_1'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['ref'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

				<br>
				<span class='i-font-size_1 i-color_soft'>
					
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function status( $status ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="ipsBadge ipsBadge--style
IPSCONTENT;

if ( $status == \IPS\nexus\Transaction::STATUS_PAID ):
$return .= <<<IPSCONTENT
4
IPSCONTENT;

elseif ( $status == \IPS\nexus\Transaction::STATUS_HELD ):
$return .= <<<IPSCONTENT
1
IPSCONTENT;

elseif ( $status == \IPS\nexus\Transaction::STATUS_REVIEW or $status == \IPS\nexus\Transaction::STATUS_DISPUTED ):
$return .= <<<IPSCONTENT
7
IPSCONTENT;

elseif ( $status == \IPS\nexus\Transaction::STATUS_REFUSED ):
$return .= <<<IPSCONTENT
5
IPSCONTENT;

elseif ( $status == \IPS\nexus\Transaction::STATUS_REFUNDED or $status == \IPS\nexus\Transaction::STATUS_PART_REFUNDED ):
$return .= <<<IPSCONTENT
2
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
6
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

$val = "tstatus_{$status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</span>
IPSCONTENT;

		return $return;
}

	function stripeData( $response=NULL, $error=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $response['outcome'] ) ):
$return .= <<<IPSCONTENT

	<div class="i-margin-top_1 i-margin-bottom_1">
		<p>
			<i class="fa-solid fa-shield"></i> 
IPSCONTENT;

$sprintf = array($response['outcome']['risk_level']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_risk_level', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		</p>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $response['source']['card'] ) ):
$return .= <<<IPSCONTENT

	<div class="i-margin-bottom_1">
		<p class='ipsTruncate ipsTruncate_line'>
			
IPSCONTENT;

if ( isset( $response['source']['card']['tokenization_method'] ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $response['source']['card']['tokenization_method'] == 'apple_pay' ):
$return .= <<<IPSCONTENT

					<i class="fa-brands fa-apple"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_tokenization_apple_pay', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $response['source']['card']['tokenization_method'] == 'android_pay' ):
$return .= <<<IPSCONTENT

					<i class="fa-brands fa-google"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_tokenization_android_pay', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_tokenization_other', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $response['source']['card']['tokenization_method'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				&nbsp;
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $response['source']['card']['brand'] == 'Visa' or $response['source']['card']['brand'] == 'visa' ):
$return .= <<<IPSCONTENT

				<i class="fa-brands fa-cc-visa" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_type_visa', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i>
			
IPSCONTENT;

elseif ( $response['source']['card']['brand'] == 'MasterCard' or $response['source']['card']['brand'] == 'mastercard' ):
$return .= <<<IPSCONTENT

				<i class="fa-brands fa-cc-mastercard" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_type_mastercard', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i>
			
IPSCONTENT;

elseif ( $response['source']['card']['brand'] == 'Discover' or $response['source']['card']['brand'] == 'discover' ):
$return .= <<<IPSCONTENT

				<i class="fa-brands fa-cc-discover" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_type_discover', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i>
			
IPSCONTENT;

elseif ( $response['source']['card']['brand'] == 'American Express' or $response['source']['card']['brand'] == 'amex' ):
$return .= <<<IPSCONTENT

				<i class="fa-brands fa-cc-amex" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_type_american_express', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i>
			
IPSCONTENT;

elseif ( $response['source']['card']['brand'] == 'Diners Club' or $response['source']['card']['brand'] == 'diners' ):
$return .= <<<IPSCONTENT

				<i class="fa-brands fa-cc-diners-club" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_type_diners_club', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i>
			
IPSCONTENT;

elseif ( $response['source']['card']['brand'] == 'JCB' or $response['source']['card']['brand'] == 'jcb' ):
$return .= <<<IPSCONTENT

				<i class="fa-brands fa-cc-jcb" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_type_jcb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class="fa-solid fa-credit-card"></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			&middot;&middot;&middot;&middot;
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $response['source']['card']['last4'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			&nbsp;
			<span class="i-font-size_-1">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_expires', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_pad( $response['source']['card']['exp_month'], 2, '0', STR_PAD_LEFT ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $response['source']['card']['exp_year'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				&nbsp;
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'card_origin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "country-{$response['source']['card']['country']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</span>
		</p>
		
IPSCONTENT;

foreach ( array( 'cvc_check', 'address_line1_check', 'address_postal_code_check' ) as $k ):
$return .= <<<IPSCONTENT

			<p class='ipsTruncate ipsTruncate_line'>
				
IPSCONTENT;

if ( $response['source']['card'][ $k ] == 'pass' ):
$return .= <<<IPSCONTENT

					<span class="i-color_positive"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$val = "stripe_{$k}_pass"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

elseif ( $response['source']['card'][ $k ] == 'fail' ):
$return .= <<<IPSCONTENT

					<span class="i-color_warning"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$val = "stripe_{$k}_fail"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<span class="i-color_issue"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

if ( $response['source']['card'][ $k ] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "stripe_{$k}_{$response['source']['card'][ $k ]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "stripe_{$k}_unchecked"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $response['source']['three_d_secure'] ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $response['source']['three_d_secure']['authenticated'] ) and $response['source']['three_d_secure']['authenticated'] ):
$return .= <<<IPSCONTENT

				<span class="i-color_positive"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_3ds_pass', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="i-color_warning"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_3ds_fail', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( isset( $response['source']['card'] ) and isset( $response['source']['card']['three_d_secure'] ) and \is_array( $response['source']['card']['three_d_secure'] ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $response['source']['card']['three_d_secure']['result'] ) and $response['source']['card']['three_d_secure']['result'] == 'authenticated' ):
$return .= <<<IPSCONTENT

				<span class="i-color_positive"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$sprintf = array($response['source']['card']['three_d_secure']['version']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_3ds_pass_version', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="i-color_warning"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$sprintf = array($response['source']['card']['three_d_secure']['version']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_3ds_fail_version', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !isset( $response['card']['three_d_secure'] ) or $response['card']['three_d_secure'] == 'not_supported' ):
$return .= <<<IPSCONTENT

				<span class="i-color_soft"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_3ds_not_supported', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_3ds_not_checked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

	<p class='ipsMessage ipsMessage--error'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_details_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function stripeDispute( $transaction, $log, $response=NULL, $error=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='cTransaction_intermediate'>
	<div class='cTransaction_icon'>
		
IPSCONTENT;

if ( isset( $response['status'] ) and \in_array( $response['status'], array( 'warning_needs_response', 'warning_under_review', 'warning_closed', 'charge_refunded' ) ) ):
$return .= <<<IPSCONTENT

			<i class='fa-solid fa-triangle-exclamation'></i>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i class='fa-solid fa-rotate-left'></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='cTransaction_info'>
		<h3 class="ipsTitle ipsTitle--h3">
			
IPSCONTENT;

if ( isset( $response['status'] ) and \in_array( $response['status'], array( 'warning_needs_response', 'warning_under_review', 'warning_closed', 'charge_refunded' ) ) ):
$return .= <<<IPSCONTENT

				<strong>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_dspd_inquiry_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</strong>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<strong>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_dspd_set', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</strong>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h3>
		<br>
		
		
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

				<br>
				<span class='i-font-size_1 i-color_soft'>
					
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( isset( $response['reason'] ) ):
$return .= <<<IPSCONTENT

		<p>
			
IPSCONTENT;

$val = "stripe_dispute_reason_{$response['reason']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( isset( $response['status'] ) and \in_array( $response['status'], array( 'needs_response', 'under_review', 'warning_needs_response', 'warning_under_review' ) ) ):
$return .= <<<IPSCONTENT

			<p class='i-font-weight_bold i-color_warning i-margin-top_1'>
				
IPSCONTENT;

$val = "stripe_dispute_status_{$response['status']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

			<p class='ipsMessage ipsMessage--error'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stripe_dispute_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $response['status'] ) and $response['status'] == 'won' ):
$return .= <<<IPSCONTENT

				</div>
			</li>
			<li class='cTransaction_positive'>
				<div class='cTransaction_icon'>
					<i class='fa-solid fa-repeat'></i>
				</div>
				<div class='cTransaction_info'>
					<h3 class="ipsTitle ipsTitle--h3">
						<strong>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_dispute_won', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</strong>
					</h3>
					<p>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_dispute_won_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
					
IPSCONTENT;

if ( $transaction->status === $transaction::STATUS_DISPUTED ):
$return .= <<<IPSCONTENT

						<div class="i-margin-top_1">
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->acpUrl()->setQueryString( array( 'do' => 'approve', 'r' => 'v' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--positive ipsButton--tiny" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_approve_from_dispute', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->acpUrl()->setQueryString( array( 'do' => 'refund', 'r' => 'v' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--tiny" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$sprintf = array($transaction->amount); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_refund_credit_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_refund_credit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( isset( $response['status'] ) and $response['status'] == 'warning_closed' ):
$return .= <<<IPSCONTENT

				</div>
			</li>
			<li class='cTransaction_positive'>
				<div class='cTransaction_icon'>
					<i class='fa-solid fa-check'></i>
				</div>
				<div class='cTransaction_info'>
					<h3 class="ipsTitle ipsTitle--h3">
						<strong>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_dispute_warning_closed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</strong>
					</h3>
					<p>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_dispute_warning_closed_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
		
IPSCONTENT;

elseif ( isset( $response['status'] ) and $response['status'] == 'lost' ):
$return .= <<<IPSCONTENT

				</div>
			</li>
			<li class='cTransaction_negative'>
				<div class='cTransaction_icon'>
					<i class='fa-solid fa-xmark'></i>
				</div>
				<div class='cTransaction_info'>
					<h3 class="ipsTitle ipsTitle--h3">
						<strong>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_dispute_lost', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</strong>
					</h3>
					<p>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_dispute_lost_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function undo_credit( $transaction, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='cTransaction_intermediate'>
	<div class='cTransaction_icon'>
		<i class='fa-solid fa-forward-fast'></i>
	</div>
	<div class='cTransaction_info'>
		<h3 class="ipsTitle ipsTitle--h3">
			<strong>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_credited_undo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</strong>
		</h3>
		<br>
		
		
IPSCONTENT;

if ( isset( $log['amount'] ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $transaction->amount->amount->compare( new \IPS\Math\Number( $log['amount'] ) ) !== 0 ):
$return .= <<<IPSCONTENT

				<p class='i-font-size_2'>
					<span class='cNexusPrice'>
IPSCONTENT;

$return .= new \IPS\nexus\Money( $log['amount'], $transaction->currency );
$return .= <<<IPSCONTENT
</span>
				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

				<br>
				<span class='i-font-size_1 i-color_soft'>
					
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function view( $transaction ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsColumns'>
	<div class='ipsColumns__secondary i-basis_280'>
		<div class="ipsBox i-text-align_center i-padding_3 i-position_sticky-top" style="--_i-sticky-margin: 15px">
			<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_total', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<span class="i-font-size_6">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->amount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

if ( $transaction->status === $transaction::STATUS_PART_REFUNDED ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $transaction->partial_refund->amount->isGreaterThanZero() ):
$return .= <<<IPSCONTENT

					<div class="i-margin-top_1">
						<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'amount_refunded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						<span class="i-font-size_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->partial_refund, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $transaction->credit->amount->isGreaterThanZero() ):
$return .= <<<IPSCONTENT

					<div class="i-margin-top_1">
						<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'amount_credited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						<span class="i-font-size_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->credit, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<hr class='ipsHr'>

			<ul class='ipsButtons ipsButtons--view-transaction'>
				
IPSCONTENT;

foreach ( $transaction->buttons('v') as $k => $button ):
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--wide 
IPSCONTENT;

if ( $k === 'approve' ):
$return .= <<<IPSCONTENT
ipsButton--positive
IPSCONTENT;

elseif ( $k === 'void' ):
$return .= <<<IPSCONTENT
 ipsButton--negative
IPSCONTENT;

elseif ( $k === 'delete' ):
$return .= <<<IPSCONTENT
ipsButton--text
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 ipsButton--inherit
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( isset( $button['data'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $button['data'] as $k => $v ):
$return .= <<<IPSCONTENT
data-
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

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $button['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$val = "{$button['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>

	</div>

	<div class='ipsColumns__primary'>

		<div class="ipsMessage i-margin-bottom_1 
IPSCONTENT;

if ( $transaction->status == \IPS\nexus\Transaction::STATUS_PAID ):
$return .= <<<IPSCONTENT
ipsMessage--success
IPSCONTENT;

elseif ( $transaction->status == \IPS\nexus\Transaction::STATUS_HELD ):
$return .= <<<IPSCONTENT
ipsMessage--warning
IPSCONTENT;

elseif ( $transaction->status == \IPS\nexus\Transaction::STATUS_REVIEW ):
$return .= <<<IPSCONTENT
ipsMessage--warning
IPSCONTENT;

elseif ( $transaction->status == \IPS\nexus\Transaction::STATUS_REFUSED or $transaction->status == \IPS\nexus\Transaction::STATUS_DISPUTED ):
$return .= <<<IPSCONTENT
ipsMessage--error
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsMessage--info
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

if ( $transaction->auth and \in_array( $transaction->status, array( \IPS\nexus\Transaction::STATUS_HELD, \IPS\nexus\Transaction::STATUS_REVIEW ) ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$val = "tstatus_{$transaction->status}_nc_desc"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$val = "tstatus_{$transaction->status}_desc"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>		

		
IPSCONTENT;

if ( isset( $transaction->extra['admin'] ) ):
$return .= <<<IPSCONTENT

			<div class='ipsMessage ipsMessage--info i-margin-bottom_1'>
				
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $transaction->extra['admin'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_admin_manual', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $transaction->fraud ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $transaction->fraud->id ):
$return .= <<<IPSCONTENT

                <div class='ipsBox i-margin-bottom_1'>
                    <h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
                    <div class='i-padding_3'>
                        
IPSCONTENT;

if ( $errorMessage = $transaction->fraud->error() ):
$return .= <<<IPSCONTENT

                        <div class='ipsMessage ipsMessage--error'>
                            <h3 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_from_maxmind', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                            <p>
                                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                            </p>
                        </div>
                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

if ( $warning = $transaction->fraud->warning() ):
$return .= <<<IPSCONTENT

                        <div class='ipsMessage ipsMessage--error'>
                            <h3 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_from_maxmind', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                            <p>
                                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                            </p>
                        </div>
                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

if ( !$errorMessage ):
$return .= <<<IPSCONTENT

                        <div class="i-margin-bottom_3 i-flex">
                            <h3 class='cTransactionFraud_riskScore 
IPSCONTENT;

if ( $transaction->fraud->risk_score > 80 ):
$return .= <<<IPSCONTENT
i-color_warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->risk_score, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%</h3>
                            <p class='cTransactionFraud_riskInfo i-flex_11'>
                                <span class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'possibility_of_fraud', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><br>
                                <span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'checked_by_maxmind', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                            </p>
                        </div>

                        <div class='ipsSpanGrid'>
                            <div class='ipsSpanGrid__4 i-background_1'>
                                <h3 class='acpBlock_title acpBlock_titleSmall'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_geo_ip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                <div class='i-padding_3'>
                                    
IPSCONTENT;

if ( $transaction->fraud->ip_address['location']['latitude'] AND $transaction->fraud->ip_address['location']['longitude'] ):
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( \IPS\GeoLocation::enabled() ):
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

$map = \IPS\GeoLocation::getByLatLong( $transaction->fraud->ip_address['location']['latitude'], $transaction->fraud->ip_address['location']['longitude'] )->map()->render( 600, 200 );
$return .= <<<IPSCONTENT

                                            <div class="i-padding_2">{$map}</div>
                                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                            <p>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['location']['latitude'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
,
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['location']['longitude'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    <p class="i-margin-top_1">
IPSCONTENT;

$sprintf = array($transaction->fraud->billing_address['distance_to_ip_location']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_distance', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
                                    <p class="i-font-size_-1 i-color_soft">
IPSCONTENT;

if ( $transaction->fraud->ip_address['location']['accuracy_radius'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($transaction->fraud->ip_address['location']['accuracy_radius']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_accuracyRadius', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_distance_estimated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>

                                    <ul class='i-margin-bottom_1 cTransactionFraud_geoip i-margin-top_1'>
                                        
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['city'] ) ):
$return .= <<<IPSCONTENT

                                        <li>
                                                    <span 
IPSCONTENT;

if ( $transaction->fraud->ip_address['city']['confidence'] ):
$return .= <<<IPSCONTENT
class='cTransactionFraud_geoInfo' title='
IPSCONTENT;

$sprintf = array($transaction->fraud->ip_address['city']['confidence']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_accuracy', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                                        
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['city']['names']['en'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                                    </span>
                                            
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['location']['metro_code'] ) ):
$return .= <<<IPSCONTENT
<span class='i-color_soft'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['location']['metro_code'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( $transaction->fraud->ip_postalCode ):
$return .= <<<IPSCONTENT

                                            <li>
                                                <span 
IPSCONTENT;

if ( $transaction->fraud->ip_address['postal']['confidence'] ):
$return .= <<<IPSCONTENT
class='cTransactionFraud_geoInfo' title='
IPSCONTENT;

$sprintf = array($transaction->fraud->ip_address['postal']['confidence']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_accuracy', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                                    
IPSCONTENT;

if ( $transaction->fraud->ip_address['postal']['code'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['postal']['code'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                </span>
                                            </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['country'] ) ):
$return .= <<<IPSCONTENT

                                            <li>
                                                <span 
IPSCONTENT;

if ( $transaction->fraud->ip_address['country']['confidence'] ):
$return .= <<<IPSCONTENT
class='cTransactionFraud_geoInfo' title='
IPSCONTENT;

$sprintf = array($transaction->fraud->ip_countryConf); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_accuracy', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                                    
IPSCONTENT;

$val = "country-{$transaction->fraud->ip_address['country']['iso_code']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                </span>
                                            </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    </ul>

                                    <ul class='cTransactionFraud'>
                                        
IPSCONTENT;

if ( isset( $transaction->fraud->billing_address['is_in_ip_country'] ) ):
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( $transaction->fraud->billing_address['is_in_ip_country'] ):
$return .= <<<IPSCONTENT

                                            <li class='cTransactionFraud_positive'>
                                                <i class="fa-solid fa-check"></i>
                                                <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_countryMatch_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                            </li>
                                            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                            <li class='cTransactionFraud_negative'>
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_countryMatch_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                            </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['country']['is_high_risk'] ) and $transaction->fraud->ip_address['country']['is_high_risk'] ):
$return .= <<<IPSCONTENT

                                            <li class='cTransactionFraud_negative'>
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_highRiskCountry_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                            </li>
                                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                            <li class='cTransactionFraud_positive'>
                                                <i class="fa-solid fa-check"></i>
                                                <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_highRiskCountry_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                            </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    </ul>
                                </div>
                            </div>
                            <div class='ipsSpanGrid__4 i-background_1'>
                                <h3 class='acpBlock_title acpBlock_titleSmall'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                <div class='i-padding_3'>
                                    <div class="ipsProgress">
                                        <div class="ipsProgress__progress 
IPSCONTENT;

if ( $transaction->fraud->ip_address['risk'] > 75 ):
$return .= <<<IPSCONTENT
ipsProgress__progress--warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" style="width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['risk'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['risk'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%</div>
                                    </div>
                                    <p class='i-font-size_1 i-margin-top_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_proxyScore', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                    <p class='i-font-size_-1 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_proxyScore_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

                                    <ul class='cTransactionFraud i-margin-top_1'>
                                        
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['traits']['is_anonymous_proxy'] ) and $transaction->fraud->ip_address['traits']['is_anonymous_proxy'] ):
$return .= <<<IPSCONTENT

                                            <li class='cTransactionFraud_negative'>
                                                <i class="fa-solid fa-circle-exclamation"></i>
                                                <p>
                                                    <span class='cTransactionFraud_geoInfo' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_anonymousProxy_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
                                                    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_anonymousProxy_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                    </span>
                                                </p>
                                            </li>
                                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                            <li class='cTransactionFraud_success'>
                                                <i class="fa-solid fa-check-circle"></i>
                                                <p>
                                                    <span class='cTransactionFraud_geoInfo' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_anonymousProxy_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
                                                    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_anonymousProxy_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                    </span>
                                                </p>
                                            </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['traits']['is_tor_exit_node'] ) and $transaction->fraud->ip_address['traits']['is_tor_exit_node'] ):
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_negative'>
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            <p>
                                                <span class='cTransactionFraud_geoInfo' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_torExit_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
                                                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_torExit_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                </span>
                                            </p>
                                        </li>
                                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_success'>
                                            <i class="fa-solid fa-check-circle"></i>
                                            <p>
                                                <span class='cTransactionFraud_geoInfo' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_torExit_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
                                                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_torExit_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                </span>
                                            </p>
                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


                                        
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['traits']['isp'] ) ):
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_neutral'>
                                            <i class="fa-solid fa-circle-info"></i>
                                            <p>
                                                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_isp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['traits']['isp'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

if ( $transaction->fraud->ip_address['traits']['organization'] and $transaction->fraud->ip_address['traits']['organization'] != $transaction->fraud->ip_address['traits']['isp'] ):
$return .= <<<IPSCONTENT

                                                <br><span class="i-color_soft i-font-size_-1">(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['traits']['organization'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
                                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            </p>
                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['traits']['user_type'] ) ):
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_neutral'>
                                            <i class="fa-solid fa-circle-info"></i>
                                            <p>
                                                
IPSCONTENT;

$val = "maxmind_ip_userType_{$transaction->fraud->ip_address['traits']['user_type']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                            </p>
                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['traits']['organization'] ) ):
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_neutral'>
                                            <i class="fa-solid fa-circle-info"></i>
                                            <p>
                                                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_timeZone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['location']['time_zone'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                            </p>
                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( $transaction->fraud->ip_domain ):
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_neutral'>
                                            <i class="fa-solid fa-circle-info"></i>
                                            <p>
                                                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_domain', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_domain, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                            </p>
                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( isset( $transaction->fraud->ip_address['traits']['autonomous_system_number'] ) ):
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_neutral'>
                                            <i class="fa-solid fa-circle-info"></i>
                                            <p>
                                                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_asnum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_address['traits']['autonomous_system_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                            </p>
                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    </ul>
                                </div>
                            </div>
                            <div class='ipsSpanGrid__4 i-background_1'>
                                
IPSCONTENT;

if ( $transaction->fraud->credit_card ):
$return .= <<<IPSCONTENT

                                <h3 class='acpBlock_title acpBlock_titleSmall'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_card_issuer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                <div class='i-padding_3'>
                                    
IPSCONTENT;

if ( isset( $transaction->fraud->credit_card['issuer']['name'] ) ):
$return .= <<<IPSCONTENT

                                    <p class='i-font-size_2'>
                                        <strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->credit_card['issuer']['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
                                    </p>
                                    
IPSCONTENT;

if ( isset( $transaction->fraud->credit_card['country'] ) || isset( $transaction->fraud->credit_card['issuer']['phone_number'] ) ):
$return .= <<<IPSCONTENT

                                    <ul class='cTransactionFraud i-margin-top_1'>
                                        
IPSCONTENT;

if ( isset( $transaction->fraud->credit_card['country'] ) ):
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_neutral'>
                                            <i class="fa-solid fa-earth-americas"></i>
                                            <p>
                                                
IPSCONTENT;

$val = "country-{$transaction->fraud->credit_card['country']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                            </p>
                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( isset( $transaction->fraud->credit_card['issuer']['phone_number'] ) ):
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_neutral'>
                                            <i class="fa-solid fa-phone"></i>
                                            <p>
                                                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->credit_card['issuer']['phone_number'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                            </p>
                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    </ul>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                </div>
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


                                <h3 class='acpBlock_title acpBlock_titleSmall'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_customer_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                <div class='i-padding_3'>
                                    <ul class='cTransactionFraud'>
                                        
IPSCONTENT;

foreach ( array( 'disposable', 'free', 'high_risk' ) as $k ):
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( !$transaction->fraud->email['is_' . $k] ):
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_positive'>
                                            <i class="fa-solid fa-check-circle"></i>
                                            <p>
IPSCONTENT;

$val = "maxmind_{$k}_n"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                        </li>
                                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                        <li class='cTransactionFraud_negative'>
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            <p>
IPSCONTENT;

$val = "maxmind_{$k}_y"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                        </li>
                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                                    </ul>
                                </div>
                            </div>
                        </div>
                        <hr class='ipsHr'>
                        <p class='i-font-size_1 i-color_soft'>
                            
IPSCONTENT;

$sprintf = array($transaction->fraud->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_footer_insights', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;

$sprintf = array($transaction->fraud->queries_remaining); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_queries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
)
                        </p>
                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    </div>
                </div>
            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                <div class='ipsBox i-margin-bottom_1'>
                    <h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
                    <div class='i-padding_3'>
                        
IPSCONTENT;

if ( $errorMessage = $transaction->fraud->error() ):
$return .= <<<IPSCONTENT

                        <div class='ipsMessage ipsMessage--error'>
                            <h3 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'error_from_maxmind', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                            <p>
                                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                            </p>
                        </div>
                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

if ( $warning = $transaction->fraud->warning() ):
$return .= <<<IPSCONTENT

                            <div class='ipsMessage ipsMessage--error'>
                                <h3 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_from_maxmind', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                <p>
                                    
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                </p>
                            </div>
                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

if ( !$errorMessage ):
$return .= <<<IPSCONTENT

                            <div class="i-margin-bottom_3 i-flex">
                                
IPSCONTENT;

if ( $transaction->fraud->riskScore !== NULL ):
$return .= <<<IPSCONTENT

                                    <h3 class='cTransactionFraud_riskScore 
IPSCONTENT;

if ( $transaction->fraud->riskScore > 80 ):
$return .= <<<IPSCONTENT
i-color_warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->riskScore, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%</h3>
                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                    <h3 class='cTransactionFraud_riskScore 
IPSCONTENT;

if ( $transaction->fraud->score > 8 ):
$return .= <<<IPSCONTENT
i-color_warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( round( $transaction->fraud->score * 10 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%</h3>
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                <p class='cTransactionFraud_riskInfo i-flex_11'>
                                    <span class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'possibility_of_fraud', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><br>
                                    <span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'checked_by_maxmind', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                                </p>
                            </div>

                            <div class='ipsSpanGrid'>
                                <div class='ipsSpanGrid__4 i-background_1'>
                                    <h3 class='acpBlock_title acpBlock_titleSmall'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_geo_ip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                    <div class='i-padding_3'>
                                        
IPSCONTENT;

if ( $transaction->fraud->ip_latitude AND $transaction->fraud->ip_longitude ):
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( \IPS\GeoLocation::enabled() ):
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

$map = \IPS\GeoLocation::getByLatLong( $transaction->fraud->ip_latitude, $transaction->fraud->ip_longitude )->map()->render( 600, 200 );
$return .= <<<IPSCONTENT

                                                <div class="i-padding_2">{$map}</div>
                                            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                <p>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_latitude, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
,
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_longitude, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        <p class="i-margin-top_1">
IPSCONTENT;

$sprintf = array($transaction->fraud->distance); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_distance', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
                                        <p class="i-font-size_-1 i-color_soft">
IPSCONTENT;

if ( $transaction->fraud->ip_accuracyRadius ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($transaction->fraud->ip_accuracyRadius); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_accuracyRadius', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_distance_estimated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>

                                        <ul class='i-margin-bottom_1 cTransactionFraud_geoip i-margin-top_1'>
                                            <li>
                                                <span 
IPSCONTENT;

if ( $transaction->fraud->ip_cityConf ):
$return .= <<<IPSCONTENT
class='cTransactionFraud_geoInfo' title='
IPSCONTENT;

$sprintf = array($transaction->fraud->ip_cityConf); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_accuracy', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                                    
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_city, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                                </span>
                                                
IPSCONTENT;

if ( isset( $transaction->fraud->ip_metroCode ) ):
$return .= <<<IPSCONTENT
<span class='i-color_soft'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_metroCode, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            </li>
                                            
IPSCONTENT;

if ( $transaction->fraud->ip_regionName || $transaction->fraud->ip_region || $transaction->fraud->ip_postalCode ):
$return .= <<<IPSCONTENT

                                                <li>
                                                    
IPSCONTENT;

if ( $transaction->fraud->ip_regionName || $transaction->fraud->ip_region ):
$return .= <<<IPSCONTENT

                                                        <span 
IPSCONTENT;

if ( $transaction->fraud->ip_regionConf ):
$return .= <<<IPSCONTENT
class='cTransactionFraud_geoInfo' title='
IPSCONTENT;

$sprintf = array($transaction->fraud->ip_regionConf); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_accuracy', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                                            
IPSCONTENT;

if ( $transaction->fraud->ip_regionName ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_regionName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $transaction->fraud->ip_region ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_region, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                        </span>
                                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                    
IPSCONTENT;

if ( $transaction->fraud->ip_postalCode ):
$return .= <<<IPSCONTENT

                                                        <span 
IPSCONTENT;

if ( $transaction->fraud->ip_postalConf ):
$return .= <<<IPSCONTENT
class='cTransactionFraud_geoInfo' title='
IPSCONTENT;

$sprintf = array($transaction->fraud->ip_postalConf); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_accuracy', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                                            
IPSCONTENT;

if ( $transaction->fraud->ip_postalCode ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_postalCode, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                        </span>
                                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( $transaction->fraud->ip_countryName || $transaction->fraud->countryCode ):
$return .= <<<IPSCONTENT

                                                <li>
                                                    <span 
IPSCONTENT;

if ( $transaction->fraud->ip_countryConf ):
$return .= <<<IPSCONTENT
class='cTransactionFraud_geoInfo' title='
IPSCONTENT;

$sprintf = array($transaction->fraud->ip_countryConf); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_accuracy', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                                                        
IPSCONTENT;

if ( $transaction->fraud->ip_countryName ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_countryName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $transaction->fraud->countryCode ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "country-{$transaction->fraud->countryCode}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                    </span>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        </ul>

                                        <ul class='cTransactionFraud'>
                                            
IPSCONTENT;

if ( $transaction->fraud->countryMatch == 'Yes' ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_positive'>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_countryMatch_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                </li>
                                            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_negative'>
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                    <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_countryMatch_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( $transaction->fraud->highRiskCountry == 'No' ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_positive'>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_highRiskCountry_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                </li>
                                            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_negative'>
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                    <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_highRiskCountry_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        </ul>
                                    </div>
                                </div>
                                <div class='ipsSpanGrid__4 i-background_1'>
                                    <h3 class='acpBlock_title acpBlock_titleSmall'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                    <div class='i-padding_3'>
                                        <div class="ipsProgress">
                                            <div class="ipsProgress__progress 
IPSCONTENT;

if ( $transaction->fraud->proxyScorePercentage() > 75 ):
$return .= <<<IPSCONTENT
ipsProgress__progress--warning
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" style="width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->proxyScorePercentage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->proxyScorePercentage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%</div>
                                        </div>
                                        <p class='i-font-size_1 i-margin-top_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_proxyScore', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                        <p class='i-font-size_-1 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_proxyScore_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

                                        <ul class='cTransactionFraud i-margin-top_1'>
                                            
IPSCONTENT;

if ( $transaction->fraud->anonymousProxy == 'No' ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_success'>
                                                    <i class="fa-solid fa-check-circle"></i>
                                                    <p>
                                                        <span class='cTransactionFraud_geoInfo' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_anonymousProxy_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
                                                            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_anonymousProxy_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                        </span>
                                                    </p>
                                                </li>
                                            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_negative'>
                                                    <i class="fa-solid fa-circle-exclamation"></i>
                                                    <p>
                                                        <span class='cTransactionFraud_geoInfo' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_anonymousProxy_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
                                                            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_anonymousProxy_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                        </span>
                                                    </p>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


                                            
IPSCONTENT;

foreach ( array( 'isTransProxy', 'ip_corporateProxy' ) as $k ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_neutral'>
                                                    <i class="fa-solid fa-circle-info"></i>
                                                    <p>
                                                        
IPSCONTENT;

if ( $transaction->fraud->$k == 'No' ):
$return .= <<<IPSCONTENT

                                                            
IPSCONTENT;

$val = "maxmind_{$k}_n"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                            
IPSCONTENT;

$val = "maxmind_{$k}_y"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                    </p>
                                                </li>
                                            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


                                            
IPSCONTENT;

if ( $transaction->fraud->ip_isp ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_neutral'>
                                                    <i class="fa-solid fa-circle-info"></i>
                                                    <p>
                                                        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_isp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_isp, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                                        
IPSCONTENT;

if ( $transaction->fraud->ip_org and $transaction->fraud->ip_org != $transaction->fraud->ip_isp ):
$return .= <<<IPSCONTENT

                                                            <br><span class="i-color_soft i-font-size_-1">(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_org, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
                                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                    </p>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( $transaction->fraud->ip_userType ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_neutral'>
                                                    <i class="fa-solid fa-circle-info"></i>
                                                    <p>
                                                        
IPSCONTENT;

$val = "maxmind_ip_userType_{$transaction->fraud->ip_userType}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                    </p>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( $transaction->fraud->ip_timeZone ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_neutral'>
                                                    <i class="fa-solid fa-circle-info"></i>
                                                    <p>
                                                        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_timeZone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_timeZone, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                                    </p>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( $transaction->fraud->ip_netSpeedCell ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_neutral'>
                                                    <i class="fa-solid fa-circle-info"></i>
                                                    <p>
                                                        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_netSpeedCell', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_netSpeedCell, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                                    </p>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( $transaction->fraud->ip_domain ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_neutral'>
                                                    <i class="fa-solid fa-circle-info"></i>
                                                    <p>
                                                        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_domain', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_domain, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                                    </p>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( $transaction->fraud->ip_asnum ):
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_neutral'>
                                                    <i class="fa-solid fa-circle-info"></i>
                                                    <p>
                                                        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_ip_asnum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->ip_asnum, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                                    </p>
                                                </li>
                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        </ul>
                                    </div>
                                </div>
                                <div class='ipsSpanGrid__4 i-background_1'>
                                    
IPSCONTENT;

if ( $transaction->fraud->binMatch !== 'NA' and $transaction->fraud->binMatch !== 'NotFound' ):
$return .= <<<IPSCONTENT

                                        <h3 class='acpBlock_title acpBlock_titleSmall'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_card_issuer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                        <div class='i-padding_3'>
                                            
IPSCONTENT;

if ( $transaction->fraud->binName ):
$return .= <<<IPSCONTENT

                                                <p class='i-font-size_2'>
                                                    <strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->binName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
                                                </p>
                                                
IPSCONTENT;

if ( $transaction->fraud->binCountry || $transaction->fraud->binPhone ):
$return .= <<<IPSCONTENT

                                                    <ul class='cTransactionFraud i-margin-top_1'>
                                                        
IPSCONTENT;

if ( $transaction->fraud->binCountry ):
$return .= <<<IPSCONTENT

                                                            <li class='cTransactionFraud_neutral'>
                                                                <i class="fa-solid fa-earth-americas"></i>
                                                                <p>
                                                                    
IPSCONTENT;

$val = "country-{$transaction->fraud->binCountry}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                                </p>
                                                            </li>
                                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                        
IPSCONTENT;

if ( $transaction->fraud->binPhone ):
$return .= <<<IPSCONTENT

                                                            <li class='cTransactionFraud_neutral'>
                                                                <i class="fa-solid fa-phone"></i>
                                                                <p>
                                                                    
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud->binPhone, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                                                </p>
                                                            </li>
                                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                    </ul>
                                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


                                            <ul class='cTransactionFraud 
IPSCONTENT;

if ( $transaction->fraud->binName ):
$return .= <<<IPSCONTENT
i-margin-top_1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
                                                
IPSCONTENT;

if ( $transaction->fraud->binMatch == 'Yes' ):
$return .= <<<IPSCONTENT

                                                    <li class='cTransactionFraud_success'>
                                                        <i class="fa-solid fa-check-circle"></i>
                                                        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_binMatch_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                    </li>
                                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                    <li class='cTransactionFraud_negative'>
                                                        <i class="fa-solid fa-circle-exclamation"></i>
                                                        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_binMatch_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                    </li>
                                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                <li class='cTransactionFraud_neutral'>
                                                    <i class="fa-solid fa-circle-exclamation"></i>
                                                    <p>
                                                        
IPSCONTENT;

if ( $transaction->fraud->prepaid == 'Yes' ):
$return .= <<<IPSCONTENT

                                                            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_binMatch_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_binMatch_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                    </p>
                                                </li>
                                            </ul>
                                        </div>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


                                    <h3 class='acpBlock_title acpBlock_titleSmall'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_customer_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                                    <div class='i-padding_3'>
                                        <ul class='cTransactionFraud'>
                                            
IPSCONTENT;

if ( $transaction->fraud->custPhoneInBillingLoc and $transaction->fraud->custPhoneInBillingLoc != 'NotFound' ):
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

if ( $transaction->fraud->custPhoneInBillingLoc == 'No' ):
$return .= <<<IPSCONTENT

                                                    <li class='cTransactionFraud_negative'>
                                                        <i class="fa-solid fa-circle-exclamation"></i>
                                                        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_custPhoneInBillingLoc_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                    </li>
                                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                    <li class='cTransactionFraud_positive'>
                                                        <i class="fa-solid fa-check-circle"></i>
                                                        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_custPhoneInBillingLoc_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                    </li>
                                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

if ( $transaction->fraud->cityPostalMatch ):
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

if ( $transaction->fraud->cityPostalMatch == 'No' ):
$return .= <<<IPSCONTENT

                                                    <li class='cTransactionFraud_negative'>
                                                        <i class="fa-solid fa-circle-exclamation"></i>
                                                        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_cityPostalMatch_n', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                    </li>
                                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                    <li class='cTransactionFraud_positive'>
                                                        <i class="fa-solid fa-check-circle"></i>
                                                        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_cityPostalMatch_y', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                    </li>
                                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

foreach ( array( 'freeMail', 'carderEmail' ) as $k ):
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

if ( $transaction->fraud->$k == 'No' ):
$return .= <<<IPSCONTENT

                                                    <li class='cTransactionFraud_positive'>
                                                        <i class="fa-solid fa-check-circle"></i>
                                                        <p>
IPSCONTENT;

$val = "maxmind_{$k}_n"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                    </li>
                                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                    <li class='cTransactionFraud_negative'>
                                                        <i class="fa-solid fa-circle-exclamation"></i>
                                                        <p>
IPSCONTENT;

$val = "maxmind_{$k}_y"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                                                    </li>
                                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <hr class='ipsHr'>
                            <p class='i-font-size_1 i-color_soft'>
                                
IPSCONTENT;

if ( $transaction->fraud->minfraud_version ):
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

$sprintf = array($transaction->fraud->service_level, $transaction->fraud->minfraud_version, $transaction->fraud->maxmindID); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_footer', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;

$sprintf = array($transaction->fraud->queriesRemaining); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'maxmind_queries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
)
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                            </p>
                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    </div>
                </div>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		<div class='ipsSpanGrid'>
			<div class='ipsSpanGrid__6'>
				<div class="ipsBox">
					<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<div class='i-padding_3'>
						<ul class='cTransaction'>
							<li>	
								<div class='cTransaction_icon'>
									<i class='fa-solid fa-cart-shopping'></i>
								</div>
								<div class='cTransaction_info'>
									<h2 class='ipsTitle ipsTitle--h3'>
                                        
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

if ( $transaction->method !== 0  ):
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_okay_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_okay_credit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</h2>
									<p class="i-color_soft">
										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->date, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									</p>
									
IPSCONTENT;

if ( $transaction->gw_id ):
$return .= <<<IPSCONTENT

										<p class="i-color_soft">
										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payment_reference', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

if ( $transaction->method AND $url = $transaction->method->gatewayUrl( $transaction ) ):
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel='noreferrer'>
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
										</p>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $transaction->method and $extraData = $transaction->method->extraData( $transaction ) ):
$return .= <<<IPSCONTENT

										{$extraData}
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $transaction->billing_agreement ):
$return .= <<<IPSCONTENT

										<p>
										    <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->billing_agreement->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($transaction->billing_agreement->gw_id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a><br>
										</p>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $transaction->ip ):
$return .= <<<IPSCONTENT

										<span class='i-font-size_1 i-color_soft'>
											<a class="i-link-color_inherit" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=ip&ip={$transaction->ip}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($transaction->ip); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_ip', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
										</span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</li>

							
IPSCONTENT;

if ( $transaction->fraud_blocked ):
$return .= <<<IPSCONTENT

								<li>
									<div class='cTransaction_icon'>
										<i class='fa-solid fa-circle-exclamation'></i>
									</div>
									<div class='cTransaction_info'>
										<h2 class='ipsTitle ipsTitle--h3'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'triggered_fraud_rule', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></h2>
										<p>
											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->fraud_blocked->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										</p>
									</div>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


							
IPSCONTENT;

foreach ( $transaction->history() as $log ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "transactions", \IPS\Request::i()->app )->{$log['s']}( $transaction, $log );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</div>
			</div>
			<div class='ipsSpanGrid__6'>
				
IPSCONTENT;

if ( $transaction->member AND $transaction->member->member_id ):
$return .= <<<IPSCONTENT

					<div class='ipsBox i-margin-bottom_1'>
						<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_customer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						<div class='i-padding_3'>
							<div class='ipsPhotoPanel ipsPhotoPanel_small'>
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsUserPhoto ipsUserPhoto--small'><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading="lazy" alt=""></a>
								<div class="ipsPhotoPanel__text">
									<h3 class="ipsPhotoPanel__primary">
										<strong><a class="i-link-color_inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></strong>
									</h3>
									<p class="ipsPhotoPanel__secondary">
                                        <span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><br>
									</p>
								</div>
                                
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_void' ) ):
$return .= <<<IPSCONTENT

                                    <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=customers&controller=view&do=void&id={$transaction->member->member_id}&rt={$transaction->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--small" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'void_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'void_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
                            <ul class="ipsList ipsList--inline ipsList--sep i-margin-top_2">
                                <li><strong>
IPSCONTENT;

$sprintf = array($transaction->member->totalSpent()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_spent', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</strong></li>
                                <li>
IPSCONTENT;

$sprintf = array($transaction->member->joined->localeDate()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_customer_since', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</li>
                            </ul>
						</div>
					</div>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class='ipsBox i-margin-bottom_1'>
						<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_customer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						<div class='i-padding_3'>
							<div class='i-background_2 i-padding_3 ipsPhotoPanel ipsPhotoPanel_large'>
								<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "default_photo.png", "core", 'global', false );
$return .= <<<IPSCONTENT
' class='ipsUserPhoto'>
								<div>
									
IPSCONTENT;

if ( $transaction->invoice AND !empty( $transaction->invoice->guest_data ) ):
$return .= <<<IPSCONTENT

										<h3 class="ipsTitle ipsTitle--h3">
											<strong>
												
IPSCONTENT;

if ( isset( $transaction->invoice->guest_data['member']['name'] ) ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->invoice->guest_data['member']['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

elseif ( isset( $transaction->invoice->guest_data['member']['cm_first_name'] ) ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->invoice->guest_data['member']['cm_first_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->invoice->guest_data['member']['cm_last_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											</strong>
											&nbsp;&nbsp;<span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->invoice->guest_data['member']['email'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
										</h3>
                                    
IPSCONTENT;

elseif ( !$transaction->member ):
$return .= <<<IPSCONTENT

                                        <span class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'deleted_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<span class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</div>
						</div>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
				
IPSCONTENT;

if ( $transaction->invoice ):
$return .= <<<IPSCONTENT

					<div class="ipsBox i-margin-bottom_1">
						<h2 class="ipsBox__header">
IPSCONTENT;

$sprintf = array($transaction->invoice->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h2>
						<div class='i-padding_3'>
							
IPSCONTENT;

$summary = $transaction->invoice->summary();
$return .= <<<IPSCONTENT

							<i-data>
                                <ul class="ipsData ipsData--table ipsData--compact ipsData--invoice">
                                    
IPSCONTENT;

foreach ( $summary['items'] as $item ):
$return .= <<<IPSCONTENT

                                        <li class="ipsData__item">
                                            <div class="ipsData__main">
                                                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $item->quantity > 1 ):
$return .= <<<IPSCONTENT
 x
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->quantity, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

if ( \count( $item->details ) ):
$return .= <<<IPSCONTENT

                                                    <br>
                                                    <span class="i-color_soft">
                                                        
IPSCONTENT;

foreach ( $item->details as $k => $v ):
$return .= <<<IPSCONTENT

                                                            
IPSCONTENT;

$val = "nexus_pfield_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
                                            <div class="i-basis_100 i-text-align_end">
                                                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->linePrice(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                            </div>
                                        </li>
                                    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                                    <li class="ipsData__item cTransactionInvoice_subtotal">
                                        <div class="ipsData__main">
                                            <strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subtotal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
                                        </div>
                                        <div class="i-basis_100 i-text-align_end">
                                            <strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['subtotal'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
                                        </div>
                                    </li>
                                    
IPSCONTENT;

foreach ( $summary['tax'] as $taxId => $tax ):
$return .= <<<IPSCONTENT

                                        <li class="ipsData__item">
                                            <div class="ipsData__main">
                                                
IPSCONTENT;

$val = "nexus_tax_{$taxId}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

if ( $tax['type'] !== 'single' ):
$return .= <<<IPSCONTENT

                                                    
IPSCONTENT;

if ( $tax['type'] === 'eu' and $transaction->invoice->billaddress->business and $transaction->invoice->billaddress->vat ):
$return .= <<<IPSCONTENT

                                                        (
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tax_rate_eu', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)
                                                    
IPSCONTENT;

elseif ( $transaction->invoice->billaddress->business ):
$return .= <<<IPSCONTENT

                                                        (
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tax_rate_business', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)
                                                    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                        (
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tax_rate_consumer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)
                                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                                    (
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $tax['rate']*100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%)
                                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                            </div>
                                            <div class="i-basis_100 i-text-align_end">
                                                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tax['amount'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                                            </div>
                                        </li>
                                    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                                    <li class="ipsData__item i-font-size_2 cTransactionInvoice_subtotal">
                                        <div class="ipsData__main">
                                            <strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
                                        </div>
                                        <div class="i-basis_100 i-text-align_end">
                                            <strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summary['total'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
                                        </div>
                                    </li>
                                </ul>
                            </i-data>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->invoice->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-margin-top_1 ipsButton ipsButton--inherit ipsButton--wide">
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-angle-right'></i>
							</a>
						</div>
					</div>
	
					
IPSCONTENT;

if ( $transaction->invoice->billaddress ):
$return .= <<<IPSCONTENT

						<div class="ipsBox">
							<h3 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_addresses', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<div class='i-padding_3'>
								
IPSCONTENT;

if ( $transaction->invoice->billaddress ):
$return .= <<<IPSCONTENT

									<div class='ipsSpanGrid i-margin-bottom_1'>
										
IPSCONTENT;

if ( \IPS\GeoLocation::enabled() ):
$return .= <<<IPSCONTENT

										<div class='ipsSpanGrid__6'>
											{$transaction->invoice->billaddress->map()->render( 350, 150 )}
										</div>
										<div class='ipsSpanGrid__6'>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<div class='ipsSpanGrid__12'>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											<h4 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
											<p>
												{$transaction->invoice->billaddress->toString('<br>')}
											</p>
											
IPSCONTENT;

if ( isset( $transaction->invoice->billaddress->vat ) and $transaction->invoice->billaddress->vat ):
$return .= <<<IPSCONTENT

												<p class='i-margin-top_1'>
													
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cm_checkout_vat_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->vatNumber( $transaction->invoice->billaddress->vat );
$return .= <<<IPSCONTENT

												</p>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

else:
$return .= <<<IPSCONTENT

					<br>
					<div class="ipsMessage ipsMessage--info">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'trans_invoice_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>
</div>	
IPSCONTENT;

		return $return;
}

	function wait( $transaction, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li>
	<div class='cTransaction_icon'>
		<i class='fa-regular fa-clock'></i>
	</div>
	<div class='cTransaction_info'>
		<span class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tstatus_wait', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

if ( isset( $log['on'] ) and $log['on'] ):
$return .= <<<IPSCONTENT

			<br>
			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $log['on'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $log['by'] ) and $log['by'] ):
$return .= <<<IPSCONTENT

				<br>
				<span class='i-font-size_1 i-color_soft'>
					
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $log['by'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $log['note'] ) and $log['note'] ):
$return .= <<<IPSCONTENT

			<p class='cTransaction_note'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['note'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}}