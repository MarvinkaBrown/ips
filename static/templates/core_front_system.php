<?php
namespace IPS\Theme;
class class_core_front_system extends \IPS\Theme\Template
{	function analyticsItemLink( $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_view_moderation_log') ):
$return .= <<<IPSCONTENT

    <a class='ipsButton ipsButton--text' href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->setQueryString('do', 'analytics'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'analytics_and_stats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'analytics_and_stats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-line-chart"></i></a>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function announcement( $announcement ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<div>
	<div class='i-padding_3 ipsPhotoPanel ipsPhotoPanel--tiny'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $announcement->author(), 'tiny' );
$return .= <<<IPSCONTENT

		<div>
			<p class='i-font-size_2 i-link-color_inherit'>
				
IPSCONTENT;

$htmlsprintf = array($announcement->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

	            
IPSCONTENT;

if ( $announcement->start ):
$return .= <<<IPSCONTENT

	                <br>
	                <span class='i-color_soft'>
IPSCONTENT;

$val = ( $announcement->start instanceof \IPS\DateTime ) ? $announcement->start : \IPS\DateTime::ts( $announcement->start );$return .= (string) $val->localeDate();
$return .= <<<IPSCONTENT
</span>
	            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		</div>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class="i-padding_3">
		<div class='ipsType_pageHeader'>
			<h1 class='ipsTitle ipsTitle--h3'><span class=''>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->mapped( 'title' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></h1>
			
IPSCONTENT;

if ( !$announcement->active ):
$return .= <<<IPSCONTENT

				<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announcement_not_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $announcement->author(), 'tiny' );
$return .= <<<IPSCONTENT

			<div>
				<p class='i-font-size_2 i-link-color_inherit'>
					
IPSCONTENT;

$htmlsprintf = array($announcement->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $announcement->start ):
$return .= <<<IPSCONTENT

						<br>
						<span class='i-color_soft'>
IPSCONTENT;

$val = ( $announcement->start instanceof \IPS\DateTime ) ? $announcement->start : \IPS\DateTime::ts( $announcement->start );$return .= (string) $val->localeDate();
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
			</div>
		</div>
	</div>
	<br>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<article class='ipsBox i-padding_3'>
		<section class='ipsRichText' data-controller='core.front.core.lightboxedImages'>
			{$announcement->mapped( 'content' )}
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_manage_announcements') and ( $announcement->canEdit() or $announcement->canDelete() ) ):
$return .= <<<IPSCONTENT

				<hr class='ipsHr'>
				<a href='#elAnnouncementActions
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' id='elAnnouncementActions
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--soft ipsButton--small' data-ipsMenu>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_actions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
				<ul id='elAnnouncementActions
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsMenu ipsMenu_auto ipsHide'>
					
IPSCONTENT;

if ( $announcement->canEdit() ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->url( 'create' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-modal='true' data-ipsDialog-destructOnClose='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_announcement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action='ipsMenu_ping'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $announcement->canDelete() ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->url( 'delete' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm  title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $announcement->url( 'status' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

if ( $announcement->active ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_mark_inactive', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_mark_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( $announcement->active ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_mark_inactive', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'announce_mark_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a></li>
				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</section>
	</article>

	
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

	function banned( $message, $warnings, $banEnd ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='i-text-align_center i-padding_3 ipsBox'>
	<i class='ipsLargeIcon fa-solid fa-lock'></i>
	<h1 class='ipsTitle ipsTitle--h2'>
IPSCONTENT;

if ( $banEnd instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'suspended', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'banned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h1>
	<p class='i-font-size_2'>
		
IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</p>
</section>


IPSCONTENT;

if ( $warnings ):
$return .= <<<IPSCONTENT

	<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warnings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	{$warnings}

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function completeProfile( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


<section class='i-padding_3'>
	<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'need_more_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<p class='i-font-size_2 i-text-align_center i-color_soft i-margin-bottom_4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'need_more_info_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<div class='ipsBox i-padding_3'>
		{$form}
	</div>
</section>

IPSCONTENT;

		return $return;
}

	function completeValidation( $member, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='i-text-align_center' data-ipsForm data-ipsFormSubmit>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
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

endforeach;
$return .= <<<IPSCONTENT


	<div class='i-text-align_center ipsBox i-padding_3'>
		<i class='ipsLargeIcon fa-solid fa-envelope-open-text'></i>
		<h1 class='ipsTitle ipsTitle--h2'>
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'registration_validate_heading', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
		<div class="ipsRichText i-font-size_2 i-margin-block_3">
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'registration_validate_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
		<div class="ipsButtons">
			<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'validate_my_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function completeWizardTemplate( $stepNames, $activeStep, $output, $baseUrl, $showSteps ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-ipsWizard class='ipsWizard'>
	<div data-role="wizardStepbar">
		
IPSCONTENT;

$completion = \intval( (string) \IPS\Member::loggedIn()->profileCompletionPercentage() );
$return .= <<<IPSCONTENT

		<progress class="ipsProgress" max='100' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->profileCompletionPercentage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></progress>
		<small class='i-color_soft i-display_block'>
IPSCONTENT;

$sprintf = array($completion . '%'); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_completion_percent', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</small>
	</div>
	<div data-role="wizardContent" class="ipsBox">
		{$output}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function contact( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('contact') );
$return .= <<<IPSCONTENT

	<div class='ipsBox'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div>
			{$form}
		</div>
	</div>

IPSCONTENT;

		return $return;
}

	function contactConfirmVerify( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox'>
	<div class="ipsBox__padding">
		<div class="i-text-align_center">
			<i class='ipsLargeIcon fa fa-envelope'></i>
			<h1 class='ipsTitle ipsTitle--h2 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact_verify', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		</div>
		<div class='i-font-size_2'>
			{$form}
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function contactDone(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox'>
	<div class="ipsBox__padding i-text-align_center">
		<i class='ipsLargeIcon fa-solid fa-envelope'></i>
		<h1 class='ipsTitle ipsTitle--h3 i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact_sent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<p class='i-font-size_2'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact_sent_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
	</div>
	<p class='ipsSubmitRow'>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "/", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_community_home', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</p>
</div>
IPSCONTENT;

		return $return;
}

	function contactFormReferrer( $referrer ) {
		$return = '';
		$return .= <<<IPSCONTENT

<p>
	
IPSCONTENT;

$return .= sprintf( \IPS\Member::loggedIn()->language()->get( 'contact_form_referrer' ), $referrer, $referrer );
$return .= <<<IPSCONTENT

</p>
IPSCONTENT;

		return $return;
}

	function contactVerify(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<br><br>
<div class='ipsBox_alt'>
	<p class='i-text-align_center i-font-size_6'>
		<i class='fa fa-envelope'></i>
	</p>

	<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact_verify', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>

	<div class='i-font-size_2 i-text-align_center ipsRichText'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contact_verify_your_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function cookies(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$prefix = \IPS\COOKIE_PREFIX;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('cookies_about') );
endif;
$return .= <<<IPSCONTENT

<div class='ipsBox'>
	<div class='ipsBox__padding ipsRichText'>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookies_about_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
 	    <h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookies_standard', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookie_standard_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
        <details>
	        <summary>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookies_standard', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</summary>
	        <div>
				
IPSCONTENT;

foreach ( \IPS\Request::getEssentialCookies() as $cookie ):
$return .= <<<IPSCONTENT

					<h4>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $prefix, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cookie, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
					<div>
IPSCONTENT;

$val = "cookie_{$cookie}_description"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	        </div>
	    </details>
	    
IPSCONTENT;

if ( \IPS\Widget\Request::i()->cookieConsentEnabled() ):
$return .= <<<IPSCONTENT

	        <h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookies_optional', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	            
IPSCONTENT;

$currentUrl = base64_encode((string) \IPS\Widget\Request::i()->url());
$return .= <<<IPSCONTENT

	            
IPSCONTENT;

$status = (int) !\IPS\Member::loggedIn()->optionalCookiesAllowed;
$return .= <<<IPSCONTENT

	            <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=cookies&do=cookieConsentToggle&ref={$currentUrl}&status={$status}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' rel='nofollow'>
	                
IPSCONTENT;

$allowed = $status ? "" : 'data-checked' ;
$return .= <<<IPSCONTENT

					<span class="ipsSwitch i-font-size_3 i-margin-start_2" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $allowed, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
></span>
	            </a>
	        </h3>
	        <p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookies_optional_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->checkKeyExists('cookie_3rdpartynotice_value') AND !empty( \IPS\Member::loggedIn()->language()->get('cookie_3rdpartynotice_value') ) ):
$return .= <<<IPSCONTENT

			<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookies_third_party', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookie_3rdpartynotice_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookies_change_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cookies_change_text', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
    </div>
</div>
IPSCONTENT;

		return $return;
}

	function coppa( $form, $postBeforeRegister ) {
		$return = '';
		$return .= <<<IPSCONTENT


<section class='i-padding_3'>
	<br>
	
IPSCONTENT;

if ( $postBeforeRegister ):
$return .= <<<IPSCONTENT

		<h1 class='ipsTitle ipsTitle--h2 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_before_register_headline', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<p class='i-font-size_2 i-text-align_center i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_before_register_subtext', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<h1 class='ipsTitle ipsTitle--h2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<p class='i-font-size_2 i-text-align_center i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'existing_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<br>

	<div data-role='registerForm' class='ipsBox i-padding_3'>
		<section class='i-text-align_center'>
			<p class='i-font-size_2'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_verify', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></p>
			<p class='i-color_soft'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_verification_only', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Settings::i()->privacy_type != "none" ):
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;

if ( \IPS\Settings::i()->privacy_type == "internal" ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=privacy", null, "privacy", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Settings::i()->privacy_link;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'privacy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>.
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
			<br><br>

			{$form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'system', 'core', 'front' ), 'coppaForm' ) )}
		</section>
	</div>
</section>
IPSCONTENT;

		return $return;
}

	function coppaConsent(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsPrint">
	<h1>
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
</h1>
	<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>

	
IPSCONTENT;

$sprintf = array(\IPS\Settings::i()->board_name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	
	<table>
		<tr>
			<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_child_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </th>
			<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_child_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </th>
		</tr>
		<tr>
			<td class="ipsPrint_doubleHeight">&nbsp;</td>
			<td class="ipsPrint_doubleHeight">&nbsp;</td>
		</tr>
	</table>
	
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_disclaimer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	
	<div></div>
	<div></div>

	<table>
		<tr>
			<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </th>
			<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_relation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </th>
		</tr>
		<tr>
			<td class="ipsPrint_doubleHeight">&nbsp;</td>
			<td class="ipsPrint_doubleHeight">&nbsp;</td>
		</tr>
		<tr>
			<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </th>
			<th>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_phone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </th>
		</tr>
		<tr>
			<td class="ipsPrint_doubleHeight">&nbsp;</td>
			<td class="ipsPrint_doubleHeight">&nbsp;</td>
		</tr>
		<tr>
			<th colspan="2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_sig', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </th>
		</tr>
		<tr>
			<td colspan="2" class="ipsPrint_tripleHeight">&nbsp;</td>
		</tr>
		<tr>
			<th colspan="2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 </th>
		</tr>
		<tr>
			<td colspan="2" class="ipsPrint_doubleHeight">&nbsp;</td>
		</tr>
	</table>

	<div></div>

	
IPSCONTENT;

if ( \IPS\Settings::i()->privacy_type != "none" ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_privacy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Settings::i()->privacy_type == "internal" ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=privacy", null, "privacy", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Settings::i()->privacy_link;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>
		<div></div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->coppa_address ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_mail', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\GeoLocation::parseForOutput( \IPS\Settings::i()->coppa_address );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->coppa_fax ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

if ( \IPS\Settings::i()->coppa_address and \IPS\Settings::i()->coppa_fax ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_form_fax', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Settings::i()->coppa_fax;
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function coppaForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='elCoppaForm' class='i-text-align_center' data-ipsForm>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
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

endforeach;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Date ):
$return .= <<<IPSCONTENT

				<input type="date" class='ipsInput ipsInput--text ipsField_short ipsInput--primary' required placeholder="
IPSCONTENT;

$val = "{$input->name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' max='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->options["max"]->format( "Y-m-d" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	&nbsp;&nbsp;<button type='submit' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'continue', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
</form>
IPSCONTENT;

		return $return;
}

	function finishRegistration( $harryPotter ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='i-padding_3'>
	<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'complete_your_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<br>
	{$harryPotter}
</section>
IPSCONTENT;

		return $return;
}

	function followedContent( $types, $currentAppModule, $currentType, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('menu_followed_content') );
$return .= <<<IPSCONTENT

	<div data-role="profileContent">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsColumns ipsColumns--lines ipsColumns--followed-content ipsBox ipsPull">
			<aside class="ipsColumns__secondary i-basis_340 i-padding_3">
				<div class="ipsSideMenu" data-ipsTabBar data-ipsTabBar-contentArea='#elFollowedContent' data-ipsTabBar-itemselector=".ipsSideMenu_item" data-ipsTabBar-activeClass="ipsSideMenu_itemActive" data-ipsSideMenu>
					<h3 class="ipsSideMenu__view">
						<a href="#user_content" data-action="openSideMenu"><i class="fa-solid fa-bars"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_content_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					</h3>
					<div class="ipsSideMenu__menu">
						
IPSCONTENT;

foreach ( $types as $app => $_types ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $app != "core" ):
$return .= <<<IPSCONTENT

								<h4 class='ipsSideMenu__subTitle'>
IPSCONTENT;

$val = "module__{$app}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
								<ul class="ipsSideMenu__list">
									
IPSCONTENT;

foreach ( $_types as $key => $class ):
$return .= <<<IPSCONTENT

										<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'type' => $key, 'change_section' => 1 ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsSideMenu_item 
IPSCONTENT;

if ( $currentType == $key ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( is_subclass_of( $class, 'IPS\Content\Item' ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$class::$title}_pl"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$class::$nodeTitle}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a></li>	
									
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

						<h4 class='ipsSideMenu__subTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'other', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
						<ul class='ipsSideMenu__list'>
							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'type' => 'core_member', 'change_section' => 1 ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsSideMenu_item 
IPSCONTENT;

if ( $currentType == 'core_member' ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'type' => 'core_tag', 'change_section' => 1 ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsSideMenu_item 
IPSCONTENT;

if ( $currentType == 'core_tag' ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						</ul>
					</div>
				</div>
			</aside>
			<section class='ipsColumns__primary' id='elFollowedContent'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->followedContentSection( $types, $currentAppModule, $currentType, (string) $table );
$return .= <<<IPSCONTENT

			</section>
		</div>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function followedContentMemberRow( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$loadedMember = \IPS\Member::load( $row->member_id );
$return .= <<<IPSCONTENT

	<li class='ipsData__item' data-controller='core.front.system.manageFollowed' data-followID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_area'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_rel_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<div class='ipsData__icon'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $loadedMember, 'small' );
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<div class='ipsData__title'>
					<h3>{$loadedMember->link( NULL, FALSE )}</h3> 
IPSCONTENT;

if ( $loadedMember->isOnline() ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle ipsOnlineStatus_online" data-ipsTooltip title='
IPSCONTENT;

$sprintf = array($row->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<span>
IPSCONTENT;

$return .= \IPS\Member\Group::load( $row->member_group_id )->formattedName;
$return .= <<<IPSCONTENT
</span>
				<ul class='ipsList ipsList--inline i-color_soft'>
					<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_member_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $loadedMember->member_posts, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
					<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_joined', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$val = ( $loadedMember->joined instanceof \IPS\DateTime ) ? $loadedMember->joined : \IPS\DateTime::ts( $loadedMember->joined );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

if ( $loadedMember->last_activity ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_last_visit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$val = ( $loadedMember->last_activity instanceof \IPS\DateTime ) ? $loadedMember->last_activity : \IPS\DateTime::ts( $loadedMember->last_activity );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
			<div>
				<span class='ipsBadge ipsBadge--icon ipsBadge--new 
IPSCONTENT;

if ( !$row->_followData['follow_is_anon'] ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='followAnonymous' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_is_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
			</div>

			<div class='i-basis_180'>
				<ul>
					<li title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_when', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='followDate'><i class='fa-regular fa-clock'></i> 
IPSCONTENT;

$val = ( $row->_followData['follow_added'] instanceof \IPS\DateTime ) ? $row->_followData['follow_added'] : \IPS\DateTime::ts( $row->_followData['follow_added'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					<li title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_how', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='followFrequency'>
						
IPSCONTENT;

if ( $row->_followData['follow_notify_freq'] == 'none' ):
$return .= <<<IPSCONTENT

							<i class='fa-regular fa-bell-slash'></i>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class='fa-solid fa-bell'></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "follow_freq_{$row->_followData['follow_notify_freq']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</li>
				</ul>
			</div>

			<div class='i-basis_180 i-text-align_center'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->manageFollow( $row->_followData['follow_app'], $row->_followData['follow_area'], $row->_followData['follow_rel_id'] );
$return .= <<<IPSCONTENT

			</div>
		</div>

		
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

			<div class='ipsData__mod'>
				<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='' class="ipsInput ipsInput--toggle">
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function followedContentSection( $types, $currentAppModule, $currentType, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='cFollowedContent'>
	<h2 class='i-font-size_3 i-font-weight_600 i-color_hard i-padding_2'>
IPSCONTENT;

if ( is_subclass_of( $types[ $currentAppModule ][ $currentType ], 'IPS\Content\Item' ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $types[ $currentAppModule ][ $currentType ]::$title . '_pl' )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stuff_i_follow', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $types[ $currentAppModule ][ $currentType ] == "\IPS\Member" ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_i_follow', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $types[ $currentAppModule ][ $currentType ]::$nodeTitle )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stuff_i_follow', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function followedContentTagRow( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li class="ipsData__item ipsData__item--manage-follow-node-row 
IPSCONTENT;

if ( method_exists( $row, 'tableClass' ) && $row->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-controller='core.front.system.manageFollowed' data-followID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_area'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_rel_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<h4 class='ipsData__title'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->text, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</a>
				</h4>
				<ul class='ipsList ipsList--inline i-color_soft'>
				    
IPSCONTENT;

$count = array_sum( $row->totals );
$return .= <<<IPSCONTENT

				    <li>
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tagged_items_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
				</ul>
			</div>

			<div>
				<span class='ipsBadge ipsBadge--icon ipsBadge--new 
IPSCONTENT;

if ( !$row->_followData['follow_is_anon'] ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='followAnonymous' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_is_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
			</div>

			<div class='i-basis_180'>
				<ul>
					<li title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_when', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='followDate'><i class='fa-regular fa-clock'></i> 
IPSCONTENT;

$val = ( $row->_followData['follow_added'] instanceof \IPS\DateTime ) ? $row->_followData['follow_added'] : \IPS\DateTime::ts( $row->_followData['follow_added'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					<li title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_how', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='followFrequency'>
						
IPSCONTENT;

if ( $row->_followData['follow_notify_freq'] == 'none' ):
$return .= <<<IPSCONTENT

							<i class='fa-regular fa-bell-slash'></i>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class='fa-solid fa-bell'></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "follow_freq_{$row->_followData['follow_notify_freq']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</li>
				</ul>
			</div>

			<div class='i-basis_180 i-text-align_center'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->manageFollow( $row->_followData['follow_app'], $row->_followData['follow_area'], $row->_followData['follow_rel_id'] );
$return .= <<<IPSCONTENT

			</div>
		</div>

		
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

			<div class='ipsData__mod'>
				<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='' class="ipsInput ipsInput--toggle">
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function followers( $url, $pagination, $followers, $anonymous, $removeAllUrl ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ipsInfScroll data-ipsInfScroll-scrollScope="#elFollowerList" data-ipsInfScroll-container="#elFollowerListContainer" data-ipsInfScroll-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsInfScroll-pageParam="followerPage" data-ipsInfScroll-pageBreakTpl="">
	<div class="ipsJS_hide">{$pagination}</div>
	
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
<div class='ipsBox'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsFollowerList i-padding_2 ipsScrollbar' id="elFollowerList">
			<i-data>
				<ul class="ipsData ipsData--table ipsData--compact ipsData--followers" id='elFollowerListContainer'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->followersRows( $followers );
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
			
IPSCONTENT;

if ( $anonymous ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $followers !== NULL and \count( $followers ) ):
$return .= <<<IPSCONTENT

					<div class="i-padding_2 i-text-align_center i-color_soft">
IPSCONTENT;

$pluralize = array( $anonymous ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_others', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class="i-padding_2 i-text-align_center i-color_soft">
IPSCONTENT;

$pluralize = array( $anonymous ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_anonymous_members', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_remove_followers') ):
$return .= <<<IPSCONTENT

			<ul class="ipsSubmitRow ipsButtons">
				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $removeAllUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmmessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_followers_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--negative">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_followers', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function followersRows( $followers ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $followers as $follower ):
$return .= <<<IPSCONTENT

	<li class='ipsData__item'>
		<div class='ipsData__icon'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $follower['follow_member_id'] ), 'tiny' );
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsData__main'>
			<strong class='ipsData__title'>
IPSCONTENT;

$link = \IPS\Member::load( $follower['follow_member_id'] )->link();
$return .= <<<IPSCONTENT
{$link}</strong>
			<span class='ipsData__meta'>
IPSCONTENT;

$val = ( $follower['follow_added'] instanceof \IPS\DateTime ) ? $follower['follow_added'] : \IPS\DateTime::ts( $follower['follow_added'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
		</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function followForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax()  ):
$return .= <<<IPSCONTENT
data-controller='core.front.core.followForm'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 accept-charset='utf-8' class="ipsFormWrap ipsFormWrap--follow" action="
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
 data-ipsForm >
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
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
		<input type="hidden" name="plupload" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div>
		<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
		<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--follow'>
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \is_string( $input ) ):
$return .= <<<IPSCONTENT

						<li>
							{$input}
						</li>
					
IPSCONTENT;

elseif ( $input instanceof \IPS\Helpers\Form\Radio ):
$return .= <<<IPSCONTENT

						<li class="ipsFieldRow">
							<strong class="ipsFieldRow__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_send_me', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							<div class="ipsFieldRow__content">{$input->html($form)}</div>
						</li>
					
IPSCONTENT;

elseif ( $input instanceof \IPS\Helpers\Form\Checkbox ):
$return .= <<<IPSCONTENT

						<li class="ipsFieldRow">{$input->html($form)}</li>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<li class="ipsFieldRow">{$input->rowHtml($form)}</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
	<div class="ipsSubmitRow ipsButtons">
		{$actionButtons[0]} 
IPSCONTENT;

if ( isset( $actionButtons[1] ) ):
$return .= <<<IPSCONTENT
{$actionButtons[1]}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</form>
IPSCONTENT;

		return $return;
}

	function guidelines( $guidelines ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	<header class='ipsPageHeader'>
		<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'guidelines', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	</header>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='ipsBox ipsPull ipsBox__padding ipsRichText'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('guidelines_value'), array('') );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

		return $return;
}

	function helpfulButton( $comment, $item ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $comment->canMarkHelpful() ):
$return .= <<<IPSCONTENT

<li class='ipsEntry__controls__helpful 
IPSCONTENT;

if ( $comment->markedHelpful() ):
$return .= <<<IPSCONTENT
 i-color_positive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( !$comment->markedHelpful() ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->csrf()->setQueryString( array( 'do' => 'toggleHelpful', 'answer' => $comment->pid ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='helpful'>
			<i class="fa-regular fa-thumbs-up"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !\IPS\Member::loggedIn()->group['gbw_view_helpful'] && $comment->helpfulCount() != 0 ):
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->helpfulCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		</a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->csrf()->setQueryString( array( 'do' => 'toggleHelpful', 'answer' => $comment->pid ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='helpful'>
			<i class="fa-solid fa-thumbs-up"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unmark_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !\IPS\Member::loggedIn()->group['gbw_view_helpful'] && $comment->helpfulCount() != 0 ):
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->helpfulCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_helpful'] && $comment->helpfulCount() != 0 ):
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->csrf()->setQueryString( array( 'do' => 'showHelpful', 'answer' => $comment->pid ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsEntry__controls-badge' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_users_found_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->helpfulCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>

IPSCONTENT;

elseif ( $comment->helpfulCount() != 0  ):
$return .= <<<IPSCONTENT

<li>
	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_helpful'] ):
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->csrf()->setQueryString( array( 'do' => 'showHelpful', 'answer' => $comment->pid ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_users_found_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-list-ul"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->helpfulCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->helpfulCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function ignore( $form, $table, $id=0 ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('ignored_users'), \IPS\Member::loggedIn()->language()->addToStack('ignored_users_blurb') );
$return .= <<<IPSCONTENT

<div data-controller='core.front.ignore.new' data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class='ipsBox ipsPull i-margin-bottom_block'>
		<div class="i-padding_3">
			<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ignored_users_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ignored_users_add_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
		{$form}
	</div>	
	<div>
		{$table}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function ignoreEditForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<form accept-charset='utf-8' id="elIgnoreForm" class="ipsFormWrap ipsFormWrap--ignore-edit" action="
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
 data-ipsForm>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
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

endforeach;
$return .= <<<IPSCONTENT

	<ul class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--ignore-edit">
		
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

			<li class='ipsFieldRow ipsFieldRow--fullWidth'>
				<ul class='ipsFieldRow__content ipsFieldRow__content--checkboxes'>
					
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Checkbox ):
$return .= <<<IPSCONTENT

							<li>
								<label class="i-flex i-align-items_center i-gap_1">
									{$input->html()}
									
IPSCONTENT;

$val = "{$input->name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</label>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		<li class='ipsSubmitRow'>
			
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

				{$button}
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</li>
	</ul>
</form>
IPSCONTENT;

		return $return;
}

	function ignoreForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' id="elIgnoreForm" class="ipsFormWrap ipsFormWrap--ignore" action="
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
 data-ipsForm>
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
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

endforeach;
$return .= <<<IPSCONTENT

	<ul class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--vertical ipsForm--ignore">
		
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !( $input instanceof \IPS\Helpers\Form\Checkbox ) ):
$return .= <<<IPSCONTENT

					<li class='ipsFieldRow ipsFieldRow--noLabel ipsFieldRow--fullWidth'>
						<div class='ipsFieldRow__content'>
							{$input->html()}
							
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

								<br>
								<span class="i-color_warning">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<li class='ipsFieldRow ipsFieldRow--fullWidth' id='elIgnoreTypes'>
				<strong class='ipsFieldRow__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ignored_users_ignore', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				<ul class='ipsFieldRow__content ipsFieldRow__content--checkboxes'>
					
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Checkbox ):
$return .= <<<IPSCONTENT

							<li>
								{$input->html()}
								<label for='check_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$input->name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		<li class='ipsFieldRow' id='elIgnoreSubmitRow'>
			<div class='ipsFieldRow__content'>
				
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

					{$button}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</li>
	</ul>
	<div id='elIgnoreLoading'></div>
</form>
IPSCONTENT;

		return $return;
}

	function ignoreTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox ipsPull' data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.global.core.table' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( $table->title ):
$return .= <<<IPSCONTENT

		<h2 class='ipsBox__header'>
IPSCONTENT;

$val = "{$table->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsButtonBar ipsButtonBar--top">
		
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

			<div data-role="tablePagination" class='ipsButtonBar__pagination'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar__end">
			<ul class="ipsDataFilters">
				
IPSCONTENT;

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

					<li>
						<a href="#elFilterByMenu_menu" class="ipsDataFilters__button" data-role="tableFilterMenu" id="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i></a>
						<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
							<li data-ipsMenuValue='' class='ipsMenu_item 
IPSCONTENT;

if ( !$table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection, 'filter' => '', 'group' => \IPS\Request::i()->group ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
							
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

								<li data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsMenu_item 
IPSCONTENT;

if ( $k === $table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection, 'group' => \IPS\Request::i()->group ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='cIgnoreType_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$table->langPrefix}{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	</div>
	<i-data class="i-position_relative i-z-index_1">
		<ol class="ipsData ipsData--table ipsData--ignored-users" id='elIgnoreUsers' data-role='tableRows'>
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

		</ol>
	</i-data>
	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--bottom">
			<div class="ipsButtonBar__pagination" data-role="tablePagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function ignoreTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty($rows) ):
$return .= <<<IPSCONTENT

	<li class='ipsEmptyMessage'>
		<div class=''>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</li>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $r ):
$return .= <<<IPSCONTENT

		<li class='ipsData__item' id='elIgnoreRow
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['ignore_ignore_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="ignoreRow" data-ignoreUserID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['ignore_ignore_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.front.ignore.existing'>
			<div class='ipsData__icon'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $r['ignore_ignore_id'] ), 'fluid' );
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsData__content">
				<div class='ipsData__main'>
					<h4 class='ipsData__title' data-role="ignoreRowName">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $r['ignore_ignore_id'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
					<ul class='ipsList ipsList--inline i-margin-top_1 i-gap_2'>
						
IPSCONTENT;

foreach ( \IPS\core\Ignore::types() as $t ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $r["ignore_{$t}"] ):
$return .= <<<IPSCONTENT

								<li><i class="fa-solid fa-ban i-font-size_-2 i-opacity_5"></i> 
IPSCONTENT;

$val = "ignore_$t"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						<li>
							<a href='#elUserIgnore
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['ignore_ignore_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' id='elUserIgnore
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['ignore_ignore_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsMenu data-ipsMenu-appendTo='#elIgnoreRow
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['ignore_ignore_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='ignoreMenu'>
								<i class='fa-solid fa-gear'></i> <i class='fa-solid fa-caret-down'></i>
							</a>
						</li>
					</ul>
					<ul class='ipsMenu ipsHide ipsJS_hide' id='elUserIgnore
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['ignore_ignore_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
						<li class='ipsMenu_item' data-ipsMenuValue='edit'>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore&do=edit&id={$r['ignore_ignore_id']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-remoteSubmit data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $r['ignore_ignore_id'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_ignore_for', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_ignored_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
						<li class='ipsMenu_item' data-ipsMenuValue='remove'>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore&do=remove&id={$r['ignore_ignore_id']}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stop_ignoring_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					</ul>
				</div>
			</div>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function invite( $links, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3'>
	<h4 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_invite', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
	<hr class='ipsHr'>
	<h5>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'link_to_site', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h5>

	<input type='text' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsInput ipsInput--text ipsInput--wide'>

	
IPSCONTENT;

if ( \count( $links )  ):
$return .= <<<IPSCONTENT

	<h5 class='i-margin-top_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'share_externally', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h5>
	<ul class='ipsList ipsList--inline i-gap_0'>
		
IPSCONTENT;

foreach ( $links as $link  ):
$return .= <<<IPSCONTENT

		<li>{$link}</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
	<hr class='ipsHr'>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<button class='ipsHide ipsButton ipsButton--small ipsButton--soft ipsButton--wide i-margin-top_2' data-controller='core.front.core.webshare' data-role='webShare' data-webShareTitle='
IPSCONTENT;

$sprintf = array(\IPS\Settings::i()->board_name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_invite_text', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-webShareText='
IPSCONTENT;

$sprintf = array(\IPS\Settings::i()->board_name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_invite_text', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-webShareUrl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

if ( \count( $links ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_share_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'share', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</button>
</div>
IPSCONTENT;

		return $return;
}

	function login( $login, $ref, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/login", "login:before", [ $login,$ref,$error ] );
$return .= <<<IPSCONTENT
<form data-ips-hook="login" accept-charset="utf-8" method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/login", "login:inside-start", [ $login,$ref,$error ] );
$return .= <<<IPSCONTENT

	<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

if ( $ref ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="ref" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $ref, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
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

if ( $usernamePasswordMethods and $buttonMethods ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

			<div class="ipsMessage ipsMessage--error i-margin-bottom_block">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox ipsPull
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-overflow_hidden">
			<div class="ipsColumns ipsColumns--login ipsColumns--lines">
				<div class="ipsColumns__primary">
					
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

						<div class="i-padding_3">
							<h1 class="ipsTitle ipsTitle--h2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
							
IPSCONTENT;

if ( \IPS\Login::registrationType() != 'disabled' ):
$return .= <<<IPSCONTENT

								<p class="i-font-size_2 i-color_soft i-font-weight_500">
									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dont_have_an_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'redirect' ):
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_reg_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										</a><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'normal' ):
$return .= <<<IPSCONTENT
data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</p>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->loginForm( $login );
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsColumns__secondary i-basis_360 i-padding_3">
					<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_faster', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					
IPSCONTENT;

if ( \count( $buttonMethods ) > 1 ):
$return .= <<<IPSCONTENT

						<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_with_these', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div class="i-grid i-gap_1 i-margin-top_2">
						
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

							<div class="cLogin_social">
								{$method->button()}
							</div>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		</div>
	
IPSCONTENT;

elseif ( $usernamePasswordMethods ):
$return .= <<<IPSCONTENT

		<div class="cLogin_single">
		
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

			<p class="ipsMessage ipsMessage--error i-margin-bottom_block">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox ipsPull
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

					<div class="i-padding_3">
						<h1 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
						
IPSCONTENT;

if ( \IPS\Login::registrationType() != 'disabled' ):
$return .= <<<IPSCONTENT

							<p class="i-font-size_2 i-color_soft">
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'dont_have_an_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'redirect' ):
$return .= <<<IPSCONTENT

									<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_reg_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									</a><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \IPS\Login::registrationType() == 'normal' ):
$return .= <<<IPSCONTENT
data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->loginForm( $login );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

elseif ( $buttonMethods ):
$return .= <<<IPSCONTENT

		<div class="cLogin_single">
			
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

				<p class="ipsMessage ipsMessage--error i-margin-bottom_block">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="i-gap_2 i-margin-top_2">
				
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

					<div class="i-text-align_center">
						{$method->button()}
					</div>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/login", "login:inside-end", [ $login,$ref,$error ] );
$return .= <<<IPSCONTENT
</form>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/login", "login:after", [ $login,$ref,$error ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function loginForm( $login ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/loginForm", "loginForm:before", [ $login ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="loginForm" class="ipsForm">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/loginForm", "loginForm:inside-start", [ $login ] );
$return .= <<<IPSCONTENT

	<li class="ipsFieldRow ipsFieldRow--fullWidth">
		<label class="ipsFieldRow__label" for="auth">
            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			<span class="ipsFieldRow__required">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</label>
		<div class="ipsFieldRow__content">
            <input type="email" class="ipsInput ipsInput--text" placeholder="
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
		<label class="ipsFieldRow__label" for="password">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			<span class="ipsFieldRow__required">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</label>
		<div class="ipsFieldRow__content">
			<input type="password" class="ipsInput ipsInput--text" placeholder="
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
	<li class="ipsFieldRow ipsFieldRow--checkbox">
		<input type="checkbox" name="remember_me" id="remember_me_checkbox" value="1" checked class="ipsInput ipsInput--toggle">
		<div class="ipsFieldRow__content">
			<label class="ipsFieldRow__label" for="remember_me_checkbox">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remember_me', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
			<div class="ipsFieldRow__desc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remember_me_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		</div>
	</li>
	<li class="ipsFieldRow ipsFieldRow--fullWidth">
		<button type="submit" name="_processLogin" value="usernamepassword" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'login', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password != 'disabled' ):
$return .= <<<IPSCONTENT

			<p class="i-text-align_end i-font-size_-2">
				
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password == 'redirect' ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_forgot_password_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					</a><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=lostpass", null, "lostpassword", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \IPS\Helpers\Form\Captcha::supportsModal() ):
$return .= <<<IPSCONTENT
data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</li>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/loginForm", "loginForm:inside-end", [ $login ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/loginForm", "loginForm:after", [ $login ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function lostPass( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('lost_password') );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_3'>
	{$form}
</div>

IPSCONTENT;

		return $return;
}

	function lostPassConfirm( $message ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('lost_password') );
$return .= <<<IPSCONTENT

<div class='ipsBox ipsBox--padding'>
	
IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function manageFollow( $app, $area, $id ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-followApp='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-followArea='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $area, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-followID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-buttonType='manage' data-controller='core.front.core.followButton'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->manageFollowButton( $app, $area, $id );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function manageFollowButton( $app, $area, $id ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->following( $app, $area, $id ) ):
$return .= <<<IPSCONTENT

		<div class="ipsButton ipsButton--inherit ipsButton--small" data-role="followButton">
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=follow&follow_app={$app}&follow_area={$area}&follow_id={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'following_this_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"  data-ipsHover data-ipsHover-cache='false' data-ipsHover-onClick><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_change_preference', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></a>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function mergeSocialAccount( $handler, $existingAccount, $login, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-margin-block_2">
	<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'link_your_accounts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<p class='i-font-size_2 i-text-align_center i-color_soft'>
IPSCONTENT;

$sprintf = array($handler->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'link_your_accounts_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class='ipsBox i-padding_3'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->reauthenticate( $login, $error );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function mfaAccountRecovery( $message ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('mfa_account_recovery') );
$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	
IPSCONTENT;

$val = "{$message}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function mfaKnownDeviceInfo( $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elTwoFactorAuthentication' class='ipsModal' data-controller='core.global.core.2fa'>
	<div>
		<h1 class='i-text-align_center ipsTitle ipsTitle--h3 i-margin-top_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_account_recovery', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<p class='ipsRichText i-text-align_center c2FA_info'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_recovery_known_device_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
		<div class="i-padding_3">
			<ul>
				<li class="i-margin-bottom_2">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login&do=logout" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "logout", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
				<li>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( '_mfa' => 'alt', '_mfaMethod' => '' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_try_another_method', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-angle-right'></i></a>
				</li>
			</ul>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function myAttachments( $files, $used ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class="ipsPageHeader ipsPageHeader--my-attachments">
	<h1 class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
</header>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_attach_max'] > 0 ):
$return .= <<<IPSCONTENT

	<div class='i-background_2 i-padding_3'>
		<p>
IPSCONTENT;

$sprintf = array(\IPS\Output\Plugin\Filesize::humanReadableFilesize( $used ), \IPS\Output\Plugin\Filesize::humanReadableFilesize( \IPS\Member::loggedIn()->group['g_attach_max'] * 1024 )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachments_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty($files) ):
$return .= <<<IPSCONTENT

	<div class='i-padding_3 i-background_2'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachments_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<i-data>
		<div class="ipsData ipsData--table ipsData--attachments">
			
IPSCONTENT;

foreach ( $files as $url => $file ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$id = mb_substr( $url, mb_strrpos( $url, '=' ) + 1 );
$return .= <<<IPSCONTENT

				<div class='ipsData__item'>
					
IPSCONTENT;

if ( \in_array( mb_strtolower( mb_substr( $file->filename, mb_strrpos( $file->filename, '.' ) + 1 ) ), \IPS\Image::supportedExtensions() ) ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsData__image'><img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' data-ipsLightbox data-ipsLightbox-group="myAttachments" loading="lazy"></a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class="ipsData__image"><i class='fa-solid fa-
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\File::getIconFromName( $file->originalFilename ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div class='ipsData__content'>
						<div class='ipsData__main' data-action='selectFile'>
							<h2 class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->originalFilename, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
							<p class='ipsData__meta'>
								
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $file->filesize() );
$return .= <<<IPSCONTENT

							</p>
						</div>
						<ul class='ipsButtons'>
							<li>
								<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=attachments&do=view&id={$id}", null, "attachments", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--inherit ipsButton--icon' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachments_view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-magnifying-glass'></i></a>
							</li>
						</ul>
					</div>
				</div>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	</i-data>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function noResults(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<p class='i-text-align_center i-font-size_2 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

		return $return;
}

	function notAdminValidated(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='i-text-align_center ipsBox'>
	<div class='i-padding_3'>
		<i class='ipsLargeIcon fa-solid fa-lock'></i>
		<h1 class='ipsTitle ipsTitle--h2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_admin_validation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<div class="ipsRichText i-font-size_2 i-margin-block_3">
			<p>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->email); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_admin_validation_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
		</div>
	</div>
	<ul class='ipsSubmitRow ipsButtons'>
		
IPSCONTENT;

$guest = new \IPS\Member;
$return .= <<<IPSCONTENT

		<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login&do=logout" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "logout", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

if ( $guest->group['g_view_board'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_continue_as_guest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a></li>
		<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register&do=cancel" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit' data-confirm data-confirmMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_cancel_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
	</ul>
</section>
IPSCONTENT;

		return $return;
}

	function notCoppaValidated(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='i-text-align_center i-padding_3 ipsBox'>
	<h1 class='ipsTitle ipsTitle--h2 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_consent_required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<div data-role='registerForm'>
		<p class='i-font-size_2 i-margin-block_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_consent_required_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register&do=coppaForm", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'coppa_print_form', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</section>
IPSCONTENT;

		return $return;
}

	function notifications( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsPageHeader ipsPageHeader--notifications'>
	<div class="ipsPageHeader__row">
		<div class='ipsPageHeader__primary'>
			<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		</div>
		<div class="ipsButtons">
			<a class="ipsButton ipsButton--text" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=options", null, "notifications_options", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-gear"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notification_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			<a class="ipsButton ipsButton--text" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&format=rss", null, "notifications_rss", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-rss"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rss', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</div>
	</div>
</header>
<div class='ipsBox'>
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function notificationsAjax( $notifications ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty( $notifications ) ):
$return .= <<<IPSCONTENT

	<li class='ipsData__item'>
		<div class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results_notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</li>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $notifications as $notification ):
$return .= <<<IPSCONTENT

		<li class='ipsData__item ipsData__item--notification' 
IPSCONTENT;

if ( !$notification['notification']->read_time ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

if ( isset( $notification['data']['url'] ) ):
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification['data']['url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification['data']['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsData__icon'>
				
IPSCONTENT;

if ( isset( $notification['data']['author'] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $notification['data']['author'], 'fluid' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsData__content'>
				<div class='ipsData__main'>
					<h4 class='ipsData__title'>
						
IPSCONTENT;

if ( isset( $notification['data']['url'] ) ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification['data']['url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification['data']['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification['data']['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</h4>
				</div>
			</div>
			<p class='i-color_soft'>
IPSCONTENT;

$val = ( $notification['notification']->updated_time instanceof \IPS\DateTime ) ? $notification['notification']->updated_time : \IPS\DateTime::ts( $notification['notification']->updated_time );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function notificationSettingsIndex( $categories ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsBox ipsPull">
	<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notification_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='ipsBox__content'>
		<ul class="cNotificationTypes" >
			
IPSCONTENT;

foreach ( $categories as $k => $enabled ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->notificationSettingsIndexRow( $k, $enabled );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</div>
<div class="ipsBox ipsPull">
	<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'where_you_receive_notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class="ipsFluid i-gap_lines i-basis_300 i-border-end-start-radius_box i-border-end-end-radius_box i-overflow_hidden">
		<div class="i-padding_3 i-flex i-gap_3">
			<div class="i-font-size_6 i-flex_00">
				<i class="fa-regular fa-bell"></i>
			</div>
			<div class="i-flex_11">
				<h3 class="ipsTitle ipsTitle--h5">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_inline', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_inline_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
		</div>
		
IPSCONTENT;

if ( \IPS\Notification::webPushEnabled() || \count( \IPS\Member::loggedIn()->getPwaAuths() ) ):
$return .= <<<IPSCONTENT

			<div class="i-padding_3 i-flex i-gap_3">
				<div class="i-font-size_6 i-flex_00">
					<i class="fa-solid fa-mobile-screen-button"></i>
				</div>
				<div class="i-flex_11">
					<h3 class="ipsTitle ipsTitle--h5">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_push', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_push_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					<ul class='ipsButtons ipsButtons--start i-margin-top_3'>
						
IPSCONTENT;

if ( \IPS\Notification::webPushEnabled() ):
$return .= <<<IPSCONTENT

							<li data-action="enablePush" style='display: none'>
								<a href='#' class='ipsButton ipsButton--small ipsButton--positive' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_push_enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_push_enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-angle-right'></i> </a>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\Notification::webPushEnabled() && \count( \IPS\Member::loggedIn()->getPwaAuths() )  ):
$return .= <<<IPSCONTENT

							<li>
								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=disable&type=push" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--small ipsButton--negative' data-confirm data-confirmIcon="false" data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_push_stop_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_push_stop_desc_sub', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_push_stop', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-angle-right'></i></a>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="i-padding_3 i-flex i-gap_3">
			<div class="i-font-size_6 i-flex_00">
				<i class="fa-regular fa-envelope"></i>
			</div>
			<div class="i-flex_11">
				<h3 class="ipsTitle ipsTitle--h5">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<p class="i-color_soft">
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->email); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_email_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
				<ul class='ipsButtons ipsButtons--start i-margin-top_2'>
					<li>
						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=disable&type=email" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--small ipsButton--inherit' data-confirm><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_notifications_email_stop', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-angle-right'></i></a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>

IPSCONTENT;

if ( \IPS\core\Stream\Subscription::hasSubscribedStreams()  ):
$return .= <<<IPSCONTENT

	<div class="ipsBox ipsPull">
	    <h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_subscriptions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			<ul class='ipsData ipsData--table ipsData--stream-subscriptions'>
				
IPSCONTENT;

foreach ( \IPS\core\Stream\Subscription::getSubscribedStreams() as $stream  ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<div class='ipsData__main'>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->stream->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->stream->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						</div>
						<div>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->stream->url()->setQueryString('do','unsubscribe')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_unsubscribe_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" >
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_unsubscribe', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function notificationSettingsIndexRow( $k, $enabled ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="cNotificationTypes__row ipsLoading--tiny">
	<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=options&type={$k}", null, "notifications_options", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-flex i-align-items_center i-gap_3 i-padding_3 i-color_root" data-action="showNotificationSettings">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->notificationSettingsIndexRowDetails( $k, $enabled );
$return .= <<<IPSCONTENT

	</a>
	<div data-role="notificationSettingsWindow" class="ipsHide"></div>
</li>
IPSCONTENT;

		return $return;
}

	function notificationSettingsIndexRowDetails( $k, $enabled ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-flex_11'>
	<h3 class="ipsTitle ipsTitle--h5 ipsTitle--margin">
IPSCONTENT;

$val = "notifications__$k"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( \count( $enabled ) ):
$return .= <<<IPSCONTENT

		<ul class='ipsList ipsList--inline ipsList--icons'>
			
IPSCONTENT;

foreach ( $enabled as $k => $v ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $v['icon'] !== 'envelope' and $v['icon'] !== 'bell' and $v['icon'] !== 'mobile' ):
$return .= <<<IPSCONTENT

					<li 
IPSCONTENT;

if ( isset( $v['description'] ) ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;

$val = "{$v['description']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $v['icon'] === 'bell' or $v['icon'] === 'mobile' ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

if ( $v['icon'] === 'bell' ):
$return .= <<<IPSCONTENT

							<i class="fa-regular fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class="fa-solid fa-mobile-screen-button"></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $v['icon'] === 'envelope' ):
$return .= <<<IPSCONTENT

					<li>
						<i class="fa-regular fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='i-color_soft i-opacity_5'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications_none_selected', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<div class="cNotificationSettings_expand i-font-size_3 i-color_soft"><i class="fa-solid fa-angle-down"></i></div>
IPSCONTENT;

		return $return;
}

	function notificationSettingsType( $title, $form, $ajax=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $ajax ):
$return .= <<<IPSCONTENT

	<div class="i-flex i-gap_3 i-padding_3 i-padding-bottom_0">
		<h3 class="i-flex_11 ipsTitle ipsTitle--h5">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		<a href="#" class="i-font-size_4 i-color_soft cNotificationTypes__toggle" data-action="closeNotificationSettings">×</a>
	</div>
	<div>
		{$form}
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( $title );
$return .= <<<IPSCONTENT

	<div class="ipsBox i-padding_3">
		{$form}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function notificationsRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT
 
	
IPSCONTENT;

foreach ( $rows as $notification ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( $notification['data']['title'] ) ):
$return .= <<<IPSCONTENT

			<li class='ipsData__item ipsData__item--notification-row' 
IPSCONTENT;

if ( $notification['data']['unread'] ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<div class='ipsData__icon'>
					
IPSCONTENT;

if ( isset( $notification['data']['author'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $notification['data']['author'], 'tiny' );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsData__content">
					<div class='ipsData__main'>
						<div class='ipsData__title'>
							
IPSCONTENT;

if ( !$notification['data']['unread'] ):
$return .= <<<IPSCONTENT

								<span class='ipsIndicator'></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $notification['data']['url'] ) ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification['data']['url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification['data']['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $notification['data']['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
				<p class='ipsData__meta'>
IPSCONTENT;

$val = ( $notification['notification']->updated_time instanceof \IPS\DateTime ) ? $notification['notification']->updated_time : \IPS\DateTime::ts( $notification['notification']->updated_time );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
			</li>
		
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

		return $return;
}

	function notificationsTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsPageHeader ipsBox ipsPull ipsPageHeader--notifications-table'>
	<div class="ipsPageHeader__row">
		<div class='ipsPageHeader__primary'>
			<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		</div>
		<div class="ipsButtons">
			<a class="ipsButton ipsButton--inherit ipsButton--small" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=options", null, "notifications_options", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-gear"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notification_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</div>
	</div>
</header>
<div class='ipsBox'>
	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			<div class="ipsButtonBar__pagination" data-role="tablePagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \is_array( $rows ) AND \count( $rows ) ):
$return .= <<<IPSCONTENT

		<i-data>
			<ol class="ipsData ipsData--notifications-table cForumTopicTable 
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
" id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="tableRows">
				
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

			</ol>
		</i-data>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--bottom">
			<div class="ipsButtonBar__pagination" data-role="tablePagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function notValidated( $validating=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='i-text-align_center ipsBox i-padding_3'>
	<i class='ipsLargeIcon fa-solid fa-envelope'></i>
	<h1 class='ipsTitle ipsTitle--h2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_confirm_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<div class="ipsRichText i-font-size_2 i-margin-block_3">
		<p>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->email); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_confirm_email_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_confirm_email_must', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>
	<ul class='ipsButtons ipsButtons--small'>
		<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register&do=resend" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_resend_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
        
IPSCONTENT;

if ( !$validating['new_reg'] ):
$return .= <<<IPSCONTENT

            <li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register&do=changeEmail", null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_change_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-modal='true' class='ipsButton ipsButton_light ipsButton_verySmall'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_change_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        <li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login&do=logout" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "logout", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

if ( $validating['new_reg'] AND !\IPS\Member::loggedIn()->members_bitoptions['created_externally'] ):
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=register&do=cancel" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "register", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit' data-confirm data-confirmMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-confirmSubMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_cancel_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
</section>
IPSCONTENT;

		return $return;
}

	function offline( $message ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="ipsLayout_mainArea">
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/offline", "systemOffline:before", [ $message ] );
$return .= <<<IPSCONTENT
<div class="ipsBox ipsBox--offline" data-ips-hook="systemOffline">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/offline", "systemOffline:inside-start", [ $message ] );
$return .= <<<IPSCONTENT

		<div class="i-padding_3">
			<h1 class="ipsTitle ipsTitle--h1 i-margin-bottom_3">
IPSCONTENT;

$sprintf = array(\IPS\Settings::i()->board_name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'offline_unavailable', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h1>
			<div class="ipsRichText">
				{$message}
			</div>
		</div>
		<div class="ipsSubmitRow">
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login&do=logout" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "logout", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=login", null, "login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'login', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/offline", "systemOffline:inside-end", [ $message ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/offline", "systemOffline:after", [ $message ] );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function pixabay( $uploader ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.global.stockart.pixabay' data-uploader="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploader, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class='ipsPixabayContent' data-role='pixabayResults'>
		<div class='ipsPixabayGrid' data-role='pixabayLoading'>
			
		</div>
		<div class='ipsPixabayMore' data-role='pixabayMore' data-offset='0'>
			<div data-role='pixabayMoreLoading' class='i-color_soft ipsHide i-margin-bottom_3'><i class='fa-solid fa-circle-notch fa-spin fa-fw'></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		</div>
	</div>
	<div class='ipsMenu_footerBar'>
		<input type='text' data-role='pixabaySearch' class='ipsInput ipsInput--text ipsInput--wide' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
		<a href="https://pixabay.com/" target='_blank' rel="noopener external nofollow" class="ipsPixabayAttribution"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 123.87"><g><path d="M523.513 40.165c-3.478-2.46-6.005-4.955-6.005-8.033H498.17c0 5.539-2.527 8.033-5.645 8.033h-5.164v56.236h100.42V40.165h-64.63zm-6.006 16.067h-18.096V48.2h20.084v6.464zm33.991 31.276c-11.457 0-20.779-9.201-20.779-20.513s9.322-20.04 20.78-20.04c11.456 0 20.777 8.729 20.777 20.04s-9.32 20.513-20.778 20.513zm11.17-20.513c0 6.08-5.01 11.028-11.169 11.028-6.159 0-11.17-4.947-11.17-11.028 0-6.08 5.011-11.027 11.17-11.027 6.158 0 11.17 4.947 11.17 11.027zm77.334-34.48l-25 63.735-19.185-7.693V77.695l13.323 5.452 17.592-44.844-65.92-25.205-6.11 19.034h-11L554.95-.003l85.053 32.52z"/><g><path d="M33.952 28.507c16.562-.38 32.107 13.09 33.856 29.606C69.9 72.627 61.362 87.7 47.98 93.592 39.874 97.616 30.72 96.38 22 96.585h-8.518v27.29H-.011c.03-21.057-.054-42.116.042-63.174.441-15.684 13.287-29.735 28.795-31.823a34.551 34.551 0 015.126-.372zm0 54.582c9.733.244 18.861-7.35 20.314-16.992 1.94-9.706-4.252-20.135-13.717-23.046-9.141-3.244-20.14 1.027-24.604 9.66-3.464 5.897-2.233 12.87-2.463 19.369v11.01h20.47zM74.334 28.177h13.34v68.08h-13.34v-68.08zM127.764 71.16h.486l18.963 25.284h16.531l-25.77-35.008 22.853-33.063h-16.532L128.25 51.71h-.486l-16.046-23.338H95.187l22.852 33.063-25.77 35.008h16.532z"/><path d="M193.953 28.177c13.511-.267 26.471 8.585 31.472 21.082 2.61 5.804 2.574 12.234 2.49 18.465v28.532c-12.04-.041-24.085.083-36.124-.065-13.842-.675-26.5-10.768-30.462-24.013-3.633-11.515-.614-24.886 7.936-33.485 6.388-6.608 15.465-10.604 24.687-10.516zm20.47 54.586c-.058-7.57.121-15.147-.1-22.711-.798-10.142-10.192-18.639-20.37-18.378-9.678-.234-18.8 7.274-20.315 16.854-1.773 9.194 3.505 19.212 12.279 22.643 5.404 2.357 11.373 1.385 17.085 1.592h11.42zM268.523 28.507c15.98-.394 30.91 12.179 33.455 27.916 2.887 14.915-5.539 31.029-19.427 37.169-13.573 6.406-31.026 2.278-40.36-9.46-5.784-6.815-8.277-15.875-7.784-24.709V1.216H247.9v27.29c6.874.001 13.75-.001 20.623.002zm0 54.582c10.248.274 19.616-8.33 20.367-18.525 1.285-10.133-6.226-20.324-16.256-22.166-5.546-.759-11.18-.248-16.766-.398H247.9c.087 7.691-.184 15.397.155 23.078 1.072 10.161 10.261 18.21 20.468 18.011zM341.648 28.177c13.513-.267 26.472 8.586 31.473 21.082 2.61 5.804 2.574 12.234 2.49 18.465v28.532c-12.04-.041-24.085.083-36.124-.065-13.841-.675-26.499-10.768-30.462-24.013-3.632-11.515-.614-24.886 7.936-33.485 6.388-6.608 15.465-10.604 24.687-10.516zm20.47 54.586c-.057-7.57.122-15.147-.099-22.711-.798-10.142-10.192-18.639-20.371-18.378-9.677-.234-18.8 7.274-20.314 16.854-1.773 9.194 3.505 19.212 12.279 22.643 5.404 2.357 11.373 1.385 17.085 1.592h11.42zM449.87 28.342c-.028 21.007.055 42.017-.041 63.022-.49 16.533-14.697 31.392-31.284 32.256-3.151.166-6.308.065-9.463.093V110.22c5.758-.029 11.918.556 17.017-2.705 6.37-3.573 10.418-10.779 10.274-18.066-11.96 9.644-30.502 9.143-42.133-.811-8.474-6.841-13.105-17.846-12.45-28.675v-31.62h13.492c.062 12.019-.127 24.046.1 36.059.732 9.893 9.554 18.266 19.468 18.502 10.047.772 19.717-7.03 21.216-16.969.605-5.946.176-11.952.305-17.924V28.343h13.497z"/></g></g></svg></a>
	</div>
</div>

IPSCONTENT;

		return $return;
}

	function privacy( $subprocessors ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('privacy') );
endif;
$return .= <<<IPSCONTENT

<div class='
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsBox--padding ipsRichText'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('privacy_text_value') );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->site_address and \IPS\Settings::i()->site_address != "null" ):
$return .= <<<IPSCONTENT

		<p>
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

$return .= \IPS\GeoLocation::parseForOutput( \IPS\Settings::i()->site_address );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $subprocessors and \count($subprocessors) ):
$return .= <<<IPSCONTENT

	<div>
		<h3 class="i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pp_third_parties', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

foreach ( $subprocessors as $processor ):
$return .= <<<IPSCONTENT

			<div class="i-padding-bottom_3">
				<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $processor['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
				<div>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $processor['description'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
				<div><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $processor['privacyUrl'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pp_privacy_policy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>
			</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function profileCompleteSocial( $step, $socialButton, $action ) {
		$return = '';
		$return .= <<<IPSCONTENT

	{$socialButton}

	
IPSCONTENT;

if ( !$step->required OR $step->completed() ):
$return .= <<<IPSCONTENT

		<ul class="ipsSubmitRow ipsButtons">
			<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action->setQueryString('_moveToStep', $step->getNextStep()), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="wizardLink" class="ipsButton ipsButton--text ipsJS_none">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_complete_skip_step', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reauthenticate( $login, $error, $blurb=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

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
	
IPSCONTENT;

$usernamePasswordMethods = $login->usernamePasswordMethods();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $blurb ):
$return .= <<<IPSCONTENT

		<p class='i-margin-bottom_3'>
			
IPSCONTENT;

$val = "{$blurb}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--error">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $usernamePasswordMethods and $buttonMethods ):
$return .= <<<IPSCONTENT

		<div class='ipsColumns'>
			<div class='ipsColumns__primary'>
				<p class='i-margin-bottom_3'>
					
IPSCONTENT;

if ( $blurb ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reauthenticate_password_blurb2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reauthenticate_password_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
				<ul class='ipsForm'>
					<li class="ipsFieldRow ipsFieldRow--fullWidth">
						<div class="ipsFieldRow__content">
							<input type="password" class='ipsInput ipsInput--text' placeholder="
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
					<li class="ipsFieldRow ipsFieldRow--fullWidth">
						<button type="submit" name="_processLogin" value="usernamepassword" class="ipsButton ipsButton--primary ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reauthenticate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					</li>
				</ul>
				
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password != 'disabled' ):
$return .= <<<IPSCONTENT

					<p class="i-text-align_end i-font-size_-2">
						
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password == 'redirect' ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_forgot_password_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener">
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=lostpass", null, "lostpassword", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Helpers\Form\Captcha::supportsModal() ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsColumns__secondary i-basis_360'>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reauthenticate_alt_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				<div class='i-gap_2 i-margin-top_2'>
					
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

						<div class='i-text-align_center'>
							{$method->button()}
						</div>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
	
IPSCONTENT;

elseif ( $usernamePasswordMethods ):
$return .= <<<IPSCONTENT

		<p class='i-margin-bottom_3'>
			
IPSCONTENT;

if ( $blurb ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reauthenticate_password_blurb2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reauthenticate_password_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
		<ul class='ipsForm'>
			<li class="ipsFieldRow">
				<div class="ipsFieldRow__content">
					<input type="password" class='ipsInput ipsInput--text' placeholder="
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
>
				</div>
			</li>
			<li class="ipsFieldRow">
				<div class="i-flex i-align-items_center i-flex-wrap_wrap i-gap_2 i-justify-content_space-between">
					<button type="submit" name="_processLogin" value="usernamepassword" class="ipsButton ipsButton--primary ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reauthenticate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password != 'disabled' ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\Settings::i()->allow_forgot_password == 'redirect' ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_forgot_password_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener" class="ipsButton ipsButton--inherit ipsButton--small">
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=lostpass", null, "lostpassword", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--inherit ipsButton--small">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</li>
		</ul>
	
IPSCONTENT;

elseif ( $buttonMethods ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !$blurb ):
$return .= <<<IPSCONTENT

			<p class='i-margin-bottom_3'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reauthenticate_button_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='i-gap_2 i-margin-top_2'>
			
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

				<div class='i-text-align_center'>
					{$method->button()}
				</div>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
</form>

IPSCONTENT;

		return $return;
}

	function reconfirmTerms( $terms, $privacy, $form, $subprocessors ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsBox ipsBox--reconfirm">
	<div class="ipsBox__padding">

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->joined->getTimestamp() < ( time() - 60 ) ):
$return .= <<<IPSCONTENT

			<div class="ipsMessage ipsMessage--general i-margin-bottom_3">
				
IPSCONTENT;

if ( $terms and $privacy ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reconfirm_terms_and_policy_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $terms ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reconfirm_terms_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reconfirm_privacy_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $terms ):
$return .= <<<IPSCONTENT

			<div class="i-margin-bottom_3">
				<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_terms', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<div class='ipsRichText i-margin-top_2'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_rules_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( $privacy ):
$return .= <<<IPSCONTENT

			<div class="i-margin-bottom_3">
				<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'privacy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<div class='ipsRichText i-margin-top_2'>
					
IPSCONTENT;

if ( \IPS\Settings::i()->privacy_type == 'external' ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

$return .= \IPS\Settings::i()->privacy_link;
$return .= <<<IPSCONTENT
' rel='external'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_privacy_policy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('privacy_text_value') );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Settings::i()->site_address and \IPS\Settings::i()->site_address != "null" ):
$return .= <<<IPSCONTENT

						<p>
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

$return .= \IPS\GeoLocation::parseForOutput( \IPS\Settings::i()->site_address );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $subprocessors and \count($subprocessors) ):
$return .= <<<IPSCONTENT

					<div class='i-margin-top_3'>
						<h3 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pp_third_parties', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<div class='ipsRichText'>
							
IPSCONTENT;

foreach ( $subprocessors as $processor ):
$return .= <<<IPSCONTENT

								<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $processor['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
								<p>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $processor['description'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $processor['privacyUrl'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pp_privacy_policy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></p>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</div>
					</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class="">
		{$form}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function referralsRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT
 
	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$member = \IPS\Member::load( $row['member_id'] );
$return .= <<<IPSCONTENT

		<li class='ipsPhotoPanel ipsPhotoPanel--tiny'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'tiny' );
$return .= <<<IPSCONTENT
	
			<div>
				<strong>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $row['member_id'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				</strong>
				<br>
				<span class='i-color_soft i-font-size_-2'>
IPSCONTENT;

$val = ( $row['joined'] instanceof \IPS\DateTime ) ? $row['joined'] : \IPS\DateTime::ts( $row['joined'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
			</div>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function referralTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='cReferal_members' data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.global.core.table
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT
,core.front.core.moderation
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $table->getPaginationKey() != 'page' ):
$return .= <<<IPSCONTENT
data-pageParam='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getPaginationKey(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-tableID='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5($table->baseUrl->stripQueryString($table->getPaginationKey())), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( $table->title ):
$return .= <<<IPSCONTENT

		<h2 class='ipsBox__header'>
IPSCONTENT;

$val = "{$table->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

		<i-data>
			<ol class="ipsData ipsData--table ipsData--referral-table" id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="tableRows">
				
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

			</ol>
		</i-data>
	    
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				<div class="ipsButtonBar__pagination" data-role="tablePagination">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'referral_no_referrals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function register( $form, $login, $postBeforeRegister ) {
		$return = '';
		$return .= <<<IPSCONTENT


<section data-el="register-form">
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/register", "registerForm:before", [ $form,$login,$postBeforeRegister ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="registerForm" data-role="registerForm">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/register", "registerForm:inside-start", [ $form,$login,$postBeforeRegister ] );
$return .= <<<IPSCONTENT

		<div class="ipsBox ipsPull ipsColumns ipsColumns--lines i-overflow_hidden">
			
IPSCONTENT;

$buttonMethods = $login->buttonMethods();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $buttonMethods ):
$return .= <<<IPSCONTENT

				<div class="ipsColumns__primary">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div class="
IPSCONTENT;

if ( !$buttonMethods ):
$return .= <<<IPSCONTENT
cRegister_noSocial
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						<div class="i-padding_3 i-border-bottom_1 i-background_2 i-border-start-start-radius_box i-border-start-end-radius_box">
							
IPSCONTENT;

if ( $postBeforeRegister ):
$return .= <<<IPSCONTENT

								<h1 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_before_register_headline', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
								
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->hidereminder ):
$return .= <<<IPSCONTENT
<p class="i-color_soft i-font-size_2 i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_before_register_subtext', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<h1 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_up', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
								<p class="i-color_soft i-font-size_2 i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'existing_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_in_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></p>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						{$form}
					</div>
			
IPSCONTENT;

if ( $buttonMethods ):
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsColumns__secondary i-basis_340 i-padding_3" id="elRegisterSocial">
					<h2 class="ipsTitle ipsTitle--h4">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_start_faster', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<p class="i-color_soft i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_connect', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					<form accept-charset="utf-8" method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						<div class="i-flex i-flex-wrap_wrap i-gap_2 i-margin-top_3">
							
IPSCONTENT;

foreach ( $buttonMethods as $method ):
$return .= <<<IPSCONTENT

								<div class="i-flex_11">
									{$method->button()}
								</div>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</div>
					</form>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/register", "registerForm:inside-end", [ $form,$login,$postBeforeRegister ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/system/register", "registerForm:after", [ $form,$login,$postBeforeRegister ] );
$return .= <<<IPSCONTENT

</section>
IPSCONTENT;

		return $return;
}

	function registerSetPassword( $form,$member ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack( 'set_password_title', FALSE, array( 'sprintf' => array( $member->name ) ) ) );
$return .= <<<IPSCONTENT

<div class='ipsBox ipsBox--padding'>
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function registerWrapper( $content ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div id='elRegisterForm' class='i-margin-inline_auto i-padding_3' data-controller='core.front.system.register'>
	{$content}
</div>
IPSCONTENT;

		return $return;
}

	function reportedAlready( $index, $report, $content ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-text-align_center">
	<div class="i-font-size_2">
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $report['date_reported'] )->html()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'automoderation_already_reported', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
	<div class="i-padding_3">
		<a data-confirm href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url()->setQueryString( array( 'do' => 'deleteReport', 'cid' => $report['id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'automoderation_already_reported_delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function reportForm( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('report') );
$return .= <<<IPSCONTENT

<div class="ipsBox ipsBox--padding">
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function resetPass( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('lost_password') );
$return .= <<<IPSCONTENT

<div class='ipsBox ipsBox--padding'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reset_pass_instructions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	<br>
	<br>
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function searchResult( $indexData, $summaryLanguage, $authorData, $itemData, $unread, $objectUrl, $itemUrl, $containerUrl, $containerTitle, $repCount, $showRepUrl, $snippet, $iPostedIn, $view, $canIgnoreComments=FALSE, $reactions=array(), $extraClass='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li class="ipsStreamItem ipsStreamItem_contentBlock ipsStreamItem_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $view, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $extraClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $indexData['index_hidden'] ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $unread ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role='activityItem' data-timestamp="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_date_created'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

if ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$itemClass = $indexData['index_class']::$itemClass;
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( 'do', 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['title'] ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		<div class='ipsStreamItem__iconCell'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhotoFromData( $authorData['member_id'], $authorData['name'], $authorData['members_seo_name'], \IPS\Member::photoUrl( $authorData ), 'fluid' );
$return .= <<<IPSCONTENT
					
		</div>
		<div class='ipsStreamItem__mainCell'>
			<div class='ipsStreamItem__header'>
				<div class='ipsStreamItem__title'>
					
IPSCONTENT;

if ( $unread ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->stripQueryString( array( 'comment' => 'comment', 'review' => 'review' ) )->setQueryString( 'do', 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->stripQueryString( array( 'comment' => 'comment', 'review' => 'review' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-linkType="star" 
IPSCONTENT;

if ( $iPostedIn ):
$return .= <<<IPSCONTENT
data-iPostedIn
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip class='ipsIndicator'></a>
					
IPSCONTENT;

elseif ( $iPostedIn ):
$return .= <<<IPSCONTENT

						<span class='ipsIndicator ipsIndicator--read ipsIndicator--participated'></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<h2>
						
IPSCONTENT;

if ( isset( $indexData['index_prefix'] ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( rawurlencode($indexData['index_prefix']), $indexData['index_prefix'] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-linkType="link" data-searchable>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['title'] ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					</h2>
					<div class='ipsBadges'>
						
IPSCONTENT;

if ( ! empty( $itemData['extra']['solved'] )  ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_solved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-check'></i></span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $indexData['index_hidden'] ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $indexData['index_hidden'] === -1 ):
$return .= <<<IPSCONTENT

								<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
							
IPSCONTENT;

elseif ( $indexData['index_hidden'] === 1 ):
$return .= <<<IPSCONTENT

								<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-triangle-exclamation'></i></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
				<div class='ipsStreamItem__summary'>
					
IPSCONTENT;

if ( $indexData['index_title'] ):
$return .= <<<IPSCONTENT

						<span data-ipsTooltip title='
IPSCONTENT;

$val = "{$itemClass::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemClass::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></span>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
				
						<span data-ipsTooltip title='
IPSCONTENT;

$val = "{$indexData['index_class']::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_class']::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summaryLanguage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $containerUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>{$containerTitle}</a>
				</div>
			</div>
			<div class='ipsStreamItem__content'>
				
IPSCONTENT;

if ( $canIgnoreComments and isset( $itemData['author'] ) and \IPS\Member::loggedIn()->member_id and isset( $authorData['member_id'] ) and isset ( $authorData['member_group_id'] ) and \IPS\Member::loggedIn()->isIgnoring( $authorData, 'topics' ) and $view != 'condensed' ):
$return .= <<<IPSCONTENT

					<div class='ipsEntry ipsEntry--ignored' id='elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_object_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ignoreCommentID='elComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_object_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ignoreUserID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $authorData['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

$sprintf = array($authorData['name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ignoring_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 <a href='#elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_object_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' data-ipsMenu data-ipsMenu-menuID='elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_object_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' data-ipsMenu-appendTo='#elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_object_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action="ignoreOptions" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_post_ignore_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
						<ul class='ipsMenu ipsHide' id='elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_object_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
							<li class='ipsMenu_item ipsJS_show' data-ipsMenuValue='showPost'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_this_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							<li class='ipsMenu_sep ipsJS_show'><hr></li>
							<li class='ipsMenu_item' data-ipsMenuValue='stopIgnoring'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore&do=remove&id={$authorData['member_id']}", null, "ignore", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array($authorData['name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stop_ignoring_posts_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
							<li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore", null, "ignore", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_ignore_preferences', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						</ul>
					</div>
					<div id='elComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_object_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsHide">
						{$snippet}
					</div>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				 	{$snippet}
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<ul class='ipsStreamItem__stats'>
				<li>
					<a rel="nofollow" href='
IPSCONTENT;

if ( $indexData['index_title'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( \in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'findReview', 'review' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'findComment', 'comment' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><i class='fa-regular fa-clock'></i> 
IPSCONTENT;

$val = ( $indexData['index_date_created'] instanceof \IPS\DateTime ) ? $indexData['index_date_created'] : \IPS\DateTime::ts( $indexData['index_date_created'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
				</li>
				
IPSCONTENT;

if ( isset( $itemClass::$databaseColumnMap['num_comments'] ) and isset( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] ] ) and $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] ] > ( $itemClass::$firstCommentRequired ? 1 : 0 ) ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

if ( $itemClass::$firstCommentRequired ):
$return .= <<<IPSCONTENT

							<i class="fa-regular fa-comments"></i> 
IPSCONTENT;

$pluralize = array( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] ] - 1 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class="fa-regular fa-comments"></i> 
IPSCONTENT;

$pluralize = array( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] ] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $itemClass::$databaseColumnMap['num_reviews'] ) and isset( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_reviews'] ] ) and $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_reviews'] ] ):
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
#reviews'><i class='fa-regular fa-star'></i> 
IPSCONTENT;

$pluralize = array( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_reviews'] ] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $view != 'condensed' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $indexData['index_class'], 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and \count( $reactions ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

							<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "search", "core" )->searchReaction( $reactions, $itemUrl->setQueryString('do', 'showReactionsReview')->setQueryString('review', $indexData['index_object_id']), $repCount );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "search", "core" )->searchReaction( $reactions, $itemUrl->setQueryString('do', 'showReactionsComment')->setQueryString('comment', $indexData['index_object_id']), $repCount );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $indexData['index_tags'] ) ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( explode( ',', $indexData['index_tags'] ), true );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$itemClass = $indexData['index_class'];
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( 'do', 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		<div class='ipsStreamItem__iconCell'>
			
IPSCONTENT;

if ( isset( $itemClass::$databaseColumnMap['author'] ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhotoFromData( $authorData['member_id'], $authorData['name'], $authorData['members_seo_name'], \IPS\Member::photoUrl( $authorData ), 'fluid' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsStreamItem__mainCell'>
			<div class='ipsStreamItem__header'>
				<div class='ipsStreamItem__title'>
					
IPSCONTENT;

if ( $unread ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( 'do', 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-linkType="star" 
IPSCONTENT;

if ( $iPostedIn ):
$return .= <<<IPSCONTENT
data-iPostedIn
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsTooltip class='ipsIndicator 
IPSCONTENT;

if ( $iPostedIn ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></a>
					
IPSCONTENT;

elseif ( $iPostedIn ):
$return .= <<<IPSCONTENT

						<span class='ipsIndicator ipsIndicator--read ipsIndicator--participated'></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<h2>
						
IPSCONTENT;

if ( isset( $indexData['index_prefix'] ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( rawurlencode($indexData['index_prefix']), $indexData['index_prefix'] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-linkType="link" data-searchable>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					</h2>
					<div class='ipsBadges'>
						
IPSCONTENT;

if ( ! empty( $itemData['extra']['solved'] )  ):
$return .= <<<IPSCONTENT
<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_solved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-check'></i></span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						 
IPSCONTENT;

if ( $indexData['index_hidden'] ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
				
IPSCONTENT;

if ( $containerTitle ):
$return .= <<<IPSCONTENT

					<div class='ipsStreamItem__summary'>
						
IPSCONTENT;

if ( isset( $itemClass::$databaseColumnMap['author'] ) ):
$return .= <<<IPSCONTENT

							<span data-ipsTooltip title='
IPSCONTENT;

$val = "{$indexData['index_class']::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'ucfirst' => TRUE ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_class']::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i></span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_class']::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $summaryLanguage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $containerUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>{$containerTitle}</a>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsStreamItem__content'>
				{$snippet}
			</div>				
			<ul class='ipsStreamItem__stats'>
				
IPSCONTENT;

if ( isset( $indexData['index_class']::$databaseColumnMap['date'] ) ):
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="nofollow"><i class='fa-regular fa-clock'></i> 
IPSCONTENT;

$val = ( $indexData['index_date_created'] instanceof \IPS\DateTime ) ? $indexData['index_date_created'] : \IPS\DateTime::ts( $indexData['index_date_created'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $indexData['index_class']::$databaseColumnMap['num_comments'] ) and isset( $itemData[ $indexData['index_class']::$databasePrefix . $indexData['index_class']::$databaseColumnMap['num_comments'] ] ) and $itemData[ $indexData['index_class']::$databasePrefix . $indexData['index_class']::$databaseColumnMap['num_comments'] ] > ( $indexData['index_class']::$firstCommentRequired ? 1 : 0 ) ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

if ( $indexData['index_class']::$firstCommentRequired ):
$return .= <<<IPSCONTENT

							<i class="fa-regular fa-comments"></i> 
IPSCONTENT;

$pluralize = array( $itemData[ $indexData['index_class']::$databasePrefix . $indexData['index_class']::$databaseColumnMap['num_comments'] ] - 1 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class="fa-regular fa-comments"></i> 
IPSCONTENT;

$pluralize = array( $itemData[ $indexData['index_class']::$databasePrefix . $indexData['index_class']::$databaseColumnMap['num_comments'] ] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $indexData['index_class']::$databaseColumnMap['num_reviews'] ) and isset( $itemData[ $indexData['index_class']::$databasePrefix . $indexData['index_class']::$databaseColumnMap['num_reviews'] ] ) and $itemData[ $indexData['index_class']::$databasePrefix . $indexData['index_class']::$databaseColumnMap['num_reviews'] ] ):
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
#reviews'><i class='fa-regular fa-star'></i> 
IPSCONTENT;

$pluralize = array( $itemData[ $indexData['index_class']::$databasePrefix . $indexData['index_class']::$databaseColumnMap['num_reviews'] ] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $view != 'condensed' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $indexData['index_class'], 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and \count( $reactions ) ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "search", "core" )->searchReaction( $reactions, $itemUrl->setQueryString('do', 'showReactions'), $repCount );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $indexData['index_tags'] ) ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( explode( ',', $indexData['index_tags'] ), true );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>
IPSCONTENT;

		return $return;
}

	function searchResultSnippet( $indexData, $itemData=null ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsStreamItem__content-content ipsStreamItem__content-content--core'>
	
IPSCONTENT;

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

		<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_4'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 class='ipsRichText ipsTruncate_4' data-searchable data-findTerm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

$return .= \IPS\Content\Search\Result::preDisplay( $indexData['index_content'] );
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $itemData['attachedImages'] ) ):
$return .= <<<IPSCONTENT

    <div>
        <div class='ipsGrid i-gap_1 i-basis_60 i-margin-top_2' data-controller="core.front.core.lightboxedImages">
            
IPSCONTENT;

foreach ( $itemData['attachedImages'] as $image  ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$imagePath = $image['thumb_location'] ?: $image['location'];
$return .= <<<IPSCONTENT

                <div>
                    <a class='ipsThumb' href='
IPSCONTENT;

$return .= \IPS\File::get( $image['extension'], $image['location'] )->url;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $image['labels'] ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alt_label_could_be', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['labels'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsLightbox 
IPSCONTENT;

if ( $image['labels'] ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alt_label_could_be', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['labels'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsLightbox-group='g
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_class_type_id_hash'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
                        <img src='
IPSCONTENT;

$return .= \IPS\File::get( $image['extension'], $imagePath )->url;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $image['labels'] ):
$return .= <<<IPSCONTENT
alt="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alt_label_could_be', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image['labels'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
alt=""
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 loading="lazy">
                    </a>
                </div>
            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        </div>
    </div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function settings( $activeTab, $output, $tabs ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('settings'), \IPS\Member::loggedIn()->language()->addToStack('settings_blurb') );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions["email_messages_bounce"] ):
$return .= <<<IPSCONTENT

    <div class="ipsMessage ipsMessage_error">
        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ucp_change_email_prompt', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    </div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div id='elSettingsTabs' data-ipsTabBar data-ipsTabBar-contentArea='#elProfileTabContent' data-ipsTabBar-itemSelector='[data-ipsSideMenu] .ipsSideMenu_item' data-ipsTabBar-activeClass='ipsSideMenu_itemActive'>
	<div class='ipsColumns ipsColumns--stack ipsBox ipsColumns--lines ipsPull'>
		<div class='ipsColumns__secondary i-basis_320 i-padding_2'>			
			<div class='ipsSideMenu' data-ipsSideMenu>
				<h3 class="ipsSideMenu__view">
					<a href="#modcp_menu" data-action="openSideMenu">
						<i class="fa-solid fa-bars"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'settings_area', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions['email_messages_bounce'] ):
$return .= <<<IPSCONTENT

							<span class="ipsNotification"><i class="fa-solid fa-triangle-exclamation"></i></span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
				</h3>
				<div class="ipsSideMenu__menu">
					<ul class="ipsSideMenu__list">
						
IPSCONTENT;

foreach ( $tabs as $key => $tab ):
$return .= <<<IPSCONTENT

						<li>
							<a href="
IPSCONTENT;

if ( isset( $tab['url'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area={$key}", null, "settings_custom", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" id='settings_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item 
IPSCONTENT;

if ( $activeTab === $key ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

if ( isset( $tab['title'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$tab['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' role="tab" aria-selected="
IPSCONTENT;

if ( $activeTab === $key ):
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

if ( isset( $tab['image'] ) ):
$return .= <<<IPSCONTENT
<div class="cLoginServiceIcon">
IPSCONTENT;

if ( $tab['image'] ):
$return .= <<<IPSCONTENT
<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['image'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<span>
IPSCONTENT;

if ( isset( $tab['title'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$tab['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
								
IPSCONTENT;

if ( isset( $tab['warning'] ) AND $tab['warning'] ):
$return .= <<<IPSCONTENT

									<span class="ipsSideMenu_itemCount"><i class="fa-solid fa-triangle-exclamation"></i></span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</a>
						</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			</div>
		</div>
		<div class='ipsColumns__primary'>
			<section id='elProfileTabContent'>
				<div id="ipsTabs_elSettingsTabs_setting_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class="ipsTabs__panel" aria-labelledby="setting_overview" aria-hidden="false">
					{$output}
				</div>
			</section>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function settingsApps( $apps ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3 i-border-bottom_3'>
	<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_apps_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
<div class='i-padding_3'>
	
IPSCONTENT;

if ( \count( $apps ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $apps as $app ):
$return .= <<<IPSCONTENT

			<div class="ipsBox i-margin-bottom_3">
				<div class="i-background_2 i-flex i-padding_3">
					<h2 class="ipsTitle ipsTitle--h3">
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app['client']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</h2>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "core&module=system&controller=settings&area=apps&do=revokeApp&client_id={$app['client']->client_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings_apps", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--small i-margin-start_auto" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_app_revoke_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_app_revoke', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				</div>
				<i-data>
					<ul class="ipsData ipsData--table ipsData--settings-apps">
						<li class="ipsData__item">
							<span class="i-basis_180">
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_app_issued', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</span>
							<span>
								
IPSCONTENT;

$val = ( $app['issued'] instanceof \IPS\DateTime ) ? $app['issued'] : \IPS\DateTime::ts( $app['issued'] );$return .= $val->html();
$return .= <<<IPSCONTENT

							</span>
						</li>
						
IPSCONTENT;

if ( $app['scopes'] ):
$return .= <<<IPSCONTENT

							<li class="ipsData__item">
								<span class="i-basis_180">
									<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_app_scopes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
								</span>
								<div>
									<ul>
										
IPSCONTENT;

foreach ( $app['scopes'] as $key => $scope ):
$return .= <<<IPSCONTENT

											<li>
												<i class="fa-solid fa-check"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $scope, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

											</li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</div>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</i-data>
			</div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_apps_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsDevices( $devices, $ipAddresses ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-background_2 i-padding_3 i-border-bottom_3">
	<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ucp_devices', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_management_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=secureAccount", null, "settings_secure", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_list_secure_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></p>
</div>
<div class='i-padding_3'>
	
IPSCONTENT;

if ( \IPS\Settings::i()->new_device_email ):
$return .= <<<IPSCONTENT

	<div class='i-margin-bottom_3'>
		<h3 class='ipsTitle ipsTitle--h5'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ucp_devices_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions['new_device_email'] ):
$return .= <<<IPSCONTENT
<span class='i-color_positive'><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ucp_devices_email_on', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<span class='i-color_negative'><i class='fa-solid fa-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ucp_devices_email_off', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h3>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ucp_devices_email_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions['new_device_email'] ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=updateDeviceEmail&value=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ucp_devices_email_disable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=updateDeviceEmail&value=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ucp_devices_email_enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $devices ) ):
$return .= <<<IPSCONTENT

		<div class='i-grid i-gap_3'>
			
IPSCONTENT;

foreach ( $devices as $device ):
$return .= <<<IPSCONTENT

				<div class="ipsInnerBox">
					<div class="i-flex i-align-items_center i-gap_2 i-padding_2">
						<div class="i-flex_00 i-basis_60">
							
IPSCONTENT;

if ( $device->userAgent()->platform === 'Macintosh' ):
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/mac.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							
IPSCONTENT;

elseif ( $device->userAgent()->platform === 'Android' or $device->userAgent()->platform === 'Windows Phone' ):
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/android.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							
IPSCONTENT;

elseif ( $device->userAgent()->platform === 'iPad' ):
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/ipad.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							
IPSCONTENT;

elseif ( $device->userAgent()->platform === 'iPhone' ):
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/iphone.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/pc.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<div class='i-flex_11 i-flex i-align-items_center i-gap_1 i-flex-wrap_wrap'>
							<div class='i-flex_11'>
								<h4 class="ipsTitle ipsTitle--h5">
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $device->userAgent()->platform, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								</h4>
								<p class="i-color_soft">
									
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->cookie['device_key'] ) and \IPS\Widget\Request::i()->cookie['device_key'] === $device->device_key ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'current_device', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $device->last_seen )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_last_loggedin', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</p>
							</div>
							
IPSCONTENT;

if ( $device->login_key or isset( $apps[ $device->device_key ] ) ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "=core&module=system&controller=settings&area=devices&do=disableAutomaticLogin&device={$device->device_key}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings_devices", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-color_negative ipsButton--small i-flex_00">
									
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->cookie['device_key'] ) and \IPS\Widget\Request::i()->cookie['device_key'] === $device->device_key ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disable_automatic_login', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
					<i-data>
						<ul class="ipsData ipsData--table ipsData--settings-devices-1">
							<li class="ipsData__item">
								<div class="ipsData__content">
									<div class="i-basis_180 i-color_hard">
										<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_user_agent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
									</div>
									<div>
										
IPSCONTENT;

if ( \in_array( $device->userAgent()->browser, array( 'Android Browser', 'AppleWebKit', 'Camino', 'Chrome', 'Edge', 'Firefox', 'IEMobile', 'Midori', 'MSIE', 'Opera', 'Puffin', 'Safari', 'SamsungBrowser', 'Silk', 'UCBrowser', 'Vivaldi' ) ) ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$browser = str_replace( ' ', '', $device->userAgent()->browser );
$return .= <<<IPSCONTENT

											<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/browsers/{$browser}.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" width="24" alt="" loading="lazy" class='i-margin-end_icon'>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $device->userAgent()->browser, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $device->userAgent()->browserVersion, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

									</div>
								</div>
							</li>
							
IPSCONTENT;

if ( $loginMethod = $device->loginMethod() and $logo = $loginMethod->logoForDeviceInformation() ):
$return .= <<<IPSCONTENT

								<li class="ipsData__item">
									<div class="ipsData__content">
										<div class="i-basis_180 i-color_hard">
											<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_login_handler', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
										</div>
										<div class="">
											<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $logo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" width="24" alt="" loading="lazy"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $loginMethod->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										</div>
									</div>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $apps[ $device->device_key ] ) ):
$return .= <<<IPSCONTENT

							<li class="ipsData__item">
								<div class="ipsData__content">
									<div class="i-basis_180 i-color_hard">
										<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
									</div>
									<div class="">
										<ul class="i-flex i-align-items_center i-gap_3 i-flex-wrap_wrap">
											
IPSCONTENT;

foreach ( $apps[ $device->device_key ] as $clientId => $app ):
$return .= <<<IPSCONTENT

											<li>
												
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $oauthClients[ $clientId ]->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

											</li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									</div>
								</div>
							</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<li class="ipsData__item">
								<div class="ipsData__content">
									<div class="i-basis_180 i-color_hard">
										<strong>
IPSCONTENT;

if ( \IPS\Settings::i()->ipsgeoip ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_last_locations', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
*
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_last_logins', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</strong>
									</div>
									<div class="">
										<ul class="">
											
IPSCONTENT;

foreach ( $ipAddresses[ $device->device_key ] as $ipAddress => $details ):
$return .= <<<IPSCONTENT

											<li>
												
IPSCONTENT;

if ( \IPS\Settings::i()->ipsgeoip ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['location'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $ipAddress, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												&nbsp; <span class="i-color_soft">
IPSCONTENT;

$val = ( $details['date'] instanceof \IPS\DateTime ) ? $details['date'] : \IPS\DateTime::ts( $details['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
											</li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									</div>
								</div>
							</li>
						</ul>
					</i-data>
				</div>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( \IPS\Settings::i()->ipsgeoip ):
$return .= <<<IPSCONTENT

			<p class="i-color_soft i-font-size_-1 i-margin-top_3">* 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_geolocation_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$userAgent = \IPS\Http\UserAgent::parse();
$return .= <<<IPSCONTENT

		<div class="ipsInnerBox">
			<div class="i-flex i-align-items_center i-gap_2 i-padding_2">
				<div class="i-flex_00 i-basis_60">
					
IPSCONTENT;

if ( $userAgent->platform === 'Macintosh' ):
$return .= <<<IPSCONTENT

					<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/mac.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
					
IPSCONTENT;

elseif ( $userAgent->platform === 'Android' or $userAgent->platform === 'Windows Phone' ):
$return .= <<<IPSCONTENT

					<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/android.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
					
IPSCONTENT;

elseif ( $userAgent->platform === 'iPad' ):
$return .= <<<IPSCONTENT

					<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/ipad.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
					
IPSCONTENT;

elseif ( $userAgent->platform === 'iPhone' ):
$return .= <<<IPSCONTENT

					<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/iphone.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/pc.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="i-flex_11">
					<h4 class="ipsTitle ipsTitle--h5">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $userAgent->platform, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'current_device', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</div>
			</div>
			<i-data>
				<ul class="ipsData ipsData--table ipsData--settings-devices-2">
					<li class="ipsData__item">
						<div class="i-basis_180 i-color_hard">
							<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_table_user_agent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
						</div>
						<div class="">
							
IPSCONTENT;

if ( \in_array( $userAgent->browser, array( 'Android Browser', 'AppleWebKit', 'Camino', 'Chrome', 'Edge', 'Firefox', 'IEMobile', 'Midori', 'MSIE', 'Opera', 'Puffin', 'Safari', 'SamsungBrowser', 'Silk', 'UCBrowser', 'Vivaldi' ) ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$browser = str_replace( ' ', '', $userAgent->browser );
$return .= <<<IPSCONTENT

								<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/browsers/{$browser}.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" width="24" alt="" loading="lazy">
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $userAgent->browser, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $userAgent->browserVersion, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					</li>
				</ul>
			</i-data>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsEmail( $form=NULL, $login=NULL, $error=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3 i-border-bottom_3'>
	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
</div>
<div class='i-padding_3'>
	
IPSCONTENT;

if ( $form ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->reg_auth_type == 'user' or \IPS\Settings::i()->reg_auth_type == 'admin_user' ):
$return .= <<<IPSCONTENT

			<div class='ipsType_textBlock'>
				<ul class='ipsList ipsList--bullets'>
					<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_explain_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
					<li>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_explain_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</li>
				</ul>
			</div>
			<hr class='ipsHr'>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		{$form}
	
IPSCONTENT;

elseif ( $login ):
$return .= <<<IPSCONTENT

		<br><hr class='ipsHr'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->reauthenticate( $login, $error );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_admin_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>

		<ol class='ipsList ipsList--bullets i-margin-top_3'>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_admin_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_admin_3', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_admin_4', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_email_admin_5', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
		</ol>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsLinks( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3 i-border-bottom_3'>
    <h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_settings_cvb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
</div>
{$form}
IPSCONTENT;

		return $return;
}

	function settingsLoginConnect( $method, $login, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $error ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--error">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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
	
IPSCONTENT;

if ( $method->type() === \IPS\Login::TYPE_USERNAME_PASSWORD ):
$return .= <<<IPSCONTENT

		<ul class='ipsForm'>
			<li class="ipsFieldRow ipsFieldRow--fullWidth">
				
IPSCONTENT;

$authType = $method->authType();
$return .= <<<IPSCONTENT

				<label class="ipsFieldRow__label" for="auth">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					<span class="ipsFieldRow__required">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</label>
				<div class="ipsFieldRow__content">
                    <input type="email" class="ipsInput ipsInput--text" placeholder="
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
>
				</div>
			</li>
			<li class="ipsFieldRow ipsFieldRow--fullWidth">
				<label class="ipsFieldRow__label" for="password">
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					<span class="ipsFieldRow__required">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</label>
				<div class="ipsFieldRow__content">
					<input type="password" class="ipsInput ipsInput--text" placeholder="
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
>
				</div>
			</li>
			<li class="ipsFieldRow ipsFieldRow--fullWidth">
				<button type="submit" name="_processLogin" value="usernamepassword" class="ipsButton ipsButton--primary ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'login', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				
IPSCONTENT;

if ( $forgotPasswordUrl = $method->forgotPasswordUrl() ):
$return .= <<<IPSCONTENT

					<p class="i-text-align_end i-font-size_-2">
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forgotPasswordUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forgotten_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</li>
		</ul>	
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		{$method->button()}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</form>
IPSCONTENT;

		return $return;
}

	function settingsLoginMethodOff( $method, $login, $error, $blurb, $canDisassociate=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-flex i-align-items_center i-gap_2 i-background_2 i-padding_3 i-border-bottom_3'>
	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

if ( $canDisassociate ):
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=login&service={$method->id}&disassociate=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings_login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--text ipsButton--text--negative ipsButton--small i-margin-start_auto" data-confirm data-confirmSubMessage="
IPSCONTENT;

$sprintf = array($method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profilesync_sign_out_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

<div class='i-padding_3'>
	<p class="i-margin-bottom_4">
IPSCONTENT;

$val = "{$blurb}"; $sprintf = array($method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->settingsLoginConnect( $method, $login, $error, $blurb );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsLoginMethodOn( $method, $form, $canDisassociate, $photoUrl, $profileName, $extraPermissions, $login, $forceSync ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3'>
	<div class=''>
		<div class="ipsPhotoPanel 
IPSCONTENT;

if ( $photoUrl ):
$return .= <<<IPSCONTENT
ipsPhotoPanel--mini
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

if ( $photoUrl ):
$return .= <<<IPSCONTENT

				<span class='ipsUserPhoto'>
					<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $photoUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading='lazy'>
				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div>
        		<h2 class='ipsPhotoPanel__primary'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $method->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>                        
				<div>
					
IPSCONTENT;

if ( $profileName ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$sprintf = array($profileName); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profilesync_headline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profilesync_signed_in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( $login ):
$return .= <<<IPSCONTENT

					<div>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->settingsLoginConnect( $method, $login, \IPS\Member::loggedIn()->language()->addToStack('profilesync_extra_permissions_required', true, array( 'sprintf' => array( $extraPermissions ) ) ) );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

if ( $canDisassociate ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=login&service={$method->id}&disassociate=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings_login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--small" data-confirm data-confirmSubMessage="
IPSCONTENT;

$sprintf = array($method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profilesync_sign_out_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( $forceSync ):
$return .= <<<IPSCONTENT

		<hr class="ipsHr">
		<ul>
			
IPSCONTENT;

foreach ( $forceSync as $details ):
$return .= <<<IPSCONTENT

				<li class="i-margin-bottom_3">
					<p>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['label'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

if ( $details['error'] ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->settingsLoginMethodSynError( $details['error'] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
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

if ( $form or $forceSync ):
$return .= <<<IPSCONTENT

		<hr class="ipsHr">
		{$form}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsLoginMethodSynError( $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span class="i-color_warning">
IPSCONTENT;

$val = "{$error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function settingsMfa( $handlers ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $handlers ) ):
$return .= <<<IPSCONTENT

	<div class='i-background_2 i-padding_3 i-border-bottom_3'>
		<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_settings_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	</div>
	<div class='i-padding_3'>
		<p class='i-margin-bottom_4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_ucp_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<div class='ipsBlockSpacer'>
			
IPSCONTENT;

foreach ( $handlers as $key => $handler ):
$return .= <<<IPSCONTENT

				<div class="i-padding_3 ipsColumns">
					<div class='ipsColumns__primary i-basis_400'>
						<h2 class="ipsTitle ipsTitle--h4 i-margin-bottom_1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $handler->ucpTitle(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $handler->memberHasConfiguredHandler( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT
&nbsp;&nbsp;<span class='i-color_positive'><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
						<p>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $handler->ucpDesc(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
					</div>
					<div class='ipsColumns__secondary'>
						<ul class="ipsButtons ipsButtons--start">
							
IPSCONTENT;

if ( $handler->memberHasConfiguredHandler( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

								<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=mfa&act=enable&type={$key}&_new=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings_mfa", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--primary">
IPSCONTENT;

$val = "mfa_{$key}_reauth"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'or', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
								<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=mfa&act=disable&type={$key}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings_mfa", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-color_negative" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_disable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=mfa&act=enable&type={$key}&_new=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings_mfa", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
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

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_hide_online_list'] !== 2 ):
$return .= <<<IPSCONTENT

	<div class='i-background_2 i-padding_3 i-border-bottom_3 
IPSCONTENT;

if ( \count( $handlers ) ):
$return .= <<<IPSCONTENT
i-border-top_3
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'settings_privacy_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	</div>
	<div class='ipsBlockSpacer i-padding_3'>
		<div class="i-padding_3 ipsColumns">
			<div class='ipsColumns__primary i-basis_400'>
				<h2 class='ipsTitle ipsTitle--h4 i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_visibility', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<div class="i-font-size_2 i-font-weight_600 i-margin-bottom_1">
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions['is_anon'] ):
$return .= <<<IPSCONTENT
<span class='i-color_negative'><i class='fa-solid fa-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_status_hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<span class='i-color_positive'><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_status_visible', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_visibility_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->group['g_hide_online_list'] ):
$return .= <<<IPSCONTENT

				<div class='ipsColumns__secondary'>
					<div class='ipsButtons ipsButtons--start'>
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions['is_anon'] ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=updateAnon&value=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_online_status', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=updateAnon&value=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide_online_status', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( \IPS\Settings::i()->core_datalayer_enabled AND \IPS\Settings::i()->core_datalayer_include_pii AND \IPS\Settings::i()->core_datalayer_member_pii_choice ):
$return .= <<<IPSCONTENT

			<div class="i-padding_3 ipsColumns">
				<div class='ipsColumns__primary i-basis_400'>
					<h2 class='ipsTitle ipsTitle--h4 i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_pii_opt_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
&nbsp;&nbsp;
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->members_bitoptions['datalayer_pii_optout'] ):
$return .= <<<IPSCONTENT
<span class='i-color_negative'><i class='fa-solid fa-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_omitted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<span class='i-color_positive'><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_collected', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
					<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_pii_opt_out_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				</div>
				<div class='ipsColumns__secondary'>
					<div class='ipsButtons ipsButtons--start'>
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions['datalayer_pii_optout'] ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=togglePii" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_omit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=togglePii" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'datalayer_collect', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function settingsMfaPassword( $login, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-background_2 i-padding_3 i-border-bottom_3'>
    <h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ucp_mfa', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
</div>
<div class='i-padding_3'>
    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->reauthenticate( $login, $error, 'mfa_ucp_blurb_password' );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsMfaSetup( $configurationScreen, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elTwoFactorAuthentication' class='ipsModal' data-controller='core.global.core.2fa'>
	<div>
		<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" accept-charset='utf-8' data-ipsForm class="ipsForm ipsForm--fullWidth">
			<input type="hidden" name="mfa_setup" value="1">
			<input type="hidden" name="csrfKey" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			{$configurationScreen}
		</form>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function settingsOverview( $loginMethods, $canChangePassword ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
	<ul class='ipsData ipsData--table ipsData--settings-overview'>
		<li class='ipsData__item'>
			<div class='ipsData__main'>
				<h4 class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_dname_changes'] ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=username", null, "settings_username", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
		<li class='ipsData__item'>
			<div class='ipsData__main'>
				<h4 class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::loggedIn()->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( \IPS\Settings::i()->allow_email_changes != 'disabled' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Settings::i()->allow_email_changes == 'redirect' ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->allow_email_changes_target, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener" class="ipsButton ipsButton--inherit">
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=email", null, "settings_email", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsButton ipsButton--inherit">
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
		
IPSCONTENT;

if ( $canChangePassword ):
$return .= <<<IPSCONTENT

			<li class='ipsData__item'>
				<div class='ipsData__main'>
					<h4 class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					********
				</div>
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=password", null, "settings_password", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<li class='ipsData__item'>
			<div class='ipsData__main'>
				<h4 class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_completion_status', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				<div class='i-margin-top_2'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->profileNextStep( \IPS\Member::loggedIn()->nextProfileStep(), false, false );
$return .= <<<IPSCONTENT
</div>
			</div>
		</li>
		
IPSCONTENT;

foreach ( $loginMethods as $id => $details ):
$return .= <<<IPSCONTENT

			<li class='ipsData__item'>
				<div class="ipsData__icon">
					<div class="ipsUserPhoto ipsUserPhoto--mini">
						
IPSCONTENT;

if ( isset( $details['icon'] ) ):
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "default_photo.png", "core", 'global', false );
$return .= <<<IPSCONTENT
" loading="lazy" alt="">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
				<div class='ipsData__main'>
					<h4 class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['blurb'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</div>
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=login&service={$id}", null, "settings_login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profilesync_configure', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function settingsPassword( $form=null ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3 i-border-bottom_3'>
	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
</div>

<div class='i-padding_3'>
	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->passwordResetForced() ):
$return .= <<<IPSCONTENT

		<p class='ipsMessage ipsMessage--warning'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password_reset_required', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
    
IPSCONTENT;

elseif ( isset( \IPS\Widget\Request::i()->success ) AND \IPS\Widget\Request::i()->success == 1 ):
$return .= <<<IPSCONTENT

        <p class="ipsMessage ipsMessage--success">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'password_changed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $form ):
$return .= <<<IPSCONTENT

		{$form}
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password_admin_1', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>

		<ol class='ipsList ipsList--bullets i-margin-top_3'>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password_admin_2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password_admin_3', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password_admin_4', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
			<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password_admin_5', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
		</ol>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsPrivacy(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-padding_3 ipsBlockSpacer'>
	
IPSCONTENT;

if ( \IPS\Settings::i()->pii_type=='on' ):
$return .= <<<IPSCONTENT

		<div class="i-padding_3">
			<div class='ipsColumns'>
				<div class='ipsColumns__primary i-basis_400'>
					<h2 class='ipsTitle ipsTitle--h4 i-margin-bottom_1' id="piiDataRequest">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pii_data_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pii_data_request_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				</div>
				<div class='ipsColumns__secondary'>
					<div class='ipsButtons ipsButtons--start'>
						
IPSCONTENT;

if ( \IPS\Member\PrivacyAction::canRequestPiiData() AND !\IPS\Member\PrivacyAction::hasPiiRequest(NULL, TRUE )  ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=requestPiiData" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_pii_data_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\Member\PrivacyAction::hasPiiRequest(NULL, TRUE ) ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=downloadPiiData" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_pii_data', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
			
IPSCONTENT;

if ( \IPS\Member\PrivacyAction::hasPiiRequest(NULL, FALSE ) ):
$return .= <<<IPSCONTENT

				<div class="ipsMessage ipsMessage--success i-margin-top_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pii_data_request_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

elseif ( \IPS\Settings::i()->pii_type=='redirect' ):
$return .= <<<IPSCONTENT

		<div class="i-padding_3 ipsColumns">
			<div class='ipsColumns__primary i-basis_400'>
				<h2 class='ipsTitle ipsTitle--h4 i-margin-bottom_1' id="piiDataRequest">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pii_data_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pii_data_request_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			<div class='ipsColumns__secondary'>
				<div class='ipsButtons ipsButtons--start'>
					<a href='
IPSCONTENT;

$return .= \IPS\Settings::i()->pii_link;
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener"  class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_pii_data_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canUseAccountDeletion()  ):
$return .= <<<IPSCONTENT

		<div class="i-padding_3">
			<div class='ipsColumns'>
				<div class='ipsColumns__primary i-basis_400'>
					<h2 class='ipsTitle ipsTitle--h4 i-margin-bottom_1' id="requestAccountDeletion">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_deletion', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_deletion_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				</div>
				
IPSCONTENT;

if ( \IPS\Member\PrivacyAction::canDeleteAccount() ):
$return .= <<<IPSCONTENT

					<div class='ipsColumns__secondary'>
						<div class='ipsButtons ipsButtons--start'>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=requestAccountDeletion" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'request_account_deletion', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->isDeletionPending ):
$return .= <<<IPSCONTENT

			    <div class='ipsMessage ipsMessage--error i-marign-top_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_deletion_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->canCancelDeletion ):
$return .= <<<IPSCONTENT

			    <div class='ipsMessage ipsMessage--error i-marign-top_3'>
			        
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_account_delete_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<br><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&do=cancelAccountDeletion" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "settings", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class=''>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			    </div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
	
IPSCONTENT;

elseif ( \IPS\Settings::i()->right_to_be_forgotten_type=='redirect' ):
$return .= <<<IPSCONTENT

		<div class="i-padding_3 ipsColumns">
			<div class='ipsColumns__primary i-basis_400'>
				<h2 class='ipsTitle ipsTitle--h4 i-margin-bottom_1' id="requestAccountDeletion">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_deletion', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_deletion_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			<div class='ipsColumns__secondary'>
				<div class='ipsButtons ipsButtons--start'>
					<a href='
IPSCONTENT;

$return .= \IPS\Settings::i()->right_to_be_forgotten_link;
$return .= <<<IPSCONTENT
' target="_blank" rel="noopener" class='ipsButton ipsButton--small ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'request_account_deletion', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsProfileSyncLogin( $method, $login, $error ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="i-margin-bottom_3">
IPSCONTENT;

$sprintf = array($method->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profilesync_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", \IPS\Request::i()->app )->settingsLoginConnect( $method, $login, $error );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsReferrals( $table, $url, $rules ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3 i-border-bottom_3'>
	<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'referrals', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
</div>

<div class='i-padding_3'>
	<div class='ipsInnerBox i-margin-bottom_3 cReferralLinks' data-controller='core.front.system.referrals'>
		<div class='i-padding_3 cReferrals_directLink'>
			<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'referral_directlink', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			<span class='cReferrals_directLink_link'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			<input class='cReferrals_directLink_input ipsHide ipsInput ipsInput--text' type="text" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<button class="cReferrer_copy cReferrer_copy_link ipsButton ipsButton--secondary ipsButton--tiny" data-clipboard-text="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
		
IPSCONTENT;

if ( \count( \IPS\core\ReferralBanner::roots() ) ):
$return .= <<<IPSCONTENT

		<div class='cReferrals_grid i-padding_2'>
		
IPSCONTENT;

$count = 0;
$return .= <<<IPSCONTENT

		<div class='cReferrals_grid_row i-margin-top_3'>
			
IPSCONTENT;

foreach ( \IPS\core\ReferralBanner::roots() as $banner ):
$return .= <<<IPSCONTENT

				<div class='cReferrals_grid_item ipsBox'>
					<div class='cReferrals_grid_item__image' style='background-image: url("
IPSCONTENT;

$return .= \IPS\File::get( "core_ReferralBanners", $banner->url )->url;
$return .= <<<IPSCONTENT
")'>
						{$banner->_title}
					</div>
					<div class='cReferrals_grid_item__body i-padding_3'>
						<div>
							<div class='i-margin-bottom_3'>
								<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'referral_html_banner', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<input type='text' class='ipsInput ipsInput--text ipsInput--wide' id="bannerValue_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $banner->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="&lt;a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'&gt;&lt;img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_ReferralBanners", $banner->url )->url;
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
'&gt;&lt;/a&gt;">
								<button class="cReferrer_copy ipsButton ipsButton--soft ipsButton--small ipsButton--wide i-margin-top_2" data-clipboard-target="#bannerValue_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $banner->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'copy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
							</div>
						</div>
					</div>
				</div>
				
IPSCONTENT;

$count++;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $count % 3 == 0 and $count !== \count( \IPS\core\ReferralBanner::roots() ) ):
$return .= <<<IPSCONTENT

					</div>
					<div class='cReferrals_grid_row i-margin-top_3'>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	</div>

	<div class="ipsInnerBox i-margin-bottom_3">
		<div class="i-background_2 i-padding_3">
			<strong><i class="fa-solid fa-users"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'referrals_yours', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		</div>
		<div class="i-padding_3">
			{$table}
		</div>
	</div>

	
IPSCONTENT;

if ( $rules ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "nexus", 'front' )->referralRulesCommission( $rules );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsSecureAccount( $canChangePassword, $canConfigureMfa, $hasConfiguredMfa, $loginMethods, $oauthApps=0 ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('secure_account'), \IPS\Member::loggedIn()->language()->addToStack('secure_account_blurb') );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $canChangePassword ):
$return .= <<<IPSCONTENT

	<div class="ipsInnerBox i-margin-bottom_3">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="i-padding_3">
			<p>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secure_account_change_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
			<div class="i-margin-top_3">
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=password", null, "settings_password", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="ipsButton ipsButton--primary ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $canConfigureMfa ):
$return .= <<<IPSCONTENT

	<div class="ipsInnerBox i-margin-bottom_3">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_settings_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="i-padding_3">
			<p>
				
IPSCONTENT;

if ( $hasConfiguredMfa ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secure_account_mfa_revise', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secure_account_mfa_setup', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
			<div class="i-margin-top_3">
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=mfa", null, "settings_mfa", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="ipsButton ipsButton--primary ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_settings_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $loginMethods ) ):
$return .= <<<IPSCONTENT

	<div class="ipsInnerBox i-margin-bottom_3">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secure_account_login_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="i-padding_3">
			<p class="i-margin-bottom_3">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secure_account_login_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
			<i-data>
				<ul class="ipsData ipsData--table ipsData--settings-secure">
					
IPSCONTENT;

foreach ( $loginMethods as $id => $details ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<div class="ipsData__icon">
								<div class="ipsUserPhoto ipsUserPhoto--mini">
									
IPSCONTENT;

if ( isset( $details['icon'] ) ):
$return .= <<<IPSCONTENT

										<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "default_photo.png", "core", 'global', false );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</div>
							<div class='ipsData__main'>
								<h4 class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
								<div class='ipsData__desc'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['blurb'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
								<div>
									<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=login&service={$id}", null, "settings_login", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profilesync_configure', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</div>
							</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $oauthApps ):
$return .= <<<IPSCONTENT

	<div class="ipsInnerBox i-margin-bottom_3">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="i-padding_3">
			<p>
				
IPSCONTENT;

$pluralize = array( $oauthApps ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secure_account_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

			</p>
			<div class="i-margin-top_3">
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=apps", null, "settings_apps", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="ipsButton ipsButton--primary ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_oauth_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function settingsSignature( $form, $sigLimits ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $sigLimits[1] != "" or $sigLimits[2] or $sigLimits[3] or $sigLimits[4] or $sigLimits[5] ):
$return .= <<<IPSCONTENT

	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'signature_restrictions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ensure_signature_restrictions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</p>
	<ul class='ipsList ipsList--inline'>
		
IPSCONTENT;

if ( $sigLimits[1] != "" ):
$return .= <<<IPSCONTENT

			<li>
IPSCONTENT;

if ( $sigLimits[1] ):
$return .= <<<IPSCONTENT
<i class='fa-solid fa-check'></i> 
IPSCONTENT;

$pluralize = array( $sigLimits[1] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sig_max_imagesr', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<i class='fa-solid fa-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sig_max_imagesr_none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $sigLimits[2] or $sigLimits[3] ):
$return .= <<<IPSCONTENT

			<li><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$sprintf = array($sigLimits[2], $sigLimits[3]); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sig_max_imgsize', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $sigLimits[4] ):
$return .= <<<IPSCONTENT

			<li><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$pluralize = array( $sigLimits[4] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sig_max_urls', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $sigLimits[5] ):
$return .= <<<IPSCONTENT

			<li><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$pluralize = array( $sigLimits[5] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sig_max_lines', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
	<hr class='ipsHr'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

{$form}
IPSCONTENT;

		return $return;
}

	function settingsUsername( $form, $made, $allowed, $since, $days ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3 i-border-bottom_3'>
	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
</div>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_dname_changes'] != -1 ):
$return .= <<<IPSCONTENT

	<p class="ipsMessage i-margin_1">
IPSCONTENT;

$sprintf = array($made, $allowed, $since->localeDate(), $days); $pluralize = array( $allowed ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_username_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

{$form}
IPSCONTENT;

		return $return;
}

	function settingsUsernameLimitReached( $message ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-background_2 i-padding_3 i-border-bottom_3'>
    <h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
</div>
<div class='i-padding_3'>
    {$message}
</div>

IPSCONTENT;

		return $return;
}

	function terms(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('reg_terms') );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
ipsBox
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsRichText i-padding_3'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('reg_rules_value') );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function unfollowFromEmail( $title, $member, $form, $choice ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $choice ):
$return .= <<<IPSCONTENT

	<div class='ipsBox'>
		<p class='i-text-align_center i-font-size_6'>
			<i class='fa-solid fa-envelope'></i>
		</p>

		<h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_guest_unfollow_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>

		<div class='i-font-size_2 i-text-align_center ipsRichText'>
			
IPSCONTENT;

if ( $choice == 'single' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$sprintf = array($title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_guest_unfollowed_thing', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

elseif ( $choice == 'all' ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_guest_unfollowed_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<br>
		<p class='i-text-align_center'>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "/", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_community_home', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</p>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='i-padding_3'>
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_guest_unfollow_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<p class='i-color_soft'>
IPSCONTENT;

$sprintf = array($member->name, $member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_guest_followed_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
		{$form}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function unsubscribed( $action ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('unsubscribed') );
$return .= <<<IPSCONTENT

<div class='ipsBox ipsBox--padding'>
	
IPSCONTENT;

if ( $action === 'markSolved' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unsubscribed_solved_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $action === 'expertNudge' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unsubscribed_expert_nudge_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unsubscribed_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function warningRow( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	<li class="ipsData__item 
IPSCONTENT;

if ( method_exists( $row, 'tableClass' ) && $row->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ">
		<div class='ipsData__icon'>
			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=view&id={$row->member}&w={$row->id}", null, "warn_view", array( \IPS\Member::load( $row->member )->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $row->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'wan_action_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'>
                <span class="ipsWarningPoints 
IPSCONTENT;

if ( $row->expire_date == 0 ):
$return .= <<<IPSCONTENT
ipsWarningPoints--removed
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			</a>
		</div>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<h4 class='ipsData__title'>				
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_announcement', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $row->tableHoverUrl ):
$return .= <<<IPSCONTENT
data-ipsHover
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

if ( $row->mapped('title') ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
				</h4>
				<ul class='ipsList ipsList--sep'>
					
IPSCONTENT;

if ( \IPS\Settings::i()->warnings_acknowledge ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

if ( $row->acknowledged ):
$return .= <<<IPSCONTENT

								<strong class='i-color_positive'><i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_acknowledged', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<strong class='i-color_soft'><i class='fa-regular fa-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_not_acknowledged', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li class='i-color_soft'>
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $row->moderator )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warned_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $row->__get( $row::$databaseColumnMap['date'] ) instanceof \IPS\DateTime ) ? $row->__get( $row::$databaseColumnMap['date'] ) : \IPS\DateTime::ts( $row->__get( $row::$databaseColumnMap['date'] ) );$return .= $val->html();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $row->expire_date > 0 ):
$return .= <<<IPSCONTENT
<em><strong>(
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $row->expire_date )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_expires', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
)</em></strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $row->expire_date == 0 ):
$return .= <<<IPSCONTENT

						<em><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_no_longer_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $row->removed_on ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $row->removed_on )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_expired_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</em></strong>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				</ul>
				
IPSCONTENT;

if ( $row->note_member or ($row->note_mods and \IPS\Member::loggedIn()->modPermission('mod_see_warn')) ):
$return .= <<<IPSCONTENT

					<div class='ipsFluid i-margin-top_3'>
						
IPSCONTENT;

if ( $row->note_member ):
$return .= <<<IPSCONTENT

							<div>
								<strong class="i-color_hard i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_member_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</strong>
								<div class="ipsRichText">{$row->note_member}</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $row->note_mods and \IPS\Member::loggedIn()->modPermission('mod_see_warn') ):
$return .= <<<IPSCONTENT

							<div>
								<strong class="i-color_hard i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_mod_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
:</strong>
								<div class="ipsRichText">{$row->note_mods}</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( $row->canDelete() ):
$return .= <<<IPSCONTENT

				<div>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url('delete')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke_this_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action="revoke" class='ipsButton ipsButton--small ipsButton--inherit' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke_this_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-size='medium'><i class="fa-solid fa-rotate-left"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke_this_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

			<div class='ipsData__mod'>
				<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( !$row->active ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class="ipsInput ipsInput--toggle">
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}