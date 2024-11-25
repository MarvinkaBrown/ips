<?php
namespace IPS\Theme;
class class_gallery_admin_global extends \IPS\Theme\Template
{	function nodeMoveDeleteContent( $url, $itemLang, $number, $destination, $albumNumber, $albumDestination ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-background_1">
	<p class="i-margin-bottom_1">
		
IPSCONTENT;

if ( $destination ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$sprintf = array($number, $itemLang, $destination->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_mass_content_move_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$sprintf = array($number, $itemLang); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_mass_content_delete_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>
	<p class="i-margin-bottom_1">
		
IPSCONTENT;

if ( $albumDestination ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$sprintf = array($albumNumber, $albumDestination->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'galnode_mass_content_move_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$sprintf = array($albumNumber); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'galnode_mass_content_delete_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>
	<p class="i-margin-bottom_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_mass_content_blurb_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class="ipsSubmitRow">
	<a class="ipsButton ipsButton--primary" 
IPSCONTENT;

if ( $number OR $albumNumber ):
$return .= <<<IPSCONTENT
href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'confirm', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_mass_content_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}}