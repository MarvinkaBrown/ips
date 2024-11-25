<?php
namespace IPS\Theme;
class class_core_front_clubs extends \IPS\Theme\Template
{	function clubCard( $club, $approvalQueue=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$coverPhoto = $club->coverPhoto( FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

$memberStatus = $club->memberStatus( \IPS\Member::loggedIn() );
$return .= <<<IPSCONTENT


<li class="ipsData__item" 
IPSCONTENT;

if ( \IPS\Settings::i()->clubs_require_approval and !$club->approved ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<div class="ipsData__image">
		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsCoverPhoto' data-controller='core.global.core.coverPhoto' data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-coverOffset='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style='--offset:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			
IPSCONTENT;

$cfObject = $coverPhoto->object;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

				<div class='ipsCoverPhoto__container'>
					<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCoverPhoto__image' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
				</div>
			
IPSCONTENT;

elseif ( ! empty( $cfObject::$coverPhotoDefault ) ):
$return .= <<<IPSCONTENT

				<div class='ipsCoverPhoto__container'>
					<div class="ipsFallbackImage" style="--i-empty-image-random--ba-co: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->object->coverPhotoBackgroundColor(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"></div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</a>
	</div>
	<div class="ipsData__content">
		<div class="ipsData__main">
			<div class="i-flex i-align-items_center i-gap_2">
				<div class="i-flex_00 i-basis_40">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'fluid' );
$return .= <<<IPSCONTENT
</div>
				<div class="i-flex_11">
					<div class='ipsData__title'>
						<h2>
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
						</h2>
						<div class="ipsBadges">
							
IPSCONTENT;

if ( !$approvalQueue and \IPS\Settings::i()->clubs_require_approval and !$club->approved ):
$return .= <<<IPSCONTENT

								<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unapproved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
							
IPSCONTENT;

elseif ( $club->featured ):
$return .= <<<IPSCONTENT

								<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-star'></i></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \in_array( $memberStatus, array( $club::STATUS_MEMBER, $club::STATUS_MODERATOR, $club::STATUS_LEADER ) ) ):
$return .= <<<IPSCONTENT

								<span class='ipsBadge ipsBadge--positive ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-check'></i></span>
							
IPSCONTENT;

elseif ( \in_array( $memberStatus, array( $club::STATUS_EXPIRED, $club::STATUS_EXPIRED_MODERATOR ) ) ):
$return .= <<<IPSCONTENT

								<span class='ipsBadge ipsBadge--intermediary ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-triangle-exclamation'></i></span>
							
IPSCONTENT;

elseif ( $memberStatus == $club::STATUS_INVITED or $memberStatus == $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT

								<span class='ipsBadge ipsBadge--neutral ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_invited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-envelope'></i></span>
							
IPSCONTENT;

elseif ( $memberStatus == $club::STATUS_REQUESTED ):
$return .= <<<IPSCONTENT

								<span class='ipsBadge ipsBadge--intermediary ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requested_desc_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-regular fa-clock'></i></span>
							
IPSCONTENT;

elseif ( $memberStatus == $club::STATUS_WAITING_PAYMENT ):
$return .= <<<IPSCONTENT

								<span class='ipsBadge ipsBadge--neutral ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_awaiting_payment_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-check'></i></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
					<ul class='ipsList ipsList--sep i-color_soft i-row-gap_0 i-font-size_-1 i-font-weight_500'>
						
IPSCONTENT;

if ( $club->type != "open" ):
$return .= <<<IPSCONTENT

							<li>
IPSCONTENT;

$val = "club_{$club->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $club->last_activity ):
$return .= <<<IPSCONTENT

							<li>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_last_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $club->last_activity instanceof \IPS\DateTime ) ? $club->last_activity : \IPS\DateTime::ts( $club->last_activity );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			</div>
			
IPSCONTENT;

if ( $club->about ):
$return .= <<<IPSCONTENT

				<div class='ipsData__desc'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->about, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ( \in_array( $club->type, array( $club::TYPE_OPEN, $club::TYPE_CLOSED ) ) or \in_array( $memberStatus, array( $club::STATUS_MEMBER, $club::STATUS_MODERATOR, $club::STATUS_LEADER, $club::STATUS_INVITED, $club::STATUS_INVITED_BYPASSING_PAYMENT, $club::STATUS_REQUESTED, $club::STATUS_EXPIRED, $club::STATUS_EXPIRED_MODERATOR ) ) ) and $priceBlurb = $club->priceBlurb() ):
$return .= <<<IPSCONTENT

				<div class='i-margin-top_1'>
					<span class="cClubPrice">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $priceBlurb, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

if ( $club->isPaid() and $club->joiningFee() and \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT

						<span class='cNexusPrice_tax i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
		
IPSCONTENT;

if ( $club->type != $club::TYPE_PUBLIC ):
$return .= <<<IPSCONTENT

			<div class='i-flex i-align-items_center i-gap_2 i-flex-wrap_wrap i-padding-top_3 i-margin-top_auto'>
				
IPSCONTENT;

if ( $club->canViewMembers()  ):
$return .= <<<IPSCONTENT

					<div class="i-flex_11">
						<ul class='ipsCaterpillar i-basis_30'>
							
IPSCONTENT;

foreach ( $club->randomTenMembers() as $member ):
$return .= <<<IPSCONTENT

								<li class='ipsCaterpillar__item'>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhotoFromData( $member['core_members']['member_id'], $member['core_members']['name'], $member['core_members']['members_seo_name'], \IPS\Member::photoUrl( $member['core_members'] ), 'fluid' );
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class="i-font-size_-1 i-font-weight_500 i-color_soft">
IPSCONTENT;

$pluralize = array( $club->members ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
			</div>
			
IPSCONTENT;

if ( $club->canJoin() ):
$return .= <<<IPSCONTENT

				<div class="i-margin-top_3">
					<a class="ipsButton ipsButton--wide ipsButton--secondary ipsButton--small" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $club->isPaid() and $memberStatus !== $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT
data-confirm data-confirmIcon="info" data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_membership_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubmessage="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->memberFeeMessage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class="fa-solid fa-user-plus"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function clubClientArea( $club ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-background_3 i-padding_3">
	<div class="ipsBox">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubCard( $club );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function clubIcon( $club, $size='tiny', $classes='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsUserPhoto ipsUserPhoto--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $size, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 cClubIcon 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( $club->profile_photo ):
$return .= <<<IPSCONTENT

		<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Clubs", $club->profile_photo )->url;
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "default_club.png", "core", 'global', false );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</a>
IPSCONTENT;

		return $return;
}

	function clubLocationBox( $club, $location ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsWidget ipsWidget--vertical">
	<h2 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='ipsWidget__content ipsWidget__padding i-text-align_center'>
		{$location->map()->render( 500, 300 )}
		<div class='ipsRichText i-text-align_center i-padding_2 i-link-color_inherit'>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $location, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function clubMemberBox( $club ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsWidget ipsWidget--vertical">
	<h2 class='ipsWidget__header'>
IPSCONTENT;

$pluralize = array( $club->members ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='ipsWidget__content'>
		<i-data>
			<ol class="ipsData ipsData--table ipsData--club-members">
				
IPSCONTENT;

$membersToShow = $club->members( array('member', 'moderator', 'leader'), 15, "IF(core_clubs_memberships.status='leader' OR core_clubs_memberships.status='moderator',0,1), core_clubs_memberships.joined ASC", 1);
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $membersToShow as $memberData ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$member = \IPS\Member::load( $memberData['core_members']['member_id'] );
$return .= <<<IPSCONTENT

				<li class='ipsData__item'>
					<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'fluid' );
$return .= <<<IPSCONTENT
</div>
					<div class='ipsData__main'>
						<div class='i-flex i-justify-content_space-between i-align-items_center i-flex-wrap_wrap'>
							<h5 class='ipsData__title'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLinkFromData( $member->member_id, $member->name, $member->seo_name, $member->member_group_id );
$return .= <<<IPSCONTENT

							</h5>
							
IPSCONTENT;

if ( $memberData['core_clubs_memberships']['status'] == \IPS\Member\Club::STATUS_LEADER && ( $club->owner and $club->owner->member_id == $member->member_id )  ):
$return .= <<<IPSCONTENT

								<span class="ipsBadge ipsBadge--positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_owner', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

elseif ( $memberData['core_clubs_memberships']['status'] == \IPS\Member\Club::STATUS_LEADER ):
$return .= <<<IPSCONTENT

								<span class="ipsBadge ipsBadge--positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_leader', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

elseif ( \in_array( $memberData['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_MODERATOR, \IPS\Member\Club::STATUS_EXPIRED_MODERATOR ) ) ):
$return .= <<<IPSCONTENT

								<span class="ipsBadge">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_moderator', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<p class='ipsData__meta'>
							
IPSCONTENT;

$sprintf = array(\IPS\DateTime::ts( $memberData['core_clubs_memberships']['joined'] )->relative( \IPS\DateTime::RELATIVE_FORMAT_LOWER )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_joined', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

						</p>
					</div>
				</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ol>
		</i-data>
		
IPSCONTENT;

if ( $club->members > \count( $membersToShow	) ):
$return .= <<<IPSCONTENT

			<div class='i-text-align_center i-font-weight_500 i-color_soft i-link-color_inherit'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'members'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_see_all_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<i class='fa-solid fa-angle-right i-margin-start_icon'></i></a>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function clubRow( $club ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$memberStatus = $club->memberStatus( \IPS\Member::loggedIn() );
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
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<div class='ipsData__icon'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'tiny' );
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsData__content'>
		<div class='ipsData__main'>
			<div class='ipsData__title'>
				<div class='ipsBadges'>
					
IPSCONTENT;

if ( \IPS\Settings::i()->clubs_require_approval and !$club->approved ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unapproved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $club->featured ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-star'></i></span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \in_array( $memberStatus, array( $club::STATUS_MEMBER, $club::STATUS_MODERATOR, $club::STATUS_LEADER ) ) ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--positive ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-check'></i></span>
						
IPSCONTENT;

elseif ( \in_array( $memberStatus, array( $club::STATUS_EXPIRED, $club::STATUS_EXPIRED_MODERATOR ) ) ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--intermediary ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_expired', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-triangle-exclamation'></i></span>
						
IPSCONTENT;

elseif ( $memberStatus == $club::STATUS_INVITED or $memberStatus == $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--neutral ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_invited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-envelope'></i></span>
						
IPSCONTENT;

elseif ( $memberStatus == $club::STATUS_WAITING_PAYMENT ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--neutral ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_awaiting_payment_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-check'></i></span>
						
IPSCONTENT;

elseif ( $memberStatus == $club::STATUS_REQUESTED ):
$return .= <<<IPSCONTENT

							<span class='ipsBadge ipsBadge--intermediary ipsBadge--icon' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requested_desc_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-regular fa-clock'></i></span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<h4>
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
				</h4>
			</div>
			<div class='ipsData__desc ipsTruncate_4'>
				
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
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function clubRules( $club ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( ! \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs_header != 'sidebar' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<h1 class='ipsTitle ipsTitle--h2 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_rules', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div id='elClubContainer'>
	
IPSCONTENT;

if ( ! \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
<div class='ipsBox'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='i-padding_3'>
			<div class='ipsRichText'>{$club->rules}</div>
		</div>
	
IPSCONTENT;

if ( ! \IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function coverPhotoOverlay( $club, $position='full' ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$memberStatus = $club->memberStatus( \IPS\Member::loggedIn() );
$return .= <<<IPSCONTENT


<div class="ipsCoverPhotoMeta">
	
IPSCONTENT;

if ( $position == 'full' ):
$return .= <<<IPSCONTENT

		<div class='ipsCoverPhoto__avatar'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'fluid', '' );
$return .= <<<IPSCONTENT

			<!--
			
IPSCONTENT;

if ( $club->isLeader() ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( 'do', 'editPhoto' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton' data-action='editPhoto' data-ipsDialog data-ipsDialog-forceReload='true' data-ipsDialog-modal='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_profile_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_profile_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip><i class='fa-regular fa-image'></i></a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			-->
		</div>
		<div class='ipsCoverPhoto__titles'>
			<div class='ipsCoverPhoto__title'>
				<h2>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				</h2>
				
IPSCONTENT;

if ( (\IPS\Settings::i()->clubs_require_approval and !$club->approved) or ($club->featured) ):
$return .= <<<IPSCONTENT

					<div class='ipsBadges'>
						
IPSCONTENT;

if ( \IPS\Settings::i()->clubs_require_approval and !$club->approved ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unapproved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
						
IPSCONTENT;

elseif ( $club->featured ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-star'></i></span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsCoverPhoto__subTitle'>
				<ul class='ipsList ipsList--sep'>
					<li>
IPSCONTENT;

$val = "club_{$club->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

if ( $club->type !== $club::TYPE_PUBLIC ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$pluralize = array( $club->members ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $club->isLeader() and $club->type === $club::TYPE_CLOSED and $pendingMembers = $club->members( array( $club::STATUS_REQUESTED ), NULL, NULL, 4 ) ):
$return .= <<<IPSCONTENT

						<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'members')->setQueryString('filter', 'requested')->setQueryString('filter', 'requested'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_issue'><i class='fa-solid fa-circle-info'></i> &nbsp;
IPSCONTENT;

$pluralize = array( $pendingMembers ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_pending_members', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $club->rules ):
$return .= <<<IPSCONTENT

						<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'rules'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_rules', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_rules', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( ( \in_array( $club->type, array( $club::TYPE_OPEN, $club::TYPE_CLOSED ) ) or ( $memberStatus = $club->memberStatus( \IPS\Member::loggedIn() ) and \in_array( $memberStatus, array( $club::STATUS_MEMBER, $club::STATUS_MODERATOR, $club::STATUS_LEADER, $club::STATUS_INVITED, $club::STATUS_INVITED_BYPASSING_PAYMENT, $club::STATUS_REQUESTED, $club::STATUS_EXPIRED, $club::STATUS_EXPIRED_MODERATOR ) ) ) ) and $priceBlurb = $club->priceBlurb() ):
$return .= <<<IPSCONTENT

						<li>
							<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $priceBlurb, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

if ( $club->isPaid() and $club->joiningFee() and \IPS\Member::loggedIn()->language()->checkKeyExists('nexus_tax_explain_val') ):
$return .= <<<IPSCONTENT

								<span class="i-font-size_-2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'nexus_tax_explain_val', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
	
		<div class='ipsCoverPhoto__buttons cClubControls__moderate'>
			<a href="#" data-action="saveClubmenu" class="ipsHide ipsButton ipsButton--small ipsButton--positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			<ul class='ipsButtons'>
				
IPSCONTENT;

if ( $club->isLeader() || ( $club->owner and $club->owner->member_id == \IPS\Member::loggedIn()->member_id ) or \IPS\Member::loggedIn()->modPermission('can_access_all_clubs') ):
$return .= <<<IPSCONTENT

					<li>
						<a class="ipsButton ipsButton--inherit" href="#" id='elClubFeatures' data-ipsMenu><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_manage', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='ipsMenuCaret'></i></a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $club->type !== \IPS\Member\Club::TYPE_PUBLIC ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $memberStatus === \IPS\Member\Club::STATUS_BANNED ):
$return .= <<<IPSCONTENT

						<li>
							<span class="ipsButton ipsButton--text" aria-disabled="true" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_banned_desc_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-xmark'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_banned_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></span>
						</li>
					
IPSCONTENT;

elseif ( \in_array( $memberStatus, array( \IPS\Member\Club::STATUS_MEMBER, \IPS\Member\Club::STATUS_MODERATOR, \IPS\Member\Club::STATUS_LEADER, \IPS\Member\Club::STATUS_EXPIRED, \IPS\Member\Club::STATUS_EXPIRED_MODERATOR, \IPS\Member\Club::STATUS_WAITING_PAYMENT ) ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $memberStatus === \IPS\Member\Club::STATUS_WAITING_PAYMENT ):
$return .= <<<IPSCONTENT

							<li>
								<a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_pay_membership_fee', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \in_array( $memberStatus, array( \IPS\Member\Club::STATUS_EXPIRED, \IPS\Member\Club::STATUS_EXPIRED_MODERATOR ) ) ):
$return .= <<<IPSCONTENT

							<li>
								<a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'renew')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_renew_membership', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !$club->owner or $club->owner->member_id != \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

							<li>
								<a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'leave')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm 
IPSCONTENT;

if ( $club->isPaid() ):
$return .= <<<IPSCONTENT
data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_leave_paid_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_leave', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( $club->canJoin() ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

if ( $club->isPaid() ):
$return .= <<<IPSCONTENT

								<a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf()->addRef( \IPS\Request::i()->url() ) , ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $memberStatus !== $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT
data-confirm data-confirmIcon="info" data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_membership_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubmessage="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->memberFeeMessage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf()->addRef( \IPS\Request::i()->url() ) , ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

elseif ( !$club->canRead() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $memberStatus === \IPS\Member\Club::STATUS_REQUESTED ):
$return .= <<<IPSCONTENT

							<li>
								<a class="ipsButton ipsButton--text" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'cancelJoin')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-xmark'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_cancel_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
							</li>
						
IPSCONTENT;

elseif ( $memberStatus === \IPS\Member\Club::STATUS_DECLINED ):
$return .= <<<IPSCONTENT

							<li>
								<span class="ipsButton ipsButton--text" aria-disabled="true" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_denied_desc_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-xmark'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_denied_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
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

		<!-- v5 todo: Remove this? -->
		<div class='ipsCoverPhoto__avatar' hidden>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'fluid' );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function create( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->pageHeader( \IPS\Member::loggedIn()->language()->get('create_club') );
$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_3'>
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function directory( $featuredClubs, $allClubs, $pagination, $baseUrl, $sortOption, $myClubsActivity, $mapMarkers=NULL, $view='grid', $mineOnly=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<header class="ipsPageHeader">
	<div class="ipsPageHeader__row">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/clubs/directory", "header:before", [ $featuredClubs,$allClubs,$pagination,$baseUrl,$sortOption,$myClubsActivity,$mapMarkers,$view,$mineOnly ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/clubs/directory", "header:inside-start", [ $featuredClubs,$allClubs,$pagination,$baseUrl,$sortOption,$myClubsActivity,$mapMarkers,$view,$mineOnly ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/clubs/directory", "title:before", [ $featuredClubs,$allClubs,$pagination,$baseUrl,$sortOption,$myClubsActivity,$mapMarkers,$view,$mineOnly ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/clubs/directory", "title:inside-start", [ $featuredClubs,$allClubs,$pagination,$baseUrl,$sortOption,$myClubsActivity,$mapMarkers,$view,$mineOnly ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_directory', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/clubs/directory", "title:inside-end", [ $featuredClubs,$allClubs,$pagination,$baseUrl,$sortOption,$myClubsActivity,$mapMarkers,$view,$mineOnly ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/clubs/directory", "title:after", [ $featuredClubs,$allClubs,$pagination,$baseUrl,$sortOption,$myClubsActivity,$mapMarkers,$view,$mineOnly ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/clubs/directory", "header:inside-end", [ $featuredClubs,$allClubs,$pagination,$baseUrl,$sortOption,$myClubsActivity,$mapMarkers,$view,$mineOnly ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/clubs/directory", "header:after", [ $featuredClubs,$allClubs,$pagination,$baseUrl,$sortOption,$myClubsActivity,$mapMarkers,$view,$mineOnly ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->clubs_allow_view_change AND \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<ul class="ipsButtonGroup">
				<li>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=clubs&controller=directory&view=grid" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "clubs_list", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( $view == 'grid' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_view_grid', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow">
						<i class="fa-solid fa-table-cells-large"></i>
					</a>
				</li>
				<li>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=clubs&controller=directory&view=list" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "clubs_list", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( $view == 'list' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_view_list', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow">
						<i class="fa-solid fa-align-justify"></i>
					</a>
				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</header>


IPSCONTENT;

if ( \count( $featuredClubs ) ):
$return .= <<<IPSCONTENT

	<div class="ipsBox ipsPull">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured_clubs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			
IPSCONTENT;

if ( $view == 'grid' ):
$return .= <<<IPSCONTENT

				<ul class="ipsData ipsData--grid ipsData--clubs-featured">
					
IPSCONTENT;

foreach ( $featuredClubs as $club ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubCard( $club );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<ol class="ipsData ipsData--table ipsData--clubs-featured">
					
IPSCONTENT;

foreach ( $featuredClubs as $club ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubRow( $club );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</i-data>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $mapMarkers !== NULL && \IPS\Settings::i()->clubs_locations ):
$return .= <<<IPSCONTENT

	<div class="ipsBox ipsPull">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_find_by_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="i-padding_2">
			<div data-ipsmap data-ipsmap-markers="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( $mapMarkers ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsmap-contenturl="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=clubs&controller=view&do=mapPopup&id=", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsMap ipsMap--small ipsJS_show"></div>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class="ipsBox ipsPull">
	<h2 class="ipsBox__header">
IPSCONTENT;

if ( $mineOnly ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_clubs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all_clubs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

if ( \count( $allClubs ) ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			<div class="ipsButtonBar__pagination">{$pagination}</div>
			<div class="ipsButtonBar__end">
				<ul class="ipsDataFilters">
					<li>
						<a href="#elSortByMenu_menu" id="elSortByMenu" class="ipsDataFilters__button" data-ipsmenu data-ipsmenu-activeclass="ipsDataFilters__button--active" data-ipsmenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
					</li>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id or \IPS\Member\Club\CustomField::areFilterableFields() or ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->clubs_paid_on ) ):
$return .= <<<IPSCONTENT

						<li>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( 'do', 'filters' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>	
			</div>
		</div>
		<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elSortByMenu_menu">
			
IPSCONTENT;

foreach ( array('last_activity', 'members', 'content', 'created', 'name') as $k ):
$return .= <<<IPSCONTENT

				<li class="ipsMenu_item 
IPSCONTENT;

if ( $k == $sortOption ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsmenuvalue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=clubs&controller=directory&sort={$k}", null, "clubs_list", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "clubs_sort_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
		<i-data>
			
IPSCONTENT;

if ( $view == 'grid' ):
$return .= <<<IPSCONTENT

				<ul class="ipsData ipsData--grid ipsData--browse-clubs">
					
IPSCONTENT;

foreach ( $allClubs as $club ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubCard( $club );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<ul class="ipsData ipsData--table ipsData--browse-clubs">
					
IPSCONTENT;

foreach ( $allClubs as $club ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubRow( $club );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</i-data>
		
IPSCONTENT;

if ( trim( $pagination ) ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				<div class="ipsButtonBar__pagination">{$pagination}</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class="ipsEmptyMessage">
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all_clubs_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function embedClub( $club ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!-- v5 todo: I can potentially mimic the "browse clubs grid" UI here -->


IPSCONTENT;

$coverPhoto = $club->coverPhoto( FALSE );
$return .= <<<IPSCONTENT

<div class='ipsRichEmbed cClubEmbed'>
	<div class='ipsRichEmbed_header'>
		<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $club->owner, 'tiny' );
$return .= <<<IPSCONTENT

			<div class='ipsPhotoPanel__text'>
				<p class='ipsPhotoPanel__primary ipsTruncate_1'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array($club->owner->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_embed_created_line', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
				</p>
				<p class='ipsPhotoPanel__secondary ipsTruncate_1'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = ( $club->created instanceof \IPS\DateTime ) ? $club->created : \IPS\DateTime::ts( $club->created );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
				</p>
			</div>
		</div>
		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_openItem'><i class='fa-solid fa-arrow-up-right-from-square'></i></a>
	</div>
	<div class='cClubEmbedHeader
IPSCONTENT;

if ( !$coverPhoto->file ):
$return .= <<<IPSCONTENT
 cClubEmbedHeaderNoPhoto
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

			<span style='background-image: url( "
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( '(', ')' ), array( '\(', '\)' ), $coverPhoto->file->url ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" )'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'medium' );
$return .= <<<IPSCONTENT

			</span>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<span>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'medium' );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='i-padding_2'>
		<p class='ipsRichEmbed_itemTitle ipsTruncate_1 i-link-color_inherit'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
		</p>
		<p class='i-color_soft'>
			
IPSCONTENT;

$val = "club_{$club->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
&nbsp;&middot;&nbsp;
IPSCONTENT;

$pluralize = array( $club->members ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $club->last_activity ):
$return .= <<<IPSCONTENT
&nbsp;&middot;&nbsp;
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_last_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $club->last_activity instanceof \IPS\DateTime ) ? $club->last_activity : \IPS\DateTime::ts( $club->last_activity );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</p>
		
IPSCONTENT;

if ( $desc = $club->about ):
$return .= <<<IPSCONTENT

			<div class='ipsRichEmbed__snippet'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $desc, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $club->type != $club::TYPE_PUBLIC ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $club->canJoin() ):
$return .= <<<IPSCONTENT

				<hr class='ipsHr ipsHr--small'>
				<a class="ipsButton ipsButton--small ipsButton--primary ipsButton--wide" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $club->isPaid() and $club->memberStatus( \IPS\Member::loggedIn() ) !== $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT
data-confirm data-confirmIcon="info" data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_membership_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubmessage="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->memberFeeMessage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $club->canViewMembers()  ):
$return .= <<<IPSCONTENT

				<hr class='ipsHr ipsHr--small'>
				<ul class='ipsCaterpillar'>
					
IPSCONTENT;

foreach ( $club->randomTenMembers() as $member ):
$return .= <<<IPSCONTENT

						<li class='ipsCaterpillar__item'>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhotoFromData( $member['core_members']['member_id'], $member['core_members']['name'], $member['core_members']['members_seo_name'], \IPS\Member::photoUrl( $member['core_members'] ), 'fluid' );
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

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function header( $club, $container=NULL, $position='full', $page=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/clubs.css', 'core', 'front' ) );
$return .= <<<IPSCONTENT



IPSCONTENT;

$memberStatus = $club->memberStatus( \IPS\Member::loggedIn() );
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $position == 'full' ):
$return .= <<<IPSCONTENT

	<!-- FULL CLUB HEADER -->
	<div id="elClubHeader" class='ipsPageHeader ipsBox cClubHeader cClubHeader--main i-margin-bottom_block'>
		{$club->coverPhoto(TRUE, 'full')}
		<div id='elClubControls'>
			<div class='cClubControls__tabs'>
				<i-tabs class='ipsTabs ipsTabs--stretch' id='ipsTabs_club' data-ipsTabBar data-ipsTabBar-disableNav data-controller="core.front.clubs.navbar" data-clubID="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					<div role='tablist' data-club-menu>
						
IPSCONTENT;

foreach ( $club->tabs( $container ) as $id => $tab ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['href'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_club_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( isset( $tab['isActive'] ) AND $tab['isActive'] ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-tab="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
				<div id='elClubTabs_tab_content' class='ipsTabs__panels ipsHide'>
					
IPSCONTENT;

foreach ( $club->tabs( $container ) as $id => $tab ):
$return .= <<<IPSCONTENT

						<div id="ipsTabs_club_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class="ipsTabs__panel" role="tabpanel" hidden></div>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
	</div>


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<!-- SIDEBAR CLUB HEADER -->
	<div id="elClubHeader_small" class='cClubHeader cClubHeader--sidebar ipsWidget ipsWidget--vertical'>
		{$club->coverPhoto(TRUE, 'sidebar')}
		<div class='ipsWidget__padding' data-controller="core.front.clubs.navbar" data-clubID="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">

			<div class='i-flex i-align-items_center i-gap_2'>
				<div class='i-flex_00 i-basis_50'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'fluid' );
$return .= <<<IPSCONTENT

				</div>
				<div class='i-flex_11'>
					<p class='i-font-weight_600 i-font-size_-1 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_currently_viewing', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					<h1 class='ipsTitle ipsTitle--h3'>
						
IPSCONTENT;

if ( \IPS\Settings::i()->clubs_require_approval and !$club->approved ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unapproved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-eye-slash'></i></span>
						
IPSCONTENT;

elseif ( $club->featured ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-star'></i></span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					</h1>
				</div>
			</div>

			<ul class='ipsList ipsList--sep i-justify-content_center i-font-weight_600 i-color_soft i-margin-top_2 i-padding-top_2 i-border-top_2'>
				<li>
IPSCONTENT;

$val = "club_{$club->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

if ( $club->type !== $club::TYPE_PUBLIC ):
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$pluralize = array( $club->members ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $club->isLeader() and $club->type === $club::TYPE_CLOSED and $pendingMembers = $club->members( array( $club::STATUS_REQUESTED ), NULL, NULL, 4 ) ):
$return .= <<<IPSCONTENT

					<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'members')->setQueryString('filter', 'requested')->setQueryString('filter', 'requested'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_issue'><i class='fa-solid fa-circle-info i-margin-end_icon'></i>
IPSCONTENT;

$pluralize = array( $pendingMembers ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_pending_members', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
			

			
IPSCONTENT;

if ( $club->type !== \IPS\Member\Club::TYPE_PUBLIC and $club->type !== $club::TYPE_READONLY and $club->canJoin() and \IPS\Widget\Request::i()->do != 'rules' ):
$return .= <<<IPSCONTENT

				<a class="ipsButton ipsButton--primary i-width_100p i-margin-top_2" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf()->addRef( \IPS\Request::i()->url() ) , ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $club->isPaid() and $memberStatus !== $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT
data-confirm data-confirmIcon="info" data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_membership_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubmessage="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->memberFeeMessage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( $club->isLeader() || ( $club->owner and $club->owner->member_id == \IPS\Member::loggedIn()->member_id ) or \IPS\Member::loggedIn()->modPermission('can_access_all_clubs') ):
$return .= <<<IPSCONTENT

				<a class="ipsButton ipsButton--inherit i-width_100p i-margin-top_2" href="#" id='elClubFeatures' data-ipsMenu><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_manage', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<a href="#" data-action="saveClubmenu" class="ipsButton ipsButton--positive i-width_100p ipsHide i-margin-top_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>

			<div class='ipsSideMenu i-margin-top_2'>
				<ul class='ipsSideMenu__list' data-club-menu>
					
IPSCONTENT;

foreach ( $club->tabs( $container ) as $id => $tab ):
$return .= <<<IPSCONTENT

						<li data-tab="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['href'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsSideMenu_item 
IPSCONTENT;

if ( isset( $tab['isActive'] ) AND $tab['isActive'] ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" role="tab"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</div>
			
			<div class='ipsResponsive_showPhone i-margin-top_2'>
				<a href='#elBrowseClub_menu' class='ipsButton ipsButton--inherit i-width_100p' id='elBrowseClub' data-ipsMenu><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'browse_club', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></a>
				
				<ul id='elBrowseClub_menu' class='ipsMenu ipsMenu_auto ipsHide'>
					<li class='ipsMenu_title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'browse_club', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

foreach ( $club->tabs( $container ) as $id => $tab ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['href'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tab['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
			</div>

			<ul class='ipsButtons i-margin-top_2'>
				
IPSCONTENT;

if ( $club->type !== $club::TYPE_PUBLIC and \in_array( $memberStatus, array( \IPS\Member\Club::STATUS_MEMBER, \IPS\Member\Club::STATUS_MODERATOR, \IPS\Member\Club::STATUS_LEADER, \IPS\Member\Club::STATUS_EXPIRED,  \IPS\Member\Club::STATUS_EXPIRED_MODERATOR ) ) and ( !$club->owner or $club->owner->member_id != \IPS\Member::loggedIn()->member_id ) ):
$return .= <<<IPSCONTENT

					<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'leave')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--text' data-confirm 
IPSCONTENT;

if ( $club->isPaid() ):
$return .= <<<IPSCONTENT
data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_leave_paid_warning', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_leave', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

elseif ( !$club->canRead() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $memberStatus === \IPS\Member\Club::STATUS_REQUESTED ):
$return .= <<<IPSCONTENT

						<li>
							<span class="ipsButton ipsButton--text" aria-disabled="true" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requested_desc_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-regular fa-clock'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requested_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</li>
					
IPSCONTENT;

elseif ( $memberStatus === \IPS\Member\Club::STATUS_DECLINED ):
$return .= <<<IPSCONTENT

						<li>
							<span class="ipsButton ipsButton--text" aria-disabled="true" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_denied_desc_short', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa-solid fa-xmark'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_denied_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
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

if ( $club->isLeader() || ( $club->owner and $club->owner->member_id == \IPS\Member::loggedIn()->member_id ) or \IPS\Member::loggedIn()->modPermission('can_access_all_clubs') ):
$return .= <<<IPSCONTENT

	<ul class='ipsMenu ipsMenu_auto ipsHide' id='elClubFeatures_menu'>
		
IPSCONTENT;

if ( !\IPS\Settings::i()->clubs_require_approval or $club->approved ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $club->isLeader() and $nodeTypes = \IPS\Member\Club::availableNodeTypes( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

				<li class='ipsMenu_title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_create_node', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

foreach ( $nodeTypes as $nodeType ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'nodeForm', 'type' => $nodeType ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_create_node', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = "{$nodeType::clubFrontTitle()}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ( $club->owner and $club->owner->member_id == \IPS\Member::loggedIn()->member_id ) or \IPS\Member::loggedIn()->modPermission('can_access_all_clubs') or \IPS\Member::loggedIn()->modPermission('can_manage_featured_clubs') ):
$return .= <<<IPSCONTENT

				<li class='ipsMenu_sep'><hr class='ipsHr'></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $club->isLeader() ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( 'do', 'editPhoto' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='editPhoto' data-ipsDialog data-ipsDialog-forceReload='true' data-ipsDialog-modal='true' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_profile_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_profile_photo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( ( $club->owner and $club->owner->member_id == \IPS\Member::loggedIn()->member_id ) or \IPS\Member::loggedIn()->modPermission('can_access_all_clubs') ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item'>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'edit'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_edit_settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_edit_settings', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_manage_featured_clubs') ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item'>
				
IPSCONTENT;

if ( $club->featured ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'unfeature')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unfeature', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'feature')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_feature', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

if ( $club->canInvite() ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item'>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( 'do', 'invite' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_invite_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_invite_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $club->canManageNavigation() AND \count( $club->tabs( $container ) ) > 1  ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item ipsJS_show ipsResponsive_hidePhone'>
				<a href="#" data-action="reorderClubmenu">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reorder_club_menu', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member\Club\Page::canAdd( $club ) ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item'>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQuerystring( 'do', 'addPage' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$sprintf = array($club->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_page_to_club', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_add_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( $page ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $page->canEdit() ):
$return .= <<<IPSCONTENT

                <li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $page->url('edit'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $page->canDelete() ):
$return .= <<<IPSCONTENT

                <li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $page->url('delete')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $container and $club->isLeader() ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_sep'><hr class='ipsHr'></li>
			<li class='ipsMenu_item'>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'nodeForm', 'type' => \get_class( $container ), 'node' => $container->_id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $container::$nodeTitle . '_sg' )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clubs_edit_this_container', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
			</li>
			
IPSCONTENT;

if ( isset( $container::$contentItemClass ) and $itemClass = $container::$contentItemClass and ( $container->modPermission( 'delete', \IPS\Member::loggedIn(), $itemClass ) or !$itemClass::contentCount( $container, TRUE, TRUE, TRUE, 1 ) ) ):
$return .= <<<IPSCONTENT

				<li class='ipsMenu_item'>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'nodeDelete', 'type' => \get_class( $container ), 'node' => $container->_id ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clubs_delete_container_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $container::$nodeTitle . '_sg' )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clubs_delete_this_container', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function mapPopup( $club ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsPhotoPanel ipsPhotoPanel--mini'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'mini' );
$return .= <<<IPSCONTENT

	<div class='i-padding_2'>
		<h3 class='ipsTitle ipsTitle--h3'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
		</h3>
		<p class='i-color_soft'>
			
IPSCONTENT;

$val = "club_{$club->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			&nbsp;&middot;&nbsp;
			
IPSCONTENT;

$pluralize = array( $club->members ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function members( $club, $members, $pagination, $sortBy, $filter, $clubStaff ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->clubs_header != 'sidebar' ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$memberStatus = $club->memberStatus( \IPS\Member::loggedIn(), 2 );
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->nonMemberClubStatus( $club, $memberStatus );
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club->canInvite() ):
$return .= <<<IPSCONTENT

	<ul class="ipsButtons ipsButtons--main i-margin-block_block">
		<li>
			<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( 'do', 'invite' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_invite_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel='nofollow noindex'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_invite_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		</li>
	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class='ipsBox i-margin-bottom_3'>
	<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_leaders', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", \IPS\Request::i()->app )->membersRows( $club, $clubStaff );
$return .= <<<IPSCONTENT

</div>


<div class='ipsBox'>
	<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_all_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'members'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.global.core.table'>
		<div class="ipsButtonBar ipsButtonBar--top">
			<div class="ipsButtonBar__pagination" data-role="tablePagination">
				{$pagination}
			</div>
			<div class='ipsButtonBar__end'>
				<ul class='ipsDataFilters'>
					<li>
						<a href="#elSortByMenu_menu" id="elSortByMenu" class='ipsDataFilters__button' data-role="sortButton" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i></a>
						<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elSortByMenu_menu">
							<li class="ipsMenu_item 
IPSCONTENT;

if ( $sortBy == 'joined' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="joined"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'members', 'filter' => $filter, 'sortby' => 'joined' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'newest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							<li class="ipsMenu_item 
IPSCONTENT;

if ( $sortBy == 'name' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsMenuValue="name"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'members', 'filter' => $filter, 'sortby' => 'name' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'username', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						</ul>
					</li>
					<li>
						<a href="#elFilterByMenu_menu" data-role="tableFilterMenu" id="elFilterByMenu" class='ipsDataFilters__button' data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i></a>
						<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elFilterByMenu_menu'>
							<li data-action="tableFilter" data-ipsMenuValue='' class='ipsMenu_item 
IPSCONTENT;

if ( !$filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'members', 'filter' => NULL, 'sortby' => $sortBy ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

if ( $club->isPaid() and $club->renewal_price ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_active_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</a>
							</li>
							
IPSCONTENT;

if ( $club->isLeader() and $club->isPaid() and $club->renewal_price ):
$return .= <<<IPSCONTENT

								<li data-action="tableFilter" data-ipsMenuValue='expired' class='ipsMenu_item 
IPSCONTENT;

if ( $filter == 'expired' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'members', 'filter' => 'expired', 'sortby' => $sortBy ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_expired_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<li data-action="tableFilter" data-ipsMenuValue='leader' class='ipsMenu_item 
IPSCONTENT;

if ( $filter == 'leader' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'members', 'filter' => 'leader', 'sortby' => $sortBy ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_leaders_and_moderators', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
							
IPSCONTENT;

if ( $club->isLeader() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $club->type === $club::TYPE_CLOSED ):
$return .= <<<IPSCONTENT

									<li data-action="tableFilter" data-ipsMenuValue='requested' class='ipsMenu_item 
IPSCONTENT;

if ( $filter == 'requested' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'members', 'filter' => 'requested', 'sortby' => $sortBy ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requests', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
									
IPSCONTENT;

if ( $club->isPaid() ):
$return .= <<<IPSCONTENT

										<li data-action="tableFilter" data-ipsMenuValue='payment_pending' class='ipsMenu_item 
IPSCONTENT;

if ( $filter == 'payment_pending' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
											<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'members', 'filter' => 'payment_pending', 'sortby' => $sortBy ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_payment_pending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
										</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<li data-action="tableFilter" data-ipsMenuValue='invited' class='ipsMenu_item 
IPSCONTENT;

if ( $filter == 'invited' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'members', 'filter' => 'invited', 'sortby' => $sortBy ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_invitations', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
								<li data-action="tableFilter" data-ipsMenuValue='banned' class='ipsMenu_item 
IPSCONTENT;

if ( $filter == 'banned' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'members', 'filter' => 'banned', 'sortby' => $sortBy ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_banned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					</li>
				</ul>
			</div>
		</div>
		<div data-role='tableRows' 
IPSCONTENT;

if ( $club->isLeader() ):
$return .= <<<IPSCONTENT
data-controller='core.front.clubs.requests'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", \IPS\Request::i()->app )->membersRows( $club, $members );
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( trim( $pagination ) ):
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

	function membersRows( $club, $members ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $members ) ):
$return .= <<<IPSCONTENT

<ol class='ipsGrid ipsGrid--lines ipsGrid--clubs-members i-basis_240'>
	
IPSCONTENT;

foreach ( $members as $member ):
$return .= <<<IPSCONTENT

		<li class='i-position_relative i-text-align_center i-padding_3 i-flex i-flex-direction_column i-gap_2' data-ips-js='memberCard'>
			<div class="i-flex i-justify-content_center">
				<div class="i-basis_140">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhotoFromData( $member['core_members']['member_id'], $member['core_members']['name'], $member['core_members']['members_seo_name'], \IPS\Member::photoUrl( $member['core_members'] ), 'fluid', '' );
$return .= <<<IPSCONTENT

				</div>
			</div>
			<h3 class='i-font-weight_600 i-font-size_3 i-color_hard'>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member['core_members']['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</h3>
			
IPSCONTENT;

if ( $club->owner and $member['core_members']['member_id'] === $club->owner->member_id ):
$return .= <<<IPSCONTENT

				<div><span class="ipsBadge ipsBadge--positive cClubMemberStatus">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_owner', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></div>
			
IPSCONTENT;

elseif ( $member['core_clubs_memberships']['status'] === \IPS\Member\Club::STATUS_LEADER ):
$return .= <<<IPSCONTENT

				<div><span class="ipsBadge ipsBadge--positive cClubMemberStatus">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_leader', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></div>
			
IPSCONTENT;

elseif ( \in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_MODERATOR, \IPS\Member\Club::STATUS_EXPIRED_MODERATOR ) ) ):
$return .= <<<IPSCONTENT

				<div><span class="ipsBadge ipsBadge--intermediary cClubMemberStatus">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_moderator', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $club->isLeader() and !\in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_REQUESTED ) ) and ( !$club->owner or $member['core_members']['member_id'] !== $club->owner->member_id ) ):
$return .= <<<IPSCONTENT

				<a href="#elMember
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member['core_members']['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Menu_menu" id="elMember
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member['core_members']['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Menu" data-ipsMenu class="ipsButton ipsButton--small ipsButton--soft ipsButton--icon ipsMemberCard_controls"><i class="fa-solid fa-gear"></i> <i class='fa-solid fa-angle-down'></i></a>
				<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="elMember
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member['core_members']['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
Menu_menu">
					
IPSCONTENT;

if ( \in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_DECLINED, \IPS\Member\Club::STATUS_BANNED ) ) ):
$return .= <<<IPSCONTENT

						<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'reInvite', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_banned_invite', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $club->isPaid() and $club->renewal_price ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $member['nexus_purchases']['ps_expire'] or \in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_INVITED, \IPS\Member\Club::STATUS_WAITING_PAYMENT ) ) ):
$return .= <<<IPSCONTENT

								<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'bypassPayment', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

if ( $member['core_clubs_memberships']['status'] === \IPS\Member\Club::STATUS_INVITED ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_bypass_fee_renew_warn', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_bypass_fee_existing_warn', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_bypass_fee', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

elseif ( !$member['nexus_purchases']['ps_expire'] ):
$return .= <<<IPSCONTENT

								<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'restorePayment', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm 
IPSCONTENT;

if ( $member['nexus_purchases']['ps_expire'] === NULL and !\in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_INVITED_BYPASSING_PAYMENT ) ) ):
$return .= <<<IPSCONTENT
data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_restore_fee_no_purchase_warn', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_restore_fee', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !\in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_INVITED, \IPS\Member\Club::STATUS_INVITED_BYPASSING_PAYMENT, \IPS\Member\Club::STATUS_WAITING_PAYMENT ) ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $member['core_clubs_memberships']['status'] === \IPS\Member\Club::STATUS_LEADER ):
$return .= <<<IPSCONTENT

								<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'demoteLeader', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_demote_leader', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'makeLeader', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_make_leader_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_make_leader', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_MODERATOR, \IPS\Member\Club::STATUS_EXPIRED_MODERATOR ) ) ):
$return .= <<<IPSCONTENT

								<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'demoteModerator', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_demote_moderator', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

elseif ( \IPS\Settings::i()->clubs_modperms != -1 ):
$return .= <<<IPSCONTENT

								<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'makeModerator', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_make_moderator_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_make_moderator', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'removeMember', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_remove_member_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_remove_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item" ><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'removeMember', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmSubMessage="
IPSCONTENT;

if ( $member['core_clubs_memberships']['status'] === \IPS\Member\Club::STATUS_INVITED ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_remove_accepted_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_remove_invitation_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_remove_invitation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			<div>
				<h4 class='i-font-weight_600'>
					
IPSCONTENT;

if ( $member['core_clubs_memberships']['status'] === \IPS\Member\Club::STATUS_REQUESTED ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_date_joined_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( \in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_INVITED, \IPS\Member\Club::STATUS_INVITED_BYPASSING_PAYMENT ) ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_date_joined_invite', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_date_joined', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</h4>
				<p>
					
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( $member['core_clubs_memberships']['joined'] )->relative(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</p>
			</div>

			
IPSCONTENT;

if ( $club->isLeader() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $club->isPaid() and $club->renewal_price ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $member['core_clubs_memberships']['status'] === \IPS\Member\Club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT

						<p class=''>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_fee_waived', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</p>
					
IPSCONTENT;

elseif ( \in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_MEMBER, \IPS\Member\Club::STATUS_EXPIRED, \IPS\Member\Club::STATUS_MODERATOR, \IPS\Member\Club::STATUS_EXPIRED_MODERATOR ) ) ):
$return .= <<<IPSCONTENT

						<hr class='ipsHr ipsHr--small'>
						<h4 class='ipsMinorTitle i-font-weight_normal'>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_membership_renews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</h4>
						<p class=''>
							
IPSCONTENT;

if ( $member['nexus_purchases']['ps_expire'] ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( !$member['nexus_purchases']['ps_active'] ):
$return .= <<<IPSCONTENT

								<span class="i-color_warning">
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$val = ( $member['nexus_purchases']['ps_expire'] instanceof \IPS\DateTime ) ? $member['nexus_purchases']['ps_expire'] : \IPS\DateTime::ts( $member['nexus_purchases']['ps_expire'] );$return .= $val->html();
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( !$member['nexus_purchases']['ps_active'] ):
$return .= <<<IPSCONTENT

								</span>
								
IPSCONTENT;

endif;
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

						</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( ( $member['added_by']['name'] or $member['invited_by']['name'] ) ):
$return .= <<<IPSCONTENT

					<hr class='ipsHr ipsHr--small'>
					<p class=''>
						
IPSCONTENT;

if ( \in_array( $member['core_clubs_memberships']['status'], array( \IPS\Member\Club::STATUS_DECLINED, \IPS\Member\Club::STATUS_BANNED ) ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $member['added_by']['name'] ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $member['core_clubs_memberships']['status'] === \IPS\Member\Club::STATUS_BANNED ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$sprintf = array($member['added_by']['name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_banned_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$sprintf = array($member['added_by']['name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_declined_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $member['core_clubs_memberships']['status'] === \IPS\Member\Club::STATUS_BANNED ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_banned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_declined', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

							
IPSCONTENT;

if ( $member['invited_by']['name'] ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$sprintf = array($member['invited_by']['name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_invited_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$sprintf = array($member['added_by']['name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_added_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							
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

endif;
$return .= <<<IPSCONTENT



			
IPSCONTENT;

if ( $club->isLeader() && $member['core_clubs_memberships']['status'] === \IPS\Member\Club::STATUS_REQUESTED ):
$return .= <<<IPSCONTENT

				<hr class='ipsHr ipsHr--small'>
				<ul class='i-flex i-gap_1 ipsMemberCard_buttons'>
					<li class='i-flex_11'>
						
IPSCONTENT;

if ( $club->isPaid() ):
$return .= <<<IPSCONTENT

							<ul class="ipsButtons">
								<li>
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'acceptRequest', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_accept_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action='requestApprove'>
										<i class='fa-solid fa-check'></i>
									</a>
								</li>
								<li>
									<a href="#elMember
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member['core_members']['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
AcceptMenu_menu" id="elMember
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member['core_members']['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
AcceptMenu" data-ipsMenu class="ipsButton ipsButton--positive ipsButton--icon"><i class='fa-solid fa-caret-down'></i></a>
									<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="elMember
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member['core_members']['member_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
AcceptMenu_menu" data-role="acceptMenu">
										<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'acceptRequest', 'waiveFee' => 1, 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action='requestApprove'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_bypass_fee', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									</ul>
								</li>
							</ul>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'acceptRequest', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--positive ipsButton--wide" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_accept_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action='requestApprove'><i class='fa-solid fa-check'></i></a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
					<li class='i-flex_11'>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'declineRequest', 'member' => $member['core_members']['member_id'] ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--wide" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_decline_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action='requestDecline'><i class='fa-solid fa-xmark'></i></a>
					</li>
				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ol>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function myClubsSidebar( $clubs, $myClubsActivity=NULL, $myClubsInvites=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id and \IPS\Member::loggedIn()->group['g_create_clubs'] ):
$return .= <<<IPSCONTENT

	<div><a class="ipsButton ipsButton--large ipsButton--primary i-width_100p" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=clubs&controller=directory&do=create", null, "clubs_list", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_club', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-users"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_club', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $myClubsInvites ) ):
$return .= <<<IPSCONTENT

	<div id='elMyClubsInvites' class="ipsWidget ipsWidget--vertical">
		<h2 class="ipsWidget__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_clubs_invites', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<section class="ipsWidget__content">
			<i-data>
				<ul class='ipsData ipsData--table ipsData--my-clubs-sidebar'>
					
IPSCONTENT;

foreach ( $myClubsInvites as $club ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->clubIcon( $club, 'fluid' );
$return .= <<<IPSCONTENT
</div>
						<div class='ipsData__main'>
							<h3 class='ipsData__title'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
							<div class='ipsData__meta'>
								<ul class='ipsList ipsList--sep'>
									<li>
IPSCONTENT;

$val = "club_{$club->type}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
									
IPSCONTENT;

if ( $club->type !== $club::TYPE_PUBLIC ):
$return .= <<<IPSCONTENT

										<li>
IPSCONTENT;

$pluralize = array( $club->members ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_members_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
							</div>
						</div>
						<div class="ipsButtons i-gap_1">
							<a class="ipsButton ipsButton--inherit ipsButton--small i-color_negative" data-ipstooltip title='
IPSCONTENT;

if ( $club->status !== $club::STATUS_REQUESTED ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_decline_invite', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_cancel_request', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'leave')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
								<i class="fa-solid fa-xmark"></i>
							</a>
							
IPSCONTENT;

if ( $club->status !== $club::STATUS_REQUESTED ):
$return .= <<<IPSCONTENT

								<a class="ipsButton ipsButton--positive ipsButton--small" data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $club->isPaid() and $club->status !== $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT
data-confirm data-confirmIcon="info" data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_membership_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubmessage="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->memberFeeMessage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<i class="fa-solid fa-check"></i>
								</a>
							
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
		</section>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div id='elMyClubs' class="ipsWidget ipsWidget--vertical">
	<h2 class="ipsWidget__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_clubs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

if ( \count( $clubs ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", \IPS\Request::i()->app )->clubs( $clubs );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<section class="ipsWidget__content ipsWidget__padding">
			<p class='i-color_soft i-font-weight_500 i-text-align_center'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_clubs_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
		</section>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

<div id='elMyClubsActivity' class="ipsWidget ipsWidget--vertical">
	<h2 class="ipsWidget__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clubs_recent_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

if ( !\count( $myClubsActivity ) ):
$return .= <<<IPSCONTENT

		<div class='ipsWidget__content ipsWidget__padding'>
			<p class='i-color_soft i-font-weight_500 i-text-align_center'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clubs_no_recent_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</p>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='ipsWidget__content'>
			<ol class="ipsStream">
				
IPSCONTENT;

foreach ( $myClubsActivity as $result ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $result !== NULL ):
$return .= <<<IPSCONTENT

						{$result->html()}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ol>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function nonMemberClubStatus( $club, $memberStatus ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \is_array( $memberStatus ) AND \in_array( $memberStatus['status'], array( $club::STATUS_INVITED, $club::STATUS_INVITED_BYPASSING_PAYMENT ) ) && $club->canJoin() ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( $memberStatus['invited_by'] ):
$return .= <<<IPSCONTENT

    	
IPSCONTENT;

$invitedBy = \IPS\Member::load( $memberStatus['invited_by'] );
$return .= <<<IPSCONTENT

    	<div class='ipsBox i-padding_3'>
	    	<div class='i-flex i-align-items_center i-justify-content_center i-flex-wrap_wrap i-gap_2'>
	    		<div class='i-flex_11'>
					<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $invitedBy, 'tiny' );
$return .= <<<IPSCONTENT

						<div>
							<h3 class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_youre_invited', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<p>
								
IPSCONTENT;

if ( $memberStatus['status'] === $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$sprintf = array($invitedBy->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_invited_bypassing_payment', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$sprintf = array($invitedBy->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_youre_invited_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</p>
						</div>
					</div>
				</div>
				<div>
					<div  class='i-flex i-gap_2 i-row-gap_0'>
						<div class='i-flex_00'>
							<a class="ipsButton ipsButton--wide ipsButton--positive" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $club->isPaid() and $memberStatus['status'] !== $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT
data-confirm data-confirmIcon="info" data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_membership_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubmessage="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->memberFeeMessage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</a>
						</div>
						<div class='i-flex_00'>
							<a class="ipsButton ipsButton--wide ipsButton--negative" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'leave')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_decline_invite', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<hr class='ipsHr'>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

elseif ( \is_array( $memberStatus ) AND $memberStatus['status'] == \IPS\Member\Club::STATUS_BANNED ):
$return .= <<<IPSCONTENT

	<div class='ipsBox i-padding_3 i-margin-bottom_3'>
		<div class='ipsPhotoPanel ipsPhotoPanel--tiny cClubStatus'>
			<i class='fa-solid fa-xmark i-font-size_2'></i>
			<div>
				<h3 class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_banned_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<p>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_banned_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</p>
			</div>
		</div>
	</div>

IPSCONTENT;

elseif ( \is_array( $memberStatus ) AND \in_array( $memberStatus['status'], array( \IPS\Member\Club::STATUS_EXPIRED, \IPS\Member\Club::STATUS_EXPIRED_MODERATOR ) ) ):
$return .= <<<IPSCONTENT

	<div class='ipsBox i-padding_3 i-margin-bottom_3'>
    	<div class='ipsColumns'>
    		<div class='ipsColumns__primary'>
				<div class='ipsPhotoPanel ipsPhotoPanel--tiny cClubStatus'>
					<i class='fa-solid fa-triangle-exclamation i-font-size_2'></i>
					<div>
						<h3 class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_expired_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_expired_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</p>
					</div>
				</div>
			</div>
			<div class='ipsColumns__secondary i-basis_200'>
				<a class="ipsButton ipsButton--wide ipsButton--positive" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'renew')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_renew_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</a>
			</div>
		</div>
	</div>
	<hr class='ipsHr'>

IPSCONTENT;

elseif ( !$club->canRead() ):
$return .= <<<IPSCONTENT

	<div class='ipsBox i-padding_3 i-margin-bottom_3'>
		
IPSCONTENT;

if ( \is_array( $memberStatus ) AND $memberStatus['status'] === $club::STATUS_REQUESTED ):
$return .= <<<IPSCONTENT

			<div class='ipsPhotoPanel ipsPhotoPanel--tiny cClubStatus'>
				<i class='fa-regular fa-clock i-font-size_2'></i>
				<div>
					<h3 class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requested_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<p>
						
IPSCONTENT;

if ( $club->isPaid() ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requested_desc_paid', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requested_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
		
IPSCONTENT;

elseif ( \is_array( $memberStatus ) AND $memberStatus['status'] === $club::STATUS_WAITING_PAYMENT ):
$return .= <<<IPSCONTENT

			<div class='ipsColumns'>
	    		<div class='ipsColumns__primary'>
					<div class='ipsPhotoPanel ipsPhotoPanel--tiny cClubStatus'>
						<i class='fa-solid fa-check i-font-size_2'></i>
						<div>
							<h3 class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_awaiting_payment_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<p>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_awaiting_payment_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</p>
						</div>
					</div>
				</div>
				<div class='ipsColumns__secondary i-basis_120'>
					<a class="ipsButton ipsButton--wide ipsButton--positive" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_pay_membership_fee', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				</div>
			</div>			
		
IPSCONTENT;

elseif ( \is_array( $memberStatus ) AND $memberStatus['status'] === \IPS\Member\Club::STATUS_DECLINED ):
$return .= <<<IPSCONTENT

			<div class='ipsPhotoPanel ipsPhotoPanel--tiny cClubStatus'>
				<i class='fa-solid fa-xmark i-font-size_2'></i>
				<div>
					<h3 class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_denied_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<p>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_denied_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='ipsColumns'>
	    		<div class='ipsColumns__primary'>
					<div class='ipsPhotoPanel ipsPhotoPanel--tiny cClubStatus'>
						<i class='fa-solid fa-lock i-font-size_2'></i>
						<div>
							<h3 class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_closed_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<p>
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_closed_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</p>
						</div>
					</div>
				</div>
				
IPSCONTENT;

if ( $club->canJoin() ):
$return .= <<<IPSCONTENT

				<div class='ipsColumns__secondary i-basis_120'>
					<a class="ipsButton ipsButton--wide ipsButton--positive" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString('do', 'join')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $club->isPaid() and \is_array( $memberStatus ) AND $memberStatus['status'] !== $club::STATUS_INVITED_BYPASSING_PAYMENT ):
$return .= <<<IPSCONTENT
data-confirm data-confirmIcon="info" data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_membership_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmSubmessage="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->memberFeeMessage(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_join', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
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

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function rulesForm( $club, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' method="post" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id='elStatusSubmit' 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<div class="ipsBox">
		<div class='ipsBox__padding'>
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


			<div class="ipsMessage ipsMessage--info i-margin-bottom_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'clubs_rules_accept_msg', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			
			<div class='ipsRichText'>
				{$club->rules}
			</div>
			
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Editor ):
$return .= <<<IPSCONTENT

						{$input->html()}
						
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

							<div class="ipsFieldRow__warning">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
		<ul class="ipsSubmitRow ipsButtons">
			
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

				<li>{$button}</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</form>	

IPSCONTENT;

		return $return;
}

	function view( $club, $activity, $fieldValues ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->clubs_header != 'sidebar' ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class='ipsColumns'>
	<section class='ipsColumns__primary ipsBlockSpacer'>
	
		
IPSCONTENT;

if ( \IPS\Settings::i()->clubs_require_approval and !$club->approved ):
$return .= <<<IPSCONTENT

			<div class='ipsBox ipsPull i-padding_3 ipsModerated'>
				<div class='i-flex i-align-items_center i-gap_2'>
		    		<div class='i-flex_11'>
						<div class='i-flex i-align-items_center i-gap_2 cClubStatus'>
							<i class='fa-solid fa-eye-slash i-font-size_2 i-flex_00'></i>
							<div class='i-flex_11'>
								<h3 class='i-font-size_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unapproved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<p>
									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_access_all_clubs') ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unapproved_desc_mod', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_unapproved_desc_public', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</p>
							</div>
						</div>
					</div>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_access_all_clubs') ):
$return .= <<<IPSCONTENT

						<div class='i-flex_00'>
							<div class='i-flex i-align-items_center i-gap_2'>
								<div class='i-flex_00'>
									<a class="ipsButton ipsButton--wide ipsButton--positive" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'approve', 'approved' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									</a>
								</div>
								<div class='i-flex_00'>
									<a class="ipsButton ipsButton--wide ipsButton--negative" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->url()->setQueryString( array( 'do' => 'approve', 'approved' => 0 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
										
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									</a>
								</div>
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


		
IPSCONTENT;

$memberStatus = $club->memberStatus( \IPS\Member::loggedIn(), 2 );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->nonMemberClubStatus( $club, $memberStatus );
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $club->canRead() || $club->about || ( $club->location() && \IPS\Settings::i()->clubs_locations && ( !\IPS\GeoLocation::enabled() ) ) || \IPS\Member\Club\CustomField::roots() ):
$return .= <<<IPSCONTENT

			<div class='ipsBox ipsPull i-padding_3'>
				
IPSCONTENT;

if ( $club->canRead() ):
$return .= <<<IPSCONTENT

					<div class='
IPSCONTENT;

if ( $club->about OR ($club->location() && \IPS\Settings::i()->clubs_locations && ( !\IPS\GeoLocation::enabled() )) ):
$return .= <<<IPSCONTENT
i-padding-bottom_3 i-border-bottom_3 i-margin-bottom_2
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'core','club', $club->id, $club->followersCount() );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( $club->about ):
$return .= <<<IPSCONTENT

					<div>
						<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_about_this_club', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<div class='ipsRichText'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->about, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $club->location() && \IPS\Settings::i()->clubs_locations && ( !\IPS\GeoLocation::enabled() ) ):
$return .= <<<IPSCONTENT

					<div class='
IPSCONTENT;

if ( $club->about ):
$return .= <<<IPSCONTENT
i-margin-top_3
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
						<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<div class='ipsRichText'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $club->location(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</div>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$printedHr = FALSE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member\Club\CustomField::roots() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( \IPS\Member\Club\CustomField::roots() as $field ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( isset( $fieldValues[ "field_" . $field->id ] ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !$printedHr ):
$return .= <<<IPSCONTENT

								<hr class='ipsHr'>
								
IPSCONTENT;

$printedHr = TRUE;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<div class='i-margin-bottom_3'>
								<h3 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $field->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
								<div class='ipsRichText'>
									{$field->displayValue( $fieldValues[ "field_" . $field->id ] )}
								</div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $club->isLeader() and $club->type === $club::TYPE_CLOSED and $pendingMembers = $club->members( array( $club::STATUS_REQUESTED ), NULL, NULL, 3 ) and \count( $pendingMembers ) ):
$return .= <<<IPSCONTENT

			<div class='ipsBox ipsPull'>
				<h2 class='ipsBox__header'><i class='fa-solid fa-lock' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requested_users_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_requested_users', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<div data-controller='core.front.clubs.requests'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", \IPS\Request::i()->app )->membersRows( $club, $pendingMembers );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $club->canRead() ):
$return .= <<<IPSCONTENT

			<div class='ipsBox ipsPull'>
				<ol class="ipsStream ipsStream_withTimeline">
					<li class='ipsStream__time ipsTitle ipsTitle--h4 i-border-start-start-radius_box i-border-start-end-radius_box'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_whats_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

foreach ( $activity as $result ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $result !== NULL ):
$return .= <<<IPSCONTENT

							{$result->html()}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</section>
</div>
IPSCONTENT;

		return $return;
}

	function viewPage( $page ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->clubs_header != 'sidebar' ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $page->club, NULL, 'full', $page );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $page->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
<br>
<div id='elClubContainer'>
	<div class='ipsBox i-padding_3'>
		<div class='ipsRichText' id='elPageContent'>
			{$page->content}
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}}