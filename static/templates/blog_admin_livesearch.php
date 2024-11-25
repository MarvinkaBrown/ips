<?php
namespace IPS\Theme;
class class_blog_admin_livesearch extends \IPS\Theme\Template
{	function blog( $blog ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li data-role='result'>
	<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=blog&module=blogs&controller=blogs&subnode=1&do=form&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
</li>


IPSCONTENT;

		return $return;
}}