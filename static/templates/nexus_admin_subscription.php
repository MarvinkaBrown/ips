<?php
namespace IPS\Theme;
class class_nexus_admin_subscription extends \IPS\Theme\Template
{	function convert( $form, $package ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsMessage ipsMessage--general">
	
IPSCONTENT;

$sprintf = array($package->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_convert_message', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

</div>
{$form}
IPSCONTENT;

		return $return;
}

	function disabled(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_3'>
	<p class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_off_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<br>
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&do=enable" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function packageLink( $package ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $package->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function rowHtml( $package, $price, $active, $inactive ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsTree_row_cells i-margin-top_1">
	<span class="ipsTree_row_cell i-color_soft"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscribers&nexus_sub_package_id={$package->id}&filter=nexus_subs_filter_active", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($active); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_package_count_active', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></span>
	<span class="ipsTree_row_cell i-color_soft"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscribers&nexus_sub_package_id={$package->id}&filter=nexus_subs_filter_inactive", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($inactive); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_sub_package_count_inactive', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></span>
	<span class="ipsTree_row_cell i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $price, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
</div>
IPSCONTENT;

		return $return;
}

	function status( $active ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="ipsBadge 
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT
ipsBadge--positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsBadge--neutral
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'inactive', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}}