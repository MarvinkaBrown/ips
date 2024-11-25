<?php
namespace IPS\Theme;
class class_core_admin_notifications extends \IPS\Theme\Template
{	function accountDeletionRequest( $users ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsGrid i-basis_400'>
	
IPSCONTENT;

foreach ( $users as $user  ):
$return .= <<<IPSCONTENT

		<div class='ipsBox i-flex i-padding_2' data-role='validatingRow'>
			<div class='ipsPhotoPanel ipsPhotoPanel_mini i-flex_11'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $user, 'mini' );
$return .= <<<IPSCONTENT

				<div>
					<h4 class='ipsPhotoPanel__primary'>
						<strong><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='userName'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></strong>
						<span class='i-color_soft i-font-weight_normal i-font-size_-1'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span>
					</h4>
					<ul class='ipsPhotoPanel__secondary'>
						<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->ip_address, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
						<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_awaiting_registered', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;

$val = ( $user->joined instanceof \IPS\DateTime ) ? $user->joined : \IPS\DateTime::ts( $user->joined );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					</ul>
				</div>
			</div>
			<ul class='ipsButtons'>
				<li><a
					href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=privacy&do=approveDeletion&id={$user->_privacy_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'
					data-action='approve' class='ipsButton ipsButton--tiny ipsButton--positive' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip><i class='fa-solid fa-check'></i></a></li>
				<li><a
					href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=privacy&do=rejectDeletion&id={$user->_privacy_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'
					data-action='reject' class='ipsButton ipsButton--tiny ipsButton--negative' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reject', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
					data-ipsTooltip><i class='fa-solid fa-xmark'></i></a></li>
			</ul>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function adminValidations( $users ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsGrid i-basis_400" data-controller="core.admin.dashboard.validation">
	
IPSCONTENT;

foreach ( $users as $user  ):
$return .= <<<IPSCONTENT

		<div class='ipsBox i-flex i-padding_2' data-role="validatingRow">
			<div class='ipsPhotoPanel ipsPhotoPanel_mini i-flex_11'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $user, 'mini' );
$return .= <<<IPSCONTENT

				<div>			
					<h4 class='ipsPhotoPanel__primary'><strong><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="userName">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></strong> <span class='i-color_soft i-font-weight_normal i-font-size_-1'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span></h4>
					<ul class='ipsPhotoPanel__secondary'>
						<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->ip_address, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
						<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_awaiting_registered', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;

$val = ( $user->joined instanceof \IPS\DateTime ) ? $user->joined : \IPS\DateTime::ts( $user->joined );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					</ul>
				</div>
			</div>
			<ul class='ipsButtons' data-role='validateToggles'>
				<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=approve&id={$user->member_id}&queue=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action='approve' class='ipsButton ipsButton--tiny ipsButton--positive' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip><i class="fa-solid fa-check"></i></a></li>
				<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=ban&permban=1&id={$user->member_id}&queue=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action='ban' class='ipsButton ipsButton--tiny ipsButton--negative'  title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ban', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip><i class="fa-solid fa-xmark"></i></a></li>
			</ul>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function dangerousPhpFunctions( $functions ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disable_functions_desc_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<p class="ipsRichText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disable_functions_desc_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class="i-margin-top_1">
	
IPSCONTENT;

foreach ( $functions as $function ):
$return .= <<<IPSCONTENT

		<span class="ipsType_code">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $function, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function displayErrors(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'display_errors_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
IPSCONTENT;

		return $return;
}

	function errorLog( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$table}
<div class="i-margin-top_4">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=errorLogs", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'support', 'diagnostic_log_settings' ) ):
$return .= <<<IPSCONTENT

		&nbsp;
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=support&controller=errorLogs&do=settings", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--tiny" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function failedMail( $count, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->formatNumber( $count )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_email_broken_desc_1', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
</div>

IPSCONTENT;

if ( $table ):
$return .= <<<IPSCONTENT

	<div class="i-margin-top_1">	
		{$table}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsRichText i-margin-top_4">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_email_broken_desc_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class="i-margin-top_1">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=email", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>

IPSCONTENT;

		return $return;
}

	function index( $notifications ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller="core.global.core.notificationList" class="cNotificationList i-grid i-gap_1">
	
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->highlightedId ) and array_key_exists( \IPS\Widget\Request::i()->highlightedId, $notifications ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "notifications", \IPS\Request::i()->app )->indexBlock( $notifications[ \IPS\Request::i()->highlightedId ], TRUE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

foreach ( $notifications as $id => $notification ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->highlightedId ) or $id != \IPS\Widget\Request::i()->highlightedId ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "notifications", \IPS\Request::i()->app )->indexBlock( $notification );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	<div class="i-padding_3 i-color_soft i-text-align_center 
IPSCONTENT;

if ( \count( $notifications ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="empty">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results_notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
</div>

IPSCONTENT;

		return $return;
}

	function indexBlock( $notification, $spacer=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-role="notificationBlock">
	<div class="ipsBox cNotification cNotification_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification->style(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

$dismissible = $notification->dismissible();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $dismissible !== $notification::DISMISSIBLE_NO ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=notifications&do=dismiss&id={$notification->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="cNotification_dismiss" title="
IPSCONTENT;

$val = "acp_notification_dismiss_{$dismissible}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip data-action="dismiss">
				<i class="fa-solid fa-xmark"></i>
			</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<h3 class='ipsBox__header'>
			{$notification->title()}
		</h3>
		<div class='i-padding_3'>
			{$notification->body()}
		</div>
	</div>
	
IPSCONTENT;

if ( $spacer ):
$return .= <<<IPSCONTENT

		<hr class="ipsHr i-margin-block_4">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function licenseKey( $id, $type ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	
IPSCONTENT;

if ( $type === 'expired' or $type === 'expireSoon' ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_benefits_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_url_invalid_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<div class="i-margin-top_3">
	<ul class="ipsButtons" 
IPSCONTENT;

if ( $type === 'expired' or $type === 'expireSoon' ):
$return .= <<<IPSCONTENT
data-controller="core.global.core.licenseRenewal" data-surveyUrl="
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/renewal_survey" );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		
IPSCONTENT;

if ( $type === 'expired' or $type === 'expireSoon' ):
$return .= <<<IPSCONTENT

			<li>
				<a href='
IPSCONTENT;

$return .= \IPS\Http\Url::ips( "docs/renew_my_license" );
$return .= <<<IPSCONTENT
' target="_blank" class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_renew_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
			<li>
				<a href='
IPSCONTENT;

if ( \IPS\Dispatcher::i()->controllerLocation === 'front' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=dismissAcpNotification&id={$id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=notifications&do=dismiss&id={$id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action="notNow" class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_renewal_not_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<li>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=licensekey&do=refresh" . "&csrfKey=" . \IPS\Session::i()->csrfKey, "admin", "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'license_check_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</li>
	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function lockedTask( $task, $description ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_tasks_broken_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<i-data>
	<ul class="ipsData ipsData--table ipsData--compact ipsData--locked-task i-margin-top_1">
		<li class="ipsData__item">
			<span class="i-basis_160"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'task_manager_app', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<div>
				<div>
						
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Application::load( $task->app )->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</div>
			</div>
		</li>
		<li class="ipsData__item">
			<span class="i-basis_160"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'task_manager_key', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<div>
				<div>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $task->key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $description ):
$return .= <<<IPSCONTENT

						<br>
						<span class="i-color_soft">
IPSCONTENT;

$val = "{$description}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</li>
		<li class="ipsData__item">
			<span class="i-basis_160"><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'task_manager_last_run', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
			<div>
				<div>
					
IPSCONTENT;

if ( $task->last_run ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$val = ( $task->last_run instanceof \IPS\DateTime ) ? $task->last_run : \IPS\DateTime::ts( $task->last_run );$return .= $val->html();
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<em>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'never', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</li>
	</ul>
</i-data>
<div class="ipsRichText i-margin-top_1">
	
IPSCONTENT;

if ( $task->app and \in_array( $task->app, \IPS\IPS::$ipsApps ) ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_tasks_broken_desc_firstparty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_tasks_broken_desc_thirdparty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<div class="i-margin-top_3">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=runTask&id={$task->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'task_manager_run', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	&nbsp;
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=taskLogs&id={$task->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'task_manager_logs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function newMember( $users, $notification, $more ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsGrid i-basis_400">
	
IPSCONTENT;

foreach ( $users as $user ):
$return .= <<<IPSCONTENT

		<div class='ipsBox i-flex i-padding_2'>
			<div class='ipsPhotoPanel ipsPhotoPanel_mini'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $user, 'mini' );
$return .= <<<IPSCONTENT

				<div>			
					<h4 class='ipsPhotoPanel__primary'><strong><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></strong> <span class='i-color_soft i-font-weight_normal i-font-size_-1'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span></h4>
					<ul class='ipsPhotoPanel__secondary'>
						<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->ip_address, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
						<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_awaiting_registered', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;

$val = ( $user->joined instanceof \IPS\DateTime ) ? $user->joined : \IPS\DateTime::ts( $user->joined );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					</ul>
				</div>
			</div>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $more > 1 ):
$return .= <<<IPSCONTENT

		<div class='ipsBox i-flex i-align-items_center i-justify-content_center i-padding_2 i-text-align_center'>
			<h4 class='i-font-size_2'><strong><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$pluralize = array( $more ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notification_NewRegComplete_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a></strong></h4>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<div class="i-color_soft">
	<span class="i-font-size_-1"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$sprintf = array($notification->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notification_NewRegComplete_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
</div>
IPSCONTENT;

		return $return;
}

	function origTables(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'orig_cleanup_suggested_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'orig_cleanup_suggested_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class="i-margin-top_1">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=notifications&do=removeOrigTables" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--tiny" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'orig_cleanup_suggested_go', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function piiRequest( $users ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsGrid i-basis_400'>
	
IPSCONTENT;

foreach ( $users as $user  ):
$return .= <<<IPSCONTENT

	<div class='ipsBox i-flex i-padding_2' data-role='validatingRow'>
		<div class='ipsPhotoPanel ipsPhotoPanel_mini i-flex_11'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $user, 'mini' );
$return .= <<<IPSCONTENT

			<div>
				<h4 class='ipsPhotoPanel__primary'><strong><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='userName'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></strong></h4>
				<div class="ipsPhotoPanel__secondary">
					<ul>
						<li>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</li>
						<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->ip_address, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
						<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_awaiting_registered', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong> 
IPSCONTENT;

$val = ( $user->joined instanceof \IPS\DateTime ) ? $user->joined : \IPS\DateTime::ts( $user->joined );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					</ul>
				</div>
			</div>
		</div>
		<ul class='ipsButtons'>
			<li><a
				href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=privacy&do=approvePii&id={$user->_privacy_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'
				data-action='approve' class='ipsButton ipsButton--tiny ipsButton--positive' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip><i class='fa-solid fa-check'></i></a></li>
			<li><a
				href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=privacy&do=rejectPii&id={$user->_privacy_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'
				data-action='reject' class='ipsButton ipsButton--tiny ipsButton--negative' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reject', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
				data-ipsTooltip><i class='fa-solid fa-xmark'></i></a></li>
		</ul>
	</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function popupList( $notifications ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $notifications ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $notifications as $notification ):
$return .= <<<IPSCONTENT

		<li class="ipsData__item cNotification_row cNotification_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification->style(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification->link(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>{$notification->title()}</span></a>
			<div class='ipsData__main'>
				<h4 class="ipsData__title">{$notification->title()}</h4>
				<p class="ipsData__desc">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification->subtitle(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
			</div>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<li class="ipsData__item">
		<div class="i-padding_3 i-color_soft i-text-align_center">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results_notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function siteOffline(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offline_message_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->task_use_cron == 'normal' AND !\IPS\CIC ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dasbhoard_tasks_site_offline_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>
</div>
IPSCONTENT;

		return $return;
}

	function spammer( $users, $notification, $more ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsGrid i-basis_400">
	
IPSCONTENT;

foreach ( $users as $user ):
$return .= <<<IPSCONTENT

		<div class='ipsBox i-flex i-padding_2'>
			<div class='ipsPhotoPanel ipsPhotoPanel_mini'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $user['member'], 'mini' );
$return .= <<<IPSCONTENT

				<div>
					<h4 class='ipsPhotoPanel__primary'><strong><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user['member']->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="userName">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user['member']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></strong> &nbsp;&nbsp;<span class='i-color_soft i-font-weight_normal'>(
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user['member']->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</span></h4>
					<p class="ipsPhotoPanel__secondary">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user['blurb'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
				</div>
			</div>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $more > 1 ):
$return .= <<<IPSCONTENT

		<div class='ipsBox i-flex i-align-items_center i-justify-content_center i-padding_2 i-text-align_center'>
			<h4 class='i-font-size_2'><strong><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&filter=members_filter_spam", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$pluralize = array( $more ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notification_NewRegComplete_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a></strong></h4>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<div class="i-color_soft">
	<span class="i-font-size_-1"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$sprintf = array($notification->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_notification_Spammer_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
</div>
IPSCONTENT;

		return $return;
}

	function supportAccountPresent( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_support_account_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class="i-margin-top_1">
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_support_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	&nbsp;
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString('do', 'delete'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm class="ipsButton ipsButton--negative ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}

	function systemRecommendations( $advice ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'system_check_recommended_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<ul class="i-margin-top_1">
	
IPSCONTENT;

foreach ( $advice as $item ):
$return .= <<<IPSCONTENT

		<li>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
<div class="ipsRichText i-margin-top_1">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'system_check_recommended_footer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
IPSCONTENT;

		return $return;
}

	function tasksNotRunning( $notification, $cronCommand, $webCronUrl ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	
IPSCONTENT;

if ( \IPS\Settings::i()->task_use_cron === 'cron' ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_tasks_cron_broken_desc_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_tasks_cron_broken_desc_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<pre class="ipsType_code">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cronCommand, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
	
IPSCONTENT;

elseif ( \IPS\Settings::i()->task_use_cron === 'web' ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_tasks_web_broken_desc_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_tasks_web_broken_desc_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<pre class="ipsType_code">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $webCronUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</pre>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_tasks_not_enough_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<div class="i-margin-top_3">
	<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&tab=settings&searchResult=task_use_cron", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--primary ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'help_me_fix_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	&nbsp; <span class="i-font-size_-1"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$sprintf = array($notification->id); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dashboard_tasks_fix_info', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
</div>
IPSCONTENT;

		return $return;
}

	function usernameLoginEnabled(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsRichText">
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_login_enabled_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class="i-margin-top_1">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=login", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username_login_enabled_button', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</div>
IPSCONTENT;

		return $return;
}}