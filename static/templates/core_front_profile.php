<?php
namespace IPS\Theme;
class class_core_front_profile extends \IPS\Theme\Template
{	function allFollowers( $member, $followers, $followersCount ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	<h3 class='ipsMinorTitle'>
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_followers', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h3>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $followersCount > 50 ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$url = \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=followers&id={$member->member_id}", 'front', 'profile_followers', $member->members_seo_name );
$return .= <<<IPSCONTENT

	<div class="ipsButtonBar">
		<div class="ipsButtonBar__pagination" data-role="tablePagination">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $url, ceil( $followersCount / 50 ), (int) \IPS\Request::i()->page ?: 1, 50 );
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<i-data>
	<ul class='ipsData ipsData--table ipsData--all-followers'>
		
IPSCONTENT;

if ( $followers !== NULL and \count( $followers ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $followers AS $follower ):
$return .= <<<IPSCONTENT

				<li class='ipsData__item 
IPSCONTENT;

if ( $follower['follow_is_anon'] ):
$return .= <<<IPSCONTENT
i-opacity_4
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
					<div class='ipsData__icon'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load($follower['follow_member_id']), 'fluid', NULL, '', FALSE );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsData__main'>
						<h4 class='ipsData__title'>
IPSCONTENT;

$return .= \IPS\Member::load($follower['follow_member_id'])->link( NULL, FALSE );
$return .= <<<IPSCONTENT
</h4> 
IPSCONTENT;

if ( $follower['follow_is_anon'] ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'anon_follower', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<br>
						
IPSCONTENT;

$return .= \IPS\Member\Group::load( \IPS\Member::load($follower['follow_member_id'])->member_group_id )->formattedName;
$return .= <<<IPSCONTENT

					</div>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id != $follower['follow_member_id'] and ( !\IPS\Member::load($follower['follow_member_id'])->members_bitoptions['pp_setting_moderate_followers'] or \IPS\Member::loggedIn()->following( 'core', 'member', $follower['follow_member_id'] ) ) ):
$return .= <<<IPSCONTENT

						<div>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->memberFollow( 'core', 'member', $follower['follow_member_id'], \IPS\Member::load( $follower['follow_member_id'] )->followersCount(), TRUE );
$return .= <<<IPSCONTENT
</div>
					
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
</i-data>

IPSCONTENT;

if ( $followersCount > 50 ):
$return .= <<<IPSCONTENT

	<div class="ipsButtonBar">
		<div class="ipsButtonBar__pagination" data-role="tablePagination">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $url, ceil( $followersCount / 50 ), (int) \IPS\Request::i()->page ?: 1, 50 );
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function fieldTab( $field, $value ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$val = "{$field}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='ipsRichText i-margin-top_3' data-controller='core.front.core.lightboxedImages'>
		{$value}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function followers( $member, $followers ) {
		$return = '';
		$return .= <<<IPSCONTENT


<h2 class='ipsWidget__header'>
	<span>
IPSCONTENT;

$pluralize = array( ($followers !== NULL) ? $member->followersCount() : 0 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_followers', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id === $member->member_id ):
$return .= <<<IPSCONTENT

		<a href='#elFollowPref_menu' data-role='followOption' data-ipsMenu data-ipsMenu-selectable='radio' id='elFollowPref' class='i-color_inherit i-font-size_-1 i-margin-start_auto'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
		<ul id='elFollowPref_menu' class='ipsMenu ipsMenu_selectable ipsMenu_normal ipsHide'>
			<li data-ipsMenuValue='enable' class='ipsMenu_item 
IPSCONTENT;

if ( !$member->members_bitoptions['pp_setting_moderate_followers'] ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=changeFollow&enabled=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "profile", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'allow_follow', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
			<li data-ipsMenuValue='disable' class='ipsMenu_item 
IPSCONTENT;

if ( $member->members_bitoptions['pp_setting_moderate_followers'] ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=changeFollow&enabled=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "profile", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disallow_follow', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
			<li class='ipsMenu_sep'><hr></li>
			<li class='i-padding_2 i-text-align_center i-color_soft'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_setting_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</li>
		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</h2>
<div class="ipsWidget__content ipsWidget__padding">
	
IPSCONTENT;

if ( ! empty( $followers ) and is_array( $followers ) and \count( $followers ) ):
$return .= <<<IPSCONTENT

		<ul class='ipsCaterpillar ipsCaterpillar--reverse'>
			
IPSCONTENT;

foreach ( $followers as $idx => $follower ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $idx <= 11 ):
$return .= <<<IPSCONTENT

					<li class='ipsCaterpillar__item 
IPSCONTENT;

if ( $follower['follow_is_anon'] ):
$return .= <<<IPSCONTENT
i-opacity_4
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::load( $follower['follow_member_id'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $follower['follow_is_anon'] ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'anon_follower', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load($follower['follow_member_id']), 'fluid', NULL, '', FALSE );
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

		<p class='i-text-align_center i-color_soft i-padding_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_followers_yet', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $followers !== NULL and $member->followersCount() > 12 ):
$return .= <<<IPSCONTENT

		<p class='i-margin-top_2'>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=followers&id={$member->member_id}", null, "profile_followers", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit i-width_100p'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_all_followers', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right-long"></i></a>
		</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function hovercard( $member, $addWarningUrl ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$rnd = mt_rand();
$return .= <<<IPSCONTENT


IPSCONTENT;

$referrer = \IPS\Widget\Request::i()->referrer;
$return .= <<<IPSCONTENT


IPSCONTENT;

$coverPhoto = $member->coverPhoto();
$return .= <<<IPSCONTENT

<!-- When altering this template be sure to also check for similar in main profile view -->
<div class="cUserHovercard" id="elUserHovercard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rnd, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class="ipsCoverPhoto cUserHovercard__header" id="elProfileHeader_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rnd, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-controller="core.global.core.coverPhoto" data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url()->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-coveroffset="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" style="--offset:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

			<div class="ipsCoverPhoto__container">
				<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsCoverPhoto__image" alt="" loading="lazy">
			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class="ipsCoverPhoto__container">
				<div class="ipsFallbackImage" style="--i-empty-image-random--ba-co: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->coverPhotoBackgroundColor(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"></div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class="cUserHovercard__grid i-grid i-gap_lines">
		<div class="i-padding_2 i-flex i-align-items_center">
			<div class="i-flex_11 ipsPhotoPanel">
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsUserPhoto">
					<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
				</a>
				<div class="ipsPhotoPanel__text">
					<div class="ipsTitle ipsTitle--h4">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id AND \IPS\Member::loggedIn()->member_id === $member->member_id AND $member->isOnlineAnonymously() ):
$return .= <<<IPSCONTENT

							<span class="cProfileHeader_history" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_is_currently_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip>
								<i class="fa-solid fa-eye-slash"></i>
							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class="ipsPhotoPanel__secondary i-font-weight_600">
						
IPSCONTENT;

$return .= \IPS\Member\Group::load( $member->member_group_id )->formattedName;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
			
IPSCONTENT;

if ( ( \IPS\Member::loggedIn()->modPermission('can_flag_as_spammer') AND $member->member_id != \IPS\Member::loggedIn()->member_id ) || \IPS\Member::loggedIn()->canWarn( $member ) || \IPS\Member::loggedIn()->modPermission('can_manage_alerts') ):
$return .= <<<IPSCONTENT

				<a href="#elUserHovercard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_more_menu" id="elUserHovercard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rnd, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_more" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderator_tools', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
..." data-ipstooltip data-ipsmenu data-ipsmenu-appendto="#elUserHovercard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rnd, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-flex_00 ipsButton ipsButton--inherit"><i class="fa-solid fa-ellipsis"></i></a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="i-flex i-flex-wrap_wrap i-justify-content_space-between i-align-items_center">
			<div>
				<div class="i-color_soft i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_joined', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
				<div class="i-color_hard i-font-size_2 i-font-weight_600">
IPSCONTENT;

$val = ( $member->joined instanceof \IPS\DateTime ) ? $member->joined : \IPS\DateTime::ts( $member->joined );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
			</div>
			
IPSCONTENT;

if ( $member->last_activity && ( ( !$member->isOnlineAnonymously() ) OR ( $member->isOnlineAnonymously() AND \IPS\Member::loggedIn()->isAdmin() ) ) ):
$return .= <<<IPSCONTENT

				<div class="i-text-align_end">
					<div class="i-color_soft i-font-weight_500">
						
IPSCONTENT;

if ( $member->isOnline() AND ( !$member->isOnlineAnonymously() OR ( $member->isOnlineAnonymously() AND \IPS\Member::loggedIn()->isAdmin() ) ) ):
$return .= <<<IPSCONTENT

							<span class="ipsOnlineStatus" data-ipstooltip title="
IPSCONTENT;

if ( $member->isOnlineAnonymously() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $member->isOnline() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"></span> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_online_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( $member->last_activity ):
$return .= <<<IPSCONTENT

							<div>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_last_visit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
							<div class="i-color_hard i-font-size_2 i-font-weight_600">
IPSCONTENT;

$val = ( $member->last_activity instanceof \IPS\DateTime ) ? $member->last_activity : \IPS\DateTime::ts( $member->last_activity );$return .= $val->html();
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

		</div>
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/hovercard", "profileHoverStats:before", [ $member,$addWarningUrl ] );
$return .= <<<IPSCONTENT
<div class="ipsFluid i-gap_lines i-padding_0" data-ips-hook="profileHoverStats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/hovercard", "profileHoverStats:inside-start", [ $member,$addWarningUrl ] );
$return .= <<<IPSCONTENT

			<div class="i-grid i-place-content_center i-text-align_center i-padding_1">
				<div class="i-color_hard i-font-size_3 i-font-weight_600">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $member->member_posts );
$return .= <<<IPSCONTENT
</div>
				<div class="i-color_soft i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_member_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			</div>
			
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_show_profile ):
$return .= <<<IPSCONTENT

				<div class="i-grid i-place-content_center i-text-align_center i-padding_1">
					<div class="i-color_hard i-font-size_3 i-font-weight_600">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $member->pp_reputation_points );
$return .= <<<IPSCONTENT
</div>
					<div class="i-color_soft i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $member->canHaveAchievements() and \IPS\core\Achievements\Badge::show() AND \IPS\core\Achievements\Badge::getStore() ):
$return .= <<<IPSCONTENT

				<div class="i-grid i-place-content_center i-text-align_center i-padding_1">
					<div class="i-color_hard i-font-size_3 i-font-weight_600">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $member->badgeCount() );
$return .= <<<IPSCONTENT
</div>
					<div class="i-color_soft i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badges', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/hovercard", "profileHoverStats:inside-end", [ $member,$addWarningUrl ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/hovercard", "profileHoverStats:after", [ $member,$addWarningUrl ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $member->canHaveAchievements() and \IPS\core\Achievements\Rank::show() AND ( \count( \IPS\core\Achievements\Rank::getStore() ) && $member->rank() ) || ( \count( \IPS\core\Achievements\Badge::getStore() ) && \count( $member->recentBadges( 5 ) ) ) ):
$return .= <<<IPSCONTENT

			<div class="i-flex i-align-items_center i-flex-wrap_wrap">
				<div class="i-flex i-align-items_center i-gap_2">
					
IPSCONTENT;

if ( \IPS\core\Achievements\Rank::getStore() && $rank = $member->rank() ):
$return .= <<<IPSCONTENT

						<div class="i-flex_00" style="width:30px">{$rank->html( '' )}</div>
						<div>
							<div class="i-color_hard i-font-weight_600">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
							
IPSCONTENT;

if ( $rankEarned = $member->rankEarned() ):
$return .= <<<IPSCONTENT

								<p class="i-color_soft i-font-weight_500">
IPSCONTENT;

$val = ( $rankEarned instanceof \IPS\DateTime ) ? $rankEarned : \IPS\DateTime::ts( $rankEarned );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( \IPS\core\Achievements\Badge::show() AND \IPS\core\Achievements\Badge::getStore() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$recentBadges = $member->recentBadges( 5 );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \count( $recentBadges ) ):
$return .= <<<IPSCONTENT

						<ul class="i-margin-start_auto i-flex i-gap_1">
							
IPSCONTENT;

foreach ( $recentBadges as $badge ):
$return .= <<<IPSCONTENT

								<li style="width:30px">
									{$badge->html( 'ipsCaterpillar__badge', TRUE, TRUE )}
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->warn_on and !$member->inGroup( explode( ',', \IPS\Settings::i()->warn_protected ) ) and ( \IPS\Member::loggedIn()->modPermission('mod_see_warn') or ( \IPS\Settings::i()->warn_show_own and \IPS\Member::loggedIn()->member_id == $member->member_id ) ) ):
$return .= <<<IPSCONTENT

			<p class="i-text-align_center">
				<i class="fa-regular fa-thumbs-down i-margin-end_icon"></i>
IPSCONTENT;

$pluralize = array( $member->warn_level ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_warn_level', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_see_emails') ):
$return .= <<<IPSCONTENT

			<p class="i-text-align_center">
				<i class="fa-regular fa-envelope i-margin-end_icon"></i><a href="mailto:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_this_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="i-border-end-start-radius_box i-border-end-end-radius_box">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/hovercard", "profileHoverButtons:before", [ $member,$addWarningUrl ] );
$return .= <<<IPSCONTENT
<div class="ipsButtons ipsButtons--fill" data-ips-hook="profileHoverButtons">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/hovercard", "profileHoverButtons:inside-start", [ $member,$addWarningUrl ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( ( \IPS\Member::loggedIn()->member_id and \IPS\Member::loggedIn()->member_id !== $member->member_id ) && !$member->members_disable_pm and !\IPS\Member::loggedIn()->members_disable_pm and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$member->member_id}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-remotesubmit data-ipsdialog-flashmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_sent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-forcereload class="ipsButton ipsButton--inherit"><i class="fa-regular fa-envelope"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id && $member->canBeIgnored() and \IPS\Member::loggedIn()->member_id !== $member->member_id  ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore&id={$member->member_id}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "ignore", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit"><i class="fa-solid fa-user-slash"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_ignore', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=content&id={$member->member_id}", "front", "profile_content", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary"><i class="fa-regular fa-file-lines"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'find_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/hovercard", "profileHoverButtons:inside-end", [ $member,$addWarningUrl ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/hovercard", "profileHoverButtons:after", [ $member,$addWarningUrl ] );
$return .= <<<IPSCONTENT

		</div>
	</div>

	
IPSCONTENT;

if ( ( \IPS\Member::loggedIn()->modPermission('can_flag_as_spammer') AND $member->member_id != \IPS\Member::loggedIn()->member_id ) || \IPS\Member::loggedIn()->canWarn( $member ) || \IPS\Member::loggedIn()->modPermission('can_manage_alerts') ):
$return .= <<<IPSCONTENT

		<ul class="ipsMenu ipsMenu_narrow ipsHide" id="elUserHovercard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rnd, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_more_menu">
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_flag_as_spammer') and $member->member_id != \IPS\Member::loggedIn()->member_id and !$member->modPermission() and !$member->isAdmin() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $member->members_bitoptions['bw_is_spammer'] ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$member->member_id}&s=0&referrer={$referrer}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmsubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-flag"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$member->member_id}&s=1&referrer={$referrer}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-confirm><i class="fa-solid fa-flag"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_flag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canWarn( $member ) ):
$return .= <<<IPSCONTENT

				<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $addWarningUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_user_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-destructonclose><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_manage_alerts') ):
$return .= <<<IPSCONTENT

                <li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=alerts&action=create&user={$member->member_id}", null, "modcp_alerts", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-destructonclose><i class="fa-solid fa-bullhorn"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	</div>


IPSCONTENT;

if ( \IPS\Settings::i()->core_datalayer_enabled ):
$return .= <<<IPSCONTENT

<script>
    if ( IpsDataLayerConfig && !window.IpsDataLayerConfig && IpsDataLayerConfig._events.social_view.enabled ) {
        $('body').trigger( 'ipsDataLayer', {
            _key: 'social_view',
            _properties: 
IPSCONTENT;

$return .= json_encode(\IPS\core\DataLayer::i()->getMemberProfileEventProperties( $member, ['view_location' => 'hovercard'] ));
$return .= <<<IPSCONTENT

        } );
    }
</script>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

		return $return;
}

	function memberFollow( $app, $area, $id, $count, $search=FALSE ) {
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
' 
IPSCONTENT;

if ( $search ):
$return .= <<<IPSCONTENT
data-buttonType='search'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-controller='core.front.core.followButton'>
	
IPSCONTENT;

if ( $search ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->memberSearchFollowButton( $app, $area, $id, $count );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->memberFollowButton( $app, $area, $id, $count );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function memberFollowButton( $app, $area, $id, $count, $size='normal', $isFollowing=null ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ( $isFollowing === null and \IPS\Member::loggedIn()->following( $app, $area, $id ) ) or $isFollowing ):
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=follow&follow_app={$app}&follow_area={$area}&follow_id={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'following_this_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip class="ipsButton ipsButton--positive" data-role="followButton" data-ipsHover data-ipsHover-cache='false' data-ipsHover-onClick>
		    <i class='fa-solid fa-check'></i>
		    <span>
IPSCONTENT;

if ( $size === 'small' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'following_member_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'following_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
		    <i class='fa-solid fa-caret-down'></i>
		</a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
	
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=follow&follow_app={$app}&follow_area={$area}&follow_id={$id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_this_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip class="ipsButton ipsButton--inherit" data-role="followButton" data-ipsHover data-ipsHover-cache='false' data-ipsHover-onClick>
		    <i class='fa-solid fa-plus'></i>
		    <span>
IPSCONTENT;

if ( $size === 'small' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_member_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function memberSearchFollowButton( $app, $area, $id, $count ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->following( $app, $area, $id ) ):
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=follow&follow_app={$app}&follow_area={$area}&follow_id={$id}&from_search=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'following_this_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--positive" data-role="followButton" data-ipsHover data-ipsHover-cache='false' data-ipsHover-onClick><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'following_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='ipsMenuCaret'></i></a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
	
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=follow&follow_app={$app}&follow_area={$area}&follow_id={$id}&from_search=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_this_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit" data-role="followButton" data-ipsHover data-ipsHover-cache='false' data-ipsHover-onClick><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'follow_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function profile( $member, $mainContent, $visitors, $sidebarFields, $followers, $addWarningUrl, $solutions ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<!-- When altering this template be sure to also check for similar in the hovercard -->
<div data-controller="core.front.profile.main">
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core", 'front' )->profileHeader( $member, false, $solutions );
$return .= <<<IPSCONTENT

	<div data-role="profileContent">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsProfile ipsProfile--profile">
			<aside class="ipsProfile__aside ipsProfile__aside--carousel" id="elProfileInfoColumn">
				<div class="ipsProfile__sticky-outer">
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profile", "profileInfoColumn:before", [ $member,$mainContent,$visitors,$sidebarFields,$followers,$addWarningUrl,$solutions ] );
$return .= <<<IPSCONTENT
<div class="ipsProfile__sticky-inner" data-ips-hook="profileInfoColumn">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profile", "profileInfoColumn:inside-start", [ $member,$mainContent,$visitors,$sidebarFields,$followers,$addWarningUrl,$solutions ] );
$return .= <<<IPSCONTENT

					
						
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('nexus') and \IPS\Settings::i()->nexus_subs_enabled and \IPS\Settings::i()->nexus_subs_show_public ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "subscription", "nexus", 'front' )->profileSubscription( $member );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


						
IPSCONTENT;

if ( (\IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_show_profile) or (\IPS\core\Achievements\Rank::show() and \count( \IPS\core\Achievements\Rank::getStore() ) && $rank = $member->rank()) ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget ipsWidget--rank-badges">
								<div class="ipsWidget__content">

									
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_show_profile ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_leaderboard_on and \IPS\Settings::i()->reputation_show_days_won_trophy and $member->getReputationDaysWonCount() and $lastDayWon = $member->getReputationLastDayWon() ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$formattedDate = $lastDayWon['date']->dayAndMonth() . (  $lastDayWon['date']->format('Y') == \IPS\DateTime::ts( time() )->format('Y' ) ? '' : " " . $lastDayWon['date']->format('Y') );
$return .= <<<IPSCONTENT

											<div class="i-link-color_inherit i-flex i-gap_2">
												<div class="i-flex_00 i-basis_40 i-text-align_center" style="font-size:30px">
													🏆
												</div>
												<div class="i-flex_11">
													<h3 class="i-font-weight_600 i-color_hard i-font-size_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->getReputationDaysWonCount() );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_days_won_count', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
!</h3>
													<p class="i-font-size_-1 i-color_soft">
IPSCONTENT;

if ( $member->member_id == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_you_congrats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_member_congrats', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=discover&controller=popular&tab=leaderboard&custom_date_start={$lastDayWon['date']->getTimeStamp()}&custom_date_end={$lastDayWon['date']->getTimeStamp()}", null, "leaderboard_leaderboard", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="i-font-weight_600">
														
IPSCONTENT;

if ( $member->member_id == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

															
IPSCONTENT;

$sprintf = array($formattedDate); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_you_won', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

														
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

															
IPSCONTENT;

$sprintf = array($member->name, $formattedDate); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_member_won', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

														
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													</a></p>
												</div>
											</div>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


									
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_show_profile ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( ! $member->canHaveAchievements() OR ! \IPS\core\Achievements\Badge::show() OR (!\count( \IPS\core\Achievements\Rank::getStore() ) && !\count( \IPS\core\Achievements\Badge::getStore() ) ) ):
$return .= <<<IPSCONTENT

											<div>
												
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

													<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=reputation", null, "profile_reputation", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="repLog" title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													<div class="cProfileRepScore cProfileRepScore--badge 
IPSCONTENT;

if ( $member->pp_reputation_points > 1 ):
$return .= <<<IPSCONTENT
cProfileRepScore--positive
IPSCONTENT;

elseif ( $member->pp_reputation_points < 0 ):
$return .= <<<IPSCONTENT
cProfileRepScore--negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
cProfileRepScore--neutral
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
														<h2 class="ipsMinorTitle i-color_inherit i-opacity_5">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
														<span class="cProfileRepScore__points">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->pp_reputation_points );
$return .= <<<IPSCONTENT
</span>
														
IPSCONTENT;

if ( $member->reputation() ):
$return .= <<<IPSCONTENT

															<span class="cProfileRepScore__title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->reputation(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
														
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

														
IPSCONTENT;

if ( $member->reputationImage() ):
$return .= <<<IPSCONTENT

															<div class="i-background_1 i-padding_2 i-text-align_center i-margin-top_2">
																<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $member->reputationImage() )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
															</div>
														
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													</div>
												
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

													<p class="i-padding-top_2 i-text-align_end i-color_soft i-font-size_-2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_show_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-right"></i></p>
												</a>
												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											</div>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


									
IPSCONTENT;

if ( \IPS\core\Achievements\Rank::show() and \count( \IPS\core\Achievements\Rank::getStore() ) && $rank = $member->rank() ):
$return .= <<<IPSCONTENT

										<div class="i-flex i-gap_2 i-align-items_center">
											<div class="i-flex_00 i-basis_40">
												{$rank->html( 'ipsDimension i-basis_40' )}
											</div>
											<div class="i-flex_11">
												<h3 class="i-font-weight_600 i-color_hard i-font-size_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
												<p class="i-color_soft i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_current_rank', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 (
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['pos'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rank->rankPosition()['max'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)</p>

												
IPSCONTENT;

if ( $nextRank = $member->nextRank() ):
$return .= <<<IPSCONTENT

													<div class="i-margin-top_2">
														<progress class="ipsProgress ipsProgress--rank i-margin-bottom_1" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $member->achievements_points / $nextRank->points * 100, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" max="100"></progress>
														
IPSCONTENT;

if ( $member->member_id == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

															<div class="i-font-size_-2 i-color_soft">
IPSCONTENT;

$pluralize = array( $nextRank->points - $member->achievements_points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_next_rank', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
														
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


									
IPSCONTENT;

if ( \IPS\core\Achievements\Badge::show() and \count( \IPS\core\Achievements\Badge::getStore() ) ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$recentBadges = $member->recentBadges( 6 );
$return .= <<<IPSCONTENT
	
										
IPSCONTENT;

if ( \count( $recentBadges ) ):
$return .= <<<IPSCONTENT

											<div>
												<div class="i-flex i-align-items_center i-justify-content_space-between i-margin-bottom_2">
													<h3 class="i-font-weight_600 i-color_hard i-font-size_2">
														
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_recent_badges', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

													</h3>
													<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=badges", null, "profile_badges", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="badgeLog" title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_badges', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" class="i-font-size_-1 i-font-weight_500 i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
												</div>
												<ul class="i-flex i-gap_1">
													
IPSCONTENT;

foreach ( $recentBadges as $badge ):
$return .= <<<IPSCONTENT

														<li class="i-basis_40">
															{$badge->html( '', TRUE, TRUE )}
														</li>
													
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

												</ul>
											</div>										
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


								</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


						
IPSCONTENT;

if ( $member->isExpert() ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget ipsWidget--expert">
								<h3 class="ipsWidget__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'community_expert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<div class="ipsWidget__content ipsWidget__padding">
									<div class="i-flex_11 i-grid i-place-content_center i-font-size_6">
										<i class="fa-solid fa-medal"></i>
									</div>
								</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


						
IPSCONTENT;

if ( $solutions && ( ! $member->canHaveAchievements() OR !\IPS\core\Achievements\Badge::show() OR ( !\count( \IPS\core\Achievements\Rank::getStore() ) && !\count( \IPS\core\Achievements\Badge::getStore() ) ) ) ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget ipsWidget--solutions">
								<div class="ipsWidget__content ipsWidget__padding">
									<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=solutions", null, "profile_solutions", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="solutionLog" title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
										<div class="cProfileRepScore cProfileRepScore--badge cProfileRepScore--solutions">
											<h2 class="ipsMinorTitle i-color_inherit i-opacity_5">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
											<span class="cProfileRepScore__points">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $solutions );
$return .= <<<IPSCONTENT
</span>
										</div>
										<p class="i-padding-top_2 i-text-align_end i-color_soft i-font-size_-2">
											
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solutionlog_show_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-right"></i>
										</p>
									</a>
								</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
						
IPSCONTENT;

if ( \IPS\Settings::i()->warn_on and !$member->inGroup( explode( ',', \IPS\Settings::i()->warn_protected ) ) and ( \IPS\Member::loggedIn()->modPermission('mod_see_warn') or ( \IPS\Settings::i()->warn_show_own and \IPS\Member::loggedIn()->member_id == $member->member_id ) ) ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget ipsWidget--warnings">
								<div class="ipsWidget__content">
									<div class="i-padding_2">
										<div id="elWarningInfo" class="i-padding_1">
											<h4 class="i-margin-bottom_2 i-font-weight_600 i-font-size_3 
IPSCONTENT;

if ( $member->mod_posts || $member->restrict_post || $member->temp_ban ):
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $member->mod_posts || $member->restrict_post || $member->temp_ban ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-triangle-exclamation i-margin-end_icon"></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

$pluralize = array( $member->warn_level ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_warn_level', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h4>
											
IPSCONTENT;

if ( !$member->mod_posts && !$member->restrict_post && !$member->temp_ban ):
$return .= <<<IPSCONTENT

												<div class="
IPSCONTENT;

if ( $member->mod_posts || $member->restrict_post || $member->temp_ban ):
$return .= <<<IPSCONTENT
i-color_negative i-font-weight_500
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_restrictions_applied', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												<div class="i-font-weight_500 i-color_hard">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restrictions_applied', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
												<ul class="ipsList ipsList--csv">
													
IPSCONTENT;

if ( $member->mod_posts ):
$return .= <<<IPSCONTENT

														<li data-ipstooltip title="
IPSCONTENT;

if ( $member->mod_posts == -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_modq_perm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $member->mod_posts )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_modq_temp', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_modq', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													
IPSCONTENT;

if ( $member->restrict_post ):
$return .= <<<IPSCONTENT

														<li data-ipstooltip title="
IPSCONTENT;

if ( $member->restrict_post == -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_nopost_perm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $member->restrict_post )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_nopost_temp', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_nopost', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													
IPSCONTENT;

if ( $member->temp_ban ):
$return .= <<<IPSCONTENT

														<li data-ipstooltip title="
IPSCONTENT;

if ( $member->temp_ban == -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_banned_perm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $member->temp_ban )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_banned_temp', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderation_banned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												</ul>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</div>
									</div>
									
IPSCONTENT;

if ( ( \IPS\Member::loggedIn()->canWarn( $member ) || \IPS\Member::loggedIn()->modPermission('can_manage_alerts') || ( \IPS\Member::loggedIn()->modPermission('can_flag_as_spammer') and !$member->modPermission() and !$member->isAdmin() ) ) and $member->member_id != \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

										<ul class="ipsButtons ipsButtons--fill i-padding_1 i-padding-top_0">
											
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_flag_as_spammer') and $member->member_id != \IPS\Member::loggedIn()->member_id and !$member->modPermission() and !$member->isAdmin() ):
$return .= <<<IPSCONTENT

												<li>
													
IPSCONTENT;

if ( $member->members_bitoptions['bw_is_spammer'] ):
$return .= <<<IPSCONTENT

														<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$member->member_id}&s=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--small" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirm data-confirmsubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-user-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
													
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

														<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$member->member_id}&s=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--small" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_flag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirm><i class="fa-solid fa-comment-slash"></i> 
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

if ( \IPS\Member::loggedIn()->modPermission('can_manage_alerts') ):
$return .= <<<IPSCONTENT

												<li>
													<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=alerts&action=create&user={$member->member_id}", null, "modcp_alerts", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" id="elWarnUserButton" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--small" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-bullhorn"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alert_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
												</li>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canWarn( $member ) ):
$return .= <<<IPSCONTENT

												<li>
													<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $addWarningUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id="elWarnUserButton" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_member', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--secondary ipsButton--wide ipsButton--small" title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_member', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

									
IPSCONTENT;

if ( \count( $member->warnings( 1 ) ) ):
$return .= <<<IPSCONTENT

										<i-data data-role="recentWarnings">
											<ol class="ipsData ipsData--table ipsData--compact ipsData--warnings i-border-top_3">
												
IPSCONTENT;

foreach ( $member->warnings( 2 ) as $warning ):
$return .= <<<IPSCONTENT

													<li class="ipsData__item" id="elWarningOverview_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
														<div class="ipsData__icon">
															<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=view&id={$member->member_id}&w={$warning->id}", null, "warn_view", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipstooltip title="
IPSCONTENT;

$pluralize = array( $warning->points ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'wan_action_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
">
																<span class="ipsWarningPoints 
IPSCONTENT;

if ( $warning->expire_date == 0 ):
$return .= <<<IPSCONTENT
ipsWarningPoints--removed
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->points, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
															</a>
														</div>
														<div class="ipsData__main">
															<h4 class="ipsData__title">
																<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=view&id={$member->member_id}&w={$warning->id}", null, "warn_view", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-showfrom="#elWarningOverview_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog-size="narrow" title="">

																	
IPSCONTENT;

if ( \IPS\Settings::i()->warnings_acknowledge ):
$return .= <<<IPSCONTENT

																		
IPSCONTENT;

if ( $warning->acknowledged ):
$return .= <<<IPSCONTENT

																			<strong class="i-color_positive" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_acknowledged', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip><i class="fa-solid fa-check-circle"></i></strong>
																		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

																			<strong class="i-color_soft" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_not_acknowledged', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip><i class="fa-regular fa-circle"></i></strong>
																		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

																	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

																	
IPSCONTENT;

$val = "core_warn_reason_{$warning->reason}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

																</a>
															</h4>
															<p class="ipsData__meta">
																
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $warning->moderator )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 · 
IPSCONTENT;

$val = ( $warning->date instanceof \IPS\DateTime ) ? $warning->date : \IPS\DateTime::ts( $warning->date );$return .= $val->html();
$return .= <<<IPSCONTENT

															</p>
															
IPSCONTENT;

if ( $warning->expire_date == 0 ):
$return .= <<<IPSCONTENT

																<p class="ipsData__meta i-margin-top_2">
																	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_no_longer_active', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $warning->removed_on ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $warning->removed_on )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_expired_on', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

																</p>
															
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
													
														</div>
														
IPSCONTENT;

if ( $warning->canDelete() ):
$return .= <<<IPSCONTENT

															<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $warning->url('delete')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke_this_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-action="revoke" class="ipsButton ipsButton--small ipsButton--text ipsButton--icon" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revoke_this_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-size="medium"><i class="fa-solid fa-rotate-left"></i></a>
														
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

													</li>
												
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

											</ol>
											<p class="ipsViewAll">
												<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&id={$member->member_id}", null, "warn_list", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_all_warnings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-remoteverify="false" data-ipsdialog-remotesubmit="false" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_warnings', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_all_c', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
											</p>
										</i-data>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</div>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_flag_as_spammer') and !$member->inGroup( explode( ',', \IPS\Settings::i()->warn_protected ) ) and \IPS\Member::loggedIn()->member_id != $member->member_id ):
$return .= <<<IPSCONTENT

								<div class="ipsWidget ipsWidget--spam">
									<div class="ipsWidget__content ipsWidget__padding">
										
IPSCONTENT;

if ( $member->members_bitoptions['bw_is_spammer'] ):
$return .= <<<IPSCONTENT

											<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$member->member_id}&s=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--small ipsButton--wide" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirm data-confirmsubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$member->member_id}&s=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit ipsButton--small ipsButton--wide" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_flag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_flag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $member->group['g_icon'] || ( $member->isOnline() AND ( !$member->isOnlineAnonymously() OR ( $member->isOnlineAnonymously() AND \IPS\Member::loggedIn()->isAdmin() ) ) AND $member->location ) || ( $member->birthday AND ( \IPS\Settings::i()->profile_birthday_type == 'public' or ( \IPS\Settings::i()->profile_birthday_type == 'private' and ( \IPS\Member::loggedIn()->member_id == $member->member_id OR \IPS\Member::loggedIn()->isAdmin() ) ) ) ) ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget ipsWidget--about" data-location="defaultFields">
								<h2 class="ipsWidget__header">
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h2>
								<div class="ipsWidget__content ipsWidget__padding">
									
IPSCONTENT;

if ( $member->group['g_icon']  ):
$return .= <<<IPSCONTENT

										<div class="i-text-align_center i-padding_2 i-margin-bottom_3"><img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $member->group['g_icon'] )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy"></div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									<ul class="ipsProfileWidget__data">
										<li>
											<strong class="ipsProfileWidget__dataLabel">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'joined', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
											<div class="ipsProfileWidget__dataValue">
IPSCONTENT;

$val = ( $member->joined instanceof \IPS\DateTime ) ? $member->joined : \IPS\DateTime::ts( $member->joined );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
										</li>
										
IPSCONTENT;

if ( $member->isOnline() AND ( !$member->isOnlineAnonymously() OR ( $member->isOnlineAnonymously() AND \IPS\Member::loggedIn()->isAdmin() ) ) AND $member->location ):
$return .= <<<IPSCONTENT

											<li>
												<strong class="ipsProfileWidget__dataLabel">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_users_location_lang', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
												<div class="ipsProfileWidget__dataValue">{$member->location()}</div>
											</li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $member->birthday AND \IPS\Settings::i()->profile_birthday_type == 'public' or ( \IPS\Settings::i()->profile_birthday_type == 'private' and ( \IPS\Member::loggedIn()->member_id == $member->member_id OR \IPS\Member::loggedIn()->isAdmin() ) ) ):
$return .= <<<IPSCONTENT

											<li>
												<strong class="ipsProfileWidget__dataLabel">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'bday', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
												<div class="ipsProfileWidget__dataValue">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->birthday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
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

						
IPSCONTENT;

if ( ( ! empty( $followers ) and is_array( $followers ) and count( $followers ) ) || \IPS\Member::loggedIn()->member_id === $member->member_id ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget ipsWidget--followers" id="elFollowers" data-feedid="member-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-controller="core.front.profile.followers">
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->followers( $member, $followers );
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $sidebarFields as $group => $fields ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \count( $fields ) AND \count( array_filter( $fields, function( $fieldValue ){ return $fieldValue['value']; } ) ) ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget ipsWidget--fields" data-location="customFields">
								
IPSCONTENT;

if ( $group != 'core_pfieldgroups_0' ):
$return .= <<<IPSCONTENT

									<h2 class="ipsWidget__header">
IPSCONTENT;

$val = "{$group}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<div class="ipsWidget__content ipsWidget__padding">
									<ul class="ipsProfileWidget__data">
										
IPSCONTENT;

foreach ( $fields as $field => $value ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $value['value'] !== "" ):
$return .= <<<IPSCONTENT

												<li>
													
IPSCONTENT;

if ( $value['custom'] ):
$return .= <<<IPSCONTENT

														{$value['value']}
													
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

														<strong class="ipsProfileWidget__dataLabel">
IPSCONTENT;

$val = "{$field}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
														<div class="ipsProfileWidget__dataValue">{$value['value']}</div>
													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												</li>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</div>
							</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_see_emails') ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget ipsWidget--contact">
								<h2 class="ipsWidget__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_contact', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
								<div class="ipsWidget__content ipsWidget__padding">
									<ul class="ipsProfileWidget__data">
										<li>
											<strong class="ipsProfileWidget__dataLabel">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_email', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
											<div class="ipsProfileWidget__dataValue">
												<div><a href="mailto:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'email_this_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->email, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
												<span class="i-color_soft i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_email_addresses', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
											</div>
										</li>
									</ul>
								</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !empty( $visitors ) || \IPS\Member::loggedIn()->member_id == $member->member_id ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget ipsWidget--recent" data-controller="core.front.profile.toggleBlock">
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", \IPS\Request::i()->app )->recentVisitorsBlock( $member, $visitors );
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profile", "profileInfoColumn:inside-end", [ $member,$mainContent,$visitors,$sidebarFields,$followers,$addWarningUrl,$solutions ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profile", "profileInfoColumn:after", [ $member,$mainContent,$visitors,$sidebarFields,$followers,$addWarningUrl,$solutions ] );
$return .= <<<IPSCONTENT

				</div>
			</aside>
			<section class="ipsProfile__main ipsBox ipsPull">
				{$mainContent}
			</section>
		</div>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function profileActivity( $member, $latestActivity ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	
IPSCONTENT;

if ( !\count( $latestActivity ) ):
$return .= <<<IPSCONTENT

		<div class='i-padding_3 i-text-align_center i-font-size_2 i-color_soft'>
			
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_recent_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<ol class='ipsStream' data-role='activityStream' id='elProfileActivityOverview'>
			
IPSCONTENT;

foreach ( $latestActivity as $activity ):
$return .= <<<IPSCONTENT

				{$activity->html()}
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function profileClubs( $member, $clubs, $pagination ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $clubs ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

		{$pagination}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<ul class='ipsData ipsData--grid ipsData--profile-clubs'>
		
IPSCONTENT;

foreach ( $clubs as $club ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubCard( $club );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</ul>

	
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

		{$pagination}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<p class='ipsEmptyMessage'>
		
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_no_clubs', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function profileHeader( $member, $small=FALSE, $solutions=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$coverPhoto = $member->coverPhoto();
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeader:before", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT
<header class="ipsPageHeader ipsBox ipsPull i-margin-bottom_block" data-ips-hook="profileHeader" data-role="profileHeader">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeader:inside-start", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT

	<div class="ipsCoverPhoto ipsCoverPhoto--profile 
IPSCONTENT;

if ( $small === true ):
$return .= <<<IPSCONTENT
ipsCoverPhoto--minimal
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" id="elProfileHeader" data-controller="core.global.core.coverPhoto" data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url()->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-coveroffset="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" style="--offset:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

			<div class="ipsCoverPhoto__container">
				<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsCoverPhoto__image" data-action="toggleCoverPhoto" alt="" loading="lazy">
			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class="ipsCoverPhoto__container">
				<div class="ipsFallbackImage" style="--i-empty-image-random--ba-co: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->coverPhotoBackgroundColor(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"></div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_modify_profiles') or ( \IPS\Member::loggedIn()->member_id == $member->member_id ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderMenu:before", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT
<div class="ipsCoverPhoto__overlay-buttons" data-hideoncoveredit data-ips-hook="profileHeaderMenu">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderMenu:inside-start", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= $member->menu( 'profile' );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderMenu:inside-end", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderMenu:after", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class="ipsCoverPhotoMeta">
		<div class="ipsCoverPhoto__avatar" id="elProfilePhoto">
			
IPSCONTENT;

if ( $member->pp_main_photo and ( mb_substr( $member->pp_photo_type, 0, 5 ) === 'sync-' or $member->pp_photo_type === 'custom' ) ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;

$return .= \IPS\File::get( "core_Profile", $member->pp_main_photo )->url;
$return .= <<<IPSCONTENT
" data-ipslightbox class="ipsUserPhoto">
					<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
				</a>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="ipsUserPhoto">
					<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsCoverPhoto__titles">
			<div class="ipsCoverPhoto__title">
				<h1>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_view_displaynamehistory'] AND $member->hasNameChanges() ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url()->setQueryString( 'do', 'namehistory' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsCoverPhoto__title-icon" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'membername_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ipsdialog data-ipsdialog-modal="true" data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'membername_history', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
						<i class="fa-solid fa-clock-rotate-left"></i>
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id AND \IPS\Member::loggedIn()->member_id === $member->member_id AND $member->isOnlineAnonymously() ):
$return .= <<<IPSCONTENT

					<span class="ipsCoverPhoto__title-icon" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_is_currently_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip>
						<i class="fa-solid fa-eye-slash"></i>
					</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsCoverPhoto__desc">
				
IPSCONTENT;

$return .= \IPS\Member\Group::load( $member->member_group_id )->formattedName;
$return .= <<<IPSCONTENT

			</div>
		</div>
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderStats:before", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT
<ul class="ipsCoverPhoto__stats" data-ips-hook="profileHeaderStats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderStats:inside-start", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ( !$member->isOnlineAnonymously() ) OR ( $member->isOnlineAnonymously() AND \IPS\Member::loggedIn()->isAdmin() ) ):
$return .= <<<IPSCONTENT

				<li>
					<h4 class="ipsCoverPhoto__statTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_last_visit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<span class="ipsCoverPhoto__statValue">
						
IPSCONTENT;

if ( $member->isOnline() AND ( !$member->isOnlineAnonymously() OR ( $member->isOnlineAnonymously() AND \IPS\Member::loggedIn()->isAdmin() ) ) ):
$return .= <<<IPSCONTENT

							<i class="fa-solid fa-circle ipsOnlineStatus_online" data-ipstooltip title="
IPSCONTENT;

if ( $member->isOnlineAnonymously() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $member->isOnline() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $member->last_activity ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $member->last_activity instanceof \IPS\DateTime ) ? $member->last_activity : \IPS\DateTime::ts( $member->last_activity );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'never', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<li>
				<h4 class="ipsCoverPhoto__statTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_member_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				<span class="ipsCoverPhoto__statValue">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->member_posts );
$return .= <<<IPSCONTENT
</span>
			</li>
			
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_show_profile ):
$return .= <<<IPSCONTENT

				<li 
IPSCONTENT;

if ( $member->reputation() ):
$return .= <<<IPSCONTENT
data-ipstooltip title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->reputation(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<h4 class="ipsCoverPhoto__statTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<span class="ipsCoverPhoto__statValue 
IPSCONTENT;

if ( $member->pp_reputation_points > 1 ):
$return .= <<<IPSCONTENT
i-color_positive
IPSCONTENT;

elseif ( $member->pp_reputation_points < 0 ):
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $member->pp_reputation_points );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=reputation", null, "profile_reputation", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="repLog" title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" class="ipsCoverPhoto__link">
							<span class="ipsInvisible">
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
						</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $solutions ):
$return .= <<<IPSCONTENT

				<li>
					<h4 class="ipsCoverPhoto__statTitle">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<span class="ipsCoverPhoto__statValue">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $solutions );
$return .= <<<IPSCONTENT
</span>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=solutions", null, "profile_solutions", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="solutionLog" title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" class="ipsCoverPhoto__link">
						<span class="ipsInvisible">
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
					</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderStats:inside-end", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderStats:after", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderButtons:before", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT
<div class="ipsCoverPhoto__buttons" data-ips-hook="profileHeaderButtons">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderButtons:inside-start", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id != $member->member_id ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id != $member->member_id and ( !$member->members_bitoptions['pp_setting_moderate_followers'] or \IPS\Member::loggedIn()->following( 'core', 'member', $member->member_id ) ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->memberFollow( 'core', 'member', $member->member_id, $member->followersCount() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id && !$member->members_disable_pm and !\IPS\Member::loggedIn()->members_disable_pm and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$member->member_id}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-remotesubmit data-ipsdialog-flashmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_sent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit"><i class="fa-solid fa-envelope"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary" data-action="goToProfile" data-type="full" title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-circle-user"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_view_profile', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=content&id={$member->member_id}", "front", "profile_content", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" class="ipsButton ipsButton--primary" data-action="browseContent" data-type="full" title="
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-file-lines"></i><span>
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id === $member->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_browse_my_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_browse_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderButtons:inside-end", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeaderButtons:after", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeader:inside-end", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT
</header>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/profile/profileHeader", "profileHeader:after", [ $member,$small,$solutions ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function profileTabs( $member, $tabs, $activeTab, $activeTabContents ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $tabs ) > 1 ):
$return .= <<<IPSCONTENT

	<i-tabs class='ipsTabs ipsTabs--sticky ipsTabs--profile ipsTabs--stretch' id='elProfileTabs' data-ipsTabBar data-ipsTabBar-contentArea='#elProfileTabs_content'>
		<div role="tablist">
			
IPSCONTENT;

foreach ( $tabs as $tab => $title ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url()->setQueryString( 'tab', $tab ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elProfileTab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsTabs__tab' role="tab" aria-controls="elProfileTab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( $activeTab == $tab ):
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

$val = "{$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

<div id='elProfileTabs_content' class='ipsTabs__panels ipsTabs__panels--profile'>
	
IPSCONTENT;

foreach ( $tabs as $tab => $title ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $activeTab == $tab ):
$return .= <<<IPSCONTENT

			<div id="elProfileTab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class='ipsTabs__panel' role="tabpanel" aria-labelledby="elProfileTab_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				{$activeTabContents}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function recentVisitorsBlock( $member, $visitors ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $member->members_bitoptions['pp_setting_count_visitors'] ):
$return .= <<<IPSCONTENT

	
	<h2 class='ipsWidget__header'>
		<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_recent_visitors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_modify_profiles') or ( \IPS\Member::loggedIn()->member_id == $member->member_id and $member->group['g_edit_profile'] ) ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=visitors&id=$member->member_id" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "profile", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='i-color_inherit i-opacity_5 i-margin-start_auto' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide_recent_visitors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action='disable'><i class='fa-solid fa-xmark'></i></a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h2>
	<div class="ipsWidget__content ipsWidget__padding">
		<div class='i-color_soft i-margin-bottom_2'>
			
IPSCONTENT;

$pluralize = array( $member->members_profile_views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_views', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( \is_array( $visitors ) AND \count( $visitors )  ):
$return .= <<<IPSCONTENT

			<ul class='ipsProfileWidget__data'>
			
IPSCONTENT;

foreach ( $visitors as $visitor ):
$return .= <<<IPSCONTENT

				<li>
					<div class='ipsPhotoPanel'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $visitor['member'], 'fluid' );
$return .= <<<IPSCONTENT

						<div class='ipsPhotoPanel__text'>
							<div class='ipsPhotoPanel__primary'>{$visitor['member']->link()}</div>
							<div class='ipsPhotoPanel__secondary i-font-size_-1'>
IPSCONTENT;

$val = ( $visitor['visit_time'] instanceof \IPS\DateTime ) ? $visitor['visit_time'] : \IPS\DateTime::ts( $visitor['visit_time'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
						</div>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='i-text-align_center i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_recent_visitors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<h2 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_recent_visitors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='ipsWidget__content ipsWidget__padding i-text-align_center'>
		<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disabled_recent_visitors', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
        
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_modify_profiles') or ( \IPS\Member::loggedIn()->member_id == $member->member_id and $member->group['g_edit_profile'] ) ):
$return .= <<<IPSCONTENT

			<div class='i-margin-top_1 i-font-weight_500'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=visitors&id={$member->member_id}&state=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "profile", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-action='enable'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>
        
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

	function singleStatus( $member, $status ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-padding_3' id='elSingleStatusUpdate'>
	<h2 class='ipsTitle ipsTitle--h3 
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->status ) ):
$return .= <<<IPSCONTENT
i-margin-top_3
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'viewing_single_status', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</h2>
</div>
IPSCONTENT;

		return $return;
}

	function tableRow( $table, $headers, $members ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $members as $member ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$loadedMember = \IPS\Member::load( $member->member_id );
$return .= <<<IPSCONTENT

	<li class='ipsData__item'>
		<div class='ipsData__icon'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $loadedMember, 'medium' );
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<div class='ipsData__title'><h3>{$loadedMember->link()}</h3> 
IPSCONTENT;

if ( $loadedMember->isOnline() ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle ipsOnlineStatus_online" data-ipsTooltip title='
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'></i>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
				<span>
IPSCONTENT;

$return .= \IPS\Member\Group::load( $member->member_group_id )->formattedName;
$return .= <<<IPSCONTENT
</span>
				<ul class='ipsList ipsList--inline i-color_soft'>
					<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_member_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $loadedMember->member_posts, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></li>
					<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_joined', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
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
 
IPSCONTENT;

$val = ( $loadedMember->last_activity instanceof \IPS\DateTime ) ? $loadedMember->last_activity : \IPS\DateTime::ts( $loadedMember->last_activity );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
		
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

			<div class='ipsData__mod'>
				<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $member ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

	function userBadgeOverview( $member, $percentage ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$baseUrl = \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=content", 'front', 'profile_content', $member->members_seo_name );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<div data-controller='core.front.profile.main' id='elProfileUserContent'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core", 'front' )->profileHeader( $member, true );
$return .= <<<IPSCONTENT

	<div data-role="profileContent">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsProfile ipsProfile--badges'>
            
IPSCONTENT;

if ( $member->canHaveAchievements() and \IPS\core\Achievements\Rank::show() and \IPS\core\Achievements\Rank::getStore() and $rank = $member->rank() ):
$return .= <<<IPSCONTENT

                <aside class='ipsProfile__aside'>
                    <div class="ipsProfile__sticky-outer">
                        <div class="ipsProfile__sticky-inner">
                            <div class='ipsWidget'>
                                <h2 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_profile_rank_progress', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
                                <div class='i-padding_3'>
                                    
IPSCONTENT;

if ( $rank ):
$return .= <<<IPSCONTENT

                                    <p class='i-margin-bottom_3'>
                                        
IPSCONTENT;

if ( $percentage ):
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

$sprintf = array($member->name, $member->rank()->rankPosition()['pos'], $member->rank()->rankPosition()['max'], $percentage); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_rank_progress_blurb_percentage', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

                                        <span class="i-color_soft i-font-size_-2" data-ipsToolTip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_rank_progress_blurb_percentage_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-circle-info"></i></span>
                                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

$sprintf = array($member->name, $member->rank()->rankPosition()['pos'], $member->rank()->rankPosition()['max']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_rank_progress_blurb', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    </p>
                                    <hr class='ipsHr i-margin-block_3'>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


                                    <ul class='cRankHistory'>
                                        
IPSCONTENT;

if ( $member->rankHistory()['earned'] ):
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

foreach ( $member->rankHistory()['earned'] as $entry ):
$return .= <<<IPSCONTENT

                                                <li class='cRankHistory__item'>
                                                    <div class='cRankHistory__icon'>{$entry['rank']->html( 'cRankHistory__itemBadge' )}</div>
                                                    <div>
                                                        <h3 class='i-font-weight_semi-bold i-color_hard i-font-size_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry['rank']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
                                                        
IPSCONTENT;

if ( ! $entry['time'] or ( $entry['time']->getTimestamp() < \IPS\Settings::i()->achievements_last_rebuilt) ):
$return .= <<<IPSCONTENT

                                                            
IPSCONTENT;

if ( \IPS\Settings::i()->achievements_last_rebuilt ):
$return .= <<<IPSCONTENT
<p class='i-color_soft'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( \IPS\Settings::i()->achievements_last_rebuilt )->shortMonthAndFullYear()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badge_earned_date_while_rebuilding', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                        
IPSCONTENT;

elseif ( $entry['time'] ):
$return .= <<<IPSCONTENT

                                                        <p class='i-color_soft'>
IPSCONTENT;

$htmlsprintf = array($entry['time']->html()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badge_earned_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
                                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                                    </div>
                                                </li>
                                            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( $member->rankHistory()['not_earned'] ):
$return .= <<<IPSCONTENT

                                            
IPSCONTENT;

foreach ( $member->rankHistory()['not_earned'] as $entry ):
$return .= <<<IPSCONTENT

                                                <li class='cRankHistory__item'>
                                                <div class='cRankHistory__icon'>{$entry['rank']->html( 'cRankHistory__itemBadge cRankHistory__itemBadge--unearned' )}</div>
                                                    <div class='i-opacity_6'>
                                                        <h3 class='i-font-weight_semi-bold i-font-size_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry['rank']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
                                                        <p><em>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badge_earned_but_not_really', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em></p>
                                                    </div>
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
                        </div>
                    </div>
                </aside>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<section class='ipsProfile__main ipsBox'>
                <h2 class='ipsBox__header'>
IPSCONTENT;

$pluralize = array( $member->badgeCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_profile_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
                
IPSCONTENT;

if ( $member->badgeCount() ):
$return .= <<<IPSCONTENT

                    <div class='i-padding_3 ipsGrid i-basis_200 i-gap_4 cProfileBadgeGrid'>
                        
IPSCONTENT;

foreach ( $member->recentBadges( NULL ) as $badge ):
$return .= <<<IPSCONTENT

                            <div class='i-flex i-gap_2 i-align-items_center'>
                                {$badge->html('i-flex_00 i-basis_40 ipsDimension', FALSE, TRUE)}
                                <div>
                                    
IPSCONTENT;

if ( ! empty( $badge->recognize ) AND $badge->recognize->contentWrapper()  ):
$return .= <<<IPSCONTENT

                                    <h4 class='i-font-weight_semi-bold'>
                                        
IPSCONTENT;

$sprintf = array($badge->_title, $badge->recognize->content()->url(), $badge->recognize->content()->indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badge_from_recognize', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

                                    </h4>
                                    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                    <h4 class='i-font-weight_semi-bold'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

if ( ! empty( $badge->awardDescription ) ):
$return .= <<<IPSCONTENT

                                        <p class='i-font-size_-2 i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $badge->awardDescription, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

if ( $badge->datetime < \IPS\Settings::i()->achievements_last_rebuilt ):
$return .= <<<IPSCONTENT

                                        
IPSCONTENT;

if ( \IPS\Settings::i()->achievements_last_rebuilt ):
$return .= <<<IPSCONTENT
<p class='i-font-size_-2 i-color_soft'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( \IPS\Settings::i()->achievements_last_rebuilt )->shortMonthAndFullYear()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badge_earned_date_while_rebuilding', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                    <p class='i-font-size_-2 i-color_soft'>
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $badge->datetime )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badge_earned_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                </div>
                            </div>
                        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                    </div>
                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                    <div class='i-padding_3 i-text-align_center i-color_soft'>
                        
IPSCONTENT;

if ( $member->member_id === \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

$sprintf = array(); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_self_none', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'achievements_member_none', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    </div>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</section>
		</div>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userContent( $member, $types, $currentAppModule, $currentType, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$baseUrl = \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=content", 'front', 'profile_content', $member->members_seo_name );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<div data-controller='core.front.profile.main' id='elProfileUserContent'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core", 'front' )->profileHeader( $member, true );
$return .= <<<IPSCONTENT

	<div data-role="profileContent">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsProfile ipsProfile--content">
			<aside class="ipsProfile__aside">
				<div class="ipsProfile__sticky-outer">
					<div class="ipsProfile__sticky-inner">
						<div class='ipsBox'>
							<div class="ipsSideMenu" id="user_content" data-ipsTabBar data-ipsTabBar-contentArea='#elUserContent' data-ipsTabBar-itemselector=".ipsSideMenu_item" data-ipsTabBar-activeClass="ipsSideMenu_itemActive" data-ipsSideMenu>
								<h3 class="ipsSideMenu__view">
									<a href="#user_content" data-action="openSideMenu"><i class="fa-solid fa-bars"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_content_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
								</h3>
								<div class="ipsSideMenu__menu">
									<ul class="ipsSideMenu__list">
										<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'change_section' => 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsSideMenu_item 
IPSCONTENT;

if ( !$currentType ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									</ul>
									
IPSCONTENT;

foreach ( $types as $app => $_types ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'type' => $key, 'change_section' => 1 ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

$val = "{$class::$title}_pl"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</div>
							</div>
						</div>
					</div>
				</div>
			</aside>
			<section class='ipsProfile__main ipsBox ipsPull' id='elUserContent'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->userContentSection( $member, $types, $currentAppModule, $currentType, $table );
$return .= <<<IPSCONTENT

			</section>
		</div>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function userContentSection( $member, $types, $currentAppModule, $currentType, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h2 class='i-padding_3 i-font-size_3 i-font-weight_600 i-border-bottom_1'>
IPSCONTENT;

if ( !$currentAppModule ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all_content_by_user', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $types[ $currentAppModule ][ $currentType ]::$title . '_pl' ), $member->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_by_user', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
{$table}
IPSCONTENT;

		return $return;
}

	function userContentStream( $member, $results, $pagination ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-baseurl="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=content&id={$member->member_id}&all_activity=1&page=1", null, "profile_content", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-resort="listResort" data-tableid="topics" data-controller="core.global.core.table">
	<div data-role="tableRows">
		
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--top">
				<div class="ipsButtonBar__pagination" data-role="tablePagination">
					{$pagination}
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<ol class='ipsStream ipsStream--profile-activity'>
			
IPSCONTENT;

foreach ( $results as $activity ):
$return .= <<<IPSCONTENT

				{$activity->html()}
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
		
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				<div class="ipsButtonBar__pagination" data-role="tablePagination">
					{$pagination}
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

	function userReputation( $member, $types, $currentAppModule, $currentType, $table, $reactions ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<div data-controller='core.front.profile.main'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core", 'front' )->profileHeader( $member, true );
$return .= <<<IPSCONTENT

	<div data-role="profileContent">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsProfile ipsProfile--reputation">
			<aside class="ipsProfile__aside">
				<div class="ipsProfile__sticky-outer">
					<div class="ipsProfile__sticky-inner">
						<div class="cProfileRepScore i-padding_2 
IPSCONTENT;

if ( $member->pp_reputation_points > 1 ):
$return .= <<<IPSCONTENT
cProfileRepScore--positive
IPSCONTENT;

elseif ( $member->pp_reputation_points < 0 ):
$return .= <<<IPSCONTENT
cProfileRepScore--negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
cProfileRepScore--neutral
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
							<h2 class='ipsMinorTitle i-color_inherit i-opacity_5'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
							<span class='cProfileRepScore__points'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->pp_reputation_points );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

if ( $member->reputation() ):
$return .= <<<IPSCONTENT

								<span class='cProfileRepScore__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->reputation(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $member->reputationImage() ):
$return .= <<<IPSCONTENT

								<div class='i-background_1 i-border-radius_box i-padding_2 i-text-align_center'>
									<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $member->reputationImage() )->url;
$return .= <<<IPSCONTENT
' alt=''>
							</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						
IPSCONTENT;

if ( \count( $reactions['given'] ) OR \count( $reactions['received'] ) ):
$return .= <<<IPSCONTENT

							<div class="ipsWidget i-padding_3">
								
IPSCONTENT;

if ( \count( $reactions['given'] ) ):
$return .= <<<IPSCONTENT

									<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_reactions_given', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
									<div class='ipsGrid ipsGrid--profile-reactions-given i-basis_80 i-margin-top_2 i-margin-bottom_4'>
										
IPSCONTENT;

foreach ( $reactions['given'] as $reaction ):
$return .= <<<IPSCONTENT

											<div class="i-flex i-align-items_center i-gap_1">
												<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction['reaction']->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' width="20" height="20" alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction['reaction']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy' data-ipsTooltip>
												<span class='i-color_soft i-font-weight_600'><span class="i-opacity_4">x</span> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
											</div>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


								
IPSCONTENT;

if ( \count( $reactions['received'] ) ):
$return .= <<<IPSCONTENT

									<h2 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_reactions_received', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
									<div class='ipsGrid ipsGrid--profile-reactions-received i-basis_80 i-margin-top_2'>
										
IPSCONTENT;

foreach ( $reactions['received'] as $reaction ):
$return .= <<<IPSCONTENT

											<div class="i-flex i-align-items_center i-gap_1">
												<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction['reaction']->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' width="20" height="20" alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction['reaction']->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy' data-ipsTooltip>
												<span class='i-color_soft i-font-weight_600'><span class="i-opacity_4">x</span> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
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

endif;
$return .= <<<IPSCONTENT
						
						<div class='ipsWidget i-padding_2'>
							<div class="ipsSideMenu" data-ipsTabBar data-ipsTabBar-contentArea='#elUserReputation' data-ipsTabBar-itemselector=".ipsSideMenu_item" data-ipsTabBar-activeClass="ipsSideMenu_itemActive" data-ipsSideMenu>
								<h3 class="ipsSideMenu__view">
									<a href="#user_reputation" data-action="openSideMenu"><i class="fa-solid fa-bars"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_content_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
								</h3>
								<div class="ipsSideMenu__menu">
									
IPSCONTENT;

foreach ( $types as $app => $_types ):
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

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=reputation&type={$key}&change_section=1", null, "profile_reputation", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
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

$val = "{$class::$title}_pl"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</div>
							</div>
						</div>
					</div>
				</div>
			</aside>
			<section class='ipsProfile__main ipsBox ipsPull'>
				<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<div id='elUserReputation'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->userReputationSection( $table );
$return .= <<<IPSCONTENT

				</div>
			</section>
		</div>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
IPSCONTENT;

		return $return;
}

	function userReputationRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$reaction = \IPS\Content\Reaction::load( $row->rep_reaction );
$return .= <<<IPSCONTENT

		<li class='ipsData__item'>		
			
IPSCONTENT;

if ( !\IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

				<div>
					<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Reaction", $reaction->_icon )->url;
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' width='20' height='20' data-ipsTooltip title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsData__icon i-align-self_center'>
				
IPSCONTENT;

if ( $row->rep_member == \IPS\Widget\Request::i()->id ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( \IPS\Request::i()->id ), 'mini' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $row->rep_member ), 'mini' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsData__main'>
				<div class='ipsData__title'>
					<span class=''>
						
IPSCONTENT;

if ( !\IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $row instanceof \IPS\Content\Comment or $row instanceof \IPS\Content\Review ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$item = $row->item();
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $row->rep_member != \IPS\Widget\Request::i()->id ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member_received )->link(), \IPS\Member::load( $row->rep_member )->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_comment_received', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $row->rep_member_received ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member )->link(), \IPS\Member::load( $row->rep_member_received )->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_comment_gave', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member )->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_comment_gave_no_recipient', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $row->rep_member != \IPS\Widget\Request::i()->id ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member_received )->link(), \IPS\Member::load( $row->rep_member )->link(), $row->indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_item_received', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $row->rep_member_received ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member )->link(), \IPS\Member::load( $row->rep_member_received )->link(), $row->indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_item_gave', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member )->link(), $row->indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_item_gave_no_recipient', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

endif;
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

							<strong>
								
IPSCONTENT;

if ( $row instanceof \IPS\Content\Comment or $row instanceof \IPS\Content\Review ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$item = $row->item();
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $row->rep_member_received ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member )->link(), $row->url(), $row->indefiniteArticle(), \IPS\Member::load( $row->rep_member_received )->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_like_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member )->link(), $row->url(), $row->indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_like_comment_no_recipient', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $row->rep_member_received ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member )->link(), $row->indefiniteArticle(), \IPS\Member::load( $row->rep_member_received )->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_like_item', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::load( $row->rep_member )->link(), $row->indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_like_item_no_recipient', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</strong>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</span>
					<span class='i-color_soft'>&nbsp;&nbsp;
IPSCONTENT;

$val = ( $row->rep_date instanceof \IPS\DateTime ) ? $row->rep_date : \IPS\DateTime::ts( $row->rep_date );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
				</div>
				
IPSCONTENT;

if ( $result = $row->truncated() ):
$return .= <<<IPSCONTENT

					<div class='ipsData__desc ipsRichText ipsTruncate_2'>
						{$result}
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

	function userReputationSection( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT


<i-data>
	<section class='ipsData ipsData--table ipsData--user-reputation'>
		{$table}
	</section>
</i-data>
IPSCONTENT;

		return $return;
}

	function userReputationTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl='
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
>
	
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

if ( $table->showAdvancedSearch AND ( (isset( $table->sortOptions ) and !empty( $table->sortOptions )) OR $table->advancedSearch ) OR !empty( $table->filters ) OR $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsButtonBar__pagination" data-role="tablePagination">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end'>
				<ul class='ipsDataFilters'>
					
IPSCONTENT;

if ( $table->showAdvancedSearch AND ( ( isset( $table->sortOptions ) and \count( $table->sortOptions ) > 1 ) OR $table->advancedSearch ) ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

if ( isset($table->sortOptions)  ):
$return .= <<<IPSCONTENT

							<a href="#elSortByMenu_menu" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsDataFilters__button' data-role="sortButton" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i></a>
							<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
									
IPSCONTENT;

$custom = TRUE;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

foreach ( $table->sortOptions as $k => $col ):
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item 
IPSCONTENT;

if ( $col === $table->sortBy ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$custom = FALSE;
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $col, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-sortDirection='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getSortDirection( $col ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $col, 'sortdirection' => $table->getSortDirection( $col ) ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$table->langPrefix}sort_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item 
IPSCONTENT;

if ( $custom ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-noSelect="true">
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
							
IPSCONTENT;

elseif ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsDataFilters__button'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

						<li>
							<a href="#elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class='ipsDataFilters__button' data-role="tableFilterMenu" id="elFilterByMenu_
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
								<li data-action="tableFilter" data-ipsMenuValue='' class='ipsMenu_item 
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => '', 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$table->langPrefix}all"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
								
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

									<li data-action="tableFilter" data-ipsMenuValue='
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

		<i-data>
			<ol class='ipsData ipsData--table ipsData--user-reputation 
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
		</i-data>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rows_in_table', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

	function userSolutions( $member, $types, $currentType, $table, $solutions ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<div data-controller='core.front.profile.main'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core", 'front' )->profileHeader( $member, true, $solutions );
$return .= <<<IPSCONTENT

	<div data-role="profileContent">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsProfile ipsProfile--solutions">
			<aside class="ipsProfile__aside">
				<div class="ipsProfile__sticky-outer">
					<div class="ipsProfile__sticky-inner">
						<div class='ipsWidget'>
							<div class='cProfileRepScore i-padding_2 cProfileRepScore--solutions'>
								<h2 class='ipsMinorTitle i-color_inherit i-opacity_5'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
								<span class='cProfileRepScore__points'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $solutions );
$return .= <<<IPSCONTENT
</span>
							</div>
						</div>
						
IPSCONTENT;

if ( \count( $types ) > 1 ):
$return .= <<<IPSCONTENT

							<div class='i-padding_2 ipsBox'>
								<div class="ipsSideMenu" data-ipsTabBar data-ipsTabBar-contentArea='#elUserSolutions' data-ipsTabBar-itemselector=".ipsSideMenu_item" data-ipsTabBar-activeClass="ipsSideMenu_itemActive" data-ipsSideMenu>
									<h3 class="ipsSideMenu__view">
										<a href="#user_solutions" data-action="openSideMenu"><i class="fa-solid fa-bars"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_content_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
									</h3>
									<div class="ipsSideMenu__menu">
										<ul class="ipsSideMenu__list">
										
IPSCONTENT;

foreach ( $types as $key => $type ):
$return .= <<<IPSCONTENT

											<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=solutions&type={$key}&change_section=1", null, "profile_solutions", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
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

$val = "{$type::$title}_pl"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									</div>
								</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</aside>
			<section class="ipsProfile__main ipsBox ipsPull">
				<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'profile_solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<div id='elUserSolutions'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->userSolutionsSection( $table );
$return .= <<<IPSCONTENT

				</div>
			</section>
		</div>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
	
IPSCONTENT;

		return $return;
}

	function userSolutionsRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		<li class='ipsData__item'>		
			<div class='ipsData__icon'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( \IPS\Request::i()->id ), 'mini' );
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsData__main'>
				<div class='ipsData__title'>
					<span>
						
IPSCONTENT;

$sprintf = array(\IPS\Member::load( \IPS\Request::i()->id )->name, $row->url(), \IPS\Member::loggedIn()->language()->addToStack( $row::$title . '_lc' ), $row->item()->url(), $row->item()->mapped('title')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solution_headline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</span>
					<span class='i-color_soft'>&nbsp;&nbsp;
IPSCONTENT;

$val = ( $row->solved_date instanceof \IPS\DateTime ) ? $row->solved_date : \IPS\DateTime::ts( $row->solved_date );$return .= $val->html();
$return .= <<<IPSCONTENT
</span>
				</div>
				
IPSCONTENT;

if ( $result = $row->truncated() ):
$return .= <<<IPSCONTENT

					<div class='ipsRichText ipsData__desc ipsTruncate_2'>
						{$result}
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

	function userSolutionsSection( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT


<i-data>
	<section class="ipsData ipsData--table ipsData--user-solutions-section">
		{$table}
	</section>
</i-data>
IPSCONTENT;

		return $return;
}

	function userSolutionsTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl='
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
>
	
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

if ( $table->showAdvancedSearch AND ( (isset( $table->sortOptions ) and !empty( $table->sortOptions )) OR $table->advancedSearch ) OR !empty( $table->filters ) OR $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsButtonBar__pagination" data-role="tablePagination">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end'>
				<ul class='ipsDataFilters'>
					
IPSCONTENT;

if ( $table->showAdvancedSearch AND ( ( isset( $table->sortOptions ) and \count( $table->sortOptions ) > 1 ) OR $table->advancedSearch ) ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

if ( isset($table->sortOptions)  ):
$return .= <<<IPSCONTENT

							<a href="#elSortByMenu_menu" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button" data-role="sortButton" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i></a>
							<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
									
IPSCONTENT;

$custom = TRUE;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

foreach ( $table->sortOptions as $k => $col ):
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item 
IPSCONTENT;

if ( $col === $table->sortBy ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$custom = FALSE;
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $col, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-sortDirection='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getSortDirection( $col ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $col, 'sortdirection' => $table->getSortDirection( $col ) ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$table->langPrefix}sort_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item 
IPSCONTENT;

if ( $custom ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-noSelect="true">
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
							
IPSCONTENT;

elseif ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class="ipsDataFilters__button">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

						<li>
							<a href="#elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" data-role="tableFilterMenu" id="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i></a>
							<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
								<li data-action="tableFilter" data-ipsMenuValue='' class='ipsMenu_item 
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => '', 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$table->langPrefix}all"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
								
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

									<li data-action="tableFilter" data-ipsMenuValue='
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

		<i-data>
			<ol class="ipsData ipsData--table ipsData--user-solutions-table 
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rows_in_table', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
}}