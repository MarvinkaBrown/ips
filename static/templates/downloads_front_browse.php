<?php
namespace IPS\Theme;
class class_downloads_front_browse extends \IPS\Theme\Template
{	function categories( $clubsOnly=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "header:before", [ $clubsOnly ] );
$return .= <<<IPSCONTENT
<header data-ips-hook="header" class="ipsPageHeader" id="elDownloadersHeader">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "header:inside-start", [ $clubsOnly ] );
$return .= <<<IPSCONTENT

	<h1 class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( '__app_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "header:inside-end", [ $clubsOnly ] );
$return .= <<<IPSCONTENT
</header>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "header:after", [ $clubsOnly ] );
$return .= <<<IPSCONTENT


IPSCONTENT;

 $layout = \IPS\Member::loggedIn()->getLayoutValue( 'downloads_categories' ); 
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !$clubsOnly ):
$return .= <<<IPSCONTENT

	<div class="ipsBox">
		<h2 class="ipsBox__header">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		<i-data>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "categories:before", [ $clubsOnly ] );
$return .= <<<IPSCONTENT
<ol class="ipsData 
IPSCONTENT;

if ( $layout === 'grid' ):
$return .= <<<IPSCONTENT
ipsData--grid
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsData--table ipsData--category
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--download-category" data-ips-hook="categories">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "categories:inside-start", [ $clubsOnly ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryRow( NULL, NULL, \IPS\downloads\Category::roots() );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "categories:inside-end", [ $clubsOnly ] );
$return .= <<<IPSCONTENT
</ol>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "categories:after", [ $clubsOnly ] );
$return .= <<<IPSCONTENT

		</i-data>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Settings::i()->club_nodes_in_apps and $clubNodes = \IPS\downloads\Category::clubNodes() ):
$return .= <<<IPSCONTENT

	<div class="ipsBox">
		<h2 class="ipsBox__header"><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=browse&id=clubs", null, "downloads_clubs", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></h2>
		<i-data>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "clubNodes:before", [ $clubsOnly ] );
$return .= <<<IPSCONTENT
<ol class="ipsData 
IPSCONTENT;

if ( $layout === 'grid' ):
$return .= <<<IPSCONTENT
ipsData--grid
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsData--table ipsData--category
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--download-category" data-ips-hook="clubNodes">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "clubNodes:inside-start", [ $clubsOnly ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryRow( NULL, NULL, $clubNodes );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "clubNodes:inside-end", [ $clubsOnly ] );
$return .= <<<IPSCONTENT
</ol>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/categories", "clubNodes:after", [ $clubsOnly ] );
$return .= <<<IPSCONTENT

		</i-data>
	</div>

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

if ( !$currentCategory or !$currentCategory->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$categories = $currentCategory ? $currentCategory->children() : \IPS\downloads\Category::roots();
$return .= <<<IPSCONTENT

	
	
IPSCONTENT;

$clubNodes = $currentCategory ? array() : ( \IPS\Settings::i()->club_nodes_in_apps ? \IPS\downloads\Category::clubNodes() : array() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !\IPS\downloads\Category::theOnlyNode() or $clubNodes ):
$return .= <<<IPSCONTENT

	<div id='elDownloadsCategoriesBlock' class='ipsWidget ipsWidget--vertical'>
		<h3 class='ipsWidget__header'>
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
		<div class='ipsWidget__content i-padding_2'>
			<div class='ipsSideMenu ipsSideMenu--truncate'>
				<ul class='ipsSideMenu__list'>
					
IPSCONTENT;

foreach ( $categories as $category ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $category->open OR \IPS\Member::loggedIn()->isAdmin() ):
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
									<span class='ipsBadge ipsBadge--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( \IPS\downloads\File::contentCount( $category ) );
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

											
IPSCONTENT;

if ( $subcategory->open OR \IPS\Member::loggedIn()->isAdmin() ):
$return .= <<<IPSCONTENT

												<li>
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
														<span class='ipsBadge ipsBadge--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( \IPS\downloads\File::contentCount( $subcategory ) );
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

if ( $clubNodes ):
$return .= <<<IPSCONTENT

						<li>
							<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=browse&id=clubs", null, "downloads_clubs", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'>
								<span class='ipsSideMenu__text'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								<span class='ipsBadge ipsBadge--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( \IPS\downloads\Category::filesInClubNodes() );
$return .= <<<IPSCONTENT
</span>
							</a>
							<ul class="ipsSideMenu__list">
								
IPSCONTENT;

$i = 0;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

foreach ( $clubNodes as $idx => $subcategory ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $subcategory->open OR \IPS\Member::loggedIn()->isAdmin() ):
$return .= <<<IPSCONTENT

										<li>
											<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'>
												<span class='ipsSideMenu__text'>
IPSCONTENT;

$sprintf = array($subcategory->club()->name, $subcategory->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club_node', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
												<span class='ipsBadge ipsBadge--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( \IPS\downloads\File::contentCount( $subcategory ) );
$return .= <<<IPSCONTENT
</span>
											</a>
										</li>
										
IPSCONTENT;

$i++;
$return .= <<<IPSCONTENT

									
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
			</div>
			<p>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=browse&do=categories", null, "downloads_categories", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text i-width_100p'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'browse_categories_d', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-arrow-right-long"></i></a>
			</p>
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

	function category( $category, $table ) {
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

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "header:before", [ $category,$table ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "header:inside-start", [ $category,$table ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "title:before", [ $category,$table ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "title:inside-start", [ $category,$table ] );
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "title:inside-end", [ $category,$table ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "title:after", [ $category,$table ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $category->description ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $category->description, array('ipsPageHeader__desc') );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "header:inside-end", [ $category,$table ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "header:after", [ $category,$table ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "headerButtons:before", [ $category,$table ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="headerButtons" class="ipsButtons">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "headerButtons:inside-start", [ $category,$table ] );
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

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'downloads', 'category', $category->_id, \IPS\downloads\File::containerFollowerCount( $category ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "headerButtons:inside-end", [ $category,$table ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/category", "headerButtons:after", [ $category,$table ] );
$return .= <<<IPSCONTENT

	</div>
</header>

<div data-controller="downloads.front.downloads.browse" class="ipsBlockSpacer">
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryMenu( $category->children() );
$return .= <<<IPSCONTENT


	<ul class="ipsButtons ipsButtons--main">
		
IPSCONTENT;

if ( $category->hasChildren() ):
$return .= <<<IPSCONTENT

			<li class="ipsResponsive_hideDesktop"><a href="#elDownloadsCategories_menu" data-ipsmenu id="elDownloadsCategories" class="ipsButton ipsButton--inherit"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'subcategory', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryButtons( $category->can('add'), $category, FALSE );
$return .= <<<IPSCONTENT

	</ul>

	<div class="ipsBox ipsPull cDownloadsCategoryTable">
		{$table}
	</div>

	
IPSCONTENT;

if ( $category AND $category->last_file_id AND \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

		<ul class="ipsButtons ipsButtons--main ipsResponsive_showPhone">
			<li>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url()->setQueryString( array( 'do' => 'markRead' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_category_read_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit" data-action="markCategoryRead"><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_category_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
			</li>
		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


</div>

<div class="ipsBox ipsBox--padding ipsPull ipsResponsive_showPhone">
	<div class="ipsPageActions">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'downloads', 'category', $category->_id, \IPS\downloads\File::containerFollowerCount( $category ) );
$return .= <<<IPSCONTENT

	</div>
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

	function categoryButtons( $canSubmitFiles, $currentCategory=NULL, $showReadButtonOnMobile=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $currentCategory AND $currentCategory->last_file_id AND \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

	<li class='
IPSCONTENT;

if ( !$showReadButtonOnMobile ):
$return .= <<<IPSCONTENT
ipsResponsive_hidePhone
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $currentCategory->url()->setQueryString( array( 'do' => 'markRead' ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_category_read_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit' data-action='markCategoryRead'><i class="fa-solid fa-check"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'mark_category_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $canSubmitFiles ):
$return .= <<<IPSCONTENT

<li>
	
IPSCONTENT;

if ( $currentCategory OR $currentCategory = \IPS\downloads\Category::theOnlyNode() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['idm_bulk_submit'] ):
$return .= <<<IPSCONTENT

			<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=submit&category={$currentCategory->id}&_new=1", null, "downloads_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_a_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-size='narrow' rel='nofollow noindex'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_a_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</a>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=submit&category={$currentCategory->id}&_new=1&do=submit", null, "downloads_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel='nofollow noindex'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_a_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=submit&_new=1", null, "downloads_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_a_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-size='narrow' rel='nofollow noindex'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_a_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

		return $return;
}

	function categoryMenu( $categories ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsMenu ipsMenu_auto ipsHide' id='elDownloadsCategories_menu'>
	
IPSCONTENT;

foreach ( $categories as $cat ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $cat->can('view') ):
$return .= <<<IPSCONTENT

			<li class='ipsMenu_item'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cat->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cat->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
<span class="ipsMenu_itemCount">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( \IPS\downloads\File::contentCount( $cat ) );
$return .= <<<IPSCONTENT
</span></a>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>

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

if ( $category->can('view') AND ( $category->open OR \IPS\Member::loggedIn()->isAdmin() ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$club = $category->club();
$return .= <<<IPSCONTENT

		<li class="ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !\IPS\downloads\File::containerUnread( $category ) ):
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
			<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;

if ( $club ):
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

if ( $category->card_image ):
$return .= <<<IPSCONTENT

					<img src='
IPSCONTENT;

$return .= \IPS\File::get( "downloads_Cards", $category->card_image )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
				
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
" alt='' loading='lazy'>
					
IPSCONTENT;

elseif ( $club->profile_photo ):
$return .= <<<IPSCONTENT

						<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Clubs", $club->profile_photo )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
					
IPSCONTENT;

elseif ( ! empty( $cfObject::$coverPhotoDefault ) ):
$return .= <<<IPSCONTENT

						<div class="ipsFallbackImage" style="--i-empty-image-random--ba-co: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->object->coverPhotoBackgroundColor(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
;"></div>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $icon = $category->getIcon() ):
$return .= <<<IPSCONTENT

						{$icon}
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</figure>

			<div class="ipsData__content">
				<div class="ipsData__main">
					<div class='ipsData__title'>
						<h4>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

if ( $club ):
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
						</h4>
					</div>
					
IPSCONTENT;

if ( $category->description ):
$return .= <<<IPSCONTENT

						<div class="ipsData__desc">
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $category->description, array('') );
$return .= <<<IPSCONTENT

						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $category->hasChildren() ):
$return .= <<<IPSCONTENT

						<ul class="ipsSubList">
							
IPSCONTENT;

foreach ( $category->children() as $subcategory ):
$return .= <<<IPSCONTENT

								<li class="ipsSubList__item 
IPSCONTENT;

if ( \IPS\downloads\File::containerUnread( $subcategory ) ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subcategory->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
				<ul class="ipsData__stats">
					
IPSCONTENT;

$count = \IPS\downloads\File::contentCount( $category );
$return .= <<<IPSCONTENT

					<li>
						<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $count );
$return .= <<<IPSCONTENT
</span>
						<span>
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'files_no_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				</ul>
				
IPSCONTENT;

if ( $lastPost = $category->lastFile() ):
$return .= <<<IPSCONTENT

					<div class="ipsData__last">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $lastPost->author(), 'fluid' );
$return .= <<<IPSCONTENT

						<div class='ipsData__last-text'>
							<div class='ipsData__last-primary'>
								<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class=''>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastPost->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
							</div>
							<div class='ipsData__last-secondary'>
								
IPSCONTENT;

$htmlsprintf = array($lastPost->author()->link( NULL, NULL, $lastPost->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $lastPost->submitted instanceof \IPS\DateTime ) ? $lastPost->submitted : \IPS\DateTime::ts( $lastPost->submitted );$return .= $val->html();
$return .= <<<IPSCONTENT

							</div>
						</div>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

	function featuredFile( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsData__item 
IPSCONTENT;

if ( $file->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<figure class='ipsData__image'>
		
IPSCONTENT;

if ( $file->primary_screenshot_thumb ):
$return .= <<<IPSCONTENT

			<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Attachment", $file->primary_screenshot_thumb )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</figure>
	<div class='ipsData__content'>
		<div class="ipsData__main">
			<div class='ipsData__title'>
				
IPSCONTENT;

if ( $file->unread() ):
$return .= <<<IPSCONTENT

					<span class='ipsIndicator' data-ipsTooltip title='
IPSCONTENT;

if ( $file->unread() === -1 ):
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

				<div class='ipsBadges'>
					
IPSCONTENT;

foreach ( $file->badges() as $badge ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !\in_array($badge->badgeType, ['ipsBadge--featured', 'ipsBadge--pinned']) ):
$return .= <<<IPSCONTENT

							{$badge}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
				<h3><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($file->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
			</div>
			<div class='ipsData__desc'>{$file->truncated(TRUE)}</div>
			
IPSCONTENT;

if ( $file->container()->bitoptions['reviews'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $file->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

				<span class="cFilePrice">
					
IPSCONTENT;

if ( $file->isPaid() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $price = $file->price() ):
$return .= <<<IPSCONTENT

							{$price}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_free', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<ul class='ipsButtons ipsButtons--start i-margin-block_2' hidden>
				
IPSCONTENT;

if ( ( $file->canDownload() or ( $file->container()->message('npd') and !$file->canBuy() ) ) && !$file->canBuy() ):
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;

if ( \IPS\Settings::i()->idm_antileech AND !$file->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('download')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('download'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary' 
IPSCONTENT;

if ( $file->requiresDownloadConfirmation() ):
$return .= <<<IPSCONTENT
data-ipsDialog
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class="fa-solid fa-download"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
					</li>
				
IPSCONTENT;

elseif ( $file->canBuy() ):
$return .= <<<IPSCONTENT

					<li>
						<a href='
IPSCONTENT;

if ( !$file->container()->message('disclaimer') OR !\in_array( $file->container()->disclaimer_location, [ 'purchase', 'both' ] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('buy')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('buy'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--secondary'><i class='fa-solid fa-cart-shopping'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'buy_now', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $price ):
$return .= <<<IPSCONTENT
 - 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( strip_tags($price), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		<div class="ipsData__extra">
			<ul class='ipsData__stats'>
				
IPSCONTENT;

if ( $file->isPaid() and !$file->nexus and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) and $file->purchaseCount() ):
$return .= <<<IPSCONTENT

					<li data-statType="purchases">
						<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $file->purchaseCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( (!$file->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) )) and $file->downloads ):
$return .= <<<IPSCONTENT

					<li data-statType="downloads">
						<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $file->downloads ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
		 
				
IPSCONTENT;

if ( $file->container()->bitoptions['comments'] and $file->comments ):
$return .= <<<IPSCONTENT

					<li data-statType="comments" hidden>
						<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $file->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
			<div class="ipsData__last">
				<div class='ipsData__last-text'>
					<div class='ipsData__last-primary'>
IPSCONTENT;

$htmlsprintf = array($file->author()->link( NULL, NULL, $file->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
					<div class='ipsData__last-secondary'>
IPSCONTENT;

if ( $file->updated == $file->submitted ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $file->submitted instanceof \IPS\DateTime ) ? $file->submitted : \IPS\DateTime::ts( $file->submitted );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $file->updated instanceof \IPS\DateTime ) ? $file->updated : \IPS\DateTime::ts( $file->updated );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
				</div>
			</div>
		</div>
	</div>

</li>
IPSCONTENT;

		return $return;
}

	function fileGrid( $file, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- v5 todo: This needs modernizing. Is it used? -->
<style>*{ color: #40d31a !important; }</style>
<div class="ipsInnerBox 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	
IPSCONTENT;

if ( $table AND method_exists( $table, 'canModerate' ) AND $table->canModerate() ):
$return .= <<<IPSCONTENT

	<div class='ipsData__mod'>
        <input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $file ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( $file->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class="ipsInput ipsInput--toggle">
	</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->thumbImage( $file->primary_screenshot_thumb, $file->name, 'medium', '', 'view_this', $file->url( 'getPrefComment' ), 'core_Attachment', '', true );
$return .= <<<IPSCONTENT

	<h4 class='ipsData__title ipsType_sectionHead'>
		
IPSCONTENT;

if ( $file->unread() ):
$return .= <<<IPSCONTENT

			<span class='ipsIndicator' data-ipsTooltip title='
IPSCONTENT;

if ( $file->unread() === -1 ):
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

foreach ( $file->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

if ( $file->prefix() ):
$return .= <<<IPSCONTENT

		<span>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $file->prefix( TRUE ), $file->prefix() );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<span class=''><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($file->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $file->canEdit() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $file->canEdit() ):
$return .= <<<IPSCONTENT
data-role="editableTitle"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></span>
	</h4>
	<p class='i-color_soft i-link-color_inherit'>
		
IPSCONTENT;

$htmlsprintf = array($file->author()->link( NULL, NULL, $file->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Widget\Request::i()->app != 'downloads' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>
	<div class='i-basis_220'>
		<p><strong>
IPSCONTENT;

if ( $file->updated == $file->submitted ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submitted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $file->submitted instanceof \IPS\DateTime ) ? $file->submitted : \IPS\DateTime::ts( $file->submitted );$return .= $val->html(FALSE);
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $file->updated instanceof \IPS\DateTime ) ? $file->updated : \IPS\DateTime::ts( $file->updated );$return .= $val->html(FALSE);
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</strong></p>
	</div>

	<!-- Rating -->
	
IPSCONTENT;

if ( $file->container()->bitoptions['reviews'] ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $file->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $file->memberReviewRating() );
$return .= <<<IPSCONTENT
&nbsp;&nbsp; <span class='i-color_soft'>(
IPSCONTENT;

$pluralize = array( $file->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<!-- Comments -->
	
IPSCONTENT;

if ( $file->container()->bitoptions['comments'] ):
$return .= <<<IPSCONTENT

	<p>
		
IPSCONTENT;

if ( $file->comments ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url()->setQueryString( 'tab', 'comments' )->setFragment('replies'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<i class='fa-solid fa-comment'></i> 
IPSCONTENT;

$pluralize = array( $file->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $file->comments ):
$return .= <<<IPSCONTENT

		</a>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<!-- Price -->
	
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

	<p class='i-font-size_2 i-text-align_center i-margin-top_2'>
                            <span class='cFilePrice'>
                                
IPSCONTENT;

if ( $file->isPaid() ):
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

if ( $price = $file->price() ):
$return .= <<<IPSCONTENT

                                        {$price}
                                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                                    
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_free', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

                                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                            </span>
	</p>
	<!-- Purchase Count -->
	<p>
		
IPSCONTENT;

if ( $file->isPaid() and !$file->nexus and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) ):
$return .= <<<IPSCONTENT

		<span 
IPSCONTENT;

if ( !$file->purchaseCount() ):
$return .= <<<IPSCONTENT
class='i-color_soft'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class='fa-solid fa-cart-shopping'></i> 
IPSCONTENT;

$pluralize = array( $file->purchaseCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>&nbsp;&nbsp;
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !$file->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) ):
$return .= <<<IPSCONTENT

		<span 
IPSCONTENT;

if ( !$file->downloads ):
$return .= <<<IPSCONTENT
class='i-color_soft'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class='fa-solid fa-circle-arrow-down'></i> 
IPSCONTENT;

$pluralize = array( $file->downloads ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>
	<!-- Tags -->
	
IPSCONTENT;

if ( \count( $file->tags() ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $file->tags(), TRUE );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function index( $featured, $new, $rated, $downloaded ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class="ipsPageHeader">
	<div class="ipsPageHeader__row">
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "header:before", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "header:inside-start", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "title:before", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "title:inside-start", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "title:inside-end", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "title:after", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "header:inside-end", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "header:after", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "buttons:before", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="buttons" class="ipsButtons ipsButtons--main">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "buttons:inside-start", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !\IPS\downloads\Category::theOnlyNode() ):
$return .= <<<IPSCONTENT

				<li class="ipsResponsive_showPhone">
					<a href="#elDownloadsCategories_menu" data-ipsmenu id="elDownloadsCategories" class="ipsButton ipsButton--inherit"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads_category_select', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryMenu( \IPS\downloads\Category::roots() );
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\downloads\Category::canOnAny('add') OR \IPS\downloads\Category::theOnlyNode() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\downloads\Category::theOnlyNode() and ( !\IPS\Settings::i()->club_nodes_in_apps or !\IPS\downloads\Category::clubNodes() ) ):
$return .= <<<IPSCONTENT

					<li class="ipsResponsive_hidePhone"><a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\downloads\Category::theOnlyNode()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryButtons( \IPS\downloads\Category::canOnAny('add'), NULL, FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "buttons:inside-end", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/browse/index", "buttons:after", [ $featured,$new,$rated,$downloaded ] );
$return .= <<<IPSCONTENT

	</div>
</header>

IPSCONTENT;

if ( !empty( $featured ) ):
$return .= <<<IPSCONTENT

	<section class="ipsBox ipsPull">
		<header class="ipsBox__header i-flex i-justify-content_space-between">
			<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'featured_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'downloads-featured-files' );
$return .= <<<IPSCONTENT

		</header>
		<div class="ipsBox__content" id="elDownloadsFeatured">
			<i-data>
				<ol class="ipsData ipsData--featured ipsData--carousel ipsData--downloads-featured-files" id="downloads-featured-files" tabindex="0">
					
IPSCONTENT;

foreach ( $featured as $file ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "downloads" )->featuredFile( $file );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
		</div>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->idm_show_newest AND \count( $new )  ):
$return .= <<<IPSCONTENT

	<section class="ipsBox ipsPull">
		<header class="ipsBox__header i-flex i-justify-content_space-between">
			<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'browse_whats_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'downloads-whats-new' );
$return .= <<<IPSCONTENT


		</header>
		<div class="ipsBox__content">
			<i-data>
				<ol class="ipsData ipsData--grid ipsData--carousel ipsData--downloads-whats-new" id="downloads-whats-new" tabindex="0">
					
IPSCONTENT;

foreach ( $new as $idx => $file ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->indexBlock( $file );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
		</div>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->idm_show_highest_rated AND \count( $rated ) ):
$return .= <<<IPSCONTENT

	<section class="ipsBox ipsPull">
		<header class="ipsBox__header i-flex i-justify-content_space-between">
			<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'browse_highest_rated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'downloads-highest-rated' );
$return .= <<<IPSCONTENT


		</header>
		<div class="ipsBox__content">
			<i-data>
				<ol class="ipsData ipsData--mini-grid ipsData--carousel ipsData--downloads-highest-rated" id="downloads-highest-rated" tabindex="0">
					
IPSCONTENT;

foreach ( $rated as $idx => $file ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->indexBlock( $file );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
		</div>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->idm_show_most_downloaded AND \count( $downloaded ) ):
$return .= <<<IPSCONTENT

	<section class="ipsBox ipsPull">
		<header class="ipsBox__header i-flex i-justify-content_space-between">
			<h2>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'browse_most_downloaded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( 'downloads-most-downloaded' );
$return .= <<<IPSCONTENT

		</header>
		<div class="ipsBox__content">
			<i-data>
				<ol class="ipsData ipsData--mini-grid ipsData--carousel ipsData--downloads-most-downloaded" id="downloads-most-downloaded" tabindex="0">
					
IPSCONTENT;

foreach ( $downloaded as $idx => $file ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->indexBlock( $file );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
		</div>
	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\downloads\Category::canOnAny('add') OR \IPS\downloads\Category::theOnlyNode() ):
$return .= <<<IPSCONTENT

	<ul class="ipsButtons ipsButtons--main ipsResponsive_showPhone">
		
IPSCONTENT;

if ( \IPS\downloads\Category::theOnlyNode() and ( !\IPS\Settings::i()->club_nodes_in_apps or !\IPS\downloads\Category::clubNodes() ) ):
$return .= <<<IPSCONTENT

			<li><a class="ipsButton ipsButton--inherit" href="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\downloads\Category::theOnlyNode()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_all_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoryButtons( \IPS\downloads\Category::canOnAny('add'), NULL, TRUE );
$return .= <<<IPSCONTENT

	</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function indexBlock( $file ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsData__item 
IPSCONTENT;

if ( $file->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<figure class='ipsData__image'>
		
IPSCONTENT;

if ( $file->primary_screenshot_thumb ):
$return .= <<<IPSCONTENT

			<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Attachment", $file->primary_screenshot_thumb )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</figure>
	<div class='ipsData__content'>
		<div class="ipsData__main">
			<div class='ipsData__title'>
				
IPSCONTENT;

if ( $file->unread() ):
$return .= <<<IPSCONTENT

					<span class='ipsIndicator' data-ipsTooltip title='
IPSCONTENT;

if ( $file->unread() === -1 ):
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

				<div class='ipsBadges'>
					
IPSCONTENT;

foreach ( $file->badges() as $badge ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( !\in_array($badge->badgeType, ['ipsBadge--pinned']) ):
$return .= <<<IPSCONTENT

							{$badge}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
				<h3><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($file->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h3>
				
IPSCONTENT;

if ( $file->container()->bitoptions['reviews'] and $file->averageReviewRating() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $file->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

				<span class="cFilePrice">
					
IPSCONTENT;

if ( $file->isPaid() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $price = $file->price() ):
$return .= <<<IPSCONTENT

							{$price}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_free', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsData__extra">
			<ul class='ipsData__stats'>
				
IPSCONTENT;

if ( $file->isPaid() and !$file->nexus and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) and $file->purchaseCount() ):
$return .= <<<IPSCONTENT

					<li data-statType="purchases">
						<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $file->purchaseCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;

$pluralize = array( $file->purchaseCount() ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( (!$file->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) )) and $file->downloads ):
$return .= <<<IPSCONTENT

					<li data-statType="downloads">
						<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $file->downloads ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'x_downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
		 
				
IPSCONTENT;

if ( $file->container()->bitoptions['comments'] and $file->comments ):
$return .= <<<IPSCONTENT

					<li data-statType="comments" hidden>
						<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $file->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
			<div class="ipsData__last">
				<div class='ipsData__last-text'>
					<div class='ipsData__last-primary'>
IPSCONTENT;

$htmlsprintf = array($file->author()->link( NULL, NULL, $file->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
					<div class='ipsData__last-secondary'>
IPSCONTENT;

if ( $file->updated == $file->submitted ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $file->submitted instanceof \IPS\DateTime ) ? $file->submitted : \IPS\DateTime::ts( $file->submitted );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $file->updated instanceof \IPS\DateTime ) ? $file->updated : \IPS\DateTime::ts( $file->updated );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</div>
				</div>
			</div>
		</div>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function indexSidebar( $canSubmitFiles, $currentCategory=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", \IPS\Request::i()->app )->categoriesSidebar( $currentCategory );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function noFiles( $category ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-text-align_center i-padding_3'>
	<p class='i-color_soft i-font-weight_600 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_files_in_cat', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

	
IPSCONTENT;

if ( $category->can('add') ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $category->club() ):
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=submit&do=submit&category={$category->id}", null, "downloads_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=submit&_new=1&category={$category->id}", null, "downloads_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_first_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsDialog-size='narrow'>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_first_file', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function rows( $table, $headers, $files ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \count( $files ) ):
$return .= <<<IPSCONTENT

    
IPSCONTENT;

if ( \IPS\Member::loggedIn()->getLayoutValue( 'downloads_categories' ) == 'grid' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $files as $file ):
$return .= <<<IPSCONTENT

			<li
				class='ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui( "css" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
  
IPSCONTENT;

if ( method_exists( $file, "tableClass" ) && $file->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $file->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
				data-rowid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
				
IPSCONTENT;

if ( $file->unread() ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( 'getPrefComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( 'getPrefComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsData__image'>
					
IPSCONTENT;

if ( $file->primary_screenshot_thumb ):
$return .= <<<IPSCONTENT

						<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Attachment", $file->primary_screenshot_thumb )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<i></i>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</a>
				<div class='ipsData__content'>
					<div class='ipsData__main'>
						
IPSCONTENT;

$price = NULL;
$return .= <<<IPSCONTENT

						<div class='ipsData__title'>
							
IPSCONTENT;

if ( $file->prefix() ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $file->prefix( TRUE ), $file->prefix() );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $file->unread() ):
$return .= <<<IPSCONTENT

								<span class='ipsIndicator' data-ipsTooltip title='
IPSCONTENT;

if ( $file->unread() === -1 ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($file->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $file->canEdit() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $file->canEdit() ):
$return .= <<<IPSCONTENT
data-role="editableTitle"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
							<div class='ipsBadges'>
								
IPSCONTENT;

foreach ( $file->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</div>
						</div>
						<div class='ipsData__desc ipsRichText ipsTruncate_2'>
							{$file->truncated()}
						</div>
						<div class="i-flex i-align-items_center i-column-gap_2 i-flex-wrap_wrap i-margin-top_1">
							
IPSCONTENT;

if ( $file->container()->bitoptions['reviews'] ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $file->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $file->memberReviewRating() );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \count( $file->tags() ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $file->tags(), TRUE );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
					</div>
					<div class='ipsData__extra'>
						<ul class='ipsData__stats'>
							
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

								<li data-stattype="price">
									
IPSCONTENT;

if ( $file->isPaid() ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $price = $file->price() ):
$return .= <<<IPSCONTENT

											<span class='ipsData__stats-label'>{$price}</span>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<span class='ipsData__stats-label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_free', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $file->isPaid() and !$file->nexus and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) and $file->purchaseCount() ):
$return .= <<<IPSCONTENT

								<li data-stattype="purchases">
									<span class='ipsData__stats-icon' data-stat-value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-hidden="true" data-ipstooltip title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'idm_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></span>
									<span class='ipsData__stats-label'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'idm_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !$file->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) and $file->downloads ):
$return .= <<<IPSCONTENT

								<li data-stattype="downloads">
									<span class='ipsData__stats-icon' data-stat-value='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
' aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></span>
									<span class='ipsData__stats-label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $file->container()->bitoptions['comments'] and $file->comments ):
$return .= <<<IPSCONTENT

								<li data-stattype="comments">
									<span class='ipsData__stats-icon' data-stat-value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-hidden="true" data-ipstooltip title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></span>
									<span class='ipsData__stats-label'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $table AND method_exists( $table, 'canModerate' ) AND $table->canModerate() ):
$return .= <<<IPSCONTENT

								<li>
									<input class='ipsInput ipsInput--toggle' type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $file ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( $file->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
						<div class='ipsData__last'>
IPSCONTENT;

$htmlsprintf = array($file->author()->link( NULL, NULL, $file->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
					</div>
				</div>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $files as $file ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "downloads" )->tableRow( $file, $table );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function tableRow( $file, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li
    class='ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui( "css" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
  
IPSCONTENT;

if ( method_exists( $file, "tableClass" ) && $file->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $file->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
    data-rowid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
    
IPSCONTENT;

if ( $file->unread() ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

>
	<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( 'getPrefComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsData__image'>
		
IPSCONTENT;

if ( $file->primary_screenshot_thumb ):
$return .= <<<IPSCONTENT

			<img src='
IPSCONTENT;

$return .= \IPS\File::get( "core_Attachment", $file->primary_screenshot_thumb )->url;
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</a>
	<div class='ipsData__content'>
		<div class='ipsData__main'>
			<div class='ipsData__title'>
				<div class='ipsBadges'>
					
IPSCONTENT;

foreach ( $file->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
				
IPSCONTENT;

if ( $file->prefix() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $file->prefix( TRUE ), $file->prefix() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $file->unread() ):
$return .= <<<IPSCONTENT

					<span class='ipsIndicator' data-ipsTooltip title='
IPSCONTENT;

if ( $file->unread() === -1 ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$sprintf = array($file->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $file->canEdit() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $file->canEdit() ):
$return .= <<<IPSCONTENT
data-role="editableTitle"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
			</div>
			<div class='ipsData__desc'>{$file->truncated()}</div>
			<div class="i-flex i-align-items_center i-gap_2 i-flex-wrap_wrap">
				
IPSCONTENT;

if ( $file->container()->bitoptions['reviews'] ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $file->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $file->memberReviewRating() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $file->tags() ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $file->tags(), TRUE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
		<div class="ipsData__extra">
			<ul class='ipsData__stats'>
				
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

					<li data-stattype="price">
						
IPSCONTENT;

if ( $file->isPaid() ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $price = $file->price() ):
$return .= <<<IPSCONTENT

								<span class='ipsData__stats-label'>{$price}</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span class='ipsData__stats-label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_free', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $file->isPaid() and !$file->nexus and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) and $file->purchaseCount() ):
$return .= <<<IPSCONTENT

					<li data-stattype="purchases">
						<span class='ipsData__stats-icon' data-stat-value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-hidden="true" data-ipstooltip title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'idm_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'idm_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( !$file->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) and $file->downloads ):
$return .= <<<IPSCONTENT

					<li data-stattype="downloads">
						<span class='ipsData__stats-icon' data-stat-value='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
' aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $file->container()->bitoptions['comments'] and $file->comments ):
$return .= <<<IPSCONTENT

					<li data-stattype="comments">
						<span class='ipsData__stats-icon' data-stat-value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-hidden="true" data-ipstooltip title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'></span>
						<span class='ipsData__stats-label'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
			<div class="ipsData__last">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $file->author(), 'fluid' );
$return .= <<<IPSCONTENT

				<div class="ipsData__last-text">
					<div class="ipsData__last-primary">
						
IPSCONTENT;

$htmlsprintf = array($file->author()->link( NULL, NULL, $file->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \IPS\Widget\Request::i()->app != 'downloads' ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'in', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class="ipsData__last-secondary">
						
IPSCONTENT;

if ( $file->updated == $file->submitted ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submitted', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $file->submitted instanceof \IPS\DateTime ) ? $file->submitted : \IPS\DateTime::ts( $file->submitted );$return .= $val->html(FALSE);
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updated', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = ( $file->updated instanceof \IPS\DateTime ) ? $file->updated : \IPS\DateTime::ts( $file->updated );$return .= $val->html(FALSE);
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		</div>
	</div>	
	
IPSCONTENT;

if ( $table AND method_exists( $table, 'canModerate' ) AND $table->canModerate() ):
$return .= <<<IPSCONTENT

		<div class='ipsData__mod'>
		    <input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->multimodActions( $file ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-state='
IPSCONTENT;

if ( $file->tableStates() ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->tableStates(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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

		return $return;
}}