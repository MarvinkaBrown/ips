<?php
namespace IPS\Theme;
class class_gallery_front_browse extends \IPS\Theme\Template
{	function album( $album, $table, $commentsAndReviews ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $album->category()->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $album->category() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id="elClubContainer">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $album->coverPhoto('masked') ):
$return .= <<<IPSCONTENT

	<div class="ipsCoverPhoto ipsCoverPhoto--album" hidden>
		<div class="ipsCoverPhoto__container">
			<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->coverPhoto('masked'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
		</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<header class="ipsPageHeader ipsBox ipsPull">
	<div class="ipsPageHeader__row">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "header:before", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "header:inside-start", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "title:before", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "title:inside-start", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "badges:before", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="badges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "badges:inside-start", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $album->asItem()->hidden() === 1 ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-triangle-exclamation"></i></span>
					
IPSCONTENT;

elseif ( $album->asItem()->hidden() === -1 ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipstooltip title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->asItem()->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-eye-slash"></i></span>
					
IPSCONTENT;

elseif ( $album->asItem()->hidden() === -2 ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipstooltip title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->asItem()->deletedBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-trash"></i></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $album->asItem()->mapped('featured') ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-star"></i></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $album->type === \IPS\gallery\Album::AUTH_TYPE_PRIVATE ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_private_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

elseif ( $album->type === \IPS\gallery\Album::AUTH_TYPE_RESTRICTED ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_friend_only_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "badges:inside-end", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "badges:after", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $album->asItem()->canEdit() ):
$return .= <<<IPSCONTENT

					<span data-controller="core.front.core.moderation">
IPSCONTENT;

if ( $album->asItem()->locked() ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-lock"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<span data-role="editableTitle" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</span>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $album->asItem()->locked() ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-lock"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "title:inside-end", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "title:after", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT

			<div class="ipsPageHeader__desc">
				
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->tab ) || \IPS\Widget\Request::i()->tab !== 'node_gallery_gallery' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_created_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 {$album->owner()->link()} 
IPSCONTENT;

if ( $album->last_img_date ):
$return .= <<<IPSCONTENT
·
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $album->last_img_date ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $album->last_img_date instanceof \IPS\DateTime ) ? $album->last_img_date : \IPS\DateTime::ts( $album->last_img_date );$return .= $val->html();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "header:inside-end", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/album", "header:after", [ $album,$table,$commentsAndReviews ] );
$return .= <<<IPSCONTENT

		<div class="ipsButtons">
			
IPSCONTENT;

if ( $album->asItem()->shareLinks() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $album->asItem() );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$followerCount = \IPS\gallery\Image::containerFollowerCount( $album );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'gallery', 'album', $album->_id, $followerCount );
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

if ( $album->description ):
$return .= <<<IPSCONTENT

		<div class="ipsPageHeader__row">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $album->description, array(''), array(), array('data-ipsTruncate') );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<!-- v5 todo: Can albums receive reactions? I couldn't find any settings to enable it -->
	
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $album->asItem(), 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

		<div class="ipsPageHeader__row" hidden>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $album->asItem() );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsPageHeader__row ipsPageHeader__row--footer">
		<div class="ipsPageHeader__primary">
			<ul class="ipsList ipsList--inline ipsList--icons i-link-color_inherit">
				<li><i class="fa-solid fa-photo-film"></i> 
IPSCONTENT;

$pluralize = array( $album->count_imgs ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_images', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

if ( $album->use_comments && $album->comments > 0 ):
$return .= <<<IPSCONTENT

					<li><i class="fa-regular fa-images"></i> 
IPSCONTENT;

$pluralize = array( $album->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_album_num_comments_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $album->allow_comments && $album->count_comments > 0 ):
$return .= <<<IPSCONTENT

					<li><i class="fa-regular fa-images"></i> 
IPSCONTENT;

$pluralize = array( $album->count_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_image_comments_s', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( $album->use_reviews && $album->reviews > 0 ):
$return .= <<<IPSCONTENT

                    <li><i class="fa-regular fa-pen-to-square"></i> 
IPSCONTENT;

$pluralize = array( $album->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_album_num_reviews_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

if ( $album->views >= 0 ):
$return .= <<<IPSCONTENT

                <li id="elAlbumViews">
                    
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') and \IPS\Member::loggedIn()->modPermission('can_view_moderation_log') ):
$return .= <<<IPSCONTENT

                        
IPSCONTENT;

$url = "app=cloud&module=analytics&controller=analytics&contentClass=\IPS\gallery\Album\Item&contentId=" . $album->id;
$return .= <<<IPSCONTENT

                        <i class="fa-solid fa-eye"></i> <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( $url, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'analytics', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'analytics', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$pluralize = array( $album->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
                    
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                        <i class="fa-solid fa-eye"></i> 
IPSCONTENT;

$pluralize = array( $album->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

                    
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
</header>


IPSCONTENT;

if ( $album->can('add') ):
$return .= <<<IPSCONTENT

	<ul class="ipsButtons ipsButtons--main">
		<li>{$album->asItem()->menu()}</li>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryButtons( $album->can('add'), $album->category(), $album );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $album->_event ) ):
$return .= <<<IPSCONTENT

	<div class="ipsBox">
		<h4 class="ipsBox__header">
IPSCONTENT;

$pluralize = array( \count( $album->_event ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'events_in_album', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h4>
		<div class="ipsBox__content ipsBox__padding">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "calendar" )->eventBlocks( $album->_event );
$return .= <<<IPSCONTENT
</div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->contentItemMessages( $album->asItem()->getMessages(), $album->asItem() );
$return .= <<<IPSCONTENT


<div class="ipsBox ipsPull">
	{$table}
</div>


IPSCONTENT;

if ( $commentsAndReviews ):
$return .= <<<IPSCONTENT

	<div class="ipsBox ipsPull" id="replies">
		
IPSCONTENT;

if ( ( $album->asItem()->use_reviews && $album->asItem()->container()->allow_reviews ) && ( $album->asItem()->use_comments && $album->asItem()->container()->allow_comments )  ):
$return .= <<<IPSCONTENT

			<h2 class="ipsBox__header" hidden>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_feedback', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( (!( $album->asItem()->use_reviews && $album->asItem()->container()->allow_reviews ) && ( $album->asItem()->use_comments && $album->asItem()->container()->allow_comments )) OR (( $album->asItem()->use_reviews && $album->asItem()->container()->allow_reviews ) && !( $album->asItem()->use_comments && $album->asItem()->container()->allow_comments ))  ):
$return .= <<<IPSCONTENT

			<div>
				{$commentsAndReviews}
			</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			{$commentsAndReviews}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsBox ipsBox--padding ipsPull ipsResponsive_hideDesktop">
	<div class="ipsPageActions">
		
IPSCONTENT;

if ( $album->asItem()->shareLinks() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $album->asItem() );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$followerCount = \IPS\gallery\Image::containerFollowerCount( $album );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'gallery', 'album', $album->_id, $followerCount );
$return .= <<<IPSCONTENT

	</div>
</div>


IPSCONTENT;

if ( $album->category()->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function albumComments( $album ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div>
	<div data-controller='core.front.core.commentFeed, core.front.core.ignoredComments' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $album->isLastPage() ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='comments'>
		<div>
			<h2 class='ipsBox__header' hidden data-role="comment_count" data-commentCountString="js_gallery_album_num_comments_uc">
IPSCONTENT;

$pluralize = array( $album->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_album_num_comments_uc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

if ( $album->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsButtonBar">
					<div class="ipsButtonBar__pagination">
						{$album->commentPagination( array('tab') )}
					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div data-role='commentFeed' data-controller='core.front.core.moderation'>
				
IPSCONTENT;

if ( \count( $album->comments( NULL, NULL, 'date', 'asc', NULL, NULL, NULL, NULL, FALSE, isset( \IPS\Widget\Request::i()->showDeleted ) ) ) ):
$return .= <<<IPSCONTENT

					<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url()->csrf()->setQueryString( 'do', 'multimodComment' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
						
IPSCONTENT;

$commentCount=0; $timeLastRead = $album->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $album->comments( NULL, NULL, 'date', 'asc', NULL, NULL, NULL, NULL, FALSE, isset( \IPS\Widget\Request::i()->showDeleted ) ) as $comment ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !$lined and $timeLastRead and $timeLastRead->getTimestamp() < $comment->mapped('date') ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $lined = TRUE and $commentCount ):
$return .= <<<IPSCONTENT

									<hr class="ipsUnreadBar">
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$commentCount++;
$return .= <<<IPSCONTENT

							{$comment->html()}
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $album );
$return .= <<<IPSCONTENT

					</form>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<p class='ipsEmptyMessage' data-role='noComments'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>			
			
IPSCONTENT;

if ( $album->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsButtonBar">
					<div class="ipsButtonBar__pagination">
						{$album->commentPagination( array('tab') )}
					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( $album->commentForm() || $album->locked() || \IPS\Member::loggedIn()->restrict_post || \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] || !\IPS\Member::loggedIn()->checkPostsPerDay() ):
$return .= <<<IPSCONTENT

			<div id='replyForm' data-role='replyArea' class='ipsComposeAreaWrapper 
IPSCONTENT;

if ( !$album->canComment() ):
$return .= <<<IPSCONTENT
cTopicPostArea_noSize
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

if ( $album->commentForm() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $album->locked() ):
$return .= <<<IPSCONTENT

						<p class='ipsComposeArea_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_locked_can_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						{$album->commentForm()}
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $album->locked() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'album_locked_cannot_comment' );
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

	function albumReviews( $album ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='core.front.core.commentFeed' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-commentsType='reviews' data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $album->isLastPage('reviews') ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->reviewFeedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='reviews'>
	
IPSCONTENT;

if ( $album->reviewForm() ):
$return .= <<<IPSCONTENT

		<div id='elImageReviewForm'>
			{$album->reviewForm()}
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $album->hasReviewed() ):
$return .= <<<IPSCONTENT

			<!-- Already reviewed -->
		
IPSCONTENT;

elseif ( \IPS\Member::loggedin()->restrict_post ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->restrict_post == -1 ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'restricted_cannot_comment' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'restricted_cannot_comment', \IPS\Member::loggedIn()->warnings(5,NULL,'rpa'), \IPS\Member::loggedIn()->restrict_post );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'unacknowledged_warning_cannot_post', \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $album->reviews( NULL, NULL, NULL, 'desc', NULL, NULL, NULL, NULL, isset( \IPS\Widget\Request::i()->showDeleted ) ) ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !$album->hasReviewed() ):
$return .= <<<IPSCONTENT
<hr class='ipsHr'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			
IPSCONTENT;

if ( $album->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsButtonBar__pagination">
					{$album->reviewPagination( array( 'tab', 'sort' ) )}
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end'>
				<ul class="ipsDataFilters">
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url()->setQueryString( array( 'tab' => 'reviews', 'sort' => 'helpful' ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->sort ) or \IPS\Widget\Request::i()->sort != 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'most_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url()->setQueryString( array( 'tab' => 'reviews', 'sort' => 'newest' ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->sort ) and \IPS\Widget\Request::i()->sort == 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'newest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				</ul>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimodHeader( $album, '#reviews', 'review' );
$return .= <<<IPSCONTENT

			</div>
		</div>

		<div data-role='commentFeed' data-controller='core.front.core.moderation'>
			<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url()->csrf()->setQueryString( 'do', 'multimodReview' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
				
IPSCONTENT;

$reviewCount=0; $timeLastRead = $album->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $album->reviews( NULL, NULL, NULL, 'desc', NULL, NULL, NULL, NULL, isset( \IPS\Widget\Request::i()->showDeleted ) ) as $review ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$lined and $timeLastRead and $timeLastRead->getTimestamp() < $review->mapped('date') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $lined = TRUE and $reviewCount ):
$return .= <<<IPSCONTENT

							<hr class="ipsUnreadBar">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$reviewCount++;
$return .= <<<IPSCONTENT

					{$review->html()}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $album, 'review' );
$return .= <<<IPSCONTENT

			</form>
		</div>
		
IPSCONTENT;

if ( $album->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				<div class="ipsButtonBar__pagination">
					{$album->reviewPagination( array( 'tab', 'sort' ) )}
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( !$album->canReview() ):
$return .= <<<IPSCONTENT

		<p class="ipsEmptyMessage" data-role="noReviews">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function albums( $table, $headers, $albums ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $albums as $album ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $album instanceof \IPS\Content\Item ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$album = $album->asNode();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<li class='ipsColumns ipsColumns--padding ipsColumns--lines i-border-bottom_1 
IPSCONTENT;

if ( $album->asItem()->hidden() ):
$return .= <<<IPSCONTENT
 ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>

			<div class='ipsColumns__secondary i-basis_380'>
				<div class='ipsTitle ipsTitle--h4 ipsTitle--margin'>
					<div class='ipsBadges'>
						
IPSCONTENT;

if ( \IPS\gallery\Image::containerUnread( $album ) ):
$return .= <<<IPSCONTENT

							<span class="ipsIndicator" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_new_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $album->asItem()->mapped('featured') ):
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

if ( $album->type === \IPS\gallery\Album::AUTH_TYPE_PRIVATE ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_private_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

elseif ( $album->type === \IPS\gallery\Album::AUTH_TYPE_RESTRICTED ):
$return .= <<<IPSCONTENT

							<span class="ipsBadge ipsBadge--warning">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_friend_only_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<h3>
						
IPSCONTENT;

if ( \IPS\gallery\Image::containerUnread( $album ) ):
$return .= <<<IPSCONTENT
<strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($album->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_album', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

if ( \IPS\gallery\Image::containerUnread( $album ) ):
$return .= <<<IPSCONTENT
</strong>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</h3>
				</div>

				
IPSCONTENT;

if ( $result = $album->truncated() ):
$return .= <<<IPSCONTENT

					<div class='ipsRichText ipsTruncate_4'>
						{$result}
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				<div class="i-margin-top_3 i-flex i-flex-wrap_wrap i-gap_2">
					<ul class='ipsList ipsList--icons i-link-color_inherit i-font-weight_500 i-flex_11 i-basis_240'>
						
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->tab ) || \IPS\Widget\Request::i()->tab !== 'node_gallery_gallery' ):
$return .= <<<IPSCONTENT

							<li class='i-color_soft'><i class='fa-regular fa-user-circle'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'album_created_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 {$album->owner()->link()}</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $album->last_img_date ):
$return .= <<<IPSCONTENT

							<li class='i-color_soft'><i class="fa-regular fa-clock"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $album->last_img_date instanceof \IPS\DateTime ) ? $album->last_img_date : \IPS\DateTime::ts( $album->last_img_date );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
					<ul class='ipsList ipsList--icons i-link-color_inherit i-font-weight_500 i-flex_11 i-basis_240'>
						<li><i class="fa-solid fa-photo-film"></i> 
IPSCONTENT;

$pluralize = array( $album->count_imgs ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_images', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

if ( $album->use_comments && $album->comments > 0 ):
$return .= <<<IPSCONTENT

							<li><i class='fa-regular fa-comments'></i> 
IPSCONTENT;

$pluralize = array( $album->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_album_num_comments_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $album->allow_comments && $album->count_comments > 0 ):
$return .= <<<IPSCONTENT

							<li><i class='fa-solid fa-comment'></i> 
IPSCONTENT;

$pluralize = array( $album->count_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_image_comments_s', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $album->use_reviews && $album->reviews > 0 ):
$return .= <<<IPSCONTENT

							<li><i class='fa-solid fa-comment'></i> 
IPSCONTENT;

$pluralize = array( $album->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_album_num_reviews_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</div>

				
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

					<div class='i-margin-top_2'>
						<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $album ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='' class='ipsInput ipsInput--toggle'>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsColumns__primary'>
				
				<ul class='ipsMasonry ipsMasonry--album-summary i-basis_80' 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

foreach ( $album->_latestImages as $image  ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$imageWidth=isset( $image->_dimensions['small'][0] ) ? $image->_dimensions['small'][0] : 0;
$return .= <<<IPSCONTENT

                    	
IPSCONTENT;

$imageHeight=isset( $image->_dimensions['small'][1] ) ? $image->_dimensions['small'][1] : 0;
$return .= <<<IPSCONTENT

                    	<li class='ipsMasonry__item' 
IPSCONTENT;

if ( $imageWidth && $imageHeight ):
$return .= <<<IPSCONTENT
style='--i-ratio:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-imageId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							<figure class='ipsFigure'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main' aria-label="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									
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
			</div>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function categories(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class="ipsPageHeader ipsBox ipsPull">
    <div class="ipsPageHeader__row">
        <div class="ipsPageHeader__primary">
           <h1 class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
        </div>
        
IPSCONTENT;

if ( \IPS\gallery\Category::canOnAny('add') ):
$return .= <<<IPSCONTENT

            <ul class="ipsButtons ipsButtons--main">
                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryButtons( \IPS\gallery\Category::canOnAny('add'), NULL, NULL );
$return .= <<<IPSCONTENT

            </ul>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>
</header>


IPSCONTENT;

if ( \IPS\Settings::i()->gallery_overview_show_categories ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$clubNodes = \IPS\Settings::i()->club_nodes_in_apps ? \IPS\gallery\Category::clubNodes() : array();
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( $rootCategories = \IPS\gallery\Category::roots() ):
$return .= <<<IPSCONTENT

        <div class="ipsBox ipsPull">
            <h2 class='ipsBox__header'>
                <span>
IPSCONTENT;

if ( $clubNodes ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'community_image_categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
            </h2>
            <div class="ipsBox__content">
                <i-data>
                    <ul class='ipsData ipsData--wallpaper ipsData--gallery-categories'>
                        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryRow( NULL, NULL, $rootCategories );
$return .= <<<IPSCONTENT

                    </ul>
                </i-data>
            </div>
        </div>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


    
IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $clubNodes = \IPS\gallery\Category::clubNodes() ):
$return .= <<<IPSCONTENT

        <div class="ipsBox ipsPull">
            
IPSCONTENT;

if ( $rootCategories ):
$return .= <<<IPSCONTENT
<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node_gallery', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            <div class="ipsBox__content">
                <i-data>
                    <ul class='ipsData ipsData--wallpaper ipsData--gallery-club-categories'>
                        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryRow( NULL, NULL, $clubNodes );
$return .= <<<IPSCONTENT

                    </ul>
                </i-data>
            </div>
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

	function categoriesSidebar( $currentCategory=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$categories = $currentCategory ? $currentCategory->children() : \IPS\gallery\Category::roots();
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count($categories) ):
$return .= <<<IPSCONTENT

<div id='elGalleryCategories' class='i-margin-top_3'>
	<h3 class='ipsBox__header'>
IPSCONTENT;

if ( $currentCategory ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subcategories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h3>
	<div class='ipsSideMenu ipsSideMenu--truncate'>
		<ul class='ipsSideMenu__list'>
			
IPSCONTENT;

foreach ( $categories as $category ):
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'>
						<strong class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
						<span class='ipsBadge ipsBadge--soft cGalleryCategoryCount'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\gallery\Image::contentCount( $category ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</a>
					
IPSCONTENT;

if ( $category->hasChildren() ):
$return .= <<<IPSCONTENT

						<ul class="ipsSideMenu__list">
							
IPSCONTENT;

foreach ( $category->children() as $idx => $subcategory ):
$return .= <<<IPSCONTENT

								<li>
									
IPSCONTENT;

if ( $idx >= 5 ):
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item'><span class='i-color_soft i-font-size_-2'>
IPSCONTENT;

$pluralize = array( \count( $category->children() ) - 5 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'and_x_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></a>
										
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'>
											<span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
											<strong class='i-font-size_-2 cGalleryCategoryCount'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\gallery\Image::contentCount( $subcategory ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
										</a>
									
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

				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=browse&do=categories", null, "gallery_categories", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--wide ipsButton--small ipsButton--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'browse_categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 &nbsp;<i class='fa-solid fa-caret-right'></i></a>
	</div>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function category( $category, $albums, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $category->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $category );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id="elClubContainer">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<header class="ipsPageHeader ipsBox ipsPull">
	<div class="ipsPageHeader__row">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "header:before", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "header:inside-start", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "title:before", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "title:inside-start", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "title:inside-end", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "title:after", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->language()->get('gallery_category_' . $category->_id . '_desc') ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $category->description, array('') );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "header:inside-end", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "header:after", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "buttons:before", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="buttons" class="ipsButtons">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "buttons:inside-start", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "analytics", "cloud" )->analyticsButton( $category, $category->id );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$followerCount = \IPS\gallery\Image::containerFollowerCount( $category );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'gallery', 'category', $category->_id, $followerCount );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "buttons:inside-end", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/category", "buttons:after", [ $category,$albums,$table ] );
$return .= <<<IPSCONTENT

	</div>
	
IPSCONTENT;

if ( $category->show_rules ):
$return .= <<<IPSCONTENT

		<div class="ipsPageHeader__row" data-controller="core.front.core.lightboxedImages">
			
IPSCONTENT;

if ( $category->show_rules == 1 ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->rules_link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsJS_show" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$val = "gallery_category_{$category->id}_rulestitle"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-content="#elCategoryRules">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'category_rules', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				<div id="elCategoryRules" class="ipsHide">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('gallery_category_' . $category->id . '_rules'), array('') );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

elseif ( $category->show_rules == 2 ):
$return .= <<<IPSCONTENT

				<strong>
IPSCONTENT;

$val = "gallery_category_{$category->id}_rulestitle"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('gallery_category_' . $category->id . '_rules'), array('') );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</header>


IPSCONTENT;

if ( $category->can('add') and ( $category->allow_albums != 2 or \IPS\Member::loggedIn()->group['g_create_albums'] or \IPS\gallery\Album::loadForSubmit( $category ) ) ):
$return .= <<<IPSCONTENT

	<ul class="ipsButtons ipsButtons--main">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryButtons( $category->can('add'), $category, NULL );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $category->children() ):
$return .= <<<IPSCONTENT

	<div class="ipsBox ipsBox--padding ipsPull">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "gallery" )->categoryGrid( $category->children() );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $albums ):
$return .= <<<IPSCONTENT

	<div class="ipsBox ipsPull" 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
 data-controller="gallery.front.global.nsfw" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		{$albums}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $table ):
$return .= <<<IPSCONTENT

	<div class="ipsBox ipsPull">
		{$table}
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class="ipsBox ipsPageActions ipsBox--padding ipsPull ipsResponsive_showPhone">
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'gallery', 'category', $category->_id, $followerCount );
$return .= <<<IPSCONTENT

</div>


IPSCONTENT;

if ( $category->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function categoryButtons( $canSubmitImages, $currentCategory=NULL, $currentAlbum=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$currentCategory ):
$return .= <<<IPSCONTENT

    <li>
        <ul class="ipsButtonGroup">
            <li>
                <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery", null, "gallery", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->do=='categories' ):
$return .= <<<IPSCONTENT
 ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipstooltip="" data-ipstooltip-safe="" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_overview', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow">
                    <i class="fa-regular fa-images"></i>
                </a>
            </li>
            <li>
                <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=browse&do=categories", null, "gallery_categories", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->do=='categories' ):
$return .= <<<IPSCONTENT
 ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipstooltip="" data-ipstooltip-safe="" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_view_categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow">
                    <i class="fa-solid fa-folder-tree"></i>
                </a>
            </li>
        </ul>
    </li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $currentCategory AND $currentCategory->cover_img_id AND \IPS\gallery\Image::modPermission( 'edit', \IPS\Member::loggedIn(), $currentCategory ) ):
$return .= <<<IPSCONTENT

	<li>
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currentCategory->url()->setQueryString( array( 'do' => 'unsetCoverPhoto' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_unset_cover', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_unset_cover', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<li>
    <a class="ipsButton ipsButton--primary" data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-extraClass='cGalleryDialog_outer' data-ipsDialog-destructOnClose='true' data-ipsDialog-remoteSubmit='true' 
IPSCONTENT;

if ( $currentAlbum ):
$return .= <<<IPSCONTENT
href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=submit&category={$currentCategory->id}&album={$currentAlbum->id}&_new=1", null, "gallery_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

elseif ( $currentCategory ):
$return .= <<<IPSCONTENT
 href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=submit&category={$currentCategory->id}&_new=1", null, "gallery_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
 href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=submit&_new=1", null, "gallery_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 rel='nofollow noindex'><i class="fa-solid fa-photo-film"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_gallery_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
</li>
IPSCONTENT;

		return $return;
}

	function categoryCarousel( $categories ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $categories ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $categories as $category ):
$return .= <<<IPSCONTENT

		<li class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
			<figure class='ipsFigure i-margin-bottom_2'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main i-aspect-ratio_7'>
					
IPSCONTENT;

if ( $category->lastImage() && $category->lastImage()->media && !$category->cover_img_id ):
$return .= <<<IPSCONTENT

						<video preload="metadata" loading="lazy"
IPSCONTENT;

if ( $category->lastImage()->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $category->lastImage()->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
							<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $category->lastImage()->original_file_name )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$category->lastImage()->masked_file_name  ):
$return .= <<<IPSCONTENT
#t=1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->lastImage()->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
						</video>
						<i class="ipsFigure__play"></i>
					
IPSCONTENT;

elseif ( $category->coverPhoto('small') !== NULL ):
$return .= <<<IPSCONTENT

						<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->coverPhoto('small'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i class="ipsFigure__icon"></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<span class='ipsInvisible'>
IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $club = $category->club() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $category->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
				</a>
				
IPSCONTENT;

if ( $category->lastImage() && $category->lastImage()->media && !$category->cover_img_id ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $category->lastImage() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $category->coverPhoto('small') !== NULL ):
$return .= <<<IPSCONTENT

	                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $category->coverPhotoObject() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</figure>
			<h3 class='ipsTitle ipsTitle--h5'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					
IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $club = $category->club() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$sprintf = array($club->name, $category->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</a>
			</h3>
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

	function categoryGrid( $categories ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $categories ) ):
$return .= <<<IPSCONTENT

	<ul class='ipsGrid i-basis_300 i-text-align_center'
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
 data-controller="gallery.front.global.nsfw"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		
IPSCONTENT;

foreach ( $categories as $category ):
$return .= <<<IPSCONTENT

			<li class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
				<figure class='ipsFigure i-margin-bottom_2'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main i-aspect-ratio_8'>
						
IPSCONTENT;

if ( $category->lastImage() && $category->lastImage()->media && !$category->cover_img_id ):
$return .= <<<IPSCONTENT

							<video preload="metadata" loading="lazy"
IPSCONTENT;

if ( $category->lastImage()->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $category->lastImage()->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $category->lastImage()->original_file_name )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$category->lastImage()->masked_file_name  ):
$return .= <<<IPSCONTENT
#t=1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->lastImage()->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
							</video>
							<i class="ipsFigure__play"></i>
						
IPSCONTENT;

elseif ( $category->coverPhoto('small') !== NULL ):
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->coverPhoto('small'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i class="ipsFigure__icon"></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<span class="ipsInvisible">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</a>
					
IPSCONTENT;

if ( $category->lastImage() && $category->lastImage()->media && !$category->cover_img_id ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $category->lastImage() );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( $category->coverPhoto('small') !== NULL ):
$return .= <<<IPSCONTENT

		                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $category->coverPhotoObject() );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</figure>

				<h2 class='ipsTitle ipsTitle--h5 ipsTitle--margin'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $club = $category->club() ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$sprintf = array($club->name, $category->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
				</h2>
				<ul class='ipsList ipsList--inline i-color_soft i-justify-content_center i-font-size_-1'>
					
IPSCONTENT;

if ( $category->lastImage() !== NULL ):
$return .= <<<IPSCONTENT
<li><i class='fa-regular fa-clock'></i> 
IPSCONTENT;

$val = ( $category->lastImage()->date instanceof \IPS\DateTime ) ? $category->lastImage()->date : \IPS\DateTime::ts( $category->lastImage()->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $category->allow_comments && $category->_commentsForDisplay > 0 ):
$return .= <<<IPSCONTENT

						<li data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $category->_commentsForDisplay ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_comment_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-comment'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_commentsForDisplay, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $category->allow_albums && $category->public_albums > 0 ):
$return .= <<<IPSCONTENT

						<li data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $category->public_albums ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_album_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-table-cells-large'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->public_albums, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$count = \IPS\gallery\Image::contentCount( $category );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $count > 0 ):
$return .= <<<IPSCONTENT

						<li data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_img_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-camera'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
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

IPSCONTENT;

		return $return;
}

	function categoryRow( $table, $headers, $categories ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $categories as $category ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $category->can('view') ):
$return .= <<<IPSCONTENT

		<li class="ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
			<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $club = $category->club() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $category->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
			<figure class='ipsData__image'>
				
IPSCONTENT;

if ( $category->lastImage() && $category->lastImage()->media && !$category->cover_img_id ):
$return .= <<<IPSCONTENT

					<video preload="metadata" loading="lazy"
IPSCONTENT;

if ( $category->lastImage()->masked_file_name  ):
$return .= <<<IPSCONTENT
 poster="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $category->lastImage()->masked_file_name )->url;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<source src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $category->lastImage()->original_file_name )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$category->lastImage()->masked_file_name  ):
$return .= <<<IPSCONTENT
#t=1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->lastImage()->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' />
					</video>
				
IPSCONTENT;

elseif ( $category->coverPhoto('small') !== NULL ):
$return .= <<<IPSCONTENT

					<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->coverPhoto('small'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<i></i>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $category->lastImage() && $category->lastImage()->media && !$category->cover_img_id ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $category->lastImage() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( $category->coverPhoto('small') !== NULL ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $category->coverPhotoObject() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</figure>
			<div class="ipsData__content">
				<div class="ipsData__main">
					<h2 class='ipsData__title'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $club = $category->club() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$sprintf = array($club->name, $category->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
					</h2>
				</div>
				<div class="ipsData__extra">
					
IPSCONTENT;

if ( $category->lastImage() !== NULL ):
$return .= <<<IPSCONTENT

						<div class="ipsData__last">
							
IPSCONTENT;

$val = ( $category->lastImage()->date instanceof \IPS\DateTime ) ? $category->lastImage()->date : \IPS\DateTime::ts( $category->lastImage()->date );$return .= $val->html();
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<ul class='ipsData__stats'>
						
IPSCONTENT;

if ( $category->allow_comments and $category->_commentsForDisplay ):
$return .= <<<IPSCONTENT

							<li data-statType='album_comments'>
								<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_commentsForDisplay, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $category->_commentsForDisplay ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_comment_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
								<span class='ipsData__stats-label'>
IPSCONTENT;

$pluralize = array( $category->_commentsForDisplay ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_comment_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $category->allow_albums and $category->public_albums ):
$return .= <<<IPSCONTENT

							<li data-statType='albums'>
								<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->public_albums, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $category->public_albums ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_album_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
								<span class='ipsData__stats-label'>
IPSCONTENT;

$pluralize = array( $category->public_albums ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_album_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$count = \IPS\gallery\Image::contentCount( $category );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $count ):
$return .= <<<IPSCONTENT

							<li data-statType='albums'>
								<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_img_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
								<span class='ipsData__stats-label'>
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cat_img_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			</div>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function imageTable( $table, $headers, $rows, $quickSearch ) {
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

IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
,gallery.front.global.nsfw
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( \IPS\Widget\Request::i()->app == 'gallery' ):
$return .= <<<IPSCONTENT
<h2 class='ipsBox__header' hidden>
IPSCONTENT;

$pluralize = array( $table->count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_images', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $table->canModerate() OR ( $table->showAdvancedSearch AND ( (isset( $table->sortOptions ) and !empty( $table->sortOptions )) OR $table->advancedSearch ) ) OR !empty( $table->filters ) OR $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsButtonBar__pagination" data-role="tablePagination">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end i-gap_3 i-row-gap_1'>
				<ul class="ipsDataFilters">
					
IPSCONTENT;

if ( $table->showAdvancedSearch AND ( ( isset( $table->sortOptions ) and !empty( $table->sortOptions ) ) OR $table->advancedSearch ) ):
$return .= <<<IPSCONTENT

						<li>
							<a href="#elSortByMenu_menu" class='ipsDataFilters__button' id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role='sortButton' data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio">
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $col, 'sortdirection' => $table->getSortDirection( $col ), 'page' => '1' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
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
' rel="nofollow">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

						<li>
							<a href="#elFilterByMenu_menu" class='ipsDataFilters__button' data-role="tableFilterMenu" id="elFilterByMenu_
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => '', 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection, 'page' => '1' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="nofollow">
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection, 'page' => '1' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' rel="nofollow">
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

					
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

						<li>
							<a class="ipsJS_show ipsDataFilters__button" href="#elCheck_menu" id="elCheck_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$val = "{$table->langPrefix}select_rows_tooltip"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsAutoCheck data-ipsAutoCheck-context="#elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active">
								<span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span> <i class="fa-solid fa-caret-down"></i>
								<span class='ipsNotification' data-role='autoCheckCount'>0</span>
							</a>
							<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="elCheck_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
								<li class="ipsMenu_title">
IPSCONTENT;

$val = "{$table->langPrefix}select_rows"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
								<li class="ipsMenu_item" data-ipsMenuValue="all"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								<li class="ipsMenu_item" data-ipsMenuValue="none"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

if ( \count($table->getFilters()) ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_sep"><hr></li>
									
IPSCONTENT;

foreach ( $table->getFilters() as $filter ):
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item" data-ipsMenuValue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $filter, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><a href="#">
IPSCONTENT;

$val = "{$filter}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

	                <form action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' method='post' class='i-display_contents'>
	                    <input type='hidden' name='csrfKey' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	                    <ul class='ipsDataFilters'>
	                        <li>
	                            <button type='submit' name='thumbnailSize' value='thumb' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_as_thumbnails', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsDataFilters__button 
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->cookie['thumbnailSize'] ) OR \IPS\Widget\Request::i()->cookie['thumbnailSize'] == 'thumb'  ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-table-cells-large'></i></button>
	                        </li>
	                        <li>
	                            <button type='submit' name='thumbnailSize' value='large' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_as_large', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsDataFilters__button 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->cookie['thumbnailSize'] ) AND \IPS\Widget\Request::i()->cookie['thumbnailSize'] == 'large'  ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-square'></i></button>
	                        </li>
	                        <li>
	                            <button type='submit' name='thumbnailSize' value='rows' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_as_list', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsDataFilters__button 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->cookie['thumbnailSize'] ) AND \IPS\Widget\Request::i()->cookie['thumbnailSize'] == 'rows'  ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-table-list'></i></button>
	                        </li>
	                    </ul>
	                </form>
	            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	        </div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

		<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-role='moderationTools' data-ipsPageAction>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !empty( $rows )  ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->cookie['thumbnailSize'] ) AND \IPS\Widget\Request::i()->cookie['thumbnailSize'] == 'large' AND \IPS\Widget\Request::i()->controller != 'search'  ):
$return .= <<<IPSCONTENT

			<div class='cGalleryLargeList' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='tableRows'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "gallery" )->tableRowsLarge( $table, $headers, $rows );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

elseif ( isset( \IPS\Widget\Request::i()->cookie['thumbnailSize'] ) AND \IPS\Widget\Request::i()->cookie['thumbnailSize'] == 'rows' AND \IPS\Widget\Request::i()->controller != 'search'  ):
$return .= <<<IPSCONTENT

			<i-data>
				<div class="ipsData ipsData--table ipsData--gallery-row-list cGalleryRowsList" id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='tableRows'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "gallery" )->tableRowsRows( $table, $headers, $rows );
$return .= <<<IPSCONTENT

				</div>
			</i-data>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<ul class='ipsMasonry i-padding_1' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='tableRows'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "gallery" )->tableRowsThumbs( $table, $headers, $rows );
$return .= <<<IPSCONTENT

			</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
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

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

			<div class="ipsData__modBar ipsJS_hide" data-role="pageActionOptions">
				<select class="ipsInput ipsInput--select i-basis_300" name="modaction" data-role="moderationAction">
					
IPSCONTENT;

if ( $table->canModerate('unhide') ):
$return .= <<<IPSCONTENT

						<option value='approve' data-icon='check-circle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate('feature') or $table->canModerate('unfeature') ):
$return .= <<<IPSCONTENT

						<optgroup label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'feature', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-icon='star' data-action='feature'>
							
IPSCONTENT;

if ( $table->canModerate('feature') ):
$return .= <<<IPSCONTENT

								<option value='feature'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'feature', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $table->canModerate('unhide') ):
$return .= <<<IPSCONTENT

								<option value='unfeature'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unfeature', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</optgroup>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate('pin') or $table->canModerate('unpin') ):
$return .= <<<IPSCONTENT

						<optgroup label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-icon='thumb-tack' data-action='pin'>
							
IPSCONTENT;

if ( $table->canModerate('pin') ):
$return .= <<<IPSCONTENT

								<option value='pin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $table->canModerate('unpin') ):
$return .= <<<IPSCONTENT

								<option value='unpin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unpin', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</optgroup>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate('hide') or $table->canModerate('unhide') ):
$return .= <<<IPSCONTENT

						<optgroup label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-icon='eye' data-action='hide'>
							
IPSCONTENT;

if ( $table->canModerate('hide') ):
$return .= <<<IPSCONTENT

								<option value='hide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $table->canModerate('unhide') ):
$return .= <<<IPSCONTENT

								<option value='unhide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unhide', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</optgroup>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate('lock') or $table->canModerate('unlock') ):
$return .= <<<IPSCONTENT

						<optgroup label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'lock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-icon='lock' data-action='lock'>
							
IPSCONTENT;

if ( $table->canModerate('lock') ):
$return .= <<<IPSCONTENT

								<option value='lock'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'lock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $table->canModerate('unlock') ):
$return .= <<<IPSCONTENT

								<option value='unlock'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unlock', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</optgroup>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate('move') ):
$return .= <<<IPSCONTENT

						<option value='move' data-icon='arrow-right'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'move', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate('split_merge') ):
$return .= <<<IPSCONTENT

						<option value='merge' data-icon='level-up'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'merge', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate('delete') ):
$return .= <<<IPSCONTENT

						<option value='delete' data-icon='trash'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate( 'tag' ) ):
$return .= <<<IPSCONTENT

					    <optgroup label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-icon='tag' data-action='tag'>
					        <option value='tag'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_single_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					        <option value='untag'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_single_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					    </optgroup>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->savedActions ):
$return .= <<<IPSCONTENT

						<optgroup label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'saved_actions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-icon='tasks' data-action='saved_actions'>
							
IPSCONTENT;

foreach ( $table->savedActions as $k => $v ):
$return .= <<<IPSCONTENT

								<option value='savedAction-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</option>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</optgroup>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</select>
				<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</form>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
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

	function index( $featured, $new, $recentlyUpdatedAlbums, $recentComments ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class="ipsPageHeader">
    <div class="ipsPageHeader__row">
        
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "header:before", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "header:inside-start", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT

               
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "title:before", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "title:inside-start", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "title:inside-end", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "title:after", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "header:inside-end", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "header:after", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( \IPS\gallery\Category::canOnAny('add') ):
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "buttons:before", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="buttons" class="ipsButtons ipsButtons--main">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "buttons:inside-start", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryButtons( \IPS\gallery\Category::canOnAny('add'), NULL, NULL );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "buttons:inside-end", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "gallery/front/browse/index", "buttons:after", [ $featured,$new,$recentlyUpdatedAlbums,$recentComments ] );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>
</header>


IPSCONTENT;

if ( \IPS\Settings::i()->gallery_overview_show_categories ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

$clubNodes = \IPS\Settings::i()->club_nodes_in_apps ? \IPS\gallery\Category::clubNodes() : array();
$return .= <<<IPSCONTENT

    <section class="ipsBox ipsPull">
        <header class="ipsBox__header">
            <h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
            <span class="ipsBox__header-secondary">
                <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=browse&do=categories", null, "gallery_categories", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_view_all_categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
            </span>
        </header>
        <div class="ipsBox__content">
            <ul class="ipsCarousel ipsCarousel--padding i-basis_240 i-text-align_center" 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="gallery-categories" tabindex="0">
                
IPSCONTENT;

if ( $rootCategories = \IPS\gallery\Category::roots() ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "gallery" )->categoryCarousel( $rootCategories );
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
                
IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $clubNodes = \IPS\gallery\Category::clubNodes() ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "gallery" )->categoryCarousel( $clubNodes );
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            </ul>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'gallery-categories' );
$return .= <<<IPSCONTENT

        </div>
    </section>    

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !empty( $featured ) ):
$return .= <<<IPSCONTENT

    <section class="ipsBox ipsPull" 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
        <header class="ipsBox__header">
            <h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured_images', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'gallery-featured' );
$return .= <<<IPSCONTENT

        </header>
        <div class="ipsBox__content">
            <ul class="ipsCarousel ipsCarousel--gallery-featured" id="gallery-featured" tabindex="0">
                
IPSCONTENT;

foreach ( $featured as $image ):
$return .= <<<IPSCONTENT

                    <li>
                        <figure class="ipsFigure ipsFigure--contain" 
IPSCONTENT;

if ( !$image->media  ):
$return .= <<<IPSCONTENT
style="--_backdrop: url('
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
')" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                            <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'context', 'featured' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsFigure__main" aria-hidden="true" tabindex="-1">
                                
IPSCONTENT;

if ( $image->media  ):
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
" type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></source>
                                    </video>
                                    <i class="ipsFigure__play"></i>
                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                    <img src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                            </a>
                            <div class="ipsFigure__header">
                                
IPSCONTENT;

if ( $image->hidden() === -1 ):
$return .= <<<IPSCONTENT

                                    <span class="ipsBadge ipsBadge--warning" data-ipstooltip title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->hiddenBlurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-eye-slash"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                                
IPSCONTENT;

elseif ( $image->hidden() === 1 ):
$return .= <<<IPSCONTENT

                                    <span class="ipsBadge ipsBadge--warning"><i class="fa-solid fa-triangle-exclamation"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                            </div>
                            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image );
$return .= <<<IPSCONTENT

                            <figcaption class="ipsFigure__footer">
                                <div class="ipsFigure__title">
                                    
IPSCONTENT;

if ( $image->unread() ):
$return .= <<<IPSCONTENT

                                        <span class="ipsIndicator" data-ipstooltip title="
IPSCONTENT;

if ( $image->unread() === -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"></span>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'context', 'featured' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" tabindex="-1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
                                </div>
                                <div>
                                    
IPSCONTENT;

$htmlsprintf = array($image->author()->link( $image->warningRef() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate_itemprop', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

                                </div>
                            </figcaption>
                        </figure>
                    </li>
                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            </ul>
        </div>
    </section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !empty( $new ) ):
$return .= <<<IPSCONTENT

    <section class="ipsBox ipsPull" 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
 data-controller="gallery.front.global.nsfw" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
        <h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_images', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
        <div class="i-padding_1">
            <ul class="ipsMasonry">
                
IPSCONTENT;

foreach ( $new as $image ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$imageWidth=isset( $image->_dimensions['small'][0] ) ? $image->_dimensions['small'][0] : 0;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$imageHeight=isset( $image->_dimensions['small'][1] ) ? $image->_dimensions['small'][1] : 0;
$return .= <<<IPSCONTENT

                    <li class="ipsMasonry__item" 
IPSCONTENT;

if ( $imageWidth && $imageHeight ):
$return .= <<<IPSCONTENT
style="--i-ratio:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
                        <figure class="ipsFigure ipsFigure--hover">
                            <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'context', 'new' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsFigure__main">
                                
IPSCONTENT;

if ( $image->media  ):
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
" type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></source>
                                    </video>
                                    <i class="ipsFigure__play"></i>
                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                    <img src="
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->small_file_name )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                <span class="ipsInvisible">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
                            </a>
                            <div class="ipsFigure__header">
                                
IPSCONTENT;

foreach ( $image->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                            </div>
                            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image );
$return .= <<<IPSCONTENT

                            <figcaption class="ipsFigure__footer">
                                <div class="ipsFigure__title">
                                    
IPSCONTENT;

if ( $image->unread() ):
$return .= <<<IPSCONTENT

                                        <span class="ipsIndicator" data-ipstooltip title="
IPSCONTENT;

if ( $image->unread() === -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"></span>
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                    <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'context', 'new' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" tabindex="-1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
                                </div>
                                <div>
                                    
IPSCONTENT;

$htmlsprintf = array($image->author()->link( $image->warningRef() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate_itemprop', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

                                </div>
                            </figcaption>
                        </figure>
                    </li>
                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            </ul>
        </div>
    </section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $recentlyUpdatedAlbums ) ):
$return .= <<<IPSCONTENT

	<section class="ipsBox ipsPull" 
IPSCONTENT;

if ( \IPS\Settings::i()->gallery_nsfw ):
$return .= <<<IPSCONTENT
data-controller="gallery.front.global.nsfw" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_recently_updated_albums', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
        <div class="i-padding_3">
            <ul class="ipsGrid i-basis_220 i-gap_3 i-text-align_center">
                
IPSCONTENT;

foreach ( $recentlyUpdatedAlbums as $album ):
$return .= <<<IPSCONTENT

	                <li class="i-_margin-block_2">
	                	<figure class="ipsFigure">
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsFigure__main i-aspect-ratio_7" aria-hidden="true" tabindex="-1">
								
IPSCONTENT;

if ( $album->asNode()->coverPhoto('masked') ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->asNode()->coverPhoto('masked'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                    <i class="ipsFigure__icon"></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</a>
                            <span class="ipsInvisible">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
                            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $album->asNode()->coverPhotoObject() );
$return .= <<<IPSCONTENT

						</figure>
						<h2 class="ipsTitle ipsTitle--h5">
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						</h2>
						<ul class="ipsList ipsList--inline i-justify-content_center i-font-size_-1 i-color_soft">
							<li data-ipstooltip title="
IPSCONTENT;

$pluralize = array( $album->count_imgs ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_images', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-camera-retro i-opacity_5"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->count_imgs, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
                            
IPSCONTENT;

if ( $album->use_comments && $album->comments > 0 ):
$return .= <<<IPSCONTENT

                            <li data-ipstooltip title="
IPSCONTENT;

$pluralize = array( $album->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_album_num_comments_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-comment i-opacity_5"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                            
IPSCONTENT;

if ( $album->allow_comments && $album->count_comments > 0 ):
$return .= <<<IPSCONTENT

                            <li data-ipstooltip title="
IPSCONTENT;

$pluralize = array( $album->count_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'gallery_image_comments_s', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-images i-opacity_5"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->count_comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
                            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					</li>
                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            </ul>
        </div>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function indexSidebar( $canSubmitImages, $currentCategory=NULL, $currentAlbum=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function miniAlbum( $album ) {
		$return = '';
		$return .= <<<IPSCONTENT


<ol class='ipsMasonry ipsMasonry--mini-album i-basis_70 i-margin-bottom_2'>
	
IPSCONTENT;

foreach ( $album->_latestImages as $image ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $image->small_file_name ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$imageWidth=isset( $image->_dimensions['small'][0] ) ? $image->_dimensions['small'][0] : 0;
$return .= <<<IPSCONTENT

      		
IPSCONTENT;

$imageHeight=isset( $image->_dimensions['small'][1] ) ? $image->_dimensions['small'][1] : 0;
$return .= <<<IPSCONTENT

			<li class='ipsMasonry__item' 
IPSCONTENT;

if ( $imageWidth && $imageHeight ):
$return .= <<<IPSCONTENT
style='--i-ratio:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-imageID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				<figure class='ipsFigure'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'browse', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
				</figure>
			</li>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li class='ipsMasonry__item' data-imageID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				<figure class='ipsFigure'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'browse', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
						
IPSCONTENT;

if ( $image->media  ):
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

endif;
$return .= <<<IPSCONTENT

					</a>
				</figure>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ol>
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $album->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_soft i-font-weight_600'><i class="fa-regular fa-images"></i> 
IPSCONTENT;

$sprintf = array($album->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_entire_album', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

		return $return;
}

	function noImages( $container ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-text-align_center i-padding_3'>
	<p class='i-font-size_2'>
IPSCONTENT;

if ( $container instanceof \IPS\gallery\Album ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_images_in_album', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_images_in_cat', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

if ( $container->can('add') ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=submit&_new=1", null, "gallery_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $container instanceof \IPS\gallery\Album ):
$return .= <<<IPSCONTENT
&category=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->category_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
&album=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
&category=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $container->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-extraClass='cGalleryDialog_outer' data-ipsDialog-close='false' data-ipsDialog-destructOnClose='true' data-ipsDialog-remoteSubmit='true'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_first_image', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function tableRowsLarge( $table, $headers, $images ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $images as $image ):
$return .= <<<IPSCONTENT

	<div class='
IPSCONTENT;

if ( $image->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-padding_3 i-border-bottom_1 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-imageId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
		<figure class='ipsFigure'>
			
IPSCONTENT;

if ( $image->media  ):
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_video', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
					<video data-role="video" loading="lazy" 
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
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
					
IPSCONTENT;

$sizes = $image->_dimensions;
$return .= <<<IPSCONTENT

					<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->masked_file_name )->url;
$return .= <<<IPSCONTENT
' width="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $sizes['large'][0], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" height="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $sizes['large'][1], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image );
$return .= <<<IPSCONTENT

        </figure>
		<div class='i-margin-top_2'>
			<div class='i-flex i-flex-wrap_wrap i-align-items_center i-justify-content_space-between'>
				<div class='ipsTitle ipsTitle--h4'>
					<h2 class=''>
						
IPSCONTENT;

if ( $image->unread() ):
$return .= <<<IPSCONTENT
<span class='ipsIndicator' data-ipsTooltip title='
IPSCONTENT;

if ( $image->unread() === -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->prefix() ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $image->prefix( TRUE ), $image->prefix() );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

if ( $image->unread() ):
$return .= <<<IPSCONTENT
<strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
					</h2>
					<div class='ipsBadges'>
					    
IPSCONTENT;

foreach ( $image->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</div>
				</div>
				<ul class='ipsList ipsList--inline'>
					
IPSCONTENT;

if ( $image->directContainer()->allow_comments && $image->comments > 0 ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$pluralize = array( $image->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $image->views > 0 ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$pluralize = array( $image->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

						<li>
							<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

if ( $image->mapped('featured') ):
$return .= <<<IPSCONTENT
unfeature
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
feature
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->mapped('pinned') ):
$return .= <<<IPSCONTENT
unpin
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
pin
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->hidden() === -1 ):
$return .= <<<IPSCONTENT
unhide
IPSCONTENT;

elseif ( $image->hidden() === 1 ):
$return .= <<<IPSCONTENT
approve
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->mapped('locked') ):
$return .= <<<IPSCONTENT
unlock
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
lock
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 move delete" data-state='
IPSCONTENT;

if ( $image->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsInput ipsInput--toggle'>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
	</div>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function tableRowsRows( $table, $headers, $images ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $images ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $images as $image ):
$return .= <<<IPSCONTENT

		<div
            class='ipsData__item ipsData__item--gallery-row 
IPSCONTENT;

if ( method_exists( $image, "tableClass" ) && $image->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->ui( "css" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
            data-imageId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
            data-rowid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
            
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

if ( $image->unread() ):
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
			<figure class='ipsFigure ipsFigure--ratio'>
				
IPSCONTENT;

if ( $image->media  ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_video', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsFigure__main'>
						<video data-role="video" loading="lazy"
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
" type='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->file_type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
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

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image, FALSE );
$return .= <<<IPSCONTENT

			</figure>
            <div class='ipsData__content'>
				<div class='ipsData__main'>
					<div class='ipsData__title'>
						<div class='ipsBadges'>
						    
IPSCONTENT;

foreach ( $image->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


							
IPSCONTENT;

if ( $image->prefix() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $image->prefix( TRUE ), $image->prefix() );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						
IPSCONTENT;

if ( $image->unread() ):
$return .= <<<IPSCONTENT

							<span class='ipsIndicator' data-ipsTooltip title='
IPSCONTENT;

if ( $image->unread() === -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<h4><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($image->caption); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_image', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $image->canEdit() ):
$return .= <<<IPSCONTENT
data-role="editableTitle"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</a></h4>
					</div>
					<p class='ipsData__meta'>
IPSCONTENT;

$htmlsprintf = array($image->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>			
					<div class='ipsData__desc ipsRichText ipsTruncate_4 i-margin-top_2'>
						{$image->truncated()}
					</div>

					<ul class='ipsData__stats'>
						
IPSCONTENT;

if ( $image->directContainer()->allow_comments ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $image->comments ):
$return .= <<<IPSCONTENT

								<li><i class="fa-regular fa-comment"></i> 
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setFragment('replies'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='i-color_root i-font-weight_500'>
IPSCONTENT;

$pluralize = array( $image->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<li><i class="fa-regular fa-clock"></i> 
IPSCONTENT;

if ( $image->updated == $image->date ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'uploaded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $image->updated instanceof \IPS\DateTime ) ? $image->updated : \IPS\DateTime::ts( $image->updated );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					</ul>

					
IPSCONTENT;

if ( \count( $image->tags() ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $image->tags() );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
			
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

				<div class='ipsData__mod'>
					<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $image ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( $image->unread() === -1 or $image->unread() === 1 ):
$return .= <<<IPSCONTENT
unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->hidden() === -1 ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

elseif ( $image->hidden === 1 ):
$return .= <<<IPSCONTENT
unapproved
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->mapped('pinned') ):
$return .= <<<IPSCONTENT
pinned
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->mapped('featured') ):
$return .= <<<IPSCONTENT
featured
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->mapped('locked') ):
$return .= <<<IPSCONTENT
locked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class="ipsInput ipsInput--toggle">
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function tableRowsThumbs( $table, $headers, $images ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $images as $image ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$imageWidth=isset( $image->_dimensions['small'][0] ) ? $image->_dimensions['small'][0] : 0;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$imageHeight=isset( $image->_dimensions['small'][1] ) ? $image->_dimensions['small'][1] : 0;
$return .= <<<IPSCONTENT

	<li class='ipsMasonry__item 
IPSCONTENT;

if ( $image->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $imageWidth && $imageHeight ):
$return .= <<<IPSCONTENT
style='--i-ratio:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageWidth, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
/
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $imageHeight, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
		<figure class='ipsFigure ipsFigure--hover'>
			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'context', 'new' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsFigure__main'>
				
IPSCONTENT;

if ( $image->media  ):
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

					<img src='
IPSCONTENT;

$return .= \IPS\File::get( "gallery_Images", $image->small_file_name )->url;
$return .= <<<IPSCONTENT
' alt="" loading="lazy">
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<span class="ipsInvisible">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
            </a>
			<div class='ipsFigure__header'>
			    
IPSCONTENT;

foreach ( $image->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

					<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

if ( $image->mapped('featured') ):
$return .= <<<IPSCONTENT
unfeature
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
feature
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->mapped('pinned') ):
$return .= <<<IPSCONTENT
unpin
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
pin
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->hidden() === -1 ):
$return .= <<<IPSCONTENT
unhide
IPSCONTENT;

elseif ( $image->hidden() === 1 ):
$return .= <<<IPSCONTENT
approve
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $image->mapped('locked') ):
$return .= <<<IPSCONTENT
unlock
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
lock
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 move delete" data-state='
IPSCONTENT;

if ( $image->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsInput ipsInput--toggle'>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "gallery", 'front' )->nsfwOverlay( $image );
$return .= <<<IPSCONTENT

			<figcaption class='ipsFigure__footer'>
				<div class='ipsFigure__title'>
					
IPSCONTENT;

if ( $image->unread() ):
$return .= <<<IPSCONTENT

						<span class='ipsIndicator' data-ipsTooltip title='
IPSCONTENT;

if ( $image->unread() === -1 ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url()->setQueryString( 'context', 'new' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" tabindex='-1'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				</div>
				<div>
					
IPSCONTENT;

$htmlsprintf = array($image->author()->link( $image->warningRef() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate_itemprop', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT


					
IPSCONTENT;

if ( $image->directContainer()->allow_comments ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $image->comments > 0 ):
$return .= <<<IPSCONTENT

							&middot; <span>
IPSCONTENT;

$pluralize = array( $image->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</figcaption>
		</figure>
	</li>

IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}