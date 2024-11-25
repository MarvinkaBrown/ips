<?php
namespace IPS\Theme;
class class_nexus_admin_payouts extends \IPS\Theme\Template
{	function link( $payout ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->amount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function Manual( $payout ) {
		$return = '';
		$return .= <<<IPSCONTENT

<pre>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->data, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
IPSCONTENT;

		return $return;
}

	function maximumLimits( $amount, $periodValue, $periodType ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$amount}

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'every', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp;
<input name="nexus_payout_maximum[0]" size="5" class="ipsInput ipsField_short ipsField_tiny" min="1" step="any" type="number" value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $periodValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
<select class="ipsInput ipsInput--select" name='nexus_payout_maximum[1]'>
	<option value='day'
IPSCONTENT;

if ( $periodType == 'day' ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'days', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value='week'
IPSCONTENT;

if ( $periodType == 'week' ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'weeks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value='month'
IPSCONTENT;

if ( $periodType == 'month' ):
$return .= <<<IPSCONTENT
 selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'months', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>

IPSCONTENT;

		return $return;
}

	function PayPal( $payout ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-text-align_center">
	<span class="i-font-size_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->data, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
</div>
<br>
IPSCONTENT;

		return $return;
}

	function status( $status ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="ipsBadge ipsBadge--style
IPSCONTENT;

if ( $status == \IPS\nexus\Payout::STATUS_COMPLETE ):
$return .= <<<IPSCONTENT
4
IPSCONTENT;

elseif ( $status == \IPS\nexus\Payout::STATUS_PENDING ):
$return .= <<<IPSCONTENT
1
IPSCONTENT;

elseif ( $status == \IPS\nexus\Payout::STATUS_CANCELED ):
$return .= <<<IPSCONTENT
5
IPSCONTENT;

elseif ( $status == \IPS\nexus\Payout::STATUS_PROCESSING ):
$return .= <<<IPSCONTENT
7
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

$val = "postatus_{$status}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</span>
IPSCONTENT;

		return $return;
}

	function Stripe( $payout ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function view( $payout ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsSpanGrid" data-ips-template="view">
	<div class="ipsSpanGrid__7 i-background_3">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payout_status', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="i-padding_3">
			<div class="i-background_2 i-padding_3 i-text-align_center">
				<p>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->date, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
				<p>
					<span class="i-font-size_6">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->amount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><br>
					
IPSCONTENT;

$sprintf = array($payout->gateway); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payout_method', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</p>
				<p class="i-color_soft"><a class="i-link-color_inherit" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=ip&ip={$payout->ip}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($payout->ip); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_ip', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></p>
			</div>
			<div class="i-text-align_center i-font-size_6">
				<i class="fa-solid fa-arrow-down"></i>
			</div>
			
IPSCONTENT;

if ( $payout->status === $payout::STATUS_CANCELED ):
$return .= <<<IPSCONTENT

				<div class="i-background_2 i-padding_3 i-text-align_center">
					<p>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->completed, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $payout->processed_by ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->processed_by->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
					<p>
						<span class="i-font-size_6"><i class="fa-solid fa-xmark"></i></span><br>
						<span class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payout_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</p>
				</div>
			
IPSCONTENT;

elseif ( $payout->status === $payout::STATUS_COMPLETE ):
$return .= <<<IPSCONTENT

				<div class="i-background_2 i-padding_3 i-text-align_center">
					<p>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->completed, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $payout->processed_by ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->processed_by->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
					<p>
						<span class="i-font-size_6"><i class="fa-solid fa-check-circle"></i></span><br>
						<span class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payout_processed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</p>
					
IPSCONTENT;

if ( $payout->gw_id ):
$return .= <<<IPSCONTENT

						<p>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
            
IPSCONTENT;

elseif ( $payout->status === $payout::STATUS_PROCESSING ):
$return .= <<<IPSCONTENT

                <div class="i-background_2 i-padding_3 i-text-align_center">
                    <p>
                        
IPSCONTENT;

if ( $payout->processed_by ):
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->processed_by->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    </p>
                    <p>
                        <span class="i-font-size_6"><i class="fa-solid fa-circle-o-notch fa-spin"></i></span><br>
                        <span class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payout_processing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                    </p>
                    
IPSCONTENT;

if ( $payout->gw_id ):
$return .= <<<IPSCONTENT

                        <p>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                </div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class="i-background_2 i-padding_3 i-text-align_center">
					<p>
						<span class="i-font-size_6"><i class="fa-solid fa-pause"></i></span><br>
						<span class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'payout_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</p>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	<div class="ipsSpanGrid__5">
		<div class="i-background_2">
			<h2 class="ipsBox__header"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'po_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></h2>
			<div class='i-padding_2 ipsPhotoPanel ipsPhotoPanel_large'>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsUserPhoto ipsUserPhoto--medium' loading="lazy" alt=""></a>
				<div>
					<span class="i-font-size_2"><a class="i-link-color_inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></span><br>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $payout->member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
				</div>
			</div>
		</div>
		<br><br>
		
IPSCONTENT;

if ( $extra = $payout->acpHtml() ):
$return .= <<<IPSCONTENT

		<div class="i-background_2">
			<h2 class="ipsBox__header">
IPSCONTENT;

$val = "payout__data_{$payout->gateway}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<div class='i-padding_3'>
				{$extra}
			</div>
		</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}}