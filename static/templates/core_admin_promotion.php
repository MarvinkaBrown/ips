<?php
namespace IPS\Theme;
class class_core_admin_promotion extends \IPS\Theme\Template
{	function activeBadge( $id, $text, $currentStatus, $ad ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$filter = \IPS\Widget\Request::i()->filter;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $currentStatus === -1 ):
$return .= <<<IPSCONTENT

	<span class='ipsBadge ipsBadge--negative'>
IPSCONTENT;

$val = "{$text}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

elseif ( ( $ad['ad_end'] AND $ad['ad_end'] < time() ) OR ( $ad['ad_maximum_unit'] == 'i' AND $ad['ad_maximum_value'] > -1 AND $ad['ad_impressions'] >= $ad['ad_maximum_value'] ) OR ( $ad['ad_maximum_unit'] == 'c' AND $ad['ad_maximum_value'] > -1 AND $ad['ad_clicks'] >= $ad['ad_maximum_value'] )  ):
$return .= <<<IPSCONTENT

	<span class='ipsBadge ipsBadge--neutral' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ad_nostatus_change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<span data-ipsStatusToggle>
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=promotion&controller=advertisements&do=toggle&id=$id&status=0&filter={$filter}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_inactive', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $currentStatus !== 1 ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-state="enabled">
			<span class='ipsBadge ipsBadge--positive'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</span>
		</a>
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=promotion&controller=advertisements&do=toggle&id=$id&status=1&filter={$filter}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $currentStatus !== 0 ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-state="disabled">
			<span class='ipsBadge ipsBadge--negative'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</span>
		</a>
	</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function adsenseHelp(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="i-margin-bottom_3">
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>
	
	<div class="i-margin-bottom_3">
		<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_login_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_login_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<a href='
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/secure-ads-adsense" );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--secondary" target='_blank' rel="noopener">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_login_link', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
	
	<div class="i-margin-bottom_3">
		<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_ajax_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_ajax_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_ajax_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<ol>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_ajax_ins_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_ajax_ins_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_ajax_ins_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_ajax_ins_4', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
		</ol>
	</div>
	
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_adsense_other', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
IPSCONTENT;

		return $return;
}

	function advertisementIframePreview( $id ) {
		$return = '';
		$return .= <<<IPSCONTENT


<iframe class='ips
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\IPS::mb_ucfirst(\IPS\SUITE_UNIQUE_KEY), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Preview' src='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=promotion&controller=advertisements&do=getHtml&id={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' style="width:100%"></iframe>

IPSCONTENT;

		return $return;
}

	function advertisementMessage(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-background_2 i-padding_3 ipsPhotoPanel ipsPhotoPanel_small'>
	<i class='fa-solid fa-circle-info i-font-size_6'></i>
	<div>
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ad_promo_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<p>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ad_promo_msg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
		<br>
		<a href='
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/product-nexus" );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_information', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
<br><br>

IPSCONTENT;

		return $return;
}

	function imageMaximums( $name, $value, $type ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ad_after', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[value]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_v" 
IPSCONTENT;

if ( $value === -1 ):
$return .= <<<IPSCONTENT
value='' data-jsdisable="true"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsField_short" size="5">
<select class="ipsInput ipsInput--select" name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[type]' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_t'>
	<option value='c' 
IPSCONTENT;

if ( $type == 'c' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ad_max_clicks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value='i' 
IPSCONTENT;

if ( $type == 'i' ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ad_max_impressions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
<div class="ipsFieldRow__inlineCheckbox">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<input type="checkbox" class="ipsInput ipsInput--toggle" role='checkbox' data-control="unlimited" name="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-unlimitedCheck' value="-1" 
IPSCONTENT;

if ( -1 === $value ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 aria-labelledby='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>
	<label for='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-unlimitedCheck' class='ipsField_unlimited' id='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'never', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
</div>
IPSCONTENT;

		return $return;
}

	function metaTagUrl( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Settings::i()->base_url;
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}