<?php
namespace IPS\Theme;
class class_core_admin_members extends \IPS\Theme\Template
{	function acpRestrictions( $current, $restrictions, $row ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form data-ipsform accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--restrictions" action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=staff&controller=admin&do=save&id={$row['row_id']}&type={$row['row_id_type']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post">
<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
<div class='ipsBox i-margin-bottom_1'>
	<div class='acpFormTabContent'>
		<ul class='ipsForm ipsForm--horizontal ipsForm--restrictions'>
			<li class='ipsFieldRow' id="use_restrictions_id">
				<div class='ipsFieldRow__label '>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mod_use_restrictions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					<span class='ipsFieldRow__required'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</div>
				<div class='ipsFieldRow__content'>
					<ul class="ipsFieldList" role="radiogroup" id="elRadio_mod_use_restrictions_use_restrictions_id">
						<li>
							<input type="radio" class="ipsInput ipsInput--toggle" name="admin_use_restrictions" value="no" 
IPSCONTENT;

if ( $current === '*' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_admin_use_restrictions_no_use_restrictions_id">
							<div class='ipsFieldList__content'>
								<label for='elRadio_admin_use_restrictions_no_use_restrictions_id' id='elField_mod_use_restrictions_label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mod_all_permissions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
								<div class='ipsFieldRow__desc'>
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'admin_all_permissions_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</div>
							</div>
						</li>
						<li>
							<input type="radio" class="ipsInput ipsInput--toggle" name="admin_use_restrictions" value="yes" 
IPSCONTENT;

if ( $current !== '*' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
  data-control="toggle" data-toggles="permission_form_wrapper" id="elRadio_admin_use_restrictions_yes_use_restrictions_id">
							<div class='ipsFieldList__content'>
								<label for='elRadio_admin_use_restrictions_yes_use_restrictions_id' id='elField_mod_use_restrictions_label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mod_restricted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
								<div class='ipsFieldRow__desc'>
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'admin_restricted_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</div>
							</div>
						</li>
					</ul>
				</div>
			</li>
		</ul>
	</div>
</div>

	<div id='permission_form_wrapper'>
		<i-tabs class='ipsTabs acpFormTabBar' id='ipsTabs_restrictions' data-ipsTabBar data-ipsTabBar-contentArea="#ipsTabs_restrictions_content">
			<div role="tablist">
				
IPSCONTENT;

foreach ( $restrictions['applications'] as $appKey => $appId ):
$return .= <<<IPSCONTENT

					<button type="button" class="ipsTabs__tab" id='ipsTabs_restrictions_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' role="tab" aria-controls="ipsTabs_restrictions_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( $appKey == 'core' ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "__app_$appKey"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

		</i-tabs>
		<div class='acpFormTabContent' id='ipsTabs_restrictions_content'>
			
IPSCONTENT;

foreach ( $restrictions['applications'] as $appKey => $appId ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_restrictions_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class='ipsTabs__panel' role="tabpanel" aria-labelledby="ipsTabs_restrictions_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $appKey != 'core' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>			
					<div class='i-padding_3' data-controller='core.admin.members.restrictions'>
						<div class='i-background_3 i-padding_3 acpAppRestrictions_header'>
							<input type="checkbox" class="ipsSwitch" id='elRestrict_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $current === '*' or ( isset( $current['applications'] ) and array_key_exists( $appKey, $current['applications'] ) ) ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> &nbsp;&nbsp;<label for='elRestrict_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack('__app_' . $appKey )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acprestrictions_app', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span></label>
						</div>
						<ul id='elRestrictions_root_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_tab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='acpAppRestrictions_panel'>
							
IPSCONTENT;

if ( isset( $restrictions['modules'] ) AND isset( $restrictions['modules'][ $appId ] )  ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

foreach ( $restrictions['modules'][ $appId ] as $moduleKey => $moduleId ):
$return .= <<<IPSCONTENT

									<li class='i-padding_3'>
										<div class="acpRestrictions_header">
											<input type="checkbox" class="ipsSwitch" name="r[applications][
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $moduleKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" id='elRestrict_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $moduleKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $moduleKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $current === '*' or ( isset( $current['applications'] ) and array_key_exists( $appKey, $current['applications'] ) and \in_array( $moduleKey, $current['applications'][ $appKey ] ) ) ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> <h2><label for='elRestrict_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $moduleKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><strong>
IPSCONTENT;

$val = "menu__{$appKey}_{$moduleKey}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></h2> &nbsp;&nbsp;<span data-role='toggle' class='ipsJS_show'><a href='#' data-action='expandAll'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'expand', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></a> / <a href='#' data-action='collapseAll'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'collapse', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></a></span></label>
										</div>
										
IPSCONTENT;

if ( isset( $restrictions['items'][ $moduleId ] ) ):
$return .= <<<IPSCONTENT

											<ul class="acpRestrictions_panel">
												
IPSCONTENT;

foreach ( $restrictions['items'][ $moduleId ] as $title => $items ):
$return .= <<<IPSCONTENT

													<li>
														<div class="acpRestrictions_subHeader acpRestrictions_open i-color_soft">
															<h3>
IPSCONTENT;

$val = "r__{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3> &nbsp;&nbsp;<span data-role='massToggle' class='ipsJS_show'><a href='#' data-action='checkAll'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a> /  <a href='#' data-action='checkNone'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></a>
														</div>
														<ul>
															
IPSCONTENT;

foreach ( $items as $k => $v ):
$return .= <<<IPSCONTENT

																<li>
																	<div>
																		<input type="checkbox" id='elRestrict_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $moduleKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsSwitch" name="r[items][
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $moduleKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
][]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $current === '*' or ( isset( $current['items'] ) and array_key_exists( $appKey, $current['items'] ) and array_key_exists( $moduleKey, $current['items'][ $appKey ] ) and \in_array( $k, $current['items'][ $appKey ][ $moduleKey ] ) ) ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> <label for='elRestrict_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $appKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $moduleKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "r__{$v}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
																	</div>
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
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>		
					</div>
				</div>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	</div>
	<div class="ipsSubmitRow">
		<button class="ipsButton ipsButton--primary" role="button" type="submit">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function adminDetails( $details ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsColumns i-padding_3'>
	<div class='ipsColumns__secondary i-basis_120 ipsResponsive_hidePhone'>
		<div class='i-padding_3 i-text-align_center'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::loggedIn(), 'medium' );
$return .= <<<IPSCONTENT

		</div>
	</div>
	<div class='ipsColumns__primary'>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--admin-details">
				<li class='ipsData__item '>
					<div class='ipsData__main'>
						<strong class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						<p>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['username'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</p>
					</div>
				</li>
				<li class='ipsData__item '>
					<div class='ipsData__main'>
						<strong class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						<p>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['email_address'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</p>
					</div>
					<p class='i-basis_100'>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=adminEmail", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--secondary' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</p>
				</li>
				
IPSCONTENT;

if ( isset( $details['password'] ) ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item '>
						<div class='ipsData__main'>
							<strong class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							<p>
								<span class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['password'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							</p>
						</div>
						<p class='i-basis_100'>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=adminPassword", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--secondary' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</p>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function badgesLog( $table, $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox' data-ips-template="badgesLog">
	<h1 class='ipsBox__header'>
IPSCONTENT;

$htmlsprintf = array($member->acpUrl(), $member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_badges_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</h1>
</div>
{$table}
IPSCONTENT;

		return $return;
}

	function bulkMailPreview( $mail, $members, $count ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox'>
	<i-tabs class='ipsTabs acpFormTabBar' id='ipsTabs_bulkmail' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_bulkmail_content'>
		<div role='tablist'>
			<button type="button" id='ipsTabs_bulkmail_overview' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_bulkmail_overview_panel" aria-selected="true">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'bm_send_overview', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</button>
			<button type="button" id='ipsTabs_bulkmail_memberlist' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_bulkmail_memberlist_panel" aria-selected="false">
				
IPSCONTENT;

$sprintf = array($count); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'bm_send_recipients', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			</button>
		</div>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

	</i-tabs>
	<div id='ipsTabs_bulkmail_content' class='acpFormTabContent'>
		<div id='ipsTabs_bulkmail_overview_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_bulkmail_overview">
			<ul class='ipsForm ipsForm--horizontal ipsForm--bulk-overview'>
				<li class='ipsJS_hide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'bm_send_overview', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
				<li class='ipsFieldRow'>
					<div class='ipsFieldRow__label'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mail_subject', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsFieldRow__content'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mail->subject, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				</li>
				<li class='ipsFieldRow'>
					<div class='ipsFieldRow__label'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mail_body', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsFieldRow__content'>
						<iframe seamless src='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=bulkmail&controller=bulkmail&do=iframePreview&id=", "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mail->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style="width:100%" height='350'></iframe>
					</div>
				</li>
			</ul>
		</div>
		<div id='ipsTabs_bulkmail_memberlist_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_bulkmail_memberlist" hidden>
			<div class='ipsJS_hide'>
IPSCONTENT;

$sprintf = array($count); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'bm_send_recipients', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
			<div class='ipsSpanGrid'>
				
IPSCONTENT;

foreach ( $members as $_index => $member ):
$return .= <<<IPSCONTENT

					<div class='ipsSpanGrid__3 i-padding_3'>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member['member_id']}", "front", "profile", $member['members_seo_name'], 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						<span class='i-color_soft'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member['email'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
					</div>
					
IPSCONTENT;

if ( $_index > 0 AND ( $_index + 1 ) % 4 == 0 ):
$return .= <<<IPSCONTENT
</div><div class='ipsSpanGrid'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>
	<div class="ipsSubmitRow">
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=bulkmail&controller=bulkmail&do=form&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mail->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue_editing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=bulkmail&controller=bulkmail&do=send&id={$mail->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'proceed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function confirmMassAction( $count, $action, $group=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsMessage ipsMessage--warning'>
	
IPSCONTENT;

if ( $action === 'prune' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_prune_confirm_msg', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( $action === 'unSub' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_unsub_confirm_msg', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$sprintf = array($group->name); $pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_move_confirm_msg', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

if ( $action === 'prune' ):
$return .= <<<IPSCONTENT

	<div class='ipsPos_center'><a class='ipsButton ipsButton--primary' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=doPrune" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>

IPSCONTENT;

elseif ( $action === 'unSub' ):
$return .= <<<IPSCONTENT

	<div class='ipsPos_center'><a class='ipsButton ipsButton--primary' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=doUnsub" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='ipsPos_center'><a class='ipsButton ipsButton--primary' href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=doMove" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function deviceAuthorization( $authorized, $active, $anonymous ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $authorized ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $active ):
$return .= <<<IPSCONTENT

		<i class="fa-solid fa-check"></i>
		
IPSCONTENT;

if ( $anonymous ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_login_key_anonymous', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_login_key_ok', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<i class="fa-regular fa-clock"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_login_key_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_login_key_no', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function deviceDuplicate(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="i-color_warning"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_used_other_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function deviceHandler( $key ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$handler = NULL;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \is_numeric( $key ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

try { $handler = \IPS\Login\Handler::load( $key ); } catch( \Exception $e ) { $handler = NULL; } 
$return .= <<<IPSCONTENT


IPSCONTENT;

elseif ( $key ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$handlers = \IPS\Login::getStore();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $handlers as $method ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$_key = mb_substr( \get_class( $method ), 10 );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $_key == $key ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$handler = $method;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $handler ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $handler->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

elseif ( $key ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<em class="i-color_soft" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_handler_unknown_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_handler_unknown', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function deviceInfo( $device, $apps, $oauthClients ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
	<ul class="ipsData ipsData--table ipsData--device-info">
		<li class="ipsData__item">
			<span class="i-basis_160"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_user_agent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<span>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $device->userAgent(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
				<code class="i-font-size_-1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $device->user_agent, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
			</span>
		</li>
		
IPSCONTENT;

foreach ( $apps as $accessToken ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item">
				<span class="i-basis_160"><strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $oauthClients[ $accessToken['client_id'] ]->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></span>
				<span>
					
IPSCONTENT;

if ( $accessToken['issue_user_agent'] ):
$return .= <<<IPSCONTENT

						<div class="i-margin-bottom_1">
							<code class="i-font-size_-1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $accessToken['issue_user_agent'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</code>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "api", \IPS\Request::i()->app )->oauthStatus( $accessToken, $oauthClients[ $accessToken['client_id'] ]->use_refresh_tokens );
$return .= <<<IPSCONTENT

					<span class="i-font-size_-1 i-color_soft">
						&nbsp;&nbsp;&nbsp;
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorization_issued', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$val = ( $accessToken['issued'] instanceof \IPS\DateTime ) ? $accessToken['issued'] : \IPS\DateTime::ts( $accessToken['issued'] );$return .= $val->html();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $accessToken['access_token_expires'] ):
$return .= <<<IPSCONTENT

							&middot;
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorization_access_token_expires', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$val = ( $accessToken['access_token_expires'] instanceof \IPS\DateTime ) ? $accessToken['access_token_expires'] : \IPS\DateTime::ts( $accessToken['access_token_expires'] );$return .= $val->html();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $accessToken['refresh_token_expires'] ):
$return .= <<<IPSCONTENT

							&middot;
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_refresh_token_expires', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$val = ( $accessToken['refresh_token_expires'] instanceof \IPS\DateTime ) ? $accessToken['refresh_token_expires'] : \IPS\DateTime::ts( $accessToken['refresh_token_expires'] );$return .= $val->html();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
					
IPSCONTENT;

if ( $accessToken['scope'] ):
$return .= <<<IPSCONTENT

						<div class="i-margin-top_1">
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_authorization_scope', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ', ', json_decode( $accessToken['scope'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</span>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		<li class="ipsData__item">
			<span class="i-basis_160"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_login_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<span>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "members", \IPS\Request::i()->app )->deviceAuthorization( (bool) $device->login_key, TRUE, $device->anonymous );
$return .= <<<IPSCONTENT
<br>
				<span class="i-font-size_-1 i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_login_key_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</span>
		</li>
		<li class="ipsData__item">
			<span class="i-basis_160"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_login_handler', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<span>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "members", \IPS\Request::i()->app )->deviceHandler( $device->login_handler );
$return .= <<<IPSCONTENT

			</span>
		</li>
		<li class="ipsData__item">
			<span class="i-basis_160"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_last_seen', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<span>
				
IPSCONTENT;

$val = ( $device->last_seen instanceof \IPS\DateTime ) ? $device->last_seen : \IPS\DateTime::ts( $device->last_seen );$return .= $val->html();
$return .= <<<IPSCONTENT

			</span>
		</li>
	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function deviceTable( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$table}
<div class="i-padding_2">
	<span class="i-font-size_-1 i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_login_key_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
</div>
IPSCONTENT;

		return $return;
}

	function downloadMemberList( $removedData, $includeInsecure ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-text-align_center">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=export&download=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--large"><i class="fa-solid fa-cloud-arrow-down"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_export', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>

IPSCONTENT;

if ( $includeInsecure ):
$return .= <<<IPSCONTENT

	<p class="ipsMessage ipsMessage--warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_export_security', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

elseif ( !empty( $removedData ) ):
$return .= <<<IPSCONTENT

	<div class='i-padding_3'>
		<div class="ipsMessage ipsMessage--warning">
			<div class="i-margin-bottom_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_export_security_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		</div>

		<div class='i-margin-top_1'>
			<table class="ipsTable i-background_1">
				<tr>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_export_security_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_export_security_column', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
					<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_export_security_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</th>
				</tr>
				
IPSCONTENT;

foreach ( $removedData as $memberId => $data ):
$return .= <<<IPSCONTENT

					<tr>
						<td>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userLink( \IPS\Member::load( $memberId ) );
$return .= <<<IPSCONTENT
</td>
						<td>
IPSCONTENT;

$val = "{$data[0]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</td>
						<td>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( base64_decode( $data[1] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</td>
					</tr>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</table>
			<div class="i-margin-top_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_export_security_footer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function emptyGroupPermissions(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsMessage ipsMessage--form'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'empty_perms_no_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

		return $return;
}

	function geoipDisclaimer(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-font-size_-1 i-color_soft i-padding_2">* 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_geolocation_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

		return $return;
}

	function groupCell( $group, $secondaryGroups ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $secondaryGroups ) ):
$return .= <<<IPSCONTENT

	<br>
	<span class='i-color_soft i-font-size_-1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secondary_groups', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->formatList( $secondaryGroups ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

		return $return;
}

	function groupLink( $group ) {
		$return = '';
		$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&advanced_search_submitted=1&members_member_group_id={$group->g_id}&noColumn=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function importMemberErrors( $errors ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'import_member_errors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<ul>
		
IPSCONTENT;

foreach ( $errors as $error ):
$return .= <<<IPSCONTENT

			<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
<div class="ipsSubmitRow">
	<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'step_finish', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function ipform( $ip, $members ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox' data-ips-template="ipform">
	<h1 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ipaddress_table_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<div>
		{$ip}
	</div>
</div>
<div class='i-margin-top_1' data-ips-template="form">
	<div class='ipsBox'>
		<h1 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'memberip_table_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<div>
			{$members}
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function logType( $type ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $type === 'display_name' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-user"></i>

IPSCONTENT;

elseif ( $type === 'email_change' ):
$return .= <<<IPSCONTENT

	<i class="fa-regular fa-envelope"></i>

IPSCONTENT;

elseif ( $type === 'password_change' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-key"></i>

IPSCONTENT;

elseif ( $type === 'mfa' ):
$return .= <<<IPSCONTENT

	<i class="fa-solid fa-lock"></i>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function memberCounts( $group ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$url = "app=core&module=members&controller=members&advanced_search_submitted=1&members_member_group_id={$group['g_id']}&noColumn=1&_groupFilter=1";
$return .= <<<IPSCONTENT

<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( $url . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'><span data-ipsGroupCount data-ipsGroupId='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group['g_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsLoading ipsLoading--tiny'>&nbsp;</span></a>
IPSCONTENT;

		return $return;
}

	function memberEmailCell( $content ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	{$content}
</div>
IPSCONTENT;

		return $return;
}

	function memberListResultsInfobox( $massActionLink ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p class="ipsMessage ipsMessage--info">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_search_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_view_full_list', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete' ) ):
$return .= <<<IPSCONTENT

		&mdash; <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $massActionLink->setQueryString( 'action', 'prune' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_search_prune', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) ):
$return .= <<<IPSCONTENT

		&mdash; <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $massActionLink->setQueryString( 'action', 'move' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_search_move', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

if ( \IPS\Widget\Request::i()->members_allow_admin_emails !== 'a' ):
$return .= <<<IPSCONTENT

			&mdash; <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $massActionLink->setQueryString( 'action', 'unSub' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_search_unsub', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</p>
IPSCONTENT;

		return $return;
}

	function memberRank( $points ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $rank = \IPS\core\Achievements\Rank::fromPoints( $points ) ):
$return .= <<<IPSCONTENT

	{$rank->html('ipsTree__thumb')}
	
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	<span class="i-color_soft i-font-size_-1">(
IPSCONTENT;

$pluralize = array( $points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_awards_points_short', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<span class="i-color_soft">
IPSCONTENT;

$pluralize = array( $points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_awards_points_short', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function memberReserved( $member=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<em>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_name_missing_as_reserved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>

IPSCONTENT;

if ( $member->members_bitoptions['created_externally'] ):
$return .= <<<IPSCONTENT

	<br><span data-ipsTooltip class="ipsBadge ipsBadge--negative">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reserved_external', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

elseif ( ( $member->last_visit == 0 and $member->joined->getTimestamp() > ( time() - 3600 ) ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$mins = ceil( ( $member->joined->getTimestamp() - ( time() - 3600 ) ) / 60 );
$return .= <<<IPSCONTENT

	<br><span title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_name_missing_as_reserved_tt', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsBadge ipsBadge--negative">
IPSCONTENT;

$sprintf = array($mins); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reserved_pending_deletion', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	







IPSCONTENT;

		return $return;
}

	function memberSearchResult( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="clearfix clickable">
	<div class="left">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'medium' );
$return .= <<<IPSCONTENT

	</div>
	<div class="left ipsMemberData">
		{$member->group['prefix']}
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
{$member->group['suffix']}<br>
		<span class="i-color_soft">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function memberValidatingCell( $email, $message ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="i-color_warning"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><br>
<span class="i-color_soft i-font-size_-1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $message, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function moderationLimits( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'after', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]" value="
IPSCONTENT;

if ( isset( $value[0] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]">
	<option value="0" 
IPSCONTENT;

if ( isset( $value[1] ) and !$value[1] ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approved_posts_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="1" 
IPSCONTENT;

if ( isset( $value[1] ) and $value[1] ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'days_since_joining', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
<div class="ipsFieldRow__inlineCheckbox">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<input type="checkbox" class="ipsInput ipsInput--toggle" data-control="unlimited" id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' 
IPSCONTENT;

if ( !isset( $value[0] ) or !$value[0] ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]">
	<label for='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' class='ipsField_unlimited'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'never', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
</div>
IPSCONTENT;

		return $return;
}

	function moderatorPermissions( $id, $action, $tabs, $hiddenValues, $actionButtons, $uploadField, $formClass='', $attributes=array(), $sidebar=NULL, $form=NULL, $errorTabs=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.admin.members.moderatorPermissions' class="ipsBox">
	<form accept-charset='utf-8' data-formId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsForm class="ipsFormWrap ipsFormWrap--moderator-permissions 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
>
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

foreach ( $v as $_v ):
$return .= <<<IPSCONTENT

					<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[]" value="
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

		
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT

			<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='acpFormTabContent'>
			<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--admin-template ipsForm--moderator-permissions'>
				
IPSCONTENT;

foreach ( $tabs[''] as $input ):
$return .= <<<IPSCONTENT

					{$input->rowHtml( $form )}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		<div id='permission_form_wrapper'>
			<!-- <ul class="ipsButtons">
				<li><a href="#" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="checkAll">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'check_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				<li><a href="#" class="ipsButton ipsButton--inherit ipsButton--tiny" data-role="uncheckAll">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'uncheck_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			</ul> -->
			<div class=''>
				
IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

					<div class="ipsMessage ipsMessage--error">
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !empty( $errorTabs ) ):
$return .= <<<IPSCONTENT

					<p class="ipsMessage ipsMessage--error">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tab_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<i-tabs class='ipsTabs i-background_2 acpFormTabBar' id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content'>
					<div role='tablist'>
						
IPSCONTENT;

$checkedTab = NULL;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $tabs as $name => $content ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $name ):
$return .= <<<IPSCONTENT

								<button type="button" id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsTabs__tab 
IPSCONTENT;

if ( \in_array( $name, $errorTabs ) ):
$return .= <<<IPSCONTENT
ipsTabs__tab--error
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" role="tab" aria-controls="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( !$checkedTab ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

$checkedTab = $name;
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

if ( \in_array( $name, $errorTabs ) ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle-exclamation"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
				<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='acpFormTabContent'>
					
IPSCONTENT;

foreach ( $tabs as $name => $collection ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $name ):
$return .= <<<IPSCONTENT

							<div id='ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $checkedTab != $name ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--admin-template ipsForm--moderator-permissions'>
									
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

										{$input->rowHtml( $form )}
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
		<div class="ipsSubmitRow">
			
IPSCONTENT;

$return .= implode( '', $actionButtons );
$return .= <<<IPSCONTENT

		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function pointsLog( $table, $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox i-margin-bottom_1' data-ips-template="pointsLog">
	<h1 class='ipsBox__header'>
IPSCONTENT;

$htmlsprintf = array($member->acpUrl(), $member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_points_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</h1>
</div>
{$table}
IPSCONTENT;

		return $return;
}

	function postingLimits( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'after', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput">
<select name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]" class="ipsInput ipsInput--select">
	<option value="0" 
IPSCONTENT;

if ( !$value[1] ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approved_posts_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="1" 
IPSCONTENT;

if ( $value[1] ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'days_since_joining', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
<div class="ipsFieldRow__inlineCheckbox">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<input type="checkbox" class="ipsInput ipsInput--toggle" data-control="unlimited" id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' 
IPSCONTENT;

if ( !$value[0] ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<label for='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' class='ipsField_unlimited'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'never', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
</div>
IPSCONTENT;

		return $return;
}

	function prefixSuffix( $name, $color, $prefix, $suffix ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_color" class="
IPSCONTENT;

if ( $prefix or $suffix ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsJS_show
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
    <div class="ipsInput__color-wrap" >
        <div class="ipsInput__color-wrap-inner">
            <input
                    type="color"
                    name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[color]"
                    value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
                    data-control="color" class="ipsInput ipsInput--text">
            <span spellcheck="false" contenteditable="plaintext-only" class="ipsInput__color-label">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
            <div data-role="iro-container" class="ipsInput__color-iro-container ipsMenu"></div>
        </div>
    </div>  &nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp; <a href="#" data-clickshow="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_html" data-clickhide="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_color">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'use_html', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</span>
<span id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_html" class="
IPSCONTENT;

if ( !$prefix and !$suffix ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<input type="text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[prefix]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_prefix" placeholder="&lt;strong&gt;" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $prefix, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsField_short"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'g_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[suffix]" id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_suffix" placeholder="&lt;/strong&gt;" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $suffix, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsField_short">
	<span class="no_js_hide"> &nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp; <a href="#" data-clickshow="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_color" data-clickhide="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_html" data-clickempty="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_prefix,
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_suffix">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'use_color', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></span>
</span>
IPSCONTENT;

		return $return;
}

	function profileCompleteBlurb( $canAdd ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3'>
	<div>
		<h4 class="i-font-weight_600 i-color_hard i-font-size_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_complete_blurb_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<div>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_complete_blurb_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ! $canAdd ):
$return .= <<<IPSCONTENT

				<div class="ipsMessage ipsMessage--info">
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_complete_no_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function profileCompleteTitle( $step ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$val = "profile_step_title_{$step['step_id']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<div class="i-font-size_-1 i-color_soft">
IPSCONTENT;

$val = "profile_step_text_{$step['step_id']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

		return $return;
}

	function quickRegisterDisabled(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='acpWarnings'>
	<div class='i-background_2 i-padding_3'>
		<div>
			<h2 class='ipsTitle ipsTitle--h3'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_complete_quick_register_off_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></h2>
			<div>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_complete_quick_register_off', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				<ul class='ipsButtons ipsButtons--end ipsButtons--quickRegisterDisabled'>
					<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=membersettings&controller=profilecompletion&do=enableQuickRegister" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_complete_quick_register_off_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				</ul>
			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function referralPopup( $content ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<div class='i-padding_3'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	{$content}


IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function referralsOverview( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<ul class='cCustomerOther_list i-grid i-gap_3'>
		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_referrals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function referralsOverviewRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li>
		<p class='ipsTruncate ipsTruncate_line'>
			{$row['member_id']}
		</p>
		<span class='i-font-size_1 i-color_soft'>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['email'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</span>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function referralsTable( $customer, $table, $title, $type ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox i-margin-bottom_1" data-ips-template="referralsTable">
	
IPSCONTENT;

$count = \count( $table->getRows(NULL) );
$return .= <<<IPSCONTENT

	<h3 class='ipsBox__header'>
IPSCONTENT;

if ( $count ):
$return .= <<<IPSCONTENT
<strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $count ):
$return .= <<<IPSCONTENT
</strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h3>
	<div class="i-padding_3">
		{$table}
		<p class='i-margin-top_1'>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Referrals&id={$customer->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$val = "customer_tab_{$type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit ipsButton--tiny ipsButton--wide'>
				
IPSCONTENT;

$val = "customer_manage_{$type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</a>
		</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function restrictionsLabel( $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $value === '*'  ):
$return .= <<<IPSCONTENT

	<span class='ipsBadge ipsBadge--positive'><i class='fa-regular fa-check-square'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unrestricted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<span class='ipsBadge ipsBadge--intermediary'><i class='fa-solid fa-triangle-exclamation'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restricted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function signatureLimits( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'after', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="0" class="ipsInput">
<select name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]" class="ipsInput ipsInput--select">
	<option value="0" 
IPSCONTENT;

if ( !$value[1] ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approved_posts_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="1" 
IPSCONTENT;

if ( $value[1] ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'days_since_joining', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
<div class="ipsFieldRow__inlineCheckbox">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<input type="checkbox" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[3]" id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_3' data-control="unlimited" 
IPSCONTENT;

if ( !$value[0] ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<label for='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_3' class='ipsField_unlimited'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'always', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
</div>
IPSCONTENT;

		return $return;
}

	function usernameChanges( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	<input type='checkbox' class="ipsInput ipsInput--toggle" id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_canchange' name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[canchange]" value="1" data-control="toggle" data-toggles="group_un_perms" 
IPSCONTENT;

if ( $value[0] ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> 
	<label for='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_canchange'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'group_allow_username_changes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
</div>
<br>
<div id="group_un_perms">
	<div data-role="unlimitedCatch">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'after', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="number" class="ipsInput ipsField_tiny" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]" min="0"> <select name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[2]" class="ipsInput ipsInput--select"><option value="0" 
IPSCONTENT;

if ( !$value[2] ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approved_posts_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option><option value="1" 
IPSCONTENT;

if ( $value[2] ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'days_since_joining', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option></select>  &nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp; 
		<input type='checkbox' class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[always]" id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_always' data-control="unlimited" 
IPSCONTENT;

if ( !$value[1] ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<label for='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_always' class='ipsField_unlimited'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'always', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
	</div>
	<br>
	<div data-role="unlimitedCatch">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'can_change_username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="number" class="ipsInput ipsField_tiny" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'times_every', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[3]" class="ipsInput ipsField_tiny" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[3], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="1"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'days', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp; 
		<input type='checkbox' class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[unlimited]" id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' value="1" data-control="unlimited" 
IPSCONTENT;

if ( $value[0] == -1 ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<label for='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' class='ipsField_unlimited'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unlimited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function usernameLengthSetting( $name, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'between', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="1" class="ipsInput ipsField_short">
&nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp;
<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" min="1" max="255" class="ipsInput ipsField_short">

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'characters_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function usernameRegexSetting( $name, $value, $easy ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="core.admin.members.allowedCharacters" data-easy="
IPSCONTENT;

if ( $easy ):
$return .= <<<IPSCONTENT
1
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[easy]" value="0" data-role="easyInput">
	<div data-role="easy" class="ipsHide">
		<ul class="ipsFieldListParent">
			<li>
				<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_letters', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				<ul class="ipsFieldList" role="radiogroup">
					<li>
						<input type="radio" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[letters]" value="all" id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_letters_all" 
IPSCONTENT;

if ( !$easy or $easy['letters'] === 'all' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<div class='ipsFieldList__content'>
							<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_letters_all'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_letters_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</div>
					</li>
					<li>
						<input type="radio" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[letters]" value="latin" id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_letters_latin" 
IPSCONTENT;

if ( $easy and $easy['letters'] === 'latin' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<div class='ipsFieldList__content'>
							<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_letters_latin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_letters_latin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</div>
					</li>
				</ul>
			</li>
			<li>
				<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_numbers', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				<ul class="ipsFieldList" role="radiogroup">
					<li>
						<input type="radio" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[numbers]" value="all" id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_numbers_all" 
IPSCONTENT;

if ( !$easy or $easy['numbers'] === 'all' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<div class='ipsFieldList__content'>
							<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_numbers_all'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_numbers_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</div>
					</li>
					<li>
						<input type="radio" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[numbers]" value="arabic" id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_numbers_arabic" 
IPSCONTENT;

if ( $easy and $easy['numbers'] === 'arabic' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<div class='ipsFieldList__content'>
							<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_letters_latin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_numbers_arabic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</div>
					</li>
					<li>
						<input type="radio" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[numbers]" value="none" id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_numbers_none" 
IPSCONTENT;

if ( $easy and $easy['numbers'] === 'none' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<div class='ipsFieldList__content'>
							<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_letters_latin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</div>
					</li>
				</ul>
			</li>
			<li>
				<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_special', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				<ul class="ipsFieldList" role="radiogroup">
					<li>
						<input type="checkbox" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[spaces]" value="1" id='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_spaces' 
IPSCONTENT;

if ( !$easy or $easy['spaces'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<div class='ipsFieldList__content'>
							<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_spaces'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_spaces', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
							<div class='ipsFieldRow__desc'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_spaces_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
						</div>
					</li>
					<li>
						<input type="checkbox" class="ipsInput ipsInput--toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[extra_enabled]" value="1" id='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_extra' 
IPSCONTENT;

if ( !$easy or $easy['extra'] ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-control="toggle" data-toggles="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_extra_input">
						<div class='ipsFieldList__content'>
							<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_extra'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_extra', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
							<div id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_extra_input">
								<input type="text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[extra]" value="
IPSCONTENT;

if ( $easy ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $easy['extra'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
_.-,
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							</div>
						</div>
					</li>
				</ul>
			</li>
		</ul>
		<a href="#" class="ipsHide" data-action="regex">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_regex', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
	<div data-role="regex">
		<input type="text" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[regex]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" placeholder="/^[A-Z0-9]+$/i"> <a href="#" class="ipsHide" data-action="easy">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_characters_easy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function warningTime( $name, $value, $prefixLang='after', $unlimitedLang='never' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$val = "{$prefixLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

<input type="number" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[0]" 
IPSCONTENT;

if ( is_array($value) AND $value[0] != -1 ):
$return .= <<<IPSCONTENT
value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $value[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 min="0" class="ipsInput ipsField_short">
<select class="ipsInput ipsInput--select" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[1]">
	<option value="h" 
IPSCONTENT;

if ( is_array($value) AND $value[1] == 'h' ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hours', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
	<option value="d" 
IPSCONTENT;

if ( is_array($value) AND $value[1] == 'd' ):
$return .= <<<IPSCONTENT
selected="selected"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'days', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
</select>
<div class="ipsFieldRow__inlineCheckbox">
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<input type="checkbox" class="ipsInput ipsInput--toggle" data-control="unlimited" id='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' 
IPSCONTENT;

if ( is_array($value) AND $value[0] == -1 ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[3]">
	<label for='el
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_unlimited' class='ipsField_unlimited'>
IPSCONTENT;

$val = "{$unlimitedLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
</div>
IPSCONTENT;

		return $return;
}}