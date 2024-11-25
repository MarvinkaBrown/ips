<?php
namespace IPS\Theme;
class class_blog_front_widgets extends \IPS\Theme\Template
{	function blogCommentFeed( $comments, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $comments )  ):
$return .= <<<IPSCONTENT

	<h3 class='ipsWidget__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
	<div class='ipsWidget__content'>
		
IPSCONTENT;

if ( isset($orientation) and $orientation == 'vertical' ):
$return .= <<<IPSCONTENT

			<i-data>
				<ul class='ipsData ipsData--table ipsData--blog-comment-feed'>
					
IPSCONTENT;

foreach ( $comments as $comment ):
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
							<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $comment->author(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
							<div class='ipsData__main'>
								<div class='ipsData__title'>
									<h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
								</div>
								<p class='ipsData__meta'><span>{$comment->author()->link()}</span> <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">{$comment->dateLine()}</a></p>
								<div class='ipsData__desc'>{$comment->truncated( true )}</div>
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
		
			<ul class='ipsEntries ipsEntries--blog-comment-widget'>
				
IPSCONTENT;

foreach ( $comments as $comment ):
$return .= <<<IPSCONTENT

					<li>
						<article class='ipsEntry ipsEntry--simple'>
							<div class='ipsEntry__content'>
								<header class='ipsEntry__header'>
									<div class='ipsEntry__header-align'>
										<div class='ipsPhotoPanel'>
											<div class='ipsAvatarStack'>
												
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
											<div class='ipsPhotoPanel__text'>
												<div class='ipsEntry__username'>
													<h3><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
												</div>
												<p class='ipsPhotoPanel__secondary'>
													
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $comment->author(), $comment->warningRef(), NULL, $comment->isAnonymous() );
$return .= <<<IPSCONTENT

													&middot;
													<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>{$comment->dateLine()}</a>
													
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

if ( $comment->canReportOrRevoke() === TRUE ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$id = mt_rand();
$return .= <<<IPSCONTENT

											<a href='#elBlogCommentFeed_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsEntry__topButton ipsEntry__topButton--ellipsis' id='elBlogCommentFeed_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsMenu><i class='fa-solid fa-ellipsis' aria-hidden="true"></i><span class='ipsInvisible'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'more_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</div>
								</header>
								<div class='ipsEntry__post'>
									
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and $comment->isHighlighted() ):
$return .= <<<IPSCONTENT

										<ul class='ipsBadges'>									
											<li><span class='ipsBadge ipsBadge--popular'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_a_popular_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></li>
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

if ( $comment->hidden() === 1 && $comment->author()->member_id == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

										<div class='i-margin-bottom_3'><strong class='i-color_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'comment_awaiting_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									<div class='ipsRichText ipsTruncate_6' data-role='commentContent' data-controller='core.front.core.lightboxedImages'>
										{$comment->content()}
									</div>
								</div>
								
IPSCONTENT;

if ( $comment->hidden() !== 1 && \IPS\IPS::classUsesTrait( $comment, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

									<div class='ipsEntry__footer'>
										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $comment );
$return .= <<<IPSCONTENT

									</div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						</article>
						
IPSCONTENT;

if ( $comment->canReportOrRevoke() === TRUE ):
$return .= <<<IPSCONTENT

							<ul id='elBlogCommentFeed_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu' class='ipsMenu ipsMenu_auto ipsHide'>
								<li class='ipsMenu_item'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url('report'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'report', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							</ul>
						
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

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function blogs( $blogs, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>

IPSCONTENT;

if ( isset($orientation) and $orientation == 'vertical' ):
$return .= <<<IPSCONTENT

<div class='ipsWidget__content'>
	<i-data>
		<ul class='ipsData ipsData--table ipsData--blogs-widget'>
			
IPSCONTENT;

foreach ( $blogs as $blog ):
$return .= <<<IPSCONTENT

				<li class='ipsData__item'>
					<div class='i-flex_11'>
						<h4 class='i-font-weight_600 i-margin-bottom_2'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($blog->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class="i-color_hard">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
						<ul class='ipsGrid ipsGrid--blog-stats ipsGrid--3'>
							<li><strong class='i-font-weight_600 i-font-size_2 i-color_hard'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_items, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong><br><span class='i-color_soft i-font-size_-1'>
IPSCONTENT;

$pluralize = array( $blog->_items ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></li>
							<li><strong class='i-font-weight_600 i-font-size_2 i-color_hard'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong><br><span class='i-color_soft i-font-size_-1'>
IPSCONTENT;

$pluralize = array( $blog->_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></li>
							<li><strong class='i-font-weight_600 i-font-size_2 i-color_hard'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->num_views, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong><br><span class='i-color_soft i-font-size_-1'>
IPSCONTENT;

$pluralize = array( $blog->num_views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_views', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></li>
						</ul>
					</div>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<i-data>
		<ol class="ipsData ipsData--table ipsData--blogs-widget" data-role="tableRows">
		
IPSCONTENT;

foreach ( $blogs as $blog ):
$return .= <<<IPSCONTENT

			<li class='ipsData__blog-item ipsColumns ipsColumns--lines'>
				<div class='ipsColumns__secondary i-basis_380 i-background_2 i-text-align_center cBlogInfo'>

					
IPSCONTENT;

$coverPhoto = $blog->coverPhoto();
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCoverPhoto ipsCoverPhoto--blog-listing' data-url="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url()->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" style='--offset:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-hidden="true" tabindex="-1">
						
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
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->coverPhotoBackgroundColor(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></div>
							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>

					<div class='i-padding_3'>
						<h3 class='ipsTitle ipsTitle--h3'>
							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($blog->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_to_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						</h3>

						<div class='i-font-weight_500 i-color_soft i-margin-top_1'>
							
IPSCONTENT;

if ( $blog->owner() instanceof \IPS\Member ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$htmlsprintf = array($blog->owner()->link(), $blog->category()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

elseif ( $club = $blog->club() ):
$return .= <<<IPSCONTENT

								<i class='fa-solid fa-users'></i> 
IPSCONTENT;

$sprintf = array($club->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_blog_for', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<i class='fa-solid fa-users'></i> 
IPSCONTENT;

$htmlsprintf = array(\IPS\Member::loggedIn()->language()->addToStack( 'blogs_groupblog_name_' . $blog->id ), $blog->category()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'group_blog_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<ul class='ipsList ipsList--stats ipsList--stacked ipsList--fill i-margin-top_3'>
							<li><span class="ipsList__value">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_items, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> <span class="ipsList__label">
IPSCONTENT;

$pluralize = array( $blog->_items ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></li>
							<li><span class="ipsList__value">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> <span class="ipsList__label">
IPSCONTENT;

$pluralize = array( $blog->_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></li>
							<li><span class="ipsList__value">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->num_views, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> <span class="ipsList__label">
IPSCONTENT;

$pluralize = array( $blog->num_views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_views', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span></li>
						</ul>
						
IPSCONTENT;

if ( $blog->description ):
$return .= <<<IPSCONTENT

							<div class='ipsTruncate_x ipsHide' style='--line-clamp: 9'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $blog->description );
$return .= <<<IPSCONTENT
</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
				<div class='ipsColumns__primary i-padding_3'>

					
IPSCONTENT;

if ( $blog->latestEntry() ):
$return .= <<<IPSCONTENT

						<div class='ipsTitle--h4'>
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							
							<h3>
								<span class='ipsBadges'>
									
IPSCONTENT;

foreach ( $blog->latestEntry()->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</span>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($blog->latestEntry()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
									
IPSCONTENT;

if ( $blog->latestEntry()->unread() ):
$return .= <<<IPSCONTENT

										<span class='ipsIndicator' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unread_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								</a>
							</h3>
						</div>
						<div class='ipsPhotoPanel ipsPhotoPanel--small i-margin-top_1 i-margin-bottom_2'>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $blog->latestEntry()->author(), '' );
$return .= <<<IPSCONTENT

							<div class='ipsPhotoPanel__text'>
								<p class='i-color_soft i-link-color_inherit'>
IPSCONTENT;

$htmlsprintf = array(trim( $blog->latestEntry()->author()->link() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'latest_entry_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $blog->latestEntry()->date instanceof \IPS\DateTime ) ? $blog->latestEntry()->date : \IPS\DateTime::ts( $blog->latestEntry()->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
							</div>
						</div>

						<div class='ipsRichText cBlogInfo_content' data-controller="core.front.core.lightboxedImages">
							{$blog->latestEntry()->content()}
						</div>

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_entries_yet', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

$recentEntries = iterator_to_array( $blog->_recentEntries );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \count( $recentEntries ) > 1 ):
$return .= <<<IPSCONTENT

						<h4 class='ipsTitle ipsTitle--h5 ipsTitle--margin i-margin-top_4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'recent_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
						<ul class='ipsBlogs__blog-listing-entries'>
							
IPSCONTENT;

foreach ( $recentEntries as $entry ):
$return .= <<<IPSCONTENT

								<li>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $entry->author(), 'fluid' );
$return .= <<<IPSCONTENT

									<div class='i-flex_11 i-flex i-gap_2 i-row-gap_0 i-flex-wrap_wrap'>
										<h4 class='
IPSCONTENT;

if ( $entry->unread() ):
$return .= <<<IPSCONTENT
font-weight_700
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-font-weight_500
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-flex_11'>
											
IPSCONTENT;

if ( $entry->unread() ):
$return .= <<<IPSCONTENT

												<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsIndicator'></a>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
												
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

											</a>
										</h4>
										
IPSCONTENT;

if ( !( $blog->owner() instanceof \IPS\Member ) ):
$return .= <<<IPSCONTENT

											<p class='i-font-size_-1 i-color_soft'>
IPSCONTENT;

$htmlsprintf = array(trim( $blog->latestEntry()->author()->link() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $blog->latestEntry()->date instanceof \IPS\DateTime ) ? $blog->latestEntry()->date : \IPS\DateTime::ts( $blog->latestEntry()->date );$return .= $val->html();
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

						</ul>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				</div>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	</i-data>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function blogStatistics( $stats, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_blogStatistics', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
	<ul class='ipsList ipsList--stats ipsList--stacked ipsList--border ipsList--fill'>
		<li>
			<strong class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_blogs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $stats['total_blogs'] );
$return .= <<<IPSCONTENT
</span>
		</li>
		<li>
			<strong class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $stats['total_entries'] );
$return .= <<<IPSCONTENT
</span>
		</li>
	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function entryFeed( $entries, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $entries )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget-blog-entry-feed_' . mt_rand();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class='ipsWidget__content'>
		<i-data>
			<ul class="ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--widget-blog-entry-feed" 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $entries as $entry ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "blog" )->entryRow( $entry );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function entryRow( $entry, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsData__item'>
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url('getLastComment'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<figure class='ipsData__image'>
		
IPSCONTENT;

if ( $entry->cover_photo ):
$return .= <<<IPSCONTENT

			<img src='
IPSCONTENT;

$return .= \IPS\File::get( "blog_Entries", $entry->cover_photo )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<div class="ipsFallbackImage" aria-hidden="true"></div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</figure>
	<div class="ipsData__content">
		<div class='ipsData__main'>
			<div class='ipsData__title'><h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url('getLastComment'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($entry->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4></div>
			<div class='ipsData__meta'>
IPSCONTENT;

$htmlsprintf = array($entry->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
			
IPSCONTENT;

if ( \IPS\Settings::i()->blog_enable_rating ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $entry->averageRating(), 5, $entry->memberRating() );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( isset($orientation) and $orientation == 'horizontal' ):
$return .= <<<IPSCONTENT

				<div class='ipsData__desc ipsRichText ipsTruncate_2'>{$entry->truncated()}</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<ul class="ipsData__stats">
			<li data-stattype="comments">
				<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $entry->num_comments );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $entry->num_comments );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$pluralize = array( $entry->num_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
				<span class='ipsData__stats-label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $entry->num_comments );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$pluralize = array( $entry->num_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
			</li>
		</ul>
	</div>
</li>
IPSCONTENT;

		return $return;
}}