<?php
namespace IPS\Theme;
class class_gallery_admin_livesearch extends \IPS\Theme\Template
{	function category( $category ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li data-role='result'>
	<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=categories&do=form&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
</li>


IPSCONTENT;

		return $return;
}}