<?php
namespace IPS\Theme;
class class_blog_front_view extends \IPS\Theme\Template
{	function blogHeader( $blog, $showCover=true ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $showCover ):
$return .= <<<IPSCONTENT

	<div id='elBlogHeader' class='ipsPageHeader ipsBox ipsPull'>
		{$blog->coverPhoto()}

		
IPSCONTENT;

if ( !( $blog->owner() instanceof \IPS\Member ) and \count( $blog->contributors() ) AND $showCover ):
$return .= <<<IPSCONTENT

			<div class='cBlogContributors ipsResponsive_hidePhone'>
				<h2 class='i-font-weight_600 i-color_soft i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'contributors_to_this', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<ul class='cBlogView_contributors ipsGrid i-basis_40 i-gap_2'>
					
IPSCONTENT;

foreach (  $blog->contributors() as $idx => $contributor ):
$return .= <<<IPSCONTENT

						<li class='i-position_relative'>
							<span data-ipsTooltip title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contributor['member']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $contributor['member'], 'fluid' );
$return .= <<<IPSCONTENT
<span class='ipsNotification'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $contributor['contributions'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							</span>
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

else:
$return .= <<<IPSCONTENT


	<div id='elBlogHeaderStats' class='ipsBox ipsPull ipsCoverPhotoMeta'>
		
		
IPSCONTENT;

if ( ( $blog->owner() instanceof \IPS\Member ) && \IPS\Widget\Request::i()->module == 'view' ):
$return .= <<<IPSCONTENT

			<div class='ipsCoverPhoto__avatar'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $blog->owner(), 'fluid' );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div class='ipsCoverPhoto__titles'>
			<div class='ipsCoverPhoto__title'>
				<h1>
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
				</h1>
				
IPSCONTENT;

if ( $blog->pinned ):
$return .= <<<IPSCONTENT

					<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pinned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-thumbtack'></i></span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsCoverPhoto__subTitle'>
				
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
		</div>
		<ul class='ipsCoverPhoto__stats'>
			<li>
				<h4 class='ipsCoverPhoto__statTitle'>
IPSCONTENT;

$pluralize = array( $blog->_items ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h4>
				<span class='ipsCoverPhoto__statValue'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $blog->_items );
$return .= <<<IPSCONTENT
</span>
			</li>
			<li>
				<h4 class='ipsCoverPhoto__statTitle'>
IPSCONTENT;

$pluralize = array( $blog->_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h4>
				<span class='ipsCoverPhoto__statValue'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $blog->_comments );
$return .= <<<IPSCONTENT
</span>
			</li>
            <li id='elBlogViews'>
                <h4 class='ipsCoverPhoto__statTitle'>
IPSCONTENT;

$pluralize = array( $blog->num_views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_views', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h4>
                
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') and \IPS\Member::loggedIn()->modPermission('can_view_moderation_log') ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$url = "app=cloud&module=analytics&controller=analytics&contentClass=" . get_class($blog) . "&contentId=" . $blog->id;
$return .= <<<IPSCONTENT

                    <span class='ipsCoverPhoto__statValue i-link-color_inherit'><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( $url, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'analytics', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'analytics', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $blog->num_views );
$return .= <<<IPSCONTENT
</a></span>
                
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                    <span class='ipsCoverPhoto__statValue'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $blog->num_views );
$return .= <<<IPSCONTENT
</span>
                
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</li>
		</ul>
		<div class='ipsCoverPhoto__buttons'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'blog', 'blog', $blog->_id, \IPS\blog\Entry::containerFollowerCount( $blog ) );
$return .= <<<IPSCONTENT

		</div>

	</div>


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function blogSidebar( $sidebar=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsWidget' id='elBlogSidebarBox'>
	<h2 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_sidebar_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='ipsWidget__content ipsWidget__padding'>
		<div class="ipsRichText">
			{$sidebar}
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function blogTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='ipsBox ipsPull' data-baseurl='
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
' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( $table->title ):
$return .= <<<IPSCONTENT

		<h2 class='ipsBox__header' hidden>
IPSCONTENT;

$val = "{$table->title}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $table->canModerate() OR (isset( $table->sortOptions ) and !empty( $table->sortOptions )) OR !empty( $table->filters ) OR $table->pages > 1 OR $table->advancedSearch ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsButtonBar__pagination" data-role="tablePagination">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end'>
				<ul class='ipsDataFilters'>
					
IPSCONTENT;

if ( ( isset( $table->sortOptions ) and !empty( $table->sortOptions ) ) OR $table->advancedSearch ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

if ( isset($table->sortOptions)  ):
$return .= <<<IPSCONTENT

								<a href="#elSortByMenu_menu" class='ipsDataFilters__button' id="elSortByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="nofollow" data-role="sortButton" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $col, 'sortdirection' => $table->getSortDirection( $col ) ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
							
IPSCONTENT;

elseif ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

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
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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
" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
							<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
								<li data-action="tableFilter" data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsMenu_item 
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => '', 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirections ) )->setPage('page',1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
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
			<div class='
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="tableRows">
				
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

			</div>
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

if ( $table->canModerate('future_publish') ):
$return .= <<<IPSCONTENT

						<option data-icon="arrow-circle-o-up" value='publish'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'publish', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

else:
$return .= <<<IPSCONTENT

		<div class='
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' id='elTable_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role="tableRows">
			
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--bottom">
			<div class="ipsButtonBar__pagination" data-role="tablePagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit, TRUE, $table->getPaginationKey() );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</section>
IPSCONTENT;

		return $return;
}

	function blogViewLarge( $entry, $table, $first = FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<article class='cBlogView_entry i-border-bottom_3 
IPSCONTENT;

if ( $entry->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller="core.front.core.lightboxedImages" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	<header class='i-background_2 i-border-bottom_3 i-padding_2'>
		<div class="ipsPhotoPanel">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $entry->author() );
$return .= <<<IPSCONTENT

			<div>
				<h2 class='ipsTitle ipsTitle--h4'>
					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->caption, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $entry->prefix() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $entry->prefix( TRUE ), $entry->prefix() );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='i-color_inherit' title="
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $entry->canEdit() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $entry->canEdit() ):
$return .= <<<IPSCONTENT
data-role="editableTitle"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

if ( $entry->unread() ):
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

if ( $entry->canEdit() ):
$return .= <<<IPSCONTENT
<span data-role="editableTitle">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
					<div class="ipsBadges">
IPSCONTENT;

foreach ( $entry->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
</div>
				</h2>
				<ul class='ipsList ipsList--sep i-color_soft i-link-color_inherit'>
					<li>
IPSCONTENT;

$htmlsprintf = array($entry->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</li>
					<li>
IPSCONTENT;

$val = ( $entry->date instanceof \IPS\DateTime ) ? $entry->date : \IPS\DateTime::ts( $entry->date );$return .= $val->html();
$return .= <<<IPSCONTENT
</li>
					
IPSCONTENT;

if ( $entry->category_id ):
$return .= <<<IPSCONTENT

						<li><span class="i-font-weight_500">{$entry->category()->link()}</span></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
	</header>
	<div class="i-padding_2">
		<section class='ipsRichText ipsTruncate_x' style='--line-clamp: 
IPSCONTENT;

if ( $first ):
$return .= <<<IPSCONTENT
20
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
7
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
			{$entry->content()}
		</section>
		
IPSCONTENT;

if ( \count( $entry->tags() ) ):
$return .= <<<IPSCONTENT

			<div class='i-margin-top_2'>		
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tags( $entry->tags() );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="i-flex i-flex-wrap_wrap i-align-items_center i-gap_2 i-margin-top_3">
			<div>
				<strong><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></strong>
			</div>
			<ul class='i-margin-start_auto ipsList ipsList--inline i-gap_3 i-row-gap_1'>
				<li class="i-margin-end_auto"></li>
				
IPSCONTENT;

if ( \IPS\Settings::i()->blog_enable_rating ):
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'small', $entry->averageRating(), 5, $entry->memberRating() );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li class='i-color_soft'>
IPSCONTENT;

$pluralize = array( $entry->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
				<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
#comments' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='i-color_hard i-font-weight_500'>
IPSCONTENT;

$pluralize = array( $entry->num_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

$idField = $entry::$databaseColumnId;
$return .= <<<IPSCONTENT

						<input class="ipsInput" type='checkbox' data-role='moderation' name="moderate[
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
'>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	</div>	
</article>
IPSCONTENT;

		return $return;
}

	function blogViewMedium( $entry, $table ) {
		$return = '';
		$return .= <<<IPSCONTENT


<!-- This template is used when viewing Profile > See my activity > Blog Entries -->
<article class='cBlogView_entry 
IPSCONTENT;

if ( $entry->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller="core.front.core.lightboxedImages" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	<header class='ipsPhotoPanel ipsPhotoPanel--mini'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $entry->author(), 'mini' );
$return .= <<<IPSCONTENT

		<div>
			<h2 class='ipsTitle ipsTitle--h5'>
			    <span>
IPSCONTENT;

foreach ( $entry->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

if ( $entry->prefix() ):
$return .= <<<IPSCONTENT

					<span>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $entry->prefix( TRUE ), $entry->prefix() );
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<span class=''>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $entry->canEdit() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $entry->canEdit() ):
$return .= <<<IPSCONTENT
data-role="editableTitle"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

if ( $entry->unread() ):
$return .= <<<IPSCONTENT

							<div class='ipsIndicator' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'unread_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</a>
				</span>
			</h2>
			<p class='i-color_soft i-link-color_inherit'>
				
IPSCONTENT;

if ( $entry->category_id ):
$return .= <<<IPSCONTENT
<span class="i-color_hard i-font-weight_600">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->category()->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

$htmlsprintf = array($entry->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $entry->date instanceof \IPS\DateTime ) ? $entry->date : \IPS\DateTime::ts( $entry->date );$return .= $val->html();
$return .= <<<IPSCONTENT

			</p>
		</div>
	</header>

	<section class='ipsRichText i-margin-block_2 ipsTruncate_4'>
		{$entry->truncated()}
	</section>

	<div class="i-flex i-align-items_center i-flex-wrap_wrap i-gap_2">
		<ul class='ipsList ipsList--inline i-flex_11'>
			
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT

				<li>
					
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
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<li><strong><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title="
IPSCONTENT;

$sprintf = array($entry->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more_about', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'read_more', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></strong></li>
			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
#comments' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='i-color_soft'>
IPSCONTENT;

$pluralize = array( $entry->num_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a></li>
			<li class='i-color_soft'>
IPSCONTENT;

$pluralize = array( $entry->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

if ( \IPS\Settings::i()->blog_enable_rating ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->rating( 'large', $entry->averageRating(), 5, $entry->memberRating() );
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</article>
IPSCONTENT;

		return $return;
}

	function categories( $currentCategory=NULL, $categories=array(), $blog=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $categories ) ):
$return .= <<<IPSCONTENT

	<section id='elBlogCategoriesBlock' class='ipsWidget ipsWidget--vertical'>
		<header class='ipsWidget__header'>
			<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			
IPSCONTENT;

if ( $blog->canEdit() ):
$return .= <<<IPSCONTENT

				<span class='ipsBox__header-secondary'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url()->setQueryString( array( 'do' => "manageCategories" ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsDialog data-ipsDialog-size='narrow'><i class='fa-solid fa-pencil'></i></a></span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</header>
		<div class='ipsSideMenu ipsSideMenu--truncate ipsCategoriesMenu'>
			<ul class='ipsSideMenu__list'>
				
IPSCONTENT;

foreach ( $categories as $category ):
$return .= <<<IPSCONTENT

					<li 
IPSCONTENT;

if ( $currentCategory and $currentCategory->id == $category->id ):
$return .= <<<IPSCONTENT
aria-current="page"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsSideMenu_item'><strong class='ipsSideMenu__text'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $category->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</strong></a>
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

	function comments( $entry ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class='ipsBox ipsBox--comments ipsPull'>
	<h2 class='ipsBox__header' data-role="comment_count">
IPSCONTENT;

$pluralize = array( $entry->num_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_comments_uc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='ipsBox__content'>
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
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='comments'>

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->featuredComments( $entry->featuredComments(), $entry->url()->setQueryString( 'recommended', 'comments' ) );
$return .= <<<IPSCONTENT


			
IPSCONTENT;

if ( $entry->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

				<div class='ipsButtonBar'>
					<div class='ipsButtonBar__pagination'>{$entry->commentPagination( array('tab') )}</div>
					<div class='ipsButtonBar__end'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimodHeader( $entry, '#comments' );
$return .= <<<IPSCONTENT
</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
			<div data-role='commentFeed' data-controller='core.front.core.moderation'>
			<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->url()->csrf()->setQueryString( 'do', 'multimodComment' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
				
IPSCONTENT;

if ( \count( $entry->comments( NULL, NULL, 'date', 'asc', NULL, NULL, NULL, NULL, FALSE, isset( \IPS\Widget\Request::i()->showDeleted ) ) ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$commentCount=0; $timeLastRead = $entry->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $entry->comments( NULL, NULL, 'date', 'asc', NULL, NULL, NULL, NULL, FALSE, isset( \IPS\Widget\Request::i()->showDeleted ) ) as $comment ):
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

							<a id="findComment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></a>
							<a id="comment-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"></a>
							{$comment->html()}
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
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

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $entry );
$return .= <<<IPSCONTENT

						</form>
			</div>
			
IPSCONTENT;

if ( $entry->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

				<div class='ipsButtonBar ipsButtonBar--bottom'>
					<div class='ipsButtonBar__pagination'>{$entry->commentPagination( array('tab') )}</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $entry->commentForm() || $entry->locked() || \IPS\Member::loggedin()->restrict_post || \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] || !\IPS\Member::loggedIn()->checkPostsPerDay() ):
$return .= <<<IPSCONTENT

				<div class='ipsComposeAreaWrapper' data-role='replyArea'>
					
IPSCONTENT;

if ( $entry->commentForm() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $entry->locked() ):
$return .= <<<IPSCONTENT

							<p class='ipsComposeArea_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_entry_locked_can_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						{$entry->commentForm()}
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $entry->locked() ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'blog_entry_locked_cannot_comment' );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

elseif ( \IPS\Member::loggedin()->restrict_post ):
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
</section>

IPSCONTENT;

		return $return;
}

	function coverPhotoOverlay( $blog ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsCoverPhotoMeta">
	
IPSCONTENT;

if ( ( $blog->owner() instanceof \IPS\Member ) && \IPS\Widget\Request::i()->module == 'view' ):
$return .= <<<IPSCONTENT

		<div class='ipsCoverPhoto__avatar'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $blog->owner(), 'fluid' );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
	<div class='ipsCoverPhoto__titles'>
		<div class='ipsCoverPhoto__title'>
			<h1>
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
			</h1>
			
IPSCONTENT;

if ( $blog->pinned ):
$return .= <<<IPSCONTENT

				<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pinned', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-thumbtack'></i></span>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class='ipsCoverPhoto__subTitle'>
			
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
	</div>
	<ul class='ipsCoverPhoto__stats'>
		<li>
			<h4 class='ipsCoverPhoto__statTitle'>
IPSCONTENT;

$pluralize = array( $blog->_items ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h4>
			<span class='ipsCoverPhoto__statValue'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $blog->_items );
$return .= <<<IPSCONTENT
</span>
		</li>
		<li>
			<h4 class='ipsCoverPhoto__statTitle'>
IPSCONTENT;

$pluralize = array( $blog->_comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h4>
			<span class='ipsCoverPhoto__statValue'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $blog->_comments );
$return .= <<<IPSCONTENT
</span>
		</li>
		<li>
			<h4 class='ipsCoverPhoto__statTitle'>
IPSCONTENT;

$pluralize = array( $blog->num_views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_views', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h4>
			<span class='ipsCoverPhoto__statValue'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $blog->num_views );
$return .= <<<IPSCONTENT
</span>
		</li>
	</ul>
	<div class='ipsCoverPhoto__buttons'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'blog', 'blog', $blog->_id, \IPS\blog\Entry::containerFollowerCount( $blog ) );
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function entry( $entry, $previous, $next ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $entry->container()->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $entry->container() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id="elClubContainer">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

/*
$return .= <<<IPSCONTENT
<!--
<header>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->blogHeader( $entry->container(), (!$club OR !\IPS\Settings::i()->clubs OR \IPS\Settings::i()->clubs_header != 'full') ? ( $entry->cover_photo ? false : true ) : FALSE );
$return .= <<<IPSCONTENT

</header>
-->
IPSCONTENT;

*/
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->contentItemMessages( $entry->getMessages(), $entry );
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/view/entry", "entry__blog:before", [ $entry,$previous,$next ] );
$return .= <<<IPSCONTENT
<article class="ipsBox ipsPull" data-ips-hook="entry__blog">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/view/entry", "entry__blog:inside-start", [ $entry,$previous,$next ] );
$return .= <<<IPSCONTENT

	<header class="ipsPageHeader">
		
IPSCONTENT;

if ( $entry->cover_photo ):
$return .= <<<IPSCONTENT

			<div class="ipsCoverPhoto" data-controller="core.global.core.coverPhoto">
				<div class="ipsCoverPhoto__container">
					<img src="
IPSCONTENT;

$return .= \IPS\File::get( "blog_Entries", $entry->cover_photo )->url;
$return .= <<<IPSCONTENT
" alt="" fetchpriority="high" decoding="sync" class="ipsCoverPhoto__image" data-action="toggleCoverPhoto">
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsPageHeader__row">
			<div class="ipsPageHeader__primary">
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/view/entry", "contentTitle__blog:before", [ $entry,$previous,$next ] );
$return .= <<<IPSCONTENT
<div class="ipsPageHeader__title" data-ips-hook="contentTitle__blog">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/view/entry", "contentTitle__blog:inside-start", [ $entry,$previous,$next ] );
$return .= <<<IPSCONTENT

					<div class="ipsBadges">
						
IPSCONTENT;

foreach ( $entry->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</div>
					<h1>
						
IPSCONTENT;

if ( $entry->canEdit() ):
$return .= <<<IPSCONTENT

							<span data-controller="core.front.core.moderation">
								
IPSCONTENT;

if ( $entry->locked() ):
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
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								</span>
							</span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<span>
IPSCONTENT;

if ( $entry->locked() ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-lock"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $entry->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

							</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</h1>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/view/entry", "contentTitle__blog:inside-end", [ $entry,$previous,$next ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/view/entry", "contentTitle__blog:after", [ $entry,$previous,$next ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Settings::i()->blog_enable_rating ):
$return .= <<<IPSCONTENT

					<div class="ipsPageHeader__rating">{$entry->rating()} <span class="i-color_soft">(
IPSCONTENT;

$pluralize = array( $entry->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span></div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \count( $entry->tags() ) OR ( $entry->canEdit() AND $entry::canTag() ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tagsWithPrefix( $entry->tags(), $entry->prefix(), FALSE, FALSE, ( $entry->canEdit() AND ( \count( $entry->tags() ) OR $entry::canTag() ) ) ? $entry->url() : NULL );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsButtons">
				
IPSCONTENT;

if ( \count( $entry->shareLinks() ) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $entry );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $entry );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'blog', 'entry', $entry->id, $entry->followersCount() );
$return .= <<<IPSCONTENT

			</div>
		</div>
		<div class="ipsPageHeader__row ipsPageHeader__row--footer">
			<div class="ipsPageHeader__primary">
				<div class="ipsPhotoPanel ipsPhotoPanel--inline">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $entry->author(), 'fluid' );
$return .= <<<IPSCONTENT

					<div class="ipsPhotoPanel__text">
						<p class="ipsPhotoPanel__primary">
							
IPSCONTENT;

if ( $entry->category_id ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $entry->date > time() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($entry->author()->link(),$entry->category()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'entry_future_posted_with_cat', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($entry->author()->link(),$entry->category()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'entry_posted_with_cat', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $entry->date > time() ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($entry->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'entry_future_posted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									
IPSCONTENT;

$htmlsprintf = array($entry->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'entry_posted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</p>
						<p class="ipsPhotoPanel__secondary">
IPSCONTENT;

$val = ( $entry->date instanceof \IPS\DateTime ) ? $entry->date : \IPS\DateTime::ts( $entry->date );$return .= $val->html();
$return .= <<<IPSCONTENT
 · 
IPSCONTENT;

$pluralize = array( $entry->views ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_views_with_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</p>
					</div>
				</div>
			</div>
		</div>
	</header>
	
	<section class="i-padding_3">
		
IPSCONTENT;

if ( $poll = $entry->getPoll() ):
$return .= <<<IPSCONTENT

			<div class="ipsInnerBox ipsInnerBox--inherit i-margin-bottom_3">
				{$poll}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
		<div class="ipsRichText ipsRichText--user" data-controller="core.front.core.lightboxedImages">{$entry->content()}</div>
		
		
IPSCONTENT;

if ( $entry->_album ):
$return .= <<<IPSCONTENT

			<hr class="ipsHr i-margin-block_3">
			<h3 class="ipsTitle ipsTitle--h5 i-margin-bottom_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'entry_images', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			{$entry->_album}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $entry->editLine() ):
$return .= <<<IPSCONTENT

			{$entry->editLine()}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</section>
	<div class="ipsEntry__footer">
		<menu class="ipsEntry__controls">
			<li>{$entry->menu()}</li>
		</menu>
		
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $entry, 'IPS\Content\Reactable' ) AND \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $entry );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/view/entry", "entry__blog:inside-end", [ $entry,$previous,$next ] );
$return .= <<<IPSCONTENT
</article>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "blog/front/view/entry", "entry__blog:after", [ $entry,$previous,$next ] );
$return .= <<<IPSCONTENT


<div class="ipsBox ipsBox--padding ipsPull ipsResponsive_showPhone">
	<div class="ipsPageActions">
		
IPSCONTENT;

if ( \count( $entry->shareLinks() ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $entry, 'verySmall', 'light' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

        	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $entry );
$return .= <<<IPSCONTENT

        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'blog', 'entry', $entry->id, $entry->followersCount() );
$return .= <<<IPSCONTENT

	</div>
</div>


IPSCONTENT;

if ( $previous or $next ):
$return .= <<<IPSCONTENT

	<nav class="ipsPager">
		<div class="ipsPager_prev">
			
IPSCONTENT;

if ( $previous ):
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $previous->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
					<span class="ipsPager_type">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'prev_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span class="ipsPager_title ipsTruncate_1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $previous->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</a>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $next ):
$return .= <<<IPSCONTENT

			<div class="ipsPager_next">
				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $next->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
					<span class="ipsPager_type">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					<span class="ipsPager_title ipsTruncate_1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $next->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</a>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</nav>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->comments( $entry );
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $entry->container()->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function manageCategories( $blog ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller="blog.front.view.manageCategories" data-blog-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<i-data>
		<ol class="ipsData ipsData--table ipsData--manage-categories cBlogCatManage" data-role="tableRows"></ol>
	</i-data>
</div>

IPSCONTENT;

		return $return;
}

	function noEntries( $container ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox__padding i-text-align_center'>
	<p class='i-font-size_2 i-color_soft'>
IPSCONTENT;

if ( \IPS\Widget\Request::i()->cat ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_entries_in_cat', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_entries', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=blog&module=blogs&controller=submit&id={$container->_id}", null, "blog_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( \IPS\Widget\Request::i()->cat ):
$return .= <<<IPSCONTENT
&cat=
IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->cat ) ? htmlspecialchars( \IPS\Widget\Request::i()->cat, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary i-margin-top_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function rows( $table, $headers, $entries ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $entries ) > 0 ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $table->sortBy == 'entry_last_update' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $entries as $idx => $entry ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $idx <= 3 ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->blogViewLarge( $entry, $table, $idx === 0 );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
		
IPSCONTENT;

if ( \count( $entries ) > 4 ):
$return .= <<<IPSCONTENT

			<div class='ipsGrid ipsGrid--blog-view-rows'>
				<div>
					
IPSCONTENT;

foreach ( $entries as $idx => $entry ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $idx > 3 && $idx % 2 == 0 ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->blogViewMedium( $entry, $table );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
				<div>
					
IPSCONTENT;

foreach ( $entries as $idx => $entry ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $idx > 3 && $idx % 2 != 0 ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->blogViewMedium( $entry, $table );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $entries as $idx => $entry ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->blogViewLarge( $entry, $table, FALSE );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->noEntries( $table->container() );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function rowsGrid( $table, $headers, $entries ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $entries ) > 0 ):
$return .= <<<IPSCONTENT

	<i-data>
		<section class="ipsData ipsData--grid ipsData--blog-entries">
			
IPSCONTENT;

foreach ( $entries as $id => $entry ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "browse", "blog", 'front' )->indexGridEntry( $entry, false, $table );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</section>
	</i-data>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->noEntries( $table->container() );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function rssImport( $form ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$form}
IPSCONTENT;

		return $return;
}

	function view( $blog, $table, $category=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $blog->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $blog );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id='elClubContainer'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "blog" )->blogHeader( $blog, (!$club OR !\IPS\Settings::i()->clubs OR \IPS\Settings::i()->clubs_header != 'full') );
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $blog->description ):
$return .= <<<IPSCONTENT

	<section class='ipsBox ipsBox--padding ipsPull'>
		<h3 class='ipsTitle ipsTitle--h5 ipsTitle--margin'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'about_this_blog', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $blog->description, array('ipsTruncate_4') );
$return .= <<<IPSCONTENT

	</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

$menu = $blog->menu();
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\blog\Entry::canCreate( \IPS\Member::loggedIn(), $blog ) or $menu->hasContent() or \count( \IPS\blog\Entry\Category::roots( NULL, NULL, array( 'entry_category_blog_id=?', $blog->id ) ) ) ):
$return .= <<<IPSCONTENT

	<ul class="ipsButtons ipsButtons--main">
		
IPSCONTENT;

if ( !$blog->club_id and $menu->hasContent() ):
$return .= <<<IPSCONTENT

			<li>
			    {$menu}
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

if ( \count( \IPS\blog\Entry\Category::roots( NULL, NULL, array( 'entry_category_blog_id=?', $blog->id ) ) ) ):
$return .= <<<IPSCONTENT

			<li>
				<a href='#elBlogCategory_menu' id='elBlogCategory' data-ipsMenu class='ipsButton ipsButton--text'>
					<span>
IPSCONTENT;

if ( $category ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array($category->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_category_viewing', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_category_select', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span><i class='ipsMenuCaret'></i>
				</a>
				<ul id='elBlogCategory_menu' class='ipsMenu ipsMenu_normal ipsMenu_selectable ipsHide'>
					<li class="ipsMenu_title">
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'categories', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $category ):
$return .= <<<IPSCONTENT
 (<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blog->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'blog_categories_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( \IPS\blog\Entry\Category::roots( NULL, NULL, array( 'entry_category_blog_id=?', $blog->id ) ) as $cat ):
$return .= <<<IPSCONTENT

							<li class='ipsMenu_item 
IPSCONTENT;

if ( $category and $category->id == $cat->id ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>{$cat->link()}</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</li>
				</ul>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \IPS\blog\Entry::canCreate( \IPS\Member::loggedIn(), $blog ) ):
$return .= <<<IPSCONTENT

			<li>
				
IPSCONTENT;

if ( $category ):
$return .= <<<IPSCONTENT

					<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=blog&module=blogs&controller=submit&id={$blog->id}&cat={$category->_id}", null, "blog_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel='nofollow noindex'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_blog_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<a class="ipsButton ipsButton--primary" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=blog&module=blogs&controller=submit&id={$blog->id}", null, "blog_submit", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel='nofollow noindex'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_blog_entry', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
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


{$table}

<div class='ipsBox ipsBox--padding ipsPull ipsResponsive_showPhone'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'blog', 'blog', $blog->_id, \IPS\blog\Entry::containerFollowerCount( $blog ) );
$return .= <<<IPSCONTENT

</div>


IPSCONTENT;

if ( $blog->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}