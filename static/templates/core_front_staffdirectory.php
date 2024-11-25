<?php
namespace IPS\Theme;
class class_core_front_staffdirectory extends \IPS\Theme\Template
{	function layout_blocks( $user ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding-block_3 i-flex i-flex-direction_column i-gap_2 i-text-align_center">
	<div class="i-flex i-justify-content_center">
		<div class="i-basis_120">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $user->member(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
	</div>
	<div>
		<h3 class="ipsTitle ipsTitle--h4">
			
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_name_" . $user->id )  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) ) ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->member()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "core_staff_directory_name_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$val = "core_staff_directory_name_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				{$user->member()->link()}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h3>
		
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_title_" . $user->id ) ):
$return .= <<<IPSCONTENT

			<p class="i-font-weight_500 i-color_soft i-margin-bottom_2">
IPSCONTENT;

$val = "core_staff_directory_title_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_blocks", "staffInfo:before", [ $user ] );
$return .= <<<IPSCONTENT
<ul class="ipsList ipsList--inline i-justify-content_center i-gap_3 i-color_soft i-link-color_inherit i-font-weight_500 i-margin-top_auto" data-ips-hook="staffInfo">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_blocks", "staffInfo:inside-start", [ $user ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_bio_" . $user->id ) ):
$return .= <<<IPSCONTENT

			<li><a href="#staffFull_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-content="#staffFull_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog-size="medium" data-ipsdialog-title="
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_name_" . $user->id )  ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "core_staff_directory_name_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->member()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
: Bio"><i class="fa-regular fa-address-card i-margin-end_icon i-opacity_6"></i>Bio</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->members_disable_pm AND !$user->member()->members_disable_pm AND \IPS\Member::loggedIn()->member_id AND \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

			<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$user->member()->member_id}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-forcereload data-ipsdialog-remotesubmit data-ipsdialog-flashmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_sent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-message i-margin-end_icon i-opacity_6"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_blocks", "staffInfo:inside-end", [ $user ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_blocks", "staffInfo:after", [ $user ] );
$return .= <<<IPSCONTENT

	<div id="staffFull_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsHide i-padding_3 i-font-size_2">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('core_staff_directory_bio_' . $user->id) );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function layout_blocks_preview(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='cStaffDirPreview cStaffDirPreview_blocks'>
	<div class='ipsSpanGrid'>
		<div class='ipsSpanGrid__3 i-text-align_center cStaffDirPreview_block'>
			<span class='cStaffDirPreview_photo'></span><br>
			<span class='cStaffDirPreview_title'></span>
		</div>
		<div class='ipsSpanGrid__3 i-text-align_center cStaffDirPreview_block'>
			<span class='cStaffDirPreview_photo'></span><br>
			<span class='cStaffDirPreview_title'></span>
		</div>
		<div class='ipsSpanGrid__3 i-text-align_center cStaffDirPreview_block'>
			<span class='cStaffDirPreview_photo'></span><br>
			<span class='cStaffDirPreview_title'></span>
		</div>
		<div class='ipsSpanGrid__3 i-text-align_center cStaffDirPreview_block'>
			<span class='cStaffDirPreview_photo'></span><br>
			<span class='cStaffDirPreview_title'></span>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function layout_full( $user ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-flex i-gap_3 i-align-items_center">
	<div class="i-basis_90 i-align-self_start">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $user->member(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
	<div class="i-flex_11">
		<div class="i-flex i-flex-wrap_wrap i-gap_1">
			<div class="i-flex_11">
				<h3 class="ipsTitle ipsTitle--h4">
					
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_name_" . $user->id )  ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) ) ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->member()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "core_staff_directory_name_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$val = "core_staff_directory_name_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						{$user->member()->link()}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</h3>
				
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_title_" . $user->id ) ):
$return .= <<<IPSCONTENT

					<p class="i-font-weight_600">
IPSCONTENT;

$val = "core_staff_directory_title_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->members_disable_pm AND !$user->member()->members_disable_pm AND \IPS\Member::loggedIn()->member_id AND \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_full", "staffInfo:before", [ $user ] );
$return .= <<<IPSCONTENT
<ul class="ipsList ipsList--inline i-gap_3 i-color_soft i-link-color_inherit i-font-weight_500" data-ips-hook="staffInfo">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_full", "staffInfo:inside-start", [ $user ] );
$return .= <<<IPSCONTENT

					<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$user->member()->member_id}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-forcereload data-ipsdialog-remotesubmit data-ipsdialog-flashmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_sent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-message i-margin-end_icon i-opacity_6"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_full", "staffInfo:inside-end", [ $user ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_full", "staffInfo:after", [ $user ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_bio_" . $user->id ) ):
$return .= <<<IPSCONTENT

			<div class="i-margin-top_3">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('core_staff_directory_bio_' . $user->id) );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function layout_full_preview(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='cStaffDirPreview cStaffDirPreview_full'>
	<div class='cStaffDirPreview_block cStaffDirPreview_row'>
		<span class='cStaffDirPreview_photo'></span><br>
		<span class='cStaffDirPreview_title'></span>
		<span class='cStaffDirPreview_text'></span>
	</div>
	<div class='cStaffDirPreview_block cStaffDirPreview_row'>
		<span class='cStaffDirPreview_photo'></span><br>
		<span class='cStaffDirPreview_title'></span>
		<span class='cStaffDirPreview_text'></span>
	</div>
	<div class='cStaffDirPreview_block cStaffDirPreview_row'>
		<span class='cStaffDirPreview_photo'></span><br>
		<span class='cStaffDirPreview_title'></span>
		<span class='cStaffDirPreview_text'></span>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function layout_half( $user ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3 i-flex i-gap_3 i-align-items_center">
	<div class="i-basis_90">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $user->member(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
	<div class="i-flex_11">
		<h3 class="ipsTitle ipsTitle--h4">
			
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_name_" . $user->id )  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) ) ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->member()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "core_staff_directory_name_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$val = "core_staff_directory_name_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				{$user->member()->link()}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h3>
		
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_title_" . $user->id )  ):
$return .= <<<IPSCONTENT

			<p class="i-font-weight_500 i-color_soft i-margin-bottom_2">
IPSCONTENT;

$val = "core_staff_directory_title_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_half", "staffInfo:before", [ $user ] );
$return .= <<<IPSCONTENT
<ul class="ipsList ipsList--inline i-gap_3 i-color_soft i-link-color_inherit i-font-weight_500 i-margin-top_1" data-ips-hook="staffInfo">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_half", "staffInfo:inside-start", [ $user ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_bio_" . $user->id ) ):
$return .= <<<IPSCONTENT

				<li><a href="#staffFull_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-content="#staffFull_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog-size="medium" data-ipsdialog-title="
IPSCONTENT;

if ( $user->id AND \IPS\Member::loggedIn()->language()->checkKeyExists( "core_staff_directory_name_" . $user->id )  ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "core_staff_directory_name_{$user->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->member()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
: Bio"><i class="fa-regular fa-address-card i-margin-end_icon i-opacity_6"></i>Bio</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->members_disable_pm AND !$user->member()->members_disable_pm AND \IPS\Member::loggedIn()->member_id AND \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

				<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$user->member()->member_id}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-forcereload data-ipsdialog-remotesubmit data-ipsdialog-flashmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_sent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-message i-margin-end_icon i-opacity_6"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_half", "staffInfo:inside-end", [ $user ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/layout_half", "staffInfo:after", [ $user ] );
$return .= <<<IPSCONTENT

		<div id="staffFull_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $user->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-padding_3 ipsHide">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('core_staff_directory_bio_' . $user->id) );
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function layout_half_preview(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='cStaffDirPreview cStaffDirPreview_full'>
	<div class='ipsSpanGrid'>
		<div class='ipsSpanGrid__6 cStaffDirPreview_block cStaffDirPreview_row'>
			<span class='cStaffDirPreview_photo'></span><br>
			<span class='cStaffDirPreview_title'></span>
			<span class='cStaffDirPreview_text'></span>
		</div>
		<div class='ipsSpanGrid__6 cStaffDirPreview_block cStaffDirPreview_row'>
			<span class='cStaffDirPreview_photo'></span><br>
			<span class='cStaffDirPreview_title'></span>
			<span class='cStaffDirPreview_text'></span>
		</div>
	</div>
	<div class='ipsSpanGrid'>
		<div class='ipsSpanGrid__6 cStaffDirPreview_block cStaffDirPreview_row'>
			<span class='cStaffDirPreview_photo'></span><br>
			<span class='cStaffDirPreview_title'></span>
			<span class='cStaffDirPreview_text'></span>
		</div>
		<div class='ipsSpanGrid__6 cStaffDirPreview_block cStaffDirPreview_row'>
			<span class='cStaffDirPreview_photo'></span><br>
			<span class='cStaffDirPreview_title'></span>
			<span class='cStaffDirPreview_text'></span>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function template( $groups, $userIsStaff=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<header class="ipsPageHeader ipsPageHeader--staff">
	<div class="ipsPageHeader__row">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "header:before", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "header:inside-start", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "title:before", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "title:inside-start", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'staff_directory', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "title:inside-end", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "title:after", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "header:inside-end", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "header:after", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $userIsStaff ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "buttons:before", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="buttons" class="ipsButtons">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "buttons:inside-start", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=staffdirectory&controller=directory&do=form", null, "staffdirectory", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" class="ipsButton ipsButton--primary" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'leader_edit_mine', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-pencil"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'leader_edit_mine', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</li>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "buttons:inside-end", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/staffdirectory/template", "buttons:after", [ $groups,$userIsStaff ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</header>


IPSCONTENT;

foreach ( $groups as $group ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$members = $group->members();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $members ) ):
$return .= <<<IPSCONTENT

		<section class="ipsBox cStaffDirectory">
			<h2 class="ipsBox__header">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $group->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
			<div class="ipsBox__content">
			    <div class="ipsGrid ipsGrid--lines ipsGrid--staff
IPSCONTENT;

if ( $group->template == 'layout_full' ):
$return .= <<<IPSCONTENT
-directory-full i-basis_100p
IPSCONTENT;

elseif ( $group->template == 'layout_half' ):
$return .= <<<IPSCONTENT
-directory-half i-basis_300
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 i-basis_200
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
			        
IPSCONTENT;

foreach ( $members as $member ):
$return .= <<<IPSCONTENT

			            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "staffdirectory", "core", 'front' )->{$group->template}( $member );
$return .= <<<IPSCONTENT

			        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			    </div>
			</div>
		</section>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}