<?php
namespace IPS\Theme;
class class_core_global_notifications extends \IPS\Theme\Template
{	function newVersion( $details ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText" data-ipsTruncate>
	{$details['releasenotes']}
</div>
<div class="i-flex i-align-items_center i-flex-wrap_wrap i-gap_1 i-padding-top_2">
	<ul class="ipsButtons">
		<li>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&_new=1", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</li>
		<li>
			<a href='
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/release_notes" );
$return .= <<<IPSCONTENT
' target='_blank' rel='nofollow noopener' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_more_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</li>
	</ul>
</div>
IPSCONTENT;

		return $return;
}}