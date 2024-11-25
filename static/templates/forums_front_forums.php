<?php
namespace IPS\Theme;
class class_forums_front_forums extends \IPS\Theme\Template
{	function clubForums(  ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/clubForums", "header:before", [  ] );
$return .= <<<IPSCONTENT
<header class="ipsPageHeader ipsPageHeader--forum-table" data-ips-hook="header">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/clubForums", "header:inside-start", [  ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/clubForums", "title:before", [  ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/clubForums", "title:inside-start", [  ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node_forums', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/clubForums", "title:inside-end", [  ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/clubForums", "title:after", [  ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/clubForums", "header:inside-end", [  ] );
$return .= <<<IPSCONTENT
</header>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/clubForums", "header:after", [  ] );
$return .= <<<IPSCONTENT


<ol class="ipsBlockSpacer" data-controller="forums.front.forum.forumList" data-baseurl="">
	<li class="ipsBox ipsPull">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subforums_header_category', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<div class="ipsBox__content">
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue('forums_forum') === 'grid' ):
$return .= <<<IPSCONTENT

				<i-data>
					<div class="ipsData ipsData--grid ipsData--forum-grid i-padding_3 i-background_2 i-border-end-start-radius_box i-border-end-end-radius_box">
						
IPSCONTENT;

foreach ( \IPS\forums\Forum::clubNodes() as $childforum ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumGridItem( $childforum );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</div>
				</i-data>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i-data>
					<ol class="ipsData ipsData--table ipsData--category ipsData--forum-category">
						
IPSCONTENT;

foreach ( \IPS\forums\Forum::clubNodes() as $childforum ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumRow( $childforum, TRUE );
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
	</li>
</ol>
IPSCONTENT;

		return $return;
}

	function forumButtons( $forum ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumButtons", "buttons:before", [ $forum ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="buttons" class="ipsButtons ipsButtons--main i-margin-block_block">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumButtons", "buttons:inside-start", [ $forum ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $forum->isCombinedView() ):
$return .= <<<IPSCONTENT

		<li>
			<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( [ 'do' => 'createMenu', 'root' => $forum->id ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_new_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow noindex"><i class="fa-regular fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start_new_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
		</li>
	
IPSCONTENT;

elseif ( $forum->can('add') ):
$return .= <<<IPSCONTENT

		<li>
			<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( 'do', 'add' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start_new_topic_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" rel="nofollow noindex"><i class="fa-regular fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start_new_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumButtons", "buttons:inside-end", [ $forum ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumButtons", "buttons:after", [ $forum ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function forumDisplay( $forum, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $forum->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $forum );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id="elClubContainer">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $forum->feature_color ):
$return .= <<<IPSCONTENT
<style>:root{--i-featured: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->feature_color, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
}</style>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\IPS\Widget\Request::i()->advancedSearchForm ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$followerCount = \IPS\forums\Topic::containerFollowerCount( $forum );
$return .= <<<IPSCONTENT

	<header class="ipsPageHeader ipsBox ipsPull ipsPageHeader--topic-list 
IPSCONTENT;

if ( $forum->feature_color ):
$return .= <<<IPSCONTENT
ipsPageHeader--hasFeatureColor
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
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<div class="ipsPageHeader__row">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "header:before", [ $forum,$table ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "header:inside-start", [ $forum,$table ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "title:before", [ $forum,$table ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "title:inside-start", [ $forum,$table ] );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "title:inside-end", [ $forum,$table ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "title:after", [ $forum,$table ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $forum->description ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $forum->description, array('ipsPageHeader__desc') );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') AND \IPS\cloud\Application::featureIsEnabled( 'realtime' ) ):
$return .= <<<IPSCONTENT

					<div data-controller="cloud.front.realtime.whosViewing" data-location="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\cloud\Realtime::i()->getLocationHash(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "header:inside-end", [ $forum,$table ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "header:after", [ $forum,$table ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $forum->sub_can_post and !$forum->password ):
$return .= <<<IPSCONTENT

				<ul class="ipsButtons">
					
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "analytics", "cloud" )->analyticsButton( $forum, $forum->_id );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'forums','forum', $forum->_id, $followerCount );
$return .= <<<IPSCONTENT
</li>
				</ul>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $forum->isCombinedView() ):
$return .= <<<IPSCONTENT

			<div class="ipsPageHeader__row ipsResponsive_hideDesktop">
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "buttons:before", [ $forum,$table ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="buttons" class="ipsButtons ipsButtons--fill">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "buttons:inside-start", [ $forum,$table ] );
$return .= <<<IPSCONTENT

					<li>
						<a class="ipsButton ipsButton--inherit" href="#" rel="nofollow" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forums_simple_dialog_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-content="#elFluidFormFilters">
							<span data-role="fluidForumMobileDesc">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'forums_simple_filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span> <i class="fa-solid fa-angle-down"></i>
						</a>
					</li>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "buttons:inside-end", [ $forum,$table ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "buttons:after", [ $forum,$table ] );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $forum->show_rules == 1 ):
$return .= <<<IPSCONTENT

			<div class="ipsPageHeader__row">
				<a href="#elForumRules" class="ipsJS_show" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$val = "forums_forum_{$forum->id}_rulestitle"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsdialog-content="#elForumRules">
IPSCONTENT;

$val = "forums_forum_{$forum->id}_rulestitle"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				<div id="elForumRules" class="ipsJS_hide i-background_2 i-padding_3">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('forums_forum_' . $forum->id . '_rules'), array('') );
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

elseif ( $forum->show_rules == 2 ):
$return .= <<<IPSCONTENT

			<div class="ipsPageHeader__row">
				<strong class="i-font-size_2 i-font-weight_600 i-margin-bottom_1 i-display_block">
IPSCONTENT;

$val = "forums_forum_{$forum->id}_rulestitle"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( \IPS\Member::loggedIn()->language()->addToStack('forums_forum_' . $forum->id . '_rules'), array('') );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	
IPSCONTENT;

if ( ! $forum->isCombinedView() and $forum->children() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "subForums:before", [ $forum,$table ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="subForums" class="ipsBox ipsBox--forum-categories ipsPull" data-controller="core.global.core.table, forums.front.forum.forumList" data-baseurl="">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "subForums:inside-start", [ $forum,$table ] );
$return .= <<<IPSCONTENT

			<h2 class="ipsBox__header">
IPSCONTENT;

if ( $forum->sub_can_post ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subforums_header', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subforums_header_category', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</h2>
			<div class="ipsBox__content">
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue('forums_forum') === 'grid' ):
$return .= <<<IPSCONTENT

					<i-data>
						<ol class="ipsData ipsData--grid ipsData--forum-grid">
							
IPSCONTENT;

foreach ( $forum->children( 'view' ) as $childforum ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumGridItem( $childforum );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ol>
					</i-data>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<i-data>
						<ol class="ipsData ipsData--table ipsData--category ipsData--forum-category">
							
IPSCONTENT;

foreach ( $forum->children( 'view' ) as $childforum ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "index", "forums" )->forumRow( $childforum, TRUE );
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "subForums:inside-end", [ $forum,$table ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumDisplay", "subForums:after", [ $forum,$table ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div data-controller="forums.front.forum.forumPage,cloud.front.realtime.viewingProvider">
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forums", \IPS\Request::i()->app )->forumButtons( $forum );
$return .= <<<IPSCONTENT

	{$table}
</div>

IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id || !\IPS\Widget\Request::i()->advancedSearchForm && $forum->sub_can_post and !$forum->password ):
$return .= <<<IPSCONTENT

	<div class="ipsPageActions ipsBox i-padding_2 ipsPull ipsResponsive_showPhone">
		
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "analytics", "cloud" )->analyticsButton( $forum, $forum->_id );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $forum->url()->setQueryString( array( 'do' => 'markRead', 'fromForum' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_forum_read_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_forum_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->advancedSearchForm && $forum->sub_can_post and !$forum->password ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'forums','forum', $forum->_id, $followerCount );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $forum->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function forumHome( $forum, $featuredTopic, $popularQuestions, $newQuestions ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!-- v5 todo: Is this template used? -->
<style>*{ color: #bde361 !important; }</style>


IPSCONTENT;

if ( $featuredTopic ):
$return .= <<<IPSCONTENT

	<div class="i-padding_3">
		<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured_question', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<br><br>
		<div class="ipsPhotoPanel">
			<span class="ipsUserPhoto">
				<img src='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $featuredTopic->author()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
			</span>
			<div class='ipsPhotoPanel__text'>
				<h3 class="i-font-size_2">
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $featuredTopic->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $featuredTopic->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				</h3>
				<p class="i-color_soft">
IPSCONTENT;

$htmlsprintf = array($featuredTopic->author()->link( NULL, NULL, $featuredTopic->isAnonymous() ), \IPS\DateTime::ts( $featuredTopic->start_date )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ask_byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
				<br>
				<div class="ipsTruncate_5">
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $featuredTopic->truncated(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				</div>
			</div>
		</div>
	</div>
	<hr class="ipsHr">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsSpanGrid">
	<div class="ipsSpanGrid__6 i-padding_3">
		<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'most_popular_questions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'from_the_past_30_days', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<i-data>
			<ol class="ipsData ipsData--table ipsData--popular-questions" id="elTopicList">
				
IPSCONTENT;

foreach ( $popularQuestions as $question ):
$return .= <<<IPSCONTENT

					<li class="ipsData__item" 
IPSCONTENT;

if ( $question->unread() ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<div class='i-basis_50'>
							<span class='ipsUserPhoto ipsUserPhoto--mini'>
								<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question->author()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
							</span>
						</div>
						<div class="ipsData__main">
							<h4 class="ipsData__title">
IPSCONTENT;

if ( $question->unread() ):
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-circle cTopicIcon"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
							<ul class="ipsData__meta ipsList ipsList--inline i-color_soft">
								
IPSCONTENT;

if ( $question->bestAnswer() ):
$return .= <<<IPSCONTENT

									<li>
										
IPSCONTENT;

$htmlsprintf = array($question->bestAnswer()->author()->link( NULL, NULL, $question->bestAnswer()->isAnonymous() ), \IPS\DateTime::ts( $question->bestAnswer()->post_date )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'best_answer_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ol>
		</i-data>
	</div>
	<div class="ipsSpanGrid__6 i-padding_3">
		<h2 class="ipsTitle ipsTitle--h3">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'new_questions', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'awaiting_an_answer', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		<ol class="ipsData ipsData--compact ipsData ipsData--question-list" id="elTopicList">
			
IPSCONTENT;

foreach ( $newQuestions as $question ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item" 
IPSCONTENT;

if ( $question->unread() ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					<div class='i-basis_50'>
						<span class='ipsUserPhoto ipsUserPhoto--mini'>
							<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question->author()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' loading='lazy'>
						</span>
					</div>
					<div class="ipsData__main">
						<h4 class="ipsData__title">
IPSCONTENT;

if ( $question->unread() ):
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-circle cTopicIcon"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
						<ul class="ipsData__meta ipsList ipsList--inline i-color_soft">
							<li>
								
IPSCONTENT;

$htmlsprintf = array($question->author()->link( NULL, NULL, $question->isAnonymous() ), \IPS\DateTime::ts( $question->start_date )->html( false )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ask_byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

							</li>
						</ul>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function forumPasswordPopup( $forum, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
<div class="ipsBox">
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<form accept-charset='utf-8' class="ipsForm ipsForm--vertical ipsForm--forum-password" method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsValidation novalidate>
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


		<div class='ipsBox__padding'>
			<p class='i-font-size_3 i-font-weight_600 i-margin-bottom_2'>
				
IPSCONTENT;

$sprintf = array($forum->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enter_forum_password_1', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

			</p>
			<ul class='i-flex i-flex-wrap_wrap i-gap_1'>
				
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $input instanceof \IPS\Helpers\Form\Text ):
$return .= <<<IPSCONTENT

							<li class="i-basis_240 i-flex_91">
								<input type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->formType, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text" required placeholder="
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
'>
								
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

							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				<li class="i-flex_11">
					<button type="submit" class="ipsButton ipsButton--primary i-width_100p">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enter_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</li>
			</ul>
		</div>
	</form>

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

	function forumSelector( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-ips-template='forumSelector' 
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
class='ipsBox'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	{$form}
</div>
IPSCONTENT;

		return $return;
}

	function forumTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "topicListTable:before", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="topicListTable" class="ipsBox ipsPull" data-baseurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-resort="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-tableid="topics" 
IPSCONTENT;

if ( $table->dummyLoading ):
$return .= <<<IPSCONTENT
data-dummyloading
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-controller="core.global.core.table
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT
,core.front.core.moderation
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "topicListTable:inside-start", [ $table,$headers,$rows,$quickSearch ] );
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

		<div class="ipsButtonBar ipsButtonBar--top">
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

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

						<li class="ipsResponsive_hidePhone">
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'do' => 'markRead', 'fromForum' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_forum_read_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-action="markForumRead"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_forum_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $table->sortOptions ) and !empty( $table->sortOptions )  ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "sortOptions:before", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="sortOptions">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "sortOptions:inside-start", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

							<a href="#elSortByMenu_menu" class="ipsDataFilters__button" id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="sortButton" data-ipsmenu data-ipsmenu-activeclass="ipsDataFilters__button--active" data-ipsmenu-selectable="radio"><span>
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $col, 'sortdirection' => $table->getSortDirection( $col ) ) )->setPage('page', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
" rel="nofollow" data-ipsdialog data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'custom_sort', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "sortOptions:inside-end", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "sortOptions:after", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "filters:before", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="filters">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "filters:inside-start", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

							<a href="#elFilterByMenu_menu" class="ipsDataFilters__button" id="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="tableFilterMenu" data-ipsmenu data-ipsmenu-activeclass="ipsDataFilters__button--active" data-ipsmenu-selectable="radio"><span>
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => '', 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage('page', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage('page', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "filters:inside-end", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "filters:after", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

						<li>
							<a href="#elCheck_menu" class="ipsDataFilters__button" id="elCheck_
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
								<span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span> <i class="fa-solid fa-caret-down"></i>
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
" method="post" data-role="moderationTools" data-ipspageaction>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

			<i-data>
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
" id="elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="tableRows">
					
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class="i-text-align_center ipsBox__padding">
				<p class="i-font-size_2 i-font-weight_500 i-margin-bottom_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_topics_in_forum', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

				
IPSCONTENT;

if ( $table->container()->can('add') ):
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->container()->url()->setQueryString( 'do', 'add' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow" class="ipsButton ipsButton--inherit ipsButton--large">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_first_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "pagination:before", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="pagination" class="ipsButtonBar ipsButtonBar--bottom" data-role="tablePagination" 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "pagination:inside-start", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "pagination:inside-end", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "pagination:after", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "topicListTable:inside-end", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/forumTable", "topicListTable:after", [ $table,$headers,$rows,$quickSearch ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function topicHover( $topic, $overviews ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='cTopicHovercard' data-controller='forums.front.forum.hovercard' data-topicID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( \count( $overviews ) > 1 ):
$return .= <<<IPSCONTENT

		<i-tabs class="ipsTabs ipsTabs--small" id="ipsTabs_topic
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsTabBar data-ipsTabBar-contentarea="#ipsTabs_topic
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content">
			<div role="tablist">
				
IPSCONTENT;

foreach ( $overviews as $tabID => $tabData ):
$return .= <<<IPSCONTENT

					<button type="button" id="ipsTabs_topic
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_topic
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel" aria-selected="
IPSCONTENT;

if ( $tabID == 'firstPost' ):
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

$val = "{$tabData[0]}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

		</i-tabs>
		<div id='ipsTabs_topic
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels ipsTabs__panels--padded cTopicHovercard_container ipsScrollbar'>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='i-padding_3 cTopicHovercard_container ipsScrollbar'>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

foreach ( $overviews as $tabID => $tabData ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $overviews ) > 1 ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_topic
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_panel' class='ipsTabs__panel' role="tabpanel" aria-labelledby="ipsTabs_topic
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $tabID != 'firstPost' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='ipsPhotoPanel'>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $tabData[1]->author(), 'fluid' );
$return .= <<<IPSCONTENT

					<div class='ipsPhotoPanel__text'>
						<div class='ipsPhotoPanel__primary'>
							{$tabData[1]->author()->link( NULL, NULL, $tabData[1]->isAnonymous() )}
						</div>
						<div class='ipsPhotoPanel__secondary'>
							
IPSCONTENT;

$val = ( $tabData[1]->mapped('date') instanceof \IPS\DateTime ) ? $tabData[1]->mapped('date') : \IPS\DateTime::ts( $tabData[1]->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $tabData[1]->item()->unread()  ):
$return .= <<<IPSCONTENT

								&middot; <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabData[1]->item()->url('markRead')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_topic_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-action='markTopicRead'><i class='fa-solid fa-check'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_topic_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $tabData[1]->canReportOrRevoke() === TRUE ):
$return .= <<<IPSCONTENT

								&middot; <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabData[1]->url('report'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id or \IPS\Helpers\Form\Captcha::supportsModal() ):
$return .= <<<IPSCONTENT
data-ipsDialog data-ipsDialog-remoteSubmit data-ipsDialog-size='medium' data-ipsDialog-flashMessage='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_submit_success', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-action='reportComment' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
				</div>
				<hr class='ipsHr'>

				<div class='ipsRichText' data-controller='core.front.core.lightboxedImages'>{$tabData[1]->content()}</div>
			
IPSCONTENT;

if ( \count( $overviews ) > 1 ):
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
    
IPSCONTENT;

if ( \IPS\Settings::i()->core_datalayer_enabled ):
$return .= <<<IPSCONTENT

    <script>
        if ( IpsDataLayerConfig && !window.IpsDataLayerConfig && IpsDataLayerConfig._events.content_view.enabled ) {
            $('body').trigger( 'ipsDataLayer', {
                _key: 'content_view',
                _properties: 
IPSCONTENT;

$return .= json_encode(array_replace($topic->getDataLayerProperties(), ['view_location' => 'hovercard', 'page_number' => null]));
$return .= <<<IPSCONTENT

            } );
        }
    </script>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function topicRow( $table, $headers, $rows ) {
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

				<li class="ipsData__item ipsData__item--moved">
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
								<h4>
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
								</h4>
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

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "topicRow:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="topicRow" class="ipsData__item 
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
ipsModerated--future
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $row->isAssignedToMember() ):
$return .= <<<IPSCONTENT
ipsData__item--assigned
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-rowid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-location="
IPSCONTENT;

if ( isset($row->locationHash) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->locationHash, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-controller="cloud.front.realtime.forumsTopicRow" 
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
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "topicRow:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $row->mapped('title') or $row->mapped('title') == 0 ):
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
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id and $row->unread() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "unreadIcon:before", [ $table,$headers,$rows ] );
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "unreadIcon:inside-start", [ $table,$headers,$rows ] );
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "unreadIcon:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "unreadIcon:after", [ $table,$headers,$rows ] );
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

if ( $row->author() != \IPS\Member::loggedIn() and \in_array( $row->$idField, $table->contentPostedIn ) ):
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "topicIcon:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url( "getNewComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
" data-ipstooltip data-ips-hook="topicIcon">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "topicIcon:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "topicIcon:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "title:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<h4 data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "title:inside-start", [ $table,$headers,$rows ] );
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
" data-ipshover-timeout="1.0" 
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "title:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</h4>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "title:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $row->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

								{$row->commentPagination( array(), 'miniPagination' )}
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->assignmentBadge( $row, '' );
$return .= <<<IPSCONTENT

							<div class="ipsBadges">
IPSCONTENT;

foreach ( $row->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
</div>
						</div>
						<div class="ipsData__meta">
							
IPSCONTENT;

$htmlsprintf = array($row->author()->link( NULL, NULL, $row->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
, 
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = ( $row->mapped('date') instanceof \IPS\DateTime ) ? $row->mapped('date') : \IPS\DateTime::ts( $row->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT
</a>
							
IPSCONTENT;

if ( !\in_array( \IPS\Dispatcher::i()->controller, array( 'forums', 'index' ) ) ):
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
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						
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
					<span data-role="activeUsers"></span>
					
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "stats:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="stats" class="ipsData__stats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "stats:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "stats:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "stats:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "latestData:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="latestData" class="ipsData__last">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "latestData:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $row->mapped('num_comments') ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->lastCommenter(), 'fluid' );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row->author(), 'fluid' );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<div class="ipsData__last-text">
								<div class="ipsData__last-primary">
									
IPSCONTENT;

if ( $row->mapped('num_comments') ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $row->mapped('last_comment_anon') ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $row->author(), NULL, NULL, TRUE );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											{$row->lastCommenter()->link()}
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										{$row->author()->link( NULL, NULL, $row->isAnonymous() )}
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
								<div class="ipsData__last-secondary">
									
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT
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

endif;
$return .= <<<IPSCONTENT

										
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

									
IPSCONTENT;

if ( ! \IPS\Output::i()->reduceLinks() ):
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
							</div>
						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "latestData:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "latestData:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

					</div>
				</div>
				
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

					<div class="ipsData__mod">
						<label class="ipsInvisible" for="mod-checkbox-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'moderator_checkbox', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						<input type="checkbox" id="mod-checkbox-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--toggle" data-role="moderation" name="moderate[
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "topicRow:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRow", "topicRow:after", [ $table,$headers,$rows ] );
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

	function topicRowSnippet( $table, $headers, $rows ) {
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

				<li class="ipsData__item ipsData__item--snippet">
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
								<h4>
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
								</h4>
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

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "topicRow:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="topicRow" data-controller="cloud.front.realtime.forumsTopicRow" class="ipsData__item ipsData__item--snippet 
IPSCONTENT;

if ( $row->groupsPosted ):
$return .= <<<IPSCONTENT
ipsData__item--highlighted
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $row->isAssignedToMember() ):
$return .= <<<IPSCONTENT
 ipsData__item--assigned
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "topicRow:inside-start", [ $table,$headers,$rows ] );
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

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $row->unread() ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "unreadIcon:before", [ $table,$headers,$rows ] );
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "unreadIcon:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "unreadIcon:after", [ $table,$headers,$rows ] );
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "badges:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="badges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "badges:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->assignmentBadge( $row, '' );
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

foreach ( $row->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "badges:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "badges:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "title:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<h4 data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "title:inside-start", [ $table,$headers,$rows ] );
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
" data-ipshover-timeout="1.0" 
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "title:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</h4>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "title:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $row->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

										{$row->commentPagination( array(), 'miniPagination' )}
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
								<div class="ipsData__meta i-font-weight_500"><!-- <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">{$row->container()->_title}</a> &middot; -->
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

							<p class="ipsData__desc">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->firstComment->snippet(680), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class="ipsData__extra">
							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "stats:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="stats" class="ipsData__stats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "stats:inside-start", [ $table,$headers,$rows ] );
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "stats:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "stats:after", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "latestPoster:before", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="latestPoster" class="ipsData__last">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "latestPoster:inside-start", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $row->mapped('num_comments') and $row->mapped( 'num_comments') > 1 ):
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "latestPoster:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "latestPoster:after", [ $table,$headers,$rows ] );
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "topicRow:inside-end", [ $table,$headers,$rows ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/forums/topicRowSnippet", "topicRow:after", [ $table,$headers,$rows ] );
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
}}