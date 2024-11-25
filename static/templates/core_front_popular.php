<?php
namespace IPS\Theme;
class class_core_front_popular extends \IPS\Theme\Template
{	function memberRow( $member, $rep=NULL, $trophy=0, $contentLabel='members_member_posts', $contentCount='member_posts' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$rep = $rep ?: $member->pp_reputation_points;
$return .= <<<IPSCONTENT


IPSCONTENT;

$contentCount = $contentCount ? $member->$contentCount : $member->member_posts;
$return .= <<<IPSCONTENT


IPSCONTENT;

$coverPhoto = $member->coverPhoto();
$return .= <<<IPSCONTENT

<li class='i-padding_2 i-flex i-flex-direction_column'>
	<div class='ipsCoverPhoto' data-controller='core.global.core.coverPhoto' data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->url()->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

			<div class='ipsCoverPhoto__container'>
				<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCoverPhoto__image' alt='' loading='lazy'>
			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='ipsCoverPhoto__container'>
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
	<div class='cUserHovercard__grid i-grid i-gap_lines'>
		<div class='i-padding_2 i-flex i-align-items_center'>
			<div class='i-flex_11 ipsPhotoPanel'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'fluid' );
$return .= <<<IPSCONTENT

				<div class='ipsPhotoPanel__text'>
					<h4 class='ipsTitle ipsTitle--h4' data-searchable>
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $member );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</h4>
					<div class='ipsPhotoPanel__secondary i-font-weight_600'>{$member->groupName}</div>
				</div>
			</div>
			
IPSCONTENT;

if ( $trophy ):
$return .= <<<IPSCONTENT

				<span class="i-flex_00 ipsLeaderboard_trophy ipsLeaderboard_trophy_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $trophy, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					<i class="fa-solid fa-trophy"></i>
				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsFluid">
			<div class='i-grid i-place-content_center i-text-align_center 
IPSCONTENT;

if ( $rep > 0 ):
$return .= <<<IPSCONTENT
i-color_positive
IPSCONTENT;

elseif ( $rep < 0 ):
$return .= <<<IPSCONTENT
i-color_negative
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				<div class='
IPSCONTENT;

if ( $rep == 0 ):
$return .= <<<IPSCONTENT
i-color_hard
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-font-size_3 i-font-weight_600'>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&do=reputation", null, "profile_reputation", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='i-color_inherit' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_badge_tooltip_period', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip><i class='fa-solid 
IPSCONTENT;

if ( $rep > 0 ):
$return .= <<<IPSCONTENT
fa-plus-circle
IPSCONTENT;

elseif ( $rep < 0 ):
$return .= <<<IPSCONTENT
fa-minus-circle
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-circle-o
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->formatNumber( $rep ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_badge_tooltip_period', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip><i class='fa-solid 
IPSCONTENT;

if ( $rep > 0 ):
$return .= <<<IPSCONTENT
fa-plus-circle
IPSCONTENT;

elseif ( $rep < 0 ):
$return .= <<<IPSCONTENT
fa-minus-circle
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
fa-circle-o
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->language()->formatNumber( $rep ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class='
IPSCONTENT;

if ( $rep == 0 ):
$return .= <<<IPSCONTENT
i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-font-weight_500'>
IPSCONTENT;

if ( \IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rep_system_like', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rep_level_points', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
			</div>
			<div class='i-grid i-place-content_center i-text-align_center'>
				<div class='i-color_hard i-font-size_3 i-font-weight_600'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $contentCount );
$return .= <<<IPSCONTENT
</div>
				<div class='i-color_soft i-font-weight_500'>
IPSCONTENT;

$val = "{$contentLabel}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			</div>
		</div>
	</div>
	
IPSCONTENT;

$showFollowButton = ( \IPS\Member::loggedIn()->member_id != $member->member_id and ( !$member->members_bitoptions['pp_setting_moderate_followers'] or \IPS\Member::loggedIn()->following( 'core', 'member', $member->member_id ) ) );
$return .= <<<IPSCONTENT

	<ul class='ipsButtons ipsButtons--fill i-margin-top_auto i-padding-top_1'>
		
IPSCONTENT;

if ( $showFollowButton ):
$return .= <<<IPSCONTENT

			<li>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->memberFollow( 'core', 'member', $member->member_id, $member->followersCount(), TRUE );
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<li>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=content&id={$member->member_id}", "front", "profile_content", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'><i class="fa-regular fa-file-lines"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'find_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
		</li>
	</ul>
</li>
IPSCONTENT;

		return $return;
}

	function popularItem( $indexData, $articles, $authorData, $itemData, $unread, $objectUrl, $itemUrl, $containerUrl, $containerTitle, $repCount, $showRepUrl, $snippet, $iPostedIn, $view, $canIgnoreComments=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsStreamItem ipsStreamItem_expanded ipsStreamItem_contentBlock'>
	
IPSCONTENT;

if ( !\IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$itemClass = $indexData['index_class']::$itemClass;
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['title'] ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$itemClass = $indexData['index_class']::$itemClass;
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['title'] ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__iconCell'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $indexData['index_author'] ), 'fluid' );
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsStreamItem__mainCell'>
		<div class='ipsStreamItem__header i-flex i-flex-wrap_wrap-reverse i-gap_1'>
			<div class='i-flex_91'>
				<h2 class='ipsStreamItem__title'>
					
IPSCONTENT;

if ( !\IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$itemClass = $indexData['index_class']::$itemClass;
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['title'] ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$itemClass = $indexData['index_class']::$itemClass;
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['title'] ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</h2>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT
			
					<p class="ipsStreamItem__summary">
						
IPSCONTENT;

$membersLiked = \IPS\Member::load( $indexData['rep_data']['member_id'] )->link();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $indexData['rep_data']['total_rep'] == 2 ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$membersLiked = \IPS\Member::loggedIn()->language()->addToStack( 'replog_member_and_one_other', FALSE, array( 'htmlsprintf' => array( $membersLiked, $objectUrl->setQueryString( array( 'do' => 'showReactionsReview', 'review' => $indexData['index_object_id'] ) ) ) ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

elseif ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$membersLiked = \IPS\Member::loggedIn()->language()->addToStack( 'replog_member_and_one_other', FALSE, array( 'htmlsprintf' => array( $membersLiked, $objectUrl->setQueryString( array( 'do' => 'showReactionsComment', 'comment' => $indexData['index_object_id'] ) ) ) ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$membersLiked = \IPS\Member::loggedIn()->language()->addToStack( 'replog_member_and_one_other', FALSE, array( 'htmlsprintf' => array( $membersLiked, $objectUrl->setQueryString('do', 'showReactions') ) ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( $indexData['rep_data']['total_rep'] > 2 ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$membersLiked = \IPS\Member::loggedIn()->language()->addToStack( 'replog_member_and_x_other', FALSE, array( 'htmlsprintf' => array( \IPS\Member::load( $indexData['rep_data']['member_id'] )->link(), $objectUrl->setQueryString( array( 'do' => 'showReactionsReview', 'review' => $indexData['index_object_id'] ) ), $indexData['rep_data']['total_rep']-1 ) ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

elseif ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$membersLiked = \IPS\Member::loggedIn()->language()->addToStack( 'replog_member_and_x_other', FALSE, array( 'htmlsprintf' => array( \IPS\Member::load( $indexData['rep_data']['member_id'] )->link(), $objectUrl->setQueryString( array( 'do' => 'showReactionsComment', 'comment' => $indexData['index_object_id'] ) ), $indexData['rep_data']['total_rep']-1 ) ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$membersLiked = \IPS\Member::loggedIn()->language()->addToStack( 'replog_member_and_x_other', FALSE, array( 'htmlsprintf' => array( $membersLiked, $objectUrl->setQueryString('do', 'showReactions'), $indexData['rep_data']['total_rep']-1 ) ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !\IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$itemClass = $indexData['index_class']::$itemClass;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $indexData['rep_data']['member_received'] ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($membersLiked, \IPS\Member::load( $indexData['rep_data']['member_received'] )->link(), $indexData['index_class']::_indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_item_gave_no_in', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($membersLiked, $indexData['index_class']::_indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_item_gave_no_recipient_no_in', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $indexData['rep_data']['member_received'] ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($membersLiked, \IPS\Member::load( $indexData['rep_data']['member_received'] )->link(), $indexData['index_class']::_indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_item_gave_no_in', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($membersLiked, $indexData['index_class']::_indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_rate_item_gave_no_recipient_no_in', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
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

if ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$itemClass = $indexData['index_class']::$itemClass;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $indexData['rep_data']['member_received'] ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($membersLiked, $objectUrl, $indexData['index_class']::_indefiniteArticle(), \IPS\Member::load( $indexData['rep_data']['member_received'] )->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_like_comment_no_in', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($membersLiked, $objectUrl, $indexData['index_class']::_indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_like_comment_no_recipient_no_in', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $indexData['rep_data']['member_received'] ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($membersLiked, $indexData['index_class']::_indefiniteArticle(), \IPS\Member::load( $indexData['rep_data']['member_received'] )->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_like_item_no_in', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($membersLiked, $indexData['index_class']::_indefiniteArticle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replog_like_item_no_recipient_no_in', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
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

					</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='i-flex_11 i-color_soft i-font-weight_600'>
				
IPSCONTENT;

if ( \IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'showReactionsReview', 'review' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-destructOnClose data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_liked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsToolTip><i class='fa-solid fa-heart i-margin-end_icon'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

elseif ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'showReactionsComment', 'comment' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-destructOnClose data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_liked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsToolTip><i class='fa-solid fa-heart i-margin-end_icon'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'showReactions' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-destructOnClose data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_liked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsToolTip><i class='fa-solid fa-heart i-margin-end_icon'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i class='fa-solid fa-heart i-margin-end_icon'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<span>
IPSCONTENT;

$pluralize = array( $indexData['rep_data']['total_rep'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like_blurb_pluralized', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $indexData['rep_data']['rep_rating'] === 1 ):
$return .= <<<IPSCONTENT

						<i class='fa-solid fa-arrow-up i-margin-end_icon'></i>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i class='fa-solid fa-arrow-down i-margin-end_icon'></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					<span>
IPSCONTENT;

$pluralize = array( $indexData['rep_data']['total_rep'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rep_level_points_pluralized', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
		
IPSCONTENT;

if ( $snippet ):
$return .= <<<IPSCONTENT

			<div class='ipsStreamItem__content'>
				{$snippet}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<ul class='ipsStreamItem__stats'>
			<li>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-clock"></i> 
IPSCONTENT;

$val = ( $indexData['index_date_updated'] instanceof \IPS\DateTime ) ? $indexData['index_date_updated'] : \IPS\DateTime::ts( $indexData['index_date_updated'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
			</li>
		</ul>

	</div>
	<div class='ipsStreamItem__popular' hidden>
		
IPSCONTENT;

if ( \IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'showReactionsReview', 'review' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-destructOnClose data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_liked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsToolTip><i class='fa-solid fa-heart'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

elseif ( \in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'showReactionsComment', 'comment' => $indexData['index_object_id'] ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-destructOnClose data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_liked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsToolTip><i class='fa-solid fa-heart'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $objectUrl->setQueryString( array( 'do' => 'showReactions' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-destructOnClose data-ipsDialog-size='narrow' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like_log_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_liked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsToolTip><i class='fa-solid fa-heart'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-heart'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<span>
IPSCONTENT;

$pluralize = array( $indexData['rep_data']['total_rep'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'like_blurb_pluralized', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $indexData['rep_data']['rep_rating'] === 1 ):
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-arrow-up'></i>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i class='fa-solid fa-arrow-down'></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['rep_data']['total_rep'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			<span>
IPSCONTENT;

$pluralize = array( $indexData['rep_data']['total_rep'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rep_level_points_pluralized', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>

IPSCONTENT;

		return $return;
}

	function popularItems( $results ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$currentSeparator = NULL;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $results ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $results as $result ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $result !== NULL ):
$return .= <<<IPSCONTENT

			{$result->html( 'expanded', FALSE, TRUE, array( \IPS\Theme::i()->getTemplate( 'popular', 'core', 'front' ), 'popularItem' ) )}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<li class='i-text-align_center i-color_soft i-padding_3' data-role="streamNoResultsMessage">
		<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'popular_no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function popularRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty( $rows ) ):
$return .= <<<IPSCONTENT

	<li class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$currentDate = null;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$rowCounts = array();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $r ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$nextDate = md5( $r['leader_date']->dayAndMonth() . $r['leader_date']->format('Y') );
$return .= <<<IPSCONTENT

		
		
IPSCONTENT;

if ( $currentDate !== $nextDate ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $currentDate  ):
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<li class='cPastLeaders_row'>
				<h2 class='cPastLeaders_title i-color_soft i-font-weight_600 i-link-color_inherit'>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=discover&controller=popular&tab=leaderboard&custom_date_start={$r['leader_date']->getTimeStamp()}&custom_date_end={$r['leader_date']->getTimeStamp()}", null, "leaderboard_leaderboard", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['leader_date']->dayAndMonth(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['leader_date']->format('Y'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
				</h2>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( ! $r['leader_member_id']->member_id or ! $r['leader_rep_total'] ):
$return .= <<<IPSCONTENT

			<!-- <div class='cPastLeaders_cell'></div> -->
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class='cPastLeaders_cell i-flex i-align-items_center i-gap_2' data-position='
IPSCONTENT;

$val = "leader_position_{$r['leader_position']}_short"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
				<div class='ipsPhotoPanel i-flex_11'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $r['leader_member_id'], 'fluid' );
$return .= <<<IPSCONTENT

					<div class='ipsPhotoPanel__text'>
						<div class='ipsPhotoPanel__primary'>{$r['leader_member_id']->link()}</div>
						<div class='ipsPhotoPanel__secondary'>
							
IPSCONTENT;

if ( \IPS\Content\Reaction::isLikeMode() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$pluralize = array( $r['leader_rep_total'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'received_x_likes', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$pluralize = array( $r['leader_rep_total'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'received_x_points', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
				<span class="i-flex_00 ipsLeaderboard_trophy ipsLeaderboard_trophy_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['leader_position'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$val = "leader_position_{$r['leader_position']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
					<i class="fa-solid fa-trophy"></i>
				</span>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !isset( $rowCounts[ $nextDate ] ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$rowCounts[ $nextDate ] = 1;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$rowCounts[ $nextDate ]++;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$currentDate = $nextDate;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
				
	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function popularTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-tableID='pastLeaders' data-controller="core.global.core.genericTable">
	<div>
		
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--top">
				<div data-role="tablePagination" class='ipsButtonBar__pagination'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

				</div>	
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<ol class='cPastLeaders ipsGrid ipsGrid--lines i-basis_300' data-role="tableRows">
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

		</ol>
		
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				<div data-role="tablePagination" class='ipsButtonBar__pagination'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

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

	function popularWrapper( $results, $areas, $topContributors, $dates, $description, $form, $tzOffsetDifference ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Content\Search\Query::isRebuildRunning() ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--info">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'popular_rebuild_is_running', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$now = \IPS\DateTime::ts( time() );
$return .= <<<IPSCONTENT


IPSCONTENT;

$thisUrl = \IPS\Widget\Request::i()->url();
$return .= <<<IPSCONTENT


<div>
    
IPSCONTENT;

if ( \count( $dates ) ):
$return .= <<<IPSCONTENT

        <div class="ipsButtonBar ipsButtonBar--top">
            <div class="ipsButtonBar__end">
                <ul class="ipsDataFilters">
                    <li>
                        <a href='#elLeaderboard_app_menu' id="elLeaderboard_app" class='ipsDataFilters__button' data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active"><span>
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->in ) and isset( $areas[ \IPS\Widget\Request::i()->in ] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($areas[ \IPS\Request::i()->in ][1]); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'leaderboard_in_app', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'leaderboard_in_all_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
                        <ul id="elLeaderboard_app_menu" class="ipsMenu ipsMenu_selectable ipsMenu_normal ipsHide">
                            <li class="ipsMenu_item 
IPSCONTENT;

if ( ! isset( \IPS\Widget\Request::i()->in ) ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisUrl->stripQueryString( 'in' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'leaderboard_all_apps', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
                            
IPSCONTENT;

foreach ( $areas as $key => $data ):
$return .= <<<IPSCONTENT

                            <li class="ipsMenu_item 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->in ) and \IPS\Widget\Request::i()->in == $key ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisUrl->setQueryString( array( 'in' => $key ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data[1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
                            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                        </ul>
                    </li>
                    <li>
                        <a href='#elLeaderboard_time_menu' id="elLeaderboard_time" class='ipsDataFilters__button' data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active">
                            <span>
                                
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->custom_date_start ) or isset( \IPS\Widget\Request::i()->custom_date_end ) ):
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

elseif ( isset( \IPS\Widget\Request::i()->time ) and isset( $dates[ \IPS\Widget\Request::i()->time ] ) and $setTime = \IPS\Widget\Request::i()->time ):
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

$val = "leaderboard_time_$setTime"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'leaderboard_time_oldest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                            </span>
                            <i class="fa-solid fa-caret-down"></i>
                        </a>
                        <ul id="elLeaderboard_time_menu" class="ipsMenu ipsMenu_selectable ipsMenu_normal ipsHide">
                            
IPSCONTENT;

foreach ( $dates as $human => $timeObject ):
$return .= <<<IPSCONTENT

                            <li class="ipsMenu_item 
IPSCONTENT;

if ( ( ! isset( \IPS\Widget\Request::i()->time ) and ( ! isset( \IPS\Widget\Request::i()->custom_date_start ) and ! isset( \IPS\Widget\Request::i()->custom_date_end ) ) and $human == 'oldest' ) or ( ! isset( \IPS\Widget\Request::i()->custom_date_start ) and ( isset( \IPS\Widget\Request::i()->time ) and \IPS\Widget\Request::i()->time == $human ) ) ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
                                <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $thisUrl->stripQueryString( array('custom_date_start', 'custom_date_end') )->setQueryString( array( 'time' => $human ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
                                    
IPSCONTENT;

$val = "leaderboard_time_$human"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                    <p class="ipsTruncate_1 i-color_soft">
                                        
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $timeObject->dayAndMonth(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $timeObject->format('Y'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $now->localeDate() != $timeObject->localeDate() ):
$return .= <<<IPSCONTENT
 - 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $now->dayAndMonth(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $now->format('Y'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    </p>
                                </a>
                            </li>
                            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                            <li class="ipsMenu_item 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->custom_date_start ) or isset( \IPS\Widget\Request::i()->custom_date_end ) ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
                                <a href="#" rel="nofollow" data-ipsDialog data-ipsDialog-size='narrow' data-ipsDialog-content='#elDateForm' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->custom_date_start ) or isset( \IPS\Widget\Request::i()->custom_date_end ) ):
$return .= <<<IPSCONTENT

                                <p class="i-color_soft">
                                    
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->custom_date_start ) ):
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

$val = ( \IPS\Request::i()->custom_date_start instanceof \IPS\DateTime ) ? \IPS\Request::i()->custom_date_start : \IPS\DateTime::ts( \IPS\Request::i()->custom_date_start );$return .= (string) $val->localeDate();
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->custom_date_end ) ):
$return .= <<<IPSCONTENT
 - 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->custom_date_end ) ):
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

$val = ( \IPS\Request::i()->custom_date_end instanceof \IPS\DateTime ) ? \IPS\Request::i()->custom_date_end : \IPS\DateTime::ts( \IPS\Request::i()->custom_date_end );$return .= (string) $val->localeDate();
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                </p>
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                </a>
                            </li>
                        </ul>
                        <div class="ipsHide" id="elDateForm">
                            {$form}
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $topContributors) ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$count = 0;
$return .= <<<IPSCONTENT

        <ol class="ipsMembers ipsGrid i-gap_lines i-basis_320 i-border-bottom_3">
            
IPSCONTENT;

foreach ( $topContributors as $memberId => $rep ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$count++;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$member = \IPS\Member::load( $memberId );
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "popular", "core" )->memberRow( $member, $rep, $count );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        </ol>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'popular_no_member_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
<section data-controller='core.front.core.ignoredComments'>
    <div class='i-padding_3 i-padding-top_5 i-background_2 i-border-bottom_3'>
        <h2 class='ipsTitle ipsTitle--h2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'popular_results_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
        <p class="i-color_soft i-font-size_2 i-font-weight_500 i-margin-top_1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
    </div>
    <div data-role='popularResults'>
        <ol class='ipsStream' data-role='popularContent'>
            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "popular", "core" )->popularItems( $results );
$return .= <<<IPSCONTENT

        </ol>
    </div>
</section>

IPSCONTENT;

if ( $tzOffsetDifference !== NULL ):
$return .= <<<IPSCONTENT

	<div class='i-padding_2 i-font-size_-1 i-color_soft i-font-weight_500 i-text-align_center'>
		<i class="fa-solid fa-earth-americas i-margin-end_icon"></i>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack('timezone__' . \IPS\Settings::i()->reputation_timezone), $tzOffsetDifference); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'popular_timezone', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function tabs( $tabs, $activeTab, $content ) {
		$return = '';
		$return .= <<<IPSCONTENT


<header class='ipsPageHeader'>
	<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'leaderboard_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
</header>

<div class='ipsBox ipsBox--leaderboard ipsPull'>
	
IPSCONTENT;

$icons = array('leaderboard' => 'trophy', 'history' => 'clock', 'members' => 'star', 'trending' => 'line-chart');
$return .= <<<IPSCONTENT

	<i-tabs class='ipsTabs ipsTabs--withIcons ipsTabs--stretch' id='ipsTabs_leaderboard' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_leaderboard_content'>
		<div role='tablist'>
			
IPSCONTENT;

foreach ( $tabs as $key ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$seoTemplate = 'leaderboard_' . $key;
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=discover&controller=popular&tab={$key}", null, "$seoTemplate", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' role='tab' id='ipsTabs_leaderboard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-controls="ipsTabs_leaderboard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" class='ipsTabs__tab' aria-selected="
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
					<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icons[$key], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i>
					
IPSCONTENT;

$val = "leaderboard_tabs_{$key}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
	<section id='ipsTabs_leaderboard_content' class="ipsTabs__panels">
		<div id='ipsTabs_leaderboard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' aria-labelledby='ipsTabs_leaderboard_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' role="tabpanel" class='ipsTabs__panel'>
			{$content}
		</div>
	</section>
</div>
IPSCONTENT;

		return $return;
}

	function topMembers( $url, $filters, $activeFilter, $output ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-resort='topMembers' data-tableID='topMembers' data-controller='core.global.core.table'>
	<div class="ipsButtonBar ipsButtonBar--top">
		<div class='ipsButtonBar__end'>
			<ul class="ipsDataFilters">
				<li>
					<a href="#elFilterByMenu_menu" id="elFilterByMenu" class='ipsDataFilters__button' data-role='tableFilterMenu' data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters--active" data-ipsMenu-selectable="radio"><span data-role="extraHtml">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $filters[ $activeFilter ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> <i class="fa-solid fa-caret-down"></i></a>
					<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' data-role="tableFilterMenu" id='elFilterByMenu_menu'>
						
IPSCONTENT;

foreach ( $filters as $k => $v ):
$return .= <<<IPSCONTENT

							<li data-action="tableFilter" data-ipsMenuValue='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( array( 'IPS\\', '\\' ), array( '', '_' ), $k ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsMenu_item 
IPSCONTENT;

if ( $k === $activeFilter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'filter' => str_replace( array( 'IPS\\', '\\' ), array( '', '_' ), $k ) ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="nofollow">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</li>
			</ul>
		</div>
	</div>
	<section data-role="tableRows">
		{$output}
	</section>
</div>

IPSCONTENT;

		return $return;
}

	function topMembersOverview( $filters ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $filters as $k => $lang ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "popular", \IPS\Request::i()->app )->topMembersResults( $k, $lang, \IPS\Member::topMembers( $k, \IPS\Settings::i()->reputation_overview_max_members ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function topMembersResults( $filter, $title, $results ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	
IPSCONTENT;

if ( $title ):
$return .= <<<IPSCONTENT

		<h2 class="ipsTitle ipsTitle--h3 ipsTitle--padding i-background_2">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $results ) ):
$return .= <<<IPSCONTENT

		<ol class="ipsMembers ipsGrid i-gap_lines i-basis_320 i-border-top_3 i-border-bottom_3">
			
IPSCONTENT;

foreach ( $results as $member ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \in_array( $filter, array( 'pp_reputation_points', 'member_posts' ) ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "popular", "core" )->memberRow( $member );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "popular", "core" )->memberRow( $member, NULL, 0, $filter::$title . '_pl_lc', '_customCount' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}}