<?php
namespace IPS\Theme;
class class_forums_front_index extends \IPS\Theme\Template
{	function forumGridItem( $forum, $isSubForum=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $forum->can('view') ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$lastPosts = $forum->lastPost(2);
$return .= <<<IPSCONTENT


IPSCONTENT;

$club = $forum->club();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "gridItem:before", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="gridItem" class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsData__item 
IPSCONTENT;

if ( $forum->redirect_on ):
$return .= <<<IPSCONTENT
ipsData__item--redirect
IPSCONTENT;

elseif ( $forum->password ):
$return .= <<<IPSCONTENT
ipsData__item--password
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsData__item--forum
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-forumid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \IPS\forums\Topic::containerUnread( $forum ) && !$forum->redirect_on ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->formattedInlineStyle( $forum );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "gridItem:inside-start", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'passForm', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_requires_password', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

if ( $club ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<a 
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT
href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'passForm', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_requires_password', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsData__image" title="
IPSCONTENT;

if ( $club ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

if ( $forum->card_image ):
$return .= <<<IPSCONTENT

				<img src="
IPSCONTENT;

$return .= \IPS\File::get( "forums_Cards", $forum->card_image )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
			
IPSCONTENT;

elseif ( $club ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$coverPhoto = $club->coverPhoto( FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$cfObject = $coverPhoto->object;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

					<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
				
IPSCONTENT;

elseif ( $club->profile_photo ):
$return .= <<<IPSCONTENT

					<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Clubs", $club->profile_photo )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
				
IPSCONTENT;

elseif ( ! empty( $cfObject::$coverPhotoDefault ) ):
$return .= <<<IPSCONTENT

					<div class="ipsFallbackImage" style="--i-empty-image-random--ba-co: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->object->coverPhotoBackgroundColor(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"></div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</a>
		<div class="ipsData__content">
			<div class="ipsData__main">
				<div class="ipsData__title">
					
IPSCONTENT;

if ( !$forum->redirect_on AND \IPS\forums\Topic::containerUnread( $forum ) AND \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

						<span class="ipsBadge ipsBadge--new">New</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "title:before", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
<h3 data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "title:inside-start", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'passForm', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_requires_password', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $club ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $forum->password ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

								<i class="fa-solid fa-lock i-font-size_-2 i-color_soft"></i>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $forum->redirect_on ):
$return .= <<<IPSCONTENT

							<i class="fa-solid fa-arrow-right i-font-size_-2 i-color_soft"></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "title:inside-end", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
</h3>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "title:after", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( $forum->description ):
$return .= <<<IPSCONTENT

					<div class="ipsData__desc">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $forum->description, array('') );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $forum->hasChildren() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "children:before", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="children" class="ipsSubList">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "children:inside-start", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $forum->children() as $subforum ):
$return .= <<<IPSCONTENT

							<li class="ipsSubList__item 
IPSCONTENT;

if ( \IPS\forums\Topic::containerUnread( $subforum ) ):
$return .= <<<IPSCONTENT
ipsSubList__item--unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsSubList__item--read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subforum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subforum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "children:inside-end", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "children:after", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "stats:before", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="stats" class="ipsData__stats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "stats:inside-start", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !$forum->redirect_on ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$count = \IPS\forums\Topic::contentCount( $forum, TRUE );
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posts_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize, 'format' => 'long' ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$pluralize = array( $forum->redirect_hits ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'redirect_hits', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\forums\Topic::modPermission( 'unhide', NULL, $forum ) AND $forum->queued_topics ):
$return .= <<<IPSCONTENT

					<li class="i-color_negative i-link-color_inherit">
						<i class="fa-solid fa-triangle-exclamation"></i>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( array( 'filter' => 'queued_topics' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$pluralize = array( $forum->queued_topics ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'queued_topics_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\forums\Topic::modPermission( 'unhide', NULL, $forum ) AND $forum->queued_posts ):
$return .= <<<IPSCONTENT

					<li class="i-color_negative i-link-color_inherit">
						<i class="fa-solid fa-triangle-exclamation"></i>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( array( 'filter' => 'queued_posts' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$pluralize = array( $forum->queued_posts ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'queued_posts_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "stats:inside-end", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "stats:after", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $lastPosts and count( $lastPosts ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $lastPosts as $lastPost ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "latestPoster:before", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
<div class="ipsData__last" data-ips-hook="latestPoster">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "latestPoster:inside-start", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $lastPost['author'], 'fluid' );
$return .= <<<IPSCONTENT

						<div class="ipsData__last-text">
							<div class="ipsData__last-primary"><a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url']->setQueryString( 'do', 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
							<small class="ipsData__last-secondary">
								
IPSCONTENT;

if ( $lastPost['last_poster_anon'] ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($lastPost['author']->link( NULL, NULL, TRUE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
, 
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($lastPost['author']->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
, 
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $lastPost['topic_title'] ):
$return .= <<<IPSCONTENT

									<a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url']->setQueryString( 'do', 'getLastComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</small>
						</div>
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "latestPoster:inside-end", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "latestPoster:after", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "gridItem:inside-end", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumGridItem", "gridItem:after", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function forumRow( $forum, $isSubForum=FALSE, $table=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $forum->can('view') ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$lastPost = $forum->lastPost();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$club = $forum->club();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "rowItem:before", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="rowItem" class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsData__item 
IPSCONTENT;

if ( !$forum->redirect_on && !$forum->password ):
$return .= <<<IPSCONTENT
ipsData__item--discussions
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $forum->redirect_on ):
$return .= <<<IPSCONTENT
ipsData__item--redirect
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $forum->password ):
$return .= <<<IPSCONTENT
ipsData__item--password 
IPSCONTENT;

if ( $forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT
ipsData__item--password-known
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-forumid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->formattedInlineStyle( $forum );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \IPS\forums\Topic::containerUnread( $forum ) && !$forum->redirect_on ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "rowItem:inside-start", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'passForm', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_requires_password', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

if ( $club ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsData__image">
			
IPSCONTENT;

if ( $club ):
$return .= <<<IPSCONTENT

				<img src="
IPSCONTENT;

if ( $club->profile_photo ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\File::get( "core_Clubs", $club->profile_photo )->url;
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "default_club.png", "core", 'global', false );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
			
IPSCONTENT;

elseif ( $forum->icon ):
$return .= <<<IPSCONTENT

				{$forum->getIcon()}
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<span class="ipsIcon ipsIcon--fa" aria-hidden="true"><i class="fa-ips"></i></span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>		
		<div class="ipsData__content">
			<div class="ipsData__main">
				<div class="ipsData__title">
					
IPSCONTENT;

if ( \IPS\forums\Topic::containerUnread( $forum ) && !$forum->redirect_on ):
$return .= <<<IPSCONTENT

						<span class="ipsIndicator" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unread', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip></span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "title:before", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
<h3 data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "title:inside-start", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'passForm', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_requires_password', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $club ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "title:inside-end", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
</h3>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "title:after", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( \IPS\forums\Topic::modPermission( 'unhide', NULL, $forum ) AND $unapprovedContent = $forum->unapprovedContentRecursive() and ( $unapprovedContent['topics'] OR $unapprovedContent['posts'] ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "pendingIcons:before", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="pendingIcons" class="i-color_warning i-font-size_-1">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "pendingIcons:inside-start", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

						<strong>
							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
: 
							
IPSCONTENT;

if ( $unapprovedContent['topics'] ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( array( 'filter' => 'queued_topics' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-color_inherit">
IPSCONTENT;

$pluralize = array( $unapprovedContent['topics'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'queued_topics_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $unapprovedContent['topics'] && $unapprovedContent['posts'] ):
$return .= <<<IPSCONTENT
 &amp; 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $unapprovedContent['posts'] ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( array( 'filter' => 'queued_posts' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-color_inherit">
IPSCONTENT;

$pluralize = array( $unapprovedContent['posts'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'queued_posts_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</strong>
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "pendingIcons:inside-end", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "pendingIcons:after", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $forum->description ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $forum->description, array('ipsData__desc') );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $forum->hasChildren() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "children:before", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="children" class="ipsSubList">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "children:inside-start", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $forum->children() as $subforum ):
$return .= <<<IPSCONTENT

							<li class="ipsSubList__item 
IPSCONTENT;

if ( \IPS\forums\Topic::containerUnread( $subforum ) ):
$return .= <<<IPSCONTENT
ipsSubList__item--unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsSubList__item--read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subforum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subforum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "children:inside-end", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "children:after", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( $forum->redirect_on ):
$return .= <<<IPSCONTENT

				<div class="ipsData__last">
					<div class="ipsData__last-text">
						<span class="ipsData__last-secondary">
IPSCONTENT;

$pluralize = array( $forum->redirect_hits ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'redirect_hits', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
					</div>
				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $lastPost AND ( $forum->can_view_others OR \IPS\Member::loggedIn()->modPermission('can_read_all_topics') OR ( \is_array( \IPS\Member::loggedIn()->modPermission('forums') ) AND \in_array( $forum->_id, \IPS\Member::loggedIn()->modPermission('forums') ) )) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "stats:before", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="stats" class="ipsData__stats ipsData__stats--large">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "stats:inside-start", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$count = \IPS\forums\Topic::contentCount( $forum, TRUE );
$return .= <<<IPSCONTENT

							<li>
								<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $count );
$return .= <<<IPSCONTENT
</span>
								<span>
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posts_no_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize, 'format' => 'short' ) );
$return .= <<<IPSCONTENT
</span>
							</li>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "stats:inside-end", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "stats:after", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $lastPost ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "latestPoster:before", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="latestPoster" class="ipsData__last">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "latestPoster:inside-start", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $lastPost['author'], 'fluid' );
$return .= <<<IPSCONTENT

						<div class="ipsData__last-text">
							
IPSCONTENT;

if ( $lastPost['topic_title'] ):
$return .= <<<IPSCONTENT

								<div class="ipsData__last-primary">
									<a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url']->setQueryString( 'do', 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
								</div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<div class="ipsData__last-secondary">
								
IPSCONTENT;

if ( $lastPost['last_poster_anon'] ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($lastPost['author']->link( NULL, NULL, TRUE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
, 
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($lastPost['author']->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
, 
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $lastPost['topic_title'] ):
$return .= <<<IPSCONTENT

									<a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url']->setQueryString( 'do', 'getLastComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</div>
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "latestPoster:inside-end", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "latestPoster:after", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "rowItem:inside-end", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumRow", "rowItem:after", [ $forum,$isSubForum,$table ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function forumSummaryRow( $forum, $isSubForum=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $forum->can('view') ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$lastPosts = $forum->lastPost(5);
$return .= <<<IPSCONTENT


IPSCONTENT;

$club = $forum->club();
$return .= <<<IPSCONTENT

	<section class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsCategoryWithFeed__item" data-forumid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-type="
IPSCONTENT;

if ( $forum->redirect_on ):
$return .= <<<IPSCONTENT
redirect
IPSCONTENT;

elseif ( $forum->password ):
$return .= <<<IPSCONTENT
password
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
forum
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $forum->feature_color ):
$return .= <<<IPSCONTENT
style="--i-featured:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->feature_color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<i-data class="ipsCategoryWithFeed__meta">
			<div class="ipsData ipsData--grid ipsData--forumFeedViewParent">
				<div class="ipsData__item" 
IPSCONTENT;

if ( \IPS\forums\Topic::containerUnread( $forum ) && !$forum->redirect_on ):
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
					<a 
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT
href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'passForm', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_requires_password', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

elseif ( $club ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
					<a 
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT
href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'passForm', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_requires_password', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsData__image">
						
IPSCONTENT;

if ( $club and ! $forum->card_image ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$coverPhoto = $club->coverPhoto( FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$cfObject = $coverPhoto->object;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

								<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							
IPSCONTENT;

elseif ( $club->profile_photo ):
$return .= <<<IPSCONTENT

								<img src="
IPSCONTENT;

$return .= \IPS\File::get( "core_Clubs", $club->profile_photo )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							
IPSCONTENT;

elseif ( ! empty( $cfObject::$coverPhotoDefault ) ):
$return .= <<<IPSCONTENT

								<div class="ipsFallbackImage" style="--i-empty-image-random--ba-co: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->object->coverPhotoBackgroundColor(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"></div>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( $forum->card_image ):
$return .= <<<IPSCONTENT

							<img src="
IPSCONTENT;

$return .= \IPS\File::get( "forums_Cards", $forum->card_image )->url;
$return .= <<<IPSCONTENT
" alt="" loading="lazy">	
						
IPSCONTENT;

elseif ( $forum->icon ):
$return .= <<<IPSCONTENT

							{$forum->getIcon()}
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<i></i>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<span class="ipsInvisible">
							
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

elseif ( $club ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$sprintf = array($club->name, $forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</span>
					</a>
					<div class="ipsData__content">
						<div class="ipsData__main">
							<header class="ipsData__title">
								
IPSCONTENT;

if ( !$forum->redirect_on AND \IPS\forums\Topic::containerUnread( $forum ) AND \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

									<span class="ipsBadge ipsBadge--new">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<h3>
									
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'passForm', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_requires_password', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $club ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</h3>
								
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

									<i class="fa-solid fa-lock i-font-size_-2 i-color_soft"></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $forum->redirect_on ):
$return .= <<<IPSCONTENT

									<i class="fa-solid fa-arrow-right i-font-size_-2 i-color_soft"></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</header>
							
IPSCONTENT;

if ( $forum->description ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $forum->description, array('ipsData__desc ipsData__desc--all') );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $forum->hasChildren() ):
$return .= <<<IPSCONTENT

								<ul class="ipsSubList">
									
IPSCONTENT;

foreach ( $forum->children() as $subforum ):
$return .= <<<IPSCONTENT

										<li class="ipsSubList__item 
IPSCONTENT;

if ( \IPS\forums\Topic::containerUnread( $subforum ) ):
$return .= <<<IPSCONTENT
ipsSubList__item--unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsSubList__item--read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
											<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subforum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subforum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
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
						<div class="ipsData__extra">
							<ul class="ipsData__stats">
								
IPSCONTENT;

if ( !$forum->redirect_on ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$count = \IPS\forums\Topic::contentCount( $forum, TRUE );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $count > 0 ):
$return .= <<<IPSCONTENT
<li>
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posts_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize, 'format' => 'long' ) );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<li>
IPSCONTENT;

$pluralize = array( $forum->redirect_hits ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'redirect_hits', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\forums\Topic::modPermission( 'unhide', NULL, $forum ) AND $forum->queued_topics ):
$return .= <<<IPSCONTENT

									<li class="i-color_negative i-link-color_inherit">
										<i class="fa-solid fa-triangle-exclamation"></i>
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( array( 'filter' => 'queued_topics' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$pluralize = array( $forum->queued_topics ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'queued_topics_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\forums\Topic::modPermission( 'unhide', NULL, $forum ) AND $forum->queued_posts ):
$return .= <<<IPSCONTENT

									<li class="i-color_negative i-link-color_inherit">
										<i class="fa-solid fa-triangle-exclamation"></i>
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( array( 'filter' => 'queued_posts' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$pluralize = array( $forum->queued_posts ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'queued_posts_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</div>
					</div>
				</div>
			</div>
		</i-data>
		
IPSCONTENT;

if ( !$forum->redirect_on ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $lastPosts and count( $lastPosts ) ):
$return .= <<<IPSCONTENT

				<i-data>
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumSummaryRow", "latestPoster:before", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="latestPoster" class="ipsData ipsData--table ipsData--forumFeedView">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumSummaryRow", "latestPoster:inside-start", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $lastPosts as $lastPost ):
$return .= <<<IPSCONTENT

							<li class="ipsData__item">
								<a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url']->setQueryString( 'do', 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
								<div class="ipsData__icon">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $lastPost['author'], 'fluid' );
$return .= <<<IPSCONTENT
</div>
								<div class="ipsData__content">
									<div class="ipsData__main">
										<h4 class="ipsData__title">
											<a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url']->setQueryString( 'do', 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
										</h4>
										<small class="ipsData__meta">
											
										</small>
									</div>
									<div class="ipsData__extra">
										<div class="ipsData__last">
											<div class="ipsData__last-text">
												<div class="ipsData__last-primary">
													
IPSCONTENT;

if ( $lastPost['last_poster_anon'] ):
$return .= <<<IPSCONTENT

														
IPSCONTENT;

$htmlsprintf = array($lastPost['author']->link( NULL, NULL, TRUE )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 
													
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

														
IPSCONTENT;

$htmlsprintf = array($lastPost['author']->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 
													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												</div>
												<div class="ipsData__last-secondary">
													
IPSCONTENT;

if ( $lastPost['topic_title'] ):
$return .= <<<IPSCONTENT

														<a href="
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url']->setQueryString( 'do', 'getLastComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['topic_url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
													
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

														
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT

													
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												</div>
											</div>
										</div>
										<ul class="ipsData__stats">
											<li data-stattype="comments" data-v="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost['posts'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
												<span class="ipsData__stats-label">
IPSCONTENT;

$pluralize = array( $lastPost['posts'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
											</li>
										</ul>
									</div>
								</div>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumSummaryRow", "latestPoster:inside-end", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/forumSummaryRow", "latestPoster:after", [ $forum,$isSubForum ] );
$return .= <<<IPSCONTENT

				</i-data>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<div>
					<p class="ipsEmptyMessage">
						
IPSCONTENT;

if ( $forum->password && !$forum->loggedInMemberHasPasswordAccess() ):
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'passForm', '1' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_requires_password', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_forum_posts_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>								
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_forum_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function forumTableRow( $table, $headers, $forums ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $forums as $forum ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumRow( $forum, FALSE, $table );
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function index(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<header class="ipsPageHeader ipsPageHeader--forum-table">
	<div class="ipsPageHeader__row">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "header:before", [  ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "header:inside-start", [  ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "title:before", [  ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "title:inside-start", [  ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forums', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "title:inside-end", [  ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "title:after", [  ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "header:inside-end", [  ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "header:after", [  ] );
$return .= <<<IPSCONTENT

		<ul class="ipsButtons ipsButtons--main">
			
IPSCONTENT;

if ( \IPS\forums\Forum::canOnAny( 'add' )  ):
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=forums&module=forums&controller=forums&do=add", null, "topic_non_forum_add_button", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--primary" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

		</ul>
	</div>
</header>
<section>
	<ol class="ipsBlockSpacer" data-controller="core.global.core.table, forums.front.forum.forumList" data-baseurl="">
		
IPSCONTENT;

foreach ( \IPS\forums\Forum::roots() as $category ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $category->can('view') && $category->hasChildren() ):
$return .= <<<IPSCONTENT

			<li data-categoryid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsBox ipsPull">
				<h2 class="ipsBox__header">
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					<button class="ipsBox__header-toggle" type="button" aria-expanded="true" aria-controls="forum-category_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipscontrols data-action="toggleCategory" data-ipstooltip aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'toggle_this_category', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i></i></button>
				</h2>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue('forums_forum') === 'grid' ):
$return .= <<<IPSCONTENT

					<i-data>
						<ol class="ipsData ipsData--grid ipsData--category ipsData--forum-grid ipsBox__content" id="forum-category_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ips-hidden-animation="slide" data-ips-hidden-event="ips:toggleForumCategory">
							
IPSCONTENT;

foreach ( $category->children() as $forum ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumGridItem( $forum );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ol>
					</i-data>
				
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->getLayoutValue('forums_forum') === 'modern' ):
$return .= <<<IPSCONTENT

					<div class="ipsCategoryWithFeed ipsBox__content" id="forum-category_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ips-hidden-animation="slide" data-ips-hidden-event="ips:toggleForumCategory">
						
IPSCONTENT;

foreach ( $category->children() as $forum ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumSummaryRow( $forum );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<i-data>
						<ol class="ipsData ipsData--table ipsData--category ipsData--forum-category ipsBox__content" id="forum-category_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ips-hidden-animation="slide" data-ips-hidden-event="ips:toggleForumCategory">
							
IPSCONTENT;

foreach ( $category->children() as $forum ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumRow( $forum );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ol>
					</i-data>
				
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

		
IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $clubForums = \IPS\forums\Forum::clubNodes() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "clubs:before", [  ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="clubs" data-categoryid="clubs" class="ipsBox ipsPull">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "clubs:inside-start", [  ] );
$return .= <<<IPSCONTENT

				<h2 class="ipsBox__header">
					<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=forums&module=forums&controller=forums&do=clubs", null, "forums_clubs", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node_forums', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<button class="ipsBox__header-toggle" type="button" aria-expanded="true" aria-controls="forum-category_clubs" data-ipscontrols data-action="toggleCategory" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'toggle_this_category', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'toggle_this_category', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
				</h2>
				<div class="ipsBox__content">
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue('forums_forum') === 'grid' ):
$return .= <<<IPSCONTENT

						<i-data>
							<ol class="ipsData ipsData--grid ipsData--forum-grid ipsBox__content" id="forum-category_clubs" data-ips-hidden-animation="slide" data-ips-hidden-event="ips:toggleForumCategory">
								
IPSCONTENT;

foreach ( $clubForums as $forum ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumGridItem( $forum );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</ol>
						</i-data>
					
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->getLayoutValue('forums_forum') === 'modern' ):
$return .= <<<IPSCONTENT

						<div class="ipsCategoryWithFeed ipsBox__content" id="forum-category_clubs" data-ips-hidden-animation="slide" data-ips-hidden-event="ips:toggleForumCategory">
							
IPSCONTENT;

foreach ( $clubForums as $forum ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumSummaryRow( $forum );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i-data>
							<ol class="ipsData ipsData--table ipsData--category ipsData--forum-category ipsBox__content" id="forum-category_clubs" data-ips-hidden-animation="slide" data-ips-hidden-event="ips:toggleForumCategory">
								
IPSCONTENT;

foreach ( $clubForums as $forum ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumRow( $forum );
$return .= <<<IPSCONTENT

								
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "clubs:inside-end", [  ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/index", "clubs:after", [  ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ol>
</section>
IPSCONTENT;

		return $return;
}

	function indexButtons( $showViewButtons=TRUE, $showFilterButton=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $showFilterButton ):
$return .= <<<IPSCONTENT

<div class='ipsPageHeader__row ipsResponsive_hideDesktop'>
	<ul class='ipsButtons ipsButtons--fill'>
		<li>
			<a class="ipsButton ipsButton--inherit" href="#" rel="nofollow" data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forums_simple_dialog_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-content='#elFluidFormFilters'>
				<span data-role='fluidForumMobileDesc'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forums_simple_filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-angle-down'></i>
			</a>
		</li>
	</ul>
</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function simplifiedForumTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "fluidTable:before", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="fluidTable" class="ipsBox ipsPull cForumFluidTable" data-baseurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-resort="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-tableid="topics" data-dummyloading data-controller="core.global.core.table
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT
,core.front.core.moderation
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "fluidTable:inside-start", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $table->title ):
$return .= <<<IPSCONTENT

		<h2 hidden>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $table->count > 0 ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "buttonBar:before", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="buttonBar" class="ipsButtonBar ipsButtonBar--top">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "buttonBar:inside-start", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

			<div data-role="tablePagination" class="ipsButtonBar__pagination" 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsButtonBar__end">
				<ul class="ipsDataFilters">
					
IPSCONTENT;

if ( isset( $table->sortOptions ) and !empty( $table->sortOptions )  ):
$return .= <<<IPSCONTENT

						<li>
							<a href="#elSortByMenu_menu" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="sortButton" data-ipsmenu data-ipsmenu-activeclass="ipsDataFilters__button--active" data-ipsmenu-selectable="radio" class="ipsDataFilters__button"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
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

if ( $col === $table->getSortByColumn() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$custom = FALSE;
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ipsmenuvalue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $col, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-sortdirection="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->getSortDirection( $col ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $col, 'sortdirection' => $table->getSortDirection( $col ) ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
" data-noselect="true">
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow">
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
							<a href="#elFilterByMenu_menu" id="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="tableFilterMenu" data-ipsmenu data-ipsmenu-activeclass="ipsDataFilters__button--active" data-ipsmenu-selectable="radio" class="ipsDataFilters__button"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
							<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" data-role="tableFilterMenu" id="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
								<li data-action="tableFilter" data-ipsmenuvalue="" class="ipsMenu_item 
IPSCONTENT;

if ( !$table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => '', 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
IPSCONTENT;

$val = "{$table->langPrefix}all"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
								
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

									<li data-action="tableFilter" data-ipsmenuvalue="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsMenu_item 
IPSCONTENT;

if ( $k === $table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow">
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
" title="
IPSCONTENT;

$val = "{$table->langPrefix}select_rows_tooltip"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ipsautocheck data-ipsautocheck-context="#elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsmenu data-ipsmenu-activeclass="ipsDataFilters__button--active">
								<span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span><i class="fa-solid fa-caret-down"></i>
								<span class="ipsNotification" data-role="autoCheckCount">0</span>
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
								
IPSCONTENT;

if ( \count($table->getFilters()) ):
$return .= <<<IPSCONTENT

									<li class="ipsMenu_sep"><hr></li>
									
IPSCONTENT;

foreach ( $table->getFilters() as $filter ):
$return .= <<<IPSCONTENT

										<li class="ipsMenu_item" data-ipsmenuvalue="
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
			</div>
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "buttonBar:inside-end", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "buttonBar:after", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

	
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
" method="post" data-role="moderationTools" data-ipspageaction>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

			<i-data>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "rows:before", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
<ol class="ipsData 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue('forums_topic') == 'snippet' ):
$return .= <<<IPSCONTENT
ipsData--snippet-topic-list
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsData--table ipsData--entries ipsData--table-topic-list
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
" data-ips-hook="rows" id="elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="tableRows">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "rows:inside-start", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "rows:inside-end", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
</ol>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "rows:after", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

			</i-data>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class="i-text-align_center ipsBox__padding">
				<p class="i-font-size_3 i-font-weight_500">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_topics_in_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

			<div class="ipsJS_hide ipsData__modBar" data-role="pageActionOptions">
				<select name="modaction" data-role="moderationAction" class="ipsInput ipsInput--select i-basis_300">
					
IPSCONTENT;

if ( $table->canModerate('unhide') ):
$return .= <<<IPSCONTENT

						<option value="approve" data-icon="check-circle">
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
" data-icon="star" data-action="feature">
							
IPSCONTENT;

if ( $table->canModerate('feature') ):
$return .= <<<IPSCONTENT

								<option value="feature">
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

								<option value="unfeature">
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
" data-icon="thumb-tack" data-action="pin">
							
IPSCONTENT;

if ( $table->canModerate('pin') ):
$return .= <<<IPSCONTENT

								<option value="pin">
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

								<option value="unpin">
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
" data-icon="eye" data-action="hide">
							
IPSCONTENT;

if ( $table->canModerate('hide') ):
$return .= <<<IPSCONTENT

								<option value="hide">
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

								<option value="unhide">
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
" data-icon="lock" data-action="lock">
							
IPSCONTENT;

if ( $table->canModerate('lock') ):
$return .= <<<IPSCONTENT

								<option value="lock">
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

								<option value="unlock">
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

						<option value="move" data-icon="arrow-right">
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

						<option value="merge" data-icon="level-up">
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

						<option value="delete" data-icon="trash">
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

					    <optgroup label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-icon="tag" data-action="tag">
					        <option value="tag">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_single_tag', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
					        <option value="untag">
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
" data-icon="tasks" data-action="saved_actions">
							
IPSCONTENT;

foreach ( $table->savedActions as $k => $v ):
$return .= <<<IPSCONTENT

								<option value="savedAction-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
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

	<div class="ipsButtonBar ipsButtonBar--bottom" 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<div class="ipsButtonBar__pagination" data-role="tablePagination">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "fluidTable:inside-end", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedForumTable", "fluidTable:after", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function simplifiedTopicRow( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$rowIds = array();
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$rowIds[] = $row->$idField;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$rowCount=1; $adShown=0;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $advertisement = \IPS\core\Advertisement::loadByLocation( 'ad_forum_listing', $rowCount, $adShown ) ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item ipsData__item--advertisement">
				{$advertisement}
				
IPSCONTENT;

$adShown++;
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$rowCount++;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $row->mapped('moved_to') ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $movedTo = $row->movedTo() AND $movedTo->container()->can('view') ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item" data-controller="cloud.front.realtime.forumsTopicRow">
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $movedTo->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
					<div class="ipsData__icon ipsData__icon--indicator">
						<i class="fa-solid fa-arrow-left"></i>
					</div>
					<div class="ipsData__content">
						<div class="ipsData__main">
							<div class="ipsData__title"><h4><em><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $movedTo->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_new_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></em></h4></div>
							<div class="ipsData__meta">
								
IPSCONTENT;

if ( isset( $row::$databaseColumnMap['status'] ) ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$statusField = $row::$databaseColumnMap['status'];
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $row->$statusField == 'merged' ):
$return .= <<<IPSCONTENT

										<p>
IPSCONTENT;

$sprintf = array($movedTo->url( 'getPrefComment' ), $movedTo->mapped('title')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_merged_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<p>
IPSCONTENT;

$sprintf = array($movedTo->container()->url(), $movedTo->container()->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_moved_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
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

$sprintf = array($movedTo->container()->url(), $movedTo->container()->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_moved_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</div>
					</div>
					
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

						<div class="ipsData__mod">
							<input type="checkbox" data-role="moderation" name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

if ( $row->mapped('featured') ):
$return .= <<<IPSCONTENT
unfeature
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->mapped('pinned') ):
$return .= <<<IPSCONTENT
unpin
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 delete" data-state="
IPSCONTENT;

if ( $row->mapped('pinned') ):
$return .= <<<IPSCONTENT
pinned
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->mapped('featured') ):
$return .= <<<IPSCONTENT
featured
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "row:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="row" class="ipsData__item 
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

if ( $row->hidden() or $row->isFutureDate() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $row->isFutureDate() ):
$return .= <<<IPSCONTENT
 ipsData__item--future
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->formattedInlineStyle( $row->container() );
$return .= <<<IPSCONTENT
 data-rowid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset($row->locationHash) ):
$return .= <<<IPSCONTENT
data-location="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->locationHash, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
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
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "row:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

if ( $row->mapped('title') or $row->mapped('title') == 0 ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $row->unread() ):
$return .= <<<IPSCONTENT

						<!-- <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getNewComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsIndicator 
IPSCONTENT;

if ( \in_array( $row->$idField, $table->contentPostedIn ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ips-hook="unreadIcon"></a> -->
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<!-- <span class='ipsIndicator ipsIndicator--read 
IPSCONTENT;

if ( \in_array( $row->$idField, $table->contentPostedIn ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' aria-hidden='true' data-ips-hook="unreadIcon"></span> -->
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id and $row->unread() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "unreadIcon:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getNewComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsData__icon" aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ips-hook="unreadIcon">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "unreadIcon:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

						<span class="ipsUserPhoto">
							<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->author()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
						</span>
						
IPSCONTENT;

if ( $row->author() != \IPS\Member::loggedIn() and  \in_array( $row->$idField, $table->contentPostedIn ) ):
$return .= <<<IPSCONTENT

							<span class="ipsUserPhoto">
								<img src="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "unreadIcon:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "unreadIcon:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<div class="ipsData__icon">
						<span class="ipsUserPhoto">
							<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->author()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
						</span>
						
IPSCONTENT;

if ( $row->author() != \IPS\Member::loggedIn() and  \in_array( $row->$idField, $table->contentPostedIn ) ):
$return .= <<<IPSCONTENT

							<span class="ipsUserPhoto">
								<img src="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Member::loggedIn()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class="ipsData__content">
					<div class="ipsData__main">
						<div class="ipsData__title">
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id and $row->unread() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "unreadIcon:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getNewComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsIndicator" aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ips-hook="unreadIcon">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "unreadIcon:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "unreadIcon:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "title:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<h4 data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "title:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

if ( $row->mapped('title') ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->canEdit() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $row->tableHoverUrl and $row->canView() ):
$return .= <<<IPSCONTENT
 data-ipshover data-ipshover-target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url()->setQueryString('preview', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipshover-timeout="1.5" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $row->canEdit() ):
$return .= <<<IPSCONTENT
 data-role="editableTitle" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<span>
										
IPSCONTENT;

if ( $row->mapped('title') or $row->mapped('title') == 0 ):
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

									</span>
								</a>
							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "title:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</h4>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "title:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $row->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

								{$row->commentPagination( array(), 'miniPagination' )}
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "badges:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="badges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "badges:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							    
IPSCONTENT;

foreach ( $row->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "badges:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "badges:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

						</div>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "description:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="description" class="ipsData__meta">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "description:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "badges:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="badges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "badges:inside-start", [ $table,$headers,$rows ] );
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

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "badges:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "badges:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$htmlsprintf = array($row->author()->link( NULL, NULL, $row->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_itemprop', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$val = ( $row->mapped('date') instanceof \IPS\DateTime ) ? $row->mapped('date') : \IPS\DateTime::ts( $row->mapped('date') );$return .= $val->html(FALSE);
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsData__container-title">
IPSCONTENT;

if ( $club = $row->container()->club() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($club->name, $row->container()->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
{$row->container()->_title}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "description:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "description:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \count( $row->tags() ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $row->tags(), true );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					
IPSCONTENT;

if ( $row->groupsPosted ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->groupPostedBadges( $row->groupsPosted, 'topic_posted_in_groups' );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<div class="ipsData__extra">
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "stats:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="stats" class="ipsData__stats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "stats:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $row->stats(FALSE) as $k => $v ):
$return .= <<<IPSCONTENT

								<li 
IPSCONTENT;

if ( $k == 'num_views' ):
$return .= <<<IPSCONTENT
class="ipsData__stats-soft i-color_soft" 
IPSCONTENT;

elseif ( \in_array( $k, $row->hotStats ) ):
$return .= <<<IPSCONTENT
class="ipsData__stats-hot" data-text="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									<span class="ipsData__stats-icon" data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $v );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize, 'format' => 'short' ) );
$return .= <<<IPSCONTENT
"></span>
									<span class="ipsData__stats-label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize, 'format' => 'short' ) );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

if ( ( $k == 'forums_comments' OR $k == 'answers_no_number' ) && \IPS\forums\Topic::modPermission( 'unhide', NULL, $row->container() ) AND $unapprovedComments = $row->mapped('unapproved_comments') ):
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url()->setQueryString( 'queued_posts', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-color_warning i-font-size_-2" data-ipstooltip title="
IPSCONTENT;

$pluralize = array( $row->topic_queuedposts ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'queued_posts_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-triangle-exclamation"></i> <strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unapprovedComments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "stats:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "stats:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "latestPoster:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="latestPoster" class="ipsData__last">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "latestPoster:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->lastCommenter(), 'fluid' );
$return .= <<<IPSCONTENT

							<div class="ipsData__last-text">
								<div class="ipsData__last-primary">
									
IPSCONTENT;

if ( $row->mapped('num_comments') ):
$return .= <<<IPSCONTENT

										{$row->lastCommenter()->link()}
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										{$row->author()->link()}
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
								<div class="ipsData__last-secondary">
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( 'getLastComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
										
IPSCONTENT;

if ( $row->mapped('last_comment') ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $row->mapped('last_comment') instanceof \IPS\DateTime ) ? $row->mapped('last_comment') : \IPS\DateTime::ts( $row->mapped('last_comment') );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $row->mapped('date') instanceof \IPS\DateTime ) ? $row->mapped('date') : \IPS\DateTime::ts( $row->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</a>
								</div>
							</div>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "latestPoster:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "latestPoster:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

					</div>
				</div>
				
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

					<div class="ipsData__mod">
						<input type="checkbox" class="ipsInput ipsInput--toggle" data-role="moderation" name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state="
IPSCONTENT;

if ( $row->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "row:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRow", "row:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $advertisement = \IPS\core\Advertisement::loadByLocation( 'ad_fluid_index_view', $rowCount, $adShown ) ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item ipsData__item--advertisement">{$advertisement}</li>
			
IPSCONTENT;

$adShown++;
$return .= <<<IPSCONTENT

		
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

	function simplifiedTopicRowSnippet( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$rowIds = array();
$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$rowIds[] = $row->$idField;
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$rowCount=1; $adShown=0;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $advertisement = \IPS\core\Advertisement::loadByLocation( 'ad_forum_listing', $rowCount, $adShown ) ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item ipsData__item--advertisement">
				{$advertisement}
				
IPSCONTENT;

$adShown++;
$return .= <<<IPSCONTENT

			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$rowCount++;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$idField = $row::$databaseColumnId;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $row->mapped('moved_to') ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $movedTo = $row->movedTo() AND $movedTo->container()->can('view') ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "movedRow:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="movedRow" class="ipsData__item ipsData__item--snippet">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "movedRow:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $movedTo->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
					<div class="ipsData__icon ipsData__icon--indicator">
						<i class="fa-solid fa-arrow-left"></i>
					</div>
					<div class="ipsData__content">
						<div class="ipsData__main">
							<div class="ipsData__title">
								
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "movedTitle:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<h4 data-ips-hook="movedTitle">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "movedTitle:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $movedTo->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_new_location', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "movedTitle:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</h4>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "movedTitle:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							</div>
							<p class="ipsData__meta">
								
IPSCONTENT;

if ( isset( $row::$databaseColumnMap['status'] ) ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$statusField = $row::$databaseColumnMap['status'];
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $row->$statusField == 'merged' ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$sprintf = array($movedTo->url( 'getPrefComment' ), $movedTo->mapped('title')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_merged_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$sprintf = array($movedTo->container()->url(), $movedTo->container()->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_moved_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$sprintf = array($movedTo->container()->url(), $movedTo->container()->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_moved_to', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</p>
						</div>
					</div>
					
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

						<div class="ipsData__mod">
							<input type="checkbox" data-role="moderation" name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

if ( $row->mapped('featured') ):
$return .= <<<IPSCONTENT
unfeature
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->mapped('pinned') ):
$return .= <<<IPSCONTENT
unpin
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 delete" data-state="
IPSCONTENT;

if ( $row->mapped('pinned') ):
$return .= <<<IPSCONTENT
pinned
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->mapped('featured') ):
$return .= <<<IPSCONTENT
featured
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "movedRow:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "movedRow:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "row:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="row" data-controller="cloud.front.realtime.forumsTopicRow" class="ipsData__item ipsData__item--snippet 
IPSCONTENT;

if ( $row->groupsPosted ):
$return .= <<<IPSCONTENT
ipsData__item--highlighted
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

if ( $row->hidden() or $row->isFutureDate() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $row->isFutureDate() ):
$return .= <<<IPSCONTENT
 ipsModerated--alternate
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-rowid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset($row->locationHash) ):
$return .= <<<IPSCONTENT
data-location="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->locationHash, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
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
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "row:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
				<div class="ipsData__icon">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->author(), 'fluid' );
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsData__content">
					<div class="ipsData__main">
						<div class="ipsColumns">
							<div class="ipsColumns__primary">
								<div class="ipsData__title">
									
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "badges:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="badges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "badges:inside-start", [ $table,$headers,$rows ] );
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

									
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "badges:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "badges:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $row->unread() ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "unreadIcon:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsIndicator 
IPSCONTENT;

if ( \in_array( $row->$idField, $table->contentPostedIn ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ips-hook="unreadIcon">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "unreadIcon:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "unreadIcon:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<span class="ipsIndicator ipsIndicator--read 
IPSCONTENT;

if ( \in_array( $row->$idField, $table->contentPostedIn ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" aria-hidden="true"></span>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "title:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<h4 data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "title:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

if ( $row->mapped('title') ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'content_deleted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->canEdit() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $row->tableHoverUrl and $row->canView() ):
$return .= <<<IPSCONTENT
 data-ipshover data-ipshover-target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url()->setQueryString('preview', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipshover-timeout="1.5" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $row->canEdit() ):
$return .= <<<IPSCONTENT
 data-role="editableTitle" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
											<span>
												
IPSCONTENT;

if ( $row->mapped('title') or $row->mapped('title') == 0 ):
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

											</span>
										</a>
									
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "title:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</h4>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "title:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $row->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

										{$row->commentPagination( array(), 'miniPagination' )}
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
								<div class="ipsData__meta i-font-weight_500"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">{$row->container()->_formattedTitle}</a> · 
IPSCONTENT;

$htmlsprintf = array($row->author()->link( NULL, NULL, $row->isAnonymous() ), \IPS\DateTime::ts( $row->mapped('date') )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_started_username_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
							</div>
							<div class="ipsColumns__secondary">
								
IPSCONTENT;

if ( \count( $row->tags() ) ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $row->tags(), true );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</div>
						
IPSCONTENT;

if ( isset($row->firstComment) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "snippet:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<p data-ips-hook="snippet" class="ipsData__desc">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "snippet:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->firstComment->snippet(680), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "snippet:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "snippet:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class="ipsData__extra">
							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "stats:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="stats" class="ipsData__stats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "stats:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

								<li>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reactionOverview( $row, NULL, NULL );
$return .= <<<IPSCONTENT

								</li>
								
IPSCONTENT;

foreach ( $row->stats(FALSE) as $k => $v ):
$return .= <<<IPSCONTENT

									<li 
IPSCONTENT;

if ( $k == 'num_views' ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

elseif ( \in_array( $k, $row->hotStats ) ):
$return .= <<<IPSCONTENT
class="ipsData__stats-hot" data-text="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-stattype="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $v === 0 ):
$return .= <<<IPSCONTENT
data-v="0" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
										<span class="ipsData__stats-icon" data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $v );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize, 'format' => 'short' ) );
$return .= <<<IPSCONTENT
"></span>
										<span class="ipsData__stats-label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize, 'format' => 'short' ) );
$return .= <<<IPSCONTENT
</span>
										
IPSCONTENT;

if ( ( $k == 'forums_comments' OR $k == 'answers_no_number' ) && \IPS\forums\Topic::modPermission( 'unhide', NULL, $row->container() ) AND $unapprovedComments = $row->mapped('unapproved_comments') ):
$return .= <<<IPSCONTENT

											<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url()->setQueryString( 'queued_posts', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-color_warning i-font-size_-2" data-ipstooltip title="
IPSCONTENT;

$pluralize = array( $row->topic_queuedposts ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'queued_posts_badge', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-triangle-exclamation"></i> <strong>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $unapprovedComments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></a>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $row->followerCount ):
$return .= <<<IPSCONTENT

									<li>
										<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=notifications&do=followers&follow_app=forums&follow_area=topic&follow_id={$row->tid}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'followers_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'who_follows_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$pluralize = array( $row->followerCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_follower_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "stats:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "stats:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "latestPoster:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="latestPoster" class="ipsData__last">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "latestPoster:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $row->mapped('num_comments') ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->lastCommenter(), 'fluid' );
$return .= <<<IPSCONTENT

									<div class="ipsData__last-text">
										<div class="ipsData__last-primary">
											
IPSCONTENT;

$htmlsprintf = array($row->lastCommenter()->link(), $row->url( 'getLastComment' ), \IPS\DateTime::ts( $row->mapped('last_comment') )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topic_snippet_last_reply', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

										</div>
									</div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "latestPoster:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "latestPoster:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
				
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

					<div class="ipsData__mod">
						<input type="checkbox" data-role="moderation" name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $row ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state="
IPSCONTENT;

if ( $row->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle">
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "row:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedTopicRowSnippet", "row:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $advertisement = \IPS\core\Advertisement::loadByLocation( 'ad_fluid_index_view', $rowCount, $adShown ) ):
$return .= <<<IPSCONTENT

			<li class="ipsData__item ipsData__item--advertisement">{$advertisement}</li>
			
IPSCONTENT;

$adShown++;
$return .= <<<IPSCONTENT

		
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

	function simplifiedView( $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class="ipsPageHeader ipsPageHeader--forum-fluid">
	<div class="ipsPageHeader__row">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedView", "header:before", [ $table ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedView", "header:inside-start", [ $table ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedView", "title:before", [ $table ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedView", "title:inside-start", [ $table ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'topics', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedView", "title:inside-end", [ $table ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedView", "title:after", [ $table ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedView", "header:inside-end", [ $table ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/index/simplifiedView", "header:after", [ $table ] );
$return .= <<<IPSCONTENT

		<ul class="ipsButtons ipsButtons--main">
			
IPSCONTENT;

if ( \IPS\forums\Forum::canOnAny( 'add' ) ):
$return .= <<<IPSCONTENT

				<li>
					<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=forums&module=forums&controller=forums&do=add", null, "topic_non_forum_add_button", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="nofollow" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

		</ul>
	</div>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->indexButtons( FALSE, TRUE );
$return .= <<<IPSCONTENT

</header>

{$table}
IPSCONTENT;

		return $return;
}

	function simplifiedViewForumSidebar( $forum, $depth=0 ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="forums.front.forum.flow" data-rootForum="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsWidget cForumMiniList_wrapper' id='elFluidFormFilters'>
	<ul class='cForumMiniList cForumMiniList_multiRoot'>
		<li class="cForumMiniList__category" data-category>
			
IPSCONTENT;

if ( $forum->sub_can_post ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$lastPost = $forum->lastPost();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$unread = \IPS\forums\Topic::containerUnread( $forum );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$children = $forum->children();
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-parent-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-node-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-has-children="1" 
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
 
IPSCONTENT;

if ( $forum->feature_color ):
$return .= <<<IPSCONTENT
style="--i-featured: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->feature_color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
; --i-featured-text: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_featureTextColor, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<span class='cForumMiniList__blob' 
IPSCONTENT;

if ( $lastPost AND $lastPost['date'] ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_simple_view_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
" data-ipsTooltip data-ipsTooltip-safe
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<i class='fa-solid fa-check'></i>
					</span>
					<span class='cForumMiniList__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<span class='cForumMiniList__count'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( \IPS\forums\Topic::contentCountItemsOnly( $forum ) );
$return .= <<<IPSCONTENT
</span>
				</a>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-parent-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-node-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $forum->hasChildren() ):
$return .= <<<IPSCONTENT

				<ul class='cForumMiniList'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums", 'front' )->simplifiedViewForumSidebar_children( $forum, $depth+1 );
$return .= <<<IPSCONTENT

				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</li>			
	</ul>
	<div class='ipsResponsive_hideDesktop'>
		<hr class='ipsHr'>
		<a href='#' class='ipsButton ipsButton--wide ipsButton--primary' data-action='dialogClose'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'done_forum_filtering', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function simplifiedViewForumSidebar_children( $parent ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $parent->hasChildren()  ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $parent->children() as $idx => $forum ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$lastPost = $forum->lastPost();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$unread = \IPS\forums\Topic::containerUnread( $forum );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$children = $forum->children();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( ! $forum->redirect_on and ( $forum->can('read') or !$forum->sub_can_post )  ):
$return .= <<<IPSCONTENT

			<li class="
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT
cForumMiniList__category
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $forum->feature_color ):
$return .= <<<IPSCONTENT
style="--i-featured: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->feature_color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
; --i-featured-text: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_featureTextColor, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-parent-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $parent->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-node-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-has-children="
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT
1
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
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
>
					<span class='cForumMiniList__blob' 
IPSCONTENT;

if ( $lastPost AND $lastPost['date'] ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_simple_view_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
" data-ipsTooltip data-ipsTooltip-safe
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<i class='fa-solid fa-check'></i>
					</span>
					<span class='cForumMiniList__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<span class='cForumMiniList__count'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( \IPS\forums\Topic::contentCountItemsOnly( $forum ) );
$return .= <<<IPSCONTENT
</span>
				</a>
				
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT

					<ul class='cForumMiniList'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums", 'front' )->simplifiedViewForumSidebar_children( $forum, 1 );
$return .= <<<IPSCONTENT

					</ul>
				
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


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function simplifiedViewSidebar( $forumIds, $map ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="forums.front.forum.flow" class='ipsWidget cForumMiniList_wrapper' id='elFluidFormFilters'>
	<ul class='cForumMiniList 
IPSCONTENT;

if ( \count( \IPS\forums\Forum::roots() ) === 1 ):
$return .= <<<IPSCONTENT
cForumMiniList_singleRoot
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
cForumMiniList_multiRoot
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>	
		
IPSCONTENT;

if ( \count( \IPS\forums\Forum::roots() ) === 1 ):
$return .= <<<IPSCONTENT
			
			
IPSCONTENT;

foreach ( \IPS\forums\Forum::roots() as $category ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums", 'front' )->simplifiedViewSidebar_children( $forumIds, $category, 0 );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( \IPS\forums\Forum::roots() as $category ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $category->hasChildren() ):
$return .= <<<IPSCONTENT

					<li data-category>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-parent-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-node-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class=''><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						
IPSCONTENT;

if ( $category->hasChildren() ):
$return .= <<<IPSCONTENT

							<ul class='cForumMiniList'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums", 'front' )->simplifiedViewSidebar_children( $forumIds, $category, 0 );
$return .= <<<IPSCONTENT

							</ul>
						
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

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $clubForums = \IPS\forums\Forum::clubNodes() ):
$return .= <<<IPSCONTENT

			<li class="
IPSCONTENT;

if ( \in_array( 'clubs', $map ) ):
$return .= <<<IPSCONTENT
cForumMiniList__categorySelected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-category>
				<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=forums&module=forums&controller=index&forumId=clubs", null, "forums", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-parent-id="clubs" data-node-id="clubs" class='
IPSCONTENT;

if ( \in_array( 'clubs', $map ) ):
$return .= <<<IPSCONTENT
cForumMiniList__selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><span class=''>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node_forums', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				<ul class='cForumMiniList'>
					
IPSCONTENT;

foreach ( $clubForums as $idx => $forum ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$lastPost = $forum->lastPost();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$unread = \IPS\forums\Topic::containerUnread( $forum );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$children = $forum->children();
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( ! $forum->redirect_on and $forum->can('read')  ):
$return .= <<<IPSCONTENT

							<li class="
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT
cForumMiniList__category
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $map[ $forum->parent_id ] ) AND \in_array( $forum->_id, $map[ $forum->parent_id ] ) ):
$return .= <<<IPSCONTENT
cForumMiniList__categorySelected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $forum->feature_color ):
$return .= <<<IPSCONTENT
style="--i-featured: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->feature_color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
; --i-featured-text: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_featureTextColor, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-parent-id="clubs" data-node-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-has-children="
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT
1
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" class='
IPSCONTENT;

if ( \in_array( $forum->_id, $forumIds ) ):
$return .= <<<IPSCONTENT
cForumMiniList__selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
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
>
									<span class='cForumMiniList__blob' 
IPSCONTENT;

if ( $lastPost AND $lastPost['date'] ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_simple_view_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
" data-ipsTooltip data-ipsTooltip-safe
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
										<i class='fa-solid fa-check'></i>
									</span>
									<span class='cForumMiniList__title'>
IPSCONTENT;

$sprintf = array($forum->club()->name, $forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
									<span class='cForumMiniList__count'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( \IPS\forums\Topic::contentCount( $forum ) );
$return .= <<<IPSCONTENT
</span>
								</a>
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

endif;
$return .= <<<IPSCONTENT

	</ul>
	<div class='ipsResponsive_hideDesktop'>
		<hr class='ipsHr'>
		<a href='#' class='ipsButton ipsButton--wide ipsButton--primary' data-action='dialogClose'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'done_forum_filtering', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function simplifiedViewSidebar_children( $forumIds, $parent, $depth ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $parent->hasChildren() and $depth < 5 ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $parent->children() as $idx => $forum ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$lastPost = $forum->lastPost();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$unread = \IPS\forums\Topic::containerUnread( $forum );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$children = $forum->children();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( ! $forum->redirect_on and ( $forum->can('read') or !$forum->sub_can_post )  ):
$return .= <<<IPSCONTENT

			<li class="
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT
cForumMiniList__category
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $forum->feature_color ):
$return .= <<<IPSCONTENT
style="--i-featured: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->feature_color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
; --i-featured-text: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_featureTextColor, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-parent-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $parent->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-node-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-has-children="
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT
1
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
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
>
					<span class='cForumMiniList__blob' 
IPSCONTENT;

if ( $lastPost AND $lastPost['date'] ):
$return .= <<<IPSCONTENT
title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forum_simple_view_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $lastPost['date'] instanceof \IPS\DateTime ) ? $lastPost['date'] : \IPS\DateTime::ts( $lastPost['date'] );$return .= $val->html();
$return .= <<<IPSCONTENT
" data-ipsTooltip data-ipsTooltip-safe
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<i class='fa-solid fa-check'></i>
					</span>
					<span class='cForumMiniList__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<span class='cForumMiniList__count'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( \IPS\forums\Topic::contentCount( $forum ) );
$return .= <<<IPSCONTENT
</span>
				</a>
				
IPSCONTENT;

if ( $children ):
$return .= <<<IPSCONTENT

					<ul class='cForumMiniList'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums", 'front' )->simplifiedViewSidebar_children( $forumIds, $forum, $depth+1 );
$return .= <<<IPSCONTENT

					</ul>
				
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


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}