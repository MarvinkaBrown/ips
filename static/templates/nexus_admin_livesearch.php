<?php
namespace IPS\Theme;
class class_nexus_admin_livesearch extends \IPS\Theme\Template
{	function customer( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsPhotoPanel ipsPhotoPanel_mini i-padding_2' data-role='result'>
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsUserPhoto ipsUserPhoto--mini" target="_blank" rel='noopener'>
		<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="" loading="lazy">
	</a>
	<div class="ipsPhotoPanel__text">
		<h2 class='ipsPhotoPanel__primary'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
		<p class='ipsPhotoPanel__secondary'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function invoice( $invoice ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsPhotoPanel ipsPhotoPanel_mini i-padding_2' data-role='result'>
	<div class="ipsPhotoPanel__text">
		<h2 class='ipsPhotoPanel__primary'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "invoices", "nexus" )->status( $invoice->status );
$return .= <<<IPSCONTENT

		</h2>
		<p class='ipsPhotoPanel__secondary'>
IPSCONTENT;

$sprintf = array($invoice->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invoice_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->total, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $invoice->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function licensekey( $lkey ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsPhotoPanel ipsPhotoPanel_mini i-padding_2' data-role='result'>
	<div class="ipsPhotoPanel__text">
		<h2 class='ipsPhotoPanel__primary'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lkey->purchase->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lkey->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
		<p class='ipsPhotoPanel__secondary'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lkey->purchase->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

$sprintf = array($lkey->purchase->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lkey->purchase->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function purchase( $purchase ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsPhotoPanel ipsPhotoPanel_mini i-padding_2' data-role='result'>
	<div class="ipsPhotoPanel__text">
		<h2 class='ipsPhotoPanel__primary'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			&nbsp;
			
IPSCONTENT;

if ( $purchase->cancelled ):
$return .= <<<IPSCONTENT

				<span class="ipsBadge ipsBadge--style5">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

elseif ( !$purchase->active ):
$return .= <<<IPSCONTENT

				<span class="ipsBadge ipsBadge--style6">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h2>
		<p class='ipsPhotoPanel__secondary'>
IPSCONTENT;

$sprintf = array($purchase->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function transaction( $transaction ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsPhotoPanel ipsPhotoPanel_mini i-padding_2' data-role='result'>
	<div class="ipsPhotoPanel__text">
		<h2 class='ipsPhotoPanel__primary'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array($transaction->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
			&nbsp;
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "transactions", "nexus" )->status( $transaction->status );
$return .= <<<IPSCONTENT

		</h2>
		<p class='ipsPhotoPanel__secondary'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->amount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

if ( $transaction->method ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->method->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_credit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $transaction->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
	</div>
</li>
IPSCONTENT;

		return $return;
}}