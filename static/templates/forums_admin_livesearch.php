<?php
namespace IPS\Theme;
class class_forums_admin_livesearch extends \IPS\Theme\Template
{	function forum( $forum ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li data-role='result'>
	<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=forums&module=forums&controller=forums&do=form&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
</li>


IPSCONTENT;

		return $return;
}}