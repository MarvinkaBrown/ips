<?php
namespace IPS\Theme;
class class_gallery_front_global extends \IPS\Theme\Template
{	function approvalQueueItem( $item, $ref, $container, $title ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="elApprovePanel" class='ipsBox'>
	<article class="">
		<div class='i-padding_3'>
			<div class="ipsPhotoPanel"> 
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->userPhoto( $item->author() );
$return .= <<<IPSCONTENT

				<div class='ipsPhotoPanel__text'>
					<a href="#user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsTitle ipsTitle--h3" data-ipsmenu="">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 &nbsp;<i class="fa-solid fa-caret-down"></i></a>
					<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('mod_can_warn') ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=warn&id={$item->author()->member_id}&ref={$ref}", null, "warn_add", array( $item->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title="
IPSCONTENT;

$sprintf = array($item->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_member', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" data-ipsDialog-remoteSubmit data-ipsDialog-flashMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warning_issued', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-role="warnUserDialog">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_user', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $item->author()->member_id != \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $item->author()->members_bitoptions['bw_is_spammer'] ):
$return .= <<<IPSCONTENT

								<li class='ipsMenu_item' data-ipsMenuValue='spamFlagButton'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$item->author()->member_id}&s=0" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $item->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'spam_unflag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<li class='ipsMenu_item' data-ipsMenuValue='spamFlagButton'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=moderation&do=flagAsSpammer&id={$item->author()->member_id}&s=1" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "flag_as_spammer", array( $item->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-confirm>
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

						<li class="ipsMenu_item"><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$item->author()->member_id}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-remoteSubmit data-ipsDialog-flashMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_sent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'message_send', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					</ul>
					
IPSCONTENT;

if ( $container ):
$return .= <<<IPSCONTENT

						<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posted_in_container', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<p class="i-color_soft">
IPSCONTENT;

$htmlsprintf = array(\IPS\DateTime::ts( $item->mapped('date') )->html(FALSE)); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'date_replied', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
				</div>
				<p>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_soft'>
						<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item::$icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$val = "{$item::$title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</a>
				</p>
			</div>
		</div>
		<div class='i-background_2 i-padding_3'>
			<div class='ipsBox'>
				<h2 class="ipsBox__header"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h2>
				<div class="ipsRichText ipsPost i-padding_3">
					
IPSCONTENT;

if ( $item->masked_file_name ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->masked_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsThumb'><i class="fa-solid fa-film"></i></a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
	</article>
</div>

IPSCONTENT;

if ( $reason = $item->approvalQueueReason() ):
$return .= <<<IPSCONTENT

	<div class='ipsBox'>
		<div class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reason_for_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		<div class='i-background_2 i-border-bottom_3 i-padding_2'>
			<p class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reason, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function comment( $item, $comment, $editorName, $app, $type, $class='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $comment::$databaseColumnId;
$return .= <<<IPSCONTENT

<div id="comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap" data-controller="core.front.core.comment" data-feedid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commentapp="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commenttype="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commentid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-quotedata="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( array('userid' => $comment->author()->member_id, 'username' => $comment->author()->name, 'timestamp' => $comment->mapped('date'), 'contentapp' => $app, 'contenttype' => $type, 'contentclass' => $class, 'contentid' => $item->id, 'contentcommentid' => $comment->$idField) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsEntry__content js-ipsEntry__content" 
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\IntersectionViewTracking' ) AND $hash=$comment->getViewTrackingHash() ):
$return .= <<<IPSCONTENT
 data-view-hash="{$hash}" data-view-tracking-data="
IPSCONTENT;

$return .= base64_encode(json_encode( $comment->getViewTrackingData() ));
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<header class="ipsEntry__header">
		<div class="ipsEntry__header-align">
			<div class="ipsPhotoPanel">
				<div class="ipsAvatarStack">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $comment->author(), 'fluid', $comment->warningRef() );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$comment->isAnonymous() and $comment->author()->canHaveAchievements() and \IPS\core\Achievements\Rank::show() and $rank = $comment->author()->rank() ):
$return .= <<<IPSCONTENT

						{$rank->html( 'ipsAvatarStack__rank' )}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsPhotoPanel__text">
					<div class="ipsEntry__username">
						<h3>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $comment->author(), $comment->warningRef(), NULL, $comment->isAnonymous() );
$return .= <<<IPSCONTENT
</h3>
						
IPSCONTENT;

if ( !$comment->isAnonymous() ):
$return .= <<<IPSCONTENT

							<span class="ipsEntry__group">
IPSCONTENT;

$return .= \IPS\Member\Group::load( $comment->author()->member_group_id )->formattedName;
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Anonymous' ) and $comment->isAnonymous() and \IPS\Member::loggedIn()->modPermission('can_view_anonymous_posters') ):
$return .= <<<IPSCONTENT

							<a data-ipshover data-ipshover-width="370" data-ipshover-onclick href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url( 'reveal' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><span class="ipsAnonymousIcon" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_anonymously_reveal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span></a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<p class="ipsPhotoPanel__secondary">
						
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->shareableUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
{$comment->dateLine()}
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $comment->editLine() ):
$return .= <<<IPSCONTENT

							(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edited_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
			
IPSCONTENT;

if ( $menu = $comment->menu() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $menu->hasContent() ):
$return .= <<<IPSCONTENT

			{$menu}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $item->commentMultimodActions() ) ):
$return .= <<<IPSCONTENT

					<input type="checkbox" name="multimod[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" data-role="moderation" data-actions="
IPSCONTENT;

if ( $comment->canSplit() ):
$return .= <<<IPSCONTENT
split merge
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $comment->hidden() === -1 AND $comment->canUnhide() ):
$return .= <<<IPSCONTENT
unhide
IPSCONTENT;

elseif ( $comment->hidden() === 1 AND $comment->canUnhide() ):
$return .= <<<IPSCONTENT
approve
IPSCONTENT;

elseif ( $comment->canHide() ):
$return .= <<<IPSCONTENT
hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $comment->canDelete() ):
$return .= <<<IPSCONTENT
delete
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-state="
IPSCONTENT;

if ( $comment->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $comment->author()->member_id ):
$return .= <<<IPSCONTENT

				<!-- Expand mini profile -->
				<button class="ipsEntry__topButton ipsEntry__topButton--profile" type="button" aria-controls="mini-profile-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-expanded="false" data-ipscontrols aria-label="Toggle mini profile"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $comment->author()->member_id ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->miniProfile( $comment, $comment->$idField );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class="ipsEntry__post">
		
IPSCONTENT;

if ( $comment->author()->hasHighlightedReplies() || ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and $comment->isHighlighted() ) OR $comment->isFeatured() ):
$return .= <<<IPSCONTENT

			<ul class="ipsBadges">
				
IPSCONTENT;

if ( $comment->author()->hasHighlightedReplies() ):
$return .= <<<IPSCONTENT

					<li><span class="ipsBadge ipsBadge--highlightedGroup">
IPSCONTENT;

$return .= \IPS\Member\Group::load( $comment->author()->member_group_id )->name;
$return .= <<<IPSCONTENT
</span></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( $comment->isFeatured() ):
$return .= <<<IPSCONTENT

                    <li><span class="ipsBadge ipsBadge--positive">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_featured_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></li>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and $comment->isHighlighted() ):
$return .= <<<IPSCONTENT

					<li><span class="ipsBadge ipsBadge--popular">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_popular_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('mod_see_warn') and $comment->warning ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentWarned( $comment );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $comment->hidden() AND $comment->hidden() != -2 ):
$return .= <<<IPSCONTENT

			<div class="ipsEntry__hiddenMessage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

elseif ( $comment->hidden() == -2 ):
$return .= <<<IPSCONTENT

			<div class="ipsEntry__hiddenMessage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->deletedBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $comment->hidden() === 1 && $comment->author()->member_id == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<div class="i-margin-bottom_3"><strong class="i-color_warning"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'comment_awaiting_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsRichText ipsRichText--user" data-role="commentContent" data-controller="core.front.core.lightboxedImages">
			{$comment->content()}
			
IPSCONTENT;

if ( $comment->editLine() ):
$return .= <<<IPSCONTENT

				{$comment->editLine()}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

if ( ( $comment->hidden() !== 1 && \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and $comment->hasReactionBar() ) || ( $comment->hidden() === 1 && ( $comment->canUnhide() || $comment->canDelete() ) ) || ( $comment->hidden() === 0 and $item->canComment() and $editorName )  ):
$return .= <<<IPSCONTENT

		<div class="ipsEntry__footer">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/global/comment", "commentControls:before", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
<menu class="ipsEntry__controls" data-role="commentControls" data-ips-hook="commentControls">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/global/comment", "commentControls:inside-start", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $comment->hidden() === 1 && ( $comment->canUnhide() || $comment->canDelete() ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->canUnhide() ):
$return .= <<<IPSCONTENT

						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('unhide')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--positive" data-action="approveComment"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->canDelete() ):
$return .= <<<IPSCONTENT

						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('delete')->csrf()->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="deleteComment" data-updateondelete="#commentCount"><i class="fa-solid fa-xmark"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->canEdit() || $comment->canSplit() || $comment->canHide() ):
$return .= <<<IPSCONTENT

						<li>
							<a href="#elControlsCommentsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="elControlsCommentsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-appendto="#comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderator_tools', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
							<ul id="elControlsCommentsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="ipsMenu ipsMenu_auto ipsHide">
								
IPSCONTENT;

if ( $comment->canEdit() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $comment->mapped('first') and $comment->item()->canEdit() ):
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->url()->setQueryString( 'do', 'edit' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('edit'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="editComment">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $comment->canSplit() ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('split'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="splitComment" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $item::$title )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split_to_new', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Hideable' ) and $comment->canHide() ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('hide')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->hidden() === 0 and $item->canComment() and $editorName ):
$return .= <<<IPSCONTENT

						<li data-ipsquote-editor="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editorName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsquote-target="#comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsJS_show">
							<button class="cMultiQuote ipsHide" data-action="multiQuoteComment" data-ipstooltip data-ipsquote-multiquote data-mqid="mq
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'multiquote', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-plus"></i></button>
						</li>
						<li data-ipsquote-editor="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $editorName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsquote-target="#comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsJS_show">
							<a href="#" data-action="quoteComment" data-ipsquote-singlequote><i class="fa-solid fa-quote-left" aria-hidden="true"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'quote', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li class="ipsHide" data-role="commentLoading">
					<span class="ipsLoading ipsLoading--tiny"></span>
				</li>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/global/comment", "commentControls:inside-end", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT
</menu>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/global/comment", "commentControls:after", [ $item,$comment,$editorName,$app,$type,$class ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $comment->hidden() !== 1 && \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and $comment->hasReactionBar() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $comment );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->sharemenu( $comment );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function commentContainer( $item, $comment ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $comment::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

$itemClassSafe = str_replace( '\\', '_', mb_substr( $comment::$itemClass, 4 ) );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $comment->isIgnored() ):
$return .= <<<IPSCONTENT

	<div class='ipsEntry ipsEntry--ignored' id='elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ignoreCommentID='elComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ignoreUserID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<i class="fa-solid fa-user-slash"></i> 
IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ignoring_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 <a href='#elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' data-ipsMenu data-ipsMenu-menuID='elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' data-ipsMenu-appendTo='#elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
			<li class='ipsMenu_item' data-ipsMenuValue='showPost'><a href='#'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_this_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li class='ipsMenu_sep'><hr></li>
			<li class='ipsMenu_item' data-ipsMenuValue='stopIgnoring'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore&do=remove&id={$comment->author()->member_id}", null, "ignore", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stop_ignoring_posts_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
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

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<a id='findComment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></a>
<a id='comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></a>
<article id='elComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsEntry js-ipsEntry ipsEntry--simple ipsEntry--gallery-comment 
IPSCONTENT;

if ( ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and $comment->isHighlighted() ) OR $comment->isFeatured() ):
$return .= <<<IPSCONTENT
ipsEntry--popular
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $comment->isIgnored() ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $comment->author()->hasHighlightedReplies() ):
$return .= <<<IPSCONTENT
ipsEntry--highlighted
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $comment->hidden() OR $item->hidden() == -2 ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $comment->author()->hasHighlightedReplies() ):
$return .= <<<IPSCONTENT
data-memberGroup="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->author()->member_group_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery" )->comment( $item, $comment, $item::$formLangPrefix . 'comment', $item::$application, $item::$module, $itemClassSafe );
$return .= <<<IPSCONTENT

</article>
IPSCONTENT;

		return $return;
}

	function commentTableHeader( $comment, $image ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-flex i-gap_2'>
	<div class='i-flex_00 i-basis_60'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->thumbImage( $image->small_file_name, $image->caption, 'small', '', 'view_this', $image->url(), 'gallery_Images' );
$return .= <<<IPSCONTENT

	</div>
	<div class='i-flex_11'>
		<h3 class='ipsTitle ipsTitle--h4'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
		<p class='i-color_soft i-link-color_inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></p>
		
IPSCONTENT;

if ( $image->container()->allow_rating ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $image->rating );
$return .= <<<IPSCONTENT
 &nbsp;&nbsp;
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<span>
IPSCONTENT;

if ( $image->container()->allow_comments ):
$return .= <<<IPSCONTENT
&nbsp;&nbsp;
IPSCONTENT;

if ( !$image->comments ):
$return .= <<<IPSCONTENT
<span class='i-color_soft'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<i class='fa-solid fa-comment'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$image->comments ):
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedAlbumComment( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--gallery-album-comment' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
 data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class=''>
		
IPSCONTENT;

if ( $item->asNode()->coverPhoto('small') ):
$return .= <<<IPSCONTENT

			<figure class='ipsFigure ipsFigure--embed-hero'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->asNode()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
					<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->asNode()->coverPhoto('masked'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->asNode()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				</a>
			</figure>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$images = \IPS\Gallery\Image::getItemsWithPermission( array( array( 'image_album_id=?', $item->asNode()->id ) ), ( $item->asNode()->sort_options == 'title' ) ? 'image_caption ASC' : \IPS\gallery\Image::$databasePrefix . \IPS\gallery\Image::$databaseColumnMap[ $item->asNode()->sort_options ] . ' DESC', 19 );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $images ) ):
$return .= <<<IPSCONTENT

			<ul class='cGalleryEmbed_albumStrip i-background_2'>
				
IPSCONTENT;

foreach ( $images as $albumImage ):
$return .= <<<IPSCONTENT

					<li>
						<figure class='ipsFigure ipsFigure--ratio'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
								
IPSCONTENT;

if ( $albumImage->masked_file_name ):
$return .= <<<IPSCONTENT

									<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $albumImage->masked_file_name )->url;
$return .= <<<IPSCONTENT
' alt="" loading="lazy">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<i class="ipsFigure__icon"></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</a>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $albumImage, FALSE );
$return .= <<<IPSCONTENT

						</figure>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

$albumNode = $item->asNode();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $albumNode->count_imgs > 19 ):
$return .= <<<IPSCONTENT

					<li>
						<figure class='ipsFigure ipsFigure--ratio'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumNode->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'><span>+
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $albumNode->count_imgs - 19, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						</figure>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='i-margin-bottom_3 i-padding-top_3 i-link-color_inherit'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item );
$return .= <<<IPSCONTENT

		</div>

		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \count( $comment->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline i-margin-top_2'>
				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $comment, TRUE, 'small' );
$return .= <<<IPSCONTENT

				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedAlbumReview( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--gallery-album-review' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
 data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class=''>
		
IPSCONTENT;

if ( $item->asNode()->coverPhoto('small') ):
$return .= <<<IPSCONTENT

			<figure class='ipsFigure ipsFigure--embed-hero'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->asNode()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
					<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->asNode()->coverPhoto('masked'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->asNode()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				</a>
			</figure>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$images = \IPS\Gallery\Image::getItemsWithPermission( array( array( 'image_album_id=?', $item->asNode()->id ) ), ( $item->asNode()->sort_options == 'title' ) ? 'image_caption ASC' : \IPS\gallery\Image::$databasePrefix . \IPS\gallery\Image::$databaseColumnMap[ $item->asNode()->sort_options ] . ' DESC', 19 );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $images ) ):
$return .= <<<IPSCONTENT

			<ul class='cGalleryEmbed_albumStrip i-background_2'>
				
IPSCONTENT;

foreach ( $images as $albumImage ):
$return .= <<<IPSCONTENT

					<li>
						<figure class='ipsFigure ipsFigure--ratio'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
								
IPSCONTENT;

if ( $albumImage->masked_file_name ):
$return .= <<<IPSCONTENT

									<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $albumImage->masked_file_name )->url;
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<i class="ipsFigure__play"></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</a>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $albumImage, FALSE );
$return .= <<<IPSCONTENT

						</figure>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

$albumNode = $item->asNode();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $albumNode->count_imgs > 19 ):
$return .= <<<IPSCONTENT

					<li>
						<figure class='ipsFigure ipsFigure--ratio'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumNode->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'><span>+
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $albumNode->count_imgs - 19, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						</figure>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='i-margin-bottom_3 i-padding-top_3 i-link-color_inherit'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item );
$return .= <<<IPSCONTENT

		</div>

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'veryLarge', $comment->mapped('rating') );
$return .= <<<IPSCONTENT
 
		
IPSCONTENT;

if ( $comment->mapped('votes_total') ):
$return .= <<<IPSCONTENT

			<p>{$comment->helpfulLine()}</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \count( $comment->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline i-margin-top_2'>
				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $comment, TRUE, 'small' );
$return .= <<<IPSCONTENT

				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedAlbums( $albumItem, $albumNode, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--gallery-album' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
 data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $albumItem, $albumItem->mapped('title'), $albumItem->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class=''>
		
IPSCONTENT;

if ( $albumNode->coverPhoto('small') ):
$return .= <<<IPSCONTENT

			<figure class='ipsFigure ipsFigure--embed-hero'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumNode->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
					<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumNode->coverPhoto('masked'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumItem->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				</a>
			</figure>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$images = \IPS\Gallery\Image::getItemsWithPermission( array( array( 'image_album_id=?', $albumNode->id ) ), ( $albumNode->sort_options == 'title' ) ? 'image_caption ASC' : \IPS\gallery\Image::$databasePrefix . \IPS\gallery\Image::$databaseColumnMap[ $albumNode->sort_options ] . ' DESC', 4 );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $images ) ):
$return .= <<<IPSCONTENT

			<ul class='cGalleryEmbed_albumStrip i-background_2'>
				
IPSCONTENT;

foreach ( $images as $albumImage ):
$return .= <<<IPSCONTENT

					<li>
						<figure class='ipsFigure ipsFigure--ratio'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
								
IPSCONTENT;

if ( $albumImage->masked_file_name ):
$return .= <<<IPSCONTENT

									<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $albumImage->masked_file_name )->url;
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
								
IPSCONTENT;

elseif ( $albumImage->media ):
$return .= <<<IPSCONTENT

									<video preload="metadata" loading="lazy">
										<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $albumImage->original_file_name )->url;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumImage->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
									</video>
									<i class="ipsFigure__play"></i>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<i class="ipsFigure__icon"></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</a>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $albumImage, FALSE );
$return .= <<<IPSCONTENT

						</figure>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( $albumNode->count_imgs > 4 ):
$return .= <<<IPSCONTENT

					<li>
						<figure class='ipsFigure ipsFigure--ratio'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumNode->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'><span>+
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $albumNode->count_imgs - 4, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						</figure>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $desc = $albumItem->truncated(TRUE) ):
$return .= <<<IPSCONTENT

			<div class='ipsRichEmbed__content'>
				<div class='ipsRichText ipsTruncate_3'>
					{$desc}
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedItemStats( $albumItem, $albumNode->use_comments );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedImage( $item, $url, $image=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--gallery-image'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $item, $item->mapped('title'), $item->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<figure class='ipsFigure ipsFigure--contain' 
IPSCONTENT;

if ( !$item->media  ):
$return .= <<<IPSCONTENT
style="--_backdrop:url('
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->masked_file_name )->url;
$return .= <<<IPSCONTENT
')"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

if ( $item->media  ):
$return .= <<<IPSCONTENT

				<div class='ipsFigure__main'>
					<video data-controller="core.global.core.embeddedvideo" id="elGalleryVideo" data-role="video" controls preload="metadata" 
IPSCONTENT;

if ( $item->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->original_file_name )->url;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
						<embed wmode="opaque" autoplay="true" showcontrols="true" showstatusbar="true" showtracker="true" src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->original_file_name )->url;
$return .= <<<IPSCONTENT
" width="480" height="360" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
					</video>
				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($item->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
					<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->masked_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $item, FALSE );
$return .= <<<IPSCONTENT

		</figure>
	</div>
	<div class='ipsRichEmbed__content'>
		
IPSCONTENT;

if ( $desc = $item->truncated(TRUE) ):
$return .= <<<IPSCONTENT

			<div class='ipsRichEmbed__snippet'>
				{$desc}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $item->copyright ):
$return .= <<<IPSCONTENT

			<p class='i-color_soft i-margin-top_2'>
				&copy; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->copyright, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedItemStats( $item );
$return .= <<<IPSCONTENT

	</div>

	
IPSCONTENT;

if ( $item->directContainer() instanceof \IPS\gallery\Album ):
$return .= <<<IPSCONTENT

		<div class='ipsRichEmbed_moreInfo'>
			<h3 class='ipsMinorTitle ipsTruncate_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from_the_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 "<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->directContainer()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->directContainer()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>"</h3>
			
IPSCONTENT;

$images = \IPS\Gallery\Image::getItemsWithPermission( array( array( 'image_album_id=?', $item->album_id ) ), ( $item->directContainer()->sort_options == 'title' ) ? 'image_caption ASC' : $item::$databasePrefix . $item::$databaseColumnMap[ $item->directContainer()->sort_options ] . ' DESC', 9 );
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( \count( $images ) ):
$return .= <<<IPSCONTENT

				<ul class='cGalleryEmbed_albumStrip cGalleryEmbed_albumStrip_mini'>
					
IPSCONTENT;

foreach ( $images as $albumImage ):
$return .= <<<IPSCONTENT

						<li>
							<figure class='ipsFigure ipsFigure--ratio'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumImage->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
									
IPSCONTENT;

if ( $albumImage->masked_file_name ):
$return .= <<<IPSCONTENT

										<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $albumImage->masked_file_name )->url;
$return .= <<<IPSCONTENT
' loading="lazy" alt=''>
									
IPSCONTENT;

elseif ( $albumImage->media ):
$return .= <<<IPSCONTENT

										<video preload="metadata" loading="lazy">
											<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $albumImage->original_file_name )->url;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumImage->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
										</video>
										<i class="ipsFigure__play"></i>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<i class="ipsFigure__icon"></i>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</a>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $albumImage, FALSE );
$return .= <<<IPSCONTENT

							</figure>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

if ( $item->directContainer()->count_imgs > 9 ):
$return .= <<<IPSCONTENT

						<li>
							<figure class='ipsFigure ipsFigure--ratio'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->directContainer()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'><span>+
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $item->directContainer()->count_imgs - 9, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
							</figure>
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

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function embedImageComment( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--gallery-image-comment' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
 data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='i-padding_3'>
		<div class='i-margin-bottom_3'>
			<div>
				<figure class='ipsFigure'>
					
IPSCONTENT;

if ( $item->media  ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($item->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_video', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
							<video data-role="video" preload="metadata" loading="lazy" 
IPSCONTENT;

if ( $item->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->original_file_name )->url;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
							</video>
							<i class="ipsFigure__play"></i>
						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($item->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
							<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
						</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $item );
$return .= <<<IPSCONTENT

				</figure>
				<div class='i-padding-top_3'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item );
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>

		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \count( $comment->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline i-margin-top_2'>
				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $comment, TRUE, 'small' );
$return .= <<<IPSCONTENT

				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function embedImageReview( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--gallery-image-review' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
 data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='i-padding_3'>
		<div class='i-margin-bottom_3'>
			<div>
				<figure class='ipsFigure'>
					
IPSCONTENT;

if ( $item->media  ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($item->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_video', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
							<video data-role="video" preload="metadata" loading="lazy" 
IPSCONTENT;

if ( $item->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->original_file_name )->url;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
							</video>
							<i class="ipsFigure__play"></i>
						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($item->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
							<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $item->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
						</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $item );
$return .= <<<IPSCONTENT

				</figure>
				<div class='i-padding-top_3'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item );
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'veryLarge', $comment->mapped('rating') );
$return .= <<<IPSCONTENT
 
		
IPSCONTENT;

if ( $comment->mapped('votes_total') ):
$return .= <<<IPSCONTENT

			<p>{$comment->helpfulLine()}</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>

		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \count( $comment->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline i-margin-top_2'>
				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $comment, TRUE, 'small' );
$return .= <<<IPSCONTENT

				</li>
			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function featuredComment( $comment, $id, $commentLang='__defart_comment' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $comment['comment'] ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$idField = $comment['comment']::$databaseColumnId;
$return .= <<<IPSCONTENT

	<div class='i-padding_3 ipsEntry ipsEntry--recommended' data-commentID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment['comment'], 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

			<div class='i-flex i-justify-content_end'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $comment['comment'] );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsEntry__header ipsPhotoPanel ipsPhotoPanel--mini i-margin-top_3'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $comment['comment']->author(), 'mini', $comment['comment']->warningRef() );
$return .= <<<IPSCONTENT

			<div>
				<h3 class='ipsPhotoPanel__primary'>
					<strong>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $comment['comment']->author(), $comment['comment']->warningRef() );
$return .= <<<IPSCONTENT
</strong>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputationBadge( $comment['comment']->author() );
$return .= <<<IPSCONTENT

				</h3>
				<p class='ipsPhotoPanel__secondary'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment['comment']->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_inherit'>{$comment['comment']->dateLine()}</a>
					
IPSCONTENT;

if ( $comment['comment']->editLine() ):
$return .= <<<IPSCONTENT

						(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edited_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
			</div>
		</div>

		<div class='ipsRichText ipsTruncate_2'>{$comment['comment']->truncated( TRUE )}</div>

		
IPSCONTENT;

if ( $comment['note'] ):
$return .= <<<IPSCONTENT

			<div class='ipsEntry__recommendedNote'>
				<p class='ipsRichText'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment['note'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

if ( isset( $comment['featured_by'] ) ):
$return .= <<<IPSCONTENT

					<p class='i-color_soft i-margin-top_2'>
IPSCONTENT;

$htmlsprintf = array($comment['featured_by']->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recommended_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

elseif ( isset( $comment['featured_by'] ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$htmlsprintf = array($comment['featured_by']->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recommended_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<hr class='ipsHr'>
		<div>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment['comment']->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action='goToComment' class='ipsButton ipsButton--text ipsButton--small ipsButton--wide'>
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->get( $commentLang )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_this_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-angle-right'></i></a>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function featuredComments( $comments, $url, $titleLang='recommended_replies', $commentLang='__defart_comment' ) {
		$return = '';
		$return .= <<<IPSCONTENT


<section data-controller='core.front.core.recommendedComments' data-url='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRecommendedComments 
IPSCONTENT;

if ( !\count( $comments ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	<div data-role="recommendedComments">
		<header class='ipsBox__header'>
			<h2>
IPSCONTENT;

$val = "{$titleLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'gallery-featured-comments' );
$return .= <<<IPSCONTENT

		</header>
		<div class="ipsCarousel" id="gallery-featured-comments" tabindex="0">
			
IPSCONTENT;

if ( \count( $comments ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $comments AS $id => $comment ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery" )->featuredComment( $comment, $id, $commentLang );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</section>
IPSCONTENT;

		return $return;
}

	function manageFollowNodeRow( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$contentItemClass = $row::$contentItemClass;
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
" data-controller='core.front.system.manageFollowed' data-followID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_area'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_followData['follow_rel_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $contentItemClass::containerUnread( $row ) ):
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
		<figure class='ipsData__image'>
			
IPSCONTENT;

if ( $coverImage = $row->coverPhoto('small')  ):
$return .= <<<IPSCONTENT

				<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverImage, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</figure>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<div class='ipsData__title'>
					
IPSCONTENT;

if ( $row->_locked ):
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-lock"></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<h4><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
				</div>
				<div class='ipsData__desc ipsTruncate_2'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_description, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</div>
				<ul class='ipsList ipsList--inline i-color_soft'>
					
IPSCONTENT;

if ( $row->_items ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$pluralize = array( $row->_items ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_content_items', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $row->_commentsForDisplay ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$pluralize = array( $row->_commentsForDisplay ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_content_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $row->_reviews ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$pluralize = array( $row->_reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'node_content_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>

				
IPSCONTENT;

if ( \count( $row->_latestImages ) ):
$return .= <<<IPSCONTENT

					<ul class='ipsGrid ipsGrid--album-thumbs i-basis_40 cGalleryManagedAlbumThumbs'>
						
IPSCONTENT;

foreach ( \array_slice( iterator_to_array( $row->_latestImages ), 0, 10, true ) as $image  ):
$return .= <<<IPSCONTENT

							<li>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsThumb'>
									<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
								</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

	function manageFollowRow( $table, $headers, $rows, $includeFirstCommentInCommentCount=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

		<li class="ipsData__item 
IPSCONTENT;

if ( $row->unread() ):
$return .= <<<IPSCONTENT
ipsData__item--unread
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
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
 
IPSCONTENT;

if ( $row->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
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
' 
IPSCONTENT;

if ( $row->unread() ):
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
			<div class='ipsData__image'>
				
IPSCONTENT;

$image = \IPS\File::get( 'gallery_Images', $row->small_file_name )->url;
$return .= <<<IPSCONTENT

				<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
			</div>
			<div class='ipsData__content'>
				<div class='ipsData__main'>
					<div class='ipsData__title'>
						<div class='ipsBadges'>
							
IPSCONTENT;

if ( $row->mapped('locked') ):
$return .= <<<IPSCONTENT

								<span><i class="fa-solid fa-lock"></i></span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


							
IPSCONTENT;

foreach ( $row->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
							
IPSCONTENT;

if ( $row->prefix() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $row->prefix( TRUE ), $row->prefix() );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						
						<h4>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
								
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
					</div>
					
IPSCONTENT;

if ( method_exists( $row, 'tableDescription' ) ):
$return .= <<<IPSCONTENT

						<div class='ipsData__desc ipsTruncate_2'>
							{$row->tableDescription()}
						</div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<div class="ipsData__meta">
							
IPSCONTENT;

$htmlsprintf = array($row->author()->link( $row->warningRef() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $row->__get( $row::$databaseColumnMap['date'] ) instanceof \IPS\DateTime ) ? $row->__get( $row::$databaseColumnMap['date'] ) : \IPS\DateTime::ts( $row->__get( $row::$databaseColumnMap['date'] ) );$return .= $val->html();
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						</div>				
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>

				<div class='cFollowedContent_anon'>
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

				<div class='i-basis_180 cFollowedContent_info'>
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

				<div class='i-basis_180 i-text-align_center cFollowedContent_manage'>
					
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
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( $row->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

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

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function nsfwOverlay( $image, $showButton=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw and !isset(  (\IPS\Request::i()->cookie['nsfwImageOptIn']) ) and $image and $image->nsfw ):
$return .= <<<IPSCONTENT

<div class='ipsNsfwOverlay 
IPSCONTENT;

if ( !$showButton ):
$return .= <<<IPSCONTENT
ipsNsfwOverlay--no-content
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
    
IPSCONTENT;

if ( $showButton ):
$return .= <<<IPSCONTENT

        <div>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_image_is_nsfw', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
        <button type='button'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_nsfw_view_anyway', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
    
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

	function profileAlbumTable( $table, $headers, $rows, $quickSearch ) {
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
' data-pageParam='albumPage' data-controller='core.global.core.table
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT
,core.front.core.moderation
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	<div data-role="tablePagination" class='i-margin-bottom_3 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, 'albumPage' );
$return .= <<<IPSCONTENT

	</div>

	<ol class='' data-role='tableRows' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	</ol>
</div>
IPSCONTENT;

		return $return;
}

	function review( $item, $review, $editorName, $app, $type ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id="review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap" data-controller="core.front.core.comment" data-feedid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commentapp="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-commenttype="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
-review" data-commentid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-quotedata="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( array('userid' => $review->author()->member_id, 'username' => $review->author()->name, 'timestamp' => $review->mapped('date'), 'contentapp' => $app, 'contenttype' => $type, 'contentid' => $item->id, 'contentcommentid' => $review->id) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsEntry__content js-ipsEntry__content" 
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $review, 'IPS\Content\IntersectionViewTracking' ) AND $hash=$review->getViewTrackingHash() ):
$return .= <<<IPSCONTENT
 data-view-hash="{$hash}" data-view-tracking-data="
IPSCONTENT;

$return .= base64_encode(json_encode( $review->getViewTrackingData() ));
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<header class="ipsEntry__header">
		<div class="ipsEntry__header-align">
			<div class="ipsPhotoPanel">
				<div class="ipsAvatarStack">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $review->author(), 'fluid', $review->warningRef() );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $review->author()->canHaveAchievements() and \IPS\core\Achievements\Rank::show() and $rank = $review->author()->rank() ):
$return .= <<<IPSCONTENT

						{$rank->html( 'ipsAvatarStack__rank' )}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsPhotoPanel__text">
					<div class="ipsEntry__username">
						<h3>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $review->author(), $review->warningRef(), NULL, $review->isAnonymous() );
$return .= <<<IPSCONTENT
</h3>
						
IPSCONTENT;

if ( !$review->isAnonymous() ):
$return .= <<<IPSCONTENT

							<span class="ipsEntry__group">
IPSCONTENT;

$return .= \IPS\Member\Group::load( $review->author()->member_group_id )->formattedName;
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<p class="ipsPhotoPanel__secondary">
						
IPSCONTENT;

if ( $review->mapped('date') ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$val = ( $review->mapped('date') instanceof \IPS\DateTime ) ? $review->mapped('date') : \IPS\DateTime::ts( $review->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unknown_date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->editLine() ):
$return .= <<<IPSCONTENT

							(
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edited_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
				</div>
			</div>
			
IPSCONTENT;

if ( \count( $item->reviewMultimodActions() ) ):
$return .= <<<IPSCONTENT

				<input type="checkbox" name="multimod[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" value="1" data-role="moderation" data-actions="
IPSCONTENT;

if ( $review->hidden() === -1 AND $review->canUnhide() ):
$return .= <<<IPSCONTENT
unhide
IPSCONTENT;

elseif ( $review->hidden() === 1 AND $review->canUnhide() ):
$return .= <<<IPSCONTENT
approve
IPSCONTENT;

elseif ( $review->canHide() ):
$return .= <<<IPSCONTENT
hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $review->canDelete() ):
$return .= <<<IPSCONTENT
delete
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-state="
IPSCONTENT;

if ( $review->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<a href="#elControlsReviews_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="ipsEntry__topButton ipsEntry__topButton--ellipsis" id="elControlsReviews_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-appendto="#review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap"><i class="fa-solid fa-ellipsis"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
			<ul id="elControlsReviews_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="ipsMenu ipsMenu_narrow ipsHide">
				
IPSCONTENT;

if ( $review->canReportOrRevoke() === TRUE ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('report'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id or \IPS\Helpers\Form\Captcha::supportsModal() ):
$return .= <<<IPSCONTENT
data-ipsdialog data-ipsdialog-remotesubmit data-ipsdialog-size="medium" data-ipsdialog-flashmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_submit_success', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-action="reportComment" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li class="ipsMenu_item">
					<a class="i-color_inherit" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->url()->setQueryString( array( 'do' => 'findReview', 'review' => $review->id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'share_this_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-content="#elShareReview_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'share_this_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" id="elShareReview_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="shareComment">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'share_this_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>

				
IPSCONTENT;

if ( $review->canEdit() || ( $review->hidden() == -2 AND \IPS\Member::loggedIn()->modPermission('can_manage_deleted_content') ) || ( \IPS\IPS::classUsesTrait( $review, 'IPS\Content\Hideable' ) && ( ( !$review->hidden() and $review->canHide() ) || ( $review->hidden() and $review->canUnhide() ) ) ) || $review->canDelete() ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_sep"><hr></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( $review->canEdit() ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('edit'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="editComment">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
				
IPSCONTENT;

if ( $review->hidden() == -2 AND \IPS\Member::loggedIn()->modPermission('can_manage_deleted_content') ):
$return .= <<<IPSCONTENT

					<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('restore')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmsubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_visible_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_visible', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('restore')->csrf()->setQueryString( 'restoreAsHidden', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmsubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_hidden_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'restore_as_hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('delete')->csrf()->setQueryString( 'immediately', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmsubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_immediately_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_immediately', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $review, 'IPS\Content\Hideable' ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !$review->hidden() and $review->canHide() ):
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item"><a href="
IPSCONTENT;

if ( $review::$hideLogKey ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('hide'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('hide')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

elseif ( $review->hidden() and $review->canUnhide() ):
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('unhide')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unhide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $review->canDelete() ):
$return .= <<<IPSCONTENT

						<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('delete')->csrf()->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="deleteComment" data-updateondelete="#reviewCount">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

if ( $review->author()->member_id ):
$return .= <<<IPSCONTENT

				<!-- Expand mini profile -->
				<button class="ipsEntry__topButton ipsEntry__topButton--profile" type="button" aria-controls="mini-profile-review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-expanded="false" data-ipscontrols aria-label="Toggle mini profile"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $review->author()->member_id ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->miniProfile( $review, $review->id );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class="ipsEntry__post">
		
IPSCONTENT;

if ( $review->hidden() AND $review->hidden() != -2 ):
$return .= <<<IPSCONTENT

			<div class="ipsEntry__hiddenMessage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

elseif ( $review->hidden() == -2 ):
$return .= <<<IPSCONTENT

			<div class="ipsEntry__hiddenMessage">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->deletedBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsRating ipsRating_large i-margin-bottom_3">
			<ul>
				
IPSCONTENT;

foreach ( range( 1, \intval( \IPS\Settings::i()->reviews_rating_out_of ) ) as $i ):
$return .= <<<IPSCONTENT

					<li class="
IPSCONTENT;

if ( $review->mapped('rating') >= $i ):
$return .= <<<IPSCONTENT
ipsRating_on
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsRating_off
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
						<i class="fa-solid fa-star"></i>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		   
IPSCONTENT;

if ( $review->mapped('votes_total') ):
$return .= <<<IPSCONTENT
<strong>{$review->helpfulLine()}</strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div id="review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsRichText ipsRichText--user" data-role="commentContent" data-controller="core.front.core.lightboxedImages">
			{$review->content()}
			
IPSCONTENT;

if ( $review->editLine() ):
$return .= <<<IPSCONTENT

				{$review->editLine()}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>

		
IPSCONTENT;

if ( $review->hasAuthorResponse() ):
$return .= <<<IPSCONTENT

			<div class="ipsReviewResponse i-padding_3 i-margin-bottom_3 i-background_2">
				<div class="i-flex i-align-items_center i-justify-content_space-between i-margin-bottom_2">
					<h4 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_response_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					
IPSCONTENT;

if ( $review->canEditResponse() OR $review->canDeleteResponse() ):
$return .= <<<IPSCONTENT

						<a href="#elControlsReviews_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_response_menu" class="ipsEntry__topButton ipsEntry__topButton--ellipsis" id="elControlsReviews_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_response" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-appendto="#review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap"><i class="fa-solid fa-ellipsis"></i></a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div data-role="reviewResponse" class="ipsRichText" data-controller="core.front.core.lightboxedImages">{$review->mapped('author_response')}</div>

				
IPSCONTENT;

if ( $review->canEditResponse() OR $review->canDeleteResponse() ):
$return .= <<<IPSCONTENT

					<ul class="ipsMenu ipsMenu_narrow ipsHide" id="elControlsReviews_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_response_menu">
						
IPSCONTENT;

if ( $review->canEditResponse() ):
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('editResponse'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_author_respond', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->canDeleteResponse() ):
$return .= <<<IPSCONTENT

							<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('deleteResponse')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $review->hidden() !== 1 ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id and ( !$review->mapped('votes_data') or !array_key_exists( \IPS\Member::loggedIn()->member_id, json_decode( $review->mapped('votes_data'), TRUE ) ) ) and $review->author()->member_id != \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				<div class="i-flex i-align-items_center i-flex-wrap_wrap i-gap_2 i-margin-top_3">
					<div class="i-font-weight_500 i-color_hard i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'did_you_find_this_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
					<div class="ipsButtons i-font-size_-2">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('rate')->setQueryString( 'helpful', TRUE )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-color_positive" data-action="rateReview"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'yes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('rate')->setQueryString( 'helpful', FALSE )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit i-color_negative" data-action="rateReview"><i class="fa-solid fa-xmark"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

	
IPSCONTENT;

if ( ( \IPS\Member::loggedIn()->member_id and ( !$review->mapped('votes_data') or !array_key_exists( \IPS\Member::loggedIn()->member_id, json_decode( $review->mapped('votes_data'), TRUE ) ) ) ) || $review->canEdit() || $review->canDelete() || $review->canHide() || $review->canUnhide() || ( $review->hidden() !== 1 && \IPS\IPS::classUsesTrait( $review, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and $review->hasReactionBar() ) ):
$return .= <<<IPSCONTENT

		<div class="ipsEntry__footer">
			
IPSCONTENT;

if ( $review->canEdit() || $review->canDelete() || $review->canHide() || $review->canUnhide() || ( $review->hidden() !== 1 && $review->canRespond() )  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/global/review", "commentControls:before", [ $item,$review,$editorName,$app,$type ] );
$return .= <<<IPSCONTENT
<menu class="ipsEntry__controls" data-role="commentControls" data-ips-hook="commentControls">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/global/review", "commentControls:inside-start", [ $item,$review,$editorName,$app,$type ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $review->hidden() === 1 && ( $review->canUnhide() || $review->canDelete() ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->canUnhide() ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('unhide')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="approveComment"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->canDelete() ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('delete')->csrf()->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="deleteComment" data-updateondelete="#commentCount"><i class="fa-solid fa-xmark"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $review->canEdit() || $review->canSplit() ):
$return .= <<<IPSCONTENT

							<li>
								<a href="#elControlsReviewsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" id="elControlsReviewsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-appendto="#review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
								<ul id="elControlsReviewsSub_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="ipsMenu ipsMenu_narrow ipsHide">
									
IPSCONTENT;

if ( $review->canEdit() ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $review->mapped('first') and $review->item()->canEdit() ):
$return .= <<<IPSCONTENT

											<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->url()->setQueryString( 'do', 'edit' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('edit'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="editComment">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $review->canSplit() ):
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('split'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="splitComment" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $item::$title )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split_to_new', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'split', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</ul>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( $review->hidden() !== 1 && $review->canRespond() ):
$return .= <<<IPSCONTENT

                        <li>
                            <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->url('respond'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="respond" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_author_respond', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'review_author_respond', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
                        </li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li class="ipsHide" data-role="commentLoading">
						<span class="ipsLoading ipsLoading--tiny"></span>
					</li>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/global/review", "commentControls:inside-end", [ $item,$review,$editorName,$app,$type ] );
$return .= <<<IPSCONTENT
</menu>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/global/review", "commentControls:after", [ $item,$review,$editorName,$app,$type ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $review->hidden() !== 1 && \IPS\IPS::classUsesTrait( $review, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $review );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->sharemenu( $review );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function reviewContainer( $item, $review ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $review::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $review->isIgnored() ):
$return .= <<<IPSCONTENT

	<div class='ipsEntry ipsEntry--ignored'>
		<i class="fa-solid fa-user-slash"></i> 
IPSCONTENT;

$sprintf = array($review->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ignoring_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<a id='review-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></a>
	<a id='findReview-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></a>
	<article id="elReview_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsEntry ipsEntry--simple ipsEntry--review js-ipsEntry 
IPSCONTENT;

if ( $review->hidden() OR $item->hidden() == -2 ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->review( $item, $review, $item::$formLangPrefix . 'review', $item::$application, $item::$module );
$return .= <<<IPSCONTENT

	</article>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function searchResultAlbumCommentSnippet( $indexData, $itemData, $images, $url, $reviewRating, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $condensed ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $images ) ):
$return .= <<<IPSCONTENT

		<ul class='cGalleryAlbums_recent cGalleryAlbums_recent--small' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

foreach ( $images as $k => $image ):
$return .= <<<IPSCONTENT

				<li data-imageId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					<figure class='ipsFigure'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
							
IPSCONTENT;

if ( $image->small_file_name ):
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
							
IPSCONTENT;

elseif ( $image->media ):
$return .= <<<IPSCONTENT

								<video loading="lazy" 
IPSCONTENT;

if ( $image->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 preload="metadata"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->original_file_name )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$image->masked_file_name  ):
$return .= <<<IPSCONTENT
#t=1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
								</video>
								<i class="ipsFigure__play"></i>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="ipsFigure__icon"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image, FALSE );
$return .= <<<IPSCONTENT

					</figure>
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

	<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--gallery'>
		<figure class='ipsFigure ipsFigure--ratio'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($itemData['album_name']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_album', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
				
IPSCONTENT;

if ( \count( $images ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$firstImage = array_shift( $images );
$return .= <<<IPSCONTENT

					<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $firstImage->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $firstImage->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</a>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $firstImage, FALSE );
$return .= <<<IPSCONTENT

		</figure>
	</div>
	<div class='ipsStreamItem__content-content ipsStreamItem__content-content--gallery'>
		
IPSCONTENT;

if ( $reviewRating !== NULL ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'medium', $reviewRating );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsStream__comment'>
			
IPSCONTENT;

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

				<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_3'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class='ipsRichText' data-searchable data-findTerm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

$return .= \IPS\Content\Search\Result::preDisplay( $indexData['index_content'] );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<ul class='cGallerySearchAlbumThumbs cGallerySearchExpanded'>
			
IPSCONTENT;

foreach ( $images as $image  ):
$return .= <<<IPSCONTENT

				<li>
					<figure class='ipsFigure ipsFigure--ratio'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
							<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
						</a>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image, FALSE );
$return .= <<<IPSCONTENT

					</figure>
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

		return $return;
}

	function searchResultAlbumSnippet( $indexData, $itemData, $images, $url, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $condensed ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $images ) ):
$return .= <<<IPSCONTENT

		<ul class='cGalleryAlbums_recent cGalleryAlbums_recent--small' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

foreach ( $images as $k => $image ):
$return .= <<<IPSCONTENT

				<li data-imageId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					<figure class='ipsFigure'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
							
IPSCONTENT;

if ( $image->small_file_name ):
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
							
IPSCONTENT;

elseif ( $image->media ):
$return .= <<<IPSCONTENT

								<video loading="lazy"
IPSCONTENT;

if ( $image->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 preload="metadata"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->original_file_name )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$image->masked_file_name  ):
$return .= <<<IPSCONTENT
#t=1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
								</video>
								<i class="ipsFigure__play"></i>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="ipsFigure__icon"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image, FALSE );
$return .= <<<IPSCONTENT

					</figure>
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

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

		<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_3'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class='ipsRichText' data-searchable data-findTerm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

$return .= \IPS\Content\Search\Result::preDisplay( $indexData['index_content'] );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \count($images) ):
$return .= <<<IPSCONTENT

		<ul class='cGalleryAlbums_recent' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

foreach ( $images as $k => $image ):
$return .= <<<IPSCONTENT

				<li data-imageId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					<figure class='ipsFigure'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
							
IPSCONTENT;

if ( $image->small_file_name ):
$return .= <<<IPSCONTENT

								<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="" loading="lazy">
							
IPSCONTENT;

elseif ( $image->media ):
$return .= <<<IPSCONTENT

								<video loading="lazy"
IPSCONTENT;

if ( $image->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 preload="metadata"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->original_file_name )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$image->masked_file_name  ):
$return .= <<<IPSCONTENT
#t=1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
								</video>
								<i class="ipsFigure__play"></i>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class="ipsFigure__icon"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<span class="ipsInvisible">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						</a>
						
IPSCONTENT;

if ( ( $image->directContainer()->allow_comments && $image->container()->allow_comments && count($image->comments()) > 0 ) ):
$return .= <<<IPSCONTENT

							<div class='ipsFigure__footer'>
								<span><i class='fa-solid fa-comment'></i> 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $image->comments() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image, FALSE );
$return .= <<<IPSCONTENT

					</figure>
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

IPSCONTENT;

		return $return;
}

	function searchResultCommentSnippet( $indexData, $itemData, $image, $url, $reviewRating, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$imageObj = \IPS\gallery\Image::constructFromData( $itemData, FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--gallery'>
		<figure class='ipsFigure ipsFigure--ratio'>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($itemData['image_caption']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
				<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData['image_caption'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
			</a>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $imageObj, FALSE );
$return .= <<<IPSCONTENT

		</figure>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$condensed ):
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-content ipsStreamItem__content-content--gallery'>
		
IPSCONTENT;

if ( $reviewRating !== NULL ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'medium', $reviewRating );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsStream__comment'>
			
IPSCONTENT;

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

				<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_3'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class='ipsRichText' data-searchable data-findTerm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

$return .= \IPS\Content\Search\Result::preDisplay( $indexData['index_content'] );
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

	function searchResultImageSnippet( $indexData, $itemData, $albumData, $image, $url, $condensed ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$imageObj = \IPS\gallery\Image::constructFromData( $itemData, FALSE );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $condensed ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

		<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--gallery'>
			<figure class='ipsFigure ipsFigure--ratio'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($indexData['index_title']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
					<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData['image_caption'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				</a>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $imageObj, FALSE );
$return .= <<<IPSCONTENT

			</figure>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $image ):
$return .= <<<IPSCONTENT

		<div class='ipsStreamItem__content-thumb ipsStreamItem__content-thumb--gallery' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			<figure class='ipsFigure ipsFigure--ratio'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($indexData['index_title']); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
					<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image )->url;
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $indexData['index_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				</a>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $imageObj, FALSE );
$return .= <<<IPSCONTENT

			</figure>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsStreamItem__content-content ipsStreamItem__content-content--gallery'>
		
IPSCONTENT;

if ( $albumData ):
$return .= <<<IPSCONTENT

			<p class='i-font-weight_500'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from_the_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=browse&album={$albumData['album_id']}", null, "gallery_album", array( $albumData['album_name_seo'] ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $albumData['album_name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( trim( $indexData['index_content'] ) !== '' ):
$return .= <<<IPSCONTENT

			<div 
IPSCONTENT;

if ( !( \IPS\Dispatcher::i()->application->directory == 'core' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key == 'search' ) ):
$return .= <<<IPSCONTENT
class='ipsRichText ipsTruncate_3'
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
class='ipsRichText' data-searchable data-findTerm
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

$return .= \IPS\Content\Search\Result::preDisplay( $indexData['index_content'] );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $itemData['image_copyright'] ):
$return .= <<<IPSCONTENT

			<p class='i-color_soft'>&copy; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemData['image_copyright'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

		return $return;
}}