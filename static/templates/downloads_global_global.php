<?php
namespace IPS\Theme;
class class_downloads_global_global extends \IPS\Theme\Template
{	function link( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/global/global/link", "link:before", [ $file ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="link" class="">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/global/global/link", "link:inside-start", [ $file ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/global/global/link", "url:before", [ $file ] );
$return .= <<<IPSCONTENT
<a data-ips-hook="url" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-color_inherit" target="_blank" rel="noopener">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/global/global/link", "url:inside-start", [ $file ] );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/global/global/link", "url:inside-end", [ $file ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/global/global/link", "url:after", [ $file ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/global/global/link", "link:inside-end", [ $file ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/global/global/link", "link:after", [ $file ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}