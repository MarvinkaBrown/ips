<?php
namespace IPS\Theme;
class class_core_front_search extends \IPS\Theme\Template
{	function filters( $baseUrl, $count, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL, $errorTabs=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$countFields = array( 'search_min_comments', 'search_min_replies', 'search_min_reviews', 'search_min_views');
$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm ipsForm--vertical ipsForm--filters" method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsForm id='elSearchFilters_content'>
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


	
IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--error i-margin-bottom_3">
			
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class="i-padding_3">
		<div class='i-flex i-align-items_center i-gap_2 cSearchMainBar'>
			<button type='submit' class='i-flex_00 cSearchPretendButton' tabindex='-1'><i class='fa-solid fa-magnifying-glass i-font-size_6'></i></button>
			<div class='i-flex_11'>
				<div class='cSearchWrapper i-position_relative'>
					<input type='text' id='elMainSearchInput' name='q' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->q, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'q', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' autofocus class='ipsInput ipsInput--text ipsInput--primary ipsInput--wide'>
					<div class='cSearchWrapper__button ipsResponsive_hidePhone'>
						<button type='submit' id='elSearchSubmit' class='ipsButton ipsButton--primary ipsButton--small 
IPSCONTENT;

if ( isset( $hiddenValues['__advanced'] ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action='searchAgain'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					</div>
				</div>
			</div>
		</div>
		<div class='i-flex i-align-items_center i-flex-wrap_wrap i-margin-top_2'>
			<div class='i-flex_11'>
				<div data-role="hints">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "search", \IPS\Request::i()->app, 'front' )->hints( $baseUrl, $count );
$return .= <<<IPSCONTENT
</div>
			</div>
			<p class='
IPSCONTENT;

if ( isset( $hiddenValues['__advanced'] ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action='showFilters'>
				<a href='#' class='ipsButton ipsButton--soft ipsButton--small'><i class='fa-solid fa-plus'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_more_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</p>
		</div>
	</div>

	<div data-role='searchFilters' class='
IPSCONTENT;

if ( !isset( $hiddenValues['__advanced'] ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) ) ):
$return .= <<<IPSCONTENT

		<i-tabs class='ipsTabs ipsTabs--stretch' id='ipsTabs_search' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_search_content'>
			<div role='tablist'>
				<button type="button" id="ipsTabs_search_searchContent" class="ipsTabs__tab" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_content_search_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" role="tab" aria-controls="ipsTabs_search_searchContent_panel" aria-selected="
IPSCONTENT;

if ( \IPS\Widget\Request::i()->type != 'core_members' ):
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_content_search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</button>
				<button type="button" id="ipsTabs_search_searchMembers" class="ipsTabs__tab" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_member_search_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" role="tab" aria-controls="ipsTabs_search_searchMembers_panel" aria-selected="
IPSCONTENT;

if ( \IPS\Widget\Request::i()->type == 'core_members' ):
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

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_member_search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</button>
			</div>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

		</i-tabs>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<section id='ipsTabs_search_content' class='ipsTabs__panels'>
			<div id='ipsTabs_search_searchContent_panel' class='ipsTabs__panel' role="tabpanel" aria-labelledby="ipsTabs_search_searchContent" data-tabType='content' 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->type == 'core_members' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				<div class='i-padding_4'>
					<div class=''>		
						<ul>
							
IPSCONTENT;

if ( \IPS\Settings::i()->tags_enabled ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( isset( $elements['search_tab_content']['tags'] ) ):
$return .= <<<IPSCONTENT

									<li class=' 
IPSCONTENT;

if ( !$elements['search_tab_content']['tags']->value ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsFieldRow--fullWidth' data-role='searchTags'>
										<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_by_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
										{$elements['search_tab_content']['tags']->html()}
										<span class='i-color_soft i-font-size_-2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tags_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( isset( $elements['search_tab_content']['eitherTermsOrTags'] ) ):
$return .= <<<IPSCONTENT

									<li class='i-margin-top_2 
IPSCONTENT;

if ( !$elements['search_tab_content']['tags']->value || !$elements['search_tab_all']['q']->value ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='searchTermsOrTags'>
										<ul class='ipsFieldRow__content ipsFieldRow__content--checkboxes'>
											<li>
												<input type='radio' name="eitherTermsOrTags" value="or" id='elRadio_eitherTermsOrTags_or' 
IPSCONTENT;

if ( $elements['search_tab_content']['eitherTermsOrTags']->value == 'or' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
												<label for='elRadio_eitherTermsOrTags_or'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'termsortags_or_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
											</li>
											<li>
												<input type='radio' name="eitherTermsOrTags" value="and" id='elRadio_eitherTermsOrTags_and' 
IPSCONTENT;

if ( $elements['search_tab_content']['eitherTermsOrTags']->value == 'and' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 class="ipsInput ipsInput--toggle">
												<label for='elRadio_eitherTermsOrTags_and'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'termsortags_and_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
											</li>
										</ul>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( isset( $elements['search_tab_content']['author'] ) ):
$return .= <<<IPSCONTENT

								<li class='i-margin-top_3 
IPSCONTENT;

if ( !$elements['search_tab_content']['author']->value ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsFieldRow--fullWidth' data-role='searchAuthors'>
									<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_by_author', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
									{$elements['search_tab_content']['author']->html()}
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
						
IPSCONTENT;

if ( isset( $elements['search_tab_content']['tags'] ) || isset( $elements['search_tab_content']['author'] ) ):
$return .= <<<IPSCONTENT

							<ul class="ipsButtons ipsButtons--start">
								
IPSCONTENT;

if ( \IPS\Settings::i()->tags_enabled and isset( $elements['search_tab_content']['tags'] ) && !$elements['search_tab_content']['tags']->value ):
$return .= <<<IPSCONTENT

									<li><a href="#" class="ipsButton ipsButton--small ipsButton--inherit" data-action="searchByTags" data-opens='searchTags,searchTermsOrTags'><i class="fa-solid fa-plus"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_by_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( isset( $elements['search_tab_content']['author'] ) && !$elements['search_tab_content']['author']->value ):
$return .= <<<IPSCONTENT

									<li><a href="#" class="ipsButton ipsButton--small ipsButton--inherit" data-action="searchByAuthors" data-opens='searchAuthors'><i class="fa-solid fa-plus"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_by_author', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>

					<div class='ipsColumns i-margin-top_3'>
						
IPSCONTENT;

if ( isset( $elements['search_tab_content']['type'] ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$type = $elements['search_tab_content']['type'];
$return .= <<<IPSCONTENT

							<div class='ipsColumns__secondary i-basis_340'>
								<div class="ipsSideMenu">
									<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'searchType', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
									<ul class="ipsSideMenu__list" data-role='searchApp' data-ipsSideMenu data-ipsSideMenu-type="radio" data-ipsSideMenu-responsive="false">
										
IPSCONTENT;

foreach ( $type->options['options'] as $k => $lang ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $k == 'core_members' ):
$return .= <<<IPSCONTENT

IPSCONTENT;

continue;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											<li>
												<span id='elSearchToggle_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsSideMenu_item 
IPSCONTENT;

if ( (string) $type->value == (string) $k ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
													<input type="radio" class="ipsSideMenu__toggle" name="type" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $type->value == (string) $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_type_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-control="toggle" data-toggles="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $type->options['toggles'][ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-toggle-visibleCheck='#elSearchToggle_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
													<label for='elRadio_type_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_type_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label' data-role='searchAppTitle'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
												</span>
											</li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
									
									
IPSCONTENT;

if ( isset( $elements['search_tab_nodes'] ) ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

foreach ( $elements['search_tab_nodes'] as $element ):
$return .= <<<IPSCONTENT

											<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $element->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-margin-top_3">
												<h3 class="ipsSideMenu__title">
IPSCONTENT;

$val = "{$element->label}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
												{$element->html()}
											</div>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
									
IPSCONTENT;

if ( isset( $elements['search_tab_content']['club'] ) ):
$return .= <<<IPSCONTENT

										<div id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['search_tab_content']['club']->htmlId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="i-margin-top_3">
											<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'club', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
											{$elements['search_tab_content']['club']->html()}
										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class='ipsColumns__primary'>
							<div data-role='searchFilters' id='elSearchFiltersMain'>
								<div class='ipsGrid ipsGrid--auto-fit i-basis_340 i-row-gap_4'>
									<div class="ipsSideMenu">
										<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'searchIn', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
										<ul class='ipsSideMenu__list' role="radiogroup" data-ipsSideMenu data-ipsSideMenu-type="radio" data-ipsSideMenu-responsive="false" data-filterType='searchIn'>
											
IPSCONTENT;

foreach ( $elements['search_tab_content']['search_in']->options['options'] as $k => $lang ):
$return .= <<<IPSCONTENT

												<li role="none">
													<span class='ipsSideMenu_item 
IPSCONTENT;

if ( (string) $elements['search_tab_content']['search_in']->value == (string) $k ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
														<input type="radio" class="ipsSideMenu__toggle" name="search_in" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['search_tab_content']['search_in']->value == (string) $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_searchIn_full_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
														<label for='elRadio_searchIn_full_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_searchIn_full_label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
													</span>
												</li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									</div>
									<div class="ipsSideMenu">
										<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'andOr', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
										<ul class='ipsSideMenu__list' role="radiogroup" data-ipsSideMenu data-ipsSideMenu-type="radio" data-ipsSideMenu-responsive="false" data-filterType='andOr'>
											
IPSCONTENT;

foreach ( $elements['search_tab_content']['search_and_or']->options['options'] as $k => $lang ):
$return .= <<<IPSCONTENT

												<li role="none">
													<span class='ipsSideMenu_item 
IPSCONTENT;

if ( (string) $elements['search_tab_content']['search_and_or']->value == (string) $k ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
														<input type="radio" class="ipsSideMenu__toggle" name="search_and_or" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['search_tab_content']['search_and_or']->value == (string) $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_andOr_full_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
														<label for='elRadio_andOr_full_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_andOr_full_label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
													</span>
												</li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									</div>
									
IPSCONTENT;

if ( isset( $elements['search_tab_content']['startDate'] ) ):
$return .= <<<IPSCONTENT

										<div class="ipsSideMenu">
											<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'startDate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
											<ul class="ipsSideMenu__list" role="radiogroup" data-ipsSideMenu data-ipsSideMenu-type="radio" data-ipsSideMenu-responsive="false" data-filterType='dateCreated'>
												
IPSCONTENT;

foreach ( $elements['search_tab_content']['startDate']->options['options'] as $k => $lang ):
$return .= <<<IPSCONTENT

													<li role="none">
														<span class='ipsSideMenu_item 
IPSCONTENT;

if ( (string) $elements['search_tab_content']['startDate']->value == (string) $k ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
															<input type="radio" class="ipsSideMenu__toggle" name="startDate" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['search_tab_content']['startDate']->value == (string) $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_startDate_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
															<label for='elRadio_startDate_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_startDate_label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
														</span>
													</li>
												
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

												<li class='cStreamForm_dates i-background_2 i-padding_2 
IPSCONTENT;

if ( $elements['search_tab_content']['startDate']->value !== 'custom' ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="dateForm">
													<div class='ipsFluid'>
														<div>
	                                                        
IPSCONTENT;

$startValue = $elements['search_tab_content']['startDateCustom']->value['start'];
$return .= <<<IPSCONTENT

															<h4 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
															<input type='date' class='ipsInput ipsInput--text' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['search_tab_content']['startDateCustom']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[start]' data-control='date' data-role='start' value='
IPSCONTENT;

if ( $startValue instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( date( 'Y-m-d', $startValue->getTimestamp() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
														</div>
														<div>
	                                                        
IPSCONTENT;

$endValue = $elements['search_tab_content']['startDateCustom']->value['end'];
$return .= <<<IPSCONTENT

															<h4 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'end', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
															<input type='date' class='ipsInput ipsInput--text' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['search_tab_content']['startDateCustom']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[end]' data-control='date' data-role='end' value='
IPSCONTENT;

if ( $endValue instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endValue->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	                                                    </div>
                                                    </div>
												</li>
											</ul>
										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( isset( $elements['search_tab_content']['updatedDate'] ) ):
$return .= <<<IPSCONTENT

										<div class="ipsSideMenu">
											<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'updatedDate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
											<ul class="ipsSideMenu__list" role="radiogroup" data-ipsSideMenu data-ipsSideMenu-type="radio" data-ipsSideMenu-responsive="false" data-filterType='dateUpdated'>
												
IPSCONTENT;

foreach ( $elements['search_tab_content']['updatedDate']->options['options'] as $k => $lang ):
$return .= <<<IPSCONTENT

													<li role="none">
														<span class='ipsSideMenu_item 
IPSCONTENT;

if ( (string) $elements['search_tab_content']['updatedDate']->value == (string) $k ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
															<input type="radio" class="ipsSideMenu__toggle" name="updatedDate" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['search_tab_content']['updatedDate']->value == (string) $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_updatedDate_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
															<label for='elRadio_updatedDate_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_updatedDate_label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
														</span>
													</li>
												
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

												<li class='cStreamForm_dates i-background_2 i-padding_2 
IPSCONTENT;

if ( $elements['search_tab_content']['updatedDate']->value !== 'custom' ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="dateForm">
													<div class='ipsFluid'>
														<div>
	                                                        
IPSCONTENT;

$startValue = $elements['search_tab_content']['updatedDateCustom']->value['start'];
$return .= <<<IPSCONTENT

															<h4 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
															<input type='date' class='ipsInput ipsInput--text' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['search_tab_content']['updatedDateCustom']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[start]' data-control='date' data-role='start' value='
IPSCONTENT;

if ( $startValue instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( date( 'Y-m-d', $startValue->getTimestamp() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
														</div>
														<div>
	                                                        
IPSCONTENT;

$endValue = $elements['search_tab_content']['updatedDateCustom']->value['end'];
$return .= <<<IPSCONTENT

															<h4 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'end', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
															<input type='date' class='ipsInput ipsInput--text' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['search_tab_content']['updatedDateCustom']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[end]' data-control='date' data-role='end' value='
IPSCONTENT;

if ( $endValue instanceof \IPS\DateTime ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( date( 'Y-m-d', $endValue->getTimestamp() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endValue, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
														</div>
													</div>
												</li>
											</ul>
										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>

								<hr class='ipsHr'>

								<h3 class="ipsSideMenu__title" id="elSearch_filter_by_number">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_filter_by_number', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<ul class="ipsButtons ipsButtons--start i-margin-top_2">
									
IPSCONTENT;

foreach ( $elements['search_tab_content'] as $inputName => $input ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( \in_array( $inputName, $countFields ) ):
$return .= <<<IPSCONTENT

											<li id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $inputName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
												<a href="#elSearch_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $inputName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="ipsButton ipsButton--small ipsButton--inherit" data-ipsMenu data-ipsMenu-appendTo='#elSearchFilters_content' data-ipsMenu-closeOnClick='false' id='elSearch_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $inputName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-role='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $inputName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_link'><span class='ipsBadge ipsBadge--style1 
IPSCONTENT;

if ( $input->value <= 0 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='fieldCount'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span> 
IPSCONTENT;

$val = "{$inputName}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
												<div class='ipsMenu ipsMenu_medium ipsFieldRow--fullWidth i-padding_3 ipsHide' id='elSearch_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $inputName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu'>
													<h4 class="ipsMinorTitle i-margin-bottom_2">
IPSCONTENT;

$val = "{$inputName}_title"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
													<div class='ipsFieldRow--fullWidth'>
														{$input->html()}
													</div>
												</div>
											</li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) ) ):
$return .= <<<IPSCONTENT

				<div id='ipsTabs_search_searchMembers_panel' class='ipsTabs__panel' role="tabpanel"  aria-labelledby="ipsTabs_search_searchMembers" 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->type != 'core_members' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-tabType='members'>
					
IPSCONTENT;

$exclude = array( 'joinedDate', 'joinedDateCustom', 'group');
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$totalCustomFields = \count( $elements['search_tab_member'] ) - \count( $exclude ); // Don't count joined, joined custom or group
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$perCol = ceil( $totalCustomFields / 2 );
$return .= <<<IPSCONTENT

					<div class='i-padding_4'>
						<span class='ipsJS_hide'>
							<input type="radio" name="type" value="core_members" 
IPSCONTENT;

if ( (string) $elements['search_tab_content']['type']->value == (string) 'core_members' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_type_core_members">
							<label for='elRadio_type_core_members' id='elField_type_core_members_label' data-role='searchAppTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'core_members_pl', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
						</span>
						<div class='ipsColumns ipsColumns--search-members'>
							<div class='ipsColumns__secondary i-basis_340'>
								
IPSCONTENT;

if ( isset( $elements['search_tab_member']['joinedDate'] ) ):
$return .= <<<IPSCONTENT

									<div class="ipsSideMenu">
										<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'joinedDate', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
										<ul class="ipsSideMenu__list" role="radiogroup" data-ipsSideMenu data-ipsSideMenu-type="radio" data-ipsSideMenu-responsive="false" data-filterType='joinedDate'>
											
IPSCONTENT;

foreach ( $elements['search_tab_member']['joinedDate']->options['options'] as $k => $lang ):
$return .= <<<IPSCONTENT

												<li role="none">
													<span class='ipsSideMenu_item 
IPSCONTENT;

if ( (string) $elements['search_tab_member']['joinedDate']->value == (string) $k ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
														<input type="radio" class="ipsSideMenu__toggle" name="joinedDate" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['search_tab_member']['joinedDate']->value == (string) $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_joinedDate_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
														<label for='elRadio_joinedDate_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_joinedDate_label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
													</span>
												</li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

											<li class='cStreamForm_dates i-background_2 i-padding_2 
IPSCONTENT;

if ( $elements['search_tab_member']['joinedDate']->value !== 'custom' ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="dateForm">
												<div class='ipsFluid'>
													<div>
														<h4 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
														<input type='date' class='ipsInput ipsInput--text' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['search_tab_member']['joinedDateCustom']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[start]' data-control='date' data-role='start' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['search_tab_member']['joinedDateCustom']->value['start'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>	
													</div>
													<div>
														<h4 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'end', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
														<input type='date' class='ipsInput ipsInput--text' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['search_tab_member']['joinedDateCustom']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[end]' data-control='date' data-role='end' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['search_tab_member']['joinedDateCustom']->value['end'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
													</div>
												</div>
											</li>
										</ul>
									</div>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
							<div class='ipsColumns__primary' data-role='searchFilters' id='elSearchFiltersMembers'>
								<div class='ipsGrid ipsGrid--auto-fit i-basis_340 i-row-gap_4'>
									<div>
										
IPSCONTENT;

if ( isset( $elements['search_tab_member']['group'] ) ):
$return .= <<<IPSCONTENT

											<div class="ipsSideMenu">
												<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'group', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
												<ul class="ipsSideMenu__list" data-ipsSideMenu data-ipsSideMenu-type="check" data-ipsSideMenu-responsive="false" data-filterType='group'>
													
IPSCONTENT;

foreach ( $elements['search_tab_member']['group']->options['options'] as $k => $group ):
$return .= <<<IPSCONTENT

														<li>
															<span class='ipsSideMenu_item 
IPSCONTENT;

if ( \is_array( $elements['search_tab_member']['group']->value ) AND \in_array( $k, $elements['search_tab_member']['group']->value ) ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
																<input type="checkbox" class="ipsSideMenu__toggle" name="group" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \is_array( $elements['search_tab_member']['group']->value ) AND \in_array( $k, $elements['search_tab_member']['group']->value ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheck_group_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
																<label for='elCheck_group_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_group_label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>{$group}</label>
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

	
										
IPSCONTENT;

$countOne = 0;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $totalCustomFields > 1 ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

foreach ( $elements['search_tab_member'] as $id => $element ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

if ( \in_array( $id, $exclude ) ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

continue;
$return .= <<<IPSCONTENT

												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$countOne++;
$return .= <<<IPSCONTENT

	
												<hr class='ipsHr'>
												<h3 class="ipsSideMenu__title">
IPSCONTENT;

$val = "{$id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
												<div class='ipsFieldRow--fullWidth'>
													{$element->html()}
												</div>
												
												
IPSCONTENT;

if ( $countOne >= $perCol ):
$return .= <<<IPSCONTENT

													
IPSCONTENT;

break;
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

									</div>
									<div>
										
IPSCONTENT;

$countTwo = 0;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

$realCount = 0;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

foreach ( $elements['search_tab_member'] as $id => $element ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( \in_array( $id, $exclude ) ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

continue;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$countTwo++;
$return .= <<<IPSCONTENT

	
											
IPSCONTENT;

if ( $countTwo <= $countOne ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

continue;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


											
IPSCONTENT;

if ( $countTwo !== ( $countOne + 1 ) ):
$return .= <<<IPSCONTENT

												<!-- HR except for first item -->
												<hr class='ipsHr'>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


											<h3 class="ipsSideMenu__title">
IPSCONTENT;

$val = "{$id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
											<div class='ipsFieldRow--fullWidth'>
												{$element->html()}
											</div>
											
IPSCONTENT;

$realCount++;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $realCount >= $perCol ):
$return .= <<<IPSCONTENT

												
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</div>			
								</div>			
							</div>
						</div>
					</div>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</section>
		<div class='ipsSubmitRow cSearchFiltersSubmit'>
			<ul class='ipsButtons'>
				<li>
					<button type="button" class="ipsButton ipsButton--text 
IPSCONTENT;

if ( isset( $hiddenValues['__advanced'] ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="cancelFilters">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
				</li>
				<li>
					<button type="submit" class="ipsButton ipsButton--primary" data-action="updateResults">
						
IPSCONTENT;

if ( \IPS\Widget\Request::i()->type == 'core_members' ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</button>
				</li>
			</ul>
		</div>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function globalSearchMenuOptions( $exclude ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

foreach ( \IPS\Output::i()->globalSearchMenuOptions() as $type => $name ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( !\in_array( $type, $exclude ) ):
$return .= <<<IPSCONTENT

		<li>
			<span class='ipsSideMenu_item' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
				<input type="radio" name="type" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" id="elQuickSearchRadio_type_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				<label for='elQuickSearchRadio_type_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elQuickSearchRadio_type_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>
IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
			</span>
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

	function hints( $url, $count ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->q ) and \IPS\Widget\Request::i()->q AND ( !isset( \IPS\Widget\Request::i()->type ) OR \IPS\Widget\Request::i()->type != 'core_members' ) ):
$return .= <<<IPSCONTENT


IPSCONTENT;

$words = \IPS\Content\Search\Query::termAsWordsArray( \IPS\Widget\Request::i()->q, FALSE, 0 );
$return .= <<<IPSCONTENT


IPSCONTENT;

$noPhraseWords = \IPS\Content\Search\Query::termAsWordsArray( \IPS\Widget\Request::i()->q, TRUE );
$return .= <<<IPSCONTENT

	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_better_results_hint', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<ul class='ipsList ipsList--inline'>
		
IPSCONTENT;

if ( ! \IPS\Content\Search\Query::termIsPhrase( \IPS\Widget\Request::i()->q ) and \count( $words ) > 1 ):
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('q', '"' . \IPS\Request::i()->q . '"'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array(\IPS\Request::i()->q); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_hint_phrase', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

elseif ( \IPS\Content\Search\Query::termIsPhrase( \IPS\Widget\Request::i()->q ) and \count( $noPhraseWords ) > 1 ):
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( array( 'q' => implode( ' ', $noPhraseWords ), 'search_and_or' => 'or' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ' . \IPS\Member::loggedIn()->language()->addToStack('search_join_or') . ' ', $noPhraseWords ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $words ) > 1 ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( ( ( !isset( \IPS\Widget\Request::i()->search_and_or ) and \IPS\Settings::i()->search_default_operator === 'and' ) or \IPS\Widget\Request::i()->search_and_or == 'and' ) ):
$return .= <<<IPSCONTENT

				<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'search_and_or', 'or'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ' . \IPS\Member::loggedIn()->language()->addToStack('search_join_or') . ' ', $words ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
			
IPSCONTENT;

elseif ( ( ( !isset( \IPS\Widget\Request::i()->search_and_or ) and \IPS\Settings::i()->search_default_operator === 'or' ) or \IPS\Widget\Request::i()->search_and_or == 'or' ) ):
$return .= <<<IPSCONTENT

				<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'search_and_or', 'and'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ' . \IPS\Member::loggedIn()->language()->addToStack('search_join_and') . ' ', $words ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $count > 1 and ( ! isset( \IPS\Widget\Request::i()->sortby ) or \IPS\Widget\Request::i()->sortby != 'newest' ) ):
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('sortby', 'newest'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_newer_first', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

elseif ( \IPS\Content\Search\Query::init()->canUseRelevancy() and $count > 1 and ( isset( \IPS\Widget\Request::i()->sortby ) and \IPS\Widget\Request::i()->sortby == 'newest' ) ):
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('sortby', 'relevancy'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_most_pertinent', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->search_in ) and \IPS\Widget\Request::i()->search_in == 'titles' ):
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('search_in', 'all'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array(\IPS\Request::i()->q); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_titles_and_content', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

elseif ( $count > 1 and ( ! isset( \IPS\Widget\Request::i()->search_in ) or \IPS\Widget\Request::i()->search_in != 'titles' ) ):
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('search_in', 'titles'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array(\IPS\Request::i()->q); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_titles_only', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->updated_after ) and \IPS\Widget\Request::i()->updated_after != 'any' ):
$return .= <<<IPSCONTENT

			<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->stripQueryString( array('updated_after' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$sprintf = array(\IPS\Request::i()->q); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_all_dates', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
		
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

	function member( $member ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsStreamItem ipsStreamItem_contentBlock ipsStreamItem_member i-padding_3 i-text-align_center">
	<div class='ipsStreamItem__mainCell'>
		<div class='i-text-align_center'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $member, 'medium' );
$return .= <<<IPSCONTENT
</div>
		<div class='ipsStreamItem__header i-margin-top_2'>
			<h2 class='ipsStreamItem__title' data-searchable>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userLink( $member );
$return .= <<<IPSCONTENT

			</h2>
			<p>{$member->groupName}</p>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputationBadge( $member );
$return .= <<<IPSCONTENT

		</div>

		<ul class='ipsFluid i-text-align_center i-border-top_3 i-border-bottom_3 i-margin-block_2 i-padding-block_2'>
			<li>
				<h3 class='i-font-size_-1 i-font-weight_500'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'joined', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<p class="i-color_hard">
IPSCONTENT;

$val = ( $member->joined instanceof \IPS\DateTime ) ? $member->joined : \IPS\DateTime::ts( $member->joined );$return .= $val->html();
$return .= <<<IPSCONTENT
</p>
			</li>
			<li>
				<h3 class='i-font-size_-1 i-font-weight_500'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_member_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<p class="i-color_hard">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $member->member_posts );
$return .= <<<IPSCONTENT
</p>
			</li>
		</ul>

		<ul class='ipsButtons'>
			<li>
				<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=content&id={$member->member_id}", "front", "profile_content", array( $member->members_seo_name ), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'find_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</li>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id != $member->member_id and ( !$member->members_bitoptions['pp_setting_moderate_followers'] or \IPS\Member::loggedIn()->following( 'core', 'member', $member->member_id ) ) ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "profile", "core" )->memberFollow( 'core', 'member', $member->member_id, $member->followersCount(), TRUE );
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	</div>
</li>
IPSCONTENT;

		return $return;
}

	function memberFilters( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$exclude = array( 'q', 'joinedDate', 'joinedDateCustom', 'group');
$return .= <<<IPSCONTENT


IPSCONTENT;

$totalCustomFields = \count( $elements[''] ) - \count( $exclude ); // Don't count q, joined, joined custom or group
$return .= <<<IPSCONTENT


IPSCONTENT;

$perCol = ceil( $totalCustomFields / 2 );
$return .= <<<IPSCONTENT



IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm ipsForm--vertical ipsForm--member-filters" method='post' action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsForm id='elSearchFilters_content'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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


	<div class='i-padding_4'>
		<!--<div class='i-background_2 i-padding_3 i-margin-block_2 ipsPhotoPanel ipsPhotoPanel--mini'>
			<i class='fa-solid fa-user i-font-size_6'></i>
			<div>
				<ul>
					
IPSCONTENT;

if ( isset( $elements['']['q'] ) ):
$return .= <<<IPSCONTENT

						<li class='i-margin-bottom_2'>
							<h3 class="ipsSideMenu__title">Search By Member Name</h3>
							<input type='text' name='q' value='
IPSCONTENT;

if ( \is_array( $elements['']['q']->value ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ',', $elements['']['q']->value ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['q']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsInput--primary ipsInput--wide'>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</ul>
			</div>
		</div>
		<hr class='ipsHr i-margin-bottom_4'>-->

		<div class='ipsSpanGrid'>
			<div class='ipsSpanGrid__4'>
				
IPSCONTENT;

if ( isset( $elements['']['joinedDate'] ) ):
$return .= <<<IPSCONTENT

					<h3 class="ipsSideMenu__title">Joined</h3>
					<ul class="ipsSideMenu__list" role="radiogroup" data-ipsSideMenu data-ipsSideMenu-type="radio" data-ipsSideMenu-responsive="false" data-filterType='joinedDate'>
						
IPSCONTENT;

foreach ( $elements['']['joinedDate']->options['options'] as $k => $lang ):
$return .= <<<IPSCONTENT

							<li>
								<a href='#' class='ipsSideMenu_item 
IPSCONTENT;

if ( (string) $elements['']['joinedDate']->value == (string) $k ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
									<input type="radio" class="ipsSideMenu__toggle" name="joinedDate" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['']['joinedDate']->value == (string) $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_joinedDate_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									<label for='elRadio_joinedDate_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_joinedDate_label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
								</a>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						<li class='ipsSpanGrid cStreamForm_dates i-background_2 i-padding_2 
IPSCONTENT;

if ( $elements['']['joinedDate']->value !== 'custom' ):
$return .= <<<IPSCONTENT
ipsJS_hide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="dateForm">
							<div class='ipsSpanGrid__6'>
								<h4 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
								<input type='date' class='ipsInput ipsInput--text' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['joinedDateCustom']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[start]' data-control='date' data-role='start' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['joinedDateCustom']->value['start'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>	
							</div>
							<div class='ipsSpanGrid__6'>
								<h4 class='ipsMinorTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'end', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
								<input type='date' class='ipsInput ipsInput--text' name='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['joinedDateCustom']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[end]' data-control='date' data-role='end' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['joinedDateCustom']->value['end'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							</div>
						</li>
					</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsSpanGrid__8' data-role='searchFilters' id='elSearchFiltersMembers'>
				<div class='ipsSpanGrid'>
					<div class='ipsSpanGrid__6'>
						
IPSCONTENT;

if ( isset( $elements['']['group'] ) ):
$return .= <<<IPSCONTENT

							<h3 class="ipsSideMenu__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'group', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<ul class="ipsSideMenu__list" data-ipsSideMenu data-ipsSideMenu-type="check" data-ipsSideMenu-responsive="false" data-filterType='group'>
								
IPSCONTENT;

foreach ( $elements['']['group']->options['options'] as $k => $group ):
$return .= <<<IPSCONTENT

									<li>
										<a href='#' class='ipsSideMenu_item 
IPSCONTENT;

if ( \is_array( $elements['']['group']->value ) AND \in_array( $k, $elements['']['group']->value ) ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
											<input type="checkbox" class="ipsSideMenu__toggle" name="group" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( \is_array( $elements['']['group']->value ) AND \in_array( $k, $elements['']['group']->value ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elCheck_group_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
											<label for='elCheck_group_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_group_label_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>{$group}</label>
										</a>
									</li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</ul>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


						
IPSCONTENT;

$countOne = 0;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $totalCustomFields > 1 ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $elements[''] as $id => $element ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \in_array( $id, $exclude ) ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

continue;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

$countOne++;
$return .= <<<IPSCONTENT


								<hr class='ipsHr'>
								<h3 class="ipsSideMenu__title">
IPSCONTENT;

$val = "{$id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
								<div class='ipsFieldRow--fullWidth'>
									{$element->html()}
								</div>
								
								
IPSCONTENT;

if ( $countOne >= $perCol ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

break;
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

					</div>
					<div class='ipsSpanGrid__6'>
						
IPSCONTENT;

$countTwo = 0;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $elements[''] as $id => $element ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( \in_array( $id, $exclude ) ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

continue;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$countTwo++;
$return .= <<<IPSCONTENT


							
IPSCONTENT;

if ( $countTwo <= $countOne ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

continue;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


							
IPSCONTENT;

if ( $countTwo !== ( $countOne + 1 ) ):
$return .= <<<IPSCONTENT

								<!-- HR except for first item -->
								<hr class='ipsHr'>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


							<h3 class="ipsSideMenu__title">
IPSCONTENT;

$val = "{$id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<div class='ipsFieldRow--fullWidth'>
								{$element->html()}
							</div>
							
							
IPSCONTENT;

if ( $countTwo >= $perCol ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

break;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</div>			
				</div>			
			</div>
		</div>
	</div>
	<div class='ipsSubmitRow cSearchFiltersSubmit'>
		<ul class='ipsButtons'>
			<li>
				<button type="button" class="ipsButton ipsButton--text" data-action="cancelFilters">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cancel', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</li>
			<li>
				<button type="submit" class="ipsButton ipsButton--primary" data-action="updateResults">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'update_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</li>
		</ul>
	</div>

IPSCONTENT;

if ( !\IPS\Widget\Request::i()->isAjax() ):
$return .= <<<IPSCONTENT
	
</form>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function results( $termJSON, $title, $results, $pagination, $baseUrl, $count=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='core.front.search.results' data-term='{$termJSON}' data-role="resultsArea">
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "search", \IPS\Request::i()->app )->resultStream( $results, $pagination, $baseUrl, NULL, $count );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function resultStream( $results, $pagination, $baseUrl, $hideSort=FALSE, $count = 0 ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox ipsPull'>
	<p class='ipsBox__header'>
IPSCONTENT;

$pluralize = array( $count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_found', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

if ( $pagination OR !$hideSort ):
$return .= <<<IPSCONTENT

		<div class='ipsButtonBar ipsButtonBar--top'>
			
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

				<div class='ipsButtonBar__pagination'>{$pagination}</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( !$hideSort ):
$return .= <<<IPSCONTENT

				<div class='ipsButtonBar__end'>
					<ul class="ipsDataFilters">
						<li>
						
IPSCONTENT;

if ( \IPS\Widget\Request::i()->type == 'core_members' or \IPS\Content\Search\Query::init()->canUseRelevancy() ):
$return .= <<<IPSCONTENT

							<a href="#elSortByMenu_menu" class='ipsDataFilters__button' id="elSortByMenu_search_results" data-role="sortButton" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active" data-ipsMenu-selectable="radio"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide" id="elSortByMenu_search_results_menu">
								
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->type ) OR \IPS\Widget\Request::i()->type != 'core_members' ):
$return .= <<<IPSCONTENT

								    
IPSCONTENT;

if ( \IPS\Content\Search\Query::init()->canUseRelevancy() ):
$return .= <<<IPSCONTENT

                                        <li class="ipsMenu_item
IPSCONTENT;

if ( \IPS\Widget\Request::i()->sortby == 'newest' ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-sortDirection='desc'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortby' => 'newest' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'date', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
                                        <li class="ipsMenu_item
IPSCONTENT;

if ( \IPS\Widget\Request::i()->sortby == 'relevancy' ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-sortDirection='desc'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortby' => 'relevancy' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_relevancy', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<li class="ipsMenu_item
IPSCONTENT;

if ( \IPS\Widget\Request::i()->sortby == 'joined' ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-sortDirection='desc'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortby' => 'joined', 'sortdirection' => 'desc' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_joined', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li class="ipsMenu_item
IPSCONTENT;

if ( \IPS\Widget\Request::i()->sortby == 'name' || !isset( \IPS\Widget\Request::i()->sortby ) ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-sortDirection='asc'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortby' => 'name', 'sortdirection' => 'asc' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_mname', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li class="ipsMenu_item
IPSCONTENT;

if ( \IPS\Widget\Request::i()->sortby == 'member_posts' ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-sortDirection='desc'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortby' => 'member_posts', 'sortdirection' => 'desc' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li class="ipsMenu_item
IPSCONTENT;

if ( \IPS\Widget\Request::i()->sortby == 'pp_reputation_points' ):
$return .= <<<IPSCONTENT
 ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-sortDirection='desc'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortby' => 'pp_reputation_points', 'sortdirection' => 'desc' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_reputation', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

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

	
IPSCONTENT;

if ( \count( $results )  ):
$return .= <<<IPSCONTENT

		<ol class="ipsStream 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->type == 'core_members' ):
$return .= <<<IPSCONTENT
cStream_members ipsGrid ipsGrid--search-result-stream i-basis_260
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role='resultsContents'>
			
IPSCONTENT;

foreach ( $results as $result ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Widget\Request::i()->type == 'core_members' ):
$return .= <<<IPSCONTENT

					{$result->searchResultHtml()}
				
IPSCONTENT;

elseif ( $result !== NULL ):
$return .= <<<IPSCONTENT

					{$result->html()}
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class='ipsBox__padding i-text-align_center i-font-size_-2 i-color_soft' data-role='resultsContents'>
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_search_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $pagination ):
$return .= <<<IPSCONTENT

		<div class='ipsButtonBar ipsButtonBar--bottom'>
			<div class='ipsButtonBar__pagination'>{$pagination}</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function search( $termArray, $title, $results, $pagination, $baseUrl, $types, $filters, $count=NULL, $advanced=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='core.front.search.main' data-baseURL='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=search&controller=search", null, "search", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
	<header class='ipsPageHeader ipsPageHeader--search ipsResponsive_hidePhone'>
		<h1 class='ipsPageHeader__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_the_community', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
		<p class='ipsPageHeader__desc 
IPSCONTENT;

if ( $advanced ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='searchBlurb'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
	</header>
	
	
IPSCONTENT;

if ( \IPS\Content\Search\Query::isRebuildRunning() ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--info">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_rebuild_is_running', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class='ipsBox ipsPull i-margin-top_3' data-controller='core.front.search.filters' id='elSearchFilters'>
		{$filters}
	</div>

	<div id="elSearch_main" class='i-margin-top_3' data-role='filterContent'>
		
IPSCONTENT;

if ( !$advanced ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "search", \IPS\Request::i()->app )->results( $termArray, $title, $results, $pagination, $baseUrl, $count );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>

IPSCONTENT;

		return $return;
}

	function searchReaction( $reactions, $itemUrl, $repCount ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( \IPS\Settings::i()->reaction_count_display == 'count' ):
$return .= <<<IPSCONTENT

	<div class='ipsReact_reactCountOnly ipsReact_reactCountOnly_mini 
IPSCONTENT;

if ( $repCount >= 1 ):
$return .= <<<IPSCONTENT
i-background_positive
IPSCONTENT;

elseif ( $repCount < 0 ):
$return .= <<<IPSCONTENT
i-background_negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-background_2
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( !\count( $reactions ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='reactCount'>
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $repCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='ipsReactOverview ipsReactOverview_small'>
		<ul>
			
IPSCONTENT;

foreach ( $reactions AS $reactID => $count ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$reaction = \IPS\Content\Reaction::load( $reactID );
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $itemUrl->setQueryString( 'reaction', $reaction->id ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip title="
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( 'reaction_title_' . $reaction->id )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'see_who_reacted_x', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<span data-ipsTooltip title="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction->_icon->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt="
IPSCONTENT;

$val = "reaction_title_{$reaction->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT
" loading="lazy">
					
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ):
$return .= <<<IPSCONTENT

						</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						</span>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<li class='ipsReactOverview_repCount'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $repCount, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
		</ul>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}