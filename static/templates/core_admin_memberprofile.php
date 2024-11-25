<?php
namespace IPS\Theme;
class class_core_admin_memberprofile extends \IPS\Theme\Template
{	function basicInformation( $member, $canChangePassword, $hasPassword, $activeIntegrations, $actions, $activeSubscription ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$coverPhoto = $member->coverPhoto();
$return .= <<<IPSCONTENT

<div class='acpMemberView_info ipsBox'>
	<div class='acpMemberView_coverPhoto' data-controller='core.global.core.coverPhoto' data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-coverOffset='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

			<div class='ipsCoverPhoto__container'>
				<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCoverPhoto__image' data-action="toggleCoverPhoto" alt=''>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $coverPhoto->editable and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_photo' ) and ( !$member->isAdmin() or \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_photo_admin' ) ) ):
$return .= <<<IPSCONTENT

			<a href='#editCover_menu' id="editCover" data-ipsMenu data-ipsMenu-activeClass='acpMemberView_editButton_active' class='acpMemberView_editButton' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_cover_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-hideOnCoverEdit data-role='coverPhotoOptions'><i class='fa-solid fa-pencil'></i><i class='fa-solid fa-caret-down'></i></a>
			<div id='editCover_menu' class='ipsMenu ipsHide'>
				<ul>
					<li class="ipsMenu_item">
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'do', 'coverPhotoUpload' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_photo_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_photo_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

						<li class='ipsMenu_item ipsHide' data-role="photoEditOption">
							<a href='#' data-action='positionCoverPhoto'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_photo_reposition', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
						<li class='ipsMenu_item' data-role="photoEditOption">
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'do', 'coverPhotoRemove' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='removeCoverPhoto'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cover_photo_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='acpMemberView_photo'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'large' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_photo' ) and ( !$member->isAdmin() or \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_photo_admin' ) ) ):
$return .= <<<IPSCONTENT

			<a href='#editPhoto_menu' class='acpMemberView_editButton' data-ipsMenu data-ipsMenu-activeClass='acpMemberView_editButton_active' id="editPhoto" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_profile_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip><i class='fa-solid fa-pencil'></i><i class='fa-solid fa-caret-down'></i></a>
			<div id='editPhoto_menu' class='ipsMenu ipsHide'>
				<ul>
					<li class="ipsMenu_item">
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'do', 'photo' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_photo_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_photo_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					
IPSCONTENT;

if ( $member->pp_photo_type == 'custom' ):
$return .= <<<IPSCONTENT

						<li class="ipsMenu_item">
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'do', 'photoCrop' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_photo_crop', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_photo_crop', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
						<li class="ipsMenu_item">
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'do', 'photoResize' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_photo_resize', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_photo_resize', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $member->pp_photo_type and !$member->pp_photo_type != 'none' ):
$return .= <<<IPSCONTENT

						<li class="ipsMenu_item">
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'photo', 'remove' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_photo_delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='i-padding_2'>
		<p class='i-flex i-align-items_center acpMemberView_username' data-controller='core.admin.core.editable' 
IPSCONTENT;

if ( ! $member->name ):
$return .= <<<IPSCONTENT
data-default='empty'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
data-url='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString('do', 'name'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) ):
$return .= <<<IPSCONTENT

				<span data-name='name' class="i-flex_11">
					<span data-role='text' class="i-font-size_6 i-font-weight_600 i-color_hard">
IPSCONTENT;

if ( $member->name ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_name_missing_add', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
				</span>
				<a href='#' data-role='edit' class='acpMemberView_editButton' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip><i class='fa-solid fa-pencil'></i></a>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

 				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

 			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
		<p class='i-flex i-align-items_center i-font-weight_600 acpMemberView_email i-margin-top_2' data-controller='core.admin.core.editable' data-url='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString('do', 'email'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) ):
$return .= <<<IPSCONTENT

				<span data-name='email' class="i-flex_11">
					<span data-role="text" 
IPSCONTENT;

if ( \IPS\Email::usingCicEmail() AND \IPS\Email::emailIsBlocked( $member->email ) ):
$return .= <<<IPSCONTENT
 class="i-color_warning"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</span>
				</span>
				
IPSCONTENT;

if ( \IPS\Email::usingCicEmail() AND \IPS\cloud\Email::emailCanBeUnblocked($member->email) ):
$return .= <<<IPSCONTENT

				<a href='#' data-role='request_unblock' class='acpMemberView_editButton acpMemberView_requestUnblockButton' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'request_unblock_email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-member-id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-member-email='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-triangle-exclamation'></i></a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<a href='#' data-role='edit' class='acpMemberView_editButton' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_email_address', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip><i class='fa-solid fa-pencil'></i></a>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

 				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

 			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
		<p class='i-color_soft'>
IPSCONTENT;

$htmlsprintf = array($member->joined->html()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_joined', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

if ( $member->referredBy() ):
$return .= <<<IPSCONTENT

			<p class="i-color_soft">
				
IPSCONTENT;

$htmlsprintf = array($member->referredBy()->acpURL(), $member->referredBy()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_referred_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $activeSubscription and $activeSubscription->package ):
$return .= <<<IPSCONTENT

			<p class="i-text-align_center i-color_positive">
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) and $activeSubscription->purchase and $activeSubscription->package ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeSubscription->purchase->acpUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-link-color_inherit">
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<i class="fa-solid fa-certificate"></i> &nbsp; 
IPSCONTENT;

$sprintf = array($activeSubscription->package->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_subs_subscriber', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) and $activeSubscription->purchase and $activeSubscription->package ):
$return .= <<<IPSCONTENT

				</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<ul class="ipsButtons ipsButtons--fill i-margin-top_3">
			
IPSCONTENT;

if ( \count( $actions ) ):
$return .= <<<IPSCONTENT

				<li><a href='#memberActions_menu' data-ipsMenu id="memberActions" class='ipsButton ipsButton--primary ipsButton--large'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_actions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' class='ipsButton ipsButton--text'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_public_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		</ul>
		
IPSCONTENT;

if ( \count( $actions ) ):
$return .= <<<IPSCONTENT

		<div id='memberActions_menu' class='ipsMenu ipsMenu_normal ipsHide'>
			<ul>
				
IPSCONTENT;

foreach ( $actions as $item ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item 
IPSCONTENT;

if ( isset( $item['class'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( $item['target'] ) ):
$return .= <<<IPSCONTENT
target='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['target'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

if ( $item['target'] == '_blank' ):
$return .= <<<IPSCONTENT
 rel="noopener"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $item['data'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

foreach ( $item['data'] as $k => $v ):
$return .= <<<IPSCONTENT
 data-
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

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
							
IPSCONTENT;

if ( isset( $item['icon'] ) ):
$return .= <<<IPSCONTENT

								<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<span data-role="title">
IPSCONTENT;

$val = "{$item['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</a>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) ):
$return .= <<<IPSCONTENT

					<li class='ipsMenu_item'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'do', 'edit' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_member_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-sliders"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_member_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $canChangePassword ):
$return .= <<<IPSCONTENT

					<li class='ipsMenu_item'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'do', 'password' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
							<i class="fa-solid fa-lock"></i>
							
IPSCONTENT;

if ( $hasPassword ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $activeIntegrations ) ):
$return .= <<<IPSCONTENT

			<div data-controller="core.admin.members.lazyLoadingProfileBlock" data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'lazyBlock', 'block' => 'IPS\core\extensions\core\MemberACPProfileBlocks\LoginMethods' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				<div class="ipsLoading i-opacity_4">
					<hr class='ipsHr'>
					<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'active_account_integrations', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<i-data>
						<ul class='ipsData ipsData--table ipsData--compact ipsData--active-integrations acpMemberView_integrations'>
							
IPSCONTENT;

foreach ( $activeIntegrations as $title ):
$return .= <<<IPSCONTENT

								<li class='ipsData__item'>
									<div class='ipsData__icon'>
										<span class="ipsUserPhoto">
											<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "default_photo.png", "core", 'global', false );
$return .= <<<IPSCONTENT
' loading="lazy" alt="">
										</span>
									</div>
									<div class='ipsData__main'>
										
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) ):
$return .= <<<IPSCONTENT

											<ul class="ipsControlStrip">
												<li class="ipsControlStrip_button">
													<a href="#" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profilesync_configure', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="ipsControlStrip_icon fa-solid fa-pencil"></i></a>
												</li>
												<li class="ipsControlStrip_button">
													<a href="#" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="ipsControlStrip_icon fa-solid fa-xmark-circle"></i></a>
												</li>
											</ul>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										<p class='acpMemberView_integrations_text'>
											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
											<span class='i-font-size_1 i-color_soft'>&nbsp;</span>
										</p>
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

	</div>
</div>

IPSCONTENT;

		return $return;
}

	function clubs( $member, $clubs ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
	<ul class='ipsData ipsData--table ipsData--clubs'>
		
IPSCONTENT;

foreach ( $clubs as $club ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item 
IPSCONTENT;

if ( \IPS\Settings::i()->clubs_require_approval and !$club->approved ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
				<div class='ipsData__icon'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core", 'front' )->clubIcon( $club, 'tiny' );
$return .= <<<IPSCONTENT

				</div>
				<div class='ipsData__main'>
					<h4 class='ipsData__title_container'>
						
IPSCONTENT;

if ( \IPS\Settings::i()->clubs_require_approval and !$club->approved ):
$return .= <<<IPSCONTENT

							<span><span class="ipsBadge ipsBadge--small ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unapproved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span></span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $club->featured ):
$return .= <<<IPSCONTENT

								<span><span class="ipsBadge ipsBadge--small ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-star'></i></span></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<span>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						</span>
					</h4>
					<div class='ipsData__desc ipsTruncate_3'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->about, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</div>
				</div>
				<ul class='ipsData__stats'>
					<li>
						
IPSCONTENT;

$val = "club_{$club->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</li>
					
IPSCONTENT;

if ( $club->type !== $club::TYPE_PUBLIC ):
$return .= <<<IPSCONTENT

						<li class="i-color_soft">
							
IPSCONTENT;

$pluralize = array( $club->members ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'clubs', 'clubs_edit' ) ):
$return .= <<<IPSCONTENT

					<div class='i-basis_100 i-margin-top_1'>
						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=clubs&controller=clubs&do=edit&id={$club->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit ipsButton--small ipsButton--wide'><i class='fa-solid fa-pencil'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_club', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function contentBreakdown( $member, $percentages, $rawCounts ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class='acpStatsBar i-margin-bottom_2'>
		
IPSCONTENT;

foreach ( $percentages as $app => $percent ):
$return .= <<<IPSCONTENT

			<div class='acpStatsBar_segment' style='width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $percent, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%;' title='
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "memberprofile", \IPS\Request::i()->app )->contentStatisticsTooltip( $app, $rawCounts[ $app ], TRUE );
$return .= <<<IPSCONTENT
' data-ipsTooltip-label='
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "memberprofile", \IPS\Request::i()->app )->contentStatisticsTooltip( $app, $rawCounts[ $app ] );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsTooltip-safe></div>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	<ul class='ipsList ipsList--inline'>
		
IPSCONTENT;

foreach ( $percentages as $app => $percent ):
$return .= <<<IPSCONTENT

			<li class='acpStatsBar_legend'>
				<span class='acpStatsBar_preview'></span>
				
IPSCONTENT;

if ( $app == 'core' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'module__core_status', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$val = "__app_{$app}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function contentStatisticsTooltip( $app, $counts, $noHtml=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $noHtml ):
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $counts as $class => $count ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$class::$title}_pl"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<strong>
		
IPSCONTENT;

if ( $app == 'core' ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'module__core_status', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$val = "__app_{$app}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</strong>
	<table>
		
IPSCONTENT;

foreach ( $counts as $class => $count ):
$return .= <<<IPSCONTENT

			<tr>
				<td>
IPSCONTENT;

$val = "{$class::$title}_pl"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</td>
				<td class="i-text-align_end" style="padding-inline-start:1em">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT
</td>
			</tr>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</table>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function devicesAndIPAddresses( $member, $lastUsedIp, $devices ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='acpMemberView_devices ipsBox' data-ips-template="devicesAndIPAddresses">
	<h2 class='ipsBox__header'>
		<i class="fa-solid fa-laptop ipsBox__header-icon" aria-hidden="true"></i>
		
IPSCONTENT;

if ( \IPS\Settings::i()->ipsgeoip and \IPS\GeoLocation::enabled() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'devices_and_locations', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'devices_and_ips', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h2>
	<i-tabs class='ipsTabs ipsTabs--small ipsTabs--stretch' id='ipsTabs_devices' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_devices_content'>
		<div role='tablist'>
			<button type="button" class="ipsTabs__tab" id='ipsTabs_devices_location' role="tab" aria-controls="ipsTabs_devices_location_panel" aria-selected="true">
				
IPSCONTENT;

if ( \IPS\Settings::i()->ipsgeoip and \IPS\GeoLocation::enabled() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_ip_locations', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ip_addresses', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</button>
			<button type="button" id='ipsTabs_devices_devices' class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_devices_devices_panel" aria-selected="false">
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_devices', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</button>
		</div>
	</i-tabs>
	<div id='ipsTabs_devices_content'>
		<div id='ipsTabs_devices_location_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_devices_location">
			
IPSCONTENT;

if ( \IPS\Settings::i()->ipsgeoip and \IPS\GeoLocation::enabled() ):
$return .= <<<IPSCONTENT

				<div data-controller="core.admin.members.lazyLoadingProfileBlock" data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'lazyBlock', 'block' => 'IPS\core\extensions\core\MemberACPProfileBlocks\Locations' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					<div class='acpMemberView_map ipsLoading ipsLoading--tiny'></div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--border ipsList--label-value'>
				<li>
					<span class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'registration_ip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span class="ipsList__value">
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=ip&ip={$member->ip_address}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-link-color_inherit">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->ip_address, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->ip_address, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				</li>
				<li>
					<span class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'last_used_ip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span class='ipsList__value'>
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=ip&ip={$lastUsedIp}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-link-color_inherit">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastUsedIp, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastUsedIp, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				</li>
				<li>
					<span class="ipsList__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'timezone', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span class="ipsList__value">
						
IPSCONTENT;

$val = "timezone__$member->timezone"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</span>
				</li>
			</ul>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) ):
$return .= <<<IPSCONTENT

			<div class="ipsViewAll">
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=ip&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_ip_addresses', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div id='ipsTabs_devices_devices_panel' class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_devices_devices" hidden>
			<i-data>
				<ul class='ipsData ipsData--table ipsData--compact ipsData--devices'>
					
IPSCONTENT;

foreach ( $devices as $device ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=devices&do=device&key={$device->device_key}&member={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $device->userAgent()->platform, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
							<div class='i-basis_50'>
								
IPSCONTENT;

if ( $device->userAgent()->platform === 'Macintosh' ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/mac.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" class="ipsImage">
								
IPSCONTENT;

elseif ( $device->userAgent()->platform === 'Android' or $device->userAgent()->platform === 'Windows Phone' ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/android.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" class="ipsImage">
								
IPSCONTENT;

elseif ( $device->userAgent()->platform === 'iPad' ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/ipad.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" class="ipsImage">
								
IPSCONTENT;

elseif ( $device->userAgent()->platform === 'iPhone' ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/iphone.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" class="ipsImage">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "logos/devices/pc.png", "core", 'interface', false );
$return .= <<<IPSCONTENT
" class="ipsImage">
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
							<div class="ipsData__content">
								<div class='ipsData__main'>
									<div class="ipsData__title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $device->userAgent()->platform, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
									<small class="ipsData__desc">
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $device->last_seen )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'device_last_loggedin', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</small>
								</div>
							</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) ):
$return .= <<<IPSCONTENT

				<div class="ipsViewAll">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=devices&do=member&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_devices', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
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

	function downloadPersonalInfo( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<div class="i-font-size_1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_export_pi_explain', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	<div class="i-padding_3 i-text-align_center">
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=exportPersonalInfo&id={$member->member_id}&process=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary"><i class="fa-solid fa-download"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function groups( $member, $secondaryGroups ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='acpMemberView_groups ipsBox' data-ips-template="groups">
	<h2 class='ipsBox__header'>
		<i class="fa-solid fa-users ipsBox__header-icon" aria-hidden="true"></i>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'groups', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( !$member->isAdmin() or ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) and  \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin1' ) ) ) ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Groups&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'groups', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h2>
	<div class='ipsBox__content'>
		<i-data>
			<ul class="ipsData ipsData--table ipsData--acpProfileGroups">
				<li class="ipsData__item">
					<div class="ipsData__main">
						<h3 class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'primary_group', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class="ipsData__desc">
							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&advanced_search_submitted=1&members_member_group_id={$member->group['g_id']}&noColumn=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
								{$member->groupName}
							</a>
						</p>
					</div>
					
IPSCONTENT;

if ( $member->group['g_icon']  ):
$return .= <<<IPSCONTENT

						<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $member->group['g_icon'] )->url;
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
				
IPSCONTENT;

if ( $secondaryGroups ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<div class="ipsData__main">
							<h3 class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'secondary_groups', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<ul class="ipsData__desc">
								
IPSCONTENT;

foreach ( $secondaryGroups as $group ):
$return .= <<<IPSCONTENT

									<li>
										<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&advanced_search_submitted=1&members_member_group_id={$group->g_id}&noColumn=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-font-size_1 i-link-color_inherit">{$group->formattedName}</a>
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
</div>

IPSCONTENT;

		return $return;
}

	function header( $member, $validatingRow, $sparkline ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $validatingRow ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--general acpMemberView_message i-margin-bottom_3">
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_validating' ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsButton_split i-float_end'>
				<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'do', 'approve' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( !$validatingRow['user_verified'] ):
$return .= <<<IPSCONTENT
ipsButton--small
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsButton--positive" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

if ( !$validatingRow['user_verified'] ):
$return .= <<<IPSCONTENT

					<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'do', 'resendEmail' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--inherit">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'resend_validation_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'ban', 'permban' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( !$validatingRow['user_verified'] ):
$return .= <<<IPSCONTENT
ipsButton--small
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsButton--negative" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ban', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<h4 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reg_admin_validation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<p>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->validatingDescription(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>		
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $time = $member->isBanned() ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--error acpMemberView_message i-margin-bottom_3">
		
IPSCONTENT;

if ( $member->temp_ban and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_ban' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_ban_admin' ) or !$member->isAdmin() ) AND $member->member_id != \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'ban' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'adjust_ban', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-float_end">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'adjust_ban', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<h4 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'banned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		
IPSCONTENT;

if ( $time === TRUE ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $member->temp_ban ):
$return .= <<<IPSCONTENT

				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_banned_perm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_banned_group', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p>
IPSCONTENT;

$sprintf = array($time); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_banned_time', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

elseif ( $member->members_bitoptions['bw_is_spammer'] ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--error acpMemberView_message i-margin-bottom_3">
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and (\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'spam', 'status' => 0 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-float_end" data-confirm data-confirmSubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<h4 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_flagged_as_spammer_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_flagged_as_spammer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>		
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->ipb_bruteforce_attempts and $member->failed_login_count >= \IPS\Settings::i()->ipb_bruteforce_attempts ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--warning acpMemberView_message i-margin-bottom_3">
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'unlock' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-float_end">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unlock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
		<h4 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_locked_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_locked_logins', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>		
	</div>

IPSCONTENT;

elseif ( \IPS\Settings::i()->security_questions_tries and $member->failed_mfa_attempts >= \IPS\Settings::i()->security_questions_tries ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--warning acpMemberView_message i-margin-bottom_3">
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'unlock' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-float_end">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unlock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>

		<h4 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_locked_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'account_locked_2fa', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ! $member->name or ! $member->email ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--error acpMemberView_message i-margin-bottom_3">
		
IPSCONTENT;

if ( $member->members_bitoptions['created_externally'] or !empty( $member->last_visit ) ):
$return .= <<<IPSCONTENT

			<h4 class='ipsMessage__title'>
				
IPSCONTENT;

if ( ! $member->name and ! $member->email ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reserved_external_both', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( ! $member->name ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reserved_external_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( ! $member->email ):
$return .= <<<IPSCONTENT
	
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reserved_external_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</h4>
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_name_missing_as_reserved_external', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<h4 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reserved_pending_deletion', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_name_missing_as_reserved_tt', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $member->members_bitoptions['is_support_account'] ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--warning acpMemberView_message i-margin-bottom_3">
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete_admin' ) ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'delete' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm class="ipsButton ipsButton--inherit i-float_end">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<h4 class='ipsMessage__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acpmemberprofile_support_account', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acpmemberprofile_support_account_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class='acpMemberView_stats ipsBox i-padding_1'>
	<div class='ipsSpanGrid'>
		<div class='ipsSpanGrid__
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT
6
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
9
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
			{$sparkline}
		</div>
		<div class='ipsSpanGrid__3'>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) ):
$return .= <<<IPSCONTENT

				<a href='#memberPostsBlock_menu' id='memberPostsBlock' data-ipsMenu class='acpMemberView_countStat acpMemberView_contentCount' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
					<h3 class='ipsMinorTitle ipsTruncate ipsTruncate_line'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_member_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<p class='i-font-size_2 acpMemberView_countStatStat ipsTruncate ipsTruncate_line'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->member_posts );
$return .= <<<IPSCONTENT
</p>
				</a>
				<div id='memberPostsBlock_menu' class='ipsMenu ipsMenu_narrow ipsHide'>
					<ul>
						<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Header&id={$member->member_id}&type=content", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_member_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_manually', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=recountContent&id={$member->member_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmType="verify" data-confirmButtons='
IPSCONTENT;

$return .= json_encode( array( 'yes' => \IPS\Member::loggedIn()->language()->addToStack('yes'), 'no' => \IPS\Member::loggedIn()->language()->addToStack('recount_all'), 'cancel' => \IPS\Member::loggedIn()->language()->addToStack('cancel') ) );
$return .= <<<IPSCONTENT
' data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_content_items_recount', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recount', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_delete' ) ):
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=deleteContent&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_delete_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_delete_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span id='memberPostsBlock' data-ipsMenu class='acpMemberView_countStat acpMemberView_contentCount' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip>
					<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_member_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<p class='i-font-size_2 acpMemberView_countStatStat'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->member_posts );
$return .= <<<IPSCONTENT
</p>
				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

			<div class='ipsSpanGrid__3'>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) ):
$return .= <<<IPSCONTENT

					<a href='#memberRepBlock_menu' id="memberRepBlock" data-ipsMenu class='acpMemberView_countStat acpMemberView_repCount'>
						<h3 class='ipsMinorTitle ipsTruncate ipsTruncate_line'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class='i-font-size_2 acpMemberView_countStatStat ipsTruncate ipsTruncate_line'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->pp_reputation_points );
$return .= <<<IPSCONTENT
</p>
					</a>
					<div id='memberRepBlock_menu' class='ipsMenu ipsMenu_narrow ipsHide'>
						<ul>
						<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Header&id={$member->member_id}&type=reputation", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'set_manually', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=recountReputation&id={$member->member_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmType="verify" data-confirmButtons='
IPSCONTENT;

$return .= json_encode( array( 'yes' => \IPS\Member::loggedIn()->language()->addToStack('yes'), 'no' => \IPS\Member::loggedIn()->language()->addToStack('recount_all'), 'cancel' => \IPS\Member::loggedIn()->language()->addToStack('cancel') ) );
$return .= <<<IPSCONTENT
' data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reputation_recount', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recount', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=removeReputation&type=given&id={$member->member_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_remove_given', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=removeReputation&type=received&id={$member->member_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_remove_received', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						</ul>
					</div>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<span id="memberRepBlock" data-ipsMenu class='acpMemberView_countStat acpMemberView_repCount'>
						<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class='i-font-size_2 acpMemberView_countStatStat'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->pp_reputation_points );
$return .= <<<IPSCONTENT
</p>
					</span>
				
IPSCONTENT;

endif;
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

	function history( $member, $history, $historyFilters ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elMemberHistory' class="ipsBox" data-controller="core.admin.members.history">
	<a href='#memberHistoryFilters_menu' id='memberHistoryFilters' class="ipsTitle ipsTitle--h4 i-padding_3 i-flex i-align-items_center i-justify-content_space-between" data-ipsMenu>
		<span data-role="historyTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all_recent_account_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		<i class="fa-solid fa-caret-down"></i>
	</a>
	<div id='memberHistoryFilters_menu' class='ipsMenu ipsHide'>
		<ul>
			
IPSCONTENT;

$logApp = NULL;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $historyFilters as $filter ):
$return .= <<<IPSCONTENT

			    
IPSCONTENT;

if ( isset($history->advancedSearch['log_type'][1]['options'][ $filter['log_type'] ]) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $logApp != $filter['log_app'] ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_title">
IPSCONTENT;

$val = "memberlog_app_{$filter['log_app']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

$logApp = $filter['log_app'];
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li class="ipsMenu_item" data-ipsMenuValue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $filter['log_app'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $filter['log_type'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=history&id={$member->member_id}&advanced_search_submitted=1&log_type[]={$filter['log_type']}&_fromFilter=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$history->advancedSearch['log_type'][1]['options'][ $filter['log_type'] ]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<li class="ipsMenu_sep"><hr></li>
			<li class="ipsMenu_item" data-ipsMenuValue=""><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=history&id={$member->member_id}&_fromFilter=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all_recent_account_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		</ul>
	</div>
	<div class="" data-role="historyDisplay">
		{$history}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function historyRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$currentTimestamp = null;
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $currentTimestamp != $row['log_date']->format('n') . '|' . $row['log_date']->format('Y') ):
$return .= <<<IPSCONTENT

		<li class='cMemberHistory_date'>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['log_date']->strFormat('%B'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['log_date']->strFormat('%Y'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</li>
		
IPSCONTENT;

$currentTimestamp = $row['log_date']->format('n') . '|' . $row['log_date']->format('Y');
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<li class=''>
		<div class='cMemberHistory_info'>
			<div class="i-color_hard i-font-weight_500 i-link-text-decoration_underline">{$row['log_data']}</div>
			<span class='i-color_soft i-link-color_inherit i-flex i-justify-content_space-between'>
				<span>{$row['log_date']->html()}</span>
				
IPSCONTENT;

if ( $row['log_ip_address'] and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) ):
$return .= <<<IPSCONTENT

					<span>{$row['log_ip_address']}</span>
				
IPSCONTENT;

endif;
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

	function historyTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<ol class='cMemberHistory 
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="tableRows">
		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	</ol>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'customer_no_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

	<div class="ipsViewAll">
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_all_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function lazyLoad( $member, $block ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="core.admin.members.lazyLoadingProfileBlock" data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'lazyBlock', 'block' => $block ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class="i-margin-bottom_1 i-text-align_center">
		<img src="
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "tiny_loading.gif", "core", 'front', false );
$return .= <<<IPSCONTENT
">
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function locations( $member, $mapMarkers ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ipsMap data-ipsMap-apiKey="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Settings::i()->google_maps_api_key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMap-markers="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $mapMarkers ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsMap ipsMap--small"></div>
IPSCONTENT;

		return $return;
}

	function loginMethods( $member, $loginMethods ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $loginMethods ) ):
$return .= <<<IPSCONTENT

	<hr class='ipsHr'>
	<h3 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'active_account_integrations', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>

	<i-data>
		<ul class='ipsData ipsData--table ipsData--login-methods acpMemberView_integrations'>
			
IPSCONTENT;

foreach ( $loginMethods as $id => $details ):
$return .= <<<IPSCONTENT

				<li class='ipsData__item'>
					
IPSCONTENT;

if ( $details['link'] ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="ipsData__image">
							
IPSCONTENT;

if ( isset( $details['icon'] ) ):
$return .= <<<IPSCONTENT

								<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsUserPhoto ipsUserPhoto--tiny" alt="" loading="lazy">
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "default_photo.png", "core", 'global', false );
$return .= <<<IPSCONTENT
' class="ipsUserPhoto ipsUserPhoto--tiny" alt="" loading="lazy">
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class="ipsData__image">
							
IPSCONTENT;

if ( isset( $details['icon'] ) ):
$return .= <<<IPSCONTENT

								<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsUserPhoto ipsUserPhoto--tiny" alt="" loading="lazy">
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "default_photo.png", "core", 'global', false );
$return .= <<<IPSCONTENT
' class="ipsUserPhoto ipsUserPhoto--tiny" alt="" loading="lazy">
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div class='ipsData__main'>
						
IPSCONTENT;

if ( isset( $details['edit'] ) and ( $details['edit'] or $details['delete'] ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) ):
$return .= <<<IPSCONTENT

							<ul class="ipsControlStrip">
								
IPSCONTENT;

if ( $details['edit'] ):
$return .= <<<IPSCONTENT

									<li class="ipsControlStrip_button">
										<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=loginEdit&id={$member->member_id}&method={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profilesync_configure', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="ipsControlStrip_icon fa-solid fa-pencil"></i></a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $details['delete'] ):
$return .= <<<IPSCONTENT

									<li class="ipsControlStrip_button">
										<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=loginDelete&id={$member->member_id}&method={$id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sign_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="ipsControlStrip_icon fa-solid fa-xmark-circle"></i></a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<p class='acpMemberView_integrations_text'>
							
IPSCONTENT;

if ( $details['link'] ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener" class="i-link-color_inherit">
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<br>
							<span class='i-font-size_1 i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['blurb'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

if ( $details['link'] ):
$return .= <<<IPSCONTENT

								</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset($details['forceSyncErrors']) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $details['forceSyncErrors'] as $type => $error ):
$return .= <<<IPSCONTENT

								<p class="i-font-size_-1 i-color_warning">
IPSCONTENT;

$val = "profilesync_{$type}_admin_error"; $sprintf = array($error); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</p>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function mainTemplate( $member, $extensions, $activeTab, $activeTabContent, $history ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='acpMemberView'>
	
	
IPSCONTENT;

if ( \count( $extensions ) > 1 ):
$return .= <<<IPSCONTENT

		<i-tabs class='ipsTabs acpMemberView_tabBar' id='ipsTabs_member' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_member_content' data-ipsTabBar-panelClass="acpMemberView_tabBarPanel">
			<div role='tablist'>
				
IPSCONTENT;

foreach ( $extensions as $key => $classname ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( 'tab', $key ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='ipsTabs_member_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-controls="ipsTabs_member_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class="ipsTabs__tab" role="tab" aria-selected="
IPSCONTENT;

if ( $key == $activeTab ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $classname::title(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

		</i-tabs>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

if ( $history ):
$return .= <<<IPSCONTENT

		<div class="acpMemberView_layoutWrap">
			<div class="acpMemberView_layoutMain">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	<div id='ipsTabs_member_content'>
		<div id="ipsTabs_member_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class="ipsTabs__panel acpMemberView_tabBarPanel" role="tabpanel" aria-labelledby="ipsTabs_member_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			{$activeTabContent}
		</div>
	</div>
	
	
	
IPSCONTENT;

if ( $history ):
$return .= <<<IPSCONTENT

			</div>
			<div class="acpMemberView_layoutHistory">
				{$history}
			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function mfa( $member, $configuredHandlers, $hasSecurityQuestions, $showEditButton ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='acpMemberView_2fa ipsBox'>
	<h2 class='ipsBox__header' data-ips-template="mfa">
		<i class="fa-solid fa-lock ipsBox__header-icon" aria-hidden="true"></i>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member__core_SecurityAnswers', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $showEditButton and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( !$member->isAdmin() or \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) ) ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\MFA&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member__core_SecurityAnswers', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h2>
	<div class='i-padding_3'>
		
IPSCONTENT;

if ( $member->members_bitoptions['security_questions_opt_out'] and \IPS\Settings::i()->mfa_required_groups != '*' and !$member->inGroup( explode( ',', \IPS\Settings::i()->mfa_required_groups ) ) ):
$return .= <<<IPSCONTENT

			<div class="i-color_negative ipsType_sectionHead">
				<i class="fa-solid fa-xmark-circle"></i> &nbsp; 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_opted_out', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $configuredHandlers as $key => $handler ):
$return .= <<<IPSCONTENT

				<div class="i-color_positive i-margin-top_1 ipsType_sectionHead">
					<i class="fa-solid fa-check-circle"></i> &nbsp; 
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( 'mfa_' . $key . '_title' )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mfa_method_enabled', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !\count( $configuredHandlers ) and !$hasSecurityQuestions ):
$return .= <<<IPSCONTENT

				<div class="i-color_soft">
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_mfa_methods_enabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Settings::i()->security_questions_enabled and $hasSecurityQuestions ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $configuredHandlers ) ):
$return .= <<<IPSCONTENT

					<hr class='ipsHr'>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$answers = $member->securityAnswers();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $answers ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $answers as $questionId => $answer ):
$return .= <<<IPSCONTENT

						<div class="i-margin-bottom_1">
							<h2 class='ipsMinorTitle'>
IPSCONTENT;

$val = "security_question_{$questionId}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
							<p class='i-font-size_1'>
								
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Text\Encrypt::fromTag( $answer )->decrypt(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</p>
						</div>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

		return $return;
}

	function notificationTypes( $member, $categories ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox i-margin-bottom_1' data-ips-template="notificationTypes">
	<h2 class='ipsBox__header'>
		<i class="fa-solid fa-bell ipsBox__header-icon" aria-hidden="true"></i>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notification_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</h2>
	<i-data>
		<ul class="ipsData ipsData--grid ipsData--notification-options cMemberNotifications">
			
IPSCONTENT;

foreach ( $categories as $k => $enabled ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Notifications&id={$member->member_id}&type={$k}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$val = "notifications__$k"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><span>
IPSCONTENT;

$val = "notifications__$k"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					<div class="ipsData__main">
						<h3 class="ipsData__title">
IPSCONTENT;

$val = "notifications__$k"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<div class="ipsData__meta">
							
IPSCONTENT;

if ( \count( $enabled ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->formatList( array_map( function( $option ) { return $option['title']; }, $enabled ), \IPS\Member::loggedIn()->language()->get('member_notifications_list_format') ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'notifications_off', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
</div>
IPSCONTENT;

		return $return;
}

	function oauth( $member, $apps, $onlyApp=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='acpMemberView_2fa ipsBox' data-ips-template="oauth">
	<h2 class='ipsBox__header'>
		<i class="fa-solid fa-arrow-right-arrow-left ipsBox__header-icon" aria-hidden="true"></i>
		
IPSCONTENT;

if ( $onlyApp ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $onlyApp->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_member_authorized_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=api&do=tokens&member_id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_view_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</h2>
	
IPSCONTENT;

if ( \count( $apps ) ):
$return .= <<<IPSCONTENT

		<i-data>
			<ul class="ipsData ipsData--table ipsData--oauth">
				
IPSCONTENT;

foreach ( $apps as $app ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<div class='ipsData__main'>
									
IPSCONTENT;

if ( $app['data']['status'] == 'active' ):
$return .= <<<IPSCONTENT

							<ul class="ipsControlStrip">
								<li class="ipsControlStrip_button">
									<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=applications&controller=oauth&do=revokeToken&client_id={$app['data']['client_id']}&member_id={$app['data']['member_id']}&token={$app['data']['access_token']}&r=p" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_app_revoke_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'oauth_app_revoke', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="ipsControlStrip_icon fa-solid fa-xmark-circle"></i></a>
								</li>
							</ul>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<div>
								{$app['title']}
								<div class='i-font-size_1 i-color_soft'>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "api", \IPS\Request::i()->app )->oauthStatus( $app['data'], $app['use_refresh_tokens'] );
$return .= <<<IPSCONTENT

								</div>
							</div>
						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class="i-padding_3 i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_oauth_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function profileData( $member, $fields ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3 i-grid i-gap_4'>
	
IPSCONTENT;

if ( $member->rank['title'] || $member->rank['image'] || \IPS\Settings::i()->profile_birthday_type != 'none' || \IPS\Settings::i()->signatures_enabled ):
$return .= <<<IPSCONTENT

		<div>
			<h2 class="ipsMinorTitle">
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h2>
			<i-data>
				<ul class="ipsData ipsData--table ipsData--compact ipsData--profile-data">
					
IPSCONTENT;

if ( \IPS\Settings::i()->profile_birthday_type != 'none' ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<span class='i-basis_100'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'bday', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
							<span class=''>
								
IPSCONTENT;

if ( $member->birthday ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->birthday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</span>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Settings::i()->signatures_enabled ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<span class='i-basis_100'><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'signature', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
							<span class='ipsRichText'>
								
IPSCONTENT;

if ( $member->signature ):
$return .= <<<IPSCONTENT

									{$member->signature}
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
								
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

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $fields as $groupKey => $values ):
$return .= <<<IPSCONTENT

		<div>
			<h2 class="ipsMinorTitle">
IPSCONTENT;

$val = "{$groupKey}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<i-data>
				<ul class="ipsData ipsData--table ipsData--compact ipsData--profile-custom">
					
IPSCONTENT;

foreach ( $values as $k => $v ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<span class='i-basis_100'><strong>
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></span>
							<div class=''>
								
IPSCONTENT;

if ( (string) $v === '' ):
$return .= <<<IPSCONTENT

									<em class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_value', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<div>{$v}</div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</i-data>
		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function quotas( $member, $messengerCount, $messengerPercent, $attachmentStorage, $attachmentPercent, $viewAttachmentsLink ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $member->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging', 'front' ) ) or $attachmentStorage !== NULL ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging', 'front' ) ) ):
$return .= <<<IPSCONTENT

	<div class='ipsAcpProfile__half acpMemberView_quota ipsBox' data-ips-template="quotas">
		<h2 class='ipsBox__header'>
			<i class="fa-solid fa-envelope ipsBox__header-icon" aria-hidden="true"></i>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_quota_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $member->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging', 'front' ) ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( !$member->isAdmin() or \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $messengerCount === NULL ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Quotas&id={$member->member_id}&enable=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary" data-confirm>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'admin_enable_messenger', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Quotas&id={$member->member_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary" data-confirm data-confirmType="verify" data-confirmIcon="question" data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'admin_disable_messenger', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'admin_disable_messenger_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmButtons='
IPSCONTENT;

$return .= json_encode( array( 'yes' => \IPS\Member::loggedIn()->language()->addToStack('no'), 'no' => \IPS\Member::loggedIn()->language()->addToStack('yes'), 'cancel' => \IPS\Member::loggedIn()->language()->addToStack('cancel') ) );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'admin_disable_messenger', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h2>
		<div class='i-padding_3'>
			
IPSCONTENT;

if ( $messengerCount !== NULL ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $messengerPercent !== NULL ):
$return .= <<<IPSCONTENT

					<meter class='ipsMeter i-margin-bottom_1' max='100' high='90' value='
IPSCONTENT;

if ( $messengerPercent > 100 ):
$return .= <<<IPSCONTENT
100
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $messengerPercent, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></meter>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
				<div class="i-flex i-margin-top_1">
					<div class="i-flex_11">
						<span class='acpMemberView_quotaNumber i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $messengerCount );
$return .= <<<IPSCONTENT
</span><br>
						
IPSCONTENT;

if ( $member->group['g_max_messages'] > 0 ):
$return .= <<<IPSCONTENT

							<span class='i-font-size_1 i-color_soft'>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->formatNumber( $member->group['g_max_messages'] )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'quota_allowance', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span class='i-font-size_1 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'quota_allowance_unlimited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					
IPSCONTENT;

if ( $messengerPercent !== NULL ):
$return .= <<<IPSCONTENT

						<span class='acpMemberView_percentage i-opacity_4 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $messengerPercent );
$return .= <<<IPSCONTENT
%</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class="i-font-size_1 i-color_soft">
					
IPSCONTENT;

if ( $member->members_disable_pm == 1 ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_quota_disabled_self', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( $member->members_disable_pm == 2 ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_quota_disabled_admin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_quota_disabled_group', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsAcpProfile__half acpMemberView_quota ipsBox' data-ips-template="quotas">
		<h2 class='ipsBox__header'>
			<i class="fa-solid fa-paperclip ipsBox__header-icon" aria-hidden="true"></i>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_attachment_quota', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $viewAttachmentsLink ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $viewAttachmentsLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_attachments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h2>
		<div class='i-padding_3'>
			
IPSCONTENT;

if ( $attachmentStorage !== NULL ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $attachmentPercent !== NULL ):
$return .= <<<IPSCONTENT

					<meter class='ipsMeter i-margin-bottom_1' max='100' high='90' value='
IPSCONTENT;

if ( $attachmentPercent > 100 ):
$return .= <<<IPSCONTENT
100
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attachmentPercent, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></meter>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class="i-flex i-margin-top_1">
					<div class="i-flex_11">
						<span class='acpMemberView_quotaNumber i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Output\Plugin\Filesize::humanReadableFilesize( $attachmentStorage );
$return .= <<<IPSCONTENT
</span><br>
						
IPSCONTENT;

if ( $member->group['g_attach_max'] > 0 ):
$return .= <<<IPSCONTENT

							<span class='i-font-size_1 i-color_soft'>
IPSCONTENT;

$sprintf = array(\IPS\Output\Plugin\Filesize::humanReadableFilesize( $member->group['g_attach_max'] * 1024 )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'quota_allowance', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span class='i-font-size_1 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'quota_allowance_unlimited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					
IPSCONTENT;

if ( $attachmentPercent !== NULL ):
$return .= <<<IPSCONTENT

						<span class='acpMemberView_percentage i-opacity_4 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $attachmentPercent );
$return .= <<<IPSCONTENT
%</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class="i-font-size_1 i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'quota_allowance_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function rank( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->achievements_enabled ):
$return .= <<<IPSCONTENT

    <div class='ipsAcpProfile__half acpMemberView_quota ipsBox' data-ips-template="rank">
        <h2 class='ipsBox__header'>
            <i class="fa-solid fa-chart-line ipsBox__header-icon" aria-hidden="true"></i>
            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_profile_ranks_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) ):
$return .= <<<IPSCONTENT

                <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Points&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_profile_points_manage', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </h2>
        
IPSCONTENT;

if ( $member->rank() ):
$return .= <<<IPSCONTENT

        <div class='i-padding_3'>
            <div class="i-flex i-align-items_center i-margin-bottom_1">
                <span class='acpMemberView_quotaNumber i-flex_11 i-font-size_1'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->rank()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><br>
                <span class='i-font-size_1 i-color_soft'>
IPSCONTENT;

$sprintf = array($member->achievements_points); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_profile_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
            </div>
            
IPSCONTENT;

if ( $nextRank = $member->nextRank() ):
$return .= <<<IPSCONTENT

                <meter class='ipsMeter i-margin-bottom_1' max='100' high='90' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $member->achievements_points / $nextRank->points * 100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></meter>
                <span class='i-font-size_1 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_rank_points_for_next', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $nextRank->points - $member->achievements_points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>
    <div class='ipsAcpProfile__half acpMemberView_quota ipsBox'>
        <h2 class='ipsBox__header'>
            <i class="fa-solid fa-certificate ipsBox__header-icon" aria-hidden="true"></i>
            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_profile_badges_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) ):
$return .= <<<IPSCONTENT

                <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString('do', 'badges'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_profile_manage_badges', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </h2>
        <div class='i-padding_3 i-flex i-flex-wrap_wrap i-gap_2'>
            
IPSCONTENT;

foreach ( $member->recentBadges(20) as $badge ):
$return .= <<<IPSCONTENT

                <span class="i-flex_00 i-basis_40">{$badge->html('ipsDimension:5')}</span>
            
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

	function tabbedBlock( $member, $block, $title, $tabNames, $activeId, $defaultContent, $editLink ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox' data-ips-template="tabbedBlock">
	
IPSCONTENT;

if ( $title !== NULL ):
$return .= <<<IPSCONTENT

		<h2 class='ipsBox__header'>
			<i class="fa-solid fa-user ipsBox__header-icon" aria-hidden="true"></i>
			
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $editLink and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( !$member->isAdmin() or \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) ) ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $tabNames ) > 1 ):
$return .= <<<IPSCONTENT

		<i-tabs class='ipsTabs ipsTabs--small' id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $block ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $block ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
data-ipsTabBar-updateURL='false'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<div role='tablist'>
				
IPSCONTENT;

foreach ( $tabNames as $i => $name ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->acpUrl()->setQueryString( array( 'do' => 'view', 'blockKey' => $block, 'block[' . $block . ']' => $i ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $block ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsTabs__tab" 
IPSCONTENT;

if ( \is_array( $name ) ):
$return .= <<<IPSCONTENT
title='
IPSCONTENT;

$val = "{$name[1]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
title='
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 role="tab" aria-controls='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $block ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' aria-selected="
IPSCONTENT;

if ( $i == $activeId ):
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

if ( \is_array( $name ) ):
$return .= <<<IPSCONTENT

							<i class="fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name[0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></i>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

		</i-tabs>
		<section id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $block ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content'>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $tabNames as $i => $name ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $i == $activeId ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $block ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' role="tabpanel" class="ipsTabs__panel" aria-labelledby="ipsTabs_
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( $block ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $i != $activeId ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					{$defaultContent}
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $tabNames ) > 1 ):
$return .= <<<IPSCONTENT

		</section>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function tabTemplate( $leftColumnBlocks, $mainColumnBlocks ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='ipsAcpProfile'>
    
IPSCONTENT;

if ( \count($leftColumnBlocks)  ):
$return .= <<<IPSCONTENT

	<aside class='ipsAcpProfile__aside'>
		
IPSCONTENT;

foreach ( $leftColumnBlocks as $block ):
$return .= <<<IPSCONTENT

			{$block->output()}
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</aside>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsAcpProfile__main'>
		
IPSCONTENT;

foreach ( $mainColumnBlocks as $block ):
$return .= <<<IPSCONTENT

			{$block->output()}
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
</section>
IPSCONTENT;

		return $return;
}

	function warnings( $member, $restrictions, $flagMessage ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsAcpProfile__half acpMemberView_contentStats ipsBox'>
	<h2 class='ipsBox__header'>
		<i class="fa-solid fa-triangle-exclamation ipsBox__header-icon" aria-hidden="true"></i>
		
IPSCONTENT;

if ( \IPS\Settings::i()->warn_on ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warnings_and_restrictions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restrictions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( !$member->isAdmin() or \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) ) ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\core\\extensions\\core\\MemberACPProfileBlocks\\Warnings&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsBox__header-secondary" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warnings_and_restrictions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h2>
	<div class="i-flex i-padding_2">
		<div class="i-flex_11">
			
IPSCONTENT;

if ( \IPS\Settings::i()->warn_on and !$member->inGroup( explode( ',', \IPS\Settings::i()->warn_protected ) ) ):
$return .= <<<IPSCONTENT

				<p class="i-font-size_2 i-font-weight_500 
IPSCONTENT;

if ( $member->warn_level > 0 ):
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-color_hard
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$pluralize = array( $member->warn_level ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_warn_level', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !\count( $restrictions ) ):
$return .= <<<IPSCONTENT

				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_restrictions_applied', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<ul class="ipsList ipsList--inline i-color_negative">
					
IPSCONTENT;

foreach ( $restrictions as $restriction ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

$val = "{$restriction}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $member->member_id != \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<ul class="ipsButtons">
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

if ( $member->members_bitoptions['bw_is_spammer'] ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=spam&id={$member->member_id}&status=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=spam&id={$member->member_id}&status=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm 
IPSCONTENT;

if ( $flagMessage ):
$return .= <<<IPSCONTENT
data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_flag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubmessage="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $flagMessage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsButton ipsButton--inherit ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_flag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_ban' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_ban_admin' ) or !$member->isAdmin() ) and $member->member_id != \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

					<li>
						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=ban&id={$member->member_id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

if ( $member->temp_ban ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'adjust_ban', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ban', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--small">
							
IPSCONTENT;

if ( $member->temp_ban ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'adjust_ban', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ban', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('mod_see_warn') and $warnings = $member->warnings( NULL ) and \count( $warnings ) ):
$return .= <<<IPSCONTENT

		<i-data class="i-border-top_2">
			<ol class="ipsData ipsData--table ipsData--warnings">
				
IPSCONTENT;

foreach ( $warnings as $warning ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item">
						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controllers=members&do=viewWarning&id={$warning->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' class="ipsLinkPanel" tabindex="-1" aria-hidden="true"><span>
IPSCONTENT;

$val = "core_warn_reason_{$warning->reason}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						<div class="ipsData__icon">
							<span class="ipsWarningPoints ipsWarningPoints--small">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						</div>
						<div class='ipsData__main'>
							<h4 class="ipsData__title">
								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controllers=members&do=viewWarning&id={$warning->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow'>
									
IPSCONTENT;

$val = "core_warn_reason_{$warning->reason}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( \IPS\Settings::i()->warnings_acknowledge ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $warning->acknowledged ):
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

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</a>
							</h4>
							<p class='ipsData__meta'>
								
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $warning->moderator )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

$val = ( $warning->date instanceof \IPS\DateTime ) ? $warning->date : \IPS\DateTime::ts( $warning->date );$return .= $val->html();
$return .= <<<IPSCONTENT

							</p>
						</div>
						
IPSCONTENT;

if ( $warning->canDelete() ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controllers=members&do=warningRevoke&id={$warning->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_revoke_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmType="verify" data-confirmButtons='
IPSCONTENT;

$return .= json_encode( array( 'yes' => \IPS\Member::loggedIn()->language()->addToStack('warning_revoke_undo'), 'no' => \IPS\Member::loggedIn()->language()->addToStack('delete'), 'cancel' => \IPS\Member::loggedIn()->language()->addToStack('cancel') ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--text'><i class="fa-solid fa-arrow-rotate-left"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ol>
		</i-data>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function warningView( $warning ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3" id="warnhovercard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">	
	<h2 class="ipsTitle ipsTitle--h4">
		
IPSCONTENT;

if ( $warning->canViewDetails() ):
$return .= <<<IPSCONTENT

			<span class='ipsWarningPoints ipsWarningPoints--small i-margin-end_icon'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			
IPSCONTENT;

$val = "core_warn_reason_{$warning->reason}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h2>
	
IPSCONTENT;

if ( \IPS\Settings::i()->warnings_acknowledge OR \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

		<p class=''>
			
IPSCONTENT;

if ( $warning->acknowledged ):
$return .= <<<IPSCONTENT

				<strong class='i-color_positive'><i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_acknowledged', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $warning->canAcknowledge() ):
$return .= <<<IPSCONTENT

					<div class='i-background_2 i-padding_3 i-text-align_center'>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url('acknowledge')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary ipsButton--wide"><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acknowledge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						<p class='i-color_soft i-font-size_-1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acknowledge_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					</div>
				
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

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $content = $warning->contentObject() and $content->canView() ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->url()->setQueryString( '_warn', $warning->id ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_soft' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_go_to_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> &nbsp;&nbsp;
IPSCONTENT;

if ( $content instanceof \IPS\Content\Comment ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $content->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $warning->canViewDetails() ):
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $warning->moderator ), 'tiny' );
$return .= <<<IPSCONTENT

			<div class="ipsPhotoPanel__text">
				<p class="ipsPhotoPanel__primary i-color_hard">
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $warning->moderator )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
				<p class='ipsPhotoPanel__secondary'>
IPSCONTENT;

$val = ( $warning->date instanceof \IPS\DateTime ) ? $warning->date : \IPS\DateTime::ts( $warning->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
			</div>
			
IPSCONTENT;

if ( $warning->canDelete() ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controllers=members&do=warningRevoke&id={$warning->id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_revoke_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmType="verify" data-confirmButtons='
IPSCONTENT;

$return .= json_encode( array( 'yes' => \IPS\Member::loggedIn()->language()->addToStack('warning_revoke_undo'), 'no' => \IPS\Member::loggedIn()->language()->addToStack('delete'), 'cancel' => \IPS\Member::loggedIn()->language()->addToStack('cancel') ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke_this_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--small ipsButton--secondary'><i class="fa-solid fa-arrow-rotate-left"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

if ( $warning->canViewDetails() or $warning->mq or $warning->rpa or $warning->suspend ):
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		<h3 class='ipsTitle ipsTitle--h4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_punishment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<ul class='ipsList_bullets'>
			
IPSCONTENT;

if ( $warning->canViewDetails() ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

if ( $warning->expire_date ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $warning->expire_date < time() ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $warning->expire_date == -1 ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_action_points_never_expire', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $warning->expire_date )); $pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_action_points_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $warning->expire_date )); $pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_action_points_expire', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_action_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
			
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $warning->mq ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_modq', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 - 
					
IPSCONTENT;

if ( $warning->mq == -1 ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'indefinitely', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

elseif ( $mq = $warning->mq_interval ):
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::formatInterval( $mq, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $warning->rpa ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_nopost', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 - 
					
IPSCONTENT;

if ( $warning->rpa == -1 ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'indefinitely', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

elseif ( $rpa = $warning->rpa_interval ):
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::formatInterval( $rpa, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $warning->suspend ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_banned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 - 
					
IPSCONTENT;

if ( $warning->suspend == -1 ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'indefinitely', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

elseif ( $suspend = $warning->suspend_interval ):
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::formatInterval( $suspend, 2 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $warning->note_member ):
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		<h3 class='ipsTitle ipsTitle--h4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_member_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<div class='ipsRichText'>
			{$warning->note_member}
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $warning->note_mods and \IPS\Member::loggedIn()->modPermission('mod_see_warn') ):
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		<h3 class='ipsTitle ipsTitle--h4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_mod_note', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<div class='ipsRichText'>
			{$warning->note_mods}
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}}