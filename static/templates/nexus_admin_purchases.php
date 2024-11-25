<?php
namespace IPS\Theme;
class class_nexus_admin_purchases extends \IPS\Theme\Template
{	function advertisement( $purchase, $advertisement ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsSpanGrid i-padding_2 i-text-align_center i-margin-bottom_1">
	<div class="ipsSpanGrid__6">
		<span class="i-font-size_6">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->impressions, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $advertisement->maximum_unit == 'i' and $advertisement->maximum_value != -1 ):
$return .= <<<IPSCONTENT
 / 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->maximum_value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		<br>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'impressions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
	<div class="ipsSpanGrid__6">
		<span class="i-font-size_6">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->clicks, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $advertisement->maximum_unit == 'c' and $advertisement->maximum_value != -1 ):
$return .= <<<IPSCONTENT
 / 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->maximum_value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		<br>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clicks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
</div>
<div class="ips
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\IPS::mb_ucfirst(\IPS\SUITE_UNIQUE_KEY), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

$images = array_filter( json_decode( $advertisement->images, TRUE ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$link = \IPS\Http\Url::external( $advertisement->link );
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

if ( \count( $images ) > 1 ):
$return .= <<<IPSCONTENT

		<i-tabs class='ipsTabs acpFormTabBar' id='ipsTabs_adView' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_adView_content' data-ipsTabBar-updateURL='false'>
			<div role='tablist'>
				
IPSCONTENT;

foreach ( $images as $key => $image ):
$return .= <<<IPSCONTENT

					<button type="button" id='ipsTabs_adView_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsTabs__tab" title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' role="tab" aria-controls='ipsTabs_adView_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' aria-selected="
IPSCONTENT;

if ( $key == "large" ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

if ( $key === 'large' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ad_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "ad_image_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
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
		<section id='ipsTabs_adView_content' class='acpFormTabContent'>
			
IPSCONTENT;

foreach ( $images as $key => $image ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_adView_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_adView_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $key != "large" ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<div class="i-padding_3">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target='_blank' rel='nofollow noreferrer'><img src="
IPSCONTENT;

$return .= \IPS\File::get( $advertisement->storageExtension(), $image )->url;
$return .= <<<IPSCONTENT
" class="ipsImage"></a>
					</div>
				</div>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</section>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class="i-padding_3">
			
IPSCONTENT;

foreach ( $images as $key => $image ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target='_blank' rel='nofollow noreferrer'><img src="
IPSCONTENT;

$return .= \IPS\File::get( $advertisement->storageExtension(), $image )->url;
$return .= <<<IPSCONTENT
" class="ipsImage"></a>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="i-font-size_-1"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="noreferrer">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $advertisement->link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
</div>
IPSCONTENT;

		return $return;
}

	function giftVoucher( $purchase, $data ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3 i-background_2'>
	<div class='ipsColumns'>
		<div class='ipsColumns__secondary i-basis_280'>
			<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gift_voucher', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<p class='i-font-size_2 cNexusGiftCardPrice'>
				
IPSCONTENT;

$return .= new \IPS\nexus\Money( $data['amount'], $data['currency'] );
$return .= <<<IPSCONTENT

			</p>	
			<div class='i-font-size_2'>
				<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['code'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
			</div>
			<p class='i-font-size_1'>
				
IPSCONTENT;

if ( $data['method'] === 'email' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sent_to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['recipient_email'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->url()->setQueryString( 'do', 'extra' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'printable_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		</div>
		<div class='ipsColumns__primary i-background_1'>
			<div class='i-padding_3 i-font-size_1'>
				
IPSCONTENT;

if ( $data['recipient_name'] ):
$return .= <<<IPSCONTENT

					<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['recipient_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
,</strong>
					<br>
					<br>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $data['message'] ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= nl2br( htmlspecialchars( $data['message'], ENT_DISALLOWED, 'UTF-8', FALSE ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $data['sender'] ):
$return .= <<<IPSCONTENT

					<br><br>
					<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['sender'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
				
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

	function hovercard( $purchase ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsPageHead_special">
	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 (#
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
</div>
<i-data>
	<ul class="ipsData ipsData--table ipsData--purchases-hovercard">
		
IPSCONTENT;

foreach ( $purchase->custom_fields as $k => $v ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $displayValue = trim( \IPS\nexus\Package\CustomField::load( $k )->displayValue( $v, TRUE ) ) ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<span class=""><strong>
IPSCONTENT;

$val = "nexus_pfield_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
					<span class="">{$displayValue}</span>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function link( $purchase, $number=FALSE, $icon=FALSE, $shortName=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $icon ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-box-archive"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsHover data-ipsHover-target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->acpUrl()->setQueryString( 'hovercard', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $number ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($purchase->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $shortName ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function lkey( $licensekey ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3 i-background_2 i-text-align_center i-margin-bottom_1'>
	<h2 class='ipsMinorTitle i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='i-font-size_2 i-font-family_monospace cNexusLicenseKey i-margin-bottom_1'>
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $licensekey->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( $licensekey->max_uses != 0 ):
$return .= <<<IPSCONTENT

		<strong>
			
IPSCONTENT;

if ( $licensekey->max_uses == -1 ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$sprintf = array($licensekey->uses); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'lkey_using_x_of_unlimited_uses', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$sprintf = array($licensekey->uses, $licensekey->max_uses); $pluralize = array( $licensekey->max_uses ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'lkey_using_x_of_x_uses', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</strong>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'lkeys_reset' ) || $licensekey->uses ):
$return .= <<<IPSCONTENT

		<ul class='ipsList ipsList--inline i-margin-top_1'>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'lkeys_reset' ) ):
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $licensekey->purchase->acpUrl()->setQueryString( array( 'do' => 'extra', 'act' => 'lkeyReset' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm class="ipsButton ipsButton--inherit ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'generate_new_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $licensekey->uses ):
$return .= <<<IPSCONTENT

				<li>
					<a href='#elLicenseUses' class='ipsButton ipsButton--tiny ipsButton--inherit' data-ipsDialog data-ipsDialog-content='#elLicenseUses' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_key_uses', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_license_usage', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>


IPSCONTENT;

if ( $licensekey->uses ):
$return .= <<<IPSCONTENT

	<div id='elLicenseUses' class='ipsHide i-padding_3'>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--lkey">
				
IPSCONTENT;

foreach ( $licensekey->activate_data as $k => $use ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<div class='ipsData__icon'>
							<span class='ipsBadge ipsBadge--icon ipsBadge--neutral'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						</div>
						<div class='ipsData__main'>
							
IPSCONTENT;

if ( \is_countable( $use['extra'] ) AND \count( $use['extra'] ) ):
$return .= <<<IPSCONTENT

								<ul>
									
IPSCONTENT;

foreach ( $use['extra'] as $k => $v ):
$return .= <<<IPSCONTENT

										<li>
											<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
:</strong> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<div class='ipsData__meta'>
								
IPSCONTENT;

if ( $use['last_checked'] ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $use['activated'] )->html( false ), htmlspecialchars( $use['ip'], ENT_DISALLOWED, 'UTF-8', FALSE ), \IPS\DateTime::ts( $use['last_checked'] )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'lkey_use_info', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $use['activated'] )->html( false ), htmlspecialchars( $use['ip'], ENT_DISALLOWED, 'UTF-8', FALSE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'lkey_use_info_nocheck', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function noLkey( $purchase ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3 i-background_2 i-text-align_center i-margin-bottom_1'>
	<h2 class='ipsMinorTitle i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='i-font-size_2 i-margin-bottom_1'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_license_key_generated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'lkeys_reset' ) || ( $purchase->licenseKey() and $purchase->licenseKey()->uses ) ):
$return .= <<<IPSCONTENT

		<ul class='ipsList ipsList--inline i-margin-top_1'>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'lkeys_reset' ) ):
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->acpUrl()->setQueryString( array( 'do' => 'extra', 'act' => 'lkeyReset' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm class="ipsButton ipsButton--inherit ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'generate_new_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function view( $purchase, $customer, $children, $customFields ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $billingAgreement = $purchase->billing_agreement AND !$purchase->billing_agreement->canceled ):
$return .= <<<IPSCONTENT

	<p class="ipsMessage ipsMessage--info">
IPSCONTENT;

$sprintf = array($billingAgreement->acpUrl()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_purchase_info', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsSpanGrid" data-ips-template="view">
	<div class='ipsSpanGrid__4'>		
		
IPSCONTENT;

if ( $purchase->cancelled ):
$return .= <<<IPSCONTENT

			<p class='i-background_negative i-padding_3 i-text-align_center i-margin-bottom_3'>
				<span class='ipsTitle ipsTitle--h3'><i class='fa-solid fa-xmark-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				<br>
				
IPSCONTENT;

if ( $purchase->can_reactivate ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_canceled_ra', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_canceled_no_ra', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

elseif ( !$purchase->active ):
$return .= <<<IPSCONTENT

			<p class='i-background_3 i-padding_3 i-text-align_center i-margin-bottom_3'>
				<span class='ipsTitle ipsTitle--h3'><i class='fa-solid fa-circle-exclamation'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</p>
		
IPSCONTENT;

elseif ( $purchase->expire ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $purchase->grace_period and $purchase->expire->getTimestamp() < time() ):
$return .= <<<IPSCONTENT

				<p class='i-background_3 i-padding_3 i-text-align_center i-margin-bottom_3'>
					<span class='ipsTitle ipsTitle--h3'><i class='fa-regular fa-clock'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_in_grace_period', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</p>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<p class='i-background_positive i-padding_3 i-text-align_center i-margin-bottom_3'>
					<span class='ipsTitle ipsTitle--h3'><i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div class="ipsBox">
			<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<i-data>
				<ul class="ipsData ipsData--table ipsData--purchase-details">
					<li class="ipsData__item">
						<span class="i-basis_120">
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						</span>
						<span class="">
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->start->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</span>
					</li>
					
IPSCONTENT;

if ( $purchase->expire ):
$return .= <<<IPSCONTENT

						<li class="ipsData__item">
							<span class="i-basis_120">
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_expire', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</span>
							<span class="">
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->expire->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</span>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $purchase->grace_period ):
$return .= <<<IPSCONTENT

						<li class="ipsData__item">
							<span class="i-basis_120">
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'p_grace_period', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</span>
							<span class="">
								
IPSCONTENT;

$grace = new \DateInterval( 'PT' . $purchase->grace_period . 'S' );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::formatInterval( \IPS\DateTime::create()->diff( \IPS\DateTime::create()->add( $grace ) ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $purchase->expire ):
$return .= <<<IPSCONTENT

									<span class="i-color_soft i-font-size_-1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->expire->add( $grace )->localeDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</span>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $purchase->renewals ):
$return .= <<<IPSCONTENT

						<li class="ipsData__item">
							<span class="i-basis_120">
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_renewals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</span>
							<span class="">
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->renewals, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $purchase->renewals->tax ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($purchase->renewals->tax->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'plus_tax_rate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $purchase->grouped_renewals ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_grouped', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $purchase->pay_to and $purchase->commission < 100 ):
$return .= <<<IPSCONTENT

									<br>
									
IPSCONTENT;

if ( $purchase->fee ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(100 - $purchase->commission, $purchase->fee, $purchase->pay_to->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renewal_commission_fee', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(100 - $purchase->commission, $purchase->pay_to->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_renewal_commission', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</span>
						</li>
						
IPSCONTENT;

if ( $purchase->billing_agreement ):
$return .= <<<IPSCONTENT

							<li class="ipsData__item">
								<span class="i-basis_120">
									<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_billing_agreement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								</span>
								<span class="">
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->billing_agreement->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->billing_agreement->gw_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

if ( $purchase->billing_agreement->canceled ):
$return .= <<<IPSCONTENT

										(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'billing_agreement_canceled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $parent = $purchase->parent() ):
$return .= <<<IPSCONTENT

						<li class="ipsData__item">
							<span class="i-basis_120">
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ps_parent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</span>
							<span class="">
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $parent->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $parent->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							</span>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
		</div>
		<br>
		
		<div class="ipsBox">
			<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_customer_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

if ( $customer ):
$return .= <<<IPSCONTENT

				<div class='i-padding_3 ipsPhotoPanel ipsPhotoPanel_small'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsUserPhoto ipsUserPhoto--small' loading="lazy" alt=""></a>
					<div>
						<h3 class='i-font-size_2 i-link-color_inherit'><strong><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></strong></h3>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
						
IPSCONTENT;

$sprintf = array($purchase->member->joined->localeDate()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_customer_since', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
<br>
						
IPSCONTENT;

$sprintf = array($purchase->member->totalSpent()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'transaction_spent', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</div>
				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class='i-padding_3'>
					<p class="ipsMessage ipsMessage--error">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchase_no_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<br><br>
	</div>
	<div class='ipsSpanGrid__8'>
		
IPSCONTENT;

if ( $content = $purchase->acpPage() ):
$return .= <<<IPSCONTENT

			{$content}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( \count( $customFields ) ):
$return .= <<<IPSCONTENT

			<div class="ipsBox">
				<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'p_custom_fields', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<i-data>
					<ul class="ipsData ipsData--table ipsData--customfields">
						
IPSCONTENT;

foreach ( $customFields as $k => $v ):
$return .= <<<IPSCONTENT

							<li class="ipsData__item">
								<span class="i-basis_120">
									<strong>
IPSCONTENT;

$val = "nexus_pfield_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								</span>
								<span class="">
									{$v}
								</span>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</i-data>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( $purchase->childrenCount() ):
$return .= <<<IPSCONTENT

		<div class='ipsBox'>
			<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'child_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			{$children}
		</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div data-controller='core.admin.members.lazyLoadingProfileBlock' data-url='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=customers&controller=purchases&do=showInvoices&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $purchase->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<div class="i-margin-bottom_1 i-text-align_center">
				<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "tiny_loading.gif", "core", 'front', false );
$return .= <<<IPSCONTENT
">
			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}}