<?php
namespace IPS\Theme;
class class_core_admin_logs extends \IPS\Theme\Template
{	function adminLogin( $log, $request ) {
		$return = '';
		$return .= <<<IPSCONTENT

	<div class="i-background_2 i-padding_3">
		<h3 class="ipsTitle ipsTitle--h3 i-padding_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'adminloginlogs_general', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->definitionTable( $log );
$return .= <<<IPSCONTENT

		<h3 class="ipsTitle ipsTitle--h3 i-padding_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'adminloginlogs_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->definitionTable( $request );
$return .= <<<IPSCONTENT

	</div>


IPSCONTENT;

		return $return;
}

	function emailErrorBody( $log ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_3 i-padding_3'>
	<div>
		<label>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emailerrorlogs_mlog_from', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
		<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['mlog_from'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
	</div>
	<pre>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['mlog_content'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
</div>
IPSCONTENT;

		return $return;
}

	function emailErrorLog( $value, $log ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$value}

IPSCONTENT;

if ( !empty($log['mlog_smtp_log'])  ):
$return .= <<<IPSCONTENT

<a data-ipsDialog data-ipsDialog-content="#errorLog-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['mlog_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'emailerrorlog_logtitle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" href='#errorLog-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['mlog_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_full_smtp_log', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
<div class='ipsHide i-background_3 i-padding_3' id='errorLog-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['mlog_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><pre>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log['mlog_smtp_log'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre></div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}