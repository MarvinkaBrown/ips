<?php
namespace IPS\Theme;
class class_calendar_admin_livesearch extends \IPS\Theme\Template
{	function calendar( $calendar ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li data-role='result'>
	<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendars&controller=calendars&do=form&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $calendar->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $calendar->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
</li>


IPSCONTENT;

		return $return;
}}