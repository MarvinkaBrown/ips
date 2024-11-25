<?php
namespace IPS\Theme;
class class_cms_admin_pages extends \IPS\Theme\Template
{	function previewTemplateLink(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span data-role="viewTemplate" class='ipsButton ipsButton--inherit ipsButton--tiny'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_block_view_template', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}}