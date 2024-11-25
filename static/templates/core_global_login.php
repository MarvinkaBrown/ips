<?php
namespace IPS\Theme;
class class_core_global_login extends \IPS\Theme\Template
{	function authyAuthenticate( $method, $done, $error, $setup, $availableMethods, $onetouch=NULL, $url=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elAuthy" 
IPSCONTENT;

if ( $onetouch ):
$return .= <<<IPSCONTENT
data-controller="core.global.core.authyOneTouch"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<div class="i-padding_3 i-text-align_center">
		
IPSCONTENT;

if ( $setup ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $method == 'authy' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_authy_setup', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( $method == 'phone' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_call_auth_done', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_sms_auth_done', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $method == 'authy' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $onetouch ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_onetouch_auth', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_authy_auth', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( $method == 'phone' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $done ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_call_auth_done', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_call_auth', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( $method == 'choose' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_choose_auth', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_sms_auth', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $method == 'authy' ):
$return .= <<<IPSCONTENT

			<div class='i-text-align_center'>
				
IPSCONTENT;

if ( $onetouch ):
$return .= <<<IPSCONTENT

					<input type="hidden" name="onetouch" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $onetouch, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="onetouchCode">
					<div class="ipsRedirect i-padding_3 ipsJS_show">
						<div class="ipsLoading ipsRedirect--loading"></div>
						<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_onetouch_waiting', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $setup ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$langCode = mb_substr( \IPS\Member::loggedIn()->language()->bcp47(), 0, 2 );
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/authy_ios" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener"><img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "appstores/ios/{$langCode}.svg", "core", 'global', false );
$return .= <<<IPSCONTENT
" class="i-margin-block_2"></a>
					<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/authy_android" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener"><img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "appstores/android/{$langCode}.png", "core", 'global', false );
$return .= <<<IPSCONTENT
" style="height: 60px"></a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $availableMethods ) > 1 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $setup ):
$return .= <<<IPSCONTENT

						<p>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_setup_alternative', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<ul class="ipsList ipsList--inline 
IPSCONTENT;

if ( !$setup ):
$return .= <<<IPSCONTENT
i-margin-top_3
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

if ( \in_array( 'sms', $availableMethods ) ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf()->setQueryString( 'authy_method', 'sms' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_alt_sms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \in_array( 'phone', $availableMethods ) ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf()->setQueryString( 'authy_method', 'phone' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_alt_phone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( $done ):
$return .= <<<IPSCONTENT

		<ul class="i-padding_3 i-background_3">
			
IPSCONTENT;

if ( !$onetouch ):
$return .= <<<IPSCONTENT

				<li class="ipsFieldRow ">
					<div class="ipsFieldRow__content cAuthy_container">
						
IPSCONTENT;

if ( $method == 'authy' ):
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "login/authy.png", "core", 'global', false );
$return .= <<<IPSCONTENT
" class="cAuthy_icon">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<input type="text" name="authy_auth_code" value="" class="ipsInput ipsInput--text 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->authy_auth_code ):
$return .= <<<IPSCONTENT
ipsField_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $method == 'authy' ):
$return .= <<<IPSCONTENT
cAuthy_field
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" autocomplete="one-time-code" >
						
IPSCONTENT;

if ( \IPS\Widget\Request::i()->authy_auth_code or ( $method == 'authy' and \IPS\Widget\Request::i()->authy_method ) ):
$return .= <<<IPSCONTENT

							<p class="i-color_warning">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<li>
				<button type='submit' class='ipsButton ipsButton--primary ipsButton--wide i-margin-bottom_3 
IPSCONTENT;

if ( $onetouch ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

if ( $onetouch ):
$return .= <<<IPSCONTENT

						<i class='fa-solid fa-check'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_submit_onetouch', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i class='fa-solid fa-lock'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_submit_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</button>
			</li>
			
IPSCONTENT;

if ( $setup ):
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf()->setQueryString( '_new', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--text ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_change_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class="i-padding_3 i-background_3 ipsSpanGrid">
			
IPSCONTENT;

if ( \in_array( 'phone', $availableMethods ) ):
$return .= <<<IPSCONTENT

				<div class="ipsSpanGrid__
IPSCONTENT;

if ( \in_array( 'sms', $availableMethods ) ):
$return .= <<<IPSCONTENT
6
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
12
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf()->setQueryString( 'authy_method', 'phone' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary ipsButton--wide'>
						<i class='fa-solid fa-phone'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_submit_call', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( 'sms', $availableMethods ) ):
$return .= <<<IPSCONTENT

				<div class="ipsSpanGrid__
IPSCONTENT;

if ( \in_array( 'phone', $availableMethods ) ):
$return .= <<<IPSCONTENT
6
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
12
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf()->setQueryString( 'authy_method', 'sms' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="sms" class='ipsButton ipsButton--primary ipsButton--wide'>
						<i class='fa-solid fa-commenting'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_submit_sms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function authyError( $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elAuthy">
	<div class="i-padding_3 ipsRichText i-text-align_center">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->isAdmin() ):
$return .= <<<IPSCONTENT

			<div class="i-color_warning i-margin-top_3">
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->canUseContactUs() ):
$return .= <<<IPSCONTENT

	<div class="i-background_3 i-padding_3">
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=contact&controller=contact", "front", "contact", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_recovery_contact', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function authySetup( $countryCode, $phoneNumber, $showingMultipleForms, $methods, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elAuthy">
	<div class='i-padding_3'>
		
IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

			<h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_setup_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsRichText i-text-align_center c2FA_info">
			
IPSCONTENT;

if ( \in_array( 'authy', $methods ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_mfa_desc_authy_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_mfa_desc_authy_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $methods ) > 1 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \count( $methods ) == 3 ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_mfa_desc_fallback_sms_or_phone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( \in_array( 'phone', $methods ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_mfa_desc_fallback_phone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_mfa_desc_fallback_sms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_mfa_desc_phone_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $methods ) == 2 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_mfa_desc_sms_or_phone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( \in_array( 'phone', $methods ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_mfa_desc_phone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_mfa_desc_sms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

if ( \in_array( 'authy', $methods ) ):
$return .= <<<IPSCONTENT

		<div class='i-text-align_center'>
			
IPSCONTENT;

$langCode = mb_substr( \IPS\Member::loggedIn()->language()->bcp47(), 0, 2 );
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/authy_ios" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener"><img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "appstores/ios/{$langCode}.svg", "core", 'global', false );
$return .= <<<IPSCONTENT
" class="i-margin-block_2"></a>
			<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/authy_android" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener"><img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "appstores/android/{$langCode}.png", "core", 'global', false );
$return .= <<<IPSCONTENT
" style="height: 60px"></a>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="i-padding_3">
		<div class='ipsSpanGrid'>
			<div class='ipsSpanGrid__5 i-text-align_center'>
				<select class="ipsInput ipsInput--select" data-sort name="countryCode">
					
IPSCONTENT;

foreach ( \IPS\Helpers\Form\Tel::$diallingCodes as $country => $codes ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $codes as $code ):
$return .= <<<IPSCONTENT

							<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $country, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-code="+
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-text="
IPSCONTENT;

$val = "country-{$country}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (+
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)" 
IPSCONTENT;

if ( $country == $countryCode or "{$country}-{$code}" == $countryCode ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "country-{$country}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (+
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</option>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</select>
			</div>
			<div class='ipsSpanGrid__7 i-text-align_center'>
				<input name="phoneNumber" type="tel" class="ipsInput ipsInput--text" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $phoneNumber, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_placeholder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" autocomplete="tel-national">
			</div>
		</div>
		
IPSCONTENT;

if ( \IPS\Widget\Request::i()->countryCode ):
$return .= <<<IPSCONTENT

			<p class="i-color_warning">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class="i-padding_3 i-background_3 ipsSpanGrid">
		
IPSCONTENT;

if ( \in_array( 'authy', $methods ) ):
$return .= <<<IPSCONTENT

			<div class="ipsSpanGrid__12">
				<button type='submit' name="method" value="authy" class='ipsButton ipsButton--primary ipsButton--wide'>
					<i class='fa-solid fa-lock'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_submit_authy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</button>
			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( 'phone', $methods ) ):
$return .= <<<IPSCONTENT

				<div class="ipsSpanGrid__
IPSCONTENT;

if ( \in_array( 'sms', $methods ) ):
$return .= <<<IPSCONTENT
6
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
12
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					<button type='submit' name="method" value="phone" class='ipsButton ipsButton--primary ipsButton--wide'>
						<i class='fa-solid fa-phone'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_submit_call', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</button>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( 'sms', $methods ) ):
$return .= <<<IPSCONTENT

				<div class="ipsSpanGrid__
IPSCONTENT;

if ( \in_array( 'phone', $methods ) ):
$return .= <<<IPSCONTENT
6
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
12
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					<button type='submit' name="method" value="sms" class='ipsButton ipsButton--primary ipsButton--wide'>
						<i class='fa-solid fa-commenting'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_submit_sms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</button>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "login", "core", 'global' )->mfaSetupOptOut(  );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function authySetupLockout( $showingMultipleForms, $lockEndTime=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elAuthy">
	<div class='i-padding_3'>
		
IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

			<h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_setup_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class="i-padding_3 ipsRichText i-text-align_center">
		
IPSCONTENT;

$sprintf = array($lockEndTime); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'authy_setup_lockout', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function googleAuthenticatorAuth( $waitUntil ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elGoogleAuthenticator" data-controller="core.global.core.googleAuth" data-waitUntil="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $waitUntil, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class="i-padding_3 ipsRichText i-text-align_center">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_auth', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
	<ul class="i-padding_3 i-background_3" data-role="codeInput">
		<li class="ipsFieldRow " id="google_authenticator_form_google_authenticator_setup_code">
			<div class="ipsFieldRow__content">
				<input type="text" name="google_authenticator_auth_code" value="" class="ipsInput ipsInput--text 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->google_authenticator_auth_code ):
$return .= <<<IPSCONTENT
ipsField_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" autocomplete="one-time-code" >
				
IPSCONTENT;

if ( \IPS\Widget\Request::i()->google_authenticator_auth_code ):
$return .= <<<IPSCONTENT

					<p class="i-color_warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_invalid_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</li>
		<li>
			<button type='submit' class='ipsButton ipsButton--primary ipsButton--wide'>
				<i class='fa-solid fa-lock'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_submit_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</button>
		</li>
	</ul>
	<div class="i-text-align_center ipsHide" data-role="codeWaiting">
		<div class="ipsProgress ipsProgress--animated">
			<div class="ipsProgress__progress" data-role="codeWaitingProgress"></div>
		</div>
		<p class="i-font-size_-2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_wait_for_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function googleAuthenticatorSetup( $qrCodeUrl, $secretKey, $showingMultipleForms ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elGoogleAuthenticator" data-controller="core.global.core.googleAuth">
	<input type="hidden" name="secret" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $secretKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div data-role="barcode">
		<div class='i-padding_3'>
			
IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

				<h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_setup_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="ipsRichText i-text-align_center c2FA_info">
				
IPSCONTENT;

if ( $showingMultipleForms ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_desc_multi', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_desc_single', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
		<div class='ipsSpanGrid'>
			<div class='ipsSpanGrid__4 i-text-align_center'>
				<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $qrCodeUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" width="150" height="150">
			</div>
			<div class='ipsSpanGrid__8 i-text-align_center'>
				
IPSCONTENT;

$langCode = mb_substr( \IPS\Member::loggedIn()->language()->bcp47(), 0, 2 );
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/googleauth_ios" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener"><img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "appstores/ios/{$langCode}.svg", "core", 'global', false );
$return .= <<<IPSCONTENT
" class="i-margin-block_2"></a>
				<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/googleauth_android" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener"><img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "appstores/android/{$langCode}.png", "core", 'global', false );
$return .= <<<IPSCONTENT
" style="height: 60px"></a>
		
				<p>
					<a class="i-cursor_pointer" data-action="showManual">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_help', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</p>
			</div>
		</div>
	</div>
	<div data-role="manual" class="ipsHide">
		<div class="i-padding_3 ipsRichText i-text-align_center">
			
IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

				<h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_setup_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="ipsRichText i-text-align_center c2FA_info">
				
IPSCONTENT;

if ( $showingMultipleForms ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_desc_multi_manual', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_desc_single_manual', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
		<div class='ipsSpanGrid'>
			<div class='ipsSpanGrid__5'>
				<i-data>
					<ul class="ipsData ipsData--table ipsData--google-auth">
						<li class="ipsData__item">
							<span class="">
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
								<em>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_account_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
							</span>
						</li>
						<li class="ipsData__item">
							<span>
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
								<code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $secretKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
							</span>
						</li>
						<li class="ipsData__item">
							<span>
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_timebased', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong><br>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'yes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</span>
						</li>
					</ul>
				</i-data>
			</div>
			<div class='ipsSpanGrid__7 i-text-align_center'>
				
IPSCONTENT;

$langCode = mb_substr( \IPS\Member::loggedIn()->language()->bcp47(), 0, 2 );
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/googleauth_ios" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener"><img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "appstores/ios/{$langCode}.svg", "core", 'global', false );
$return .= <<<IPSCONTENT
" class="i-margin-block_2"></a>
				<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/googleauth_android" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener"><img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "appstores/android/{$langCode}.png", "core", 'global', false );
$return .= <<<IPSCONTENT
" style="height: 60px"></a>
		
				<p class='i-text-align_center'>
					<a class="i-cursor_pointer" data-action="showBarcode">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_help_reset', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</p>
			</div>
		</div>
	</div>

	<ul class="i-padding_3 i-background_3">
		<li class="ipsFieldRow " id="google_authenticator_form_google_authenticator_setup_code">
			<div class="ipsFieldRow__content">
				<input type="text" name="google_authenticator_setup_code" value="" class="ipsInput ipsInput--text 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->google_authenticator_setup_code ):
$return .= <<<IPSCONTENT
ipsField_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

if ( \IPS\Widget\Request::i()->google_authenticator_setup_code ):
$return .= <<<IPSCONTENT

					<p class="i-color_warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_mfa_invalid_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</li>
		<li>
			<button type='submit' class='ipsButton ipsButton--primary ipsButton--wide'>
				<i class='fa-solid fa-lock'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'google_submit_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</button>
		</li>
	</ul>
    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "login", "core", 'global' )->mfaSetupOptOut(  );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function loginButton( $method ) {
		$return = '';
		$return .= <<<IPSCONTENT


<button type="submit" name="_processLogin" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSocial 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->buttonClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style="background-color: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->buttonColor(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

if ( $icon = $method->buttonIcon() ):
$return .= <<<IPSCONTENT

		<span class='ipsSocial__icon'>
			
IPSCONTENT;

if ( \is_string( $icon ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $icon === "x-twitter" ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
				
IPSCONTENT;

elseif ( $icon === "apple" ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg>
				
IPSCONTENT;

elseif ( $icon === "facebook-f" ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M80 299.3V512H196V299.3h86.5l18-97.8H196V166.9c0-51.7 20.3-71.5 72.7-71.5c16.3 0 29.4 .4 37 1.2V7.9C291.4 4 256.4 0 236.2 0C129.3 0 80 50.5 80 159.4v42.1H14v97.8H80z"/></svg>
				
IPSCONTENT;

elseif ( $icon === "google" ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512"><path d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"/></svg>
				
IPSCONTENT;

elseif ( $icon === "linkedin" ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M100.3 448H7.4V148.9h92.9zM53.8 108.1C24.1 108.1 0 83.5 0 53.8a53.8 53.8 0 0 1 107.6 0c0 29.7-24.1 54.3-53.8 54.3zM447.9 448h-92.7V302.4c0-34.7-.7-79.2-48.3-79.2-48.3 0-55.7 37.7-55.7 76.7V448h-92.8V148.9h89.1v40.8h1.3c12.4-23.5 42.7-48.3 87.9-48.3 94 0 111.3 61.9 111.3 142.3V448z"/></svg>
				
IPSCONTENT;

elseif ( $icon === "windows" ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M0 93.7l183.6-25.3v177.4H0V93.7zm0 324.6l183.6 25.3V268.4H0v149.9zm203.8 28L448 480V268.4H203.8v177.9zm0-380.6v180.1H448V32L203.8 65.7z"/></svg>
				
IPSCONTENT;

elseif ( $icon === "wordpress" ):
$return .= <<<IPSCONTENT

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M61.7 169.4l101.5 278C92.2 413 43.3 340.2 43.3 256c0-30.9 6.6-60.1 18.4-86.6zm337.9 75.9c0-26.3-9.4-44.5-17.5-58.7-10.8-17.5-20.9-32.4-20.9-49.9 0-19.6 14.8-37.8 35.7-37.8 .9 0 1.8 .1 2.8 .2-37.9-34.7-88.3-55.9-143.7-55.9-74.3 0-139.7 38.1-177.8 95.9 5 .2 9.7 .3 13.7 .3 22.2 0 56.7-2.7 56.7-2.7 11.5-.7 12.8 16.2 1.4 17.5 0 0-11.5 1.3-24.3 2l77.5 230.4L249.8 247l-33.1-90.8c-11.5-.7-22.3-2-22.3-2-11.5-.7-10.1-18.2 1.3-17.5 0 0 35.1 2.7 56 2.7 22.2 0 56.7-2.7 56.7-2.7 11.5-.7 12.8 16.2 1.4 17.5 0 0-11.5 1.3-24.3 2l76.9 228.7 21.2-70.9c9-29.4 16-50.5 16-68.7zm-139.9 29.3l-63.8 185.5c19.1 5.6 39.2 8.7 60.1 8.7 24.8 0 48.5-4.3 70.6-12.1-.6-.9-1.1-1.9-1.5-2.9l-65.4-179.2zm183-120.7c.9 6.8 1.4 14 1.4 21.9 0 21.6-4 45.8-16.2 76.2l-65 187.9C426.2 403 468.7 334.5 468.7 256c0-37-9.4-71.8-26-102.1zM504 256c0 136.8-111.3 248-248 248C119.2 504 8 392.7 8 256 8 119.2 119.2 8 256 8c136.7 0 248 111.2 248 248zm-11.4 0c0-130.5-106.2-236.6-236.6-236.6C125.5 19.4 19.4 125.5 19.4 256S125.6 492.6 256 492.6c130.5 0 236.6-106.1 236.6-236.6z"/></svg>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<i class='fa-brands fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->buttonIcon(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</span>
		<span class='ipsSocial__text'>
IPSCONTENT;

$val = "{$method->buttonText()}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$val = "{$method->buttonText()}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</button>
IPSCONTENT;

		return $return;
}

	function mfaAuthenticate( $screen, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elTwoFactorAuthentication' class='ipsModal' data-controller='core.global.core.2fa'>
	<div>
        
IPSCONTENT;

if ( \IPS\Dispatcher::i()->controllerLocation !== 'admin' ):
$return .= <<<IPSCONTENT

        <div class="i-margin-top_2">
            <a class="ipsButton--text ipsButton--text--light i-font-size_2" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( '_mfaCancel' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_back', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        <h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-top_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" accept-charset='utf-8' data-ipsForm class="ipsForm ipsForm_fullWidth">
			<input type="hidden" name="mfa_auth" value="1">
			<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			{$screen}
			<div class="i-background_3 i-text-align_center i-padding_3 i-padding-top_0 cOtherMethod">
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( '_mfa' => 'alt', '_mfaMethod' => '' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_try_another_method', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-angle-right'></i></a>
			</div>
		</form>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function mfaLockout( $lockEndTime=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elTwoFactorAuthentication' class='ipsModal' data-controller='core.global.core.2fa'>
	<div>
		
IPSCONTENT;

if ( \IPS\Settings::i()->mfa_lockout_behaviour == 'lock' ):
$return .= <<<IPSCONTENT

			<div class="i-padding_3 ipsRichText i-text-align_center">
				
IPSCONTENT;

$sprintf = array($lockEndTime); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_locked_out_end_time', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class="i-padding_3 ipsRichText i-text-align_center">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_locked_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</div>			
			
IPSCONTENT;

if ( \IPS\Settings::i()->mfa_lockout_behaviour == 'email' ):
$return .= <<<IPSCONTENT

				<div class="i-background_3 i-padding_3">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=mfarecovery", "front", "mfarecovery", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_recovery_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			
IPSCONTENT;

elseif ( \IPS\Settings::i()->mfa_lockout_behaviour == 'contact' ):
$return .= <<<IPSCONTENT

				<div class="i-background_3 i-padding_3">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=contact&controller=contact", "front", "contact", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_recovery_contact', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function mfaRecovery( $handlers, $url, $knownDevicesAvailable=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elTwoFactorAuthentication' class='ipsModal' data-controller='core.global.core.2fa'>
	<div>
		<h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-top_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_recover_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<p class='ipsRichText i-text-align_center c2FA_info'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_recover_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
		<div class="i-padding_3">
			<ul>
				
IPSCONTENT;

foreach ( $handlers as $key => $handler ):
$return .= <<<IPSCONTENT

					<li class="i-margin-bottom_2">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( '_mfa' => 'alt', '_mfaMethod' => $key ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--wide">
IPSCONTENT;

$val = "{$handler->recoveryButton()}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $knownDevicesAvailable ):
$return .= <<<IPSCONTENT

					<li class="i-margin-bottom_2"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( '_mfa' => 'knownDevice' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--soft ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_recovery_known_device', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \in_array( 'email', explode( ',', \IPS\Settings::i()->mfa_forgot_behaviour ) ) ):
$return .= <<<IPSCONTENT

					<li class="i-margin-bottom_2"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=mfarecovery", "front", "mfarecovery", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--text ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_recovery_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \in_array( 'contact', explode( ',', \IPS\Settings::i()->mfa_forgot_behaviour ) ) and \IPS\Member::loggedIn()->canUseContactUs() ):
$return .= <<<IPSCONTENT

					<li class="i-margin-bottom_2"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=contact&controller=contact", "front", "contact", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--text ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_recovery_contact', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function mfaSetup( $acceptableHandlers, $member, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elTwoFactorAuthentication' class='ipsModal' data-controller='core.global.core.2fa'>
	<div>
		<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" accept-charset='utf-8' data-ipsForm class="ipsForm ipsForm_fullWidth">
			<input type="hidden" name="mfa_setup" value="1">
			<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

$checked = NULL;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $acceptableHandlers ) > 1 ):
$return .= <<<IPSCONTENT

				<div class='i-background_3 i-text-align_center'>
					<div class='i-padding_3'>
						<h1 class='i-text-align_center ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_setup_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
						<p class='i-margin-top_2 i-margin-inline_auto ipsRichText c2FA_info'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_setup_multiple', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</p>
					</div>
					<i-tabs class='ipsTabs ipsTabs--small ipsTabs--stretch' id='ipsTabs_2fa' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_2fa_content'>
						<div role='tablist'>
							
IPSCONTENT;

foreach ( $acceptableHandlers as $key => $handler ):
$return .= <<<IPSCONTENT

								<button type="button" id='ipsTabs_2fa_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_2fa_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( !$checked ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

$checked = $key;
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
									<input class='ipsJS_hide' type="radio" name="mfa_method" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id="el2FARadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $checked == $key ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									
IPSCONTENT;

$val = "mfa_{$key}_title"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</button>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
						
						</div>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

					</i-tabs>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div id='ipsTabs_2fa_content' class='ipsTabs__panels'>
				
IPSCONTENT;

foreach ( $acceptableHandlers as $key => $handler ):
$return .= <<<IPSCONTENT

					<div id='ipsTabs_2fa_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_2fa_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $checked and $checked != $key ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						{$handler->configurationScreen( $member, ( \count( $acceptableHandlers ) > 1 ), $url )}
					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>

			
IPSCONTENT;

if ( \IPS\Settings::i()->mfa_required_groups != '*' and !$member->inGroup( explode( ',', \IPS\Settings::i()->mfa_required_groups ) ) ):
$return .= <<<IPSCONTENT

				<div class="i-background_3 i-text-align_center i-padding_3 i-padding-top_0 cOtherMethod">
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( '_mfa', 'optout' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text ipsButton--wide' data-confirm 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists('security_questions_opt_out_warning_value') ):
$return .= <<<IPSCONTENT
data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'security_questions_opt_out_warning_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_opt_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</form>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function mfaSetupOptOut(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->mfa_required_groups != '*' and !\IPS\Member::loggedIn()->inGroup( explode( ',', \IPS\Settings::i()->mfa_required_groups ) ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$thisUrl = \IPS\Widget\Request::i()->url();
$return .= <<<IPSCONTENT

<div class="i-background_3 i-text-align_center i-padding_3 i-padding-top_0 cOtherMethod">
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisUrl->stripQueryString(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_opt_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function oauthAuthorize( $url, $client, $scopes ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="core.global.core.framebust">
	<div class="i-text-align_center i-margin-bottom_3">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::loggedIn(), 'large' );
$return .= <<<IPSCONTENT

		<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$sprintf = array(\IPS\Settings::i()->board_name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorize_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
	</div>
	<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post">
		<input type="hidden" name="allow" value="1">
		<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<div class="ipsBox i-padding_3">
			<div class="i-font-size_2">
				
IPSCONTENT;

if ( $scopes and !$client->type ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$sprintf = array($client->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_auth_scope_title_named', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					<ul class="
IPSCONTENT;

if ( $client->choose_scopes ):
$return .= <<<IPSCONTENT
ipsFieldList
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsList ipsList--bullets
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-margin-top_3">
						
IPSCONTENT;

foreach ( $scopes as $key => $scope ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

if ( $client->choose_scopes ):
$return .= <<<IPSCONTENT

									<input id="elScope_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" type="checkbox" name="grantedScope[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" checked class="ipsInput ipsInput--toggle">
									<div class="ipsFieldList__content">
										<label for="elScope_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $scope, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</label>
									</div>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $scope, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<p class="i-text-align_center">
IPSCONTENT;

$sprintf = array($client->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_auth_no_scope', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
		<div class="ipsSubmitRow">
			<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorize_approve', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</button>
			<div class="ipsButtons i-margin-top_3">
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'prompt', 'login' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorize_switch', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'allow', 0 )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--text">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function oauthLogin( $url, $client, $scopes, $login, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="core.global.core.framebust" class='i-margin-top_3'>
	<form accept-charset='utf-8' method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

$usernamePasswordMethods = $login->usernamePasswordMethods();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $usernamePasswordMethods and $buttonMethods ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

				<div class="ipsMessage ipsMessage--error">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsColumns'>
				<div class='ipsColumns__primary'>
					<div class='
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-padding_3 ipsPull'>
						<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$sprintf = array(\IPS\Settings::i()->board_name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorize_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
						
IPSCONTENT;

if ( \IPS\Login::registrationType() != 'disabled' ):
$return .= <<<IPSCONTENT

							<p class='i-font-size_2 i-color_soft'>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dont_have_an_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'redirect' ):
$return .= <<<IPSCONTENT

									<a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_reg_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Http\Url::internal( 'oauth/authorize/?register', 'interface' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</p>
							<hr class='ipsHr i-margin-block_3'>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->loginForm( $login );
$return .= <<<IPSCONTENT

					</div>
				</div>
				<div class='ipsColumns__secondary i-basis_360'>
					<div class='
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-padding_3 ipsPull'>
						<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_faster', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						
IPSCONTENT;

if ( \count( $buttonMethods ) > 1 ):
$return .= <<<IPSCONTENT

							<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_with_these', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class='i-gap_2 i-margin-top_2'>
							
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

								<div class='i-text-align_center'>
									{$method->button()}
								</div>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
			</div>
		
IPSCONTENT;

elseif ( $usernamePasswordMethods ):
$return .= <<<IPSCONTENT

			<div class='cLogin_single'>
			
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

				<p class="ipsMessage ipsMessage--error">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class="
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-padding_3">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->loginForm( $login );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

elseif ( $buttonMethods ):
$return .= <<<IPSCONTENT

			<div class="cLogin_single">
				
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

					<p class="ipsMessage ipsMessage--error">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='i-gap_2 i-margin-top_2'>
					
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

						<div class='i-text-align_center'>
							{$method->button()}
						</div>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</form>
</div>
IPSCONTENT;

		return $return;
}

	function oauthLoginStandard( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">

IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

<ul>
	
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Password ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Password ):
$return .= <<<IPSCONTENT

					<li class='ipsFieldRow 
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT
ipsFieldRow_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $input->htmlId ):
$return .= <<<IPSCONTENT
id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<label class='ipsFieldRow__label' for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

$val = "{$input->name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $input->required ):
$return .= <<<IPSCONTENT
<span class='ipsFieldRow__required'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</label>
						<div class='ipsFieldRow__content'>
							{$input->html()}
							
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password != 'disabled' ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password == 'redirect' ):
$return .= <<<IPSCONTENT

									<a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_forgot_password_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener" class="i-font-size_-2">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=lostpass", null, "lostpassword", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Helpers\Form\Captcha::supportsModal() ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="i-font-size_-2">
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

								<br>
								<span class="i-color_warning">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				{$input->rowHtml($form)}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function securityQuestionsAuth( $question ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 ipsRichText i-text-align_center">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'security_questions_auth_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>
<ul class="i-padding_3">
	<li class="ipsFieldRow">
		<label class='ipsFieldRow__label'>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</label>
		<div class="ipsFieldRow__content">
			<input type="text" name="security_answer" autocomplete="off" class="ipsInput ipsInput--text 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->security_answer ):
$return .= <<<IPSCONTENT
ipsField_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

if ( \IPS\Widget\Request::i()->security_answer ):
$return .= <<<IPSCONTENT

				<p class="i-color_warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'security_answer_incorrect', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</li>
</ul>
<ul class="i-padding_3 i-background_3">
	<li>
		<button type='submit' class='ipsButton ipsButton--primary ipsButton--wide'>
			<i class='fa-solid fa-lock'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'security_answer_submit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</button>
	</li>
</ul>

IPSCONTENT;

		return $return;
}

	function securityQuestionsSetup( $securityQuestions, $showingMultipleForms ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

	<div class='i-padding_3'>
		<h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_setup_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<div class="ipsRichText i-text-align_center">
			
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->security_questions_number ?: 3 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'security_questions_setup_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<ul class="i-padding_3">
	
IPSCONTENT;

foreach ( range( 1, min( \IPS\Settings::i()->security_questions_number ?: 3, \count( $securityQuestions ) ) ) as $i ):
$return .= <<<IPSCONTENT

		<li class="ipsFieldRow">
			<div class="ipsFieldRow__content">
				<select class="ipsInput ipsInput--select" name="security_question[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]">
					
IPSCONTENT;

foreach ( $securityQuestions as $k => $v ):
$return .= <<<IPSCONTENT

						<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \is_array( \IPS\Widget\Request::i()->security_question ) AND \IPS\Widget\Request::i()->security_question[$i] == $k ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</select>
			</div>
		</li>
		<li class="ipsFieldRow">
			<div class="ipsFieldRow__content">
				<input type="text" class="ipsInput ipsInput--text" name="security_answer[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->security_answer[$i] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->security_answer[$i], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
			</div>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>

IPSCONTENT;

if ( \is_array( \IPS\Widget\Request::i()->security_answer ) and \count( array_filter( \IPS\Widget\Request::i()->security_answer ) ) ):
$return .= <<<IPSCONTENT

	<div class="i-padding_3 i-color_warning">
IPSCONTENT;

$pluralize = array( \IPS\Settings::i()->security_questions_number ?: 3 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'security_questions_unique', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<ul class="i-padding_3 i-background_3">
	<li>
		<button type='submit' class='ipsButton ipsButton--primary ipsButton--wide'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'security_questions_save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</button>
	</li>
</ul>

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "login", "core", 'global' )->mfaSetupOptOut(  );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function verifyAuthenticate( $method, $done, $error, $setup, $availableMethods, $url=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elVerify">
	<div class="i-padding_3 i-text-align_center">
        
IPSCONTENT;

if ( $method == 'phone' ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $done ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_call_auth_done', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_call_auth', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

elseif ( $done ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$val = "verify_{$method}_auth_done"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$val = "verify_{$method}_auth"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( $done ):
$return .= <<<IPSCONTENT

		<ul class="i-padding_3 i-background_3">
            <li class="ipsFieldRow ">
				<div class="ipsFieldRow_content cVerify_container">
					<input type="text" name="verify_auth_code" value="" class="ipsInput ipsInput--text 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->verify_auth_code ):
$return .= <<<IPSCONTENT
ipsField_error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" autocomplete="one-time-code" >
					
IPSCONTENT;

if ( \IPS\Widget\Request::i()->verify_auth_code ):
$return .= <<<IPSCONTENT

						<p class="i-color_warning">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                </div>
            </li>
			<li>
				<button type='submit' class='ipsButton ipsButton--primary ipsButton--wide i-margin-bottom_3'>
					<i class='fa-solid fa-lock'></i> &nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_submit_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</button>
			</li>
			
IPSCONTENT;

if ( $setup ):
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf()->setQueryString( '_new', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--text ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_change_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class="i-padding_3 i-background_3">
			
IPSCONTENT;

if ( \in_array( 'phone', $availableMethods ) ):
$return .= <<<IPSCONTENT

				<div class="i-margin-bottom_3">
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf()->setQueryString( 'verify_method', 'phone' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary ipsButton--wide'>
						<i class='fa-solid fa-phone'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_submit_call', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( 'sms', $availableMethods ) ):
$return .= <<<IPSCONTENT

                <div class="i-margin-bottom_3">
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf()->setQueryString( 'verify_method', 'sms' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="sms" class='ipsButton ipsButton--primary ipsButton--wide'>
						<i class='fa-solid fa-comment-dots'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_submit_sms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( \in_array( 'whatsapp', $availableMethods ) ):
$return .= <<<IPSCONTENT

            <div class="i-margin-bottom_3">
                <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->csrf()->setQueryString( 'verify_method', 'whatsapp' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="sms" class='ipsButton ipsButton--primary ipsButton--wide'>
                    <i class='fa-reguar fa-comment'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_submit_whatsapp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                </a>
            </div>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function verifyError( $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elVerify">
	<div class="i-padding_3 ipsRichText i-text-align_center">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->isAdmin() ):
$return .= <<<IPSCONTENT

			<div class="i-color_warning i-margin-top_3">
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->canUseContactUs() ):
$return .= <<<IPSCONTENT

	<div class="i-background_3 i-padding_3">
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=contact&controller=contact", "front", "contact", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_recovery_contact', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function verifySetup( $countryCode, $phoneNumber, $showingMultipleForms, $methods, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elVerify">
	<div class='i-padding_3'>
		
IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

			<h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_setup_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsRichText i-text-align_center c2FA_info">
				
IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_mfa_desc_phone_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $methods ) > 1 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_mfa_desc_sms_or_phone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( \in_array( 'phone', $methods ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_mfa_desc_phone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_mfa_desc_sms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	<div class="i-padding_3">
		<div class='ipsSpanGrid'>
			<div class='ipsSpanGrid__5 i-text-align_center'>
				<select class="ipsInput ipsInput--select" data-sort name="countryCode">
					
IPSCONTENT;

foreach ( \IPS\Helpers\Form\Tel::$diallingCodes as $country => $codes ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $codes as $code ):
$return .= <<<IPSCONTENT

							<option value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $country, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-code="+
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-text="
IPSCONTENT;

$val = "country-{$country}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (+
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)" 
IPSCONTENT;

if ( $country == $countryCode or "{$country}-{$code}" == $countryCode ):
$return .= <<<IPSCONTENT
selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$val = "country-{$country}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (+
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</option>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</select>
			</div>
			<div class='ipsSpanGrid__7 i-text-align_center'>
				<input name="phoneNumber" type="tel" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $phoneNumber, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_placeholder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" autocomplete="tel-national">
			</div>
		</div>
		
IPSCONTENT;

if ( \IPS\Widget\Request::i()->countryCode ):
$return .= <<<IPSCONTENT

			<p class="i-color_warning">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class="i-padding_3 i-background_3">
			
IPSCONTENT;

if ( \in_array( 'phone', $methods ) ):
$return .= <<<IPSCONTENT

				<div class="i-margin-bottom_3">
					<button type='submit' name="method" value="phone" class='ipsButton ipsButton--primary ipsButton--wide'>
						<i class='fa-solid fa-phone'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_submit_call', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</button>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( 'sms', $methods ) ):
$return .= <<<IPSCONTENT

				<div class="i-margin-bottom_3">
					<button type='submit' name="method" value="sms" class='ipsButton ipsButton--primary ipsButton--wide'>
						<i class='fa-solid fa-comment-dots'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_submit_sms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</button>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( \in_array( 'whatsapp', $methods ) ):
$return .= <<<IPSCONTENT

                <div class="i-margin-bottom_3">
                    <button type='submit' name="method" value="whatsapp" class='ipsButton ipsButton--primary ipsButton--wide'>
                        <i class='fa-reguar fa-comment'></i>&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_submit_whatsapp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                    </button>
                </div>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "login", "core", 'global' )->mfaSetupOptOut(  );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function verifySetupLockout( $showingMultipleForms, $lockEndTime=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elAuthy">
	<div class='i-padding_3'>
		
IPSCONTENT;

if ( !$showingMultipleForms ):
$return .= <<<IPSCONTENT

			<h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_popup_setup_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class="i-padding_3 ipsRichText i-text-align_center">
		
IPSCONTENT;

$sprintf = array($lockEndTime); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'verify_setup_lockout', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}}