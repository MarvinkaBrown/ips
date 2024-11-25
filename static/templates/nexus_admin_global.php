<?php
namespace IPS\Theme;
class class_nexus_admin_global extends \IPS\Theme\Template
{	function userLink( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $member->member_id ):
$return .= <<<IPSCONTENT

	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $member->cm_name ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->cm_name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function vatNumber( $number ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $details = \IPS\nexus\Tax::validateVAT( $number ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $details['address'] ):
$return .= <<<IPSCONTENT

		<span title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['address'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip class="cVatNumber">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['countryCode'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['vatNumber'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['countryCode'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['vatNumber'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $number, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}