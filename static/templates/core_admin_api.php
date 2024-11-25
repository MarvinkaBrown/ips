<?php
namespace IPS\Theme;
class class_core_admin_api extends \IPS\Theme\Template
{	function apiKey( $key ) {
		$return = '';
		$return .= <<<IPSCONTENT

<code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
IPSCONTENT;

		return $return;
}

	function apiKeyField( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
<div class="cApiKey">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

		return $return;
}

	function apiLogCredentials( $row, $apiKey, $client, $member ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $row['api_key'] ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $apiKey ):
$return .= <<<IPSCONTENT

		<i class="fa-solid fa-key" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_id', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $apiKey->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<i class="fa-solid fa-key" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_id', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip></i> <code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['api_key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

elseif ( $row['access_token'] ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $row['member_id'] and $member ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userPhoto( $member, 'tiny' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $member->member_id ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userLink( $member, 'tiny' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'deleted_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$sprintf = array($client->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_log_member_client', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( $client ):
$return .= <<<IPSCONTENT

		<i class="fa-solid fa-cubes" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_client', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $client->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<i class="fa-solid fa-cubes" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_client', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip></i> <code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['access_token'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function apiPermissionDesc( $title, $description ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-text-align_start">
	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $description ):
$return .= <<<IPSCONTENT

		<br><span class="i-color_soft i-font-size_-1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function clientDetails( $val, $agent=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $agent ):
$return .= <<<IPSCONTENT

	<span data-ipsTooltip title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $val, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($agent->browserVersion, $agent->platform); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mobile_app_user_agent', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

elseif ( mb_strlen( $val ) > 40 ):
$return .= <<<IPSCONTENT

	<code title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $val, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_substr( $val, 0, 37 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
...</code>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $val, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function htaccess( $error, $url, $errorMessage ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $errorMessage ):
$return .= <<<IPSCONTENT

	<p class="ipsMessage ipsMessage--error i-margin-bottom_2">
IPSCONTENT;

$sprintf = array((string)$url); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_instructions_error', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		<br>
		
IPSCONTENT;

$sprintf = array($errorMessage); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_returned_error', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsBox ipsBox--api">
	<div class="i-padding_3 ipsRichText">
		<p><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_instructions_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></p>
		<ol>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_instructions_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$sprintf = array(\IPS\ROOT_PATH); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_instructions_2', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_instructions_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
		</ol>
	</div>
	<div class="ipsSubmitRow">
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=api&tab=apiLogs&recheck=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function oauthClientLink( $client ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'api', 'oauth_manage' ) ):
$return .= <<<IPSCONTENT
href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=oauth&do=view&client_id={$client->client_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $client->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function oauthScopeField( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class="i-grid i-gap_3">
	<li>
		<h4 class="i-color_hard i-font-weight_bold">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_auth_scope_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<input type="text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[key]" value="
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text ipsInput--auto">
	</li>
	<li>
		<h4 class="i-color_hard i-font-weight_bold">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_auth_scope_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<p class="i-margin-bottom_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_auth_scope_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<textarea name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[desc]" class="ipsInput ipsInput--text ipsInput--fullWidth" rows="5">
IPSCONTENT;

if ( $value ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value['desc'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</textarea>
		<p class="i-color_soft i-font-size_-1 i-margin-top_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_auth_scope_desc_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</li>
</ul>

IPSCONTENT;

		return $return;
}

	function oauthSecret( $client, $secret, $bruteForce ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $client->type == 'wordpress' ):
$return .= <<<IPSCONTENT

	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<div class="ipsBox i-margin-bottom_1">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_app_settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--oauth-secret">
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_application', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_application_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</span>
				</li>
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_app_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span>
						
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT

					</span>
				</li>
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_client_id', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span data-ipsCopy>
						<code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $client->client_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
								<button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="copyButton" data-clipboard-text="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $client->client_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					</span>
				</li>
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_client_secret', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span data-ipsCopy>
						
IPSCONTENT;

if ( $secret ):
$return .= <<<IPSCONTENT

							<code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $secret, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code><br>
								<button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="copyButton" data-clipboard-text="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $secret, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
							<span class="i-color_warning"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_client_secret_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'oauth_secrets' ) ):
$return .= <<<IPSCONTENT

								<br>
								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=api&tab=oauth&do=view&client_id={$client->client_id}&newSecret=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_regenerate_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_regenerate_secret', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				</li>
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_scope', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span>
						<code>email</code>
					</span>
				</li>
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorize_uri', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span>
						<code>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "oauth/authorize/", "interface", "", array(), \IPS\Http\Url::PROTOCOL_HTTPS ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
</code>
					</span>
				</li>
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_token_uri', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span>
						<code>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "oauth/token/", "interface", "", array(), \IPS\Http\Url::PROTOCOL_HTTPS ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
</code>
					</span>
				</li>
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_user_uri', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span>
						
IPSCONTENT;

if ( \IPS\Settings::i()->use_friendly_urls and \IPS\Settings::i()->htaccess_mod_rewrite ):
$return .= <<<IPSCONTENT

							<code>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( rtrim( \IPS\Settings::i()->base_url, '/' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/api/core/me</code>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<code>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( rtrim( \IPS\Settings::i()->base_url, '/' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/api/index.php?/core/me</code>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				</li>
			</ul>
		</i-data>
	</div>
	<div class="ipsBox i-margin-bottom_1">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_attribute_mapping', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--oauth-secret">
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_map_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span>
						<code>email</code>
					</span>
				</li>
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_wordpress_map_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span>
						<code>name</code>
					</span>
				</li>
			</ul>
		</i-data>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class="ipsBox i-margin-bottom_1">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_client_credentials', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--oauth-secret">
				<li class="ipsData__item">
					<span class="i-basis_180">
						<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_client_id', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
					</span>
					<span data-ipsCopy>
								<code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $client->client_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
								<button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="copyButton" data-clipboard-text="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $client->client_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					</span>
				</li>
				
IPSCONTENT;

if ( $client->client_secret ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<span class="i-basis_180">
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_client_secret', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						</span>
						<span data-ipsCopy>
							
IPSCONTENT;

if ( $secret ):
$return .= <<<IPSCONTENT

									<code id="secret">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $secret, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
									 <button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="copyButton" data-clipboard-text="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $secret, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
									<br>
								<span class="i-color_warning"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_client_secret_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'oauth_secrets' ) ):
$return .= <<<IPSCONTENT

									<br>
									<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=api&tab=oauth&do=view&client_id={$client->client_id}&newSecret=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_regenerate_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_regenerate_secret', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>
	
IPSCONTENT;

if ( $client->type != 'invision' ):
$return .= <<<IPSCONTENT

		<div class="i-margin-bottom_1">
			<div class="ipsBox">
				<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_endpoint_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<i-data>
					<ul class="ipsData ipsData--table ipsData--oauth-secret">
						
IPSCONTENT;

$grants = explode( ',', $client->grant_types );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \in_array( 'authorization_code', $grants ) or \in_array( 'token', $grants ) ):
$return .= <<<IPSCONTENT

							<li class="ipsData__item">
								<span class="i-basis_180">
									<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorize_uri', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								</span>
								<span>
									<code>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "oauth/authorize/", "interface", "", array(), \IPS\Http\Url::PROTOCOL_HTTPS ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
</code><br>
									
IPSCONTENT;

if ( \in_array( 'authorization_code', $grants ) and \in_array( 'token', $grants ) ):
$return .= <<<IPSCONTENT

										<span class="ipsType_desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorize_uri_desc_3', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

elseif ( \in_array( 'authorization_code', $grants ) ):
$return .= <<<IPSCONTENT

										<span class="ipsType_desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorize_uri_desc_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<span class="ipsType_desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorize_uri_desc_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \in_array( 'authorization_code', $grants ) or \in_array( 'client_credentials', $grants ) or \in_array( 'password', $grants ) ):
$return .= <<<IPSCONTENT

							<li class="ipsData__item">
								<span class="i-basis_180">
									<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_token_uri', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								</span>
								<span>
									<code>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "oauth/token/", "interface", "", array(), \IPS\Http\Url::PROTOCOL_HTTPS ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
</code><br>
									<span class="ipsType_desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_token_uri_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</i-data>
			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $bruteForce ):
$return .= <<<IPSCONTENT

	<div class="ipsBox i-margin-bottom_1">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_brute_force', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="i-color_soft i-padding_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_brute_force_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		{$bruteForce}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function oauthStatus( $accessToken, $useRefreshTokens ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $accessToken['status'] == 'revoked' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_status_revoked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

elseif ( $accessToken['access_token_expires'] ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $accessToken['access_token_expires'] > time() ):
$return .= <<<IPSCONTENT

		<i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_status_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $useRefreshTokens and $accessToken['refresh_token'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $accessToken['refresh_token_expires'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $accessToken['refresh_token_expires'] > time() ):
$return .= <<<IPSCONTENT

					<i class="fa-solid fa-rotate-right"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_status_refresh', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_status_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class="fa-solid fa-rotate-right"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_status_refresh', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_status_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_status_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function permissionsField( $endpoints, $name, $value, $type=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class='ipsFieldRow'>
	<div class='ipsFieldRow__label'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_permissions_endpoint', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsFieldRow__content'>
		<p class='i-margin-bottom_1'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_permissions_endpoint_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "api", \IPS\Request::i()->app )->permissionsFieldHtml( $endpoints, $name, $value, $type );
$return .= <<<IPSCONTENT

	</div>
</li>

IPSCONTENT;

		return $return;
}

	function permissionsFieldHtml( $endpoints, $name, $value, $type=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.admin.system.apiPermissions'>
	
IPSCONTENT;

foreach ( $endpoints as $app => $sections ):
$return .= <<<IPSCONTENT

		<div class='i-margin-bottom_4'>
			<div class='i-background_3 i-padding_2 cApiPermissions_header'>
				<h2 class='i-font-size_2'>
IPSCONTENT;

$val = "__app_{$app}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<span data-role="massToggle" class="ipsJS_show">
					<a href="#" data-action="checkAll">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a> /  <a href="#" data-action="checkNone">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</span>
			</div>
			<ul class='cApiPermissions'>
				
IPSCONTENT;

foreach ( $sections as $sectionID => $sectionEndpoints ):
$return .= <<<IPSCONTENT

					<li class='cApiPermissions_closed'>
						<h3 class='ipsCursor_pointer' data-action='toggleSection'>
IPSCONTENT;

$val = "__api_{$app}_{$sectionID}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3> 
						<span class='i-color_soft i-font-size_-1' data-role='endpointOverview'></span>
						<span data-role="massToggle" class="ipsJS_show">
							<a href="#" data-action="checkAll">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a> /  <a href="#" data-action="checkNone">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</span>
						<ul>
							
IPSCONTENT;

foreach ( $sectionEndpoints as $endpointKey => $endpoint ):
$return .= <<<IPSCONTENT

								<li>
									<ul class='ipsList ipsList--inline'>
										<li>
											<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endpointKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][access]" class="ipsInput ipsInput--toggle" value="1" 
IPSCONTENT;

if ( isset( $value[ $endpointKey ]['access'] ) and $value[ $endpointKey ]['access'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
											<label>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_permissions_access', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
										</li>
										<li>
											<input type="checkbox" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endpointKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][log]" class="ipsInput ipsInput--toggle" value="1" 
IPSCONTENT;

if ( isset( $value[ $endpointKey ]['log'] ) and $value[ $endpointKey ]['log'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
											<label>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_permissions_log', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
										</li>
										<li><strong><code>
IPSCONTENT;

$return .= \IPS\Api\Controller::parseEndpointForDisplay( $endpoint['title'] );
$return .= <<<IPSCONTENT
</code></strong></li>
									</ul>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function referenceEndpoint( $data, $params, $exceptions, $response, $additionalClasses ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="cApi">
	<div class='i-padding_3'>
		<div class="i-margin-bottom_4">
			<h2 class="ipsTitle ipsTitle--h3"><code>
IPSCONTENT;

$return .= \IPS\Api\Controller::parseEndpointForDisplay( $data['title'], 'large', TRUE );
$return .= <<<IPSCONTENT
</code></h2>
			<p class="i-margin-bottom_2 i-font-size_2">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( nl2br( $data['description'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

if ( isset( $data['details']['apimemberonly']) ):
$return .= <<<IPSCONTENT

				<p class="ipsMessage ipsMessage--warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_endpoint_member_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $data['details']['apiclientonly']) ):
$return .= <<<IPSCONTENT

				<p class="ipsMessage ipsMessage--warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_endpoint_client_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset( $data['details']['note']) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $data['details']['note'] as $note ):
$return .= <<<IPSCONTENT

					<p class="ipsMessage ipsMessage--info">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $note[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
		<div class="i-margin-bottom_4">
			<h3 class="ipsTitle ipsTitle--h3 
IPSCONTENT;

if ( $params ):
$return .= <<<IPSCONTENT
i-margin-bottom_1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_parameters', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			
IPSCONTENT;

if ( $params ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "api", \IPS\Request::i()->app )->referenceTable( $params );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_parameters_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>

		<div class="i-margin-bottom_4">
			<h3 class="ipsTitle ipsTitle--h3  
IPSCONTENT;

if ( $exceptions ):
$return .= <<<IPSCONTENT
i-margin-bottom_1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_exceptions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			
IPSCONTENT;

if ( $exceptions ):
$return .= <<<IPSCONTENT

				<table class="ipsTable">
					<tr>
						<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_exception_code', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
						<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_exception_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
						<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_exception_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
					</tr>
					
IPSCONTENT;

foreach ( $exceptions as $exception ):
$return .= <<<IPSCONTENT

						<tr>
							<td><code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $exception[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code></td>
							<td><code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $exception[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code></td>
							<td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $exception[2], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
						</tr>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</table>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_exceptions_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>

		<div class="i-margin-bottom_4">
			<h3 class="ipsTitle ipsTitle--h3 
IPSCONTENT;

if ( $response ):
$return .= <<<IPSCONTENT
i-margin-bottom_1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_response', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			
IPSCONTENT;

if ( $response ):
$return .= <<<IPSCONTENT

				{$response}
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_response_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>

		
IPSCONTENT;

foreach ( $additionalClasses as $class => $data ):
$return .= <<<IPSCONTENT

			<div class="i-margin-bottom_4">
				<a id="object-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></a>
				<h3 class="ipsTitle ipsTitle--h3 i-margin-bottom_1 i-link-color_inherit"><code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_object', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				{$data}
			</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function referenceTable( $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT

<table class="ipsTable">
	<tr>
		<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_param_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
		<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_param_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
		<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_param_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
	</tr>
	
IPSCONTENT;

foreach ( $rows as $column ):
$return .= <<<IPSCONTENT

		<tr>
			<td>
				
IPSCONTENT;

if ( isset( $column[4] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $column[4] === 'required' ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--neutral">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

elseif ( $column[4] === 'client' ):
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-key" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_response_client', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip></i>
					
IPSCONTENT;

elseif ( $column[4] === 'member' ):
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-user" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_response_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $column[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
			</td>
			<td>
				
IPSCONTENT;

if ( mb_strpos( $column[0], '|' ) !== FALSE OR \in_array( $column[0], array( 'array', 'int', 'string', 'float', 'datetime', 'bool', 'object' ) ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $column[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( mb_substr( $column[0], 0, 1 ) == '[' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \in_array( mb_substr( $column[0], 1, -1 ), array( 'int', 'string', 'float', 'datetime', 'bool', 'object' ) ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$sprintf = array(mb_substr( $column[0], 1, -1 )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_array_of_scalar', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_array_of', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="#object-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_strtolower( mb_substr( $column[0], mb_strrpos( $column[0], '\\' ) + 1, -1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_strtolower( mb_substr( $column[0], mb_strrpos( $column[0], '\\' ) + 1, -1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_array_objects', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href="#object-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_strtolower( mb_substr( $column[0], mb_strrpos( $column[0], '\\' ) + 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_strtolower( mb_substr( $column[0], mb_strrpos( $column[0], '\\' ) + 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_object', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</td>
			<td class='ipsTable_wrap'>
IPSCONTENT;

$val = "{$column[2]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</td>
		</tr>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</table>
IPSCONTENT;

		return $return;
}

	function referenceTemplate( $endPoints, $tree, $selected, $content ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isSecure() ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--warning i-margin_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_https_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsColumns ipsColumns--lines cApiReference" data-controller='core.admin.system.api'>
	<div class="ipsColumns__secondary i-basis_360">
		<h2 class='ipsTitle ipsMinorTitle i-padding-top_2 i-padding-inline_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_endpoints', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<div class="i-padding_2">
				<ul class="cApiTree i-margin-block_1">
					
IPSCONTENT;

foreach ( $tree as $app => $controllers ):
$return .= <<<IPSCONTENT

						<li>
							<details open data-ipsdetails>
								<summary>
IPSCONTENT;

$val = "__app_{$app}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</summary>
								<i-details-content>
									<ul>
										
IPSCONTENT;

foreach ( $controllers as $controller => $endpoints ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( \count( $endpoints ) > 1 ):
$return .= <<<IPSCONTENT

												<li>
													<details data-ipsdetails>
														<summary>
IPSCONTENT;

$val = "__api_{$app}_{$controller}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</summary>
														<i-details-content>
															<ul>
																
IPSCONTENT;

foreach ( $endpoints as $key => $endpoint ):
$return .= <<<IPSCONTENT

																	<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=api&tab=apiReference&endpoint={$key}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action='showEndpoint'><code>
IPSCONTENT;

$return .= \IPS\Api\Controller::parseEndpointForDisplay( $endpoint['title'] );
$return .= <<<IPSCONTENT
</code></a></li>
																
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

															</ul>
														</i-details-content>
													</details>
												</li>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												<li>
													
IPSCONTENT;

foreach ( $endpoints as $key => $endpoint ):
$return .= <<<IPSCONTENT

														<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=api&tab=apiReference&endpoint={$key}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action='showEndpoint'><code>
IPSCONTENT;

$return .= \IPS\Api\Controller::parseEndpointForDisplay( $endpoint['title'] );
$return .= <<<IPSCONTENT
</code></a>
													
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

												</li>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</i-details-content>
							</details>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</div>
	</div>
	<div class="ipsColumns__primary" data-role="referenceContainer">
		
IPSCONTENT;

if ( $content ):
$return .= <<<IPSCONTENT

			{$content}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function viewLog( $request, $response ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<h2 class="ipsMinorTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_request_data', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<pre>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $request, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
	<h2 class="ipsMinorTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_response', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<pre>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $response, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
</div>
IPSCONTENT;

		return $return;
}

	function webhookDesc( $hook ) {
		$return = '';
		$return .= <<<IPSCONTENT

    <span class="i-color_soft i-font-size_-1">
        
IPSCONTENT;

foreach ( $hook->events as $badge ):
$return .= <<<IPSCONTENT

            <span class="ipsBadge ipsBadge--normal"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

    </span>
IPSCONTENT;

		return $return;
}

	function webhooks( $webhooks ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_2'>
	<div class='ipsBox'>
	    <i-tabs class='ipsTabs' id='ipsTabs_hooks' data-ipsTabBar data-ipsTabBar-contentArea='#elWebhookReference' data-ipstabbar-updateurl="false">
	        <div role='tablist'>
				
IPSCONTENT;

$checkedTab = NULL;
$return .= <<<IPSCONTENT

	            
IPSCONTENT;

foreach ( $webhooks as $app => $appWebhooks ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \count($appWebhooks) ):
$return .= <<<IPSCONTENT

						<button type="button" class='ipsTabs__tab' role='tab' id='ipsTabs_hooks_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-controls="ipsTabs_hooks_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( !$checkedTab ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

$checkedTab = $app;
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

$val = "__app_{$app}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</button>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

	    </i-tabs>	
	    <section id='elWebhookReference' class='acpFormTabContent'>
	        
IPSCONTENT;

foreach ( $webhooks as $app => $appWebhooks ):
$return .= <<<IPSCONTENT

	        
IPSCONTENT;

$additionalClasses = [];
$return .= <<<IPSCONTENT

	        
IPSCONTENT;

if ( \count($appWebhooks) ):
$return .= <<<IPSCONTENT

	            <div id='ipsTabs_hooks_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' role="tabpanel" class="ipsTabs__panel" aria-labelledby="ipsTabs_hooks_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $checkedTab != $app ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	                <div class="i-padding_3">
	                    
IPSCONTENT;

foreach ( $appWebhooks as $key => $data ):
$return .= <<<IPSCONTENT

	                    <div class="i-padding-top_3">
	                        <h2 class="ipsTitle ipsTitle--h3 i-text-align_center">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
	                        <hr class="ipsHr">
	                        <p>
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists( "webhook_" . $key )  ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "webhook_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>
	                        <hr class="ipsHr">
	                        <p class="i-font-weight_bold i-margin-bottom_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'webhook_payload', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
	                        
IPSCONTENT;

if ( \is_string($data) ):
$return .= <<<IPSCONTENT

	                        
IPSCONTENT;

$additionalClasses[$data] = \IPS\Api\DocumentationHelper::getDescriptionForClass($data);
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

foreach ( \IPS\Api\DocumentationHelper::getAdditionalClasses($data) as $c ):
$return .= <<<IPSCONTENT

                                
IPSCONTENT;

$additionalClasses[$c] = \IPS\Api\DocumentationHelper::getDescriptionForClass($c);
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


	                        <pre><a href="#object-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_strtolower( mb_substr( $data, mb_strrpos( $data, '\\' ) + 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></pre>
	                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	                        <table class="ipsTable">
	                            <thead><tr><th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'webhook_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th><th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'webhook_description', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th></tr></thead>
	                            
IPSCONTENT;

if ( \is_array($data) ):
$return .= <<<IPSCONTENT

	                            
IPSCONTENT;

foreach ( $data as $k => $v ):
$return .= <<<IPSCONTENT

	                            <tr>
	                                <td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
	                                <td>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
	                            </tr>
	                            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	                        </table>
	                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	                    </div>
	                    <hr class="ipsHr_thick">
	                    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	                </div>
	                <div class="i-padding_3">
	                    <h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'webhook_class_payload', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
                        
IPSCONTENT;

foreach ( $additionalClasses as $class => $data ):
$return .= <<<IPSCONTENT

                            <a id="object-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( mb_strtolower( mb_substr( $class, mb_strrpos( $class, '\\' ) + 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></a>
                            <h3 class="ipsTitle ipsTitle--h3 i-margin-bottom_1 i-link-color_inherit"><code>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_object', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                            {$data}
                            <hr class='ipsHr'>
                        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	                </div>
	            </div>
	        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	    </section>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function webhookselector( $name, $value, $required, $options, $multiple=FALSE, $class='', $disabled=FALSE, $toggles=array(), $id=NULL, $unlimited=NULL, $unlimitedLang='all', $unlimitedToggles=array(), $toggleOn=TRUE, $descriptions=array(), $impliedUnlimited=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $multiple ):
$return .= <<<IPSCONTENT

<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[__EMPTY]" value="__EMPTY">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div data-control="granularCheckboxset" data-count="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $options ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">

IPSCONTENT;

if ( $unlimited !== NULL AND $impliedUnlimited === FALSE ):
$return .= <<<IPSCONTENT

<div data-role="checkboxsetUnlimited" class="
IPSCONTENT;

if ( !\is_array( $value ) ):
$return .= <<<IPSCONTENT
ipsJS_show
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<input
		type='checkbox'
		name="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( trim( $name, '[]' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited"
	value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unlimited, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	id="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( trim( $id ?: $name, '[]' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited"
	
IPSCONTENT;

if ( $unlimited === $value ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $disabled ):
$return .= <<<IPSCONTENT
disabled aria-disabled='true'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	class="ipsSwitch"
	data-role="checkboxsetUnlimitedToggle"
	>
	&nbsp;
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	&nbsp;
	<a class="ipsCursor_pointer" data-action="checkboxsetCustomize">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'customize', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div data-role="checkboxsetGranular" class="ipsField__checkboxOverflow 
IPSCONTENT;

if ( $unlimited !== NULL AND $impliedUnlimited === FALSE and !\is_array( $value ) ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">

	<div class="ipsSpanGrid">
		
IPSCONTENT;

foreach ( $options as $k => $v ):
$return .= <<<IPSCONTENT

        <div class='ipsSpanGrid__3'>Span 3</div>
			<input type="checkbox" class="ipsInput ipsInput--toggle 
IPSCONTENT;

if ( $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" 
IPSCONTENT;

if ( ( $unlimited !== NULL AND $unlimited === $value ) or ( \is_array( $value ) AND \in_array( $k, $value ) ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled === TRUE or ( \is_array( $disabled ) and \in_array( $k, $disabled ) ) ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $toggles[ $k ] ) and !empty( $toggles[ $k ] ) ):
$return .= <<<IPSCONTENT
data-control="toggle" 
IPSCONTENT;

if ( $toggleOn === FALSE ):
$return .= <<<IPSCONTENT
data-togglesOff
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-togglesOn
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $toggles[ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<div class='ipsFieldList__content'>
				<label for='elCheckbox_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label' data-role="label">{$v}</label>
				
IPSCONTENT;

if ( isset( $descriptions[ $k ] ) ):
$return .= <<<IPSCONTENT

					<div class='ipsFieldRow__desc'>
						{$descriptions[ $k ]}
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
        </div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>

	<div class="i-margin-top_1 ipsJS_show ipsField__checkboxOverflow__toggles" data-role="massToggles">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		&nbsp;
		<a class="ipsCursor_pointer" data-action="checkboxsetAll">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a> / <a class="ipsCursor_pointer" data-action="checkboxsetNone">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
</div>
IPSCONTENT;

		return $return;
}

	function zapier( $apiKey ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'zapier_setup_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
<div class="ipsBox">
	<ul class="ipsForm ipsForm--horizontal ipsForm--zapier">
		<li class="ipsFieldRow ipsFieldRow_yesNo ">
			<div class="ipsFieldRow__label">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'zapier_community_url', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsFieldRow__content" data-ipscopy>
				<code class=" prettyprint prettyprinted" data-role="copyTarget">
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( rtrim( \IPS\Settings::i()->base_url, '/' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/</code>
                <button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="copyButton" data-clipboard-text="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( rtrim( \IPS\Settings::i()->base_url, '/' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
            </div>
		</li>

		<li class="ipsFieldRow ipsFieldRow_yesNo ">
			<div class="ipsFieldRow__label">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'api_id', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsFieldRow__content" data-ipscopy>
				<code class=" prettyprint prettyprinted" data-role="copyTarget" >
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $apiKey->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
                <button type="button" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="copyButton" data-clipboard-text="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $apiKey->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
            </div>
		</li>
	</ul>
	<hr class="ipsHr_thick">
	<div class="ipsBox i-padding_3">
		<script type="module" src="https://zapier.com/partner/embed/app-directory/wrapper.js?app=invision-community&link-target=new-tab&theme=auto&applimit=5&zaplimit=6&zapstyle=card&introcopy=hide"></script>
	</div>

</div>

IPSCONTENT;

		return $return;
}}