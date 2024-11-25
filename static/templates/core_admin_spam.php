<?php
namespace IPS\Theme;
class class_core_admin_spam extends \IPS\Theme\Template
{	function spamGeoSettings( $matrix ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\in_array( \IPS\Login::registrationType(), array( 'normal', 'full' ) ) OR !\IPS\Settings::i()->ipsgeoip ):
$return .= <<<IPSCONTENT

<p class='ipsMessage ipsMessage--warning i-margin_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'geolocation_settings_requirements', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<p class='ipsMessage ipsMessage--info i-margin_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'geolocation_settings_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
{$matrix}
IPSCONTENT;

		return $return;
}

	function spamQandASettings( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\in_array( \IPS\Login::registrationType(), array( 'normal', 'full' ) ) ):
$return .= <<<IPSCONTENT

<p class='ipsMessage ipsMessage--warning i-margin_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'qanda_settings_requirements', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


{$table}
IPSCONTENT;

		return $return;
}}