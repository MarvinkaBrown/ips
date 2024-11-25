<?php
namespace IPS\Theme;
class class_forums_front_global extends \IPS\Theme\Template
{	function commentTableHeader( $comment, $topic ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $topic::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

$iposted = $topic->container()->contentPostedIn( NULL, [ $topic->$idField ] );
$return .= <<<IPSCONTENT

<div>
	<h3 class="ipsTitle ipsTitle--h3">
		
IPSCONTENT;

if ( $topic->unread() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/commentTableHeader", "unreadIcon:before", [ $comment,$topic ] );
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url( 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip class="ipsIndicator 
IPSCONTENT;

if ( \in_array( $topic->$idField, $iposted ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-ips-hook="unreadIcon">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/commentTableHeader", "unreadIcon:inside-end", [ $comment,$topic ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/commentTableHeader", "unreadIcon:after", [ $comment,$topic ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<span class="ipsIndicator ipsIndicator--read 
IPSCONTENT;

if ( \in_array( $topic->$idField, $iposted ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"></span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($topic->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

if ( $topic->container()->allow_rating ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $topic->rating_hits ? ( $topic->rating_total / $topic->rating_hits ) : 0 );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h3>
	<p class="i-color_soft i-link-color_inherit">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></p>
</div>
IPSCONTENT;

		return $return;
}

	function embedPost( $comment, $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsRichEmbed ipsRichEmbed--forum-post'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $comment, $item->mapped('title'), $comment->mapped('date'), $url );
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed_originalItem'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedOriginalItem( $item, TRUE );
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsRichEmbed__snippet'>
			{$comment->truncated(TRUE)}
		</div>
		
IPSCONTENT;

if ( \IPS\Settings::i()->reputation_enabled and \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \count( $comment->reactions() ) ):
$return .= <<<IPSCONTENT

			<ul class='ipsList ipsList--inline'>
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

	function embedTopic( $item, $url ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsRichEmbed ipsRichEmbed--forum-topic'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedHeader( $item, $item->mapped('title'), $item->mapped('date'), $url );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $contentImage = $item->contentImages(1) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$attachType = key( $contentImage[0] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$firstPhoto = \IPS\File::get( $attachType, $contentImage[0][ $attachType ] );
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsRichEmbed_masthead'>
			<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $firstPhoto->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
		</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsRichEmbed__content'>
		<div class='ipsRichEmbed__snippet'>
			{$item->truncated(TRUE)}
		</div>

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "embed", "core" )->embedItemStats( $item );
$return .= <<<IPSCONTENT

	</div>
</div>
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

	<li class="ipsData__item ipsData__item--manage-follow-node-row 
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
'>
		<div class='ipsData__content'>
			<div class='ipsData__main'>
				<h4 class='ipsData__title'>
					
IPSCONTENT;

if ( $row->_locked ):
$return .= <<<IPSCONTENT

						<i class="fa-solid fa-lock"></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</a>
				</h4>
				<ul class='ipsList ipsList--inline i-color_soft'>
					
IPSCONTENT;

if ( $row->memberCanAccessOthersTopics( \IPS\Member::loggedIn() )  ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$count = \IPS\forums\Topic::contentCount( $row, TRUE );
$return .= <<<IPSCONTENT

						<li>
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'posts_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
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

	function row( $table, $headers, $topic, $showReadMarkers=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$idField = $topic::$databaseColumnId;
$return .= <<<IPSCONTENT


IPSCONTENT;

$iPosted = isset( $table->contentPostedIn ) ? $table->contentPostedIn : ( ( $table AND method_exists( $table, 'container' ) AND $topic->container() !== NULL ) ? $topic->container()->contentPostedIn() : array() );
$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "row:before", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="row" class="ipsData__item 
IPSCONTENT;

if ( method_exists( $topic, 'tableClass' ) && $topic->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $topic->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $showReadMarkers and $topic->unread() ):
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "row:inside-start", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	
IPSCONTENT;

if ( $showReadMarkers ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $topic->unread() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "readMarker:before", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url( 'getNewComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsIndicator 
IPSCONTENT;

if ( \in_array( $topic->$idField, $iPosted ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" aria-label="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'first_unread_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip data-ips-hook="readMarker">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "readMarker:inside-end", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "readMarker:after", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "readMarker:before", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
<span class="ipsIndicator ipsIndicator--read 
IPSCONTENT;

if ( \in_array( $topic->$idField, $iPosted ) ):
$return .= <<<IPSCONTENT
ipsIndicator--participated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ips-hook="readMarker">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "readMarker:inside-end", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "readMarker:after", [ $table,$headers,$topic,$showReadMarkers ] );
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

	<div class="ipsData__content">
		<div class="ipsData__main">
			<div class="ipsData__title">
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "badges:before", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="badges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "badges:inside-start", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

				    
IPSCONTENT;

foreach ( $topic->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $topic->prefix() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $topic->prefix( TRUE ), $topic->prefix() );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "badges:inside-end", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "badges:after", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "title:before", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
<h4 data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "title:inside-start", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $topic->tableHoverUrl AND $topic->canView() ):
$return .= <<<IPSCONTENT
data-ipshover data-ipshover-target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->setQueryString('preview', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipshover-timeout="1.5" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "title:inside-end", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
</h4>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "title:after", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $topic->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

					{$topic->commentPagination( array(), 'miniPagination' )}
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsData__meta">
				
IPSCONTENT;

$htmlsprintf = array($topic->author()->link( NULL, NULL, $topic->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $topic->mapped('date') instanceof \IPS\DateTime ) ? $topic->mapped('date') : \IPS\DateTime::ts( $topic->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Widget\Request::i()->controller != 'forums' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( \count( $topic->tags() ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $topic->tags(), true, true );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "stats:before", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="stats" class="ipsData__stats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "stats:inside-start", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $topic->stats(FALSE) as $k => $v ):
$return .= <<<IPSCONTENT

				<li 
IPSCONTENT;

if ( \in_array( $k, $topic->hotStats ) ):
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

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "stats:inside-end", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "stats:after", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

		<div class="ipsData__last">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "latestUserPhoto:before", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
<div class="ipsPhotoPanel" data-ips-hook="latestUserPhoto">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "latestUserPhoto:inside-start", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $topic->mapped('num_comments') ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $topic->lastCommenter(), 'fluid' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $topic->author(), 'fluid' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class="ipsPhotoPanel__text">
					<div class="ipsPhotoPanel__primary">
						
IPSCONTENT;

if ( $topic->mapped('num_comments') ):
$return .= <<<IPSCONTENT

							{$topic->lastCommenter()->link()}
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							{$topic->author()->link( NULL, NULL, $topic->isAnonymous() )}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class="ipsPhotoPanel__secondary">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url( 'getLastComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

if ( $topic->mapped('last_comment') ):
$return .= <<<IPSCONTENT
<span class="ipsData__last-replied">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'replied_lc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span> 
IPSCONTENT;

$val = ( $topic->mapped('last_comment') instanceof \IPS\DateTime ) ? $topic->mapped('last_comment') : \IPS\DateTime::ts( $topic->mapped('last_comment') );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $topic->mapped('date') instanceof \IPS\DateTime ) ? $topic->mapped('date') : \IPS\DateTime::ts( $topic->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
					</div>
				</div>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "latestUserPhoto:inside-end", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "latestUserPhoto:after", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

if ( $table AND method_exists( $table, 'canModerate' ) AND $table->canModerate() ):
$return .= <<<IPSCONTENT

		<div class="ipsData__mod">
			<input class="ipsInput ipsInput--toggle" type="checkbox" data-role="moderation" name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tid, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $topic ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state="
IPSCONTENT;

if ( $topic->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "row:inside-end", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/global/row", "row:after", [ $table,$headers,$topic,$showReadMarkers ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function rows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "forums" )->row( $table, $headers, $row );
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

	function searchNoPermission( $lang, $link=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsStreamItem ipsStreamItem--password ipsStreamItem_contentBlock">
	<div class='ipsStreamItem__iconCell'>
		<i class="fa-solid fa-lock"></i>
	</div>
	<div class='ipsStreamItem__mainCell'>
		<div class='i-color_soft i-font-weight_500'>
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lang, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $link ):
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $link, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'enter_password', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</li>

IPSCONTENT;

		return $return;
}}