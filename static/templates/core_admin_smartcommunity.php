<?php
namespace IPS\Theme;
class class_core_admin_smartcommunity extends \IPS\Theme\Template
{	function cloud(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!-- Top bit -->
<div class="i-padding_3 ipsBox i-margin-bottom_2">
    <h3 class="ipsTitle ipsTitle--h3">
        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cloud_smart_community_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </h3>
    <p class="i-color_soft">
        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cloud_smart_community_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </p>
</div>
<div class="ipsGrid i-basis_320">
    <!-- IMAGE SCANNER -->
	<div class="ipsSpanGrid__6">
        <div class="">
            <div class="ipsBox acpEnhancement">
                <div class="acpEnhancement__title">
                    <h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cloud_ai_service_imagescan', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <span class="ipsBadge ipsBadge--neutral i-float_end">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'only_available_on_cloud', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></h3>
                </div>
                <div class="ipsRichText">
                    <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cloud_ai_service_imagescan_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                </div>
            </div>
        </div>
    </div>
    <!-- TRENDING -->
    <div class="ipsSpanGrid__6">
        <div class="">
            <div class="ipsBox acpEnhancement">
                <div class="acpEnhancement__title">
                    <h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cloud_ai_service_trending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp; <span class="ipsBadge ipsBadge--neutral i-float_end">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'only_available_on_cloud', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></h3>
                </div>
                <div class="ipsRichText">
                    <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cloud_ai_service_trending_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                </div>
            </div>
        </div>
    </div>
    <!-- REALTIME -->
    <div class="ipsSpanGrid__6">
        <div class="">
            <div class="ipsBox acpEnhancement">
                <div class="acpEnhancement__title">
                    <h3>
                        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cloud_ai_service_realtime', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp; <span class="ipsBadge ipsBadge--neutral i-float_end">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'only_available_on_cloud', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                    </h3>
                </div>
                <div class="ipsRichText">
                    <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cloud_ai_service_realtime_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div>
	<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/switch_to_cloud" );
$return .= <<<IPSCONTENT
" target="_blank" class="ipsButton ipsButton--wide ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'learn_more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}}