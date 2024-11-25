<?php
namespace IPS\Theme;
class class_forums_front_topics extends \IPS\Theme\Template
{	function activity( $topic, $location ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$lastPoster = \IPS\Member::load( $topic->last_poster_id);
$return .= <<<IPSCONTENT


IPSCONTENT;

$members = $topic->topPosters(4);
$return .= <<<IPSCONTENT


IPSCONTENT;

$busy = $topic->showSummaryFeature('popularDays') ? $topic->popularDays(4) : FALSE;
$return .= <<<IPSCONTENT


IPSCONTENT;

$reacted = $topic->showSummaryFeature('topPost') ? $topic->topReactedPosts(3) : FALSE;
$return .= <<<IPSCONTENT


IPSCONTENT;

$helpful = $topic->showSummaryFeature('helpful') ? $topic->helpfulPosts(3) : FALSE;
$return .= <<<IPSCONTENT


IPSCONTENT;

$images = $topic->showSummaryFeature('uploads') ? $topic->imageAttachments(4) : FALSE;
$return .= <<<IPSCONTENT


IPSCONTENT;

$hasAnyContent = (bool) ($members or $reacted or $helpful or $images);
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $location == 'sidebar' ):
$return .= <<<IPSCONTENT

<div class="ipsWidget cTopicOverviewContainer">
	<div class="cTopicOverview cTopicOverview--sidebar ipsResponsive_showDesktop" 
IPSCONTENT;

if ( $hasAnyContent ):
$return .= <<<IPSCONTENT
data-controller='forums.front.topic.activity'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

<div class="ipsBox ipsPull cTopicOverviewContainer 
IPSCONTENT;

if ( $topic->showSummaryOnDesktop() != 'post' ):
$return .= <<<IPSCONTENT
ipsResponsive_hideDesktop
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( ! $topic->showSummaryOnMobile() ):
$return .= <<<IPSCONTENT
ipsResponsive_hidePhone
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<div class="cTopicOverview cTopicOverview--main" 
IPSCONTENT;

if ( $hasAnyContent ):
$return .= <<<IPSCONTENT
data-controller='forums.front.topic.activity'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="cTopicOverview__content">
			<ul class='cTopicOverview__item cTopicOverview__item--stats'>
				
IPSCONTENT;

if ( $topic->posts > 1 ):
$return .= <<<IPSCONTENT

					<li>
						<span class="i-color_soft i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replies', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						<span class="i-color_hard i-font-weight_700 i-font-size_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $topic->posts-1 );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li>
					<span class="i-color_soft i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'views', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span class="i-color_hard i-font-weight_700 i-font-size_3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $topic->views );
$return .= <<<IPSCONTENT
</span>
				</li>
				<li>
					<span class="i-color_soft i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topicactivity_created', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span class="i-color_hard i-font-weight_700 i-font-size_3">
IPSCONTENT;

$val = ( $topic->start_date instanceof \IPS\DateTime ) ? $topic->start_date : \IPS\DateTime::ts( $topic->start_date );$return .= $val->html(TRUE, TRUE);
$return .= <<<IPSCONTENT
</span>
				</li>
				<li>
					<span class="i-color_soft i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'last_reply', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span class="i-color_hard i-font-weight_700 i-font-size_3">
IPSCONTENT;

$val = ( $topic->last_post instanceof \IPS\DateTime ) ? $topic->last_post : \IPS\DateTime::ts( $topic->last_post );$return .= $val->html(TRUE, TRUE);
$return .= <<<IPSCONTENT
</span>
				</li>
			</ul>
			
IPSCONTENT;

if ( $members ):
$return .= <<<IPSCONTENT

				<div class='cTopicOverview__item cTopicOverview__item--topPosters'>
					<h4 class='cTopicOverview__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topicactivity_topposters', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<ul class='cTopicOverview__dataList'>
						
IPSCONTENT;

foreach ( $members as $data ):
$return .= <<<IPSCONTENT

							<li class="ipsPhotoPanel">
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $data['member'], 'fluid' );
$return .= <<<IPSCONTENT

								<div class='ipsPhotoPanel__text'>
									<strong class='ipsPhotoPanel__primary i-color_hard'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['member']->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['member']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></strong>
									<span class='ipsPhotoPanel__secondary'>
IPSCONTENT;

$pluralize = array( $data['count'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topicactivity_number_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
								</div>
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

if ( $busy ):
$return .= <<<IPSCONTENT

				<div class='cTopicOverview__item cTopicOverview__item--popularDays'>
					<h4 class='cTopicOverview__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topicactivity_populardays', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<ul class='cTopicOverview__dataList'>
						
IPSCONTENT;

foreach ( $busy as $row ):
$return .= <<<IPSCONTENT

							<li>
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->shareableUrl( $row['commentId'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow" class='cTopicOverview__dataItem i-grid i-color_soft'>
									<span class='i-font-weight_bold i-color_hard'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['date']->dayAndShortMonth(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['date']->format('Y'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									<span>
IPSCONTENT;

$pluralize = array( $row['count'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topicactivity_number_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
								</a>
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

if ( $reacted ):
$return .= <<<IPSCONTENT

				<div class='cTopicOverview__item cTopicOverview__item--popularPosts'>
					<h4 class='cTopicOverview__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topicactivity_popularposts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<ul class='cTopicOverview__dataList'>
						
IPSCONTENT;

foreach ( $reacted as $data ):
$return .= <<<IPSCONTENT

							<li>
								<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
									<span class='ipsUserPhoto'>
										<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->author()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
									</span>
									<div class="ipsPhotoPanel__text">
										<h5 class='ipsPhotoPanel__primary i-color_hard'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h5>
										<p class='ipsPhotoPanel__secondary'>
											<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
IPSCONTENT;

$val = ( $data['comment']->mapped('date') instanceof \IPS\DateTime ) ? $data['comment']->mapped('date') : \IPS\DateTime::ts( $data['comment']->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
										</p>
									</div>
								</div>
								<p class='i-margin-top_2 ipsRichText ipsTruncate_3'>
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->truncated(true, 200), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								</p>
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

if ( $helpful ):
$return .= <<<IPSCONTENT

				<div class='cTopicOverview__item cTopicOverview__item--helpfulPosts'>
					<h4 class='cTopicOverview__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topicactivity_helpfulposts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<ul class='cTopicOverview__dataList'>
						
IPSCONTENT;

foreach ( $helpful as $data ):
$return .= <<<IPSCONTENT

							<li>
								<div class='ipsPhotoPanel ipsPhotoPanel--tiny'>
									<span class='ipsUserPhoto'>
										<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->author()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
									</span>
									<div class="ipsPhotoPanel__text">
										<h5 class="ipsPhotoPanel__primary i-color_hard">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h5>
										<p class="ipsPhotoPanel__secondary">
											<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->shareableUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
IPSCONTENT;

$val = ( $data['comment']->mapped('date') instanceof \IPS\DateTime ) ? $data['comment']->mapped('date') : \IPS\DateTime::ts( $data['comment']->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
										</p>
									</div>
								</div>
								<p class='i-margin-top_2 ipsRichText ipsTruncate_3'>
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['comment']->truncated(true, 200), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								</p>
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

if ( $images ):
$return .= <<<IPSCONTENT

				<div class='cTopicOverview__item cTopicOverview__item--images'>
					<h4 class='cTopicOverview__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topicactivity_images', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
					<ul class='cTopicOverview__imageGrid' data-controller='core.front.core.lightboxedImages'>
						
IPSCONTENT;

foreach ( $images as $row ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$image = \IPS\File::get( 'core_Attachment', ( $row['attach_thumb_location'] ) ? $row['attach_thumb_location'] : $row['attach_location'] )->url;
$return .= <<<IPSCONTENT

							<li class='cTopicOverview__image'>
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['commentUrl'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow" class='ipsThumb'>
									<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
								</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $hasAnyContent ):
$return .= <<<IPSCONTENT

			<button type="button" data-action='toggleOverview' class='cTopicOverview__toggle'><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topicactivity_expand', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-chevron-down'></i></button>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function expertBadge( $topic, $comment, $viewerIsFollowingTheseExperts ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$isFollowing = ( is_array( $viewerIsFollowingTheseExperts ) and count( $viewerIsFollowingTheseExperts ) and in_array( $comment->author()->member_id, $viewerIsFollowingTheseExperts ) );
$return .= <<<IPSCONTENT

<span class="ipsBadge ipsBadge--expert">
    <i class="fa-solid fa-graduation-cap"></i>
    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forums_is_expert', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( $comment->author()->member_id != \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

        <span data-followApp='core' data-followArea='member' data-followID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-buttonSize="small" data-controller='core.front.core.followButton'>
            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->memberFollowButton( 'core', 'member', $comment->author()->member_id, 0, 'small', $isFollowing );
$return .= <<<IPSCONTENT

        </span>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</span>


IPSCONTENT;

		return $return;
}

	function post( $item, $comment, $editorName, $app, $type, $class='', $viewerIsFollowingTheseExperts=[] ) {
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

$return .= \IPS\Theme\Template::htmlspecialchars( json_encode( array('userid' => $comment->author()->member_id, 'username' => $comment->author()->name, 'timestamp' => $comment->mapped('date'), 'contentapp' => $comment::$application, 'contenttype' => $type, 'contentid' => $item->tid, 'contentclass' => $class, 'contentcommentid' => $comment->$idField) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

if ( \IPS\Platform\Bridge::i()->featureIsEnabled( 'topic_summary' ) AND \IPS\Settings::i()->ips_topic_summary_enabled ):
$return .= <<<IPSCONTENT
 data-post-score="
IPSCONTENT;

$return .= json_encode( $comment->post_score );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $comment->post_force_in_summary ):
$return .= <<<IPSCONTENT
data-post-force-in-summary="1" 
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

if ( \IPS\Member::loggedIn()->getLayoutValue( 'forum_topic_view_firstpost' ) and $comment->isFirst() ):
$return .= <<<IPSCONTENT
 data-first-post="true" data-first-page="
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->page ) and \IPS\Widget\Request::i()->page > 1 ):
$return .= <<<IPSCONTENT
false
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
true
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<div class="ipsEntry__post">
		<div class="ipsEntry__meta">
			
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->shareableUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsEntry__date" rel="nofollow">
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

			<!-- Traditional badges -->
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postBadges:before", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="postBadges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postBadges:inside-start", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( ! $comment->isFirst() and $comment->author()->member_id AND $comment->author()->member_id == $item->author()->member_id ):
$return .= <<<IPSCONTENT

					<li><span class="ipsBadge ipsBadge--author">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'author', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $comment->authorIsAnExpert() ):
$return .= <<<IPSCONTENT

				    <li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->expertBadge( $item, $comment, $viewerIsFollowingTheseExperts );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
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

if ( ( $comment->item()->isSolved() and $comment->item()->mapped('solved_comment_id') == $comment->pid ) ):
$return .= <<<IPSCONTENT

					<li><span class="ipsBadge ipsBadge--solution"><i class="fa-solid fa-check" aria-hidden="true"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_solved_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></span></li>
				
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

if ( $comment->isHighlighted()  ):
$return .= <<<IPSCONTENT

					<li><span class="ipsBadge ipsBadge--popular">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_popular_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postBadges:inside-end", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postBadges:after", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			{$comment->menu()->linkHtml()}
			
IPSCONTENT;

if ( \count( $item->commentMultimodActions() ) and !$comment->mapped('first') ):
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
" class="ipsInput ipsInput--toggle" id="mod-checkbox-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $comment->hidden() !== 0 ):
$return .= <<<IPSCONTENT

			<div class="ipsEntry__hiddenMessage">
				
IPSCONTENT;

if ( $comment->hidden() AND $comment->hidden() != -2 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $comment->hidden() == -2 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->deletedBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
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

if ( $comment->showRecognized() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentRecognized( $comment );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<!-- Post content -->
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postContent:before", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="postContent" class="ipsRichText ipsRichText--user" data-role="commentContent" data-controller="core.front.core.lightboxedImages">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postContent:inside-start", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue( 'forum_topic_view_firstpost' ) and $comment->isFirst() and isset( \IPS\Widget\Request::i()->page ) and \IPS\Widget\Request::i()->page > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsEntry__truncate" data-ipstruncate>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				{$comment->content()}

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue( 'forum_topic_view_firstpost' ) and $comment->isFirst() and isset( \IPS\Widget\Request::i()->page ) and \IPS\Widget\Request::i()->page > 1 ):
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( $comment->editLine() ):
$return .= <<<IPSCONTENT

				{$comment->editLine()}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postContent:inside-end", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postContent:after", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $comment->author()->signature AND trim( $comment->author()->signature ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->signature( $comment->author() );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	</div>
	
IPSCONTENT;

if ( ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled and $comment->hasReactionBar() ) || ( $comment->hidden() === 1 && ( $comment->canUnhide() || $comment->canDelete() ) ) || ( $comment->hidden() === 0 and $item->canComment() and $editorName ) || $comment->item()->canSolve()  ):
$return .= <<<IPSCONTENT

		<div class="ipsEntry__footer">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postFooterControls:before", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<menu data-ips-hook="postFooterControls" class="ipsEntry__controls" data-role="commentControls" data-controller="core.front.helpful.helpful">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postFooterControls:inside-start", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
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
" data-action="approveComment"><i class="fa-solid fa-check" aria-hidden="true"></i><span>
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
" data-action="deleteComment" data-updateondelete="#commentCount"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span>
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
							<a href="#elControls_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_moderator_menu" id="elControls_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_moderator" data-ipsmenu data-ipsmenu-appendto="#comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_wrap"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderator_tools', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down" aria-hidden="true"></i></a>
							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postFooterControlsMenu:before", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="postFooterControlsMenu" id="elControls_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_moderator_menu" class="ipsMenu ipsMenu_narrow ipsHide">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postFooterControlsMenu:inside-start", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

								
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
" data-ipsdialog-destructonclose="true">
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
" data-ipsdialog-destructonclose="true">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postFooterControlsMenu:inside-end", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postFooterControlsMenu:after", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

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
							<button class="ipsHide" data-action="multiQuoteComment" data-ipstooltip data-ipsquote-multiquote data-mqid="mq
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

if ( ( $comment->item()->isSolved() and $comment->item()->mapped('solved_comment_id') == $comment->pid ) AND $comment->item()->canSolve() ):
$return .= <<<IPSCONTENT

						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->csrf()->setQueryString( array( 'do' => 'unsolve', 'answer' => $comment->pid ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="unsolveComment"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unsolve_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->item()->canSolve() AND ! $comment->item()->isSolved() ):
$return .= <<<IPSCONTENT

						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $item->url()->csrf()->setQueryString( array( 'do' => 'solve', 'answer' => $comment->pid ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="solveComment"><i class="fa-solid fa-check" aria-hidden="true"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solve_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

                    	<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $comment, FALSE );
$return .= <<<IPSCONTENT
</li>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( ! $comment->mapped('first') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "system", "core" )->helpfulButton( $comment, $item );
$return .= <<<IPSCONTENT

                    
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postFooterControls:inside-end", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</menu>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/post", "postFooterControls:after", [ $item,$comment,$editorName,$app,$type,$class,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !( $comment->hidden() === 1 && ( $comment->canUnhide() || $comment->canDelete() ) ) and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
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

	function postContainer( $item, $comment, $votes=array(), $otherClasses='', $viewerIsFollowingTheseExperts=[] ) {
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

	<div class="ipsEntry ipsEntry--ignored" id="elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ignorecommentid="elComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ignoreuserid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->author()->member_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<i class="fa-solid fa-user-slash"></i> 
IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ignoring_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 <a href="#elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" data-ipsmenu data-ipsmenu-menuid="elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" data-ipsmenu-appendto="#elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="ignoreOptions" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_post_ignore_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-caret-down"></i></a>
		<ul class="ipsMenu ipsHide" id="elIgnoreComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
			<li class="ipsMenu_item ipsJS_show" data-ipsmenuvalue="showPost"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_this_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
			<li class="ipsMenu_sep ipsJS_show"><hr></li>
			<li class="ipsMenu_item" data-ipsmenuvalue="stopIgnoring"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore&do=remove&id={$comment->author()->member_id}", null, "ignore", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stop_ignoring_posts_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
			<li class="ipsMenu_item"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ignore", null, "ignore", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'change_ignore_preferences', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		</ul>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<a id="findComment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></a>
<div id="comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></div>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postWrapper:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<article data-ips-hook="postWrapper" id="elComment_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="
		ipsEntry js-ipsEntry 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $otherClasses, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsEntry--
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue('forums_post') === 'modern' ):
$return .= <<<IPSCONTENT
simple
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
post
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->ui('css'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( ( \IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_highlight and $comment->reactionCount() >= \IPS\Settings::i()->reputation_highlight ) OR $comment->isFeatured() ):
$return .= <<<IPSCONTENT
 ipsEntry--popular 
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

if ( $comment->isIgnored() ):
$return .= <<<IPSCONTENT
 ipsHide 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( $comment->hidden() OR $item->hidden() === -2 ):
$return .= <<<IPSCONTENT
 ipsModerated 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( ( $item->isSolved() and $item->mapped('solved_comment_id') == $comment->pid ) ):
$return .= <<<IPSCONTENT
 ipsEntry--solved 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		" 
IPSCONTENT;

if ( $comment->author()->hasHighlightedReplies() ):
$return .= <<<IPSCONTENT
 data-membergroup="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->author()->member_group_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $field = $item::$databaseColumnMap['first_comment_id'] and $item->$field == $comment->$idField ):
$return .= <<<IPSCONTENT
data-ips-first-post
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postWrapper:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue('forums_post') === 'classic' ):
$return .= <<<IPSCONTENT

		<!-- Author column -->
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "topicAuthorColumn:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<aside data-ips-hook="topicAuthorColumn" class="ipsEntry__author-column">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "topicAuthorColumn:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $comment->author()->isOnline() AND ( !$comment->author()->isOnlineAnonymously() OR ( $comment->author()->isOnlineAnonymously() AND \IPS\Member::loggedIn()->isAdmin() ) ) ):
$return .= <<<IPSCONTENT

				<span class="ipsEntry__author-online" data-ipstooltip title="
IPSCONTENT;

if ( $comment->author()->isOnlineAnonymously() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $comment->author()->isOnline() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"></span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserPhoto:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="postUserPhoto" class="ipsAvatarStack">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserPhoto:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $comment->author(), 'fluid', $comment->warningRef() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $comment->author()->modShowBadge() ):
$return .= <<<IPSCONTENT

					<!-- <span class="ipsAvatarStack__badge ipsAvatarStack__badge--moderator" data-ipsTooltip title='
IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_is_moderator', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'></span> -->
				
IPSCONTENT;

elseif ( $comment->author()->joinedRecently() ):
$return .= <<<IPSCONTENT

					<span class="ipsAvatarStack__badge ipsAvatarStack__badge--new" data-ipstooltip title="
IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_is_new_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
"></span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !$comment->isAnonymous() and $comment->author()->canHaveAchievements() and \IPS\core\Achievements\Rank::show() and $rank = $comment->author()->rank() ):
$return .= <<<IPSCONTENT

					{$rank->html( 'ipsAvatarStack__rank' )}
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserPhoto:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserPhoto:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUsername:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<h3 data-ips-hook="postUsername" class="ipsEntry__username">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUsername:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $comment->author(), $comment->warningRef(), FALSE, $comment->isAnonymous() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $comment->isAnonymous() and \IPS\Member::loggedIn()->modPermission('can_view_anonymous_posters') ):
$return .= <<<IPSCONTENT

					<a data-ipshover data-ipshover-width="370" data-ipshover-onclick href="
IPSCONTENT;

if ( $comment->isFirst() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->url( 'reveal' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url( 'reveal' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" rel="nofollow"><span class="ipsAnonymous" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_anonymously_reveal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span></a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $comment->author()->modShowBadge() ):
$return .= <<<IPSCONTENT

					<span class="ipsEntry__author-moderator-badge ipsBadge ipsBadge--icon ipsBadge--soft" data-ipstooltip title="
IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'member_is_moderator', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
						<i class="fa-solid fa-shield-halved"></i>
					</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUsername:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</h3>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUsername:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !$comment->isAnonymous() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserGroup:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="postUserGroup" class="ipsEntry__group">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserGroup:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->author()->modShowBadge() ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=staffdirectory&controller=directory", null, "staffdirectory", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member\Group::load( $comment->author()->member_group_id )->formattedName;
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member\Group::load( $comment->author()->member_group_id )->formattedName;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserGroup:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserGroup:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !$comment->isAnonymous() && \IPS\Member\Group::load( $comment->author()->member_group_id )->g_icon  ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserGroupImage:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="postUserGroupImage" class="ipsEntry__group-image">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserGroupImage:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->author()->modShowBadge() ):
$return .= <<<IPSCONTENT

						<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=staffdirectory&controller=directory", null, "staffdirectory", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
							<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $comment->author()->group['g_icon'] )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Theme", $comment->author()->group['g_icon'] )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserGroupImage:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserGroupImage:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $comment->author()->member_id ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserStats:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="postUserStats" class="ipsEntry__authorStats ipsEntry__authorStats--minimal">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserStats:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					<li data-i-el="posts">
						
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) )  ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$comment->author()->member_id}&do=content", null, "profile_content", array( $comment->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_pl', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip>
								<i class="fa-solid fa-comment"></i>
								<span data-i-el="number">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $comment->author()->member_posts );
$return .= <<<IPSCONTENT
</span>
								<span data-i-el="label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_pl_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span>
								<i class="fa-solid fa-comment"></i>
								<span data-i-el="number">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $comment->author()->member_posts );
$return .= <<<IPSCONTENT
</span>
								<span data-i-el="label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_pl_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
					
IPSCONTENT;

if ( isset( $comment->author_solved_count ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserSolutions:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="postUserSolutions" data-i-el="solutions">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserSolutions:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) )  ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$comment->author()->member_id}&do=solutions", null, "profile_solutions", array( $comment->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip>
									<i class="fa-solid fa-circle-check"></i>
									<span data-i-el="number">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $comment->author_solved_count );
$return .= <<<IPSCONTENT
</span>
									<span data-i-el="label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span>
									<i class="fa-solid fa-circle-check"></i>
									<span data-i-el="number">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $comment->author_solved_count );
$return .= <<<IPSCONTENT
</span>
									<span data-i-el="label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solutions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserSolutions:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserSolutions:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $comment->author()->canHaveAchievements() and \IPS\core\Achievements\Badge::show() AND \IPS\core\Achievements\Badge::getStore() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserBadges:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="postUserBadges" data-i-el="badges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserBadges:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$comment->author()->member_id}&do=badges", null, "profile_badges", array( $comment->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="badgeLog" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badges', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
								<i class="fa-solid fa-award"></i>
								<span data-i-el="number">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $comment->author()->badgeCount() );
$return .= <<<IPSCONTENT
</span>
								<span data-i-el="label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'badges', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</a>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserBadges:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserBadges:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_show_profile ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserReputation:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="postUserReputation" data-i-el="reputation">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserReputation:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$comment->author()->member_id}&do=reputation", null, "profile_reputation", array( $comment->author()->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="repLog" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<i class="fa-solid fa-heart"></i>
								<span data-i-el="number">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $comment->author()->pp_reputation_points );
$return .= <<<IPSCONTENT
</span>
								<span data-i-el="label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reputation_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

								</span></a>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserReputation:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserReputation:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserStats:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserStats:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserCustomFields:before", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="postUserCustomFields" class="ipsEntry__authorFields">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserCustomFields:inside-start", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->customFieldsDisplay( $comment->author() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserCustomFields:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postUserCustomFields:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			{$comment->ui( 'authorPanel' )}
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "topicAuthorColumn:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</aside>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "topicAuthorColumn:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->postHeader( $item, $comment, $idField, $viewerIsFollowingTheseExperts );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->post( $item, $comment, $item::$formLangPrefix . 'comment', $item::$application, $item::$module, $itemClassSafe, $viewerIsFollowingTheseExperts );
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postWrapper:inside-end", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</article>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postContainer", "postWrapper:after", [ $item,$comment,$votes,$otherClasses,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function postHeader( $item, $comment, $idField, $viewerIsFollowingTheseExperts=[] ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "topicAuthorColumn:before", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<aside data-ips-hook="topicAuthorColumn" class="ipsEntry__header">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "topicAuthorColumn:inside-start", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

	<div class="ipsEntry__header-align">

		<div class="ipsPhotoPanel">
			<!-- Avatar -->
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postUserPhoto:before", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div class="ipsAvatarStack" data-ips-hook="postUserPhoto">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postUserPhoto:inside-start", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

				
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

				
IPSCONTENT;

if ( $comment->author()->isOnline() AND ( !$comment->author()->isOnlineAnonymously() OR ( $comment->author()->isOnlineAnonymously() AND \IPS\Member::loggedIn()->isAdmin() ) ) ):
$return .= <<<IPSCONTENT

					<span class="ipsOnlineStatus" data-ipstooltip title="
IPSCONTENT;

if ( $comment->author()->isOnlineAnonymously() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now_anon', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $comment->author()->isOnline() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($comment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_now', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"></span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postUserPhoto:inside-end", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postUserPhoto:after", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			<!-- Username -->
			<div class="ipsPhotoPanel__text">
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postUsername:before", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<h3 data-ips-hook="postUsername" class="ipsEntry__username">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postUsername:inside-start", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $comment->author(), $comment->warningRef(), FALSE, $comment->isAnonymous() );
$return .= <<<IPSCONTENT

					
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

if ( $comment->isAnonymous() and \IPS\Member::loggedIn()->modPermission('can_view_anonymous_posters') ):
$return .= <<<IPSCONTENT

						<a data-ipshover data-ipshover-width="370" data-ipshover-onclick href="
IPSCONTENT;

if ( $comment->isFirst() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->url( 'reveal' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url( 'reveal' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" rel="nofollow"><span class="ipsAnonymousIcon" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_anonymously_reveal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span></a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postUsername:inside-end", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</h3>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postUsername:after", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

				<p class="ipsPhotoPanel__secondary">
					
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->shareableUrl(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
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

				</p>
			</div>
		</div>

		<!-- Minimal badges -->
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postBadgesSecondary:before", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="postBadgesSecondary" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postBadgesSecondary:inside-start", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $comment->authorIsAnExpert() ):
$return .= <<<IPSCONTENT

				<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->expertBadge( $item, $comment, $viewerIsFollowingTheseExperts );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
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

if ( ( $comment->item()->isSolved() and $comment->item()->mapped('solved_comment_id') == $comment->pid ) ):
$return .= <<<IPSCONTENT

				<li><span class="ipsBadge ipsBadge--positive"><i class="fa-solid fa-check" aria-hidden="true"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_solved_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></span></li>
			
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

if ( $comment->isHighlighted()  ):
$return .= <<<IPSCONTENT

				<li><span class="ipsBadge ipsBadge--popular">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_popular_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postBadgesSecondary:inside-end", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "postBadgesSecondary:after", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( \count( $item->commentMultimodActions() ) and !$comment->mapped('first') ):
$return .= <<<IPSCONTENT

            <label class="ipsInput ipsInput--toggle ipsInput--pseudo" for="mod-checkbox-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></label>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		{$comment->menu()}

		
IPSCONTENT;

if ( $comment->author()->member_id ):
$return .= <<<IPSCONTENT

			<!-- Expand mini profile -->
			<button class="ipsEntry__topButton ipsEntry__topButton--profile" type="button" aria-controls="mini-profile-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-expanded="false" data-ipscontrols aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'author_stats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->miniProfile( $comment, $comment->$idField );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "topicAuthorColumn:inside-end", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</aside>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/postHeader", "topicAuthorColumn:after", [ $item,$comment,$idField,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function topic( $topic, $comments, $votes=array(), $nextUnread=NULL, $pagination=NULL, $topicVotes=array(), $firstPost=null, $viewerIsFollowingTheseExperts=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$Traditional = \IPS\Member::loggedIn()->getLayoutValue( 'forums_post' ) !== 'modern';
$return .= <<<IPSCONTENT


IPSCONTENT;

$featureFirstPost = \IPS\Member::loggedIn()->getLayoutValue( 'forum_topic_view_firstpost' );
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $club = $topic->container()->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $topic->container() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id="elClubContainer" class="i-display_contents">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<!-- Start #ipsTopicView -->
<div class="ipsBlockSpacer" id="ipsTopicView" data-ips-topic-ui="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue( 'forums_post' ) === 'modern' ):
$return .= <<<IPSCONTENT
minimal
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
traditional
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ips-topic-first-page="
IPSCONTENT;

if ( $topic->isFirstPage() ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ips-topic-comments="
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show == 'helpful' ):
$return .= <<<IPSCONTENT
helpful
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
all
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->assignmentHeader( $topic );
$return .= <<<IPSCONTENT


<div class="ipsBox ipsPull">
	<header class="ipsPageHeader">
		<div class="ipsPageHeader__row">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "header:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "header:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

				<div class="ipsPageHeader__title">
					
IPSCONTENT;

if ( $topic->canEdit() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "titleEditable:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="titleEditable" data-controller="core.front.core.moderation">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "titleEditable:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

							<span data-role="editableTitle" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "titleEditable:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "titleEditable:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "title:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "title:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "title:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "title:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "badges:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="badges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "badges:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $topic->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "badges:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "badges:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( \count( $topic->tags() ) OR ( $topic->canEdit() AND $topic::canTag( NULL, $topic->container() ) ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tagsWithPrefix( $topic->tags(), $topic->prefix(), FALSE, FALSE, ( $topic->canEdit() AND ( \count( $topic->tags() ) OR $topic::canTag( NULL, $topic->container() ) ) ) ? $topic->url() : NULL );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $featureFirstPost and \IPS\Platform\Bridge::i()->featureIsEnabled( 'realtime' ) ):
$return .= <<<IPSCONTENT

					<!-- Who's viewing, for featured post header -->
					<div data-controller="cloud.front.realtime.whosViewing" data-location="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\cloud\Realtime::i()->getLocationHash(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "header:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "header:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !$topic->isArchived() and !$topic->container()->password ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicHeaderButtons:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="topicHeaderButtons" class="ipsButtons">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicHeaderButtons:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$topic->container()->disable_sharelinks ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $topic );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $topic );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'forums', 'topic', $topic->tid, $topic->followersCount() );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicHeaderButtons:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicHeaderButtons:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		{$topic->postSummaryBlurb}
		
IPSCONTENT;

if ( ! $featureFirstPost ):
$return .= <<<IPSCONTENT

			<!-- PageHeader footer is only shown in traditional view -->
			<div class="ipsPageHeader__row ipsPageHeader__row--footer">
				<div class="ipsPageHeader__primary">
					<div class="ipsPhotoPanel ipsPhotoPanel--inline">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $topic->author(), 'fluid', $topic->warningRef() );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicHeaderMetaData:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="topicHeaderMetaData" class="ipsPhotoPanel__text">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicHeaderMetaData:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

							<div class="ipsPhotoPanel__primary">
IPSCONTENT;

$htmlsprintf = array($topic->author()->link( $topic->warningRef(), NULL, $topic->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
							<div class="ipsPhotoPanel__secondary">
IPSCONTENT;

$val = ( $topic->start_date instanceof \IPS\DateTime ) ? $topic->start_date : \IPS\DateTime::ts( $topic->start_date );$return .= $val->html();
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-font-weight_600">{$topic->container()->_formattedTitle}</a></div>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicHeaderMetaData:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicHeaderMetaData:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

					</div>
				</div>
				
IPSCONTENT;

if ( \IPS\Platform\Bridge::i()->featureIsEnabled( 'realtime' ) ):
$return .= <<<IPSCONTENT

					<div data-controller="cloud.front.realtime.whosViewing" data-location="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\cloud\Realtime::i()->getLocationHash(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>

	
IPSCONTENT;

if ( $featureFirstPost and $firstPost ):
$return .= <<<IPSCONTENT


		<!-- First post of modern view -->
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->postContainer( $topic, $firstPost, $votes, 'ipsEntry--first-simple', $viewerIsFollowingTheseExperts );
$return .= <<<IPSCONTENT


	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


</div>


IPSCONTENT;

if ( $featureFirstPost and $firstPost and ($topic->showSummaryOnDesktop() === 'post' OR $topic->showSummaryOnMobile()) ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->activity( $topic, 'post' );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $topic->canSolve() AND ! $topic->isSolved() AND $topic->isNotModeratorButCanSolve() ):
$return .= <<<IPSCONTENT

	<!-- Has this been solved? message -->
	<div class="ipsMessage ipsMessage--general ipsPull" data-controller="forums.front.topic.solved">
		<i class="fa-regular fa-circle-question ipsMessage__icon"></i>
		<div class="i-flex i-justify-content_space-between i-align-items_center i-flex-wrap_wrap i-gap_1">
			<div class="i-flex_11 i-basis_420">
				<h4 class="ipsMessage__title">
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solved_did_it_tho_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h4>
				<div class="ipsRichText">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solved_did_it_tho_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
			</div>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_bitoptions['no_solved_reenage'] ):
$return .= <<<IPSCONTENT

				<a href="#" data-action="mailSolvedReminders" class="ipsButton ipsButton--primary"><i class="fa-solid fa-envelope"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solved_reengage_on', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<!-- These can be hidden on traditional first page using <div data-ips-hide="traditional-first"> -->

IPSCONTENT;

if ( $featureFirstPost ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $topic->isSolved() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->topicSolutionMessage( $topic );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( ( $mostHelpful = $topic->helpfulPosts(1) and $mostHelpful[0]['count'] >= \IPS\Settings::i()->forums_helpful_highlight) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->topicHelpfulMessage( $mostHelpful );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<!-- Content messages -->

IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->contentItemMessages( $topic->getMessages(), $topic );
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $topic->hidden() === 1 and $topic->canUnhide() ):
$return .= <<<IPSCONTENT

	<!-- Pending approval -->
	<div class="ipsMessage ipsMessage--warning ipsPull">
		<div class="i-flex i-justify-content_space-between i-align-items_center i-flex-wrap_wrap i-gap_1">
			<div class="i-flex_11 i-basis_420">
				<h4 class="ipsMessage__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			</div>
			<ul class="ipsButtons">
				<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unhide' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve_title_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
				
IPSCONTENT;

if ( $topic->canDelete() ):
$return .= <<<IPSCONTENT

					<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_delete_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit"><i class="fa-solid fa-xmark"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
				
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

if ( $topic->hidden() === -1 ):
$return .= <<<IPSCONTENT

	<!-- Hidden topic -->
	<div class="ipsMessage ipsMessage--warning ipsPull">
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT




<div class="i-flex i-flex-wrap_wrap-reverse i-align-items_center i-gap_3">

	<!-- All replies / Helpful Replies: This isn't shown on the first page, due to data-ips-hide="traditional-first" -->
	
IPSCONTENT;

if ( $featureFirstPost and $topic::itemHasHelpful( $topic ) and ( $mostHelpful = $topic->helpfulPosts(1) and $mostHelpful[0]['count'] >= \IPS\Settings::i()->forums_helpful_highlight) ):
$return .= <<<IPSCONTENT

		<ul class="ipsButtons ipsButtons--start" data-ips-hide="traditional-first">
			<li>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->setQueryString( 'show', 'all' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show !== 'helpful' ):
$return .= <<<IPSCONTENT
ipsButton--secondary
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" rel="nofollow">
					
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show === 'helpful' and $topic->posts > 2 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$pluralize = array( $topic->posts - 1, $topic->posts - 1 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_all_n_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_all_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</a>
			</li>
			<li>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->setQueryString( 'show', 'helpful' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show == 'helpful' ):
$return .= <<<IPSCONTENT
ipsButton--secondary
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" rel="nofollow" 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show !== 'helpful' and $topic->helpfulsRepliesCount() > 1 ):
$return .= <<<IPSCONTENT
data-role="helpfulCount" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show !== 'helpful' and $topic->helpfulsRepliesCount() > 1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$pluralize = array( $topic->helpfulsRepliesCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_only_n_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_only_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</a>
			</li>
		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<!-- Start new topic, Reply to topic: Shown on all views -->
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicMainButtons:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="topicMainButtons" class="i-flex_11 ipsButtons ipsButtons--main">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicMainButtons:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

		<li>
			{$topic->menu()}
		</li>
		
IPSCONTENT;

if ( $topic->container()->can('add') ):
$return .= <<<IPSCONTENT

			<li>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->container()->url()->setQueryString( 'do', 'add' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow" class="ipsButton ipsButton--text" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start_new_topic_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start_new_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $topic->canComment() ):
$return .= <<<IPSCONTENT

			<li data-controller="forums.front.topic.reply">
				<a href="#replyForm" rel="nofollow" class="ipsButton 
IPSCONTENT;

if ( $topic->locked() ):
$return .= <<<IPSCONTENT
ipsButton--negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsButton--primary
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="replyToTopic"><i class="fa-solid fa-reply"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reply_to_this_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $topic->locked() ):
$return .= <<<IPSCONTENT
 (
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'locked', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicMainButtons:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicMainButtons:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

</div>


IPSCONTENT;

if ( $poll = $topic->getPoll() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicPoll:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="topicPoll" class="ipsBox ipsPull">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicPoll:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

		{$poll}
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicPoll:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicPoll:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->featuredComments( $topic->featuredComments(), $topic->url()->setQueryString( 'recommended', 'comments' ), 'recommended_posts', 'post_lc' );
$return .= <<<IPSCONTENT


<div id="comments" data-controller="core.front.core.commentFeed,forums.front.topic.view, core.front.core.ignoredComments" 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autopoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-baseurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $topic->isLastPage() ):
$return .= <<<IPSCONTENT
data-lastpage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="cTopic ipsBlockSpacer">

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicPostFeed:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="topicPostFeed" id="elPostFeed" class="ipsEntries ipsPull ipsEntries--topic" data-role="commentFeed" data-controller="core.front.core.moderation" 
IPSCONTENT;

if ( $topic->topic_answered_pid ):
$return .= <<<IPSCONTENT
 data-topicanswerid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->topic_answered_pid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicPostFeed:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( ( \count( $topic->commentMultimodActions() ) && ( $topic->posts > 1 OR $topic->mapped('unapproved_comments') > 0 OR $topic->mapped('hidden_comments') > 0 ) ) || $pagination ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--top">
				
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

					<div class="ipsButtonBar__pagination">{$pagination}</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $topic->commentMultimodActions() ) ):
$return .= <<<IPSCONTENT

					<div class="ipsButtonBar__end">
						<ul class="ipsDataFilters">
							<li>
								<a class="ipsJS_show ipsDataFilters__button" href="#elCheck_menu" id="elCheck" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ipsautocheck data-ipsautocheck-context="#elPostFeed" data-ipsmenu data-ipsmenu-activeclass="ipsDataFilters__button--active">
									<span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span> <i class="fa-solid fa-caret-down"></i>
									<span class="ipsNotification" data-role="autoCheckCount">0</span>
								</a>
								<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="elCheck_menu">
									<li class="ipsMenu_title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
									<li class="ipsMenu_item" data-ipsmenuvalue="all"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li class="ipsMenu_item" data-ipsmenuvalue="none"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li class="ipsMenu_sep"><hr></li>
									<li class="ipsMenu_item" data-ipsmenuvalue="hidden"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li class="ipsMenu_item" data-ipsmenuvalue="unhidden"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unhidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li class="ipsMenu_item" data-ipsmenuvalue="unapproved"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unapproved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								</ul>
							</li>
						</ul>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->csrf()->setQueryString( 'do', 'multimodComment' )->setPage('page', (int) \IPS\Request::i()->page ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipspageaction data-role="moderationTools">
			
IPSCONTENT;

$postCount=0; $adShown=0; $timeLastRead = $topic->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $comments ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $topic->generateCommentMetaData( $comments, \IPS\Settings::i()->forums_mod_actions_anon ) as $comment ):
$return .= <<<IPSCONTENT


					
IPSCONTENT;

if ( (!$lined and $timeLastRead and $timeLastRead->getTimestamp() < $comment->mapped('date')) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $lined = TRUE and $postCount ):
$return .= <<<IPSCONTENT

							<div class="ipsUnreadBar">
								<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_meta_unread', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

$postCount++;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->postContainer( $topic, $comment, $votes, '', $viewerIsFollowingTheseExperts );
$return .= <<<IPSCONTENT


                    
IPSCONTENT;

$hasSolvedMessage = false;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$contentUnderPostOne = false;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( ( ! $featureFirstPost ) and $postCount == 1 and $topic->isFirstPage() ):
$return .= <<<IPSCONTENT

						<!-- If this is the first post in the traditional UI, show some of the extras/messages below it -->
						
IPSCONTENT;

if ( $topic->isSolved() ):
$return .= <<<IPSCONTENT

						    
IPSCONTENT;

$hasSolvedMessage = true;
$return .= <<<IPSCONTENT

							<!-- Show the Solved message in favour of the Most Helpful message, if the topic is solved.. -->
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->topicSolutionMessage( $topic );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( ($mostHelpful = $topic->helpfulPosts(1) and $mostHelpful[0]['count'] >= \IPS\Settings::i()->forums_helpful_highlight) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$hasSolvedMessage = true;
$return .= <<<IPSCONTENT

							<!-- Otherwise show the most helpful message if the criteria is met -->
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->topicHelpfulMessage( $mostHelpful );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


						<!-- Show the All Replies / Most Helpful tabs -->
						
IPSCONTENT;

if ( $topic::itemHasHelpful( $topic ) and ($mostHelpful = $topic->helpfulPosts(1) and $mostHelpful[0]['count'] >= \IPS\Settings::i()->forums_helpful_highlight) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$contentUnderPostOne = true;
$return .= <<<IPSCONTENT

							<ul class="ipsButtons ipsButtons--start i-padding_2">
								<li>
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->setQueryString( 'show', 'all' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--tiny 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show !== 'helpful' ):
$return .= <<<IPSCONTENT
ipsButton--positive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" rel="nofollow">
										
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show === 'helpful' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$pluralize = array( $topic->posts - 1, $topic->posts - 1 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_all_n_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_all_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</a>
								</li>
								<li>
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->setQueryString( 'show', 'helpful' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--tiny 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show == 'helpful' ):
$return .= <<<IPSCONTENT
ipsButton--positive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" rel="nofollow">
										
IPSCONTENT;

if ( \IPS\Widget\Request::i()->show !== 'helpful' and $topic->helpfulsRepliesCount() > 1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$pluralize = array( $topic->helpfulsRepliesCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_only_n_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_only_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</a>
								</li>
							</ul>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
					
IPSCONTENT;

if ( ( isset( $comment->metaData['comment']['moderation'] ) OR isset( $comment->metaData['comment']['timeGap'] ) ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$contentUnderPostOne = true;
$return .= <<<IPSCONTENT

						<ul class="ipsTopicMeta">
							
IPSCONTENT;

if ( isset( $comment->metaData['comment']['moderation'] ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

foreach ( $comment->metaData['comment']['moderation'] as $modAction ):
$return .= <<<IPSCONTENT

									<li class="ipsTopicMeta__item ipsTopicMeta__item--moderation">
										<span class="ipsTopicMeta__time i-color_soft">
IPSCONTENT;

$val = ( $modAction['row']['ctime'] instanceof \IPS\DateTime ) ? $modAction['row']['ctime'] : \IPS\DateTime::ts( $modAction['row']['ctime'] );$return .= $val->html(TRUE, TRUE);
$return .= <<<IPSCONTENT
</span>
										<span class="ipsTopicMeta__action">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $modAction['blurb'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
									</li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $comment->metaData['comment']['timeGap'] ) ):
$return .= <<<IPSCONTENT

								<li class="ipsTopicMeta__item ipsTopicMeta__item--time">
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->metaData['comment']['timeGap']['blurb'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
...
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

if ( $advertisement = \IPS\core\Advertisement::loadByLocation( 'ad_topic_view', $postCount, $adShown ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$adShown++;
$return .= <<<IPSCONTENT

						{$advertisement}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( ( !$featureFirstPost and $postCount == 1 and ( $topic->showSummaryOnDesktop() === 'post' OR $topic->showSummaryOnMobile() ) ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "topics", "forums" )->activity( $topic, 'post' );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $topic->isFirstPage() and $postCount == 1 and $hasSolvedMessage and ! $contentUnderPostOne ):
$return .= <<<IPSCONTENT

					<!-- If no content, we need some margin -->
					<div class="i-margin_2"></div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_posts_show', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $topic->posts > 1 ):
$return .= <<<IPSCONTENT
 - <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->setQueryString( 'show', 'all' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_show_all_replies', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $topic );
$return .= <<<IPSCONTENT

		</form>
		
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				<div class="ipsButtonBar__pagination">{$pagination}</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicPostFeed:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicPostFeed:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

if ( $topic->isArchived() ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--info">
			<h4 class="ipsMessage__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_is_archived', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_archived_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

if ( ( $topic->commentForm() || $topic->locked() || \IPS\Member::loggedIn()->restrict_post || \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] || !\IPS\Member::loggedIn()->checkPostsPerDay()) && !$topic->isArchived() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicReplyForm:before", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
<div id="replyForm" data-ips-hook="topicReplyForm" data-role="replyArea" class="cTopicPostArea ipsBox ipsPull 
IPSCONTENT;

if ( !$topic->canComment() ):
$return .= <<<IPSCONTENT
cTopicPostArea_noSize
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') AND \IPS\cloud\Application::featureIsEnabled( 'realtime' ) ):
$return .= <<<IPSCONTENT
data-controller="cloud.front.realtime.forumsReplyArea,cloud.front.realtime.whosTyping" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicReplyForm:inside-start", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $topic->commentForm() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $topic->locked() ):
$return .= <<<IPSCONTENT

					<p class="ipsComposeArea_warning"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_locked_can_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

elseif ( ( $topic->getPoll() and $topic->getPoll()->poll_only ) ):
$return .= <<<IPSCONTENT

					<p class="ipsComposeArea_warning"><i class="fa-solid fa-circle-info"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_poll_can_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				{$topic->commentForm()}
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $topic->locked() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'topic_locked_cannot_comment' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->restrict_post ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'restricted_cannot_comment', \IPS\Member::loggedIn()->warnings(5,NULL,'rpa'), \IPS\Member::loggedIn()->restrict_post );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'unacknowledged_warning_cannot_post', \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( !\IPS\Member::loggedIn()->checkPostsPerDay() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'member_exceeded_posts_per_day' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicReplyForm:inside-end", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/topics/topic", "topicReplyForm:after", [ $topic,$comments,$votes,$nextUnread,$pagination,$topicVotes,$firstPost,$viewerIsFollowingTheseExperts ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( !$topic->isArchived() and !$topic->container()->password ):
$return .= <<<IPSCONTENT

		<div class="ipsPageActions ipsBox i-padding_2 ipsPull ipsResponsive_showPhone">
			
IPSCONTENT;

if ( !$topic->container()->disable_sharelinks ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $topic, 'verySmall', 'light' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $topic );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'forums', 'topic', $topic->tid, $topic->followersCount() );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

<div class="ipsPager">
	<div class="ipsPager_prev">
		
IPSCONTENT;

if ( \IPS\forums\Forum::isSimpleView( $topic->container() ) ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=forums&module=forums&controller=index", null, "forums", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_back_to_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="parent">
				<span class="ipsPager_type">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_back_to_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($topic->container()->metaTitle()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" rel="parent">
				<span class="ipsPager_type">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_back_to_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( $nextUnread !== NULL ):
$return .= <<<IPSCONTENT

		<div class="ipsPager_next">
			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->setQueryString( array( 'do' => 'nextUnread' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_next_unread_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
				<span class="ipsPager_type">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_next_unread', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</a>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>



</div> <!-- End #ipsTopicView -->



IPSCONTENT;

if ( $topic->container()->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function topicHelpfulMessage( $mostHelpful ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( ( $mostHelpful[0]['comment']->hidden() == 0 OR ( \in_array( $mostHelpful[0]['comment']->hidden(), array( 1, -1 ) ) AND $mostHelpful[0]['comment']->canUnhide() ) ) ):
$return .= <<<IPSCONTENT

 <div data-role="mostHelpful">
	<div class='ipsBox ipsBox--padding'>
		<div class='ipsColumns i-align-items_center i-gap_3'>
			<div class='ipsColumns__primary'>
				<h4 class='ipsTitle ipsTitle--h5 ipsTitle--margin'>
IPSCONTENT;

$htmlsprintf = array($mostHelpful[0]['comment']->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'most_helpful_byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
<span class='i-color_soft'>, 
IPSCONTENT;

$val = ( $mostHelpful[0]['comment']->mapped('date') instanceof \IPS\DateTime ) ? $mostHelpful[0]['comment']->mapped('date') : \IPS\DateTime::ts( $mostHelpful[0]['comment']->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT
</span></h4>
				<div>
					{$mostHelpful[0]['comment']->truncated()}
				</div>
			</div>
			<div class="ipsColumns__secondary">
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $mostHelpful[0]['comment']->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow" class='ipsButton ipsButton--secondary ipsButton--small'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'helpful_go_to_most_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right"></i></a>
			</div>
		</div>
	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function topicSolutionMessage( $topic ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $solvedComment = $topic->getSolution() AND ( $solvedComment->hidden() == 0 OR ( \in_array( $solvedComment->hidden(), array( 1, -1 ) ) AND $solvedComment->canUnhide() ) ) ):
$return .= <<<IPSCONTENT

	<div class='ipsMessage ipsMessage--success ipsPull'>
		<div class='i-flex i-justify-content_space-between i-align-items_center i-flex-wrap_wrap i-gap_1'>
			<div class='i-flex_11'>
				<h4 class='ipsMessage__title'>
IPSCONTENT;

$htmlsprintf = array($solvedComment->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solved_byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</h4>
				<div class='i-color_soft'>
IPSCONTENT;

$val = ( $solvedComment->mapped('date') instanceof \IPS\DateTime ) ? $solvedComment->mapped('date') : \IPS\DateTime::ts( $solvedComment->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT
</div>
			</div>
			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->setQueryString( array( 'do' => 'findComment', 'comment' => $topic->topic_answered_pid )), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow" class='ipsButton ipsButton--inherit'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'solved_and_go', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right"></i></a>
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}