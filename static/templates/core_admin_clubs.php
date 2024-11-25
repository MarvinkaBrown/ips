<?php
namespace IPS\Theme;
class class_core_admin_clubs extends \IPS\Theme\Template
{	function clubTypesBar( $percentages, $rawCounts ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-background_2">
	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_club_types', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='acpStatsBar i-margin-bottom_1'>
		
IPSCONTENT;

foreach ( $percentages as $type => $percent ):
$return .= <<<IPSCONTENT

			<div class='acpStatsBar_segment' style='width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $percent, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%;' title='
IPSCONTENT;

$val = "club_type_{$type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $rawCounts[ $type ] );
$return .= <<<IPSCONTENT
' data-ipsTooltip-label='
IPSCONTENT;

$val = "club_type_{$type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $rawCounts[ $type ] );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsTooltip-safe></div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	<ul class='ipsList ipsList--inline'>
		
IPSCONTENT;

foreach ( $percentages as $type => $percent ):
$return .= <<<IPSCONTENT

			<li class='acpStatsBar_legend'>
				<span class='acpStatsBar_preview'></span>
				
IPSCONTENT;

$val = "club_type_{$type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

	function disabled( $availableTypes ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_3'>
	<p class="i-font-size_2">
IPSCONTENT;

$sprintf = array($availableTypes); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clubs_enable_blurb_1', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
	<p class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clubs_enable_blurb_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<p class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clubs_enable_blurb_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<br>
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=clubs&controller=clubs&do=enable" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
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

	function members( $value, $link ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function name( $name, $data ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$data['approved'] ):
$return .= <<<IPSCONTENT

	<span class="ipsBadge ipsBadge--small ipsBadge--icon ipsBadge--warning" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unapproved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
		<i class="fa-solid fa-eye-slash"></i>
	</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function owner( $owner ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userPhoto( $owner, 'tiny' );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $owner->member_id ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userLink( $owner );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'deleted_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function privacy( $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $value === \IPS\Member\Club::TYPE_PUBLIC ):
$return .= <<<IPSCONTENT

	<span class="ipsBadge ipsBadge--style4">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_type_public', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

elseif ( $value === \IPS\Member\Club::TYPE_OPEN ):
$return .= <<<IPSCONTENT

	<span class="ipsBadge ipsBadge--style1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_type_open', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

elseif ( $value === \IPS\Member\Club::TYPE_CLOSED ):
$return .= <<<IPSCONTENT

	<span class="ipsBadge ipsBadge--style7">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_type_closed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

elseif ( $value === \IPS\Member\Club::TYPE_PRIVATE ):
$return .= <<<IPSCONTENT

	<span class="ipsBadge ipsBadge--style5">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_type_private', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

elseif ( $value === \IPS\Member\Club::TYPE_READONLY ):
$return .= <<<IPSCONTENT

	<span class="ipsBadge ipsBadge--style2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_type_readonly', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function statsOverview( $clubTypes, $clubSignups, $clubCreations ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-margin-bottom_1 ipsChart ipsBox'>
	{$clubTypes}
</div>
<hr class='ipsHr'>
<div class='ipsChart ipsBox'>{$clubSignups}</div>
<hr class='ipsHr'>
<div>{$clubCreations}</div>
IPSCONTENT;

		return $return;
}}