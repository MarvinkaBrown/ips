<?php
namespace IPS\Theme;
class class_core_admin_promote extends \IPS\Theme\Template
{	function groupLink( $id, $name ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=groups&id=$id", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>

IPSCONTENT;

		return $return;
}

	function internalBlurb(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3'>
	<div>
		<h2 class='ipsTitle ipsTitle--h3'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_internal_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></h2>
		<div>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_internal_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</div>
</div>

IPSCONTENT;

		return $return;
}

	function permissionBlurb( $groups ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3'>
	<div>
		<h2 class='ipsTitle ipsTitle--h3'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_permission_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></h2>
		<div>
			
IPSCONTENT;

if ( \count($groups) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$htmlsprintf = array( \IPS\Member::loggedIn()->language()->formatList($groups) );$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_permission_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'promote_permission_none_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>

IPSCONTENT;

		return $return;
}}