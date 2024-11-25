<?php
namespace IPS\Theme;
class class_blog_front_browse extends \IPS\Theme\Template
{	function categories( $currentCategory=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

	
IPSCONTENT;

$categories = $currentCategory ? $currentCategory->children( 'view', NULL, FALSE ) : \IPS\blog\Category::roots();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !\IPS\blog\Category::theOnlyNode( array(), TRUE, FALSE ) and \count( $categories ) ):
$return .= <<<IPSCONTENT

	<div id='elBlogCategoriesBlock' class='ipsWidget ipsWidget--vertical'>
		<h3 class='ipsWidget__header'>
IPSCONTENT;

if ( $currentCategory ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_subcategories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
		<div class='ipsWidget__content'>
			<div class='ipsSideMenu ipsSideMenu--truncate ipsCategoriesMenu'>
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
								<span class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
								<span class='ipsSideMenu_itemCount'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $category->childrenCount() );
$return .= <<<IPSCONTENT
</span>
							</a>
						</li>
					
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


IPSCONTENT;

		return $return;
}

	function featuredEntries( $featured ) {
		$return = '';
		$return .= <<<IPSCONTENT


<section class='ipsBox ipsPull'>
	<header class='ipsBox__header'>
		<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'blogs-featured-entries' );
$return .= <<<IPSCONTENT

	</header>
	<div class='ipsBox__content'>
		<i-data>
			<div class='ipsData ipsData--featured ipsData--carousel ipsData--blogs-featured-entries' id='blogs-featured-entries' tabindex="0">
				
IPSCONTENT;

foreach ( $featured as $entry ):
$return .= <<<IPSCONTENT

					<article class='ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $entry->hidden() OR $entry->status === 'draft' ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $entry->unread() ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span></a>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsData__image'>
							
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

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->defaultThumb(  );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
						<div class='ipsData__content'>
							<div class='ipsData__main'>
								
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->id ):
$return .= <<<IPSCONTENT

									<div class='i-color_soft i-link-color_inherit i-font-weight_600'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
								
IPSCONTENT;

elseif ( $entry->category_id ):
$return .= <<<IPSCONTENT

									<div class="i-color_soft i-link-color_inherit i-font-weight_600"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->category()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->category()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								<div class='ipsData__title'>
									
IPSCONTENT;

if ( $entry->prefix() ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $entry->prefix( TRUE ), $entry->prefix() );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									<div class='ipsBadges'>
										
IPSCONTENT;

foreach ( $entry->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</div>
									
IPSCONTENT;

if ( $entry->unread() ):
$return .= <<<IPSCONTENT

										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url('getNewComment'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsIndicator' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unread_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unread_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									<h3>
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" data-role="newsTitle">
IPSCONTENT;

$return .= htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									</h3>
								</div>
								<section class='ipsData__desc'>{$entry->truncated(TRUE)}</section>
							</div>
							<div class="ipsData__extra">
								<ul class='ipsData__stats'>
									
IPSCONTENT;

foreach ( $entry->stats(TRUE) as $k => $v ):
$return .= <<<IPSCONTENT

										<li data-stat-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
											
IPSCONTENT;

if ( $k === "comments" ):
$return .= <<<IPSCONTENT

												<span><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
#comments' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "blog_{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a></span>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "blog_{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
								<div class='ipsData__last'>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $entry->author(), 'fluid' );
$return .= <<<IPSCONTENT

									<ul class='ipsData__last-text'>
										<li class='ipsData__last-primary'>
											
IPSCONTENT;

if ( $entry->category_id ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$htmlsprintf = array($entry->author()->link(),$entry->category()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'entry_name_with_cat', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->author()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</li>
										<li class='ipsData__last-secondary'>
IPSCONTENT;

$val = ( $entry->date instanceof \IPS\DateTime ) ? $entry->date : \IPS\DateTime::ts( $entry->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
									</ul>
								</div>
							</div>
						</div>
					</article>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</i-data>
	</div>
</section>
IPSCONTENT;

		return $return;
}

	function index( $table, $featured, $blogs=array(), $viewMode=NULL, $category=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class="ipsPageHeader ipsPageHeader--blogs-index">
	<div class="ipsPageHeader__row">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "header:before", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "header:inside-start", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "title:before", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "title:inside-start", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blogs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "title:inside-end", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "title:after", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "header:inside-end", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "header:after", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $blogs ) || \IPS\blog\Blog::canCreate() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "buttons:before", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="buttons">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "buttons:inside-start", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->indexButtons( $blogs, $viewMode, FALSE, $category );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "buttons:inside-end", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/index", "buttons:after", [ $table,$featured,$blogs,$viewMode,$category ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</header>

IPSCONTENT;

if ( \count( $featured ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->featuredEntries( $featured );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $blogs ) || \IPS\blog\Blog::canCreate() ):
$return .= <<<IPSCONTENT

	<div class="ipsResponsive_showPhone">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->indexButtons( $blogs, $viewMode, TRUE, $category );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<div class="ipsBox ipsPull">
	{$table}
</div>
IPSCONTENT;

		return $return;
}

	function indexButtons( $blogs=array(), $viewMode=NULL, $forMobile=FALSE, $category=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $blogs ) || \IPS\blog\Blog::canCreate() ):
$return .= <<<IPSCONTENT

	<ul class='ipsButtons ipsButtons--main 
IPSCONTENT;

if ( !$forMobile ):
$return .= <<<IPSCONTENT
ipsResponsive_hidePhone
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( \count( $blogs ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \count( $blogs ) > 1 ):
$return .= <<<IPSCONTENT

				<li>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=blog&module=blogs&controller=browse&page=1&filter=my_blogs&sortby=blog_last_edate", null, "blogs", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsMenu id='elMyBlogs' class='ipsButton ipsButton--text'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'my_blogs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></a>
					<ul class='ipsMenu ipsMenu_normal ipsHide' id='elMyBlogs_menu'>
						
IPSCONTENT;

foreach ( $blogs as $blog ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item'>
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

if ( \IPS\blog\Blog::canCreate() ):
$return .= <<<IPSCONTENT

				<li>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=blog&module=blogs&controller=create", null, "blog_create", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_new_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( \IPS\blog\Blog::canCreate() ):
$return .= <<<IPSCONTENT

			<li>
				<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=blog&module=blogs&controller=create", null, "blog_create", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $blogs ) AND \IPS\blog\Blog::canOnAny( 'add' ) ):
$return .= <<<IPSCONTENT

			<li>
				
IPSCONTENT;

if ( \count( $blogs ) > 1 ):
$return .= <<<IPSCONTENT

					<a class="ipsButton ipsButton--primary" href="#elCreateEntry_menu" data-ipsMenu id='elCreateEntry'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></a>
					<ul class='ipsMenu ipsMenu_normal ipsHide' id='elCreateEntry_menu'>
						<li class='ipsMenu_title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_entry_to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

foreach ( $blogs as $blog ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $blog->disabled != 1 ):
$return .= <<<IPSCONTENT

								<li class='ipsMenu_item'>
									<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=blog&module=blogs&controller=submit&id={$blog->id}", null, "blog_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
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

					
IPSCONTENT;

foreach ( $blogs as $blog ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $blog->disabled != 1 ):
$return .= <<<IPSCONTENT

							<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=blog&module=blogs&controller=submit&id={$blog->id}", null, "blog_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel='nofollow noindex'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</li>
		
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

	function indexGrid( $entries, $featured, $blogs, $pagination, $viewMode, $category ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class="ipsPageHeader ipsPageHeader--blogs-grid">
	<div class="ipsPageHeader__row">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "header:before", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "header:inside-start", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "title:before", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "title:inside-start", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blogs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "title:inside-end", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "title:after", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "header:inside-end", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "header:after", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $blogs ) || \IPS\blog\Blog::canCreate() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "buttons:before", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="buttons">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "buttons:inside-start", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->indexButtons( $blogs, $viewMode, FALSE, $category );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "buttons:inside-end", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/browse/indexGrid", "buttons:after", [ $entries,$featured,$blogs,$pagination,$viewMode,$category ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</header>

IPSCONTENT;

if ( \count( $featured ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->featuredEntries( $featured );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $blogs ) || \IPS\blog\Blog::canCreate() ):
$return .= <<<IPSCONTENT

	<div class="ipsResponsive_showPhone">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->indexButtons( $blogs, $viewMode, TRUE, $category );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<section class="ipsBox ipsPull">
	
IPSCONTENT;

if ( \count($entries) ):
$return .= <<<IPSCONTENT

		<i-data>
			<div class="ipsData ipsData--grid ipsData--blog-entries">
				
IPSCONTENT;

foreach ( $entries as $id => $entry ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "blog", 'front' )->indexGridEntry( $entry, true );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</i-data>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_entries_yet', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</section>

IPSCONTENT;

if ( $pagination['pages'] > 1 ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $pagination['url'], $pagination['pages'], $pagination['page'], $pagination['perpage'], TRUE, 'page' );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function indexGridEntry( $entry, $primary=false, $table=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<article class='ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $entry->hidden() OR $entry->status === 'draft' ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $entry->unread() ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span></a>
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' class='ipsData__image'>
		
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

	</a>
	<div class='ipsData__content'>
		<div class='ipsData__main'>
			
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->id ):
$return .= <<<IPSCONTENT

				<div class='i-color_soft i-link-color_inherit i-font-weight_600'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
			
IPSCONTENT;

elseif ( $entry->category_id ):
$return .= <<<IPSCONTENT

				<div class="i-color_soft i-link-color_inherit i-font-weight_600"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->category()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->category()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsData__title'>
				
IPSCONTENT;

if ( $entry->prefix() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $entry->prefix( TRUE ), $entry->prefix() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='ipsBadges'>
					
IPSCONTENT;

foreach ( $entry->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( $entry->unread() ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url('getNewComment'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsIndicator' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unread_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unread_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<h3>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
" data-role="newsTitle">
IPSCONTENT;

$return .= htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
				</h3>
			</div>
			<section class='ipsData__desc'>{$entry->truncated(TRUE)}</section>
		</div>
		<div class="ipsData__extra">
			<ul class='ipsData__stats'>
				
IPSCONTENT;

foreach ( $entry->stats(TRUE) as $k => $v ):
$return .= <<<IPSCONTENT

					<li data-stat-type="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
						
IPSCONTENT;

if ( $k === "comments" ):
$return .= <<<IPSCONTENT

							<span><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
#comments' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "blog_{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a></span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "blog_{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
			<div class='ipsData__last'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $entry->author(), 'fluid' );
$return .= <<<IPSCONTENT

				<ul class='ipsData__last-text'>
					<li class='ipsData__last-primary'>
						
IPSCONTENT;

if ( $entry->category_id ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$htmlsprintf = array($entry->author()->link(),$entry->category()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'entry_name_with_cat', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->author()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->author()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
					<li class='ipsData__last-secondary'>
IPSCONTENT;

$val = ( $entry->date instanceof \IPS\DateTime ) ? $entry->date : \IPS\DateTime::ts( $entry->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
				</ul>
			</div>
		</div>
		
IPSCONTENT;

if ( $table and $table->canModerate() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$idField = $entry::$databaseColumnId;
$return .= <<<IPSCONTENT

			<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->$idField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $entry ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( $entry->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsInput ipsInput--toggle'>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</article>
IPSCONTENT;

		return $return;
}

	function rows( $table, $headers, $blogs ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $blogs ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $blogs as $blog ):
$return .= <<<IPSCONTENT


		
IPSCONTENT;

$coverPhoto = $blog->coverPhoto();
$return .= <<<IPSCONTENT

		<li class='ipsData__blog-item ipsColumns ipsColumns--lines'>
			<div class='ipsColumns__secondary i-basis_380 i-background_2 i-text-align_center cBlogInfo 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>

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
;"></div>
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

					<ul class='ipsList ipsList--stacked ipsList--fill i-margin-top_3'>
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

					<div class='i-flex'>
						<div class='i-flex_11'>
					
							<div class='ipsTitle ipsTitle--h4'>
								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->latestEntry()->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
								<div class='ipsBadges'>
								    
IPSCONTENT;

foreach ( $blog->latestEntry()->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</div>
								
								<h3>
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
						</div>

						
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

							<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $blog ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='' class='ipsInput ipsInput--toggle'>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


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


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<li><p class="ipsEmptyMessage">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p></li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}