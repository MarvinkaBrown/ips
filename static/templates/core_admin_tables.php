<?php
namespace IPS\Theme;
class class_core_admin_tables extends \IPS\Theme\Template
{	function content( $content ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-ipsTruncate>
	{$content}
</div>
IPSCONTENT;

		return $return;
}

	function rows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty( $rows ) ):
$return .= <<<IPSCONTENT

	<tr>
		<td colspan="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $headers ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<div class='i-padding_4 i-color_soft'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( ( isset( $table->rootButtons['add'] ) ) ):
$return .= <<<IPSCONTENT

					&nbsp;&nbsp;
					<a 
						
IPSCONTENT;

if ( isset( $table->rootButtons['add']['link'] ) ):
$return .= <<<IPSCONTENT
href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->rootButtons['add']['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						title='
IPSCONTENT;

$val = "{$table->rootButtons['add']['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
						class='ipsButton ipsButton--secondary ipsButton--small 
IPSCONTENT;

if ( isset( $table->rootButtons['add']['class'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->rootButtons['add']['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
						role="button"
						
IPSCONTENT;

if ( isset( $table->rootButtons['add']['data'] ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $table->rootButtons['add']['data'] as $k => $v ):
$return .= <<<IPSCONTENT

								data-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( isset( $table->rootButtons['add']['hotkey'] ) ):
$return .= <<<IPSCONTENT

							data-keyAction='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->rootButtons['add']['hotkey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					>
IPSCONTENT;

$val = "{$table->rootButtons['add']['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</td>
	</tr>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $rowId => $r ):
$return .= <<<IPSCONTENT

		<tr class='
IPSCONTENT;

if ( isset( $table->highlightRows[ $rowId ] ) ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->highlightRows[ $rowId ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-keyNavBlock 
IPSCONTENT;

if ( isset( $r['_buttons']['view'] ) ):
$return .= <<<IPSCONTENT
data-tableClickTarget="view"
IPSCONTENT;

elseif ( isset( $r['_buttons']['edit'] ) ):
$return .= <<<IPSCONTENT
data-tableClickTarget="edit"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

foreach ( $r as $k => $v ):
$return .= <<<IPSCONTENT

				<td class='
IPSCONTENT;

if ( $k === 'photo' ):
$return .= <<<IPSCONTENT
ipsTable_icon
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $k === ( $table->mainColumn ?: $table->quickSearch ) OR $k === 'o_invoice' ):
$return .= <<<IPSCONTENT
ipsTable_wrap
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $k === $table->mainColumn ):
$return .= <<<IPSCONTENT
ipsTable_primary
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $k === '_buttons' ):
$return .= <<<IPSCONTENT
ipsTable_controls
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( isset( $table->rowClasses[ $k ] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->rowClasses[ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $k !== $table->mainColumn && $k !== '_buttons' && $k !== 'photo' ):
$return .= <<<IPSCONTENT
data-title="
IPSCONTENT;

$val = "{$table->langPrefix}{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					
IPSCONTENT;

if ( $k === '_buttons' ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( $v );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						{$v}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</td>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</tr>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function table( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller="core.global.core.table">
	
IPSCONTENT;

if ( isset( $headers['_buttons'] ) ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->buttons( $table->rootButtons, '' );
endif;
$return .= <<<IPSCONTENT

	<div class="ipsBox">
		
IPSCONTENT;

if ( $quickSearch !== NULL or $table->advancedSearch or !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

			<div data-role="tableSortBar">

				<div class='ipsButtonBar ipsButtonBar--top'>
					<div data-role="tablePagination" class='ipsButtonBar__pagination 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

					</div>
					<!-- Filter buttons -->
					<div class='ipsButtonBar__end'>
						<ul class='ipsDataFilters'>
							
IPSCONTENT;

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

								<li>
									<a href='#elFilterMenu_menu' class='ipsDataFilters__button' data-role="tableFilterMenu" id='elFilterMenu' data-ipsMenu data-ipsMenu-activeClass='ipsDataFilters__button--active' data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
									<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elFilterMenu_menu'>
										<li data-ipsMenuValue='' class='ipsMenu_item 
IPSCONTENT;

if ( !array_key_exists( $table->filter, $table->filters ) ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

											<li class='ipsMenu_item 
IPSCONTENT;

if ( $k === $table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action="tableFilter" data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><a href=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<li>
								<a href='#elSortMenu_menu' class='ipsDataFilters__button' id='elSortMenu' data-ipsMenu data-ipsMenu-activeClass='ipsDataFilters__button--active' data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elSortMenu_menu'>
									
IPSCONTENT;

foreach ( $headers as $k => $header ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $header !== '_buttons' && !\in_array( $header, $table->noSort ) ):
$return .= <<<IPSCONTENT

											<li class='ipsMenu_item 
IPSCONTENT;

if ( $header == $table->sortBy ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $header, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
												
IPSCONTENT;

if ( $header == $table->sortBy and $table->sortDirection == 'desc' ):
$return .= <<<IPSCONTENT

													<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $header, 'sortdirection' => 'desc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
												
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

													<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $header, 'sortdirection' => 'asc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

														
IPSCONTENT;

$val = "{$table->langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
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
							</li>
							<li>
								<a href='#elOrderMenu_menu' class='ipsDataFilters__button' id='elOrderMenu' data-ipsMenu data-ipsMenu-activeClass='ipsDataFilters__button--active' data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elOrderMenu_menu'>
									<li class='ipsMenu_item 
IPSCONTENT;

if ( $table->sortDirection == 'asc' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='asc'>
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => 'asc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ascending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
									<li class='ipsMenu_item 
IPSCONTENT;

if ( $table->sortDirection == 'desc' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='desc'>
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => 'desc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'descending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								</ul>
							</li>
						</ul>
						
IPSCONTENT;

/*
$return .= <<<IPSCONTENT
<!-- <ul class='ipsDataFilters' hidden>
							<li data-action="tableFilter" data-filter="">
								<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection, 'filter' => '' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsDataFilters__button 
IPSCONTENT;

if ( !array_key_exists( $table->filter, $table->filters ) ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
							</li>
							
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

								<li data-action="tableFilter" data-filter="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsDataFilters__button 
IPSCONTENT;

if ( $k === $table->filter ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
						--> 
IPSCONTENT;

*/
$return .= <<<IPSCONTENT

						<!-- Search -->
						
IPSCONTENT;

if ( $quickSearch !== NULL or $table->advancedSearch ):
$return .= <<<IPSCONTENT

							<div class="acpTable_search">
								
IPSCONTENT;

if ( $quickSearch !== NULL ):
$return .= <<<IPSCONTENT

									<i class="fa-solid fa-magnifying-glass"></i>
									<input type='text' data-role='tableSearch' results placeholder="
IPSCONTENT;

if ( \is_string( $quickSearch ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $table->langPrefix . $quickSearch )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->quicksearch?:'', ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

									<a class='acpWidgetSearch' data-ipsTooltip aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-gear'></i></a>
								
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

endif;
$return .= <<<IPSCONTENT

		
		<div data-role="extraHtml">{$table->extraHtml}</div>
		<div class="ipsTableScroll">
			<table class='ipsTable ipsTable--collapse ipsTable_zebra 
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' data-role="table" data-ipsKeyNav data-ipsKeyNav-observe='e d return'>
				<thead>
					<tr class='i-background_3'>
						
IPSCONTENT;

foreach ( $headers as $k => $header ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $header !== '_buttons' ):
$return .= <<<IPSCONTENT

								<th class='
IPSCONTENT;

if ( !\in_array( $header, $table->noSort ) ):
$return .= <<<IPSCONTENT
ipsTable_sortable 
IPSCONTENT;

if ( $table->sortBy and $header == ( mb_strrpos( $table->sortBy, ',' ) !== FALSE ? trim( mb_substr( $table->sortBy, mb_strrpos( $table->sortBy, ',' ) + 1 ) ) : $table->sortBy ) ):
$return .= <<<IPSCONTENT
ipsTable_sortableActive ipsTable_sortable
IPSCONTENT;

if ( $table->sortDirection == 'asc' ):
$return .= <<<IPSCONTENT
Asc
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
Desc
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsTable_sortableAsc
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( array_key_exists( $header, $table->classes ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->classes[ $header ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $header, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !\in_array( $header, $table->noSort ) ):
$return .= <<<IPSCONTENT
data-action="tableSort" 
IPSCONTENT;

if ( $table->sortBy and $header == ( mb_strrpos( $table->sortBy, ',' ) !== FALSE ? trim( mb_substr( $table->sortBy, mb_strrpos( $table->sortBy, ',' ) + 1 ) ) : $table->sortBy ) ):
$return .= <<<IPSCONTENT
aria-sort="
IPSCONTENT;

if ( $table->sortDirection == 'asc' ):
$return .= <<<IPSCONTENT
ascending
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
descending
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $table->widths[ $header ] ) ):
$return .= <<<IPSCONTENT
style="width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->widths[ $header ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
									
IPSCONTENT;

if ( !\in_array( $header, $table->noSort ) ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $header == $table->sortBy and $table->sortDirection == 'desc' ):
$return .= <<<IPSCONTENT

											<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $header, 'sortdirection' => 'asc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $header, 'sortdirection' => 'desc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$val = "{$table->langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

												<i class="fa-solid fa-caret-up ipsTable_sortable__icon"></i>
											</a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$val = "{$table->langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</th>
							
IPSCONTENT;

elseif ( $header === '_buttons' ):
$return .= <<<IPSCONTENT

								<th>&nbsp;</th>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</tr>
				</thead>
				<tbody data-role="tableRows">
					
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

				</tbody>
			</table>
		</div>
		<div class='ipsButtonBar ipsButtonBar--bottom'>
			<div data-role="tablePagination" class='ipsButtonBar__pagination 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}}