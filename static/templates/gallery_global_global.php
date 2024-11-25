<?php
namespace IPS\Theme;
class class_gallery_global_global extends \IPS\Theme\Template
{	function link( $image ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class=''><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-color_inherit" target="_blank" rel='noopener'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
IPSCONTENT;

		return $return;
}}