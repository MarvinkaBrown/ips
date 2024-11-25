<?php
namespace IPS\Theme;
class class_core_admin_system extends \IPS\Theme\Template
{	function backgroundProcessesRunNow(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox'>
	<div class="i-padding_3">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'background_process_run_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		<p>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=background&do=process" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'background_process_run_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function login( $login, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset(\IPS\Widget\Request::i()->cookie['acptheme']) AND \IPS\Widget\Request::i()->cookie['acptheme'] == 'dark' ):
$return .= <<<IPSCONTENT
data-ips-scheme="dark"
IPSCONTENT;

elseif ( !isset(\IPS\Widget\Request::i()->cookie['acptheme']) AND isset(\IPS\Widget\Request::i()->cookie['acpthemedefault']) AND \IPS\Widget\Request::i()->cookie['acpthemedefault'] == 'dark' ):
$return .= <<<IPSCONTENT
data-ips-scheme="dark"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-scheme="light"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<head>
		<meta charset="utf-8">
		<title>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'login', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</title>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT

		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	</head>
	<body class='ipsApp ipsApp_admin' id='elLogin' data-controller="core.admin.core.app">
		<div id='elLogin_box' class="ipsBox" data-controller="core.admin.system.login">
			<form accept-charset='utf-8' method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				<div data-role="loginForms">
					<div class='cAcpLoginBox'>
						<div class='cAcpLoginBox_logo'>
							<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logo_dark_full.png", "core", 'admin', false );
$return .= <<<IPSCONTENT
' alt=''>
						</div>
						<div class="">
							
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

								<div class='ipsMessage ipsMessage--error'>
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$usernamePasswordMethods = $login->usernamePasswordMethods();
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $usernamePasswordMethods ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->loginForm( $login );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $buttonMethods ):
$return .= <<<IPSCONTENT

									<hr class="ipsHr">
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $buttonMethods ):
$return .= <<<IPSCONTENT

								<ul class='ipsForm ipsForm--vertical ipsForm--admin-login'>
									
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

										<li class='ipsFieldRow--fullWidth i-margin-top_1'>
											{$method->button()}
										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
			</form>
		</div>
	</body>
</html>
IPSCONTENT;

		return $return;
}

	function loginForm( $login ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsForm ipsForm--vertical ipsForm--admin-login-form'>
	<li class="ipsFieldRow ipsFieldRow--fullWidth">
		<div class="ipsFieldRow__content">
			<input class="ipsInput" autofocus type="email" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" name="auth" id="auth" 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->auth ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->auth ) ? htmlspecialchars( \IPS\Widget\Request::i()->auth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 autocomplete="email">
		</div>
	</li>
	<li class="ipsFieldRow ipsFieldRow--fullWidth">
		<div class="ipsFieldRow__content">
			<input class="ipsInput" type="password" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" name="password" id="password" 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->password ) ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->password ) ? htmlspecialchars( \IPS\Widget\Request::i()->password, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 autocomplete="current-password">
		</div>
	</li>
	<li class='ipsFieldRow ipsFieldRow--fullWidth'>
		<button type='submit' name="_processLogin" value="usernamepassword" class='ipsButton ipsButton--primary'><i class='fa-solid fa-lock'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'login', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</li>
</ul>

IPSCONTENT;

		return $return;
}

	function manualUpgradeRequired( $link=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="i-margin-bottom_1">
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	</div>
	<div class="ipsRichText i-margin-bottom_1">
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_manual', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/client_area" );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--large" target="_blank" rel="noopener">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_go_to_clientarea', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_manual_footer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>
</div>
<div class="ipsSubmitRow">
	<a href="upgrade" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function mfaLogin( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!DOCTYPE html>
<html lang="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->bcp47(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" dir="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->isrtl ):
$return .= <<<IPSCONTENT
rtl
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ltr
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<head>
		<meta charset="utf-8">
		<title>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'login', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</title>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeCSS(  );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->includeJS(  );
$return .= <<<IPSCONTENT

		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	</head>
	<body class='ipsApp ipsApp_admin' id='elLogin' data-controller="core.admin.core.app">
		<div id='elLogin_box' data-controller="core.admin.system.login" class='ipsBox elLogin_single'>
			<div data-role="loginForms">
				<div class='cAcpLoginBox'>
					<div id='elTabContent'>
						{$form}
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
IPSCONTENT;

		return $return;
}

	function systemLogFileView( $contents ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-margin-bottom_1">
	<textarea class="ipsInput ipsInput--text" rows="40" style="font-family: monospace;">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contents, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
</div>
IPSCONTENT;

		return $return;
}

	function systemLogView( $log ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-margin-bottom_3">
	<div class='i-flex i-align-items_center ipsGap'>
		<div class='i-flex_00'>
			<span class='ipsUserPhoto ipsUserPhoto--medium'>
				<img src='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $log->member_id )->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt=''>
			</span>
		</div>
		<div class='i-flex_11'>
			<h1>
				
IPSCONTENT;

if ( $log->member_id ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title='
IPSCONTENT;

$sprintf = array(\IPS\Member::load($log->member_id)->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'triggered_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $log->member_id )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'triggered_guest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				(
IPSCONTENT;

$return .= \IPS\Member\Group::load( \IPS\Member::load( $log->member_id )->member_group_id )->formattedName;
$return .= <<<IPSCONTENT
)
			</h1>
			<div>
IPSCONTENT;

$val = ( $log->time instanceof \IPS\DateTime ) ? $log->time : \IPS\DateTime::ts( $log->time );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
			<div>
				<div class='ipsTruncate'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'triggered_at', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $log->url ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel="noopener" title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<em>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'log_missing_data', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
	</div>
</div>
<div class="i-margin-bottom_3">
	
IPSCONTENT;

if ( $log->exception_class ):
$return .= <<<IPSCONTENT

		<code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log->exception_class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
::
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log->exception_code, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code><br>
		<br>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<textarea class="ipsInput ipsInput--text" rows="15" style="font-family: monospace;">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log->message, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
</div>
<div class="i-margin-bottom_3">
	<h3 class="ipsMinorTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'log_backtrace', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	<textarea class="ipsInput ipsInput--text" rows="8" style="font-family: monospace;">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $log->backtrace, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</textarea>
</div>

IPSCONTENT;

if ( $log->loaded_hooks ):
$return .= <<<IPSCONTENT

<div class="i-margin-bottom_3">
	<h3 class="ipsMinorTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'log_loaded_hooks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	<ul>
		
IPSCONTENT;

foreach ( $log->loaded_hooks as $hook ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$details = \IPS\Log::hookDetails( $hook );
$return .= <<<IPSCONTENT

		<li><a href='
IPSCONTENT;

if ( $details['type'] == 'app' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&do=details&id={$details['id']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'app_view_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $hook, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function upgradeDeltaDatabaseCheck( $version ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="i-margin-bottom_1">
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_database_check_fail_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	</div>
	<div class="i-margin-bottom_1">
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_database_check_fail_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>
</div>
<div class="ipsSubmitRow">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&do=databaseChecker&_upgradeVersion={$version}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'self_service', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'help_me_fix_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function upgradeDeltaFailed( $error, $deltaDownloadUrl ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="i-margin-bottom_1">
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	</div>
	<div class="ipsRichText i-margin-bottom_1">
		
IPSCONTENT;

if ( $error == 'ftp' ):
$return .= <<<IPSCONTENT

			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_manual_ftp', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

elseif ( $error == 'unexpected_response' ):
$return .= <<<IPSCONTENT

			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_fail_server', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

elseif ( $error == 'exception' ):
$return .= <<<IPSCONTENT

			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_fail_client', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_manual', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $deltaDownloadUrl ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $deltaDownloadUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--large"><i class="fa-solid fa-cloud-arrow-down"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			<p><a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/client_area" );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_download_full', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></p>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/client_area" );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--large" target="_blank" rel="noopener">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_go_to_clientarea', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_manual_footer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>
</div>
<div class="ipsSubmitRow">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&check=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>

IPSCONTENT;

		return $return;
}

	function upgradeDeltaFailedCic(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.admin.system.autoupgradetimer'>
	<div class="i-padding_3">
		<div class="i-margin-bottom_1">
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_fail_cic_head', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		</div>
		<div class="i-margin-bottom_1">
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_fail_cic_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
	</div>
	<div class="ipsSubmitRow">
		<span data-role="counter-wrapper">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_fail_cic_cont', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		<span data-role="continue-button" class='ipsJS_hide'><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&check=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></span>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function upgradeDeltaFtp( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
IPSCONTENT;

$sprintf = array(\IPS\Http\Url::internal('app=core&module=system&controller=upgrade&manual=1')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_ftp_instructions', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
{$form}
IPSCONTENT;

		return $return;
}

	function upgradeDeltaLargeTables( $version, $largeTables ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post">
	<input type="hidden" name="version" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="select_version_submitted" value="1">
	<input type="hidden" name="skip_md5_check" value="1">
    <input type="hidden" name="skip_resource_check" value="1">
	<input type="hidden" name="skip_theme_check" value="1">
	<input type="hidden" name="skip_large_tables_check" value="1">
	<div class="i-padding_3">
		<div class="i-margin-bottom_1">
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_initial_large_tables_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		</div>
		<div class="i-margin-bottom_1">
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_initial_large_tables_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
		<table class="ipsTable">
			<thead>
				<tr>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sql_table_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sql_table_rows', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sql_table_size', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				</tr>
			</thead>
			<tbody>
				
IPSCONTENT;

foreach ( $largeTables as $table ):
$return .= <<<IPSCONTENT

					<tr>
						<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
						<td>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( \IPS\Db::i()->cachedTableData[ $table ]['rows'] );
$return .= <<<IPSCONTENT
</td>
						<td>
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( \IPS\Db::i()->cachedTableData[ $table ]['size'] );
$return .= <<<IPSCONTENT
</td>
					</tr>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</tbody>
		</table>
	</div>
	<div class="ipsSubmitRow">
		<input type="submit" class="ipsButton ipsButton--primary" name="skip_theme_check" value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function upgradeDeltaManualQuery( $mr, $query ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="core.admin.system.upgradeManualQuery" data-url="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&mr={$mr}&runQuery=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
	<div class="i-padding_3">
		<div class="i-margin-bottom_1">
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		</div>
		<p class="i-margin-bottom_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_delta_manual_query', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<div class="ipsCode i-margin-bottom_1" data-ipsCopy>
			<code class="prettyprint lang-sql" data-role="copyTarget">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $query, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
			<button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny i-float_end ipsHide" data-role="copyButton" data-clipboard-text="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $query, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</div>
	<div class="ipsSubmitRow" data-role="querySuccessButtons">
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&mr={$mr}&query_has_been_ran=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary" data-action="redirectContinue">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_delta_manual_query_done', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		<div class="ipsHide" data-role="runManualButton">
			&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp;
			<button type="button" class="ipsButton ipsButton--secondary" data-action="runQuery">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_delta_manual_query_auto', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function upgradeDeltaMd5( $version, $files ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post">
	<input type="hidden" name="version" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="select_version_submitted" value="1">
	<input type="hidden" name="skip_md5_check" value="1">
	<div class="i-padding_3">
		<div class="i-margin-bottom_1">
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_initial_md5_fail_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		</div>
		<div class="i-margin-bottom_1">
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_initial_md5_fail_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<ul>
				
IPSCONTENT;

foreach ( $files as $file ):
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	</div>
	<div class="ipsSubmitRow">
		<input type="submit" class="ipsButton ipsButton--primary" value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function upgradeDeltaQueryFailed( $mr, $e ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="i-margin-bottom_1">
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_delta_query_fail_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	</div>
	<p class="i-margin-bottom_1">
		
IPSCONTENT;

if ( \IPS\CIC ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_delta_query_fail_cic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_delta_query_fail_nocic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>
	<div class="ipsMessage ipsMessage--error">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $e->getMessage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
	<div class="ipsCode i-margin-bottom_1" data-ipsCopy>
		<code class="prettyprint lang-sql" data-role="copyTarget">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Db::_replaceBinds( $e->query, $e->binds ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
		<button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny i-float_end ipsHide" data-role="copyButton" data-clipboard-text="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Db::_replaceBinds( $e->query, $e->binds ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>	
</div>
<div class="ipsSubmitRow" data-role="querySuccessButtons">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&mr={$mr}&query_has_been_ran=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary" data-action="redirectContinue">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_anyway', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function upgradeDeltaResourceIssues( $version, $resources ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post">
	<input type="hidden" name="version" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="select_version_submitted" value="1">
	<input type="hidden" name="skip_md5_check" value="1">
	<input type="hidden" name="skip_resource_check" value="1">
	<div class="i-padding_3">
        <div class="i-margin-bottom_1">
            <h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_initial_resource_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
        </div>
        <div class="i-margin-bottom_1">
            <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_initial_resource_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
        </div>
        
IPSCONTENT;

foreach ( $resources as $type => $res ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( \count( $res ) ):
$return .= <<<IPSCONTENT

        <h3>
IPSCONTENT;

$val = "download_upgrade_initial_resource_upgrade_{$type}"; $sprintf = array(\count($res)); $pluralize = array( \count($res) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h3>
        
IPSCONTENT;

$val = "download_upgrade_initial_resource_upgrade_{$type}_blurb"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

        <ul class="i-flex i-align-items_center i-gap_2 i-flex-wrap_wrap i-margin-top_1">
        
IPSCONTENT;

foreach ( $res AS $r  ):
$return .= <<<IPSCONTENT

            <li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        </ul>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	<div class="ipsSubmitRow">
		<input type="submit" class="ipsButton ipsButton--primary" name="skip_resource_check" value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
	</div>
</form>

IPSCONTENT;

		return $return;
}

	function upgradeDeltaThemeConflicts( $version, $conflicts ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post">
	<input type="hidden" name="version" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $version, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="select_version_submitted" value="1">
	<input type="hidden" name="skip_md5_check" value="1">
    <input type="hidden" name="skip_resource_check" value="1">
	<input type="hidden" name="skip_theme_check" value="1">
	<div class="i-padding_3">
		<div class="i-margin-bottom_1">
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$pluralize = array( \count($conflicts) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_initial_theme_conflicts_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
		</div>
		<div class="i-margin-bottom_1">
			<p>
IPSCONTENT;

$pluralize = array( \count($conflicts) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_initial_theme_conflicts_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</p>
		</div>
		
IPSCONTENT;

foreach ( $conflicts as $themeId => $categories ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$theme = \IPS\Theme::load( $themeId );
$return .= <<<IPSCONTENT

			<div class="i-margin-bottom_1">
				<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $theme->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
				<ul>
					
IPSCONTENT;

if ( isset( $categories['html'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $categories['html'] as $templateName => $templateId ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

list( $app, $location, $group, $name ) = explode( '/', $templateName );
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$themeId}&t_type=templates&t_app={$app}&t_location={$location}&t_group={$group}&t_name={$name}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &rarr; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &rarr; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &rarr; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $categories['css'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $categories['css'] as $cssName => $cssId ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

list( $app, $location, $group, $name ) = explode( '/', $cssName );
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$themeId}&t_type=css&t_app={$app}&t_location={$location}&t_group={$group}&t_name={$name}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &rarr; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &rarr; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &rarr; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
	
		<p class="i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_upgrade_initial_theme_conflicts_tip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>	
	</div>
	<div class="ipsSubmitRow">
		<input type="submit" class="ipsButton ipsButton--primary" name="skip_theme_check" value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function upgradeExtract( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-text-align_center">
	<iframe src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" width="100%" style="border:0; height:50px; margin:0"></iframe>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_extracting', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function upgradeExtractCic( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-text-align_center">
	<iframe src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" width="100%" style="border:0; height:200px; margin:0" scrolling="no"></iframe>
	<p class='i-color_soft'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_applycic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>
</div>
IPSCONTENT;

		return $return;
}

	function upgradeExtractCic2(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.admin.system.autoupgradetimer'>
	<div class="i-padding_3">
		<div class="i-margin-bottom_1">
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_cic2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		</div>
		<div class="i-margin-bottom_1">
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_cic2_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
	</div>
	<div class="ipsSubmitRow">
		<span data-role="counter-wrapper">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delta_upgrade_cic2_cont', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		<span data-role="continue-button" class='ipsJS_hide'><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&check=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></span>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function upgradeFinished( $databaseErrors ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	
	<p class="ipsMessage ipsMessage--success">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_complete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>
	
IPSCONTENT;

if ( $databaseErrors ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--warning">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_complete_database_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br>
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=upgrade&do=databaseChecker" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'self_service', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--tiny i-margin-top_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'help_me_fix_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function upgradeManagedContactUs( $supportEmail ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 ipsBox">
	<h2 class="ipsTitle ipsTitle--h3 i-text-align_center">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'managed_upgrade_block_head', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class="i-padding_3 i-margin-top_1 i-margin-bottom_1 i-text-align_center"><img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "machine.png", "core", 'admin', false );
$return .= <<<IPSCONTENT
'></div>
	<p class="i-font-size_2 i-text-align_center">
IPSCONTENT;

$sprintf = array($supportEmail, $supportEmail); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'managed_upgrade_block_msg', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
</div>
IPSCONTENT;

		return $return;
}

	function upgradeSelectVersion( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\CIC AND \IPS\Cicloud\isManaged() ):
$return .= <<<IPSCONTENT

	<h2 class="ipsTitle ipsTitle--h3 i-text-align_center i-padding_3">
IPSCONTENT;

$sprintf = array(\IPS\Cicloud\managedSupportEmail()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'managed_upgrade_allow_head', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h2>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $input->options['options'] ) > 1 ):
$return .= <<<IPSCONTENT

			<div class="i-background_3 i-padding_3">
				<div class="i-margin-bottom_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_choose_version', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $input->options['options'] as $longVersion => $humanVersion ):
$return .= <<<IPSCONTENT

			<form accept-charset='utf-8' action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post">
				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
				
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \is_array($v) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $v as $_k => $_v ):
$return .= <<<IPSCONTENT

							<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<input type="hidden" name="version" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $longVersion, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				
				
IPSCONTENT;

if ( \count( $input->options['options'] ) > 1 ):
$return .= <<<IPSCONTENT

					<div class="ipsBox i-margin-bottom_1">
						
IPSCONTENT;

if ( $humanVersion === \IPS\Application::load('core')->version ):
$return .= <<<IPSCONTENT

							<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_check_patches', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<h2 class='ipsBox__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $humanVersion, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class="i-padding_3 ipsRichText ipsRichText--release-notes">
							
IPSCONTENT;

if ( $humanVersion === \IPS\Application::load('core')->version ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $input->options['_details'][ $longVersion ]['releasenotes'] ):
$return .= <<<IPSCONTENT

									<ul>
										
IPSCONTENT;

foreach ( $input->options['_details'][ $longVersion ]['changes'] as $issue ):
$return .= <<<IPSCONTENT

											<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $issue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $input->options['_details'][ $longVersion ]['security'] ):
$return .= <<<IPSCONTENT

									<p><strong class="i-color_warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_security_update', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></p>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $input->options['_details'][ $longVersion ]['releasenotes'] ):
$return .= <<<IPSCONTENT

									<div>{$input->options['_details'][ $longVersion ]['releasenotes']}</div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<input type="submit" class="ipsButton ipsButton--primary" value="
IPSCONTENT;

if ( \IPS\CIC ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start_upgrade', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

if ( $input->options['_details'][ $longVersion ]['updateurl'] ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->options['_details'][ $longVersion ]['updateurl'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener" class='ipsButton ipsButton--secondary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_version_moreinfo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class="i-background_1 i-padding_3 ipsRichText ipsRichText--release-notes">
						
IPSCONTENT;

if ( $humanVersion === \IPS\Application::load('core')->version ):
$return .= <<<IPSCONTENT

							<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'upgrade_check_patches', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
							
IPSCONTENT;

if ( $input->options['_details'][ $longVersion ]['releasenotes'] ):
$return .= <<<IPSCONTENT

								<ul>
									
IPSCONTENT;

foreach ( $input->options['_details'][ $longVersion ]['changes'] as $issue ):
$return .= <<<IPSCONTENT

										<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $issue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<h2>
IPSCONTENT;

$sprintf = array($humanVersion); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_version_info', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h2>
							
IPSCONTENT;

if ( $input->options['_details'][ $longVersion ]['security'] ):
$return .= <<<IPSCONTENT

								<p><strong class="i-color_warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_security_update', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></p>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $input->options['_details'][ $longVersion ]['releasenotes'] ):
$return .= <<<IPSCONTENT

								<div>{$input->options['_details'][ $longVersion ]['releasenotes']}</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class="ipsSubmitRow">
						<input type="submit" class="ipsButton ipsButton--primary" value="
IPSCONTENT;

if ( \IPS\CIC ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start_upgrade', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

if ( $input->options['_details'][ $longVersion ]['updateurl'] ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->options['_details'][ $longVersion ]['updateurl'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener" class='ipsButton ipsButton--secondary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_version_moreinfo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</form>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $input->options['options'] ) > 1 ):
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}}